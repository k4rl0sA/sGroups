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

function whe_client() {
    $filtros = [];
    if (!empty($_POST['fdep'])) {
        $filtros[] = ['campo' => 'C.departamento', 'valor' => limpiar_y_escapar_array(explode(",", $_POST['fdep'])), 'operador' => 'IN'];
    }    
    if (!empty($_POST['fid'])) {
        $filtros[] = ['campo' => 'id_usuario', 'valor' => $_POST['fid'], 'operador' => 'like'];
    }
    return fil_where($filtros);
}

function tot_client() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-users','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activos','icono'=>'fa-solid fa-user-check','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='1'"],
        ['titulo'=>'Inactivos','icono'=>'fa-solid fa-user-xmark','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='2'"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM clientes C WHERE ";
        $filter = whe_client();
        
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

function lis_client() {
    $regxPag = 15;
    $pag = si_noexiste('pag-client', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_client();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM clientes C WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT C.`id_usuario` AS ACCIONES, C.id_usuario AS Documento, nombre, 
            CTLG(1,departamento) AS Departamento, 
            CTLG(2,ciudad) Ciudad, CTLG(3,perfil) Perfil, 
            C.`n_contacto` AS Telefono, CTLG(4,eps) AS EPS, 
            CTLG(5,C.arl) AS ARL, C.correo AS Correo, 
            CTLG(6,estado) Estado
            FROM clientes C WHERE ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    show_sql($sql." WHERE ".$where. " LIMIT ?,?",array_merge($params,[$offset,$regxPag]),$types ."ii");
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "client", $regxPag, "lib.php");
}

function focus_client() {
    return 'client';
}

function men_client() {
    $rta = cap_menus('client','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'client' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_client() {
    $rta = "";
    $t = ['id'=>'', 'id_usuario'=>'', 'nombre'=>'', 'departamento'=>'', 'ciudad'=>'', 
          'perfil'=>'', 'telefono'=>'', 'eps'=>'', 'arl'=>'', 'correo'=>'', 'estado'=>''];
    $w = 'client';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_client(); 
    
    if ($d == "") {$d = $t;}
    $o = 'docder';
    
    $c[] = new cmp('id', 'h', 100, $d['id'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('doc', 'n', 9999999999, $d['id_usuario'], $w.' '.$o, 'Número de Documento', 'doc', '', '', true, true, '', 'col-2');
    $c[] = new cmp('nom', 't', 100, $d['nombre'], $w.' '.$o, 'Nombres Completos', 'nombre', '', '', true, true, '', 'col-2');
    $c[] = new cmp('dep', 's', 3, $d['departamento'], $w.' '.$o, 'Departamento', 'departamento', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ciu', 's', 3, $d['ciudad'], $w.' '.$o, 'Ciudad', 'ciudad', '', '', true, true, '', 'col-2');
    $c[] = new cmp('per', 's', 3, $d['perfil'], $w.' '.$o, 'Perfil', 'perfil', '', '', true, true, '', 'col-2');
    $c[] = new cmp('tel', 'n', 9999999999, $d['telefono'], $w.' '.$o, 'Teléfono', 'telefono', '', '', true, true, '', 'col-2');
    $c[] = new cmp('eps', 's', 3, $d['eps'], $w.' '.$o, 'EPS', 'eps', '', '', true, true, '', 'col-2');
    $c[] = new cmp('arl', 's', 3, $d['arl'], $w.' '.$o, 'ARL', 'arl', '', '', true, true, '', 'col-2');
    $c[] = new cmp('cor', 't', 50, $d['correo'], $w.' '.$o, 'Correo Electrónico', 'correo', '', '', true, true, '', 'col-2');
    $c[] = new cmp('est', 's', 3, $d['estado'], $w.' '.$o, 'Estado', 'estado', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_client() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT id, id_usuario, nombre, departamento, ciudad, perfil, 
                n_contacto telefono, eps, arl, correo, estado 
                FROM clientes WHERE id_usuario='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_client() {
    $id = divide($_POST['id']);
    $commonParams = [
        ['type' => 'i', 'value' => $_POST['doc']],
        ['type' => 's', 'value' => $_POST['nom']],
        ['type' => 'i', 'value' => $_POST['dep']],
        ['type' => 'i', 'value' => $_POST['ciu']],
        ['type' => 'i', 'value' => $_POST['per']],
        ['type' => 'i', 'value' => $_POST['tel']],
        ['type' => 'i', 'value' => $_POST['eps']],
        ['type' => 'i', 'value' => $_POST['arl']],
        ['type' => 's', 'value' => $_POST['cor']]
    ];
    
    $usu = $_SESSION['documento'];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO clientes VALUES (NULL,?,?,?,?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?)";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 'i', 'value' => $_POST['est']]
            ]
        );
    } else {
        $sql = "UPDATE clientes SET id_usuario=?,nombre=?,departamento=?,
                ciudad=?,perfil=?,n_contacto=?,eps=?,arl=?,correo=?,
                usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado=? 
                WHERE id = ?";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 'i', 'value' => $_POST['est']],
                ['type' => 'i', 'value' => $id[0]],
            ]
        );
    }
    
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8'); 
    echo json_encode($rta);
    exit;
}

// Funciones de opciones (se mantienen igual que en el original)
function opc_ciudad($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=2 and estado="A" ORDER BY 1', $id);
}

function opc_departamento($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=1 and estado="A" ORDER BY 1', $id);
}

function opc_perfil($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=3 and estado="A" ORDER BY 1', $id);
}

function opc_eps($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=4 and estado="A" ORDER BY 1', $id);
}

function opc_arl($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=5 and estado="A" ORDER BY 1', $id);
}

function opc_estado($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=6 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'client') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Cliente' id='".$c['ACCIONES']."' Onclick=\"mostrar('client','pro',event,'','lib.php',4,'Clientes');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    return $rta;
}