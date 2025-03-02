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
        $filtros[] = ['campo' => 'C.idcatalogo', 'valor' => limpiar_y_escapar_array(explode(",", $_POST['fidcata'])), 'operador' => 'IN'];
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
    $sql = "SELECT C.idcatalogo ACCIONES,C.idcatadeta ID,C.descripcion,CTLG(6,IF(C.estado=A,1,2)) Estado,C.valor
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
    $t=['idcatalogo'=>'','idcatadeta'=>'','descripcion'=>'','valor'=>'','estado'=>'A'];
    $w='catalogo';
    $d=get_catalogo(); 
    if ($d=="") {$d=$t;}
    $d['estado']=($d['estado']=='A')?'SI':'NO';
    $ids = ($d['idcatalogo']) ? $d['idcatalogo'].'_'.$d['idcatadeta'] :'' ;
    $c[]=new cmp('id','h',99999,$ids,$w,'',0,'','','',false,'','col-1');
    $c[]=new cmp('cat','s',60,$d['idcatalogo'],$w,'Nombre Catalogo','catalogo');
    $c[]=new cmp('cod','n',99999,$d['idcatadeta'],$w,'Codigo Item');
    $c[]=new cmp('des','t',80,$d['descripcion'],$w,'Texto Descriptivo Item',null,null,'Describa el Item del catalogo');
    $c[]=new cmp('est','o',2,$d['estado'],$w,'Item Activo');
    $c[]=new cmp('val','n',99999,$d['valor'],$w,'Valor',0,'rgxdfnum','NNNN',false,true,'Número de 4 a 10 Digitos');
    for ($i=0;$i<count($c);$i++) $rta.=$c[$i]->put();
    $rta.="</div>";
    return $rta;
	}
    function get_catalogo(){
	    if($_POST['id']=='0'){
	    	return "";
	    }else{
	    	$id=divide($_POST['id']);
	    	$sql="SELECT * FROM catadeta WHERE idcatalogo='".$id[0]."' AND idcatadeta='".$id[1]."'";
	    	//~ echo $sql;
	    	$info=datos_mysql($sql);
	    	return $info['responseResult'][0];		
	    } 
	}
    function gra_catalogo(){
        $id=divide($_POST['id']);
        $est=($_POST['est']=='SI'?'A':'I');
        $commonParams = [
            ['type' => 'i', 'value' => $_POST['cat']],
            ['type' => 'i', 'value' => $_POST['cod']],
            ['type' => 's', 'value' => $_POST['des']],
            ['type' => 's', 'value' => $est],
            ['type' => 'i', 'value' => $_POST['val']]
        ];
        if($_POST['id']){
            $sql = "UPDATE catadeta SET idcatalogo=?,idcatadeta=?,descripcion=?,estado =?,valor=? WHERE idcatalogo = ? and idcatadeta=?";
                $params = array_merge(
                    $commonParams,
                    [['type' => 'i', 'value' => $id[0]],
                    ['type' => 'i', 'value' => $id[1]]]
                );
        }else{
            $sql = "INSERT INTO catadeta VALUES (?,?,?,?,?)";
            $params = $commonParams;

        }
		$rta = mysql_prepd($sql, $params);
		header('Content-Type: application/json; charset=utf-8'); 
		echo json_encode($rta);
		exit;
	}

    function opc_catalogo(){
     return opc_sql("SELECT `idcatalogo`,concat(idcatalogo,' - ',nombre) FROM `catalogo` ORDER BY 1",$id = ($_POST['id'] == '') ? '' : divide($_POST['id'])[0]);
    }

function formato_dato($a,$b,$c,$d){
	$b=strtolower($b);
	$rta=$c[$d];
	if (($a=='catalogo') && ($b=='acciones')){
		   $rta="<nav class='menu right'>";
		   $rta.="<li class='fa-solid fa-pen-to-square icon' title='Editar Catalogo' id='".$c['ACCIONES']."_".$c['ID']."' Onclick=\"mostrar('catalogo','pro',event,'','lib.php',4,'Catalogos');\"></li>";
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