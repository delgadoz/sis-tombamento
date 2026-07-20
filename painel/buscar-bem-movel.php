<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit;
}
$usuario = $_SESSION['usuario'];
header('Content-Type: application/json; charset=utf-8');
$numero_tombamento = trim($_GET['numero_tombamento'] ?? '');

if ($numero_tombamento === '' || !ctype_digit($numero_tombamento)) {
    echo json_encode(null);
    exit;
}
$cnpj_logado = $_SESSION['cnpj'];
try {
    $stmt = $pdo->prepare(
        "SELECT
            id,
            numero_tombamento,
            descricao,
            marca,
            numero_empenho,
            DATE_FORMAT(data_aquisicao, '%Y-%m-%d') AS data_aquisicao,
            numero_nota,
            setor,
            subsetor,
            unidade,
            grupo_id,
            estado,
            tipo_id,
            valor,
            (created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)) AS dentro_prazo
         FROM bens_moveis
         WHERE numero_tombamento = :numero_tombamento
           AND cnpj = :cnpj AND created_by = :usuario
         LIMIT 1"
    );
    $stmt->bindParam(':numero_tombamento', $numero_tombamento, PDO::PARAM_STR);
    $stmt->bindParam(':cnpj',              $cnpj_logado,       PDO::PARAM_STR);
	$stmt->bindParam(':usuario',              $usuario,       PDO::PARAM_STR);
    $stmt->execute();
    $bem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bem) {
        echo json_encode(null);
        exit;
    }

    // CAST para tornar a flag em bool
    $bem['dentro_prazo'] = (bool) $bem['dentro_prazo'];

    echo json_encode($bem);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(null);
}
