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

function whe_ordcomp() {
    $filtros = [];
    $params = [];
    $types = '';

    $map = [
        'fcliente'   => ['field' => 'O.cliente',   'type' => 'i'],
        'fcomercial' => ['field' => 'O.comercial', 'type' => 'i'],
        'fgestor'    => ['field' => 'O.gestor',    'type' => 'i'],
        'festado'    => ['field' => 'O.estado',    'type' => 's'],
    ];
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

function lis_ordcomp() {
    $regxPag = 15;
    $pag = si_noexiste('pag-ordcomp', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_ordcomp();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];

    if (empty($where)) $where = '1=1';

    $sqltot = "SELECT COUNT(*) total FROM orden_compra O WHERE $where";
    $total = obtener_total_registros($sqltot, $params, $types);

    $sql = "SELECT 
                O.id_ordcom AS ACCIONES,
                O.id_ordcom AS 'N° Orden',
                O.cliente AS Cliente,
                O.valor AS Valor,
                O.factura AS Factura,
                O.comercial AS Comercial,
                O.gestor AS Gestor,
                O.estado AS Estado,
                DATE_FORMAT(O.fecha_create, '%d/%m/%Y %H:%i') AS 'Fecha Creación'
            FROM orden_compra O
            WHERE $where
            ORDER BY O.fecha_create DESC";

    $datos = obtener_datos_paginados($sql, '', $params, $types, $offset, $regxPag);
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "ordcomp", $regxPag, "lib.php");
}

function cmp_ordcomp() {
    $rta = "";
    $t = ['id_ordcom'=>'','cliente'=>'','valor'=>'','factura'=>'','comercial'=>'','gestor'=>'','estado'=>'A'];
    $w = 'ordcomp';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_ordcomp();
    if ($d == "") {$d = $t;}
    $o = 'oc';
    $c[] = new cmp('id', 'h', 100, $d['id_ordcom'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('cli', 's', 11, $d['cliente'], $w.' '.$o, 'Cliente', 'clientes', '', '', true, true, '', 'col-3');
    $c[] = new cmp('val', 'n', 11, $d['valor'], $w.' '.$o, 'Valor', 'valor', '', '', true, true, '', 'col-2');
    $c[] = new cmp('fac', 'n', 11, $d['factura'], $w.' '.$o, 'Factura', 'factura', '', '', true, true, '', 'col-2');
    $c[] = new cmp('com', 's', 11, $d['comercial'], $w.' '.$o, 'Comercial', 'comerciales', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ges', 's', 11, $d['gestor'], $w.' '.$o, 'Gestor', 'gestores', '', '', true, true, '', 'col-2');
    $c[] = new cmp('est', 's', 1, $d['estado'], $w.' '.$o, 'Estado', 'estados_ordcomp', '', '', true, true, '', 'col-2');

    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_ordcomp() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = intval($_POST['id']);
        $sql = "SELECT * FROM orden_compra WHERE id_ordcom = ?";
        $info = datos_mysql($sql, [$id], "i");
        return $info['responseResult'][0] ?? "";
    }
}

function gra_ordcomp() {
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

function men_ordcomp() {
    return cap_menus('ordcomp', 'pro');
}
function focus_ordcomp() {
    return 'ordcomp';
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
function opc_estados_ordcomp($id='') {
    return opc_sql('SELECT DISTINCT estado, estado FROM orden_compra ORDER BY estado', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'ordcomp') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Orden' id='".$c['ACCIONES']."' Onclick=\"mostrar('ordcomp','pro',event,'','lib.php',4,'Orden de Compra');\"></li>";
        $rta .= "</nav>";
    }
    return $rta;
}
function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'ordcomp' && $c['Estado'] == 'I') {
        $rta = 'bg-light-red';
    }
    return $rta;
}