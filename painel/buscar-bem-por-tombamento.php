<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

// ===== AUTENTICAÇÃO =====
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['encontrado' => false, 'erro' => 'Não autenticado.']);
    exit;
}

$cnpj = $_SESSION['cnpj'];

// ===== VALIDAÇÃO DO PARÂMETRO =====
$numero_tombamento = trim($_GET['numero_tombamento'] ?? '');

if ($numero_tombamento === '' || !ctype_digit($numero_tombamento)) {
    http_response_code(400);
    echo json_encode(['encontrado' => false, 'erro' => 'Número de tombamento inválido.']);
    exit;
}

// ===== BUSCA NO BANCO =====
try {
    $stmt = $pdo->prepare(
        "SELECT numero_empenho, data_aquisicao, numero_nota, setor, subsetor, tipo
         FROM bens_moveis
         WHERE numero_tombamento = :t AND cnpj = :c
         LIMIT 1"
    );
    $stmt->bindParam(':t', $numero_tombamento, PDO::PARAM_STR);
    $stmt->bindParam(':c', $cnpj, PDO::PARAM_STR);
    $stmt->execute();
    $bem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bem) {
        echo json_encode(['encontrado' => false]);
        exit;
    }

    echo json_encode([
        'encontrado'     => true,
        'numero_empenho' => $bem['numero_empenho'],
        'data_aquisicao' => $bem['data_aquisicao'],
        'numero_nota'    => $bem['numero_nota'],
        'setor'          => $bem['setor'],
        'subsetor'       => $bem['subsetor'],
        'tipo'           => $bem['tipo'],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['encontrado' => false, 'erro' => 'Erro interno. Tente novamente.']);
}
