# Guia de Contextos para Dashboards y Modulos

Fecha: 2026-03-03

## Objetivo

Definir un vocabulario comun para `client_contexts` y `admin_contexts` y su uso en:

- `Mi-panel`
- `Dashboard unificado`
- `Plantillas`
- futuras recomendaciones del compositor

La meta no es ocultar modulos, sino priorizar mejor segun la vista y el momento de uso.

## Regla general

Un contexto debe describir una intencion, espacio o capa funcional reconocible.

Debe evitarse usar:

- nombres demasiado tecnicos
- slugs internos de implementacion
- etiquetas redundantes o casi iguales

## Contextos canonicos recomendados

### Contextos de espacio

- `portal`
- `mi_panel`
- `cuenta`
- `comunidad`

### Contextos de base organizativa

- `socios`
- `membresia`
- `colectivos`
- `asociacion`

### Contextos operativos

- `energia`
- `consumo`
- `cuidados`
- `eventos`
- `agenda`
- `actividad`

### Contextos transversales

- `gobernanza`
- `transparencia`
- `impacto`
- `sostenibilidad`
- `aprendizaje`
- `cultura`

### Contextos culturales o de intencion

- `saberes`
- `suficiencia`
- `solidaridad`
- `coordinacion`

## Tabla recomendada por tipo de modulo

### Modulos base

#### `comunidades`

- `comunidad`
- `miembro`
- `coordinacion`

#### `socios`

- `socios`
- `membresia`
- `cuenta`
- `comunidad`

#### `colectivos`

- `colectivos`
- `asociacion`
- `gobernanza`
- `comunidad`

### Modulos verticales

#### `energia_comunitaria`

- `energia`
- `comunidad`
- `gestion`

#### `grupos_consumo`

- `consumo`
- `comunidad`
- `coordinacion`

#### `banco_tiempo`

- `cuidados`
- `intercambio`
- `comunidad`

#### `ayuda_vecinal`

- `cuidados`
- `comunidad`
- `solidaridad`

#### `eventos`

- `eventos`
- `agenda`
- `actividad`
- `comunidad`

### Modulos transversales

#### `participacion`

- `participacion`
- `gobernanza`
- `comunidad`

#### `transparencia`

- `transparencia`
- `gobernanza`
- `rendicion_cuentas`

#### `huella_ecologica`

- `impacto`
- `sostenibilidad`
- `energia`
- `consumo`

#### `economia_suficiencia`

- `consumo`
- `suficiencia`
- `aprendizaje`
- `comunidad`

#### `saberes_ancestrales`

- `aprendizaje`
- `comunidad`
- `cultura`
- `saberes`

## Reglas de consistencia

- Un modulo no deberia declarar mas de 4 contextos salvo justificacion clara.
- Si un contexto no cambia la priorizacion de ninguna vista, sobra.
- `comunidad` sirve para modulos con pertenencia o vida compartida real, no como etiqueta comodin.
- `gobernanza` debe reservarse para decision, supervision, rendicion o reglas colectivas.
- `aprendizaje` debe reservarse para modulos que ensenian o transforman cultura, no para cualquier ayuda textual.

## Uso por pantalla

### Mi-panel

Debe usar `client_contexts` para:

- ordenar ecosistemas
- ordenar capas transversales
- marcar relevancia contextual

No debe usarlo para ocultar informacion critica por defecto.

### Dashboard unificado

Debe usar preferentemente `admin_contexts` como senal de contexto de vista actual.

Si un modulo no declara `admin_contexts`, puede usar `client_contexts` como fallback.

Ejemplos:

- si la vista esta cerca de `comunidad`, subir `comunidades`, `eventos`, `ayuda_vecinal`
- si la vista esta cerca de `energia`, subir `energia_comunitaria` y `huella_ecologica`
- si la vista esta cerca de `gobernanza`, subir `participacion`, `transparencia`, `colectivos`

### Plantillas

Puede reutilizar estos contextos como:

- filtros blandos
- capacidades sugeridas
- explicacion del perfil

Pero no debe mezclar contextos con sectores o tipos de organizacion.

## Antipatrones

Evitar:

- `general`
- `otros`
- `modulo`
- `dashboard`
- `gestion` si no hay una capa operativa real
- duplicar `participacion` y `gobernanza` cuando solo uno aplica

## Criterio de evolucion

Si aparece un contexto nuevo:

1. debe aportar una diferencia real de orden o recomendacion
2. debe documentarse aqui
3. debe revisarse si ya estaba cubierto por otro contexto existente

## Relacion con otros documentos

- Este documento complementa `ESTANDAR-METADATOS-ECOSISTEMA-MODULOS.md`
- Este documento aterriza la parte de dashboards definida en `ARQUITECTURA-DASHBOARDS-ECOSISTEMA.md`
