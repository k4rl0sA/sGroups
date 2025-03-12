const rgxtxt="[a-zA-Z0-9]{0,99}";
const rgxmail = "^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$";
const rgxndoc = "[0-9]{1,18}";
const rgxpred = /^[0-9]{1,7}$/;
const rgxphone = /^(3\d{9}|[0-9]{7})$/;
const rgxphone1 = /^(3\d{9}|[0-9]{7}|0)$/;
const SUCCESS_DURATION = 5000;
const ERROR_DURATION = 7000;
const INFO_DURATION = 1000;
const WARNING_DURATION = 5000;

window.appVersion = "1.03.29.1";

const version = document.querySelector("div.usuario");

if (version) {
  const actual = version.textContent;
  let ver=actual.split("_");
   // Verificar si la versión en el div.usuario es igual a window.appVersion
   
  if (ver[1] !== window.appVersion) {
	alert('Por favor recuerda borrar tu Cache, para utilizar la versión más estable del sistema '+window.appVersion);
	window.location.href = '/logout.php';
	exit;
    version.textContent = actual + '_' + window.appVersion;
  }
}

document.addEventListener('keydown', function (event) {
	if (event.ctrlKey && event.key === 'v') {
		inform('Esta acción no esta permitida');
	  event.preventDefault();
	}
  });

   document.addEventListener('contextmenu', function (event) {
	inform('Esta acción no esta permitida');
	event.preventDefault();
  });

function countMaxChar(ele, max=5000) {
	ele.addEventListener("input", function() {
		var longitud = this.value.length;
		if (longitud > max){
			this.value=this.value.slice(0,max);
			warnin("Has ingresado más de " + max + " caracteres en el campo");
		}
	});
}
function solo_numero(e) {
	var unicode = e.charCode ? e.charCode : e.keyCode
	if (unicode != 8 & unicode != 9) {
		if ((unicode < 48 || unicode > 57))
			return false
	}
}
function solo_numeroFloat(e) {
	var unicode = e.charCode ? e.charCode : e.keyCode;
	if ((unicode >= 48 && unicode <= 57) || unicode === 46) {
	  var inputValue = e.target.value;
	  if (unicode === 46 && inputValue.indexOf('.') !== -1) {
		return false;
	  }
	} else if (unicode !== 8 && unicode !== 9) {
	  return false;
	}
  }
function solo_fecha(e) {
	var unicode = e.charCode ? e.charCode : e.keyCode
	if (unicode != 8 && unicode != 9) {
		if ((unicode < 45 || unicode > 58) && (unicode!=32))
			return false
	}
}
function solo_hora(e) {
	var unicode = e.charCode ? e.charCode : e.keyCode
	if (unicode != 8 && unicode != 9) {
		if ((unicode < 48 || unicode > 58))
			return false
	}
}



function checkon(a) {
	if (a.value == 'NO')
		a.value = 'SI';
	else
		a.value = 'NO';
}
function is_option(a) {
    for (var i = 0; i < (a.list?.options?.length || 0); i++) { // Usar ?. y || para evitar errores
        if (a.list?.options?.[i]?.value == a.value) {
            return true;
        }
    }
    return false;
}
function solo_reg(a,b='[A..Z]',inver = false) {
	eliminarError(a);
	var r = new RegExp(b);
	a.classList.remove('alert');
	a.classList.remove('invalid');
	// Simplificando la lógica con un operador ternario
	(inver ? r.test(a.value) : !r.test(a.value)) ? a.classList.add('alert', 'invalid') : null;
	if(a.classList.contains('alert','invalid')){
		return mostrarError(a,"El formato no es valido");
	}
}
function valido(a) {
	eliminarError(a);
    a.classList.remove('alert', 'invalid');
    // Maneja campos vacíos (tanto selects simples como múltiples)
    if (a.value === '') {
        a.classList.add('alert', 'invalid');
        if (a.multiple) {
            document.querySelector('select[name="' + a.id + '[]"]').previousElementSibling.classList.add('alerta');
        }
        return mostrarError(a, 'Este campo es obligatorio.'); // Mensaje personalizado para campos vacíos
    }
	if (a.list !== undefined && a.list !==null) {if (!is_option(a)) {a.classList.add('alerta','invalid');return mostrarError(a, 'El valor ingresado no es una opción válida.');}}
    // Maneja validación de rango para fechas, horas y fechas/horas
    if (['date', 'time', 'datetime-local', 'datetime'].includes(a.type)) {
        if ((a.min !== '' || a.max !== '') && (a.value < a.min || a.value > a.max)) {
            const minMaxMessage = `El valor debe estar entre ${a.min} y ${a.max}.`;
            return mostrarError(a, minMaxMessage); // Mensaje personalizado para rangos de fecha/hora
        }
    }
    return !a.classList.contains('alert', 'invalid');
}

function mostrarError(element, message) {
    let errorElement = element.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('error-message')) {
        errorElement = document.createElement('span');
        errorElement.classList.add('error-message');
        element.parentNode.insertBefore(errorElement, element.nextSibling);
    }
    // Crear el elemento <i> para el icono
    let iconElement = document.createElement('i');
    iconElement.classList.add('fa-solid', 'fa-circle-exclamation'); // Añade las clases del icono
    iconElement.style.marginRight = '5px'; // Espacio entre el icono y el mensaje
    // Limpiar el contenido anterior del errorElement antes de añadir el nuevo contenido
    errorElement.innerHTML = '';
    // Agregar el icono y el mensaje al elemento de error
    errorElement.appendChild(iconElement);
    errorElement.appendChild(document.createTextNode(message)); // Añade el texto como un nodo de texto para evitar problemas de seguridad
    errorElement.style.display = 'block';
    element.classList.add('alert');
    return false;
}
function eliminarError(element) {
    let errorElement = element.nextElementSibling;
    if (errorElement && errorElement.classList.contains('error-message')) {
        errorElement.remove();
        element.classList.remove('alert');
    }
}
function valor(a, b) {
	var x=document.getElementById(a);
	if (x==undefined) var x=parent.document.getElementById(a);
	if (b!=undefined && x!=undefined) x.value=b;
	if (x!=undefined) {
		if (x.value=='') return x.value;
		if (!isNaN(x.value)) return parseInt(x.value);
		else return x.value;
	}
}
function ir_pag(tb, pag, tot,path) {
	if ((pag > 0) && (pag <= tot))
		document.getElementById('pag-'+tb).value = pag;
	act_lista(tb, document.getElementById('pag-'+tb),path);
}
function mostrar(tb, a='', ev, m='', lib=ruta_app, w=7, tit='', k='0') {
	var id = tb+'-'+a;
	if (a == 'pro') {
        if (ev!=undefined) {
			k=ev.target.id;
		}else{
			tit=document.querySelector('.content.content-2 .title.txt-center h2').innerText;
		}
		crear_panel(tb, a, w, lib, tit);
        act_html(id+'-con',lib,'a=cmp&tb='+tb+'&id='+k);
	}
	if (a == 'fix') {
        if (ev!=undefined) {tit=ev.currentTarget.title;k=ev.target.id;}
		panel_fix(tb, a, w, lib, tit);
        act_html(id+'-con',lib,'a=cmp&tb='+tb+'&id='+k);        
	}
	if (a == 'sta') {
        if (ev!=undefined) {tit=ev.currentTarget.title;k=ev.target.id;}
		panel_static(tb, a, w, lib, tit);
        act_html(id+'-con',lib,'a=cmp&tb='+tb+'&id='+k);        
	}
    if (document.getElementById(id+'-msj')!=undefined) document.getElementById(id+'-msj').innerHTML="";
	if (document.getElementById(tb+'-msj')!=undefined) document.getElementById(tb+'-msj').innerHTML="";
    foco(inner(id+'-foco'));
}
function crear_panel(tb, a, b = 7, lib = ruta_app, tit = '') {
	var id = tb+'-'+a;
	if (document.getElementById(id) == undefined) {
		var p = document.createElement('div');
		p.id = id;
		p.className = a+' frm-data ';
		var txt ="<div class='ventana'><div class='barra-titulo'>";
		txt += "<span class='frm-title txt-center'>"+(tit==''?tb.replace('_', ' '):tit)+"</span>";
		txt += "<button class='btn-cerrar' Onclick=\"ocultar('"+tb+"','"+a+"');\"><i class='fas fa-times'></i></button></div>";

		// txt += "<nav class='left'><ul class='menu' id='"+id+"-menu'></ul></nav><nav class='menu right'><li class='icono "+tb+ " cancelar' title='Cerrar' Onclick=\"ocultar('"+tb+"','"+a+"');\"></li></nav></div>";
        txt += "<div class='frm-row "+(a=='lib'?'lib-con':'')+"' id='"+id+"-con' ></div>";
		txt +="<div class='frm-menu' id='"+id+"-menu'></div>";
		txt +='<div class="card-body"></div>';
		p.innerHTML = txt;
		document.getElementById(tb+'-frmcap').appendChild(p);
        act_html(id+'-menu',lib,'tb='+tb+'&a=men&b='+a, false);
	}
}
/* function act_html(a, b, c, d = false) {  
    if (document.getElementById(a) != undefined) {
        pFetch(b, c + form_input('fapp'), function(responseText) { 
            let x = document.getElementById(a);
            if (x.tagName == "INPUT")
                x.value = responseText.replace(/(\r\n|\n|\r)/gm, "");
            else 
			x.insertAdjacentHTML('afterend',responseText.replace(/(\r\n|\n|\r)/gm, ""))
                // x.innerHTML = responseText.replace(/(\r\n|\n|\r)/gm, "");03-02-2025
				ShowCells(a.split("-")[0]+'_fil');
				if (x.classList.contains('frm-row')) {
					const elementos = x.querySelectorAll('input, select, textarea');
					if (elementos.length > 1) {
						elementos[1].focus();
					}
				}
            if (d != false)
                d.apply('a');
        }, "POST", {"Content-type": "application/x-www-form-urlencoded"});
    }
} */
function act_html(a, b, c, d = false) {
    if (document.getElementById(a) != undefined) {
        pFetch(b, c + form_input('fapp'), function (responseText) {
            let x = document.getElementById(a);
            let cleanedResponse = responseText.replace(/(\r\n|\n|\r)/gm, ""); // Limpia el texto
            if (x.tagName == "INPUT") {
                x.value = cleanedResponse;
            } else {
                // Elimina todos los hijos del elemento
                while (x.firstChild) {
                    x.removeChild(x.firstChild);
                }
                let tempContainer = document.createElement('div');
                tempContainer.innerHTML = cleanedResponse; // Usa innerHTML en un contenedor temporal
                while (tempContainer.firstChild) {
                    x.appendChild(tempContainer.firstChild);
                }
            }
            ShowCells(a.split("-")[0] + '_fil');
            if (x.classList.contains('frm-row')) {
                const elementos = x.querySelectorAll('input, select, textarea');
                if (elementos.length > 1) {
                    elementos[1].focus();
                }
            }
            if (d != false) {
                d.apply('a');
            }
        }, "POST", { "Content-type": "application/x-www-form-urlencoded" });
    }
}
/*  function form_input(a) {
	var d = "";
	var frm = document.getElementById(a);
	for (i = 0; i < frm.elements.length; i++) {
		if (frm.elements[i].tagName = "select" && frm.elements[i].multiple) {
			var vl = [];
			for (var o = 0; o < frm.elements[i].options.length; o++) {
				if (frm.elements[i].options[o].selected) {
					vl.push("'"+frm.elements[i].options[o].value+"'");
				}
			}
			d += "&"+frm.elements[i].id+"="+vl.join(",");
		} else {
			d += "&"+frm.elements[i].id+"="+frm.elements[i].value.toString();
		}
	}
	return d;
} 
 */	 function form_input(a) {
    var d = "";
    var frm = document.getElementById(a);
    for (var i = 0; i < frm.elements.length; i++) {
        var element = frm.elements[i]; // Almacenar el elemento para facilitar el acceso
        if (element.tagName.toUpperCase() === "SELECT" && element.multiple) {
            var vl = [];
            for (var o = 0; o < element.options.length; o++) {
                if (element.options[o].selected) {
                    vl.push(element.options[o].value); // No es necesario añadir comillas extra
                }
            }
            d += "&" + element.id + "=" + vl.join(",");  // O usar un formato de array si tu servidor lo soporta
        } else if (element.id) { // Solo añadir parámetros para elementos con un nombre
            d += "&" + element.id + "=" + element.value; // Usar element.name, no element.id si quieres usar el atributo name
        }
    }
    return d;
}
/* function mostrar(tb, a='', ev, m='', lib=ruta_app, w=7, tit='', k='0') {
	var id = tb+'-'+a;
	if (a == 'pro') {
        if (ev!=undefined) {
			k=ev.target.id;
		}else{
			tit=document.querySelector('.content.content-2 .title.txt-center h2').innerText;
		}
		crear_panel(tb, a, w, lib, tit);
        act_html(id+'-con',lib,'a=cmp&tb='+tb+'&id='+k);
	}
	if (a == 'fix') {
        if (ev!=undefined) {tit=ev.currentTarget.title;k=ev.target.id;}
		panel_fix(tb, a, w, lib, tit);
        act_html(id+'-con',lib,'a=cmp&tb='+tb+'&id='+k);        
	}
	if (a == 'sta') {
        if (ev!=undefined) {tit=ev.currentTarget.title;k=ev.target.id;}
		panel_static(tb, a, w, lib, tit);
        act_html(id+'-con',lib,'a=cmp&tb='+tb+'&id='+k);        
	}
    if (document.getElementById(id+'-msj')!=undefined) document.getElementById(id+'-msj').innerHTML="";
	if (document.getElementById(tb+'-msj')!=undefined) document.getElementById(tb+'-msj').innerHTML="";
    foco(inner(id+'-foco'));
}

function crear_panel(tb, a, b = 7, lib = ruta_app, tit = '') {
	var id = tb+'-'+a;
	if (document.getElementById(id) == undefined) {
		var p = document.createElement('div');
		p.id = id;
		p.className = a+' frm-data ';
		var txt ="<div class='ventana'><div class='barra-titulo'>";
		txt += "<span class='frm-title txt-center'>"+(tit==''?tb.replace('_', ' '):tit)+"</span>";
		txt += "<button class='btn-cerrar' Onclick=\"ocultar('"+tb+"','"+a+"');\"><i class='fas fa-times'></i></button></div>";

		// txt += "<nav class='left'><ul class='menu' id='"+id+"-menu'></ul></nav><nav class='menu right'><li class='icono "+tb+ " cancelar' title='Cerrar' Onclick=\"ocultar('"+tb+"','"+a+"');\"></li></nav></div>";
        txt += "<div class='frm-row "+(a=='lib'?'lib-con':'')+"' id='"+id+"-con' ></div>";
		txt +="<div class='frm-menu' id='"+id+"-menu'></div>";
		txt +='<div class="card-body"></div>';
		p.innerHTML = txt;
		document.getElementById(tb+'-main').appendChild(p);
        act_html(id+'-menu',lib,'tb='+tb+'&a=men&b='+a, false);
        // act_html(id+'-foco',lib,'tb='+tb+'&a=focus&b='+a, false); 
	}
	// document.getElementById(id).style.display = "block";	
	//document.getElementById(id+"-con").innerHTML="";		
} */

function panel_fix(tb, a, b = 7, lib = ruta_app, tit = '') {
	var id = tb+'-'+a;
	if (document.getElementById(id) == undefined){
		var p = document.createElement('div');
		p.id = id;
		p.className = a+' panel'+(a=='fix'?' col-8':' col-'+b);
		var txt = "<div class='frm-row "+(a=='lib'?'lib-con':'')+"' id='"+id+"-con' ></div>";
		p.innerHTML = txt;
		document.getElementById('fapp').appendChild(p);
		Drag.init(document.getElementById(id+'-con'),p);
		document.getElementById(id).style.top=(screen.height-p.style.height)/7;
		document.getElementById(id).style.left=(screen.width-p.style.width)/10.5;
        act_html(id+'-menu',lib,'tb='+tb+'&a=men&b='+a, false);
        act_html(id+'-foco',lib,'tb='+tb+'&a=focus&b='+a, false); 
	}
	document.getElementById(id).style.display = "block";	
}
function panel_static(tb, a, b = 7, lib = ruta_app, tit = '') {
	var id = tb+'-'+a;
	if (document.getElementById(id) == undefined) {
		var p = document.createElement('div');
		p.id = id;
		p.className = a+' panel'+(a=='frm'?'col-0':' static col-'+b);
		var txt = "<div id='"+id+"-tit'>";
		txt += "<span id='"+id+"-foco' class='oculto'></span>";
		txt += "<nav cass='left'></nav><nav class='menu right'></nav></div>";
		txt += "<span id='"+id+"-msj' class='mensaje' ></span>";
        txt += "<div class='contenido "+(a=='lib'?'lib-con':'')+"' id='"+id+"-con' ></div>";
		p.innerHTML = txt;
		document.getElementById('fapp').appendChild(p);
		Drag.init(document.getElementById(id+'-tit'),p);
		document.getElementById(id).style.top=(screen.height-p.style.height)/7;
		document.getElementById(id).style.left=(screen.width-p.style.width)/10.5;
        act_html(id+'-menu',lib,'tb='+tb+'&a=men&b='+a, false);
        act_html(id+'-foco',lib,'tb='+tb+'&a=focus&b='+a, false); 
	}
	document.getElementById(id).style.display = "block";	
	//document.getElementById(id+"-con").innerHTML="";		
}
function foco(a){
    if (document.getElementById(a)!=undefined) document.getElementById(a).focus();
}
function inner(a){
    if (document.getElementById(a)!=undefined) {
        var b=document.getElementById(a).innerHTML;
        return b.replace(/(\r\n|\n|\r)/gm, "");
    }    
}
function ocultar(tb,a) {
    var windo = document.getElementById(tb+'-'+a);
    windo.parentNode.removeChild(windo);
} 
function can_children(tb, a) {
	if (a == 'cap') {
		var id = tb+'-'+a;
		var c = document.getElementById(id+'-menu');
		if (c != undefined) {
			for (var b = 0; b < c.children.length; b++) {
				if (c.children[b].id.indexOf('mostrar') >= 0) {
					var d = c.children[b].id.substr(c.children[b].id.indexOf('mostrar')+8);
					ocultar(d, 'tab');
					ocultar(d, 'gra');
				}
			}
		}
		if (document.getElementById('indicador-objeto') != undefined)
			ocultar(document.getElementById('indicador-objeto').value.toLowerCase(), 'tab');
	}
}
function desplegar(a) {
	if (document.getElementById(a) != undefined) {
		var b = document.getElementById(a);
		if (b.style.display == 'none') {			
            var left = (screen.width - b.style.width) / 2;
            var top = (screen.height - b.style.height) / 2;        
            b.top=top;
            b.left=left;
            b.style.display = 'block';
        }   
		else
			b.style.display = 'none';
	}
}
function act_lista(tb, b,lib = ruta_app) {
	if (document.getElementById(tb+'-msj') != undefined)
		valor(tb+'-msj', '...');
	if (document.getElementById(tb+'-lis') != undefined)
		act_html(tb+'-lis', lib, 'tb=' +tb+ '&a=lis', false); 
	if (document.getElementById(tb+'-tot') != undefined)
		act_html(tb+'-tot', lib, 'tb=' +tb+ '&a=tot', false); 
	if (document.getElementById('indicador-indicador') != undefined)
		if (document.getElementById('grafica_gra') != undefined)
			graficar();
	if (parent.document.getElementById(tb+'-frm- ') != undefined)
		resizeIframe(parent.document.getElementById(tb+'-frm-con').childNodes[0]);
}
/* function act_html(a, b, c, d = false) {  
    if (document.getElementById(a) != undefined) {
        pFetch(b, c + form_input('fapp'), function(responseText) { 
            var x = document.getElementById(a);
            if (x.tagName == "INPUT")
                x.value = responseText.replace(/(\r\n|\n|\r)/gm, "");
            else 
                x.innerHTML = responseText.replace(/(\r\n|\n|\r)/gm, "");
                
            //  if (x.classList.contains('contenido')){
            //     var f = x.id.replace('con', 'foco');
            //     if (document.getElementById(f) != undefined)
            //         foco(document.getElementById(f).innerText);
            // } 
				if (x.classList.contains('frm-row')) {
					const elementos = x.querySelectorAll('input, select, textarea');
					if (elementos.length > 1) {
						elementos[1].focus();
					}
				}
            if (d != false)
                d.apply('a');
        }, "POST", {"Content-type": "application/x-www-form-urlencoded"});
    }
} 
function form_input(a) {
	var d = "";
	var frm = document.getElementById(a);
	for (i = 0; i < frm.elements.length; i++) {
		if (frm.elements[i].tagName = "select" && frm.elements[i].multiple) {
			var vl = [];
			for (var o = 0; o < frm.elements[i].options.length; o++) {
				if (frm.elements[i].options[o].selected) {
					vl.push("'"+frm.elements[i].options[o].value+"'");
				}
			}
			d += "&"+frm.elements[i].id+"="+vl.join(",");
		} else {
			d += "&"+frm.elements[i].id+"="+frm.elements[i].value.toString();
		}
	}
	return d;
} */

/* function act_html(a, b, c, d = false) {
    if (document.getElementById(a) != undefined) {
        const form = document.getElementById('fapp');
        if (!form) {
            console.error("Formulario 'fapp' no encontrado.");
            return;
        }
        const formData = new FormData(form);
        const params = new URLSearchParams(c);
        for (const [key, value] of params) {
            formData.append(key, value);
        }
        if (!formData.has('tb')) {
            formData.append('tb', valParam('tb'));
        }
        if (!formData.has('a')) {
            formData.append('a', valParam('a'));
        }
        if (!formData.has('id')) {
            formData.append('id', valParam('id'));
        }
		console.log(formData)
        pFetch(b, formData, function(responseText) {
            // *** ESTO ES LO QUE FALTABA ***
            var x = document.getElementById(a);
            if (x.tagName == "INPUT")
                x.value = responseText.replace(/(\r\n|\n|\r)/gm, "");
            else 
                x.innerHTML = responseText.replace(/(\r\n|\n|\r)/gm, "");
            if (x.classList.contains('frm-row')) {
                const elementos = x.querySelectorAll('input, select, textarea');
                if (elementos.length > 1) {
                    elementos[1].focus();
                }
            }
            if (d != false)
                d.apply('a');
            // *** FIN DE LA PARTE QUE FALTABA ***
        }, "POST");
    }
}
function valParam(nombre) {
    nombre = nombre.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + nombre + '(=([^&#]*)|&|#|$)'),
        resultados = regex.exec(window.location.href);
    if (!resultados) return null;
    if (!resultados[2]) return '';
    return decodeURIComponent(resultados[2].replace(/\+/g, ' '));
} */

function selectDepend(a,b,c){
	if(b!=''){
		const x = document.getElementById(a);
		const z = document.getElementById(b);
		z.innerHTML="";
		if (window.XMLHttpRequest)
			xmlhttp = new XMLHttpRequest();
		else
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			xmlhttp.onreadystatechange = function () {
			if ((xmlhttp.readyState == 4) && (xmlhttp.status == 200)){
				data =JSON.parse(xmlhttp.responseText);
				console.log(data)
			}}
				xmlhttp.open("POST",c,false);
				xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xmlhttp.send('a=opc&tb='+a+b+'&id='+x.value);
				//~ var rta =data;
				var data=Object.values(data);
				var opt = document.createElement('option');
				opt.text ='SELECCIONE';
				// opt.classList.add('alerta');
				opt.value='';
				z.add(opt);
				for(i=0;i<data.length;i++){
					var obj=Object.keys(data[i]);
					var opt = document.createElement('option');
					opt.text =data[i][obj[1]];
					opt.value=data[i][obj[0]];;
					z.add(opt);
				}
	}
}

// Cola de mensajes global
const messageQueue = [];
/**
 * Función principal para manejar respuestas de myFetch.
 * Muestra mensajes de éxito o error con los tiempos adecuados.
 */
function handleResponse(response) {
    if (!response) {
        enqueueMessage('error', 'Error en la petición.', ERROR_DURATION);
        return;
    }
    if (typeof response === 'object' && response.status) {
        if (response.status === 'error') {
			// typeErrors(response.message);
            enqueueMessage('error', response.message || 'Error desconocido.', ERROR_DURATION);
        } else {
            enqueueMessage('success', response.message || 'Operación completada.', SUCCESS_DURATION);
        }
    } else {
        enqueueMessage('success', response, SUCCESS_DURATION); // Manejo de respuestas en texto plano
    }
}
/**
 * Agrega un mensaje a la cola y lo muestra en orden.
 */
function enqueueMessage(type, message, duration) {
    messageQueue.push({ type, message, duration });
    if (messageQueue.length === 1) {
        processNextMessage();
    }
}
/**
 * Procesa el siguiente mensaje en la cola y lo muestra.
 */
function processNextMessage() {
    if (messageQueue.length === 0) return;
    const { type, message, duration } = messageQueue[0];
    displayToast(type, message, duration);
    setTimeout(() => {
        messageQueue.shift();
        processNextMessage();
    }, duration);
}
/**
 * Muestra un mensaje tipo toast con animación y barra de progreso.
 */
function displayToast(type, message, duration) {
    const overlay = document.getElementById('overlay');
    const toast = document.querySelector(".toast");
    const toastIcon = document.querySelector('.toast-content i');
    const toastTitle = document.querySelector('.text-1');
    const toastMessage = document.querySelector('.text-2');
    const progressBar = document.querySelector('.progress');
    let iconClass, titleText;
    switch (type) {
        case 'success':
            iconClass = 'fas fa-check-circle success';
            titleText = 'Éxito';
            break;
        case 'error':
            iconClass = 'fas fa-times-circle danger';
            titleText = 'Error';
            break;
        case 'info':
            iconClass = 'fas fa-info-circle info';
            titleText = 'Información';
            break;
        case 'warning':
            iconClass = 'fas fa-exclamation-triangle warning';
            titleText = 'Advertencia';
            break;
        default:
            iconClass = 'fas fa-info-circle info';
            titleText = 'Mensaje';
    }
    overlay.style.visibility = 'visible';
    toastIcon.className = iconClass;
    toastTitle.textContent = titleText;
    toastMessage.textContent = message;
    toast.classList.add("active");
    progressBar.classList.add("active");
    progressBar.style.transition = `width ${duration}ms linear`;
    progressBar.style.width = '100%';
    setTimeout(() => {
        toast.classList.remove("active");
        progressBar.classList.remove("active");
        progressBar.style.width = '0%';

        setTimeout(() => {
            if (messageQueue.length === 0) overlay.style.visibility = 'hidden';
        }, 500);
    }, duration);
}
/**
 * Función mejorada de myFetch para hacer peticiones a un backend.
 */
/* async function myFetch(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data + form_input('fapp')
        });
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Error ${response.status}: ${errorText}`);
        }
        const contentType = response.headers.get('content-type');
        let responseData = contentType && contentType.includes('application/json')
            ? await response.json()
            : await response.text();
        return responseData;
    } catch (error) {
        console.error('Error en la petición:', error);
        enqueueMessage('error', `Error en la petición: ${error.message}`, 7000);
        return null;
    }
} */

	async function myFetch(url, data) {
		try {
			const csrfToken = document.querySelector('input[name="csrf_tkn"]').value; // Obtener el token CSRF
			const response = await fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data + "&csrf_tkn=" + csrfToken + form_input('fapp')
			});
			console.log('Enviando datos:', data, 'Respuesta completa:', response);
			const responseText = await response.text();
			console.log('Texto de respuesta:', responseText);
			if (responseText.trim().startsWith('<')) {
				console.error('Error: El servidor devolvió HTML en lugar de JSON.');
				throw new Error('El servidor no devolvió un JSON válido.');
			}
			const responseData = JSON.parse(responseText);
			if (responseData.csrf_tkn) {
				updateCsrfToken(responseData.csrf_tkn);
			}
			return responseData;
		} catch (error) {
			console.error('Error en la petición:', error);
			enqueueMessage('error', `Error en la petición: ${typeErrors(error.message)}`, 7000);
			return null;
		}
	}
	
function typeErrors(rta) {
	console.error("Error del servidor:", rta);
	let errorMessage = "Error al realizar la tarea, intenta nuevamente"; // Mensaje por defecto
	switch (true) {
		case rta.includes('Duplicate entry'):
			errorMessage = 'El elemento a guardar ya existe.';
			break;
		case rta.includes('SQL syntax'):
			errorMessage = 'Error de Sintaxis en el SQL.';
			break;
		case rta.includes('Access denied for user'):
			errorMessage = 'Acceso Denegado, valida la cadena de conexión a la BD.';
			break;
		case rta.includes('Too many connections'):
			errorMessage = 'Ha alcanzado temporalmente el límite de conexiones, número alto de usuarios.';
			break;
		case rta.includes('Out of memory'):
			errorMessage = 'No tiene suficiente memoria para almacenar el resultado completo.';
			break;
		case rta.includes('Unknown column'):
			errorMessage = 'Error asociado a una columna inexistente de la tabla.';
			break;
		case rta.includes('Table'):
			errorMessage = 'Error en la sintaxis, asociada a la tabla.';
			break;
		case rta.includes('Failed to fetch'):
			errorMessage = 'Error en la red, valida la conexión a internet.';
			break;
		case rta.includes('Unexpected end of JSON input'):
			errorMessage = 'Error 500 interno del Servidor.';
			break;
		default:
			const errMatch = rta.match(/msj\['(.*?)'\]/);
			if (errMatch && errMatch[1]) {
				errorMessage = errMatch[1]; // Extrae el mensaje del error
			}
	}
	enqueueMessage('error', errorMessage, ERROR_DURATION);
}
/**
 * Función para mostrar la barra de carga.
 */
function showLoadingBar() {
    let loadingBar = document.getElementById('loading-bar');
    if (!loadingBar) {
        // Crear la barra si no existe
        loadingBar = document.createElement('div');
        loadingBar.id = 'loading-bar';
        loadingBar.style.position = 'fixed';
        loadingBar.style.top = '0';
        loadingBar.style.left = '0';
        loadingBar.style.width = '0%';
        loadingBar.style.height = '4px';
        loadingBar.style.backgroundColor = '#007bff';
        loadingBar.style.transition = 'width 0.4s ease-in-out';
        document.body.appendChild(loadingBar);
    }
    loadingBar.style.width = '0%';
    loadingBar.style.visibility = 'visible';
    // Simulación de carga progresiva
    let progress = 0;
    const interval = setInterval(() => {
        if (progress < 90) { // No llega al 100% hasta que termine la petición
            progress += 10;
            loadingBar.style.width = progress + '%';
        }
    }, 300);
    return interval; // Retorna el intervalo para poder detenerlo después
}
/**
 * Función para ocultar la barra de carga.
 */
function hideLoadingBar(interval) {
    clearInterval(interval);
    const loadingBar = document.getElementById('loading-bar');
    if (loadingBar) {
        loadingBar.style.width = '100%'; // Llenar la barra antes de ocultarla
        setTimeout(() => {
            loadingBar.style.visibility = 'hidden';
            loadingBar.style.width = '0%';
        }, 500);
    }
}
		async function pFetch(path, data, callback, method = "POST", headers = {}) {
			const loadingInterval = showLoadingBar(); // Mostrar la barra de carga
			try {
				const csrfToken = document.querySelector('input[name="csrf_tkn"]').value;
				const response = await fetch(path, {
					method,
					headers: { ...headers },
					body: data+'&csrf_tkn='+csrfToken,
					// body: data,
					credentials: 'same-origin'
				});
				if (response.status === 401) {
					window.location.href = '/';
					const responseData = await response.json();
					// window.location.href = responseData.redirect;
					return;
				}
				if (response.url === 'https://' + window.location.hostname + '/index.php') {
					window.location.href = '/';
					return;
				}
				if (response.status === 500) {
					enqueueMessage('error','Error interno del servidor', ERROR_DURATION);
					return;
				}
				if (response.status === 403) {
					enqueueMessage('error','Token CSRF inválido.', ERROR_DURATION);
					return;
				}
				if (!response.ok) {
					const errorText = await response.text();
					throw new Error(`Error ${response.status}: ${errorText}`);
				}
				const contentType = response.headers.get("Content-Type");
				let responseData;
				if (contentType && contentType.includes("application/json")) {
					responseData = await response.json();
					if (responseData.csrf_tkn) {
						updCsrfTkn(responseData.csrf_tkn);
					}
				} else {
					responseData = await response.text();
				}
				if (callback) {
					callback(responseData);
				}
				//  showToast('success', 'Operación completada.'); // Muestra un toast de éxito
				return responseData;
			} catch (error) {
				console.error("Error en pFetch:", error);
				enqueueMessage('error', ` ${error.message}`, ERROR_DURATION);
			}finally {
				hideLoadingBar(loadingInterval); // Ocultar la barra de carga al finalizar
			}
		}

  async function getJSON(action, table, id,url=ruta_app,customHeaders = {}) {
	if (loader?.style) loader.style.display = "block";
	const headers = {
	  "Content-type": "application/x-www-form-urlencoded",
	  ...customHeaders,
	};
	const body = `a=${action}&tb=${table}&id=${id}`;
	let rawData;
	try {
	  const response = await fetch(url, { method: "POST", headers, body });
	  if (!response.ok) {
		rawData = await response.text();
		throw new Error(`Network response was not ok: ${response.status} - ${response.statusText}`);
	  }
  
	  const rawData = await response.text(); // Obtén el contenido de la respuesta como texto
	  console.error(`Response: ${rawData}`);

	  const data = JSON.parse(rawData);
  
	  if (loader?.style) loader.style.display = "none";
	  return data;
	} catch (error) {
	  console.error(error+rawData);
	  	if (rawData) {
      		console.error(`Error Response: ${rawData}`);
    	}
	  handleRequestError(error.message);
	}
  }
  
  function handleRequestError(error) {
	if (loader?.style) loader.style.display = 'none';
	typeErrors(error.errorCode);
	console.error(error); // Cambia console.log por console.error
	errors('Error al realizar la solicitud');
  }

function getDatForm(clsKey, fun,clsCmp) {
	const c = document.querySelectorAll(`.${clsKey} input, .${clsKey} select`);
	let id = '';
		for (let i = 0; i < c.length; i++) {
		  const {value} = c[i];
		  if (value === '') {
				break;
		  }
		  id += `${value}_`;
		}
		if (id===''){
		  return false;
		}else{
			id = id.slice(0, -1);
				getJSON('get', fun, id)
				  .then(data => {
					if (Object.keys(data).length === 0) {
						inform('No se encontraron registros asociados');
						return;
					  }
					  var data=Object.values(data);
					  var cmp=document.querySelectorAll(`.${clsCmp} input ,.${clsCmp} select`);
					  for (i=1;i<cmp.length;i++) {
						  if(cmp[i].type==='checkbox')cmp[i].checked=false;
							  if (cmp[i].value=='SI' && cmp[i].type==='checkbox'){
								  cmp[i].checked=true;
							  }else if(cmp[i].value!='SI' && cmp[i].type==='checkbox'){
								  cmp[i].value='NO';
							  }
							  // key += value !== '' ? value + '_' : '';
							  cmp[i].value=i==0?data[i-1]:data[i];
							  for (x=0;x<c.length;x++) {
								  if(cmp[i].name==c[x]) cmp[i].disabled = true;
							  }
					  }
				  })
			  .catch(handleRequestError);
	  }	
  }

  function getDaTab(clsKey, fun,clsCmp) {
	const c = document.querySelectorAll(`.${clsKey} input, .${clsKey} select`);
	let id = '';
		for (let i = 0; i < c.length; i++) {
		  const {value} = c[i];
		  if (value === '') {
				break;
		  }
		  id += `${value}_`;
		}
		if (id===''){
		  return false;
		}else{
			id = id.slice(0, -1);
				getJSON('get', fun, id)
				  .then(data => {
					if (Object.keys(data).length === 0) {
						inform('No se encontraron registros asociados');
						return;
					  }
					  var data=Object.values(data);
					  var cmp=document.querySelectorAll(`.${clsCmp} input ,.${clsCmp} select`);
					  for (i=1;i<cmp.length;i++) {
						  if(cmp[i].type==='checkbox')cmp[i].checked=false;
							  if (cmp[i].value=='SI' && cmp[i].type==='checkbox'){
								  cmp[i].checked=true;
							  }else if(cmp[i].value!='SI' && cmp[i].type==='checkbox'){
								  cmp[i].value='NO';
							  }
							  // key += value !== '' ? value + '_' : '';
							  cmp[i].value=i==0?data[i-1]:data[i];
							  for (x=0;x<c.length;x++) {
								  if(cmp[i].name==c[x]) cmp[i].disabled = true;
							  }
					  }
				  })
			  .catch(handleRequestError);
	  }	
  }

  function validDate(a,b,c){
	let Ini=dateAdd(b);
	let Fin=dateAdd(c);
	
	let min=`${Ini.a}-${Ini.m}-${Ini.d}`;
	let max=`${Fin.a}-${Fin.m}-${Fin.d}`;
	
	RangeDateTime(a.id,min,max);
  }
  

//++++++++++++++++++++++++++++++++APARIENCIA++++++++++++++++++++++
/* function collapse(a) {
	const el=document.getElementById(a.id);
	if (el.classList.contains('collapsible')){
		var coll = document.getElementsByClassName('collapsible');
		var i;
		for (i = 0; i < coll.length; i++) {
    		coll[i].classList.toggle("active");
    		var content = coll[i].nextElementSibling;
    		if (content.style.maxHeight){
      			content.style.maxHeight = null;
    		} else {
      			content.style.maxHeight = content.scrollHeight + "px";
    		}
		}
	}else{
		return;
	}
}
 */
function hideFix(a,b){
	const panel=document.getElementById(a+'-'+b);
	if (panel!=undefined) panel.style.display='none';
}

//++++++++++++++++++++++++++++++++Activar e Inactivar Elementos++++++++++++++++++++++
function enaFie(ele, flag) {
	if(ele.type==='checkbox' && ele.checked==true){
		ele.checked=false;
	}else if(ele.type==='checkbox'){
		ele.value = 'NO';
	}else{
		ele.value = '';
	}
    ele.disabled = flag;
    ele.required = !flag;
    ele.classList.toggle('valido', !flag);
    ele.classList.toggle('captura', !flag);
    ele.classList.toggle('bloqueo', flag);
    flag ? ele.setAttribute('readonly', true) : ele.removeAttribute('readonly');
}

function noRequired(ele,flag){
	ele.required = !flag;
	ele.classList.toggle('valido', !flag);
}


function hidFie(ele,flag){
	switch (ele.nodeName) {
		case 'SELECT':
			ele.required = !flag;
    		ele.classList.toggle('valido', !flag);
    		ele.classList.toggle('captura', !flag);
			ele.classList.toggle('oculto', flag);
			ele.classList.toggle('bloqueo', flag);
			if(!flag){
				ele.disabled = flag;
				ele.setAttribute('readonly', true); 
			}else{
				ele.disabled = !flag;
				ele.disabled = !flag;
				ele.removeAttribute('readonly')
			}
			if (flag==true)ele.value='';
			break;
		case 'INPUT':
			ele.required = !flag;
    		ele.classList.toggle('valido', !flag);
    		ele.classList.toggle('captura', !flag);
			ele.classList.toggle('oculto', flag);
			ele.classList.toggle('bloqueo', flag);
			if(!flag){
				ele.disabled = flag;
				ele.removeAttribute('readonly')
			}else{
				ele.disabled = !flag;
				ele.setAttribute('readonly', true); 
			}
			if (flag==true)ele.value='';
			break;
		case 'TEXTAREA':
			ele.required = !flag;
			ele.classList.toggle('valido', !flag);
			ele.classList.toggle('captura', !flag);
			ele.classList.toggle('oculto', flag);
			ele.classList.toggle('bloqueo', flag);
			if(!flag){
				ele.disabled = flag;
				ele.removeAttribute('readonly')
			}else{
				ele.disabled = !flag;
				ele.setAttribute('readonly', true); 
			}
			if (flag==true)ele.value='';
			break;
		case 'INPUT':
			ele.required = !flag;
    		ele.classList.toggle('valido', !flag);
    		ele.classList.toggle('captura', !flag);
			ele.classList.toggle('oculto', flag);
			ele.classList.toggle('bloqueo', flag);
			if(!flag){
				ele.removeAttribute('readonly')
				ele.setAttribute('readonly', true); 
			}else{
				ele.disabled = flag;
			}
			if (flag==true && ele.type=='checkbox') {
				ele.value='NO';
				ele.checked=!flag;
			}else if(flag==false && ele.type=='checkbox' && ele.checked==false){
				ele.value='NO';
			}
			if (flag==true && ele.type!='checkbox')ele.value='';
			break;
		default:
		ele.classList.toggle('oculto', flag);
			break;
	}
}

function lockeds(ele,flag) {
    ele.readOnly = flag === true;
    ele.classList.toggle('bloqueo', flag === true);
    ele.disabled = flag === true;
}


function hidLabFie(ele,flag){
	switch (ele.nodeName) {
		case 'SELECT':
			ele.required = !flag;
    		ele.classList.toggle('valido', !flag);
    		ele.classList.toggle('captura', !flag);
			ele.classList.toggle('oculto', flag);
			if (flag==true)ele.value='';
			break;
		case 'INPUT':
			ele.required = !flag;
    		ele.classList.toggle('valido', !flag);
    		ele.classList.toggle('captura', !flag);
			ele.classList.toggle('oculto', flag);
			if (flag==true && ele.type=='checkbox') {
				ele.value='NO';
				ele.checked=!flag;
			}else if(flag==false && ele.type=='checkbox' && ele.checked==false){
				ele.value='NO';
			}
			if (flag==true && ele.type!='checkbox')ele.value='';
			break;
		default:
		ele.classList.toggle('oculto', flag);
			break;
	}
}

/*   function Color(a) {
	var div = document.getElementById(a);
	var tabla = div.querySelector('table');
  
	tabla.addEventListener('click', function(event) {
	  var td = event.target.closest('td');
  
	  if (td !== null && td.parentElement !== null) {
		cambiarColorFila(div, td);//td.parentElement   .firstChild.parentNode
	  }
	});Enab
  
	var filaSeleccionada = null;
  
	function cambiarColorFila(div, fila) {
	  if (filaSeleccionada !== null) {
		filaSeleccionada.style.backgroundColor = '';
	  }
  
	  fila.style.backgroundColor = '#fbeb4dc4';
	  filaSeleccionada = fila;
	}
  }
 */
  
  
  
  
//++++++++++++++++++++++++++++++++Validar fechas Minimos y maximos++++++++++++++++++++++
function dateAdd(d=0,m=0,y=0,H=0,M=0,S=0){
	var now=new Date();
	now.setDate(now.getDate()+d)
	now.setMonth(now.getMonth() + m)
	now.setFullYear(now.getFullYear()+y);
	now.setHours(now.getHours()+H-5);
	now.setMinutes(now.getMinutes()+M);
	now.setSeconds(now.getSeconds()+S);
	
	let days= now.toISOString().slice(8,10);
	let mont= now.toISOString().slice(5,7);
	let year= now.toISOString().slice(0,4);
	let hour= now.toISOString().slice(11,13);
	let minu= now.toISOString().slice(14,16);
	let seco= now.toISOString().slice(17,19);
    
	return { 'd': days,
             'm': mont,
             'a': year,
             'H': hour,
             'M': minu, 
             'S': seco
          };
}

function RangeDateTime(a,b,c){
	d = document.getElementById(a);
	d.min=b;
	d.max=c;
}

  async function DownloadCsv(a,b,c) {
	try {
		const data = await getJSON(a,b,form_input(c));
		csv(data['file']);
	} catch (error) {
	  console.error(error);
	  errors('ya se realizo la descarga el dia de hoy intentalo mañana nuevamente.');
	}
  }

  function uploadCsv(ncol, tab, archivo, ruta, mod) {
	if (archivo.files.length > 0) {
	  const loader = document.getElementById('loader');
	  if (loader != undefined) loader.style.display = 'block';
  
	  const formData = new FormData();
	  formData.append("ncol", ncol);
	  formData.append("tab", tab);
	  formData.append("archivo", archivo.files[0]);
  
	  let data = null; // Inicializa la variable data
  
	  fetch(ruta, {
		method: "POST",
		body: formData,
	  })
		.then((response) => {
		  if (response.ok) {
			return response.text();
		  } else {
			if (loader != undefined) loader.style.display = 'none';
			throw new Error("Network response was not ok");
		  }
		})
		.then((responseData) => {
		  data = responseData; // Almacena la respuesta en la variable data
		  const response = JSON.parse(data);
		  const type = response.type;
		  const msj = response.msj;
		  if (loader != undefined) loader.style.display = 'none';
		  if (type == 'Error') {
			errors(msj);
		  } else if (type == 'OK') {
			ok(msj);
		  } else {
			warnin(msj);
		  }
		  act_lista(mod);
		})
		.catch((error) => {
		  console.error(error + '=' + data); // Muestra el valor de data junto con el error
		  errors('Ha ocurrido un error al procesar la solicitud');
		});
	} else {
	  warnin('Selecciona un archivo válido');
	}
  }
  

  function hidFieOpt(act,clsCmp,x,valid) {
	const cmpAct=document.getElementById(act);
	const cmps = document.querySelectorAll(`.${clsCmp}`);
	if(cmpAct.value=='SI'){
		for(i=0;i<cmps.length;i++){
			hidFie(cmps[i],!valid);
		}
	}else{
		for(i=0;i<cmps.length;i++){
			hidFie(cmps[i],valid);
		}
	}
}

/* const all = document.querySelector('body');
const thm = document.getElementById('theme');

if (localStorage.getItem('demo-theme')) {
  const theme = localStorage.getItem('demo-theme');
  all.classList.add(`theme-${theme}`);
}

  thm.addEventListener('change', e => {
    let colour = thm.value;
    all.className = '';
    all.classList.add(`theme-${colour}`);
    localStorage.setItem('demo-theme', colour);
  }); */

  function ShowCells(a) {
	const tbody = document.querySelectorAll('tbody#'+a);
	if (tbody[0]!==undefined){
		var rows = tbody[0].querySelectorAll('tr');
	rows.forEach(function (row) {
		row.classList.add('closed');
	});
	rows.forEach(function (row) {
		row.addEventListener('click', function () {
			if (!row.classList.contains('closed') && row.parentNode.tagName === 'TBODY') {
				row.classList.add('closed');
			} else {
				rows.forEach(function (r) {
					r.classList.add('closed');
				});
				row.classList.remove('closed');
			}
		});
	});
	}
}

function countFilter(a) {
    // Seleccionamos el contenedor con id 'deriva-fil'
    const contenedor = document.getElementById(a+'-fil');
    // Seleccionamos todos los inputs y selects dentro del contenedor
    const inputs = contenedor.querySelectorAll('input, select');
    let contador = 0;
    // Iteramos sobre todos los inputs y selects
    inputs.forEach(elemento => {
        // Verificamos si el elemento es un checkbox
        if (elemento.type === 'checkbox') {
            // Contamos si el checkbox está marcado
            if (elemento.checked) {
                contador++;
            }
        } else {
            // Para inputs normales o selects, verificamos si tienen un valor
            if (elemento.value.trim() !== '') {
                contador++;
            }
        }
    });
    return contador;
}

function badgeFilter(x) {
    const conta = countFilter(x);
    const spanCont = document.getElementById('fil-badge');
    const labelFiltros = document.querySelector('label.filtros');
    if (conta === 0) {
        if (spanCont) {
            spanCont.remove();
        }
    } else {
        if (!spanCont) {
            const nuevoSpan = document.createElement('span');
            nuevoSpan.id = 'fil-badge';
            nuevoSpan.className = 'badge badge-pill badge-warning';
            nuevoSpan.textContent = `${conta}`;
            if (labelFiltros) {
                labelFiltros.appendChild(nuevoSpan);
            }
        } else {
            spanCont.textContent = `${conta}`;
        }
    }
}

 
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar celdas en tabla de datos responsive
    
/******************START SELECTS MULT ONLY***********************/
    const multipleRemoveSelects = document.getElementsByClassName('choices-multiple-remove-button');
    for (let i = 0; i < multipleRemoveSelects.length; i++) {
        const multipleRemoveChoices = new Choices(multipleRemoveSelects[i], {
            allowHTML: true,
            removeItemButton: true,
            loadingText: 'Cargando...',
            noResultsText: 'No hay resultados',
            noChoicesText: 'No hay más opciones',
        });
    }

    // select para una sola opción
    const singleSelects = document.getElementsByClassName('single-select');
    for (let i = 0; i < singleSelects.length; i++) {
        const choices = new Choices(singleSelects[i], {
            allowHTML: true,
            searchEnabled: true,
            itemSelectText: 'Selecciona una opción',
            position: 'bottom',
            loadingText: 'Cargando...',
            noResultsText: 'No hay resultados',
            noChoicesText: 'No hay una opcion',
        });
    }

/******************END SELECTS MULT ONLY***********************/
/******************START BOTONS***********************/
// Seleccionamos todos los botones que tienen la clase "add-btn"
const btnNew = document.querySelectorAll('.add-btn');
// Iteramos sobre los botones y les agregamos el evento 'click'
btnNew.forEach(boton => {
    boton.addEventListener('click', function() {
        // Obtenemos los valores de los atributos data-param1 y data-param2
        const wind = this.getAttribute('data-mod');
        // Llamamos a la función con los parámetros obtenidos
        mostrar(wind,'pro');
    });
});

const btnUpd = document.querySelectorAll('.act-btn');
btnUpd.forEach(boton => {
    boton.addEventListener('click', function() {
        const lisTable = this.getAttribute('data-mod');
		act_lista(act_lista(mod))
    });
});

const btnExp = document.querySelectorAll('.export-btn');
	btnExp.forEach(boton => {
		boton.addEventListener('click', function() {
	        const modul = this.getAttribute('data-mod');
			const csrfToken = document.querySelector('input[name="csrf_tkn"]').value; 
			const formData = new FormData();
			formData.append('a', 'exp');
			formData.append('tb', modul);
			formData.append('csrf_tkn', csrfToken);
			for(var pair of formData.entries()) {
				console.log(pair[0]+ ', '+ pair[1]);
			  }
			fetch('lib.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.text())
			.then(html => {
				document.getElementById(modul+'modalContainer').innerHTML = html;
				document.getElementById(modul+'modal').style.display = 'block';
			})
			.catch(error => {
				error.error('Error al obtener los datos: ', error);
        		statusMessage.textContent = 'Error al procesar la solicitud.';
			});
	    });
	});

/******************START BOTONS***********************/
/******************START IMPORT***********************/
const modal = document.getElementById('modal'),
	openModalBtn = document.getElementById('openModal'),
	cancelLoadingBtn = document.getElementById('cancelLoading'),
	closeModalBtn = document.getElementById('closeModal'),
	progressBar = document.getElementById('progressBar'),
	progressText = document.getElementById('progressText'),
	statusMessage = document.getElementById('statusMessage'),
	fileName = document.getElementById('file-name'),
	fileInput = document.getElementById('fileInput');

	let loading = false,progress = 0;
	

fileInput.addEventListener('change', (event) => {
    const files = event.target.files;
    showFileName(files);
});

const showFileName = (files) => {
    if (files.length) {
        fileName.textContent = `Archivo : ${files[0].name}`;
    } else {
        fileName.textContent = 'Selecciona un archivo aquí';
    }
};

function cancelLoading() {
	loading = false;
	resetProgress();
	statusMessage.textContent = 'Carga cancelada por el usuario.';
}
function resetProgress() {
	progress = 0;
	updateProgress(progress);
	fileInput.value = '';
	progressBar.style.width=0;
	progressText.textContent='0% Completado'
	// startLoadingBtn.style.display = 'inline-block';
	cancelLoadingBtn.style.display = 'none';
	closeModalBtn.style.display = 'none';
}



openModalBtn.onclick = () => {
	modal.style.display = "block";
    closeModalBtn.style.display = "block";
};

closeModalBtn.onclick = () => {
	
	fileName.textContent='Selecciona un archivo aquí';
	modal.style.display = "none";
    statusMessage.textContent ='';
    resetProgress();
};

cancelLoadingBtn.onclick = cancelLoading;

const observer = new MutationObserver(() => {
	if (statusMessage.textContent.trim() !== "") {
		statusMessage.classList.add('has-cont');
	} else {
		statusMessage.classList.remove('has-cont');
	}
});

observer.observe(statusMessage, { childList: true, subtree: true });

});

function startImport(file,ncol,tab,imp) {
	const formData = new FormData();
	formData.append('archivo', file);
	formData.append('ncol', ncol);
	formData.append('tab', tab);

	fetch(imp, {
		method: 'POST',
		body: formData
	})
	.then(response => response.body)
	.then(body => {
		const reader = body.getReader();
		const decoder = new TextDecoder("utf-8");
		let buffer = '';

		function processText({ done, value }) {
			if (done) {
				console.log("Carga completada.");
				return;
			}

			buffer += decoder.decode(value, { stream: true });
			let parts = buffer.split('\n');
			buffer = parts.pop();
			const errorsAll = []; 
			let endInd = parts.length - 1;
			parts.forEach((part, index) => {
				if (part.trim()) {
					try {
						const json = JSON.parse(part);
						console.log('JSON recibido:', json);
						handleServerResponse(json, errorsAll, index, endInd);
					} catch (error) {
						console.error("Error al procesar JSON:", error, part);
					}
				}
			});
			reader.read().then(processText);
		}
		reader.read().then(processText);
	})
	.catch(error => {
		console.error('Error:', error);
		statusMessage.textContent = `Ocurrió un error: ${error.message}`;
	});
}

function handleServerResponse(json, errorsAll, index, endInd) {
	if (json.status === 'progress') {
		updateProgress(json.progress);
		statusMessage.textContent = json.errors;
		if (json.errors) errorsAll.push(json.errors);

	} else if (json.status === 'success') {
		updateProgress(json.progress);
		statusMessage.textContent = json.message;
		if (json.errors) errorsAll.push(json.errors);
		// closeModalBtn.style.display = 'inline-block';

	} else if (json.status === 'error') {
		if (json.errors) errorsAll.push(json.errors);
	}

	if (index === endInd) {
		statusMessage.innerHTML += "<br><br>Errores:<br>" + errorsAll.join("<br>");
	}
}

function updateProgress(newProgress) {
    let currentProgress = parseInt(progressBar.style.width) || 0;
    function updateGradually() {
        if (currentProgress < newProgress) {
            currentProgress += 1;
            progressBar.style.width = `${currentProgress}%`;
            progressText.textContent = `${currentProgress}% completado`;
            setTimeout(updateGradually, 50);
        }
    }
    updateGradually();
}

/******************END IMPORT************************/


/******************CREATE BTNS************************/
// Función para crear los botones según los permisos obtenidos
function crearBotones(data,mod) {
	const container = document.getElementById(mod+'-btns');

	// Limpia el contenedor antes de agregar nuevos botones
	container.innerHTML = '';

	// Crear botón si tiene permiso para crear
	if (data.crear === 'SI') {
		const btnCrear = document.createElement('button');
		btnCrear.innerText = 'Crear';
		btnCrear.classList.add('btn', 'btn-crear');
		container.appendChild(btnCrear);
	}

	// Crear botón si tiene permiso para editar
	if (data.editar === 'SI') {
		const btnEditar = document.createElement('button');
		btnEditar.innerText = 'Editar';
		btnEditar.classList.add('btn', 'btn-editar');
		container.appendChild(btnEditar);
	}

	// Crear botón si tiene permiso para consultar
	if (data.consultar === 'SI') {
		const btnConsultar = document.createElement('button');
		btnConsultar.innerText = 'Consultar';
		btnConsultar.classList.add('btn', 'btn-consultar');
		container.appendChild(btnConsultar);
	}

	// Crear botón si tiene permiso para exportar
	if (data.exportar === 'SI') {
		const btnExportar = document.createElement('button');
		btnExportar.innerText = 'Exportar';
		btnExportar.classList.add('btn', 'btn-exportar');
		container.appendChild(btnExportar);
	}

	// Crear botón si tiene permiso para importar
	if (data.importar === 'SI') {
		const btnImportar = document.createElement('button');
		btnImportar.innerText = 'Importar';
		btnImportar.classList.add('btn', 'btn-importar');
		container.appendChild(btnImportar);
	}
}
/******************END BTNS************************/

/******************CREATE TKNS CSRF************************/
function updCsrfTkn(newToken) {
    window.csrfToken = newToken;
    const csrfInput = document.querySelector('input[name="csrf_tkn"]');
    if (csrfInput) {
        csrfInput.value = newToken;
    }
}
/******************CREATE TKNS CSRF************************/