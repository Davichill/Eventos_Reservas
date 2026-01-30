<?php
// 1. Cargar dependencias
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin']) || !isset($_GET['id'])) {
    die("Acceso denegado");
}

$id = $conn->real_escape_string($_GET['id']);

// 2. Obtener datos de la base de datos (Consulta actualizada con JOIN a salones)
$sql = "SELECT r.*, e.nombre as evento_tipo, m.nombre as mesa_nombre, s.nombre_salon,
               c.razon_social, c.identificacion, c.cliente_nombre, c.cliente_email,
               (SELECT GROUP_CONCAT(nombre_plato SEPARATOR ' • ') 
                FROM reserva_detalles_menu WHERE id_reserva = r.id) as platos_lista
        FROM reservas r
        JOIN tipos_evento e ON r.id_tipo_evento = e.id
        JOIN clientes c ON r.id_cliente = c.id
        LEFT JOIN mesas m ON r.id_mesa = m.id
        LEFT JOIN salones s ON r.id_salon = s.id
        WHERE r.id = '$id'";

$res = $conn->query($sql);
$d = $res->fetch_assoc();

if (!$d)
    die("Reserva no encontrada");

// 3. Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Manejo del Logo
$rutaImagen = '../img/logo_goquito.png';
$base64 = '';
if (file_exists($rutaImagen)) {
    $tipoArchivo = pathinfo($rutaImagen, PATHINFO_EXTENSION);
    $datosImagen = file_get_contents($rutaImagen);
    $base64 = 'data:image/' . $tipoArchivo . ';base64,' . base64_encode($datosImagen);
}

ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #001f3f;
            padding-bottom: 10px;
        }

        .section {
            background: #001f3f;
            color: white;
            padding: 4px 10px;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #eee;
            text-align: left;
        }

        .label {
            font-weight: bold;
            width: 25%;
            background-color: #f9f9f9;
            color: #001f3f;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <?php if ($base64): ?>
            <img src="<?php echo $base64; ?>" style="width: 140px;">
        <?php endif; ?>
        <div style="font-size: 14px; font-weight: bold; margin-top: 5px;">ORDEN DE SERVICIO #<?= $d['id'] ?></div>
    </div>

    <div class="section">INFORMACIÓN DEL CLIENTE</div>
    <table>
        <tr>
            <td class="label">Razón Social:</td>
            <td><?= htmlspecialchars($d['razon_social'] ?: $d['cliente_nombre']) ?></td>
            <td class="label">RUC / Cédula:</td>
            <td><?= htmlspecialchars($d['identificacion']) ?></td>
        </tr>
        <tr>
            <td class="label">Contratante:</td>
            <td><?= htmlspecialchars($d['cliente_nombre']) ?></td>
            <td class="label">Email:</td>
            <td><?= htmlspecialchars($d['cliente_email']) ?></td>
        </tr>
    </table>

    <div class="section">DETALLES DEL EVENTO Y UBICACIÓN</div>
    <table>
        <tr>
            <td class="label">Salón Asignado:</td>
            <td colspan="3" style="font-size: 13px; font-weight: bold; color: #001f3f;">
                <?= htmlspecialchars($d['nombre_salon'] ?: 'Sin asignar') ?>
            </td>
        </tr>
        <tr>
            <td class="label">Fecha del Evento:</td>
            <td><?= date("d/m/Y", strtotime($d['fecha_evento'])) ?></td>
            <td class="label">Asistentes:</td>
            <td><?= $d['cantidad_personas'] ?> Pax</td>
        </tr>
        <tr>
            <td class="label">Horario:</td>
            <td><?= substr($d['hora_inicio'], 0, 5) ?> a <?= substr($d['hora_fin'], 0, 5) ?></td>
            <td class="label">Tipo de Evento:</td>
            <td><?= htmlspecialchars($d['evento_tipo']) ?></td>
        </tr>
    </table>

    <div class="section">ALIMENTOS Y BEBIDAS</div>
    <table>
        <tr>
            <td class="label">Menú Seleccionado:</td>
            <td><?= htmlspecialchars($d['platos_lista'] ?: 'No especificado') ?></td>
        </tr>
        <tr>
            <td class="label">Observaciones:</td>
            <td><?= nl2br(htmlspecialchars($d['observaciones'] ?: 'Ninguna')) ?></td>
        </tr>
    </table>

    <div class="section">MONTAJE Y LOGÍSTICA</div>
    <table>
        <tr>
            <td class="label">Estilo de Mesa:</td>
            <td><?= htmlspecialchars($d['mesa_nombre'] ?: 'Por definir') ?></td>
            <td class="label">Mantelería:</td>
            <td><?= htmlspecialchars($d['manteleria']) ?></td>
        </tr>
        <tr>
            <td class="label">Color Servilleta:</td>
            <td><?= htmlspecialchars($d['color_servilleta']) ?></td>
            <td class="label">Audiovisuales:</td>
            <td><?= htmlspecialchars($d['equipos_audiovisuales'] ?: 'Estándar') ?></td>
        </tr>
        <tr>
            <td class="label">Logística adicional:</td>
            <td colspan="3"><?= nl2br(htmlspecialchars($d['logistica'] ?: 'N/A')) ?></td>
        </tr>
    </table>

    <div class="footer">
        GO Quito Hotel - Av. Eloy Alfaro y Catalina Aldaz - Quito, Ecuador | Generado el: <?= date("d/m/Y H:i") ?>
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

if (ob_get_length())
    ob_end_clean();

$dompdf->stream("Orden_Servicio_" . $id . ".pdf", array("Attachment" => 0)); // 0 para abrir en navegador, 1 para descargar
exit();
?>