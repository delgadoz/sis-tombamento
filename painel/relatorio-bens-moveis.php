<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

require_once 'conexao.php'; // arquivo de conexão PDO

$nome_completo = $_SESSION['nome'];

// ===== BUSCA OS DADOS PARA OS DROPDOWNS =====

// Grupo: não existe tabela própria, então usamos DISTINCT direto de bens_moveis
$stmtGrupos = $pdo->query("SELECT DISTINCT grupo FROM bens_moveis WHERE grupo <> '' ORDER BY grupo ASC");
$grupos = $stmtGrupos->fetchAll(PDO::FETCH_COLUMN);

// Unidade: tabela própria
$stmtUnidades = $pdo->query("SELECT descricao FROM unidades ORDER BY descricao ASC");
$unidades = $stmtUnidades->fetchAll(PDO::FETCH_COLUMN);

// Setor: tabela própria
$stmtSetores = $pdo->query("SELECT descricao FROM setores ORDER BY descricao ASC");
$setores = $stmtSetores->fetchAll(PDO::FETCH_COLUMN);

// SubSetor: tabela própria
$stmtSubsetores = $pdo->query("SELECT descricao FROM subsetores ORDER BY descricao ASC");
$subsetores = $stmtSubsetores->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Bens Móveis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <style>

        /* ===== CONTEÚDO ===== */
        main {
            width: 100%;
            padding: 40px;
        }

        .content {
            background: rgba(0, 0, 0, 0.65);
            border-radius: 12px;
            padding: 40px;
            width: 100%;
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

        .content h2 {
            font-size: 2rem;
            margin-bottom: 25px;
        }

        .filtros-lista {
            margin-bottom: 24px;
        }

        /* ===== GROUP BOX (mesmo padrão do cadastrar-bem-movel.php) ===== */
        .group-box {
            border: 1px solid rgba(255, 152, 0, 0.35);
            border-radius: 10px;
            padding: 28px 24px 20px;
            margin-bottom: 28px;
            position: relative;
        }

        .group-box:last-child {
            margin-bottom: 0;
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

        /* Cada opção (radio + label) fica em sua própria linha */
        .radio-linha {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .radio-linha:last-child {
            margin-bottom: 0;
        }

        .radio-linha input[type="radio"] {
            accent-color: #ff9800;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .radio-linha label {
            font-size: 0.9rem;
            opacity: 0.9;
            cursor: pointer;
        }

        /* O campo (select/data) fica EMBAIXO do radio, não ao lado,
           evitando overflow quando a coluna do grid é estreita.
           width usa calc() para descontar o recuo esquerdo (26px),
           senão o campo estoura a borda direita do group-box. */
        .campo-selecao {
            width: calc(100% - 26px);
            box-sizing: border-box;
            margin: 0 0 12px 26px;
        }

        .campo-selecao:last-child {
            margin-bottom: 0;
        }

        .campo-selecao select,
        .campo-selecao input[type="date"] {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 40px 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: border 0.2s, opacity 0.2s;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .campo-selecao input[type="date"] {
            padding: 8px 10px;
        }

        .campo-selecao select option {
            background: #1a1a1a;
            color: #fff;
        }

        .campo-selecao select option[value=""] {
            color: rgba(255, 255, 255, 0.5);
        }

        .campo-selecao select:focus,
        .campo-selecao input[type="date"]:focus {
            border-color: #ff9800;
        }

        .campo-selecao select:disabled,
        .campo-selecao input[type="date"]:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Wrapper da seta customizada, igual ao cadastrar-subsetor.php */
        .select-wrapper {
            position: relative;
            width: 100%;
        }

        .select-wrapper::after {
            content: '▾';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff9800;
            pointer-events: none;
            font-size: 1rem;
            line-height: 1;
        }

        .select-wrapper:has(select:disabled)::after {
            opacity: 0.4;
        }

        .periodo-datas {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .periodo-datas input[type="date"] {
            flex: 1 1 100px;
            min-width: 0;
        }

        /* ===== ORDENAR POR (empilhado, uma opção por linha) ===== */
        .ordenar-por .radio-linha:last-child {
            margin-bottom: 0;
        }

        /* ===== BOTÃO ===== */
        .btn-submit {
            padding: 12px 32px;
            background: #ff9800;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: block;
            margin: 30px auto 0;
        }

        .btn-submit:hover {
            background: #e68900;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        /* ===== MENSAGENS DE FEEDBACK ===== */
        .mensagem {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .mensagem.erro {
            background: rgba(220, 53, 69, 0.25);
            border: 1px solid rgba(220, 53, 69, 0.6);
            color: #ff6b7a;
        }

    </style>
</head>
<body>

    <!-- TOPO -->
    <?php require 'includes/header.php'; ?>

    <!-- CONTEÚDO -->
    <main>
        <div class="content">
            <span class="badge">📊 Relatório</span>

            <h2>Relatório de Bens Móveis</h2>

            <?php if (!empty($_SESSION['erro'])): ?>
                <div class="mensagem erro">
                    ⚠️ <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <form action="gerar-relatorio-bens-moveis" method="POST" target="_blank">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">

                <div class="filtros-lista">

                    <!-- AQUISIÇÃO -->
                    <div class="group-box">
                        <span class="legend">📅 Aquisição</span>

                        <div class="radio-linha">
                            <input type="radio" id="aquisicao_periodo" name="aquisicao_filtro" value="periodo" onchange="atualizarCampo(this)">
                            <label for="aquisicao_periodo">Período</label>
                        </div>
                        <div class="campo-selecao periodo-datas">
                            <input type="date" name="data_inicio" disabled>
                            <input type="date" name="data_fim" disabled>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="aquisicao_todos" name="aquisicao_filtro" value="todos" checked>
                            <label for="aquisicao_todos">Todos</label>
                        </div>
                    </div>

                    <!-- GRUPO -->
                    <div class="group-box">
                        <span class="legend">🗂️ Escolha o Grupo</span>

                        <div class="radio-linha">
                            <input type="radio" id="grupo_descricao" name="grupo_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="grupo_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="grupo_valor" disabled>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?= htmlspecialchars($grupo) ?>"><?= htmlspecialchars($grupo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="grupo_todos" name="grupo_filtro" value="todos" checked>
                            <label for="grupo_todos">Todos</label>
                        </div>
                    </div>

                    <!-- UNIDADE -->
                    <div class="group-box">
                        <span class="legend">🏢 Escolha a Unidade</span>

                        <div class="radio-linha">
                            <input type="radio" id="unidade_descricao" name="unidade_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="unidade_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="unidade_valor" disabled>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php foreach ($unidades as $unidade): ?>
                                        <option value="<?= htmlspecialchars($unidade) ?>"><?= htmlspecialchars($unidade) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="unidade_todos" name="unidade_filtro" value="todos" checked>
                            <label for="unidade_todos">Todos</label>
                        </div>
                    </div>

                    <!-- SETOR -->
                    <div class="group-box">
                        <span class="legend">📍 Escolha o Setor</span>

                        <div class="radio-linha">
                            <input type="radio" id="setor_descricao" name="setor_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="setor_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="setor_valor" disabled>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php foreach ($setores as $setor): ?>
                                        <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="setor_todos" name="setor_filtro" value="todos" checked>
                            <label for="setor_todos">Todos</label>
                        </div>
                    </div>

                    <!-- SUBSETOR -->
                    <div class="group-box">
                        <span class="legend">🧩 Escolha o SubSetor</span>

                        <div class="radio-linha">
                            <input type="radio" id="subsetor_descricao" name="subsetor_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="subsetor_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="subsetor_valor" disabled>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php foreach ($subsetores as $subsetor): ?>
                                        <option value="<?= htmlspecialchars($subsetor) ?>"><?= htmlspecialchars($subsetor) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="subsetor_todos" name="subsetor_filtro" value="todos" checked>
                            <label for="subsetor_todos">Todos</label>
                        </div>
                    </div>

                    <!-- ORDENAR POR -->
                    <div class="group-box">
                        <span class="legend">🔀 Ordenar por</span>

                        <div class="ordenar-por">
                            <div class="radio-linha">
                                <input type="radio" id="ordenar_codigo" name="ordenar_por" value="codigo">
                                <label for="ordenar_codigo">Código</label>
                            </div>
                            <div class="radio-linha">
                                <input type="radio" id="ordenar_tombamento" name="ordenar_por" value="tombamento" checked>
                                <label for="ordenar_tombamento">Tombamento</label>
                            </div>
                            <div class="radio-linha">
                                <input type="radio" id="ordenar_descricao" name="ordenar_por" value="descricao">
                                <label for="ordenar_descricao">Descrição</label>
                            </div>
                        </div>
                    </div>

                </div>

                <button type="submit" class="btn-submit">Emitir Relatório</button>
            </form>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

    <script>
        // Habilita o campo (select/date) associado ao radio quando ele é marcado,
        // e desabilita os campos dos radios "irmãos" do mesmo grupo.
        function atualizarCampo(radioSelecionado) {
            const grupo = radioSelecionado.name;
            document.querySelectorAll(`input[name="${grupo}"]`).forEach(radio => {
                const linha = radio.closest('.radio-linha');
                const campoSelecao = linha.nextElementSibling;
                if (campoSelecao && campoSelecao.classList.contains('campo-selecao')) {
                    const campos = campoSelecao.querySelectorAll('select, input[type="date"]');
                    campos.forEach(campo => {
                        campo.disabled = !radio.checked;
                    });
                }
            });
        }

        // Garante que ao marcar "Todos" os campos do outro radio sejam desabilitados
        document.querySelectorAll('.group-box').forEach(grupo => {
            grupo.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', () => atualizarCampo(radio));
            });
        });
    </script>

</body>
</html>
