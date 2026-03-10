# Catalogo De Modulos

## Alcance

Este catalogo sustituye al catalogo heredado generado automaticamente que mezclaba estados antiguos, pantallas teoricas y etiquetas de "completo" que ya no eran fiables.

Su matriz sigue el inventario operativo de `60` IDs del loader, no una afirmacion de que existan `60` modulos con clase principal auditada. Para esa cifra, prevalece `docs/ESTADO-REAL-PLUGIN.md` y la auditoria vigente.

Desde ahora debe leerse junto con:

- `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`
- `reports/MATRIZ-REVISION-60-MODULOS-2026-03-03.md`
- `reports/BACKLOG-RUNTIME-MODULOS-PARCIALES-2026-03-03.md`

## Navegacion rapida

- [Resumen actual](#resumen-actual)
- [Matriz actual de los 60 modulos](#matriz-actual-de-los-60-modulos)
- [Modulos que hoy requieren mas cautela](#modulos-que-hoy-requieren-mas-cautela)
- [Modulos mejor posicionados en la fase actual](#modulos-mejor-posicionados-en-la-fase-actual)
- [Uso recomendado del catalogo](#uso-recomendado-del-catalogo)

## Como leer este documento

### `Repasado intensivo`

Modulo tocado con profundidad en portal, tabs, UX, integraciones, renderer, contratos o runtime durante la fase reciente. No significa validacion completa en produccion, pero si una pasada seria.

### `Parcial`

Modulo revisado de forma util, con puentes, contratos, aliases, dashboards o fallbacks saneados, pero sin la misma profundidad funcional que el bloque intensivo.

### `Pendiente / no prioritario`

Modulo fuera del foco principal de la fase reciente.

## Resumen actual

| Estado | Cantidad |
|---|---|
| `Repasado intensivo` | 43 |
| `Parcial` | 16 |
| `Pendiente / no prioritario` | 1 |

## Criterio de fiabilidad

Este catalogo prioriza:

- estado de revision reciente
- trazabilidad del trabajo aplicado
- honestidad sobre lo que sigue parcial

No intenta deducir automaticamente:

- que todos los flujos de negocio estan cerrados
- que todas las rutas han sido probadas en runtime hoy
- que una pantalla existente equivale a madurez alta

## Matriz actual de los 60 modulos

Estas `60` entradas representan el catalogo operativo actual del loader. No sustituyen la cifra auditada de `59` modulos con clase principal ni deben leerse como garantia de madurez equivalente.

| Modulo | Estado actual | Nota breve |
|---|---|---|
| `woocommerce` | `Parcial` | Ajustes de contrato y fallbacks; no fue foco de integracion incremental. |
| `banco_tiempo` | `Repasado intensivo` | Mucho trabajo en portal, tabs, UX, acciones, contexto y enlace con comunidades. |
| `marketplace` | `Repasado intensivo` | Bastante trabajo en acciones, rutas y patron padre/satelite por anuncio. |
| `grupos_consumo` | `Repasado intensivo` | Muy trabajado en rutas, `Pedido actual`, tabs cruzadas, UX e integraciones. |
| `facturas` | `Parcial` | Mejor puente del portal y dashboard, pero sin pasada runtime profunda. |
| `fichaje_empleados` | `Parcial` | Ajustes de tabs y dashboard; sin gran pasada funcional profunda. |
| `eventos` | `Repasado intensivo` | Integrado fuerte con comunidades y contexto por `comunidad_id`. |
| `socios` | `Parcial` | Navegacion y tabs saneadas; no fue modulo prioritario de integracion incremental. |
| `incidencias` | `Repasado intensivo` | Mejorado en frontend, acciones y patron padre/satelite por incidencia. |
| `participacion` | `Repasado intensivo` | Mucho trabajo en portal y satelites por propuesta. |
| `presupuestos_participativos` | `Repasado intensivo` | Mejorado en contratos y patron padre/satelite por proyecto. |
| `avisos_municipales` | `Repasado intensivo` | Integrado como padre incremental por aviso. |
| `advertising` | `Repasado intensivo` | Integrado como padre incremental por anuncio. |
| `ayuda_vecinal` | `Repasado intensivo` | Mejoras reales en frontend, REST y UX. |
| `biblioteca` | `Repasado intensivo` | Integraciones y tabs; ya actua como padre incremental. |
| `bicicletas_compartidas` | `Parcial` | Ajustes de aliases y routing; sin pasada profunda de runtime. |
| `carpooling` | `Parcial` | Ajustes de contrato y aliases; no fue foco profundo. |
| `chat_grupos` | `Repasado intensivo` | Muy trabajado en UX, assets, contexto por entidad e integraciones. |
| `chat_interno` | `Repasado intensivo` | Mucho saneamiento en assets, rutas, errores y runtime del portal. |
| `chat_estados` | `Repasado intensivo` | Dashboard, widget y wiring modernizados. |
| `compostaje` | `Repasado intensivo` | Solicitud real, dashboard y puente moderno del portal. |
| `cursos` | `Repasado intensivo` | Mejorado en contratos y como padre incremental contextual. |
| `empresarial` | `Repasado intensivo` | Dashboard y wiring mejorados; bastante trabajo estructural. |
| `espacios_comunes` | `Repasado intensivo` | Corregidos contratos JS/PHP y partes runtime. |
| `huertos_urbanos` | `Repasado intensivo` | Integrado como padre incremental por huerto. |
| `multimedia` | `Repasado intensivo` | Muy trabajado en frontend, admin, rutas, UX e integraciones contextuales. |
| `parkings` | `Repasado intensivo` | Contratos, shortcodes y acciones bastante saneados. |
| `podcast` | `Repasado intensivo` | Ya no es placeholder; integrado como padre incremental por serie. |
| `radio` | `Repasado intensivo` | Contratos y patron padre/satelite por programa. |
| `reciclaje` | `Repasado intensivo` | Corregidos nombres de tabla, frontend y contratos principales. |
| `red_social` | `Repasado intensivo` | Muy trabajado en rutas, tabs, UX, integraciones y contexto por entidad. |
| `talleres` | `Repasado intensivo` | Integrado como padre incremental por taller. |
| `tramites` | `Repasado intensivo` | Portal y JS saneados; varias rutas y contratos corregidos. |
| `transparencia` | `Repasado intensivo` | Mejorado en puente del portal y tabs contextuales modernas. |
| `colectivos` | `Repasado intensivo` | Integrado como padre incremental por colectivo. |
| `foros` | `Repasado intensivo` | Mucho trabajo en esquema real, mappings e integracion contextual. |
| `clientes` | `Parcial` | Ajustes de dashboard y aliases; no fue foco profundo de runtime. |
| `comunidades` | `Repasado intensivo` | Convertido en contenedor fuerte de satelites y mejorado en UX y rutas. |
| `bares` | `Parcial` | Tabs y rutas saneadas, pero sin pasada profunda. |
| `trading_ia` | `Parcial` | Ajustes de aliases y contratos; no prioritario para integracion contextual. |
| `dex_solana` | `Parcial` | Ajustes de aliases y contratos; no prioritario para esta fase. |
| `themacle` | `Parcial` | Mejoras de shortcodes y acciones, pero sin profundidad comparable al resto. |
| `reservas` | `Repasado intensivo` | Corregido conflicto de handlers, calendario y runtime frontend. |
| `email_marketing` | `Parcial` | Mejoras de contrato, dashboard y fallbacks, sin pasada profunda total. |
| `sello_conciencia` | `Parcial` | Dashboard saneado; no fue foco de arquitectura incremental. |
| `circulos_cuidados` | `Repasado intensivo` | Integrado como padre incremental y saneado en cron y dashboard. |
| `economia_don` | `Repasado intensivo` | Integrado como padre incremental por entidad `economia_don`. |
| `justicia_restaurativa` | `Repasado intensivo` | Ajustes previos relevantes; buen encaje contextual aunque falta mas runtime. |
| `huella_ecologica` | `Parcial` | Ajustes de acciones y contratos; no fue foco profundo. |
| `economia_suficiencia` | `Repasado intensivo` | Integrado como padre incremental y mejorado funcionalmente. |
| `energia_comunitaria` | `Repasado intensivo` | Integrado como padre incremental por comunidad energetica. |
| `saberes_ancestrales` | `Repasado intensivo` | Integrado como padre incremental por saber. |
| `biodiversidad_local` | `Repasado intensivo` | Mejorado en contratos y portal; revision relevante aunque menos profunda que otros. |
| `trabajo_digno` | `Repasado intensivo` | Ajustes relevantes de contratos y navegacion; encaja como padre incremental. |
| `recetas` | `Repasado intensivo` | Muy trabajadas en CPT, frontend, permisos e integracion contextual. |
| `campanias` | `Repasado intensivo` | Saneado en assets, navegacion y UX del portal. |
| `documentacion_legal` | `Repasado intensivo` | Integrado como padre incremental por documento. |
| `seguimiento_denuncias` | `Parcial` | Ajustes de aside y contratos, sin pasada profunda funcional. |
| `mapa_actores` | `Parcial` | Ajustes de aside y contrato, no foco profundo. |
| `encuestas` | `Pendiente / no prioritario` | Fuera del foco principal de esta fase; sin pasada equivalente al resto. |

## Modulos que hoy requieren mas cautela

Si el objetivo es desplegar o validar funcionalidad real cuanto antes, conviene revisar primero estos modulos antes de prometer alcance:

- `facturas`
- `clientes`
- `socios`
- `bares`
- `email_marketing`
- `fichaje_empleados`
- `sello_conciencia`
- `woocommerce`
- `themacle`
- `bicicletas_compartidas`
- `carpooling`
- `huella_ecologica`
- `seguimiento_denuncias`
- `mapa_actores`
- `trading_ia`
- `dex_solana`
- `encuestas`

## Modulos mejor posicionados en la fase actual

El bloque con mas trabajo reciente y mejor trazabilidad de cambios incluye:

- `banco_tiempo`
- `marketplace`
- `grupos_consumo`
- `eventos`
- `incidencias`
- `participacion`
- `presupuestos_participativos`
- `biblioteca`
- `chat_grupos`
- `chat_interno`
- `compostaje`
- `cursos`
- `espacios_comunes`
- `multimedia`
- `podcast`
- `radio`
- `red_social`
- `tramites`
- `transparencia`
- `comunidades`
- `reservas`
- `economia_suficiencia`
- `energia_comunitaria`
- `recetas`

## Uso recomendado del catalogo

Este documento sirve para:

- saber si un modulo fue trabajado intensivamente o solo parcialmente
- priorizar validacion runtime
- ubicar que modulos pueden entrar antes en una tanda de QA

No debe usarse para:

- afirmar que un modulo esta totalmente cerrado sin prueba runtime
- deducir que toda pantalla documentada existe y funciona exactamente igual que antes
- sustituir la auditoria vigente
