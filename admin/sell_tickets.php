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

// Obtener rifas disponibles
$available_raffles = [];
try {
    $sql = "SELECT id, name, ticket_price, total_tickets, sold_tickets, status, draw_date 
            FROM raffles 
            WHERE status = 'active' 
            AND draw_date > NOW() 
            AND sold_tickets < total_tickets 
            ORDER BY draw_date ASC";
    $available_raffles = fetchAll($sql);
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
        if (!in_array($payment_method, ['cash', 'transfer'])) {
            throw new Exception('Método de pago inválido');
        }

        // Verificar disponibilidad de boletos
        $raffle = fetchOne("SELECT * FROM raffles WHERE id = ? AND status = 'active'", [$raffle_id]);
        if (!$raffle) {
            throw new Exception('La rifa seleccionada no está disponible');
        }

        $available_tickets = $raffle['total_tickets'] - $raffle['sold_tickets'];
        if ($quantity > $available_tickets) {
            throw new Exception("Solo quedan {$available_tickets} boletos disponibles");
        }

        // Calcular totales
        $unit_price = $raffle['ticket_price'];
        $total_amount = $unit_price * $quantity;
        $commission_rate = $raffle['commission_rate'] / 100;
        $commission_amount = $total_amount * $commission_rate;

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
        $pdo = getDB(); // Changed from getPDO() to getDB()
        $pdo->beginTransaction();

        try {
            // Insertar la venta
            $sql_sale = "INSERT INTO sales (
                raffle_id, seller_id, customer_name, customer_phone, customer_email,
                quantity, unit_price, total_amount, commission_amount, 
                payment_method, cash_received, change_amount, ticket_numbers, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            executeQuery($sql_sale, [
                $raffle_id,
                $current_admin['id'], 
                $customer_name,
                $customer_phone,
                $customer_email,
                $quantity,
                $unit_price,
                $total_amount,
                $commission_amount,
                $payment_method,
                $payment_method === 'cash' ? $cash_received : null,
                $change_amount,
                json_encode($ticket_numbers),
                $notes
            ]);

            // Actualizar contador de boletos vendidos
            $sql_update = "UPDATE raffles SET sold_tickets = sold_tickets + ? WHERE id = ?";
            executeQuery($sql_update, [$quantity, $raffle_id]);

            // Confirmar transacción
            $pdo->commit();

            logAdminActivity('sell_tickets', "Venta realizada: {$quantity} boletos de rifa '{$raffle['name']}'");
            
            $success_message = "¡Venta realizada exitosamente! Boletos: " . implode(', ', $ticket_numbers);
            if ($change_amount > 0) {
                $success_message .= " | Cambio: $" . number_format($change_amount, 2);
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
    <style>
        .sell-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: #f8fafc;
            min-height: 100vh;
        }

        .sell-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .sell-form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .form-header h2 {
            font-size: 1.8rem;
            margin: 0;
        }

        .form-content {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-input, .form-select {
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background: white;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .payment-option.selected {
            border-color: #10b981;
            background: #ecfdf5;
            color: #059669;
        }

        .payment-option:hover {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .payment-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .cash-section {
            display: none;
            background: #fff7ed;
            border: 2px solid #fed7aa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .cash-section.active {
            display: block;
        }

        .cash-calculation {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .summary-panel {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid #bae6fd;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .summary-title {
            color: #0369a1;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(14, 165, 233, 0.2);
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .summary-label {
            color: #0369a1;
        }

        .summary-value {
            color: #1e40af;
            font-weight: 600;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
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

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            min-width: 150px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        .change-highlight {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-top: 1rem;
            font-weight: 600;
            color: #92400e;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .sell-container {
                padding: 1rem;
            }

            .sell-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .cash-calculation {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sell-container">
        <!-- Header -->
        <div class="sell-header">
            <div class="header-info">
                <h1><i class="fas fa-shopping-cart"></i> Vender Boletos</h1>
                <p>Registra una nueva venta de boletos</p>
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
                            <select id="raffle_id" name="raffle_id" class="form-select" required>
                                <option value="">Seleccione una rifa...</option>
                                <?php foreach ($available_raffles as $raffle): ?>
                                    <?php $available = $raffle['total_tickets'] - $raffle['sold_tickets']; ?>
                                    <option value="<?php echo $raffle['id']; ?>" 
                                            data-price="<?php echo $raffle['ticket_price']; ?>"
                                            data-available="<?php echo $available; ?>">
                                        <?php echo htmlspecialchars($raffle['name']); ?> - 
                                        $<?php echo number_format($raffle['ticket_price'], 2); ?> 
                                        (<?php echo $available; ?> disponibles)
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
                            <i class="fas fa-shopping-cart"></i>
                            Procesar Venta
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

        // Selección de rifa
        document.getElementById('raffle_id').addEventListener('change', function() {
            const select = this;
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                selectedRaffle = {
                    id: option.value,
                    price: parseFloat(option.dataset.price),
                    available: parseInt(option.dataset.available)
                };
                
                // Actualizar límite de cantidad
                const quantityInput = document.getElementById('quantity');
                quantityInput.max = selectedRaffle.available;
                quantityInput.value = 1;
            } else {
                selectedRaffle = null;
            }
            
            updateSummary();
        });

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
                const subtotal = unitPrice * quantity;
                const commission = subtotal * 0.10; // 10% comisión por defecto
                
                document.getElementById('unit-price').textContent = '$' + unitPrice.toFixed(2);
                document.getElementById('quantity-display').textContent = quantity;
                document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
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