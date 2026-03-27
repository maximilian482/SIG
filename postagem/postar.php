<?php
session_start();
require_once '../includes/funcoes.php';
require '../vendor/autoload.php'; // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = conectar();

// Verifica login
if (!isset($_SESSION['id_funcionario']) || empty($_SESSION['id_funcionario'])) {
  $_SESSION['erro_postagem'] = 'Sessão expirada. Faça login novamente.';
  header('Location: ../login.php');
  exit;
}

$idFuncionario = $_SESSION['id_funcionario'];
$conteudo = trim($_POST['conteudo'] ?? '');
$limiteConteudo = 50000;

// Validação do conteúdo
if (strlen($conteudo) > $limiteConteudo) {
  $_SESSION['erro_postagem'] = 'Conteúdo muito longo. Limite de 50 mil caracteres.';
  header('Location: ../index.php');
  exit;
}

$textoLimpo = strip_tags($conteudo);
if (empty($conteudo) || $textoLimpo === '') {
  $_SESSION['erro_postagem'] = 'Mensagem vazia ou inválida.';
  header('Location: ../index.php');
  exit;
}

// Remove imagens base64 grandes
if (preg_match_all('/<img[^>]+src="data:image\/[^;]+;base64,[^"]+"/i', $conteudo, $matches)) {
  foreach ($matches[0] as $imgTag) {
    preg_match('/base64,([^"]+)/', $imgTag, $base64Match);
    $base64 = $base64Match[1] ?? '';
    $tamanhoBytes = (int)(strlen($base64) * 3 / 4);
    if ($tamanhoBytes > 2 * 1024 * 1024) {
      $conteudo = str_replace($imgTag, '', $conteudo);
    }
  }
}

// Insere a postagem
$stmt = $conn->prepare("
  INSERT INTO postagens (funcionario_id, conteudo, imagem, data_postagem, visivel)
  VALUES (?, ?, NULL, NOW(), 1)
");
$stmt->bind_param("is", $idFuncionario, $conteudo);
$stmt->execute();

// Se salvou, envia e-mail
if ($stmt->affected_rows > 0) {

  // Nome do autor
  $resAutor = $conn->query("SELECT nome FROM funcionarios WHERE id = $idFuncionario");
  $nomeAutor = ($resAutor && $row = $resAutor->fetch_assoc()) ? $row['nome'] : 'Colaborador';

  // Lista de e-mails (todos ativos)
  $sqlEmails = "SELECT email FROM funcionarios WHERE desligamento IS NULL AND email <> ''";
  $resEmails = $conn->query($sqlEmails);

  // LOG: quantidade de e-mails encontrados
  error_log("=== ENVIO DE EMAIL DO FEED ===");
  error_log("Emails encontrados: " . $resEmails->num_rows);

  // Configura PHPMailer
  $mail = new PHPMailer(true);

  try {
    $mail->SMTPDebug = 2; // Log detalhado
    $mail->Debugoutput = 'error_log';

    $mail->isSMTP();
    $mail->Host       = '***REMOVED***';
    $mail->SMTPAuth   = true;
    $mail->Username   = '***REMOVED***';
    $mail->Password   = '***REMOVED***';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    error_log("SMTP inicializado com sucesso.");

    $mail->setFrom('***REMOVED***', 'Sistema Souza Farma');
    $mail->isHTML(true);
    $mail->Subject = "📢 Nova postagem no Mural Souza Farma";

    $mensagem = "
      <div style='font-family:Arial,sans-serif;'>
        <img src='https://sigsfe.com.br/assets/logo_empresa.jpg' style='max-width:180px;'><br><br>

        Olá!<br><br>
        Uma nova postagem foi publicada no Mural da Souza Farma.<br><br>

        <strong>Autor:</strong> {$nomeAutor}<br>
        <strong>Data:</strong> " . date('d/m/Y H:i') . "<br><br>

        Acesse o mural:<br>
        <a href='https://sigsfe.com.br/feed' style='color:#007bff;'>Clique aqui para abrir</a><br><br>

        <hr>

        <strong>IMPORTANTE:</strong><br>
        Para acessar o sistema, utilize seu <strong>e-mail cadastrado na Empresa</strong> e os 
        <strong>6 primeiros dígitos do seu CPF</strong> como senha padrão.<br><br>

        Caso já tenha alterado sua senha, continue usando a senha personalizada normalmente.<br><br>

        — Sistema Interno Souza Farma
      </div>
    ";

    $mail->Body = $mensagem;

    // Garante que você receba uma cópia
    $mail->addAddress('***REMOVED***');
    error_log("Cópia direta adicionada para o remetente.");

    // Adiciona todos os destinatários
    while ($row = $resEmails->fetch_assoc()) {
      $mail->addBCC($row['email']);
      error_log("Destinatário adicionado: " . $row['email']);
    }

    $mail->send();
    error_log("E-mail enviado com sucesso!");

  } catch (Exception $e) {
    error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
  }
}

$_SESSION['sucesso_postagem'] = 'Postagem publicada com sucesso!';
header('Location: ../index.php');
exit;
?>
