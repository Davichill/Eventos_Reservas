<?php
// Aseguramos que $t esté disponible.
if (!isset($t)) {
    include_once __DIR__ . '/../idiomas.php';
    $lang = $_SESSION['lang'] ?? 'es';
    $t = $texts[$lang];
}
include_once __DIR__ . '/../php/conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="stylesheet" href="css/menu/coctel.css">
    <style>
        /* Contenedor principal de la sección */
        .categoria-seccion {
            margin-bottom: 10px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        /* Encabezado que recibe el clic */
        .categoria-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            background-color: #f8f9fa;
            padding: 15px 20px;
            transition: background 0.3s ease;
            user-select: none;
        }

        .categoria-header:hover {
            background-color: #f0f0f0;
        }

        /* Contenedor del contenido */
        .categoria-contenido {
            max-height: 2000px;
            opacity: 1;
            overflow: hidden;
            transition: max-height 0.4s ease, opacity 0.3s ease, padding 0.4s ease;
            padding: 15px;
        }

        /* Estado cerrado */
        .seccion-cerrada .categoria-contenido {
            max-height: 0 !important;
            opacity: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            pointer-events: none;
        }

        /* Estado cerrado - encabezado */
        .seccion-cerrada .categoria-header {
            background-color: #e9ecef;
        }

        /* Rotación de la flecha */
        .flecha-toggle {
            display: inline-block;
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }

        .seccion-cerrada .flecha-toggle {
            transform: rotate(-90deg);
        }

        /* Para que todas las secciones empiecen abiertas por defecto */
        .categoria-seccion:not(.seccion-cerrada) {
            border-color: #dee2e6;
        }
        
        /* Mejor visualización del checkbox deshabilitado */
        .disabled-check {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .disabled-check .label-text {
            color: #999;
        }
    </style>
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
                if (!function_exists('pintarEstructuraCoctel')) {
                    function pintarEstructuraCoctel($conn, $t)
                    {
                        $rutaNavegador = "img/menu_coctel/";
                        $imgDefault = "img/no-image.png";

                        $sqlCat = "SELECT DISTINCT categoria FROM menu_coctel 
                                   ORDER BY FIELD(categoria, 'BOCADOS SALADOS', 'VEGETARIANO / VEGANO', 'MARISCOS Y PESCADOS', 'BOCADITOS DULCES')";
                        $resCat = $conn->query($sqlCat);
                        
                        $index = 0; // Contador para identificar cada sección

                        while ($cat = $resCat->fetch_assoc()) {
                            $nombreCat = $cat['categoria'];
                            $keyCat = 'cat_' . strtolower(str_replace([' ', '/'], ['_', '_'], $nombreCat));
                            $tituloMostrar = $t[$keyCat] ?? $nombreCat;
                            
                            // Identificador único para la sección
                            $sectionId = 'cat-' . strtolower(str_replace([' ', '/'], ['-', '-'], $nombreCat));
                            
                            // Por defecto, todas las secciones comienzan ABIERTAS
                            $closedByDefault = false;

                            echo '<div class="categoria-seccion" id="' . $sectionId . '" data-index="' . $index . '">';
                            echo '<div class="categoria-header" onclick="toggleSeccion(this)">';
                            echo '<h3 class="titulo-categoria-pdf" style="margin:0;">' . $tituloMostrar . '</h3>';
                            echo '<span class="flecha-toggle">▼</span>';
                            echo '</div>';

                            echo '<div class="categoria-contenido">';
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
                                    $imgFull = (!empty($item['imagen_url']) && file_exists(__DIR__ . '/../' . $rutaNavegador . $item['imagen_url']))
                                        ? $rutaNavegador . $item['imagen_url']
                                        : $imgDefault;
                                    ?>
                                    <div class="checkbox-item">
                                        <label class="label-click"
                                            onmouseover="actualizarVisor('<?= $imgFull ?>', '<?= htmlspecialchars($item['nombre']) ?>')">
                                            <input type="checkbox" name="bocaditos[]" value="<?= htmlspecialchars($item['nombre']) ?>"
                                                class="check-bocadito" data-categoria="<?= htmlspecialchars($nombreCat) ?>">
                                            <span class="label-text"><?= htmlspecialchars($item['nombre']) ?></span>
                                        </label>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            }
                            echo '</div>'; // Cierre contenido
                            echo '</div>'; // Cierre sección
                            $index++;
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
                    
                    <!-- Botones de control del acordeón -->
                    <div class="acordeon-controls" style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="expandirTodo()" class="btn-acordeon" style="background: #27ae60; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-expand"></i> Expandir Todo
                        </button>
                        <button type="button" onclick="colapsarTodo()" class="btn-acordeon" style="background: #e74c3c; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-compress"></i> Colapsar Todo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // ============================================
        // FUNCIONES PARA EL ACORDEÓN
        // ============================================
        
        // Variable global para el estado del acordeón
        const ACORDEON_STORAGE_KEY = 'acordeon_coctel_estado';
        
        /**
         * Alterna el estado de una sección (abrir/cerrar)
         */
        function toggleSeccion(elemento) {
            const seccion = elemento.parentElement;
            const seccionId = seccion.id;
            
            // Alternar clase
            seccion.classList.toggle('seccion-cerrada');
            
            // Guardar estado en localStorage
            guardarEstadoAcordeon();
            
            // También guardar el estado específico de esta sección
            const estaCerrada = seccion.classList.contains('seccion-cerrada');
            guardarEstadoSeccion(seccionId, estaCerrada);
        }
        
        /**
         * Guarda el estado completo del acordeón en localStorage
         */
        function guardarEstadoAcordeon() {
            const secciones = document.querySelectorAll('.categoria-seccion');
            const estado = {};
            
            secciones.forEach(seccion => {
                const seccionId = seccion.id;
                estado[seccionId] = seccion.classList.contains('seccion-cerrada');
            });
            
            try {
                localStorage.setItem(ACORDEON_STORAGE_KEY, JSON.stringify(estado));
            } catch (e) {
                console.error('Error al guardar estado del acordeón:', e);
            }
        }
        
        /**
         * Guarda el estado de una sección específica
         */
        function guardarEstadoSeccion(seccionId, estaCerrada) {
            try {
                const estadoActual = JSON.parse(localStorage.getItem(ACORDEON_STORAGE_KEY)) || {};
                estadoActual[seccionId] = estaCerrada;
                localStorage.setItem(ACORDEON_STORAGE_KEY, JSON.stringify(estadoActual));
            } catch (e) {
                console.error('Error al guardar estado de sección:', e);
            }
        }
        
        /**
         * Carga el estado guardado del acordeón al cargar la página
         */
        function cargarEstadoAcordeon() {
            try {
                const estadoGuardado = localStorage.getItem(ACORDEON_STORAGE_KEY);
                if (!estadoGuardado) return;
                
                const estado = JSON.parse(estadoGuardado);
                const secciones = document.querySelectorAll('.categoria-seccion');
                
                secciones.forEach(seccion => {
                    const seccionId = seccion.id;
                    if (estado.hasOwnProperty(seccionId)) {
                        if (estado[seccionId] === true) {
                            seccion.classList.add('seccion-cerrada');
                        } else {
                            seccion.classList.remove('seccion-cerrada');
                        }
                    }
                });
            } catch (e) {
                console.error('Error al cargar estado del acordeón:', e);
            }
        }
        
        /**
         * Expande todas las secciones
         */
        function expandirTodo() {
            const secciones = document.querySelectorAll('.categoria-seccion');
            secciones.forEach(seccion => {
                seccion.classList.remove('seccion-cerrada');
            });
            guardarEstadoAcordeon();
        }
        
        /**
         * Colapsa todas las secciones
         */
        function colapsarTodo() {
            const secciones = document.querySelectorAll('.categoria-seccion');
            secciones.forEach(seccion => {
                seccion.classList.add('seccion-cerrada');
            });
            guardarEstadoAcordeon();
        }
        
        /**
         * Expande solo las secciones que tienen checkboxes seleccionados
         */
        function expandirSeleccionados() {
            const secciones = document.querySelectorAll('.categoria-seccion');
            secciones.forEach(seccion => {
                const tieneSeleccionados = seccion.querySelector('.check-bocadito:checked');
                if (tieneSeleccionados) {
                    seccion.classList.remove('seccion-cerrada');
                } else {
                    seccion.classList.add('seccion-cerrada');
                }
            });
            guardarEstadoAcordeon();
        }
        
        // ============================================
        // FUNCIONES PARA EL VISOR Y CHECKBOXES
        // ============================================
        
        function actualizarVisor(ruta, nombre) {
            const img = document.getElementById('img-visor');
            const txt = document.getElementById('nombre-bocado-visor');
            
            img.style.opacity = '0.7';
            setTimeout(() => {
                img.src = ruta;
                img.style.opacity = '1';
                txt.innerText = nombre;
                txt.classList.remove('lang-txt');
            }, 100);
        }
        
        // Manejo de cambios en checkboxes
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('check-bocadito')) {
                const limit = 6;
                const seleccionados = document.querySelectorAll('.check-bocadito:checked');
                const checks = document.querySelectorAll('.check-bocadito');
                
                // Actualizar contador
                const countElement = document.getElementById('count');
                countElement.innerText = seleccionados.length;
                
                // Feedback visual
                if (seleccionados.length === limit) {
                    countElement.style.color = '#e74c3c';
                    countElement.style.fontWeight = 'bold';
                    countElement.style.backgroundColor = '#fff3e0';
                    countElement.style.padding = '2px 8px';
                    countElement.style.borderRadius = '4px';
                } else if (seleccionados.length >= limit - 1) {
                    countElement.style.color = '#f39c12';
                    countElement.style.fontWeight = 'bold';
                } else {
                    countElement.style.color = '';
                    countElement.style.fontWeight = '';
                    countElement.style.backgroundColor = '';
                    countElement.style.padding = '';
                    countElement.style.borderRadius = '';
                }
                
                // Habilitar/deshabilitar checkboxes
                checks.forEach(c => {
                    const parent = c.closest('.checkbox-item') || c.parentElement;
                    
                    if (seleccionados.length >= limit) {
                        if (!c.checked) {
                            c.disabled = true;
                            parent.classList.add('disabled-check');
                        }
                    } else {
                        c.disabled = false;
                        parent.classList.remove('disabled-check');
                    }
                });
                
                // Auto-expandir sección cuando se selecciona un item
                if (e.target.checked) {
                    const seccion = e.target.closest('.categoria-seccion');
                    if (seccion && seccion.classList.contains('seccion-cerrada')) {
                        seccion.classList.remove('seccion-cerrada');
                        guardarEstadoSeccion(seccion.id, false);
                    }
                }
            }
        });
        
        function resetVisor() {
            const txt = document.getElementById('nombre-bocado-visor');
            txt.classList.add('lang-txt');
            txt.setAttribute('data-key', 'visor_instruccion');
            if (typeof traducirPagina === 'function') {
                traducirPagina(window.currentLang || 'es');
            }
        }
        
        // ============================================
        // INICIALIZACIÓN AL CARGAR LA PÁGINA
        // ============================================
        
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Cargar estado guardado del acordeón
            cargarEstadoAcordeon();
            
            // 2. Configurar botones de control del acordeón
            const acordeonControls = document.querySelector('.acordeon-controls');
            if (!acordeonControls) {
                // Si no existen los botones en el HTML, crearlos dinámicamente
                const visorCard = document.querySelector('.visor-sticky-card');
                if (visorCard) {
                    const controlsHtml = `
                        <div class="acordeon-controls" style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" onclick="expandirTodo()" class="btn-acordeon" style="background: #27ae60; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                                <i class="fas fa-expand"></i> Expandir Todo
                            </button>
                            <button type="button" onclick="colapsarTodo()" class="btn-acordeon" style="background: #e74c3c; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                                <i class="fas fa-compress"></i> Colapsar Todo
                            </button>
                            <button type="button" onclick="expandirSeleccionados()" class="btn-acordeon" style="background: #3498db; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                                <i class="fas fa-filter"></i> Solo Seleccionados
                            </button>
                        </div>
                    `;
                    visorCard.insertAdjacentHTML('beforeend', controlsHtml);
                }
            }
            
            // 3. Inicializar contador
            const seleccionadosInicial = document.querySelectorAll('.check-bocadito:checked').length;
            document.getElementById('count').innerText = seleccionadosInicial;
            
            // 4. Verificar si hay checkboxes seleccionados y expandir sus secciones
            if (seleccionadosInicial > 0) {
                setTimeout(expandirSeleccionados, 500);
            }
            
            // 5. Asegurar que todas las secciones tengan ID si no lo tienen
            const seccionesSinId = document.querySelectorAll('.categoria-seccion:not([id])');
            seccionesSinId.forEach((seccion, index) => {
                seccion.id = 'cat-seccion-' + index;
            });
        });
        
        // Guardar estado antes de recargar la página
        window.addEventListener('beforeunload', function() {
            guardarEstadoAcordeon();
        });
    </script>
</body>
</html>