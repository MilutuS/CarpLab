<?php
/**
 * SMX - Simple MySQL eXecutor
 * Klasa do obsługi połączeń z bazą danych MySQL
 */

class smx {
    private static $connection = null;
    private static $config = null;
    
    /**
     * Wczytaj konfigurację z pliku lub użyj domyślnej
     */
    private static function getConfig() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        // Spróbuj wczytać z db_config.php
        $configFile = __DIR__ . '/db_config.php';
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
            // Domyślna konfiguracja
            self::$config = [
                'host' => 'localhost',
                'username' => 'root',
                'password' => '_AGMQ8@YyOLa',
                'database' => 'partacz_fishing',
                'port' => 3306,
                'charset' => 'utf8mb4'
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Inicjalizacja połączenia z bazą danych
     */
    private static function connect() {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        $config = self::getConfig();
        
        try {
            $port = isset($config['port']) ? $config['port'] : 3306;
            
            self::$connection = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $port
            );
            
            if (self::$connection->connect_error) {
                throw new Exception('Błąd połączenia: ' . self::$connection->connect_error);
            }
            
            // Ustaw charset
            self::$connection->set_charset($config['charset']);
            
            return self::$connection;
        } catch (Exception $e) {
            error_log('SMX Connection Error: ' . $e->getMessage());
            die('Błąd połączenia z bazą danych');
        }
    }
    
    /**
     * Główna metoda do wykonywania zapytań SQL
     * 
     * @param string $query Zapytanie SQL
     * @param array $params Parametry do podstawienia (opcjonalne)
     * @return mysqli_result|bool|array Wynik zapytania
     */
    public static function justQuery($query, $params = []) {
        $conn = self::connect();
        
        // Jeśli są parametry, użyj prepared statements
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                error_log('SMX Prepare Error: ' . $conn->error . ' | Query: ' . $query);
                return false;
            }
            
            // Automatyczne określenie typów parametrów
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            
            // Bind parametrów
            if (!empty($types)) {
                $stmt->bind_param($types, ...$bindParams);
            }
            
            $stmt->execute();
            
            // Dla SELECT zwróć wyniki jako tablicę asocjacyjną
            if (stripos(trim($query), 'SELECT') === 0) {
                $result = $stmt->get_result();
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $stmt->close();
                return $rows;
            }
            
            // Dla INSERT/UPDATE/DELETE zwróć liczbę zmienionych wierszy
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
            
        } else {
            // Zwykłe zapytanie bez parametrów
            $result = $conn->query($query);
            
            if (!$result) {
                error_log('SMX Query Error: ' . $conn->error . ' | Query: ' . $query);
                return false;
            }
            
            // Dla SELECT zwróć wyniki jako tablicę
            if ($result instanceof mysqli_result) {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                return $rows;
            }
            
            // Dla INSERT/UPDATE/DELETE zwróć true
            return true;
        }
    }
    
    /**
     * Pobierz ostatnie wstawione ID
     */
    public static function lastInsertId() {
        $conn = self::connect();
        return $conn->insert_id;
    }
    
    /**
     * Escape string dla bezpieczeństwa
     */
    public static function escape($string) {
        $conn = self::connect();
        return $conn->real_escape_string($string);
    }
    
    /**
     * Zamknij połączenie
     */
    public static function close() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
    
    /**
     * Konfiguracja połączenia (opcjonalne)
     */
    public static function configure($config) {
        self::$config = array_merge(self::getConfig(), $config);
    }
}
