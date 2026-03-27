<?php
require_once 'dados/conexao.php';
$conn = conectar();

// Seleciona todos os funcionários
$result = $conn->query("SELECT id, cpf, senha FROM funcionarios");

$atualizados = 0;
while ($row = $result->fetch_assoc()) {
    $id    = (int)$row['id'];
    $cpf   = preg_replace('/\D+/', '', $row['cpf'] ?? '');
    $senha = trim($row['senha'] ?? '');

    // Se a senha já está criptografada (prefixo típico do password_hash), pula
    if (strlen($senha) > 20 && strpos($senha, '$2y$') === 0) {
        continue;
    }

    // Se a senha em texto puro corresponde aos 6 primeiros dígitos do CPF
    if (!empty($cpf) && substr($cpf, 0, 6) === $senha) {
        $novoHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmtUpd = $conn->prepare("UPDATE funcionarios SET senha = ? WHERE id = ?");
        $stmtUpd->bind_param("si", $novoHash, $id);
        if ($stmtUpd->execute()) {
            $atualizados++;
        }
    }
}

echo "✅ Senhas atualizadas: $atualizados\n";
?>
