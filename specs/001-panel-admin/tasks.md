# 001 — Lista de tareas

Convención: `[ ]` pendiente, `[x]` completada. Cada tarea indica el requisito
que cubre.

## Preparación

- [x] T01 — Crear documentación SDD (`specs/README.md`, requisitos, diseño,
  tareas). _Transversal._
- [x] T02 — Definir esquema `users` + `settings` y retirar tablas heredadas.
  _Modelo de datos._
- [x] T03 — Crear `tools/install.php` (BD, schema y seed idempotente de
  `radiolp`). _HU-08._

## Núcleo del panel

- [x] T04 — Soporte de `config.local.php` y conexión PDO lazy (`app/inc/db.php`).
  _NFR compatibilidad._
- [x] T05 — Helpers de sesión, CSRF, flash, escape y URLs (`app/admin/…`).
  _NFR seguridad._
- [x] T06 — Repositorio de usuarios (listar, buscar, crear, actualizar,
  eliminar, contar). _HU-04…HU-07._
- [x] T07 — Router `/admin/*` con protección de rutas y `404`. _HU-02, HU-03._
- [x] T08 — Login y logout con throttle y regeneración de sesión.
  _HU-02, HU-03._
- [x] T09 — Layout responsive, login, dashboard y vistas de usuarios.
  _HU-01…HU-07._
- [x] T10 — Estilos profesionales (`admin.css`) e interacción (`admin.js`).
  _NFR diseño/responsividad._

## Integración y cierre

- [x] T11 — Botón de acceso al panel en la página pública. _HU-01._
- [x] T12 — Crear la base de datos local y ejecutar la instalación.
  _HU-08._
- [x] T13 — Verificación: sintaxis PHP y pruebas de flujo (login, CRUD,
  protecciones, logout, responsive). _NFR pruebas._
- [x] T14 — Actualizar `DOCUMENTACION.md` con la Fase 2. _Transversal._

## Pendiente para próximas especificaciones

- [ ] CRUD de sorteos.
- [ ] CRUD de productos.
- [ ] Gestión de imágenes (logo, portada, favoritos).
- [ ] Personalización de colores del sitio.
- [ ] Recuperación de contraseña.
- [ ] Registro de auditoría.
