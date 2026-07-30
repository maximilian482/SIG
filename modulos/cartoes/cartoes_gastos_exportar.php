<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

/* ============================================================
   RECEBE FILTROS
   ============================================================ */

$filtro_cartao = $_GET['cartao'] ?? '';
$filtro_func   = $_GET['func'] ?? '';
$filtro_data   = $_GET['data'] ?? '';
$filtro_mes    = $_GET['mes'] ?? '';
$filtro_ano    = $_GET['ano'] ?? '';
$filtro_ciclo  = $_GET['ciclo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

/* ============================================================
   MONTA QUERY
   ============================================================ */

$sql = "
    SELECT 
        g.id,
        g.codigo_cartao,
        g.nome_funcionario,
        s.nome AS setor_nome,
        g.data_compra,
        g.competencia_mes,
        g.competencia_ano,
        g.descricao,
        g.parcelas,
        g.valor_parcela,
        g.finalidade,
        g.centro_custo,
        g.tipo_lancamento,
        g.nota_fiscal,
        g.lancado_vetor,
        g.id_ciclo
    FROM cartoes_gastos g
    LEFT JOIN setores s ON s.id = g.id_setor
    WHERE 1
";

if ($filtro_cartao) $sql .= " AND g.codigo_cartao = '$filtro_cartao' ";
if ($filtro_func)   $sql .= " AND g.nome_funcionario LIKE '%$filtro_func%' ";
if ($filtro_data)   $sql .= " AND g.data_compra = '$filtro_data' ";
if ($filtro_mes)    $sql .= " AND g.competencia_mes = " . intval($filtro_mes);
if ($filtro_ano)    $sql .= " AND g.competencia_ano = " . intval($filtro_ano);
if ($filtro_ciclo)  $sql .= " AND g.id_ciclo = " . intval($filtro_ciclo);

if ($filtro_status === "conferido") {
    $sql .= " AND (
        g.finalidade IS NOT NULL AND 
        g.centro_custo IS NOT NULL AND 
        g.tipo_lancamento IS NOT NULL AND 
        g.nota_fiscal IS NOT NULL AND
        g.lancado_vetor IS NOT NULL
    ) ";
}

if ($filtro_status === "pendente") {
    $sql .= " AND (
        g.finalidade IS NULL OR 
        g.centro_custo IS NULL OR 
        g.tipo_lancamento IS NULL OR 
        g.nota_fiscal IS NULL OR
        g.lancado_vetor IS NULL
    ) ";
}

$sql .= " ORDER BY g.data_compra DESC";

$result = $conn->query($sql);

/* ============================================================
   PREPARA ARQUIVO EXCEL (CSV)
   ============================================================ */

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=gastos_filtrados.csv");

$saida = fopen("php://output", "w");

// Cabeçalho
fputcsv($saida, [
    "Ciclo",
    "Data da Compra",
    "Competência",
    "Cartão",
    "Funcionário",
    "Setor",
    "Descrição",
    "Parcela",
    "Valor Parcela",
    "Finalidade",
    "Centro de Custo",
    "Tipo de Lançamento",
    "Nota Fiscal",
    "Lançado no Vetor",
    "Status"
]);

$meses = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
    7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];

/* ============================================================
   LINHAS
   ============================================================ */

while ($g = $result->fetch_assoc()) {

    $competencia = $meses[$g['competencia_mes']] . "/" . $g['competencia_ano'];

    $conferido = (
        $g['finalidade'] &&
        $g['centro_custo'] &&
        $g['tipo_lancamento'] &&
        $g['nota_fiscal'] &&
        $g['lancado_vetor']
    );

    $status = $conferido ? "Conferido" : "Pendente";

    fputcsv($saida, [
        $g['id_ciclo'],
        date('d/m/Y', strtotime($g['data_compra'])),
        $competencia,
        $g['codigo_cartao'],
        $g['nome_funcionario'],
        $g['setor_nome'],
        $g['descricao'],
        $g['parcelas'],
        number_format($g['valor_parcela'], 2, ',', '.'),
        $g['finalidade'],
        $g['centro_custo'],
        $g['tipo_lancamento'],
        $g['nota_fiscal'],
        $g['lancado_vetor'],
        $status
    ]);
}

fclose($saida);
exit;
