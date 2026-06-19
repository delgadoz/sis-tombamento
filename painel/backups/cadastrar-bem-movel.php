<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];

// Busca os setores cadastrados
try {
    $stmt   = $pdo->query("SELECT id, descricao FROM setores ORDER BY descricao ASC");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $setores = [];
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

        .group-box legend {
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

        /* ===== BOTÃO SUBMIT ===== */
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

                        <div class="form-group">
                            <label for="numero_tombamento">Nº de Tombamento</label>
                            <input type="text" id="numero_tombamento" name="numero_tombamento"
                                placeholder="Ex: 001234" maxlength="100" required autocomplete="off">
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
                            <input type="date" id="data_aquisicao" name="data_aquisicao" required>
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
                                <select id="grupo" name="grupo" required>
                                    <option value="" disabled selected>Selecione o grupo</option>
                                    <option value="Móveis">Móveis</option>
                                    <option value="Eletrodoméstico">Eletrodoméstico</option>
                                    <option value="Eletrônicos">Eletrônicos</option>
                                    <option value="Instrumento Musical">Instrumento Musical</option>
                                    <option value="Equipamentos Hospitalares">Equipamentos Hospitalares</option>
                                    <option value="Máquinas e Equipamentos">Máquinas e Equipamentos</option>
                                    <option value="Veículos">Veículos</option>
                                    <option value="Ferramentas">Ferramentas</option>
                                    <option value="Outros">Outros</option>
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
                                    <option value="NOVO">Novo</option>
                                    <option value="BOM">Bom</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <div class="select-wrapper">
                                <select id="tipo" name="tipo" required>
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <option value="gestão anterior">Gestão Anterior</option>
                                    <option value="aquisição">Aquisição</option>
                                    <option value="doação">Doação</option>
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
                            <label for="valor">Custo (R$)</label>
                            <input type="number" id="valor" name="valor"
                                placeholder="Ex: 1500.00" min="0" step="0.01" required>
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

                    <div class="preview-container" id="previewContainer"></div>
                </div>

                <button type="submit" class="btn-submit">Cadastrar Bem Móvel</button>
            </form>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

    <script>
        // ===== SELECTS DINÂMICOS =====
        const selectSetor    = document.getElementById('setor');
        const selectSubsetor = document.getElementById('subsetor');
        const selectUnidade  = document.getElementById('unidade');
        const wrapperSubsetor = document.getElementById('wrapper-subsetor');
        const wrapperUnidade  = document.getElementById('wrapper-unidade');

        selectSetor.addEventListener('change', function () {
            const setor = this.value;

            // Reseta unidade
            selectUnidade.innerHTML = '<option value="" selected>Selecione primeiro o subsetor</option>';
            selectUnidade.disabled  = true;

            // Carrega subsetores
            selectSubsetor.innerHTML = '<option value="" disabled selected>Carregando...</option>';
            selectSubsetor.disabled  = true;
            wrapperSubsetor.classList.add('loading');

            fetch('buscar-subsetores.php?setor=' + encodeURIComponent(setor))
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    wrapperSubsetor.classList.remove('loading');
                    if (data.length === 0) {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Nenhum subsetor encontrado</option>';
                    } else {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Selecione o subsetor</option>';
                        data.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.descricao;
                            opt.textContent = item.descricao;
                            selectSubsetor.appendChild(opt);
                        });
                        selectSubsetor.disabled = false;
                    }
                })
                .catch(() => {
                    wrapperSubsetor.classList.remove('loading');
                    selectSubsetor.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
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

        // ===== PREVIEW DE IMAGENS =====
        const inputImagens      = document.getElementById('imagens');
        const previewContainer  = document.getElementById('previewContainer');
        const MAX_IMAGENS       = 2;
        let arquivosSelecionados = [];

        inputImagens.addEventListener('change', function () {
            const novos = Array.from(this.files);

            // Soma os já selecionados com os novos, respeitando o limite
            const disponiveis = MAX_IMAGENS - arquivosSelecionados.length;
            if (disponiveis <= 0) return;

            const paraAdicionar = novos.slice(0, disponiveis);
            arquivosSelecionados = arquivosSelecionados.concat(paraAdicionar);

            renderizarPreviews();
            sincronizarInput();
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
    </script>

</body>
</html>