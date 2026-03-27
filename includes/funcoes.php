<?php

require_once __DIR__ . '/../dados/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

define('CAMINHO_BASE', '/projeto-loja');


/* ============================================================
   📷 FOTO DE PERFIL
============================================================ */
function caminhoFotoPerfil($conn, $idFuncionario) {
    // Foto padrão (URL correta)
    $caminho = '/imagens/perfil.png';

    if ($idFuncionario) {
        $stmt = $conn->prepare("SELECT foto FROM funcionarios WHERE id = ?");
        $stmt->bind_param("i", $idFuncionario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && !empty($res['foto'])) {
            $foto = $res['foto'];

            // Caminhos físicos
            $novo = __DIR__ . "/../uploads/perfil/" . $foto;
            $antigo = __DIR__ . "/../uploads/" . $foto;

            // Caminhos web
            $urlNovo = "/uploads/perfil/" . $foto;
            $urlAntigo = "/uploads/" . $foto;

            if (is_file($novo)) {
                $caminho = $urlNovo;
            } elseif (is_file($antigo)) {
                $caminho = $urlAntigo;
            }
        }
    }

    return $caminho;
}


/* ============================================================
   🔑 VERIFICA PERMISSÃO POR MÓDULO
============================================================ */
function temAcesso($conn, $cpf, $modulo) {

    // Acesso total somente para cargos EXATOS
    $cargoSessao = strtolower($_SESSION['cargo'] ?? '');
    if ($cargoSessao === 'ceo' || $cargoSessao === 'super') {
        return true;
    }

    if (!$cpf || !$modulo) {
        return false;
    }

    $sql = "
        SELECT acesso
        FROM acessos_usuarios
        WHERE cpf = ?
          AND modulo = ?
          AND acesso = 1
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("ss", $cpf, $modulo);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    return !empty($res);
}

/* ============================================================
   📊 VERIFICA SE O USUÁRIO TEM ALGUM MÓDULO DE GESTÃO
============================================================ */
function usuarioTemAcessoGestao($conn, $cpf) {

    if (!$cpf) return false;

    // CEO e SUPER têm acesso total
    $cargoSessao = strtolower($_SESSION['cargo'] ?? '');
    if (in_array($cargoSessao, ['ceo', 'super'])) {
        return true;
    }

    $sql = "
        SELECT COUNT(*) AS total
        FROM acessos_usuarios
        WHERE cpf = ?
          AND acesso = 1
          AND modulo LIKE 'gestao_%'
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    return $total > 0;
}

/* ============================================================
   🔤 NORMALIZA TEXTO
============================================================ */
function normalizar($texto) {
    $texto = strtolower(trim($texto));
    $texto = str_replace(
        ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'],
        ['a','a','a','a','e','e','i','o','o','o','u','c'],
        $texto
    );
    return preg_replace('/[^a-z0-9]/', '', $texto);
}

/* ============================================================
   🔤 NORMALIZA STATUS
============================================================ */
function normalizarStatus($status) {
    if ($status === null) return '';

    // Normaliza acentos e caixa
    $status = strtolower(trim($status));
    $status = str_replace(
        ['ç','ã','á','â','à','é','ê','í','ó','ô','ú'],
        ['c','a','a','a','a','e','e','i','o','o','u'],
        $status
    );

    // Mapeamento de status conhecidos
    $mapa = [
        'reaberto'               => ['reaberto', 'reaberto pelo usuario'],
        'aberto'                 => ['aberto', 'novo', 'iniciado'],
        'em andamento'           => ['em andamento', 'andamento', 'processando'],
        'aguardando avaliacao'   => ['aguardando avaliacao', 'aguardando avaliacao do solicitante', 'aguardando avaliacao do usuario'],
        'fechado'                => ['fechado', 'finalizado', 'concluido'],
        'encerrado'              => ['encerrado', 'encerrado automaticamente'],
        'cancelado'              => ['cancelado', 'cancelado pelo usuario', 'cancelado pelo setor']
    ];


    // Procura no mapa
    foreach ($mapa as $normalizado => $variacoes) {
        foreach ($variacoes as $v) {
            if (str_contains($status, $v)) {
                return $normalizado;
            }
        }
    }

    // Caso não encontre, retorna o texto limpo
    return $status;
}

/* ============================================================
   📊 CONTADORES GERAIS
============================================================ */
function contarFuncionarios($conn) {
    return $conn->query("
        SELECT COUNT(*) AS total 
        FROM funcionarios 
        WHERE desligamento IS NULL
    ")->fetch_assoc()['total'] ?? 0;
}

function contarItensInventario($conn) {
    return $conn->query("
        SELECT COUNT(*) AS total 
        FROM inventario 
        WHERE baixa IS NULL
    ")->fetch_assoc()['total'] ?? 0;
}

function contarLojas($conn) {
    return $conn->query("
        SELECT COUNT(*) AS total 
        FROM lojas 
        WHERE nome LIKE '%loja%' OR nome LIKE '%filial%'
    ")->fetch_assoc()['total'] ?? 0;
}

/* ============================================================
   📋 CHAMADOS
============================================================ */
function listarChamados($conn) {
    $chamados = [];
    $res = $conn->query("
        SELECT setor_destino, status, loja_origem, loja_destino 
        FROM chamados
    ");

    while ($row = $res->fetch_assoc()) {
        $chamados[] = $row;
    }

    return $chamados;
}

/* ============================================================
   📌 PENDÊNCIAS POR SETOR (corrigido para ID)
============================================================ */
function contarPendenciasPorSetor($chamados, $setorId) {

    $total = 0;
    $setorId = intval($setorId);

    $statusValidos = [
        'aberto',
        'em andamento',
        'reaberto',
        'aguardando avaliacao'
    ];

    foreach ($chamados as $c) {

        $status = normalizarStatus($c['status'] ?? '');

        if (
            intval($c['setor_destino']) === $setorId &&
            in_array($status, $statusValidos)
        ) {
            $total++;
        }
    }

    return $total;
}

/* ============================================================
   📌 PENDÊNCIAS DA LOJA (corrigido para ID)
============================================================ */
function contarChamadosLoja($chamados, $lojaId) {

    if (!$lojaId) return 0;

    $total = 0;
    $lojaId = intval($lojaId);

    $statusValidos = [
        'aberto',
        'em andamento',
        'reaberto',
        'aguardando avaliacao'
    ];

    foreach ($chamados as $c) {

        $status = normalizarStatus($c['status'] ?? '');

        if (
            intval($c['loja_destino']) === $lojaId &&
            in_array($status, $statusValidos)
        ) {
            $total++;
        }
    }

    return $total;
}

/* ============================================================
   📌 OBTÉM SETORES LIBERADOS (corrigido para ID + nome)
============================================================ */
/**
 * Retorna array de setores do usuário baseado em CPF.
 * - Normaliza CPF (apenas dígitos).
 * - Suporta módulos do tipo "setor_<id>" e "setor_nome".
 * - Evita duplicatas.
 * - Fallback: usa id_setor do funcionário quando não houver entradas em acessos_usuarios.
 *
 * Retorno: array de arrays ['id' => int, 'nome' => string]
 */
function usuarioTemSetores($conn, $cpf) {
    // normaliza CPF (apenas dígitos)
    $cpfLimpo = preg_replace('/\D+/', '', (string)$cpf);

    $setores = [];
    $seenIds = [];

    // 1) Buscar módulos em acessos_usuarios (módulos do tipo 'setor_%')
    $sql = "SELECT modulo 
            FROM acessos_usuarios 
            WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
              AND acesso = 1
              AND modulo LIKE 'setor_%'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $cpfLimpo);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $mod = trim($row['modulo'] ?? '');
            if ($mod === '') continue;

            // Caso o módulo seja do tipo setor_123 (id numérico), usar id direto
            if (preg_match('/^setor_(\d+)$/i', $mod, $m)) {
                $setorId = intval($m[1]);
                $stmt2 = $conn->prepare("SELECT id, nome FROM setores WHERE id = ? LIMIT 1");
                if ($stmt2) {
                    $stmt2->bind_param("i", $setorId);
                    $stmt2->execute();
                    $dadosSetor = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                    if ($dadosSetor && !isset($seenIds[intval($dadosSetor['id'])])) {
                        $seenIds[intval($dadosSetor['id'])] = true;
                        $setores[] = ['id' => intval($dadosSetor['id']), 'nome' => trim($dadosSetor['nome'])];
                    }
                }
                continue;
            }

            // Caso contrário, extrair o nome textual: setor_financeiro -> financeiro
            $nomeModulo = strtolower(preg_replace('/^setor_/', '', $mod));
            $nomeModulo = trim($nomeModulo);

            // Buscar setor por nome (comparação case-insensitive)
            $stmt2 = $conn->prepare("SELECT id, nome FROM setores WHERE LOWER(nome) = ? LIMIT 1");
            if ($stmt2) {
                $stmt2->bind_param("s", $nomeModulo);
                $stmt2->execute();
                $dadosSetor = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                if ($dadosSetor && !isset($seenIds[intval($dadosSetor['id'])])) {
                    $seenIds[intval($dadosSetor['id'])] = true;
                    $setores[] = ['id' => intval($dadosSetor['id']), 'nome' => trim($dadosSetor['nome'])];
                }
            }
        }

        $stmt->close();
    }

    // 2) Se não houver setores via acessos_usuarios, usar fallback: setor do funcionário (id_setor)
    if (empty($setores)) {
        $sqlF = "
            SELECT s.id, s.nome
            FROM funcionarios f
            LEFT JOIN setores s ON f.id_setor = s.id
            WHERE REPLACE(REPLACE(REPLACE(f.cpf, '.', ''), '-', ''), ' ', '') = ?
            LIMIT 1
        ";
        $stmtF = $conn->prepare($sqlF);
        if ($stmtF) {
            $stmtF->bind_param("s", $cpfLimpo);
            $stmtF->execute();
            $rowF = $stmtF->get_result()->fetch_assoc();
            $stmtF->close();
            if ($rowF && !empty($rowF['id'])) {
                $setores[] = ['id' => intval($rowF['id']), 'nome' => trim($rowF['nome'])];
            }
        }
    }

    return $setores;
}

/* ============================================================
   📌 CONTA A QUANTIDADE DE TAREFAS DO USUÁRIO
============================================================ */
function aguardando_avaliacao($conn, $idUsuario, $setorUsuario, $lojaUsuario) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM tarefas_plano
        WHERE status = 'aguardando_avaliacao'
          AND (
                (responsavel_tipo = 'usuario' AND responsavel_id = ?)
             OR (responsavel_tipo = 'setor'   AND responsavel_id = ?)
             OR (responsavel_tipo = 'loja'    AND responsavel_id = ?)
          )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $idUsuario, $setorUsuario, $lojaUsuario);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();
    return intval($res['total'] ?? 0);
}


/* ============================================================
   📌 CONTAR TAREFAS PENDENES
============================================================ */

function contarTarefasPendentes($conn, $idFuncionario) {

    $sql = "
        SELECT COUNT(DISTINCT t.id) AS total
        FROM tarefas_plano t
        WHERE t.status NOT IN ('concluida','avaliada','aguardando_avaliacao')
          AND t.responsavel_tipo = 'funcionario'
          AND t.responsavel_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idFuncionario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return intval($row['total']);
}



/* ============================================================
   📌 SISTEMA DE MENSAGENS 'ALERTAS'
============================================================ */
function setFlash($tipo, $mensagem) {
    $_SESSION['flash'] = [
        'tipo' => $tipo,   // success, error, warning, info
        'mensagem' => $mensagem
    ];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/* ============================================================
   🔗 função para noramlizar as datas
============================================================ */

function formatarData($data) {
    if (!$data || $data === '0000-00-00') return '—';
    return date('d-m-Y', strtotime($data));
}

/* ============================================================
   RESOLVER RESPONSÁVEL — FUNÇÃO GLOBAL
============================================================ */

function nomeFuncionarioCached($conn, $id) {
    static $cache = [];
    if (!$id) return '-';
    if (isset($cache[$id])) return $cache[$id];

    $stmt = $conn->prepare("SELECT nome FROM funcionarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $nome = $stmt->get_result()->fetch_assoc()['nome'] ?? '-';
    $stmt->close();

    return $cache[$id] = $nome;
}

function nomeSetorCached($conn, $id) {
    static $cache = [];
    if (!$id) return '-';
    if (isset($cache[$id])) return $cache[$id];

    $stmt = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $nome = $stmt->get_result()->fetch_assoc()['nome'] ?? '-';
    $stmt->close();

    return $cache[$id] = $nome;
}

function nomeLojaCached($conn, $id) {
    static $cache = [];
    if (!$id) return '-';
    if (isset($cache[$id])) return $cache[$id];

    $stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $nome = $stmt->get_result()->fetch_assoc()['nome'] ?? '-';
    $stmt->close();

    return $cache[$id] = $nome;
}

function resolverResponsavel($conn, $t) {
    $tipo = strtolower($t['responsavel_tipo'] ?? $t['tipo_responsavel'] ?? '');
    $id   = intval($t['responsavel_id'] ?? 0);

    return match ($tipo) {
        'funcionario', 'usuario' => nomeFuncionarioCached($conn, $id),
        'setor' => nomeSetorCached($conn, $id),
        'loja' => nomeLojaCached($conn, $id),
        default => '-'
    };
}


//=======================================
//  CALCULAR PRAZO TAREFA
// ======================================

function calcularPrazoClasse($dataLimite) {
    if (!$dataLimite) return ['label' => '-', 'class' => 'prazo-encerrado'];

    $hoje = strtotime(date('Y-m-d'));
    $limite = strtotime($dataLimite);

    $dias = ceil(($limite - $hoje) / 86400);

    if ($dias <= 0) {
        return ['label' => 'Prazo encerrado', 'class' => 'prazo-encerrado'];
    }

    if ($dias === 1) {
        $label = "Falta 1 dia";
    } else {
        $label = "Faltam $dias dias";
    }

    if ($dias > 10) {
        $class = "prazo-verde";
    } elseif ($dias > 5) {
        $class = "prazo-amarelo";
    } else {
        $class = "prazo-vermelho";
    }

    return ['label' => $label, 'class' => $class];
}

// ===================================
// Formatar status tarefa
// ===================================

function formatarStatusTarefa($status) {
    $mapa = [
        'pendente'              => 'Pendente',
        'em_andamento'          => 'Em Andamento',
        'aguardando_avaliacao'  => 'Aguardando Avaliação',
        'concluida'             => 'Concluída',
        'avaliada'              => 'Avaliada',
        'reaberta'              => 'Reaberta',
        'cancelada'             => 'Cancelada'
    ];

    return $mapa[$status] ?? ucfirst(str_replace('_', ' ', $status));
}


// ====================================
// Registro de resposta de tarefas
// ++++++++++++++++++++++++++++++++

function registrarInteracaoTarefa(mysqli $conn, int $id_tarefa, ?int $usuario_id, string $tipo, string $mensagem) {
    $sql = "INSERT INTO respostas_tarefas (id_tarefa, usuario_id, tipo, mensagem)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $id_tarefa, $usuario_id, $tipo, $mensagem);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// Contar pendências do trilho (para o motoboy)
// ============================================

function contarTrilhoPendentes(mysqli $conn): int {
    $sql = "
        SELECT COUNT(*) AS total
        FROM chamados_trilho
        WHERE status = 'faturado'
    ";

    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    return intval($row['total']);
}

// ============================================
// Funcao gerar titulo trilho
// ============================================

function gerarTituloTrilho($itens) {
    if (empty($itens)) {
        return "Itens diversos";
    }

    $primeiro = $itens[0]['descricao'] ?? "Item";

    $total = count($itens);

    if ($total === 1) {
        return $primeiro;
    }

    return $primeiro . " + " . ($total - 1) . " itens";
}
