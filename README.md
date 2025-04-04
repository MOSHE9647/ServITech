# ServITech

[![Laravel Logo](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)](https://laravel.com)

[![Build Status](https://github.com/laravel/framework/workflows/tests/badge.svg)](https://github.com/laravel/framework/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/framework)](https://packagist.org/packages/laravel/framework)
[![Latest Stable Version](https://img.shields.io/packagist/v/laravel/framework)](https://packagist.org/packages/laravel/framework)
[![License](https://img.shields.io/packagist/l/laravel/framework)](https://packagist.org/packages/laravel/framework)

## Sobre el Proyecto

ServITech es una aplicación web desarrollada con el framework Laravel. Este proyecto tiene como objetivo proporcionar una plataforma robusta y escalable para la gestión de servicios técnicos.

## Instalación de PHP

Antes de ejecutar la aplicación, asegúrate de que tu máquina local tenga instalados [PHP](https://www.php.net/ "PHP") y [Composer](https://getcomposer.org/ "Composer"). Además, deberías instalar [Node y NPM](https://nodejs.org/en/download "Node y NPM") para poder compilar los recursos del frontend de la aplicación.

## Cómo Ejecutar el Proyecto

Para ejecutar el proyecto localmente, sigue estos pasos:

1. Clona el repositorio:

    ```sh
    git clone https://github.com/tu-usuario/servitech.git
    cd servitech
    ```

2. Crea un archivo `.env` basado en el ejemplo proporcionado:

    ```sh
    cp .env.example .env
    ```

3. Genera la clave de la aplicación:

    ```sh
    php artisan key:generate
    ```

4. Configura tu archivo `.env` con las credenciales de tu base de datos y otros servicios.

5. Ejecuta las migraciones de la base de datos:

    ```sh
    php artisan migrate
    ```

6. Inicia el servidor de desarrollo:

    ```sh
    composer run dev
    ```

## Cómo Buildear el Proyecto

Para buildear el proyecto para producción, ejecuta:

```sh
npm run build
```

## Acceder a la Documentación de la API

La documentación de la API está disponible en la ruta `/api/docs`. Puedes acceder a ella visitando:  

## Ejemplo de Archivo .env  

Aquí tienes un ejemplo de cómo debería verse tu archivo `.env`:

```sh
APP_NAME=ServITech
APP_VERSION=1.0.0
APP_ENV=local
APP_KEY=base64:tu_clave_genérica
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_LEVEL=debug

## Si utilizas MySQL cambia 'sqlite' por 'mysql' y descomenta las variables DB_*
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña
MAIL_FROM_ADDRESS="mailhelper@servitechcr.com"
MAIL_FROM_NAME="${APP_NAME}"

# L5 SWAGGER
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_API_BASE_PATH=/
L5_SWAGGER_API_ROUTE=/api/doc
L5_SWAGGER_USE_ABSOLUTE_PATH=true
L5_FORMAT_TO_USE_FOR_DOCS=json

VITE_APP_NAME="${APP_NAME}"
```

## Contribuyendo

Gracias por considerar contribuir al proyecto **ServITech**. La guía de contribución se puede encontrar en la documentación de Laravel.  

## Código de Conducta

Para asegurar que la comunidad de **ServITech** sea acogedora para todos, por favor revisa y cumple con el **Código de Conducta**.  

## Licencia

El framework **Laravel** es un software de código abierto licenciado bajo la licencia **MIT**.  
