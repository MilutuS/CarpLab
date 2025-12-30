# CarpLab - Struktura Projektu

## 📁 Nowa Struktura Plików

Projekt został zreorganizowany dla lepszej czytelności i łatwiejszego zarządzania kodem.

### Główne pliki:
- **index.php** - Główny plik aplikacji (framework z nawigacją i includami)
- **config.php** - Backend API (obsługa wszystkich żądań AJAX)
- **script.js** - JavaScript (logika aplikacji)
- **style.css** - Stylizacja CSS
- **smx.php** - Klasa bazy danych
- **DotEnv.php** - Parser plików .env

### 📂 Katalog `/sites/`

Wszystkie zakładki (strony) aplikacji znajdują się w osobnych plikach:

#### Główne zakładki:
- **home.php** - Strona główna z powitaniem
- **products.php** - Baza produktów (składniki)
- **recipes.php** - Receptury na kulki
- **stock.php** - Stan magazynowy
- **orders.php** - Lista zakupów
- **executed.php** - Historia wykonanych przepisów
- **finished-balls.php** - Gotowe kulki

#### Zakładki użytkownika:
- **settings.php** - Ustawienia profilu

#### Zakładki admina:
- **history.php** - Logi systemowe
- **users.php** - Zarządzanie użytkownikami
- **pending-recipes.php** - Oczekujące przepisy
- **backup.php** - Kopie zapasowe bazy danych

#### Komponenty:
- **modals.php** - Wszystkie modale (okna dialogowe)

## 🔧 Jak to działa?

Główny plik **index.php** zawiera:
1. Nagłówek HTML z linkami do CSS/JS
2. Nawigację (sidebar)
3. Include'y do wszystkich stron z katalogu `/sites/`
4. Skrypty JavaScript

Każda zakładka to oddzielny plik PHP zawierający tylko HTML danej sekcji.

## ✅ Zalety nowej struktury:

- ✨ **Czytelność** - każda zakładka w osobnym pliku
- 🔧 **Łatwość modyfikacji** - edycja jednej strony nie wymaga przeszukiwania 1000+ linii kodu
- 🚀 **Szybszy rozwój** - łatwe dodawanie nowych zakładek
- 👥 **Praca zespołowa** - różni programiści mogą pracować na różnych stronach
- 📦 **Modularność** - komponenty można łatwo przenosić między projektami

## 📝 Edycja zakładek:

Aby edytować konkretną stronę, otwórz odpowiedni plik w katalogu `/sites/`:

```
sites/
├── home.php          → Strona główna
├── products.php      → Baza produktów
├── recipes.php       → Przepisy
├── stock.php         → Magazyn
├── orders.php        → Zakupy
├── executed.php      → Historia
├── finished-balls.php → Gotowe kulki
├── settings.php      → Ustawienia
├── history.php       → Logi (admin)
├── users.php         → Użytkownicy (admin)
├── pending-recipes.php → Oczekujące (admin)
├── backup.php        → Backupy (admin)
└── modals.php        → Modale
```

## 🔄 Backup:

Stary plik index.php został zapisany jako **index_old.php** (kopia zapasowa).

## 🚀 Deployment:

Katalog `/sites/` jest automatycznie synchronizowany przez SFTP (skonfigurowane w `.vscode/sftp.json`).

---

**Data refaktoryzacji:** 30 grudnia 2025
