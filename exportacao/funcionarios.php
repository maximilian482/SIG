<?php
session_start();

$cpf = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

// Função para verificar acesso
function temAcesso($cpf, $modulo) {
  $acessos = json_decode(@file_get_contents('../dados/acessos_usuarios.json'), true) ?: [];
  return !empty($acessos[$cpf][$modulo]);
}

if (!isset($_SESSION['usuario']) || ($cargo !== 'super' && !temAcesso($cpf, 'relatorios'))) {
  header('Location: ../index.php');
  exit;
}

$lojas         = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];
$cargosRaw     = json_decode(@file_get_contents('../dados/cargos.json'), true) ?: [];
$funcionarios  = json_decode(@file_get_contents('../dados/funcionarios.json'), true) ?: [];

/* ========= Funções utilitárias para datas ========= */

function parseDataFlex($valor) {
  // Aceita formatos comuns: Y-m-d, d/m/Y, d-m-Y, Y/m/d
  if (!$valor || !is_string($valor)) return null;
  $valor = trim($valor);

  // Tenta diretamente com DateTime
  try {
    // Se for yyyy-mm-dd ou algo parseável
    $dt = new DateTime($valor);
    // Protege contra parsing errado de strings que não são data
    // Exige pelo menos presença de dígitos e separadores
    if (!preg_match('/\d{2,4}[\-\/]\d{1,2}[\-\/]\d{1,2}/', $valor) && !preg_match('/\d{4}\-\d{2}\-\d{2}/', $valor)) {
      // continua tentando manualmente
      throw new Exception('forçar tentativa manual');
    }
    return $dt;
  } catch (Exception $e) {}

  // Tenta padrões explícitos
  $formatos = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'm-d-Y'];
  foreach ($formatos as $fmt) {
    $dt = DateTime::createFromFormat($fmt, $valor);
    if ($dt && $dt->format($fmt) === $valor) {
      return $dt;
    }
  }

  // Última tentativa: normaliza separador e tenta d-m-Y e Y-m-d
  $v = str_replace('/', '-', $valor);
  foreach (['d-m-Y', 'Y-m-d'] as $fmt) {
    $dt = DateTime::createFromFormat($fmt, $v);
    if ($dt && $dt->format($fmt) === $v) return $dt;
  }

  return null;
}

function formatarDataBr($valor) {
  $dt = parseDataFlex($valor);
  return $dt ? $dt->format('d-m-Y') : '—';
}

function tempoDeServico($valorDataInicio) {
  $inicio = parseDataFlex($valorDataInicio);
  if (!$inicio) return '—';
  $hoje = new DateTime('today');
  $dif = $inicio->diff($hoje);

  $partes = [];
  if ($dif->y > 0) $partes[] = $dif->y . ' ano' . ($dif->y > 1 ? 's' : '');
  if ($dif->m > 0) $partes[] = $dif->m . ' mês' . ($dif->m > 1 ? 'es' : '');
  if (empty($partes)) $partes[] = 'Menos de 1 mês';

  return implode(' e ', $partes);
}

/* ========= Cargos ========= */
$cargos = [];
foreach ($cargosRaw as $c) {
  if (is_array($c) && isset($c['nome'])) $cargos[] = $c['nome'];
  elseif (is_string($c)) $cargos[] = $c;
}
$cargos = array_values(array_filter(array_unique($cargos)));

/* ========= Filtros ========= */
$lojaSelecionada   = $_GET['loja']   ?? '';
$cargoSelecionado  = $_GET['cargo']  ?? '';
$statusSelecionado = $_GET['status'] ?? '';
$filtroAplicado    = !empty($lojaSelecionada) || !empty($cargoSelecionado) || !empty($statusSelecionado);

/* ========= Detecta campos disponíveis nos funcionários (dinâmico) ========= */
$camposDetectados = [];
foreach ($lojas as $lojaId => $info) {
  if ($lojaSelecionada && $lojaId !== $lojaSelecionada) continue;
  $lista = $funcionarios[$lojaId] ?? [];
  foreach ($lista as $f) {
    foreach ($f as $campo => $valor) {
      $camposDetectados[$campo] = true;
    }
  }
}
$camposDetectados = array_keys($camposDetectados);

// Aliases para campos especiais
$aliasesContratacao = ['admissao', 'contratacao', 'data_contratacao'];
$aliasesAniversario = ['aniversario', 'nascimento', 'data_nascimento'];

// Descobre qual campo será usado para contratação e aniversário
$campoContratacao = null;
foreach ($aliasesContratacao as $c) {
  if (in_array($c, $camposDetectados, true)) { $campoContratacao = $c; break; }
}

$campoAniversario = null;
foreach ($aliasesAniversario as $c) {
  if (in_array($c, $camposDetectados, true)) { $campoAniversario = $c; break; }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>👥 Exportar Funcionários</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; padding: 30px; color: #333; }
    h2 { font-size: 24px; margin-bottom: 10px; }
    form { margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); max-width: 640px; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    select { width: 100%; padding: 8px; margin-top: 4px; border-radius: 4px; border: 1px solid #ccc; }
    .btn { margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
    .btn:hover { background: #0056b3; }
    .btn-secondary { background: #6c757d; }
    .btn-secondary:hover { background: #5a6268; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 14px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
    .table-wrap { overflow-x: auto; margin-top: 20px; }
    small.muted { color: #666; }
  </style>
</head>
<body>

<h2>👥 Exportar Funcionários</h2>
<p>Filtre os dados por loja, cargo e status antes de visualizar ou exportar.</p>

<form method="get">
  <label for="loja">Loja</label>
  <select name="loja" id="loja">
    <option value="">Todas</option>
    <?php foreach ($lojas as $id => $info):
      $nome = $info['nome'] ?? $id;
      $selected = ($id === $lojaSelecionada) ? 'selected' : '';
    ?>
      <option value="<?= htmlspecialchars($id) ?>" <?= $selected ?>><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="cargo">Cargo</label>
  <select name="cargo" id="cargo">
    <option value="">Todos</option>
    <?php foreach ($cargos as $cargo):
      $selected = ($cargo === $cargoSelecionado) ? 'selected' : '';
    ?>
      <option value="<?= htmlspecialchars($cargo) ?>" <?= $selected ?>><?= htmlspecialchars($cargo) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="status">Status</label>
  <select name="status" id="status">
    <option value="">Todos</option>
    <option value="ativo"   <?= $statusSelecionado === 'ativo'   ? 'selected' : '' ?>>Ativos</option>
    <option value="inativo" <?= $statusSelecionado === 'inativo' ? 'selected' : '' ?>>Inativos</option>
  </select>

  <button type="submit" class="btn">🔍 Aplicar Filtro</button>
</form>

<?php if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>

  <?php
    // Monta a query para exportação com os mesmos filtros
    $query = http_build_query([
      'loja' => $lojaSelecionada,
      'cargo' => $cargoSelecionado,
      'status' => $statusSelecionado
    ]);
  ?>
  <a href="exportar_funcionarios_excel.php?<?= $query ?>" class="btn">📥 Exportar Excel</a>
  <a href="exportar_funcionarios_pdf.php?<?= $query ?>" class="btn btn-secondary">🖨️ Exportar PDF</a>
  <a href="index.php" class="btn btn-secondary" style="margin-top:30px;">🔙 Voltar</a>
  

  <div class="table-wrap">
    <table>
      <tr>
        <th>Loja</th>
        <?php
          // Monta cabeçalho dinâmico: todos os campos, mas com tratamento especial
          foreach ($camposDetectados as $campo) {
            // Pularemos exibição crua de contratação e aniversário; formatamos abaixo
            if ($campoContratacao && $campo === $campoContratacao) { 
              echo '<th>Contratação</th><th>Tempo de serviço</th>';
            } elseif ($campoAniversario && $campo === $campoAniversario) {
              echo '<th>Aniversário</th>';
            } else {
              echo '<th>' . htmlspecialchars(ucfirst($campo)) . '</th>';
            }
          }
          // Caso não exista nenhum campo de contratação nos dados, ainda assim queremos a coluna Tempo?
          if (!$campoContratacao) {
            // Nada a fazer: sem data de contratação, não dá pra calcular tempo de serviço.
          }
        ?>
      </tr>

      <?php foreach ($lojas as $lojaId => $info):
        if ($lojaSelecionada && $lojaId !== $lojaSelecionada) continue;
        $lista = $funcionarios[$lojaId] ?? [];

        foreach ($lista as $f):
          $cargo = $f['cargo'] ?? '';
          $ativo = !empty($f['ativo']);

          if ($cargoSelecionado && $cargo !== $cargoSelecionado) continue;
          if ($statusSelecionado === 'ativo'   && !$ativo) continue;
          if ($statusSelecionado === 'inativo' &&  $ativo) continue;
      ?>
        <tr>
          <td><?= htmlspecialchars($info['nome'] ?? $lojaId) ?></td>

          <?php
            // Renderização por campo, com formatações
            foreach ($camposDetectados as $campo) {
              // Contratação: formata + tempo de serviço
              if ($campoContratacao && $campo === $campoContratacao) {
                $contratacaoBr = formatarDataBr($f[$campoContratacao] ?? '');
                $tempo = tempoDeServico($f[$campoContratacao] ?? '');
                echo '<td>' . htmlspecialchars($contratacaoBr) . '</td>';
                echo '<td>' . htmlspecialchars($tempo) . '</td>';
                continue;
              }

              // Aniversário: formata
              if ($campoAniversario && $campo === $campoAniversario) {
                $anivBr = formatarDataBr($f[$campoAniversario] ?? '');
                echo '<td>' . htmlspecialchars($anivBr) . '</td>';
                continue;
              }

              // Demais campos: valor bruto (escapado). Para booleanos, deixa amigável
              $valor = $f[$campo] ?? '—';
              if (is_bool($valor)) {
                $valor = $valor ? 'Sim' : 'Não';
              }
              echo '<td>' . htmlspecialchars((string)$valor) . '</td>';
            }
          ?>
        </tr>
      <?php endforeach; ?>
      <?php endforeach; ?>
    </table>
    <?php
$totalFuncionarios = 0;
$contagemPorCargo = [];

foreach ($lojas as $lojaId => $info) {
  if ($lojaSelecionada && $lojaId !== $lojaSelecionada) continue;
  $lista = $funcionarios[$lojaId] ?? [];

  foreach ($lista as $f) {
    $cargo = $f['cargo'] ?? '—';
    $ativo = !empty($f['ativo']);

    if ($cargoSelecionado && $cargo !== $cargoSelecionado) continue;
    if ($statusSelecionado === 'ativo'   && !$ativo) continue;
    if ($statusSelecionado === 'inativo' &&  $ativo) continue;

    $totalFuncionarios++;
    if (!isset($contagemPorCargo[$cargo])) {
      $contagemPorCargo[$cargo] = 0;
    }
    $contagemPorCargo[$cargo]++;
  }
}
?>

<div style="margin-top: 20px; background: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); max-width: 600px;">
  <strong>👥 Total de funcionários exibidos:</strong> <?= $totalFuncionarios ?><br><br>
  <strong>📊 Distribuição por cargo:</strong>
  <ul style="margin-top: 8px;">
    <?php foreach ($contagemPorCargo as $cargo => $quantidade): ?>
      <li><?= htmlspecialchars($cargo) ?>: <?= $quantidade ?></li>
    <?php endforeach; ?>
  </ul>
</div>

  </div>
<?php endif; ?>

<!-- <a href="index.php" class="btn btn-secondary" style="margin-top:30px;">🔙 Voltar</a> -->

</body>
</html>
