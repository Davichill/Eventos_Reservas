// Variables globales
    let globalMax = 0;
    const mainCont = document.getElementById('contenedor-menu-cena');
    const seccionSorbet = document.getElementById('seccion-sorbet');
    
    // Objeto para mapear categorías con nombres más amigables
    const categoriasNombres = {
        'Entradas': 'Entrada',
        'Plato Fuerte': 'Plato Principal',
        'Postres': 'Postre',
        'Sorbet': 'Sorbet'
    };

    // Función para actualizar la vista previa
    function actualizarPrevisualizacion(imagenUrl, nombrePlato) {
        const previewImg = document.getElementById('preview-image');
        const previewTitle = document.getElementById('preview-title');
        const previewDesc = document.getElementById('preview-description');
        
        // Fade out
        previewImg.style.opacity = '0.5';
        
        setTimeout(() => {
            previewImg.src = imagenUrl;
            previewImg.alt = nombrePlato;
            previewImg.style.opacity = '1';
            
            previewTitle.textContent = nombrePlato;
            
            // Descripción predeterminada
            previewDesc.textContent = 'Plato delicioso preparado por nuestros chefs.';
        }, 100);
    }

    // Función para actualizar el resumen de selección
    function actualizarResumenSeleccion() {
        const selectedItems = mainCont.querySelectorAll('input[type="checkbox"]:checked');
        const container = document.getElementById('selected-items');
        const selectedCount = document.getElementById('selected-count');
        
        selectedCount.textContent = selectedItems.length;
        
        if (selectedItems.length === 0) {
            container.innerHTML = '<p class="empty-selection">Aún no ha seleccionado platos</p>';
            return;
        }
        
        let html = '';
        selectedItems.forEach(input => {
            const nombre = input.value;
            const categoria = categoriasNombres[input.dataset.group] || input.dataset.group;
            
            html += `
                <div class="selected-item">
                    <div class="selected-item-name">${nombre}</div>
                    <div class="selected-item-category">${categoria}</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    // Función para activar el menú
    function activarMenu(n) {
        globalMax = n;
        
        // Actualizar contador máximo
        document.getElementById('max-selections').textContent = n;
        
        // Mostrar u ocultar sección de sorbet
        if (n === 4) {
            seccionSorbet.style.display = 'block';
        } else {
            seccionSorbet.style.display = 'none';
            // Desmarcar sorbet si estaba seleccionado
            const sorbetCheck = seccionSorbet.querySelector('input[type="checkbox"]');
            if (sorbetCheck) sorbetCheck.checked = false;
        }
        
        // Activar contenedor
        mainCont.style.opacity = "1";
        mainCont.style.pointerEvents = "auto";
        
        // Resetear todas las selecciones
        const checks = mainCont.querySelectorAll('input[type="checkbox"]');
        checks.forEach(c => {
            c.checked = false;
            c.disabled = false;
            c.closest('.item-cena').classList.remove('disabled');
        });
        
        // Actualizar resumen
        actualizarResumenSeleccion();
    }

    // Event listener para cambios en las selecciones
    mainCont.addEventListener('change', function (e) {
        if (e.target.type !== 'checkbox') return;

        const seleccionados = mainCont.querySelectorAll('input:checked');
        const grupo = e.target.dataset.group;
        const checksDelMismoGrupo = mainCont.querySelectorAll(`input[data-group="${grupo}"]`);

        if (seleccionados.length > globalMax) {
            e.target.checked = false;
            alert("Su plan permite máximo " + globalMax + " selecciones.");
            return;
        }

        // Lógica de 1 plato por tiempo (data-group)
        checksDelMismoGrupo.forEach(cb => {
            if (cb !== e.target) {
                if (e.target.checked) {
                    cb.disabled = true;
                    cb.closest('.item-cena').classList.add('disabled');
                } else {
                    if (seleccionados.length < globalMax) {
                        cb.disabled = false;
                        cb.closest('.item-cena').classList.remove('disabled');
                    }
                }
            }
        });

        // Lógica de límite global
        const todosLosChecks = mainCont.querySelectorAll('input[type="checkbox"]');
        if (mainCont.querySelectorAll('input:checked').length >= globalMax) {
            todosLosChecks.forEach(c => {
                if (!c.checked) {
                    c.disabled = true;
                    c.closest('.item-cena').classList.add('disabled');
                }
            });
        } else {
            todosLosChecks.forEach(c => {
                const g = c.dataset.group;
                const hayMarcadoEnGrupo = mainCont.querySelector(`input[data-group="${g}"]:checked`);
                if (!hayMarcadoEnGrupo) {
                    c.disabled = false;
                    c.closest('.item-cena').classList.remove('disabled');
                }
            });
        }

        // Actualizar el resumen de selección
        actualizarResumenSeleccion();
        
        // Si se selecciona este plato, mostrar en vista previa
        if (e.target.checked) {
            const label = e.target.closest('.item-cena');
            const nombre = e.target.value;
            // Intentar obtener la imagen del atributo onmouseover
            const onmouseover = label.getAttribute('onmouseover') || '';
            const match = onmouseover.match(/actualizarPrevisualizacion\('([^']+)',/);
            const imagenUrl = match ? match[1] : '../img/no-image.png';
            actualizarPrevisualizacion(imagenUrl, nombre);
        }
    });

    // Event listener para hover sobre los items
    document.addEventListener('DOMContentLoaded', function() {
        const items = document.querySelectorAll('.item-cena');
        items.forEach(item => {
            item.addEventListener('mouseenter', function() {
                if (!this.classList.contains('disabled')) {
                    const input = this.querySelector('input');
                    if (input && !input.disabled) {
                        const nombre = this.querySelector('span').textContent;
                        const onmouseover = this.getAttribute('onmouseover') || '';
                        const match = onmouseover.match(/actualizarPrevisualizacion\('([^']+)',/);
                        const imagenUrl = match ? match[1] : '../img/no-image.png';
                        actualizarPrevisualizacion(imagenUrl, nombre);
                    }
                }
            });
        });
    });