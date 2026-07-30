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


// PROCESSA FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo   = trim($_POST['codigo_cartao']);
    $banco    = trim($_POST['banco']);
    $conta    = trim($_POST['conta_associada']);
    $numero   = trim($_POST['numero_cartao']);
    $limite   = floatval($_POST['limite']);
    $saldo    = floatval($_POST['saldo_inicial']);
    $status   = $_POST['status'];

    if ($codigo === '' || $banco === '' || $conta === '' || $numero === '' || $limite <= 0) {
        $_SESSION['flash'] = [
            'mensagem' => 'Preencha todos os campos obrigatórios.',
            'tipo' => 'erro'
        ];
        header("Location: cartoes_cadastrar.php");
        exit;
    }

    // Insere no banco (AGORA COM saldo_atual)
    $stmt = $conn->prepare("
        INSERT INTO cartoes 
        (codigo_cartao, banco, conta_associada, numero_cartao, limite, saldo_atual, status, ultima_movimentacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssssdds", $codigo, $banco, $conta, $numero, $limite, $saldo, $status);
    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Cartão cadastrado com sucesso!',
        'tipo' => 'sucesso'
    ];

    header("Location: cartoes_mestre.php");
    exit;
}

ob_start();
?>

<div class="container py-4">

    <h1 class="mb-3">➕ Cadastrar Cartão</h1>
    <p class="text-muted">Preencha os dados abaixo para cadastrar um novo cartão corporativo.</p>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Código do Cartão *</label>
                    <input type="text" name="codigo_cartao" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Banco *</label>
                    <input type="text" name="banco" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Conta Associada *</label>
                    <input type="text" name="conta_associada" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Número do Cartão *</label>
                    <input type="text" name="numero_cartao" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Limite *</label>
                    <input type="number" step="0.01" name="limite" class="form-control" required>
                </div>

                 <div class="col-md-3">
                    <label class="form-label">Dia de vencimento *</label>
                    <input type="number" step="0.01" name="vencimento_dia" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Saldo Inicial *</label>
                    <input type="number" step="0.01" name="saldo_inicial" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="DISPONÍVEL">DISPONÍVEL</option>
                        <option value="BLOQUEADO">BLOQUEADO</option>
                    </select>
                </div>

                <div class="col-12 mt-3">
                    <button class="btn btn-primary">Cadastrar Cartão</button>
                    <a href="cartoes_mestre.php" class="btn btn-secondary">Voltar</a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
