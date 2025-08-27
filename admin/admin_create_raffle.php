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

$success_message = '';
$error_message = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos del formulario
        $name = trim($_POST['raffle_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $draw_date = $_POST['draw_date'] ?? '';
        $draw_time = $_POST['draw_time'] ?? '';
        $ticket_price = floatval($_POST['ticket_price'] ?? 0);
        $total_tickets = intval($_POST['total_tickets'] ?? 0);
        $commission_rate = floatval($_POST['commission_rate'] ?? 10);

        // Validaciones básicas
        if (empty($name)) {
            throw new Exception('El nombre de la rifa es obligatorio');
        }
        if (empty($draw_date) || empty($draw_time)) {
            throw new Exception('La fecha y hora del sorteo son obligatorias');
        }
        if ($ticket_price <= 0) {
            throw new Exception('El precio del boleto debe ser mayor a 0');
        }
        if ($total_tickets <= 0) {
            throw new Exception('El número de boletos debe ser mayor a 0');
        }

        // Validar que la fecha sea futura
        $draw_datetime = $draw_date . ' ' . $draw_time;
        if (strtotime($draw_datetime) <= time()) {
            throw new Exception('La fecha y hora del sorteo debe ser futura');
        }

        // Procesar imágenes subidas
        $uploaded_images = [];
        if (isset($_FILES['images'])) {
            $upload_dir = '../uploads/raffles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $new_filename = uniqid('raffle_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                        $uploaded_images[] = $new_filename;
                    }
                }
            }
        }

        // Insertar en la base de datos
        $sql = "INSERT INTO raffles (name, description, draw_date, ticket_price, total_tickets, commission_rate, images, created_by, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $images_json = json_encode($uploaded_images);
        
        executeQuery($sql, [
            $name,
            $description,
            $draw_datetime,
            $ticket_price,
            $total_tickets,
            $commission_rate,
            $images_json,
            $current_admin['id']
        ]);

        logAdminActivity('create_raffle', "Rifa creada: {$name}");
        $success_message = 'Rifa creada exitosamente';

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error al crear rifa: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Rifa - Panel de Administración</title>
    <link rel="stylesheet" href="../assets/css/admin/admin_login.css">
    <link rel="stylesheet" href="../assets/css/admin/panel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="robots" content="noindex, nofollow">
    <style>
        .create-raffle-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: #f8fafc;
            min-height: 100vh;
        }

        .create-raffle-header {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-info p {
            color: #666;
            font-size: 1.1rem;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
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

        .form-input {
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .image-upload-container {
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .image-upload-container:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .image-upload-container.dragover {
            border-color: #667eea;
            background: #e0e7ff;
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .upload-text {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .upload-subtext {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1;
        }

        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .calculations-panel {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid #bae6fd;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .calculations-title {
            color: #0369a1;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(14, 165, 233, 0.2);
        }

        .calc-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .calc-label {
            color: #0369a1;
        }

        .calc-value {
            color: #1e40af;
            font-weight: 600;
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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

        .hidden-input {
            display: none;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .create-raffle-container {
                padding: 1rem;
            }

            .create-raffle-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .form-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="create-raffle-container">
        <!-- Header -->
        <div class="create-raffle-header">
            <div class="header-info">
                <h1>Crear Nueva Rifa</h1>
                <p>Complete la información para crear una nueva rifa</p>
            </div>
            <div class="admin-info">
                <div class="admin-badge"><?php echo htmlspecialchars($current_admin['user_type']); ?></div>
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

        <!-- Formulario -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-gift"></i> Información de la Rifa</h2>
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

                <form method="POST" enctype="multipart/form-data" id="raffleForm">
                    <div class="form-grid">
                        <!-- Nombre de la rifa -->
                        <div class="form-group">
                            <label class="form-label" for="raffle_name">
                                <i class="fas fa-tag"></i> Nombre de la Rifa *
                            </label>
                            <input type="text" 
                                   id="raffle_name" 
                                   name="raffle_name" 
                                   class="form-input" 
                                   placeholder="Ej: iPhone 15 Pro Max"
                                   required>
                        </div>

                        <!-- Precio del boleto -->
                        <div class="form-group">
                            <label class="form-label" for="ticket_price">
                                <i class="fas fa-dollar-sign"></i> Precio del Boleto *
                            </label>
                            <input type="number" 
                                   id="ticket_price" 
                                   name="ticket_price" 
                                   class="form-input" 
                                   placeholder="50.00"
                                   step="0.01"
                                   min="0.01"
                                   required>
                        </div>

                        <!-- Número total de boletos -->
                        <div class="form-group">
                            <label class="form-label" for="total_tickets">
                                <i class="fas fa-ticket-alt"></i> Número Total de Boletos *
                            </label>
                            <input type="number" 
                                   id="total_tickets" 
                                   name="total_tickets" 
                                   class="form-input" 
                                   placeholder="1000"
                                   min="1"
                                   required>
                        </div>

                        <!-- Tasa de comisión -->
                        <div class="form-group">
                            <label class="form-label" for="commission_rate">
                                <i class="fas fa-percentage"></i> Tasa de Comisión (%)
                            </label>
                            <input type="number" 
                                   id="commission_rate" 
                                   name="commission_rate" 
                                   class="form-input" 
                                   placeholder="10"
                                   step="0.1"
                                   min="0"
                                   max="100"
                                   value="10">
                        </div>

                        <!-- Fecha del sorteo -->
                        <div class="form-group">
                            <label class="form-label" for="draw_date">
                                <i class="fas fa-calendar"></i> Fecha del Sorteo *
                            </label>
                            <input type="date" 
                                   id="draw_date" 
                                   name="draw_date" 
                                   class="form-input" 
                                   required>
                        </div>

                        <!-- Hora del sorteo -->
                        <div class="form-group">
                            <label class="form-label" for="draw_time">
                                <i class="fas fa-clock"></i> Hora del Sorteo *
                            </label>
                            <input type="time" 
                                   id="draw_time" 
                                   name="draw_time" 
                                   class="form-input" 
                                   required>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group full-width">
                            <label class="form-label" for="description">
                                <i class="fas fa-align-left"></i> Descripción (Opcional)
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-input form-textarea" 
                                      placeholder="Descripción detallada de la rifa, términos y condiciones, etc."></textarea>
                        </div>

                        <!-- Subida de imágenes -->
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-images"></i> Imágenes de la Rifa (Máximo 5)
                            </label>
                            <div class="image-upload-container" onclick="document.getElementById('images').click()">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">
                                    Haz clic aquí o arrastra las imágenes
                                </div>
                                <div class="upload-subtext">
                                    Formatos soportados: JPG, PNG, GIF (Máximo 5MB cada una)
                                </div>
                            </div>
                            <input type="file" 
                                   id="images" 
                                   name="images[]" 
                                   class="hidden-input" 
                                   multiple 
                                   accept="image/*">
                            <div id="imagePreview" class="image-preview"></div>
                        </div>
                    </div>

                    <!-- Panel de cálculos -->
                    <div class="calculations-panel">
                        <div class="calculations-title">
                            <i class="fas fa-calculator"></i>
                            Cálculos Automáticos
                        </div>
                        <div class="calc-row">
                            <span class="calc-label">Ingresos Brutos Potenciales:</span>
                            <span class="calc-value" id="grossRevenue">$0.00</span>
                        </div>
                        <div class="calc-row">
                            <span class="calc-label">Total en Comisiones:</span>
                            <span class="calc-value" id="totalCommissions">$0.00</span>
                        </div>
                        <div class="calc-row">
                            <span class="calc-label">Ingresos Netos Estimados:</span>
                            <span class="calc-value" id="netRevenue">$0.00</span>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="form-buttons">
                        <a href="panel.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Crear Rifa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Configurar fecha mínima (hoy)
        document.getElementById('draw_date').min = new Date().toISOString().split('T')[0];

        // Variables para imágenes
        const imageInput = document.getElementById('images');
        const imagePreview = document.getElementById('imagePreview');
        const uploadContainer = document.querySelector('.image-upload-container');
        let selectedFiles = [];

        // Manejar selección de archivos
        imageInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        // Drag and drop
        uploadContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadContainer.classList.add('dragover');
        });

        uploadContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadContainer.classList.remove('dragover');
        });

        uploadContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadContainer.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        function handleFiles(files) {
            const maxFiles = 5;
            const remainingSlots = maxFiles - selectedFiles.length;
            const filesToAdd = Math.min(files.length, remainingSlots);

            for (let i = 0; i < filesToAdd; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    displayPreview(file, selectedFiles.length - 1);
                }
            }

            updateFileInput();
        }

        function displayPreview(file, index) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="preview-image">
                    <button type="button" class="remove-image" onclick="removeImage(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                imagePreview.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }

        function removeImage(index) {
            selectedFiles.splice(index, 1);
            updateFileInput();
            refreshPreviews();
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            imageInput.files = dt.files;
        }

        function refreshPreviews() {
            imagePreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                displayPreview(file, index);
            });
        }

        // Cálculos automáticos
        function calculateRevenues() {
            const ticketPrice = parseFloat(document.getElementById('ticket_price').value) || 0;
            const totalTickets = parseInt(document.getElementById('total_tickets').value) || 0;
            const commissionRate = parseFloat(document.getElementById('commission_rate').value) || 0;

            const grossRevenue = ticketPrice * totalTickets;
            const totalCommissions = grossRevenue * (commissionRate / 100);
            const netRevenue = grossRevenue - totalCommissions;

            document.getElementById('grossRevenue').textContent = '$' + grossRevenue.toLocaleString('es-MX', {minimumFractionDigits: 2});
            document.getElementById('totalCommissions').textContent = '$' + totalCommissions.toLocaleString('es-MX', {minimumFractionDigits: 2});
            document.getElementById('netRevenue').textContent = '$' + netRevenue.toLocaleString('es-MX', {minimumFractionDigits: 2});
        }

        // Event listeners para cálculos
        document.getElementById('ticket_price').addEventListener('input', calculateRevenues);
        document.getElementById('total_tickets').addEventListener('input', calculateRevenues);
        document.getElementById('commission_rate').addEventListener('input', calculateRevenues);

        // Calcular al cargar la página
        calculateRevenues();
    </script>
</body>
</html>