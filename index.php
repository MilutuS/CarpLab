<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>CarpLab</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <link href="style.css" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
    <nav class="app-sidebar">
        <div class="brand-logo">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#0d6efd" viewBox="0 0 16 16">
                    <path d="M15.5 8c-1.657-2.5-4.5-4-7.5-4-1.7 0-3.29.5-4.5 1.5C2.5 5.5 2 5 1.5 5c-.5 0-1 .5-1 1s.5 1 1 1c.5 0 1-.5 1.5-1.5C4.21 7.5 5.8 8 7.5 8c3 0 5.843-1.5 7.5-4zm-7.5 5c1.7 0 3.29-.5 4.5-1.5.5 1 1 1.5 1.5 1.5.5 0 1-.5 1-1s-.5-1-1-1c-.5 0-1 .5-1.5 1.5C11.79 8.5 10.2 8 8.5 8c-3 0-5.843 1.5-7.5 4zm2-2a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
            </div>
            <span>CarpLab</span>
        </div>
        
        <div class="nav-menu nav" id="mainTabs" role="tablist">
            <a class="nav-item active" data-bs-toggle="tab" data-bs-target="#home" type="button">
                <i class="bi bi-house-fill"></i> <span class="nav-text-desktop">Strona główna</span><span class="nav-text-mobile">Główna</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#products" type="button">
                <i class="bi bi-grid-fill"></i> <span class="nav-text-desktop">Baza produktów</span><span class="nav-text-mobile">Produkty</span>
            </a>
            <a class="nav-item" data-bs-toggle="tab" data-bs-target="#recipes" type="button">
                <i class="bi bi-journal-bookmark-fill"></i> <span class="nav-text-desktop">Przepisy</span><span class="nav-text-mobile">Przepisy</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#stock" type="button">
                <i class="bi bi-clipboard-data-fill"></i> <span class="nav-text-desktop">Stan magazynowy</span><span class="nav-text-mobile">Magazyn</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                <i class="bi bi-bag-fill"></i> <span class="nav-text-desktop">Zakupy</span><span class="nav-text-mobile">Zakupy</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#executed" type="button">
                <i class="bi bi-clock-history"></i> <span class="nav-text-desktop">Historia</span><span class="nav-text-mobile">Historia</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#finished-balls" type="button">
                <i class="bi bi-basket-fill"></i> <span class="nav-text-desktop">Gotowe kulki</span><span class="nav-text-mobile">Kulki</span>
            </a>
            
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                <i class="bi bi-gear-fill"></i> <span class="nav-text-desktop">Ustawienia</span><span class="nav-text-mobile">Opcje</span>
            </a>
            
             <a class="nav-item require-admin d-none" id="history-tab-nav" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="bi bi-activity"></i> <span class="nav-text-desktop">Logi</span><span class="nav-text-mobile">Logi</span>
            </a>
             <a class="nav-item require-admin d-none" id="users-tab-nav" data-bs-toggle="tab" data-bs-target="#users" type="button">
                <i class="bi bi-people-fill"></i> <span class="nav-text-desktop">Użytkownicy</span><span class="nav-text-mobile">Users</span>
            </a>
             <a class="nav-item require-admin d-none" id="pending-recipes-tab-nav" data-bs-toggle="tab" data-bs-target="#pending-recipes" type="button">
                <i class="bi bi-hourglass-split"></i> <span class="nav-text-desktop">Oczekujące przepisy</span><span class="nav-text-mobile">Oczek.</span>
            </a>
             <a class="nav-item require-admin d-none" id="backup-tab-nav" data-bs-toggle="tab" data-bs-target="#backup" type="button">
                <i class="bi bi-shield-lock-fill"></i> <span class="nav-text-desktop">Admin</span><span class="nav-text-mobile">Admin</span>
            </a>
            
            <button class="nav-item mobile-logout-btn require-login d-none" id="mobileLogoutBtn" type="button" style="display: none;">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
        
        <!-- Informacja o użytkowniku -->
        <div class="user-info mt-auto p-3 border-top">
            <div id="userLoggedIn" class="d-none">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-white-50">Zalogowany jako:</small>
                        <div class="text-white"><strong id="currentUsername"></strong></div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" id="logoutBtn">
                        <i class="bi bi-box-arrow-right"></i> Wyloguj
                    </button>
                </div>
            </div>
            <div id="userLoggedOut">
                <button class="btn btn-primary w-100" id="showLoginModal">
                    <i class="bi bi-person-fill"></i> Zaloguj się
                </button>
            </div>
        </div>
    </nav>

    <main class="app-content">
        <div class="tab-content" id="mainTabContent">
            
            <?php include 'sites/home.php'; ?>
            
            <?php include 'sites/products.php'; ?>

            <?php include 'sites/recipes.php'; ?>

            <?php include 'sites/stock.php'; ?>

            <?php include 'sites/orders.php'; ?>

            <?php include 'sites/executed.php'; ?>
            
            <?php include 'sites/finished-balls.php'; ?>
            
            <?php include 'sites/settings.php'; ?>
            
            <?php include 'sites/history.php'; ?>
            
            <?php include 'sites/users.php'; ?>
            
            <?php include 'sites/backup.php'; ?>

            <?php include 'sites/pending-recipes.php'; ?>

        </div>
    </main>
</div>

<!-- Mobilny przycisk zwijania menu -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" type="button">
    <i class="bi bi-chevron-down"></i>
</button>

<?php include 'sites/modals.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="script.js?v=<?php echo time(); ?>"></script>

<script>
$(document).ready(function() {
    // Obsługa kliknięcia w nawigację na mobile, żeby przewijało do góry
    $('.nav-item').on('click', function() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
    
    // Obsługa zwijania/rozwijania menu mobilnego
    const menuToggle = $('#mobileMenuToggle');
    const sidebar = $('.app-sidebar');
    const content = $('.app-content');
    
    // Sprawdź zapisany stan menu
    const menuHidden = localStorage.getItem('mobileMenuHidden') === 'true';
    if (menuHidden) {
        sidebar.addClass('mobile-hidden');
        menuToggle.addClass('menu-hidden');
        content.addClass('menu-hidden');
    }
    
    menuToggle.on('click', function() {
        const isHidden = sidebar.hasClass('mobile-hidden');
        
        if (isHidden) {
            // Pokaż menu
            sidebar.removeClass('mobile-hidden');
            menuToggle.removeClass('menu-hidden');
            content.removeClass('menu-hidden');
            localStorage.setItem('mobileMenuHidden', 'false');
        } else {
            // Ukryj menu
            sidebar.addClass('mobile-hidden');
            menuToggle.addClass('menu-hidden');
            content.addClass('menu-hidden');
            localStorage.setItem('mobileMenuHidden', 'true');
        }
    });
    
    // Obsługa mobilnego przycisku wylogowania
    $('#mobileLogoutBtn').on('click', function() {
        $('#logoutBtn').trigger('click');
    });
});
</script>

</body>
</html>
