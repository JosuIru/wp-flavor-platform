# Funcionalidades Compartidas

## Alcance

En este documento, "funcionalidades compartidas" no significa solo microfeatures de una entidad. Se refiere a cualquier capacidad transversal que varios modulos reutilizan.

## Por que importan

El plugin seria inmanejable si cada modulo resolviera por su cuenta:

- permisos
- dashboards
- integraciones
- notificaciones
- formularios
- documentacion
- vistas de administracion

Las capas compartidas existen para evitar esa duplicacion.

## Bloques transversales principales

| Bloque | Que resuelve |
|---|---|
| Permisos y roles | Control de acceso por modulo y accion |
| Dashboard tabs y widgets | Presencia del modulo en paneles |
| Integraciones provider/consumer | Relacion entre contenidos o entidades de modulos distintos |
| Admin UI y paginas de modulo | Insercion en el panel unificado |
| Notificaciones | Mensajes y alertas comunes |
| Frontend actions y formularios | Contratos de acciones y formularios reutilizables |
| Metadatos de modulo | Nombre, descripcion, icono, color, visibilidad y contexto |
| API y AJAX | Exposicion de operaciones al frontend o apps |

## Funcionalidades compartidas visibles para usuario final

Segun el modulo, varias experiencias se repiten:

- tabs en dashboard
- widgets
- formularios
- listados
- acciones contextuales
- contadores o KPIs
- bloques de actividad o seguimiento

## Funcionalidades compartidas para administracion

En admin se repiten especialmente:

- menus unificados
- paneles de permisos
- sistemas de analitica
- health checks
- documentacion integrada
- exportacion e importacion

## Sistema de integraciones

Parte importante de la funcionalidad compartida es la capacidad de relacionar modulos. Ejemplos tipicos:

- un consumer acepta contenido de un provider
- un modulo aparece como satelite funcional de otro
- un dashboard agrupa varios modulos en un mismo contexto

## Formularios y acciones

La auditoria vigente deja claro que esta es una zona desigual:

- no todos los modulos implementan `get_form_config()`
- el frontend no sigue un unico patron perfecto
- hay mezcla de shortcodes, templates y controladores dedicados

Eso no invalida el sistema, pero obliga a validar modulo por modulo cuando un flujo dependa mucho de formularios.

## Permisos como funcionalidad transversal

La seguridad operativa no debe verse como un anexo. El sistema compartido de permisos condiciona:

- que botones aparecen
- que endpoints aceptan una accion
- que administradores intermedios pueden gestionar un modulo

## Regla de implementacion

Cuando se crea o corrige un modulo, conviene apoyarse en las capas compartidas antes de inventar una nueva solucion local.

## Documentos relacionados

- `GUIA_MODULOS.md`
- `INTEGRACIONES.md`
- `PERMISSIONS-USAGE.md`
- `ARQUITECTURA-PLUGIN.md`
