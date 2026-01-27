<?php
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    echo json_encode([]);
    exit();
}

$accion = $_GET['accion'] ?? '';

if ($accion === 'listar') {
    // Consulta simple para obtener todas las mesas activas
    // Nota: tu tabla no tiene campo 'activo', así que obtenemos todas
    $sql = "SELECT id, nombre, imagen_url FROM mesas ORDER BY nombre ASC";
    $res = $conn->query($sql);
    
    $mesas = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $mesas[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'imagen_url' => $row['imagen_url']
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($mesas);
} else {
    echo json_encode([]);
}
?>