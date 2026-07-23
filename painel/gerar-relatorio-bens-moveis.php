<?php
session_start();
require_once 'conexao.php';

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: relatorio-bens-moveis');
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
    header('Location: relatorio-bens-moveis');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== MONTAGEM DINÂMICA DOS FILTROS (WHERE) =====
$condicoes = [];
$parametros = [];

// --- Período de Tombamento ---
if (($_POST['periodo_tombamento_filtro'] ?? 'todos') === 'periodo') {
    $dataInicio_tombamento = trim($_POST['data_inicio_tombamento'] ?? '');
    $dataFim_tombamento    = trim($_POST['data_fim_tombamento'] ?? '');

    if (empty($dataInicio_tombamento) || empty($dataFim_tombamento)) {
        $_SESSION['erro'] = 'Informe a data início e a data fim do período.';
        header('Location: relatorio-bens-moveis');
        exit;
    }

    if ($dataInicio_tombamento > $dataFim_tombamento) {
        $_SESSION['erro'] = 'A data início não pode ser maior que a data fim.';
        header('Location: relatorio-bens-moveis');
        exit;
    }

	$condicoes[] = 'b.created_at >= :data_inicio_tombamento AND b.created_at < :data_fim_tombamento';

	$parametros[':data_inicio_tombamento'] = $dataInicio_tombamento . ' 00:00:00';
	$parametros[':data_fim_tombamento'] = date('Y-m-d', strtotime($dataFim_tombamento . ' +1 day')) . ' 00:00:00';
}

// --- Período de Aquisição ---
if (($_POST['periodo_aquisicao_filtro'] ?? 'todos') === 'periodo') {
    $dataInicio_aquisicao = trim($_POST['data_inicio'] ?? '');
    $dataFim_aquisicao    = trim($_POST['data_fim'] ?? '');

    if (empty($dataInicio_aquisicao) || empty($dataFim_aquisicao)) {
        $_SESSION['erro'] = 'Informe a data início e a data fim do período.';
        header('Location: relatorio-bens-moveis');
        exit;
    }

    if ($dataInicio_aquisicao > $dataFim_aquisicao) {
        $_SESSION['erro'] = 'A data início não pode ser maior que a data fim.';
        header('Location: relatorio-bens-moveis');
        exit;
    }

    $condicoes[] = 'b.data_aquisicao BETWEEN :data_inicio AND :data_fim';
    $parametros[':data_inicio'] = $dataInicio_aquisicao;
    $parametros[':data_fim']    = $dataFim_aquisicao;
}

// --- Grupo ---
if (($_POST['grupo_filtro'] ?? 'todos') === 'descricao') {
    $grupoValor = trim($_POST['grupo_valor'] ?? '');
    if ($grupoValor !== '' && ctype_digit($grupoValor)) {
        $condicoes[] = 'b.grupo_id = :grupo_id';
        $parametros[':grupo_id'] = (int) $grupoValor;
    }
}

// --- Unidade ---
if (($_POST['unidade_filtro'] ?? 'todos') === 'descricao') {
    $unidadeValor = trim($_POST['unidade_valor'] ?? '');
    if ($unidadeValor !== '') {
        $condicoes[] = 'b.unidade = :unidade';
        $parametros[':unidade'] = $unidadeValor;
    }
}

// --- Setor Original ---
if (($_POST['setor_original_filtro'] ?? 'todos') === 'descricao') {
    $setorOriginalValor = trim($_POST['setor_original_valor'] ?? '');
    if ($setorOriginalValor !== '' && ctype_digit($setorOriginalValor)) {
        $condicoes[] = 'b.setor_original = :setor_original';
        $parametros[':setor_original'] = (int) $setorOriginalValor;
    }
}

// --- Setor ---
if (($_POST['setor_filtro'] ?? 'todos') === 'descricao') {
    $setorValor = trim($_POST['setor_valor'] ?? '');
    if ($setorValor !== '') {
        $condicoes[] = 'b.setor = :setor';
        $parametros[':setor'] = $setorValor;
    }
}

// --- SubSetor ---
if (($_POST['subsetor_filtro'] ?? 'todos') === 'descricao') {
    $subsetorValor = trim($_POST['subsetor_valor'] ?? '');
    if ($subsetorValor !== '') {
        $condicoes[] = 'b.subsetor = :subsetor';
        $parametros[':subsetor'] = $subsetorValor;
    }
}

// ===== ORDENAÇÃO (whitelist para evitar SQL injection) =====

$colunasOrdenacao = [
    'codigo'     => 'b.id',
    'tombamento' => 'b.numero_tombamento',
    'descricao'  => 'b.descricao',
];

$ordenarPorEscolhido = $_POST['ordenar_por'] ?? 'tombamento';
$colunaOrdenacao = $colunasOrdenacao[$ordenarPorEscolhido] ?? 'numero_tombamento';

// ===== MONTAGEM E EXECUÇÃO DA QUERY =====
$sql = "SELECT b.id, b.numero_tombamento, b.descricao, b.marca, so.descricao AS setor_original, b.setor, b.subsetor, b.unidade,
               g.nome AS grupo, b.estado, t.tipo, b.valor, b.data_aquisicao
        FROM bens_moveis b
        INNER JOIN grupos g ON g.id = b.grupo_id
        INNER JOIN tipos t ON t.id = b.tipo_id
        LEFT JOIN setores so ON so.id = b.setor_original";

if (!empty($condicoes)) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}

$sql .= " ORDER BY {$colunaOrdenacao} ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$bens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== MONTAGEM DO HTML DO RELATÓRIO =====
$dataEmissao = date('d/m/Y H:i');
$totalRegistros = count($bens);
$valorTotal = array_sum(array_column($bens, 'valor'));

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
            font-size: 10px;
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
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
        }

        tbody td {
            padding: 5px 4px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }

        tbody tr:nth-child(even) {
            background: #f7f7f7;
        }

        .valor {
            text-align: right;
        }

        tfoot td {
            padding: 8px 4px;
            font-weight: bold;
            border-top: 2px solid #333;
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
        <h2>Relatório de Bens Móveis</h2>
			<?php if (isset($dataInicio_tombamento)): ?>
				<p>Período de tombamento: <?= date('d/m/Y', strtotime($dataInicio_tombamento)); ?> até <?= date('d/m/Y', strtotime($dataFim_tombamento)); ?></p>
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
        <p class="sem-resultados">Nenhum bem encontrado para os filtros selecionados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tombamento</th>
                    <th>Descrição</th>
                    <th>Marca</th>
                    <th>Setor Original</th>
                    <th>Setor</th>
                    <th>SubSetor</th>
                    <th>Unidade</th>
                    <th>Grupo</th>
                    <th>Estado</th>
                    <th>Tipo</th>
                    <th>Aquisição</th>
                    <th class="valor">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bens as $bem): ?>
                    <tr>
                        <td><?= htmlspecialchars($bem['numero_tombamento']) ?></td>
                        <td><?= htmlspecialchars($bem['descricao']) ?></td>
                        <td><?= htmlspecialchars($bem['marca'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($bem['setor_original'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($bem['setor']) ?></td>
                        <td><?= htmlspecialchars($bem['subsetor']) ?></td>
                        <td><?= htmlspecialchars($bem['unidade'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($bem['grupo']) ?></td>
                        <td><?= htmlspecialchars($bem['estado']) ?></td>
                        <td><?= htmlspecialchars($bem['tipo']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($bem['data_aquisicao']))) ?></td>
                        <td class="valor">R$ <?= number_format((float)$bem['valor'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="11">Valor total</td>
                    <td class="valor">R$ <?= number_format($valorTotal, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
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

// Exibe o PDF diretamente no navegador (sem forçar download)
$dompdf->stream('relatorio-bens-moveis-' . date('Y-m-d_His') . '.pdf', ['Attachment' => false]);
exit;