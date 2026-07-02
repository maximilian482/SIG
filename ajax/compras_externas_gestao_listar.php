<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$conn = conectar();

// Permissão
$cpf = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

$acessoTotal = in_array($cargo, ['super', 'ceo']);

if (!$acessoTotal && !temAcesso($conn, $cpf, "gestao_compras_externas")) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão']);
    exit;
}

// Ler JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$pagina = max(1, intval($input['pagina'] ?? 1));
$status = trim($input['status'] ?? '');
$loja   = trim($input['loja'] ?? '');
$busca  = trim($input['busca'] ?? '');

$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Filtros
$where = [];
$params = [];
$types = '';

if ($status !== '') {
    $where[] = "ce.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($loja !== '') {
    $where[] = "ce.loja_id = ?";
    $params[] = intval($loja);
    $types .= "i";
}

if ($busca !== '') {
    $where[] = "(ce.produto LIKE ? OR f.nome LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $types .= "ss";
}

$whereSql = empty($where) ? "" : "WHERE " . implode(" AND ", $where);

// Contar total
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM compras_externas ce
    JOIN lojas l ON l.id = ce.loja_id
    JOIN funcionarios f ON f.id = ce.solicitante_id
    $whereSql
";

$stmt = $conn->prepare($sqlCount);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$paginas = max(1, ceil($total / $porPagina));

// Buscar dados
$sql = "
    SELECT ce.*, l.nome AS loja_nome, f.nome AS solicitante_nome
    FROM compras_externas ce
    JOIN lojas l ON l.id = ce.loja_id
    JOIN funcionarios f ON f.id = ce.solicitante_id
    $whereSql
    ORDER BY ce.id DESC
    LIMIT ? OFFSET ?
";

$params2 = $params;
$types2 = $types . "ii";
$params2[] = $porPagina;
$params2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();

$dados = [];
while ($c = $res->fetch_assoc()) {

    $primeiroNome = explode(" ", trim($c['solicitante_nome']))[0];

    $dados[] = [
        'id'          => $c['id'],
        'loja'        => $c['loja_nome'],
        'produto'     => $c['produto'],
        'solicitante' => $primeiroNome,
        'status'      => $c['status']
    ];
}

echo json_encode([
    'sucesso' => true,
    'pagina'  => $pagina,
    'paginas' => $paginas,
    'total'   => $total,
    'dados'   => $dados
]);
exit;
