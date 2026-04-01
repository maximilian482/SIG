<?php if ($flash = getFlash()): ?>

<?php
    // Converte tipo do flash → tipo do sistema premium
    $mapa = [
        'success' => 'sucesso',
        'error'   => 'erro',
        'warning' => 'aviso',
        'info'    => 'info'
    ];

    $tipo = $mapa[$flash['tipo']] ?? 'info';
    $msg  = addslashes($flash['mensagem']);
?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    mostrarMensagem("<?= $msg ?>", "<?= $tipo ?>");
});
</script>

<?php endif; ?>
