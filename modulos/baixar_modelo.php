<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=modelo_funcionarios.csv');

// Abre saída
$output = fopen('php://output', 'w');

// Cabeçalho atualizado com email
fputcsv($output, [
    'codigo', 'nome', 'cpf', 'cargo_id', 'loja_id', 'email', 'contratacao', 'nascimento'
]);

// Exemplo de linha preenchida
fputcsv($output, [
    '12458', 'João da Silva', '12345678901', '1', '2', 'joao@empresa.com', '2020-05-10', '1990-03-15'
]);

fclose($output);
exit;
?>
