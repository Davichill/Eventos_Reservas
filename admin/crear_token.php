<?php
include '../php/conexion.php';
session_start();

// Verificación de sesión
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Recibimos y saneamos los datos del formulario
    $id_cliente = $conn->real_escape_string($_POST['id_cliente']);
    $id_tipo_evento = $conn->real_escape_string($_POST['id_tipo_evento']);
    $id_salon = $conn->real_escape_string($_POST['id_salon']);
    $nombre_evento = $conn->real_escape_string($_POST['nombre_evento']);
    $cantidad_personas = $conn->real_escape_string($_POST['cantidad_personas']);
    $fecha_evento = $conn->real_escape_string($_POST['fecha_evento']);
    $hora_inicio = $conn->real_escape_string($_POST['hora_inicio']);
    $hora_fin = $conn->real_escape_string($_POST['hora_fin']);
    $nota_admin = $conn->real_escape_string($_POST['nota'] ?? '');
    
    // --- NUEVO: Obtener el ID del coordinador actual ---
    $id_coordinador = $_SESSION['admin_id']; // Asumiendo que guardas el ID en la sesión
    // --------------------------------------------------

    // Generación de token único para el enlace del cliente
    $token = bin2hex(random_bytes(16));

    // 2. Obtenemos datos para el Log de auditoría
    $resCliente = $conn->query("SELECT cliente_nombre FROM clientes WHERE id = '$id_cliente'");
    $datosCliente = $resCliente->fetch_assoc();
    $nombre_cliente = $datosCliente['cliente_nombre'] ?? 'ID: ' . $id_cliente;

    $resSalon = $conn->query("SELECT nombre_salon FROM salones WHERE id = '$id_salon'");
    $datosSalon = $resSalon->fetch_assoc();
    $nombre_salon = $datosSalon['nombre_salon'] ?? 'ID: ' . $id_salon;
    
    // --- NUEVO: Obtener nombre del coordinador ---
    $resCoordinador = $conn->query("SELECT nombre_completo FROM admin_usuarios WHERE id = '$id_coordinador'");
    $datosCoordinador = $resCoordinador->fetch_assoc();
    $nombre_coordinador = $datosCoordinador['nombre_completo'] ?? 'ID: ' . $id_coordinador;
    // ---------------------------------------------

    // 3. Crear Reserva INCLUYENDO id_coordinador
    $sqlReserva = "INSERT INTO reservas (
                        id_cliente, 
                        id_tipo_evento, 
                        nombre_evento, 
                        id_salon, 
                        cantidad_personas, 
                        fecha_evento, 
                        hora_inicio, 
                        hora_fin, 
                        token, 
                        estado, 
                        notas,
                        id_coordinador  -- <-- NUEVO CAMPO
                    ) 
                    VALUES (
                        '$id_cliente', 
                        '$id_tipo_evento', 
                        '$nombre_evento', 
                        '$id_salon', 
                        '$cantidad_personas', 
                        '$fecha_evento', 
                        '$hora_inicio', 
                        '$hora_fin', 
                        '$token', 
                        'Pendiente', 
                        '$nota_admin',
                        '$id_coordinador'  -- <-- NUEVO VALOR
                    )";

    if ($conn->query($sqlReserva)) {
        $id_reserva_nueva = $conn->insert_id;

        // --- REGISTRO DE LOG DE ADMINISTRACIÓN ACTUALIZADO ---
        $id_admin = $_SESSION['admin_id'] ?? 0;
        $accion = "CREAR_INVITACION";
        $tabla = "reservas";
        $descripcion = "Coordinador $nombre_coordinador generó invitación para $nombre_cliente ($nombre_evento) en salón $nombre_salon de $hora_inicio a $hora_fin. ID: $id_reserva_nueva";

        $sqlLog = "INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) 
                   VALUES ('$id_admin', '$accion', '$tabla', '$id_reserva_nueva', '$descripcion')";

        $conn->query($sqlLog);
        // -----------------------------------------------------

        // Redirección de éxito
        header("Location: nueva_invitacion.php?exito=1&token=" . $token);
        exit();
    } else {
        die("Error crítico al crear la reserva: " . $conn->error);
    }
} else {
    header("Location: nueva_invitacion.php");
    exit();
}
?>