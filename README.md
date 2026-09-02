# Feng Office (fo23) — środowisko lokalne (Laragon)

CRM/kolaboracja oparta o Feng Office `2.7.1.6`. Historycznie produkcja działa na
PHP 5.5.9 + MySQL 5.5.62. To repo dokumentuje migrację na
PHP 8.4 + MySQL 8.4 i pracę lokalną w Laragonie.

Szczegóły architektury, status migracji i lista niekompatybilności PHP 8.4 do naprawienia:
zobacz **`CLAUDE.md`**.

## Wymagania

- Laragon z PHP **7.4.x** (na start — patrz "Dlaczego PHP 7.4, a nie 8.4" niżej) i MySQL 8.x
- Rozszerzenia PHP: `pdo_mysql`, `mbstring`, `gd`, `curl`, `zip`, `openssl`, `bcmath` (opcjonalnie, dla `BenchmarkTimer`)

## Setup od zera

1. **Baza danych** — utworzona lokalnie jako `fo23` z dumpa `kopia_fo23_2026-09-02.sql`
   (produkcyjny MySQL 5.5 dump). Jeśli trzeba odtworzyć od nowa:

   ```
   mysql -u root -e "DROP DATABASE IF EXISTS fo23; CREATE DATABASE fo23 CHARACTER SET utf8 COLLATE utf8_unicode_ci; SET PERSIST sql_mode='';"
   mysql -u root --init-command="SET sql_mode='';SET foreign_key_checks=0;" fo23 < kopia_fo23_2026-09-02.sql
   ```

   Uwaga: `sql_mode` musi być złagodzony (bez `STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE`),
   bo dump zawiera daty `0000-00-00 00:00:00` — domyślny `sql_mode` w MySQL 8 odrzuci import/zapytania.
   Robimy to trwale przez `SET PERSIST`, więc nie trzeba tego powtarzać przy każdym połączeniu.

2. **Konfiguracja** — `config/config.php` (nieobecny w gicie, wrażliwe dane). Wzorzec:
   `config/empty.config.php`. Kluczowe stałe:

   ```php
   define('DB_ADAPTER', 'pdo_mysql'); // NIE 'mysql' - ext/mysql usunięte w PHP 7+
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'fo23');
   define('ROOT_URL', 'http://fo.local');
   ```

3. **Wirtualny host** — Laragon auto-generuje `fo.local` na podstawie nazwy folderu
   (`C:\laragon\www\fo`) po restarcie usług ("Auto Virtual Hosts"). Wpis do
   `C:\Windows\System32\drivers\etc\hosts` wymaga uprawnień administratora — Laragon robi to
   sam przy starcie/restarcie Apache. Jeśli `http://fo.local` nie działa: uruchom Laragon jako
   administrator i zrestartuj Apache.

4. **Otwórz** `http://fo.local` w przeglądarce.

## Status: PHP 7.4 działa, PHP 8.4 prawie

**PHP 7.4 + MySQL 8.4: potwierdzone działające end-to-end** (strona logowania renderuje się
poprawnie). Naprawiono po drodze kilka realnych bugów w kodzie (nie tylko kosmetycznych) —
duplikaty nazw parametrów, zarezerwowaną nazwę klasy `Object`, `continue` poza pętlą, twarde
ścieżki produkcyjne w cache autoloadera. Pełna lista w `CLAUDE.md`.

**PHP 8.4: prawie działa** — cały kod przechodzi `php -l` bez błędów składni, żądanie dochodzi aż
do renderowania widoku logowania (widać to w `cache/log.php`), ale finalna odpowiedź HTTP wraca
pusta (200 OK, 0 bajtów). Przyczyna nieustalona — do zbadania w kolejnej sesji, szczegóły i
hipotezy w `CLAUDE.md` sekcja "Otwarty problem". Prawdopodobny winowajca: masowe wywoływanie
niestatycznych metod statycznie (deprecated w PHP 8, ale wciąż powinno działać przez fallback).

Żeby przełączyć wersję PHP używaną przez Laragon dla Apache: Laragon → prawy klik na "PHP" w
menu → wybór wersji (`7.4.33` na razie, `8.4.12` do dalszego debugowania).

## Dane, które NIE są w gicie

- `kopia_fo23_2026-09-02.sql` — dump produkcyjnej bazy (dane klienta)
- `upload/` — załączniki użytkowników (~6 GB)
- `cache/log.php` — log aplikacji (potrafi urosnąć do >1 GB, generowany automatycznie)
- `config/config.php` — hasła/sekrety

Patrz `.gitignore`.

## Plany

- Migracja pełnego stosu na PHP 8.4 (lista blockerów w `CLAUDE.md`)
- REST API (bazując na istniejących zalążkach w `public/API/` i `public/webservices/`, albo od zera)
- Serwer MCP dla integracji z AI
