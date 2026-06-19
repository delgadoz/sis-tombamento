<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit();
}

$usuario = $_SESSION['usuario']; 
$nome_completo = $_SESSION['nome'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Início</title>
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
            margin-bottom: 15px;
        }

        .content p {
            font-size: 1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <!-- TOPO -->
	<?php require 'includes/header.php'; ?>

    <!-- CONTEÚDO -->
    <main>
        <div class="content">
            <span class="badge">🔐 Área Restrita</span>

            <h2>Bem-vindo, <?= htmlspecialchars($nome_completo) ?> 👋</h2>

            <p>
                Este é o painel inicial do sistema.  
                Utilize o menu superior para navegar entre as funcionalidades.
            </p>
        </div>

		<?php require 'includes/footer.php'; ?>
    </main>

</body>
</html>
