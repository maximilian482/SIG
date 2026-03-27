<?php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

$conn = conectar();

$erro = "";
$sucesso = "";

// SALVAR MODELO DE TAREFA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = $_POST['descricao'] ?? '';

    $tipo_responsavel = $_POST['tipo_responsavel'] ?? null;
    $resp_setor       = $_POST['responsavel_setor'] ?? null;
    $resp_loja        = $_POST['responsavel_loja'] ?? null;
    $resp_usuario     = $_POST['responsavel_usuario'] ?? null;

    if ($titulo === '') {
        $erro = "O título é obrigatório.";
    } elseif (!$tipo_responsavel) {
        $erro = "O tipo de responsável é obrigatório.";
    } else {

        $responsavel_setor   = null;
        $responsavel_loja    = null;
        $responsavel_usuario = null;

        if ($tipo_responsavel === 'setor') {
            $responsavel_setor = $resp_setor !== '' ? (int)$resp_setor : null;
        } elseif ($tipo_responsavel === 'loja') {
            $responsavel_loja = $resp_loja !== '' ? (int)$resp_loja : null;
        } elseif ($tipo_responsavel === 'usuario') {
            $responsavel_usuario = $resp_usuario !== '' ? (int)$resp_usuario : null;
        }

        $sql = "INSERT INTO tarefas_modelo (
                    titulo,
                    descricao,
                    tipo_responsavel,
                    responsavel_setor,
                    responsavel_loja,
                    responsavel_usuario,
                    ativo
                ) VALUES (?, ?, ?, ?, ?, ?, 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssiii",
            $titulo,
            $descricao,
            $tipo_responsavel,
            $responsavel_setor,
            $responsavel_loja,
            $responsavel_usuario
        );

        if ($stmt->execute()) {
            $sucesso = "Modelo de tarefa criado com sucesso!";
        } else {
            $erro = "Erro ao criar modelo de tarefa: " . $stmt->error;
        }
    }
}

// Carregar listas
$setores = $conn->query("SELECT id, nome FROM setores ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$usuarios = $conn->query("SELECT id, nome FROM funcionarios ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$lojas = $conn->query("SELECT id, nome FROM lojas WHERE ativo = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Criar Modelo de Tarefa</title>
<style>
body { background:#f5f5f5; font-family:Arial; }
.form-wrapper {
    max-width:600px;
    margin:20px auto;
    background:#fff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 12px rgba(0,0,0,0.1);
}
h1 {
    color:#006437;
    border-left:6px solid #00A859;
    padding-left:10px;
    text-align:center;
}
label { font-weight:bold; color:#006437; display:block; margin-top:10px; }
input, select, textarea {
    width:100%; padding:8px; border:2px solid #00A859; border-radius:5px; margin-top:5px;
}
.btn { background:#006437; color:white; padding:10px; border:none; border-radius:6px; cursor:pointer; width:100%; margin-top:20px; }
</style>
</head>
<body>

<div class="form-wrapper">
    <h1>Criar Modelo de Tarefa</h1>

    <?php if ($erro): ?>
        <p style="color:red;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <p style="color:green;"><?= htmlspecialchars($sucesso) ?></p>
    <?php endif; ?>

    <form method="post">

        <label>Título *</label>
        <input type="text" name="titulo" required>

        <label>Descrição</label>
        <textarea name="descricao" rows="4"></textarea>

        <label>Tipo de responsável *</label>
        <select id="tipo_responsavel" name="tipo_responsavel" required>
            <option value="">Selecione</option>
            <option value="usuario">Usuário</option>
            <option value="setor">Setor</option>
            <option value="loja">Loja</option>
        </select>

        <div id="resp_usuario" style="display:none;">
            <label>Responsável (Usuário)</label>
            <select name="responsavel_usuario" id="responsavel_usuario">
                <option value="">Selecione</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="resp_setor" style="display:none;">
            <label>Responsável (Setor)</label>
            <select name="responsavel_setor" id="responsavel_setor">
                <option value="">Selecione</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="resp_loja" style="display:none;">
            <label>Responsável (Loja)</label>
            <select name="responsavel_loja" id="responsavel_loja">
                <option value="">Selecione</option>
                <?php foreach ($lojas as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn">Salvar Modelo</button>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipo = document.getElementById('tipo_responsavel');
    const blUsuario = document.getElementById('resp_usuario');
    const blSetor   = document.getElementById('resp_setor');
    const blLoja    = document.getElementById('resp_loja');

    function atualizarCampos() {
        const v = tipo.value;
        blUsuario.style.display = 'none';
        blSetor.style.display   = 'none';
        blLoja.style.display    = 'none';

        if (v === 'usuario') blUsuario.style.display = 'block';
        if (v === 'setor')   blSetor.style.display   = 'block';
        if (v === 'loja')    blLoja.style.display    = 'block';
    }

    tipo.addEventListener('change', atualizarCampos);
    atualizarCampos();
});
</script>

</body>
</html>
