<?php
session_start();
require_once 'conexao.php';

// Bloqueia qualquer método que não seja POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-subsetor');
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
    header('Location: cadastrar-subsetor');
    exit;
}

// Invalida o token após uso (token de uso único)
unset($_SESSION['csrf_token']);

// ===== VALIDAÇÃO DOS CAMPOS =====
$descricao  = trim($_POST['descricao'] ?? '');
$setor      = trim($_POST['setor']     ?? '');
$created_by = $_SESSION['usuario'];

if (empty($setor)) {
    $_SESSION['erro'] = 'Selecione um setor.';
    header('Location: cadastrar-subsetor');
    exit;
}

if (empty($descricao)) {
    $_SESSION['erro'] = 'O campo Descrição é obrigatório.';
    header('Location: cadastrar-subsetor');
    exit;
}

if (strlen($descricao) < 10) {
    $_SESSION['erro'] = 'O nome do subsetor não pode ter menos de 10 caracteres.';
    header('Location: cadastrar-subsetor');
    exit;
}

if (strlen($descricao) > 255) {
    $_SESSION['erro'] = 'O nome do subsetor não pode ultrapassar 255 caracteres.';
    header('Location: cadastrar-subsetor');
    exit;
}

// ===== VERIFICA SE O SETOR ENVIADO EXISTE NO BANCO =====
// Impede que um valor adulterado no POST seja aceito
try {
    $sqlSetor = "SELECT id FROM setores WHERE descricao = :setor LIMIT 1";
    $stmtSetor = $pdo->prepare($sqlSetor);
    $stmtSetor->bindParam(':setor', $setor, PDO::PARAM_STR);
    $stmtSetor->execute();

    if (!$stmtSetor->fetch()) {
        $_SESSION['erro'] = 'O setor selecionado é inválido.';
        header('Location: cadastrar-subsetor');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-subsetor');
    exit;
}

// ===== VERIFICA SE O SUBSETOR JÁ EXISTE NO MESMO SETOR =====
try {
    $sqlVerifica = "SELECT id FROM subsetores 
                    WHERE descricao = :descricao AND setor = :setor 
                    LIMIT 1";
    $stmtVerifica = $pdo->prepare($sqlVerifica);
    $stmtVerifica->bindParam(':descricao', $descricao, PDO::PARAM_STR);
    $stmtVerifica->bindParam(':setor',     $setor,     PDO::PARAM_STR);
    $stmtVerifica->execute();

    if ($stmtVerifica->fetch()) {
        $_SESSION['erro'] = 'Este subsetor já está cadastrado para o setor selecionado.';
        header('Location: cadastrar-subsetor');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-subsetor');
    exit;
}

// ===== INSERÇÃO NO BANCO (PDO + Prepared Statement) =====
try {
    $sql = "INSERT INTO subsetores (descricao, setor, created_by) 
            VALUES (:descricao, :setor, :created_by)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':descricao',  $descricao,  PDO::PARAM_STR);
    $stmt->bindParam(':setor',      $setor,      PDO::PARAM_STR);
    $stmt->bindParam(':created_by', $created_by, PDO::PARAM_STR);
    $stmt->execute();

    $_SESSION['sucesso'] = 'Subsetor cadastrado com sucesso!';
    header('Location: cadastrar-subsetor');
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-subsetor');
    exit;
}
