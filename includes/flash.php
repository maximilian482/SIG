<?php if ($flash = getFlash()): ?>
    <div class="flash flash-<?= $flash['tipo'] ?>">
        <?= htmlspecialchars($flash['mensagem']) ?>
    </div>
<?php endif; ?>

<style>
.flash {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: bold;
    font-size: 0.95rem;
}
.flash-success { background: #e8ffe8; color: #006437; border-left: 5px solid #00A859; }
.flash-error   { background: #ffe8e8; color: #b30000; border-left: 5px solid #cc0000; }
.flash-warning { background: #fff7e6; color: #b36b00; border-left: 5px solid #ff9900; }
.flash-info    { background: #e6f3ff; color: #005c99; border-left: 5px solid #0099ff; }
</style>
