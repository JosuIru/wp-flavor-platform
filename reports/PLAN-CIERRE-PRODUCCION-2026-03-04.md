# Plan Maestro de Cierre a Produccion 2026-03-04

## Objetivo

Convertir el plugin en un sistema cerrable por evidencia, no por intuicion, hasta dejar en estado de produccion solo los modulos y flujos que cumplan una definicion de hecho verificable.

Este plan no presupone que "todo" debe entrar en produccion. Obliga a clasificar cada modulo como:

- apto para cierre a produccion
- apto con validacion adicional
- experimental o dependiente de entorno
- candidato a congelacion o deprecacion

## Regla de trabajo

Ningun modulo puede marcarse como `listo para produccion` solo por existir en el arbol, por tener codigo o por haber pasado lint.

Un modulo solo se cierra cuando tiene:

- contrato tecnico claro
- flujo principal operativo
- permisos coherentes
- datos o estados vacios honestos
- validacion runtime real
- evidencia escrita del resultado

## Definicion de hecho por modulo

Un modulo queda cerrado solo si cumple todo esto:

- carga sin fatales ni warnings visibles
- la ruta principal del portal responde bien
- tabs, widgets y CTAs no apuntan a legacy roto
- login y permisos estan alineados con la request real
- assets cargan sin errores visibles
- tablas, CPT o taxonomias requeridas existen
- accion principal funciona
- estados vacios y mensajes de acceso son utiles
- existe evidencia runtime minima
- el resultado queda reflejado en matriz y reporte

## Capas de cierre

### 1. Base comun

Incluye:

- bootstrap
- loader
- instalacion y migraciones
- helpers
- routing dinamico
- redirects de login
- renderers de tabs y acciones
- documentacion canonica

### 2. Cierre por modulo

Incluye:

- rutas principales
- tabs y widgets
- formularios y acciones
- AJAX y REST
- permisos
- estados vacios
- datos minimos

### 3. Validacion de entorno

Incluye:

- HTTP estable de la instancia local
- login funcional
- tablas y datos requeridos
- logs limpios
- capacidad de reproducir errores y regresiones

### 4. Release

Incluye:

- checklist global de produccion
- backlog residual clasificado
- decision explicita sobre modulos fuera de alcance
- informe final de go/no-go

## Prioridad operativa

### Ola 1. Base comun y portal

Objetivo:

- dejar estable la infraestructura compartida del portal
- cerrar deuda transversal antes de seguir modulo a modulo

Bloques:

- login y `redirect_to`
- paginas dinamicas
- tabs y actions renderizados por modulo
- shortcodes legacy visibles
- documentacion canonica

### Ola 2. Modulos criticos del portal

Objetivo:

- cerrar los modulos que mas impactan navegacion, autoservicio y valor de negocio

Modulos:

- `marketplace`
- `tramites`
- `reservas`
- `foros`
- `colectivos`
- `socios`
- `participacion`
- `eventos`

### Ola 3. Modulos maduros con validacion pendiente

Objetivo:

- transformar madurez estatica en evidencia runtime

Modulos:

- `banco-tiempo`
- `biblioteca`
- `cursos`
- `email-marketing`
- `encuestas`
- `grupos-consumo`
- `huertos-urbanos`
- `incidencias`
- `presupuestos-participativos`
- `reciclaje`
- `talleres`
- `socios`

### Ola 4. Modulos parciales o con deuda alta

Objetivo:

- decidir por modulo si se repara, se congela o se deja fuera de release

Modulos orientativos:

- `bares`
- `chat-grupos`
- `chat-interno`
- `clientes`
- `themacle`
- `woocommerce`
- `sello-conciencia`
- `trading-ia`
- `dex-solana`

## Artefactos obligatorios

Este plan se ejecuta junto con:

- `reports/MATRIZ-CIERRE-MODULOS-2026-03-04.csv`
- `reports/BACKLOG-PRIORIZADO-PRODUCCION-2026-03-04.md`
- `reports/CHECKLIST-PRODUCCION-PLUGIN-2026-03-04.md`
- reportes de tanda fechados por sesion

## Metodo de ejecucion por tanda

Cada tanda debe seguir este ciclo:

1. inventario del modulo
2. deteccion de deuda estructural
3. correccion de rutas, tabs, widgets y formularios
4. validacion runtime real
5. documentacion del resultado
6. actualizacion de matriz y backlog

## Reglas de clasificacion

### Estado tecnico

Valores permitidos:

- `cerrado`
- `en_validacion`
- `parcial`
- `bloqueado_entorno`
- `experimental`
- `fuera_release`

### Severidad de hallazgo

Valores permitidos:

- `P0` fatal o bloqueo de flujo principal
- `P1` flujo principal incompleto o inconsistente
- `P2` incoherencia secundaria o UX deficiente
- `P3` deuda documental o cleanup

## Criterio de salida global

El plugin solo puede presentarse como apto para produccion cuando se cumpla:

- todos los modulos activos clasificados
- todos los `P0` y `P1` cerrados o explicitamente fuera de release
- rutas criticas del portal validadas en runtime
- instalacion y upgrade comprobados
- login y permisos coherentes
- sin shortcodes legacy visibles en flujos activos
- sin rutas legacy rotas en portal
- sin fatales nuevos en logs de validacion
- checklist de produccion completada

## Lectura correcta de alcance

La meta real no es "terminar todo".

La meta correcta es:

- definir que entra en produccion
- cerrar con evidencia lo que si entra
- aislar o congelar lo que no esta listo
- dejar trazabilidad de cada decision
