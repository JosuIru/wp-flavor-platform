# Arquitectura de Dashboards por Ecosistema

## Objetivo

Formalizar una lectura comun para:

- `Plantillas` en el compositor
- `Dashboard unificado` de admin
- `Mi-panel` o dashboard de cliente

La unidad principal deja de ser una lista plana de widgets o modulos y pasa a ser:

`ecosistema -> modulo base -> satelites verticales -> capas transversales`

## Problema que resuelve

El sistema ya tenia:

- modulos bien separados por dominio
- perfiles o plantillas bastante ricos
- dashboards con widgets y accesos

Pero cada pantalla leia la estructura de forma distinta:

- `Plantillas` pensaba en casos de uso
- `Admin` pensaba en categorias de widget
- `Mi-panel` pensaba en widgets y atajos sueltos

Eso dificultaba leer el producto como ecosistema.

## Modelo de lectura

### 1. Modulo base

Es el modulo que aporta contexto principal.

Ejemplos:

- `comunidades`
- `socios`
- `colectivos`

Responsabilidades:

- identidad
- pertenencia
- contexto
- espacio raiz de operacion

### 2. Satelites verticales

Son modulos operativos que cuelgan del contexto base.

Ejemplos:

- `energia_comunitaria`
- `grupos_consumo`
- `banco_tiempo`
- `ayuda_vecinal`
- `eventos`

Responsabilidades:

- flujos concretos
- datos de operacion
- tareas
- actividad especializada

### 3. Capas transversales

Son modulos que no aportan tanto flujo propio como gobernanza, medicion, aprendizaje o soporte cultural.

Ejemplos:

- `participacion`
- `transparencia`
- `huella_ecologica`
- `saberes_ancestrales`
- `economia_suficiencia`

Responsabilidades:

- medir
- gobernar
- enseniar
- reforzar coherencia ecosistemica

## Regla de jerarquia

Toda interfaz principal debe poder responder:

1. cual es el modulo base activo
2. que satelites verticales cuelgan de ese modulo
3. que capas transversales ya estan activas
4. que capas transversales aun faltan o son recomendadas

## Aplicacion por pantalla

### Plantillas

`Plantillas` es la puerta de entrada del producto.

Debe mostrar:

- capas del perfil
- capacidades del ecosistema
- modulos requeridos
- capas recomendadas

No debe quedarse en:

- numero de modulos
- icono
- descripcion breve

### Dashboard unificado de admin

Debe organizarse en este orden:

1. resumen de ecosistemas activos
2. grupos por modulo base
3. widgets de cada ecosistema
4. capas transversales activas o sugeridas
5. categorias legacy como capa secundaria o fallback

La categoria de widget sigue siendo util, pero ya no debe ser la lectura principal.

### Mi-panel de cliente

Debe organizarse en este orden:

1. resumen personal
2. mis ecosistemas activos
3. satelites por ecosistema
4. capas transversales relacionadas
5. actividad reciente

La pregunta principal de `Mi-panel` no es "que widgets tengo", sino "en que ecosistemas participo".

## Metadata minima necesaria

### En modulo

Ya existe el contrato base:

- `module_role`
- `depends_on`
- `supports_modules`
- `measures_modules`
- `governs_modules`
- `teaches_modules`
- `base_for_modules`

### Recomendacion adicional para dashboards

Conviene formalizar tambien:

- `dashboard_parent_module`
- `dashboard_satellite_priority`
- `dashboard_transversal_priority`
- `dashboard_client_contexts`

Si no existe esta metadata adicional, se puede derivar desde `depends_on` y `module_role`, pero eso debe considerarse fallback, no fuente principal.

## Reglas de render

### Reglas para admin

- Un widget con `module` debe poder ubicarse dentro de un ecosistema
- Si el modulo es `base`, el widget pertenece a su propio grupo
- Si el modulo es `vertical` y depende de otro, se agrupa bajo ese padre
- Si el modulo es `transversal`, aparece como capa transversal del ecosistema relacionado

### Reglas para cliente

- Los accesos y widgets de un modulo vertical deben mostrarse dentro del ecosistema padre
- Las capas transversales activas deben ser clicables
- Las capas transversales sugeridas deben llevar a activacion, compositor o documentacion segun contexto

## Criterio UX

No mostrar primero una lista plana si existe una estructura ecosistemica clara.

Priorizar:

1. contexto
2. jerarquia
3. accion
4. detalle

## Estado actual ya implementado

### Ya visible

- `Plantillas` enriquecidas con capacidades y recomendaciones
- filtro por capacidad en compositor
- `Mi-panel` con bloque `Mis ecosistemas activos`
- `Dashboard unificado` con resumen y grupos por ecosistema
- capas transversales visibles como activas o sugeridas

### Pendiente

- ordenar toda la actividad y accesos por ecosistema
- hacer que sugerencias lleven siempre al destino mas util
- formalizar metadata adicional de dashboard
- documentar ejemplos canonicos por modulo

## Recomendacion para modulos nuevos

Cuando se cree un modulo nuevo:

1. declarar su `module_role`
2. declarar sus relaciones ecosistemicas
3. decidir si cuelga de un modulo base o si es transversal
4. declarar como quiere aparecer en dashboard admin
5. declarar como quiere aparecer en `Mi-panel`

Si un modulo no puede responder a esas cinco preguntas, todavia no esta bien integrado en el ecosistema.
