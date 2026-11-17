# 004 — Decisiones del MVP de sorteos

- **Estado:** decisiones aprobadas. **Sin implementación.**
- **Fecha:** 2026-11-16
- **Base:** `specs/003-sorteos/analisis-modulos.md`
- **Alcance:** fijar el alcance del MVP, las decisiones de producto, pagos manuales, participantes, tickets, sorteo, web pública y operación.
- **Restricción:** no se modifica código, esquema ni infraestructura en esta fase.

> **Nota:** este documento registra decisiones del organizador. Algunas tienen implicancias legales o tributarias que deben validarse antes de publicar. Donde existe una tensión entre “promocional” y “venta de participaciones”, se deja marcada como **riesgo crítico**.

---

## 1. Restricciones fijas del proyecto

Estas reglas no se negocian en el MVP:

1. **Stack actual:** PHP 8 + MySQL/MariaDB + HTML/CSS/JS nativos.
2. **Sin frameworks nuevos**, sin Laravel, sin React, sin Tailwind.
3. **Sin pasarela de pagos automática** en el MVP.
4. **Pagos manuales** con Yape, Plin y transferencia BCP.
5. **El cliente paga fuera del sistema** (app Yape/Plin/banco) y luego sube su comprobante.
6. **El administrador valida el pago manualmente**; no hay webhook ni conciliación automática.
7. Se reutiliza el panel actual, su front controller, PDO, CSRF y el UI kit.
8. La web pública actual (radio) se conserva; se agrega una capa de sorteos.
9. No se agregan servicios obligatorios que rompan el hosting actual.
10. La implementación se hará en una fase posterior, con especificación aprobada.
11. **Arquitectura:** monolito modular por dominio, sin MVC rígido; MySQL/MariaDB con PDO. Ver `specs/006-mvp-sorteos/arquitectura.md`.

---

## 2. Resumen de decisiones

| # | Decisión | Respuesta registrada | Implicancia |
| --- | --- | --- | --- |
| 1 | Modelo de pago | Venta de participaciones con Yape, Plin y BCP | No es gratuito; hay cobro obligatorio. |
| 2 | Sorteos simultáneos | Varios | La web y el panel deben soportar listado y filtros. |
| 3 | Numeración | Número automático único | El sistema asigna; no se elige número. |
| 4 | Venta | Precio único + cantidad | Precio por ticket y cantidad 1–N. |
| 5 | Límite por persona | Atributo del sorteo en el CRUD | `max_tickets_per_participant` configurable por sorteo. |
| 6 | Datos del participante | Ficha completa | Nombre, DNI, celular y correo. |
| 7 | Confirmación de pago | Subir captura + aprobación admin | Cola de revisión manual. |
| 8 | Reserva | 30 minutos | Números bloqueados mientras se paga. |
| 9 | Cuenta pública | Invitado con contacto | Sin contraseña; acceso por código/contacto. |
| 10 | Motor | Commit-reveal | Semilla comprometida y verificable. |
| 11 | Web pública | Home mixta radio + sorteos | Se conserva la radio y se agrega `/sorteos`. |
| 12 | Legal | “No requiere / promocional” | **Sujeto a validación legal por el cobro.** |
| 13 | Notificaciones | Correo + WhatsApp manual | Correo pendiente de configurar; WhatsApp manual. |
| 14 | Transparencia | Ganadores + hashes + lista anonimizada | Verificación pública sin exponer PII. |
| 15 | Datos de pago | Configurables por sorteo | Yape/Plin/BCP con QR, teléfono o cuenta. |
| 16 | Entrega de premios | Seguimiento manual | Estados y evidencia en el panel. |
| 17 | Roles | Un admin con auditoría | Sin roles múltiples en el MVP. |
| 18 | Correo | No disponible; hay que configurarlo | El MVP no depende del correo para operar. |
| 19 | Arquitectura | Monolito modular por dominio, sin MVC rígido | MySQL/MariaDB con PDO; ver especificación 006. |
| 20 | Premios | Catálogo dedicado con fotos, descripción y valor; generalmente productos | El sorteo selecciona premios del catálogo. |
| 21 | Métodos de pago | Módulo independiente: billetera digital o cuenta bancaria, teléfono/cuenta/tarjeta y QR opcional | Cada sorteo activa los métodos que correspondan. |
| 22 | Sobrepago | Número de contacto del dueño visible en la página de pago/confirmación | El cliente contacta para regularizar. |
| 23 | Correo y FAQ | Diferidos | El MVP usa pantalla + WhatsApp manual; sin correo ni FAQ. |

---

## 3. Decisiones detalladas

### 3.1 Modelo: venta de participaciones

El MVP **no es un sorteo gratuito**. Cada participación tiene un precio y se paga por alguno de estos medios:

- Yape.
- Plin.
- Transferencia BCP.

El cliente paga fuera del sistema y el administrador valida el comprobante. No hay cobro automático, tokenización ni pasarela.

**Consecuencias:**

- El ticket solo se emite cuando el pago es aprobado.
- La reserva no es una compra.
- El sistema debe mostrar monto exacto, código de orden y datos del titular.
- El administrador debe tener una cola de pagos pendientes.
- Las devoluciones se gestionan manualmente y se registran.
- La contabilidad/tributación debe revisarse antes de lanzar.

**Riesgo crítico:** el organizador indicó que el sorteo es “promocional / no requiere autorización”, pero también que el cobro es una **venta de participaciones**. Esa combinación debe validarse con abogado y contador. El sistema puede construirse, pero **no debe publicarse** hasta cerrar esta validación. Ver sección 8.

### 3.2 Varios sorteos simultáneos

El MVP debe soportar **más de un sorteo activo a la vez**.

**Consecuencias:**

- Listado público `/sorteos` con estados y filtros.
- Home mixta con sorteos destacados.
- Panel con listado, búsqueda y filtros por estado/fecha.
- Cada sorteo tiene su propio precio, cupos, límite por persona, métodos de pago, bases y sorteo.
- Los números son únicos dentro de cada sorteo, no globales.
- La reserva y el pago se identifican por sorteo + orden.

**Recomendación:** mostrar como máximo 3 sorteos destacados en la home y el resto en el listado, para no saturar la página de radio.

### 3.3 Numeración automática

El sistema asigna un número único por participación dentro de cada sorteo.

**Reglas:**

- `UNIQUE (raffle_id, number)` en base de datos.
- Asignación dentro de una transacción.
- Si el sorteo tiene cupo fijo, se puede precargar el pool de números.
- El cliente no elige número en el MVP.
- El número visible puede ser secuencial con padding (ej. `0001`) o un código público aleatorio; el número interno es el que se usa para el sorteo.
- Un número reservado no puede asignarse a otra persona.
- Al expirar la reserva, el número vuelve a estar disponible.
- Un número pagado no se reasigna sin anulación/reembolso documentado.

**Recomendación:** mostrar un número legible (secuencial) y un código público aleatorio para consultar/verificar, evitando enumeración.

### 3.4 Precio único + cantidad

Cada sorteo define:

- Precio por ticket.
- Moneda (PEN).
- Cantidad mínima (1).
- Cantidad máxima por orden (sugerido: igual o menor al límite por persona).

El cliente elige cuántos tickets quiere, dentro del límite. No hay paquetes con descuento en el MVP.

**Reglas:**

- El monto a pagar = precio × cantidad.
- El monto debe mostrarse en el resumen y en las instrucciones de pago.
- No se permiten pagos parciales.
- Si el cliente paga un monto distinto, el admin rechaza o regulariza con evidencia.
- Si hay promoción o descuento, se define antes de publicar y se versiona en las bases.

### 3.5 Límite por persona configurable

El límite **no es global**. Es un atributo del sorteo:

- `max_tickets_per_participant`.
- Se configura en el CRUD del sorteo.
- Se valida en servidor por DNI, correo y celular.
- Se aplica por participante, no por orden.
- No se puede cambiar después de publicar sin crear una nueva versión de bases y comunicar el cambio.
- Si el límite es 5, una persona puede comprar 5 tickets en una o varias órdenes, pero no 6.

**Riesgo:** si se valida solo por correo, la persona puede usar correos distintos. Por eso se pide DNI y celular. El DNI debe normalizarse/validarse y compararse de forma segura.

### 3.6 Ficha completa del participante

Datos mínimos al comprar:

- Nombre completo.
- DNI.
- Celular.
- Correo electrónico.

**Uso:**

- Contacto y coordinación de pago.
- Control de duplicados y límite por persona.
- Entrega del premio.
- Notificaciones.
- Auditoría.

**Reglas:**

- DNI y celular son datos sensibles; acceso restringido.
- No se muestran completos en la web pública.
- El correo se usa para contacto, pero el MVP no depende de que esté verificado.
- Si el correo no está configurado, el admin puede contactar por WhatsApp manual.
- La ficha se acepta con bases y política de privacidad; se registra versión, fecha, IP y user agent.
- La edad mínima se declara y se valida en el formulario; la verificación documental se hace al reclamar, salvo que la ley exija otra cosa.

### 3.7 Pago manual con comprobante y aprobación

Flujo:

```text
cliente elige cantidad
→ completa ficha
→ acepta bases/privacidad
→ sistema reserva N números (30 min)
→ muestra instrucciones de pago
→ cliente paga por Yape/Plin/BCP
→ cliente sube captura
→ admin revisa y aprueba/rechaza
→ si aprueba: pago confirmado + tickets emitidos
→ si rechaza: se informa motivo; la reserva sigue hasta expirar o se libera
```

**El comprobante es evidencia, no confirmación automática.** El admin debe verificar en su app Yape/Plin o en el banco:

- Monto exacto.
- Fecha/hora.
- Nombre del titular o mensaje.
- Código de orden si el método permite incluirlo.
- Que no sea un comprobante duplicado.

**Estados del pago:**

- `pendiente_comprobante`
- `comprobante_recibido`
- `en_revision`
- `aprobado`
- `rechazado`
- `reembolsado`
- `anulado`

**Reglas:**

- Un pago aprobado no se edita; se reembolsa/anula con evidencia.
- Un comprobante no puede aprobar dos órdenes.
- Si el pago llega después de la expiración, el admin decide: asignar nuevos números disponibles o rechazar/reembolsar.
- Si el cliente paga de más, se registra y se coordina devolución.
- Si el cliente paga de menos, se rechaza o se solicita complemento.
- El admin puede aprobar una orden con reserva expirada solo si hay números disponibles y queda auditado.
- Los comprobantes se guardan en almacenamiento privado.

**Limitación técnica:** el hosting no tiene `fileinfo`. Para el MVP se aceptarán imágenes JPG/PNG/WebP validadas con `getimagesize`, tamaño máximo y nombre aleatorio. PDF de BCP se puede evaluar después; si se acepta, validar cabecera `%PDF` y tamaño. No se sirven archivos desde `public_html`.

### 3.8 Reserva de 30 minutos

- Al confirmar la orden, se reservan los números por 30 minutos.
- La reserva se muestra con cuenta regresiva.
- Al expirar, los números se liberan.
- La expiración se evalúa al consultar y con un proceso programado (cron/worker) si está disponible.
- Si no hay cron, la liberación perezosa al consultar es el respaldo.
- El admin puede extender manualmente una reserva con auditoría.

**Excepción BCP:** una transferencia BCP puede tardar más de 30 minutos. Se recomienda que el tiempo sea configurable por método o que el admin pueda extender la reserva. Para el MVP, se mantiene 30 min por defecto y se documenta la extensión manual.

### 3.9 Participación como invitado

El cliente **no crea una cuenta con contraseña**. Compra como invitado con su ficha.

**Consecuencias:**

- El sistema guarda el historial por DNI/correo/celular.
- El cliente consulta sus participaciones con un código de orden + dato de contacto (propuesta: últimos 4 del celular o DNI).
- El admin puede reenviar el código por WhatsApp manual.
- No hay login público en el MVP.
- No hay recuperación de contraseña.
- La seguridad se basa en códigos aleatorios y rate limiting, no en una sesión de usuario.

**Propuesta de acceso:** pantalla de confirmación con código de orden y código de consulta; enlace `wa.me` prellenado para que el cliente lo guarde. Esta propuesta debe validarse en la especificación 007.

### 3.10 Motor commit-reveal

Al cerrar el sorteo:

1. Se congela el conjunto de tickets **pagados**.
2. Se calcula `snapshot_hash = SHA-256(lista ordenada de tickets)`.
3. Se genera `seed = random_bytes(32)`.
4. Se publica `seed_hash = SHA-256(seed)` y `snapshot_hash`.
5. El día del sorteo se revela la semilla.
6. Se calcula el ganador de forma determinista con `HMAC-SHA256(seed, raffle_id + snapshot_hash + dominio)`.
7. Se publican ganador, suplentes, semilla, hashes y acta.
8. Cualquiera puede verificar con la semilla y la lista.

**Reglas:**

- No se puede elegir el ganador manualmente.
- El snapshot no se modifica después de publicado.
- Los tickets reservados, cancelados o reembolsados no entran.
- Los suplentes se definen con la misma semilla y dominios distintos.
- Si se repite el sorteo, se documenta el motivo y se genera una nueva semilla/hash.
- El acta incluye fecha, hora, algoritmo, versión, hashes, total y resultado.

**No incluido en MVP:** notario, transmisión en vivo, beacon externo, auditoría de terceros. Se pueden agregar después.

### 3.11 Home mixta radio + sorteos

Se conserva la home de radio y se agrega:

- Un bloque de sorteos destacados (máximo 3).
- Un enlace visible a `/sorteos`.
- Un listado completo en `/sorteos`.
- Una página de detalle por sorteo.
- Una página de resultados.
- Enlaces a bases, privacidad y reclamos.

**Ventaja:** no se pierde la audiencia de radio ni el SEO actual. **Riesgo:** la home Sitejet tiene estilos/scripts inline; el bloque nuevo debe integrarse de forma limpia y no arrastrar esa deuda técnica. La implementación exacta se definirá en la especificación 007.

### 3.12 Legal: “promocional / no requiere” con venta de participaciones

**Decisión registrada:** el organizador declara que el sorteo es promocional y no requiere autorización.

**Observación:** también se registra que el cobro es **venta de participaciones**. Esa combinación puede cambiar la naturaleza del sorteo y las obligaciones aplicables. No se debe asumir que “promocional” elimina la necesidad de autorización, bases, tributos o evaluación AML.

**Medidas para el MVP:**

- Incluir un checklist legal antes de publicar.
- Incluir un campo opcional de número de autorización/resolución.
- Incluir bases versionadas, términos, privacidad y Libro de Reclamaciones.
- Registrar aceptación de bases y privacidad.
- Incluir edad mínima y declaración.
- Evaluar inscripción del banco de datos y oficial de datos.
- Evaluar comprobantes/tributación por el cobro.
- Evaluar AML/UIF según el valor y el tipo de premio.
- No publicar hasta que el organizador confirme por escrito que validó la calificación legal.

**Go/No-Go:** el sistema puede desarrollarse, pero el botón “Publicar sorteo” debe exigir que el checklist legal esté completo o que exista una confirmación documentada del organizador. Esta es una recomendación de diseño para la siguiente fase.

### 3.13 Notificaciones: pantalla + WhatsApp manual (correo diferido)

- **Correo:** diferido a una fase posterior; el MVP no depende de SMTP.
- **WhatsApp:** manual. El admin envía el mensaje desde su WhatsApp; el sistema puede generar un enlace `wa.me` con texto prellenado.
- **Pantalla:** confirmación inmediata con código de orden, números, monto y estado.
- **Centro de notificaciones:** no habrá en el MVP.
- **Registro:** el admin puede marcar “notificado por WhatsApp” y dejar constancia.
- Si se configura SMTP después, se podrán enviar confirmaciones y recuperaciones; no es bloqueante.

**Riesgo:** sin correo automático, la trazabilidad depende de la pantalla y del WhatsApp manual. Hay que insistir en que el cliente guarde su código.

### 3.14 Transparencia: ganadores + hashes + lista anonimizada

Se publicará:

- Ganador y suplentes.
- Número de ticket y premio.
- Fecha y hora del sorteo.
- Semilla, hash de semilla y hash del snapshot.
- Algoritmo y versión.
- Total de participaciones válidas.
- Lista anonimizada (código de ticket, sin DNI/correo/celular).
- Acta descargable.
- Instrucciones de verificación.

No se publicarán DNI, correos, teléfonos, direcciones ni comprobantes. Si se publica el nombre del ganador, debe estar en las bases y contar con consentimiento cuando corresponda.

### 3.15 Datos de pago configurables por sorteo

Cada sorteo podrá activar uno o varios métodos:

- Yape: QR, titular, celular, instrucciones.
- Plin: QR, titular, celular, instrucciones.
- BCP: banco, cuenta, CCI, titular, RUC si aplica, instrucciones.

**Modelo recomendado:** métodos de pago reutilizables en un módulo independiente del panel; cada sorteo selecciona los métodos activos y puede sobrescribir instrucciones. El MVP puede empezar con un método por proveedor y asignarlo a cada sorteo.

**Reglas:**

- El QR debe ser una imagen subida al panel y almacenada de forma segura.
- El monto y el código de orden se muestran siempre.
- El cliente debe pagar el monto exacto.
- El método debe estar activo y visible solo en sorteos publicados.
- Cambiar datos de pago después de publicar debe quedar auditado y comunicado.

### 3.16 Entrega de premios con seguimiento manual

El panel tendrá un módulo de entregas:

- Lista de ganadores y suplentes.
- Estado: pendiente de contacto, contactado, identidad verificada, entrega programada, entregado, no reclamado, desierto.
- Registro de contacto (fecha, canal, responsable).
- Evidencia: foto, acta, firma, comprobante (almacenamiento privado).
- Plazo de reclamo definido en las bases.
- Reporte de entregas y pendientes.

No habrá portal público del ganador en el MVP. El contacto es manual (WhatsApp/llamada) y la evidencia la sube el admin.

### 3.17 Un administrador con auditoría

El MVP mantiene **un solo rol administrativo** con auditoría de acciones sensibles:

- Crear/editar/publicar/cerrar sorteo.
- Aprobar/rechazar pago.
- Emitir/anular ticket.
- Ejecutar sorteo.
- Publicar resultados.
- Registrar entrega.
- Cambiar configuración de pagos.

**No incluido:** roles múltiples, permisos granulares, 2FA. Se recomienda 2FA antes de manejar grandes volúmenes, pero no es parte del MVP.

### 3.18 Arquitectura: monolito modular por dominio

No se adopta un MVC rígido. El MVP se organiza como **monolito modular por dominio**:

- `app/modules/raffles`, `participants`, `payments`, `draws`, `deliveries`.
- Handlers finos para HTTP.
- Servicios con reglas de negocio y transacciones.
- Repositorios con SQL/PDO.
- Vistas sin SQL.
- `app/shared` para CSRF, auditoría, archivos, validación y helpers.
- `app/web` para la nueva capa pública; `app/admin` se conserva.

**Base de datos:** MySQL/MariaDB con PDO, migraciones SQL y prepared statements. No se cambia de motor en el MVP y no se usa ORM.

El detalle está en `specs/006-mvp-sorteos/arquitectura.md`.

### 3.19 Premios: catálogo dedicado

Los premios se gestionan desde un **módulo de Premios** dentro del panel, separado del sorteo.

Campos mínimos:

- Nombre del premio.
- Descripción.
- Tipo: generalmente producto; también servicio/otro si se requiere.
- Valor estimado.
- Foto(s) del premio.
- Método de entrega.
- Estado activo/inactivo.

Reglas:

- El premio es un catálogo reutilizable; el sorteo lo selecciona y le asigna una posición.
- El **precio del boleto** y el **máximo de boletos por persona** se configuran en el sorteo, no en el premio.
- Las fotos se validan y se almacenan de forma controlada; no se permite HTML ni archivos ejecutables.
- Un premio puede estar en un sorteo activo o en borrador; no se elimina si tiene un sorteo asociado.
- Para el MVP, se admite un premio principal y premios secundarios opcionales.

### 3.20 Métodos de pago: módulo independiente

Los métodos de pago se gestionan desde un **módulo de Métodos de pago**, separado del sorteo.

Campos mínimos:

- Nombre visible.
- Tipo: **billetera digital** o **cuenta bancaria**.
- Proveedor: Yape, Plin, BCP u otro.
- Titular.
- Teléfono (billetera).
- Número de cuenta/tarjeta (banco).
- CCI (banco).
- Banco.
- Imagen QR opcional.
- Teléfono de contacto para sobrepagos.
- Instrucciones.
- Estado activo/inactivo.

Reglas:

- Cada sorteo activa los métodos que correspondan.
- El monto exacto y el código de orden se muestran siempre.
- No hay integración automática; el pago es manual.
- El QR es opcional; si no existe, se muestran los datos de teléfono/cuenta.
- Cambiar un método después de publicar un sorteo queda auditado.

### 3.21 Sobrepagos y contacto del dueño

Si el cliente paga de más, la página de pago/confirmación mostrará el **número de contacto del dueño** para regularizar.

Reglas:

- El número debe estar vigente y visible en la página de pago y en la confirmación.
- Puede ser el teléfono del método de pago o un número de soporte configurable.
- El administrador registra el sobrepago y decide: devolución, compensación o ajuste.
- No se emite ticket adicional por un sobrepago sin autorización y auditoría.

### 3.22 Correo y FAQ diferidos

- **Correo:** no se implementa en el MVP; queda para una fase posterior.
- **FAQ:** no se implementa en el MVP; queda para una fase posterior.
- El MVP usa pantalla de confirmación, número de contacto y WhatsApp manual.
- Las páginas legales (bases, privacidad, términos y Libro de Reclamaciones) sí se incluyen.

---

## 4. Alcance del MVP

### 4.1 Incluido

- Gestión de sorteos: crear, editar borrador, publicar, cerrar, cancelar.
- Varios sorteos simultáneos.
- Catálogo de premios con fotos, descripción, tipo producto y valor estimado.
- Asignación de premios al sorteo.
- Bases versionadas.
- Número automático único por sorteo.
- Precio único + cantidad.
- Límite por persona configurable por sorteo.
- Ficha completa del participante.
- Compra como invitado.
- Reserva de 30 minutos.
- Pago manual con Yape, Plin o BCP.
- Módulo independiente de métodos de pago: billetera digital o cuenta bancaria, teléfono/cuenta/tarjeta, QR opcional.
- Configuración de métodos de pago por sorteo.
- Subida de comprobante.
- Número de contacto del dueño visible para sobrepagos.
- Cola de revisión y aprobación manual.
- Emisión de ticket al aprobar.
- Consulta de participaciones con código.
- Cierre de ventas.
- Snapshot y sorteo commit-reveal.
- Página pública de resultados con hashes y lista anonimizada.
- Bases, términos, privacidad y Libro de Reclamaciones.
- Seguimiento manual de entrega de premios.
- Auditoría de acciones críticas.
- Reportes mínimos: dashboard y CSV.

### 4.2 Fuera del MVP

- Pasarela automática (Culqi, Niubiz, Izipay, Mercado Pago).
- API de Yape/Plin o conciliación automática.
- API oficial de WhatsApp.
- OTP SMS, RENIEC/KYC.
- Comprobantes electrónicos SUNAT.
- Notaría, transmisión en vivo, beacon externo.
- AML/KYC automatizado.
- Cuenta pública con contraseña.
- Portal del ganador.
- Roles múltiples y 2FA.
- Multi-idioma.
- Analítica avanzada/BI.
- App móvil nativa.
- Correo electrónico automático.
- Preguntas frecuentes (FAQ).

### 4.3 Pendiente de configuración

- `fileinfo` o validación alternativa de archivos.
- Cron/worker para expiración y recordatorios.
- Backups.
- Dominio/SSL.
- Textos legales con plantillas para revisión.
- Producto, descripción y valor del premio.
- Autorización/resolución (si corresponde).
- Tributación/comprobantes.
- SMTP/correo (diferido a una fase posterior).
- FAQ (diferido a una fase posterior).

---

## 5. Flujos del MVP

### 5.1 Flujo administrativo

1. Admin crea sorteo en borrador.
2. Define premio, precio, cupos, límite por persona, fechas, métodos de pago y bases.
3. Completa checklist legal.
4. Publica.
5. Administra participantes y pagos pendientes.
6. Aprueba/rechaza comprobantes.
7. Emite tickets al aprobar.
8. Cierra ventas a la fecha/hora.
9. Genera snapshot y publica hash de semilla.
10. Ejecuta sorteo en la fecha.
11. Publica resultados y acta.
12. Contacta ganadores y registra entrega.
13. Cierra el sorteo.

### 5.2 Flujo público

1. Cliente llega a la home mixta o `/sorteos`.
2. Ve sorteos publicados y elige uno.
3. Lee premio, precio, fechas, cupos y bases.
4. Indica cantidad.
5. Completa ficha y acepta bases/privacidad.
6. El sistema reserva números por 30 min.
7. El cliente ve instrucciones de pago (QR, teléfono, cuenta BCP, monto, código).
8. Paga por Yape/Plin/BCP.
9. Sube captura.
10. Espera aprobación.
11. Al aprobar, ve/recibe sus números y código.
12. Consulta estado con código.
13. Espera sorteo y consulta resultados.
14. Si gana, coordina reclamo y entrega.

### 5.3 Flujo de pago manual

```text
orden creada
→ números reservados
→ instrucciones mostradas
→ comprobante subido
→ admin revisa contra app/banco
→ aprueba → tickets emitidos
→ rechaza → motivo + opción de reintento
→ expira → números liberados
```

**Importante:** el comprobante no confirma el pago por sí solo. La aprobación es humana y auditada.

---

## 6. Rutas propuestas

### 6.1 Públicas

| Ruta | Uso |
| --- | --- |
| `/` | Home mixta radio + sorteos destacados |
| `/sorteos` | Listado de sorteos |
| `/sorteos/{slug}` | Detalle del sorteo |
| `/sorteos/{slug}/participar` | Ficha, cantidad, reserva |
| `/sorteos/{slug}/pago/{orden}` | Instrucciones y subida de comprobante |
| `/sorteos/{slug}/resultados` | Resultados y verificación |
| `/participaciones` | Consulta por código |
| `/legal/bases/{slug}` | Bases versionadas |
| `/legal/privacidad` | Privacidad |
| `/legal/terminos` | Términos |
| `/libro-de-reclamaciones` | Libro virtual |
| `/contacto` | Soporte y WhatsApp |

### 6.2 Administrativas

| Ruta | Uso |
| --- | --- |
| `/admin/raffles` | Listado |
| `/admin/raffles/create` | Crear |
| `/admin/raffles/{id}/edit` | Editar borrador |
| `/admin/raffles/{id}/publish` | Publicar |
| `/admin/raffles/{id}/close` | Cerrar ventas |
| `/admin/raffles/{id}/draw` | Ejecutar sorteo |
| `/admin/raffles/{id}/results` | Resultados |
| `/admin/prizes` | Catálogo de premios |
| `/admin/payments` | Cola de comprobantes |
| `/admin/participants` | Participantes |
| `/admin/tickets` | Tickets |
| `/admin/deliveries` | Entrega de premios |
| `/admin/audit` | Auditoría |
| `/admin/payment-methods` | Métodos Yape/Plin/BCP |
| `/admin/reports` | Reportes |

---

## 7. Modelo conceptual para la siguiente especificación

No se implementa ahora. Entidades mínimas:

- `raffles`: sorteo, estado, precio, cupos, límite por persona, fechas, bases.
- `prizes`: catálogo de premios con foto, descripción, tipo producto y valor.
- `raffle_prizes`: asignación de premios del catálogo al sorteo.
- `raffle_rules_versions`: bases versionadas inmutables.
- `payment_methods`: tipo billetera/banco, proveedor, titular, teléfono, cuenta/tarjeta, CCI, QR opcional, contacto, instrucciones.
- `raffle_payment_methods`: métodos activos por sorteo.
- `participants`: nombre, DNI, celular, correo, estado.
- `orders`: participante, sorteo, cantidad, monto, estado, código público.
- `raffle_numbers`: números disponibles, reservados, pagados o cancelados.
- `payment_proofs`: archivo, monto declarado, estado, revisor, observación.
- `payments`: confirmación manual, monto, método, aprobado_por, fecha.
- `tickets`: número, participante, orden, estado, código público.
- `draws`: snapshot, semilla, hash, algoritmo, ejecutor, fecha.
- `draw_results`: ganador, suplentes, ticket, posición.
- `deliveries`: ganador, estado, evidencia, responsable.
- `audit_logs`: actor, acción, entidad, antes/después, IP, fecha.
- `legal_acceptances`: participante, versión de bases/privacidad, fecha, IP.

**Reglas clave:**

- `UNIQUE (raffle_id, number)`.
- `UNIQUE (order_code)`.
- `UNIQUE (ticket_public_code)`.
- Montos en centavos.
- Fechas en `America/Lima`.
- Snapshots y bases publicadas inmutables.
- Auditoría append-only.

---

## 8. Riesgos y condiciones de lanzamiento

### 8.1 Riesgo legal principal

**Declaración del organizador:** “no requiere / promocional”.
**Realidad registrada:** “sí, es venta de participaciones”.

Estas dos afirmaciones pueden ser incompatibles. Antes de publicar:

- Validar con abogado si el cobro convierte el sorteo en una actividad que requiere autorización.
- Validar bases, edad, premios, publicidad y reclamos.
- Validar obligaciones tributarias por el cobro.
- Validar si aplica evaluación AML/UIF por el valor del premio.
- Validar inscripción del banco de datos y política de privacidad.
- Validar si se requiere número de autorización/resolución visible.

**Condición:** el sistema puede desarrollarse, pero el lanzamiento queda sujeto a esta validación. El checklist legal del panel no reemplaza la asesoría.

### 8.2 Riesgos operativos

- Aprobación manual de pagos: requiere revisar Yape/Plin/BCP uno por uno.
- Comprobantes falsos o duplicados: validar monto, fecha, titular y código.
- BCP puede tardar más de 30 minutos: definir extensión o tiempo distinto.
- Sin correo: la comunicación depende de pantalla y WhatsApp manual.
- Sin `fileinfo`: validar imágenes con `getimagesize` y límites.
- Sin cron: la expiración de reservas puede depender de consultas y limpieza manual.
- Varios sorteos: mayor carga de revisión y soporte.

### 8.3 Riesgos técnicos

- Race conditions en asignación de números: usar transacciones y unique constraints.
- Duplicados de pago: idempotencia y revisión.
- Archivos maliciosos: validación, almacenamiento privado, nombres aleatorios.
- Fuga de DNI/celular: cifrado/ hash, accesos restringidos, no publicar.
- PHP 8.0 EOL: riesgo de seguridad; plan de upgrade.
- Backups: definir y probar restauración.

### 8.4 Go/No-Go

**Go para desarrollo:** decisiones de MVP cerradas, arquitectura 006 y especificación 007 aprobadas.
**No-Go para lanzamiento:** falta validación legal del cobro, bases/privacidad, y configuración mínima de pagos/seguridad.

---

## 9. Criterios de aceptación del MVP

1. El admin crea un sorteo con precio, cupos, límite por persona, métodos de pago y bases.
2. El sorteo se publica solo si el checklist legal mínimo está completo.
3. La home mixta muestra sorteos destacados y `/sorteos` lista todos los publicados.
4. El cliente compra como invitado con nombre, DNI, celular y correo.
5. El sistema asigna números únicos y reserva por 30 minutos.
6. El cliente ve instrucciones de Yape/Plin/BCP con QR/datos y monto exacto.
7. El cliente sube comprobante; el admin lo aprueba o rechaza.
8. Al aprobar, se emiten tickets y se muestra confirmación con código.
9. No se puede vender el mismo número dos veces.
10. El límite por persona se valida por DNI/correo/celular.
11. Al cerrar ventas, solo entran tickets pagados.
12. El sorteo commit-reveal produce un resultado reproducible.
13. La página de resultados muestra ganador, suplentes, semilla, hashes y lista anonimizada.
14. El admin registra contacto y entrega del premio.
15. Las acciones críticas quedan en auditoría.
16. El sistema funciona en el stack actual, sin frameworks nuevos.
17. El admin gestiona premios con foto, descripción, tipo producto y valor.
18. El admin gestiona métodos de pago con tipo billetera/banco, teléfono/cuenta/tarjeta y QR opcional.
19. La página de pago muestra el número de contacto del dueño para sobrepagos.
20. El MVP no depende de correo ni FAQ para operar.

---

## 10. Preguntas menores pendientes (no bloquean el diseño, sí la operación)

1. ¿El tiempo de reserva será único o configurable por método? (BCP puede necesitar más).
2. ¿Se aceptará PDF de BCP o solo imagen?
3. ¿Cómo se consultará “mis participaciones” sin correo? (código + celular/DNI).
4. ¿Se publicará el nombre del ganador? ¿Con qué consentimiento?
5. ¿Qué producto específico y valor tendrá el primer premio? (afecta legal/AML).
6. ¿Se emitirán comprobantes por el cobro?
7. ¿Quién atiende WhatsApp y en qué horario?
8. ¿Habrá 2FA antes de manejar montos altos?
9. ¿Qué política de reembolso se aplicará?
10. ¿Se aprueban las plantillas legales base o se ajustan con abogado?

---

## 11. Siguiente paso

1. Validar la calificación legal del cobro con abogado/contador.
2. Confirmar producto/valor de premio y política de reembolso.
3. Configurar `fileinfo`/validación de archivos, cron y backups si es posible. SMTP queda diferido.
4. Usar la especificación 007 aprobada como base de implementación.
5. Implementar por fases: premios + sorteo + web + participantes + tickets + pagos manuales + motor + transparencia + entrega + auditoría.

**No se publica ni se cobra hasta completar el Gate 0. La Fase 1 técnica puede avanzar en paralelo.**

---

## 12. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Decisiones del MVP registradas a partir del cuestionario. |
