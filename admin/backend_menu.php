<?php
include '../php/conexion.php';
header('Content-Type: application/json');

// Validar parámetros básicos
if (!isset($_GET['accion']) || !isset($_GET['id_tipo'])) {
    echo json_encode([]);
    exit;
}

$accion = $_GET['accion'];
$id_tipo = intval($_GET['id_tipo']);

// === MAPEO DE TABLAS ===
// Aquí definimos qué tabla y columnas usar según el tipo de evento
$tabla = "";
$col_categoria = "";     // Qué columna actúa como "Categoría Principal"
$col_subcategoria = "";  // Qué columna actúa como "Subcategoría"

switch ($id_tipo) {
    case 1: // Desayuno (Tiene 'categoria', no tiene sub)
        $tabla = "menu_desayunos";
        $col_categoria = "categoria"; 
        $col_subcategoria = "NULL"; 
        break;
    case 2: // Seminario (Tiene 'seccion' como principal, 'categoria' como sub)
        $tabla = "menu_seminario";
        $col_categoria = "seccion"; 
        $col_subcategoria = "categoria"; 
        break;
    case 3: // Coctel (Tiene 'categoria' y 'subcategoria')
        $tabla = "menu_coctel";
        $col_categoria = "categoria";
        $col_subcategoria = "subcategoria";
        break;
    case 5: // Almuerzo/Cena (Tiene 'tiempo' como principal, 'subcategoria' como sub)
        $tabla = "menu_almuerzo_cena";
        $col_categoria = "tiempo"; 
        $col_subcategoria = "subcategoria";
        break;
    case 6: // Coffee Break (Tiene 'categoria', no tiene sub)
        $tabla = "menu_coffee_break";
        $col_categoria = "categoria";
        $col_subcategoria = "NULL";
        break;
    default:
        echo json_encode([]); exit;
}

// === LÓGICA DE RESPUESTA ===

if ($accion == 'categorias') {
    // 1. Devuelve la lista para el primer Select
    $sql = "SELECT DISTINCT $col_categoria as categoria FROM $tabla WHERE $col_categoria IS NOT NULL ORDER BY $col_categoria";
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = ['categoria' => $row['categoria']];
    }
    echo json_encode($data);

} elseif ($accion == 'subcategorias') {
    // 2. Devuelve la lista para el segundo Select
    
    // Si la tabla no usa subcategorías, devolvemos array vacío para que JS cargue los platos directo
    // O devolvemos "General" si quieres que el usuario haga clic en algo.
    if ($col_subcategoria === "NULL") {
        echo json_encode([['subcategoria' => 'General']]);
        exit;
    }

    $cat = $conn->real_escape_string($_GET['categoria']);
    $sql = "SELECT DISTINCT $col_subcategoria as subcategoria FROM $tabla WHERE $col_categoria = '$cat' AND $col_subcategoria IS NOT NULL";
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = ['subcategoria' => $row['subcategoria']];
    }
    
    // Si no hay resultados (ej. cocteles a veces no tienen sub), enviamos 'General'
    if (empty($data)) {
        $data[] = ['subcategoria' => 'General'];
    }
    
    echo json_encode($data);

} elseif ($accion == 'items') {
    // 3. Devuelve los checkboxes de platos
    $cat = $conn->real_escape_string($_GET['categoria']);
    $sub = isset($_GET['subcategoria']) ? $conn->real_escape_string($_GET['subcategoria']) : '';

    $sql = "SELECT nombre FROM $tabla WHERE $col_categoria = '$cat'";

    // Solo filtramos por subcategoría si la tabla la tiene y no es el dummy 'General'
    if ($col_subcategoria !== "NULL" && $sub !== 'General' && $sub !== '') {
        $sql .= " AND $col_subcategoria = '$sub'";
    }

    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = ['nombre' => $row['nombre']];
    }
    echo json_encode($data);
}
?>