<?php
// registrar_pago.php
ob_start();
include '../php/conexion.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Verificar si es admin principal
$admin_id = $_SESSION['admin_id'];
$sql_admin = "SELECT id, usuario, nombre_completo, tipo FROM admin_usuarios WHERE id = '$admin_id'";
$res_admin = $conn->query($sql_admin);
$admin_info = $res_admin->fetch_assoc();

$es_admin_principal = ($admin_info && $admin_info['id'] == 1 && $admin_info['tipo'] == 'principal');

if (!$es_admin_principal) {
    header("Location: dashboard.php");
    exit();
}

// Obtener ID del evento
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evento_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Obtener información del evento
$sql_evento = "SELECT r.*, c.cliente_nombre, e.nombre as tipo_evento 
               FROM reservas r
               JOIN clientes c ON r.id_cliente = c.id
               JOIN tipos_evento e ON r.id_tipo_evento = e.id
               WHERE r.id = $evento_id";
$res_evento = $conn->query($sql_evento);
if (!$res_evento || $res_evento->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}
$evento = $res_evento->fetch_assoc();

// Obtener pagos existentes
$sql_pagos = "SELECT * FROM pagos_reservas WHERE id_reserva = $evento_id ORDER BY tipo_pago";
$res_pagos = $conn->query($sql_pagos);
$pagos_existentes = [];
$tipos_registrados = [];

if ($res_pagos && $res_pagos->num_rows > 0) {
    while ($pago = $res_pagos->fetch_assoc()) {
        $pagos_existentes[] = $pago;
        $tipos_registrados[] = $pago['tipo_pago'];
    }
}

// Calcular total pagado
$total_pagado = 0;
foreach ($pagos_existentes as $pago) {
    $total_pagado += $pago['monto'];
}

// Calcular saldo pendiente
$saldo_pendiente = $evento['total_evento'] - $total_pagado;

// Determinar tipo de pago sugerido
$tipo_sugerido = 'Deposito 1';
if (in_array('Deposito 1', $tipos_registrados)) {
    $tipo_sugerido = 'Deposito 2';
}
if (in_array('Deposito 2', $tipos_registrados)) {
    $tipo_sugerido = 'Saldo Final';
}
if (in_array('Saldo Final', $tipos_registrados)) {
    $tipo_sugerido = 'Adicional';
}

// Procesar formulario
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar campos
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo_pago = $conn->real_escape_string($_POST['metodo_pago'] ?? '');
    $tipo_pago = $conn->real_escape_string($_POST['tipo_pago'] ?? '');
    $referencia = $conn->real_escape_string($_POST['referencia'] ?? '');
    $notas = $conn->real_escape_string($_POST['notas'] ?? '');
    
    // Validaciones
    if ($monto <= 0) {
        $error = "El monto debe ser mayor a 0";
    } elseif ($metodo_pago == '') {
        $error = "Seleccione un método de pago";
    } elseif ($tipo_pago == '') {
        $error = "Seleccione un tipo de pago";
    } elseif ($tipo_pago != 'Adicional' && in_array($tipo_pago, $tipos_registrados)) {
        $error = "Ya existe un pago registrado de tipo '$tipo_pago'";
    } elseif ($monto > $saldo_pendiente && $tipo_pago != 'Adicional') {
        $error = "El monto excede el saldo pendiente de $" . number_format($saldo_pendiente, 2);
    } else {
        // Registrar pago
        $sql_insert = "INSERT INTO pagos_reservas 
                      (id_reserva, monto, metodo_pago, tipo_pago, referencia, notas, registrado_por) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("idssssi", $evento_id, $monto, $metodo_pago, $tipo_pago, $referencia, $notas, $admin_id);
        
        if ($stmt->execute()) {
            // Actualizar total pagado en reserva
            $nuevo_total = $total_pagado + $monto;
            $estado_pago = 'Parcial';
            
            if ($nuevo_total >= $evento['total_evento']) {
                $estado_pago = 'Pagado';
            } elseif ($nuevo_total > 0) {
                $estado_pago = 'Parcial';
            } else {
                $estado_pago = 'Pendiente';
            }
            
            $sql_update = "UPDATE reservas 
                          SET total_pagado = ?, estado_pago = ? 
                          WHERE id = ?";
            $stmt2 = $conn->prepare($sql_update);
            $stmt2->bind_param("dsi", $nuevo_total, $estado_pago, $evento_id);
            $stmt2->execute();
            
            $mensaje = "Pago registrado exitosamente";
            
            // Redireccionar después de 2 segundos
            header("refresh:2;url=detalle_evento.php?id=$evento_id");
        } else {
            $error = "Error al registrar el pago: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - GO Quito Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/gestion_menu/estilos_admin.css">
    <style>
    :root {
        --primary: #001f3f;
        --accent: #d4af37;
        --success: #27ae60;
        --danger: #e74c3c;
        --bg: #f4f7f9;
        --white: #ffffff;
    }
    
    body {
        background: var(--bg);
        font-family: 'Segoe UI', sans-serif;
        color: #333;
    }
    
    .container {
        max-width: 800px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .header {
        background: linear-gradient(135deg, var(--primary) 0%, #003366 100%);
        color: white;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 31, 63, 0.15);
    }
    
    .header h1 {
        margin: 0 0 10px 0;
        font-size: 1.8rem;
    }
    
    .header p {
        opacity: 0.9;
        margin: 0;
    }
    
    .info-card {
        background: var(--white);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .info-item {
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        background: #f8f9fa;
    }
    
    .info-item.total {
        border-top: 4px solid var(--primary);
        background: #e8f4fd;
    }
    
    .info-item.pagado {
        border-top: 4px solid var(--success);
        background: #eafaf1;
    }
    
    .info-item.pendiente {
        border-top: 4px solid var(--danger);
        background: #fdeded;
    }
    
    .info-item h3 {
        font-size: 0.9rem;
        color: #666;
        margin: 0 0 10px 0;
        text-transform: uppercase;
    }
    
    .info-item .monto {
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
    }
    
    /* Formulario */
    .form-card {
        background: var(--white);
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(214, 175, 55, 0.1);
    }
    
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 15px;
        padding-right: 45px;
    }
    
    .btn {
        padding: 14px 30px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-primary {
        background: var(--accent);
        color: var(--primary);
    }
    
    .btn-primary:hover {
        background: #c19a2d;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(214, 175, 55, 0.3);
    }
    
    .btn-secondary {
        background: #95a5a6;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #7f8c8d;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .pagos-plan {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
    }
    
    .plan-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .plan-item:last-child {
        border-bottom: none;
    }
    
    .plan-item.completado {
        color: var(--success);
    }
    
    .plan-item.pendiente {
        color: #666;
    }
    
    .plan-item .status {
        width: 24px;
        text-align: center;
    }
    
    /* Modal para registrar total */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 30px;
        width: 90%;
        max-width: 500px;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .modal-header h2 {
        margin: 0;
        color: var(--primary);
    }
    
    .close-modal {
        float: right;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    @media (max-width: 576px) {
        .container {
            padding: 15px;
            margin: 20px auto;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-check-alt"></i> Registrar Pago</h1>
            <p>Evento #<?= $evento_id ?> - <?= htmlspecialchars($evento['cliente_nombre']) ?></p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $mensaje ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <!-- Información del evento -->
        <div class="info-card">
            <h2 style="color: var(--primary); margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Información Financiera
            </h2>
            
            <div class="info-grid">
                <div class="info-item total">
                    <h3>Total Evento</h3>
                    <div class="monto">$<?= number_format($evento['total_evento'], 2) ?></div>
                </div>
                
                <div class="info-item pagado">
                    <h3>Total Pagado</h3>
                    <div class="monto">$<?= number_format($total_pagado, 2) ?></div>
                    <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                        <?= count($pagos_existentes) ?> pago(s)
                    </div>
                </div>
                
                <div class="info-item pendiente">
                    <h3>Saldo Pendiente</h3>
                    <div class="monto">$<?= number_format($saldo_pendiente, 2) ?></div>
                    <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                        Estado: <?= $evento['estado_pago'] ?>
                    </div>
                </div>
            </div>
            
            <!-- Plan de pagos 50% - 50% -->
            <div class="pagos-plan">
                <h4 style="margin-bottom: 15px; color: var(--primary);">
                    <i class="fas fa-calendar-check"></i> Plan de Pagos (50% - 50%)
                </h4>
                
                <div class="plan-item <?= in_array('Deposito 1', $tipos_registrados) ? 'completado' : 'pendiente' ?>">
                    <div>
                        <span class="status">
                            <i class="fas <?= in_array('Deposito 1', $tipos_registrados) ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        </span>
                        <span>Depósito 1 (50%)</span>
                    </div>
                    <div>$<?= number_format($evento['total_evento'] * 0.5, 2) ?></div>
                </div>
                
                <div class="plan-item <?= in_array('Deposito 2', $tipos_registrados) ? 'completado' : 'pendiente' ?>">
                    <div>
                        <span class="status">
                            <i class="fas <?= in_array('Deposito 2', $tipos_registrados) ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        </span>
                        <span>Depósito 2 / Saldo Final (50%)</span>
                    </div>
                    <div>$<?= number_format($evento['total_evento'] * 0.5, 2) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Formulario de pago -->
        <div class="form-card">
            <h2 style="color: var(--primary); margin-bottom: 25px;">
                <i class="fas fa-plus-circle"></i> Registrar Nuevo Pago
            </h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Tipo de Pago *</label>
                    <select name="tipo_pago" class="form-control" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="Deposito 1" <?= $tipo_sugerido == 'Deposito 1' ? 'selected' : '' ?> <?= in_array('Deposito 1', $tipos_registrados) ? 'disabled' : '' ?>>
                            Depósito 1 (50%)
                        </option>
                        <option value="Deposito 2" <?= $tipo_sugerido == 'Deposito 2' ? 'selected' : '' ?> <?= in_array('Deposito 2', $tipos_registrados) ? 'disabled' : '' ?>>
                            Depósito 2 / Saldo Final (50%)
                        </option>
                        <option value="Adicional" <?= $tipo_sugerido == 'Adicional' ? 'selected' : '' ?>>
                            Pago Adicional
                        </option>
                    </select>
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Esquema de pagos: 50% al confirmar, 50% antes del evento
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Monto *</label>
                    <input type="number" name="monto" class="form-control" 
                           step="0.01" min="0.01" max="<?= $saldo_pendiente ?>" 
                           placeholder="0.00" required
                           value="<?= $tipo_sugerido == 'Deposito 1' ? $evento['total_evento'] * 0.5 : '' ?>">
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Saldo disponible: $<?= number_format($saldo_pendiente, 2) ?>
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Método de Pago *</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="">Seleccionar método...</option>
                        <option value="Transferencia">Transferencia Bancaria</option>
                        <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Referencia / Comprobante</label>
                    <input type="text" name="referencia" class="form-control" 
                           placeholder="Ej: TRANSF-001, COMP-2024-001, etc.">
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Número de transferencia, comprobante o transacción
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notas Adicionales</label>
                    <textarea name="notas" class="form-control" rows="3" 
                              placeholder="Observaciones sobre este pago..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Pago
                    </button>
                    <a href="detalle_evento.php?id=<?= $evento_id ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
        
        <?php if (count($pagos_existentes) > 0): ?>
        <div class="info-card">
            <h2 style="color: var(--primary); margin-bottom: 20px;">
                <i class="fas fa-history"></i> Historial de Pagos
            </h2>
            
            <?php foreach ($pagos_existentes as $pago): ?>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #3498db;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong><?= $pago['tipo_pago'] ?></strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 5px;">
                            <i class="far fa-calendar"></i> <?= date("d/m/Y H:i", strtotime($pago['fecha_pago'])) ?>
                            | <i class="fas fa-money-check"></i> <?= $pago['metodo_pago'] ?>
                            <?php if ($pago['referencia']): ?>
                                | Ref: <?= htmlspecialchars($pago['referencia']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($pago['notas']): ?>
                            <div style="font-size: 0.85rem; color: #666; margin-top: 5px; font-style: italic;">
                                <?= nl2br(htmlspecialchars($pago['notas'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary);">
                        $<?= number_format($pago['monto'], 2) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal para registrar total del evento (solo si no tiene total) -->
    <?php if ($evento['total_evento'] == 0): ?>
    <div id="modalTotal" class="modal-overlay" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-dollar-sign"></i> Establecer Total del Evento</h2>
                <button class="close-modal" onclick="cerrarModal()">&times;</button>
            </div>
            
            <form id="formTotal" method="POST" action="actualizar_total.php">
                <input type="hidden" name="id_reserva" value="<?= $evento_id ?>">
                
                <div class="form-group">
                    <label class="form-label">Total del Evento *</label>
                    <input type="number" name="total_evento" class="form-control" 
                           step="0.01" min="0.01" placeholder="0.00" required>
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Ingrese el valor total acordado para este evento
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notas (Opcional)</label>
                    <textarea name="notas_total" class="form-control" rows="3" 
                              placeholder="Observaciones sobre el precio acordado..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Guardar Total
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    function cerrarModal() {
        document.getElementById('modalTotal').style.display = 'none';
    }
    
    // Auto-seleccionar tipo de pago sugerido
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($evento['total_evento'] == 0): ?>
            // Si no hay total, mostrar modal automáticamente
            document.getElementById('modalTotal').style.display = 'flex';
        <?php endif; ?>
        
        // Auto-cambiar monto según tipo de pago
        const tipoPagoSelect = document.querySelector('select[name="tipo_pago"]');
        const montoInput = document.querySelector('input[name="monto"]');
        const totalEvento = <?= $evento['total_evento'] ?>;
        
        tipoPagoSelect.addEventListener('change', function() {
            if (this.value === 'Deposito 1') {
                montoInput.value = (totalEvento * 0.5).toFixed(2);
                montoInput.max = totalEvento * 0.5;
            } else if (this.value === 'Deposito 2') {
                montoInput.value = (totalEvento * 0.5).toFixed(2);
                montoInput.max = totalEvento * 0.5;
            } else if (this.value === 'Adicional') {
                montoInput.value = '';
                montoInput.max = <?= $saldo_pendiente ?>;
            }
        });
    });
    </script>
</body>
</html>