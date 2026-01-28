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

// ===== AGREGAR ESTO =====
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
// ===== FIN DE AGREGAR =====

$filtro_nombre = isset($_GET['busqueda']) ? $conn->real_escape_string($_GET['busqueda']) : '';
// ... resto del código ...

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
    <link rel="stylesheet" href="../css/gestion_menu/modal.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos para la sección de filtros y botones de acción */
        .filters-section {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        /* Botón Filtrar (Sobrio y elegante) */
        .btn-filter {
            background-color: #001f3f;
            /* Azul oscuro corporativo */
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
            height: 42px;
            /* Alineado con los inputs */
        }

        .btn-filter:hover {
            background-color: #003366;
        }

        /* Botón Nueva Reserva (Llamativo y positivo) */
        .btn-new {
            background-color: #27ae60;
            /* Verde éxito */
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            height: 42px;
            border: none;
        }

        .btn-new:hover {
            background-color: #219150;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        /* Ajuste opcional para los labels de los filtros */
        .filter-group label {
            display: block;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 5px;
            font-weight: bold;
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

            <div style="padding: 20px; max-width: 1700px; margin: auto;">
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
                                <th style="width: 80px;">Estado</th>
                                <th style="width: 100px;">Fecha/Hora</th>
                                <th style="width: 220px;">Facturación / Cliente</th>
                                <th style="width: 120px;">Evento/Pax</th>
                                <th style="width: 140px;">Montaje</th>
                                <th>Detalles Críticos</th>
                                <th style="width: 70px;">Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $res->fetch_assoc()): ?>
                                <tr onclick='verDetalle(<?= json_encode($row); ?>)'>
                                    <td>
                                        <span
                                            class="status-badge <?= strtolower($row['estado']) ?>"><?= $row['estado'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= date("d/m/Y", strtotime($row['fecha_evento'])) ?></strong><br>
                                        <small>🕒
                                            <?= $row['hora_inicio'] ? date("H:i", strtotime($row['hora_inicio'])) : '--:--' ?>
                                            a
                                            <?= $row['hora_fin'] ? date("H:i", strtotime($row['hora_fin'])) : '--:--' ?></small>
                                    </td>
                                    <td>
                                        <div style="font-weight: bold; color: var(--legal);">
                                            <?= $row['razon_social'] ?: $row['cliente_nombre'] ?>
                                        </div>
                                        <small style="color: #666;">ID: <?= $row['identificacion'] ?></small>
                                    </td>
                                    <td>
                                        <strong><?= $row['evento'] ?></strong><br>👥 <?= $row['cantidad_personas'] ?> Pax
                                    </td>
                                    <td>
                                        <?= $row['mesa_nombre'] ?: 'N/A' ?><br>
                                        <small>👕 M: <?= $row['manteleria'] ?: 'N/A' ?></small><br>
                                        <small>🧻 S: <?= $row['color_servilleta'] ?: 'N/A' ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row['estado'] == 'Confirmada'): ?>
                                            <div class="badge-legal">⚖️ Contrato: <?= $row['firma_nombre'] ?: 'Firmado' ?></div>
                                            <?php if ($row['observaciones']): ?>
                                                <div class="badge-cocina">⚠️ Cocina: <?= substr($row['observaciones'], 0, 45) ?>...
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($row['equipos_audiovisuales']): ?>
                                                <div class="badge-it">🎤 IT: <?= $row['equipos_audiovisuales'] ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <em style="color: #999;">Esperando confirmación...</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-copy"
                                            onclick="copyToClipboard(event, '<?= $row['token'] ?>')"
                                            style="position: relative; z-index: 10; cursor: pointer;">
                                            🔗
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL DE VISUALIZACIÓN (igual al primer código) -->
            <div id="modalEvento" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 style="margin:0; font-size: 1.1rem;">Expediente del Evento</h2>
                        <button type="button" class="btn-pdf" onclick="exportarReservaPDF()"
                            style="background: #c0392b; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            📄 Exportar PDF
                        </button>

                        <div id="edit-button-container" style="display: none;">
                            <button type="button" class="btn-edit" onclick="solicitarEdicion()">
                                ✏️ Editar Expediente
                            </button>
                        </div>
                        <div id="no-permiso-message" class="no-permiso" style="display: none;">
                            ⚠️ Solo el administrador principal puede editar expedientes
                        </div>
                        <button type="button" class="btn-cancel" onclick="cerrarModal()">Cerrar</button>
                    </div>
                    <div class="modal-body">
                        <div class="section-title">📂 Información Legal y Facturación</div>
                        <div class="legal-box">
                            <div class="field"><label>Razón Social</label>
                                <p id="m-razon" class="highlight-p"></p>
                            </div>
                            <div class="field"><label>RUC / Cédula</label>
                                <p id="m-ruc"></p>
                            </div>
                            <div class="field"><label>Representante Legal</label>
                                <p id="m-representante"></p>
                            </div>
                            <div class="field"><label>Dirección Fiscal</label>
                                <p id="m-direccion"></p>
                            </div>
                            <div class="field"><label>Correo Facturación</label>
                                <p id="m-correo-f"></p>
                            </div>
                            <div class="field"><label>Firmado por</label>
                                <p id="m-firma" style="color: var(--legal); font-weight: bold;"></p>
                            </div>

                        </div>

                        <div class="section-title">📞 Contactos Directos</div>
                        <div class="field"><label>Contratante</label>
                            <p id="m-cliente"></p>
                        </div>
                        <div class="field"><label>Encargado Día Evento</label>
                            <p id="m-encargado-nom" class="highlight-p"></p>
                        </div>
                        <div class="field"><label>Celular Encargado</label>
                            <p id="m-encargado-tel" class="highlight-p"></p>
                        </div>

                        <div class="section-title">🍽️ Detalles de Alimentos y Bebidas</div>
                        <div class="field full"
                            style="background: #e8f5e9; padding: 15px; border-radius: 5px; border: 1px solid #c8e6c9;">
                            <label>Menú Seleccionado</label>
                            <div id="m-menu-final"></div>
                        </div>
                        <div class="field full" style="background: #fff3e0; padding: 10px; border-radius: 5px;">
                            <label>Restricciones Alimenticias (Cocina)</label>
                            <p id="m-observaciones"></p>
                        </div>

                        <div class="section-title">⚙️ Operativa y Montaje</div>
                        <div class="field"><label>Horario</label>
                            <p id="m-horario" class="highlight-p"></p>
                        </div>
                        <div class="field"><label>Fecha del Evento</label>
                            <p id="m-fecha-evento" style="color: #001f3f; font-weight: bold;"></p>
                        </div>
                        <div class="field"><label>Evento / Pax</label>
                            <p id="m-evento-pax"></p>
                        </div>
                        <div class="field"><label>Mesa / Mantelería</label>
                            <p id="m-montaje"></p>
                        </div>
                        <div class="field"><label>Color Servilleta</label>
                            <p id="m-servilleta" style="font-weight: bold; color: #555;"></p>
                        </div>
                        <div class="field full"><label>IT & Logística</label>
                            <p id="m-it-log"></p>
                        </div>

                        <div id="m-plan-container" class="field full"
                            style="text-align: center; background: #f8f9fa; padding: 15px; border: 1px dashed #ccc;">
                            <a id="m-plan-link" href="#" target="_blank"
                                style="color: var(--info); font-weight: bold; text-decoration: none;">🖼️ VER
                                PLANIMETRÍA</a>
                        </div>


                    </div>
                </div>
            </div>

            <!-- MODAL DE EDICIÓN (nuevo modal separado) -->
            <div id="modalEdicion" class="modal">
                <div class="modal-content" style="width: 90%; max-width: 1200px;">
                    <form id="formEdicionTotal">
                        <input type="hidden" name="id_tipo_evento" id="e-id-tipo-evento">
                        <input type="hidden" name="id_reserva" id="e-id">
                        <input type="hidden" name="id_cliente" id="e-id-cliente">

                        <div class="modal-header">
                            <h2 style="margin:0; font-size: 1.1rem;">
                                Editar Expediente <span id="e-id-display"></span>
                            </h2>
                            <span style="cursor:pointer; font-size: 24px;" onclick="cerrarModalEdicion()">&times;</span>
                        </div>

                        <div class="modal-body">
                            <!-- Layout principal de dos columnas -->
                            <div class="form-grid-container">
                                <!-- Columna izquierda -->
                                <div class="form-column">
                                    <!-- Sección Legal -->
                                    <div class="form-section legal">
                                        <div class="section-title">📂 Información Legal y Facturación</div>
                                        <div class="fields-grid">
                                            <div class="field">
                                                <label>Razón Social</label>
                                                <input type="text" name="razon_social" id="e-razon" class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>RUC / Cédula</label>
                                                <input type="text" name="identificacion" id="e-ruc" class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Representante Legal</label>
                                                <input type="text" name="representante_legal" id="e-representante"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Dirección Fiscal</label>
                                                <input type="text" name="direccion_fiscal" id="e-direccion"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Correo Facturación</label>
                                                <input type="email" name="correo_facturacion" id="e-correo-f"
                                                    class="input-edit">
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Sección del Evento -->
                                    <div class="form-section event">
                                        <div class="section-title">📅 Datos del Evento</div>
                                        <div class="fields-grid">
                                            <div class="field">
                                                <label>Fecha Evento</label>
                                                <input type="date" name="fecha_evento" id="e-fecha" class="input-edit">
                                            </div>
                                            <div class="field full-width">
                                                <label>Horario</label>
                                                <div class="time-group">
                                                    <input type="time" name="hora_inicio" id="e-inicio"
                                                        class="input-edit" placeholder="Inicio">
                                                    <input type="time" name="hora_fin" id="e-fin" class="input-edit"
                                                        placeholder="Fin">
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label>Cantidad de Personas</label>
                                                <input type="number" name="cantidad_personas" id="e-pax"
                                                    class="input-edit" min="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna derecha -->
                                <div class="form-column">
                                    <!-- Sección de Contactos -->
                                    <div class="form-section contact">
                                        <div class="section-title">📞 Contactos Directos</div>
                                        <div class="fields-grid">
                                            <div class="field">
                                                <label>Nombre Contratante</label>
                                                <input type="text" name="cliente_nombre" id="e-cliente-nombre"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Teléfono Contratante</label>
                                                <input type="text" name="cliente_telefono" id="e-cliente-telefono"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Email Contratante</label>
                                                <input type="email" name="cliente_email" id="e-cliente-email"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Encargado Evento</label>
                                                <input type="text" name="contacto_evento_nombre" id="e-encargado-nombre"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Teléfono Encargado</label>
                                                <input type="text" name="contacto_evento_telefono"
                                                    id="e-encargado-telefono" class="input-edit">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección de Operativa -->
                                    <div class="form-section operation">
                                        <div class="section-title">⚙️ Operativa y Montaje</div>
                                        <div class="fields-grid">
                                            <!-- Busca estos campos en el modal de edición y reemplázalos: -->

                                            <!-- Campo de Mesa -->
                                            <div class="field">
                                                <label>Mesa</label>
                                                <select name="id_mesa" id="e-mesa" class="input-edit">
                                                    <option value="">-- Seleccione Mesa --</option>
                                                    <!-- Las opciones se cargarán con JavaScript -->
                                                </select>
                                            </div>

                                            <!-- Campo de Mantelería -->
                                            <div class="field">
                                                <label>Mantelería</label>
                                                <select name="manteleria" id="e-manteleria" class="input-edit">
                                                    <option value="">-- Seleccione Mantelería --</option>
                                                    <option value="Blanco">Blanco</option>
                                                    <option value="Negro">Negro</option>
                                                    <option value="Beige">Beige</option>
                                                    <option value="Rojo">Rojo</option>
                                                    <option value="Azul">Azul</option>
                                                    <option value="Verde">Verde</option>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label>Color Servilleta</label>
                                                <select name="color_servilleta" id="e-servilleta" class="input-edit">
                                                    <option value="">-- Seleccione Color --</option>
                                                    <option value="Blanco">Blanco</option>
                                                    <option value="Negro">Negro</option>
                                                    <option value="Beige">Beige</option>
                                                    <option value="Marfil">Marfil</option>
                                                    <option value="Rojo">Rojo</option>
                                                    <option value="Azul">Azul</option>
                                                    <option value="Verde">Verde</option>
                                                    <option value="Amarillo">Amarillo</option>
                                                    <option value="Naranja">Naranja</option>
                                                    <option value="Rosa">Rosa</option>
                                                    <option value="Morado">Morado</option>
                                                    <option value="Gris">Gris</option>
                                                    <option value="Personalizado">Personalizado</option>
                                                    <option value="N/A">No aplica</option>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label>Equipos Audiovisuales</label>
                                                <input type="text" name="equipos_audiovisuales" id="e-it"
                                                    class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Logística Especial</label>
                                                <input type="text" name="logistica" id="e-logistica" class="input-edit">
                                            </div>
                                            <div class="field">
                                                <label>Estado Reserva</label>
                                                <select name="estado" id="e-estado" class="input-edit">
                                                    <option value="Pendiente">Pendiente</option>
                                                    <option value="Confirmada">Confirmada</option>
                                                    <option value="Cancelada">Cancelada</option>
                                                    <option value="Completada">Completada</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Menú (ancho completo) -->
                            <div class="menu-section">
                                <div class="section-title" style="color: #d35400;">🍽️ Menú del Evento</div>

                                <div class="menu-controls">
                                    <div class="field">
                                        <label>Categoría</label>
                                        <select id="selector-categoria" class="input-edit"
                                            onchange="cargarSubcategorias()">
                                            <option value="">-- Seleccione Categoría --</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Subcategoría</label>
                                        <select id="selector-subcategoria" class="input-edit"
                                            onchange="cargarPlatosCheckboxes()">
                                            <option value="">-- Ver Todo --</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Platos Disponibles</label>
                                    <div id="contenedor-checkboxes">
                                        <em>Seleccione una categoría...</em>
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Resumen de platos seleccionados:</label>
                                    <div id="resumen-platos-actuales">
                                        Ningún plato seleccionado.
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Observaciones (ancho completo) -->
                            <div class="notes-section">
                                <div class="section-title">📝 Observaciones y Notas</div>
                                <div class="fields-grid">
                                    <div class="field">
                                        <label>Observaciones (Cocina)</label>
                                        <textarea name="observaciones" id="e-observaciones" class="input-edit" rows="3"
                                            placeholder="Restricciones alimenticias, alergias, etc."></textarea>
                                    </div>
                                    <div class="field">
                                        <label>Notas Internas</label>
                                        <textarea name="notas_internas" id="e-notas-internas" class="input-edit"
                                            rows="2" placeholder="Notas para el equipo interno"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-cancel" onclick="cerrarModalEdicion()">Cancelar</button>
                            <button type="submit" class="btn-save">💾 GUARDAR CAMBIOS</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/admin/dashboard.js"></script>
    <script>
        // Pasar variables de permisos desde PHP
        window.esAdminPrincipal = <?= $es_admin_principal ? 'true' : 'false' ?>;
        window.adminNombre = "<?= addslashes($admin_info['nombre_completo'] ?? '') ?>";
    </script>
</body>

</html>