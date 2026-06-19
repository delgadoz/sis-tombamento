<?php
// Você pode usar PHP aqui futuramente (login, configs, etc)
$titulo = "Site em Desenvolvimento";
$mensagem = "Estamos trabalhando para trazer algo incrível em breve!";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
        }

        .container {
            background: rgba(0, 0, 0, 0.65);
            padding: 40px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            padding: 10px 20px;
            background: #ff9800;
            color: #000;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        footer {
            margin-top: 30px;
            font-size: 0.85rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>

    <div class="container">
        <span class="badge">🚧 Em Desenvolvimento</span>

        <h1><?= $titulo ?></h1>

        <p><?= $mensagem ?></p>

        <footer>
            &copy; <?= date("Y") ?> - Todos os direitos reservados
        </footer>
    </div>

</body>
</html>
