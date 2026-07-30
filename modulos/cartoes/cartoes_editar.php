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


// Recebe o código do cartão
$codigo_cartao = $_GET['cartao'] ?? '';

if (!$codigo_cartao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Cartão inválido.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_mestre.php");
    exit;
}

// Busca dados do cartão
$stmt = $conn->prepare("
    SELECT *
    FROM cartoes
    WHERE codigo_cartao = ?
");
$stmt->bind_param("s", $codigo_cartao);
$stmt->execute();
$cartao = $stmt->get_result()->fetch_assoc();

if (!$cartao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Cartão não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_mestre.php");
    exit;
}

// Verifica status do cartão
$status = $cartao['status'];

// Busca atribuição ativa (se existir)
$atr = $conn->query("
    SELECT a.*, f.nome 
    FROM cartoes_atribuicoes a
    JOIN funcionarios f ON f.cpf = a.cpf_funcionario
    WHERE a.codigo_cartao = '{$cartao['codigo_cartao']}'
      AND a.ativo = 1
")->fetch_assoc();

// Função de mensagem premium
function msgPremium($tipo, $titulo, $texto) {
    echo "
    <div class='alert alert-$tipo shadow-sm p-3 mb-4'>
        <h5 class='mb-2'><strong>$titulo</strong></h5>
        <p>$texto</p>
    </div>
    ";
}

ob_start();
?>

<div class="container py-4" style="max-width: 900px;">

    <h1 class="mb-3">✏ Editar Cartão <?= $cartao['codigo_cartao'] ?></h1>
    <p class="text-muted">Atualize os dados do cartão corporativo.</p>

    <a href="/modulos/cartoes/cartoes_mestre.php" class="btn btn-secondary mb-3">⬅ Voltar</a>

    <?php
    // Caso EM USO → mostrar aviso, mas permitir edição
    if ($status === 'EM USO' && $atr) {

        $nome = $atr['nome'];
        $p = explode(' ', trim($nome));
        $nomeReduzido = $p[0] . ' ' . end($p);

        msgPremium(
            "warning",
            "Cartão em Uso",
            "Este cartão está <strong>EM USO</strong> pelo funcionário <strong>$nomeReduzido</strong>.<br>
            Você pode alterar o status abaixo. Ao salvar, o cartão sairá da posse do funcionário."
        );
    }

    // Caso AGUARDANDO ASSINATURA → bloquear edição
    if ($status === 'AGUARDANDO ASSINATURA' && $atr) {

        $nome = $atr['nome'];
        $p = explode(' ', trim($nome));
        $nomeReduzido = $p[0] . ' ' . end($p);

        msgPremium(
            "danger",
            "Alteração Bloqueada",
            "Este cartão está <strong>AGUARDANDO ASSINATURA</strong> do funcionário <strong>$nomeReduzido</strong>.<br>
            Para editar este cartão, você deve primeiro <strong>EXCLUIR a atribuição</strong>."
        );

        echo "
            <a href='/modulos/cartoes/cartoes_atribuir.php' class='btn btn-secondary'>
                Ir para Atribuições
            </a>
            ";


        // IMPORTANTE: NÃO EXIBIR FORMULÁRIO
        echo "</div>";
        $conteudo = ob_get_clean();
        include __DIR__ . '/../../includes/layout.php';
        exit;
    }
    ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="/modulos/cartoes/cartoes_editar_salvar.php" class="row g-3">

                <input type="hidden" name="codigo_cartao" value="<?= $cartao['codigo_cartao'] ?>">

                <div class="col-md-6">
                    <label class="form-label">Banco:</label>
                    <input type="text" name="banco" class="form-control"
                           value="<?= $cartao['banco'] ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Conta Associada:</label>
                    <input type="text" name="conta_associada" class="form-control"
                           value="<?= $cartao['conta_associada'] ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Número do Cartão:</label>
                    <input type="text" name="numero_cartao" class="form-control"
                           value="<?= $cartao['numero_cartao'] ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Limite:</label>
                    <input type="number" step="0.01" name="limite" class="form-control"
                           value="<?= $cartao['limite'] ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Saldo Atual:</label>
                    <input type="number" step="0.01" name="saldo_atual" class="form-control"
                           value="<?= $cartao['saldo_atual'] ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Limite:</label>
                    <input type="number" step="0.01" name="limite" class="form-control"
                        value="<?= $cartao['limite'] ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Saldo Atual:</label>
                    <input type="number" step="0.01" name="saldo_atual" class="form-control"
                        value="<?= $cartao['saldo_atual'] ?>" required>
                </div>

                <!-- NOVO CAMPO: DIA DE VENCIMENTO -->
                <div class="col-md-3">
                    <label class="form-label">Dia de Vencimento:</label>
                    <input type="number"
                        name="vencimento_dia"
                        class="form-control"
                        min="1"
                        max="31"
                        value="<?= $cartao['vencimento_dia'] ?>"
                        required>
                </div>


                <div class="col-md-6">
                    <label class="form-label">Status:</label>
                    <select name="status" class="form-select" required>
                        <?php
                        // LISTA COMPLETA DE STATUS
                        $statusList = [
                            'DISPONÍVEL',
                            'AGUARDANDO ASSINATURA',
                            'EM USO',
                            'PERDIDO',
                            'EXTRAVIADO',
                            'ROUBADO',
                            'DANIFICADO',
                            'BLOQUEADO',
                            'CANCELADO',
                            'INATIVO'
                        ];

                        foreach ($statusList as $s):
                        ?>
                            <option value="<?= $s ?>" <?= ($cartao['status'] === $s ? 'selected' : '') ?>>
                                <?= $s ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Última Utilização:</label>
                    <input type="text" class="form-control" 
                           value="<?= ($cartao['ultima_movimentacao'] ? date('d/m/Y', strtotime($cartao['ultima_movimentacao'])) : '—') ?>"
                           disabled>
                </div>

                <div class="col-12">
                    <label class="form-label">Motivo Inativação / Observações:</label>
                    <textarea name="motivo_inativacao" class="form-control" rows="3"><?= $cartao['motivo_inativacao'] ?></textarea>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <button class="btn btn-primary">💾 Salvar Alterações</button>
                    <a href="/modulos/cartoes/cartoes_mestre.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
