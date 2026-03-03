# Auditoria de asides de modulos

Fecha: 2026-03-02

## Hallazgo principal

El `aside` lateral del portal no se generaba desde los tabs reales del modulo, sino desde una matriz hardcodeada independiente en `includes/frontend/class-dynamic-pages.php`.

Eso provocaba desalineaciones como:

- enlaces laterales a acciones ya no definidas en el modulo vivo
- etiquetas distintas entre el panel principal y la ruta lateral
- acciones integradas visibles en el aside pero no coherentes con `get_dashboard_tabs()` o `get_renderer_config()['tabs']`
- casos como `comunidades`, donde el aside mostraba una mezcla de accesos legacy y tabs actuales

## Correccion aplicada

Se ha cambiado el renderer comun del sidebar en `includes/frontend/class-dynamic-pages.php` para:

- priorizar tabs reales obtenidos por `get_dashboard_tabs()`
- usar `get_renderer_config()['tabs']` cuando sea la fuente valida del modulo
- hacer que los tabs reales prevalezcan sobre aliases legacy equivalentes
- complementar solo con acciones legacy utiles
- eliminar duplicados por `slug` y por etiqueta
- omitir entradas redundantes respecto al enlace raiz `Ver todos`
- ocultar entradas con `requires_login` cuando no hay sesion
- respetar tambien `hidden_nav` y otros metadatos de tabs definidos via `get_renderer_config()['tabs']`
- respetar `cap` para no mostrar acciones restringidas a usuarios sin capacidad suficiente
- fusionar `get_dashboard_tabs()` con `get_renderer_config()['tabs']` en vez de tratarlos como fuentes excluyentes
- cuando un modulo ya tiene tabs reales, dejar de rellenar el aside con acciones legacy no prioritarias
- reutilizar esa misma composicion viva tambien en las acciones rapidas principales del modulo
- limitar el uso de la matriz legacy completa a fallback puro, usando solo un subconjunto prioritario cuando existen tabs modernas

## Impacto

La correccion afecta a todos los modulos que renderizan su contenido a traves de `Flavor_Dynamic_Pages`.

Mejora especialmente:

- `comunidades`
- `reservas`
- `eventos`
- `parkings`
- `red-social`
- `grupos-consumo`
- `tramites`
- `participacion`
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
- `socios`
- `bares`
- `fichaje-empleados`
- cualquier modulo cuyo aside estuviera desfasado respecto a tabs/config modernos

## Validacion hecha

- `php -l` correcto en `includes/frontend/class-dynamic-pages.php`
- comprobacion runtime en `comunidades/multimedia`:
  - en anonimo aparecen acciones coherentes con el contexto publico: `Foros`, `Eventos`, `Crear`, `Explorar`, `Mis comunidades`, `Multimedia`, `Anuncios`, `Recursos`
  - tabs privados como `Miembros` y `Actividad` ya no se muestran sin sesion
  - desaparece parte importante de la dependencia exclusiva de la matriz hardcodeada anterior al combinar tabs legacy y renderer config
- comprobacion runtime en `reservas`:
  - el aside queda alineado con `Recursos disponibles`, `Mis reservas`, `Calendario`, `Hacer reserva`
- comprobacion runtime en `red-social`:
  - el aside ya muestra `Feed`, `Mi Perfil`, `Explorar`, `Amigos`, `Mensajes`, `Historias`
  - se elimina la entrada legacy `Notifications`, que no pertenecia a la navegacion actual del modulo
- alineacion estatica adicional:
  - `eventos` queda alineado con `Próximos eventos`, `Mis inscripciones`, `Calendario`, `Crear evento`, respetando capacidad `edit_posts`
  - `parkings` queda alineado con `Mapa`, `Listado`, `Reservar`, `Mis reservas`
  - `reservas` queda alineado tambien en fallback con `Recursos disponibles`, `Mis reservas`, `Calendario`, `Hacer reserva`
  - `grupos-consumo` ya no conserva en fallback etiquetas legacy como `Cestas` ni rutas privadas visibles que el propio modulo marca con `hidden_nav`
  - `tramites` queda alineado con `Catálogo`, `Iniciar trámite`, `Mis trámites`, `Seguimiento`
  - `participacion` usa fallback coherente con `Propuestas`, `Votaciones`, `Resultados`, `Debates`, `Reuniones`
  - `biblioteca` queda alineado con `Catálogo`, `Mis préstamos`, `Novedades`, `Reseñas`, `Clubes`
  - `multimedia` queda alineado con `Galería`, `Álbumes`, `Mi galería`, `Subir`
  - `podcast` queda alineado con `Episodios`, `Programas`, `Mis suscripciones`, `Favoritos`
  - `radio` queda alineado con `En vivo`, `Programación`, `Programas`, `Mis programas`
  - `ayuda-vecinal` queda alineado con `Ofrecer ayuda`, `Pedir ayuda`, `Mis ayudas`, `Voluntarios`, `Mapa`, `Estadísticas`
  - `circulos-cuidados` queda alineado con `Círculos`, `Necesidades`, `Unirse`, `Mis círculos`, `Registrar cuidado`
  - `justicia-restaurativa` queda alineado con `Información`, `Mediadores`, `Solicitar proceso`, `Mis procesos`
  - `compostaje` queda alineado con `Composteras`, `Registrar aporte`, `Mis aportes`, `Turnos`, `Ranking`
  - `reciclaje` queda alineado con `Mapa`, `Puntos`, `Mi impacto`, `Guía`
  - `saberes-ancestrales` queda alineado con `Catálogo`, `Guardianes`, `Talleres`, `Documentar`, `Aprender`
  - `documentacion-legal` queda alineado con `Documentos`, `Leyes`, `Modelos`, `Sentencias`, `Favoritos`
  - `seguimiento-denuncias` queda alineado con `Mis denuncias`, `Nueva`, `Alertas`, `Archivadas`
  - `transparencia` queda alineado con `Documentos`, `Presupuestos`, `Solicitar información`, `Mis solicitudes`
  - `avisos-municipales` queda alineado con `Todos`, `Urgentes`, `Sin leer`, `Suscripción`
  - `campanias` queda alineado con `Campañas`, `Mis campañas`, `Firmar`, `Acciones`
  - `mapa-actores` queda alineado con `Actores`, `Grafo`, `Por tipo`, `Relaciones`
  - `trabajo-digno` queda alineado con `Ofertas`, `Emprendimientos`, `Publicar oferta`, `Mis postulaciones`, `Mi CV`
  - `socios` ya expone tabs modernas para `Socios`, `Unirse`, `Mi perfil`, `Mis cuotas` y `Pagar cuota`
  - `bares` ya expone tabs modernas para `Bares`, `Mapa`, `Reservar`, `Mis reservas` y `Mis reseñas`, reutilizando su dashboard real para tabs privadas
  - `fichaje-empleados` ya expone tabs modernas para `Estado actual`, `Fichar entrada`, `Fichar salida`, `Mis fichajes` y `Solicitar corrección`, respetando la capacidad `flavor_fichaje_acceso`

## Riesgo residual

Siguen existiendo acciones legacy hardcodeadas en `get_module_actions()` y conviene reducirlas progresivamente modulo por modulo hasta dejar el sidebar completamente derivado de configuracion viva.

El cambio actual corrige el comportamiento del portal sin romper compatibilidad con rutas legacy.

La validacion runtime completa sigue condicionada por la inestabilidad intermitente de `localhost:10028` desde CLI.
