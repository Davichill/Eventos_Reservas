<?php
// Ajusta esta ruta a tu conexión real (vimos que usabas ../php/conexion.php antes)
require_once '../php/conexion.php'; 
header('Content-Type: application/json');

$filtro = $_GET['filtro'] ?? '7dias';
$fecha_actual = date('Y-m-d');

// Lógica de fechas (Tu Switch está perfecto)
switch ($filtro) {
    case '7dias':  $fecha_inicio = date('Y-m-d', strtotime('-7 days')); $fecha_fin = $fecha_actual; break;
    case '30dias': $fecha_inicio = date('Y-m-d', strtotime('-30 days')); $fecha_fin = $fecha_actual; break;
    case 'mes':    $fecha_inicio = date('Y-m-01'); $fecha_fin = date('Y-m-t'); break;
    default:       $fecha_inicio = date('Y-m-d', strtotime('-7 days')); $fecha_fin = $fecha_actual;
}

try {
    // Usamos LEFT JOIN para que si un tipo de evento no tiene pagos, aparezca con $0
    $sql = "
        SELECT 
            te.nombre AS tipo,
            COALESCE(SUM(p.monto), 0) AS total_ingresos
        FROM tipos_evento te
        LEFT JOIN reservas r ON te.id = r.id_tipo_evento
        LEFT JOIN pagos_reservas p ON r.id = p.id_reserva
        GROUP BY te.id, te.nombre
        ORDER BY total_ingresos DESC
    ";
    
    // Si usas MySQLi (según tus códigos anteriores)
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tipos = [];
    $ingresos = [];
    $total_general = 0;
    
    while ($row = $result->fetch_assoc()) {
        $tipos[] = $row['tipo'];
        $monto = (float)$row['total_ingresos'];
        $ingresos[] = $monto;
        $total_general += $monto;
    }

    echo json_encode([
        'success' => true,
        'tipos' => $tipos,
        'ingresos' => $ingresos,
        'total_general' => $total_general
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}