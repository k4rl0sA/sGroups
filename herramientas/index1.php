<?php
require_once __DIR__ . '/../config/config.php';
if (!isset($_SESSION['nombre'])) {
    header('Location: ../index.php');
    exit();
}
require_once __DIR__.'/../src/gestion.php';
$mod='herramienta_prestamo';
$mod1='herramientas';
$mod2='prestamo_detalle';
$ya = new DateTime();

$tecnicos = opc_sql("SELECT id_usuario, nombre FROM usuarios WHERE estado=1 ORDER BY nombre", '');
$herramientas = opc_sql("SELECT id_herramienta, nombre FROM herramientas WHERE estado=1 AND stock_disponible > 0 ORDER BY nombre", '');
$estados = opc_sql("SELECT DISTINCT estado_prestamo, estado_prestamo AS descripcion FROM herramienta_prestamo WHERE estado=1", '');

$acc = acceBtns($mod);
$btns = '<button class="act-btn" data-mod='.$mod.' title="Actualizar"><i class="fas fa-rotate"></i></button>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamo de Herramientas</title>
    <link href="../libs/css/menu.css?v=30.0" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="../libs/css/app.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="../libs/js/main.js?v=4.0"></script>
    <script src="../libs/js/app.js?v=3.0"></script>
    <link rel="stylesheet" href="../libs/css/choices.min.css?v=2.0">
    <script src="../libs/js/choices.min.js"></script>
    <script src="../../libs/js/menu.js?v=1.0"></script>
    <script>
        let mod = 'herramienta_prestamo';
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
                    <div class="title txt-center"><h2>Gestión de Préstamos de Herramientas</h2></div>
                    <div class="frm-filter poppins-font" id='<?php echo $mod; ?>-fil'>
                        <div class="input-box">
                            <label for="ftecnico">Técnico:</label>
                            <select class='choices-single' id="ftecnico" name="ftecnico" OnChange="actualizar();">
                                <option value="">Todos</option>
                                <?php echo $tecnicos; ?>
                            </select>
                        </div>
                        <div class="input-box">
                            <label for="fherramienta">Herramienta:</label>
                            <select class='choices-single' id="fherramienta" name="fherramienta" OnChange="actualizar();">
                                <option value="">Todas</option>
                                <?php echo $herramientas; ?>
                            </select>
                        </div>
                        <div class="input-box">
                            <label for="festado">Estado Préstamo:</label>
                            <select class='choices-single' id="festado" name="festado" OnChange="actualizar();">
                                <option value="">Todos</option>
                                <?php echo $estados; ?>
                            </select>
                        </div>
                    </div>
                    <div class='load' id='loader' z-index='0'></div>
                </div>
                <div class="content content-2">
                    <div class="title txt-center"><h2>Listado de Préstamos</h2></div>
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
            <!-- Formulario para registrar préstamo -->
            <h3>Registrar Préstamo</h3>
            <div class="input-box">
                <label for="fecha_prestamo">Fecha Préstamo:</label>
                <input type="date" id="fecha_prestamo" name="fecha_prestamo" required>
            </div>
            <div class="input-box">
                <label for="tecnico">Técnico:</label>
                <select id="tecnico" name="tecnico" required>
                    <?php echo $tecnicos; ?>
                </select>
            </div>
            <div class="input-box">
                <label for="herramienta">Herramienta:</label>
                <select id="herramienta" name="herramienta" required>
                    <?php echo $herramientas; ?>
                </select>
            </div>
            <div class="input-box">
                <label for="cantidad">Cantidad:</label>
                <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
            </div>
            <div class="input-box">
                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones"></textarea>
            </div>
            <button type="submit" onclick="grabar('herramienta_prestamo', event);">Registrar Préstamo</button>
        </div>
        <div id='<?php echo $mod1; ?>-frmcap'></div>
        <div id='<?php echo $mod2; ?>-frmcap'></div>
    </form>
    </div>
    <!-- Puedes agregar overlays, modals, scripts JS igual que en reqgest/index.php -->
</body>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrfInput = document.querySelector('input[name="csrf_tkn"]');
        window.csrfToken = csrfInput ? csrfInput.value : '';
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
        let ruta_app = 'lib.php';
        let res = confirm("¿Desea registrar el préstamo de herramienta?");
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
                    enqueueMessage('error', "Error al guardar el préstamo.", 7000);
                });
        }
    }
</script>
</html>
