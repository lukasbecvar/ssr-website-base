## Server-Side-Rendering website base
This is a basic website template for creating a server-side-rendered websites with Symfony and Bootstrap. It includes a simple admin system with user management, visitor tracking, contact inbox, and logs management.

## Description
This template is essentially the skeleton of the [becvar-site](https://github.com/lukasbecvar/becvar-site) application, with just the public part removed. The admin system includes its own user system, visitor tracking, contact inbox, and logs, and is ready for further expansion. The public part is prepared for integrating a new design and content.

## Preview
![system preview](.github/assets/preview.gif)

## Installation (with mysql/apache)
- install app [Requirements](#requirements)
- Clone the repository to your /www directory
- configure apache config like [apache-site.conf](./docker/configs/apache-site.conf)
- for production use only APP_ENV=prod! (in .env)
- configure app environment in .env.prod
- install dependencies `composer install && npm install && npm run build`
- create database `doctrine:database:create`
- migrate database structure `doctrine:migrations:migrate --no-interaction`
- install opcache (optional)

## Development environment
The complete stack for development is in Docker Compose, including PHP, Node, and Composer. You just need to run the docker-start script.

## Translations
Translations are managed by [Symfony-translations](https://symfony.com/doc/current/translation.html). Translation select is based on ip geolocation using [ip-api.com](https://ip-api.com). If you want to add a new language, you need to create a new translation file in the `translations` directory. The file name should be the language code (e.g. `messages.en.yml`).

## Authentication
Authentication uses a completely custom user system with a username and password, managed by the application user manager. Only the first user can use the registration form to create an admin user. Login uses standard PHP session and cookie for the 'remember me' feature.

## Admin Suite Integration (Optional)
Admin Suite is an open-source admin panel for managing services and Linux servers [admin-suite-page](https://admin-suite.site/). You can use Admin Suite to manage your website and track metrics and logs.
### External Log Integration
External Log allows you to send logs to a remote service. Enable it in your `.env` file by setting `EXTERNAL_LOG_ENABLED=true` and configuring `EXTERNAL_LOG_API_TOKEN` and `EXTERNAL_LOG_URL`. More information about External Log can be found [here](https://github.com/lukasbecvar/admin-suite?tab=readme-ov-file#logging).
### Metrics Exporter Integration
Metrics Exporter allows you to get metrics from your website. Enable it in your `.env` file by setting `METRICS_EXPORTER_ENABLED=true` and configuring `METRICS_EXPORTER_ALLOWED_IP`. More information about Metrics Exporter can be found [here](https://github.com/lukasbecvar/admin-suite?tab=readme-ov-file#metrics-collector-for-http-services).

## Requirements
- Apache / Nginx PHP compatible web server
- PHP 8.5
- PHP-PDO_MYSQL
- PHP-INTL
- PHP-GD

## Dependencies
* Symfony
   * [Website](https://symfony.com/)   
* Bootstrap
   * [Website](https://getbootstrap.com/)
* Boxicons
   * [Website](https://boxicons.com/)
* Fontawesome
   * [Website](https://fontawesome.com)
* Apexcharts
   * [Github](https://github.com/apexcharts/apexcharts.js)
* PhpunitPrettyPrint
   * [Github](https://github.com/indentno/phpunit-pretty-print)

## License
This software is licensed under the [MIT license](LICENSE)
