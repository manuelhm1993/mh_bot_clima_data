# Bot Clima Maracaibo — Bitácora de desarrollo (v1.0.0)

Formato pensado para copiar directo a Trello (cada `##` = lista, cada `- [ ]` = tarjeta) o crear como Issues en GitHub Projects (cada tarjeta = 1 issue, usando las etiquetas y prioridades indicadas).

**Convención de labels:** `Backend` · `Infra` · `Docs` · `Bug` · `Chore`
**Convención de prioridad (Matriz de Covey):** 🌋 Urgente · 🔥 Alta · 📋 Media · 🥱 Baja

---

## ✅ Épica 1 — Infraestructura Docker local

- [x] `Dockerfile.dev` con PHP 8.3-Apache + extensiones `pdo_mysql`, `curl` `Infra` 🔥
- [x] `docker-compose-dev.yml` con servicios `web` + `db`, puertos evadiendo Laragon (8080/33060) `Infra` 🌋
- [x] Volumen nombrado `mh_bot_clima_data` para persistencia de MySQL `Infra` 🔥
- [x] `healthcheck` en servicio `db` con `mysqladmin ping` autenticado `Infra` 📋
- [x] `depends_on: condition: service_healthy` para evitar condición de carrera en el arranque `Infra` 📋
- [x] Auto-inicialización de esquema vía `/docker-entrypoint-initdb.d/` `Infra` 📋

## ✅ Épica 2 — Autoload y estructura del proyecto

- [x] `composer.json` con PSR-4 (`App\` → `app/`) y `files` para helpers globales `Backend` 🔥
- [x] Estructura de carpetas: `app/Services`, `app/Repositories`, `app/helpers`, `bin/`, `config/` `Chore` 📋
- [x] Corrección de case-sensitivity en carpetas (`services` → `Services`, Linux es case-sensitive) `Bug` 🔥

## ✅ Épica 3 — Lógica de negocio

- [x] `WeatherService` — consulta Open-Meteo, temperatura máxima diaria de Maracaibo `Backend` 🌋
- [x] `HydrationCalculator` — fórmula lineal calibrada con dato real del usuario (2.4L @ 30°C) `Backend` 🌋
- [x] Litros bebé como constante fija (245ml, no escala con temperatura) `Backend` 🔥
- [x] Cálculo de hervidas necesarias según capacidad de olla (5L) `Backend` 🔥

## ✅ Épica 4 — Persistencia

- [x] Esquema `run_logs` con `UNIQUE KEY uniq_run_date` (anti-duplicado) `Backend` 🌋
- [x] `RunLogRepository::insertToday()` con manejo de constraint violation (código 23000) `Backend` 🔥
- [x] `RunLogRepository::findToday()` para el cron de mediodía (sin recalcular clima) `Backend` 🔥
- [x] `config/database.php` como factory de PDO, agnóstico de entorno `Backend` 📋

## ✅ Épica 5 — Notificaciones

- [x] `TelegramNotifier` vía Guzzle + Telegram Bot API (`sendMessage`, HTML parse mode) `Backend` 🔥
- [x] `MailNotifier` vía PHPMailer + SMTP Gmail (contraseña de aplicación, STARTTLS) `Backend` 🔥
- [x] Manejo de fallos sin interrumpir el script (log guardado aunque falle la notificación) `Backend` 📋
- [x] Scripts de prueba aislados (`tests/test-telegram.php`, `tests/test-mail.php`) `Chore` 📋

## ✅ Épica 6 — Entry points y cron

- [x] `bin/run-morning.php` — flujo completo: clima → cálculo → insert → notificar `Backend` 🌋
- [x] `bin/run-noon.php` — recordatorio sin recalcular clima `Backend` 🔥
- [x] Cron en WSL2 como red de seguridad (retirado tras validar producción) `Infra` 📋
- [x] Cron en cPanel (Banahosting) — 6:00 AM y 12:00 PM `Infra` 🌋

## ✅ Épica 7 — Migración a producción (Banahosting)

- [x] Verificación de PHP 8.3 disponible por dominio (MultiPHP Manager) `Infra` 🌋
- [x] Creación de base de datos y usuario MySQL vía cPanel `Infra` 🌋
- [x] Subida de código vía zip + extracción en File Manager (sin SSH tradicional, puerto 22 bloqueado) `Infra` 🔥
- [x] `.env` de producción creado directo en servidor (nunca vía Git/FTP mezclado con código) `Infra` 🌋
- [x] Importación de `schema.sql` vía phpMyAdmin `Infra` 🔥
- [x] **Bug crítico resuelto:** `phpdotenv` v5+ no usa `putenv()` por defecto en CloudLinux/CageFS — variables solo en `$_ENV`. Fix: helper `env()` en `app/helpers/common.php` usado en vez de `getenv()` directo `Bug` 🌋
- [x] Prueba end-to-end en producción real (Terminal cPanel) `Infra` 🌋

## ✅ Épica 8 — Documentación y versionado

- [x] `README.md` con setup local, variables de entorno, arquitectura, deploy `Docs` 🔥
- [ ] Tag `v1.0.0` + GitHub Release `Chore` 🔥

---

## 🔜 Backlog — Roadmap futuro

- [ ] Soporte multi-destinatario en Telegram (lista de `chat_id`) `Backend` 📋
- [ ] Soporte multi-destinatario en Gmail — ya diseñado en `MailNotifier` (`recipients[]`/`ccRecipients[]`), falta solo poblar `.env` con más de un valor `Backend` 🥱
- [ ] Dashboard de histórico de `run_logs` (tendencia de consumo mensual) `Frontend` 🥱
- [ ] Reintentos automáticos si `notification_status = 'failed'` `Backend` 📋
- [ ] Verificar que `.env` no sea accesible públicamente vía navegador en producción `Bug` 🔥
- [ ] Replicar helper `env()` de forma consistente en ambos entornos (Docker ya funcionaba con `getenv()`, homogeneizar igual) `Chore` 📋
- [ ] Considerar `feels_like` (sensación térmica) en vez de temperatura de aire pura para el cálculo `Backend` 🥱

---

## 🐛 Bugs resueltos durante el desarrollo (para referencia futura)

| Bug | Causa | Fix |
|---|---|---|
| `docker: image not found composer:2.8.x` | Tag con wildcard no es válido en Docker Hub | Usar tag exacto (`2.9.4`) |
| `Class not found` | Carpetas en minúscula, PSR-4 case-sensitive | Renombrar a PascalCase (`app/Services/`) |
| `base_dir() undefined` | Función propia llamada antes de cargar el autoloader que la define | Primer `require` calculado con `dirname(__DIR__)` puro |
| `Unable to read .env` | `.env` fuera del volumen montado en Docker | `Dotenv::safeLoad()` en vez de `load()`, ya que Compose inyecta vía `env_file` |
| `Access denied 'root'@'localhost'` en `mysql -p` | Contraseña interactiva competía con stdin redirigido desde archivo SQL | Leer password de variable de entorno del contenedor (`$MYSQL_ROOT_PASSWORD`) |
| `Table 'run_logs' doesn't exist` | Esquema nunca ejecutado contra el volumen nuevo | Montar `schema.sql` en `/docker-entrypoint-initdb.d/` |
| `TELEGRAM_BOT_TOKEN` vacío en contenedor | `env_file` solo se lee al crear el contenedor, no en cada exec | `docker compose up -d --force-recreate <servicio>` tras editar `.env` |
| `Access denied for user ''@'localhost'` en producción | `phpdotenv` v5+ no usa `putenv()`, variables solo en `$_ENV` | Helper `env()` que lee `$_ENV`/`$_SERVER` antes que `getenv()` |

---

*Generado a partir de la sesión de desarrollo del 10 de agosto de 2026.*
