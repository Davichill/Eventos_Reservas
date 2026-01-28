<?php
session_start();
include_once __DIR__ . '/../php/conexion.php';

// Verificación de sesión y obtención de ID de administrador
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

// Configuración de categorías del PDF
$secciones = ['ENTRADA', 'PLATO FUERTE', 'POSTRE'];
$categorias = [
    'ENTRADA' => ['ENSALADAS', 'CEVICHES Y CAUSAS', 'ENTRADAS Y CREMAS'],
    'PLATO FUERTE' => ['CARNES Y ESPECIALIDADES', 'POLLO', 'MARISCOS', 'VEGETARIANOS'],
    'POSTRE' => ['TORTAS Y PASTELES', 'CREMOSOS', 'HORNEADOS', 'LIGEROS Y FRUTALES']
];

// LÓGICA DE GUARDADO / EDITAR
if (isset($_POST['btnguardar'])) {
    $id = intval($_POST['id']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $seccion = $conn->real_escape_string($_POST['seccion']);
    $cat = $conn->real_escape_string($_POST['categoria']);

    $foto_sql = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto_nombre = time() . "_" . $_FILES['foto']['name'];
        if (move_uploaded_file($_FILES['foto']['tmp_name'], "../img/menu_seminario/" . $foto_nombre)) {
            $foto_sql = ", imagen_url = '$foto_nombre'";
        }
    }

    if ($id > 0) {
        // --- LOG DE EDICIÓN ---
        $sql = "UPDATE menu_seminario SET nombre='$nombre', seccion='$seccion', categoria='$cat' $foto_sql WHERE id=$id";
        if ($conn->query($sql)) {
            registrarLog($conn, $id_admin, 'EDITAR', 'menu_seminario', $id, "Editó el plato: $nombre ($seccion - $cat)");
        }
    } else {
        // --- LOG DE CREACIÓN ---
        $img_val = !empty($foto_nombre) ? "'$foto_nombre'" : "NULL";
        $sql = "INSERT INTO menu_seminario (nombre, seccion, categoria, imagen_url) VALUES ('$nombre', '$seccion', '$cat', $img_val)";
        if ($conn->query($sql)) {
            $nuevo_id = $conn->insert_id;
            registrarLog($conn, $id_admin, 'CREAR', 'menu_seminario', $nuevo_id, "Agregó nuevo plato: $nombre a la sección $seccion");
        }
    }
    header("Location: gestion_seminario.php?msj=ok");
    exit();
}

// ELIMINAR
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);

    // Obtenemos datos antes de borrar para el historial
    $res = $conn->query("SELECT nombre, seccion, imagen_url FROM menu_seminario WHERE id=$id");
    if ($reg = $res->fetch_assoc()) {
        $nombre_plato = $reg['nombre'];
        $seccion_plato = $reg['seccion'];

        // Borrar archivo físico
        if (!empty($reg['imagen_url']) && file_exists("../img/menu_seminario/" . $reg['imagen_url'])) {
            unlink("../img/menu_seminario/" . $reg["imagen_url"]);
        }

        // --- LOG DE ELIMINACIÓN ---
        if ($conn->query("DELETE FROM menu_seminario WHERE id=$id")) {
            registrarLog($conn, $id_admin, 'ELIMINAR', 'menu_seminario', $id, "Eliminó el plato: $nombre_plato de la sección $seccion_plato");
        }
    }

    header("Location: gestion_seminario.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión Seminario | GO Hotel</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_seminario.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        .filter-dashboard {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
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

        #filter-search,
        #filter-seccion,
        #filter-categoria {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        #filter-search:focus {
            border-color: #d35400;
            outline: none;
        }

        .filter-results {
            font-size: 0.85rem;
            font-weight: bold;
            color: #666;
            padding-bottom: 10px;
        }

        /* Para animar la desaparición de las cards */
        .card-plato {
            transition: all 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include 'navbar.php'; ?>

            <?php if (isset($_GET['msj'])): ?>
                <div class="status-message success">
                    ✅ Plato guardado correctamente
                </div>
            <?php endif; ?>

            <div class="main-container">
                <div class="header-section">
                    <h1>Gestión de Menú para Seminarios</h1>
                    <div class="header-info">
                        <div class="path-info">/img/menu_seminario/</div>

                    </div>
                </div>

                <!-- Formulario principal para crear nuevos platos -->
                <div class="form-box">
                    <h3>Registrar Nuevo Plato</h3>
                    <form method="POST" enctype="multipart/form-data" class="form-grid">
                        <div class="form-group">
                            <label>Nombre del Plato:</label>
                            <input type="text" name="nombre" id="crear_nombre"
                                placeholder="Ej: Lomo Saltado, Ceviche Mixto, etc." required
                                title="Ingrese el nombre completo del plato">
                        </div>
                        <div class="form-group">
                            <label>Sección:</label>
                            <select name="seccion" id="crear_seccion" onchange="actualizarSubs(this.value, 'crear_cat')"
                                required>
                                <option value="">Seleccione sección...</option>
                                <?php foreach ($secciones as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Categoría específica:</label>
                            <select name="categoria" id="crear_cat" required>
                                <option value="">Primero elija sección</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Imagen:</label>
                            <input type="file" name="foto" accept="image/*" required
                                title="Suba una imagen del plato (JPG, PNG, GIF)">
                        </div>
                        <input type="hidden" name="id" value="0">
                        <button type="submit" name="btnguardar" class="btn-main">Guardar Plato</button>
                    </form>
                </div>

                <div class="filter-dashboard">
                    <div class="filter-main">
                        <input type="text" id="filter-search" placeholder="🔍 Buscar por nombre del plato...">
                    </div>
                    <div class="filter-options">
                        <div class="filter-group">
                            <label>Sección:</label>
                            <select id="filter-seccion">
                                <option value="all">Todas las secciones</option>
                                <?php foreach ($secciones as $s): ?>
                                    <option value="<?= $s ?>">
                                        <?= $s ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Categoría:</label>
                            <select id="filter-categoria">
                                <option value="all">Todas las categorías</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-results">
                        Mostrando <span id="visible-count">0</span> platos
                    </div>
                </div>

                <!-- Listado de platos existentes -->
                <div class="grid-platos">
                    <?php
                    $items = $conn->query("SELECT * FROM menu_seminario ORDER BY FIELD(seccion, 'ENTRADA', 'PLATO FUERTE', 'POSTRE'), categoria");

                    if ($items->num_rows === 0): ?>
                        <div class="empty-state">
                            <h4>No hay platos registrados</h4>
                            <p>Comienza agregando platos usando el formulario superior</p>
                        </div>
                    <?php else:
                        while ($row = $items->fetch_assoc()):
                            $img_src = (!empty($row['imagen_url']) && file_exists("../img/menu_seminario/" . $row['imagen_url']))
                                ? "../img/menu_seminario/" . $row['imagen_url']
                                : null;
                            $seccion_class = '';
                            if ($row['seccion'] == 'ENTRADA') {
                                $seccion_class = 'seccion-entrada';
                            } elseif ($row['seccion'] == 'PLATO FUERTE') {
                                $seccion_class = 'seccion-plato';
                            } else {
                                $seccion_class = 'seccion-postre';
                            }
                            ?>
                            <div class="card-plato <?= $seccion_class ?>">
                                <div class="card-image">
                                    <?php if ($img_src): ?>
                                        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($row['nombre']) ?>"
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
                                        <span class="badge-seccion"><?= $row['seccion'] ?></span>
                                        <span class="badge-categoria"><?= $row['categoria'] ?></span>
                                    </div>

                                    <h4 class="card-title"><?= htmlspecialchars($row['nombre']) ?></h4>

                                    <div class="card-actions">
                                        <a href="javascript:void(0)" onclick='abrirEditor(<?= json_encode($row) ?>)'
                                            class="btn-card btn-card-edit" title="Editar plato">
                                            ✏️ Editar
                                        </a>
                                        <a href="?del=<?= $row['id'] ?>" class="btn-card btn-card-delete"
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

            <!-- MODAL DE EDICIÓN FLOTANTE -->
            <div id="modalEdit">
                <div class="modal-content">
                    <h3>Editar Plato</h3>
                    <form method="POST" enctype="multipart/form-data" id="formEditar">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="form-group">
                            <label>Nombre del Plato:</label>
                            <input type="text" name="nombre" id="edit_nombre" required>
                        </div>

                        <div class="form-group">
                            <label>Sección:</label>
                            <select name="seccion" id="edit_seccion"
                                onchange="actualizarSubs(this.value, 'edit_cat', '')" required>
                                <?php foreach ($secciones as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Categoría específica:</label>
                            <select name="categoria" id="edit_cat" required>
                                <option value="">Cargando...</option>
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
        const categorias = <?= json_encode($categorias) ?>;

        function actualizarSubs(seccion, targetId, selectedValue = "") {
            const select = document.getElementById(targetId);
            select.innerHTML = '';

            if (categorias[seccion]) {
                const options = categorias[seccion];

                // Agregar opción inicial
                const defaultOption = new Option('Seleccione categoría...', '', true, true);
                defaultOption.disabled = true;
                defaultOption.selected = true;
                select.add(defaultOption);

                // Agregar opciones
                options.forEach(c => {
                    const option = new Option(c, c);
                    select.add(option);
                });

                // Seleccionar valor si existe
                if (selectedValue && options.includes(selectedValue)) {
                    select.value = selectedValue;
                }
            }
        }

        function abrirEditor(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nombre').value = data.nombre;

            const catSelect = document.getElementById('edit_seccion');
            catSelect.value = data.seccion;

            // Actualizar categorías con la selección actual
            actualizarSubs(data.seccion, 'edit_cat', data.categoria);

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
                const seccion = document.getElementById('crear_seccion').value;
                const categoria = document.getElementById('crear_cat').value;

                if (!nombre) {
                    e.preventDefault();
                    alert('Por favor ingrese el nombre del plato');
                    document.getElementById('crear_nombre').focus();
                    return false;
                }

                if (!seccion) {
                    e.preventDefault();
                    alert('Por favor seleccione una sección');
                    document.getElementById('crear_seccion').focus();
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
                const nombre = document.getElementById('edit_nombre').value.trim();
                const seccion = document.getElementById('edit_seccion').value;
                const categoria = document.getElementById('edit_cat').value;

                if (!nombre) {
                    e.preventDefault();
                    alert('Por favor ingrese el nombre del plato');
                    document.getElementById('edit_nombre').focus();
                    return false;
                }

                if (!seccion) {
                    e.preventDefault();
                    alert('Por favor seleccione una sección');
                    document.getElementById('edit_seccion').focus();
                    return false;
                }

                if (!categoria) {
                    e.preventDefault();
                    alert('Por favor seleccione una categoría');
                    document.getElementById('edit_cat').focus();
                    return false;
                }
            });
        });

        // Efecto al cambiar selección en formulario de creación
        document.getElementById('crear_seccion').addEventListener('change', function () {
            if (this.value) {
                const select = document.getElementById('crear_cat');
                select.style.borderColor = 'var(--accent-color)';
                setTimeout(() => {
                    select.style.borderColor = '';
                }, 500);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('filter-search');
            const filterSeccion = document.getElementById('filter-seccion');
            const filterCategoria = document.getElementById('filter-categoria');
            const cards = document.querySelectorAll('.card-plato');
            const countDisplay = document.getElementById('visible-count');

            // Usamos el objeto 'categorias' que ya tienes definido en el PHP para JS
            function actualizarFiltroCategorias() {
                const seccion = filterSeccion.value;
                filterCategoria.innerHTML = '<option value="all">Todas las categorías</option>';

                if (categorias[seccion]) {
                    categorias[seccion].forEach(cat => {
                        filterCategoria.add(new Option(cat, cat));
                    });
                }
                ejecutarFiltrado();
            }

            function ejecutarFiltrado() {
                const busqueda = searchInput.value.toLowerCase();
                const seccionSel = filterSeccion.value;
                const categoriaSel = filterCategoria.value;
                let contador = 0;

                cards.forEach(card => {
                    const nombre = card.querySelector('.card-title').textContent.toLowerCase();
                    const seccionCard = card.querySelector('.badge-seccion').textContent;
                    const categoriaCard = card.querySelector('.badge-categoria').textContent;

                    const coincideNombre = nombre.includes(busqueda);
                    const coincideSeccion = (seccionSel === 'all' || seccionCard === seccionSel);
                    const coincideCategoria = (categoriaSel === 'all' || categoriaCard === categoriaSel);

                    if (coincideNombre && coincideSeccion && coincideCategoria) {
                        card.style.display = 'block';
                        setTimeout(() => card.style.opacity = '1', 10);
                        contador++;
                    } else {
                        card.style.opacity = '0';
                        card.style.display = 'none';
                    }
                });

                countDisplay.textContent = contador;
            }

            // Event Listeners
            searchInput.addEventListener('input', ejecutarFiltrado);
            filterSeccion.addEventListener('change', actualizarFiltroCategorias);
            filterCategoria.addEventListener('change', ejecutarFiltrado);

            // Inicializar contador
            ejecutarFiltrado();
        });
    </script>

</body>

</html>