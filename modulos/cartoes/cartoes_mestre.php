<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// CPF sempre limpo e padronizado
$cpfLogado = trim(preg_replace('/\D/', '', $_SESSION['cpf'] ?? ''));

// Verifica acesso pelo EDITAR ACESSOS
if (!temAcesso($conn, $cpfLogado, 'cartoes')) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não possui acesso ao módulo de Cartões Corporativos.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Busca cartões cadastrados
$cartoes = $conn->query("
    SELECT * 
    FROM cartoes 
    ORDER BY codigo_cartao ASC
");

if (isset($_SESSION['flash']) && 
    $_SESSION['flash']['mensagem'] === 'Você não possui acesso ao módulo de Cartões Corporativos.') {
    unset($_SESSION['flash']);
}


ob_start();
?>
<link rel="stylesheet" href="/css/cartoes.css">

<div class="container-fluid py-4">

    <h1 class="mb-3">💳 Cartões Corporativos</h1>
    <p class="text-muted">Painel mestre de gestão: cadastro, atribuição, ocorrências, utilizações e auditoria.</p>

    <!-- BOTÕES DE AÇÃO -->
    <div class="row g-3 mb-4">

        <div class="col-auto">
            <a href="cartoes_cadastrar.php" class="btn btn-primary">
                ➕ Cadastrar Cartão
            </a>
        </div>

        <div class="col-auto">
            <a href="cartoes_atribuir.php" class="btn btn-secondary">
                👤 Atribuir Cartão
            </a>
        </div>

        <div class="col-auto">
            <a href="cartoes_ocorrencias.php" class="btn btn-outline-dark">
                📇 Ocorrências
            </a>
        </div>

        <div class="col-auto">
            <a href="cartoes_utilizacao.php" class="btn btn-outline-dark">
                🧾 Utilizações / Faturas
            </a>
        </div>

        <div class="col-auto">
            <a href="cartoes_historico.php" class="btn btn-outline-dark">
                📜 Histórico Geral
            </a>
        </div>

    </div>

    <!-- TABELA -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">📋 Cartões Cadastrados</h5>
        </div>

        <div class="card-body p-0">

<div class="table-responsive">
<table class="table table-striped table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>Código</th>
            <th>Saldo Atual</th>
            <th>Venc.</th>
            <th>Última Atualização</th>
            <th>Status</th>
            <th>Em posse de</th>
            <th class="text-center">Ações</th>
        </tr>
    </thead>

    <tbody>
        <?php while ($c = $cartoes->fetch_assoc()): ?>

        <?php
            // POSSE ATUAL — via cartoes_atribuicoes + setor
            $atr = $conn->prepare("
                SELECT f.nome, s.nome AS setor
                FROM cartoes_atribuicoes a
                JOIN funcionarios f ON f.cpf = a.cpf_funcionario
                JOIN setores s ON s.id = f.id_setor
                WHERE a.codigo_cartao = ?
                  AND a.ativo = 1
                LIMIT 1
            ");
            $atr->bind_param("s", $c['codigo_cartao']);
            $atr->execute();
            $res = $atr->get_result();

            if ($res->num_rows) {
                $dados = $res->fetch_assoc();
                $nomeCompleto = trim($dados['nome']);
                $partes = explode(" ", $nomeCompleto);
                $primeiroNome = $partes[0];
                $setor = $dados['setor'];

                $emPosse = "$primeiroNome – $setor";
            } else {
                $emPosse = '—';
            }

            /// Badge de status (novo fluxo)
            $status = $c['status'];

            $badgeClass = match($status) {

                // Status positivos
                'DISPONÍVEL'             => 'bg-success',
                'EM USO'                 => 'bg-primary',
                'AGUARDANDO ASSINATURA'  => 'bg-warning text-dark',

                // Status críticos (vermelhos)
                'PERDIDO'                => 'bg-danger',
                'EXTRAVIADO'             => 'bg-danger',
                'ROUBADO'                => 'bg-danger',
                'DANIFICADO'             => 'bg-danger',

                // Status administrativos
                'BLOQUEADO'              => 'bg-danger',
                'CANCELADO'              => 'bg-danger',
                'INATIVO'                => 'bg-secondary',

                // Qualquer outro status inesperado
                default                  => 'bg-dark'
            };


            // ID SEGURO PARA MODAL
            $modalID = preg_replace('/[^A-Za-z0-9]/', '', $c['codigo_cartao']);
        ?>
            <?php
            // Buscar atribuição ativa para este cartão
            $stmtAtrID = $conn->prepare("
                SELECT id 
                FROM cartoes_atribuicoes
                WHERE codigo_cartao = ?
                AND ativo = 1
                LIMIT 1
            ");
            $stmtAtrID->bind_param("s", $c['codigo_cartao']);
            $stmtAtrID->execute();
            $atrID = $stmtAtrID->get_result()->fetch_assoc()['id'] ?? null;
            ?>
        <tr>
            <td><?= $c['codigo_cartao'] ?></td>

            <td>R$ <?= number_format($c['saldo_atual'], 2, ',', '.') ?></td>
            <td>
                <?= $c['vencimento_dia'] ?: '—' ?>
            </td>


            <td>
                <?= $c['ultima_movimentacao'] 
                    ? date('d/m/Y', strtotime($c['ultima_movimentacao'])) 
                    : '—' ?>
            </td>

            <td>
                <span class="badge <?= $badgeClass ?>">
                    <?= $status ?>
                </span>
            </td>

            <td><?= $emPosse ?></td>

            <td class="text-center">

                <!-- Editar -->
                <a href="/modulos/cartoes/cartoes_editar.php?cartao=<?= urlencode($c['codigo_cartao']) ?>" 
                   class="btn btn-sm btn-outline-secondary me-1" title="Editar cartão">
                    ✏️
                </a>

                <!-- Histórico -->
                <a href="/modulos/cartoes/cartoes_historico.php?cartao=<?= urlencode($c['codigo_cartao']) ?>" 
                   class="btn btn-sm btn-outline-primary me-1" title="Histórico">
                    📜
                </a>

                <!-- Detalhes -->
                <button class="btn btn-sm btn-outline-dark" 
                        data-bs-toggle="modal" 
                        data-bs-target="#detalhes<?= $modalID ?>"
                        title="Detalhes">
                    🔍
                </button>

                <!-- Recolher Cartão -->

                <?php if ($status === 'EM USO' && $atrID): ?>
                    <a href="cartoes_assinar_devolucao.php?id=<?= $atrID ?>" 
                    class="btn btn-sm me-1" 
                    title="Recolher cartão">
                        🔄
                    </a>
                <?php endif; ?>


            </td>
        </tr>

        <!-- MODAL DE DETALHES -->
        <div class="modal fade" id="detalhes<?= $modalID ?>" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title">Detalhes do Cartão <?= $c['codigo_cartao'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">

                <p><strong>Banco:</strong> <?= $c['banco'] ?></p>
                <p><strong>Conta Associada:</strong> <?= $c['conta_associada'] ?></p>
                <p><strong>Número do Cartão:</strong> <?= $c['numero_cartao'] ?></p>
                <p><strong>Vencimento:</strong> <?= $c['vencimento_dia'] ?: '—' ?></p>

                <p><strong>Limite:</strong> R$ <?= number_format($c['limite'], 2, ',', '.') ?></p>

                <p><strong>Saldo Atual:</strong> R$ <?= number_format($c['saldo_atual'], 2, ',', '.') ?></p>

                <p><strong>Status:</strong> 
                    <span class="badge <?= $badgeClass ?>"><?= $status ?></span>
                </p>

                <p><strong>Última Movimentação:</strong> 
                    <?= $c['ultima_movimentacao'] ? date('d/m/Y', strtotime($c['ultima_movimentacao'])) : '—' ?>
                </p>

                <hr>

                <p><strong>Em posse de:</strong> <?= $emPosse ?></p>

              </div>

              <?php
                // Buscar atribuição ativa para este cartão
                $stmtAtrID = $conn->prepare("
                    SELECT id 
                    FROM cartoes_atribuicoes
                    WHERE codigo_cartao = ?
                    AND ativo = 1
                    LIMIT 1
                ");
                $stmtAtrID->bind_param("s", $c['codigo_cartao']);
                $stmtAtrID->execute();
                $atrID = $stmtAtrID->get_result()->fetch_assoc()['id'] ?? null;
                ?>

                <?php if ($status === 'EM USO' && $atrID): ?>
                    <a href="cartoes_assinar_devolucao.php?id=<?= $atrID ?>" 
                    class="btn btn-warning w-100 mb-3">
                        🔄 Recolher Cartão
                    </a>
                <?php endif; ?>


              <div class="modal-footer">
                <a href="cartoes_editar.php?cartao=<?= $c['codigo_cartao'] ?>" class="btn btn-primary">
                    ✏️ Editar Cartão
                </a>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
              </div>

            </div>
          </div>
        </div>

        <?php endwhile; ?>
    </tbody>

</table>
</div>

        </div>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
