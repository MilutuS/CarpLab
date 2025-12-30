<div class="tab-pane fade" id="products">
    <div class="page-header d-flex justify-content-between align-items-end">
        <div>
            <h1 class="page-title">Moja Spiżarnia</h1>
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
