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

// 1. PROCESAR ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar'])) {
    $nombre_evento = $conn->real_escape_string($_POST['nombre_evento']);
    $id_salon = intval($_POST['id_salon']);
    $fecha = $_POST['fecha_evento'];
    $h_inicio = $_POST['hora_inicio'];
    $h_fin = $_POST['hora_fin'];
    $pax = intval($_POST['cantidad_personas']);
    $estado = $_POST['estado'];
    $observaciones = $conn->real_escape_string($_POST['observaciones']);
    $notas = $conn->real_escape_string($_POST['notas']);
    $equipos = $conn->real_escape_string($_POST['equipos_audiovisuales']);

    $sql_update = "UPDATE reservas SET 
                    nombre_evento = '$nombre_evento',
                    id_salon = '$id_salon',
                    fecha_evento = '$fecha',
                    hora_inicio = '$h_inicio',
                    hora_fin = '$h_fin',
                    cantidad_personas = '$pax',
                    estado = '$estado',
                    observaciones = '$observaciones',
                    notas = '$notas',
                    equipos_audiovisuales = '$equipos'
                   WHERE id = $evento_id";

    if ($conn->query($sql_update)) {
        header("Location: detalle_evento.php?id=$evento_id&msg=updated");
        exit();
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}

// 2. OBTENER DATOS ACTUALES
$sql = "SELECT r.*, c.cliente_nombre FROM reservas r 
        JOIN clientes c ON r.id_cliente = c.id 
        WHERE r.id = $evento_id";
$res = $conn->query($sql);
$row = $res->fetch_assoc();

// Obtener salones para el select
$salones = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon ASC");
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
        }

        body {
            background: var(--bg);
            font-family: 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .edit-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            margin: auto;
            overflow: hidden;
        }

        .edit-header {
            background: var(--primary);
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
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full {
            grid-column: span 2;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: 0.3s;
        }

        /* Estilos específicos para selects de hora */
        .select-hora {
            position: relative;
            width: 100%;
        }

        .select-hora select {
            width: 100%;
            max-width: 300px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
        }

        .select-hora select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .select-hora select:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 8px rgba(166, 137, 69, 0.2);
        }

        .section-divider {
            grid-column: span 2;
            border-bottom: 2px solid #f0f0f0;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            color: var(--accent);
            font-weight: bold;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-save:hover {
            background: #003366;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #eee;
            color: #666;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .time-value-display {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>

<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="edit-card">
            <div class="edit-header">
                <div>
                    <h2 style="margin:0;"><i class="fas fa-edit"></i> Editar Expediente #<?= $evento_id ?></h2>
                    <small>Cliente: <?= htmlspecialchars($row['cliente_nombre']) ?></small>
                </div>
                <a href="detalle_evento.php?id=<?= $evento_id ?>" style="color:white;"><i class="fas fa-times"></i></a>
            </div>

            <form action="" method="POST" class="form-container" onsubmit="return validarHorario()">
                <div class="form-grid">

                    <div class="section-divider">INFORMACIÓN GENERAL</div>

                    <div class="form-group full">
                        <label>Nombre Personalizado del Evento</label>
                        <input type="text" name="nombre_evento" value="<?= htmlspecialchars($row['nombre_evento']) ?>"
                            placeholder="Ej: Boda Familia Perez">
                    </div>

                    <div class="form-group">
                        <label>Salón</label>
                        <select name="id_salon">
                            <?php
                            // Resetear el puntero del resultado
                            $salones->data_seek(0);
                            while ($s = $salones->fetch_assoc()): ?>
                                <option value="<?= $s['id'] ?>" <?= ($s['id'] == $row['id_salon']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nombre_salon']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estado de Reserva</label>
                        <select name="estado" style="border-left: 5px solid var(--accent);">
                            <option value="Pendiente" <?= ($row['estado'] == 'Pendiente') ? 'selected' : '' ?>>Pendiente
                            </option>
                            <option value="Confirmada" <?= ($row['estado'] == 'Confirmada') ? 'selected' : '' ?>>Confirmada
                            </option>
                            <option value="Cancelada" <?= ($row['estado'] == 'Cancelada') ? 'selected' : '' ?>>Cancelada
                            </option>
                        </select>
                    </div>

                    <div class="section-divider">LOGÍSTICA Y HORARIO</div>

                    <div class="form-group">
                        <label>Fecha del Evento</label>
                        <input type="date" name="fecha_evento" value="<?= $row['fecha_evento'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Pax (Personas)</label>
                        <input type="number" name="cantidad_personas" value="<?= $row['cantidad_personas'] ?>" required>
                    </div>

                    <!-- NUEVO: SELECTORES DE HORA CON LA MISMA LÓGICA -->
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

                    <div class="form-group full">
                        <label>Equipos Audiovisuales / Requerimientos</label>
                        <input type="text" name="equipos_audiovisuales"
                            value="<?= htmlspecialchars($row['equipos_audiovisuales']) ?>">
                    </div>

                    <div class="section-divider">NOTAS Y OBSERVACIONES</div>

                    <div class="form-group">
                        <label>Observaciones de Cocina (Menú)</label>
                        <textarea name="observaciones"
                            rows="4"><?= htmlspecialchars($row['observaciones']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Notas Internas / Logística</label>
                        <textarea name="notas" rows="4"><?= htmlspecialchars($row['notas']) ?></textarea>
                    </div>

                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                    <a href="detalle_evento.php?id=<?= $evento_id ?>" class="btn-cancel">Descartar</a>
                    <button type="submit" name="actualizar" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startSelect = document.getElementById('hora_inicio');
            const endSelect = document.getElementById('hora_fin');

            // Hora actual del evento
            const horaActualInicio = "<?= $row['hora_inicio'] ?>";
            const horaActualFin = "<?= $row['hora_fin'] ?>";

            // Función para hacer select compacto (igual a confirmación)
            const makeCompact = (el) => {
                el.addEventListener('mousedown', function () {
                    if (this.options.length > 8) this.size = 8;
                });
                const reset = function () { this.size = 0; };
                el.addEventListener('change', reset);
                el.addEventListener('blur', reset);
            };

            makeCompact(startSelect);
            makeCompact(endSelect);

            function format12h(minutes) {
                let h = Math.floor(minutes / 60) % 24;
                let m = minutes % 60;
                let ampm = h >= 12 ? 'PM' : 'AM';
                let displayH = h % 12 || 12;
                let displayM = m === 0 ? '00' : m;
                return `${displayH}:${displayM} ${ampm}`;
            }

            function format24h(minutes) {
                let h = Math.floor(minutes / 60) % 24;
                let m = minutes % 60;
                return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
            }

            // Convertir hora actual a minutos para comparación
            function horaAMinutos(hora24) {
                const [h, m, s] = hora24.split(':').map(Number);
                return (h * 60) + m;
            }

            // Llenar selector de hora inicio (cada 30 minutos)
            for (let i = 0; i < 1440; i += 30) {
                let opt = document.createElement('option');
                const valor24h = format24h(i);
                opt.value = valor24h;
                opt.textContent = format12h(i);

                // Seleccionar la hora actual por defecto
                if (horaActualInicio === valor24h) {
                    opt.selected = true;
                }

                startSelect.appendChild(opt);
            }

            // Función para cargar opciones de fin
            function cargarOpcionesFin(horaInicio) {
                const [h, m] = horaInicio.split(':').map(Number);
                const startMins = (h * 60) + m;
                endSelect.innerHTML = '';
                endSelect.disabled = false;

                // Primera opción
                let defaultOpt = document.createElement('option');
                defaultOpt.value = "";
                defaultOpt.textContent = "Seleccione duración...";
                endSelect.appendChild(defaultOpt);

                // Opciones cada 30 minutos
                for (let i = 30; i <= 1440; i += 30) {
                    let currentMins = startMins + i;
                    if (currentMins > 1440) break;

                    let opt = document.createElement('option');
                    const valor24h = format24h(currentMins);
                    opt.value = valor24h;

                    let totalHoras = i / 60;
                    let duracionTexto = "";

                    // Solo muestra texto de horas si es exacto
                    if (Number.isInteger(totalHoras)) {
                        let unit = totalHoras === 1 ? 'hora' : 'horas';
                        duracionTexto = ` (${totalHoras} ${unit})`;
                    }

                    opt.textContent = `${format12h(currentMins)}${duracionTexto}`;

                    // Seleccionar la hora actual por defecto
                    if (horaActualFin === valor24h) {
                        opt.selected = true;
                    }

                    endSelect.appendChild(opt);
                }
            }

            // Cargar opciones de fin basadas en la hora actual
            if (horaActualInicio) {
                cargarOpcionesFin(horaActualInicio);
            }

            // Evento cuando cambia la hora de inicio
            startSelect.addEventListener('change', function () {
                if (this.value) {
                    cargarOpcionesFin(this.value);
                } else {
                    endSelect.innerHTML = '<option value="">Seleccione inicio primero</option>';
                    endSelect.disabled = true;
                }
            });

            // Función de validación antes de enviar
            window.validarHorario = function () {
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

                // Validar duración mínima (30 minutos)
                if ((finMins - inicioMins) < 30) {
                    alert("La duración mínima del evento debe ser de 30 minutos.");
                    return false;
                }

                return true;
            };
        });
    </script>
</body>

</html>