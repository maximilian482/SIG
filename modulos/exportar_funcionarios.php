<?php
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=funcionarios_export.csv');

$output = fopen('php://output', 'w');

// Cabeçalho atualizado com telefone e endereço
fputcsv($output, [
    'codigo', 'nome', 'cpf', 'cargo_id', 'loja_id', 'email', 'telefone', 'endereco', 'contratacao', 'nascimento'
]);

// Buscar todos os funcionários
$sql = "SELECT codigo, nome, cpf, cargo_id, loja_id, email, telefone, endereco, contratacao, nascimento 
        FROM funcionarios 
        ORDER BY id";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['codigo'],
        $row['nome'],
        $row['cpf'],
        $row['cargo_id'],
        $row['loja_id'],
        $row['email'],
        $row['telefone'],
        $row['endereco'],
        $row['contratacao'],
        $row['nascimento']
    ]);
}

fclose($output);
exit;
?>
