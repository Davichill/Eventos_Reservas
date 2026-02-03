<?php
include '../php/conexion.php';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '7dias';

// Calcular fechas según filtro
$fechaActual = date('Y-m-d');
$fechaInicio = '';

switch($filtro) {
    case '7dias':
        $fechaInicio = date('Y-m-d', strtotime('-7 days'));
        $condicionFecha = "AND fecha_evento >= '$fechaInicio' AND fecha_evento <= '$fechaActual'";
        break;
    case '30dias':
        $fechaInicio = date('Y-m-d', strtotime('-30 days'));
        $condicionFecha = "AND fecha_evento >= '$fechaInicio' AND fecha_evento <= '$fechaActual'";
        break;
    case 'mes':
        $fechaInicio = date('Y-m-01'); // Primer día del mes
        $condicionFecha = "AND fecha_evento >= '$fechaInicio' AND fecha_evento <= '$fechaActual'";
        break;
    case 'todos':
        $condicionFecha = "";
        break;
}

// Consulta para obtener ventas por día de la semana
$sql = "SELECT 
            DAYNAME(fecha_evento) as dia_semana,
            DAYOFWEEK(fecha_evento) as num_dia,
            COUNT(*) as total_eventos
        FROM reservas 
        WHERE estado IN ('Confirmada', 'Completada')
        $condicionFecha
        GROUP BY DAYOFWEEK(fecha_evento), DAYNAME(fecha_evento)
        ORDER BY num_dia";

// Para debugging
error_log("Consulta días vendidos SQL: " . $sql);

$result = $conn->query($sql);

// Mapeo de nombres de días en inglés a español
$diasInglesEspanol = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo'
];

// Orden de días (Lunes a Domingo)
$ordenDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

$dias = [];
$ventas = [];

// Inicializar array con todos los días en orden
foreach ($ordenDias as $dia) {
    $dias[] = $dia;
    $ventas[] = 0;
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $diaIngles = $row['dia_semana'];
        $diaEspanol = $diasInglesEspanol[$diaIngles] ?? $diaIngles;
        $totalEventos = intval($row['total_eventos']);
        
        // Encontrar el índice del día en el array ordenado
        $indice = array_search($diaEspanol, $ordenDias);
        if ($indice !== false) {
            $ventas[$indice] = $totalEventos;
        }
    }
}

$data = [
    'dias' => $dias,
    'ventas' => $ventas,
    'filtro' => $filtro
];

header('Content-Type: application/json');
echo json_encode($data);
?>