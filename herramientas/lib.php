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

function whe_prestamo() {
    $filtros = [];
    if (!empty($_POST['ftecnico'])) {
        $filtros[] = ['campo' => 'tecnico', 'valor' => $_POST['ftecnico'], 'operador' => '='];
    }
    if (!empty($_POST['fherramienta'])) {
        $filtros[] = ['campo' => 'id_herramienta', 'valor' => $_POST['fherramienta'], 'operador' => '='];
    }
    if (!empty($_POST['festado'])) {
        $filtros[] = ['campo' => 'estado_prestamo', 'valor' => $_POST['festado'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_prestamo() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-tasks','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Pendientes','icono'=>'fas fa-clock','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado_prestamo='PEN'"],
        ['titulo'=>'En Proceso','icono'=>'fas fa-spinner','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado_prestamo='PRO'"],
        ['titulo'=>'Completados','icono'=>'fas fa-check-circle','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado_prestamo='COM'"]
    ];
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM herramienta_prestamo WHERE estado=1" . $total['condicion'];
        $resultado_consulta = datos_mysql($sql);
        if ($resultado_consulta === null || !isset($resultado_consulta['responseResult'][0]['Total'])) {
            $rta .= generar_metrica('Error', 'fas fa-exclamation-circle', 'fa fa-level-up arrow-icon', 'N/A');
        } else {
            $rta .= generar_metrica($total['titulo'], $total['icono'], $total['indicador'], $resultado_consulta['responseResult'][0]['Total']);
        }
    }
    return $rta;
}

function lis_prestamo() {
    $regxPag = 15;
    $pag = si_noexiste('pag-prestamo', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_prestamo();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];

    $sqltot = "SELECT count(*) AS Total FROM herramienta_prestamo WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);

    $sql = "SELECT id_prestamo AS ACCIONES, id_prestamo AS id, fecha_prestamo, tecnico, estado_prestamo, observaciones FROM herramienta_prestamo WHERE " . $where . " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $regxPag;
    $types .= "ii";
    $datos = datos_mysql($sql, $params, $types);
    if ($datos['responseResult'] === []) return no_reg();
    return create_table($total, $datos['responseResult'], "prestamo", $regxPag, "lib.php");
}

function gra_prestamo() {
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s');
    $sql = "INSERT INTO herramienta_prestamo (fecha_prestamo, idusu_presto, idreqcom, tecnico, observaciones, estado_prestamo, usu_create, fecha_create, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $params = [
        ['type' => 's', 'value' => $_POST['fecha_prestamo']],
        ['type' => 'i', 'value' => $usu],
        ['type' => 'i', 'value' => $_POST['idreqcom'] ?? 0],
        ['type' => 'i', 'value' => $_POST['tecnico']],
        ['type' => 's', 'value' => $_POST['observaciones']],
        ['type' => 's', 'value' => 'PEN'],
        ['type' => 's', 'value' => $usu],
        ['type' => 's', 'value' => $fecha]
    ];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function lis_herramient() {
    $sql = "SELECT id_herramienta, nombre, descripcion, stock_total, stock_disponible FROM herramientas WHERE estado=1";
    $datos = datos_mysql($sql);
    if ($datos['responseResult'] === []) return no_reg();
    return create_table(0, $datos['responseResult'], "herramient", 15, "lib.php");
}

function lis_prestamo_detalle() {
    $idprestamo = $_POST['id'] ?? 0;
    $sql = "SELECT pd.id_detalle, pd.idherramienta, h.nombre, pd.cantidad, pd.fecha_prestamo, pd.fecha_devolucion FROM prestamo_detalle pd JOIN herramientas h ON pd.idherramienta = h.id_herramienta WHERE pd.idprestamo = ? AND pd.estado=1";
    $params = [['type' => 'i', 'value' => $idprestamo]];
    $datos = mysql_prepd($sql, $params);
    if ($datos['responseResult'] === []) return no_reg();
    return create_table(0, $datos['responseResult'], "prestamo_detalle", 15, "lib.php");
}

function focus_comreq() {
    return 'comreq';
}

function men_comreq() {
    $rta = cap_menus('comreq','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'comreq' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_prestamo() {
    $t = [
        'id_prestamo' => '',
        'fecha_prestamo' => '',
        'tecnico' => '',
        'observaciones' => '',
        'estado_prestamo' => 'PEN'
    ];
    $w = 'prestamo';
    $uPd = true;
    $d = get_prestamo();
    if ($d == "") $d = $t;
    $c = [];
    $c[] = new cmp('id', 'h', 100, $d['id_prestamo'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('fec', 'd', 3, $d['fecha_prestamo'], $w, 'Fecha Préstamo', '', '', '', true, $uPd, '', 'col-3');
    $c[] = new cmp('tec', 's', 3, $d['tecnico'], $w, 'Técnico', 'usuarios', '', '', true, $uPd, '', 'col-6');
    $c[] = new cmp('obs', 't', 500, $d['observaciones'], $w, 'Observaciones', '', '', '', true, $uPd, '', 'col-12');
    $c[] = new cmp('est', 's', 3, $d['estado_prestamo'], $w, 'Estado Préstamo', 'estado_prestamo', '', '', true, $uPd, '', 'col-3');
    $rta = '';
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_prestamo() {
    $id = $_POST['id'] ?? '';
    if ($id === '0' || empty($id)) return "";
    $sql = "SELECT * FROM herramienta_prestamo WHERE id_prestamo = ?";
    $params = [['type' => 'i', 'value' => $id]];
    $info = mysql_prepd($sql, $params);
    if (isset($info['responseResult']) && !empty($info['responseResult'])) {
        return $info['responseResult'][0];
    }
    return "";
}

function gra_prestamo_detalle() {
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s');
    $sql = "INSERT INTO prestamo_detalle (idprestamo, idherramienta, cantidad, fecha_prestamo, fecha_devolucion, usu_create, fecha_create, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    $params = [
        ['type' => 'i', 'value' => $_POST['idprestamo']],
        ['type' => 'i', 'value' => $_POST['idherramienta']],
        ['type' => 'i', 'value' => $_POST['cantidad']],
        ['type' => 's', 'value' => $_POST['fecha_prestamo']],
        ['type' => 's', 'value' => $_POST['fecha_devolucion']],
        ['type' => 's', 'value' => $usu],
        ['type' => 's', 'value' => $fecha]
    ];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function upd_prestamo() {
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s');
    $sql = "UPDATE herramienta_prestamo SET fecha_prestamo=?, tecnico=?, observaciones=?, estado_prestamo=?, usu_update=?, fecha_update=? WHERE id_prestamo=?";
    $params = [
        ['type' => 's', 'value' => $_POST['fecha_prestamo']],
        ['type' => 'i', 'value' => $_POST['tecnico']],
        ['type' => 's', 'value' => $_POST['observaciones']],
        ['type' => 's', 'value' => $_POST['estado_prestamo']],
        ['type' => 's', 'value' => $usu],
        ['type' => 's', 'value' => $fecha],
        ['type' => 'i', 'value' => $_POST['id_prestamo']]
    ];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function del_prestamo() {
    $id = $_POST['id'] ?? 0;
    $sql = "UPDATE herramienta_prestamo SET estado=0 WHERE id_prestamo=?";
    $params = [['type' => 'i', 'value' => $id]];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function del_prestamo_detalle() {
    $id = $_POST['id'] ?? 0;
    $sql = "UPDATE prestamo_detalle SET estado=0 WHERE id_detalle=?";
    $params = [['type' => 'i', 'value' => $id]];
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

function opc_estado_prestamo($id='') {
    $opciones = [
        ['value' => 'PEN', 'descripcion' => 'Pendiente'],
        ['value' => 'PRO', 'descripcion' => 'En Proceso'],
        ['value' => 'COM', 'descripcion' => 'Completado']
    ];
    if ($id === '') {
        return json_encode($opciones);
    } else {
        foreach ($opciones as $opcion) {
            if ($opcion['value'] == $id) {
                return json_encode([$opcion]);
            }
        }
        return json_encode([]);
    }
}

function opc_usuarios($id='') {
    return opc_sql("SELECT id_usuario, nombre FROM usuarios WHERE estado = 1 ORDER BY nombre", $id);
}

function opc_herramientas($id='') {
    return opc_sql("SELECT id_herramienta, nombre FROM herramientas WHERE estado=1 ORDER BY nombre", $id);
}


function tot_herramient() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-tools','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Disponibles','icono'=>'fas fa-check','indicador'=>'fa fa-level-up arrow-icon','condicion' => ' AND stock_disponible > 0'],
        ['titulo'=>'Prestadas','icono'=>'fas fa-hand-holding','indicador'=>'fa fa-level-down arrow-icon','condicion' => ' AND stock_disponible = 0']
    ];
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM herramientas WHERE estado=1" . $total['condicion'];
        $resultado_consulta = datos_mysql($sql);
        if ($resultado_consulta === null || !isset($resultado_consulta['responseResult'][0]['Total'])) {
            $rta .= generar_metrica('Error', 'fas fa-exclamation-circle', 'fa fa-level-up arrow-icon', 'N/A');
        } else {
            $rta .= generar_metrica($total['titulo'], $total['icono'], $total['indicador'], $resultado_consulta['responseResult'][0]['Total']);
        }
    }
    return $rta;
}
function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'prestamo') && ($b == 'acciones')) {
        $rta = "<nav class='menu left'>";
        // $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Requerimiento' id='".$c['ACCIONES']."' Onclick=\"mostrar('comreq','pro',event,'','lib.php',3,'Requerimientos');\"></li>";
        $perfil = obtenerPerfil($_SESSION['documento']);
        if ($perfil == '1'|| $perfil == '7' ) {
            $rta .= "<li class='fa-solid fa-tasks icon' title='Gestionar Requerimiento' id='".$c['ACCIONES']."' Onclick=\"mostrar('reqlidser','pro',event,'','gestiona.php',3,'Gestión de Requerimientos');\"></li>";
        }
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'prestamo') {
        
    }
    return $rta;
}