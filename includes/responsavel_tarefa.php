<?php
function obterResponsavelTarefa(mysqli $conn, array $tarefa): string
{
    if (!$tarefa['responsavel_tipo'] || !$tarefa['responsavel_id']) {
        return '—';
    }

    switch ($tarefa['responsavel_tipo']) {

        case 'funcionario':
            $sql = "SELECT nome FROM funcionarios WHERE id = ?";
            break;

        case 'setor':
            $sql = "SELECT nome FROM setores WHERE id = ?";
            break;

        case 'loja':
            $sql = "SELECT nome FROM lojas WHERE id = ?";
            break;

        default:
            return '—';
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tarefa['responsavel_id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    return $res['nome'] ?? '—';
}
