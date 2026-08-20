# Bot Clima Maracaibo — Notificador de hidratación por clima

Bot automatizado que consulta el pronóstico diario de temperatura en Maracaibo, calcula el mínimo de agua a hervir y consumir según el hogar (5 adultos + 1 bebé de 9 meses), y notifica el plan del día por Telegram y Gmail. Corre dos veces al día vía cron: cálculo completo a las 6:00 AM, recordatorio a las 12:00 PM.

🌐 [bot-clima.mhenriquez.com](https://bot-clima.mhenriquez.com) · 📦 Release v1.0.0

---

### Stack tecnológico 💻

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white) ![Docker](https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white) ![Composer](https://img.shields.io/badge/composer-%23885630.svg?style=for-the-badge&logo=composer&logoColor=white) ![Git](https://img.shields.io/badge/git-%23F05033.svg?style=for-the-badge&logo=git&logoColor=white)

<details>
<summary>Ranking por uso 📈</summary>

| Ranking | Tecnología                  |
|--------:|------------------------------|
| 1       | **PHP 8.3**                  |
| 2       | **MySQL 8.4**                 |
| 3       | **Docker / Docker Compose**   |
| 4       | Guzzle (HTTP client)          |
| 5       | PHPMailer (SMTP)              |
| 6       | vlucas/phpdotenv              |
| 7       | Open-Meteo API (sin key)      |
| 8       | Telegram Bot API              |
| 9       | Git / GitHub                  |

</details>

---

## Arquitectura del cálculo 🧮

```
06:00 (cron 1 — run-morning.php)
→ Consulta pronóstico de temperatura máxima (Open-Meteo, Maracaibo)
→ Calcula litros: 2.0L base + 0.08L por cada °C sobre 25°C (calibrado con dato real: 2.4L a 30°C)
→ Bebé: 245ml fijos (245ml, punto medio recomendación pediátrica 9 meses)
→ Hervidas necesarias = ceil(total_litros / 5L) — capacidad de la olla
→ Guarda registro del día en MySQL (protegido contra duplicado con UNIQUE KEY)
→ Notifica Telegram + Gmail con el plan completo del día

12:00 (cron 2 — run-noon.php)
→ NO vuelve a consultar el clima (evita inconsistencia con el cálculo de la mañana)
→ Lee el registro de hoy ya guardado
→ Notifica recordatorio de las hervidas restantes
```

---

## Setup local ⚙️

**Requisitos:** Docker + Docker Compose · WSL2/Ubuntu (regla de oro: el proyecto vive en el filesystem nativo de Linux, nunca en `/mnt/c/`)

```bash
git clone https://github.com/MHenriquezCA/bot-clima.git
cd bot-clima

cp .env.example .env
# Completar credenciales reales en .env (ver sección de abajo)

docker compose -f docker-compose-dev.yml up -d --build
```

Instalar dependencias con Composer efímero (sin ensuciar el host):
```bash
cd src
docker run --rm -u $(id -u):$(id -g) -v $(pwd):/app -w /app composer:2.9.4 composer install
cd ..
```

Crear el esquema de base de datos:
```bash
docker compose -f docker-compose-dev.yml exec -T mh_clima_db sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" mh_clima' < database/schema.sql
```

Probar el flujo completo:
```bash
docker compose -f docker-compose-dev.yml exec mh_clima_web php bin/run-morning.php
docker compose -f docker-compose-dev.yml exec mh_clima_web php bin/run-noon.php
```

Prueba aislada de cada notificador (sin tocar la base de datos):
```bash
docker compose -f docker-compose-dev.yml exec mh_clima_web php tests/test-telegram.php
docker compose -f docker-compose-dev.yml exec mh_clima_web php tests/test-mail.php
```

---

## Variables de entorno requeridas 🔑

```env
# Base de datos
DB_HOST=mh_clima_db
DB_NAME=
DB_USER=
DB_PASS=
DB_ROOT_PASS=

# Clima (Maracaibo, Zulia, Venezuela)
WEATHER_LAT=10.6427
WEATHER_LON=-71.6125
WEATHER_TIMEZONE=America/Caracas
WEATHER_CITY=Maracaibo

# Hogar
HOUSEHOLD_ADULTS=5

# Telegram (BotFather + getUpdates para el chat_id)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# Gmail (contraseña de aplicación, no la contraseña normal)
GMAIL_USER=
GMAIL_APP_PASSWORD=
GMAIL_TO=
GMAIL_CC=
```

> ⚠️ En hosting compartido (CloudLinux/CageFS), `phpdotenv` v5+ no usa `putenv()` por defecto — las variables quedan solo en `$_ENV`/`$_SERVER`, no en `getenv()`. Por eso el proyecto usa el helper `env()` definido en `app/helpers/common.php` en vez de `getenv()` directo, para que el mismo código funcione igual en Docker local y en producción.

---

## Estructura del proyecto 📁

```
mh_bot_clima_data/
├── app/
│   ├── helpers/
│   │   └── common.php     # base_dir(), env()
│   ├── Repositories/      # RunLogRepository
│   └── Services/          # HydrationCalculator, MailNotifier, TelegramNotifier, WeatherService
├── bin/
│   ├── run-morning.php    # Cron 6:00 AM
│   └── run-noon.php       # Cron 12:00 PM
├── config/
│   └── database.php       # Factory de conexión PDO
├── database/
│   ├── 2026-08-19-....sql # Migraciones para modificar las tablas
│   └── schema.sql         # Schema de la db
├── docs/
│   └── files.md           # Archivos de documentación para el desarrollador
├── public/
│   ├── resources/         # Recursos de media
│   └── index.php          # Punto de entrada de la app
└── tests/                 # Scripts de prueba manual, aislados (no suben a producción)
```

---

## Deploy 🚀

Hosting compartido (cPanel, sin acceso SSH tradicional — se usa la Terminal integrada de cPanel):

1. `vendor/` se genera en local y se sube completo (no hay Composer disponible por SSH en el servidor).
2. `.env` se crea directo en el servidor, nunca se sube por Git ni FTP mezclado con el código.
3. Esquema de base de datos se importa vía phpMyAdmin.
4. Cron Jobs configurados desde la interfaz de cPanel:

```cron
0 6 * * * /usr/local/bin/ea-php83 /home/gdchvqhp/curso.mhenriquez.com/bin/run-morning.php >> /home/gdchvqhp/curso.mhenriquez.com/storage/logs/morning.log 2>&1
0 12 * * * /usr/local/bin/ea-php83 /home/gdchvqhp/curso.mhenriquez.com/bin/run-noon.php >> /home/gdchvqhp/curso.mhenriquez.com/storage/logs/noon.log 2>&1
```

> ⚠️ Verificar `/.env` desde el navegador (`https://curso.mhenriquez.com/.env`) devuelve 403/404 — nunca debe ser accesible públicamente.

---

## Roadmap 🗺️

- [ ] Destinatarios múltiples en Telegram (varios `chat_id`) y Gmail (`GMAIL_TO`/`GMAIL_CC` con lista)
- [ ] Dashboard simple de histórico (`run_logs`) para ver tendencias de consumo por mes
- [ ] Alertas si `notification_status` queda en `failed` tras reintentos

---

Desarrollado por [MHenriquez C.A.](https://mhenriquez.com) · Maracaibo, Venezuela
