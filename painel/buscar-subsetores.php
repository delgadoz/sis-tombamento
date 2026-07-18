<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([]);
    exit;
}

$setor = trim($_GET['setor'] ?? '');

if (empty($setor)) {
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $sql  = "SELECT descricao FROM subsetores WHERE setor = :setor ORDER BY descricao ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':setor', $setor, PDO::PARAM_STR);
    $stmt->execute();

    $subsetores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($subsetores);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([]);
}
