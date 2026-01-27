<?php


$host = "localhost";
$usuario = "root"; 
$password = "";     
$base_datos = "db_eventos";

$conn = new mysqli($host, $usuario, $password, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión fatal: " . $conn->connect_error);
}

$conn->set_charset("utf8");


?>