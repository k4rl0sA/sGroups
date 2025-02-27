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

function whe_catalogo() {
    $filtros = [];
    if (!empty($_POST['fidcata'])) {
        $filtros[] = ['campo' => 'C.departamento', 'valor' => limpiar_y_escapar_array(explode(",", $_POST['fidcata'])), 'operador' => 'IN'];
    }    
    if (!empty($_POST['fcatalogo'])) {
        $filtros[] = ['campo' => 'C.descripcion', 'valor' => $_POST['fcatalogo'], 'operador' => 'like'];
    }
    if (!empty($_POST['festado'])) {
        $filtros[] = ['campo' => 'C.estado','valor' => limpiar_y_escapar_array(explode(",", $_POST['festado'])), 'operador' => 'IN'];
    }
    return fil_where($filtros);
}
function tot_catalogo() {
    $totals = [
    ['titulo'=>'Total','icono'=>'fas fa-users','indicador'=>'fa fa-level-up arrow-icon','condicion' => ''],
    ['titulo'=>'Activos','icono'=>'fa-brands fa-creative-commons-by','indicador'=>'fa fa-level-up arrow-icon','condicion'=>" AND estado='A'"],
    ['titulo'=>'Inactivos','icono'=>'fa-solid fa-user-xmark','indicador'=>'fa fa-level-down arrow-icon','condicion' =>" AND estado='I'"]
    ];
    $rta = '';
    foreach ($totals as $total) {
        $sql = "SELECT count(*) AS Total FROM catadeta C WHERE ";
        $filter = whe_catalogo();
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

function lis_catalogo() {
    $regxPag = 15;
    $pag = si_noexiste('pag-catalogo', 1);
    $offset = ($pag - 1) * $regxPag;$filter = whe_catalogo();
    $where = $filter['where'];$params = $filter['params'];$types = $filter['types'];
	$sqltot="SELECT COUNT(*) total  FROM `catadeta` C WHERE " . $where;
    $total = obtener_total_registros($sqltot,$params, $types);
    $sql = "SELECT C.idcatalogo ACCIONES,C.idcatadeta ID,C.descripcion,C.estado,C.valor,
    CTLG(6,estado) Estado
 FROM `catadeta` C ";
$where.=" ORDER BY 1,2";
    $datos = obtener_datos_paginados($sql, $where, $params, $types, $offset, $regxPag);
	// show_sql($sql." WHERE ".$where. " LIMIT ?,?",array_merge($params,[$offset,$regxPag]),$types ."ii");
     if ($datos === []) return no_reg();
    return create_table($total, $datos, "catalogo", $regxPag, "lib.php");
}
 function focus_catalogo(){
	return 'catalogo';
   }
   function men_catalogo(){
	$rta=cap_menus('catalogo','pro');
	return $rta;
   }
   function cap_menus($a,$b='cap',$con='con') {
	$rta = "";
    $acc=rol($a);
    if ($a=='catalogo' && isset($acc['crear']) && $acc['crear']=='SI') {  
        $rta .= "<button class='frm-btn $a grabar' onclick=\"grabar('$a', this);\"><span class='frm-txt'>Grabar</span><i class='fa-solid fa-floppy-disk icon'></i></button>";
    }
	return $rta;
  }
  function cmp_catalogo(){
	$rta="";
	$t=['id'=>'','id_usuario'=>'','nombre'=>'','departamento'=>'','ciudad'=>'','perfil'=>'','telefono'=>'','eps'=>'','arl'=>'','correo'=>'','estado'=>''];
	$w='catalogo';
	$uPd = $_REQUEST['id']=='0' ? true : false;
	$d=get_catalogo(); 
	// print_r($d);
	if ($d=="") {$d=$t;}
	$o='docder';
	// var_dump($_POST);
	$c[]=new cmp('id','h',100,$d['id'],$w,'',0,'','','',false,'','col-1');
    $c[]=new cmp('doc','n',9999999999,$d['id_usuario'],$w.' '.$o,'Numero de Documento','doc','','',true,true,'','col-2');
    $c[]=new cmp('nom','t',100,$d['nombre'],$w.' '.$o,'Nombres','nombre','','',true,true,'','col-2');
    $c[]=new cmp('dep','s',3,$d['departamento'],$w.' '.$o,'Departamento','departamento','','',true,true,'','col-2');
    $c[]=new cmp('ciu','s',3,$d['ciudad'],$w.' '.$o,'Ciudad','ciudad','','',true,true,'','col-2');
    $c[]=new cmp('per','s',3,$d['perfil'],$w.' '.$o,'Perfil','perfil','','',true,true,'','col-2');
    $c[]=new cmp('tel','n',9999999999,$d['telefono'],$w.' '.$o,'Telefono','telefono','','',true,true,'','col-2');
	$c[]=new cmp('eps','s',3,$d['eps'],$w.' '.$o,'EPS','eps','','',true,true,'','col-2',"validDate(this,-3,0);");
	$c[]=new cmp('arl','s',3,$d['arl'],$w.' '.$o,'ARL','arl','','',true,true,'','col-2');
    $c[]=new cmp('cor','t',50,$d['correo'],$w.' '.$o,'Correo','correo','','',true,true,'','col-2');
    $c[]=new cmp('est','s',3,$d['estado'],$w.' '.$o,'Estado','estado','','',true,true,'','col-2');
	for ($i=0;$i<count($c);$i++) $rta.=$c[$i]->put();
	$rta.="</div>";
	return $rta;
	}
    function get_catalogo(){
		if($_POST['id']=='0'){
			return "";
		}else{
			$id=divide($_POST['id']);
			$sql="SELECT id,id_usuario,nombre,departamento,ciudad,perfil,n_contacto telefono,eps,arl,correo,estado  FROM usuarios WHERE id_usuario='".$id[0]."'";
			$info=datos_mysql($sql);
			return $info['responseResult'][0];		
		} 
	}
    function gra_catalogo(){
		$id=divide($_POST['id']);
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
        $usu=$_SESSION['documento'];
			if (empty($id[0])) {
                
                $sql = "INSERT INTO usuarios VALUES (NULL,?,?,?,?,?,?,?,?,?,null,null,null,?,DATE_SUB(NOW(), INTERVAL 5 HOUR),NULL,NULL,?)";
                $params = array_merge(
                    $commonParams,
                    [
                        ['type' => 'i', 'value' => $usu],
                        ['type' => 'i', 'value' => $_POST['est']]
                    ]
                );
			}else{
                $sql = "UPDATE usuarios SET id_usuario=?,nombre=?,departamento=?,ciudad=?,perfil=?,n_contacto=?,eps=?,arl=?,correo=?,usu_update=?,fecha_update=DATE_SUB(NOW(), INTERVAL 5 HOUR),estado =? WHERE id = ?";
                $params = array_merge(
                    $commonParams,
                    [
                        ['type' => 'i', 'value' => $usu],
                        ['type' => 'i', 'value' => $_POST['est']],
                        ['type' => 'i', 'value' => $id[0]],
                    ]
                );
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

    function opc_ciudad($id=''){
    return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=2 and estado="1" ORDER BY 1',$id);
    }
    function opc_departamento($id=''){
        return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=1 and estado="1" ORDER BY 1',$id);
    }
    function opc_perfil($id=''){
        return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=3 and estado="1" ORDER BY 1',$id);
    }
    function opc_eps($id=''){
        return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=4 and estado="1" ORDER BY 1',$id);
    }
    function opc_arl($id=''){
        return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=5 and estado="1" ORDER BY 1',$id);
    }
    function opc_estado($id=''){
        return opc_sql('SELECT idcatadeta,descripcion FROM catadeta WHERE idcatalogo=6 and estado="1" ORDER BY 1',$id);
    }
function formato_dato($a,$b,$c,$d){
	$b=strtolower($b);
	$rta=$c[$d];
	if (($a=='catalogo') && ($b=='acciones')){
		   $rta="<nav class='menu right'>";
		   $rta.="<li class='fa-solid fa-pen-to-square icon' title='Editar Empleados' id='".$c['ACCIONES']."' Onclick=\"mostrar('catalogo','pro',event,'','lib.php',4,'Funcionarios');\"></li>";
           /* $rta.="<li class='fa-solid fa-triangle-exclamation icon' title='Hallazgos' id='".$c['ACCIONES']."' Onclick=\"mostrar('hallaz','pro',event,'','hallazgos.php',4,'Hallazgos');\"></li>"; */
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