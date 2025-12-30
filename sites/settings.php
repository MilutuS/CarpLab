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
