
const visor = document.getElementById('visor-imagen-fixed');
const placeholder = document.getElementById('texto-placeholder');
const nombrePreview = document.getElementById('nombre-preview');

function mostrarPreview(url, nombre) {
    if (url !== "") {
        visor.src = url;
        visor.style.display = "block";
        nombrePreview.innerText = "Vista: " + nombre;
        nombrePreview.style.display = "block";
        placeholder.style.display = "none";
    }
}

function ocultarPreview() {
    // Si quieres que la imagen se quede hasta que pases por otra, comenta estas líneas:
    /*
    visor.style.display = "none";
    nombrePreview.style.display = "none";
    placeholder.style.display = "block";
    */
}

function seleccionarCard(elemento) {
    const radio = elemento.querySelector('input[type="radio"]');
    radio.checked = true;
    document.querySelectorAll('.opcion-card').forEach(c => c.classList.remove('seleccionada'));
    elemento.classList.add('seleccionada');
}
