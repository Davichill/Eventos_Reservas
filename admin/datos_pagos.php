<?php
include '../php/conexion.php';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '7dias';

// Calcular fechas según filtro
$fechaActual = date('Y-m-d');
$whereClause = "WHERE estado IN ('Confirmada', 'Completada')";

switch($filtro) {
    case '7dias':
        $fechaInicio = date('Y-m-d', strtotime('-7 days'));
        $whereClause .= " AND fecha_evento >= '$fechaInicio'";
        break;
    case '30dias':
        $fechaInicio = date('Y-m-d', strtotime('-30 days'));
        $whereClause .= " AND fecha_evento >= '$fechaInicio'";
        break;
    case 'todos':
        // No agregamos filtro de fecha, solo mantenemos el filtro de estado
        break;
}

// Consulta SQL - IMPORTANTE: Usar COALESCE para evitar NULL
$sql = "SELECT 
            COALESCE(SUM(total_evento), 0) as total,
            COALESCE(SUM(total_pagado), 0) as pagado
        FROM reservas 
        $whereClause";

// Para debugging (quitar en producción)
error_log("Consulta pagos SQL: " . $sql);

$result = $conn->query($sql);

if (!$result) {
    error_log("Error en consulta: " . $conn->error);
    $total = 0;
    $pagado = 0;
} else {
    $row = $result->fetch_assoc();
    $total = floatval($row['total']);
    $pagado = floatval($row['pagado']);
}

$pendiente = $total - $pagado;

$data = [
    'labels' => ['Pagado', 'Pendiente'],
    'values' => [$pagado, $pendiente],
    'total' => $total,
    'filtro' => $filtro
];

header('Content-Type: application/json');
echo json_encode($data);
?>