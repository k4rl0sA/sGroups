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

function whe_customer() {
    $filtros = [];
    if (!empty($_POST['fcliente'])) {
        $filtros[] = ['campo' => 'C.cliente', 'valor' => '%'.$_POST['fcliente'].'%', 'operador' => 'LIKE'];
    }
    if (!empty($_POST['fnit'])) {
        $filtros[] = ['campo' => 'C.nit', 'valor' => '%'.$_POST['fnit'].'%', 'operador' => 'LIKE'];
    }
    if (!empty($_POST['fdep'])) {
        $filtros[] = ['campo' => 'C.departamento', 'valor' => $_POST['fdep'], 'operador' => '='];
    }
    if (!empty($_POST['fciu'])) {
        $filtros[] = ['campo' => 'C.ciudad', 'valor' => $_POST['fciu'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_customer() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-users','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activos','icono'=>'fa-solid fa-user-check','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='1'"],
        ['titulo'=>'Inactivos','icono'=>'fa-solid fa-user-xmark','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='2'"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM clientes C WHERE ";
        $filter = whe_customer();
        
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

function lis_customer() {
    $regxPag = 15;
    $pag = si_noexiste('pag-customer', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_customer();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM clientes C WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT C.`id_cliente` AS ACCIONES, 
            C.nit AS NIT, 
            C.cliente AS Cliente,
            C.direccion AS Dirección,
            C.pagina_web AS 'Página Web',
            C.n_contacto AS Contacto,
            CTLG(1,C.departamento) AS Departamento,
            CTLG(2,C.ciudad) AS Ciudad,
            CTLG(6,C.estado) AS Estado
            FROM clientes C ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "customer", $regxPag, "lib.php");
}

function focus_customer() {
    return 'customer';
}

function men_customer() {
    $rta = cap_menus('customer','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'customer' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_customer() {
    $rta = "";
    $t = [
        'id_cliente' => '', 
        'nit' => '', 
        'cliente' => '',
        'direccion' => '', 
        'pagina_web' => '', 
        'n_contacto' => '', 
        'departamento' => '', 
        'ciudad' => '', 
        'estado' => 'A'
    ];
    
    $w = 'customer';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_customer(); 
    
    if ($d == "") {$d = $t;}
    $o = 'cli';
    
    $c[] = new cmp('id', 'h', 100, $d['id_cliente'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('nit', 'n', 9999999999, $d['nit'], $w.' '.$o, 'NIT', 'nit', '', '', true, true, '', 'col-2');
    $c[] = new cmp('cli', 't', 80, $d['cliente'], $w.' '.$o, 'Nombre Cliente', 'cliente', '', '', true, true, '', 'col-3');
    $c[] = new cmp('dir', 't', 50, $d['direccion'], $w.' '.$o, 'Dirección', 'direccion', '', '', true, true, '', 'col-3');
    $c[] = new cmp('web', 't', 50, $d['pagina_web'], $w.' '.$o, 'Página Web', 'pagina_web', '', '', false, false, '', 'col-2');
    $c[] = new cmp('cont', 't', 10, $d['n_contacto'], $w.' '.$o, 'Teléfono Contacto', 'n_contacto', '', '', true, true, '', 'col-2');
    $c[] = new cmp('dep', 's', 1, $d['departamento'], $w.' '.$o, 'Departamento', 'departamento', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ciu', 's', 1, $d['ciudad'], $w.' '.$o, 'Ciudad', 'ciudad', '', '', true, true, '', 'col-2');
    $c[] = new cmp('est', 's', 2, $d['estado'], $w.' '.$o, 'Estado', 'estado', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_customer() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT * FROM clientes WHERE id_cliente='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_customer() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    
    $commonParams = [
        ['type' => 'i', 'value' => $_POST['nit']],
        ['type' => 's', 'value' => $_POST['cli']],
        ['type' => 's', 'value' => $_POST['dir']],
        ['type' => 's', 'value' => $_POST['web']],
        ['type' => 's', 'value' => $_POST['cont']],
        ['type' => 'i', 'value' => $_POST['dep']],
        ['type' => 'i', 'value' => $_POST['ciu']]
    ];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO clientes VALUES (
            NULL,?,?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?
        )";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 's', 'value' => $_POST['est']]
            ]
        );
    } else {
        $sql = "UPDATE clientes SET 
            nit=?,cliente=?,direccion=?,
            pagina_web=?,n_contacto=?,departamento=?,
            ciudad=?,usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado=? 
            WHERE id_cliente = ?";
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
function opc_departamento($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=1 and estado="A" ORDER BY 1', $id);
}

function opc_ciudad($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=2 and estado="A" ORDER BY 1', $id);
}

function opc_estado($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=6 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'customer') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Cliente' id='".$c['ACCIONES']."' Onclick=\"mostrar('customer','pro',event,'','lib.php',4,'Clientes');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'customer' && $c['Estado'] == 'Inactivo') {
        $rta = 'bg-light-red';
    }
    return $rta;
}