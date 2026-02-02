<?php
include '../php/conexion.php';
error_reporting(0);
header('Content-Type: application/json');

$sql = "SELECT fecha_evento, COUNT(*) as total FROM reservas WHERE fecha_evento >= CURDATE() GROUP BY fecha_evento ORDER BY fecha_evento ASC LIMIT 10";
$res = $conn->query($sql);

$labels = [];
$values = [];

while($row = $res->fetch_assoc()) {
    $labels[] = date("d M", strtotime($row['fecha_evento']));
    $values[] = (int)$row['total'];
}

// Si la consulta está vacía, enviamos datos de prueba para ver si el gráfico aparece
if(empty($labels)){
    echo json_encode(["labels" => ["Sin eventos"], "values" => [0]]);
} else {
    echo json_encode(["labels" => $labels, "values" => $values]);
}