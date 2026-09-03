# Changelog

Wszystkie znaczące zmiany w projekcie (migracja PHP 5.5 → 8.4, MySQL, dalej API/MCP) są
odnotowywane w tym pliku. Format luźno wzorowany na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/).

Najnowsze zmiany na górze.

## [Nieopublikowane]

## 2026-09-03 (część 2): pełny dashboard z realnymi danymi po zalogowaniu

Kontynuacja tej samej sesji — po naprawieniu "pustego body" (patrz część 1 niżej) okazało się,
że po zalogowaniu główny dashboard nadal pokazywał komunikat "system Feng Office nie jest
obecnie w stanie wykonać Twojego żądania". Przyczyna identyczna jak w części 1 — kolejna
kaskada tego samego typu błędów (`Non-static method X cannot be called statically`, niezgodne
sygnatury nadpisanych metod), tym razem w kodzie renderującym layout aplikacji (`website.php`)
i panele dashboardu, docierająca aż do konkretnej wtyczki `workspaces` (stąd zgłoszony przez
użytkownika objaw: "obszary robocze są cały czas problemem że się nie pokazują").

Zweryfikowane wizualnie w przeglądarce (Chrome, sesja zalogowana): dashboard renderuje
kalendarz, listę zaległych/nadchodzących zadań, listę osób, nieprzeczytane wiadomości; drzewo
"Obszary robocze" pokazuje realne workspace'y produkcyjne (kilku rzeczywistych obszarów roboczych klienta -
nazwy pominięte); zakładka "Przegląd" listuje 69888 obiektów; zakładka "E-mail"
pokazuje realne wiadomości; zakładka "Kontakty" działa (pusta lista to poprawne zachowanie dla
bieżącego kontekstu). Konsola przeglądarki: 0 błędów aplikacji (tylko szum od rozszerzenia
Chrome, niezwiązany z Feng Office).

### Naprawione — kolejne przypadki "non-static method called statically"

- `application/layouts/website.php` (linia z `Hook::fire('on_page_load', 'mail', ...)`) →
  `plugins/mail/hooks/mail_hooks.php` (`mail_on_page_load`) wołał `MailContents::instance()->
  findAll()` — samo `findAll` OK, ale konstrukcja klasy `MailContent` (patrz niżej) crashowała
  wcześniej niezależnie.
- `environment/classes/localization/Localization.class.php` — `dateByLocalization()` (jedyna
  metoda w tej klasie faktycznie wołana statycznie z zewnątrz, `application/helpers/format.php`)
  → dodano `static`.
- `application/models/tab_panel_permissions/TabPanelPermissions.class.php` — 4 metody
  (`clearByPermissionGroup`, `isModuleEnabled`, `getRoleModules`, `getAllRolesModules`) → `static`.
- `application/models/permission_groups/PermissionGroups.class.php` — 4 metody
  (`getNonPersonalPermissionGroups`, `getNonPersonalSameLevelPermissionsGroups`, `getParentId`,
  `getGuestPermissionGroups`) → `static`.
- `application/models/project_milestones/ProjectMilestones.class.php` — 3 metody
  (`createMilestoneCopy`, `copyTasks`, `getRangeMilestones`) → `static`.
- `application/models/notifier/Notifier.class.php` — 5 metod (`notifyAction`,
  `milestoneAssigned`, `taskAssigned`, `workEstimate`, `sendReminders`) → `static`.
- `plugins/mail/application/models/mail_accounts/MailAccounts.class.php` — 2 metody
  (`getMailAccountsByUser`, `getMailAccountsEditByUser`) → `static` (`getAccountById` zostawione
  bez zmian - używa `$this`).
- `plugins/mail/application/models/mail_account_contacts/MailAccountContacts.class.php` — 6
  metod (`getByAccount`, `getByContact`, `getByAccountAndContact`, `deleteByAccount`,
  `deleteByContact`, `countByAccount`) → `static`.
- `plugins/mail/application/models/mail_contents/MailContents.class.php` — 4 metody
  (`getEmails`, `getByMessageId`, `countUserInboxUnreadEmails`, `getConditionsRules`) → `static`.
- **`(new ClassName())->canAdd(...)` zamiast `ClassName::canAdd(...)`** — zweryfikowane empirycznie
  (patrz "Ważna korekta wiedzy" niżej), że PHP 8.4 rzuca fatal error NIEZALEŻNIE od tego, czy
  `$this` istnieje w wywołującym kontekście (np. wewnątrz metody kontrolera) - liczy się tylko,
  czy jest zgodny typem. ~30 wywołań statycznych `canAdd()` (na `ProjectEvent`, `ProjectFile`,
  `ProjectForm`, `ProjectMessage`, `ProjectMilestone`, `ProjectTask`, `ProjectWebpage`, `Contact`,
  `MailAccount`) w kontrolerach i widokach zamienione skryptowo na `(new ClassName())->canAdd(...)`
  (metoda sama w sobie nie używa stanu instancji - to bezpieczna, semantycznie poprawna zamiana,
  bo `canAdd` sprawdza uprawnienie do dodania NOWEGO obiektu). Pominięto jedno martwe wystąpienie
  (`Project::canAdd()` w `application/views/dashboard/my_projects.php` - klasa `Project` w ogóle
  nie istnieje w tej wersji, kod już wcześniej niedziałający, poza zakresem tej migracji).
- `application/widgets/calendar/index.php` — `ProjectEvent::canAdd()` (ten sam przypadek,
  znaleziony osobno przed mass-fixem).

### Naprawione — `eval()` z dynamiczną klasą wołającą niestatyczną metodę statycznie

- `application/models/objects/Objects.class.php` (`findObject()`) — `eval('... '.$handler_class.
  '::findById(...)')` zamienione na `$handler_class::instance()->findById($object_id)` (usunięto
  `eval()` całkowicie - PHP wspiera wywołania statyczne przez zmienną klasy natywnie).
- `application/controllers/MemberController.class.php` (2 miejsca) — `eval('...'.$handler_class.
  '::getPublicColumns()')` → `$handler_class::instance()->getPublicColumns()`.
- `application/controllers/TemplateController.class.php` — analogicznie dla
  `getTemplateObjectProperties()`.

### Naprawione — kolejne niezgodne sygnatury metod (fatalne błędy kompilacji)

- `application/models/object_types/ObjectType.class.php` (`getIsLinkableObjectType()`) —
  `catch(Exception $e)` → `catch(Throwable $e)` (błąd fatalny wewnątrz `eval()` przy
  konstruowaniu obiektu nie był łapany), plus `Logger::log()` błędu zamiast cichego przełknięcia.
- `application/models/comments/Comment.class.php` — `getCommentNum()` koliduje nazwą z
  `ContentDataObject::getCommentNum(Comment $comment)` (inna funkcja, przypadkowa kolizja nazw,
  jak wcześniej `getTableName()`) → dodano zgodny (nullable) parametr, metoda nieużywana z
  zewnątrz więc zero ryzyka zmiany zachowania.
- `plugins/mail/application/models/mail_contents/MailContent.class.php` — `getComments()` →
  dodano brakujący parametr `$include_trashed = false` zgodny z `ContentDataObject`.
- `application/models/DimensionObject.class.php` i `plugins/workspaces/application/models/
  Workspace.class.php` — `getIconClass()` → dodano brakujący parametr `$large = false` zgodny z
  `ContentDataObject::getIconClass($large = false)` (to dokładnie ten błąd blokował wyświetlanie
  drzewa "Obszary robocze" — konstrukcja klasy `Workspace` failowała przy autoloadzie).

### Naprawione — `ContentDataObjects::listing()` wołane na klasie abstrakcyjnej

Cztery miejsca (`DashboardController.class.php`, 3x `ObjectController.class.php`) wołały
`ContentDataObjects::listing(...)` bezpośrednio na klasie abstrakcyjnej — zamierzony,
udokumentowany w kodzie wzorzec "generycznego listowania obiektów wszystkich typów", polegający
w PHP 5 na tym, że `$this` wewnątrz metody było cicho `null`, a metoda (`listing()` i pomocnicza
`getObjectTypeId()`) sprawdzała `$this instanceof ContentDataObjects` żeby wykryć ten przypadek.
**W PHP 8 samo odwołanie się do `$this` poza kontekstem obiektu jest fatalnym błędem** - a
wywołanie niestatycznej metody statycznie fatalnie kończy się jeszcze wcześniej, zanim ciało
metody w ogóle zacznie się wykonywać (zweryfikowane empirycznie - `isset($this)` nie pomaga,
bo call site fatalnieje first). Nie da się tego naprawić samym dodaniem `static`, bo metoda
faktycznie różni się zachowaniem zależnie od tego, czy jest wywołana na konkretnym obiekcie.

Rozwiązanie: nowa klasa `application/models/GenericContentDataObjects.class.php` — konkretna,
"bezosobowa" podklasa `ContentDataObjects` (konstruktor jak `BaseObjects`, `object_type_name`
pozostaje `null`, dokładnie odtwarzając stare zachowanie "brak `$this`"). 4 wywołania zamienione
na `(new GenericContentDataObjects())->listing(...)`.

### Naprawione — pozostałości `ext/mysql` (usuniętego w PHP 7)

Zamiast punktowo poprawiać kilkanaście wywołań `mysql_real_escape_string()`/`mysql_query()`/
`mysql_fetch_array()`/`mysql_error()` w różnych kontrolerach (ryzyko literówek przy ręcznym
przepisywaniu zapytań SQL budowanych przez konkatenację), dodano w
`environment/functions/general.php` kompatybilne polyfille tych 4 funkcji oparte o aktywne
połączenie PDO (`DB::connection()->getLink()`) — zachowują identyczny kontrakt (m.in.
`mysql_real_escape_string` NIE dodaje cudzysłowów wokół wyniku, tak jak oryginał, żeby nie
złamać istniejących zapytań budowanych jako `"... = '".mysql_real_escape_string($x)."'"`).

### Naprawione — `count(): Argument #1 must be of type Countable|array, null given`

Kolejna kategoria fatalnych `TypeError` w PHP 8 (w PHP 7 `count(null)` tylko ostrzegał i zwracał
0) - dodano `is_array()`/domyślną wartość przed `count()` w:
`application/controllers/DimensionController.class.php`,
`application/models/ContentDataObjects.class.php`,
`application/models/object_members/ObjectMembers.class.php` (2 miejsca).

### Ważna korekta wiedzy (dla przyszłych sesji)

Wcześniejsze założenie "wywołanie niestatycznej metody statycznie jest bezpieczne, jeśli
wywołujący kontekst ma jakiekolwiek `$this`" jest **błędne**. Zweryfikowane empirycznie: PHP 8.4
fatalnie kończy wywołanie `B::niestatyczna()` z wnętrza metody instancji klasy `A` (niezwiązanej
z `B`) dokładnie tak samo, jak z kontekstu w pełni statycznego - liczy się WYŁĄCZNIE zgodność
typu `$this` z klasą deklarującą metodę (albo jej przodkiem/potomkiem). To oznacza, że każde
wywołanie `ClassName::metoda()` (bez `self::`/`parent::` z rzeczywiście zgodnej hierarchii) do
metody NIE oznaczonej `static` jest fatalne w PHP 8+, niezależnie od tego, skąd jest wołane.

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
