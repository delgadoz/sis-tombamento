<?php
session_start();
require_once 'conexao.php';
require_once 'rate_limit.php';
require_once 'log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$ip    = obterIpCliente();

if (empty($email) || empty($senha)) {
    $_SESSION['erro'] = 'Preencha todos os campos.';
    header('Location: login');
    exit;
}

// Limpeza periódica de registros antigos (baixa probabilidade por requisição)
limparRegistrosAntigos($pdo);

// Bloqueio por IP 
if (estaBloqueado($pdo, $ip, 'ip')) {
    $_SESSION['erro'] = 'Muitas tentativas. Tente novamente em alguns minutos.';
    header('Location: login');
    exit;
}

// Bloqueio por e-mail
if (estaBloqueado($pdo, $email, 'email')) {
    $_SESSION['erro'] = 'Muitas tentativas para este e-mail. Tente novamente mais tarde.';
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
        registrarTentativaFalha($pdo, $ip, 'ip');
        registrarTentativaFalha($pdo, $email, 'email');
        registrarLoginFalho($pdo, $email, $ip);
        $_SESSION['erro'] = 'E-mail ou senha inválidos.';
        header('Location: login');
        exit;
    }

    // Login OK - limpa o histórico de tentativas falhas
    limparTentativas($pdo, $ip, 'ip');
    limparTentativas($pdo, $email, 'email');
	
	$_SESSION['usuario_id'] = $usuario['id'];
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
