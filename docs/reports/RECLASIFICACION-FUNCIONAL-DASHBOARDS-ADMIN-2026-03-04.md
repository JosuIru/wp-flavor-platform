# Reclasificación Funcional de Dashboards Admin 2026-03-04

## Motivo

La auditoría `AUDITORIA-DASHBOARDS-ADMIN-2026-03-04.md` clasifica la capa admin con criterio principalmente estructural:

- slug detectable
- `get_admin_config()`
- renderer admin
- integración con panel unificado

Eso es útil para inventario, pero no sirve para afirmar que todos los dashboards estén al nivel de `gc-dashboard`.

## Referencia canónica

El dashboard de referencia funcional es:

- `grupos_consumo` -> `gc-dashboard`

Razones:
- implementación dedicada muy amplia en [includes/modules/grupos-consumo/class-gc-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/class-gc-dashboard-tab.php)
- assets propios de dashboard
- múltiples subflujos reales
- interacción y estados trabajados
- conexión profunda con procesos del módulo

No es un dashboard “resumen”; es casi un mini producto administrativo.

## Lectura correcta

Hay tres niveles reales:

### 1. Dashboard maduro

Se parece de verdad a `gc-dashboard` en profundidad funcional, no sólo en wiring.

Módulos:
- `grupos_consumo`

### 2. Dashboard operativo pero resumido

Tiene dashboard real, métricas, accesos y cierta UX propia, pero está varios escalones por debajo de `gc-dashboard` en interacción, densidad funcional o especialización.

Módulos:
- `marketplace`
- `foros`
- `socios`
- `incidencias`
- `banco_tiempo`
- `ayuda_vecinal`
- `mapa_actores`
- `chat_estados`
- `chat_grupos`
- `chat_interno`
- `tramites`
- `eventos`
- `reservas`
- `multimedia`
- `comunidades`
- `radio`

### 3. Dashboard estructural o básico

Existe el contrato admin, pero la experiencia está más cerca de una portada de módulo o de un resumen ligero que de un dashboard maduro.

En la muestra revisada hoy no entra `grupos_consumo`; el resto de candidatos deben compararse uno a uno antes de venderlos como maduros.

## Evidencia resumida

### `grupos_consumo`

- Dashboard tab dedicado: `2690` líneas en [includes/modules/grupos-consumo/class-gc-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/class-gc-dashboard-tab.php)
- Flujos claros:
  - lista de compra
  - mis pedidos
  - suscripciones
  - mis grupos
  - calendario
- assets específicos:
  - [includes/modules/grupos-consumo/assets/gc-dashboard.css](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/gc-dashboard.css)
  - [includes/modules/grupos-consumo/assets/gc-dashboard.js](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/gc-dashboard.js)

### Ejemplos de dashboards resumidos

- `eventos`: vista dashboard compacta de `73` líneas en [includes/modules/eventos/views/dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/views/dashboard.php)
- `reservas`: vista dashboard de `208` líneas en [includes/modules/reservas/views/dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/reservas/views/dashboard.php)
- `marketplace`: vista dashboard de `375` líneas en [includes/modules/marketplace/views/dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/views/dashboard.php)
- `foros`: vista dashboard de `283` líneas en [includes/modules/foros/views/dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/foros/views/dashboard.php)
- `socios`: vista dashboard de `446` líneas en [includes/modules/socios/views/dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/socios/views/dashboard.php)

Esos dashboards pueden ser buenos o suficientes, pero no están en la misma categoría que `gc-dashboard`.

## Qué ha pasado en la práctica

Se mezclaron dos cosas distintas:

1. `madurez estructural`
2. `madurez funcional`

Durante esta tanda se cerró bien la primera:
- slugs
- accesibilidad
- panel unificado
- shell
- capacidades por rol

Pero eso no iguala automáticamente la segunda.

## Conclusión

La frase correcta no es:

- “todos los dashboards están al nivel de grupos de consumo”

La frase correcta es:

- “la infraestructura admin de dashboards está bastante alineada, pero la profundidad funcional sigue siendo desigual y `grupos_consumo` continúa como referencia más madura”

## Siguiente paso recomendado

Si se quiere homogeneidad real, el plan correcto es:

1. usar `gc-dashboard` como patrón
2. escoger 5 dashboards prioritarios
3. elevarlos a la categoría `operativo fuerte`
4. sólo después prometer paridad visual o funcional

Prioridad sugerida:
- `marketplace`
- `tramites`
- `reservas`
- `eventos`
- `socios`
