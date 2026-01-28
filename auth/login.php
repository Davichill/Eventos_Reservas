<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login Admin - GO Quito Hotel</title>
    <link rel="stylesheet" href="../css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <!-- Reemplazar "img/logo_goquito.png" con la ruta correcta de tu logo -->
                    <img src="../img/logo_goquito.png" alt="GO Quito Hotel" class="hotel-logo">
                    <div>
                        <p class="login-subtitle">Panel Administrativo</p>
                    </div>
                </div>
            </div>

            <form action="auth.php" method="POST" class="login-form" id="loginForm">
                <div id="errorMessage" class="error-message" style="display: none;">
                    ⚠️ Credenciales incorrectas. Intente nuevamente.
                </div>

                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-input" 
                           placeholder="Ingrese su usuario" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Ingrese su contraseña" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        👁️
                    </button>
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    Acceder al Panel
                </button>
            </form>

            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> GO Quito Hotel. Todos los derechos reservados.</p>
                <p style="margin-top: 8px; font-size: 0.8rem; opacity: 0.8;">
                    Solo personal autorizado
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginButton = document.getElementById('loginButton');
            const errorMessage = document.getElementById('errorMessage');
            
            // Show loading state
            loginButton.classList.add('loading');
            loginButton.disabled = true;
            
            // Simulate processing time
            setTimeout(() => {
                // Check for empty fields
                const usuario = document.getElementById('usuario').value.trim();
                const password = document.getElementById('password').value.trim();
                
                if (!usuario || !password) {
                    errorMessage.textContent = '⚠️ Por favor complete todos los campos';
                    errorMessage.style.display = 'block';
                    loginButton.classList.remove('loading');
                    loginButton.disabled = false;
                    
                    // Hide error after 3 seconds
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 3000);
                    
                    return;
                }
            }, 300);
        });

        // Check URL for error parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error')) {
                const errorMessage = document.getElementById('errorMessage');
                errorMessage.style.display = 'block';
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
            
            // Auto-focus on username field
            document.getElementById('usuario').focus();
        });
    </script>
</body>
</html>