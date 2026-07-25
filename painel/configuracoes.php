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
    <link rel="stylesheet" href="css/configuracoes.css">
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
