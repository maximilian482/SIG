<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php';
include ROOT_PATH . '/perfil/menu_perfil.php';

// Dados do usuário logado
$cpf        = $_SESSION['cpf'] ?? '';
$cargo      = strtolower($_SESSION['cargo'] ?? '');
$lojaId     = intval($_SESSION['loja'] ?? 0);
$usuario    = $_SESSION['usuario'] ?? 'Usuário';

// Verifica sessão
if (!isset($_SESSION['usuario']) || !$lojaId) {
    header('Location: ../login.php');
    exit;
}

// Apenas gerente/subgerente ou permissão de acesso
$temAcessoLoja = in_array($cargo, ['gerente', 'subgerente']) 
                 || temAcesso($conn, $cpf, 'acesso_painel_loja');

if (!$temAcessoLoja) {
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Acesso restrito à gerência ou responsável autorizado da unidade.</h2>";
    exit;
}


/* ============================================================
   FUNÇÕES AUXILIARES (mesmas do loja.php do ADM)
   ============================================================ */

function buscarFuncionarioPorId($conn, $id) {
    if (!$id) return ['nome' => '—', 'telefone' => ''];
    $stmt = $conn->prepare("SELECT nome, telefone FROM funcionarios WHERE id = ? AND desligamento IS NULL LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows ? $result->fetch_assoc() : ['nome' => '—', 'telefone' => ''];
}

function alertaCertificado($dataValidade) {
    if (!$dataValidade) return ['texto' => 'Não cadastrado', 'cor' => 'gray'];

    $hoje     = new DateTime();
    $validade = new DateTime($dataValidade);
    $dias     = $hoje->diff($validade)->days;

    if ($validade < $hoje) {
        return ['texto' => "❌ Expirado há {$dias} dias", 'cor' => 'red'];
    }
    if ($dias <= 30) {
        return ['texto' => "⚠️ Vence em {$dias} dias", 'cor' => 'orange'];
    }
    return ['texto' => "⏳ Vence em {$dias} dias", 'cor' => 'green'];
}

/* ============================================================
   BUSCA PRINCIPAL DA LOJA (igual ao ADM, mas usando $lojaId)
   ============================================================ */

// Dados da loja
$stmt = $conn->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<p>❌ Loja não encontrada.</p>";
    exit;
}

$loja = $result->fetch_assoc();

// Certificado digital
$stmtCert = $conn->prepare("
    SELECT validade, arquivo, TRIM(COALESCE(senha, '')) AS senha
    FROM lojas_certificados
    WHERE loja_id = ?
    LIMIT 1
");
$stmtCert->bind_param("i", $lojaId);
$stmtCert->execute();
$certificado = $stmtCert->get_result()->fetch_assoc();
$alertaCert  = alertaCertificado($certificado['validade'] ?? null);

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

// Chamados abertos pela loja (qualquer setor)
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chamados
    WHERE loja_origem = ?
      AND LOWER(status) IN ('aberto', 'em andamento', 'reaberto', 'aguardando avaliação')
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$chamadosAbertos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;


// Pendências da loja
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chamados
    WHERE loja_destino = ?
      AND LOWER(status) IN ('aberto', 'em andamento', 'reaberto', 'aguardando avaliação')
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$pendenciasLoja = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Gerente e subgerente
$gerenteId    = intval($loja['gerente_id'] ?? 0);
$subgerenteId = intval($loja['subgerente_id'] ?? 0);

$responsavel  = buscarFuncionarioPorId($conn, $gerenteId);
$subgerente   = buscarFuncionarioPorId($conn, $subgerenteId);

$textoObs = $loja['observacoes'] ?? '—';

// Buscar meta da loja
$stmtMeta = $conn->prepare("SELECT meta FROM lojas WHERE id = ?");
$stmtMeta->bind_param("i", $lojaId);
$stmtMeta->execute();
$meta = $stmtMeta->get_result()->fetch_assoc()['meta'] ?? 0;

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

        <tr>
          <td>Meta da Loja</td>
          <td>
            <span class="meta-valor">
              R$ <?= number_format($meta, 2, ',', '.') ?>
            </span>
          </td>
        </tr>


        <tr>
          <td>Funcionários ativos</td>
          <td><span class="badge verde"><?= $funcionariosAtivos ?></span></td>
        </tr>

        <tr>
          <td>Chamados abertos pela loja</td>
          <td>
            <?= $chamadosAbertos > 0 
              ? "<span class='badge amarelo'>{$chamadosAbertos}</span>" 
              : "<span class='badge verde'>✅ Nenhum chamado aberto</span>" ?>
          </td>
        </tr>


        <tr>
          <td>Pendências da loja</td>
          <td>
            <?= $pendenciasLoja > 0 
              ? "<span class='badge amarelo'>{$pendenciasLoja}</span>" 
              : "<span class='badge verde'>✅ Tudo certo</span>" ?>
          </td>
        </tr>

      </table>
    </div>
  </div>

  <!-- Informações gerais -->
  <div class="secao">
    <h3>📋 Informações gerais </h3>

    <div class="info-box"><strong>Nome:</strong> <?= htmlspecialchars($loja['nome']) ?></div>
    <div class="info-box"><strong>CNPJ:</strong> <?= htmlspecialchars($loja['cnpj']) ?></div>
    <div class="info-box"><strong>Inscrição Estadual:</strong> <?= htmlspecialchars($loja['inscricao_estadual']) ?></div>

    <div class="info-box">
      <strong>Responsável:</strong> 
      <?= htmlspecialchars($responsavel['nome']) ?> 
      <?= !empty($responsavel['telefone']) ? "📞 " . htmlspecialchars($responsavel['telefone']) : '' ?>
    </div>

    <div class="info-box">
      <strong>2º Responsável:</strong> 
      <?= htmlspecialchars($subgerente['nome']) ?> 
      <?= !empty($subgerente['telefone']) ? "📞 " . htmlspecialchars($subgerente['telefone']) : '' ?>
    </div>

    <div class="info-box">
      <strong>Endereço:</strong> 
      <?= htmlspecialchars($loja['endereco']) ?>, 
      <?= htmlspecialchars($loja['bairro']) ?> - 
      <?= htmlspecialchars($loja['cidade']) ?>/<?= htmlspecialchars($loja['estado']) ?>, 
      <?= htmlspecialchars($loja['cep']) ?>
    </div>

    <div class="info-box"><strong>Telefone fixo:</strong> <?= htmlspecialchars($loja['telefone_fixo']) ?></div>
    <div class="info-box"><strong>Celular:</strong> <?= htmlspecialchars($loja['celular']) ?></div>
    <div class="info-box"><strong>Email (Gmail):</strong> <?= htmlspecialchars($loja['email_gmail']) ?></div>
    <div class="info-box"><strong>Email corporativo:</strong> <?= htmlspecialchars($loja['email_corporativo']) ?></div>
    <div class="info-box"><strong>Funcionamento:</strong> <?= htmlspecialchars($loja['dias_funcionamento']) ?></div>
    <div class="info-box"><strong>Observações:</strong> <?= nl2br(htmlspecialchars($textoObs)) ?></div>
  </div>

  <!-- Certificado -->
  <div class="secao">
    <h3>📑 Certificado Digital</h3>

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
        <!-- <button type="button" onclick="toggleSenha()" style="cursor:pointer;">👁️</button> -->
      <?php else: ?>
        — Nenhuma senha definida
      <?php endif; ?>
    </div>

    <script>
    function toggleSenha() {
      const campo = document.getElementById('senhaCert');
      campo.type = campo.type === "password" ? "text" : "password";
    }
    </script>

  </div>

  <!-- Inventário -->
  <div class="secao">
    <h3>🧮 Inventário de Dispositivos</h3>

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
    <a class="btn" href="../index.php">🔙 Voltar</a>
  </div>

</div>
</body>
</html>
