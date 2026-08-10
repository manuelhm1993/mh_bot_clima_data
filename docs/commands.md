```bash
1. Crear el proyecto composer
dexec composer:2.9.4 composer init --name="mhenriquez/bot-clima" --type=project --require=php:^8.3 -n

2. Instalar las dependencias necesarias
dexec composer:2.9.4 composer require vlucas/phpdotenv guzzlehttp/guzzle phpmailer/phpmailer

3. Correr el contenedor
docker compose -f docker-compose-dev.yml exec -T mh_clima_db sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" mh_clima' < database/schema.sql

4. Comprobar la consulta 2 del dia
docker compose -f docker-compose-dev.yml exec mh_clima_web php bin/run-morning.php
```