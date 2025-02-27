<?php
session_start(); // Inicia la sesión
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/gestion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = test_input($_POST['usuario']);
    $password = test_input($_POST['password']);
    try {
        if (verificarUsuario($usuario, $password)) {
            log_error("Inicio de sesión exitoso para: " . $usuario);
            header("Location: Inicio/");
            exit();
        } else {
            log_error($usuario.' = Error 1: Nombre de usuario o contraseña incorrectos.');
            $_SESSION['error'] = "Nombre de usuario o contraseña incorrectos."; // Almacena el error en la sesión
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        log_error($usuario.' = Error 2: '.$e->getMessage());
        $_SESSION['error'] = "Ocurrió un error inesperado. Por favor, inténtelo de nuevo."; // Almacena el error en la sesión
        header("Location: index.php");
        exit();
    }
}