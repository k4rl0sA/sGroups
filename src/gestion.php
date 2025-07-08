<?php
require_once __DIR__ . '/../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
//Funcion para cerrar la sesion
function usuSess(){
  return $usu = isset($_SESSION['documento']) ? $_SESSION['documento'] : 'Usuario Desconocido';
}
//Funcion conectar a BD
function db_connect() {
  $con = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
  if ($con->connect_error) {
      log_error(' = Error 3: conexión Fallida');
      throw new Exception('Error en la conexión a la base de datos: ' . $con->connect_error);
  }
  return $con;
}
//Función verifica usuario logueado
function verificarUsuario($usuario, $password) {
  $conn = db_connect();
    $stmt = $conn->prepare("SELECT id_usuario,nombre,clave  FROM usuarios WHERE id_usuario = ? AND estado ='1'");
    if (!$stmt) {
      log_error(' = Error 4: al preparar la Consulta');
      throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }
    $user = filter_var($usuario, FILTER_SANITIZE_NUMBER_INT);
    $stmt->bind_param("i", $user);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($documento,$nombre,$clave);
        $stmt->fetch();
        if (password_verify($password, $clave)) {
          $_SESSION['nombre'] = $nombre;
          $_SESSION['documento'] = $documento;
          return true;
      }    
    }
    return false;
}
// Función para limpiar entradas
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
// Función para registrar errores
function log_error($message) {
  $timestamp = date('Y-m-d H:i:s');
  $marca = date('Y-m-d H:i:s', strtotime('-5 hours')); 
  $logMessage = "[$marca] - ".usuSess()." = $message" . PHP_EOL;
  try {
      file_put_contents(__DIR__ . '/../errors.log', $logMessage, FILE_APPEND);
  } catch (Throwable $e) {
      file_put_contents(__DIR__ . '/../errors_backup.log', "[$marca] Error al registrar: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
  }
}
//Funcion para crear elementos btn de accion, basado en una consulta por rol
function acceBtns($a) {
  $rta = [];
  $perfil = obtenerPerfil($_SESSION['documento']);
  $componente = obtenerComponente($_SESSION['documento']);
  if ($perfil !== null && $componente !== null) {
      $sql = "SELECT perfil, area, crear, editar, consultar, exportar, importar FROM adm_roles WHERE modulo = ? AND perfil = ? AND area = ? AND estado = 'A'";
      $params = [$a, $perfil, $componente];$types = "sss";
      $data = exec_sql($sql, $params, $types);
      if ($data !== null && isset($data[0])) { 
          $rta = $data[0];
      } else {
          log_error("acceBtns: No se encontraron datos para el modulo: ".$a." Perfil: ".$perfil." area: ".$componente." SQL: ".$sql);
      }
  } else {
      log_error("acceBtns: No se encontraron perfil o area.");
  }
  return $rta;
}
function perfil($a){
	$perf=rol($a);
	if (empty($perf['perfil']) || $perf['perfil'] === array()){
    if(!$_SESSION['documento']){
      log_error(' = Error 9: Inicio de la función perfil con parámetro: ' . $a);
      http_response_code(401);
    exit();
    }
		echo '<div class="lock">
          <i class="fas fa-lock fa-5x lock-icon"></i>
          <h2>Acceso No Autorizado</h2>
          Lo siento, no tienes permiso para acceder a esta área.
          '.$a.'</div>';
          exit();
		 }
}
function rol($a) {
  $rta = [];
  $documento = usuSess();
  if ($documento !== null) {
      $perfil = obtenerPerfil($documento);
      $componente = obtenerComponente($documento);
      if ($perfil !== null && $componente !== null) {
          $sql = "SELECT perfil, area, crear, editar, consultar, exportar, importar FROM adm_roles WHERE modulo = ? AND perfil = ? AND area = ? AND estado = 'A'";
          $params = [$a, $perfil, $componente];$types = "sss";
          $data = exec_sql($sql, $params, $types); 
          if ($data !== null && isset($data[0])) { // Verificar si $data no es null y tiene al menos un elemento
              $rta = $data[0];
          } else {
            if(!$_SESSION['documento']){
              log_error("rol: No se encontraron datos para el modulo: ".$a." Perfil: ".$perfil." area: ".$componente." SQL: ".$sql);
              http_response_code(401);
            exit();
            }
          }
      } else {
          log_error('rol: No se encontró perfil o area para el usuario: ' . $documento . " Perfil: " . var_export($perfil,true) . " area: " . var_export($componente,true));
      }
  } else {
      log_error('rol: Intento de acceder a rol sin documento de usuario: ' . $a);
  }
  return $rta;
}

function obtenerPerfil($documento) {
  $sql = "SELECT perfil FROM `usuarios`  WHERE id_usuario = ?";
  $params = [$documento];
  $types = "i";
  return exec_sql($sql, $params, $types, false);
}
function obtenerComponente($documento) {
  $sql = "SELECT area
        FROM usuarios
        WHERE id_usuario = ? AND estado = '1'";
  $params = [$documento];
  $types = "i";
  return exec_sql($sql, $params, $types, false);
}
function obtenerMenu($usuario) {
  $conn = db_connect();
  $stmt = $conn->prepare("SELECT m.id, m.link, m.icono, m.enlace, m.menu, m.contenedor FROM adm_menu m 
                          JOIN adm_menuusuarios mu ON m.id = mu.idmenu 
                          JOIN usuarios u ON mu.perfil = u.perfil 
                          WHERE u.id_usuario = ? AND m.estado = 'A' AND u.estado = '1' ORDER BY m.id ASC");
  $stmt->bind_param("s", $usuario);
  $stmt->execute();
  $result = $stmt->get_result();
  $menu = [];
  while ($row = $result->fetch_assoc()) {
      $menu[] = $row;
  }
  return construirMenuJerarquico($menu);
}
function construirMenuJerarquico($menuItems, $menuPadre = 0) {
  $menu = [];
  foreach ($menuItems as $item) {
      if ($item['menu'] == $menuPadre) {
          $submenu = construirMenuJerarquico($menuItems, $item['id']);
          if ($submenu) {
              $item['submenu'] = $submenu;
          }
          $menu[] = $item;
      }
  }
  return $menu;
}
//Funcion para crear elementos option de un desplegable no por BD0
function opc_arr($a = [], $b = "", $c = true) {
  $rta = "<option value='' class='alerta'>SELECCIONE</option>";
  if (!empty($a)) { // Usar !empty() para verificar si el array no está vacío
      foreach ($a as $item) {
          $value = is_array($item) && isset($item['v']) ? $item['v'] : (is_string($item) || is_numeric($item) ? $item : null);
          $label = is_array($item) && isset($item['l']) ? $item['l'] : (is_string($item) || is_numeric($item) ? $item : null);
          if ($value !== null && $label !== null) {
              $selected = (strtoupper($value) == strtoupper($b) || strtoupper($label) == strtoupper($b)) ? " selected" : "";
              $disabled = ($c === false) ? " disabled" : "";
              $rta .= "<option value='" . $value . "'$selected$disabled>" . $label . "</option>\n";
          }
      }
  }
  return $rta;
}
//Funcion para crear elementos option basado en un consulta a BD
function opc_sql($sql, $val = null, $id_column = null, $descripcion_column = null) {
  $rta = "<option value='' class='alerta'>SELECCIONE</option>";
  $data = exec_sql($sql, [], "", true);
  if ($data === null) {
      log_error("opc_sql: Error al ejecutar la consulta: " . $sql);
      return $rta;
  }
  if (empty($data)) {
      log_error("opc_sql: La consulta no devolvió resultados: " . $sql);
      return $rta;
  }
  foreach ($data as $row) {
      // Determinación dinámica de las columnas ID y Descripción
      if ($id_column === null) {
          $keys = array_keys($row);
          $id_column = $keys[0]; // Usar la primera columna como ID por defecto
          $descripcion_column = isset($keys[1]) ? $keys[1] : $id_column; // Usar la segunda o la primera si solo hay una
      }
      $id = isset($row[$id_column]) ? $row[$id_column] : null;
      $descripcion = isset($row[$descripcion_column]) ? $row[$descripcion_column] : null;
      if ($id !== null) { // Solo se requiere que el ID no sea null
          $selected = ($val !== null && strtoupper($id) == strtoupper($val)) ? " selected" : "";
          $rta .= "<option value='" . $id . "'$selected>" . htmlentities($descripcion ?? $id, ENT_QUOTES) . "</option>"; // Mostrar ID si la descripción es null
      } else {
          log_error("opc_sql: Fila con ID NULL en la consulta: " . $sql . ". Fila: " . var_export($row, true));
      }
  }
  return $rta;
}
function si_noexiste($a,$b){
  if (isset($_REQUEST[$a]))
	 return $_REQUEST[$a];
  else
	 return $b;
}
function alinea($a){
  if (is_numeric($a)) return 'txt-right';
  elseif (is_numeric(str_replace(",","",$a))) return 'txt-right';
  elseif (strpos($a,'%')>0) return 'txt-right';
  elseif (strlen($a)<=2) return 'txt-center';
  else return 'txt-left';
}
function divide($a){
	$id=explode("_", $a);
	return ($id);
}
function show_sql($data_query, $params, $types) {
  if (empty($params)) {
      echo "<pre>" . htmlentities($data_query) . "</pre>";
      return;
  }
  $consulta_final = $data_query;
  $param_index = 0;
  for ($i = 0; $i < strlen($types); $i++) {
      $type = $types[$i];
      $param = $params[$param_index];
      if ($type == 's') {
          $valor_escapado = "'" . str_replace("'", "''", $param) . "'";
      } elseif ($type == 'i' || $type == 'd') { // Incluir 'd' para doubles/floats
          $valor_escapado = $param;
      } else {
          $valor_escapado = "'" . str_replace("'", "''", $param) . "'"; // Manejo por defecto como string
      }
      $consulta_final = preg_replace('/\?/', $valor_escapado, $consulta_final, 1);
      $param_index++;
  }
  echo "<pre>".$consulta_final."</pre>";//htmlentities($consulta_final)
}
function fil_where($filtros) {
  $where = "1";
  $params = [];
  $types = "";
  foreach ($filtros as $filtro) {
      if (!isset($filtro['campo']) || !isset($filtro['valor'])) {
          log_error("fil_where: Filtro incompleto.");
          continue;
      }
      $campo = $filtro['campo'];
      $valor = $filtro['valor'];
      $operador = $filtro['operador'] ?? "=";
      // Escapa el nombre del campo (importante para la seguridad)
      $campo = preg_replace('/[^a-zA-Z0-9_.]/', '', $campo);//FALTA .
      if (is_array($valor)) {
          if (!empty($valor)) {
              $placeholders = implode(',', array_fill(0, count($valor), '?'));
              $where .= " AND $campo IN ($placeholders)";
              // Escapa los valores del array ANTES de agregarlos a $params
              $escaped_valor = limpiar_y_escapar_array($valor);
              $params = array_merge($params, $escaped_valor);
              $types .= str_repeat(determinar_tipo_dato($valor[0]), count($valor)); // Tipo de dato del primer elemento
          } else {
              $where .= " AND 0"; // Condición siempre falsa para arrays vacíos
          }
      } elseif ($valor !== null && $valor !== "") {
        if ($operador === 'like') {
          // Si el operador es LIKE, agregamos los caracteres % al valor
          $valor = "%$valor%";
      }
          $where .= " AND $campo $operador ?";
          // Escapa el valor ANTES de agregarlo a $params
          $escaped_valor = limpiar_y_escapar_array($valor);
          $params[] = $escaped_valor;
          $types .= determinar_tipo_dato($valor);
      } else {
          $where .= " AND 0"; // Condición siempre falsa para valores vacíos
      }
  }
  return ['where' => $where, 'params' => $params, 'types' => $types];
}
function determinar_tipo_dato($valor) {
  if (is_int($valor)) {
      return "i"; // Entero
  } elseif (is_float($valor)) {
      return "d"; // Decimal
  } else {
      return "s"; // String (u otro tipo si es necesario)
  }
}
function limpiar_y_escapar_array($array) {
  $conexion = db_connect();
  if (!is_array($array)) {
      return mysqli_real_escape_string($conexion, $array);
  }
  $escaped_array = [];
  foreach ($array as $valor) {
      $escaped_array[] = mysqli_real_escape_string($conexion, $valor);
  }
  return $escaped_array;
}
function exec_sql($sql, $params = [], $types = "", $fetch_all = true) {
  $con = db_connect();
  if (!$con) {
      log_error('exec_sql: Error de conexión a la base de datos.');
      return null;
  }
  $stmt = null;
  $result = null;
  $data = null; // Inicializar $data
  try {
      $con->set_charset('utf8');
      $stmt = $con->prepare($sql);
      if (!$stmt) {
          log_error("exec_sql: Error al preparar la consulta: " . $con->error . " SQL: " . $sql);
          return null;
      }
      // Solo vincular parámetros si se proporcionan
      if (!empty($params) && !empty($types)) {
          $stmt->bind_param($types, ...$params);
      }
      $stmt->execute();
      if ($stmt->errno) {
          log_error("exec_sql: Error en la consulta: " . $stmt->error . " SQL: " . $sql);
          return null;
      }
      $result = $stmt->get_result();
      if ($result) {
          if ($fetch_all) {
              $data = [];
              while ($row = $result->fetch_assoc()) {
                  $data[] = $row;
              }
          } else {
              $row = $result->fetch_row();
              $data = $row[0] ?? null;
          }
      } 
  } catch (mysqli_sql_exception $e) {
      log_error("exec_sql: Excepción en la consulta: " . $e->getMessage());
      echo json_encode(['Error:'.$e->getMessage()]);
      return null;
  } finally {
      if ($result) {
          $result->free_result(); // Liberar $result primero
      }
      if ($stmt) {
          $stmt->close();
      }
      if ($con) {
          $con->close();
      }
  }
  return $data;
}
function obtener_total_registros($sql,$params, $types) {
  $total = exec_sql($sql, $params, $types, false);
  return $total;
}
function obtener_datos_paginados($consulta_base, $where, $params, $types, $offset, $regxPag) {
  $data_query = $consulta_base . " WHERE " . $where . " LIMIT ?, ?";
  $params[] = $offset;
  $params[] = $regxPag;
  $types .= "ii";
  $datos = exec_sql($data_query, $params, $types, true);
  return $datos;
}
function create_table($totalReg, $data_arr, $obj_name, $rp = 20,$mod='lib.php', $no = array('R')) {
  $rta = "";
  $pg = si_noexiste('pag-'.$obj_name, 1);
  $rta .= "<div class='table-contain'><div class='header-tools'><div class='tools'></div></div><table>";
  if (!empty($data_arr)) {
    $np = ceil($totalReg / $rp);
    $ri = ($pg - 1) * $rp;
    $rta .= "<thead><tr>";
    foreach ($data_arr[0] as $key => $cmp) {
        if (!in_array($key, $no)) {
           $rta .= "<th>".$key."</th>";
        }
    }
    $rta .= "</tr></thead id='".$obj_name."_cab'>";
    $rta .= "<tbody id='".$obj_name."_fil'>";
    for ($idx = 0; $idx <= ($ri + $rp); $idx++) {
      if (isset($data_arr[$idx])) {
         $r = $data_arr[$idx];
         $rta .= "<tr class='closed' ".bgcolor($obj_name, $r, "r")." >";
         foreach ($data_arr[0] as $key => $cmp) {
            if (!in_array($key, $no)) {
               $rta .= "<td title='".$key."' class='".alinea($r[$key])."' ".bgcolor($obj_name, $r, "c").">";
               $rta .= formato_dato($obj_name, $key, $r, $key);
               $rta .= "</td>";
            }
         }
         $rta .= "</tr>\n";
      }
    }
    $nc = count($data_arr[0]);
    if ($totalReg != 1) {
      $rta .= "<tr><td class='resumen' colspan=$nc >".pags_table($obj_name, $pg, $np, $totalReg,$mod)."</td></tr>";
    }
  }
  $rta .= "</tbody>";
  $rta .= "</table>";
  return $rta;
}
function pags_table($tb, $pg, $np, $nr,$mod) {
  $np= ($np>$nr) ? ($np-1) : $np;
  $rta = "<nav class='menu'>";
  $rta .= "<li class='fa-solid fa-angles-left' OnClick=\"ir_pag('".$tb."', 1, ".$np.",'".$mod."');\"></li>";
  $rta .= "<li class='fa-solid fa-angle-left'  OnClick=\"ir_pag('".$tb."', $pg-1, ".$np.",'".$mod."');\"></li>";
  $rta .= "<input type='text' class='pagina ".$tb." filtro txt-center' maxlength=10 id='pag-".$tb."' value='".$pg."' 
            Onkeypress=\"return solo_numero(event);\" OnChange=\"ir_pag('".$tb."', this.value, ".$np.",'".$mod."');\">";
  $rta .= "<span> de ".$np." Paginas ";
  $rta .= "<input type='text' class='pagina txt-center' id='rec-".$tb."' value='".$nr."' disabled>"; 
  $rta .= " Registros</span>";
  $rta .= "<li class='fa-solid fa-angle-right' OnClick=\"ir_pag('".$tb."', $pg+1, ".$np.",'".$mod."');\"></li>";
  $rta .= "<li class='fa-solid fa-angles-right' OnClick=\"ir_pag('".$tb."', $np, ".$np.",'".$mod."');\"></li>";
  $rta .= "</div>";
  return $rta;
}
function cleanTxt($val) {
  $val = trim($val);
  $pattern = '/[;\|\/\*><\[\{\]\}\x1F\x7F]/';
  $val = preg_replace('/\s+/', ' ', $val);
  $val = preg_replace($pattern, '', $val);
  $val = str_replace(["\n", "\r", "\t"], '', $val);
  return $val;
}
function saveTxt($val) {
  if (is_null($val)) {
    $val = '';
  } else {
    $val = trim($val);
  }
  $val = trim($val);
  $val = addslashes($val);
  $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
  $pattern = '/[;\|\/\*><\[\{\]\}\x1F\x7F]/';
  $val = preg_replace('/\s+/', ' ', $val);
  $val = preg_replace($pattern,'', $val);
  $val = str_replace(array("\n", "\r", "\t"),'', $val);
  return strtoupper($val);
}
function no_reg(){
return "<div class='no-registros'><h2>No se encontraron registros</h2><p>Intenta ajustar tus filtros o agregar nuevos datos.</p></div>";
}
function arrExp($a){
  $arr=is_array($a) ? $a : [$a];
  return explode(",", implode(",", $arr));
}
function datos_mysql($sql,$resulttype = MYSQLI_ASSOC, $pdbs = false){
  $arr = ['code' => 0, 'message' => '', 'responseResult' => []];
  $con =db_connect();
if (!$con) {
    die(json_encode(['code' => 30, 'message' => 'Connection error']));
}
try {
  $con->set_charset('utf8');
  $rs = $con->query($sql);
  // fetch($con, $rs, $resulttype, $arr);
  if ($rs === TRUE) {
		$arr['responseResult'][] = ['affected_rows' => $con->affected_rows];
	}else {
		if ($rs === FALSE) {
			die(json_encode(['code' => $con->errno, 'message' => $con->error]));
		}
		while ($r = $rs->fetch_array($resulttype)) {
			$arr['responseResult'][] = $r;
		}
		$rs->free();
	}
  return $arr;
} catch (mysqli_sql_exception $e) {
  $code=$e->getCode();
  $msj=$e->getMessage();
  log_error("Error $code : $msj"); // Log del error
  $response['status'] = 'error';
  $response['message'] = "Error ".$e->getCode() ." en la consulta. Por favor, contacte al administrador del sistema. (Error interno: Desajuste de parámetros en Mysql)"; 
  // die(json_encode(['code' => 30, 'message' => 'Error BD', 'errors' => ['code' => $e->getCode(), 'message' => $e->getMessage()]]));
}finally {
 /*  $con->close(); */
}
return $arr;
}
function mysql_prepd($sql, $params) {
  $con = db_connect();
    if (!$con) {
        return ['status' => 'error', 'message' => 'Error al conectar a la base de datos.'];
    }
    $con->set_charset('utf8');
    $response = ['status' => 'success', 'message' => ''];
    try {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $types = '';
            $values = [];
            foreach ($params as $param) {
                $type = ($param['type'] === 'z') ? 's' : (($param['type'] === 's') ? 's' : $param['type']);
                $types .= $type;
                $value = ($param['type'] === 's' || $param['type'] === 'z') ? saveTxt($param['value']) : saveTxt($param['value']); // Simplificado
                $values[] = $value;
            }
            $num_placeholders = substr_count($sql, '?');
            $num_params = count($values);
            if ($num_placeholders !== $num_params) {
                $error_message = "Error: El número de placeholders (?) ($num_placeholders) no coincide con el número de parámetros ($num_params).";
                log_error($error_message); // Log del error
                $response['status'] = 'error';
                $response['message'] = "Error en la consulta. Por favor, contacte al administrador del sistema. (Error interno: Desajuste de parámetros)"; // Mensaje genérico al usuario
                return $response; // Salir inmediatamente en caso de error grave
            }
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            if ($affectedRows > 0) {
                $op = (stripos($sql, 'UPDATE') !== false) ? 'actualizado' : 'insertado'; // Usar stripos para insensibilidad a mayúsculas
                $response['message'] = "Registro $op correctamente. ($affectedRows filas afectadas)"; // Mensaje más informativo
            } else {
                $response['message'] = "No se encontraron registros para actualizar/insertar o no se realizaron cambios."; // Mensaje más claro
            }
        } else {
            $error_message = "Error en la preparación de la consulta: " . $con->error;
            log_error($error_message);
            $response['status'] = 'error';
            $response['message'] = "Error en la consulta. Por favor, contacte al administrador del sistema. (Error interno: Preparación de consulta fallida)";
        }
    } catch (mysqli_sql_exception $e) {
           if ($e->getCode() == 1062) {
            // Error de clave duplicada
            $response['status'] = 'duplicate';
            $response['message'] = "Ya existe un registro con los mismos datos únicos. Por favor, verifique la información.";
            $response['errorCode'] = 1062;
        } else {
            $error_message = "Error en la consulta (mysqli_sql_exception): " . $e->getCode() . " - " . $e->getMessage();
            log_error($error_message);
            $response['status'] = 'error';
            $response['message'] = "Error en la consulta. Por favor, contacte al administrador del sistema. (Error interno: " . $e->getCode() . ")"; // Mensaje con código de error
            $response['errorCode'] = $e->getCode();
        }
    } finally {
        if (isset($con)) { // Verificar si la conexión se estableció antes de intentar cerrarla
            $con->close();
        }
    }
    return $response;
}
function initializeMail(&$mail, $config) {
  $mail->SMTPDebug = 0; // Habilita la salida de depuración (puedes cambiarlo a 0 en producción)
  $mail->isSMTP();
  $mail->CharSet = $config['CharSet'] ?? 'UTF-8';
  $mail->SMTPSecure = $config['SMTPSecure'] ?? MAIL_ENCRYPTION;
  $mail->Host = $config['Host'] ?? MAIL_HOST;
  $mail->Port = $config['Port'] ?? MAIL_PORT;
  $mail->Username = $config['Username'] ?? MAIL_USERNAME;
  $mail->Password = $config['Password'] ?? MAIL_PASSWORD;
  $mail->SMTPAuth = $config['SMTPAuth'] ?? true;
  $mail->isHTML($config['IsHTML'] ?? true);
  $mail->From = $config['From'] ?? 'soporte@controlebeh.site';
  $mail->FromName = $config['FromName'] ?? MAIL_USERNAME;
  $mail->Subject = $config['Subject'] ?? 'Asunto del Correo';
  $mail->AltBody = $config['AltBody'] ?? 'Utilice un lector de mail apropiado!';
}
function sendMail($mails, $subject, $body, $placeholders = [],$plantilla='plantilla'){
  $mail = new PHPMailer(true);
  $response = ['status' => 'success', 'message' => ''];
  try {
      initializeMail($mail, ["Subject" => $subject]);
      foreach ($mails as $email) {
          $mail->addAddress($email);
      }
      // Leer la plantilla HTML
    $plantilla = file_get_contents(__DIR__ . "/PHPMailer/".$plantilla.".html");
      // Reemplazar placeholders en la plantilla
      foreach ($placeholders as $key => $value) {
          $plantilla = str_replace("{" . $key . "}", $value, $plantilla);
      }
      $mail->Body = $plantilla;
      if ($mail->send()) {
        return $response['message'] = "Correo enviado exitosamente.";
      } else {
        $error_message = "Error al enviar el correo. - " . $mail->ErrorInfo;
        log_error($error_message);
        $response['status'] = 'error';
        $response['message'] = "Error al enviar el correo,Por favor valide el correo";
        $response['errorCode'] = $mail->ErrorInfo;
        return $response;
      }
  } catch (Exception $e) {
    $error_message = "Error al enviar el correo. " . $e->getCode() . " - " . $e->getMessage();
        log_error($error_message);
        $response['status'] = 'error';
        $response['message'] = "Error al enviar el correo. Por favor, contacte al administrador del sistema. (Error interno: " . $e->getCode() . ")"; // Mensaje con código de error
        $response['errorCode'] = $e->getCode();
      return $response;
  }
}
function generar_metrica($titulo, $icono, $indicador, $valor) {
  return '<div class="metric-box">
      <div class="left">
          <h3>'.$titulo.'</h3>
          <div class="icon"><i class="'.$icono.'"></i></div>
      </div>
      <div class="right">
          <i class="'.$indicador.'" aria-hidden="true"></i>
          <div class="value">'.$valor.'</div>
      </div>
  </div>';
}
class cmp {
  public $n; //name 1
  public $t; //type 2
  public $s; //size 3
  public $d; //default 4
  public $w; //div class 5
  public $l; //label 6
  public $c; //list/options 7
  public $x; //regexp 8
  public $h; //holder 9
  public $v; //valid 10
  public $u; //update/enabled 11 
  public $tt; //title 12
  public $ww; //width field 13
  public $vc;//Validaciones personalizadas 14
  public $sd;//Select dependientes 15
  public $so;//Validaciones personalizadas otro evento 16s
  public function __construct(
      $n = 'dato', $t = 't', $s = 10, $d = '', $w = 'div', $l = '', $c = '', $x = null, $h = '..',
      $v = true, $u = true, $tt = '', $ww = 'col-10', $vc = false, array $sd = [], $so = false
  ) {
      $this->n = $n;
      $this->t = $t;
      $this->w = $w;
      $this->l = $l ?: $n; // Use null coalescing operator
      $this->c = $c;
      $this->s = $s;
      $this->d = $d;
      $this->x = $x ?? ($t == 'n' ? 'rgxdfnum' : 'rgxtxt'); // Use null coalescing operator
      $this->h = $h;
      $this->v = $v;
      $this->u = $u;
      $this->tt = $tt;
      $this->ww = $ww;
      $this->vc = $vc;
      $this->sd = $sd;
      $this->so = $so;
  }
  public function put() {
      $b = match ($this->t) { // Use match expression for cleaner switch
          's' => input_sel($this),
          'o' => input_opt($this),
          'a' => input_area($this),
          'd' => input_date($this),
          'e' => encabezado($this),
          'l' => subtitulo($this),
          'c' => input_clock($this),
          'm' => select_mult($this),
          'n' => input_num($this),
          'lb' => input_label($this),
          default => input_txt($this),
      };
      return $b . "</div>";
  }
}
// Helper function to sanitize attributes
function saniti($value) {
  return htmlspecialchars($value === null ? '' : $value, ENT_QUOTES, 'UTF-8'); // Manejando valor nulo
}
// Helper function to build common input attributes
function attri($a, $type = 'text') {
  $att = " type='" . saniti($type) . "' id='" . saniti($a->n) . "' name='" . saniti($a->n) . "'";
  $att .= " class='" . saniti($a->w) . " " . ($a->v ? 'valido' : '') . " " . ($a->u ? 'captura' : 'bloqueo') . "'";
  $att .= " title='" . saniti($a->tt) . "'";
  if (!$a->u) $att .= " readonly";
  return $att;
}
function input_txt($a) {
  $rta = "";
  $type = ($a->t == 'h') ? 'hidden' : 'text';
  $value = saniti($a->d);
  $placeholder = saniti($a->h);
  $list = saniti($a->c);
  $pattern = saniti($a->x);
  $maxlength = saniti($a->s);
  $w = saniti($a->w);
  $ww = saniti($a->ww);
  $l = saniti($a->l);
  $vc = saniti($a->vc);
  //Manejo de tipos especiales
  if ($a->t == 'fhms') {$pattern = 'rgxdatehms'; $placeholder = 'YYYY-MM-DD HH:MM:SS'; $maxlength = 19;}
  if ($a->t == 'fhm')  {$pattern = 'rgxdatehm'; $placeholder = 'YYYY-MM-DD HH:MM'; $maxlength = 16;}
  if ($a->t == 'hm')   {$pattern = 'rgxtime'; $placeholder = 'HH:MM'; $maxlength = 5;}
  if ($a->t == 'f')    {$pattern = 'rgxdate'; $placeholder = 'YYYY-MM-DD'; $maxlength = 10;}
  $classExtra = ($a->t == 't' ? '' : ' txt-right');
  if ($a->t != 'h') {
      $rta = "<div class='campo {$w} {$ww} borde1 oscuro'><div>{$l}</div>";
  }
  $rta .= "<input " . attri($a, $type);
  if($maxlength) $rta .= " maxlength='{$maxlength}'";
  if ($pattern) $rta .= " pattern='{$pattern}'";
  $rta .= " class='" . saniti($a->w) . " " . ($a->v ? 'valido' : '') . " " . ($a->u ? 'captura' : 'bloqueo') . $classExtra."'";
  if ($placeholder) $rta .= " placeholder='{$placeholder}'";
  if ($value) $rta .= " value='{$value}'";
  if ($a->t != 'h') {
      $rta .= " required onblur=\"";
      if ($a->v) $rta .= "if(valido(this));";
      if ($pattern) $rta .= "solo_reg(this,{$pattern});";
      if ($vc) $rta .= "{$vc}(this);";
      $rta .= "\"";
  }
  if ($a->t == 'n') $rta .= " onkeypress=\"return solo_numero(event);\"";
  if ($a->t == 'sd') $rta .= " onkeypress=\"return solo_numeroFloat(event);\"";
  if (strpos($a->t, 'f') !== false) $rta .= " onkeypress=\"return solo_fecha(event);\"";
  if ($list) $rta .= " list='lista_{$list}'";
  $rta .= ">";
  if ($a->t != 'h'){
      $rta .= "</div>";
  }
  return $rta;
}
function input_sel($a) {
  $rta = "<div class='campo " . saniti($a->w) . " " . saniti($a->ww) . " borde1 oscuro'><div>" . saniti($a->l) . "</div>";
  $rta .= "<select " . attri($a);
  $rta .= " required onChange=\"";
  if ($a->v) $rta .= "valido(this);";
  if ($a->vc) $rta .= saniti($a->vc) . "(this);"; // Sanitizando la llamada a la función
  $rta .= "\" onblur=\"";
  if ($a->v) $rta .= "if(valido(this))";
  if ($a->x) $rta .= "solo_reg(this," . saniti($a->x) . ");"; // Sanitizando la expresión regular
  $rta .= "\"";
  if (!$a->u) $rta .= " disabled";
  if (!empty($a->sd)) { // Comprobando si $a->sd no está vacío
      $rta .= " onchange=\"";
      foreach ($a->sd as $dep) {
          if ($dep) $rta .= "changeSelect('" . saniti($a->n) . "','" . saniti($dep) . "');";
      }
      if ($a->so) $rta .= saniti($a->so) . "(this);"; // Sanitizando la llamada a la función
      $rta .= "\"";
  }
  // eval reemplazándolo con call_user_func
  $func = "opc_{$a->c}";
  $opc = '';
  if (function_exists($func)) {
      $opc = call_user_func($func, saniti($a->d));
  }
  $rta .= ">" . $opc . "</select></div>";
  return $rta;
}
function select_mult($a) {
  // Sanitizar todas las entradas
  $w = saniti($a->w);$ww = saniti($a->ww);$n = saniti($a->n);$x = saniti($a->x);$vc = saniti($a->vc);$so = saniti($a->so);$l = saniti($a->l);$u = $a->u; // Propiedad para habilitar/deshabilitar el campo
  // Construir el HTML
  $rta = "<div class='campo {$w} {$ww} borde1 oscuro'><div>{$l}</div>";
  $rta .= "<input type='search' id='{$n}' class='mult' placeholder='-- SELECCIONE --' onclick='showMult(this,true);' onsearch='searchMult(this);'" . (!$u ? " disabled" : "") . ">";
  $rta .= "<select multiple id='f{$n}' name='f{$n}' class='{$w} captura check mult close " . ($a->v ? 'valido' : '') . "' onblur='showMult(this,false);'";
  $rta .= " required onchange=\"";
  if ($a->v) $rta .= "if(valido(this))";
  if ($x) $rta .= "solo_reg(this,{$x});";
  if ($vc) $rta .= "{$vc}(this);";
  $rta .= "\"";
  if (!empty($a->sd)) {
      $rta .= " onchange=\"";
      foreach ($a->sd as $dep) {
          if ($dep) $rta .= "changeSelect('{$n}','" . saniti($dep) . "');";
      }
      if ($so) $rta .= "{$so}(this);";
      $rta .= "\"";
  }
  $rta .= (!$u ? " disabled" : "") . ">"; // Deshabilitar el select si $u es false
  // Obtener las opciones del select
  $func = "opc_{$a->c}";
  $opc = '';
  if (function_exists($func)) {
      $opc = call_user_func($func, saniti($a->d));
  }
  $rta .= $opc . "</select></div>";
  // Agregar lógica de Choices.js si el campo está deshabilitado
  if (!$u) {
      $rta .= "<script>
          document.addEventListener('DOMContentLoaded', function() {
              const selectElement = document.getElementById('f{$n}');
              const choices = new Choices(selectElement);
              choices.disable(); // Desactivar el campo con Choices.js
          });
          alert('El campo {$n} está deshabilitado.');
      </script>";
  }
  return $rta;
}
function input_num($a){
  $name = saniti($a->n);
  $label = saniti($a->l);
  $value = is_numeric($a->d) ? $a->d : '';
  $title = saniti($a->tt);
  $x = saniti($a->x);
  $a->w = $a->w ?? '';
  $a->ww = $a->ww ?? '';
  $a->s = is_numeric($a->s) ? $a->s : ''; // Validar valor máximo
  $a->v = $a->v ?? false;
  $a->u = $a->u ?? true;
  $a->t = $a->t ?? '';
  $a->vc = $a->vc ?? '';
  $a->so = $a->so ?? '';
  $rta = "<div class='campo " . saniti($a->w) . " " . saniti($a->ww) . " borde1 oscuro'>";
  $rta .= "<div>{$label}</div>";
  $rta .= "<input type='number' id='{$name}' name='{$name}'";
  if ($a->s !== '') $rta .= " max='" . saniti($a->s) . "'";
  $rta .= " class='" . saniti($a->w) . " " . ($a->v ? 'valido' : '') . " " . ($a->u ? 'captura' : 'bloqueo') . " " . ($a->t == 't' ? '' : 'txt-right') . "'";
  $rta .= " title='{$title}'";
  $rta .= " onkeypress=\"return event.charCode >= 48 && event.charCode <= 57;\"";
  $rta .= " onblur=\"";
  if ($a->v) $rta .= "if(valido(this))";
  if ($a->x) $rta .= "solo_reg(this," . saniti($a->x) . ");";
  $rta .= "\"";
  if ($a->vc !== '') $rta .= " onfocus=\"" . saniti($a->vc) . "\"";
  if ($a->so !== '') $rta .= " onchange=\"" . saniti($a->so) . "\"";
  if (!$a->u) $rta .= " readonly";
  if ($value !== '') $rta .= " value='" . saniti($value) . "'";
  $rta .= "></div>"; // Cerrar el div
  return $rta;
}
function input_opt($a) {
  $w = saniti($a->w);
  $ww = saniti($a->ww);
  $l = saniti($a->l);
  $n = saniti($a->n);
  $vc = saniti($a->vc);
  $style = ($ww !== 'col-9') ? "" : " style=\"height:20px;\"";
  $chkStyle = ($ww === 'col-9') ? " style=\"left: 100%;top:-16px;\"" : "";
  $checked = ($a->d == 'SI') ? " checked value='SI'" : " value='NO'";
  $rta = "<div class='campo {$w} {$ww} borde1 oscuro'{$style}>";
  $rta .= "<div>{$l}</div>";
  $rta .= "<div class='chk'{$chkStyle}>";
  $rta .= "<input " . attri($a, 'checkbox') . $checked;
  $rta .= " onclick=\"checkon(this);";
  if ($a->vc) $rta .= $vc . ";"; // Sanitiza la llamada a la función
  $rta .= "\"><label for='{$n}'></label></div>";
  $rta .= "</div>"; // Cierra el div principal
  return $rta;
}
function input_area($a) {
  $value = saniti($a->d);
  $cols = saniti($a->s);
  $style = "style='width:95%;'";
  $rta = "<div class='campo " . saniti($a->w) . " " . saniti($a->ww) . " borde1 oscuro'><div>" . saniti($a->l) . "</div>";
  $rta .= "<textarea " . attri($a);
  $rta .= " cols='{$cols}' " . saniti($style);
  if ($a->v) $rta .= " required onblur=\"valido(this);\"";
  $rta .= " onkeypress='countMaxChar(this);'>";
  $rta .= $value;
  $rta .= "</textarea></div>"; // Cierra el div
  return $rta;
}
function input_date($a) {
  $value = saniti($a->d);
  $rta = "<div class='campo " . saniti($a->w) . " " . saniti($a->ww) . " borde1 oscuro'><div>" . saniti($a->l) . "</div>";
  $rta .= "<input " . attri($a, 'date');
  if ($a->vc) $rta .= " onfocus=\"" . saniti($a->vc) . "\""; // Sanitiza el evento
  if ($a->so) $rta .= " onchange=\"" . saniti($a->so) . "\""; // Sanitiza el evento
  if ($value) $rta .= " value=\"" . $value . "\"";
  $rta .= "></div>"; // Cierra el div
  return $rta;
}
function input_clock($a) {
  $value = saniti($a->d);
  $rta = "<div class='campo " . saniti($a->w) . " " . saniti($a->ww) . " borde1 oscuro'><div>" . saniti($a->l) . "</div>";
  $rta .= "<input " . attri($a, 'time');
  if ($value) $rta .= " value=\"" . $value . "\"";
  $rta .= "></div>"; // Cierra el div
  return $rta;
}
function encabezado($a) {
  $d = saniti($a->d); // Sanitiza el contenido del encabezado
  $n = saniti($a->n);
  $w = saniti($a->w);
  $rta = "<div class='encabezado {$n}'>{$d}<div class='text-right'><li class='icono desplegar-panel' id='{$n}' title='ocultar' onclick=\"plegarPanel('{$w}','{$n}');\"></li></div></div>";
  return $rta;
}
function subtitulo($a) {
  $d = saniti($a->d); // Sanitiza el contenido del subtítulo
  $n = saniti($a->n);
  $rta = "<div class='subtitulo {$n}'>{$d}</div>";
  return $rta;
}
function input_label($a) {
  $value = htmlspecialchars($a->d ?? '', ENT_QUOTES, 'UTF-8');
  $label = htmlspecialchars($a->l ?? '', ENT_QUOTES, 'UTF-8');
  $title = htmlspecialchars($a->tt ?? '', ENT_QUOTES, 'UTF-8');
  $rta = "<div class='campo " . htmlspecialchars($a->w, ENT_QUOTES, 'UTF-8') . " " .htmlspecialchars($a->ww, ENT_QUOTES, 'UTF-8') . " borde1 oscuro'>";
  $rta .= "<div>{$label}</div>";
  $rta .= "<div class='info-label' title='{$title}'>{$value}</div>";
  $rta .= "</div>";
  return $rta;
}
