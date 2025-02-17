<?php
// Verificar si los archivos incluidos existen
if (!file_exists(__DIR__ . '/../src/gestion.php')) {
    die("El archivo gestion.php no existe.");
}
require_once __DIR__ . '/../src/gestion.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'] ?? null;
    $nueva_password = $_POST['nueva_password'] ?? null;

    if (!$id_usuario || !$nueva_password) {
        die("Datos incompletos.");
    }

    $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);

    $con = db_connect();
    $stmt = $con->prepare("UPDATE usuarios SET clave = ?, token= NULL, exp_tkn = NULL WHERE id_usuario = ?");
    $stmt->bind_param("si", $hashed_password, $id_usuario);
    $stmt->execute();

    echo "Contraseña actualizada con éxito. <a href='../index.php'>Iniciar sesión</a>";
}
?>
