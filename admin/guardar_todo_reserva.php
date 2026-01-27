<?php
include '../php/conexion.php';
session_start();

// Verificar sesión admin
if (!isset($_SESSION['admin'])) {
    echo "Error: No autorizado";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. RECOLECTAR DATOS ---
    $id_reserva = intval($_POST['id_reserva']);

    // Datos Evento
    $fecha = $_POST['fecha_evento'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $pax = intval($_POST['cantidad_personas']);

    // Datos Cliente
    $cliente_nombre = $conn->real_escape_string($_POST['cliente_nombre']);
    $razon_social = $conn->real_escape_string($_POST['razon_social']);
    $identificacion = $conn->real_escape_string($_POST['identificacion']);
    $telefono = $conn->real_escape_string($_POST['cliente_telefono']);

    // Datos Montaje
    $id_mesa = !empty($_POST['id_mesa']) ? intval($_POST['id_mesa']) : "NULL";
    $manteleria = $conn->real_escape_string($_POST['manteleria']);
    $servilleta = $conn->real_escape_string($_POST['color_servilleta']);
    $it = $conn->real_escape_string($_POST['equipos_audiovisuales']);

    // Datos Menú
    $lista_platos = isset($_POST['platos_finales']) ? json_decode($_POST['platos_finales'], true) : [];

    // --- 2. INICIAR TRANSACCIÓN ---
    $conn->begin_transaction();

    try {
        // A. Obtener el ID del cliente vinculado
        $sql_get_client = "SELECT id_cliente FROM reservas WHERE id = $id_reserva";
        $res_client = $conn->query($sql_get_client);
        if ($row = $res_client->fetch_assoc()) {
            $id_cliente = $row['id_cliente'];

            // B. Actualizar tabla CLIENTES
            $sql_update_cliente = "UPDATE clientes SET 
                cliente_nombre = '$cliente_nombre',
                razon_social = '$razon_social',
                identificacion = '$identificacion',
                cliente_telefono = '$telefono'
                WHERE id = $id_cliente";

            if (!$conn->query($sql_update_cliente)) {
                throw new Exception("Error actualizando cliente: " . $conn->error);
            }
        }

        // C. Actualizar tabla RESERVAS
        $sql_update_reserva = "UPDATE reservas SET 
            fecha_evento = '$fecha',
            hora_inicio = '$hora_inicio',
            hora_fin = '$hora_fin',
            cantidad_personas = $pax,
            id_mesa = $id_mesa,
            manteleria = '$manteleria',
            color_servilleta = '$servilleta',
            equipos_audiovisuales = '$it'
            WHERE id = $id_reserva";

        if (!$conn->query($sql_update_reserva)) {
            throw new Exception("Error actualizando reserva: " . $conn->error);
        }

        // D. Actualizar Platos
        $conn->query("DELETE FROM reserva_detalles_menu WHERE id_reserva = $id_reserva");

        if (!empty($lista_platos) && is_array($lista_platos)) {
            $stmt = $conn->prepare("INSERT INTO reserva_detalles_menu (id_reserva, nombre_plato) VALUES (?, ?)");
            foreach ($lista_platos as $plato) {
                if (trim($plato) !== "") {
                    $stmt->bind_param("is", $id_reserva, $plato);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // --- E. REGISTRAR LOG DE EDICIÓN (AQUÍ ESTÁ EL CAMBIO) ---
        $id_admin = $_SESSION['admin_id']; // Asegúrate de que esta variable exista en tu login
        $nombre_admin = $_SESSION['admin'];
        $accion = "EDITAR";
        $tabla = "reservas";
        $descripcion = "El administrador $nombre_admin editó los datos de la reserva #$id_reserva y sus detalles de menú.";

        $sql_log = "INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) 
                    VALUES ('$id_admin', '$accion', '$tabla', '$id_reserva', '$descripcion')";

        if (!$conn->query($sql_log)) {
            throw new Exception("Error registrando log: " . $conn->error);
        }

        // --- 3. COMMIT ---
        $conn->commit();
        echo "success";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

} else {
    echo "Solicitud inválida";
}
?>