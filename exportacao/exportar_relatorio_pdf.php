<?php
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ========= Parâmetros =========
$origem = $_GET['origem'] ?? '';
if (!$origem) {
  die('Origem não especificada.');
}

$filtros = $_GET;
unset($filtros['origem']);

$arquivoFonte = "../dados/{$origem}.json";
$dados = json_decode(@file_get_contents($arquivoFonte), true) ?: [];

if (!$dados) {
  die('Dados não encontrados ou inválidos.');
}

// ========= Filtragem =========
$filtrados = [];

foreach ($dados as $grupoId => $lista) {
  if (!is_array($lista)) continue; // ignora se não for array

  foreach ($lista as $item) {
    if (!is_array($item)) continue; // ignora se o item não for array

    $ok = true;

    foreach ($filtros as $campo => $valor) {
      if ($valor === '') continue;

      // Se o campo não existe no item, ignora
      if (!array_key_exists($campo, $item)) continue;

      // Compara como string para evitar problemas de tipo
      if ((string)$item[$campo] !== (string)$valor) {
        $ok = false;
        break;
      }
    }

    if ($ok) {
      $item['grupo'] = $grupoId;
      $filtrados[] = $item;
    }
  }
}


if (empty($filtrados)) {
  die('Nenhum dado encontrado com os filtros aplicados.');
}

// ========= Detecta campos =========
$campos = [];
foreach ($filtrados as $item) {
  foreach ($item as $campo => $valor) {
    $campos[$campo] = true;
  }
}
$campos = array_keys($campos);

// ========= Monta HTML =========
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Segoe UI', sans-serif; font-size: 12px; }
    h2 { margin-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
  </style>
</head>
<body>

<h2>Relatório: <?= ucfirst($origem) ?></h2>

<table>
  <tr>
    <th>Grupo</th>
    <?php foreach ($campos as $campo): ?>
      <th><?= ucfirst($campo) ?></th>
    <?php endforeach; ?>
  </tr>

  <?php foreach ($filtrados as $item): ?>
    <tr>
      <td><?= htmlspecialchars($item['grupo']) ?></td>
      <?php foreach ($campos as $campo): ?>
        <?php
          $valor = $item[$campo] ?? '—';

          // Formata moeda
          if (in_array($campo, ['valor', 'valor_unitario', 'preco', 'custo_unitario', 'valor_total'])) {
            $valor = preg_replace('/[^\d,\.]/', '', (string)$valor);
            $valor = str_replace('.', '', $valor); // remove milhar
            $valor = str_replace(',', '.', $valor); // vírgula vira decimal
            $valor = 'R$ ' . number_format((float)$valor, 2, ',', '.');
          }

          // Booleanos
          if (is_bool($valor)) {
            $valor = $valor ? 'Sim' : 'Não';
          }
        ?>
        <td><?= htmlspecialchars((string)$valor) ?></td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
</table>

<p style="margin-top: 20px;">Total de registros: <strong><?= count($filtrados) ?></strong></p>

</body>
</html>
<?php
$html = ob_get_clean();

// ========= Gera PDF =========
$options = new Options();
$options->set('defaultFont', 'Segoe UI');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("relatorio_{$origem}.pdf", ["Attachment" => true]);
