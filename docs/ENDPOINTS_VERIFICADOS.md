# Endpoints verificados para módulos prioritarios

## eventos
- `GET /wp-json/flavor-chat-ia/v1/eventos` (listado, paginación, búsqueda)
- `GET /wp-json/flavor-chat-ia/v1/eventos/{id}` (detalle + plazas, estado inscripción)
- `POST /wp-json/flavor-chat-ia/v1/eventos/{id}/inscribir` (requiere login)
- `DELETE /wp-json/flavor-chat-ia/v1/eventos/{id}/cancelar` (requiere login)

## facturas
- `GET /wp-json/flavor-chat/v1/facturas` (listado, filtros, paginado)
- `POST /wp-json/flavor-chat/v1/facturas` (crear factura, requiere permisos)
- `GET /wp-json/flavor-chat/v1/facturas/{id}` (detalle + líneas/pagos)
- `PUT /wp-json/flavor-chat/v1/facturas/{id}` (actualizar)
- `DELETE /wp-json/flavor-chat/v1/facturas/{id}` (eliminar, admin)
- `GET /wp-json/flavor-chat/v1/facturas/{id}/pdf` (descargar PDF)
- `GET` y `POST /wp-json/flavor-chat/v1/facturas/{id}/pagos` (listar/registrar pagos)
- `GET /wp-json/flavor-chat/v1/facturas/estadisticas` (resumen)

## fichaje-empleados
- `POST /wp-json/flavor/v1/fichaje/entrada` (registrar entrada con notas/geo/dispositivo)
- `POST /wp-json/flavor/v1/fichaje/salida` (registrar salida con notas/geo/dispositivo)
- `GET /wp-json/flavor/v1/fichaje/estado` (estado actual, pausa activa)
- `GET /wp-json/flavor/v1/fichaje/historial` (historial filtrable por fecha/tipo)
- `GET /wp-json/flavor/v1/fichaje/resumen` (resumen mensual)
- `POST /wp-json/flavor/v1/fichaje/pausa/iniciar` y `POST /wp-json/flavor/v1/fichaje/pausa/finalizar`
- `GET /wp-json/flavor/v1/fichaje/hoy` (fichajes del día actual)

## incidencias
- `GET /wp-json/flavor-chat-ia/v1/incidencias/dashboard` (estadísticas + recientes)
- `GET /wp-json/flavor-chat-ia/v1/incidencias` (listado filtrable por estado/categoría)
- `GET /wp-json/flavor-chat-ia/v1/incidencias/mis-incidencias` (listado personal)
- `GET /wp-json/flavor-chat-ia/v1/incidencias/{id}` (detalle)
- `POST /wp-json/flavor-chat-ia/v1/incidencias` (crear incidencia)
- `POST /wp-json/flavor-chat-ia/v1/incidencias/{id}/votar`
- `POST /wp-json/flavor-chat-ia/v1/incidencias/{id}/comentario`
- `GET /wp-json/flavor-chat-ia/v1/incidencias/categorias`
- `GET /wp-json/flavor-chat-ia/v1/incidencias/mapa`

## reservas
- `GET /wp-json/flavor/v1/reservas` (listado con filtros optional)
- `GET /wp-json/flavor/v1/reservas/{id}` (detalle)
- `POST /wp-json/flavor/v1/reservas` (crear reserva)
- `PUT /wp-json/flavor/v1/reservas/{id}` (editar)
- `POST /wp-json/flavor/v1/reservas/{id}/cancelar`
- `GET /wp-json/flavor/v1/reservas/disponibilidad` (consulta disponibilidad)
- `GET /wp-json/flavor/v1/reservas/config` (tipos de servicio y horarios)

## socios
- `GET /wp-json/flavor-chat-ia/v1/socios/perfil` (perfil + estado de membresía)
- `PUT /wp-json/flavor-chat-ia/v1/socios/perfil` (actualizar teléfono/dirección)
- `GET /wp-json/flavor-chat-ia/v1/socios/cuotas` (historial de cuotas)
- `GET /wp-json/flavor-chat-ia/v1/socios/carnet`
- `GET /wp-json/flavor-chat-ia/v1/socios/beneficios`
- `GET /wp-json/flavor-chat-ia/v1/socios/actividad`
## talleres
- `GET /wp-json/flavor-chat-ia/v1/talleres/dashboard` (estadísticas + proximas sesiones/destacados)
- `GET /wp-json/flavor-chat-ia/v1/talleres` (listado público filtrable por categoría/modalidad/busqueda)
- `GET /wp-json/flavor-chat-ia/v1/talleres/mis-talleres` (talleres del usuario separado por estado)
- `GET /wp-json/flavor-chat-ia/v1/talleres/{id}` (detalle completo con sesiones, plazas y estado de inscripción)
- `POST /wp-json/flavor-chat-ia/v1/talleres/{id}/inscribir` (inscribirse)
- `DELETE /wp-json/flavor-chat-ia/v1/talleres/{id}/cancelar` (cancelar inscripción)
- `POST /wp-json/flavor-chat-ia/v1/talleres/{id}/valorar` (valorar con puntuación/comentario)
- `GET /wp-json/flavor-chat-ia/v1/talleres/categorias` (catálogo de categorías)
- `GET /wp-json/flavor/v1/talleres/*` (módulo clásico usa flavor/v1):
  - `GET /wp-json/flavor/v1/talleres` (listado similar)
  - `GET /wp-json/flavor/v1/talleres/{id}` (detalle)
  - `GET /wp-json/flavor/v1/talleres/categorias` y `GET /wp-json/flavor/v1/talleres/calendario` (metadatos)
  - `POST /wp-json/flavor/v1/talleres/inscribirse` / `POST /wp-json/flavor/v1/talleres/cancelar` / `/mis-inscripciones`
  - `POST /wp-json/flavor/v1/talleres/{id}/valorar`, `GET /wp-json/flavor/v1/talleres/{id}/materiales`, `/certificado`
  - `POST /wp-json/flavor/v1/talleres/proponer` (crear nuevo taller) y `/organizador/mis-talleres`
  - `POST /wp-json/flavor/v1/talleres/{id}/asistencia` y `GET /wp-json/flavor/v1/talleres/{id}/estadisticas`
## tramites
- `GET /wp-json/flavor-tramites/v1/tipos` y `GET /wp-json/flavor-tramites/v1/tipos/{id}` (catálogo público de tipos de trámite)
- `GET /wp-json/flavor-tramites/v1/expedientes` (listado de expedientes del usuario autenticado)
- `POST /wp-json/flavor-tramites/v1/expedientes` (crear nuevo expediente)
- `GET /wp-json/flavor-tramites/v1/expedientes/{id}` (detalle)
- `PUT /wp-json/flavor-tramites/v1/expedientes/{id}` (actualizar)
- `POST /wp-json/flavor-tramites/v1/expedientes/{id}/documentos` (adjuntar archivos)
- `GET /wp-json/flavor-tramites/v1/expedientes/{id}/historial` (seguimiento completo)
- `GET /wp-json/flavor-tramites/v1/expedientes/consulta/{numero}` (consulta pública por número)
- `GET /wp-json/flavor-tramites/v1/estados` (lista de estados posibles)
