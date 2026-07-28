# Cloudi Go API

Base URL local:

```txt
http://127.0.0.1:8000/api
```

Headers recomendados:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

## Formato De Respuesta

Exito:

```json
{
  "success": true,
  "message": "Operacion realizada correctamente.",
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "message": "No fue posible completar la operacion."
}
```

Validacion:

```json
{
  "success": false,
  "message": "Los datos proporcionados no son validos.",
  "errors": {
    "field": ["Mensaje de validacion."]
  }
}
```

## Health

### GET `/api/health`

Respuesta:

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "application": "Cloudi Go",
    "database": "connected",
    "timestamp": "2026-07-28T14:00:00-06:00"
  }
}
```

## Autenticacion

### POST `/api/auth/register`

Payload:

```json
{
  "name": "Alejandro Leon",
  "email": "alejandro@example.com",
  "password": "password-seguro",
  "password_confirmation": "password-seguro",
  "device_name": "Cloudi Go Web"
}
```

Respuesta `201`:

```json
{
  "success": true,
  "message": "Registro exitoso.",
  "data": {
    "token": "TOKEN",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Alejandro Leon",
      "email": "alejandro@example.com",
      "email_verified_at": null,
      "created_at": "2026-07-28T14:00:00-06:00"
    }
  }
}
```

### POST `/api/auth/login`

Payload:

```json
{
  "email": "alejandro@example.com",
  "password": "password-seguro",
  "device_name": "Cloudi Go Web"
}
```

Respuesta `200`:

```json
{
  "success": true,
  "message": "Inicio de sesion exitoso.",
  "data": {
    "token": "TOKEN",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Alejandro Leon",
      "email": "alejandro@example.com",
      "email_verified_at": null,
      "created_at": "2026-07-28T14:00:00-06:00"
    }
  }
}
```

### GET `/api/auth/me`

Requiere token.

Respuesta:

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Alejandro Leon",
      "email": "alejandro@example.com",
      "email_verified_at": null,
      "created_at": "2026-07-28T14:00:00-06:00"
    }
  }
}
```

### POST `/api/auth/logout`

Requiere token.

Respuesta:

```json
{
  "success": true,
  "message": "Sesion cerrada correctamente."
}
```

## Enlaces

Modelo:

```json
{
  "id": 1,
  "name": "Demo CloudiShop",
  "original_url": "https://cloudishop.mx/demo",
  "short_code": "demo",
  "short_url": "http://localhost:8000/demo",
  "is_custom_alias": true,
  "status": "active",
  "clicks_count": 0,
  "expires_at": null,
  "last_clicked_at": null,
  "created_at": "2026-07-28T14:00:00-06:00",
  "updated_at": "2026-07-28T14:00:00-06:00"
}
```

### GET `/api/links`

Requiere token.

Query params:

```txt
search
status=active|inactive
sort=created_at|updated_at|name|clicks_count|last_clicked_at
direction=asc|desc
per_page=1..100
page
```

Respuesta:

```json
{
  "success": true,
  "data": {
    "links": [],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 1,
      "total": 1
    }
  }
}
```

### POST `/api/links`

Requiere token.

Payload:

```json
{
  "name": "Demo CloudiShop",
  "original_url": "https://cloudishop.mx/demo",
  "custom_alias": "demo",
  "expires_at": null,
  "status": "active"
}
```

Notas:

- `custom_alias` es opcional.
- Si no se envia, el backend genera un codigo de 7 caracteres.
- `original_url` solo acepta `http` o `https`.
- Se rechazan `localhost`, IPs privadas, IPs reservadas y `169.254.169.254`.

### GET `/api/links/{link}`

Requiere token y propiedad del enlace.

### PUT/PATCH `/api/links/{link}`

Requiere token y propiedad del enlace.

Payload:

```json
{
  "name": "Nuevo nombre",
  "original_url": "https://cloudishop.mx/nuevo",
  "expires_at": null,
  "status": "active"
}
```

El `short_code` no cambia al editar.

### PATCH `/api/links/{link}/status`

Payload:

```json
{
  "status": "inactive"
}
```

### DELETE `/api/links/{link}`

Soft delete del enlace.

Respuesta:

```json
{
  "success": true,
  "message": "Enlace eliminado correctamente."
}
```

## Analitica

### GET `/api/links/{link}/analytics`

Requiere token y propiedad del enlace.

Query params opcionales:

```txt
from=2026-07-01
to=2026-07-28
```

Respuesta:

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_clicks": 1420,
      "today_clicks": 34,
      "last_7_days_clicks": 280,
      "last_30_days_clicks": 934
    },
    "clicks_by_day": [
      {
        "date": "2026-07-27",
        "clicks": 42
      }
    ],
    "top_referrers": [
      {
        "referrer": "instagram.com",
        "clicks": 300
      }
    ],
    "top_devices": [
      {
        "device": "mobile",
        "clicks": 980
      }
    ],
    "top_browsers": [
      {
        "browser": "Chrome",
        "clicks": 700
      }
    ],
    "top_operating_systems": [
      {
        "operating_system": "iOS",
        "clicks": 410
      }
    ]
  }
}
```

## Redireccion Publica

### GET `/{shortCode}`

No usa prefijo `/api` y no requiere token.

Estados:

```txt
302: redireccion correcta
403: enlace inactivo
404: codigo inexistente
410: enlace expirado
```

Cada redireccion valida registra un click, incrementa `clicks_count` y actualiza `last_clicked_at`.

## Codigos De Error Comunes

```txt
401: No autenticado
403: Sin permiso
422: Error de validacion
429: Rate limit
```

## Ejemplos Curl

Login:

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@cloudi.local","password":"password","device_name":"Cloudi Go Web"}'
```

Crear enlace:

```bash
curl -X POST http://127.0.0.1:8000/api/links \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"name":"Demo","original_url":"https://cloudishop.mx/demo","custom_alias":"demo"}'
```

Analytics:

```bash
curl http://127.0.0.1:8000/api/links/1/analytics \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"
```
