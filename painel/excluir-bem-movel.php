<?php
session_start();
require_once 'conexao.php';
require_once 'log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-bem-movel');
    exit;
}

if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario_id'])) {
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

unset($_SESSION['csrf_token']);

$id_bem      = trim($_POST['bem_id']  ?? '');
$cnpj_logado = $_SESSION['cnpj'];

if (empty($id_bem) || !ctype_digit($id_bem)) {
    $_SESSION['erro'] = 'Bem inválido. Realize a busca novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// ===== VERIFICA SE O BEM EXISTE E PERTENCE AO CNPJ DO USUÁRIO =====
try {
    $stmtVerifica = $pdo->prepare(
        "SELECT *
         FROM bens_moveis
         WHERE id = :id AND cnpj = :cnpj
		 AND created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
         LIMIT 1"
    );
    $stmtVerifica->bindParam(':id',   $id_bem,      PDO::PARAM_INT);
    $stmtVerifica->bindParam(':cnpj', $cnpj_logado, PDO::PARAM_STR);
    $stmtVerifica->execute();

    $bem = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

    if (!$bem) {
        $_SESSION['erro'] = 'Bem não encontrado ou sem permissão para excluir.';
        header('Location: alterar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno ao verificar o bem. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// ===== EXCLUSÃO NO BANCO =====
try {
    $stmtDelete = $pdo->prepare(
        "DELETE FROM bens_moveis WHERE id = :id AND cnpj = :cnpj"
    );
    $stmtDelete->bindParam(':id',   $id_bem,      PDO::PARAM_INT);
    $stmtDelete->bindParam(':cnpj', $cnpj_logado, PDO::PARAM_STR);
    $stmtDelete->execute();

    if ($stmtDelete->rowCount() === 0) {
        $_SESSION['erro'] = 'Não foi possível excluir o bem. Tente novamente.';
        header('Location: alterar-bem-movel');
        exit;
    }

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'exclusao', 'bens_moveis', (int) $id_bem, $bem, null);
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno ao excluir o bem. Tente novamente.';
    header('Location: alterar-bem-movel');
    exit;
}

// ===== REMOVE AS IMAGENS DO SERVIDOR =====
if (!empty($bem['imagens'])) {
    $urlsImagens = json_decode($bem['imagens'], true);
    if (is_array($urlsImagens)) {
        foreach ($urlsImagens as $caminho) {
            if (!empty($caminho) && file_exists($caminho)) {
                unlink($caminho);
            }
        }
    }
}

$_SESSION['sucesso'] = 'Bem móvel Nº ' . htmlspecialchars($bem['numero_tombamento']) . ' excluído com sucesso.';
header('Location: alterar-bem-movel');
exit;
