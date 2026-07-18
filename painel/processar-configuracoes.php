<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: configuracoes');
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

$token_recebido = $_POST['csrf_token']    ?? '';
$token_sessao   = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !hash_equals($token_sessao, $token_recebido)) {
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: configuracoes');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== IDENTIFICA QUAL AÇÃO FOI SOLICITADA (WHITELIST) =====
$acao = $_POST['acao'] ?? '';
$acoes_permitidas = ['alterar_senha'];

if (!in_array($acao, $acoes_permitidas, true)) {
    $_SESSION['erro'] = 'Ação inválida.';
    header('Location: configuracoes');
    exit;
}

$usuario = $_SESSION['usuario'];

// ===== BUSCA OS DADOS ATUAIS DO USUÁRIO NO BANCO =====
try {
    $stmtU = $pdo->prepare("SELECT id, senha FROM usuarios WHERE usuario = :u LIMIT 1");
    $stmtU->bindParam(':u', $usuario, PDO::PARAM_STR);
    $stmtU->execute();
    $dadosUsuario = $stmtU->fetch(PDO::FETCH_ASSOC);

    if (!$dadosUsuario) {
        $_SESSION['erro'] = 'Erro interno. Tente novamente.';
        header('Location: configuracoes');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: configuracoes');
    exit;
}

// ===== VALIDA A SENHA ATUAL (OBRIGATÓRIA EM AMBOS OS FORMULÁRIOS) =====
$senha_atual = $_POST['senha_atual'] ?? '';

if (empty($senha_atual)) {
    $_SESSION['erro'] = 'A senha atual é obrigatória para confirmar a alteração.';
    header('Location: configuracoes');
    exit;
}

if (!password_verify($senha_atual, $dadosUsuario['senha'])) {
    $_SESSION['erro'] = 'Senha atual incorreta.';
    header('Location: configuracoes');
    exit;
}

// ===== AÇÃO: ALTERAR SENHA =====

if ($acao === 'alterar_senha') {

    $nova_senha          = $_POST['nova_senha']          ?? '';
    $nova_senha_confirma = $_POST['nova_senha_confirma'] ?? '';

    if (empty($nova_senha) || empty($nova_senha_confirma)) {
        $_SESSION['erro'] = 'Preencha a nova senha e a confirmação.';
        header('Location: configuracoes');
        exit;
    }

    if (!hash_equals($nova_senha, $nova_senha_confirma)) {
        $_SESSION['erro'] = 'A nova senha e a confirmação não coincidem.';
        header('Location: configuracoes');
        exit;
    }

    if (strlen($nova_senha) < 8) {
        $_SESSION['erro'] = 'A nova senha deve ter no mínimo 8 caracteres.';
        header('Location: configuracoes');
        exit;
    }

    if (strlen($nova_senha) > 72) {
        $_SESSION['erro'] = 'A nova senha não pode ter mais de 72 caracteres.';
        header('Location: configuracoes');
        exit;
    }

    if (!preg_match('/[A-Za-z]/', $nova_senha) || !preg_match('/[0-9]/', $nova_senha)) {
        $_SESSION['erro'] = 'A nova senha deve conter letras e números.';
        header('Location: configuracoes');
        exit;
    }

    if (password_verify($nova_senha, $dadosUsuario['senha'])) {
        $_SESSION['erro'] = 'A nova senha deve ser diferente da senha atual.';
        header('Location: configuracoes');
        exit;
    }

    $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // ===== ATUALIZA A SENHA NO BANCO =====
    try {
        $stmtUp = $pdo->prepare("UPDATE usuarios SET senha = :s WHERE id = :id");
        $stmtUp->bindParam(':s',  $novo_hash,         PDO::PARAM_STR);
        $stmtUp->bindParam(':id', $dadosUsuario['id'], PDO::PARAM_INT);
        $stmtUp->execute();

        // Por segurança, invalida a sessão atual e exige novo login
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        session_start();
        $_SESSION['sucesso'] = 'Senha atualizada com sucesso! Faça login novamente.';
        header('Location: login');
        exit;

    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro interno. Tente novamente.';
        header('Location: configuracoes');
        exit;
    }
}
