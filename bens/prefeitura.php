<?php
require_once '../painel/conexao.php';

try {
    if (!empty($_GET['tombamento'])) {
        $stmt = $pdo->prepare("SELECT b.*, t.tipo FROM bens_moveis b INNER JOIN tipos t ON t.id = b.tipo_id WHERE b.numero_tombamento = :tombamento and b.cnpj = 'prefeitura' LIMIT 1");
        $stmt->bindParam(':tombamento', $_GET['tombamento'], PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $stmt = $pdo->query("SELECT b.*, t.tipo FROM bens_moveis b INNER JOIN tipos t ON t.id = b.tipo_id WHERE b.cnpj = 'prefeitura' ORDER BY b.id ASC LIMIT 1");
    }

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header('Location: ../index.php?msg-alert=Patrimônio não encontrado!');
        exit;
    }

    $stmtPrev = $pdo->prepare("
        SELECT numero_tombamento FROM bens_moveis 
        WHERE id < :id 
        AND cnpj = 'prefeitura'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmtPrev->execute([':id' => $item['id']]);
    $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    $stmtNext = $pdo->prepare("
        SELECT numero_tombamento FROM bens_moveis 
        WHERE id > :id 
        AND cnpj = 'prefeitura'
        ORDER BY id ASC 
        LIMIT 1
    ");
    $stmtNext->execute([':id' => $item['id']]);
    $next = $stmtNext->fetch(PDO::FETCH_ASSOC);

    $imagens = json_decode($item['imagens'], true);
    if (!is_array($imagens)) {
        $imagens = [];
    }

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
<title>Patrimônio Público — Detalhes do Bem</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --green-dark:   #005c00;
    --green-main:   #008d00;
    --green-mid:    #1aa01a;
    --green-light:  #e6f5e6;
    --green-pale:   #f2faf2;
    --text-dark:    #1a2b1a;
    --text-mid:     #3d563d;
    --text-muted:   #6b826b;
    --border:       #cce0cc;
    --white:        #ffffff;
    --bg:           #f4f9f4;
}

body {
    font-family: 'DM Sans', sans-serif;
    background-color: var(--bg);
    color: var(--text-dark);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── HEADER ── */
.site-header {
    background: var(--green-dark);
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 72px;
    border-bottom: 2px solid #e30909
;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo-placeholder {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    background: #fff;
    border: 1.5px dashed rgba(255,255,255,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
	object-fit: contain;
}

/* Para usar logo real, substitua .logo-placeholder por uma <img>:
   <img src="logo-prefeitura.png" alt="Logotipo" class="logo-placeholder"> */

.logo-placeholder svg {
    width: 24px;
    height: 24px;
    opacity: 0.5;
}

.logo-text {
    line-height: 1.25;
}

.logo-text .municipio {
    font-family: 'Lora', serif;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    letter-spacing: 0.01em;
}

.logo-text .orgao {
    font-size: 11px;
    font-weight: 300;
    color: rgba(255,255,255,0.65);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.header-badge {
    font-size: 11px;
    font-weight: 500;
    color: rgba(255,255,255,0.7);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    border: 1px solid rgba(255,255,255,0.2);
    padding: 5px 12px;
    border-radius: 20px;
}

/* ── MAIN LAYOUT ── */
main {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 36px 20px 48px;
}

.card {
    background: var(--white);
    max-width: 860px;
    width: 100%;
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 2px 24px rgba(0, 141, 0, 0.07);
}

/* ── CARD TITLE BAR ── */
.card-titlebar {
    background: var(--green-pale);
    border-bottom: 1px solid var(--border);
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.card-titlebar h1 {
    font-family: 'Lora', serif;
    font-size: 17px;
    font-weight: 600;
    color: var(--green-dark);
    letter-spacing: 0.01em;
}

.tombamento-tag {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    background: var(--green-main);
    color: #fff;
    padding: 5px 14px;
    border-radius: 20px;
}

/* ── IMAGE GALLERY ── */
.gallery {
    position: relative;
    background: #eef5ee;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    border-bottom: 1px solid var(--border);
    overflow: hidden;
}

.gallery img#img {
    max-height: 320px;
    max-width: 100%;
    object-fit: contain;
    display: block;
    padding: 24px;
    transition: opacity 0.2s ease;
}

.gallery .no-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 14px;
}

.gallery .no-image svg {
    width: 48px;
    height: 48px;
    opacity: 0.35;
}

.arrow-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    background: rgba(0, 80, 0, 0.65);
    color: #fff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.18s ease;
    line-height: 1;
}

.arrow-btn:hover {
    background: var(--green-dark);
}

.arrow-btn.left  { left: 14px; }
.arrow-btn.right { right: 14px; }

.img-counter {
    position: absolute;
    bottom: 12px;
    right: 16px;
    font-size: 11px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
    background: rgba(0,60,0,0.55);
    padding: 3px 10px;
    border-radius: 20px;
}

/* ── INFO GRID ── */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
    border-bottom: 1px solid var(--border);
}

.info-cell {
    padding: 20px 28px;
    border-bottom: 1px solid var(--border);
    border-right: 1px solid var(--border);
}

.info-cell:nth-child(even) {
    border-right: none;
}

.info-cell:nth-last-child(-n+2) {
    border-bottom: none;
}

.info-cell .label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--green-main);
    margin-bottom: 5px;
}

.info-cell .value {
    font-size: 15px;
    font-weight: 400;
    color: var(--text-dark);
    line-height: 1.4;
}

/* ── DESCRIPTION ── */
.description-block {
    padding: 24px 28px;
    border-bottom: 1px solid var(--border);
}

.description-block .label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--green-main);
    margin-bottom: 8px;
}

.description-block p {
    font-size: 14px;
    color: var(--text-mid);
    line-height: 1.75;
}

/* ── NAV BUTTONS ── */
.nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
}

.nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    color: var(--green-dark);
    padding: 9px 18px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    background: var(--white);
    transition: all 0.18s ease;
    letter-spacing: 0.01em;
}

.nav-btn:hover {
    background: var(--green-dark);
    color: #fff;
    border-color: var(--green-dark);
}

.nav-btn svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

.nav-spacer { flex: 1; }

/* ── FOOTER ── */
footer {
    background: var(--green-dark);
    border-top: 2px solid #e30909;
    padding: 20px 24px;
    text-align: center;
}

footer p {
    font-size: 12px;
    color: rgba(255,255,255,0.55);
    letter-spacing: 0.03em;
}

footer p strong {
    color: rgba(255,255,255,0.8);
    font-weight: 500;
}

footer .divider {
    display: inline-block;
    margin: 0 8px;
    opacity: 0.3;
}

/* ── RESPONSIVE ── */
@media (max-width: 580px) {
    .site-header { height: auto; padding: 14px 16px; }
    .header-badge { display: none; }
    .info-grid { grid-template-columns: 1fr; }
    .info-cell { border-right: none !important; }
    .info-cell:nth-last-child(-n+2) { border-bottom: 1px solid var(--border); }
    .info-cell:last-child { border-bottom: none; }
    .card-titlebar { flex-direction: column; align-items: flex-start; }
    .nav-bar { gap: 10px; flex-wrap: wrap; }
    main { padding: 20px 12px 36px; }
}
</style>
</head>
<body>

<!-- ══ CABEÇALHO ══ -->
<header class="site-header">
    <div class="logo-area">
        <div class="logo-placeholder">
            <img src="imgs/logo-prefeitura.png" alt="Logotipo da Prefeitura" class="logo-placeholder">
        </div>
        <div class="logo-text">
            <div class="municipio">Prefeitura Municipal</div>
            <div class="orgao">Controle Patrimonial</div>
        </div>
    </div>
</header>

<!-- ══ CONTEÚDO PRINCIPAL ══ -->
<main>
    <div class="card">

        <!-- Barra de título -->
        <div class="card-titlebar">
            <h1>Detalhes do Bem Patrimonial</h1>
            <span class="tombamento-tag">
                PAT-<?= $ano ?>-<?= htmlspecialchars($item['numero_tombamento']) ?>
            </span>
        </div>

        <!-- Galeria de imagens -->
        <div class="gallery">
            <?php if (count($imagens) > 0): ?>
                <?php if (count($imagens) > 1): ?>
                    <button class="arrow-btn left" onclick="prevImage()" aria-label="Imagem anterior">&#8249;</button>
                <?php endif; ?>
                <img id="img" src="<?= htmlspecialchars($imagens[0]) ?>" alt="Imagem do bem patrimonial">
                <?php if (count($imagens) > 1): ?>
                    <button class="arrow-btn right" onclick="nextImage()" aria-label="Próxima imagem">&#8250;</button>
                    <span class="img-counter" id="counter">1 / <?= count($imagens) ?></span>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21"/>
                    </svg>
                    <span>Sem imagem cadastrada</span>
                </div>
            <?php endif; ?>
        </div>
		
		<!-- Descrição -->
        <div class="description-block">
            <div class="label">Descrição do Item</div>
            <p><?= htmlspecialchars($item['descricao']) ?></p>
        </div>

        <!-- Grade de informações -->
        <div class="info-grid">
		
			<div class="info-cell">
                <div class="label">Marca</div>
                <div class="value"><?= htmlspecialchars($item['marca']) ?></div>
            </div>
			
			<div class="info-cell">
                <div class="label">Estado de Conservação</div>
                <div class="value"><?= htmlspecialchars($item['estado']) ?></div>
            </div>
			
			<div class="info-cell">
                <div class="label">Tipo</div>
                <div class="value"><?= ucfirst(htmlspecialchars($item['tipo'])); ?></div>
            </div>
			
			<div class="info-cell">
                <div class="label">Data de Aquisição</div>
                <div class="value"><?= date('d/m/Y', strtotime($item['data_aquisicao'])) ?></div>
            </div>

            <div class="info-cell">
                <div class="label">Nº de Tombamento</div>
                <div class="value"><?= htmlspecialchars($item['numero_tombamento']) ?></div>
            </div>

            <div class="info-cell">
                <div class="label">Localização</div>
                <div class="value">
                    <?= htmlspecialchars($item['setor']) ?> — <?= htmlspecialchars($item['subsetor']) ?>
                    <?= !empty($item['unidade']) ? '<br><small style="color:var(--text-muted)">' . htmlspecialchars($item['unidade']) . '</small>' : '' ?>
                </div>
            </div>

        </div>

        <!-- Navegação entre registros -->
        <div class="nav-bar">
            <?php if ($prev): ?>
                <a class="nav-btn" href="?tombamento=<?= urlencode($prev['numero_tombamento']) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                    Anterior
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <?php if ($next): ?>
                <a class="nav-btn" href="?tombamento=<?= urlencode($next['numero_tombamento']) ?>">
                    Próximo
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- ══ RODAPÉ ══ -->
<footer>
    <p>
        Sistema de Controle Patrimonial
        <span class="divider">|</span>
        <strong><a href="https://caraubas.pb.gov.br" style="color:#fff;" target="_blank">Prefeitura Municipal de Caraúbas</a></strong> &copy; <?= date('Y') ?> Todos os direitos reservados
    </p>
</footer>

<script>
let imagens = <?= json_encode($imagens) ?>;
let index = 0;
const total = imagens.length;

function showImage() {
    const el = document.getElementById("img");
    if (!el) return;
    el.style.opacity = '0';
    setTimeout(() => {
        el.src = imagens[index];
        el.style.opacity = '1';
    }, 150);
    const counter = document.getElementById("counter");
    if (counter) counter.textContent = (index + 1) + ' / ' + total;
}

function nextImage() {
    if (total > 1) { index = (index + 1) % total; showImage(); }
}

function prevImage() {
    if (total > 1) { index = (index - 1 + total) % total; showImage(); }
}
</script>

</body>
</html>