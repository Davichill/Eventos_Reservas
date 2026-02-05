<?php
include '../php/conexion.php';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';

// Calcular fechas según filtro
$fechaActual = date('Y-m-d');
$fechaInicio = '';

switch($filtro) {
    case '7dias':
        $fechaInicio = date('Y-m-d', strtotime('-7 days'));
        $condicionFecha = "AND r.fecha_evento >= '$fechaInicio' AND r.fecha_evento <= '$fechaActual'";
        break;
    case '30dias':
        $fechaInicio = date('Y-m-d', strtotime('-30 days'));
        $condicionFecha = "AND r.fecha_evento >= '$fechaInicio' AND r.fecha_evento <= '$fechaActual'";
        break;
    case 'mes':
        $fechaInicio = date('Y-m-01'); // Primer día del mes
        $condicionFecha = "AND r.fecha_evento >= '$fechaInicio' AND r.fecha_evento <= '$fechaActual'";
        break;
    case 'todos':
        $condicionFecha = "";
        break;
}

// Consulta para obtener ocupación por salón
$sql = "SELECT 
            s.nombre_salon,
            s.id as id_salon,
            COUNT(r.id) as total_eventos,
            AVG(
                TIMESTAMPDIFF(
                    MINUTE, 
                    CONCAT(r.fecha_evento, ' ', r.hora_inicio),
                    CONCAT(r.fecha_evento, ' ', r.hora_fin)
                ) / 60.0
            ) as horas_promedio
        FROM salones s
        LEFT JOIN reservas r ON s.id = r.id_salon
            AND r.estado IN ('Confirmada', 'Completada')
            $condicionFecha
        GROUP BY s.id, s.nombre_salon
        HAVING total_eventos > 0
        ORDER BY horas_promedio DESC";

// Para debugging
error_log("Consulta ocupación salones SQL: " . $sql);

$result = $conn->query($sql);

$salones = [];
$horasPromedio = [];
$totalEventos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salones[] = $row['nombre_salon'];
        $horasPromedio[] = floatval($row['horas_promedio']);
        $totalEventos[] = intval($row['total_eventos']);
    }
}

// Si no hay datos, incluir salones vacíos
if (empty($salones)) {
    $sqlSalones = "SELECT nombre_salon FROM salones ORDER BY nombre_salon";
    $resultSalones = $conn->query($sqlSalones);
    
    if ($resultSalones && $resultSalones->num_rows > 0) {
        while ($row = $resultSalones->fetch_assoc()) {
            $salones[] = $row['nombre_salon'];
            $horasPromedio[] = 0;
            $totalEventos[] = 0;
        }
    }
}

$data = [
    'salones' => $salones,
    'horasPromedio' => $horasPromedio,
    'totalEventos' => $totalEventos,
    'filtro' => $filtro
];

header('Content-Type: application/json');
echo json_encode($data);
?>