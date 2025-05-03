<?php
require_once __DIR__ . '/../config/config.php'; // <- AQUÍ: Incluir config.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link href="../../libs/css/menu.css?v=8.0" rel="stylesheet" type="text/css">
    <link href="../../libs/css/graph.css?v=2.0" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../libs/js/menu.js?v=4.0"></script>
</head>
<body>
<?php 
    include __DIR__.'/../src/nav.php'; 
    ?>
    <div class="wrapper main" id='Inicio-main'>
    <header>
        Dashboard
    </header>
    <div class="container">
        <div class="card">
            <h3>Número de Órdenes de Servicio</h3>
            <canvas id="ordenesChart"></canvas>
        </div>
        <div class="card">
            <h3>Número de Cotizaciones</h3>
            <canvas id="cotizacionesChart"></canvas>
        </div>
        <div class="card">
            <h3>Total de Ventas por Mes</h3>
            <canvas id="ventasChart"></canvas>
        </div>
        <div class="card">
            <h3>Top 10 Vendedores</h3>
            <canvas id="vendedoresChart"></canvas>
        </div>
        <div class="card">
            <h3>Número de Requerimientos Comerciales</h3>
            <canvas id="requerimientosChart"></canvas>
        </div>
    </div>

    <script>
        // Número de Órdenes de Servicio
        const ordenesCtx = document.getElementById('ordenesChart').getContext('2d');
        new Chart(ordenesCtx, {
            type: 'bar',
            data: {
                labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
                datasets: [{
                    label: 'Órdenes de Servicio',
                    data: [10, 20, 15, 30, 25, 10, 5],
                    backgroundColor: '#007bff'
                }]
            }
        });

        // Número de Cotizaciones
        const cotizacionesCtx = document.getElementById('cotizacionesChart').getContext('2d');
        new Chart(cotizacionesCtx, {
            type: 'line',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
                datasets: [{
                    label: 'Cotizaciones',
                    data: [150, 200, 180, 250, 300, 280],
                    borderColor: '#28a745',
                    fill: false
                }]
            }
        });

        // Total de Ventas por Mes
        const ventasCtx = document.getElementById('ventasChart').getContext('2d');
        new Chart(ventasCtx, {
            type: 'bar',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
                datasets: [{
                    label: 'Ventas (COP)',
                    data: [3000, 4000, 3500, 5000, 4500, 4800],
                    backgroundColor: '#ffc107'
                }]
            }
        });

        // Top 10 Vendedores
        /*
        const vendedoresCtx = document.getElementById('vendedoresChart').getContext('2d');
        new Chart(vendedoresCtx, {
            type: 'horizontalBar',
            data: {
                labels: ['Vendedor A', 'Vendedor B', 'Vendedor C', 'Vendedor D', 'Vendedor E', 'Vendedor F', 'Vendedor G', 'Vendedor H', 'Vendedor I', 'Vendedor J'],
                datasets: [{
                    label: 'Ventas',
                    data: [1000, 900, 850, 800, 750, 700, 650, 600, 550, 500],
                    backgroundColor: '#dc3545'
                }]
            }
        });
*/
        // Número de Requerimientos Pendientes
        const requerimientosCtx = document.getElementById('requerimientosChart').getContext('2d');
        new Chart(requerimientosCtx, {
            type: 'pie',
            <?php
            require_once __DIR__ . '/../config/config.php';
            $sql = "SELECT estado_req, COUNT(*) as total FROM req_comercial GROUP BY estado_req";
            $result = datos_mysql($sql);

            if ($result['code'] === 0 && !empty($result['responseResult'])) {
                $labels = [];
                $data = [];
                $backgroundColors = ['#6c757d', '#007bff']; // Colores para el gráfico

                foreach ($result['responseResult'] as $index => $row) {
                    $labels[] = $row['estado_req'] === '1' ? 'Pendientes' : 'Completados';
                    $data[] = $row['total'];
                }
            } else {
                $labels = ['Error'];
                $data = [0];
                $backgroundColors = ['#dc3545'];
            }
            ?>
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Requerimientos',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: <?php echo json_encode($backgroundColors); ?>
                }]
            }
        });

        // Top 10 Vendedores con más Ventas
        const vendedoresCtx = document.getElementById('vendedoresChart').getContext('2d');
        new Chart(vendedoresCtx, {
            type: 'bar', // Tipo de gráfico de barras
            data: {
                labels: ['Vendedor A', 'Vendedor B', 'Vendedor C', 'Vendedor D', 'Vendedor E',
                    'Vendedor F', 'Vendedor G', 'Vendedor H', 'Vendedor I', 'Vendedor J'], // Nombres de los vendedores
                datasets: [{
                    label: 'Ventas en COP',
                    data: [10000, 9500, 8800, 8500, 8200, 7900, 7500, 7200, 6800, 6500], // Ventas correspondientes
                    backgroundColor: [
                        '#007bff', '#6f42c1', '#dc3545', '#ffc107', '#28a745',
                        '#17a2b8', '#e83e8c', '#fd7e14', '#343a40', '#6610f2'
                    ], // Colores individuales para cada barra
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Eje invertido para barras horizontales
                scales: {
                    x: {
                        beginAtZero: true, // Inicia desde cero en el eje X
                        title: {
                            display: true,
                            text: 'Ventas (COP)',
                            color: '#333',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Ocultar leyenda (opcional)
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return `$${tooltipItem.raw.toLocaleString('en-US')}`; // Formato monetario
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>