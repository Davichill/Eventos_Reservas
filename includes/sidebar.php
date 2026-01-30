<nav class="sidebar">
    <div class="sidebar-header">
        <h3 style="margin:0; color: #d4af37;">GO QUITO</h3>
        <small style="color: #fff; opacity: 0.7;">Panel Administrativo</small>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="pagina_principal.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pagina_principal.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Dashboard

            </a>
        </li>
        <li>
            <a href="contactos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'contactos.php' ? 'active' : '' ?>">
                <i class="fas fa-address-book"></i> Contactos
            </a>
        </li>
        <li>
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Eventos & Reservas
            </a>
        </li>
        <li>
            <a href="calendario_eventos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'calendario_eventos.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Calendario
            </a>
        </li>
       
    </ul>
</nav>