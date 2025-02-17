<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos y Exportación</title>
</head>
<body>
    <h1>Gestión de Eventos y Exportación de Datos</h1>
    <button id="exportarBtn">Exportar Datos</button>
    <!-- Script para manejar la exportación de datos -->
    <script>
        let Exec = false;
        // Definir un Map que mapea tipos de eventos a otro Map que mapea selectores a funciones específicas
        const eventHandlers = new Map();
        // Añadir manejadores para diferentes elementos y eventos
        function addEventHandler(selector, eventType, handler, options = {}) {
            if (!eventHandlers.has(eventType)) {
                eventHandlers.set(eventType, new Map());
            }
            const eventMap = eventHandlers.get(eventType);
            if (!eventMap.has(selector)) {
                eventMap.set(selector, []);
            }
            eventMap.get(selector).push({ handler, options });
        }
        // Manejador para el botón de exportar datos
        addEventHandler('#exportarBtn', 'click', function(event) {
            Exec=true;
            event.preventDefault();
            exportarDatos('exp_datos');
            Exec=false;
        });
        // Función para exportar datos utilizando fetch
        function exportarDatos(funcion) {
            if(Exec){
            // Construir la URL para la petición a lib.php
            const url = `lib.php?funcion=${funcion}`;
            // Opciones para la petición fetch
            const fetchOptions = {
                method: 'GET', // Método GET para este ejemplo
                headers: {
                    'Content-Type': 'application/vnd.ms-excel' // Tipo de contenido Excel
                }
            };
            fetch(url, fetchOptions)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al exportar datos.');
                    }
                    // Crear un enlace para descargar el archivo
                    response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = 'datos_exportados.xlsx'; // Nombre del archivo Excel
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                    });
                })
                .catch(error => console.error('Error:', error));
            }
        }
        // Agregar un único listener para una lista ampliada de eventos de interés
        const eventTypes = ['click', 'mouseover', 'input', 'focus', 'blur', 'change', 'keydown', 'keyup', 'submit'];
        eventTypes.forEach(eventType => {
            document.addEventListener(eventType, function(event) {
                handleEvent(event, eventType);
            });
        });
        // Función para manejar el evento
        function handleEvent(event, eventType) {
            const target = event.target;
            if (eventHandlers.has(eventType)) {
                const eventMap = eventHandlers.get(eventType);
                for (let [selector, handlers] of eventMap.entries()) {
                    if (target.matches(selector)) {
                        handlers.forEach(({ handler, options }) => {
                            if (options.preventDefault) event.preventDefault();
                            if (options.stopPropagation) event.stopPropagation();
                            handler.call(target, event);
                        });
                    }
                }
            }
        }
    </script>
</body>
</html>