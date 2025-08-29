<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Obtener información del administrador actual
$current_admin = getCurrentAdmin();

// Verificar que sea committee
if ($current_admin['user_type'] !== 'committee') {
    header('Location: panel.php');
    exit();
}

// Obtener rifa_id de la URL
$rifa_id = $_GET['rifa_id'] ?? null;
if (!$rifa_id) {
    header('Location: panel.php');
    exit();
}

$success_message = '';
$error_message = '';

// Obtener información real de la rifa desde la base de datos
try {
    $sql = "SELECT * FROM raffles WHERE id = ?";
    $rifa_info = fetchOne($sql, [$rifa_id]);
    
    if (!$rifa_info) {
        header('Location: panel.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener información de la rifa: " . $e->getMessage());
    header('Location: panel.php');
    exit();
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $ticket_price = floatval($_POST['ticket_price'] ?? 0);
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        $status = $_POST['raffle_status'] ?? 'active';
        $auto_draw = isset($_POST['auto_draw']) ? 1 : 0;
        $allow_partial_sales = isset($_POST['allow_partial_sales']) ? 1 : 0;
        $max_tickets_per_user = intval($_POST['max_tickets_per_user'] ?? 50);
        
        // Validaciones
        if ($ticket_price <= 0) {
            throw new Exception('El precio del boleto debe ser mayor a 0');
        }
        
        if ($commission_rate < 0 || $commission_rate > 50) {
            throw new Exception('La comisión debe estar entre 0% y 50%');
        }
        
        if ($max_tickets_per_user < 1 || $max_tickets_per_user > 1000) {
            throw new Exception('El máximo de boletos por usuario debe estar entre 1 y 1000');
        }
        
        // Actualizar la base de datos
        $update_sql = "UPDATE raffles SET 
                        ticket_price = ?, 
                        commission_rate = ?, 
                        status = ?, 
                        updated_at = NOW() 
                       WHERE id = ?";
        
        executeQuery($update_sql, [
            $ticket_price,
            $commission_rate,
            $status,
            $rifa_id
        ]);
        
        // Actualizar la información local para mostrar los cambios
        $rifa_info['ticket_price'] = $ticket_price;
        $rifa_info['commission_rate'] = $commission_rate;
        $rifa_info['status'] = $status;
        
        logAdminActivity('update_raffle_settings', "Actualizó configuración de rifa: {$rifa_info['name']} - Precio: $ticket_price, Comisión: {$commission_rate}%");
        
        $success_message = 'Configuración actualizada correctamente';
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error al actualizar configuración: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?php echo htmlspecialchars($rifa_info['name']); ?> - Rifas Online</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .settings-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .breadcrumb a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .breadcrumb a:hover {
            opacity: 0.8;
        }
        
        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .rifa-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .rifa-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .rifa-details {
            display: flex;
            gap: 2rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .content {
            flex: 1;
            padding: 2rem;
        }
        
        .settings-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .settings-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .section-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .icon-general {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .icon-sales {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .section-description {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 0.3rem;
        }
        
        .section-content {
            padding: 2rem;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info {
            flex: 1;
        }
        
        .setting-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.3rem;
            font-size: 1rem;
        }
        
        .setting-description {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.4;
        }
        
        .setting-control {
            margin-left: 2rem;
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        /* Form Controls */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-input,
        .form-select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .input-addon {
            font-weight: 600;
            color: #64748b;
        }
        
        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8fafc;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f1f5f9;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #dcfdf7;
            color: #059669;
        }
        
        .status-paused {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-finished {
            background: #fee2e2;
            color: #dc2626;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .alert-success {
            background: #dcfdf7;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        /* Price/Commission Highlighting */
        .price-highlight {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .price-highlight h4 {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .price-highlight p {
            color: #1e40af;
            margin: 0;
        }
        
        .commission-highlight {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .commission-highlight h4 {
            color: #059669;
            margin-bottom: 0.5rem;
        }
        
        .commission-highlight p {
            color: #059669;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .settings-layout {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .content {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header-top {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .rifa-details {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .section-content {
                padding: 1.5rem;
            }
            
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .setting-control {
                margin-left: 0;
                width: 100%;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="header">
            <div class="header-top">
                <div>
                    <div class="breadcrumb">
                        <a href="panel.php">Panel</a>
                        <span>→</span>
                        <span>Configuración</span>
                    </div>
                    <h1 class="header-title">Configuración de Rifa</h1>
                </div>
                <a href="panel.php" class="back-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5"/>
                        <path d="M12 19l-7-7 7-7"/>
                    </svg>
                    Volver
                </a>
            </div>
            
            <div class="rifa-info">
                <div class="rifa-name"><?php echo htmlspecialchars($rifa_info['name']); ?></div>
                <div class="rifa-details">
                    <span>ID: #<?php echo $rifa_info['id']; ?></span>
                    <span>Estado: <span class="status-badge status-<?php echo $rifa_info['status']; ?>"><?php echo ucfirst($rifa_info['status']); ?></span></span>
                    <span>Precio Actual: $<?php echo number_format($rifa_info['ticket_price'], 2); ?></span>
                    <span>Comisión Actual: <?php echo number_format($rifa_info['commission_rate'], 1); ?>%</span>
                    <span>Sorteo: <?php echo date('d/m/Y H:i', strtotime($rifa_info['draw_date'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M15 9l-6 6"/>
                        <path d="M9 9l6 6"/>
                    </svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form id="settingsForm" method="POST">
                <input type="hidden" name="update_settings" value="1">
                
                <div class="settings-layout">
                    <!-- Configuración General -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon icon-general">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="section-title">Configuración General</h2>
                                <p class="section-description">Ajustes básicos de la rifa</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Estado de la Rifa</div>
                                    <div class="setting-description">Controla si la rifa está activa, pausada o finalizada</div>
                                </div>
                                <div class="setting-control">
                                    <select name="raffle_status" class="form-select" style="width: 150px;">
                                        <option value="active" <?php echo $rifa_info['status'] === 'active' ? 'selected' : ''; ?>>Activa</option>
                                        <option value="paused" <?php echo $rifa_info['status'] === 'paused' ? 'selected' : ''; ?>>Pausada</option>
                                        <option value="finished" <?php echo $rifa_info['status'] === 'finished' ? 'selected' : ''; ?>>Finalizada</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Sorteo Automático</div>
                                    <div class="setting-description">Ejecutar automáticamente el sorteo en la fecha programada</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="auto_draw" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Permitir Ventas Parciales</div>
                                    <div class="setting-description">Realizar sorteo aunque no se vendan todos los boletos</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="allow_partial_sales">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Máximo de Boletos por Usuario</label>
                                <div class="input-group">
                                    <input type="number" name="max_tickets_per_user" class="form-input" style="width: 120px;" value="50" min="1" max="1000">
                                    <span class="input-addon">boletos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración de Ventas -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon icon-sales">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="section-title">Configuración de Ventas</h2>
                                <p class="section-description">Precios y comisiones</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
                            <div class="price-highlight">
                                <h4>💰 Precio del Boleto</h4>
                                <p>Precio actual: $<?php echo number_format($rifa_info['ticket_price'], 2); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nuevo Precio del Boleto</label>
                                <div class="input-group">
                                    <span class="input-addon">$</span>
                                    <input type="number" name="ticket_price" class="form-input" style="width: 120px;" 
                                           value="<?php echo $rifa_info['ticket_price']; ?>" min="0.01" step="0.01" required>
                                </div>
                                <small style="color: #64748b; font-size: 0.8rem;">
                                    Este cambio se reflejará inmediatamente en todo el sistema
                                </small>
                            </div>

                            <div class="commission-highlight">
                                <h4>📊 Comisión de Vendedores</h4>
                                <p>Comisión actual: <?php echo number_format($rifa_info['commission_rate'], 1); ?>%</p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nueva Comisión de Vendedores</label>
                                <div class="input-group">
                                    <input type="number" name="commission_rate" class="form-input" style="width: 100px;" 
                                           value="<?php echo $rifa_info['commission_rate']; ?>" min="0" max="50" step="0.1" required>
                                    <span class="input-addon">%</span>
                                </div>
                                <small style="color: #64748b; font-size: 0.8rem;">
                                    Entre 0% y 50%. Los vendedores verán este cambio inmediatamente
                                </small>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Notificar Ventas a Vendedores</div>
                                    <div class="setting-description">Enviar notificaciones cuando se realice una venta</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="notify_sellers" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Reporte de Ventas Diario</div>
                                    <div class="setting-description">Generar reportes automáticos de ventas diarias</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="daily_reports" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones principales -->
                <div class="settings-section" style="margin-top: 2rem;">
                    <div class="section-content">
                        <div class="button-group">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='panel.php'">
                                Cancelar
                            </button>
                            
                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17,21 17,13 7,13 7,21"/>
                                    <polyline points="7,3 7,8 15,8"/>
                                </svg>
                                Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Preview de cambios
        function previewPriceChange() {
            const newPrice = document.querySelector('input[name="ticket_price"]').value;
            const commission = document.querySelector('input[name="commission_rate"]').value;
            
            if (newPrice && commission) {
                const commissionAmount = (newPrice * commission / 100).toFixed(2);
                console.log(`Nuevo precio: $${newPrice}, Comisión: ${commission}% ($${commissionAmount})`);
            }
        }
        
        // Actualizar preview en tiempo real
        document.querySelector('input[name="ticket_price"]').addEventListener('input', previewPriceChange);
        document.querySelector('input[name="commission_rate"]').addEventListener('input', previewPriceChange);
        
        // Confirmar cambios importantes
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const currentPrice = <?php echo $rifa_info['ticket_price']; ?>;
            const currentCommission = <?php echo $rifa_info['commission_rate']; ?>;
            const newPrice = parseFloat(document.querySelector('input[name="ticket_price"]').value);
            const newCommission = parseFloat(document.querySelector('input[name="commission_rate"]').value);
            
            let changes = [];
            if (newPrice !== currentPrice) {
                changes.push(`Precio: $${currentPrice} → $${newPrice}`);
            }
            if (newCommission !== currentCommission) {
                changes.push(`Comisión: ${currentCommission}% → ${newCommission}%`);
            }
            
            if (changes.length > 0) {
                const confirm = window.confirm(
                    `¿Estás seguro de realizar estos cambios?\n\n${changes.join('\n')}\n\nEstos cambios se aplicarán inmediatamente en todo el sistema.`
                );
                
                if (!confirm) {
                    e.preventDefault();
                }
            }
        });
        
        function showSuccessMessage(message) {
            const messageEl = document.createElement('div');
            messageEl.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
                z-index: 10000;
                font-weight: 600;
                animation: slideIn 0.3s ease;
            `;
            messageEl.textContent = message;
            
            document.body.appendChild(messageEl);
            
            setTimeout(() => {
                messageEl.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    document.body.removeChild(messageEl);
                }, 300);
            }, 3000);
        }
        
        // Añadir estilos para animaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>