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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/admin/admin_login.css">
    <meta name="robots" content="noindex, nofollow">
    <style>
        .admin-panel {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2rem;
        }
        
        .panel-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .panel-header {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-info h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-info p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .admin-info {
            text-align: right;
        }
        
        .admin-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        
        .admin-details {
            color: #666;
            font-size: 0.9rem;
        }
        
        .panel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .panel-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .panel-card:hover {
            transform: translateY(-5px);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .card-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .card-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        @media (max-width: 768px) {
            .admin-panel {
                padding: 1rem;
            }
            
            .panel-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .admin-info {
                text-align: center;
            }
        }
    </style>
</head>
<body>
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
</body>
</html>