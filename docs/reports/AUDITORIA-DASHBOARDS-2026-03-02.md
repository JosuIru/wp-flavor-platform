# Auditoria de Dashboards

Fecha: 2026-03-02

## Alcance

Revision estatica de `dashboard-tab` y `dashboard-widget` en `includes/modules` para detectar:

- subtabs visibles sin handler
- enlaces muertos dentro del dashboard
- incoherencias entre tab principal y widget
- redundancias estructurales de registro

## Correcciones aplicadas

### Clientes

Archivo: `includes/modules/clientes/class-clientes-dashboard-tab.php`

- `subtab=detalle` ya se maneja por switch de forma explicita.
- `subtab=editar` ya no queda muerto.
- se anadio `render_editar_cliente()` con formulario precargado.

### Publicidad

Archivo: `includes/modules/advertising/class-advertising-dashboard-tab.php`

- `mis-anuncios`, `crear` y `facturacion` ya delegan en los shortcodes nativos del modulo:
  - `flavor_ads_dashboard`
  - `flavor_ads_crear`
  - `flavor_ads_ingresos`
- `subtab=editar` deja de simular un formulario de dashboard distinto al flujo real y pasa a modo lectura con enlace a estadisticas.
- `subtab=stats` ya no queda muerto.
- los botones de activar/pausar ahora exponen tambien las clases `btn-pausar-anuncio` y `btn-activar-anuncio`, alineadas con el JS legado del modulo.

### Facturas

Archivo: `includes/modules/facturas/class-facturas-dashboard-tab.php`

- `subtab=ver` ya no queda muerto y delega en `flavor_detalle_factura`.
- `subtab=pdf` ya no queda muerto.
- `subtab=nueva` ya no mantiene un formulario paralelo y delega en `flavor_nueva_factura`.
- se anadieron `render_ver_factura()` y `render_pdf_factura()`, pero la vista principal y la creacion ya usan el flujo nativo del modulo.
- la generacion de PDF usa el `Module Loader` actual en vez de asumir un singleton inexistente del modulo.

### Sello de Conciencia

Archivo: `includes/modules/sello-conciencia/class-sello-conciencia-dashboard-tab.php`

- ya estaba corregido en esta fase:
  - `subtab=detalle`
  - `subtab=ver`
- `subtab=solicitar` ya no promete un envio funcional inexistente:
  - se deja el formulario como referencia
  - el envio queda desactivado de forma explicita

### Chat Grupos

Archivo: `includes/modules/chat-grupos/class-chat-grupos-dashboard-tab.php`

- el dashboard ya no mantiene una UI paralela para `mis-grupos`, `explorar` y `crear`.
- esas subtabs delegan ahora en los shortcodes nativos del modulo:
  - `chat_grupos_mis_grupos`
  - `flavor_grupos_explorar`
  - `chat_grupos_crear`
- con esto se elimina la principal redundancia entre dashboard tab y frontend real del modulo.

### Empresarial

Archivo: `includes/modules/empresarial/class-empresarial-dashboard-tab.php`

- `servicios`, `equipo`, `testimonios` y `portfolio` ya no renderizan una copia del modulo.
- esas subtabs delegan en los shortcodes reales:
  - `empresarial_servicios`
  - `empresarial_equipo`
  - `empresarial_testimonios`
  - `empresarial_portfolio`
- `perfil` deja de mostrarse como formulario editable sin backend equivalente y pasa a un resumen honesto del estado publico.

### Chat Interno

Archivo: `includes/modules/chat-interno/class-chat-interno-dashboard-tab.php`

- el dashboard ya no renderiza una interfaz de chat paralela con HTML/CSS propio.
- ahora reutiliza los shortcodes reales del modulo:
  - `chat_interno_conversaciones`
  - `chat_interno_nuevo`
  - `chat_interno_archivados`
  - `chat_interno_mensajes`
- con esto se elimina otra redundancia fuerte entre dashboard tab y frontend operativo del modulo.

### Fichaje de Empleados

Archivo: `includes/modules/fichaje-empleados/class-fichaje-empleados-dashboard-tab.php`

- `fichar`, `historial` y `resumen` ya no renderizan una UI paralela de dashboard.
- ahora delegan en los shortcodes/frontend nativos:
  - `fichaje_panel`
  - `fichaje_historial`
  - `fichaje_resumen`
- con esto los botones de entrada, salida y pausa quedan alineados con el JS/AJAX real del modulo.

### Chat Estados

Archivo: `includes/modules/chat-estados/class-chat-estados-dashboard-tab.php`

- el dashboard tab ya no mantiene una interfaz propia con selectores incompatibles respecto al JS del modulo.
- ahora reutiliza los shortcodes reales:
  - `flavor_estados`
  - `flavor_estados_crear`
  - `flavor_estados_mis_estados`
- con esto se elimina otra duplicidad fuerte entre dashboard tab y frontend operativo.

### Email Marketing

Archivo: `includes/modules/email-marketing/class-em-dashboard-tab.php`

- en esta pasada no se detecto otra UI paralela rota.
- `suscripciones`, `preferencias` e `historial` conservan handlers AJAX propios y coherentes con el modulo:
  - `em_dashboard_toggle_suscripcion`
  - `em_dashboard_guardar_preferencias`
  - `em_dashboard_marcar_email_leido`
- no se trato como deuda de redundancia porque el dashboard no simula acciones sin backend.

## Hallazgos de coherencia

### Sin redundancia estructural grave

En la revision actual no aparece un patron fuerte de "mismo dashboard registrado dos veces" ni "mismo widget duplicado" en los modulos revisados. La mayoria de combinaciones `dashboard-tab + dashboard-widget` son complementarias:

- el `tab` sirve como area completa de trabajo
- el `widget` sirve como resumen o acceso rapido

### Redundancia aparente, no real

Algunos resultados del escaneo parecian duplicados, pero eran solo:

- coincidencias repetidas del mismo `render_widget()` por patron de busqueda
- archivos de widget cargados desde el modulo, no registrados dos veces

### Dashboard unificado: doble capa, pero no doble ejecucion

Archivos:

- `admin/class-admin-menu-manager.php`
- `includes/dashboard/class-unified-dashboard.php`

Observacion:

- existe una aparente duplicidad en la carga de assets del dashboard unificado
- en la practica no parece una doble ejecucion simultanea, sino compatibilidad de carga tardia:
  - `Admin_Menu_Manager` encola assets desde el callback porque ahi el hook `admin_enqueue_scripts` ya paso
  - `Flavor_Unified_Dashboard` mantiene su propio `enqueue_assets()` cuando la clase ya esta cargada a tiempo

Conclusion:

- es deuda de arquitectura y convendria unificarla mas adelante
- no se ha tratado como bug activo en esta fase porque no implica por si sola un registro doble confirmado de widgets o tabs

### Client Dashboard separado, no redundante

Archivo:

- `includes/frontend/class-client-dashboard.php`

Observacion:

- el dashboard de cliente mantiene su propio sistema de widgets (`flavor_client_dashboard_init`)
- no comparte el mismo registro del dashboard unificado admin
- esto hoy parece una separacion intencional entre dashboard cliente/frontend y dashboard unificado admin, no una duplicidad accidental

### Chat Estados normalizado

Archivo: `includes/modules/chat-estados/class-chat-estados-module.php`

- ya usa el registro moderno `flavor_register_dashboard_widgets`
- mantiene compatibilidad con el dashboard legacy via `flavor_dashboard_widgets`
- expone `get_dashboard_widget_data()` para el dashboard unificado

Con esto sale de la lista principal de deuda residual de widgets.

### Integration Registry corregido

Archivo: `includes/modules/class-integration-registry.php`

- el widget del registro de integraciones ya usa la firma moderna de `flavor_register_dashboard_widgets`.
- deja de comportarse como callback legacy de array en un hook moderno.
- ahora registra un `Flavor_Module_Widget` coherente con el dashboard unificado.

### Widgets: barrido final

En el barrido final de `dashboard-widget` y `widget`:

- no aparecieron mensajes literales de `en desarrollo`, `placeholder` funcional o `no implementado` en la capa de widgets.
- no se detectaron mas registros legacy incompatibles con el dashboard moderno fuera de la compatibilidad intencional de `chat-estados`.
- la deuda residual de widgets pasa a ser principalmente runtime y calidad de datos, no contrato estructural.

## Estado resultante

- los enlaces muertos visibles mas claros en dashboards quedan corregidos
- la duplicacion mas clara de dashboard tab vs frontend real queda reducida en `chat-grupos` y `empresarial`
- `chat-interno` tambien deja de mantener una interfaz paralela
- `fichaje-empleados` y `chat-estados` pasan a usar sus frontends reales
- `publicidad` y `facturas` reducen tambien la duplicidad fuerte de formularios respecto a sus shortcodes nativos
- `email-marketing` queda revisado como dashboard coherente, no como duplicado roto
- no se detecto una duplicacion estructural amplia de widgets/tabs fuera de esos casos
- la deuda restante ya no esta en enlaces muertos principales, sino en validacion runtime y algunos formularios sin backend equivalente

## Validacion

- `php -l` OK en:
  - `includes/modules/clientes/class-clientes-dashboard-tab.php`
  - `includes/modules/advertising/class-advertising-dashboard-tab.php`
  - `includes/modules/facturas/class-facturas-dashboard-tab.php`
- `includes/modules/chat-estados/class-chat-estados-module.php`
- `includes/modules/sello-conciencia/class-sello-conciencia-dashboard-tab.php`
- `includes/modules/chat-grupos/class-chat-grupos-dashboard-tab.php`
- `includes/modules/empresarial/class-empresarial-dashboard-tab.php`
- `includes/modules/chat-interno/class-chat-interno-dashboard-tab.php`
- `includes/modules/fichaje-empleados/class-fichaje-empleados-dashboard-tab.php`
- `includes/modules/chat-estados/class-chat-estados-dashboard-tab.php`
- `includes/modules/class-integration-registry.php`

La validacion runtime completa de dashboards sigue pendiente de entorno estable en `localhost:10028`.
