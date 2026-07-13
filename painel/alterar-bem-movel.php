<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];
$cnpj          = $_SESSION['cnpj'];

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alterar Bem Móvel</title>
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

        .group-box legend,
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

        .form-group input:disabled,
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

        /* ===== BUSCA POR TOMBAMENTO ===== */
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

        .tombamento-wrapper input[type="text"] {
            flex: 1;
            border: none !important;
            background: transparent !important;
            padding: 11px 14px !important;
            color: #fff;
            font-size: 1rem;
            outline: none;
            width: 100%;
            box-sizing: border-box;
            border-radius: 0 !important;
        }

        .btn-buscar {
            background: rgba(255, 152, 0, 0.15);
            border: none;
            color: #ff9800;
            font-size: 1.1rem;
            padding: 0 16px;
            cursor: pointer;
            transition: background 0.15s;
            line-height: 1;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
        }

        .btn-buscar:hover  { background: rgba(255, 152, 0, 0.3); }
        .btn-buscar:active { background: rgba(255, 152, 0, 0.5); }

        .btn-buscar.carregando {
            pointer-events: none;
            animation: spin-inline 0.8s linear infinite;
        }

        @keyframes spin-inline {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        /* Aviso de busca */
        .busca-info {
            margin-top: 10px;
            font-size: 0.82rem;
            padding: 8px 12px;
            border-radius: 7px;
            display: none;
        }

        .busca-info.nao-encontrado {
            display: block;
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b7a;
        }

        .busca-info.encontrado {
            display: block;
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
            color: #5dd879;
        }

        /* ===== CAMPOS DO FORMULÁRIO (desabilitados até busca) ===== */
        .campos-bem {
            opacity: 0.45;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .campos-bem.ativo {
            opacity: 1;
            pointer-events: all;
        }

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

        /* ===== BOTÕES DE AÇÃO ===== */
        .acoes-wrapper {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 8px;
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
        }

        .btn-submit:hover  { background: #e68900; }
        .btn-submit:active { transform: scale(0.98); }
        .btn-submit:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-excluir {
            padding: 13px 40px;
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b7a;
            border: 1px solid rgba(220, 53, 69, 0.5);
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-excluir:hover  { background: rgba(220, 53, 69, 0.3); }
        .btn-excluir:active { transform: scale(0.98); }
        .btn-excluir:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        @media (max-width: 600px) {
            .btn-submit,
            .btn-excluir {
                width: 100%;
                padding: 14px;
            }
        }

        /* ===== MODAL DE CONFIRMAÇÃO ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-overlay.visivel {
            display: flex;
        }

        .modal {
            background: #1a1a1a;
            border: 1px solid rgba(220, 53, 69, 0.5);
            border-radius: 12px;
            padding: 32px 28px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
            text-align: center;
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from { transform: scale(0.9); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .modal-icone { font-size: 2.5rem; margin-bottom: 12px; }

        .modal h3 {
            font-size: 1.2rem;
            margin: 0 0 10px;
            color: #fff;
        }

        .modal p {
            font-size: 0.92rem;
            opacity: 0.75;
            margin: 0 0 24px;
            line-height: 1.5;
        }

        .modal p strong {
            color: #ff9800;
            opacity: 1;
        }

        .modal-botoes {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-botoes button {
            flex: 1;
            padding: 11px 0;
            border-radius: 8px;
            border: none;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .modal-botoes button:active { transform: scale(0.97); }

        .btn-modal-cancelar {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15) !important;
        }

        .btn-modal-cancelar:hover { background: rgba(255,255,255,0.14); }

        .btn-modal-confirmar {
            background: #dc3545;
            color: #fff;
        }

        .btn-modal-confirmar:hover { background: #b02a37; }

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
        }

    </style>
</head>
<body>

    <?php require 'includes/header.php'; ?>

    <main>
        <div class="content">
            <span class="badge">✏️ Alteração de Bem Móvel</span>
            <h2>Alterar Bem Móvel</h2>

            <?php if (!empty($_SESSION['erro'])): ?>
                <div class="mensagem erro">⚠️ <?= htmlspecialchars($_SESSION['erro']) ?></div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['sucesso'])): ?>
                <div class="mensagem sucesso">✅ <?= htmlspecialchars($_SESSION['sucesso']) ?></div>
                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <form action="alteracao-bem-movel.php" method="POST" id="formBem">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">
                <!-- ID do bem encontrado (preenchido via JS após busca) -->
                <input type="hidden" name="id_bem" id="id_bem">

                <!-- ===== BUSCA POR TOMBAMENTO ===== -->
                <div class="group-box">
                    <span class="legend">🔍 Buscar Patrimônio</span>
                    <div class="form-grid">

                        <div class="form-group full-width">
                            <label for="numero_tombamento">Nº de Tombamento</label>
                            <div class="tombamento-wrapper">
                                <input type="text" id="numero_tombamento" name="numero_tombamento"
                                    placeholder="Digite o número de tombamento e pressione Enter ou clique na lupa"
                                    autocomplete="off" inputmode="numeric">
                                <button type="button" class="btn-buscar" id="btnBuscar" title="Buscar bem">
                                    🔍
                                </button>
                            </div>
                            <div class="busca-info" id="buscaInfo"></div>
                        </div>

                    </div>
                </div>

                <!-- ===== DADOS DO BEM ===== -->
                <div class="campos-bem" id="camposBem">

                    <div class="group-box">
                        <span class="legend">📋 Dados do Bem</span>
                        <div class="form-grid">

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

                    <div class="acoes-wrapper">
                        <button type="submit" class="btn-submit" id="btnSubmit" disabled>💾 Salvar Alterações</button>
                        <button type="button" class="btn-excluir" id="btnExcluir" disabled>🗑️ Excluir</button>
                    </div>

                </div><!-- /.campos-bem -->
            </form>
        </div>

        <!-- ===== MODAL DE CONFIRMAÇÃO DE EXCLUSÃO ===== -->
        <div class="modal-overlay" id="modalExclusao">
            <div class="modal">
                <div class="modal-icone">⚠️</div>
                <h3>Confirmar Exclusão</h3>
                <p>Você tem certeza que deseja excluir o tombamento de número <strong id="modalTombamento"></strong>? Esta ação não poderá ser desfeita.</p>
                <div class="modal-botoes">
                    <button type="button" class="btn-modal-cancelar" id="btnModalCancelar">Cancelar</button>
                    <button type="button" class="btn-modal-confirmar" id="btnModalConfirmar">Sim, excluir</button>
                </div>
            </div>
        </div>

        <!-- Formulário oculto para envio da exclusão -->
        <form action="excluir-bem-movel.php" method="POST" id="formExclusao">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">
            <input type="hidden" name="bem_id"     id="excluir_bem_id">
        </form>

        <?php require 'includes/footer.php'; ?>
    </main>

    <script>
        // ===== REFERÊNCIAS =====
        const inputTombamento  = document.getElementById('numero_tombamento');
        const btnBuscar        = document.getElementById('btnBuscar');
        const buscaInfo        = document.getElementById('buscaInfo');
        const camposBem        = document.getElementById('camposBem');
        const btnSubmit        = document.getElementById('btnSubmit');
        const btnExcluir       = document.getElementById('btnExcluir');
        const idBemInput       = document.getElementById('id_bem');

        const selectSetor      = document.getElementById('setor');
        const selectSubsetor   = document.getElementById('subsetor');
        const selectUnidade    = document.getElementById('unidade');
        const wrapperSubsetor  = document.getElementById('wrapper-subsetor');
        const wrapperUnidade   = document.getElementById('wrapper-unidade');

        // ===== MODAL DE EXCLUSÃO =====
        const modalOverlay      = document.getElementById('modalExclusao');
        const modalTombamento   = document.getElementById('modalTombamento');
        const btnModalCancelar  = document.getElementById('btnModalCancelar');
        const btnModalConfirmar = document.getElementById('btnModalConfirmar');
        const formExclusao      = document.getElementById('formExclusao');
        const excluirBemId      = document.getElementById('excluir_bem_id');

        btnExcluir.addEventListener('click', function () {
            const tombamento = inputTombamento.value.trim();
            if (!idBemInput.value || !tombamento) return;

            // Preenche o modal com o número de tombamento e abre
            modalTombamento.textContent = tombamento;
            excluirBemId.value          = idBemInput.value;
            modalOverlay.classList.add('visivel');
        });

        btnModalCancelar.addEventListener('click', function () {
            modalOverlay.classList.remove('visivel');
        });

        // Fecha o modal ao clicar fora dele
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) modalOverlay.classList.remove('visivel');
        });

        btnModalConfirmar.addEventListener('click', function () {
            formExclusao.submit();
        });

        // ===== BUSCA DO BEM POR TOMBAMENTO =====
        function realizarBusca() {
            const tombamento = inputTombamento.value.trim();

            if (!tombamento) {
                buscaInfo.className = 'busca-info nao-encontrado';
                buscaInfo.textContent = '⚠️ Informe o número de tombamento.';
                return;
            }

            // Estado: carregando
            btnBuscar.textContent = '⟳';
            btnBuscar.classList.add('carregando');
            buscaInfo.className = 'busca-info';
            buscaInfo.textContent = '';

            fetch('buscar-bem-movel.php?numero_tombamento=' + encodeURIComponent(tombamento))
                .then(r => { if (!r.ok) throw new Error('Erro na requisição'); return r.json(); })
                .then(data => {
                    btnBuscar.textContent = '🔍';
                    btnBuscar.classList.remove('carregando');

                    if (!data || !data.id) {
                        buscaInfo.className = 'busca-info nao-encontrado';
                        buscaInfo.textContent = '❌ Patrimônio não encontrado. Verifique o número de tombamento.';
                        camposBem.classList.remove('ativo');
                        btnSubmit.disabled = true;
                        btnExcluir.disabled = true;
                        idBemInput.value = '';
                        return;
                    }

                    // Preenche os campos com os dados retornados
                    preencherFormulario(data);

                    buscaInfo.className = 'busca-info encontrado';
                    buscaInfo.textContent = '✅ Patrimônio encontrado! Edite os campos abaixo e salve as alterações.';
                    camposBem.classList.add('ativo');
                    btnSubmit.disabled = false;
                    btnExcluir.disabled = false;
                })
                .catch(() => {
                    btnBuscar.textContent = '🔍';
                    btnBuscar.classList.remove('carregando');
                    buscaInfo.className = 'busca-info nao-encontrado';
                    buscaInfo.textContent = '❌ Erro ao realizar a busca. Tente novamente.';
                });
        }

        // ===== PREENCHE OS CAMPOS DO FORMULÁRIO =====
        function preencherFormulario(bem) {
            idBemInput.value = bem.id;

            setVal('descricao',      bem.descricao      ?? '');
            setVal('marca',          bem.marca          ?? '');
            setVal('numero_empenho', bem.numero_empenho ?? '');
            setVal('data_aquisicao', bem.data_aquisicao ?? '');
            setVal('numero_nota',    bem.numero_nota    ?? '');
            setVal('grupo',          bem.grupo_id       ?? '');
            setVal('estado',         bem.estado         ?? '');
            setVal('tipo',           bem.tipo           ?? '');

            // Custo: formata e preenche os dois campos
            const valorNum = parseFloat(bem.valor ?? 0);
            if (!isNaN(valorNum)) {
                document.getElementById('valor').value = valorNum.toFixed(2);
                document.getElementById('valor_display').value = formatarMoeda(valorNum);
            }

            // Setor → dispara carregamento de subsetor → depois unidade
            const setorVal = bem.setor ?? '';
            setVal('setor', setorVal);

            if (setorVal) {
                carregarSubsetores(setorVal, bem.subsetor ?? '', bem.unidade ?? '');
            }
        }

        function setVal(id, valor) {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = valor;
        }

        // ===== FORMATA NÚMERO PARA MOEDA BRASILEIRA =====
        function formatarMoeda(numero) {
            const centavos = Math.round(numero * 100);
            const digits   = String(centavos).padStart(3, '0');
            const partesCentavos = digits.slice(-2);
            let   partesReais    = digits.slice(0, -2).replace(/^0+/, '') || '0';
            partesReais = partesReais.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return partesReais + ',' + partesCentavos;
        }

        // ===== CARREGA SUBSETORES E DEPOIS UNIDADES =====
        function carregarSubsetores(setor, subsetorAtual, unidadeAtual) {
            selectSubsetor.innerHTML = '<option value="" disabled selected>Carregando...</option>';
            selectSubsetor.disabled  = true;
            wrapperSubsetor.classList.add('loading');

            selectUnidade.innerHTML = '<option value="" selected>Selecione primeiro o subsetor</option>';
            selectUnidade.disabled  = true;

            fetch('buscar-subsetores.php?setor=' + encodeURIComponent(setor))
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
                        opt.value       = item.descricao;
                        opt.textContent = item.descricao;
                        if (item.descricao === subsetorAtual) opt.selected = true;
                        selectSubsetor.appendChild(opt);
                    });
                    selectSubsetor.disabled = false;

                    // Se havia um subsetor salvo, carrega as unidades correspondentes
                    if (subsetorAtual) {
                        carregarUnidades(setor, subsetorAtual, unidadeAtual);
                    }
                })
                .catch(() => {
                    wrapperSubsetor.classList.remove('loading');
                    selectSubsetor.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
                });
        }

        function carregarUnidades(setor, subsetor, unidadeAtual) {
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
                        return;
                    }

                    selectUnidade.innerHTML = '<option value="" selected>Nenhuma (opcional)</option>';
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value       = item.descricao;
                        opt.textContent = item.descricao;
                        if (item.descricao === unidadeAtual) opt.selected = true;
                        selectUnidade.appendChild(opt);
                    });
                    selectUnidade.disabled = false;
                })
                .catch(() => {
                    wrapperUnidade.classList.remove('loading');
                    selectUnidade.innerHTML = '<option value="" selected>Erro ao carregar</option>';
                    selectUnidade.disabled  = true;
                });
        }

        // ===== SELECTS DINÂMICOS (interação manual do usuário) =====
        selectSetor.addEventListener('change', function () {
            selectUnidade.innerHTML = '<option value="" selected>Selecione primeiro o subsetor</option>';
            selectUnidade.disabled  = true;

            selectSubsetor.innerHTML = '<option value="" disabled selected>Carregando...</option>';
            selectSubsetor.disabled  = true;
            wrapperSubsetor.classList.add('loading');

            fetch('buscar-subsetores.php?setor=' + encodeURIComponent(this.value))
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    wrapperSubsetor.classList.remove('loading');
                    if (data.length === 0) {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Nenhum subsetor encontrado</option>';
                    } else {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Selecione o subsetor</option>';
                        data.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value       = item.descricao;
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
                            opt.value       = item.descricao;
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

        // ===== EVENTOS DE BUSCA =====
        btnBuscar.addEventListener('click', realizarBusca);

        inputTombamento.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                realizarBusca();
            }
        });

        // ===== FORMATAÇÃO DO CUSTO (R$) =====
        document.getElementById('valor_display').addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '');
            digits = digits.padStart(3, '0');
            const centavos = digits.slice(-2);
            let reais      = digits.slice(0, -2).replace(/^0+/, '') || '0';
            reais = reais.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.value = reais + ',' + centavos;
            document.getElementById('valor').value = reais.replace(/\./g, '') + '.' + centavos;
        });

        // ===== VALIDAÇÃO DO FORM ANTES DE ENVIAR =====
        document.getElementById('formBem').addEventListener('submit', function (e) {
            // Bloqueia envio se nenhum bem foi carregado
            if (!idBemInput.value) {
                e.preventDefault();
                buscaInfo.className = 'busca-info nao-encontrado';
                buscaInfo.textContent = '⚠️ Busque um bem pelo número de tombamento antes de salvar.';
                inputTombamento.focus();
                return;
            }

            // Garante que o valor oculto está preenchido
            const valorHidden = document.getElementById('valor');
            if (!valorHidden.value || parseFloat(valorHidden.value) <= 0) {
                e.preventDefault();
                document.getElementById('valor_display').focus();
                return;
            }
        });
    </script>

</body>
</html>