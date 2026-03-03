# Auditoria de Riesgos 403 en Modulos

**Fecha:** 2026-03-01
**Alcance:** revision estatica de patrones que pueden provocar `403 Forbidden` en frontend, AJAX y REST dentro de `includes/modules`

## Resumen

El arbol actual no muestra un unico origen de 403. Los tres focos principales son:

- nonces AJAX o formularios no alineados entre PHP y JavaScript
- `permission_callback` o `current_user_can()` demasiado restrictivos para la ruta real de uso
- URLs de assets construidas desde `frontend/` en lugar de la raiz del modulo, lo que rompe la carga de JS/CSS y puede derivar en peticiones denegadas o rutas inexistentes segun reglas del servidor

## Hallazgos Cuantitativos

| Metrica | Valor |
|---------|-------|
| Modulos con clase principal | 59 |
| Controladores frontend | 41 |
| Validaciones `check_ajax_referer(...)` | 622 |
| Declaraciones `permission_callback` | 628 |
| Controladores frontend con patron de URL de assets fragil detectado inicialmente | 18 |

## Estado tras saneamiento

La pasada actual ha normalizado los 18 controladores frontend detectados con el patron:

- `plugins_url('', dirname(__FILE__))`
- `plugins_url('assets/', dirname(__FILE__))`

aplicado desde `frontend/`.

Estado actual de ese patron concreto:

- `0` coincidencias restantes en `includes/modules/*/frontend/*.php`

Ademas se han corregido referencias a nombres de archivo inexistentes en:

- `espacios-comunes`
- `huertos-urbanos`
- `justicia-restaurativa`
- `saberes-ancestrales`
- `trabajo-digno`

## Modulos Corregidos en Esta Pasada

### Reservas

- Se corrigio la base de assets en `includes/modules/reservas/frontend/class-reservas-frontend-controller.php`
- Se añadió compatibilidad entre el payload JS legacy (`fecha`, `notas`) y el backend actual (`fecha_inicio`, `fecha_fin`, `motivo`)

Impacto esperado:

- reduce errores de carga de `reservas.css` y `reservas.js`
- evita fallos de validacion que podian acabar en respuestas de error al enviar formularios

### Presupuestos Participativos

- Se corrigio la base de assets del frontend
- Se sustituyeron nombres de archivo inexistentes por los reales: `presupuestos.css` y `presupuestos.js`

Impacto esperado:

- elimina un caso claro de frontend sin JS/CSS operativo
- reduce 403/404 asociados a rutas de recurso incorrectas

### Normalizacion masiva de frontend controllers

Se han corregido rutas de assets en:

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

Impacto esperado:

- reduce errores de carga de CSS/JS por URL calculada desde el subdirectorio `frontend/`
- reduce la probabilidad de `403 Forbidden` o `404` aparentes en recursos estáticos bajo configuraciones de servidor estrictas

## Patrones de Riesgo Detectados

### 1. Asset URLs construidas desde `frontend/`

Controladores detectados inicialmente con patron fragil:

- `includes/modules/ayuda-vecinal/frontend/class-ayuda-vecinal-frontend-controller.php`
- `includes/modules/banco-tiempo/frontend/class-banco-tiempo-frontend-controller.php`
- `includes/modules/campanias/frontend/class-campanias-frontend-controller.php`
- `includes/modules/comunidades/frontend/class-comunidades-frontend-controller.php`
- `includes/modules/documentacion-legal/frontend/class-documentacion-legal-frontend-controller.php`
- `includes/modules/espacios-comunes/frontend/class-espacios-comunes-frontend-controller.php`
- `includes/modules/eventos/frontend/class-eventos-frontend-controller.php`
- `includes/modules/huertos-urbanos/frontend/class-huertos-urbanos-frontend-controller.php`
- `includes/modules/incidencias/frontend/class-incidencias-frontend-controller.php`
- `includes/modules/justicia-restaurativa/frontend/class-justicia-restaurativa-frontend-controller.php`
- `includes/modules/participacion/frontend/class-participacion-frontend-controller.php`
- `includes/modules/saberes-ancestrales/frontend/class-saberes-ancestrales-frontend-controller.php`
- `includes/modules/seguimiento-denuncias/frontend/class-seguimiento-denuncias-frontend-controller.php`
- `includes/modules/socios/frontend/class-socios-frontend-controller.php`
- `includes/modules/talleres/frontend/class-talleres-frontend-controller.php`
- `includes/modules/trabajo-digno/frontend/class-trabajo-digno-frontend-controller.php`
- `includes/modules/tramites/frontend/class-tramites-frontend-controller.php`
- `includes/modules/transparencia/frontend/class-transparencia-frontend-controller.php`

Estado:

- corregido en la pasada actual para los 18 controladores listados

Riesgo:

- si el controller calcula la URL desde `frontend/`, el recurso final puede apuntar a una ruta inexistente o distinta de la real del modulo

Regla de correccion:

- resolver assets desde `dirname(dirname(__FILE__))` o desde una constante de ruta/URL del modulo

### 2. Nonces con nombres de campo distintos segun flujo

Ejemplos visibles:

- `presupuestos-participativos` usa `pp_nonce`, `pp_nonce_field` y ademas otro flujo paralelo con `flavor_presupuestos_nonce`
- `comunidades` mezcla `comunidades_nonce_field` y `nonce` segun handler

Riesgo:

- formularios renderizados por PHP y peticiones JS pueden enviar nombres distintos al esperado por `check_ajax_referer(...)`

Regla de correccion:

- unificar por modulo el nombre del action nonce y el nombre del campo transmitido

### 3. Permisos que pueden devolver 403 legitimo pero inesperado

Casos representativos:

- `comunidades` devuelve `403` si el usuario no pertenece a la comunidad destino
- `chat-interno` devuelve `403` para conversaciones sin acceso
- varios modulos REST protegen escritura con `permission_callback`

Riesgo:

- el 403 no es fallo tecnico sino respuesta correcta, pero si el frontend no lo trata como caso de negocio parece error del sistema

Regla de correccion:

- mapear 403 esperados a mensajes de UI y no tratarlos como fallo generico de red

## Casos con 403 Explicito en Codigo

- `includes/modules/comunidades/class-comunidades-module.php`
- `includes/modules/chat-interno/class-chat-interno-module.php`
- `includes/modules/podcast/class-podcast-module.php`
- `includes/modules/ayuda-vecinal/class-ayuda-vecinal-module.php`
- `includes/modules/dex-solana/class-dex-solana-module.php`
- `includes/modules/foros/class-foros-module.php`
- `includes/modules/grupos-consumo/class-gc-whatsapp-channel.php`

## Recomendaciones

- normalizar un helper comun para construir URLs de assets de modulo
- estandarizar un unico contrato nonce por modulo: `action`, nombre de campo y objeto JS localizado
- auditar por prioridad los 18 controladores frontend listados arriba
- tratar de forma diferenciada `403` de permisos esperados frente a `403` por nonce o path de recurso

## Validacion Runtime Parcial

Hallazgos adicionales durante esta pasada:

- Local marca el sitio `sitio prueba` como `running` en `~/.config/Local/site-statuses.json`
- la URL declarada de trabajo sigue siendo `http://localhost:10028/`
- desde este entorno no fue posible abrir directamente `localhost:10028`, por lo que no se pudo reproducir el frontend con `curl`

Senales utiles ya disponibles:

- existe una auditoria web historica en `reports/auditoria_web_mi_portal_links_deep_10028_2026-02-16.csv` donde multiples rutas del portal devolvian `200`
- los `403` de `wp-login.php?action=logout...` detectados en esa auditoria son esperados y no cuentan como fallo del plugin

Bloqueos runtime recientes en logs:

- `participacion`: `Cannot redeclare Flavor_Chat_Participacion_Module::get_renderer_config()`
- `espacios-comunes`: `Call to undefined method Flavor_Chat_Espacios_Comunes_Module::registrar_en_panel_unificado()`
- `circulos-cuidados`: callback de cron `enviar_recordatorios` inexistente
- `ayuda-vecinal`: `Flavor_Ayuda_Vecinal_Dashboard_Widget::get_instance()` inexistente en runtime
- `comunidades`: `Cannot redeclare Flavor_Chat_Comunidades_Module::get_renderer_config()`
- `woocommerce`: log histórico con `syntax error, unexpected token "private"`

Accion aplicada:

- `circulos-cuidados` ya incluye una implementacion segura minima de `enviar_recordatorios()` para evitar ese fatal de cron
- `ayuda-vecinal` ahora expone `Flavor_Ayuda_Vecinal_Dashboard_Widget::get_instance()` para compatibilidad con flujos runtime antiguos y actuales
- `espacios-comunes` incluye una implementacion local explicita de `registrar_en_panel_unificado()` como red de seguridad frente a desalineaciones de carga

Validacion adicional:

- `includes/modules/woocommerce/class-woocommerce-module.php` pasa `php -l` en el árbol actual
- `includes/modules/comunidades/class-comunidades-module.php` pasa `php -l` y solo contiene una definicion visible de `get_renderer_config()`

Conclusión práctica:

- una ruta como `http://localhost:10028/mi-portal/grupos-consumo/suscripciones/` puede fallar aunque su módulo esté correctamente definido, si otro módulo activo rompe el bootstrap del plugin durante `plugins_loaded`
- los bloqueos de `participacion`, `comunidades` y el error de `woocommerce` parecen corresponder a código histórico distinto del árbol actual, o a runtime cacheado/desalineado, por lo que requieren validacion en la instancia Local antes de parchear a ciegas
