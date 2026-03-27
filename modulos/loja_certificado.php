<?php
// Garantir que $certificado, $alertaCert, $lojaId e $usuarioPodeVerSenha já existem no loja.php
?>

<div class="secao">

    <div class="titulo-com-editar">
        <h3>🔐 Certificado Digital</h3>
        <button class="btn-editar" onclick="abrirModalCertificado()">✏️</button>
    </div>

    <div class="info-linha">
        <span class="info-label">Status:</span>
        <span class="info-valor" style="color: <?= $alertaCert['cor'] ?>;">
            <?= $alertaCert['texto'] ?>
        </span>
    </div>

    <div class="info-linha">
        <span class="info-label">Validade:</span>
        <span class="info-valor">
            <?= $certificado['validade'] ? date('d/m/Y', strtotime($certificado['validade'])) : 'Não informado' ?>
        </span>
    </div>

    <div class="info-linha">
        <span class="info-label">Senha:</span>
        <span class="info-valor senha-wrapper">
            <?php if ($usuarioPodeVerSenha): ?>
                <input type="password" id="senhaCert" value="<?= htmlspecialchars($certificado['senha'] ?? '') ?>" readonly>
                <button type="button" class="btn-olho" onclick="toggleSenhaCert()">👁️</button>
            <?php else: ?>
                <i>Oculta</i>
            <?php endif; ?>
        </span>
    </div>

    <div class="info-linha">
        <span class="info-label">Arquivo atual:</span>
        <span class="info-valor">
            <?php if (!empty($certificado['arquivo'])): ?>
                <a class="btn-download" href="/<?= $certificado['arquivo'] ?>" target="_blank">
                    ⬇️ Baixar certificado
                </a>
            <?php else: ?>
                Não enviado
            <?php endif; ?>
        </span>
    </div>

</div>

