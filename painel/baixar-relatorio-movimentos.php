<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

if (empty($_SESSION['relatorio_movimentos_pdf_arquivo'])) {
    header('Location: relatorio-movimentos');
    exit;
}

// basename() evita path traversal (../../etc)
$nomeArquivo = basename($_SESSION['relatorio_movimentos_pdf_arquivo']);
$caminho = __DIR__ . '/tmp_relatorios/' . $nomeArquivo;

if (!is_file($caminho)) {
    unset($_SESSION['relatorio_movimentos_pdf_arquivo']);
    header('Location: relatorio-movimentos');
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');

readfile($caminho);
exit;
