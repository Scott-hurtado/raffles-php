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
        
        .sellers-container {
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .icon-sellers {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .icon-active {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .icon-commission {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .icon-sales {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.3rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .content-header {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .content-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .add-seller-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .add-seller-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .sellers-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .sellers-table th,
        .sellers-table td {
            padding: 1.2rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sellers-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .sellers-table tbody tr:hover {
            background: #f1f5f9;
        }
        
        .seller-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .seller-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .seller-details {
            flex: 1;
        }
        
        .seller-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }
        
        .seller-contact {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .performance-cell {
            text-align: center;
        }
        
        .performance-number {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
        }
        
        .performance-label {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .commission-info {
            text-align: center;
        }
        
        .commission-rate {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            display: inline-block;
        }
        
        .commission-earned {
            font-weight: 600;
            color: #059669;
            font-size: 0.9rem;
        }
        
        .seller-status {
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
        
        .status-inactive {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .seller-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-view {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-toggle {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .action-btn svg {
            width: 16px;
            height: 16px;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .sellers-table {
                font-size: 0.9rem;
            }
            
            .sellers-table th,
            .sellers-table td {
                padding: 1rem 0.8rem;
            }
            
            .seller-info {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
            
            .seller-avatar {
                width: 40px;
                height: 40px;
            }
        }
    </style>
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