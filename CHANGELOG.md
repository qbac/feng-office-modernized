# Changelog

Wszystkie znaczące zmiany w projekcie (migracja PHP 5.5 → 8.4, MySQL, dalej API/MCP) są
odnotowywane w tym pliku. Format luźno wzorowany na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/).

Najnowsze zmiany na górze.

## [Nieopublikowane]

## 2026-09-03

### PHP 8.4: naprawiony "puste body" — działa end-to-end

Kontynuacja otwartego problemu z 2026-09-02. Zdiagnozowane i naprawione ~10 kaskadowych błędów
fatalnych (każdy kolejny ujawniał się dopiero po naprawieniu poprzedniego, głębiej w łańcuchu
bootowania `init.php` → `application.php` → routing → render). Zweryfikowane przez prawdziwe
żądanie HTTP do Apache (nie tylko CLI): `http://fo.local/index.php?c=access&a=login` → 200 OK,
pełny HTML po polsku; `http://fo.local/index.php` → poprawny redirect 302.

### Metoda diagnozy

- Symulacja żądania CGI bezpośrednio przez `php-cgi.exe` (PowerShell, ze zmiennymi środowiskowymi
  `REQUEST_METHOD`/`SCRIPT_FILENAME`/itd.) — pozwala widzieć `exit code` i pełny output bez
  pośrednictwa Apache.
- Binary-search przez tymczasowe `error_log()` checkpointy wstawiane w `init.php`,
  `application.php` i dalej w głąb stosu wywołań — usuwane po zlokalizowaniu każdego problemu.
- Izolowane skrypty testowe (`/tmp/testcontact.php` i podobne) ładujące pojedyncze klasy przez
  realny `feng__autoload()` poza pełnym bootem aplikacji — szybsze iteracje niż pełny request.
- Kluczowa obserwacja: **fatalne błędy kompilacji PHP** (niezgodne sygnatury nadpisanych metod,
  redeklaracja stałych) **nie trafiają do żadnego loga ani handlera** — proces kończy się cicho
  (`exit 255`, zero bajtów, zero linii w `php_errors.log` i `cache/log.php`). Odróżnialne od
  uncaught `\Error`/`\Exception` (które global `set_exception_handler` loguje do `cache/log.php`,
  ale i tak nic nie wypisuje na wyjście, bo handler tylko robi `Logger::log()`).

### Naprawione — root cause: "non-static method called statically" to w PHP 8.0+ FATAL, nie deprecation

W PHP 7.x wołanie niestatycznej metody w sposób statyczny (`ClassName::metoda()` bez `$this` w
zasięgu) było tylko deprecated i nadal działało przez kompatybilnościowy fallback. **Od PHP 8.0
to fatalny, niekatchowalny przez `catch(Exception $e)` `\Error`** (łapie go tylko
`catch(Throwable)` albo `catch(Error)`) — a cały kod bazowy łapał wyłącznie `Exception`. Stąd
błąd cicho zabijał request na globalnym exception handlerze, który tylko loguje i nic nie
wypisuje (`__production_exception_handler` w `application/functions.php`).

Wzorzec `isset($this) ? $this->x() : ClassName::instance()->x()` (klasyczny "static or instance"
idiom z PHP 4/5) jest wszechobecny w wygenerowanych klasach `Base*` (find/findOne/findAll/
findById/count/delete/paginate/instance/getColumns) — problem pojawia się tylko gdy metoda jest
faktycznie WOŁANA statycznie z kontekstu bez `$this` (metoda statyczna, kod proceduralny), co
zdarza się w dziesiątkach miejsc w kodzie aplikacyjnym.

- `environment/library/database/DB.class.php` — `connectAdapter()`, `useAdapter()`,
  `getAdapterClass()` (prywatne, wołane `self::` z wnętrza statycznej `connect()`) → dodano
  `static` (żadna nie używa `$this`).
- `environment/library/database/DB.class.php` (`close()`) — dodatkowo zabezpieczone przed
  wywołaniem na `null` (brak połączenia) `instanceof AbstractDBAdapter` przed `->close()`.
- **104 metody `instance()`** (Singleton) w `application/models/**/base/Base*.class.php` i kilku
  helperach (`breadcrumbs.php`, `pageactions.php`, `tabbednavigation.php`) → dodano `static`.
  Zweryfikowane skryptowo, że żadna nie odwołuje się do `$this`.
- **101 metod `getColumns()`** w tych samych klasach → dodano `static`; plus
  `environment/classes/dataaccess/DataManager.class.php`: `abstract function getColumns()` →
  `abstract static function getColumns()` (deklaracja bazowa musi być zgodna z nadpisaniami,
  inaczej fatal "Cannot make non static method static").
- **Mechaniczna, skryptowa poprawka ok. 1070 wywołań** w całym `application/`, `plugins/`,
  `environment/`: `ClassName::(find|findOne|findAll|findById|count|delete|paginate)(` oraz
  `self::(...)(` → `ClassName::instance()->(...)` / `self::instance()->(...)`. Bezpieczne 1:1,
  bo to dokładnie to, co i tak robi gałąź "else" tych metod — więc zachowanie identyczne
  niezależnie od tego, czy wcześniej trafiało w gałąź `isset($this)` czy w `else`.
- Punktowe poprawki (te same wywołania, ale przez `self::`/literalną nazwę klasy poza zasięgiem
  skryptu, znalezione dopiero przy odpalaniu kolejnych requestów):
  `application/application.php` (`Hook::init()` przez `Plugins::instance()`, oraz
  `CompanyWebsite::init()` → `CompanyWebsite::instance()->init()`),
  `application/models/object_types/ObjectTypes.class.php` (`findByName()`),
  `application/models/contact_config_options/ContactConfigOptions.class.php` (`getByName()`).

### Naprawione — inne fatalne błędy kompilacji (niezgodne sygnatury / redeklaracje)

- **96 metod `Base*::paginate()`** (w tym `BasePlugins`) — brakujący parametr `$count = null`
  względem `DataManager::paginate($arguments, $items_per_page, $current_page, $count)` (fatal
  "Declaration ... must be compatible with"). Poprawiono skryptowo (sygnatura + przekazanie
  parametru w wywołaniach `parent::paginate(...)` / `ClassName::instance()->paginate(...)`).
- `environment/classes/dataaccess/DataObject.class.php` — `validate($errors)` → `validate(&$errors)`.
  Baza nie miała referencji, a WSZYSTKIE 24 nadpisania w kodzie aplikacyjnym (`Contact::validate`,
  `ProjectTask::validate` itd.) mają `&$errors` — niezgodność blokowała autoload całej hierarchii
  `Contact`. `DataObject::doValidate()` i tak polega na mutacji przez referencję, więc baza była
  tą "złą" stroną niezgodności.
- `application/models/object_types/base/BaseObjectType.class.php` — `getTableName()` →
  `getTableName($escape = false)`. To getter kolumny `table_name` (nazwa tabeli MySQL powiązanej
  z typem obiektu), przypadkowo koliduje nazwą z `DataObject::getTableName($escape)` (zwraca
  nazwę tabeli SQL bieżącego obiektu) — inna funkcja, ta sama nazwa, niezgodna sygnatura blokowała
  autoload `ObjectType`.
- `library/utf8/utf8.php` — `MB_OVERLOAD_STRING` (stała usunięta w PHP 8.0 razem z
  `mbstring.func_overload`, string overloading nie istnieje od PHP 8) → sprawdzenie owinięte w
  `defined('MB_OVERLOAD_STRING')`.

### Naprawione — obsługa błędów w rdzeniu (systemowe, nie punktowe)

- `init.php` — dwa główne bloki `try/catch` (wokół `DB::connect()` i wokół
  `Env::executeAction()`) łapały tylko `catch(Exception $e)`. Zmienione na `catch(Throwable $e)`
  — inaczej każdy `\Error` (w tym własne klasy błędów aplikacji typu `DBConnectError extends
  Error`, gdzie `Error` to WBUDOWANA klasa PHP 7+, nie własna) omijał obsługę błędów i kończył
  request cicho przez globalny exception handler.

### Znane problemy (nieblokujące, do ogarnięcia kiedyś)

- `http://fo.local/` (bez `index.php`) serwuje pusty (0 bajtów) statyczny `public/index.html` z
  2016 zamiast przechodzić przez PHP — Apache's DirectoryIndex wybiera go przed `index.php`.
  Aplikacja generuje własne URL-e zawsze z `index.php`, więc to kosmetyczne, ale mylące przy
  ręcznym wejściu na `/`.
- Wzorzec `isset($this) ? ... : ClassName::instance()->...` nadal istnieje w kodzie (poprawiono
  tylko punkty wywołania, nie usunięto wzorca) — nowy kod wołający te metody statycznie musi
  pisać `ClassName::instance()->metoda()` od razu, inaczej znów będzie fatal.
- Nieco ponad 100 pozostałych "Optional parameter declared before required parameter" deprecation
  (widoczne w `php_errors.log`) — nieblokujące (PHP 8 traktuje je jako required, ale wywołania w
  kodzie i tak zawsze podają wszystkie argumenty w tych miejscach), do posprzątania przy okazji.
- Reszta pozycji z listy w `CLAUDE.md` ("Do zrobienia — reszta z pierwotnego przeglądu") — nadal
  niezweryfikowana: `ereg`/`eregi`/`split`/`create_function`, `each()`, konstruktory PHP4-style,
  referencje z domyślnymi wartościami w sygnaturach.

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
