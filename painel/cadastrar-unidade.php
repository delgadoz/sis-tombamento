<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

// Busca os setores cadastrados para popular o select
try {
    $stmt = $pdo->query("SELECT id, descricao FROM setores ORDER BY descricao ASC");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $setores = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Unidade</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <link rel="stylesheet" href="css/cadastrar-unidade.css">
</head>
<body>

    <!-- TOPO -->
    <?php require 'includes/header.php'; ?>

    <!-- CONTEÚDO -->
    <main>
        <div class="content">
            <span class="badge">🏬 Cadastro de Unidade</span>

            <h2>Nova Unidade</h2>

            <?php if (!empty($_SESSION['erro'])): ?>
                <div class="mensagem erro">
                    ⚠️ <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['sucesso'])): ?>
                <div class="mensagem sucesso">
                    ✅ <?= htmlspecialchars($_SESSION['sucesso']) ?>
                </div>
                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <?php if (empty($setores)): ?>
                <div class="aviso">
                    ⚠️ Nenhum setor cadastrado. Cadastre um setor antes de adicionar uma unidade.
                </div>
            <?php else: ?>
                <form action="cadastro-unidade.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">

                    <!-- SELECT: SETOR -->
                    <div class="form-group">
                        <label for="setor">Setor</label>
                        <div class="select-wrapper">
                            <select id="setor" name="setor" required>
                                <option value="" disabled selected>Selecione o setor</option>
                                <?php foreach ($setores as $setor): ?>
                                    <option value="<?= htmlspecialchars($setor['descricao']) ?>">
                                        <?= htmlspecialchars($setor['descricao']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- SELECT: SUBSETOR (populado via JS após escolha do setor) -->
                    <div class="form-group">
                        <label for="subsetor">Subsetor</label>
                        <div class="select-wrapper" id="wrapper-subsetor">
                            <select id="subsetor" name="subsetor" required disabled>
                                <option value="" disabled selected>Selecione primeiro o setor</option>
                            </select>
                        </div>
                    </div>

                    <!-- CAMPO: DESCRIÇÃO -->
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <input
                            type="text"
                            id="descricao"
                            name="descricao"
                            placeholder="Digite o nome da unidade"
                            maxlength="255"
                            required
                            autocomplete="off"
                        >
                    </div>

                    <button type="submit" class="btn-submit">Cadastrar Unidade</button>
                </form>
            <?php endif; ?>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

    <script>
        const selectSetor    = document.getElementById('setor');
        const selectSubsetor = document.getElementById('subsetor');
        const wrapperSubsetor = document.getElementById('wrapper-subsetor');

        selectSetor.addEventListener('change', function () {
            const setorSelecionado = this.value;

            // Reseta e bloqueia o select de subsetor enquanto carrega
            selectSubsetor.innerHTML = '<option value="" disabled selected>Carregando...</option>';
            selectSubsetor.disabled  = true;
            wrapperSubsetor.classList.add('loading');

            fetch('buscar-subsetores.php?setor=' + encodeURIComponent(setorSelecionado))
                .then(response => {
                    if (!response.ok) throw new Error('Erro na requisição');
                    return response.json();
                })
                .then(data => {
                    wrapperSubsetor.classList.remove('loading');
                    selectSubsetor.innerHTML = '';

                    if (data.length === 0) {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Nenhum subsetor encontrado</option>';
                        selectSubsetor.disabled  = true;
                    } else {
                        selectSubsetor.innerHTML = '<option value="" disabled selected>Selecione o subsetor</option>';
                        data.forEach(function (item) {
                            const option    = document.createElement('option');
                            option.value    = item.descricao;
                            option.textContent = item.descricao;
                            selectSubsetor.appendChild(option);
                        });
                        selectSubsetor.disabled = false;
                    }
                })
                .catch(() => {
                    wrapperSubsetor.classList.remove('loading');
                    selectSubsetor.innerHTML = '<option value="" disabled selected>Erro ao carregar subsetores</option>';
                    selectSubsetor.disabled  = true;
                });
        });
    </script>

</body>
</html>
