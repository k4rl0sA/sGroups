<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/gestion.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = test_input($_POST['usuario']);
    $password = test_input($_POST['password']);
    try {
        if (verificarUsuario($usuario, $password)) {
            log_error("Inicio de sesión exitoso para: " . $usuario);
            // $redirect = ($password === "Subred2025+") ? "cambio-clave/" : "Inicio/";
            header("Location:Inicio/");
            exit();
        } else {
            log_error($usuario.' = Error 1: Nombre de usuario o contraseña incorrectos.');
            displayError("Nombre de usuario o contraseña incorrectos.");
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        log_error($usuario.' = Error 2: '.$e->getMessage());
    }
}
// Función para mostrar errores
function displayError($message) {
    log_error($message);
    echo "<div class='error'>
            <span class='closebtn' onclick=\"this.parentElement.style.display='none';\">&times;</span> 
            <strong>Error!</strong> $message
          </div>";
          exit;
}