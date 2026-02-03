<?php
include_once __DIR__ . '/../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Obtenemos el ID del admin de la sesión para los logs
$id_admin = $_SESSION['admin_id'];

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
$carpeta_destino = "../img/menu_almuerzo/";

if (!file_exists($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

// --- LÓGICA CRUD ---
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $tiempo = $_POST['tiempo'];
    $subcategoria = $_POST['subcategoria'];

    $imagen_sql = "";
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombre_img = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['imagen']['name']);
        $ruta_final = $carpeta_destino . $nombre_img;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_final)) {
            $imagen_sql = ", imagen_url = '$nombre_img'";
        }
    }

    if ($id > 0) {
        $sql = "UPDATE menu_almuerzo_cena SET nombre='$nombre', tiempo='$tiempo', subcategoria='$subcategoria' $imagen_sql WHERE id=$id";
        if ($conn->query($sql)) {
            registrarLog($conn, $id_admin, 'EDITAR', 'menu_almuerzo_cena', $id, "Editó el plato: $nombre (Tiempo: $tiempo)");
        }
    } else {
        // Ajuste para permitir imagen NULL si no se sube nada
        $img_val = $imagen_sql ? "'" . str_replace(", imagen_url = '", "", rtrim($imagen_sql, "'")) . "'" : "NULL";
        $sql = "INSERT INTO menu_almuerzo_cena (nombre, tiempo, subcategoria, imagen_url, estado) 
                VALUES ('$nombre', '$tiempo', '$subcategoria', $img_val, 1)";
        if ($conn->query($sql)) {
            $nuevo_id = $conn->insert_id;
            registrarLog($conn, $id_admin, 'CREAR', 'menu_almuerzo_cena', $nuevo_id, "Creó nuevo plato: $nombre en $subcategoria");
        }
    }
    header("Location: gestion_almuerzo.php?res=ok");
    exit();
}

if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $res = $conn->query("SELECT nombre, imagen_url FROM menu_almuerzo_cena WHERE id=$id");
    $data = $res->fetch_assoc();
    $nombre_plato = $data['nombre'];

    if ($data['imagen_url'] && file_exists($carpeta_destino . $data['imagen_url'])) {
        unlink($carpeta_destino . $data['imagen_url']);
    }

    if ($conn->query("DELETE FROM menu_almuerzo_cena WHERE id=$id")) {
        registrarLog($conn, $id_admin, 'ELIMINAR', 'menu_almuerzo_cena', $id, "Eliminó el plato: $nombre_plato");
    }
    header("Location: gestion_almuerzo.php?res=del");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión Almuerzos | GO Quito</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_almuerzo.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-dashboard {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group-main {
            flex: 1;
            min-width: 250px;
        }

        .filter-group-options {
            display: flex;
            gap: 15px;
            flex: 2;
        }

        .filter-select-wrapper {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
        }

        #filter-search,
        #filter-tiempo,
        #filter-sub {
            padding: 10px 15px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s;
        }

        #filter-search:focus {
            border-color: #d35400;
        }

        .filter-stats {
            font-size: 0.85rem;
            color: #777;
            font-weight: bold;
            padding-bottom: 10px;
        }

        .card {
            transition: transform 0.3s, opacity 0.3s;
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

            <?php if (isset($_GET['res'])): ?>
                <div class="status-message <?= $_GET['res'] === 'ok' ? 'success' : 'error' ?>">
                    <?= $_GET['res'] === 'ok' ? '✅ Plato guardado correctamente' : '🗑️ Plato eliminado exitosamente' ?>
                </div>
            <?php endif; ?>

            <div class="main-container">
                <div class="header-section">
                    <h1>Administración de Almuerzos y Cenas</h1>
                    <div class="header-info">
                        <div class="path-info">/img/menu_almuerzo/</div>
                    </div>
                </div>

                <div class="form-box">
                    <h3>Registrar Nuevo Plato</h3>
                    <form method="POST" enctype="multipart/form-data" class="form-grid">
                        <div class="form-group">
                            <label>Nombre del plato:</label>
                            <input type="text" name="nombre" id="crear_nombre"
                                placeholder="Ej: Lomo Stroganoff, Ceviche Mixto, etc." required
                                title="Ingrese el nombre completo del plato">
                        </div>
                        <div class="form-group">
                            <label>Tiempo:</label>
                            <select name="tiempo" id="main-tiempo" onchange="cambiarSub(this.value, 'main-sub')"
                                required>
                                <option value="">Seleccione tiempo...</option>
                                <option value="Entradas">🍽️ Entradas</option>
                                <option value="Plato Fuerte">🍖 Plato Fuerte</option>
                                <option value="Postres">🍰 Postres</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subcategoría:</label>
                            <select name="subcategoria" id="main-sub" required>
                                <option value="">Primero elija tiempo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Imagen (Opcional):</label>
                            <input type="file" name="imagen" accept="image/*"
                                title="Suba una imagen del plato (JPG, PNG, GIF)">
                        </div>
                        <input type="hidden" name="id" value="0">
                        <button type="submit" name="guardar" class="btn-main">Guardar</button>
                    </form>
                </div>

                <div class="filter-dashboard">
                    <div class="filter-group-main">
                        <input type="text" id="filter-search" placeholder="🔍 Buscar plato por nombre...">
                    </div>

                    <div class="filter-group-options">
                        <div class="filter-select-wrapper">
                            <label>Tiempo:</label>
                            <select id="filter-tiempo">
                                <option value="all">Todos los tiempos</option>
                                <option value="Entradas">🍽️ Entradas</option>
                                <option value="Plato Fuerte">🍖 Plato Fuerte</option>
                                <option value="Postres">🍰 Postres</option>
                            </select>
                        </div>

                        <div class="filter-select-wrapper">
                            <label>Subcategoría:</label>
                            <select id="filter-sub">
                                <option value="all">Todas las subcategorías</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-stats">
                        Mostrando <span id="count-visible">0</span> platos
                    </div>
                </div>
                <div class="grid-platos">
                    <?php
                    $platos = $conn->query("SELECT * FROM menu_almuerzo_cena ORDER BY tiempo, subcategoria");

                    if ($platos->num_rows === 0): ?>
                        <div class="empty-state">
                            <h4>No hay platos registrados</h4>
                            <p>Comienza agregando platos usando el formulario superior</p>
                        </div>
                    <?php else:
                        while ($p = $platos->fetch_assoc()):
                            $img_src = $carpeta_destino . $p['imagen_url'];
                            $img_exists = !empty($p['imagen_url']) && file_exists($img_src);
                            $tiempo_class = '';
                            if ($p['tiempo'] == 'Entradas') {
                                $tiempo_class = 'tiempo-entradas';
                            } elseif ($p['tiempo'] == 'Plato Fuerte') {
                                $tiempo_class = 'tiempo-plato';
                            } else {
                                $tiempo_class = 'tiempo-postres';
                            }
                            ?>
                            <div class="card <?= $tiempo_class ?>">
                                <div class="card-image">
                                    <?php if ($img_exists): ?>
                                        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"
                                            onerror="this.parentElement.innerHTML='<div class=\'no-image\'><span>🍽️</span><span>Imagen no disponible</span></div>'">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <span>🍽️</span>
                                            <span>Sin imagen</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-content">
                                    <div class="badge-container">
                                        <span class="badge-tiempo"><?= $p['tiempo'] ?></span>
                                        <span class="badge-subcategoria"><?= $p['subcategoria'] ?></span>
                                    </div>

                                    <h4 class="card-title"><?= htmlspecialchars($p['nombre']) ?></h4>

                                    <div class="card-actions">
                                        <a href="javascript:void(0)" onclick='abrirEditor(<?= json_encode($p) ?>)'
                                            class="btn-card btn-card-edit" title="Editar plato">
                                            ✏️ Editar
                                        </a>
                                        <a href="?del=<?= $p['id'] ?>" class="btn-card btn-card-delete"
                                            onclick="return confirm('¿Está seguro de eliminar este plato?')"
                                            title="Eliminar plato">
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
                    <h3>Editar Plato</h3>
                    <form method="POST" enctype="multipart/form-data" id="formEditar">
                        <input type="hidden" name="id" id="edit-id">

                        <div class="form-group">
                            <label>Nombre del plato:</label>
                            <input type="text" name="nombre" id="edit-nombre" required>
                        </div>

                        <div class="form-group">
                            <label>Tiempo:</label>
                            <select name="tiempo" id="edit-tiempo" onchange="cambiarSub(this.value, 'edit-sub', '')"
                                required>
                                <option value="Entradas">🍽️ Entradas</option>
                                <option value="Plato Fuerte">🍖 Plato Fuerte</option>
                                <option value="Postres">🍰 Postres</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subcategoría:</label>
                            <select name="subcategoria" id="edit-sub" required>
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nueva imagen (opcional):</label>
                            <input type="file" name="imagen" accept="image/*">
                            <small style="color:var(--text-light); display:block; margin-top:5px;">
                                Deje en blanco para mantener la imagen actual
                            </small>
                        </div>

                        <button type="submit" name="guardar" class="btn-main">Actualizar</button>
                        <button type="button" onclick="cerrarModal()" class="btn-cancel">Cancelar</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const subcats = {
            "Entradas": ["ENSALADAS", "CEVICHES Y CAUSAS", "ENTRADAS Y CREMAS"],
            "Plato Fuerte": ["CARNES Y ESPECIALIDADES", "POLLO", "MARISCOS", "VEGETARIANOS"],
            "Postres": ["TORTAS Y PASTELES", "CREMOSOS", "POSTRES HORNEADOS", "POSTRES LIGEROS Y FRUTALES"]
        };

        function cambiarSub(tiempo, targetId, selectedValue = "") {
            const select = document.getElementById(targetId);
            select.innerHTML = '';

            if (subcats[tiempo]) {
                const options = subcats[tiempo];
                const defaultOption = new Option('Seleccione subcategoría...', '', true, true);
                defaultOption.disabled = true;
                defaultOption.selected = true;
                select.add(defaultOption);

                options.forEach(item => {
                    const option = new Option(item, item);
                    select.add(option);
                });

                if (selectedValue && options.includes(selectedValue)) {
                    select.value = selectedValue;
                }
            }
        }

        function abrirEditor(data) {
            document.getElementById('edit-id').value = data.id;
            document.getElementById('edit-nombre').value = data.nombre;
            const tiempoSelect = document.getElementById('edit-tiempo');
            tiempoSelect.value = data.tiempo;
            cambiarSub(data.tiempo, 'edit-sub', data.subcategoria);
            const modal = document.getElementById('modalEdit');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            modal.style.animation = 'none';
            setTimeout(() => { modal.style.animation = 'fadeIn 0.3s ease'; }, 10);
        }

        function cerrarModal() {
            const modal = document.getElementById('modalEdit');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.getElementById('modalEdit').addEventListener('click', function (e) {
            if (e.target.id === 'modalEdit') cerrarModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarModal();
        });

        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                const messages = document.querySelectorAll('.status-message');
                messages.forEach(msg => msg.remove());
            }, 3500);

            const searchInput = document.getElementById('filter-search');
            const tiempoSelect = document.getElementById('filter-tiempo');
            const subSelect = document.getElementById('filter-sub');
            const cards = document.querySelectorAll('.card');
            const countDisplay = document.getElementById('count-visible');

            function updateFilterSub() {
                const tiempo = tiempoSelect.value;
                subSelect.innerHTML = '<option value="all">Todas las subcategorías</option>';
                if (subcats[tiempo]) {
                    subcats[tiempo].forEach(s => { subSelect.add(new Option(s, s)); });
                }
                applyFilters();
            }

            function applyFilters() {
                const searchText = searchInput.value.toLowerCase();
                const selectedTiempo = tiempoSelect.value;
                const selectedSub = subSelect.value;
                let visibleCount = 0;

                cards.forEach(card => {
                    const name = card.querySelector('.card-title').textContent.toLowerCase();
                    const tiempo = card.querySelector('.badge-tiempo').textContent;
                    const sub = card.querySelector('.badge-subcategoria').textContent;
                    const matchesSearch = name.includes(searchText);
                    const matchesTiempo = (selectedTiempo === 'all' || tiempo === selectedTiempo);
                    const matchesSub = (selectedSub === 'all' || sub === selectedSub);

                    if (matchesSearch && matchesTiempo && matchesSub) {
                        card.style.display = 'block';
                        card.style.opacity = '1';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                        card.style.opacity = '0';
                    }
                });
                countDisplay.textContent = visibleCount;
            }

            searchInput.addEventListener('input', applyFilters);
            tiempoSelect.addEventListener('change', updateFilterSub);
            subSelect.addEventListener('change', applyFilters);
            applyFilters();
        });
    </script>
</body>
</html>