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
    $sql = "SELECT r.*, rc.ticket_price as committee_ticket_price, rc.commission_rate as committee_commission_rate
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.committee_id = ? AND rc.is_active = 1
            WHERE r.id = ?";
    $rifa_info = fetchOne($sql, [$current_admin['id'], $rifa_id]);
    
    if (!$rifa_info) {
        header('Location: panel.php');
        exit();
    }
    
    // Determinar precios actuales (del comité si existen, sino los originales)
    $current_ticket_price = $rifa_info['committee_ticket_price'] ?? $rifa_info['ticket_price'];
    $current_commission_rate = $rifa_info['committee_commission_rate'] ?? $rifa_info['commission_rate'];
    
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

        // Iniciar transacción
        $pdo = getDB();
        $pdo->beginTransaction();

        try {
            // IMPORTANTE: NO modificamos la tabla raffles, solo raffle_committee
            // La tabla raffles mantiene los precios originales del admin
            
            // Verificar si ya existe un registro en raffle_committee para esta rifa y comité
            $committee_check = fetchOne(
                "SELECT id FROM raffle_committee WHERE raffle_id = ? AND committee_id = ? AND is_active = 1",
                [$rifa_id, $current_admin['id']]
            );

            if ($committee_check) {
                // Actualizar registro existente en raffle_committee
                $update_committee_sql = "UPDATE raffle_committee SET 
                                        ticket_price = ?, 
                                        commission_rate = ?, 
                                        original_price = ?, 
                                        updated_at = NOW() 
                                        WHERE id = ?";
                
                executeQuery($update_committee_sql, [
                    $ticket_price,
                    $commission_rate,
                    $rifa_info['ticket_price'], // Precio original del admin
                    $committee_check['id']
                ]);
            } else {
                // Crear nuevo registro en raffle_committee
                $insert_committee_sql = "INSERT INTO raffle_committee 
                                        (raffle_id, committee_id, ticket_price, commission_rate, original_price, is_active, created_at, updated_at) 
                                        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())";
                
                executeQuery($insert_committee_sql, [
                    $rifa_id,
                    $current_admin['id'],
                    $ticket_price,
                    $commission_rate,
                    $rifa_info['ticket_price'] // Precio original del admin
                ]);
            }

            // Confirmar transacción
            $pdo->commit();

            // Actualizar variables locales para mostrar los cambios en la interfaz
            $current_ticket_price = $ticket_price;
            $current_commission_rate = $commission_rate;
            $rifa_info['committee_ticket_price'] = $ticket_price;
            $rifa_info['committee_commission_rate'] = $commission_rate;
            
            logAdminActivity('update_committee_pricing', "Actualizó precios del comité para rifa: {$rifa_info['name']} - Precio: $ticket_price, Comisión: {$commission_rate}% (Originales: {$rifa_info['ticket_price']}, {$rifa_info['commission_rate']}%)");
            
            $success_message = 'Configuración del comité actualizada correctamente. Los precios originales del admin se mantienen inalterados.';

        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error al actualizar configuración del comité: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Comité - <?php echo htmlspecialchars($rifa_info['name']); ?> - Rifas Online</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/admin/settings_committee.css">
</head>
<body>
    <div class="settings-container">
        <div class="header">
            <div class="header-top">
                <div>
                    <div class="breadcrumb">
                        <a href="panel.php">Panel</a>
                        <span>→</span>
                        <span>Configuración del Comité</span>
                    </div>
                    <h1 class="header-title">Configuración Independiente del Comité</h1>
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

            <div class="independence-notice">
                🔄 Sistema de Precios Independientes: Los cambios del comité NO afectan los precios originales del admin
            </div>

            <div class="schema-info">
                <h5>📊 Información del Sistema</h5>
                <p><strong>Tabla "raffles":</strong> Mantiene precios originales del admin intactos • <strong>Tabla "raffle_committee":</strong> Almacena solo tus personalizaciones</p>
            </div>

            <!-- Comparación de Precios -->
            <div class="price-comparison">
                <h4>💰 Comparación de Precios</h4>
                <div class="comparison-row">
                    <span class="comparison-label">Precio Original (Admin):</span>
                    <span class="comparison-value original-price">$<?php echo number_format($rifa_info['ticket_price'], 2); ?></span>
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">Tu Precio Actual (Comité):</span>
                    <span class="comparison-value committee-price">$<?php echo number_format($current_ticket_price, 2); ?></span>
                </div>
            </div>

            <div class="commission-comparison">
                <h4>📊 Comparación de Comisiones</h4>
                <div class="comparison-row">
                    <span class="comparison-label">Comisión Original (Admin):</span>
                    <span class="comparison-value original-price"><?php echo number_format($rifa_info['commission_rate'], 1); ?>%</span>
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">Tu Comisión Actual (Comité):</span>
                    <span class="comparison-value committee-price"><?php echo number_format($current_commission_rate, 1); ?>%</span>
                </div>
            </div>

            <form id="settingsForm" method="POST">
                <input type="hidden" name="update_settings" value="1">
                
                <div class="settings-layout">
                    <!-- Configuración de Precios del Comité -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon icon-sales">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="section-title">Precios del Comité</h2>
                                <p class="section-description">Configuración independiente - NO afecta los precios del admin</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
                            <div class="alert alert-info">
                                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 16v-4"/>
                                    <path d="M12 8h.01"/>
                                </svg>
                                <span>Los precios originales del admin (tabla "raffles") permanecen inalterados. Tus cambios se guardan en "raffle_committee".</span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Precio del Boleto del Comité</label>
                                <div class="input-group">
                                    <span class="input-addon">$</span>
                                    <input type="number" name="ticket_price" class="form-input" style="width: 120px;" 
                                           value="<?php echo $current_ticket_price; ?>" min="0.01" step="0.01" required>
                                </div>
                                <small style="color: #64748b; font-size: 0.8rem;">
                                    Precio original del admin: $<?php echo number_format($rifa_info['ticket_price'], 2); ?> (no se modificará)
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Comisión de Vendedores del Comité</label>
                                <div class="input-group">
                                    <input type="number" name="commission_rate" class="form-input" style="width: 100px;" 
                                           value="<?php echo $current_commission_rate; ?>" min="0" max="50" step="0.1" required>
                                    <span class="input-addon">%</span>
                                </div>
                                <small style="color: #64748b; font-size: 0.8rem;">
                                    Comisión original del admin: <?php echo number_format($rifa_info['commission_rate'], 1); ?>% (no se modificará)
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
                                <h2 class="section-title">Configuración Operativa</h2>
                                <p class="section-description">Ajustes de funcionamiento</p>
                            </div>
                        </div>
                        
                        <div class="section-content">
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

                            <!-- Información técnica -->
                            <div class="schema-info">
                                <h5>🔧 Arquitectura de Datos</h5>
                                <p><strong>raffles.ticket_price:</strong> $<?php echo number_format($rifa_info['ticket_price'], 2); ?> (inmutable por comité)</p>
                                <p><strong>raffles.commission_rate:</strong> <?php echo number_format($rifa_info['commission_rate'], 1); ?>% (inmutable por comité)</p>
                                <p><strong>raffle_committee.ticket_price:</strong> $<?php echo number_format($current_ticket_price, 2); ?> (editable)</p>
                                <p><strong>raffle_committee.commission_rate:</strong> <?php echo number_format($current_commission_rate, 1); ?>% (editable)</p>
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
                                Guardar Solo en Raffle_Committee
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Preview de cambios sin afectar precios originales
        function previewPriceChange() {
            const newPrice = document.querySelector('input[name="ticket_price"]').value;
            const commission = document.querySelector('input[name="commission_rate"]').value;
            const originalPrice = <?php echo $rifa_info['ticket_price']; ?>;
            const originalCommission = <?php echo $rifa_info['commission_rate']; ?>;
            
            if (newPrice && commission) {
                const commissionAmount = (newPrice * commission / 100).toFixed(2);
                console.log(`Precios independientes:
                    Admin (original): $${originalPrice} (${originalCommission}%)
                    Comité (nuevo): $${newPrice} (${commission}%) - Comisión: $${commissionAmount}`);
            }
        }
        
        // Actualizar preview en tiempo real
        document.querySelector('input[name="ticket_price"]').addEventListener('input', previewPriceChange);
        document.querySelector('input[name="commission_rate"]').addEventListener('input', previewPriceChange);
        
        // Confirmar cambios importantes
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const originalPrice = <?php echo $rifa_info['ticket_price']; ?>;
            const originalCommission = <?php echo $rifa_info['commission_rate']; ?>;
            const newPrice = parseFloat(document.querySelector('input[name="ticket_price"]').value);
            const newCommission = parseFloat(document.querySelector('input[name="commission_rate"]').value);
            
            let changes = [];
            changes.push(`COMITÉ - Precio: $${newPrice} (Original admin: $${originalPrice})`);
            changes.push(`COMITÉ - Comisión: ${newCommission}% (Original admin: ${originalCommission}%)`);
            
            const confirm = window.confirm(
                `¿Confirmar cambios del comité?\n\n${changes.join('\n')}\n\n✅ Los precios originales del admin NO se modificarán\n✅ Cambios se guardan solo en raffle_committee`
            );
            
            if (!confirm) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>