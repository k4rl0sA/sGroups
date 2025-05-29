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

function whe_office() {
    $filtros = [];
    if (!empty($_POST['foficina'])) {
        $filtros[] = ['campo' => 'O.oficina', 'valor' => '%'.$_POST['foficina'].'%', 'operador' => 'LIKE'];
    }
    if (!empty($_POST['ftipo'])) {
        $filtros[] = ['campo' => 'O.tipo_oficina', 'valor' => $_POST['ftipo'], 'operador' => '='];
    }
    if (!empty($_POST['fdep'])) {
        $filtros[] = ['campo' => 'O.departamento', 'valor' => $_POST['fdep'], 'operador' => '='];
    }
    if (!empty($_POST['fciu'])) {
        $filtros[] = ['campo' => 'O.ciudad', 'valor' => $_POST['fciu'], 'operador' => '='];
    }
    return fil_where($filtros);
}

function tot_office() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-building','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activas','icono'=>'fa-solid fa-check-circle','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='1'"],
        ['titulo'=>'Inactivas','icono'=>'fa-solid fa-times-circle','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='2'"]
    ];
    
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM oficinas O WHERE ";
        $filter = whe_office();
        
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

function lis_office() {
    $regxPag = 15;
    $pag = si_noexiste('pag-office', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_office();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    
    $sqltot = "SELECT COUNT(*) total FROM oficinas O WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT O.`id_oficina` AS ACCIONES, 
            CTLG(9,O.tipo_oficina) AS Tipo, 
            O.oficina AS Oficina,
            O.direccion AS Dirección,
            O.n_contacto AS Contacto,
            CTLG(1,O.departamento) AS Departamento,
            CTLG(2,O.ciudad) AS Ciudad,
            CTLG(6,O.estado) AS Estado
            FROM oficinas O ";
    
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "office", $regxPag, "lib.php");
}

function focus_office() {
    return 'office';
}

function men_office() {
    $rta = cap_menus('office','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'office' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_office() {
    $rta = "";
    $t = ['id_oficina' => '','tipo_oficina' => '','oficina' => '','direccion' => '','n_contacto' => '','departamento' => '','ciudad' => '','estado' => 'A'];
    $w = 'office';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_office(); 
    
    if ($d == "") {$d = $t;}
    $o = 'ofic';
    
    $c[] = new cmp('id', 'h', 100, $d['id_oficina'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('tipo', 's', 10, $d['tipo_oficina'], $w.' '.$o, 'Tipo Oficina', 'tipo_oficina', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ofic', 't', 80, $d['oficina'], $w.' '.$o, 'Nombre Oficina', 'oficina', '', '', true, true, '', 'col-3');
    $c[] = new cmp('dir', 't', 50, $d['direccion'], $w.' '.$o, 'Dirección', 'direccion', '', '', true, true, '', 'col-3');
    $c[] = new cmp('cont', 't', 10, $d['n_contacto'], $w.' '.$o, 'Teléfono Contacto', 'n_contacto', '', '', true, true, '', 'col-2');
    $c[] = new cmp('dep', 's', 1, $d['departamento'], $w.' '.$o, 'Departamento', 'departamento', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ciu', 's', 1, $d['ciudad'], $w.' '.$o, 'Ciudad', 'ciudad', '', '', true, true, '', 'col-2');
    $c[] = new cmp('est', 's', 2, $d['estado'], $w.' '.$o, 'Estado', 'estado', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_office() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT * FROM oficinas WHERE id_oficina='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_office() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    
    $commonParams = [
        ['type' => 'i', 'value' => $_POST['tipo']],
        ['type' => 's', 'value' => $_POST['ofic']],
        ['type' => 's', 'value' => $_POST['dir']],
        ['type' => 's', 'value' => $_POST['cont']],
        ['type' => 'i', 'value' => $_POST['dep']],
        ['type' => 'i', 'value' => $_POST['ciu']]
    ];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO oficinas VALUES (
            NULL,?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?
        )";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 's', 'value' => $_POST['est']]
            ]
        );
    } else {
        $sql = "UPDATE oficinas SET 
            tipo_oficina=?,oficina=?,direccion=?,
            n_contacto=?,departamento=?,ciudad=?,
            usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado=? 
            WHERE id_oficina = ?";
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
function opc_tipo_oficina($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=9 and estado="A" ORDER BY 1', $id);
}

function opc_departamento($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=7 and estado="A" ORDER BY 1', $id);
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
    if (($a == 'office') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Oficina' id='".$c['ACCIONES']."' Onclick=\"mostrar('office','pro',event,'','lib.php',4,'Oficinas');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'office' && $c['Estado'] == 'Inactivo') {
        $rta = 'bg-light-red';
    }
    return $rta;
}