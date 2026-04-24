<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$loja = isset($_GET['loja']) ? intval($_GET['loja']) : 0;

// Base: itens que devem sair da loja (abertos / faturado)
$where = " WHERE ct.status IN ('aberto','faturado') ";
if ($loja) $where .= " AND ct.loja_origem_id = $loja ";

// Opcional: limitar por loja do usuário se necessário
// $lojaUsuario = $_SESSION['loja'] ?? 0;
// if (!temAcesso($conn, $_SESSION['cpf'], 'trilho_adm')) {
//     $where .= " AND (ct.loja_origem_id = $lojaUsuario OR ct.loja_destino_id = $lojaUsuario) ";
// }

$sql = "
SELECT ct.*, lo.nome AS origem_nome, ld.nome AS destino_nome
FROM chamados_trilho ct
LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
{$where}
ORDER BY ct.id DESC
LIMIT 200
";

$res = $conn->query($sql);

if (!$res) {
    echo "<p style='color:red;'>Erro ao consultar.</p>";
    exit;
}

if ($res->num_rows == 0) {
    echo "<p>Nenhum protocolo encontrado.</p>";
    exit;
}

while ($c = $res->fetch_assoc()) {
    // Exemplo simples de card
    echo "<div class='card-trilho'>";
    echo "<div class='card-header'><strong>{$c['protocolo']}</strong> <span class='tag-status'>{$c['status']}</span></div>";
    echo "<div class='card-produto'>" . htmlspecialchars($c['descricao']) . "</div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Saída:</strong> " . htmlspecialchars($c['origem_nome']) . "</p>";
    echo "<p><strong>Destino:</strong> " . htmlspecialchars($c['destino_nome']) . "</p>";
    echo "</div>";
    echo "<div class='card-actions'><button class='btn-detalhes' data-id='{$c['id']}'>Detalhes</button></div>";
    echo "</div>";
}
