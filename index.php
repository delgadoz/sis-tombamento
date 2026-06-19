<?php
$msg_alert = $_GET['msg-alert'] ?? null;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Página Inicial</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    width: 100%;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #ffffff;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

.container {
    width: 100%;
    max-width: 400px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.btn {
    width: 100%;
    padding: 16px 20px;
    text-align: center;
    text-decoration: none;
    background: #ffffff;
    color: #333333;
    font-size: 18px;
    font-weight: 600;
    border: 1px solid #dcdcdc;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
    transition: .2s;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn:active {
    transform: scale(.98);
}

/* ALERTA */

.alert-overlay {
    position: fixed;
    inset: 0;

    background: rgba(0,0,0,.45);

    display: flex;
    justify-content: center;
    align-items: center;

    z-index: 9999;
}

.alert-box {
    width: 90%;
    max-width: 420px;

    background: white;

    border-radius: 16px;

    padding: 25px;

    box-shadow: 0 15px 40px rgba(0,0,0,.25);

    text-align: center;

    animation: aparecer .25s ease;
}

.alert-title {
    font-size: 22px;
    font-weight: bold;

    color: #222;

    margin-bottom: 15px;
}

.alert-message {
    color: #555;

    line-height: 1.5;

    margin-bottom: 25px;
}

.alert-button {
    border: none;

    background: #0069d9;

    color: white;

    padding: 12px 30px;

    border-radius: 10px;

    cursor: pointer;

    font-size: 16px;
}

.alert-button:hover {
    background: #0053ad;
}

@keyframes aparecer {
    from {
        opacity: 0;
        transform: scale(.9);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (max-width:480px){

.btn{
font-size:16px;
padding:15px;
}

.container{
padding:16px;
}

}

</style>

</head>

<body>

<?php if (!empty($msg_alert)): ?>

<div class="alert-overlay" id="alert">
    <div class="alert-box">

        <div class="alert-title">
            Aviso
        </div>

        <div class="alert-message">
            <?= htmlspecialchars($msg_alert) ?>
        </div>

        <button
            class="alert-button"
            onclick="fecharAlerta()"
        >
            Fechar
        </button>

    </div>
</div>

<?php endif; ?>

<div class="container">

<a href="bens/saude" class="btn">
Bens da Secretaria Municipal de Saúde
</a>

<a href="bens/prefeitura" class="btn">
Bens da Prefeitura Municipal de Caraúbas
</a>

</div>

<script>

function fecharAlerta() {

    document
        .getElementById('alert')
        ?.remove();

}

</script>

</body>
</html>