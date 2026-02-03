<?php
// Obtener el nombre del archivo actual
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Definir qué páginas activan cada menú
$seccion_dashboard = ['pagina_principal.php'];
$seccion_contactos = ['contactos.php', 'contacto_nuevo.php', 'contacto_editar.php', 'contacto_perfil.php'];
$seccion_eventos   = ['dashboard.php', 'detalle_evento.php', 'editar_evento.php','gestion_almuerzo.php','gestion_coctel.php','gestion_coffee.php','gestion_desayunos.php','gestion_mesas.php','gestion_seminario.php'];
$seccion_calendario = ['calendario_eventos.php'];
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <h3 style="margin:0; color: #d4af37;">GO QUITO</h3>
        <small style="color: #fff; opacity: 0.7;">Panel Administrativo</small>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="pagina_principal.php" class="<?= in_array($pagina_actual, $seccion_dashboard) ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="contactos.php" class="<?= in_array($pagina_actual, $seccion_contactos) ? 'active' : '' ?>">
                <i class="fas fa-address-book"></i> Contactos
            </a>
        </li>

        <li>
            <a href="dashboard.php" class="<?= in_array($pagina_actual, $seccion_eventos) ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Eventos & Reservas
            </a>
        </li>
        
        <li>
            <a href="calendario_eventos.php" class="<?= in_array($pagina_actual, $seccion_calendario) ? 'active' : '' ?>">
                <i class="fas fa-calendar-day"></i> Calendario
            </a>
        </li>
    </ul>
</nav>