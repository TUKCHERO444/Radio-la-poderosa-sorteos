# 006 — Arquitectura del MVP: monolito modular por dominio

- **Estado:** aprobado. **Sin implementación.**
- **Fecha:** 2026-11-16
- **Base:** `specs/004-mvp-sorteos/decisiones.md` y `specs/005-mvp-sorteos/plan-fases.md`
- **Decisión:** no adoptar un MVC rígido. Usar un **monolito modular por dominio** sobre el stack actual, con **MySQL/MariaDB** y PDO.
- **Convenciones:** ver `specs/006-mvp-sorteos/convenciones.md`.
- **Especificación funcional:** ver `specs/007-mvp-sorteos/especificacion.md`.
- **Restricción:** no se modifica código en esta fase.

---

## 1. Contexto

El proyecto no usa framework ni Composer en producción. Ya existe un front controller (`public_html/index.php`), un panel administrativo con router propio, PDO, sesiones, CSRF y un UI kit nativo. El MVP de sorteos agregará varios dominios: sorteos, participantes, pagos manuales, tickets, sorteo, transparencia y entregas.

Un MVC clásico (`models/`, `controllers/`, `views/` para todo) no encaja bien porque:

- No hay framework que imponga ese patrón.
- “Modelo” suele mezclar entidades, SQL y reglas de negocio.
- Duplicaría capas para un equipo pequeño.
- El panel actual ya está organizado por rutas y vistas, no por MVC.
- Los dominios del sorteo tienen reglas transaccionales y estados que se entienden mejor como servicios de dominio.

La decisión es mantener una arquitectura sencilla, explícita y modular, sin ceremonia innecesaria.

---

## 2. Decisión

Adoptar un **monolito modular por dominio**:

```text
app/
  admin/                  # entrega administrativa existente
  web/                    # nueva entrega pública de sorteos (App\Web)
  modules/                # reglas de negocio por dominio
    raffles/
    prizes/
    payment_methods/
    participants/
    payments/
    draws/
    deliveries/
    support/
  shared/                 # infraestructura compartida
  database/migrations/    # SQL versionado
  views/                  # layouts y parciales compartidos
public_html/
  index.php               # front controller
  assets/admin/           # UI kit actual
  assets/web/             # estilos públicos nuevos
tools/
  migrate.php
  worker.php
  tests/
```

Cada módulo de dominio contiene clases con nombres explícitos:

- `RaffleService.php`: reglas de negocio, validaciones, transacciones y estados.
- `RaffleRepository.php`: SQL/PDO, consultas y persistencia.
- `PublicRaffleHandler.php` / `AdminRaffleHandler.php`: puntos de entrada HTTP finos.
- `RaffleStatus.php`: constantes de estados.
- `views/`: vistas propias del módulo, si no son compartidas.
- `routes.php`: registro de rutas del módulo, cuando aplique.

No se crea una carpeta `models/` genérica. No se usa ORM. No se usa Active Record. No se crea un contenedor de servicios.

---

## 3. Principios

1. **Handlers finos:** leen la petición, validan lo básico, llaman al servicio y renderizan/redirigen.
2. **Servicios con las reglas:** estados, límites, reservas, aprobaciones, sorteo, entrega.
3. **Repositorios con SQL:** PDO, prepared statements, transacciones, sin HTML ni reglas de negocio.
4. **Vistas sin SQL:** solo presentan datos escapados y reciben arrays o DTOs simples.
5. **Dominio sin HTML:** los servicios no saben si se ejecutan desde el panel o desde la web pública.
6. **Una sola fuente de verdad:** admin y público llaman al mismo servicio; no se duplican reglas.
7. **Transacciones explícitas:** reservas, pagos, tickets y sorteo se ejecutan en transacción.
8. **Migraciones SQL:** esquema versionado, sin cambios manuales en producción.
9. **Sin dependencias nuevas:** PDO, cURL, funciones nativas; si algo requiere un paquete, se evalúa.
10. **Incremental:** se reutiliza el panel actual y se agregan módulos sin reescribir todo.

---

## 4. Flujo de una petición

```text
public_html/index.php
  → determina ruta pública o /admin
  → carga configuración y PDO lazy
  → despacha al router correspondiente
  → handler del módulo
  → valida CSRF/permisos/datos
  → llama al servicio de dominio
  → servicio usa repositorio(s) y transacción
  → devuelve resultado/errores
  → handler renderiza vista o redirige
  → layout + vista
```

### Ejemplo conceptual: reservar tickets

```text
POST /sorteos/{slug}/participar
→ PublicRaffleHandler::reserve()
→ RaffleService::getPublishedBySlug()
→ ParticipantService::findOrCreate()
→ TicketService::reserve()
    → RaffleRepository::lockRaffle()
    → TicketRepository::allocateNumbers()
    → OrderRepository::create()
    → AuditService::record()
→ render confirmación + instrucciones de pago
```

No hay “controller → model → view” genérico; hay un handler de caso de uso que coordina servicios.

---

## 5. Responsabilidades por capa

### 5.1 Handlers

- Reciben `$_GET`, `$_POST`, sesión y CSRF.
- Validan permisos del panel o acceso público.
- Normalizan datos de entrada.
- Llaman a un método de servicio.
- Manejan errores de validación y flash messages.
- Renderizan una vista o redirigen.
- **No** contienen SQL ni reglas de negocio complejas.

### 5.2 Servicios

- Implementan casos de uso: crear sorteo, publicar, reservar, aprobar pago, emitir ticket, sortear, entregar.
- Aplican reglas: límites, estados, fechas, montos, cupos, unicidad.
- Abren y cierran transacciones.
- Llaman a repositorios y a otros servicios cuando corresponde.
- Registran auditoría.
- Devuelven resultados estructurados (`['ok' => true, 'data' => ...]` o excepciones de dominio).
- **No** generan HTML ni leen `$_POST` directamente.

### 5.3 Repositorios

- Encapsulan SQL y PDO.
- Usan prepared statements.
- Devuelven arrays o DTOs simples.
- Implementan bloqueos (`SELECT ... FOR UPDATE`), inserciones únicas y consultas de listado.
- No validan reglas de negocio.
- No renderizan.

### 5.4 Vistas

- PHP/HTML con escape (`e()`).
- Reciben datos ya preparados.
- No ejecutan SQL.
- No contienen lógica de negocio.
- Reutilizan layouts y parciales.

### 5.5 Shared

- `db.php`: PDO singleton.
- `http.php`: request/response, redirect, JSON, flash.
- `auth.php`: sesión admin, CSRF, throttle.
- `audit.php`: registro append-only.
- `storage.php`: archivos privados.
- `validation.php`: reglas comunes (DNI, teléfono, email, montos, fechas).
- `money.php`: centavos/formato PEN.
- `ids.php`: códigos públicos.
- `view.php`: render de layouts.

---

## 6. Estructura propuesta y migración desde el código actual

### 6.1 Estado actual

- `app/admin/router.php`: rutas del panel.
- `app/admin/views/`: vistas del panel.
- `app/admin/helpers.php`: helpers.
- `app/admin/auth.php`: sesión/CSRF.
- `app/admin/users_repository.php`: SQL de usuarios.
- `app/admin/views.php`: render.
- `app/database/schema.sql`: esquema.
- `public_html/index.php`: front controller.

### 6.2 Evolución recomendada

No se reescribe el panel. Se agrega:

```text
app/
  web/
    router.php
    views/
  modules/
    raffles/
      RaffleService.php
      RaffleRepository.php
      PublicRaffleHandler.php
      AdminRaffleHandler.php
      views/            # opcional
    participants/
    payments/
    draws/
    deliveries/
  shared/
    audit/
      AuditService.php
    storage/
      PrivateStorage.php
    validation/
      Validator.php
    http/
      Request.php
      Response.php
    Money.php
    Dates.php
    Ids.php
  database/migrations/
```

- `app/admin/router.php` sigue siendo el router del panel.
- Las rutas nuevas del panel se delegan a handlers de módulos.
- `app/admin/views.php` puede reutilizarse o adaptarse para layouts.
- `app/admin/helpers.php` se mantiene; los helpers nuevos van a `app/shared/`.
- `users` sigue siendo la tabla de administradores.
- `participants` es una tabla nueva y separada.

### 6.3 Regla de no duplicación

Si una regla se necesita en admin y público, vive en el servicio. Ejemplo: el límite de tickets por persona se valida en `TicketService`, no en el formulario público ni en el panel.

---

## 7. Base de datos

### 7.1 Motor

- **MySQL/MariaDB** es el motor del MVP. No se cambia de motor.
- Se usa PDO con prepared statements.
- Se evita `FLOAT` para dinero; se usan centavos enteros.
- Fechas en `America/Lima`; si una integración futura usa UTC, se convierte en el borde.
- `utf8mb4` y `InnoDB`.
- Foreign keys y unique constraints como parte del diseño.

### 7.2 Migraciones

- `app/database/migrations/` con archivos SQL numerados.
- `tools/migrate.php` aplica pendientes y registra en `schema_migrations`.
- No se edita una migración ya aplicada; se crea una nueva.
- Backup previo y rollback documentado.

### 7.3 SQL y portabilidad

Aunque el MVP usa MySQL, se aplican estas reglas para no acoplarse de más:

- SQL estándar cuando sea posible.
- Funciones específicas (`SELECT ... FOR UPDATE`, `JSON`, `ON DUPLICATE KEY`) encapsuladas en repositorios, no en vistas ni handlers.
- Nombres de tablas/columnas en `snake_case`.
- Sin `SELECT *` en código de producción; columnas explícitas.
- Índices revisados para listados y reservas.

### 7.4 Transacciones

Casos que **deben** usar transacción:

- Asignación de números y creación de orden.
- Aprobación de pago y emisión de tickets.
- Snapshot y resultados del sorteo.
- Cambio de estado de entrega con evidencia.
- Cualquier operación que afecte cupos o dinero.

### 7.5 Concurrencia

- `UNIQUE (raffle_id, number)`.
- Bloqueo de fila del sorteo o de un contador de números.
- Reintento acotado ante colisión.
- Idempotencia en aprobación de pagos.
- Pruebas de dos solicitudes simultáneas por el mismo número.

---

## 8. Vistas y UI

- Admin: reutiliza `public_html/assets/admin/` y las clases del UI kit (`.card`, `.table`, `.btn`, `.field`, etc.).
- Público: nueva capa en `public_html/assets/web/` con su propio CSS modular, reutilizando variables de marca si conviene.
- No se mezclan estilos inline de Sitejet con formularios transaccionales.
- Las vistas del panel y del público comparten parciales cuando tiene sentido, pero no dependen entre sí.
- Accesibilidad: labels, foco, contraste, teclado, mensajes de error.

---

## 9. Seguridad

- Handlers validan CSRF en POST.
- Servicios verifican permisos/estado; no confían en el formulario.
- Repositorios usan prepared statements.
- Vistas escapan con `e()`.
- Archivos privados fuera de `public_html`.
- Auditoría de acciones críticas.
- Rate limiting en endpoints públicos.
- Sesión admin separada de la participación pública.
- No se exponen datos personales en vistas públicas.

---

## 10. Errores y validación

- Errores de validación: se devuelven al handler y se muestran en el formulario.
- Errores de regla de negocio: excepción de dominio controlada (ej. “sin cupos”, “reserva expirada”).
- Errores técnicos: log privado, mensaje genérico al usuario.
- No se usa `die()` ni `var_dump()` en producción.
- Los mensajes de error no revelan datos de otros participantes.

---

## 11. Pruebas

- **Servicios:** pruebas unitarias de reglas (límites, estados, montos, fechas, commit-reveal).
- **Repositorios:** pruebas de integración contra MySQL de prueba.
- **Handlers:** pruebas HTTP con el servidor local.
- **Concurrencia:** dos reservas simultáneas, dos aprobaciones del mismo pago.
- **Seguridad:** CSRF, XSS, SQLi, archivos, autorización.
- **UI:** Lighthouse, responsive, teclado.
- **Sorteo:** reproducibilidad y verificación independiente.

Herramientas: runner simple en `tools/tests/run.php`, sin dependencias en producción. PHPUnit no es requisito del MVP. Ver `specs/006-mvp-sorteos/convenciones.md`.

---

## 12. Lo que no se hará

- No Laravel, Symfony, CodeIgniter ni otro framework MVC.
- No ORM (Doctrine, Eloquent, ActiveRecord).
- No carpeta `models/` genérica.
- No contenedor de dependencias.
- No capa de “controllers” ceremoniosa para cada vista.
- No SQL en vistas ni en JavaScript.
- No lógica de negocio duplicada entre admin y público.
- No cambios de motor de base de datos en el MVP.
- No microservicios.
- No colas externas; se usa tabla de jobs + cron si está disponible.

---

## 13. Consecuencias

### Ventajas

- Menos ceremonia y más claridad para un equipo pequeño.
- Reglas de negocio en un solo lugar.
- Admin y público comparten servicios.
- Se adapta al código actual sin reescribir.
- MySQL sigue siendo el motor conocido.
- Facilita pruebas de transacciones y concurrencia.

### Costos

- Requiere disciplina para no mezclar SQL en handlers/vistas.
- Hay que definir convenciones desde la Fase 1.
- Más carpetas que un enfoque procedimental, pero menos acoplamiento.
- La calidad depende de que los servicios no se conviertan en “god classes”.

### Mitigaciones

- Revisión de código con checklist de capas.
- Un servicio por caso de uso o dominio acotado.
- Repositorios pequeños y explícitos.
- Pruebas de integración.
- Documentar decisiones en este ADR.

---

## 14. Impacto en el plan por fases

La Fase 1 del plan 005 se amplía con:

- Estructura `app/modules`, `app/shared`, `app/web`.
- Convenciones de handlers, servicios, repositorios y vistas.
- Migraciones SQL.
- Pruebas base.
- Revisión de que el panel actual siga funcionando.

Las fases 2 a 7 implementan cada dominio dentro de `app/modules/`, sin crear un MVC genérico.

---

## 15. Criterios de aceptación de la arquitectura

1. Existe una estructura de carpetas aprobada.
2. Un handler no contiene SQL.
3. Un servicio no contiene HTML.
4. Un repositorio no contiene reglas de negocio.
5. Una vista no ejecuta SQL.
6. Admin y público comparten el mismo servicio para una regla común.
7. Las migraciones se aplican desde cero.
8. Las transacciones de reserva y pago están probadas.
9. No se agregó un framework ni un ORM.
10. MySQL/MariaDB sigue siendo el motor.

---

## 16. Decisiones abiertas menores

- ¿Se migra `app/admin/helpers.php` a `app/shared/` ahora o se deja y se agrega lo nuevo?
- ¿Los layouts del panel se comparten con el público o se separan?
- ¿Se usa un objeto `Result` o arrays para respuestas de servicio?
- Los tests usan runner simple; PHPUnit queda fuera del MVP.
- Las excepciones de dominio se nombran con el patrón `AlgoException`.

Estas decisiones se cierran en la Fase 1, no bloquean el plan.

---

## 17. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Decisión de monolito modular por dominio, sin MVC rígido, con MySQL/MariaDB. |
