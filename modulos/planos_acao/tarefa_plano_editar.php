<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Parâmetros inválidos.");

// Buscar tarefa
$sqlT = "SELECT * FROM tarefas_plano WHERE id = ? LIMIT 1";
$stmtT = $conn->prepare($sqlT);
$stmtT->bind_param("i", $id);
$stmtT->execute();
$tarefa = $stmtT->get_result()->fetch_assoc();
$stmtT->close();
if (!$tarefa) die("Tarefa não encontrada.");

// Buscar plano
$sqlP = "SELECT titulo, data_inicio, data_fim FROM planos_acao WHERE id = ? LIMIT 1";
$stmtP = $conn->prepare($sqlP);
$stmtP->bind_param("i", $tarefa['id_plano']);
$stmtP->execute();
$plano = $stmtP->get_result()->fetch_assoc();
$stmtP->close();
if (!$plano) die("Plano não encontrado.");

$erro = "";

// PREFILL
$tipo = $tarefa['responsavel_tipo'] ?? '';
if ($tipo === 'usuario') $tipo = 'funcionario';

$val = [
    'titulo' => $tarefa['titulo'] ?? '',
    'descricao' => $tarefa['descricao'] ?? '',
    'data_limite' => $tarefa['data_limite'] ?? ($plano['data_fim'] ?? ''),
    'responsavel_tipo' => $tipo,
    'responsavel_id' => intval($tarefa['responsavel_id'] ?? 0),
];

// SALVAR ALTERAÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $val['titulo'] = trim($_POST['titulo'] ?? '');
    $val['descricao'] = trim($_POST['descricao'] ?? '');
    $val['data_limite'] = trim($_POST['data_limite'] ?? '') ?: null;
    $val['responsavel_tipo'] = trim($_POST['responsavel_tipo'] ?? '');
    $val['responsavel_id'] = intval($_POST['responsavel_id'] ?? 0);

    if ($val['responsavel_tipo'] === 'usuario') {
        $val['responsavel_tipo'] = 'funcionario';
    }

    if ($val['titulo'] === '') {
        $erro = "O título é obrigatório.";
    }

    if (!$erro && empty($val['data_limite'])) {
        $erro = "O prazo é obrigatório.";
    }

    if (!$erro && $val['data_limite']) {
        $dLim = date('Y-m-d', strtotime($val['data_limite']));
        if ($dLim < $plano['data_inicio']) {
            $erro = "O prazo deve ser igual ou posterior à data de início do plano.";
        }
        if ($dLim > $plano['data_fim']) {
            $erro = "O prazo deve ser igual ou anterior à data final do plano.";
        }
    }

    if (!$erro) {

        if (empty($val['responsavel_tipo']) || $val['responsavel_id'] <= 0) {
            $erro = "Selecione um responsável válido.";
        } else {

            $sqlU = "UPDATE tarefas_plano SET
                        titulo = ?,
                        descricao = ?,
                        data_limite = ?,
                        tipo_responsavel = ?,
                        responsavel_tipo = ?,
                        responsavel_id = ?
                     WHERE id = ?";

            $stmtU = $conn->prepare($sqlU);

            $stmtU->bind_param(
                "sssssii",
                $val['titulo'],
                $val['descricao'],
                $val['data_limite'],
                $val['responsavel_tipo'],
                $val['responsavel_tipo'],
                $val['responsavel_id'],
                $id
            );


            if (!$stmtU->execute()) {
                $erro = "Erro ao salvar alterações: " . htmlspecialchars($stmtU->error);
            } else {
                $_SESSION['flash'] = [
                    'mensagem' => 'Tarefa atualizada com sucesso.',
                    'tipo' => 'success'
                ];
                header("Location: planos_acao_detalhes.php?id=" . $tarefa['id_plano']);
                exit;
            }
        }
    }
}

$titulo = "Editar Tarefa";

ob_start();
?>

<div class="container tarefa-editar">

    <h2>Editar Tarefa</h2>

    <?php if ($erro): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" id="formEditar">

        <label>Título *</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($val['titulo']) ?>" required>

        <label>Descrição</label>
        <textarea name="descricao" rows="4"><?= htmlspecialchars($val['descricao']) ?></textarea>

        <label>Prazo *</label>
        <input type="date" name="data_limite"
               value="<?= htmlspecialchars($val['data_limite'] ?? '') ?>"
               min="<?= htmlspecialchars($plano['data_inicio']) ?>"
               max="<?= htmlspecialchars($plano['data_fim']) ?>"
               required>

        <label>Tipo de responsável *</label>
        <select name="responsavel_tipo" id="responsavel_tipo" required>
            <option value="">— Selecionar —</option>
            <option value="funcionario" <?= ($val['responsavel_tipo'] === 'funcionario') ? 'selected' : '' ?>>Funcionário</option>
            <option value="setor" <?= ($val['responsavel_tipo'] === 'setor') ? 'selected' : '' ?>>Setor</option>
            <option value="loja" <?= ($val['responsavel_tipo'] === 'loja') ? 'selected' : '' ?>>Loja</option>
        </select>

        <label>Responsável *</label>
        <select name="responsavel_id" id="responsavel_id" required>
            <option value="">Selecione</option>
        </select>

        <button type="submit" class="btn">Salvar Alterações</button>
        <a href="planos_acao_detalhes.php?id=<?= $tarefa['id_plano'] ?>" class="btn ghost">← Voltar</a>

    </form>

</div>

<?php
$conteudo = ob_get_clean();
$cssExtra = "/css/tarefa_plano_editar.css";
$scripts  = '<script src="/js/tarefa_plano_editar.js"></script>';
$scripts = "
<script>
document.addEventListener('DOMContentLoaded', () => {

    const tipoSelect = document.getElementById('responsavel_tipo');
    const respSelect = document.getElementById('responsavel_id');
    const valorAtual = '" . $tarefa['responsavel_id'] . "';

    function carregarResponsaveis() {
        const tipo = tipoSelect.value;

        fetch('/modulos/planos_acao/ajax_responsaveis.php?tipo=' + tipo)
            .then(r => r.json())
            .then(lista => {
                respSelect.innerHTML = '';

                lista.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.nome;

                    if (item.id == valorAtual) {
                        opt.selected = true;
                    }

                    respSelect.appendChild(opt);
                });
            })
            .catch(err => console.error('Erro ao carregar responsáveis:', err));
    }

    tipoSelect.addEventListener('change', carregarResponsaveis);

    carregarResponsaveis(); // carregar ao abrir a página
});
</script>
";

include ROOT_PATH . "/includes/layout.php";
