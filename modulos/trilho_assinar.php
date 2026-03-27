<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

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
const canvas = document.getElementById("canvasAssinatura");
const ctx = canvas.getContext("2d");

// Ajusta o canvas para ocupar a largura total
function ajustarCanvas() {
    const container = document.querySelector(".canvas-container");
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
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

canvas.addEventListener("mousedown", e => {
    desenhando = true;
    const p = pos(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
});

canvas.addEventListener("mousemove", e => {
    if (!desenhando) return;
    const p = pos(e);
    ctx.lineWidth = 3;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
});

canvas.addEventListener("mouseup", () => desenhando = false);
canvas.addEventListener("mouseleave", () => desenhando = false);

// TOUCH
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
    ctx.lineWidth = 3;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
});

canvas.addEventListener("touchend", () => desenhando = false);

// Limpar assinatura
document.getElementById("limpar").addEventListener("click", () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
});

// Mostrar campo de observação
document.getElementById("btnAddObs").addEventListener("click", () => {
    document.querySelector(".label-observacoes").style.display = "block";
    document.querySelector(".input-observacoes").style.display = "block";
    document.getElementById("btnAddObs").style.display = "none";
});

// Verifica se o canvas está vazio
function canvasVazio() {
    const pixelBuffer = new Uint32Array(
        ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer
    );
    return !pixelBuffer.some(color => color !== 0);
}

// Impede enviar sem assinar
document.getElementById("formAssinatura").addEventListener("submit", (e) => {
    if (canvasVazio()) {
        e.preventDefault();
        alert("Por favor, faça a assinatura antes de confirmar.");
        return;
    }

    document.getElementById("assinatura_base64").value = canvas.toDataURL("image/png");
});
</script>

</body>
</html>
