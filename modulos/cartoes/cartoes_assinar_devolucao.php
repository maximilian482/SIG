<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// ID da atribuição
$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição inválida.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Busca dados da atribuição + ciclo
$stmt = $conn->prepare("
    SELECT a.*, 
           f.nome AS funcionario_nome, 
           f.id_setor,
           c.banco,
           c.codigo_cartao,
           c.saldo_atual,
           cc.id_ciclo
    FROM cartoes_atribuicoes a
    JOIN funcionarios f ON f.cpf = a.cpf_funcionario
    JOIN cartoes c ON c.codigo_cartao = a.codigo_cartao
    LEFT JOIN cartoes_ciclos cc ON cc.id_ciclo = a.id_ciclo
    WHERE a.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$atr = $stmt->get_result()->fetch_assoc();

if (!$atr) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição não encontrada.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Nome reduzido
$nomeCompleto = trim($atr['funcionario_nome']);
$partes = explode(" ", $nomeCompleto);
$nomeReduzido = $partes[0] . " " . end($partes);

// Buscar total de gastos do ciclo (CORRIGIDO)
$stmtG = $conn->prepare("
    SELECT SUM(valor_parcela) AS total_gastos
    FROM cartoes_gastos
    WHERE id_ciclo = ?
");
$stmtG->bind_param("i", $atr['id_ciclo']);
$stmtG->execute();
$totalGastos = floatval($stmtG->get_result()->fetch_assoc()['total_gastos'] ?? 0);

// Saldo inicial da entrega
$saldoInicial = floatval($atr['saldo_entregue']);

// Saldo esperado no banco
$saldoEsperado = $saldoInicial - $totalGastos;

ob_start();
?>

<div class="container py-4" style="max-width: 900px;">

    <h1 class="mb-3">🔄 Coletar Assinaturas de Devolução</h1>
    <p class="text-muted">O gestor deve conferir o saldo no banco antes de coletar as assinaturas.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h4 class="mb-3">📋 Resumo da Devolução</h4>

            <p><strong>Cartão:</strong> <?= $atr['codigo_cartao'] ?> — <?= $atr['banco'] ?></p>
            <p><strong>Funcionário:</strong> <?= $nomeReduzido ?></p>
            <p><strong>Ciclo:</strong> #<?= $atr['id_ciclo'] ?></p>

            <hr>

            <p><strong>Saldo inicial entregue:</strong>  
                R$ <?= number_format($saldoInicial, 2, ',', '.') ?>
            </p>

            <p><strong>Total de gastos declarados:</strong>  
                R$ <?= number_format($totalGastos, 2, ',', '.') ?>
            </p>

            <p><strong>Saldo esperado no banco:</strong>  
                R$ <?= number_format($saldoEsperado, 2, ',', '.') ?>
            </p>

            <div class="mt-3">
                <label class="form-label"><strong>Saldo conferido no banco (GESTOR):</strong></label>
                <input type="number" step="0.01" id="saldo_banco" class="form-control" required>
            </div>

        </div>
    </div>

    <button class="btn btn-primary w-100 mb-3" onclick="abrirAssinatura()">
        ✍ Iniciar Coleta de Assinaturas
    </button>

    <a href="cartoes_mestre.php" class="btn btn-secondary w-100">⬅ Voltar</a>

</div>

<!-- Tela de Assinatura Dupla -->
<div id="assinaturaSlide" style="
    position: fixed; top:0; left:0; width:100%; height:100%;
    background:white; display:none; z-index:9999;
">

    <!-- FUNCIONÁRIO -->
    <div id="slideFuncionario" style="width:100%; height:100%; padding:10px;">
        <h2 class="mb-3 text-center">Assinatura do Funcionário</h2>

        <canvas id="canvasFuncionario" style="width:100vw; height:70vh; touch-action:none;"></canvas>

        <button class="btn btn-warning w-100 mt-3" onclick="limparFuncionario()">🧹 Limpar Assinatura</button>

        <button class="btn btn-primary mt-3 w-100" onclick="mostrarGestor()">Próximo →</button>
    </div>

    <!-- GESTOR -->
    <div id="slideGestor" style="width:100%; height:100%; padding:10px; display:none;">
        <h2 class="mb-3 text-center">Assinatura do Gestor</h2>

        <canvas id="canvasGestor" style="width:100vw; height:70vh; touch-action:none;"></canvas>

        <button class="btn btn-warning w-100 mt-3" onclick="limparGestor()">🧹 Limpar Assinatura</button>

        <button class="btn btn-success mt-3 w-100" onclick="finalizarAssinatura()">Finalizar</button>
    </div>

</div>

<script>
let funcionarioAssinou = false;
let gestorAssinou = false;

function abrirAssinatura(){
    let saldoBanco = document.getElementById("saldo_banco").value;

    if (!saldoBanco) {
        alert("Digite o saldo conferido no banco.");
        return;
    }

    document.getElementById("assinaturaSlide").style.display = "block";
}

function mostrarGestor(){
    if (!funcionarioAssinou) {
        alert("O funcionário precisa assinar antes de continuar.");
        return;
    }
    document.getElementById("slideFuncionario").style.display = "none";
    document.getElementById("slideGestor").style.display = "block";
}

function setupCanvas(canvas){
    let ctx = canvas.getContext("2d");
    let desenhando = false;

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight * 0.70;

    function getPos(e){
        if (e.touches){
            let rect = canvas.getBoundingClientRect();
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        } else {
            return { x: e.offsetX, y: e.offsetY };
        }
    }

    canvas.addEventListener("mousedown", () => {
        desenhando = true;
        if (canvas.id === "canvasFuncionario") funcionarioAssinou = true;
        if (canvas.id === "canvasGestor") gestorAssinou = true;
    });

    canvas.addEventListener("mouseup", () => { desenhando = false; ctx.beginPath(); });

    canvas.addEventListener("mousemove", function(e){
        if(!desenhando) return;
        let pos = getPos(e);
        ctx.lineWidth = 3;
        ctx.lineCap = "round";
        ctx.strokeStyle = "#000";
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    });

    canvas.addEventListener("touchstart", function(e){
        desenhando = true;
        if (canvas.id === "canvasFuncionario") funcionarioAssinou = true;
        if (canvas.id === "canvasGestor") gestorAssinou = true;
        e.preventDefault();
    });

    canvas.addEventListener("touchend", function(e){
        desenhando = false;
        ctx.beginPath();
        e.preventDefault();
    });

    canvas.addEventListener("touchmove", function(e){
        if(!desenhando) return;
        let pos = getPos(e);
        ctx.lineWidth = 3;
        ctx.lineCap = "round";
        ctx.strokeStyle = "#000";
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
    });
}

setupCanvas(document.getElementById("canvasFuncionario"));
setupCanvas(document.getElementById("canvasGestor"));

function limparFuncionario(){
    let ctx = canvasFuncionario.getContext("2d");
    ctx.clearRect(0, 0, canvasFuncionario.width, canvasFuncionario.height);
    funcionarioAssinou = false;
}

function limparGestor(){
    let ctx = canvasGestor.getContext("2d");
    ctx.clearRect(0, 0, canvasGestor.width, canvasGestor.height);
    gestorAssinou = false;
}

function finalizarAssinatura(){

    if (!funcionarioAssinou) {
        alert("O funcionário precisa assinar antes de continuar.");
        return;
    }

    if (!gestorAssinou) {
        alert("O gestor precisa assinar antes de finalizar.");
        return;
    }

    let funcionarioAss = canvasFuncionario.toDataURL();
    let gestorAss = canvasGestor.toDataURL();

    let saldoBanco = parseFloat(document.getElementById("saldo_banco").value);

    let saldoInicial = <?= $saldoInicial ?>;
    let totalGastos  = <?= $totalGastos ?>;
    let saldoEsperado = <?= $saldoEsperado ?>;

    let divergencia = saldoEsperado - saldoBanco;

    let data = new FormData();
    data.append("id", <?= $id ?>);
    data.append("id_ciclo", <?= $atr['id_ciclo'] ?>);
    data.append("saldo_banco", saldoBanco);
    data.append("assinatura_funcionario", funcionarioAss);
    data.append("assinatura_gestor", gestorAss);
    data.append("divergencia", divergencia);

    data.append("cpf_gestor", "<?= $_SESSION['cpf'] ?>");

    fetch("cartoes_assinar_devolucao_salvar.php", {
        method: "POST",
        body: data
    }).then(() => {
        window.location.href = "cartoes_ver_assinaturas_devolucao.php?id=<?= $id ?>";
    });
}
</script>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
