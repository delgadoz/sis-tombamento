<?php
session_start();
require_once 'conexao.php';

// Bloqueia qualquer método que não seja POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-unidade');
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
    header('Location: cadastrar-unidade');
    exit;
}

// Invalida o token após uso (token de uso único)
unset($_SESSION['csrf_token']);

// ===== VALIDAÇÃO DOS CAMPOS =====
$descricao  = trim($_POST['descricao'] ?? '');
$setor      = trim($_POST['setor']     ?? '');
$subsetor   = trim($_POST['subsetor']  ?? '');
$created_by = $_SESSION['usuario'];

if (empty($setor)) {
    $_SESSION['erro'] = 'Selecione um setor.';
    header('Location: cadastrar-unidade');
    exit;
}

if (empty($subsetor)) {
    $_SESSION['erro'] = 'Selecione um subsetor.';
    header('Location: cadastrar-unidade');
    exit;
}

if (empty($descricao)) {
    $_SESSION['erro'] = 'O campo Descrição é obrigatório.';
    header('Location: cadastrar-unidade');
    exit;
}

if (strlen($descricao) < 3) {
    $_SESSION['erro'] = 'O nome da unidade não pode ter menos de 3 caracteres.';
    header('Location: cadastrar-unidade');
    exit;
}

if (strlen($descricao) > 255) {
    $_SESSION['erro'] = 'O nome da unidade não pode ultrapassar 255 caracteres.';
    header('Location: cadastrar-unidade');
    exit;
}

// ===== VERIFICA SE O SETOR EXISTE NO BANCO =====
try {
    $sqlSetor = "SELECT id FROM setores WHERE descricao = :setor LIMIT 1";
    $stmtSetor = $pdo->prepare($sqlSetor);
    $stmtSetor->bindParam(':setor', $setor, PDO::PARAM_STR);
    $stmtSetor->execute();

    if (!$stmtSetor->fetch()) {
        $_SESSION['erro'] = 'O setor selecionado é inválido.';
        header('Location: cadastrar-unidade');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-unidade');
    exit;
}

// ===== VERIFICA SE O SUBSETOR EXISTE E PERTENCE AO SETOR INFORMADO =====
try {
    $sqlSubsetor = "SELECT id FROM subsetores WHERE descricao = :subsetor AND setor = :setor LIMIT 1";
    $stmtSubsetor = $pdo->prepare($sqlSubsetor);
    $stmtSubsetor->bindParam(':subsetor', $subsetor, PDO::PARAM_STR);
    $stmtSubsetor->bindParam(':setor',    $setor,    PDO::PARAM_STR);
    $stmtSubsetor->execute();

    if (!$stmtSubsetor->fetch()) {
        $_SESSION['erro'] = 'O subsetor selecionado é inválido.';
        header('Location: cadastrar-unidade');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-unidade');
    exit;
}

// ===== VERIFICA SE A UNIDADE JÁ EXISTE NO MESMO SUBSETOR =====
try {
    $sqlVerifica = "SELECT id FROM unidades 
                    WHERE descricao = :descricao AND setor = :setor AND subsetor = :subsetor
                    LIMIT 1";
    $stmtVerifica = $pdo->prepare($sqlVerifica);
    $stmtVerifica->bindParam(':descricao', $descricao, PDO::PARAM_STR);
    $stmtVerifica->bindParam(':setor',     $setor,     PDO::PARAM_STR);
    $stmtVerifica->bindParam(':subsetor',  $subsetor,  PDO::PARAM_STR);
    $stmtVerifica->execute();

    if ($stmtVerifica->fetch()) {
        $_SESSION['erro'] = 'Esta unidade já está cadastrada para o subsetor selecionado.';
        header('Location: cadastrar-unidade');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-unidade');
    exit;
}

// ===== INSERÇÃO NO BANCO (PDO + Prepared Statement) =====
try {
    $sql = "INSERT INTO unidades (descricao, setor, subsetor, created_by) 
            VALUES (:descricao, :setor, :subsetor, :created_by)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':descricao',  $descricao,  PDO::PARAM_STR);
    $stmt->bindParam(':setor',      $setor,      PDO::PARAM_STR);
    $stmt->bindParam(':subsetor',   $subsetor,   PDO::PARAM_STR);
    $stmt->bindParam(':created_by', $created_by, PDO::PARAM_STR);
    $stmt->execute();

    $_SESSION['sucesso'] = 'Unidade cadastrada com sucesso!';
    header('Location: cadastrar-unidade');
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-unidade');
    exit;
}
