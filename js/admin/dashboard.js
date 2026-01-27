// Variable global para los platos seleccionados
let platosSeleccionadosGlobal = [];
// Variable para almacenar los datos actuales
let datosActuales = {};

// Variables de permisos
let esAdminPrincipal = false;
let adminNombre = '';

// --- NUEVA FUNCIÓN PARA LOGS ---
function enviarLog(accion, tabla, idRegistro, descripcion) {
    const formData = new FormData();
    formData.append('accion', accion);
    formData.append('tabla_afectada', tabla);
    formData.append('id_registro_afectado', idRegistro);
    formData.append('descripcion', descripcion);

    fetch('registrar_log.php', {
        method: 'POST',
        body: formData
    }).catch(err => console.error('Error al registrar log:', err));
}

// Inicializar permisos desde el HTML
document.addEventListener('DOMContentLoaded', function () {
    const permElement = document.querySelector('[data-es-admin-principal]');
    if (permElement) {
        esAdminPrincipal = permElement.dataset.esAdminPrincipal === 'true';
        adminNombre = permElement.dataset.adminNombre || '';
    }

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

function verDetalle(d) {
    datosActuales = d;

    // Llenar modal de visualización
    document.getElementById('m-razon').innerText = d.razon_social || d.cliente_nombre || 'N/A';
    document.getElementById('m-ruc').innerText = d.identificacion || 'N/A';
    document.getElementById('m-representante').innerText = d.representante_legal || 'N/A';
    document.getElementById('m-direccion').innerText = d.direccion_fiscal || 'N/A';
    document.getElementById('m-correo-f').innerText = d.correo_facturacion || 'N/A';

    let firmaTexto = 'No especificado';
    if (d.firma_nombre && d.firma_nombre !== 'null' && d.firma_nombre !== 'NULL') {
        firmaTexto = d.firma_nombre;
        if (d.firma_identificacion && d.firma_identificacion !== 'null' && d.firma_identificacion !== 'NULL') {
            firmaTexto += " (" + d.firma_identificacion + ")";
        }
    } else if (d.cliente_nombre) {
        firmaTexto = d.cliente_nombre + " (automático)";
    }

    const firmaElement = document.getElementById('m-firma');
    if (firmaElement) firmaElement.innerText = firmaTexto;

    if (d.fecha_evento) {
        const fecha = new Date(d.fecha_evento);
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
        document.getElementById('m-fecha-evento').innerText = fechaFormateada;
    }

    document.getElementById('m-cliente').innerText = d.cliente_nombre + " (" + (d.cliente_telefono || 'N/A') + ")";
    document.getElementById('m-encargado-nom').innerText = d.contacto_evento_nombre || d.cliente_nombre || 'No especificado';
    document.getElementById('m-encargado-tel').innerText = d.contacto_evento_telefono || d.cliente_telefono || 'N/A';

    // Menú
    const menuContainer = document.getElementById('m-menu-final');
    let paquete = d.menu_opcion || "Pendiente";
    let platosRaw = d.platos_lista || "";
    let html = `<div style="font-weight:bold; color:var(--primary); margin-bottom:12px;">${paquete}</div>`;

    if (platosRaw) {
        let items = platosRaw.split('||');
        html += `<ul style="display:grid; grid-template-columns: 1fr 1fr; gap:8px 25px; list-style-type: disc; padding-left:20px;">`;
        items.forEach(item => { if (item.trim() !== "") html += `<li>${item}</li>`; });
        html += `</ul>`;
    } else {
        html += `<em>Selección de platos pendiente</em>`;
    }
    menuContainer.innerHTML = html;

    // Operativa
    document.getElementById('m-horario').innerText = (d.hora_inicio ? d.hora_inicio.substring(0, 5) : '--') + " a " + (d.hora_fin ? d.hora_fin.substring(0, 5) : '--');
    document.getElementById('m-evento-pax').innerText = d.evento + " / " + d.cantidad_personas + " Pax";
    document.getElementById('m-montaje').innerText = (d.mesa_nombre || 'N/A') + " - " + (d.manteleria || 'N/A');
    document.getElementById('m-servilleta').innerText = d.color_servilleta || "No especificado";
    document.getElementById('m-it-log').innerText = "IT: " + (d.equipos_audiovisuales || "Ninguno") + " | LOG: " + (d.logistica || "N/A");
    document.getElementById('m-observaciones').innerText = d.observaciones || "Sin restricciones";

    // Planimetría
    if (d.planimetria_url) {
        document.getElementById('m-plan-container').style.display = "block";
        document.getElementById('m-plan-link').href = "../uploads/" + d.planimetria_url;
    } else {
        document.getElementById('m-plan-container').style.display = "none";
    }

    // Control de permisos botón edición
    const editButtonContainer = document.getElementById('edit-button-container');
    const noPermisoMessage = document.getElementById('no-permiso-message');

    if (esAdminPrincipal) {
        if (editButtonContainer) editButtonContainer.style.display = 'block';
        if (noPermisoMessage) noPermisoMessage.style.display = 'none';
    } else {
        if (editButtonContainer) editButtonContainer.style.display = 'none';
        if (noPermisoMessage) noPermisoMessage.style.display = 'block';
    }

    // LOG: El admin visualizó un expediente
    enviarLog('VISUALIZAR', 'reservas', d.id, `Admin ${adminNombre} visualizó detalles de la reserva.`);

    document.getElementById('modalEvento').style.display = "block";
}

function solicitarEdicion() {
    if (!esAdminPrincipal) {
        alert("⚠️ No tienes permisos para editar expedientes.");
        return false;
    }

    // LOG: Intento de edición
    enviarLog('SOLICITUD_EDITAR', 'reservas', datosActuales.id, `Admin ${adminNombre} entró al modo edición.`);

    cerrarModal();
    abrirModalEdicion();
    return true;
}

function abrirModalEdicion() {
    const d = datosActuales;
    document.getElementById('e-id').value = d.id;
    document.getElementById('e-id-cliente').value = d.id_cliente || '';
    document.getElementById('e-id-tipo-evento').value = d.id_tipo_evento || '';
    document.getElementById('e-id-display').innerText = "#" + d.id;

    document.getElementById('e-razon').value = d.razon_social || '';
    document.getElementById('e-ruc').value = d.identificacion || '';
    document.getElementById('e-representante').value = d.representante_legal || '';
    document.getElementById('e-direccion').value = d.direccion_fiscal || '';
    document.getElementById('e-correo-f').value = d.correo_facturacion || '';

    document.getElementById('e-fecha').value = d.fecha_evento || '';
    document.getElementById('e-inicio').value = d.hora_inicio || '';
    document.getElementById('e-fin').value = d.hora_fin || '';
    document.getElementById('e-pax').value = d.cantidad_personas || '';

    document.getElementById('e-cliente-nombre').value = d.cliente_nombre || '';
    document.getElementById('e-cliente-telefono').value = d.cliente_telefono || '';
    document.getElementById('e-cliente-email').value = d.cliente_email || '';
    document.getElementById('e-encargado-nombre').value = d.contacto_evento_nombre || '';
    document.getElementById('e-encargado-telefono').value = d.contacto_evento_telefono || '';

    document.getElementById('e-it').value = d.equipos_audiovisuales || '';
    document.getElementById('e-logistica').value = d.logistica || '';
    document.getElementById('e-estado').value = d.estado || 'Pendiente';
    document.getElementById('e-observaciones').value = d.observaciones || '';
    document.getElementById('e-notas-internas').value = d.notas_internas || '';

    cargarMesas(d.id_mesa).then(() => {
        if (d.manteleria) document.getElementById('e-manteleria').value = d.manteleria;
        if (d.color_servilleta) document.getElementById('e-servilleta').value = d.color_servilleta;
    });

    platosSeleccionadosGlobal = d.platos_lista ? d.platos_lista.split('||').filter(p => p.trim() !== '') : [];
    actualizarResumenVisual();
    cargarCategorias(d.id_tipo_evento);

    document.getElementById('modalEdicion').style.display = "block";
}

// === GUARDAR CAMBIOS CON LOG ===
document.getElementById('formEdicionTotal')?.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!esAdminPrincipal) {
        alert("⚠️ No tienes permisos para guardar cambios.");
        return false;
    }

    const formData = new FormData(this);
    formData.append('platos_finales', JSON.stringify(platosSeleccionadosGlobal));
    const idReserva = document.getElementById('e-id').value;

    fetch('guardar_todo_reserva.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.text())
        .then(res => {
            if (res.trim() === 'success') {
                // LOG: Guardado exitoso
                enviarLog('EDITAR', 'reservas', idReserva, `Admin ${adminNombre} guardó cambios en el expediente.`);

                alert("¡Expediente actualizado correctamente!");
                location.reload();
            } else {
                alert("Error al guardar: " + res);
            }
        })
        .catch(error => alert("Error en la conexión: " + error));
});

// Resto de funciones auxiliares (cargarMesas, cargarCategorias, etc. se mantienen igual)
function cargarMesas(mesaSeleccionada = '') {
    return new Promise((resolve) => {
        const selectMesa = document.getElementById('e-mesa');
        fetch('backend_mesas.php?accion=listar')
            .then(r => r.json())
            .then(mesas => {
                selectMesa.innerHTML = '<option value="">-- Seleccione Mesa --</option>';
                mesas.forEach(mesa => {
                    const option = document.createElement('option');
                    option.value = mesa.id;
                    option.textContent = mesa.nombre;
                    if (mesa.id == mesaSeleccionada) option.selected = true;
                    selectMesa.appendChild(option);
                });
                resolve();
            }).catch(() => resolve());
    });
}

function cargarCategorias(idTipoEvento) {
    const selector = document.getElementById('selector-categoria');
    fetch(`backend_menu.php?accion=categorias&id_tipo=${idTipoEvento}`)
        .then(r => r.json())
        .then(data => {
            selector.innerHTML = "<option value=''>-- Seleccione Categoría --</option>";
            data.forEach(cat => {
                selector.innerHTML += `<option value="${cat.categoria}">${cat.categoria}</option>`;
            });
        });
}

function actualizarResumenVisual() {
    const container = document.getElementById('resumen-platos-actuales');
    if (container) container.innerHTML = platosSeleccionadosGlobal.length > 0 ? platosSeleccionadosGlobal.join(", ") : "Ninguno";
}

function cerrarModal() { document.getElementById('modalEvento').style.display = "none"; }
function cerrarModalEdicion() { document.getElementById('modalEdicion').style.display = "none"; }

window.onclick = function (event) {
    if (event.target == document.getElementById('modalEvento')) cerrarModal();
    if (event.target == document.getElementById('modalEdicion')) cerrarModalEdicion();
}

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
    // Usamos window.location.origin para que funcione en localhost y en producción
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
    // Aseguramos que no se vea pero que sea seleccionable
    textArea.style.position = "fixed";
    textArea.style.left = "-9999px";
    textArea.style.top = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const exitoso = document.execCommand('copy');
        if (exitoso) {
            mostrarAvisoCopiado("✅ Link copiado (Legacy Mode)");
        } else {
            alert("No se pudo copiar el link.");
        }
    } catch (err) {
        alert("Error crítico al copiar: " + err);
    }
    document.body.removeChild(textArea);
}

// Función estética para no usar solo alerts aburridos
function mostrarAvisoCopiado(mensaje) {
    // Puedes usar un alert simple o algo más elegante como un Toast
    alert(mensaje);
}