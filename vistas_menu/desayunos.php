<?php
// Consultamos los tipos oficiales
$query = "SELECT * FROM menu_desayunos 
          WHERE estado = 1 
          ORDER BY FIELD(nombre, 'DESAYUNO SALUDABLE', 'DESAYUNO AMERICANO', 'DESAYUNO ESPECIAL', 'DESAYUNO ECUATORIANO')";
$resultado = $conn->query($query);
?>

<link rel="stylesheet" href="css/menu/desayuno.css">

<div class="layout-gestion-menu">

    <div class="columna-seleccion">
        <?php while ($row = $resultado->fetch_assoc()):
            // 1. Traducir el Título
            $llaveNombre = strtolower(str_replace(" ", "_", $row['nombre'])); 
            $label = isset($t[$llaveNombre]) ? $t[$llaveNombre] : ucwords(strtolower(str_replace("DESAYUNO ", "", $row['nombre'])));
            
            $imagen = !empty($row['imagen_url']) ? "img/menu_desayuno/" . $row['imagen_url'] : "";
            ?>
            <div class="opcion-card" onclick="seleccionarCard(this)"
                onmouseenter="mostrarPreview('<?= $imagen ?>', '<?= $label ?>')" onmouseleave="ocultarPreview()">

                <div style="display: flex; align-items: center;">
                    <input type="radio" name="menu_opcion" value="<?= $row['nombre'] ?>" required
                        style="accent-color:#d35400;">
                    <span class="titulo-desayuno"><?= $label ?></span>
                </div>

                <ul class="detalle-lista-menu">
                    <?php
                    $items = explode("\n", $row['descripcion']);
                    foreach ($items as $item) {
                        $textoItem = trim($item);
                        if ($textoItem != "") {
                            // 2. Traducir cada item de la lista
                            $itemTraducido = isset($t[$textoItem]) ? $t[$textoItem] : $textoItem;
                            echo "<li>" . htmlspecialchars($itemTraducido) . "</li>";
                        }
                    }
                    ?>
                </ul>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="columna-visor" >
        <div id="texto-placeholder">
            <p><?= $t['placeholder_visor'] ?></p>
        </div>
        <img id="visor-imagen-fixed" src="" alt="Previsualización">
        <div id="nombre-preview"></div>
    </div>

</div>

<script src="js/menu/desayuno.js"></script>