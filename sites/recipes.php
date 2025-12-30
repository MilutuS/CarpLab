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
                <!-- Nagłówek zwijany na mobile -->
                <div class="card-header-collapse d-lg-none" data-bs-toggle="collapse" data-bs-target="#addRecipeCollapse" aria-expanded="false">
                    <h5 class="fw-bold mb-0 text-primary d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-plus-circle me-2"></i>Dodaj przepis</span>
                        <i class="bi bi-chevron-down collapse-icon"></i>
                    </h5>
                </div>
                
                <!-- Zawartość formularza - zwijana na mobile -->
                <div class="collapse d-lg-block" id="addRecipeCollapse">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4 text-primary d-none d-lg-block">Dodaj przepis</h5>
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
        </div>
        
        <div class="col-lg-7 order-lg-1">
            <div id="recipesList" class="row g-3"></div>
        </div>
    </div>
</div>
