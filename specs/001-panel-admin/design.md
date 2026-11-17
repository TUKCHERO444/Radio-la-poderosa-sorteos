# 001 — Diseño técnico

- **Especificación:** `specs/001-panel-admin/requirements.md`
- **Estado:** implementado

## 1. Principios

- Sin framework ni Composer. PHP 8.0 compatible.
- `app/` nunca se expone; todo pasa por `public_html/index.php`.
- Una única entrada pública (`public_html/index.php`) despacha el sitio y el
  panel. Así funciona igual con Apache (`.htaccess`) y con el servidor PHP local
  (`tools/router.php`).
- Helpers pequeños y explícitos en lugar de abstracciones innecesarias.

## 2. Estructura de archivos

```text
app/
  bootstrap.php                     # config, helpers globales, PDO (lazy)
  inc/
    config.php                      # configuración base (producción)
    config.local.php                # override local (no se despliega)
    db.php                          # función db(): PDO singleton
  admin/
    helpers.php                     # e(), urls, flash
    auth.php                        # sesión, login, CSRF, throttle
    users_repository.php            # consultas de usuarios
    views.php                       # render de vistas y layout
    router.php                      # despacho de rutas /admin/*
    views/
      layout.php
      login.php
      dashboard.php
      users/index.php
      users/form.php
  database/
    schema.sql                      # tablas users y settings
public_html/
  index.php                         # despacha /admin/* al router del panel
  assets/admin/admin.css
  assets/admin/admin.js
tools/
  install.php                       # crea BD, aplica schema y siembra usuario
specs/
  001-panel-admin/...
```

## 3. Rutas

Todas bajo el prefijo `/admin`. Método entre paréntesis.

| Ruta | Acción | Acceso |
| --- | --- | --- |
| `/admin/login` (GET) | Formulario de login | Público |
| `/admin/login` (POST) | Validar credenciales | Público |
| `/admin/logout` (POST) | Cerrar sesión | Autenticado |
| `/admin` (GET) | Dashboard | Autenticado |
| `/admin/users` (GET) | Listado de usuarios | Autenticado |
| `/admin/users/create` (GET) | Formulario de alta | Autenticado |
| `/admin/users` (POST) | Guardar nuevo usuario | Autenticado |
| `/admin/users/{id}/edit` (GET) | Formulario de edición | Autenticado |
| `/admin/users/{id}` (POST) | Guardar cambios | Autenticado |
| `/admin/users/{id}/delete` (POST) | Eliminar usuario | Autenticado |

Cualquier otra ruta `/admin/*` responde `404` dentro del panel.

## 4. Modelo de datos

Base de datos MySQL `radiolpsorteos` (local).

```sql
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    name VARCHAR(120) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`settings` se conserva para la futura personalización de colores e imágenes.

Las tablas heredadas del export de Sitejet (`properties`, `jobs`, `modules`,
`pages`, `media`) se retiran del esquema porque no aplican al producto de
sorteos. Podrán reintroducirse con su propia especificación.

### Roles

- `admin`: acceso total (por ahora el único con capacidad de gestión).
- `editor`: reservado para fases posteriores; hoy puede iniciar sesión y ver el
  dashboard, pero no se usa para restringir acciones.

## 5. Seguridad

- **Contraseñas:** `password_hash($pw, PASSWORD_DEFAULT)` y `password_verify`.
  Rehash automático si cambia el algoritmo.
- **Sesión:** nombre `radiolp_admin`, `HttpOnly`, `SameSite=Lax`, `Secure` si
  HTTPS. `session_regenerate_id(true)` al autenticar y al cerrar.
- **CSRF:** token aleatorio de 32 bytes en sesión. Todos los `POST` lo validan
  con `hash_equals`. Sin token → `419`.
- **Throttle:** 5 fallos → bloqueo de 60 s, controlado en sesión.
- **Consultas:** PDO con `ATTR_EMULATE_PREPARES = false` y prepared statements.
- **XSS:** todo dato dinámico pasa por `e()` (`htmlspecialchars`).
- **Acceso:** `require_admin()` al inicio de cada ruta privada.
- **Errores:** en producción no se muestran detalles; en local
  `config.local.php` activa `debug`.

## 6. Configuración

- `app/inc/config.php`: valores base de producción.
- `app/inc/config.local.php`: override local (MySQL `root` sin contraseña, BD
  `radiolpsorteos`, `debug = true`). No debe subirse al hosting.
- `config()` fusiona ambos con `array_replace_recursive`.
- La conexión es **lazy**: `db()` abre PDO solo cuando el panel la usa. El sitio
  público sigue funcionando sin base de datos.

## 7. Interfaz

- Layout `app/admin/views/layout.php`: sidebar oscuro + topbar + contenido.
- Sidebar con navegación (Dashboard, Usuarios) y bloque de usuario + botón de
  cerrar sesión al pie.
- En pantallas ≤ 900 px el sidebar pasa a panel deslizante con overlay y botón
  hamburguesa.
- Login como tarjeta centrada sobre fondo degradado.
- Componentes CSS propios: botones, tarjetas, alertas, tablas, formularios.
- Iconografía SVG en línea; cero dependencias externas.

## 8. Instalación

`tools/install.php`:

1. Lee la configuración (con override local).
2. Intenta `CREATE DATABASE IF NOT EXISTS` (si no hay permiso, continúa).
3. Ejecuta `app/database/schema.sql`.
4. Si no existe `radiolp`, lo crea con `password_hash('admin123')`.
5. Reporta el resultado por consola.

## 9. Pruebas previstas

- `php -l` sobre todos los archivos PHP nuevos.
- Instalación y login correcto con `radiolp` / `admin123`.
- Login incorrecto y bloqueo tras 5 intentos.
- Acceso sin sesión a `/admin`, `/admin/users` → redirección a login.
- CRUD completo: crear, editar, eliminar, validaciones y protecciones.
- Cierre de sesión y posterior rechazo de rutas privadas.
- Revisión responsive a 360 px, 768 px y escritorio.

## 10. Riesgos y decisiones

- **Un solo front controller:** mantiene compatibilidad con el `.htaccess`
  existente y con el router de desarrollo.
- **Eliminación de tablas heredadas:** se documenta para evitar confusión; no
  hay datos productivos.
- **`fileinfo` no disponible en producción:** no se suben archivos en esta
  entrega; se resolverá con validación por extensión y `getimagesize`.
