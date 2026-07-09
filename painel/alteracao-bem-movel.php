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
$grupo           = trim($_POST['grupo']            ?? '');
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

// ===== VALIDAÇÃO: CAMPOS OBRIGATÓRIOS =====
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

// Grupos permitidos
$grupos_validos = [
    'Móveis', 'Eletrodoméstico', 'Eletrônicos', 'Instrumento Musical',
    'Equipamentos Hospitalares', 'Máquinas e Equipamentos', 'Veículos',
    'Ferramentas', 'Outros'
];
if (empty($grupo) || !in_array($grupo, $grupos_validos, true)) {
    $_SESSION['erro'] = 'Grupo inválido.';
    header('Location: alterar-bem-movel');
    exit;
}

// Estados permitidos
$estados_validos = ['Novo', 'Bom', 'Regular', 'Ruim', 'Depreciado', 'Inservivel'];
if (empty($estado) || !in_array($estado, $estados_validos, true)) {
    $_SESSION['erro'] = 'Estado inválido.';
    header('Location: alterar-bem-movel');
    exit;
}

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

// ===== VERIFICA SE O BEM EXISTE E PERTENCE AO CNPJ DO USUÁRIO =====
try {
    $stmtVerifica = $pdo->prepare(
        "SELECT id FROM bens_moveis
         WHERE id = :id AND cnpj = :cnpj
		 AND created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
         LIMIT 1"
    );
    $stmtVerifica->bindParam(':id',   $id_bem,       PDO::PARAM_INT);
    $stmtVerifica->bindParam(':cnpj', $cnpj_logado,  PDO::PARAM_STR);
    $stmtVerifica->execute();

    if (!$stmtVerifica->fetch()) {
        $_SESSION['erro'] = 'Bem não encontrado ou sem permissão para alterar.';
        header('Location: alterar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno ao verificar o bem. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// ===== ATUALIZAÇÃO NO BANCO (PDO + Prepared Statement) =====
try {
    $sql = "UPDATE bens_moveis SET
                descricao       = :descricao,
                marca           = :marca,
                numero_empenho  = :numero_empenho,
                data_aquisicao  = :data_aquisicao,
                numero_nota     = :numero_nota,
                setor           = :setor,
                subsetor        = :subsetor,
                unidade         = :unidade,
                grupo           = :grupo,
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
    $stmt->bindParam(':grupo',          $grupo,          PDO::PARAM_STR);
    $stmt->bindParam(':estado',         $estado,         PDO::PARAM_STR);
    $stmt->bindParam(':tipo',           $tipo,           PDO::PARAM_STR);
    $stmt->bindParam(':valor',          $valor,          PDO::PARAM_STR);
    $stmt->bindParam(':updated_by',     $usuario_logado, PDO::PARAM_STR);
    $stmt->bindParam(':id',             $id_bem,         PDO::PARAM_INT);
    $stmt->bindParam(':cnpj',           $cnpj_logado,    PDO::PARAM_STR);

    $stmt->execute();

    $_SESSION['sucesso'] = 'Bem móvel alterado com sucesso!';
    header('Location: alterar-bem-movel');
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno ao salvar as alterações. Tente novamente.' . $e;
    header('Location: alterar-bem-movel');
    exit;
}