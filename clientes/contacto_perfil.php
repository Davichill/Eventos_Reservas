<?php
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: contactos.php");
    exit();
}

/**
 * CONSULTA CORREGIDA: 
 * Usamos 'admin_usuarios' para obtener el nombre del administrador que creó el registro.
 */
$query = "SELECT c.*, u.nombre_completo as creador 
          FROM clientes c 
          LEFT JOIN admin_usuarios u ON c.id_usuario_creador = u.id 
          WHERE c.id = $id";
$res = $conn->query($query);
$cliente = $res->fetch_assoc();

if (!$cliente) {
    echo "Cliente no encontrado.";
    exit();
}

// Obtenemos las reservas (eventos) de este cliente
$query_eventos = "SELECT * FROM reservas WHERE id_cliente = $id ORDER BY fecha_evento DESC";
$eventos = $conn->query($query_eventos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil | <?= htmlspecialchars($cliente['cliente_nombre']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        .profile-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* Lateral Izquierdo */
        .profile-side {
            text-align: center;
        }

        .avatar {
            width: 70px;
            height: 70px;
            background: var(--dorado-quito);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 15px;
            font-weight: bold;
        }

        .data-label {
            font-size: 0.7rem;
            color: #aaa;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 15px;
            text-align: left;
        }

        .data-value {
            font-size: 0.95rem;
            color: #333;
            margin-top: 2px;
            text-align: left;
            border-bottom: 1px solid #f9f9f9;
            padding-bottom: 5px;
        }

        /* Lista de Eventos */
        .event-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #fcfcfc;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: 0.3s;
        }

        .event-card:hover {
            border-color: var(--dorado-quito);
            background: #fff;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-confirmada {
            background: #e1f7e1;
            color: #2ecc71;
        }

        .status-pendiente {
            background: #fef5e7;
            color: #f39c12;
        }

        .btn-edit {
            display: block;
            width: 100%;
            background: var(--azul-quito);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-edit:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-bar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="contactos.php" style="color: #666;"><i class="fas fa-chevron-left"></i> Volver</a>
                <h2 style="color: var(--azul-quito);">Perfil del Cliente</h2>
            </div>
        </header>

        <div class="content-body">
            <div class="profile-layout">

                <div class="card profile-side">
                    <div class="avatar"><?= substr($cliente['cliente_nombre'], 0, 1) ?></div>
                    <h3 style="color: var(--azul-quito);">
                        <?= htmlspecialchars($cliente['cliente_nombre'] . " " . ($cliente['cliente_apellido'] ?? '')) ?>
                    </h3>
                    <p style="font-size: 0.85rem; color: #888;">
                        <?= htmlspecialchars($cliente['razon_social'] ?: 'Persona Natural') ?></p>

                    <div class="data-label">RUC / Cédula</div>
                    <div class="data-value"><?= htmlspecialchars($cliente['identificacion']) ?></div>

                    <div class="data-label">Correo Electrónico</div>
                    <div class="data-value"><?= htmlspecialchars($cliente['cliente_email']) ?></div>

                    <div class="data-label">Teléfono</div>
                    <div class="data-value"><?= htmlspecialchars($cliente['cliente_telefono']) ?></div>

                    <div class="data-label">Dirección</div>
                    <div class="data-value"><?= htmlspecialchars($cliente['direccion_fiscal'] ?: 'No registrada') ?>
                    </div>

                    <div class="data-label">Registrado por</div>
                    <div class="data-value" style="color: var(--dorado-quito);"><i class="fas fa-user-shield"></i>
                        <?= htmlspecialchars($cliente['creador'] ?? 'Admin Principal') ?></div>

                    <a href="contacto_editar.php?id=<?= $id ?>" class="btn-edit">
                        <i class="fas fa-user-edit"></i> Editar Contacto
                    </a>
                </div>

                <div class="card">
                    <h4
                        style="color: var(--azul-quito); margin-bottom: 20px; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px;">
                        <i class="fas fa-calendar-check"></i> Historial de Eventos
                    </h4>

                    <?php if ($eventos->num_rows > 0): ?>
                        <?php while ($ev = $eventos->fetch_assoc()): ?>
                            <div class="event-card">
                                <div>
                                    <div style="font-weight: bold; color: var(--azul-quito);">
                                        <?= htmlspecialchars($ev['nombre_evento'] ?? 'Reserva General') ?></div>
                                    <div style="font-size: 0.8rem; color: #999; margin-top: 4px;">
                                        <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($ev['fecha_evento'])) ?>
                                        &nbsp;&nbsp; <i class="far fa-clock"></i> <?= $ev['hora_inicio'] ?? '--:--' ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-pill status-<?= strtolower($ev['estado'] ?? 'pendiente') ?>">
                                        <?= $ev['estado'] ?? 'Pendiente' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: #ccc;">
                            <i class="fas fa-calendar-times fa-4x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>Este cliente no tiene eventos ni reservas registradas.</p>
                            <a href="nueva_invitacion.php?id_cliente=<?= $id ?>"
                                style="color: var(--azul-quito); font-weight: bold; text-decoration: none; display: inline-block; margin-top: 10px;">
                                <i class="fas fa-plus"></i> Crear nueva reserva ahora
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>

</html>