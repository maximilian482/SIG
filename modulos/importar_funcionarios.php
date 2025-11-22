<?php
require_once '../dados/conexao.php';
$conn = conectar();

function normalizarCpf($cpfRaw) {
    // remove tudo que não for número
    $cpf = preg_replace('/\D+/', '', (string)$cpfRaw);
    // completa com zeros à esquerda até 11 dígitos
    return str_pad($cpf, 11, '0', STR_PAD_LEFT);
}

function normalizarData($str) {
    $str = trim((string)$str);
    if ($str === '') return null;

    // Aceita formatos como 1/7/2024 ou 01/07/2024
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $match)) {
        $dia = str_pad($match[1], 2, '0', STR_PAD_LEFT);
        $mes = str_pad($match[2], 2, '0', STR_PAD_LEFT);
        $ano = $match[3];
        return "$ano-$mes-$dia";
    }

    // Já está no formato yyyy-mm-dd
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return $str;

    return null;
}

function normalizarNome($nomeRaw) {
    // Converte para Title Case (primeira letra de cada palavra maiúscula)
    return mb_convert_case(trim($nomeRaw), MB_CASE_TITLE, "UTF-8");
}

$relatorio = ['inseridos'=>0,'atualizados'=>0,'ignorados'=>0,'erros'=>0,'mensagens'=>[]];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $conn->begin_transaction(); // inicia transação

    try {
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
                'email'       => array_search('email', $map),
                'contratacao' => array_search('contratacao', $map),
                'nascimento'  => array_search('nascimento', $map),
            ];

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $codigo      = $row[$indices['codigo']] ?? null;
                $nome        = $row[$indices['nome']] ?? null;
                if ($nome) {
                    $nome = normalizarNome($nome);
                }
                $cpfRaw      = $row[$indices['cpf']] ?? null;
                $cargoId     = isset($row[$indices['cargo_id']]) ? (int)trim($row[$indices['cargo_id']]) : null;
                $lojaId      = isset($row[$indices['loja_id']])  ? (int)trim($row[$indices['loja_id']])  : null;
                $email       = $row[$indices['email']] ?? null;
                $contratacao = normalizarData($row[$indices['contratacao']] ?? '');
                $nascimento  = normalizarData($row[$indices['nascimento']] ?? '');

                $cpf = normalizarCpf($cpfRaw);
                if (!$nome || !$cpf) {
                    $relatorio['ignorados']++;
                    $relatorio['mensagens'][] = "Ignorado: nome/CPF inválidos ({$cpf}).";
                    continue;
                }

                // senha inicial = 6 primeiros dígitos do CPF
                $senhaInicial = substr($cpf, 0, 6);

                // Monta dinamicamente os campos de UPDATE
                $updates = [];
                if ($nome)        $updates[] = "nome=VALUES(nome)";
                if ($cargoId)     $updates[] = "cargo_id=VALUES(cargo_id)";
                if ($lojaId)      $updates[] = "loja_id=VALUES(loja_id)";
                if ($email)       $updates[] = "email=VALUES(email)";
                if ($contratacao) $updates[] = "contratacao=VALUES(contratacao)";
                if ($nascimento)  $updates[] = "nascimento=VALUES(nascimento)";
                if ($codigo)      $updates[] = "codigo=VALUES(codigo)";

                $sql = "
                    INSERT INTO funcionarios (codigo, nome, cpf, cargo_id, loja_id, email, contratacao, nascimento, senha)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE " . implode(", ", $updates);

                $stmt = $conn->prepare($sql);
                // Tipos: codigo(s), nome(s), cpf(s), cargo_id(i), loja_id(i), email(s), contratacao(s), nascimento(s), senha(s)
                $stmt->bind_param("ssssissss",
                    $codigo, $nome, $cpf, $cargoId, $lojaId, $email, $contratacao, $nascimento, $senhaInicial
                );

                if ($stmt->execute()) {
                    if ($conn->affected_rows === 1) $relatorio['inseridos']++;
                    else $relatorio['atualizados']++;
                } else {
                    throw new Exception("Erro CPF {$cpf}: ".$stmt->error);
                }
            }
            fclose($handle);
        }

        $conn->commit(); // confirma tudo se não houve erro
    } catch (Exception $e) {
        $conn->rollback(); // desfaz tudo se houve erro
        $relatorio['erros']++;
        $relatorio['mensagens'][] = "Rollback executado: ".$e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Importar Funcionários</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    h2 {
      color: #2c3e50;
      text-align: center;
    }
    form {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      max-width: 400px;
      margin: 20px auto;
      text-align: center;
    }
    input[type="file"] {
      margin: 10px 0;
    }
    button {
      background: #3498db;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }
    button:hover {
      background: #2980b9;
    }
    .btn-voltar {
      display: inline-block;
      margin: 20px auto;
      text-align: center;
    }
    .btn-voltar a {
      background: #95a5a6;
      color: #fff;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 14px;
    }
    .btn-voltar a:hover {
      background: #7f8c8d;
    }
    .relatorio {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      max-width: 500px;
      margin: 20px auto;
    }
    .relatorio h3 {
      margin-top: 0;
      color: #27ae60;
    }
    .relatorio ul {
      list-style: none;
      padding: 0;
    }
    .relatorio li {
      padding: 8px;
      border-bottom: 1px solid #eee;
    }
    .mensagem {
      background: #ecf0f1;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
      font-size: 13px;
    }
  </style>
</head>
<body>
  <h2>📥 Importar Funcionários</h2>
  
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="arquivo" accept=".csv" required>
    <br>
    <button type="submit">Importar</button>
  </form>

  <div class="btn-voltar">
    <a href="gestao_funcionarios.php">⬅ Voltar para Gestão de Funcionários</a>
  </div>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="relatorio">
      <h3>Resumo da Importação</h3>
      <ul>
        <li><strong>Inseridos:</strong> <?= $relatorio['inseridos'] ?></li>
        <li><strong>Atualizados:</strong> <?= $relatorio['atualizados'] ?></li>
        <li><strong>Ignorados:</strong> <?= $relatorio['ignorados'] ?></li>
        <li><strong>Erros:</strong> <?= $relatorio['erros'] ?></li>
      </ul>
      <?php foreach ($relatorio['mensagens'] as $m): ?>
        <div class="mensagem"><?= htmlspecialchars($m) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</body>
</html>

