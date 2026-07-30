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

/* ============================================================
   LIMPAR FILTROS
   ============================================================ */
if (isset($_GET['limpar']) && $_GET['limpar'] == 1) {
    unset($_SESSION['filtros_cartoes_utilizacao']);
    header("Location: cartoes_utilizacao.php");
    exit;
}

/* ============================================================
   SALVAR FILTROS NA SESSÃO
   ============================================================ */
if (!empty($_GET)) {
    $_SESSION['filtros_cartoes_utilizacao'] = $_GET;
}

$filtros = $_SESSION['filtros_cartoes_utilizacao'] ?? [];

$filtro_cartao = $filtros['cartao'] ?? '';
$filtro_func   = $filtros['func'] ?? '';
$filtro_data   = $filtros['data'] ?? '';
$filtro_mes    = $filtros['mes'] ?? '';
$filtro_ano    = $filtros['ano'] ?? '';
$filtro_ciclo  = $filtros['ciclo'] ?? '';
$filtro_status = $filtros['status'] ?? '';

/* ============================================================
   PAGINAÇÃO
   ============================================================ */
$pagina = intval($_GET['pagina'] ?? 1);
$limite = 20;
$offset = ($pagina - 1) * $limite;

/* ============================================================
   QUERY PRINCIPAL
   ============================================================ */
$sqlBase = "
    FROM cartoes_gastos g
    LEFT JOIN setores s ON s.id = g.id_setor
    WHERE 1
";

if ($filtro_cartao) $sqlBase .= " AND g.codigo_cartao = '$filtro_cartao' ";
if ($filtro_func)   $sqlBase .= " AND g.nome_funcionario LIKE '%$filtro_func%' ";
if ($filtro_data)   $sqlBase .= " AND g.data_compra = '$filtro_data' ";
if ($filtro_mes)    $sqlBase .= " AND g.competencia_mes = " . intval($filtro_mes);
if ($filtro_ano)    $sqlBase .= " AND g.competencia_ano = " . intval($filtro_ano);
if ($filtro_ciclo)  $sqlBase .= " AND g.id_ciclo = " . intval($filtro_ciclo);

if ($filtro_status === "conferido") {
    $sqlBase .= " AND (
        g.finalidade IS NOT NULL AND 
        g.centro_custo IS NOT NULL AND 
        g.tipo_lancamento IS NOT NULL AND 
        g.nota_fiscal IS NOT NULL AND
        g.lancado_vetor IS NOT NULL
    ) ";
}

if ($filtro_status === "pendente") {
    $sqlBase .= " AND (
        g.finalidade IS NULL OR 
        g.centro_custo IS NULL OR 
        g.tipo_lancamento IS NULL OR 
        g.nota_fiscal IS NULL OR
        g.lancado_vetor IS NULL
    ) ";
}

/* ============================================================
   CONTAGEM TOTAL PARA PAGINAÇÃO
   ============================================================ */
$sqlCount = "SELECT COUNT(*) AS total " . $sqlBase;
$totalRegistros = $conn->query($sqlCount)->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limite);

/* ============================================================
   BUSCA DOS REGISTROS
   ============================================================ */
$sql = "
    SELECT g.*, s.nome AS setor_nome, g.id_ciclo
    $sqlBase
    ORDER BY g.data_compra DESC
    LIMIT $limite OFFSET $offset
";

$gastosQuery = $conn->query($sql);
$gastos = [];

while ($g = $gastosQuery->fetch_assoc()) {
    $gastos[] = $g;
}

/* ============================================================
   SOMA TOTAL
   ============================================================ */
$totalGeral = 0;
foreach ($gastos as $g) {
    $totalGeral += floatval($g['valor_parcela']);
}

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes_utilizacao.css">

<div class="cartoes-modulo-gestor">

    <h1>💵 Utilizações / Faturas</h1>
    <p class="subtitulo">Registros de gastos realizados pelos colaboradores.</p>

    <!-- FILTROS -->
    <form class="filtros" method="GET">

        <input type="text" name="cartao" placeholder="Código do cartão" value="<?= $filtro_cartao ?>">
        <input type="text" name="func" placeholder="Nome do funcionário" value="<?= $filtro_func ?>">
        <input type="date" name="data" value="<?= $filtro_data ?>">
        <input type="number" name="ciclo" placeholder="Ciclo" value="<?= $filtro_ciclo ?>">

        <select name="status">
            <option value="">Status...</option>
            <option value="conferido" <?= ($filtro_status === "conferido" ? "selected" : "") ?>>Conferido</option>
            <option value="pendente" <?= ($filtro_status === "pendente" ? "selected" : "") ?>>Pendente</option>
        </select>

        <select name="mes">
            <option value="">Mês...</option>
            <?php
            $meses = [
                1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
            ];
            foreach ($meses as $num=>$nome):
            ?>
                <option value="<?= $num ?>" <?= ($filtro_mes == $num ? 'selected' : '') ?>>
                    <?= $nome ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="ano">
            <option value="">Ano...</option>
            <?php for ($i = 2024; $i <= 2035; $i++): ?>
                <option value="<?= $i ?>" <?= ($filtro_ano == $i ? 'selected' : '') ?>>
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select>

        <button class="btn-filtrar">Buscar</button>
        <a href="cartoes_utilizacao.php?limpar=1" class="btn-limpar">Limpar</a>

    </form>

    <!-- BOTÃO DE VOLTAR -->
    <a href="cartoes_mestre.php" class="btn-voltar">⬅ Voltar</a>

<?php if ($totalRegistros > 0): ?>
    <div class="total-gastos">
        <h3>💰 Total do filtro: 
            <span class="valor-total">
                R$ <?= number_format($totalGeral, 2, ',', '.') ?>
            </span>
        </h3>

        <h4 class="quantidade-itens">
            📦 Quantidade de itens: 
            <span class="valor-qtd"><?= $totalRegistros ?></span>
        </h4>
    </div>
<?php endif; ?>

    <!-- TABELA -->
    <div class="tabela-gastos">

        <table>
            <thead>
                <tr>
                    <th>Ciclo</th>
                    <th>Data</th>
                    <th>Competência</th>
                    <th>Cartão</th>
                    <th>Funcionário</th>
                    <th>Setor</th>
                    <th>Descrição</th>
                    <th>Parcela</th>
                    <th>Valor Parcela</th>
                    <th>Status</th>
                    <th>Comprovante</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($gastos as $g): ?>

                <?php
                    $conferido = (
                        $g['finalidade'] &&
                        $g['centro_custo'] &&
                        $g['tipo_lancamento'] &&
                        $g['nota_fiscal'] &&
                        $g['lancado_vetor']
                    );

                    $competenciaMes = $g['competencia_mes'] ?? null;
                    $competenciaAno = $g['competencia_ano'] ?? null;

                    if ($competenciaMes && $competenciaAno) {
                        $competencia = $meses[$competenciaMes] . "/" . $competenciaAno;
                    } else {
                        $competencia = "—";
                    }

                    $nomeParts = explode(" ", trim($g['nome_funcionario']));
                    $nomeReduzido = $nomeParts[0];
                ?>

                <tr>
                    <td><?= $g['id_ciclo'] ?></td>
                    <td><?= date('d/m', strtotime($g['data_compra'])) ?></td>
                    <td><?= $competencia ?></td>
                    <td><?= $g['codigo_cartao'] ?></td>
                    <td><?= $nomeReduzido ?></td>
                    <td><?= $g['setor_nome'] ?></td>
                    <td><?= $g['descricao'] ?></td>

                    <td><?= $g['parcelas'] ?></td>
                    <td>R$ <?= number_format($g['valor_parcela'], 2, ',', '.') ?></td>

                    <td>
                        <?php if ($conferido): ?>
                            <span class="status conferido">✔</span>
                        <?php else: ?>
                            <span class="status pendente">⏳</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <a href="/uploads/comprovantes/<?= $g['comprovante'] ?>" target="_blank">
                            📄
                        </a>
                    </td>

                    <td>
                        <a href="cartoes_gasto_detalhes.php?id=<?= $g['id'] ?>" class="btn-acao">
                            🔍
                        </a>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>

        </table>

    </div>

    <!-- PAGINAÇÃO -->
    <div class="paginacao">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a 
                href="?pagina=<?= $i ?>" 
                class="<?= ($i == $pagina ? 'pagina-ativa' : '') ?>"
            >
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>

</div>

<a href="cartoes_gastos_exportar.php?cartao=<?= $filtro_cartao ?>&func=<?= $filtro_func ?>&data=<?= $filtro_data ?>&mes=<?= $filtro_mes ?>&ano=<?= $filtro_ano ?>&ciclo=<?= $filtro_ciclo ?>&status=<?= $filtro_status ?>" 
   class="btn-exportar">📤 Exportar Excel</a>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
