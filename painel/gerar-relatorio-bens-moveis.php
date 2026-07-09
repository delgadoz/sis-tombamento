<?php
session_start();
require_once 'conexao.php'; // arquivo de conexão PDO

// Requer o autoload do Composer (necessário: composer require dompdf/dompdf)
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Bloqueia qualquer método que não seja POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: relatorio-bens-moveis');
    exit;
}

// Redireciona para login se não houver sessão ativa
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

// --- Aquisição (período) ---
if (($_POST['aquisicao_filtro'] ?? 'todos') === 'periodo') {
    $dataInicio = trim($_POST['data_inicio'] ?? '');
    $dataFim    = trim($_POST['data_fim'] ?? '');

    if (empty($dataInicio) || empty($dataFim)) {
        $_SESSION['erro'] = 'Informe a data início e a data fim do período.';
        header('Location: relatorio-bens-moveis');
        exit;
    }

    if ($dataInicio > $dataFim) {
        $_SESSION['erro'] = 'A data início não pode ser maior que a data fim.';
        header('Location: relatorio-bens-moveis');
        exit;
    }

    $condicoes[] = 'data_aquisicao BETWEEN :data_inicio AND :data_fim';
    $parametros[':data_inicio'] = $dataInicio;
    $parametros[':data_fim']    = $dataFim;
}

// --- Grupo ---
if (($_POST['grupo_filtro'] ?? 'todos') === 'descricao') {
    $grupoValor = trim($_POST['grupo_valor'] ?? '');
    if ($grupoValor !== '') {
        $condicoes[] = 'grupo = :grupo';
        $parametros[':grupo'] = $grupoValor;
    }
}

// --- Unidade ---
if (($_POST['unidade_filtro'] ?? 'todos') === 'descricao') {
    $unidadeValor = trim($_POST['unidade_valor'] ?? '');
    if ($unidadeValor !== '') {
        $condicoes[] = 'unidade = :unidade';
        $parametros[':unidade'] = $unidadeValor;
    }
}

// --- Setor ---
if (($_POST['setor_filtro'] ?? 'todos') === 'descricao') {
    $setorValor = trim($_POST['setor_valor'] ?? '');
    if ($setorValor !== '') {
        $condicoes[] = 'setor = :setor';
        $parametros[':setor'] = $setorValor;
    }
}

// --- SubSetor ---
if (($_POST['subsetor_filtro'] ?? 'todos') === 'descricao') {
    $subsetorValor = trim($_POST['subsetor_valor'] ?? '');
    if ($subsetorValor !== '') {
        $condicoes[] = 'subsetor = :subsetor';
        $parametros[':subsetor'] = $subsetorValor;
    }
}

// ===== ORDENAÇÃO (whitelist para evitar SQL injection) =====

$colunasOrdenacao = [
    'codigo'     => 'id',
    'tombamento' => 'numero_tombamento',
    'descricao'  => 'descricao',
];

$ordenarPorEscolhido = $_POST['ordenar_por'] ?? 'tombamento';
$colunaOrdenacao = $colunasOrdenacao[$ordenarPorEscolhido] ?? 'numero_tombamento';

// ===== MONTAGEM E EXECUÇÃO DA QUERY =====
$sql = "SELECT id, numero_tombamento, descricao, marca, setor, subsetor, unidade, grupo, estado, tipo, valor, data_aquisicao
        FROM bens_moveis";

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
        <hr>
    </header>

    <footer>
        Emitido em <?= htmlspecialchars($dataEmissao) ?> — Página <script type="text/php">
            if (isset($pdf)) {
                $text = "{PAGE_NUM} de {PAGE_COUNT}";
                $font = $fontMetrics->get_font("Helvetica", "normal");
                $pdf->page_text(270, 800, $text, $font, 8, array(0.4,0.4,0.4));
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
                    <td colspan="10">Valor total</td>
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
