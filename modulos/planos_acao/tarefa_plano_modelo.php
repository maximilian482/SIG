<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$idPlano = intval($_GET['id_plano'] ?? 0);
if ($idPlano <= 0) {
    die('Plano inválido.');
}

// Carrega dados do plano
$sqlPlano = "SELECT * FROM planos_acao WHERE id = ?";
$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bind_param("i", $idPlano);
$stmtPlano->execute();
$plano = $stmtPlano->get_result()->fetch_assoc();
$stmtPlano->close();

if (!$plano) {
    die('Plano não encontrado.');
}

// Carrega modelos
$sqlModelos = "
    SELECT tm.id, tm.titulo, tm.descricao, s.nome AS setor_nome
    FROM tarefas_modelo tm
    LEFT JOIN setores s ON s.id = tm.id_setor
    ORDER BY s.nome, tm.titulo
";
$modelos = $conn->query($sqlModelos)->fetch_all(MYSQLI_ASSOC);

$erro = '';
$val = [
    'titulo'           => '',
    'descricao'        => '',
    'data_limite'      => $plano['data_fim'] ?? '',
    'responsavel_tipo' => '',
    'responsavel_id'   => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $val['titulo']           = trim($_POST['titulo'] ?? '');
    $val['descricao']        = trim($_POST['descricao'] ?? '');
    $val['data_limite']      = trim($_POST['data_limite'] ?? '');
    $val['responsavel_tipo'] = trim($_POST['responsavel_tipo'] ?? '');
    $val['responsavel_id']   = intval($_POST['responsavel_id'] ?? 0);

    if ($val['titulo'] === '') {
        $erro = 'Informe o título da tarefa.';
    } elseif ($val['responsavel_tipo'] === '' || $val['responsavel_id'] <= 0) {
        $erro = 'Selecione o tipo de responsável e o responsável.';
    }

    $data_limite_db = $val['data_limite'] !== '' ? $val['data_limite'] : null;

    if (!$erro) {

        $sql = "INSERT INTO tarefas_plano
                (id_plano, id_modelo, titulo, descricao,
                 tipo_responsavel, responsavel_tipo, responsavel_id,
                 data_limite, prazo, status, criado_em, atualizado_em)
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, NULL, 'pendente', NOW(), NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssis",
            $idPlano,
            $val['titulo'],
            $val['descricao'],
            $val['responsavel_tipo'],
            $val['responsavel_tipo'],
            $val['responsavel_id'],
            $data_limite_db
        );

        if ($stmt->execute()) {
            $_SESSION['flash'] = [
                'mensagem' => 'Tarefa criada com sucesso.',
                'tipo'     => 'success'
            ];
            header("Location: planos_acao_detalhes.php?id={$idPlano}");
            exit;
        } else {
            $erro = "Erro ao salvar a tarefa: " . htmlspecialchars($stmt->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Adicionar Tarefa por Modelo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/css/planos_acao_form.css">
</head>
<body>

<div class="form-container">

<h2>Adicionar Tarefa ao Plano: <?= htmlspecialchars($plano['titulo']) ?></h2>

<?php if ($erro): ?>
    <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form method="post" id="formTarefa" novalidate>

    <label>Modelo de Tarefa</label>
    <select id="modelo_id" onchange="carregarModelo()">
        <option value="">— Selecionar modelo —</option>
        <?php foreach ($modelos as $m): ?>
            <option value="<?= $m['id'] ?>">
                [<?= htmlspecialchars($m['setor_nome'] ?? 'Sem setor') ?>] 
                <?= htmlspecialchars($m['titulo']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Título *</label>
    <input type="text" name="titulo" required value="<?= htmlspecialchars($val['titulo']) ?>">

    <label>Descrição</label>
    <textarea name="descricao" rows="4"><?= htmlspecialchars($val['descricao']) ?></textarea>

    <label>Data limite</label>
    <input type="date" name="data_limite"
           min="<?= htmlspecialchars($plano['data_inicio']) ?>"
           max="<?= htmlspecialchars($plano['data_fim']) ?>"
           value="<?= htmlspecialchars($val['data_limite']) ?>">

    <label>Tipo de responsável *</label>
    <select name="responsavel_tipo" id="responsavel_tipo">
        <option value="">— Selecionar —</option>
        <option value="funcionario">Usuário (Funcionário)</option>
        <option value="setor">Setor</option>
        <option value="loja">Loja</option>
    </select>

    <label>Responsável *</label>
    <select name="responsavel_id" id="responsavel_id">
        <option value="">Selecione</option>
    </select>

    <button class="btn-primary" id="btnSubmit">Criar Tarefa</button>
</form>

</div>

<script>
function carregarModelo() {
    const id = document.getElementById('modelo_id').value;
    if (!id) return;

    fetch('ajax_modelo_tarefa.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data) {
                document.querySelector('input[name="titulo"]').value = data.titulo;
                document.querySelector('textarea[name="descricao"]').value = data.descricao;
            }
        });
}

(function(){
    const tipoEl = document.getElementById('responsavel_tipo');
    const sel = document.getElementById('responsavel_id');
    const btn = document.getElementById('btnSubmit');

    function carregarResponsaveis(tipo) {
        sel.innerHTML = '<option>Carregando...</option>';

        fetch('ajax_responsaveis.php?tipo=' + encodeURIComponent(tipo))
            .then(r => r.json())
            .then(data => {
                sel.innerHTML = '<option value="">Selecione</option>';
                data.forEach(i => {
                    const opt = document.createElement('option');
                    opt.value = i.id;
                    opt.textContent = i.nome;
                    sel.appendChild(opt);
                });
            });
    }

    tipoEl.addEventListener('change', function () {
        if (!this.value) {
            sel.innerHTML = '<option value="">Selecione</option>';
            return;
        }
        carregarResponsaveis(this.value);
    });

})();
</script>

</body>
</html>
