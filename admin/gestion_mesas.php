<?php
include_once __DIR__ . '/../php/conexion.php';
session_start();

// Verificación de sesión
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$id_admin = $_SESSION['admin_id'];

/**
 * Función para registrar movimientos en la tabla de auditoría
 */
function registrarLog($conn, $id_admin, $accion, $tabla, $id_registro, $descripcion)
{
    $accion = $conn->real_escape_string($accion);
    $tabla = $conn->real_escape_string($tabla);
    $descripcion = $conn->real_escape_string($descripcion);

    $sql = "INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) 
            VALUES ('$id_admin', '$accion', '$tabla', '$id_registro', '$descripcion')";
    $conn->query($sql);
}

// --- CONFIGURACIÓN DE RUTA ESPECÍFICA ---
$carpeta_destino = "../img/mesas/";

if (!file_exists($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

// --- LÓGICA CRUD ---
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = $conn->real_escape_string($_POST['nombre']);

    $imagen_sql = "";
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombre_img = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['imagen']['name']);
        $ruta_final = $carpeta_destino . $nombre_img;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_final)) {
            $imagen_sql = ", imagen_url = '$nombre_img'";
        }
    }

    if ($id > 0) {
        // --- LOG DE EDICIÓN ---
        $sql = "UPDATE mesas SET nombre='$nombre' $imagen_sql WHERE id=$id";
        if ($conn->query($sql)) {
            registrarLog($conn, $id_admin, 'EDITAR', 'mesas', $id, "Actualizó la configuración de la mesa: $nombre");
        }
    } else {
        // --- LOG DE CREACIÓN ---
        $img_val = $imagen_sql ? str_replace(", imagen_url = ", "", $imagen_sql) : "NULL";
        $sql = "INSERT INTO mesas (nombre, imagen_url) VALUES ('$nombre', $img_val)";
        if ($conn->query($sql)) {
            $nuevo_id = $conn->insert_id;
            registrarLog($conn, $id_admin, 'CREAR', 'mesas', $nuevo_id, "Registró una nueva mesa: $nombre");
        }
    }

    header("Location: gestion_mesas.php?res=ok");
    exit();
}

if (isset($_GET['del'])) {
    $id = intval($_GET['del']);

    // Obtener datos antes de borrar para el log y para borrar la imagen
    $res = $conn->query("SELECT nombre, imagen_url FROM mesas WHERE id=$id");
    if ($img_data = $res->fetch_assoc()) {
        $nombre_mesa = $img_data['nombre'];

        if ($img_data['imagen_url'] && file_exists($carpeta_destino . $img_data['imagen_url'])) {
            unlink($carpeta_destino . $img_data['imagen_url']);
        }

        // --- LOG DE ELIMINACIÓN ---
        if ($conn->query("DELETE FROM mesas WHERE id=$id")) {
            registrarLog($conn, $id_admin, 'ELIMINAR', 'mesas', $id, "Eliminó la configuración de mesa: $nombre_mesa");
        }
    }

    header("Location: gestion_mesas.php?res=del");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Mesas | GO Quito</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_mesas.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <?php if (isset($_GET['res'])): ?>
        <div class="status-message <?= $_GET['res'] === 'ok' ? 'success' : 'error' ?>">
            <?= $_GET['res'] === 'ok' ? '✅ Mesa guardada correctamente' : '🗑️ Mesa eliminada exitosamente' ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <div class="header-section">
            <h1>Administración de Mesas</h1>
            <div class="header-info">
                <div class="path-info">/img/mesas/</div>
            </div>
        </div>

        <div class="form-box">
            <h3>Registrar Nueva Mesa</h3>
            <form method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label>Nombre de la mesa:</label>
                    <input type="text" name="nombre" id="crear_nombre"
                        placeholder="Ej: Mesa Imperial, Mesa Redonda (10 pax)" required>
                </div>
                <div class="form-group">
                    <label>Imagen de la mesa:</label>
                    <input type="file" name="imagen" accept="image/*" required>
                </div>
                <input type="hidden" name="id" value="0">
                <button type="submit" name="guardar" class="btn-main">Guardar Mesa</button>
            </form>
        </div>

        <div class="grid-platos">
            <?php
            $mesas = $conn->query("SELECT * FROM mesas ORDER BY id DESC");

            if ($mesas->num_rows === 0): ?>
                <div class="empty-state">
                    <h4>No hay mesas registradas</h4>
                    <p>Comienza agregando configuraciones de mesas usando el formulario.</p>
                </div>
            <?php else:
                while ($m = $mesas->fetch_assoc()):
                    $img_src = $carpeta_destino . $m['imagen_url'];
                    $img_exists = (!empty($m['imagen_url']) && file_exists($img_src));
                    ?>
                    <div class="card">
                        <div class="card-image">
                            <?php if ($img_exists): ?>
                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($m['nombre']) ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <span>🪑</span>
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-content">
                            <h4 class="card-title"><?= htmlspecialchars($m['nombre']) ?></h4>
                            <div class="card-actions">
                                <a href="javascript:void(0)" onclick='abrirEditor(<?= json_encode($m) ?>)'
                                    class="btn-card btn-card-edit">
                                    ✏️ Editar
                                </a>
                                <a href="?del=<?= $m['id'] ?>" class="btn-card btn-card-delete"
                                    onclick="return confirm('¿Eliminar esta configuración de mesa?')">
                                    🗑️ Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            endif; ?>
        </div>
    </div>

    <div id="modalEdit">
        <div class="modal-content">
            <h3>Editar Mesa</h3>
            <form method="POST" enctype="multipart/form-data" id="formEditar">
                <input type="hidden" name="id" id="edit-id">

                <div class="form-group">
                    <label>Nombre de la mesa:</label>
                    <input type="text" name="nombre" id="edit-nombre" required>
                </div>

                <div class="form-group">
                    <label>Cambiar imagen (opcional):</label>
                    <input type="file" name="imagen" accept="image/*">
                    <small style="color:gray; display:block; margin-top:5px;">Deje en blanco para mantener la
                        actual</small>
                </div>

                <button type="submit" name="guardar" class="btn-main">Actualizar</button>
                <button type="button" onclick="cerrarModal()" class="btn-cancel">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function abrirEditor(data) {
            document.getElementById('edit-id').value = data.id;
            document.getElementById('edit-nombre').value = data.nombre;

            const modal = document.getElementById('modalEdit');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal() {
            const modal = document.getElementById('modalEdit');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera o ESC
        window.onclick = function (event) {
            if (event.target == document.getElementById('modalEdit')) cerrarModal();
        }
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarModal(); });

        // Auto-remover mensajes
        setTimeout(() => {
            const messages = document.querySelectorAll('.status-message');
            messages.forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>

</html>