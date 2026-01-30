<?php
session_start();
include 'php/conexion.php';
include 'idiomas.php';

if (!isset($_GET['token'])) {
    die("Acceso denegado: Enlace inválido.");
}

$token = $_GET['token'];

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'es';
$t = $texts[$lang];

$sql = "SELECT r.*, e.nombre as nombre_evento, c.cliente_nombre, c.cliente_telefono, c.cliente_email
        FROM reservas r 
        JOIN tipos_evento e ON r.id_tipo_evento = e.id 
        JOIN clientes c ON r.id_cliente = c.id
        WHERE r.token = '$token' AND r.estado = 'Pendiente'";

$res = $conn->query($sql);
$reserva = $res->fetch_assoc();

if (!$reserva) {
    die($lang == 'es' ? "Este enlace ha expirado o ya fue confirmado. Gracias." : "This link has expired or has already been confirmed. Thank you.");
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['confirmacion']; ?> - GO Quito Hotel</title>
    <link rel="stylesheet" href="css/confirmacion_cliente.css">
    <style>
        :root {
            --primary: #001f3f;
            --accent: #d4af37;
        }

        .lang-switcher {
            text-align: right;
            padding: 10px;
        }

        .lang-switcher a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            margin-left: 15px;
            border: 1px solid white;
            padding: 5px 10px;
            border-radius: 4px;
        }

        /* Corrección de ancho: auto para que no se estire feo */
        select {
            width: 100%;
            max-width: 300px;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--accent);
        }

        select:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        .campo-flex {
            margin-bottom: 15px;
            flex: 1;
            min-width: 250px;
        }
    </style>
</head>

<body>

    <header style="background: var(--primary); color: white; padding: 20px; text-align: center;">
        <div class="lang-switcher">
            <a href="?token=<?php echo $token; ?>&lang=es">🇪🇸 ES</a>
            <a href="?token=<?php echo $token; ?>&lang=en">🇺🇸 EN</a>
        </div>
        <h1><?php echo $t['confirmacion']; ?>: <?php echo $reserva['nombre_evento']; ?></h1>
    </header>

    <main style="max-width: 1000px; margin: auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <img src="img/logo_goquito.png" alt="Logo GO Quito Hotel" style="max-width: 250px; height: auto;">
        </div>

        <div class="instruccion">
            <p><?php echo $t['estimado']; ?> <strong><?php echo $reserva['cliente_nombre']; ?></strong>,
                <?php echo $t['instruccion']; ?></p>
        </div>

        <form action="php/finalizar_reserva.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <input type="hidden" name="id_reserva" value="<?php echo $reserva['id']; ?>">
            <input type="hidden" name="id_tipo_evento" value="<?php echo $reserva['id_tipo_evento']; ?>">

            <section>
                <h2><?php echo $t['sec1_titulo']; ?></h2>
                <div class="cuadro-facturacion">
                    <div class="seccion-flex">
                        <div class="campo-flex">
                            <label><?php echo $t['razon_social']; ?></label>
                            <input type="text" name="razon_social" required>
                        </div>
                        <div class="campo-flex">
                            <label><?php echo $t['rep_legal']; ?></label>
                            <input type="text" name="representante_legal" required>
                        </div>
                    </div>
                    <div class="seccion-flex">
                        <div class="campo-flex">
                            <label><?php echo $t['id_fiscal']; ?></label>
                            <input type="text" name="identificacion" required>
                        </div>
                        <div class="campo-flex">
                            <label><?php echo $t['dir_fiscal']; ?></label>
                            <input type="text" name="direccion_fiscal" required>
                        </div>
                    </div>
                    <div class="seccion-flex">
                        <div class="campo-flex">
                            <label><?php echo $t['tel_contacto']; ?></label>
                            <input type="tel" name="telefono" value="<?php echo $reserva['cliente_telefono']; ?>"
                                required>
                        </div>
                        <div class="campo-flex">
                            <label><?php echo $t['correo_fact']; ?></label>
                            <input type="email" name="correo_facturacion" required>
                        </div>
                    </div>
                    <div style="border-top: 1px solid #eee; margin-top: 10px; padding-top: 15px;">
                        <p style="font-weight: bold; color: #e67e22; margin-bottom: 10px;">
                            <?php echo $t['encargado_dia']; ?></p>
                        <div class="seccion-flex">
                            <div class="campo-flex">
                                <label><?php echo $t['nombre_encargado']; ?></label>
                                <input type="text" name="contacto_evento_nombre" required>
                            </div>
                            <div class="campo-flex">
                                <label><?php echo $t['cel_encargado']; ?></label>
                                <input type="tel" name="contacto_evento_telefono" required>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h2><?php echo $t['sec2_titulo']; ?></h2>
                <div class="seccion-flex">
                    <div class="campo-flex">
                        <label><?php echo $t['h_inicio']; ?></label>
                        <select name="hora_inicio" id="hora_inicio" required></select>
                    </div>
                    <div class="campo-flex">
                        <label><?php echo $t['h_fin']; ?></label>
                        <select name="hora_fin" id="hora_fin" required disabled>
                            <option value="">
                                <?php echo $lang == 'es' ? 'Seleccione inicio primero' : 'Select start first'; ?>
                            </option>
                        </select>
                    </div>
                </div>
                <div class="campo-flex">
                    <label><?php echo $t['equipos']; ?></label>
                    <input type="text" name="equipos_audiovisuales">
                </div>
            </section>

            <section>
                <h2><?php echo $t['sec3_titulo']; ?></h2>
                <div class="grid-opciones">
                    <?php
                    switch ($reserva['id_tipo_evento']) {
                        case 1:
                            include 'vistas_menu/desayunos.php';
                            break;
                        case 2:
                            include 'vistas_menu/seminario.php';
                            break;
                        case 3:
                            include 'vistas_menu/coctel.php';
                            break;
                        case 5:
                            include 'vistas_menu/almuerzo_cena.php';
                            break;
                        case 6:
                            include 'vistas_menu/coffee_break.php';
                            break;
                        default:
                            echo "<p>Details with advisor.</p>";
                            break;
                    }
                    ?>
                </div>
            </section>

            <section>
                <h2><?php echo $t['sec4_titulo']; ?></h2>
                <div class="mesas-grid">
                    <?php
                    $mesas = $conn->query("SELECT * FROM mesas");
                    while ($m = $mesas->fetch_assoc()): ?>
                        <label>
                            <input type="radio" name="id_mesa" value="<?php echo $m['id']; ?>" style="display:none;"
                                required>
                            <div class="mesa-card">
                                <img src="img/mesas/<?php echo $m['imagen_url']; ?>" alt="Setup">
                                <p><?php echo $m['nombre']; ?></p>
                            </div>
                        </label>
                    <?php endwhile; ?>
                </div>
                <div class="seccion-flex" style="margin-top:20px;">
                    <div class="campo-flex">
                        <label><?php echo $t['manteleria']; ?></label>
                        <select name="manteleria" required>
                            <option value="Blanco">Blanco / White</option>
                            <option value="Negro">Negro / Black</option>
                            <option value="Champagne">Champagne</option>
                        </select>
                    </div>
                    <div class="campo-flex">
                        <label><?php echo $t['servilletas']; ?></label>
                        <select name="color_servilleta" required>
                            <option value="Blanco">Blanco / White</option>
                            <option value="Rojo">Rojo / Red</option>
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <h2><?php echo $t['sec5_titulo']; ?></h2>
                <div class="campo-flex alerta-cocina" style="margin-bottom: 20px; padding: 15px;">
                    <label><?php echo $t['obs_cocina']; ?></label>
                    <textarea name="observaciones" style="height: 80px; width:100%;"></textarea>
                </div>
                <div class="campo-flex logistica-montaje" style="margin-bottom: 20px; padding: 15px;">
                    <label><?php echo $t['logistica']; ?></label>
                    <textarea name="logistica" style="height: 80px; width:100%;"></textarea>
                </div>
                <div class="campo-flex"
                    style="background: #fdfdfd; border: 2px dashed #ccc; padding: 20px; text-align: center;">
                    <label><?php echo $t['planimetria']; ?></label>
                    <input type="file" name="planimetria" accept="image/*">
                </div>
            </section>

            <button type="submit"
                style="width: 100%; margin: 40px 0; padding: 20px; background: var(--primary); color: white; font-weight: bold; font-size: 1.2rem; cursor:pointer; border:none; border-radius:4px;">
                <?php echo $t['btn_enviar']; ?>
            </button>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startSelect = document.getElementById('hora_inicio');
            const endSelect = document.getElementById('hora_fin');
            const isEs = "<?php echo $lang; ?>" === 'es';

            // Mantiene el desplegable con tamaño fijo de 8 para que no ocupe toda la pantalla
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

            for (let i = 0; i < 1440; i += 30) {
                let opt = document.createElement('option');
                opt.value = format24h(i);
                opt.textContent = format12h(i);
                startSelect.appendChild(opt);
            }

            startSelect.addEventListener('change', function () {
                const [h, m] = this.value.split(':').map(Number);
                const startMins = (h * 60) + m;
                endSelect.innerHTML = '';
                endSelect.disabled = false;

                for (let i = 30; i <= 1440; i += 30) {
                    let currentMins = startMins + i;
                    if (currentMins > 1440) break;

                    let opt = document.createElement('option');
                    opt.value = format24h(currentMins);

                    let totalHoras = i / 60;
                    let duracionTexto = "";

                    // Solo muestra texto de horas (1 hora, 2 horas...) si es exacto
                    if (Number.isInteger(totalHoras)) {
                        let unit = isEs ? (totalHoras === 1 ? 'hora' : 'horas') : (totalHoras === 1 ? 'hour' : 'hours');
                        duracionTexto = ` (${totalHoras} ${unit})`;
                    }

                    opt.textContent = `${format12h(currentMins)}${duracionTexto}`;
                    endSelect.appendChild(opt);
                }
            });
        });
    </script>
</body>

</html>