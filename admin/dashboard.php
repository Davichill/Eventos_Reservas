<?php
ob_start();

include '../php/conexion.php';
session_start();

function registrarLog($conn, $id_admin, $accion, $tabla, $id_registro, $descripcion)
{
    $accion = $conn->real_escape_string($accion);
    $tabla = $conn->real_escape_string($tabla);
    $descripcion = $conn->real_escape_string($descripcion);

    $sql = "INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) 
            VALUES ('$id_admin', '$accion', '$tabla', '$id_registro', '$descripcion')";
    $conn->query($sql);
}

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Obtener información del administrador actual
$admin_id = $_SESSION['admin_id'];
$sql_admin = "SELECT id, usuario, nombre_completo, tipo FROM admin_usuarios WHERE id = '$admin_id'";
$res_admin = $conn->query($sql_admin);
$admin_info = $res_admin->fetch_assoc();

// Verificar si es administrador principal (ID 1 = admin principal)
$es_admin_principal = false;
if ($admin_info && $admin_info['id'] == 1 && $admin_info['tipo'] == 'principal') {
    $es_admin_principal = true;
}

$filtro_nombre = isset($_GET['busqueda']) ? $conn->real_escape_string($_GET['busqueda']) : '';
$filtro_evento = isset($_GET['id_tipo_evento']) ? $_GET['id_tipo_evento'] : '';
$filtro_fecha = isset($_GET['fecha_evento']) ? $_GET['fecha_evento'] : '';

$sql = "SELECT r.*, 
               e.nombre as evento, 
               m.nombre as mesa_nombre,
               c.razon_social, c.identificacion, c.representante_legal, 
               c.direccion_fiscal, c.correo_facturacion, c.cliente_nombre,
               c.cliente_email, c.cliente_telefono,
               r.firma_nombre, r.firma_identificacion, 
               (SELECT GROUP_CONCAT(nombre_plato SEPARATOR '||') 
                FROM reserva_detalles_menu 
                WHERE id_reserva = r.id) as platos_lista
        FROM reservas r
        JOIN tipos_evento e ON r.id_tipo_evento = e.id
        JOIN clientes c ON r.id_cliente = c.id
        LEFT JOIN mesas m ON r.id_mesa = m.id
        WHERE 1=1";

if ($filtro_nombre != '') {
    $sql .= " AND (c.cliente_nombre LIKE '%$filtro_nombre%' OR c.identificacion LIKE '%$filtro_nombre%' OR c.razon_social LIKE '%$filtro_nombre%')";
}
if ($filtro_evento != '') {
    $sql .= " AND r.id_tipo_evento = '$filtro_evento'";
}
if ($filtro_fecha != '') {
    $sql .= " AND r.fecha_evento = '$filtro_fecha'";
}

$sql .= " ORDER BY r.fecha_evento ASC";
$res = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo - GO Quito Hotel</title>
    <link rel="stylesheet" href="../css/gestion_menu/admin.css">
    <link rel="stylesheet" href="../css/gestion_menu/admin2.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

        <div class="content-body">
            <form method="GET" class="filters-section">
                <div class="filter-group">
                    <label>Búsqueda</label>
                    <input type="text" name="busqueda" value="<?= htmlspecialchars($filtro_nombre) ?>"
                        placeholder="Cliente o RUC...">
                </div>
                <div class="filter-group">
                    <label>Evento</label>
                    <select name="id_tipo_evento">
                        <option value="">Todos</option>
                        <?php $tipos = $conn->query("SELECT * FROM tipos_evento");
                        while ($t = $tipos->fetch_assoc()) {
                            echo "<option value='" . $t['id'] . "' " . ($filtro_evento == $t['id'] ? 'selected' : '') . ">" . $t['nombre'] . "</option>";
                        } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Fecha</label>
                    <input type="date" name="fecha_evento" value="<?= $filtro_fecha ?>">
                </div>
                <button type="submit" class="btn-filter">Filtrar</button>
                <a href="../admin/nueva_invitacion.php" class="btn-new">+ Nueva Reserva</a>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 120px;">Fecha/Hora</th>
                            <th style="width: 220px;">Facturación / Cliente</th>
                            <th style="width: 140px;">Evento/Pax</th>
                            <th style="width: 150px;">Montaje</th>
                            <th style="min-width: 200px;">Detalles Críticos</th>
                            <th style="width: 70px;">Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $res->fetch_assoc()): ?>
                            <tr onclick="window.location.href='detalle_evento.php?id=<?= $row['id'] ?>'"
                                style="cursor: pointer;">
                                <td>
                                    <span class="status-badge status-<?= strtolower($row['estado']) ?>">
                                        <?= $row['estado'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= date("d/m/Y", strtotime($row['fecha_evento'])) ?></strong><br>
                                    <small>🕒
                                        <?= $row['hora_inicio'] ? date("H:i", strtotime($row['hora_inicio'])) : '--:--' ?>
                                        a
                                        <?= $row['hora_fin'] ? date("H:i", strtotime($row['hora_fin'])) : '--:--' ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="font-weight: bold; color: #27ae60; font-size: 0.95rem;">
                                        <?= htmlspecialchars($row['razon_social'] ?: $row['cliente_nombre']) ?>
                                    </div>
                                    <small style="color: #666; font-size: 0.85rem;">ID:
                                        <?= htmlspecialchars($row['identificacion']) ?></small>
                                </td>
                                <td>
                                    <strong style="font-size: 0.95rem;"><?= htmlspecialchars($row['evento']) ?></strong><br>
                                    <small>👥 <?= $row['cantidad_personas'] ?> Pax</small>
                                </td>
                                <td>
                                    <span style="font-size: 0.9rem;"><?= htmlspecialchars($row['mesa_nombre'] ?: 'N/A') ?></span><br>
                                    <small style="font-size: 0.8rem;">👕 M: <?= htmlspecialchars($row['manteleria'] ?: 'N/A') ?></small><br>
                                    <small style="font-size: 0.8rem;">🧻 S: <?= htmlspecialchars($row['color_servilleta'] ?: 'N/A') ?></small>
                                </td>
                                <td>
                                    <?php if ($row['estado'] == 'Confirmada'): ?>
                                        <?php if ($row['firma_nombre']): ?>
                                            <div class="badge-legal" title="<?= htmlspecialchars($row['firma_nombre']) ?>">
                                                ⚖️ Contrato: <?= htmlspecialchars(strlen($row['firma_nombre']) > 30 ? substr($row['firma_nombre'], 0, 30) . '...' : $row['firma_nombre']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($row['observaciones']): ?>
                                            <div class="badge-cocina" title="<?= htmlspecialchars($row['observaciones']) ?>">
                                                ⚠️ Cocina: <?= substr(htmlspecialchars($row['observaciones']), 0, 40) ?>...
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($row['equipos_audiovisuales']): ?>
                                            <div class="badge-it" title="<?= htmlspecialchars($row['equipos_audiovisuales']) ?>">
                                                🎤 IT: <?= substr(htmlspecialchars($row['equipos_audiovisuales']), 0, 30) ?>...
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em style="color: #999; font-size: 0.9rem;">Esperando confirmación...</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn-copy"
                                        onclick="copyToClipboard(event, '<?= $row['token'] ?>')"
                                        title="Copiar enlace">
                                        🔗
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
    
    // Función para copiar al portapapeles
    function copyToClipboard(event, token) {
        event.stopPropagation();
        const url = window.location.origin + '/eventos-reservas/confirmar.php?token=' + token;
        navigator.clipboard.writeText(url).then(() => {
            const originalText = event.target.innerHTML;
            event.target.innerHTML = '✓';
            event.target.style.color = '#27ae60';
            
            setTimeout(() => {
                event.target.innerHTML = originalText;
                event.target.style.color = '';
            }, 2000);
        }).catch(err => {
            console.error('Error al copiar: ', err);
        });
    }
    
    // Pasar variables de permisos desde PHP
    window.esAdminPrincipal = <?= $es_admin_principal ? 'true' : 'false' ?>;
    window.adminNombre = "<?= addslashes($admin_info['nombre_completo'] ?? '') ?>";
    </script>
</body>
</html>