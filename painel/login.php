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
	
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(
                rgba(0,0,0,0.6),
                rgba(0,0,0,0.6)
            ),
            url("imgs/background.jpg") no-repeat center center;
            background-size: cover;

            display: flex;
			flex-direction: column; 
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            background: rgba(0, 0, 0, 0.65);
            padding: 40px;
            border-radius: 12px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            text-align: center;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 25px;
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

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input {
            padding: 10px 28px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            outline: none;
        }

        input:focus {
            box-shadow: 0 0 0 2px #ff9800;
        }

        button {
            padding: 12px;
            border-radius: 8px;
            border: none;
            background: #ff9800;
            color: #000;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #ffa726;
        }

        .error {
            color: #ff6b6b;
            font-size: 0.9rem;
            display: none;
            text-align: center;
			padding-top:7px;
        }
		
</style>
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
