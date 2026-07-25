<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];
$cnpj          = $_SESSION['cnpj'];

try {
    $stmt    = $pdo->query("SELECT id, descricao FROM setores ORDER BY descricao ASC");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $setores = [];
}

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alterar Bem Móvel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <link rel="stylesheet" href="css/alterar-bem-movel.css">
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

                    <div class="aviso-prazo" id="avisoPrazo">
                        ⏳ Este bem foi cadastrado há mais de 3 dias. Apenas <strong>Setor</strong>, <strong>Subsetor</strong>, <strong>Unidade</strong> e <strong>Estado</strong> podem ser alterados.
                    </div>

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

                    <div class="acoes-wrapper">
                        <button type="submit" class="btn-submit" id="btnSubmit" disabled>💾 Salvar Alterações</button>
                        <button type="button" class="btn-excluir" id="btnExcluir" disabled>🗑️ Excluir</button>
                    </div>

                </div>
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
        const avisoPrazo       = document.getElementById('avisoPrazo');

        // Campos que só podem ser alterados dentro dos 3 dias após o cadastro.
        // Setor, Subsetor, Unidade e Estado ficam de fora dessa lista pois
        // continuam editáveis independentemente do prazo.
        const CAMPOS_PRAZO_LIMITADO = [
            'descricao', 'marca', 'numero_empenho', 'data_aquisicao',
            'numero_nota', 'setor_origem', 'grupo', 'tipo', 'valor_display'
        ];

        // ===== HABILITA/BLOQUEIA CAMPOS CONFORME O PRAZO DE EDIÇÃO =====
        function aplicarModoEdicao(dentroPrazo) {
            CAMPOS_PRAZO_LIMITADO.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = !dentroPrazo;
            });

            avisoPrazo.classList.toggle('visivel', !dentroPrazo);
        }

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
                        aplicarModoEdicao(true); // reseta os campos para o estado padrão (habilitados)
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

            aplicarModoEdicao(bem.dentro_prazo === true || bem.dentro_prazo === 1 || bem.dentro_prazo === '1');

            setVal('descricao',      bem.descricao      ?? '');
            setVal('marca',          bem.marca          ?? '');
            setVal('numero_empenho', bem.numero_empenho ?? '');
            setVal('data_aquisicao', bem.data_aquisicao ?? '');
            setVal('numero_nota',    bem.numero_nota    ?? '');
            setVal('setor_origem',   bem.setor_original ?? '');
            setVal('grupo',          bem.grupo_id       ?? '');
            setVal('estado',         bem.estado         ?? '');
            setVal('tipo',           bem.tipo_id        ?? '');

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