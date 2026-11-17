# 005 — Plan por fases del MVP de sorteos

- **Estado:** aprobado para ejecución. **Fase 0 en curso. Sin implementación de código todavía.**
- **Fecha:** 2026-11-16
- **Base:** `specs/003-sorteos/analisis-modulos.md` y `specs/004-mvp-sorteos/decisiones.md`
- **Objetivo:** convertir las decisiones del MVP en un plan ejecutable por fases, con tareas, entregables, dependencias, criterios de aceptación y puertas de control.
- **Restricción:** no se modifica código, esquema ni infraestructura en esta fase.

> **Nota:** el plan respeta el stack actual (PHP 8 + MySQL + HTML/CSS/JS nativos) y el pago manual por Yape, Plin y transferencia BCP. La validación legal del cobro es una **puerta de lanzamiento**, no una tarea de programación.
>
> **Arquitectura aprobada:** monolito modular por dominio, **sin MVC rígido**, con MySQL/MariaDB y PDO. El detalle está en `specs/006-mvp-sorteos/arquitectura.md`.

---

## 1. Principios de ejecución

1. **Primero el alcance, después el código.** Cada fase debe tener criterios de aceptación aprobados.
2. **Nada de pagos automáticos en el MVP.** El administrador valida comprobantes manualmente.
3. **Nada de datos personales sin control.** DNI, celular y correo se protegen y no se publican.
4. **Nada de sorteos sin evidencia.** Snapshot, semilla, hash y acta desde el primer sorteo.
5. **Nada de números duplicados.** Transacciones y restricciones únicas.
6. **Nada de cambios silenciosos.** Publicar, aprobar pagos, emitir tickets, sortear y entregar quedan auditados.
7. **Nada de frameworks nuevos.** Se reutiliza el front controller, PDO, CSRF y el UI kit actual.
8. **Cada fase termina con una puerta de control.** No se avanza si la anterior no está aceptada.
9. **La validación legal puede ir en paralelo al desarrollo, pero bloquea el lanzamiento.**
10. **Si una funcionalidad exige un servicio externo no aprobado, se posterga.**
11. **Arquitectura:** monolito modular por dominio; handlers finos, servicios, repositorios SQL y vistas sin SQL.
12. **Base de datos:** MySQL/MariaDB con PDO; sin ORM y sin cambio de motor en el MVP.

---

## 2. Resumen de fases

| Fase | Objetivo | Complejidad | Depende de | Puerta de salida |
| --- | --- | --- | --- | --- |
| 0 | Decisiones, legal, pagos y infraestructura mínima | Media | — | Checklist legal e infraestructura |
| 1 | Fundamentos técnicos: migraciones, datos, auditoría, archivos, cron | Media | Fase 0 | Modelo y base listos |
| 2 | Backoffice de sorteos: CRUD, premios, bases, métodos de pago | Alta | Fase 1 | Sorteo publicable |
| 3 | Web pública: home mixta, listado, detalle, legal | Media | Fase 2 | Páginas públicas aprobadas |
| 4 | Participación y tickets: invitado, ficha, números, reserva | Alta | Fase 3 | Compra/reserva sin duplicados |
| 5 | Pagos manuales: Yape/Plin/BCP, comprobante, aprobación | Alta | Fase 4 | Ticket emitido solo con pago aprobado |
| 6 | Sorteo y transparencia: commit-reveal, resultados, acta | Media/Alta | Fase 5 | Resultado verificable |
| 7 | Entrega, auditoría, reportes y notificaciones manuales | Media | Fase 6 | Cierre operativo |
| 8 | Hardening, UAT, lanzamiento y estabilización | Media | Fase 7 | Go/No-Go firmado |

**Lectura:** las fases son secuenciales en su puerta de salida, pero algunas tareas pueden solaparse (legal, contenidos, diseño, infraestructura). No se recomienda empezar la Fase 4 sin la Fase 2 aprobada.

---

## 3. Roles y responsabilidades

| Rol | Responsabilidad |
| --- | --- |
| Product owner / organizador | Decisiones de negocio, premios, precios, bases, aprobación final. |
| Responsable legal / abogado | Calificación del sorteo, autorización, bases, privacidad, reclamos. |
| Contador / tributario | Comprobantes, tratamiento del cobro, reportes. |
| Tech lead / backend | Arquitectura, base de datos, transacciones, pagos manuales, motor, seguridad. |
| Frontend / diseño | Web pública, panel, accesibilidad, responsive, contenido visual. |
| Operaciones / soporte | Revisión de pagos, WhatsApp, entrega de premios, reclamos. |
| QA / testing | Plan de pruebas, concurrencia, seguridad, UAT. |
| Infraestructura / hosting | PHP, MySQL, cron, SMTP, backups, SSL, monitoreo. |

**Regla:** ninguna persona debe aprobar su propio pago, sorteo o entrega si se puede evitar. Con un solo admin en el MVP, la auditoría y la evidencia son obligatorias.

---

## 4. Fase 0 — Decisiones, legal y operación mínima

### Objetivo

Cerrar lo que no se puede resolver programando: calificación legal, premios, cuentas de pago, textos, responsables y infraestructura mínima.

### Tareas

- [ ] **T00.1 — Definir el premio y el precio.**
  - Tipo de premio: efectivo, bien, servicio, viaje, mixto.
  - Valor aproximado.
  - Precio por ticket, moneda, cupos, límite por persona.
  - Fecha de inicio, cierre, sorteo y entrega.
- [ ] **T00.2 — Validar la calificación legal del cobro.**
  - ¿Es promocional o venta de participaciones?
  - ¿Requiere autorización/resolución? ¿De qué autoridad?
  - Bases, edad mínima, reclamos, publicidad, consentimiento de imagen.
  - Política de privacidad y evaluación del banco de datos.
  - Evaluación AML/UIF si el valor del premio lo justifica.
- [ ] **T00.3 — Definir la política operativa de pagos.**
  - Cuentas Yape, Plin y BCP.
  - Titular, teléfono, QR, cuenta, CCI.
  - Quién revisa los pagos y en qué horario.
  - Qué hacer con pagos duplicados, incompletos o tardíos.
  - Política de reembolso.
- [ ] **T00.4 — Definir la política de reclamo y entrega.**
  - Plazo de reclamo.
  - Documentos de identidad.
  - Entrega presencial, transferencia o delivery.
  - Premio no reclamado y suplentes.
  - Evidencia y conservación.
- [ ] **T00.5 — Validar infraestructura mínima.**
  - SMTP/correo: diferido; el MVP no lo usa.
  - `fileinfo` o validación alternativa de imágenes.
  - Cron/worker para reservas y recordatorios.
  - Backups y restauración.
  - PHP 8.0 EOL: plan de upgrade.
- [ ] **T00.6 — Definir contenidos.**
  - Home mixta: bloque de sorteos destacados.
  - Bases, privacidad, términos y Libro de Reclamaciones con plantillas para revisión legal.
  - FAQ: diferida a una fase posterior.
  - Tono y estilo.
- [ ] **T00.7 — Aprobar el plan y los criterios de aceptación.**

### Entregables

- Checklist legal firmado o confirmación documentada de que se asume el riesgo.
- Ficha de premio/precio/fechas.
- Manual operativo de pagos manuales.
- Checklist de infraestructura.
- Bosquejo de contenidos y rutas.
- Plan 005 aprobado.

### Criterios de aceptación

- Existe una decisión escrita sobre la calificación legal del cobro.
- Existen cuentas de pago y responsables definidos.
- Existe una política de reembolso y no reclamado.
- Existe un plan para correo, archivos, cron y backups.
- El alcance del MVP está aprobado.

### Puerta de control

**Gate 0:** sin validación legal del cobro, el sistema puede desarrollarse, pero **no publicarse**. Si se decide avanzar, se hace como piloto interno sin venta al público.

---

## 5. Fase 1 — Fundamentos técnicos

### Objetivo

Preparar la base segura y mantenible sobre la que se construirán los módulos: migraciones, datos, auditoría, archivos, cron y configuración.

**Avance 2026-11-16:** T01.1 y T01.2 completados. Ver `specs/009-fase-1/avance.md`.

### Tareas

- [ ] **T01.0 — Estructura modular y convenciones.**
  - Crear `app/modules`, `app/shared`, `app/web`, `app/database/migrations`.
  - Definir convenciones de handlers, servicios, repositorios y vistas.
  - Definir transacciones, errores de dominio y auditoría.
  - Revisar que el panel actual siga funcionando.
- [x] **T01.1 — Sistema de migraciones.**
  - Tabla `schema_migrations`.
  - Script `tools/migrate.php`.
  - Migraciones versionadas e idempotentes.
  - Backup previo y rollback documentado.
- [x] **T01.2 — Modelo de datos del MVP.**
  - Sorteos, catálogo de premios, bases versionadas.
  - Métodos de pago y asignación por sorteo.
  - Participantes, órdenes, reservas, comprobantes, pagos, tickets.
  - Sorteos ejecutados, snapshots, resultados.
  - Entregas, auditoría, aceptaciones legales.
  - Reglas: `UNIQUE (raffle_id, number)`, códigos públicos, montos en centavos, fechas en `America/Lima`.
- [ ] **T01.3 — Auditoría.**
  - Tabla append-only.
  - Campos: actor, acción, entidad, antes/después, IP, user agent, fecha.
  - Registro de acciones críticas.
- [ ] **T01.4 — Almacenamiento privado de archivos.**
  - Comprobantes y QR fuera de `public_html`.
  - Validación JPG/PNG/WebP con `getimagesize`.
  - Tamaño máximo, nombre aleatorio, descarga autenticada.
  - PDF de BCP: decidir si se acepta y cómo validarlo.
- [ ] **T01.5 — Módulo de métodos de pago.**
  - Tipo billetera digital o cuenta bancaria.
  - Proveedor, titular, teléfono, cuenta/tarjeta, CCI, QR opcional, contacto.
  - Activación por sorteo.
- [ ] **T01.6 — Worker/cron.**
  - Expiración de reservas.
  - Limpieza de códigos.
  - Notificaciones manuales; correo diferido.
  - Lock para evitar ejecuciones simultáneas.
- [ ] **T01.7 — Integración con el UI kit.**
  - Entradas de menú para Premios, Sorteos, Pagos, Participantes, Entregas, Auditoría y Métodos de pago.
  - Tablas, formularios, alertas y diálogos reutilizados.
- [ ] **T01.8 — Logs y respaldos.**
  - Errores en archivo privado.
  - Backup programado y prueba de restauración.
- [ ] **T01.9 — Pruebas base de arquitectura.**
  - Un handler no contiene SQL.
  - Un servicio no contiene HTML.
  - Un repositorio no contiene reglas de negocio.
  - Una vista no ejecuta SQL.
  - Admin y público comparten un servicio común.

### Entregables

- Migraciones y esquema aprobados.
- Módulo de auditoría.
- Almacenamiento privado validado.
- Catálogo de premios y módulo de métodos de pago.
- Worker/cron operativo o respaldo documentado.
- Menú administrativo preparado.

### Criterios de aceptación

- La estructura modular y las convenciones están aprobadas.
- Las migraciones se ejecutan desde cero y son repetibles.
- Un archivo inválido es rechazado.
- Una acción crítica genera auditoría.
- Un método de pago se crea, edita y asigna a un sorteo.
- La expiración de reservas funciona con o sin cron.
- Se cumple el checklist de capas de `specs/006-mvp-sorteos/arquitectura.md`.

### Puerta de control

**Gate 1:** modelo de datos y migraciones aprobados; backup/restore probado.

---

## 6. Fase 2 — Backoffice de sorteos

### Objetivo

Permitir que el administrador cree, publique y cierre sorteos con reglas versionadas, métodos de pago y checklist legal.

### Tareas

- [ ] **T02.0 — Catálogo de premios.**
  - Nombre, descripción, tipo producto, valor estimado.
  - Fotos del premio con validación.
  - Estado activo/inactivo.
  - Asignación de premios al sorteo y posición.
- [ ] **T02.1 — CRUD de sorteo en borrador.**
  - Nombre, slug, descripción, portada, fechas.
  - Precio, moneda, cupos, límite por persona, edad mínima.
  - Selección de premios del catálogo, condiciones e imágenes.
- [ ] **T02.2 — Bases versionadas.**
  - Crear versión, publicar, hash.
  - Inmutabilidad después de publicar.
  - Aceptación por el participante.
- [ ] **T02.3 — Métodos de pago por sorteo.**
  - Seleccionar métodos creados en el módulo de métodos de pago.
  - Asignar QR/datos/instrucciones específicas.
  - Mostrar contacto del dueño para sobrepagos.
- [ ] **T02.4 — Checklist de publicación.**
  - Premio, fechas, bases, precio, cupos, métodos, autorización si aplica, privacidad.
  - Bloqueo si falta un requisito.
  - Confirmación reforzada.
- [ ] **T02.5 — Estados y transiciones.**
  - Borrador → publicado → ventas cerradas → sorteado → entrega → cerrado.
  - Pausar, cancelar, reprogramar con motivo y auditoría.
- [ ] **T02.6 — Listado administrativo.**
  - Búsqueda, filtros por estado/fecha, contadores.
  - Acciones según estado.
- [ ] **T02.7 — Vista previa pública.**
  - Ver el sorteo como lo verá el cliente antes de publicar.
- [ ] **T02.8 — Auditoría de cambios críticos.**
  - Publicar, cerrar, cancelar, reprogramar, cambiar métodos.
- [ ] **T02.9 — Módulo de métodos de pago.**
  - Tipo billetera digital o cuenta bancaria.
  - Proveedor, titular, teléfono, cuenta/tarjeta, CCI.
  - QR opcional e instrucciones.
  - Estado activo/inactivo y contacto para sobrepagos.

### Entregables

- CRUD completo de sorteos.
- Bases versionadas.
- Checklist de publicación.
- Listado con filtros.
- Vista previa.

### Criterios de aceptación

- Se crea un sorteo con premio, precio, cupos, límite y métodos.
- No se publica sin checklist completo.
- Las bases publicadas no se editan.
- Se cierra y cancela con motivo y auditoría.
- Varios sorteos pueden coexistir.

### Puerta de control

**Gate 2:** un sorteo de prueba se crea, publica, cierra y cancela correctamente, con auditoría.

---

## 7. Fase 3 — Web pública

### Objetivo

Mostrar los sorteos al público sin romper la home de radio y con contenido legal accesible.

### Tareas

- [ ] **T03.1 — Layout público de sorteos.**
  - Nueva capa limpia, sin mezclar estilos inline de Sitejet.
  - Navegación: Inicio, Sorteos, Ganadores, Cómo participar, Reclamos.
- [ ] **T03.2 — Home mixta.**
  - Bloque de hasta 3 sorteos destacados.
  - Enlace visible a `/sorteos`.
  - Conservar radio, player y contenido actual.
- [ ] **T03.3 — Listado `/sorteos`.**
  - Tarjetas con estado, premio, precio, fechas, cupos y CTA.
  - Filtros por estado.
- [ ] **T03.4 — Detalle `/sorteos/{slug}`.**
  - Premio, fechas, precio, cupos, métodos de pago.
  - Bases, autorización, privacidad, CTA.
  - Estado y cuenta regresiva.
- [ ] **T03.5 — Páginas legales.**
  - Bases por sorteo/versión.
  - Política de privacidad.
  - Términos.
  - Libro de Reclamaciones virtual.
  - FAQ: diferida.
- [ ] **T03.6 — SEO y compartir.**
  - Título, descripción, Open Graph, URL estable.
  - Sin datos personales.
- [ ] **T03.7 — Service worker y caché.**
  - Excluir rutas dinámicas (`/sorteos/*/participar`, `/pago`, `/participaciones`) o usar network-first.
  - No cachear comprobantes ni datos privados.
- [ ] **T03.8 — Accesibilidad y responsive.**
  - Foco, labels, contraste, teclado, 375/768/escritorio.
  - Lighthouse.

### Entregables

- Home mixta.
- Listado y detalle.
- Páginas legales.
- SEO y cache definidos.
- Pruebas responsive y accesibilidad.

### Criterios de aceptación

- Solo se ven sorteos publicados/cerrados, nunca borradores.
- La home conserva la radio y muestra sorteos destacados.
- Las bases y la privacidad son accesibles antes de participar.
- No hay datos personales en páginas públicas.
- Lighthouse sin errores críticos.

### Puerta de control

**Gate 3:** contenido público y legal aprobado; navegación y responsive validados.

---

## 8. Fase 4 — Participación y tickets

### Objetivo

Permitir que un invitado compre participaciones, reciba números únicos y reserva por 30 minutos.

### Tareas

- [ ] **T04.1 — Formulario de participante.**
  - Nombre, DNI, celular, correo.
  - Validación de formato y mayoría de edad.
  - Aceptación de bases y privacidad, con versión, fecha, IP y user agent.
- [ ] **T04.2 — Cantidad y límite.**
  - Precio × cantidad.
  - Límite por persona desde el sorteo.
  - Validación en servidor por DNI/correo/celular.
- [ ] **T04.3 — Pool y asignación de números.**
  - Pool por sorteo.
  - Asignación en transacción.
  - `UNIQUE (raffle_id, number)`.
  - Reintento ante colisión.
- [ ] **T04.4 — Reserva de 30 minutos.**
  - Estado reservado.
  - Expiración perezosa + worker.
  - Liberación de números.
  - Extensión manual para BCP si se aprueba.
- [ ] **T04.5 — Orden y códigos.**
  - Código de orden público.
  - Código de consulta.
  - Estado de la orden.
- [ ] **T04.6 — Confirmación.**
  - Números, monto, estado, instrucciones.
  - Enlace `wa.me` prellenado.
  - Aviso de que el ticket se emite solo con pago aprobado.
- [ ] **T04.7 — Consulta de participaciones.**
  - Código + dato de contacto (propuesta).
  - Sin exponer datos de terceros.

### Entregables

- Formulario y validaciones.
- Asignación de números.
- Reserva y expiración.
- Pantalla de confirmación.
- Consulta por código.

### Criterios de aceptación

- Dos personas no pueden recibir el mismo número.
- El límite por persona se cumple.
- La reserva expira a los 30 minutos.
- El cliente puede consultar su orden sin contraseña.
- No se emite ticket sin pago aprobado.

### Puerta de control

**Gate 4:** prueba de concurrencia y expiración superada; sin duplicados ni sobreventa.

---

## 9. Fase 5 — Pagos manuales Yape/Plin/BCP

### Objetivo

Cobrar de forma manual, recibir comprobantes y emitir tickets solo después de la aprobación del administrador.

### Tareas

- [ ] **T05.1 — Pantalla de instrucciones.**
  - Monto exacto, código de orden.
  - QR, titular, teléfono o cuenta/CCI.
  - Instrucciones por método.
  - Cuenta regresiva.
- [ ] **T05.2 — Subida de comprobante.**
  - JPG/PNG/WebP; validar `getimagesize`, tamaño y nombre.
  - Guardar en almacenamiento privado.
  - Vista previa para el admin.
- [ ] **T05.3 — Cola de revisión.**
  - Filtros por estado, método, fecha, sorteo.
  - Datos de la orden, participante, monto y comprobante.
  - Aprobar/rechazar con motivo.
- [ ] **T05.4 — Emisión de tickets.**
  - Al aprobar: pago confirmado, orden pagada, tickets emitidos.
  - Al rechazar: motivo, opción de reintento, reserva según estado.
  - Recalcular disponibilidad.
- [ ] **T05.5 — Casos especiales.**
  - Comprobante duplicado.
  - Monto incorrecto.
  - Pago tardío con reserva expirada.
  - Pago de más/menos.
  - Reembolso manual.
- [ ] **T05.6 — BCP.**
  - Extensión manual de reserva.
  - Registro de voucher.
  - Verificación en banca.
- [ ] **T05.7 — Auditoría y evidencia.**
  - Quién aprobó, cuándo, monto, comprobante.
  - No borrar comprobantes.

### Entregables

- Instrucciones de pago.
- Carga de comprobante.
- Cola de revisión.
- Emisión de tickets.
- Manejo de casos especiales.

### Criterios de aceptación

- El ticket se emite solo con pago aprobado.
- Un comprobante no se usa dos veces.
- Un rechazo no emite ticket.
- El admin puede ver y auditar todo.
- Los archivos no son públicos.
- El monto y el código se muestran correctamente.

### Puerta de control

**Gate 5:** flujo completo con Yape, Plin y BCP probado; aprobación/rechazo auditados.

---

## 10. Fase 6 — Sorteo y transparencia

### Objetivo

Ejecutar un sorteo verificable, sin intervención manual sobre el resultado, y publicar la evidencia.

### Tareas

- [ ] **T06.1 — Cierre de ventas y snapshot.**
  - Congelar tickets pagados.
  - Excluir reservados, cancelados y reembolsados.
  - Ordenar y calcular hash.
- [ ] **T06.2 — Semilla y compromiso.**
  - Generar `seed` con CSPRNG.
  - Calcular `seed_hash`.
  - Publicar hash y snapshot antes del sorteo.
- [ ] **T06.3 — Ejecución.**
  - Confirmación del admin.
  - Cálculo determinista del ganador y suplentes.
  - Registro de fecha, ejecutor y algoritmo.
- [ ] **T06.4 — Resultados.**
  - Ganador, suplentes, ticket, premio.
  - Publicación.
- [ ] **T06.5 — Verificación pública.**
  - Semilla, hash de semilla, hash de snapshot, algoritmo.
  - Lista anonimizada.
  - Instrucciones o verificador.
  - Acta imprimible/PDF.
- [ ] **T06.6 — Reprogramación/redraw.**
  - Procedimiento documentado.
  - Nueva semilla/hash.
  - Evidencia y auditoría.
- [ ] **T06.7 — Pruebas.**
  - Reproducibilidad.
  - Snapshot inmutable.
  - Exclusión de tickets inválidos.
  - Resultado sin datos personales.

### Entregables

- Snapshot y hashes.
- Motor de sorteo.
- Resultados públicos.
- Acta.
- Verificación.

### Criterios de aceptación

- Dos ejecuciones con la misma semilla y snapshot dan el mismo resultado.
- El ganador no se puede editar manualmente.
- La página pública permite verificar.
- No se exponen DNI/correo/teléfono.
- El acta coincide con los hashes.

### Puerta de control

**Gate 6:** auditoría interna del sorteo y verificación independiente superadas.

---

## 11. Fase 7 — Entrega, auditoría, reportes y notificaciones

### Objetivo

Cerrar el ciclo operativo: contactar ganadores, registrar entrega, auditar y reportar.

### Tareas

- [ ] **T07.1 — Seguimiento de entrega.**
  - Estados: pendiente, contactado, identidad verificada, programada, entregado, no reclamado, desierto.
  - Contacto, fecha, responsable.
- [ ] **T07.2 — Evidencia.**
  - Foto, acta, firma, comprobante.
  - Almacenamiento privado y acceso restringido.
- [ ] **T07.3 — Auditoría visible.**
  - Vista de logs con filtros.
  - Sin edición/borrado.
- [ ] **T07.4 — Reportes.**
  - Dashboard: sorteos, tickets, ingresos, pendientes, entregas.
  - CSV de pagos, tickets, participantes y entregas.
  - Control de acceso.
- [ ] **T07.5 — WhatsApp manual.**
  - Plantillas de texto.
  - Enlace `wa.me` prellenado.
  - Registro de “notificado”.
- [ ] **T07.6 — Correo y FAQ: diferidos.**
  - No se implementan en el MVP.
  - El MVP usa pantalla, WhatsApp manual y número de contacto.
  - Quedan documentados para una fase posterior.

### Entregables

- Módulo de entregas.
- Evidencia.
- Visor de auditoría.
- Reportes/CSV.
- Plantillas WhatsApp.
- Número de contacto para sobrepagos.

### Criterios de aceptación

- Se registra una entrega completa con evidencia.
- El reporte CSV coincide con la base.
- La auditoría muestra las acciones críticas.
- El envío manual queda registrado.
- El número de contacto para sobrepagos se muestra en la página de pago/confirmación.
- No se exponen datos personales en reportes sin permiso.

### Puerta de control

**Gate 7:** operación de entrega y reportes aprobada por el organizador.

---

## 12. Fase 8 — Hardening, UAT y lanzamiento

### Objetivo

Reducir riesgos técnicos y operativos antes de abrir al público.

### Tareas

- [ ] **T08.1 — Revisión de seguridad.**
  - CSRF, XSS, SQLi, autorización, sesión, archivos, rate limiting.
  - Secretos y configuración.
  - Cabeceras y HTTPS.
- [ ] **T08.2 — Performance y concurrencia.**
  - Índices.
  - Prueba de reserva simultánea.
  - Tiempos de carga.
- [ ] **T08.3 — Accesibilidad y responsive.**
  - Lighthouse.
  - 375/768/escritorio.
  - Teclado y foco.
- [ ] **T08.4 — Backup/restore.**
  - Prueba real de restauración.
  - Documentación.
- [ ] **T08.5 — UAT.**
  - Sorteo piloto interno.
  - Casos reales de pago/prueba.
  - Corrección de hallazgos.
- [ ] **T08.6 — Cierre legal/contenido.**
  - Bases, privacidad, Libro de Reclamaciones.
  - Autorización/checklist.
  - Política de reembolso.
- [ ] **T08.7 — Despliegue.**
  - Backup previo.
  - Migraciones.
  - Verificación.
  - Rollback.
- [ ] **T08.8 — Post-lanzamiento.**
  - Monitoreo.
  - Soporte.
  - Revisión de métricas.

### Entregables

- Informe de seguridad.
- Pruebas de carga/concurrencia.
- UAT firmada.
- Checklist de lanzamiento.
- Plan de rollback.
- Primer sorteo piloto.

### Criterios de aceptación

- No hay hallazgos críticos abiertos.
- El backup se restaura correctamente.
- El sorteo piloto se ejecuta de punta a punta.
- Legal/contenido aprobado.
- Go/No-Go firmado.

### Puerta de control

**Gate 8:** lanzamiento autorizado. Si la validación legal del cobro no está cerrada, **no se publica**.

---

## 13. Pistas transversales

### 13.1 Legal y cumplimiento

- Validar calificación del cobro.
- Bases, privacidad, edad, reclamos.
- Banco de datos, ODP, ARCO.
- Tributación/comprobantes.
- AML/UIF si aplica.
- Consentimiento de imagen del ganador.

### 13.2 Infraestructura

- PHP 8.0 EOL: plan de upgrade.
- `fileinfo` o validación alternativa.
- SMTP (diferido; no requerido en MVP).
- Cron/worker.
- Backups y restauración.
- SSL/HTTPS.
- Monitoreo y logs.

### 13.3 Calidad y seguridad

- Pruebas de concurrencia.
- Pruebas de archivos maliciosos.
- Pruebas de autorización.
- Pruebas de regresión.
- Revisión de dependencias.
- Auditoría de pagos y sorteos.

### 13.4 Contenido y diseño

- Home mixta.
- Textos legales.
- FAQ (diferida).
- Plantillas de WhatsApp.
- Accesibilidad.
- SEO.

---

## 14. Backlog MVP priorizado

### Must

- CRUD de sorteos y bases versionadas.
- Catálogo de premios con fotos, descripción y valor.
- Módulo de métodos de pago (billetera/banco, teléfono/cuenta/tarjeta, QR opcional).
- Home mixta, listado y detalle.
- Participante invitado con ficha completa.
- Números automáticos y reserva de 30 minutos.
- Pagos manuales Yape/Plin/BCP con comprobante.
- Aprobación/rechazo y emisión de tickets.
- Snapshot, commit-reveal, resultados y verificación.
- Transparencia con hashes y lista anonimizada.
- Entrega manual con evidencia.
- Auditoría.
- Páginas legales y Libro de Reclamaciones.

### Should

- Consulta de participaciones por código.
- Reportes CSV.
- Plantillas de WhatsApp.
- Extensión de reserva para BCP.
- Dashboard de métricas.
- Contacto visible para sobrepagos.

### Could

- PDF de BCP.
- Verificador interactivo avanzado.
- 2FA.
- Roles múltiples.
- Correo automático (diferido).
- FAQ (diferida).
- Notificaciones push.

### Won't (MVP)

- Pasarela automática.
- API Yape/Plin.
- API WhatsApp.
- OTP/RENIEC.
- SUNAT.
- Notaría/live stream.
- AML automatizado.
- Portal del ganador.
- Multi-idioma.
- BI avanzado.

---

## 15. Plan de pruebas del MVP

### 15.1 Sorteos

- Crear borrador con todos los campos.
- No publicar sin checklist.
- Publicar y ver en web.
- Editar borrador; no editar bases publicadas.
- Cerrar, cancelar, reprogramar.
- Varios sorteos simultáneos.

### 15.2 Participación y tickets

- Compra con ficha completa.
- DNI/celular/correo inválidos.
- Límite por persona.
- Dos solicitudes simultáneas por el mismo número.
- Expiración de reserva.
- Consulta por código.
- Acceso a datos de terceros (debe fallar).

### 15.3 Pagos manuales

- Subir JPG/PNG/WebP válido.
- Subir archivo falso o demasiado grande.
- Aprobar pago y emitir ticket.
- Rechazar pago y permitir reintento.
- Comprobante duplicado.
- Monto incorrecto.
- Pago después de expiración.
- Pago BCP con extensión.
- Acceso no autorizado a comprobantes.

### 15.4 Sorteo

- Snapshot con tickets pagados.
- Exclusión de reservados/cancelados.
- Hash de semilla antes.
- Reproducir resultado con semilla.
- Intento de cambiar ganador.
- Redraw documentado.
- Página pública sin PII.

### 15.5 Entrega y reportes

- Estados de entrega.
- Evidencia privada.
- CSV correcto.
- Auditoría de acciones.
- Notificación manual registrada.

### 15.6 Seguridad y calidad

- CSRF.
- XSS.
- SQLi.
- Rate limiting.
- Sesión/admin.
- Archivos.
- Backup/restore.
- Lighthouse.
- Responsive.

---

## 16. Matriz de riesgos del plan

| Riesgo | Fase | Impacto | Mitigación |
| --- | --- | --- | --- |
| Calificación legal del cobro | 0/8 | Muy alto | Validar con abogado/contador antes de publicar. |
| BCP tarda más de 30 min | 4/5 | Medio | Extensión manual o tiempo configurable. |
| Comprobante falso/duplicado | 5 | Alto | Revisión manual, hash de archivo, monto/código. |
| Duplicado/sobreventa | 4 | Alto | Transacciones, unique, pruebas de concurrencia. |
| Archivo malicioso | 1/5 | Alto | Validación, tamaño, almacenamiento privado. |
| Sin cron | 1/4 | Medio | Expiración perezosa + limpieza manual. |
| Sin SMTP | 7 | Medio | WhatsApp manual + pantalla. |
| PHP 8.0 EOL | 0/8 | Alto | Upgrade antes de lanzamiento. |
| Fuga de DNI/celular | 1–7 | Muy alto | Cifrado/hash, RBAC, auditoría, no publicar. |
| Pérdida de datos | 8 | Muy alto | Backup cifrado y restauración probada. |
| Scope creep | Todo | Alto | Backlog MoSCoW y gates. |

---

## 17. Decisiones abiertas mapeadas a fases

| Decisión | Fase | Estado |
| --- | --- | --- |
| Producto/valor del premio | 0 | En revisión |
| Autorización/calificación legal | 0 | Pendiente |
| Plantillas legales | 0 | En revisión |
| Política de reembolso | 0 | Pendiente |
| Tiempo de reserva BCP | 0/4 | Pendiente |
| PDF de BCP | 1/5 | Pendiente |
| Consulta de participaciones sin correo | 4 | Propuesta |
| Publicación del nombre del ganador | 6 | Pendiente |
| SMTP/correo | 7 | Diferido |
| FAQ | 7 | Diferida |
| 2FA | 8+ | Diferido |
| Roles múltiples | 8+ | Diferido |

---

## 18. Fuera de alcance del plan

- Implementación en esta fase.
- Pasarela automática.
- API Yape/Plin.
- WhatsApp API.
- OTP/RENIEC.
- SUNAT.
- Notaría/live stream.
- AML automatizado.
- Portal del ganador.
- Multi-idioma.
- BI avanzado.
- App móvil.
- Framework MVC, ORM/ActiveRecord o contenedor de servicios.
- Cambio de motor de base de datos.
- Correo electrónico automático.
- Preguntas frecuentes (FAQ).

---

## 19. Siguiente paso inmediato

1. Cerrar la Fase 0: premios/productos, validación legal, métodos de pago, políticas y contenidos.
2. Aprobar el Gate 0 para publicar; el desarrollo técnico puede iniciar en paralelo.
3. Revisar la arquitectura aprobada en `specs/006-mvp-sorteos/arquitectura.md`.
4. Usar la especificación 007 aprobada como base de implementación.
5. Iniciar la Fase 1 técnica cuando el organizador lo autorice.

**No se publica ni se cobra hasta completar el Gate 0. La Fase 1 técnica puede avanzar en paralelo.**

---

## 20. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Plan por fases del MVP basado en 003 y 004. |
