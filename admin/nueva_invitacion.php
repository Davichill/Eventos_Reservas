<?php
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// 1. Obtener lista de clientes para el selector
$clientes_query = $conn->query("SELECT id, cliente_nombre, cliente_apellido, razon_social, identificacion FROM clientes ORDER BY cliente_nombre ASC");

// 2. Obtener lista de salones y organizarlos por jerarquía
$salones_query = $conn->query("SELECT * FROM salones ORDER BY nombre_salon ASC");
$salones_padres = [];
$salones_hijos = [];

while ($s = $salones_query->fetch_assoc()) {
    if ($s['subdivision_de'] == NULL) {
        $salones_padres[] = $s;
    } else {
        $salones_hijos[$s['subdivision_de']][] = $s;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Generar Invitación | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .header-step {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--azul-quito);
            margin-bottom: 20px;
            border-bottom: 2px solid #f4f4f4;
            padding-bottom: 10px;
        }

        /* Estilos para el Link Generado */
        .success-box {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .input-copy-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .input-copy {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            font-size: 0.9rem;
        }

        .btn-copy {
            background: var(--azul-quito);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        /* Ajuste Select2 */
        .select2-container--default .select2-selection--single {
            height: 45px;
            border: 1px solid #ddd;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .grid-campos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .campo-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo-group label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .campo-group input,
        .campo-group select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        /* Nuevos estilos para selects de hora con tamaño controlado */
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
            border-color: #3498db;
        }

        .select-hora select:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        .btn-regresar {
            text-decoration: none;
            color: var(--azul-quito);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .btn-generar {
            background: var(--azul-quito);
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-bar" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="dashboard.php" class="btn-regresar">
                <i class="fas fa-arrow-left"></i> Regresar al Dashboard
            </a>
            <span class="user-name">Módulo de Reservas Rápidas</span>
        </header>

        <div class="content-body">

            <?php if (isset($_GET['exito']) && isset($_GET['token'])):
                // Cambia esta URL por la de tu servidor real cuando lo subas
                $url_cliente = "http://localhost/eventos-reservas/confirmar.php?token=" . $_GET['token'];
                ?>
                <div class="success-box">
                    <h3 style="color: #2e7d32;"><i class="fas fa-check-circle"></i> ¡Enlace Generado Exitosamente!</h3>
                    <p>Copia el siguiente link y envíalo al cliente por WhatsApp o Email:</p>
                    <div class="input-copy-group">
                        <input type="text" value="<?= $url_cliente ?>" id="linkReserva" readonly class="input-copy">
                        <button onclick="copyLink()" class="btn-copy">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
                <script>
                    function copyLink() {
                        var copyText = document.getElementById("linkReserva");
                        copyText.select();
                        copyText.setSelectionRange(0, 99999); // Para móviles
                        document.execCommand("copy");
                        alert("¡Enlace copiado! Ya puedes pegarlo en WhatsApp.");
                    }
                </script>
            <?php endif; ?>
            <div class="form-container">
                <form action="crear_token.php" method="POST">
                    <div class="header-step">
                        <i class="fas fa-search"></i>
                        <h2>Paso 1: Buscar y Seleccionar Cliente</h2>
                    </div>

                    <div class="campo-group" style="margin-bottom: 30px; width: 100%;">
                        <label>Escribe el nombre o RUC del cliente:</label>
                        <select name="id_cliente" id="buscador_cliente" style="width: 100%;" required>
                            <option value="">Escribe para buscar...</option>
                            <?php while ($c = $clientes_query->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['cliente_nombre'] . " " . $c['cliente_apellido'] . " | " . $c['identificacion'] . " (" . ($c['razon_social'] ?: 'Particular') . ")") ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="header-step">
                        <i class="fas fa-calendar-day"></i>
                        <h2>Paso 2: Detalles del Evento</h2>
                    </div>

                    <div class="grid-campos">
                        <div class="campo-group" style="grid-column: span 2;">
                            <label>Nombre Personalizado del Evento</label>
                            <input type="text" name="nombre_evento"
                                placeholder="Ej: Boda Familia Pérez o Congreso de Cardiología" required>
                        </div>
                        <div class="campo-group">
                            <label>Tipo de Evento</label>
                            <select name="id_tipo_evento" required>
                                <option value="">Seleccione...</option>
                                <?php
                                $tipos = $conn->query("SELECT * FROM tipos_evento ORDER BY nombre");
                                while ($t = $tipos->fetch_assoc()) {
                                    echo "<option value='{$t['id']}'>" . htmlspecialchars($t['nombre']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="campo-group">
                            <label>Asignar Salón / Espacio</label>
                            <select name="id_salon" required>
                                <option value="">Seleccione un salón...</option>
                                <?php foreach ($salones_padres as $padre): ?>
                                    <option value="<?= $padre['id'] ?>" style="font-weight: bold;">
                                        <?= htmlspecialchars($padre['nombre_salon']) ?>
                                        <?= $padre['capacidad'] ? '(Cap: ' . $padre['capacidad'] . ')' : '' ?>
                                    </option>

                                    <?php if (isset($salones_hijos[$padre['id']])): ?>
                                        <?php foreach ($salones_hijos[$padre['id']] as $hijo): ?>
                                            <option value="<?= $hijo['id'] ?>">
                                                &nbsp;&nbsp;&nbsp;↳ <?= htmlspecialchars($hijo['nombre_salon']) ?>
                                                <?= $hijo['capacidad'] ? '(Cap: ' . $hijo['capacidad'] . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo-group">
                            <label>Fecha del Evento</label>
                            <input type="date" name="fecha_evento" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- NUEVOS SELECTORES DE HORA - IGUAL AL DE CONFIRMACIÓN -->
                        <div class="campo-group">
                            <label>Hora de Inicio</label>
                            <div class="select-hora">
                                <select name="hora_inicio" id="hora_inicio" required>
                                    <option value="">Seleccione hora...</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo-group">
                            <label>Hora de Finalización</label>
                            <div class="select-hora">
                                <select name="hora_fin" id="hora_fin" required disabled>
                                    <option value="">Seleccione inicio primero</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo-group">
                            <label>Cantidad de Personas (Pax)</label>
                            <input type="number" name="cantidad_personas" min="1" max="1000" value="50" required>
                        </div>
                        <div class="campo-group" style="grid-column: span 2;">
                            <label>Nota Interna</label>
                            <input type="text" name="nota" placeholder="Ej: Confirmar montaje tipo escuela">
                        </div>
                    </div>

                    <button type="submit" class="btn-generar">
                        <i class="fas fa-magic"></i> Generar Invitación
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#buscador_cliente').select2({
                placeholder: "Busca por nombre, RUC o empresa...",
                allowClear: true,
                language: {
                    noResults: function () { return "No se encontró al cliente."; }
                }
            });

            // =====================
            // LÓGICA DE HORARIOS (EXACTA A CONFIRMACIÓN)
            // =====================
            const startSelect = document.getElementById('hora_inicio');
            const endSelect = document.getElementById('hora_fin');

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

            // Formatear a 12 horas para mostrar (igual)
            function format12h(minutes) {
                let h = Math.floor(minutes / 60) % 24;
                let m = minutes % 60;
                let ampm = h >= 12 ? 'PM' : 'AM';
                let displayH = h % 12 || 12;
                let displayM = m === 0 ? '00' : m;
                return `${displayH}:${displayM} ${ampm}`;
            }

            // Formatear a 24 horas para valor del input (igual)
            function format24h(minutes) {
                let h = Math.floor(minutes / 60) % 24;
                let m = minutes % 60;
                return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
            }

            // Llenar selector de hora inicio (cada 30 minutos) - IGUAL
            for (let i = 0; i < 1440; i += 30) {
                let opt = document.createElement('option');
                opt.value = format24h(i);
                opt.textContent = format12h(i);
                startSelect.appendChild(opt);
            }

            // Cuando cambie la hora de inicio - IGUAL
            startSelect.addEventListener('change', function () {
                const [h, m] = this.value.split(':').map(Number);
                const startMins = (h * 60) + m;
                endSelect.innerHTML = '';
                endSelect.disabled = false;

                // Primera opción
                let defaultOpt = document.createElement('option');
                defaultOpt.value = "";
                defaultOpt.textContent = "Seleccione duración...";
                endSelect.appendChild(defaultOpt);

                // IGUAL: Opciones cada 30 minutos, con texto de horas solo si son exactas
                for (let i = 30; i <= 1440; i += 30) {
                    let currentMins = startMins + i;
                    if (currentMins > 1440) break;

                    let opt = document.createElement('option');
                    opt.value = format24h(currentMins);

                    let totalHoras = i / 60;
                    let duracionTexto = "";

                    // Solo muestra texto de horas (1 hora, 2 horas...) si es exacto - IGUAL
                    if (Number.isInteger(totalHoras)) {
                        let unit = totalHoras === 1 ? 'hora' : 'horas';
                        duracionTexto = ` (${totalHoras} ${unit})`;
                    }

                    opt.textContent = `${format12h(currentMins)}${duracionTexto}`;
                    endSelect.appendChild(opt);
                }
            });

            // Validación antes de enviar el formulario - IGUAL (pero con nombres de variables diferentes)
            $('form').on('submit', function (e) {
                const hora_inicio = document.getElementById('hora_inicio');
                const hora_fin = document.getElementById('hora_fin');

                if (!hora_inicio.value || !hora_fin.value) {
                    alert("Por favor, seleccione tanto la hora de inicio como la de finalización.");
                    e.preventDefault();
                    return;
                }

                const [h1, m1] = hora_inicio.value.split(':').map(Number);
                const [h2, m2] = hora_fin.value.split(':').map(Number);

                const inicioMins = (h1 * 60) + m1;
                const finMins = (h2 * 60) + m2;

                if (finMins <= inicioMins) {
                    alert("La hora de finalización debe ser mayor a la hora de inicio.");
                    e.preventDefault();
                    return;
                }

                // Validar duración mínima (30 minutos) - IGUAL
                if ((finMins - inicioMins) < 30) {
                    alert("La duración mínima del evento debe ser de 30 minutos.");
                    e.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>

</html>