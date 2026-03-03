# Backlog Operativo de Modulos 2026-03-02

## Uso de este backlog

Este backlog traduce el plan maestro a una ejecucion por tandas. Cada tanda debe cerrarse con:

- correcciones en codigo
- validacion runtime
- evidencia en reporte
- actualizacion de matriz de salud

## Estado actual resumido

- `Tanda 1`: muy avanzada en saneamiento estatico y parcialmente validada en runtime cuando el entorno Local respondio.
- `Tanda 2`: muy avanzada en integraciones, tabs, sidebars, aliases y consistencia de portal.
- `Tanda 5` y `Tanda 6`: ya no son solo exploratorias; varios modulos legacy/dependientes de entorno quedaron estabilizados a nivel de contrato.
- Bloqueo principal restante: validacion runtime consistente desde CLI/navegador sobre `localhost:10028`.
- Se inicio una subfase de `deslegacyficacion` del portal:
  - `banco-tiempo`
  - `grupos-consumo`
  - `reservas`
  - `tramites`
  - `participacion`
  - `espacios-comunes`
  - `red-social`
  - `comunidades`
  ya pasan a `modern-only` en sidebar y acciones prioritarias del portal.

## Definicion de hecho por modulo

Un modulo se considera reparado cuando cumple todo esto:

- esquema y tablas coherentes con el codigo activo
- frontend y dashboard cargan sin `403`, `404` ni `500`
- AJAX y REST responden con contrato consistente
- assets y `wp_localize_script` alineados con el JS
- contenido real minimo visible
- integraciones declaradas funcionando o marcadas como no operativas
- estados vacios y permisos correctos

## Tanda 1. Nucleo del portal

### Objetivo

Estabilizar los modulos que más afectan a `mi-portal`, dashboard cliente y navegacion principal.

### Modulos

- `comunidades`
- `red-social`
- `grupos-consumo`
- `reservas`
- `tramites`
- `participacion`
- `espacios-comunes`

### Trabajo

- cerrar tablas y columnas inconsistentes
- eliminar duplicidad entre handlers legacy y actuales
- validar rutas, shortcodes, tabs y assets
- sembrar contenido real minimo
- registrar errores runtime restantes

### Criterio de cierre

- recorrido manual completo de pantallas principales
- sin errores fatales nuevos
- sin `403` de nonce/permisos en flujos basicos

### Estado actual

- `comunidades`: saneado en tabs, integraciones, feed y nodos; pendiente validacion runtime completa con sesion estable
- `red-social`: dashboard y aliases saneados; pendiente validacion runtime funcional
- `grupos-consumo`: flujo de suscripciones muy saneado; pendiente validacion funcional completa
- `reservas`: handlers y frontend alineados; pendiente validacion runtime completa
- `tramites`: puente de acciones y JS/PHP saneado; pendiente validacion runtime
- `participacion`: JS/PHP y aliases saneados; pendiente validacion runtime
- `espacios-comunes`: JS/PHP, registro y acciones saneados; pendiente validacion runtime

## Tanda 2. Integraciones de contenido

### Objetivo

Dejar operativos los modulos que alimentan contenido compartido, relaciones y nodos.

### Modulos

- `eventos`
- `multimedia`
- `radio`
- `podcast`
- `biblioteca`
- `recetas`
- `banco-tiempo`
- `parkings`
- `reciclaje`

### Trabajo

- validar providers y consumers
- corregir metaboxes y targets reales
- conectar contenido visible entre modulos
- revisar buscadores, feeds y cards

### Criterio de cierre

- cada modulo muestra contenido real enlazado
- no hay integraciones declaradas sin destino real

### Estado actual

- `eventos`: integrado en `comunidades`, pero sigue pendiente validar filtrado real por `comunidad_id` con datos reales
- `multimedia`, `radio`, `podcast`, `biblioteca`, `banco-tiempo`, `parkings`, `reciclaje`: muy saneados a nivel de contrato/portal
- `recetas`: flujo comunitario moderado saneado
- pendientes principales: contenido real y validacion runtime estable

## Tanda 3. Modulos operativos y de gestion

### Objetivo

Cerrar los modulos con valor funcional alto pero menor centralidad en la portada del portal.

### Modulos

- `socios`
- `talleres`
- `marketplace`
- `incidencias`
- `huertos-urbanos`
- `bicicletas-compartidas`
- `cursos`
- `foros`
- `ayuda-vecinal`

### Trabajo

- revisar CRUD real
- validar tabs, widgets y paneles
- sembrar contenido minimo
- corregir estados vacios y CTAs

### Estado actual

- `socios`, `talleres`, `marketplace`, `incidencias`, `huertos-urbanos`, `bicicletas-compartidas`, `cursos`, `foros`, `ayuda-vecinal`: todos han recibido saneamiento estructural o de aliases
- `foros` y `marketplace` ya no son simplemente modulos "rotos"; requieren cierre runtime y contenido real

## Tanda 4. Modulos con deuda media

### Modulos

- `campanias`
- `colectivos`
- `compostaje`
- `documentacion-legal`
- `justicia-restaurativa`
- `mapa-actores`
- `podcast`
- `saberes-ancestrales`
- `seguimiento-denuncias`
- `trabajo-digno`
- `transparencia`
- `avisos-municipales`

### Trabajo

- revisar coherencia de frontend
- revisar assets y localizacion JS
- decidir si quedan activos por defecto o bajo activacion selectiva

### Estado actual

- `campanias`, `colectivos`, `compostaje`, `justicia-restaurativa`, `saberes-ancestrales`, `trabajo-digno`, `transparencia`, `avisos-municipales`: con mejoras ya aplicadas
- `documentacion-legal`, `mapa-actores`, `seguimiento-denuncias`: revisados en navegacion/aside; pendientes de cierre runtime

## Tanda 5. Modulos con deuda alta o rol incierto

### Modulos

- `clientes`
- `themacle`
- `chat-grupos`
- `chat-interno`
- `bares`
- `advertising`
- `chat-estados`
- `economia-suficiencia`
- `sello-conciencia`
- `woocommerce`

### Trabajo

- clasificar por modulo:
  - reparar ahora
  - congelar como experimental
  - deprecar
- evitar que un modulo de esta tanda rompa bootstrap global

### Estado actual

- `clientes`, `themacle`, `chat-grupos`, `chat-interno`, `bares`, `advertising`, `chat-estados`, `economia-suficiencia`, `woocommerce`: saneados en mayor o menor medida a nivel de contrato
- esta tanda ya no es solo de contencion; varios modulos quedaron aptos para validacion runtime
- `sello-conciencia` sigue siendo de los candidatos mas claros para revision posterior

## Tanda 6. Modulos especiales o dependientes de entorno

### Modulos

- `dex-solana`
- `trading-ia`
- `facturas`
- `fichaje-empleados`
- `empresarial`
- `email-marketing`
- `economia-don`

### Trabajo

- validar dependencias externas
- confirmar que el entorno local soporta cada modulo
- aislar cualquier funcionalidad que dependa de servicios externos

### Estado actual

- `facturas`, `fichaje-empleados`, `empresarial`, `email-marketing`, `economia-don`: saneados en el puente de acciones
- `dex-solana` y `trading-ia`: siguen siendo candidatos a aislamiento por dependencia externa

## Backlog transversal

### Base de datos

- matriz de tablas por modulo
- matriz de columnas legacy vs actuales
- plan de migraciones idempotentes

### Frontend

- matriz `script -> objeto localizado -> endpoints`
- revision de `.min.js` y versionado
- revision de assets inexistentes o rutas fragiles

### Runtime

- auditoria de `debug.log`
- auditoria de `wc-logs`
- auditoria de `Network` en navegador
- matriz "error historico" vs "error reproducible actual"

### Contenido real

- dataset minimo por dominio
- relaciones reales entre entidades
- eliminacion de dependencias de demo

## Entregables por tanda

- reporte de correcciones
- listado de modulos cerrados
- listado de modulos bloqueados
- incidencias runtime pendientes
- actualizacion de matriz de salud

## Orden de ejecucion recomendado

1. Tanda 1
2. Tanda 2
3. Base de datos transversal
4. Tanda 3
5. Tanda 4
6. Tanda 5
7. Tanda 6
8. Validacion global final

## Siguiente paso realista

En el estado actual, la mayor parte del valor adicional ya no viene de seguir ampliando aliases, sino de:

1. recuperar validacion runtime estable en `localhost:10028`
2. recorrer modulo por modulo las rutas del portal ya saneadas
3. registrar solo fallos vivos y cerrar una ultima tanda basada en evidencia runtime
