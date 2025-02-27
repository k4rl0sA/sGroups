<?php
session_start();
include __DIR__ . '/config/claves.php'; 
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
<title>SOLUTION GROUPS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="keywords" content="login, IPS, salud, proteger, servicios, vacunas, medicina, ips">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' https://www.google.com https://www.gstatic.com 'sha256-NYXvD0OuUHzREFi7qe8/qfEoi1ThJvW5g80vXESTWlE=';">
    <link href="./libs/css/styleLogin.css?v=2.0" rel="stylesheet" type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($claves['publica'], ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body>
<div class="login">
    <div class="login-top">
        <img src="../libs/img/p.png" alt="Logo">
    </div>
    <h1>Inicio</h1>
    <form action="login.php" method="post" class="frm" ><!-- autocomplete="off" -->
        <div class="input">
            <input type="text" name="usuario" required minlength="8" maxlength="18" pattern="[0-9]+" title="Solo números">
            <label>Usuario</label>
        </div>
        <div class="input">
            <input type="password" name="password" required minlength="8">
            <label>Contraseña</label>
        </div>
        <input type="hidden" id="tkn" name="tkn">
        <button type="submit" id='btn' class="btn" disabled>
            <span class="text">Ingresar</span>
            <i class="fas fa-sign-in-alt icon"></i>
        </button>
    </form>
    <p>¿Olvidó su contraseña? <a href="pwd/recuperar.php">Click Aquí</a></p>

</div>
<script>
    grecaptcha.ready(function(){
        grecaptcha.execute(
            '<?php echo htmlspecialchars($claves['publica']); ?>',
            {action: 'formulario'}
        ).then(function(token){
            const tkn = document.getElementById('tkn');
            const btn = document.getElementById('btn');
            tkn.value = token;
            btn.disabled = false;
        }).catch(function(error) {
            console.error("Error al obtener el token de reCAPTCHA:", error);
            alert("Error al verificar reCAPTCHA. Por favor, inténtelo de nuevo.");
        });
    });
</script>
</body>
</html>