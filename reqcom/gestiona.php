<?php
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

function focus_reqlidser() {
    return 'reqlidser';
}

function men_reqlidser() {
    $rta = "";
    $acc = rol('reqlidser');
    if (isset($acc['crear']) && $acc['crear'] == 'SI') {  
        $rta .= "<button class='frm-btn reqlidser grabar' onclick=\"grabar('reqlidser', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}

function cmp_reqlidser() {
    $rta = "";
    $t = ['id_reqser' => '','idreqcom' => '','tecnicos' => '','fecha_ejecu' => '','activi_realiza' => '','obs_gestor' => '','no_tecnicos' => 1,'no_dias' => 1,'inversion' => '','estado_ejecu' => 'PEN']; 
    $w = 'reqlidser';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_reqlidser();
    $r = get_comreq();
    if ($d == "") $d = $t; 
    $o = 'req';
    $c[] = new cmp('id', 'h', 100, $r['req'].'_'.$d['id_reqser'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('act', 'lb',500 , $r['actividad'] ?? '', $w.' '.$o, 'Actividad', 'actividades', '', '', true, true, '', 'col-3','ActiRequCome();');
    $c[] = new cmp('cot', 'lb', 3, $r['cotizacion']?? '', $w.' '.$o, 'Cotización', 'cotizaciones', '', '', true, false, '', 'col-3');
    $c[] = new cmp('req', 'lb', 3, $r['requerimiento']?? '', $w.' '.$o, 'Requerimiento', 'requerimientos', '', '', true, false, '', 'col-4');
    $c[] = new cmp('emp', 'lb', 3, $r['cod_empresa']?? '', $w.' '.$o, 'Empresa', 'empresas', '', '', true, true, '', 'col-3');
    $c[] = new cmp('con', 'lb', 3, $r['cod_contacto']?? '', $w.' '.$o, 'Contacto', 'contactos', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ofi', 'lb', 3, $r['cod_oficina']?? '', $w.' '.$o, 'Oficina', 'oficinas', '', '', true, true, '', 'col-2');
    $c[] = new cmp('des', 'lb', 500, $r['descripcion']?? '', $w.' '.$o, 'Descripción', 'descripcion', '', '', true, true, '', 'col-2');
    $c[] = new cmp('pen', 'lb', 500, $r['pendientes']?? '', $w.' '.$o, 'Pendientes', 'pendientes', '', '', false, true, '', 'col-12');

    $c[] = new cmp('tec', 'm', 3, $d['tecnicos'], $w, 'Técnicos', 'usuarios', '', '', true, $uPd, '', 'col-6');
    $c[] = new cmp('fec', 'd', 3, $d['fecha_ejecu'], $w, 'Fecha Ejecución', '', '', '', true, $uPd, '', 'col-3');
    $c[] = new cmp('act', 't', 500, $d['activi_realiza'], $w, 'Actividades Realizadas', '', '', '', true, $uPd, '', 'col-12');
    $c[] = new cmp('obs', 't', 500, $d['obs_gestor'], $w, 'Observaciones Gestor', '', '', '', true, $uPd, '', 'col-12');
    $c[] = new cmp('nte', 'n', 2, $d['no_tecnicos'], $w, 'N° Técnicos', '', '', '', true, $uPd, '', 'col-2');
    $c[] = new cmp('ndi', 'n', 2, $d['no_dias'], $w, 'N° Días', '', '', '', true, $uPd, '', 'col-2');
    $c[] = new cmp('inv', 't', 500, $d['inversion'], $w, 'Inversión', '', '', '', true, $uPd, '', 'col-6');
    $c[] = new cmp('est', 's', 3, $d['estado_ejecu'], $w, 'Estado Ejecución', 'estado_ejecucion', '', '', true, $uPd, '', 'col-3');
    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

function get_reqlidser() {
    $id = $_POST['id'];
    if ($id === '0' || empty($id)) return "";
    $sql = "SELECT * FROM req_lidser WHERE id_reqser = ?";
    $params = [['type' => 'i', 'value' => $id]];
    $info = mysql_prepd($sql, $params);
    if (isset($info['responseResult']) && !empty($info['responseResult'])) {
        return $info['responseResult'][0];
    }
    return "";
}

function get_comreq() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT R.id_reqcom req,CTLG(8,R.actividad) 'actividad',R.cotizacion 'cotizacion',R.requerimiento 'requerimiento',C.cliente 'cod_contacto',CO.nombre cod_empresa,
        O.oficina 'cod_oficina',R.descripcion,R.pendientes  
        FROM req_comercial R 
        LEFT JOIN req_asig RA ON R.id_reqcom = RA.idreqcom
        LEFT JOIN clientes C ON R.cod_empresa = C.id_cliente
        LEFT JOIN contactos CO ON R.cod_contacto = CO.id_contacto
        LEFT JOIN oficinas O ON R.cod_oficina = O.id_oficina
        WHERE R.id_reqcom='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];        
    } 
}
function gra_reqlidser() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $fecha = time();
    
    if (empty($id[1])) {
        // Insert
        $sql = "INSERT INTO req_lidser VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        $params = [['type' => 'i', 'value' => $id[0]],
            ['type' => 's', 'value' => $_POST['tec']],
            ['type' => 's', 'value' => $_POST['fec']],
            ['type' => 's', 'value' => $_POST['act']],
            ['type' => 's', 'value' => $_POST['obs']],
            ['type' => 'i', 'value' => $_POST['nte']],
            ['type' => 'i', 'value' => $_POST['ndi']],
            ['type' => 's', 'value' => $_POST['inv']],
            ['type' => 's', 'value' => $_POST['est']],
            ['type' => 's', 'value' => $usu],
            ['type' => 'i', 'value' => $fecha],
            ['type' => 's', 'value' => $usu],
            ['type' => 'i', 'value' => $fecha],
            ['type' => 'i', 'value' => 1]
        ];
    } else {
        // Update
        $sql = "UPDATE req_lidser SET tecnicos = ?,fecha_ejecu = ?,activi_realiza = ?,obs_gestor = ?,no_tecnicos = ?,no_dias = ?,inversion = ?,estado_ejecu = ?,usu_update = ?,fecha_update = ?
            WHERE id_reqser = ?";
        $params = [['type' => 's', 'value' => $_POST['tec']],
            ['type' => 's', 'value' => $_POST['fec']],
            ['type' => 's', 'value' => $_POST['act']],
            ['type' => 's', 'value' => $_POST['obs']],
            ['type' => 'i', 'value' => $_POST['nte']],
            ['type' => 'i', 'value' => $_POST['ndi']],
            ['type' => 's', 'value' => $_POST['inv']],
            ['type' => 's', 'value' => $_POST['est']],
            ['type' => 's', 'value' => $usu],
            ['type' => 'i', 'value' => $fecha],
            ['type' => 'i', 'value' => $id[1]]];
    }
    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8'); 
    echo json_encode($rta);
    exit;
}
function opc_estado_ejecucion($id='') {
    $opciones = [
        ['value' => 'PEN', 'descripcion' => 'Pendiente'],
        ['value' => 'PRO', 'descripcion' => 'En Proceso'],
        ['value' => 'COM', 'descripcion' => 'Completado']
    ];
    
    if ($id === '') {
        return json_encode($opciones);
    } else {
        foreach ($opciones as $opcion) {
            if ($opcion['value'] == $id) {
                return json_encode([$opcion]);
            }
        }
        return json_encode([]);
    }
}

function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'reqlidser') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Gestión' id='".$c['ACCIONES']."' Onclick=\"mostrar('reqlidser','pro',event,'','lib.php',4,'Gestión de Servicios');\"></li>";
        $rta .= "<li class='fa-solid fa-trash icon' title='Eliminar Gestión' id='".$c['ACCIONES']."' Onclick=\"eliminar('reqlidser',this);\"></li>";
        $rta .= "</nav>";
    }    
    return $rta;
}

function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'reqlidser') {
        switch($c['Estado']) {
            case 'Pendiente': $rta = 'bg-light-orange'; break;
            case 'En Proceso': $rta = 'bg-light-blue'; break;
            case 'Completado': $rta = 'bg-light-green'; break;
        }
    }
    return $rta;
}