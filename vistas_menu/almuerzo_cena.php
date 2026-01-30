<?php
include_once __DIR__ . '/../php/conexion.php';
include_once __DIR__ . '/../php/platos_config.php';

// FUNCIÓN SIMPLIFICADA como Seminario
if (!function_exists('pintarCheckCena')) {
    function pintarCheckCena($nombre, $grupo, $imagen, $t, $tiempo_db = '', $info_extra = [])
    {
        $ruta_img = !empty($imagen) ? "img/menu_almuerzo/$imagen" : "img/no-image.png";
        $nombreTraducido = isset($t[$nombre]) ? $t[$nombre] : $nombre;

        // Data attributes adicionales para platos fuertes
        $data_extra = '';
        if ($tiempo_db == 'Plato Fuerte' && !empty($info_extra)) {
            $data_extra = ' data-guarnicion="' . htmlspecialchars($info_extra['guarnicion'] ?? '', ENT_QUOTES) . '"';
            $data_extra .= ' data-vegetales="' . htmlspecialchars($info_extra['vegetales'] ?? '', ENT_QUOTES) . '"';
        }

        echo "<label class='item-cena' data-img='$ruta_img'$data_extra>
                <input type='checkbox' name='bocaditos[]' value='$nombre' data-group='$grupo'> 
                <span>$nombreTraducido</span>
              </label>";
    }
}

$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';
$t = $texts[$lang];
?>

<link rel="stylesheet" href="css/menu/almuerzo_cena.css">

<div class="instruccion"
    style="grid-column: 1 / -1; background: #fff8e1; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px;">
    <strong><?php echo $t['menu_almuerzo_title']; ?></strong> <?php echo $t['menu_almuerzo_desc']; ?>
</div>

<div style="grid-column: 1 / -1; display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
    <label class="plan-card">
        <input type="radio" name="menu_opcion" value="Menú 2 Tiempos" onclick="activarMenu(2)" required>
        <div class="plan-titulo"><?php echo $t['menu_2_tiempos']; ?></div>
        <ul class="plan-detalles">
            <li><?php echo $t['menu_2_sub']; ?></li>
        </ul>
    </label>

    <label class="plan-card">
        <input type="radio" name="menu_opcion" value="Menú 3 Tiempos" onclick="activarMenu(3)">
        <div class="plan-titulo"><?php echo $t['menu_3_tiempos']; ?></div>
        <ul class="plan-detalles">
            <li><?php echo $t['menu_3_sub']; ?></li>
        </ul>
    </label>

    <label class="plan-card">
        <input type="radio" name="menu_opcion" value="Menú 4 Tiempos" onclick="activarMenu(3)">
        <div class="plan-titulo">Menú 4 Tiempos</div>
        <ul class="plan-detalles">
            <li>Entrada + Plato Fuerte + Postre + Sorbet incluido</li>
        </ul>
    </label>
</div>

<div id="contenedor-menu-cena"
    style="grid-column: 1 / -1; opacity: 0.5; pointer-events: none; transition: 0.3s; display: grid; grid-template-columns: 1fr 320px; gap: 20px;">

    <div class="opciones-cena">
        <!-- SECCIONES COMO SEMINARIO - CAMBIADO: QUITADO "open" -->
        <?php
        $secciones_config = [
            'Entradas' => ['titulo' => $t['entradas_title'], 'grupo' => 'Entradas'],
            'Plato Fuerte' => ['titulo' => $t['platos_fuertes_title'], 'grupo' => 'Plato Fuerte'],
            'Postres' => ['titulo' => $t['postres_title'], 'grupo' => 'Postres']
        ];

        foreach ($secciones_config as $seccion_db => $info):
            ?>
            <details class="seccion-seminario">
                <!-- SIN "open" para que empiece cerrado -->
                <summary><?php echo $info['titulo']; ?></summary>
                <div class="contenido-seminario">
                    <?php
                    // Obtener subcategorías
                    $sqlSub = "SELECT DISTINCT subcategoria FROM menu_almuerzo_cena 
                          WHERE tiempo = '$seccion_db' AND estado = 1 ORDER BY subcategoria ASC";
                    $resSub = $conn->query($sqlSub);

                    if ($resSub && $resSub->num_rows > 0):
                        while ($sub = $resSub->fetch_assoc()):
                            $nombreSub = $sub['subcategoria'];
                            $nombreSubTraducido = isset($t[$nombreSub]) ? $t[$nombreSub] : $nombreSub;
                            ?>
                            <p class="sub-cat"><?php echo $nombreSubTraducido; ?></p>
                            <div class="grid-items">
                                <?php
                                // Obtener platos de esta subcategoría
                                $sqlPlatos = "SELECT * FROM menu_almuerzo_cena 
                                 WHERE tiempo = '$seccion_db' AND subcategoria = '$nombreSub' AND estado = 1";
                                $resPlatos = $conn->query($sqlPlatos);

                                while ($item = $resPlatos->fetch_assoc()):
                                    $info_extra = [];
                                    if ($seccion_db == 'Plato Fuerte') {
                                        $info_extra = obtenerInfoPlato($item['nombre']);
                                    }

                                    pintarCheckCena(
                                        $item['nombre'],
                                        $info['grupo'],
                                        $item['imagen_url'],
                                        $t,
                                        $seccion_db,
                                        $info_extra
                                    );
                                endwhile;
                                ?>
                            </div>
                            <?php
                        endwhile;
                    else:
                        echo "<small style='color:#999;'>" . ($lang === 'en' ? 'No options configured.' : 'No hay opciones configuradas.') . "</small>";
                    endif;
                    ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <!-- VISTA PREVIA COMO SEMINARIO -->
    <div class="visor-cena" style="padding-top: 30px;">
        <div
            style="position: sticky; top: 20px; background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;">
            <p style="font-size: 11px; font-weight: bold; color: #888; margin-bottom: 10px; text-transform: uppercase;">
                <?php echo $t['vista_previa']; ?>
            </p>

            <div
                style="width: 100%; height: 200px; overflow: hidden; border-radius: 4px; background: #f9f9f9; border: 1px solid #eee;">
                <img id="preview-image" src="img/no-image.png"
                    style="width: 100%; height: 100%; object-fit: cover; transition: 0.3s;">
            </div>

            <p id="preview-title"
                style="margin-top: 15px; font-weight: bold; font-size: 14px; color: #333; min-height: 40px;">
                <?php echo $t['select_plate']; ?>
            </p>

            <!-- DETALLES EXTRA PARA PLATOS FUERTES -->
            <div id="preview-details" style="display: none; margin-top: 10px; text-align: left; font-size: 0.85rem;">
                <div style="margin-bottom: 5px;">
                    <strong><?php echo $t['guarnicion'] ?? 'Guarnición'; ?>:</strong>
                    <span id="detail-guarnicion" style="color: #555;">-</span>
                </div>
                <div>
                    <strong><?php echo $t['vegetales'] ?? 'Vegetales'; ?>:</strong>
                    <span id="detail-vegetales" style="color: #555;">-</span>
                </div>
            </div>

            <div class="selection-summary"
                style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <strong><?php echo $t['total_selected']; ?>:</strong>
                    <span id="selected-count" style="font-weight: 600; color: #d35400;">0</span> /
                    <span id="max-selections" style="font-weight: 600;">0</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/menu/almuerzo_cena.js"></script>