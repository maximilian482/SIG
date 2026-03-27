<?php
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$dispositivos = [];

if ($lojaId > 0) {
    $stmtDisp = $conn->prepare("
        SELECT id, nome, localizacao, descricao
        FROM lojas_dispositivos
        WHERE loja_id = ?
        ORDER BY nome ASC
    ");
    $stmtDisp->bind_param("i", $lojaId);
    $stmtDisp->execute();
    $dispositivos = $stmtDisp->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="secao">

    <div class="titulo-com-editar">
        <h3>📦 Dispositivos da Loja</h3>
        <?php if ($lojaId > 0): ?>
            <button class="btn-adicionar" onclick="abrirModalDispositivo()">➕ Adicionar</button>
        <?php endif; ?>
    </div>

    <?php if ($lojaId <= 0): ?>

        <p>Nenhuma loja selecionada.</p>

    <?php elseif (empty($dispositivos)): ?>

        <p>Nenhum dispositivo cadastrado.</p>

    <?php else: ?>

        <div class="lista-dispositivos">
            <?php foreach ($dispositivos as $d): ?>
                <div class="dispositivo-card">

                    <h4><?= htmlspecialchars($d['nome']) ?></h4>

                    <p><strong>Localização:</strong> <?= htmlspecialchars($d['localizacao']) ?></p>

                    <?php if (!empty($d['descricao'])): ?>
                        <p><?= nl2br(htmlspecialchars($d['descricao'])) ?></p>
                    <?php endif; ?>

                    <div class="dispositivo-acoes">
                        <button class="btn-editar" onclick="editarDispositivo(<?= $d['id'] ?>)">✏️ Editar</button>
                        <button class="btn-excluir" onclick="excluirDispositivo(<?= $d['id'] ?>, <?= $lojaId ?>)">🗑️ Excluir</button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>


<!-- ============================================
     🔥 MODAL EMBUTIDO — AGORA SEMPRE CORRETO
=============================================== -->
<div id="modalDispositivo" class="plano-modal hidden">
    <div class="plano-modal-conteudo">

        <button type="button" class="plano-modal-close modal-fechar-x">✖</button>

        <h3 id="tituloModalDispositivo">➕ Adicionar Dispositivo</h3>

        <form method="post" action="loja_dispositivo_salvar.php">

            <!-- Agora SEMPRE recebe o ID correto -->
            <input type="hidden" name="loja_id" id="form-loja-id" value="<?= $lojaId ?>">

            <input type="hidden" name="id" id="idDispositivo">

            <label>Nome:</label>
            <input type="text" name="nome" id="nomeDispositivo" required>

            <label>Localização:</label>
            <input type="text" name="localizacao" id="localizacaoDispositivo" required>

            <label>Descrição:</label>
            <textarea name="descricao" id="descricaoDispositivo" rows="4"></textarea>

            <div class="modal-botoes">
                <button class="btn-salvar">Salvar</button>
                <button type="button" class="btn-cancelar plano-modal-close">Cancelar</button>
            </div>

        </form>

    </div>
</div>


<script>
function abrirModalDispositivo() {
    document.getElementById('tituloModalDispositivo').innerText = "➕ Adicionar Dispositivo";

    document.getElementById('idDispositivo').value = "";
    document.getElementById('nomeDispositivo').value = "";
    document.getElementById('localizacaoDispositivo').value = "";
    document.getElementById('descricaoDispositivo').value = "";

    // Sempre força o ID correto
    document.getElementById('form-loja-id').value = <?= $lojaId ?>;

    document.getElementById('modalDispositivo').classList.remove('hidden');
}

function editarDispositivo(id) {
    fetch("loja_dispositivo_get.php?id=" + id)
        .then(r => r.json())
        .then(d => {
            document.getElementById('tituloModalDispositivo').innerText = "✏️ Editar Dispositivo";

            document.getElementById('idDispositivo').value = d.id;
            document.getElementById('nomeDispositivo').value = d.nome;
            document.getElementById('localizacaoDispositivo').value = d.localizacao;
            document.getElementById('descricaoDispositivo').value = d.descricao;

            // Sempre força o ID correto
            document.getElementById('form-loja-id').value = <?= $lojaId ?>;

            document.getElementById('modalDispositivo').classList.remove('hidden');
        });
}
</script>
