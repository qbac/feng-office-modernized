-- Jednorazowe utworzenie bazy i użytkownika fo23 w ISTNIEJĄCYM, współdzielonym kontenerze MySQL
-- (inny projekt na tym samym serwerze, patrz docker-compose.yml -> shared_mysql_net).
-- Nie tworzymy nowego kontenera MySQL — tylko nową bazę/usera w tym, co już działa.
--
-- Uruchomić na docelowym serwerze, z katalogu tego innego projektu (ten z docker-compose.yml,
-- który definiuje usługę "database"):
--   docker compose exec database mysql -u root -p < /opt/fo23/config/PRODUCTION_DB_SETUP.sql
-- (hasło roota MySQL jest w .env / docker-compose.yml tamtego projektu -> MYSQL_ROOT_PASSWORD)
--
-- Podmienić 'ZMIEN_TO_HASLO' na wygenerowane hasło i wpisać to samo hasło
-- w config/config.php (DB_PASS) na serwerze produkcyjnym.

CREATE DATABASE IF NOT EXISTS fo23 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'fo23'@'%' IDENTIFIED BY 'ZMIEN_TO_HASLO';
GRANT ALL PRIVILEGES ON fo23.* TO 'fo23'@'%';
FLUSH PRIVILEGES;
