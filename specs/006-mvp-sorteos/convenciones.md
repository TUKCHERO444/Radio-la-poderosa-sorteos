# Convenciones de la arquitectura 006

- **Estado:** aprobado. **Sin implementación.**
- **Base:** `specs/006-mvp-sorteos/arquitectura.md`
- **Objetivo:** fijar nombres, carpetas, capas, contratos y reglas de código para que el MVP se implemente de forma consistente y sin MVC rígido.
- **Decisiones del organizador:** inglés para código/BD, español para UI; clases con autoload manual; PHP 8.0 compatible; excepciones de dominio + validación por arrays; tabla de rutas para módulos nuevos; validadores compartidos + servicio; runner de pruebas simple; fechas en `America/Lima`; MySQL/MariaDB.

---

## 1. Idioma y nomenclatura

| Elemento | Convención | Ejemplo |
| --- | --- | --- |
| Tablas | Inglés, plural, `snake_case` | `raffles`, `raffle_prizes`, `ticket_reservations` |
| Columnas | Inglés, `snake_case` | `raffle_id`, `amount_cents`, `created_at` |
| Clases | Inglés, `PascalCase` | `RaffleService`, `TicketRepository`, `PublicRaffleHandler` |
| Métodos | Inglés, `camelCase`, verbo + objeto | `createRaffle`, `reserveTickets`, `approvePayment` |
| Funciones helper | Inglés, `snake_case` | `validate_dni`, `format_money` |
| Variables | Inglés, `camelCase` | `$raffleId`, `$paymentProof` |
| Constantes | Inglés, `UPPER_SNAKE_CASE` | `MAX_TICKETS_PER_ORDER` |
| Estados | Inglés, `snake_case` | `pending_payment`, `paid`, `expired` |
| Rutas públicas | Español, `kebab-case` | `/sorteos`, `/sorteos/{slug}/participar` |
| Rutas admin | Inglés, `kebab-case` | `/admin/raffles`, `/admin/payments` |
| Textos UI | Español | “Comprobante recibido”, “Reserva expirada” |
| Mensajes de error | Español, claros y sin datos de terceros | “El DNI no es válido.” |
| Comentarios y docs | Español | “Bloquea el sorteo y reserva números.” |

**Regla:** el código y la base de datos están en inglés; la interfaz, los correos, las plantillas y los mensajes están en español. Las rutas públicas están en español por SEO/UX; las rutas del panel siguen el estilo actual en inglés (`/admin/users`, `/admin/raffles`).

---

## 2. Carpetas y archivos

```text
app/
  admin/                     # panel actual, se conserva
  web/                       # capa pública nueva (namespace App\Web)
  modules/
    raffles/
      RaffleService.php
      RaffleRepository.php
      PublicRaffleHandler.php
      AdminRaffleHandler.php
      RaffleStatus.php
    prizes/
      PrizeService.php
      PrizeRepository.php
      AdminPrizeHandler.php
    payment_methods/
      PaymentMethodService.php
      PaymentMethodRepository.php
      AdminPaymentMethodHandler.php
    participants/
    payments/
    draws/
    deliveries/
    support/
  shared/
    http/Request.php
    http/Response.php
    domain/DomainException.php
    validation/Validator.php
    audit/AuditService.php
    storage/PrivateStorage.php
    Money.php
    Dates.php
    Ids.php
  database/
    migrations/
  views/
    layouts/
      admin.php
      web.php
    partials/
  bootstrap.php
public_html/
  index.php
  assets/admin/
  assets/web/
tools/
  migrate.php
  worker.php
  tests/
    run.php
```

Reglas:

- Carpetas en minúsculas, incluyendo los subdirectorios de `app/shared` (`http`, `domain`, `validation`, `audit`, `storage`).
- Archivos de clase en `PascalCase.php`.
- Archivos de vistas en `snake_case.php` (`index.php`, `form.php`, `show.php`).
- Migraciones con timestamp: `20261116120000_create_raffles.sql`.
- No se crea `models/`, `controllers/` ni `repositories/` global. Cada módulo tiene sus clases.
- No se mezclan vistas de admin y web en la misma carpeta; los layouts compartidos viven en `app/views/layouts`.

---

## 3. Autoload y namespaces

- Namespace raíz: `App\`.
- Módulos: `App\Modules\Raffles`, `App\Modules\Prizes`, `App\Modules\PaymentMethods`, `App\Modules\Participants`, `App\Modules\Payments`, `App\Modules\Draws`, `App\Modules\Deliveries`.
- Compartido: `App\Shared\...`.
- Web: `App\Web`.
- Admin nuevo: `App\Admin` solo si se crean clases nuevas; el router actual puede seguir con funciones.

Autoload manual en `app/bootstrap.php`:

- `App\Modules\Raffles\RaffleService` → `app/modules/raffles/RaffleService.php`.
- `App\Shared\Http\Request` → `app/shared/http/Request.php`.
- `App\Admin\UserHandler` → `app/admin/UserHandler.php`.
- Si el archivo no existe, no se lanza error en el autoload; el error aparece al usar la clase.

No se usa Composer en producción. Las funciones helper y archivos de configuración se incluyen con `require` explícito.

---

## 4. Clases y responsabilidades

### 4.1 Handlers

- Nombres: `PublicRaffleHandler`, `AdminRaffleHandler`, `AdminPaymentHandler`.
- Métodos: `index`, `show`, `create`, `store`, `edit`, `update`, `publish`, `close`, `draw`, `results`, `approve`, `reject`.
- Reciben un `Request` y devuelven un `Response`.
- Validan CSRF/permisos, llaman a un servicio y renderizan/redirigen.
- No contienen SQL ni reglas de negocio.
- No usan `$_POST` directamente; usan `$request->post()`, `$request->query()`, `$request->file()`.

### 4.2 Servicios

- Nombres: `RaffleService`, `ParticipantService`, `TicketService`, `PaymentService`, `DrawService`, `DeliveryService`.
- Métodos de caso de uso: `createDraft`, `publish`, `closeSales`, `reserveTickets`, `approvePayment`, `rejectPayment`, `executeDraw`, `publishResults`, `registerDelivery`.
- Reciben datos ya normalizados y devuelven arrays/DTOs simples.
- Aplican reglas de negocio, estados y transacciones.
- Lanzan excepciones de dominio cuando una regla falla.
- No generan HTML ni leen superglobales.

### 4.3 Repositorios

- Nombres: `RaffleRepository`, `ParticipantRepository`, `OrderRepository`, `TicketRepository`, `PaymentRepository`, `DrawRepository`, `DeliveryRepository`.
- Métodos: `findById`, `findByPublicCode`, `findBySlug`, `listPublished`, `listByStatus`, `insert`, `updateStatus`, `lockForUpdate`, `countAvailable`.
- Usan PDO y prepared statements.
- No validan reglas de negocio; no lanzan mensajes de UI.
- Devuelven arrays o `null`.
- No abren transacciones; la transacción la controla el servicio.

### 4.4 DTOs

- Solo cuando un array se vuelve difícil de leer.
- Nombres: `RaffleSummary`, `TicketReservationResult`, `PaymentInstructions`.
- Sin lógica de negocio; solo datos tipados.
- No se usan como entidades ORM.

### 4.5 Excepciones

- Base: `App\Shared\Domain\DomainException`.
- Ejemplos: `RaffleNotPublishedException`, `NoTicketsAvailableException`, `ReservationExpiredException`, `InvalidStateTransitionException`, `PaymentAlreadyProcessedException`, `TicketLimitReachedException`.
- Se lanzan desde servicios.
- Los handlers las capturan y deciden el HTTP status/flash.
- No se usan excepciones para validación de formulario; para eso se usan arrays.

---

## 5. HTTP y routing

### 5.1 Tabla de rutas

Los módulos nuevos usan una tabla declarativa:

```php
return [
    ['GET',  '/sorteos', [PublicRaffleHandler::class, 'index']],
    ['GET',  '/sorteos/{slug}', [PublicRaffleHandler::class, 'show']],
    ['POST', '/sorteos/{slug}/participar', [PublicRaffleHandler::class, 'reserve']],
    ['GET',  '/admin/raffles', [AdminRaffleHandler::class, 'index']],
    ['POST', '/admin/raffles/{id}/publish', [AdminRaffleHandler::class, 'publish']],
];
```

- El router compila `{param}` a regex y pasa un array de parámetros.
- El método HTTP se valida.
- Rutas no encontradas → 404.
- Rutas que requieren sesión → `require_admin()` antes del handler.
- El router actual del panel se conserva; las rutas nuevas se agregan a una tabla y se delegan.

### 5.2 Request/Response

`App\Shared\Http\Request`:

- `method()`, `path()`, `query($key, $default)`, `post($key, $default)`, `all()`, `file($key)`, `ip()`, `userAgent()`.
- No modifica superglobales.

`App\Shared\Http\Response`:

- `view(string $view, array $data = [], int $status = 200)`.
- `redirect(string $url, int $status = 302)`.
- `json(array $data, int $status = 200)`.
- `send()` envía headers y contenido.
- No contiene lógica de negocio.

### 5.3 Códigos HTTP

| Caso | Código |
| --- | --- |
| Validación de formulario | 422 |
| Conflicto de estado / sin cupos / reserva expirada | 409 |
| No encontrado | 404 |
| No autorizado | 401/403 |
| Rate limit | 429 |
| Error técnico | 500 |

---

## 6. Validación y errores

### 6.1 Validación de formato

En `app/shared/validation/Validator.php`:

- `required`, `string`, `maxLength`, `email`, `dni`, `phonePe`, `integer`, `money`, `date`, `in`.
- Devuelve `['field' => 'mensaje en español']`.
- Los handlers usan el validador antes de llamar al servicio.

### 6.2 Reglas de negocio

En el servicio:

- Límite de tickets por persona.
- Cupos disponibles.
- Estado del sorteo.
- Monto exacto.
- Reserva vigente.
- Pago no duplicado.
- Transición de estado válida.
- Snapshot y semilla.

### 6.3 Contrato de error

- **Validación de formulario:** arrays `['field' => 'message']`; el handler vuelve a mostrar el formulario con 422.
- **Regla de negocio:** excepción de dominio; el handler muestra un mensaje flash o una página de estado con 409.
- **Error técnico:** se registra en log privado; se muestra un mensaje genérico 500.
- **No** se exponen datos de otros participantes, SQL, rutas internas ni stack traces.

---

## 7. Transacciones

- La transacción se abre y cierra en el **servicio**, no en el repositorio ni en el handler.
- Repositorios usan la conexión PDO existente; no hacen `beginTransaction`.
- Operaciones transaccionales obligatorias:
  - Asignar números y crear orden.
  - Aprobar pago y emitir tickets.
  - Rechazar pago y liberar/ajustar reserva.
  - Snapshot y ejecución del sorteo.
  - Cambiar estado de entrega con evidencia.
- Ante excepción: `rollBack()` y relanzar.
- No se anidan transacciones en el MVP. Si se necesita, se usa un savepoint documentado.
- Auditoría: se registra dentro de la misma transacción para cambios críticos; si la transacción falla, no debe quedar una auditoría de un cambio que no ocurrió.

---

## 8. Base de datos

### 8.1 Reglas

- MySQL/MariaDB, InnoDB, `utf8mb4`, `utf8mb4_unicode_ci`.
- Tablas en plural, inglés, `snake_case`.
- PK `id` `INT UNSIGNED AUTO_INCREMENT`.
- FK `<tabla_singular>_id`.
- Timestamps `created_at`, `updated_at`.
- Dinero en centavos: `amount_cents BIGINT UNSIGNED` (o `INT UNSIGNED` si el rango alcanza).
- Moneda: `currency CHAR(3)` con `PEN`.
- Booleanos: `is_active TINYINT(1)` o `status` cuando hay más de dos estados.
- Estados: `VARCHAR(30)` con valores documentados; no `ENUM` para facilitar migraciones.
- JSON solo para metadatos flexibles; no para campos que se filtran.
- `SELECT` con columnas explícitas; no `SELECT *` en producción.
- `LIMIT` obligatorio en listados.
- Índices en claves de búsqueda y FKs.
- `FOR UPDATE` solo dentro de transacción.

### 8.2 Borrado

- Sorteos con tickets/pagos no se eliminan; se cancelan.
- Participantes: soft delete o anonimización según retención.
- Comprobantes y evidencias: no se borran; se archivan con retención.
- Auditoría: append-only; sin `UPDATE`/`DELETE` desde la aplicación.

### 8.3 Migraciones

- Archivos `YYYYMMDDHHMMSS_descripcion.sql`.
- `tools/migrate.php` aplica pendientes y registra en `schema_migrations`.
- No se edita una migración aplicada; se crea otra.
- Backup previo y rollback documentado.

---

## 9. IDs, códigos y dinero

- ID interno: autoincremental, no se expone en URLs públicas si se puede evitar.
- Código público: base62 aleatorio de 12–16 caracteres (`public_code`), único.
- Código de orden: base62 de 10–12 caracteres; visible para el cliente.
- Código de acceso de invitado: aleatorio, se muestra una vez y se guarda hasheado; se usa para consultar participaciones.
- Código de ticket: único por sorteo, visible y verificable.
- Dinero: enteros en centavos; nunca `FLOAT`.
- Formato: `S/ 1,234.56`.
- Fechas: `America/Lima`; `DATETIME`; no se usa `date()` crudo sin zona.

---

## 10. Vistas y frontend

- Vistas PHP con escape `e()`.
- No SQL en vistas.
- Layouts: `app/views/layouts/admin.php`, `app/views/layouts/web.php`.
- Admin reutiliza el UI kit actual (`.card`, `.table`, `.btn`, `.field`, etc.).
- Web pública usa `public_html/assets/web/` con CSS modular propio; puede reutilizar variables de marca.
- No se mezclan estilos inline de Sitejet con formularios transaccionales.
- JS progresivo, sin frameworks; validación en servidor siempre.
- Accesibilidad: labels, foco, contraste, teclado, mensajes de error.
- No se muestran datos personales en páginas públicas.
- Service worker: excluir rutas dinámicas o usar network-first para `/sorteos/*/participar`, `/pago`, `/participaciones`, `/admin`, `/assets/admin`.

---

## 11. Seguridad

- CSRF en todos los POST.
- Escape de salida con `e()`.
- Prepared statements siempre.
- Hash de contraseñas de admin con `password_hash`.
- Sesión admin separada de la participación pública.
- Rate limiting en formularios públicos y login.
- Archivos: validar tamaño, tipo, nombre aleatorio; almacenar fuera de `public_html`.
- No registrar DNI/celular/comprobantes en logs.
- No confiar en validación de JavaScript.
- Cabeceras de seguridad existentes y HTTPS.

---

## 12. Pruebas

- Runner simple en `tools/tests/run.php`.
- Tests unitarios de servicios: reglas, estados, montos, límites, commit-reveal.
- Tests de integración de repositorios contra MySQL de prueba.
- Tests HTTP manuales/asistidos con el servidor local.
- Tests de concurrencia: dos reservas del mismo número; dos aprobaciones del mismo pago.
- Tests de seguridad: CSRF, XSS, SQLi, archivos, autorización.
- Tests de UI: Lighthouse, responsive, teclado.
- No se agrega PHPUnit en producción; si se usa en desarrollo, no debe ser requisito para desplegar.

---

## 13. Documentación y estilo

- `declare(strict_types=1);` en archivos nuevos.
- Sin etiqueta de cierre `?>`.
- PSR-12 adaptado: 4 espacios, llaves en nueva línea, nombres claros.
- Docblocks en clases y métodos públicos.
- Comentarios en español; identificadores en inglés.
- No `var_dump`, `print_r` ni `die` en producción.
- Cada módulo tiene un README breve si agrega reglas no obvias.
- Las decisiones nuevas se documentan en `specs/` antes de implementar.

---

## 14. Checklist de revisión de código

- [ ] El handler no contiene SQL.
- [ ] El servicio no contiene HTML.
- [ ] El repositorio no contiene reglas de negocio.
- [ ] La vista no ejecuta SQL.
- [ ] La transacción está en el servicio.
- [ ] Los errores de validación son arrays.
- [ ] Las reglas de negocio son excepciones de dominio.
- [ ] Los POST validan CSRF.
- [ ] Las consultas usan prepared statements.
- [ ] Las salidas usan `e()`.
- [ ] Los archivos se guardan fuera de `public_html`.
- [ ] Los datos personales no se publican ni se registran en logs.
- [ ] El dinero está en centavos.
- [ ] Las fechas están en `America/Lima`.
- [ ] El código nuevo funciona en PHP 8.0.
- [ ] No se agregó framework, ORM ni Composer a producción.
- [ ] El test o la verificación manual está documentado.

---

## 15. Respuestas registradas del organizador

| Pregunta | Decisión |
| --- | --- |
| Idioma | Inglés para código/BD; español para UI. |
| Servicios/repositorios | Clases con namespaces y autoload manual. |
| PHP | Compatible con PHP 8.0. |
| Errores | Excepciones de dominio + arrays de validación. |
| Routing | Tabla de rutas para módulos nuevos; admin actual se conserva. |
| Validación | Validadores compartidos + reglas en el servicio. |
| Pruebas | Runner simple sin dependencias. |
| Fechas | `America/Lima` en BD y UI. |
| Arquitectura | Monolito modular por dominio, sin MVC rígido. |
| Base de datos | MySQL/MariaDB con PDO; sin ORM. |

---

## 16. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Convenciones de arquitectura 006 y respuestas del organizador. |
