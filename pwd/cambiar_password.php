<?php
// Verificar si los archivos incluidos existen
if (!file_exists(__DIR__ . '/../src/gestion.php')) {
    die("El archivo gestion.php no existe.");
}
require_once __DIR__ . '/../src/gestion.php';

// Obtener el token de la URL
$token = $_GET['token'] ?? null;
if (!$token) {
    die("Token no válido.");
}

// Conectar a la base de datos
$con = db_connect();
if (!$con) {
    die("Error de conexión a la base de datos.");
}

// Preparar y ejecutar la consulta SQL
$stmt = $con->prepare("SELECT id_usuario FROM usuarios WHERE token = ? AND exp_tkn > DATE_SUB(NOW(), INTERVAL 5 HOUR)");
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $con->error);
}
$stmt->bind_param("s", $token);
if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}
$stmt->store_result();

// Verificar si el token es válido
if ($stmt->num_rows === 0) {
    die("Token inválido o expirado.");
}

// Obtener el ID del usuario
$stmt->bind_result($id_usuario);
$stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cambiar Contraseña</title>
</head>
<body>
    <form action="procesar_cambio.php" method="POST">
        <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
        <label>Nueva contraseña:</label>
        <input type="password" name="nueva_password" required minlength="8">
        <button type="submit">Actualizar Contraseña</button>
    </form>
</body>
</html>