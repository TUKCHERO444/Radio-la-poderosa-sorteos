# 001 — Panel de administración (base)

- **Estado:** implementada (base)
- **Fecha:** 2026-11-15
- **Depende de:** Fase 1 (sitio público)

## 1. Contexto

El proyecto evoluciona de una página de radio a una **plataforma de sorteos**.
El panel de administración será el centro de gestión del negocio. A futuro
administrará sorteos, productos de venta, imágenes y colores del sitio. En esta
primera entrega solo se implementa el acceso y la gestión de usuarios para
tener una base segura sobre la que crecer.

## 2. Objetivo

Permitir que un administrador acceda mediante login a un panel privado,
responsive y profesional, y gestione los usuarios del sistema.

## 3. Historias de usuario y criterios de aceptación

### HU-01 — Acceso al panel desde el sitio público

Como visitante, quiero un botón visible en la esquina superior del sitio para
llegar al panel de administración.

Criterios de aceptación:

- [x] Existe un botón fijo en la esquina superior derecha de la página pública.
- [x] El botón tiene etiqueta accesible y enlaza a `/admin`.
- [x] En móvil el botón sigue visible y no tapa el reproductor de forma crítica.

### HU-02 — Inicio de sesión

Como administrador, quiero iniciar sesión con usuario y contraseña para acceder
al panel.

Criterios de aceptación:

- [x] `/admin` redirige a `/admin/login` si no hay sesión.
- [x] El login valida usuario y contraseña contra la base de datos.
- [x] Las contraseñas se verifican con `password_verify`, nunca en texto plano.
- [x] Ante credenciales incorrectas se muestra un mensaje genérico.
- [x] Tras 5 intentos fallidos se bloquea el intento durante 60 segundos.
- [x] Al iniciar sesión se regenera el ID de sesión (evita fijación).
- [x] Se registra `last_login_at` del usuario.

### HU-03 — Cierre de sesión

Como administrador autenticado, quiero cerrar sesión desde el panel.

Criterios de aceptación:

- [x] Existe un botón "Cerrar sesión" en la parte inferior del sidebar.
- [x] El cierre se hace por `POST` con token CSRF.
- [x] Al cerrar se destruye la sesión y se redirige al login.
- [x] Las rutas privadas dejan de ser accesibles.

### HU-04 — Listado de usuarios

Como administrador, quiero ver todos los usuarios registrados.

Criterios de aceptación:

- [x] Tabla con ID, usuario, nombre, rol, último acceso y fecha de creación.
- [x] Estado vacío claro cuando no hay usuarios.
- [x] La tabla es usable en móvil (scroll horizontal controlado).
- [x] Se muestra un contador de usuarios en el dashboard.

### HU-05 — Crear usuario

Como administrador, quiero crear usuarios indicando usuario, nombre, rol y
contraseña.

Criterios de aceptación:

- [x] El usuario es obligatorio, único, de 3 a 50 caracteres
  (`[A-Za-z0-9._-]`).
- [x] La contraseña es obligatoria al crear, mínimo 8 caracteres.
- [x] Se solicita confirmación de contraseña.
- [x] Los errores se muestran junto al formulario conservando los datos válidos.
- [x] Al guardar se redirige con mensaje de éxito.

### HU-06 — Editar usuario

Como administrador, quiero actualizar los datos de un usuario.

Criterios de aceptación:

- [x] Permite cambiar nombre, rol y contraseña.
- [x] Dejar la contraseña vacía conserva la actual.
- [x] El nombre de usuario se puede cambiar y sigue siendo único.
- [x] No se puede degradar al último administrador a un rol sin privilegios.

### HU-07 — Eliminar usuario

Como administrador, quiero eliminar usuarios.

Criterios de aceptación:

- [x] Se pide confirmación antes de eliminar.
- [x] No se puede eliminar el propio usuario autenticado.
- [x] No se puede eliminar el último administrador.
- [x] La operación usa `POST` con CSRF y se redirige con mensaje de éxito.

### HU-08 — Usuario inicial

Como equipo de desarrollo, necesitamos un usuario inicial para entrar al panel.

Criterios de aceptación:

- [x] El instalador crea el usuario `radiolp` con contraseña `admin123`.
- [x] La contraseña se guarda hasheada, nunca en claro.
- [x] El instalador es idempotente: no duplica ni sobrescribe el usuario.

## 4. Requisitos no funcionales

- **Seguridad:** CSRF, sesiones seguras, consultas preparadas, escape de salida,
  bloqueo por intentos fallidos.
- **Responsividad:** usable desde 320 px hasta escritorio; sidebar colapsable.
- **Diseño:** estética administrativa profesional, coherente con la marca
  (azul formal + superficies claras, inspirado en AdminLTE).
- **Compatibilidad:** PHP 8.0, MySQL/MariaDB, Apache/cPanel y servidor PHP local.
- **Accesibilidad:** foco visible, etiquetas `label`, `aria-*` básicos.
- **Mantenibilidad:** código separado por responsabilidades, sin dependencias
  externas.

## 5. Fuera de alcance (para esta entrega)

- CRUD de sorteos.
- CRUD de productos.
- Subida de imágenes y edición de colores del sitio.
- Recuperación de contraseña por correo.
- Multiusuario avanzado, permisos granulares o auditoría detallada.

Estos puntos quedan previstos en el diseño para fases posteriores.
