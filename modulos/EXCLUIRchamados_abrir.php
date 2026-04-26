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
$lojaOrigem    = intval($_SESSION['loja']  ?? 0);
$setorOrigem   = intval($_SESSION['id_setor'] ?? $_SESSION['setor'] ?? 0);
$nomeUsuario   = $_SESSION['nome']  ?? $usuario;

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
// LISTA DE SETORES E LOJAS
// ===============================
$setores = $conn->query("SELECT id, nome FROM setores ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$lojas   = $conn->query("
    SELECT id, nome FROM lojas 
    WHERE LOWER(nome) NOT IN ('escritorio','escritório')
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

$setoresIds = array_column($setores, 'id');
$lojasIds   = array_column($lojas, 'id');

$erro = "";

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo        = trim($_POST['titulo'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $tipo_destino  = trim($_POST['tipo_destino'] ?? '');
    $setor_destino = intval($_POST['setor_destino'] ?? 0);
    $loja_destino  = intval($_POST['loja_destino'] ?? 0);

    if (!$titulo || !$descricao || !$tipo_destino || !$idSolicitante) {
        $erro = "❌ Preencha todos os campos obrigatórios.";
    } else {

        if ($tipo_destino === 'setor' && !in_array($setor_destino, $setoresIds)) {
            $erro = "❌ Selecione um setor válido.";
        }

        if ($tipo_destino === 'loja' && !in_array($loja_destino, $lojasIds)) {
            $erro = "❌ Selecione uma loja válida.";
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

                $setor_destino_db = ($tipo_destino === 'setor') ? $setor_destino : 0;
                $loja_destino_db  = ($tipo_destino === 'loja')  ? $loja_destino  : 0;

                // ===============================
                // DESCOBRIR NOME DO DESTINO
                // ===============================
                $nomeDestino = "";

                if ($tipo_destino === 'setor') {
                    foreach ($setores as $s) {
                        if ($s['id'] == $setor_destino) {
                            $nomeDestino = "o setor <strong>{$s['nome']}</strong>";
                            break;
                        }
                    }
                } else {
                    foreach ($lojas as $l) {
                        if ($l['id'] == $loja_destino) {
                            $nomeDestino = "<strong>{$l['nome']}</strong>";
                            break;
                        }
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
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'aberto', ?)
                ");

                $stmt->bind_param(
                    "sssiiiii",
                    $codigo,
                    $titulo,
                    $descricao,
                    $setor_destino_db,
                    $loja_destino_db,
                    $setorOrigem,
                    $lojaOrigem,
                    $idSolicitante
                );

                if ($stmt->execute()) {

                    $_SESSION['sucesso'] =
                        "✔️ Chamado <strong>{$codigo}</strong> aberto com sucesso para {$nomeDestino}.";

                    header("Location: /modulos/chamados_publico.php");
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
<h2>➕ Abrir novo chamado</h2>
<p>Preencha os dados abaixo para registrar um chamado técnico ou administrativo.</p>

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

    <label for="tipo_destino">Destino:</label>
    <select id="tipo_destino" name="tipo_destino" required onchange="toggleDestino()">
        <option value="">— Selecione —</option>
        <option value="setor" <?= (($_POST['tipo_destino'] ?? '') === 'setor') ? 'selected' : '' ?>>Setor</option>
        <option value="loja"  <?= (($_POST['tipo_destino'] ?? '') === 'loja')  ? 'selected' : '' ?>>Loja</option>    </select>

    <div id="destinoSetor" style="display:<?= (($_POST['tipo_destino'] ?? '') === 'setor') ? 'block' : 'none' ?>;">
        <label for="setor_destino">Setor de destino:</label>
        <select id="setor_destino" name="setor_destino">
            <option value="">— Selecione —</option>
            <?php foreach ($setores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (intval($_POST['setor_destino'] ?? 0) === intval($s['id'])) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="destinoLoja" style="display:<?= (($_POST['tipo_destino'] ?? '') === 'loja') ? 'block' : 'none' ?>;">
        <label for="loja_destino">Loja de destino:</label>
        <select id="loja_destino" name="loja_destino">
            <option value="">— Selecione —</option>
            <?php foreach ($lojas as $l): ?>
                <option value="<?= $l['id'] ?>" <?= (intval($_POST['loja_destino'] ?? 0) === intval($l['id'])) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="botoes-acoes">
        <a class="btn-voltar" href="chamados_publico.php">🔙 Voltar</a>
        <button type="submit" class="btn-submit">📨 Enviar chamado</button>
    </div>

</form>

<script>
function toggleDestino() {
    const tipo = document.getElementById('tipo_destino').value;

    if (tipo === 'trilho') {
        window.location.href = 'chamados_trilho_abrir.php';
        return;
    }

    document.getElementById('destinoSetor').style.display = (tipo === 'setor') ? 'block' : 'none';
    document.getElementById('destinoLoja').style.display  = (tipo === 'loja')  ? 'block' : 'none';
}
</script>


</div>

<?php
$conteudo = ob_get_clean();
$modais = "";

include ROOT_PATH . "/includes/layout.php";
