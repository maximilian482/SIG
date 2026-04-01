<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VALIDA LOGIN
// ===============================
if (!isset($_SESSION['cpf'])) {
    echo "Acesso negado.";
    exit;
}

$cpf = $_SESSION['cpf'];

// ===============================
// VALIDA PERMISSÃO TRILHO
// ===============================
if (!temAcesso($conn, $cpf, 'trilho_motoboy')) {
    echo "Acesso negado.";
    exit;
}

// ===============================
// VALIDA ID
// ===============================
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "ID inválido";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

<link rel="stylesheet" href="/css/trilho_assinar.css">

<title>Assinatura - Trilho</title>
</head>
<body>

<div class="assinatura-wrapper">

    <a href="trilho_motoboy.php" class="btn-voltar-assinatura">⬅ Cancelar / Voltar</a>

    <h2 class="titulo-assinatura">📦 Confirmar Entrega</h2>

    <form id="formAssinatura" action="trilho_salvar_assinatura.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="assinatura_base64" id="assinatura_base64">

        <label class="label-nome">Nome de quem recebeu:</label>
        <input type="text" name="assinatura_nome" class="input-nome" required>

        <!-- Botão para mostrar observação -->
        <button type="button" id="btnAddObs" class="btn-add-obs">+ Adicionar observação</button>

        <!-- Campo de observação escondido inicialmente -->
        <label class="label-observacoes" style="display:none;">Observações:</label>
        <textarea name="observacoes" class="input-observacoes" style="display:none;" placeholder="Ex: Recebido sem avarias"></textarea>

        <label class="label-assinatura">Assinatura:</label>

        <div class="canvas-container">
            <canvas id="canvasAssinatura"></canvas>
        </div>

        <div class="botoes">
            <button type="button" id="limpar" class="btn-limpar">Limpar</button>
            <button type="submit" class="btn-confirmar">Confirmar Entrega</button>
        </div>
    </form>

</div>

<script>
// ===============================
// CONFIGURAÇÃO DO CANVAS
// ===============================
const canvas = document.getElementById("canvasAssinatura");
const ctx = canvas.getContext("2d");

function ajustarCanvas() {
    const container = document.querySelector(".canvas-container");
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
    ctx.lineWidth = 3;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
}
ajustarCanvas();
window.addEventListener("resize", ajustarCanvas);

let desenhando = false;

function pos(e) {
    const rect = canvas.getBoundingClientRect();
    return {
        x: (e.touches ? e.touches[0].clientX : e.clientX) - rect.left,
        y: (e.touches ? e.touches[0].clientY : e.clientY) - rect.top
    };
}

// DESENHO MOUSE
canvas.addEventListener("mousedown", e => {
    desenhando = true;
    const p = pos(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
});

canvas.addEventListener("mousemove", e => {
    if (!desenhando) return;
    const p = pos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
});

canvas.addEventListener("mouseup", () => desenhando = false);
canvas.addEventListener("mouseleave", () => desenhando = false);

// DESENHO TOUCH
canvas.addEventListener("touchstart", e => {
    e.preventDefault();
    desenhando = true;
    const p = pos(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
});

canvas.addEventListener("touchmove", e => {
    e.preventDefault();
    if (!desenhando) return;
    const p = pos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
});

canvas.addEventListener("touchend", () => desenhando = false);

// ===============================
// LIMPAR ASSINATURA
// ===============================
document.getElementById("limpar").addEventListener("click", () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
});

// ===============================
// MOSTRAR CAMPO DE OBSERVAÇÃO
// ===============================
document.getElementById("btnAddObs").addEventListener("click", () => {
    document.querySelector(".label-observacoes").style.display = "block";
    document.querySelector(".input-observacoes").style.display = "block";
    document.getElementById("btnAddObs").style.display = "none";
});

// ===============================
// VERIFICA SE O CANVAS ESTÁ VAZIO
// ===============================
function canvasVazio() {
    const pixelBuffer = new Uint32Array(
        ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer
    );
    return !pixelBuffer.some(color => color !== 0);
}

// ===============================
// ENVIO DO FORMULÁRIO
// ===============================
document.getElementById("formAssinatura").addEventListener("submit", (e) => {

    if (canvasVazio()) {
        e.preventDefault();
        mostrarMensagem("Por favor, faça a assinatura antes de confirmar.", "aviso");
        return;
    }

    const nome = document.querySelector(".input-nome").value.trim();
    if (nome.length < 3) {
        e.preventDefault();
        mostrarMensagem("Digite o nome de quem recebeu.", "aviso");
        return;
    }

    document.getElementById("assinatura_base64").value = canvas.toDataURL("image/png");

    mostrarMensagem("Registrando entrega...", "sucesso");
});
</script>

   <!-- SISTEMA GLOBAL DE MENSAGENS -->
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


<script>
function mostrarMensagem(msg, tipo = "sucesso") {
    const overlay = document.getElementById("overlayMensagem");
    const box = document.getElementById("mensagemTopo");
    const texto = document.getElementById("textoMensagem");
    const icone = document.getElementById("iconeMensagem");

    const icones = {
        sucesso: "✔️",
        erro: "❌",
        aviso: "⚠️",
        info: "ℹ️"
    };

    icone.innerText = icones[tipo] || "ℹ️";
    texto.innerHTML = msg;

    // Cores iguais ao layout.php
    if (tipo === "sucesso") {
        box.style.background = "var(--verde-palmeiras-claro)";
        box.style.color = "white";
    } 
    else if (tipo === "erro") {
        box.style.background = "var(--erro-bg)";
        box.style.color = "var(--erro-texto)";
    } 
    else if (tipo === "aviso") {
        box.style.background = "var(--warning-bg)";
        box.style.color = "var(--warning-texto)";
    }
    else {
        box.style.background = "var(--branco)";
        box.style.color = "var(--texto-principal)";
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




</body>
</html>
