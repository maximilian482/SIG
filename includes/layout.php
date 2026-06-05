<?php
// Sessão e conexão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// layout.php — Estrutura base do sistema

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';


// require_once ROOT_PATH . '/dados/conexao.php';
// $conn = conectar();

// Detectar se é AJAX
$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

// Carregar menus
ob_start();
include ROOT_PATH . '/includes/menu.php';
$menuLateral = ob_get_clean();

ob_start();
include ROOT_PATH . '/perfil/menu_perfil.php';
$menuPerfil = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-br">

<?php include ROOT_PATH . '/includes/head.php'; ?>

<body>

<!-- ===============================
     SISTEMA GLOBAL DE MENSAGENS (AGORA NO TOPO)
================================ -->

<?= $menuLateral ?>
<?= $menuPerfil ?>

<main class="layout-principal">
    <?= $conteudo ?>
</main>

<?= $modais ?? '' ?>



<!-- ===============================
     SISTEMA GLOBAL DE MENSAGENS
================================ -->
<div id="overlayMensagem" style="
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.65);
    z-index:2500;
"></div>

<div id="mensagemTopo" style="
    display:none;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    background:var(--branco);
    color:var(--texto-principal);
    padding:25px 35px;
    border-radius:12px;
    box-shadow:0 4px 14px rgba(0,0,0,0.35);
    z-index:3000;
    font-weight:bold;
    font-size:1.3em;
    text-align:center;
    opacity:0;
    transition:opacity 0.4s ease;
    min-width:300px;
">
    <div id="iconeMensagem" style="font-size:2.2em; margin-bottom:10px;"></div>
    <span id="textoMensagem"></span>
</div>

<?php include ROOT_PATH . '/includes/scripts.php'; ?>

<!-- ===============================
     SCRIPT GLOBAL DE MENSAGENS
================================ -->
<script>
function mostrarMensagem(msg, tipo = "sucesso") {
    const overlay = document.getElementById("overlayMensagem");
    const box = document.getElementById("mensagemTopo");
    const texto = document.getElementById("textoMensagem");
    const icone = document.getElementById("iconeMensagem");

    const icones = {
        sucesso: "✔️",
        erro: "❌",
        aviso: "⚠️"
    };

    icone.innerText = icones[tipo] || "ℹ️";
    texto.innerHTML = msg;

    if (tipo === "sucesso") {
        box.style.background = "var(--verde-palmeiras-claro)";
        box.style.color = "white";
    } else if (tipo === "erro") {
        box.style.background = "var(--erro-bg)";
        box.style.color = "var(--erro-texto)";
    } else if (tipo === "aviso") {
        box.style.background = "var(--warning-bg)";
        box.style.color = "var(--warning-texto)";
    }

    overlay.style.display = "block";
    box.style.display = "block";

    setTimeout(() => box.style.opacity = "1", 10);

    setTimeout(() => {
        box.style.opacity = "0";
        setTimeout(() => {
            overlay.style.display = "none";
            box.style.display = "none";
        }, 400);
    }, 5000);
}
</script>

<!-- ===============================
     SCRIPT GLOBAL DO MENU DO PERFIL
================================ -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const btnPerfil = document.getElementById("perfilBtn");
    const menuPerfil = document.getElementById("menuPerfil");

    if (!btnPerfil || !menuPerfil) return;

    btnPerfil.addEventListener("click", (e) => {
        e.stopPropagation();
        menuPerfil.classList.toggle("aberto");
    });

    document.addEventListener("click", (e) => {
        if (!menuPerfil.contains(e.target) && !btnPerfil.contains(e.target)) {
            menuPerfil.classList.remove("aberto");
        }
    });
});
</script>

<!-- ===============================
     DISPARO AUTOMÁTICO DE MENSAGENS
================================ -->
<?php if (!$isAjax && !empty($_SESSION['flash'])): ?>
<script>
    mostrarMensagem(
        "<?= addslashes($_SESSION['flash']['mensagem']) ?>",
        "<?= $_SESSION['flash']['tipo'] === 'success' ? 'sucesso' : ($_SESSION['flash']['tipo'] === 'error' ? 'erro' : 'aviso') ?>"
    );
</script>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>




<!-- SCRIPTS ESPECÍFICOS DA PÁGINA -->
<?= $scripts ?? '' ?>

<!-- SCRIPT GLOBAL DE MODAIS -->
<script src="/js/global.js"></script>

</body>
</html>

