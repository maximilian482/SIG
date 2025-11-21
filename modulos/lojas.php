<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

$cpf   = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

// Função de verificação de acesso
function temAcesso($conn, $cpf, $modulo) {
  $cargoSessao = strtolower($_SESSION['cargo'] ?? '');
  if (in_array($cargoSessao, ['super', 'ceo'])) {
    return true;
  }

  $modulosPermitidos = ['relatorios', 'cadastro', 'financeiro'];
  if (!in_array($modulo, $modulosPermitidos)) {
    return false;
  }

  $query = "SELECT 1 FROM acessos_usuarios au 
            JOIN funcionarios f ON au.funcionario_id = f.id 
            WHERE f.cpf = ? AND au.$modulo = 1 LIMIT 1";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $cpf);
  $stmt->execute();
  return $stmt->get_result()->num_rows > 0;
}

// Verifica sessão e acesso
if (!isset($_SESSION['usuario']) || !temAcesso($conn, $cpf, 'relatorios')) {
  header('Location: ../index.php');
  exit;
}

// Função para alerta de certificado
function alertaCertificado($dataValidade) {
  if (!$dataValidade) {
    return ['texto' => 'Não cadastrado', 'cor' => 'gray'];
  }

  $hoje = new DateTime();
  $validade = new DateTime($dataValidade);
  $intervalo = $hoje->diff($validade);
  $dias = (int)$intervalo->days;

  if ($validade < $hoje) {
    return [
      'texto' => "❌ Expirado há {$dias} dia" . ($dias > 1 ? 's' : ''),
      'cor'   => 'red'
    ];
  } elseif ($dias <= 30) {
    return [
      'texto' => "⚠️ Vence em {$dias} dia" . ($dias > 1 ? 's' : ''),
      'cor'   => 'orange'
    ];
  } else {
    return [
      'texto' => "⏳ Vence em {$dias} dia" . ($dias > 1 ? 's' : ''),
      'cor'   => 'green'
    ];
  }
}

// Consulta às lojas com responsáveis
$lojas = [];
$stmt = $conn->prepare("
  SELECT
    l.id, l.nome, l.cnpj, lc.validade AS certificado_validade,
    fg.nome AS nome_gerente, fg.telefone AS tel_gerente,
    fs.nome AS nome_subgerente, fs.telefone AS tel_subgerente
  FROM lojas l
  LEFT JOIN lojas_certificados lc ON lc.loja_id = l.id
  LEFT JOIN funcionarios fg ON l.gerente_id = fg.id AND fg.desligamento IS NULL
  LEFT JOIN funcionarios fs ON l.subgerente_id = fs.id AND fs.desligamento IS NULL
  ORDER BY l.nome
");
$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) {
  $lojas[] = $linha;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Gestão de Lojas</title>
  <link rel="stylesheet" href="../css/loja.css">
</head>
<body>
<div class="container">

  <h2>🏪 Lojas cadastradas</h2>
  <p>Visualize todas as unidades com informações cruciais e acesse os detalhes completos.</p>

  <div class="tabela-container">
    <table>
      <thead>
        <tr>
          <th>Unidade</th>
          <th>CNPJ</th>
          <th>Responsável</th>
          <th>2º Responsável</th>
          <th>Certificado Digital</th>
          <th>Status</th>
          <th>Detalhes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lojas as $loja): 
          $data = $loja['certificado_validade'] ?? null;
          $alerta = alertaCertificado($data);
        ?>
          <tr>
            <td><?= htmlspecialchars($loja['nome']) ?></td>
            <td><?= htmlspecialchars($loja['cnpj']) ?></td>
            <td><?= htmlspecialchars($loja['nome_gerente'] ?? '—') ?></td>
            <td><?= htmlspecialchars($loja['nome_subgerente'] ?? '—') ?></td>
            <td><?= $data ? date('d/m/Y', strtotime($data)) : '—' ?></td>
            <td style="color: <?= $alerta['cor'] ?>;"><?= $alerta['texto'] ?></td>
            <td><a href="loja.php?id=<?= urlencode($loja['id']) ?>">🔍 Ver painel</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="botoes-acoes">
    <a class="btn" href="/modulos/gestao.php">🏠 Voltar</a>
    <a class="btn" href="adicionar_loja.php">➕ Nova Loja</a>
  </div>

</div>
</body>
</html>
