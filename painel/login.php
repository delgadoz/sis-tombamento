<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header('Location: painel');
    exit(); 
}

$titulo = "Área de Login";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/geral.css">
	<link rel="stylesheet" href="css/login.css">
</head>
<body>

    <div class="container">
        <span class="badge">🔐 Área Restrita</span>

        <h1><?= $titulo ?></h1>
		
		<?php if (isset($_SESSION['erro'])): ?>
			<div class="error" style="display:block;padding-bottom:8px;">
			<?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
			</div>
		<?php endif; ?>

		<form id="loginForm" method="POST" action="autenticar" novalidate>
			<div>
				<input type="email" name="email" id="email" placeholder="E-mail" required>
				<div class="error" id="emailError">Digite um e-mail válido.</div>
			</div>

			<div>
				<input type="password" name="senha" id="senha" placeholder="Senha" required>
				<div class="error" id="senhaError">
					A senha deve ter no mínimo 8 caracteres.
				</div>
			</div>

			<button type="submit">Entrar</button>
		</form>			
    </div>
	
	<?php include 'includes/footer.php'; ?>

	<script>
		const form = document.getElementById('loginForm');
		const email = document.getElementById('email');
		const senha = document.getElementById('senha');

		const emailError = document.getElementById('emailError');
		const senhaError = document.getElementById('senhaError');

		function validarEmail(valor) {
			const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return regex.test(valor);
		}

		form.addEventListener('submit', function (e) {
			let valido = true;

			// Validação do e-mail
			if (!validarEmail(email.value)) {
				emailError.style.display = 'block';
				valido = false;
			} else {
				emailError.style.display = 'none';
			}

			// Validação da senha
			if (senha.value.length < 8) {
				senhaError.style.display = 'block';
				valido = false;
			} else {
				senhaError.style.display = 'none';
			}

			if (!valido) {
				e.preventDefault();
			}
		});
	</script>

</body>
</html>
