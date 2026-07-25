<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

require_once 'conexao.php'; 

$nome_completo = $_SESSION['nome'];

// ===== BUSCA OS DADOS PARA OS DROPDOWNS =====

// Grupo
$stmtGrupos = $pdo->query("SELECT id, nome FROM grupos ORDER BY nome ASC");
$grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

// Setor Original (precisa do id, já que agora é FK) e Setor (mantém descrição, como já era)
$stmtSetores = $pdo->query("SELECT id, descricao FROM setores ORDER BY descricao ASC");
$setoresCompletos = $stmtSetores->fetchAll(PDO::FETCH_ASSOC);
$setores = array_column($setoresCompletos, 'descricao');

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Bens Móveis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <link rel="stylesheet" href="css/relatorio-bens-moveis.css">
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
					
					                    <!-- PERÍODO DE TOMBAMENTO -->
                    <div class="group-box">
                        <span class="legend">📅 Período de Tombamento </span>

                        <div class="radio-linha">
                            <input type="radio" id="tombamento_periodo" name="periodo_tombamento_filtro" value="periodo" onchange="atualizarCampo(this)">
                            <label for="tombamento_periodo">Período específico</label>
                        </div>
                        <div class="campo-selecao periodo-datas">
                            <input type="date" name="data_inicio_tombamento" disabled>
                            <input type="date" name="data_fim_tombamento" disabled>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="tombamento_todos" name="periodo_tombamento_filtro" value="todos" checked>
                            <label for="tombamento_todos">Todos</label>
                        </div>
                    </div>
					
					                    <!-- PERÍODO DE AQUISIÇÃO -->
                    <div class="group-box">
                        <span class="legend">📅 Período de Aquisição</span>

                        <div class="radio-linha">
                            <input type="radio" id="aquisicao_periodo" name="periodo_aquisicao_filtro" value="periodo" onchange="atualizarCampo(this)">
                            <label for="aquisicao_periodo">Período específico</label>
                        </div>
                        <div class="campo-selecao periodo-datas">
                            <input type="date" name="data_inicio" disabled>
                            <input type="date" name="data_fim" disabled>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="aquisicao_todos" name="periodo_aquisicao_filtro" value="todos" checked>
                            <label for="aquisicao_todos">Todos</label>
                        </div>
                    </div>

                    <!-- SETOR -->
                    <!-- SETOR ORIGINAL -->
                    <div class="group-box">
                        <span class="legend">📦 Setor Original</span>

                        <div class="radio-linha">
                            <input type="radio" id="setor_original_descricao" name="setor_original_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="setor_original_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="setor_original_valor" disabled>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php foreach ($setoresCompletos as $so): ?>
                                        <option value="<?= (int) $so['id'] ?>"><?= htmlspecialchars($so['descricao']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="setor_original_todos" name="setor_original_filtro" value="todos" checked>
                            <label for="setor_original_todos">Todos</label>
                        </div>
                    </div>

                    <!-- SETOR -->
                    <div class="group-box">
                        <span class="legend">📍 Setor</span>

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
                        <span class="legend">🧩 SubSetor</span>

                        <div class="radio-linha">
                            <input type="radio" id="subsetor_descricao" name="subsetor_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="subsetor_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="subsetor_valor" disabled>
                                    <option value="" disabled selected>Selecione um setor primeiro...</option>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="subsetor_todos" name="subsetor_filtro" value="todos" checked>
                            <label for="subsetor_todos">Todos</label>
                        </div>
                    </div>
					
					                    <!-- UNIDADE -->
                    <div class="group-box">
                        <span class="legend">🏢 Unidade</span>

                        <div class="radio-linha">
                            <input type="radio" id="unidade_descricao" name="unidade_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="unidade_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="unidade_valor" disabled>
                                    <option value="" disabled selected>Selecione um subsetor primeiro...</option>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="unidade_todos" name="unidade_filtro" value="todos" checked>
                            <label for="unidade_todos">Todos</label>
                        </div>
                    </div>
					
					                   <!-- GRUPO -->
                    <div class="group-box">
                        <span class="legend">🗂️ Grupo</span>

                        <div class="radio-linha">
                            <input type="radio" id="grupo_descricao" name="grupo_filtro" value="descricao" onchange="atualizarCampo(this)">
                            <label for="grupo_descricao">Descrição</label>
                        </div>
                        <div class="campo-selecao">
                            <div class="select-wrapper">
                                <select name="grupo_valor" disabled>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?= (int) $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="grupo_todos" name="grupo_filtro" value="todos" checked>
                            <label for="grupo_todos">Todos</label>
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

        // ===== CASCATA SETOR -> SUBSETOR -> UNIDADE =====

        const OPCAO_VAZIA = '<option value="" disabled selected>Selecione...</option>';

        const selectSetor    = document.querySelector('select[name="setor_valor"]');
        const selectSubsetor = document.querySelector('select[name="subsetor_valor"]');
        const selectUnidade  = document.querySelector('select[name="unidade_valor"]');

        function resetarSelect(select, textoPlaceholder) {
            select.innerHTML = `<option value="" disabled selected>${textoPlaceholder}</option>`;
        }

        function preencherSelect(select, itens) {
            select.innerHTML = OPCAO_VAZIA;
            itens.forEach(item => {
                const option = document.createElement('option');
                option.value = item.descricao;
                option.textContent = item.descricao;
                select.appendChild(option);
            });
        }

        // Busca os subsetores pertencentes ao setor informado
        async function popularSubsetores(setor) {
            resetarSelect(selectSubsetor, 'Selecione um setor primeiro...');
            resetarSelect(selectUnidade, 'Selecione um subsetor primeiro...');

            if (!setor) return;

            try {
                const resposta = await fetch(`buscar-subsetores?setor=${encodeURIComponent(setor)}`);
                if (!resposta.ok) return;
                const subsetores = await resposta.json();
                preencherSelect(selectSubsetor, subsetores);
            } catch (erro) {
                console.error('Erro ao buscar subsetores:', erro);
            }
        }

        // Busca as unidades pertencentes ao setor + subsetor informados
        async function popularUnidades(setor, subsetor) {
            resetarSelect(selectUnidade, 'Selecione um subsetor primeiro...');

            if (!setor || !subsetor) return;

            try {
                const resposta = await fetch(`buscar-unidades?setor=${encodeURIComponent(setor)}&subsetor=${encodeURIComponent(subsetor)}`);
                if (!resposta.ok) return;
                const unidades = await resposta.json();
                preencherSelect(selectUnidade, unidades);
            } catch (erro) {
                console.error('Erro ao buscar unidades:', erro);
            }
        }

        selectSetor.addEventListener('change', function () {
            popularSubsetores(this.value);
        });

        selectSubsetor.addEventListener('change', function () {
            popularUnidades(selectSetor.value, this.value);
        });
    </script>

</body>
</html>