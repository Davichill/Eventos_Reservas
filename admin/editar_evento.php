<?php
ob_start();
include '../php/conexion.php';
session_start();

// Verificación de seguridad
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información del administrador
$admin_id = $_SESSION['admin_id'];
$sql_admin = "SELECT id, usuario, nombre_completo, tipo FROM admin_usuarios WHERE id = '$admin_id'";
$res_admin = $conn->query($sql_admin);
$admin_info = $res_admin->fetch_assoc();
$es_admin_principal = ($admin_info && $admin_info['id'] == 1 && $admin_info['tipo'] == 'principal');

// OBTENER DATOS ACTUALES CON TODAS LAS RELACIONES
$sql = "SELECT r.*, 
               c.*,
               e.nombre as tipo_evento_nombre,
               s.nombre_salon,
               m.nombre as mesa_nombre
        FROM reservas r 
        JOIN clientes c ON r.id_cliente = c.id 
        JOIN tipos_evento e ON r.id_tipo_evento = e.id
        LEFT JOIN salones s ON r.id_salon = s.id
        LEFT JOIN mesas m ON r.id_mesa = m.id
        WHERE r.id = $evento_id";

$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}
$row = $res->fetch_assoc();

// Agrega esta variable después de obtener $row
$id_tipo_evento_actual = $row['id_tipo_evento'];

$sql_menus_actuales = "SELECT id, categoria, nombre_plato 
                       FROM reserva_detalles_menu 
                       WHERE id_reserva = $evento_id";
$res_menus_actuales = $conn->query($sql_menus_actuales);
$menus_seleccionados = [];
$platos_nombres_seleccionados = [];

if ($res_menus_actuales && $res_menus_actuales->num_rows > 0) {
    while ($menu = $res_menus_actuales->fetch_assoc()) {
        $menus_seleccionados[] = $menu;
        $platos_nombres_seleccionados[] = $menu['nombre_plato'];
    }
}

// Obtener todos los platos disponibles
// Determinar qué tablas de menú corresponden al tipo de evento actual
switch($id_tipo_evento_actual) {
    case 1: // Desayuno
        $sql_platos = "SELECT id, nombre as nombre_plato, 'Desayuno' as categoria, 'menu_desayunos' as tabla_origen 
                       FROM menu_desayunos 
                       ORDER BY nombre_plato";
        break;
    case 2: // Seminario
        $sql_platos = "SELECT id, nombre as nombre_plato, 'Seminario' as categoria, 'menu_seminario' as tabla_origen 
                       FROM menu_seminario 
                       ORDER BY nombre_plato";
        break;
    case 3: // Cóctel
        $sql_platos = "SELECT id, nombre as nombre_plato, 'Cóctel' as categoria, 'menu_coctel' as tabla_origen 
                       FROM menu_coctel 
                       ORDER BY nombre_plato";
        break;
    case 5: // Almuerzo/Cena
        $sql_platos = "SELECT id, nombre as nombre_plato, 'Almuerzo/Cena' as categoria, 'menu_almuerzo_cena' as tabla_origen 
                       FROM menu_almuerzo_cena 
                       ORDER BY nombre_plato";
        break;
    case 6: // Coffee Break
        $sql_platos = "SELECT id, nombre as nombre_plato,'Coffee Break' as categoria, 'menu_coffee_break' as tabla_origen 
                       FROM menu_coffee_break 
                       ORDER BY nombre_plato";
        break;
    default:
        // Si no hay coincidencia, mostrar todos
        $sql_platos = "SELECT id, nombre as nombre_plato, 'Desayuno' as categoria, 'menu_desayunos' as tabla_origen 
                       FROM menu_desayunos
                       UNION ALL
                       SELECT id, nombre as nombre_plato, 'Almuerzo/Cena' as categoria, 'menu_almuerzo_cena' as tabla_origen 
                       FROM menu_almuerzo_cena
                       UNION ALL
                       SELECT id, nombre as nombre_plato,  'Cóctel' as categoria, 'menu_coctel' as tabla_origen 
                       FROM menu_coctel
                       UNION ALL
                       SELECT id, nombre as nombre_plato, 'Coffee Break' as categoria, 'menu_coffee_break' as tabla_origen 
                       FROM menu_coffee_break
                       UNION ALL
                       SELECT id, nombre as nombre_plato, 'Seminario' as categoria, 'menu_seminario' as tabla_origen 
                       FROM menu_seminario
                       ORDER BY categoria, nombre_plato";
        break;
}

$res_platos = $conn->query($sql_platos);
$platos_disponibles = [];
$categorias_platos = [];

if ($res_platos && $res_platos->num_rows > 0) {
    while ($plato = $res_platos->fetch_assoc()) {
        $platos_disponibles[] = $plato; // Usamos array indexado en lugar de asociativo por ID
        $categorias_platos[$plato['categoria']][] = $plato;
    }
}

// Obtener salones para el select
$salones = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon ASC");

// Obtener tipos de evento
$tipos_evento = $conn->query("SELECT id, nombre FROM tipos_evento ORDER BY nombre ASC");

// Obtener mesas disponibles
$mesas = $conn->query("SELECT id, nombre FROM mesas ORDER BY nombre ASC");

// 1. PROCESAR ACTUALIZACIÓN GENERAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar'])) {
    // Datos generales de la reserva
    $nombre_evento = $conn->real_escape_string($_POST['nombre_evento']);
    $id_salon = intval($_POST['id_salon']);
    $id_mesa = intval($_POST['id_mesa']);
    $id_tipo_evento = intval($_POST['id_tipo_evento']);
    $fecha = $_POST['fecha_evento'];
    $h_inicio = $_POST['hora_inicio'];
    $h_fin = $_POST['hora_fin'];
    $pax = intval($_POST['cantidad_personas']);
    $estado = $_POST['estado'];
    $observaciones = $conn->real_escape_string($_POST['observaciones']);
    $notas = $conn->real_escape_string($_POST['notas']);
    $equipos = $conn->real_escape_string($_POST['equipos_audiovisuales']);
    $total_evento = floatval($_POST['total_evento']);
    
    // Datos del contacto del evento
    $contacto_nombre = $conn->real_escape_string($_POST['contacto_evento_nombre']);
    $contacto_telefono = $conn->real_escape_string($_POST['contacto_evento_telefono']);

    // Actualizar reserva
    $sql_update = "UPDATE reservas SET 
                    nombre_evento = '$nombre_evento',
                    id_salon = '$id_salon',
                    id_mesa = '$id_mesa',
                    id_tipo_evento = '$id_tipo_evento',
                    fecha_evento = '$fecha',
                    hora_inicio = '$h_inicio',
                    hora_fin = '$h_fin',
                    cantidad_personas = '$pax',
                    estado = '$estado',
                    observaciones = '$observaciones',
                    notas = '$notas',
                    equipos_audiovisuales = '$equipos',
                    total_evento = '$total_evento',
                    contacto_evento_nombre = '$contacto_nombre',
                    contacto_evento_telefono = '$contacto_telefono'
                   WHERE id = $evento_id";

    if ($conn->query($sql_update)) {
        // 2. PROCESAR ACTUALIZACIÓN DE MENÚS
        if (isset($_POST['platos'])) {
            // Eliminar menús anteriores
            $conn->query("DELETE FROM reserva_detalles_menu WHERE id_reserva = $evento_id");
            
            // Insertar nuevos menús
            $platos_indices = $_POST['platos'] ?? [];
            
            foreach ($platos_indices as $index) {
                $index = intval($index);
                if ($index >= 0 && $index < count($platos_disponibles)) {
                    $plato = $platos_disponibles[$index];
                    $nombre_plato = $conn->real_escape_string($plato['nombre_plato']);
                    $categoria = $conn->real_escape_string($plato['categoria']);
                    
                    // Nota: No podemos almacenar cantidad, notas ni precio en la estructura actual
                    $sql_menu = "INSERT INTO reserva_detalles_menu 
                                (id_reserva, categoria, nombre_plato) 
                                VALUES ($evento_id, '$categoria', '$nombre_plato')";
                    $conn->query($sql_menu);
                }
            }
        }
        
        // 3. ACTUALIZAR DATOS DEL CLIENTE (si es admin principal)
        if ($es_admin_principal) {
            $cliente_nombre = $conn->real_escape_string($_POST['cliente_nombre']);
            $cliente_email = $conn->real_escape_string($_POST['cliente_email']);
            $cliente_telefono = $conn->real_escape_string($_POST['cliente_telefono']);
            $razon_social = $conn->real_escape_string($_POST['razon_social']);
            $identificacion = $conn->real_escape_string($_POST['identificacion']);
            $correo_facturacion = $conn->real_escape_string($_POST['correo_facturacion']);
            
            $sql_cliente = "UPDATE clientes SET 
                           cliente_nombre = '$cliente_nombre',
                           cliente_email = '$cliente_email',
                           cliente_telefono = '$cliente_telefono',
                           razon_social = '$razon_social',
                           identificacion = '$identificacion',
                           correo_facturacion = '$correo_facturacion'
                           WHERE id = {$row['id_cliente']}";
            $conn->query($sql_cliente);
        }
        
        header("Location: detalle_evento.php?id=$evento_id&msg=updated");
        exit();
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
} // <-- ESTA ES LA LLAVE QUE FALTABA

// Convertir $platos_disponibles a JSON para JavaScript
$platos_disponibles_json = json_encode($platos_disponibles);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Evento #<?= $evento_id ?> | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        :root {
            --primary: #001f3f;
            --accent: #a68945;
            --bg: #f4f7f9;
            --success: #27ae60;
            --warning: #f39c12;
        }

        body {
            background: var(--bg);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        .edit-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 1400px;
            margin: auto;
            overflow: hidden;
        }

        .edit-header {
            background: linear-gradient(135deg, var(--primary) 0%, #003366 100%);
            color: white;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-container {
            padding: 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full {
            grid-column: span 2;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: 0.3s;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(166, 137, 69, 0.1);
        }

        .section-divider {
            grid-column: span 2;
            border-bottom: 2px solid #f0f0f0;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            color: var(--accent);
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-divider i {
            margin-right: 10px;
        }

        .btn-save {
            background: var(--accent);
            color: var(--primary);
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-save:hover {
            background: #c19a2d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(166, 137, 69, 0.3);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .time-value-display {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        /* Estilos para la sección de menús */
        .menu-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .menu-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            align-items: center;
        }

        .menu-item-header {
            grid-column: span 4;
            background: #f0f0f0;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-weight: bold;
            color: var(--primary);
        }

        .btn-add-menu {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-remove-menu {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Selectores de hora */
        .select-hora {
            position: relative;
            width: 100%;
        }

        .select-hora select {
            width: 100%;
            max-width: 300px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
        }

        .select-hora select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(166, 137, 69, 0.1);
        }

        /* Tabs para diferentes secciones */
        .tab-container {
            margin-bottom: 30px;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: 0.3s;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Estilos para montos */
        .monto-input {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full {
                grid-column: span 1;
            }
            
            .section-divider {
                grid-column: span 1;
            }
            
            .menu-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .menu-item-header {
                grid-column: span 1;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .edit-header {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="edit-card">
            <div class="edit-header">
                <div>
                    <h2 style="margin:0; font-size: 1.8rem;">
                        <i class="fas fa-edit"></i> Editar Expediente #<?= $evento_id ?>
                    </h2>
                    <small style="opacity: 0.9;">Cliente: <?= htmlspecialchars($row['cliente_nombre']) ?></small>
                </div>
                <a href="detalle_evento.php?id=<?= $evento_id ?>" style="color:white; text-decoration:none;">
                    <i class="fas fa-times fa-lg"></i>
                </a>
            </div>

            <!-- Tabs de navegación -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button type="button" class="tab-btn active" onclick="showTab('general')">
                        <i class="fas fa-info-circle"></i> Información General
                    </button>
                    <button type="button" class="tab-btn" onclick="showTab('menu')">
                        <i class="fas fa-utensils"></i> Menú del Evento
                    </button>
                    <button type="button" class="tab-btn" onclick="showTab('cliente')">
                        <i class="fas fa-user"></i> Datos del Cliente
                    </button>
                    <button type="button" class="tab-btn" onclick="showTab('pagos')">
                        <i class="fas fa-money-check-alt"></i> Pagos
                    </button>
                </div>

                <form action="" method="POST" class="form-container" onsubmit="return validarFormulario()" id="mainForm">
                    
                    <!-- TAB 1: INFORMACIÓN GENERAL -->
                    <div id="tab-general" class="tab-content active">
                        <div class="form-grid">
                            <div class="section-divider">
                                <i class="fas fa-calendar-alt"></i> INFORMACIÓN DEL EVENTO
                            </div>

                            <div class="form-group">
                                <label>Tipo de Evento</label>
                                <select name="id_tipo_evento" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <?php while ($tipo = $tipos_evento->fetch_assoc()): ?>
                                        <option value="<?= $tipo['id'] ?>" 
                                            <?= ($tipo['id'] == $row['id_tipo_evento']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipo['nombre']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Nombre Personalizado del Evento</label>
                                <input type="text" name="nombre_evento" 
                                       value="<?= htmlspecialchars($row['nombre_evento']) ?>"
                                       placeholder="Ej: Boda Familia Perez">
                            </div>

                            <div class="form-group">
                                <label>Estado de Reserva</label>
                                <select name="estado" style="border-left: 5px solid var(--accent);">
                                    <option value="Pendiente" <?= ($row['estado'] == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="Confirmada" <?= ($row['estado'] == 'Confirmada') ? 'selected' : '' ?>>Confirmada</option>
                                    <option value="Cancelada" <?= ($row['estado'] == 'Cancelada') ? 'selected' : '' ?>>Cancelada</option>
                                    <option value="Completada" <?= ($row['estado'] == 'Completada') ? 'selected' : '' ?>>Completada</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Total del Evento</label>
                                <input type="number" name="total_evento" class="monto-input"
                                       value="<?= $row['total_evento'] ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>

                            <div class="section-divider">
                                <i class="fas fa-hotel"></i> UBICACIÓN Y HORARIO
                            </div>

                            <div class="form-group">
                                <label>Salón</label>
                                <select name="id_salon">
                                    <option value="">Sin salón asignado</option>
                                    <?php
                                    $salones->data_seek(0);
                                    while ($s = $salones->fetch_assoc()): ?>
                                        <option value="<?= $s['id'] ?>" 
                                            <?= ($s['id'] == $row['id_salon']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['nombre_salon']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Mesa / Estación</label>
                                <select name="id_mesa">
                                    <option value="">Sin mesa asignada</option>
                                    <?php
                                    $mesas->data_seek(0);
                                    while ($m = $mesas->fetch_assoc()): ?>
                                        <option value="<?= $m['id'] ?>" 
                                            <?= ($m['id'] == $row['id_mesa']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nombre']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Fecha del Evento</label>
                                <input type="date" name="fecha_evento" value="<?= $row['fecha_evento'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Pax (Personas)</label>
                                <input type="number" name="cantidad_personas" 
                                       value="<?= $row['cantidad_personas'] ?>" required min="1">
                            </div>

                            <div class="form-group">
                                <label>Hora Inicio</label>
                                <div class="select-hora">
                                    <select name="hora_inicio" id="hora_inicio" required>
                                        <option value="">Seleccione hora...</option>
                                    </select>
                                </div>
                                <div class="time-value-display">
                                    Actual: <?= date('h:i A', strtotime($row['hora_inicio'])) ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Hora Fin</label>
                                <div class="select-hora">
                                    <select name="hora_fin" id="hora_fin" required>
                                        <option value="">Seleccione inicio primero</option>
                                    </select>
                                </div>
                                <div class="time-value-display">
                                    Actual: <?= date('h:i A', strtotime($row['hora_fin'])) ?>
                                </div>
                            </div>

                            <div class="section-divider">
                                <i class="fas fa-info-circle"></i> CONTACTO DEL EVENTO
                            </div>

                            <div class="form-group">
                                <label>Nombre del Contacto</label>
                                <input type="text" name="contacto_evento_nombre" 
                                       value="<?= htmlspecialchars($row['contacto_evento_nombre']) ?>"
                                       placeholder="Persona de contacto para el evento">
                            </div>

                            <div class="form-group">
                                <label>Teléfono del Contacto</label>
                                <input type="text" name="contacto_evento_telefono" 
                                       value="<?= htmlspecialchars($row['contacto_evento_telefono']) ?>"
                                       placeholder="Teléfono directo">
                            </div>

                            <div class="form-group full">
                                <label>Equipos Audiovisuales / Requerimientos Especiales</label>
                                <input type="text" name="equipos_audiovisuales"
                                       value="<?= htmlspecialchars($row['equipos_audiovisuales']) ?>"
                                       placeholder="Ej: Proyector, micrófonos, pantalla...">
                            </div>

                            <div class="section-divider">
                                <i class="fas fa-sticky-note"></i> NOTAS Y OBSERVACIONES
                            </div>

                            <div class="form-group">
                                <label>Observaciones de Cocina (Menú)</label>
                                <textarea name="observaciones" rows="4"
                                          placeholder="Notas específicas para cocina..."><?= htmlspecialchars($row['observaciones']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Notas Internas / Logística</label>
                                <textarea name="notas" rows="4"
                                          placeholder="Notas internas para el equipo..."><?= htmlspecialchars($row['notas']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: MENÚ DEL EVENTO -->
                    <div id="tab-menu" class="tab-content">
                        <div class="form-grid">
                            <div class="section-divider full">
                                <i class="fas fa-utensils"></i> MENÚ SELECCIONADO
                            </div>

                            <div class="form-group full">
                                <div id="menu-items-container">
    <?php if (count($menus_seleccionados) > 0): ?>
        <?php foreach ($menus_seleccionados as $index => $menu): ?>
            <div class="menu-item" data-index="<?= $index ?>">
                <select name="platos[]" class="plato-select" required>
                    <option value="">Seleccionar plato...</option>
                    <?php foreach ($categorias_platos as $categoria => $platos_cat): ?>
                        <optgroup label="<?= htmlspecialchars($categoria) ?>">
                            <?php foreach ($platos_cat as $plato_index => $plato): ?>
                                <option value="<?= $plato_index ?>" 
                                         
                                        <?= ($plato['nombre_plato'] == $menu['nombre_plato']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plato['nombre_plato']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                
                <!-- Nota: No podemos guardar cantidad en la estructura actual -->
                <div style="padding: 12px; background: #f0f0f0; border-radius: 6px; text-align: center;">
                    <i class="fas fa-info-circle"></i> Cantidad no disponible
                </div>
                
                <div style="padding: 12px; background: #f0f0f0; border-radius: 6px; text-align: center;">
                    <i class="fas fa-info-circle"></i> Notas no disponibles
                </div>
                
                <button type="button" class="btn-remove-menu" onclick="removeMenuItem(this)">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="menu-item" data-index="0">
            <select name="platos[]" class="plato-select" required>
                <option value="">Seleccionar plato...</option>
                <?php foreach ($categorias_platos as $categoria => $platos_cat): ?>
                    <optgroup label="<?= htmlspecialchars($categoria) ?>">
                        <?php foreach ($platos_cat as $plato_index => $plato): ?>
                            <option value="<?= $plato_index ?>" 
                                    >
                                <?= htmlspecialchars($plato['nombre_plato']) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            
            <div style="padding: 12px; background: #f0f0f0; border-radius: 6px; text-align: center;">
                <i class="fas fa-info-circle"></i> Cantidad no disponible
            </div>
            
            <div style="padding: 12px; background: #f0f0f0; border-radius: 6px; text-align: center;">
                <i class="fas fa-info-circle"></i> Notas no disponibles
            </div>
            
            <button type="button" class="btn-remove-menu" onclick="removeMenuItem(this)">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    <?php endif; ?>
</div>
                                
                                <button type="button" class="btn-add-menu" onclick="addMenuItem()">
                                    <i class="fas fa-plus"></i> Agregar Plato
                                </button>
                                
                                <div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border-radius: 8px;">
                                    <strong>Total estimado del menú:</strong> 
                                    <span id="total-menu">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATOS DEL CLIENTE -->
                    <div id="tab-cliente" class="tab-content">
                        <div class="form-grid">
                            <div class="section-divider full">
                                <i class="fas fa-user"></i> INFORMACIÓN DEL CLIENTE
                            </div>

                            <?php if ($es_admin_principal): ?>
                                <div class="form-group">
                                    <label>Nombre del Cliente</label>
                                    <input type="text" name="cliente_nombre" 
                                           value="<?= htmlspecialchars($row['cliente_nombre']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Email del Cliente</label>
                                    <input type="email" name="cliente_email" 
                                           value="<?= htmlspecialchars($row['cliente_email']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Teléfono del Cliente</label>
                                    <input type="text" name="cliente_telefono" 
                                           value="<?= htmlspecialchars($row['cliente_telefono']) ?>">
                                </div>

                                <div class="section-divider full">
                                    <i class="fas fa-file-invoice"></i> DATOS DE FACTURACIÓN
                                </div>

                                <div class="form-group">
                                    <label>Razón Social</label>
                                    <input type="text" name="razon_social" 
                                           value="<?= htmlspecialchars($row['razon_social']) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Identificación / RUC</label>
                                    <input type="text" name="identificacion" 
                                           value="<?= htmlspecialchars($row['identificacion']) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Email de Facturación</label>
                                    <input type="email" name="correo_facturacion" 
                                           value="<?= htmlspecialchars($row['correo_facturacion']) ?>">
                                </div>
                            <?php else: ?>
                                <div class="form-group full" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-lock fa-3x" style="color: #ccc; margin-bottom: 20px;"></i>
                                    <h3 style="color: #666;">Solo administradores principales pueden editar datos del cliente</h3>
                                    <p>Contacte al administrador principal para realizar cambios en esta sección.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TAB 4: PAGOS -->
                    <div id="tab-pagos" class="tab-content">
                        <div class="form-grid">
                            <div class="section-divider full">
                                <i class="fas fa-money-check-alt"></i> INFORMACIÓN DE PAGOS
                            </div>

                            <div class="form-group full" style="text-align: center; padding: 40px;">
                                <i class="fas fa-external-link-alt fa-3x" style="color: var(--accent); margin-bottom: 20px;"></i>
                                <h3 style="color: var(--primary);">Gestión de Pagos</h3>
                                <p>La gestión de pagos se realiza desde la página de detalle del evento.</p>
                                <a href="detalle_evento.php?id=<?= $evento_id ?>" class="btn-save" style="margin-top: 20px;">
                                    <i class="fas fa-external-link-alt"></i> Ir a Gestión de Pagos
                                </a>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #f0f0f0; display: flex; gap: 15px; justify-content: flex-end;">
                        <a href="detalle_evento.php?id=<?= $evento_id ?>" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="actualizar" class="btn-save">
                            <i class="fas fa-save"></i> Guardar Todos los Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Pasar los platos disponibles a JavaScript
        const platosDisponibles = <?= $platos_disponibles_json ?>;
        
        document.addEventListener('DOMContentLoaded', function () {
            // ... (resto del código JavaScript permanece igual) ...
        });

        // Funciones para los tabs
        function showTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar el tab seleccionado
            document.getElementById(`tab-${tabName}`).classList.add('active');
            
            // Actualizar botones del tab
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`).classList.add('active');
        }

        // Funciones para la gestión de menús
        function addMenuItem() {
            const container = document.getElementById('menu-items-container');
            const items = container.querySelectorAll('.menu-item');
            const newIndex = items.length;
            
            const newItem = document.createElement('div');
            newItem.className = 'menu-item';
            newItem.setAttribute('data-index', newIndex);
            
            // Crear el HTML del select con las opciones dinámicas
            let selectHTML = '<select name="platos[]" class="plato-select" required onchange="calculateTotal()">';
            selectHTML += '<option value="">Seleccionar plato...</option>';
            
            // Agrupar por categoría
            const categoriasAgrupadas = {};
            platosDisponibles.forEach((plato, index) => {
                if (!categoriasAgrupadas[plato.categoria]) {
                    categoriasAgrupadas[plato.categoria] = [];
                }
                categoriasAgrupadas[plato.categoria].push({...plato, originalIndex: index});
            });
            
            // Generar optgroups
            for (const [categoria, platos] of Object.entries(categoriasAgrupadas)) {
                selectHTML += `<optgroup label="${categoria}">`;
                platos.forEach(plato => {
                    selectHTML += `<option value="${plato.originalIndex}" data-precio="${plato.precio}">`;
                    selectHTML += `${plato.nombre_plato} - $${parseFloat(plato.precio).toFixed(2)}`;
                    selectHTML += `</option>`;
                });
                selectHTML += `</optgroup>`;
            }
            
            selectHTML += '</select>';
            
            newItem.innerHTML = selectHTML + 
                `<div style="padding: 12px; background: #f0f0f0; border-radius: 6px; text-align: center;">
                    <i class="fas fa-info-circle"></i> Cantidad no disponible
                </div>
                <div style="padding: 12px; background: #f0f0f0; border-radius: 6px; text-align: center;">
                    <i class="fas fa-info-circle"></i> Notas no disponibles
                </div>
                <button type="button" class="btn-remove-menu" onclick="removeMenuItem(this)">
                    <i class="fas fa-trash"></i> Eliminar
                </button>`;
            
            container.appendChild(newItem);
            calculateTotal();
        }

        function removeMenuItem(button) {
            const container = document.getElementById('menu-items-container');
            const items = container.querySelectorAll('.menu-item');
            
            if (items.length > 1) {
                const item = button.closest('.menu-item');
                item.remove();
                
                // Reindexar los items
                container.querySelectorAll('.menu-item').forEach((item, index) => {
                    item.setAttribute('data-index', index);
                });
                
                calculateTotal();
            } else {
                alert('Debe haber al menos un plato en el menú.');
            }
        }

        function calculateTotal() {
            let total = 0;
            const platoSelects = document.querySelectorAll('.plato-select');
            
            platoSelects.forEach(select => {
                if (select.value && select.selectedOptions[0]) {
                    const precio = parseFloat(select.selectedOptions[0].dataset.precio) || 0;
                    // Como no tenemos cantidad, asumimos 1 por defecto
                    total += precio * 1;
                }
            });
            
            document.getElementById('total-menu').textContent = '$' + total.toFixed(2);
            
            // Actualizar también el total del evento si existe el campo
            const totalEventoInput = document.querySelector('input[name="total_evento"]');
            if (totalEventoInput) {
                totalEventoInput.value = total.toFixed(2);
            }
        }

        // Validación del formulario
        function validarFormulario() {
            const startSelect = document.getElementById('hora_inicio');
            const endSelect = document.getElementById('hora_fin');
            const totalEvento = document.querySelector('input[name="total_evento"]');
            
            // Validar horario
            if (!startSelect.value || !endSelect.value) {
                alert("Por favor, seleccione tanto la hora de inicio como la de finalización.");
                return false;
            }

            const [h1, m1] = startSelect.value.split(':').map(Number);
            const [h2, m2] = endSelect.value.split(':').map(Number);

            const inicioMins = (h1 * 60) + m1;
            const finMins = (h2 * 60) + m2;

            if (finMins <= inicioMins) {
                alert("La hora de finalización debe ser mayor a la hora de inicio.");
                return false;
            }

            if ((finMins - inicioMins) < 30) {
                alert("La duración mínima del evento debe ser de 30 minutos.");
                return false;
            }

            // Validar que haya al menos un plato en el menú
            const platoSelects = document.querySelectorAll('.plato-select');
            let hasAtLeastOnePlato = false;
            platoSelects.forEach(select => {
                if (select.value) hasAtLeastOnePlato = true;
            });
            
            if (!hasAtLeastOnePlato) {
                alert("Debe seleccionar al menos un plato para el menú.");
                return false;
            }

            // Validar total del evento
            if (totalEvento && parseFloat(totalEvento.value) < 0) {
                alert("El total del evento no puede ser negativo.");
                return false;
            }

            return true;
        }

        // Calcular total inicial
        setTimeout(() => {
            calculateTotal();
        }, 100);
    </script>
</body>
</html>