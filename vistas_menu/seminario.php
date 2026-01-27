<?php
// Asegurar que $t esté disponible
if (!isset($t)) {
    include_once __DIR__ . '/../idiomas.php';
    $lang = $_SESSION['lang'] ?? 'es';
    $t = $texts[$lang];
}

if (!function_exists('pintarCheckSeminario')) {
    function pintarCheckSeminario($nombre, $grupo, $imagen, $t)
    {
        $ruta_img = !empty($imagen) ? "img/menu_seminario/$imagen" : "img/placeholder.jpg";
        // Buscamos si el nombre del plato tiene traducción
        $nombreTraducido = isset($t[$nombre]) ? $t[$nombre] : $nombre;

        echo "<label class='item-seminario' data-img='$ruta_img'>
                <input type='checkbox' name='bocaditos[]' value='$nombre' data-group='$grupo'> 
                <span>$nombreTraducido</span>
              </label>";
    }
}
?>

<link rel="stylesheet" href="css/menu/seminario.css">

<div class="instruccion"
    style="grid-column: 1 / -1; background: #e8f4f8; border-left: 5px solid #3498db; padding: 15px; margin-bottom: 20px;">
    <strong><?= $t['seminario_title'] ?></strong> <?= $t['seminario_desc'] ?>
</div>

<div style="grid-column: 1 / -1; display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
    <label class="plan-card">
        <input type="radio" name="menu_opcion" value="Seminario Full Day" onclick="activarSeminario()" required>
        <div class="plan-titulo"><?= $t['full_day'] ?></div>
        <ul class="plan-detalles">
            <li><?= $t['coffee_am'] ?></li>
            <li><strong><?= $t['almuerzo_3'] ?></strong></li>
            <li><?= $t['coffee_pm'] ?></li>
            <li><?= $t['agua_ilimitada'] ?></li>
        </ul>
    </label>

    <label class="plan-card">
        <input type="radio" name="menu_opcion" value="Seminario Half Day" onclick="activarSeminario()">
        <div class="plan-titulo"><?= $t['half_day'] ?></div>
        <ul class="plan-detalles">
            <li>1 <?= $t['coffee_am'] ?> (AM o PM)</li>
            <li><strong><?= $t['almuerzo_3'] ?></strong></li>
            <li><?= $t['agua_ilimitada'] ?></li>
        </ul>
    </label>
</div>

<div id="contenedor-almuerzo-seminario"
    style="grid-column: 1 / -1; opacity: 0.5; pointer-events: none; transition: 0.3s; display: grid; grid-template-columns: 1fr 320px; gap: 20px;">

    <div class="opciones-seminario">
        <h3 style="color: #001f3f; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-top: 30px;">
            <?= $t['sel_almuerzo'] ?>
        </h3>
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;"><?= $t['instruccion_almuerzo'] ?></p>

        <?php
        $secciones_config = [
            'ENTRADA' => ['titulo' => $t['entrada_titulo'], 'grupo' => 'entrada'],
            'PLATO FUERTE' => ['titulo' => $t['fuerte_titulo'], 'grupo' => 'fuerte'],
            'POSTRE' => ['titulo' => $t['postre_titulo'], 'grupo' => 'postre']
        ];

        foreach ($secciones_config as $seccion_db => $info):
            ?>
            <details class="seccion-seminario" <?= ($seccion_db == 'ENTRADA') ? 'open' : '' ?>>
                <summary><?= $info['titulo'] ?></summary>
                <div class="contenido-seminario">
                    <?php
                    $sql_cats = "SELECT DISTINCT categoria FROM menu_seminario WHERE seccion = '$seccion_db' AND estado = 1";
                    $res_cats = $conn->query($sql_cats);

                    while ($cat = $res_cats->fetch_assoc()):
                        $nombre_cat = $cat['categoria'];
                        // Traducir categoría si existe en idiomas.php
                        $keyCat = 'cat_' . strtolower(str_replace([' ', '/'], ['_', '_'], $nombre_cat));
                        $tituloCat = isset($t[$keyCat]) ? $t[$keyCat] : $nombre_cat;
                        ?>
                        <p class="sub-cat"><?= $tituloCat ?></p>
                        <div class="grid-items">
                            <?php
                            $sql_items = "SELECT nombre, imagen_url FROM menu_seminario WHERE categoria = '$nombre_cat' AND seccion = '$seccion_db' AND estado = 1";
                            $res_items = $conn->query($sql_items);
                            while ($item = $res_items->fetch_assoc()) {
                                pintarCheckSeminario($item['nombre'], $info['grupo'], $item['imagen_url'], $t);
                            }
                            ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <div class="visor-seminario" style="padding-top: 30px;">
        <div
            style="position: sticky; top: 20px; background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;">
            <p style="font-size: 11px; font-weight: bold; color: #888; margin-bottom: 10px; text-transform: uppercase;">
                <?= $t['vista_previa'] ?></p>
            <div
                style="width: 100%; height: 200px; overflow: hidden; border-radius: 4px; background: #f9f9f9; border: 1px solid #eee;">
                <img id="img-preview-seminario" src="img/logo-hotel.png"
                    style="width: 100%; height: 100%; object-fit: cover; transition: 0.3s;">
            </div>
            <p id="txt-preview-seminario"
                style="margin-top: 15px; font-weight: bold; font-size: 14px; color: #333; min-height: 40px;">
                <?= $t['visor_seminario'] ?></p>
        </div>
    </div>
</div>

<script src="js/menu/seminario.js"></script>