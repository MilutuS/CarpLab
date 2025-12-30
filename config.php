<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Europe/Warsaw');

// Dołącz klasę SMX
require_once __DIR__ . '/smx.php';

// Katalog uploadów
define('UPLOADS_DIR', __DIR__ . '/uploads/');

// Tworzenie katalogu jeśli nie istnieje
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
    error_log("Created uploads directory: " . UPLOADS_DIR);
}

// Test zapisu do katalogu
if (!is_writable(UPLOADS_DIR)) {
    error_log("WARNING: Uploads directory is not writable: " . UPLOADS_DIR);
}

// Funkcja logowania aktywności
function logActivity($type, $description, $details = array()) {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    smx::justQuery(
        "INSERT INTO activity_log (id, user_id, type, description, details, timestamp) VALUES (?, ?, ?, ?, ?, ?)",
        array(uniqid(), $userId, $type, $description, json_encode($details, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'))
    );
}

// Funkcja sprawdzająca czy użytkownik jest zalogowany
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array('success' => false, 'error' => 'Wymagane logowanie', 'require_login' => true));
        exit;
    }
}

// Funkcja sprawdzająca czy użytkownik jest adminem
function requireAdmin() {
    requireLogin();
    // Sprawdź uprawnienia oryginalnego admina, nie przełączonego użytkownika
    $adminId = isset($_SESSION['admin_original_user_id']) ? $_SESSION['admin_original_user_id'] : $_SESSION['user_id'];
    $users = smx::justQuery("SELECT * FROM users WHERE id = ?", array($adminId));
    if (empty($users) || !isset($users[0]['is_admin']) || $users[0]['is_admin'] != 1) {
        echo json_encode(array('success' => false, 'error' => 'Wymagane uprawnienia admina', 'require_admin' => true));
        exit;
    }
}

// Funkcja pobierająca ID zalogowanego użytkownika
function getCurrentUserId() {
    // Jeśli admin jest przełączony na innego użytkownika
    if (isset($_SESSION['admin_switched_user_id']) && $_SESSION['admin_switched_user_id']) {
        return $_SESSION['admin_switched_user_id'];
    }
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Funkcja dekodująca JSON w recepturach
function decodeRecipeJson($recipes) {
    if (empty($recipes)) return $recipes;
    
    foreach ($recipes as &$recipe) {
        if (isset($recipe['ingredients']) && is_string($recipe['ingredients'])) {
            $recipe['ingredients'] = json_decode($recipe['ingredients'], true);
        }
    }
    return $recipes;
}

// Funkcja dekodująca JSON w executed_recipes
function decodeExecutedJson($executed) {
    if (empty($executed)) return $executed;
    
    foreach ($executed as &$exec) {
        if (isset($exec['ingredients_used']) && is_string($exec['ingredients_used'])) {
            $exec['ingredients_used'] = json_decode($exec['ingredients_used'], true);
        }
    }
    return $executed;
}

// Funkcja dekodująca JSON w activity_log
function decodeActivityJson($logs) {
    if (empty($logs)) return $logs;
    
    foreach ($logs as &$log) {
        if (isset($log['details']) && is_string($log['details'])) {
            $log['details'] = json_decode($log['details'], true);
        }
    }
    return $logs;
}

// Obsługa requestów
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'check_session':
            // Sprawdź czy użytkownik jest zalogowany
            if (isset($_SESSION['user_id'])) {
                $users = smx::justQuery("SELECT * FROM users WHERE id = ?", array($_SESSION['user_id']));
                if (!empty($users)) {
                    $user = $users[0];
                    
                    // Jeśli użytkownik jest zablokowany, wyloguj
                    if (isset($user['is_blocked']) && $user['is_blocked'] == 1) {
                        session_destroy();
                        echo json_encode(array('logged_in' => false, 'blocked' => true, 'message' => 'Twoje konto zostało zablokowane'));
                        exit;
                    }
                    
                    $response = array(
                        'logged_in' => true,
                        'user' => $user
                    );
                    
                    // Jeśli admin jest przełączony na innego użytkownika
                    if (isset($_SESSION['admin_switched_user_id'])) {
                        $switchedUser = smx::justQuery("SELECT * FROM users WHERE id = ?", array($_SESSION['admin_switched_user_id']));
                        if (!empty($switchedUser)) {
                            $response['switched_user'] = $switchedUser[0];
                            $response['is_admin_switched'] = true;
                            $response['original_admin'] = $user;
                        }
                    }
                    
                    echo json_encode($response);
                } else {
                    echo json_encode(array('logged_in' => false));
                }
            } else {
                echo json_encode(array('logged_in' => false));
            }
            break;
            
        case 'logout':
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Użytkownik';
            logActivity('logout', "Wylogowano: {$username}");
            session_destroy();
            echo json_encode(array('success' => true));
            break;
            
        case 'get_products':
            // Pobierz produkty użytkownika
            $userId = getCurrentUserId();
            if ($userId) {
                // Zalogowany: tylko własne produkty
                $products = smx::justQuery(
                    "SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC",
                    array($userId)
                );
            } else {
                // Niezalogowany: brak produktów
                $products = array();
            }
            echo json_encode($products);
            break;
        
        case 'get_product':
            // Pobierz pojedynczy produkt (może być innego użytkownika dla publicznych przepisów)
            $productId = isset($_GET['id']) ? $_GET['id'] : '';
            if (empty($productId)) {
                echo json_encode(['success' => false, 'error' => 'Brak ID produktu']);
                exit;
            }
            
            $product = smx::justQuery("SELECT * FROM products WHERE id = ?", array($productId));
            if (!empty($product)) {
                echo json_encode(['success' => true, 'product' => $product[0]]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Produkt nie istnieje']);
            }
            break;
            
        case 'get_recipes':
            // Pobierz przepisy użytkownika + publiczne
            $userId = getCurrentUserId();
            if ($userId) {
                // Zalogowany: własne + publiczne zatwierdzone
                $recipes = smx::justQuery(
                    "SELECT r.*, u.username as author_username FROM recipes r LEFT JOIN users u ON r.user_id = u.id WHERE r.user_id = ? OR (r.is_public = 1 AND r.status = 'approved') ORDER BY r.created_at DESC",
                    array($userId)
                );
            } else {
                // Niezalogowany: tylko publiczne zatwierdzone
                $recipes = smx::justQuery("SELECT r.*, u.username as author_username FROM recipes r LEFT JOIN users u ON r.user_id = u.id WHERE r.is_public = 1 AND r.status = 'approved' ORDER BY r.created_at DESC");
            }
            $recipes = decodeRecipeJson($recipes);
            
            // Dodaj nazwy produktów do składników
            foreach ($recipes as &$recipe) {
                if (isset($recipe['ingredients']) && is_array($recipe['ingredients'])) {
                    foreach ($recipe['ingredients'] as &$ingredient) {
                        $productId = $ingredient['product_id'];
                        $products = smx::justQuery("SELECT name FROM products WHERE id = ?", array($productId));
                        $ingredient['product_name'] = !empty($products) ? $products[0]['name'] : 'Produkt usunięty';
                    }
                }
            }
            
            echo json_encode($recipes);
            break;
            
        case 'get_executed_recipes':
            $userId = getCurrentUserId();
            if ($userId) {
                $executed = smx::justQuery("SELECT * FROM executed_recipes WHERE user_id = ? ORDER BY executed_at DESC", array($userId));
            } else {
                $executed = array();
            }
            $executed = decodeExecutedJson($executed);
            echo json_encode($executed);
            break;
            
        case 'get_product_history':
            $productId = isset($_GET['product_id']) ? $_GET['product_id'] : '';
            $userId = getCurrentUserId();
            if ($userId) {
                $history = smx::justQuery(
                    "SELECT * FROM product_history WHERE product_id = ? AND user_id = ? ORDER BY timestamp DESC",
                    array($productId, $userId)
                );
            } else {
                $history = array();
            }
            echo json_encode($history);
            break;
            
        case 'get_orders':
            $userId = getCurrentUserId();
            if ($userId) {
                $orders = smx::justQuery("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", array($userId));
            } else {
                $orders = array();
            }
            echo json_encode($orders);
            break;
            
        case 'get_activity_log':
            requireAdmin();
            // Admin widzi wszystkie logi, nie tylko swoje
            $log = smx::justQuery("SELECT al.*, u.username FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.timestamp DESC LIMIT 1000");
            $log = decodeActivityJson($log);
            echo json_encode(array('success' => true, 'log' => $log));
            break;
            
        case 'get_users':
            requireAdmin();
            $users = smx::justQuery("SELECT * FROM users ORDER BY created_at DESC");
            echo json_encode(array('success' => true, 'users' => $users));
            break;
            
        case 'get_user_settings':
            requireLogin();
            $userId = getCurrentUserId();
            $users = smx::justQuery("SELECT settings FROM users WHERE id = ?", array($userId));
            
            $settings = array(
                'max_product_weight_g' => 5000,
                'max_product_weight_ml' => 2000,
                'max_product_weight_szt' => 100,
                'recipe_multiplier' => 1.7
            ); // domyślne
            
            if (!empty($users) && !empty($users[0]['settings'])) {
                $userSettings = json_decode($users[0]['settings'], true);
                if ($userSettings) {
                    $settings = array_merge($settings, $userSettings);
                }
            }
            
            echo json_encode(array('success' => true, 'settings' => $settings));
            break;
            
        case 'create_backup':
            // Wyłącz wyświetlanie błędów na czas backupu
            ini_set('display_errors', '0');
            error_reporting(E_ALL);
            
            requireAdmin();
            $timestamp = date('Y-m-d_H-i-s');
            $backupDir = __DIR__ . '/backups';
            
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            
            // Pobierz konfigurację bazy danych z .env
            $envFile = __DIR__ . '/.env';
            
            if (!file_exists($envFile)) {
                error_log('BŁĄD: Plik .env nie istnieje podczas tworzenia backupu');
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Brak pliku .env. Zobacz README_ENV.md']);
                exit;
            }
            
            require_once __DIR__ . '/DotEnv.php';
            try {
                $dotenv = new DotEnv($envFile);
                $dotenv->load();
                
                $dbConfig = [
                    'host' => env('DB_HOST', 'localhost'),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                    'database' => env('DB_DATABASE', 'carplab'),
                    'port' => env('DB_PORT', '3306')
                ];
            } catch (Exception $e) {
                error_log('Błąd ładowania .env dla backupu: ' . $e->getMessage());
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Błąd konfiguracji: ' . $e->getMessage()]);
                exit;
            }
            
            $filename = "backup_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;
            
            // Wykryj system operacyjny i znajdź mysqldump
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $mysqldumpPath = 'mysqldump';
            
            if ($isWindows) {
                // Ścieżki dla WAMP na Windows
                $possiblePaths = [
                    'C:\\wamp64\\bin\\mariadb\\mariadb11.4.9\\bin\\mysqldump.exe',
                    'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe',
                    'C:\\xampp\\mysql\\bin\\mysqldump.exe'
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $mysqldumpPath = $path;
                        break;
                    }
                }
            } else {
                // Linux - znajdź mysqldump używając which
                $whichOutput = [];
                exec('which mysqldump 2>&1', $whichOutput, $whichReturnCode);
                if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
                    $mysqldumpPath = trim($whichOutput[0]);
                } else {
                    // Sprawdź typowe lokalizacje na Linuxie
                    $possiblePaths = [
                        '/usr/bin/mysqldump',
                        '/usr/local/bin/mysqldump',
                        '/usr/local/mysql/bin/mysqldump'
                    ];
                    
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $mysqldumpPath = $path;
                            break;
                        }
                    }
                }
            }
            
            error_log('mysqldump path: ' . $mysqldumpPath);
            
            // Wykryj port
            $port = isset($dbConfig['port']) ? $dbConfig['port'] : '3306';
            
            // Buduj komendę BEZ przekierowania (exec nie lubi > w komendzie)
            if (empty($dbConfig['password'])) {
                $command = sprintf(
                    '%s --host=%s --port=%s --user=%s --single-transaction --routines --triggers %s 2>&1',
                    escapeshellarg($mysqldumpPath),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($port),
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['database'])
                );
            } else {
                $command = sprintf(
                    '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s 2>&1',
                    escapeshellarg($mysqldumpPath),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($port),
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['password']),
                    escapeshellarg($dbConfig['database'])
                );
            }
            
            // Wykonaj backup i zapisz output do pliku
            exec($command, $output, $returnCode);
            
            // Szczegółowe logowanie dla debugowania
            error_log('Backup command: ' . $command);
            error_log('Backup return code: ' . $returnCode);
            error_log('Backup output lines: ' . count($output));
            
            if ($returnCode !== 0) {
                $error = implode("\n", $output);
                error_log('Backup error: ' . $error);
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Błąd mysqldump: ' . $error]);
                exit;
            }
            
            // Zapisz output do pliku (usuń ostrzeżenia mysqldump)
            $filteredOutput = array_filter($output, function($line) {
                // Pomiń ostrzeżenia mysqldump
                return strpos($line, 'mysqldump: [Warning]') === false 
                    && strpos($line, 'Warning: Using a password') === false;
            });
            
            $dumpContent = implode("\n", $filteredOutput);
            if (empty($dumpContent)) {
                error_log('Backup error: Empty dump content');
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Pusta zawartość backupu']);
                exit;
            }
            
            file_put_contents($filepath, $dumpContent);
            
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                error_log('Backup error: File not created or empty');
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Nie udało się zapisać pliku backupu']);
                exit;
            }
            
            logActivity('backup_created', 'Utworzono kopię zapasową: ' . $filename, [
                'filename' => $filename,
                'size' => filesize($filepath)
            ]);
            
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'timestamp' => date('Y-m-d H:i:s'),
                'size' => filesize($filepath)
            ]);
            break;
            
        case 'get_backups':
            $backupDir = __DIR__ . '/backups';
            $backups = [];
            
            if (file_exists($backupDir)) {
                $files = scandir($backupDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $ext = pathinfo($file, PATHINFO_EXTENSION);
                        // Pokaż tylko pliki SQL (nowy format)
                        if ($ext === 'sql') {
                            $filepath = $backupDir . '/' . $file;
                            $backups[] = [
                                'filename' => $file,
                                'size' => filesize($filepath),
                                'date' => date('Y-m-d H:i:s', filemtime($filepath)),
                                'type' => 'sql'
                            ];
                        }
                    }
                }
                usort($backups, function($a, $b) {
                    return strcmp($b['date'], $a['date']);
                });
            }
            
            echo json_encode(['success' => true, 'backups' => $backups]);
            break;
            
        default:
            echo json_encode(['error' => 'Nieznana akcja']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'register':
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            
            // Walidacja
            if (empty($username) || empty($email) || empty($password)) {
                echo json_encode(array('success' => false, 'error' => 'Wszystkie pola są wymagane'));
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(array('success' => false, 'error' => 'Hasło musi mieć min. 6 znaków'));
                exit;
            }
            
            // Sprawdź czy username istnieje
            $existing = smx::justQuery("SELECT id FROM users WHERE username = ?", array($username));
            if (!empty($existing)) {
                echo json_encode(array('success' => false, 'error' => 'Nazwa użytkownika jest zajęta'));
                exit;
            }
            
            // Sprawdź czy email istnieje
            $existing = smx::justQuery("SELECT id FROM users WHERE email = ?", array($email));
            if (!empty($existing)) {
                echo json_encode(array('success' => false, 'error' => 'Email jest już zarejestrowany'));
                exit;
            }
            
            // Utwórz użytkownika
            $userId = uniqid();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            smx::justQuery(
                "INSERT INTO users (id, username, password_hash, email, created_at) VALUES (?, ?, ?, ?, ?)",
                array($userId, $username, $passwordHash, $email, date('Y-m-d H:i:s'))
            );
            
            // Zaloguj automatycznie
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            
            logActivity('register', 'Nowy użytkownik: ' . $username);
            
            echo json_encode(array(
                'success' => true,
                'user' => array('id' => $userId, 'username' => $username, 'email' => $email)
            ));
            break;
            
        case 'admin_change_password':
            requireAdmin();
            
            $userId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            
            if (empty($userId) || empty($newPassword)) {
                echo json_encode(array('success' => false, 'error' => 'Brak wymaganych danych'));
                exit;
            }
            
            // Pobierz dane użytkownika
            $user = smx::justQuery("SELECT username FROM users WHERE id = ?", array($userId));
            if (empty($user)) {
                echo json_encode(array('success' => false, 'error' => 'Użytkownik nie istnieje'));
                exit;
            }
            
            // Zmień hasło
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            smx::justQuery(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                array($passwordHash, $userId)
            );
            
            logActivity('admin_password_change', 'Admin zmienił hasło użytkownikowi: ' . $user[0]['username'], array(
                'target_user_id' => $userId
            ));
            
            echo json_encode(array('success' => true, 'message' => 'Hasło zostało zmienione'));
            break;
            
        case 'login':
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            
            if (empty($username) || empty($password)) {
                echo json_encode(array('success' => false, 'error' => 'Podaj login i hasło'));
                exit;
            }
            
            // Znajdź użytkownika
            $users = smx::justQuery("SELECT * FROM users WHERE username = ?", array($username));
            
            if (empty($users)) {
                echo json_encode(array('success' => false, 'error' => 'Nieprawidłowy login lub hasło'));
                exit;
            }
            
            $user = $users[0];
            
            // Sprawdź czy użytkownik jest zablokowany
            if (isset($user['is_blocked']) && $user['is_blocked'] == 1) {
                echo json_encode(array('success' => false, 'error' => 'Konto zablokowane. Skontaktuj się z administratorem.'));
                exit;
            }
            
            // Sprawdź hasło
            if (!password_verify($password, $user['password_hash'])) {
                echo json_encode(array('success' => false, 'error' => 'Nieprawidłowy login lub hasło'));
                exit;
            }
            
            // Zaloguj
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            logActivity('login', 'Zalogowano: ' . $user['username']);
            
            echo json_encode(array(
                'success' => true,
                'user' => array('id' => $user['id'], 'username' => $user['username'], 'email' => $user['email'])
            ));
            break;
            
        case 'add_product':
            requireLogin(); // Wymaga logowania
            
            // Debug: loguj informacje o przesyłanym pliku
            if (isset($_FILES['image'])) {
                error_log("File upload attempt - Name: " . $_FILES['image']['name'] . 
                         ", Size: " . $_FILES['image']['size'] . 
                         ", Type: " . $_FILES['image']['type'] . 
                         ", Error: " . $_FILES['image']['error'] .
                         ", Tmp: " . $_FILES['image']['tmp_name']);
            } else {
                error_log("No file in upload for add_product");
            }
            
            $imageUrl = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowedExtensions)) {
                    error_log("Invalid file extension: {$ext}");
                    echo json_encode(['success' => false, 'error' => 'Nieprawidłowy format pliku. Dozwolone: JPG, PNG, GIF, WEBP']);
                    exit;
                }
                
                $filename = uniqid('product_') . '.' . $ext;
                $destination = UPLOADS_DIR . $filename;
                
                if (!file_exists(UPLOADS_DIR)) {
                    mkdir(UPLOADS_DIR, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $imageUrl = 'uploads/' . $filename;
                    error_log("Image uploaded successfully: {$imageUrl}");
                } else {
                    error_log("Failed to move uploaded file to: {$destination}");
                }
            } else if (isset($_FILES['image'])) {
                $errorCode = $_FILES['image']['error'];
                error_log("Upload error code: {$errorCode}");
                
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    echo json_encode(['success' => false, 'error' => 'Plik jest za duży. Maksymalny rozmiar to 20MB.']);
                    exit;
                }
            }
            
            $productId = uniqid();
            $userId = getCurrentUserId();
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $weight = floatval(isset($_POST['weight']) ? $_POST['weight'] : 0);
            $unit = isset($_POST['unit']) ? $_POST['unit'] : 'g';
            $price = floatval(isset($_POST['price_per_1000']) ? $_POST['price_per_1000'] : 0);
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $link = isset($_POST['link']) ? $_POST['link'] : '';
            $createdAt = date('Y-m-d H:i:s');
            
            smx::justQuery(
                "INSERT INTO products (id, user_id, name, weight, unit, price_per_1000, description, link, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array($productId, $userId, $name, $weight, $unit, $price, $description, $link, $imageUrl, $createdAt)
            );
            
            smx::justQuery(
                "INSERT INTO product_history (id, user_id, product_id, action, amount, unit, timestamp) VALUES (?, ?, ?, 'created', ?, ?, ?)",
                array(uniqid(), $userId, $productId, $weight, $unit, $createdAt)
            );
            
            logActivity('product_created', "Dodano produkt: {$name}", [
                'product_id' => $productId,
                'product_name' => $name,
                'weight' => $weight,
                'unit' => $unit
            ]);
            
            echo json_encode([
                'success' => true, 
                'product' => [
                    'id' => $productId,
                    'name' => $name,
                    'weight' => $weight,
                    'unit' => $unit,
                    'price_per_1000' => $price,
                    'image' => $imageUrl,
                    'created_at' => $createdAt
                ]
            ]);
            break;
            
        case 'update_product_weight':
            requireLogin();
            
            $productId = isset($_POST['id']) ? $_POST['id'] : '';
            $addWeight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;
            $newUnit = isset($_POST['unit']) ? $_POST['unit'] : null;
            $newPrice = isset($_POST['price_per_1000']) ? floatval($_POST['price_per_1000']) : null;
            $description = isset($_POST['description']) ? $_POST['description'] : null;
            $link = isset($_POST['link']) ? $_POST['link'] : null;
            
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowedExtensions)) {
                    error_log("Invalid file extension on update: {$ext}");
                } else {
                    $imageName = uniqid() . '.' . $ext;
                    $destination = UPLOADS_DIR . $imageName;
                    
                    if (!file_exists(UPLOADS_DIR)) {
                        mkdir(UPLOADS_DIR, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $imagePath = 'uploads/' . $imageName;
                    } else {
                        error_log("Failed to move uploaded file on update to: {$destination}");
                    }
                }
            }
            
            $product = smx::justQuery("SELECT * FROM products WHERE id = ?", [$productId]);
            
            if (empty($product)) {
                echo json_encode(['success' => false, 'error' => 'Produkt nie znaleziony']);
                break;
            }
            
            $product = $product[0];
            
            if ($addWeight !== null) {
                smx::justQuery("UPDATE products SET weight = weight + ? WHERE id = ?", [$addWeight, $productId]);
                
                $action = $addWeight > 0 ? 'added' : 'removed';
                $userId = getCurrentUserId();
                smx::justQuery(
                    "INSERT INTO product_history (id, user_id, product_id, action, amount, unit, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [uniqid(), $userId, $productId, $action, abs($addWeight), $product['unit'], date('Y-m-d H:i:s')]
                );
                
                $actionText = $addWeight > 0 ? "Dodano" : "Odjęto";
                logActivity('product_weight_changed', "{$actionText} ".abs($addWeight)." {$product['unit']} - {$product['name']}", [
                    'product_id' => $productId,
                    'amount' => $addWeight
                ]);
            }
            
            if ($newUnit !== null) {
                smx::justQuery("UPDATE products SET unit = ? WHERE id = ?", [$newUnit, $productId]);
                logActivity('product_unit_changed', "Zmieniono jednostkę produktu {$product['name']} na {$newUnit}", ['new_unit' => $newUnit]);
            }
            
            if ($newPrice !== null) {
                smx::justQuery("UPDATE products SET price_per_1000 = ? WHERE id = ?", [$newPrice, $productId]);
                logActivity('product_price_changed', "Zmieniono cenę produktu {$product['name']}", ['new_price' => $newPrice]);
            }
            
            if ($description !== null) {
                smx::justQuery("UPDATE products SET description = ? WHERE id = ?", [$description, $productId]);
            }
            
            if ($link !== null) {
                smx::justQuery("UPDATE products SET link = ? WHERE id = ?", [$link, $productId]);
            }
            
            if ($imagePath !== null) {
                smx::justQuery("UPDATE products SET image = ? WHERE id = ?", [$imagePath, $productId]);
                logActivity('product_image_changed', "Zmieniono zdjęcie produktu {$product['name']}", ['new_image' => $imagePath]);
            }
            
            $products = smx::justQuery("SELECT * FROM products");
            echo json_encode(['success' => true, 'products' => $products]);
            break;
            
        case 'add_recipe':
            try {
                requireLogin();
                
                $imageUrl = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowedExtensions)) {
                    error_log("Invalid file extension: {$ext}");
                    echo json_encode(['success' => false, 'error' => 'Nieprawidłowy format pliku. Dozwolone: JPG, PNG, GIF, WEBP']);
                    exit;
                }
                
                $filename = uniqid('recipe_') . '.' . $ext;
                $destination = UPLOADS_DIR . $filename;
                
                if (!file_exists(UPLOADS_DIR)) {
                    mkdir(UPLOADS_DIR, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $imageUrl = 'uploads/' . $filename;
                    error_log("Recipe image uploaded successfully: {$imageUrl}");
                } else {
                    error_log("Failed to move uploaded recipe image to: {$destination}");
                }
            } else if (isset($_FILES['image'])) {
                $errorCode = $_FILES['image']['error'];
                error_log("Recipe upload error code: {$errorCode}");
                
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    echo json_encode(['success' => false, 'error' => 'Plik jest za duży. Maksymalny rozmiar to 20MB.']);
                    exit;
                }
            }
            
            $recipeId = uniqid();
            $userId = getCurrentUserId();
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $ingredients = isset($_POST['ingredients']) ? $_POST['ingredients'] : '[]';
            $isPublic = isset($_POST['is_public']) && $_POST['is_public'] == '1' ? 1 : 0;
            // Jeśli przepis ma być publiczny, ustaw status na 'pending', inaczej 'approved'
            $status = $isPublic ? 'pending' : 'approved';
            $multiplier = isset($_POST['multiplier']) ? floatval($_POST['multiplier']) : 1.7;
            $createdAt = date('Y-m-d H:i:s');
            
            $result = smx::justQuery(
                "INSERT INTO recipes (id, user_id, name, description, multiplier, ingredients, is_public, status, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$recipeId, $userId, $name, $description, $multiplier, $ingredients, $isPublic, $status, $imageUrl, $createdAt]
            );
            
            if (!$result) {
                throw new Exception("Nie udało się zapisać przepisu do bazy danych");
            }
            
            $ingredientsArray = json_decode($ingredients, true);
            
            logActivity('recipe_created', "Dodano przepis: {$name}", [
                'recipe_id' => $recipeId,
                'ingredients_count' => count($ingredientsArray)
            ]);
            
            echo json_encode([
                'success' => true, 
                'recipe' => [
                    'id' => $recipeId,
                    'name' => $name,
                    'description' => $description,
                    'ingredients' => $ingredientsArray,
                    'image' => $imageUrl,
                    'created_at' => $createdAt
                ]
            ]);
            } catch (Exception $e) {
                error_log("Error in add_recipe: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Błąd serwera: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_recipe':
            try {
                requireLogin();
                
                // Debug: loguj informacje o przesyłanym pliku
                if (isset($_FILES['image'])) {
                    error_log("UPDATE_RECIPE - File upload attempt - Name: " . $_FILES['image']['name'] . 
                             ", Size: " . $_FILES['image']['size'] . 
                             ", Type: " . $_FILES['image']['type'] . 
                             ", Error: " . $_FILES['image']['error'] .
                             ", Tmp: " . $_FILES['image']['tmp_name']);
                } else {
                    error_log("UPDATE_RECIPE - No file in upload");
                }
                
                $recipeId = isset($_POST['recipe_id']) ? $_POST['recipe_id'] : '';
            $userId = getCurrentUserId();
            
            $oldRecipes = smx::justQuery("SELECT * FROM recipes WHERE id = ?", [$recipeId]);
            
            if (empty($oldRecipes)) {
                echo json_encode(['success' => false, 'error' => 'Przepis nie znaleziony']);
                exit;
            }
            
            $oldRecipe = $oldRecipes[0];
            
            // Sprawdź czy to właściciel przepisu lub admin
            $currentUserData = smx::justQuery("SELECT is_admin FROM users WHERE id = ?", [$userId]);
            $isAdmin = !empty($currentUserData) && $currentUserData[0]['is_admin'] == 1;
            
            if ($oldRecipe['user_id'] !== $userId && !$isAdmin) {
                echo json_encode(['success' => false, 'error' => 'Brak uprawnień do edycji tego przepisu']);
                exit;
            }
            $oldRecipe['ingredients'] = json_decode($oldRecipe['ingredients'], true);
            
            $imageUrl = $oldRecipe['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                error_log("UPDATE_RECIPE - Processing image with extension: '{$ext}'");
                
                if (!in_array($ext, $allowedExtensions)) {
                    error_log("Invalid file extension on recipe update: {$ext}");
                    echo json_encode(['success' => false, 'error' => "Nieprawidłowy format pliku: {$ext}. Dozwolone: JPG, PNG, GIF, WEBP"]);
                    exit;
                }
                
                $filename = uniqid('recipe_') . '.' . $ext;
                $destination = UPLOADS_DIR . $filename;
                
                error_log("UPDATE_RECIPE - Attempting to save to: {$destination}");
                
                if (!file_exists(UPLOADS_DIR)) {
                    mkdir(UPLOADS_DIR, 0777, true);
                    error_log("UPDATE_RECIPE - Created uploads directory");
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $imageUrl = 'uploads/' . $filename;
                    error_log("Recipe image updated successfully: {$imageUrl}");
                    
                    // Usuń stare zdjęcie jeśli istnieje i jest inne niż domyślne
                    if ($oldRecipe['image'] && $oldRecipe['image'] !== 'uploads/default.png' && file_exists($oldRecipe['image'])) {
                        @unlink($oldRecipe['image']);
                        error_log("UPDATE_RECIPE - Deleted old image: " . $oldRecipe['image']);
                    }
                } else {
                    error_log("Failed to move uploaded recipe image from {$_FILES['image']['tmp_name']} to: {$destination}");
                    // Nie przerywaj - może użytkownik chce tylko zaktualizować dane bez zdjęcia
                }
            } else if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Plik jest za duży (przekracza upload_max_filesize)',
                    UPLOAD_ERR_FORM_SIZE => 'Plik jest za duży (przekracza MAX_FILE_SIZE)',
                    UPLOAD_ERR_PARTIAL => 'Plik został przesłany tylko częściowo',
                    UPLOAD_ERR_NO_FILE => 'Nie przesłano pliku',
                    UPLOAD_ERR_NO_TMP_DIR => 'Brak folderu tymczasowego',
                    UPLOAD_ERR_CANT_WRITE => 'Błąd zapisu pliku na dysk',
                    UPLOAD_ERR_EXTENSION => 'Rozszerzenie PHP zatrzymało upload'
                ];
                $errorCode = $_FILES['image']['error'];
                $errorMsg = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : "Nieznany błąd: {$errorCode}";
                error_log("UPDATE_RECIPE - Upload error code: {$errorCode} - {$errorMsg}");
                
                // Jeśli to błąd rozmiaru, zwróć przyjazny komunikat
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    echo json_encode(['success' => false, 'error' => 'Plik jest za duży. Maksymalny rozmiar to 20MB. Spróbuj zmniejszyć zdjęcie.']);
                    exit;
                }
            }
            
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $ingredients = isset($_POST['ingredients']) ? $_POST['ingredients'] : '[]';
            $multiplier = isset($_POST['multiplier']) ? floatval($_POST['multiplier']) : 1.7;
            $isPublic = isset($_POST['is_public']) && $_POST['is_public'] == '1' ? 1 : 0;
            $newIngredients = json_decode($ingredients, true);
            $updatedAt = date('Y-m-d H:i:s');
            
            // Logika statusu:
            // - Jeśli zmienia na publiczny (was private, now public) -> status = 'pending' (wymaga moderacji)
            // - Jeśli pozostaje publiczny i edytuje treść -> status = 'pending' (wymaga ponownej moderacji)
            // - Jeśli zmienia na prywatny -> status = 'approved' (prywatne są zawsze zatwierdzone)
            $status = $isPublic ? 'pending' : 'approved';
            
            smx::justQuery(
                "UPDATE recipes SET name = ?, description = ?, multiplier = ?, ingredients = ?, is_public = ?, status = ?, image = ?, updated_at = ? WHERE id = ?",
                [$name, $description, $multiplier, $ingredients, $isPublic, $status, $imageUrl, $updatedAt, $recipeId]
            );
            
            logActivity('recipe_updated', "Zaktualizowano przepis: {$name}", [
                'recipe_id' => $recipeId,
                'ingredients_count' => count($newIngredients)
            ]);
            
            echo json_encode([
                'success' => true, 
                'recipe' => [
                    'id' => $recipeId,
                    'name' => $name,
                    'description' => $description,
                    'ingredients' => $newIngredients,
                    'image' => $imageUrl,
                    'created_at' => $oldRecipe['created_at'],
                    'updated_at' => $updatedAt
                ]
            ]);
            } catch (Exception $e) {
                error_log("Error in update_recipe: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Błąd serwera: ' . $e->getMessage()]);
            }
            break;
            
        case 'execute_recipe':
            requireLogin();
            
            $recipeId = isset($_POST['recipe_id']) ? $_POST['recipe_id'] : '';
            $kgAmount = floatval(isset($_POST['kg_amount']) ? $_POST['kg_amount'] : 1);
            $multiplier = floatval(isset($_POST['multiplier']) ? $_POST['multiplier'] : 1.7);
            $reduceStock = (isset($_POST['reduce_stock']) && $_POST['reduce_stock'] === 'true') ? 1 : 0;
            
            // Walidacja mnożnika
            if ($multiplier < 1 || $multiplier > 5) {
                echo json_encode(['success' => false, 'error' => 'Mnożnik musi być między 1 a 5']);
                exit;
            }
            
            $recipes = smx::justQuery("SELECT * FROM recipes WHERE id = ?", [$recipeId]);
            
            if (empty($recipes)) {
                echo json_encode(['success' => false, 'error' => 'Przepis nie znaleziony']);
                exit;
            }
            
            $recipe = $recipes[0];
            $recipe['ingredients'] = json_decode($recipe['ingredients'], true);
            
            $products = smx::justQuery("SELECT * FROM products");
            
            $calculatedIngredients = [];
            $errors = [];
            $totalCost = 0;
            
            foreach ($recipe['ingredients'] as $ingredient) {
                $productFound = false;
                
                foreach ($products as $product) {
                    if ($product['id'] === $ingredient['product_id']) {
                        $productFound = true;
                        $neededAmount = 0;
                        
                        if ($ingredient['type'] === 'dry') {
                            $neededAmount = ($ingredient['percentage'] / 100) * $kgAmount * 1000;
                        } else {
                            $neededAmount = $ingredient['amount_per_kg'] * $kgAmount;
                        }
                        
                        $ingredientCost = 0;
                        if (isset($product['price_per_1000']) && $product['price_per_1000'] > 0) {
                            $ingredientCost = ($neededAmount / 1000) * $product['price_per_1000'];
                            $totalCost += $ingredientCost;
                        }
                        
                        $calculatedIngredients[] = [
                            'product_id' => $product['id'],
                            'product_name' => $product['name'],
                            'needed' => $neededAmount,
                            'unit' => $product['unit'],
                            'available' => $product['weight'],
                            'type' => $ingredient['type'],
                            'cost' => $ingredientCost,
                            'price_per_1000' => isset($product['price_per_1000']) ? $product['price_per_1000'] : 0
                        ];
                        
                        // Sprawdzaj dostępność tylko jeśli reduceStock jest włączone
                        if ($reduceStock && $product['weight'] < $neededAmount) {
                            $errors[] = "Niewystarczająca ilość: {$product['name']} (potrzeba {$neededAmount}{$product['unit']}, dostępne {$product['weight']}{$product['unit']})";
                        }
                        break;
                    }
                }
                
                if (!$productFound) {
                    $errors[] = "Produkt nie znaleziony: ID {$ingredient['product_id']}";
                }
            }
            
            $actualBallsAmount = $kgAmount * $multiplier; // Rzeczywista ilość kulek
            $costPerKg = $actualBallsAmount > 0 ? ($totalCost / $actualBallsAmount) : 0;
            
            if (!empty($errors)) {
                echo json_encode([
                    'success' => false, 
                    'errors' => $errors, 
                    'calculated' => $calculatedIngredients,
                    'total_cost' => round($totalCost, 2),
                    'cost_per_kg' => round($costPerKg, 2),
                    'multiplier' => $multiplier,
                    'dry_weight' => $kgAmount,
                    'actual_balls_amount' => round($actualBallsAmount, 2),
                    'reduce_stock' => $reduceStock
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'calculated' => $calculatedIngredients,
                'total_cost' => round($totalCost, 2),
                'cost_per_kg' => round($costPerKg, 2),
                'multiplier' => $multiplier,
                'dry_weight' => $kgAmount,
                'actual_balls_amount' => round($actualBallsAmount, 2),
                'reduce_stock' => $reduceStock
            ]);
            break;
            
        case 'confirm_execute_recipe':
            requireLogin();
            
            $recipeId = isset($_POST['recipe_id']) ? $_POST['recipe_id'] : '';
            $kgAmount = floatval(isset($_POST['kg_amount']) ? $_POST['kg_amount'] : 1);
            $multiplier = floatval(isset($_POST['multiplier']) ? $_POST['multiplier'] : 1.7);
            $reduceStock = (isset($_POST['reduce_stock']) && $_POST['reduce_stock'] === 'true') ? 1 : 0;
            
            $recipes = smx::justQuery("SELECT * FROM recipes WHERE id = ?", [$recipeId]);
            
            if (empty($recipes)) {
                echo json_encode(['success' => false, 'error' => 'Przepis nie znaleziony']);
                exit;
            }
            
            $recipe = $recipes[0];
            $recipe['ingredients'] = json_decode($recipe['ingredients'], true);
            
            $products = smx::justQuery("SELECT * FROM products");
            
            $calculatedIngredients = [];
            $ingredientsUsed = [];
            $totalCost = 0;
            $timestamp = date('Y-m-d H:i:s');
            
            foreach ($recipe['ingredients'] as $ingredient) {
                foreach ($products as $product) {
                    if ($product['id'] === $ingredient['product_id']) {
                        $neededAmount = 0;
                        
                        if ($ingredient['type'] === 'dry') {
                            $neededAmount = ($ingredient['percentage'] / 100) * $kgAmount * 1000;
                        } else {
                            $neededAmount = $ingredient['amount_per_kg'] * $kgAmount;
                        }
                        
                        $ingredientCost = 0;
                        if (isset($product['price_per_1000']) && $product['price_per_1000'] > 0) {
                            $ingredientCost = ($neededAmount / 1000) * $product['price_per_1000'];
                            $totalCost += $ingredientCost;
                        }
                        
                        $calculatedIngredients[] = [
                            'product_id' => $product['id'],
                            'product_name' => $product['name'],
                            'needed' => $neededAmount,
                            'unit' => $product['unit']
                        ];
                        
                        $ingredientsUsed[] = [
                            'product_id' => $product['id'],
                            'product_name' => $product['name'],
                            'amount' => $neededAmount,
                            'unit' => $product['unit'],
                            'cost' => $ingredientCost
                        ];
                        break;
                    }
                }
            }
            
            $userId = getCurrentUserId();
            
            // Zredukuj stan tylko jeśli opcja jest włączona
            if ($reduceStock) {
                foreach ($calculatedIngredients as $calcIng) {
                    smx::justQuery(
                        "UPDATE products SET weight = weight - ? WHERE id = ?",
                        [$calcIng['needed'], $calcIng['product_id']]
                    );
                    
                    smx::justQuery(
                        "INSERT INTO product_history (id, user_id, product_id, action, amount, unit, recipe_name, timestamp) VALUES (?, ?, ?, 'used', ?, ?, ?, ?)",
                        [uniqid(), $userId, $calcIng['product_id'], $calcIng['needed'], $calcIng['unit'], $recipe['name'], $timestamp]
                    );
                }
            }
            
            $actualBallsAmount = $kgAmount * $multiplier;
            $costPerKg = $actualBallsAmount > 0 ? ($totalCost / $actualBallsAmount) : 0;
            $executedId = uniqid();
            
            smx::justQuery(
                "INSERT INTO executed_recipes (id, user_id, recipe_id, recipe_name, kg_amount, ingredients_used, total_cost, cost_per_kg, multiplier, actual_balls_amount, reduce_stock, executed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$executedId, $userId, $recipeId, $recipe['name'], $kgAmount, json_encode($ingredientsUsed, JSON_UNESCAPED_UNICODE), $totalCost, $costPerKg, $multiplier, $actualBallsAmount, $reduceStock, $timestamp]
            );
            
            $stockAction = $reduceStock ? "ze zdjęciem ze stanu" : "bez zdjęcia ze stanu";
            logActivity('recipe_executed', "Wykonano przepis: {$recipe['name']} ({$kgAmount} kg suchych, {$actualBallsAmount} kg kulek, mnożnik {$multiplier}, {$stockAction})", [
                'recipe_id' => $recipeId,
                'kg_amount' => $kgAmount,
                'multiplier' => $multiplier,
                'actual_balls_amount' => $actualBallsAmount,
                'reduce_stock' => $reduceStock
            ]);
            
            // Dodaj gotowe kulki do magazynu (filtruj po user_id)
            $existingBalls = smx::justQuery("SELECT id, quantity FROM finished_balls WHERE recipe_id = ? AND user_id = ?", [$recipeId, $userId]);
            
            if (!empty($existingBalls)) {
                // Zaktualizuj ilość
                $ballId = $existingBalls[0]['id'];
                $oldQuantity = floatval($existingBalls[0]['quantity']);
                $newQuantity = $oldQuantity + $actualBallsAmount;
                
                smx::justQuery(
                    "UPDATE finished_balls SET quantity = ?, updated_at = ? WHERE id = ?",
                    [$newQuantity, $timestamp, $ballId]
                );
                
                // Dodaj do historii kulek
                smx::justQuery(
                    "INSERT INTO ball_history (id, ball_id, change_type, quantity_change, quantity_after, description, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [uniqid(), $ballId, 'add', $actualBallsAmount, $newQuantity, "Produkcja z {$kgAmount} kg składników (mnożnik {$multiplier})", $timestamp]
                );
            } else {
                // Utwórz nowy wpis kulek
                $ballId = uniqid();
                smx::justQuery(
                    "INSERT INTO finished_balls (id, user_id, recipe_id, recipe_name, quantity, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$ballId, $userId, $recipeId, $recipe['name'], $actualBallsAmount, $timestamp, $timestamp]
                );
                
                // Dodaj do historii kulek
                smx::justQuery(
                    "INSERT INTO ball_history (id, ball_id, change_type, quantity_change, quantity_after, description, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [uniqid(), $ballId, 'create', $actualBallsAmount, $actualBallsAmount, "Pierwsza produkcja z {$kgAmount} kg składników (mnożnik {$multiplier})", $timestamp]
                );
            }
            
            $products = smx::justQuery("SELECT * FROM products");
            
            echo json_encode([
                'success' => true, 
                'message' => "Przepis wykonany! Zrobiono {$kgAmount}kg kulek.",
                'products' => $products
            ]);
            break;
            
        case 'delete_product':
            requireLogin();
            
            $productId = isset($_POST['id']) ? $_POST['id'] : '';
            $userId = getCurrentUserId();
            
            $products = smx::justQuery("SELECT * FROM products WHERE id = ?", [$productId]);
            
            if (!empty($products)) {
                $product = $products[0];
                
                if ($product['user_id'] !== $userId) {
                    echo json_encode(['success' => false, 'error' => 'Brak uprawnień do usunięcia tego produktu']);
                    exit;
                }
                
                smx::justQuery("DELETE FROM products WHERE id = ?", [$productId]);
                
                logActivity('product_deleted', "Usunięto produkt: {$product['name']}", [
                    'product_id' => $productId
                ]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_recipe':
            requireLogin();
            
            $recipeId = isset($_POST['id']) ? $_POST['id'] : '';
            $userId = getCurrentUserId();
            
            $recipes = smx::justQuery("SELECT * FROM recipes WHERE id = ?", [$recipeId]);
            
            if (!empty($recipes)) {
                $recipe = $recipes[0];
                
                // Sprawdź czy to właściciel przepisu lub admin
                $currentUserData = smx::justQuery("SELECT is_admin FROM users WHERE id = ?", [$userId]);
                $isAdmin = !empty($currentUserData) && $currentUserData[0]['is_admin'] == 1;
                
                if ($recipe['user_id'] !== $userId && !$isAdmin) {
                    echo json_encode(['success' => false, 'error' => 'Brak uprawnień do usunięcia tego przepisu']);
                    exit;
                }
                
                smx::justQuery("DELETE FROM recipes WHERE id = ?", [$recipeId]);
                
                logActivity('recipe_deleted', "Usunięto przepis: {$recipe['name']}", [
                    'recipe_id' => $recipeId
                ]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'undo_executed_recipe':
            requireLogin();
            
            $executedId = isset($_POST['executed_id']) ? $_POST['executed_id'] : '';
            $userId = getCurrentUserId();
            
            $executed = smx::justQuery("SELECT * FROM executed_recipes WHERE id = ?", [$executedId]);
            
            if (empty($executed)) {
                echo json_encode(['success' => false, 'error' => 'Wykonany przepis nie znaleziony']);
                exit;
            }
            
            $executedRecipe = $executed[0];
            
            if ($executedRecipe['user_id'] !== $userId) {
                echo json_encode(['success' => false, 'error' => 'Brak uprawnień do cofnięcia tego przepisu']);
                exit;
            }
            $executedRecipe['ingredients_used'] = json_decode($executedRecipe['ingredients_used'], true);
            
            // Czy zwracać składniki do magazynu (tylko jeśli reduce_stock był true)
            $shouldReturnStock = isset($executedRecipe['reduce_stock']) && $executedRecipe['reduce_stock'] == 1;
            
            if ($shouldReturnStock) {
                foreach ($executedRecipe['ingredients_used'] as $ing) {
                    smx::justQuery(
                        "UPDATE products SET weight = weight + ? WHERE id = ?",
                        [$ing['amount'], $ing['product_id']]
                    );
                    
                    smx::justQuery(
                        "INSERT INTO product_history (id, user_id, product_id, action, amount, unit, timestamp, note) VALUES (?, ?, ?, 'added', ?, ?, ?, ?)",
                        [uniqid(), $userId, $ing['product_id'], $ing['amount'], $ing['unit'], date('Y-m-d H:i:s'), 'Cofnięto przepis: ' . $executedRecipe['recipe_name']]
                    );
                }
            }
            
            // Usuń kulki z magazynu gotowych kulek
            $actualBallsAmount = isset($executedRecipe['actual_balls_amount']) ? floatval($executedRecipe['actual_balls_amount']) : 0;
            $recipeId = $executedRecipe['recipe_id'];
            
            if ($actualBallsAmount > 0 && !empty($recipeId)) {
                $existingBalls = smx::justQuery("SELECT id, quantity FROM finished_balls WHERE recipe_id = ? AND user_id = ?", [$recipeId, $userId]);
                
                if (!empty($existingBalls)) {
                    $ballId = $existingBalls[0]['id'];
                    $currentQuantity = floatval($existingBalls[0]['quantity']);
                    $newQuantity = $currentQuantity - $actualBallsAmount;
                    
                    if ($newQuantity > 0) {
                        // Zaktualizuj ilość
                        smx::justQuery(
                            "UPDATE finished_balls SET quantity = ?, updated_at = ? WHERE id = ?",
                            [$newQuantity, date('Y-m-d H:i:s'), $ballId]
                        );
                        
                        // Dodaj do historii kulek
                        smx::justQuery(
                            "INSERT INTO ball_history (id, ball_id, change_type, quantity_change, quantity_after, description, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [uniqid(), $ballId, 'remove', $actualBallsAmount, $newQuantity, "Cofnięto produkcję: {$executedRecipe['recipe_name']}", date('Y-m-d H:i:s')]
                        );
                    } else {
                        // Usuń całkowicie (historia zostanie usunięta przez CASCADE)
                        smx::justQuery("DELETE FROM finished_balls WHERE id = ?", [$ballId]);
                    }
                }
            }
            
            smx::justQuery("DELETE FROM executed_recipes WHERE id = ?", [$executedId]);
            
            $stockInfo = $shouldReturnStock ? " ze zwrotem składników" : " bez zwrotu składników";
            logActivity('recipe_undone', "Cofnięto przepis: {$executedRecipe['recipe_name']}{$stockInfo}", [
                'executed_id' => $executedId,
                'returned_stock' => $shouldReturnStock
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Przepis został cofnięty.'
            ]);
            break;
            
        case 'clear_all_history':
            requireLogin();
            
            smx::justQuery("TRUNCATE TABLE activity_log");
            logActivity('logs_cleared', 'Wyczyszczono logi systemowe');
            echo json_encode([
                'success' => true,
                'message' => 'Logi zostały wyczyszczone.'
            ]);
            break;
            
        case 'restore_backup':
            // Wyłącz wyświetlanie błędów
            ini_set('display_errors', '0');
            error_reporting(E_ALL);
            
            requireAdmin();
            
            $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
            $backupDir = __DIR__ . '/backups';
            $filepath = $backupDir . '/' . $filename;
            
            if (!file_exists($filepath)) {
                echo json_encode(['success' => false, 'error' => 'Plik nie istnieje']);
                exit;
            }
            
            // Sprawdź czy to jest plik SQL
            if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'sql') {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy format pliku']);
                exit;
            }
            
            // Pobierz konfigurację bazy danych z .env
            $envFile = __DIR__ . '/.env';
            
            if (!file_exists($envFile)) {
                error_log('BŁĄD: Plik .env nie istnieje podczas przywracania backupu');
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Brak pliku .env. Zobacz README_ENV.md']);
                exit;
            }
            
            require_once __DIR__ . '/DotEnv.php';
            try {
                $dotenv = new DotEnv($envFile);
                $dotenv->load();
                
                $dbConfig = [
                    'host' => env('DB_HOST', 'localhost'),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                    'database' => env('DB_DATABASE', 'carplab'),
                    'port' => env('DB_PORT', '3306')
                ];
            } catch (Exception $e) {
                error_log('Błąd ładowania .env dla przywracania: ' . $e->getMessage());
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Błąd konfiguracji: ' . $e->getMessage()]);
                exit;
            }
            
            // Wykryj system operacyjny i znajdź mysql
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $mysqlPath = 'mysql';
            
            if ($isWindows) {
                // Ścieżki dla WAMP na Windows
                $possiblePaths = [
                    'C:\\wamp64\\bin\\mariadb\\mariadb11.4.9\\bin\\mysql.exe',
                    'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysql.exe',
                    'C:\\xampp\\mysql\\bin\\mysql.exe'
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $mysqlPath = $path;
                        break;
                    }
                }
            } else {
                // Linux - znajdź mysql używając which
                $whichOutput = [];
                exec('which mysql 2>&1', $whichOutput, $whichReturnCode);
                if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
                    $mysqlPath = trim($whichOutput[0]);
                } else {
                    // Sprawdź typowe lokalizacje na Linuxie
                    $possiblePaths = [
                        '/usr/bin/mysql',
                        '/usr/local/bin/mysql',
                        '/usr/local/mysql/bin/mysql'
                    ];
                    
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $mysqlPath = $path;
                            break;
                        }
                    }
                }
            }
            
            error_log('mysql path: ' . $mysqlPath);
            error_log('mysql path: ' . $mysqlPath);
            
            // Wykryj port
            $port = isset($dbConfig['port']) ? $dbConfig['port'] : '3306';
            
            // Wczytaj zawartość pliku SQL
            $sqlContent = file_get_contents($filepath);
            if ($sqlContent === false) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Nie można odczytać pliku backupu']);
                exit;
            }
            
            // Użyj mysql client do wykonania SQL
            if (empty($dbConfig['password'])) {
                $command = sprintf(
                    '%s --host=%s --port=%s --user=%s %s 2>&1',
                    escapeshellarg($mysqlPath),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($port),
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['database'])
                );
            } else {
                $command = sprintf(
                    '%s --host=%s --port=%s --user=%s --password=%s %s 2>&1',
                    escapeshellarg($mysqlPath),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($port),
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['password']),
                    escapeshellarg($dbConfig['database'])
                );
            }
            
            // Utwórz deskryptor do pipe stdin
            $descriptorspec = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];
            
            error_log('Restore command: ' . $command);
            
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                // Wyślij SQL do stdin
                fwrite($pipes[0], $sqlContent);
                fclose($pipes[0]);
                
                // Odczytaj output
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                
                $returnCode = proc_close($process);
                
                error_log('Restore return code: ' . $returnCode);
                error_log('Restore stdout: ' . $stdout);
                error_log('Restore stderr: ' . $stderr);
                
                if ($returnCode !== 0) {
                    $error = !empty($stderr) ? $stderr : $stdout;
                    error_log('Restore error: ' . $error);
                    if (ob_get_length()) ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Błąd przywracania: ' . $error]);
                    exit;
                }
            } else {
                error_log('Restore error: Failed to open process');
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Nie można uruchomić mysql client']);
                exit;
            }
            
            logActivity('backup_restored', 'Przywrócono dane z kopii zapasowej: ' . $filename);
            
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Kopia zapasowa przywrócona pomyślnie.'
            ]);
            break;
            
        case 'delete_backup':
            requireLogin();
            
            $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
            $backupDir = __DIR__ . '/backups';
            $filepath = $backupDir . '/' . $filename;
            
            if (file_exists($filepath)) {
                unlink($filepath);
                logActivity('backup_deleted', 'Usunięto kopię zapasową: ' . $filename);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'add_order':
            requireLogin();
            
            $imageUrl = null;
            
            if (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
                $imageUrl = $_POST['existing_image'];
            } else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowedExtensions)) {
                    error_log("Invalid file extension on order: {$ext}");
                    echo json_encode(['success' => false, 'error' => 'Nieprawidłowy format pliku. Dozwolone: JPG, PNG, GIF, WEBP']);
                    exit;
                }
                
                $filename = uniqid('order_') . '.' . $ext;
                $destination = UPLOADS_DIR . $filename;
                
                if (!file_exists(UPLOADS_DIR)) {
                    mkdir(UPLOADS_DIR, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $imageUrl = 'uploads/' . $filename;
                    error_log("Order image uploaded successfully: {$imageUrl}");
                } else {
                    error_log("Failed to move uploaded order image to: {$destination}");
                }
            } else if (isset($_FILES['image'])) {
                error_log("Order upload error code: " . $_FILES['image']['error']);
            }
            
            $orderId = uniqid();
            $userId = getCurrentUserId();
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $quantity = floatval(isset($_POST['quantity']) ? $_POST['quantity'] : 0);
            $unit = isset($_POST['unit']) ? $_POST['unit'] : 'g';
            $price = floatval(isset($_POST['price_per_1000']) ? $_POST['price_per_1000'] : 0);
            $createdAt = date('Y-m-d H:i:s');
            
            smx::justQuery(
                "INSERT INTO orders (id, user_id, name, quantity, unit, price_per_1000, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$orderId, $userId, $name, $quantity, $unit, $price, $imageUrl, $createdAt]
            );
            
            logActivity('order_created', "Dodano zamówienie: {$name} ({$quantity} {$unit})", [
                'order_id' => $orderId
            ]);
            
            echo json_encode(['success' => true, 'order' => [
                'id' => $orderId,
                'name' => $name,
                'quantity' => $quantity,
                'unit' => $unit,
                'price_per_1000' => $price,
                'image' => $imageUrl,
                'created_at' => $createdAt
            ]]);
            break;
            
        case 'move_order_to_stock':
            requireLogin();
            
            $orderId = isset($_POST['order_id']) ? $_POST['order_id'] : '';
            $userId = getCurrentUserId();
            
            $orders = smx::justQuery("SELECT * FROM orders WHERE id = ?", [$orderId]);
            
            if (empty($orders)) {
                echo json_encode(['success' => false, 'error' => 'Zamówienie nie znalezione']);
                exit;
            }
            
            $order = $orders[0];
            
            $products = smx::justQuery(
                "SELECT * FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))",
                [$order['name']]
            );
            
            if (!empty($products)) {
                $product = $products[0];
                smx::justQuery(
                    "UPDATE products SET weight = weight + ?, price_per_1000 = ? WHERE id = ?",
                    [$order['quantity'], $order['price_per_1000'] > 0 ? $order['price_per_1000'] : $product['price_per_1000'], $product['id']]
                );
                
                smx::justQuery(
                    "INSERT INTO product_history (id, user_id, product_id, action, amount, unit, timestamp, note) VALUES (?, ?, ?, 'added', ?, ?, ?, 'Zamówienie dostarczone')",
                    [uniqid(), $userId, $product['id'], $order['quantity'], $order['unit'], date('Y-m-d H:i:s')]
                );
            } else {
                $newProductId = uniqid();
                smx::justQuery(
                    "INSERT INTO products (id, user_id, name, weight, unit, price_per_1000, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$newProductId, $userId, $order['name'], $order['quantity'], $order['unit'], $order['price_per_1000'], $order['image'], date('Y-m-d H:i:s')]
                );
                
                smx::justQuery(
                    "INSERT INTO product_history (id, user_id, product_id, action, amount, unit, timestamp) VALUES (?, ?, ?, 'created', ?, ?, ?)",
                    [uniqid(), $userId, $newProductId, $order['quantity'], $order['unit'], date('Y-m-d H:i:s')]
                );
            }
            
            smx::justQuery("DELETE FROM orders WHERE id = ?", [$orderId]);
            
            logActivity('order_moved', "Przeniesiono zamówienie na stan: {$order['name']}", [
                'order_id' => $orderId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Zamówienie przeniesione do magazynu'
            ]);
            break;
            
        case 'delete_order':
            requireLogin();
            
            $orderId = isset($_POST['order_id']) ? $_POST['order_id'] : '';
            $userId = getCurrentUserId();
            
            $orders = smx::justQuery("SELECT * FROM orders WHERE id = ?", [$orderId]);
            
            if (!empty($orders)) {
                $order = $orders[0];
                
                if ($order['user_id'] !== $userId) {
                    echo json_encode(['success' => false, 'error' => 'Brak uprawnień do usunięcia tego zamówienia']);
                    exit;
                }
            }
            
            smx::justQuery("DELETE FROM orders WHERE id = ?", [$orderId]);
            
            logActivity('order_deleted', "Usunięto zamówienie: {$order['name']}", [
                'order_id' => $orderId,
                'quantity' => $order['quantity']
            ]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'block_user':
            requireAdmin();
            
            $targetUserId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            
            if (empty($targetUserId)) {
                echo json_encode(['success' => false, 'error' => 'Brak ID użytkownika']);
                exit;
            }
            
            smx::justQuery("UPDATE users SET is_blocked = 1 WHERE id = ?", array($targetUserId));
            logActivity('user_blocked', 'Zablokowano użytkownika ID: ' . $targetUserId);
            echo json_encode(['success' => true, 'message' => 'Użytkownik zablokowany']);
            break;
            
        case 'unblock_user':
            requireAdmin();
            
            $targetUserId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            
            if (empty($targetUserId)) {
                echo json_encode(['success' => false, 'error' => 'Brak ID użytkownika']);
                exit;
            }
            
            smx::justQuery("UPDATE users SET is_blocked = 0 WHERE id = ?", array($targetUserId));
            logActivity('user_unblocked', 'Odblokowano użytkownika ID: ' . $targetUserId);
            echo json_encode(['success' => true, 'message' => 'Użytkownik odblokowany']);
            break;
            
        case 'delete_user':
            requireAdmin();
            
            $targetUserId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            
            if (empty($targetUserId)) {
                echo json_encode(['success' => false, 'error' => 'Brak ID użytkownika']);
                exit;
            }
            
            // Sprawdź czy to nie ty sam
            if ($targetUserId === $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Nie możesz usunąć samego siebie']);
                exit;
            }
            
            // Pobierz dane użytkownika przed usunięciem
            $userData = smx::justQuery("SELECT username FROM users WHERE id = ?", [$targetUserId]);
            $username = !empty($userData) ? $userData[0]['username'] : 'Unknown';
            
            // Usuń dane użytkownika z różnych tabel
            smx::justQuery("DELETE FROM products WHERE user_id = ?", [$targetUserId]);
            smx::justQuery("DELETE FROM recipes WHERE user_id = ?", [$targetUserId]);
            smx::justQuery("DELETE FROM orders WHERE user_id = ?", [$targetUserId]);
            smx::justQuery("DELETE FROM executed_recipes WHERE user_id = ?", [$targetUserId]);
            smx::justQuery("DELETE FROM finished_balls WHERE user_id = ?", [$targetUserId]);
            smx::justQuery("DELETE FROM activity_log WHERE user_id = ?", [$targetUserId]);
            
            // Usuń samego użytkownika
            smx::justQuery("DELETE FROM users WHERE id = ?", [$targetUserId]);
            
            logActivity('user_deleted', 'Usunięto użytkownika: ' . $username . ' (ID: ' . $targetUserId . ')');
            echo json_encode(['success' => true, 'message' => 'Użytkownik został trwale usunięty']);
            break;
            
        case 'add_user_by_admin':
            error_log("add_user_by_admin called");
            requireAdmin();
            error_log("requireAdmin passed");
            
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $isAdmin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;
            
            error_log("Username: $username, Email: $email, IsAdmin: $isAdmin");
            
            // Walidacja
            if (empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Wszystkie pola są wymagane']);
                exit;
            }
            
            if (strlen($username) < 3) {
                echo json_encode(['success' => false, 'error' => 'Nazwa użytkownika musi mieć minimum 3 znaki']);
                exit;
            }
            
            if (strpos($username, ' ') !== false) {
                echo json_encode(['success' => false, 'error' => 'Nazwa użytkownika nie może zawierać spacji']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'error' => 'Hasło musi mieć minimum 6 znaków']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy adres email']);
                exit;
            }
            
            // Sprawdź czy użytkownik już istnieje
            $existingUser = smx::justQuery("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if (!empty($existingUser)) {
                echo json_encode(['success' => false, 'error' => 'Użytkownik o tej nazwie lub emailu już istnieje']);
                exit;
            }
            
            // Dodaj użytkownika
            $userId = uniqid();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            smx::justQuery(
                "INSERT INTO users (id, username, email, password_hash, is_admin, is_blocked, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)",
                [$userId, $username, $email, $hashedPassword, $isAdmin, date('Y-m-d H:i:s')]
            );
            
            logActivity('user_added_by_admin', 'Dodano nowego użytkownika: ' . $username . ' (admin: ' . ($isAdmin ? 'tak' : 'nie') . ')');
            echo json_encode(['success' => true, 'message' => 'Użytkownik został dodany pomyślnie']);
            break;
            
        case 'switch_user':
            requireAdmin();
            
            $targetUserId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            
            if (empty($targetUserId)) {
                echo json_encode(['success' => false, 'error' => 'Brak ID użytkownika']);
                exit;
            }
            
            // Sprawdź czy użytkownik istnieje
            $targetUser = smx::justQuery("SELECT id, username FROM users WHERE id = ?", [$targetUserId]);
            
            if (empty($targetUser)) {
                echo json_encode(['success' => false, 'error' => 'Użytkownik nie istnieje']);
                exit;
            }
            
            $_SESSION['admin_switched_user_id'] = $targetUserId;
            $_SESSION['admin_original_user_id'] = $_SESSION['user_id'];
            
            logActivity('admin_switch_user', "Admin przełączył się na użytkownika: {$targetUser[0]['username']}", [
                'target_user_id' => $targetUserId,
                'target_username' => $targetUser[0]['username']
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => "Przełączono na użytkownika: {$targetUser[0]['username']}",
                'switched_user' => $targetUser[0]
            ]);
            break;
            
        case 'switch_back_admin':
            requireAdmin();
            
            if (!isset($_SESSION['admin_original_user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Nie jesteś przełączony na innego użytkownika']);
                exit;
            }
            
            unset($_SESSION['admin_switched_user_id']);
            unset($_SESSION['admin_original_user_id']);
            
            logActivity('admin_switch_back', "Admin powrócił do własnego konta");
            
            echo json_encode(['success' => true, 'message' => 'Powrócono do konta administratora']);
            break;
            
        case 'get_pending_recipes':
            requireAdmin();
            
            $pendingRecipes = smx::justQuery("SELECT r.*, u.username as author_username FROM recipes r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at ASC");
            
            echo json_encode(['success' => true, 'recipes' => $pendingRecipes]);
            break;
            
        case 'moderate_recipe':
            requireAdmin();
            
            $recipeId = isset($_POST['recipe_id']) ? $_POST['recipe_id'] : '';
            $action = isset($_POST['moderate_action']) ? $_POST['moderate_action'] : ''; // 'approve' or 'reject'
            
            if (empty($recipeId) || !in_array($action, ['approve', 'reject'])) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowe parametry']);
                exit;
            }
            
            $status = $action === 'approve' ? 'approved' : 'rejected';
            
            smx::justQuery("UPDATE recipes SET status = ? WHERE id = ?", [$status, $recipeId]);
            
            $recipe = smx::justQuery("SELECT name FROM recipes WHERE id = ?", [$recipeId]);
            $recipeName = !empty($recipe) ? $recipe[0]['name'] : 'Przepis';
            
            logActivity('recipe_moderated', "Przepis '{$recipeName}' został " . ($action === 'approve' ? 'zaakceptowany' : 'odrzucony'), [
                'recipe_id' => $recipeId,
                'action' => $action
            ]);
            
            echo json_encode(['success' => true, 'message' => "Przepis został " . ($action === 'approve' ? 'zaakceptowany' : 'odrzucony')]);
            break;
            
        case 'save_user_settings':
            requireLogin();
            
            $userId = getCurrentUserId();
            $maxWeightG = isset($_POST['max_product_weight_g']) ? intval($_POST['max_product_weight_g']) : 5000;
            $maxWeightMl = isset($_POST['max_product_weight_ml']) ? intval($_POST['max_product_weight_ml']) : 2000;
            $maxWeightSzt = isset($_POST['max_product_weight_szt']) ? intval($_POST['max_product_weight_szt']) : 100;
            $recipeMultiplier = isset($_POST['recipe_multiplier']) ? floatval($_POST['recipe_multiplier']) : 1.7;
            
            // Walidacja
            if ($maxWeightG < 1000 || $maxWeightG > 50000) {
                echo json_encode(['success' => false, 'error' => 'Waga (g) musi być między 1000 a 50000']);
                exit;
            }
            
            if ($maxWeightMl < 100 || $maxWeightMl > 50000) {
                echo json_encode(['success' => false, 'error' => 'Waga (ml) musi być między 100 a 50000']);
                exit;
            }
            
            if ($maxWeightSzt < 10 || $maxWeightSzt > 10000) {
                echo json_encode(['success' => false, 'error' => 'Ilość (szt) musi być między 10 a 10000']);
                exit;
            }
            
            if ($recipeMultiplier < 1 || $recipeMultiplier > 5) {
                echo json_encode(['success' => false, 'error' => 'Mnożnik musi być między 1 a 5']);
                exit;
            }
            
            $settings = array(
                'max_product_weight_g' => $maxWeightG,
                'max_product_weight_ml' => $maxWeightMl,
                'max_product_weight_szt' => $maxWeightSzt,
                'recipe_multiplier' => $recipeMultiplier
            );
            
            smx::justQuery("UPDATE users SET settings = ? WHERE id = ?", array(json_encode($settings), $userId));
            logActivity('settings_updated', 'Zaktualizowano ustawienia użytkownika');
            echo json_encode(['success' => true, 'message' => 'Ustawienia zapisane']);
            break;
            
        // === GOTOWE KULKI ===
        case 'get_finished_balls':
            requireLogin();
            $userId = getCurrentUserId();
            $balls = smx::justQuery(
                "SELECT fb.*, 
                        r.image as recipe_image,
                        (SELECT COUNT(*) FROM ball_history WHERE ball_id = fb.id) as history_count
                 FROM finished_balls fb 
                 LEFT JOIN recipes r ON fb.recipe_id = r.id
                 WHERE fb.user_id = ?
                 ORDER BY fb.updated_at DESC",
                array($userId)
            );
            echo json_encode(['success' => true, 'balls' => $balls ?: []]);
            break;
            
        case 'add_finished_ball':
            requireLogin();
            $userId = getCurrentUserId();
            $recipeId = isset($_POST['recipe_id']) ? $_POST['recipe_id'] : '';
            $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
            
            if (empty($recipeId)) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy przepis']);
                exit;
            }
            
            if ($quantity <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ilość musi być większa od 0']);
                exit;
            }
            
            // Pobierz nazwę i obrazek przepisu
            $recipe = smx::justQuery("SELECT name, image FROM recipes WHERE id = ?", array($recipeId));
            if (empty($recipe)) {
                echo json_encode(['success' => false, 'error' => 'Przepis nie istnieje']);
                exit;
            }
            
            $recipeName = $recipe[0]['name'];
            $recipeImage = $recipe[0]['image'];
            
            // Sprawdź czy kulki z tego przepisu już istnieją dla tego użytkownika
            $existing = smx::justQuery("SELECT id, quantity FROM finished_balls WHERE recipe_id = ? AND user_id = ?", array($recipeId, $userId));
            
            if (!empty($existing)) {
                // Zaktualizuj ilość
                $ballId = $existing[0]['id'];
                $oldQuantity = floatval($existing[0]['quantity']);
                $newQuantity = $oldQuantity + $quantity;
                
                smx::justQuery(
                    "UPDATE finished_balls SET quantity = ?, updated_at = ? WHERE id = ?",
                    array($newQuantity, date('Y-m-d H:i:s'), $ballId)
                );
                
                // Dodaj do historii
                smx::justQuery(
                    "INSERT INTO ball_history (id, ball_id, change_type, quantity_change, quantity_after, description, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    array(uniqid(), $ballId, 'add', $quantity, $newQuantity, 'Dodano kulki z produkcji', date('Y-m-d H:i:s'))
                );
                
                logActivity('ball_added', "Dodano {$quantity}kg kulek: {$recipeName}", ['ball_id' => $ballId, 'quantity' => $quantity]);
                echo json_encode(['success' => true, 'message' => 'Zaktualizowano ilość kulek', 'ball_id' => $ballId]);
            } else {
                // Utwórz nowy wpis
                $ballId = uniqid();
                smx::justQuery(
                    "INSERT INTO finished_balls (id, user_id, recipe_id, recipe_name, quantity, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    array($ballId, $userId, $recipeId, $recipeName, $quantity, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'))
                );
                
                // Dodaj do historii
                smx::justQuery(
                    "INSERT INTO ball_history (id, ball_id, change_type, quantity_change, quantity_after, description, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    array(uniqid(), $ballId, 'create', $quantity, $quantity, 'Utworzono nowy typ kulek', date('Y-m-d H:i:s'))
                );
                
                logActivity('ball_created', "Utworzono nowe kulki: {$recipeName}", ['ball_id' => $ballId, 'quantity' => $quantity]);
                echo json_encode(['success' => true, 'message' => 'Dodano nowe kulki', 'ball_id' => $ballId]);
            }
            break;
            
        case 'update_ball_quantity':
            requireLogin();
            $ballId = isset($_POST['ball_id']) ? $_POST['ball_id'] : '';
            $changeType = isset($_POST['change_type']) ? $_POST['change_type'] : ''; // 'add' lub 'remove'
            $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            
            if (empty($ballId)) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowe ID kulek']);
                exit;
            }
            
            if (!in_array($changeType, ['add', 'remove'])) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy typ zmiany']);
                exit;
            }
            
            if ($quantity <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ilość musi być większa od 0']);
                exit;
            }
            
            // Pobierz aktualne kulki
            $ball = smx::justQuery("SELECT * FROM finished_balls WHERE id = ?", array($ballId));
            if (empty($ball)) {
                echo json_encode(['success' => false, 'error' => 'Kulki nie istnieją']);
                exit;
            }
            
            $currentQuantity = floatval($ball[0]['quantity']);
            $recipeName = $ball[0]['recipe_name'];
            
            if ($changeType === 'add') {
                $newQuantity = $currentQuantity + $quantity;
            } else {
                $newQuantity = $currentQuantity - $quantity;
                if ($newQuantity < 0) {
                    echo json_encode(['success' => false, 'error' => 'Nie można odjąć więcej niż posiadasz']);
                    exit;
                }
            }
            
            // Zaktualizuj ilość
            smx::justQuery(
                "UPDATE finished_balls SET quantity = ?, updated_at = ? WHERE id = ?",
                array($newQuantity, date('Y-m-d H:i:s'), $ballId)
            );
            
            // Dodaj do historii
            smx::justQuery(
                "INSERT INTO ball_history (id, ball_id, change_type, quantity_change, quantity_after, description, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                array(uniqid(), $ballId, $changeType, $quantity, $newQuantity, $description ?: ($changeType === 'add' ? 'Dodano kulki' : 'Odjęto kulki'), date('Y-m-d H:i:s'))
            );
            
            $action = $changeType === 'add' ? 'Dodano' : 'Odjęto';
            logActivity('ball_updated', "{$action} {$quantity}kg kulek: {$recipeName}", ['ball_id' => $ballId, 'quantity' => $quantity]);
            
            echo json_encode(['success' => true, 'message' => 'Zaktualizowano ilość kulek', 'new_quantity' => $newQuantity]);
            break;
            
        case 'delete_ball':
            requireLogin();
            $ballId = isset($_POST['ball_id']) ? $_POST['ball_id'] : '';
            
            if (empty($ballId)) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowe ID kulek']);
                exit;
            }
            
            // Pobierz informacje o kulkach
            $ball = smx::justQuery("SELECT * FROM finished_balls WHERE id = ?", array($ballId));
            if (empty($ball)) {
                echo json_encode(['success' => false, 'error' => 'Kulki nie istnieją']);
                exit;
            }
            
            $recipeName = $ball[0]['recipe_name'];
            
            // Usuń kulki (historia zostanie usunięta przez CASCADE)
            smx::justQuery("DELETE FROM finished_balls WHERE id = ?", array($ballId));
            
            logActivity('ball_deleted', "Usunięto kulki: {$recipeName}", ['ball_id' => $ballId]);
            echo json_encode(['success' => true, 'message' => 'Kulki zostały usunięte']);
            break;
            
        case 'get_ball_history':
            requireLogin();
            $ballId = isset($_POST['ball_id']) ? $_POST['ball_id'] : '';
            
            if (empty($ballId)) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowe ID kulek']);
                exit;
            }
            
            $history = smx::justQuery(
                "SELECT * FROM ball_history WHERE ball_id = ? ORDER BY created_at DESC",
                array($ballId)
            );
            
            echo json_encode(['success' => true, 'history' => $history ?: []]);
            break;
            
        default:
            echo json_encode(['error' => 'Nieznana akcja']);
    }
}
?>
