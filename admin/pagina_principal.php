<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Control | GO Quito</title>
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="../css/gestion_menu/graficos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" style="margin-left: 260px;">
        <header class="top-bar" style="padding: 20px; background: white; border-bottom: 1px solid #eee;">
            <h2><i class="fas fa-chart-line"></i> Resumen de Ingresos y Pagos</h2>
        </header>

        <div class="dashboard-grid">

            <!-- Gráfico de estado de cobros -->
            <div class="card-grafico" id="card-pagos">
                <button class="btn-actualizar" onclick="actualizarTodo()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>

                <h3>Estado de Cobros Totales</h3>

                <div class="filtros-container" id="filtros-pagos">
                    <button class="filtro-btn active" onclick="cambiarFiltroPagos('7dias')"
                        id="filtro-pagos-7dias">Últimos 7 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroPagos('30dias')" id="filtro-pagos-30dias">Últimos
                        30 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroPagos('todos')"
                        id="filtro-pagos-todos">Todos</button>
                </div>

                <div class="grafico-con-contador">
                    <div style="width: 300px;">
                        <canvas id="pagoPieChart"></canvas>
                    </div>

                    <div class="contador-derecha">
                        <div class="contador-item">
                            <div class="contador-titulo">Total Proyectado</div>
                            <div class="contador-numero" id="totalInfo">$0.00</div>
                            <div class="contador-subtitulo" id="subtituloTotal">Cargando...</div>
                        </div>

                        <div class="contador-item" id="contadorPagado"
                            style="border-left-color: #27ae60; display: none;">
                            <div class="contador-titulo">Total Pagado</div>
                            <div class="contador-numero" id="totalPagado">$0.00</div>
                            <div class="contador-subtitulo" id="porcentajePagado">0%</div>
                        </div>

                        <div class="contador-item" id="contadorPendiente"
                            style="border-left-color: #e74c3c; display: none;">
                            <div class="contador-titulo">Total Pendiente</div>
                            <div class="contador-numero" id="totalPendiente">$0.00</div>
                            <div class="contador-subtitulo" id="porcentajePendiente">0%</div>
                        </div>

                        <div class="contador-item" id="contadorEventosPagos"
                            style="border-left-color: #3498db; display: none;">
                            <div class="contador-titulo">Total Eventos</div>
                            <div class="contador-numero" id="totalEventosPagos">0</div>
                            <div class="contador-subtitulo">En el período</div>
                        </div>
                    </div>
                </div>

                <p style="color: #888; font-size: 0.9rem; margin-top: 15px;">Comparativa de cartera real vs. cobros</p>
            </div>

            <!-- Gráfico de días más/menos vendidos -->
            <div class="card-grafico">
                <h3><i class="fas fa-chart-bar"></i> Días Más/Menos Vendidos</h3>

                <div class="filtros-container" id="filtros-dias">
                    <button class="filtro-btn active" onclick="cambiarFiltroDias('7dias')"
                        id="filtro-dias-7dias">Últimos 7 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroDias('30dias')" id="filtro-dias-30dias">Últimos 30
                        días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroDias('mes')" id="filtro-dias-mes">Este mes</button>
                    <button class="filtro-btn" onclick="cambiarFiltroDias('todos')"
                        id="filtro-dias-todos">Todos</button>
                </div>

                <div class="grafico-dias-vendidos">
                    <div style="height: 250px;">
                        <canvas id="diasVendidosChart"></canvas>
                    </div>
                </div>

                <div class="dias-vendidos-contadores" id="diasVendidosContadores">
                    <!-- Los contadores se llenarán dinámicamente -->
                </div>

                <div class="ranking-dias" id="rankingDias">
                    <!-- El ranking se llenará dinámicamente -->
                </div>

                <p style="color: #888; font-size: 0.9rem; margin-top: 15px;">Análisis de ventas por día de la semana</p>
            </div>

            <!-- Gráfico de ocupación por salones -->
            <div class="card-grafico">
                <h3><i class="fas fa-building"></i> Ocupación por Salones</h3>

                <div class="filtros-container" id="filtros-salones">
                    <button class="filtro-btn active" onclick="cambiarFiltroSalones('7dias')"
                        id="filtro-salones-7dias">Últimos 7 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroSalones('30dias')"
                        id="filtro-salones-30dias">Últimos 30 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroSalones('mes')" id="filtro-salones-mes">Este
                        mes</button>
                    <button class="filtro-btn" onclick="cambiarFiltroSalones('todos')"
                        id="filtro-salones-todos">Todos</button>
                </div>

                <div class="grafico-ocupacion-salones">
                    <div style="height: 250px;">
                        <canvas id="ocupacionSalonesChart"></canvas>
                    </div>
                </div>

                <div class="ocupacion-contadores" id="ocupacionContadores">
                    <!-- Los contadores se llenarán dinámicamente -->
                </div>

                <p style="color: #888; font-size: 0.9rem; margin-top: 15px;">Horas promedio de ocupación por salón</p>
            </div>

            <!-- Gráfico de Ingresos Promedio por Tipo -->
            <div class="card-grafico">
                <h3><i class="fas fa-money-bill-wave"></i> Ingresos Promedio por Tipo</h3>

                <div class="filtros-container" id="filtros-ingresos">
                    <button class="filtro-btn active" onclick="cambiarFiltroIngresos('7dias')"
                        id="filtro-ingresos-7dias">Últimos 7 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroIngresos('30dias')"
                        id="filtro-ingresos-30dias">Últimos 30 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroIngresos('mes')" id="filtro-ingresos-mes">Este
                        mes</button>
                    <button class="filtro-btn" onclick="cambiarFiltroIngresos('todos')"
                        id="filtro-ingresos-todos">Todos</button>
                </div>

                <div style="height: 300px; position: relative;">
                    <canvas id="ingresosTipoChart"></canvas>
                </div>

                <div class="contadores-ingresos" id="contadoresIngresos"
                    style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                    <!-- Los contadores se llenarán dinámicamente -->
                </div>

                <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 0.9rem; color: #666; display: flex; justify-content: space-between;">
                        <span>Total General:</span>
                        <span id="totalIngresos" style="font-weight: bold; color: #001f3f;">$0.00</span>
                    </div>
                    <div style="font-size: 0.8rem; color: #888; margin-top: 5px;">
                        <span id="subtituloIngresos">Cargando datos...</span>
                    </div>
                </div>
            </div>

            <!-- Gráfico de ocupación de eventos -->
            <div class="card-grafico" style="grid-column: span 2;">
                <h3>Ocupación de Eventos por Día</h3>

                <div class="filtros-container" id="filtros-eventos">
                    <button class="filtro-btn active" onclick="cambiarFiltroEventos('7prox')"
                        id="filtro-eventos-7prox">Próximos 7 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroEventos('30prox')"
                        id="filtro-eventos-30prox">Próximos 30 días</button>
                    <button class="filtro-btn" onclick="cambiarFiltroEventos('6meses')"
                        id="filtro-eventos-6meses">Próximos 6 meses</button>
                </div>

                <div style="height: 300px; position: relative;">
                    <canvas id="eventosDiaChart"></canvas>
                </div>

                <div class="contador-linea">
                    <div class="contador-linea-item">
                        <div class="contador-titulo">Total de Eventos</div>
                        <div class="contador-numero" id="totalEventos">0</div>
                        <div class="contador-subtitulo">En el período seleccionado</div>
                    </div>

                    <div class="contador-linea-item" id="contadorDiasConEventos" style="display: none;">
                        <div class="contador-titulo">Días con Eventos</div>
                        <div class="contador-numero" id="diasConEventos">0</div>
                        <div class="contador-subtitulo" id="porcentajeDias">0%</div>
                    </div>

                    <div class="contador-linea-item" id="contadorEventosPromedio" style="display: none;">
                        <div class="contador-titulo">Promedio por Día</div>
                        <div class="contador-numero" id="eventosPromedio">0.0</div>
                        <div class="contador-subtitulo">Eventos por día</div>
                    </div>
                </div>

                <!-- Calendario de eventos -->
                <div class="calendario-container">
                    <h3 style="margin-top: 0; margin-bottom: 15px; text-align: center;">
                        <i class="fas fa-calendar-alt"></i> Calendario de Eventos
                    </h3>
                    <div class="calendario-header">
                        <div class="calendario-botones">
                            <button class="calendario-btn" onclick="anoAnterior()">← Año</button>
                            <button class="calendario-btn" onclick="mesAnterior()">← Mes</button>
                        </div>
                        <div class="calendario-titulo-container">
                            <div class="calendario-mes-titulo" id="titulo"></div>
                            <div class="calendario-anio-titulo" id="anio"></div>
                        </div>
                        <div class="calendario-botones">
                            <button class="calendario-btn" onclick="mesSiguiente()">Mes →</button>
                            <button class="calendario-btn" onclick="anoSiguiente()">Año →</button>
                        </div>
                    </div>

                    <div class="calendario-grid">
                        <div class="calendario-nombre-dia">L</div>
                        <div class="calendario-nombre-dia">M</div>
                        <div class="calendario-nombre-dia">M</div>
                        <div class="calendario-nombre-dia">J</div>
                        <div class="calendario-nombre-dia">V</div>
                        <div class="calendario-nombre-dia">S</div>
                        <div class="calendario-nombre-dia">D</div>
                    </div>

                    <div id="dias" class="calendario-grid"></div>

                    <div style="margin-top: 15px; font-size: 9px; color: #666;">
                        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                            <span><span class="calendario-evento calendario-P"
                                    style="display: inline-block; width: 12px; height: 12px;"></span> Pendiente</span>
                            <span><span class="calendario-evento calendario-C"
                                    style="display: inline-block; width: 12px; height: 12px;"></span> Confirmada</span>
                            <span><span class="calendario-evento calendario-X"
                                    style="display: inline-block; width: 12px; height: 12px;"></span> Cancelada</span>
                            <span><span class="calendario-evento calendario-F"
                                    style="display: inline-block; width: 12px; height: 12px;"></span> Completada</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/admin/graficos.js"></script>
</body>

</html>