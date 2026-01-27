<?php
// 1. Cargar dependencias (Asegúrate de que la ruta a tu autoload es correcta)
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin']) || !isset($_GET['id'])) {
    die("Acceso denegado");
}

$id = $conn->real_escape_string($_GET['id']);

// 2. Obtener datos de la base de datos (Tu consulta SQL)
$sql = "SELECT r.*, e.nombre as evento_tipo, m.nombre as mesa_nombre,
               c.razon_social, c.identificacion, c.cliente_nombre, c.cliente_email,
               (SELECT GROUP_CONCAT(nombre_plato SEPARATOR ' • ') 
                FROM reserva_detalles_menu WHERE id_reserva = r.id) as platos_lista
        FROM reservas r
        JOIN tipos_evento e ON r.id_tipo_evento = e.id
        JOIN clientes c ON r.id_cliente = c.id
        LEFT JOIN mesas m ON r.id_mesa = m.id
        WHERE r.id = '$id'";

$res = $conn->query($sql);
$d = $res->fetch_assoc();

if (!$d)
    die("Reserva no encontrada");

// 3. Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Importante para cargar imágenes/logos

$dompdf = new Dompdf($options);

// Ruta física de tu logo (ajusta la carpeta según tu estructura)
$rutaImagen = '../img/logo_goquito.png';

// Convertir a Base64 para máxima compatibilidad
$tipoArchivo = pathinfo($rutaImagen, PATHINFO_EXTENSION);
$datosImagen = file_get_contents($rutaImagen);
$base64 = 'data:image/' . $tipoArchivo . ';base64,' . base64_encode($datosImagen);

// 4. Preparar el diseño HTML en una variable
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #001f3f;
            padding-bottom: 10px;
        }

        .hotel-name {
            font-size: 22px;
            font-weight: bold;
            color: #001f3f;
            margin: 0;
        }

        .doc-title {
            font-size: 16px;
            color: #555;
            margin: 5px 0;
        }

        .section {
            background: #001f3f;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #eee;
            text-align: left;
        }

        .label {
            font-weight: bold;
            width: 30%;
            background-color: #f9f9f9;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="<?php echo $base64; ?>" style="width: 150px; height: auto; margin-bottom: 10px;">
    </div>

    <div class="section">INFORMACIÓN DEL CLIENTE</div>
    <table>
        <tr>
            <td class="label">Razón Social:</td>
            <td><?= $d['razon_social'] ?: 'N/A' ?></td>
        </tr>
        <tr>
            <td class="label">RUC / Cédula:</td>
            <td><?= $d['identificacion'] ?></td>
        </tr>
        <tr>
            <td class="label">Contratante:</td>
            <td><?= $d['cliente_nombre'] ?></td>
        </tr>
        <tr>
            <td class="label">Email:</td>
            <td><?= $d['cliente_email'] ?></td>
        </tr>
    </table>

    <div class="section">DETALLES OPERATIVOS</div>
    <table>
        <tr>
            <td class="label">Fecha del Evento:</td>
            <td><?= date("d/m/Y", strtotime($d['fecha_evento'])) ?></td>
            <td class="label">Personas:</td>
            <td><?= $d['cantidad_personas'] ?> Pax</td>
        </tr>
        <tr>
            <td class="label">Horario:</td>
            <td><?= substr($d['hora_inicio'], 0, 5) ?> a <?= substr($d['hora_fin'], 0, 5) ?></td>
            <td class="label">Tipo:</td>
            <td><?= $d['evento_tipo'] ?></td>
        </tr>
    </table>

    <div class="section">ALIMENTOS Y BEBIDAS</div>
    <table>
        <tr>
            <td class="label">Menú Seleccionado:</td>
            <td><?= $d['platos_lista'] ?: 'No especificado' ?></td>
        </tr>
        <tr>
            <td class="label">Restricciones/Notas:</td>
            <td><?= $d['observaciones'] ?: 'Ninguna' ?></td>
        </tr>
    </table>

    <div class="section">MONTAJE Y EQUIPOS</div>
    <table>
        <tr>
            <td class="label">Salón/Mesa:</td>
            <td><?= $d['mesa_nombre'] ?: 'Por definir' ?></td>
        </tr>
        <tr>
            <td class="label">Mantelería:</td>
            <td>Mesa: <?= $d['manteleria'] ?> | Servilleta: <?= $d['color_servilleta'] ?></td>
        </tr>
        <tr>
            <td class="label">IT / Audiovisuales:</td>
            <td><?= $d['equipos_audiovisuales'] ?: 'Ninguno' ?></td>
        </tr>
    </table>

    <div class="footer">
        GO Quito Hotel - Av. Eloy Alfaro y Catalina Aldaz - Quito, Ecuador
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

// 5. Renderizar
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// --- CORRECCIÓN CRÍTICA AQUÍ ---
// Limpiamos cualquier salida previa (espacios, warnings de PHP) 
// para que no ensucien el binario del PDF
if (ob_get_length())
    ob_end_clean();

// Enviamos los headers correctos para la descarga
$dompdf->stream("Expediente_Evento_" . $id . ".pdf", array("Attachment" => 1));
exit(); // Importante para detener cualquier ejecución posterior
?>