<?php
function gerarTabelaFuncionarios($funcionarios, $lojas, $filtros = []) {
  $lojaFiltro   = $filtros['loja']   ?? '';
  $cargoFiltro  = $filtros['cargo']  ?? '';
  $statusFiltro = $filtros['status'] ?? '';

  ob_start();
  echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">';
  echo '<tr style="background:#f0f0f0;">
          <th>Loja</th><th>Nome</th><th>Cargo</th><th>Status</th>
          <th>CPF</th><th>Telefone</th><th>Email</th>
          <th>Aniversário</th><th>Contratação</th><th>Tempo de serviço</th>
        </tr>';

  $totalPorCargo = [];
  $totalGeral = 0;

  foreach ($lojas as $lojaId => $info) {
    if ($lojaFiltro && $lojaId !== $lojaFiltro) continue;
    $lista = $funcionarios[$lojaId] ?? [];

    foreach ($lista as $f) {
      $cargo = $f['cargo'] ?? '—';
      $ativo = !empty($f['ativo']);

      if ($cargoFiltro && $cargo !== $cargoFiltro) continue;
      if ($statusFiltro === 'ativo' && !$ativo) continue;
      if ($statusFiltro === 'inativo' && $ativo) continue;

      $dtAdmissao = parseData($f['admissao'] ?? '');
      $dtAniversario = parseData($f['aniversario'] ?? '');

      echo '<tr>';
      echo '<td>' . htmlspecialchars($info['nome'] ?? $lojaId) . '</td>';
      echo '<td>' . htmlspecialchars($f['nome'] ?? '—') . '</td>';
      echo '<td>' . htmlspecialchars($cargo) . '</td>';
      echo '<td>' . ($ativo ? 'Ativo' : 'Inativo') . '</td>';
      echo '<td>' . htmlspecialchars($f['cpf'] ?? '—') . '</td>';
      echo '<td>' . htmlspecialchars($f['telefone'] ?? '—') . '</td>';
      echo '<td>' . htmlspecialchars($f['email'] ?? '—') . '</td>';
      echo '<td>' . ($dtAniversario ? $dtAniversario->format('d-m-Y') : '—') . '</td>';
      echo '<td>' . ($dtAdmissao ? $dtAdmissao->format('d-m-Y') : '—') . '</td>';
      echo '<td>' . ($dtAdmissao ? tempoServico($dtAdmissao) : '—') . '</td>';
      echo '</tr>';

      $totalGeral++;
      $totalPorCargo[$cargo] = ($totalPorCargo[$cargo] ?? 0) + 1;
    }
  }

  echo '</table><br>';
  echo "<strong>Total de funcionários:</strong> $totalGeral<br>";
  echo "<strong>Distribuição por cargo:</strong><ul>";
  foreach ($totalPorCargo as $cargo => $qtd) {
    echo "<li>$cargo: $qtd</li>";
  }
  echo "</ul>";

  return ob_get_clean();
}

function parseData($valor) {
  if (!$valor || !is_string($valor)) return null;

  // Remove hora, se houver
  $valor = preg_replace('/\s+\d{2}:\d{2}(:\d{2})?$/', '', trim($valor));
  $valor = str_replace('/', '-', $valor);

  // Tenta parsing direto
  try {
    return new DateTime($valor);
  } catch (Exception $e) {
    return null;
  }
}


function tempoServico($dt) {
  if (!$dt instanceof DateTime) return '—';
  $hoje = new DateTime();
  $dif = $dt->diff($hoje);
  $anos = $dif->y;
  $meses = $dif->m;
  if ($anos === 0 && $meses === 0) return 'Menos de 1 mês';
  $txt = '';
  if ($anos > 0) $txt .= "$anos ano" . ($anos > 1 ? 's' : '');
  if ($meses > 0) $txt .= ($txt ? ' e ' : '') . "$meses mês" . ($meses > 1 ? 'es' : '');
  return $txt;
}

echo '<pre>';
print_r($f['admissao']);
print_r(parseData($f['admissao']));
echo '</pre>';
