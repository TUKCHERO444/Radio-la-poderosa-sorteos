# Radio La Poderosa - Documentación del proyecto

## 1. Resumen

- Estado actual: Fase 1 (sitio público) completada y probada localmente. Fase 2 (panel de administración) iniciada: login, sesiones, CRUD de usuarios y base del panel responsive.
- Carpeta oficial del proyecto: `C:\Users\USUARIO\Desktop\radiolpsorteos`
- Carpeta original de referencia: `C:\Users\USUARIO\Desktop\code` (no modificar; solo se usó como fuente del export de Sitejet).
- Stack: PHP 8.0, MySQL, Apache/cPanel. No se usa Laravel ni framework.
- Restricciones del hosting: PHP 8.0.30 (EOL), `fileinfo` no disponible, `curl` y `pdo_mysql` disponibles. Un solo usuario administrador (ampliable mediante el CRUD). Panel de administración en desarrollo.
- Entorno local usado para pruebas: PHP 8.2.12, Composer 2.10.3, MariaDB 10.4 local (XAMPP, usuario `root` sin clave), `pdo_mysql`, `pdo_sqlite` y `fileinfo` disponibles.

La documentación y el proyecto se encuentran en `C:\Users\USUARIO\Desktop\radiolpsorteos`. El trabajo debe continuar desde esa carpeta.

## 2. Estructura del proyecto

```text
radiolpsorteos/
  app/
    .htaccess
    bootstrap.php
    inc/
      config.php
    database/
      schema.sql
    storage/
      cache/
        .gitkeep
        covers/
          .gitkeep
      logs/
        .gitkeep
    templates/
      404.html
      pages/
        home.html
        subpage.html
        legal-notice.html
        privacy.html
      real-estate-single-page-layout/
        2-bedroom-apartment-in-chicago.html
        apartment-in-denver.html
        beautiful-house-in-anaheim.html
        executive-office-in-anaheim.html
        luxury-house-in-newton.html
        modern-house-in-denver.html
        office-space-in-chicago.html
        serviced-office-in-denver.html
        studio-loft-in-chicago.html
      jobs-1-item/
        1-accountant-tech.html
        2-developer-sales.html
        3-analyst-sales.html
        4-accountant-human-resources.html
        5-engineer-sales.html
        6-analyst-sales.html
      modules-1-item/
        coding.html
        data-science.html
        digital-marketing.html
        finances.html
        photography.html
        public-speaking.html
  public_html/
    .htaccess
    .well-known/
    api.php
    assets copiados:
      bundles/
      css/
      g/
      images/
      js/
      webcard/
    cover.php
    icons/
      apple-touch-icon.png
      icon-192.png
      icon-192-maskable.png
      icon-512.png
      icon-512-maskable.png
    index.php
    manifest.json
    radio-stats.php
    robots.txt
    sitemap.xml
    sw.js
    uploads/
      .htaccess
      .gitkeep
  tools/
    router.php
  dist/
    radiolpsorteos-app.zip
    radiolpsorteos-public_html.zip
  DOCUMENTACION.md
```

Archivos añadidos en la Fase 2:

```text
app/
  admin/
    auth.php                  # sesión, CSRF, login y throttle
    helpers.php               # escape, URLs y mensajes flash
    router.php                # rutas /admin/*
    users_repository.php      # consultas de usuarios
    views.php                 # render de vistas y layout
    views/
      layout.php
      login.php
      dashboard.php
      error.php
      users/
        index.php
        form.php
  inc/
    config.local.php          # override local (no desplegar)
    db.php                    # conexión PDO lazy
  database/
    schema.sql                # users + settings
public_html/
  assets/
    admin/
      admin.css
      admin.js
specs/
  README.md
  001-panel-admin/
    requirements.md
    design.md
    tasks.md
tools/
  install.php                 # crea BD, aplica esquema y siembra usuario
```

## 3. Rutas implementadas

El front controller es `public_html/index.php`.

| Ruta | Plantilla |
| --- | --- |
| `/` | `app/templates/pages/home.html` |
| `/subpage` | `app/templates/pages/subpage.html` |
| `/legal-notice` | `app/templates/pages/legal-notice.html` |
| `/privacy` | `app/templates/pages/privacy.html` |
| `/real-estate-single-page-layout/{slug}` | `app/templates/real-estate-single-page-layout/{slug}.html` |
| `/jobs-1-item/{slug}` | `app/templates/jobs-1-item/{slug}.html` |
| `/modules-1-item/{slug}` | `app/templates/modules-1-item/{slug}.html` |

Comportamiento:

- Las rutas con barra final también funcionan (`/subpage/`, `/real-estate-single-page-layout/.../`).
- `/index.html` redirige con `301` a `/`.
- Cualquier ruta inexistente devuelve `404` con `app/templates/404.html`.
- Los slugs solo aceptan `[a-z0-9-]`; no hay saltos de directorio.

### 3.1 Panel de administración (Fase 2)

El panel vive bajo `/admin` y se despacha desde el mismo front controller
(`public_html/index.php`), por lo que funciona tanto con Apache como con el
servidor PHP local.

| Ruta | Método | Acción |
| --- | --- | --- |
| `/admin/login` | GET | Formulario de acceso |
| `/admin/login` | POST | Validar credenciales |
| `/admin/logout` | POST | Cerrar sesión (CSRF) |
| `/admin` | GET | Dashboard |
| `/admin/users` | GET | Listado de usuarios |
| `/admin/users/create` | GET | Formulario de alta |
| `/admin/users` | POST | Guardar usuario |
| `/admin/users/{id}/edit` | GET | Formulario de edición |
| `/admin/users/{id}` | POST | Guardar cambios |
| `/admin/users/{id}/delete` | POST | Eliminar usuario |

Seguridad implementada:

- Contraseñas con `password_hash` / `password_verify` (bcrypt).
- Sesión `radiolp_admin` con cookie `HttpOnly`, `SameSite=Lax` y `Secure` en HTTPS.
- Regeneración de ID de sesión al iniciar y al cerrar.
- Token CSRF en todos los formularios `POST`.
- Bloqueo de login durante 60 s tras 5 intentos fallidos.
- Consultas preparadas con PDO y escape de toda salida HTML.
- Protecciones: no eliminar el propio usuario ni el último administrador, y no
  quitar el rol admin al último administrador.

El diseño es responsive: sidebar deslizante con overlay en móvil, tablas que se
convierten en tarjetas y formularios de una columna en pantallas pequeñas.

Las especificaciones viven en `specs/` y siguen Spec Driven Development:

- `specs/001-panel-admin/requirements.md`
- `specs/001-panel-admin/design.md`
- `specs/001-panel-admin/tasks.md`

## 4. Plantillas y contenido

- Se generaron 25 plantillas desde el export de Sitejet con `tools/build_templates.php`.
- Se conservaron las URLs y el diseño original.
- Se eliminaron de las plantillas los restos de la colección CMS de Sitejet:
  - `collection-loading`
  - `opacity:0`
  - `data-collection-bind`
- Verificación: 0 ocurrencias de esos elementos en las plantillas.
- El contenido de los 21 ítems es estático de muestra:
  - 9 inmuebles.
  - 6 empleos.
  - 6 módulos.
- Se agregaron enlaces de PWA en todas las plantillas:
  - `manifest.json`
  - `theme-color`
  - `apple-touch-icon`
- La página de inicio conserva además el enlace de manifest que ya existía en el cuerpo del HTML original.

## 5. Reproductor de radio

Archivo principal: `app/templates/pages/home.html`.

Cambios realizados:

- Se eliminó la constante `statsTarget`.
- `updateMetadata()` ahora consume `GET /radio-stats.php?t=...` del mismo origen.
- `fetchCoverArt()` ahora consume `GET /cover.php?q=...` del mismo origen.
- Se eliminaron las llamadas a `api.allorigins.win`, `corsproxy.io` y la búsqueda directa a iTunes desde el navegador.
- El stream sigue usando la URL original:
  `https://cast2.my-control-panel.com/proxy/radiolax/stream`
- El logo por defecto sigue siendo el recurso externo de postimg usado por el sitio original.

### `radio-stats.php`

- Hace proxy con cURL al endpoint de estadísticas del radio.
- Cachea la respuesta válida durante 5 segundos en `app/storage/cache/radio-stats.json`.
- Si el origen falla y existe caché, devuelve la caché anterior (`X-Cache: STALE`).
- Si no hay caché válida, responde `502` con `{"error":"stats_unavailable"}`.
- Incluye fallback a `file_get_contents` si cURL no está disponible.
- Responde como `application/json; charset=utf-8`.

### `cover.php`

- Busca carátulas en iTunes mediante cURL.
- Cachea por 24 horas en `app/storage/cache/covers/` usando SHA-256 de la consulta.
- Devuelve `{"cover":"..."}` con la imagen en 600x600 cuando existe.
- Si no hay resultado, devuelve `{"cover":null}` y cachea el resultado válido de "sin carátula".
- Si falla la red, no cachea el fallo; responde `{"cover":null}`.
- Incluye fallback a `file_get_contents` si cURL no está disponible.

## 6. PWA

- `public_html/manifest.json`:
  - Nombre: Radio La Poderosa.
  - `start_url` y `scope`: `/`.
  - `display`: `standalone`.
  - Iconos 192 y 512, versión normal y `maskable`.
- `public_html/sw.js`:
  - Cachea el shell y assets principales.
  - Estrategia network-first para navegación, con fallback a `/`.
  - Cache-first para assets estáticos.
  - Excluye `/radio-stats.php`, `/cover.php` y `/admin`.
  - Versión de caché actual: `radio-poderosa-v1`.
- Registro del service worker en `home.html` solo en HTTPS o `localhost`.
- Iconos generados con `System.Drawing` a partir del logo del sitio:
  - `icon-192.png`, `icon-512.png`.
  - `icon-192-maskable.png`, `icon-512-maskable.png`.
  - `apple-touch-icon.png` (180x180).
- La PWA requiere HTTPS en producción.

## 7. Seguridad

- `app/bootstrap.php` envía:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- `public_html/.htaccess`:
  - `Options -Indexes`
  - `DirectoryIndex index.php`
  - Rewrite de rutas limpias a `index.php`.
  - Deniega archivos `.sql`, `.log`, `.ini`, `.sh`.
- `app/.htaccess` y `tools/.htaccess`: deniegan todo acceso directo.
- `public_html/uploads/.htaccess`: deniega ejecución de PHP dentro de uploads.
- `public_html/api.php`: stub que responde `404` en JSON. Sirve para sobrescribir el antiguo `api.php` de Sitejet si queda en el hosting.
- `app/inc/config.php` queda fuera de `public_html`; no hay credenciales en el directorio público.

## 8. Base de datos (Fase 2)

Archivo: `app/database/schema.sql`.

Tablas:

- `users`: cuentas del panel (`id`, `username`, `name`, `password_hash`,
  `role`, `last_login_at`, `created_at`, `updated_at`).
- `settings`: clave/valor para la futura personalización de colores e imágenes.

Las tablas heredadas del export de Sitejet (`pages`, `properties`, `jobs`,
`modules`, `media`) se retiraron porque no aplican al producto de sorteos. Podrán
reintroducirse con su propia especificación.

Instalación local:

```powershell
php tools/install.php
```

El instalador crea la base de datos `radiolpsorteos` si no existe, aplica el
esquema y crea el usuario `radiolp` con contraseña `admin123` (guardada con
bcrypt). Es idempotente: si el usuario ya existe, no lo modifica.

Configuración:

- `app/inc/config.php`: valores base de producción.
- `app/inc/config.local.php`: override local (`127.0.0.1`, BD
  `radiolpsorteos`, usuario `root` sin clave, `debug = true`). **No subir este
  archivo al hosting.**
- `config()` fusiona ambos archivos con `array_replace_recursive`.
- La conexión PDO es lazy (`app/inc/db.php`), así que el sitio público no
  depende de MySQL.

Notas:

- El esquema usa `utf8mb4` y `InnoDB`.
- Para subidas de archivos se debe usar `getimagesize` y lista blanca de
  extensiones, porque `fileinfo` no está disponible en el hosting.
- No se deben generar miniaturas dependientes de GD si no está garantizado.

## 9. Pruebas realizadas

Comandos usados:

```powershell
php -l <archivo.php>
php -S localhost:8080 -t public_html tools/router.php
```

Resultados:

- 7 archivos PHP verificados sin errores de sintaxis.
- 25 rutas probadas: 25 con `200`, 0 fallos.
- Ruta inexistente: `404`.
- `/index.html`: `301` a `/`.
- Rutas con barra final: `200`.
- 22 referencias locales de HTML/CSS probadas: 0 faltantes.
- 25 URLs locales dentro de CSS verificadas: 0 faltantes.
- `/radio-stats.php`: `200` con JSON real del radio.
- `/cover.php?q=bad%20bunny`: `200` con carátula real de iTunes.
- `manifest.json`: JSON válido con 4 iconos.
- No quedan referencias a `statsTarget`, `allorigins` ni `corsproxy` en las plantillas.

Pruebas de la Fase 2 (panel de administración):

- `php -l` sin errores en los 16 archivos PHP nuevos o modificados.
- `php tools/install.php`: base de datos, esquema y usuario `radiolp` creados.
- Login correcto con `radiolp` / `admin123`; `last_login_at` actualizado.
- Login sin sesión en `/admin` y `/admin/users` redirige a `/admin/login`.
- CRUD completo verificado: crear, duplicado rechazado, contraseña corta
  rechazada, editar (nombre y rol) y eliminar.
- Protección verificada: no se puede eliminar el propio usuario.
- Logout con CSRF destruye la sesión; las rutas privadas vuelven a redirigir.
- Bloqueo tras 5 intentos fallidos (incluso con la clave correcta).
- CSRF inválido responde `419`; `GET /admin/logout` responde `405`;
  `/admin/noexiste` responde `404` con el layout del panel.
- Assets `/assets/admin/admin.css` y `/assets/admin/admin.js` responden `200`.
- Botón de acceso visible en la portada (`#adminAccessBtn` apunta a `/admin`).
- Sitio público y `/radio-stats.php` siguen respondiendo `200`.

## 10. Despliegue en cPanel

Zips generados en `dist/`:

- `C:\Users\USUARIO\Desktop\radiolpsorteos\dist\radiolpsorteos-app.zip` (0.12 MB)
- `C:\Users\USUARIO\Desktop\radiolpsorteos\dist\radiolpsorteos-public_html.zip` (40.08 MB)

Procedimiento recomendado:

1. Hacer respaldo de `public_html` y de la base de datos actual.
2. Subir `radiolpsorteos-app.zip` a `/home/USER/` y extraerlo. Debe quedar `/home/USER/app`.
3. Subir `radiolpsorteos-public_html.zip` dentro de `/home/USER/public_html/` y extraerlo. El zip no incluye una carpeta contenedora; los archivos quedan directamente en `public_html`.
4. Activar "Show Hidden Files" en cPanel y verificar que exista `public_html/.htaccess`.
5. Dar permisos `775` a:
   - `app/storage`
   - `app/storage/cache`
   - `app/storage/cache/covers`
   - `app/storage/logs`
6. Verificar que el hosting use PHP 8.0 y que `curl` esté habilitado. `mbstring` es opcional; el código tiene protecciones.
7. Eliminar o mover los archivos antiguos de Sitejet que no formen parte de este proyecto. El zip sobrescribe `index.php`, `.htaccess`, `robots.txt`, `sitemap.xml` y `api.php`, pero conviene limpiar el resto.
8. Probar:
   - `/`
   - `/subpage`
   - `/legal-notice`
   - `/privacy`
   - Un inmueble, un empleo y un módulo.
   - `/radio-stats.php`
   - `/cover.php?q=prueba`
   - Instalación PWA en un navegador con HTTPS.
9. Importar `app/database/schema.sql` en la base de datos de producción (o
   ejecutar `php tools/install.php` si el hosting permite CLI) y configurar las
   credenciales reales en `app/inc/config.php`.
10. Abrir `/admin`, iniciar sesión y **cambiar la contraseña de `radiolp`**.
11. Verificar que `.htaccess` esté activo y que `/admin` no sea accesible sin
    sesión.

Notas de despliegue:

- Si el dominio usa otro document root, ajustar la ubicación de `app` para que quede como hermano de `public_html`.
- Si se sube todo dentro de `public_html`, los `require` fallarán y además `app` quedaría expuesto; no hacerlo.
- Si se cambia el contenido de `public_html` o `sw.js`, subir la versión de `CACHE_NAME` para invalidar la caché PWA.
- Los zips se generaron antes de las últimas pruebas de caché; no contienen archivos de caché temporales.
- **No subir `app/inc/config.local.php`**: es solo para desarrollo local.
- En producción, `app/inc/config.php` debe contener el nombre y usuario de la
  base de datos de cPanel, no los valores de ejemplo.
- Si el hosting no ofrece CLI, importar `schema.sql` desde phpMyAdmin y crear el
  usuario inicial con un hash de `password_hash`.

## 11. Pendientes y siguientes pasos

1. Desplegar en producción y verificar el sitio con el dominio real.
2. Confirmar permisos de escritura en `app/storage`.
3. Cambiar la contraseña del usuario `radiolp` en producción.
4. Implementar el CRUD de sorteos (siguiente especificación).
5. Implementar el CRUD de productos de venta.
6. Implementar la gestión de imágenes (logo, portada, fotos).
7. Implementar la personalización de colores del sitio.
8. Añadir recuperación de contraseña y registro de auditoría.
9. Opcional: reemplazar el logo externo por un recurso local para mejorar la PWA offline.
10. Opcional: agregar respaldo automático de la base de datos.

## 12. Archivos clave

| Archivo | Función |
| --- | --- |
| `app/bootstrap.php` | Configuración, helpers, render, 404, cabeceras de seguridad. |
| `app/inc/config.php` | Configuración de app, radio y base de datos. |
| `app/inc/config.local.php` | Override local de desarrollo (no desplegar). |
| `app/inc/db.php` | Conexión PDO lazy. |
| `app/admin/router.php` | Rutas del panel `/admin/*`. |
| `app/admin/auth.php` | Sesión, CSRF, login, logout y throttle. |
| `app/admin/users_repository.php` | Consultas de usuarios. |
| `app/admin/views/` | Layout, login, dashboard y vistas de usuarios. |
| `app/database/schema.sql` | Esquema MySQL (`users`, `settings`). |
| `app/templates/pages/home.html` | Página de inicio, reproductor y botón `/admin`. |
| `app/templates/404.html` | Página de error. |
| `public_html/index.php` | Front controller (sitio público y panel). |
| `public_html/assets/admin/` | CSS y JS del panel. |
| `public_html/radio-stats.php` | Proxy de estadísticas del radio. |
| `public_html/cover.php` | Proxy de carátulas de iTunes. |
| `public_html/.htaccess` | Rewrite, índices y seguridad. |
| `public_html/manifest.json` | Manifiesto PWA. |
| `public_html/sw.js` | Service worker (excluye `/admin`). |
| `tools/router.php` | Router para servidor PHP local. |
| `tools/install.php` | Crea BD, aplica esquema y siembra usuario. |
| `tools/build_templates.php` | Generador local de plantillas desde Sitejet. |
| `specs/` | Especificaciones SDD del proyecto. |
| `dist/` | Zips de despliegue para cPanel/FTP. |
| `DOCUMENTACION.md` | Este documento. |

## 13. Credenciales y accesos

- No se almacenaron credenciales de cPanel, FTP ni base de datos de producción
  en el proyecto.
- **Usuario inicial del panel (solo desarrollo):** `radiolp` / `admin123`.
  Cambiar la contraseña tras el primer acceso en producción.
- La contraseña se guarda hasheada con bcrypt; no existe en texto plano en la
  base de datos.
- Las credenciales locales de MySQL están en `app/inc/config.local.php` y no
  deben subirse al hosting.
- El despliegue en producción está pendiente de acceso a cPanel/FTP o de que el usuario suba los zips.
- Para continuar, trabajar desde `C:\Users\USUARIO\Desktop\radiolpsorteos`.
