<?php
// Si la sesión aún no ha sido iniciada, la iniciamos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar la página actual para la clase 'active'
$current_page = basename($_SERVER['PHP_SELF']);

// Validar que el nombre de usuario exista para evitar el error de "null"
$nombre_admin = isset($_SESSION['admin_usuario']) ? $_SESSION['admin_usuario'] : 'Admin';
?>

<nav class="main-navbar">
    <span class="nav-label">Gestión:</span>

    <div class="nav-menu">
        <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            Reservas
        </a>

        <a href="gestion_desayunos.php"
            class="nav-link <?= ($current_page == 'gestion_desayunos.php') ? 'active' : '' ?>">
            Desayunos
        </a>

        <a href="gestion_coffee.php" class="nav-link <?= ($current_page == 'gestion_coffee.php') ? 'active' : '' ?>">
            Coffee Break
        </a>

        <a href="gestion_almuerzo.php"
            class="nav-link <?= ($current_page == 'gestion_almuerzo.php') ? 'active' : '' ?>">
            Almuerzos
        </a>

        <a href="gestion_seminario.php"
            class="nav-link <?= ($current_page == 'gestion_seminario.php') ? 'active' : '' ?>">
            Seminario
        </a>

        <a href="gestion_coctel.php" class="nav-link <?= ($current_page == 'gestion_coctel.php') ? 'active' : '' ?>">
            Coctel
        </a>

        <a href="gestion_mesas.php" class="nav-link <?= ($current_page == 'gestion_mesas.php') ? 'active' : '' ?>">
            Mesas
        </a>
    </div>
</nav>