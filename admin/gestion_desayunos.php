<?php
session_start();
include_once __DIR__ . '/../php/conexion.php';

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

$categorias_permitidas = [
    "DESAYUNO SALUDABLE",
    "DESAYUNO AMERICANO",
    "DESAYUNO ESPECIAL",
    "DESAYUNO ECUATORIANO"
];

// 1. LÓGICA DE ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Obtenemos datos antes de borrar para el historial
    $res = $conn->query("SELECT nombre, imagen_url FROM menu_desayunos WHERE id = $id");
    if ($img = $res->fetch_assoc()) {
        $nombre_desayuno = $img['nombre'];

        // Borrar archivo físico
        if (!empty($img['imagen_url']) && file_exists("../img/menu_desayuno/" . $img['imagen_url'])) {
            unlink("../img/menu_desayuno/" . $img['imagen_url']);
        }

        // --- LOG DE ELIMINACIÓN ---
        if ($conn->query("DELETE FROM menu_desayunos WHERE id = $id")) {
            registrarLog($conn, $id_admin, 'ELIMINAR', 'menu_desayunos', $id, "Eliminó el menú de desayuno: $nombre_desayuno");
        }
    }

    header("Location: gestion_desayunos.php?msj=del");
    exit();
}

// 2. LÓGICA DE GUARDAR / EDITAR
if (isset($_POST['btnguardar'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);

    $foto_sql = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto_nombre = time() . "_" . $_FILES['foto']['name'];
        if (move_uploaded_file($_FILES['foto']['tmp_name'], "../img/menu_desayuno/" . $foto_nombre)) {
            $foto_sql = ", imagen_url = '$foto_nombre'";
        }
    }

    if ($id > 0) {
        // --- LOG DE EDICIÓN ---
        $sql = "UPDATE menu_desayunos SET nombre = '$nombre', descripcion = '$descripcion' $foto_sql WHERE id = $id";
        if ($conn->query($sql)) {
            registrarLog($conn, $id_admin, 'EDITAR', 'menu_desayunos', $id, "Actualizó detalles del menú: $nombre");
        }
    } else {
        // --- LOG DE CREACIÓN ---
        $img_final = (!empty($foto_nombre)) ? "'$foto_nombre'" : "NULL";
        $sql = "INSERT INTO menu_desayunos (nombre, categoria, descripcion, imagen_url, estado) 
                VALUES ('$nombre', '$nombre', '$descripcion', $img_final, 1)";
        if ($conn->query($sql)) {
            $nuevo_id = $conn->insert_id;
            registrarLog($conn, $id_admin, 'CREAR', 'menu_desayunos', $nuevo_id, "Creó nuevo menú de desayuno: $nombre");
        }
    }

    header("Location: gestion_desayunos.php?msj=ok");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión Desayunos | GO Hotel</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_desayuno.css">

</head>

<body>
    <?php include 'navbar.php'; ?>

    <?php if (isset($_GET['msj'])): ?>
        <div class="status-message <?= $_GET['msj'] === 'ok' ? 'success' : 'error' ?>">
            <?= $_GET['msj'] === 'ok' ? '✅ Menú guardado correctamente' : '🗑️ Menú eliminado exitosamente' ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <div class="header-section">
            <h1>Gestión de Desayunos</h1>
            <div class="header-info">
                <div class="path-info">/img/menu_desayuno/</div>

            </div>
        </div>

        <!-- Formulario principal -->
        <div class="form-box">
            <h3>Registrar Nuevo Menú</h3>
            <form method="POST" enctype="multipart/form-data" id="formDesayuno" class="form-grid">
                <input type="hidden" name="id" id="input_id" value="0">

                <div class="form-group">
                    <label>Tipo de Desayuno:</label>
                    <select name="nombre" id="input_nombre" required>
                        <option value="">Seleccione tipo...</option>
                        <?php foreach ($categorias_permitidas as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Imagen (Opcional):</label>
                    <input type="file" name="foto" accept="image/*" title="Suba una imagen representativa del desayuno">
                    <span class="file-info">Formatos: JPG, PNG, GIF. Tamaño máximo: 2MB</span>
                </div>

                <div class="form-group full-width">
                    <label>Descripción del Menú (Componentes):</label>
                    <textarea name="descripcion" id="input_descripcion" placeholder="Ej: 
• Jugo de fruta natural
• Huevos revueltos
• Pan artesanal
• Café o té
• Fruta fresca" required></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" name="btnguardar" id="btn-submit" class="btn-main">
                        Guardar Menú
                    </button>
                    <button type="button" onclick="cancelarEdicion()" id="btn-cancelar" class="btn-cancel"
                        style="display: none;">
                        Cancelar Edición
                    </button>
                </div>
            </form>
        </div>

        <!-- Listado de menús -->
        <div class="grid-menus">
            <?php
            $res = $conn->query("SELECT * FROM menu_desayunos ORDER BY id DESC");

            if ($res->num_rows === 0): ?>
                <div class="empty-state">
                    <h4>No hay menús de desayuno registrados</h4>
                    <p>Comienza agregando menús usando el formulario superior</p>
                </div>
            <?php else:
                while ($row = $res->fetch_assoc()):
                    $img_path = (!empty($row['imagen_url']) && file_exists("../img/menu_desayuno/" . $row['imagen_url']))
                        ? "../img/menu_desayuno/" . $row['imagen_url']
                        : null;

                    // Determinar clase CSS según tipo de desayuno
                    $tipo_class = '';
                    $tipo = strtolower($row['nombre']);
                    if (strpos($tipo, 'saludable') !== false) {
                        $tipo_class = 'tipo-saludable';
                    } elseif (strpos($tipo, 'americano') !== false) {
                        $tipo_class = 'tipo-americano';
                    } elseif (strpos($tipo, 'especial') !== false) {
                        $tipo_class = 'tipo-especial';
                    } elseif (strpos($tipo, 'ecuatoriano') !== false) {
                        $tipo_class = 'tipo-ecuatoriano';
                    }
                    ?>
                    <div class="card-menu <?= $tipo_class ?>">
                        <div class="card-image">
                            <?php if ($img_path): ?>
                                <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($row['nombre']) ?>"
                                    onerror="this.parentElement.innerHTML='<div class=\'no-image\'><span>🍳</span><span>Imagen no disponible</span></div>'">
                            <?php else: ?>
                                <div class="no-image">
                                    <span>🍳</span>
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-content">
                            <div class="badge-container">
                                <span class="badge-tipo"><?= $row['nombre'] ?></span>
                            </div>

                            <div class="card-descripcion">
                                <?= nl2br(htmlspecialchars($row['descripcion'])) ?>
                            </div>

                            <div class="card-actions">
                                <a href="javascript:void(0)" onclick='editarMenu(<?= json_encode($row) ?>)'
                                    class="btn-card btn-card-edit" title="Editar menú">
                                    ✏️ Editar
                                </a>
                                <a href="?eliminar=<?= $row['id'] ?>" class="btn-card btn-card-delete"
                                    onclick="return confirm('¿Está seguro de eliminar este menú?')" title="Eliminar menú">
                                    🗑️ Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            endif; ?>
        </div>
    </div>

    <script>
        function editarMenu(data) {
            document.getElementById('input_id').value = data.id;
            document.getElementById('input_nombre').value = data.nombre;
            document.getElementById('input_descripcion').value = data.descripcion;

            // Cambiar texto y color del botón
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '💾 Actualizar Menú';
            btnSubmit.style.background = 'linear-gradient(135deg, var(--success-color), #219653)';

            // Mostrar botón cancelar
            document.getElementById('btn-cancelar').style.display = 'flex';

            // Scroll suave al formulario
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Efecto visual en el formulario
            const formBox = document.querySelector('.form-box');
            formBox.style.borderColor = 'var(--accent-color)';
            formBox.style.boxShadow = '0 0 0 3px rgba(211, 84, 0, 0.15)';

            setTimeout(() => {
                formBox.style.borderColor = 'transparent';
                formBox.style.boxShadow = '';
            }, 1500);
        }

        function cancelarEdicion() {
            document.getElementById('input_id').value = "0";
            document.getElementById('formDesayuno').reset();

            // Restaurar botón principal
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '💾 Guardar Menú';
            btnSubmit.style.background = 'linear-gradient(135deg, var(--primary-color), var(--primary-dark))';

            // Ocultar botón cancelar
            document.getElementById('btn-cancelar').style.display = 'none';
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-remover mensajes de estado
            setTimeout(() => {
                const messages = document.querySelectorAll('.status-message');
                messages.forEach(msg => msg.remove());
            }, 3500);

            // Validación del formulario
            document.getElementById('formDesayuno').addEventListener('submit', function (e) {
                const nombre = document.getElementById('input_nombre').value;
                const descripcion = document.getElementById('input_descripcion').value.trim();

                if (!nombre) {
                    e.preventDefault();
                    alert('Por favor seleccione el tipo de desayuno');
                    document.getElementById('input_nombre').focus();
                    return false;
                }

                if (!descripcion) {
                    e.preventDefault();
                    alert('Por favor ingrese la descripción del menú');
                    document.getElementById('input_descripcion').focus();
                    return false;
                }
            });

            // Efecto al cambiar tipo de desayuno
            document.getElementById('input_nombre').addEventListener('change', function () {
                if (this.value) {
                    this.style.borderColor = 'var(--accent-color)';
                    setTimeout(() => {
                        this.style.borderColor = '';
                    }, 500);
                }
            });
        });
    </script>

</body>

</html>