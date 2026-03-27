<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$lojaId = intval($_POST['loja_id'] ?? 0);
if ($lojaId <= 0) die("Loja inválida.");

$validade = $_POST['validade'] ?? null;
$senha    = trim($_POST['senha'] ?? '');

$stmtCert = $conn->prepare("
    SELECT validade, arquivo, TRIM(COALESCE(senha, '')) AS senha
    FROM lojas_certificados
    WHERE loja_id = ?
    LIMIT 1
");
$stmtCert->bind_param("i", $lojaId);
$stmtCert->execute();
$certificado = $stmtCert->get_result()->fetch_assoc();

$arquivo = $certificado['arquivo'] ?? null;

// Upload
if (!empty($_FILES['arquivo']['name'])) {
    $pastaBase = "../uploads/certificados/";
    if (!is_dir($pastaBase)) mkdir($pastaBase, 0777, true);

    $nomeArquivo = uniqid("cert_") . "_" . basename($_FILES['arquivo']['name']);
    $caminhoRel  = "uploads/certificados/" . $nomeArquivo;
    $caminhoAbs  = $pastaBase . $nomeArquivo;

    if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoAbs)) {
        $arquivo = $caminhoRel;
    }
}

$conn->begin_transaction();

try {
    $stmtCheck = $conn->prepare("SELECT 1 FROM lojas_certificados WHERE loja_id = ? LIMIT 1");
    $stmtCheck->bind_param("i", $lojaId);
    $stmtCheck->execute();
    $existe = $stmtCheck->get_result()->fetch_column();

    if ($existe) {
        if ($arquivo !== null) {
            $stmtUpd = $conn->prepare("
                UPDATE lojas_certificados
                SET validade = ?, arquivo = ?, senha = ?
                WHERE loja_id = ?
            ");
            $stmtUpd->bind_param("sssi", $validade, $arquivo, $senha, $lojaId);
        } else {
            $stmtUpd = $conn->prepare("
                UPDATE lojas_certificados
                SET validade = ?, senha = ?
                WHERE loja_id = ?
            ");
            $stmtUpd->bind_param("ssi", $validade, $senha, $lojaId);
        }
        $stmtUpd->execute();
    } else {
        $stmtIns = $conn->prepare("
            INSERT INTO lojas_certificados (loja_id, validade, arquivo, senha)
            VALUES (?, ?, ?, ?)
        ");
        $stmtIns->bind_param("isss", $lojaId, $validade, $arquivo, $senha);
        $stmtIns->execute();
    }

    $conn->commit();
    header("Location: loja.php?id=" . $lojaId . "&aba=certificado");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Erro: " . $e->getMessage());
}
