
let esAdminPrincipal = false;
let adminNombre = '';

// Inicializar permisos desde el HTML
document.addEventListener('DOMContentLoaded', function () {
    // Intentar obtener permisos desde data attributes
    const permElement = document.querySelector('[data-es-admin-principal]');
    if (permElement) {
        esAdminPrincipal = permElement.dataset.esAdminPrincipal === 'true';
        adminNombre = permElement.dataset.adminNombre || '';
    }

    // Fallback a variables globales
    if (typeof window.esAdminPrincipal !== 'undefined') {
        esAdminPrincipal = window.esAdminPrincipal;
    }
    if (typeof window.adminNombre !== 'undefined') {
        adminNombre = window.adminNombre;
    }

    console.log('Permisos cargados:', {
        esAdminPrincipal: esAdminPrincipal,
        nombre: adminNombre
    });
});

/**
 * Copia el enlace de la invitación al portapapeles
 * @param {Event} event - El evento del click para detener la propagación
 * @param {string} token - El token único de la reserva
 */
function copyToClipboard(event, token) {
    // 1. Evitamos que el click abra el modal de detalles de la fila
    if (event) {
        event.stopPropagation();
    }

    // 2. Construimos la URL
    const urlInvitacion = window.location.origin + "/eventos-reservas/confirmar.php?token=" + token;

    // 3. Intento de copia usando la API moderna (requiere HTTPS)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(urlInvitacion)
            .then(() => {
                mostrarAvisoCopiado("✅ ¡Link copiado!");
            })
            .catch(err => {
                console.error("Error al copiar: ", err);
                metodoCopiaFallback(urlInvitacion);
            });
    } else {
        // 4. Método de respaldo (Fallback) para servidores sin SSL/HTTPS
        metodoCopiaFallback(urlInvitacion);
    }
}

function metodoCopiaFallback(texto) {
    const textArea = document.createElement("textarea");
    textArea.value = texto;
    textArea.style.position = "fixed";
    textArea.style.left = "-9999px";
    textArea.style.top = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const exitoso = document.execCommand('copy');
        if (exitoso) {
            mostrarAvisoCopiado("✅ Link copiado");
        } else {
            alert("No se pudo copiar el link.");
        }
    } catch (err) {
        alert("Error crítico al copiar: " + err);
    }
    document.body.removeChild(textArea);
}

function mostrarAvisoCopiado(mensaje) {
    // Puedes usar un toast notification si lo prefieres
    alert(mensaje);
}

// Función para exportar PDF (cuando se implemente)
function exportarPDF(eventoId) {
    window.open('generar_pdf.php?id=' + eventoId, '_blank');
}

// Función auxiliar para formatear texto
function limpiarTexto(texto) {
    if (!texto) return null;

    const textoLimpio = texto.toString().trim();

    if (textoLimpio === '' ||
        textoLimpio.toLowerCase() === 'null' ||
        textoLimpio.toLowerCase() === 'n/a' ||
        textoLimpio.toLowerCase() === 'no especificado' ||
        textoLimpio.toLowerCase() === 'pendiente' ||
        textoLimpio.toLowerCase() === 'sin especificar' ||
        textoLimpio.toLowerCase() === 'seleccione...') {
        return null;
    }

    return textoLimpio;
}

// Función para mostrar/ocultar filtros (si es necesario)
function toggleFiltros() {
    const filtros = document.getElementById('filtrosAvanzados');
    if (filtros) {
        filtros.style.display = filtros.style.display === 'none' ? 'block' : 'none';
    }
}