<?php
require_once '../dados/conexao.php';
$conn = conectar();

function normalizarCpf($cpfRaw) {
    $cpf = preg_replace('/\D+/', '', (string)$cpfRaw);
    return strlen($cpf) === 11 ? $cpf : null;
}
function normalizarData($str) {
    $str = trim((string)$str);
    if ($str === '') return null;
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $str)) {
        [$d, $m, $y] = explode('/', $str);
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return $str;
    return null;
}

$relatorio = ['inseridos'=>0,'atualizados'=>0,'ignorados'=>0,'erros'=>0,'mensagens'=>[]];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $handle = fopen($_FILES['arquivo']['tmp_name'], 'r');
    if ($handle) {
        $cabecalho = fgetcsv($handle, 0, ",");
        $map = array_map('strtolower', $cabecalho);

        $indices = [
            'codigo'      => array_search('codigo', $map),
            'nome'        => array_search('nome', $map),
            'cpf'         => array_search('cpf', $map),
            'cargo_id'    => array_search('cargo_id', $map),
            'loja_id'     => array_search('loja_id', $map),
            'email'       => array_search('email', $map),   // novo campo
            'contratacao' => array_search('contratacao', $map),
            'nascimento'  => array_search('nascimento', $map),
        ];

        $stmt = $conn->prepare("
            INSERT INTO funcionarios (codigo, nome, cpf, cargo_id, loja_id, email, contratacao, nascimento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              nome=VALUES(nome), cargo_id=VALUES(cargo_id), loja_id=VALUES(loja_id),
              email=VALUES(email), contratacao=VALUES(contratacao), nascimento=VALUES(nascimento),
              codigo=VALUES(codigo)
        ");

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $codigo      = $row[$indices['codigo']] ?? null;
            $nome        = $row[$indices['nome']] ?? null;
            $cpfRaw      = $row[$indices['cpf']] ?? null;
            $cargoId     = $row[$indices['cargo_id']] ?? null;
            $lojaId      = $row[$indices['loja_id']] ?? null;
            $email       = $row[$indices['email']] ?? null;
            $contratacao = normalizarData($row[$indices['contratacao']] ?? '');
            $nascimento  = normalizarData($row[$indices['nascimento']] ?? '');

            $cpf = normalizarCpf($cpfRaw);
            if (!$nome || !$cpf) {
                $relatorio['ignorados']++;
                $relatorio['mensagens'][] = "Ignorado: nome/CPF inválidos.";
                continue;
            }

            $stmt->bind_param("sssissss",
                $codigo, $nome, $cpf, $cargoId, $lojaId, $email, $contratacao, $nascimento
            );
            if ($stmt->execute()) {
                if ($conn->affected_rows === 1) $relatorio['inseridos']++;
                else $relatorio['atualizados']++;
            } else {
                $relatorio['erros']++;
                $relatorio['mensagens'][] = "Erro CPF {$cpf}: ".$stmt->error;
            }
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head><meta charset="UTF-8"><title>Importar Funcionários</title></head>
<body>
  <h2>📥 Importar Funcionários</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="arquivo" accept=".csv" required>
    <button type="submit">Importar</button>
  </form>
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <h3>Resumo:</h3>
    <ul>
      <li>Inseridos: <?= $relatorio['inseridos'] ?></li>
      <li>Atualizados: <?= $relatorio['atualizados'] ?></li>
      <li>Ignorados: <?= $relatorio['ignorados'] ?></li>
      <li>Erros: <?= $relatorio['erros'] ?></li>
    </ul>
    <?php foreach ($relatorio['mensagens'] as $m): ?>
      <p><?= htmlspecialchars($m) ?></p>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
