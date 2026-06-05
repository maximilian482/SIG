<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpf = $_SESSION['cpf'];

if (!temAcesso($conn, $cpf, 'auditoria_pp')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar Auditoria PP.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/avaliacoes_loja.css">
<link rel="stylesheet" href="/css/auditoria_pp.css">

<div class="botoes-avaliacoes">
    <a href="auditoria_pp.php" class="btn btn-cinza">⬅ Voltar</a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">📜 Histórico de Auditorias PP</h2>
        <p class="subtitulo-pagina">Veja as auditorias realizadas recentemente.</p>

        <div class="card-premium lista-avaliacoes-container">

            <table class="tabela-premium" id="tabela-auditorias">
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Nota Geral</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $sql = "
                    SELECT a.id, a.nota_geral, a.data_auditoria, l.nome AS loja
                    FROM auditoria_pp a
                    JOIN lojas l ON l.id = a.loja_id
                    ORDER BY a.id DESC
                    LIMIT 50
                ";

                $res = $conn->query($sql);

                if ($res->num_rows === 0):
                ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">Nenhuma auditoria encontrada.</td>
                    </tr>
                <?php
                else:
                    while ($a = $res->fetch_assoc()):

                        // Converter nota 0/5/10 → 0/50/100
                        $nota = floatval($a['nota_geral']);
                        if ($nota == 10) $nota = 100;
                        elseif ($nota == 5) $nota = 50;
                        else $nota = 0;

                        // Classe visual
                        $classeNota = "nota-ruim";
                        if ($nota >= 75) $classeNota = "nota-bom";
                        else if ($nota >= 40) $classeNota = "nota-parcial";
                ?>

                    <!-- LINHA PRINCIPAL -->
                    <tr>
                        <td><?= htmlspecialchars($a['loja']) ?></td>
                        <td class="<?= $classeNota ?>"><?= number_format($nota, 2, ',', '.') ?>%</td>
                        <td><?= date("d/m/Y", strtotime($a['data_auditoria'])) ?></td>
                        <td class="col-acoes">

                            <!-- USANDO OS BOTÕES ORIGINAIS -->
                            <a class="btn-icone btn-detalhes" data-id="<?= $a['id'] ?>" title="Ver detalhes">
                                
                            </a>

                            <a class="btn-icone btn-excluir" data-id="<?= $a['id'] ?>" title="Excluir">
                                
                            </a>

                        </td>
                    </tr>

                    <!-- LINHA DE DETALHES (OBRIGATÓRIA PARA O JS FUNCIONAR) -->
                    <tr class="linha-detalhes oculto">
                        <td colspan="4">
                            <div class="detalhes-conteudo"></div>
                        </td>
                    </tr>

                <?php
                    endwhile;
                endif;
                ?>

                </tbody>
            </table>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/auditoria_pp_grafico.js?v=<?= time() ?>"></script>
<script src="/js/auditoria_pp_detalhes.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
