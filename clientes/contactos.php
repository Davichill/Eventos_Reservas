<?php
include '../php/conexion.php';
session_start();

// Seguridad
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Contactos | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        /* Estilos de la cabecera */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn-nuevo {
            background: var(--dorado-quito);
            color: var(--azul-quito);
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-nuevo:hover {
            background: var(--azul-quito);
            color: var(--blanco);
            transform: translateY(-2px);
        }

        /* Estilos de la tabla interactiva */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: #f8f9fa;
            color: var(--azul-quito);
            padding: 15px;
            text-align: left;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #eee;
        }

        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clickable-row:hover {
            background-color: #fdf8f0 !important; /* Resalte suave */
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
        }

        .client-info strong {
            display: block;
            color: var(--azul-quito);
            font-size: 1rem;
        }

        .client-info small {
            color: #777;
            font-weight: 500;
        }

        .badge-id {
            background: #eef2f7;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #333;
        }

        /* Iconos de acción */
        .action-container {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .action-btn {
            color: var(--azul-quito);
            transition: 0.3s;
            padding: 8px;
            border-radius: 50%;
        }

        .action-btn:hover {
            background: rgba(0, 31, 63, 0.1);
            color: var(--accent-color);
            transform: scale(1.1);
        }

    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-bar">
            <div class="user-info">
                <span>Bienvenido, <span class="user-name"><?= $_SESSION['admin_nombre'] ?? 'Admin' ?></span></span>
                <a href="../auth/logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </header>

        <div class="content-body">
            <div class="page-header">
                <h1 style="color: var(--azul-quito);"><i class="fas fa-address-book"></i> Directorio de Clientes</h1>
                <a href="contacto_nuevo.php" class="btn-nuevo">
                    <i class="fas fa-plus-circle"></i> Agregar Nuevo Cliente
                </a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente / Empresa</th>
                            <th>Ubicación</th>
                            <th>Identificación</th>
                            <th>Información de Contacto</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT * FROM clientes ORDER BY id DESC");
                        if ($res->num_rows > 0):
                            while ($c = $res->fetch_assoc()): ?>
                                <tr class="clickable-row" data-href="contacto_perfil.php?id=<?= $c['id'] ?>">
                                    <td class="client-info">
                                        <strong><?= htmlspecialchars($c['cliente_nombre'] . " " . ($c['cliente_apellido'] ?? '')) ?></strong>
                                        <small><?= htmlspecialchars($c['razon_social'] ?: 'Consumidor Final') ?></small>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <i class="fas fa-map-marker-alt" style="color: var(--dorado-quito);"></i> 
                                            <?= htmlspecialchars(($c['ciudad'] ?? 'Quito') . ", " . ($c['pais'] ?? 'Ecuador')) ?>
                                        </div>
                                    </td>
                                    <td><span class="badge-id"><?= htmlspecialchars($c['identificacion']) ?></span></td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <i class="fas fa-envelope" style="color: #aaa;"></i> <?= htmlspecialchars($c['cliente_email']) ?><br>
                                            <i class="fas fa-phone" style="color: #aaa;"></i> <?= htmlspecialchars($c['cliente_telefono']) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-container">
                                            <a href="nueva_invitacion.php?id_cliente=<?= $c['id'] ?>" 
                                               class="action-btn" 
                                               onclick="event.stopPropagation();" 
                                               title="Nueva Reserva">
                                                <i class="fas fa-calendar-plus fa-lg"></i>
                                            </a>
                                            <i class="fas fa-chevron-right" style="color: #ddd;"></i>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; 
                        else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 50px; color: #aaa;">
                                    <i class="fas fa-info-circle fa-2x"></i><br>No hay clientes registrados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const rows = document.querySelectorAll(".clickable-row");
        rows.forEach(row => {
            row.addEventListener("click", () => {
                const destination = row.getAttribute("data-href");
                if (destination) {
                    window.location.href = destination;
                }
            });
        });
    });
    </script>

</body>
</html>