# Especificaciones — Radio La Poderosa

Este directorio aplica **Spec Driven Development (SDD)** al proyecto. Ninguna
funcionalidad se implementa sin una especificación aprobada en `specs/`.

## Flujo de trabajo

1. **Requisitos** (`requirements.md`): qué se necesita y por qué. Se escriben en
   lenguaje de negocio, con historias de usuario y criterios de aceptación.
2. **Diseño técnico** (`design.md`): cómo se resuelve. Arquitectura, rutas,
   modelo de datos, seguridad, UI y estrategia de pruebas.
3. **Tareas** (`tasks.md`): desglose ejecutable y trazable. Cada tarea referencia
   los requisitos que satisface.
4. **Implementación**: solo se codifica lo que está en la especificación.
5. **Verificación**: se actualiza `tasks.md` y `DOCUMENTACION.md` con el
   resultado de las pruebas.

Si durante la implementación aparece una necesidad no prevista, primero se
actualiza la especificación y luego el código.

## Convenciones del proyecto

- PHP 8.0+ sin framework ni Composer (restricción del hosting).
- MySQL/MariaDB con `PDO` y consultas preparadas. Nunca SQL concatenado con
  entrada de usuario.
- Contraseñas con `password_hash` / `password_verify` (bcrypt).
- Sesiones con cookie `HttpOnly`, `SameSite=Lax` y `Secure` cuando hay HTTPS.
- Formularios con token CSRF y validación en servidor.
- Salida HTML siempre escapada con `htmlspecialchars`.
- `app/` es privado; solo `public_html/` es público.
- CSS y JS del panel en `public_html/assets/admin/`.
- Idioma de interfaz y documentación: español.
- Fechas y horas en zona `America/Lima`.

## Índice de especificaciones

| ID | Funcionalidad | Estado |
| --- | --- | --- |
| 001 | Panel de administración: acceso, login y CRUD de usuarios | Implementada (base) |
| 002 | UI kit nativo del panel (tablas, formularios, botones, tema) | Implementada |
| 003 | Sorteos: análisis de módulos, viabilidad y relación con la web pública | Documentado (sin implementar) |
| 004 | MVP de sorteos: decisiones de alcance, pagos manuales y operación | Documentado (sin implementar) |
| 005 | MVP de sorteos: plan por fases, tareas y puertas de control | Documentado (sin implementar) |
| 006 | MVP de sorteos: arquitectura modular por dominio, sin MVC rígido | Documentado (sin implementar) |
| 007 | MVP de sorteos: especificación funcional, modelo de datos y criterios de aceptación | Aprobada (sin implementar) |
| 008 | Fase 0: decisiones, legal, pagos e infraestructura | En ejecución |
| 009 | Fase 1: esquema inicial, migrador y seeder demo | En ejecución |

## Registro de cambios

- `specs/CHANGELOG.md`: historial de cambios de documentación y código.

## Contexto de producto

El sitio nació como una página de radio (`live.radiolapoderosatv.com`) pero su
objetivo final es ser una **plataforma de sorteos**. El panel de administración
se diseñará alrededor de ese objetivo. En fases posteriores gestionará sorteos,
productos de venta, imágenes y colores del sitio. Para no bloquear el arranque,
la Fase 2 empieza por lo mínimo imprescindible: autenticación y usuarios.
