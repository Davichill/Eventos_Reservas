document.getElementById('contenedor-almuerzo-seminario').addEventListener('mouseover', function(e) {
    const item = e.target.closest('.item-seminario');
    if (item) {
        const url = item.getAttribute('data-img');
        const nombre = item.querySelector('span').innerText;
        document.getElementById('img-preview-seminario').src = url;
        document.getElementById('txt-preview-seminario').innerText = nombre;
    }
});

const contSeminario = document.getElementById('contenedor-almuerzo-seminario');
const limiteSeminario = 3; // 1 Entrada + 1 Fuerte + 1 Postre

function activarSeminario() {
    contSeminario.style.opacity = "1";
    contSeminario.style.pointerEvents = "auto";
    const checks = contSeminario.querySelectorAll('input[type="checkbox"]');
    checks.forEach(c => {
        c.checked = false;
        c.disabled = false;
        c.closest('.item-seminario').classList.remove('disabled');
    });
}

contSeminario.addEventListener('change', function (e) {
    if (e.target.type !== 'checkbox') return;

    const totalChecked = contSeminario.querySelectorAll('input:checked').length;

    if (totalChecked > limiteSeminario) {
        e.target.checked = false;
        alert("El Almuerzo Ejecutivo incluye solo 1 Entrada, 1 Fuerte y 1 Postre.");
        return;
    }

    const grupo = e.target.dataset.group;
    const hermanos = contSeminario.querySelectorAll(`input[data-group="${grupo}"]`);

    hermanos.forEach(h => {
        if (h !== e.target) {
            if (e.target.checked) {
                h.disabled = true;
                h.closest('.item-seminario').classList.add('disabled');
            } else if (totalChecked < limiteSeminario) {
                h.disabled = false;
                h.closest('.item-seminario').classList.remove('disabled');
            }
        }
    });

    const todos = contSeminario.querySelectorAll('input[type="checkbox"]');
    if (contSeminario.querySelectorAll('input:checked').length >= limiteSeminario) {
        todos.forEach(t => {
            if (!t.checked) {
                t.disabled = true;
                t.closest('.item-seminario').classList.add('disabled');
            }
        });
    } else {
        todos.forEach(t => {
            const g = t.dataset.group;
            const haySeleccion = contSeminario.querySelector(`input[data-group="${g}"]:checked`);
            if (!haySeleccion) {
                t.disabled = false;
                t.closest('.item-seminario').classList.remove('disabled');
            }
        });
    }
});
