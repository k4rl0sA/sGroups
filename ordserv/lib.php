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

// FILTROS
function whe_ordser() {
    $filtros = [];
    if (!empty($_POST['fempresa'])) {
        $filtros[] = ['campo' => 'O.empresa', 'valor' => $_POST['fempresa'], 'operador' => '='];
    }
    if (!empty($_POST['foficina'])) {
        $filtros[] = ['campo' => 'O.oficina', 'valor' => $_POST['foficina'], 'operador' => '='];
    }
    if (!empty($_POST['festado'])) {
        $filtros[] = ['campo' => 'O.estado', 'valor' => $_POST['festado'], 'operador' => '='];
    }
    if (!empty($_POST['ftecnico'])) {
        $filtros[] = ['campo' => 'O.tecnico', 'valor' => $_POST['ftecnico'], 'operador' => '='];
    }
    if (!empty($_POST['freq'])) {
        $filtros[] = ['campo' => 'O.req', 'valor' => $_POST['freq'], 'operador' => '='];
    }
    if (!empty($_POST['fcomercial'])) {
        $filtros[] = ['campo' => 'O.comercial', 'valor' => $_POST['fcomercial'], 'operador' => '='];
    }
    return fil_where($filtros);
}

// TOTALES
function tot_ordser() {
    $totals = [
        ['titulo'=>'Total','icono'=>'fas fa-tasks','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
        ['titulo'=>'Activas','icono'=>'fas fa-check','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='A'"],
        ['titulo'=>'Cerradas','icono'=>'fas fa-times','indicador'=>'fa fa-level-down arrow-icon','condicion'=>" AND estado='C'"]
    ];
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM orden_servi O WHERE ";
        $filter = whe_ordser();
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

// LISTADO
function lis_ordser() {
    $regxPag = 15;
    $pag = si_noexiste('pag-ordser', 1);
    $offset = ($pag - 1) * $regxPag;
    $filter = whe_ordser();
    $where = $filter['where'];
    $params = $filter['params'];
    $types = $filter['types'];

    $sqltot = "SELECT COUNT(*) total FROM orden_servi O WHERE " . $where;
    $total = obtener_total_registros($sqltot, $params, $types);

    $sql = "SELECT O.id_ordser AS ACCIONES,
            O.req AS Requerimiento,
            CTLG(1,O.empresa) AS Empresa,
            CTLG(2,O.oficina) AS Oficina,
            O.materiales AS Materiales,
            O.activ_reali AS 'Actividades Realizadas',
            O.observacion AS Observación,
            O.tecnico AS Técnico,
            O.comercial AS Comercial,
            O.gestor AS Gestor,
            O.detalle_gestor AS 'Detalle Gestor',
            O.estado AS Estado,
            DATE_FORMAT(O.fecha_create, '%d/%m/%Y %H:%i') AS 'Fecha Creación'
            FROM orden_servi O
            ";
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);

    if ($datos === []) return no_reg();
    return create_table($total, $datos, "ordser", $regxPag, "lib.php");
}

// FORMULARIO
function cmp_ordser() {
    $rta = "";
    $t = ['id_ordser'=>'','req'=>'','empresa'=>'','oficina'=>'','materiales'=>'','activ_reali'=>'','observacion'=>'','tecnico'=>'','comercial'=>'','gestor'=>'','detalle_gestor'=>'','estado'=>'A'];
    $w = 'ordser';
    $uPd = $_REQUEST['id'] == '0' ? true : false;
    $d = get_ordser();
    if ($d == "") {$d = $t;}
    $o = 'os';
    $c[] = new cmp('id', 'h', 100, $d['id_ordser'], $w, '', 0, '', '', '', false, '', 'col-1');
    $c[] = new cmp('req', 'n', 9999999999, $d['req'], $w.' '.$o, 'Requerimiento', 'req', '', '', true, true, '', 'col-2');
    $c[] = new cmp('emp', 's', 3, $d['empresa'], $w.' '.$o, 'Empresa', 'empresas', '', '', true, true, '', 'col-3');
    $c[] = new cmp('ofi', 's', 3, $d['oficina'], $w.' '.$o, 'Oficina', 'oficinas', '', '', true, true, '', 'col-3');
    $c[] = new cmp('mat', 'a', 500, $d['materiales'], $w.' '.$o, 'Materiales', 'materiales', '', '', true, true, '', 'col-12');
    $c[] = new cmp('act', 'a', 500, $d['activ_reali'], $w.' '.$o, 'Actividades Realizadas', 'activ_reali', '', '', true, true, '', 'col-12');
    $c[] = new cmp('obs', 'a', 500, $d['observacion'], $w.' '.$o, 'Observación', 'observacion', '', '', true, true, '', 'col-12');
    $c[] = new cmp('tec', 's', 3, $d['tecnico'], $w.' '.$o, 'Técnico', 'tecnicos', '', '', true, true, '', 'col-2');
    $c[] = new cmp('com', 's', 3, $d['comercial'], $w.' '.$o, 'Comercial', 'comerciales', '', '', true, true, '', 'col-2');
    $c[] = new cmp('ges', 's', 3, $d['gestor'], $w.' '.$o, 'Gestor', 'gestores', '', '', true, true, '', 'col-2');
    $c[] = new cmp('detges', 'a', 500, $d['detalle_gestor'], $w.' '.$o, 'Detalle Gestor', 'detalle_gestor', '', '', true, true, '', 'col-12');
    $c[] = new cmp('est', 's', 2, $d['estado'], $w.' '.$o, 'Estado', 'estados_ordser', '', '', true, true, '', 'col-2');

    for ($i = 0; $i < count($c); $i++) $rta .= $c[$i]->put();
    $rta .= "</div>";
    return $rta;
}

// OBTENER REGISTRO
function get_ordser() {
    if ($_POST['id'] == '0') {
        return "";
    } else {
        $id = divide($_POST['id']);
        $sql = "SELECT * FROM orden_servi WHERE id_ordser='".$id[0]."'";
        $info = datos_mysql($sql);
        return $info['responseResult'][0];
    }
}

// GUARDAR/ACTUALIZAR
function gra_ordser() {
    $id = divide($_POST['id']);
    $usu = $_SESSION['documento'];
    $fecha = date('Y-m-d H:i:s');

    $commonParams = [
        ['type' => 'i', 'value' => $_POST['req']],
        ['type' => 'i', 'value' => $_POST['emp']],
        ['type' => 'i', 'value' => $_POST['ofi']],
        ['type' => 's', 'value' => $_POST['mat']],
        ['type' => 's', 'value' => $_POST['act']],
        ['type' => 's', 'value' => $_POST['obs']],
        ['type' => 's', 'value' => $_POST['tec']],
        ['type' => 's', 'value' => $_POST['com']],
        ['type' => 's', 'value' => $_POST['ges']],
        ['type' => 's', 'value' => $_POST['detges']],
        ['type' => 'i', 'value' => $usu],
        ['type' => 's', 'value' => $fecha],
        ['type' => 's', 'value' => $_POST['est']]
    ];

    if (empty($id[0])) {
        $sql = "INSERT INTO orden_servi 
            (req,empresa,oficina,materiales,activ_reali,observacion,tecnico,comercial,gestor,detalle_gestor,usu_create,fecha_create,estado)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = $commonParams;
    } else {
        $sql = "UPDATE orden_servi SET 
            req=?,empresa=?,oficina=?,materiales=?,activ_reali=?,observacion=?,tecnico=?,comercial=?,gestor=?,detalle_gestor=?,usu_update=?,fecha_update=?,estado=?
            WHERE id_ordser = ?";
        $params = array_merge($commonParams, [['type' => 'i', 'value' => $id[0]]]);
    }

    $rta = mysql_prepd($sql, $params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rta);
    exit;
}

// OPCIONES PARA SELECTS
function opc_empresas($id='') {
    return opc_sql('SELECT nit, cliente FROM clientes WHERE estado=1 ORDER BY 2', $id);
}
function opc_oficinas($id='') {
    return opc_sql('SELECT id_oficina,oficina FROM oficinas WHERE estado=1 ORDER BY oficina', $id);
}
function opc_tecnicos($id='') {
    return opc_sql('SELECT id_usuario,nombre FROM usuarios WHERE perfil=14 AND estado=1 ORDER BY nombre', $id);
}
function opc_comerciales($id='') {
    return opc_sql('SELECT id_usuario,nombre FROM usuarios WHERE perfil=3 AND estado=1 ORDER BY nombre', $id);
}
function opc_gestores($id='') {
    return opc_sql('SELECT id_usuario,nombre FROM usuarios WHERE perfil=15 AND estado=1 ORDER BY nombre', $id);
}
function opc_estados_ordser($id='') {
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=10 and estado="A" ORDER BY 1', $id);
}

// FORMATO DE DATOS PARA TABLA
function formato_dato($a, $b, $c, $d) {
    $b = strtolower($b);
    $rta = $c[$d];
    if (($a == 'ordser') && ($b == 'acciones')) {
        $rta = "<nav class='menu right'>";
        $rta .= "<li class='fa-solid fa-pen-to-square icon' title='Editar Orden' id='".$c['ACCIONES']."' Onclick=\"mostrar('ordser','pro',event,'','lib.php',4,'Orden de Servicio');\"></li>";
        $rta .= "</nav>";
    }
    return $rta;
}

// COLORES DE FILA
function bgcolor($a, $c, $f='c') {
    $rta = "";
    if ($a == 'ordser') {
        switch($c['Estado']) {
            case 'A': $rta = 'bg-light-blue'; break;
            case 'C': $rta = 'bg-light-green'; break;
        }
    }
    return $rta;
}

// MENÚS Y FOCUS
function focus_ordser() {
    return 'ordser';
}
function men_ordser() {
    $rta = cap_menus('ordser','pro');
    return $rta;
}
function cap_menus($a, $b='cap', $con='con') {
    $rta = "";
    $acc = rol($a);
    if ($a == 'ordser' && isset($acc['crear']) && $acc['crear'] == 'SI') {
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
    return $rta;
}
