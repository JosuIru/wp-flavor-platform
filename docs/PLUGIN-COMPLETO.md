# Plugin Completo

## Resumen

`flavor-chat-ia` es una plataforma WordPress modular con foco en comunidades, administracion, contenidos, economia colaborativa, sostenibilidad, paneles de usuario y herramientas transversales.

La version declarada en el plugin es `3.1.1`.

## Navegacion rapida

- [Capas principales](#capas-principales)
- [Datos cuantitativos y criterio de lectura](#datos-cuantitativos-y-criterio-de-lectura)
- [Subsistemas mas importantes](#subsistemas-mas-importantes)
- [Que puede hacer el plugin](#que-puede-hacer-el-plugin)
- [Que no debe darse por supuesto](#que-no-debe-darse-por-supuesto)
- [Flujo conceptual](#flujo-conceptual)
- [Fuentes de verdad recomendadas](#fuentes-de-verdad-recomendadas)

## Lectura ejecutiva

| Punto | Lectura actual |
|---|---|
| Tipo de producto | Plataforma WordPress modular |
| Version declarada | `3.1.1` |
| Núcleo operativo | Loader de modulos + paneles + capas transversales |
| Estado general | Amplio, utilizable y todavia heterogeneo |
| Regla de lectura | Separar recuento estructural de estado real auditado |

## Capas principales

| Capa | Funcion |
|---|---|
| `flavor-chat-ia.php` | Bootstrap principal y carga de dependencias |
| `includes/` | Clases base, cargadores, utilidades, integraciones y frontends compartidos |
| `includes/modules/` | Modulos nativos del sistema |
| `admin/` | Pantallas de gestion, dashboards y herramientas de administracion |
| `assets/` | CSS, JS y recursos comunes |
| `addons/` | Extensiones separadas del core |
| `docs/` | Documentacion tecnica y operativa |
| `reports/` | Auditorias, hallazgos y reportes historicos o de validacion |

## Datos cuantitativos y criterio de lectura

Para evitar repetir el problema de la documentacion historica, conviene distinguir dos tipos de cifra:

- `estado real auditado`: lo que fija `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`
- `recuento estructural del loader`: lo que puede verse hoy en el arbol y en `class-module-loader.php`

### Estado real auditado

Estas son las cifras que deben tomarse como referencia cuando se habla del estado del sistema:

| Metrica | Valor |
|---|---|
| Modulos con clase principal | 59 |
| Modulos con frontend controller | 41 |
| Modulos con `views/` no vacio | 38 |
| Modulos con `templates/` no vacio | 31 |
| Modulos con assets | 54 |
| Modulos con `install.php` | 17 |
| Modulos con API propia | 22 |
| Modulos con dashboard tab | 52 |
| Modulos con widget | 38 |
| Modulos con `get_form_config()` | 13 |
| Modulos conectados a Provider/Consumer | 27 |

### Recuento estructural del loader

Ademas, el cargador actual registra `60` IDs de modulo builtin y el arbol contiene `61` directorios dentro de `includes/modules/`. Esas cifras son utiles para entender el inventario tecnico, pero no deben sustituir a la auditoria cuando se habla de completitud o madurez.

La diferencia entre `60` IDs del loader y `59` modulos con clase principal auditada debe leerse como una discrepancia operativa del inventario actual, no como prueba de que existan `60` modulos igualmente cerrados o verificables.

## Regla de lectura operativa

- Si hablas de inventario tecnico, usa el loader y el arbol.
- Si hablas de estado real, usa la auditoria vigente.
- Si hablas de experiencia funcional, valida el flujo en runtime.

## Subsistemas mas importantes

### Sistema modular

El nucleo del producto es el cargador `includes/modules/class-module-loader.php`, que registra y carga los modulos activos.

### Dashboard y shell de administracion

El plugin incluye varias capas de admin:

- dashboard principal
- dashboard unificado
- vista unificada de modulos
- asistentes de configuracion
- analitica
- exportacion e importacion
- tours guiados
- documentacion

### Frontend y paneles de usuario

Varios modulos exponen:

- templates frontend
- shortcodes
- tabs de dashboard de cliente
- widgets
- controladores frontend dedicados

### Integraciones entre modulos

Algunos modulos actuan como providers y otros como consumers para conectar contenido, relaciones y contextos de uso.

### Permisos granulares

No todo depende del rol WordPress general. El plugin define capabilities y roles especificos por modulo.

### Addons

El arbol actual incluye, al menos, estos addons:

| Addon | Estado en el arbol |
|---|---|
| `flavor-admin-assistant` | Presente |
| `flavor-advertising-pro` | Presente |
| `flavor-network-communities` | Presente |
| `flavor-restaurant-ordering` | Presente |
| `flavor-web-builder-pro` | Presente, con archivo principal marcado como `.disabled` |

## Que puede hacer el plugin

Segun los modulos que actives, el sistema puede cubrir:

- socios y membresias
- eventos y talleres
- marketplace y grupos de consumo
- reservas, tramites y transparencia
- foros, chat y red social
- podcast, radio y biblioteca
- sostenibilidad y economia comunitaria
- modulos especializados como trading, DEX o sectores empresariales

## Que no debe darse por supuesto

No todos los modulos tienen el mismo grado de homogeneidad ni la misma calidad de runtime. Hay modulos muy maduros y otros con deuda tecnica o cobertura parcial.

## Flujo conceptual

El flujo normal de trabajo es:

- activar perfil o modulos
- configurar paginas, visibilidad y permisos
- validar dashboards y frontends
- conectar modulos por integraciones cuando tenga sentido
- verificar estado real antes de desplegar promesas funcionales

## Fuentes de verdad recomendadas

Para entender el plugin completo conviene leer en este orden:

- `FILOSOFIA-PLUGIN.md`
- `GUIA-ADMINISTRACION.md`
- `ARQUITECTURA-PLUGIN.md`
- `GUIA_MODULOS.md`
- `ESTADO-REAL-PLUGIN.md`
