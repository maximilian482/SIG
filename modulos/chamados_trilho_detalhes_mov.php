<?php
include_once("conexao.php");
include_once("funcoes.php");
session_start();

$id = intval($_GET['id']);

// Buscar protocolo
$sql = "SELECT t.*, u.nome AS motoboy_nome
        FROM trilho t
        LEFT JOIN usuarios u ON u.id = t.motoboy_id
        WHERE t.id = $id";

$result = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($result);

if (!$dados) {
    echo "Protocolo não encontrado.";
    exit;
}

// Buscar itens
$sqlItens = "SELECT * FROM trilho_itens WHERE trilho_id = $id ORDER BY id ASC";
$itens = mysqli_query($conn, $sqlItens);

// TAG por tipo
function tagTipo($tipo) {
    switch ($tipo) {
        case "remanejamento":
            return '<span class="tag-tipo tipo-remanejamento">🔄 Remanejamento</span>';
        case "malote":
            return '<span class="tag-tipo tipo-malote">📨 Malote</span>';
        case "item":
            return '<span class="tag-tipo tipo-item">📌 Item Diverso</span>';
    }
}

$tag = tagTipo($dados['tipo']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detalhes do Protocolo</title>

    <link rel="stylesheet" href="css/chamados_trilho.css">
    <script src="js/chamados_trilho.js"></script>
</head>

<body>

<div class="card-trilho">

    <?= $tag ?>

    <h2 class="card-titulo">Protocolo #<?= $dados['id'] ?></h2>

    <div class="card-body">

        <p><strong>Origem:</strong> <?= $dados['origem'] ?></p>
        <p><strong>Destino:</strong> <?= $dados['destino'] ?></p>
        <p><strong>Aos cuidados de:</strong> <?= $dados['cuidados'] ?></p>

        <hr>

        <h3>Itens</h3>

        <?php
        if (mysqli_num_rows($itens) == 0) {
            echo "<p>Nenhum item cadastrado.</p>";
        } else {
            echo "<ul>";
            while ($i = mysqli_fetch_assoc($itens)) {
                echo "<li><strong>{$i['descricao']}</strong> — {$i['quantidade']} unidade(s)</li>";
            }
            echo "</ul>";
        }
        ?>

        <hr>

        <h3>Informações do Processo</h3>

        <p><strong>Status:</strong> <?= ucfirst($dados['status']) ?></p>

        <?php if ($dados['data_abertura']) { ?>
            <p><strong>Abertura:</strong> <?= date("d/m/Y H:i", strtotime($dados['data_abertura'])) ?></p>
        <?php } ?>

        <?php if ($dados['data_coleta']) { ?>
            <p><strong>Coleta:</strong> <?= date("d/m/Y H:i", strtotime($dados['data_coleta'])) ?></p>
        <?php } ?>

        <?php if ($dados['motoboy_nome']) { ?>
            <p><strong>Motoboy:</strong> <?= $dados['motoboy_nome'] ?></p>
        <?php } ?>

        <?php if ($dados['data_entrega']) { ?>
            <p><strong>Entrega:</strong> <?= date("d/m/Y H:i", strtotime($dados['data_entrega'])) ?></p>
        <?php } ?>

    </div>

    <div class="card-actions">

        <button onclick="history.back()" class="btn-trilho btn-detalhes">
            Voltar
        </button>

        <?php if ($dados['status'] == "aberto") { ?>
            <button class="btn-trilho btn-coletar" data-id="<?= $dados['id'] ?>" data-tipo="<?= $dados['tipo'] ?>">
                Coletar
            </button>
        <?php } ?>

        <?php if ($dados['status'] == "em_rota") { ?>
            <button class="btn-trilho btn-finalizar" data-id="<?= $dados['id'] ?>" data-tipo="<?= $dados['tipo'] ?>">
                Finalizar
            </button>
        <?php } ?>

    </div>

</div>

</body>
</html>
