<?php
session_start();
ini_set('display_errors','1');
include $_SERVER['DOCUMENT_ROOT'].'/libs/nav.php';
?>
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catalogo</title>
<link href="https://fonts.googleapis.com/css2?family=Economica&family=Spicy+Rice&family=Trade+Winds&display=swap" rel="stylesheet">
<link href="../libs/css/s.css" rel="stylesheet">
<!--
<link href="../libs/css/styleCustom.css" rel="stylesheet">
-->
<script src="/libs/js/c18082020.js"></script>
<script src="/libs/js/d.js"></script>
<script >
var mod='catalogo';	
var ruta_app='lib.php';
function csv(b){
		//~ var myWindow = window.open("../libs/procesar.php?a=exportar&b="+b,"Descargar archivo");
		var myWindow = window.open("../libs/gestion.php?a=exportar&b="+b,"Descargar archivo");
}

document.onkeyup=function(ev) {
 ev=ev||window.event;
 if (ev.ctrlKey && ev.keyCode==46) ev.target.value='';
 if (ev.ctrlKey && ev.keyCode==45) ev.target.value=ev.target.placeholder;
};


function actualizar(){
	act_lista(mod);
}

function showFil(a){
	desplegar(a+'-fil');
	if (document.getElementById(a) != undefined) {
		var w=document.getElementById(a);
		if(w.classList.contains('col-8')){
			w.classList.replace('col-8','col');
		}else{
			w.classList.replace('col','col-8');
		}
		
	}
}


function grabar(tb='',ev){
  if (tb=='' && ev.target.classList.contains(proc)) tb=proc;
  var f=document.getElementsByClassName('valido '+tb);
   for (i=0;i<f.length;i++) {
     if (!valido(f[i])) {f[i].focus(); return};
  }  
  document.getElementById(tb+'-msj').innerHTML=ajax(ruta_app,"a=gra&tb="+tb,false);
  if (document.getElementById(tb+'-msj') != undefined)
  act_lista(tb);
}

</script>
</head>
<body Onload="actualizar();">
<?php
require_once "../libs/gestion.php";
if (!isset($_SESSION["us_riesgo"])){ die("<script>window.top.location.href = '/';</script>");}

$mod='catalogo';
$ya = new DateTime();
$estados=opc_sql("select idcatadeta,descripcion from catadeta where idcatalogo=11 and estado='A' order by 1",'A');
$catalogos=opc_sql("SELECT `idcatalogo`,concat(idcatalogo,' - ',nombre) FROM `catalogo` ORDER BY 1",''); 

//~ $estados="";
//~ $catalogos="";
//~ $datos = json_encode(dato_mysql("select idcatadeta,descripcion from catadeta where idcatalogo=1 order by 2"));
//~ $transacciones=opcion_mysql("select idcatadeta,descripcion from catadeta where idcatalogo=1 order by 2 ",'');
//~ $medpags=opcion_mysql("select DSCODDET,DSDES from GEDETSUPTIP where DSCODTIP = 87",'');
//~ $naturalezas=opcion_mysql("select DSCODDET,DSDES from GEDETSUPTIP where DSCODTIP = 201",'');
//~ $estados =opcion_mysql("SELECT DSCODDET, DSDES ESTADO FROM GEDETSUPTIP WHERE DSCODTIP=199 ORDER BY 1 DESC",'');
$gu=[['dato1','dato2'],['dato3','dato4'],['dato5','dato6'],['dato7','dato8']];
//~ $catalogo=opcion_mysql("select idcatadeta,descripcion from catadeta where idcatalogo=1 order by 2 ",'');
//~ $estados=opcion_mysql("select idcatadeta,descripcion from catadeta where idcatalogo=11 order by 2 ",'',false);
//~ $catalogos=opc_sql("select id_usuario,nombre from usuarios order by 1",'A');

//~ ,descripcion from catadeta where idcatalogo=11 order by 2",'A');
//~ $catalogos=opc_sql("select id_usuario,nombre from usuarios order by 1",'A');
//~ $catalogos=opc_sql("select idcatalogo,nombre from catalogo order by 1",'A');


function opc_oci($sql,$val,$str=true){ 
	$rta="<option value class='alerta' >SELECCIONE</option>";
	$rs=oci_parse($GLOBALS['con'],$sql);
	if (oci_execute($rs)){
		while ($row = oci_fetch_array($rs,OCI_NUM+OCI_RETURN_NULLS)) {
			$o=(int)$row[0];
		$r=($str==true)?$row[0]:$row[1];
       $sel=($r==$val)?" selected ":"";   
       if ($str) {
		   $rta.="<option ".$sel." value='".$row[0]."' >".htmlentities($row[1],ENT_QUOTES)."</option>";
       }else{  
		 $rta.="<option ".$sel." >".str_pad($o,4,"0",STR_PAD_LEFT).' '.htmlentities($row[1],ENT_QUOTES)."</option>";
	 }
		}
	}
	return $rta;
}

?>


<form method='post' id='fapp' >
<div class="col-2 menu-filtro" id='<?php echo$mod; ?>-fil'>
	
<!--
	<div class="campo"><div>Transacción</div>
		<input class="captura" size=10 id="ftransaccion" name="ftransaccion" list="lista_transaccion" OnChange="actualizar();" >
		<datalist id="lista_transaccion" > ?php echo  $transacciones; ?></datalist>
	</div>
	<select id='grupo_usuario' class='filtro' name='grupo_usuario '>?php echo opcion($gu,$vgu); ?></select>
-->
	<div class="campo"><div>Cod Catalogo</div>
		<select class="captura" id="fidcata" name="fidcata" OnChange="actualizar();">
			<?php echo $catalogos; ?>
		</select>
	</div>
	<div class="campo"><div>Catalogo</div><input class="captura" size=50 id="fcatalogo" name="fcatalogo" OnChange="actualizar();"></div>
	<div class="campo"><div>Estado (A/I)</div>
		<select class="captura" id="festado" name="festado" OnChange="actualizar();">
			<?php echo $estados; ?>
		</select>
	</div>
</div>
<div class='col-8 panel' id='<?php echo$mod; ?>'>
      <div class='titulo' >CATALOGO
		<nav class='menu left' >
			<li class='icono listado' title='Ver Listado' onclick="desplegar(mod+'-lis');" ></li>
			<li class='icono exportar'      title='Exportar CSV'    Onclick="csv(mod);"></li>
			<li class='icono actualizar'    title='Actualizar'      Onclick="actualizar();">
			<li class='icono filtros'    title='Filtros'      Onclick="showFil(mod);">
			<li class='icono crear'       title='Crear Catalogo'     Onclick="mostrar(mod,'pro');"></li>
			<!-- change_size(mod,600,500);	 -->
		</nav>
		<nav class='menu right' >
			<li class='icono ayuda'      title='Necesitas Ayuda'            Onclick=" window.open('https://sites.google.com/', '_blank');"></li>
            <li class='icono cancelar'      title='Salir'            Onclick="location.href='../logout.php'"></li>
        </nav>               
      </div>
      <div>
<!--
		<b style="color:red">Para consultar seleccione todos los filtros necesarios y de clic en el boton de actualizar : </b>
		<div style="margin: auto 95px">	Información:  <b>Ctrl Supr</b> limpia campos</div>
		<li class='icono actualizar'></li>
-->
		</div>	
     <span class='mensaje' id='<?php echo$mod; ?>-msj' ></span>
     <div class='contenido' id='<?php echo$mod; ?>-lis' ></div>     
</div>
<div class='load' id='loader' z-index='0' ></div>
</form>	
</body>
