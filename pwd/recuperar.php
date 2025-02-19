<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/gestion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = filter_var($_POST['usuario'], FILTER_SANITIZE_NUMBER_INT);

    $con = db_connect();
    $stmt = $con->prepare("SELECT correo FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($email);
        $stmt->fetch();
        
        $token = bin2hex(random_bytes(32)); // Token aleatorio
        $expira = date('Y-m-d H:i:s', strtotime('-5 hours +10 minutes'));

        // Guardamos el token en la BD
        $stmt = $con->prepare("UPDATE usuarios SET token = ?, exp_tkn = ? WHERE id_usuario = ?");
        $stmt->bind_param("ssi", $token, $expira, $usuario);
        $stmt->execute();

        $enlace = DOMINIO."/pwd/cambiar_password.php?token=" . $token;

        $sql="SELECT * FROM usuarios WHERE id_usuario='".$usuario."'";
			$info=datos_mysql($sql);
			$rta= $info['responseResult'][0];

        $mails = [$email];
        $subject = "Recuperación de contraseña";
        $body = "";
        $placeholders = ["nombre"=>$rta['nombre'],"enlace"=>$enlace,"info"=>"assycoltecnologia@gmail.com","expira"=>$expira];
        $result = sendMail($mails, $subject, $body, $placeholders,'clave');
        if ($result) {
            echo "Se ha enviado un correo a su cuenta registrada, con las instrucciones para recuperar la contraseña.";
        } else {
            echo "No se pudo enviar el correo.";
        }
    } else {
        echo "Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Recuperar Contraseña</title></head>
<body>
    <form method="POST">
        <label>Ingrese su documento:</label>
        <input type="text" name="usuario" required>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
