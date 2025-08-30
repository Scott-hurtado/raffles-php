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
    <title>Vendedores - <?php echo htmlspecialchars($rifa_info['name']); ?> - Panel de Committee</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #ffffff;
            color: #1f2937;
            line-height: 1.6;
        }

        /* Header Unificado */
        .main-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }

        .header-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .breadcrumb a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .raffle-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #3b82f6;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
            font-size: 0.9rem;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            border: 1px solid #7c3aed;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #6d28d9, #5b21b6);
            border-color: #6d28d9;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
            opacity: 0.8;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--card-color);
        }

        .stat-card.stat-sellers { --card-color: #3b82f6; }
        .stat-card.stat-active { --card-color: #10b981; }
        .stat-card.stat-commission { --card-color: #f59e0b; }
        .stat-card.stat-sales { --card-color: #8b5cf6; }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: var(--card-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-icon i {
            font-size: 1.2rem;
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #059669;
        }

        /* Content Section */
        .content-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fafbfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: #6b7280;
        }

        /* Tabla Moderna */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table thead th {
            background: #f9fafb;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .modern-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #f9fafb;
        }

        .modern-table tbody td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .seller-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .seller-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .seller-info h4 {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .seller-contact {
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .performance-cell {
            text-align: center;
        }

        .performance-number {
            font-weight: 700;
            color: #111827;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .performance-label {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .commission-info {
            text-align: center;
        }

        .commission-rate {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .commission-earned {
            font-weight: 700;
            color: #059669;
            font-size: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfdf7;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #6b7280;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #374151;
            border-color: #d1d5db;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border-color: #3b82f6;
        }

        .action-btn.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-color: #10b981;
        }

        .action-btn.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border-color: #f59e0b;
        }

        .action-btn.primary:hover,
        .action-btn.success:hover,
        .action-btn.warning:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-container {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modern-table {
                font-size: 0.9rem;
            }

            .modern-table thead th,
            .modern-table tbody td {
                padding: 0.75rem;
            }

            .seller-cell {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .action-buttons {
                justify-content: center;
                flex-wrap: wrap;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Unificado -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                </div>
                <div class="header-info">
                    <div class="breadcrumb">
                        <a href="panel.php">Panel</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Vendedores</span>
                    </div>
                    <h1>Gestión de Vendedores</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="raffle-badge">
                    <i class="fas fa-gift"></i>
                    <?php echo htmlspecialchars($rifa_info['name']); ?>
                </div>
                <a href="panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-sellers">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Total Vendedores</div>
                </div>
                <div class="stat-number"><?php echo number_format($total_sellers); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Registrados
                </div>
            </div>

            <div class="stat-card stat-active">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-title">Vendedores Activos</div>
                </div>
                <div class="stat-number"><?php echo number_format($active_sellers); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Actualmente vendiendo
                </div>
            </div>

            <div class="stat-card stat-commission">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-title">Comisiones Totales</div>
                </div>
                <div class="stat-number">$<?php echo number_format($total_commission, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Pagadas a vendedores
                </div>
            </div>

            <div class="stat-card stat-sales">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-title">Ventas por Vendedores</div>
                </div>
                <div class="stat-number">$<?php echo number_format($total_sales_by_sellers, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Ingresos generados
                </div>
            </div>
        </div>

        <!-- Gestión de Vendedores -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Lista de Vendedores
                </h2>
                <button class="btn btn-primary" onclick="addSeller()">
                    <i class="fas fa-plus"></i>
                    Agregar Vendedor
                </button>
            </div>

            <?php if (empty($sellers_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No hay vendedores registrados</h3>
                    <p>Comienza agregando tu primer vendedor</p>
                    <button class="btn btn-primary" onclick="addSeller()" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i>
                        Agregar Primer Vendedor
                    </button>
                </div>
            <?php else: ?>
                <table class="modern-table">
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
                                    <div class="seller-cell">
                                        <div class="seller-avatar">
                                            <?php echo strtoupper(substr($seller['name'], 0, 1)); ?>
                                        </div>
                                        <div class="seller-info">
                                            <h4><?php echo htmlspecialchars($seller['name']); ?></h4>
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
                                    <div class="status-badge status-<?php echo $seller['status']; ?>">
                                        <i class="fas fa-<?php echo $seller['status'] === 'active' ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo ucfirst($seller['status']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($seller['joined_date'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn primary" title="Ver Detalles" onclick="viewSeller(<?php echo $seller['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button class="action-btn success" title="Editar" onclick="editSeller(<?php echo $seller['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="action-btn warning" 
                                                title="<?php echo $seller['status'] === 'active' ? 'Desactivar' : 'Activar'; ?>" 
                                                onclick="toggleSellerStatus(<?php echo $seller['id']; ?>, '<?php echo $seller['status']; ?>')">
                                            <?php if ($seller['status'] === 'active'): ?>
                                                <i class="fas fa-user-slash"></i>
                                            <?php else: ?>
                                                <i class="fas fa-user-check"></i>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>