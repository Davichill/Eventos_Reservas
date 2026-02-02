<?php
include '../php/conexion.php';
header('Content-Type: application/json');

// Consultamos el total que se debería cobrar y lo que ya se ha cobrado
$sql = "SELECT 
            SUM(total_evento) as total_proyectado, 
            SUM(total_pagado) as total_recaudado 
        FROM reservas";

$res = $conn->query($sql);
$datos = $res->fetch_assoc();

// Calculamos el saldo pendiente para el gráfico de pie
$pendiente = $datos['total_proyectado'] - $datos['total_recaudado'];

$respuesta = [
    "labels" => ["Cobrado", "Pendiente"],
    "values" => [
        (float)$datos['total_recaudado'], 
        (float)($pendiente > 0 ? $pendiente : 0)
    ],
    "total" => (float)$datos['total_proyectado']
];

echo json_encode($respuesta);