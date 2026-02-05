// editar_evento.js - Funciones para la edición de eventos

// Variables globales
let platosDisponibles = [];
let categoriasPlatos = {};
let eventoHoraInicio = '';
let eventoHoraFin = '';

// Función de inicialización
function initializeEventoEditor() {
    platosDisponibles = window.platosDisponibles || [];
    categoriasPlatos = window.categoriasPlatos || {};
    eventoHoraInicio = window.eventoHoraInicio || '';
    eventoHoraFin = window.eventoHoraFin || '';
    
    // Inicializar selects de hora
    initializeTimeSelect('hora_inicio', eventoHoraInicio);
    initializeTimeSelect('hora_fin', eventoHoraFin, 'hora_inicio');
    
    // Calcular total inicial
    calculateTotal();
    updatePlatoCounter();
}

// Funciones de manejo de hora
function initializeTimeSelect(selectId, currentValue, dependentSelectId = null) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccione hora...</option>';
    
    // Generar opciones de hora cada 30 minutos
    for (let hour = 0; hour < 24; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            const displayTime = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            
            const option = document.createElement('option');
            option.value = timeString;
            option.textContent = displayTime;
            
            if (timeString === currentValue) {
                option.selected = true;
            }
            
            select.appendChild(option);
        }
    }
    
    if (dependentSelectId) {
        select.addEventListener('change', function() {
            updateEndTimeOptions(dependentSelectId, selectId);
        });
    }
}

function updateEndTimeOptions(startSelectId, endSelectId) {
    const startSelect = document.getElementById(startSelectId);
    const endSelect = document.getElementById(endSelectId);
    
    if (!startSelect || !endSelect || !startSelect.value) {
        if (endSelect) {
            endSelect.innerHTML = '<option value="">Seleccione inicio primero</option>';
        }
        return;
    }
    
    const [startHour, startMinute] = startSelect.value.split(':').map(Number);
    
    endSelect.innerHTML = '<option value="">Seleccione hora...</option>';
    
    // Generar opciones desde 30 minutos después de la hora de inicio hasta 23:59
    for (let hour = startHour; hour < 24; hour++) {
        for (let minute = (hour === startHour ? startMinute + 30 : 0); minute < 60; minute += 30) {
            if (hour === startHour && minute <= startMinute) continue;
            
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            const displayTime = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            
            const option = document.createElement('option');
            option.value = timeString;
            option.textContent = displayTime;
            
            // Seleccionar el valor actual si existe
            if (timeString === eventoHoraFin) {
                option.selected = true;
            }
            
            endSelect.appendChild(option);
        }
    }
}

// Funciones para los tabs
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    const tabElement = document.getElementById(`tab-${tabName}`);
    if (tabElement) {
        tabElement.classList.add('active');
    }
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const tabButton = document.querySelector(`.tab-btn[onclick*="showTab('${tabName}')"]`);
    if (tabButton) {
        tabButton.classList.add('active');
    }
}

// Funciones para la gestión de menús
function addMenuItem() {
    const container = document.getElementById('menu-items-container');
    if (!container) return;
    
    const items = container.querySelectorAll('.menu-item');
    const newIndex = items.length;
    
    const newItem = document.createElement('div');
    newItem.className = 'menu-item';
    newItem.setAttribute('data-index', newIndex);
    
    // Construir el select con todas las opciones agrupadas
    let selectHTML = '<select name="platos[]" class="plato-select" required onchange="updatePlatoInfo(this, ' + newIndex + ')">';
    selectHTML += '<option value="">Seleccionar plato...</option>';
    
    // Recorrer la estructura de categorías
    for (const [tipoEvento, categorias] of Object.entries(categoriasPlatos)) {
        for (const [categoria, subcategorias] of Object.entries(categorias)) {
            if (tipoEvento === 'Coffee Break' || tipoEvento === 'Desayuno') {
                // Sin subcategorías
                selectHTML += `<optgroup label="${escapeHtml(tipoEvento)} - ${escapeHtml(categoria)}">`;
                if (subcategorias.general) {
                    subcategorias.general.forEach(plato => {
                        selectHTML += `<option value="${plato.id}" 
                                         data-precio="${plato.precio}"
                                         data-categoria="${escapeHtml(plato.categoria)}"
                                         data-subcategoria="${escapeHtml(plato.subcategoria)}"
                                         data-tipo-evento="${escapeHtml(plato.tipo_evento)}">
                                       ${escapeHtml(plato.nombre_plato)} - $${parseFloat(plato.precio).toFixed(2)}
                                     </option>`;
                    });
                }
                selectHTML += '</optgroup>';
            } else {
                // Con subcategorías
                for (const [subcategoria, platos] of Object.entries(subcategorias)) {
                    selectHTML += `<optgroup label="${escapeHtml(tipoEvento)} - ${escapeHtml(categoria)} - ${escapeHtml(subcategoria)}">`;
                    platos.forEach(plato => {
                        selectHTML += `<option value="${plato.id}" 
                                         data-precio="${plato.precio}"
                                         data-categoria="${escapeHtml(plato.categoria)}"
                                         data-subcategoria="${escapeHtml(plato.subcategoria)}"
                                         data-tipo-evento="${escapeHtml(plato.tipo_evento)}">
                                       ${escapeHtml(plato.nombre_plato)} - $${parseFloat(plato.precio).toFixed(2)}
                                     </option>`;
                    });
                    selectHTML += '</optgroup>';
                }
            }
        }
    }
    
    selectHTML += '</select>';
    
    newItem.innerHTML = selectHTML + 
        `<input type="number" name="cantidades[]" class="cantidad-input" min="1" value="1" 
               placeholder="Cantidad" onchange="calculateItemTotal(this)">
         <input type="number" name="precios_unitarios[]" class="precio-unitario-input" step="0.01" min="0"
               value="0" placeholder="Precio unitario" onchange="calculateItemTotal(this)">
         <textarea name="notas_platos[]" placeholder="Notas del plato" rows="1" 
                  class="notas-plato-input"></textarea>
         <div class="menu-subtotal">$<span class="item-total">0.00</span></div>
         <button type="button" class="btn-remove-menu" onclick="removeMenuItem(this)">
            <i class="fas fa-trash"></i>
         </button>`;
    
    container.appendChild(newItem);
    updatePlatoCounter();
}

function updatePlatoInfo(select, index) {
    const selectedOption = select.selectedOptions[0];
    if (selectedOption && selectedOption.value) {
        const precio = selectedOption.dataset.precio;
        const itemDiv = select.closest('.menu-item');
        const precioInput = itemDiv.querySelector('.precio-unitario-input');
        
        // Actualizar el precio unitario con el precio del plato seleccionado
        if (precioInput) {
            precioInput.value = parseFloat(precio).toFixed(2);
            
            // Recalcular el total de este ítem
            calculateItemTotal(precioInput);
        }
    }
}

function calculateItemTotal(inputElement) {
    const itemDiv = inputElement.closest('.menu-item');
    if (!itemDiv) return;
    
    const cantidadInput = itemDiv.querySelector('.cantidad-input');
    const precioInput = itemDiv.querySelector('.precio-unitario-input');
    const totalSpan = itemDiv.querySelector('.item-total');
    
    if (!cantidadInput || !precioInput || !totalSpan) return;
    
    const cantidad = parseFloat(cantidadInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;
    const total = cantidad * precio;
    
    totalSpan.textContent = total.toFixed(2);
    calculateTotal();
}

function removeMenuItem(button) {
    const container = document.getElementById('menu-items-container');
    if (!container) return;
    
    const items = container.querySelectorAll('.menu-item');
    
    if (items.length > 1) {
        const item = button.closest('.menu-item');
        if (item) {
            item.remove();
            
            // Reindexar los items
            container.querySelectorAll('.menu-item').forEach((item, index) => {
                item.setAttribute('data-index', index);
            });
            
            calculateTotal();
            updatePlatoCounter();
        }
    } else {
        alert('Debe haber al menos un plato en el menú.');
    }
}

function calculateTotal() {
    let total = 0;
    let itemCount = 0;
    
    const itemTotals = document.querySelectorAll('.item-total');
    itemTotals.forEach(span => {
        const itemTotal = parseFloat(span.textContent) || 0;
        total += itemTotal;
        itemCount++;
    });
    
    // Calcular precio promedio
    const precioPromedio = itemCount > 0 ? total / itemCount : 0;
    
    // Actualizar displays
    const totalMenuElement = document.getElementById('total-menu');
    const contadorPlatosElement = document.getElementById('contador-platos');
    const precioPromedioElement = document.getElementById('precio-promedio');
    const totalEventoInput = document.getElementById('total-evento-input');
    
    if (totalMenuElement) {
        totalMenuElement.textContent = '$' + total.toFixed(2);
    }
    if (contadorPlatosElement) {
        contadorPlatosElement.textContent = itemCount;
    }
    if (precioPromedioElement) {
        precioPromedioElement.textContent = '$' + precioPromedio.toFixed(2);
    }
    if (totalEventoInput) {
        totalEventoInput.value = total.toFixed(2);
    }
    
    return total;
}

function updatePlatoCounter() {
    const platoSelects = document.querySelectorAll('.plato-select');
    let platosValidos = 0;
    
    platoSelects.forEach(select => {
        if (select.value) platosValidos++;
    });
    
    const contadorPlatosElement = document.getElementById('contador-platos');
    if (contadorPlatosElement) {
        contadorPlatosElement.textContent = platosValidos;
    }
}

// Validación del formulario
function validarFormulario() {
    const startSelect = document.getElementById('hora_inicio');
    const endSelect = document.getElementById('hora_fin');
    const totalEvento = document.querySelector('input[name="total_evento"]');
    
    // Validar horario
    if (!startSelect || !endSelect || !startSelect.value || !endSelect.value) {
        alert("Por favor, seleccione tanto la hora de inicio como la de finalización.");
        return false;
    }
    
    const [h1, m1] = startSelect.value.split(':').map(Number);
    const [h2, m2] = endSelect.value.split(':').map(Number);
    
    const inicioMins = (h1 * 60) + m1;
    const finMins = (h2 * 60) + m2;
    
    if (finMins <= inicioMins) {
        alert("La hora de finalización debe ser mayor a la hora de inicio.");
        return false;
    }
    
    if ((finMins - inicioMins) < 30) {
        alert("La duración mínima del evento debe ser de 30 minutos.");
        return false;
    }
    
    // Validar que haya al menos un plato en el menú
    const platoSelects = document.querySelectorAll('.plato-select');
    let hasAtLeastOnePlato = false;
    platoSelects.forEach(select => {
        if (select.value) hasAtLeastOnePlato = true;
    });
    
    if (!hasAtLeastOnePlato) {
        alert("Debe seleccionar al menos un plato para el menú.");
        return false;
    }
    
    // Validar que todas las cantidades sean válidas
    const cantidadInputs = document.querySelectorAll('.cantidad-input');
    for (let input of cantidadInputs) {
        if (!input.value || parseFloat(input.value) < 1) {
            alert("Por favor, ingrese una cantidad válida (mínimo 1) para todos los platos.");
            input.focus();
            return false;
        }
    }
    
    // Validar total del evento
    if (totalEvento && parseFloat(totalEvento.value) < 0) {
        alert("El total del evento no puede ser negativo.");
        return false;
    }
    
    return true;
}

// Función auxiliar para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Calcular total inicial después de que la página cargue
if (typeof document !== 'undefined') {
    setTimeout(() => {
        calculateTotal();
    }, 100);
}

// Hacer las funciones disponibles globalmente
window.initializeEventoEditor = initializeEventoEditor;
window.showTab = showTab;
window.addMenuItem = addMenuItem;
window.updatePlatoInfo = updatePlatoInfo;
window.calculateItemTotal = calculateItemTotal;
window.removeMenuItem = removeMenuItem;
window.calculateTotal = calculateTotal;
window.updatePlatoCounter = updatePlatoCounter;
window.validarFormulario = validarFormulario;