<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

error_log("SMTP_USER=" . $_ENV['SMTP_USER']);
error_log("SMTP_PASS=" . $_ENV['SMTP_PASS']);

$conn = conectar();

$usuarioId = $_SESSION['funcionario_id'] ?? 0;
if ($usuarioId <= 0) {
    die("Acesso restrito. Faça login novamente.");
}

// ===============================
// PROCESSAMENTO DO FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $destinatarioId = (int)($_POST['destinatario_id'] ?? 0);
    $conteudo       = trim($_POST['conteudo'] ?? '');
    $arquivo        = null;
    $destinatario   = 0;

    // Buscar dados do destinatário pelo ID
    if ($destinatarioId > 0) {
        $stmtBusca = $conn->prepare("SELECT id, email, cpf FROM funcionarios WHERE id = ? LIMIT 1");
        $stmtBusca->bind_param("i", $destinatarioId);
        $stmtBusca->execute();
        $resBusca = $stmtBusca->get_result()->fetch_assoc();
        $stmtBusca->close();

        if ($resBusca) {
            $destinatario      = (int)$resBusca['id'];
            $emailDestinatario = $resBusca['email'];
            $cpfDestinatario   = $resBusca['cpf'];
        }
    }

    // Upload de arquivo (opcional)
    if (!empty($_FILES['arquivo']['name'])) {
        $pasta = "../uploads/mensagens/";
        if (!is_dir($pasta)) mkdir($pasta, 0777, true);

        $nomeArquivo = time() . "_" . basename($_FILES['arquivo']['name']);
        $caminho = $pasta . $nomeArquivo;

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
            $arquivo = $caminho;
        }
    }

    // Salvar mensagem
    if ($destinatario > 0 && $conteudo !== '') {

        $stmt = $conn->prepare("
            INSERT INTO mensagens (remetente_id, destinatario_id, conteudo, lida, data, arquivo)
            VALUES (?, ?, ?, 0, NOW(), ?)
        ");
        $stmt->bind_param("iiss", $usuarioId, $destinatario, $conteudo, $arquivo);
        $stmt->execute();
        $stmt->close();

        // ===============================
        // ENVIO DE E-MAIL
        // ===============================
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = gethostbyname($_ENV['SMTP_HOST']); // força IPv4
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $_ENV['SMTP_PORT'];
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            $mail->AddEmbeddedImage(ROOT_PATH . '/assets/logo_empresa.jpg', 'logo_sfe');




            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ];


            $mail->setFrom($_ENV['SMTP_USER'], 'SIGSFE');
            $mail->addAddress($emailDestinatario);

            $mail->isHTML(false);
            $mail->Subject = "Você recebeu uma nova mensagem no SIGSFE";

            $textoAnexo = $arquivo ? 
                "A mensagem contém um anexo.\n\n" :
                "A mensagem não possui anexos.\n\n";

            $primeiros6 = substr(preg_replace('/\D/', '', $cpfDestinatario), 0, 6);

            $mail->isHTML(true);
$mail->Subject = "Você recebeu uma nova mensagem no SIGSFE";

$mail->Body = '
<div style="background:#f4f4f4; padding:30px; font-family:Arial, sans-serif; color:#333;">
    <div style="max-width:600px; margin:0 auto; background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.08);">

        <div style="text-align:center; margin-bottom:20px;">
            <img src="cid:logo_sfe" style="width:160px; margin-bottom:10px;">
            <h2 style="margin:0; font-size:20px; color:#2a7a3f;">Atacadão Souza Farma Express</h2>
        </div>

        <p style="font-size:16px;">Olá,</p>

        <p style="font-size:16px;">
            Você recebeu uma nova mensagem no <strong>SIGSFE</strong>.
        </p>

        <p style="font-size:16px; margin-top:25px;"><strong>Conteúdo da mensagem:</strong></p>
        <div style="background:#fafafa; padding:15px; border-left:4px solid #2a7a3f; border-radius:5px; font-size:15px;">
            '.nl2br($conteudo).'
        </div>

        <p style="font-size:15px; margin-top:20px;">'.$textoAnexo.'</p>

        <p style="font-size:15px;">
            Para visualizar a mensagem completa, acesse:<br>
            <a href="https://sigsfe.com.br" style="color:#2a7a3f; font-weight:bold;">https://sigsfe.com.br</a>
        </p>

        <div style="margin-top:25px; padding:15px; background:#eef8f0; border-radius:6px; font-size:15px;">
            <strong>Login:</strong> '.$emailDestinatario.'<br>
            <strong>Senha inicial:</strong> '.$primeiros6.'
        </div>

        <p style="margin-top:30px; font-size:16px;">
            Atenciosamente,<br>
            <strong>Atacadão Souza Farma Express</strong>
        </p>

    </div>
</div>
';

            $mail->send();

        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
        }

        $sucesso = true;

    } else {
        $erro = "Preencha todos os campos obrigatórios ou verifique se o destinatário existe.";
    }
}

// ===============================
// CONTEÚDO DA PÁGINA
// ===============================
ob_start();
?>

<link rel="stylesheet" href="/css/mensagem.css">

<div class="pagina-conteudo">
    <div class="card-mensagem">
        <h1>✉️ Enviar Mensagem</h1>

        <?php if (!empty($sucesso)): ?>
            <p class="msg-sucesso">Mensagem enviada com sucesso!</p>
        <?php elseif (!empty($erro)): ?>
            <p class="msg-erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

            <label>Destinatário:</label>
            <p class="aviso-lista">Selecione abaixo o funcionário que receberá a mensagem.</p>

            <select name="destinatario_id" id="destinatario_id" required>
                <option value="">-- Selecione um funcionário --</option>
                <?php
                $res = $conn->query("SELECT id, nome FROM funcionarios ORDER BY nome");
                while ($f = $res->fetch_assoc()) {
                    echo "<option value=\"{$f['id']}\">{$f['nome']}</option>";
                }
                ?>
            </select>

            <label>Mensagem:</label>
            <textarea name="conteudo" rows="5" required></textarea>

            <label>Anexo (opcional):</label>
            <input type="file" name="arquivo">

            <button type="submit">Enviar Mensagem</button>
        </form>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
$modais = "";
include ROOT_PATH . "/includes/layout.php";
