<?php
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Obtener lista de clientes para el selector
$clientes_query = $conn->query("SELECT id, cliente_nombre, cliente_apellido, razon_social, identificacion FROM clientes ORDER BY cliente_nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Generar Invitación | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .header-step {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--azul-quito);
            margin-bottom: 20px;
            border-bottom: 2px solid #f4f4f4;
            padding-bottom: 10px;
        }

        /* Ajuste para que Select2 combine con tu diseño */
        .select2-container--default .select2-selection--single {
            height: 45px;
            border: 1px solid #ddd;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }

        .grid-campos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .campo-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo-group label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .campo-group input,
        .campo-group select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn-regresar {
            text-decoration: none;
            color: var(--azul-quito);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .btn-generar {
            background: var(--azul-quito);
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-bar" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="dashboard.php" class="btn-regresar">
                <i class="fas fa-arrow-left"></i> Regresar al Dashboard
            </a>
            <span class="user-name">Módulo de Reservas Rápidas</span>
        </header>

        <div class="content-body">
            <div class="form-container">
                <form action="crear_token.php" method="POST">
                    <div class="header-step">
                        <i class="fas fa-search"></i>
                        <h2>Paso 1: Buscar y Seleccionar Cliente</h2>
                    </div>

                    <div class="campo-group" style="margin-bottom: 30px; width: 100%;">
                        <label>Escribe el nombre o RUC del cliente:</label>
                        <select name="id_cliente" id="buscador_cliente" style="width: 100%;" required>
                            <option value="">Escribe para buscar...</option>
                            <?php while ($c = $clientes_query->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['cliente_nombre'] . " " . $c['cliente_apellido'] . " | " . $c['identificacion'] . " (" . ($c['razon_social'] ?: 'Particular') . ")") ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="header-step">
                        <i class="fas fa-calendar-day"></i>
                        <h2>Paso 2: Detalles del Evento</h2>
                    </div>

                    <div class="grid-campos">
                        <div class="campo-group">
                            <label>Tipo de Evento</label>
                            <select name="id_tipo_evento" required>
                                <option value="">Seleccione...</option>
                                <?php
                                $tipos = $conn->query("SELECT * FROM tipos_evento ORDER BY nombre");
                                while ($t = $tipos->fetch_assoc()) {
                                    echo "<option value='{$t['id']}'>" . htmlspecialchars($t['nombre']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="campo-group">
                            <label>Fecha del Evento</label>
                            <input type="date" name="fecha_evento" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="campo-group">
                            <label>Cantidad de Personas (Pax)</label>
                            <input type="number" name="cantidad_personas" min="1" max="1000" value="50" required>
                        </div>
                        <div class="campo-group">
                            <label>Nota Interna</label>
                            <input type="text" name="nota" placeholder="Ej: Confirmar salón principal">
                        </div>
                    </div>

                    <button type="submit" class="btn-generar">
                        <i class="fas fa-magic"></i> Generar Invitación
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar el buscador inteligente
            $('#buscador_cliente').select2({
                placeholder: "Busca por nombre, RUC o empresa...",
                allowClear: true,
                language: {
                    noResults: function () { return "No se encontró al cliente. Regístralo en el Directorio."; }
                }
            });
        });
    </script>
</body>

</html>