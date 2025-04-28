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

function whe_leadserv() {
    $filtros = [];
    if (!empty($_POST['fempresa'])) {
        $filtros[] = ['campo' => 'R.cod_empresa', 'valor' => $_POST['fempresa'], 'operador' => '='];
    }
    if (!empty($_POST['fcontacto'])) {
        $filtros[] = ['campo' => 'R.cod_contacto', 'valor' => $_POST['fcontacto'], 'operador' => '='];
    }
    if (!empty($_POST['festado'])) {
        $filtros[] = ['campo' => 'R.estado_req', 'valor' => $_POST['festado'], 'operador' => '='];
    }
    if (!empty($_POST['factividad'])) {
        $filtros[] = ['campo' => 'R.actividad', 'valor' => $_POST['factividad'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_leadserv() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-tasks','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Pendientes','icono'=>'fas fa-clock','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado_req=1"],
        ['titulo'=>'En Proceso','icono'=>'fas fa-spinner','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado_req=2"],
        ['titulo'=>'Completados','icono'=>'fas fa-check-circle','indicador'=>'fa fa-level-down arrow-icon','condicion'=>" AND estado_req=3"],
        ['titulo'=>'Cancelados','icono'=>'fas fa-times-circle','indicador'=>'fa fa-level-down arrow-icon','condicion'=>" AND estado_req=4"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM req_lidser R WHERE ";
        $filter = whe_leadserv();
        
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

function lis_leadserv() {
    $regxPag = 15;
    $pag = si_noexiste('pag-leadserv', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_leadserv();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM req_lidser R WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT R.`id_reqcom` AS ACCIONES, 
            CTLG(16,R.actividad) AS Actividad,
            CTLG(17,R.cotizacion) AS 'Tipo Cotización',
            CTLG(18,R.requerimiento) AS 'Tipo Requerimiento',
            E.nombre AS Empresa,
            CONCAT(C.nombre, ' (', C.n_contacto, ')') AS Contacto,
            O.oficina AS Oficina,
            SUBSTRING(R.descripcion, 1, 50) AS Descripción,
            CTLG(15,R.estado_req) AS Estado,
            DATE_FORMAT(FROM_UNIXTIME(R.fecha_create), '%d/%m/%Y %H:%i') AS 'Fecha Creación'
            FROM req_lidser R
            LEFT JOIN empresas E ON R.cod_empresa = E.cod_empresa
            LEFT JOIN contactos C ON R.cod_contacto = C.cod_contacto
            LEFT JOIN oficinas O ON R.cod_oficina = O.cod_oficina
            WHERE ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "leadserv", $regxPag, "lib.php");
}

function focus_leadserv() {
    return 'leadserv';
}

function men_leadserv() {
    $rta = cap_menus('leadserv','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'leadserv' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_leadserv() {
    $rta = "";
    $t = [
        'id_reqcom' => '', 
        'actividad' => '', 
        'cotizacion' => '',
        'requerimiento' => '', 
        'cod_empresa' => '', 
        'cod_contacto' => '', 
        'cod_oficina' => '', 
        'descripcion' => '', 
        'pendienets' => '', 
        'estado_req' => '1'
    ];
    
    $w = 'leadserv';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_leadserv(); 
    
    if ($d == "") {$d = $t;}
    $o = 'ls';
    
    $c[] = new cmp('id', 'h', 100, $d['id_reqcom'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('act', 's', 3, $d['actividad'], $w.' '.$o, 'Tipo Actividad', 'actividad', '', '', true, true, '', 'col-2');
    $c[] = new cmp('cot', 's', 3, $d['cotizacion'], $w.' '.$o, 'Tipo Cotización', 'cotizacion', '', '', true, true, '', 'col-2');
    $c[] = new cmp('req', 's', 3, $d['requerimiento'], $w.' '.$o, 'Tipo Requerimiento', 'requerimiento', '', '', true, true, '', 'col-2');
    $c[] = new cmp('emp', 's', 3, $d['cod_empresa'], $w.' '.$o, 'Empresa', 'cod_empresa', '', '', true, true, '', 'col-3');
    $c[] = new cmp('con', 's', 3, $d['cod_contacto'], $w.' '.$o, 'Contacto', 'cod_contacto', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ofi', 's', 3, $d['cod_oficina'], $w.' '.$o, 'Oficina', 'cod_oficina', '', '', true, true, '', 'col-2');
    $c[] = new cmp('des', 'a', 500, $d['descripcion'], $w.' '.$o, 'Descripción del Servicio', 'descripcion', '', '', true, true, '', 'col-12');
    $c[] = new cmp('pen', 'a', 500, $d['pendienets'], $w.' '.$o, 'Pendientes/Notas', 'pendienets', '', '', false, false, '', 'col-12');
    $c[] = new cmp('est', 's', 3, $d['estado_req'], $w.' '.$o, 'Estado', 'estado_req', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_leadserv() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT * FROM req_lidser WHERE id_reqcom='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_leadserv() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $fecha = time(); // Usamos timestamp UNIX
    
    $commonParams = [
        ['type' => 's', 'value' => $_POST['act']],
        ['type' => 's', 'value' => $_POST['cot']],
        ['type' => 's', 'value' => $_POST['req']],
        ['type' => 'i', 'value' => $_POST['emp']],
        ['type' => 'i', 'value' => $_POST['con']],
        ['type' => 'i', 'value' => $_POST['ofi']],
        ['type' => 's', 'value' => $_POST['des']],
        ['type' => 's', 'value' => $_POST['pen']],
        ['type' => 'i', 'value' => $_POST['est']]
    ];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO req_lidser VALUES (
            NULL,?,?,?,?,?,?,?,?,?,?,?,NULL,NULL,?
        )";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 's', 'value' => $usu],
                ['type' => 'i', 'value' => $fecha],
                ['type' => 'i', 'value' => 1] // Estado activo
            ]
        );
    } else {
        $sql = "UPDATE req_lidser SET 
            actividad=?,cotizacion=?,requerimiento=?,
            cod_empresa=?,cod_contacto=?,cod_oficina=?,
            descripcion=?,pendienets=?,estado_req=?,
            usu_update=?,fecha_update=?
            WHERE id_reqcom = ?";
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

// Funciones de opciones para catálogos
function opc_actividades_lidser($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=16 and estado="A" ORDER BY 1', $id);
}

function opc_cotizaciones_lidser($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=17 and estado="A" ORDER BY 1', $id);
}

function opc_requerimientos_lidser($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=18 and estado="A" ORDER BY 1', $id);
}

function opc_empresas($id='') {
    return opc_sql('SELECT cod_empresa,nombre FROM empresas WHERE estado=1 ORDER BY nombre', $id);
}

function opc_contactos($id='') {
    return opc_sql('SELECT cod_contacto,nombre FROM contactos WHERE estado=1 ORDER BY nombre', $id);
}

function opc_oficinas($id='') {
    return opc_sql('SELECT cod_oficina,oficina FROM oficinas WHERE estado="A" ORDER BY oficina', $id);
}

function opc_estados_lidser($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=15 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'leadserv') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Requerimiento' id='".$c['ACCIONES']."' Onclick=\"mostrar('leadserv','pro',event,'','lib.php',4,'Requerimientos');\"></li>";
        $rta .= "<li class='fas fa-history icon' title='Historial' id='".$c['ACCIONES']."' Onclick=\"mostrar('historial','pro',event,'','historial.php',4,'Historial');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'leadserv') {
        switch($c['Estado']) {
            case 'Pendiente': $rta = 'bg-light-yellow'; break;
            case 'En Proceso': $rta = 'bg-light-blue'; break;
            case 'Completado': $rta = 'bg-light-green'; break;
            case 'Cancelado': $rta = 'bg-light-red'; break;
        }
    }
    return $rta;
}