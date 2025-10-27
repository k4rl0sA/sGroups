<?php
/* require_once __DIR__ . '/../config/config.php';
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

function whe_ordcom() {
    $filtros = [];
    $params = [];
    $types = '';

    $map = ['fcliente'   => ['field' => 'O.cliente',   'type' => 'i'],'fcomercial' => ['field' => 'O.comercial', 'type' => 'i'],'fgestor'    => ['field' => 'O.gestor',    'type' => 'i'],'festado'    => ['field' => 'O.estado',    'type' => 's'],];
    foreach ($map as $key => $info) {
        if (!empty($_POST[$key])) {
            $filtros[] = "{$info['field']} = ?";
            $params[] = $_POST[$key];
            $types .= $info['type'];
        }
    }
    if (!isset($_POST['festado']) || $_POST['festado'] === '') {
        $filtros[] = "O.estado = 'A'";
    }
    $where = implode(' AND ', $filtros);
    return ['where' => $where, 'params' => $params, 'types' => $types];
}

function lis_ordcom() {
    $regxPag = 15;
    $pag = si_noexiste('pag-ordcom', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_ordcom();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];


    $sqltot = "SELECT COUNT(*) total FROM orden_compra O";
    if (trim($where) !== '') {
        $sqltot .= " WHERE $where";
    }
    $total = obtener_total_registros($sqltot, $params, $types);

    $sql = "SELECT O.id_ordcom AS ACCIONES,
                O.id_ordcom AS 'N° Orden',
                O.cliente AS Cliente,
                O.valor AS Valor,
                O.factura AS Factura,
                O.comercial AS Comercial,
                O.gestor AS Gestor,
                O.estado AS Estado,
                DATE_FORMAT(O.fecha_create, '%d/%m/%Y %H:%i') AS 'Fecha Creación'
            FROM orden_compra O";
    if (trim($where) !== '') {
        $sql .= " WHERE $where";
    }
    $sql .= " ORDER BY O.fecha_create DESC";

    $datos = obtener_datos_paginados($sql, '', $params, $types, $offset, $regxPag);
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "ordcom", $regxPag, "lib.php");
}

function cmp_ordcom() {
    $rta = "";
    $t = ['id_ordcom'=>'','cliente'=>'','valor'=>'','factura'=>'','comercial'=>'','gestor'=>'','estado'=>'A'];
    $w = 'ordcom';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_ordcom();
    if ($d == "") {$d = $t;}
    $o = 'oc';
    $c[] = new cmp('id', 'h', 100, $d['id_ordcom'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('cli', 's', 11, $d['cliente'], $w.' '.$o, 'Cliente', 'clientes', '', '', true, true, '', 'col-3');
    $c[] = new cmp('val', 'n', 11, $d['valor'], $w.' '.$o, 'Valor', 'valor', '', '', true, true, '', 'col-2');
    $c[] = new cmp('fac', 'n', 11, $d['factura'], $w.' '.$o, 'Factura', 'factura', '', '', true, true, '', 'col-2');
    $c[] = new cmp('com', 's', 11, $d['comercial'], $w.' '.$o, 'Comercial', 'comerciales', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ges', 's', 11, $d['gestor'], $w.' '.$o, 'Gestor', 'gestores', '', '', true, true, '', 'col-2');
    $c[] = new cmp('est', 's', 1, $d['estado'], $w.' '.$o, 'Estado', 'estados_ordcom', '', '', true, true, '', 'col-2');

    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_ordcom() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = intval($_POST['id']);
        $sql = "SELECT * FROM orden_compra WHERE id_ordcom = ?";
        $info = datos_mysql($sql, [$id], "i");
        return $info['responseResult'][0] ?? "";
    }
}

function gra_ordcom() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $est = ($_POST['est']=='1') ? 'A' : 'I' ;
    $commonParams = [
        ['type' => 's', 'value' => $_POST['req']],
        ['type' => 'd', 'value' => $_POST['emp']],
        ['type' => 's', 'value' => $_POST['ofi']],
        ['type' => 'i', 'value' => $_POST['mat']],
        ['type' => 's', 'value' => $_POST['act']],
        ['type' => 's', 'value' => $_POST['obs']],
        ['type' => 's', 'value' => $_POST['tec']],
        ['type' => 's', 'value' => $_POST['det']]
        
    ];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO orden_servi VALUES (NULL,?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?)";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 's', 'value' => $_POST['est']]
            ]
        );
    } else {
        $sql = "UPDATE orden_servi SET    provedor=?,credito=?,direccion=?,ciudad=?,nit=?,comercial=?,n_contacto=?,correo=?,descripcion=?,pagina_web=?,telefono=?,movil_2=?,usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado=? 
            WHERE id_ordser = ?";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 's', 'value' => $_POST['est']],
                ['type' => 'i', 'value' => $id[0]]
            ]
        );
    }
    
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8'); 
    echo json_encode($rta);
    exit;
}

function men_ordcom() {
    return cap_menus('ordcom', 'pro');
}
function focus_ordcom() {
    return 'ordcom';
}

function opc_clientes($id='') {
    return opc_sql('SELECT id_cliente, cliente FROM clientes WHERE estado=1 ORDER BY cliente', $id);
}
function opc_comerciales($id='') {
    return opc_sql('SELECT id_usuario, nombre FROM usuarios WHERE perfil=3 AND estado=1 ORDER BY nombre', $id);
}
function opc_gestores($id='') {
    return opc_sql('SELECT id_usuario, nombre FROM usuarios WHERE perfil=5 AND estado=1 ORDER BY nombre', $id);
}
function opc_estados_ordcom($id='') {
    return opc_sql('SELECT DISTINCT estado, estado FROM orden_compra ORDER BY estado', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'ordcom') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Orden' id='".$c['ACCIONES']."' Onclick=\"mostrar('ordcom','pro',event,'','lib.php',4,'Orden de Compra');\"></li>";
        $rta .= "</nav>";
    }
    return $rta;
}
function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'ordcom' && $c['Estado'] == 'I') {
        $rta = 'bg-light-red';
    }
    return $rta;
} */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/gestion.php';

if ($_POST['a'] != 'opc') $perf = perfil($_POST['tb']);
if (!isset($_SESSION['documento'])) {
    log_error("Error 20: Usuario No Autorizado." . $_SESSION['documento']);
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

function whe_ordcom() {
    $filtros = [];
    if (!empty($_POST['fcliente'])) {
        $filtros[] = ['campo' => 'OC.cliente', 'valor' => $_POST['fcliente'], 'operador' => '='];
    }
    if (!empty($_POST['fcomercial'])) {
        $filtros[] = ['campo' => 'OC.comercial', 'valor' => $_POST['fcomercial'], 'operador' => '='];
    }
    if (!empty($_POST['ffactura'])) {
        $filtros[] = ['campo' => 'OC.factura', 'valor' => $_POST['ffactura'], 'operador' => '='];
    }
    if (!empty($_POST['festado'])) {
        $filtros[] = ['campo' => 'OC.estado', 'valor' => $_POST['festado'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_ordcom() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-file-invoice','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activas','icono'=>'fas fa-spinner','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='A'"],
        ['titulo'=>'Completadas','icono'=>'fas fa-check-circle','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='C'"],
        ['titulo'=>'Pendientes','icono'=>'fas fa-clock','indicador'=>'fa fa-level-up arrow-icon','condicion' =>" AND estado='P'"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM orden_compra OC WHERE ";
        $filter = whe_ordcom();
        
        if (!isset($filter['where']) || !isset($filter['params']) || !isset($filter['types'])) {
            $rta .= generar_metrica('Error', 'fas fa-exclamation-circle', 'fa fa-level-up arrow-icon', 'N/A');
            continue;
        }
        
        $sql .= $filter['where'] . $total['condicion'];
        $params = $filter['params'];
        $types = $filter['types'];
        $resultado_consulta = exec_sql($sql, $params, $types);
        
        if ($resultado_consulta === null || !isset($resultado_consulta[0]['Total'])) {
            $rta .= generar_metrica('Error', 'fas fa-exclamation-circle', 'fa fa-level-up arrow-icon', 'N/A');
        } else {
            $rta .= generar_metrica($total['titulo'], $total['icono'], $total['indicador'], $resultado_consulta[0]['Total']);
        }
    }
    return $rta;
}

function lis_ordcom() {
    $regxPag = 15;
    $pag = si_noexiste('pag-ordcom', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_ordcom();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM orden_compra OC WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT 
            OC.id_ordcom AS ACCIONES,
            C.cliente AS Cliente,
            FORMAT(OC.valor, 0) AS Valor,
            OC.factura AS Factura,
            U1.nombre AS Comercial,
            U2.nombre AS Gestor,
            DATE_FORMAT(OC.fecha_create, '%d/%m/%Y') AS 'Fecha Creación',
            CASE OC.estado
                WHEN 'A' THEN 'Activo'
                WHEN 'C' THEN 'Completado'
                WHEN 'P' THEN 'Pendiente'
                ELSE OC.estado
            END AS Estado
            FROM orden_compra OC
            LEFT JOIN clientes C ON OC.cliente = C.id_cliente
            LEFT JOIN usuarios U1 ON OC.comercial = U1.id_usuario
            LEFT JOIN usuarios U2 ON OC.gestor = U2.id_usuario
            ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "ordcom", $regxPag, "lib.php");
}

function focus_ordcom() {
    return 'ordcom';
}

function men_ordcom() {
    $rta = "";
    $acc = rol('ordcom');
    if (isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn ordcom grabar' onclick=\"grabar('ordcom', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_ordcom() {
    $rta = "";
    $t = [
        'id_ordcom' => '',
        'cliente' => '',
        'valor' => '',
        'factura' => '',
        'comercial' => '',
        'gestor' => '',
        'estado' => 'A'
    ];
    
    $w = 'ordcom';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_ordcom(); 
    if ($d == "") {$d = $t;}
    $o = 'ord';
    
    $c[] = new cmp('id', 'h', 100, $d['id_ordcom'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('cli', 's', 3, $d['cliente'], $w.' '.$o, 'Cliente', 'clientes', '', '', true, true, '', 'col-3');
    $c[] = new cmp('val', 'n', 11, $d['valor'], $w.' '.$o, 'Valor', 'valor', '', '', true, true, '', 'col-2', 'onblur="formatCurrency(this)"');
    $c[] = new cmp('fac', 'n', 11, $d['factura'], $w.' '.$o, 'Factura', 'factura', '', '', true, true, '', 'col-2');
    $c[] = new cmp('com', 's', 3, $d['comercial'], $w.' '.$o, 'Comercial', 'comerciales', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ges', 's', 3, $d['gestor'], $w.' '.$o, 'Gestor', 'gestores', '', '', true, true, '', 'col-3');
    $c[] = new cmp('est', 's', 1, $d['estado'], $w.' '.$o, 'Estado', 'estado_orden', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_ordcom() {
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    if ($id === '0' || empty($id)) {
        return "";
    }
    
    $sql = "SELECT * FROM orden_compra WHERE id_ordcom = ?";
    $info = mysql_prepd($sql, [['type' => 'i', 'value' => $id]]);
    
    if (isset($info['responseResult'][0])) {
        return $info['responseResult'][0];
    }
    return null;
}

function gra_ordcom() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s', strtotime('-5 hours'));
    
    $valor = str_replace(['$', '.', ','], '', $_POST['val']);
    $valor = filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
    
    $commonParams = [
        ['type' => 'i', 'value' => $_POST['cli']],  // cliente
        ['type' => 'i', 'value' => $valor],         // valor
        ['type' => 'i', 'value' => $_POST['fac']],  // factura
        ['type' => 's', 'value' => $_POST['com']],  // comercial
        ['type' => 's', 'value' => $_POST['ges']],  // gestor
        ['type' => 's', 'value' => $_POST['est']]   // estado
    ];
    
    if (empty($id[0])) {
        // INSERT
        $sql = "INSERT INTO orden_compra VALUES (
            NULL,?,?,?,?,?,?,?,?,NULL,NULL,?
        )";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 's', 'value' => $usu],
                ['type' => 'i', 'value' => $fecha],
                ['type' => 's', 'value' => 'A']  // estado por defecto
            ]
        );
    } else {
        // UPDATE
        $sql = "UPDATE orden_compra SET 
            cliente = ?,
            valor = ?,
            factura = ?,
            comercial = ?,
            gestor = ?,
            estado = ?,
            usu_update = ?,
            fecha_update = ?
            WHERE id_ordcom = ?";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 's', 'value' => $usu],
                ['type' => 'i', 'value' => $fecha],
                ['type' => 'i', 'value' => $id[0]]
            ]
        );
    }
    
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8'); 
    echo json_encode($rta);
    exit;
}

function opc_clientes($id='') {
    return opc_sql('SELECT id_cliente, cliente FROM clientes WHERE estado = 1 ORDER BY cliente', $id);
}

function opc_comerciales($id='') {
    return opc_sql('SELECT id_usuario, nombre FROM usuarios WHERE perfil = 3 AND estado = 1 ORDER BY nombre', $id);
}

function opc_gestores($id='') {
    return opc_sql('SELECT id_usuario,nombre FROM usuarios WHERE id_usuario='.$_SESSION['documento'].' AND estado=1 ORDER BY nombre', $id);
}

function opc_estado_orden($id='') {
       return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=10 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    
    if (($a == 'ordcom') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Orden' id='".$c['ACCIONES']."' Onclick=\"mostrar('ordcom','pro',event,'','lib.php',4,'Órdenes de Compra');\"></li>";
        
        // Solo mostrar eliminar si no está completada
        if ($c['Estado'] != 'Completado') {
            $rta .= "<li class='fa-solid fa-trash icon' title='Eliminar Orden' id='".$c['ACCIONES']."' Onclick=\"eliminar('ordcom',this);\"></li>";
        }
        
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'ordcom') {
        switch($c['Estado']) {
            case 'Activo': $rta = 'bg-light-blue'; break;
            case 'Completado': $rta = 'bg-light-green'; break;
            case 'Pendiente': $rta = 'bg-light-yellow'; break;
        }
    }
    return $rta;
}