# Guia de Severidad en Dashboards y Herramientas

## Objetivo

Definir una semantica comun de prioridad para:

- `mi-portal`
- `dashboard admin unificado`
- bloques de `Herramientas de hoy`
- bloques de `Que hacer ahora`
- `Señales del nodo`
- `Siguiente foco`

La severidad no sustituye al rol del modulo ni al tipo de herramienta. Es una capa adicional para ordenar la atencion.

## Escala canonica

Se usan tres niveles:

- `attention`
- `followup`
- `stable`

Su lectura humana en interfaz es:

- `Atencion`
- `Seguimiento`
- `Estable`

## Significado operativo

### Atencion

Usar cuando la accion o señal requiere respuesta proxima o tiene impacto operativo directo.

Ejemplos:

- una herramienta principal de operacion del nodo
- una incidencia o aviso con gravedad alta
- una accion prevista para hoy o en menos de 24 horas
- una capa transversal pendiente que bloquea o degrada un ecosistema activo

### Seguimiento

Usar cuando la accion o señal requiere revision, coordinacion o continuidad, pero no implica urgencia inmediata.

Ejemplos:

- tareas de coordinacion
- revisiones de widgets o accesos secundarios
- avisos informativos relevantes
- acciones previstas en los proximos 2 o 3 dias

### Estable

Usar cuando la accion o señal es positiva, de consulta o de baja urgencia.

Ejemplos:

- herramientas de consulta o comprension
- confirmaciones o mensajes de estado correcto
- acciones futuras sin urgencia

## Relacion con la semantica del sistema

La severidad convive con otras capas y no debe mezclarse con ellas:

- `role`: `base`, `vertical`, `transversal`
- `kind`: `Coordinar`, `Operar`, `Entender`
- `contexts`: `comunidad`, `gobernanza`, `energia`, `cuidados`, etc.

La regla es:

- `role` explica la posicion del modulo en el ecosistema
- `kind` explica el tipo de accion o widget
- `contexts` explica el dominio o foco
- `severity` explica cuanta atencion merece ahora

## Criterio actual en mi-portal

### Herramientas de hoy

La severidad se calcula hoy con una heuristica simple:

- `Operar` o puntuacion muy alta => `Atencion`
- `Coordinar` o puntuacion media-alta => `Seguimiento`
- el resto => `Estable`

Esto sirve para priorizar sin exigir que cada modulo publique todavia una urgencia propia.

### Señales del nodo

Mapeo actual:

- `error` => `Atencion`
- `warning` => `Atencion`
- `info` => `Seguimiento`
- `success` => `Estable`

### Siguiente foco

Mapeo actual por proximidad temporal:

- hasta 24 horas => `Atencion`
- hasta 3 dias => `Seguimiento`
- mas adelante => `Estable`

## Criterio actual en dashboard admin

### Que hacer ahora

El dashboard unificado usa hoy esta lectura:

- `Foco` sobre ecosistema relevante => `Atencion`
- `Completar` capas pendientes => `Atencion`
- `Revisar` widget o acceso secundario => `Seguimiento`

El estado `Estable` queda reservado para acciones positivas, informativas o ya normalizadas cuando se amplie esta capa.

## Criterio visual

La codificacion visual vigente es:

- `Atencion`: ambar o naranja suave
- `Seguimiento`: azul
- `Estable`: verde

Esta escala debe mantenerse tanto en portal como en admin para evitar lecturas contradictorias.

## Reglas de uso

- No usar `Atencion` como estado por defecto.
- No usar severidad para describir identidad del modulo.
- No usar severidad como sustituto de permisos o estados de negocio.
- Si un modulo ya conoce su urgencia real, esa senal debe prevalecer sobre la heuristica generica.
- Si no existe dato real de urgencia, usar la heuristica comun antes que inventar estados modulo a modulo.

## Recomendacion para modulos futuros

Cuando un modulo tenga eventos propios de prioridad, deberia exponer una lectura compatible con:

- `attention`
- `followup`
- `stable`

Y dejar que el dashboard la consuma directamente.

Mientras no exista esa capa, el sistema seguira usando severidad derivada de:

- tipo de herramienta
- cercania temporal
- tipo de notificacion
- capas pendientes del ecosistema

## Estado actual

Esta semantica ya esta reflejada en:

- `mi-portal`
- `Herramientas de hoy`
- `Favoritas`
- `Señales del nodo`
- `Siguiente foco`
- `dashboard admin unificado`
- `Que hacer ahora`

## Implementacion actual en codigo

La fuente comun de verdad para severidad ya existe en:

- `includes/class-dashboard-severity.php`

Hoy la consumen al menos:

- `includes/class-portal-shortcodes.php`
- `admin/views/unified-dashboard.php`

La utilidad centraliza:

- etiquetas visibles
- severidad por herramienta
- severidad por tipo de notificacion
- severidad por cercania temporal
- severidad para acciones ejecutivas del dashboard admin

## Siguiente paso recomendado

El siguiente paso deseable es que modulos y widgets con urgencia real publiquen su propia severidad compatible con esta escala, para que el sistema deje de depender solo de heuristicas compartidas.
