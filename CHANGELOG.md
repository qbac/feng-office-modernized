# Changelog

Wszystkie znaczące zmiany w projekcie (migracja PHP 5.5 → 8.4, MySQL, dalej API/MCP) są
odnotowywane w tym pliku. Format luźno wzorowany na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/).

Najnowsze zmiany na górze.

## [Nieopublikowane]

## 2026-09-02

### Dodane
- `CLAUDE.md` — kontekst projektu, status migracji PHP 8.4, lista napraw i otwartych problemów
- `README.md` — instrukcja setupu lokalnego (Laragon)
- `.gitignore` — wyklucza `upload/`, dumpy `*.sql`, `cache/*` (poza wyjątkami), `config/config.php`
- Repozytorium git zainicjowane, pierwszy commit (5756 plików)

### Zmienione
- Baza `fo23` odtworzona lokalnie (Laragon, MySQL 8.4.3) z `kopia_fo23_2026-09-02.sql`
  (produkcyjny dump MySQL 5.5) — 105 tabel, 294k obiektów, 97 workspace'ów
- `sql_mode` na lokalnym MySQL złagodzony trwale (`SET PERSIST sql_mode='';`) — dump zawiera
  daty `0000-00-00 00:00:00`, niekompatybilne z domyślnym `STRICT_TRANS_TABLES,NO_ZERO_DATE`
  w MySQL 8
- `config/config.php`: `DB_ADAPTER` zmieniony z `'mysql'` na `'pdo_mysql'` (istniejący w kodzie
  `PdoMysqlDBAdapter` używający czystego PDO zamiast usuniętego w PHP 7 `ext/mysql`);
  `DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME` pod lokalny MySQL; `ROOT_URL` → `http://fo.local`
- `cache/log.php` (był >1 GB) wyczyszczony lokalnie

### Naprawione (blockery uruchomienia na PHP 7+/8+)
- `application/functions.php` (`feng__autoload`) — usunięto bezpośrednie wywołania
  `mysql_connect/mysql_query/mysql_fetch_object/mysql_close` (ext/mysql, usunięte w PHP 7),
  używane do wykrywania katalogów pluginów przy zimnym cache klas; zastąpione PDO
- Skasowano stary `cache/autoloader.php` — zawierał twarde ścieżki produkcyjne (Linux,
  `/var/www/html/fo23/...`), przez co żadna klasa nie ładowała się na Windows; plik cache,
  odtwarza się automatycznie
- `plugins/workspaces/hooks/workspaces_hooks.php:27` — zduplikowana nazwa parametru
  (`function f($cols, &$cols)`), legalne w PHP 5.5, fatal compile error od PHP 7.0 —
  pierwszy (nieużywany) parametr przemianowany
- `application/models/objects/Objects.class.php:38` — zduplikowane `$start`/`$limit` w
  sygnaturze `getObjects()` (martwy kod, brak wywołań w repo) — usunięto duplikaty
- Klasa `Object` (`application/models/objects/Object.class.php`) przemianowana na
  **`FengObject`** — `Object` jest zarezerwowaną nazwą klasy od PHP 7.2; zaktualizowane
  2 miejsca użycia w `ContentDataObject.class.php` oraz string `'Object'` przekazywany do
  `DataManager::__construct()` w `BaseObjects.class.php`
- `continue;` poza pętlą (fatal compile error od PHP 7.0, w PHP 5 tolerowany jako no-op) —
  naprawione zachowując dotychczasowe zachowanie w `application/helpers/application.php:1482`
  oraz w 4 miejscach `application/models/notifier/Notifier.class.php`
  (`forgotPassword`, `passwordExpiration`, `newUserAccount`, `newUserAccountLinkPassword`)

### Naprawione (specyficzne dla PHP 8.4, ponad wymagania PHP 7.4)
- `environment/functions/general.php:495` — dostęp do znaku stringa przez `$val{...}`
  (usunięte w PHP 8.0) → `$val[...]`
- `environment/functions/general.php` (`fix_input_quotes`) — `get_magic_quotes_gpc()`
  (usunięta w PHP 8.0) zamieniona w no-op (magic quotes nie istnieją od PHP 5.4)
- `environment/functions/general.php` — własne polyfille `str_starts_with()` /
  `str_ends_with()` kolidowały z natywnymi funkcjami PHP 8.0+ (redeclare fatal) →
  owinięte w `function_exists()`
- `environment/functions/general.php` (`php_config_value_to_bytes`) — jawny `(float)` cast
  przed mnożeniem, żeby uniknąć `Warning: non-numeric value` w PHP 8

### Zweryfikowane
- **PHP 7.4 + MySQL 8.4: działa end-to-end** — strona logowania renderuje się poprawnie
  (200 OK, pełne HTML, po polsku)
- **PHP 8.4: prawie działa** — cały kod przechodzi `php -l` bez błędów składni w
  `application/`, `environment/`, `plugins/`; żądanie dochodzi do renderowania widoku logowania
  (potwierdzone w `cache/log.php`), ale finalna odpowiedź HTTP wraca pusta (200 OK, 0 bajtów)

### Znane problemy (otwarte, do kolejnej sesji)
- **PHP 8.4: puste body mimo poprawnego przejścia przez routing/render** — przyczyna
  nieustalona. Podejrzenie: masowe (dziesiątki w jednym requeście) wywoływanie niestatycznych
  metod statycznie (`Non-static method X::instance() should not be called statically`,
  deprecated od PHP 8.0) może się zachowywać inaczej na 8.4 niż na 7.4. Szczegóły i hipotezy w
  `CLAUDE.md` sekcja "Otwarty problem"
- `ereg`/`eregi`/`split`/`create_function`, `each()`, konstruktory PHP4-style,
  referencje z domyślnymi wartościami w sygnaturach — zidentyfikowane przez wcześniejszy
  przegląd, nie blokowały PHP 7.4, status pod PHP 8.4 niezweryfikowany (patrz `CLAUDE.md`)
