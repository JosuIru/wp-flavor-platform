# Arquitectura del Plugin

## Vista de conjunto

La arquitectura combina un bootstrap central con modulos autocontenidos y varias capas administrativas. No es un sistema minimalista, pero si sigue un reparto de responsabilidades reconocible.

## Capas tecnicas

| Capa | Responsabilidad |
|---|---|
| Bootstrap principal | Declarar constantes, cargar dependencias y enganchar acciones base |
| Cargadores y managers | Descubrir modulos, addons, tabs, paginas y metadatos |
| Clases base y traits | Reutilizar permisos, notificaciones, integraciones, admin UI y widgets |
| Modulos | Implementar logica de negocio vertical |
| Frontend controllers y templates | Exponer experiencias de usuario por modulo |
| Admin classes y views | Construir paneles de gestion y shell administrativo |
| APIs y AJAX | Exponer operaciones a interfaz web, movil o panel admin |

## Bootstrap

El archivo `flavor-chat-ia.php` es el punto de entrada. Desde ahi se cargan:

- clases nucleares
- documentacion y API docs
- sistemas admin
- cargadores de modulos y dashboards
- utilidades transversales

La auditoria vigente identifica que el bootstrap sigue siendo grande y con algunas cargas duplicadas a nivel conceptual, aunque `require_once` evita colisiones directas.

## Sistema modular

El loader real esta en:

- `includes/modules/class-module-loader.php`

Ese loader registra 60 modulos builtin y puede aceptar ampliaciones via filtros.

Cada modulo suele vivir en:

- `includes/modules/{slug}/`

La forma mas habitual de un modulo es:

- clase principal `class-*-module.php`
- `install.php` opcional
- `frontend/` opcional
- `views/` opcional
- `templates/` opcional
- `assets/` opcional
- API, dashboard tab o widget segun el caso

## Dashboards

El plugin trabaja con varias nociones de dashboard:

- dashboard administrativo general
- dashboard unificado por ecosistemas y contextos
- tabs o widgets de modulo
- paneles de usuario o cliente

Eso explica por que hay varias clases y vistas relacionadas con dashboards en `admin/`, `includes/admin/` e `includes/modules/`.

## Integraciones

No todos los modulos estan aislados. Algunos proveen contenido y otros lo consumen. La arquitectura de integraciones se apoya en:

- `trait-module-integrations.php`
- `class-integration-registry.php`
- `config-integrations.php`

## Permisos

La capa de permisos no se reduce a roles WordPress por defecto. Se extiende con:

- capabilities por modulo
- helpers de verificacion
- asignacion de roles funcionales

## Frontend

El frontend mezcla varios mecanismos:

- shortcodes
- templates
- controladores dedicados
- tabs de dashboards
- widgets

Por eso la homogeneidad entre modulos no es total. La arquitectura es funcional, pero conviven varios patrones de implementacion.

## Riesgos estructurales conocidos

Segun la auditoria vigente, los principales riesgos arquitectonicos son:

- bootstrap sobredimensionado
- instalacion de tablas repartida entre capas
- documentacion historica contradictoria
- variacion de patrones entre modulos
- desigualdad en formularios y contratos frontend

## Como leer esta arquitectura correctamente

La mejor manera de entender el sistema no es buscar un unico framework interno perfecto, sino reconocer estas piezas:

- un core compartido
- un conjunto amplio de modulos verticales
- capas transversales reutilizables
- deuda tecnica acumulada pero acotable si se trabaja con criterio
