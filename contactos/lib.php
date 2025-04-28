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
    http_response_code(403); // Prohibido
    exit();
}

// Regenerar el token después de validarlo
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

function whe_contact() {
    $filtros = [];
    if (!empty($_POST['fdep'])) {
        $filtros[] = ['campo' => 'C.departamento', 'valor' => limpiar_y_escapar_array(explode(",", $_POST['fdep'])), 'operador' => 'IN'];
    }    
    if (!empty($_POST['fid'])) {
        $filtros[] = ['campo' => 'id_usuario', 'valor' => $_POST['fid'], 'operador' => 'like'];
    }
    return fil_where($filtros);
}

function tot_contact() {
    $totals = [
    ['titulo'=>'Total','icono'=>'fas fa-users','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
    ['titulo'=>'Activos','icono'=>'fa-brands fa-creative-commons-by','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='1'"],
    ['titulo'=>'Inactivos','icono'=>'fa-solid fa-user-xmark','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='2'"]
    ];
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM contactos C WHERE ";
        $filter = whe_contact();
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

function lis_contact() {
    $regxPag = 15;
    $pag = si_noexiste('pag-contact', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_contact();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];
    $tabla = "contactos";
    
    $sqltot = "SELECT COUNT(*) total FROM `contactos` C WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);
    
    $sql = "SELECT C.`id_contacto` AS ACCIONES, C.nombre AS 'Nombre Contacto', C.`n_contacto` AS 'N° Contacto', C.correo AS Correo, 
            CTLG(6,estado) Estado
            FROM `contactos` C  ";
    
    $where .= " GROUP BY  C.nombre";
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
    
    if ($datos === []) return no_reg();
    return create_table($total, $datos, "contact", $regxPag, "lib.php");
}

function focus_contact() {
    return 'contact';
}

function men_contact() {
    $rta = cap_menus('contact','pro');
    return $rta;
}

function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'contact' && isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_contact() {
    $rta = "";
    $t = ['id_contacto'=>'', 'nombre'=>'', 'telefono'=>'', 'correo'=>'', 'estado'=>''];
    $w = 'contact';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_contact(); 
    
    if ($d == "") {$d = $t;}
    $o = 'docder';
    $c[] = new cmp('id', 'h', 100, $d['id_contacto'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('nom', 't', 100, $d['nombre'], $w.' '.$o, 'Nombres', 'nombre', '', '', true, true, '', 'col-4');
    $c[] = new cmp('tel', 'n', 9999999999, $d['telefono'], $w.' '.$o, 'Telefono', 'telefono', '', '', true, true, '', 'col-2');
    $c[] = new cmp('cor', 't', 50, $d['correo'], $w.' '.$o, 'Correo', 'correo', '', '', true, true, '', 'col-2');
    $c[] = new cmp('est', 's', 3, $d['estado'], $w.' '.$o, 'Estado', 'estado', '', '', true, true, '', 'col-2');
    
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_contact() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT id_contacto, nombre, n_contacto AS telefono , correo, estado FROM contactos WHERE id_contacto='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}

function gra_contact() {
    $id = divide($_POST['id']);
    $commonParams = [
        
        ['type' => 's', 'value' => $_POST['nom']],
        ['type' => 'i', 'value' => $_POST['tel']],
        ['type' => 's', 'value' => $_POST['cor']]
    ];
    
    $usu = $_SESSION['documento'];
    
    if (empty($id[0])) {
        $sql = "INSERT INTO contactos VALUES (NULL,?,?,?,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?)";
        $params = array_merge(
            $commonParams,
            [
                ['type' => 'i', 'value' => $usu],
                ['type' => 'i', 'value' => $_POST['est']]
            ]
        );
    } else {
        $sql = "UPDATE contactos SET nombre=?,n_contacto=?,correo=?,usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado=? WHERE id_contacto = ?";
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

function opc_estado($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=6 and estado="A" ORDER BY 1', $id);
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'contact') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Contacto' id='".$c['ACCIONES']."' Onclick=\"mostrar('contact','pro',event,'','lib.php',4,'Contactos');\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    return $rta;
}