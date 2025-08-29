<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Obtener información del administrador actual
$current_admin = getCurrentAdmin();

// Procesar logout si se solicita
if (isset($_GET['logout'])) {
    logAdminActivity('logout', 'Cierre de sesión');
    logoutAdmin();
    header('Location: admin_login.php');
    exit();
}

// Obtener rifas reales de la base de datos con datos actualizados
$rifas_data = [];
try {
    $sql = "SELECT 
                r.*,
                COALESCE(r.sold_tickets, 0) as sold_tickets,
                a.username as created_by_name
            FROM raffles r 
            LEFT JOIN admins a ON r.created_by = a.id 
            ORDER BY r.updated_at DESC, r.created_at DESC";
    
    $rifas_data = fetchAll($sql);
    
    // Procesar datos para la vista
    foreach ($rifas_data as &$rifa) {
        // Decodificar imágenes JSON
        if ($rifa['images']) {
            $rifa['images_array'] = json_decode($rifa['images'], true) ?: [];
        } else {
            $rifa['images_array'] = [];
        }
        
        // Formatear fecha para mostrar
        $rifa['formatted_date'] = date('d/m/Y H:i', strtotime($rifa['draw_date']));
        $rifa['days_remaining'] = ceil((strtotime($rifa['draw_date']) - time()) / 86400);
        
        // Calcular potencial de ingresos basado en precio actual
        $rifa['potential_revenue'] = $rifa['total_tickets'] * $rifa['ticket_price'];
        $rifa['current_revenue'] = $rifa['sold_tickets'] * $rifa['ticket_price'];
        
        // Calcular comisiones totales
        $rifa['total_commission'] = $rifa['current_revenue'] * ($rifa['commission_rate'] / 100);
    }
} catch (Exception $e) {
    error_log("Error al obtener rifas: " . $e->getMessage());
    $rifas_data = [];
}

// Estadísticas generales actualizadas
$stats = [
    'active_raffles' => 0,
    'total_users' => 0,
    'monthly_sales' => 0,
    'total_tickets_sold' => 0,
    'total_revenue' => 0,
    'total_commissions' => 0
];

try {
    // Rifas activas
    $stats['active_raffles'] = fetchOne("SELECT COUNT(*) as count FROM raffles WHERE status = 'active'")['count'] ?? 0;
    
    // Total de usuarios registrados
    $stats['total_users'] = fetchOne("SELECT COUNT(*) as count FROM admins")['count'] ?? 0;
    
    // Ventas del mes (usando precio actual de cada rifa)
    $monthly_sales_query = "
        SELECT COALESCE(SUM(r.ticket_price * r.sold_tickets), 0) as total 
        FROM raffles r 
        WHERE MONTH(r.created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
    ";
    $stats['monthly_sales'] = fetchOne($monthly_sales_query)['total'] ?? 0;
    
    // Total de boletos vendidos
    $stats['total_tickets_sold'] = fetchOne("SELECT COALESCE(SUM(sold_tickets), 0) as total FROM raffles")['total'] ?? 0;
    
    // Revenue total
    $revenue_query = "SELECT COALESCE(SUM(r.ticket_price * r.sold_tickets), 0) as total FROM raffles r";
    $stats['total_revenue'] = fetchOne($revenue_query)['total'] ?? 0;
    
    // Comisiones totales
    $commissions_query = "SELECT COALESCE(SUM(r.ticket_price * r.sold_tickets * (r.commission_rate / 100)), 0) as total FROM raffles r";
    $stats['total_commissions'] = fetchOne($commissions_query)['total'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de <?php echo ucfirst($current_admin['user_type']); ?> - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/admin/admin_login.css">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/admin/panel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if ($current_admin['user_type'] === 'admin'): ?>
    <!-- Vista Admin -->
    <div class="admin-panel">
        <div class="panel-container">
            <!-- Header -->
            <div class="panel-header">
                <div class="welcome-info">
                    <h1>Panel de Administración</h1>
                    <p>Bienvenido, <?php echo htmlspecialchars($current_admin['username']); ?></p>
                </div>
                <div class="admin-info">
                    <div class="admin-badge"><?php echo htmlspecialchars($current_admin['user_type']); ?></div>
                    <div class="admin-details">
                        <?php echo htmlspecialchars($current_admin['email']); ?><br>
                        Última sesión: <?php echo date('d/m/Y H:i', $current_admin['login_time']); ?>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="?logout=1" class="logout-btn" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                            <i class="fas fa-sign-out-alt"></i>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas rápidas actualizadas -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_raffles']; ?></div>
                    <div class="stat-label">Rifas Activas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="stat-label">Revenue Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['monthly_sales'], 0); ?></div>
                    <div class="stat-label">Ventas del Mes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['total_commissions'], 0); ?></div>
                    <div class="stat-label">Comisiones Totales</div>
                </div>
            </div>
            
            <!-- Contenido principal - Tabla de rifas -->
            <div class="admin-content">
                <div class="rifas-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Gestión de Rifas</h2>
                        <button class="create-rifa-btn" onclick="createNewRifa()">
                            <i class="fas fa-plus"></i>
                            Crear Nueva Rifa
                        </button>
                    </div>
                    
                    <?php if (empty($rifas_data)): ?>
                    <div style="padding: 3rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-gift" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3 style="margin-bottom: 0.5rem;">No hay rifas creadas</h3>
                        <p>Comienza creando tu primera rifa haciendo clic en "Crear Nueva Rifa"</p>
                    </div>
                    <?php else: ?>
                    <table class="rifas-table">
                        <thead>
                            <tr>
                                <th>Rifa</th>
                                <th>Fecha de Sorteo</th>
                                <th>Precio & Comisión</th>
                                <th>Progreso de Venta</th>
                                <th>Revenue</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rifas_data as $rifa): ?>
                            <?php 
                                $progress_percentage = $rifa['total_tickets'] > 0 ? ($rifa['sold_tickets'] / $rifa['total_tickets']) * 100 : 0;
                                $is_expired = strtotime($rifa['draw_date']) < time();
                            ?>
                            <tr>
                                <td>
                                    <div class="rifa-name"><?php echo htmlspecialchars($rifa['name']); ?></div>
                                    <div class="rifa-date">
                                        ID: #<?php echo $rifa['id']; ?>
                                        <?php if ($rifa['days_remaining'] >= 0 && !$is_expired): ?>
                                            • <?php echo $rifa['days_remaining']; ?> días restantes
                                        <?php elseif ($is_expired && $rifa['status'] !== 'finished'): ?>
                                            • <span style="color: #ef4444;">Vencida</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="rifa-date">
                                        <?php echo $rifa['formatted_date']; ?>
                                        <?php if ($is_expired && $rifa['status'] !== 'finished'): ?>
                                            <br><small style="color: #ef4444;">Requiere sorteo</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="rifa-price">$<?php echo number_format($rifa['ticket_price'], 2); ?></div>
                                    <div style="font-size: 0.8rem; color: #6b7280;">
                                        Comisión: <?php echo number_format($rifa['commission_rate'], 1); ?>%
                                    </div>
                                    <div style="font-size: 0.8rem; color: #059669; font-weight: 600;">
                                        $<?php echo number_format($rifa['ticket_price'] * ($rifa['commission_rate'] / 100), 2); ?> por boleto
                                    </div>
                                    <?php if (strtotime($rifa['updated_at']) > strtotime('-1 hour')): ?>
                                        <div style="font-size: 0.7rem; color: #f59e0b; font-weight: 600;">
                                            🔄 Actualizado recientemente
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rifa-progress">
                                        <div class="progress-text">
                                            <?php echo number_format($rifa['sold_tickets']); ?> / <?php echo number_format($rifa['total_tickets']); ?> 
                                            (<?php echo number_format($progress_percentage, 1); ?>%)
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                                        </div>
                                        <?php if ($progress_percentage >= 100): ?>
                                            <div style="font-size: 0.8rem; color: #059669; font-weight: 600; margin-top: 0.3rem;">
                                                ¡Agotada!
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #059669; font-size: 1rem;">
                                        $<?php echo number_format($rifa['current_revenue'], 0); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #6b7280;">
                                        de $<?php echo number_format($rifa['potential_revenue'], 0); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #f59e0b; font-weight: 600;">
                                        Comisiones: $<?php echo number_format($rifa['total_commission'], 0); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="rifa-status status-<?php echo $rifa['status']; ?>">
                                        <?php 
                                            $status_labels = [
                                                'active' => 'Activa',
                                                'paused' => 'Pausada',
                                                'finished' => 'Finalizada',
                                                'cancelled' => 'Cancelada'
                                            ];
                                            echo $status_labels[$rifa['status']] ?? ucfirst($rifa['status']);
                                        ?>
                                    </span>
                                    <?php if ($rifa['draw_completed']): ?>
                                        <div style="font-size: 0.8rem; color: #059669; margin-top: 0.3rem;">
                                            <i class="fas fa-trophy"></i> Sorteada
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rifa-actions">
                                        <button class="action-btn btn-users" 
                                                data-tooltip="Gestionar Usuarios"
                                                onclick="manageUsers(<?php echo $rifa['id']; ?>)">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        
                                        <?php if (!$rifa['draw_completed'] && ($is_expired || $progress_percentage >= 100)): ?>
                                        <button class="action-btn btn-draw" 
                                                data-tooltip="Lanzar Sorteo"
                                                onclick="launchDraw(<?php echo $rifa['id']; ?>)"
                                                style="background: #fbbf24; color: white; border-color: #fbbf24;">
                                            <i class="fas fa-trophy"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="action-btn btn-draw" 
                                                data-tooltip="Lanzar Sorteo"
                                                onclick="launchDraw(<?php echo $rifa['id']; ?>)">
                                            <i class="fas fa-trophy"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button class="action-btn btn-payments" 
                                                data-tooltip="Gestionar Pagos"
                                                onclick="managePayments(<?php echo $rifa['id']; ?>)">
                                            <i class="fas fa-credit-card"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-reports" 
                                                data-tooltip="Ver Reportes"
                                                onclick="viewReports(<?php echo $rifa['id']; ?>)">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-settings" 
                                                data-tooltip="Configuración"
                                                onclick="rifaSettings(<?php echo $rifa['id']; ?>)">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        
                                        <div class="status-menu-container">
                                            <button class="action-btn btn-status-menu" 
                                                    data-tooltip="Cambiar Estado"
                                                    onclick="toggleStatusMenu(<?php echo $rifa['id']; ?>)">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            
                                            <div class="status-dropdown" id="statusMenu-<?php echo $rifa['id']; ?>">
                                                <div class="status-option active" 
                                                     onclick="changeRifaStatus(<?php echo $rifa['id']; ?>, 'active')">
                                                    <div class="status-dot status-dot-active"></div>
                                                    Activa
                                                </div>
                                                <div class="status-option paused" 
                                                     onclick="changeRifaStatus(<?php echo $rifa['id']; ?>, 'paused')">
                                                    <div class="status-dot status-dot-paused"></div>
                                                    Pausada
                                                </div>
                                                <div class="status-option finished" 
                                                     onclick="changeRifaStatus(<?php echo $rifa['id']; ?>, 'finished')">
                                                    <div class="status-dot status-dot-finished"></div>
                                                    Finalizada
                                                </div>
                                                <div class="status-option cancelled" 
                                                     onclick="changeRifaStatus(<?php echo $rifa['id']; ?>, 'cancelled')">
                                                    <div class="status-dot status-dot-cancelled"></div>
                                                    Cancelada
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($current_admin['user_type'] === 'committee'): ?>
    <!-- Vista Committee -->
    <div class="committee-panel">
        <div class="panel-container">
            <!-- Header Committee -->
            <div class="committee-header">
                <div style="flex: 1;">
                    <div class="committee-header-top">
                        <div>
                            <div class="breadcrumb">
                                <span>Panel</span>
                            </div>
                            <h1 class="committee-title">Panel de Comité</h1>
                        </div>
                        <div class="committee-user-info">
                            <div class="committee-avatar">
                                <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                            </div>
                            <div class="committee-details">
                                <div class="committee-name"><?php echo htmlspecialchars($current_admin['username']); ?></div>
                                <div class="committee-type"><?php echo ucfirst($current_admin['user_type']); ?></div>
                            </div>
                            <a href="?logout=1" class="logout-btn" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Committee -->
            <div class="committee-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-rifas">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                        <div class="stat-number"><?php echo $stats['active_raffles']; ?></div>
                        <div class="stat-label">Rifas Activas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-active">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                        <div class="stat-label">Revenue Total</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-sales">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                        <div class="stat-number">$<?php echo number_format($stats['total_commissions'], 0); ?></div>
                        <div class="stat-label">Comisiones Generadas</div>
                    </div>
                </div>
                
                <!-- Tabla de rifas para committee con información actualizada -->
                <div class="rifas-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Gestión de Rifas</h2>
                        <button class="create-rifa-btn" onclick="createNewRifa()">
                            <i class="fas fa-plus"></i>
                            Crear Nueva Rifa
                        </button>
                    </div>
                    
                    <?php if (!empty($rifas_data)): ?>
                    <table class="rifas-table">
                        <thead>
                            <tr>
                                <th>Rifa</th>
                                <th>Precio & Comisión</th>
                                <th>Progreso de Venta</th>
                                <th>Revenue</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rifas_data as $rifa): ?>
                            <?php $progress_percentage = $rifa['total_tickets'] > 0 ? ($rifa['sold_tickets'] / $rifa['total_tickets']) * 100 : 0; ?>
                            <tr>
                                <td>
                                    <div class="rifa-name"><?php echo htmlspecialchars($rifa['name']); ?></div>
                                    <div class="rifa-date">
                                        ID: #<?php echo $rifa['id']; ?> • <?php echo $rifa['formatted_date']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="rifa-price">$<?php echo number_format($rifa['ticket_price'], 2); ?></div>
                                    <div style="font-size: 0.8rem; color: #059669; font-weight: 600;">
                                        <?php echo number_format($rifa['commission_rate'], 1); ?>% comisión
                                    </div>
                                    <div style="font-size: 0.8rem; color: #6b7280;">
                                        $<?php echo number_format($rifa['ticket_price'] * ($rifa['commission_rate'] / 100), 2); ?> por venta
                                    </div>
                                    <?php if (strtotime($rifa['updated_at']) > strtotime('-1 hour')): ?>
                                        <div style="font-size: 0.7rem; color: #f59e0b; font-weight: 600;">
                                            ✨ Recién actualizado
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rifa-progress">
                                        <div class="progress-text">
                                            <?php echo number_format($rifa['sold_tickets']); ?> / <?php echo number_format($rifa['total_tickets']); ?> 
                                            (<?php echo number_format($progress_percentage, 1); ?>%)
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #059669;">
                                        $<?php echo number_format($rifa['current_revenue'], 0); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #6b7280;">
                                        de $<?php echo number_format($rifa['potential_revenue'], 0); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="rifa-status status-<?php echo $rifa['status']; ?>">
                                        <?php 
                                            $status_labels = [
                                                'active' => 'Activa',
                                                'paused' => 'Pausada', 
                                                'finished' => 'Finalizada',
                                                'cancelled' => 'Cancelada'
                                            ];
                                            echo $status_labels[$rifa['status']] ?? ucfirst($rifa['status']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="rifa-actions">
                                        <button class="action-btn btn-users" 
                                                data-tooltip="Vendedores"
                                                onclick="window.location.href='sellers.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                            <i class="fas fa-user-tie"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-payments" 
                                                data-tooltip="Contabilidad"
                                                onclick="window.location.href='accounting.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-settings" 
                                                data-tooltip="Configuración"
                                                onclick="window.location.href='settings_committee.php?rifa_id=<?php echo $rifa['id']; ?>'"
                                                style="background: #f59e0b; color: white; border-color: #f59e0b;">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="padding: 3rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-gift" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3 style="margin-bottom: 0.5rem;">No hay rifas disponibles</h3>
                        <p>Las rifas aparecerán aquí una vez que sean creadas</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Vista Seller con precios y comisiones actualizadas -->
    <div class="seller-panel">
        <div class="panel-container">
            <!-- Header Seller -->
            <div class="seller-header">
                <div style="flex: 1;">
                    <div class="seller-header-top">
                        <div>
                            <div class="breadcrumb">
                                <span>Panel</span>
                            </div>
                            <h1 class="seller-title">Panel de Vendedor</h1>
                        </div>
                        <div class="seller-user-info">
                            <div class="seller-avatar">
                                <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                            </div>
                            <div class="seller-details">
                                <div class="seller-name"><?php echo htmlspecialchars($current_admin['username']); ?></div>
                                <div class="seller-type"><?php echo ucfirst($current_admin['user_type']); ?></div>
                            </div>
                            <a href="?logout=1" class="logout-btn" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Seller -->
            <div class="seller-content">
                <!-- Estadísticas del Vendedor -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-tickets">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div class="stat-number">0</div>
                        <div class="stat-label">Boletos Vendidos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-commission">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                        <div class="stat-number">$0.00</div>
                        <div class="stat-label">Comisiones Ganadas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-ranking">
                                <i class="fas fa-medal"></i>
                            </div>
                        </div>
                        <div class="stat-number">-</div>
                        <div class="stat-label">Ranking Mensual</div>
                    </div>
                </div>
                
                <!-- Rifas Disponibles para Vender con precios actualizados -->
                <div class="rifas-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Rifas Disponibles</h2>
                        <button class="create-rifa-btn" onclick="window.location.href='sell_tickets.php'">
                            <i class="fas fa-shopping-cart"></i>
                            Crear Venta
                        </button>
                    </div>
                    
                    <?php if (!empty($rifas_data)): ?>
                    <table class="rifas-table">
                        <thead>
                            <tr>
                                <th>Rifa</th>
                                <th>Precio del Boleto</th>
                                <th>Tu Comisión</th>
                                <th>Disponibles</th>
                                <th>Mis Ventas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rifas_data as $rifa): ?>
                            <?php 
                                $available_tickets = $rifa['total_tickets'] - $rifa['sold_tickets'];
                                $my_sales = 0; // Aquí implementarías la lógica para obtener las ventas del vendedor actual
                                $commission = $rifa['ticket_price'] * ($rifa['commission_rate'] / 100);
                            ?>
                            <tr>
                                <td>
                                    <div class="rifa-name"><?php echo htmlspecialchars($rifa['name']); ?></div>
                                    <div class="rifa-date">Sorteo: <?php echo $rifa['formatted_date']; ?></div>
                                    <?php if (strtotime($rifa['updated_at']) > strtotime('-1 hour')): ?>
                                        <div style="font-size: 0.7rem; color: #f59e0b; font-weight: 600;">
                                            🔄 Precios actualizados
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rifa-price" style="font-size: 1.2rem; font-weight: 700;">
                                        $<?php echo number_format($rifa['ticket_price'], 2); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="commission-info" style="font-size: 1.2rem; font-weight: 700;">
                                        $<?php echo number_format($commission, 2); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #059669;">
                                        <?php echo number_format($rifa['commission_rate'], 1); ?>% por boleto
                                    </div>
                                </td>
                                <td>
                                    <div class="available-tickets"><?php echo number_format($available_tickets); ?></div>
                                </td>
                                <td>
                                    <div class="my-sales"><?php echo $my_sales; ?></div>
                                </td>
                                <td>
                                    <div class="rifa-actions">
                                        <button class="action-btn btn-sell" 
                                                data-tooltip="Vender Boletos"
                                                onclick="sellTickets(<?php echo $rifa['id']; ?>)"
                                                <?php echo $available_tickets <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-view-sales" 
                                                data-tooltip="Ver Mis Ventas"
                                                onclick="viewMySales(<?php echo $rifa['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="padding: 3rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3 style="margin-bottom: 0.5rem;">No hay rifas disponibles</h3>
                        <p>Las rifas disponibles para vender aparecerán aquí</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Funciones para el Admin
        function createNewRifa() {
            window.location.href = 'admin_create_raffle.php';
        }
        
        function manageUsers(rifaId) {
            window.location.href = 'admin_create_user.php?rifa_id=' + rifaId;
        }
        
        function launchDraw(rifaId) {
            if (confirm(`¿Estás seguro de que deseas lanzar el sorteo para la rifa ID: ${rifaId}?`)) {
                window.location.href = 'admin_run_raffle.php?rifa_id=' + rifaId;
            }
        }
        
        function managePayments(rifaId) {
            window.location.href = 'admin_payments.php?rifa_id=' + rifaId;
        }
        
        function viewReports(rifaId) {
            window.location.href = 'admin_reports.php?rifa_id=' + rifaId;
        }
        
        function rifaSettings(rifaId) {
            window.location.href = 'admin_settings.php?rifa_id=' + rifaId;
        }
        
        // Funciones para status menu
        function toggleStatusMenu(rifaId) {
            const menu = document.getElementById('statusMenu-' + rifaId);
            const allMenus = document.querySelectorAll('.status-dropdown');
            
            allMenus.forEach(m => {
                if (m !== menu) {
                    m.classList.remove('active');
                }
            });
            
            menu.classList.toggle('active');
        }
        
        function changeRifaStatus(rifaId, newStatus) {
            if (confirm(`¿Estás seguro de cambiar el estado de la rifa?`)) {
                fetch('update_raffle_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        raffle_id: rifaId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al cambiar el estado: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error de conexión: ' + error);
                });
                
                document.getElementById('statusMenu-' + rifaId).classList.remove('active');
            }
        }
        
        // Funciones para vendedores
        function sellTickets(rifaId) {
            window.location.href = 'sell_tickets.php?rifa_id=' + rifaId;
        }
        
        function viewMySales(rifaId) {
            window.location.href = 'my_sales.php?rifa_id=' + rifaId;
        }
        
        // Auto-refresh para mostrar cambios en tiempo real
        <?php if ($current_admin['user_type'] === 'committee' || $current_admin['user_type'] === 'seller'): ?>
        setInterval(function() {
            // Verificar si hay cambios recientes en las rifas
            fetch('check_raffle_updates.php')
            .then(response => response.json())
            .then(data => {
                if (data.hasUpdates) {
                    // Mostrar notificación de actualización
                    const notification = document.createElement('div');
                    notification.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: linear-gradient(135deg, #f59e0b, #d97706);
                        color: white;
                        padding: 1rem;
                        border-radius: 10px;
                        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
                        z-index: 10000;
                        cursor: pointer;
                        animation: slideIn 0.3s ease;
                    `;
                    notification.innerHTML = '🔄 Hay actualizaciones disponibles. <strong>Haz clic para recargar</strong>';
                    notification.onclick = () => location.reload();
                    
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        if (document.body.contains(notification)) {
                            document.body.removeChild(notification);
                        }
                    }, 10000);
                }
            })
            .catch(error => console.log('Error checking updates:', error));
        }, 60000); // Verificar cada minuto
        <?php endif; ?>
        
        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.status-menu-container')) {
                document.querySelectorAll('.status-dropdown').forEach(menu => {
                    menu.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>