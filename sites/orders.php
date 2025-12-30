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
