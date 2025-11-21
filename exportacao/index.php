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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>📁 Exportação de Relatórios</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f9f9f9;
      padding: 30px;
      color: #333;
    }

    h2 {
      font-size: 24px;
      margin-bottom: 10px;
    }

    p {
      font-size: 15px;
      margin-bottom: 30px;
    }

    .export-menu {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
    }

    .export-card {
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      transition: 0.3s;
    }

    .export-card:hover {
      background: #f0f8ff;
      transform: scale(1.02);
    }

    .export-card h3 {
      margin-bottom: 10px;
      font-size: 18px;
    }

    .export-card p {
      font-size: 14px;
      color: #555;
      margin-bottom: 12px;
    }

    .export-card a {
      display: inline-block;
      margin-top: 10px;
      padding: 8px 16px;
      background: #007bff;
      color: white;
      border-radius: 4px;
      text-decoration: none;
      font-weight: bold;
    }

    .export-card a:hover {
      background: #0056b3;
    }

    .btn-voltar {
      margin-top: 40px;
      display: inline-block;
      padding: 10px 20px;
      background: #6c757d;
      color: white;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }

    .btn-voltar:hover {
      background: #5a6268;
    }
  </style>
</head>
<body>

<h2>📁 Exportação de Relatórios</h2>
<p>Escolha uma categoria para visualizar os filtros e opções de exportação.</p>

<div class="export-menu">

  <div class="export-card">
    <h3>👥 Funcionários</h3>
    <p>Exportar por loja, cargo ou status. Ideal para RH e gestão de equipe.</p>
    <a href="funcionarios.php">Acessar</a>
  </div>

  <div class="export-card">
    <h3>📦 Inventário</h3>
    <p>Exportar equipamentos por loja, tipo e status. Útil para controle patrimonial.</p>
    <a href="inventario.php">Acessar</a>
  </div>

  <div class="export-card">
    <h3>📨 Chamados</h3>
    <p>Exportar chamados por setor, status e loja. Ideal para TI e manutenção.</p>
    <a href="chamados.php">Acessar</a>
  </div>

  <div class="export-card">
    <h3>🛠️ Inconformidades</h3>
    <p>Exportar por loja, tipo e status. Útil para auditoria e conformidade.</p>
    <a href="inconformidades.php">Acessar</a>
  </div>

</div>

<!-- <a href="../modulos/relatorios.php" class="btn-voltar">🔙 Voltar aos relatórios</a> -->
  <a class="btn" href="../index.php" style="margin-top:20px; display:inline-block;">🔙 Voltar ao painel principal</a>


</body>
</html>
