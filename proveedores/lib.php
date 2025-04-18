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

function whe_provider() {
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

function tot_provider() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-truck','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activos','icono'=>'fa-solid fa-check-circle','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='A'"],
        ['titulo'=>'Inactivos','icono'=>'fa-solid fa-times-circle','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='I'"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM provedores P WHERE ";
        $filter = whe_provider();
        
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

function lis_provider() {
    $regxPag = 15;
    $pag = si_noexiste('pag-provider', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_provider();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM provedores P WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT P.`id_provedor` AS ACCIONES, P.provedor AS Proveedor, 
            P.nit AS NIT, CTLG(2,P.ciudad) AS Ciudad,
            P.n_contacto AS Contacto, P.correo AS Email,
            P.telefono AS Teléfono, CTLG(6,P.estado) AS Estado
            FROM provedores P  ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "provider", $regxPag, "lib.php");
}

function focus_provider() {
    return 'provider';
}

function men_provider() {
    $rta = cap_menus('provider','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'provider' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_provider() {
    $rta = "";
    $t = [
        'id_provedor' => '', 
        'provedor' => '', 
        'credito' => '0',
        'direccion' => '', 
        'ciudad' => '', 
        'nit' => '', 
        'comercial' => '', 
        'n_contacto' => '', 
        'correo' => '', 
        'descripcion' => '', 
        'pagina_web' => '', 
        'telefono' => '', 
        'movil_2' => '', 
        'estado' => 'A'
    ];
    
    $w = 'provider';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_provider(); 
    
    if ($d == "") {$d = $t;}
    $o = 'prov';
    
    $c[] = new cmp('id', 'h', 100, $d['id_provedor'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('prov', 't', 100, $d['provedor'], $w.' '.$o, 'Nombre Proveedor', 'provedor', '', '', true, true, '', 'col-3');
    $c[] = new cmp('cred', 'n', 10, $d['credito'], $w.' '.$o, 'Crédito', 'credito', '', '', true, false, '', 'col-2');
    $c[] = new cmp('dir', 't', 50, $d['direccion'], $w.' '.$o, 'Dirección', 'direccion', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ciu', 's', 3, $d['ciudad'], $w.' '.$o, 'Ciudad', 'ciudad', '', '', true, true, '', 'col-2');
    $c[] = new cmp('nit', 't', 12, $d['nit'], $w.' '.$o, 'NIT', 'nit', '', '', true, true, '', 'col-2');
    $c[] = new cmp('com', 't', 10, $d['comercial'], $w.' '.$o, 'Teléfono Comercial', 'comercial', '', '', true, false, '', 'col-2');
    $c[] = new cmp('cont', 't', 10, $d['n_contacto'], $w.' '.$o, 'Contacto Principal', 'n_contacto', '', '', true, true, '', 'col-2');
    $c[] = new cmp('email', 't', 50, $d['correo'], $w.' '.$o, 'Correo Electrónico', 'correo', '', '', true, true, '', 'col-3');
    $c[] = new cmp('desc', 'a', 3000, $d['descripcion'], $w.' '.$o, 'Descripción', 'descripcion', '', '', false, false, '', 'col-12');
    $c[] = new cmp('web', 't', 50, $d['pagina_web'], $w.' '.$o, 'Página Web', 'pagina_web', '', '', false, false, '', 'col-3');
    $c[] = new cmp('tel', 't', 10, $d['telefono'], $w.' '.$o, 'Teléfono Fijo', 'telefono', '', '', false, false, '', 'col-2');
    $c[] = new cmp('movil', 't', 10, $d['movil_2'], $w.' '.$o, 'Móvil Secundario', 'movil_2', '', '', false, false, '', 'col-2');
    $c[] = new cmp('est', 's', 2, $d['estado'], $w.' '.$o, 'Estado', 'estado', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_provider() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT * FROM provedores WHERE id_provedor='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_provider() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    
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
        $sql = "INSERT INTO provedores VALUES (
            NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?
        )";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 's', 'value' => $_POST['est']]
            ]
        );
    } else {
        $sql = "UPDATE provedores SET 
            provedor=?,credito=?,direccion=?,ciudad=?,nit=?,
            comercial=?,n_contacto=?,correo=?,descripcion=?,
            pagina_web=?,telefono=?,movil_2=?,
            usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado=? 
            WHERE id_provedor = ?";
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
    if (($a == 'provider') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Proveedor' id='".$c['ACCIONES']."' Onclick=\"mostrar('provider','pro',event,'','lib.php',4,'Proveedores');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'provider' && $c['Estado'] == 'Inactivo') {
        $rta = 'bg-light-red';
    }
    return $rta;
}