<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';
require_once ROOT_PATH . '/dados/conexao.php';
require_once ROOT_PATH . '/includes/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

$conn = conectar();

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo = "Importar Funcionários";
$cssExtra = "/css/importar_funcionarios.css";

// ===============================
// FUNÇÃO DE LOG
// ===============================
function log_import($mensagem) {
    $arquivo = ROOT_PATH . '/log_importacao.txt';
    $data = date('Y-m-d H:i:s');
    file_put_contents($arquivo, "[$data] $mensagem\n", FILE_APPEND);
}

// ===============================
// FUNÇÕES AUXILIARES
// ===============================
function normalizarCpf($cpfRaw) {
    $cpf = preg_replace('/\D+/', '', (string)$cpfRaw);
    return str_pad($cpf, 11, '0', STR_PAD_LEFT);
}

function normalizarData($str) {
    $str = trim((string)$str);
    if ($str === '') return null;

    if (is_numeric($str)) {
        $unix = ($str - 25569) * 86400;
        return gmdate("Y-m-d", $unix);
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $match)) {
        return "{$match[3]}-" . str_pad($match[2], 2, '0', STR_PAD_LEFT) . "-" . str_pad($match[1], 2, '0', STR_PAD_LEFT);
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return $str;

    return null;
}

function normalizarNome($nomeRaw) {
    return mb_convert_case(trim($nomeRaw), MB_CASE_TITLE, "UTF-8");
}

// ===============================
// PROCESSAMENTO DO XLSX
// ===============================
$relatorio = ['inseridos'=>0,'atualizados'=>0,'ignorados'=>0,'erros'=>0,'mensagens'=>[]];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {

    log_import("=== INÍCIO DA IMPORTAÇÃO ===");

    $conn->begin_transaction();

    try {

        // Ler XLSX
        $xlsx = SimpleXLSX::parse($_FILES['arquivo']['tmp_name']);

        if (!$xlsx) {
            log_import("ERRO ao abrir XLSX: " . SimpleXLSX::parseError());
            throw new Exception("Erro ao ler XLSX: " . SimpleXLSX::parseError());
        }

        // Identificar a aba HC
        $sheetNames = $xlsx->sheetNames();
        log_import("Abas encontradas: " . json_encode($sheetNames));

        $sheetIndex = array_search('HC', $sheetNames);

        if ($sheetIndex === false) {
            throw new Exception("A aba 'HC' não foi encontrada no arquivo.");
        }

        $rows = $xlsx->rows($sheetIndex);
        log_import("Total de linhas lidas na aba HC: " . count($rows));

        foreach ($rows as $index => $row) {

            log_import("Lendo linha $index: " . json_encode($row));

            if ($index === 0) {
                log_import("Cabeçalho ignorado.");
                continue;
            }

            // ===============================
            // MAPEAR COLUNAS DO EXCEL
            // ===============================
            $lojaNome   = trim($row[0] ?? '');
            $codigo     = trim($row[1] ?? '');
            $cpfRaw     = trim($row[2] ?? '');
            $nascimento = normalizarData($row[3] ?? '');
            $email      = trim($row[4] ?? '');
            $nome       = normalizarNome($row[5] ?? '');
            $admissao   = normalizarData($row[6] ?? '');
            $cargoNome  = trim($row[7] ?? '');
            $setorNome  = trim($row[8] ?? '');
            $funcaoSec  = trim($row[9] ?? '');

            $cpf = normalizarCpf($cpfRaw);

            log_import("Valores normalizados: codigo=$codigo, nome=$nome, cpf=$cpf, loja=$lojaNome, cargo=$cargoNome");

            if (!$nome || !$cpf || strlen($cpf) != 11) {
                log_import("IGNORADO: nome ou CPF inválido.");
                $relatorio['ignorados']++;
                continue;
            }

            // ===============================
            // BUSCAR IDs NO BANCO
            // ===============================

            // LOJA
            $stmt = $conn->prepare("SELECT id FROM lojas WHERE nome = ?");
            $stmt->bind_param("s", $lojaNome);
            $stmt->execute();
            $stmt->bind_result($lojaId);
            $stmt->fetch();
            $stmt->close();

            log_import("Loja encontrada: $lojaId");

            if (!$lojaId) {
                log_import("ERRO: Loja inválida ($lojaNome)");
                $relatorio['ignorados']++;
                continue;
            }

            // CARGO
            $stmt = $conn->prepare("SELECT id FROM cargos WHERE nome = ?");
            $stmt->bind_param("s", $cargoNome);
            $stmt->execute();
            $stmt->bind_result($cargoId);
            $stmt->fetch();
            $stmt->close();

            log_import("Cargo encontrado: $cargoId");

            if (!$cargoId) {
                log_import("ERRO: Cargo inválido ($cargoNome)");
                $relatorio['ignorados']++;
                continue;
            }

            // ===============================
            // INSERIR / ATUALIZAR
            // ===============================

            $senhaInicial = substr($cpf, 0, 6);

            $sql = "
                INSERT INTO funcionarios 
                (codigo, nome, cpf, cargo_id, loja_id, email, contratacao, nascimento, senha)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                AS new
                ON DUPLICATE KEY UPDATE
                    nome = new.nome,
                    cargo_id = new.cargo_id,
                    loja_id = new.loja_id,
                    email = new.email,
                    contratacao = new.contratacao,
                    nascimento = new.nascimento,
                    codigo = new.codigo
            ";

            log_import("SQL preparado.");

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sssiiisss",
                $codigo,
                $nome,
                $cpf,
                $cargoId,
                $lojaId,
                $email,
                $admissao,
                $nascimento,
                $senhaInicial
            );

            log_import("Executando INSERT para CPF $cpf");

            if (!$stmt->execute()) {
                log_import("ERRO MYSQL: " . $stmt->error);
                throw new Exception("Erro CPF {$cpf}: ".$stmt->error);
            }

            log_import("Linha $index executada com sucesso.");

            if ($conn->affected_rows === 1) $relatorio['inseridos']++;
            else $relatorio['atualizados']++;

            $stmt->close();
        }

        $conn->commit();
        log_import("COMMIT executado.");

    } catch (Exception $e) {
        $conn->rollback();
        log_import("ROLLBACK: " . $e->getMessage());
        $relatorio['erros']++;
        $relatorio['mensagens'][] = "Rollback executado: ".$e->getMessage();
    }

    log_import("=== FIM DA IMPORTAÇÃO ===");
}

// ===============================
// CONTEÚDO DA PÁGINA
// ===============================
ob_start();
?>



<h2>📥 Importar Funcionários</h2>

<form method="POST" enctype="multipart/form-data" class="form-importar">
    <input type="file" name="arquivo" accept=".xlsx" required>
    <button type="submit">Importar</button>
</form>

<div class="btn-voltar">
    <a href="funcionarios_gestao.php" class="btn btn-secondary">⬅ Voltar</a>
</div>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="relatorio relatorio-destaque">

        <h3>📊 Resultado da Importação</h3>

        <div class="cards-resumo">
            <div class="card-resumo inseridos">
                <span class="numero"><?= $relatorio['inseridos'] ?></span>
                <span class="label">Inseridos</span>
            </div>

            <div class="card-resumo atualizados">
                <span class="numero"><?= $relatorio['atualizados'] ?></span>
                <span class="label">Atualizados</span>
            </div>

            <div class="card-resumo ignorados">
                <span class="numero"><?= $relatorio['ignorados'] ?></span>
                <span class="label">Ignorados</span>
            </div>

            <div class="card-resumo erros">
                <span class="numero"><?= $relatorio['erros'] ?></span>
                <span class="label">Erros</span>
            </div>
        </div>

        <?php if (!empty($relatorio['mensagens'])): ?>
            <h4>📌 Detalhes</h4>
            <div class="lista-mensagens">
                <?php foreach ($relatorio['mensagens'] as $m): ?>
                    <div class="mensagem-item"><?= htmlspecialchars($m) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
