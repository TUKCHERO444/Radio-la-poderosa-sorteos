# 003 — Sorteos: análisis de módulos, viabilidad y relación con la web pública

- **Estado:** análisis y documentación. **No implementado.**
- **Fecha:** 2026-11-16
- **Alcance:** evaluar los 12 módulos recomendados para el sistema de sorteos, contrastar su utilidad y viabilidad con el proyecto actual, definir su relación con la web pública por desarrollar y dejar las preguntas que deben responderse antes de codificar.
- **Depende de:** `specs/001-panel-admin/`, `specs/002-panel-ui-kit/`, `DOCUMENTACION.md`.
- **Restricción de esta fase:** solo documentación. No se modifica código, esquema de base de datos ni infraestructura.

> **Aviso legal:** este documento es un análisis técnico, funcional y de viabilidad. No constituye asesoría legal, tributaria, contable ni financiera. Las referencias a normas peruanas son puntos de validación; deben confirmarse con un abogado y un contador antes de publicar un sorteo o recibir dinero del público.

---

## 1. Resumen ejecutivo

1. Los 12 módulos son útiles, pero **no son un MVP**. El sistema puede y debe empezar con un subconjunto pequeño, seguro y auditable.
2. El proyecto actual es una **web de radio** con un panel administrativo de usuarios. No existe todavía lógica de sorteos, participantes, tickets, pagos ni páginas públicas dinámicas.
3. La primera decisión no es técnica: **¿el sorteo es gratuito o con ticket pagado?** Esa respuesta cambia el alcance legal, los pagos, la identidad, la facturación, el antifraude y la operación.
4. Si hay pago, el orden recomendado es: **primero cumplimiento y cuentas de comercio; después pasarela; nunca al revés**.
5. El módulo más crítico es el **motor del sorteo**. Debe ser verificable, reproducible y sin intervención manual sobre el resultado. La recomendación es un esquema **commit-reveal** con CSPRNG, hash público antes del sorteo y semilla revelada después.
6. La **transparencia pública** no es un adorno: es parte del producto y de la confianza. Debe incluir bases versionadas, ganadores, lista anonimizada, semilla/hash y acta.
7. La **gestión de participantes** debe separarse de la tabla `users` del panel. Mezclar administradores con participantes complica seguridad, privacidad y permisos.
8. La **verificación de identidad** no debería pedirse toda al registrarse. Conviene minimizar datos al inicio y verificar DNI/identidad al momento de reclamar un premio, salvo que la ley o el tipo de sorteo exijan otra cosa.
9. **Pagos, OTP, RENIEC, WhatsApp, SUNAT, notaría y transmisión en vivo** son módulos condicionales. Su viabilidad depende de contratos, costos, volúmenes y obligaciones legales, no solo de código.
10. El hosting actual tiene restricciones relevantes: **PHP 8.0.30 EOL**, sin Composer en producción, sin `fileinfo`, sin cola/cron documentados, sin correo transaccional ni SMS/WhatsApp. Antes de pagos o datos sensibles, hay que resolver el upgrade de PHP y la operación.
11. La web pública actual es una plantilla de Sitejet con estilos y scripts inline. Para sorteos conviene una **sección pública nueva y limpia**, reutilizando la identidad de marca, no mezclando la lógica transaccional con la plantilla original.
12. La recomendación es avanzar por fases: **Fase 0 decisiones + legal + infraestructura**, **Fase 1 sorteo gratuito o con pago manual**, **Fase 2 pagos/notificaciones/entrega**, **Fase 3 pasarela/SUNAT/OTP/WhatsApp/AML** según corresponda.
13. Antes de escribir código deben responderse las preguntas bloqueantes de la sección 11. Sin esas respuestas, cualquier diseño corre el riesgo de quedar fuera de alcance, ser legalmente inviable o no poder operar.
14. Este documento **no implementa nada**. Su objetivo es que la siguiente especificación se apruebe con información completa.

---

## 2. Línea base: qué existe hoy y qué limita el diseño

### 2.1 Web pública actual

- El front controller es `public_html/index.php`.
- Las rutas actuales son estáticas: `/`, `/subpage`, `/legal-notice`, `/privacy` y algunas colecciones de plantillas Sitejet.
- La home (`app/templates/pages/home.html`) es una página de **radio**: reproductor, programación, contacto, redes sociales, PWA y service worker.
- No hay listado de sorteos, detalle de sorteo, checkout, cuenta de participante, ganadores ni verificación pública.
- La home tiene estilos y scripts inline heredados de Sitejet. No es una base ideal para formularios, pagos o datos personales.
- El service worker (`public_html/sw.js`) cachea activos del sitio público. Ya excluye `/admin` y `/assets/admin/`; las nuevas rutas dinámicas de sorteos deberán excluirse o usar estrategia network-first.

### 2.2 Panel administrativo actual

- Login, sesiones, CSRF, throttle, dashboard y CRUD de usuarios.
- UI kit nativo (CSS modular, tema claro/oscuro, tablas, formularios, diálogos) listo para reutilizar en los módulos nuevos.
- No hay módulos de sorteos, productos, pagos, reportes ni auditoría funcional.
- La tabla `users` corresponde a **administradores**, no a participantes públicos.
- No hay roles granulares: hoy existe `admin` y `editor`, pero no un sistema de permisos por módulo.

### 2.3 Base de datos actual

Solo existen:

- `users`: administradores del panel.
- `settings`: clave/valor para personalización futura.

No hay tablas para sorteos, premios, participantes, tickets, reservas, pagos, sorteos ejecutados, ganadores, entregas, notificaciones, reclamaciones ni auditoría.

### 2.4 Stack y restricciones del hosting

| Elemento | Situación actual | Implicación |
| --- | --- | --- |
| PHP | 8.0.30 en producción (EOL) | Riesgo de seguridad; pagos y datos sensibles exigen upgrade. |
| Framework | Ninguno; PHP nativo | Más control, pero hay que construir migraciones, validación y jobs. |
| Composer | Disponible en local, no en producción | SDKs de pasarelas pueden no ser viables; usar REST/cURL o vendorizar. |
| Base de datos | MySQL/MariaDB, PDO, utf8mb4 | Adecuada para el MVP; cuidado con concurrencia y transacciones. |
| `fileinfo` | No disponible en producción | Subidas de imágenes/comprobantes requieren validación alternativa o habilitarlo. |
| `curl` / `pdo_mysql` | Disponibles | Permiten integraciones REST y pagos por API. |
| Cron / colas | No documentados | Reservas expiradas, correos y conciliaciones necesitan un worker programado. |
| Correo | No configurado | Verificación de correo y notificaciones requieren SMTP/proveedor. |
| SMS / WhatsApp | No configurados | OTP y avisos por WhatsApp son fases posteriores. |
| Hosting | cPanel/Apache compartido | No apto para procesos largos, streaming propio ni workers permanentes. |
| Almacenamiento | `app/storage` privado | Adecuado para evidencias si se protege y se valida. |
| Respaldos | No documentados | Hay que definir respaldo, cifrado y prueba de restauración. |
| Roles | `admin` / `editor` sin permisos finos | Se necesita RBAC antes de módulos sensibles. |

### 2.5 Qué se puede reutilizar

- Front controller y organización `app/` + `public_html/`.
- PDO, sesiones, CSRF, `password_hash`, `password_verify`, prepared statements.
- UI kit del panel: tablas, formularios, alertas, diálogos, badges, layout.
- `settings` para branding y parámetros no transaccionales.
- Service worker con exclusión de rutas privadas.
- Helpers de escape y URLs.

### 2.6 Qué no se debe reutilizar tal cual

- La tabla `users` para participantes.
- La `settings` para datos transaccionales.
- La home de Sitejet para checkout o formularios con datos personales.
- El esquema actual sin migraciones.
- El PHP 8.0 para pagos o datos sensibles.
- El `schema.sql` aplicado manualmente como única estrategia de evolución.

---

## 3. Decisiones que condicionan todos los módulos

Antes de elegir arquitectura hay que cerrar estas decisiones. Cada una cambia el alcance y el orden de implementación.

| Decisión | Opciones | Impacto | ¿Bloquea el MVP? |
| --- | --- | --- | --- |
| Modelo del sorteo | Gratuito / pagado / mixto (promocional + venta) | Legal, pagos, tickets, identidad, facturación, antifraude | Sí |
| Tipo de premio | Efectivo / bienes / servicios / viajes / mixto | Entrega, impuestos, AML, evidencia, logística | Sí |
| Autorización | ¿Requiere resolución/autorización? ¿De qué autoridad? | Legal, fechas, contenido público | Sí |
| Entidad organizadora | Persona natural / empresa / RUC / marca | Contratos, facturación, responsabilidad | Sí |
| Participantes | Cuenta obligatoria / invitado con correo / presencial | UX, privacidad, verificación, conversión | Sí |
| Identidad | DNI al registrar / DNI al reclamar / RENIEC / OTP | Costo, fricción, privacidad, antifraude | Condicional |
| Numeración | Ticket numerado / participación sin número / número elegido | Concurrencia, UX, lógica de sorteo | Sí |
| Pago | Sin pago / manual / pasarela / Yape-Plin | Conciliación, SUNAT, fraude, soporte | Condicional |
| Motor | CSPRNG simple / commit-reveal / beacon externo | Confianza, complejidad, auditoría | Sí |
| Transparencia | Solo ganadores / lista anonimizada / verificación completa | Privacidad, confianza, esfuerzo | Recomendado |
| Notificaciones | Correo / SMS / WhatsApp / PWA | Costo, entregabilidad, consentimiento | Condicional |
| Operación | Equipo, horarios, entrega, soporte | SLA, capacidad, costos | Sí |
| Infraestructura | cPanel actual / VPS / nube | Pagos, workers, respaldos, escalabilidad | Sí |

**Regla:** ninguna decisión de código debe tomarse antes de saber si el sorteo es gratuito o pagado y si requiere autorización. Son las dos variables que más cambian el sistema.

---

## 4. Mapa de módulos y prioridad recomendada

| # | Módulo | Utilidad | Viabilidad actual | Prioridad | Fase sugerida |
| --- | --- | --- | --- | --- | --- |
| 1 | Gestión de sorteos | Núcleo del negocio | Alta | Crítica | 1 |
| 2 | Usuarios y participantes | Necesario para participar | Alta con alcance mínimo | Crítica | 1 |
| 3 | Tickets / participaciones | Necesario si hay cupos/números | Alta con control transaccional | Crítica | 1 |
| 4 | Pagos | Solo si se cobra | Condicional | Alta si es pagado | 2–3 |
| 5 | Motor del sorteo | Confianza y validez | Alta con commit-reveal | Crítica | 1 |
| 6 | Transparencia pública | Confianza y cumplimiento | Alta | Crítica | 1 |
| 7 | Entrega de premios | Cierre del ciclo | Media/operativa | Alta | 1–2 |
| 8 | Cumplimiento y legal | Condición de lanzamiento | Depende de abogado/contador | Crítica | 0–1 |
| 9 | Seguridad y antifraude | Protege todo | Media; hay base | Crítica | 0–1 |
| 10 | Notificaciones | Comunicación esencial | Alta para correo; media para SMS/WhatsApp | Alta | 1–3 |
| 11 | Reportes y analítica | Gestión y contabilidad | Alta para CSV; media para BI | Media | 2 |
| 12 | Soporte | Reclamos y atención | Alta para formulario; media para chat | Media | 2–3 |

**Lectura:** “Crítica” no significa “toda en la fase 1”; significa que no se puede lanzar sin una versión mínima. “Condicional” significa que su existencia depende del modelo de negocio.

---

## 5. Análisis detallado por módulo

### 5.1 Módulo 1 — Gestión de sorteos (backoffice)

#### Propósito y valor

Es el núcleo administrativo: crear, versionar, publicar, cerrar y dar seguimiento a cada sorteo. Sin este módulo no hay producto. Su valor está en reducir errores humanos y dejar evidencia de cada cambio.

#### Alcance recomendado

**MVP**

- Crear sorteo en borrador: nombre, slug, descripción, premio(s), fechas, cupos, precio (si aplica), moneda, reglas, edad mínima, máximo por persona, autorización/resolución si corresponde.
- Estados mínimos: `borrador`, `publicado`, `cerrado`, `sorteado`, `premio_entregado`, `cancelado`.
- Versionado de bases y condiciones: una versión publicada no se edita; se crea una nueva versión y se comunica.
- Vista previa antes de publicar.
- Checklist de publicación: premio definido, fechas válidas, bases completas, autorización cargada, imagen, responsable.
- Historial de cambios y auditoría.

**Después**

- Categorías/etiquetas, sorteos recurrentes, plantillas, programación automática de publicación, multi-idioma, múltiples premios con imágenes, videos, destacados en home, campañas de marketing.

#### Flujo y estados

```text
borrador → en_revision → publicado → ventas_cerradas → sorteo_programado
        → semilla_comprometida → sorteado → ganadores_publicados
        → entrega_en_proceso → premio_entregado → cerrado
```

Estados excepcionales: `pausado`, `cancelado`, `desierto`, `reprogramado`. Cada transición debe tener regla, responsable y registro.

#### Datos y reglas

Entidades propuestas:

- `raffles`: id, public_code, slug, nombre, resumen, descripción, estado, fechas, cupos, precio, moneda, edad mínima, máximo por persona, autorización, versión de bases vigente, creador, publicador, fechas de publicación/cierre/sorteo.
- `raffle_prizes`: orden, título, descripción, tipo, valor estimado, imagen, condiciones, entrega.
- `raffle_rules_versions`: versión, contenido, hash, autor, fecha, estado, aceptación requerida.
- `raffle_media`: imágenes, videos, portada, alt text.
- `raffle_status_history`: estado anterior, estado nuevo, actor, motivo, fecha.

Reglas:

- Slug único y estable.
- No se puede publicar sin bases y premio.
- Una versión publicada es inmutable; el hash permite detectar cambios.
- El precio y los cupos no cambian después de publicar; si cambian, se crea una nueva versión y se decide si se reinicia el sorteo o se comunica el cambio.
- El sorteo no se puede eliminar si tiene participaciones o pagos; se cancela con evidencia.

#### Relación con la web pública

- Listado `/sorteos`: solo sorteos publicados o cerrados, nunca borradores.
- Detalle `/sorteos/{slug}`: premio, fechas, cupos, precio, bases, autorización, CTA.
- Estado visible: “Próximamente”, “En curso”, “Últimos cupos”, “Cerrado”, “Sorteado”, “Cancelado”.
- Contenido SEO: título, descripción, imagen, Open Graph, datos estructurados cuando corresponda.
- La web pública no debe exponer campos internos (notas, costos, datos de contacto, usuarios).

#### Dependencias e integraciones

- Módulo de participantes y tickets.
- Módulo legal (bases versionadas, autorización).
- Almacenamiento de imágenes (fileinfo o validación alternativa).
- Notificaciones para publicación/cambios.
- Reportes y auditoría.

#### Seguridad, privacidad y legal

- Solo roles autorizados pueden crear/publicar/cerrar.
- Publicar y cancelar requieren confirmación reforzada y auditoría.
- El contenido público debe pasar por un sanitizador; no aceptar HTML arbitrario de usuarios.
- La autorización/resolución debe ser un campo visible y verificable, no una nota interna.

#### Viabilidad y riesgos

- **Técnica:** alta. Se puede implementar con PHP/MySQL y el UI kit actual.
- **Operativa:** requiere definir quién aprueba y publica.
- **Legal:** depende del tipo de sorteo y autorización.
- **Riesgo principal:** publicar bases incompletas o cambiarlas después. Mitigación: versionado + hash + checklist + doble control.

#### Preguntas específicas

- ¿Quién crea, revisa y publica? ¿Se requiere doble aprobación?
- ¿Cuántos sorteos activos a la vez?
- ¿Un premio o varios? ¿Premios suplentes?
- ¿Sorteo gratuito o pagado?
- ¿Requiere autorización? ¿De qué entidad y con qué número?
- ¿Las bases se publican en la misma página o en un documento aparte?
- ¿Se permite reprogramar? ¿Con qué aviso y tratamiento de participantes?
- ¿Qué pasa si no se vende el cupo mínimo?

---

### 5.2 Módulo 2 — Usuarios y participantes

#### Propósito y valor

Permite identificar quién participa, evitar duplicados, cumplir obligaciones de privacidad y contactar ganadores. Es la puerta de entrada del público y, a la vez, el mayor riesgo de datos personales.

#### Alcance recomendado

**MVP**

- Participación como invitado con correo verificado, o cuenta mínima con correo y contraseña.
- DNI con validación de formato/check digit y declaración de mayoría de edad.
- Aceptación de bases, términos y política de privacidad, con registro de versión, fecha, IP y user agent.
- Prevención básica de duplicados por correo y DNI.
- Lista de bloqueo manual (organizadores, empleados, menores, personas excluidas).
- Panel de participantes con búsqueda, estado de verificación y exportación controlada.

**Después**

- OTP por SMS/WhatsApp.
- Validación RENIEC o proveedor KYC.
- Cuenta completa con historial, direcciones y métodos de pago.
- Segmentación, consentimiento de marketing, baja de comunicaciones.
- Eliminación/anonymización automática por retención.

#### Flujo y estados

```text
visitante → registro/participación → correo pendiente → correo verificado
         → participante habilitado → participante bloqueado/excluido
         → ganador → identidad verificada → entrega
```

No conviene bloquear la participación hasta verificar DNI/RENIEC si el sorteo es de bajo valor: se puede verificar al reclamar. Para premios altos, la verificación temprana puede ser obligatoria.

#### Datos y reglas

Entidades propuestas:

- `participants`: id, public_code, email, email_verified_at, phone, phone_verified_at, document_type, document_number_hash/encrypted, name, birth_date, status, created_at, last_login_at.
- `participant_sessions` o cookies firmadas para invitados.
- `participant_verifications`: tipo, proveedor, resultado, fecha, evidencia.
- `blocklist`: documento/email/teléfono, motivo, origen, vigencia.
- `legal_acceptances`: participant_id, documento, versión, hash, fecha, IP, user agent.
- `consents`: marketing, comunicaciones, cesión, fecha, revocación.

Reglas:

- No guardar DNI en claro si se puede evitar; usar hash para duplicados y cifrado para recuperación autorizada.
- Un correo = un participante; un DNI = un participante, salvo reglas explícitas.
- El DNI y la fecha de nacimiento son datos sensibles; acceso restringido y auditado.
- Un participante bloqueado no puede comprar ni participar.
- La cuenta no debe revelar si un correo existe (evitar enumeración).

#### Relación con la web pública

- Registro/login en `/cuenta` o dentro del flujo de participación.
- Verificación de correo en `/verificar-correo`.
- “Mis participaciones” con tickets, estado de pago y resultados.
- Formulario de reclamo de premio.
- Baja de datos y solicitudes ARCO.
- Aviso de privacidad y consentimiento en el momento de la recolección.

#### Dependencias e integraciones

- Correo transaccional (verificación, recuperación).
- SMS/WhatsApp si se hace OTP.
- RENIEC/KYC si se valida identidad.
- Motor de tickets y pagos.
- Auditoría y seguridad.

#### Seguridad, privacidad y legal

- Ley 29733 y su Reglamento (D.S. 016-2024-JUS): consentimiento, finalidad, minimización, seguridad, retención, derechos ARCO, inscripción del banco de datos y oficial de datos cuando corresponda.
- No usar datos de participantes para marketing sin consentimiento separado.
- No publicar DNI, correo ni teléfono.
- Rate limiting, captcha, verificación de correo, bloqueo temporal, detección de cuentas múltiples.
- Cifrado en tránsito y en reposo para datos sensibles; control de acceso por rol.

#### Viabilidad y riesgos

- **Técnica:** alta para correo + DNI con validación local; media para RENIEC/OTP.
- **Operativa:** la atención de soporte y verificación consume tiempo.
- **Legal:** alta dependencia de la política de privacidad y del registro del banco de datos.
- **Riesgo principal:** pedir demasiados datos al inicio y perder conversión, o almacenar DNI sin protección. Mitigación: minimizar, verificar al reclamar, cifrar y auditar.

#### Preguntas específicas

- ¿La participación requiere cuenta o se permite como invitado?
- ¿El DNI se pide al registrar o solo al reclamar?
- ¿Se exigirá RENIEC? ¿Qué proveedor y costo?
- ¿Se exigirá OTP? ¿SMS, WhatsApp o correo?
- ¿Cómo se controlan duplicados y cuentas múltiples?
- ¿Quiénes están excluidos? ¿Cómo se carga la lista?
- ¿Qué edad mínima? ¿Cómo se verifica?
- ¿Cuánto tiempo se conservan los datos? ¿Quién es el responsable del banco de datos?
- ¿Habrá consentimiento de marketing separado?

---

### 5.3 Módulo 3 — Tickets / participaciones

#### Propósito y valor

Representa la participación concreta de una persona en un sorteo. Debe garantizar unicidad, trazabilidad y equidad. Es donde ocurren los errores más costosos: duplicados, sobreventa, reservas eternas y tickets “perdidos”.

#### Alcance recomendado

**MVP**

- Un ticket = un número o una participación, según el modelo.
- Asignación automática de números únicos por sorteo.
- Límite por persona.
- Reserva temporal con expiración mientras se paga o confirma.
- Estados: `reservado`, `pendiente_pago`, `pagado`, `cancelado`, `expirado`, `reembolsado`, `anulado`.
- Comprobante de participación con código firmado/QR.
- Historial por participante.
- Panel de tickets con búsqueda, estado y auditoría.

**Después**

- Elección manual de números, compra de múltiples números, paquetes, transferencia de tickets (solo si la ley y las bases lo permiten), tickets físicos, códigos de activación.

#### Flujo y estados

```text
disponible → reservado (expira) → pendiente_pago → pagado → válido para sorteo
                                     ↘ expirado/cancelado
pagado → reembolsado/anulado (con evidencia y auditoría)
```

La reserva debe expirar por tiempo y por cierre del sorteo. Un ticket pagado no se puede editar; solo reembolsar/anular con regla.

#### Datos y reglas

Entidades propuestas:

- `raffle_numbers`: raffle_id, number, status, ticket_id, unique(raffle_id, number).
- `ticket_reservations`: raffle_id, number, participant_id, expires_at, token, status.
- `tickets`: raffle_id, number, participant_id, order_id, status, public_code, issued_at.
- `ticket_orders`: participant_id, raffle_id, quantity, amount, status, payment_id.
- `ticket_events`: historial de estados.

Reglas de concurrencia:

- `UNIQUE(raffle_id, number)` es obligatorio.
- Asignación dentro de una transacción con `SELECT ... FOR UPDATE` o `INSERT` + reintento ante duplicado.
- Precarga de números para sorteos con cupo fijo; evitar `COUNT(*)` como fuente de verdad.
- Expiración perezosa al consultar + worker programado.
- Idempotencia en la creación de órdenes y en la confirmación de pago.
- Un ticket pagado no se puede duplicar aunque llegue dos veces el webhook.

#### Relación con la web pública

- Selección de cantidad y, si aplica, número.
- Contador de disponibilidad en tiempo real o cuasi real.
- Reserva con cuenta regresiva.
- Confirmación con código, correo y “mis participaciones”.
- Aviso claro de que la reserva no es una compra hasta el pago.
- Estado del sorteo y resultado en la misma cuenta.

#### Dependencias e integraciones

- Participantes.
- Pagos (si aplica).
- Sorteo (snapshot de tickets válidos).
- Correo/WhatsApp.
- Auditoría.

#### Seguridad, privacidad y legal

- No permitir que un usuario vea tickets de otros.
- Códigos públicos aleatorios, no secuenciales, para evitar enumeración.
- Firma/QR verificable sin exponer datos personales.
- Política de reembolso y cancelación en las bases.
- Si el ticket es transferible, definir reglas y registro; si no, prohibirlo explícitamente.
- Evitar sobreventa y “último número” manipulado.

#### Viabilidad y riesgos

- **Técnica:** alta con MySQL transaccional y unique constraints; media si hay alta concurrencia y hosting compartido.
- **Operativa:** la atención de pagos y reservas expiradas requiere procesos.
- **Riesgo principal:** race conditions y sobreventa. Mitigación: transacciones, locks, unique, pruebas de carga, idempotencia.
- **Riesgo secundario:** usuarios que reservan y no pagan, bloqueando números. Mitigación: expiración corta y límite por persona.

#### Preguntas específicas

- ¿Ticket numerado o participación sin número?
- ¿El usuario elige número o se asigna automático?
- ¿Cuántos tickets por persona? ¿Por transacción?
- ¿Duración de la reserva? ¿Se libera automáticamente?
- ¿Se permite transferir? ¿Se permite regalar?
- ¿Qué comprobante recibe el participante?
- ¿Qué pasa si se cae el pago después de reservar?
- ¿Se puede anular un ticket pagado? ¿Con qué causal?

---

### 5.4 Módulo 4 — Pagos (si se cobra)

#### Propósito y valor

Permite cobrar tickets y conciliar el dinero. Es un módulo de alto riesgo: fraude, contracargos, duplicados, obligaciones tributarias y experiencia de compra. **Solo tiene sentido si el sorteo es pagado.**

#### Alcance recomendado

**MVP si es pagado y el volumen es bajo**

- Pago manual: Yape/Plin/transferencia con subida de comprobante y aprobación administrativa.
- Registro de orden, pago, comprobante y estado.
- Validación manual con doble control y auditoría.
- Conciliación básica contra extracto.
- Política de reembolso clara.

**Fase posterior**

- Pasarela con checkout hospedado: Culqi, Niubiz, Izipay o Mercado Pago.
- Webhooks firmados, idempotencia, conciliación automática.
- Reembolsos y contracargos.
- Yape/Plin mediante integración oficial.
- Comprobantes electrónicos SUNAT.
- Registro contable y reportes.

#### Flujo y estados

```text
orden_creada → pago_pendiente → pago_recibido → pago_confirmado → ticket_emitido
             ↘ pago_rechazado → reintento/cancelación
pago_confirmado → reembolso_solicitado → reembolsado
```

Un pago confirmado debe ser idempotente: el mismo evento no puede emitir dos tickets ni duplicar ingresos.

#### Datos y reglas

Entidades propuestas:

- `orders`: participant_id, raffle_id, amount_cents, currency, status, public_code, created_at.
- `payments`: order_id, provider, provider_payment_id, amount_cents, status, paid_at, raw_status, idempotency_key.
- `payment_events`: provider, event_id, payload_hash, processed_at, result.
- `refunds`: payment_id, amount_cents, reason, status, evidence.
- `receipts`: tipo, serie, número, estado SUNAT, XML/PDF, fecha.
- `reconciliation_runs` y `reconciliation_items`.

Reglas:

- Montos en centavos (`BIGINT` o `INT UNSIGNED`) para evitar decimales flotantes.
- Moneda explícita (PEN).
- Nunca guardar datos de tarjeta.
- Webhook con firma, idempotencia y verificación de monto/moneda/orden.
- Un pago aprobado no se “edita”; se reembolsa o se anula con evidencia.
- La conciliación debe comparar pasarela vs. base de datos vs. tickets emitidos.

#### Relación con la web pública

- Checkout claro con precio, moneda, cantidad, datos del participante, bases y política de privacidad.
- Confirmación inmediata y correo.
- Página de pago fallido con reintento.
- Historial de pagos y comprobantes en la cuenta.
- Aviso de que el ticket se emite solo con pago confirmado.

#### Dependencias e integraciones

- Pasarela/contrato de comercio.
- RUC, cuenta bancaria, facturación.
- SUNAT si se emiten comprobantes.
- Conciliación y contabilidad.
- Notificaciones.
- Seguridad y antifraude.

#### Seguridad, privacidad y legal

- PCI DSS: usar checkout hospedado o tokenización; no almacenar PAN/CVV.
- HTTPS obligatorio; secretos fuera del repositorio.
- Webhooks verificados y con reintentos; logs sin datos sensibles.
- Prevención de fraude: límites, velocidad, mismo DNI/IP/tarjeta, listas, revisión manual.
- SUNAT: confirmar si cada venta requiere boleta/factura electrónica y cómo se emitirá.
- Consumer protection: información clara de precio, condiciones, reembolsos y reclamos.
- SBS/UIF: evaluar si la actividad o los premios caen en el ámbito de sujetos obligados; validar con abogado. No asumir que no aplica.

#### Viabilidad y riesgos

- **Técnica:** media. Sin Composer se puede usar REST/cURL, pero cada pasarela tiene requisitos, sandbox, webhooks y soporte.
- **Operativa:** alta carga de conciliación, reembolsos y atención.
- **Legal/tributaria:** alta. Requiere RUC, cuenta, contratos y probablemente comprobantes.
- **Riesgo principal:** lanzar pagos sin conciliación ni soporte. Mitigación: empezar manual o con checkout hospedado y conciliación diaria.
- **Riesgo de plataforma:** PHP 8.0 EOL y hosting compartido pueden incumplir requisitos de seguridad de la pasarela.

#### Preguntas específicas

- ¿El sorteo es gratuito o pagado? (bloqueante)
- ¿Precio, moneda y cantidad mínima/máxima?
- ¿Pasarela preferida? ¿Ya hay cuenta de comercio?
- ¿Quién es el titular del RUC y de la cuenta?
- ¿Se emitirán boletas/facturas electrónicas? ¿Quién es el PSE/OSE?
- ¿Se aceptará Yape/Plin? ¿Manual o integrado?
- ¿Política de reembolsos y contracargos?
- ¿Quién concilia y con qué frecuencia?
- ¿Qué volumen mensual se espera?
- ¿Se acepta pago manual con comprobante en la fase 1?

---

### 5.5 Módulo 5 — Motor del sorteo

#### Propósito y valor

Es el módulo más crítico para la confianza. Debe garantizar que el ganador no fue elegido ni modificado por una persona. Un sorteo percibido como manipulado destruye el producto, aunque el resto funcione.

#### Alcance recomendado

**MVP**

- Snapshot inmutable de participantes/tickets válidos al cierre.
- Hash del snapshot publicado.
- Semilla CSPRNG de 32 bytes.
- Hash de la semilla publicado **antes** del sorteo.
- Ejecución con algoritmo documentado y reproducible.
- Semilla revelada después.
- Resultado y suplentes.
- Acta digital con fecha, sorteo, semilla, hash, lista, algoritmo, ejecutor y resultado.
- Página pública de verificación.
- Registro de auditoría sin edición manual del resultado.

**Después**

- Testigo/notario, transmisión en vivo, auditoría externa, beacon público adicional, sellado de tiempo, verificación independiente con script descargable.

#### Esquema recomendado: commit-reveal

1. Al cerrar el sorteo se congela el conjunto de tickets válidos.
2. Se calcula `snapshot_hash = SHA-256(lista ordenada de tickets y participantes)`.
3. Se genera `seed = random_bytes(32)`.
4. Se publica `seed_hash = SHA-256(seed)` junto con `snapshot_hash`, sorteo y fecha.
5. El día del sorteo se revela `seed`.
6. Se calcula un número determinista, por ejemplo:
   `index = HMAC-SHA256(seed, raffle_id || snapshot_hash || "draw")` interpretado como entero, módulo cantidad de participantes.
7. Se seleccionan ganador y suplentes con el mismo método y un dominio distinto por posición.
8. Cualquier persona puede recalcular el resultado con la semilla, el snapshot y el algoritmo.

Ventajas: no requiere blockchain, es barato, reproducible y auditable. Limitación: si el organizador genera la semilla y no la compromete antes, no hay garantía; por eso el hash debe publicarse antes y quedar registrado.

#### Flujo y estados

```text
programado → cerrado → snapshot_generado → hash_publicado
           → semilla_revelada → ejecutado → resultado_publicado → auditado
```

Ningún estado permite “editar” el ganador. Si hay error, se documenta y se repite con un nuevo sorteo o se aplica la regla de reemplazo predefinida.

#### Datos y reglas

Entidades propuestas:

- `draws`: raffle_id, scheduled_at, executed_at, status, algorithm_version, executed_by, acta_url.
- `draw_snapshots`: draw_id, snapshot_hash, total_entries, generated_at, immutable.
- `draw_snapshot_entries`: snapshot_id, ticket_id, participant_id, position, entry_hash.
- `draw_seeds`: draw_id, seed_encrypted, seed_hash, committed_at, revealed_at.
- `draw_results`: draw_id, position, ticket_id, participant_id, result_type (ganador/suplente), published_at.
- `draw_documents`: acta, hash, firma, evidencia, URL.
- `draw_audit`: eventos, actor, IP, user agent, payload hash.

Reglas:

- El snapshot se genera después del cierre y antes de revelar la semilla.
- Un ticket reembolsado/cancelado antes del cierre no entra.
- Un ticket pagado después del cierre no entra, salvo regla expresa.
- Los suplentes se definen antes del sorteo y se publican.
- El algoritmo y la versión se publican en las bases.
- El resultado no se puede modificar; solo se puede declarar desierto/reprogramar con acta.

#### Relación con la web pública

- Página de resultados con ganadores y suplentes.
- Semilla, hash de semilla, hash de snapshot y algoritmo visibles.
- Lista de participantes anonimizada o con identificadores públicos.
- Herramienta de verificación: formulario o script que permita recalcular.
- Acta descargable.
- Enlace a transmisión en vivo si existe.
- Historial de sorteos.

#### Dependencias e integraciones

- Tickets y participantes.
- Cierre de ventas.
- Almacenamiento de actas/evidencias.
- PDF/impresión.
- Notificaciones.
- Legal (notaría, bases, autorización).

#### Seguridad, privacidad y legal

- La semilla no debe aparecer en logs antes de revelarse.
- Acceso al secreto restringido; idealmente doble control.
- El snapshot debe ser inmutable; no se recalcula después de publicar.
- El acta debe incluir responsable, fecha/hora, algoritmo, hashes y resultado.
- La lista pública no debe exponer DNI/correo/teléfono.
- Si la ley o el premio exigen notario/transmisión, planificarlo con anticipación.

#### Viabilidad y riesgos

- **Técnica:** alta. `random_bytes`, `hash`, `hash_hmac` y transacciones son suficientes.
- **Operativa:** requiere disciplina: publicar el hash antes, no tocar el snapshot, ejecutar a la hora.
- **Legal:** depende del tipo de sorteo y del valor del premio.
- **Riesgo principal:** percepción de manipulación. Mitigación: commit-reveal, acta, verificación pública, doble control y auditoría.
- **Riesgo secundario:** errores en el snapshot (tickets inválidos). Mitigación: conciliación previa y revisión.

#### Preguntas específicas

- ¿Qué valor tienen los premios? ¿Exige notario o transmisión en vivo?
- ¿Quién ejecuta el sorteo? ¿Se exige doble control?
- ¿Se publica la semilla y el algoritmo? ¿En qué formato?
- ¿Qué algoritmo se usará y cómo se documenta?
- ¿Habrá suplentes? ¿Cuántos?
- ¿Qué pasa si el ganador no reclama?
- ¿Se permite reprogramar? ¿Con qué evidencia?
- ¿Se auditará con un tercero?
- ¿La lista de participantes será pública, anonimizada o solo verificable por hash?

---

### 5.6 Módulo 6 — Transparencia pública

#### Propósito y valor

Convierte el sorteo en un proceso verificable. No es solo una página de ganadores: incluye bases, resultados, evidencia, historial y canales de reclamo. Es un requisito de confianza y, según el caso, de cumplimiento.

#### Alcance recomendado

**MVP**

- Página de resultados por sorteo: ganador, suplentes, fecha, premio.
- Semilla, hash de semilla, hash de snapshot, algoritmo y total de participantes.
- Lista de participantes con identificadores anonimizados.
- Bases y condiciones versionadas, con hash y fecha.
- Autorización/resolución visible si corresponde.
- Acta digital descargable.
- Página de preguntas frecuentes y reclamos.
- Enlace al Libro de Reclamaciones virtual.

**Después**

- Verificador interactivo, descarga de evidencia, historial completo, transmisión en vivo, sellado de tiempo, API pública de verificación.

#### Relación con la web pública

Rutas sugeridas:

- `/sorteos/{slug}/resultados`
- `/ganadores`
- `/sorteos/{slug}/bases`
- `/verificar/{codigo}` o `/verificar-sorteo`
- `/legal/privacidad`
- `/legal/terminos`
- `/libro-de-reclamaciones`
- `/preguntas-frecuentes`

La información debe ser accesible sin login, salvo datos personales que no corresponda publicar. Debe funcionar en móvil, imprimirse y tener URLs estables.

#### Datos y reglas

- Publicar solo datos necesarios: nombre público o alias, número de ticket, posición, premio.
- Nunca publicar DNI completo, correo, teléfono, dirección ni comprobantes.
- Si se publica el nombre del ganador, contar con base legal/consentimiento y reglas del sorteo.
- El hash de la lista permite verificar sin exponer la lista completa.
- La página de resultados no se edita; si hay corrección, se agrega una fe de erratas con versión.

#### Seguridad, privacidad y legal

- Minimización de datos.
- Consentimiento para difusión de imagen/nombre si se usa en marketing.
- Derecho de oposición/rectificación.
- Cache control para evitar resultados obsoletos.
- Protección contra scraping excesivo.
- Accesibilidad y lenguaje claro.

#### Viabilidad y riesgos

- **Técnica:** alta. Son páginas de solo lectura + PDF/HTML.
- **Operativa:** requiere aprobar contenido y mantener evidencia.
- **Legal:** la publicidad de ganadores debe estar en las bases.
- **Riesgo:** filtrar datos personales. Mitigación: revisión, anonimización y pruebas.

#### Preguntas específicas

- ¿Se publicará el nombre del ganador? ¿Con qué consentimiento?
- ¿Se publicará la lista completa o solo hashes?
- ¿Quién aprueba la publicación?
- ¿Se requiere acta notarial o transmisión?
- ¿Cuánto tiempo se conserva la evidencia pública?
- ¿Habrá verificación interactiva o solo documento?

---

### 5.7 Módulo 7 — Entrega de premios

#### Propósito y valor

Cierra el ciclo y protege al organizador. Un ganador sin entrega documentada es un reclamo seguro. También es donde aparecen obligaciones de identidad, impuestos y prevención de lavado de activos.

#### Alcance recomendado

**MVP**

- Lista de ganadores y suplentes.
- Estado de contacto: pendiente, contactado, identidad verificada, entrega programada, entregado, no reclamado, desierto.
- Registro de contacto y evidencia (foto, acta, firma, comprobante).
- Plazo de reclamo visible en las bases.
- Plantillas de comunicación.
- Panel de seguimiento y reportes.

**Después**

- Portal del ganador para subir documentos, agendar entrega y firmar digitalmente.
- Logística integrada, transportistas, seguros.
- KYC/AML para premios altos.
- Gestión de premios no reclamados y suplentes automática.

#### Flujo y estados

```text
ganador → contacto_iniciado → identidad_verificada → entrega_programada
        → entregado → cerrado
        ↘ no_reclamado → suplente → cerrado
```

Cada transición debe registrar fecha, responsable, canal y evidencia.

#### Datos y reglas

- `prize_deliveries`: raffle_id, winner_id, prize_id, status, deadline, delivered_at, delivered_by, evidence_url, notes.
- `delivery_evidence`: tipo, archivo, hash, fecha, responsable.
- `claim_tokens`: token de un solo uso para reclamo.
- `identity_verifications`: tipo, resultado, evidencia, fecha.
- `delivery_events`: historial.

Reglas:

- No entregar sin verificar identidad y aceptación de bases.
- El plazo y las consecuencias de no reclamar deben estar en las bases.
- La evidencia debe ser privada y con acceso restringido.
- Los premios no reclamados se rigen por las bases y la ley aplicable.

#### Relación con la web pública

- Página de “reclama tu premio” con token.
- Estado del reclamo.
- Instrucciones claras, documentos requeridos y plazos.
- Aviso de privacidad para la evidencia.
- No mostrar datos de otros ganadores.

#### Dependencias e integraciones

- Motor del sorteo.
- Participantes e identidad.
- Notificaciones.
- Almacenamiento privado.
- Legal/contabilidad/AML.

#### Seguridad, privacidad y legal

- Verificación de identidad proporcionada y segura.
- Evidencia cifrada/privada; no en `public_html`.
- Consentimiento para uso de imagen.
- Obligaciones tributarias sobre premios.
- AML/KYC si el valor o la actividad lo exigen; validar con abogado.
- Plazos y reclamos conforme a consumer protection.

#### Viabilidad y riesgos

- **Técnica:** media. El seguimiento es fácil; la carga de documentos requiere validación y almacenamiento seguro.
- **Operativa:** alta. Requiere personal, logística y evidencia.
- **Legal:** alta para premios altos.
- **Riesgo:** entregar sin evidencia o a la persona equivocada. Mitigación: doble verificación, acta y evidencia.

#### Preguntas específicas

- ¿Qué tipos de premio y valor?
- ¿Entrega presencial, delivery, transferencia?
- ¿Quién entrega y dónde?
- ¿Plazo de reclamo y política de no reclamado?
- ¿Se exigirá DNI, RENIEC, datos bancarios?
- ¿Se publicará foto/acta?
- ¿Hay obligaciones tributarias o AML?
- ¿Quién custodia la evidencia y por cuánto tiempo?

---

### 5.8 Módulo 8 — Cumplimiento y legal

#### Propósito y valor

Permite lanzar sin bloqueos legales y reduce el riesgo de sanciones, reclamos y pérdida de confianza. No es un módulo “de software” solamente: es un conjunto de decisiones, documentos y procesos.

#### Alcance recomendado

**Antes de lanzar**

- Definir si el sorteo es promocional/gratuito o con pago.
- Confirmar autorización/resolución y autoridad competente, si aplica.
- Redactar bases y condiciones versionadas.
- Redactar política de privacidad y términos de uso.
- Definir tratamiento de datos, retención y derechos ARCO.
- Evaluar inscripción del banco de datos y oficial de datos.
- Implementar Libro de Reclamaciones virtual.
- Definir comprobantes/tributación si hay pago.
- Evaluar AML/UIF si corresponde.
- Definir verificación de edad y juego responsable si hay pago.
- Definir publicidad y consentimiento de imagen de ganadores.
- Definir jurisdicción, ley aplicable y canal de reclamos.

**Después**

- Auditoría legal periódica, actualización de bases, revisión de proveedores, planes de respuesta a incidentes.

#### Referencias de validación (no asesoría)

- **Protección de datos:** Ley N.° 29733 y Reglamento D.S. N.° 016-2024-JUS; ANPD. Verificar inscripción del banco de datos, política de privacidad, consentimiento, ODP y derechos ARCO.
- **Libro de Reclamaciones:** INDECOPI. Puede ser físico o virtual; debe estar disponible en el mismo medio virtual; atención en plazo legal; reporte al SIREC cuando corresponda por ingresos.
- **SBS/UIF:** la normativa de prevención de LA/FT y la relación de sujetos obligados deben revisarse para determinar si la actividad o los premios quedan alcanzados. La SBS publicó resoluciones y proyectos en 2026; no asumir aplicabilidad sin revisión legal.
- **SUNAT:** si hay venta de tickets, confirmar comprobantes electrónicos, RUC, PSE/OSE y obligaciones contables.
- **Consumidor:** información veraz, condiciones claras, derecho de reclamo y trato no discriminatorio.
- **Autorización del sorteo:** confirmar con abogado si el tipo de sorteo requiere autorización municipal, sectorial o resolución, y qué número debe publicarse.

#### Relación con la web pública

- Bases y términos accesibles antes de participar.
- Aviso de privacidad en formularios.
- Casillas de aceptación no premarcadas.
- Libro de Reclamaciones visible y funcional.
- Autorización/resolución visible.
- Edad mínima y juego responsable si aplica.
- Canales de contacto y reclamo.

#### Viabilidad y riesgos

- **Técnica:** baja complejidad; son páginas, formularios y registros.
- **Legal:** alta complejidad y depende de terceros.
- **Riesgo principal:** lanzar sin autorización o sin bases adecuadas. Mitigación: fase 0 legal con abogado/contador.
- **Riesgo secundario:** cambios normativos. Mitigación: revisión periódica y versionado.

#### Preguntas específicas

- ¿Quién es el titular del sorteo y el responsable de datos?
- ¿Qué tipo de sorteo es y qué autorización requiere?
- ¿Se cobra? ¿Se emite comprobante?
- ¿Se inscribirá el banco de datos? ¿Quién será el ODP?
- ¿Se requiere evaluación AML/UIF?
- ¿Qué ley/jurisdicción y canal de reclamos?
- ¿Se publicará el nombre/imagen del ganador?
- ¿Qué edad mínima y cómo se verifica?
- ¿Qué plazo de conservación de evidencia?

---

### 5.9 Módulo 9 — Seguridad y antifraude

#### Propósito y valor

Protege dinero, tickets, datos personales y la credibilidad del sorteo. En un sistema de sorteos, la seguridad no es solo “evitar hackeos”: es evitar fraude interno, duplicados, manipulación de resultados y filtraciones.

#### Alcance recomendado

**Base actual aprovechable**

- CSRF, sesiones, `password_hash`, prepared statements, throttle de login.
- Separación `app/` privado y `public_html/`.
- HTTPS en producción.
- UI kit sin dependencias externas.

**Brechas a cubrir**

- 2FA para administradores.
- Roles y permisos granulares (admin, operador, auditor, soporte).
- Auditoría inmutable de acciones sensibles.
- Rate limiting y captcha en formularios públicos.
- Protección de fuerza bruta y enumeración.
- Firma/verificación de tickets.
- Idempotencia en pagos.
- Cifrado de datos sensibles.
- Gestión de secretos.
- Respaldos y restauración probada.
- Actualización de PHP (8.0 EOL).
- Monitoreo, alertas y respuesta a incidentes.
- Validación de archivos subidos.
- Revisión de dependencias.

#### Controles propuestos

- RBAC con permisos por módulo/acción.
- 2FA TOTP para admin; códigos de recuperación.
- Registro de auditoría append-only con hash encadenado y campos antes/después.
- Rate limits por IP, cuenta, correo y acción.
- Captcha en registro, reclamo y contacto.
- Correos de alerta ante acciones sensibles.
- Doble control en publicar, ejecutar sorteo, aprobar pago y entregar premio.
- Backups cifrados y prueba de restauración.
- Variables de entorno/secretos fuera del repositorio.
- HTTPS/HSTS, cookies seguras, cabeceras de seguridad.
- Escaneo de archivos y validación MIME/extensión; almacenamiento fuera del webroot.
- Logs sin datos sensibles.
- Plan de respuesta a incidentes y contacto.

#### Relación con la web pública

- Formularios protegidos, sin exponer si un correo existe.
- Verificación de correo/teléfono.
- Límites de intentos y bloqueos temporales.
- Avisos de seguridad y privacidad.
- Página de estado/incidentes si aplica.

#### Viabilidad y riesgos

- **Técnica:** media. RBAC, auditoría y 2FA son viables sin framework; captcha y monitoreo pueden requerir servicios externos.
- **Operativa:** requiere disciplina y responsables.
- **Riesgo principal:** PHP 8.0 EOL y hosting compartido. Mitigación: upgrade y revisión de configuración.
- **Riesgo secundario:** fraude interno. Mitigación: doble control, auditoría y separación de funciones.

#### Preguntas específicas

- ¿Qué roles existirán y qué puede hacer cada uno?
- ¿2FA obligatorio para todos los admin?
- ¿Quién accede a la base de datos y a los secretos?
- ¿Qué proveedor de captcha?
- ¿Cuánto tiempo se conservan logs/auditoría?
- ¿Hay plan de respaldos y restauración?
- ¿Se puede actualizar PHP?
- ¿Quién responde a incidentes?

---

### 5.10 Módulo 10 — Notificaciones y comunicación

#### Propósito y valor

Mantiene informado al participante y reduce soporte: verificación, reserva, pago, ticket, recordatorio, resultado, reclamo y entrega. También es un canal de marketing, pero debe separarse del transaccional.

#### Alcance recomendado

**MVP**

- Correo transaccional para verificación, recuperación, confirmación de participación, resultado y reclamo.
- Plantillas editables con variables.
- Registro de envíos y errores.
- Reintentos básicos.
- Baja de marketing separada de transaccional.

**Después**

- SMS y WhatsApp Business API para OTP y avisos críticos.
- Push PWA para recordatorios (no crítico).
- Preferencias por canal.
- Segmentos y campañas.

#### Datos y reglas

- `notification_templates`: clave, asunto, cuerpo, canal, idioma, versión.
- `notifications`: destinatario, canal, evento, estado, programado, enviado, error.
- `notification_attempts`: intento, proveedor, respuesta, fecha.
- `opt_outs`/preferences.

Reglas:

- No enviar marketing sin consentimiento.
- No incluir datos sensibles innecesarios en el correo.
- Enlaces con tokens de un solo uso y expiración.
- Reintentos con límite y alerta.
- Registrar rebotes y quejas.

#### Relación con la web pública

- Formulario de contacto, verificación, recuperación, preferencias.
- Página de “revisa tu correo”.
- Avisos de resultado y reclamo.
- Centro de notificaciones en la cuenta.

#### Dependencias e integraciones

- SMTP/proveedor de correo.
- SMS/WhatsApp si aplica.
- Plantillas y cola de envío (tabla + cron).
- Privacidad y consentimiento.

#### Viabilidad y riesgos

- **Técnica:** alta para correo con SMTP; media para SMS/WhatsApp por contratos y costos.
- **Operativa:** entregabilidad (SPF/DKIM/DMARC), rebotes, soporte.
- **Riesgo:** correos en spam o WhatsApp bloqueado por plantillas no aprobadas. Mitigación: proveedor serio, dominio autenticado y plantillas aprobadas.

#### Preguntas específicas

- ¿Qué eventos son obligatorios?
- ¿Volumen estimado?
- ¿Proveedor de correo/SMS/WhatsApp?
- ¿Presupuesto por mensaje?
- ¿Se usará WhatsApp oficial?
- ¿Quién redacta y aprueba plantillas?
- ¿Cómo se gestiona la baja de marketing?

---

### 5.11 Módulo 11 — Reportes y analítica

#### Propósito y valor

Permite operar y rendir cuentas: ventas, participantes, conversión, ingresos vs. premios, reembolsos, entrega y soporte. También alimenta contabilidad y auditoría.

#### Alcance recomendado

**MVP**

- Dashboard con métricas por sorteo: tickets vendidos, ingresos, participantes, conversión, reembolsos, premios.
- Exportación CSV de tickets, pagos y participantes (con permisos).
- Reporte de conciliación.
- Reporte de entrega de premios.
- Reporte de reclamos.

**Después**

- Gráficos, cohortes, embudos, comparativas, exportación Excel/PDF, integración contable, BI.

#### Datos y reglas

- Definir métricas únicas y consistentes.
- Zona horaria `America/Lima`.
- Montos en centavos y moneda.
- Separar ingresos brutos, reembolsos, comisiones y neto.
- No mezclar tickets pagados con reservas.
- Exportaciones con control de acceso y marca de agua/registro.

#### Relación con la web pública

- No exponer reportes internos.
- Publicar solo métricas agregadas si se decide (participantes, recaudación, etc.).
- Cuidado con datos personales en exportaciones.

#### Viabilidad y riesgos

- **Técnica:** alta para SQL + CSV; media para gráficos/BI.
- **Operativa:** requiere definir KPIs y responsables.
- **Riesgo:** métricas inconsistentes o datos personales en exportaciones. Mitigación: diccionario de datos y permisos.

#### Preguntas específicas

- ¿Qué KPIs importan?
- ¿Quién recibe reportes y con qué frecuencia?
- ¿Formato contable/tributario?
- ¿Se requiere integración con contabilidad?
- ¿Se publicarán métricas en la web?

---

### 5.12 Módulo 12 — Soporte

#### Propósito y valor

Atiende dudas, reclamos y problemas de pago/entrega. Es obligatorio tener un canal de reclamos; el resto es calidad de servicio.

#### Alcance recomendado

**MVP**

- Preguntas frecuentes.
- Formulario de contacto.
- Libro de Reclamaciones virtual con constancia y plazo.
- Correo de soporte y registro básico de casos.
- Base de conocimiento mínima.

**Después**

- Ticketing con estados, SLA y asignación.
- Chat/WhatsApp.
- Base de conocimiento completa.
- Reportes de reclamos y encuestas.

#### Datos y reglas

- `support_tickets`: canal, participante, asunto, estado, prioridad, responsable, SLA.
- `support_messages`: mensajes, adjuntos, autor, fecha.
- `claims`: hoja de reclamación, tipo (queja/reclamo), detalle, pedido, respuesta, constancia.
- `claim_attachments` con validación y almacenamiento privado.

Reglas:

- No mezclar reclamo formal con consulta general.
- Plazo legal de atención; constancia de recepción.
- Escalamiento y responsable.
- No pedir más datos de los necesarios.
- Registrar consentimiento y privacidad.

#### Relación con la web pública

- `/libro-de-reclamaciones` visible en footer y en la página del sorteo.
- Formulario con constancia descargable.
- FAQ y contacto.
- Estado del caso para el usuario (opcional).

#### Viabilidad y riesgos

- **Técnica:** alta para formulario + correo + base de datos.
- **Operativa:** depende de personal y horarios.
- **Riesgo:** incumplir el plazo de reclamos o perder evidencia. Mitigación: bandeja, alertas y reporte.

#### Preguntas específicas

- ¿Quién atiende y en qué horario?
- ¿SLA interno?
- ¿Se usará WhatsApp/chat?
- ¿Se reportará al SIREC?
- ¿Dónde se guardan las constancias?
- ¿Cómo se escala un reclamo?

---

## 6. Relación detallada con la web pública por desarrollar

### 6.1 Punto de partida

La web actual es una home de radio con plantilla Sitejet. Para sorteos hace falta una **capa pública nueva**:

- Un layout limpio y reutilizable (no la plantilla Sitejet con estilos inline).
- Navegación clara: Inicio, Sorteos, Ganadores, Cómo participar, Preguntas, Contacto, Libro de Reclamaciones.
- Componentes de sorteo: tarjeta, estado, cuenta regresiva, premio, cupos, CTA.
- Formularios accesibles y seguros.
- SEO y Open Graph.
- Service worker con estrategia adecuada para páginas dinámicas.

La home de radio puede conservarse como sección o convivir con un bloque de “sorteos destacados”, pero la lógica transaccional debe vivir en páginas nuevas.

### 6.2 Rutas públicas propuestas

| Ruta | Contenido | Estado |
| --- | --- | --- |
| `/` | Home de la radio + sorteos destacados (decisión pendiente) | A definir |
| `/sorteos` | Listado de sorteos publicados/cerrados | Público |
| `/sorteos/{slug}` | Detalle, premio, fechas, cupos, bases, CTA | Público |
| `/sorteos/{slug}/participar` | Registro/compra/reserva | Público |
| `/sorteos/{slug}/resultados` | Ganadores, semilla, hashes, acta | Público |
| `/ganadores` | Historial de ganadores | Público |
| `/verificar-sorteo` | Verificación de resultado | Público |
| `/cuenta` | Mis participaciones, pagos, datos | Privado |
| `/cuenta/participaciones` | Tickets y estados | Privado |
| `/cuenta/reclamar/{token}` | Reclamo de premio | Privado/token |
| `/legal/bases/{slug}` | Bases versionadas | Público |
| `/legal/privacidad` | Política de privacidad | Público |
| `/legal/terminos` | Términos de uso | Público |
| `/libro-de-reclamaciones` | Libro virtual | Público |
| `/preguntas-frecuentes` | FAQ | Público |
| `/contacto` | Contacto/soporte | Público |

### 6.3 Estados del sorteo en la web

| Estado | Qué ve el público | Acción permitida |
| --- | --- | --- |
| Borrador | Nada | Ninguna |
| Programado | “Próximamente”, fecha | Recordatorio/registro si se define |
| Publicado | Detalle, cupos, precio, CTA | Participar/comprar |
| Últimos cupos | Disponibilidad baja | Participar |
| Ventas cerradas | “Cerrado”, fecha de sorteo | Consultar bases |
| Sorteando | “En proceso”, transmisión si existe | Ver transmisión |
| Sorteado | Ganadores, semilla, hashes, acta | Verificar, reclamar |
| Entrega | Estado de entrega | Reclamar/seguimiento |
| Cerrado | Resultado histórico | Consultar |
| Cancelado | Motivo, devolución si aplica | Reclamo |

### 6.4 Journey del participante

1. Descubre el sorteo en home/listado.
2. Lee premio, fechas, cupos, precio y bases.
3. Acepta bases y privacidad.
4. Se registra o participa como invitado.
5. Verifica correo (y teléfono/DNI si aplica).
6. Elige cantidad/número.
7. Reserva y paga (o confirma participación gratuita).
8. Recibe ticket/comprobante.
9. Recibe recordatorio del sorteo.
10. Consulta resultado.
11. Si gana, recibe contacto y reclama.
12. Verifica identidad y recibe el premio.
13. Se conserva evidencia y se cierra el caso.

### 6.5 Contenido mínimo de la página de sorteo

- Nombre y resumen.
- Imagen/portada con alt text.
- Premio(s) y condiciones.
- Fechas: inicio, fin, sorteo, entrega.
- Cupos y disponibilidad.
- Precio y moneda (si aplica).
- Cómo participar.
- Bases y condiciones versionadas.
- Autorización/resolución si corresponde.
- Edad mínima y restricciones.
- Política de privacidad y tratamiento de datos.
- Canal de reclamos.
- Estado y CTA.
- Preguntas frecuentes.
- Compartir (Open Graph).

### 6.6 SEO, accesibilidad y rendimiento

- Títulos y descripciones únicos.
- URLs limpias y estables.
- Open Graph/Twitter Card.
- Datos estructurados solo si son correctos (evitar marcar como “Event” si no corresponde).
- HTML semántico, foco visible, contraste, labels, teclado.
- Imágenes optimizadas (WebP/AVIF) y `loading="lazy"`.
- No bloquear el render con scripts de terceros.
- Caché: páginas de resultados pueden cachearse; checkout y cuenta no.
- Service worker: excluir `/sorteos/*/participar`, `/cuenta`, `/api` y resultados dinámicos, o usar network-first.
- PWA: no prometer offline para pagos o participación.

### 6.7 Analítica y consentimiento

- Medir visitas, conversión, abandono y origen con una herramienta que cumpla privacidad.
- Consentimiento de cookies/analítica si aplica.
- No enviar datos personales a analítica.
- Eventos: ver sorteo, iniciar participación, verificación, reserva, pago, ticket, resultado, reclamo.
- Panel interno con métricas agregadas.

---

## 7. Modelo de datos conceptual

No se implementa en esta fase. Es una guía para la siguiente especificación.

### 7.1 Dominios

| Dominio | Tablas principales |
| --- | --- |
| Administración | `users` (existente), `roles`, `permissions`, `role_permissions`, `admin_2fa`, `admin_sessions` |
| Participantes | `participants`, `participant_verifications`, `participant_sessions`, `blocklist`, `legal_acceptances`, `consents` |
| Sorteos | `raffles`, `raffle_prizes`, `raffle_rules_versions`, `raffle_media`, `raffle_status_history`, `raffle_authorizations` |
| Tickets | `raffle_numbers`, `ticket_reservations`, `tickets`, `ticket_orders`, `ticket_events` |
| Pagos | `payments`, `payment_events`, `refunds`, `receipts`, `reconciliation_runs`, `reconciliation_items` |
| Sorteo | `draws`, `draw_snapshots`, `draw_snapshot_entries`, `draw_seeds`, `draw_results`, `draw_documents`, `draw_audit` |
| Entrega | `prize_deliveries`, `delivery_evidence`, `claim_tokens`, `identity_verifications`, `delivery_events` |
| Comunicación | `notification_templates`, `notifications`, `notification_attempts`, `notification_preferences` |
| Soporte | `support_tickets`, `support_messages`, `claims`, `claim_attachments` |
| Auditoría | `audit_logs`, `audit_log_chain`, `login_attempts`, `security_events` |
| Configuración | `settings` (existente), `feature_flags`, `sequences` |

### 7.2 Reglas transversales

- **Dinero:** enteros en centavos (`amount_cents`), moneda explícita, sin `FLOAT`.
- **Fechas:** `DATETIME` en `America/Lima`; guardar UTC si se integra con pasarelas y convertir en presentación.
- **IDs públicos:** códigos aleatorios (ULID/UUID o base62) para URLs de participantes/tickets; no exponer autoincrementales.
- **Borrado:** lógico para entidades con historial; físico solo con política de retención y auditoría.
- **Inmutabilidad:** bases publicadas, snapshots, resultados, pagos confirmados y auditoría.
- **Foreign keys:** InnoDB, `ON DELETE RESTRICT` en dinero/tickets; `SET NULL` solo donde no rompa historial.
- **Índices:** `raffles.slug`, `raffle_numbers(raffle_id, number)`, `tickets.public_code`, `payments.provider_payment_id`, `participants.email`, `participants.document_hash`.
- **JSON:** solo para metadatos flexibles; no para campos que se consultan/filtran.
- **Auditoría:** `audit_logs` con `id`, `actor_type`, `actor_id`, `action`, `entity_type`, `entity_id`, `before_json`, `after_json`, `ip`, `user_agent`, `created_at`, `previous_hash`, `hash`. Sin `UPDATE`/`DELETE` desde la aplicación.
- **Privacidad:** separar PII de datos operativos; cifrar documento/teléfono/correo si el riesgo lo justifica; enmascarar en UI y exportaciones.

### 7.3 Máquinas de estado resumidas

**Sorteo:** borrador → en_revision → publicado → ventas_cerradas → snapshot → semilla_comprometida → sorteado → resultados_publicados → entrega → cerrado. Excepciones: pausado, cancelado, desierto, reprogramado.

**Ticket:** reservado → pendiente_pago → pagado → válido → sorteado → ganador/no_ganador → entregado. Excepciones: expirado, cancelado, reembolsado, anulado.

**Pago:** pendiente → procesando → aprobado → conciliado → reembolsado/contracargo. Excepciones: rechazado, duplicado, expirado.

**Entrega:** pendiente_contacto → contactado → identidad_verificada → programada → entregada → cerrada. Excepciones: no_reclamado, desierto, suplente.

---

## 8. Arquitectura técnica y operativa propuesta

### 8.1 Enfoque

Mantener PHP nativo + MySQL + front controller, pero agregar una capa de dominio pequeña y explícita:

- `app/raffles/` o `app/domain/` con servicios y repositorios.
- `app/database/migrations/` y `tools/migrate.php`.
- `app/jobs/` y `tools/worker.php` para cron.
- `app/views/public/` para la web nueva.
- `app/views/admin/` para el backoffice.
- Mantener `app/` fuera de `public_html/`.

### 8.2 Módulos/servicios

- `RaffleService`: crear, versionar, publicar, cerrar, cancelar.
- `ParticipantService`: registrar, verificar, bloquear, anonimizar.
- `TicketService`: asignar, reservar, confirmar, anular.
- `PaymentService`: crear orden, procesar webhook, conciliar, reembolsar.
- `DrawService`: snapshot, semilla, hash, ejecutar, publicar.
- `DeliveryService`: contacto, verificación, evidencia, cierre.
- `NotificationService`: plantillas, colas, envío, rebotes.
- `AuditService`: registrar eventos y cadena de hash.
- `ReportService`: consultas y exportaciones.
- `SupportService`: reclamos y tickets.

### 8.3 Jobs y cron

Sin cola dedicada, usar tabla `jobs` + `tools/worker.php` cada minuto desde cPanel:

- Expirar reservas.
- Enviar correos pendientes.
- Reintentar notificaciones.
- Conciliar pagos.
- Generar reportes/PDF.
- Limpiar tokens expirados.
- Alertas de sorteos próximos.

El worker debe ser idempotente, con lock para evitar ejecuciones simultáneas.

### 8.4 Archivos y evidencias

- Guardar fuera de `public_html` (`app/storage/private/`).
- Validar extensión y MIME; si `fileinfo` no está, usar `getimagesize` para imágenes y whitelist estricta; para PDF/otros, validar firma y tamaño.
- Nombres aleatorios, no el nombre original.
- Descarga autenticada y auditada.
- Límite de tamaño y cuota.

### 8.5 Correo

- SMTP autenticado (cPanel o proveedor transaccional).
- SPF, DKIM y DMARC en el dominio.
- Plantillas con variables escapadas.
- Cola y reintentos.
- Registro de rebotes/quejas.
- Separar transaccional de marketing.

### 8.6 Pagos

- Checkout hospedado para reducir PCI.
- Webhook firmado e idempotente.
- Conciliación diaria.
- Reembolsos con doble control.
- Secretos en variables de entorno.
- Logs sin PAN/CVV.
- Pruebas en sandbox y con montos mínimos antes de producción.

### 8.7 PDF/actas

- MVP: HTML imprimible con `window.print()` o generación desde el navegador.
- Fase posterior: librería PDF vendorizada o servicio externo.
- Guardar hash del acta y del snapshot.
- Firma electrónica si se requiere; validar validez legal.

### 8.8 Migraciones y despliegue

- `schema.sql` deja de ser la única fuente; agregar migraciones versionadas.
- `tools/migrate.php` con tabla `schema_migrations`.
- Despliegue con backup previo, migración, verificación y rollback.
- Configuración por entorno (`config.local.php` no se despliega).
- Secretos fuera de `public_html` y del repositorio.

### 8.9 Infraestructura

- **Recomendado:** actualizar a PHP 8.2/8.3, habilitar `fileinfo`, cron, correo transaccional y backups.
- **Si no se puede:** limitar el MVP a sorteo gratuito, sin pagos, sin archivos sensibles y con respaldo manual; aun así, el PHP EOL es un riesgo.
- **No recomendado:** streaming propio, workers permanentes o pagos en el hosting compartido actual sin revisión.

---

## 9. Roadmap por fases

### Fase 0 — Decisiones, legal e infraestructura

- Responder preguntas bloqueantes.
- Validar legal/tributario/AML.
- Definir modelo gratuito/pagado y premios.
- Actualizar PHP y habilitar `fileinfo`/cron/correo si es posible.
- Definir roles, auditoría y respaldos.
- Aprobar requisitos y modelo de datos.

**Salida:** especificación aprobada y entorno mínimo listo.

### Fase 1 — MVP de sorteo

- Gestión de sorteos + bases versionadas.
- Web pública: listado, detalle, bases, FAQ.
- Participación gratuita o con pago manual.
- Participantes con correo verificado y aceptación legal.
- Tickets/participaciones únicas.
- Motor commit-reveal + acta + página de resultados.
- Correo transaccional.
- Auditoría básica, roles mínimos.
- Reportes CSV básicos.

**No incluye:** pasarela automática, OTP, RENIEC, WhatsApp, SUNAT, notaría, live stream, AML automático.

### Fase 2 — Operación y pagos manuales

- Órdenes, reservas, comprobantes, conciliación manual.
- Cuenta de participante e historial.
- Entrega de premios con evidencia.
- Notificaciones y recordatorios.
- Reportes y dashboard.
- Soporte/reclamos.

### Fase 3 — Automatización y cumplimiento avanzado

- Pasarela (Culqi/Niubiz/Izipay/Mercado Pago).
- Webhooks, conciliación automática, reembolsos.
- SUNAT/comprobantes.
- OTP SMS/WhatsApp y RENIEC/KYC.
- Antifraude avanzado.
- Notaría/live stream si aplica.
- Reportes contables/auditoría.

### Fase 4 — Escala

- Multi-sorteo, campañas, analítica avanzada, API, integraciones, BI, soporte multicanal.

**Regla de avance:** no pasar a pagos o datos sensibles sin cerrar la fase legal y de infraestructura.

---

## 10. Matriz de riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
| --- | --- | --- | --- |
| Sorteo sin autorización/incumplimiento | Media | Muy alto | Fase 0 legal; validar autoridad y bases. |
| Fraude/contracargos | Media | Alto | Checkout hospedado, límites, revisión, conciliación. |
| Sobreventa/duplicados | Media | Alto | Unique constraints, transacciones, idempotencia, pruebas. |
| Manipulación percibida del sorteo | Media | Muy alto | Commit-reveal, acta, verificación pública, doble control. |
| Filtración de datos personales | Media | Muy alto | Minimización, cifrado, RBAC, auditoría, pruebas. |
| PHP 8.0 EOL | Alta | Alto | Upgrade antes de pagos; plan de migración. |
| Pérdida de datos | Baja/Media | Muy alto | Backups cifrados, offsite, restauración probada. |
| Correos en spam | Alta | Medio | SPF/DKIM/DMARC, proveedor, monitoreo. |
| Baja conversión por fricción | Media | Medio | Invitado, minimizar datos, UX clara. |
| Soporte desbordado | Media | Medio | FAQ, SLA, macros, ticketing. |
| Cambio normativo | Media | Alto | Revisión legal periódica, versionado. |
| Dependencia de un proveedor | Media | Medio | Diseño de adaptadores, contratos, salida. |

---

## 11. Preguntas abiertas para la siguiente fase

### 11.1 Bloqueantes (sin respuesta no se debe codificar)

1. ¿El sorteo es **gratuito** o **pagado**?
2. ¿Qué se sortea: efectivo, bienes, servicios, viajes, mixto? ¿Valor aproximado?
3. ¿La actividad requiere **autorización/resolución**? ¿De qué autoridad y con qué número?
4. ¿Quién es la entidad organizadora y el titular del RUC?
5. ¿Se emitirán comprobantes electrónicos? ¿Quién los emite?
6. ¿Se aceptarán pagos con pasarela, Yape/Plin o solo manual?
7. ¿Se publicará el nombre del ganador? ¿Con qué consentimiento?
8. ¿Qué plazo y política de reclamo/entrega se aplicará?
9. ¿Se requiere evaluación AML/UIF por el valor o tipo de premio?
10. ¿Se puede actualizar PHP y habilitar `fileinfo`/cron/correo?
11. ¿Quién ejecuta el sorteo y qué doble control se exige?
12. ¿La lista de participantes será pública, anonimizada o solo hash?

### 11.2 Modelo de negocio y alcance

- ¿Cuántos sorteos al mes y cuántos activos a la vez?
- ¿Ticket único, múltiples tickets, paquetes?
- ¿Precio y moneda? ¿Mínimo/máximo por persona?
- ¿Se permite participación presencial o solo web?
- ¿Habrá sorteo gratis como captación y otro pagado?
- ¿Se venderán productos además de tickets?
- ¿Se requiere multi-idioma?
- ¿Cuál es el presupuesto de infraestructura y proveedores?
- ¿Cuál es la fecha objetivo de lanzamiento?

### 11.3 Identidad y participantes

- ¿Cuenta obligatoria o invitado con correo?
- ¿DNI al registrar o al reclamar?
- ¿RENIEC/KYC? ¿Proveedor?
- ¿OTP por SMS/WhatsApp/correo?
- ¿Edad mínima y verificación?
- ¿Cómo se detectan duplicados y cuentas múltiples?
- ¿Quiénes están excluidos y cómo se cargan?
- ¿Retención de datos y política ARCO?
- ¿Marketing con consentimiento separado?

### 11.4 Tickets y pagos

- ¿Números elegidos o asignados?
- ¿Reserva expira en cuántos minutos?
- ¿Se permite transferir/regalar?
- ¿Política de reembolso/contracargo?
- ¿Pasarela preferida y cuenta existente?
- ¿Yape/Plin manual o integrado?
- ¿Quién concilia y con qué frecuencia?
- ¿Se acepta pago manual con comprobante?
- ¿Qué pasa si el pago llega después del cierre?

### 11.5 Sorteo y transparencia

- ¿Algoritmo commit-reveal o beacon externo?
- ¿Quién genera/publica la semilla?
- ¿Doble control o notario?
- ¿Transmisión en vivo? ¿Plataforma?
- ¿Suplentes y reemplazos?
- ¿Qué pasa si el ganador no reclama?
- ¿Se publica acta y hashes?
- ¿Se ofrece verificador interactivo?
- ¿Auditoría externa?

### 11.6 Web pública y operación

- ¿La home sigue siendo la radio o pasa a ser sorteos?
- ¿Se conserva la plantilla Sitejet o se crea un layout nuevo?
- ¿Qué rutas y SEO se priorizan?
- ¿Quién redacta bases, FAQ y contenido?
- ¿Quién atiende soporte y en qué horario?
- ¿Cómo se entregan los premios y quién los custodia?
- ¿Qué evidencia se conserva y por cuánto tiempo?
- ¿Qué reportes necesita contabilidad/auditoría?

---

## 12. Recomendación y siguiente paso

### 12.1 Recomendación

1. **No implementar los 12 módulos a la vez.** Empezar por un MVP de sorteo gratuito o con pago manual, sin pasarela, OTP ni RENIEC.
2. **Cerrar primero la fase legal y de negocio.** Sin autorización, bases y tratamiento de datos, el software no resuelve el problema.
3. **Resolver la infraestructura mínima:** PHP actualizado, cron, correo, backups y `fileinfo` si habrá archivos.
4. **Diseñar el motor commit-reveal desde el inicio.** Es barato y evita rehacer el sorteo después.
5. **Separar participantes de administradores** y crear roles/auditoría antes de manejar dinero.
6. **Construir la web pública nueva** con un layout limpio, no sobre la plantilla Sitejet con estilos inline.
7. **Dejar pagos, OTP, RENIEC, WhatsApp, SUNAT y AML para fases posteriores**, condicionadas a contratos y validación legal.

### 12.2 Taller sugerido (2–3 horas)

1. Modelo de negocio: gratis/pagado, premio, volumen.
2. Legal: autorización, bases, datos, comprobantes, AML.
3. Identidad: invitado vs cuenta, DNI, OTP.
4. Tickets: numeración, reservas, límites.
5. Sorteo: algoritmo, acta, transparencia.
6. Operación: entrega, soporte, evidencia.
7. Infraestructura: PHP, cron, correo, backups.
8. Cierre: alcance MVP, fechas, responsables.

### 12.3 Entregables para aprobar la siguiente fase

- Requisitos funcionales priorizados (MoSCoW).
- Modelo de datos aprobado.
- Máquinas de estado.
- Diseño de rutas públicas y de administración.
- Bases y política de privacidad en borrador legal.
- Decisión de pagos y proveedores.
- Especificación del motor del sorteo.
- Plan de infraestructura y seguridad.
- Presupuesto y cronograma.

### 12.4 Lo que no se hará en esta fase

- No se modifica el código actual.
- No se crean tablas ni migraciones.
- No se integran pasarelas, correo, SMS ni WhatsApp.
- No se publican sorteos.
- No se toman decisiones legales por el usuario.
- No se promete una fecha de lanzamiento sin respuestas.

---

## 13. Anexos

### A. Glosario

- **Sorteo:** evento con reglas, premio y participantes.
- **Participación/ticket:** unidad que representa una oportunidad.
- **Snapshot:** foto inmutable del conjunto de participantes al cierre.
- **Commit-reveal:** publicar el hash de una semilla antes y revelar la semilla después.
- **CSPRNG:** generador de números aleatorios criptográficamente seguro.
- **Idempotencia:** repetir una operación sin duplicar el efecto.
- **Conciliación:** comparar pagos de la pasarela con la base y los tickets.
- **ARCO:** acceso, rectificación, cancelación y oposición.
- **ODP:** oficial de datos personales.
- **SLA:** acuerdo de nivel de servicio.
- **PII:** información personal identificable.

### B. Rutas administrativas propuestas

| Ruta | Módulo |
| --- | --- |
| `/admin/sorteos` | Listado |
| `/admin/sorteos/create` | Crear |
| `/admin/sorteos/{id}/edit` | Editar borrador |
| `/admin/sorteos/{id}/preview` | Vista previa |
| `/admin/sorteos/{id}/publish` | Publicar |
| `/admin/sorteos/{id}/close` | Cerrar ventas |
| `/admin/sorteos/{id}/draw` | Ejecutar sorteo |
| `/admin/sorteos/{id}/results` | Resultados |
| `/admin/participantes` | Participantes |
| `/admin/tickets` | Tickets |
| `/admin/pagos` | Pagos/conciliación |
| `/admin/entregas` | Entrega de premios |
| `/admin/reportes` | Reportes |
| `/admin/soporte` | Reclamos/soporte |
| `/admin/auditoria` | Auditoría |
| `/admin/configuracion` | Ajustes |

### C. Referencias de validación

- Ley N.° 29733, Ley de Protección de Datos Personales y su Reglamento (D.S. N.° 016-2024-JUS). ANPD / Gob.pe.
- INDECOPI, Libro de Reclamaciones: https://consumidor.gob.pe/libro-de-reclamaciones/
- SBS, Prevención de lavado de activos / Sujetos Obligados: https://www.sbs.gob.pe/prevencion-de-lavado-activos/Sujetos-Obligados/Relacion-de-Sujetos-Obligados
- SUNAT, comprobantes electrónicos y obligaciones tributarias: https://www.sunat.gob.pe/
- INDECOPI, protección al consumidor y publicidad.
- Autoridad competente para autorización de sorteos: validar según el tipo de sorteo.

> Verificar siempre la vigencia y aplicabilidad de cada norma con un profesional antes de lanzar.

### D. Supuestos del documento

- El sorteo será operado por una entidad peruana.
- Los participantes serán personas naturales en Perú, mayores de edad.
- La moneda será PEN.
- La zona horaria será `America/Lima`.
- El equipo es pequeño y el hosting es cPanel.
- No se usará blockchain en el MVP.
- No se implementará código en esta fase.

### E. Fuera de alcance de esta fase

- Implementación de los módulos.
- Diseño de base de datos final.
- Contratos con pasarelas, SMS, WhatsApp, RENIEC o notaría.
- Redacción legal definitiva.
- Estimación cerrada de tiempos y costos.
- Compromiso de lanzamiento.

### F. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Análisis inicial de los 12 módulos, viabilidad, relación con la web pública y preguntas abiertas. |
