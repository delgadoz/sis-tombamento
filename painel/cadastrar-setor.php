<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Setor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <link rel="stylesheet" href="css/cadastrar-setor.css">
</head>
<body>

    <!-- TOPO -->
    <?php require 'includes/header.php'; ?>

    <!-- CONTEÚDO -->
    <main>
        <div class="content">
            <span class="badge">🏢 Cadastro de Setor</span>

            <h2>Novo Setor</h2>

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

            <form action="cadastro-setor.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <input
                        type="text"
                        id="descricao"
                        name="descricao"
                        placeholder="Digite o nome do setor"
                        maxlength="255"
                        required
                        autocomplete="off"
                    >
                </div>

                <button type="submit" class="btn-submit">Cadastrar Setor</button>
            </form>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

</body>
</html>
