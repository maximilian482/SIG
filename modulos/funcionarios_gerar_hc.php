<?php
require_once '../dados/conexao.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conn = conectar();

// ===============================
// BUSCAR FUNCIONÁRIOS
// ===============================
$sql = "
SELECT 
    f.id,
    l.nome AS loja,
    f.codigo,
    f.cpf,
    f.nascimento,
    f.email,
    f.nome,
    f.contratacao,
    c.nome_cargo AS cargo,
    fs.nome AS funcao_secundaria
FROM funcionarios f
LEFT JOIN lojas l ON l.id = f.loja_id
LEFT JOIN cargos c ON c.id = f.cargo_id
LEFT JOIN funcionario_funcoes_secundarias ffs ON ffs.funcionario_id = f.id
LEFT JOIN funcoes_secundarias fs ON fs.id = ffs.funcao_secundaria_id
WHERE f.eh_funcionario = 1
  AND f.desligamento IS NULL
ORDER BY l.nome, f.nome
";


$res = $conn->query($sql);

// ===============================
// CRIAR PLANILHA
// ===============================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("HC");

// Cabeçalhos
$headers = [
    "Loja",
    "Código",
    "CPF",
    "Nascimento",
    "Email",
    "Nome",
    "Admissão",
    "Função",
    "Função Secundária"
];

$col = 1;
foreach ($headers as $h) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($colLetter . '1', $h);

    $col++;
}

// Dados
$linha = 2;
while ($row = $res->fetch_assoc()) {
    $sheet->setCellValue("A{$linha}", $row['loja']);
    $sheet->setCellValue("B{$linha}", $row['codigo']);
    $sheet->setCellValue("C{$linha}", $row['cpf']);
    $sheet->setCellValue("D{$linha}", $row['nascimento']);
    $sheet->setCellValue("E{$linha}", $row['email']);
    $sheet->setCellValue("F{$linha}", $row['nome']);
    $sheet->setCellValue("G{$linha}", $row['contratacao']);
    $sheet->setCellValue("H{$linha}", $row['cargo']);
    $sheet->setCellValue("I{$linha}", $row['funcao_secundaria']);
    $linha++;
}

// ===============================
// DOWNLOAD DO ARQUIVO
// ===============================
$arquivo = "HC_" . date("Y-m-d") . ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$arquivo\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
