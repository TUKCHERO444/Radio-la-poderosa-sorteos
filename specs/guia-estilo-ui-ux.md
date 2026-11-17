# Guía de estilo UI/UX — Sistema interno de gestión (Carwash)

Documento de referencia para replicar el lenguaje visual del panel interno en otro sistema.
Está basado en la implementación real del proyecto (Blade + Tailwind CSS 4), no en una propuesta
teórica. Al final incluye una sección de portabilidad para llevar estos patrones a cualquier stack.

---

## 1. Principios de diseño

1. **Anti-fatiga:** el modo oscuro no usa negro puro; trabaja con tonos Slate (`#020617`, `#0f172a`,
   `#1e293b`). El modo claro usa grises suaves (`gray-50` de fondo, blanco en superficies).
2. **Densidad operativa:** pantallas de trabajo (ventas, caja, listados) con datos compactos pero
   táctiles: celdas de tabla con `py-8`, cabeceras con `py-6`.
3. **Consistencia semántica:** los colores no se eligen por gusto; cada color comunica estado
   (azul = acción principal, verde = éxito/activo, ámbar = advertencia, rojo = error/eliminar).
4. **Un solo patrón por tarea:** todo listado se ve igual, todo formulario se ve igual, todo modal
   se ve igual. El usuario reconoce la interfaz sin reaprender.
5. **Jerarquía discreta:** títulos de 24 px, datos de 14 px, metadatos de 12 px. Sin tipografías
   decorativas ni sombras fuertes; el contraste y el espaciado hacen el trabajo.
6. **Feedback inmediato:** transiciones de color cortas, spinner/disabled en acciones asíncronas,
   modales de confirmación y mensajes flash visibles.

---

## 2. Stack visual

| Elemento | Implementación |
| :--- | :--- |
| Framework CSS | Tailwind CSS 4 (utilidades + `@theme` + `@layer components`) |
| Tipografía base | `Instrument Sans`, con fallback a `ui-sans-serif, system-ui, sans-serif` |
| Iconografía | SVG inline estilo Heroicons outline, `stroke-width="2"`, `viewBox="0 0 24 24"` |
| Modo oscuro | Variante por clase `.dark` en `<html>` (`@custom-variant dark`) |
| Tema persistente | `localStorage.theme` + preferencia del sistema (`prefers-color-scheme`) |
| Alertas | SweetAlert2 para confirmaciones destructivas |
| Gráficos | Chart.js (solo dashboard) |

No hay CSS-in-JS ni framework de componentes. Los patrones viven en clases de utilidad y en un
pequeño set de clases semánticas (`bg-surface`, `input-main`, `label-main`, etc.).

---

## 3. Tipografía y formatos de texto

### 3.1 Escala tipográfica

| Rol | Clases | Tamaño / peso | Uso |
| :--- | :--- | :--- | :--- |
| Título de página (H1) | `text-2xl font-semibold text-gray-800 dark:text-text-primary-dark` | 24 px / 600 | Una sola vez por vista, arriba a la izquierda |
| Título de sección / tarjeta (H2) | `text-base font-semibold text-primary` | 16 px / 600 | Bloques del dashboard y secciones internas |
| Cifra KPI | `text-2xl font-bold text-primary` | 24 px / 700 | Tarjetas de métricas; variante `text-xl font-semibold` para desgloses |
| Texto de cuerpo / datos | `text-sm` | 14 px / 400 | Celdas de tabla, párrafos, campos |
| Texto de apoyo | `text-xs text-secondary` | 12 px / 400 | Ayudas bajo inputs, subtítulos, notas |
| Etiqueta de campo | `label-main` (`text-sm font-medium`) | 14 px / 500 | `<label>` de formularios |
| Cabecera de tabla | `text-xs font-medium uppercase tracking-wider` | 12 px / 500, mayúsculas | `<th>` de listados |
| Micro-etiqueta / badge | `text-[10px] font-semibold uppercase tracking-wide` | 10 px / 600 | Estados compactos (stock, “Incoherente”) |
| Badge normal | `text-xs font-medium` | 12 px / 500 | Estados y categorías en píldora |
| Nombre de marca (sidebar) | `text-lg font-semibold text-white` | 18 px / 600 | Cabecera del menú lateral |

Regla: **no usar `font-bold` en texto corrido**. Negrita solo en cifras KPI, títulos y énfasis
puntual (`font-medium` / `font-semibold`).

### 3.2 Formatos de texto y microcopy

- **Idioma:** todo en español, tono directo e impersonal (“Crear cliente”, “No hay productos
  registrados.”).
- **Capitalización:** títulos y botones en *sentence case* (“Nueva venta”, “Gestión de productos”).
  Las mayúsculas se reservan para cabeceras de tabla y badges pequeños, vía CSS (`uppercase`),
  nunca escribiéndolas en el HTML.
- **Obligatoriedad:** asterisco rojo junto a la etiqueta: `DNI <span class="text-red-500">*</span>`.
- **Opcionalidad:** texto pequeño y atenuado al lado de la etiqueta:
  `<span class="text-text-secondary-dark text-xs font-normal">(Opcional)</span>`.
- **Ayuda de campo:** una línea debajo del input, `mt-1 text-xs text-secondary`, explicando formato:
  “Exactamente 8 dígitos, solo números.”, “Solo letras. Máximo 50 caracteres.”
- **Error de campo:** `mt-1 text-xs text-red-600 dark:text-red-400` (o `text-sm` en formularios
  de autenticación), junto con borde rojo en el input.
- **Valores vacíos:** guion largo `—` (nunca “N/A” ni celda en blanco).
- **Moneda:** prefijo `S/ ` y dos decimales: `S/ 1,234.56` (`number_format($v, 2)`).
- **Fechas y horas:** generadas con `now()`/Carbon en zona `America/Lima`; no usar `date()` crudo.
- **Cargas:** texto con puntos suspensivos tipográficos: “Cargando resumen…”, “Buscar por nombre…”.
- **Placeholders:** ejemplos reales, no instrucciones: `12345678`, `987654321`, `Ej: 50`.
- **Botones:** verbo en infinitivo (“Guardar”, “Crear producto”, “Volver”, “Confirmar”, “Cancelar”).
- **Enlaces:** azul y con subrayado solo al hover: `text-sm text-blue-600 dark:text-blue-400 hover:underline`.
- **Aria-label:** obligatorio en botones que solo muestran icono, y descriptivo del registro
  (“Editar cliente Juan Pérez”, “Eliminar producto Aceite 20W50”).

### 3.3 Longitud y legibilidad

- Formularios de creación/edición limitados a `max-w-lg` para evitar líneas largas.
- Textos secundarios y columnas descriptivas se truncan con `truncate` cuando compiten por espacio.
- Etiquetas de acción cortas (1–2 palabras); el detalle va en el `aria-label` o en un tooltip (`title`).

---

## 4. Color y tokens

### 4.1 Tokens del panel (definidos en `resources/css/app.css`)

| Token | Valor | Uso |
| :--- | :--- | :--- |
| `--color-background-dark` | `#020617` (slate-950) | Fondo del `body` en oscuro |
| `--color-surface-dark` | `#0f172a` (slate-900) | Tarjetas, tablas, modales |
| `--color-border-dark` | `#1e293b` (slate-800) | Bordes y divisiones |
| `--color-text-primary-dark` | `#e2e8f0` (slate-200) | Títulos y datos principales |
| `--color-text-secondary-dark` | `#94a3b8` (slate-400) | Etiquetas, ayudas, texto secundario |

### 4.2 Equivalencias claro / oscuro

| Rol | Claro | Oscuro |
| :--- | :--- | :--- |
| Fondo de página | `bg-gray-50` (`#f9fafb`) | `bg-background-dark` (`#020617`) |
| Superficie (tarjeta/tabla) | `bg-white` | `bg-surface-dark` (`#0f172a`) |
| Borde | `border-gray-200` | `border-border-dark` (`#1e293b`) |
| Texto principal | `text-gray-900` / `text-gray-800` | `text-text-primary-dark` (`#e2e8f0`) |
| Texto secundario | `text-gray-600` / `text-gray-500` | `text-text-secondary-dark` (`#94a3b8`) |
| Cabecera de tabla | `bg-gray-50` | `bg-slate-800/50` |
| Hover de fila | `bg-gray-50` | `bg-slate-800/50` |
| Fondo de input | `bg-white` | `bg-slate-800` |
| Sidebar | `bg-gray-900` | `bg-gray-900` (siempre oscuro) |

### 4.3 Colores semánticos

| Semántica | Claro | Oscuro |
| :--- | :--- | :--- |
| Acción principal | `bg-blue-600` / hover `bg-blue-700`, texto blanco | Igual |
| Foco | `ring-blue-500` | Igual |
| Éxito / activo | `bg-green-100 text-green-800 border-green-200` | `bg-green-900/30 text-green-400` |
| Error / eliminar | `bg-red-100 text-red-700 border-red-200` | `bg-red-900/30 text-red-400` |
| Advertencia | `bg-yellow-100 text-yellow-800` / `bg-amber-100` | `bg-yellow-900/30 text-yellow-400` |
| Informativo | `bg-sky-100 text-sky-700` | `bg-sky-900/30 text-sky-400` |
| Inventario / stock | `bg-teal-100 text-teal-700` | `bg-teal-900/30 text-teal-400` |
| Acento por módulo | Ventas `blue`, Lavados `emerald`, Cambio de aceite `amber` | Versiones `-400` |

Regla anti-ruido: **un solo color de acento por pantalla** además del azul de acción. Los colores
de estado se usan en badges y cifras, nunca como fondo de página.

### 4.4 Clases semánticas reutilizables

Estas clases encapsulan el par claro/oscuro y deben copiarse tal cual al nuevo sistema:

```css
.bg-surface   { @apply bg-white dark:bg-surface-dark; }
.text-primary { @apply text-gray-900 dark:text-text-primary-dark; }
.text-secondary { @apply text-gray-600 dark:text-text-secondary-dark; }
.border-main  { @apply border-gray-200 dark:border-border-dark; }
.divide-main  { @apply divide-gray-200 dark:divide-border-dark; }
.input-main   { @apply bg-white dark:bg-slate-800 text-gray-900 dark:text-text-primary-dark
                       border-gray-300 dark:border-border-dark
                       focus:ring-blue-500 focus:border-blue-500; }
.input-error  { @apply border-red-500 dark:border-red-500/50
                       bg-red-50 dark:bg-red-900/10 focus:ring-red-500 focus:border-red-500; }
.label-main   { @apply block text-sm font-medium text-gray-700 dark:text-text-secondary-dark; }
.error-message{ @apply mt-1 text-xs text-red-600 dark:text-red-400; }
```

---

## 5. Layout y navegación

### 5.1 Estructura general

```
<body class="flex h-screen overflow-hidden bg-gray-50 dark:bg-background-dark transition-colors duration-300">
  <aside class="hidden lg:flex flex-col w-64 ...">   ← sidebar fijo (≥1024 px)
  <main class="flex-1 overflow-y-auto pb-[77px] lg:pb-0"> ← contenido
  <nav class="lg:hidden fixed bottom-0 ... h-[77px]"> ← barra inferior móvil
</body>
```

- **Sidebar:** ancho `w-64` (256 px), siempre oscuro (`bg-gray-900`), borde derecho `border-gray-800`.
  Cabecera de `h-16 px-6` con nombre del sistema y botón de tema.
- **Navegación:** `px-4 py-6 space-y-1`; ítem `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium`.
  Inactivo: `text-gray-400 hover:bg-gray-800 hover:text-white`. Activo: `bg-gray-800 text-white font-semibold`.
  Submenús con chevron que rota y `ml-4`.
- **Cierre de sesión:** al pie, separado por `border-t border-gray-800`, hover
  `hover:bg-red-900/20 hover:text-red-400`.
- **Contenido:** padding uniforme `p-6`; dashboard usa `p-6 space-y-6`; `main` con scroll propio.
- **Cabecera de página:** `flex items-center justify-between mb-6` (o `flex-col sm:flex-row` cuando
  incluye buscador y botón).
- **Móvil:** barra inferior fija de 77 px, ítems `flex-col`, iconos `w-6 h-6`, texto `text-[10px]`,
  activo en `text-blue-400`, submenús como *drop-up* (`absolute bottom-[77px]`).

### 5.2 Rejillas

- KPIs: `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4`.
- Gráficos: `grid grid-cols-1 lg:grid-cols-3 gap-4` (gráfico principal `lg:col-span-2`).
- Formularios: una columna, `max-w-lg`; secciones de `mb-5` por campo.
- Listados: tabla a ancho completo, sin rejilla.

---

## 6. Componentes

### 6.1 Botones

| Variante | Clases base | Uso |
| :--- | :--- | :--- |
| Primario | `inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors` | Acción principal de la vista |
| Primario de formulario | `px-5 py-2 ... focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2` | Guardar / crear |
| Neutro / secundario | `px-4 py-2 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-text-primary-dark text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700` | Volver, acciones auxiliares |
| Cancelar (texto) | `px-5 py-2 text-sm font-medium text-gray-600 dark:text-text-secondary-dark hover:text-gray-900 dark:hover:text-text-primary-dark` | Junto a Guardar |
| Acción de tabla | `inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg` | Editar, Stock, Lavados, Activar |
| Peligro de tabla | `bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50` | Eliminar |
| Peligro primario | `bg-red-600 hover:bg-red-700 text-white` | Confirmación destructiva en modal |

Detalles obligatorios:

- Icono dentro del botón: `w-4 h-4` (o `w-3.5 h-3.5` en acciones de tabla), separado con `gap-2`/`gap-1`.
- `transition-colors` siempre; `disabled:opacity-50 disabled:cursor-not-allowed` en acciones asíncronas.
- Botones de solo icono: `p-2 rounded-full` o `rounded-lg` + `aria-label`.
- Acciones congeladas por regla de negocio: se aplica `[data-requiere-caja]`, que el CSS global deja
  en gris (`opacity .4`, `grayscale`, `pointer-events: none`). Útil como patrón de “acción bloqueada”.

### 6.2 Formularios

```html
<div class="mb-5">
    <label for="campo" class="label-main mb-1">Nombre del campo <span class="text-red-500">*</span></label>
    <input type="text" id="campo"
           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none
                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                  transition-colors input-main">
    <p class="mt-1 text-xs text-secondary">Texto de ayuda del formato.</p>
    @error('campo')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>
```

- Contenedor del formulario: `bg-surface rounded-lg border border-main p-6 max-w-lg`.
- Separación vertical entre campos: `mb-5`; el último bloque de acciones no lleva margen inferior.
- Selects y textareas comparten `input-main` + `px-3 py-2 text-sm border rounded-lg`.
- Checkboxes: `w-4 h-4 text-blue-600 border-main rounded focus:ring-blue-500 dark:bg-slate-800`,
  con etiqueta `inline-flex items-center gap-2 cursor-pointer` y texto `text-sm text-secondary`.
- Campos de solo lectura: se mantiene la forma del input, con fondo atenuado
  (`bg-slate-50 dark:bg-slate-800/50`).
- Errores: borde `border-red-400` y fondo `bg-red-50 dark:bg-red-900/20` (o `input-error`).
- Validación en vivo con `novalidate` + JS de filtrado (`data-filter="digits|letters"`,
  `data-length`, `data-validate-length`). El servidor sigue validando.

### 6.3 Tablas / listados

```html
<div class="bg-surface rounded-lg border border-main overflow-x-auto transition-colors duration-300">
    <table class="min-w-full divide-y divide-main">
        <thead class="bg-gray-50 dark:bg-slate-800/50">
            <tr>
                <th class="px-6 py-6 text-left text-xs font-medium text-gray-500
                           dark:text-text-secondary-dark uppercase tracking-wider">Columna</th>
            </tr>
        </thead>
        <tbody class="bg-surface divide-y divide-main">
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-6 py-8 whitespace-nowrap text-sm text-primary">Dato</td>
            </tr>
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
```

- **Paginación:** 10 registros por página (`paginate(10)`).
- **Padding:** `th` `py-6`, `td` `py-8` (miniaturas y filas condensadas pueden usar `py-6`).
- **Scroll horizontal:** el wrapper usa `overflow-x-auto`, nunca `overflow-hidden`.
- **Alineación:** todo `text-left`; importes y datos secundarios en `text-secondary`; el dato
  identificador (nombre, placa, DNI) en `text-primary`.
- **Cabecera de listado:** título + buscador (`w-full sm:w-64 md:w-72` con icono de lupa a la
  izquierda) + botón primario “Crear …”.
- **Filtros:** `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4`, selects con
  `data-auto-submit` y etiqueta visible.
- **Estado vacío:** `text-center py-12 text-gray-500 dark:text-text-secondary-dark text-sm`
  (“No hay clientes registrados.”). Si hay búsqueda, mensaje específico + icono `w-12 h-12`
  en `text-gray-300 dark:text-slate-600`.
- **Buscador:** `<input type="search">` con `pl-9`, icono absoluto a la izquierda y
  `placeholder-gray-400 dark:placeholder-text-secondary-dark`.

### 6.4 Badges y píldoras de estado

```html
<!-- Estado normal -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
             bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">Activo</span>

<!-- Estado micro (stock) -->
<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
             uppercase tracking-wide bg-red-600 dark:bg-red-900/60 text-white dark:text-red-300">Sin stock</span>
```

- Forma: píldora (`rounded-full`), sin borde.
- Colores por estado: verde = activo/éxito, rojo = inactivo/agotado, ámbar = advertencia/consumo
  interno, azul = comercial/informativo, teal = inventario.
- Texto corto, en una línea, sin punto final.

### 6.5 Tarjetas y KPIs

```html
<div class="bg-surface border border-main rounded-xl p-5 shadow-sm">
    <p class="text-sm text-secondary">Ingresos de hoy</p>
    <p class="text-2xl font-bold text-primary mt-1">S/ 1,250.00</p>
    <p class="text-xs text-secondary mt-1">Comparado con ayer</p>
</div>
```

- Radio `rounded-xl`, borde `border-main`, sombra `shadow-sm` (máximo).
- Etiqueta arriba (12–14 px secundaria), cifra grande debajo, contexto opcional en 12 px.
- Rejillas con `gap-4`; secciones separadas con `space-y-6`.

### 6.6 Modales

Componente base: `resources/views/components/modal.blade.php` (`<x-modal>`).

- Overlay: `bg-slate-900/75` (o `bg-black/80 backdrop-blur-sm` en el visor de imágenes).
- Panel: `bg-white dark:bg-surface-dark rounded-xl border dark:border-border-dark shadow-xl`,
  ancho según `maxWidth` (`sm|md|lg|xl|2xl`).
- Título: `text-lg font-medium text-gray-900 dark:text-text-primary-dark`.
- Cierre: botón circular `p-2 rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700`
  con icono X y `aria-label="Cerrar"`.
- Cuerpo: `px-6 pb-4 sm:pb-6`.
- Pie: `bg-gray-50 dark:bg-slate-800/50 px-4 py-3 border-t` y botones alineados a la derecha
  (`sm:flex sm:flex-row-reverse`).
- Animación opcional: opacidad + `scale-95 → scale-100` en 300 ms (`data-modal-animated`).
- Estructura de confirmación: pregunta en el cuerpo, botón de peligro a la derecha y “Cancelar”
  a la izquierda.

### 6.7 Alertas y mensajes flash

```html
<div role="alert" class="mb-4 px-4 py-3 rounded-lg bg-green-100 text-green-800 border border-green-200 text-sm">
    {{ session('success') }}
</div>
<div role="alert" class="mb-4 px-4 py-3 rounded-lg bg-red-100 text-red-800 border border-red-200 text-sm">
    {{ session('error') }}
</div>
```

- Siempre arriba del contenido, justo después del padding de la página.
- `role="alert"` obligatorio.
- Aviso contextual dentro de modal: `p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800`,
  icono `w-4 h-4` y texto `text-xs`.
- Confirmaciones destructivas: SweetAlert2 mediante `data-confirm="¿Estás seguro…?"`; nunca
  `window.confirm`.

### 6.8 Búsqueda, filtros y paginación

- Buscador con icono de lupa `w-4 h-4`, input `type="search"`, `aria-label` descriptivo.
- Indicador de resultados opcional debajo del header: `text-sm text-secondary mb-3`.
- Filtros como formulario GET con selects y `data-auto-submit`, etiquetas visibles.
- Paginación de Laravel tal cual (`$items->links()`) envuelta en `mt-4`; no se personaliza la vista.

---

## 7. Patrones de pantalla

### 7.1 Listado (CRUD index)

1. Flash messages.
2. Header: título H1 + buscador + botón primario “Crear …”.
3. Filtros opcionales (fila de selects).
4. Tabla dentro de tarjeta `bg-surface border border-main rounded-lg overflow-x-auto`.
5. Estado vacío si no hay registros.
6. Paginación a 10.
7. Modales auxiliares al final del archivo.

### 7.2 Formulario (create / edit)

1. Flash messages.
2. Header: H1 + botón neutro “Volver”.
3. Tarjeta `max-w-lg p-6` con formulario `novalidate`.
4. Campos con etiqueta, input, ayuda y error.
5. Acciones: primario “Guardar” + enlace de texto “Cancelar”.
6. JS específico del módulo con `@vite(...)` al final del `@section('content')`.

### 7.3 Dashboard

1. Header H1 + subtítulo + selector de período (chips).
2. Fila de 4 KPIs.
3. Fila de desglose por fuente con color semántico.
4. Bloque operativo (3 métricas).
5. Gráficos (Chart.js) en tarjetas de altura fija `h-72`.
6. Tablas o listas resumidas en dos columnas.

### 7.4 Punto de venta (ventas / lavados)

- Cabecera con título y botón neutro para volver.
- Tarjeta de captura (buscador de producto, tabla de detalle editable).
- Bloque de totales `max-w-sm space-y-4` con subtotal de solo lectura, descuentos y total.
- Botones de acción al final; acciones bloqueadas si no hay caja abierta.
- Celdas y controles táctiles: `py-6`/`py-8`, inputs `text-sm`.

### 7.5 Autenticación

- `layouts.auth`: `min-h-screen flex items-center justify-center`, columna `max-w-md px-6 py-8`.
- Tarjeta `bg-white dark:bg-surface-dark rounded-2xl shadow-sm border p-8`.
- Título centrado `text-2xl font-semibold`, campos `mb-4`/`mb-6`, botón a ancho completo.
- Mismo sistema de tokens que el panel; no se usa una identidad distinta.

---

## 8. Modo oscuro

- La clase `dark` se aplica en `<html>` antes del render (script inline en `head`) para evitar FOUC.
- Persistencia: `localStorage.theme`; si no existe, se respeta `prefers-color-scheme`.
- Todo componente debe declarar su par claro/oscuro. **Nunca** dejar fondos blancos o textos
  `text-gray-900` sin variante oscura.
- Superficies oscuras: `#0f172a` sobre `#020617`; bordes `#1e293b`; texto `#e2e8f0` / `#94a3b8`.
- Colores de estado en oscuro usan fondo translúcido (`bg-red-900/30`) y texto `-400`.
- Iconos SVG heredan color (`stroke="currentColor"`); si no tienen color de marca, se les da
  `text-gray-400` o el secundario del tema.
- Transiciones: `transition-colors duration-300` en contenedores grandes; `duration-200` en botones.
- Imágenes y miniaturas: borde `border-main` para no brillar sobre fondo oscuro.

---

## 9. Responsive y móvil

- Punto de quiebre de navegación: `lg` (1024 px). Debajo, sidebar oculto y barra inferior fija.
- `main` reserva espacio para la barra móvil: `pb-[77px] lg:pb-0`.
- Cabeceras que incluyen controles pasan a columna: `flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`.
- Buscadores y botones a ancho completo en móvil (`w-full sm:w-auto`).
- Tablas: scroll horizontal, nunca recortar columnas.
- Formularios: una columna siempre; `max-w-lg` también en escritorio.
- Textos de navegación móvil pequeños (`text-[10px]`) pero con iconos de 24 px para área táctil.
- Al imprimir: sidebar y barra móvil ocultas (`print:hidden`), scroll liberado (`print:overflow-visible`).

---

## 10. Interacción, accesibilidad y feedback

- **Foco visible:** `focus:outline-none focus:ring-2 focus:ring-blue-500` (+ `focus:ring-offset-2`
  en botones primarios de formulario).
- **Estados deshabilitados:** `disabled:opacity-50 disabled:cursor-not-allowed`.
- **Carga:** cambiar el texto del botón y deshabilitarlo; no bloquear la página completa.
- **Confirmación:** toda acción destructiva pide confirmación con SweetAlert2 (`data-confirm`).
- **Etiquetas:** todo input tiene `<label for>`; los iconos decorativos llevan `aria-hidden="true"`.
- **Roles:** mensajes con `role="alert"`; modales con `role="dialog"`, `aria-modal="true"` y
  `aria-labelledby`.
- **Botones de icono:** `aria-label` con la acción y el registro afectado.
- **Feedback de error:** borde + mensaje textual; nunca solo color.
- **Sin dependencia de red para formularios:** las consultas externas (DNI, placa) son opcionales
  y jamás bloquean el guardado.

---

## 11. Espaciado, radios y sombras

| Token | Valor | Uso |
| :--- | :--- | :--- |
| Padding de página | `p-6` | Todas las vistas del panel |
| Separación entre bloques | `space-y-6`, `gap-4` | Dashboard y rejillas |
| Separación entre campos | `mb-5` | Formularios |
| Radio estándar | `rounded-lg` (8 px) | Inputs, botones, tablas, alertas |
| Radio de tarjeta | `rounded-xl` (12 px) | KPIs y tarjetas del dashboard |
| Radio destacado | `rounded-2xl` (16 px) | Login, visor de imágenes |
| Radio de píldora | `rounded-full` | Badges, botones de icono circulares |
| Sombra de tarjeta | `shadow-sm` | Superficies en reposo |
| Sombra de modal | `shadow-xl` | Diálogos |
| Sombra de drop-up | `shadow-2xl` | Menús móviles |

---

## 12. Cómo portar este patrón a otro sistema

### 12.1 Definir primero los tokens

Copia el bloque `@theme` y las clases semánticas. En otro stack, tradúcelos a variables CSS:

```css
:root {
  --bg-page: #f9fafb;
  --surface: #ffffff;
  --border: #e5e7eb;
  --text-primary: #111827;
  --text-secondary: #4b5563;
  --accent: #2563eb;
  --accent-hover: #1d4ed8;
  --ring: #3b82f6;
  --radius: 8px;
  --radius-card: 12px;
}
.dark {
  --bg-page: #020617;
  --surface: #0f172a;
  --border: #1e293b;
  --text-primary: #e2e8f0;
  --text-secondary: #94a3b8;
}
```

Ningún componente debe usar colores literales: todos consumen los tokens.

### 12.2 Equivalencias de estructura

| Aquí (Blade/Tailwind) | Otro sistema (ej. React/Vue) |
| :--- | :--- |
| `layouts.app` | `AppLayout` con sidebar + `<main>` + bottom nav |
| `x-modal` | `Modal` con overlay, panel, header, footer |
| `bg-surface` / `text-primary` | clases utilitarias o props del tema |
| `data-confirm` + SweetAlert2 | helper `confirmAction()` previo a la mutación |
| `paginate(10)` | paginación de 10 ítems por página |
| `@error` / flash | estado de error por campo + toast/banner global |

### 12.3 Checklist de implementación

- [ ] Tokens claro/oscuro definidos y sin colores literales en componentes.
- [ ] Tipografía: 24/16/14/12/10 px con los pesos indicados.
- [ ] Layout: sidebar 256 px, contenido `p-6`, header de página con H1 de 24 px.
- [ ] Navegación inferior en móvil + scroll horizontal en tablas.
- [ ] Formularios: `label-main`, `input-main`, ayuda 12 px, error rojo textual.
- [ ] Tablas: cabecera 12 px mayúsculas, `py-6`, celdas `py-8`, `overflow-x-auto`, 10 por página.
- [ ] Botones: primario azul, neutro gris, peligro rojo suave, iconos 16 px, `aria-label`.
- [ ] Modales: overlay oscuro, panel `rounded-xl`, footer gris, cierre con X.
- [ ] Badges: píldora, color por estado, texto corto en mayúsculas solo si es micro-badge.
- [ ] Alertas flash con `role="alert"` y confirmación en acciones destructivas.
- [ ] Foco visible azul y estados `disabled` atenuados.
- [ ] Tema persistente en `localStorage` sin FOUC.
- [ ] Formatos: `S/ 0.00`, `—` para vacíos, fechas en `America/Lima`.

### 12.4 Anti-patrones (no hacer)

- Usar negro puro (`#000`) o blanco puro como fondo en modo oscuro.
- Mezclar radios (`rounded-md`, `rounded-xl`, `rounded-3xl`) sin criterio.
- Sombras grandes en tarjetas de contenido.
- Botones sin icono cuando la acción es destructiva o de navegación.
- Tablas con `overflow-hidden` (rompe el scroll móvil).
- Mayúsculas escritas en el HTML en lugar de aplicadas con CSS.
- Mensajes de error solo por color.
- Colores nuevos por módulo sin pasar por los tokens semánticos.
- Formularios a ancho completo en escritorio.
- Más de un H1 por vista o jerarquías que salten niveles.

---

## 13. Referencias de código

- Tokens y clases semánticas: `resources/css/app.css`.
- Layout y navegación: `resources/views/layouts/app.blade.php`.
- Layout de autenticación: `resources/views/layouts/auth.blade.php`.
- Modal reutilizable: `resources/views/components/modal.blade.php`.
- Tema persistente: `resources/views/partials/theme-script.blade.php`.
- Guía de modo oscuro: `.kiro/guia-modo-oscuro.md`.
- Guía de listados: `.kiro/reajuste-listados.md`.
- Ejemplos reales: `dashboard.blade.php`, `productos/index.blade.php`,
  `clientes/index.blade.php`, `clientes/create.blade.php`, `auth/login.blade.php`.
