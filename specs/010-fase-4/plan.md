# 010 - Fase 4: participación, órdenes, números y reservas

- **Estado:** propuesta para aprobación.
- **Fecha:** 2026-11-16.
- **Base:** `specs/005-mvp-sorteos/plan-fases.md` (Fase 4), `specs/006-mvp-sorteos/arquitectura.md`, `specs/007-mvp-sorteos/especificacion.md` (RF-PAR, RF-TIC, RN, estados), `specs/009-fase-1/avance.md`.
- **Objetivo:** permitir que un invitado se registre, elija cantidad, reciba números únicos, obtenga una orden con reserva de 30 minutos y pueda consultar su estado sin contraseña, sin emitir tickets hasta que exista un pago aprobado (Fase 5).
- **Fuera de alcance:** subida de comprobantes, aprobación/rechazo de pagos, emisión de tickets, sorteo, commit-reveal, entregas y reportes. Esas fases se apoyan en este diseño pero no se implementan aquí.

---

## 0. Resumen de decisiones

| # | Decisión | Motivo |
| --- | --- | --- |
| D1 | Asignación **bajo demanda** de `raffle_numbers`, dentro de una transacción con bloqueo de la fila del sorteo (`SELECT ... FOR UPDATE`). | Evita pre-crear miles de filas y funciona en MariaDB 10.4 (XAMPP) sin `SKIP LOCKED`. |
| D2 | `UNIQUE (raffle_id, number)` como última barrera; reintento acotado ante colisión. | Garantiza que nunca haya dos tickets con el mismo número. |
| D3 | DNI guardado como **HMAC-SHA256** (búsqueda/duplicados) + **AES-256-GCM** (visualización autorizada). | No se almacena DNI en claro; se puede mostrar al admin con clave. |
| D4 | Código de acceso de la orden **mostrado una sola vez** y guardado como HMAC ligado al código público. | Consulta sin contraseña sin almacenar secretos en claro. |
| D5 | Autorización de la confirmación por **sesión pública** (no por URL). | Evita filtrar códigos en logs, historial o referers. |
| D6 | Expiración **perezosa + worker CLI** cada minuto. | No depende de un cron perfecto y libera números incluso si el worker falla. |
| D7 | Tickets solo tras pago aprobado (Fase 5). En Fase 4 se reserva, no se emite. | Cumple RF-TIC-06 y RN-07. |
| D8 | Participación habilitada solo si el sorteo está `published`, dentro de ventana de ventas, con bases publicadas y texto de privacidad versionado. | Evita aceptaciones sin documento vigente. |
| D9 | `audit_chain` de una sola fila para serializar la cadena de auditoría. | Las acciones públicas concurrentes pueden bifurcar la cadena actual. |
| D10 | Reutilización de participante existente solo si coincide celular o correo. | Evita que un tercero use el DNI de otra persona para consumir su límite o crear órdenes a su nombre. |

Decisiones que requieren aprobación explícita antes de implementar: **D1, D3, D4, D5, D9, D10** (ver sección 12).

---

## 1. Alcance funcional

### 1.1 Incluido

1. Detalle público del sorteo: `/sorteos/{slug}`.
2. Formulario de participación: nombre, DNI, celular, correo, cantidad, mayoría de edad, aceptación de bases y privacidad.
3. Registro o reutilización segura del participante.
4. Cálculo de total en centavos en el servidor.
5. Validación de límite por persona y cupo del sorteo.
6. Asignación automática de números únicos.
7. Orden `pending_payment` con vencimiento a 30 minutos (configurable).
8. Confirmación con números, monto, estado, métodos de pago activos e instrucciones.
9. Consulta por código de orden + código de acceso.
10. Expiración de reservas: perezosa y por CLI.
11. Listados administrativos de participantes y órdenes.
12. Detalle administrativo de orden con números, aceptaciones y auditoría.
13. Extensión manual de reserva y expiración forzada con auditoría.
14. Pruebas unitarias, de integración y de concurrencia.

### 1.2 Excluido (otras fases)

- Comprobantes, pagos, aprobación, rechazo, tickets (Fase 5).
- Sorteo, snapshot, semilla, resultados (Fase 6).
- Entregas, evidencias, reportes, WhatsApp automático (Fase 7).
- Cuentas de usuario, login público, recuperación de contraseña.
- Selección manual de números por el participante.
- Pasarelas de pago, OTP, RENIEC, correo automático.

---

## 2. Reglas de seguridad

### 2.1 Superficie pública y sesión

- La sesión pública se inicia **solo** en rutas transaccionales (`/participar`, confirmación, `/participaciones`). `/sorteos` y `/sorteos/{slug}` no abren sesión para poder cachearse.
- Cookie de sesión:
  - Nombre: `rlps_pub`.
  - `HttpOnly=true`, `SameSite=Lax`, `Secure` cuando HTTPS, `Path=/`.
  - `session.use_strict_mode=1`, `session.use_only_cookies=1`, `session.cookie_httponly=1`.
  - `session.gc_maxlifetime=3600`.
- La sesión solo guarda:
  - token CSRF;
  - token de formulario (idempotencia);
  - IDs de órdenes autorizadas para ver la confirmación (máx. 10, con expiración de 2 h);
  - resultado temporal de la consulta;
  - mensajes flash.
- **Nunca** se guarda DNI, nombre, correo, celular ni códigos de acceso en la sesión.
- Tras crear la orden se ejecuta `session_regenerate_id(true)` para evitar fijación de sesión.

### 2.2 CSRF

- Todas las rutas `POST` públicas exigen `_token` con `hash_equals`.
- Respuesta inválida: `419` con página amigable y enlace para reintentar; no se procesa nada.
- Defensa adicional: si el navegador envía `Sec-Fetch-Site`, debe ser `same-origin` o `none`; si envía `Origin`, debe coincidir con el host de `app.url`.
- El token se mantiene por sesión; el token de formulario se rota tras cada envío exitoso.

### 2.3 Límite de peticiones (rate limiting)

Componente `App\Shared\Security\RateLimiter`, almacenamiento en archivos con `flock` (sin tabla nueva, apto para hosting compartido):

```text
storage/rate-limit/{bucket}/{sha256(key)}.json
```

Cada archivo guarda `window_start` y `hits`. La clave se compone con HMAC para no almacenar IP o DNI en claro.

| Bucket | Clave | Límite propuesto | Ventana |
| --- | --- | --- | --- |
| `participate.ip` | HMAC(IP) | 10 envíos | 10 min |
| `participate.document` | HMAC(DNI) | 6 envíos | 30 min |
| `lookup.ip` | HMAC(IP) | 15 intentos | 15 min |
| `lookup.order` | HMAC(código de orden) | 8 intentos | 15 min |
| `confirmation.ip` | HMAC(IP) | 60 vistas | 10 min |

- Al exceder: `429` con `Retry-After` y mensaje en español.
- Configurable en `participation.rate_limits`; en pruebas se inyecta reloj y directorio temporal.

### 2.4 Datos personales

- **DNI:** se normaliza a dígitos. Se guarda:
  - `document_number_hash = HMAC-SHA256("document|DNI|{numero}", hash_key)`;
  - `document_number_encrypted = "v1:" . base64url(iv | tag | ciphertext)` con AES-256-GCM;
  - `document_type = "DNI"`, longitud 8.
- **Búsqueda de duplicados:** por hash exacto, nunca por texto cifrado.
- **Visualización:** solo en el detalle administrativo de participante/orden, descifrando con la clave activa. Los listados muestran `****1234` (últimos 4 visibles).
- **Correo y celular:** se guardan en claro porque se usan para contacto y señales secundarias; se muestran enmascarados en listados y nunca en páginas públicas.
- **Llaves:**
  - `security.hash_key`: base64 de 32 bytes para HMAC (documento, códigos, idempotencia, IP).
  - `security.encryption_keys`: mapa `versión => base64(32 bytes)`.
  - `security.active_key_version`: versión usada al cifrar.
  - Si faltan llaves, las rutas de participación responden `503` con “Participación no disponible temporalmente” y se registra el error; el panel sigue funcionando.
  - La rotación del `hash_key` requiere reindexar hashes (fuera del MVP); la rotación de cifrado se soporta agregando una nueva versión al mapa.
- **Logs:** nunca se registra DNI, nombre, correo, celular, código de acceso ni contenido de bases. Se registran IDs, códigos públicos de orden y hashes.
- **Auditoría:** `before_json`/`after_json` de participantes y órdenes solo contienen IDs, códigos públicos, cantidades, montos y estados; no PII.

### 2.5 Códigos

- **Código de orden:** 14 caracteres alfanuméricos (`Ids::publicCode(14)`), único en `orders.public_code`. Se muestra agrupado (`ABCD-EFGH-JKLM`) solo en UI; en BD y validación va sin guiones.
- **Código de acceso:** 10 caracteres del alfabeto sin ambigüedad `23456789ABCDEFGHJKLMNPQRSTUVWXYZ` (32 símbolos ⇒ ~2^50 combinaciones).
  - Se muestra una sola vez en la confirmación.
  - Se guarda como `HMAC-SHA256("order-access|{public_code}|{codigo}", hash_key)`.
  - La verificación normaliza mayúsculas, quita guiones y espacios, busca por `public_code` y compara con `hash_equals`.
- **Código de ticket:** Fase 5; misma política.
- **Colisiones:** reintento hasta 5 veces sobre violación de unique; si persiste, error controlado.

### 2.6 Consulta de participaciones

- Requiere **código de orden + código de acceso**. No se acepta solo DNI, celular o correo.
- Respuesta uniforme ante error: “No encontramos una orden con esos datos.” No se distingue entre orden inexistente y código incorrecto.
- Rate limit por IP y por código de orden.
- Resultado en sesión y renderizado inmediato; no se expone en la URL. `Cache-Control: no-store`.
- Se muestran: estado, sorteo, cantidad, números, total, vencimiento y nombre enmascarado. DNI enmascarado.

### 2.7 Entrada, salida e inyección

- Todas las consultas usan PDO preparado; `ORDER BY` y columnas de filtro se eligen de listas blancas.
- Toda salida dinámica pasa por `e()` (`htmlspecialchars` con `ENT_QUOTES`).
- Texto de bases se escapa y se muestra con `nl2br` sobre valor escapado.
- No hay subida de archivos en esta fase.
- Cabeceras en páginas transaccionales:
  - `Cache-Control: no-store, private`;
  - `X-Robots-Tag: noindex, nofollow`;
  - se mantienen `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y `Permissions-Policy` del bootstrap.

### 2.8 Idempotencia y doble envío

- Cada formulario incluye `_form_token` (32 bytes aleatorios, guardado en sesión).
- `orders.idempotency_key = HMAC-SHA256("order-idem|{form_token}", hash_key)` con índice único.
- Si el `INSERT` de la orden viola la clave:
  - se busca la orden existente;
  - si coincide con el mismo sorteo y participante, se redirige a su confirmación;
  - si no coincide, se responde `409` sin revelar datos.
- El token se marca como usado en sesión; un doble clic o “atrás + enviar” no crea otra orden.

### 2.9 Auditoría

- `AuditService` se modifica para usar una fila de bloqueo `audit_chain` (ver 3.2), garantizando una sola cadena incluso con reservas concurrentes.
- Orden de bloqueo global: **raffle → participant → order → raffle_numbers → audit_chain**. Nunca al revés.
- Acciones nuevas:

| Acción | Entidad | Actor |
| --- | --- | --- |
| `participant.created` | `participant` | `public` / `system` |
| `participant.reused` | `participant` | `public` |
| `participant.reuse_denied` | `participant` | `public` |
| `order.created` | `order` | `public` |
| `order.expired` | `order` | `system` |
| `order.cancelled` | `order` | `admin` |
| `order.reservation_extended` | `order` | `admin` |
| `legal.accepted` | `legal_acceptance` | `public` |

- El actor público se registra como `actor_type='public'`, `actor_id=null`; el worker como `actor_type='system'`.
- IP y user agent salen del request o del CLI; se truncan a 45/255.

---

## 3. Persistencia en base de datos

### 3.1 Tablas existentes que se usan

| Tabla | Uso en Fase 4 |
| --- | --- |
| `raffles` | Estado, ventana de ventas, precio, cupo, límite por persona, bases vigentes, `draw_at`. |
| `raffle_rules_versions` | Documento de bases aceptado (`content`, `content_hash`, `version`). |
| `participants` | Ficha única por DNI (hash + cifrado). |
| `orders` | Orden y reserva (`pending_payment`, `expires_at`). |
| `raffle_numbers` | Inventario de números (`available`, `reserved`; `paid` en Fase 5). |
| `legal_acceptances` | Evidencia de aceptación de bases y privacidad. |
| `audit_logs` | Bitácora encadenada. |
| `raffle_prizes`, `prizes` | Detalle público. |
| `raffle_payment_methods`, `payment_methods` | Instrucciones en la confirmación (solo lectura). |

### 3.2 Migración nueva

Archivo: `app/database/migrations/20261117100000_phase4_participation.sql`.

```sql
-- Idempotencia y control de abuso en órdenes
ALTER TABLE orders
    ADD COLUMN idempotency_key CHAR(64) NULL AFTER access_code_hash,
    ADD COLUMN request_ip_hash CHAR(64) NULL AFTER expires_at,
    ADD COLUMN extended_at DATETIME NULL AFTER expires_at,
    ADD UNIQUE KEY orders_idempotency_unique (idempotency_key),
    ADD KEY orders_ip_status_idx (request_ip_hash, status);

-- Trazabilidad de la versión de bases aceptada
ALTER TABLE legal_acceptances
    ADD COLUMN rules_version_id INT UNSIGNED NULL AFTER order_id,
    ADD KEY legal_acceptances_rules_idx (rules_version_id),
    ADD CONSTRAINT fk_legal_acceptances_rules
        FOREIGN KEY (rules_version_id) REFERENCES raffle_rules_versions (id)
        ON DELETE SET NULL;

-- Índices para asignación y expiración
ALTER TABLE raffle_numbers
    ADD KEY raffle_numbers_raffle_status_idx (raffle_id, status, reserved_until),
    ADD KEY raffle_numbers_order_status_idx (order_id, status);

-- Cadena de auditoría serializada
CREATE TABLE IF NOT EXISTS audit_chain (
    id TINYINT UNSIGNED NOT NULL,
    last_log_id BIGINT UNSIGNED NULL,
    last_hash CHAR(64) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO audit_chain (id) VALUES (1);

UPDATE audit_chain ac
SET last_log_id = (SELECT id FROM audit_logs ORDER BY id DESC LIMIT 1),
    last_hash = (SELECT hash FROM audit_logs ORDER BY id DESC LIMIT 1)
WHERE ac.id = 1
  AND ac.last_log_id IS NULL;
```

Notas:

- `idempotency_key` admite `NULL`; MySQL permite múltiples `NULL` en un índice único.
- `extended_at` registra la última extensión manual (BCP).
- `request_ip_hash` no es PII directa y permite limitar abuso por IP sin guardar la IP.
- No se agregan `CHECK` para mantener compatibilidad con MariaDB antiguo; las reglas se validan en servicio.
- La migración debe ser idempotente según la convención del proyecto: el migrador registra el archivo y no lo vuelve a ejecutar. Para entornos ya migrados, usar una migración nueva, no editar la de Fase 1.

### 3.3 Estados

#### Orden

```text
pending_payment ──> expired
        │
        ├──> cancelled            (solo admin, Fase 4)
        │
        └── Fase 5:
            pending_payment -> proof_received -> under_review -> approved
                                                        └────> rejected
            approved -> refunded
```

Reglas:

- `pending_payment` exige `expires_at > NOW()`; si no, pasa a `expired` antes de cualquier lectura pública.
- `expired` y `cancelled` liberan números y no admiten comprobantes.
- `approved` conserva números y habilita tickets (Fase 5).
- No se borran órdenes ni participantes.

#### Número

```text
available ──> reserved ──> paid
     ^            │
     └────────────┘  (expira/cancela)
paid ──> cancelled/refunded (solo admin, con auditoría, Fase 5)
```

- `reserved` exige `order_id` y `reserved_until`.
- `paid` exige `ticket_id` (Fase 5).
- `available` exige `order_id = NULL`, `ticket_id = NULL`, `reserved_until = NULL`.

#### Participante

- `active` por defecto.
- `blocked` solo por admin, con motivo auditado (no se implementa UI en Fase 4; se deja previsto).

### 3.4 Reglas de integridad

- `orders.quantity >= 1`, `unit_price_cents >= 0`, `total_amount_cents = unit_price_cents * quantity`.
- `orders.expires_at = created_at + reservation_minutes` para `pending_payment`.
- `raffle_numbers.number` en `1..raffles.total_tickets`.
- `raffle_numbers.raffle_id` debe coincidir con `orders.raffle_id` (validado en servicio; no se puede expresar con FK simple).
- Un número `reserved` pertenece a una sola orden.
- Un número `paid` pertenece a un solo ticket.
- La suma de números `reserved` + `paid` de un sorteo nunca supera `total_tickets`.
- La suma de números activos (`reserved` vigentes + `paid`) por participante y sorteo nunca supera `raffles.max_tickets_per_participant`.

### 3.5 Relaciones con entidades actuales

```text
raffles 1 ──── N raffle_numbers
   │ 1                 │ N
   │                   │
   │ N                 │ 0..1
raffle_prizes           orders ──── 1 participants
   │ N                   │ 1
prizes                    │
                          ├── N raffle_numbers
                          ├── N legal_acceptances
                          └── N tickets            (Fase 5)
raffle_rules_versions 1 ── N legal_acceptances
users 1 ── N audit_logs (actor admin)
```

- `participants` es global (una fila por DNI); se reutiliza entre sorteos.
- `orders` siempre pertenece a un sorteo y a un participante.
- `raffle_numbers.order_id` es `NULL` cuando el número está disponible y se conserva al pasar a `paid` para trazabilidad.
- `tickets.order_id` y `tickets.participant_id` deben coincidir con la orden (validación de Fase 5).
- `legal_acceptances.order_id` puede ser `NULL` a nivel de esquema, pero en Fase 4 toda aceptación creada desde una reserva lleva `order_id`.
- El pago (`payments`) se relaciona con la orden en Fase 5; Fase 4 solo muestra métodos activos.

---

## 4. Diseño técnico del módulo

### 4.1 Estructura de archivos

```text
app/shared/
  Clock.php
  security/
    Crypto.php
    RateLimiter.php
    ClientIp.php
  validation/
    FieldRules.php              (extender: email, access_code, order_code, quantity)
  audit/
    AuditService.php            (modificar: audit_chain)

app/modules/participants/
  ParticipantRepository.php
  ParticipantService.php
  ParticipantValidator.php
  AdminParticipantHandler.php

app/modules/raffles/
  RaffleNumberRepository.php
  RafflePublicRepository.php    (o extender RaffleRepository)

app/modules/orders/
  OrderRepository.php
  OrderService.php
  NumberAllocator.php
  ReservationExpiryService.php
  OrderAccess.php
  AdminOrderHandler.php

app/web/
  router.php
  helpers.php
  PublicRaffleHandler.php
  PublicOrderHandler.php
  PublicLookupHandler.php
  views/
    sorteo-detalle.php
    participar.php
    confirmacion.php
    participaciones.php
    partials/
      order-summary.php
      payment-methods.php
      field-error.php

app/admin/views/participants/
  index.php
  show.php
app/admin/views/orders/
  index.php
  show.php

app/web/legal/
  privacidad-v1.md
  terminos-v1.md

tools/
  expire_reservations.php
  keygen.php
  tests/
    bootstrap.php
    run_all.php
    crypto.php
    rate_limiter.php
    participant_service.php
    number_allocator.php
    order_service.php
    expiry_service.php
    field_rules.php
    concurrency.php
    reserve_worker.php
```

### 4.2 Responsabilidades

| Clase | Responsabilidad | No debe |
| --- | --- | --- |
| `PublicRaffleHandler` | Mostrar detalle, formulario y ejecutar reserva. | Contener SQL ni reglas de negocio. |
| `PublicOrderHandler` | Confirmación autorizada por sesión. | Aceptar el código de acceso por URL. |
| `PublicLookupHandler` | Formulario y consulta por códigos. | Revelar si el DNI/correo existen. |
| `ParticipantValidator` | Normalizar y validar campos de la ficha. | Tocar la base de datos. |
| `ParticipantService` | Crear o reutilizar participante, reglas de coincidencia, auditoría. | Asignar números. |
| `RaffleNumberRepository` | Consultas y actualizaciones del inventario. | Decidir política de cupo. |
| `NumberAllocator` | Política de asignación, reutilización y límites dentro de la transacción. | Abrir transacciones propias. |
| `OrderService` | Orquestar reserva: transacción, orden, aceptaciones, auditoría, idempotencia. | Emitir tickets ni aprobar pagos. |
| `OrderAccess` | Generar/verificar códigos de acceso y códigos públicos. | Guardar códigos en claro. |
| `ReservationExpiryService` | Expirar órdenes y liberar números de forma idempotente. | Expirar órdenes pagadas. |
| `RafflePublicRepository` | Sorteos públicos por slug, premios, métodos, bases. | Devolver borradores en producción. |

### 4.3 Configuración nueva

`app/inc/config.php`:

```php
'security' => [
    'hash_key' => getenv('SECURITY_HASH_KEY') ?: '',
    'active_key_version' => getenv('SECURITY_ACTIVE_KEY_VERSION') ?: 'v1',
    'encryption_keys' => [
        // 'v1' => getenv('SECURITY_ENCRYPTION_KEY_V1') ?: '',
    ],
],
'participation' => [
    'reservation_minutes' => (int) (getenv('RESERVATION_MINUTES') ?: 30),
    'max_per_order' => (int) (getenv('MAX_PER_ORDER') ?: 20),
    'max_active_numbers_per_ip' => (int) (getenv('MAX_ACTIVE_NUMBERS_PER_IP') ?: 0),
    'rate_limits' => [
        'participate.ip' => ['limit' => 10, 'window' => 600],
        'participate.document' => ['limit' => 6, 'window' => 1800],
        'lookup.ip' => ['limit' => 15, 'window' => 900],
        'lookup.order' => ['limit' => 8, 'window' => 900],
    ],
],
'legal' => [
    'privacy_version' => '2026-11',
    'terms_version' => '2026-11',
],
```

- En local, las llaves reales van en `app/inc/config.local.php` (fuera de `public_html`, ignorado por git).
- `tools/keygen.php` imprime dos llaves base64 de 32 bytes y las instrucciones para pegarlas en `config.local.php`.
- `max_active_numbers_per_ip = 0` desactiva el tope; en producción se recomienda 30. Requiere `request_ip_hash`.

### 4.4 Rutas públicas

`public_html/index.php` delega en `app/web/router.php` cuando la ruta empieza por `/sorteos/` o es `/participaciones`.

| Método | Ruta | Handler | Notas |
| --- | --- | --- | --- |
| GET | `/sorteos/{slug}` | `PublicRaffleHandler@show` | Solo estados públicos; `noindex` si borrador/preview. |
| GET | `/sorteos/{slug}/participar` | `PublicRaffleHandler@participate` | Formulario; 404 si no admite ventas. |
| POST | `/sorteos/{slug}/participar` | `PublicRaffleHandler@reserve` | CSRF + rate limit + idempotencia. |
| GET | `/sorteos/{slug}/confirmacion/{orderCode}` | `PublicOrderHandler@show` | Requiere sesión autorizada. |
| GET | `/participaciones` | `PublicLookupHandler@form` | Formulario; muestra resultado temporal. |
| POST | `/participaciones` | `PublicLookupHandler@lookup` | CSRF + rate limit. |

- `/sorteos` (landing) sigue siendo la ruta actual y no cambia de handler.
- Los códigos se validan con `[A-Za-z0-9]{14}`; los slugs con `[a-z0-9-]{1,120}`.
- Rutas desconocidas bajo `/sorteos/` → 404 con la plantilla del sitio.

### 4.5 Rutas administrativas

Se agregan a `app/admin/module_routes.php`:

| Método | Ruta | Handler |
| --- | --- | --- |
| GET | `/participants` | `AdminParticipantHandler@index` |
| GET | `/participants/{id}` | `AdminParticipantHandler@show` |
| GET | `/orders` | `AdminOrderHandler@index` |
| GET | `/orders/{id}` | `AdminOrderHandler@show` |
| POST | `/orders/{id}/expire` | `AdminOrderHandler@expire` |
| POST | `/orders/{id}/extend` | `AdminOrderHandler@extend` |
| POST | `/orders/{id}/cancel` | `AdminOrderHandler@cancel` |

- Todas pasan por el router autenticado existente.
- `expire`, `extend` y `cancel` exigen CSRF y auditoría.
- `extend` acepta minutos entre 5 y 120; solo si la orden sigue `pending_payment`.
- No hay borrado de órdenes ni participantes.
- Se añaden entradas “Participantes” y “Órdenes” al menú del panel.

### 4.6 Flujo de reserva

1. `GET /sorteos/{slug}/participar`
   - Carga sorteo público, premios, bases vigentes y métodos activos.
   - Si no está `published`, fuera de ventana, sin bases o sin llaves → 404/503 con mensaje.
   - Genera `_token` y `_form_token`.
2. `POST /sorteos/{slug}/participar`
   - 1) CSRF, honeypot, rate limit.
   - 2) Normaliza y valida campos (sección 6).
   - 3) Verifica aceptaciones declaradas.
   - 4) `BEGIN`.
   - 5) `SELECT ... FROM raffles WHERE id=? FOR UPDATE`.
   - 6) Revalida estado, ventana, cupo, precio y bases.
   - 7) Crea o reutiliza participante (regla D10).
   - 8) Inserta orden con `idempotency_key` y `request_ip_hash`.
   - 9) Libera reservas vencidas del sorteo.
   - 10) Verifica límite por participante.
   - 11) Asigna números (`NumberAllocator`).
   - 12) Inserta dos `legal_acceptances` (bases y privacidad).
   - 13) Audita `participant.*`, `order.created` y `legal.accepted`.
   - 14) `COMMIT`.
   - 15) `session_regenerate_id(true)`, autoriza la orden en sesión, guarda el código de acceso solo para mostrarlo una vez y redirige a la confirmación.
3. `GET /sorteos/{slug}/confirmacion/{orderCode}`
   - Expira la orden si corresponde.
   - Verifica que la orden esté autorizada en sesión y pertenezca al slug.
   - Muestra números, estado, vencimiento, total, métodos e instrucciones.
   - Si no está autorizada: 404 uniforme.

### 4.7 Algoritmo de asignación

Pseudocódigo compatible con MariaDB 10.4:

```sql
START TRANSACTION;

-- 1. Serializar por sorteo
SELECT id, status, price_cents, currency, total_tickets,
       max_tickets_per_participant, sales_start_at, sales_end_at,
       draw_at, current_rules_version_id
FROM raffles
WHERE id = :raffle_id
FOR UPDATE;

-- 2. Liberar reservas vencidas de este sorteo
UPDATE raffle_numbers
SET status = 'available', order_id = NULL, reserved_until = NULL
WHERE raffle_id = :raffle_id
  AND status = 'reserved'
  AND reserved_until <= :now;

-- 3. Cupo ocupado
SELECT COUNT(*) FROM raffle_numbers
WHERE raffle_id = :raffle_id
  AND (status = 'paid' OR status = 'reserved');

-- 4. Reutilizar números libres (los más bajos primero)
SELECT id, number
FROM raffle_numbers
WHERE raffle_id = :raffle_id AND status = 'available'
ORDER BY number ASC
LIMIT :quantity
FOR UPDATE;

-- 5. Si faltan, insertar números nuevos
SELECT COALESCE(MAX(number), 0) AS max_number
FROM raffle_numbers
WHERE raffle_id = :raffle_id;

-- new_number = max_number + 1 ... max_number + missing
-- rechazar si new_number > total_tickets

INSERT INTO raffle_numbers
    (raffle_id, number, status, order_id, reserved_until)
VALUES
    (:raffle_id, :number, 'reserved', :order_id, :expires_at);

-- 6. Marcar reutilizados
UPDATE raffle_numbers
SET status = 'reserved', order_id = :order_id, reserved_until = :expires_at
WHERE id IN (:ids) AND status = 'available';

COMMIT;
```

Detalles:

- `MAX(number)` es seguro porque la fila del sorteo está bloqueada; no se usa `FOR UPDATE` con agregados.
- Se reutilizan huecos antes de crear números nuevos.
- `quantity` se limita a `min(max_per_order, max_tickets_per_participant, cupo_restante)`.
- La inserción de números nuevos se hace en lote; si hubiera colisión por unique, se reintenta la transacción completa hasta 2 veces.
- Los errores `1213` (deadlock) y `1205` (lock wait timeout) se reintentan con espera aleatoria de 20–80 ms; al tercer intento, `503` con mensaje de reintento.

### 4.8 Expiración

#### Perezosa

- `OrderService::reserve` ejecuta `expireRaffle($raffleId)` dentro de la transacción antes de contar cupo.
- `PublicOrderHandler@show` y `PublicLookupHandler@lookup` ejecutan `expireOrder($orderId)` si `expires_at <= now`.
- `OrderService` rechaza operar sobre una orden vencida.

#### Worker CLI

`tools/expire_reservations.php`:

```text
php tools/expire_reservations.php --limit=200
```

- Solo CLI (`PHP_SAPI === 'cli'`), si no `403`.
- Lock file `storage/locks/expire_reservations.lock` con `flock` no bloqueante para evitar solapamiento.
- Procesa en lotes por `id`:
  1. `SELECT id FROM orders WHERE status='pending_payment' AND expires_at <= NOW() ORDER BY expires_at LIMIT :limit`.
  2. Por orden: `BEGIN`, `SELECT ... FOR UPDATE`, revalidar estado y fecha, `UPDATE orders SET status='expired'`, liberar `raffle_numbers`, auditar `order.expired`, `COMMIT`.
- Idempotente: ejecutarlo dos veces no cambia nada.
- No toca órdenes `approved`, `proof_received`, `under_review` ni `paid`.
- Cron sugerido: cada minuto.
- Códigos de salida: `0` éxito, `1` error de configuración/BD, `2` lock ocupado.

### 4.9 Consulta de orden

1. `GET /participaciones` muestra formulario (código de orden + código de acceso).
2. `POST` valida CSRF y rate limit.
3. Busca por `orders.public_code`; si no existe, error uniforme.
4. Normaliza el código de acceso y compara `hash_equals`.
5. Expira la orden si corresponde.
6. Guarda en sesión `lookup_order_id` con expiración de 5 minutos y redirige a `GET /participaciones`.
7. El `GET` muestra el resultado una vez y limpia el dato de sesión.
8. Se muestra un botón “Ver confirmación” que autoriza la orden en sesión (sin volver a pedir códigos durante 2 h).

### 4.10 Detalle público

- `GET /sorteos/{slug}`:
  - nombre, resumen, descripción, portada, premios con posición, valor estimado, fechas, precio, cupo, máximo por persona, bases vigentes y métodos de pago activos;
  - CTA “Participar” si está en ventana; si no, estado “Ventas cerradas” o “Próximamente”;
  - aviso: “El ticket se emite solo cuando el pago es aprobado”;
  - enlace a `/sorteos` y a la consulta `/participaciones`;
  - `noindex` si es preview de borrador.
- No muestra participantes, números vendidos ni datos personales. Puede mostrar “Números disponibles: X” calculado como `total_tickets - ocupados` (sin listar números).
- La landing `/sorteos` muestra una **tarjeta por sorteo activo** (`published`, `paused`, `sales_closed`) generada desde la base de datos: imagen, estado, nombre, resumen, premio principal, premios adicionales, precio, máximo por persona, fechas y CTA. No depende de la diapositiva seleccionada en el carrusel; el carrusel sigue sincronizando solo la cuenta regresiva y los datos del registro. Si no hay sorteos activos, se usa la lista pública disponible como respaldo.
- Reutiliza `public_html/css/sorteos.css` y `public_html/js/sorteos.js`; no se agregan estilos inline.

---

## 5. Validaciones

### 5.1 Reglas de campo

| Campo | Regla | Normalización | Mensaje |
| --- | --- | --- | --- |
| `full_name` | Obligatorio, `person_name`, 5–160, al menos 2 letras y 2 palabras | Colapsa espacios, quita controles | “Ingresa tu nombre y apellido.” |
| `document_type` | Obligatorio, solo `DNI` | Mayúsculas | “Selecciona el tipo de documento.” |
| `document_number` | Obligatorio, exactamente 8 dígitos | Quita puntos, guiones y espacios | “Ingresa un DNI válido de 8 dígitos.” |
| `phone` | Obligatorio, `9XXXXXXXX` | Quita separadores | “Ingresa un celular de 9 dígitos que empiece por 9.” |
| `email` | Obligatorio, máx. 160, formato válido | Minúsculas y `trim` | “Ingresa un correo válido.” |
| `quantity` | Obligatorio, entero 1..`min(max_per_order, max_per_person, cupo)` | Dígitos | “Elige una cantidad válida.” |
| `is_adult` | Obligatorio (`1`) | Booleano | “Debes declarar que eres mayor de edad.” |
| `accept_rules` | Obligatorio (`1`) | Booleano | “Debes aceptar las bases del sorteo.” |
| `accept_privacy` | Obligatorio (`1`) | Booleano | “Debes aceptar la política de privacidad.” |
| `_token` | CSRF válido | — | “La sesión expiró. Recarga la página.” |
| `_form_token` | 32–64 caracteres hex | — | “El formulario expiró. Recarga la página.” |
| `website` (honeypot) | Debe estar vacío | — | Se ignora silenciosamente y se registra el intento. |

Extender `FieldRules`:

- `email`: patrón compatible con HTML5, longitud 6–160, sin espacios.
- `access_code`: `[A-Z0-9]{8,12}`, se normaliza a mayúsculas.
- `order_code`: `[A-Za-z0-9]{14}`.
- `quantity`: entero positivo (reutiliza `integer`).
- `dni`: `[0-9]{8}` (más estricto que `document`).

Los atributos `data-pattern`, `data-message`, `inputmode` y el saneamiento del cliente se generan con `field_attrs()`/`FieldRules` para mantener una sola fuente de verdad.

### 5.2 Reglas de negocio en servidor

| ID | Regla | Respuesta |
| --- | --- | --- |
| V1 | El sorteo existe y es visible públicamente. | 404 |
| V2 | `status === 'published'`. | 409 “Este sorteo no está recibiendo participaciones.” |
| V3 | `sales_start_at <= now <= sales_end_at` (si están definidas). | 409 “Las ventas están cerradas.” |
| V4 | `draw_at` nulo o futuro. | 409 |
| V5 | `price_cents > 0` para sorteos pagados; si es 0, se permite pero se muestra “Gratis”. | 409 si el precio es inconsistente. |
| V6 | `1 <= quantity <= max_per_order`. | 422 |
| V7 | Números activos del participante + quantity <= `max_tickets_per_participant`. | 422 “Superas el máximo de números por persona.” |
| V8 | Ocupados + quantity <= `total_tickets`. | 409 “Ya no quedan números suficientes.” |
| V9 | `total_amount_cents = price_cents * quantity` calculado en servidor. | 422 si el cliente envía total. |
| V10 | Bases publicadas vigentes (`current_rules_version_id` y `status='published'`). | 409 “La participación aún no está habilitada.” |
| V11 | Texto de privacidad disponible y con hash. | 503 |
| V12 | DNI no bloqueado. | 403 genérico |
| V13 | Si el DNI existe, celular o correo deben coincidir. | 422 “El documento ya está registrado. Usa el celular o correo con el que participaste.” |
| V14 | La orden solo se considera vigente si `expires_at > now`. | 409 |
| V15 | Un número no puede repetirse en el sorteo. | Reintento / 409 |
| V16 | La cantidad total de la orden no puede exceder el cupo restante. | 409 |

### 5.3 Validación en cliente

- `novalidate` en el formulario y validación propia (patrón existente de `admin.js`) para mensajes consistentes.
- Al enviar:
  - deshabilitar el botón y mostrar “Procesando…”;
  - no permitir doble envío;
  - mostrar errores junto al campo y un resumen al inicio;
  - enfocar el primer error.
- Contador de total en vivo: `precio × cantidad`, formateado con `Intl.NumberFormat('es-PE')`.
- Contador de vencimiento solo en la confirmación, leyendo `expires_at` en ISO 8601.
- Sin JavaScript, el formulario sigue funcionando; el servidor recalcula todo.

### 5.4 Mensajes y accesibilidad

- Todos los mensajes en español neutro, sin jerga técnica.
- `aria-invalid`, `aria-describedby`, `role="alert"` en el resumen.
- Etiquetas visibles, no solo placeholders.
- Contraste y foco visibles; reutilizar tokens del sitio público.

---

## 6. Concurrencia, idempotencia y consistencia

### 6.1 Escenarios

| Escenario | Resultado esperado |
| --- | --- |
| Dos reservas simultáneas del mismo sorteo | Se serializan por la fila del sorteo; números disjuntos. |
| Dos peticiones del mismo formulario | La segunda encuentra `idempotency_key` y redirige a la misma orden. |
| Reserva y expiración simultáneas | La expiración bloquea la orden; la reserva no puede reutilizar números de una orden que aún no expiró. |
| Worker de expiración ejecutado dos veces | Solo la primera cambia estados; la segunda no encuentra filas. |
| Reserva cuando quedan menos números que la cantidad | 409 y rollback total; no se crea orden ni participante. |
| Fallo al insertar aceptaciones legales | Rollback total; no queda orden huérfana. |
| Dos participantes con el mismo DNI | `UNIQUE(document_number_hash)`; el segundo reutiliza o recibe error de coincidencia. |
| Colisión de código público | Reintento de generación (máx. 5). |
| Deadlock | Reintento acotado; luego 503. |

### 6.2 Reglas de transacción

- Una reserva = **una transacción**. No se llama a servicios externos dentro de ella.
- Lock order: `raffles` → `participants` → `orders` → `raffle_numbers` → `audit_chain`.
- La transacción debe durar menos de 300 ms en condiciones normales.
- `PDO::ATTR_EMULATE_PREPARES = false` ya está configurado; se usan prepared statements reales.
- No se usan transacciones anidadas; los servicios reciben el `PDO` y asumen que el llamador controla la transacción cuando corresponde.
- `NumberAllocator` no abre transacciones: recibe una ya abierta y falla si `!$pdo->inTransaction()`.

### 6.3 Tope por IP (opcional, recomendado en producción)

- `order.request_ip_hash = HMAC("ip|{REMOTE_ADDR}", hash_key)`.
- Si `max_active_numbers_per_ip > 0`, antes de asignar se cuenta:
  - números `reserved` vigentes + `paid` de órdenes con el mismo `request_ip_hash` en el sorteo;
  - si supera el tope, 429 “Demasiadas reservas activas desde esta conexión.”
- `ClientIp` usa `REMOTE_ADDR` por defecto y solo acepta `CF-Connecting-IP`/`X-Forwarded-For` si la IP remota está en `security.trusted_proxies`.
- En local el tope está desactivado (`0`) para no estorbar las pruebas.

### 6.4 Recuperación

- Si el usuario pierde la sesión antes de ver el código de acceso, puede recuperarlo contactando al admin; el admin no ve el código (solo el hash), pero puede extender la reserva o generar una nueva orden tras anular la anterior.
- Fase 5 añadirá el reenvío manual por WhatsApp; no se implementa aquí.
- No se guarda el código de acceso en logs ni en la base en claro; si se pierde, se regenera con una acción administrativa auditada (prevista, no UI en Fase 4).

---

## 7. Pruebas

### 7.1 Enfoque

- Sin PHPUnit ni dependencias nuevas: runner propio en `tools/tests/`.
- `bootstrap.php` común:
  - define `APP_ROOT`;
  - carga `app/bootstrap.php`;
  - expone `check()`, `section()`, `summary()` y `assertThrows()`;
  - crea un directorio temporal para rate limit;
  - configura llaves de prueba fijas;
  - conecta a `radiolpsorteos_test` (base separada).
- `run_all.php` ejecuta cada archivo en un proceso hijo y devuelve código distinto de cero si algo falla.

### 7.2 Base de datos de pruebas

- Nombre: `radiolpsorteos_test`.
- `tools/tests/setup_db.php`:
  - crea la base si no existe;
  - ejecuta todas las migraciones en orden;
  - trunca tablas entre suites (con `SET FOREIGN_KEY_CHECKS=0`).
- `app/inc/config.php` acepta `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` por entorno para que las pruebas no toquen la base local.
- Nunca se ejecutan pruebas contra `radiolpsorteos` de desarrollo.
- Fixtures:
  - sorteo `published` con precio 500 (= S/ 5.00), 50 números, máximo 5 por persona;
  - sorteo `draft`;
  - sorteo `sales_closed`;
  - bases publicadas;
  - dos métodos de pago activos.

### 7.3 Matriz de pruebas unitarias

#### `tools/tests/crypto.php`

| Caso | Esperado |
| --- | --- |
| HMAC del mismo DNI es estable | Igual en dos llamadas |
| HMAC de DNI distinto cambia | Distinto |
| HMAC incluye dominio y tipo | `DNI|12345678` ≠ `RUC|12345678` |
| Cifrado/descifrado AES-256-GCM | Texto original |
| Texto cifrado alterado | Excepción |
| Versión de llave desconocida | Excepción |
| Llave faltante o corta | `CryptoUnavailableException` |
| Código de acceso ligado a la orden | Mismo código en otra orden produce hash distinto |
| Normalización del código | Minúsculas/guiones equivalentes |

#### `tools/tests/rate_limiter.php`

| Caso | Esperado |
| --- | --- |
| Primeros N intentos | Permitidos |
| Intento N+1 | Bloqueado con `retry_after` |
| Ventana vencida | Permitido de nuevo |
| Buckets distintos | No se afectan |
| Claves distintas | No se afectan |
| Archivo corrupto | Se reinicia el contador, no rompe |
| Concurrencia básica con `flock` | No pierde incrementos |

#### `tools/tests/participant_service.php`

| Caso | Esperado |
| --- | --- |
| Nombre válido con tildes | Crea participante |
| Nombre con números/símbolos | 422 |
| DNI de 7/9 dígitos | 422 |
| Celular que no empieza por 9 | 422 |
| Correo inválido | 422 |
| Menor de edad declarado | 422 |
| DNI nuevo | Fila nueva, hash y cifrado presentes |
| DNI repetido con mismo celular | Reutiliza la fila |
| DNI repetido con mismo correo | Reutiliza la fila |
| DNI repetido con celular/correo distintos | 422 sin revelar datos |
| DNI repetido con distinto tipo | No reutiliza |
| Email se normaliza a minúsculas | Guardado en minúsculas |
| Nunca se guarda DNI en claro | `document_number_encrypted` no contiene el número |

#### `tools/tests/number_allocator.php`

| Caso | Esperado |
| --- | --- |
| Sorteo vacío, 3 números | 1, 2, 3 |
| Segunda orden | 4, 5 |
| Liberar 2 y 4, nueva orden de 2 | Reutiliza 2 y 4 |
| Cantidad > cupo restante | Excepción, sin cambios |
| Cantidad > `total_tickets` | Excepción |
| `max_per_person` | Excepción antes de asignar |
| Números de otra orden | No se tocan |
| Número ya `paid` | No se reutiliza |
| Colisión forzada | Reintento y error controlado |
| Rollback | No quedan números reservados |

#### `tools/tests/order_service.php`

| Caso | Esperado |
| --- | --- |
| Total = precio × cantidad | Centavos exactos |
| Precio 0 | Total 0, orden creada |
| Cantidad 0 o negativa | 422 |
| Sorteo `draft`/`paused`/`sales_closed` | 409 |
| Ventas futuras/pasadas | 409 |
| Sin bases publicadas | 409 |
| Sin aceptación de bases/privacidad | 422 |
| Aceptación correcta | Dos filas con versión, hash, IP y UA |
| Orden vence en 30 min | `expires_at` correcto |
| Configuración de 45 min | `expires_at` correcto |
| Doble envío con mismo token | Una sola orden |
| Token reutilizado con otro sorteo | 409 |
| Orden expirada no se confirma | 409 |
| Orden ajena no se autoriza en sesión | 404 |
| Código de acceso incorrecto | Error uniforme |
| Código de acceso correcto | Autoriza |
| `order.created` auditado | Una entrada, sin PII |
| Fallo de auditoría | Rollback de toda la reserva |

#### `tools/tests/expiry_service.php`

| Caso | Esperado |
| --- | --- |
| Orden vencida | `expired`, números `available` |
| Orden vigente | Sin cambios |
| Orden `approved` | Sin cambios |
| Ejecutar dos veces | Idempotente |
| Lote con `limit` | Solo procesa N |
| Worker sin CLI | 403 |
| Lock ocupado | Salida 2 |
| Auditoría | `order.expired` con actor `system` |

#### `tools/tests/field_rules.php` (extender)

- Correo válido/inválido, mayúsculas y espacios.
- DNI estricto de 8.
- Código de acceso con guiones/minúsculas.
- Código de orden de 14.
- Cantidad entera.

### 7.4 Prueba de concurrencia

`tools/tests/concurrency.php`:

1. Crea un sorteo de prueba con 50 números, máximo 5 por persona.
2. Lanza 10 procesos (`proc_open`) de `reserve_worker.php`; cada uno intenta 8 reservas con DNI distinto.
3. Espera a todos y agrega resultados.
4. Verifica:
   - exactamente 50 números `reserved`;
   - 30 rechazos por cupo;
   - ningún número duplicado;
   - ninguna orden con más de 5 números;
   - ninguna orden con total incorrecto;
   - sin errores 500 ni deadlocks no controlados;
   - la suma de `quantity` de órdenes creadas es 50.
5. Repite con 10 procesos y el mismo DNI para verificar el límite por persona.
6. Limpia la base de pruebas.

También se incluye una prueba HTTP manual documentada con `curl`/PowerShell para CSRF, cookie de sesión, doble envío y consulta.

### 7.5 Criterio de “pruebas verdes”

- `php tools/tests/run_all.php` termina con código 0.
- Al menos 70 aserciones en total.
- La prueba de concurrencia pasa 3 veces consecutivas.
- No hay avisos de PHP (`E_WARNING`, `E_NOTICE`, `E_DEPRECATED`) en la salida.
- Cobertura funcional de: validaciones, DNI/cifrado, idempotencia, límites, cupo, expiración, autorización y auditoría.

---

## 8. Orden de implementación

### M0 — Base de seguridad (1 día)

- [ ] `Clock`, `Crypto`, `ClientIp`, `RateLimiter`.
- [ ] `tools/keygen.php`.
- [ ] Configuración `security`, `participation`, `legal`.
- [ ] Pruebas `crypto.php`, `rate_limiter.php`.
- [ ] Extender `FieldRules` (email, dni, access_code, order_code, quantity) y sus pruebas.

### M1 — Esquema y auditoría (0.5 día)

- [ ] Migración `20261117100000_phase4_participation.sql`.
- [ ] `AuditService` con `audit_chain`.
- [ ] Pruebas de cadena de auditoría y migración en base de pruebas.

### M2 — Dominio (1.5 días)

- [ ] `ParticipantRepository`, `ParticipantValidator`, `ParticipantService`.
- [ ] `RaffleNumberRepository`, `NumberAllocator`.
- [ ] `OrderRepository`, `OrderAccess`, `OrderService`.
- [ ] `ReservationExpiryService`.
- [ ] Pruebas de participantes, números, órdenes y expiración.

### M3 — Web pública (1.5 días)

- [ ] `app/web/router.php` y `helpers.php`.
- [ ] Detalle `/sorteos/{slug}`.
- [ ] Formulario y reserva.
- [ ] Confirmación autorizada por sesión.
- [ ] Vistas, CSS y JS.
- [ ] `noindex`, `no-store`, canonical y Open Graph del detalle.

### M4 — Consulta y panel (1 día)

- [ ] `/participaciones` (GET/POST).
- [ ] Listados y detalles de participantes y órdenes.
- [ ] Extender, expirar y cancelar con auditoría.
- [ ] Enlaces en el menú y navegación.

### M5 — Expiración y concurrencia (0.5 día)

- [ ] `tools/expire_reservations.php`.
- [ ] Documentar cron.
- [ ] Prueba de concurrencia.
- [ ] Ajustes de rendimiento e índices.

### M6 — Cierre (0.5 día)

- [ ] QA manual desktop/móvil.
- [ ] Revisión de logs (sin PII).
- [ ] Lighthouse de `/sorteos` y detalle.
- [ ] Actualizar `specs/009-fase-1/avance.md` o crear `specs/010-fase-4/avance.md`.
- [ ] Actualizar `specs/CHANGELOG.md`.
- [ ] Checklist de Gate 4.

Total estimado: **5–6 días de trabajo efectivo**, sin contar la revisión legal (Gate 0).

---

## 9. Criterios de aceptación (Gate 4)

- [ ] Dos personas no pueden recibir el mismo número, ni siquiera con concurrencia.
- [ ] La suma de números reservados/pagados nunca supera `total_tickets`.
- [ ] El límite por persona se valida en servidor y se aplica por DNI.
- [ ] La reserva vence a los 30 minutos (configurable) y libera números.
- [ ] El worker de expiración es idempotente y no toca órdenes pagadas.
- [ ] El cliente puede consultar su orden sin contraseña usando código de orden + código de acceso.
- [ ] No se emite ningún ticket sin pago aprobado.
- [ ] No se expone DNI, correo, celular ni códigos en URLs, listados públicos o logs.
- [ ] El DNI se guarda hasheado y cifrado; nunca en claro.
- [ ] Todas las acciones críticas quedan auditadas con cadena íntegra.
- [ ] Un doble envío no crea dos órdenes.
- [ ] La confirmación solo es accesible desde la sesión que creó la orden.
- [ ] El panel muestra participantes y órdenes con datos enmascarados y detalle autorizado.
- [ ] Las pruebas unitarias y de concurrencia pasan.
- [ ] `/sorteos` mantiene Lighthouse ≥ 95 en rendimiento y 100 en accesibilidad/SEO.
- [ ] Las bases y la privacidad aceptadas quedan versionadas con hash, IP, UA y fecha.

---

## 10. Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| MariaDB 10.4 sin `SKIP LOCKED` | Reservas serializadas por sorteo | Bloqueo de fila del sorteo; transacciones cortas. |
| Pérdida de `hash_key` | No se pueden buscar duplicados ni verificar códigos | Respaldo seguro; documentar que la rotación requiere reindexado. |
| Pérdida de llave de cifrado | No se puede ver el DNI | Mapa de versiones y respaldo; el hash sigue funcionando. |
| Ataque de acaparamiento de números | Cupo agotado sin pagos | Rate limit por IP/DNI + tope opcional de números activos por IP + expiración de 30 min. |
| DNI ajeno usado por un tercero | Órdenes a nombre de otro | Reutilización solo con celular/correo coincidente; auditoría de rechazos. |
| Sesión perdida antes de ver el código | Usuario no puede consultar | Recuperación manual por admin; Fase 5 reenvío por WhatsApp. |
| Cron no configurado | Números retenidos | Expiración perezosa en cada reserva/consulta. |
| Hora del servidor incorrecta | Vencimientos y ventanas erradas | `America/Lima` en bootstrap; validar `NOW()` de MySQL y PHP. |
| Enumeración de códigos | Acceso a órdenes ajenas | Código de acceso de 10 caracteres + rate limit + respuesta uniforme. |
| Auditoría bifurcada | Cadena inválida | `audit_chain` con bloqueo de fila. |
| Bases desactualizadas | Aceptación inválida | `current_rules_version_id` y hash; bloqueo si no hay versión publicada. |

---

## 11. Bloqueos externos (Gate 0)

Fase 4 se puede desarrollar y probar con datos ficticios, pero **no se debe abrir al público ni cobrar** hasta cerrar:

- revisión legal de bases, privacidad, términos y Libro de Reclamaciones;
- cuentas reales Yape/Plin/BCP y titularidad;
- premios definitivos y valor estimado;
- política de reembolso, no reclamo y sobrepago;
- autorización de uso de imagen/nombre del ganador si aplica;
- infraestructura de producción, cron y respaldos.

El plan asume que el texto de privacidad existe en `app/web/legal/privacidad-v1.md` y que las bases se cargan en `raffle_rules_versions`. Si el área legal entrega otro formato, se reemplaza el contenido y se sube la versión, sin cambiar el código.

---

## 12. Decisiones a aprobar

1. **Pool bajo demanda** con bloqueo de sorteo y `UNIQUE (raffle_id, number)` (D1/D2).
2. **DNI con HMAC + AES-256-GCM** y llaves en `config.local.php` (D3).
3. **Código de acceso mostrado una vez** y confirmación autorizada por sesión (D4/D5).
4. **Reutilización de participante** solo con celular o correo coincidente (D10).
5. **`audit_chain`** para serializar la auditoría (D9).
6. **Privacidad versionada en archivo** (`privacidad-v1.md`) hasta que exista la página legal de Fase 3.
7. **Panel mínimo**: listados, detalle, extender, expirar y cancelar; la cola de pagos llega en Fase 5.
8. **Tope opcional por IP** desactivado en local y recomendado en producción (30 números activos).

Si se aprueban, el siguiente paso es implementar M0 y M1, luego M2 con sus pruebas antes de tocar las vistas públicas.

---

## 13. Anexos

### 13.1 Ejemplo de inserción de orden

```sql
INSERT INTO orders
    (public_code, access_code_hash, idempotency_key, raffle_id, participant_id,
     quantity, unit_price_cents, total_amount_cents, currency, status,
     expires_at, request_ip_hash)
VALUES
    (:public_code, :access_hash, :idempotency_key, :raffle_id, :participant_id,
     :quantity, :unit_price, :total, 'PEN', 'pending_payment',
     :expires_at, :ip_hash);
```

### 13.2 Ejemplo de aceptación legal

```php
$acceptances = [
    [
        'document_type' => 'rules',
        'document_version' => (string) $rules['version'],
        'document_hash' => $rules['content_hash'],
        'rules_version_id' => (int) $rules['id'],
    ],
    [
        'document_type' => 'privacy',
        'document_version' => (string) config('legal.privacy_version'),
        'document_hash' => hash('sha256', $privacyContent),
        'rules_version_id' => null,
    ],
];
```

### 13.3 Consulta de límite por participante

```sql
SELECT COUNT(*) AS active_numbers
FROM raffle_numbers rn
JOIN orders o ON o.id = rn.order_id
WHERE rn.raffle_id = :raffle_id
  AND o.participant_id = :participant_id
  AND (
      rn.status = 'paid'
      OR (rn.status = 'reserved' AND rn.reserved_until > :now)
  );
```

### 13.4 Consulta de cupo ocupado

```sql
SELECT COUNT(*) AS occupied
FROM raffle_numbers
WHERE raffle_id = :raffle_id
  AND status IN ('reserved', 'paid');
```

### 13.5 Checklist de QA manual

- [ ] Formulario vacío: muestra todos los errores y no envía.
- [ ] DNI de 7 dígitos: error claro.
- [ ] Celular de 8 dígitos: error claro.
- [ ] Correo inválido: error claro.
- [ ] Cantidad 0 y cantidad mayor al límite: error claro.
- [ ] Sin aceptar bases: error; con aceptación: crea orden.
- [ ] Doble clic en “Participar”: una sola orden.
- [ ] Confirmación muestra números, total, vencimiento y métodos.
- [ ] Recargar la confirmación sigue funcionando dentro de la sesión.
- [ ] Abrir la confirmación en otro navegador: 404.
- [ ] Consulta con código correcto: muestra la orden.
- [ ] Consulta con código incorrecto: mensaje uniforme.
- [ ] Esperar 30 minutos o forzar expiración: números liberados.
- [ ] Panel: listado de órdenes, detalle, extender y expirar.
- [ ] Panel: participante muestra DNI descifrado solo en detalle.
- [ ] Logs sin PII.
- [ ] Móvil: formulario usable, sin scroll horizontal.
