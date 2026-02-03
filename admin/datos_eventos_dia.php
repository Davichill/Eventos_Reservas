<?php
include '../php/conexion.php';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '7prox';

// Calcular fechas según filtro
$fechaActual = date('Y-m-d');
$fechaFin = '';

switch($filtro) {
    case '7prox':
        $fechaFin = date('Y-m-d', strtotime('+7 days'));
        break;
    case '30prox':
        $fechaFin = date('Y-m-d', strtotime('+30 days'));
        break;
    case '6meses':
        $fechaFin = date('Y-m-d', strtotime('+6 months'));
        break;
}

// Consulta con filtro
$sql = "SELECT 
            fecha_evento,
            COUNT(*) as cantidad
        FROM reservas 
        WHERE fecha_evento >= '$fechaActual'
        AND fecha_evento <= '$fechaFin'
        AND estado IN ('Pendiente', 'Confirmada')
        GROUP BY fecha_evento
        ORDER BY fecha_evento";

$result = $conn->query($sql);

$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $fecha = date('d/m', strtotime($row['fecha_evento']));
    $labels[] = $fecha;
    $values[] = intval($row['cantidad']);
}

// Calcular total de eventos
$totalEventos = array_sum($values);

// Rellenar días sin eventos con 0
$fechaInicioObj = new DateTime($fechaActual);
$fechaFinObj = new DateTime($fechaFin);
$intervalo = DateInterval::createFromDateString('1 day');
$periodo = new DatePeriod($fechaInicioObj, $intervalo, $fechaFinObj);

$dataCompleta = [];
foreach ($periodo as $fecha) {
    $fechaStr = $fecha->format('Y-m-d');
    $fechaLabel = $fecha->format('d/m');
    $dataCompleta[$fechaStr] = ['label' => $fechaLabel, 'value' => 0];
}

// Combinar con datos de la BD
for ($i = 0; $i < count($labels); $i++) {
    foreach ($dataCompleta as $key => $value) {
        if ($value['label'] == $labels[$i]) {
            $dataCompleta[$key]['value'] = $values[$i];
            break;
        }
    }
}

// Preparar arrays finales
$finalLabels = [];
$finalValues = [];

foreach ($dataCompleta as $item) {
    $finalLabels[] = $item['label'];
    $finalValues[] = $item['value'];
}

$data = [
    'labels' => $finalLabels,
    'values' => $finalValues,
    'totalEventos' => $totalEventos
];

header('Content-Type: application/json');
echo json_encode($data);
?>