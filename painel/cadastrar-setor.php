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

        /* ===== FORMULÁRIO ===== */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 24px;
        }

        .form-group label {
            font-size: 0.95rem;
            font-weight: bold;
            opacity: 0.9;
        }

        .form-group input[type="text"] {
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: border 0.2s;
        }

        .form-group input[type="text"]::placeholder {
            opacity: 0.5;
        }

        .form-group input[type="text"]:focus {
            border-color: #ff9800;
        }

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
			margin: 0 auto;
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

        .mensagem.sucesso {
            background: rgba(40, 167, 69, 0.25);
            border: 1px solid rgba(40, 167, 69, 0.6);
            color: #5dd879;
        }

    </style>
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
