<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = '***REMOVED***';
    $mail->SMTPAuth   = true;
    $mail->Username   = '***REMOVED***';
    $mail->Password   = '***REMOVED***'; // <<< ALTERAR
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('***REMOVED***', 'Teste SMTP');
    $mail->addAddress('***REMOVED***'); // Envia para você mesmo

    $mail->isHTML(true);
    $mail->Subject = 'Teste de SMTP - SIGSFE';
    $mail->Body    = 'Se você recebeu este e-mail, o SMTP está funcionando!';

    $mail->send();
    echo "E-mail enviado com sucesso!";
} catch (Exception $e) {
    echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
}
