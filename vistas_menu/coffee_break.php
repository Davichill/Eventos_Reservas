<?php 
// 1. Modificamos la función para recibir el nombre de la imagen
if (!function_exists('pintarCheckCoffee')) {
    function pintarCheckCoffee($nombre, $imagen) {
        $ruta_img = !empty($imagen) ? "img/menu_coffee/$imagen" : "img/placeholder.jpg";
        echo "<label class='item-coffee' data-img='$ruta_img'>
                <input type='checkbox' name='bocaditos[]' value='$nombre'> 
                <span>$nombre</span>
              </label>";
    }
}

// Obtener idioma (asumiendo que hay una variable $lang disponible)
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';
$t = $texts[$lang];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="css/menu/coffee_break.css">
</head>
<body>

<div class="instruccion" style="grid-column: 1 / -1; background: #fff8e1; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px;">
    <strong><?php echo $t['menu_plan_coctel_title']; ?></strong> <?php echo $t['menu_plan_coctel_desc']; ?>
</div>

<div style="grid-column: 1 / -1; display: flex; gap: 15px; margin-bottom: 20px;">
    <label class="plan-btn">
        <input type="radio" name="menu_opcion" value="Coffee Break AM" onclick="activarCoffee()" required>
        <span><?php echo $t['coffee_am']; ?></span>
    </label>
    <label class="plan-btn">
        <input type="radio" name="menu_opcion" value="Coffee Break PM" onclick="activarCoffee()">
        <span><?php echo $t['coffee_pm']; ?></span>
    </label>
</div>

<div id="contenedor-coffee" style="grid-column: 1 / -1; opacity: 0.5; pointer-events: none; transition: 0.3s; display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
    
    <div class="lista-opciones">
        <?php
        $sql_categorias = "SELECT DISTINCT categoria FROM menu_coffee_break WHERE estado = 1 ORDER BY categoria ASC";
        $res_categorias = $conn->query($sql_categorias);

        if ($res_categorias && $res_categorias->num_rows > 0):
            while ($cat = $res_categorias->fetch_assoc()):
                $nombre_categoria = $cat['categoria'];
                // Traducir el nombre de la categoría si existe en el diccionario
                $nombre_categoria_traduccion = isset($t[$nombre_categoria]) ? $t[$nombre_categoria] : $nombre_categoria;
        ?>
            <details class="seccion-maestra">
                <summary><?= htmlspecialchars($nombre_categoria_traduccion) ?></summary>
                <div class="contenido-interno">
                    <div class="grid-platos">
                        <?php
                        // 2. Traemos imagen_url en la consulta
                        $sql_bocaditos = "SELECT nombre, imagen_url FROM menu_coffee_break WHERE categoria = '" . $conn->real_escape_string($nombre_categoria) . "' AND estado = 1 ORDER BY nombre ASC";
                        $res_bocaditos = $conn->query($sql_bocaditos);
                        while ($bocadito = $res_bocaditos->fetch_assoc()) {
                            // Traducir el nombre del bocadito si existe en el diccionario
                            $nombre_bocadito_traduccion = isset($t[$bocadito['nombre']]) ? $t[$bocadito['nombre']] : $bocadito['nombre'];
                            pintarCheckCoffee($nombre_bocadito_traduccion, $bocadito['imagen_url']);
                        }
                        ?>
                    </div>
                </div>
            </details>
        <?php 
            endwhile; 
        else:
            echo "<p style='color:gray; padding:20px;'>" . ($lang === 'en' ? 'No snacks available in the menu.' : 'No hay bocaditos disponibles en el menú.') . "</p>";
        endif;
        ?>
    </div>

    <div class="visor-lateral" style="position: sticky; top: 20px; height: fit-content; background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;">
        <span style="font-size: 11px; color: #888; font-weight: bold;"><?php echo $t['vista_previa']; ?></span>
        <div style="width: 100%; height: 180px; margin: 10px 0; background: #f9f9f9; border-radius: 5px; overflow: hidden; border: 1px solid #eee;">
            <img id="img-preview" src="img/logo-hotel.png" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <p id="txt-preview" style="font-size: 13px; font-weight: bold; color: #333;"><?php echo $t['visor_instruccion']; ?></p>
        
        <div class="contador-bocaditos" style="margin-top: 15px;">
            <?php echo $t['seleccionados']; ?>: <span id="coffee-count">0</span> <?php echo $lang === 'en' ? 'of 2' : 'de 2'; ?>
        </div>
    </div>
</div>

<script src="js/menu/coffee.js"></script>

</body>
</html>