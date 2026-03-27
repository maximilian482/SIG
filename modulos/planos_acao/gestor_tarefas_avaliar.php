<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id_tarefa'] ?? 0);
$id_plano = intval($_GET['id_plano'] ?? 0);

$sql = "SELECT * FROM tarefas_plano WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$tarefa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tarefa) {
    echo "<p>Tarefa não encontrada.</p>";
    exit;
}
?>

<link rel="stylesheet" href="/css/gestor_tarefas_avaliar.css">

<div class="modal-avaliacao-content">

    <button class="modal-avaliacao-close" onclick="fecharModalAvaliacao()">×</button>

    <h2>Avaliar Tarefa</h2>

    <p><strong><?= htmlspecialchars($tarefa['titulo']) ?></strong></p>

    <?php if (!empty($tarefa['descricao'])): ?>
        <p><?= nl2br(htmlspecialchars($tarefa['descricao'])) ?></p>
    <?php endif; ?>

    <form id="formAvaliacao" method="POST" action="/modulos/planos_acao/gestor_tarefas_avaliar_salvar.php">

        <input type="hidden" name="tarefa_id" value="<?= $id ?>">
        <input type="hidden" name="id_plano" value="<?= $id_plano ?>">

        <textarea name="comentario" placeholder="Descreva o motivo (obrigatório ao reabrir)"></textarea>

        <div class="btn-acoes">
            <button type="submit" name="acao" value="aprovar" class="btn-aprovar">Aprovar</button>
            <button type="submit" name="acao" value="reabrir" class="btn-reabrir">Reabrir</button>
            <button type="submit" name="acao" value="excluir" class="btn-excluir">Excluir</button>
        </div>

    </form>

</div>

<script>
window.inicializarAvaliacao = function () {

    const form = document.getElementById("formAvaliacao");
    if (!form) {
        console.log("formAvaliacao NÃO encontrado");
        return;
    }

    console.log("inicializarAvaliacao chamado");

    // Remove listeners antigos
    const clone = form.cloneNode(true);
    form.parentNode.replaceChild(clone, form);

    const newForm = document.getElementById("formAvaliacao");

    // Guarda a última ação clicada
    let ultimaAcao = null;

    newForm.querySelectorAll("button[type='submit'][name='acao']").forEach(btn => {
        btn.addEventListener("click", () => {
            ultimaAcao = btn.value;
            console.log("Botão clicado:", ultimaAcao);
        });
    });

    newForm.addEventListener("submit", function (e) {
        const comentario = newForm.comentario.value.trim();
        const acao = ultimaAcao;

        console.log("SUBMIT disparado. ultimaAcao =", acao, "comentario =", comentario);

        // Se não souber qual ação é, deixa o backend decidir
        if (!acao) {
            console.log("Nenhuma ação detectada, deixando enviar...");
            return;
        }

        if (acao === "reabrir" && comentario.length < 5) {
            console.log("Bloqueando reabertura: comentário insuficiente");
            e.preventDefault();
            mostrarMensagem(
                "Para reabrir a tarefa, descreva o motivo com pelo menos 5 caracteres.",
                "aviso"
            );
            return;
        }

        console.log("Submit permitido");
    });
};
</script>
