<?php
$platos_info = [
    // CARNES Y ESPECIALIDADES
    'Lomo stroganoff' => [
        'guarnicion' => 'Arroz pilaf',
        'vegetales' => 'Mix de vegetales salteados de temporada'
    ],
    'Medallón de lomo' => [
        'guarnicion' => 'Mil hojas de papa',
        'vegetales' => 'Vegetales grillados de temporada'
    ],
    'Moro cremoso y churrasco' => [
        'guarnicion' => 'Moro cremoso (incluido)',
        'vegetales' => 'Ensalteado de vegetales de temporada'
    ],
    'Fritada de cerdo' => [
        'guarnicion' => 'Arroz rojo',
        'vegetales' => 'Curtido tibio de vegetales de temporada'
    ],
    'Panceta de cerdo tonkatsu' => [
        'guarnicion' => 'Arroz blanco tipo pilaf',
        'vegetales' => 'Vegetales salteados orientales de temporada'
    ],
    
    // POLLO
    'Suprema de pollo' => [
        'guarnicion' => 'Puré de papa',
        'vegetales' => 'Vegetales al vapor de temporada'
    ],
    'Roulade de pollo con puré' => [
        'guarnicion' => 'Puré de papa (incluido)',
        'vegetales' => 'Vegetales glaseados de temporada'
    ],
    'Roulade de pollo con arroz alverjado' => [
        'guarnicion' => 'Arroz alverjado (incluido)',
        'vegetales' => 'Vegetales salteados de temporada'
    ],
    'Cordon bleu de pollo' => [
        'guarnicion' => 'Cremoso de camote',
        'vegetales' => 'Vegetales al vapor de temporada'
    ],
    'Aji de pollo peruano' => [
        'guarnicion' => 'Arroz blanco',
        'vegetales' => 'Ensalada tibia de vegetales de temporada'
    ],
    'Seco de pollo' => [
        'guarnicion' => 'Arroz rojo',
        'vegetales' => 'Vegetales salteados de temporada'
    ],
    
    // MARISCOS
    'Pesca del día' => [
        'guarnicion' => 'Arroz al coco',
        'vegetales' => 'Vegetales al vapor de temporada'
    ],
    'Pescado a la florentina' => [
        'guarnicion' => 'Puré de papa',
        'vegetales' => 'Vegetales salteados de temporada'
    ],
    'Spaghetti en salsa de mariscos' => [
        'guarnicion' => '',
        'vegetales' => 'Vegetales salteados de temporada'
    ],
    
    // VEGETARIANOS
    'Albondigas de avena en salsa napolitana' => [
        'guarnicion' => 'Arroz al pesto',
        'vegetales' => 'Vegetales salteados de temporada'
    ],
    'Spaghetti al pesto' => [
        'guarnicion' => '',
        'vegetales' => 'Vegetales asados de temporada'
    ],
    'Spaghetti pomodoro' => [
        'guarnicion' => '',
        'vegetales' => 'Vegetales salteados de temporada'
    ],
    'Chaufa de vegetales' => [
        'guarnicion' => '',
        'vegetales' => 'Vegetales integrados en la preparación'
    ]
];

// Función para obtener info de un plato (con coincidencia flexible)
function obtenerInfoPlato($nombre_plato) {
    global $platos_info;
    
    // Primero intenta coincidencia exacta
    if (isset($platos_info[$nombre_plato])) {
        return $platos_info[$nombre_plato];
    }
    
    // Busca coincidencia parcial (para nombres similares)
    foreach ($platos_info as $key => $info) {
        similar_text(strtolower($nombre_plato), strtolower($key), $percentage);
        if ($percentage > 80) { // 80% de similitud
            return $info;
        }
    }
    
    return null;
}
?>