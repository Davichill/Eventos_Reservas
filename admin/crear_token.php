<?php
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitización de datos
    $cliente_nombre = $conn->real_escape_string(trim($_POST['cliente_nombre']));
    $identificacion_input = $conn->real_escape_string(trim($_POST['cliente_identificacion'])); 
    $cliente_email = $conn->real_escape_string(trim($_POST['cliente_email'])); 
    $cliente_telefono = $conn->real_escape_string(trim($_POST['cliente_telefono'])); 
    $id_tipo_evento = $conn->real_escape_string($_POST['id_tipo_evento']);
    $cantidad_personas = $conn->real_escape_string($_POST['cantidad_personas']);
    $fecha_evento = $conn->real_escape_string($_POST['fecha_evento']);
    $token = bin2hex(random_bytes(16));

    // PASO A: Crear Cliente (Siempre nuevo para mantener independencia)
    $sqlCliente = "INSERT INTO clientes (cliente_nombre, identificacion, cliente_email, cliente_telefono) 
                   VALUES ('$cliente_nombre', '$identificacion_input', '$cliente_email', '$cliente_telefono')";
    
    if ($conn->query($sqlCliente)) {
        $id_cliente = $conn->insert_id;

        // PASO B: Crear Reserva
        $sqlReserva = "INSERT INTO reservas (id_cliente, id_tipo_evento, cantidad_personas, fecha_evento, token, estado) 
                       VALUES ('$id_cliente', '$id_tipo_evento', '$cantidad_personas', '$fecha_evento', '$token', 'Pendiente')";

        if ($conn->query($sqlReserva)) {
            $id_reserva_nueva = $conn->insert_id;
            
            // --- NUEVO: REGISTRO DE LOG ---
            // Asumimos que guardas el ID del admin en $_SESSION['admin_id'] al hacer login
            $id_admin = $_SESSION['admin_id']; 
            $accion = "CREAR";
            $tabla = "reservas";
            $descripcion = "Admin creó nueva invitación para $cliente_nombre. Evento ID: $id_reserva_nueva";

            $sqlLog = "INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) 
                       VALUES ('$id_admin', '$accion', '$tabla', '$id_reserva_nueva', '$descripcion')";
            $conn->query($sqlLog);
            // ------------------------------

            header("Location: nueva_invitacion.php?exito=1&token=" . $token);
            exit(); 
        }
    }
}