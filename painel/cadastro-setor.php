<?php
session_start();
require_once 'conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-setor');
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

// ===== PROTEÇÃO CSRF =====
$token_recebido  = $_POST['csrf_token']   ?? '';
$token_sessao    = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !hash_equals($token_sessao, $token_recebido)) {
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: cadastrar-setor');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== VALIDAÇÃO DOS CAMPOS =====
$descricao  = trim($_POST['descricao'] ?? '');
$created_by = $_SESSION['usuario'];

if (empty($descricao)) {
    $_SESSION['erro'] = 'O campo Descrição é obrigatório.';
    header('Location: cadastrar-setor');
    exit;
}

if (strlen($descricao) > 255) {
    $_SESSION['erro'] = 'O nome do setor não pode ultrapassar 255 caracteres.';
    header('Location: cadastrar-setor');
    exit;
}

if (strlen($descricao) < 10) {
    $_SESSION['erro'] = 'O nome do setor não pode ter menos de 10 caracteres.';
    header('Location: cadastrar-setor');
    exit;
}

// ===== VERIFICA SE O SETOR JÁ EXISTE =====
$sqlVerifica = "SELECT id FROM setores WHERE descricao = :descricao LIMIT 1";
$stmtVerifica = $pdo->prepare($sqlVerifica);
$stmtVerifica->bindParam(':descricao', $descricao, PDO::PARAM_STR);
$stmtVerifica->execute();

if ($stmtVerifica->fetch()) {
    $_SESSION['erro'] = 'Este setor já está cadastrado.';
    header('Location: cadastrar-setor');
    exit;
}

// ===== INSERÇÃO NO BANCO (PDO + Prepared Statement) =====
try {
    $sql = "INSERT INTO setores (descricao, created_by) 
            VALUES (:descricao, :created_by)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':descricao',  $descricao,  PDO::PARAM_STR);
    $stmt->bindParam(':created_by', $created_by, PDO::PARAM_STR);
    $stmt->execute();

    $_SESSION['sucesso'] = 'Setor cadastrado com sucesso!';
    header('Location: cadastrar-setor');
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-setor');
    exit;
}
