<?php
require_once __DIR__ . '/../config/config.php';
if (!isset($_SESSION['nombre'])) {
    header('Location: ../index.php');
    exit();
}
require_once __DIR__.'/../src/gestion.php';
$mod='customer';
$ya = new DateTime();
$departamentos=opc_sql("SELECT idcatadeta,descripcion from catadeta where idcatalogo=1 and estado='A' ORDER BY 2",'');
$ciudades=opc_sql("SELECT idcatadeta,descripcion from catadeta where idcatalogo=2 and estado='A' ORDER BY 2",'');
$acc=acceBtns('customer');
$btns='<button class="act-btn" data-mod='.$mod.' title="Actualizar"><i class="fas fa-rotate"></i></button>';
if (isset($acc['crear']) && $acc['crear'] == 'SI') {
    $btns .= '<button class="add-btn" data-mod='.$mod.' title="Nuevo"><i class="fas fa-plus"></i></button>';
}
if (isset($acc['importar']) && $acc['importar'] == 'SI') {
    $btns .= '<button id="openModal" class="upload-btn" data-mod='.$mod.' title="Importar"><i class="fas fa-upload"></i></button>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Clientes || <?php echo APP; ?></title>
    <link href="../libs/css/menu.css?v=30.0" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="../libs/css/app.css?v=22.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="../libs/js/main.js?v=4.0"></script>
    <link rel="stylesheet" href="../libs/css/choices.min.css?v=2.0">
    <script src="../libs/js/choices.min.js"></script>
    <script src="../../libs/js/menu.js?v=1.0"></script>
    <script>
        let mod = 'customer';
        let ruta_app = 'lib.php';
        function actualizar() {
            act_lista(mod);
            badgeFilter(mod);
        }
    </script>
    <?php include __DIR__.'/../src/nav.php'; ?>
</head>
<body Onload="actualizar();">
    <div class="wrapper main" id='<?php echo $mod; ?>-main'>
    <form method='post' id='fapp' onsubmit="return false;">
    <input type="hidden" name="csrf_tkn" value="<?php echo $_SESSION['csrf_tkn'];?>">
        <div class="top-menu">
            <input type="radio" name="slider"  id="filtros">
            <input type="radio" name="slider" checked id="datos">
            <nav>
                <label for="filtros" class="filtros"><i class="fa-solid fa-sliders fa-rotate-90"></i>Filtros
                <span class="badge badge-pill badge-warning" id='fil-badge'></span></label>
                <label for="datos" class="datos"><i class="fas fa-table"></i>Datos</label>
                <div class="slider"></div>
            </nav>
            <section>
                <div class="content content-1">
                    <div class="title txt-center"><h2>Clientes</h2></div>
                    <div class="frm-filter poppins-font" id='<?php echo $mod; ?>-fil'>
                        <div class="input-box">
                            <label for="fcliente">Nombre Cliente:</label>
                            <input type="text" class="captura" id="fcliente" name="fcliente" OnChange="actualizar();">
                        </div>
                        <div class="input-box">
                            <label for="fnit">NIT:</label>
                            <input type="text" class="captura" id="fnit" name="fnit" OnChange="actualizar();">
                        </div>
                        <div class="input-box">
                            <label for="fdep">Departamento:</label>
                            <select class='choices-single' id="fdep" name="fdep" OnChange="actualizar();">
                                <option value="">Todos</option>
                                <?php echo $departamentos; ?>
                            </select>
                        </div>
                        <div class="input-box">
                            <label for="fciu">Ciudad:</label>
                            <select class='choices-single' id="fciu" name="fciu" OnChange="actualizar();">
                                <option value="">Todas</option>
                                <?php echo $ciudades; ?>
                            </select>
                        </div>
                    </div>
                    <div class='load' id='loader' z-index='0'></div>
                </div>
                <div class="content content-2">
                    <div class="title txt-center"><h2>Clientes</h2></div>
                    <div id='<?php echo $mod; ?>-btns' class="header">
                        <?php echo $btns ?>
                        <div class="totals" id='<?php echo $mod; ?>-tot'></div>
                    </div>
                    <div class='panel' id='<?php echo $mod; ?>'>
                        <span class='mensaje' id='<?php echo $mod; ?>-msj'></span>
                        <div class='contenido' id='<?php echo $mod; ?>-lis'></div>
                    </div>
                </div>
            </section>
        </div>
        <div id='<?php echo $mod; ?>-frmcap'>
        </div>
        <div id='hallaz-frmcap'>
        </div>
        </form>
    </div>
    <div class="overlay" id="overlay" style="visibility:hidden;" onClick="closeModal();">
        <div class="toast" id="loader">
            <div class="toast-content">
                <i class=""></i>
                <div class='message' id='<?php echo $mod; ?>-toast'>       
                    <span class="text text-1"></span>
                    <span class="text text-2"></span>
                </div>
            </div>
            <i class="fa-solid fa-xmark close"></i>
            <div class="progress"></div>
        </div>
    </div>
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" id="closeModal">&times;</span>
            <h2>Cargar Registros</h2>
            <p>Por favor, seleccione un archivo CSV para cargar a la base de datos.</p>
            <div class="file-upload">
                <input type="file" id="fileInput" accept=".csv" />
                <i class="fa-solid fa-cloud-arrow-up cloud-icon"></i>
                <p id="file-name">Selecciona un archivo aquí</p>
                <button type="button" class="browse-btn" onclick="document.getElementById('fileInput').click();">
                    Examinar
                </button>
            </div>
            <div class="progress-container">
                <div id="progressBar" class="progress-bar"></div>
            </div>
            <p id="progressText">0% completado</p>
            <p id="statusMessage"></p>
            <div class="button-container">
                <button id="startLoading">Iniciar Carga</button>
                <button id="cancelLoading" style="display: none;">Cancelar</button>
                <button id="closeModal" style="display: none;">Cerrar</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfInput = document.querySelector('input[name="csrf_tkn"]');
            window.csrfToken = csrfInput ? csrfInput.value : '';
            const fileInput = document.getElementById('fileInput'),
                startLoadingBtn = document.getElementById('startLoading');
            startLoadingBtn.onclick = async () => {
                const file = fileInput.files[0];
                if (file) {
                    try {
                        // Lógica para importar clientes
                        error.log(userData);
                    } catch (error) {
                        error.error('Error al obtener los datos: ', error);
                        statusMessage.textContent = 'Error al procesar la solicitud.';
                    }
                } else {
                    statusMessage.textContent = 'Por favor seleccione un archivo CSV.';
                }
            };
        });
        
        function creaBtns(a) {
            const data = 'a=btn&tb='+a; 
            pFetch('lib.php', data, (responseData) => {
                if (responseData) {
                    crearBotones(responseData,a);
                }
            });
        }
        
        function grabar(tb = '', ev) {
            if (tb === '' && ev.target.classList.contains(proc)) tb = proc;
            const fields = document.getElementsByClassName('valido ' + tb);
            for (let i = 0; i < fields.length; i++) {
                if (!valido(fields[i])) {
                    fields[i].focus();
                    ev.preventDefault();
                    return;
                }
            }
            const res = confirm("¿Desea guardar la información del cliente?");
            if (res) {
                myFetch(ruta_app, `a=gra&tb=${tb}`)
                    .then(rta => {
                        handleResponse(rta);
                        if (rta && typeof rta === 'object' && rta.status !== 'error') {
                            act_lista(tb);
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición:", error);
                        enqueueMessage('error', "Error al guardar el cliente.", 7000);
                    });
            }
        }
    </script>
</body>
</html>