<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);

$sql = "SELECT titulo, prazo, avaliacao_comentario 
        FROM tarefas_plano 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$t) {
    echo "<p>Tarefa não encontrada.</p>";
    exit;
}

/* ============================================================
   CORREÇÃO: Tratamento seguro para datas inválidas
   ============================================================ */

$valorPrazo = trim($t['prazo'] ?? '');

if (!empty($valorPrazo) && strlen($valorPrazo) >= 8 && strtotime($valorPrazo) !== false) {

    $hoje = new DateTime();
    $dataPrazo = new DateTime($valorPrazo);
    $diff = $hoje->diff($dataPrazo);

    if ($dataPrazo < $hoje) {
        $prazoTexto = "Atrasado há " . $diff->days . " dia(s)";
    } elseif ($diff->days === 0) {
        $prazoTexto = "Vence hoje";
    } else {
        $prazoTexto = $diff->days . " dia(s) restante(s)";
    }

    $prazoFormatado = date('d/m/Y', strtotime($valorPrazo));

} else {
    // Prazo inválido ou vazio
    $prazoTexto = "—";
    $prazoFormatado = "—";
}
?>

<h2 style="color:#b30000; display:flex; align-items:center; gap:8px;">
    ⚠️ REABERTO
</h2>

<p><strong>Título:</strong><br>
<?= htmlspecialchars($t['titulo']) ?>
</p>

<p><strong>Motivo:</strong><br>
<?= nl2br(htmlspecialchars($t['avaliacao_comentario'] ?? '—')) ?>
</p>

<!--
<p><strong>Prazo:</strong><br>
<?= $prazoFormatado ?>  
<br>
<small style="color:#555; font-weight:bold;">
    <?= $prazoTexto ?>
</small>
</p>
-->
