<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];

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
    <title>Cadastrar Subsetor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/geral.css">
    <link rel="stylesheet" href="css/cadastrar-subsetor.css">
</head>
<body>

    <!-- TOPO -->
    <?php require 'includes/header.php'; ?>

    <!-- CONTEÚDO -->
    <main>
        <div class="content">
            <span class="badge">🗂️ Cadastro de Subsetor</span>

            <h2>Novo Subsetor</h2>

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
                    ⚠️ Nenhum setor cadastrado. Cadastre um setor antes de adicionar um subsetor.
                </div>
            <?php else: ?>
                <form action="cadastro-subsetor.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">

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

                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <input
                            type="text"
                            id="descricao"
                            name="descricao"
                            placeholder="Digite o nome do subsetor"
                            maxlength="255"
                            required
                            autocomplete="off"
                        >
                    </div>

                    <button type="submit" class="btn-submit">Cadastrar Subsetor</button>
                </form>
            <?php endif; ?>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

</body>
</html>