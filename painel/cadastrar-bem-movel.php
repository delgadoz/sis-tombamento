<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];
$cnpj = $_SESSION['cnpj'];

// Busca os setores cadastrados
try {
    $stmt    = $pdo->query("SELECT id, descricao FROM setores ORDER BY descricao ASC");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $setores = [];
}

// Busca os grupos cadastrados
try {
    $stmtG  = $pdo->query("SELECT id, nome FROM grupos ORDER BY nome ASC");
    $grupos = $stmtG->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $grupos = [];
}

// Busca os tipos cadastrados
try {
    $stmtTp = $pdo->query("SELECT id, tipo FROM tipos ORDER BY tipo ASC");
    $tipos  = $stmtTp->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tipos = [];
}

// Busca o último número de tombamento cadastrado pelo usuário logado
$proximoTombamento = 1;

try {
    $stmtT = $pdo->prepare(
        "SELECT numero_tombamento FROM bens_moveis
         WHERE created_by = :usuario_id AND cnpj = :cnpj
         ORDER BY CAST(numero_tombamento AS UNSIGNED) DESC
         LIMIT 1"
    );
    $stmtT->bindParam(':usuario_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
	$stmtT->bindParam(':cnpj', $_SESSION['cnpj'], PDO::PARAM_STR);
    $stmtT->execute();
    $ultimoT = $stmtT->fetchColumn();
    $proximoTombamento = ($ultimoT !== false && is_numeric($ultimoT))
        ? (int)$ultimoT + 1
        : 1;
} catch (PDOException $e) {
    $proximoTombamento = 1;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Bem Móvel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <style>

        /* ===== LAYOUT ===== */
        main {
            width: 100%;
            padding: 40px;
            box-sizing: border-box;
        }

        .content {
            background: rgba(0, 0, 0, 0.65);
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            background: #ff9800;
            color: #000;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        .content > h2 {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        /* ===== GROUP BOX ===== */
        .group-box {
            border: 1px solid rgba(255, 152, 0, 0.35);
            border-radius: 10px;
            padding: 28px 24px 20px;
            margin-bottom: 28px;
            position: relative;
        }

        .group-box .legend {
            position: absolute;
            top: -13px;
            left: 16px;
            background: #ff9800;
            color: #000;
            font-weight: bold;
            font-size: 0.8rem;
            padding: 3px 12px;
            border-radius: 20px;
            white-space: nowrap;
        }

        /* ===== GRID DE CAMPOS ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        /* ===== CAMPOS ===== */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-size: 0.88rem;
            font-weight: bold;
            opacity: 0.85;
        }

        .form-group label .opcional {
            font-weight: normal;
            opacity: 0.55;
            font-size: 0.78rem;
            margin-left: 4px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 40px 11px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: border 0.2s;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .form-group input[type="date"] {
            padding-right: 14px;
            color-scheme: dark;
        }

        .form-group input[type="number"] {
            padding-right: 14px;
        }

        .form-group input::placeholder {
            opacity: 0.4;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #ff9800;
        }

        .form-group select:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .form-group select option {
            background: #1a1a1a;
            color: #fff;
        }

        .select-wrapper {
            position: relative;
            width: 100%;
        }

        .select-wrapper::after {
            content: '▾';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff9800;
            pointer-events: none;
            font-size: 1rem;
            line-height: 1;
        }

        .select-wrapper.loading::after {
            content: '⟳';
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            from { transform: translateY(-50%) rotate(0deg); }
            to   { transform: translateY(-50%) rotate(360deg); }
        }

        /* ===== UPLOAD DE IMAGENS ===== */
        .upload-area {
            border: 2px dashed rgba(255, 152, 0, 0.45);
            border-radius: 10px;
            padding: 24px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
        }

        .upload-area:hover {
            border-color: #ff9800;
            background: rgba(255, 152, 0, 0.05);
        }

        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-area .upload-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .upload-area p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.75;
        }

        .upload-area .upload-hint {
            font-size: 0.78rem;
            opacity: 0.5;
            margin-top: 4px;
        }

        .preview-container {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .preview-item {
            position: relative;
            width: 110px;
            height: 110px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid rgba(255, 152, 0, 0.4);
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-item .remove-img {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(0,0,0,0.7);
            border: none;
            color: #ff6b7a;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* ===== Nº TOMBAMENTO COM BOTÕES ===== */
        .tombamento-wrapper {
            display: flex;
            align-items: stretch;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            transition: border-color 0.2s;
        }

        .tombamento-wrapper:focus-within {
            border-color: #ff9800;
        }

        .tombamento-wrapper input[type="number"] {
            flex: 1;
            border: none;
            background: transparent;
            padding: 11px 10px;
            color: #fff;
            font-size: 1rem;
            outline: none;
            text-align: center;
            appearance: textfield;
            -moz-appearance: textfield;
            width: 100%;
            box-sizing: border-box;
        }

        .tombamento-wrapper input[type="number"]::-webkit-outer-spin-button,
        .tombamento-wrapper input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }

        .tomb-btn {
            background: rgba(255, 152, 0, 0.15);
            border: none;
            color: #ff9800;
            font-size: 1.2rem;
            padding: 0 14px;
            cursor: pointer;
            transition: background 0.15s;
            line-height: 1;
            user-select: none;
        }

        .tomb-btn:hover  { background: rgba(255, 152, 0, 0.3); }
        .tomb-btn:active { background: rgba(255, 152, 0, 0.5); }

        /* ===== CUSTO FORMATADO ===== */
        .custo-wrapper {
            position: relative;
        }

        .custo-wrapper .custo-prefix {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 0.95rem;
            pointer-events: none;
        }

        .custo-wrapper input {
            padding-left: 42px !important;
        }

        /* ===== ERRO DE IMAGEM ===== */
        .img-erro {
            color: #ff6b7a;
            font-size: 0.82rem;
            margin-top: 8px;
            display: none;
        }

        .img-erro.visivel { display: block; }

        /* ===== TOGGLE TOMBAMENTO EM MASSA ===== */
        .tombamento-row {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .tombamento-row .form-group {
            flex: 1 1 180px;
        }

        .toggle-massa-wrapper {
            display: flex;
            flex-direction: column;
            gap: 7px;
            align-items: flex-end;
            flex: 1 1 180px;
        }

        .toggle-massa-label-text {
            font-size: 0.88rem;
            font-weight: bold;
            opacity: 0.85;
            white-space: nowrap;
        }

        .toggle-switch-row {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 44px; /* mesma altura do input de tombamento */
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .toggle-track {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 26px;
            cursor: pointer;
            transition: background 0.25s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .toggle-track::after {
            content: '';
            position: absolute;
            left: 3px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            transition: left 0.25s, background 0.25s;
        }

        .toggle-switch input:checked + .toggle-track {
            background: #ff9800;
            border-color: #ff9800;
        }

        .toggle-switch input:checked + .toggle-track::after {
            left: calc(100% - 21px);
            background: #000;
        }

        .massa-quantidade-group {
            display: none;
            flex-direction: column;
            gap: 7px;
            flex: 1 1 180px;
        }

        .massa-quantidade-group.visivel {
            display: flex;
        }

        .massa-quantidade-group label {
            font-size: 0.88rem;
            font-weight: bold;
            opacity: 0.85;
        }

        .massa-hint {
            font-size: 0.75rem;
            opacity: 0.5;
            margin-top: 2px;
        }

        /* ===== REAPROVEITAR DADOS (RECICLAGEM) ===== */
        .reciclar-wrapper {
            display: flex;
            flex-direction: column;
            gap: 7px;
            align-items: flex-start;
            flex: 0 0 auto;
        }

        .btn-reciclar {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 152, 0, 0.15);
            border: 1px solid rgba(255, 152, 0, 0.35);
            border-radius: 8px;
            color: #ff9800;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            padding: 0;
        }

        .btn-reciclar:hover  { background: rgba(255, 152, 0, 0.3); }
        .btn-reciclar:active { transform: scale(0.95); }

        .btn-reciclar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-reciclar .icone-reciclar {
            width: 20px;
            height: 20px;
        }

        .btn-reciclar.carregando .icone-reciclar {
            animation: spin 0.8s linear infinite;
        }

        .btn-submit {
            padding: 13px 40px;
            background: #ff9800;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: block;
            margin: 8px auto 0;
        }

        .btn-submit:hover  { background: #e68900; }
        .btn-submit:active { transform: scale(0.98); }

        /* ===== MENSAGENS ===== */
        .mensagem {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .mensagem.erro {
            background: rgba(220, 53, 69, 0.25);
            border: 1px solid rgba(220, 53, 69, 0.6);
            color: #ff6b7a;
        }

        .mensagem.sucesso {
            background: rgba(40, 167, 69, 0.25);
            border: 1px solid rgba(40, 167, 69, 0.6);
            color: #5dd879;
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 600px) {
            main    { padding: 16px; }

            .content {
                padding: 24px 16px;
                border-radius: 8px;
            }

            .content > h2 { font-size: 1.5rem; }

            .group-box { padding: 28px 14px 16px; }

            .form-grid { grid-template-columns: 1fr; }

            .form-group input,
            .form-group select {
                font-size: 16px; /* evita zoom no iOS */
            }

            .btn-submit {
                width: 100%;
                padding: 14px;
            }

            .preview-item {
                width: 90px;
                height: 90px;
            }
        }

    </style>
</head>
<body>

    <?php require 'includes/header.php'; ?>

    <main>
        <div class="content">
            <span class="badge">📦 Cadastro de Bem Móvel</span>
            <h2>Novo Bem Móvel</h2>

            <?php if (!empty($_SESSION['erro'])): ?>
                <div class="mensagem erro">⚠️ <?= htmlspecialchars($_SESSION['erro']) ?></div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['sucesso'])): ?>
                <div class="mensagem sucesso">✅ <?= htmlspecialchars($_SESSION['sucesso']) ?></div>
                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <form action="cadastro-bem-movel.php" method="POST" enctype="multipart/form-data" id="formBem">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">

                <!-- ===== DADOS DO BEM ===== -->
                <div class="group-box">
                    <span class="legend">📋 Dados do Bem</span>
                    <div class="form-grid">

                        <!-- Linha: tombamento + toggle em massa -->
                        <div class="tombamento-row full-width">

                            <div class="form-group">
                                <label for="numero_tombamento">Nº de Tombamento</label>
                                <div class="tombamento-wrapper">
                                    <button type="button" class="tomb-btn" id="tombMenos">−</button>
                                    <input type="number" id="numero_tombamento" name="numero_tombamento"
                                        min="0" step="1" required autocomplete="off"
                                        value="<?= $proximoTombamento ?>">
                                    <button type="button" class="tomb-btn" id="tombMais">+</button>
                                </div>
                            </div>

                            <div class="reciclar-wrapper">
                                <span class="toggle-massa-label-text">&nbsp;</span>
                                <div class="toggle-switch-row">
                                    <button type="button" class="btn-reciclar" id="btnReciclar"
                                        title="Reaproveitar informações do bem">
                                        <svg class="icone-reciclar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <polyline points="1 20 1 14 7 14"></polyline>
                                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="toggle-massa-wrapper">
                                <span class="toggle-massa-label-text">Tombamento em Massa</span>
                                <div class="toggle-switch-row">
                                    <label class="toggle-switch" title="Ativar tombamento em massa">
                                        <input type="checkbox" id="toggleMassa" name="tombamento_em_massa" value="1">
                                        <span class="toggle-track"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="massa-quantidade-group" id="massaQuantidadeGroup">
                                <label for="quantidade_massa">Quantidade de bens</label>
                                <div class="tombamento-wrapper">
                                    <button type="button" class="tomb-btn" id="massaMenos">−</button>
                                    <input type="number" id="quantidade_massa" name="quantidade_massa"
                                        min="2" max="25" step="1" value="2" autocomplete="off">
                                    <button type="button" class="tomb-btn" id="massaMais">+</button>
                                </div>
                                <span class="massa-hint">Mín. 2 · Máx. 25 tombamentos</span>
                            </div>

                        </div>

                        <div class="form-group full-width">
                            <label for="descricao">Descrição</label>
                            <input type="text" id="descricao" name="descricao"
                                placeholder="Ex: Cadeira giratória ergonômica" maxlength="255" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="marca">Marca <span class="opcional">(opcional)</span></label>
                            <input type="text" id="marca" name="marca"
                                placeholder="Ex: Multimóveis" maxlength="100" autocomplete="off">
                        </div>

                    </div>
                </div>

                <!-- ===== DADOS DO EMPENHO / NOTAS ===== -->
                <div class="group-box">
                    <span class="legend">🧾 Dados do Empenho / Nota</span>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="numero_empenho">Nº do Empenho</label>
                            <input type="text" id="numero_empenho" name="numero_empenho"
                                placeholder="Ex: 2024NE000123" maxlength="100" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="data_aquisicao">Data de Aquisição</label>
                            <input type="date" id="data_aquisicao" name="data_aquisicao" max="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="numero_nota">Nº da Nota</label>
                            <input type="text" id="numero_nota" name="numero_nota"
                                placeholder="Ex: NF-0001" maxlength="100" required autocomplete="off">
                        </div>

                    </div>
                </div>

                <!-- ===== LOCALIZAÇÃO ===== -->
                <div class="group-box">
                    <span class="legend">📍 Localização</span>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="setor_origem">Setor de Origem</label>
                            <div class="select-wrapper">
                                <select id="setor_origem" name="setor_origem" required>
                                    <option value="" disabled selected>Selecione o setor de origem</option>
                                    <?php foreach ($setores as $s): ?>
                                        <option value="<?= (int) $s['id'] ?>">
                                            <?= htmlspecialchars($s['descricao']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="setor">Setor</label>
                            <div class="select-wrapper">
                                <select id="setor" name="setor" required>
                                    <option value="" disabled selected>Selecione o setor</option>
                                    <?php foreach ($setores as $s): ?>
                                        <option value="<?= htmlspecialchars($s['descricao']) ?>">
                                            <?= htmlspecialchars($s['descricao']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subsetor">Subsetor</label>
                            <div class="select-wrapper" id="wrapper-subsetor">
                                <select id="subsetor" name="subsetor" required disabled>
                                    <option value="" disabled selected>Selecione primeiro o setor</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="unidade">Unidade <span class="opcional">(opcional)</span></label>
                            <div class="select-wrapper" id="wrapper-unidade">
                                <select id="unidade" name="unidade" disabled>
                                    <option value="" selected>Selecione primeiro o subsetor</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="grupo">Grupo</label>
                            <div class="select-wrapper">
                                <select id="grupo" name="grupo_id" required>
                                    <option value="" disabled selected>Selecione o grupo</option>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?= (int) $g['id'] ?>">
                                            <?= htmlspecialchars($g['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ===== SITUAÇÃO ===== -->
                <div class="group-box">
                    <span class="legend">📊 Situação</span>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <div class="select-wrapper">
                                <select id="estado" name="estado" required>
                                    <option value="" disabled selected>Selecione o estado</option>
                                    <option value="Novo">Novo</option>
                                    <option value="Bom">Bom</option>
									<option value="Regular">Regular</option>
									<option value="Ruim">Ruim</option>
									<option value="Depreciado">Depreciado</option>
									<option value="Inservivel">Inservível</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <div class="select-wrapper">
                                <select id="tipo" name="tipo_id" required>
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php foreach ($tipos as $t): ?>
                                        <option value="<?= (int) $t['id'] ?>">
                                            <?= htmlspecialchars(ucfirst($t['tipo'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ===== VALORES ===== -->
                <div class="group-box">
                    <span class="legend">💰 Valores</span>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="valor_display">Custo (R$)</label>
                            <div class="custo-wrapper">
                                <span class="custo-prefix">R$</span>
                                <input type="text" id="valor_display" name="valor_display"
                                    placeholder="0,00" required autocomplete="off"
                                    inputmode="numeric">
                                <!-- Campo oculto com o valor numérico enviado ao PHP -->
                                <input type="hidden" id="valor" name="valor">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ===== IMAGENS ===== -->
                <div class="group-box">
                    <span class="legend">📷 Imagens</span>

                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="imagens" name="imagens[]"
                            accept="image/jpeg,image/png,image/webp"
                            capture="environment"
                            multiple>
                        <div class="upload-icon">📷</div>
                        <p>Toque para fotografar ou escolher da galeria</p>
                        <p class="upload-hint">Até 2 imagens • JPG, PNG ou WEBP • Máx. 5MB cada</p>
                    </div>

                    <p class="img-erro" id="imgErro">⚠️ Adicione ao menos uma imagem antes de continuar.</p>

                    <div class="preview-container" id="previewContainer"></div>
                </div>

                <button type="submit" class="btn-submit">Cadastrar Bem Móvel</button>
            </form>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

    <script>
        // ===== SELECTS DINÂMICOS =====
        const selectSetorOrigem = document.getElementById('setor_origem');
        const selectSetor     = document.getElementById('setor');
        const selectSubsetor  = document.getElementById('subsetor');
        const selectUnidade   = document.getElementById('unidade');
        const wrapperSubsetor = document.getElementById('wrapper-subsetor');
        const wrapperUnidade  = document.getElementById('wrapper-unidade');

        // Carrega os subsetores de um setor. Se subsetorAlvo for informado e existir
        // na lista retornada, seleciona-o automaticamente (usado pelo botão de reaproveitar dados).
        function carregarSubsetores(setor, subsetorAlvo = null) {
            selectUnidade.innerHTML = '<option value="" selected>Selecione primeiro o subsetor</option>';
            selectUnidade.disabled  = true;

            selectSubsetor.innerHTML = '<option value="" disabled selected>Carregando...</option>';
            selectSubsetor.disabled  = true;
            wrapperSubsetor.classList.add('loading');

            return fetch('buscar-subsetores.php?setor=' + encodeURIComponent(setor))
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    wrapperSubsetor.classList.remove('loading');
                    if (data.length === 0) {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Nenhum subsetor encontrado</option>';
                        return;
                    }

                    selectSubsetor.innerHTML = '<option value="" disabled selected>Selecione o subsetor</option>';
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.descricao;
                        opt.textContent = item.descricao;
                        selectSubsetor.appendChild(opt);
                    });
                    selectSubsetor.disabled = false;

                    if (subsetorAlvo) {
                        const existe = Array.from(selectSubsetor.options).some(o => o.value === subsetorAlvo);
                        if (existe) {
                            selectSubsetor.value = subsetorAlvo;
                            selectSubsetor.dispatchEvent(new Event('change'));
                        }
                    }
                })
                .catch(() => {
                    wrapperSubsetor.classList.remove('loading');
                    selectSubsetor.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
                });
        }

        selectSetor.addEventListener('change', function () {
            carregarSubsetores(this.value);
        });

        // Ao selecionar o Setor de Origem, preenche automaticamente o campo Setor
        // com o mesmo valor (o usuário pode alterá-lo depois, se o setor atual
        // do bem for diferente do setor de origem constante na nota).
        selectSetorOrigem.addEventListener('change', function () {
            const descricaoSelecionada = this.options[this.selectedIndex].text.trim();
            selectSetor.value = descricaoSelecionada;
            selectSetor.dispatchEvent(new Event('change'));
        });

        // ===== REAPROVEITAR DADOS (RECICLAGEM) =====
        // Busca um bem já cadastrado pelo Nº de Tombamento informado e preenche
        // Nº do Empenho, Data de Aquisição, Nº da Nota, Setor, Subsetor e Tipo,
        // facilitando o cadastro de itens da mesma nota fiscal.
        const btnReciclar = document.getElementById('btnReciclar');

        btnReciclar.addEventListener('click', function () {
            const numTomb = inputTomb.value.trim();

            if (!numTomb) {
                alert('Informe o Nº de Tombamento para buscar os dados.');
                inputTomb.focus();
                return;
            }

            btnReciclar.disabled = true;
            btnReciclar.classList.add('carregando');

            fetch('buscar-bem-por-tombamento.php?numero_tombamento=' + encodeURIComponent(numTomb))
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    if (!data.encontrado) {
                        alert('Nenhum bem cadastrado foi encontrado com esse Nº de Tombamento.');
                        return;
                    }

                    document.getElementById('numero_empenho').value = data.numero_empenho || '';
                    document.getElementById('numero_nota').value    = data.numero_nota    || '';
                    document.getElementById('data_aquisicao').value = data.data_aquisicao || '';

                    if (data.tipo_id) {
                        document.getElementById('tipo').value = data.tipo_id;
                    }

                    if (data.setor_original) {
                        selectSetorOrigem.value = data.setor_original;
                    }

                    if (data.setor) {
                        selectSetor.value = data.setor;
                        carregarSubsetores(data.setor, data.subsetor || null);
                    }
                })
                .catch(() => {
                    alert('Erro ao buscar os dados. Tente novamente.');
                })
                .finally(() => {
                    btnReciclar.disabled = false;
                    btnReciclar.classList.remove('carregando');
                });
        });

        selectSubsetor.addEventListener('change', function () {
            const setor    = selectSetor.value;
            const subsetor = this.value;

            selectUnidade.innerHTML = '<option value="" selected>Carregando...</option>';
            selectUnidade.disabled  = true;
            wrapperUnidade.classList.add('loading');

            fetch('buscar-unidades.php?setor=' + encodeURIComponent(setor) + '&subsetor=' + encodeURIComponent(subsetor))
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    wrapperUnidade.classList.remove('loading');
                    if (data.length === 0) {
                        selectUnidade.innerHTML = '<option value="" selected>Nenhuma unidade encontrada</option>';
                        selectUnidade.disabled  = true;
                    } else {
                        selectUnidade.innerHTML = '<option value="" selected>Nenhuma (opcional)</option>';
                        data.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.descricao;
                            opt.textContent = item.descricao;
                            selectUnidade.appendChild(opt);
                        });
                        selectUnidade.disabled = false;
                    }
                })
                .catch(() => {
                    wrapperUnidade.classList.remove('loading');
                    selectUnidade.innerHTML = '<option value="" selected>Erro ao carregar</option>';
                    selectUnidade.disabled  = true;
                });
        });

        // ===== Nº TOMBAMENTO: BOTÕES +/- =====
        const inputTomb = document.getElementById('numero_tombamento');
        document.getElementById('tombMenos').addEventListener('click', () => {
            const v = parseInt(inputTomb.value) || 0;
            if (v > 0) inputTomb.value = v - 1;
        });
        document.getElementById('tombMais').addEventListener('click', () => {
            const v = parseInt(inputTomb.value) || 0;
            inputTomb.value = v + 1;
        });

        // ===== TOGGLE TOMBAMENTO EM MASSA =====
        const toggleMassa         = document.getElementById('toggleMassa');
        const massaQuantidadeGroup = document.getElementById('massaQuantidadeGroup');
        const inputQuantidadeMassa = document.getElementById('quantidade_massa');
        const MASSA_MIN = 2;
        const MASSA_MAX = 25;

        toggleMassa.addEventListener('change', function () {
            if (this.checked) {
                massaQuantidadeGroup.classList.add('visivel');
                inputQuantidadeMassa.required = true;
            } else {
                massaQuantidadeGroup.classList.remove('visivel');
                inputQuantidadeMassa.required = false;
            }
        });

        document.getElementById('massaMenos').addEventListener('click', () => {
            const v = parseInt(inputQuantidadeMassa.value) || MASSA_MIN;
            if (v > MASSA_MIN) inputQuantidadeMassa.value = v - 1;
        });

        document.getElementById('massaMais').addEventListener('click', () => {
            const v = parseInt(inputQuantidadeMassa.value) || MASSA_MIN;
            if (v < MASSA_MAX) inputQuantidadeMassa.value = v + 1;
        });

        // Garante que o usuário não ultrapasse os limites digitando manualmente
        inputQuantidadeMassa.addEventListener('change', function () {
            let v = parseInt(this.value);
            if (isNaN(v) || v < MASSA_MIN) this.value = MASSA_MIN;
            if (v > MASSA_MAX)             this.value = MASSA_MAX;
        });

        // ===== FORMATAÇÃO DO CUSTO (R$) =====
        const valorDisplay = document.getElementById('valor_display');
        const valorHidden  = document.getElementById('valor');

        valorDisplay.addEventListener('input', function () {
            // Remove tudo que não for dígito
            let digits = this.value.replace(/\D/g, '');

            // Garante ao menos 3 dígitos (ex: "001" = R$ 0,01)
            digits = digits.padStart(3, '0');

            // Separa centavos (2 últimos) dos reais
            const centavos = digits.slice(-2);
            let reais      = digits.slice(0, -2).replace(/^0+/, '') || '0';

            // Insere separadores de milhar
            reais = reais.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            const formatado = reais + ',' + centavos;
            this.value = formatado;

            // Valor numérico para o campo oculto (ex: 1500.99)
            valorHidden.value = reais.replace(/\./g, '') + '.' + centavos;
        });

        // ===== PREVIEW E VALIDAÇÃO DE IMAGENS =====
        const inputImagens     = document.getElementById('imagens');
        const previewContainer = document.getElementById('previewContainer');
        const imgErro          = document.getElementById('imgErro');
        const MAX_IMAGENS      = 2;
        let arquivosSelecionados = [];

        inputImagens.addEventListener('change', function () {
            const novos      = Array.from(this.files);
            const disponiveis = MAX_IMAGENS - arquivosSelecionados.length;
            if (disponiveis <= 0) return;

            arquivosSelecionados = arquivosSelecionados.concat(novos.slice(0, disponiveis));
            renderizarPreviews();
            sincronizarInput();

            // Esconde o erro ao adicionar imagem
            if (arquivosSelecionados.length > 0) {
                imgErro.classList.remove('visivel');
            }
        });

        function renderizarPreviews() {
            previewContainer.innerHTML = '';
            arquivosSelecionados.forEach((arquivo, index) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const item = document.createElement('div');
                    item.className = 'preview-item';
                    item.innerHTML = `
                        <img src="${e.target.result}" alt="Imagem ${index + 1}">
                        <button type="button" class="remove-img" data-index="${index}" title="Remover">✕</button>
                    `;
                    previewContainer.appendChild(item);
                    item.querySelector('.remove-img').addEventListener('click', function () {
                        arquivosSelecionados.splice(parseInt(this.dataset.index), 1);
                        renderizarPreviews();
                        sincronizarInput();
                    });
                };
                reader.readAsDataURL(arquivo);
            });
        }

        function sincronizarInput() {
            const dt = new DataTransfer();
            arquivosSelecionados.forEach(f => dt.items.add(f));
            inputImagens.files = dt.files;
        }

        // ===== VALIDAÇÃO DO FORM ANTES DE ENVIAR =====
        document.getElementById('formBem').addEventListener('submit', function (e) {

            // Valida imagem
            if (arquivosSelecionados.length === 0) {
                e.preventDefault();
                imgErro.classList.add('visivel');
                imgErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Garante que o valor oculto está preenchido
            if (!valorHidden.value || parseFloat(valorHidden.value) < 0) {
                e.preventDefault();
                valorDisplay.focus();
                return;
            }
        });
    </script>

</body>
</html>