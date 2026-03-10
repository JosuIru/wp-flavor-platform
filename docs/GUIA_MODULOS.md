# Guia de Modulos

## Resumen

El sistema modular es el nucleo operativo de `flavor-chat-ia`. El loader actual registra 60 IDs de modulo, y la referencia canonica de estado real pasa a ser la auditoria de `2026-03-04`, que refleja el arbol actual tras una tanda amplia de cambios.

## Navegacion rapida

- [Que es un modulo](#que-es-un-modulo)
- [Estructura habitual](#estructura-habitual)
- [Inventario actual por capacidades estructurales](#inventario-actual-por-capacidades-estructurales)
- [Familias funcionales](#familias-funcionales)
- [Nivel de madurez](#nivel-de-madurez)
- [Como decidir que modulo activar](#como-decidir-que-modulo-activar)
- [Regla operativa](#regla-operativa)

## Lectura ejecutiva

| Punto | Lectura actual |
|---|---|
| IDs en el loader | `60` |
| Modulos con clase principal | `59` |
| Criterio de estado | Auditoria `2026-03-04` |
| Riesgo de lectura | Confundir existencia con madurez |
| Recomendacion | Activar pocos modulos bien validados |

## Que es un modulo

Un modulo suele encapsular:

- identidad funcional
- datos propios
- logica de negocio
- pantallas o templates
- integracion con dashboards
- permisos
- acciones frontend o API

## Estructura habitual

La forma mas comun es:

| Pieza | Papel |
|---|---|
| `class-*-module.php` | Clase principal |
| `install.php` | Creacion o ajuste de tablas |
| `frontend/` | Controladores frontend |
| `views/` | Vistas admin |
| `templates/` | Plantillas de usuario |
| `assets/` | CSS y JS |
| `class-*-api.php` | Endpoints REST propios |
| `class-*-dashboard-tab.php` | Tab de dashboard |
| `class-*-dashboard-widget.php` | Widget |

## Inventario actual por capacidades estructurales

Las cifras siguientes siguen el criterio de la auditoria `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`:

| Metrica | Valor |
|---|---|
| Modulos con clase principal | 59 |
| Con frontend controller | 41 |
| Con `views/` | 38 |
| Con `templates/` | 31 |
| Con assets | 54 |
| Con `install.php` | 17 |
| Con API propia | 22 |
| Con dashboard tab | 52 |
| Con widget | 38 |
| Con `get_form_config()` | 13 |
| Con Provider/Consumer | 27 |

Nota:

El loader builtin registra 60 entradas, pero la auditoria vigente fija 59 modulos con clase principal. Esa diferencia debe tratarse como discrepancia de inventario y no como evidencia de completitud funcional.

## Regla de interpretacion

- Un modulo presente en el loader no implica flujo cerrado.
- Un modulo repasado intensivamente no implica validacion final.
- Un modulo parcial no esta descartado, pero requiere QA dirigida.

## Familias funcionales

### Comunidad, participacion y gobernanza

- `socios`
- `eventos`
- `participacion`
- `presupuestos_participativos`
- `incidencias`
- `tramites`
- `transparencia`
- `comunidades`
- `colectivos`
- `encuestas`
- `campanias`
- `documentacion_legal`
- `seguimiento_denuncias`
- `mapa_actores`

### Economia, comercio y operacion

- `woocommerce`
- `marketplace`
- `grupos_consumo`
- `facturas`
- `advertising`
- `clientes`
- `reservas`
- `fichaje_empleados`
- `bares`
- `empresarial`
- `email_marketing`

### Cultura, conocimiento y contenidos

- `cursos`
- `talleres`
- `biblioteca`
- `multimedia`
- `podcast`
- `radio`
- `recetas`

### Comunidad digital, cuidados y relacion social

- `ayuda_vecinal`
- `chat_grupos`
- `chat_interno`
- `chat_estados`
- `red_social`
- `banco_tiempo`
- `circulos_cuidados`
- `justicia_restaurativa`
- `saberes_ancestrales`
- `trabajo_digno`
- `sello_conciencia`
- `economia_don`
- `economia_suficiencia`

### Territorio, movilidad y sostenibilidad

- `bicicletas_compartidas`
- `carpooling`
- `compostaje`
- `espacios_comunes`
- `huertos_urbanos`
- `parkings`
- `reciclaje`
- `huella_ecologica`
- `energia_comunitaria`
- `biodiversidad_local`

### Especializados o experimentales

- `trading_ia`
- `dex_solana`
- `themacle`

## Nivel de madurez

La auditoria vigente separa los modulos en alta, media y baja madurez. La idea importante no es memorizar una lista perfecta, sino entender que:

- existe un bloque amplio utilizable
- hay modulos con cobertura razonable pero patron desigual
- siguen existiendo candidatos a refactor o redefinicion

Entre los de alta madurez en la auditoria aparecen:

- `banco_tiempo`
- `biblioteca`
- `cursos`
- `email_marketing`
- `encuestas`
- `eventos`
- `grupos_consumo`
- `huertos_urbanos`
- `incidencias`
- `marketplace`
- `participacion`
- `presupuestos_participativos`
- `reciclaje`
- `reservas`
- `socios`
- `talleres`
- `tramites`

Entre los que requieren mas cautela o aparecen como candidatos claros a refactor o redefinicion:

- `bares`
- `chat_grupos`
- `chat_interno`
- `clientes`
- `themacle`

## Como decidir que modulo activar

Activa un modulo si cumple estas tres condiciones:

- resuelve una necesidad real del proyecto
- tiene ruta, dashboard o frontend validable
- encaja con los permisos y datos que vas a mantener

## Regla operativa

Es mejor tener pocos modulos bien enlazados que muchos modulos encendidos sin recorrido funcional completo.

## Donde ampliar el detalle

Para una referencia modulo por modulo, consulta:

- `CATALOGO-MODULOS.md`
- `INTEGRACIONES.md`
- `PERMISSIONS-USAGE.md`
