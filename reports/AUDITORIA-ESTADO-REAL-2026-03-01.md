# Auditoria del Estado Real del Sistema de Modulos

**Fecha:** 2026-03-01
**Alcance:** revision estatica del plugin `flavor-chat-ia`
**Version declarada del plugin:** 3.1.1
**Estado general:** funcional parcial con deuda estructural relevante

## Resumen Ejecutivo

El sistema tiene una base de codigo amplia y utilizable, con muchos modulos implementados y sin errores de sintaxis PHP en `includes/modules`. El problema principal no es falta total de desarrollo, sino inconsistencia: documentacion contradictoria, bootstrap sobredimensionado, instalacion de tablas repartida en varias capas y desalineacion entre nombres de modulo, integraciones y esquemas de base de datos.

No se ha podido certificar el estado runtime de WordPress y MySQL desde CLI en este entorno. `wp-cli` no ha podido cargar WordPress correctamente porque el PHP CLI no tiene `mysqli` operativo y tampoco ha conectado al socket MySQL por defecto. Por tanto, este informe valida arquitectura e implementacion estatica, pero no garantiza ausencia de errores en la base de datos en la instalacion local activa.

## Metodologia

- Revision de documentacion principal en raiz, `docs/` y `reports/`
- Revision del bootstrap en `flavor-chat-ia.php`
- Revision del cargador de modulos y contratos internos
- Recuento estatico de modulos, frontends, instaladores, APIs, tabs y widgets
- Lint PHP de todo `includes/modules`
- Contraste entre integraciones declaradas y tablas realmente creadas

## Hallazgos Principales

### 1. La documentacion previa no era fiable como estado canonico

Se detectaron versiones y cifras incompatibles entre si:

- `README.md` declaraba v3.0
- el plugin declara v3.1.1
- `docs/INDICE-DOCUMENTACION.md` hablaba de v4.0
- `docs/ESTADO-REAL-MODULOS.md` y varios reportes de febrero daban recuentos distintos de modulos

Conclusión: la documentacion historica acumulada no sirve como fuente unica de verdad.

### 2. El sistema carga mucho codigo y parte del bootstrap esta duplicado

En `flavor-chat-ia.php` aparecen cargas duplicadas de clases centrales como:

- `includes/api/class-api-rate-limiter.php`
- `includes/class-module-shortcodes.php`
- `includes/admin/class-module-gap-admin.php`
- `includes/frontend/class-user-dashboard.php`
- `includes/frontend/class-client-dashboard.php`

`require_once` evita doble definicion, pero el problema de fondo sigue ahi: bootstrap dificil de mantener, alto acoplamiento y mayor riesgo de efectos laterales.

### 3. La capa de base de datos esta desalineada en varios modulos

Este es el riesgo tecnico mas importante detectado.

Casos claros:

- `presupuestos-participativos` usa `flavor_presupuestos_proyectos` en el modulo e integraciones, pero su instalador crea `flavor_pp_proyectos` y `flavor_pp_propuestas`
- `reservas` trabaja con `flavor_reservas_recursos`, pero su instalador solo crea `flavor_reservas`
- `reciclaje` referencia `flavor_puntos_reciclaje` en parte del modulo, pero crea `flavor_reciclaje_puntos`

Esto indica que hay modulos que pueden renderizar UI o registrar integraciones sobre tablas que no coinciden con las creadas por sus propios instaladores.

### 4. Hay bastante implementacion real, pero no una estandarizacion consistente

Los modulos no siguen un unico patron. Conviven:

- `templates/`
- `views/`
- `frontend/class-*-frontend-controller.php`
- assets propios
- tabs y widgets separados

Eso no significa que el sistema no funcione, pero si que es mas costoso auditarlo, extenderlo y verificarlo.

### 5. Los formularios de modulo siguen siendo una zona irregular

Solo una parte de los modulos implementa `get_form_config()`. Aunque el sistema actual de shortcodes ya no depende exactamente del flujo antiguo descrito por auditorias previas, el estado real sigue siendo desigual: muchos modulos tienen frontend o vistas, pero no exponen configuracion de formulario homogénea.

### 6. Hay pendientes funcionales reales

No son muchos, pero existen:

- `economia-suficiencia`: notificacion pendiente
- `compostaje`: solicitud frontend marcada como TODO sin persistencia real

## Estado Cuantitativo Real

Recuento estatico sobre `includes/modules`:

| Metrica | Valor |
|---------|-------|
| Modulos con clase principal | 59 |
| Controladores frontend | 40 |
| `install.php` por modulo | 17 |
| Modulos con `views/` no vacio | 38 |
| Modulos con `templates/` no vacio | 31 |
| Modulos con assets | 53 |
| Modulos con `get_form_config()` | 13 |
| Modulos con API propia | 22 |
| Modulos con dashboard tab | 51 |
| Modulos con widget | 37 |
| Modulos conectados a Provider/Consumer | 26 |

## Estado por Madurez

### Alta madurez

Estos modulos tienen combinacion razonable de frontend o vistas, assets, tab y ademas API o instalador:

- banco-tiempo
- biblioteca
- bicicletas-compartidas
- cursos
- economia-don
- email-marketing
- encuestas
- espacios-comunes
- eventos
- grupos-consumo
- huertos-urbanos
- incidencias
- marketplace
- participacion
- presupuestos-participativos
- reciclaje
- reservas
- socios
- talleres
- tramites

### Madurez media

Tienen bastante implementacion, pero les falta homogeneidad o una de las piezas clave:

- advertising
- avisos-municipales
- ayuda-vecinal
- bares
- biodiversidad-local
- campanias
- carpooling
- chat-estados
- chat-grupos
- chat-interno
- circulos-cuidados
- clientes
- colectivos
- compostaje
- comunidades
- dex-solana
- documentacion-legal
- economia-suficiencia
- empresarial
- facturas
- fichaje-empleados
- foros
- huella-ecologica
- justicia-restaurativa
- mapa-actores
- multimedia
- parkings
- podcast
- radio
- recetas
- red-social
- saberes-ancestrales
- seguimiento-denuncias
- sello-conciencia
- trabajo-digno
- trading-ia
- transparencia
- woocommerce

### Madurez baja

Estos son los candidatos mas claros a refactor o redefinicion:

- bares
- chat-grupos
- chat-interno
- clientes
- themacle

## Integraciones entre Modulos

### Estado actual

La base existe y no es meramente documental:

- hay 26 modulos que usan `register_as_integration_consumer()` o `register_as_integration_provider()`
- existen providers reales como `multimedia`, `biblioteca`, `recetas` y `red-social`
- existen consumers reales como `eventos`, `talleres`, `cursos`, `marketplace`, `comunidades`, `reservas`, `tramites`, `incidencias`

### Riesgos detectados

- la matriz central en `includes/modules/config-integrations.php` incluye tablas objetivo que no siempre coinciden con las creadas por instaladores
- parte del sistema depende de nombres normalizados con guion bajo, mientras el arbol fisico usa guiones
- la configuracion central y la implementacion por traits conviven, lo que complica saber cual es la fuente efectiva en cada modulo

## Base de Datos

### Lo que si se puede afirmar

- existe un instalador central muy grande en `includes/class-database-installer.php`
- ademas existen instaladores por modulo
- la activacion del plugin tambien llama a capas extra de creacion de tablas

### Lo que no se puede afirmar

- no se ha podido verificar por CLI la existencia real de las tablas en la base de datos local
- no se ha podido validar carga de WordPress en runtime por limitaciones del entorno CLI

### Riesgos de BD visibles en codigo

- esquemas duplicados o paralelos para una misma capacidad
- nombres de tabla incompatibles entre modulo, integracion e instalador
- dificultad para saber si la tabla correcta se crea en activacion o solo cuando el modulo intenta autoactivarse

## Componentes y Subsistemas

### Correctos o razonablemente consistentes

- cargador de modulos base
- integraciones Provider/Consumer como concepto
- shortcodes con modo demo desactivado por defecto
- presencia amplia de tabs y widgets
- gran parte del arbol modular sin errores de sintaxis

### Inconsistentes o necesitados de refactor

- bootstrap general
- documentacion de estado
- estandar de formularios
- estrategia de instalacion de tablas
- criterios de completitud por modulo

## Carencias Prioritarias

### Prioridad 1

- unificar nombres de tablas y contratos de integracion
- definir un unico flujo de instalacion de tablas
- consolidar documentacion canonica y eliminar auditorias obsoletas

### Prioridad 2

- definir estandar obligatorio por modulo
- cerrar TODOs funcionales reales
- separar claramente modulos maduros de modulos conceptuales o incompletos

### Prioridad 3

- reducir bootstrap
- documentar que piezas son historicas, cuales activas y cuales deprecadas
- automatizar verificacion de modulos con un script de auditoria repetible

## Verificaciones Ejecutadas

- lint PHP sobre `includes/modules`: sin errores de sintaxis
- recuento estatico de estructura modular
- revision manual de bootstrap, integraciones, instaladores y documentacion

## Conclusión

El proyecto no esta en estado de abandono ni de colapso tecnico. Hay mucho codigo real y varios modulos con madurez aceptable o alta. El problema principal es de coherencia del sistema: documentacion superpuesta, contratos internos mezclados y base de datos no suficientemente estandarizada.

La recomendacion es tratar este informe como documento canonico inicial y reconstruir a partir de aqui:

1. contratos de tablas
2. contratos de modulo completo
3. documentacion viva y unica

