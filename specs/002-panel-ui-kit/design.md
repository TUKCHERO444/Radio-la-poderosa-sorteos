# 002 — UI kit nativo del panel administrativo

- **Estado:** implementado
- **Fecha:** 2026-11-15
- **Base:** `specs/guia-estilo-ui-ux.md` (guía del panel Carwash)
- **Alcance:** sistema visual reutilizable para el panel de sorteos.

## 1. Objetivo

Adaptar los patrones de la guía UI/UX al panel actual de Radio La Poderosa y
dejarlos como **template** para las próximas fases (sorteos, productos, imágenes
y configuración). El resultado debe ser coherente, accesible, responsive y fácil
de extender sin depender de un framework.

## 2. Decisiones

- **CSS nativo**, sin Tailwind, Bootstrap, preprocesadores ni CSS-in-JS.
- **Variables CSS nativas** como única fuente de verdad. No se usan tokens de
  un framework.
- **Cero estilos inline** en las vistas. Las páginas de sistema (base de datos
  no disponible y sesión expirada) también usan el CSS del panel.
- **CSS modular** por responsabilidad. `admin.css` es solo el manifiesto de
  imports.
- **Modo claro/oscuro** con `data-theme` en `<html>` y persistencia en
  `localStorage`. El tema claro es el predeterminado (estilo AdminLTE); el
  usuario puede cambiarlo desde el botón de la topbar.
- **Tipografía del sistema** (Inter si está instalada; Segoe UI / system-ui como
  respaldo). Sin fuentes externas ni bloqueo de render.
- **Iconografía SVG inline** coherente (trazo 1.8–2, `currentColor`,
  `aria-hidden="true"` en decorativos).
- **Diálogo de confirmación nativo** con `<dialog>` en lugar de
  `window.confirm`.
- **Paleta formal azul/clara estilo AdminLTE:** sidebar claro
  (`#f7faff` → `#eef5fd`), texto `#475569`, borde `#dbe7f5`, activo
  `#0b5ed7` con texto blanco; acento primario `#0b5ed7`, hover `#0a58ca`,
  suave `#e7f1ff`; fondo de página `#f4f6f9`; superficies blancas y estados
  semánticos. Sin dorado. En oscuro el sidebar pasa a `#0b1626` → `#0f1f33` y
  el acento de texto a `#6ea8fe`.
- **Contenido fluido:** el área de trabajo no tiene `max-width`; los listados y
  las tablas usan todo el ancho disponible. Los formularios siguen limitados
  por `.form-card` para mantener la legibilidad.
- **Componentes con clases genéricas** (`.btn`, `.card`, `.table`, `.field`,
  `.alert`, `.badge`), no atadas a una pantalla.

## 3. Estructura de archivos

```text
public_html/assets/admin/
  admin.css                     # manifiesto de @imports
  theme-init.js                 # aplica el tema antes del primer pintado
  admin.js                      # sidebar, tema, diálogo, formularios
  css/
    base/
      reset.css                 # reset y base del documento
      tokens.css                # variables claro/oscuro
      typography.css            # escala y microcopy
      utilities.css             # sr-only, skip-link, stack, grid…
    layout/
      shell.css                 # shell, contenido y overlay
      sidebar.css               # marca, navegación y pie
      topbar.css                # barra superior y menú móvil
      auth.css                  # login
    components/
      buttons.css               # botones universales
      cards.css                 # tarjetas y cabeceras de página
      stats.css                 # KPIs
      badges.css                # estados y píldoras
      tables.css                # tablas y celdas compuestas
      forms.css                 # campos, errores, ayudas, inputs
      alerts.css                # flash y avisos
      empty-state.css           # estados vacíos
      toolbar.css               # búsqueda, filtros y contador
      pagination.css            # paginación (lista para futuras fases)
      dialog.css                # confirmación nativa
      theme-toggle.css          # selector claro/oscuro
    pages/
      dashboard.css             # hero, accesos y hoja de ruta
      system.css                # páginas 419 / 500
    responsive.css              # breakpoints 1100/900/720/480
    print.css                   # impresión
```

Orden de carga: base → layout → componentes → páginas → responsive → print.
Ningún archivo de componente debe importar otro.

## 4. Clases base del template

### Botones

```html
<a class="btn btn-primary" href="…">Acción principal</a>
<button class="btn btn-ghost btn-sm" type="button">Acción secundaria</button>
<button class="btn btn-danger-soft btn-sm" type="submit">Eliminar</button>
<button class="btn btn-icon" aria-label="Cerrar">…</button>
```

Variantes: `btn-primary`, `btn-ghost`, `btn-secondary`, `btn-light`, `btn-soft`,
`btn-danger`, `btn-danger-soft`. Tamaños: `btn-sm`, `btn-lg`, `btn-icon`,
`btn-block`. Estado de carga: `.is-loading` + `disabled`.

### Tarjetas y página

```html
<section class="page-head">
    <div class="page-head-text">
        <h1>Título de la página</h1>
        <p>Descripción breve.</p>
    </div>
    <div class="page-head-actions">…</div>
</section>

<section class="card">
    <header class="card-header"><h2>Sección</h2></header>
    …
</section>

<section class="card card-flush">…tabla…</section>
```

### Tabla

```html
<section class="card card-flush">
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Descripción del listado</caption>
            <thead>
            <tr><th scope="col">Columna</th></tr>
            </thead>
            <tbody>
            <tr><td data-label="Columna">Dato</td></tr>
            </tbody>
        </table>
    </div>
</section>
```

- `data-label` es obligatorio en cada `td` para la vista móvil en tarjetas.
- Acciones con texto + icono (`.btn btn-ghost btn-sm`,
  `.btn btn-danger-soft btn-sm`).
- El wrapper `.table-wrap` conserva el scroll horizontal cuando hace falta,
  pero la tabla usa todo el ancho disponible: `.app-content` no tiene
  `max-width` y el listado fluye hasta el borde del área de trabajo.

### Formularios

```html
<div class="field">
    <label for="campo">Campo <span class="required">*</span></label>
    <input id="campo" class="input" aria-describedby="campo-feedback">
    <small id="campo-feedback" class="field-hint">Ayuda del campo.</small>
</div>
```

- Error: `.field.has-error`, `.field-error`, `aria-invalid="true"` y
  `aria-describedby` apuntando al mensaje.
- Acciones: `.form-actions` con botón primario + enlace/botón neutro.
- El formulario de alta usa `data-loading` para mostrar el spinner y desactivar
  el envío.

### Alertas, badges y estados vacíos

```html
<div class="alert alert-success" role="status">Guardado.</div>
<div class="alert alert-error" role="alert">Revisa los campos.</div>
<span class="badge badge-primary">Administrador</span>
<span class="badge badge-slate">Editor</span>

<section class="empty">
    <span class="empty-icon" aria-hidden="true">…</span>
    <h2>Sin resultados</h2>
    <p>Texto de apoyo.</p>
    <div class="empty-actions">…</div>
</section>
```

### Confirmación nativa

El layout incluye `<dialog id="confirmDialog">`. Cualquier formulario con
`data-confirm` se intercepta y muestra el diálogo:

```html
<form method="post" action="…"
      data-confirm="¿Eliminar el registro?"
      data-confirm-title="Eliminar registro"
      data-confirm-label="Eliminar">
```

Atributos opcionales: `data-confirm-title` y `data-confirm-label`. Si el
navegador no soporta `<dialog>`, se usa `window.confirm` como respaldo.

### Tema

`theme-init.js` aplica `data-theme="light|dark"` antes del CSS. El botón
`[data-theme-toggle]` alterna y persiste en `localStorage` con la clave
`radiolp-admin-theme`. Si no hay preferencia guardada, el tema predeterminado
es el claro.

## 5. Reglas de mantenimiento

1. Los colores semánticos (texto, superficies, bordes y estados) viven en
   `tokens.css`. Los degradados, sombras y tintes de marca pueden usar
   `rgba()` derivados del azul de marca dentro del componente; no introduzcas
   colores de estado nuevos ni hex sueltos para texto o fondos.
2. No usar `style=""` en PHP ni en HTML. Si hace falta un ajuste, se crea una
   clase en el archivo del componente o de la página.
3. No duplicar patrones: si una pantalla nueva necesita un listado, reutiliza
   `.page-head` + `.toolbar` + `.card-flush` + `.table`.
4. Cada `td` de una tabla responsive lleva `data-label`.
5. Todo botón de solo icono necesita `aria-label` descriptivo.
6. Los errores se muestran junto al campo y también en el resumen superior.
7. Un solo `h1` por vista. La topbar es contexto, no el título principal.
8. Los iconos decorativos llevan `aria-hidden="true"` y `focusable="false"`.

## 6. Caché de assets y service worker

El sitio público registra `public_html/sw.js` con alcance `/`. Por eso el panel
queda cubierto por el service worker aunque no lo registre. Para evitar que el
panel sirva CSS/JS antiguos:

- El fetch del service worker **excluye** `/admin` y `/assets/admin/`; esas
  rutas siempre van a la red.
- La caché pública usa una versión explícita (`radio-poderosa-v2`). Al cambiar
  la versión, `activate` elimina las cachés anteriores.
- Los assets del panel se referencian con `admin_asset()` y la constante
  `ADMIN_ASSET_VERSION` de `app/admin/helpers.php`. Al desplegar cambios de
  CSS/JS hay que incrementar esa constante para forzar una URL nueva.
- Si se agregan o renombran archivos CSS, actualizar también los `@import` de
  `assets/admin/admin.css`.

## 7. Cómo agregar un módulo nuevo (sorteos, productos)

1. Crear la vista en `app/admin/views/<modulo>/`.
2. Reutilizar `page-head`, `toolbar`, `card`, `table`, `badge`, `empty`.
3. Duplicar el patrón de `users/index.php` para el listado y
   `users/form.php` para el formulario.
4. Añadir una entrada real al sidebar en `layout.php` y marcar
   `aria-current="page"` según `$active`.
5. No crear CSS nuevo salvo que el módulo tenga una pieza realmente distinta;
   en ese caso, crear `css/pages/<modulo>.css` y agregarlo al manifiesto.
6. Verificar con Lighthouse (accesibilidad), a 375 px y con teclado.

## 8. Verificación realizada

- `php -l` sin errores en router, helpers, repositorio, layout y vistas.
- Login, dashboard, listado, búsqueda, alta con validación y cierre de sesión
  probados en el servidor PHP local.
- Diálogo de confirmación: apertura, foco inicial en “Cancelar”, cierre y
  restauración de foco.
- Búsqueda de usuarios con y sin resultados.
- Revisión responsive en iframes de 375 px y 768 px: sin overflow horizontal,
  sidebar colapsable, tablas en tarjetas, formulario a una columna.
- Verificación de ancho en iframe de 1800 px: el contenido ocupa 1528 px, la
  tarjeta 1464 px y la tabla 1462 px, sin scroll horizontal.
- Lighthouse: accesibilidad 100, buenas prácticas 100 y SEO 100 en login,
  dashboard, listado y formulario, en tema claro y oscuro.
