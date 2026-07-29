<?php
session_start();
require_once 'conexao.php';

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: relatorio-movimentos');
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

// ===== PROTEÇÃO CSRF =====
$token_recebido = $_POST['csrf_token']   ?? '';
$token_sessao   = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !hash_equals($token_sessao, $token_recebido)) {
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: relatorio-movimentos');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== MONTAGEM DINÂMICA DOS FILTROS (WHERE) =====
// Só entram no relatório os registros de log_auditoria referentes a
// mudança de setor/subsetor/unidade de bens móveis (ação 'movimento'),
// mesmo que a edição tenha gerado também um registro de ação 'edicao'.
$condicoes = ['la.acao = :acao_movimento', 'la.tabela_afetada = :tabela_afetada'];
$parametros = [
    ':acao_movimento' => 'movimento',
    ':tabela_afetada' => 'bens_moveis',
];

// --- Período da Movimentação ---
if (($_POST['periodo_movimentacao_filtro'] ?? 'todos') === 'periodo') {
    $dataInicio_movimentacao = trim($_POST['data_inicio_movimentacao'] ?? '');
    $dataFim_movimentacao    = trim($_POST['data_fim_movimentacao'] ?? '');

    if (empty($dataInicio_movimentacao) || empty($dataFim_movimentacao)) {
        $_SESSION['erro'] = 'Informe a data início e a data fim do período.';
        header('Location: relatorio-movimentos');
        exit;
    }

    if ($dataInicio_movimentacao > $dataFim_movimentacao) {
        $_SESSION['erro'] = 'A data início não pode ser maior que a data fim.';
        header('Location: relatorio-movimentos');
        exit;
    }

    $condicoes[] = 'la.criado_em >= :data_inicio_movimentacao AND la.criado_em < :data_fim_movimentacao';

    $parametros[':data_inicio_movimentacao'] = $dataInicio_movimentacao . ' 00:00:00';
    $parametros[':data_fim_movimentacao'] = date('Y-m-d', strtotime($dataFim_movimentacao . ' +1 day')) . ' 00:00:00';
}

// ===== ORDENAÇÃO (whitelist para evitar SQL injection) =====

$colunasOrdenacao = [
    'codigo'     => 'b.id',
    'tombamento' => 'b.numero_tombamento',
    'descricao'  => 'b.descricao',
];

$ordenarPorEscolhido = $_POST['ordenar_por'] ?? 'tombamento';
$colunaOrdenacao = $colunasOrdenacao[$ordenarPorEscolhido] ?? 'b.numero_tombamento';

// ===== MONTAGEM E EXECUÇÃO DA QUERY =====
// dados_antes / dados_depois guardam o estado de setor/subsetor/unidade
// antes e depois da movimentação, em formato JSON.
$sql = "SELECT la.id, la.criado_em, u.nome AS usuario_nome,
               b.numero_tombamento, b.descricao,
               JSON_UNQUOTE(JSON_EXTRACT(la.dados_antes,  '\$.setor'))    AS setor_origem,
               JSON_UNQUOTE(JSON_EXTRACT(la.dados_antes,  '\$.subsetor')) AS subsetor_origem,
               JSON_UNQUOTE(JSON_EXTRACT(la.dados_antes,  '\$.unidade'))  AS unidade_origem,
               JSON_UNQUOTE(JSON_EXTRACT(la.dados_depois, '\$.setor'))    AS setor_destino,
               JSON_UNQUOTE(JSON_EXTRACT(la.dados_depois, '\$.subsetor')) AS subsetor_destino,
               JSON_UNQUOTE(JSON_EXTRACT(la.dados_depois, '\$.unidade'))  AS unidade_destino
        FROM log_auditoria la
        INNER JOIN usuarios u ON u.id = la.usuario_id
        LEFT JOIN bens_moveis b ON b.id = la.registro_id";

$sql .= ' WHERE ' . implode(' AND ', $condicoes);
$sql .= " ORDER BY {$colunaOrdenacao} ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== FUNÇÃO AUXILIAR PARA CAMPOS VAZIOS =====
// Trata tanto NULL (chave ausente no JSON) quanto string vazia
// (ex.: unidade_destino = "" quando o bem deixou de ter unidade).
function formatarCampo(?string $valor): string
{
    return htmlspecialchars(($valor === null || $valor === '') ? '-' : $valor);
}

// ===== MONTAGEM DO HTML DO RELATÓRIO =====
$dataEmissao = date('d/m/Y H:i');
$totalRegistros = count($movimentos);

// ===== MARCA D'ÁGUA (brasão da prefeitura) =====
$caminhoLogo = __DIR__ . '/imgs/brasao.png';
$marcaDaguaBase64 = '';
if (file_exists($caminhoLogo)) {
    $tipoImagem = mime_content_type($caminhoLogo);
    $conteudoImagem = file_get_contents($caminhoLogo);
    $marcaDaguaBase64 = 'data:' . $tipoImagem . ';base64,' . base64_encode($conteudoImagem);
}

ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 90px 30px 60px 30px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #222;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            text-align: center;
        }

        header h1 {
            font-size: 16px;
            margin: 0;
            color: #333;
        }

        header h2 {
            font-size: 12px;
            font-weight: normal;
            margin: 4px 0 0;
            color: #555;
        }

        header hr {
            border: none;
            border-top: 2px solid #ff9800;
            margin-top: 8px;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #777;
            text-align: center;
        }

        .info-relatorio {
            margin-bottom: 12px;
            font-size: 9px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #ff9800;
            color: #000;
            padding: 6px 3px;
            text-align: left;
            font-size: 8px;
        }

        tbody td {
            padding: 5px 3px;
            border-bottom: 1px solid #ddd;
            font-size: 8px;
        }

        tbody tr:nth-child(even) {
            background: #f7f7f7;
        }

        .sem-resultados {
            text-align: center;
            padding: 20px;
            color: #888;
        }

        .marca-dagua {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 480px;
            margin-left: -240px;
            margin-top: -240px;
            opacity: 0.09;
            z-index: -1;
        }

        .marca-dagua img {
            width: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <?php if ($marcaDaguaBase64): ?>
        <div class="marca-dagua">
            <img src="<?= $marcaDaguaBase64 ?>" alt="">
        </div>
    <?php endif; ?>

    <header>
        <h1>Prefeitura Municipal de Caraúbas - PB</h1>
        <h2>Relatório de Movimentações</h2>
			<?php if (isset($dataInicio_movimentacao)): ?>
				<p>Período da movimentação: <?= date('d/m/Y', strtotime($dataInicio_movimentacao)); ?> até <?= date('d/m/Y', strtotime($dataFim_movimentacao)); ?></p>
			<?php endif; ?>
        <hr>
    </header>

    <footer>
        <script type="text/php">
            if (isset($pdf)) {
                $texto = "Emitido em <?= addslashes($dataEmissao) ?> — Página {PAGE_NUM} de {PAGE_COUNT}";
                $font  = $fontMetrics->getFont("Helvetica", "normal");
                $tamanhoFonte = 8;

                $larguraTexto  = $fontMetrics->getTextWidth($texto, $font, $tamanhoFonte);
                $larguraPagina = $pdf->get_width();
                $x = ($larguraPagina - $larguraTexto) / 2; // centralizado
                $y = $pdf->get_height() - 25;              // 25pt acima da borda inferior

                $pdf->page_text($x, $y, $texto, $font, $tamanhoFonte, array(0.47, 0.47, 0.47));
            }
        </script>
    </footer>

    <div class="info-relatorio">
        Total de registros: <?= $totalRegistros ?>
    </div>

    <?php if ($totalRegistros === 0): ?>
        <p class="sem-resultados">Nenhuma movimentação encontrada para o período selecionado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Usuário</th>
                    <th>Tombamento</th>
                    <th>Descrição</th>
                    <th>Setor Origem</th>
                    <th>SubSetor Origem</th>
                    <th>Unidade Origem</th>
                    <th>Setor Destino</th>
                    <th>SubSetor Destino</th>
                    <th>Unidade Destino</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimentos as $mov): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($mov['criado_em']))) ?></td>
                        <td><?= htmlspecialchars(date('H:i:s', strtotime($mov['criado_em']))) ?></td>
                        <td><?= htmlspecialchars($mov['usuario_nome']) ?></td>
                        <td><?= htmlspecialchars($mov['numero_tombamento'] ?? '(bem excluído)') ?></td>
                        <td><?= htmlspecialchars($mov['descricao'] ?? '-') ?></td>
                        <td><?= formatarCampo($mov['setor_origem']) ?></td>
                        <td><?= formatarCampo($mov['subsetor_origem']) ?></td>
                        <td><?= formatarCampo($mov['unidade_origem']) ?></td>
                        <td><?= formatarCampo($mov['setor_destino']) ?></td>
                        <td><?= formatarCampo($mov['subsetor_destino']) ?></td>
                        <td><?= formatarCampo($mov['unidade_destino']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// ===== GERAÇÃO DO PDF COM DOMPDF =====
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isPhpEnabled', true); // necessário para o {PAGE_NUM} de {PAGE_COUNT} no rodapé

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();

// ===== GRAVA O PDF EM ARQUIVO TEMPORÁRIO E REDIRECIONA PARA ROTA GET =====

$diretorioTemp = __DIR__ . '/tmp_relatorios';
if (!is_dir($diretorioTemp)) {
    mkdir($diretorioTemp, 0770, true);
}

// Remove o relatório anterior desta sessão (se houver) antes de gerar o novo
if (!empty($_SESSION['relatorio_movimentos_pdf_arquivo'])) {
    $arquivoAnterior = $diretorioTemp . '/' . basename($_SESSION['relatorio_movimentos_pdf_arquivo']);
    if (is_file($arquivoAnterior)) {
        @unlink($arquivoAnterior);
    }
}

// Limpeza best-effort de relatórios órfãos (sessões abandonadas) com mais de 2h
foreach (glob($diretorioTemp . '/*.pdf') ?: [] as $arquivoAntigo) {
    if (filemtime($arquivoAntigo) < time() - 7200) {
        @unlink($arquivoAntigo);
    }
}

$nomeArquivo = 'relatorio-movimentos-' . date('Y-m-d_His') . '-' . bin2hex(random_bytes(4)) . '.pdf';
file_put_contents($diretorioTemp . '/' . $nomeArquivo, $dompdf->output());

$_SESSION['relatorio_movimentos_pdf_arquivo'] = $nomeArquivo;

header('Location: baixar-relatorio-movimentos.php');
exit;
