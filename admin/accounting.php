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

// Datos de ejemplo para contabilidad (en producción vendría de la base de datos)
$accounting_data = [
    'income' => [
        'ticket_sales' => 34000.00,
        'online_sales' => 25000.00,
        'seller_sales' => 9000.00,
        'other_income' => 500.00
    ],
    'expenses' => [
        'seller_commissions' => 2700.00,
        'platform_fees' => 850.00,
        'marketing' => 1200.00,
        'operational' => 450.00,
        'other_expenses' => 300.00
    ],
    'pending' => [
        'pending_sales' => 5500.00,
        'pending_commissions' => 550.00,
        'pending_withdrawals' => 2000.00
    ]
];

// Datos de ejemplo para transacciones recientes
$recent_transactions = [
    [
        'id' => 'TXN-001',
        'date' => '2025-01-15',
        'type' => 'income',
        'category' => 'Venta de boletos',
        'description' => 'Venta online - 5 boletos',
        'amount' => 250.00,
        'method' => 'Tarjeta de crédito',
        'status' => 'completed'
    ],
    [
        'id' => 'TXN-002', 
        'date' => '2025-01-15',
        'type' => 'expense',
        'category' => 'Comisión vendedor',
        'description' => 'Comisión María García',
        'amount' => -30.00,
        'method' => 'Transferencia',
        'status' => 'completed'
    ],
    [
        'id' => 'TXN-003',
        'date' => '2025-01-14',
        'type' => 'income',
        'category' => 'Venta de boletos',
        'description' => 'Venta por vendedor - Juan Pérez',
        'amount' => 150.00,
        'method' => 'Efectivo',
        'status' => 'pending'
    ],
    [
        'id' => 'TXN-004',
        'date' => '2025-01-14',
        'type' => 'expense',
        'category' => 'Gastos operacionales',
        'description' => 'Comisión plataforma de pago',
        'amount' => -12.50,
        'method' => 'Automático',
        'status' => 'completed'
    ]
];

$total_income = array_sum($accounting_data['income']);
$total_expenses = array_sum($accounting_data['expenses']);
$net_profit = $total_income - $total_expenses;
$profit_margin = $total_income > 0 ? ($net_profit / $total_income) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad - <?php echo htmlspecialchars($rifa_info['name']); ?> - Rifas Online</title>
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
        
        .accounting-container {
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
        
        /* Tarjetas de resumen financiero */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .card-income::before {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .card-expenses::before {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .card-profit::before {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .card-margin::before {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .icon-income {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .icon-expenses {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .icon-profit {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .icon-margin {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        
        .card-amount {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .card-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .change-indicator {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .change-positive {
            color: #059669;
        }
        
        .change-negative {
            color: #dc2626;
        }
        
        /* Layout principal */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8fafc;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        /* Breakdown de ingresos y gastos */
        .breakdown-list {
            padding: 0;
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .breakdown-item:last-child {
            border-bottom: none;
        }
        
        .breakdown-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .breakdown-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .breakdown-text {
            flex: 1;
        }
        
        .breakdown-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }
        
        .breakdown-description {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .breakdown-amount {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .amount-income {
            color: #059669;
        }
        
        .amount-expense {
            color: #dc2626;
        }
        
        /* Transacciones recientes */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transactions-table th,
        .transactions-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .transactions-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .transaction-id {
            font-family: monospace;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .transaction-description {
            font-weight: 500;
            color: #1e293b;
        }
        
        .transaction-category {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .transaction-amount {
            font-weight: 700;
            font-size: 1rem;
        }
        
        .transaction-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: #dcfdf7;
            color: #059669;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #dc2626;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-layout {
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
            
            .summary-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card-amount {
                font-size: 1.8rem;
            }
            
            .transactions-table {
                font-size: 0.85rem;
            }
            
            .transactions-table th,
            .transactions-table td {
                padding: 0.8rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="accounting-container">
        <div class="header">
            <div class="header-top">
                <div>
                    <div class="breadcrumb">
                        <a href="panel.php">Panel</a>
                        <span>→</span>
                        <span>Contabilidad</span>
                    </div>
                    <h1 class="header-title">Contabilidad y Finanzas</h1>
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
            <!-- Resumen financiero -->
            <div class="summary-grid">
                <div class="summary-card card-income">
                    <div class="card-header">
                        <div class="card-icon icon-income">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20m5-10a5 5 0 1 1-10 0 5 5 0 0 1 10 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="card-amount">$<?php echo number_format($total_income, 2); ?></div>
                    <div class="card-label">Ingresos Totales</div>
                    <div class="change-indicator change-positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                            <polyline points="17,6 23,6 23,12"/>
                        </svg>
                        +12.5% vs mes anterior
                    </div>
                </div>
                
                <div class="summary-card card-expenses">
                    <div class="card-header">
                        <div class="card-icon icon-expenses">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M16 16l-4-4-4 4"/>
                                <path d="M12 8v8"/>
                            </svg>
                        </div>
                    </div>
                    <div class="card-amount">$<?php echo number_format($total_expenses, 2); ?></div>
                    <div class="card-label">Gastos Totales</div>
                    <div class="change-indicator change-positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                            <polyline points="17,18 23,18 23,12"/>
                        </svg>
                        -3.2% vs mes anterior
                    </div>
                </div>
                
                <div class="summary-card card-profit">
                    <div class="card-header">
                        <div class="card-icon icon-profit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                            </svg>
                        </div>
                    </div>
                    <div class="card-amount">$<?php echo number_format($net_profit, 2); ?></div>
                    <div class="card-label">Ganancia Neta</div>
                    <div class="change-indicator change-positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                            <polyline points="17,6 23,6 23,12"/>
                        </svg>
                        +18.7% vs mes anterior
                    </div>
                </div>
                
                <div class="summary-card card-margin">
                    <div class="card-header">
                        <div class="card-icon icon-margin">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                                <line x1="9" y1="9" x2="9.01" y2="9"/>
                                <line x1="15" y1="9" x2="15.01" y2="9"/>
                            </svg>
                        </div>
                    </div>
                    <div class="card-amount"><?php echo number_format($profit_margin, 1); ?>%</div>
                    <div class="card-label">Margen de Ganancia</div>
                    <div class="change-indicator change-positive">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                            <polyline points="17,6 23,6 23,12"/>
                        </svg>
                        +5.3% vs mes anterior
                    </div>
                </div>
            </div>
            
            <!-- Layout principal -->
            <div class="main-layout">
                <!-- Desglose de ingresos -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">Desglose de Ingresos</h2>
                    </div>
                    <div class="breakdown-list">
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon icon-income">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                        <line x1="3" y1="6" x2="21" y2="6"/>
                                        <path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Venta de Boletos</div>
                                    <div class="breakdown-description">Ventas directas de boletos</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['ticket_sales'], 2); ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                        <line x1="8" y1="21" x2="16" y2="21"/>
                                        <line x1="12" y1="17" x2="12" y2="21"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Ventas Online</div>
                                    <div class="breakdown-description">Plataforma web</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['online_sales'], 2); ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <path d="M20 8v6"/>
                                        <path d="M23 11h-6"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Ventas por Vendedores</div>
                                    <div class="breakdown-description">Comisiones de vendedores</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['seller_sales'], 2); ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                        <path d="M12 17h.01"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Otros Ingresos</div>
                                    <div class="breakdown-description">Patrocinios, bonificaciones</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['other_income'], 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Desglose de gastos -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">Desglose de Gastos</h2>
                    </div>
                    <div class="breakdown-list">
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon icon-expenses">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <path d="M20 8v6"/>
                                        <path d="M23 11h-6"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Comisiones Vendedores</div>
                                    <div class="breakdown-description">Pagos a vendedores</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['seller_commissions'], 2); ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                        <line x1="1" y1="10" x2="23" y2="10"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Comisiones Plataforma</div>
                                    <div class="breakdown-description">Pagos, transferencias</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['platform_fees'], 2); ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M7 7h.01"/>
                                        <path d="M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Marketing</div>
                                    <div class="breakdown-description">Publicidad, promoción</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['marketing'], 2); ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-left">
                                <div class="breakdown-icon" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                    </svg>
                                </div>
                                <div class="breakdown-text">
                                    <div class="breakdown-label">Gastos Operacionales</div>
                                    <div class="breakdown-description">Hosting, mantenimiento</div>
                                </div>
                            </div>
                            <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['operational'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transacciones recientes -->
            <div class="section-card" style="margin-top: 2rem;">
                <div class="section-header">
                    <h2 class="section-title">Transacciones Recientes</h2>
                </div>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                        <tr>
                            <td class="transaction-id"><?php echo $transaction['id']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                            <td>
                                <div class="transaction-description"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                <div class="transaction-category"><?php echo htmlspecialchars($transaction['category']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['method']); ?></td>
                            <td class="transaction-amount <?php echo $transaction['type'] === 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                $<?php echo number_format(abs($transaction['amount']), 2); ?>
                            </td>
                            <td>
                                <span class="transaction-status status-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>