<?php
include_once __DIR__ . '/../php/conexion.php';
session_start();

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

// --- CONFIGURACIÓN DE RUTA ESPECÍFICA ---
$carpeta_destino = "../img/menu_coctel/";

if (!file_exists($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

// --- LÓGICA CRUD ---
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $subcategoria = $conn->real_escape_string($_POST['subcategoria']);

    $imagen_sql = "";
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombre_img = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['imagen']['name']);
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $carpeta_destino . $nombre_img)) {
            $imagen_sql = ", imagen_url = '$nombre_img'";
        }
    }

    if ($id > 0) {
        // --- LOG DE EDICIÓN ---
        $sql = "UPDATE menu_coctel SET nombre='$nombre', categoria='$categoria', subcategoria='$subcategoria' $imagen_sql WHERE id=$id";
        if ($conn->query($sql)) {
            registrarLog($conn, $id_admin, 'EDITAR', 'menu_coctel', $id, "Editó el bocadito: $nombre (Categoría: $categoria)");
        }
    } else {
        // --- LOG DE CREACIÓN ---
        $img_val = $imagen_sql ? "'$nombre_img'" : "NULL";
        $sql = "INSERT INTO menu_coctel (nombre, categoria, subcategoria, imagen_url, estado) 
                VALUES ('$nombre', '$categoria', '$subcategoria', $img_val, 1)";
        if ($conn->query($sql)) {
            $nuevo_id = $conn->insert_id;
            registrarLog($conn, $id_admin, 'CREAR', 'menu_coctel', $nuevo_id, "Creó nuevo bocadito: $nombre en $categoria");
        }
    }

    header("Location: gestion_coctel.php?res=ok");
    exit();
}

if (isset($_GET['del'])) {
    $id = intval($_GET['del']);

    // Obtenemos datos antes de borrar para el historial y eliminar archivo físico
    $res = $conn->query("SELECT nombre, imagen_url FROM menu_coctel WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        $nombre_bocadito = $row['nombre'];

        if ($row['imagen_url'] && file_exists($carpeta_destino . $row['imagen_url'])) {
            unlink($carpeta_destino . $row['imagen_url']);
        }

        // --- LOG DE ELIMINACIÓN ---
        if ($conn->query("DELETE FROM menu_coctel WHERE id=$id")) {
            registrarLog($conn, $id_admin, 'ELIMINAR', 'menu_coctel', $id, "Eliminó el bocadito: $nombre_bocadito");
        }
    }

    header("Location: gestion_coctel.php?res=del");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión Cóctel | GO Quito</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_coctel.css">
    <style>
        /*Estilo del filtro*/
        .filter-dashboard {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
            border-left: 5px solid #d4af37;
            /* Color dorado/elegante */
        }

        .filter-main {
            flex: 1;
            min-width: 250px;
        }

        .filter-options {
            display: flex;
            gap: 15px;
            flex: 2;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
        }

        .filter-dashboard label {
            font-size: 0.85rem;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
        }

        #filter-search,
        #filter-cat,
        #filter-sub {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 100%;
        }

        #filter-search:focus {
            border-color: #d4af37;
            outline: none;
            box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
        }

        .filter-results {
            width: 100%;
            font-size: 0.9rem;
            color: #888;
            margin-top: 10px;
            font-style: italic;
        }

        /* Animación de filtrado */
        .card {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <?php if (isset($_GET['res'])): ?>
        <div class="status-message success">
            <?php
            $msg = [
                'ok' => '✅ Plato guardado correctamente',
                'del' => '🗑️ Plato eliminado exitosamente'
            ];
            echo $msg[$_GET['res']] ?? 'Operación completada';
            ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="header-section">
            <h1>Administración de Cóctel (Bocaditos)</h1>
            <p>Gestiona los bocaditos para eventos de cóctel. Los cambios se reflejarán inmediatamente en el menú.</p>
            <div class="path-info">/img/menu_coctel/</div>
        </div>

        <div class="form-box">
            <form method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label>Nombre del Bocadito:</label>
                    <input type="text" name="nombre" required placeholder="Ej: Mini empanadas de carne"
                        title="Ingrese el nombre descriptivo del bocadito">
                </div>
                <div class="form-group">
                    <label>Categoría:</label>
                    <select name="categoria" id="main-cat" onchange="actualizarSubs(this.value, 'main-sub')" required>
                        <option value="">Seleccione categoría...</option>
                        <option value="BOCADOS SALADOS"> Bocados Salados</option>
                        <option value="VEGETARIANO / VEGANO"> Vegetariano / Vegano</option>
                        <option value="MARISCOS Y PESCADOS"> Mariscos y Pescados</option>
                        <option value="BOCADITOS DULCES"> Bocaditos Dulces</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategoría:</label>
                    <select name="subcategoria" id="main-sub" required>
                        <option value="">Primero elija categoría</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Imagen:</label>
                    <input type="file" name="imagen" accept="image/*" required
                        title="Suba una imagen del bocadito (JPG, PNG, GIF)">
                </div>
                <button type="submit" name="guardar" class="btn-main">Guardar</button>
            </form>
        </div>

        <div class="filter-dashboard">
            <div class="filter-main">
                <label>Buscar bocadito:</label>
                <input type="text" id="filter-search" placeholder="🔍 Escribe el nombre...">
            </div>
            <div class="filter-options">
                <div class="filter-group">
                    <label>Categoría:</label>
                    <select id="filter-cat">
                        <option value="all">Todas las categorías</option>
                        <option value="BOCADOS SALADOS">Bocados Salados</option>
                        <option value="VEGETARIANO / VEGANO">Vegetariano / Vegano</option>
                        <option value="MARISCOS Y PESCADOS">Mariscos y Pescados</option>
                        <option value="BOCADITOS DULCES">Bocaditos Dulces</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Subcategoría:</label>
                    <select id="filter-sub">
                        <option value="all">Todas las subcategorías</option>
                    </select>
                </div>
            </div>
            <div class="filter-results">
                Mostrando <span id="visible-count">0</span> bocaditos
            </div>
        </div>

        <div class="grid-platos">
            <?php
            $bocaditos = $conn->query("SELECT * FROM menu_coctel ORDER BY categoria ASC, subcategoria ASC, nombre ASC");

            if ($bocaditos->num_rows === 0): ?>
                <div class="empty-state">
                    <h4>No hay bocaditos registrados</h4>
                    <p>Comienza agregando bocaditos usando el formulario superior</p>
                </div>
            <?php else:
                while ($b = $bocaditos->fetch_assoc()):
                    $img_src = $carpeta_destino . $b['imagen_url'];
                    $img_exists = file_exists($img_src);
                    ?>
                    <div class="card">
                        <div class="card-image">
                            <?php if ($img_exists): ?>
                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($b['nombre']) ?>"
                                    onerror="this.parentElement.innerHTML='<div class=\'no-image\'>🍽️</div>'">
                            <?php else: ?>
                                <div class="no-image">🍽️</div>
                            <?php endif; ?>
                        </div>

                        <div class="card-content">
                            <div class="badge-container">
                                <span class="badge-cat"><?= $b['categoria'] ?></span>
                                <span class="badge-sub"><?= $b['subcategoria'] ?></span>
                            </div>

                            <h4 class="card-title"><?= htmlspecialchars($b['nombre']) ?></h4>

                            <div class="card-actions">
                                <a href="javascript:void(0)" onclick='abrirEditor(<?= json_encode($b) ?>)'
                                    class="btn-action btn-edit" title="Editar bocadito">
                                    ✏️ Editar
                                </a>
                                <a href="?del=<?= $b['id'] ?>" class="btn-action btn-delete"
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

    <!-- Modal de edición -->
    <div id="modalEdit">
        <div class="modal-content">
            <h3>✏️ Editar Bocadito</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-id">

                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" id="edit-nombre" required>
                </div>

                <div class="form-group">
                    <label>Categoría:</label>
                    <select name="categoria" id="edit-cat" onchange="actualizarSubs(this.value, 'edit-sub', '')"
                        required>
                        <option value="BOCADOS SALADOS"> Bocados Salados</option>
                        <option value="VEGETARIANO / VEGANO"> Vegetariano / Vegano</option>
                        <option value="MARISCOS Y PESCADOS"> Mariscos y Pescados</option>
                        <option value="BOCADITOS DULCES"> Bocaditos Dulces</option>
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

    <script>
        const opcionesCoctel = {
            "BOCADOS SALADOS": ["Carne", "Embutidos y charcutería", "Pollo", "Bocaditos típicos / calientes"],
            "VEGETARIANO / VEGANO": ["General"],
            "MARISCOS Y PESCADOS": ["General"],
            "BOCADITOS DULCES": ["General"]
        };

        function actualizarSubs(cat, targetId, selectedValue = "") {
            const select = document.getElementById(targetId);
            select.innerHTML = '';

            if (opcionesCoctel[cat]) {
                const options = opcionesCoctel[cat];

                // Agregar opción inicial
                const defaultOption = new Option('Seleccione subcategoría', '', true, true);
                defaultOption.disabled = true;
                defaultOption.selected = true;
                select.add(defaultOption);

                // Agregar opciones
                options.forEach(sub => {
                    const option = new Option(sub, sub);
                    select.add(option);
                });

                // Seleccionar valor si existe
                if (selectedValue && options.includes(selectedValue)) {
                    select.value = selectedValue;
                }
            }
        }

        function abrirEditor(data) {
            document.getElementById('edit-id').value = data.id;
            document.getElementById('edit-nombre').value = data.nombre;

            const catSelect = document.getElementById('edit-cat');
            catSelect.value = data.categoria;

            // Actualizar subcategorías
            actualizarSubs(data.categoria, 'edit-sub', data.subcategoria);

            // Mostrar modal con animación
            const modal = document.getElementById('modalEdit');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
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
            const mainCat = document.getElementById('main-cat');
            if (mainCat.value) {
                actualizarSubs(mainCat.value, 'main-sub');
            }

            // Auto-remover mensajes de estado
            setTimeout(() => {
                const messages = document.querySelectorAll('.status-message');
                messages.forEach(msg => msg.remove());
            }, 3500);
        });

        // Validación de formulario
        document.querySelector('form').addEventListener('submit', function (e) {
            const nombre = document.querySelector('input[name="nombre"]');
            const categoria = document.querySelector('select[name="categoria"]');
            const subcategoria = document.querySelector('select[name="subcategoria"]');

            if (!nombre.value.trim()) {
                e.preventDefault();
                alert('Por favor ingrese el nombre del bocadito');
                nombre.focus();
                return false;
            }

            if (!categoria.value) {
                e.preventDefault();
                alert('Por favor seleccione una categoría');
                categoria.focus();
                return false;
            }

            if (!subcategoria.value) {
                e.preventDefault();
                alert('Por favor seleccione una subcategoría');
                subcategoria.focus();
                return false;
            }
        });
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('filter-search');
            const filterCat = document.getElementById('filter-cat');
            const filterSub = document.getElementById('filter-sub');
            const cards = document.querySelectorAll('.card');
            const countDisplay = document.getElementById('visible-count');

            // Función para actualizar las subcategorías del FILTRO
            function actualizarFiltroSubs() {
                const cat = filterCat.value;
                filterSub.innerHTML = '<option value="all">Todas las subcategorías</option>';

                if (opcionesCoctel[cat]) {
                    opcionesCoctel[cat].forEach(sub => {
                        filterSub.add(new Option(sub, sub));
                    });
                }
                ejecutarFiltrado();
            }

            // Función principal de filtrado
            function ejecutarFiltrado() {
                const busqueda = searchInput.value.toLowerCase().trim();
                const catSel = filterCat.value;
                const subSel = filterSub.value;
                let contador = 0;

                cards.forEach(card => {
                    const nombre = card.querySelector('.card-title').textContent.toLowerCase();
                    const catCard = card.querySelector('.badge-cat').textContent;
                    const subCard = card.querySelector('.badge-sub').textContent;

                    const coincideNombre = nombre.includes(busqueda);
                    const coincideCat = (catSel === 'all' || catCard === catSel);
                    const coincideSub = (subSel === 'all' || subCard === subSel);

                    if (coincideNombre && coincideCat && coincideSub) {
                        card.style.display = 'block';
                        setTimeout(() => { card.style.opacity = '1'; card.style.transform = 'scale(1)'; }, 10);
                        contador++;
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        card.style.display = 'none';
                    }
                });

                countDisplay.textContent = contador;
            }

            // Event Listeners para el filtro
            searchInput.addEventListener('input', ejecutarFiltrado);
            filterCat.addEventListener('change', actualizarFiltroSubs);
            filterSub.addEventListener('change', ejecutarFiltrado);

            // Inicializar el contador al cargar
            ejecutarFiltrado();
        });
    </script>
</body>

</html>