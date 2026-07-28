# Cloudi Go Backend

Backend MVP para crear, administrar, compartir y medir enlaces cortos. La API esta construida con Laravel 12 y esta preparada para consumirse desde un panel propio en React/Vite con Tailwind.

## Requisitos

- PHP 8.3 o superior
- Composer 2
- MySQL 8 para produccion
- Extensiones PHP habituales de Laravel: `pdo`, `mbstring`, `openssl`, `tokenizer`, `ctype`, `json`, `fileinfo`

## Instalacion

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Para datos locales de prueba:

```bash
php artisan db:seed
```

Usuario local generado solo en `APP_ENV=local`:

```txt
email: admin@cloudi.local
password: password
```

## Configuracion

Variables principales:

```env
APP_NAME="Cloudi Go"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
FRONTEND_URL=http://localhost:5173
SHORT_URL_BASE=http://localhost:8000
APP_TIMEZONE=America/Mexico_City
CORS_ALLOWED_ORIGINS=http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cloudi_go
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

En hosting de produccion ajusta:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tu-dominio.com
SHORT_URL_BASE=https://go.tu-dominio.com
FRONTEND_URL=https://panel.tu-dominio.com
CORS_ALLOWED_ORIGINS=https://panel.tu-dominio.com
```

## Ejecucion Local

```bash
php artisan serve
```

Health check:

```bash
curl http://127.0.0.1:8000/api/health
```

## Pruebas

```bash
php artisan test
vendor/bin/pint --dirty --test
```

## Rutas Principales

Autenticacion:

```txt
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

Enlaces:

```txt
GET    /api/links
POST   /api/links
GET    /api/links/{link}
PUT    /api/links/{link}
PATCH  /api/links/{link}
DELETE /api/links/{link}
PATCH  /api/links/{link}/status
GET    /api/links/{link}/analytics
```

Publico:

```txt
GET /{shortCode}
```

## Seguridad Implementada

- Laravel Sanctum con tokens Bearer
- Password hashing nativo de Laravel
- Policies por propiedad del enlace
- Form Requests
- Validacion estricta de URL destino
- Rechazo de hosts privados, localhost y metadata IP
- Soft deletes
- Hash HMAC de IP para clicks
- Rate limits para login, registro, API, creacion y redirecciones
- CORS configurable por entorno

## Hosting De Produccion

En hosting compatible con Laravel:

1. Sube el codigo del proyecto.
2. Configura el document root hacia `public/`.
3. Crea la base MySQL y variables `.env`.
4. Ejecuta:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si el hosting no permite ejecutar comandos SSH, ejecuta migraciones desde una terminal disponible del proveedor o despliega una base ya migrada. No subas `.env` con credenciales de desarrollo.

## Documentacion API

Contrato completo en [docs/api.md](docs/api.md).
