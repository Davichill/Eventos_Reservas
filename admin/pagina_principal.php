<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Control | GO Quito</title>
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card-grafico {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stats-recaudacion {
            margin-top: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            color: #001f3f;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" style="margin-left: 260px;">
        <header class="top-bar" style="padding: 20px; background: white; border-bottom: 1px solid #eee;">
            <h2><i class="fas fa-chart-line"></i> Resumen de Ingresos y Pagos</h2>
        </header>

        <div class="dashboard-grid">

            <div class="card-grafico">
                <h3>Estado de Cobros Totales</h3>
                <div style="width: 300px; margin: 0 auto;">
                    <canvas id="pagoPieChart"></canvas>
                </div>
                <div id="totalInfo" class="stats-recaudacion">Cargando...</div>
                <p style="color: #888; font-size: 0.9rem;">Comparativa de cartera real vs. cobros</p>
            </div>

            <div class="card-grafico" style="grid-column: span 2;">
                <h3>Ocupación de Eventos por Día</h3>
                <div style="height: 300px;">
                    <canvas id="eventosDiaChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        // 1. Función para cargar datos de pagos
        async function cargarPagos() {
            try {
                const res = await fetch("datos_pagos.php");
                const data = await res.json();

                document.getElementById("totalInfo").innerText = "Total Proyectado: $" + data.total.toLocaleString();

                new Chart(document.getElementById("pagoPieChart"), {
                    type: "doughnut",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: ["#27ae60", "#e74c3c"],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            } catch (error) {
                console.error("Error cargando pagos:", error);
            }
        }

        // 2. Función para cargar eventos por día
        async function cargarEventosPorDia() {
            try {
                const res = await fetch("datos_eventos_dia.php");
                const data = await res.json();

                new Chart(document.getElementById("eventosDiaChart"), {
                    type: "line",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Número de Eventos',
                            data: data.values,
                            borderColor: '#d4af37',
                            backgroundColor: 'rgba(212, 175, 55, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#001f3f'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            } catch (error) {
                console.error("Error cargando eventos:", error);
            }
        }

        // 3. Ejecución única al cargar la página
        window.onload = function () {
            cargarPagos();
            cargarEventosPorDia();
        };
    </script>
</body>
</html>