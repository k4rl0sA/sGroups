<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/gestion.php';

$perf = perfil($_POST['tb']);
if (!isset($_SESSION['documento'])) {
    log_error("Error 20: Usuario No Autorizado.".$_SESSION['documento']);
    http_response_code(401);
    echo json_encode(['redirect' => '/']);
    exit();
}

if (!isset($_POST['csrf_tkn']) || $_POST['csrf_tkn'] !== $_SESSION['csrf_tkn']) {
    log_error("Error 24: Intento de CSRF detectado. " . $_POST['csrf_tkn'] . ' frente a ' . $_SESSION['csrf_tkn']);
    http_response_code(403);
    exit();
}

$a = filter_var($_POST['a'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$tb = filter_var($_POST['tb'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$func = $a . '_' . $tb;

if (!function_exists($func)) {
    log_error("Error 21: Función no encontrada. Intento de llamar a: " . $func);
    http_response_code(400);
    echo json_encode(['error' => 'Función no encontrada', 'funcion' => $func]);
    exit();
}

try {
    $rta = $func();
    if (is_array($rta)) {
        echo json_encode($rta);
    } else {
        echo $rta;
    }
} catch (Exception $e) {
    log_error("Error 23: Excepción al ejecutar la función. Función: " . $func . ", Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

function lis_herramientas() {
    $sql = "SELECT id_herramienta, nombre, descripcion, stock_total, stock_disponible FROM herramientas WHERE estado=1";
    $datos = datos_mysql($sql);
    if ($datos['responseResult'] === []) return no_reg();
    return create_table(0, $datos['responseResult'], "herramientas", 15, "herramient.php");
}

function gra_herramientas() {
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s', strtotime('-5 hours');
    $sql = "INSERT INTO herramientas (nombre, descripcion, stock_total, stock_disponible, usu_create, fecha_create, estado) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $params = [
        ['type' => 's', 'value' => $_POST['nombre']],
        ['type' => 's', 'value' => $_POST['descripcion']],
        ['type' => 'i', 'value' => $_POST['stock_total']],
        ['type' => 'i', 'value' => $_POST['stock_disponible']],
        ['type' => 's', 'value' => $usu],
        ['type' => 's', 'value' => $fecha]
    ];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function upd_herramientas() {
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s', strtotime('-5 hours');
    $sql = "UPDATE herramientas SET nombre=?, descripcion=?, stock_total=?, stock_disponible=?, usu_update=?, fecha_update=? WHERE id_herramienta=?";
    $params = [
        ['type' => 's', 'value' => $_POST['nombre']],
        ['type' => 's', 'value' => $_POST['descripcion']],
        ['type' => 'i', 'value' => $_POST['stock_total']],
        ['type' => 'i', 'value' => $_POST['stock_disponible']],
        ['type' => 's', 'value' => $usu],
        ['type' => 's', 'value' => $fecha],
        ['type' => 'i', 'value' => $_POST['id_herramienta']]
    ];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function del_herramientas() {
    $id = $_POST['id'] ?? 0;
    $sql = "UPDATE herramientas SET estado=0 WHERE id_herramienta=?";
    $params = [['type' => 'i', 'value' => $id]];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'herramientas') && ($b == 'acciones')) {
        $rta = "<nav class='menu left'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Herramienta' id='".$c['id_herramienta']."' Onclick=\"mostrar('herramientas','pro',event,'','herramient.php',3,'Editar Herramienta');\"></li>";
        $rta .= "<li class='fa-solid fa-trash icon' title='Eliminar Herramienta' id='".$c['id_herramienta']."' Onclick=\"eliminar('herramientas',this);\"></li>";
        $rta .= "</nav>";
    }
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'herramientas') {
        if ($c['stock_disponible'] == 0) {
            $rta = 'bg-light-red';
        } elseif ($c['stock_disponible'] < $c['stock_total']) {
            $rta = 'bg-light-yellow';
        } else {
            $rta = 'bg-light-green';
        }
    }
    return $rta;
}

function opc_herramientas($id='') {
    return opc_sql("SELECT id_herramienta, nombre FROM herramientas WHERE estado=1 ORDER BY nombre", $id);
}

function opc_stock($id='') {
    return opc_sql("SELECT stock_total, stock_disponible FROM herramientas WHERE estado=1 ORDER BY nombre", $id);
}
