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

// Obtener menús actuales con TODOS los campos
$sql_menus_actuales = "SELECT * FROM reserva_detalles_menu 
                       WHERE id_reserva = $evento_id 
                       ORDER BY id";
$res_menus_actuales = $conn->query($sql_menus_actuales);
$menus_seleccionados = [];
$platos_nombres_seleccionados = [];

if ($res_menus_actuales && $res_menus_actuales->num_rows > 0) {
    while ($menu = $res_menus_actuales->fetch_assoc()) {
        $menus_seleccionados[] = $menu;
    }
}

// Obtener todos los platos disponibles según el tipo de evento
$platos_disponibles = [];
$categorias_platos = [];

switch($id_tipo_evento_actual) {
    case 1: // Desayuno
        $sql_platos = "SELECT id, nombre as nombre_plato,  tipo as categoria, 
                              '' as subcategoria, 'Desayuno' as tipo_evento, 'menu_desayunos' as tabla_origen 
                       FROM menu_desayunos 
                       ORDER BY tipo, nombre";
        break;
    case 2: // Seminario
        $sql_platos = "SELECT id, nombre as nombre_plato, tiempo as categoria, 
                              subcategoria, 'Seminario' as tipo_evento, 'menu_seminario' as tabla_origen 
                       FROM menu_seminario 
                       ORDER BY tiempo, subcategoria, nombre";
        break;
    case 3: // Cóctel
        $sql_platos = "SELECT id, nombre as nombre_plato,  categoria, 
                              subcategoria, 'Cóctel' as tipo_evento, 'menu_coctel' as tabla_origen 
                       FROM menu_coctel 
                       ORDER BY categoria, subcategoria, nombre";
        break;
    case 5: // Almuerzo/Cena
        $sql_platos = "SELECT id, nombre as nombre_plato,  tiempo as categoria, 
                              subcategoria, 'Almuerzo/Cena' as tipo_evento, 'menu_almuerzo_cena' as tabla_origen 
                       FROM menu_almuerzo_cena 
                       ORDER BY tiempo, subcategoria, nombre";
        break;
    case 6: // Coffee Break
        $sql_platos = "SELECT id, nombre as nombre_plato,categoria, 
                              '' as subcategoria, 'Coffee Break' as tipo_evento, 'menu_coffee_break' as tabla_origen 
                       FROM menu_coffee_break 
                       ORDER BY categoria, nombre";
        break;
    default:
        // Si no hay coincidencia, mostrar todos
        $sql_platos = "SELECT id, nombre as nombre_plato, tipo as categoria, 
                              '' as subcategoria, 'Desayuno' as tipo_evento, 'menu_desayunos' as tabla_origen 
                       FROM menu_desayunos
                       UNION ALL
                       SELECT id, nombre as nombre_plato, tiempo as categoria, 
                              subcategoria, 'Almuerzo/Cena' as tipo_evento, 'menu_almuerzo_cena' as tabla_origen 
                       FROM menu_almuerzo_cena
                       UNION ALL
                       SELECT id, nombre as nombre_plato, categoria, 
                              subcategoria, 'Cóctel' as tipo_evento, 'menu_coctel' as tabla_origen 
                       FROM menu_coctel
                       UNION ALL
                       SELECT id, nombre as nombre_plato, categoria, 
                              '' as subcategoria, 'Coffee Break' as tipo_evento, 'menu_coffee_break' as tabla_origen 
                       FROM menu_coffee_break
                       UNION ALL
                       SELECT id, nombre as nombre_plato, tiempo as categoria, 
                              subcategoria, 'Seminario' as tipo_evento, 'menu_seminario' as tabla_origen 
                       FROM menu_seminario
                       ORDER BY tipo_evento, categoria, subcategoria, nombre_plato";
        break;
}

$res_platos = $conn->query($sql_platos);
$platos_por_id = []; // Para búsqueda rápida por ID

if ($res_platos && $res_platos->num_rows > 0) {
    while ($plato = $res_platos->fetch_assoc()) {
        $platos_disponibles[] = $plato;
        $platos_por_id[$plato['id']] = $plato;
        
        // Organizar por tipo de evento, categoría y subcategoría
        $tipo_evento = $plato['tipo_evento'];
        $categoria = $plato['categoria'];
        $subcategoria = $plato['subcategoria'];
        
        if (!isset($categorias_platos[$tipo_evento])) {
            $categorias_platos[$tipo_evento] = [];
        }
        if (!isset($categorias_platos[$tipo_evento][$categoria])) {
            $categorias_platos[$tipo_evento][$categoria] = [];
        }
        
        if ($subcategoria && $subcategoria != '') {
            if (!isset($categorias_platos[$tipo_evento][$categoria][$subcategoria])) {
                $categorias_platos[$tipo_evento][$categoria][$subcategoria] = [];
            }
            $categorias_platos[$tipo_evento][$categoria][$subcategoria][] = $plato;
        } else {
            $categorias_platos[$tipo_evento][$categoria]['general'][] = $plato;
        }
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
            
            // Insertar nuevos menús con todos los campos
            $platos_ids = $_POST['platos'] ?? [];
            $cantidades = $_POST['cantidades'] ?? [];
            $notas_platos = $_POST['notas_platos'] ?? [];
            $precios_unitarios = $_POST['precios_unitarios'] ?? [];
            
            for ($i = 0; $i < count($platos_ids); $i++) {
                $plato_id = intval($platos_ids[$i]);
                $cantidad = isset($cantidades[$i]) ? intval($cantidades[$i]) : 1;
                $nota_plato = isset($notas_platos[$i]) ? $conn->real_escape_string($notas_platos[$i]) : '';
                $precio_unitario = isset($precios_unitarios[$i]) ? floatval($precios_unitarios[$i]) : 0;
                
                if ($plato_id > 0 && $cantidad > 0) {
                    // Buscar información completa del plato
                    if (isset($platos_por_id[$plato_id])) {
                        $plato_info = $platos_por_id[$plato_id];
                        
                        $sql_menu = "INSERT INTO reserva_detalles_menu 
                                    (id_reserva, categoria, subcategoria, nombre_plato, 
                                     cantidad, precio_unitario, notas, tipo_evento) 
                                    VALUES (
                                        $evento_id, 
                                        '" . $conn->real_escape_string($plato_info['categoria']) . "',
                                        '" . $conn->real_escape_string($plato_info['subcategoria']) . "',
                                        '" . $conn->real_escape_string($plato_info['nombre_plato']) . "',
                                        $cantidad,
                                        $precio_unitario,
                                        '$nota_plato',
                                        '" . $conn->real_escape_string($plato_info['tipo_evento']) . "'
                                    )";
                        $conn->query($sql_menu);
                    }
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
}

// Convertir $platos_disponibles a JSON para JavaScript
$platos_disponibles_json = json_encode($platos_disponibles);
$categorias_platos_json = json_encode($categorias_platos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Evento #<?= $evento_id ?> | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="../css/gestion_menu/editar_evento.css">
    
    <style>
        .menu-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
        }
        
        .menu-item select,
        .menu-item input,
        .menu-item textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-remove-menu {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .btn-remove-menu:hover {
            background: #c82333;
        }
        
        .btn-add-menu {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .btn-add-menu:hover {
            background: #218838;
        }
        
        .menu-subtotal {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .category-header {
            background: #e3f2fd;
            padding: 8px 12px;
            margin: 10px 0 5px 0;
            border-radius: 4px;
            font-weight: bold;
            color: #1565c0;
        }
        
        .subcategory-header {
            background: #f3e5f5;
            padding: 6px 10px;
            margin: 5px 0 3px 0;
            border-radius: 3px;
            font-weight: bold;
            color: #7b1fa2;
            font-size: 0.9em;
        }
        
        .price-display {
            font-weight: bold;
            color: #27ae60;
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
                </div>

                <form action="" method="POST" class="form-container" onsubmit="return validarFormulario()" id="mainForm">
                    <input type="hidden" name="total_evento" id="total-evento-input" value="<?= $row['total_evento'] ?>">
                    
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

                    <!-- TAB 2: MENÚ DEL EVENTO - MEJORADO -->
                    <div id="tab-menu" class="tab-content">
                        <div class="form-grid">
                            <div class="section-divider full">
                                <i class="fas fa-utensils"></i> MENÚ DEL EVENTO
                            </div>

                            <div class="form-group full">
                                <div id="menu-items-container">
                                    <?php if (count($menus_seleccionados) > 0): ?>
                                        <?php foreach ($menus_seleccionados as $index => $menu): ?>
                                            <div class="menu-item" data-index="<?= $index ?>">
                                                <select name="platos[]" class="plato-select" required 
                                                        onchange="updatePlatoInfo(this, <?= $index ?>)">
                                                    <option value="">Seleccionar plato...</option>
                                                    <?php 
                                                    // Generar opciones agrupadas
                                                    foreach ($categorias_platos as $tipo_evento => $categorias): 
                                                        foreach ($categorias as $categoria => $subcategorias):
                                                            // Para Coffee Break que no tiene subcategorías
                                                            if ($tipo_evento == 'Coffee Break' || $tipo_evento == 'Desayuno'): ?>
                                                                <optgroup label="<?= htmlspecialchars($tipo_evento) ?> - <?= htmlspecialchars($categoria) ?>">
                                                                    <?php foreach ($subcategorias['general'] ?? [] as $plato): ?>
                                                                        <option value="<?= $plato['id'] ?>" 
                                                                                data-precio="<?= $plato['precio'] ?>"
                                                                                data-categoria="<?= htmlspecialchars($plato['categoria']) ?>"
                                                                                data-subcategoria="<?= htmlspecialchars($plato['subcategoria']) ?>"
                                                                                data-tipo-evento="<?= htmlspecialchars($plato['tipo_evento']) ?>"
                                                                                <?= ($plato['nombre_plato'] == $menu['nombre_plato']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($plato['nombre_plato']) ?> - $<?= number_format($plato['precio'], 2) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </optgroup>
                                                            <?php else: 
                                                                // Para eventos con subcategorías
                                                                foreach ($subcategorias as $subcategoria => $platos_sub): ?>
                                                                    <optgroup label="<?= htmlspecialchars($tipo_evento) ?> - <?= htmlspecialchars($categoria) ?> - <?= htmlspecialchars($subcategoria) ?>">
                                                                        <?php foreach ($platos_sub as $plato): ?>
                                                                            <option value="<?= $plato['id'] ?>" 
                                                                                    
                                                                                    data-categoria="<?= htmlspecialchars($plato['categoria']) ?>"
                                                                                    data-subcategoria="<?= htmlspecialchars($plato['subcategoria']) ?>"
                                                                                    data-tipo-evento="<?= htmlspecialchars($plato['tipo_evento']) ?>"
                                                                                    <?= ($plato['nombre_plato'] == $menu['nombre_plato']) ? 'selected' : '' ?>>
                                                                                <?= htmlspecialchars($plato['nombre_plato']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </optgroup>
                                                                <?php endforeach; 
                                                            endif;
                                                        endforeach;
                                                    endforeach; ?>
                                                </select>
                                                
                                                
                                                
                                                <button type="button" class="btn-remove-menu" onclick="removeMenuItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="menu-item" data-index="0">
                                            <select name="platos[]" class="plato-select" required 
                                                    onchange="updatePlatoInfo(this, 0)">
                                                <option value="">Seleccionar plato...</option>
                                                <?php 
                                                foreach ($categorias_platos as $tipo_evento => $categorias): 
                                                    foreach ($categorias as $categoria => $subcategorias):
                                                        if ($tipo_evento == 'Coffee Break' || $tipo_evento == 'Desayuno'): ?>
                                                            <optgroup label="<?= htmlspecialchars($tipo_evento) ?> - <?= htmlspecialchars($categoria) ?>">
                                                                <?php foreach ($subcategorias['general'] ?? [] as $plato): ?>
                                                                    <option value="<?= $plato['id'] ?>" 
                                                                            data-precio="<?= $plato['precio'] ?>"
                                                                            data-categoria="<?= htmlspecialchars($plato['categoria']) ?>"
                                                                            data-subcategoria="<?= htmlspecialchars($plato['subcategoria']) ?>"
                                                                            data-tipo-evento="<?= htmlspecialchars($plato['tipo_evento']) ?>">
                                                                        <?= htmlspecialchars($plato['nombre_plato']) ?> - $<?= number_format($plato['precio'], 2) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </optgroup>
                                                        <?php else: 
                                                            foreach ($subcategorias as $subcategoria => $platos_sub): ?>
                                                                <optgroup label="<?= htmlspecialchars($tipo_evento) ?> - <?= htmlspecialchars($categoria) ?> - <?= htmlspecialchars($subcategoria) ?>">
                                                                    <?php foreach ($platos_sub as $plato): ?>
                                                                        <option value="<?= $plato['id'] ?>" 
                                                                                data-precio="<?= $plato['precio'] ?>"
                                                                                data-categoria="<?= htmlspecialchars($plato['categoria']) ?>"
                                                                                data-subcategoria="<?= htmlspecialchars($plato['subcategoria']) ?>"
                                                                                data-tipo-evento="<?= htmlspecialchars($plato['tipo_evento']) ?>">
                                                                            <?= htmlspecialchars($plato['nombre_plato']) ?> - $<?= number_format($plato['precio'], 2) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </optgroup>
                                                            <?php endforeach; 
                                                        endif;
                                                    endforeach;
                                                endforeach; ?>
                                            </select>
                                            
                                            
                                            
                                            <button type="button" class="btn-remove-menu" onclick="removeMenuItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="btn-add-menu" onclick="addMenuItem()">
                                    <i class="fas fa-plus"></i> Agregar Plato
                                </button>
                                
                               
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
    <!-- ... resto del HTML ... -->

<script src="../js/admin/editar_evento.js"></script>
<script>
    // Inicializar el script con los datos necesarios
    document.addEventListener('DOMContentLoaded', function () {
        // Pasar datos desde PHP a JavaScript
        window.platosDisponibles = <?= $platos_disponibles_json ?>;
        window.categoriasPlatos = <?= $categorias_platos_json ?>;
        window.eventoHoraInicio = '<?= $row['hora_inicio'] ?>';
        window.eventoHoraFin = '<?= $row['hora_fin'] ?>';
        window.eventoId = <?= $evento_id ?>;
        
        // Inicializar las funciones
        if (typeof initializeEventoEditor === 'function') {
            initializeEventoEditor();
        }
    });
</script>
</body>
</html>
</body>
</html>
