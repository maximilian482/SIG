<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

$cpf     = $_SESSION['cpf'] ?? '';
$cargo   = strtolower($_SESSION['cargo'] ?? '');
$lojaId  = intval($_GET['id'] ?? 0); // agora usamos 'id' como parâmetro


function buscarFuncionarioPorId($conn, $id) {
  if (!$id) return ['nome' => '—', 'telefone' => ''];
  $stmt = $conn->prepare("SELECT nome, telefone FROM funcionarios WHERE id = ? AND desligamento IS NULL LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->num_rows ? $result->fetch_assoc() : ['nome' => '—', 'telefone' => ''];
}

function buscarResponsavelPorCargo($conn, $lojaId, $cargoAlvo) {
  $stmt = $conn->prepare("
    SELECT f.nome, f.telefone
    FROM funcionarios f
    JOIN cargos c ON f.cargo_id = c.id
    WHERE f.loja_id = ? AND f.desligamento IS NULL AND LOWER(c.nome_cargo) = ?
    LIMIT 1
  ");
  $cargoAlvo = strtolower($cargoAlvo);
  $stmt->bind_param("is", $lojaId, $cargoAlvo);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc() ?? ['nome' => '—', 'telefone' => ''];
}

// Verifica acesso
function temAcesso($conn, $cpf, $modulo) {
  $cargoSessao = strtolower($_SESSION['cargo'] ?? '');

  // Cargos com acesso total
  if (in_array($cargoSessao, ['super', 'ceo'])) {
    return true;
  }

  // Lista de módulos válidos
  $modulosPermitidos = ['relatorios', 'cadastro', 'financeiro']; // ajuste conforme suas colunas reais
  if (!in_array($modulo, $modulosPermitidos)) {
    return false;
  }

  // Consulta segura
  $query = "SELECT 1 FROM acessos_usuarios au 
            JOIN funcionarios f ON au.funcionario_id = f.id 
            WHERE f.cpf = ? AND au.$modulo = 1 LIMIT 1";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $cpf);
  $stmt->execute();
  return $stmt->get_result()->num_rows > 0;
}

// Proteção de acesso
if (!isset($_SESSION['usuario']) || !temAcesso($conn, $cpf, 'relatorios')) {
  header('Location: ../index.php');
  exit;
}


// Função para alerta de certificado
function alertaCertificado($dataValidade) {
  if (!$dataValidade) return ['texto' => 'Não cadastrado', 'cor' => 'gray'];
  $hoje = new DateTime();
  $validade = new DateTime($dataValidade);
  $dias = $hoje->diff($validade)->days;
  if ($validade < $hoje)   return ['texto' => "❌ Expirado há {$dias} dias", 'cor' => 'red'];
  if ($dias <= 30)         return ['texto' => "⚠️ Vence em {$dias} dias",  'cor' => 'orange'];
  return ['texto' => "⏳ Vence em {$dias} dias", 'cor' => 'green'];
}

// Busca loja
$stmt = $conn->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
  $loja = $result->fetch_assoc();
} else {
  echo "<p>❌ Loja não encontrada.</p><a class='btn' href='lojas.php'>🔙 Voltar</a>";
  exit;
}

// Certificado digital
$stmtCert = $conn->prepare("
  SELECT validade,
         arquivo,
         TRIM(COALESCE(senha, '')) AS senha
  FROM lojas_certificados
  WHERE loja_id = ?
  LIMIT 1
");
$stmtCert->bind_param("i", $lojaId);
$stmtCert->execute();
$certificado = $stmtCert->get_result()->fetch_assoc();

$alertaCert = alertaCertificado($certificado['validade'] ?? null);

// Equipamentos
$equipamentos = [];
$stmt = $conn->prepare("SELECT nome, ip, observacao FROM lojas_equipamentos WHERE loja_id = ?");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();
while ($eq = $result->fetch_assoc()) {
  $equipamentos[] = $eq;
}

// Funcionários ativos
$stmt = $conn->prepare("SELECT nome, telefone, cargo_id FROM funcionarios WHERE loja_id = ? AND desligamento IS NULL");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$funcionarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$funcionariosAtivos = count($funcionarios);

// Gerente e subgerente
$responsavel = $subgerente = ['nome' => '—', 'telefone' => ''];
foreach ($funcionarios as $f) {
  $cargo = strtolower($f['cargo_id']);
  if ($cargo === 'gerente') $responsavel = ['nome' => $f['nome'], 'telefone' => $f['telefone']];
  if ($cargo === 'subgerente') $subgerente = ['nome' => $f['nome'], 'telefone' => $f['telefone']];
}

// Chamados abertos
$chamadosTI = $chamadosManutencao = 0;

$stmt = $conn->prepare("
  SELECT cg.nome_cargo
  FROM chamados ch
  JOIN funcionarios f ON ch.responsavel_id = f.id
  JOIN cargos cg ON f.cargo_id = cg.id
  WHERE ch.loja_origem = ? AND ch.status = 'Aberto'
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();

while ($c = $result->fetch_assoc()) {
  $cargo = strtolower($c['nome_cargo']);
  if ($cargo === 'ti') $chamadosTI++;
  if ($cargo === 'manutencao') $chamadosManutencao++;
}


// Inconformidades abertas
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM inconformidades WHERE loja_id = ? AND status = 'Aberto'");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$inconfAbertas = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$responsavel = buscarResponsavelPorCargo($conn, $lojaId, 'gerente');
$subgerente  = buscarResponsavelPorCargo($conn, $lojaId, 'subgerente');

$gerenteId    = intval($loja['gerente_id'] ?? 0);
$subgerenteId = intval($loja['subgerente_id'] ?? 0);

$responsavel  = buscarFuncionarioPorId($conn, $gerenteId);
$subgerente   = buscarFuncionarioPorId($conn, $subgerenteId);
$textoObs = $loja['observacoes'] ?? '—';


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel da Loja <?= htmlspecialchars($loja['nome']) ?></title>
  <link rel="stylesheet" href="../css/loja.css">
</head>
<body>
<div class="container">

  <h2>🏪 Painel da Unidade: <?= htmlspecialchars($loja['nome']) ?></h2>

  <!-- Indicadores -->
  <div class="secao">
    <h3>📊 Indicadores operacionais</h3>
    <div class="tabela-container">
      <table>
        <tr><th>Indicador</th><th>Valor</th></tr>
        <tr><td>Funcionários ativos</td><td><span class="badge verde"><?= $funcionariosAtivos ?></span></td></tr>
        <tr><td>Chamados TI abertos</td><td><?= $chamadosTI > 0 ? "<span class='badge amarelo'>{$chamadosTI}</span>" : "<span class='badge verde'>✅ Tudo certo</span>" ?></td></tr>
        <tr><td>Chamados Manutenção abertos</td><td><?= $chamadosManutencao > 0 ? "<span class='badge amarelo'>{$chamadosManutencao}</span>" : "<span class='badge verde'>✅ Tudo certo</span>" ?></td></tr>
        <tr><td>Inconformidades abertas</td><td><?= $inconfAbertas > 0 ? "<span class='badge amarelo'>{$inconfAbertas}</span>" : "<span class='badge verde'>✅ Tudo certo</span>" ?></td></tr>
      </table>
    </div>
  </div>

  <!-- Informações gerais -->
  <div class="secao">
    <h3>📋 Informações gerais <a href="editar_info_gerais.php?id=<?= $loja['id'] ?>">✏️</a></h3>
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

  <!-- Certificado -->
<div class="secao">
  <h3>📑 Certificado Digital 
    <a href="editar_certificado.php?id=<?= urlencode($loja['id']) ?>">✏️</a>
  </h3>

  <div class="info-box">
    <strong>Validade:</strong> 
    <?= !empty($certificado['validade']) ? date('d/m/Y', strtotime($certificado['validade'])) : '—' ?>
  </div>

  <div class="info-box" style="color: <?= $alertaCert['cor'] ?>;">
    <strong>Status:</strong> <?= $alertaCert['texto'] ?>
  </div>

  <div class="info-box">
    <strong>Arquivo:</strong>
    <?php if (!empty($certificado['arquivo'])): ?>
      <a href="../<?= htmlspecialchars($certificado['arquivo']) ?>" download>📥 Baixar certificado</a>
    <?php else: ?>
      — Nenhum arquivo definido
    <?php endif; ?>
  </div>

  <div class="info-box">
  <strong>Senha:</strong>
  <?php if (!empty($certificado['senha'])): ?>
    <input type="password" id="senhaCert" value="<?= htmlspecialchars($certificado['senha']) ?>" readonly style="border:none; background:transparent; width:auto;">
    <button type="button" onclick="toggleSenha()" style="cursor:pointer;">👁️</button>
  <?php else: ?>
    — Nenhuma senha definida
  <?php endif; ?>
</div>

<script>
function toggleSenha() {
  const campo = document.getElementById('senhaCert');
  if (campo.type === "password") {
    campo.type = "text";
  } else {
    campo.type = "password";
  }
}
</script>

</div>


  <!-- Inventário -->
  <div class="secao">
    <h3>🧮 Inventário de Dispositivos <a href="editar_equipamentos.php?nome=<?= urlencode($loja['nome']) ?>">✏️</a></h3>
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
      <p>Nenhum equipamento cadastrado.</p>
    <?php endif; ?>
  </div>

  <div class="botoes-acoes">
    <a class="btn" href="lojas.php">🔙 Voltar</a>
  </div>

</div>
</body>
</html>

