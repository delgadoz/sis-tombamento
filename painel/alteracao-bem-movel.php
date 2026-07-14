<?php
session_start();
require_once 'conexao.php'; // arquivo de conexão PDO

// Bloqueia qualquer método que não seja POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-bem-movel');
    exit;
}

// Redireciona para login se não houver sessão ativa
if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

// ===== PROTEÇÃO CSRF =====
$token_recebido = $_POST['csrf_token']    ?? '';
$token_sessao   = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !hash_equals($token_sessao, $token_recebido)) {
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// Invalida o token após uso (token de uso único)
unset($_SESSION['csrf_token']);

// ===== COLETA E SANITIZAÇÃO DOS CAMPOS =====
$id_bem          = trim($_POST['id_bem']          ?? '');
$numero_tomb     = trim($_POST['numero_tombamento'] ?? '');
$descricao       = trim($_POST['descricao']        ?? '');
$marca           = trim($_POST['marca']            ?? '');
$numero_empenho  = trim($_POST['numero_empenho']   ?? '');
$data_aquisicao  = trim($_POST['data_aquisicao']   ?? '');
$numero_nota     = trim($_POST['numero_nota']      ?? '');
$setor           = trim($_POST['setor']            ?? '');
$subsetor        = trim($_POST['subsetor']         ?? '');
$unidade         = trim($_POST['unidade']          ?? '');
$grupo_id        = trim($_POST['grupo_id']         ?? '');
$estado          = trim($_POST['estado']           ?? '');
$tipo            = trim($_POST['tipo']             ?? '');
$valor_raw       = trim($_POST['valor']            ?? '');

$usuario_logado  = $_SESSION['usuario'];
$cnpj_logado     = $_SESSION['cnpj'];

// ===== VALIDAÇÃO: ID DO BEM =====
if (empty($id_bem) || !ctype_digit($id_bem)) {
    $_SESSION['erro'] = 'Bem inválido. Realize a busca novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// ===== VERIFICA SE O BEM EXISTE, PERTENCE AO CNPJ DO USUÁRIO E SE ESTÁ DENTRO DO PRAZO =====
try {
    $stmtVerifica = $pdo->prepare(
        "SELECT id, (created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)) AS dentro_prazo
         FROM bens_moveis
         WHERE id = :id AND cnpj = :cnpj
         LIMIT 1"
    );
    $stmtVerifica->bindParam(':id',   $id_bem,       PDO::PARAM_INT);
    $stmtVerifica->bindParam(':cnpj', $cnpj_logado,  PDO::PARAM_STR);
    $stmtVerifica->execute();
    $bem = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

    if (!$bem) {
        $_SESSION['erro'] = 'Bem não encontrado ou sem permissão para alterar.';
        header('Location: alterar-bem-movel');
        exit;
    }

    // Após 3 dias, apenas setor, subsetor, unidade e estado podem ser alterados
    $edicao_completa = (bool) $bem['dentro_prazo'];
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno ao verificar o bem. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// ===== VALIDAÇÃO: CAMPOS OBRIGATÓRIOS (APENAS DENTRO DO PRAZO DE EDIÇÃO COMPLETA) =====
if ($edicao_completa) {
    if (empty($descricao)) {
        $_SESSION['erro'] = 'O campo Descrição é obrigatório.';
        header('Location: alterar-bem-movel');
        exit;
    }

    if (strlen($descricao) > 255) {
        $_SESSION['erro'] = 'A descrição não pode ultrapassar 255 caracteres.';
        header('Location: alterar-bem-movel');
        exit;
    }

    if (empty($numero_empenho)) {
        $_SESSION['erro'] = 'O campo Nº do Empenho é obrigatório.';
        header('Location: alterar-bem-movel');
        exit;
    }

    if (strlen($numero_empenho) > 100) {
        $_SESSION['erro'] = 'O Nº do Empenho não pode ultrapassar 100 caracteres.';
        header('Location: alterar-bem-movel');
        exit;
    }

    if (empty($data_aquisicao)) {
        $_SESSION['erro'] = 'O campo Data de Aquisição é obrigatório.';
        header('Location: alterar-bem-movel');
        exit;
    }

    // Valida formato de data (YYYY-MM-DD)
    $d = DateTime::createFromFormat('Y-m-d', $data_aquisicao);
    if (!$d || $d->format('Y-m-d') !== $data_aquisicao) {
        $_SESSION['erro'] = 'Data de Aquisição inválida.';
        header('Location: alterar-bem-movel');
        exit;
    }

    if (empty($numero_nota)) {
        $_SESSION['erro'] = 'O campo Nº da Nota é obrigatório.';
        header('Location: alterar-bem-movel');
        exit;
    }

    if (strlen($numero_nota) > 100) {
        $_SESSION['erro'] = 'O Nº da Nota não pode ultrapassar 100 caracteres.';
        header('Location: alterar-bem-movel');
        exit;
    }
}

// ===== VALIDAÇÃO: SETOR / SUBSETOR (SEMPRE OBRIGATÓRIOS, EDITÁVEIS EM QUALQUER PRAZO) =====
if (empty($setor)) {
    $_SESSION['erro'] = 'O campo Setor é obrigatório.';
    header('Location: alterar-bem-movel');
    exit;
}

if (empty($subsetor)) {
    $_SESSION['erro'] = 'O campo Subsetor é obrigatório.';
    header('Location: alterar-bem-movel');
    exit;
}

if ($edicao_completa) {
    // Grupo: valida formato e existência no banco
    if ($grupo_id === '' || !ctype_digit($grupo_id)) {
        $_SESSION['erro'] = 'Grupo inválido.';
        header('Location: alterar-bem-movel');
        exit;
    }

    try {
        $stmtG = $pdo->prepare("SELECT id FROM grupos WHERE id = :id LIMIT 1");
        $stmtG->bindParam(':id', $grupo_id, PDO::PARAM_INT);
        $stmtG->execute();
        if (!$stmtG->fetch()) {
            $_SESSION['erro'] = 'O grupo selecionado é inválido.';
            header('Location: alterar-bem-movel');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro interno ao verificar o grupo. Tente novamente.';
        header('Location: alterar-bem-movel');
        exit;
    }
}

// Estados permitidos (sempre validado — o campo Estado é editável em qualquer prazo)
$estados_validos = ['Novo', 'Bom', 'Regular', 'Ruim', 'Depreciado', 'Inservivel'];
if (empty($estado) || !in_array($estado, $estados_validos, true)) {
    $_SESSION['erro'] = 'Estado inválido.';
    header('Location: alterar-bem-movel');
    exit;
}

if ($edicao_completa) {
    // Tipos permitidos
    $tipos_validos = ['gestão anterior', 'aquisição', 'doação'];
    if (empty($tipo) || !in_array($tipo, $tipos_validos, true)) {
        $_SESSION['erro'] = 'Tipo inválido.';
        header('Location: alterar-bem-movel');
        exit;
    }

    // ===== VALIDAÇÃO DO VALOR =====
    if ($valor_raw === '' || !is_numeric($valor_raw) || (float)$valor_raw <= 0) {
        $_SESSION['erro'] = 'O campo Custo é obrigatório e deve ser maior que zero.';
        header('Location: alterar-bem-movel');
        exit;
    }

    $valor = number_format((float)$valor_raw, 2, '.', '');
}

// ===== ATUALIZAÇÃO NO BANCO (PDO + Prepared Statement) =====
try {
    if ($edicao_completa) {
        // Dentro do prazo de 3 dias: edição completa do registro
        $sql = "UPDATE bens_moveis SET
                    descricao       = :descricao,
                    marca           = :marca,
                    numero_empenho  = :numero_empenho,
                    data_aquisicao  = :data_aquisicao,
                    numero_nota     = :numero_nota,
                    setor           = :setor,
                    subsetor        = :subsetor,
                    unidade         = :unidade,
                    grupo_id        = :grupo_id,
                    estado          = :estado,
                    tipo            = :tipo,
                    valor           = :valor,
                    updated_by      = :updated_by,
                    updated_at      = NOW()
                WHERE id = :id AND cnpj = :cnpj";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':descricao',      $descricao,      PDO::PARAM_STR);
        $stmt->bindParam(':marca',          $marca,          PDO::PARAM_STR);
        $stmt->bindParam(':numero_empenho', $numero_empenho, PDO::PARAM_STR);
        $stmt->bindParam(':data_aquisicao', $data_aquisicao, PDO::PARAM_STR);
        $stmt->bindParam(':numero_nota',    $numero_nota,    PDO::PARAM_STR);
        $stmt->bindParam(':setor',          $setor,          PDO::PARAM_STR);
        $stmt->bindParam(':subsetor',       $subsetor,       PDO::PARAM_STR);
        $stmt->bindParam(':unidade',        $unidade,        PDO::PARAM_STR);
        $stmt->bindParam(':grupo_id',       $grupo_id,       PDO::PARAM_INT);
        $stmt->bindParam(':estado',         $estado,         PDO::PARAM_STR);
        $stmt->bindParam(':tipo',           $tipo,           PDO::PARAM_STR);
        $stmt->bindParam(':valor',          $valor,          PDO::PARAM_STR);
        $stmt->bindParam(':updated_by',     $usuario_logado, PDO::PARAM_STR);
        $stmt->bindParam(':id',             $id_bem,         PDO::PARAM_INT);
        $stmt->bindParam(':cnpj',           $cnpj_logado,    PDO::PARAM_STR);
    } else {
        // Fora do prazo de 3 dias: edição restrita a setor, subsetor, unidade e estado
        $sql = "UPDATE bens_moveis SET
                    setor           = :setor,
                    subsetor        = :subsetor,
                    unidade         = :unidade,
                    estado          = :estado,
                    updated_by      = :updated_by,
                    updated_at      = NOW()
                WHERE id = :id AND cnpj = :cnpj";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':setor',          $setor,          PDO::PARAM_STR);
        $stmt->bindParam(':subsetor',       $subsetor,       PDO::PARAM_STR);
        $stmt->bindParam(':unidade',        $unidade,        PDO::PARAM_STR);
        $stmt->bindParam(':estado',         $estado,         PDO::PARAM_STR);
        $stmt->bindParam(':updated_by',     $usuario_logado, PDO::PARAM_STR);
        $stmt->bindParam(':id',             $id_bem,         PDO::PARAM_INT);
        $stmt->bindParam(':cnpj',           $cnpj_logado,    PDO::PARAM_STR);
    }

    $stmt->execute();

    $_SESSION['sucesso'] = 'Bem móvel alterado com sucesso!';
    header('Location: alterar-bem-movel');
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno ao salvar as alterações. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}
