<?php
// detalle_evento.php
ob_start();
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Obtener información del administrador
$admin_id = $_SESSION['admin_id'];
$sql_admin = "SELECT id, usuario, nombre_completo, tipo FROM admin_usuarios WHERE id = '$admin_id'";
$res_admin = $conn->query($sql_admin);
$admin_info = $res_admin->fetch_assoc();

$es_admin_principal = ($admin_info && $admin_info['id'] == 1 && $admin_info['tipo'] == 'principal');

// Obtener ID del evento
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evento_id == 0) {
    header("Location: index.php");
    exit();
}

// CONSULTA SQL ACTUALIZADA: Incluye JOIN con la tabla 'salones'
$sql = "SELECT r.*, 
               e.nombre as evento, 
               m.nombre as mesa_nombre,
               s.nombre_salon,
               c.razon_social, c.identificacion, c.representante_legal, 
               c.direccion_fiscal, c.correo_facturacion, c.cliente_nombre,
               c.cliente_email, c.cliente_telefono,
               (SELECT GROUP_CONCAT(nombre_plato SEPARATOR '||') 
                FROM reserva_detalles_menu 
                WHERE id_reserva = r.id) as platos_lista
        FROM reservas r
        JOIN tipos_evento e ON r.id_tipo_evento = e.id
        JOIN clientes c ON r.id_cliente = c.id
        LEFT JOIN mesas m ON r.id_mesa = m.id
        LEFT JOIN salones s ON r.id_salon = s.id
        WHERE r.id = $evento_id";

$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    header("Location: index.php");
    exit();
}
$row = $res->fetch_assoc();

// Preparar platos
$platos_array = !empty($row['platos_lista']) ? explode('||', $row['platos_lista']) : [];

// Funciones de utilidad
function formatHora($hora)
{
    return (empty($hora) || $hora == '00:00:00') ? '--:--' : date("H:i", strtotime($hora));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente #<?= $row['id'] ?> - GO Quito Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
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
    }

    /* Contenedor del contenido principal */
    .content-body {
        padding: 20px;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .content-body {
            padding: 15px;
        }
    }

    /* Header del detalle */
    .detail-header {
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        padding: 25px;
        border-radius: var(--radius);
        color: white;
        box-shadow: 0 15px 35px rgba(0, 31, 63, 0.2);
        margin-bottom: 25px;
        width: 100%;
    }

    .detail-title {
        margin-bottom: 20px;
    }

    .detail-title h1 {
        margin: 0 0 10px 0;
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .detail-title p {
        font-size: 1rem;
        opacity: 0.9;
    }

    .status-badge {
        background: var(--accent);
        color: var(--primary);
        padding: 6px 15px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
        margin: 5px 0;
    }

    /* Contenedor de información */
    .info-container {
        background: var(--white);
        border-radius: var(--radius);
        padding: 0;
        box-shadow: var(--shadow);
        width: 100%;
        margin-bottom: 30px;
    }

    /* Grid responsive */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        width: 100%;
    }

    @media (max-width: 1200px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .info-container {
            overflow-x: hidden;
        }
    }

    .info-section {
        padding: 25px;
        border-right: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-grid .info-section:nth-child(4n),
    .info-grid .info-section:last-child {
        border-right: none;
    }

    @media (max-width: 1200px) {
        .info-grid .info-section:nth-child(2n) {
            border-right: none;
        }
        
        .info-grid .info-section:nth-last-child(-n+2) {
            border-bottom: none;
        }
    }

    @media (max-width: 768px) {
        .info-section {
            border-right: none;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
    }

    /* Estilos de sección */
    .section-title {
        color: var(--primary);
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }

    .section-title i {
        color: var(--accent);
    }

    /* Campos */
    .field-group {
        margin-bottom: 15px;
        width: 100%;
    }

    .field-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #888;
        text-transform: uppercase;
        display: block;
        margin-bottom: 5px;
    }

    .field-value {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        font-size: 0.95rem;
        border-left: 3px solid transparent;
        word-break: break-word;
        width: 100%;
    }

    .highlight-value {
        border-left-color: var(--accent);
        font-weight: 600;
        background: #fffdf5;
    }

    /* Botones de acción */
    .detail-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 12px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
        transition: 0.3s;
        cursor: pointer;
        text-align: center;
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-pdf {
        background: #e74c3c;
        color: white;
    }

    .btn-edit {
        background: var(--accent);
        color: var(--primary);
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Plato items */
    .plato-item {
        background: #f1f8e9;
        color: #2e7d32;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 5px;
        font-size: 0.9rem;
        border-left: 3px solid #4caf50;
        word-break: break-word;
    }

    /* Sección de planimetría */
    .planimetria-section {
        margin-top: 30px;
        text-align: center;
        width: 100%;
        background: var(--white);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
    }

    .planimetria-section .btn-action {
        display: inline-flex;
        background: var(--primary);
        color: white;
        margin-top: 15px;
    }

    /* Ajustes para móviles */
    @media (max-width: 576px) {
        .content-body {
            padding: 10px;
        }
        
        .detail-header {
            padding: 20px;
            border-radius: 10px;
        }
        
        .detail-title h1 {
            font-size: 1.5rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .status-badge {
            margin-left: 0;
            align-self: flex-start;
        }
        
        .btn-action {
            width: 100%;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .detail-actions {
            flex-direction: column;
        }
        
        .info-section {
            padding: 20px;
        }
    }
</style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <header class="top-bar">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-info">
                <span>Admin: <strong><?= $_SESSION['admin_nombre'] ?? 'Staff' ?></strong></span>
            </div>
        </header>

        <div class="content-body">
            <div class="detail-header">
                <div class="detail-title">
                    <h1>Expediente #<?= $row['id'] ?> <span class="status-badge"><?= $row['estado'] ?></span></h1>
                    <p><i class="far fa-calendar"></i> <?= date("d/m/Y", strtotime($row['fecha_evento'])) ?></p>
                </div>
                <div class="detail-actions">
                    <a href="dashboard.php" class="btn-action btn-back"><i class="fas fa-chevron-left"></i> Volver</a>
                    <button onclick="window.open('generar_pdf.php?id=<?= $row['id'] ?>')" 
                            class="btn-action btn-pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                    <?php if ($es_admin_principal): ?>
                        <a href="editar_evento.php?id=<?= $row['id'] ?>" class="btn-action btn-edit">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-container">
                <div class="info-grid">
                    <!-- Facturación -->
                    <div class="info-section">
                        <h2 class="section-title"><i class="fas fa-file-invoice"></i> Facturación</h2>
                        <div class="field-group">
                            <span class="field-label">Razón Social</span>
                            <div class="field-value highlight-value">
                                <?= htmlspecialchars($row['razon_social'] ?: $row['cliente_nombre']) ?>
                            </div>
                        </div>
                        <div class="field-group">
                            <span class="field-label">Identificación</span>
                            <div class="field-value"><?= htmlspecialchars($row['identificacion'] ?: 'N/A') ?></div>
                        </div>
                        <div class="field-group">
                            <span class="field-label">Email Facturación</span>
                            <div class="field-value">
                                <?= htmlspecialchars($row['correo_facturacion'] ?: $row['cliente_email']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Ubicación y Horario -->
                    <div class="info-section">
                        <h2 class="section-title"><i class="fas fa-hotel"></i> Ubicación y Horario</h2>
                        <div class="field-group">
                            <span class="field-label">Salón Asignado</span>
                            <div class="field-value highlight-value">
                                <i class="fas fa-door-open"></i>
                                <?= htmlspecialchars($row['nombre_salon'] ?: 'Sin salón asignado') ?>
                            </div>
                        </div>
                        <div class="field-group">
                            <span class="field-label">Tipo de Evento / Pax</span>
                            <div class="field-value"><?= htmlspecialchars($row['evento']) ?> — 👥
                                <?= $row['cantidad_personas'] ?> Pax
                            </div>
                        </div>
                        <div class="field-group">
                            <span class="field-label">Horario</span>
                            <div class="field-value"><?= formatHora($row['hora_inicio']) ?> a
                                <?= formatHora($row['hora_fin']) ?>
                            </div>
                        </div>
                        <div class="field-group">
                            <span class="field-label">Mesa / Estación</span>
                            <div class="field-value"><?= htmlspecialchars($row['mesa_nombre'] ?: 'N/A') ?></div>
                        </div>
                    </div>

                    <!-- Menú -->
                    <div class="info-section">
                        <h2 class="section-title"><i class="fas fa-utensils"></i> Menú</h2>
                        <?php if (!empty($platos_array)): ?>
                            <?php foreach ($platos_array as $plato): ?>
                                <div class="plato-item"><?= htmlspecialchars($plato) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#999; font-style:italic;">No definido.</p>
                        <?php endif; ?>
                        <?php if (!empty($row['observaciones'])): ?>
                            <div class="field-group" style="margin-top:15px;">
                                <span class="field-label">Notas de Cocina</span>
                                <div class="field-value" style="background:#fff3e0; border-left-color:#e67e22;">
                                    <?= nl2br(htmlspecialchars($row['observaciones'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Logística -->
                    <div class="info-section">
                        <h2 class="section-title"><i class="fas fa-info-circle"></i> Logística</h2>
                        <div class="field-group">
                            <span class="field-label">Equipos</span>
                            <div class="field-value">
                                <?= htmlspecialchars($row['equipos_audiovisuales'] ?: 'Estándar') ?>
                            </div>
                        </div>
                        <?php if (!empty($row['notas'])): ?>
                            <div class="field-group">
                                <span class="field-label">Notas Internas</span>
                                <div class="field-value" style="background:#e8f4fd; border-left-color:#3498db;">
                                    <?= nl2br(htmlspecialchars($row['notas'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($row['planimetria_url']): ?>
                <div class="planimetria-section">
                    <h2 class="section-title" style="justify-content:center;"><i class="fas fa-map"></i> Planimetría</h2>
                    <a href="<?= htmlspecialchars($row['planimetria_url']) ?>" target="_blank" class="btn-action">
                        <i class="fas fa-expand"></i> Ver Mapa de Montaje
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
    document.querySelector('.sidebar-overlay').addEventListener('click', () => {
        toggleSidebar();
    });
    </script>
</body>
</html>