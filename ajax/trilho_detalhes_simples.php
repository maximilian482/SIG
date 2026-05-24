<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

$sql = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome,
        fs.nome AS nome_solicitante,
        fd.nome AS nome_solicitado
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    LEFT JOIN funcionarios fs ON fs.id = ct.solicitante_id
    LEFT JOIN funcionarios fd ON fd.id = ct.solicitado_id
    WHERE ct.id = $id
";

$res = $conn->query($sql);
$dados = $res->fetch_assoc();

echo json_encode([
    'tipo'        => ucfirst($dados['tipo']),
    'descricao'   => $dados['descricao'],
    'origem'      => $dados['origem_nome'],
    'destino'     => $dados['destino_nome'],
    'responsavel' => $dados['nome_solicitado'] ?: '-',
    'observacoes' => $dados['observacoes'] ?: '-',
    'acao'        => $dados['acao']

]);
