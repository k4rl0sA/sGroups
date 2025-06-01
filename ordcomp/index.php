<?php
require_once __DIR__ . '/../config/config.php';
if (!isset($_SESSION['nombre'])) {
    header('Location: ../index.php');
    exit();
}
require_once __DIR__.'/../src/gestion.php';
$mod='ordcom';
$ya = new DateTime();


$clientes = opc_sql("SELECT id_cliente, cliente FROM clientes WHERE estado = 1 ORDER BY cliente", '');
$comerciales = opc_sql("SELECT id_usuario, nombre FROM usuarios WHERE perfil = 3 AND estado = 1 ORDER BY nombre", ''); // Asumiendo perfil 3 es comercial
$gestores = opc_sql("SELECT id_usuario, nombre FROM usuarios WHERE perfil = 15 AND estado = 1 ORDER BY nombre", ''); // Asumiendo perfil 4 es gestor

$acc = acceBtns('ordcom');
$btns = '<button class="act-btn" data-mod='.$mod.' title="Actualizar"><i class="fas fa-rotate"></i></button>';
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
    <title>Órdenes de Compra || <?php echo APP; ?></title>
    <link href="../libs/css/menu.css?v=30.0" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="../libs/css/app.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="../libs/js/main.js?v=3.0"></script>
    <script src="../libs/js/app.js?v=3.0"></script>
    <link rel="stylesheet" href="../libs/css/choices.min.css?v=2.0">
    <script src="../libs/js/choices.min.js"></script>
    <script src="../../libs/js/menu.js?v=1.0"></script>
    <script>
        let mod = 'ordcom';
        let ruta_app = 'lib.php';
        function actualizar() {
            act_lista(mod);
            badgeFilter(mod);
        }
        
        function formatCurrency(input) {
            // Formatear valor monetario mientras se escribe
            let value = input.value.replace(/\D/g, '');
            if(value.length > 0) {
                value = parseInt(value, 10).toLocaleString('es-CO');
                input.value = '$ ' + value;
            }
        }
    </script>
    <?php include __DIR__.'/../src/nav.php'; ?>
</head>
<body Onload="actualizar();">
    <div class="wrapper main" id='<?php echo $mod; ?>-main'>
    <form method='post' id='fapp' onsubmit="return false;">
    <input type="hidden" name="csrf_tkn" value="<?php echo $_SESSION['csrf_tkn'];?>">
        <div class="top-menu">
            <input type="radio" name="slider" id="filtros">
            <input type="radio" name="slider" checked id="datos">
            <nav>
                <label for="filtros" class="filtros"><i class="fa-solid fa-sliders fa-rotate-90"></i>Filtros
                <span class="badge badge-pill badge-warning" id='fil-badge'></span></label>
                <label for="datos" class="datos"><i class="fas fa-table"></i>Datos</label>
                <div class="slider"></div>
            </nav>
            <section>
                <div class="content content-1">
                    <div class="title txt-center"><h2>Órdenes de Compra</h2></div>
                    <div class="frm-filter poppins-font" id='<?php echo $mod; ?>-fil'>
                        <div class="input-box">
                            <label for="fcliente">Cliente:</label>
                            <select class='choices-single' id="fcliente" name="fcliente" OnChange="actualizar();">
                                <option value="">Todos</option>
                                <?php echo $clientes; ?>
                            </select>
                        </div>
                        <div class="input-box">
                            <label for="fcomercial">Comercial:</label>
                            <select class='choices-single' id="fcomercial" name="fcomercial" OnChange="actualizar();">
                                <option value="">Todos</option>
                                <?php echo $comerciales; ?>
                            </select>
                        </div>
                        <div class="input-box">
                            <label for="ffactura">No. Factura:</label>
                            <input type="text" id="ffactura" name="ffactura" placeholder="Buscar por factura" OnChange="actualizar();">
                        </div>
                        <div class="input-box">
                            <label for="festado">Estado:</label>
                            <select class='choices-single' id="festado" name="festado" OnChange="actualizar();">
                                <option value="">Todos</option>
                                <option value="A">Activo</option>
                                <option value="C">Completado</option>
                                <option value="P">Pendiente</option>
                            </select>
                        </div>
                    </div>
                    <div class='load' id='loader' z-index='0'></div>
                </div>
                <div class="content content-2">
                    <div class="title txt-center"><h2>Órdenes de Compra</h2></div>
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
        </form>
    </div>
    
    <!-- Modal para carga de archivos -->
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
            
            // Inicializar selects con Choices.js
            new Choices('#fcliente', {searchEnabled: true, shouldSort: false});
            new Choices('#fcomercial', {searchEnabled: true, shouldSort: false});
            new Choices('#festado', {searchEnabled: false, shouldSort: false});
            
            const fileInput = document.getElementById('fileInput'),
                startLoadingBtn = document.getElementById('startLoading');
                
            startLoadingBtn.onclick = async () => {
                const file = fileInput.files[0];
                if (file) {
                    try {
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
            const res = confirm("¿Desea guardar la orden de compra?");
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
                        enqueueMessage('error', "Error al guardar la orden.", 7000);
                    });
            }
        }
    </script>
</body>
</html>