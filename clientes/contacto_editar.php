<?php
include '../php/conexion.php';
session_start();

// 1. Verificación de Seguridad
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Validar que exista un ID válido en la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: contactos.php");
    exit();
}

$id_cliente = $conn->real_escape_string($_GET['id']);

// 3. Obtener los datos actuales del cliente para llenar el formulario
$cliente_query = $conn->query("SELECT * FROM clientes WHERE id = '$id_cliente'");
if ($cliente_query->num_rows == 0) {
    header("Location: contactos.php");
    exit();
}
$c = $cliente_query->fetch_assoc();

// 4. Obtener usuarios administradores para el selector
$usuarios = $conn->query("SELECT id, usuario FROM admin_usuarios ORDER BY usuario ASC");

// 5. Procesar la actualización al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_actualizar'])) {
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

    $sql = "UPDATE clientes SET 
            cliente_nombre = '$nombre', 
            cliente_apellido = '$apellido', 
            razon_social = '$empresa', 
            identificacion = '$identificacion', 
            cliente_email = '$email', 
            cliente_telefono = '$telefono', 
            ciudad = '$ciudad', 
            pais = '$pais', 
            direccion_fiscal = '$direccion', 
            id_usuario_creador = '$id_usuario'
            WHERE id = '$id_cliente'";

    if ($conn->query($sql)) {
        // Registro en Bitácora (Logs)
        $id_admin = $_SESSION['admin_id'] ?? 0;
        $descLog = "Se actualizó la ficha del cliente: $nombre $apellido (ID: $id_cliente)";
        $stmtLog = $conn->prepare("INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) VALUES (?, 'EDITAR', 'clientes', ?, ?)");
        $stmtLog->bind_param("iis", $id_admin, $id_cliente, $descLog);
        $stmtLog->execute();

        // REDIRECCIÓN A LA FICHA DEL CONTACTO
        header("Location: contacto_perfil.php?id=" . $id_cliente . "&msj=updated");
        exit();
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente | GO Quito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
        :root {

            --dorado-quito: #d4af37;
        }

        .form-container {
            background: white;
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--azul-quito);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group input:focus {
            border-color: var(--dorado-quito);
            outline: none;
        }

        /* Botón Regresar superior */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .btn-back:hover {
            color: var(--azul-quito);
        }

        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #666;
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            flex: 1;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-update {
            background: var(--dorado-quito);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            flex: 2;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-update:hover {
            background: #b38f00;
            transform: translateY(-2px);
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-bar">
            <span class="user-name">Directorio > Editar Información</span>
        </header>

        <div class="content-body">
            <div class="form-container">

                <h2
                    style="margin-bottom: 25px; color: var(--azul-quito); border-bottom: 2px solid #f4f4f4; padding-bottom: 10px;">
                    <i class="fas fa-user-edit"></i> Editando:
                    <?= htmlspecialchars($c['cliente_nombre'] . " " . $c['cliente_apellido']) ?>
                </h2>

                <?php if (isset($error)): ?>
                    <div class="alert-error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($c['cliente_nombre']) ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Apellido *</label>
                            <input type="text" name="apellido" value="<?= htmlspecialchars($c['cliente_apellido']) ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label>RUC / Cédula *</label>
                            <input type="text" name="identificacion"
                                value="<?= htmlspecialchars($c['identificacion']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Razón Social / Empresa</label>
                            <input type="text" name="empresa" value="<?= htmlspecialchars($c['razon_social']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Correo Electrónico *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($c['cliente_email']) ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" value="<?= htmlspecialchars($c['cliente_telefono']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Ciudad</label>
                            <input type="text" name="ciudad" value="<?= htmlspecialchars($c['ciudad']) ?>">
                        </div>
                        <div class="form-group">
                            <label>País</label>
                            <input type="text" name="pais" value="<?= htmlspecialchars($c['pais']) ?>">
                        </div>

                        <div class="form-group full-width">
                            <label>Dirección</label>
                            <textarea name="direccion"
                                rows="2"><?= htmlspecialchars($c['direccion_fiscal']) ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label>Usuario Responsable</label>
                            <select name="id_usuario" required>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($c['id_usuario_creador'] == $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['usuario']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="contacto_perfil.php?id=<?= $id_cliente ?>" class="btn-cancel">Descartar Cambios</a>
                        <button type="submit" name="btn_actualizar" class="btn-update">
                            <i class="fas fa-save"></i> Guardar y Ver Ficha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>