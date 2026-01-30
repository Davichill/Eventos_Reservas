// Variables globales
let globalMax = 0;
let maxDisplay = 0;
const mainCont = document.getElementById('contenedor-menu-cena');
let hoverTimeout = null;
let currentHoverItem = null;
let isTransitioning = false;

// Función para contar cuántas secciones únicas hay disponibles
function contarSeccionesDisponibles() {
    const gruposUnicos = new Set();
    const checkboxes = mainCont.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        gruposUnicos.add(cb.dataset.group);
    });
    return gruposUnicos.size;
}

// Función optimizada para actualizar vista previa
function actualizarPrevisualizacion(item) {
    if (!item || item.classList.contains('disabled') || isTransitioning) return;
    
    isTransitioning = true;
    
    const img = item.getAttribute('data-img') || 'img/no-image.png';
    const nombre = item.getAttribute('data-nombre') || 'Seleccione un plato';
    const guarnicion = item.getAttribute('data-guarnicion') || '';
    const vegetales = item.getAttribute('data-vegetales') || '';
    const tiempo = item.getAttribute('data-tiempo');
    
    const previewImg = document.getElementById('preview-image');
    const previewTitle = document.getElementById('preview-title');
    const detailGuarnicion = document.getElementById('detail-guarnicion');
    const detailVegetales = document.getElementById('detail-vegetales');
    const previewDetails = document.getElementById('preview-details');
    
    // Solo hacer fade si la imagen cambia
    const currentSrc = previewImg.src;
    const newSrc = img.startsWith('http') ? img : (window.location.origin + '/' + img);
    
    if (currentSrc !== newSrc && !currentSrc.endsWith(img)) {
        previewImg.style.opacity = '0.3';
        
        setTimeout(() => {
            previewImg.src = img;
            previewImg.style.opacity = '1';
        }, 200);
    }
    
    // Actualizar texto inmediatamente
    previewTitle.textContent = nombre;
    
    // Mostrar detalles solo si es plato fuerte y tiene información
    if (tiempo === 'Plato Fuerte' && (guarnicion || vegetales)) {
        previewDetails.style.display = 'block';
        detailGuarnicion.textContent = guarnicion || '-';
        detailVegetales.textContent = vegetales || '-';
    } else {
        previewDetails.style.display = 'none';
    }
    
    setTimeout(() => {
        isTransitioning = false;
    }, 300);
}

// Función para activar el menú
function activarMenu(n) {
    const seccionesDisponibles = contarSeccionesDisponibles();
    
    // Si el usuario quiere más selecciones de las secciones disponibles, ajustamos
    const maxSelecciones = Math.min(n, seccionesDisponibles);
    
    globalMax = maxSelecciones;
    maxDisplay = n; // Guardamos lo que se muestra
    
    // Actualizar contador máximo (mostramos lo que pidió, aunque no pueda seleccionar todo)
    document.getElementById('max-selections').textContent = maxDisplay;
    
    // Si hay discrepancia, mostrar advertencia
    if (n > seccionesDisponibles) {
        console.warn(`El menú de ${n} tiempos solo permite ${seccionesDisponibles} selecciones`);
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
    
    // Resetear vista previa
    const previewImg = document.getElementById('preview-image');
    previewImg.style.opacity = '0.5';
    setTimeout(() => {
        previewImg.src = 'img/no-image.png';
        previewImg.style.opacity = '1';
        document.getElementById('preview-title').textContent = 'Seleccione un plato';
        document.getElementById('preview-details').style.display = 'none';
        document.getElementById('selected-count').textContent = '0';
    }, 150);
}

// Event listener para hover con throttling
if (mainCont) {
    mainCont.addEventListener('mouseover', function(e) {
        const item = e.target.closest('.item-cena');
        
        if (!item || item === currentHoverItem || item.classList.contains('disabled')) return;
        
        // Cancelar timeout anterior
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
        }
        
        // Actualizar inmediatamente si no hay timeout activo
        if (!hoverTimeout) {
            actualizarPrevisualizacion(item);
            currentHoverItem = item;
        }
        
        // Establecer timeout para prevenir actualizaciones rápidas
        hoverTimeout = setTimeout(() => {
            hoverTimeout = null;
        }, 50);
    });

    // Limpiar timeout al salir
    mainCont.addEventListener('mouseout', function(e) {
        const item = e.target.closest('.item-cena');
        if (item && hoverTimeout) {
            clearTimeout(hoverTimeout);
            hoverTimeout = null;
        }
    });

    // Event listener para cambios en checkboxes
    mainCont.addEventListener('change', function(e) {
        if (e.target.type !== 'checkbox') return;

        const checkbox = e.target;
        const item = checkbox.closest('.item-cena');
        const grupo = checkbox.dataset.group;
        const seleccionados = mainCont.querySelectorAll('input:checked');
        
        // Validar límite global
        if (seleccionados.length > globalMax) {
            checkbox.checked = false;
            
            // Mensaje más claro si hay discrepancia
            let mensaje = `Su plan permite máximo ${globalMax} selecciones`;
            if (maxDisplay > globalMax) {
                mensaje += ` (de ${maxDisplay} tiempos anunciados)`;
            }
            alert(mensaje + ".");
            return;
        }

        // Lógica de 1 plato por tiempo (data-group)
        const checksDelMismoGrupo = mainCont.querySelectorAll(`input[data-group="${grupo}"]`);
        
        checksDelMismoGrupo.forEach(cb => {
            const cbItem = cb.closest('.item-cena');
            if (cb !== checkbox) {
                if (checkbox.checked) {
                    cb.disabled = true;
                    cbItem.classList.add('disabled');
                } else {
                    cb.disabled = false;
                    cbItem.classList.remove('disabled');
                }
            }
        });

        // Lógica de límite global
        if (seleccionados.length >= globalMax) {
            mainCont.querySelectorAll('input[type="checkbox"]').forEach(c => {
                if (!c.checked) {
                    c.disabled = true;
                    c.closest('.item-cena').classList.add('disabled');
                }
            });
        } else {
            mainCont.querySelectorAll('input[type="checkbox"]').forEach(c => {
                const g = c.dataset.group;
                const hayMarcadoEnGrupo = mainCont.querySelector(`input[data-group="${g}"]:checked`);
                if (!hayMarcadoEnGrupo) {
                    c.disabled = false;
                    c.closest('.item-cena').classList.remove('disabled');
                }
            });
        }

        // Actualizar vista previa si se selecciona
        if (checkbox.checked) {
            actualizarPrevisualizacion(item);
        }
        
        // Actualizar contador
        document.getElementById('selected-count').textContent = seleccionados.length;
    });
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    // Asegurar que los detalles estén cerrados por defecto
    const details = document.querySelectorAll('details.seccion-maestra');
    details.forEach(detail => {
        detail.open = false;
    });
    
    // Contar secciones disponibles al cargar
    const seccionesDisponibles = mainCont ? contarSeccionesDisponibles() : 0;
    console.log(`Secciones disponibles: ${seccionesDisponibles}`);
    
    // Configurar vista previa inicial con transición suave
    const previewImg = document.getElementById('preview-image');
    if (previewImg) {
        previewImg.style.opacity = '0';
        setTimeout(() => {
            previewImg.src = 'img/no-image.png';
            previewImg.style.opacity = '1';
            if (document.getElementById('preview-title')) {
                document.getElementById('preview-title').textContent = 'Seleccione un plato';
            }
        }, 100);
    }
    
    // Prevenir doble clic en checkboxes
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('click', function(e) {
            if (this.disabled) {
                e.preventDefault();
                return false;
            }
        });
    });
});