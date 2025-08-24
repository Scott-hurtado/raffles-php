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

// Datos de ejemplo para rifas (en producción vendría de la base de datos)
$rifas_data = [
    [
        'id' => 1,
        'name' => 'iPhone 15 Pro Max',
        'draw_date' => '2025-02-15',
        'ticket_price' => 50.00,
        'total_tickets' => 1000,
        'sold_tickets' => 680,
        'status' => 'active'
    ],
    [
        'id' => 2,
        'name' => 'PlayStation 5 + Juegos',
        'draw_date' => '2025-02-20',
        'ticket_price' => 30.00,
        'total_tickets' => 1500,
        'sold_tickets' => 1200,
        'status' => 'active'
    ],
    [
        'id' => 3,
        'name' => 'MacBook Air M2',
        'draw_date' => '2025-02-25',
        'ticket_price' => 75.00,
        'total_tickets' => 800,
        'sold_tickets' => 450,
        'status' => 'active'
    ],
    [
        'id' => 4,
        'name' => 'Tesla Model 3',
        'draw_date' => '2025-03-01',
        'ticket_price' => 500.00,
        'total_tickets' => 200,
        'sold_tickets' => 85,
        'status' => 'active'
    ]
];
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
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16,17 21,12 16,7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number">247</div>
                    <div class="stat-label">Sorteos Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">15,432</div>
                    <div class="stat-label">Usuarios Registrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$45,670</div>
                    <div class="stat-label">Ventas del Mes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">98.7%</div>
                    <div class="stat-label">Satisfacción</div>
                </div>
            </div>
            
            <!-- Panel de opciones -->
            <div class="panel-grid">
                <div class="panel-card">
                    <div class="card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Gestionar Rifas</h3>
                    <p class="card-description">Crear, editar y administrar todas las rifas activas del sistema</p>
                    <a href="#" class="card-button">Administrar Rifas</a>
                </div>
                
                <div class="panel-card">
                    <div class="card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6"/>
                            <path d="M23 11h-6"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Usuarios</h3>
                    <p class="card-description">Administrar usuarios registrados y sus participaciones</p>
                    <a href="#" class="card-button">Ver Usuarios</a>
                </div>
                
                <div class="panel-card">
                    <div class="card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Pagos</h3>
                    <p class="card-description">Revisar transacciones y gestionar pagos pendientes</p>
                    <a href="#" class="card-button">Ver Pagos</a>
                </div>
                
                <div class="panel-card">
                    <div class="card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 9V5a3 3 0 0 0-6 0v4"/>
                            <rect x="2" y="9" width="20" height="11" rx="2" ry="2"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Sorteos</h3>
                    <p class="card-description">Ejecutar sorteos y administrar ganadores</p>
                    <a href="#" class="card-button">Gestionar Sorteos</a>
                </div>
                
                <div class="panel-card">
                    <div class="card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 19c-5 0-8-4-8-9s4-9 9-9 9 4 9 9"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Reportes</h3>
                    <p class="card-description">Generar reportes y estadísticas del sistema</p>
                    <a href="#" class="card-button">Ver Reportes</a>
                </div>
                
                <div class="panel-card">
                    <div class="card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Configuración</h3>
                    <p class="card-description">Ajustar configuraciones del sistema y parámetros</p>
                    <a href="#" class="card-button">Configurar</a>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
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
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16,17 21,12 16,7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                    <line x1="3" y1="6" x2="21" y2="6"/>
                                    <path d="M16 10a4 4 0 0 1-8 0"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number">4</div>
                        <div class="stat-label">Rifas Activas</div>
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
                        <div class="stat-number">2,415</div>
                        <div class="stat-label">Boletos Vendidos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-sales">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number">$45,670</div>
                        <div class="stat-label">Ventas del Mes</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-satisfaction">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number">98.7%</div>
                        <div class="stat-label">Satisfacción</div>
                    </div>
                </div>
                
                <div class="rifas-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Rifas en Proceso</h2>
                    </div>
                    
                    <table class="rifas-table">
                        <thead>
                            <tr>
                                <th>Rifa</th>
                                <th>Fecha de Sorteo</th>
                                <th>Precio del Boleto</th>
                                <th>Progreso de Venta</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rifas_data as $rifa): ?>
                            <?php 
                                $progress_percentage = ($rifa['sold_tickets'] / $rifa['total_tickets']) * 100;
                                $formatted_date = date('d/m/Y', strtotime($rifa['draw_date']));
                            ?>
                            <tr>
                                <td>
                                    <div class="rifa-name"><?php echo htmlspecialchars($rifa['name']); ?></div>
                                    <div class="rifa-date">ID: #<?php echo $rifa['id']; ?></div>
                                </td>
                                <td>
                                    <div class="rifa-date"><?php echo $formatted_date; ?></div>
                                </td>
                                <td>
                                    <div class="rifa-price">$<?php echo number_format($rifa['ticket_price'], 2); ?></div>
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
                                    <span class="rifa-status status-<?php echo $rifa['status']; ?>">
                                        <?php echo ucfirst($rifa['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="rifa-actions">
                                        <button class="action-btn btn-sellers" 
                                                data-tooltip="Vendedores"
                                                onclick="window.location.href='sellers.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                <circle cx="8.5" cy="7" r="4"/>
                                                <path d="M20 8v6"/>
                                                <path d="M23 11h-6"/>
                                            </svg>
                                        </button>
                                        
                                        <button class="action-btn btn-accounting" 
                                                data-tooltip="Contabilidad"
                                                onclick="window.location.href='accounting.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="1" x2="12" y2="23"/>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                            </svg>
                                        </button>
                                        
                                        <button class="action-btn btn-settings" 
                                                data-tooltip="Configuración"
                                                onclick="window.location.href='settings_committee.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="3"/>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                            </svg>
                                        </button>
                                        
                                        <div class="status-menu-container">
                                            <button class="action-btn btn-status-menu" 
                                                    data-tooltip="Cambiar Estado"
                                                    onclick="toggleStatusMenu(<?php echo $rifa['id']; ?>)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="5" cy="12" r="1"/>
                                                    <circle cx="12" cy="12" r="1"/>
                                                    <circle cx="19" cy="12" r="1"/>
                                                </svg>
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
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
                                            
                                            <button class="action-btn btn-accounting" 
                                                    data-tooltip="Contabilidad"
                                                    onclick="window.location.href='accounting.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                                </svg>
                                            </button>
                                            
                                            <button class="action-btn btn-settings" 
                                                    data-tooltip="Configuración"
                                                    onclick="window.location.href='settings_committee.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="3"/>
                                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                                </svg>
                                            </button>
                                            
                                            <div class="status-menu-container">
                                                <button class="action-btn btn-status-menu" 
                                                        data-tooltip="Cambiar Estado"
                                                        onclick="toggleStatusMenu(<?php echo $rifa['id']; ?>)">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="1"/>
                                                        <circle cx="19" cy="12" r="1"/>
                                                        <circle cx="5" cy="12" r="1"/>
                                                    </svg>
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
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
<!-- Vista Seller -->
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
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16,17 21,12 16,7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v5"/>
                                <path d="M3 12v5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number">156</div>
                    <div class="stat-label">Boletos Vendidos</div>
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
                    <div class="stat-number">$2,340</div>
                    <div class="stat-label">Comisiones Ganadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon icon-month">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number">45</div>
                    <div class="stat-label">Ventas Este Mes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon icon-ranking">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                                <path d="M4 22h16"/>
                                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                                <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-number">#3</div>
                    <div class="stat-label">Ranking Mensual</div>
                </div>
            </div>
            
            <!-- Panel de Rifas Disponibles -->
            <div class="rifas-table-container">
                <div class="table-header">
                    <h2 class="table-title">Rifas Disponibles para Venta</h2>
                    <p class="table-subtitle">Rifas activas donde puedes vender boletos y ganar comisiones</p>
                </div>
                
                <table class="rifas-table">
                    <thead>
                        <tr>
                            <th>Rifa</th>
                            <th>Fecha de Sorteo</th>
                            <th>Precio del Boleto</th>
                            <th>Tu Comisión</th>
                            <th>Progreso de Venta</th>
                            <th>Tus Ventas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rifas_data as $rifa): ?>
                        <?php 
                            $progress_percentage = ($rifa['sold_tickets'] / $rifa['total_tickets']) * 100;
                            $formatted_date = date('d/m/Y', strtotime($rifa['draw_date']));
                            $commission_rate = 0.10; // 10% de comisión
                            $commission_amount = $rifa['ticket_price'] * $commission_rate;
                            $seller_sales = rand(5, 25); // Simulación de ventas del vendedor
                            $seller_earnings = $seller_sales * $commission_amount;
                        ?>
                        <tr>
                            <td>
                                <div class="rifa-name"><?php echo htmlspecialchars($rifa['name']); ?></div>
                                <div class="rifa-date">ID: #<?php echo $rifa['id']; ?></div>
                            </td>
                            <td>
                                <div class="rifa-date"><?php echo $formatted_date; ?></div>
                            </td>
                            <td>
                                <div class="rifa-price">$<?php echo number_format($rifa['ticket_price'], 2); ?></div>
                            </td>
                            <td>
                                <div class="commission-amount">$<?php echo number_format($commission_amount, 2); ?></div>
                                <div class="commission-rate">(<?php echo ($commission_rate * 100); ?>%)</div>
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
                                <div class="seller-sales">
                                    <div class="sales-count"><?php echo $seller_sales; ?> vendidos</div>
                                    <div class="sales-earnings">$<?php echo number_format($seller_earnings, 2); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="rifa-actions">
                                    <button class="action-btn btn-sell" 
                                            data-tooltip="Vender Boletos"
                                            onclick="window.location.href='sell_tickets.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v5"/>
                                            <path d="M3 12v5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    
                                    <button class="action-btn btn-history" 
                                            data-tooltip="Historial de Ventas"
                                            onclick="window.location.href='sales_history.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12,6 12,12 16,14"/>
                                        </svg>
                                    </button>
                                    
                                    <button class="action-btn btn-details" 
                                            data-tooltip="Ver Detalles"
                                            onclick="window.location.href='raffle_details.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Panel de Acciones Rápidas -->
            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v5"/>
                            <path d="M3 12v5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <h3>Venta Rápida</h3>
                    <p>Acceso rápido para vender boletos de cualquier rifa activa</p>
                    <button class="action-button">Vender Ahora</button>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h3>Mis Comisiones</h3>
                    <p>Revisa el detalle de tus comisiones y ganancias</p>
                    <button class="action-button">Ver Comisiones</button>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 9V5a3 3 0 0 0-6 0v4"/>
                            <rect x="2" y="9" width="20" height="11" rx="2" ry="2"/>
                        </svg>
                    </div>
                    <h3>Sorteos Ganados</h3>
                    <p>Histórico de sorteos donde tus clientes han resultado ganadores</p>
                    <button class="action-button">Ver Ganadores</button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>