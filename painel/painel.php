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
    <link rel="stylesheet" href="css/painel.css">
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
