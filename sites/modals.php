<!-- Modal kamery -->
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

<!-- Modal szczegółów przepisu -->
<div class="modal fade" id="recipeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            
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

<!-- Modal produkcji -->
<div class="modal fade" id="productionModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
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

<!-- Modal dodawania użytkownika przez admina -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Dodaj nowego użytkownika
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label class="form-label">Nazwa użytkownika</label>
                        <input type="text" class="form-control" id="newUsername" required placeholder="np. jan_kowalski">
                        <div class="form-text">Minimum 3 znaki, bez spacji</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="newUserEmail" required placeholder="jan@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasło</label>
                        <input type="password" class="form-control" id="newUserPassword" required minlength="6" placeholder="Minimum 6 znaków">
                        <div class="form-text">Hasło musi mieć minimum 6 znaków</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newUserIsAdmin">
                            <label class="form-check-label" for="newUserIsAdmin">
                                <i class="bi bi-shield-check me-1"></i>Uprawnienia administratora
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle me-2"></i>Dodaj użytkownika
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
