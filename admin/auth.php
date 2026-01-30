<?php
include '../php/conexion.php';
session_start();

// Verificar si ya está logueado
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$user = trim($_POST['usuario'] ?? '');
$pass = trim($_POST['password'] ?? '');

if (empty($user) || empty($pass)) {
    echo "<script>alert('Por favor, complete todos los campos'); window.location.href='login.php';</script>";
    exit();
}

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
    
    // Verificar contraseña con soporte para hash y texto plano (transición)
    $login_exitoso = false;
    
    // 1. Primero intentar con password_verify (si está hasheada)
    if (password_verify($pass, $usuario['password'])) {
        $login_exitoso = true;
    }
    // 2. Si no, verificar como texto plano (para migración)
    else if ($pass === $usuario['password']) {
        $login_exitoso = true;
        // Marcar que necesita actualizar su contraseña
        $_SESSION['password_needs_update'] = true;
    }
    
    if ($login_exitoso) {
        // Configurar sesión
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $usuario['id'];
        $_SESSION['admin_usuario'] = $usuario['usuario'];
        $_SESSION['admin_nombre'] = $usuario['nombre_completo'];
        $_SESSION['admin_tipo'] = $usuario['tipo'];
        
        // Determinar si es administrador principal
        $_SESSION['es_principal'] = ($usuario['id'] == 1 && $usuario['tipo'] == 'principal');
        
        // Redirigir
        header("Location: dashboard.php");
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