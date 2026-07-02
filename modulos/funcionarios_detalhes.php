<?php
session_start();
require_once '../includes/funcoes.php';
require_once '../dados/conexao.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<p>❌ Funcionário inválido.</p>";
    exit;
}

// ===============================
// BUSCAR DADOS DO FUNCIONÁRIO
// ===============================
$sql = "
    SELECT f.*, 
           l.nome AS nome_loja,
           c.nome_cargo AS nome_cargo,
           s.nome AS nome_setor
    FROM funcionarios f
    LEFT JOIN lojas l ON f.loja_id = l.id
    LEFT JOIN cargos c ON f.cargo_id = c.id
    LEFT JOIN setores s ON f.id_setor = s.id
    WHERE f.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>❌ Funcionário não encontrado.</p>";
    exit;
}

$f = $result->fetch_assoc();

// ===============================
// BUSCAR FUNÇÃO SECUNDÁRIA
// ===============================
$sqlFuncao = "
    SELECT fs.nome 
    FROM funcionario_funcoes_secundarias ffs
    JOIN funcoes_secundarias fs ON fs.id = ffs.funcao_secundaria_id
    WHERE ffs.funcionario_id = ?
    LIMIT 1
";

$stmtFunc = $conn->prepare($sqlFuncao);
$stmtFunc->bind_param("i", $id);
$stmtFunc->execute();
$resFunc = $stmtFunc->get_result();

$funcaoSecundaria = $resFunc->num_rows > 0
    ? $resFunc->fetch_assoc()['nome']
    : "Nenhuma";

// ===============================
// FORMATAR NOME REDUZIDO
// ===============================
$partes = explode(' ', trim($f['nome']));
$primeiro = $partes[0] ?? '';
$ultimo   = $partes[count($partes)-1] ?? '';
$nomeReduzido = $primeiro . ' ' . $ultimo;

// ===============================
// FORMATAR DATAS
// ===============================
$contratacao = !empty($f['contratacao']) ? date('d/m/Y', strtotime($f['contratacao'])) : '—';
$nascimento  = !empty($f['nascimento'])  ? date('d/m/Y', strtotime($f['nascimento']))  : '—';

// ===============================
// TEMPO DE EMPRESA
// ===============================
function tempoDeEmpresaLocal($data) {
    if (!$data) return '—';
    $hoje = new DateTime();
    $inicio = new DateTime($data);
    $intervalo = $inicio->diff($hoje);
    $anos = $intervalo->y;
    $meses = $intervalo->m;
    $texto = '';
    if ($anos > 0) $texto .= $anos . ' ano' . ($anos > 1 ? 's' : '');
    if ($meses > 0) {
        if ($texto) $texto .= ' e ';
        $texto .= $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }
    return $texto ?: 'Menos de 1 mês';
}

$tempoEmpresa = tempoDeEmpresaLocal($f['contratacao']);
?>

<style>
.modal-detalhes {
    padding: 10px 5px;
    font-family: 'Segoe UI', sans-serif;
}

.modal-detalhes h3 {
    color: #1E513D;
    margin-bottom: 15px;
    font-size: 1.6em;
    text-align: center;
}

.detalhes-box {
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
    font-size: 1.05em;
}

.detalhes-box strong {
    color: #1E513D;
}

.btn-acoes {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 18px;
}

.btn-editar {
    background: #1E513D;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: bold;
}

.btn-inativar {
    background: #b30000;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: bold;
}
</style>

<div class="modal-detalhes">

    <h3><?= htmlspecialchars($nomeReduzido) ?></h3>

    <div class="detalhes-box"><strong>Código Vetor:</strong> <?= $f['codigo'] ?></div>
    <div class="detalhes-box"><strong>CC:</strong> <?= $f['cc'] ?></div>
    <div class="detalhes-box"><strong>Nome completo:</strong> <?= $f['nome'] ?></div>
    <div class="detalhes-box"><strong>CPF:</strong> <?= $f['cpf'] ?></div>

    <div class="detalhes-box"><strong>Cargo:</strong> <?= $f['nome_cargo'] ?></div>
    <div class="detalhes-box"><strong>Setor:</strong> <?= $f['nome_setor'] ?></div>
    <div class="detalhes-box"><strong>Função Secundária:</strong> <?= $funcaoSecundaria ?></div>

    <div class="detalhes-box"><strong>Loja:</strong> <?= $f['nome_loja'] ?></div>
    <div class="detalhes-box"><strong>Endereço:</strong> <?= $f['endereco'] ?></div>
    <div class="detalhes-box"><strong>Telefone:</strong> <?= $f['telefone'] ?></div>
    <div class="detalhes-box"><strong>Email:</strong> <?= $f['email'] ?></div>

    <div class="detalhes-box"><strong>Data de contratação:</strong> <?= $contratacao ?></div>
    <div class="detalhes-box"><strong>Tempo de empresa:</strong> <?= $tempoEmpresa ?></div>
    <div class="detalhes-box"><strong>Nascimento:</strong> <?= $nascimento ?></div>

    <div class="btn-acoes">

        <div class="btn-acoes">

            <a class="btn-editar"
            href="funcionarios_editar.php?loja=<?= $f['loja_id'] ?>&id=<?= $f['id'] ?>">
            ✏️ Editar
            </a>

            <a class="btn-inativar"
            href="funcionarios_inativar.php?loja=<?= $f['loja_id'] ?>&id=<?= $f['id'] ?>">
            🗑️ Inativar
            </a>

        </div>


</div>
