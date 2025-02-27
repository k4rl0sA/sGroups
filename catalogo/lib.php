<?php
require_once "../libs/gestion.php";
ini_set('display_errors','1');

if (!isset($_SESSION['us_riesgo'])) die("<script>window.top.location.href='/';</script>");
else {
  $rta="";
  switch ($_POST['a']){
  case 'csv': 
    header_csv ($_REQUEST['tb'].'.csv');
    $rs=array('','');    
    echo csv($rs);
    die;
    break;
  default:
    eval('$rta='.$_POST['a'].'_'.$_POST['tb'].'();');
    if (is_array($rta)) json_encode($rta);
	else echo $rta;
  }   
}

 //~ $id=($_POST['id']==''?[0,0]:explode('-',$_POST['id']));	

function divide($a){
	$id=explode("_", $a);
	return ($id);
}


function whe_catalogo() {
	$sql = "";
	if ($_POST['fidcata'])
		$sql .= " AND idcatalogo='".$_POST['fidcata']."' ";
	if ($_POST['fcatalogo'])
		$sql .= " AND descripcion like '%".$_POST['fcatalogo']."%' ";
	if ($_POST['festado'])
		$sql .= " AND estado = '".$_POST['festado']."' ";
	return $sql;
}


function lis_catalogo(){
	$sql="SELECT ROW_NUMBER() OVER (ORDER BY 1) R,idcatalogo ACCIONES,idcatadeta ID,descripcion,estado,valor from catadeta
	WHERE '1'='1'";
$sql.=whe_catalogo();
$sql.="ORDER BY 1,2,CAST(idcatadeta AS UNSIGNED), idcatadeta";
// echo $sql;
	$_SESSION['sql_catalogo']=$sql;
	$datos=datos_mysql($sql);
return panel_content($datos["responseResult"],"catalogo",15);
}

function focus_catalogo(){
 return 'catalogo';
}

function men_catalogo(){
 $rta=cap_menus('catalogo','pro');
 //~ $rta.=menu('tab','catalogo');
 return $rta;
}


function cap_menus($a,$b='cap',$con='con') {
  $rta = "";
  //~ $rta .= "<li class='icono $a grabar'      title='Grabar'          OnClick=\"grabar('$a',this);\"></li>";
  //~ $rta .= "<li class='icono $a cancelar'    title='Cerrar'          Onclick=\"ocultar('".$a."','".$b."');\" >";
  //~ $rta.="<li class='icono $a pdf' title='Pdf' id='' OnClick=\"pdf(this,event);\"></li>";
  //~ $rta.="<li class='icono crear' id='btn-crear-".$a."' OnClick=\"act_actual('".$a."','0',event);\" >";
  //~ $rta.="<li class='icono importar' id='btn-importar-".$a."' OnClick=\"upload('csv','".$a.".csv','".$a."-file');\" >";
  //~ $rta.="<li id='btn-exportar_".$a."' class='icono $a exportar' OnClick=\"exportar('".$a."');\" >";
  //~ $rta.="<li class='icono imprimir' id='btn-imprimir-".$a."' OnClick=\"imprimir('".$a."','".$a.'-'.$a.'_id'."');\" >";
  
  //~ $rta .= "<li class='icono $a crear'       title='Adicionar'       Onclick=\"captura.lim('$a',cmp['$a']);\"><li>";
    //~ $rta .= "<li class='icono $a grabar'      title='Grabar'          OnClick=\"grabar('$a',this);\"></li>";
    
  $rta .= "<li class='icono $a grabar'      title='Grabar'          OnClick=\"grabar('$a',this);\"></li>";
  //~ $rta .= "<li class='icono $a importar'    title='Importar'        OnClick=\"upload('csv','$a','$a','$con');\"></li>";
  //~ $rta .= "<li class='icono $a documento'   title='Ver Captura'     Onclick=\"desplegar('$a-captura');\"></li>";	  
  //~ $rta .= "<li class='icono $a exportar'       title='Exportar'             ></li>"; 
  //~ $rta .= "<li class='icono $a pdf'    title='Imprimir registros '   ></li>";   
  //~ $rta .= "<li class='icono $a basura'      title='Eliminar registros '   ></li>";     
  $rta .= "<li class='icono $a actualizar'  title='Actualizar'      Onclick=\"act_lista('$a',this);\"></li>";
  //~ $rta .= "<li class='icono $a listado'     title='Ver Tabla'       Onclick=\"desplegar('$a-lis');\"></li>";
  //~ $rta .= "<li class='icono $a total'       title='Ver Total'       Onclick=\"desplegar('$a-tot');\"></li>";
  $rta .= "<li class='icono $a cancelar'    title='Cerrar'          Onclick=\"ocultar('".$a."','".$b."');\" >";
  return $rta;
}

function cmp_catalogo(){
 $rta="";
 $t=['idcatalogo'=>'','idcatadeta'=>'','descripcion'=>'','valor'=>'','estado'=>'A'];
 $w='catalogo';
 //~ $id=explode('-',$_REQUEST['id']);
 $d=get_catalogo(); 
 //~ var_dump($d);
 if ($d=="") {$d=$t;}
 $d['estado']=($d['estado']=='A')?'SI':'NO';
 //~ $v=($data['valor']==0)?'':$data['valor'];
 if($d['idcatalogo']){$ids=$d['idcatalogo'].'_'.$d['idcatadeta'];}else{$ids='';}
 $c[]=new cmp('id','h',100,$ids,$w,'',0,'','','',false,'','col-1');
 $c[]=new cmp('cat','s',60,$d['idcatalogo'],$w,'Nombre Catalogo','catalogo');
 $c[]=new cmp('cod','t',12,$d['idcatadeta'],$w,'Codigo Item');
 $c[]=new cmp('des','t',40,$d['descripcion'],$w,'Texto Descriptivo Item',null,null,'Describa el Item del catalogo');
 $c[]=new cmp('est','o',1,$d['estado'],$w,'Item Activo');
 $c[]=new cmp('val','n',10,$d['valor'],$w,'Valor',0,'rgxdfnum','NNNN',false,true,'Número de 4 a 10 Digitos');
 for ($i=0;$i<count($c);$i++) $rta.=$c[$i]->put();
 $rta.="</div>";
 return $rta;
 }

function opc_catalogo(){
	//~ echo $id = ($_POST['id'] == '') ? '' : divide($_POST['id'])[0];
 return opc_sql("SELECT `idcatalogo`,concat(idcatalogo,' - ',nombre) FROM `catalogo` ORDER BY 1",$id = ($_POST['id'] == '') ? '' : divide($_POST['id'])[0]);
}

function get_catalogo(){
	//~ $id = ($_POST['id']=='0') ? '[0,0]' : explode('-',$_POST['id']);;
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
if($_POST['id']){
 $id=divide($_POST['id']);
 $est=($_POST['est']=='SI'?'A':'I');
 //~ $val=(isset($_POST['val'])?$_POST['val']:'0'); , valor=".$val."
 $sql="UPDATE `catadeta` SET idcatalogo={$_POST['cat']},idcatadeta=UPPER('{$_POST['cod']}'),descripcion=UPPER('{$_POST['des']}'),
	estado=upper('".$est."'),valor=".$val=($_POST['val']?$_POST['val']:0)."
	WHERE idcatalogo=UPPER('{$id[0]}') AND idcatadeta=UPPER('{$id[1]}');";
	//~ echo $sql;
}else{
	 $est=($_POST['est']=='SI'?'A':'I');
	//~ UPDATE `catadeta` SET `idcatadeta` = 'CC' WHERE `catadeta`.`idcatalogo` = 1 AND `catadeta`.`idcatadeta` = 'CCi';
	$sql="INSERT INTO `catadeta` VALUES (upper('{$_POST['cat']}'),UPPER('{$_POST['cod']}'),UPPER('{$_POST['des']}'),'".$est."',".$val=($_POST['val']?$_POST['val']:0).");";
//~ $sql="CALL CREA_CATALOGODETALLE($id[0],$id[1],'{$_POST['cod']}','$est','{$_POST['cod']}','{$_POST['cod']}');
 //~ CALL SP_ACT_CATALOGO('".intval($cat[0])."','{$_POST['cat']}','{$_POST['des']}','{$_POST['val']}','$est',:rta)";
 //~ return (dato_oci($sql,true,0));	
	//~ echo $sql;
}

return dato_mysql($sql);
}
	
function formato_dato($a,$b,$c,$d){
 $b=strtolower($b);
 $rta=$c[$d];
 //~ $rta=iconv('ISO-8859-1', 'UTF-8',$rta);
  if ($a=='catalogo'&& $b=='id'){$rta= "<div class='txt-center'>".$c['ID']."</div>";}
  //~ var_dump($c);
 if (($a=='catalogo') && ($b=='acciones'))    {
		$rta="<nav class='menu right'>";
		//~ $rta.="<li class='icono pdf' title='Pdf' id='".$c['ID']."' OnClick=\"pdf(this,event);\"></li>";
		$rta.="<li class='icono editar' title='Editar' id='".$c['ACCIONES']."_".$c['ID']."' Onclick=\"mostrar('catalogo','pro',event,'','lib.php',4);\"></li>";
		if ($c['estado']=='A'){
			//~ $rta.="<li class='icono inactiva' title='Inactivar' id='".$c['ID']."' OnClick=\"inactivareg(this,event,'agenda');act_lista(f,this);\" ></li>";
		}
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
