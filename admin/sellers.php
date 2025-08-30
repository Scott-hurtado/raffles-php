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
    'sold_tickets' => 680
];

// Datos de ejemplo para vendedores (en producción vendría de la base de datos)
$sellers_data = [
    [
        'id' => 1,
        'name' => 'Juan Pérez',
        'email' => 'juan@email.com',
        'phone' => '+52 662 123 4567',
        'tickets_sold' => 85,
        'commission_rate' => 10.00,
        'total_sales' => 4250.00,
        'commission_earned' => 425.00,
        'status' => 'active',
        'joined_date' => '2024-12-01'
    ],
    [
        'id' => 2,
        'name' => 'María García',
        'email' => 'maria@email.com',
        'phone' => '+52 662 987 6543',
        'tickets_sold' => 120,
        'commission_rate' => 12.00,
        'total_sales' => 6000.00,
        'commission_earned' => 720.00,
        'status' => 'active',
        'joined_date' => '2024-11-15'
    ],
    [
        'id' => 3,
        'name' => 'Carlos López',
        'email' => 'carlos@email.com',
        'phone' => '+52 662 456 7890',
        'tickets_sold' => 45,
        'commission_rate' => 8.00,
        'total_sales' => 2250.00,
        'commission_earned' => 180.00,
        'status' => 'inactive',
        'joined_date' => '2024-10-20'
    ],
    [
        'id' => 4,
        'name' => 'Ana Martínez',
        'email' => 'ana@email.com',
        'phone' => '+52 662 321 0987',
        'tickets_sold' => 95,
        'commission_rate' => 11.00,
        'total_sales' => 4750.00,
        'commission_earned' => 522.50,
        'status' => 'active',
        'joined_date' => '2024-12-10'
    ]
];

$total_sellers = count($sellers_data);
$active_sellers = count(array_filter($sellers_data, fn($s) => $s['status'] === 'active'));
$total_commission = array_sum(array_column($sellers_data, 'commission_earned'));
$total_sales_by_sellers = array_sum(array_column($sellers_data, 'total_sales'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendedores - <?php echo htmlspecialchars($rifa_info['name']); ?> - Rifas Online</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/admin/sellers.css">
</head>
<body>
    <div class="sellers-container">
        <div class="header">
            <div class="header-top">
                <div>
                    <div class="breadcrumb">
                        <a href="panel.php">Panel</a>
                        <span>→</span>
                        <span>Vendedores</span>
                    </div>
                    <h1 class="header-title">Gestión de Vendedores</h1>
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
                    <span>Precio: $<?php echo number_format($rifa_info['ticket_price'], 2); ?></span>
                    <span>Vendidos: <?php echo number_format($rifa_info['sold_tickets']); ?>/<?php echo number_format($rifa_info['total_tickets']); ?></span>
                    <span>Sorteo: <?php echo date('d/m/Y', strtotime($rifa_info['draw_date'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="content">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon icon-sellers">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <path d="M20 8v6"/>
                                <path d="M23 11h-6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $total_sellers; ?></div>
                    <div class="stat-label">Total Vendedores</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon icon-active">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $active_sellers; ?></div>
                    <div class="stat-label">Vendedores Activos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon icon-commission">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number">$<?php echo number_format($total_commission, 2); ?></div>
                    <div class="stat-label">Comisiones Totales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon icon-sales">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number">$<?php echo number_format($total_sales_by_sellers, 2); ?></div>
                    <div class="stat-label">Ventas por Vendedores</div>
                </div>
            </div>
            
            <!-- Tabla de Vendedores -->
            <div class="main-content">
                <div class="content-header">
                    <h2 class="content-title">Lista de Vendedores</h2>
                    <button class="add-seller-btn" onclick="addSeller()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6"/>
                            <path d="M23 11h-6"/>
                        </svg>
                        Agregar Vendedor
                    </button>
                </div>
                
                <table class="sellers-table">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Rendimiento</th>
                            <th>Comisión</th>
                            <th>Estado</th>
                            <th>Fecha de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers_data as $seller): ?>
                        <tr>
                            <td>
                                <div class="seller-info">
                                    <div class="seller-avatar">
                                        <?php echo strtoupper(substr($seller['name'], 0, 1)); ?>
                                    </div>
                                    <div class="seller-details">
                                        <div class="seller-name"><?php echo htmlspecialchars($seller['name']); ?></div>
                                        <div class="seller-contact">
                                            <?php echo htmlspecialchars($seller['email']); ?><br>
                                            <?php echo htmlspecialchars($seller['phone']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="performance-cell">
                                <div class="performance-number"><?php echo number_format($seller['tickets_sold']); ?></div>
                                <div class="performance-label">boletos vendidos</div>
                                <div style="margin-top: 0.5rem;">
                                    <div class="performance-number">$<?php echo number_format($seller['total_sales'], 2); ?></div>
                                    <div class="performance-label">ventas totales</div>
                                </div>
                            </td>
                            <td class="commission-info">
                                <div class="commission-rate"><?php echo $seller['commission_rate']; ?>%</div>
                                <div class="commission-earned">$<?php echo number_format($seller['commission_earned'], 2); ?></div>
                            </td>
                            <td>
                                <span class="seller-status status-<?php echo $seller['status']; ?>">
                                    <?php echo ucfirst($seller['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($seller['joined_date'])); ?>
                            </td>
                            <td>
                                <div class="seller-actions">
                                    <button class="action-btn btn-view" onclick="viewSeller(<?php echo $seller['id']; ?>)" title="Ver Detalles">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    
                                    <button class="action-btn btn-edit" onclick="editSeller(<?php echo $seller['id']; ?>)" title="Editar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    
                                    <button class="action-btn btn-toggle" 
                                            onclick="toggleSellerStatus(<?php echo $seller['id']; ?>, '<?php echo $seller['status']; ?>')" 
                                            title="<?php echo $seller['status'] === 'active' ? 'Desactivar' : 'Activar'; ?>">
                                        <?php if ($seller['status'] === 'active'): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                            <circle cx="12" cy="16" r="1"/>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                        </svg>
                                        <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="10" rx="2" ry="2"/>
                                            <circle cx="12" cy="16" r="1"/>
                                            <path d="M7 11V7a5 5 0 0 1 8.9-2.9l3 3"/>
                                            <path d="M2.1 13a5.002 5.002 0 0 1 0-2"/>
                                        </svg>
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function addSeller() {
            alert('Función para agregar nuevo vendedor - Por implementar');
            // Aquí irías a un modal o página para agregar vendedor
        }
        
        function viewSeller(sellerId) {
            alert(`Ver detalles del vendedor ID: ${sellerId}`);
            // Aquí mostrarías un modal con detalles del vendedor
        }
        
        function editSeller(sellerId) {
            alert(`Editar vendedor ID: ${sellerId}`);
            // Aquí irías a un modal o página para editar vendedor
        }
        
        function toggleSellerStatus(sellerId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activar' : 'desactivar';
            
            if (confirm(`¿Estás seguro de que quieres ${action} a este vendedor?`)) {
                // Aquí harías la petición AJAX para cambiar el estado
                alert(`Vendedor ${sellerId} ${action === 'activar' ? 'activado' : 'desactivado'} correctamente`);
                location.reload(); // En producción, actualizarías solo la fila
            }
        }
    </script>
</body>
</html>