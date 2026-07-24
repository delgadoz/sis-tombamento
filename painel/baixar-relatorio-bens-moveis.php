<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

if (empty($_SESSION['relatorio_pdf_arquivo'])) {
    header('Location: relatorio-bens-moveis');
    exit;
}

// basename() evita path traversal (../../etc)
$nomeArquivo = basename($_SESSION['relatorio_pdf_arquivo']);
$caminho = __DIR__ . '/tmp_relatorios/' . $nomeArquivo;

if (!is_file($caminho)) {
    unset($_SESSION['relatorio_pdf_arquivo']);
    header('Location: relatorio-bens-moveis');
    exit;
}

// Importante: NÃO apagar o arquivo nem limpar a sessão aqui.
// O visualizador de PDF do navegador pode fazer mais de um GET nesta
// mesma URL (um para exibir, outro quando o usuário clica em salvar),
// então o arquivo precisa continuar disponível para requisições repetidas.
// A limpeza acontece em gerar-relatorio-bens-moveis.php (relatório
// seguinte) e pela rotina de expiração por idade.

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');

readfile($caminho);
exit;
