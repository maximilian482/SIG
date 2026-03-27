<?php
$stmtDisp = $conn->prepare("
    SELECT id, nome, localizacao, descricao
    FROM lojas_dispositivos
    WHERE loja_id = ?
    ORDER BY nome ASC
");
$stmtDisp->bind_param("i", $lojaId);
$stmtDisp->execute();
$dispositivos = $stmtDisp->get_result()->fetch_all(MYSQLI_ASSOC);


?>

<div class="secao">

    <div class="titulo-com-editar">
        <h3>📦 Dispositivos da Loja</h3>
        <button class="btn-adicionar" onclick="abrirModalDispositivo()">➕ Adicionar</button>
    </div>

    <?php if (empty($dispositivos)): ?>
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
                    <button class="btn-excluir" onclick="excluirDispositivo(<?= $d['id'] ?>)">🗑️ Excluir</button>
                </div>

            </div>
        <?php endforeach; ?>

        </div>
    <?php endif; ?>

</div>
