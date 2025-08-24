<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premios - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/partials/navbar.css">
    <link rel="stylesheet" href="../assets/css/raffles/results.css">
    <link rel="stylesheet" href="../assets/css/partials/footer.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../partials/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="rewards-hero">
        <div class="hero-content">
            <h1 class="hero-title">Increíbles Premios</h1>
            <p class="hero-subtitle">Descubre todos los premios disponibles y participa por el que más te guste</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-container">
        <div class="filters-content">
            <h2 class="filters-title">Filtrar Premios</h2>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Todos</button>
                <button class="filter-btn" data-filter="electronics">Electrónicos</button>
                <button class="filter-btn" data-filter="vehicles">Vehículos</button>
                <button class="filter-btn" data-filter="cash">Dinero</button>
                <button class="filter-btn" data-filter="other">Otros</button>
            </div>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Buscar premio..." class="search-input">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Rewards Grid Section -->
    <div class="rewards-container">
        <div class="rewards-grid" id="rewardsGrid">
            <!-- Los premios se generarán dinámicamente -->
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../partials/footer.php'; ?>

    <!-- Load Scripts -->
    <script src="../assets/js/partials/navbar.js"></script>
    <script src="../assets/js/raffles/rewards.js"></script>
</body>
</html>