<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Tarefa inválida.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Buscar tarefa original
$sql = "SELECT * FROM tarefas_plano WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$tarefa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tarefa) {
    setFlash('error', 'Tarefa não encontrada.');
    header("Location: planos_acao_listar.php");
    exit;
}

ob_start();
?>

<div class="container">
    <h1>Clonar Tarefa</h1>
    <p class="small">Edite as informações antes de concluir a clonagem.</p>

    <form method="POST" action="planos_acao_tarefa_clonar_salvar.php" class="form-padrao">

        <input type="hidden" name="id_plano" value="<?= $tarefa['id_plano'] ?>">

        <label>Título da nova tarefa:</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($tarefa['titulo']) ?> (Cópia)" required>

        <label>Descrição:</label>
        <textarea name="descricao" required><?= htmlspecialchars($tarefa['descricao']) ?></textarea>

        <label>Tipo de responsável:</label>
        <select id="responsavel_tipo" name="responsavel_tipo" required>
            <option value="funcionario" <?= $tarefa['responsavel_tipo']=='funcionario'?'selected':'' ?>>Funcionário</option>
            <option value="setor" <?= $tarefa['responsavel_tipo']=='setor'?'selected':'' ?>>Setor</option>
            <option value="loja" <?= $tarefa['responsavel_tipo']=='loja'?'selected':'' ?>>Loja</option>
        </select>

        <label>Responsável:</label>
        <select id="responsavel_id" name="responsavel_id" required></select>

        <label>Nova data limite:</label>
        <input type="date" name="data_limite" value="<?= $tarefa['data_limite'] ?>" required>

        <div class="botoes-form">
            <button type="submit" class="btn">Salvar</button>
            <a href="planos_acao_detalhes.php?id=<?= $tarefa['id_plano'] ?>" class="btn-secondary">Cancelar</a>
        </div>

    </form>

</div>

<?php
$conteudo = ob_get_clean();
$cssExtra = "/css/planos_acao_listar.css"; 
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
