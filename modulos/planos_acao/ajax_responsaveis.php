<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

// validar tipo
$tipo = trim((string)($_GET['tipo'] ?? ''));
if (!in_array($tipo, ['funcionario','setor','loja'], true)) {
    echo json_encode([]);
    exit;
}

$out = [];

try {
    if ($tipo === 'funcionario') {
        // opcional: filtrar por competencia via ?competencia=nome
        $competencia = trim((string)($_GET['competencia'] ?? ''));

        if ($competencia !== '') {
            // verifique se a tabela funcionario_competencias existe no seu schema
            $sql = "
                SELECT f.id, f.nome
                FROM funcionarios f
                INNER JOIN funcionario_competencias fc ON fc.funcionario_id = f.id
                WHERE fc.competencia = ?
                  AND COALESCE(f.desligamento,0) = 0
                ORDER BY f.nome
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $competencia);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) $out[] = ['id' => intval($r['id']), 'nome' => $r['nome']];
                $stmt->close();
            } else {
                error_log("ajax_responsaveis prepare funcionario+competencia failed: " . $conn->error);
            }
        } else {
            $sql = "SELECT id, nome FROM funcionarios WHERE COALESCE(desligamento,0) = 0 ORDER BY nome";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) $out[] = ['id' => intval($r['id']), 'nome' => $r['nome']];
                $stmt->close();
            } else {
                error_log("ajax_responsaveis prepare funcionarios failed: " . $conn->error);
            }
        }
    } elseif ($tipo === 'setor') {
        $sql = "SELECT id, nome FROM setores ORDER BY nome";
        $res = $conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) $out[] = ['id' => intval($r['id']), 'nome' => $r['nome']];
        } else {
            error_log("ajax_responsaveis setores query failed: " . $conn->error);
        }
    } elseif ($tipo === 'loja') {
        $sql = "SELECT id, nome FROM lojas ORDER BY nome";
        $res = $conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) $out[] = ['id' => intval($r['id']), 'nome' => $r['nome']];
        } else {
            error_log("ajax_responsaveis lojas query failed: " . $conn->error);
        }
    }
} catch (Throwable $e) {
    error_log("ajax_responsaveis exception: " . $e->getMessage());
}

// garantir saída consistente
echo json_encode($out);
