<?php
// actualizar_total.php
ob_start();
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Verificar si es admin principal
$admin_id = $_SESSION['admin_id'];
$sql_admin = "SELECT id, tipo FROM admin_usuarios WHERE id = '$admin_id'";
$res_admin = $conn->query($sql_admin);
$admin_info = $res_admin->fetch_assoc();

$es_admin_principal = ($admin_info && $admin_info['id'] == 1 && $admin_info['tipo'] == 'principal');

if (!$es_admin_principal) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reserva_id = intval($_POST['id_reserva'] ?? 0);
    $total_evento = floatval($_POST['total_evento'] ?? 0);
    
    if ($reserva_id > 0 && $total_evento > 0) {
        // Actualizar total del evento
        $sql_update = "UPDATE reservas SET total_evento = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("di", $total_evento, $reserva_id);
        
        if ($stmt->execute()) {
            // Si hay notas, agregarlas como observación
            if (!empty($_POST['notas_total'])) {
                $notas = $conn->real_escape_string($_POST['notas_total']);
                $sql_notas = "UPDATE reservas SET observaciones = CONCAT(COALESCE(observaciones, ''), '\n\n[NOTA DE PRECIO]: ', ?) WHERE id = ?";
                $stmt2 = $conn->prepare($sql_notas);
                $stmt2->bind_param("si", $notas, $reserva_id);
                $stmt2->execute();
            }
            
            $_SESSION['mensaje'] = "Total del evento actualizado exitosamente";
        } else {
            $_SESSION['error'] = "Error al actualizar el total";
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = "Datos inválidos";
    }
    
    // Redirigir de regreso a registrar_pago.php
    header("Location: registrar_pago.php?id=" . $reserva_id);
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>