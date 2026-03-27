<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/funcoes.php';

// Título da página
$titulo = "Gestão de Funcionários";

// CSS extra (cards)
$cssExtra = "/css/card.css";

// Conteúdo da página
$conteudo = '
<h1>👥 Gestão de Funcionários</h1>
<p>Escolha uma das opções abaixo:</p>

<div class="cards-container">

    <div class="card">
      <h2>👥 Funcionários</h2>
      <p>Listar, adicionar e editar funcionários</p>
      <a href="funcionarios.php">Acessar</a>
    </div>

    <div class="card">
      <h2>📊 Gestão de Funcionários</h2>
      <p>Visão geral, exportações e ações em lote</p>
      <a href="funcionarios_gestao.php">Acessar</a>
    </div>

    <div class="card">
      <h2>🧩 Gestão de Cargos</h2>
      <p>Adicionar, editar e excluir cargos</p>
      <a href="funcionarios_gestao_cargos.php">Acessar</a>
    </div>

    <div class="card">
      <h2>🧭 Gestão de Setores</h2>
      <p>Adicionar, editar e excluir setores</p>
      <a href="funcionarios_gestao_setores.php">Acessar</a>
    </div>

</div>

<br>
<a class="btn btn-secondary" href="gestao.php">🔙 Voltar ao Painel de Gestão</a>
';

// Inclui o layout final
include ROOT_PATH . "/includes/layout.php";
