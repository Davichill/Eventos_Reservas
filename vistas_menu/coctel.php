<?php
// Aseguramos que $t esté disponible. Si no existe (acceso directo), cargamos por defecto 'es'
if (!isset($t)) {
    include_once __DIR__ . '/../idiomas.php';
    $lang = $_SESSION['lang'] ?? 'es';
    $t = $texts[$lang];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="stylesheet" href="css/menu/coctel.css">
</head>

<body>
    <div class="coctel-layout-container">

        <div class="instruccion-sticky">
            <p>
                <strong><?= $t['menu_plan_coctel_title'] ?></strong> 
                <?= $t['menu_plan_coctel_desc'] ?>
            </p>
        </div>

        <div class="coctel-grid-cuerpo">
            <div class="opciones-columna-izq">
                <?php
                include_once __DIR__ . '/../php/conexion.php';

                if (!function_exists('pintarEstructuraCoctel')) {
                    function pintarEstructuraCoctel($conn, $t)
                    {
                        $rutaNavegador = "img/menu_coctel/";

                        $sqlCat = "SELECT DISTINCT categoria FROM menu_coctel 
                                   ORDER BY FIELD(categoria, 'BOCADOS SALADOS', 'VEGETARIANO / VEGANO', 'MARISCOS Y PESCADOS', 'BOCADITOS DULCES')";
                        $resCat = $conn->query($sqlCat);

                        while ($cat = $resCat->fetch_assoc()) {
                            $nombreCat = $cat['categoria'];
                            
                            // Lógica de traducción para categorías de la BD
                            $keyCat = 'cat_' . strtolower(str_replace([' ', '/'], ['_', '_'], $nombreCat));
                            $tituloMostrar = isset($t[$keyCat]) ? $t[$keyCat] : $nombreCat;

                            echo '<div class="categoria-seccion">';
                            echo '<h3 class="titulo-categoria-pdf">' . $tituloMostrar . '</h3>';

                            $sqlSub = "SELECT DISTINCT subcategoria FROM menu_coctel WHERE categoria = '$nombreCat' ORDER BY subcategoria ASC";
                            $resSub = $conn->query($sqlSub);

                            while ($sub = $resSub->fetch_assoc()) {
                                $nombreSub = $sub['subcategoria'];
                                if ($nombreSub !== "General") {
                                    echo '<h4 class="subtitulo-pdf">' . $nombreSub . '</h4>';
                                }

                                echo '<div class="grid-checks-bocaditos">';
                                $sqlItems = "SELECT * FROM menu_coctel WHERE categoria = '$nombreCat' AND subcategoria = '$nombreSub' AND estado = 1";
                                $resItems = $conn->query($sqlItems);

                                while ($item = $resItems->fetch_assoc()) {
                                    $imgFull = !empty($item['imagen_url']) ? $rutaNavegador . $item['imagen_url'] : 'img/no-image.png';
                                    ?>
                                    <div class="checkbox-item">
                                        <label class="label-click" onmouseover="actualizarVisor('<?= $imgFull ?>', '<?= $item['nombre'] ?>')">
                                            <input type="checkbox" name="bocaditos[]" value="<?= $item['nombre'] ?>" class="check-bocadito">
                                            <span class="label-text"><?= $item['nombre'] ?></span>
                                        </label>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                    }
                }
                pintarEstructuraCoctel($conn, $t);
                ?>
            </div>

            <div class="visor-columna">
                <div class="visor-sticky-card">
                    <p class="visor-label"><?= $t['vista_previa'] ?></p>
                    <div id="contenedor-img-visor">
                        <img id="img-visor" src="img/no-image.png" alt="Referencia">
                    </div>
                    <h5 id="nombre-bocado-visor"><?= $t['visor_instruccion'] ?></h5>

                    <div class="contador-votos">
                        <?= $t['seleccionados'] ?>: <span id="count">0</span> / 6
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<script src="js/menu/coctel.js"></script>
</html>