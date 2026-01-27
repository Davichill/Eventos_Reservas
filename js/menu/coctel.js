function actualizarVisor(ruta, nombre) {
    const img = document.getElementById('img-visor');
    const txt = document.getElementById('nombre-bocado-visor');
    img.style.opacity = '0.7';
    setTimeout(() => {
        img.src = ruta;
        img.style.opacity = '1';
        txt.innerText = nombre;
    }, 100);
}

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('check-bocadito')) {
        const limit = 6;
        const seleccionados = document.querySelectorAll('.check-bocadito:checked');
        const checks = document.querySelectorAll('.check-bocadito');
        document.getElementById('count').innerText = seleccionados.length;

        checks.forEach(c => {
            if (seleccionados.length >= limit) {
                if (!c.checked) {
                    c.disabled = true;
                    c.parentElement.classList.add('disabled-check');
                }
            } else {
                c.disabled = false;
                c.parentElement.classList.remove('disabled-check');
            }
        });
    }
});

function actualizarVisor(ruta, nombre) {
    const img = document.getElementById('img-visor');
    const txt = document.getElementById('nombre-bocado-visor');
    
    img.style.opacity = '0.7';
    setTimeout(() => {
        img.src = ruta;
        img.style.opacity = '1';
        
        // Mantenemos el nombre. Si en el futuro traduces los platos, 
        // aquí podrías buscar la traducción en el objeto global de JS.
        txt.innerText = nombre;
        
        // Removemos la clase de traducción para que no intente 
        // traducir un nombre propio de plato como si fuera una llave
        txt.classList.remove('lang-txt'); 
    }, 100);
}

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('check-bocadito')) {
        const limit = 6;
        const seleccionados = document.querySelectorAll('.check-bocadito:checked');
        const checks = document.querySelectorAll('.check-bocadito');
        
        // Actualizar contador numérico
        const countElement = document.getElementById('count');
        countElement.innerText = seleccionados.length;

        // Feedback visual si llega al límite
        if (seleccionados.length === limit) {
            countElement.style.color = 'var(--primary)';
            countElement.style.fontWeight = 'bold';
        } else {
            countElement.style.color = '';
            countElement.style.fontWeight = '';
        }

        checks.forEach(c => {
            if (seleccionados.length >= limit) {
                if (!c.checked) {
                    c.disabled = true;
                    // Buscamos el label o contenedor para aplicar estilo visual de deshabilitado
                    const parent = c.closest('.checkbox-item') || c.parentElement;
                    parent.classList.add('disabled-check');
                }
            } else {
                c.disabled = false;
                const parent = c.closest('.checkbox-item') || c.parentElement;
                parent.classList.remove('disabled-check');
            }
        });
    }
});

/**
 * Función para resetear el visor al idioma actual cuando el mouse sale
 * Útil si quieres que el texto "Pase el mouse..." se traduzca al cambiar idioma
 */
function resetVisor() {
    const txt = document.getElementById('nombre-bocado-visor');
    txt.classList.add('lang-txt');
    txt.setAttribute('data-key', 'visor_instruccion');
    // Si tienes una función global de traducir, la llamas aquí:
    if (typeof traducirPagina === 'function') {
        traducirPagina(window.currentLang || 'es');
    }
}