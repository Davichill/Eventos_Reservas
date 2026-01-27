<?php
include_once __DIR__ . '/../php/conexion.php';

// 1. FUNCIÓN MEJORADA CON IMÁGENES - RUTAS CORREGIDAS PARA COINCIDIR CON CÓCTEL
if (!function_exists('pintarSeccionSubdividida')) {
    function pintarSeccionSubdividida($tiempo_db, $conn, $lang = 'es')
    {
        // Ruta base CORREGIDA para coincidir con cóctel
        $rutaBase = "img/menu_almuerzo/"; // Sin ../

        // Buscamos qué subcategorías existen para este Tiempo
        $sqlSub = "SELECT DISTINCT subcategoria FROM menu_almuerzo_cena 
                   WHERE tiempo = '$tiempo_db' AND estado = 1 ORDER BY subcategoria ASC";
        $resSub = $conn->query($sqlSub);

        if ($resSub && $resSub->num_rows > 0) {
            while ($sub = $resSub->fetch_assoc()) {
                $nombreSub = $sub['subcategoria'];
                $nombreSubTraducido = isset($GLOBALS['t'][$nombreSub]) ? $GLOBALS['t'][$nombreSub] : $nombreSub;

                // Imprimimos el título de la subdivisión
                echo "<p class='sub-titulo-pdf'>$nombreSubTraducido</p>";
                echo "<div class='grid-platos'>";

                // Traemos los platos de esa subcategoría
                $sqlPlatos = "SELECT * FROM menu_almuerzo_cena 
                             WHERE tiempo = '$tiempo_db' AND subcategoria = '$nombreSub' AND estado = 1";
                $resPlatos = $conn->query($sqlPlatos);

                while ($item = $resPlatos->fetch_assoc()) {
                    $nombre = $item['nombre'];
                    $nombreTraducido = isset($GLOBALS['t'][$nombre]) ? $GLOBALS['t'][$nombre] : $nombre;
                    // RUTA CORREGIDA: Sin ../
                    $img = $item['imagen_url'] ? $rutaBase . $item['imagen_url'] : 'img/no-image.png';
                    echo "<label class='item-cena' onmouseover=\"actualizarPrevisualizacion('$img', '$nombreTraducido')\">
                            <input type='checkbox' name='bocaditos[]' value='$nombre' data-group='$tiempo_db'> 
                            <span>$nombreTraducido</span>
                          </label>";
                }
                echo "</div>"; // Cierra grid-platos
            }
        } else {
            echo "<small style='color:#999;'>" . ($lang === 'en' ? 'No options configured.' : 'No hay opciones configuradas.') . "</small>";
        }
    }
}

// Obtener idioma
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';
$t = $texts[$lang];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <link rel="stylesheet" href="css/menu/almuerzo_cena.css">
    <style>
        /* Contenedor principal para mantener todo en su sitio */
        .menu-layout-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            /* Columna fija para la vista previa */
            gap: 25px;
            align-items: start;
        }

        /* Grilla de platos: Ahora permite más columnas */
        .grid-platos {
            display: grid;
            /* Bajamos de 400px a 180px para que entren varios de izquierda a derecha */
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            width: 100%;
        }

        /* Evitar que los platos largos rompan el diseño */
        .item-cena {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            min-height: 45px;
            /* Altura uniforme */
        }

        /* LA CLAVE: Columna de vista previa pegajosa */
        .columna-previa {
            position: sticky;
            top: 20px;
            /* Se queda fija al hacer scroll */
            max-width: 350px;
        }

        .preview-card {
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            background: white;
        }

        /* Ajuste para móviles */
        @media (max-width: 900px) {
            .menu-layout-container {
                grid-template-columns: 1fr;
            }

            .columna-previa {
                position: static;
                margin: 0 auto;
            }
        }
    </style>
</head>

<!-- LAYOUT CON DOS COLUMNAS -->
<div class="menu-layout-container">
    <!-- COLUMNA IZQUIERDA: MENÚ DE SELECCIÓN -->
    <div class="columna-seleccion">
        <div class="instruccion"
            style="background: #fff8e1; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <strong><?php echo $t['menu_almuerzo_title']; ?></strong> <?php echo $t['menu_almuerzo_desc']; ?>
        </div>

        <div class="planes-container" style="display: flex; gap: 15px; margin-bottom: 20px;">
            <label class="plan-btn">
                <input type="radio" name="menu_opcion" value="Menú 2 Tiempos" onclick="activarMenu(2)" required>
                <span><?php echo $t['menu_2_tiempos']; ?><br><small>(<?php echo $t['menu_2_sub']; ?>)</small></span>
            </label>
            <label class="plan-btn">
                <input type="radio" name="menu_opcion" value="Menú 3 Tiempos" onclick="activarMenu(3)">
                <span><?php echo $t['menu_3_tiempos']; ?><br><small>(<?php echo $t['menu_3_sub']; ?>)</small></span>
            </label>
            <label class="plan-btn">
                <input type="radio" name="menu_opcion" value="Menú 4 Tiempos" onclick="activarMenu(4)">
                <span><?php echo $t['menu_4_tiempos']; ?><br><small>(<?php echo $t['menu_4_sub']; ?>)</small></span>
            </label>
        </div>

        <div id="contenedor-menu-cena" style="opacity: 0.5; pointer-events: none; transition: 0.3s;">

            <details open class="seccion-maestra">
                <summary>1. <?php echo $t['entradas_title']; ?></summary>
                <div class="contenido-interno">
                    <?php pintarSeccionSubdividida("Entradas", $conn, $lang); ?>
                </div>
            </details>

            <details open class="seccion-maestra">
                <summary>2. <?php echo $t['platos_fuertes_title']; ?></summary>
                <div class="contenido-interno">
                    <?php pintarSeccionSubdividida("Plato Fuerte", $conn, $lang); ?>
                </div>
            </details>

            <details open class="seccion-maestra">
                <summary>3. <?php echo $t['postres_title']; ?></summary>
                <div class="contenido-interno">
                    <?php pintarSeccionSubdividida("Postres", $conn, $lang); ?>
                </div>
            </details>

            <!-- SECCIÓN DE SORBET (solo para 4 tiempos) -->
            <details open class="seccion-maestra" id="seccion-sorbet" style="display: none;">
                <summary>4. <?php echo $t['sorbet_title']; ?></summary>
                <div class="contenido-interno">
                    <div class="grid-platos">
                        <!-- Imagen por defecto para sorbet -->
                        <label class="item-cena"
                            onmouseover="actualizarPrevisualizacion('../img/no-image.png', '<?php echo $t['sorbet_limon']; ?>')">
                            <input type="checkbox" name="bocaditos[]" value="Sorbet de Limón" data-group="Sorbet">
                            <span><?php echo $t['sorbet_limon']; ?></span>
                        </label>
                    </div>
                </div>
            </details>
        </div>
    </div>

    <!-- COLUMNA DERECHA: VISTA PREVIA -->
    <div class="columna-previa">
        <div class="preview-card">
            <div class="preview-header">
                <h3><?php echo $t['vista_previa']; ?></h3>
                <p class="preview-label"><?php echo $t['instruccion_preview']; ?></p>
            </div>

            <div class="preview-image-container">
                <img id="preview-image" src="../img/no-image.png" alt="<?php echo $t['alt_preview']; ?>">
            </div>

            <div class="preview-info">
                <h4 id="preview-title"><?php echo $t['select_plate']; ?></h4>
            </div>

            <div class="selection-summary">
                <div class="selection-counter">
                    <strong><?php echo $t['total_selected']; ?>:</strong>
                    <span id="selected-count">0</span> / <span id="max-selections">0</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/menu/almuerzo_cena.js"></script>