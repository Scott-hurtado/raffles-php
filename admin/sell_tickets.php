<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Obtener información del administrador actual
$current_admin = getCurrentAdmin();

// Solo vendedores pueden acceder
if ($current_admin['user_type'] !== 'seller') {
    header('Location: panel.php');
    exit();
}

// Procesar logout si se solicita
if (isset($_GET['logout'])) {
    logAdminActivity('logout', 'Cierre de sesión');
    logoutAdmin();
    header('Location: admin_login.php');
    exit();
}

$success_message = '';
$error_message = '';
$selected_raffle = null;

// Obtener ID de rifa específica si se proporciona
$selected_raffle_id = $_GET['rifa_id'] ?? null;

// Obtener rifas disponibles con precios y comisiones del comité
$available_raffles = [];
try {
    $sql = "SELECT 
                r.id, r.name, r.total_tickets, 
                COALESCE(r.sold_tickets, 0) as sold_tickets, 
                r.status, r.draw_date, r.updated_at,
                CASE 
                    WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price 
                    ELSE r.ticket_price 
                END as ticket_price,
                CASE 
                    WHEN rc.commission_rate IS NOT NULL THEN rc.commission_rate 
                    ELSE r.commission_rate 
                END as commission_rate,
                rc.original_price,
                rc.updated_at as committee_updated_at,
                CASE 
                    WHEN rc.ticket_price IS NOT NULL THEN 1
                    ELSE 0
                END as has_committee_pricing
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.is_active = 1
            WHERE r.status = 'active' 
            AND r.draw_date > NOW() 
            ORDER BY r.draw_date ASC";
    $available_raffles = fetchAll($sql);
    
    // Si hay una rifa específica seleccionada, buscarla
    if ($selected_raffle_id) {
        foreach ($available_raffles as $raffle) {
            if ($raffle['id'] == $selected_raffle_id) {
                $selected_raffle = $raffle;
                break;
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error al obtener rifas disponibles: " . $e->getMessage());
}

// Procesar la venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos del formulario
        $raffle_id = intval($_POST['raffle_id'] ?? 0);
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? '';
        $cash_received = floatval($_POST['cash_received'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Validaciones básicas
        if ($raffle_id <= 0) {
            throw new Exception('Debe seleccionar una rifa válida');
        }
        if (empty($customer_name)) {
            throw new Exception('El nombre del cliente es obligatorio');
        }
        if (empty($customer_phone)) {
            throw new Exception('El teléfono del cliente es obligatorio');
        }
        if ($quantity <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }
        if (!in_array($payment_method, ['cash', 'transfer', 'card'])) {
            throw new Exception('Método de pago inválido');
        }

        // Verificar disponibilidad de boletos y obtener precios del comité
        $raffle_query = "SELECT 
                            r.*,
                            CASE 
                                WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price 
                                ELSE r.ticket_price 
                            END as final_ticket_price,
                            CASE 
                                WHEN rc.commission_rate IS NOT NULL THEN rc.commission_rate 
                                ELSE r.commission_rate 
                            END as final_commission_rate,
                            rc.committee_id,
                            rc.original_price
                        FROM raffles r 
                        LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.is_active = 1
                        WHERE r.id = ? AND r.status = 'active'";
        
        $raffle = fetchOne($raffle_query, [$raffle_id]);
        if (!$raffle) {
            throw new Exception('La rifa seleccionada no está disponible');
        }

        $available_tickets = $raffle['total_tickets'] - $raffle['sold_tickets'];
        if ($quantity > $available_tickets) {
            throw new Exception("Solo quedan {$available_tickets} boletos disponibles");
        }

        // Calcular totales con precios del comité
        $unit_price = $raffle['final_ticket_price'];
        $total_amount = $unit_price * $quantity;
        $commission_rate = $raffle['final_commission_rate'];
        $commission_amount = $total_amount * ($commission_rate / 100);

        // Validar efectivo si es necesario
        $change_amount = 0;
        if ($payment_method === 'cash') {
            if ($cash_received < $total_amount) {
                throw new Exception('El efectivo recibido es insuficiente');
            }
            $change_amount = $cash_received - $total_amount;
        }

        // Generar números de boletos consecutivos
        $start_ticket = $raffle['sold_tickets'] + 1;
        $end_ticket = $start_ticket + $quantity - 1;
        $ticket_numbers = [];
        for ($i = $start_ticket; $i <= $end_ticket; $i++) {
            $ticket_numbers[] = str_pad($i, 6, '0', STR_PAD_LEFT);
        }

        // Iniciar transacción
        $pdo = getDB();
        $pdo->beginTransaction();

        try {
            // Insertar la venta en la tabla sells con precios del comité
            $sql_sale = "INSERT INTO sells (
                raffle_id, seller_id, customer_name, customer_phone, customer_email,
                quantity, unit_price, total_amount, commission_rate, commission_amount, 
                payment_method, cash_received, change_amount, ticket_numbers, notes, 
                committee_id, original_unit_price,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())";
            
            // Preparar y ejecutar la consulta de inserción
            $stmt = $pdo->prepare($sql_sale);
            $stmt->execute([
                $raffle_id,
                $current_admin['id'], 
                $customer_name,
                $customer_phone,
                $customer_email,
                $quantity,
                $unit_price, // Precio del comité
                $total_amount,
                $commission_rate, // Comisión del comité
                $commission_amount,
                $payment_method,
                $payment_method === 'cash' ? $cash_received : null,
                $change_amount,
                json_encode($ticket_numbers),
                $notes,
                $raffle['committee_id'], // ID del comité que estableció los precios
                $raffle['ticket_price'] // Precio original de la rifa
            ]);
            
            // Obtener el ID de la venta recién insertada
            $sale_id = $pdo->lastInsertId();

            // Actualizar contador de boletos vendidos
            $sql_update = "UPDATE raffles SET 
                          sold_tickets = sold_tickets + ?,
                          updated_at = NOW() 
                          WHERE id = ?";
            executeQuery($sql_update, [$quantity, $raffle_id]);

            // Confirmar transacción
            $pdo->commit();

            // Log de actividad con información de precios del comité
            $price_info = "";
            if ($raffle['committee_id']) {
                $price_info = " - Precio comité: $" . number_format($unit_price, 2) . 
                             " (Original: $" . number_format($raffle['ticket_price'], 2) . ")";
            }
            
            logAdminActivity('sell_tickets', "Venta #{$sale_id}: {$quantity} boletos de rifa '{$raffle['name']}' - Cliente: {$customer_name} - Total: $" . number_format($total_amount, 2) . " - Comisión: $" . number_format($commission_amount, 2) . $price_info);
            
            $success_message = "¡Venta registrada exitosamente!\n";
            $success_message .= "ID de Venta: #{$sale_id}\n";
            $success_message .= "Boletos: " . implode(', ', $ticket_numbers) . "\n";
            $success_message .= "Total: $" . number_format($total_amount, 2) . "\n";
            $success_message .= "Tu comisión: $" . number_format($commission_amount, 2);
            
            if ($raffle['committee_id']) {
                $success_message .= "\n(Precios establecidos por el comité)";
            }
            
            if ($change_amount > 0) {
                $success_message .= "\nCambio: $" . number_format($change_amount, 2);
            }

            // Actualizar la información de la rifa seleccionada
            if ($selected_raffle && $selected_raffle['id'] == $raffle_id) {
                $selected_raffle['sold_tickets'] += $quantity;
            }
            
            // Actualizar la lista de rifas disponibles
            foreach ($available_raffles as &$r) {
                if ($r['id'] == $raffle_id) {
                    $r['sold_tickets'] += $quantity;
                    break;
                }
            }

        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error en venta de boletos: " . $e->getMessage());
    }
}

// Obtener estadísticas del vendedor actual usando precios del comité
$seller_stats = [
    'total_sales' => 0,
    'total_tickets' => 0,
    'total_commission' => 0,
    'sales_this_month' => 0
];

try {
    // Total de ventas del vendedor (usando precios reales de venta)
    $stats_query = "SELECT 
                        COUNT(*) as total_sales,
                        COALESCE(SUM(quantity), 0) as total_tickets,
                        COALESCE(SUM(commission_amount), 0) as total_commission
                    FROM sells 
                    WHERE seller_id = ? AND status = 'completed'";
    
    $stats = fetchOne($stats_query, [$current_admin['id']]);
    if ($stats) {
        $seller_stats['total_sales'] = $stats['total_sales'];
        $seller_stats['total_tickets'] = $stats['total_tickets'];
        $seller_stats['total_commission'] = $stats['total_commission'];
    }

    // Ventas del mes actual
    $month_query = "SELECT COUNT(*) as sales_this_month 
                   FROM sells 
                   WHERE seller_id = ? 
                   AND status = 'completed'
                   AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                   AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    
    $month_stats = fetchOne($month_query, [$current_admin['id']]);
    if ($month_stats) {
        $seller_stats['sales_this_month'] = $month_stats['sales_this_month'];
    }

} catch (Exception $e) {
    error_log("Error al obtener estadísticas del vendedor: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vender Boletos - Panel de Vendedor</title>
    <link rel="stylesheet" href="../assets/css/admin/admin_login.css">
    <link rel="stylesheet" href="../assets/css/admin/panel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/admin/sell_tickets.css">
</head>
<body>
    <div class="sell-container">
        <!-- Header -->
        <div class="sell-header">
            <div class="header-info">
                <h1><i class="fas fa-shopping-cart"></i> Vender Boletos</h1>
                <p>Registra una nueva venta - Los precios pueden ser establecidos por el comité</p>
            </div>
            <div class="admin-info">
                <div class="admin-badge">Vendedor</div>
                <div class="admin-details">
                    <?php echo htmlspecialchars($current_admin['username']); ?><br>
                    <?php echo htmlspecialchars($current_admin['email']); ?>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="panel.php" class="btn btn-secondary" style="margin-right: 1rem;">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Panel
                    </a>
                    <a href="?logout=1" class="logout-btn" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>

        <div class="db-notice">
            📊 Los precios pueden ser personalizados por cada comité - Se registran en tabla "sells" con trazabilidad completa
        </div>

        <!-- Estadísticas del vendedor -->
        <div class="seller-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($seller_stats['total_sales']); ?></div>
                <div class="stat-label">Ventas Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($seller_stats['total_tickets']); ?></div>
                <div class="stat-label">Boletos Vendidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($seller_stats['total_commission'], 2); ?></div>
                <div class="stat-label">Comisiones Ganadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($seller_stats['sales_this_month']); ?></div>
                <div class="stat-label">Ventas Este Mes</div>
            </div>
        </div>

        <!-- Información de rifa seleccionada -->
        <?php if ($selected_raffle): ?>
        <div class="raffle-info-card">
            <h3>
                <i class="fas fa-info-circle"></i>
                Rifa Seleccionada
                <?php if (strtotime($selected_raffle['updated_at']) > strtotime('-1 hour')): ?>
                    <span class="updated-indicator">🔄 Actualizada recientemente</span>
                <?php endif; ?>
                <?php if ($selected_raffle['has_committee_pricing']): ?>
                    <span class="committee-pricing-indicator">👥 Precios del comité</span>
                <?php endif; ?>
            </h3>
            <div class="raffle-details">
                <div class="detail-item">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value"><?php echo htmlspecialchars($selected_raffle['name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Precio por Boleto</span>
                    <span class="detail-value">$<?php echo number_format($selected_raffle['ticket_price'], 2); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tu Comisión</span>
                    <span class="detail-value"><?php echo number_format($selected_raffle['commission_rate'], 1); ?>% ($<?php echo number_format($selected_raffle['ticket_price'] * ($selected_raffle['commission_rate'] / 100), 2); ?>)</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Boletos Disponibles</span>
                    <span class="detail-value"><?php echo number_format($selected_raffle['total_tickets'] - $selected_raffle['sold_tickets']); ?></span>
                </div>
            </div>
            
            <?php if ($selected_raffle['has_committee_pricing'] && $selected_raffle['original_price']): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(59, 130, 246, 0.2);">
                <p style="font-size: 0.8rem; color: #6b7280;">
                    <strong>Precio original:</strong> $<?php echo number_format($selected_raffle['original_price'], 2); ?>
                    • <strong>Este precio fue personalizado por el comité</strong>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Formulario de Venta -->
        <div class="sell-form-container">
            <div class="form-header">
                <h2><i class="fas fa-ticket-alt"></i> Información de la Venta</h2>
            </div>

            <div class="form-content">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($available_raffles)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-info-circle"></i> No hay rifas disponibles para vender en este momento.
                    </div>
                <?php else: ?>

                <form method="POST" id="sellForm">
                    <div class="form-grid">
                        <!-- Selección de Rifa -->
                        <div class="form-group full-width">
                            <label class="form-label" for="raffle_id">
                                <i class="fas fa-gift"></i> Seleccionar Rifa *
                            </label>
                            <select id="raffle_id" name="raffle_id" class="form-select" required onchange="updateRaffleInfo()">
                                <option value="">Seleccione una rifa...</option>
                                <?php foreach ($available_raffles as $raffle): ?>
                                    <?php 
                                        $available = $raffle['total_tickets'] - $raffle['sold_tickets']; 
                                        $isSelected = $selected_raffle && $selected_raffle['id'] == $raffle['id'];
                                        $recentlyUpdated = strtotime($raffle['updated_at']) > strtotime('-1 hour');
                                        $hasCommitteePricing = $raffle['has_committee_pricing'];
                                    ?>
                                    <option value="<?php echo $raffle['id']; ?>" 
                                            data-price="<?php echo $raffle['ticket_price']; ?>"
                                            data-commission="<?php echo $raffle['commission_rate']; ?>"
                                            data-available="<?php echo $available; ?>"
                                            <?php echo $isSelected ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($raffle['name']); ?> - 
                                        $<?php echo number_format($raffle['ticket_price'], 2); ?> 
                                        (<?php echo number_format($raffle['commission_rate'], 1); ?>% comisión) -
                                        <?php echo $available; ?> disponibles
                                        <?php if ($hasCommitteePricing): ?>👥<?php endif; ?>
                                        <?php if ($recentlyUpdated): ?>🔄<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Información del Cliente -->
                        <div class="form-group">
                            <label class="form-label" for="customer_name">
                                <i class="fas fa-user"></i> Nombre del Cliente *
                            </label>
                            <input type="text" 
                                   id="customer_name" 
                                   name="customer_name" 
                                   class="form-input" 
                                   placeholder="Nombre completo"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="customer_phone">
                                <i class="fas fa-phone"></i> Teléfono *
                            </label>
                            <input type="tel" 
                                   id="customer_phone" 
                                   name="customer_phone" 
                                   class="form-input" 
                                   placeholder="Número de teléfono"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="customer_email">
                                <i class="fas fa-envelope"></i> Email (Opcional)
                            </label>
                            <input type="email" 
                                   id="customer_email" 
                                   name="customer_email" 
                                   class="form-input" 
                                   placeholder="correo@ejemplo.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="quantity">
                                <i class="fas fa-hashtag"></i> Cantidad de Boletos *
                            </label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   class="form-input" 
                                   placeholder="1"
                                   min="1"
                                   required>
                        </div>

                        <!-- Método de Pago -->
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-credit-card"></i> Método de Pago *
                            </label>
                            <div class="payment-methods">
                                <div class="payment-option" onclick="selectPaymentMethod('cash')">
                                    <i class="fas fa-money-bill-wave payment-icon"></i>
                                    <div>Efectivo</div>
                                </div>
                                <div class="payment-option" onclick="selectPaymentMethod('transfer')">
                                    <i class="fas fa-university payment-icon"></i>
                                    <div>Transferencia</div>
                                </div>
                                <div class="payment-option" onclick="selectPaymentMethod('card')">
                                    <i class="fas fa-credit-card payment-icon"></i>
                                    <div>Tarjeta</div>
                                </div>
                            </div>
                            <input type="hidden" id="payment_method" name="payment_method" required>
                        </div>

                        <!-- Sección para Efectivo -->
                        <div id="cash-section" class="cash-section">
                            <h4 style="color: #ea580c; margin-bottom: 1rem;">
                                <i class="fas fa-calculator"></i> Cálculo de Cambio
                            </h4>
                            <div class="cash-calculation">
                                <div class="form-group">
                                    <label class="form-label" for="cash_received">
                                        Efectivo Recibido
                                    </label>
                                    <input type="number" 
                                           id="cash_received" 
                                           name="cash_received" 
                                           class="form-input" 
                                           step="0.01"
                                           placeholder="0.00">
                                </div>
                                <div id="change-display" class="change-highlight" style="display: none;">
                                    <div>Cambio a Entregar:</div>
                                    <div style="font-size: 1.5rem;" id="change-amount">$0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="form-group full-width">
                            <label class="form-label" for="notes">
                                <i class="fas fa-sticky-note"></i> Notas (Opcional)
                            </label>
                            <textarea id="notes" 
                                      name="notes" 
                                      class="form-input" 
                                      rows="3"
                                      placeholder="Notas adicionales sobre la venta..."></textarea>
                        </div>
                    </div>

                    <!-- Panel de Resumen -->
                    <div class="summary-panel">
                        <div class="summary-title">
                            <i class="fas fa-receipt"></i>
                            Resumen de la Venta
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Precio por Boleto:</span>
                            <span class="summary-value" id="unit-price">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Cantidad:</span>
                            <span class="summary-value" id="quantity-display">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value" id="subtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Comisión (%):</span>
                            <span class="summary-value" id="commission-rate">0%</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tu Comisión:</span>
                            <span class="summary-value" id="commission">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total:</span>
                            <span class="summary-value" id="total-amount">$0.00</span>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="form-buttons">
                        <a href="panel.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                            <i class="fas fa-database"></i>
                            Registrar Venta
                        </button>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let selectedRaffle = null;
        let paymentMethod = null;

        // Auto-seleccionar rifa si viene pre-seleccionada
        <?php if ($selected_raffle): ?>
        window.addEventListener('load', function() {
            updateRaffleInfo();
        });
        <?php endif; ?>

        // Actualizar información cuando cambia la rifa
        function updateRaffleInfo() {
            const select = document.getElementById('raffle_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                selectedRaffle = {
                    id: option.value,
                    price: parseFloat(option.dataset.price),
                    commission: parseFloat(option.dataset.commission),
                    available: parseInt(option.dataset.available)
                };
                
                // Actualizar límite de cantidad
                const quantityInput = document.getElementById('quantity');
                quantityInput.max = selectedRaffle.available;
                quantityInput.value = 1;
                
                console.log('Rifa seleccionada:', selectedRaffle);
            } else {
                selectedRaffle = null;
            }
            
            updateSummary();
        }

        // Selección de cantidad
        document.getElementById('quantity').addEventListener('input', function() {
            if (selectedRaffle && this.value > selectedRaffle.available) {
                this.value = selectedRaffle.available;
                alert(`Solo hay ${selectedRaffle.available} boletos disponibles`);
            }
            updateSummary();
        });

        // Selección de método de pago
        function selectPaymentMethod(method) {
            paymentMethod = method;
            document.getElementById('payment_method').value = method;
            
            // Actualizar UI
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.closest('.payment-option').classList.add('selected');
            
            // Mostrar/ocultar sección de efectivo
            const cashSection = document.getElementById('cash-section');
            if (method === 'cash') {
                cashSection.classList.add('active');
            } else {
                cashSection.classList.remove('active');
            }
            
            updateSummary();
        }

        // Cálculo de cambio
        document.getElementById('cash_received').addEventListener('input', function() {
            calculateChange();
        });

        function calculateChange() {
            if (!selectedRaffle || paymentMethod !== 'cash') return;
            
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const total = selectedRaffle.price * quantity;
            const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
            const change = cashReceived - total;
            
            const changeDisplay = document.getElementById('change-display');
            const changeAmount = document.getElementById('change-amount');
            
            if (cashReceived > 0) {
                changeDisplay.style.display = 'block';
                changeAmount.textContent = '$' + change.toFixed(2);
                
                if (change < 0) {
                    changeAmount.style.color = '#dc2626';
                    changeAmount.textContent = 'Insuficiente: -$' + Math.abs(change).toFixed(2);
                } else {
                    changeAmount.style.color = '#059669';
                }
            } else {
                changeDisplay.style.display = 'none';
            }
        }

        // Actualizar resumen
        function updateSummary() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            
            if (selectedRaffle && quantity > 0) {
                const unitPrice = selectedRaffle.price;
                const commissionRate = selectedRaffle.commission;
                const subtotal = unitPrice * quantity;
                const commission = subtotal * (commissionRate / 100);
                
                document.getElementById('unit-price').textContent = '$' + unitPrice.toFixed(2);
                document.getElementById('quantity-display').textContent = quantity;
                document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
                document.getElementById('commission-rate').textContent = commissionRate.toFixed(1) + '%';
                document.getElementById('commission').textContent = '$' + commission.toFixed(2);
                document.getElementById('total-amount').textContent = '$' + subtotal.toFixed(2);
                
                // Calcular cambio si es efectivo
                calculateChange();
                
                // Habilitar botón si todo está completo
                const submitBtn = document.getElementById('submit-btn');
                const customerName = document.getElementById('customer_name').value.trim();
                const customerPhone = document.getElementById('customer_phone').value.trim();
                
                if (customerName && customerPhone && paymentMethod) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            } else {
                document.getElementById('unit-price').textContent = '$0.00';
                document.getElementById('quantity-display').textContent = '0';
                document.getElementById('subtotal').textContent = '$0.00';
                document.getElementById('commission-rate').textContent = '0%';
                document.getElementById('commission').textContent = '$0.00';
                document.getElementById('total-amount').textContent = '$0.00';
                document.getElementById('submit-btn').disabled = true;
            }
        }

        // Validar formulario en tiempo real
        document.getElementById('customer_name').addEventListener('input', updateSummary);
        document.getElementById('customer_phone').addEventListener('input', updateSummary);

        // Validación antes de envío
        document.getElementById('sellForm').addEventListener('submit', function(e) {
            if (paymentMethod === 'cash') {
                const quantity = parseInt(document.getElementById('quantity').value) || 0;
                const total = selectedRaffle.price * quantity;
                const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
                
                if (cashReceived < total) {
                    e.preventDefault();
                    alert('El efectivo recibido es insuficiente para completar la venta.');
                    return;
                }
            }
        });
    </script>
</body>
</html>