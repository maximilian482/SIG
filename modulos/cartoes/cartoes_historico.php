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


$cartoes = $conn->query("SELECT codigo_cartao FROM cartoes ORDER BY codigo_cartao ASC");

$filtro = $_GET['cartao'] ?? '';

ob_start();
?>
<link rel="stylesheet" href="/css/cartoes.css">

<h1 class="mb-3">📜 Histórico Completo do Cartão</h1>

<a href="cartoes_mestre.php" class="btn btn-cinza mb-3">⬅ Voltar</a>

<p>Visualize todas as retiradas, devoluções e sinistros deste cartão.</p>

<div class="cartoes-card">

    <h3>Filtrar por Cartão</h3>

    <form method="GET" class="form-grid">

        <div class="form-group">
            <label>Código do Cartão:</label>
            <select name="cartao" required>
                <option value="">Selecione...</option>
                <?php while ($c = $cartoes->fetch_assoc()): ?>
                    <option value="<?= $c['codigo_cartao'] ?>" <?= ($filtro === $c['codigo_cartao']) ? 'selected' : '' ?>>
                        <?= $c['codigo_cartao'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-actions">
            <button class="btn btn-novo">🔍 Buscar</button>
            <a href="cartoes_historico.php" class="btn btn-cinza">Limpar</a>
        </div>

    </form>
</div>

<?php if ($filtro): ?>

<div class="cartoes-card mt-4">
    <h3>Histórico de Movimentações</h3>

    <?php
        // Histórico da tabela cartoes_historico
        $stmtH = $conn->prepare("
            SELECT 
                h.id,
                h.codigo_cartao,
                h.acao,
                h.descricao,
                h.data_hora,
                h.id_atribuicao
            FROM cartoes_historico h
            WHERE h.codigo_cartao = ?
            AND (h.acao = 'RETIRADA' OR h.acao = 'DEVOLVIDO')
            ORDER BY h.data_hora DESC

        ");
        $stmtH->bind_param("s", $filtro);
        $stmtH->execute();
        $historico = $stmtH->get_result();

        // Devoluções da tabela cartoes_atribuicoes + ciclo
        $stmtA = $conn->prepare("
            SELECT 
                a.id,
                a.codigo_cartao,
                a.cpf_funcionario,
                a.data_atribuicao,
                a.data_devolucao,
                a.saldo_entregue,
                a.saldo_devolvido,
                cc.divergencia,
                a.assinatura_funcionario,
                a.assinatura_funcionario_devolucao,
                a.assinatura_gestor,
                a.assinatura_gestor_devolucao,
                a.id_ciclo
            FROM cartoes_atribuicoes a
            LEFT JOIN cartoes_ciclos cc ON cc.id_ciclo = a.id_ciclo
            WHERE a.codigo_cartao = ?
              AND a.data_devolucao IS NOT NULL
            ORDER BY a.data_devolucao DESC
        ");
        $stmtA->bind_param("s", $filtro);
        $stmtA->execute();
        $atribuicoes = $stmtA->get_result();

        $registros = [];

        while ($h = $historico->fetch_assoc()) {
            $registros[] = [
                'tipo'           => 'historico',
                'acao'           => $h['acao'],
                'descricao'      => $h['descricao'],
                'data'           => $h['data_hora'],
                'id_atribuicao'  => $h['id_atribuicao'],
                'saldo_entregue' => null,
                'saldo_devolvido'=> null,
                'divergencia'    => null
            ];
        }

        while ($a = $atribuicoes->fetch_assoc()) {

            $existe = false;
            foreach ($registros as $r) {
                if ($r['id_atribuicao'] == $a['id']) {
                    $existe = true;
                    break;
                }
            }

            if (!$existe) {
                $registros[] = [
                    'tipo'           => 'atribuicao',
                    'acao'           => 'DEVOLVIDO',
                    'descricao'      => "Cartão devolvido por " . buscarNome($conn, $a['cpf_funcionario']),
                    'data'           => $a['data_devolucao'],
                    'id_atribuicao'  => $a['id'],
                    'saldo_entregue' => $a['saldo_entregue'],
                    'saldo_devolvido'=> $a['saldo_devolvido'],
                    'divergencia'    => $a['divergencia']
                ];
            }
        }

        usort($registros, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
    ?>

    <table class="tabela-padrao">
        <thead>
            <tr>
                <th>Data</th>
                <th>Ação</th>
                <th>Descrição</th>
                <th>Saldo na ação</th>
                <th>Divergência</th>
                <th>Assinaturas</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($registros as $r): ?>

                <?php
                    // Saldo na ação
                    $saldoAcao = '—';

                    if ($r['id_atribuicao']) {

                        if ($r['acao'] === 'RETIRADA') {
                            if ($r['saldo_entregue'] === null) {
                                $stmtS = $conn->prepare("SELECT saldo_entregue FROM cartoes_atribuicoes WHERE id = ?");
                                $stmtS->bind_param("i", $r['id_atribuicao']);
                                $stmtS->execute();
                                $r['saldo_entregue'] = $stmtS->get_result()->fetch_assoc()['saldo_entregue'] ?? null;
                            }
                            $saldoAcao = $r['saldo_entregue'];

                        } elseif ($r['acao'] === 'DEVOLVIDO') {
                            if ($r['saldo_devolvido'] === null) {
                                $stmtS = $conn->prepare("SELECT saldo_devolvido FROM cartoes_atribuicoes WHERE id = ?");
                                $stmtS->bind_param("i", $r['id_atribuicao']);
                                $stmtS->execute();
                                $r['saldo_devolvido'] = $stmtS->get_result()->fetch_assoc()['saldo_devolvido'] ?? null;
                            }
                            $saldoAcao = $r['saldo_devolvido'];
                        }
                    }

                    if ($saldoAcao !== null && $saldoAcao !== '—') {
                        $saldoAcao = 'R$ ' . number_format($saldoAcao, 2, ',', '.');
                    } else {
                        $saldoAcao = '—';
                    }

                    // Assinaturas
                    $btnAssinatura = "<span class='text-muted'>Sem assinatura</span>";

                    if ($r['id_atribuicao']) {

                        $stmtA = $conn->prepare("
                            SELECT 
                                assinatura_funcionario,
                                assinatura_gestor,
                                assinatura_funcionario_devolucao,
                                assinatura_gestor_devolucao
                            FROM cartoes_atribuicoes
                            WHERE id = ?
                        ");
                        $stmtA->bind_param("i", $r['id_atribuicao']);
                        $stmtA->execute();
                        $atrSign = $stmtA->get_result()->fetch_assoc();

                        if ($r['acao'] === 'RETIRADA' && ($atrSign['assinatura_funcionario'] || $atrSign['assinatura_gestor'])) {
                            $btnAssinatura = "
                                <a href='/modulos/cartoes/cartoes_ver_assinaturas.php?id={$r['id_atribuicao']}&origem=historico'
                                   target='_blank' class='btn fs-4'>
                                    🖊️ 
                                </a>
                            ";
                        }

                        if ($r['acao'] === 'DEVOLVIDO' && ($atrSign['assinatura_funcionario_devolucao'] || $atrSign['assinatura_gestor_devolucao'])) {
                            $btnAssinatura = "
                                <a href='/modulos/cartoes/cartoes_ver_assinaturas_devolucao.php?id={$r['id_atribuicao']}&origem=historico'
                                   target='_blank' class='btn fs-4'>
                                    🖊️ 
                                </a>
                            ";
                        }
                    }

                    // Ícones de divergência
                    $iconeDivergencia = '—';

                    if ($r['acao'] === 'DEVOLVIDO') {

                        if ($r['divergencia'] === null) {
                            $stmtD = $conn->prepare("
                                SELECT cc.divergencia 
                                FROM cartoes_ciclos cc
                                JOIN cartoes_atribuicoes a ON a.id_ciclo = cc.id_ciclo
                                WHERE a.id = ?
                            ");
                            $stmtD->bind_param("i", $r['id_atribuicao']);
                            $stmtD->execute();
                            $r['divergencia'] = $stmtD->get_result()->fetch_assoc()['divergencia'] ?? 0;
                        }

                        if ($r['divergencia'] != 0) {
                            $iconeDivergencia = "
                                <a href='cartoes_evento_devolucao.php?id={$r['id_atribuicao']}' class='text-warning fs-4'>
                                    ⚠
                                </a>
                            ";
                        } else {
                            $iconeDivergencia = "
                                <a href='cartoes_evento_devolucao.php?id={$r['id_atribuicao']}' class='btn-success fs-4'>
                                    ✔
                                </a>
                            ";
                        }

                    }
                ?>

                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($r['data'])) ?></td>
                    <td><strong><?= $r['acao'] ?></strong></td>
                    <td><?= $r['descricao'] ?></td>
                    <td><?= $saldoAcao ?></td>
                    <td><?= $iconeDivergencia ?></td>
                    <td><?= $btnAssinatura ?></td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php endif; ?>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
