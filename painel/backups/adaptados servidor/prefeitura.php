<?php
require_once '../painel/conexao.php';

try {
    // Verifica se veio tombamento via GET
    if (!empty($_GET['tombamento'])) {
        $stmt = $pdo->prepare("SELECT * FROM bens_moveis WHERE numero_tombamento = :tombamento and cnpj = 'prefeitura' LIMIT 1");
        $stmt->bindParam(':tombamento', $_GET['tombamento'], PDO::PARAM_STR);
        $stmt->execute();
    } else {
        // Busca o primeiro registro
        $stmt = $pdo->query("SELECT * FROM bens_moveis WHERE cnpj = 'prefeitura' ORDER BY id ASC LIMIT 1");
    }

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo "Não existe nada no momento.";
        exit;
    }
	
		// Buscar item anterior
	$stmtPrev = $pdo->prepare("
		SELECT numero_tombamento FROM bens_moveis 
		WHERE id < :id 
		AND cnpj = 'prefeitura'
		ORDER BY id DESC 
		LIMIT 1
	");
	$stmtPrev->execute([':id' => $item['id']]);
	$prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

	// Buscar próximo item
	$stmtNext = $pdo->prepare("
		SELECT numero_tombamento FROM bens_moveis 
		WHERE id > :id 
		AND cnpj = 'prefeitura'
		ORDER BY id ASC 
		LIMIT 1
	");
	$stmtNext->execute([':id' => $item['id']]);
	$next = $stmtNext->fetch(PDO::FETCH_ASSOC);

    // Processar imagens JSON
    $imagens = json_decode($item['imagens'], true);
    if (!is_array($imagens)) {
        $imagens = [];
    }

    // Ano da data
    $ano = date('Y', strtotime($item['data_aquisicao']));

} catch (Exception $e) {
    echo "Erro ao carregar dados.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalhes do Patrimônio</title>

<style>
/* (mantive seu CSS original) */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

body {
    background-color: #f5f7fb;
    display: flex;
    justify-content: center;
    padding: 20px;
}

.container {
    background: #fff;
    max-width: 900px;
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.header { padding: 20px; text-align: center; }

.header h1 { margin-top: 8px; font-size: 22px; color: #333; }

.tag {
    background: #e6f0ff;
    color: #2b6cb0;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    display: inline-block;
    margin-top: 10px;
}

.image-box {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #f0f0f0;
    padding: 30px;
}

.image-box img {
    max-width: 250px;
}

.arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 30px;
    cursor: pointer;
    background: rgba(0,0,0,0.3);
    color: #fff;
    padding: 5px 10px;
    border-radius: 5px;
}

.left { left: 10px; }
.right { right: 10px; }

.info {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    border-top: 1px solid #eee;
}

.info-item span {
    display: block;
    color: #888;
    font-size: 12px;
}

.description {
    padding: 20px;
    border-top: 1px solid #eee;
}

@media (max-width: 600px) {
    .info { grid-template-columns: 1fr; }
}

.nav-buttons {
    display: flex;
    justify-content: space-between;
    padding: 20px;
    border-top: 1px solid #eee;
}

.nav-btn {
    text-decoration: none;
    color: #111;
    font-size: 14px;
    font-weight: 500;
    padding: 10px 16px;
    border-radius: 8px;
    border: 1px solid #ddd;
    transition: all 0.2s ease;
    font-family: 'Segoe UI', Arial, sans-serif;
}

.nav-btn:hover {
    background-color: #111;
    color: #fff;
    border-color: #111;
}
</style>

<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>DETALHES DO PATRIMÔNIO</h1>
        <div class="tag">
            PAT-<?= $ano ?>-<?= htmlspecialchars($item['numero_tombamento']) ?>
        </div>
    </div>

    <div class="image-box">
        <?php if (count($imagens) > 0): ?>
            <span class="arrow left" onclick="prevImage()">‹</span>
            <img id="img" src="<?= htmlspecialchars('../painel/' . $imagens[0]) ?>">
            <span class="arrow right" onclick="nextImage()">›</span>
        <?php else: ?>
            <span>Sem imagem</span>
        <?php endif; ?>
    </div>

    <div class="info">

        <div class="info-item">
            <strong>Nº de Tombamento</strong><br>
            <?= htmlspecialchars($item['numero_tombamento']) ?>
        </div>

        <div class="info-item">
            <strong>Localização</strong><br>
            <?= htmlspecialchars($item['setor']) ?> —
            <?= htmlspecialchars($item['subsetor']) ?>
            <?= !empty($item['unidade']) ? ', ' . htmlspecialchars($item['unidade']) : '' ?>
        </div>

        <div class="info-item">
            <strong>Data de Aquisição</strong><br>
            <?= date('d/m/Y', strtotime($item['data_aquisicao'])) ?>
        </div>

        <div class="info-item">
            <strong>Estado de Conservação</strong><br>
            <?= htmlspecialchars($item['estado']) ?>
        </div>

    </div>

    <div class="description">
        <strong>Descrição do Item</strong><br><br>
        <?= htmlspecialchars($item['descricao']) ?>
    </div>
	
	<div class="nav-buttons">

		<?php if ($prev): ?>
			<a class="nav-btn" href="?tombamento=<?= urlencode($prev['numero_tombamento']) ?>">
				← Anterior
			</a>
		<?php else: ?>
			<span></span>
		<?php endif; ?>

		<?php if ($next): ?>
			<a class="nav-btn" href="?tombamento=<?= urlencode($next['numero_tombamento']) ?>">
				Próximo →
			</a>
		<?php endif; ?>

	</div>

</div>

<script>
let imagens = <?= json_encode($imagens) ?>;
let index = 0;

function showImage() {
    document.getElementById("img").src = '../painel/' + imagens[index];
}

function nextImage() {
    if (imagens.length > 1) {
        index = (index + 1) % imagens.length;
        showImage();
    }
}

function prevImage() {
    if (imagens.length > 1) {
        index = (index - 1 + imagens.length) % imagens.length;
        showImage();
    }
}

lucide.createIcons();
</script>

</body>
</html>