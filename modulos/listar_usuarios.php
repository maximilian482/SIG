<?php
session_start();
if (($_SESSION['perfil'] ?? '') !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$usuarios     = json_decode(@file_get_contents('../dados/usuarios.json'), true) ?: [];
$vinculos     = json_decode(@file_get_contents('../dados/vinculos_usuarios.json'), true) ?: [];
$funcionarios = json_decode(@file_get_contents('../dados/funcionarios.json'), true) ?: [];

// Utilitário para normalizar CPF (só dígitos)
function soDigitos($v) {
  return preg_replace('/\D+/', '', (string)$v);
}

// 1) Cria um índice por CPF a partir de funcionarios.json (aninhado por loja)
$funcPorCpf = []; // [cpfNumerico] => ['nome' => ..., 'loja' => ..., 'dados' => ...]
foreach ($funcionarios as $lojaId => $lista) {
  // $lista pode ser array numérico OU objeto com chaves "0","1",...
  if (is_array($lista)) {
    foreach ($lista as $f) {
      $cpf = soDigitos($f['cpf'] ?? '');
      if ($cpf) {
        $funcPorCpf[$cpf] = [
          'nome' => $f['nome'] ?? '',
          'loja' => $lojaId,
          'dados' => $f,
        ];
      }
    }
  }
}

// 2) Função para resolver nome do funcionário com múltiplas fontes
function resolverNomeFuncionario($cpfChave, $funcPorCpf, $usuarios, $vinculos) {
  $cpfNum = soDigitos($cpfChave);

  // a) Tenta no índice de funcionários
  if (isset($funcPorCpf[$cpfNum])) {
    $nome = trim((string)($funcPorCpf[$cpfNum]['nome'] ?? ''));
    if ($nome !== '') return $nome;
  }

  // b) Tenta em usuarios.json (pode estar indexado por CPF ou por usuário)
  if (isset($usuarios[$cpfChave]['nome'])) {
    $n = trim((string)$usuarios[$cpfChave]['nome']);
    if ($n !== '') return $n;
  }
  if (isset($usuarios[$cpfNum]['nome'])) {
    $n = trim((string)$usuarios[$cpfNum]['nome']);
    if ($n !== '') return $n;
  }

  // c) Tenta em vinculos_usuarios.json
  if (isset($vinculos[$cpfChave]['nome'])) {
    $n = trim((string)$vinculos[$cpfChave]['nome']);
    if ($n !== '') return $n;
  }
  if (isset($vinculos[$cpfNum]['nome'])) {
    $n = trim((string)$vinculos[$cpfNum]['nome']);
    if ($n !== '') return $n;
  }

  return '(desconhecido)';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Gestão de Acessos</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>🔐 Gestão de Acessos</h2>
<p>Visualize e edite os acessos vinculados aos funcionários.</p>

<table>
  <tr>
    <th>Usuário</th>
    <th>Funcionário</th>
    <th>CPF</th>
    <th>Cargo</th>
    <th>Loja</th>
    <th>Perfil</th>
    <th>Ações</th>
  </tr>
  <?php foreach ($vinculos as $cpf => $v): ?>
    <?php
      $usuario   = $v['usuario'] ?? '';
      $perfil    = $v['perfil'] ?? 'padrao';
      $cargo     = $v['cargo'] ?? '';
      $lojaVinc  = $v['loja'] ?? '';
      $nome      = resolverNomeFuncionario($cpf, $funcPorCpf, $usuarios, $vinculos);
    ?>
    <tr>
      <td><?= htmlspecialchars($usuario) ?></td>
      <td><?= htmlspecialchars($nome) ?></td>
      <td><?= htmlspecialchars($cpf) ?></td>
      <td><?= htmlspecialchars($cargo) ?></td>
      <td><?= htmlspecialchars($lojaVinc ?: '—') ?></td>
      <td><?= htmlspecialchars($perfil) ?></td>
      <td>
        <a href="editar_usuario.php?cpf=<?= urlencode($cpf) ?>">✏️ Editar</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<a href="cadastrar_usuario.php" class="btn" style="margin:10px 0; display:inline-block;">➕ Usuário</a>
<a class="btn" href="../index.php" style="margin-top:20px;">🔙 Voltar</a>

</body>
</html>
