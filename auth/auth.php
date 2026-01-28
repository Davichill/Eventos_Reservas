<?php
include '../php/conexion.php';
session_start();

/**
 * 1. MANEJO DE SESIÓN Y REDIRECCIÓN
 * Si es una petición normal (GET), protegemos el login.
 * Si es un intento de acceso (POST), limpiamos la sesión previa para permitir el nuevo login.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si ya había alguien logueado, limpiamos sus datos para procesar las nuevas credenciales
    if (isset($_SESSION['admin'])) {
        session_unset();
    }
} else {
    // Si no es POST y ya está logueado, lo mandamos al dashboard
    if (isset($_SESSION['admin'])) {
        header("Location: ../admin/dashboard.php");
        exit();
    }
    // Si no es POST y no está logueado, lo regresamos al formulario
    header("Location: ../auth/login.php");
    exit();
}

/**
 * 2. RECOLECCIÓN DE DATOS
 */
$user = trim($_POST['usuario'] ?? '');
$pass = trim($_POST['password'] ?? '');

if (empty($user) || empty($pass)) {
    echo "<script>alert('Por favor, complete todos los campos'); window.location.href='login.php';</script>";
    exit();
}

/**
 * 3. CONSULTA A BASE DE DATOS
 */
$sql = "SELECT id, usuario, nombre_completo, password, tipo, activo FROM admin_usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<script>alert('Error del sistema. Intente más tarde.'); window.location.href='login.php';</script>";
    exit();
}

$stmt->bind_param("s", $user);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    // Verificar si el usuario está activo
    if ($usuario['activo'] != 1) {
        echo "<script>alert('Usuario inactivo. Contacte al administrador.'); window.location.href='login.php';</script>";
        exit();
    }

    /**
     * 4. VERIFICACIÓN DE CONTRASEÑA
     */
    $login_exitoso = false;

    // Intento 1: password_verify (recomendado para hashes)
    if (password_verify($pass, $usuario['password'])) {
        $login_exitoso = true;
    }
    // Intento 2: Texto plano (solo para cuentas antiguas en migración)
    else if ($pass === $usuario['password']) {
        $login_exitoso = true;
        // Opcional: Forzar cambio o marcar para hashear en el siguiente login
        $_SESSION['password_needs_update'] = true;
    }

    /**
     * 5. ESTABLECIMIENTO DE SESIÓN
     */
    if ($login_exitoso) {
        // Regenerar ID de sesión por seguridad tras login exitoso
        session_regenerate_id(true);

        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $usuario['id'];
        $_SESSION['admin_usuario'] = $usuario['usuario'];
        $_SESSION['admin_nombre'] = $usuario['nombre_completo'];
        $_SESSION['admin_tipo'] = $usuario['tipo'];

        // Determinar si es administrador principal
        $_SESSION['es_principal'] = ($usuario['id'] == 1 && $usuario['tipo'] == 'principal');

        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos'); window.location.href='login.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Usuario o contraseña incorrectos'); window.location.href='login.php';</script>";
    exit();
}

$stmt->close();
$conn->close();
?>