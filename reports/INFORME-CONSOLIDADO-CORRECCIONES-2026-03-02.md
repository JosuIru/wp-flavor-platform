# Informe Consolidado de Correcciones 2026-03-02

## Alcance

Este informe consolida la pasada de auditoria y saneamiento aplicada sobre documentacion, modulos, integraciones, AJAX, assets frontend, tablas y errores probables de `403 Forbidden`.

Sirve como cierre operativo de la fase de auditoria estatica y correccion estructural. No sustituye una validacion runtime completa en navegador y base de datos real.

## Estado consolidado

- Documentacion canonica limpia y sin referencias principales a reportes desfasados de febrero.
- Matriz actualizada de modulos disponible en `modulos_matriz_actual_2026-03-01.csv`.
- Riesgos estructurales graves identificados y parcialmente corregidos en tablas, handlers AJAX y carga de assets.
- Varios modulos frontend quedaron alineados entre `wp_localize_script`, JS consumidor y endpoints reales.
- El caso real reproducido de `403` en `grupos-consumo/suscripciones` fue corregido.
- El sistema comun de `aside` y tabs del portal quedo refactorizado para depender primero de configuracion viva y mucho menos de matrices legacy.
- Se redujo de forma amplia la deuda de `execute_action()` en modulos que exponian tabs o acciones del portal sin puente real.
- Se mitigó el warning global `preg_replace(): regular expression is too large` en el pipeline del portal.

## Correcciones aplicadas

### 1. Documentacion y trazabilidad

Se dejo como base vigente:

- `AUDITORIA-ESTADO-REAL-2026-03-01.md`
- `AUDITORIA-403-MODULOS-2026-03-01.md`
- `modulos_matriz_actual_2026-03-01.csv`

Se actualizaron los indices documentales y se eliminaron reportes de modulos que ya no coincidian con el arbol actual.

### 2. Esquema, tablas e integraciones

Se corrigieron desajustes claros entre codigo e instaladores en:

- `reservas`
- `presupuestos-participativos`
- `reciclaje`

Tipos de correccion aplicados:

- tablas referenciadas que no coincidian con las creadas por `install.php`
- nombres de tabla divergentes en modulos y `config-integrations.php`
- validacion de activacion dependiente de tablas reales

### 3. Frontend controllers y rutas de assets

Se normalizaron rutas de assets en 18 controladores frontend para evitar construccion fragil de URLs desde `frontend/` y reducir probables `403/404` por recursos mal resueltos.

Modulos corregidos en esta capa:

- `ayuda-vecinal`
- `banco-tiempo`
- `campanias`
- `comunidades`
- `documentacion-legal`
- `espacios-comunes`
- `eventos`
- `huertos-urbanos`
- `incidencias`
- `justicia-restaurativa`
- `participacion`
- `saberes-ancestrales`
- `seguimiento-denuncias`
- `socios`
- `talleres`
- `trabajo-digno`
- `tramites`
- `transparencia`

### 4. Mitigaciones runtime sobre errores historicos

Se añadieron blindajes concretos en:

- `circulos-cuidados`
  - callback de cron `enviar_recordatorios()` inexistente
- `ayuda-vecinal`
  - compatibilidad `get_instance()` en widget dashboard
- `espacios-comunes`
  - implementacion explicita de `registrar_en_panel_unificado()`

Estas correcciones reducen fatales por desalineacion entre codigo activo y rutas de ejecucion antiguas.

### 5. Caso real corregido: `grupos-consumo/suscripciones`

Incidencias corregidas:

- `403 Forbidden` por nonce cruzado entre `gc_lista_compra_nonce` y `gc_suscripcion_nonce`
- rechazo de permisos por `consumidor_id` obsoleto o no perteneciente al usuario
- endpoints REST incompletos para reanudar suscripciones
- incoherencias entre `slug`, `id`, `tipo_cesta_id` y `cesta_id`
- selectors frontend parciales
- join SQL incorrecto en vista de suscripcion
- handlers de suscripcion sin blindaje ante IDs inexistentes

Resultado:

- el flujo de suscripciones queda mucho mas consistente tanto por `admin-ajax.php` como por REST
- ya no depende de IDs legacy enviados por HTML viejo
- los errores de permisos pasaron de rechazo tecnico a validacion funcional real

### 6. `reservas`

Se corrigio el conflicto entre handlers AJAX duplicados del modulo principal y del controlador frontend.

Se añadió:

- delegacion al controlador frontend cuando la peticion usa el flujo moderno
- endpoint `reservas_calendario`
- soporte de disponibilidad por fecha con retorno de `horarios`
- helpers para slots horarios y render de calendario AJAX

Esto alinea el backend con `assets/js/reservas.js`.

### 7. Desajustes JS/PHP de configuracion localizada

Se corrigio el patron repetido donde PHP publicaba un objeto distinto al esperado por el JS.

Modulos ajustados:

- `espacios-comunes`
- `participacion`
- `tramites`
- `banco-tiempo`
- `reciclaje`
- `multimedia`
- `radio`
- `parkings`

Adicionalmente, se revisaron `incidencias`, `ayuda-vecinal` y `avisos-municipales` sin encontrar en esta pasada un desajuste equivalente ya accionable.

### 8. Navegacion lateral, tabs y `aside`

Se refactorizo `includes/frontend/class-dynamic-pages.php` para que:

- use tabs reales de modulo antes que matrices legacy
- fusione `get_dashboard_tabs()` y `get_renderer_config()['tabs']`
- respete `requires_login`, `hidden_nav` y `cap`
- reduzca `get_module_actions()` a fallback historico y no fuente principal
- unifique `aside` y quick actions con la misma composicion viva
- soporte contenido de tabs basado en `callback:metodo`

Se dejaron tambien mas alineados numerosos fallbacks legacy de navegacion en modulos como:

- `comunidades`
- `red-social`
- `grupos-consumo`
- `tramites`
- `participacion`
- `eventos`
- `reservas`
- `parkings`
- `biblioteca`
- `multimedia`
- `podcast`
- `radio`
- `ayuda-vecinal`
- `circulos-cuidados`
- `justicia-restaurativa`
- `compostaje`
- `reciclaje`
- `saberes-ancestrales`
- `documentacion-legal`
- `seguimiento-denuncias`
- `transparencia`
- `avisos-municipales`
- `campanias`
- `mapa-actores`
- `trabajo-digno`

Y se añadió fase 2 de modernizacion para modulos legacy puros:

- `socios`
- `bares`
- `fichaje-empleados`

### 8.1. Retirada progresiva de legacy en navegacion

Se inicio una fase explicita de retirada de fallback legacy en `includes/frontend/class-dynamic-pages.php`.

Modulos puestos en modo `modern-only` para sidebar y quick actions prioritarias:

- `banco-tiempo`
- `grupos-consumo`
- `reservas`
- `tramites`
- `participacion`
- `espacios-comunes`
- `red-social`
- `comunidades`

Efectos de esta fase:

- el `aside` deja de complementar con acciones legacy prioritarias cuando el modulo ya tiene tabs modernas suficientes
- se reducen mezclas de slugs historicos y actuales
- se corrigen widgets rapidos que apuntaban a acciones legacy (`tablon`, `ciclo`, `mis-expedientes`, etc.)
- `banco-tiempo` deja de usar shortcodes legacy como `banco_tiempo_mi_balance` y `banco_tiempo_intercambios`

Deuda legacy residual:

- otros modulos aun no entran en `modern-only` porque siguen dependiendo de acciones legacy o no tienen tabs modernas suficientes
- la retirada completa debe hacerse por modulo y con validacion runtime

### 9. `comunidades`, red/nodos y tabs integradas

Se corrigieron problemas estructurales en:

- `comunidades`
- `red-social`
- `class-network-content-bridge.php`

Tipos de correccion aplicados:

- consultas federadas movidas desde `flavor_network_directory` a `flavor_network_nodes`
- alineacion de `comunidades` con `articulos_social`, `imagen` y contratos actuales
- creacion/migracion de columnas de metricas de nodos usadas realmente en runtime
- correccion de tabs del dashboard de `red-social` para reutilizar UI/shortcodes reales
- render contextual de `foros` y `eventos` en `comunidades`

### 10. Modulos incompletos saneados

Se cerraron o mitigaron piezas incompletas reales en:

- `foros`
  - consultas principales alineadas con `flavor_foros_hilos` y `flavor_foros_respuestas`
  - mapeo persistente entidad -> foro
  - integracion contextual desde dynamic pages
- `recetas`
  - `[flavor_receta]` ya no es placeholder
  - flujo comunitario moderado: usuario normal envia a revision, editor publica
- `compostaje`
  - tabla `flavor_solicitudes_compost`
  - `ajax_solicitar_compost()` ya crea solicitud real
- `economia-suficiencia`
  - `notificar_prestamo_recurso()` implementado
- `transparencia`
  - aliases de acciones y shortcodes para evitar `Accion no implementada`
- `themacle`
  - shortcodes reales de `mis_temas` y `formulario`
- `clientes`
  - aliases genericos del portal

### 11. Mitigacion del warning de regex grande

Se corrigio el problema global que disparaba:

- `Warning: preg_replace(): Compilation failed: regular expression is too large`

### 12. Ajustes especificos de `banco-tiempo`

Se corrigieron tres focos reales de legacy/desalineacion:

- `Archive Renderer` ya no consulta `created_at` ni `tipo` en `banco-tiempo`; usa `fecha_publicacion` y estadisticas coherentes con la tabla real
- `Dynamic Pages` ya no usa los shortcodes legacy `banco_tiempo_mi_balance` y `banco_tiempo_intercambios`
- el fallback de navegacion de `banco-tiempo` queda alineado con `servicios`, `mi-saldo`, `intercambios`, `ranking`, `reputacion`, `mensajes`, `ofrecer` y `buscar`

Medidas aplicadas:

- desactivacion de `shortcode_unautop()` en el flujo del portal
- evitar reanadir `do_shortcode` sobre `the_content` si WordPress ya lo tenia
- cortar escaneos innecesarios de `the_content` en clases de assets y red para rutas `mi-portal`

### 12. Saneamiento masivo del puente de acciones (`execute_action`)

Se añadieron aliases y wrappers reales para reducir fallos silenciosos del portal en modulos como:

- `chat-interno`
- `parkings`
- `facturas`
- `carpooling`
- `fichaje-empleados`
- `reciclaje`
- `biodiversidad-local`
- `podcast`
- `huertos-urbanos`
- `bicicletas-compartidas`
- `empresarial`
- `colectivos`
- `marketplace`
- `talleres`
- `biblioteca`
- `red-social`
- `participacion`
- `avisos-municipales`
- `tramites`
- `woocommerce`
- `email-marketing`

En `marketplace`, ademas, se añadieron wrappers reales para:

- `ver_anuncio`
- `publicar_anuncio`
- `mis_anuncios`
- `contactar_vendedor`

de forma que el modulo ya no prometa acciones del portal que no aterrizaban en metodos reales.

## Riesgos residuales

### 1. Validacion runtime incompleta

No se pudo certificar por CLI una validacion completa contra WordPress/MySQL del sitio Local. La auditoria es fuerte a nivel estatico y de coherencia de codigo, pero no equivale a certificacion funcional total en navegador.

### 2. Logs historicos no siempre reproducibles

Parte de los fatales encontrados en logs parecen corresponder a ejecuciones anteriores o arboles desalineados respecto al codigo actual. Conviene separar siempre:

- error historico de log
- error reproducible actual

Tambien se comprobó que varios fatales historicos de dashboard (`client-dashboard`, `unified-dashboard`) ya no se reproducen en el arbol actual.

### 3. Deuda repetida de arquitectura frontend

El patron `wp_localize_script` vs objeto JS consumidor no era aislado. Aunque se corrigieron varios casos, es probable que existan mas modulos con el mismo defecto.

### 4. Minificados y caches

En algunos modulos el arbol contiene archivos `.min.js` heredados. Aunque los flujos revisados cargan el `.js` correcto, sigue siendo recomendable revisar versionado y activos minificados para evitar comportamiento distinto por cache.

### 5. Base de datos real

Se corrigieron incoherencias evidentes de nombres de tabla, pero sigue pendiente confirmar:

- existencia real de tablas en la instancia activa
- migraciones sobre datos ya existentes
- compatibilidad de instalaciones antiguas

## Prioridad siguiente

### Prioridad 1

Validacion runtime real en navegador de:

- `grupos-consumo`
- `reservas`
- `tramites`
- `espacios-comunes`
- `participacion`
- `comunidades`
- `marketplace`
- `chat-interno`
- `parkings`

### Prioridad 2

Pasada global adicional para encontrar mas modulos con:

- nonces cruzados
- `permission_callback` demasiado restrictivo
- objetos localizados inconsistentes
- endpoints JS definidos pero no registrados en backend

### Prioridad 3

Validacion real de base de datos e instaladores:

- tablas creadas
- upgrades
- coexistencia con datos legacy

### Prioridad 4

Consolidacion documental final:

- actualizar backlog por estado real de modulos ya saneados
- marcar modulos con deuda profunda funcional frente a modulos con solo deuda runtime
- separar claramente "modulo reparado estaticamente" de "modulo validado runtime"

## Conclusion operativa

El plugin no estaba en un estado homogéneo, pero la pasada actual elimina varios focos reales de error:

- documentacion contradictoria
- referencias de tabla inconsistentes
- rutas de assets fragiles
- `403` por nonces y permisos cruzados
- desajustes entre JS y configuracion publicada por PHP
- endpoints faltantes respecto al frontend

El estado actual es objetivamente mejor y mas coherente que el punto de partida, pero aun requiere validacion runtime sistematica para cerrar el estado de produccion o preproduccion con garantias.
