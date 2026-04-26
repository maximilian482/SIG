<?php
session_start();
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';
$conn = conectar();

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['usuario']) || !isset($_SESSION['cpf'])) {
    header('Location: ../login.php');
    exit;
}

$usuario       = $_SESSION['usuario'];
$cpf           = $_SESSION['cpf'];
$setorOrigem   = intval($_SESSION['id_setor'] ?? $_SESSION['setor'] ?? 0);
$nomeUsuario   = $_SESSION['nome'] ?? $usuario;

// ===============================
// BUSCAR ID DO FUNCIONÁRIO
// ===============================
$cpfLimpo = preg_replace('/\D+/', '', $cpf);
$idSolicitante = 0;

$stmtUser = $conn->prepare("
    SELECT id FROM funcionarios
    WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
");
$stmtUser->bind_param("s", $cpfLimpo);
$stmtUser->execute();
$resUser = $stmtUser->get_result();

if ($resUser->num_rows === 1) {
    $idSolicitante = intval($resUser->fetch_assoc()['id']);
}

// ===============================
// LISTA DE SETORES
// ===============================
$setores = $conn->query("SELECT id, nome FROM setores ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$setoresIds = array_column($setores, 'id');

$erro = "";

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo        = trim($_POST['titulo'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $setor_destino = intval($_POST['setor_destino'] ?? 0);

    if (!$titulo || !$descricao || !$setor_destino || !$idSolicitante) {
        $erro = "❌ Preencha todos os campos obrigatórios.";
    } else {

        if (!in_array($setor_destino, $setoresIds)) {
            $erro = "❌ Selecione um setor válido.";
        }

        if (empty($erro)) {

            // Prevenção de duplicatas
            $stmtDup = $conn->prepare("
                SELECT id FROM chamados
                WHERE solicitante_id = ? AND titulo = ? AND descricao = ?
                  AND data_abertura >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                LIMIT 1
            ");
            $stmtDup->bind_param("iss", $idSolicitante, $titulo, $descricao);
            $stmtDup->execute();
            $dup = $stmtDup->get_result();

            if ($dup->num_rows > 0) {
                $erro = "❌ Um chamado idêntico já foi criado recentemente.";
            }

            if (empty($erro)) {

                $codigo = 'CHM-' . date('Ymd') . '-' . rand(100, 999);

                // Nome do setor destino
                $nomeDestino = "";
                foreach ($setores as $s) {
                    if ($s['id'] == $setor_destino) {
                        $nomeDestino = "<strong>{$s['nome']}</strong>";
                        break;
                    }
                }

                // ===============================
                // INSERIR CHAMADO
                // ===============================
                $stmt = $conn->prepare("
                    INSERT INTO chamados (
                        codigo_chamado, titulo, descricao,
                        setor_destino, loja_destino, setor_origem, loja_origem,
                        data_abertura, status, solicitante_id
                    ) VALUES (?, ?, ?, ?, 0, ?, 0, NOW(), 'aberto', ?)
                ");

                $stmt->bind_param(
                    "sssiii",
                    $codigo,
                    $titulo,
                    $descricao,
                    $setor_destino,
                    $setorOrigem,
                    $idSolicitante
                );

                if ($stmt->execute()) {

                    $_SESSION['sucesso'] =
                        "✔️ Chamado <strong>{$codigo}</strong> aberto com sucesso para o setor {$nomeDestino}.";

                    header("Location: chamados_setores_publico.php");
                    exit;

                } else {
                    $erro = "❌ Erro ao registrar chamado. Tente novamente.";
                    error_log("ERRO abrir chamado: " . $stmt->error);
                }
            }
        }
    }
}

// ===============================
// CONTEÚDO DA PÁGINA
// ===============================
ob_start();
?>
<link rel="stylesheet" href="/css/chamados_abrir.css">

<div class="container-chamado">
<h2>➕ Abrir chamado para Setor</h2>
<p>Preencha os dados abaixo para registrar um chamado interno.</p>

<?php if (!empty($erro)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            mostrarMensagem("<?= addslashes($erro) ?>", "erro");
        });
    </script>
<?php endif; ?>

<form method="POST" class="form-chamado">

    <label for="titulo">Título:</label>
    <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">

    <label for="descricao">Descrição:</label>
    <textarea id="descricao" name="descricao" rows="4" required><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>

    <label for="setor_destino">Setor de destino:</label>
    <select id="setor_destino" name="setor_destino" required>
        <option value="">— Selecione —</option>
        <?php foreach ($setores as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (intval($_POST['setor_destino'] ?? 0) === intval($s['id'])) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="botoes-acoes">
        <a class="btn-voltar" href="chamados_setores_publico.php">🔙 Voltar</a>
        <button type="submit" class="btn-submit">📨 Enviar chamado</button>
    </div>

</form>
</div>

<?php
$conteudo = ob_get_clean();
$modais = "";
include ROOT_PATH . "/includes/layout.php";
?>
