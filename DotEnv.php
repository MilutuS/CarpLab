<?php
/**
 * Prosta klasa do ładowania zmiennych środowiskowych z pliku .env
 */
class DotEnv {
    /**
     * Ścieżka do katalogu głównego
     */
    protected $path;

    /**
     * Konstruktor
     */
    public function __construct($path) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('Plik .env nie istnieje: %s', $path));
        }
        $this->path = $path;
    }

    /**
     * Załaduj zmienne środowiskowe z pliku .env
     */
    public function load() {
        if (!is_readable($this->path)) {
            throw new RuntimeException(sprintf('Plik .env nie może być odczytany: %s', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Pomijaj komentarze
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsuj linię
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Usuń cudzysłowy jeśli istnieją
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                // Ustaw zmienną środowiskową
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv(sprintf('%s=%s', $name, $value));
                }
            }
        }
    }
}

/**
 * Funkcja pomocnicza do pobierania zmiennych środowiskowych
 * 
 * @param string $key Klucz zmiennej środowiskowej
 * @param mixed $default Wartość domyślna jeśli zmienna nie istnieje
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }
    
    // Konwersja wartości tekstowych na odpowiednie typy
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }
    
    return $value;
}
