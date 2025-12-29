<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Partacz-Fishing</title>
    
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
            <span>Partacz-Fishing</span>
        </div>
        
        <div class="nav-menu nav" id="mainTabs" role="tablist">
            <a class="nav-item active" data-bs-toggle="tab" data-bs-target="#home" type="button">
                <i class="bi bi-house-fill"></i> <span>Strona główna</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#products" type="button">
                <i class="bi bi-grid-fill"></i> <span>Baza produktów</span>
            </a>
            <a class="nav-item" data-bs-toggle="tab" data-bs-target="#recipes" type="button">
                <i class="bi bi-journal-bookmark-fill"></i> <span>Przepisy</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#stock" type="button">
                <i class="bi bi-clipboard-data-fill"></i> <span>Stan magazynowy</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                <i class="bi bi-bag-fill"></i> <span>Zakupy</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#executed" type="button">
                <i class="bi bi-clock-history"></i> <span>Historia</span>
            </a>
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#finished-balls" type="button">
                <i class="bi bi-basket-fill"></i> <span>Gotowe kulki</span>
            </a>
            
            <a class="nav-item require-login d-none" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                <i class="bi bi-gear-fill"></i> <span>Ustawienia</span>
            </a>
            
             <a class="nav-item require-admin d-none" id="history-tab-nav" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="bi bi-activity"></i> <span>Logi</span>
            </a>
             <a class="nav-item require-admin d-none" id="users-tab-nav" data-bs-toggle="tab" data-bs-target="#users" type="button">
                <i class="bi bi-people-fill"></i> <span>Użytkownicy</span>
            </a>
             <a class="nav-item require-admin d-none" id="pending-recipes-tab-nav" data-bs-toggle="tab" data-bs-target="#pending-recipes" type="button">
                <i class="bi bi-hourglass-split"></i> <span>Oczekujące przepisy</span>
            </a>
             <a class="nav-item require-admin d-none" id="backup-tab-nav" data-bs-toggle="tab" data-bs-target="#backup" type="button">
                <i class="bi bi-shield-lock-fill"></i> <span>Admin</span>
            </a>
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
            
            <!-- STRONA GŁÓWNA -->
            <div class="tab-pane fade show active" id="home">
                <div class="home-hero mb-5">
                    <div class="container py-5">
                        <div class="row align-items-center">
                            <div class="col-lg-6 mb-4 mb-lg-0">
                                <h1 class="display-4 fw-bold mb-3">Witaj w Świecie Wędkarskich Kulek</h1>
                                <p class="lead text-muted mb-4">Twój asystent do tworzenia idealnych zanęt i kulek proteinowych. Zarządzaj składnikami, twórz przepisy i dziel się swoimi najlepszymi recepturami z innymi wędkarzami.</p>

                            </div>
                            <div class="col-lg-6">
                                <img src="https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&h=600&fit=crop" alt="Wędkarstwo" class="img-fluid rounded-4 shadow-lg">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mb-5">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="clean-card h-100 text-center">
                                <div class="card-body p-4">
                                    <div class="feature-icon mb-3">
                                        <i class="bi bi-bucket-fill text-primary" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4 class="fw-bold mb-3">Zarządzaj składnikami</h4>
                                    <p class="text-muted">Prowadź bazę swoich składników, śledź stany magazynowe i nigdy nie zabraknie Ci potrzebnych produktów.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="clean-card h-100 text-center">
                                <div class="card-body p-4">
                                    <div class="feature-icon mb-3">
                                        <i class="bi bi-journal-bookmark-fill text-success" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4 class="fw-bold mb-3">Twórz przepisy</h4>
                                    <p class="text-muted">Zapisuj swoje sprawdzone receptury na kulki i zanęty. Obliczaj automatycznie proporcje składników.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="clean-card h-100 text-center">
                                <div class="card-body p-4">
                                    <div class="feature-icon mb-3">
                                        <i class="bi bi-people-fill text-info" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4 class="fw-bold mb-3">Dziel się wiedzą</h4>
                                    <p class="text-muted">Publikuj swoje przepisy i korzystaj z receptur innych wędkarzów. Razem tworzymy społeczność.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mb-5">
                    <div class="row align-items-center">
                        <div class="col-lg-6 order-lg-2 mb-4 mb-lg-0">
                            <img src="https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800&h=600&fit=crop" alt="Kulki proteinowe" class="img-fluid rounded-4 shadow">
                        </div>
                        <div class="col-lg-6 order-lg-1">
                            <h2 class="display-5 fw-bold mb-3 text-white">Profesjonalne narzędzie dla wędkarzy</h2>
                            <p class="text-white-50 mb-4">Nasza aplikacja została stworzona z myślą o wędkarzach, którzy poważnie podchodzą do przygotowania zanęt i kulek proteinowych. Niezależnie czy jesteś początkującym, czy doświadczonym karpiarzem - znajdziesz tu wszystkie narzędzia potrzebne do tworzenia idealnych receptur.</p>
                            <ul class="list-unstyled">
                                <li class="mb-2 text-white"><i class="bi bi-check-circle-fill text-success me-2"></i>Precyzyjne obliczenia proporcji</li>
                                <li class="mb-2 text-white"><i class="bi bi-check-circle-fill text-success me-2"></i>Automatyczne przeliczanie na gramy</li>
                                <li class="mb-2 text-white"><i class="bi bi-check-circle-fill text-success me-2"></i>Historia wykonanych przepisów</li>
                                <li class="mb-2 text-white"><i class="bi bi-check-circle-fill text-success me-2"></i>Zarządzanie zakupami</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- SLIDER PARTNERÓW -->
                <div class="partners-section py-5 bg-light" style="display: none;">
                    <div class="container">
                        <h3 class="text-center fw-bold mb-4">Nasi Partnerzy</h3>
                        <p class="text-center text-muted mb-5">Współpracujemy z najlepszymi markami wędkarskimi</p>
                        
                        <div class="partners-slider-wrapper position-relative">
                            <button class="slider-btn slider-btn-prev" onclick="moveSlider(-1)">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="partners-slider" id="partnersSlider">
                                <a href="https://www.starbaits.com" target="_blank" class="partner-item">
                                    <img src="uploads/partners/starbaits.png" alt="Starbaits" onerror="this.parentElement.innerHTML='<div class=\'partner-placeholder\'>Starbaits</div>'">
                                </a>
                                <a href="https://www.mainline-baits.com" target="_blank" class="partner-item">
                                    <img src="uploads/partners/mainline.png" alt="Mainline" onerror="this.parentElement.innerHTML='<div class=\'partner-placeholder\'>Mainline</div>'">
                                </a>
                                <a href="https://www.ccmoore.com" target="_blank" class="partner-item">
                                    <img src="uploads/partners/ccmoore.png" alt="CC Moore" onerror="this.parentElement.innerHTML='<div class=\'partner-placeholder\'>CC Moore</div>'">
                                </a>
                                <a href="https://www.nashbait.co.uk" target="_blank" class="partner-item">
                                    <img src="uploads/partners/nash.png" alt="Nash" onerror="this.parentElement.innerHTML='<div class=\'partner-placeholder\'>Nash Bait</div>'">
                                </a>
                                <a href="https://www.dynamitebaits.com" target="_blank" class="partner-item">
                                    <img src="uploads/partners/dynamite.png" alt="Dynamite Baits" onerror="this.parentElement.innerHTML='<div class=\'partner-placeholder\'>Dynamite Baits</div>'">
                                </a>
                                <a href="https://www.sonstof.com" target="_blank" class="partner-item">
                                    <img src="uploads/partners/sonstof.png" alt="Sonubaits" onerror="this.parentElement.innerHTML='<div class=\'partner-placeholder\'>Sonubaits</div>'">
                                </a>
                            </div>
                            <button class="slider-btn slider-btn-next" onclick="moveSlider(1)">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="products">
                <div class="page-header d-flex justify-content-between align-items-end">
                    <div>
                        <h1 class="page-title">Moja Spiżarniaa</h1>
                        <p class="page-subtitle">Zarządzaj składnikami do Twoich kulek</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4 order-lg-2 require-login d-none">
                        <div class="clean-card mb-4">
                            <div class="card-body">
                                <h5 class="fw-bold mb-4 d-flex align-items-center"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Nowy Składnik</h5>
                                <form id="addProductForm">
                                    <div class="mb-3">
                                        <label class="form-label">Nazwa produktu</label>
                                        <input type="text" class="form-control" id="productName" placeholder="Np. Mączka Rybna" required>
                                    </div>
                                    
                                    <div class="row g-2 mb-3">
                                        <div class="col-7">
                                            <label class="form-label">Ilość pocz.</label>
                                            <input type="number" class="form-control" id="productWeight" step="0.01" required>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label">Jednostka</label>
                                            <select class="form-select" id="productUnit">
                                                <option value="g">Gramy (g)</option>
                                                <option value="ml">Mililitry (ml)</option>
                                                <option value="szt">Sztuki</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Kalkulator ceny za 1000 jednostek</label>
                                        <div class="bg-light p-3 rounded-3 mb-2">
                                            <div class="mb-2">
                                                <label class="form-label small">Cena zakupu</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="boughtPrice" step="0.01" min="0" placeholder="np. 15.99">
                                                    <span class="input-group-text">PLN</span>
                                                </div>
                                                <small class="text-muted">Użyje ilości z pola "Ilość pocz." powyżej</small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="calculatePricePer1000()">
                                                <i class="bi bi-calculator me-1"></i> Przelicz cenę za 1000j
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Cena za 1000 jednostek</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-muted border-end-0">PLN</span>
                                            <input type="number" class="form-control border-start-0 ps-0" id="productPrice" step="0.01" min="0" placeholder="Wpisz lub przelicz">
                                        </div>
                                        <small class="text-muted">Możesz wpisać ręcznie lub użyć kalkulatora powyżej</small>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Opis produktu</label>
                                        <textarea class="form-control" id="productDescription" rows="3" placeholder="Dodatkowe informacje o produkcie..."></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Link do zakupu</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-link-45deg"></i></span>
                                            <input type="url" class="form-control border-start-0 ps-0" id="productLink" placeholder="https://sklep.pl/produkt">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Zdjęcie produktu</label>
                                        <div class="d-flex gap-2 mb-2">
                                            <button type="button" class="btn btn-outline-primary flex-fill" onclick="document.getElementById('productImage').click()">
                                                <i class="bi bi-image me-1"></i> Wgraj plik
                                            </button>
                                            <button type="button" class="btn btn-outline-primary flex-fill" onclick="openCamera('product')">
                                                <i class="bi bi-camera me-1"></i> Zrób zdjęcie
                                            </button>
                                        </div>
                                        <input type="file" id="productImage" accept="image/*" hidden>
                                        <div id="productImagePreview" class="mt-2 text-center"></div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 py-3">
                                        Dodaj Składnik
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8 order-lg-1">
                        <div id="productsList" class="row g-3">
                            </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="recipes">
                <div class="page-header">
                    <h1 class="page-title">Receptury</h1>
                    <p class="page-subtitle">Twoje tajne mieszanki</p>
                </div>
                
                <div class="mb-3 require-login d-none">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="filterMyRecipes">
                            <i class="bi bi-person-fill me-1"></i>Twoje przepisy
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="filterPublicRecipes">
                            <i class="bi bi-globe me-1"></i>Publiczne przepisy
                        </button>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-lg-5 order-lg-2 require-login d-none">
                        <div class="clean-card position-sticky" style="top: 20px;">
                            <div class="card-body">
                                <h5 class="fw-bold mb-4 text-primary">Dodaj przepis</h5>
                                <form id="addRecipeForm">
                                    <div class="mb-3">
                                        <label class="form-label">Nazwa miksu</label>
                                        <input type="text" class="form-control" id="recipeName" required placeholder="Np. Squid & Octopus">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Notatki</label>
                                        <textarea class="form-control" id="recipeDescription" rows="2" placeholder="Opis działania, pora roku..."></textarea>
                                    </div>

                                    <div class="p-3 bg-light rounded-4 mb-3 border border-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold small text-uppercase text-muted d-flex align-items-center mb-0">
                                                <i class="bi bi-bucket me-2"></i> Baza (Suche)
                                            </h6>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <input type="radio" class="btn-check" name="dryUnitMode" id="modePercent" value="percent" checked onchange="toggleDryUnit('percent')">
                                                <label class="btn btn-outline-primary fw-bold" for="modePercent">%</label>
                                                
                                                <input type="radio" class="btn-check" name="dryUnitMode" id="modeGrams" value="grams" onchange="toggleDryUnit('grams')">
                                                <label class="btn btn-outline-primary fw-bold" for="modeGrams">Gramy</label>
                                            </div>
                                        </div>
                                        
                                        <div id="dryIngredientsList"></div>
                                        
                                        <button type="button" class="btn btn-white border w-100 mt-2 shadow-sm" onclick="addIngredientField('dry')">
                                            <i class="bi bi-plus-lg me-1"></i> Dodaj składnik
                                        </button>
                                        
                                        <div id="dryPercentInfo" class="mt-3 p-2 bg-white rounded-3 border" style="display:none;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="small fw-bold text-muted">Suma procentów:</span>
                                                <span class="fs-5 fw-bold" id="dryPercentTotal">0%</span>
                                            </div>
                                            <div class="progress mt-2" style="height: 8px;">
                                                <div id="dryPercentBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span class="small text-muted">Pozostało:</span>
                                                <span class="small fw-bold text-primary" id="dryPercentRemaining">100%</span>
                                            </div>
                                        </div>
                                        
                                        <div id="dryTotalInfo" class="mt-2 text-end small fw-bold text-muted" style="display:none;">
                                            Suma wagi: <span id="dryTotalWeight">0</span>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3 bg-light rounded-4 mb-4 border border-light">
                                        <h6 class="fw-bold mb-3 small text-uppercase text-muted d-flex align-items-center">
                                            <i class="bi bi-droplet me-2"></i> Zalewa (Mokre)
                                        </h6>
                                        <div id="wetIngredientsList"></div>
                                        <button type="button" class="btn btn-white border w-100 mt-2 shadow-sm" onclick="addIngredientField('wet')">
                                            <i class="bi bi-plus-lg me-1"></i> Dodaj składnik (ml)
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="recipeIsPublic">
                                            <label class="form-check-label" for="recipeIsPublic">
                                                <i class="bi bi-globe"></i> Udostępnij publicznie
                                                <small class="text-muted d-block">Inni użytkownicy będą widzieć ten przepis</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Zdjęcie gotowych kulek</label>
                                        <div class="d-flex gap-2 mb-2">
                                            <button type="button" class="btn btn-outline-primary flex-fill" onclick="document.getElementById('recipeImage').click()">
                                                <i class="bi bi-image me-1"></i> Wgraj plik
                                            </button>
                                            <button type="button" class="btn btn-outline-primary flex-fill" onclick="openCamera('recipe')">
                                                <i class="bi bi-camera me-1"></i> Zrób zdjęcie
                                            </button>
                                        </div>
                                        <input type="file" id="recipeImage" accept="image/*" hidden>
                                        <div id="recipeImagePreview" class="mt-2 text-center"></div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 py-3">Zapisz Recepturę</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-7 order-lg-1">
                        <div id="recipesList" class="row g-3"></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="stock">
                <div class="page-header">
                    <h1 class="page-title">Magazyn</h1>
                    <p class="page-subtitle">Szybki podgląd stanu produktów</p>
                </div>
                
                <div class="clean-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-2 text-muted fw-bold border-0 col-photo">Foto</th>
                                    <th class="text-muted fw-bold border-0">Produkt</th>
                                    <th class="text-muted fw-bold border-0 text-center col-qty">Ilość</th>
                                    <th class="text-muted fw-bold border-0 d-none d-sm-table-cell text-center">Wartość</th> 
                                    <th class="text-center pe-2 text-muted fw-bold border-0 col-action">Akcje</th>
                                </tr>
                            </thead>
                            <tbody id="stockTableBody" class="border-top-0">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="orders">
                <div class="page-header">
                    <h1 class="page-title">Lista Zakupów</h1>
                    <p class="page-subtitle">Czego brakuje do kolejnego przepisu?</p>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="clean-card sticky-top" style="top: 20px; z-index: 1;">
                            <div class="card-body">
                                <h5 class="fw-bold mb-4">Dodaj do listy</h5>
                                <form id="addOrderForm">
                                    <div class="d-flex bg-light p-1 rounded-3 mb-3 border">
                                        <input type="radio" class="btn-check" name="orderProductType" id="orderTypeNew" value="new" checked>
                                        <label class="btn btn-sm flex-fill rounded-2 border-0 fw-bold" for="orderTypeNew">Nowy</label>
                                        
                                        <input type="radio" class="btn-check" name="orderProductType" id="orderTypeExisting" value="existing">
                                        <label class="btn btn-sm flex-fill rounded-2 border-0 fw-bold" for="orderTypeExisting">Z Bazy</label>
                                    </div>

                                    <div id="orderNewProductFields" class="mb-3">
                                        <input type="text" class="form-control" id="orderProductName" placeholder="Co trzeba kupić?">
                                    </div>
                                    <div id="orderExistingProductFields" class="mb-3" style="display:none;">
                                        <select class="form-select" id="orderProductSelect"></select>
                                    </div>
                                    
                                    <div class="row g-2 mb-3">
                                        <div class="col-7">
                                            <input type="number" class="form-control" id="orderQuantity" placeholder="Ile?" required>
                                        </div>
                                        <div class="col-5">
                                            <select class="form-select" id="orderUnit">
                                                <option value="g">g</option>
                                                <option value="ml">ml</option>
                                                <option value="szt">szt</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="input-group mb-4">
                                        <span class="input-group-text bg-white text-muted">PLN</span>
                                        <input type="number" class="form-control border-start-0" id="orderPrice" placeholder="Cena (opcjonalnie)">
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2">
                                        <i class="bi bi-plus-lg me-2"></i> Dodaj pozycję
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div id="ordersList"></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="executed">
                <div class="page-header">
                    <h1 class="page-title">Historia Produkcji</h1>
                    <p class="page-subtitle">Wykonane przepisy - możesz je cofnąć</p>
                </div>
                <div id="executedRecipesList" class="mt-4"></div>
            </div>
            
            <!-- GOTOWE KULKI -->
            <div class="tab-pane fade" id="finished-balls">
                <div class="page-header">
                    <h1 class="page-title">Gotowe kulki</h1>
                    <p class="page-subtitle">Zarządzaj zapasem gotowych kulek</p>
                </div>
                
                <div class="mb-4">
                    <button class="btn btn-success" id="addFinishedBallBtn">
                        <i class="bi bi-plus-circle me-2"></i>Dodaj kulki z przepisu
                    </button>
                </div>
                
                <div id="finishedBallsList"></div>
            </div>
            
            <div class="tab-pane fade" id="settings">
                 <div class="page-header">
                    <h1 class="page-title">Ustawienia</h1>
                    <p class="page-subtitle">Personalizuj swój profil i preferencje</p>
                </div>
                <div class="clean-card p-4">
                    <form id="userSettingsForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Maksymalne wartości dla paska postępu</label>
                            <p class="text-muted small mb-3">Określ maksymalne wartości dla każdej jednostki, do których będzie obliczany pasek wypełnienia produktów</p>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Gramy (g)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="maxProductWeightG" value="5000" min="100" max="50000" step="100">
                                        <span class="input-group-text">g</span>
                                    </div>
                                    <div class="form-text small">Pasek pełny przy tej ilości gram</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Mililitry (ml)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="maxProductWeightMl" value="2000" min="100" max="20000" step="100">
                                        <span class="input-group-text">ml</span>
                                    </div>
                                    <div class="form-text small">Pasek pełny przy tej ilości ml</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Sztuki (szt)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="maxProductWeightSzt" value="100" min="10" max="1000" step="10">
                                        <span class="input-group-text">szt</span>
                                    </div>
                                    <div class="form-text small">Pasek pełny przy tej ilości sztuk</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Mnożnik przy przepisach</label>
                            <p class="text-muted small mb-2">Określ przez co mnożyć ilość suchych produktów aby obliczyć rzeczywistą ilość kulek</p>
                            <div class="input-group">
                                <input type="number" class="form-control" id="recipeMultiplier" value="1.7" min="1" max="5" step="0.1">
                                <span class="input-group-text">x</span>
                            </div>
                            <div class="form-text">Przykład: 1.7x = z 1kg suchych składników otrzymasz 1.7kg kulek</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Zapisz ustawienia
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="tab-pane fade" id="history">
                 <div class="page-header">
                    <h1 class="page-title">Logi Systemowe</h1>
                    <p class="page-subtitle">Pełna historia wszystkich operacji</p>
                </div>
                <div class="clean-card p-4">
                    <button class="btn btn-danger mb-3" onclick="clearLogs()"><i class="bi bi-trash me-2"></i>Wyczyść logi</button>
                    <div class="table-responsive">
                        <table id="activityHistoryTable" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 60px;"></th>
                                    <th>Opis</th>
                                    <th>Użytkownik</th>
                                    <th>Data i czas</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="users">
                 <div class="page-header">
                    <h1 class="page-title">Zarządzanie Użytkownikami</h1>
                    <p class="page-subtitle">Lista wszystkich użytkowników systemu</p>
                </div>
                <div class="clean-card p-4">
                    <div class="mb-4">
                        <input type="text" id="userSearch" class="form-control" placeholder="🔍 Szukaj użytkownika...">
                    </div>
                    <div id="usersList"></div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="backup">
                <div class="page-header">
                     <h1 class="page-title">Kopie Zapasowe</h1>
                </div>
                <div class="clean-card p-4">
                    <div class="mb-3">
                        <button class="btn btn-success me-2" onclick="createBackup()"><i class="bi bi-cloud-arrow-down-fill me-2"></i>Utwórz Backup</button>
                        <button class="btn btn-danger" onclick="clearLogs()"><i class="bi bi-trash me-2"></i>Wyczyść logi</button>
                    </div>
                    <div id="backupsList"></div>
                </div>
            </div>

            <!-- Zakładka oczekujących przepisów -->
            <div class="tab-pane fade require-admin" id="pending-recipes">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0 text-white">Oczekujące przepisy</h4>
                    <span class="badge bg-warning" id="pendingRecipesCount">0</span>
                </div>
                
                <div id="pendingRecipesList"></div>
            </div>

        </div>
    </main>
</div>

<div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
            <div class="modal-body p-0 position-relative">
                <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 450px; object-fit: cover; background: #000;"></video>
                <canvas id="cameraCanvas" style="display: none;"></canvas>
                <div class="position-absolute bottom-0 start-0 w-100 p-4 d-flex justify-content-center gap-4" style="background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);">
                    <button type="button" class="btn btn-light rounded-circle shadow" style="width: 56px; height: 56px;" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                    <button type="button" class="btn btn-primary rounded-circle shadow-lg" style="width: 72px; height: 72px; border: 4px solid rgba(255,255,255,0.2);" onclick="capturePhoto()"><i class="bi bi-camera-fill fs-3"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="recipeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            
            <div class="position-relative bg-light" style="height: 250px; overflow: hidden;">
                <img id="viewRecipeImage" src="" class="w-100 h-100" style="object-fit: cover;">
                <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                    <h3 id="viewRecipeTitle" class="text-white fw-bold mb-0 text-shadow">Nazwa Przepisu</h3>
                    <p id="viewRecipeAuthor" class="text-white-50 small mb-0"></p>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 shadow-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="text-uppercase text-secondary fw-bold small mb-2">Notatki / Opis</h6>
                    <div id="viewRecipeDesc" class="p-3 bg-light rounded-3 text-dark border border-light" style="white-space: pre-line;">
                        </div>
                </div>
                
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="badge bg-primary bg-opacity-10 text-primary border px-3 py-2">
                            <i class="bi bi-calculator me-2"></i>Mnożnik: <span id="viewRecipeMultiplier" class="fw-bold">1.7x</span>
                        </div>
                        <small class="text-muted">Rzeczywista ilość kulek = suche składniki × mnożnik</small>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100" style="background-color: #fff;">
                            <h6 class="fw-bold text-primary mb-3 d-flex align-items-center">
                                <i class="bi bi-bucket-fill me-2"></i> Baza (Suche)
                            </h6>
                            <ul id="viewRecipeDry" class="list-group list-group-flush small">
                                </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100" style="background-color: #fff;">
                            <h6 class="fw-bold text-info mb-3 d-flex align-items-center">
                                <i class="bi bi-droplet-fill me-2"></i> Zalewa (Mokre)
                            </h6>
                            <ul id="viewRecipeWet" class="list-group list-group-flush small">
                                </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productionModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="productionModalTitle">Lista Produkcyjna</h5>
                    <p class="text-muted small mb-0">Odważ składniki i zaznacz gotowe</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 p-3 rounded-3 mb-4 require-login">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-warning mb-1">
                                <i class="bi bi-box-arrow-down me-2"></i>Zarządzanie stanem magazynowym
                            </div>
                            <div class="small text-muted">Czy składniki mają być zdjęte z magazynu?</div>
                        </div>
                        <div class="form-check form-switch form-check-reverse">
                            <input class="form-check-input fs-5" type="checkbox" id="reduceStockSwitch" checked>
                            <label class="form-check-label fw-bold" for="reduceStockLabel" id="reduceStockLabel">
                                Zdejmuj ze stanu
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="bg-info bg-opacity-10 border border-info border-opacity-25 p-4 rounded-3 mb-4 require-logout">
                    <div class="text-center">
                        <i class="bi bi-info-circle fs-2 text-info mb-3"></i>
                        <h6 class="fw-bold text-info mb-2">Wymagane logowanie</h6>
                        <p class="text-muted mb-3">Aby wykonać przepis i zobaczyć szczegóły dostępności składników, musisz się zalogować.</p>
                        <button class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Zaloguj się
                        </button>
                    </div>
                </div>
                
                <div class="bg-light p-4 rounded-4 mb-4 border border-light shadow-sm require-login">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-uppercase text-secondary small mb-2">Ile chcesz zrobić?</label>
                            <div class="input-group input-group-lg">
                                <input type="number" id="prodGrams" class="form-control border-0 shadow-sm fw-bold text-primary" value="1000" step="100">
                                <span class="input-group-text border-0 bg-white shadow-sm fw-bold text-muted">g</span>
                                <button class="btn btn-primary shadow-sm px-4" id="btnProdRecalculate">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Przelicz
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-uppercase text-secondary small mb-2">Mnożnik</label>
                            <div class="input-group input-group-lg">
                                <input type="number" id="prodMultiplier" class="form-control border-0 shadow-sm fw-bold text-success" value="1.7" step="0.1" min="1" max="5">
                                <span class="input-group-text border-0 bg-white shadow-sm fw-bold text-muted">x</span>
                            </div>
                            <div class="form-text small">Rzeczywista ilość kulek</div>
                        </div>
                    </div>
                </div>

                <div id="productionListArea">
                    <div class="text-center py-5 text-muted opacity-50">
                        <i class="bi bi-basket fs-1 mb-2 d-block"></i>
                        <span>Wybierz wagę i kliknij Przelicz...</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-0 pt-3 bg-white">
                <button type="button" class="btn btn-light border px-5" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-success px-5 flex-grow-1 fw-bold shadow-sm require-login" id="btnProdConfirm" disabled>
                    <i class="bi bi-check-lg me-2"></i> <span id="btnProdConfirmText">Gotowe - zdejmij ze stanu</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal logowania -->
<div class="modal fade" id="loginModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="loginTitle">Zaloguj się</span>
                    <!--<span id="registerTitle" class="d-none">Rejestracja</span>-->
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Formularz logowania -->
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Nazwa użytkownika</label>
                        <input type="text" class="form-control" id="loginUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasło</label>
                        <input type="password" class="form-control" id="loginPassword" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Zaloguj</button>
                    <!--<div class="text-center mt-3">
                        <small>Nie masz konta? <a href="#" id="showRegister">Zarejestruj się</a></small>
                    </div>-->
                </form>
                
                <!-- Formularz rejestracji -->
                <form id="registerForm" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Nazwa użytkownika</label>
                        <input type="text" class="form-control" id="registerUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="registerEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasło (min. 6 znaków)</label>
                        <input type="password" class="form-control" id="registerPassword" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Zarejestruj</button>
                    <div class="text-center mt-3">
                        <small>Masz już konto? <a href="#" id="showLogin">Zaloguj się</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal dodawania kulek z przepisu -->
<div class="modal fade" id="addFinishedBallModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dodaj kulki z przepisu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addFinishedBallForm">
                    <div class="mb-3">
                        <label class="form-label">Wybierz przepis</label>
                        <select class="form-select" id="ballRecipeSelect" required>
                            <option value="">-- Wybierz przepis --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ilość kulek (kg)</label>
                        <input type="number" class="form-control" id="ballQuantity" step="0.01" min="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-2"></i>Dodaj kulki
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal zarządzania ilością kulek -->
<div class="modal fade" id="manageBallModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Zarządzaj kulkami</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle fs-4 me-3"></i>
                        <div>
                            <h6 class="mb-1" id="manageBallName"></h6>
                            <p class="mb-0 small">Aktualny stan: <strong class="fs-5" id="manageBallQuantity"></strong> kg</p>
                        </div>
                    </div>
                </div>
                
                <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#addQuantityTab" type="button">
                            <i class="bi bi-plus-circle me-2"></i>Dodaj
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#removeQuantityTab" type="button">
                            <i class="bi bi-dash-circle me-2"></i>Usuń
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="addQuantityTab">
                        <form id="addQuantityForm">
                            <input type="hidden" id="manageBallId">
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-plus-square text-success me-2"></i>Ilość do dodania (kg)
                                </label>
                                <input type="number" class="form-control form-control-lg" id="addQuantityInput" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-journal-text me-2"></i>Opis (opcjonalnie)
                                </label>
                                <input type="text" class="form-control" id="addQuantityDescription" placeholder="np. Nowa produkcja z 15.12">
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-circle me-2"></i>Dodaj ilość
                            </button>
                        </form>
                    </div>
                    
                    <div class="tab-pane fade" id="removeQuantityTab">
                        <form id="removeQuantityForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-dash-square text-warning me-2"></i>Ilość do odjęcia (kg)
                                </label>
                                <input type="number" class="form-control form-control-lg" id="removeQuantityInput" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-journal-text me-2"></i>Opis (opcjonalnie)
                                </label>
                                <input type="text" class="form-control" id="removeQuantityDescription" placeholder="np. Wyjazd nad jezioro - 2.5kg">
                            </div>
                            <button type="submit" class="btn btn-warning btn-lg w-100">
                                <i class="bi bi-arrow-down-circle me-2"></i>Usuń ilość
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal historii kulek -->
<div class="modal fade" id="ballHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historia zmian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="historyBallName"></h6>
                <div id="ballHistoryList" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Podglądu Produktu -->
<div class="modal fade" id="productViewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Szczegóły produktu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="viewProductImage" src="" class="img-fluid rounded-3" style="max-height: 250px; object-fit: cover; width: 100%;" onerror="this.src='uploads/default.png'">
                </div>
                <h4 class="mb-2" id="viewProductName"></h4>
                <p class="text-muted mb-3" id="viewProductDescription"></p>
                
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Stan magazynowy</small>
                            <div class="fs-5 fw-bold" id="viewProductWeight"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block" id="viewProductPriceLabel">Cena za 1000g</small>
                            <div class="fs-5 fw-bold text-success" id="viewProductPrice"></div>
                        </div>
                    </div>
                </div>
                
                <div id="viewProductLinkContainer" class="d-none">
                    <a id="viewProductLink" href="" target="_blank" class="btn btn-primary w-100">
                        <i class="bi bi-cart3 me-2"></i>Przejdź do sklepu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="script.js?v=<?php echo time(); ?>"></script>

<script>
$(document).ready(function() {
    // Poprawka na mobilne menu - logi i backup przenieśmy do zakładki "Więcej" jeśli brakuje miejsca,
    // ale w tym projekcie mamy tylko 5 głównych ikon, co idealnie pasuje do dolnego paska.
    
    // Obsługa kliknięcia w nawigację na mobile, żeby przewijało do góry
    $('.nav-item').on('click', function() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
});
</script>

</body>
</html>