<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$nome_completo = $_SESSION['nome'];
$cnpj          = $_SESSION['cnpj'];
$usuario       = $_SESSION['usuario'];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações da Conta</title>
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

        .group-box legend {
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
        .form-group input[type="password"] {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 14px;
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

        .form-group input::placeholder {
            opacity: 0.4;
        }

        .form-group input:focus {
            border-color: #ff9800;
        }

        .form-group input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ===== INPUT DE SENHA COM TOGGLE DE VISIBILIDADE ===== */
        .senha-wrapper {
            position: relative;
        }

        .senha-wrapper input {
            padding-right: 44px !important;
        }

        .senha-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-size: 1rem;
            padding: 4px;
            line-height: 1;
            user-select: none;
        }

        .senha-toggle:hover {
            color: #ff9800;
        }

        /* ===== FORÇA DA SENHA ===== */
        .forca-senha {
            display: flex;
            gap: 6px;
            margin-top: 4px;
        }

        .forca-senha .barra {
            height: 4px;
            flex: 1;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.15);
            transition: background 0.25s;
        }

        .forca-senha .barra.fraca   { background: #dc3545; }
        .forca-senha .barra.media   { background: #ff9800; }
        .forca-senha .barra.forte   { background: #28a745; }

        .forca-texto {
            font-size: 0.78rem;
            opacity: 0.6;
            margin-top: 4px;
        }

        .requisitos-senha {
            font-size: 0.78rem;
            opacity: 0.55;
            margin-top: 6px;
            line-height: 1.5;
        }

        .requisitos-senha .ok {
            color: #5dd879;
            opacity: 1;
        }

        /* ===== CONFIRMAÇÃO DE SENHA ATUAL ===== */
        .confirma-senha-box {
            background: rgba(255, 152, 0, 0.06);
            border: 1px dashed rgba(255, 152, 0, 0.4);
            border-radius: 10px;
            padding: 18px 20px;
            margin-top: 4px;
        }

        .confirma-senha-box .aviso {
            font-size: 0.82rem;
            opacity: 0.65;
            margin: 0 0 12px 0;
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
            display: block;
            margin: 8px 0 0;
        }

        .btn-submit:hover  { background: #e68900; }
        .btn-submit:active { transform: scale(0.98); }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

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

            .form-group input {
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
            <span class="badge">⚙️ Configurações</span>
            <h2>Configurações da Conta</h2>

            <?php if (!empty($_SESSION['erro'])): ?>
                <div class="mensagem erro">⚠️ <?= htmlspecialchars($_SESSION['erro']) ?></div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['sucesso'])): ?>
                <div class="mensagem sucesso">✅ <?= htmlspecialchars($_SESSION['sucesso']) ?></div>
                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <!-- ===== ALTERAR SENHA ===== -->
            <form action="processar-configuracoes.php" method="POST" id="formSenha" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))) ?>">
                <input type="hidden" name="acao" value="alterar_senha">

                <div class="group-box">
                    <span class="legend">🔑 Alterar Senha</span><br><br>

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="nova_senha">Nova Senha</label>
                            <div class="senha-wrapper">
                                <input type="password" id="nova_senha" name="nova_senha"
                                    placeholder="Mínimo 8 caracteres" required autocomplete="new-password"
                                    minlength="8" maxlength="72">
                                <button type="button" class="senha-toggle" data-target="nova_senha">👁</button>
                            </div>
                            <div class="forca-senha" id="forcaSenhaBarras">
                                <div class="barra"></div>
                                <div class="barra"></div>
                                <div class="barra"></div>
                            </div>
                            <p class="forca-texto" id="forcaSenhaTexto">Digite a nova senha</p>
                        </div>

                        <div class="form-group">
                            <label for="nova_senha_confirma">Confirmar Nova Senha</label>
                            <div class="senha-wrapper">
                                <input type="password" id="nova_senha_confirma" name="nova_senha_confirma"
                                    placeholder="Repita a nova senha" required autocomplete="new-password"
                                    minlength="8" maxlength="72">
                                <button type="button" class="senha-toggle" data-target="nova_senha_confirma">👁</button>
                            </div>
                            <p class="forca-texto" id="confirmaSenhaTexto"></p>
                        </div>

                        <div class="form-group full-width">
                            <p class="requisitos-senha" id="requisitosSenha">
                                A senha deve conter: <span id="reqTamanho">no mínimo 8 caracteres</span> ·
                                <span id="reqLetra">letras</span> ·
                                <span id="reqNumero">números</span>
                            </p>
                        </div>

                    </div>

                    <div class="confirma-senha-box">
                        <p class="aviso">🔒 Confirme sua senha atual para salvar esta alteração. Por segurança, você precisará entrar novamente após a troca.</p>
                        <div class="form-group">
                            <label for="senha_atual_senha">Senha Atual</label>
                            <div class="senha-wrapper">
                                <input type="password" id="senha_atual_senha" name="senha_atual"
                                    placeholder="Sua senha atual" required autocomplete="current-password">
                                <button type="button" class="senha-toggle" data-target="senha_atual_senha">👁</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="btnSalvarSenha">Salvar Nova Senha</button>
                </div>
            </form>
        </div>

        <?php require 'includes/footer.php'; ?>
    </main>

    <script>
        // ===== TOGGLE DE VISIBILIDADE DE SENHA =====
        document.querySelectorAll('.senha-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '🙈';
                } else {
                    input.type = 'password';
                    this.textContent = '👁';
                }
            });
        });

        // ===== INDICADOR DE FORÇA DA SENHA =====
        const novaSenha       = document.getElementById('nova_senha');
        const confirmaSenha   = document.getElementById('nova_senha_confirma');
        const barras          = document.querySelectorAll('#forcaSenhaBarras .barra');
        const forcaTexto      = document.getElementById('forcaSenhaTexto');
        const confirmaTexto   = document.getElementById('confirmaSenhaTexto');
        const reqTamanho      = document.getElementById('reqTamanho');
        const reqLetra        = document.getElementById('reqLetra');
        const reqNumero       = document.getElementById('reqNumero');

        function avaliarForca(senha) {
            const temTamanho = senha.length >= 8;
            const temLetra   = /[A-Za-z]/.test(senha);
            const temNumero  = /[0-9]/.test(senha);
            const temEspecial = /[^A-Za-z0-9]/.test(senha);
            const temMaiusculaEMinuscula = /[a-z]/.test(senha) && /[A-Z]/.test(senha);

            reqTamanho.classList.toggle('ok', temTamanho);
            reqLetra.classList.toggle('ok', temLetra);
            reqNumero.classList.toggle('ok', temNumero);

            let pontos = 0;
            if (temTamanho) pontos++;
            if (temLetra && temNumero) pontos++;
            if (temEspecial || temMaiusculaEMinuscula) pontos++;

            barras.forEach(b => b.className = 'barra');

            if (senha.length === 0) {
                forcaTexto.textContent = 'Digite a nova senha';
                return;
            }

            if (!temTamanho || !temLetra || !temNumero) {
                barras[0].classList.add('fraca');
                forcaTexto.textContent = 'Senha não atende aos requisitos mínimos';
                return;
            }

            if (pontos === 1) {
                barras[0].classList.add('fraca');
                forcaTexto.textContent = 'Senha fraca';
            } else if (pontos === 2) {
                barras[0].classList.add('media');
                barras[1].classList.add('media');
                forcaTexto.textContent = 'Senha média';
            } else {
                barras.forEach(b => b.classList.add('forte'));
                forcaTexto.textContent = 'Senha forte';
            }
        }

        function verificarConfirmacao() {
            if (confirmaSenha.value.length === 0) {
                confirmaTexto.textContent = '';
                confirmaTexto.style.color = '';
                return;
            }
            if (confirmaSenha.value === novaSenha.value) {
                confirmaTexto.textContent = '✓ As senhas coincidem';
                confirmaTexto.style.color = '#5dd879';
            } else {
                confirmaTexto.textContent = '✕ As senhas não coincidem';
                confirmaTexto.style.color = '#ff6b7a';
            }
        }

        novaSenha.addEventListener('input', function () {
            avaliarForca(this.value);
            verificarConfirmacao();
        });
        confirmaSenha.addEventListener('input', verificarConfirmacao);

        // ===== VALIDAÇÃO DO FORM DE SENHA ANTES DE ENVIAR =====
        document.getElementById('formSenha').addEventListener('submit', function (e) {
            const senha = novaSenha.value;
            const temTamanho = senha.length >= 8;
            const temLetra   = /[A-Za-z]/.test(senha);
            const temNumero  = /[0-9]/.test(senha);

            if (!temTamanho || !temLetra || !temNumero) {
                e.preventDefault();
                novaSenha.focus();
                novaSenha.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (senha !== confirmaSenha.value) {
                e.preventDefault();
                confirmaSenha.focus();
                confirmaSenha.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        });
    </script>

</body>
</html>
