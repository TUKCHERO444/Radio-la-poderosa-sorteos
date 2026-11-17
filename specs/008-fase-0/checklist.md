# 008 — Fase 0: decisiones, legal e infraestructura

- **Estado:** en ejecución.
- **Fecha de apertura:** 2026-11-16.
- **Base:** `specs/004-mvp-sorteos/decisiones.md`, `specs/005-mvp-sorteos/plan-fases.md`, `specs/006-mvp-sorteos/arquitectura.md`, `specs/006-mvp-sorteos/convenciones.md`, `specs/007-mvp-sorteos/especificacion.md`.
- **Objetivo:** cerrar las condiciones legales, operativas y de infraestructura mínimas para iniciar la implementación sin decisiones apresuradas.
- **Regla:** toda decisión o cambio se documenta aquí y en `specs/CHANGELOG.md`.

---

## 1. Aprobaciones registradas

| Documento | Estado | Fecha |
| --- | --- | --- |
| 004 — Decisiones del MVP | Aprobadas | 2026-11-16 |
| 005 — Plan por fases | Aprobado para ejecución | 2026-11-16 |
| 006 — Arquitectura modular | Aprobada | 2026-11-16 |
| 006 — Convenciones | Aprobadas | 2026-11-16 |
| 007 — Especificación funcional y técnica | Aprobada | 2026-11-16 |

**Consecuencia:** la Fase 0 se abre formalmente. La implementación de código puede iniciarse en las partes que no dependan de decisiones legales o de cuentas de pago, pero **no se publica ningún sorteo** hasta superar el Gate 0.

---

## 2. Decisiones ya cerradas

| Tema | Decisión | Fuente |
| --- | --- | --- |
| Stack | PHP 8.0 compatible, MySQL/MariaDB, HTML/CSS/JS nativos | 004, 006 |
| Arquitectura | Monolito modular por dominio, sin MVC rígido | 006 |
| Código | Inglés; UI español; clases con autoload manual | convenciones |
| Base de datos | MySQL/MariaDB con PDO; sin ORM | 004, 006 |
| Pagos | Yape, Plin y BCP manuales; métodos configurables | 004 |
| Métodos de pago | Módulo independiente: billetera/banco, teléfono/cuenta/tarjeta, QR opcional | 004 |
| Premios | Catálogo dedicado con fotos, descripción, tipo producto y valor | 004 |
| Sobrepago | Número de contacto del dueño visible en pago/confirmación | 004 |
| Comprobante | Cliente sube captura; admin aprueba | 004 |
| Plantillas legales | Bases, privacidad, términos y Libro de Reclamaciones para revisión | 007 |
| Sorteos | Varios simultáneos | 004 |
| Tickets | Número automático; reserva 30 min | 004 |
| Límite | Configurable por sorteo | 004 |
| Participante | Invitado con nombre, DNI, celular y correo | 004 |
| Motor | Commit-reveal | 004 |
| Web | Home mixta radio + sorteos | 004 |
| Transparencia | Ganadores + hashes + lista anonimizada | 004 |
| Notificaciones | Pantalla + WhatsApp manual; correo y FAQ diferidos | 004 |
| Entrega | Seguimiento manual | 004 |
| Roles | Un admin con auditoría | 004 |
| Legal | Declarado promocional/no requiere, sujeto a validación por el cobro | 004 |

---

## 3. Checklist de Fase 0

| ID | Tarea | Responsable | Estado | Evidencia / nota |
| --- | --- | --- | --- | --- |
| F0-01 | Definir producto/premio, fotos, descripción y valor estimado | Organizador | En revisión | Generalmente productos; valor pendiente. |
| F0-02 | Definir precio por ticket, cupos y límite por persona | Organizador | Pendiente | Se configura por sorteo en el panel. |
| F0-03 | Validar calificación legal del cobro con abogado | Organizador / legal | Pendiente | “Promocional” + “venta de participaciones” debe confirmarse. |
| F0-04 | Confirmar si requiere autorización/resolución y número | Organizador / legal | Pendiente | Campo opcional en el sorteo. |
| F0-05 | Redactar/aprobar bases y condiciones | Organizador / legal | Pendiente | Deben versionarse. |
| F0-06 | Redactar/aprobar política de privacidad y términos | Organizador / legal | En revisión | Plantillas base creadas; revisión legal pendiente. |
| F0-07 | Evaluar inscripción del banco de datos y ODP | Organizador / legal | Pendiente | Puede ser obligatorio. |
| F0-08 | Definir política de reembolso, pago tardío y duplicado | Organizador | Pendiente | Debe estar en las bases. |
| F0-09 | Definir plazo de reclamo y política de no reclamado | Organizador | Pendiente | Debe estar en las bases. |
| F0-10 | Definir método Yape: titular, celular, QR y contacto | Organizador | Pendiente | Se carga en el módulo de métodos de pago. |
| F0-11 | Definir método Plin: titular, celular, QR y contacto | Organizador | Pendiente | Se carga en el módulo de métodos de pago. |
| F0-12 | Definir método BCP: titular, cuenta/tarjeta, CCI y contacto | Organizador | Pendiente | Se carga en el módulo de métodos de pago. |
| F0-13 | Definir responsable y horario de revisión de pagos | Organizador | Pendiente | Operación diaria. |
| F0-14 | Confirmar SMTP/correo | Infraestructura | No aplica | Diferido; el MVP no usa correo. |
| F0-15 | Confirmar `fileinfo` o validación alternativa de imágenes | Infraestructura | Pendiente | Si no, JPG/PNG/WebP con `getimagesize`. |
| F0-16 | Confirmar cron/worker para reservas y tareas | Infraestructura | Pendiente | Si no, expiración perezosa y limpieza manual. |
| F0-17 | Confirmar backups y prueba de restauración | Infraestructura | Pendiente | Antes de datos reales. |
| F0-18 | Confirmar PHP 8.0 o plan de upgrade | Infraestructura | Pendiente | PHP 8.0 está EOL; upgrade recomendado. |
| F0-19 | Redactar textos de home y plantillas WhatsApp | Organizador / contenido | Pendiente | FAQ diferida. |
| F0-20 | Aprobar checklist legal de publicación | Organizador / legal | Pendiente | Bloquea “Publicar sorteo”. |
| F0-21 | Confirmar política de publicidad de ganadores | Organizador / legal | Pendiente | Nombre, imagen, consentimiento. |
| F0-22 | Confirmar si habrá comprobantes/tributación | Organizador / contador | Pendiente | Si hay venta, evaluar SUNAT. |
| F0-23 | Definir catálogo de premios: fotos, descripción, tipo producto y valor | Organizador | En revisión | Se gestiona en el módulo de Premios. |
| F0-24 | Definir módulo de métodos de pago: billetera/banco, teléfono/cuenta/tarjeta, QR opcional | Organizador | Pendiente | Módulo independiente del sorteo. |
| F0-25 | Definir número de contacto del dueño para sobrepagos | Organizador | Pendiente | Visible en pago/confirmación. |
| F0-26 | Revisar plantillas legales base con abogado | Organizador / legal | En revisión | `specs/007-mvp-sorteos/plantillas-legales.md`. |

**Estados:** Pendiente · En revisión · Aprobado · No aplica.

---

## 4. Gate 0 — Condiciones para iniciar implementación pública

Se puede avanzar en código de infraestructura sin cerrar todo, pero el **lanzamiento** requiere:

- [ ] Calificación legal del cobro validada o riesgo asumido por escrito.
- [ ] Bases, privacidad y términos aprobados con plantillas revisadas.
- [ ] Métodos Yape/Plin/BCP y responsables definidos.
- [ ] Número de contacto para sobrepagos definido.
- [ ] Política de reembolso y no reclamado aprobada.
- [ ] Checklist legal de publicación aprobado.
- [ ] Infraestructura mínima: backups, HTTPS, validación de archivos y cron o respaldo documentado.
- [ ] Al menos un sorteo piloto interno aprobado.
- [ ] Go/No-Go firmado.

**Si el Gate 0 no está completo:** se puede desarrollar y probar en local, pero no se publica ni se recibe dinero del público.

---

## 5. Datos que necesito del organizador

Para cerrar la Fase 0 necesito, como mínimo:

1. **Premio:** producto, fotos, descripción y valor aproximado.
2. **Precio:** cuánto costará cada ticket y cuántos cupos tendrá el primer sorteo.
3. **Legal:** si ya validó la calificación con abogado/contador; si tiene autorización o si asumirá el riesgo por escrito.
4. **Bases/privacidad:** si aprueba las plantillas base o envía textos propios.
5. **Yape:** titular, celular, QR y nombre que debe mostrarse.
6. **Plin:** titular, celular, QR y nombre que debe mostrarse.
7. **BCP:** titular, cuenta/tarjeta, CCI y nombre que debe mostrarse.
8. **Sobrepagos:** número de contacto del dueño que se mostrará en la página.
9. **Reembolsos:** qué pasa si el cliente paga de más, de menos, tarde o se arrepiente.
10. **No reclamado:** cuánto tiempo espera y qué pasa con el premio.
11. **Hosting:** si puede habilitar `fileinfo`, cron y backups; si puede actualizar PHP.
12. **Contenido:** textos de home y plantillas de WhatsApp; FAQ queda diferida.

---

## 6. Trabajo que puede iniciar en paralelo

Sin cerrar las decisiones legales/comerciales se puede preparar:

- [x] Sistema de migraciones y esquema inicial (ver `specs/009-fase-1/avance.md`).
- [x] Seeder de datos ficticios inicial.
- Estructura modular y autoload.
- Clases compartidas: Request/Response, Validator, AuditService, PrivateStorage, Money, Dates, Ids.
- Tablas base del modelo de datos.
- Catálogo de premios con fotos y validación.
- Módulo de métodos de pago (billetera/banco, QR opcional).
- Runner de pruebas.
- Pantallas de administración en modo borrador.
- Página pública de sorteo en modo borrador.
- Pruebas de asignación de números y concurrencia.

**No se debe iniciar todavía:**

- Publicación real de sorteos.
- Cobro real por Yape/Plin/BCP.
- Ejecución de un sorteo con participantes reales.
- Entrega de premios.
- Envío de comunicaciones a clientes reales.

---

## 7. Registro de decisiones y cambios

Toda decisión nueva se registra aquí:

| Fecha | Decisión | Origen | Impacto |
| --- | --- | --- | --- |
| 2026-11-16 | Aprobar 004, 005, 006 y 007 | Organizador | Abre Fase 0. |
| 2026-11-16 | Mantener MySQL/MariaDB y monolito modular | Organizador | Arquitectura 006. |
| 2026-11-16 | Pagos manuales Yape/Plin/BCP | Organizador | Flujo de pagos del MVP. |
| 2026-11-16 | PHP 8.0, clases y autoload manual | Organizador | Convenciones 006. |
| 2026-11-16 | Fechas en America/Lima | Organizador | Convenciones 006. |
| 2026-11-16 | Catálogo de premios con fotos, descripción y valor; generalmente productos | Organizador | Módulo de Premios. |
| 2026-11-16 | Métodos de pago en módulo independiente: billetera/banco, teléfono/cuenta/tarjeta, QR opcional | Organizador | Módulo de Métodos de pago. |
| 2026-11-16 | Número de contacto del dueño visible para sobrepagos | Organizador | Página de pago/confirmación. |
| 2026-11-16 | Correo y FAQ diferidos | Organizador | MVP con pantalla + WhatsApp manual. |
| 2026-11-16 | Plantillas legales base para revisión en Perú | Organizador | `specs/007-mvp-sorteos/plantillas-legales.md`. |

Toda modificación de código o documentación se registra en `specs/CHANGELOG.md`.

---

## 8. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Apertura de Fase 0, aprobaciones y checklist. |
