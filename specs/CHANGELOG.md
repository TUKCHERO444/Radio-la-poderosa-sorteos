# Changelog del proyecto

Registro de cambios de documentación y código. Toda modificación debe añadir una entrada aquí y actualizar la especificación correspondiente.

Formato:

- **Fecha:** `YYYY-MM-DD`
- **Tipo:** `docs` · `code` · `fix` · `security` · `infra`
- **Área:** panel, web pública, sorteos, pagos, documentación, etc.
- **Archivos:** rutas relevantes.
- **Nota:** impacto o decisión asociada.

---

## 2026-11-16

### code - Premios de todos los sorteos activos en /sorteos

- **Área:** web pública / sorteos.
- **Archivos:** `app/web/sorteos.php`, `app/web/views/sorteos.php`, `public_html/css/sorteos.css`, `public_html/sw.js`.
- **Nota:** la sección «Premios» ya no muestra solo el panel del sorteo seleccionado en el carrusel. Ahora renderiza una tarjeta por cada sorteo activo (`published`, `paused`, `sales_closed`) desde la base de datos, con su imagen, estado, resumen, premio principal, premios adicionales, precio, máximo por persona, fechas y CTA. Hoy solo aparece Maicol; al publicar otro sorteo, su tarjeta se genera automáticamente. Se subió la caché a `radio-poderosa-v18` y el CSS a `v=20261116-12`. La card se amplió a 494 px, la imagen a 340 px y el texto se compactó (13 px, padding lateral de 26 px).

### docs - Plan detallado de Fase 4

- **Área:** documentación / participación / seguridad.
- **Archivos:** `specs/010-fase-4/plan.md`.
- **Nota:** se documentó el plan de participación, órdenes, números y reservas: reglas de seguridad, persistencia, relaciones con las entidades actuales, diseño del módulo, validaciones, concurrencia, matriz de pruebas y orden de implementación. Queda pendiente aprobar las decisiones D1, D3, D4, D5, D9 y D10 antes de codificar.

### fix - Vista previa de borradores solo en desarrollo

- **Área:** web pública / seguridad.
- **Archivos:** `app/inc/config.php`, `app/inc/config.local.php` (local), `app/web/sorteos.php`.
- **Nota:** el borrador más reciente solo se muestra en `/sorteos` si `public.preview_drafts` está activo. En local se activó para probar; en producción queda en `false`, de modo que la web pública respeta la regla de no mostrar borradores.

### fix — Portada editable en sorteos publicados

- **Área:** panel / sorteos / multimedia.
- **Archivos:** `app/modules/raffles/AdminRaffleHandler.php`, `app/modules/raffles/RaffleService.php`, `app/modules/raffles/RaffleRepository.php`, `app/admin/module_routes.php`, `app/admin/views/raffles/form.php`, `public_html/assets/admin/admin.js`.
- **Nota:** el formulario principal de un sorteo publicado es de solo lectura, así que el input de portada estaba deshabilitado y el botón no hacía nada. Se añadió un formulario independiente `POST /admin/raffles/{id}/cover` para subir/reemplazar la portada sin modificar fechas, premios ni bases. Guarda la `secure_url` en `raffles.cover_image_path`, elimina la imagen anterior y audita `raffle.cover_updated`. El hero público usa esa URL; las tarjetas usan `prizes.image_path`. La vista previa del formulario es local y no confirma el guardado.

### fix — Carga y visualización de imágenes de premios

- **Área:** panel / multimedia / web pública.
- **Archivos:** `app/shared/storage/ImageStorage.php`, `public_html/assets/admin/admin.js`, `app/web/sorteos.php`, `app/admin/views/prizes/form.php`, `app/admin/views/payment_methods/form.php`, `app/admin/views/raffles/form.php`.
- **Nota:** se eleva el límite a 10 MB y se valida tipo/tamaño en el cliente antes de enviar, con mensaje bajo el campo y bloqueo del formulario. La portada de `/sorteos` usa la imagen de portada o la primera imagen de premio disponible. Se verificó el flujo real: la imagen se guarda en Cloudinary, la URL queda en `image_path` y se muestra en la tarjeta y el hero. La vista previa del formulario es local y no confirma el guardado; el error de consola "message channel closed" es de una extensión del navegador. Se restauraron los datos de prueba.

### code — Página de sorteos conectada al panel

- **Área:** web pública / sorteos.
- **Archivos:** `app/web/sorteos.php`, `app/web/views/sorteos.php`, `app/modules/raffles/RaffleRepository.php`, `public_html/index.php`, `public_html/css/sorteos.css`, `public_html/js/sorteos.js`, `public_html/js/sorteos-carousel.js`, `public_html/sw.js`.
- **Nota:** `/sorteos` ahora se alimenta de los sorteos y premios reales. El carrusel muestra una diapositiva por sorteo (portada o imagen del premio principal, nombre, precio, cupos, fechas y estado), la cuenta regresiva y la sección de premios se sincronizan al cambiar de sorteo, y el registro toma el precio/máximo del sorteo activo. Los sorteos publicados son visibles; si no hay ninguno, se muestra el borrador más reciente en vista previa `noindex`. Se eliminó la plantilla estática y las imágenes de marcador. Caché PWA `radio-poderosa-v14`.

### code — Imágenes de premios y multimedia en Cloudinary

- **Área:** panel / multimedia.
- **Archivos:** `app/shared/storage/CloudinaryClient.php`, `app/shared/storage/ImageStorage.php`, `app/inc/config.php`, `app/inc/config.local.php` (solo local), `public_html/sw.js`.
- **Nota:** se replica el flujo del proyecto Carwash (`cloudinary-labs/cloudinary-laravel` + `CLOUDINARY_URL`): subida firmada con cURL, `secure_url` guardada en `image_path`, borrado por `public_id`, carpetas `radiolpsorteos/{prizes,payment-methods,raffles}` y fallback local. Sin dependencias nuevas ni Composer. El secreto queda en `app/inc/config.local.php`, fuera de `public_html`. Probado con alta, edición y baja de premios desde el panel; la imagen pública responde 200 y el asset se elimina al reemplazar/borrar. El CDN puede conservar la URL cacheada unos minutos.

### code — Tarjetas de premios más amplias y con imagen protagonista

- **Área:** web pública / diseño.
- **Archivos:** `public_html/css/sorteos.css`, `public_html/sw.js`.
- **Nota:** en «Premios del sorteo» la sección usa hasta 1440 px de ancho con 16 px de margen lateral, la rejilla sube el espacio entre tarjetas a 28 px y cada tarjeta gana aproximadamente 78 × 49 px en pantallas de 1440 px. La imagen crece de 180 a 270 px de alto (210 px en móvil) y la viñeta de puesto y el bloque de texto se reducen: badge de 11 px, título de 20 px, descripción de 14 px y padding de 20 px. Caché PWA `radio-poderosa-v11`.

### code — Carrusel de imágenes en el hero de sorteos

- **Área:** web pública / diseño.
- **Archivos:** `app/templates/pages/sorteos.html`, `public_html/css/sorteos.css`, `public_html/js/sorteos-carousel.js`, `public_html/images/sorteos/hero-speaker.svg`, `hero-care.svg`, `hero-breakfast.svg`, `public_html/sw.js`.
- **Nota:** la tarjeta principal del hero ahora es un carrusel de 3 imágenes con transición de fundido, avance automático cada 5.5 s, flechas, indicadores, botón de pausa/reanudar, swipe táctil y navegación con teclado. Se pausa al pasar el cursor o al enfocar y respeta `prefers-reduced-motion`. Las tres imágenes son SVG de marca en la paleta del sorteo, pensadas como marcadores de posición reemplazables por fotos. Caché PWA `radio-poderosa-v9`.

### code — Expansión de la tarjeta del hero de sorteos

- **Área:** web pública / diseño.
- **Archivos:** `public_html/css/sorteos.css`, `public_html/sw.js`.
- **Nota:** en escritorio (≥ 900 px) la tarjeta `.prize` crece 500 px en total a lo ancho (250 px por lado) y 30 % de su altura base hacia arriba y abajo (de 520 a 832 px). El ancho se limita al viewport con 16 px de margen y se desactiva el desbordamiento horizontal; en móvil/tablet se mantiene el tamaño de la plantilla. Caché PWA `radio-poderosa-v7`.

### code — Página de sorteos basada en `Main.dc.html`

- **Área:** web pública / diseño.
- **Archivos:** `app/templates/pages/sorteos.html`, `public_html/css/sorteos.css`, `public_html/js/sorteos.js`, `public_html/sw.js`.
- **Nota:** se reemplaza el placeholder de `/sorteos` por una emulación de la plantilla `public_html/Main.dc.html`: barra superior, navegación, hero con premio principal y cuenta regresiva, tarjetas de premios, pasos, registro, preguntas frecuentes y pie. Se respetan la distribución y la paleta (`#17243A`, `#4F5B6E`, `#F6F3EC`, `#FFFFFF`, `#E2DCCF`, `#B4472B`, `#C9A24B`) y las tipografías Inter/Space Grotesk. HTML, CSS y JS quedan en archivos independientes; el JS actualiza la cuenta regresiva y valida el formulario de aviso. Caché PWA `radio-poderosa-v6`.

### code — Portada pública y acceso a sorteos

- **Área:** web pública / diseño.
- **Archivos:** `app/templates/pages/home.html`, `app/templates/pages/sorteos.html`, `public_html/css/radio-landing.css`, `public_html/index.php`, `public_html/sw.js`.
- **Nota:** la portada de la radio se reorganiza en un hero de dos columnas (presentación de la emisora + reproductor en vivo), se añade el botón **Ver sorteos** debajo del reproductor, se retira el CSS inline del widget a una hoja modular y se crea la ruta `/sorteos` con una página placeholder `noindex`. También se corrigen encabezados y enlaces sociales del pie para accesibilidad. Caché PWA actualizada a `radio-poderosa-v5`.

### code — Validación de escritura y tipos de dato

- **Área:** panel / validación.
- **Archivos:** `app/shared/validation/FieldRules.php`, `app/admin/helpers.php`, `app/modules/prizes/PrizeService.php`, `app/modules/payment_methods/PaymentMethodService.php`, `app/modules/raffles/RaffleService.php`, `app/admin/router.php`, `app/admin/views/`, `public_html/assets/admin/admin.js`, `tools/tests/field_rules.php`.
- **Nota:** reglas compartidas entre formulario y backend: teléfono de 9 dígitos que empieza por 9, DNI de 8 o RUC de 11, cuenta de 8 a 20 dígitos, CCI de 20, nombres de persona solo con letras, nombres visibles con caracteres permitidos, slug, precios con dos decimales, enteros con rangos, fechas 2020–2100 y textos sin caracteres de control. El cliente filtra al escribir, valida al salir del campo y bloquea el envío con un resumen de errores; el backend normaliza separadores (`S/`, miles, guiones) y vuelve a validar antes de guardar. Se añaden 41 pruebas unitarias de reglas.

### code — CRUD de premios

- **Área:** panel / sorteos.
- **Archivos:** `app/modules/prizes/`, `app/admin/views/prizes/`.
- **Nota:** alta, edición, activación y eliminación de premios con nombre, tipo, valor estimado, entrega, descripción e imagen. Valida longitudes, enum de tipo, valor no negativo y bloquea la eliminación si el premio está asignado a un sorteo. Registra auditoría.

### code — CRUD de métodos de pago

- **Área:** panel / pagos.
- **Archivos:** `app/modules/payment_methods/`, `app/admin/views/payment_methods/`.
- **Nota:** billeteras (Yape/Plin) y cuentas bancarias (BCP), QR opcional y teléfono de contacto para sobrepagos. Campos obligatorios según el tipo; no elimina métodos asignados o con pagos. Registra auditoría.

### code — CRUD de sorteos y publicación

- **Área:** panel / sorteos.
- **Archivos:** `app/modules/raffles/`, `app/admin/views/raffles/`.
- **Nota:** creación y edición en borrador, asignación de premio principal y adicionales, métodos de pago, precio, cupos, máximo por persona, fechas, bases versionadas, portada y destacado. Publicación con checklist legal de cinco puntos, cierre de ventas, cancelación y eliminación controlada. Genera hash de bases y registra auditoría.

### code — Autoload modular y clases compartidas

- **Área:** arquitectura.
- **Archivos:** `app/bootstrap.php`, `app/shared/`.
- **Nota:** autoload `App\` con carpetas snake_case; `Input`, `Validator`, `Money`, `Dates`, `Ids`, `DomainException`, `AuditService` e `ImageStorage`. Valida y guarda imágenes JPG/PNG/WebP hasta 5 MB.

### code — Migración de confirmación legal

- **Área:** base de datos / legal.
- **Archivos:** `app/database/migrations/20261116130000_add_legal_confirmation_to_raffles.sql`.
- **Nota:** añade `legal_checklist`, `legal_confirmed_at` y `legal_confirmed_by` a `raffles` para auditar la publicación.

### code — Formularios y tablas del panel

- **Área:** panel / diseño.
- **Archivos:** `public_html/assets/admin/css/components/form-layout.css`, `media.css`, `tables.css`, `public_html/assets/admin/admin.js`, `app/admin/views/layout.php`, `dashboard.php`.
- **Nota:** layout de formulario en dos columnas con panel lateral, secciones, asignaciones, checklist de publicación, campo de imagen con vista previa, generación de slug, sincronización de premio principal y campos condicionales de pago. Resumen de errores con enlaces y foco automático. Tablas compactas sin desbordamiento; navegación y dashboard actualizados.

### fix — Despachador de rutas por método HTTP

- **Área:** panel / infraestructura.
- **Archivos:** `app/admin/router.php`, `app/admin/module_routes.php`.
- **Nota:** el despachador recorre todas las rutas antes de responder 405 y envía `Allow` con los métodos válidos.

### fix — Autoload de módulos con carpetas compuestas

- **Área:** arquitectura.
- **Archivos:** `app/bootstrap.php`.
- **Nota:** `PaymentMethods` se traduce a `payment_methods`; antes el autoload buscaba `paymentmethods` y las pantallas fallaban.

### code — Esquema inicial de base de datos del MVP

- **Área:** base de datos / Fase 1.
- **Archivos:** `app/database/migrations/20261116120000_create_mvp_schema.sql`.
- **Nota:** se crean tablas de premios, sorteos, bases, métodos de pago, participantes, órdenes, tickets, números, comprobantes, pagos, sorteos ejecutados, resultados, entregas, aceptaciones legales, auditoría y notificaciones.

### code — Migrador de base de datos

- **Área:** infraestructura / Fase 1.
- **Archivos:** `tools/migrate.php`.
- **Nota:** aplica `schema.sql` si falta la base, crea `schema_migrations`, ejecuta migraciones pendientes y evita repetirlas.

### code — Seeder demo inicial

- **Área:** base de datos / Fase 1.
- **Archivos:** `tools/seed.php`.
- **Nota:** crea 3 premios producto, 3 métodos de pago demo (Yape, Plin, BCP), un sorteo demo en `draft`, bases demo y settings demo. Es idempotente y no crea participantes ni pagos reales.

### infra — Backup previo a la migración

- **Área:** base de datos.
- **Archivos:** `app/storage/backups/radiolpsorteos_before_mvp_20261116.sql`.
- **Nota:** respaldo de la base local antes de aplicar el esquema.

### docs — Avance de Fase 1

- **Área:** documentación.
- **Archivos:** `specs/009-fase-1/avance.md`, `specs/005-mvp-sorteos/plan-fases.md`, `specs/README.md`.
- **Nota:** se documenta el esquema, migrador, seeder, datos demo, comandos y verificación.

### docs — Aprobación del MVP y apertura de Fase 0

- **Área:** documentación / MVP.
- **Archivos:**
  - `specs/004-mvp-sorteos/decisiones.md`
  - `specs/005-mvp-sorteos/plan-fases.md`
  - `specs/006-mvp-sorteos/arquitectura.md`
  - `specs/006-mvp-sorteos/convenciones.md`
  - `specs/007-mvp-sorteos/especificacion.md`
  - `specs/008-fase-0/checklist.md`
  - `specs/README.md`
- **Nota:** se aprueban las decisiones, el plan, la arquitectura y la especificación. Se abre la Fase 0 con checklist legal, pagos, infraestructura y contenidos.

### docs — Convenciones de arquitectura 006

- **Área:** arquitectura / código.
- **Archivos:** `specs/006-mvp-sorteos/convenciones.md`
- **Nota:** inglés para código/BD, español para UI; clases con autoload manual; PHP 8.0; excepciones + validación por arrays; tabla de rutas; validadores compartidos; runner simple; fechas en `America/Lima`; MySQL/MariaDB con PDO.

### docs — Especificación 007

- **Área:** producto / técnica.
- **Archivos:** `specs/007-mvp-sorteos/especificacion.md`
- **Nota:** requisitos funcionales, reglas de negocio, modelo de datos, rutas, pantallas, flujos, commit-reveal, criterios de aceptación y trazabilidad.

### docs — Arquitectura 006 actualizada

- **Área:** arquitectura.
- **Archivos:** `specs/006-mvp-sorteos/arquitectura.md`
- **Nota:** se usa `app/web` para la capa pública, se referencian convenciones y 007, y se cierra la decisión de runner de pruebas.

### docs — Plan 005 actualizado

- **Área:** planificación.
- **Archivos:** `specs/005-mvp-sorteos/plan-fases.md`
- **Nota:** Fase 1 incluye estructura modular y pruebas de capas; se referencia la arquitectura 006 y la especificación 007.

### docs — Registro de cambios

- **Área:** documentación.
- **Archivos:** `specs/CHANGELOG.md`
- **Nota:** se crea este registro para documentar todos los cambios futuros.

### docs — Normalización de carpetas de `app/shared`

- **Área:** arquitectura / convenciones.
- **Archivos:** `specs/006-mvp-sorteos/convenciones.md`, `specs/006-mvp-sorteos/arquitectura.md`
- **Nota:** todos los directorios van en minúsculas (`app/shared/http`, `domain`, `validation`, `audit`, `storage`) para que el autoload manual funcione igual en Windows y Linux.

### docs — Módulos de premios y métodos de pago

- **Área:** producto / arquitectura.
- **Archivos:** `specs/004-mvp-sorteos/decisiones.md`, `specs/005-mvp-sorteos/plan-fases.md`, `specs/006-mvp-sorteos/arquitectura.md`, `specs/006-mvp-sorteos/convenciones.md`, `specs/007-mvp-sorteos/especificacion.md`, `specs/008-fase-0/checklist.md`.
- **Nota:** los premios se gestionan en un catálogo dedicado (fotos, descripción, tipo producto, valor); los métodos de pago en un módulo independiente (billetera/banco, teléfono/cuenta/tarjeta, QR opcional). El sorteo selecciona premios y métodos.

### docs — Sobrepagos, correo y FAQ

- **Área:** producto / operación.
- **Archivos:** `specs/004-mvp-sorteos/decisiones.md`, `specs/005-mvp-sorteos/plan-fases.md`, `specs/007-mvp-sorteos/especificacion.md`, `specs/008-fase-0/checklist.md`.
- **Nota:** se muestra el número de contacto del dueño para sobrepagos; correo y FAQ quedan diferidos. El MVP usa pantalla + WhatsApp manual.

### docs — Plantillas legales base para Perú

- **Área:** legal / documentación.
- **Archivos:** `specs/007-mvp-sorteos/plantillas-legales.md`, `specs/007-mvp-sorteos/especificacion.md`.
- **Nota:** se crean borradores de bases, privacidad, términos, Libro de Reclamaciones y consentimiento de imagen para revisión legal. No son textos definitivos.

### docs — Ajustes de Fase 0 y plan

- **Área:** planificación / operación.
- **Archivos:** `specs/004-mvp-sorteos/decisiones.md`, `specs/005-mvp-sorteos/plan-fases.md`, `specs/008-fase-0/checklist.md`.
- **Nota:** se actualizan tareas, entregables, alcance y checklist con catálogo de premios, módulo de métodos de pago, contacto para sobrepagos y correo/FAQ diferidos.

---

## 2026-11-15

### code — UI kit del panel administrativo

- **Área:** panel / diseño.
- **Archivos:** `public_html/assets/admin/`, `app/admin/views/`.
- **Nota:** CSS modular, componentes universales, tema claro/oscuro, diálogo nativo, tablas responsive, formularios accesibles y búsqueda de usuarios.

### code — Paleta azul formal y tablas fluidas

- **Área:** panel / diseño.
- **Archivos:** `public_html/assets/admin/css/base/tokens.css`, `layout/sidebar.css`, `components/`, `pages/`, `layout.php`, `users/index.php`.
- **Nota:** sidebar claro estilo AdminLTE, acento azul, tablas a todo el ancho, contraste corregido.

### fix — Caché de assets del panel

- **Área:** panel / infraestructura.
- **Archivos:** `public_html/sw.js`, `app/admin/helpers.php`, vistas.
- **Nota:** el service worker excluye `/assets/admin/`; se agregó `ADMIN_ASSET_VERSION` y URLs versionadas.

### docs — Guía UI/UX y especificaciones base

- **Área:** documentación.
- **Archivos:** `specs/guia-estilo-ui-ux.md`, `specs/001-panel-admin/`, `specs/002-panel-ui-kit/`.
- **Nota:** base de estilo y UI kit del panel.

---

## Pendiente de registrar

- Implementación de participantes, órdenes, tickets, pagos y sorteos ejecutados.
- Página pública de sorteos en modo borrador.
- Configuración de SMTP, cron, backups y `fileinfo`.
- Cambios legales o de contenido aprobados por el organizador.

---

## Regla de mantenimiento

Cada cambio debe:

1. Actualizar la especificación correspondiente (003–007 o la que aplique).
2. Añadir una entrada en este changelog.
3. Indicar si afecta el alcance, la arquitectura, la seguridad o el lanzamiento.
4. No modificar código sin una tarea o decisión documentada.
