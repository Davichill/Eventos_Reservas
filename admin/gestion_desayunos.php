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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Desayunos | GO Hotel</title>
    <link rel="stylesheet" href="../css/gestion_menu/gestion_desayuno.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #001f3f;
            --accent: #d4af37;
            --bg: #f4f7f9;
            --white: #ffffff;
            --text: #2c3e50;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            background-color: var(--bg);
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        /* Contenedor principal */
        .main-content {
            flex: 1;
            width: calc(100% - 260px);
            margin-left: 260px;
            min-height: 100vh;
            padding: 0;
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                width: 0;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            
            .admin-sidebar.active {
                width: 260px;
                transform: translateX(0);
            }
            
            .main-content {
                width: 100%;
                margin-left: 0;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .admin-sidebar.active ~ .sidebar-overlay {
                display: block;
            }
        }

        /* Top Bar */
        .top-bar {
            background: var(--white);
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: block;
            }
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-logout {
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .btn-logout:hover {
            background: #003366;
        }

        /* Contenedor principal */
        .main-container {
            padding: 20px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
        }

        @media (max-width: 576px) {
            .main-container {
                padding: 10px;
            }
        }

        /* Header section */
        .header-section {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }

        @media (max-width: 768px) {
            .header-section {
                padding: 20px;
            }
        }

        .header-section h1 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .header-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .path-info {
            background: #f0f7ff;
            padding: 8px 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--primary);
            border: 1px dashed #b3d7ff;
        }

        /* Mensajes de estado */
        .status-message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: var(--radius);
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }

        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Formulario */
        .form-box {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        @media (max-width: 768px) {
            .form-box {
                padding: 20px;
            }
        }

        .form-box h3 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .form-group.full-width {
                grid-column: span 1;
            }
        }

        .form-group label {
            font-weight: 600;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group input[type="file"],
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95rem;
            font-family: inherit;
            width: 100%;
            transition: border-color 0.3s;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .file-info {
            font-size: 0.8rem;
            color: #777;
            margin-top: 5px;
        }

        /* Botones */
        .button-group {
            grid-column: span 2;
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .button-group {
                grid-column: span 1;
                flex-direction: column;
            }
        }

        .btn-main {
            background: linear-gradient(135deg, var(--primary), #003366);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 31, 63, 0.2);
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #6c757d;
            padding: 14px 30px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        /* Grid de menús */
        .grid-menus {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        @media (max-width: 1200px) {
            .grid-menus {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .grid-menus {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .grid-menus {
                grid-template-columns: 1fr;
            }
        }

        /* Card de menú */
        .card-menu {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #f0f0f0;
        }

        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .card-image {
            height: 200px;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .card-menu:hover .card-image img {
            transform: scale(1.05);
        }

        .no-image {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #7a8ca0;
            font-size: 0.9rem;
        }

        .no-image span:first-child {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        /* Contenido de la card */
        .card-content {
            padding: 20px;
        }

        .badge-container {
            margin-bottom: 15px;
        }

        .badge-tipo {
            display: inline-block;
            padding: 6px 12px;
            background: #f0f7ff;
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-descripcion {
            color: var(--text);
            line-height: 1.6;
            font-size: 0.9rem;
            margin-bottom: 20px;
            max-height: 150px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .card-descripcion::-webkit-scrollbar {
            width: 5px;
        }

        .card-descripcion::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .card-descripcion::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 3px;
        }

        /* Botones de acción en card */
        .card-actions {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 576px) {
            .card-actions {
                flex-direction: column;
            }
        }

        .btn-card {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
        }

        .btn-card-edit {
            background: #e8f5e9;
            color: #27ae60;
            border: 1px solid #c8e6c9;
        }

        .btn-card-edit:hover {
            background: #d4edda;
            transform: translateY(-2px);
        }

        .btn-card-delete {
            background: #fdeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
        }

        .btn-card-delete:hover {
            background: #f8d7da;
            transform: translateY(-2px);
        }

        /* Empty state */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--radius);
            border: 2px dashed #ddd;
        }

        .empty-state h4 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .empty-state p {
            color: #777;
            font-size: 0.95rem;
        }

        /* Tipos de desayuno */
        .tipo-saludable .badge-tipo {
            background: #e8f5e9;
            color: #27ae60;
        }

        .tipo-americano .badge-tipo {
            background: #fff3e0;
            color: #f39c12;
        }

        .tipo-especial .badge-tipo {
            background: #e3f2fd;
            color: #2980b9;
        }

        .tipo-ecuatoriano .badge-tipo {
            background: #f3e5f5;
            color: #8e44ad;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-bar">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-info">
                <span>Bienvenido, <span class="user-name"><?= $_SESSION['admin_nombre'] ?? 'Admin' ?></span></span>
                <a href="../auth/logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </header>
        
        <?php include 'navbar.php'; ?>

        <div class="main-container">
            <?php if (isset($_GET['msj'])): ?>
                <div class="status-message <?= $_GET['msj'] === 'ok' ? 'success' : 'error' ?>">
                    <?= $_GET['msj'] === 'ok' ? '✅ Menú guardado correctamente' : '🗑️ Menú eliminado exitosamente' ?>
                </div>
            <?php endif; ?>

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
                        <input type="file" name="foto" accept="image/*"
                            title="Suba una imagen representativa del desayuno">
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
                            <i class="fas fa-save"></i> Guardar Menú
                        </button>
                        <button type="button" onclick="cancelarEdicion()" id="btn-cancelar" class="btn-cancel"
                            style="display: none;">
                            <i class="fas fa-times"></i> Cancelar Edición
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
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="?eliminar=<?= $row['id'] ?>" class="btn-card btn-card-delete"
                                        onclick="return confirm('¿Está seguro de eliminar este menú?')"
                                        title="Eliminar menú">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Cerrar sidebar al hacer clic en un enlace (en móvil)
        document.querySelectorAll('.admin-sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    toggleSidebar();
                }
            });
        });
        
        // Cerrar sidebar al redimensionar a desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) {
                document.querySelector('.admin-sidebar').classList.remove('active');
            }
        });
        
        // Cerrar sidebar al hacer clic en overlay
        document.querySelector('.sidebar-overlay')?.addEventListener('click', () => {
            toggleSidebar();
        });

        function editarMenu(data) {
            document.getElementById('input_id').value = data.id;
            document.getElementById('input_nombre').value = data.nombre;
            document.getElementById('input_descripcion').value = data.descripcion;

            // Cambiar texto y color del botón
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> Actualizar Menú';
            btnSubmit.style.background = 'linear-gradient(135deg, #27ae60, #219653)';

            // Mostrar botón cancelar
            document.getElementById('btn-cancelar').style.display = 'flex';

            // Scroll suave al formulario
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Efecto visual en el formulario
            const formBox = document.querySelector('.form-box');
            formBox.style.borderColor = 'var(--accent)';
            formBox.style.boxShadow = '0 0 0 3px rgba(212, 175, 55, 0.15)';

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
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Menú';
            btnSubmit.style.background = 'linear-gradient(135deg, var(--primary), #003366)';

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
                    this.style.borderColor = 'var(--accent)';
                    setTimeout(() => {
                        this.style.borderColor = '';
                    }, 500);
                }
            });
        });
    </script>
</body>
</html>