<?php
function loadEnv($filePath, $requiredEnv) {
    if (!file_exists($filePath)) {
        throw new Exception("El archivo .env no existe en la ruta especificada: $filePath");
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!in_array($key, $requiredEnv)) {
            throw new Exception("La clave '$key' en el archivo .env no está definida en la lista de variables requeridas.");
        }
        //Usar el valor del entorno si existe, sino el del .env
        $value = getenv($key) ?: $value;
        define($key, $value); // Definir la constante
    }
}
$requiredEnv = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DB_PORT', 'SESSION_NAME', 'HASH_ALGORITHM', 'ENCRYPTION_KEY', 'API_BASE_URL', 'API_KEY', 'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_PORT', 'MAIL_ENCRYPTION', 'APP', 'VERS', 'DOMINIO', 'ERROR_LOG_PATH', 'MOSTRAR_ERRORES', 'SESSION_SAVE_PATH', 'APP_ENV'];
try {
    loadEnv(__DIR__ . '/.env', $requiredEnv);
} catch (Exception $e) {
    echo 'Error de configuración: ' . $e->getMessage();
    exit(1);
}
// Configuración de la sesión (USAR CONSTANTES)
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => DOMINIO, // Usar la constante DOMINIO
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id();
// Verificar si es una nueva sesion
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    // Es una sesión nueva.
    $_SESSION['LAST_ACTIVITY'] = time();
}
// Verificar si la sesión ha expirado (ej. 30 minutos de inactividad)
if (time() - $_SESSION['LAST_ACTIVITY'] > 3600) {
    // Destruir la sesión anterior
    session_destroy();
    session_start();
    $_SESSION['LAST_ACTIVITY'] = time();
}
$_SESSION['LAST_ACTIVITY'] = time();
if (!isset($_SESSION['csrf_tkn'])) {
    $_SESSION['csrf_tkn'] = bin2hex(random_bytes(32)); // Genera un token seguro
}
// Configuración de errores
$mostrar_errores = filter_var(MOSTRAR_ERRORES, FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $mostrar_errores ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ERROR_LOG_PATH ?: __DIR__ . '/../errors.log');
// Otras configuraciones de la aplicación
setlocale(LC_TIME, 'es_CO');