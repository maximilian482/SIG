<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo   = "Gestão de Lojas";
$cssExtra = "/css/loja.css";

// ===============================
// FUNÇÃO DE ALERTA DO CERTIFICADO
// ===============================
function alertaCertificado($dataValidade) {
    if (!$dataValidade) {
        return ['texto' => 'Não cadastrado', 'cor' => 'gray'];
    }

    $hoje = new DateTime();
    $validade = new DateTime($dataValidade);
    $intervalo = $hoje->diff($validade);
    $dias = (int)$intervalo->days;

    if ($validade < $hoje) {
        return [
            'texto' => "❌ Expirado há {$dias} dia" . ($dias > 1 ? 's' : ''),
            'cor'   => 'red'
        ];
    } elseif ($dias <= 30) {
        return [
            'texto' => "⚠️ Vence em {$dias} dia" . ($dias > 1 ? 's' : ''),
            'cor'   => 'orange'
        ];
    } else {
        return [
            'texto' => "⏳ Vence em {$dias} dia" . ($dias > 1 ? 's' : ''),
            'cor'   => 'green'
        ];
    }
}

// ===============================
// CONSULTA LOJAS
// ===============================
$lojas = [];
$stmt = $conn->prepare("
    SELECT
        l.id, l.nome, l.cnpj, l.meta,
        lc.validade AS certificado_validade,
        fg.nome AS nome_gerente, fg.telefone AS tel_gerente,
        fs.nome AS nome_subgerente, fs.telefone AS tel_subgerente
    FROM lojas l
        LEFT JOIN lojas_certificados lc ON lc.loja_id = l.id
        LEFT JOIN funcionarios fg ON l.gerente_id = fg.id AND fg.desligamento IS NULL
        LEFT JOIN funcionarios fs ON l.subgerente_id = fs.id AND fs.desligamento IS NULL
    WHERE LOWER(l.nome) NOT IN ('escritorio', 'escritório')
    ORDER BY l.nome
");

$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) {
    $lojas[] = $linha;
}

// ===============================
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>

<?php if (!empty($_SESSION['sucesso'])): ?>
<script>
    mostrarMensagem("<?= addslashes($_SESSION['sucesso']) ?>", "sucesso");
</script>
<?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['erros'])): ?>
<script>
    mostrarMensagem("<?= addslashes(implode(' | ', $_SESSION['erros'])) ?>", "erro");
</script>
<?php unset($_SESSION['erros']); ?>
<?php endif; ?>

<h2>🏪 Lojas cadastradas</h2>
<p>Visualize todas as unidades com informações importantes e acesse os detalhes completos.</p>

<div class="tabela-container">
    <table class="tabela">
        <thead>
            <tr>
                <th>Unidade</th>
                <th>CNPJ</th>
                <th>Responsável</th>
                <th>2º Responsável</th>
                <th>Certificado Digital</th>
                <th>Meta da Loja</th>
                <th>Detalhes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lojas as $loja): 
                $alerta = alertaCertificado($loja['certificado_validade']);
            ?>
            <tr>
                <td><?= htmlspecialchars($loja['nome']) ?></td>
                <td><?= htmlspecialchars($loja['cnpj']) ?></td>
                <td><?= htmlspecialchars($loja['nome_gerente'] ?? '—') ?></td>
                <td><?= htmlspecialchars($loja['nome_subgerente'] ?? '—') ?></td>

                <td style="color: <?= $alerta['cor'] ?>;">
                    <?= $alerta['texto'] ?>
                </td>

                <td>
                    <?= $loja['meta'] > 0 
                        ? "R$ " . number_format($loja['meta'], 2, ',', '.') 
                        : "—" ?>
                </td>

                <td>
                    <a class="btn-small" href="loja.php?id=<?= urlencode($loja['id']) ?>">🔍</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<br>

<div class="acoes-final">
    <a class="btn" href="/modulos/gestao.php">🏠 Voltar</a>
    <a class="btn" href="adicionar_loja.php">➕ Nova Loja</a>
</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
