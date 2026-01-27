<?php
include_once __DIR__ . '/../php/conexion.php';

// 1. FUNCIÓN MEJORADA CON IMÁGENES - RUTAS CORREGIDAS PARA COINCIDIR CON CÓCTEL
if (!function_exists('pintarSeccionSubdividida')) {
    function pintarSeccionSubdividida($tiempo_db, $conn)
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
                
                // Imprimimos el título de la subdivisión
                echo "<p class='sub-titulo-pdf'>$nombreSub</p>";
                echo "<div class='grid-platos'>";

                // Traemos los platos de esa subcategoría
                $sqlPlatos = "SELECT * FROM menu_almuerzo_cena 
                             WHERE tiempo = '$tiempo_db' AND subcategoria = '$nombreSub' AND estado = 1";
                $resPlatos = $conn->query($sqlPlatos);

                while ($item = $resPlatos->fetch_assoc()) {
                    $nombre = $item['nombre'];
                    // RUTA CORREGIDA: Sin ../
                    $img = $item['imagen_url'] ? $rutaBase . $item['imagen_url'] : 'img/no-image.png';
                    echo "<label class='item-cena' onmouseover=\"actualizarPrevisualizacion('$img', '$nombre')\">
                            <input type='checkbox' name='bocaditos[]' value='$nombre' data-group='$tiempo_db'> 
                            <span>$nombre</span>
                          </label>";
                }
                echo "</div>"; // Cierra grid-platos
            }
        } else {
            echo "<small style='color:#999;'>No hay opciones configuradas.</small>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <link rel="stylesheet" href="css/menu/almuerzo_cena.css">

</head>

<!-- LAYOUT CON DOS COLUMNAS -->
<div class="menu-layout-container">
    <!-- COLUMNA IZQUIERDA: MENÚ DE SELECCIÓN -->
    <div class="columna-seleccion">
        <div class="instruccion" style="background: #fff8e1; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <strong>Menú Almuerzo / Cena:</strong> Seleccione el plan deseado y elija un plato por cada tiempo disponible según su plan.
        </div>

        <div class="planes-container" style="display: flex; gap: 15px; margin-bottom: 20px;">
            <label class="plan-btn">
                <input type="radio" name="menu_opcion" value="Menú 2 Tiempos" onclick="activarMenu(2)" required>
                <span>Menú 2 Tiempos<br><small>(Fuerte + Postre)</small></span>
            </label>
            <label class="plan-btn">
                <input type="radio" name="menu_opcion" value="Menú 3 Tiempos" onclick="activarMenu(3)">
                <span>Menú 3 Tiempos<br><small>(Entrada + Fuerte + Postre)</small></span>
            </label>
            <label class="plan-btn">
                <input type="radio" name="menu_opcion" value="Menú 4 Tiempos" onclick="activarMenu(4)">
                <span>Menú 4 Tiempos<br><small>(Entrada + Sorbet + Fuerte + Postre)</small></span>
            </label>
        </div>

        <div id="contenedor-menu-cena" style="opacity: 0.5; pointer-events: none; transition: 0.3s;">

            <details open class="seccion-maestra">
                <summary>1. ENTRADAS / CEVICHES</summary>
                <div class="contenido-interno">
                    <?php pintarSeccionSubdividida("Entradas", $conn); ?>
                </div>
            </details>

            <details open class="seccion-maestra">
                <summary>2. PLATOS FUERTES</summary>
                <div class="contenido-interno">
                    <?php pintarSeccionSubdividida("Plato Fuerte", $conn); ?>
                </div>
            </details>

            <details open class="seccion-maestra">
                <summary>3. POSTRES</summary>
                <div class="contenido-interno">
                    <?php pintarSeccionSubdividida("Postres", $conn); ?>
                </div>
            </details>

            <!-- SECCIÓN DE SORBET (solo para 4 tiempos) -->
            <details open class="seccion-maestra" id="seccion-sorbet" style="display: none;">
                <summary>4. SORBET DE LIMÓN</summary>
                <div class="contenido-interno">
                    <div class="grid-platos">
                        <!-- Imagen por defecto para sorbet -->
                        <label class="item-cena" onmouseover="actualizarPrevisualizacion('../img/no-image.png', 'Sorbet de Limón')">
                            <input type="checkbox" name="bocaditos[]" value="Sorbet de Limón" data-group="Sorbet"> 
                            <span>Sorbet de Limón</span>
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
                <h3>Vista Previa</h3>
                <p class="preview-label">Pase el mouse sobre un plato para verlo aquí</p>
            </div>
            
            <div class="preview-image-container">
                <img id="preview-image" src="../img/no-image.png" alt="Vista previa del plato">
            </div>
            
            <div class="preview-info">
                <h4 id="preview-title">Seleccione un plato</h4>
            </div>

            <div class="selection-summary">
                <div class="selection-counter">
                    <strong>Total seleccionados:</strong>
                    <span id="selected-count">0</span> / <span id="max-selections">0</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/menu/almuerzo_cena.js" > </script>