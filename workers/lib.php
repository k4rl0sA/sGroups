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

function whe_repDiar() {
    $filtros = [];
    $perfil = obtenerPerfil($_SESSION['documento']);
    if (!empty($_POST['fcol']) && $perfil == 'ADM') {
        $filtros[] = ['campo' => 'R.usu_create', 'valor' => limpiar_y_escapar_array(explode(",", $_POST['fcol'])), 'operador' => 'IN'];
    } else {
        $filtros[] = ['campo' => 'R.usu_create', 'valor' => limpiar_y_escapar_array([$_SESSION['documento']]), 'operador' => 'IN'];
    }
    if (!empty($_POST['fdes']) && !empty($_POST['fhas'])) {
        $filtros[] = ['campo' => 'fecha_report', 'valor' => $_POST['fdes'], 'operador' => '>='];
        $filtros[] = ['campo' => 'fecha_report', 'valor' => $_POST['fhas'], 'operador' => '<='];
    }
    return fil_where($filtros);
}
function lis_repDiar() {
    $regxPag = 15;
    $pag = si_noexiste('pag-repDiar', 1);
    $offset = ($pag - 1) * $regxPag;$filter = whe_repDiar();$where = $filter['where'];$params = $filter['params'];$types = $filter['types'];
    $tabla = "usuarios";
	$sqltot="SELECT COUNT(*) total  FROM `usuarios` R LEFT JOIN nove_repdiar N ON R.id_rep = N.idrep LEFT JOIN usuarios U ON R.usu_create = U.id_usuario WHERE " . $where;
    $total = obtener_total_registros($sqltot,$params, $types);
    $sql = "SELECT R.`id_rep` AS ACCIONES, R.id_rep AS Cod_Registro, R.`fecha_report` AS Fecha_Reporte, R.`cant_report` AS Registros_Reportados, COUNT(N.idrep) AS Registros_Digitados, U.`nombre` AS Colaborador, R.`fecha_create` AS Fecha_Creacion 
 FROM `usuarios` R LEFT JOIN nove_repdiar N ON R.id_rep = N.idrep LEFT JOIN usuarios U ON R.usu_create = U.id_usuario";
$where.=" GROUP BY R.id_rep, R.fecha_report, R.cant_report, U.nombre, R.fecha_create";
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
	// show_sql($sql." WHERE ".$where. " LIMIT ?,?",array_merge($params,[$offset,$regxPag]),$types ."ii");
     if ($datos === []) return no_reg();
    return create_table($total, $datos, "repDiar", $regxPag, "lib.php");
}
 function focus_repDiar(){
	return 'repDiar';
   }
   function men_repDiar(){
	$rta=cap_menus('repDiar','pro');
	return $rta;
   }
   function cap_menus($a,$b='cap',$con='con') {
	$rta = "";
	// $rta .= "<li class='fa-solid fa-floppy-disk $a grabar'      title='Grabar'          OnClick=\"grabar('$a',this);\"></li>";
	$rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
	/* $rta .="<button class='frm-btn $a actualizar' onclick=\"act_lista('".$a."',this);\"'>Actualizar</button>";
	$rta .= "<li class='icono $a actualizar'  title='Actualizar'      Onclick=\"act_lista('".$a."',this);\"></li>"; */
	return $rta;
  }
  function cmp_repDiar(){
	$rta="";
	$t=['id_rep'=>'','fecha_report'=>'','cant_report'=>''];
	$w='repDiar';
	$uPd = $_REQUEST['id']=='0' ? true : false;
	$d=get_repDiar(); 
	// print_r($d);
	if ($d=="") {$d=$t;}
	$o='docder';
	// var_dump($_POST);
	$c[]=new cmp('id','h',100,$d['id_rep'],$w,'',0,'','','',false,'','col-1');
	$c[]=new cmp('fec','d',10,$d['fecha_report'],$w.' '.$o,'Fecha Reporte','','','',true,$uPd,'','col-2',"validDate(this,-3,0);");
	$c[]=new cmp('can','n',9,$d['cant_report'],$w.' '.$o,'Cantidad Registros','','','',true,true,'','col-2');
	for ($i=0;$i<count($c);$i++) $rta.=$c[$i]->put();
	$rta.="</div>";
	return $rta;
	}
    function get_repDiar(){
		if($_POST['id']=='0'){
			return "";
		}else{
			$id=divide($_POST['id']);
			$sql="SELECT * FROM usuarios WHERE id_rep='".$id[0]."'";
			$info=datos_mysql($sql);
			return $info['responseResult'][0];		
		} 
	}
    function gra_repDiar(){
		$id=divide($_POST['id']);
			if (empty($id[0])) { //verifica si el id no esta vacio para realizar un update o un insert
                $sql = "INSERT INTO usuarios VALUES(NULL,?,?,?,DATE_SUB(NOW(),INTERVAL 5 HOUR),NULL,NULL,'A')";
                $params = [
                    ['type' => 's', 'value' => $_POST['fec']],
                    ['type' => 'i', 'value' => $_POST['can']],
                    ['type' => 'i', 'value' => $_SESSION['documento']]
                ];
                // $types = "sii"; 
			}else{
                $sql = "UPDATE usuarios SET cant_report = ? WHERE id_rep = ?";
                $params = [
                    ['type' => 'i', 'value' => $_POST['can']],
                    ['type' => 'i', 'value' => $id[0]]
                ];
                // $types = "ii"; 
            }
/*             $param_values = array_map(fn($p) => $p['value'], $params);
            $debug_sql = show_sql($sql, $param_values, $types);
            $rta = ['status' => 'success', 'message' => $debug_sql]; */
		$rta = mysql_prepd($sql, $params);
		header('Content-Type: application/json; charset=utf-8'); 
		echo json_encode($rta);
        // $_SESSION['csrf_tkn'] = bin2hex(random_bytes(32));
		exit;
	}
function formato_dato($a,$b,$c,$d){
	$b=strtolower($b);
	$rta=$c[$d];
	if (($a=='repDiar') && ($b=='acciones')){
		   $rta="<nav class='menu right'>";
		   $rta.="<li class='fa-solid fa-pen-to-square icon' title='Editar Reporte Diario' id='".$c['ACCIONES']."' Onclick=\"mostrar('repDiar','pro',event,'','lib.php',4);\"></li>";
           $rta.="<li class='fa-solid fa-triangle-exclamation icon' title='Hallazgos' id='".$c['ACCIONES']."' Onclick=\"mostrar('hallaz','pro',event,'','hallazgos.php',4,'Hallazgos');\"></li>";
		   $rta.="</nav>";
	   }    
	return $rta;
   }
function bgcolor($a,$c,$f='c'){
	$rta="";
	//~ if ($a=='transacciones'&&$c['ESTADO']=='A') $rta='green';
	return $rta;
   }
   ?>