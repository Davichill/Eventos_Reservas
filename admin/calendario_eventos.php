<?php
session_start();
include '../php/conexion.php';

// Modificamos la consulta SQL para incluir el nombre personalizado
$sql = "SELECT r.id, r.hora_inicio, r.hora_fin, r.fecha_evento, r.nombre_evento,
               e.nombre as tipo_nombre, c.cliente_nombre, s.nombre_salon,
               r.cantidad_personas, r.contacto_evento_nombre, r.contacto_evento_telefono,
               r.estado  
        FROM reservas r
        INNER JOIN tipos_evento e ON r.id_tipo_evento = e.id
        INNER JOIN clientes c ON r.id_cliente = c.id
        LEFT JOIN salones s ON r.id_salon = s.id";

$res = $conn->query($sql);
$eventos_js = [];

while ($row = $res->fetch_assoc()) {
    // Usamos el nombre del evento, si no tiene, usamos el tipo de evento
    $titulo_mostrar = !empty($row['nombre_evento']) ? $row['nombre_evento'] : $row['tipo_nombre'];

    // Determinar color por estado del evento
    $color = '#001f3f'; // Color base

    switch (strtoupper($row['estado'] ?? 'PENDIENTE')) {
        case 'CONFIRMADA':
            $color = '#27ae60'; // Verde para confirmados
            break;
        case 'PENDIENTE':
            $color = '#f39c12'; // Naranja para pendientes
            break;
        case 'CANCELADA':
            $color = '#e74c3c'; // Rojo para cancelados
            break;
        case 'COMPLETADA':
            $color = '#3498db'; // Azul para completados
            break;
        default:
            $color = '#001f3f'; // Azul oscuro por defecto
    }

    // Si el título es muy largo, lo acortamos para el calendario
    $titulo_calendario = (strlen($titulo_mostrar) > 25) ? substr($titulo_mostrar, 0, 25) . '...' : $titulo_mostrar;

    $eventos_js[] = [
        'id' => $row['id'],
        'title' => $titulo_calendario,
        'title_full' => $titulo_mostrar,
        'start' => $row['fecha_evento'] . 'T' . $row['hora_inicio'],
        'end' => $row['fecha_evento'] . 'T' . $row['hora_fin'],
        'salon' => $row['nombre_salon'] ?? 'Sin Salón',
        'cliente' => $row['cliente_nombre'],
        'tipo' => $row['tipo_nombre'],
        'pax' => $row['cantidad_personas'],
        'contacto' => $row['contacto_evento_nombre'],
        'telefono' => $row['contacto_evento_telefono'],
        'estado' => $row['estado'] ?? 'Pendiente',
        'color' => $color,
        'textColor' => 'white'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset='utf-8' />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet' />
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.print.css' rel='stylesheet'
        media='print' />

    <!-- Bootstrap para responsive -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css'
        rel='stylesheet' />

    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/es.js'></script>

    <script>
        $(document).ready(function () {
            var datosEventos = <?php echo json_encode($eventos_js); ?>;
            console.log("Datos cargados:", datosEventos);

            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay,listWeek'
                },
                locale: 'es',
                defaultView: 'agendaWeek',
                slotDuration: '00:30:00', // CAMBIO: Slots de 30 minutos
                slotLabelFormat: 'H:mm',
                allDaySlot: false,
                slotEventOverlap: false,
                weekends: true,
                minTime: "00:00:00", // CAMBIO: Mostrar desde media noche
                maxTime: "24:00:00", // CAMBIO: Mostrar hasta media noche siguiente
                eventLimit: true,
                navLinks: true,
                nowIndicator: true,
                eventLimitText: "más...",
                events: datosEventos,
                scrollTime: '08:00:00', // Hora inicial al cargar

                // Función para calcular automáticamente minTime y maxTime
                viewRender: function (view, element) {
                    // Solo para vista semanal y diaria
                    if (view.name === 'agendaWeek' || view.name === 'agendaDay') {
                        setTimeout(function () {
                            ajustarHorasVisibles();
                        }, 100);
                    }
                },

                eventRender: function (event, element) {
                    // Agregar tooltip con información completa
                    element.attr('title',
                        'Evento: ' + event.title_full + '\n' +
                        'Cliente: ' + event.cliente + '\n' +
                        'Salón: ' + event.salon + '\n' +
                        'Estado: ' + event.estado + '\n' +
                        'Asistentes: ' + (event.pax || 'N/A') + ' Pax\n' +
                        'Contacto: ' + (event.contacto || 'N/A')
                    );

                    // Crear contenido personalizado para el evento
                    element.find('.fc-content').html(
                        '<div class="fc-event-title">' +
                        '<strong>' + event.title + '</strong>' +
                        '<span class="fc-event-estado">' + event.estado + '</span>' +
                        '</div>' +
                        '<div class="fc-event-details">' +
                        '<small><i class="fas fa-user"></i> ' + event.cliente + '</small><br>' +
                        '<small><i class="fas fa-map-marker-alt"></i> ' + event.salon + '</small>' +
                        '</div>'
                    );
                },

                eventClick: function (event) {
                    window.location.href = 'detalle_evento.php?id=' + event.id;
                    return false;
                },

                slotLabelFormat: 'H:mm',
                eventTimeFormat: 'H:mm',

                views: {
                    month: {
                        titleFormat: 'MMMM YYYY'
                    },
                    week: {
                        titleFormat: 'D [de] MMMM [de] YYYY',
                        columnFormat: 'ddd D'
                    },
                    day: {
                        titleFormat: 'dddd D [de] MMMM [de] YYYY'
                    }
                }
            });

            // Función para ajustar horas visibles según eventos
            function ajustarHorasVisibles() {
                var calendar = $('#calendar').fullCalendar('getCalendar');
                var eventos = calendar.clientEvents();

                if (eventos.length === 0) return;

                var minHora = 24;
                var maxHora = 0;

                // Encontrar la hora más temprana y más tardía entre todos los eventos
                eventos.forEach(function (evento) {
                    var startHour = evento.start.hour();
                    var endHour = evento.end ? evento.end.hour() : startHour + 1;

                    if (startHour < minHora) minHora = startHour;
                    if (endHour > maxHora) maxHora = endHour;
                });

                // Asegurar un margen mínimo
                minHora = Math.max(0, minHora - 1);
                maxHora = Math.min(24, maxHora + 2);

                console.log("Horas detectadas: min=" + minHora + ", max=" + maxHora);

                // Actualizar solo si hay diferencia
                if (minHora < 8 || maxHora > 20) {
                    calendar.fullCalendar('option', 'minTime', minHora + ':00:00');
                    calendar.fullCalendar('option', 'maxTime', maxHora + ':00:00');
                }
            }

            // Botones de control
            $('#btnToday').click(function () {
                $('#calendar').fullCalendar('today');
            });

            $('#btnMonth').click(function () {
                $('#calendar').fullCalendar('changeView', 'month');
            });

            $('#btnWeek').click(function () {
                $('#calendar').fullCalendar('changeView', 'agendaWeek');
            });

            $('#btnDay').click(function () {
                $('#calendar').fullCalendar('changeView', 'agendaDay');
            });

            // Botón para mostrar todas las horas
            $('#btnAllHours').click(function () {
                $('#calendar').fullCalendar('option', 'minTime', '00:00:00');
                $('#calendar').fullCalendar('option', 'maxTime', '24:00:00');
                $(this).addClass('active').siblings().removeClass('active');
            });

            // Botón para horas laborables (8am-10pm)
            $('#btnBusinessHours').click(function () {
                $('#calendar').fullCalendar('option', 'minTime', '08:00:00');
                $('#calendar').fullCalendar('option', 'maxTime', '22:00:00');
                $(this).addClass('active').siblings().removeClass('active');
            });
        });
    </script>

    <style>
        :root {
            --azul-quito: #001f3f;
            --dorado-quito: #a68945;
            --azul-claro: #3498db;
            --verde: #27ae60;
            --rojo: #e74c3c;
            --naranja: #f39c12;
        }

        body {
            background: linear-gradient(135deg, #f4f7f9 0%, #e8ecf1 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }

        /* Contenedor principal del calendario */
        #calendar {
            max-width: 1400px;
            margin: 20px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 31, 63, 0.15);
            border: 1px solid rgba(0, 31, 63, 0.1);
            overflow-x: auto;
        }

        /* Encabezado del calendario */
        .fc-toolbar {
            background: var(--azul-quito);
            color: white;
            padding: 20px;
            margin: -30px -30px 30px -30px;
            border-radius: 20px 20px 0 0;
            border-bottom: 3px solid var(--dorado-quito);
        }

        .fc-toolbar h2 {
            color: white !important;
            font-weight: 600;
            font-size: 1.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Botones del calendario */
        .fc-button {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            color: white !important;
            text-shadow: none !important;
            border-radius: 8px !important;
            padding: 8px 15px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            margin: 0 3px !important;
        }

        .fc-button:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: white !important;
            transform: translateY(-2px);
        }

        .fc-state-active {
            background: var(--dorado-quito) !important;
            border-color: var(--dorado-quito) !important;
            box-shadow: 0 4px 12px rgba(166, 137, 69, 0.3) !important;
        }

        /* Celdas y días */
        .fc-day-header {
            background: #f8f9fa;
            color: var(--azul-quito);
            font-weight: 700;
            padding: 15px;
            text-transform: uppercase;
            border: 1px solid #e9ecef;
        }

        .fc-day-number {
            color: var(--azul-quito);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .fc-today {
            background: rgba(52, 152, 219, 0.1) !important;
            border: 2px solid var(--azul-claro) !important;
        }

        /* Slots de tiempo más pequeños para ver más horas */
        .fc-time-grid .fc-slats td {
            height: 40px !important;
            /* Reducir altura para ver más horas */
        }

        /* Estilo de los eventos */
        .fc-event {
            border: none !important;
            border-radius: 8px !important;
            padding: 8px 10px !important;
            margin: 2px 0 !important;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid var(--dorado-quito) !important;
        }

        .fc-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            filter: brightness(1.05);
        }

        /* Contenido del evento */
        .fc-event-title {
            font-weight: 600;
            font-size: 0.9em;
            color: white;
            margin-bottom: 3px;
            line-height: 1.2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .fc-event-estado {
            font-size: 0.7em;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .fc-event-details {
            font-size: 0.75em;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.3;
        }

        .fc-event-details i {
            margin-right: 4px;
            font-size: 0.8em;
        }

        /* Colores por estado */
        .evento-confirmada {
            border-left-color: var(--verde) !important;
            background: linear-gradient(135deg, var(--verde) 0%, #2ecc71 100%) !important;
        }

        .evento-pendiente {
            border-left-color: var(--naranja) !important;
            background: linear-gradient(135deg, var(--naranja) 0%, #f1c40f 100%) !important;
        }

        .evento-cancelada {
            border-left-color: var(--rojo) !important;
            background: linear-gradient(135deg, var(--rojo) 0%, #c0392b 100%) !important;
        }

        .evento-completada {
            border-left-color: var(--azul-claro) !important;
            background: linear-gradient(135deg, var(--azul-claro) 0%, #2980b9 100%) !important;
        }

        /* Botones de control personalizados */
        .calendar-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-calendar {
            background: var(--azul-quito);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-calendar:hover {
            background: #003366;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 31, 63, 0.2);
        }

        .btn-calendar.active {
            background: var(--dorado-quito);
            box-shadow: 0 0 0 3px rgba(166, 137, 69, 0.3);
        }

        .btn-calendar i {
            font-size: 1.1em;
        }

        /* Leyenda de estados */
        .legend-estados {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .estado-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            padding: 5px 12px;
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .estado-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            #calendar {
                margin: 10px;
                padding: 15px;
            }

            .fc-toolbar h2 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .fc-toolbar {
                flex-direction: column;
                gap: 10px;
            }

            .fc-toolbar .fc-center {
                order: 1;
                margin-bottom: 10px;
            }

            .fc-toolbar .fc-left,
            .fc-toolbar .fc-right {
                order: 2;
                width: 100%;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
            }

            .fc-button {
                padding: 6px 10px !important;
                font-size: 0.85em !important;
                margin: 2px !important;
            }

            .calendar-controls {
                flex-direction: column;
                align-items: center;
            }

            .btn-calendar {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>

        <div class="main-content">
            <div style="max-width: 1400px; margin: 0 auto;">
                <!-- Header personalizado -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: var(--azul-quito); margin-bottom: 10px;">
                        <i class="fas fa-calendar-alt"></i> Calendario Completo de Eventos
                    </h1>
                    <p style="color: #666; font-size: 1.1rem;">
                        Visualización de todos los eventos - GO Quito Hotel
                    </p>
                </div>

                <!-- Leyenda de estados -->
                <div class="legend-estados">
                    <div class="estado-item">
                        <div class="estado-color" style="background: var(--verde);"></div>
                        <span>Confirmados</span>
                    </div>
                    <div class="estado-item">
                        <div class="estado-color" style="background: var(--naranja);"></div>
                        <span>Pendientes</span>
                    </div>
                    <div class="estado-item">
                        <div class="estado-color" style="background: var(--rojo);"></div>
                        <span>Cancelados</span>
                    </div>
                    <div class="estado-item">
                        <div class="estado-color" style="background: var(--azul-claro);"></div>
                        <span>Completados</span>
                    </div>
                    <div class="estado-item">
                        <div class="estado-color" style="background: var(--azul-quito);"></div>
                        <span>Otros</span>
                    </div>
                </div>

                <!-- Botones de control -->
                <div class="calendar-controls">
                    <button class="btn-calendar" id="btnToday">
                        <i class="fas fa-calendar-day"></i> Hoy
                    </button>
                    <button class="btn-calendar" id="btnMonth">
                        <i class="fas fa-calendar"></i> Vista Mensual
                    </button>
                    <button class="btn-calendar" id="btnWeek">
                        <i class="fas fa-calendar-week"></i> Vista Semanal
                    </button>
                    <button class="btn-calendar" id="btnDay">
                        <i class="fas fa-calendar-day"></i> Vista Diaria
                    </button>
                    <button class="btn-calendar" id="btnAllHours" class="active">
                        <i class="fas fa-clock"></i> Todas las Horas
                    </button>
                    <button class="btn-calendar" id="btnBusinessHours">
                        <i class="fas fa-briefcase"></i> Horas Laborables
                    </button>
                    <a href="dashboard.php" class="btn-calendar" style="background: #27ae60;">
                        <i class="fas fa-list"></i> Ver Listado Completo
                    </a>
                </div>

                <!-- Calendario -->
                <div id='calendar'></div>

                <!-- Estadísticas -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 40px;">
                    <div
                        style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-align: center; flex: 1; min-width: 200px;">
                        <div style="font-size: 2.5rem; color: var(--azul-quito); font-weight: bold;">
                            <?php echo count($eventos_js); ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">Total Eventos</div>
                    </div>
                    <div
                        style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-align: center; flex: 1; min-width: 200px;">
                        <div style="font-size: 2.5rem; color: var(--dorado-quito); font-weight: bold;">
                            <?php
                            $today = date('Y-m-d');
                            $count_today = 0;
                            foreach ($eventos_js as $evento) {
                                if (substr($evento['start'], 0, 10) == $today) {
                                    $count_today++;
                                }
                            }
                            echo $count_today;
                            ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">Eventos Hoy</div>
                    </div>
                    <div
                        style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-align: center; flex: 1; min-width: 200px;">
                        <div style="font-size: 2.5rem; color: #3498db; font-weight: bold;">
                            <?php
                            $count_week = 0;
                            $start_week = date('Y-m-d', strtotime('monday this week'));
                            $end_week = date('Y-m-d', strtotime('sunday this week'));
                            foreach ($eventos_js as $evento) {
                                $event_date = substr($evento['start'], 0, 10);
                                if ($event_date >= $start_week && $event_date <= $end_week) {
                                    $count_week++;
                                }
                            }
                            echo $count_week;
                            ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">Eventos Esta Semana</div>
                    </div>
                    <div
                        style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-align: center; flex: 1; min-width: 200px;">
                        <div style="font-size: 2.5rem; color: #9b59b6; font-weight: bold;">
                            <?php
                            $count_month = 0;
                            $current_month = date('Y-m');
                            foreach ($eventos_js as $evento) {
                                if (substr($evento['start'], 0, 7) == $current_month) {
                                    $count_month++;
                                }
                            }
                            echo $count_month;
                            ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">Eventos Este Mes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>