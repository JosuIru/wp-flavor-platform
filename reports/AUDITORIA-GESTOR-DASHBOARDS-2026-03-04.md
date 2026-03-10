# Auditoría de Acceso Gestor a Dashboards 2026-03-04

## Alcance

Revisión estática del acceso a dashboards administrativos desde la vista `gestor_grupos`.

Objetivo:
- distinguir dashboards que ya pueden abrirse por capacidad `flavor_ver_dashboard`
- separar los casos con señales de checks legacy internos basados en `manage_options`
- identificar dashboards primarios ya adaptados a modo lectura para gestor

## Criterio aplicado

Se considera `acceso gestor` cuando:
- el dashboard pertenece a una categoría visible para gestor de grupos
- el panel unificado puede registrar la página principal con capacidad `flavor_ver_dashboard`

Se marca `revisión interna` cuando además se detecta al menos una de estas señales sin bypass equivalente:
- `views/dashboard.php` con `current_user_can('manage_options')` y sin `flavor_ver_dashboard`
- presencia de checks `manage_options` en el archivo principal del módulo que también define el renderer admin, sin bypass equivalente

## Estado actual

La vía de acceso ya queda abierta para el dashboard primario en categorías:
- `comunidad`
- `comunicacion`
- `actividades`
- `servicios`
- `recursos`
- `sostenibilidad`

Esto no implica que todos los dashboards sean ya plenamente utilizables por `gestor_grupos`.

## Ajustes aplicados

### 1. Apertura del dashboard primario por capacidad

El panel unificado ya puede registrar el dashboard principal con `flavor_ver_dashboard` para categorías visibles del modo gestor.

### 2. Guardas legacy corregidas en views dashboard

Se han corregido las guardas del dashboard primario en:
- `carpooling`
- `bicicletas_compartidas`
- `parkings`

En estos tres casos, la vista dashboard ya acepta:
- `manage_options`
- `flavor_ver_dashboard`

### 3. Modo lectura explícito en dashboards primarios

Se ha dejado comportamiento de solo lectura, sin atajos a subpáginas restringidas ni acciones admin implícitas, en:
- `tramites`
- `incidencias`
- `eventos`
- `foros`
- `campanias`
- `comunidades`
- `chat_estados`
- `chat_interno`
- `mapa_actores`
- `ayuda_vecinal`
- `banco_tiempo`

Patrón aplicado:
- aviso visible de `vista resumida para gestor de grupos`
- CTA `Ver en portal` en lugar de accesos rápidos de administración
- omisión de `handle_admin_actions()` para usuarios sin `manage_options` cuando aplica
- corte temprano del dashboard cuando el resto del contenido es claramente administrativo

### 4. Corrección estructural en chat grupos

`chat_grupos` no tenía respaldada la ruta de dashboard por el archivo incluido desde `render_admin_dashboard()`.

Se ha sustituido por un dashboard inline funcional con:
- métricas básicas
- resumen operativo
- CTA a portal para gestor
- accesos administrativos sólo para administradores

### 5. Red social y multimedia con accesos seguros

En `red_social` se han reorientado los quick links del dashboard para modo gestor a destinos seguros del portal, evitando enviar a moderación o configuración administrativa.

En `multimedia` además se ha corregido el dashboard primario para usar `views/dashboard.php`, ya que `admin-dashboard.php` no existe en el árbol actual. También se ha corregido el entrypoint admin heredado `render_admin_page()` para que apunte al template real.

### 6. Estadísticas del panel unificado con enlaces seguros

Se han reorientado enlaces de estadísticas visibles para `gestor_grupos` en:
- `chat_interno`
- `mapa_actores`
- `ayuda_vecinal`
- `banco_tiempo`

Con esto, las tarjetas resumen ya no llevan al gestor a subpáginas admin restringidas cuando existe un destino seguro en portal.

### 7. Pantallas secundarias saneadas para acceso accidental o directo

Se ha dejado comportamiento de solo lectura también en varias pantallas secundarias que podían seguir siendo alcanzables por URL directa o por enlaces residuales:
- `chat_interno > conversaciones`
- `mapa_actores > listado`
- `mapa_actores > relaciones`
- `ayuda_vecinal > solicitudes`
- `ayuda_vecinal > voluntarios`
- `banco_tiempo > intercambios`
- `banco_tiempo > miembros`
- `banco_tiempo > servicios`
- `multimedia > galeria`

Patrón aplicado en esta tanda:
- CTA `Ver en portal` cuando el perfil es `gestor_grupos`
- aviso explícito de `vista de consulta` o `solo lectura`
- eliminación de exportaciones, altas, edición o enlaces a perfiles admin para ese perfil
- bloqueo de escritura en `banco_tiempo > servicios` para usuarios sin `manage_options`
- degradación segura de enlaces internos a texto plano cuando el destino era puramente administrativo

## Residual conocido

- En varios módulos siguen existiendo subpáginas secundarias sólo admin. Eso es correcto mientras no se expongan como atajo principal al rol gestor.
- En `mapa_actores`, `ayuda_vecinal`, `banco_tiempo` y `multimedia` siguen existiendo pantallas puramente administrativas fuera del circuito de consulta rápida del gestor. Eso es aceptable siempre que no se les abra capacidad ni se reintroduzcan atajos desde dashboards.

## Lectura operativa

- La navegación ya está resuelta sin saturar `FlavorShell`.
- El acceso gestor ya es funcional de verdad en varios dashboards primarios, no solo visible en el índice.
- El acceso gestor también queda bastante más estable en secundarios visibles o alcanzables, reduciendo caídas a exportaciones, edición o perfiles de usuario en admin.
- Quedan todavía módulos que requieren validación runtime autenticada para confirmar que no existen checks más profundos fuera del renderer principal.
- La etiqueta `Revisión interna` del índice ya no debería dispararse por una simple coexistencia de `manage_options` cuando también existe bypass explícito para dashboard.

## Siguiente tanda recomendada

Validar en runtime autenticado con un usuario `flavor_gestor_grupos` y cerrar los residuos que sólo puedan detectarse navegando:
- enlaces secundarios todavía visibles en dashboards con tablas complejas
- checks internos adicionales distintos de `manage_options`
- subpantallas auxiliares que no se alcanzan por índice pero sí por URL directa
