# Changelog

Wszystkie znaczące zmiany w projekcie (migracja PHP 5.5 → 8.4, MySQL, dalej API/MCP) są
odnotowywane w tym pliku. Format luźno wzorowany na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/).

Najnowsze zmiany na górze.

## [Nieopublikowane]

## 2026-09-03 (część 8): wersjonowanie forka

Dodane: plik `VERSION` (SemVer) w root repo, stała `FORK_VERSION` w `init.php` czytana z tego
pliku, wyświetlana w stopce obok linku do kodu źródłowego. Niezależne od `version.php`
(pozostawionego bez zmian — porównują się do niego skrypty `public/upgrade/`). Od teraz release
= aktualizacja `VERSION` + tag gita (`vX.Y.Z`) + wpis w tym pliku.

## 2026-09-03 (część 7): naprawa Administracji i Konta

Zgłoszenie: sekcje "Administracja" i "Konto" w ogóle nie działały po zalogowaniu. Metoda
diagnozy: skrypt CLI ładujący `init.php` z `CONSOLE_MODE` (pomija automatyczny dispatch),
symulujący zalogowanego użytkownika przez `CompanyWebsite::instance()->setLoggedUser()`, wołający
kolejno każdą akcję obu kontrolerów bezpośrednio i łapiący `Throwable` — ta sama metoda co przy
diagnozie "pustego body" w części 1, tylko usystematyzowana w pętli po wszystkich akcjach zamiast
punktowych sprawdzeń.

### Naprawione
- `application/models/config_categories/ConfigCategories.class.php` (`getAll()`) — brak `static`,
  metoda nie używa `$this` (blokowało `AdministrationController::configuration()`).
- `application/models/administration_tools/AdministrationTools.class.php` (`getAll()`,
  `getByName()`) — jw., brak `static` (blokowało `AdministrationController::tools()`).
- `application/models/project_tasks/ProjectTasks.class.php`
  (`getAllTaskTemplates()`) — `ORDER BY \`title\`` w SQL, ale `title` nie jest kolumną tabeli
  `project_tasks` (tytuł żyje w `objects.name`, a to zapytanie nie robi JOIN-a z `objects`) —
  błąd `Column not found: 1054 Unknown column 'title'`. To NIE jest regresja migracji PHP 8.4,
  tylko pre-istniejący bug (nieprawidłowa kolumna w ORDER BY istniała już w oryginalnym kodzie,
  po prostu nigdy wcześniej nie trafiona). Naprawione przez usunięcie `ORDER BY` z SQL i sortowanie
  wyniku w PHP po `getObjectName()` (`usort` + `strcasecmp`), zamiast dorabiać JOIN do zapytania.

Zweryfikowane: wszystkie akcje `AdministrationController` (13) i przetestowane akcje
`AccountController` (7) wykonują się bez `Throwable` w powyższym skrypcie diagnostycznym.

## 2026-09-03 (część 6): zgodność z AGPLv3 przed publikacją na GitHubie

Feng Office jest na licencji AGPLv3 (`license.txt`) — jej klauzula sieciowa obowiązuje niezależnie
od tego, że konkretne wdrożenie działa tylko w sieci wewnętrznej (dostęp z zewnątrz wyłącznie przez
VPN): każdy użytkownik łączący się przez sieć musi mieć możliwość dotarcia do kodu źródłowego
zmodyfikowanej wersji, nie tylko użytkownicy internetu publicznego.

### Dodane
- `README.md` — notatka na górze: nieoficjalny fork, brak powiązania z Feng Office, licencja
  AGPLv3.
- **Link "Kod źródłowy (AGPLv3)" w stopce aplikacji** (`application/layouts/website.php`, obok
  istniejącego `product_signature()`) — wskazuje na `SOURCE_CODE_URL` (nowa stała configu,
  ustawiana per-wdrożenie w `config/config.php`). Spełnia literalnie wymóg AGPLv3 §13 (prominent
  notice + sposób dotarcia do Corresponding Source) niezależnie od tego, czy operator wdrożenia
  poinformował użytkowników o lokalizacji repo inną drogą.

## 2026-09-03 (część 5): przygotowanie deploymentu Docker pod produkcję

Cel: wdrożenie w środowisku Docker obok innych, już działających projektów na tym samym serwerze,
za nginxem/reverse proxy. **Świadoma decyzja: bez własnego kontenera MySQL** — kontener `app`
dołącza do już istniejącej, współdzielonej sieci Dockera (utworzonej przez inny projekt w tej
samej infrastrukturze) i łączy się z jego kontenerem MySQL, zamiast stawiać kolejny serwer
bazodanowy. Wymaga dostosowania nazwy sieci/kontenera do konkretnego środowiska docelowego —
patrz komentarze w `docker-compose.yml`.

### Dodane
- `Dockerfile` — `php:8.4-fpm` + rozszerzenia realnie używane przez kod: `gd` (fpdf/simplegd),
  `curl` (PEAR/Zend HTTP), `zip` (upload/eksport plików), `mbstring`, `pdo_mysql`, `xml`, `opcache`
  (zweryfikowane grepem po `application/`/`library/`, nie zgadywane).
- `docker-compose.yml` — usługi `app` (PHP-FPM) i `web` (nginx, port hosta **8084** jako przykład —
  zweryfikuj `docker ps` na docelowym serwerze i wybierz faktycznie wolny port). `web` widzi tylko
  izolowaną sieć `internal`, nie sieć współdzieloną z bazą — żeby uniknąć sytuacji, w której dwa
  różne projekty na tym samym serwerze mają usługę o tej samej nazwie (`app`) na wspólnej sieci
  i `fastcgi_pass app:9000` omyłkowo rozwiązuje się na kontener innego projektu. Brak usługi
  `database` — zamiast tego zewnętrzna sieć (`external: true`) wskazująca na już istniejący
  kontener bazy.
- `nginx.conf` — document root = katalog główny projektu (nie `public/`), bo Feng Office wywołuje
  `index.php` bezpośrednio z roota (routing przez query string), a `public/` to tylko
  assety/API/webservices. Zablokowany bezpośredni dostęp do `config/`, `cache/`, `environment/`,
  `tmp/`.
- `.dockerignore` — wyklucza `upload/` (~6 GB) i dumpy `*.sql` (766 MB) z kontekstu builda (i tak
  montowane później jako wolumen, nie kopiowane w Dockerfile).
- `config/config.docker.php.example` — szablon `config/config.php` pod produkcję: `DB_HOST=database`
  (nazwa usługi w zewnętrznej sieci bazy danych, nie nowy hostname), `DB_NAME`/`DB_USER=fo23`,
  `ROOT_URL` — placeholder domeny do podmiany na docelową.
- `config/PRODUCTION_DB_SETUP.sql` — jednorazowy skrypt do uruchomienia na serwerze (`docker compose
  exec database mysql -u root -p < ...`) tworzący bazę `fo23` i usera `fo23` w **istniejącym**
  kontenerze MySQL. Nie uruchomione automatycznie — wymaga hasła roota z produkcji.
- `deploy.sh` / `update.sh` — bez kroków Composer/Doctrine (Feng Office nie ma menedżera
  zależności ani migracji) — `deploy.sh` dodatkowo czyści `cache/` (autoloader odbudowuje się
  automatycznie) i ustawia `www-data:www-data` na `upload/`/`cache/`/`tmp/`.

### Do zrobienia (poza tą sesją, wymaga dostępu do docelowego serwera)
- Utworzyć katalog projektu na serwerze, `git clone`/`rsync` repo (bez `upload/` i dumpów —
  te trzeba przenieść osobno, rsync z obecnej produkcji PHP 5.5).
- Dostosować nazwę zewnętrznej sieci Dockera w `docker-compose.yml` do tej faktycznie utworzonej
  przez istniejący kontener bazy danych na docelowym serwerze.
- Uruchomić `config/PRODUCTION_DB_SETUP.sql` na kontenerze bazy, zaimportować dump danych
  (`kopia_fo23_*.sql`) do nowo utworzonej bazy `fo23`.
- Skopiować `config/config.docker.php.example` → `config/config.php` na serwerze, wpisać
  wygenerowane hasło DB i docelową domenę.
- Skonfigurować reverse proxy / proxy manager pod docelową domenę → port `web`.
- `docker compose up -d --build`, potem `chown -R www-data:www-data upload cache tmp`.

## 2026-09-03 (część 4): pełna obsługa zadań (widok, edycja, nowe zadanie, filtrowanie)

Zgłoszenia po części 3: nie można wejść w szczegóły zadania, formularz edycji miał puste pola,
zawężanie listy zadań przez obszar roboczy/tag nie działało, "Nowe zadanie" nie działało. Wszystkie
cztery okazały się tą samą kaskadą co poprzednio — naprawione i zweryfikowane wizualnie (wejście
w zadanie, edycja z wypełnionymi polami, nowe zadanie, filtrowanie przez obszar roboczy + tag
łącznie).

### Naprawione — `Hook::fire()` z przypisaniem jako argument przez referencję

`Hook::fire("...", $object, $ret = 0)` — trzeci parametr `Hook::fire` jest zadeklarowany jako
`&$ret` (przez referencję). Przekazanie wyrażenia przypisania `$ret = 0` zamiast gotowej zmiennej
było tolerowane w PHP 7 ("Only variables should be passed by reference"), w PHP 8 jest fatalne.
Naprawione w `application/views/co/view.php` i `application/views/co/properties.php` przez
rozbicie na `$ret = 0;` + `Hook::fire(..., $ret);`.

### Naprawione — biblioteka HTMLPurifier i inne third-party: składnia `{}` dla offsetu stringa

Blokowało to renderowanie KAŻDEGO opisu zadania/komentarza przechodzącego przez `purify_html()`
(fatal parse error przy pierwszym użyciu, więc każdy widok zadania z opisem od razu się wysypywał).
Naprawione we wszystkich znalezionych wystąpieniach w `library/htmlpurifier/
HTMLPurifier.standalone.php` (3 miejsca) oraz skryptowo w 19 innych plikach bibliotek third-party
(`library/utf8/*`, `library/json/JSON.class.php`, `library/swift/*`, `library/zend/Zend/
Search/Lucene/*`, `library/PEAR/*` i inne) — składnia `$var{offset}` usunięta w PHP 8.0, zastąpiona
`$var[offset]` (identyczna semantyka, zero zmiany zachowania). Jeden plik (`library/PEAR/HTTP/
Request.php`) ma dodatkowo pre-existing, niezwiązany błąd (`&new` - referencyjny `new`, usunięty w
PHP 7) — potwierdzone, że ta klasa (PEAR `HTTP_Request`) nie jest nigdzie używana w aplikacji,
zostawione bez zmian.

### Naprawione — kolejne metody bez `static` (ten sam wzorzec co w częściach 2-3)

- `application/models/object_reminders/ObjectReminders.class.php` — 4 metody
  (`getAllRemindersByObjectAndUser`, `getByObject`, `getDueReminders`, `findByEvent`).
- `application/models/project_co_types/ProjectCoTypes.class.php` — `getObjectTypesByManager`
  (blokowało formularz edycji/dodawania zadania — `add_task.php` woła ją przy budowaniu listy
  dostępnych typów obiektów).
- `application/models/contact_member_permissions/ContactMemberPermissions.class.php` — 5 metod
  (`contactCanAccessMemberAll`, `contactCanReadObjectTypeinMember`,
  `canAccessObjectTypeinMembersPermissionGroups`, `getActiveContextPermissions`,
  `grantAllPermissions`) — blokowało sprawdzanie uprawnień przy przełączaniu kontekstu na
  konkretny obszar roboczy (`application/helpers/permissions.php`).
- `application/helpers/pageactions.php` (`PageActions::clearActions`) — call site
  `application/views/co/actions.php` zmieniony na `PageActions::instance()->clearActions()`
  (metoda używa `$this`, więc — inaczej niż większość napraw w tej sesji — nie mogła być po
  prostu oznaczona jako `static`).

### Naprawione — luka w skryptowej naprawie z części 2/3: wywołania ze spacją przed nawiasem

Wcześniejszy mechaniczny fix `ClassName::metoda(` → `ClassName::instance()->metoda(` (i analogicznie
dla `self::`) używał wzorca wymagającego braku spacji przed `(`. Kod w kilku miejscach ma styl
`ClassName::metoda ( ...)` (ze spacją) — te wystąpienia zostały pominięte i dopiero teraz ujawniły
się jako kolejne fatalne błędy. Naprawione (tym samym mechanizmem, tylko z dopasowaniem spacji)
w: `application/models/contacts/Contact.class.php`, `application/models/contact_member_permissions/
ContactMemberPermissions.class.php`, `application/models/project_tasks/ProjectTasks.class.php`,
`application/models/template_tasks/TemplateTasks.class.php`,
`application/models/project_files/ProjectFiles.class.php` (plus dodanie `static` do
`findByCSVIds`), `plugins/workspaces/hooks/workspaces_hooks.php`. Warto pamiętać o tym wariancie
przy każdej przyszłej naprawie tego wzorca.

## 2026-09-03 (część 3): zakładki Zadania/Kalendarz/Czas + brakujące ikonki

Zgłoszenia użytkownika po części 2: brak ikon w "Obszarach roboczych" i w widżecie
"Dokumenty" na dashboardzie, oraz (najważniejsze) niedziałające zakładki Zadania, Kalendarz,
Czas. Wszystkie 5 zgłoszeń naprawione i zweryfikowane wizualnie.

### Naprawione — brakujące ikonki (jedna wspólna przyczyna)

`http://fo.local/s.gif` (uniwersalny 1x1 "spacer gif" ExtJS, używany wszędzie do renderowania
ikon przez CSS `background-image` na klasie) zwracał 404 — plik `s.gif` (i `favicon.ico`)
fizycznie leży w katalogu głównym repo, nie w `public/` (ten sam rodzaj problemu co
`public/plugins` z poprzedniej sesji: stara struktura zakładała webroot = katalog główny, a
migracja wprowadziła `public/` jako webroot). Skopiowano oba pliki do `public/`. Naprawiło to
jednocześnie ikonki w "Obszarach roboczych" i w widżecie "Dokumenty" (ikony typu pliku PDF)
na dashboardzie — obie korzystały z tego samego mechanizmu.

### Naprawione — zakładka Zadania (`TaskController::new_list_tasks`)

Dwie kolejne kolizje nazw metod tego samego typu co wcześniejsze `getTableName()`/
`getCommentNum()`: `Comment::getTimeslotNum()`… a właściwie **`Timeslot::getTimeslotNum()`**
w `application/models/timeslots/Timeslot.class.php` kolidowało z
`ContentDataObject::getTimeslotNum(Timeslot $timeslot)` — blokowało autoload klasy `Timeslot`
wywoływanej z `ProjectTasks::getArrayInfo()` → `getOpenTimeslots()`. Dodano zgodny (nullable)
parametr, metoda nieużywana z zewnątrz.

### Naprawione — zakładka Kalendarz (`event/calendar.php`)

`ProjectTasks::getRangeTasksByUser()` i 5 innych metod w `ProjectTasks.class.php`
(`maxOrder`, `createTaskCopy`, `copySubTasks`, `findByRelated`, `findByTaskAndRelated`) nie
były oznaczone `static`, mimo że wołane statycznie w kilku miejscach (w tym rekurencyjnie
wewnątrz samej klasy — `copySubTasks` woła `ProjectTasks::createTaskCopy(...)` i
`ProjectTasks::copySubTasks(...)`). Żadna nie używa `$this` → bezpieczne dodanie `static`.
(`findByRelatedCached` zostawiona bez zmian - używa `$this`.)

### Naprawione — zakładka Czas (`TimeController::index`)

`ContentDataObjects::populateTimeslots()` wołane statycznie na klasie abstrakcyjnej z
`ProjectTasks::populateTimeslots($tasks)` — metoda operuje wyłącznie na przekazanym
argumencie `$objects_list`, nie na `$this`, więc (w przeciwieństwie do `listing()` z części 2)
wystarczyło dodać `static` bez potrzeby tworzenia dodatkowej "bezosobowej" klasy.

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
