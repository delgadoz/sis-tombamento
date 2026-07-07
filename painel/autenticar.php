<?php
session_start();
require_once 'conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    $_SESSION['erro'] = 'Preencha todos os campos.';
    header('Location: login');
    exit;
}

try {
    $sql = "SELECT id, usuario, nome, email, senha, cnpj 
            FROM usuarios 
            WHERE email = :email 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        $_SESSION['erro'] = 'E-mail ou senha inválidos.';
        header('Location: login');
        exit;
    }

    // Login OK
    $_SESSION['usuario'] = $usuario['usuario'];
    $_SESSION['nome']    = $usuario['nome'];
    $_SESSION['email']   = $usuario['email'];
	$_SESSION['cnpj']   = $usuario['cnpj'];

    header('Location: painel');
    exit;

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: login');
    exit;
}
