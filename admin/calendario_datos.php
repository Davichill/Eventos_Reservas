<?php
include '../php/conexion.php';

$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

$sql = "SELECT 
            MONTH(fecha_evento) as mes,
            DAY(fecha_evento) as dia,
            estado,
            COUNT(*) as cantidad
        FROM reservas 
        WHERE YEAR(fecha_evento) = $anio
        GROUP BY MONTH(fecha_evento), DAY(fecha_evento), estado
        ORDER BY mes, dia";

$result = $conn->query($sql);
$datos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mes = intval($row['mes']);
        $dia = intval($row['dia']);
        $estado = $row['estado'];
        $cantidad = intval($row['cantidad']);
        
        if (!isset($datos[$mes])) {
            $datos[$mes] = [];
        }
        if (!isset($datos[$mes][$dia])) {
            $datos[$mes][$dia] = [];
        }
        
        $datos[$mes][$dia][$estado] = $cantidad;
    }
}

header('Content-Type: application/json');
echo json_encode($datos);
?>