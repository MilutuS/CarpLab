let products = [];
let recipes = [];
let allRecipes = []; // Wszystkie przepisy (do filtrowania)
let recipeFilter = 'my'; // 'my' lub 'public'
let executedRecipes = [];
let orders = [];
let activityLog = []; // Cache dla historii aktywności
let currentCameraTarget = null;
let cameraStream = null;
let currentProductionRecipeId = null;
let currentUser = null; // Zalogowany użytkownik
let userSettings = { max_product_weight: 5000, recipe_multiplier: 1.7 }; // Ustawienia użytkownika

// Sprawdź sesję użytkownika
function checkSession() {
  fetch("config.php?action=check_session")
    .then(response => response.json())
    .then(data => {
      if (data.blocked) {
        Swal.fire({
          icon: 'error',
          title: 'Konto zablokowane',
          text: data.message || 'Twoje konto zostało zablokowane. Skontaktuj się z administratorem.',
          confirmButtonText: 'OK'
        }).then(() => {
          showLoginModal();
        });
        currentUser = null;
        updateUserUI(false);
        loadProducts();
        loadRecipes();
        return;
      }
      
      if (data.logged_in) {
        currentUser = data.user;
        
        // Jeśli admin jest przełączony na innego użytkownika
        if (data.is_admin_switched && data.switched_user) {
          currentUser = data.switched_user;
          currentUser.is_admin_switched = true;
          currentUser.original_admin = data.original_admin;
        }
        
        updateUserUI(true);
        // Wczytaj ustawienia użytkownika
        loadUserSettings();
        // Wczytaj dane po zalogowaniu
        loadProducts();
        loadRecipes();
        loadExecutedRecipes();
        loadOrders();
        loadFinishedBalls(); // Dodaj ładowanie gotowych kulek
        
        // Ładuj dane admina tylko dla adminów
        if (currentUser.is_admin == 1) {
          loadActivityHistory();
          loadBackups();
          loadUsers();
        }
      } else {
        currentUser = null;
        updateUserUI(false);
        // Wczytaj tylko dane publiczne
        loadProducts();
        loadRecipes();
        // Nie pokazuj modala automatycznie
      }
    });
}

// Aktualizuj UI w zależności od stanu logowania
function updateUserUI(loggedIn) {
  if (loggedIn) {
    $('#userLoggedIn').removeClass('d-none');
    $('#userLoggedOut').addClass('d-none');
    $('#currentUsername').text(currentUser.username);
    
    // Jeśli admin jest przełączony, pokaż wskaźnik
    if (currentUser.is_admin_switched) {
      $('#adminSwitchIndicator').remove(); // usuń poprzedni wskaźnik
      $('#userLoggedIn').prepend(`
        <div id="adminSwitchIndicator" class="alert alert-warning alert-dismissible fade show p-2 mb-2" role="alert">
          <i class="bi bi-person-check me-2"></i>
          <strong>Tryb administratora:</strong> Przełączono na użytkownika ${currentUser.username}
          <button type="button" class="btn btn-sm btn-outline-warning ms-2" onclick="switchBackToAdmin()">
            <i class="bi bi-arrow-left me-1"></i>Powrót do admina
          </button>
        </div>
      `);
    } else {
      $('#adminSwitchIndicator').remove();
    }
    
    // Pokaż elementy wymagające logowania, ukryj elementy dla wylogowanych
    $('.require-login').removeClass('d-none').css('display', '');
    $('.require-logout').addClass('d-none');
    
    // Pokaż elementy dla admina
    if (currentUser && currentUser.is_admin == 1) {
      $('.require-admin').removeClass('d-none').css('display', '');
    } else {
      $('.require-admin').addClass('d-none');
    }
  } else {
    $('#userLoggedIn').addClass('d-none');
    $('#userLoggedOut').removeClass('d-none');
    
    // Wyczyść prywatne dane
    executedRecipes = [];
    orders = [];
    activityLog = [];
    
    // Wyczyść widoki HTML
    $('#executedRecipesList').empty();
    $('#ordersList').empty();
    $('#activityList').empty();
    $('#stockList').empty();
    
    // Ukryj elementy wymagające logowania i admina, pokaż elementy dla wylogowanych
    $('.require-login').addClass('d-none');
    $('.require-admin').addClass('d-none');
    $('.require-logout').removeClass('d-none').css('display', '');
    
    // Przełącz na zakładkę "Baza produktów" jeśli jesteśmy na ukrytej zakładce
    const activeTab = $('.nav-item.active').attr('data-bs-target');
    if (activeTab === '#stock' || activeTab === '#orders' || activeTab === '#executed' || activeTab === '#history' || activeTab === '#backup') {
      $('a[data-bs-target="#products"]').tab('show');
    }
  }
}

// Pokaż modal logowania
function showLoginModal() {
  $('#loginModal').modal('show');
}

// Inicjalizacja przy załadowaniu strony
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM loaded, initializing...");

  // Sprawdzenie czy jQuery jest dostępne
  if (typeof $ === "undefined") {
    alert("jQuery nie załadowało się! Sprawdź połączenie internetowe.");
    return;
  }

  // Przywróć ostatnią aktywną zakładkę
  const lastActiveTab = localStorage.getItem("activeTab");
  if (lastActiveTab) {
    const tabButton = document.querySelector(
      `a[data-bs-target="${lastActiveTab}"]`
    );
    if (tabButton) {
      const tab = new bootstrap.Tab(tabButton);
      tab.show();
    }
  }

  // Zapisuj aktywną zakładkę przy zmianie
  document.querySelectorAll('a[data-bs-toggle="tab"]').forEach((button) => {
    button.addEventListener("shown.bs.tab", function (event) {
      const target = event.target.getAttribute("data-bs-target");
      localStorage.setItem("activeTab", target);

      // Odświeżanie danych przy zmianie zakładki
      if (target === "#products") loadProducts();
      if (target === "#recipes") loadRecipes();
      if (target === "#stock") loadProducts(); // Stan to też produkty
      if (target === "#orders") loadOrders();
      if (target === "#executed") loadExecutedRecipes();
      if (target === "#history") loadActivityHistory();
      if (target === "#users") loadUsers();
      if (target === "#pending-recipes") loadPendingRecipes();
      if (target === "#settings") loadUserSettings();
      if (target === "#backup") loadBackups();
    });
  });

  // Sprawdź sesję i wczytaj dane
  checkSession();

  // Obsługa formularzy
  const productForm = document.getElementById("addProductForm");
  if (productForm) {
    productForm.addEventListener("submit", handleAddProduct);
  }

  const recipeForm = document.getElementById("addRecipeForm");
  if (recipeForm) {
    recipeForm.addEventListener("submit", handleAddRecipe);
  }

  const orderForm = document.getElementById("addOrderForm");
  if (orderForm) {
    orderForm.addEventListener("submit", handleAddOrder);
  }

  // Podgląd zdjęć z pliku z kompresją
  $("#productImage").on("change", async function () {
    if (this.files && this.files[0]) {
      try {
        const compressed = await compressImage(this.files[0]);
        // Stwórz nowy DataTransfer aby podmienić plik
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressed);
        this.files = dataTransfer.files;
        previewImage(this, "#productImagePreview");
      } catch(e) {
        console.error('Compression error:', e);
        previewImage(this, "#productImagePreview");
      }
    }
  });

  $("#recipeImage").on("change", async function () {
    if (this.files && this.files[0]) {
      try {
        const compressed = await compressImage(this.files[0]);
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressed);
        this.files = dataTransfer.files;
        previewImage(this, "#recipeImagePreview");
      } catch(e) {
        console.error('Compression error:', e);
        previewImage(this, "#recipeImagePreview");
      }
    }
  });

  // Przełączanie między nowym produktem a wyborem z listy w zamówieniach
  $('input[name="orderProductType"]').on("change", function () {
    if ($("#orderTypeNew").is(":checked")) {
      $("#orderNewProductFields").slideDown(200);
      $("#orderExistingProductFields").slideUp(200);
      $("#orderProductName").prop("required", true);
      $("#orderProductSelect").prop("required", false);
    } else {
      $("#orderNewProductFields").slideUp(200);
      $("#orderExistingProductFields").slideDown(200);
      $("#orderProductName").prop("required", false);
      $("#orderProductSelect").prop("required", true);

      // Odśwież Select2 przy pokazaniu (fix dla display: none)
      setTimeout(() => {
        $("#orderProductSelect").select2({
          theme: "bootstrap-5",
          placeholder: "Wybierz produkt z listy...",
          width: "100%",
        });
      }, 250);
    }
  });

  // Admin mode jest teraz kontrolowany przez backend (is_admin w bazie danych)
  
  // Inicjalizuj licznik procentów - pokaż od razu dla domyślnego trybu %
  setTimeout(() => {
    if ($("#modePercent").is(":checked")) {
      $("#dryPercentInfo").show();
      calculateDryPercent();
    }
  }, 100);
});

// ============= KALKULATOR CENY =============

function calculatePricePer1000() {
  const boughtAmount = parseFloat($('#productWeight').val());
  const boughtPrice = parseFloat($('#boughtPrice').val());
  
  if (!boughtAmount || !boughtPrice || boughtAmount <= 0 || boughtPrice <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Brak danych',
      text: 'Podaj ilość początkową i cenę zakupu',
      confirmButtonColor: '#3b82f6'
    });
    return;
  }
  
  // Przelicz: (cena / ilość) * 1000
  const pricePer1000 = (boughtPrice / boughtAmount) * 1000;
  
  $('#productPrice').val(pricePer1000.toFixed(2));
  
  Swal.fire({
    icon: 'success',
    title: 'Przeliczono!',
    html: `<div class="text-start">
      <p><strong>Ilość początkowa:</strong> ${boughtAmount.toFixed(2)} jednostek</p>
      <p><strong>Cena zakupu:</strong> ${boughtPrice.toFixed(2)} PLN</p>
      <hr>
      <p class="text-primary fs-5 mb-0"><strong>Cena za 1000j:</strong> ${pricePer1000.toFixed(2)} PLN</p>
    </div>`,
    confirmButtonColor: '#3b82f6'
  });
}

// ============= PRODUKTY =============

function loadProducts() {
  $.ajax({
    url: "config.php?action=get_products",
    method: "GET",
    dataType: "json",
    success: function (data) {
      products = data;
      renderProducts();
      renderStockTable();
    },
    error: function (xhr, status, error) {
      console.error("Load products error:", error);
    },
  });
}

function renderProducts() {
  const container = $("#productsList");
  container.empty();

  if (products.length === 0) {
    container.html(
      '<div class="col-12"><div class="p-5 text-center text-muted border border-dashed rounded-4 bg-light">Twój magazyn jest pusty. Dodaj pierwszy składnik!</div></div>'
    );
    return;
  }

  products.forEach((product) => {
    const defaultImage = "uploads/default.png";
    const productImage = product.image || defaultImage;
    const priceInfo = product.price_per_1000
      ? `<span class="badge-price">${parseFloat(product.price_per_1000).toFixed(
          2
        )} zł</span>`
      : "";

    // Określ maksymalną wartość dla danej jednostki
    let maxWeight;
    if (product.unit === 'g') {
      maxWeight = userSettings.max_product_weight_g || 5000;
    } else if (product.unit === 'ml') {
      maxWeight = userSettings.max_product_weight_ml || 2000;
    } else if (product.unit === 'szt') {
      maxWeight = userSettings.max_product_weight_szt || 100;
    } else {
      maxWeight = 5000;
    }

    // Dynamiczna ocena stanu
    const percentageFilled = (parseFloat(product.weight) / maxWeight) * 100;
    let stockColor, progressBarColor;
    
    if (percentageFilled <= 20) {
      stockColor = "text-danger";
      progressBarColor = "bg-danger";
    } else if (percentageFilled <= 40) {
      stockColor = "text-warning";
      progressBarColor = "bg-warning";
    } else if (percentageFilled < 80) {
      stockColor = "text-success";
      progressBarColor = "bg-success";
    } else {
      stockColor = "text-primary";
      progressBarColor = "bg-primary";
    }

    const card = `
            <div class="col-6 col-md-4 col-xl-3">
                <div class="product-grid-card">
                    <div class="product-img-container">
                        <img src="${productImage}" class="product-img" alt="${
      product.name
    }" onerror="this.src='${defaultImage}'">
                        <div class="position-absolute top-0 end-0 p-2">
                            ${priceInfo}
                        </div>
                    </div>
                    <div class="product-details">
                        <h5 class="product-title text-truncate" title="${
                          product.name
                        }">${product.name}</h5>
                        ${product.description ? `<p class="small text-muted mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${product.description}</p>` : ''}
                        <div class="mb-2 d-flex align-items-baseline gap-2">
                            <span class="fs-4 fw-bold ${stockColor}">${
      product.weight
    }</span>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem;">${
                              product.unit
                            }</small>
                        </div>
                        
                        <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 4px; overflow: hidden;">
                            <div class="progress-bar ${progressBarColor}" role="progressbar" style="width: ${Math.min(
      (product.weight / maxWeight) * 100,
      100
    )}%"></div>
                        </div>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-sm btn-light" onclick="viewProduct('${
                          product.id
                        }')" title="Podgląd">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-light" onclick="addWeightToProduct('${
                          product.id
                        }')" title="Edytuj">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger" onclick="deleteProduct('${
                          product.id
                        }')" title="Usuń">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    container.append(card);
  });
}

function handleAddProduct(e) {
  e.preventDefault();

  const formData = new FormData();
  formData.append("action", "add_product");
  formData.append("name", $("#productName").val());
  formData.append("weight", $("#productWeight").val());
  formData.append("unit", $("#productUnit").val());
  formData.append("price_per_1000", $("#productPrice").val() || 0);
  formData.append("description", $("#productDescription").val() || '');
  formData.append("link", $("#productLink").val() || '');

  const imageFile = $("#productImage")[0].files[0];
  if (imageFile) {
    formData.append("image", imageFile);
  }

  $.ajax({
    url: "config.php",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        Swal.fire("Sukces!", "Produkt został dodany", "success");
        $("#addProductForm")[0].reset();
        $("#productImagePreview").empty();
        setTimeout(() => loadProducts(), 500);
      } else if (response.require_login) {
        showLoginModal();
        Swal.fire("Wymagane logowanie", "Zaloguj się aby dodać produkt", "warning");
      } else {
        Swal.fire(
          "Błąd",
          response.error || "Nie udało się dodać produktu",
          "error"
        );
      }
    },
    error: function () {
      Swal.fire("Błąd", "Wystąpił problem z serwerem", "error");
    },
  });
}

function addWeightToProduct(productId) {
  const product = products.find((p) => p.id === productId);
  
  Swal.fire({
    title: `Edytuj: ${product.name}`,
    html: `
        <div class="mb-3">
            <label class="form-label">Korekta stanu magazynowego</label>
            <input type="number" id="swal-weight" class="form-control" placeholder="+/- ilość" step="0.01">
            <small class="text-muted">Obecnie: ${product.weight} ${product.unit}</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Jednostka</label>
            <select id="swal-unit" class="form-select">
                <option value="g" ${product.unit === 'g' ? 'selected' : ''}>Gramy (g)</option>
                <option value="ml" ${product.unit === 'ml' ? 'selected' : ''}>Mililitry (ml)</option>
                <option value="szt" ${product.unit === 'szt' ? 'selected' : ''}>Sztuki</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Cena za 1000 jednostek (PLN)</label>
            <input type="number" id="swal-price" class="form-control" step="0.01" min="0" value="${product.price_per_1000 || ''}">
        </div>
        <div class="mb-3">
            <label class="form-label">Opis produktu</label>
            <textarea id="swal-description" class="form-control" rows="3" placeholder="Dodatkowe informacje...">${product.description || ''}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Link do zakupu</label>
            <input type="url" id="swal-link" class="form-control" placeholder="https://sklep.pl/produkt" value="${product.link || ''}">
        </div>
        <div class="mb-3">
            <label class="form-label">Zdjęcie</label>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light border flex-fill" onclick="document.getElementById('swal-image').click()">
                    <i class="bi bi-image me-1"></i> Wgraj plik
                </button>
                <button type="button" class="btn btn-light border flex-fill" onclick="Swal.close(); openCamera('productEdit_${productId}')">
                    <i class="bi bi-camera me-1"></i> Zrób zdjęcie
                </button>
            </div>
            <input type="file" id="swal-image" class="form-control d-none" accept="image/*">
            <div id="swal-image-preview" class="mt-2 text-center"></div>
        </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Zapisz",
    cancelButtonText: "Anuluj",
    didOpen: () => {
      // Podgląd zdjęcia
      document.getElementById("swal-image").addEventListener("change", function() {
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
            document.getElementById("swal-image-preview").innerHTML = 
              '<img src="' + e.target.result + '" class="rounded border" style="max-height: 100px;">';
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    },
    preConfirm: () => {
      const weight = document.getElementById("swal-weight").value;
      const unit = document.getElementById("swal-unit").value;
      const price = document.getElementById("swal-price").value;
      const description = document.getElementById("swal-description").value;
      const link = document.getElementById("swal-link").value;
      const imageFile = document.getElementById("swal-image").files[0];
      
      return {
        weight: weight ? parseFloat(weight) : null,
        unit: unit,
        price: price ? parseFloat(price) : null,
        description: description || '',
        link: link || '',
        image: imageFile || null
      };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append("action", "update_product_weight");
      formData.append("id", productId);
      
      if (result.value.weight !== null) {
        formData.append("weight", result.value.weight);
      }
      
      formData.append("unit", result.value.unit);
      
      if (result.value.price !== null) {
        formData.append("price_per_1000", result.value.price);
      }
      
      formData.append("description", result.value.description);
      formData.append("link", result.value.link);
      
      if (result.value.image) {
        formData.append("image", result.value.image);
      }

      $.ajax({
        url: "config.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire("Zapisano!", "Produkt zaktualizowany", "success");
            loadProducts();
          } else if (response.require_login) {
            showLoginModal();
            Swal.fire("Wymagane logowanie", "Zaloguj się aby edytować produkt", "warning");
          } else {
            Swal.fire("Błąd", response.error || "Nie udało się zapisać", "error");
          }
        },
        error: function() {
          Swal.fire("Błąd", "Problem z połączeniem", "error");
        }
      });
    }
  });
}

function viewProductHistory(productId) {
  const product = products.find((p) => p.id === productId);
  if (!product) return;

  $.get(`config.php?action=get_product_history&product_id=${productId}`, function (response) {
    const history = typeof response === 'string' ? JSON.parse(response) : response;
    
    console.log("Product history:", history);
    
    let historyHtml = '<div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">';
    
    if (!history || history.length === 0) {
      historyHtml += '<div class="list-group-item text-muted text-center">Brak historii zmian dla tego produktu.</div>';
    } else {
      // Sortuj od najnowszych
      history.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
      
      history.forEach((entry) => {
        let changeText = '';
        let changeClass = '';
        let actionIcon = '';
        
        // Określ typ akcji i zmianę
        if (entry.action === 'created') {
          changeText = `Utworzono (${entry.amount} ${entry.unit})`;
          changeClass = 'text-info';
          actionIcon = '<i class="bi bi-plus-circle me-1"></i>';
        } else if (entry.action === 'added') {
          changeText = `+${entry.amount} ${entry.unit}`;
          changeClass = 'text-success';
          actionIcon = '<i class="bi bi-arrow-up-circle me-1"></i>';
        } else if (entry.action === 'removed' || entry.action === 'used') {
          changeText = `-${entry.amount} ${entry.unit}`;
          changeClass = 'text-danger';
          actionIcon = '<i class="bi bi-arrow-down-circle me-1"></i>';
        } else {
          // Dla nieznanych akcji
          const amount = entry.amount || entry.change || 0;
          changeText = amount > 0 ? `+${amount} ${entry.unit}` : `${amount} ${entry.unit}`;
          changeClass = amount > 0 ? 'text-success' : 'text-danger';
          actionIcon = '<i class="bi bi-arrow-left-right me-1"></i>';
        }
        
        const noteText = entry.note || '';
        
        historyHtml += `
          <div class="list-group-item d-flex justify-content-between align-items-start py-3">
            <div style="flex: 1;">
              <div class="fw-bold ${changeClass} mb-1">
                ${actionIcon}${changeText}
              </div>
              <small class="text-muted d-block">${entry.timestamp}</small>
              ${noteText ? `<div class="small text-secondary mt-1 fst-italic">${noteText}</div>` : ''}
            </div>
          </div>
        `;
      });
    }
    
    historyHtml += '</div>';
    
    Swal.fire({
      title: `📊 Historia: ${product.name}`,
      html: historyHtml,
      width: '650px',
      confirmButtonText: 'Zamknij',
      confirmButtonColor: '#3b82f6',
      customClass: {
        popup: 'text-start'
      }
    });
  });
}

function viewProduct(productId) {
  let product = products.find(p => p.id === productId);
  
  // Jeśli produkt nie istnieje lokalnie (np. z publicznego przepisu), pobierz z backendu
  if (!product) {
    $.ajax({
      url: 'config.php?action=get_product&id=' + productId,
      method: 'GET',
      dataType: 'json',
      success: function(response) {
        if (response.success && response.product) {
          showProductModal(response.product);
        } else {
          Swal.fire('Błąd', 'Nie znaleziono produktu', 'error');
        }
      },
      error: function() {
        Swal.fire('Błąd', 'Nie udało się pobrać danych produktu', 'error');
      }
    });
    return;
  }
  
  showProductModal(product);
}

function showProductModal(product) {
  const defaultImage = 'uploads/default.png';
  
  $('#viewProductImage').attr('src', product.image || defaultImage);
  $('#viewProductName').text(product.name);
  $('#viewProductDescription').text(product.description || 'Brak opisu');
  $('#viewProductWeight').text(`${product.weight} ${product.unit}`);
  $('#viewProductPrice').text(product.price_per_1000 ? `${parseFloat(product.price_per_1000).toFixed(2)} zł` : 'Brak');
  
  // Dynamiczna etykieta ceny w zależności od jednostki
  const priceLabel = product.unit === 'ml' ? 'Cena za 1000ml' : 'Cena za 1000g';
  $('#viewProductPriceLabel').text(priceLabel);
  
  if (product.link && product.link.trim() !== '') {
    // Dodaj protokół jeśli brakuje
    let fullLink = product.link;
    if (!fullLink.match(/^https?:\/\//i)) {
      fullLink = 'https://' + fullLink;
    }
    $('#viewProductLink').attr('href', fullLink);
    $('#viewProductLinkContainer').removeClass('d-none').show();
  } else {
    $('#viewProductLinkContainer').addClass('d-none').hide();
  }
  
  const modal = new bootstrap.Modal(document.getElementById('productViewModal'));
  modal.show();
}

function deleteProduct(productId) {
  Swal.fire({
    title: "Usunąć produkt?",
    text: "Tej operacji nie można cofnąć.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Tak, usuń",
    cancelButtonText: "Anuluj",
    confirmButtonColor: "#ef4444",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "config.php",
        method: "POST",
        data: { action: "delete_product", id: productId },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire(
              "Usunięto!",
              "Produkt został usunięty z bazy.",
              "success"
            );
            loadProducts();
          } else if (response.require_login) {
            showLoginModal();
            Swal.fire("Wymagane logowanie", "Zaloguj się aby usunąć produkt", "warning");
          }
        },
      });
    }
  });
}

// ============= PRZEPISY =============

function loadRecipes() {
  $.ajax({
    url: "config.php?action=get_recipes",
    method: "GET",
    dataType: "json",
    success: function (data) {
      allRecipes = data;
      filterRecipes();
    },
  });
}

function filterRecipes() {
  if (!currentUser) {
    // Niezalogowany - pokaż tylko publiczne i zatwierdzone
    recipes = allRecipes.filter(r => r.is_public == 1 && r.status === 'approved');
  } else if (recipeFilter === 'my') {
    // Zalogowany - pokaż tylko własne (wszystkie statusy)
    recipes = allRecipes.filter(r => r.user_id === currentUser.id);
  } else {
    // Zalogowany - pokaż tylko publiczne zatwierdzone (bez swoich oczekujących)
    recipes = allRecipes.filter(r => r.is_public == 1 && r.status === 'approved');
  }
  renderRecipes();
}

function renderRecipes() {
  const container = $("#recipesList");
  container.empty();

  if (recipes.length === 0) {
    container.html(
      '<div class="col-12"><div class="p-5 text-center text-muted border border-dashed rounded-4 bg-light">Nie masz jeszcze żadnych przepisów.</div></div>'
    );
    return;
  }

  recipes.forEach((recipe) => {
    const defaultRecipeImage = "uploads/default.png";
    const recipeImage = recipe.image || defaultRecipeImage;
    // Dodaj timestamp aby wymusić odświeżenie cache
    const recipeImageWithCache = recipeImage + '?t=' + new Date().getTime();
    const dryCount = recipe.ingredients.filter(
      (ing) => ing.type === "dry"
    ).length;
    const wetCount = recipe.ingredients.filter(
      (ing) => ing.type === "wet"
    ).length;

    const card = `
        <div class="col-12 mb-3">
            <div class="recipe-card-layout">
                
                <div class="recipe-img-wrapper">
                    <img src="${recipeImageWithCache}" onerror="this.src='${defaultRecipeImage}'">
                    <div class="recipe-img-overlay" onclick="viewRecipe('${
                      recipe.id
                    }')">
                        <i class="bi bi-eye-fill text-white fs-2"></i>
                    </div>
                </div>
                
                <div class="recipe-body">
                    
                    <div class="recipe-header">
                        <div class="recipe-title-block" style="cursor: pointer;" onclick="viewRecipe('${
                          recipe.id
                        }')">
                            <h5 class="fw-bold mb-1 text-truncate" title="${
                              recipe.name
                            }">${recipe.name}</h5>
                            <p class="text-secondary small mb-0 text-truncate">${
                              recipe.description || "Brak notatek"
                            }</p>
                            ${recipe.user_id !== currentUser?.id && recipe.author_username ? `<p class="text-muted small mb-0"><i class="bi bi-person-circle me-1"></i>Autor: ${recipe.author_username}</p>` : ''}
                        </div>
                        
                        <div class="recipe-actions">
                            <button class="btn btn-sm btn-light border" onclick="viewRecipe('${
                              recipe.id
                            }')">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${
                              currentUser && (recipe.user_id === currentUser.id || currentUser.is_admin == 1)
                                ? `<button class="btn btn-sm btn-light border" onclick="editRecipe('${
                                    recipe.id
                                  }')">
                                      <i class="bi bi-pencil"></i>
                                   </button>
                                   <button class="btn btn-sm btn-light text-danger border" onclick="deleteRecipe('${
                                     recipe.id
                                   }')">
                                      <i class="bi bi-trash"></i>
                                   </button>`
                                : ""
                            }
                        </div>
                    </div>
                    
                    <div class="recipe-badges" onclick="viewRecipe('${
                      recipe.id
                    }')" style="cursor: pointer;">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border recipe-badge-item">
                            <i class="bi bi-bucket me-1"></i> ${dryCount} sypkich
                        </span>
                        <span class="badge bg-info bg-opacity-10 text-info border recipe-badge-item">
                            <i class="bi bi-droplet me-1"></i> ${wetCount} płynnych
                        </span>
                        ${
                          currentUser && recipe.user_id === currentUser.id && recipe.is_public == 1 ? 
                          (recipe.status === 'pending' ? 
                            '<span class="badge bg-warning text-dark border recipe-badge-item"><i class="bi bi-clock-history me-1"></i>Oczekuje na akceptację</span>' :
                          recipe.status === 'rejected' ? 
                            '<span class="badge bg-danger border recipe-badge-item"><i class="bi bi-x-circle me-1"></i>Odrzucony</span>' :
                          recipe.status === 'approved' ? 
                            '<span class="badge bg-success border recipe-badge-item"><i class="bi bi-check-circle me-1"></i>Zaakceptowany</span>' : 
                            '')
                          : ''
                        }
                    </div>
                    
                    <button class="btn btn-primary btn-sm w-100 mt-auto" onclick="executeRecipe('${
                      recipe.id
                    }')">
                        <i class="bi bi-measuring-cup me-2"></i> Przygotuj przepis
                    </button>

                </div>
            </div>
        </div>
    `;
    container.append(card);
  });
}

function viewRecipe(recipeId) {
  const recipe = recipes.find((r) => r.id == recipeId);
  if (!recipe) return;

  // Wypełnij dane podstawowe
  $("#viewRecipeTitle").text(recipe.name);
  $("#viewRecipeDesc").text(recipe.description || "Brak dodatkowych notatek.");
  
  // Pokaż mnożnik
  $("#viewRecipeMultiplier").text((recipe.multiplier || 1.7) + "x");
  
  // Pokaż autora jeśli to nie jest własny przepis
  if (recipe.user_id !== currentUser?.id && recipe.author_username) {
    $("#viewRecipeAuthor").html(`<i class="bi bi-person-circle me-1"></i>Autor: ${recipe.author_username}`).show();
  } else {
    $("#viewRecipeAuthor").hide();
  }

  const defaultImage = "uploads/default.png";
  const recipeImageWithCache = (recipe.image || defaultImage) + '?t=' + new Date().getTime();
  $("#viewRecipeImage").attr("src", recipeImageWithCache);

  // Wyczyść listy
  $("#viewRecipeDry").empty();
  $("#viewRecipeWet").empty();

  // Wypełnij składniki
  let hasDry = false;
  let hasWet = false;

  if (recipe.ingredients && recipe.ingredients.length > 0) {
    recipe.ingredients.forEach((ing) => {
      const prodName = ing.product_name || "Produkt usunięty";
      const viewButton = ing.product_id ? `<button class="btn btn-sm btn-link p-0 ms-2" onclick="viewProduct('${ing.product_id}')" title="Podgląd produktu"><i class="bi bi-eye"></i></button>` : '';

      if (ing.type === "dry") {
        hasDry = true;
        // Wyświetl gramy jeśli są dostępne, inaczej procenty
        const displayValue = ing.grams 
          ? `${ing.grams}g` 
          : `${Math.round(ing.percentage)}%`;
        const badgeClass = ing.grams ? 'bg-success' : 'bg-secondary';
        
        $("#viewRecipeDry").append(`
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>${prodName}${viewButton}</span>
                        <span class="fw-bold badge ${badgeClass}">${displayValue}</span>
                    </li>
                `);
      } else if (ing.type === "wet") {
        hasWet = true;
        $("#viewRecipeWet").append(`
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>${prodName}${viewButton}</span>
                        <span class="fw-bold badge bg-info text-dark">${ing.amount_per_kg} ml/szt</span>
                    </li>
                `);
      }
    });
  }

  if (!hasDry)
    $("#viewRecipeDry").html(
      '<li class="list-group-item text-muted fst-italic px-0">Brak składników suchych</li>'
    );
  if (!hasWet)
    $("#viewRecipeWet").html(
      '<li class="list-group-item text-muted fst-italic px-0">Brak składników płynnych</li>'
    );

  // Pokaż modal
  const modal = new bootstrap.Modal(
    document.getElementById("recipeDetailsModal")
  );
  modal.show();
}

function toggleDryUnit(mode) {
  const isGrams = mode === "grams";
  const placeholder = isGrams ? "Ilość (g)" : "% (np. 20)";
  const addon = isGrams ? "g" : "%";

  // Zaktualizuj istniejące pola
  $("#dryIngredientsList .input-group").each(function () {
    $(this).find("input").attr("placeholder", placeholder);
    $(this).find(".input-group-text").text(addon);
  });

  // Pokaż/ukryj podsumowanie wagi i procentów
  if (isGrams) {
    $("#dryTotalInfo").show();
    $("#dryPercentInfo").hide();
    calculateDryTotal(); // Przelicz na bieżąco
  } else {
    $("#dryTotalInfo").hide();
    $("#dryPercentInfo").show();
    calculateDryPercent(); // Przelicz procenty
  }
}

// Funkcja pomocnicza do liczenia sumy gramów na żywo
$(document).on("input", 'input[name="ingredient_amount_dry[]"]', function () {
  if ($("#modeGrams").is(":checked")) {
    calculateDryTotal();
  } else {
    calculateDryPercent();
  }
});

function calculateDryTotal() {
  let total = 0;
  $('input[name="ingredient_amount_dry[]"]').each(function () {
    const val = parseFloat($(this).val()) || 0;
    total += val;
  });
  $("#dryTotalWeight").text(total.toFixed(2) + " g");
}

function calculateDryPercent() {
  let total = 0;
  $('input[name="ingredient_amount_dry[]"]').each(function () {
    const val = parseFloat($(this).val()) || 0;
    total += val;
  });
  
  const remaining = 100 - total;
  const percentage = Math.min(total, 100);
  
  $("#dryPercentTotal").text(total.toFixed(1) + "%");
  
  // Pokaż pozostało lub przekroczono
  if (remaining >= 0) {
    $("#dryPercentRemaining").text(remaining.toFixed(1) + "%").removeClass("text-danger").addClass("text-primary");
  } else {
    $("#dryPercentRemaining").text("Przekroczono o " + Math.abs(remaining).toFixed(1) + "%").removeClass("text-primary").addClass("text-danger");
  }
  
  $("#dryPercentBar").css("width", percentage + "%");
  
  // Zmień kolor paska w zależności od wartości
  $("#dryPercentBar").removeClass("bg-primary bg-warning bg-danger bg-success");
  if (total > 100) {
    $("#dryPercentBar").addClass("bg-danger");
    $("#dryPercentBar").css("width", "100%"); // Pełny pasek na czerwono
  } else if (total >= 95) {
    $("#dryPercentBar").addClass("bg-success");
  } else if (total >= 80) {
    $("#dryPercentBar").addClass("bg-warning");
  } else {
    $("#dryPercentBar").addClass("bg-primary");
  }
}

function addIngredientField(type) {
  const ingredientId = "ing_" + Date.now();

  // Sprawdź jaki jest aktualny tryb dla suchych
  let isGramsMode = false;
  if (type === "dry") {
    isGramsMode = $("#modeGrams").is(":checked");
  }

  // Budowanie opcji select
  let options = '<option value="">Wyszukaj składnik...</option>';
  products.forEach((p) => {
    options += `<option value="${p.id}">${p.name} (magazyn: ${p.weight} ${p.unit})</option>`;
  });

  // Ustal placeholder i jednostkę
  let placeholder = type === "dry" ? "%" : "ml/szt";
  let addon = type === "dry" ? "%" : "ml";

  if (type === "dry" && isGramsMode) {
    placeholder = "Ilość (g)";
    addon = "g";
  }

  const container =
    type === "dry" ? "#dryIngredientsList" : "#wetIngredientsList";

  const html = `
    <div class="input-group mb-2" id="${ingredientId}">
        <select class="form-select ingredient-select" name="ingredient_product_${type}[]" style="max-width: 60%;">${options}</select>
        <input type="number" class="form-control" name="ingredient_amount_${type}[]" placeholder="${placeholder}" step="0.01" required>
        <span class="input-group-text bg-white text-secondary small" style="min-width: 40px; justify-content: center;">${addon}</span>
        <button type="button" class="btn btn-light border text-danger" onclick="$('#${ingredientId}').remove(); if($('#modeGrams').is(':checked')) { calculateDryTotal(); } else { calculateDryPercent(); }"><i class="bi bi-x-lg"></i></button>
    </div>
  `;

  $(container).append(html);

  $(`#${ingredientId} .ingredient-select`).select2({
    theme: "bootstrap-5",
    placeholder: "Wyszukaj...",
    width: "resolve",
    dropdownParent: $("#addRecipeForm").parent(),
  });
  
  // Po wybraniu produktu, przejdź na pole ilości
  $(`#${ingredientId} .ingredient-select`).on('select2:select', function() {
    $(`#${ingredientId} input[type="number"]`).focus();
  });
  
  // Automatycznie otwórz dropdown i ustaw focus
  setTimeout(() => {
    $(`#${ingredientId} .ingredient-select`).select2('open');
  }, 100);
  
  // Przelicz na bieżąco po dodaniu pola
  if (type === 'dry') {
    if ($("#modeGrams").is(":checked")) {
      calculateDryTotal();
    } else {
      calculateDryPercent();
    }
  }
}

function addIngredientFieldForEdit(type, productId, amount) {
  const ingredientId = "ing_" + Date.now() + Math.floor(Math.random() * 100);

  let options = '<option value="">Wyszukaj składnik...</option>';
  products.forEach((p) => {
    const selected = p.id == productId ? "selected" : "";
    options += `<option value="${p.id}" ${selected}>${p.name} (magazyn: ${p.weight} ${p.unit})</option>`;
  });

  // Sprawdź aktualny tryb dla suchych składników
  let placeholder = type === "dry" ? "%" : "ml/szt";
  let addon = type === "dry" ? "%" : "ml";
  
  if (type === "dry" && $("#modeGrams").is(":checked")) {
    placeholder = "Ilość (g)";
    addon = "g";
  }
  
  const container = type === "dry" ? "#dryIngredientsList" : "#wetIngredientsList";

  const html = `
        <div class="input-group mb-2" id="${ingredientId}">
            <select class="form-select ingredient-select" name="ingredient_product_${type}[]" style="max-width: 60%;">${options}</select>
            <input type="number" class="form-control" name="ingredient_amount_${type}[]" placeholder="${placeholder}" value="${amount}" step="0.01" required>
            <span class="input-group-text bg-white text-secondary small" style="min-width: 40px; justify-content: center;">${addon}</span>
            <button type="button" class="btn btn-light border text-danger" onclick="$('#${ingredientId}').remove(); if($('#modeGrams').is(':checked')) { calculateDryTotal(); } else { calculateDryPercent(); }"><i class="bi bi-x-lg"></i></button>
        </div>
    `;

  $(container).append(html);

  $(`#${ingredientId} .ingredient-select`).select2({
    theme: "bootstrap-5",
    placeholder: "Wyszukaj...",
    width: "resolve",
  });
  
  // Przelicz na bieżąco po dodaniu
  if (type === 'dry') {
    if ($("#modeGrams").is(":checked")) {
      calculateDryTotal();
    } else {
      calculateDryPercent();
    }
  }
}

function handleAddRecipe(e) {
  e.preventDefault();
  
  // Sprawdź czy użytkownik jest zalogowany
  if (!currentUser) {
    showLoginModal();
    Swal.fire("Wymagane logowanie", "Zaloguj się aby dodać przepis", "warning");
    return;
  }

  const dryIngredients = [];
  const wetIngredients = [];

  // Sprawdź czy tryb to gramy
  const isGramsMode = $("#modeGrams").is(":checked");
  let totalDryWeight = 0;

  // 1. Najpierw zbierz dane suchych
  const dryInputs = [];
  $('select[name="ingredient_product_dry[]"]').each(function (index) {
    const val = $(this).val();
    const amount = parseFloat(
      $('input[name="ingredient_amount_dry[]"]').eq(index).val()
    );
    if (val && amount) {
      dryInputs.push({ product_id: val, amount: amount });
      totalDryWeight += amount;
    }
  });

  // 2. Przetwórz suche
  if (isGramsMode) {
    if (totalDryWeight <= 0) {
      Swal.fire("Błąd", "Suma wagi składników musi być większa od 0.", "error");
      return;
    }

    // Zapisz zarówno gramy jak i procenty
    let currentSum = 0;

    dryInputs.forEach((item, index) => {
      let percentage;

      // Jeśli to ostatni element, oblicz go jako resztę do 100%
      if (index === dryInputs.length - 1) {
        percentage = 100 - currentSum;
        if (percentage < 0) percentage = 0;
      } else {
        percentage = Math.round((item.amount / totalDryWeight) * 100);
        currentSum += percentage;
      }

      dryIngredients.push({
        product_id: item.product_id,
        percentage: percentage,
        grams: item.amount, // Zapisz oryginalne gramy
        type: "dry",
      });
    });
  } else {
    // Tryb procentowy
    let totalPercent = 0;
    dryInputs.forEach((item) => {
      const rounded = Math.round(item.amount);
      dryIngredients.push({
        product_id: item.product_id,
        percentage: rounded,
        type: "dry",
      });
      totalPercent += rounded;
    });

    // Walidacja 100% (tylko w trybie %)
    if (dryIngredients.length > 0) {
      if (totalPercent > 100) {
        Swal.fire({
          icon: "error",
          title: "Za dużo składników!",
          text: `Suma składników suchych wynosi ${totalPercent}%. Nie może przekraczać 100%!`,
          confirmButtonColor: '#3b82f6'
        });
        return;
      } else if (Math.abs(totalPercent - 100) > 1) {
        Swal.fire({
          icon: "warning",
          title: "Uwaga",
          text: `Suma składników suchych wynosi ${totalPercent}%. Powinna wynosić 100%.`,
          confirmButtonColor: '#3b82f6'
        });
      }
    }
  }

  // 3. Mokre (bez zmian)
  $('select[name="ingredient_product_wet[]"]').each(function (index) {
    const val = $(this).val();
    const amount = parseFloat(
      $('input[name="ingredient_amount_wet[]"]').eq(index).val()
    );
    if (val && amount)
      wetIngredients.push({
        product_id: val,
        amount_per_kg: amount,
        type: "wet",
      });
  });

  const ingredients = [...dryIngredients, ...wetIngredients];
  if (ingredients.length === 0) {
    Swal.fire("Pusty przepis", "Dodaj przynajmniej jeden składnik.", "warning");
    return;
  }

  const editingId = $("#addRecipeForm").data("editing-id");
  const formData = new FormData();
  formData.append("action", editingId ? "update_recipe" : "add_recipe");
  if (editingId) formData.append("recipe_id", editingId);

  formData.append("name", $("#recipeName").val());
  formData.append("description", $("#recipeDescription").val());
  formData.append("ingredients", JSON.stringify(ingredients));
  formData.append("is_public", $("#recipeIsPublic").is(':checked') ? '1' : '0');
  const multiplierValue = $("#recipeMultiplierInput").val();
  formData.append("multiplier", multiplierValue && !isNaN(parseFloat(multiplierValue)) ? parseFloat(multiplierValue) : 1.7);

  const imageFile = $("#recipeImage")[0].files[0];
  if (imageFile) formData.append("image", imageFile);

  $.ajax({
    url: "config.php",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const msg = isGramsMode
          ? "Przepis zapisany (gramy przeliczono na pełne %)"
          : "Przepis zapisany.";
        Swal.fire("Gotowe", msg, "success");

        $("#addRecipeForm")[0].reset();
        $(
          "#dryIngredientsList, #wetIngredientsList, #recipeImagePreview"
        ).empty();
        $("#addRecipeForm").removeData("editing-id");
        $('#addRecipeForm button[type="submit"]').text("Zapisz Recepturę");

        // Reset do procentów
        $("#modePercent").prop("checked", true);
        toggleDryUnit("percent");

        loadRecipes();
      } else if (response.require_login) {
        showLoginModal();
        Swal.fire("Wymagane logowanie", "Zaloguj się aby dodać przepis", "warning");
      } else {
        Swal.fire("Błąd", "Nie udało się zapisać.", "error");
      }
    },
    error: function(xhr, status, error) {
      console.log('AJAX Error:', status, error, xhr);
      Swal.fire("Błąd AJAX", "Wystąpił błąd podczas wysyłania: " + error, "error");
    }
  });
}

function editRecipe(recipeId) {
  const recipe = recipes.find((r) => r.id == recipeId);
  if (!recipe) return;

  // Sprawdź uprawnienia
  if (!currentUser || (recipe.user_id !== currentUser.id && currentUser.is_admin != 1)) {
    Swal.fire('Błąd', 'Nie masz uprawnień do edycji tego przepisu', 'error');
    return;
  }

  // Reset formularza
  $("#addRecipeForm")[0].reset();

  // Sprawdź czy przepis ma składniki w gramach
  const hasGrams = recipe.ingredients.some(ing => ing.type === 'dry' && ing.grams);
  
  if (hasGrams) {
    $("#modeGrams").prop("checked", true);
    toggleDryUnit("grams");
  } else {
    $("#modePercent").prop("checked", true);
    toggleDryUnit("percent");
  }

  $("#dryIngredientsList, #wetIngredientsList").empty();

  // Ustaw dane
  $("#recipeName").val(recipe.name);
  $("#recipeDescription").val(recipe.description);
  $("#recipeIsPublic").prop("checked", recipe.is_public == 1);
  $("#recipeMultiplierInput").val(recipe.multiplier || 1.7);

  // Składniki
  recipe.ingredients.forEach((ing) => {
    if (ing.type === "dry") {
      // Użyj gramów jeśli są dostępne, inaczej procenty
      const amount = ing.grams || ing.percentage;
      addIngredientFieldForEdit("dry", ing.product_id, amount);
    }
    if (ing.type === "wet")
      addIngredientFieldForEdit("wet", ing.product_id, ing.amount_per_kg);
  });

  // Tryb edycji
  $("#addRecipeForm").data("editing-id", recipeId);
  $('#addRecipeForm button[type="submit"]').text("Zaktualizuj Recepturę");

  // Scroll
  document
    .getElementById("addRecipeForm")
    .scrollIntoView({ behavior: "smooth" });
}

function deleteRecipe(id) {
  const recipe = recipes.find((r) => r.id == id);
  if (!recipe) return;

  // Sprawdź uprawnienia
  if (!currentUser || (recipe.user_id !== currentUser.id && currentUser.is_admin != 1)) {
    Swal.fire('Błąd', 'Nie masz uprawnień do usuwania tego przepisu', 'error');
    return;
  }

  Swal.fire({
    title: "Usunąć przepis?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Usuń",
    cancelButtonText: "Anuluj",
    confirmButtonColor: "#ef4444",
  }).then((res) => {
    if (res.isConfirmed) {
      $.ajax({
        url: "config.php",
        method: "POST",
        data: { action: "delete_recipe", id: id },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            Swal.fire("Usunięto", "Przepis został usunięty", "success");
            loadRecipes();
          } else if (data.require_login) {
            showLoginModal();
            Swal.fire("Wymagane logowanie", "Zaloguj się aby usunąć przepis", "warning");
          }
        },
        error: function() {
          Swal.fire("Błąd", "Problem z połączeniem", "error");
        }
      });
    }
  });
}

function executeRecipe(recipeId) {
  const recipe = recipes.find((r) => r.id == recipeId);
  if (!recipe) return;

  // Sprawdź czy użytkownik jest zalogowany
  if (!currentUser) {
    Swal.fire({
      title: 'Wymagane logowanie',
      text: 'Aby wykonać przepis, musisz się zalogować.',
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Zaloguj się',
      cancelButtonText: 'Anuluj'
    }).then((result) => {
      if (result.isConfirmed) {
        $('#loginModal').modal('show');
      }
    });
    return;
  }

  currentProductionRecipeId = recipeId;

  // 1. Ustawienia początkowe modala
  $("#productionModalTitle").text(recipe.name);
  $("#prodGrams").val(1000); // Domyślnie 1kg
  
  // 2. Ustaw mnożnik z przepisu lub domyślny z ustawień użytkownika
  const recipeMultiplier = parseFloat(recipe.multiplier) || userSettings.recipe_multiplier || 1.7;
  $("#prodMultiplier").val(recipeMultiplier);
  
  $("#btnProdConfirm").prop("disabled", true); // Zablokuj przycisk dopóki nie przeliczy

  // 3. Pokaż modal
  const modal = new bootstrap.Modal(document.getElementById("productionModal"));
  modal.show();

  // 4. Automatycznie przelicz dla domyślnej wartości (1000g)
  calculateProduction();
}

// Funkcja przeliczająca (wywoływana przyciskiem "Przelicz" lub na starcie)
function calculateProduction() {
  const grams = parseFloat($("#prodGrams").val());
  const multiplier = parseFloat($("#prodMultiplier").val());
  
  if (!grams || grams <= 0) {
    Swal.fire("Błąd", "Podaj prawidłową wagę", "warning");
    return;
  }
  
  if (!multiplier || multiplier < 1 || multiplier > 5) {
    Swal.fire("Błąd", "Mnożnik musi być między 1 a 5", "warning");
    return;
  }

  const kg = grams / 1000;

  // Loader
  $("#productionListArea").html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-3 fw-medium text-muted">Przeliczanie receptury...</div>
        </div>
    `);
  $("#btnProdConfirm").prop("disabled", true);

  $.post(
    "config.php",
    {
      action: "execute_recipe",
      recipe_id: currentProductionRecipeId,
      kg_amount: kg,
      multiplier: multiplier,
      reduce_stock: $("#reduceStockSwitch").is(":checked"),
    },
    function (response) {
      let data;
      try {
        if (typeof response === "object") {
          data = response;
        } else {
          data = JSON.parse(response);
        }
      } catch (e) {
        console.error("Błąd parsowania:", response);
        $("#productionListArea").html(
          '<div class="alert alert-danger rounded-3">Błąd danych z serwera.</div>'
        );
        return;
      }

      if (!data) {
        $("#productionListArea").html(
          '<div class="alert alert-danger">Pusta odpowiedź.</div>'
        );
        return;
      }

      let html = "";

      // Podsumowanie mnożnika i rzeczywistej ilości
      if (data.multiplier && data.actual_balls_amount) {
        html += `
            <div class="bg-success bg-opacity-10 border border-success border-opacity-25 p-3 rounded-3 mb-3 shadow-sm">
                <div class="row g-2 text-center">
                    <div class="col-3">
                        <div class="small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Suche składniki</div>
                        <div class="fw-bold text-dark">${data.dry_weight} kg</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Mnożnik</div>
                        <div class="fw-bold text-success">× ${data.multiplier}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Rzeczywista ilość kulek</div>
                        <div class="fw-bold text-primary">${data.actual_balls_amount} kg</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Stan magazynu</div>
                        <div class="fw-bold ${data.reduce_stock ? 'text-warning' : 'text-info'}">${data.reduce_stock ? 'Redukcja' : 'Bez redukcji'}</div>
                    </div>
                </div>
            </div>`;
      }

      // Podsumowanie kosztów
      if (data.total_cost) {
        html += `
            <div class="d-flex justify-content-between align-items-center bg-white border border-primary border-opacity-25 p-3 rounded-3 mb-4 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Koszt całkowity</div>
                        <div class="fw-bold text-dark">${parseFloat(
                          data.total_cost
                        ).toFixed(2)} PLN</div>
                    </div>
                </div>
                <div class="text-end">
                     <div class="small text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Cena za 1kg kulek</div>
                     <div class="fw-bold text-primary">${parseFloat(
                       data.cost_per_kg
                     ).toFixed(2)} PLN</div>
                </div>
            </div>`;
      }

      // Generator sekcji (Suche / Mokre)
      const generateSection = (items, title, icon, colorClass) => {
        if (!items || items.length === 0) return "";

        let sectionHtml = `
                <div class="mb-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-${colorClass}">
                        <i class="bi ${icon}"></i> ${title}
                    </h6>
                    <div class="production-list-group">
            `;

        items.forEach((ing, idx) => {
          const available = parseFloat(ing.available);
          const needed = parseFloat(ing.needed);
          const hasEnough = available >= needed;

          // Logika klas CSS - tylko dla zalogowanych użytkowników
          const itemClass = currentUser && !hasEnough ? "danger" : "";
          const stockClass = currentUser && hasEnough ? "ok" : (currentUser ? "low" : "");
          const stockLabel = currentUser 
            ? (hasEnough ? `Magazyn: ${available.toFixed(2)}` : `BRAK! Masz: ${available.toFixed(2)}`)
            : "";

          // ID dla inputa
          const checkId = `check_${title.substring(0, 3)}_${idx}`;

          // OnClick handler do zmiany koloru tła (toggle class 'checked')
          const toggleScript =
            "this.closest('.production-item').classList.toggle('checked', this.checked)";

          sectionHtml += `
                    <label class="production-item ${itemClass}" for="${checkId}">
                        ${currentUser ? `<input class="production-checkbox" type="checkbox" id="${checkId}" onchange="${toggleScript}">` : ''}
                        
                        <div class="prod-details">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="product-name">${
                                  ing.product_name
                                }</div>
                                <div class="product-weight">${needed.toFixed(
                                  2
                                )} <small>${ing.unit}</small></div>
                            </div>
                            ${currentUser ? `<div class="stock-tag ${stockClass}">
                                ${stockLabel}
                            </div>` : ''}
                        </div>
                    </label>
                `;
        });
        sectionHtml += `</div></div>`;
        return sectionHtml;
      };

      const dryItems = data.calculated
        ? data.calculated.filter((i) => i.type === "dry")
        : [];
      const wetItems = data.calculated
        ? data.calculated.filter((i) => i.type === "wet")
        : [];

      html += generateSection(
        dryItems,
        "Suche (Baza)",
        "bi-bucket-fill",
        "secondary"
      );
      html += generateSection(
        wetItems,
        "Mokre (Zalewa)",
        "bi-droplet-fill",
        "info"
      );

      if (!data.success) {
        html += `
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 mt-4">
                <i class="bi bi-exclamation-octagon-fill fs-3 text-danger"></i>
                <div>
                    <strong class="d-block">Brakuje składników!</strong>
                    Nie możesz zdjąć towaru ze stanu, dopóki nie uzupełnisz magazynu.
                </div>
            </div>`;
      }

      $("#productionListArea").html(html);

      // Odblokuj przycisk
      $("#btnProdConfirm").prop("disabled", !data.success);
    }
  );
}

// Obsługa przycisków wewnątrz modala (trzeba to podpiąć raz po załadowaniu strony)
$(document).ready(function () {
  // Kliknięcie "Przelicz"
  $("#btnProdRecalculate").on("click", function () {
    calculateProduction();
  });

  // Enter w polu wagi też przelicza
  $("#prodGrams").on("keypress", function (e) {
    if (e.which == 13) calculateProduction();
  });
  
  // Enter w polu mnożnika też przelicza
  $("#prodMultiplier").on("keypress", function (e) {
    if (e.which == 13) calculateProduction();
  });
  
  // Obsługa przełącznika stanu magazynowego
  $("#reduceStockSwitch").on("change", function() {
    const isChecked = $(this).is(":checked");
    $("#reduceStockLabel").text(isChecked ? "Zdejmuj ze stanu" : "Nie zdejmuj ze stanu");
    $("#btnProdConfirmText").text(isChecked ? "Gotowe - zdejmij ze stanu" : "Gotowe - zapisz bez zmian stanu");
    // Automatycznie przelicz po zmianie
    if (currentProductionRecipeId) {
      calculateProduction();
    }
  });

  // Kliknięcie "Gotowe - zdejmij ze stanu"
  $("#btnProdConfirm").on("click", function () {
    const grams = parseFloat($("#prodGrams").val());
    const kg = grams / 1000;

    // Blokada przycisku żeby nie klikać 2 razy
    $(this)
      .prop("disabled", true)
      .html(
        '<div class="spinner-border spinner-border-sm me-2"></div> Zapisywanie...'
      );

    $.ajax({
      url: "config.php",
      method: "POST",
      data: {
        action: "confirm_execute_recipe",
        recipe_id: currentProductionRecipeId,
        kg_amount: kg,
        multiplier: parseFloat($("#prodMultiplier").val()),
        reduce_stock: $("#reduceStockSwitch").is(":checked"),
      },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          // Zamknij modal
          bootstrap.Modal.getInstance(
            document.getElementById("productionModal")
          ).hide();

          Swal.fire({
            icon: "success",
            title: "Sukces!",
            text: "Składniki zostały pobrane z magazynu.",
            timer: 2000,
            showConfirmButton: false,
          });

          loadProducts(); // Odśwież stany
          loadFinishedBalls(); // Odśwież gotowe kulki
        } else if (data.require_login) {
          showLoginModal();
          Swal.fire("Wymagane logowanie", "Zaloguj się aby wykonać przepis", "warning");
          // Przywróć przycisk
          $("#btnProdConfirm")
            .prop("disabled", false)
            .html(
              '<i class="bi bi-check-lg me-2"></i> Gotowe - zdejmij ze stanu'
            );
        } else {
          Swal.fire("Błąd", data.error || "Wystąpił błąd zapisu", "error");
          // Przywróć przycisk
          $("#btnProdConfirm")
            .prop("disabled", false)
            .html(
              '<i class="bi bi-check-lg me-2"></i> Gotowe - zdejmij ze stanu'
            );
        }
      },
      error: function() {
        Swal.fire("Błąd", "Problem z połączeniem", "error");
        $("#btnProdConfirm")
          .prop("disabled", false)
          .html('<i class="bi bi-check-lg me-2"></i> Gotowe - zdejmij ze stanu');
      }
    });
  });
});

// ============= ZAMÓWIENIA (ZAKUPY) =============

function loadOrders() {
  $.ajax({
    url: "config.php?action=get_orders",
    method: "GET",
    dataType: "json",
    success: function (data) {
      orders = data;
      renderOrders();
      populateOrderProductSelect();
    },
    error: function (xhr) {
      console.error("Error loading orders", xhr);
    },
  });
}

function renderOrders() {
  const container = $("#ordersList");
  container.empty();

  if (orders.length === 0) {
    container.html(
      '<div class="col-12"><div class="p-5 text-center text-muted border border-dashed rounded-4 bg-light">Brak produktów na liście zakupów. Wszystko masz!</div></div>'
    );
    return;
  }

  // Tworzenie listy
  const listGroup = $('<div class="d-flex flex-column gap-3"></div>');

  orders.forEach((order) => {
    const image = order.image || "uploads/default.png";

    const item = `
            <div class="clean-card p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <img src="${image}" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='uploads/default.png'">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">${order.name}</h6>
                        <div class="text-primary fw-bold">
                            ${order.quantity} <span class="text-muted small text-uppercase">${order.unit}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm d-flex align-items-center gap-2 px-3" onclick="moveOrderToStock('${order.id}')" title="Dodaj do stanu">
                        <i class="bi bi-check-lg"></i> <span class="d-none d-md-inline">Kupione</span>
                    </button>
                    <button class="btn btn-light text-danger btn-sm border" onclick="deleteOrder('${order.id}')" title="Usuń">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    listGroup.append(item);
  });

  container.append(listGroup);
}

function populateOrderProductSelect() {
  const select = $("#orderProductSelect");
  select.empty();
  select.append('<option value="">Wybierz produkt z bazy...</option>');

  products.forEach((product) => {
    select.append(
      `<option value="${product.id}" data-unit="${product.unit}">${product.name} (${product.weight} ${product.unit})</option>`
    );
  });

  // Inicjalizuj Select2 z motywem Bootstrap 5
  select.select2({
    theme: "bootstrap-5",
    placeholder: "Wybierz produkt...",
    width: "100%",
    dropdownParent: $("#orderExistingProductFields"), // Fix dla poprawnego wyświetlania
  });

  select.on("change", function () {
    const selectedOption = $(this).find(":selected");
    const unit = selectedOption.data("unit");
    if (unit) $("#orderUnit").val(unit);
  });
}

function handleAddOrder(e) {
  e.preventDefault();

  const isNew = $("#orderTypeNew").is(":checked");

  const formData = new FormData();
  formData.append("action", "add_order");
  formData.append("quantity", $("#orderQuantity").val());
  formData.append("unit", $("#orderUnit").val());
  formData.append("price_per_1000", $("#orderPrice").val() || 0);

  if (isNew) {
    formData.append("name", $("#orderProductName").val());
    // Dodaj plik tylko dla nowego
    /* Implementacja obrazka dla nowego - opcjonalne */
  } else {
    const prodId = $("#orderProductSelect").val();
    if (!prodId) {
      Swal.fire("Błąd", "Wybierz produkt", "warning");
      return;
    }

    const prod = products.find((p) => p.id == prodId);
    formData.append("name", prod.name);
    if (prod.image) formData.append("existing_image", prod.image);
  }

  $.ajax({
    url: "config.php",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "Dodano do listy zakupów",
          showConfirmButton: false,
          timer: 2000,
        });

        $("#addOrderForm")[0].reset();
        $("#orderProductSelect").val("").trigger("change");
        loadOrders();
      } else if (response.require_login) {
        showLoginModal();
        Swal.fire("Wymagane logowanie", "Zaloguj się aby dodać zamówienie", "warning");
      } else {
        Swal.fire("Błąd", response.error, "error");
      }
    },
  });
}

function moveOrderToStock(orderId) {
  Swal.fire({
    title: "Dodać do magazynu?",
    text: "Produkt zniknie z listy zakupów i powiększy stan magazynowy.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Tak, dodaj",
    cancelButtonText: "Anuluj",
    confirmButtonColor: "var(--primary)",
  }).then((res) => {
    if (res.isConfirmed) {
      $.ajax({
        url: "config.php",
        method: "POST",
        data: { action: "move_order_to_stock", order_id: orderId },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            Swal.fire("Sukces", "Stan magazynowy zaktualizowany.", "success");
            loadOrders();
            loadProducts();
          } else if (data.require_login) {
            showLoginModal();
            Swal.fire("Wymagane logowanie", "Zaloguj się aby przenieść do magazynu", "warning");
          } else {
            Swal.fire("Błąd", data.error, "error");
          }
        },
        error: function() {
          Swal.fire("Błąd", "Problem z połączeniem", "error");
        }
      });
    }
  });
}

function deleteOrder(id) {
  Swal.fire({
    title: "Usunąć z listy?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Usuń",
    cancelButtonText: "Nie",
    confirmButtonColor: "#ef4444",
  }).then((res) => {
    if (res.isConfirmed) {
      $.post(
        "config.php",
        { action: "delete_order", order_id: id },
        function (resp) {
          loadOrders();
        }
      );
    }
  });
}

// ============= STAN MAGAZYNOWY (TABELA) =============

function renderStockTable() {
  const tbody = $("#stockTableBody");
  tbody.empty();

  if (products.length === 0) {
    tbody.html(
      '<tr><td colspan="5" class="text-center py-4 text-muted">Magazyn pusty</td></tr>'
    );
    return;
  }

  products.forEach((product) => {
    const defaultImage = "uploads/default.png";
    const productImage = product.image || defaultImage;
    const price = product.price_per_1000 ? parseFloat(product.price_per_1000) : 0;

    // Oblicz wartość całkowitą
    let totalValue = price > 0 && product.weight > 0 ? (product.weight / 1000) * price : 0;
    const valueText = totalValue > 0 ? totalValue.toFixed(2) + " zł" : "-";

    // Dynamiczna ocena stanu
    let maxWeight;
    if (product.unit === 'g') {
      maxWeight = userSettings.max_product_weight_g || 5000;
    } else if (product.unit === 'ml') {
      maxWeight = userSettings.max_product_weight_ml || 2000;
    } else if (product.unit === 'szt') {
      maxWeight = userSettings.max_product_weight_szt || 100;
    } else {
      maxWeight = 5000;
    }

    const percentageFilled = (parseFloat(product.weight) / maxWeight) * 100;
    let stockColor;
    
    if (percentageFilled <= 20) {
      stockColor = "text-danger";
    } else if (percentageFilled <= 40) {
      stockColor = "text-warning";
    } else if (percentageFilled < 80) {
      stockColor = "text-success";
    } else {
      stockColor = "text-primary";
    }

    const listItem = `
      <tr class="stock-list-item">
        <td class="col-action text-center">
          <button class="btn btn-light btn-sm stock-view-btn" onclick="viewStockProduct('${product.id}')" title="Podgląd">
            <i class="bi bi-eye"></i>
          </button>
        </td>
        <td class="col-photo">
          <img src="${productImage}" class="stock-list-img" alt="${product.name}" onerror="this.src='${defaultImage}'">
        </td>
        <td>
          <div class="stock-list-info">
            <h6 class="stock-list-name">${product.name}</h6>
            <small class="text-muted d-md-none">
              <span class="stock-list-amount ${stockColor}">${product.weight} ${product.unit}</span>
              <span class="mx-1">•</span>
              Wartość: ${valueText}
            </small>
          </div>
        </td>
        <td class="text-center d-none d-md-table-cell">
          <span class="stock-list-amount ${stockColor}">${product.weight} ${product.unit}</span>
        </td>
        <td class="d-none d-md-table-cell text-center fw-bold text-secondary">
          ${valueText}
        </td>
      </tr>
    `;
    tbody.append(listItem);
  });
}

// Wyświetl szczegóły produktu w magazynie
function viewStockProduct(productId) {
  const product = products.find(p => p.id === productId);
  
  if (!product) {
    Swal.fire('Błąd', 'Nie znaleziono produktu', 'error');
    return;
  }

  const defaultImage = 'uploads/default.png';
  const productImage = product.image || defaultImage;
  const price = product.price_per_1000 ? parseFloat(product.price_per_1000) : 0;
  const totalValue = price > 0 && product.weight > 0 ? (product.weight / 1000) * price : 0;
  const valueText = totalValue > 0 ? totalValue.toFixed(2) + " zł" : "-";
  
  Swal.fire({
    title: product.name,
    html: `
      <div class="text-start">
        <div class="text-center mb-3">
          <img src="${productImage}" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;" onerror="this.src='${defaultImage}'">
        </div>
        <div class="mb-2">
          <strong>Stan magazynowy:</strong> ${product.weight} ${product.unit}
        </div>
        <div class="mb-2">
          <strong>Wartość całkowita:</strong> ${valueText}
        </div>
        ${product.price_per_1000 ? `<div class="mb-2"><strong>Cena za 1000${product.unit}:</strong> ${parseFloat(product.price_per_1000).toFixed(2)} zł</div>` : ''}
        ${product.description ? `<div class="mb-2"><strong>Opis:</strong> ${product.description}</div>` : ''}
        ${product.link ? `<div class="mb-3"><a href="${product.link}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-cart me-1"></i> Przejdź do sklepu</a></div>` : ''}
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary flex-fill" onclick="Swal.close(); addWeightToProduct('${product.id}')">
          <i class="bi bi-pencil me-1"></i> Edytuj
        </button>
        <button class="btn btn-secondary flex-fill" onclick="Swal.close(); viewProductHistory('${product.id}')">
          <i class="bi bi-clock-history me-1"></i> Historia
        </button>
        <button class="btn btn-danger flex-fill" onclick="Swal.close(); deleteProduct('${product.id}')">
          <i class="bi bi-trash me-1"></i> Usuń
        </button>
      </div>
    `,
    showConfirmButton: false,
    showCloseButton: true,
    width: '600px',
    customClass: {
      popup: 'stock-product-modal'
    }
  });
}

// Toggle szczegółów produktu w magazynie
function toggleStockDetails(productId) {
  const detailsRow = $(`#stock-details-${productId}`);
  const toggleBtn = $(`.stock-list-item[data-product-id="${productId}"] .stock-toggle-btn i`);
  
  if (detailsRow.is(':visible')) {
    detailsRow.slideUp(200);
    toggleBtn.removeClass('bi-chevron-up').addClass('bi-chevron-down');
  } else {
    // Ukryj wszystkie inne szczegóły
    $('.stock-details-row').slideUp(200);
    $('.stock-toggle-btn i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    
    // Pokaż ten wiersz
    detailsRow.slideDown(200);
    toggleBtn.removeClass('bi-chevron-down').addClass('bi-chevron-up');
  }
}

// ============= HISTORIA I BACKUP =============

function loadExecutedRecipes() {
  $.get("config.php?action=get_executed_recipes", function (data) {
    try {
      executedRecipes = typeof data === 'string' ? JSON.parse(data) : data;
    } catch (e) {
      console.error("Error parsing executed recipes:", e, data);
      executedRecipes = [];
    }
    
    const container = $("#executedRecipesList");
    container.empty();

    console.log("Loaded executed recipes:", executedRecipes);

    if (!executedRecipes || executedRecipes.length === 0) {
      container.html(
        '<div class="clean-card p-4 text-center"><p class="text-muted mb-0">Brak wykonanych przepisów. Pierwszy wykonany przepis pojawi się tutaj.</p></div>'
      );
      return;
    }

    // Sortowanie od najnowszych
    executedRecipes.sort(
      (a, b) => new Date(b.executed_at) - new Date(a.executed_at)
    );

    executedRecipes.forEach((exec) => {
      const recipe = recipes.find((r) => r.id == exec.recipe_id);
      const name = recipe ? recipe.name : exec.recipe_name || "Nieznany przepis";

      let html = `
                <div class="clean-card mb-3 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">${name}</h6>
                        <small class="text-muted">${exec.executed_at}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                         <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2">
                            <i class="bi bi-bucket me-1"></i> Suche: <strong>${
                              exec.kg_amount
                            } kg</strong>
                         </span>
                         ${
                           exec.multiplier
                             ? `<span class="badge bg-success bg-opacity-10 text-success border px-3 py-2">
                                 <i class="bi bi-x me-1"></i> Mnożnik: <strong>${exec.multiplier}</strong>
                                </span>`
                             : ""
                         }
                         ${
                           exec.actual_balls_amount
                             ? `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
                                 <i class="bi bi-box-seam me-1"></i> Kulki: <strong>${parseFloat(exec.actual_balls_amount).toFixed(1)} kg</strong>
                                </span>`
                             : ""
                         }
                         ${
                           exec.total_cost
                             ? `<span class="badge bg-warning bg-opacity-10 text-warning border px-3 py-2">Koszt: ${parseFloat(
                                 exec.total_cost
                               ).toFixed(2)} zł</span>`
                             : ""
                         }
                         ${
                           exec.reduce_stock === false || exec.reduce_stock === 0
                             ? `<span class="badge bg-danger bg-opacity-10 text-danger border px-3 py-2">
                                 <i class="bi bi-exclamation-triangle me-1"></i>Bez zdjęcia ze stanu
                                </span>`
                             : ""
                         }
                    </div>
                    ${
                      exec.reduce_stock === false || exec.reduce_stock === 0
                        ? `<button class="btn btn-sm btn-outline-danger w-100" onclick="undoExecutedRecipe('${exec.id}', false)">
                            <i class="bi bi-trash me-1"></i> Usuń z historii
                           </button>`
                        : `<button class="btn btn-sm btn-outline-warning w-100" onclick="undoExecutedRecipe('${exec.id}', true)">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Cofnij i zwróć składniki na stan
                           </button>`
                    }
                </div>
            `;
      container.append(html);
    });
  });
}

function undoExecutedRecipe(id, shouldRestoreStock) {
  const title = shouldRestoreStock ? "Cofnąć produkcję?" : "Usunąć z historii?";
  const text = shouldRestoreStock 
    ? "Składniki wrócą na stan magazynowy." 
    : "Ten wpis zostanie usunięty z historii. Składniki nie były pobierane ze stanu.";
  const confirmText = shouldRestoreStock ? "Cofnij" : "Usuń";
  
  Swal.fire({
    title: title,
    text: text,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: confirmText,
    confirmButtonColor: shouldRestoreStock ? "#f59e0b" : "#ef4444",
    cancelButtonText: "Anuluj"
  }).then((res) => {
    if (res.isConfirmed) {
      $.ajax({
        url: "config.php",
        method: "POST",
        data: { action: "undo_executed_recipe", executed_id: id },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            Swal.fire("Cofnięto!", "Stan magazynowy przywrócony.", "success");
            loadExecutedRecipes();
            loadProducts();
            loadFinishedBalls(); // Odśwież gotowe kulki
          } else if (data.require_login) {
            showLoginModal();
            Swal.fire("Wymagane logowanie", "Zaloguj się aby cofnąć operację", "warning");
          } else {
            Swal.fire("Błąd", data.error, "error");
          }
        },
        error: function() {
          Swal.fire("Błąd", "Problem z połączeniem", "error");
        }
      });
    }
  });
}

// Logi i Backup
function loadActivityHistory() {
  $.get("config.php?action=get_activity_log", function (res) {
    try {
      const data = typeof res === 'string' ? JSON.parse(res) : res;
      console.log("Activity log data:", data);
      
      if (data.require_admin) {
        Swal.fire({
          icon: 'error',
          title: 'Brak dostępu',
          text: 'Tylko admin może przeglądać logi',
          timer: 2000
        });
        return;
      }
      
      if (data.success && data.log && data.log.length > 0) {
        // Przygotuj dane dla DataTables
        const tableData = data.log.map(entry => {
          // Określ ikonę i kolor na podstawie typu
          let icon = 'bi-info-circle';
          let colorClass = 'text-primary';
          
          if (entry.type === 'recipe_executed' || entry.type === 'recipe_confirm') {
            icon = 'bi-box-seam';
            colorClass = 'text-success';
          } else if (entry.type === 'product_added' || entry.type === 'product_created') {
            icon = 'bi-plus-circle';
            colorClass = 'text-info';
          } else if (entry.type === 'product_weight_changed') {
            icon = 'bi-arrow-left-right';
            colorClass = 'text-warning';
          } else if (entry.type === 'product_deleted' || entry.type === 'recipe_deleted') {
            icon = 'bi-trash';
            colorClass = 'text-danger';
          } else if (entry.type === 'backup_created') {
            icon = 'bi-shield-check';
            colorClass = 'text-secondary';
          }
          
          return {
            icon: `<i class="bi ${icon} ${colorClass} fs-4"></i>`,
            description: entry.description,
            username: entry.username || '-',
            timestamp: entry.timestamp,
            sortDate: new Date(entry.timestamp).getTime()
          };
        });
        
        // Zniszcz istniejącą tabelę jeśli istnieje
        if ($.fn.DataTable.isDataTable('#activityHistoryTable')) {
          $('#activityHistoryTable').DataTable().destroy();
        }
        
        // Inicjalizuj DataTable
        $('#activityHistoryTable').DataTable({
          data: tableData,
          columns: [
            { data: 'icon', orderable: false, searchable: false },
            { data: 'description' },
            { data: 'username' },
            { data: 'timestamp' }
          ],
          order: [[3, 'desc']], // Sortuj po dacie malejąco (od najnowszych)
          language: {
            search: "Szukaj:",
            lengthMenu: "Pokaż _MENU_ wpisów",
            info: "Pokazano _START_ do _END_ z _TOTAL_ wpisów",
            infoEmpty: "Brak wpisów",
            infoFiltered: "(przefiltrowano z _MAX_ wpisów)",
            zeroRecords: "Nie znaleziono pasujących wpisów",
            emptyTable: "Brak logów systemowych",
            paginate: {
              first: "Pierwsza",
              last: "Ostatnia",
              next: "Następna",
              previous: "Poprzednia"
            }
          },
          pageLength: 25,
          responsive: true,
          dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
        });
      } else {
        // Brak danych
        if ($.fn.DataTable.isDataTable('#activityHistoryTable')) {
          $('#activityHistoryTable').DataTable().destroy();
        }
        $('#activityHistoryTable tbody').html(`
          <tr>
            <td colspan="4" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
              <p>Brak logów systemowych</p>
            </td>
          </tr>
        `);
      }
    } catch (e) {
      console.error("Error loading activity history:", e);
      $('#activityHistoryTable tbody').html(`
        <tr>
          <td colspan="4">
            <div class="alert alert-danger mb-0">
              <i class="bi bi-exclamation-triangle me-2"></i>
              Błąd ładowania logów: ${e.message}
            </div>
          </td>
        </tr>
      `);
    }
  });
}

function loadBackups() {
  $.ajax({
    url: "config.php?action=get_backups",
    method: "GET",
    dataType: "json",
    success: function (data) {
      if (data.success) {
        let html = "";
        data.backups.forEach((b) => {
          const sizeKB = b.size / 1024;
          const sizeDisplay = sizeKB > 1024 ? `${(sizeKB / 1024).toFixed(2)} MB` : `${sizeKB.toFixed(1)} KB`;
          html += `
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
              <div>
                <div class="fw-bold font-monospace">${b.filename}</div>
                <div class="small text-muted">${b.date} • ${sizeDisplay}</div>
              </div>
              <div class="btn-group">
                <button class="btn btn-sm btn-success" onclick="restoreBackup('${b.filename}')">Przywróć</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('${b.filename}')"><i class="bi bi-trash"></i></button>
              </div>
            </div>
          `;
        });
        $("#backupsList").html(html || '<div class="text-muted p-3">Brak kopii zapasowych</div>');
      }
    },
    error: function() {
      $("#backupsList").html('<div class="text-danger p-3">Błąd wczytywania backupów</div>');
    }
  });
}

function createBackup() {
  $.ajax({
    url: "config.php?action=create_backup",
    method: "GET",
    dataType: "json",
    success: function (data) {
      if (data.require_admin) {
        Swal.fire({
          icon: 'error',
          title: 'Brak dostępu',
          text: 'Tylko admin może tworzyć backupy',
          timer: 2000
        });
        return;
      }
      if (data.success) {
        Swal.fire("Sukces", "Kopia utworzona", "success");
        loadBackups();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Błąd',
          text: data.error || 'Nie udało się utworzyć kopii zapasowej',
        });
      }
    },
    error: function(xhr, status, error) {
      console.error('Backup error:', xhr.responseText);
      Swal.fire({
        icon: 'error',
        title: 'Błąd',
        text: 'Nie udało się utworzyć kopii zapasowej: ' + error,
      });
    }
  });
}

function restoreBackup(file) {
  Swal.fire({
    title: "Przywrócić?",
    text: "Aktualne dane zostaną nadpisane!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Przywróć",
    confirmButtonColor: "#ef4444",
  }).then((res) => {
    if (res.isConfirmed) {
      $.post(
        "config.php",
        { action: "restore_backup", filename: file },
        function (data) {
          if (data.require_admin) {
            Swal.fire({
              icon: 'error',
              title: 'Brak dostępu',
              text: 'Tylko admin może przywracać backupy',
              timer: 2000
            });
            return;
          }
          Swal.fire("Gotowe", "Baza przywrócona. Odświeżam...", "success").then(
            () => location.reload()
          );
        }
      );
    }
  });
}

function deleteBackup(file) {
  $.post(
    "config.php",
    { action: "delete_backup", filename: file },
    function () {
      loadBackups();
    }
  );
}

// ============= ZARZĄDZANIE UŻYTKOWNIKAMI =============

let allUsers = [];

function loadUsers() {
  $.get("config.php?action=get_users", function(res) {
    try {
      const data = typeof res === 'string' ? JSON.parse(res) : res;
      
      if (data.require_admin) {
        Swal.fire({
          icon: 'error',
          title: 'Brak dostępu',
          text: 'Tylko admin może przeglądać użytkowników',
          timer: 2000
        });
        return;
      }
      
      if (data.success && data.users) {
        allUsers = data.users;
        renderUsers(allUsers);
      }
    } catch(e) {
      console.error("Błąd ładowania użytkowników:", e);
    }
  });
}

function renderUsers(users) {
  const container = $('#usersList');
  
  if (!users || users.length === 0) {
    container.html('<p class="text-muted">Brak użytkowników</p>');
    return;
  }
  
  let html = '<div class="table-responsive"><table class="table table-hover">';
  html += '<thead><tr>';
  html += '<th>Username</th>';
  html += '<th>Email</th>';
  html += '<th>Rola</th>';
  html += '<th>Status</th>';
  html += '<th>Data rejestracji</th>';
  html += '<th>Akcje</th>';
  html += '</tr></thead><tbody>';
  
  users.forEach(user => {
    const isAdmin = user.is_admin == 1;
    const isBlocked = user.is_blocked == 1;
    const statusBadge = isBlocked 
      ? '<span class="badge bg-danger">Zablokowany</span>' 
      : '<span class="badge bg-success">Aktywny</span>';
    const roleBadge = isAdmin 
      ? '<span class="badge bg-warning text-dark">Admin</span>' 
      : '<span class="badge bg-secondary">User</span>';
    
    html += '<tr>';
    html += '<td>' + escapeHtml(user.username) + '</td>';
    html += '<td>' + escapeHtml(user.email) + '</td>';
    html += '<td>' + roleBadge + '</td>';
    html += '<td>' + statusBadge + '</td>';
    html += '<td>' + formatDateTime(user.created_at) + '</td>';
    html += '<td>';
    
    if (user.id !== currentUser.id) { // Nie pozwalaj blokować samego siebie
      html += '<button class="btn btn-sm btn-info me-1" onclick="switchToUser(\'' + user.id + '\', \'' + escapeHtml(user.username) + '\')"><i class="bi bi-person-check me-1"></i>Przełącz się</button>';
      html += '<button class="btn btn-sm btn-warning me-1" onclick="changeUserPassword(\'' + user.id + '\', \'' + escapeHtml(user.username) + '\')"><i class="bi bi-key me-1"></i>Zmień hasło</button>';
      
      if (isBlocked) {
        html += '<button class="btn btn-sm btn-success" onclick="unblockUser(\'' + user.id + '\', \'' + escapeHtml(user.username) + '\')"><i class="bi bi-unlock me-1"></i>Odblokuj</button>';
      } else {
        html += '<button class="btn btn-sm btn-danger" onclick="blockUser(\'' + user.id + '\', \'' + escapeHtml(user.username) + '\')"><i class="bi bi-lock me-1"></i>Zablokuj</button>';
      }
    } else {
      html += '<span class="text-muted">To Ty</span>';
    }
    
    html += '</td>';
    html += '</tr>';
  });
  
  html += '</tbody></table></div>';
  container.html(html);
}

function blockUser(userId, username) {
  Swal.fire({
    title: 'Zablokować użytkownika?',
    text: 'Użytkownik "' + username + '" nie będzie mógł się zalogować',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Zablokuj',
    confirmButtonColor: '#dc3545',
    cancelButtonText: 'Anuluj'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('config.php', {
        action: 'block_user',
        user_id: userId
      }, function(data) {
        if (data.success) {
          Swal.fire('Zablokowano!', data.message, 'success');
          loadUsers();
        } else {
          Swal.fire('Błąd', data.error, 'error');
        }
      }, 'json');
    }
  });
}

function unblockUser(userId, username) {
  Swal.fire({
    title: 'Odblokować użytkownika?',
    text: 'Użytkownik "' + username + '" będzie mógł się ponownie zalogować',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Odblokuj',
    confirmButtonColor: '#28a745',
    cancelButtonText: 'Anuluj'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('config.php', {
        action: 'unblock_user',
        user_id: userId
      }, function(data) {
        if (data.success) {
          Swal.fire('Odblokowano!', data.message, 'success');
          loadUsers();
        } else {
          Swal.fire('Błąd', data.error, 'error');
        }
      }, 'json');
    }
  });
}

function changeUserPassword(userId, username) {
  Swal.fire({
    title: 'Zmiana hasła',
    html: `
      <p class="mb-3">Nowe hasło dla użytkownika: <strong>${username}</strong></p>
      <input type="text" id="newPassword" class="swal2-input" placeholder="Wpisz nowe hasło" autocomplete="off">
    `,
    showCancelButton: true,
    confirmButtonText: 'Zmień hasło',
    cancelButtonText: 'Anuluj',
    confirmButtonColor: '#ffc107',
    preConfirm: () => {
      const newPassword = document.getElementById('newPassword').value;
      
      if (!newPassword) {
        Swal.showValidationMessage('Wpisz nowe hasło');
        return false;
      }
      
      return { newPassword };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('config.php', {
        action: 'admin_change_password',
        user_id: userId,
        new_password: result.value.newPassword
      }, function(data) {
        if (data.success) {
          Swal.fire('Sukces!', 'Hasło zostało zmienione', 'success');
        } else {
          Swal.fire('Błąd', data.error || 'Nie udało się zmienić hasła', 'error');
        }
      }, 'json').fail(function() {
        Swal.fire('Błąd', 'Wystąpił problem z połączeniem', 'error');
      });
    }
  });
}

function switchToUser(userId, username) {
  Swal.fire({
    title: 'Przełączyć się na użytkownika?',
    text: `Czy chcesz przełączyć się na użytkownika "${username}"? Będziesz widzieć jego dane.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Tak, przełącz',
    cancelButtonText: 'Anuluj'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('config.php', {
        action: 'switch_user',
        user_id: userId
      }, function(data) {
        if (data.success) {
          Swal.fire('Sukces!', data.message, 'success').then(() => {
            // Odśwież całą stronę żeby załadować dane przełączonego użytkownika
            location.reload();
          });
        } else {
          Swal.fire('Błąd!', data.error || 'Nie udało się przełączyć użytkownika', 'error');
        }
      }, 'json');
    }
  });
}

function switchBackToAdmin() {
  $.post('config.php', {
    action: 'switch_back_admin'
  }, function(data) {
    if (data.success) {
      Swal.fire('Sukces!', data.message, 'success').then(() => {
        location.reload();
      });
    } else {
      Swal.fire('Błąd!', data.error || 'Nie udało się powrócić do konta administratora', 'error');
    }
  }, 'json');
}

function loadPendingRecipes() {
  $.post('config.php', {
    action: 'get_pending_recipes'
  }, function(data) {
    if (data.success) {
      renderPendingRecipes(data.recipes);
      $('#pendingRecipesCount').text(data.recipes.length);
    } else {
      Swal.fire('Błąd', data.error || 'Nie udało się pobrać oczekujących przepisów', 'error');
    }
  }, 'json');
}

function moderateRecipe(recipeId, action) {
  const actionText = action === 'approve' ? 'zaakceptować' : 'odrzucić';
  const actionColor = action === 'approve' ? 'success' : 'warning';
  
  Swal.fire({
    title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} przepis?`,
    text: `Czy na pewno chcesz ${actionText} ten przepis?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: actionText.charAt(0).toUpperCase() + actionText.slice(1),
    confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
    cancelButtonText: 'Anuluj'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('config.php', {
        action: 'moderate_recipe',
        recipe_id: recipeId,
        moderate_action: action
      }, function(data) {
        if (data.success) {
          Swal.fire('Sukces!', data.message, actionColor);
          loadPendingRecipes();
          // Odśwież listę przepisów jeśli jest załadowana
          if (typeof loadRecipes === 'function') {
            loadRecipes();
          }
        } else {
          Swal.fire('Błąd!', data.error || 'Nie udało się wykonać akcji', 'error');
        }
      }, 'json');
    }
  });
}

function viewPendingRecipe(recipeId) {
  viewRecipe(recipeId);
}

function renderPendingRecipes(recipes) {
  const container = $('#pendingRecipesList');
  
  if (!recipes || recipes.length === 0) {
    container.html('<div class="text-center p-4"><p class="text-white-50">Brak oczekujących przepisów</p></div>');
    return;
  }
  
  let html = '';
  
  recipes.forEach(recipe => {
    const ingredients = JSON.parse(recipe.ingredients || '[]');
    const dryCount = ingredients.filter(i => i.type === 'dry').length;
    const wetCount = ingredients.filter(i => i.type === 'wet').length;
    
    html += `
      <div class="card mb-3">
        <div class="card-body">
          <div class="row">
            <div class="col-md-2">
              <img src="${recipe.image || 'uploads/default-recipe.jpg'}" class="img-fluid rounded" alt="${recipe.name}">
            </div>
            <div class="col-md-7">
              <h5 class="card-title">${recipe.name}</h5>
              <p class="card-text">${recipe.description || 'Brak opisu'}</p>
              <small class="text-muted">Autor: ${recipe.author_username} | Dodano: ${formatDateTime(recipe.created_at)}</small>
              <div class="mt-2">
                <span class="badge bg-secondary me-2">Suche: ${dryCount}</span>
                <span class="badge bg-info">Mokre: ${wetCount}</span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="d-flex flex-column gap-2">
                <button class="btn btn-success" onclick="moderateRecipe('${recipe.id}', 'approve')">
                  <i class="bi bi-check-lg me-1"></i>Zaakceptuj
                </button>
                <button class="btn btn-danger" onclick="moderateRecipe('${recipe.id}', 'reject')">
                  <i class="bi bi-x-lg me-1"></i>Odrzuć
                </button>
                <button class="btn btn-outline-primary" onclick="viewRecipe('${recipe.id}')">
                  <i class="bi bi-eye me-1"></i>Podgląd
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  });
  
  container.html(html);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function formatDateTime(datetime) {
  if (!datetime) return '';
  const date = new Date(datetime);
  return date.toLocaleString('pl-PL');
}

// Wyszukiwanie użytkowników
$('#userSearch').on('keyup', function() {
  const searchTerm = $(this).val().toLowerCase();
  
  if (searchTerm === '') {
    renderUsers(allUsers);
    return;
  }
  
  const filtered = allUsers.filter(user => {
    return user.username.toLowerCase().includes(searchTerm) ||
           user.email.toLowerCase().includes(searchTerm);
  });
  
  renderUsers(filtered);
});

function clearLogs() {
  Swal.fire({
    title: "Wyczyścić wszystkie logi?",
    text: "Tej operacji nie można cofnąć!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Wyczyść",
    cancelButtonText: "Anuluj",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "config.php",
        method: "POST",
        data: { action: "clear_all_history" },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            Swal.fire("Wyczyszczono!", "Wszystkie logi zostały usunięte", "success");
            loadActivityHistory();
          } else if (data.require_login) {
            showLoginModal();
            Swal.fire("Wymagane logowanie", "Zaloguj się aby wyczyścić logi", "warning");
          } else {
            Swal.fire("Błąd", data.error || "Nie udało się wyczyścić", "error");
          }
        },
        error: function() {
          Swal.fire("Błąd", "Problem z połączeniem", "error");
        }
      });
    }
  });
}

// ============= KOMPRESJA OBRAZÓW =============

function compressImage(file, maxSizeMB = 1) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = function(event) {
      const img = new Image();
      img.src = event.target.result;
      img.onload = function() {
        const canvas = document.createElement('canvas');
        let width = img.width;
        let height = img.height;
        
        // Zmniejsz wymiary jeśli obraz jest za duży
        const maxDimension = 1920;
        if (width > maxDimension || height > maxDimension) {
          if (width > height) {
            height = (height / width) * maxDimension;
            width = maxDimension;
          } else {
            width = (width / height) * maxDimension;
            height = maxDimension;
          }
        }
        
        canvas.width = width;
        canvas.height = height;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        
        // Kompresuj do JPEG z jakością 0.8
        canvas.toBlob(function(blob) {
          // Sprawdź rozmiar
          const sizeMB = blob.size / 1024 / 1024;
          console.log(`Compressed image size: ${sizeMB.toFixed(2)} MB`);
          
          // Jeśli nadal za duże, spróbuj z niższą jakością
          if (sizeMB > maxSizeMB) {
            canvas.toBlob(function(blob2) {
              resolve(new File([blob2], file.name, { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.6);
          } else {
            resolve(new File([blob], file.name, { type: 'image/jpeg' }));
          }
        }, 'image/jpeg', 0.8);
      };
      img.onerror = reject;
    };
    reader.onerror = reject;
  });
}

// ============= KAMERA =============

function openCamera(target) {
  currentCameraTarget = target;
  const modal = new bootstrap.Modal(document.getElementById("cameraModal"));
  modal.show();

  const video = document.getElementById("cameraVideo");

  navigator.mediaDevices
    .getUserMedia({ video: { facingMode: "environment" } })
    .then(function (stream) {
      cameraStream = stream;
      video.srcObject = stream;
    })
    .catch(function (err) {
      Swal.fire("Błąd", "Nie można uruchomić kamery.", "error");
      modal.hide();
    });

  $("#cameraModal").on("hidden.bs.modal", function () {
    if (cameraStream) {
      cameraStream.getTracks().forEach((track) => track.stop());
      cameraStream = null;
    }
  });
}

function capturePhoto() {
  const video = document.getElementById("cameraVideo");
  const canvas = document.getElementById("cameraCanvas");
  const context = canvas.getContext("2d");

  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  context.drawImage(video, 0, 0);

  canvas.toBlob(function (blob) {
    const file = new File([blob], "photo.jpg", { type: "image/jpeg" });
    const dt = new DataTransfer();
    dt.items.add(file);

    if (currentCameraTarget === "product") {
      document.getElementById("productImage").files = dt.files;
      previewImage(
        document.getElementById("productImage"),
        "#productImagePreview"
      );
    } else if (currentCameraTarget === "recipe") {
      document.getElementById("recipeImage").files = dt.files;
      previewImage(
        document.getElementById("recipeImage"),
        "#recipeImagePreview"
      );
    }

    bootstrap.Modal.getInstance(document.getElementById("cameraModal")).hide();
  }, "image/jpeg");
}

function previewImage(input, target) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      $(target).html(
        `<img src="${e.target.result}" class="rounded-3 shadow-sm border" style="max-height: 120px;">`
      );
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ======================
// SYSTEM LOGOWANIA
// ======================

// Przełączanie między logowaniem a rejestracją
$('#showRegister').on('click', function(e) {
  e.preventDefault();
  $('#loginForm').addClass('d-none');
  $('#registerForm').removeClass('d-none');
  $('#loginTitle').addClass('d-none');
  $('#registerTitle').removeClass('d-none');
});

$('#showLogin').on('click', function(e) {
  e.preventDefault();
  $('#registerForm').addClass('d-none');
  $('#loginForm').removeClass('d-none');
  $('#registerTitle').addClass('d-none');
  $('#loginTitle').removeClass('d-none');
});

// Obsługa logowania
$('#loginForm').on('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData();
  formData.append('action', 'login');
  formData.append('username', $('#loginUsername').val());
  formData.append('password', $('#loginPassword').val());
  
  fetch('config.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      currentUser = data.user;
      $('#loginModal').modal('hide');
      
      Swal.fire({
        icon: 'success',
        title: 'Zalogowano!',
        text: 'Witaj ' + data.user.username,
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        // Przeładuj stronę aby odświeżyć wszystko
        location.reload();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Błąd logowania',
        text: data.error
      });
    }
  });
});

// Obsługa rejestracji
$('#registerForm').on('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData();
  formData.append('action', 'register');
  formData.append('username', $('#registerUsername').val());
  formData.append('email', $('#registerEmail').val());
  formData.append('password', $('#registerPassword').val());
  
  fetch('config.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      currentUser = data.user;
      $('#loginModal').modal('hide');
      
      Swal.fire({
        icon: 'success',
        title: 'Konto utworzone!',
        text: 'Witaj ' + data.user.username,
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        // Przeładuj stronę aby odświeżyć wszystko
        location.reload();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Błąd rejestracji',
        text: data.error
      });
    }
  });
});

// Filtry przepisów
$('#filterMyRecipes').on('click', function() {
  recipeFilter = 'my';
  $('#filterMyRecipes').addClass('active');
  $('#filterPublicRecipes').removeClass('active');
  filterRecipes();
});

$('#filterPublicRecipes').on('click', function() {
  recipeFilter = 'public';
  $('#filterPublicRecipes').addClass('active');
  $('#filterMyRecipes').removeClass('active');
  filterRecipes();
});

// Wylogowanie
$('#logoutBtn').on('click', function() {
  fetch('config.php?action=logout')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: 'info',
          title: 'Wylogowano',
          text: 'Do zobaczenia!',
          timer: 1500,
          showConfirmButton: false
        }).then(() => {
          location.reload();
        });
      }
    });
});

// Przycisk w UI do pokazania modala
$('#showLoginModal').on('click', function() {
  showLoginModal();
});

// ============= USTAWIENIA UŻYTKOWNIKA =============

function loadUserSettings() {
  $.get("config.php?action=get_user_settings", function(res) {
    try {
      const data = typeof res === 'string' ? JSON.parse(res) : res;
      
      if (data.success && data.settings) {
        userSettings = data.settings;
        // Wypełnij formularz
        $('#maxProductWeightG').val(userSettings.max_product_weight_g || 5000);
        $('#maxProductWeightMl').val(userSettings.max_product_weight_ml || 2000);
        $('#maxProductWeightSzt').val(userSettings.max_product_weight_szt || 100);
        $('#recipeMultiplier').val(userSettings.recipe_multiplier || 1.7);
        // Ustaw domyślny mnożnik w formularzu przepisu
        $('#recipeMultiplierInput').val(userSettings.recipe_multiplier || 1.7);
        // Odśwież produkty żeby zastosować nowe ustawienia
        renderProducts();
      }
    } catch(e) {
      console.error("Błąd ładowania ustawień:", e);
    }
  });
}

$('#userSettingsForm').on('submit', function(e) {
  e.preventDefault();
  
  const maxWeightG = parseInt($('#maxProductWeightG').val());
  const maxWeightMl = parseInt($('#maxProductWeightMl').val());
  const maxWeightSzt = parseInt($('#maxProductWeightSzt').val());
  const recipeMultiplier = parseFloat($('#recipeMultiplier').val());
  
  $.post('config.php', {
    action: 'save_user_settings',
    max_product_weight_g: maxWeightG,
    max_product_weight_ml: maxWeightMl,
    max_product_weight_szt: maxWeightSzt,
    recipe_multiplier: recipeMultiplier
  }, function(data) {
    if (data.success) {
      userSettings.max_product_weight_g = maxWeightG;
      userSettings.max_product_weight_ml = maxWeightMl;
      userSettings.max_product_weight_szt = maxWeightSzt;
      userSettings.recipe_multiplier = recipeMultiplier;
      // Zaktualizuj domyślny mnożnik w formularzu przepisu
      $('#recipeMultiplierInput').val(recipeMultiplier);
      Swal.fire({
        icon: 'success',
        title: 'Zapisano!',
        text: data.message,
        timer: 2000
      });
      // Odśwież produkty żeby zastosować nowe ustawienia
      renderProducts();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Błąd',
        text: data.error
      });
    }
  }, 'json');
});

// === SLIDER PARTNERÓW - NIESKOŃCZONA KARUZELA ===
let currentSlide = 0;
let isTransitioning = false;
let autoScrollInterval;

function initializeSlider() {
  const slider = document.getElementById('partnersSlider');
  if (!slider) return;
  
  const items = slider.querySelectorAll('.partner-item');
  if (items.length === 0) return;
  
  // Sklonuj elementy dla nieskończonej karuzeli
  const itemsArray = Array.from(items);
  
  // Dodaj sklonowane elementy na początku i końcu
  itemsArray.forEach(item => {
    const clone = item.cloneNode(true);
    slider.appendChild(clone);
  });
  
  itemsArray.forEach(item => {
    const clone = item.cloneNode(true);
    slider.insertBefore(clone, slider.firstChild);
  });
  
  // Ustaw pozycję początkową (pomiń pierwsze sklonowane elementy)
  const itemWidth = items[0].offsetWidth + 32; // 32px to gap
  currentSlide = items.length;
  slider.style.transform = `translateX(-${currentSlide * itemWidth}px)`;
}

function moveSlider(direction) {
  if (isTransitioning) return;
  
  const slider = document.getElementById('partnersSlider');
  if (!slider) return;
  
  const allItems = slider.querySelectorAll('.partner-item');
  const originalItemsCount = Math.floor(allItems.length / 3); // Oryginalne elementy (1/3 z wszystkich)
  const itemWidth = allItems[0].offsetWidth + 32;
  
  isTransitioning = true;
  currentSlide += direction;
  
  slider.style.transition = 'transform 0.5s ease-in-out';
  slider.style.transform = `translateX(-${currentSlide * itemWidth}px)`;
  
  setTimeout(() => {
    slider.style.transition = 'none';
    
    // Sprawdź czy jesteśmy na sklonowanych elementach i przeskocz bezpośrednio
    if (currentSlide >= originalItemsCount * 2) {
      currentSlide = originalItemsCount;
      slider.style.transform = `translateX(-${currentSlide * itemWidth}px)`;
    } else if (currentSlide < originalItemsCount) {
      currentSlide = originalItemsCount * 2 - 1;
      slider.style.transform = `translateX(-${currentSlide * itemWidth}px)`;
    }
    
    isTransitioning = false;
  }, 500);
}

function startAutoScroll() {
  autoScrollInterval = setInterval(() => {
    moveSlider(1);
  }, 3000);
}

function stopAutoScroll() {
  clearInterval(autoScrollInterval);
}

// Event listenery
$(document).on('mouseenter', '.partners-slider-wrapper', stopAutoScroll);
$(document).on('mouseleave', '.partners-slider-wrapper', startAutoScroll);

// Inicjalizacja po załadowaniu DOM
$(document).ready(function() {
  setTimeout(() => {
    initializeSlider();
    startAutoScroll();
  }, 500);
});

// ============================================
// GOTOWE KULKI
// ============================================

let finishedBalls = [];
let currentManageBallId = null;
let currentHistoryBallId = null;

// Ładowanie gotowych kulek
function loadFinishedBalls() {
  $.ajax({
    url: "config.php",
    method: "POST",
    data: { action: "get_finished_balls" },
    dataType: "json",
    success: function(response) {
      console.log("Finished balls response:", response);
      if (response.success) {
        finishedBalls = response.balls || [];
        renderFinishedBalls();
      } else {
        console.error("Error loading finished balls:", response.error);
        Swal.fire("Błąd", response.error || "Nie udało się załadować kulek", "error");
      }
    },
    error: function(xhr, status, error) {
      console.error("AJAX error loading finished balls:", status, error);
      console.error("Response:", xhr.responseText);
    }
  });
}

// Renderowanie listy kulek
function renderFinishedBalls() {
  const container = $("#finishedBallsList");
  
  if (finishedBalls.length === 0) {
    container.html(`
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>Nie masz jeszcze gotowych kulek.
        Kliknij "Dodaj kulki z przepisu" aby rozpocząć.
      </div>
    `);
    return;
  }
  
  let html = '<div class="row g-3">';
  
  finishedBalls.forEach(ball => {
    const quantity = parseFloat(ball.quantity);
    const lowStock = quantity < 5;
    const outOfStock = quantity === 0;
    const defaultImage = 'uploads/default.png';
    const ballImage = ball.recipe_image || defaultImage;
    
    html += `
      <div class="col-md-6 col-lg-4">
        <div class="clean-card h-100">
          <div class="position-relative" style="height: 180px; overflow: hidden; border-radius: 16px 16px 0 0;">
            <img src="${ballImage}" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='${defaultImage}'">
          </div>
          <div class="card-body">
            <h5 class="card-title mb-3">${escapeHtml(ball.recipe_name)}</h5>
            
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Stan magazynowy:</span>
                <span class="badge ${outOfStock ? 'bg-danger' : lowStock ? 'bg-warning' : 'bg-success'} fs-6">
                  ${quantity.toFixed(2)} kg
                </span>
              </div>
              ${outOfStock ? '<div class="alert alert-danger py-2 mb-2"><small>Brak kulek!</small></div>' : ''}
              ${lowStock && !outOfStock ? '<div class="alert alert-warning py-2 mb-2"><small>Niski stan!</small></div>' : ''}
            </div>
            
            <div class="d-flex gap-2 mb-2">
              <button class="btn btn-sm btn-success flex-fill manage-ball-btn" data-ball-id="${ball.id}" data-ball-name="${escapeHtml(ball.recipe_name)}" data-ball-quantity="${quantity}">
                <i class="bi bi-pencil-square"></i> Zarządzaj
              </button>
              <button class="btn btn-sm btn-info history-ball-btn" data-ball-id="${ball.id}" data-ball-name="${escapeHtml(ball.recipe_name)}">
                <i class="bi bi-clock-history"></i>
              </button>
            </div>
            
            <button class="btn btn-sm btn-outline-danger w-100 delete-ball-btn" data-ball-id="${ball.id}" data-ball-name="${escapeHtml(ball.recipe_name)}">
              <i class="bi bi-trash"></i> Usuń całkowicie
            </button>
            
            <div class="mt-2">
              <small class="text-muted">
                <i class="bi bi-clock"></i> Ostatnia zmiana: ${formatDateTime(ball.updated_at)}
              </small>
            </div>
          </div>
        </div>
      </div>
    `;
  });
  
  html += '</div>';
  container.html(html);
}

// Otwórz modal dodawania kulek
$("#addFinishedBallBtn").click(function() {
  // Załaduj przepisy do selecta
  const select = $("#ballRecipeSelect");
  select.html('<option value="">-- Wybierz przepis --</option>');
  
  // Załaduj wszystkie przepisy użytkownika
  $.get("config.php", { action: "get_recipes" }, function(response) {
    // Backend zwraca tablicę przepisów bezpośrednio
    if (Array.isArray(response) && response.length > 0) {
      // Filtruj przepisy użytkownika (własne lub publiczne)
      const userRecipes = response.filter(r => r.user_id === currentUser.id || r.is_public == 1);
      
      userRecipes.forEach(recipe => {
        select.append(`<option value="${recipe.id}">${escapeHtml(recipe.name)}</option>`);
      });
    }
  });
  
  $("#ballQuantity").val("");
  $("#addFinishedBallModal").modal("show");
});

// Formularz dodawania kulek
$("#addFinishedBallForm").submit(function(e) {
  e.preventDefault();
  
  const recipeId = $("#ballRecipeSelect").val();
  const quantity = parseFloat($("#ballQuantity").val());
  
  if (!recipeId || quantity <= 0) {
    Swal.fire("Błąd", "Wybierz przepis i podaj prawidłową ilość", "error");
    return;
  }
  
  $.post("config.php", {
    action: "add_finished_ball",
    recipe_id: recipeId,
    quantity: quantity
  }, function(response) {
    if (response.success) {
      Swal.fire("Sukces", response.message, "success");
      $("#addFinishedBallModal").modal("hide");
      loadFinishedBalls();
    } else {
      Swal.fire("Błąd", response.error, "error");
    }
  });
});

// Otwórz modal zarządzania
function openManageBallModal(ballId, ballName, currentQuantity) {
  currentManageBallId = ballId;
  $("#manageBallId").val(ballId);
  $("#manageBallName").text(ballName);
  $("#manageBallQuantity").text(currentQuantity.toFixed(2));
  
  $("#addQuantityInput").val("");
  $("#addQuantityDescription").val("");
  $("#removeQuantityInput").val("");
  $("#removeQuantityDescription").val("");
  
  // Pokaż modal najpierw
  $("#manageBallModal").modal("show");
  
  // Aktywuj zakładkę dodawania po krótkim opóźnieniu
  setTimeout(() => {
    const addTabElement = document.getElementById("addQuantityTab");
    if (addTabElement) {
      addTabElement.click();
    }
  }, 100);
}

// Formularz dodawania ilości
$("#addQuantityForm").submit(function(e) {
  e.preventDefault();
  
  const quantity = parseFloat($("#addQuantityInput").val());
  const description = $("#addQuantityDescription").val();
  
  if (quantity <= 0) {
    Swal.fire("Błąd", "Podaj prawidłową ilość", "error");
    return;
  }
  
  $.post("config.php", {
    action: "update_ball_quantity",
    ball_id: currentManageBallId,
    change_type: "add",
    quantity: quantity,
    description: description
  }, function(response) {
    if (response.success) {
      Swal.fire("Sukces", "Dodano ilość kulek", "success");
      $("#manageBallModal").modal("hide");
      loadFinishedBalls();
    } else {
      Swal.fire("Błąd", response.error, "error");
    }
  });
});

// Formularz usuwania ilości
$("#removeQuantityForm").submit(function(e) {
  e.preventDefault();
  
  const quantity = parseFloat($("#removeQuantityInput").val());
  const description = $("#removeQuantityDescription").val();
  
  if (quantity <= 0) {
    Swal.fire("Błąd", "Podaj prawidłową ilość", "error");
    return;
  }
  
  $.post("config.php", {
    action: "update_ball_quantity",
    ball_id: currentManageBallId,
    change_type: "remove",
    quantity: quantity,
    description: description
  }, function(response) {
    if (response.success) {
      Swal.fire("Sukces", "Odjęto ilość kulek", "success");
      $("#manageBallModal").modal("hide");
      loadFinishedBalls();
    } else {
      Swal.fire("Błąd", response.error, "error");
    }
  });
});

// Usuń kulki całkowicie
function deleteBall(ballId, ballName) {
  Swal.fire({
    title: "Usunąć kulki?",
    text: `Czy na pewno chcesz usunąć "${ballName}"? Ta operacja usunie również całą historię zmian.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Usuń",
    confirmButtonColor: "#dc3545",
    cancelButtonText: "Anuluj"
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("config.php", {
        action: "delete_ball",
        ball_id: ballId
      }, function(response) {
        if (response.success) {
          Swal.fire("Usunięto", response.message, "success");
          loadFinishedBalls();
        } else {
          Swal.fire("Błąd", response.error, "error");
        }
      });
    }
  });
}

// Zobacz historię zmian
function viewBallHistory(ballId, ballName) {
  currentHistoryBallId = ballId;
  $("#historyBallName").text(ballName);
  $("#ballHistoryList").html('<div class="text-center"><div class="spinner-border"></div></div>');
  
  $("#ballHistoryModal").modal("show");
  
  $.post("config.php", {
    action: "get_ball_history",
    ball_id: ballId
  }, function(response) {
    if (response.success) {
      renderBallHistory(response.history || []);
    } else {
      $("#ballHistoryList").html(`<div class="alert alert-danger">${response.error}</div>`);
    }
  });
}

// Renderowanie historii
function renderBallHistory(history) {
  const container = $("#ballHistoryList");
  
  if (history.length === 0) {
    container.html('<div class="alert alert-info">Brak historii zmian</div>');
    return;
  }
  
  let html = '<div class="list-group">';
  
  history.forEach(entry => {
    const change = parseFloat(entry.quantity_change);
    const after = parseFloat(entry.quantity_after);
    
    let icon, colorClass, changeText;
    
    switch(entry.change_type) {
      case 'create':
        icon = 'plus-circle-fill';
        colorClass = 'text-success';
        changeText = `Utworzono (+${change.toFixed(2)} kg)`;
        break;
      case 'add':
        icon = 'arrow-up-circle-fill';
        colorClass = 'text-success';
        changeText = `Dodano (+${change.toFixed(2)} kg)`;
        break;
      case 'remove':
        icon = 'arrow-down-circle-fill';
        colorClass = 'text-warning';
        changeText = `Odjęto (-${change.toFixed(2)} kg)`;
        break;
      case 'delete':
        icon = 'trash-fill';
        colorClass = 'text-danger';
        changeText = `Usunięto (-${change.toFixed(2)} kg)`;
        break;
      default:
        icon = 'circle-fill';
        colorClass = 'text-secondary';
        changeText = entry.change_type;
    }
    
    html += `
      <div class="list-group-item">
        <div class="d-flex w-100 justify-content-between align-items-start">
          <div>
            <h6 class="mb-1 ${colorClass}">
              <i class="bi bi-${icon} me-2"></i>${changeText}
            </h6>
            <p class="mb-1 small">${escapeHtml(entry.description || '')}</p>
            <small class="text-muted">
              <i class="bi bi-clock"></i> ${formatDateTime(entry.created_at)}
            </small>
          </div>
          <span class="badge bg-secondary">Stan: ${after.toFixed(2)} kg</span>
        </div>
      </div>
    `;
  });
  
  html += '</div>';
  container.html(html);
}

// Ładowanie kulek przy przełączaniu zakładki
$('a[data-bs-target="#finished-balls"]').on('shown.bs.tab', function() {
  loadFinishedBalls();
});

// Event delegation dla przycisków kulek
$(document).on('click', '.manage-ball-btn', function() {
  const ballId = $(this).data('ball-id');
  const ballName = $(this).data('ball-name');
  const ballQuantity = parseFloat($(this).data('ball-quantity'));
  openManageBallModal(ballId, ballName, ballQuantity);
});

$(document).on('click', '.history-ball-btn', function() {
  const ballId = $(this).data('ball-id');
  const ballName = $(this).data('ball-name');
  viewBallHistory(ballId, ballName);
});

$(document).on('click', '.delete-ball-btn', function() {
  const ballId = $(this).data('ball-id');
  const ballName = $(this).data('ball-name');
  deleteBall(ballId, ballName);
});

