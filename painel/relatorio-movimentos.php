<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

require_once 'conexao.php';

$nome_completo = $_SESSION['nome'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Movimentações</title>
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

            <h2>Relatório de Movimentações</h2>

            <?php if (!empty($_SESSION['erro'])): ?>
                <div class="mensagem erro">
                    ⚠️ <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <form action="gerar-relatorio-movimentos" method="POST" target="_blank">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">

                <div class="filtros-lista">

                    <!-- PERÍODO DA MOVIMENTAÇÃO -->
                    <div class="group-box">
                        <span class="legend">📅 Período da Movimentação</span>

                        <div class="radio-linha">
                            <input type="radio" id="movimentacao_periodo" name="periodo_movimentacao_filtro" value="periodo" onchange="atualizarCampo(this)">
                            <label for="movimentacao_periodo">Período específico</label>
                        </div>
                        <div class="campo-selecao periodo-datas">
                            <input type="date" name="data_inicio_movimentacao" disabled>
                            <input type="date" name="data_fim_movimentacao" disabled>
                        </div>

                        <div class="radio-linha">
                            <input type="radio" id="movimentacao_todos" name="periodo_movimentacao_filtro" value="todos" checked>
                            <label for="movimentacao_todos">Todos</label>
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
        // Habilita o campo (date) associado ao radio quando ele é marcado,
        // e desabilita os campos dos radios "irmãos" do mesmo grupo.
        function atualizarCampo(radioSelecionado) {
            const grupo = radioSelecionado.name;
            document.querySelectorAll(`input[name="${grupo}"]`).forEach(radio => {
                const linha = radio.closest('.radio-linha');
                const campoSelecao = linha.nextElementSibling;
                if (campoSelecao && campoSelecao.classList.contains('campo-selecao')) {
                    const campos = campoSelecao.querySelectorAll('input[type="date"]');
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
