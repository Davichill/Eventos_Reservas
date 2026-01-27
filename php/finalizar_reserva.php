<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Captura de identificadores
    $token = $conn->real_escape_string($_POST['token'] ?? '');
    $id_reserva = intval($_POST['id_reserva'] ?? 0);

    // Obtener id_cliente para actualizar datos fiscales
    $res_cliente = $conn->query("SELECT id_cliente FROM reservas WHERE token = '$token'");
    $fila_cliente = $res_cliente->fetch_assoc();
    $id_cliente = $fila_cliente['id_cliente'] ?? 0;

    if ($id_cliente == 0) {
        die("Error: No se encontró la reserva o el cliente.");
    }

    // 2. Datos Fiscales (Tabla clientes)
    $identificacion = $conn->real_escape_string($_POST['identificacion'] ?? '');
    $razon_social = $conn->real_escape_string($_POST['razon_social'] ?? '');
    $representante_legal = $conn->real_escape_string($_POST['representante_legal'] ?? '');
    $direccion_fiscal = $conn->real_escape_string($_POST['direccion_fiscal'] ?? '');
    $correo_facturacion = $conn->real_escape_string($_POST['correo_facturacion'] ?? '');

    // 3. Datos Operativos y Validación de Mesa
    // CAPTURA CORRECTA DE ID_MESA PARA EVITAR VARIABLE INDEFINIDA
    $id_mesa_input = isset($_POST['id_mesa']) ? intval($_POST['id_mesa']) : 0;

    // VALIDACIÓN DE LLAVE FORÁNEA: Si el ID no existe en la tabla 'mesas', enviamos NULL
    $check_mesa = $conn->query("SELECT id FROM mesas WHERE id = $id_mesa_input");
    $valor_mesa_sql = ($check_mesa->num_rows > 0) ? $id_mesa_input : "NULL";

    $firma_nombre = $conn->real_escape_string($_POST['firma_nombre'] ?? '');
    $firma_identificacion = $conn->real_escape_string($_POST['firma_identificacion'] ?? '');
    $contacto_nom = $conn->real_escape_string($_POST['contacto_evento_nombre'] ?? '');
    $contacto_tel = $conn->real_escape_string($_POST['contacto_evento_telefono'] ?? '');
    $hora_inicio = $conn->real_escape_string($_POST['hora_inicio'] ?? '');
    $hora_fin = $conn->real_escape_string($_POST['hora_fin'] ?? '');
    $manteleria = $conn->real_escape_string($_POST['manteleria'] ?? '');
    $color_servilleta = $conn->real_escape_string($_POST['color_servilleta'] ?? '');
    $logistica = $conn->real_escape_string($_POST['logistica'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $equipos = $conn->real_escape_string($_POST['equipos_audiovisuales'] ?? '');
    $menu_paquete = $conn->real_escape_string($_POST['menu_opcion'] ?? 'No especificado');

    // 4. Manejo de Planimetría
    $planimetria_url = "";
    if (isset($_FILES['planimetria']) && $_FILES['planimetria']['error'] === 0) {
        $ruta = "../uploads/";
        if (!file_exists($ruta)) {
            mkdir($ruta, 0777, true);
        }
        $nombre_archivo = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['planimetria']['name']);
        if (move_uploaded_file($_FILES['planimetria']['tmp_name'], $ruta . $nombre_archivo)) {
            $planimetria_url = $nombre_archivo;
        }
    }

    // INICIO DE TRANSACCIÓN SQL
    $conn->begin_transaction();

    try {
        // ACTUALIZACIÓN CLIENTE
        $conn->query("UPDATE clientes SET identificacion='$identificacion', razon_social='$razon_social', representante_legal='$representante_legal', direccion_fiscal='$direccion_fiscal', correo_facturacion='$correo_facturacion' WHERE id=$id_cliente");

        // ACTUALIZACIÓN RESERVA (CORREGIDO: id_mesa sin comillas si es NULL)
        $sql_reserva = "UPDATE reservas SET 
                        estado = 'Confirmada', 
                        id_mesa = $valor_mesa_sql, 
                        firma_nombre = '$firma_nombre', 
                        firma_identificacion = '$firma_identificacion',
                        contacto_evento_nombre = '$contacto_nom',
                        contacto_evento_telefono = '$contacto_tel',
                        hora_inicio = '$hora_inicio',
                        hora_fin = '$hora_fin',
                        manteleria = '$manteleria',
                        color_servilleta = '$color_servilleta',
                        logistica = '$logistica',
                        observaciones = '$observaciones',
                        equipos_audiovisuales = '$equipos',
                        menu_opcion = '$menu_paquete'";

        if ($planimetria_url) {
            $sql_reserva .= ", planimetria_url = '$planimetria_url'";
        }
        $sql_reserva .= " WHERE token = '$token'";

        if (!$conn->query($sql_reserva)) {
            throw new Exception("Error al actualizar reserva: " . $conn->error);
        }

        // 5. MANEJO DE PLATOS
        $conn->query("DELETE FROM reserva_detalles_menu WHERE id_reserva = $id_reserva");
        $platos = isset($_POST['bocaditos']) && is_array($_POST['bocaditos']) ? $_POST['bocaditos'] : [];

        if (!empty($platos)) {
            $stmt_menu = $conn->prepare("INSERT INTO reserva_detalles_menu (id_reserva, nombre_plato) VALUES (?, ?)");
            foreach ($platos as $plato) {
                $stmt_menu->bind_param("is", $id_reserva, $plato);
                $stmt_menu->execute();
            }
            $stmt_menu->close();
        }

        $conn->commit();

        // MENSAJE DE ÉXITO VISUAL
        echo "<html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family:sans-serif; text-align:center; padding:50px; background:#f4f7f9;'>
                <div style='background:white; padding:40px; border-radius:15px; display:inline-block; box-shadow:0 4px 15px rgba(0,0,0,0.1);'>
                    <h1 style='color:#2e7d32; font-size:40px; margin-bottom:10px;'>✔</h1>
                    <h2 style='color:#333;'>¡Confirmación Exitosa!</h2>
                    <p style='color:#666;'>Los datos del evento han sido registrados correctamente.</p>
                    <p style='margin-top:20px; font-size:12px; color:#aaa;'>Ya puede cerrar esta ventana.</p>
                </div>
              </body></html>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error crítico: " . $e->getMessage();
    }
}
?>