# Catálogo de Módulos - Flavor Chat IA

> Documentación generada automáticamente - Última actualización: 2026-02-28

## Índice de Módulos

| Módulo | CPTs | Dashboard Tab | Widget | Páginas | Estado |
|--------|------|---------------|--------|---------|--------|
| [advertising](#advertising) | fc_anuncio | - | - | 4 | Parcial |
| [avisos-municipales](#avisos-municipales) | fc_aviso | Frontend Controller | - | 5 | Completo |
| [ayuda-vecinal](#ayuda-vecinal) | fc_ayuda | Frontend Controller | - | 6 | Completo |
| [banco-tiempo](#banco-tiempo) | fc_servicio_bt | Dashboard Tab | - | 8 | Completo |
| [bares](#bares) | fc_bar | Dashboard Tab | - | 5 | Completo |
| [biblioteca](#biblioteca) | fc_libro | Dashboard Tab | - | 6 | Completo |
| [bicicletas-compartidas](#bicicletas-compartidas) | fc_bicicleta, fc_estacion | Dashboard Tab | - | 7 | Completo |
| [biodiversidad-local](#biodiversidad-local) | fc_especie | Dashboard Tab | Widget | 5 | Completo |
| [campanias](#campanias) | fc_campania | Dashboard Tab | - | 4 | Completo |
| [carpooling](#carpooling) | fc_viaje | Dashboard Tab | - | 7 | Completo |
| [chat-estados](#chat-estados) | - | - | Widget | 2 | Parcial |
| [chat-grupos](#chat-grupos) | fc_grupo_chat | - | - | 4 | Parcial |
| [chat-interno](#chat-interno) | fc_mensaje | - | - | 3 | Parcial |
| [circulos-cuidados](#circulos-cuidados) | fc_circulo | Dashboard Tab | Widget | 6 | Completo |
| [clientes](#clientes) | fc_cliente | - | - | 4 | Parcial |
| [colectivos](#colectivos) | fc_colectivo | Dashboard Tab | - | 7 | Completo |
| [compostaje](#compostaje) | fc_compostador | Dashboard Tab | - | 6 | Completo |
| [comunidades](#comunidades) | fc_comunidad | Dashboard Tab | - | 12 | Completo |
| [cursos](#cursos) | fc_curso | Dashboard Tab | - | 8 | Completo |
| [dex-solana](#dex-solana) | - | - | - | 3 | Parcial |
| [documentacion-legal](#documentacion-legal) | fc_documento | Frontend Controller | - | 4 | Completo |
| [economia-don](#economia-don) | fc_intercambio | Dashboard Tab | Widget | 8 | Completo |
| [economia-suficiencia](#economia-suficiencia) | fc_recurso | Dashboard Tab | Widget | 6 | Completo |
| [email-marketing](#email-marketing) | fc_campania_email | Dashboard Tab | - | 5 | Completo |
| [empresarial](#empresarial) | fc_empresa | - | - | 5 | Parcial |
| [encuestas](#encuestas) | fc_encuesta | Dashboard Tab | - | 6 | Completo |
| [espacios-comunes](#espacios-comunes) | fc_espacio | Dashboard Tab | - | 7 | Completo |
| [eventos](#eventos) | fc_evento | Dashboard Tab | - | 8 | Completo |
| [facturas](#facturas) | fc_factura | - | - | 4 | Parcial |
| [fichaje-empleados](#fichaje-empleados) | fc_fichaje | - | - | 5 | Parcial |
| [foros](#foros) | fc_tema, fc_respuesta | Dashboard Tab | - | 8 | Completo |
| [grupos-consumo](#grupos-consumo) | gc_grupo, gc_producto, gc_pedido, gc_ciclo | Dashboard Tab | Widget | 16 | Completo |
| [huella-ecologica](#huella-ecologica) | - | Dashboard Tab | Widget | 10 | Completo |
| [huertos-urbanos](#huertos-urbanos) | fc_parcela | Dashboard Tab | - | 7 | Completo |
| [incidencias](#incidencias) | fc_incidencia | Dashboard Tab | - | 7 | Completo |
| [justicia-restaurativa](#justicia-restaurativa) | fc_caso_jr | Dashboard Tab | Widget | 8 | Completo |
| [mapa-actores](#mapa-actores) | fc_actor | Frontend Controller | - | 4 | Completo |
| [marketplace](#marketplace) | fc_anuncio_mp | Dashboard Tab | - | 8 | Completo |
| [multimedia](#multimedia) | fc_medio | Dashboard Tab | - | 6 | Completo |
| [parkings](#parkings) | fc_parking, fc_plaza | Frontend Controller | - | 8 | Completo |
| [participacion](#participacion) | fc_propuesta, fc_debate | Dashboard Tab | - | 7 | Completo |
| [podcast](#podcast) | fc_episodio | Dashboard Tab | - | 6 | Completo |
| [presupuestos-participativos](#presupuestos-participativos) | fc_proyecto_pp | Dashboard Tab | - | 9 | Completo |
| [radio](#radio) | fc_programa | Dashboard Tab | - | 6 | Completo |
| [reciclaje](#reciclaje) | fc_punto_reciclaje | Dashboard Tab | - | 6 | Completo |
| [recetas](#recetas) | fc_receta | Frontend Controller | - | 5 | Completo |
| [red-social](#red-social) | fc_publicacion | - | - | 11 | Parcial |
| [reservas](#reservas) | fc_recurso_reserva | Dashboard Tab | - | 6 | Completo |
| [saberes-ancestrales](#saberes-ancestrales) | fc_saber | Dashboard Tab | Widget | 8 | Completo |
| [seguimiento-denuncias](#seguimiento-denuncias) | fc_denuncia | Frontend Controller | - | 5 | Completo |
| [sello-conciencia](#sello-conciencia) | fc_sello | - | Widget | 4 | Parcial |
| [socios](#socios) | fc_socio | Dashboard Tab | - | 6 | Completo |
| [talleres](#talleres) | fc_taller | Dashboard Tab | - | 7 | Completo |
| [themacle](#themacle) | - | - | - | 2 | Parcial |
| [trabajo-digno](#trabajo-digno) | fc_oferta_empleo | Dashboard Tab | Widget | 8 | Completo |
| [trading-ia](#trading-ia) | - | - | - | 3 | Parcial |
| [tramites](#tramites) | fc_tramite | Dashboard Tab | - | 6 | Completo |
| [transparencia](#transparencia) | fc_documento_pub | Dashboard Tab | - | 8 | Completo |
| [woocommerce](#woocommerce) | - (usa WC) | - | - | 3 | Parcial |

---

## Detalle por Módulo

### advertising
**Descripción**: Sistema de gestión de publicidad y anuncios patrocinados.

**CPTs**: `fc_anuncio`

**Taxonomías**: `tipo_anuncio`, `ubicacion_anuncio`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[advertising_banner]` - Muestra banner publicitario
- `[advertising_rotativo]` - Banners rotativos
- `[advertising_zona]` - Zona de anuncios

**Páginas/Tabs**:
- dashboard (principal)
- crear (crear anuncio)
- mis-anuncios (listado propio)
- estadisticas (métricas)

**Vinculaciones**: Ninguna

**Estado**: Parcial - Falta dashboard tab

---

### avisos-municipales
**Descripción**: Sistema de avisos y comunicados oficiales del ayuntamiento.

**CPTs**: `fc_aviso`

**Taxonomías**: `categoria_aviso`, `urgencia_aviso`

**Dashboard Tab**: Frontend Controller (`Flavor_Avisos_Municipales_Frontend_Controller`)

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[avisos_listar]` - Lista de avisos
- `[avisos_detalle]` - Detalle de aviso
- `[avisos_recientes]` - Avisos recientes
- `[avisos_urgentes]` - Solo urgentes
- `[avisos_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (panel principal)
- activos (avisos vigentes)
- archivo (histórico)
- suscripciones (alertas)
- crear (solo admin)

**Vinculaciones**: `comunidades` (avisos por comunidad)

**Estado**: Completo

---

### ayuda-vecinal
**Descripción**: Sistema de solicitudes y ofertas de ayuda entre vecinos.

**CPTs**: `fc_ayuda`

**Taxonomías**: `tipo_ayuda`, `estado_ayuda`, `zona_ayuda`

**Dashboard Tab**: Frontend Controller (`Flavor_Ayuda_Vecinal_Frontend_Controller`)

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[ayuda_vecinal_listar]` - Listado de ayudas
- `[ayuda_vecinal_solicitar]` - Formulario solicitud
- `[ayuda_vecinal_ofrecer]` - Formulario oferta
- `[ayuda_vecinal_mis_ayudas]` - Mis participaciones
- `[ayuda_vecinal_mapa]` - Mapa de ayudas
- `[ayuda_vecinal_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (panel)
- solicitar (nueva solicitud)
- ofrecer (nueva oferta)
- mis-ayudas (participaciones)
- activas (en curso)
- historial (completadas)

**Vinculaciones**: `comunidades`, `socios`

**Estado**: Completo

---

### banco-tiempo
**Descripción**: Intercambio de servicios usando tiempo como moneda.

**CPTs**: `fc_servicio_bt`

**Taxonomías**: `categoria_servicio`, `zona_servicio`

**Dashboard Tab**: `Flavor_Banco_Tiempo_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[banco_tiempo_servicios]` - Catálogo servicios
- `[banco_tiempo_ofrecer]` - Ofrecer servicio
- `[banco_tiempo_solicitar]` - Solicitar servicio
- `[banco_tiempo_mi_balance]` - Balance de horas
- `[banco_tiempo_intercambios]` - Mis intercambios
- `[banco_tiempo_ranking]` - Ranking usuarios
- `[banco_tiempo_buscar]` - Buscador
- `[banco_tiempo_perfil]` - Perfil usuario

**Páginas/Tabs**:
- dashboard (panel principal)
- servicios (catálogo)
- ofrecer (nuevo servicio)
- solicitar (nueva solicitud)
- mis-intercambios (historial)
- mi-balance (horas)
- comunidad (ranking)
- perfil (mi perfil)

**Vinculaciones**: `socios`, `comunidades`

**Estado**: Completo

---

### bares
**Descripción**: Directorio de bares y establecimientos de hostelería.

**CPTs**: `fc_bar`

**Taxonomías**: `tipo_bar`, `zona_bar`, `servicios_bar`

**Dashboard Tab**: `Flavor_Bares_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[bares_listar]` - Directorio
- `[bares_detalle]` - Ficha del bar
- `[bares_cercanos]` - Por proximidad
- `[bares_eventos]` - Eventos en bares
- `[bares_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (directorio)
- mapa (ubicaciones)
- eventos (agenda)
- favoritos (mis favoritos)
- crear (registrar bar)

**Vinculaciones**: `eventos`, `reservas`

**Estado**: Completo

---

### biblioteca
**Descripción**: Gestión de biblioteca comunitaria con préstamos.

**CPTs**: `fc_libro`

**Taxonomías**: `genero_libro`, `estado_libro`, `idioma_libro`

**Dashboard Tab**: `Flavor_Biblioteca_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[biblioteca_catalogo]` - Catálogo libros
- `[biblioteca_detalle]` - Ficha libro
- `[biblioteca_mis_prestamos]` - Mis préstamos
- `[biblioteca_reservar]` - Reservar libro
- `[biblioteca_buscar]` - Buscador
- `[biblioteca_novedades]` - Últimas adquisiciones

**Páginas/Tabs**:
- dashboard (catálogo)
- mis-prestamos (activos)
- historial (anteriores)
- reservas (pendientes)
- donar (donar libros)
- estadisticas (lectura)

**Vinculaciones**: `socios`, `comunidades`

**Estado**: Completo

---

### bicicletas-compartidas
**Descripción**: Sistema de préstamo de bicicletas compartidas.

**CPTs**: `fc_bicicleta`, `fc_estacion`

**Taxonomías**: `tipo_bicicleta`, `estado_bicicleta`

**Dashboard Tab**: `Flavor_Bicicletas_Compartidas_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[bicicletas_mapa]` - Mapa estaciones
- `[bicicletas_disponibles]` - Disponibilidad
- `[bicicletas_reservar]` - Reservar
- `[bicicletas_mis_usos]` - Mi historial
- `[bicicletas_estadisticas]` - Mis estadísticas
- `[bicicletas_estaciones]` - Lista estaciones
- `[bicicletas_perfil]` - Mi perfil ciclista

**Páginas/Tabs**:
- dashboard (estado general)
- mapa (estaciones)
- reservar (nueva reserva)
- mis-usos (historial)
- estadisticas (métricas)
- mantenimiento (reportes)
- perfil (configuración)

**Vinculaciones**: `huella-ecologica`, `comunidades`

**Estado**: Completo

---

### biodiversidad-local
**Descripción**: Catálogo de especies locales y observaciones naturalistas.

**CPTs**: `fc_especie`

**Taxonomías**: `tipo_especie`, `habitat`, `estado_conservacion`

**Dashboard Tab**: `Flavor_Biodiversidad_Local_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Biodiversidad_Local_Widget`

**Shortcodes**:
- `[biodiversidad_catalogo]` - Catálogo especies
- `[biodiversidad_observaciones]` - Avistamientos
- `[biodiversidad_registrar]` - Nueva observación
- `[biodiversidad_mapa]` - Mapa biodiversidad
- `[biodiversidad_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (resumen)
- catalogo (especies)
- mis-observaciones (propias)
- registrar (nueva)
- mapa (ubicaciones)

**Vinculaciones**: `huella-ecologica`, `comunidades`

**Estado**: Completo

---

### campanias
**Descripción**: Gestión de campañas sociales y medioambientales.

**CPTs**: `fc_campania`

**Taxonomías**: `tipo_campania`, `estado_campania`, `causa`

**Dashboard Tab**: `Flavor_Campanias_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[campanias_activas]` - Campañas en curso
- `[campanias_detalle]` - Detalle campaña
- `[campanias_participar]` - Unirse
- `[campanias_crear]` - Nueva campaña
- `[campanias_mis_campanias]` - Mis participaciones
- `[campanias_impacto]` - Métricas de impacto
- `[campanias_donar]` - Donaciones

**Páginas/Tabs**:
- dashboard (activas)
- mis-campanias (participaciones)
- crear (nueva)
- impacto (métricas)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Completo

---

### carpooling
**Descripción**: Sistema de viajes compartidos en coche.

**CPTs**: `fc_viaje`

**Taxonomías**: `tipo_viaje`, `frecuencia_viaje`

**Dashboard Tab**: `Flavor_Carpooling_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[carpooling_buscar]` - Buscar viajes
- `[carpooling_publicar]` - Publicar viaje
- `[carpooling_mis_viajes]` - Mis viajes
- `[carpooling_reservas]` - Mis reservas
- `[carpooling_conductor]` - Perfil conductor
- `[carpooling_valoraciones]` - Valoraciones
- `[carpooling_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (panel)
- buscar (encontrar viaje)
- publicar (ofrecer viaje)
- mis-viajes (como conductor)
- reservas (como pasajero)
- perfil (mi perfil)
- valoraciones (reseñas)

**Vinculaciones**: `huella-ecologica`, `comunidades`

**Estado**: Completo

---

### chat-estados
**Descripción**: Sistema de estados y actualizaciones de usuarios.

**CPTs**: Ninguno (usa tabla personalizada)

**Dashboard Tab**: No implementado

**Widget Dashboard**: `Flavor_Chat_Estados_Widget`

**Shortcodes**:
- `[estados_feed]` - Feed de estados
- `[estados_publicar]` - Publicar estado

**Páginas/Tabs**:
- feed (timeline)
- publicar (nuevo estado)

**Vinculaciones**: `red-social`

**Estado**: Parcial - Falta dashboard tab

---

### chat-grupos
**Descripción**: Sistema de chat en grupos temáticos.

**CPTs**: `fc_grupo_chat`

**Taxonomías**: `categoria_grupo`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[chat_grupos_lista]` - Lista de grupos
- `[chat_grupos_crear]` - Crear grupo
- `[chat_grupos_mis_grupos]` - Mis grupos
- `[chat_grupo]` - Interfaz de chat
- `[chat_grupos_buscar]` - Buscador
- `[chat_grupos_miembros]` - Miembros grupo
- `[chat_grupos_configuracion]` - Ajustes
- `[chat_grupos_archivados]` - Archivados

**Páginas/Tabs**:
- dashboard (mis grupos)
- crear (nuevo grupo)
- buscar (explorar)
- archivados (inactivos)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Parcial - Falta dashboard tab

---

### chat-interno
**Descripción**: Sistema de mensajería privada entre usuarios.

**CPTs**: `fc_mensaje`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[chat_inbox]` - Bandeja entrada
- `[chat_nuevo]` - Nuevo mensaje
- `[chat_conversacion]` - Ver conversación

**Páginas/Tabs**:
- inbox (mensajes)
- nuevo (redactar)
- archivados (archivo)

**Vinculaciones**: `socios`

**Estado**: Parcial - Falta dashboard tab

---

### circulos-cuidados
**Descripción**: Círculos de apoyo mutuo y cuidados comunitarios.

**CPTs**: `fc_circulo`

**Taxonomías**: `tipo_circulo`, `necesidad`

**Dashboard Tab**: `Flavor_Circulos_Cuidados_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Circulos_Cuidados_Widget`

**Shortcodes**:
- `[circulos_listar]` - Lista círculos
- `[circulos_crear]` - Crear círculo
- `[circulos_mis_circulos]` - Mis círculos
- `[circulos_detalle]` - Detalle círculo
- `[circulos_necesidades]` - Necesidades activas
- `[circulos_ofrecer]` - Ofrecer cuidado

**Páginas/Tabs**:
- dashboard (mis círculos)
- explorar (todos)
- crear (nuevo)
- necesidades (activas)
- historial (pasados)
- configuracion (ajustes)

**Vinculaciones**: `comunidades`, `ayuda-vecinal`

**Estado**: Completo

---

### clientes
**Descripción**: Gestión de clientes para negocios.

**CPTs**: `fc_cliente`

**Taxonomías**: `tipo_cliente`, `estado_cliente`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[clientes_listar]` - Lista clientes
- `[clientes_detalle]` - Ficha cliente
- `[clientes_crear]` - Nuevo cliente
- `[clientes_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (listado)
- crear (nuevo)
- importar (masivo)
- exportar (descarga)

**Vinculaciones**: `facturas`, `empresarial`

**Estado**: Parcial - Falta dashboard tab

---

### colectivos
**Descripción**: Gestión de colectivos, asociaciones y grupos organizados.

**CPTs**: `fc_colectivo`

**Taxonomías**: `tipo_colectivo`, `ambito_colectivo`

**Dashboard Tab**: `Flavor_Colectivos_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[colectivos_directorio]` - Directorio
- `[colectivos_detalle]` - Ficha colectivo
- `[colectivos_crear]` - Crear colectivo
- `[colectivos_mis_colectivos]` - Mis colectivos
- `[colectivos_actividades]` - Actividades
- `[colectivos_miembros]` - Miembros
- `[colectivos_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (mis colectivos)
- directorio (explorar)
- crear (nuevo)
- actividades (agenda)
- miembros (gestión)
- documentos (archivos)
- configuracion (ajustes)

**Vinculaciones**: `comunidades`, `eventos`, `foros`

**Estado**: Completo

---

### compostaje
**Descripción**: Sistema de compostaje comunitario.

**CPTs**: `fc_compostador`

**Taxonomías**: `tipo_compostador`, `estado_compostador`

**Dashboard Tab**: `Flavor_Compostaje_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[compostaje_mapa]` - Mapa compostadores
- `[compostaje_registro]` - Registrar aporte
- `[compostaje_mis_aportes]` - Mis aportes
- `[compostaje_estadisticas]` - Estadísticas
- `[compostaje_guia]` - Guía compostaje
- `[compostaje_comunidad]` - Datos comunidad
- `[compostaje_calendario]` - Calendario turnos
- `[compostaje_solicitar]` - Solicitar acceso
- `[compostaje_incidencias]` - Reportar problema
- `[compostaje_formacion]` - Cursos

**Páginas/Tabs**:
- dashboard (resumen)
- mapa (ubicaciones)
- mis-aportes (historial)
- estadisticas (métricas)
- guia (información)
- calendario (turnos)

**Vinculaciones**: `huella-ecologica`, `comunidades`, `reciclaje`

**Estado**: Completo

---

### comunidades
**Descripción**: Gestión de comunidades virtuales temáticas o geográficas.

**CPTs**: `fc_comunidad`

**Taxonomías**: `tipo_comunidad`, `categoria_comunidad`

**Dashboard Tab**: `Flavor_Comunidades_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[comunidades_listar]` - Directorio
- `[comunidades_detalle]` - Página comunidad
- `[comunidades_crear]` - Crear comunidad
- `[comunidades_mis_comunidades]` - Mis comunidades
- `[comunidades_feed_unificado]` - Feed actividad
- `[comunidades_busqueda]` - Buscador
- `[comunidades_calendario]` - Calendario
- `[comunidades_recursos_compartidos]` - Recursos
- `[comunidades_tablon]` - Tablón anuncios
- `[comunidades_metricas]` - Estadísticas
- `[comunidades_actividad]` - Timeline
- `[comunidades_notificaciones]` - Notificaciones

**Páginas/Tabs**:
- dashboard (panel)
- explorar (directorio)
- mis-comunidades (propias)
- crear (nueva)
- [id] (detalle)
- [id]/miembros (miembros)
- [id]/eventos (calendario)
- [id]/recursos (archivos)
- [id]/foro (discusiones)
- [id]/anuncios (tablón)
- [id]/configuracion (ajustes)
- [id]/estadisticas (métricas)

**Vinculaciones**: Todos los módulos pueden vincularse a comunidades

**Estado**: Completo

---

### cursos
**Descripción**: Plataforma de cursos y formación online.

**CPTs**: `fc_curso`

**Taxonomías**: `categoria_curso`, `nivel_curso`, `modalidad_curso`

**Dashboard Tab**: `Flavor_Cursos_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[cursos_catalogo]` - Catálogo
- `[cursos_detalle]` - Página curso
- `[cursos_inscribirse]` - Inscripción
- `[cursos_mis_cursos]` - Mis cursos
- `[cursos_progreso]` - Mi progreso
- `[cursos_certificados]` - Mis certificados
- `[cursos_buscar]` - Buscador
- `[cursos_crear]` - Crear curso (instructor)

**Páginas/Tabs**:
- dashboard (mis cursos)
- catalogo (explorar)
- mis-cursos (inscritos)
- progreso (avance)
- certificados (obtenidos)
- instructor (crear/gestionar)
- historial (completados)
- favoritos (guardados)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Completo

---

### dex-solana
**Descripción**: Integración con DEX de Solana para trading.

**CPTs**: Ninguno

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[dex_trading]` - Interfaz trading
- `[dex_portfolio]` - Portfolio
- `[dex_mercados]` - Mercados

**Páginas/Tabs**:
- trading (interfaz)
- portfolio (cartera)
- mercados (listado)

**Vinculaciones**: `trading-ia`

**Estado**: Parcial - Módulo especializado

---

### documentacion-legal
**Descripción**: Repositorio de documentos legales y normativos.

**CPTs**: `fc_documento`

**Taxonomías**: `tipo_documento`, `ambito_documento`

**Dashboard Tab**: Frontend Controller

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[documentos_listar]` - Listado
- `[documentos_detalle]` - Ver documento
- `[documentos_buscar]` - Buscador
- `[documentos_categorias]` - Por categoría

**Páginas/Tabs**:
- dashboard (listado)
- categorias (navegación)
- buscar (buscador)
- favoritos (guardados)

**Vinculaciones**: `transparencia`, `tramites`

**Estado**: Completo

---

### economia-don
**Descripción**: Sistema de economía del don e intercambios sin moneda.

**CPTs**: `fc_intercambio`

**Taxonomías**: `tipo_intercambio`, `categoria_don`

**Dashboard Tab**: `Flavor_Economia_Don_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Economia_Don_Widget`

**Shortcodes**:
- `[economia_don_tablero]` - Tablero general
- `[economia_don_ofrecer]` - Ofrecer don
- `[economia_don_solicitar]` - Solicitar don
- `[economia_don_mis_dones]` - Mis participaciones
- `[economia_don_estadisticas]` - Estadísticas
- `[economia_don_agradecimientos]` - Muro gracias
- `[economia_don_ranking]` - Ranking generosidad
- `[economia_don_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (tablero)
- ofrecer (nuevo don)
- solicitar (pedir don)
- mis-dones (participaciones)
- agradecimientos (muro)
- estadisticas (métricas)
- comunidad (ranking)
- historial (pasados)

**Vinculaciones**: `comunidades`, `banco-tiempo`

**Estado**: Completo

---

### economia-suficiencia
**Descripción**: Herramientas para economía de suficiencia y autogestión.

**CPTs**: `fc_recurso`

**Taxonomías**: `tipo_recurso`, `categoria_recurso`

**Dashboard Tab**: `Flavor_Economia_Suficiencia_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Economia_Suficiencia_Widget`

**Shortcodes**:
- `[suficiencia_recursos]` - Recursos compartidos
- `[suficiencia_calculadora]` - Calculadora necesidades
- `[suficiencia_mis_recursos]` - Mis recursos
- `[suficiencia_guias]` - Guías autosuficiencia
- `[suficiencia_comunidad]` - Comunidad
- `[suficiencia_estadisticas]` - Estadísticas
- `[suficiencia_retos]` - Retos suficiencia
- `[suficiencia_logros]` - Mis logros
- `[suficiencia_compartir]` - Compartir recurso
- `[suficiencia_mapa]` - Mapa recursos

**Páginas/Tabs**:
- dashboard (resumen)
- recursos (compartidos)
- calculadora (necesidades)
- guias (tutoriales)
- retos (desafíos)
- logros (badges)

**Vinculaciones**: `huella-ecologica`, `grupos-consumo`

**Estado**: Completo

---

### email-marketing
**Descripción**: Sistema de newsletters y campañas de email.

**CPTs**: `fc_campania_email`

**Taxonomías**: `tipo_campania_email`, `lista_email`

**Dashboard Tab**: `Flavor_EM_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[em_suscripcion]` - Formulario suscripción
- `[em_preferencias]` - Preferencias
- `[em_archivo]` - Archivo newsletters
- `[em_campania]` - Ver campaña
- `[em_estadisticas]` - Estadísticas
- `[em_listas]` - Gestión listas
- `[em_crear]` - Crear campaña
- `[em_programar]` - Programar envío
- `[em_plantillas]` - Plantillas
- `[em_contactos]` - Contactos

**Páginas/Tabs**:
- dashboard (resumen)
- campanias (listado)
- crear (nueva)
- listas (suscriptores)
- estadisticas (métricas)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Completo

---

### empresarial
**Descripción**: Herramientas para gestión empresarial básica.

**CPTs**: `fc_empresa`

**Taxonomías**: `sector_empresa`, `tamano_empresa`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[empresarial_perfil]` - Perfil empresa
- `[empresarial_directorio]` - Directorio empresas
- `[empresarial_servicios]` - Servicios
- `[empresarial_contacto]` - Contacto
- `[empresarial_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (panel)
- perfil (datos empresa)
- servicios (ofertas)
- clientes (gestión)
- estadisticas (métricas)

**Vinculaciones**: `clientes`, `facturas`

**Estado**: Parcial - Falta dashboard tab

---

### encuestas
**Descripción**: Sistema de encuestas y votaciones.

**CPTs**: `fc_encuesta`

**Taxonomías**: `tipo_encuesta`, `estado_encuesta`

**Dashboard Tab**: `Flavor_Encuestas_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[encuestas_activas]` - Encuestas activas
- `[encuestas_votar]` - Participar
- `[encuestas_resultados]` - Ver resultados
- `[encuestas_crear]` - Crear encuesta
- `[encuestas_mis_encuestas]` - Mis encuestas
- `[encuestas_historial]` - Mis votos

**Páginas/Tabs**:
- dashboard (activas)
- mis-encuestas (creadas)
- historial (participadas)
- crear (nueva)
- resultados (análisis)
- configuracion (ajustes)

**Vinculaciones**: `participacion`, `comunidades`

**Estado**: Completo

---

### espacios-comunes
**Descripción**: Gestión de espacios comunitarios compartidos.

**CPTs**: `fc_espacio`

**Taxonomías**: `tipo_espacio`, `equipamiento`

**Dashboard Tab**: `Flavor_Espacios_Comunes_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[espacios_catalogo]` - Catálogo espacios
- `[espacios_detalle]` - Ficha espacio
- `[espacios_reservar]` - Reservar
- `[espacios_disponibilidad]` - Calendario
- `[espacios_mis_reservas]` - Mis reservas
- `[espacios_buscar]` - Buscador
- `[espacios_mapa]` - Mapa espacios

**Páginas/Tabs**:
- dashboard (resumen)
- catalogo (espacios)
- mis-reservas (activas)
- historial (pasadas)
- calendario (disponibilidad)
- favoritos (guardados)
- solicitar (nuevo espacio)

**Vinculaciones**: `reservas`, `comunidades`, `eventos`

**Estado**: Completo

---

### eventos
**Descripción**: Gestión de eventos y actividades.

**CPTs**: `fc_evento`

**Taxonomías**: `tipo_evento`, `categoria_evento`

**Dashboard Tab**: `Flavor_Eventos_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[eventos_calendario]` - Calendario
- `[eventos_proximos]` - Próximos eventos
- `[eventos_detalle]` - Página evento
- `[eventos_inscribirse]` - Inscripción
- `[eventos_mis_eventos]` - Mis eventos
- `[eventos_crear]` - Crear evento
- `[eventos_buscar]` - Buscador
- `[eventos_pasados]` - Histórico

**Páginas/Tabs**:
- dashboard (calendario)
- proximos (agenda)
- mis-eventos (inscritos)
- crear (nuevo)
- organizados (como organizador)
- historial (pasados)
- favoritos (guardados)
- configuracion (alertas)

**Vinculaciones**: `comunidades`, `colectivos`, `espacios-comunes`

**Estado**: Completo

---

### facturas
**Descripción**: Sistema de facturación básico.

**CPTs**: `fc_factura`

**Taxonomías**: `estado_factura`, `tipo_factura`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[facturas_listar]` - Lista facturas
- `[facturas_crear]` - Nueva factura
- `[facturas_detalle]` - Ver factura
- `[facturas_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (listado)
- crear (nueva)
- pendientes (por cobrar)
- pagadas (completadas)

**Vinculaciones**: `clientes`, `empresarial`

**Estado**: Parcial - Falta dashboard tab

---

### fichaje-empleados
**Descripción**: Control de fichajes y horarios de empleados.

**CPTs**: `fc_fichaje`

**Taxonomías**: `tipo_fichaje`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[fichaje_reloj]` - Reloj fichaje
- `[fichaje_historial]` - Mi historial
- `[fichaje_resumen]` - Resumen horas
- `[fichaje_calendario]` - Calendario
- `[fichaje_solicitudes]` - Permisos

**Páginas/Tabs**:
- dashboard (fichar)
- historial (registros)
- calendario (mes)
- solicitudes (permisos)
- configuracion (horarios)

**Vinculaciones**: `empresarial`

**Estado**: Parcial - Falta dashboard tab

---

### foros
**Descripción**: Sistema de foros de discusión.

**CPTs**: `fc_tema`, `fc_respuesta`

**Taxonomías**: `categoria_foro`, `etiqueta_tema`

**Dashboard Tab**: `Flavor_Foros_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[foros_listar]` - Lista foros
- `[foros_temas]` - Temas del foro
- `[foros_tema]` - Ver tema
- `[foros_crear_tema]` - Nuevo tema
- `[foros_mis_temas]` - Mis temas
- `[foros_buscar]` - Buscador
- `[foros_ultimos]` - Últimos temas
- `[foros_populares]` - Más activos

**Páginas/Tabs**:
- dashboard (foros)
- mis-temas (propios)
- mis-respuestas (participaciones)
- favoritos (seguidos)
- buscar (buscador)
- nuevo (crear tema)
- moderacion (solo mods)
- configuracion (notificaciones)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Completo

---

### grupos-consumo
**Descripción**: Gestión completa de grupos de consumo agroecológico.

**CPTs**: `gc_grupo`, `gc_producto`, `gc_pedido`, `gc_ciclo`

**Taxonomías**: `categoria_producto`, `tipo_producto`, `productor`

**Dashboard Tab**: `Flavor_GC_Dashboard_Tab`

**Widget Dashboard**: `Flavor_GC_Dashboard_Widget`

**Shortcodes**:
- `[gc_catalogo]` - Catálogo productos
- `[gc_carrito]` - Carrito compra
- `[gc_mi_pedido]` - Mi pedido actual
- `[gc_mis_pedidos]` - Historial pedidos
- `[gc_ciclo_actual]` - Ciclo actual
- `[gc_ciclos]` - Todos los ciclos
- `[gc_grupos_lista]` - Lista grupos
- `[gc_mi_cesta]` - Mi cesta
- `[gc_nav]` - Navegación
- `[gc_panel]` - Panel usuario
- `[gc_productores]` - Productores
- `[gc_productores_cercanos]` - Cercanos
- `[gc_productos]` - Productos
- `[gc_suscripciones]` - Suscripciones
- `[gc_historial]` - Historial completo
- `[gc_calendario]` - Calendario ciclos

**Páginas/Tabs**:
- dashboard (panel)
- catalogo (productos)
- carrito (compra)
- mis-pedidos (historial)
- ciclo-actual (activo)
- grupos (lista)
- productores (directorio)
- suscripciones (recurrentes)
- mi-cesta (guardados)
- configuracion (preferencias)
- mi-grupo (gestión)
- turnos (voluntariado)
- estadisticas (métricas)
- crear-grupo (nuevo)
- unirse (solicitud)
- pagos (facturación)

**Vinculaciones**: `comunidades`, `huella-ecologica`

**Estado**: Completo

---

### huella-ecologica
**Descripción**: Calculadora y seguimiento de huella ecológica personal.

**CPTs**: Ninguno (usa tablas personalizadas)

**Dashboard Tab**: `Flavor_Huella_Ecologica_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Huella_Ecologica_Widget`

**Shortcodes**:
- `[huella_calculadora]` - Calculadora
- `[huella_mi_huella]` - Mi huella
- `[huella_historial]` - Evolución
- `[huella_comparativa]` - Comparativa
- `[huella_consejos]` - Consejos
- `[huella_retos]` - Retos eco
- `[huella_logros]` - Mis logros
- `[huella_comunidad]` - Comunidad
- `[huella_ranking]` - Ranking
- `[huella_objetivos]` - Mis objetivos

**Páginas/Tabs**:
- dashboard (resumen)
- calculadora (calcular)
- mi-huella (actual)
- historial (evolución)
- retos (desafíos)
- logros (badges)
- comunidad (comparativa)
- consejos (recomendaciones)
- objetivos (metas)
- configuracion (preferencias)

**Vinculaciones**: `bicicletas-compartidas`, `carpooling`, `compostaje`, `reciclaje`, `grupos-consumo`

**Estado**: Completo

---

### huertos-urbanos
**Descripción**: Gestión de parcelas de huertos urbanos.

**CPTs**: `fc_parcela`

**Taxonomías**: `tipo_parcela`, `estado_parcela`, `huerto`

**Dashboard Tab**: `Flavor_Huertos_Urbanos_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[huertos_mapa]` - Mapa parcelas
- `[huertos_solicitar]` - Solicitar parcela
- `[huertos_mi_parcela]` - Mi parcela
- `[huertos_calendario]` - Calendario siembra
- `[huertos_intercambio]` - Intercambio semillas
- `[huertos_comunidad]` - Comunidad
- `[huertos_guias]` - Guías cultivo

**Páginas/Tabs**:
- dashboard (mi parcela)
- mapa (huertos)
- solicitar (nueva)
- calendario (siembras)
- intercambio (semillas)
- comunidad (vecinos)
- guias (tutoriales)

**Vinculaciones**: `comunidades`, `compostaje`

**Estado**: Completo

---

### incidencias
**Descripción**: Sistema de reporte de incidencias urbanas.

**CPTs**: `fc_incidencia`

**Taxonomías**: `tipo_incidencia`, `estado_incidencia`, `prioridad`, `zona`

**Dashboard Tab**: `Flavor_Incidencias_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[incidencias_reportar]` - Reportar nueva
- `[incidencias_mapa]` - Mapa incidencias
- `[incidencias_listar]` - Listado
- `[incidencias_mis_reportes]` - Mis reportes
- `[incidencias_detalle]` - Ver incidencia
- `[incidencias_estadisticas]` - Estadísticas
- `[incidencias_seguimiento]` - Seguir estado

**Páginas/Tabs**:
- dashboard (resumen)
- reportar (nueva)
- mapa (ubicaciones)
- mis-reportes (propios)
- seguimiento (estados)
- historial (resueltas)
- estadisticas (métricas)

**Vinculaciones**: `comunidades`, `avisos-municipales`

**Estado**: Completo

---

### justicia-restaurativa
**Descripción**: Sistema de mediación y justicia restaurativa comunitaria.

**CPTs**: `fc_caso_jr`

**Taxonomías**: `tipo_caso`, `estado_caso`

**Dashboard Tab**: `Flavor_Justicia_Restaurativa_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Justicia_Restaurativa_Widget`

**Shortcodes**:
- `[jr_solicitar]` - Solicitar mediación
- `[jr_mis_casos]` - Mis casos
- `[jr_proceso]` - Ver proceso
- `[jr_mediadores]` - Directorio mediadores
- `[jr_recursos]` - Recursos info
- `[jr_estadisticas]` - Estadísticas
- `[jr_formacion]` - Formación mediadores
- `[jr_voluntariado]` - Ser mediador

**Páginas/Tabs**:
- dashboard (resumen)
- solicitar (nuevo caso)
- mis-casos (participante)
- mediador (como mediador)
- formacion (capacitación)
- recursos (información)
- estadisticas (métricas)
- configuracion (preferencias)

**Vinculaciones**: `comunidades`

**Estado**: Completo

---

### mapa-actores
**Descripción**: Mapa de actores locales y sus relaciones.

**CPTs**: `fc_actor`

**Taxonomías**: `tipo_actor`, `sector`, `ambito`

**Dashboard Tab**: Frontend Controller

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[mapa_actores]` - Mapa visual
- `[mapa_actores_lista]` - Lista actores
- `[mapa_actores_detalle]` - Ficha actor
- `[mapa_actores_relaciones]` - Red relaciones

**Páginas/Tabs**:
- dashboard (mapa)
- lista (directorio)
- relaciones (red)
- crear (registrar actor)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Completo

---

### marketplace
**Descripción**: Mercadillo de compraventa entre particulares.

**CPTs**: `fc_anuncio_mp`

**Taxonomías**: `categoria_anuncio`, `estado_anuncio`, `condicion`

**Dashboard Tab**: `Flavor_Marketplace_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[marketplace_catalogo]` - Catálogo
- `[marketplace_publicar]` - Publicar anuncio
- `[marketplace_detalle]` - Ver anuncio
- `[marketplace_mis_anuncios]` - Mis anuncios
- `[marketplace_buscar]` - Buscador
- `[marketplace_favoritos]` - Favoritos
- `[marketplace_mensajes]` - Mensajes
- `[marketplace_perfil]` - Perfil vendedor

**Páginas/Tabs**:
- dashboard (explorar)
- publicar (nuevo)
- mis-anuncios (propios)
- favoritos (guardados)
- mensajes (conversaciones)
- compras (historial)
- ventas (realizadas)
- moderacion (solo mods)

**Vinculaciones**: `comunidades`, `socios`

**Estado**: Completo

---

### multimedia
**Descripción**: Galería multimedia comunitaria.

**CPTs**: `fc_medio`

**Taxonomías**: `tipo_medio`, `album`, `etiqueta_medio`

**Dashboard Tab**: `Flavor_Multimedia_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[multimedia_galeria]` - Galería
- `[multimedia_subir]` - Subir archivo
- `[multimedia_mis_archivos]` - Mis archivos
- `[multimedia_albums]` - Álbumes
- `[multimedia_buscar]` - Buscador
- `[multimedia_destacados]` - Destacados

**Páginas/Tabs**:
- dashboard (galería)
- mis-archivos (propios)
- subir (nuevo)
- albums (colecciones)
- favoritos (guardados)
- configuracion (privacidad)

**Vinculaciones**: `comunidades`, `eventos`

**Estado**: Completo

---

### parkings
**Descripción**: Gestión de plazas de parking compartido.

**CPTs**: `fc_parking`, `fc_plaza`

**Taxonomías**: `tipo_parking`, `tipo_plaza`

**Dashboard Tab**: Frontend Controller

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[parkings_mapa]` - Mapa parkings
- `[parkings_disponibilidad]` - Disponibilidad
- `[parkings_reservar]` - Reservar plaza
- `[parkings_mis_reservas]` - Mis reservas
- `[parkings_ofrecer]` - Ofrecer plaza
- `[parkings_mis_plazas]` - Mis plazas
- `[parkings_estadisticas]` - Estadísticas
- `[parkings_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (mapa)
- reservar (buscar)
- mis-reservas (activas)
- ofrecer (mi plaza)
- mis-plazas (propias)
- historial (pasadas)
- estadisticas (uso)
- configuracion (alertas)

**Vinculaciones**: `comunidades`, `bicicletas-compartidas`

**Estado**: Completo

---

### participacion
**Descripción**: Procesos de participación ciudadana.

**CPTs**: `fc_propuesta`, `fc_debate`

**Taxonomías**: `tipo_propuesta`, `estado_propuesta`, `ambito`

**Dashboard Tab**: `Flavor_Participacion_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[participacion_propuestas]` - Propuestas
- `[participacion_debates]` - Debates
- `[participacion_votaciones]` - Votaciones
- `[participacion_nueva_propuesta]` - Nueva propuesta
- `[participacion_mis_propuestas]` - Mis propuestas
- `[participacion_resultados]` - Resultados
- `[participacion_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (procesos)
- propuestas (listado)
- debates (discusiones)
- votaciones (activas)
- nueva (crear propuesta)
- mis-propuestas (propias)
- resultados (histórico)

**Vinculaciones**: `comunidades`, `presupuestos-participativos`, `encuestas`

**Estado**: Completo

---

### podcast
**Descripción**: Plataforma de podcasts comunitarios.

**CPTs**: `fc_episodio`

**Taxonomías**: `programa_podcast`, `categoria_podcast`

**Dashboard Tab**: `Flavor_Podcast_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[podcast_player]` - Reproductor
- `[podcast_episodios]` - Lista episodios
- `[podcast_programas]` - Programas
- `[podcast_detalle]` - Ver episodio
- `[podcast_suscribirse]` - Suscripción
- `[podcast_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (programas)
- episodios (listado)
- favoritos (guardados)
- historial (escuchados)
- suscripciones (seguidos)
- crear (grabar/subir)

**Vinculaciones**: `comunidades`, `radio`

**Estado**: Completo

---

### presupuestos-participativos
**Descripción**: Sistema de presupuestos participativos.

**CPTs**: `fc_proyecto_pp`

**Taxonomías**: `categoria_proyecto`, `estado_proyecto`, `edicion_pp`

**Dashboard Tab**: `Flavor_Presupuestos_Participativos_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[pp_proyectos]` - Lista proyectos
- `[pp_proponer]` - Nueva propuesta
- `[pp_votar]` - Votación
- `[pp_resultados]` - Resultados
- `[pp_mis_propuestas]` - Mis propuestas
- `[pp_presupuesto]` - Estado presupuesto
- `[pp_calendario]` - Calendario proceso
- `[pp_estadisticas]` - Estadísticas
- `[pp_ediciones]` - Ediciones anteriores

**Páginas/Tabs**:
- dashboard (proceso actual)
- proyectos (listado)
- proponer (nueva)
- votar (votación)
- mis-propuestas (propias)
- resultados (ganadores)
- presupuesto (asignación)
- calendario (fases)
- ediciones (histórico)

**Vinculaciones**: `participacion`, `comunidades`

**Estado**: Completo

---

### radio
**Descripción**: Radio comunitaria online.

**CPTs**: `fc_programa`

**Taxonomías**: `genero_programa`, `dia_emision`

**Dashboard Tab**: `Flavor_Radio_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[radio_player]` - Reproductor en vivo
- `[radio_programacion]` - Parrilla
- `[radio_programa]` - Ficha programa
- `[radio_dedicatorias]` - Enviar dedicatoria
- `[radio_podcast]` - A la carta
- `[radio_chat]` - Chat en vivo

**Páginas/Tabs**:
- dashboard (en vivo)
- programacion (parrilla)
- programas (catálogo)
- a-la-carta (podcast)
- dedicatorias (enviar)
- chat (en directo)

**Vinculaciones**: `podcast`, `comunidades`

**Estado**: Completo

---

### reciclaje
**Descripción**: Sistema de puntos de reciclaje y economía circular.

**CPTs**: `fc_punto_reciclaje`

**Taxonomías**: `tipo_residuo`, `tipo_punto`

**Dashboard Tab**: `Flavor_Reciclaje_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[reciclaje_mapa]` - Mapa puntos
- `[reciclaje_guia]` - Guía separación
- `[reciclaje_registro]` - Registrar aporte
- `[reciclaje_mis_aportes]` - Mis aportes
- `[reciclaje_estadisticas]` - Estadísticas
- `[reciclaje_retos]` - Retos

**Páginas/Tabs**:
- dashboard (resumen)
- mapa (puntos)
- guia (información)
- mis-aportes (historial)
- estadisticas (métricas)
- retos (desafíos)

**Vinculaciones**: `huella-ecologica`, `compostaje`

**Estado**: Completo

---

### recetas
**Descripción**: Recetario comunitario.

**CPTs**: `fc_receta`

**Taxonomías**: `tipo_receta`, `dificultad`, `tiempo`, `ingrediente`

**Dashboard Tab**: Frontend Controller

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[recetas_listar]` - Recetario
- `[recetas_detalle]` - Ver receta
- `[recetas_crear]` - Nueva receta
- `[recetas_mis_recetas]` - Mis recetas
- `[recetas_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (recetario)
- mis-recetas (propias)
- crear (nueva)
- favoritas (guardadas)
- planificador (menú semanal)

**Vinculaciones**: `grupos-consumo`

**Estado**: Completo

---

### red-social
**Descripción**: Red social interna de la plataforma.

**CPTs**: `fc_publicacion`

**Taxonomías**: `tipo_publicacion`

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[rs_feed]` - Feed principal
- `[rs_perfil]` - Perfil usuario
- `[rs_crear_publicacion]` - Nueva publicación
- `[rs_notificaciones]` - Notificaciones
- `[rs_explorar]` - Explorar
- `[rs_historias]` - Stories
- `[rs_badges]` - Insignias
- `[rs_ranking]` - Ranking
- `[rs_reputacion]` - Reputación
- `[rs_mi_actividad]` - Mi actividad
- `[flavor_social_feed]` - Feed (alias)

**Páginas/Tabs**:
- dashboard (feed)
- perfil (mi perfil)
- explorar (descubrir)
- notificaciones (alertas)
- historias (stories)
- badges (insignias)
- amigos (conexiones)
- configuracion (privacidad)
- buscar (usuarios)
- mensajes (DMs)
- actividad (timeline)

**Vinculaciones**: `comunidades`, `chat-interno`

**Estado**: Parcial - Falta dashboard tab

---

### reservas
**Descripción**: Sistema genérico de reservas.

**CPTs**: `fc_recurso_reserva`

**Taxonomías**: `tipo_recurso_reserva`, `estado_reserva`

**Dashboard Tab**: `Flavor_Reservas_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[reservas_calendario]` - Calendario
- `[reservas_disponibilidad]` - Ver disponible
- `[reservas_reservar]` - Nueva reserva
- `[reservas_mis_reservas]` - Mis reservas
- `[reservas_recursos]` - Lista recursos
- `[reservas_gestionar]` - Gestionar (admin)

**Páginas/Tabs**:
- dashboard (calendario)
- mis-reservas (activas)
- historial (pasadas)
- recursos (disponibles)
- nueva (reservar)
- configuracion (preferencias)

**Vinculaciones**: `espacios-comunes`, `bares`, `biblioteca`

**Estado**: Completo

---

### saberes-ancestrales
**Descripción**: Repositorio de conocimientos tradicionales.

**CPTs**: `fc_saber`

**Taxonomías**: `tipo_saber`, `origen`, `ambito_saber`

**Dashboard Tab**: `Flavor_Saberes_Ancestrales_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Saberes_Ancestrales_Widget`

**Shortcodes**:
- `[saberes_catalogo]` - Catálogo
- `[saberes_detalle]` - Ver saber
- `[saberes_compartir]` - Compartir saber
- `[saberes_mis_saberes]` - Mis aportes
- `[saberes_guardianes]` - Guardianes
- `[saberes_buscar]` - Buscador
- `[saberes_mapa]` - Mapa saberes
- `[saberes_multimedia]` - Galería

**Páginas/Tabs**:
- dashboard (catálogo)
- mis-saberes (aportados)
- compartir (nuevo)
- guardianes (maestros)
- mapa (origen)
- favoritos (guardados)
- multimedia (archivos)
- comunidad (intercambio)

**Vinculaciones**: `comunidades`

**Estado**: Completo

---

### seguimiento-denuncias
**Descripción**: Sistema de seguimiento de denuncias ciudadanas.

**CPTs**: `fc_denuncia`

**Taxonomías**: `tipo_denuncia`, `estado_denuncia`, `ambito_denuncia`

**Dashboard Tab**: Frontend Controller

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[denuncias_nueva]` - Nueva denuncia
- `[denuncias_seguimiento]` - Seguir estado
- `[denuncias_mis_denuncias]` - Mis denuncias
- `[denuncias_estadisticas]` - Estadísticas
- `[denuncias_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (resumen)
- nueva (crear)
- mis-denuncias (propias)
- seguimiento (estados)
- estadisticas (métricas)

**Vinculaciones**: `incidencias`, `transparencia`

**Estado**: Completo

---

### sello-conciencia
**Descripción**: Sistema de certificación y sellos de conciencia.

**CPTs**: `fc_sello`

**Taxonomías**: `tipo_sello`, `ambito_sello`

**Dashboard Tab**: No implementado

**Widget Dashboard**: `Flavor_Sello_Conciencia_Widget`

**Shortcodes**:
- `[sello_catalogo]` - Catálogo sellos
- `[sello_solicitar]` - Solicitar sello
- `[sello_mis_sellos]` - Mis sellos
- `[sello_verificar]` - Verificar sello

**Páginas/Tabs**:
- dashboard (mis sellos)
- catalogo (disponibles)
- solicitar (nueva)
- verificar (comprobar)

**Vinculaciones**: `grupos-consumo`, `empresarial`

**Estado**: Parcial - Falta dashboard tab

---

### socios
**Descripción**: Gestión de socios y membresías.

**CPTs**: `fc_socio`

**Taxonomías**: `tipo_socio`, `estado_socio`, `cuota`

**Dashboard Tab**: `Flavor_Socios_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[socios_mi_perfil]` - Mi perfil socio
- `[socios_cuotas]` - Mis cuotas
- `[socios_carnet]` - Carnet digital
- `[socios_directorio]` - Directorio
- `[socios_beneficios]` - Beneficios
- `[socios_alta]` - Darse de alta

**Páginas/Tabs**:
- dashboard (mi perfil)
- cuotas (pagos)
- carnet (digital)
- beneficios (ventajas)
- directorio (otros socios)
- configuracion (datos)

**Vinculaciones**: `comunidades`, `colectivos`

**Estado**: Completo

---

### talleres
**Descripción**: Gestión de talleres y formaciones presenciales.

**CPTs**: `fc_taller`

**Taxonomías**: `categoria_taller`, `nivel_taller`, `ubicacion_taller`

**Dashboard Tab**: `Flavor_Talleres_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[talleres_catalogo]` - Catálogo
- `[talleres_detalle]` - Ficha taller
- `[talleres_inscribirse]` - Inscripción
- `[talleres_mis_talleres]` - Mis talleres
- `[talleres_impartir]` - Proponer taller
- `[talleres_calendario]` - Calendario
- `[talleres_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (próximos)
- catalogo (todos)
- mis-talleres (inscritos)
- impartir (como formador)
- historial (pasados)
- favoritos (guardados)
- certificados (obtenidos)

**Vinculaciones**: `cursos`, `comunidades`, `espacios-comunes`

**Estado**: Completo

---

### themacle
**Descripción**: Integración con temas personalizados.

**CPTs**: Ninguno

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[themacle_config]` - Configuración
- `[themacle_preview]` - Vista previa

**Páginas/Tabs**:
- configuracion (ajustes)
- preview (vista previa)

**Vinculaciones**: Ninguna

**Estado**: Parcial - Módulo utilitario

---

### trabajo-digno
**Descripción**: Bolsa de empleo y economía solidaria.

**CPTs**: `fc_oferta_empleo`

**Taxonomías**: `tipo_empleo`, `sector_empleo`, `modalidad_empleo`

**Dashboard Tab**: `Flavor_Trabajo_Digno_Dashboard_Tab`

**Widget Dashboard**: `Flavor_Trabajo_Digno_Widget`

**Shortcodes**:
- `[empleo_ofertas]` - Ofertas empleo
- `[empleo_detalle]` - Ver oferta
- `[empleo_publicar]` - Publicar oferta
- `[empleo_mi_cv]` - Mi CV
- `[empleo_candidaturas]` - Mis candidaturas
- `[empleo_buscar]` - Buscador
- `[empleo_formacion]` - Formación
- `[empleo_emprendimientos]` - Emprendimientos

**Páginas/Tabs**:
- dashboard (ofertas)
- mi-cv (curriculum)
- candidaturas (aplicaciones)
- publicar (nueva oferta)
- formacion (capacitación)
- emprendimientos (proyectos)
- favoritas (guardadas)
- alertas (notificaciones)

**Vinculaciones**: `comunidades`, `cursos`

**Estado**: Completo

---

### trading-ia
**Descripción**: Sistema de trading asistido por IA.

**CPTs**: Ninguno

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[trading_dashboard]` - Panel trading
- `[trading_senales]` - Señales
- `[trading_portfolio]` - Portfolio

**Páginas/Tabs**:
- dashboard (panel)
- senales (señales)
- portfolio (cartera)

**Vinculaciones**: `dex-solana`

**Estado**: Parcial - Módulo especializado

---

### tramites
**Descripción**: Gestión de trámites administrativos.

**CPTs**: `fc_tramite`

**Taxonomías**: `tipo_tramite`, `estado_tramite`, `departamento`

**Dashboard Tab**: `Flavor_Tramites_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[tramites_catalogo]` - Catálogo trámites
- `[tramites_iniciar]` - Iniciar trámite
- `[tramites_mis_tramites]` - Mis trámites
- `[tramites_seguimiento]` - Seguimiento
- `[tramites_cita]` - Cita previa
- `[tramites_buscar]` - Buscador

**Páginas/Tabs**:
- dashboard (mis trámites)
- catalogo (disponibles)
- iniciar (nuevo)
- seguimiento (estados)
- citas (agenda)
- historial (completados)

**Vinculaciones**: `documentacion-legal`, `avisos-municipales`

**Estado**: Completo

---

### transparencia
**Descripción**: Portal de transparencia y datos abiertos.

**CPTs**: `fc_documento_pub`

**Taxonomías**: `tipo_documento_pub`, `categoria_transparencia`, `periodo`

**Dashboard Tab**: `Flavor_Transparencia_Dashboard_Tab`

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[transparencia_documentos]` - Documentos
- `[transparencia_presupuestos]` - Presupuestos
- `[transparencia_contratos]` - Contratos
- `[transparencia_indicadores]` - Indicadores
- `[transparencia_solicitud]` - Solicitud info
- `[transparencia_buscar]` - Buscador
- `[transparencia_datos_abiertos]` - Open data
- `[transparencia_estadisticas]` - Estadísticas

**Páginas/Tabs**:
- dashboard (resumen)
- documentos (archivo)
- presupuestos (finanzas)
- contratos (licitaciones)
- indicadores (métricas)
- solicitar (derecho acceso)
- datos-abiertos (API/descargas)
- estadisticas (analítica)

**Vinculaciones**: `documentacion-legal`, `seguimiento-denuncias`

**Estado**: Completo

---

### woocommerce
**Descripción**: Integración con WooCommerce.

**CPTs**: Usa CPTs de WooCommerce

**Dashboard Tab**: No implementado

**Widget Dashboard**: No implementado

**Shortcodes**:
- `[flavor_wc_mis_pedidos]` - Mis pedidos
- `[flavor_wc_cuenta]` - Mi cuenta
- `[flavor_wc_tienda]` - Tienda

**Páginas/Tabs**:
- tienda (productos)
- cuenta (mi cuenta)
- pedidos (historial)

**Vinculaciones**: `grupos-consumo`, `marketplace`

**Estado**: Parcial - Depende de WooCommerce

---

## Estadísticas Globales

| Métrica | Valor |
|---------|-------|
| Total módulos | 60 |
| Módulos completos | 45 |
| Módulos parciales | 15 |
| Total CPTs | 52 |
| Total shortcodes | 333 |
| Dashboard Tabs | 38 |
| Widgets Dashboard | 11 |
| Frontend Controllers | 15 |

---

## Vinculaciones Principales

```
comunidades ─────┬─── Núcleo central, se vincula con casi todos
                 │
huella-ecologica ┼─── bicicletas-compartidas, carpooling,
                 │    compostaje, reciclaje, grupos-consumo
                 │
participacion ───┼─── presupuestos-participativos, encuestas
                 │
socios ──────────┼─── colectivos, ayuda-vecinal, banco-tiempo
                 │
empresarial ─────┼─── clientes, facturas
                 │
transparencia ───┼─── documentacion-legal, seguimiento-denuncias
```

---

## Módulos que Necesitan Desarrollo

### Alta Prioridad
1. **red-social** - Falta dashboard tab completo
2. **chat-grupos** - Falta dashboard tab
3. **chat-interno** - Falta dashboard tab
4. **empresarial** - Falta dashboard tab

### Media Prioridad
5. **advertising** - Falta dashboard tab
6. **clientes** - Falta dashboard tab
7. **facturas** - Falta dashboard tab
8. **fichaje-empleados** - Falta dashboard tab

### Baja Prioridad
9. **chat-estados** - Falta dashboard tab (tiene widget)
10. **sello-conciencia** - Falta dashboard tab (tiene widget)
11. **dex-solana** - Módulo especializado
12. **trading-ia** - Módulo especializado
13. **themacle** - Módulo utilitario
14. **woocommerce** - Depende de plugin externo
