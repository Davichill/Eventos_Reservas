<?php
include '../php/conexion.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva Invitación - GO Quito</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/nueva_invitacion.css">
    <script>
        function validarFormulario() {
            // Obtener valores de los campos
            const clienteNombre = document.querySelector('input[name="cliente_nombre"]').value.trim();
            const clienteIdentificacion = document.querySelector('input[name="cliente_identificacion"]').value.trim();
            const clienteTelefono = document.querySelector('input[name="cliente_telefono"]').value.trim();
            const clienteEmail = document.querySelector('input[name="cliente_email"]').value.trim();
            const idTipoEvento = document.querySelector('select[name="id_tipo_evento"]').value;
            const cantidadPersonas = document.querySelector('input[name="cantidad_personas"]').value;
            const fechaEvento = document.querySelector('input[name="fecha_evento"]').value;
            
            // Validar nombre
            if (clienteNombre.length < 3) {
                alert('El nombre debe tener al menos 3 caracteres');
                return false;
            }
            
            // Validar identificación (solo números, 10-13 dígitos)
            const regexIdentificacion = /^\d{10,13}$/;
            if (!regexIdentificacion.test(clienteIdentificacion)) {
                alert('La identificación debe tener entre 10 y 13 dígitos numéricos');
                return false;
            }
            
            // Validar teléfono (solo números, 10 dígitos para Ecuador)
            const regexTelefono = /^09\d{8}$/;
            if (!regexTelefono.test(clienteTelefono)) {
                alert('El teléfono debe tener 10 dígitos y comenzar con 09');
                return false;
            }
            
            // Validar email
            const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regexEmail.test(clienteEmail)) {
                alert('Por favor ingrese un correo electrónico válido');
                return false;
            }
            
            // Validar tipo de evento
            if (idTipoEvento === '') {
                alert('Por favor seleccione un tipo de evento');
                return false;
            }
            
            // Validar cantidad de personas
            if (cantidadPersonas < 1 || cantidadPersonas > 1000) {
                alert('La cantidad de personas debe estar entre 1 y 1000');
                return false;
            }
            
            // Validar fecha (no puede ser anterior a hoy)
            const hoy = new Date().toISOString().split('T')[0];
            if (fechaEvento < hoy) {
                alert('La fecha del evento no puede ser anterior a hoy');
                return false;
            }
            
            // Validar que la fecha no sea más de 2 años en el futuro
            const fechaMaxima = new Date();
            fechaMaxima.setFullYear(fechaMaxima.getFullYear() + 2);
            const fechaMaximaStr = fechaMaxima.toISOString().split('T')[0];
            
            if (fechaEvento > fechaMaximaStr) {
                alert('La fecha del evento no puede ser más de 2 años en el futuro');
                return false;
            }
            
            return true;
        }
        
        // Validación en tiempo real para teléfono
        function validarTelefono(input) {
            const valor = input.value.replace(/\D/g, ''); // Remover no números
            if (valor.length > 10) {
                input.value = valor.substring(0, 10);
            } else {
                input.value = valor;
            }
        }
        
        // Validación en tiempo real para identificación
        function validarIdentificacion(input) {
            const valor = input.value.replace(/\D/g, ''); // Remover no números
            if (valor.length > 13) {
                input.value = valor.substring(0, 13);
            } else {
                input.value = valor;
            }
        }
        
        // Validación en tiempo real para cantidad de personas
        function validarCantidadPersonas(input) {
            let valor = parseInt(input.value) || 1;
            if (valor < 1) valor = 1;
            if (valor > 1000) valor = 1000;
            input.value = valor;
        }
        
        // Establecer fecha mínima (hoy) y máxima (2 años desde hoy)
        window.onload = function() {
            const hoy = new Date().toISOString().split('T')[0];
            const fechaMaxima = new Date();
            fechaMaxima.setFullYear(fechaMaxima.getFullYear() + 2);
            const fechaMaximaStr = fechaMaxima.toISOString().split('T')[0];
            
            const fechaInput = document.querySelector('input[name="fecha_evento"]');
            fechaInput.min = hoy;
            fechaInput.max = fechaMaximaStr;
        }
    </script>
</head>

<body>
    <header>
        <h1>Generar Enlace de Confirmación</h1>
        <a href="dashboard.php" class="btn-login" style="color: #ff7675; text-decoration: none; font-size: 13px; font-weight: bold;">Volver al Panel</a>
    </header>

    <main
        style="max-width: 700px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">

        <?php if (isset($_GET['exito'])):
            $url_cliente = "http://localhost/eventos-reservas/confirmar.php?token=" . $_GET['token'];
            ?>
            <div class="success-box">
                <p style="color: #2e7d32; margin-top: 0; font-weight: bold;">✔ ¡Enlace generado con éxito!</p>
                <p style="font-size: 0.9rem;">Copia este enlace y envíalo por WhatsApp o Correo al cliente:</p>
                <input type="text" value="<?php echo htmlspecialchars($url_cliente); ?>" id="linkReserva" readonly class="input-copy">
                <button onclick="copyLink()" class="btn-copy">Copiar Enlace</button>
            </div>

            <script>
                function copyLink() {
                    var copyText = document.getElementById("linkReserva");
                    copyText.select();
                    copyText.setSelectionRange(0, 99999); // Para dispositivos móviles
                    document.execCommand("copy");
                    alert("Enlace copiado al portapapeles correctamente.");
                }
            </script>
        <?php endif; ?>

        <form action="crear_token.php" method="POST" onsubmit="return validarFormulario()">
            <section>
                <h2>Datos de Contacto</h2>

                <div class="campo-flex" style="margin-bottom: 20px;">
                    <label>Nombre del Cliente / Empresa:</label>
                    <input type="text" name="cliente_nombre" placeholder="Ej: Juan Pérez o Empresa XYZ" required
                        minlength="3" maxlength="100">
                </div>

                <div class="seccion-flex">
                    <div class="campo-flex">
                        <label>Identificación (Cédula o RUC):</label>
                        <input type="text" name="cliente_identificacion" placeholder="Ej: 1722334455" required
                            autocomplete="off" pattern="\d{10,13}" title="10-13 dígitos numéricos"
                            oninput="validarIdentificacion(this)">
                    </div>
                    <div class="campo-flex">
                        <label>Número de Celular:</label>
                        <input type="tel" name="cliente_telefono" placeholder="Ej: 0999099004" required
                            pattern="09\d{8}" title="10 dígitos, comenzando con 09"
                            oninput="validarTelefono(this)">
                    </div>
                </div>

                <div class="campo-flex" style="margin-top: 15px;">
                    <label>Correo Electrónico:</label>
                    <input type="email" name="cliente_email" placeholder="ejemplo@correo.com" required
                        maxlength="100">
                </div>

                <h2>Detalles del Evento</h2>

                <div class="campo-flex" style="margin-bottom: 20px;">
                    <label>Tipo de Evento:</label>
                    <select name="id_tipo_evento" required>
                        <option value="">Seleccione el tipo...</option>
                        <?php
                        $res = $conn->query("SELECT * FROM tipos_evento ORDER BY nombre");
                        while ($row = $res->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="seccion-flex">
                    <div class="campo-flex">
                        <label>Número de Personas (Pax):</label>
                        <input type="number" name="cantidad_personas" min="1" max="1000" required
                            value="1" oninput="validarCantidadPersonas(this)">
                    </div>
                    <div class="campo-flex">
                        <label>Fecha del Evento:</label>
                        <input type="date" name="fecha_evento" required>
                    </div>
                </div>
            </section>

            <button type="submit" class="btn-generar-premium"
                style="width: 100%; margin-top: 30px; padding: 15px; background: #1a237e; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                Generar Invitación y Link
            </button>
        </form>
    </main>
</body>

</html>