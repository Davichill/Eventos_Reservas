<?php

session_start();
include_once __DIR__ . '/../php/conexion.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Obtenemos el ID del administrador de la sesión
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

// Categorías extraídas directamente del PDF de Coffee Break
$categorias_coffee = [
    'SÁNDUCHES Y WRAPS',
    'EMPANADAS Y BOCADOS SALADOS',
    'VEGETARIANO',
    'QUICHES Y SALADOS AL HORNO',
    'PASTELERÍA Y DULCES',
    'GALLETAS Y PEQUEÑOS DULCES'
];

// LÓGICA DE GUARDADO
if (isset($_POST['btnguardar'])) {
    $id = intval($_POST['id']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $cat = $conn->real_escape_string($_POST['categoria']);

    $foto_sql = "";
    $foto_nombre = null; // Inicializamos para evitar errores

    if (!empty($_FILES['foto']['name'])) {
        $foto_nombre = time() . "_" . $_FILES['foto']['name'];
        if (move_uploaded_file($_FILES['foto']['tmp_name'], "../img/menu_coffee/" . $foto_nombre)) {
            $foto_sql = ", imagen_url = '$foto_nombre'";
        }
    }

    if ($id > 0) {
        // --- LOG DE EDICIÓN ---
        $sql = "UPDATE menu_coffee_break SET nombre='$nombre', categoria='$cat' $foto_sql WHERE id=$id";
        if ($conn->query($sql)) {
            registrarLog($conn, $id_admin, 'EDITAR', 'menu_coffee_break', $id, "Editó el bocadito: $nombre (Categoría: $cat)");
        }
    } else {
        // --- LOG DE CREACIÓN ---
        // Ajuste: Si no hay foto, se envía NULL explícito a la BD
        $img_val = ($foto_nombre !== null) ? "'$foto_nombre'" : "NULL";
        $sql = "INSERT INTO menu_coffee_break (nombre, categoria, imagen_url) VALUES ('$nombre', '$cat', $img_val)";
        if ($conn->query($sql)) {
            $nuevo_id = $conn->insert_id;
            registrarLog($conn, $id_admin, 'CREAR', 'menu_coffee_break', $nuevo_id, "Creó nuevo bocadito: $nombre");
        }
    }
    header("Location: gestion_coffee.php?msj=ok");
    exit();
}

// ELIMINAR
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);

    // Obtenemos datos antes de borrar para el log e imagen
    $res = $conn->query("SELECT nombre, imagen_url FROM menu_coffee_break WHERE id=$id");
    if ($reg = $res->fetch_assoc()) {
        $nombre_bocadito = $reg['nombre'];

        if (!empty($reg['imagen_url']) && file_exists("../img/menu_coffee/" . $reg['imagen_url'])) {
            unlink("../img/menu_coffee/" . $reg['imagen_url']);
        }

        // --- LOG DE ELIMINACIÓN ---
        if ($conn->query("DELETE FROM menu_coffee_break WHERE id=$id")) {
            registrarLog($conn, $id_admin, 'ELIMINAR', 'menu_coffee_break', $id, "Eliminó el bocadito: $nombre_bocadito");
        }
    }
    header("Location: gestion_coffee.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión Coffee Break | GO Hotel</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_coffee.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        .filter-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            border-color: #d35400;
            outline: none;
        }

        .category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            border-color: #d35400;
            color: #d35400;
        }

        .filter-btn.active {
            background: #d35400;
            color: #fff;
            border-color: #d35400;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="top-bar">
                <div class="user-info">
                    <span>Bienvenido, <span class="user-name"><?= $_SESSION['admin_nombre'] ?? 'Admin' ?></span></span>
                    <a href="../auth/logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            <?php include 'navbar.php'; ?>

            <?php if (isset($_GET['msj'])): ?>
                <div class="status-message success">
                    ✅ Bocadito guardado correctamente
                </div>
            <?php endif; ?>

            <div class="main-container">
                <div class="header-section">
                    <h1>Gestión de Coffee Break</h1>
                    <div class="header-info">
                        <div class="path-info">/img/menu_coffee/</div>
                        <span>Gestiona los bocaditos para coffee break según categorías del PDF</span>
                    </div>
                </div>

                <div class="form-box">
                    <h3>Registrar Nuevo Bocadito</h3>
                    <form method="POST" enctype="multipart/form-data" class="form-grid">
                        <div class="form-group">
                            <label>Nombre del Bocadito:</label>
                            <input type="text" name="nombre" id="crear_nombre"
                                placeholder="Ej: Mini sándwich de jamón y queso" required
                                title="Ingrese el nombre completo del bocadito">
                        </div>
                        <div class="form-group">
                            <label>Categoría:</label>
                            <select name="categoria" id="crear_cat" required>
                                <option value="">Seleccione categoría...</option>
                                <?php foreach ($categorias_coffee as $c): ?>
                                    <option value="<?= $c ?>"><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Imagen (Opcional):</label>
                            <input type="file" name="foto" accept="image/*"
                                title="Suba una imagen del bocadito (JPG, PNG, GIF)">
                        </div>
                        <input type="hidden" name="id" value="0">
                        <button type="submit" name="btnguardar" class="btn-main">Guardar</button>
                    </form>
                </div>

                <div class="grid-bocaditos">
                    <div class="filter-container">
                        <div class="search-box">
                            <input type="text" id="filter-search" placeholder="🔍 Buscar por nombre...">
                        </div>
                        <div class="category-filters">
                            <button class="filter-btn active" data-filter="all">Todos</button>
                            <?php foreach ($categorias_coffee as $c): ?>
                                <button class="filter-btn" data-filter="<?= $c ?>">
                                    <?= $c ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                    $items = $conn->query("SELECT * FROM menu_coffee_break ORDER BY categoria ASC, nombre ASC");

                    if ($items->num_rows == 0): ?>
                        <div class="empty-state">
                            <h4>No hay bocaditos registrados</h4>
                            <p>Comienza agregando bocaditos usando el formulario superior</p>
                        </div>
                    <?php else:
                        while ($row = $items->fetch_assoc()):
                            $img_src = (!empty($row['imagen_url']) && file_exists("../img/menu_coffee/" . $row['imagen_url']))
                                ? "../img/menu_coffee/" . $row['imagen_url']
                                : null;

                            // Determinar clase CSS según categoría
                            $categoria_class = '';
                            $categoria = strtolower($row['categoria']);
                            if (strpos($categoria, 'sánduch') !== false) {
                                $categoria_class = 'categoria-sandwiches';
                            } elseif (strpos($categoria, 'empanad') !== false) {
                                $categoria_class = 'categoria-empanadas';
                            } elseif (strpos($categoria, 'vegetarian') !== false) {
                                $categoria_class = 'categoria-vegetariano';
                            } elseif (strpos($categoria, 'quich') !== false) {
                                $categoria_class = 'categoria-quiches';
                            } elseif (strpos($categoria, 'pasteler') !== false) {
                                $categoria_class = 'categoria-pasteleria';
                            } elseif (strpos($categoria, 'gallet') !== false) {
                                $categoria_class = 'categoria-galletas';
                            }
                            ?>
                            <div class="card-bocadito <?= $categoria_class ?>">
                                <div class="card-image">
                                    <?php if ($img_src): ?>
                                        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($row['nombre']) ?>"
                                            onerror="this.parentElement.innerHTML='<div class=\'no-image\'><span>☕</span><span>Imagen no disponible</span></div>'">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <span>☕</span>
                                            <span>Sin imagen</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-content">
                                    <div class="badge-container">
                                        <span class="badge-categoria"><?= $row['categoria'] ?></span>
                                    </div>

                                    <h4 class="card-title"><?= htmlspecialchars($row['nombre']) ?></h4>

                                    <div class="card-actions">
                                        <a href="javascript:void(0)" onclick='abrirEditor(<?= json_encode($row) ?>)'
                                            class="btn-card btn-card-edit" title="Editar bocadito">
                                            ✏️ Editar
                                        </a>
                                        <a href="?del=<?= $row['id'] ?>" class="btn-card btn-card-delete"
                                            onclick="return confirm('¿Está seguro de eliminar este bocadito?')"
                                            title="Eliminar bocadito">
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
                    <h3>Editar Bocadito</h3>
                    <form method="POST" enctype="multipart/form-data" id="formEditar">
                        <input type="hidden" name="id" id="edit-id">

                        <div class="form-group">
                            <label>Nombre del Bocadito:</label>
                            <input type="text" name="nombre" id="edit-nombre" required>
                        </div>

                        <div class="form-group">
                            <label>Categoría:</label>
                            <select name="categoria" id="edit-cat" required>
                                <?php foreach ($categorias_coffee as $c): ?>
                                    <option value="<?= $c ?>"><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nueva imagen (opcional):</label>
                            <input type="file" name="foto" accept="image/*">
                            <small style="color:var(--text-light); display:block; margin-top:5px;">
                                Deje en blanco para mantener la imagen actual
                            </small>
                        </div>

                        <button type="submit" name="btnguardar" class="btn-main">Actualizar</button>
                        <button type="button" onclick="cerrarModal()" class="btn-cancel">Cancelar</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function abrirEditor(data) {
            document.getElementById('edit-id').value = data.id;
            document.getElementById('edit-nombre').value = data.nombre;
            document.getElementById('edit-cat').value = data.categoria;

            // Mostrar modal con animación
            const modal = document.getElementById('modalEdit');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Efecto visual
            modal.style.animation = 'none';
            setTimeout(() => {
                modal.style.animation = 'fadeIn 0.3s ease';
            }, 10);
        }

        function cerrarModal() {
            const modal = document.getElementById('modalEdit');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEdit').addEventListener('click', function (e) {
            if (e.target.id === 'modalEdit') {
                cerrarModal();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });

        // Inicializar
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-remover mensajes de estado
            setTimeout(() => {
                const messages = document.querySelectorAll('.status-message');
                messages.forEach(msg => msg.remove());
            }, 3500);

            // Validación del formulario de creación
            document.querySelector('.form-grid').addEventListener('submit', function (e) {
                const nombre = document.getElementById('crear_nombre').value.trim();
                const categoria = document.getElementById('crear_cat').value;

                if (!nombre) {
                    e.preventDefault();
                    alert('Por favor ingrese el nombre del bocadito');
                    document.getElementById('crear_nombre').focus();
                    return false;
                }

                if (!categoria) {
                    e.preventDefault();
                    alert('Por favor seleccione una categoría');
                    document.getElementById('crear_cat').focus();
                    return false;
                }
            });

            // Validación del formulario de edición
            document.getElementById('formEditar').addEventListener('submit', function (e) {
                const nombre = document.getElementById('edit-nombre').value.trim();
                const categoria = document.getElementById('edit-cat').value;

                if (!nombre) {
                    e.preventDefault();
                    alert('Por favor ingrese el nombre del bocadito');
                    document.getElementById('edit-nombre').focus();
                    return false;
                }

                if (!categoria) {
                    e.preventDefault();
                    alert('Por favor seleccione una categoría');
                    document.getElementById('edit-cat').focus();
                    return false;
                }
            });
        });

        // Efecto al cambiar categoría
        document.getElementById('crear_cat').addEventListener('change', function () {
            if (this.value) {
                this.style.borderColor = 'var(--accent-color)';
                setTimeout(() => {
                    this.style.borderColor = '';
                }, 500);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('filter-search');
            const filterButtons = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.card-bocadito');

            function filterItems() {
                const searchText = searchInput.value.toLowerCase();
                const activeCategory = document.querySelector('.filter-btn.active').dataset.filter;

                cards.forEach(card => {
                    const cardName = card.querySelector('.card-title').textContent.toLowerCase();
                    const cardCategory = card.querySelector('.badge-categoria').textContent;

                    const matchesSearch = cardName.includes(searchText);
                    const matchesCategory = (activeCategory === 'all' || cardCategory === activeCategory);

                    if (matchesSearch && matchesCategory) {
                        card.style.display = 'block';
                        card.style.animation = 'fadeIn 0.4s ease forwards';
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Mostrar mensaje si no hay resultados
                checkEmptyResults();
            }

            function checkEmptyResults() {
                let visibleCards = [...cards].filter(c => c.style.display !== 'none').length;
                let emptyMsg = document.querySelector('.no-results-msg');

                if (visibleCards === 0) {
                    if (!emptyMsg) {
                        const grid = document.querySelector('.grid-bocaditos');
                        grid.insertAdjacentHTML('afterend', '<div class="no-results-msg" style="text-align:center; padding:20px; color:#777;">No se encontraron bocaditos con esos filtros.</div>');
                    }
                } else if (emptyMsg) {
                    emptyMsg.remove();
                }
            }

            // Evento para búsqueda
            searchInput.addEventListener('input', filterItems);

            // Evento para botones de categoría
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    filterItems();
                });
            });
        });
    </script>

</body>

</html>