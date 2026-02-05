function generarColores(cantidad) {
    const colores = [
        'rgba(212, 175, 55, 0.8)', // Dorado
        'rgba(0, 31, 63, 0.8)',   // Azul Marino
        'rgba(39, 174, 96, 0.8)',  // Verde
        'rgba(231, 76, 60, 0.8)',  // Rojo
        'rgba(155, 89, 182, 0.8)', // Morado
        'rgba(52, 152, 219, 0.8)'  // Azul claro
    ];
    return Array.from({ length: cantidad }, (_, i) => colores[i % colores.length]);
}
function formatoDinero(numero) {
    return '$' + Number(numero).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
// Variables globales adicionales
let chartIngresos = null;
let filtroIngresosActual = '7dias';

// Función para cambiar filtro de ingresos
function cambiarFiltroIngresos(filtro) {
    filtroIngresosActual = filtro;
    const botones = document.querySelectorAll('#filtros-ingresos .filtro-btn');
    botones.forEach(btn => btn.classList.remove('active'));
    document.getElementById(`filtro-ingresos-${filtro}`).classList.add('active');
    cargarIngresosPorTipo();
}

// Función para cargar ingresos por tipo
async function cargarIngresosPorTipo() {
    try {
        const res = await fetch(`datos_ingresos_tipo.php?filtro=${filtroIngresosActual}&t=${Date.now()}`);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        // Verificar que los datos sean válidos
        if (!data || !Array.isArray(data.tipos) || !Array.isArray(data.ingresos)) {
            throw new Error('Datos inválidos recibidos del servidor');
        }

        const totalIngresos = data.ingresos.reduce((sum, value) => sum + value, 0);

        // Actualizar total general
        document.getElementById("totalIngresos").textContent = formatoDinero(totalIngresos);
        document.getElementById("subtituloIngresos").textContent = getFiltroTexto(filtroIngresosActual, 'ingresos');

        // Actualizar contadores
        actualizarContadoresIngresos(data);

        // Destruir gráfico anterior si existe
        if (chartIngresos) {
            chartIngresos.destroy();
        }

        // Colores para el gráfico
        const colores = generarColores(data.tipos.length);

        // Crear gráfico de barras para ingresos por tipo
        chartIngresos = new Chart(document.getElementById("ingresosTipoChart"), {
            type: "bar",
            data: {
                labels: data.tipos,
                datasets: [{
                    label: 'Ingresos Promedio',
                    data: data.ingresos,
                    backgroundColor: colores,
                    borderColor: colores.map(color => color.replace('0.8', '1')),
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Monto en USD'
                        },
                        ticks: {
                            callback: function (value) {
                                return '$' + value.toLocaleString('es-ES');
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tipo de Evento/Servicio'
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const tipo = data.tipos[context.dataIndex];
                                const ingreso = data.ingresos[context.dataIndex];
                                const porcentaje = totalIngresos > 0 ? Math.round((ingreso / totalIngresos) * 100) : 0;
                                return `${tipo}: ${formatoDinero(ingreso)} (${porcentaje}%)`;
                            }
                        }
                    }
                }
            }
        });

        console.log('Datos de ingresos por tipo:', data);

    } catch (error) {
        console.error("Error cargando ingresos por tipo:", error);

        // Mostrar gráfico vacío en caso de error
        if (chartIngresos) {
            chartIngresos.destroy();
        }

        chartIngresos = new Chart(document.getElementById("ingresosTipoChart"), {
            type: "bar",
            data: {
                labels: ['Sin datos'],
                datasets: [{
                    data: [0],
                    backgroundColor: ['#ccc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Mostrar mensaje de error
        document.getElementById("contadoresIngresos").innerHTML = `
            <div style="text-align: center; padding: 20px; color: #888; width: 100%;">
                No hay datos disponibles para mostrar
            </div>
        `;

        document.getElementById("totalIngresos").textContent = "$0.00";
        document.getElementById("subtituloIngresos").textContent = "Error cargando datos";
    }
}

// Función para actualizar contadores de ingresos
function actualizarContadoresIngresos(data) {
    const contenedor = document.getElementById("contadoresIngresos");
    let html = '';

    // Encontrar el tipo con mayor y menor ingreso
    let maxIngreso = 0;
    let minIngreso = Infinity;
    let tipoMax = '';
    let tipoMin = '';

    data.tipos.forEach((tipo, index) => {
        const ingreso = data.ingresos[index];

        if (ingreso > maxIngreso) {
            maxIngreso = ingreso;
            tipoMax = tipo;
        }

        if (ingreso < minIngreso && ingreso > 0) {
            minIngreso = ingreso;
            tipoMin = tipo;
        }

        // Crear contador para cada tipo
        const color = generarColores(data.tipos.length)[index];
        const porcentaje = data.ingresos.reduce((sum, val) => sum + val, 0) > 0
            ? Math.round((ingreso / data.ingresos.reduce((sum, val) => sum + val, 0)) * 100)
            : 0;

        html += `
            <div style="
                background: ${color}15;
                border-left: 4px solid ${color};
                padding: 12px 15px;
                border-radius: 8px;
                min-width: 150px;
                flex: 1;
            ">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">
                    ${tipo}
                </div>
                <div style="font-size: 18px; font-weight: bold; color: #001f3f;">
                    ${formatoDinero(ingreso)}
                </div>
                <div style="font-size: 11px; color: #888; margin-top: 3px;">
                    ${porcentaje}% del total
                </div>
            </div>
        `;
    });

    if (data.tipos.length === 0) {
        html = `<div style="text-align: center; padding: 20px; color: #888; width: 100%;">
            No hay tipos de ingresos registrados en este período
        </div>`;
    }

    contenedor.innerHTML = html;
}

// Actualizar la función getFiltroTexto para incluir ingresos
function getFiltroTexto(filtro, tipo) {
    const textos = {
        // ... tus otros textos existentes ...
        ingresos: {
            '7dias': 'Últimos 7 días',
            '30dias': 'Últimos 30 días',
            'mes': 'Este mes',
            'todos': 'Todos los datos'
        }
    };

    return textos[tipo]?.[filtro] || '';
}

// Actualizar la función actualizarTodo para incluir ingresos
function actualizarTodo() {
    const btn = document.querySelector('.btn-actualizar');
    const icono = btn.querySelector('i');

    // Animación de carga
    icono.classList.add('fa-spin');
    btn.disabled = true;

    // Recargar todos los datos
    cargarDatosCalendario();
    cargarPagos();
    cargarEventosPorDia();
    cargarOcupacionSalones();
    cargarDiasVendidos();
    cargarIngresosPorTipo(); // ← Agregar esta línea

    // Restaurar botón después de 1 segundo
    setTimeout(() => {
        icono.classList.remove('fa-spin');
        btn.disabled = false;
    }, 1000);
}

// Agregar al window.onload
window.onload = function () {
    cargarDatosCalendario();
    cargarPagos();
    cargarEventosPorDia();
    cargarOcupacionSalones();
    cargarDiasVendidos();
    cargarIngresosPorTipo(); // ← Agregar esta línea
};
// Variables globales
let chartPagos = null;
let chartEventos = null;
let chartOcupacion = null;
let chartDiasVendidos = null;
let filtroPagosActual = '7dias';
let filtroEventosActual = '7prox';
let filtroSalonesActual = 'todos';
let filtroDiasActual = 'todos';

// Variables y funciones para el calendario
const nombresMeses = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

let anioActual = new Date().getFullYear();
let mesActual = new Date().getMonth() + 1; // Enero = 1
let datosCalendario = {};

// Nombres de días en español
const nombresDias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

// Colores para gráficos
function generarColores(cantidad, tipo = 'normal') {
    const colores = [];

    if (tipo === 'dias') {
        // Colores para días de la semana
        const coloresDias = [
            '#e74c3c', // Domingo - rojo
            '#3498db', // Lunes - azul
            '#2ecc71', // Martes - verde
            '#f39c12', // Miércoles - naranja
            '#9b59b6', // Jueves - morado
            '#1abc9c', // Viernes - turquesa
            '#f1c40f'  // Sábado - amarillo
        ];
        return coloresDias.slice(0, Math.min(cantidad, 7));
    }

    // Colores generales
    const coloresBase = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
        '#1abc9c', '#d35400', '#34495e', '#7f8c8d', '#27ae60'
    ];

    for (let i = 0; i < cantidad; i++) {
        if (i < coloresBase.length) {
            colores.push(coloresBase[i]);
        } else {
            const r = Math.floor(Math.random() * 156) + 100;
            const g = Math.floor(Math.random() * 156) + 100;
            const b = Math.floor(Math.random() * 156) + 100;
            colores.push(`rgb(${r}, ${g}, ${b})`);
        }
    }

    return colores;
}

// Función para actualizar todo
function actualizarTodo() {
    const btn = document.querySelector('.btn-actualizar');
    const icono = btn.querySelector('i');

    // Animación de carga
    icono.classList.add('fa-spin');
    btn.disabled = true;

    // Recargar todos los datos
    cargarDatosCalendario();
    cargarPagos();
    cargarEventosPorDia();
    cargarOcupacionSalones();
    cargarDiasVendidos();

    // Restaurar botón después de 1 segundo
    setTimeout(() => {
        icono.classList.remove('fa-spin');
        btn.disabled = false;
    }, 1000);
}

// Funciones para filtros
function cambiarFiltroPagos(filtro) {
    filtroPagosActual = filtro;
    const botones = document.querySelectorAll('#filtros-pagos .filtro-btn');
    botones.forEach(btn => btn.classList.remove('active'));
    document.getElementById(`filtro-pagos-${filtro}`).classList.add('active');
    cargarPagos();
}

function cambiarFiltroEventos(filtro) {
    filtroEventosActual = filtro;
    const botones = document.querySelectorAll('#filtros-eventos .filtro-btn');
    botones.forEach(btn => btn.classList.remove('active'));
    document.getElementById(`filtro-eventos-${filtro}`).classList.add('active');
    cargarEventosPorDia();
}

function cambiarFiltroSalones(filtro) {
    filtroSalonesActual = filtro;
    const botones = document.querySelectorAll('#filtros-salones .filtro-btn');
    botones.forEach(btn => btn.classList.remove('active'));
    document.getElementById(`filtro-salones-${filtro}`).classList.add('active');
    cargarOcupacionSalones();
}

function cambiarFiltroDias(filtro) {
    filtroDiasActual = filtro;
    const botones = document.querySelectorAll('#filtros-dias .filtro-btn');
    botones.forEach(btn => btn.classList.remove('active'));
    document.getElementById(`filtro-dias-${filtro}`).classList.add('active');
    cargarDiasVendidos();
}

// Cargar datos del calendario
function cargarDatosCalendario() {
    fetch(`calendario_datos.php?anio=${anioActual}&t=${Date.now()}`)
        .then(res => {
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            return res.json();
        })
        .then(result => {
            datosCalendario = result;
            mostrarCalendario();
        })
        .catch(error => {
            console.error("Error cargando datos del calendario:", error);
            mostrarCalendario();
        });
}

function mostrarCalendario() {
    if (mesActual < 1) { mesActual = 12; anioActual--; }
    if (mesActual > 12) { mesActual = 1; anioActual++; }

    if (datosCalendario[mesActual] === undefined) {
        cargarDatosCalendario();
        return;
    }

    document.getElementById("titulo").textContent = nombresMeses[mesActual - 1];
    document.getElementById("anio").textContent = anioActual;

    const diasDiv = document.getElementById("dias");
    diasDiv.innerHTML = "";

    const primerDiaJS = new Date(anioActual, mesActual - 1, 1).getDay();
    const offset = (primerDiaJS === 0) ? 6 : primerDiaJS - 1;
    const diasMes = new Date(anioActual, mesActual, 0).getDate();

    for (let i = 0; i < offset; i++) {
        diasDiv.innerHTML += `<div class="calendario-dia"></div>`;
    }

    for (let d = 1; d <= diasMes; d++) {
        let html = `<div class="calendario-dia"><div class="calendario-numero">${d}</div>`;

        if (datosCalendario[mesActual] && datosCalendario[mesActual][d]) {
            for (const estado in datosCalendario[mesActual][d]) {
                let inicial = "P";
                if (estado === "Confirmada") inicial = "C";
                if (estado === "Cancelada") inicial = "X";
                if (estado === "Completada") inicial = "F";

                html += `<div class="calendario-evento calendario-${inicial}">${inicial}: ${datosCalendario[mesActual][d][estado]}</div>`;
            }
        }

        html += `</div>`;
        diasDiv.innerHTML += html;
    }
}

// Funciones de navegación del calendario
function mesAnterior() { mesActual--; mostrarCalendario(); }
function mesSiguiente() { mesActual++; mostrarCalendario(); }
function anoAnterior() { anioActual--; mesActual = 1; cargarDatosCalendario(); }
function anoSiguiente() { anioActual++; mesActual = 1; cargarDatosCalendario(); }

// Funciones de formato
function formatoDinero(valor) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
}

function formatoHoras(horas) {
    const horasEnteras = Math.floor(horas);
    const minutos = Math.round((horas - horasEnteras) * 60);
    if (minutos === 0) return `${horasEnteras}h`;
    if (horasEnteras === 0) return `${minutos}min`;
    return `${horasEnteras}h ${minutos}min`;
}

// Función para cargar días más/menos vendidos
async function cargarDiasVendidos() {
    try {
        const res = await fetch(`datos_dias_vendidos.php?filtro=${filtroDiasActual}&t=${Date.now()}`);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        // Verificar que los datos sean válidos
        if (!data || !Array.isArray(data.dias) || !Array.isArray(data.ventas)) {
            throw new Error('Datos inválidos recibidos del servidor');
        }

        // Calcular estadísticas
        const totalVentas = data.ventas.reduce((sum, value) => sum + value, 0);
        const promedioVentas = data.ventas.length > 0 ? totalVentas / data.ventas.length : 0;

        // Encontrar día con más y menos ventas
        let maxVentas = 0, minVentas = Infinity;
        let diaMasVendido = '', diaMenosVendido = '';
        let indexMax = 0, indexMin = 0;

        data.ventas.forEach((venta, index) => {
            if (venta > maxVentas) {
                maxVentas = venta;
                diaMasVendido = data.dias[index];
                indexMax = index;
            }
            if (venta < minVentas) {
                minVentas = venta;
                diaMenosVendido = data.dias[index];
                indexMin = index;
            }
        });

        // Calcular porcentajes vs promedio
        const porcentajeMas = promedioVentas > 0 ? Math.round(((maxVentas - promedioVentas) / promedioVentas) * 100) : 0;
        const porcentajeMenos = promedioVentas > 0 ? Math.round(((minVentas - promedioVentas) / promedioVentas) * 100) : 0;

        // Actualizar contadores
        actualizarContadoresDiasVendidos(data, diaMasVendido, maxVentas, porcentajeMas, diaMenosVendido, minVentas, porcentajeMenos, promedioVentas);

        // Actualizar ranking
        actualizarRankingDias(data);

        // Destruir gráfico anterior si existe
        if (chartDiasVendidos) {
            chartDiasVendidos.destroy();
        }

        // Crear gráfico de barras para días
        const colores = generarColores(data.dias.length, 'dias');

        chartDiasVendidos = new Chart(document.getElementById("diasVendidosChart"), {
            type: "bar",
            data: {
                labels: data.dias,
                datasets: [{
                    label: 'Eventos Vendidos',
                    data: data.ventas,
                    backgroundColor: colores.map((color, index) => {
                        if (index === indexMax) return '#2ecc71'; // Verde para mejor día
                        if (index === indexMin) return '#e74c3c'; // Rojo para peor día
                        return color;
                    }),
                    borderColor: colores.map(color => color.replace('0.8', '1')),
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Eventos'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Día de la Semana'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const dia = data.dias[context.dataIndex];
                                const ventas = data.ventas[context.dataIndex];
                                const porcentaje = promedioVentas > 0 ? Math.round(((ventas - promedioVentas) / promedioVentas) * 100) : 0;
                                const signo = porcentaje >= 0 ? '+' : '';
                                return `${dia}: ${ventas} eventos (${signo}${porcentaje}% vs promedio)`;
                            }
                        }
                    }
                }
            }
        });

        // Log para debugging
        console.log('Datos de días vendidos:', data);

    } catch (error) {
        console.error("Error cargando días vendidos:", error);

        // Mostrar gráfico vacío en caso de error
        if (chartDiasVendidos) {
            chartDiasVendidos.destroy();
        }

        chartDiasVendidos = new Chart(document.getElementById("diasVendidosChart"), {
            type: "bar",
            data: {
                labels: ['Sin datos'],
                datasets: [{
                    data: [0],
                    backgroundColor: ['#ccc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Mostrar mensaje de error
        document.getElementById("diasVendidosContadores").innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #888;">
                        No hay datos disponibles para mostrar
                    </div>
                `;

        document.getElementById("rankingDias").innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #888;">
                        No hay datos disponibles
                    </div>
                `;
    }
}

// Función para actualizar contadores de días vendidos
function actualizarContadoresDiasVendidos(data, diaMasVendido, maxVentas, porcentajeMas, diaMenosVendido, minVentas, porcentajeMenos, promedioVentas) {
    const contenedor = document.getElementById("diasVendidosContadores");

    let html = `
                <div class="dias-vendidos-item mejor">
                    <div class="dias-vendidos-dia">${diaMasVendido || 'N/A'}</div>
                    <div class="dias-vendidos-cantidad">${maxVentas || 0}</div>
                    <div class="dias-vendidos-subtitulo">Día más vendido</div>
                    <div class="dias-vendidos-porcentaje porcentaje-positivo">
                        ${porcentajeMas > 0 ? '+' : ''}${porcentajeMas || 0}% vs promedio
                    </div>
                </div>
                
                <div class="dias-vendidos-item peor">
                    <div class="dias-vendidos-dia">${diaMenosVendido || 'N/A'}</div>
                    <div class="dias-vendidos-cantidad">${minVentas || 0}</div>
                    <div class="dias-vendidos-subtitulo">Día menos vendido</div>
                    <div class="dias-vendidos-porcentaje porcentaje-negativo">
                        ${porcentajeMenos || 0}% vs promedio
                    </div>
                </div>
            `;

    contenedor.innerHTML = html;
}

// Función para actualizar ranking de días
function actualizarRankingDias(data) {
    const rankingContainer = document.getElementById("rankingDias");

    // Crear array de objetos para ordenar
    const diasConVentas = data.dias.map((dia, index) => ({
        dia: dia,
        ventas: data.ventas[index],
        index: index
    }));

    // Ordenar de mayor a menor
    diasConVentas.sort((a, b) => b.ventas - a.ventas);

    let html = '';

    if (diasConVentas.length === 0) {
        html = '<div style="text-align: center; padding: 20px; color: #888;">No hay datos disponibles</div>';
    } else {
        diasConVentas.forEach((item, index) => {
            const posicion = index + 1;
            const esMejor = index === 0;
            const esPeor = index === diasConVentas.length - 1;

            html += `
                        <div class="ranking-item" style="${esMejor ? 'background: #d5f4e6;' : esPeor ? 'background: #fadbd8;' : ''}">
                            <div class="ranking-posicion">${posicion}°</div>
                            <div class="ranking-dia">${item.dia}</div>
                            <div class="ranking-cantidad">${item.ventas} eventos</div>
                        </div>
                    `;
        });
    }

    rankingContainer.innerHTML = html;
}

// Función para cargar ocupación de salones
async function cargarOcupacionSalones() {
    try {
        const res = await fetch(`datos_ocupacion_salones.php?filtro=${filtroSalonesActual}&t=${Date.now()}`);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        if (!data || !Array.isArray(data.salones) || !Array.isArray(data.horasPromedio)) {
            throw new Error('Datos inválidos recibidos del servidor');
        }

        const colores = generarColores(data.salones.length);

        if (chartOcupacion) {
            chartOcupacion.destroy();
        }

        chartOcupacion = new Chart(document.getElementById("ocupacionSalonesChart"), {
            type: "bar",
            data: {
                labels: data.salones,
                datasets: [{
                    label: 'Horas Promedio de Ocupación',
                    data: data.horasPromedio,
                    backgroundColor: colores,
                    borderColor: colores.map(color => color.replace('0.8', '1')),
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Horas Promedio' },
                        ticks: { callback: value => value + 'h' }
                    },
                    x: {
                        title: { display: true, text: 'Salones' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const salon = data.salones[context.dataIndex];
                                const horas = data.horasPromedio[context.dataIndex];
                                const eventos = data.totalEventos?.[context.dataIndex] || 0;
                                return `${salon}: ${formatoHoras(horas)} (${eventos} eventos)`;
                            }
                        }
                    }
                }
            }
        });

        actualizarContadoresSalones(data);
        console.log('Datos de ocupación de salones:', data);

    } catch (error) {
        console.error("Error cargando ocupación de salones:", error);

        if (chartOcupacion) {
            chartOcupacion.destroy();
        }

        chartOcupacion = new Chart(document.getElementById("ocupacionSalonesChart"), {
            type: "bar",
            data: {
                labels: ['Sin datos'],
                datasets: [{ data: [0], backgroundColor: ['#ccc'] }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        document.getElementById("ocupacionContadores").innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #888;">
                        No hay datos disponibles para mostrar
                    </div>
                `;
    }
}

// Función para actualizar contadores de salones
function actualizarContadoresSalones(data) {
    const contenedor = document.getElementById("ocupacionContadores");
    let html = '';

    let maxHoras = 0;
    let salonMasOcupado = '';

    data.salones.forEach((salon, index) => {
        const horas = data.horasPromedio[index] || 0;
        const eventos = data.totalEventos?.[index] || 0;
        const color = generarColores(data.salones.length)[index];

        if (horas > maxHoras) {
            maxHoras = horas;
            salonMasOcupado = salon;
        }

        html += `
                    <div class="ocupacion-item" style="border-top-color: ${color};">
                        <div class="ocupacion-nombre" title="${salon}">${salon}</div>
                        <div class="ocupacion-horas">${formatoHoras(horas)}</div>
                        <div class="ocupacion-subtitulo">${eventos} eventos</div>
                        ${horas === maxHoras ? '<div class="ocupacion-promedio">Más ocupado</div>' : ''}
                    </div>
                `;
    });

    if (data.salones.length === 0) {
        html = `<div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #888;">No hay salones con eventos en este período</div>`;
    }

    contenedor.innerHTML = html;
}

// Función para cargar pagos (simplificada para ejemplo)
async function cargarPagos() {
    try {
        document.getElementById("totalInfo").textContent = "$0.00";
        document.getElementById("subtituloTotal").textContent = "Cargando...";

        const timestamp = Date.now();
        const res = await fetch(`datos_pagos.php?filtro=${filtroPagosActual}&t=${timestamp}`);

        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const data = await res.json();

        if (!data || typeof data.total === 'undefined') throw new Error('Datos inválidos recibidos del servidor');

        const total = data.total || 0;
        const pagado = data.values?.[0] || 0;
        const pendiente = data.values?.[1] || 0;
        const totalEventos = data.totalEventos || 0;

        const porcentajePagado = total > 0 ? Math.round((pagado / total) * 100) : 0;
        const porcentajePendiente = total > 0 ? Math.round((pendiente / total) * 100) : 0;

        document.getElementById("totalInfo").textContent = formatoDinero(total);
        document.getElementById("subtituloTotal").textContent = getFiltroTexto(filtroPagosActual, 'pagos');

        document.getElementById("contadorPagado").style.display = 'block';
        document.getElementById("totalPagado").textContent = formatoDinero(pagado);
        document.getElementById("porcentajePagado").textContent = `${porcentajePagado}% del total`;

        document.getElementById("contadorPendiente").style.display = 'block';
        document.getElementById("totalPendiente").textContent = formatoDinero(pendiente);
        document.getElementById("porcentajePendiente").textContent = `${porcentajePendiente}% del total`;

        if (totalEventos > 0) {
            document.getElementById("contadorEventosPagos").style.display = 'block';
            document.getElementById("totalEventosPagos").textContent = totalEventos;
        } else {
            document.getElementById("contadorEventosPagos").style.display = 'none';
        }

        if (chartPagos) chartPagos.destroy();

        chartPagos = new Chart(document.getElementById("pagoPieChart"), {
            type: "doughnut",
            data: {
                labels: data.labels || ['Pagado', 'Pendiente'],
                datasets: [{
                    data: [pagado, pendiente],
                    backgroundColor: ["#27ae60", "#e74c3c"],
                    hoverOffset: 4,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${formatoDinero(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error("Error cargando pagos:", error);
        document.getElementById("totalInfo").textContent = "$0.00";
        document.getElementById("subtituloTotal").textContent = "Error cargando datos";

        document.getElementById("contadorPagado").style.display = 'none';
        document.getElementById("contadorPendiente").style.display = 'none';
        document.getElementById("contadorEventosPagos").style.display = 'none';

        if (chartPagos) chartPagos.destroy();

        chartPagos = new Chart(document.getElementById("pagoPieChart"), {
            type: "doughnut",
            data: {
                labels: ['Sin datos'],
                datasets: [{ data: [1], backgroundColor: ["#ccc"] }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }
}

// Función para cargar eventos por día (simplificada)
async function cargarEventosPorDia() {
    try {
        const res = await fetch(`datos_eventos_dia.php?filtro=${filtroEventosActual}&t=${Date.now()}`);

        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const data = await res.json();

        if (!data || !Array.isArray(data.labels) || !Array.isArray(data.values)) {
            throw new Error('Datos inválidos recibidos del servidor');
        }

        const totalEventos = data.values.reduce((sum, value) => sum + value, 0);
        const diasConEventos = data.values.filter(value => value > 0).length;
        const totalDias = data.values.length;
        const porcentajeDias = totalDias > 0 ? Math.round((diasConEventos / totalDias) * 100) : 0;
        const eventosPromedio = diasConEventos > 0 ? (totalEventos / diasConEventos).toFixed(1) : "0.0";

        document.getElementById("totalEventos").textContent = totalEventos;

        if (totalDias > 0) {
            document.getElementById("contadorDiasConEventos").style.display = 'block';
            document.getElementById("diasConEventos").textContent = diasConEventos;
            document.getElementById("porcentajeDias").textContent = `${porcentajeDias}% de los días`;

            document.getElementById("contadorEventosPromedio").style.display = 'block';
            document.getElementById("eventosPromedio").textContent = eventosPromedio;
        }

        if (chartEventos) chartEventos.destroy();

        chartEventos = new Chart(document.getElementById("eventosDiaChart"), {
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
                    pointBackgroundColor: '#001f3f',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, callback: value => Number.isInteger(value) ? value : '' },
                        title: { display: true, text: 'Cantidad de Eventos' }
                    },
                    x: { title: { display: true, text: 'Fecha' } }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: getFiltroTexto(filtroEventosActual, 'eventos'),
                        font: { size: 14 }
                    },
                    tooltip: {
                        callbacks: {
                            title: tooltipItems => `Fecha: ${tooltipItems[0].label}`,
                            label: context => `Eventos: ${context.raw}`
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error("Error cargando eventos:", error);

        document.getElementById("contadorDiasConEventos").style.display = 'none';
        document.getElementById("contadorEventosPromedio").style.display = 'none';
        document.getElementById("totalEventos").textContent = "0";

        if (chartEventos) chartEventos.destroy();

        chartEventos = new Chart(document.getElementById("eventosDiaChart"), {
            type: "line",
            data: { labels: ['Sin datos'], datasets: [{ data: [0], borderColor: '#ccc' }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
}

// Función auxiliar para textos de filtro
function getFiltroTexto(filtro, tipo) {
    const textos = {
        pagos: {
            '7dias': 'Últimos 7 días',
            '30dias': 'Últimos 30 días',
            'todos': 'Todos los datos'
        },
        eventos: {
            '7prox': 'Próximos 7 días',
            '30prox': 'Próximos 30 días',
            '6meses': 'Próximos 6 meses'
        },
        salones: {
            '7dias': 'Últimos 7 días',
            '30dias': 'Últimos 30 días',
            'mes': 'Este mes',
            'todos': 'Todos los datos'
        },
        dias: {
            '7dias': 'Últimos 7 días',
            '30dias': 'Últimos 30 días',
            'mes': 'Este mes',
            'todos': 'Todos los datos'
        }
    };

    return textos[tipo]?.[filtro] || '';
}

// Ejecución al cargar la página
window.onload = function () {
    cargarDatosCalendario();
    cargarPagos();
    cargarEventosPorDia();
    cargarOcupacionSalones();
    cargarDiasVendidos();
};
