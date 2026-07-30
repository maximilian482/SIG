<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// CPF sempre limpo e padronizado
$cpfLogado = trim(preg_replace('/\D/', '', $_SESSION['cpf'] ?? ''));

// Verifica acesso pelo EDITAR ACESSOS
if (!temAcesso($conn, $cpfLogado, 'cartoes')) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não possui acesso ao módulo de Cartões Corporativos.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// ID do gasto
$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro inválido.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_utilizacao.php");
    exit;
}

// Buscar dados do gasto
$stmt = $conn->prepare("
    SELECT 
        g.id,
        g.codigo_cartao,
        g.nome_funcionario,
        g.id_setor,
        g.data_compra,
        g.competencia_mes,
        g.competencia_ano,
        g.descricao,
        g.parcelas,
        g.valor_parcela,
        g.comprovante,
        g.finalidade,
        g.centro_custo,
        g.tipo_lancamento,
        g.nota_fiscal,
        g.lancado_vetor,
        s.nome AS setor_nome
    FROM cartoes_gastos g
    LEFT JOIN setores s ON s.id = g.id_setor
    WHERE g.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$g = $stmt->get_result()->fetch_assoc();

if (!$g) {
    $_SESSION['flash'] = [
        'mensagem' => 'Gasto não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_utilizacao.php");
    exit;
}

// Meses para exibir competência
$meses = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
    7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];

$competencia = $meses[$g['competencia_mes']] . "/" . $g['competencia_ano'];

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes_utilizacao.css">

<div class="cartoes-modulo-gestor">

    <h1>🔍 Detalhes do Gasto</h1>
    <p class="subtitulo">Informações completas do lançamento para conferência e ajuste.</p>

    <a href="cartoes_utilizacao.php" class="btn-filtrar" style="margin-bottom:15px; display:inline-block;">
        ⬅ Voltar
    </a>

    <div class="detalhes-gasto">

        <h3>Informações gerais</h3>

        <table class="tabela-detalhes">
            <tr>
                <th>Cartão</th>
                <td><?= htmlspecialchars($g['codigo_cartao']) ?></td>
            </tr>
            <tr>
                <th>Funcionário</th>
                <td><?= htmlspecialchars($g['nome_funcionario']) ?></td>
            </tr>
            <tr>
                <th>Setor</th>
                <td><?= htmlspecialchars($g['setor_nome']) ?></td>
            </tr>
            <tr>
                <th>Data da compra</th>
                <td><?= date('d/m/Y', strtotime($g['data_compra'])) ?></td>
            </tr>
            <tr>
                <th>Competência</th>
                <td><?= $competencia ?></td>
            </tr>
            <tr>
                <th>Descrição</th>
                <td><?= htmlspecialchars($g['descricao']) ?></td>
            </tr>
            <tr>
                <th>Parcela</th>
                <td><?= (int)$g['parcelas'] ?></td>
            </tr>
            <tr>
                <th>Valor da parcela</th>
                <td>R$ <?= number_format($g['valor_parcela'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <th>Comprovante</th>
                <td>
                    <?php if (!empty($g['comprovante'])): ?>
                        <a href="/uploads/comprovantes/<?= htmlspecialchars($g['comprovante']) ?>" target="_blank">
                            📄 Abrir comprovante
                        </a>
                    <?php else: ?>
                        <span class="texto-muted">Não informado</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <hr>

        <h3>Informações financeiras</h3>

        <form method="POST" action="cartoes_gasto_detalhes_salvar.php">

            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">

            <div class="campo">
                <label>Finalidade</label>
                <select name="finalidade" class="form-control">
                    <option value="">Selecione...</option>
                    <?php
                    $finalidades = [
                        "Alimentação","Combustível","Hospedagem","Transporte","Material de escritório",
                        "Material de limpeza","Ferramentas","Equipamentos","Serviços","Reembolso",
                        "Manutenção","Treinamento","Viagem","Outros"
                    ];
                    foreach ($finalidades as $f):
                    ?>
                        <option value="<?= $f ?>" <?= ($g['finalidade'] === $f ? 'selected' : '') ?>>
                            <?= $f ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Centro de custo</label>
                <select name="centro_custo" class="form-control">
                    <option value="">Selecione...</option>
                    <?php
                    $centros = [
                        "Administrativo","Comercial","Operacional","Logística","Marketing","TI",
                        "Financeiro","RH","Diretoria","Projetos","Manutenção","Expansão"
                    ];
                    foreach ($centros as $c):
                    ?>
                        <option value="<?= $c ?>" <?= ($g['centro_custo'] === $c ? 'selected' : '') ?>>
                            <?= $c ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Tipo de lançamento</label>
                <select name="tipo_lancamento" class="form-control">
                    <option value="">Selecione...</option>
                    <?php
                    $tipos = ["Único","Parcelado","Reembolso","Adiantamento","Estorno"];
                    foreach ($tipos as $t):
                    ?>
                        <option value="<?= $t ?>" <?= ($g['tipo_lancamento'] === $t ? 'selected' : '') ?>>
                            <?= $t ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Nota fiscal digitalizada?</label>
                <select name="nota_fiscal" class="form-control">
                    <option value="">Selecione...</option>
                    <option value="Sim" <?= ($g['nota_fiscal'] === 'Sim' ? 'selected' : '') ?>>Sim</option>
                    <option value="Não" <?= ($g['nota_fiscal'] === 'Não' ? 'selected' : '') ?>>Não</option>
                </select>
            </div>

            <div class="campo">
                <label>Lançado no vetor?</label>
                <select name="lancado_vetor" class="form-control">
                    <option value="">Selecione...</option>
                    <option value="Sim" <?= ($g['lancado_vetor'] === 'Sim' ? 'selected' : '') ?>>Sim</option>
                    <option value="Não" <?= ($g['lancado_vetor'] === 'Não' ? 'selected' : '') ?>>Não</option>
                </select>
            </div>

            <button class="btn-filtrar" style="margin-top:15px;">
                💾 Salvar alterações
            </button>

        </form>

    </div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
