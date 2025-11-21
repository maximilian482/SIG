<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar(); // Inicializa a conexão com o banco

// Verifica login
if (!isset($_SESSION['id_funcionario']) || empty($_SESSION['id_funcionario'])) {
  $_SESSION['erro_postagem'] = 'Sessão expirada. Faça login novamente.';
  header('Location: ../login.php');
  exit;
}

$idFuncionario = $_SESSION['id_funcionario'];
$conteudo = trim($_POST['conteudo'] ?? '');
$limiteConteudo = 50000; // Limite de 50 mil caracteres

// Validação do conteúdo
if (strlen($conteudo) > $limiteConteudo) {
  $_SESSION['erro_postagem'] = 'Conteúdo muito longo. Limite de 50 mil caracteres.';
  header('Location: ../index.php');
  exit;
}

$textoLimpo = strip_tags($conteudo);
if (empty($conteudo) || $textoLimpo === '') {
  $_SESSION['erro_postagem'] = 'Mensagem vazia ou inválida. Verifique o conteúdo antes de publicar.';
  header('Location: ../index.php');
  exit;
}

// Verifica imagens embutidas via base64 no conteúdo
if (preg_match_all('/<img[^>]+src="data:image\/[^;]+;base64,[^"]+"/i', $conteudo, $matches)) {
  foreach ($matches[0] as $imgTag) {
    preg_match('/base64,([^"]+)/', $imgTag, $base64Match);
    $base64 = $base64Match[1] ?? '';
    $tamanhoBytes = (int)(strlen($base64) * 3 / 4);
    if ($tamanhoBytes > 2 * 1024 * 1024) {
      // Remove a imagem embutida do conteúdo
      $conteudo = str_replace($imgTag, '', $conteudo);
    }
  }
}

// Insere a postagem no banco (sem imagem)
$stmt = $conn->prepare("
  INSERT INTO postagens (funcionario_id, conteudo, imagem, data_postagem, visivel)
  VALUES (?, ?, NULL, NOW(), 1)
");
$stmt->bind_param("is", $idFuncionario, $conteudo);
$stmt->execute();

// Redireciona após postagem
$_SESSION['sucesso_postagem'] = 'Postagem publicada com sucesso!';
header('Location: ../index.php');
exit;
?>
