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

function whe_reqasig() {
    $filtros = [];
    if (!empty($_POST['freq'])) {
        $filtros[] = ['campo' => 'RA.idreqcom', 'valor' => $_POST['freq'], 'operador' => '='];
    }
    if (!empty($_POST['fasignado'])) {
        $filtros[] = ['campo' => 'RA.asignado', 'valor' => $_POST['fasignado'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_reqasig() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-file-invoice','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activas','icono'=>'fas fa-spinner','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado=1"],
        ['titulo'=>'Completadas','icono'=>'fas fa-check-circle','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado=2"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM req_asig RA WHERE ";
        $filter = whe_reqasig();
        
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

function lis_reqasig() {
    $regxPag = 15;
    $pag = si_noexiste('pag-reqasig', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_reqasig();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    $sqltot = "SELECT COUNT(*) total FROM req_asig RA WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    $sql = "SELECT 
            RA.id_reqseg AS ACCIONES, 
            CONCAT('REQ-', RA.idreqcom) AS Requerimiento,
            CTLG(1, RC.cod_empresa) AS Empresa,
            C.nombre AS Contacto,
            U.nombre AS Asignado,
            DATE_FORMAT(FROM_UNIXTIME(RA.fecha_create), '%d/%m/%Y') AS 'Fecha Asignación',
            IF(RA.estado=1, 'Activo', 'Completado') AS Estado
            FROM req_asig RA
            LEFT JOIN req_comercial RC ON RA.idreqcom = RC.id_reqcom
            LEFT JOIN contactos C ON RC.cod_contacto = C.id_contacto
            LEFT JOIN usuarios U ON RA.asignado = U.id_usuario
            ";
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "reqasig", $regxPag, "lib.php");
}

function focus_reqasig() {
    return 'reqasig';
}

function men_reqasig() {
    $rta = "";
    $acc = rol('reqasig');
    if (isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn reqasig grabar' onclick=\"grabar('reqasig', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_reqasig() {
    $rta = "";
    $t = ['id_reqseg' => '', 'idreqcom' => '', 'asignado' => ''];
    $w = 'reqasig';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_reqasig(); 
    if ($d == "") {$d = $t;}
    $o = 'req';
    $c[] = new cmp('id', 'h', 100, $d['id_reqseg'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('ofi', 'lb', 0, $d['oficina'] ?? '', 'reqasig', 'Oficina', '', '', '', false, false, '', 'col-6');
    $c[] = new cmp('act', 'lb',500 , $d['actividad'] ?? '', $w.' '.$o, 'Actividad', 'actividades', '', '', true, true, '', 'col-3','ActiRequCome();');
    $c[] = new cmp('cot', 'lb', 3, $d['cotizacion']?? '', $w.' '.$v1.' '.$o, 'Cotización', 'cotizaciones', '', '', true, false, '', 'col-3');
    $c[] = new cmp('req', 'lb', 3, $d['requerimiento']?? '', $w.' '.$v1.' '.$o, 'Requerimiento', 'requerimientos', '', '', true, false, '', 'col-4');
    $c[] = new cmp('emp', 'lb', 3, $d['cod_empresa']?? '', $w.' '.$o, 'Empresa', 'empresas', '', '', true, true, '', 'col-3');
    $c[] = new cmp('con', 'lb', 3, $d['cod_contacto']?? '', $w.' '.$o, 'Contacto', 'contactos', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ofi', 'lb', 3, $d['cod_oficina']?? '', $w.' '.$o, 'Oficina', 'oficinas', '', '', true, true, '', 'col-2');
    $c[] = new cmp('des', 'lb', 500, $d['descripcion']?? 'SE MUESTRA TODO EL TEXTO QUE YO INGRESE AQUI TAN LARGO COMO SEA', $w.' '.$o, 'Descripción', 'descripcion', '', '', true, true, '', 'col-2');
    $c[] = new cmp('pen', 'lb', 500, $d['pendientes']?? '', $w.' '.$o, 'Pendientes', 'pendientes', '', '', false, true, '', 'col-12');

    $c[] = new cmp('req', 's', 3, $d['idreqcom'], $w.' '.$o, 'Requerimiento', 'requerimientos', '', '', true, true, '', 'col-4');
    $c[] = new cmp('per', 's', 3,'', $w.' '.$o, 'Perfil', 'perfil', '', '', true, true, '', 'col-4');
    $c[] = new cmp('asi', 's', 3, $d['asignado'], $w.' '.$o, 'Asignado a', 'usuarios', '', '', true, true, '', 'col-4');
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_reqasig() {
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    if ($id === '0' || empty($id))    return "";
    $info = mysql_prepd("SELECT * FROM req_asig WHERE id_reqseg = ?",[['type' => 'i', 'value' => $id]]);
    if (isset($info['responseResult'][0])) {
        return $info['responseResult'][0];
    }
    return null;
}

function gra_reqasig() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $fecha = time();
    
    $commonParams = [
        ['type' => 'i', 'value' => $_POST['req']],
        ['type' => 's', 'value' => $_POST['asi']]
    ];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO req_asig VALUES (
            NULL,?,?,?,?,?,?,?
        )";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 's', 'value' => $usu],
                ['type' => 'i', 'value' => $fecha],
                ['type' => 's', 'value' => $usu],
                ['type' => 'i', 'value' => $fecha],
                ['type' => 'i', 'value' => 1]
            ]
        );
    } else {
        $sql = "UPDATE req_asig SET 
            idreqcom=?,asignado=?,
            usu_update=?,fecha_update=?
            WHERE id_reqseg = ?";
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

function opc_requerimientos($id='') {
    return opc_sql('SELECT id_reqcom, CONCAT("REQ-", id_reqcom) as descripcion FROM req_comercial WHERE estado = 1 ORDER BY id_reqcom', $id);
}

function opc_usuarios($id='') {
    return opc_sql('SELECT id_usuario, nombre FROM usuarios WHERE estado = 1 ORDER BY nombre', $id);
}

function opc_perfil($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=3 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'reqasig') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Asignación' id='".$c['ACCIONES']."' Onclick=\"mostrar('reqasig','pro',event,'','lib.php',4,'Asignaciones');\"></li>";
        $rta .= "<li class='fa-solid fa-trash icon' title='Eliminar Asignación' id='".$c['ACCIONES']."' Onclick=\"eliminar('reqasig',this);\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'reqasig') {
        switch($c['Estado']) {
            case 'Activo': $rta = 'bg-light-blue'; break;
            case 'Completado': $rta = 'bg-light-green'; break;
        }
    }
    return $rta;
}