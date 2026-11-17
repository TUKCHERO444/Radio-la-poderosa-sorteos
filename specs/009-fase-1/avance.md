# 009 — Fase 1: avance técnico

- **Estado:** Fase 1 en curso.
- **Fecha:** 2026-11-16.
- **Base:** `specs/005-mvp-sorteos/plan-fases.md`, `specs/006-mvp-sorteos/arquitectura.md`, `specs/007-mvp-sorteos/especificacion.md`.
- **Alcance de este avance:** esquema inicial de base de datos, migrador y seeder de datos ficticios; autoload y clases compartidas; CRUD de premios, métodos de pago y sorteos en el panel.

---

## 1. Resumen

Se inició la Fase 1 con la base de datos del MVP:

- Migración SQL con las tablas del modelo aprobado.
- Herramienta `tools/migrate.php` para aplicar migraciones.
- Seeder `tools/seed.php` con premios, métodos de pago y un sorteo demo en borrador.
- Base de datos local respaldada antes de aplicar cambios.

Sobre esa base se implementó el primer bloque funcional del panel:

- Autoload manual para el monolito modular.
- Clases compartidas de entrada, validación, dinero, fechas, IDs, auditoría e imágenes.
- CRUD de premios con foto, tipo de premio y valor estimado.
- CRUD de métodos de pago con billetera/banco, QR y contacto para sobrepagos.
- CRUD de sorteos con premios, métodos de pago, fechas, bases versionadas y publicación con checklist legal.
- Validaciones conectadas a los tipos y reglas de cada tabla.

**No se publicó ningún sorteo real, no se cobró dinero y no se crearon participantes/pagos reales.** Las pruebas de publicación se hicieron con un sorteo descartable, eliminado al finalizar.

---

## 2. Archivos creados

```text
app/database/migrations/
  20261116120000_create_mvp_schema.sql
tools/
  migrate.php
  seed.php
```

### 2.1 Migración

Crea:

- `prizes`
- `raffles`
- `raffle_rules_versions`
- `raffle_prizes`
- `payment_methods`
- `raffle_payment_methods`
- `participants`
- `orders`
- `tickets`
- `raffle_numbers`
- `payment_proofs`
- `payments`
- `draws`
- `draw_results`
- `deliveries`
- `delivery_evidence`
- `legal_acceptances`
- `audit_logs`
- `notification_logs`
- `schema_migrations` (creada por el migrador)

No modifica `users` ni `settings`; los usa como base.

### 2.2 Migrador

`tools/migrate.php`:

- Solo CLI.
- Verifica conexión.
- Aplica `app/database/schema.sql` si no existe `users`.
- Crea `schema_migrations`.
- Aplica migraciones pendientes en orden.
- Registra cada migración aplicada.
- No vuelve a aplicar una migración registrada.

### 2.3 Seeder

`tools/seed.php`:

- Solo CLI.
- Se ejecuta en modo debug; `--force` solo para casos controlados.
- Es idempotente: no duplica premios, métodos, sorteo ni bases.
- Crea datos ficticios:
  - 3 premios de producto.
  - 3 métodos de pago: Yape, Plin y BCP demo.
  - 1 sorteo demo en estado `draft`.
  - 1 versión de bases demo.
  - Asignación de premios y métodos al sorteo.
  - Settings demo: `demo_seed`, `demo_seeded_at`, `support_phone`, `payment_contact_phone`.

### 2.4 Autoload y clases compartidas

`app/bootstrap.php` registra un autoload manual para el prefijo `App\`:

- Convierte cada segmento PascalCase del namespace a carpeta snake_case.
- `App\Modules\Prizes\PrizeService` → `app/modules/prizes/PrizeService.php`.
- `App\Modules\PaymentMethods\PaymentMethodService` → `app/modules/payment_methods/PaymentMethodService.php`.
- `App\Shared\Audit\AuditService` → `app/shared/audit/AuditService.php`.

Clases compartidas añadidas:

| Clase | Ruta | Uso |
| --- | --- | --- |
| `Input` | `app/shared/http/Input.php` | Lectura segura de `$_POST`, `$_GET`, arrays y archivos. |
| `Validator` | `app/shared/validation/Validator.php` | Requeridos, longitudes, enteros, decimales, enums y fechas. |
| `Money` | `app/shared/Money.php` | Conversión a centavos y formato `S/`. |
| `Dates` | `app/shared/Dates.php` | Fechas `America/Lima` para `datetime-local` y vistas. |
| `Ids` | `app/shared/Ids.php` | Códigos públicos y slugs únicos. |
| `DomainException` | `app/shared/exceptions/DomainException.php` | Errores de regla de negocio. |
| `AuditService` | `app/shared/audit/AuditService.php` | Bitácora encadenada por hash. |
| `ImageStorage` | `app/shared/storage/ImageStorage.php` | Validación y guardado de JPG/PNG/WebP en `public_html/uploads`. |

### 2.5 Migración de confirmación legal

`app/database/migrations/20261116130000_add_legal_confirmation_to_raffles.sql` añade a `raffles`:

- `legal_checklist` (JSON) con los cinco puntos confirmados.
- `legal_confirmed_at`.
- `legal_confirmed_by` con FK a `users`.

Permite auditar quién y cuándo confirmó el checklist antes de publicar.

### 2.6 CRUDs de administración

Se creó un despachador de rutas para módulos (`app/admin/module_routes.php`) y fábricas de servicios (`app/admin/services.php`). El router del panel resuelve primero las rutas nuevas y luego las existentes de usuarios.

| Módulo | Rutas | Archivos principales |
| --- | --- | --- |
| Premios | `/admin/prizes`, `/admin/prizes/create`, `/admin/prizes/{id}/edit`, `toggle`, `delete` | `app/modules/prizes/` |
| Métodos de pago | `/admin/payment-methods`, `/admin/payment-methods/create`, `/admin/payment-methods/{id}/edit`, `toggle`, `delete` | `app/modules/payment_methods/` |
| Sorteos | `/admin/raffles`, `/admin/raffles/create`, `/admin/raffles/{id}/edit`, `publish`, `close`, `cancel`, `delete` | `app/modules/raffles/` |

Cada módulo separa repositorio (SQL), servicio (reglas + auditoría + transacciones) y handler (HTTP).

Reglas de integridad conectadas a los formularios:

- **Premios:** nombre obligatorio (2–160), tipo `product`/`service`/`cash`/`other`, valor no negativo, entrega ≤ 60, descripción ≤ 5000, imagen ≤ 5 MB JPG/PNG/WebP. No se elimina un premio asignado a un sorteo.
- **Métodos de pago:** nombre ≤ 120; tipo `wallet`/`bank`; proveedor `yape`/`plin`/`bcp`/`other`; titular obligatorio; teléfono obligatorio en billetera; banco y cuenta obligatorios en banco; CCI ≤ 40; contacto para sobrepagos obligatorio; instrucciones ≤ 2000; QR opcional. No se elimina un método asignado o con pagos registrados.
- **Sorteos:** nombre 3–160; slug único ≤ 120; resumen ≤ 255; descripción ≤ 20000; precio > 0; cupos 1–1 000 000; máximo por persona entre 1 y los cupos; edad 18–100; fechas en orden (inicio < cierre ≤ sorteo < reclamo); al menos un premio activo y un método activo; bases 20–50 000 caracteres; orden destacado 1–3 y único entre destacados; solo se edita/elimina en `draft`.
- Todos los cambios relevantes se registran en `audit_logs` con transacción.
- Las imágenes se guardan con nombre aleatorio en `uploads/{prizes,payment-methods,raffles}` y se eliminan al reemplazar o borrar el registro.

### 2.7 Experiencia de formularios y listados

- Formularios en dos columnas (contenido + panel lateral fijo) que aprovechan el ancho del panel y colapsan a una columna en pantallas angostas.
- Secciones con encabezado, descripción y campos agrupados por tema.
- Resumen de errores al inicio del formulario con enlaces `#campo` y foco automático; los errores también se muestran debajo de cada campo con `aria-invalid` y `aria-describedby`.
- Vista previa de imagen antes de subir, generación de slug desde el nombre, sincronización del premio principal y campos condicionales según el tipo de pago.
- Diálogo de confirmación reutilizable para acciones destructivas y zona de cancelación con motivo obligatorio.
- Tablas compactas: columnas fusionadas (valor/entrega, cupos/límite, premios/métodos) para evitar scroll horizontal; acciones frecuentes primero y cancelación solo en la ficha del sorteo.
- Tema claro/oscuro con tokens; sin estilos en línea.

### 2.8 Validación de escritura y tipos de dato

Se añadió una capa de reglas compartidas (`app/shared/validation/FieldRules.php`) que define el patrón, el mensaje, el modo de limpieza y el `inputmode` de cada tipo de campo. El formulario renderiza esos datos con `field_attrs()` y JavaScript usa exactamente los mismos patrones; el backend normaliza y vuelve a validar antes de guardar.

| Campo | Regla | Cliente | Servidor |
| --- | --- | --- | --- |
| Teléfono (`phone`, `contact_phone`) | 9 dígitos, empieza por 9 | `type=tel`, solo dígitos | quita separadores y valida `9[0-9]{8}` |
| Documento del titular | DNI 8 o RUC 11 | solo dígitos, máximo 11 | `(?:[0-9]{8}|[0-9]{11})` |
| Cuenta / tarjeta | 8 a 20 dígitos | solo dígitos | `[0-9]{8,20}` |
| CCI | exactamente 20 dígitos | solo dígitos | `[0-9]{20}` |
| Titular | letras, espacios, apóstrofo, punto y guion; al menos 2 letras | filtra dígitos y símbolos | patrón `person_name` + conteo de letras |
| Nombres visibles (premio, sorteo, método, banco, entrega) | letras, números y signos básicos; al menos 1 letra | filtra caracteres no permitidos | patrón `display_name` + conteo de letras |
| Slug | minúsculas, números y guiones simples | normaliza mientras se escribe | `Ids::slug` + patrón `slug` |
| Precio y valor estimado | hasta 2 decimales; precio mayor a cero | `inputmode=decimal`, recorta a 2 decimales | normaliza `S/`, miles y coma decimal; patrón `decimal` |
| Enteros (cupos, máximo por persona, edad, orden) | solo dígitos y rangos | filtra a dígitos, respeta `min`/`max` | patrón `integer` + rangos |
| Fechas | `datetime-local`, años 2020–2100 y orden cronológico | `required` | `Dates::fromInput` + rango + orden |
| Descripciones y bases | texto libre sin caracteres de control | `maxlength` | `normalizeText` + longitudes |
| Usuario del panel | 3–50 con letras, números, punto, guion y guion bajo | filtra al escribir | regex existente |
| Imágenes | JPG/PNG/WebP hasta 5 MB | `accept` | `getimagesize`, MIME y tamaño |

Comportamiento en el cliente:

- Los campos numéricos y de teléfono descartan caracteres no válidos al escribir o pegar.
- Los nombres de persona descartan dígitos y símbolos; los nombres visibles conservan letras, números y signos básicos.
- Al salir del campo o enviar, se muestra el mensaje bajo el campo (`field-error`, `aria-invalid`).
- Si hay varios errores, se genera un resumen con enlaces a cada campo y se enfoca automáticamente.
- El backend repite la normalización y la validación: nunca confía en el navegador.

### 2.9 Portada pública y página de sorteos

- La portada de la radio se reorganizó en un hero de dos columnas: presentación de la emisora a la izquierda y reproductor en vivo a la derecha.
- Se añadió un botón **Ver sorteos** debajo del reproductor, con enlace a la ruta `/sorteos`.
- El CSS del reproductor y del hero se movió a `public_html/css/radio-landing.css`; ya no hay estilos inline para el diseño.
- La página `/sorteos` emula la plantilla `public_html/Main.dc.html`: barra superior, navegación con CTA, hero oscuro con premio principal y cuenta regresiva, tres tarjetas de premios, pasos de participación, formulario de registro, preguntas frecuentes y pie.
- Paleta exacta de la plantilla: `--ink #17243A`, `--mut #4F5B6E`, `--bg #F6F3EC`, `--card #FFFFFF`, `--line #E2DCCF`, `--accent #B4472B`, `--gold #C9A24B`; tipografías Inter y Space Grotesk.
- Código separado por responsabilidad: `app/templates/pages/sorteos.html`, `public_html/css/sorteos.css` y `public_html/js/sorteos.js`.
- La cuenta regresiva se calcula desde `data-deadline` y el formulario valida nombre, correo, teléfono y aceptación; al enviarse muestra el aviso de registro próximamente.
- La página queda `noindex` hasta que el sorteo real esté publicado; el service worker sube a `radio-poderosa-v14` e incluye la hoja y el script de sorteos.
- En escritorio, la tarjeta del hero se expande 500 px en total hacia los lados y 30 % de su altura base hacia arriba y abajo (520 → 832 px), con límite al ancho visible para evitar scroll horizontal.
- La tarjeta funciona como **carrusel de 3 imágenes** con fundido, avance automático cada 5.5 s, flechas, indicadores, pausa/reanudar, swipe táctil, teclado y pausa al pasar el cursor o enfocar. Respeta `prefers-reduced-motion`.
- El carrusel toma la portada del sorteo o, si no existe, la imagen del premio principal; cuando no hay imagen usa un degradado de marca (sin plantillas). El comportamiento vive en `public_html/js/sorteos-carousel.js`.
- En **Premios del sorteo**, las tarjetas se amplían hasta ocupar más ancho (sección de hasta 1440 px), la imagen crece a 270 px de alto (210 px en móvil) y la viñeta de puesto y el bloque de texto se reducen (badge de 11 px, título de 20 px y descripción de 14 px).
- Se corrigieron encabezados y enlaces sociales del pie para que la portada obtenga 100 en accesibilidad.

### 2.10 Almacenamiento de imágenes en Cloudinary

- Se investigó el proyecto **Carwash** (`C:\Users\USUARIO\Desktop\carwash_sistema`): Laravel 12 con `cloudinary-labs/cloudinary-laravel`, credencial `CLOUDINARY_URL` (`cloudinary://api_key:api_secret@cloud_name`), subida con `Cloudinary::uploadApi()->upload(...)`, `secure_url` guardada en la entidad, borrado por `public_id` y fallback a `storage/app/public`.
- Este proyecto no usa Composer ni Laravel: se implementó `App\Shared\Storage\CloudinaryClient` con cURL y firmas SHA-1, y `ImageStorage` ahora soporta los drivers `local` y `cloudinary` mediante `config('media.*')`.
- La configuración local vive en `app/inc/config.local.php` (fuera de `public_html`): driver `cloudinary`, URL copiada desde el `.env` de Carwash y carpeta base `radiolpsorteos`. El secreto no se imprime ni se documenta; en producción se puede usar `CLOUDINARY_URL`/`MEDIA_DRIVER` por entorno.
- Carpetas: `radiolpsorteos/prizes`, `radiolpsorteos/payment-methods` y `radiolpsorteos/raffles`. `image_path` guarda la `secure_url` (108 caracteres en la prueba) y el `public_id` se deriva de la URL para el borrado.
- Se conserva la validación JPG/PNG/WebP hasta 10 MB (`getimagesize`, MIME y tamaño), reforzada en el cliente antes de enviar. El almacenamiento local sigue disponible como fallback.
- Flujo: subir fuera de la transacción, guardar la URL en BD y borrar la imagen anterior al reemplazar o eliminar. Si Cloudinary falla, el formulario muestra el error y no se guarda la entidad.
- Verificación: subida y borrado directo contra la API; alta, edición y baja desde el panel con URL de Cloudinary; imagen pública accesible; una segunda destrucción devuelve `result: not found`. El CDN puede seguir sirviendo la URL cacheada unos minutos tras el borrado, aunque el asset ya no exista en la cuenta.
- Pendiente: migrar imágenes locales existentes (si aparecen) y valorar transformaciones `q_auto/f_auto` para optimizar la entrega.

### 2.11 Web pública conectada a los sorteos

- `/sorteos` dejó de ser una plantilla estática: ahora se renderiza desde `app/web/sorteos.php` con la vista `app/web/views/sorteos.php`, usando `RaffleRepository`.
- Sorteos visibles: `published`, `paused`, `sales_closed`, `drawing`, `drawn` y `results_published`. Si no hay ninguno y `public.preview_drafts` está activo (true en local, false por defecto en producción), se muestra el **borrador más reciente como vista previa** (`noindex`). Si tampoco hay borrador, se muestra un estado "Muy pronto".
- El **carrusel** tiene una diapositiva por sorteo con su portada o la primera imagen de premio disponible; si no hay imagen usa un degradado de marca. Muestra nombre, resumen, precio por número, cupos, máximo por persona, fechas y estado.
- La **cuenta regresiva** apunta al siguiente hito del sorteo activo (apertura, cierre o fecha del sorteo) y se actualiza al cambiar de diapositiva.
- La sección **Premios de nuestros sorteos** muestra una tarjeta por cada sorteo activo (`published`, `paused`, `sales_closed`), generada desde la base de datos: portada o imagen principal, estado, nombre, resumen, premio principal, premios adicionales, precio, máximo por persona, fechas y CTA. Con un solo sorteo activo se muestra una tarjeta (hoy Maicol); al publicar más, aparecen automáticamente. El carrusel sigue actualizando la cuenta regresiva y los datos del registro. El service worker sube a `radio-poderosa-v18` y el CSS a `v=20261116-12`. La card mide 494 px de ancho (30 % más), la imagen 340 px de alto y el bloque de texto usa 13 px con 26 px de padding lateral.
- Cada tarjeta de premio usa la imagen del premio (Cloudinary o local) si existe; si no, un icono de regalo. Muestra puesto, nombre, descripción y valor referencial.
- El título, la descripción, `og:image` y `robots` se generan desde el sorteo principal: vista previa/estado vacío quedan `noindex`; los sorteos publicados son indexables.
- Se eliminó la plantilla estática `app/templates/pages/sorteos.html` y las imágenes de marcador `public_html/images/sorteos/*.svg`.
- Pruebas: vista previa del borrador demo, dos sorteos públicos de prueba con carrusel/paneles sincronizados, cambio de precio/máximo/cuenta regresiva, estado vacío y Lighthouse 100. El demo quedó restaurado en `draft` y los datos de prueba se eliminaron.
- Diagnóstico de imágenes: si una foto no aparece, comprobar `prizes.image_path` o `raffles.cover_image_path`; la vista previa del formulario es local y no confirma el guardado. El hero usa la portada o la primera imagen de premio disponible. Los errores de consola del tipo "message channel closed" provienen de extensiones del navegador, no de la página.
- Los sorteos publicados son de solo lectura, por lo que se añadió un **formulario independiente de portada** (`POST /admin/raffles/{id}/cover`) que permite subir o reemplazar la imagen sin tocar fechas, premios ni bases. Guarda la `secure_url` en `raffles.cover_image_path`, elimina la anterior y registra `raffle.cover_updated`.
- Enlaces de visualización: el hero usa `raffles.cover_image_path` (o la primera `prizes.image_path` disponible) en un `<img src>`; las tarjetas usan `prizes.image_path`. La página no consulta Cloudinary: solo muestra la URL guardada en la base de datos.

---

## 3. Datos demo

| Elemento | Datos |
| --- | --- |
| Premios | Combo desayuno, Parlante Bluetooth, Kit de cuidado personal |
| Tipo | `product` |
| Valores | S/ 45.00, S/ 120.00, S/ 80.00 (referenciales) |
| Yape | Teléfono `999999999`, titular `DEMO - Radio La Poderosa` |
| Plin | Teléfono `999999999`, titular `DEMO - Radio La Poderosa` |
| BCP | Cuenta/CCI `000...`, titular `DEMO - Radio La Poderosa` |
| Sorteo | `sorteo-demo-lanzamiento` |
| Estado | `draft` |
| Precio | S/ 5.00 |
| Cupos | 100 |
| Máximo por persona | 5 |
| Fechas | Inicio hoy, cierre +30 días, sorteo +31 días, reclamo +38 días |

Todos los datos son ficticios y evidentemente demo. No deben usarse en producción.

---

## 4. Cómo ejecutar

Desde la raíz del proyecto:

```powershell
php tools/migrate.php
php tools/seed.php
```

Si la base no existe o no tiene `users`/`settings`, el migrador aplica primero `app/database/schema.sql`.

Para volver a ejecutar el seeder sin duplicar:

```powershell
php tools/seed.php
```

No se recomienda `--force` en producción.

---

## 5. Verificación realizada

- `php -l` sin errores en todos los archivos PHP de `app/admin`, `app/modules` y `app/shared`.
- `node --check public_html/assets/admin/admin.js` sin errores.
- 26 imports CSS verificados; `ADMIN_ASSET_VERSION = 20261116-9`.
- Migración aplicada: `20261116120000_create_mvp_schema.sql` y `20261116130000_add_legal_confirmation_to_raffles.sql`.
- Segunda ejecución del migrador: `[skip]`, sin duplicar.
- Seeder ejecutado dos veces: segunda ejecución con `[skip]` para premios, métodos, sorteo y bases.
- Tablas verificadas en MySQL/MariaDB.
- Claves foráneas verificadas en `raffles` y `raffle_numbers`.
- Backup previo: `app/storage/backups/radiolpsorteos_before_mvp_20261116.sql`.
- Pruebas de reglas con `php tools/tests/field_rules.php`: 41 comprobaciones correctas (teléfono, documento, cuenta, CCI, nombres, slug, decimales, enteros, usuario y texto).
- Prueba HTTP automatizada en local (`php -S 127.0.0.1:8123`): 55 comprobaciones del CRUD y 23 de validación de escritura (rechazos por letras, símbolos, longitudes y rangos; normalización de teléfonos, cuentas, DNI, precios y slugs).
- Verificado que el sorteo demo permanece en `draft`.
- Verificado en navegador: saneamiento al escribir, resumen de errores con foco, enlaces a campos, bloqueo de envíos inválidos, generación de slug con tildes, campos condicionales, vista previa de imagen, tema oscuro y ausencia de errores de consola.
- Lighthouse: accesibilidad, buenas prácticas y SEO en 100 en las pantallas nuevas, incluidos los estados con errores de servidor y de cliente; sin desbordamiento horizontal en tablas a 1296 px.

---

## 6. Pendiente de Fase 1

- Página pública en modo borrador (listado y ficha del sorteo).
- Módulo de participantes: alta invitada, DNI/celular/correo y aceptación legal.
- Reserva de números (30 minutos) y emisión de tickets.
- Carga y revisión manual de comprobantes de pago.
- Motor de sorteo commit-reveal y publicación de resultados.
- Entrega de premios con evidencia.
- Pruebas de asignación de números y concurrencia.
- Configuración de `fileinfo`, cron y backups.

---

## 7. Advertencias

- El sorteo demo está en `draft`; no debe publicarse.
- Los métodos de pago son ficticios; no usar en producción.
- Las bases demo no son un documento legal.
- No se crearon participantes, órdenes, pagos ni tickets reales.
- La validación legal del cobro sigue pendiente.
- El checklist legal de publicación solo registra la confirmación del administrador; no sustituye la revisión de un abogado.
- El método de pago `INTERBANK` existente en la base local tiene datos que no cumplen las reglas nuevas (documento de 9 dígitos y cuenta/CCI con letras); al editarlo habrá que corregirlos.

---

## 8. Control del documento

| Versión | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-11-16 | Esquema inicial, migrador y seeder demo. |
| 1.1 | 2026-11-16 | Autoload, clases compartidas, migración legal y CRUD de premios, métodos de pago y sorteos. |
| 1.2 | 2026-11-16 | Validación de escritura y tipos de dato en cliente y backend, normalización y pruebas. |
| 1.3 | 2026-11-16 | Portada pública en dos columnas, CTA a sorteos y página placeholder `/sorteos`. |
| 1.4 | 2026-11-16 | Página `/sorteos` emulando `Main.dc.html`, con HTML/CSS/JS modulares y paleta de la plantilla. |
| 1.5 | 2026-11-16 | Tarjeta del hero ampliada +500 px a lo ancho y 30 % de altura arriba/abajo en escritorio. |
| 1.6 | 2026-11-16 | Carrusel de 3 imágenes en la tarjeta del hero, con controles, autoplay y soporte táctil/teclado. |
| 1.7 | 2026-11-16 | Tarjetas de premios más amplias, imagen protagonista y viñeta/texto reducidos. |
| 1.8 | 2026-11-16 | Imágenes de premios, QR y portadas almacenadas en Cloudinary con fallback local. |
| 1.9 | 2026-11-16 | `/sorteos` conectada a los sorteos y premios reales, con carrusel/paneles sincronizados y vista previa de borradores. |
| 1.10 | 2026-11-16 | Validación de imágenes en cliente/servidor (10 MB) y portada con cualquier imagen de premio disponible. |
| 1.11 | 2026-11-16 | Formulario independiente para actualizar la portada de sorteos publicados y aclaración de los campos de imagen. |
| 1.12 | 2026-11-16 | Vista previa de borradores limitada a desarrollo mediante `public.preview_drafts`. |
