<?php
include '../php/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['admin_id'])) {
    $id_admin = $_SESSION['admin_id'];
    $accion = $conn->real_escape_string($_POST['accion']);
    $tabla = $conn->real_escape_string($_POST['tabla_afectada']);
    $id_reg = $conn->real_escape_string($_POST['id_registro_afectado']);
    $desc = $conn->real_escape_string($_POST['descripcion']);

    $conn->query("INSERT INTO logs_admin (id_admin, accion, tabla_afectada, id_registro_afectado, descripcion) 
                  VALUES ('$id_admin', '$accion', '$tabla', '$id_reg', '$desc')");
}
?>