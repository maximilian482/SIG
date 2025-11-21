<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

// Proteção de acesso: apenas gerente
if (!isset($_SESSION['usuario']) || strtolower($_SESSION['cargo'] ?? '') !== 'gerente') {
  header('Location: ../login.php');
  exit;
}

$lojaId = intval($_SESSION['loja'] ?? 0);
if (!$lojaId) {
  echo "<p>❌ Loja não definida na sessão.</p>";
  exit;
}

// Buscar dados da loja
$stmt = $conn->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$loja = $stmt->get_result()->fetch_assoc();
if (!$loja) {
  echo "<p>❌ Loja não encontrada.</p>";
  exit;
}

$textoObs = $loja['observacoes'] ?? '—';


// Funcionários ativos
$stmt = $conn->prepare("
  SELECT f.nome, f.telefone, c.nome_cargo
  FROM funcionarios f
  JOIN cargos c ON f.cargo_id = c.id
  WHERE f.loja_id = ? AND f.desligamento IS NULL
");

$stmt->bind_param("i", $lojaId);
$stmt->execute();
$funcionarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$funcionariosAtivos = count($funcionarios);

$responsavel = $subgerente = ['nome' => '—', 'telefone' => ''];
foreach ($funcionarios as $f) {
  $cargo = strtolower($f['nome_cargo']);
  if ($cargo === 'gerente') {
    $responsavel = ['nome' => $f['nome'], 'telefone' => $f['telefone']];
  }
  if ($cargo === 'subgerente') {
    $subgerente = ['nome' => $f['nome'], 'telefone' => $f['telefone']];
  }
}

// Gerente e subgerente (usando IDs da tabela lojas)
$gerenteId    = intval($loja['gerente_id'] ?? 0);
$subgerenteId = intval($loja['subgerente_id'] ?? 0);

$responsavel  = buscarFuncionarioPorId($conn, $gerenteId);
$subgerente   = buscarFuncionarioPorId($conn, $subgerenteId);

function buscarFuncionarioPorId($conn, $id) {
  if (!$id) return ['nome' => '—', 'telefone' => ''];
  $stmt = $conn->prepare("SELECT nome, telefone FROM funcionarios WHERE id = ? AND desligamento IS NULL LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->num_rows ? $result->fetch_assoc() : ['nome' => '—', 'telefone' => ''];
}


// Indicadores
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM funcionarios WHERE loja_id = ? AND desligamento IS NULL");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$funcionariosAtivos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM chamados WHERE loja_origem = ? AND status = 'Aberto'");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$chamadosAbertos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM inconformidades WHERE loja_id = ? AND status = 'Aberto'");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$inconfAbertas = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Inventário
$equipamentos = [];
$stmt = $conn->prepare("SELECT nome, ip, observacao FROM lojas_equipamentos WHERE loja_id = ?");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();
while ($eq = $result->fetch_assoc()) {
  $equipamentos[] = $eq;
}

// Documentos
// $documentos = [];
// $stmt = $conn->prepare("SELECT nome, validade, arquivo FROM lojas_documentos WHERE loja_id = ?");
// $stmt->bind_param("i", $lojaId);
// $stmt->execute();
// $result = $stmt->get_result();
// while ($doc = $result->fetch_assoc()) {
//   $documentos[] = $doc;
// }

// Certificado digital
$stmt = $conn->prepare("SELECT validade, arquivo, senha FROM lojas_certificados WHERE loja_id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc() ?? [];

// Contratos
// $contratos = [];
// $stmt = $conn->prepare("SELECT tipo, empresa, telefone, responsavel, numero FROM lojas_contratos WHERE loja_id = ?");
// $stmt->bind_param("i", $lojaId);
// $stmt->execute();
// $result = $stmt->get_result();
// while ($c = $result->fetch_assoc()) {
//   $contratos[] = $c;
// }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel da Loja — Gerente</title>
  <link rel="stylesheet" href="../css/loja.css">
</head>
<body>
<div class="container">

<h2>🏪 Painel da Loja: <?= htmlspecialchars($loja['nome']) ?></h2>

<!-- Indicadores -->
<div class="secao">
  <h3>📊 Indicadores</h3>
  <div class="tabela-container">
    <table>
      <tr><th>Funcionários ativos</th><td><span class="badge verde"><?= $funcionariosAtivos ?></span></td></tr>
      <tr><th>Chamados abertos</th><td><?= $chamadosAbertos > 0 ? "<span class='badge amarelo'>{$chamadosAbertos}</span>" : "<span class='badge verde'>✅ Nenhum</span>" ?></td></tr>
      <tr><th>Inconformidades abertas</th><td><?= $inconfAbertas > 0 ? "<span class='badge amarelo'>{$inconfAbertas}</span>" : "<span class='badge verde'>✅ Nenhuma</span>" ?></td></tr>
      <tr><th>Itens no inventário</th><td><?= !empty($equipamentos) ? "<span class='badge verde'>".count($equipamentos)." item(s)</span>" : "<span class='badge amarelo'>Nenhum item registrado</span>" ?></td></tr>
    </table>
  </div>
</div>

<!-- Informações gerais -->
<div class="secao">
  <h3>📋 Informações Gerais</h3>
  <div class="info-box"><strong>Nome:</strong> <?= htmlspecialchars($loja['nome']) ?></div>
    <div class="info-box"><strong>CNPJ:</strong> <?= htmlspecialchars($loja['cnpj']) ?></div>
    <div class="info-box"><strong>Inscrição Estadual:</strong> <?= htmlspecialchars($loja['inscricao_estadual']) ?></div>
    <div class="info-box"><strong>Gerente:</strong> <?= htmlspecialchars($responsavel['nome']) ?> <?= !empty($responsavel['telefone']) ? "📞 " . htmlspecialchars($responsavel['telefone']) : '' ?></div>
    <div class="info-box"><strong>Subgerente:</strong> <?= htmlspecialchars($subgerente['nome']) ?> <?= !empty($subgerente['telefone']) ? "📞 " . htmlspecialchars($subgerente['telefone']) : '' ?></div>
    <div class="info-box"><strong>Endereço:</strong> <?= htmlspecialchars($loja['endereco']) ?>, <?= htmlspecialchars($loja['bairro']) ?> - <?= htmlspecialchars($loja['cidade']) ?>/<?= htmlspecialchars($loja['estado']) ?>, <?= htmlspecialchars($loja['cep']) ?></div>
    <div class="info-box"><strong>Telefone fixo:</strong> <?= htmlspecialchars($loja['telefone_fixo']) ?></div>
    <div class="info-box"><strong>Celular:</strong> <?= htmlspecialchars($loja['celular']) ?></div>
    <div class="info-box"><strong>Email (Gmail):</strong> <?= htmlspecialchars($loja['email_gmail']) ?></div>
    <div class="info-box"><strong>Email corporativo:</strong> <?= htmlspecialchars($loja['email_corporativo']) ?></div>
    <div class="info-box"><strong>Funcionamento:</strong> <?= htmlspecialchars($loja['dias_funcionamento']) ?></div>
    <div class="info-box"><strong>Observações:</strong> <?= nl2br(htmlspecialchars($textoObs)) ?></div>
</div>

<!-- Dispositivos -->
<div class="secao">
  <h3>🧮 Dispositivos</h3>
  <?php if (!empty($equipamentos)): ?>
    <div class="tabela-container">
      <table>
        <tr><th>Nome</th><th>IP</th><th>Observação</th></tr>
        <?php foreach ($equipamentos as $eq): ?>
          <tr>
            <td><?= htmlspecialchars($eq['nome']) ?></td>
            <td><?= htmlspecialchars($eq['ip']) ?></td>
            <td><?= htmlspecialchars($eq['observacao']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Nenhum dispositivo registrado.</p>
  <?php endif; ?>
</div>

<!-- Documentos -->
<div class="secao">
  <h3>📄 Documentos da Loja</h3>
  <?php if (!empty($documentos)): ?>
    <div class="tabela-container">
      <table>
        <tr><th>Nome</th><th>Data</th><th>Arquivo</th></tr>
        <?php foreach ($documentos as $doc): ?>
          <tr>
            <td><?= htmlspecialchars($doc['nome']) ?></td>
            <td><?= !empty($doc['validade']) ? date('d/m/Y', strtotime($doc['validade'])) : '—' ?></td>
            <td><?= !empty($doc['arquivo']) ? "<a href='../".htmlspecialchars($doc['arquivo'])."' download>📥 Baixar</a>" : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Nenhum documento registrado.</p>
  <?php endif; ?>
</div>

<!-- Certificado -->
<div class="secao">
  <h3>🔐 Certificado Digital</h3>
  <div class="info-box"><strong>Validade:</strong> <?= !empty($cert['validade']) ? date('d/m/Y', strtotime($cert['validade'])) : '—' ?></div>
  <div class="info-box"><strong>Senha:</strong> <?= !empty($cert['senha']) ? '🔒 Oculta' : '—' ?></div>
  <div class="info-box"><strong>Arquivo:</strong> <?= !empty($cert['arquivo']) ? "<a href='../".htmlspecialchars($cert['arquivo'])."' download>📥 Baixar certificado</a>" : '— Nenhum arquivo definido' ?></div>
</div>

<!-- Contratos -->
<div class="secao">
  <h3>📑 Contratos</h3>
  <?php if (!empty($contratos)): ?>
    <div class="tabela-container">
      <table>
        <tr><th>Tipo</th><th>Empresa</th><th>Telefone</th><th>Responsável</th><th>Nº Contrato</th></tr>
        <?php foreach ($contratos as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['tipo'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['empresa'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['telefone'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['responsavel'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['numero'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Nenhum contrato registrado.</p>
  <?php endif; ?>
</div>

<div class="botoes-acoes">
  <a class="btn" href="../index.php">🔙 Voltar ao menu</a>
</div>

</div> <!-- fecha container -->
</body>
</html>
