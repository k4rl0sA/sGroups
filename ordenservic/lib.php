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

function whe_serivice_order() {
    $filtros = [];
    if (!empty($_POST['fprovedor'])) {
        $filtros[] = ['campo' => 'P.provedor', 'valor' => '%'.$_POST['fprovedor'].'%', 'operador' => 'LIKE'];
    }
    if (!empty($_POST['fnit'])) {
        $filtros[] = ['campo' => 'P.nit', 'valor' => '%'.$_POST['fnit'].'%', 'operador' => 'LIKE'];
    }
    if (!empty($_POST['fciudad'])) {
        $filtros[] = ['campo' => 'P.ciudad', 'valor' => $_POST['fciudad'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_serivice_order() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-truck','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activos','icono'=>'fa-solid fa-check-circle','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='1'"],
        ['titulo'=>'Inactivos','icono'=>'fa-solid fa-times-circle','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='2'"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM orden_servi O WHERE ";
        $filter = whe_serivice_order();
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

function lis_serivice_order() {
    $regxPag = 15;
    $pag = si_noexiste('pag-serivice_order', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_serivice_order();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM orden_servi O WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT O.id_ordser AS ACCIONES, O.req AS Requerimiento, 
            O.oficina AS Oficina, O.materiales AS Materiales,
            O.activ_reali AS 'Actividades Realizadas', O.observacion AS Observaciones,
            O.tecnico AS Tecnico, O.comercial AS Comercial, O.detall_gestor AS 'Detalle Gestor'
            FROM `orden_servi` O
  ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "serivice_order", $regxPag, "lib.php");
}

function focus_serivice_order() {
    return 'serivice_order';
}

function men_serivice_order() {
    $rta = cap_menus('serivice_order','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'serivice_order' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_serivice_order() {
    $rta = "";
    $t = ['id_ordser' => '','req' => '','empresa' => '','oficina' => '','materiales' => '','actirea' => '','observa' => '','tecnico' => '','detalle' => ''];
    $w = 'serivice_order';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_serivice_order(); 
    if ($d == "") {$d = $t;}
    $o = 'prov';
    $c[] = new cmp('id', 'h', 100, $d['id_ordser'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('req', 't', 100, $d['req'], $w.' '.$o, 'Codigo Requerimiento', 'req', '', '', true, true, '', 'col-3');
    $c[] = new cmp('emp', 't', 10, $d['empresa'], $w.' '.$o, 'Empresa', 'empresa', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ofi', 't', 50, $d['oficina'], $w.' '.$o, 'Oficina', 'oficina', '', '', true, true, '', 'col-3');
    $c[] = new cmp('mat', 't', 3, $d['materiales'], $w.' '.$o, 'Materiales', 'materiales', '', '', true, true, '', 'col-2');
    $c[] = new cmp('act', 't', 12, $d['actirea'], $w.' '.$o, 'Actividades Realizadas', 'actirea', '', '', true, true, '', 'col-2');
    $c[] = new cmp('obs', 't', 10, $d['observa'], $w.' '.$o, 'Observaciones', 'observa', '', '', true, true, '', 'col-2');
    $c[] = new cmp('tec', 't', 10, $d['tecnico'], $w.' '.$o, 'Tecnico', 'tecnico', '', '', true, true, '', 'col-2');
    $c[] = new cmp('det', 't', 50, $d['detalle'], $w.' '.$o, 'Detalles del Gestor', 'detalle', '', '', true, true, '', 'col-3');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_serivice_order() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT * FROM orden_servi WHERE id_ordser='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_serivice_order() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $est = ($_POST['est']=='1') ? 'A' : 'I' ;
    $commonParams = [
        ['type' => 's', 'value' => $_POST['prov']],
        ['type' => 'd', 'value' => $_POST['cred']],
        ['type' => 's', 'value' => $_POST['dir']],
        ['type' => 'i', 'value' => $_POST['ciu']],
        ['type' => 's', 'value' => $_POST['nit']],
        ['type' => 's', 'value' => $_POST['com']],
        ['type' => 's', 'value' => $_POST['cont']],
        ['type' => 's', 'value' => $_POST['email']],
        ['type' => 's', 'value' => $_POST['desc']],
        ['type' => 's', 'value' => $_POST['web']],
        ['type' => 's', 'value' => $_POST['tel']],
        ['type' => 's', 'value' => $_POST['movil']]
    ];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO orden_servi VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?)";
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

// Funciones de opciones
function opc_ciudad($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=2 and estado="A" ORDER BY 1', $id);
}

function opc_estado($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=6 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'serivice_order') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Proveedor' id='".$c['ACCIONES']."' Onclick=\"mostrar('serivice_order','pro',event,'','lib.php',4,'Proveedores');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'serivice_order' && $c['Estado'] == 'Inactivo') {
        $rta = 'bg-light-red';
    }
    return $rta;
}