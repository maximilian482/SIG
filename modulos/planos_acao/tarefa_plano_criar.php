<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$idPlano = intval($_GET['id_plano'] ?? 0);
if ($idPlano <= 0) {
    die('Plano inválido.');
}

// Carrega dados do plano
$sqlPlano = "SELECT * FROM planos_acao WHERE id = ?";
$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bind_param("i", $idPlano);
$stmtPlano->execute();
$resPlano = $stmtPlano->get_result();
$plano = $resPlano->fetch_assoc();
$stmtPlano->close();

if (!$plano) {
    die('Plano não encontrado.');
}

$erro = '';
$val = [
    'titulo'           => '',
    'descricao'        => '',
    'data_limite'      => $plano['data_fim'] ?? '',
    'responsavel_tipo' => '',
    'responsavel_id'   => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val['titulo']           = trim($_POST['titulo'] ?? '');
    $val['descricao']        = trim($_POST['descricao'] ?? '');
    $val['data_limite']      = trim($_POST['data_limite'] ?? '');
    $val['responsavel_tipo'] = trim($_POST['responsavel_tipo'] ?? '');
    $val['responsavel_id']   = intval($_POST['responsavel_id'] ?? 0);

    if ($val['titulo'] === '') {
        $erro = 'Informe o título da tarefa.';
    } elseif ($val['responsavel_tipo'] === '' || $val['responsavel_id'] <= 0) {
        $erro = 'Selecione o tipo de responsável e o responsável.';
    }

    $data_limite_db = null;
    if ($val['data_limite'] !== '') {
        $data_limite_db = $val['data_limite'];
    }

    if (!$erro) {
        $tipoFinal = $val['responsavel_tipo']; // 'funcionario', 'setor' ou 'loja'

        $sql = "INSERT INTO tarefas_plano
                (id_plano, id_modelo, titulo, descricao,
                 tipo_responsavel, responsavel_tipo, responsavel_id,
                 data_limite, prazo, status, criado_em, atualizado_em)
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, NULL, 'pendente', NOW(), NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("ERR prepare insert tarefa: " . $conn->error . " SQL: " . $sql);
            $erro = "Erro interno ao preparar o registro.";
        } else {
            $bind_ok = $stmt->bind_param(
                "issssis",
                $idPlano,
                $val['titulo'],
                $val['descricao'],
                $tipoFinal,              // tipo_responsavel
                $tipoFinal,              // responsavel_tipo
                $val['responsavel_id'],  // responsavel_id
                $data_limite_db          // data_limite
            );

            if (!$bind_ok) {
                error_log("ERR bind_param failed: " . $stmt->error);
                $erro = "Erro interno ao preparar os dados.";
            } else {
                if (!$stmt->execute()) {
                    error_log("ERR execute insert tarefa: " . $stmt->error);
                    $erro = "Erro ao salvar a tarefa: " . htmlspecialchars($stmt->error);
                } else {
                    $_SESSION['flash'] = [
                        'mensagem' => 'Tarefa criada com sucesso.',
                        'tipo' => 'success'
                    ];

                    header("Location: planos_acao_detalhes.php?id=" . $idPlano);
                    exit;
                }
            }
        }
    }
}

$titulo = "Criar Tarefa";

ob_start();
?>

<div class="container tarefa-criar">

    <h2>Criar Tarefa no Plano: <?= htmlspecialchars($plano['titulo'] ?? '') ?></h2>

    <?php if ($erro): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" id="formTarefa" novalidate>

        <label>Título *</label>
        <input type="text" name="titulo" required value="<?= htmlspecialchars($val['titulo']) ?>">

        <label>Descrição</label>
        <textarea name="descricao" rows="4"><?= htmlspecialchars($val['descricao']) ?></textarea>

        <label>Data limite</label>
        <input type="date" name="data_limite"
               min="<?= htmlspecialchars($plano['data_inicio']) ?>"
               max="<?= htmlspecialchars($plano['data_fim']) ?>"
               value="<?= htmlspecialchars($val['data_limite'] ?? '') ?>">
        <div class="small">
            Por padrão a data limite é o fim do prazo do plano; altere se necessário.
        </div>

        <label>Tipo de responsável</label>
        <select name="responsavel_tipo" id="responsavel_tipo">
            <option value="">— Selecionar —</option>
            <option value="funcionario" <?= ($val['responsavel_tipo'] === 'funcionario') ? 'selected' : '' ?>>Usuário (Funcionário)</option>
            <option value="setor" <?= ($val['responsavel_tipo'] === 'setor') ? 'selected' : '' ?>>Setor</option>
            <option value="loja" <?= ($val['responsavel_tipo'] === 'loja') ? 'selected' : '' ?>>Loja</option>
        </select>

        <label>Responsável</label>
        <select name="responsavel_id" id="responsavel_id">
            <option value="">Selecione</option>
        </select>

        <button id="btnSubmit">Criar Tarefa</button>
        <a href="planos_acao_detalhes.php?id=<?= $idPlano ?>" class="btn ghost">← Voltar</a>
    </form>

</div>

<?php
$conteudo = ob_get_clean();
$cssExtra = "/css/tarefa_plano_criar.css";
$scripts  = '<script src="/js/tarefa_plano_criar.js"></script>';

include ROOT_PATH . "/includes/layout.php";
