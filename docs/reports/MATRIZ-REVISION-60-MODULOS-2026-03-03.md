# Matriz De Revision De Los 60 Modulos

Esta matriz parte del loader real en `includes/modules/class-module-loader.php`, que registra `60` modulos builtin.

No certifica runtime profundo de todos los modulos. Refleja el nivel real de revision aplicado en esta fase:

- `Repasado intensivo`: tocado con profundidad en portal, tabs, renderer, integraciones, UX, contratos o runtime.
- `Parcial`: revisado y ajustado en contratos, aliases, dashboards o wiring, pero no con la misma profundidad funcional.
- `Pendiente / no prioritario`: fuera del foco principal de esta fase.

## Resumen

- `Repasado intensivo`: `43`
- `Parcial`: `16`
- `Pendiente / no prioritario`: `1`

## Matriz

| Modulo | Estado | Nota breve |
| --- | --- | --- |
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
| `carpooling` | `Parcial` | Ajustes de contrato/aliases; no fue foco profundo. |
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
| `comunidades` | `Repasado intensivo` | Convertido en contenedor fuerte de satelites y mejorado en UX/rutas. |
| `bares` | `Parcial` | Tabs y rutas saneadas, pero sin pasada profunda. |
| `trading_ia` | `Parcial` | Ajustes de aliases/contratos; no prioritario para integracion contextual. |
| `dex_solana` | `Parcial` | Ajustes de aliases/contratos; no prioritario para esta fase. |
| `themacle` | `Parcial` | Mejoras de shortcodes y acciones, pero sin profundidad comparable al resto. |
| `reservas` | `Repasado intensivo` | Corregido conflicto de handlers, calendario y runtime frontend. |
| `email_marketing` | `Parcial` | Mejoras de contrato, dashboard y fallbacks, sin pasada profunda total. |
| `sello_conciencia` | `Parcial` | Dashboard saneado; no fue foco de arquitectura incremental. |
| `circulos_cuidados` | `Repasado intensivo` | Integrado como padre incremental y saneado en cron/dashboard. |
| `economia_don` | `Repasado intensivo` | Integrado como padre incremental por entidad `economia_don`. |
| `justicia_restaurativa` | `Repasado intensivo` | Ajustes previos relevantes; buen encaje contextual aunque falta mas runtime. |
| `huella_ecologica` | `Parcial` | Ajustes de acciones y contratos; no fue foco profundo. |
| `economia_suficiencia` | `Repasado intensivo` | Integrado como padre incremental y mejorado funcionalmente. |
| `energia_comunitaria` | `Repasado intensivo` | Integrado como padre incremental por comunidad energetica. |
| `saberes_ancestrales` | `Repasado intensivo` | Integrado como padre incremental por saber. |
| `biodiversidad_local` | `Repasado intensivo` | Mejorado en contratos y portal; revision relevante aunque menos profunda que otros. |
| `trabajo_digno` | `Repasado intensivo` | Ajustes relevantes de contratos y navegacion; encaja como padre incremental. |
| `recetas` | `Repasado intensivo` | Muy trabajadas en CPT, frontend, permisos e integracion contextual. |
| `campanias` | `Repasado intensivo` | Saneado en assets/navegacion y UX del portal. |
| `documentacion_legal` | `Repasado intensivo` | Integrado como padre incremental por documento. |
| `seguimiento_denuncias` | `Parcial` | Ajustes de aside/contratos, sin pasada profunda funcional. |
| `mapa_actores` | `Parcial` | Ajustes de aside y contrato, no foco profundo. |
| `encuestas` | `Pendiente / no prioritario` | Fuera del foco principal de esta fase; sin pasada equivalente al resto. |

## Lectura practica

- La parte prioritaria y reusable del ecosistema modular esta ya muy trabajada.
- Los modulos `Parcial` suelen tener puente/contrato saneado, pero no validacion funcional tan profunda.
- `encuestas` queda como el modulo menos tocado en esta fase.
