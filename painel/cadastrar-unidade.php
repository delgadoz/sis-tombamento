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
    <style>

        /* ===== CONTEÚDO ===== */
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

        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 40px 12px 16px;
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

        .form-group select:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .form-group select option {
            background: #1a1a1a;
            color: #fff;
        }

        .form-group select option[value=""] {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-group input[type="text"]::placeholder {
            opacity: 0.5;
        }

        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-color: #ff9800;
        }

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

        /* Ícone de carregamento no select de subsetor */
        .select-wrapper.loading::after {
            content: '⟳';
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            from { transform: translateY(-50%) rotate(0deg); }
            to   { transform: translateY(-50%) rotate(360deg); }
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

        .aviso {
            background: rgba(255, 152, 0, 0.15);
            border: 1px solid rgba(255, 152, 0, 0.4);
            color: #ffb74d;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 600px) {
            main {
                padding: 16px;
            }

            .content {
                padding: 24px 16px;
                border-radius: 8px;
            }

            .content h2 {
                font-size: 1.5rem;
                margin-bottom: 16px;
            }

            .form-group input[type="text"],
            .form-group select {
                font-size: 16px; /* impede zoom automático no iOS */
                padding: 12px 40px 12px 12px;
            }

            .btn-submit {
                width: 100%;
                padding: 14px;
                font-size: 1rem;
            }
        }

    </style>
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
