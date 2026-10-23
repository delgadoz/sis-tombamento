<?php
session_start();
require_once 'conexao.php';
require_once 'log.php';
require_once 'includes/form-flash.php';

// Campos do formulário que podem ser devolvidos ao usuário em caso de erro.
// id_bem e numero_tombamento são usados para refazer a busca e conferir se os
// dados guardados pertencem ao mesmo bem antes de restaurá-los.
const CAMPOS_FORM_ALTERACAO = [
    'id_bem', 'numero_tombamento',
    'descricao', 'marca', 'numero_empenho', 'data_aquisicao', 'numero_nota',
    'setor_origem', 'setor', 'subsetor', 'unidade', 'grupo_id',
    'estado', 'tipo_id', 'valor',
];

/**
 * Volta para o formulário de alteração com a mensagem de erro, preservando
 * os dados digitados. Encerra a execução.
 */
function voltarParaAlteracao(string $mensagem): void
{
    voltarComErro($mensagem, 'alterar-bem-movel', 'alteracao_bem', CAMPOS_FORM_ALTERACAO);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-bem-movel');
    exit;
}

if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario_id'])) {
    header('Location: login');
    exit;
}

$token_recebido = campoPost('csrf_token');
$token_sessao   = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !is_string($token_sessao) || !hash_equals($token_sessao, $token_recebido)) {
    // Sem preservar os dados: a requisição pode ter sido forjada por outro site.
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== COLETA E SANITIZAÇÃO DOS CAMPOS =====
$id_bem          = campoPost('id_bem');
$descricao       = campoPost('descricao');
$marca           = campoPost('marca');
$numero_empenho  = campoPost('numero_empenho');
$data_aquisicao  = campoPost('data_aquisicao');
$numero_nota     = campoPost('numero_nota');
$setor_origem    = campoPost('setor_origem');
$setor           = campoPost('setor');
$subsetor        = campoPost('subsetor');
$unidade         = campoPost('unidade');
$grupo_id        = campoPost('grupo_id');
$estado          = campoPost('estado');
$tipo_id         = campoPost('tipo_id');
$valor_raw       = campoPost('valor');

$usuario_id      = $_SESSION['usuario_id'];
$cnpj_logado     = $_SESSION['cnpj'];

// ===== VALIDAÇÃO: ID DO BEM =====
if ($id_bem === '' || !ctype_digit($id_bem)) {
    voltarParaAlteracao('Bem inválido. Realize a busca novamente.');
}

// ===== VERIFICA SE O BEM EXISTE, PERTENCE AO USUÁRIO/CNPJ E SE ESTÁ DENTRO DO PRAZO =====
// created_by: a busca (buscar-bem-movel.php) só mostra bens do próprio usuário,
// então a gravação precisa aplicar a mesma regra; sem isso, bastaria enviar
// outro id_bem no POST para alterar o bem de um colega.
try {
    $stmtVerifica = $pdo->prepare(
        "SELECT *, (created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)) AS dentro_prazo
         FROM bens_moveis
         WHERE id = :id AND cnpj = :cnpj AND created_by = :usuario_id
         LIMIT 1"
    );
    $stmtVerifica->bindParam(':id',         $id_bem,      PDO::PARAM_INT);
    $stmtVerifica->bindParam(':cnpj',       $cnpj_logado, PDO::PARAM_STR);
    $stmtVerifica->bindParam(':usuario_id', $usuario_id,  PDO::PARAM_INT);
    $stmtVerifica->execute();
    $bem = $stmtVerifica->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    voltarParaAlteracao('Erro interno ao verificar o bem. Tente novamente.');
}

if (!$bem) {
    voltarParaAlteracao('Bem não encontrado ou sem permissão para alterar.');
}

// Após 3 dias, apenas setor, subsetor, unidade e estado podem ser alterados
$edicao_completa = (bool) $bem['dentro_prazo'];

// Snapshot "antes" para o log de auditoria (remove campo calculado que não existe na tabela)
$dadosAntes = $bem;
unset($dadosAntes['dentro_prazo']);

// ===== VALIDAÇÃO: CAMPOS OBRIGATÓRIOS (APENAS DENTRO DO PRAZO DE EDIÇÃO COMPLETA) =====
if ($edicao_completa) {
    if ($descricao === '') {
        voltarParaAlteracao('O campo Descrição é obrigatório.');
    }

    if (mb_strlen($descricao, 'UTF-8') > 255) {
        voltarParaAlteracao('A descrição não pode ultrapassar 255 caracteres.');
    }

    if (mb_strlen($marca, 'UTF-8') > 100) {
        voltarParaAlteracao('A marca não pode ultrapassar 100 caracteres.');
    }

    if ($numero_empenho === '') {
        voltarParaAlteracao('O campo Nº do Empenho é obrigatório.');
    }

    if (mb_strlen($numero_empenho, 'UTF-8') > 100) {
        voltarParaAlteracao('O Nº do Empenho não pode ultrapassar 100 caracteres.');
    }

    if ($data_aquisicao === '') {
        voltarParaAlteracao('O campo Data de Aquisição é obrigatório.');
    }

    // Valida formato de data (YYYY-MM-DD)
    $d = DateTime::createFromFormat('Y-m-d', $data_aquisicao);
    if (!$d || $d->format('Y-m-d') !== $data_aquisicao) {
        voltarParaAlteracao('Data de Aquisição inválida.');
    }

    // Mesma regra do cadastro: a alteração não pode burlar a data futura
    if ($d > new DateTime('today')) {
        voltarParaAlteracao('A data de aquisição não pode ser maior que a data atual.');
    }

    if ($numero_nota === '') {
        voltarParaAlteracao('O campo Nº da Nota é obrigatório.');
    }

    if (mb_strlen($numero_nota, 'UTF-8') > 100) {
        voltarParaAlteracao('O Nº da Nota não pode ultrapassar 100 caracteres.');
    }

    // Setor de Origem: só pode ser alterado dentro do prazo de 3 dias
    if (!ctype_digit($setor_origem)) {
        voltarParaAlteracao('O campo Setor de Origem é obrigatório.');
    }

    try {
        $stmtSO = $pdo->prepare("SELECT id FROM setores WHERE id = :v LIMIT 1");
        $stmtSO->bindParam(':v', $setor_origem, PDO::PARAM_INT);
        $stmtSO->execute();
        $setorOrigemExiste = (bool) $stmtSO->fetch();
    } catch (PDOException $e) {
        voltarParaAlteracao('Erro interno ao verificar o setor de origem. Tente novamente.');
    }

    if (!$setorOrigemExiste) {
        voltarParaAlteracao('O setor de origem selecionado é inválido.');
    }
}

// ===== VALIDAÇÃO: SETOR / SUBSETOR / UNIDADE (EDITÁVEIS EM QUALQUER PRAZO) =====
if ($setor === '') {
    voltarParaAlteracao('O campo Setor é obrigatório.');
}

if ($subsetor === '') {
    voltarParaAlteracao('O campo Subsetor é obrigatório.');
}

// Confere no banco, como o cadastro já fazia: sem isso, um POST manipulado
// gravaria qualquer texto como setor/subsetor/unidade.
try {
    $stmtS = $pdo->prepare("SELECT id FROM setores WHERE descricao = :v LIMIT 1");
    $stmtS->bindParam(':v', $setor, PDO::PARAM_STR);
    $stmtS->execute();
    $setorValido = (bool) $stmtS->fetch();

    $stmtSS = $pdo->prepare("SELECT id FROM subsetores WHERE descricao = :sub AND setor = :set LIMIT 1");
    $stmtSS->bindParam(':sub', $subsetor, PDO::PARAM_STR);
    $stmtSS->bindParam(':set', $setor,    PDO::PARAM_STR);
    $stmtSS->execute();
    $subsetorValido = (bool) $stmtSS->fetch();

    $unidadeValida = true;
    if ($unidade !== '') {
        $stmtU = $pdo->prepare("SELECT id FROM unidades WHERE descricao = :u AND setor = :s AND subsetor = :ss LIMIT 1");
        $stmtU->bindParam(':u',  $unidade,  PDO::PARAM_STR);
        $stmtU->bindParam(':s',  $setor,    PDO::PARAM_STR);
        $stmtU->bindParam(':ss', $subsetor, PDO::PARAM_STR);
        $stmtU->execute();
        $unidadeValida = (bool) $stmtU->fetch();
    }
} catch (PDOException $e) {
    voltarParaAlteracao('Erro interno ao verificar a localização. Tente novamente.');
}

if (!$setorValido) {
    voltarParaAlteracao('O setor selecionado é inválido.');
}
if (!$subsetorValido) {
    voltarParaAlteracao('O subsetor selecionado é inválido.');
}
if (!$unidadeValida) {
    voltarParaAlteracao('A unidade selecionada é inválida.');
}

if ($edicao_completa) {
    // Grupo: valida formato e existência no banco
    if (!ctype_digit($grupo_id)) {
        voltarParaAlteracao('Grupo inválido.');
    }

    try {
        $stmtG = $pdo->prepare("SELECT id FROM grupos WHERE id = :id LIMIT 1");
        $stmtG->bindParam(':id', $grupo_id, PDO::PARAM_INT);
        $stmtG->execute();
        $grupoExiste = (bool) $stmtG->fetch();
    } catch (PDOException $e) {
        voltarParaAlteracao('Erro interno ao verificar o grupo. Tente novamente.');
    }

    if (!$grupoExiste) {
        voltarParaAlteracao('O grupo selecionado é inválido.');
    }
}

// Estados permitidos (sempre validado — o campo Estado é editável em qualquer prazo)
$estados_validos = ['Novo', 'Bom', 'Regular', 'Ruim', 'Depreciado', 'Inservivel'];
if (!in_array($estado, $estados_validos, true)) {
    voltarParaAlteracao('Estado inválido.');
}

if ($edicao_completa) {
    // Tipo: valida formato e existência no banco
    if (!ctype_digit($tipo_id)) {
        voltarParaAlteracao('Tipo inválido.');
    }

    try {
        $stmtTp = $pdo->prepare("SELECT id FROM tipos WHERE id = :id LIMIT 1");
        $stmtTp->bindParam(':id', $tipo_id, PDO::PARAM_INT);
        $stmtTp->execute();
        $tipoExiste = (bool) $stmtTp->fetch();
    } catch (PDOException $e) {
        voltarParaAlteracao('Erro interno ao verificar o tipo. Tente novamente.');
    }

    if (!$tipoExiste) {
        voltarParaAlteracao('O tipo selecionado é inválido.');
    }

    // ===== VALIDAÇÃO DO VALOR =====
    if ($valor_raw === '' || !is_numeric($valor_raw) || (float) $valor_raw < 0) {
        // Zero é permitido (ex.: bens recebidos em doação)
        voltarParaAlteracao('O campo Custo é obrigatório e deve ser igual ou maior que zero.');
    }

    $valor = number_format((float) $valor_raw, 2, '.', '');
}

// ===== ATUALIZAÇÃO NO BANCO (PDO + Prepared Statement) =====
try {
    if ($edicao_completa) {
        $sql = "UPDATE bens_moveis SET
                    descricao       = :descricao,
                    marca           = :marca,
                    numero_empenho  = :numero_empenho,
                    data_aquisicao  = :data_aquisicao,
                    numero_nota     = :numero_nota,
                    setor           = :setor,
                    setor_original  = :setor_original,
                    subsetor        = :subsetor,
                    unidade         = :unidade,
                    grupo_id        = :grupo_id,
                    estado          = :estado,
                    tipo_id         = :tipo_id,
                    valor           = :valor
                WHERE id = :id AND cnpj = :cnpj AND created_by = :usuario_id";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':descricao',      $descricao,      PDO::PARAM_STR);
        $stmt->bindParam(':marca',          $marca,          PDO::PARAM_STR);
        $stmt->bindParam(':numero_empenho', $numero_empenho, PDO::PARAM_STR);
        $stmt->bindParam(':data_aquisicao', $data_aquisicao, PDO::PARAM_STR);
        $stmt->bindParam(':numero_nota',    $numero_nota,    PDO::PARAM_STR);
        $stmt->bindParam(':setor',          $setor,          PDO::PARAM_STR);
        $stmt->bindParam(':setor_original', $setor_origem,   PDO::PARAM_INT);
        $stmt->bindParam(':subsetor',       $subsetor,       PDO::PARAM_STR);
        $stmt->bindParam(':unidade',        $unidade,        PDO::PARAM_STR);
        $stmt->bindParam(':grupo_id',       $grupo_id,       PDO::PARAM_INT);
        $stmt->bindParam(':estado',         $estado,         PDO::PARAM_STR);
        $stmt->bindParam(':tipo_id',        $tipo_id,        PDO::PARAM_INT);
        $stmt->bindParam(':valor',          $valor,          PDO::PARAM_STR);
        $stmt->bindParam(':id',             $id_bem,         PDO::PARAM_INT);
        $stmt->bindParam(':cnpj',           $cnpj_logado,    PDO::PARAM_STR);
        $stmt->bindParam(':usuario_id',     $usuario_id,     PDO::PARAM_INT);
    } else {
        $sql = "UPDATE bens_moveis SET
                    setor           = :setor,
                    subsetor        = :subsetor,
                    unidade         = :unidade,
                    estado          = :estado
                WHERE id = :id AND cnpj = :cnpj AND created_by = :usuario_id";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':setor',          $setor,          PDO::PARAM_STR);
        $stmt->bindParam(':subsetor',       $subsetor,       PDO::PARAM_STR);
        $stmt->bindParam(':unidade',        $unidade,        PDO::PARAM_STR);
        $stmt->bindParam(':estado',         $estado,         PDO::PARAM_STR);
        $stmt->bindParam(':id',             $id_bem,         PDO::PARAM_INT);
        $stmt->bindParam(':cnpj',           $cnpj_logado,    PDO::PARAM_STR);
        $stmt->bindParam(':usuario_id',     $usuario_id,     PDO::PARAM_INT);
    }

    $stmt->execute();

    if ($edicao_completa) {
        $dadosDepois = [
            'descricao'      => $descricao,
            'marca'          => $marca,
            'numero_empenho' => $numero_empenho,
            'data_aquisicao' => $data_aquisicao,
            'numero_nota'    => $numero_nota,
            'setor'          => $setor,
            'setor_original' => $setor_origem,
            'subsetor'       => $subsetor,
            'unidade'        => $unidade,
            'grupo_id'       => $grupo_id,
            'estado'         => $estado,
            'tipo_id'        => $tipo_id,
            'valor'          => $valor,
        ];
    } else {
        $dadosDepois = [
            'setor'    => $setor,
            'subsetor' => $subsetor,
            'unidade'  => $unidade,
            'estado'   => $estado,
        ];
    }

    // ===== SEPARA MOVIMENTO (setor/subsetor/unidade) DE EDIÇÃO (demais campos) =====
    $campos_movimento = ['setor', 'subsetor', 'unidade'];

    $houve_movimento = false;
    foreach ($campos_movimento as $campo) {
        if ((string) ($dadosAntes[$campo] ?? '') !== (string) ($dadosDepois[$campo] ?? '')) {
            $houve_movimento = true;
            break;
        }
    }

    $houve_outra_alteracao = false;
    foreach ($dadosDepois as $campo => $valorCampo) {
        if (in_array($campo, $campos_movimento, true)) {
            continue;
        }
        if ((string) ($dadosAntes[$campo] ?? '') !== (string) $valorCampo) {
            $houve_outra_alteracao = true;
            break;
        }
    }

    // 'edicao' só é registrada se algo além de setor/subsetor/unidade mudou
    if ($houve_outra_alteracao) {
        registrarAuditoria($pdo, $usuario_id, 'edicao', 'bens_moveis', (int) $id_bem, $dadosAntes, $dadosDepois);
    }

    // 'movimento' é registrada sempre que setor/subsetor/unidade mudou, com ou sem outras alterações
    if ($houve_movimento) {
        $movimentoAntes  = array_intersect_key($dadosAntes, array_flip($campos_movimento));
        $movimentoDepois = array_intersect_key($dadosDepois, array_flip($campos_movimento));
        registrarAuditoria($pdo, $usuario_id, 'movimento', 'bens_moveis', (int) $id_bem, $movimentoAntes, $movimentoDepois);
    }

} catch (PDOException $e) {
    voltarParaAlteracao('Erro interno ao salvar as alterações. Tente novamente.');
}

limparOld('alteracao_bem');

$_SESSION['sucesso'] = 'Bem móvel alterado com sucesso!';
header('Location: alterar-bem-movel');
exit;
