document.addEventListener('mouseover', function(e) {
    const item = e.target.closest('.item-coffee');
    if (item && document.getElementById('img-preview')) {
        const url = item.getAttribute('data-img');
        const nombre = item.querySelector('span').innerText;
        document.getElementById('img-preview').src = url;
        document.getElementById('txt-preview').innerText = nombre;
    }
});

const containerCoffee = document.getElementById('contenedor-coffee');
const displayCount = document.getElementById('coffee-count');
const limitCoffee = 2;

function activarCoffee() {
    containerCoffee.style.opacity = "1";
    containerCoffee.style.pointerEvents = "auto";
    // Resetear si cambian de AM a PM
    const checks = containerCoffee.querySelectorAll('input[type="checkbox"]');
    checks.forEach(c => {
        c.checked = false;
        c.disabled = false;
        c.closest('.item-coffee').classList.remove('disabled');
    });
    displayCount.innerText = "0";
}

containerCoffee.addEventListener('change', function (e) {
    if (e.target.type !== 'checkbox') return;

    const total = containerCoffee.querySelectorAll('input:checked').length;
    displayCount.innerText = total;

    const allChecks = containerCoffee.querySelectorAll('input[type="checkbox"]');

    if (total >= limitCoffee) {
        // Bloquear los no seleccionados
        allChecks.forEach(c => {
            if (!c.checked) {
                c.disabled = true;
                c.closest('.item-coffee').classList.add('disabled');
            }
        });
    } else {
        // Desbloquear todos
        allChecks.forEach(c => {
            c.disabled = false;
            c.closest('.item-coffee').classList.remove('disabled');
        });
    }
});
