# Plan de Reparacion de Modulos 2026-03-02

## Objetivo

Dejar todos los modulos del plugin funcionales, consistentes con el sistema actual, con contenido real verificable y sin desalineaciones entre:

- esquema de base de datos
- shortcodes y rutas
- dashboards
- AJAX y REST
- assets frontend
- integraciones provider/consumer
- documentacion y reportes

## Principios de ejecucion

- Un solo contrato por modulo para frontend y backend.
- Ningun modulo debe depender de tablas, columnas o endpoints legacy no migrados.
- Toda pantalla activa debe tener datos reales o estados vacios coherentes.
- Toda integracion declarada en configuracion debe existir en codigo y base de datos.
- Toda validacion de permisos, nonce y `permission_callback` debe ser reproducible en runtime.

## Fase 1. Normalizacion estructural

### 1. Inventario canonico

- congelar listado real de modulos activos y registrados
- clasificar cada modulo por:
  - esquema
  - frontend
  - dashboard
  - AJAX
  - REST
  - instalador
  - integraciones
  - contenido demo o real

### 2. Contratos de datos

- alinear tablas, columnas y migraciones con el codigo realmente cargado
- eliminar referencias a tablas legacy o crear migraciones explicitas
- normalizar nombres de claves comunes:
  - `user_id`
  - `created_at`
  - `estado`
  - `slug`
  - `imagen`

### 3. Contratos frontend

- revisar modulo por modulo:
  - objeto publicado por `wp_localize_script`
  - archivo JS realmente cargado
  - nombres de acciones AJAX/REST
  - nonces y permisos
- retirar o desactivar controladores frontend legacy que dupliquen handlers

## Fase 2. Reparacion por prioridad

### Prioridad A. Modulos nucleares del portal

- `comunidades`
- `red-social`
- `grupos-consumo`
- `reservas`
- `tramites`
- `participacion`
- `espacios-comunes`

Objetivo:

- flujo completo funcional en navegador
- datos reales visibles
- sin `403`, `404` ni fatales

### Prioridad B. Modulos con integraciones transversales

- `eventos`
- `multimedia`
- `radio`
- `podcast`
- `biblioteca`
- `recetas`
- `banco-tiempo`
- `parkings`
- `reciclaje`

Objetivo:

- integraciones coherentes
- metaboxes y relaciones funcionales
- contenido compartible sin errores

### Prioridad C. Modulos con menor madurez o deuda alta

- `clientes`
- `themacle`
- `chat-grupos`
- `chat-interno`
- `bares`
- cualquier modulo sin dashboard, install o frontend consistente

Objetivo:

- decidir por modulo:
  - reparar
  - encapsular como experimental
  - desactivar del sistema actual

## Fase 3. Contenido real y consistencia funcional

### 1. Datos base

- crear dataset realista minimo por modulo activo
- evitar dependencias de datos demo ocultos o hardcodeados
- sembrar:
  - comunidades reales
  - publicaciones reales
  - eventos reales
  - reservas reales
  - tramites reales
  - anuncios y elementos multimedia reales

### 2. Relaciones reales

- conectar contenido entre modulos:
  - `comunidades` <-> `eventos`
  - `comunidades` <-> `articulos_social`
  - `multimedia` <-> providers
  - `grupos-consumo` <-> `recetas`
  - `reservas` <-> recursos reales
  - `participacion` <-> encuestas y propuestas

### 3. Estados vacios y permisos

- todo modulo debe tener:
  - estado vacio correcto
  - mensaje de permisos correcto
  - CTA util
  - degradacion limpia sin datos

## Fase 4. Validacion runtime

### 1. Matriz de pruebas

Por modulo:

- cargar pantalla principal
- crear elemento
- listar elemento
- editar o interactuar
- validar permisos
- validar respuesta AJAX/REST
- validar enlaces y assets

### 2. Validacion tecnica

- revisar logs PHP nuevos tras cada tanda
- revisar consola y `Network`
- revisar `403/404/500`
- revisar consultas SQL que dependan de columnas no migradas

### 3. Validacion de negocio

- comprobar que el contenido mostrado tenga sentido para usuario final
- validar dashboard cliente y portal como sistema unificado

## Fase 5. Cierre y mantenimiento

- actualizar documentacion canónica
- marcar módulos experimentales o deprecados
- generar matriz de salud por modulo
- fijar checklist de regression para futuras entregas

## Entregables

- matriz de salud por modulo
- plan de migraciones de base de datos
- lista de módulos reparados, desactivados o deprecados
- dataset mínimo real por dominio funcional
- auditoría runtime final con evidencia

## Orden operativo recomendado

1. Cerrar deuda estructural de tablas y columnas.
2. Cerrar deuda de AJAX, nonces y assets.
3. Eliminar duplicidad entre flujos legacy y actuales.
4. Sembrar contenido real mínimo.
5. Ejecutar validación runtime módulo por módulo.
6. Consolidar documentación y matriz final.
