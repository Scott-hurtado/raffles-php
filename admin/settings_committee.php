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

// Datos de ejemplo para la rifa (en producción vendría de la base de datos)
$rifa_info = [
    'id' => $rifa_id,
    'name' => 'iPhone 15 Pro Max',
    'draw_date' => '2025-02-15',
    'ticket_price' => 50.00,
    'total_tickets' => 1000,
    'sold_tickets' => 680,
    'status' => 'active',
    'commission_rate' => 10.00,
    'auto_draw' => true,
    'notifications_enabled' => true,
    'allow_partial_sales' => false,
    'max_tickets_per_user' => 50
];

// Configuraciones disponibles para el committee
$committee_permissions = [
    'change_status' => true,
    'modify_commission' => true,
    'manage_notifications' => true,
    'view_reports' => true,
    'manage_sellers' => true,
    'modify_draw_date' => false, // Solo admin
    'change_prize' => false, // Solo admin
    'delete_raffle' => false // Solo admin
];
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
        
        .icon-notifications {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .icon-advanced {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
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
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
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
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
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
                    <span>Precio: $<?php echo number_format($rifa_info['ticket_price'], 2); ?></span>
                    <span>Sorteo: <?php echo date('d/m/Y', strtotime($rifa_info['draw_date'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="content">
            <form id="settingsForm" onsubmit="saveSettings(event)">
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
                                        <input type="checkbox" name="auto_draw" <?php echo $rifa_info['auto_draw'] ? 'checked' : ''; ?>>
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
                                        <input type="checkbox" name="allow_partial_sales" <?php echo $rifa_info['allow_partial_sales'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Máximo de Boletos por Usuario</label>
                                <div class="input-group">
                                    <input type="number" name="max_tickets_per_user" class="form-input" style="width: 120px;" value="<?php echo $rifa_info['max_tickets_per_user']; ?>" min="1" max="1000">
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
                                <p class="section-description">Comisiones y configuración de vendedores</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
                            <?php if ($committee_permissions['modify_commission']): ?>
                            <div class="form-group">
                                <label class="form-label">Comisión de Vendedores</label>
                                <div class="input-group">
                                    <input type="number" name="commission_rate" class="form-input" style="width: 100px;" value="<?php echo $rifa_info['commission_rate']; ?>" min="0" max="50" step="0.1">
                                    <span class="input-addon">%</span>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 16v-4"/>
                                    <path d="M12 8h.01"/>
                                </svg>
                                <span>La modificación de comisiones requiere permisos de administrador.</span>
                            </div>
                            <?php endif; ?>
                            
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
                    
                    <!-- Configuración de Notificaciones -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon icon-notifications">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="section-title">Notificaciones</h2>
                                <p class="section-description">Configurar alertas y notificaciones</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Notificaciones por Email</div>
                                    <div class="setting-description">Recibir alertas importantes por correo electrónico</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="email_notifications" <?php echo $rifa_info['notifications_enabled'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Notificaciones Push</div>
                                    <div class="setting-description">Alertas en tiempo real en el navegador</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="push_notifications">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Frecuencia de Reportes</label>
                                <select name="report_frequency" class="form-select">
                                    <option value="daily">Diario</option>
                                    <option value="weekly" selected>Semanal</option>
                                    <option value="monthly">Mensual</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración Avanzada -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon icon-advanced">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="section-title">Configuración Avanzada</h2>
                                <p class="section-description">Opciones adicionales y herramientas</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
                            <div class="alert alert-warning">
                                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                </svg>
                                <span>Estas configuraciones pueden afectar el funcionamiento de la rifa. Úsalas con precaución.</span>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-label">Modo de Depuración</div>
                                    <div class="setting-description">Activar logs detallados para diagnóstico</div>
                                </div>
                                <div class="setting-control">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="debug_mode">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Zona Horaria</label>
                                <select name="timezone" class="form-select">
                                    <option value="America/Mexico_City" selected>México (GMT-6)</option>
                                    <option value="America/New_York">Estados Unidos Este (GMT-5)</option>
                                    <option value="America/Los_Angeles">Estados Unidos Oeste (GMT-8)</option>
                                    <option value="Europe/Madrid">España (GMT+1)</option>
                                </select>
                            </div>
                            
                            <div class="button-group">
                                <button type="button" class="btn btn-secondary" onclick="exportData()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7,10 12,15 17,10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                    Exportar Datos
                                </button>
                                
                                <button type="button" class="btn btn-danger" onclick="confirmReset()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="1,4 1,10 7,10"/>
                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                    </svg>
                                    Reiniciar Configuración
                                </button>
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
        function saveSettings(event) {
            event.preventDefault();
            
            // Mostrar loading
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div style="display: flex; align-items: center; gap: 0.5rem;"><div style="width: 16px; height: 16px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>Guardando...</div>';
            submitBtn.disabled = true;
            
            // Simular guardado (aquí harías la petición AJAX real)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Mostrar mensaje de éxito
                showSuccessMessage('Configuración guardada correctamente');
            }, 2000);
        }
        
        function exportData() {
            if (confirm('¿Deseas exportar todos los datos de esta rifa?')) {
                alert('Función de exportación - Por implementar\n\nSe descargaría un archivo CSV con todos los datos.');
            }
        }
        
        function confirmReset() {
            if (confirm('⚠️ ¿Estás seguro de que quieres reiniciar toda la configuración?\n\nEsta acción NO se puede deshacer.')) {
                alert('Configuración reiniciada a valores por defecto');
                location.reload();
            }
        }
        
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
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>