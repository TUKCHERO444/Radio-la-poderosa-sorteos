# 007 — Especificación funcional y técnica del MVP de sorteos

- **Estado:** aprobada. **Sin implementación.**
- **Fecha:** 2026-11-16
- **Base:** `specs/004-mvp-sorteos/decisiones.md`, `specs/005-mvp-sorteos/plan-fases.md`, `specs/006-mvp-sorteos/arquitectura.md` y `specs/006-mvp-sorteos/convenciones.md`.
- **Plantillas legales:** `specs/007-mvp-sorteos/plantillas-legales.md` (borradores para revisión).
- **Objetivo:** convertir las decisiones del MVP en requisitos verificables, modelo de datos, rutas, pantallas, flujos y criterios de aceptación.
- **Restricción:** no se modifica código en esta fase.

> **Aviso legal:** el organizador declara el sorteo como promocional/no sujeto a autorización, pero también registra que el cobro es una venta de participaciones. Esa combinación debe validarse antes de publicar. La especificación incluye un checklist legal y una puerta de publicación; no reemplaza asesoría legal.

---

## 1. Alcance del MVP

### 1.1 Incluido

- Varios sorteos simultáneos.
- Creación, publicación, cierre y cancelación de sorteos.
- Catálogo de premios con fotos, descripción, tipo producto y valor estimado.
- Asignación de premios al sorteo.
- Bases versionadas.
- Participación como invitado con nombre, DNI, celular y correo.
- Números únicos asignados automáticamente.
- Precio único por ticket y cantidad.
- Límite de tickets por persona configurable por sorteo.
- Reserva de 30 minutos (extensible para BCP).
- Pagos manuales por Yape, Plin y transferencia BCP.
- Módulo independiente de métodos de pago (billetera/banco, teléfono/cuenta/tarjeta, QR opcional).
- Subida de comprobante y aprobación manual.
- Número de contacto del dueño para sobrepagos.
- Emisión de tickets solo con pago aprobado.
- Cierre de ventas, snapshot y sorteo commit-reveal.
- Resultados públicos con hashes y lista anonimizada.
- Entrega de premios con seguimiento manual.
- Auditoría de acciones críticas.
- Páginas legales y Libro de Reclamaciones.
- Home mixta radio + sorteos.
- Reportes mínimos y exportación CSV.
- WhatsApp manual y pantalla de confirmación.
- Correo y FAQ: diferidos.

### 1.2 Fuera del MVP

- Pasarela automática.
- API Yape/Plin/BCP.
- API oficial de WhatsApp.
- OTP/RENIEC/KYC.
- Comprobantes electrónicos SUNAT.
- Notaría, live stream, beacon externo.
- AML automatizado.
- Cuenta pública con contraseña.
- Portal del ganador.
- Roles múltiples, 2FA.
- Multi-idioma.
- Analítica avanzada/BI.
- App móvil.
- Correo electrónico automático.
- Preguntas frecuentes (FAQ).

---

## 2. Actores

| Actor | Descripción | Permisos |
| --- | --- | --- |
| Administrador | Único rol del panel en el MVP | Crear/publicar/cerrar sorteos, revisar pagos, emitir tickets, sortear, publicar resultados, registrar entregas, ver auditoría. |
| Participante | Persona que compra/participa | Ver sorteos, registrarse como invitado, reservar, subir comprobante, consultar estado y resultados. |
| Visitante | Persona que no ha participado | Ver home, listado, detalle, bases, resultados y páginas legales. |
| Sistema | Procesos internos | Expirar reservas, calcular snapshot/hash, registrar auditoría, generar reportes. |

---

## 3. Glosario

- **Sorteo/raffle:** evento con premio, reglas, fechas y participaciones.
- **Ticket:** unidad pagada que representa una participación y tiene un número único.
- **Orden:** intención de compra con cantidad, monto y datos del participante.
- **Reserva:** bloqueo temporal de números mientras se paga.
- **Comprobante:** imagen subida por el cliente como evidencia del pago.
- **Pago:** registro financiero aprobado/rechazado por el administrador.
- **Snapshot:** lista inmutable de tickets pagados al cierre.
- **Semilla/seed:** valor aleatorio usado para calcular el ganador.
- **Commit-reveal:** publicar el hash de la semilla antes y revelar la semilla después.
- **Acta:** documento con el resultado y la evidencia.
- **Participante invitado:** persona sin cuenta/contraseña, identificada por sus datos y códigos.

---

## 4. Requisitos funcionales

### 4.1 Administración de sorteos

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-ADM-01 | Crear sorteo en borrador con nombre, slug, resumen, descripción, selección de premios, precio, cupos, límite por persona, edad mínima y fechas. | Must |
| RF-ADM-02 | Editar un sorteo en borrador; no editar campos críticos después de publicar. | Must |
| RF-ADM-03 | Gestionar un catálogo de premios: nombre, descripción, tipo producto, valor estimado, fotos y método de entrega. | Must |
| RF-ADM-04 | Crear y publicar versiones de bases y condiciones; una versión publicada es inmutable. | Must |
| RF-ADM-05 | Asociar métodos de pago Yape, Plin y BCP al sorteo. | Must |
| RF-ADM-06 | Gestionar un módulo independiente de métodos de pago: billetera digital o cuenta bancaria, titular, teléfono, cuenta/tarjeta, CCI, QR opcional e instrucciones. | Must |
| RF-ADM-07 | Completar un checklist legal antes de publicar. | Must |
| RF-ADM-08 | Publicar, pausar, cerrar ventas, cancelar y reprogramar con motivo y auditoría. | Must |
| RF-ADM-09 | Ver listado de sorteos con búsqueda y filtros por estado/fecha. | Must |
| RF-ADM-10 | Ver una vista previa pública del sorteo antes de publicar. | Should |
| RF-ADM-11 | Marcar sorteos destacados para la home (máximo 3). | Should |
| RF-ADM-12 | Ver dashboard con sorteos activos, pagos pendientes y próximos sorteos. | Should |
| RF-ADM-13 | Asignar premios del catálogo al sorteo y definir su posición. | Must |
| RF-ADM-14 | Configurar el precio del boleto y el máximo de boletos por persona en el sorteo. | Must |
| RF-ADM-15 | Mostrar el número de contacto del dueño para sobrepagos. | Must |
| RF-ADM-16 | Permitir activar/desactivar métodos de pago sin eliminarlos si tienen historial. | Should |

**Reglas:**

- No se publica sin premio, precio, fechas, cupos, bases y checklist legal.
- El precio y el límite por persona no se cambian después de publicar; si se requiere, se crea una nueva versión y se comunica.
- Un sorteo con tickets/pagos no se elimina; se cancela.
- La vista previa no es pública ni indexable.

### 4.2 Web pública

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-WEB-01 | Home mixta que conserva la radio y muestra hasta 3 sorteos destacados. | Must |
| RF-WEB-02 | Listado `/sorteos` con sorteos publicados, cerrados o sorteados. | Must |
| RF-WEB-03 | Detalle `/sorteos/{slug}` con premio, fechas, precio, cupos, métodos de pago, bases y CTA. | Must |
| RF-WEB-04 | Mostrar estado del sorteo y cuenta regresiva. | Must |
| RF-WEB-05 | Mostrar bases versionadas, autorización si existe, privacidad y términos. FAQ queda diferida. | Must |
| RF-WEB-06 | Página de resultados con ganador, suplentes, semilla, hashes y lista anonimizada. | Must |
| RF-WEB-07 | Página de consulta de participaciones por código. | Must |
| RF-WEB-08 | Libro de Reclamaciones virtual. | Must |
| RF-WEB-09 | SEO: título, descripción, canonical, Open Graph. | Should |
| RF-WEB-10 | Service worker con exclusión o network-first para rutas dinámicas. | Must |
| RF-WEB-11 | Responsive y accesible (WCAG AA razonable). | Must |
| RF-WEB-12 | Mostrar el número de contacto del dueño en pago/confirmación para sobrepagos. | Must |

**Reglas:**

- Un sorteo en borrador no se muestra.
- No se muestran datos personales de participantes.
- Los enlaces de pago y consulta usan HTTPS.
- El CTA se deshabilita si el sorteo no está publicado o no hay cupos.
- La home no se rompe si no hay sorteos.

### 4.3 Participantes

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-PAR-01 | Registro como invitado con nombre completo, DNI, celular y correo. | Must |
| RF-PAR-02 | Validar formato de DNI, celular, correo y campos obligatorios. | Must |
| RF-PAR-03 | Declaración de mayoría de edad y aceptación de bases/privacidad. | Must |
| RF-PAR-04 | Registrar versión, hash, fecha, IP y user agent de la aceptación. | Must |
| RF-PAR-05 | Detectar duplicados por DNI; correo/celular como señales secundarias. | Must |
| RF-PAR-06 | Aplicar límite de tickets por persona por sorteo. | Must |
| RF-PAR-07 | Consultar participaciones con código de orden + código de acceso. | Must |
| RF-PAR-08 | Permitir que el admin reenvíe el código por WhatsApp manual. | Should |
| RF-PAR-09 | No exponer DNI/correo/celular en páginas públicas. | Must |

**Reglas:**

- El DNI se normaliza y se guarda con hash para duplicados y cifrado para visualización autorizada.
- El correo no se verifica en el MVP si no hay SMTP; no es bloqueante.
- El límite se valida en servidor; no se confía en el formulario.
- Un participante con orden pagada no puede superar el límite.
- La edad se declara; la verificación documental se realiza al reclamar.

### 4.4 Órdenes, reservas y tickets

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-TIC-01 | Crear orden con sorteo, participante, cantidad, precio unitario y total. | Must |
| RF-TIC-02 | Asignar números únicos automáticamente por sorteo. | Must |
| RF-TIC-03 | Reservar números por 30 minutos. | Must |
| RF-TIC-04 | Liberar números al expirar o cancelar. | Must |
| RF-TIC-05 | Impedir duplicados y sobreventa con transacción + unique. | Must |
| RF-TIC-06 | Emitir tickets solo cuando el pago esté aprobado. | Must |
| RF-TIC-07 | Generar código público de orden y código de acceso. | Must |
| RF-TIC-08 | Mostrar confirmación con números, monto, estado e instrucciones. | Must |
| RF-TIC-09 | Permitir extensión manual de reserva para BCP. | Should |
| RF-TIC-10 | Permitir anulación/reembolso con auditoría. | Should |

**Reglas:**

- `UNIQUE (raffle_id, number)`.
- La asignación se hace en transacción.
- Un número reservado no se asigna a otra orden.
- Un ticket pagado no se reasigna sin anulación documentada.
- Si la reserva expira antes del pago, el admin decide: nuevos números o rechazo.
- La cantidad máxima por orden no supera el límite por persona.

### 4.5 Pagos manuales

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-PAY-01 | Mostrar métodos activos del sorteo: Yape, Plin, BCP. | Must |
| RF-PAY-02 | Mostrar monto exacto, código de orden e instrucciones. | Must |
| RF-PAY-03 | Mostrar QR, titular, teléfono o cuenta/CCI según método. | Must |
| RF-PAY-04 | Permitir subir comprobante JPG/PNG/WebP. | Must |
| RF-PAY-05 | Validar tamaño, tipo y contenido de imagen. | Must |
| RF-PAY-06 | Guardar comprobante en almacenamiento privado. | Must |
| RF-PAY-07 | Registrar hash del archivo para detectar duplicados. | Should |
| RF-PAY-08 | Cola de pagos pendientes para el admin. | Must |
| RF-PAY-09 | Aprobar pago y emitir tickets. | Must |
| RF-PAY-10 | Rechazar pago con motivo y permitir reintento. | Must |
| RF-PAY-11 | Detectar monto distinto, comprobante duplicado y reserva expirada. | Must |
| RF-PAY-12 | Registrar auditoría de aprobación/rechazo. | Must |
| RF-PAY-13 | Registrar reembolso manual. | Should |

**Reglas:**

- El comprobante es evidencia; la aprobación es humana.
- No se emite ticket sin pago aprobado.
- Un comprobante no puede aprobar dos órdenes.
- El admin verifica monto, fecha, titular/código y que no esté duplicado.
- Si el monto no coincide, se rechaza o se regulariza con nota y evidencia.
- Si el pago llega tarde, el admin decide con números disponibles.
- BCP puede requerir extensión de reserva.

### 4.6 Sorteo commit-reveal

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-DRW-01 | Cerrar ventas y congelar tickets pagados. | Must |
| RF-DRW-02 | Calcular hash del snapshot. | Must |
| RF-DRW-03 | Generar semilla CSPRNG y hash de semilla. | Must |
| RF-DRW-04 | Publicar hash de semilla y snapshot antes del sorteo. | Must |
| RF-DRW-05 | Ejecutar sorteo con algoritmo determinista. | Must |
| RF-DRW-06 | Seleccionar ganador y suplentes. | Must |
| RF-DRW-07 | Revelar semilla y publicar resultado. | Must |
| RF-DRW-08 | Generar acta con fecha, algoritmo, hashes y resultado. | Must |
| RF-DRW-09 | Permitir verificación pública. | Must |
| RF-DRW-10 | Documentar reprogramación/redraw con nueva semilla. | Should |
| RF-DRW-11 | No permitir edición manual del ganador. | Must |

**Reglas:**

- Solo entran tickets pagados y válidos al cierre.
- El snapshot no se modifica después de publicado.
- La semilla no se registra en logs antes de revelarse.
- El acta y los hashes deben coincidir.
- El resultado se calcula con `HMAC-SHA256(seed, raffle_id + snapshot_hash + dominio)`.

### 4.7 Transparencia

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-TRA-01 | Publicar ganador y suplentes con número de ticket y premio. | Must |
| RF-TRA-02 | Publicar semilla, hash de semilla, hash de snapshot y algoritmo. | Must |
| RF-TRA-03 | Publicar lista anonimizada de participaciones. | Must |
| RF-TRA-04 | Publicar acta descargable. | Must |
| RF-TRA-05 | Explicar cómo verificar el resultado. | Should |
| RF-TRA-06 | No publicar DNI, correo, celular, dirección ni comprobantes. | Must |
| RF-TRA-07 | Mantener historial de sorteos. | Should |

**Reglas:**

- Si se publica el nombre del ganador, debe estar en las bases y contar con consentimiento cuando corresponda.
- La lista anonimizada usa códigos de ticket, no datos personales.
- La página de resultados es estable y cacheable.

### 4.8 Entrega de premios

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-DEL-01 | Listar ganadores y suplentes por sorteo. | Must |
| RF-DEL-02 | Registrar contacto, fecha, canal y responsable. | Must |
| RF-DEL-03 | Registrar verificación de identidad. | Must |
| RF-DEL-04 | Registrar entrega con evidencia privada. | Must |
| RF-DEL-05 | Gestionar estados: pendiente, contactado, verificado, programado, entregado, no reclamado, desierto. | Must |
| RF-DEL-06 | Respetar plazo de reclamo de las bases. | Must |
| RF-DEL-07 | Permitir suplente cuando corresponda. | Should |

**Reglas:**

- No se entrega sin verificar identidad y aceptación de bases.
- La evidencia no es pública.
- El plazo y las consecuencias de no reclamar están en las bases.
- El admin registra todo; no hay portal del ganador en el MVP.

### 4.9 Auditoría

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-AUD-01 | Registrar creación, edición, publicación, cierre, cancelación y reprogramación. | Must |
| RF-AUD-02 | Registrar aprobación/rechazo de pagos y emisión/anulación de tickets. | Must |
| RF-AUD-03 | Registrar ejecución, publicación de resultados y entrega. | Must |
| RF-AUD-04 | Guardar actor, acción, entidad, antes/después, IP, user agent y fecha. | Must |
| RF-AUD-05 | Ser append-only, sin edición/borrado desde la aplicación. | Must |
| RF-AUD-06 | Permitir consulta filtrada al admin. | Should |

**Reglas:**

- La auditoría de cambios críticos se registra en la misma transacción.
- No se guardan DNI/celular/comprobantes en los logs.
- Los logs técnicos van a archivo privado, separados de la auditoría de negocio.

### 4.10 Reportes

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-REP-01 | Dashboard con sorteos, tickets pagados, ingresos, pagos pendientes y entregas. | Should |
| RF-REP-02 | Exportar CSV de pagos, tickets, participantes y entregas. | Must |
| RF-REP-03 | Reporte de conciliación manual por método. | Should |
| RF-REP-04 | Reporte de entregas pendientes. | Should |
| RF-REP-05 | No incluir datos personales innecesarios. | Must |

### 4.11 Notificaciones

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-NOT-01 | Mostrar confirmación en pantalla con código, números, monto y estado. | Must |
| RF-NOT-02 | Generar enlace `wa.me` con mensaje prellenado. | Should |
| RF-NOT-03 | Permitir al admin marcar “notificado por WhatsApp”. | Should |
| RF-NOT-04 | Correo y FAQ: diferidos a una fase posterior. | Diferido |
| RF-NOT-05 | Registrar intentos/errores de notificación manual. | Should |

**Reglas:**

- El correo y la FAQ no son requisitos del MVP.
- No se envía marketing sin consentimiento.
- Los mensajes no incluyen DNI ni datos sensibles innecesarios.
- El número de contacto del dueño se muestra para sobrepagos.

### 4.12 Legal y cumplimiento

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF-LEG-01 | Publicar bases versionadas por sorteo. | Must |
| RF-LEG-02 | Publicar política de privacidad y términos. | Must |
| RF-LEG-03 | Publicar Libro de Reclamaciones virtual. | Must |
| RF-LEG-04 | Registrar aceptación de bases/privacidad. | Must |
| RF-LEG-05 | Incluir edad mínima y declaración de mayoría de edad. | Must |
| RF-LEG-06 | Incluir campo de autorización/resolución si corresponde. | Must |
| RF-LEG-07 | Checklist legal antes de publicar. | Must |
| RF-LEG-08 | Confirmación del organizador sobre calificación legal. | Must |
| RF-LEG-09 | Política de reembolso y no reclamado en bases. | Must |
| RF-LEG-10 | Evaluar inscripción de banco de datos y ODP. | Should |
| RF-LEG-11 | Usar plantillas base de revisión legal para Perú (bases, privacidad, términos y Libro de Reclamaciones). | Must |
| RF-LEG-12 | Mostrar el número de contacto del dueño para sobrepagos. | Must |

**Reglas:**

- El sistema no puede garantizar el cumplimiento legal; solo deja evidencia.
- El checklist no reemplaza asesoría.
- Las plantillas legales de `specs/007-mvp-sorteos/plantillas-legales.md` son borradores para revisión de abogado; no se consideran definitivas hasta su aprobación.
- Si el organizador no confirma la calificación legal, el sorteo queda en borrador.

---

## 5. Reglas de negocio

| ID | Regla |
| --- | --- |
| RN-01 | El precio del ticket es mayor a cero si el sorteo es pagado. |
| RN-02 | La cantidad por orden es al menos 1 y no supera el límite del sorteo. |
| RN-03 | El total es `precio × cantidad` en centavos. |
| RN-04 | El límite por persona se aplica por DNI; correo/celular son señales secundarias. |
| RN-05 | Un número pertenece a un solo sorteo y a una sola orden/ticket. |
| RN-06 | La reserva dura 30 minutos por defecto; BCP puede extenderse manualmente. |
| RN-07 | Un ticket solo es válido si el pago está aprobado. |
| RN-08 | Un comprobante no puede aprobar dos órdenes. |
| RN-09 | El snapshot solo incluye tickets pagados/válidos al cierre. |
| RN-10 | El ganador se calcula con semilla y snapshot; no se edita. |
| RN-11 | El resultado se publica con hashes y lista anonimizada. |
| RN-12 | No se publican datos personales. |
| RN-13 | No se entrega un premio sin verificar identidad y bases. |
| RN-14 | Toda acción crítica se audita. |
| RN-15 | El sorteo no se publica sin checklist legal. |
| RN-16 | El catálogo de premios es independiente; el sorteo selecciona y posiciona premios. |
| RN-17 | El precio del boleto y el máximo por persona se configuran en el sorteo, no en el premio. |
| RN-18 | Cada método de pago es billetera digital o cuenta bancaria; el QR es opcional. |
| RN-19 | Si hay sobrepago, el cliente contacta al número visible; el admin registra y decide con auditoría. |

---

## 6. Estados y transiciones

### 6.1 Sorteo

```text
draft → published → sales_closed → drawing → drawn → results_published → closed
  ↘ cancelled
  ↘ paused → published
```

| Estado | Significado | Acciones |
| --- | --- | --- |
| draft | En edición | Editar, previsualizar, publicar, cancelar |
| published | Visible y con ventas | Comprar, cerrar, pausar |
| paused | Visible sin ventas | Reanudar, cerrar, cancelar |
| sales_closed | Sin ventas, pendiente de sorteo | Programar/ejecutar, cancelar |
| drawing | Snapshot/semilla en proceso | Ejecutar, publicar |
| drawn | Resultado calculado | Publicar resultados |
| results_published | Resultados públicos | Entregar, cerrar |
| closed | Histórico | Consultar |
| cancelled | Anulado | Consultar, reembolsar |

### 6.2 Orden

```text
pending_payment → proof_received → under_review → approved
                                             ↘ rejected → pending_payment/expired
pending_payment/proof_received/under_review → expired
approved → refunded
```

### 6.3 Número

```text
available → reserved → paid
reserved → available (expira/cancela)
paid → cancelled/refunded (con auditoría)
```

### 6.4 Pago

```text
pending → approved
pending → rejected
approved → refunded
```

### 6.5 Sorteo ejecutado

```text
scheduled → snapshot_created → seed_committed → drawn → published
```

### 6.6 Entrega

```text
pending_contact → contacted → identity_verified → scheduled → delivered → closed
contacted/identity_verified/scheduled → not_claimed/deserted
```

---

## 7. Modelo de datos

No se implementa en esta fase. Tipos y nombres son propuestas para la migración.

### 7.1 Tablas existentes

- `users`: administradores. No se reutiliza para participantes.
- `settings`: configuración general no transaccional.
- `schema_migrations`: nuevas migraciones.

### 7.2 Tablas nuevas

#### `raffles`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | Auto |
| public_code | VARCHAR(20) | Único |
| slug | VARCHAR(120) | Único |
| name | VARCHAR(160) | |
| summary | VARCHAR(255) | |
| description | MEDIUMTEXT | |
| status | VARCHAR(30) | draft/published/paused/sales_closed/drawing/drawn/results_published/closed/cancelled |
| price_cents | BIGINT UNSIGNED | 0 si gratuito |
| currency | CHAR(3) | PEN |
| total_tickets | INT UNSIGNED | Cupo |
| max_tickets_per_participant | INT UNSIGNED | Límite |
| min_age | TINYINT UNSIGNED | 18 por defecto |
| sales_start_at | DATETIME | |
| sales_end_at | DATETIME | |
| draw_at | DATETIME | |
| claim_deadline_at | DATETIME | |
| current_rules_version_id | INT UNSIGNED | FK |
| is_featured | TINYINT(1) | Home |
| featured_order | TINYINT UNSIGNED | |
| cover_image_path | VARCHAR(255) | Privado o público controlado |
| created_by | INT UNSIGNED | FK users |
| published_at | DATETIME NULL | |
| closed_at | DATETIME NULL | |
| drawn_at | DATETIME NULL | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Índices: `status`, `sales_start_at`, `sales_end_at`, `is_featured`, `slug`, `public_code`.

#### `prizes`

Catálogo independiente de premios.

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| public_code | VARCHAR(20) | Único |
| name | VARCHAR(160) | |
| description | TEXT | |
| prize_type | VARCHAR(30) | product/service/cash/other |
| estimated_value_cents | BIGINT UNSIGNED NULL | Valor referencial |
| image_path | VARCHAR(255) NULL | Foto del premio |
| delivery_method | VARCHAR(60) | |
| is_active | TINYINT(1) | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

#### `raffle_prizes`

Asignación de premios del catálogo a un sorteo.

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| raffle_id | INT UNSIGNED | FK |
| prize_id | INT UNSIGNED | FK |
| position | TINYINT UNSIGNED | 1 = principal |
| conditions | TEXT NULL | Condición específica |
| created_at | DATETIME | |

Unique: `(raffle_id, position)`. Un premio puede asignarse a un sorteo; si el negocio requiere exclusividad, se valida en el servicio.

#### `raffle_rules_versions`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| raffle_id | INT UNSIGNED | FK |
| version | INT UNSIGNED | |
| content | MEDIUMTEXT | |
| content_hash | CHAR(64) | SHA-256 |
| status | VARCHAR(20) | draft/published/archived |
| published_at | DATETIME NULL | |
| created_by | INT UNSIGNED | |
| created_at | DATETIME | |

Unique: `(raffle_id, version)`.

#### `payment_methods`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| name | VARCHAR(120) | Nombre visible |
| type | VARCHAR(20) | wallet/bank |
| provider | VARCHAR(40) | yape/plin/bcp/other |
| holder_name | VARCHAR(160) | Titular |
| holder_document | VARCHAR(20) NULL | |
| phone | VARCHAR(20) NULL | Billetera digital |
| bank_name | VARCHAR(80) NULL | Cuenta bancaria |
| account_number | VARCHAR(40) NULL | Cuenta/tarjeta |
| cci | VARCHAR(40) NULL | Cuenta bancaria |
| qr_image_path | VARCHAR(255) NULL | QR opcional |
| contact_phone | VARCHAR(20) NULL | Contacto para sobrepagos |
| instructions | TEXT | |
| is_active | TINYINT(1) | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

#### `raffle_payment_methods`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| raffle_id | INT UNSIGNED | FK |
| payment_method_id | INT UNSIGNED | FK |
| instructions_override | TEXT NULL | |
| is_active | TINYINT(1) | |
| sort_order | TINYINT UNSIGNED | |

Unique: `(raffle_id, payment_method_id)`.

#### `participants`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| public_code | VARCHAR(20) | Único |
| full_name | VARCHAR(160) | |
| document_type | VARCHAR(10) | DNI |
| document_number_hash | CHAR(64) | Único; para duplicados |
| document_number_encrypted | VARBINARY(255) | Para visualización autorizada |
| phone | VARCHAR(20) | |
| email | VARCHAR(160) | |
| is_adult_declared | TINYINT(1) | |
| status | VARCHAR(20) | active/blocked |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Índices: `document_number_hash` unique, `email`, `phone`, `public_code` unique.

#### `orders`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| public_code | VARCHAR(20) | Único |
| access_code_hash | CHAR(64) | Código de consulta |
| raffle_id | INT UNSIGNED | FK |
| participant_id | INT UNSIGNED | FK |
| quantity | INT UNSIGNED | |
| unit_price_cents | BIGINT UNSIGNED | |
| total_amount_cents | BIGINT UNSIGNED | |
| currency | CHAR(3) | PEN |
| status | VARCHAR(30) | pending_payment/proof_received/under_review/approved/rejected/expired/cancelled/refunded |
| expires_at | DATETIME | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Índices: `raffle_id`, `participant_id`, `status`, `expires_at`, `public_code` unique.

#### `raffle_numbers`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | BIGINT UNSIGNED PK | |
| raffle_id | INT UNSIGNED | FK |
| number | INT UNSIGNED | |
| status | VARCHAR(20) | available/reserved/paid/cancelled |
| order_id | INT UNSIGNED NULL | FK |
| reserved_until | DATETIME NULL | |
| ticket_id | INT UNSIGNED NULL | FK |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Unique: `(raffle_id, number)`. Índices: `status`, `order_id`, `reserved_until`.

#### `tickets`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| public_code | VARCHAR(24) | Único |
| raffle_id | INT UNSIGNED | FK |
| number | INT UNSIGNED | |
| participant_id | INT UNSIGNED | FK |
| order_id | INT UNSIGNED | FK |
| status | VARCHAR(20) | paid/cancelled/refunded |
| issued_at | DATETIME | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Unique: `(raffle_id, number)`, `public_code`.

#### `payment_proofs`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| order_id | INT UNSIGNED | FK |
| file_path | VARCHAR(255) | Privado |
| file_hash | CHAR(64) | SHA-256 |
| mime_type | VARCHAR(60) | |
| file_size | INT UNSIGNED | |
| status | VARCHAR(20) | received/approved/rejected |
| uploaded_at | DATETIME | |
| reviewed_by | INT UNSIGNED NULL | |
| reviewed_at | DATETIME NULL | |
| review_notes | TEXT NULL | |

Índices: `order_id`, `file_hash`, `status`.

#### `payments`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| order_id | INT UNSIGNED | FK |
| method | VARCHAR(20) | yape/plin/bcp |
| payment_method_id | INT UNSIGNED | FK |
| amount_cents | BIGINT UNSIGNED | |
| currency | CHAR(3) | PEN |
| status | VARCHAR(20) | pending/approved/rejected/refunded |
| approved_by | INT UNSIGNED NULL | |
| approved_at | DATETIME NULL | |
| reference | VARCHAR(120) NULL | |
| notes | TEXT NULL | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Índices: `order_id`, `status`, `method`.

#### `draws`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| raffle_id | INT UNSIGNED | FK |
| status | VARCHAR(30) | scheduled/snapshot_created/seed_committed/drawn/published |
| scheduled_at | DATETIME | |
| executed_at | DATETIME NULL | |
| executed_by | INT UNSIGNED NULL | |
| algorithm_version | VARCHAR(20) | |
| snapshot_hash | CHAR(64) NULL | |
| seed_hash | CHAR(64) NULL | |
| seed_encrypted | VARBINARY(255) NULL | |
| seed_revealed_at | DATETIME NULL | |
| total_entries | INT UNSIGNED NULL | |
| acta_path | VARCHAR(255) NULL | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

#### `draw_results`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| draw_id | INT UNSIGNED | FK |
| position | TINYINT UNSIGNED | 1 = ganador; 2+ suplentes |
| result_type | VARCHAR(20) | winner/substitute |
| ticket_id | INT UNSIGNED | FK |
| participant_id | INT UNSIGNED | FK |
| published_at | DATETIME NULL | |

Unique: `(draw_id, position)`.

#### `deliveries`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| raffle_id | INT UNSIGNED | FK |
| draw_id | INT UNSIGNED | FK |
| draw_result_id | INT UNSIGNED | FK |
| prize_id | INT UNSIGNED | FK |
| participant_id | INT UNSIGNED | FK |
| status | VARCHAR(30) | pending_contact/contacted/identity_verified/scheduled/delivered/not_claimed/deserted/closed |
| contacted_at | DATETIME NULL | |
| identity_verified_at | DATETIME NULL | |
| scheduled_at | DATETIME NULL | |
| delivered_at | DATETIME NULL | |
| delivered_by | INT UNSIGNED NULL | |
| notes | TEXT NULL | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

#### `delivery_evidence`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| delivery_id | INT UNSIGNED | FK |
| file_path | VARCHAR(255) | Privado |
| file_hash | CHAR(64) | |
| mime_type | VARCHAR(60) | |
| evidence_type | VARCHAR(30) | photo/acta/signature/receipt |
| uploaded_by | INT UNSIGNED | |
| created_at | DATETIME | |

#### `legal_acceptances`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| participant_id | INT UNSIGNED | FK |
| order_id | INT UNSIGNED NULL | FK |
| document_type | VARCHAR(20) | rules/privacy/terms |
| document_version | VARCHAR(20) | |
| document_hash | CHAR(64) | |
| accepted_at | DATETIME | |
| ip | VARCHAR(45) | |
| user_agent | VARCHAR(255) | |

#### `audit_logs`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | BIGINT UNSIGNED PK | |
| actor_type | VARCHAR(20) | admin/system |
| actor_id | INT UNSIGNED NULL | |
| action | VARCHAR(60) | |
| entity_type | VARCHAR(60) | |
| entity_id | BIGINT UNSIGNED NULL | |
| before_json | JSON NULL | |
| after_json | JSON NULL | |
| ip | VARCHAR(45) | |
| user_agent | VARCHAR(255) | |
| created_at | DATETIME | |
| previous_hash | CHAR(64) NULL | |
| hash | CHAR(64) | |

Índices: `created_at`, `action`, `entity_type`, `entity_id`.

#### `notification_logs`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| order_id | INT UNSIGNED NULL | |
| participant_id | INT UNSIGNED NULL | |
| channel | VARCHAR(20) | whatsapp/email/screen |
| template_key | VARCHAR(60) | |
| status | VARCHAR(20) | sent/failed |
| sent_by | INT UNSIGNED NULL | |
| sent_at | DATETIME | |
| notes | TEXT NULL | |

#### `schema_migrations`

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | INT UNSIGNED PK | |
| migration | VARCHAR(255) | Único |
| applied_at | DATETIME | |

---

## 8. Rutas

### 8.1 Públicas

| Método | Ruta | Handler | Descripción |
| --- | --- | --- | --- |
| GET | `/` | Home existente + bloque | Home mixta |
| GET | `/sorteos` | `PublicRaffleHandler@index` | Listado |
| GET | `/sorteos/{slug}` | `PublicRaffleHandler@show` | Detalle |
| GET | `/sorteos/{slug}/participar` | `PublicRaffleHandler@participate` | Formulario |
| POST | `/sorteos/{slug}/participar` | `PublicRaffleHandler@reserve` | Crear orden/reserva |
| GET | `/sorteos/{slug}/pago/{orderCode}` | `PublicPaymentHandler@show` | Instrucciones |
| POST | `/sorteos/{slug}/pago/{orderCode}` | `PublicPaymentHandler@upload` | Subir comprobante |
| GET | `/sorteos/{slug}/confirmacion/{orderCode}` | `PublicPaymentHandler@confirmation` | Confirmación |
| GET | `/participaciones` | `PublicParticipantHandler@lookupForm` | Consulta |
| POST | `/participaciones` | `PublicParticipantHandler@lookup` | Buscar orden |
| GET | `/sorteos/{slug}/resultados` | `PublicDrawHandler@results` | Resultados |
| GET | `/legal/bases/{slug}` | `PublicLegalHandler@rules` | Bases |
| GET | `/legal/privacidad` | `PublicLegalHandler@privacy` | Privacidad |
| GET | `/legal/terminos` | `PublicLegalHandler@terms` | Términos |
| GET | `/libro-de-reclamaciones` | `PublicSupportHandler@claims` | Libro |
| POST | `/libro-de-reclamaciones` | `PublicSupportHandler@storeClaim` | Registrar |
| GET | `/contacto` | `PublicSupportHandler@contact` | Contacto y WhatsApp |

### 8.2 Administrativas

| Método | Ruta | Handler | Descripción |
| --- | --- | --- | --- |
| GET | `/admin/prizes` | `AdminPrizeHandler@index` | Catálogo de premios |
| GET | `/admin/prizes/create` | `AdminPrizeHandler@create` | Crear premio |
| POST | `/admin/prizes` | `AdminPrizeHandler@store` | Guardar premio |
| GET | `/admin/prizes/{id}/edit` | `AdminPrizeHandler@edit` | Editar premio |
| POST | `/admin/prizes/{id}` | `AdminPrizeHandler@update` | Actualizar premio |
| GET | `/admin/raffles` | `AdminRaffleHandler@index` | Listado |
| GET | `/admin/raffles/create` | `AdminRaffleHandler@create` | Crear |
| POST | `/admin/raffles` | `AdminRaffleHandler@store` | Guardar |
| GET | `/admin/raffles/{id}/edit` | `AdminRaffleHandler@edit` | Editar |
| POST | `/admin/raffles/{id}` | `AdminRaffleHandler@update` | Actualizar |
| POST | `/admin/raffles/{id}/publish` | `AdminRaffleHandler@publish` | Publicar |
| POST | `/admin/raffles/{id}/pause` | `AdminRaffleHandler@pause` | Pausar |
| POST | `/admin/raffles/{id}/close` | `AdminRaffleHandler@close` | Cerrar |
| POST | `/admin/raffles/{id}/cancel` | `AdminRaffleHandler@cancel` | Cancelar |
| GET | `/admin/raffles/{id}/preview` | `AdminRaffleHandler@preview` | Vista previa |
| GET | `/admin/raffles/{id}/draw` | `AdminDrawHandler@show` | Sorteo |
| POST | `/admin/raffles/{id}/snapshot` | `AdminDrawHandler@snapshot` | Snapshot |
| POST | `/admin/raffles/{id}/draw` | `AdminDrawHandler@execute` | Ejecutar |
| POST | `/admin/raffles/{id}/results/publish` | `AdminDrawHandler@publish` | Publicar |
| GET | `/admin/raffles/{id}/deliveries` | `AdminDeliveryHandler@index` | Entregas |
| GET | `/admin/payments` | `AdminPaymentHandler@index` | Cola |
| GET | `/admin/payments/{id}` | `AdminPaymentHandler@show` | Detalle |
| POST | `/admin/payments/{id}/approve` | `AdminPaymentHandler@approve` | Aprobar |
| POST | `/admin/payments/{id}/reject` | `AdminPaymentHandler@reject` | Rechazar |
| GET | `/admin/participants` | `AdminParticipantHandler@index` | Participantes |
| GET | `/admin/orders` | `AdminOrderHandler@index` | Órdenes |
| GET | `/admin/tickets` | `AdminTicketHandler@index` | Tickets |
| GET | `/admin/deliveries` | `AdminDeliveryHandler@index` | Entregas |
| GET | `/admin/audit` | `AdminAuditHandler@index` | Auditoría |
| GET | `/admin/payment-methods` | `AdminPaymentMethodHandler@index` | Métodos de pago |
| POST | `/admin/payment-methods` | `AdminPaymentMethodHandler@store` | Crear |
| POST | `/admin/payment-methods/{id}` | `AdminPaymentMethodHandler@update` | Editar |
| GET | `/admin/reports` | `AdminReportHandler@index` | Reportes |

---

## 9. Pantallas

### 9.1 Panel administrativo

| Pantalla | Elementos | Acciones | Validaciones |
| --- | --- | --- | --- |
| Dashboard | Sorteos activos, pagos pendientes, próximos sorteos, entregas pendientes | Ir a módulos | — |
| Sorteos lista | Tabla, búsqueda, filtros, estado, fechas, cupos, pagos | Crear, editar, publicar, cerrar, cancelar, ver | — |
| Premios | Catálogo, foto, descripción, tipo producto, valor | Crear, editar, activar | Imagen y datos completos |
| Sorteo crear/editar | Tabs: básico, premios, fechas, métodos de pago, bases, legal | Guardar, previsualizar, publicar | Campos requeridos, fechas, precio, cupos, límite |
| Publicar | Checklist legal, resumen, confirmación | Publicar | Checklist completo |
| Pagos cola | Filtros, método, estado, monto, comprobante | Revisar, aprobar, rechazar | Pago pendiente, comprobante, monto |
| Pago detalle | Datos de orden/participante, comprobante, historial | Aprobar, rechazar, nota | No duplicado, monto, reserva |
| Participantes | Tabla, búsqueda, DNI enmascarado | Ver detalle, exportar | Permisos |
| Órdenes | Tabla, estado, sorteo, monto | Ver, cancelar | Permisos |
| Tickets | Tabla, número, estado, participante | Ver, anular | Permisos |
| Sorteo | Snapshot, hashes, semilla, ejecutar | Snapshot, ejecutar, publicar | Estado correcto, doble confirmación |
| Resultados | Ganador, suplentes, hashes, acta | Publicar, descargar | Snapshot inmutable |
| Entregas | Ganadores, estados, evidencia | Contactar, verificar, programar, entregar | Evidencia, identidad |
| Auditoría | Filtros, actor, acción, entidad, fecha | Ver detalle | Solo lectura |
| Métodos de pago | Tipo billetera/banco, proveedor, titular, teléfono/cuenta/tarjeta, CCI, QR opcional, contacto | Crear, editar, activar | Datos completos |
| Reportes | Métricas, filtros, exportar CSV | Exportar | Permisos |

### 9.2 Web pública

| Pantalla | Elementos | Acciones | Validaciones |
| --- | --- | --- | --- |
| Home mixta | Radio + 3 sorteos destacados + enlace | Ver sorteo | — |
| Listado | Tarjetas, estado, precio, fechas, CTA | Filtrar, ver | — |
| Detalle | Premio, fechas, precio, cupos, bases, métodos | Participar | Sorteo publicado, cupos |
| Participar | Cantidad, ficha, aceptación | Reservar | Datos, edad, límite |
| Pago | Monto, QR/teléfono/cuenta, código, contacto para sobrepagos, subir comprobante | Subir | Imagen válida, orden vigente |
| Confirmación | Números, estado, código, WhatsApp, contacto | Guardar código | — |
| Consulta | Código de orden + acceso | Buscar | Código válido |
| Resultados | Ganador, suplentes, semilla, hashes, lista | Verificar, acta | — |
| Bases | Versión, hash, contenido | Imprimir | — |
| Privacidad/términos | Contenido legal | — | — |
| Libro de Reclamaciones | Formulario, constancia | Enviar | Datos, email/celular |
| Contacto | Teléfono/WhatsApp del dueño | Llamar/escribir | — |

---

## 10. Flujos detallados

### 10.1 Crear y publicar sorteo

1. Admin crea o actualiza el premio en el catálogo: foto, descripción, tipo producto y valor.
2. Admin crea el sorteo y selecciona el/los premios.
3. Completa fechas, precio del boleto, cupos, máximo por persona y edad.
4. Crea/elige bases.
5. Selecciona métodos de pago desde el módulo de métodos.
6. Completa checklist legal.
7. Previsualiza.
8. Publica con doble confirmación.
9. El sorteo aparece en `/sorteos` y home si es destacado.

### 10.2 Participar y pagar

1. Cliente elige cantidad.
2. Completa ficha y acepta bases/privacidad.
3. Sistema valida límite y cupos.
4. Reserva números por 30 min.
5. Crea orden y muestra instrucciones: monto, QR/teléfono/cuenta, código y contacto para sobrepagos.
6. Cliente paga por Yape/Plin/BCP.
7. Sube comprobante.
8. Orden pasa a `proof_received`.
9. Admin revisa.
10. Aprueba → tickets emitidos.
11. Rechaza → motivo y opción de reintento.
12. Expira → números liberados.

### 10.3 Aprobar pago

1. Admin abre cola.
2. Verifica comprobante, monto, fecha y titular.
3. Confirma que no está duplicado.
4. Si el monto es mayor, contacta al cliente con el número visible y registra el sobrepago.
5. Si el monto es menor o distinto, rechaza con motivo.
6. Aprueba.
7. Sistema registra pago, emite tickets, actualiza orden y auditoría.
8. Cliente ve confirmación.

### 10.4 Cerrar y sortear

1. Admin cierra ventas.
2. Sistema congela tickets pagados.
3. Genera snapshot y hash.
4. Genera semilla y hash.
5. Publica hashes.
6. Admin ejecuta sorteo.
7. Sistema calcula ganador/suplentes.
8. Revela semilla.
9. Publica resultados y acta.
10. Cliente consulta y verifica.

### 10.5 Entregar premio

1. Admin contacta ganador.
2. Verifica identidad.
3. Programa entrega.
4. Entrega y sube evidencia.
5. Marca entregado.
6. Cierra caso o deriva a suplente.

### 10.6 Sobrepago

1. El cliente paga un monto mayor al de la orden.
2. La página de pago/confirmación muestra el número de contacto del dueño.
3. El cliente contacta y reporta el sobrepago.
4. El admin verifica el pago y registra el monto.
5. El admin decide devolución, compensación o ajuste.
6. Todo queda auditado; no se emiten tickets extra sin autorización.

---

## 11. Motor commit-reveal

### 11.1 Datos

- `raffle_id`.
- Snapshot de tickets pagados, ordenados por número.
- `snapshot_hash = SHA-256(concat(ticket_public_code + ":" + number + "\n"))`.
- `seed = random_bytes(32)`.
- `seed_hash = SHA-256(seed)`.
- `algorithm_version = "commit-reveal-v1"`.

### 11.2 Cálculo

- `payload = raffle_id + "|" + snapshot_hash + "|winner"`.
- `digest = HMAC-SHA256(seed, payload)`.
- `index = int(digest, 16) mod total_entries`.
- Ganador = entrada en `index`.
- Suplentes: mismo proceso con `"substitute-1"`, `"substitute-2"`, etc., excluyendo ya seleccionados.

### 11.3 Publicación

- Antes del sorteo: `snapshot_hash`, `seed_hash`, total, algoritmo.
- Después: `seed`, ganador, suplentes, acta.
- Verificación: recalcular con la semilla y el snapshot.

### 11.4 Reglas

- La semilla no se registra en logs antes de revelarse.
- El snapshot no se modifica.
- El resultado no se edita.
- Un redraw genera nueva semilla/hash y se documenta.

---

## 12. Privacidad y transparencia

### 12.1 Datos públicos

- Nombre del sorteo, premio, fechas, precio, cupos.
- Bases y su hash.
- Ganador/suplentes (nombre si las bases lo permiten).
- Número de ticket.
- Semilla, hashes, algoritmo.
- Lista anonimizada (código de ticket).
- Acta.

### 12.2 Datos privados

- DNI, correo, celular, dirección.
- Comprobantes.
- Evidencia de entrega.
- Historial de pagos.
- Datos de participantes no ganadores.

### 12.3 Reglas

- No se publican datos personales sin base legal/consentimiento.
- Se aplica minimización y enmascaramiento.
- Se registra aceptación de privacidad.
- Se define retención y eliminación/anonymización.

---

## 13. Requisitos no funcionales

### 13.1 Compatibilidad

- PHP 8.0 compatible.
- MySQL/MariaDB.
- Apache/cPanel.
- Navegadores modernos.
- Sin Composer en producción.

### 13.2 Seguridad

- CSRF en POST.
- Prepared statements.
- Escape de salida.
- Rate limiting.
- Validación de archivos.
- Almacenamiento privado.
- Auditoría.
- HTTPS.
- Sesión admin segura.

### 13.3 Rendimiento

- Índices en listados y reservas.
- Paginación en admin y listado público (20 por página).
- Imágenes optimizadas.
- Evitar N+1.
- Pruebas de concurrencia.

### 13.4 Accesibilidad

- Contraste, foco, labels, teclado.
- Mensajes de error claros.
- Lighthouse razonable.

### 13.5 SEO

- Títulos, meta descripción, canonical, Open Graph.
- URLs estables.
- Sitemap actualizado.
- No indexar borradores, pagos ni cuenta.

### 13.6 Privacidad

- Minimización.
- Enmascaramiento.
- Retención.
- Derechos ARCO.
- Registro de aceptación.

### 13.7 Operación

- Backups.
- Logs.
- Worker/cron.
- Restauración probada.

---

## 14. Criterios de aceptación

### 14.1 Sorteo

- [ ] Se crea un premio en el catálogo con foto, descripción, tipo producto y valor.
- [ ] Se crea un sorteo con premio, precio, cupos, límite, fechas y métodos.
- [ ] Se crea un método de pago de tipo billetera/banco con teléfono/cuenta/tarjeta y QR opcional.
- [ ] No se publica sin checklist legal.
- [ ] Se publica y aparece en `/sorteos`.
- [ ] Se cierra y no acepta nuevas compras.
- [ ] Se cancela con motivo y auditoría.

### 14.2 Participación

- [ ] Un invitado compra con ficha completa.
- [ ] Se respeta el límite por persona.
- [ ] Se asignan números únicos.
- [ ] Dos solicitudes simultáneas no obtienen el mismo número.
- [ ] La reserva expira a los 30 minutos.
- [ ] El cliente consulta con código.

### 14.3 Pagos

- [ ] Se muestran Yape/Plin/BCP configurados.
- [ ] Se sube una imagen válida.
- [ ] Se rechaza un archivo inválido.
- [ ] El admin aprueba y se emiten tickets.
- [ ] El admin rechaza y no se emiten tickets.
- [ ] Un comprobante no se usa dos veces.
- [ ] Un pago aprobado no se edita.
- [ ] Se muestra el contacto del dueño para sobrepagos.
- [ ] Se audita la aprobación/rechazo.

### 14.4 Sorteo

- [ ] El snapshot solo incluye pagados.
- [ ] El hash de semilla se publica antes.
- [ ] El resultado se reproduce con la semilla.
- [ ] El ganador no se edita.
- [ ] La página pública muestra hashes y lista anonimizada.
- [ ] No hay datos personales en la página pública.

### 14.5 Entrega

- [ ] Se registra contacto.
- [ ] Se verifica identidad.
- [ ] Se registra evidencia.
- [ ] Se marca entregado.
- [ ] Se audita.

### 14.6 Legal

- [ ] Bases y privacidad accesibles con plantillas para revisión legal.
- [ ] Libro de Reclamaciones funciona.
- [ ] Se registra aceptación.
- [ ] El checklist legal bloquea publicación.
- [ ] El número de contacto del dueño está visible para sobrepagos.

---

## 15. Trazabilidad

| Requisito | Decisión/Plan |
| --- | --- |
| Varios sorteos | 004 §3.2 |
| Pagos manuales | 004 §3.7 |
| Números automáticos | 004 §3.3 |
| Límite por persona | 004 §3.5 |
| Invitado | 004 §3.9 |
| Commit-reveal | 004 §3.10 |
| Home mixta | 004 §3.11 |
| Transparencia | 004 §3.14 |
| Entrega manual | 004 §3.16 |
| Catálogo de premios | 004 §3.19 |
| Métodos de pago | 004 §3.20 |
| Sobrepagos | 004 §3.21 |
| Correo y FAQ diferidos | 004 §3.22 |
| Plantillas legales | 007-plantillas |
| Arquitectura | 006 |
| Convenciones | 006-convenciones |
| Fases | 005 |

---

## 16. Preguntas abiertas

1. ¿El tiempo de reserva BCP será 30 min o configurable?
2. ¿Se aceptará PDF de BCP?
3. ¿Se publicará el nombre del ganador? ¿Con qué consentimiento?
4. ¿Qué producto específico y valor tendrá el primer premio?
5. ¿Se emitirán comprobantes por el cobro?
6. ¿Quién atiende WhatsApp y en qué horario?
7. ¿Se requerirá 2FA antes de montos altos?
8. ¿Política de reembolso y no reclamado?
9. ¿Se inscribirá el banco de datos y se designará ODP?
10. ¿Se usarán las plantillas legales tal como están o se ajustarán con abogado?

---

## 17. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Especificación funcional y técnica del MVP. |
