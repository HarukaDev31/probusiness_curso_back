# cursoback con Docker (PHP 8.5.12)

El servicio corre en contenedores con PHP 8.5.12 y Laravel 13. No hace falta instalar PHP 8.5 en el servidor.

## Qué levanta `docker-compose.yml`

| Servicio | Qué hace | Puerto (solo 127.0.0.1) |
|---|---|---|
| `app` | php-fpm 8.5.12 con el código | `DOCKER_FPM_PORT` (9384) |
| `web` | nginx que sirve `public/` y pasa PHP a `app` | `DOCKER_HTTP_PORT` (8384) |

- Ambos usan `network_mode: host`, así que la app se conecta a la **MySQL del propio servidor** con las credenciales
  del `.env` de la carpeta donde se hace el pull (no se levanta MySQL en Docker). `DB_HOST=127.0.0.1` sigue funcionando.
- El proyecto no usa colas ni scheduler (`QUEUE_CONNECTION=sync`, sin tareas programadas), por eso no hay contenedores de eso.
- `storage/` se monta desde la carpeta del servidor (logs y archivos se conservan).
- Los puertos se pueden cambiar poniendo `DOCKER_FPM_PORT` o `DOCKER_HTTP_PORT` en el `.env`.

## Despliegue

```bash
cd /var/www/html/cursoback   # carpeta actual del servicio, con su .env
git pull
docker compose up -d --build
docker compose ps
docker compose logs -f app
```

Luego, en el nginx del servidor, el sitio de este servicio pasa de `fastcgi_pass` a php 7.4 a un proxy al contenedor:

```nginx
location / {
    proxy_pass http://127.0.0.1:8384;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    client_max_body_size 50M;
}
```

## Notas

- Comandos artisan: `docker compose exec app php artisan <comando>`.
- Tras cambiar código: `docker compose up -d --build`.
