<?php
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener usuarios para el selector "Creado por"
// Cambiamos 'usuarios' por 'admin' y verificamos los nombres de las columnas
$usuarios = $conn->query("SELECT id, usuario FROM admin_usuarios ORDER BY usuario ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_guardar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $apellido = $conn->real_escape_string($_POST['apellido']);
    $empresa = $conn->real_escape_string($_POST['empresa']);
    $identificacion = $conn->real_escape_string($_POST['identificacion']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $ciudad = $conn->real_escape_string($_POST['ciudad']);
    $pais = $conn->real_escape_string($_POST['pais']);
    $direccion = $conn->real_escape_string($_POST['direccion']);
    $id_usuario = $conn->real_escape_string($_POST['id_usuario']);

    $sql = "INSERT INTO clientes (cliente_nombre, cliente_apellido, razon_social, identificacion, cliente_email, cliente_telefono, ciudad, pais, direccion_fiscal, id_usuario_creador) 
            VALUES ('$nombre', '$apellido', '$empresa', '$identificacion', '$email', '$telefono', '$ciudad', '$pais', '$direccion', '$id_usuario')";

    if ($conn->query($sql)) {
        header("Location: contactos.php?msj=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Cliente | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        .form-container {
            background: white;
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .full-width { grid-column: span 2; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-weight: 600; color: var(--azul-quito); font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        .btn-cancel {
            background: #eee;
            color: #555;
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }
        .btn-save-full {
            background: var(--azul-quito);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            flex: 2;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-bar">
            <span class="user-name">Nuevo Registro de Cliente</span>
        </header>

        <div class="content-body">
            <div class="form-container">
                <h2 style="margin-bottom: 25px; color: var(--dorado-quito); border-bottom: 2px solid #f4f4f4; padding-bottom: 10px;">
                    <i class="fas fa-user-plus"></i> Información del Cliente
                </h2>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" required placeholder="Ej: Juan">
                        </div>
                        <div class="form-group">
                            <label>Apellido *</label>
                            <input type="text" name="apellido" required placeholder="Ej: Pérez">
                        </div>
                        <div class="form-group">
                            <label>RUC / Cédula *</label>
                            <input type="text" name="identificacion" required placeholder="17xxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Razón Social / Empresa</label>
                            <input type="text" name="empresa" placeholder="Nombre de la empresa">
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico *</label>
                            <input type="email" name="email" required placeholder="email@ejemplo.com">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" placeholder="099xxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Ciudad</label>
                            <input type="text" name="ciudad" placeholder="Quito">
                        </div>
                        <div class="form-group">
                            <label>País</label>
                            <input type="text" name="pais" placeholder="Ecuador">
                        </div>
                        <div class="form-group full-width">
                            <label>Dirección Fiscal</label>
                            <textarea name="direccion" rows="2" placeholder="Calle, Nro, Intersección..."></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Asignar Usuario Responsable</label>
                            <select name="id_usuario" required>
                                <option value="">Seleccione un usuario...</option>
                                <?php while($u = $usuarios->fetch_assoc()): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($_SESSION['admin_id'] == $u['id']) ? 'selected' : '' ?>>
                                        <?= $u['usuario'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="contactos.php" class="btn-cancel">Cancelar</a>
                        <button type="submit" name="btn_guardar" class="btn-save-full">
                            <i class="fas fa-save"></i> Guardar Cliente en Directorio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>