<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$cpf = $_SESSION['cpf'] ?? '';
if (!$cpf) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada']);
    exit;
}

// Garantir usuário logado
$stmt = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if (!$usuario) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado']);
    exit;
}

// Ler JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$pagina      = max(1, intval($input['pagina'] ?? 1));
$solicitante = trim($input['solicitante'] ?? '');
$status      = trim($input['status'] ?? 'todos');

$porPagina = 10;
$offset    = ($pagina - 1) * $porPagina;

// Montar filtros
$where  = [];
$params = [];
$types  = '';

if ($solicitante !== '') {
    $where[]   = "f.nome LIKE ?";
    $params[]  = "%{$solicitante}%";
    $types    .= "s";
}

if ($status !== '' && $status !== 'todos') {
    $where[]   = "ce.status = ?";
    $params[]  = $status;
    $types    .= "s";
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

// Contar total
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM compras_externas ce
    JOIN lojas l ON l.id = ce.loja_id
    JOIN funcionarios f ON f.id = ce.solicitante_id
    $whereSql
";

$stmt = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$paginas = max(1, ceil($total / $porPagina));

// Buscar dados
$sqlDados = "
    SELECT ce.id, ce.status, ce.produto, l.nome AS loja_nome, f.nome AS solicitante_nome
    FROM compras_externas ce
    JOIN lojas l ON l.id = ce.loja_id
    JOIN funcionarios f ON f.id = ce.solicitante_id
    $whereSql
    ORDER BY ce.id DESC
    LIMIT ? OFFSET ?
";

$paramsDados = $params;
$typesDados  = $types . "ii";
$paramsDados[] = $porPagina;
$paramsDados[] = $offset;

$stmt = $conn->prepare($sqlDados);
$stmt->bind_param($typesDados, ...$paramsDados);
$stmt->execute();
$res = $stmt->get_result();

$dados = [];
while ($c = $res->fetch_assoc()) {

    // ===============================
    // 🔥 AQUI ESTÁ A ALTERAÇÃO PEDIDA
    // ===============================
    $partes = explode(" ", trim($c['solicitante_nome']));
    $nomeCurto = $partes[0];
    // ===============================

    $dados[] = [
        'id'          => $c['id'],
        'loja'        => $c['loja_nome'],
        'produto'     => $c['produto'],
        'solicitante' => $nomeCurto, // ← agora só o primeiro nome
        'status'      => $c['status'],
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
