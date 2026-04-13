# Backlog Priorizado de Produccion 2026-03-04

## Uso de este backlog

Este backlog convierte el plan maestro en trabajo ejecutable. Cada item debe asociarse a uno o varios modulos y cerrarse con evidencia runtime cuando aplique.

## P0. Bloqueadores de produccion

### 1. Cerrar validacion runtime estable del portal

Impacto:

- sin HTTP estable no puede certificarse cierre real

Salida esperada:

- instancia local respondiendo de forma consistente
- smoke tests manuales o CLI reproducibles sobre rutas criticas

### 2. Cerrar routing y renderers inconsistentes restantes

Impacto:

- tabs que caen en views admin o renderers genericos rompen flujos completos

Salida esperada:

- acciones y tabs principales renderizadas por su modulo real
- sin shortcodes legacy visibles en HTML de usuario

### 3. Cerrar instalacion y contratos de datos

Impacto:

- tablas ausentes o columnas desalineadas invalidan cualquier validacion frontend

Salida esperada:

- inventario de tablas requeridas
- modulos bloqueados por esquema identificados y clasificados

## P1. Flujos principales pendientes

### 4. Cerrar ola de modulos criticos del portal

Modulos:

- `marketplace`
- `tramites`
- `reservas`
- `foros`
- `colectivos`
- `socios`
- `participacion`
- `eventos`

Salida esperada:

- ruta principal
- una accion principal
- permisos
- estado vacio util
- evidencia runtime

Pendientes concretos de ola 2:

- `marketplace` `P1`
  - cerrar flujos secundarios visibles del portal (`mis-anuncios`, `favoritos`, `categorias`) con contrato final consistente
  - revisar enlaces auxiliares legacy restantes en API/formateadores que aun usan singles publicos del CPT
- `marketplace` `P2`
  - mejorar estados vacios y mensajes del detalle cuando el `anuncio_id` no existe
  - revisar duplicidades visuales de tabs y widgets del dashboard

- `tramites` `P1`
  - validar funcionalmente `iniciar`, `mis-tramites`, `seguimiento` y `citas` con datos reales o dataset minimo
  - confirmar permisos y transiciones de login en cada subflujo
- `tramites` `P2`
  - mejorar vacios del catalogo y secundarios para que siempre ofrezcan salida operativa

- `reservas` `P1`
  - validar runtime real de `recursos`, `mis-reservas` y `nueva-reserva`
  - confirmar que filtros `estado` y CTAs del dashboard aterrizan en vistas coherentes
- `reservas` `P2`
  - mejorar UX de estados vacios y mensajes sin datos

- `foros` `P1`
  - validar lectura de foro y navegacion de hilos desde rutas del portal
  - confirmar que no quedan vistas admin incrustadas en tabs secundarios
- `foros` `P2`
  - mejorar estados vacios de actividad y listado para que no sean texto muerto

- `colectivos` `P1`
  - validar detalle, crear y mis colectivos con datos reales
  - cerrar referencias legacy restantes a `/colectivos/...` fuera del flujo principal
- `colectivos` `P2`
  - mejorar vacios de listado, proyectos y asambleas

- `participacion` `P1`
  - validar el cruce completo con `foros` y otras acciones visibles del modulo
  - comprobar que no quedan enlaces malformados o rutas mezcladas
- `participacion` `P2`
  - ajustar estados vacios y mensajes contextuales

- `eventos` `P1`
  - validar alta, detalle y gestion de `mis-inscripciones` con datos reales
  - confirmar que calendarios y widgets secundarios usan solo rutas portal
- `eventos` `P2`
  - mejorar vacios y mensajes de no-inscripcion o sin eventos

- `socios` `P1`
  - validar `unirse`, `mi-perfil` y `mis-cuotas` con permisos reales
  - comprobar coherencia entre dashboard tab, widget y vistas secundarias
- `socios` `P2`
  - mejorar vacios y copy de estados sin membresia o sin cuotas

### 5. Validar login y permisos por flujo

Impacto:

- un modulo puede cargar bien y seguir roto por acceso o redirect incorrecto

Salida esperada:

- login correcto
- redirects correctos
- vistas privadas y publicas diferenciadas

### 6. Cerrar widgets, dashboards y CTAs

Impacto:

- un modulo aparentemente funcional sigue roto si su dashboard o widget lleva a enlaces muertos

Salida esperada:

- widgets y quick actions alineados con rutas reales

Pendiente residual ya identificado:

- revisar widgets secundarios de ola 2 para eliminar CTAs decorativos o enlaces a tabs no validados aun

## P2. Coherencia y UX residual

### 7. Mejorar estados vacios y mensajes de permisos

Objetivo:

- que no queden pantallas mudas ni bloques sin salida operativa

Prioridad inmediata:

- `reservas`
- `foros`
- `colectivos`
- `eventos`
- `socios`

### 8. Eliminar residuos legacy restantes

Objetivo:

- reducir rutas heredadas, aliases ambiguos y markup de admin incrustado en frontend

Residuales conocidos:

- referencias auxiliares a singles publicos o `get_permalink()` en `marketplace`
- rutas legacy residuales en vistas no principales de `colectivos`
- posibles tabs secundarios no validados en `eventos`, `socios` y `reservas`

### 9. Sembrar dataset minimo por dominio funcional

Objetivo:

- validar con datos minimamente realistas y no solo con estructuras vacias

## P3. Release, documentacion y gobierno

### 10. Consolidar documentacion canonica

Objetivo:

- una sola narrativa valida para estado real, backlog, plan y cierre

### 11. Clasificar modulos fuera de release

Objetivo:

- no arrastrar a produccion modulos experimentales o dependientes de entorno sin evidencia

### 12. Preparar informe final de go/no-go

Objetivo:

- decidir produccion con checklist y evidencias, no por sensacion de avance

## Secuencia recomendada

1. estabilizar entorno runtime
2. cerrar `P0` transversal
3. ejecutar ola 2 de modulos criticos
4. actualizar matriz
5. ejecutar olas siguientes solo sobre modulos que realmente entren en release
