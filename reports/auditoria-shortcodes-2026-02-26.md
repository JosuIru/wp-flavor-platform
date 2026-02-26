# Auditoría de Shortcodes

Fecha: 2026-02-26 01:08:46

## Resumen

| Estado | Cantidad |
|--------|----------|
| Total registrados | 628 |
| ✅ Completos | 436 |
| ⚠️ Parciales/Placeholder | 121 |
| ❌ Vacíos/Sin implementar | 71 |

## Por Módulo

### 📦 advertising

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_ad]` | `shortcode_ad()` | ❌ Vacío (return "") |
| `[flavor_ads_dashboard]` | `shortcode_dashboard()` | ✅ Usa template |
| `[flavor_ads_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[flavor_ads_ingresos]` | `shortcode_ingresos()` | ✅ Usa template |

### 📦 avisos-municipales

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[avisos_activos]` | `shortcode_avisos_activos()` | ✅ Implementado |
| `[avisos_zona]` | `shortcode_avisos_zona()` | ✅ Implementado |
| `[suscribirse_avisos]` | `shortcode_suscribirse()` | ✅ Usa template |
| `[historial_avisos]` | `shortcode_historial()` | ✅ Implementado |
| `[aviso_detalle]` | `shortcode_aviso_detalle()` | ✅ Implementado |
| `[avisos_urgentes]` | `shortcode_avisos_urgentes()` | ❌ Vacío (return "") |
| `[flavor_avisos_listado]` | `shortcode_listado()` | ✅ Implementado |
| `[flavor_avisos_urgentes]` | `shortcode_urgentes()` | ❌ Vacío (return "") |
| `[flavor_avisos_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_avisos_suscripciones]` | `shortcode_suscripciones()` | ✅ Implementado |
| `[flavor_avisos_buscador]` | `shortcode_buscador()` | ✅ Implementado |
| `[flavor_avisos_categorias]` | `shortcode_categorias()` | ✅ Implementado |
| `[flavor_avisos_banner]` | `shortcode_banner()` | ❌ Vacío (return "") |
| `[flavor_avisos_dashboard]` | `shortcode_dashboard()` | ⚠️ Parcial (334 chars) |

### 📦 ayuda-vecinal

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[ayuda_vecinal_solicitudes]` | `shortcode_solicitudes()` | ⚠️ Parcial (311 chars) |
| `[ayuda_vecinal_voluntarios]` | `shortcode_voluntarios()` | ⚠️ Parcial (240 chars) |
| `[ayuda_vecinal_solicitar]` | `shortcode_solicitar()` | ✅ Usa template |
| `[ayuda_vecinal_ofrecer]` | `shortcode_ofrecer()` | ✅ Usa template |
| `[ayuda_vecinal_mis_solicitudes]` | `shortcode_mis_solicitudes()` | ✅ Usa template |
| `[ayuda_vecinal_mis_ayudas]` | `shortcode_mis_ayudas()` | ✅ Usa template |
| `[ayuda_vecinal_estadisticas]` | `shortcode_estadisticas()` | ⚠️ Muy corto/básico |
| `[ayuda_vecinal_cercana]` | `shortcode_cercana()` | ✅ Implementado |

### 📦 banco-tiempo

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[bt_mi_reputacion]` | `shortcode_mi_reputacion()` | ✅ Encontrado |
| `[bt_ranking_comunidad]` | `shortcode_ranking_comunidad()` | ✅ Encontrado |
| `[bt_fondo_solidario]` | `shortcode_fondo_solidario()` | ✅ Encontrado |
| `[bt_dashboard_sostenibilidad]` | `shortcode_dashboard_sostenibilidad()` | ✅ Encontrado |
| `[bt_donar_horas]` | `shortcode_donar_horas()` | ✅ Encontrado |
| `[banco_tiempo_servicios]` | `shortcode_servicios()` | ⚠️ Parcial (308 chars) |
| `[banco_tiempo_mi_saldo]` | `shortcode_mi_saldo()` | ✅ Usa template |
| `[banco_tiempo_mis_servicios]` | `shortcode_mis_servicios()` | ✅ Usa template |
| `[banco_tiempo_mis_intercambios]` | `shortcode_mis_intercambios()` | ✅ Usa template |
| `[banco_tiempo_ofrecer]` | `shortcode_ofrecer()` | ✅ Usa template |
| `[banco_tiempo_detalle]` | `shortcode_detalle()` | ⚠️ Parcial (469 chars) |
| `[banco_tiempo_ranking]` | `shortcode_ranking()` | ⚠️ Parcial (205 chars) |
| `[banco_tiempo_ultimos_intercambios]` | `shortcode_ultimos_intercambios()` | ✅ Implementado |

### 📦 biblioteca

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[biblioteca_catalogo]` | `shortcode_catalogo()` | ✅ Usa template |
| `[biblioteca_mis_prestamos]` | `shortcode_mis_prestamos()` | ✅ Usa template |
| `[biblioteca_reservas]` | `shortcode_reservas()` | ⚠️ Parcial (305 chars) |
| `[biblioteca_busqueda]` | `shortcode_busqueda()` | ✅ Implementado |
| `[biblioteca_novedades]` | `shortcode_novedades()` | ✅ Implementado |
| `[biblioteca_prestamos_activos]` | `shortcode_prestamos_activos()` | ❌ Vacío (return "") |
| `[biblioteca_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[biblioteca_mis_libros]` | `shortcode_mis_libros()` | ✅ Usa template |
| `[biblioteca_agregar]` | `shortcode_agregar()` | ✅ Usa template |

### 📦 bicicletas-compartidas

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[bicicletas-compartidas_mis-prestamos]` | `shortcode_mis_prestamos()` | ⚠️ Placeholder/TODO |
| `[flavor_bicicletas_compartidas_acciones]` | `shortcode_acciones()` | ✅ Usa template |
| `[bicicletas_estaciones_cercanas]` | `shortcode_estaciones_cercanas()` | ❌ Vacío (return "") |
| `[bicicletas_prestamo_actual]` | `shortcode_prestamo_actual()` | ❌ Vacío (return "") |
| `[flavor_bicicletas_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[flavor_bicicletas_estaciones]` | `shortcode_estaciones()` | ✅ Implementado |
| `[flavor_bicicletas_disponibles]` | `shortcode_disponibles()` | ⚠️ Placeholder/TODO |
| `[flavor_bicicletas_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_bicicletas_reservar]` | `shortcode_reservar()` | ❌ Método no encontrado |
| `[flavor_bicicletas_mis_prestamos]` | `shortcode_mis_prestamos()` | ✅ Implementado |
| `[flavor_bicicletas_prestamo_activo]` | `shortcode_prestamo_activo()` | ❌ Vacío (return "") |
| `[flavor_bicicletas_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |

### 📦 biodiversidad-local

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_biodiversidad_catalogo]` | `shortcode_catalogo()` | ⚠️ Placeholder/TODO |
| `[flavor_biodiversidad_especie]` | `shortcode_especie()` | ✅ Implementado |
| `[flavor_biodiversidad_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[flavor_biodiversidad_reportar]` | `shortcode_reportar()` | ✅ Usa template |
| `[flavor_biodiversidad_mis_avistamientos]` | `shortcode_mis_avistamientos()` | ❌ Vacío (return "") |
| `[flavor_biodiversidad_proyectos]` | `shortcode_proyectos()` | ✅ Implementado |
| `[flavor_biodiversidad_proyecto]` | `shortcode_proyecto()` | ✅ Implementado |
| `[flavor_biodiversidad_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[biodiversidad_catalogo]` | `shortcode_catalogo()` | ⚠️ Muy corto/básico |
| `[biodiversidad_mapa]` | `shortcode_mapa()` | ⚠️ Muy corto/básico |
| `[biodiversidad_registrar]` | `shortcode_registrar()` | ⚠️ Muy corto/básico |
| `[biodiversidad_proyectos]` | `shortcode_proyectos()` | ⚠️ Muy corto/básico |
| `[biodiversidad_mis_avistamientos]` | `shortcode_mis_avistamientos()` | ⚠️ Muy corto/básico |

### 📦 campanias

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_campanias_dashboard]` | `shortcode_dashboard()` | ✅ Usa template |
| `[flavor_campanias_destacadas]` | `shortcode_destacadas()` | ✅ Implementado |
| `[campanias_listar]` | `shortcode_listar()` | ✅ Usa template |
| `[campanias_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[campanias_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[campanias_mis_campanias]` | `shortcode_mis_campanias()` | ✅ Usa template |
| `[campanias_firmar]` | `shortcode_firmar()` | ✅ Usa template |
| `[campanias_mapa]` | `shortcode_mapa()` | ⚠️ Muy corto/básico |
| `[campanias_calendario]` | `shortcode_calendario()` | ⚠️ Muy corto/básico |

### 📦 carpooling

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[carpooling_viajes]` | `shortcode_viajes()` | ⚠️ Parcial (310 chars) |
| `[carpooling_mis_viajes]` | `shortcode_mis_viajes()` | ✅ Usa template |
| `[carpooling_mis_reservas]` | `shortcode_mis_reservas()` | ✅ Usa template |
| `[carpooling_publicar]` | `shortcode_publicar()` | ✅ Usa template |
| `[carpooling_buscar]` | `shortcode_buscar()` | ✅ Implementado |
| `[carpooling_proximo_viaje]` | `shortcode_proximo_viaje()` | ❌ Vacío (return "") |
| `[carpooling_busqueda_rapida]` | `shortcode_busqueda_rapida()` | ✅ Implementado |
| `[carpooling_buscar_viaje]` | `shortcode_buscar_viaje()` | ✅ Usa template |
| `[carpooling_publicar_viaje]` | `shortcode_publicar_viaje()` | ✅ Usa template |

### 📦 chat-estados

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_estados]` | `shortcode_estados()` | ✅ Usa template |
| `[flavor_estados_crear]` | `shortcode_crear_estado()` | ❌ Vacío (return "") |

### 📦 chat-grupos

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_chat_grupos]` | `shortcode_chat_grupos()` | ✅ Usa template |
| `[flavor_chat_grupo]` | `shortcode_chat_grupo()` | ✅ Usa template |
| `[flavor_grupos_lista]` | `shortcode_grupos_lista()` | ✅ Implementado |
| `[flavor_grupos_explorar]` | `shortcode_grupos_explorar()` | ✅ Implementado |
| `[flavor_grupos_crear]` | `shortcode_crear_grupo()` | ✅ Usa template |
| `[chat_grupos_sin_leer]` | `shortcode_sin_leer()` | ❌ Vacío (return "") |
| `[chat_mensajes_sin_leer]` | `shortcode_mensajes_sin_leer()` | ❌ Vacío (return "") |
| `[flavor_chat_grupo_integrado]` | `shortcode_chat_integrado()` | ✅ Usa template |

### 📦 chat-interno

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_chat_inbox]` | `shortcode_inbox()` | ✅ Usa template |
| `[flavor_chat_conversacion]` | `shortcode_conversacion()` | ✅ Usa template |
| `[flavor_iniciar_chat]` | `shortcode_iniciar_chat()` | ❌ Vacío (return "") |

### 📦 circulos-cuidados

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_circulos_listado]` | `shortcode_listado()` | ✅ Usa template |
| `[flavor_circulos_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_circulos_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[flavor_circulos_necesidades]` | `shortcode_necesidades()` | ✅ Usa template |
| `[flavor_circulos_publicar_necesidad]` | `shortcode_publicar_necesidad()` | ❌ Vacío (return "") |
| `[flavor_circulos_mis_circulos]` | `shortcode_mis_circulos()` | ❌ Vacío (return "") |
| `[flavor_circulos_mis_horas]` | `shortcode_mis_horas()` | ❌ Vacío (return "") |
| `[flavor_circulos_mapa]` | `shortcode_mapa()` | ⚠️ Parcial (327 chars) |
| `[circulos_cuidados]` | `shortcode_listado()` | ✅ Usa template |
| `[mis_cuidados]` | `shortcode_mis_cuidados()` | ✅ Usa template |
| `[necesidades_cuidados]` | `shortcode_necesidades()` | ✅ Usa template |
| `[flavor_circulos_mis_cuidados]` | `shortcode_mis_cuidados()` | ✅ Usa template |

### 📦 colectivos

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_colectivos_listado]` | `shortcode_listado()` | ⚠️ Placeholder/TODO |
| `[flavor_colectivos_detalle]` | `shortcode_detalle()` | ⚠️ Placeholder/TODO |
| `[flavor_colectivos_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[flavor_colectivos_mis_colectivos]` | `shortcode_mis_colectivos()` | ❌ Vacío (return "") |
| `[flavor_colectivos_proyectos]` | `shortcode_proyectos()` | ❌ Vacío (return "") |
| `[flavor_colectivos_asambleas]` | `shortcode_asambleas()` | ❌ Vacío (return "") |
| `[flavor_colectivos_miembros]` | `shortcode_miembros()` | ❌ Vacío (return "") |
| `[flavor_colectivos_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[colectivos_listar]` | `shortcode_listar()` | ✅ Usa template |
| `[colectivos_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[colectivos_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[colectivos_mis_colectivos]` | `shortcode_mis_colectivos()` | ✅ Usa template |
| `[colectivos_proyectos]` | `shortcode_proyectos()` | ✅ Usa template |
| `[colectivos_asambleas]` | `shortcode_asambleas()` | ✅ Usa template |
| `[colectivos_mi_actividad]` | `shortcode_mi_actividad()` | ✅ Usa template |

### 📦 compostaje

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[mapa_composteras]` | `shortcode_mapa_composteras()` | ⚠️ Placeholder/TODO |
| `[registrar_aportacion]` | `shortcode_registrar_aportacion()` | ✅ Usa template |
| `[mis_aportaciones]` | `shortcode_mis_aportaciones()` | ✅ Implementado |
| `[guia_compostaje]` | `shortcode_guia_compostaje()` | ✅ Implementado |
| `[ranking_compostaje]` | `shortcode_ranking_compostaje()` | ✅ Implementado |
| `[estadisticas_compostaje]` | `shortcode_estadisticas_compostaje()` | ✅ Implementado |
| `[turnos_compostaje]` | `shortcode_turnos_compostaje()` | ⚠️ Placeholder/TODO |
| `[compostaje_cercana]` | `shortcode_cercana()` | ❌ Vacío (return "") |
| `[compostaje_mi_balance]` | `shortcode_mi_balance()` | ❌ Vacío (return "") |
| `[flavor_compostaje_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[flavor_compostaje_puntos]` | `shortcode_lista_puntos()` | ✅ Implementado |
| `[flavor_compostaje_registrar]` | `shortcode_registrar()` | ✅ Usa template |
| `[flavor_compostaje_mis_aportaciones]` | `shortcode_mis_aportaciones()` | ✅ Implementado |
| `[flavor_compostaje_turnos]` | `shortcode_turnos()` | ⚠️ Placeholder/TODO |
| `[flavor_compostaje_guia]` | `shortcode_guia()` | ✅ Implementado |
| `[flavor_compostaje_ranking]` | `shortcode_ranking()` | ✅ Implementado |
| `[flavor_compostaje_mi_balance]` | `shortcode_mi_balance()` | ❌ Vacío (return "") |
| `[flavor_compostaje_dashboard]` | `shortcode_dashboard()` | ⚠️ Parcial (334 chars) |

### 📦 comunidades

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[comunidades_listado]` | `shortcode_listado()` | ✅ Implementado |
| `[comunidades_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[comunidades_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[comunidades_mis_comunidades]` | `shortcode_mis_comunidades()` | ✅ Usa template |
| `[comunidades_feed]` | `shortcode_feed()` | ✅ Usa template |
| `[comunidades_miembros]` | `shortcode_miembros()` | ✅ Implementado |
| `[comunidades_listar]` | `shortcode_listado()` | ✅ Usa template |
| `[comunidades_actividad]` | `shortcode_feed_actividad()` | ✅ Implementado |
| `[comunidades_feed_unificado]` | `shortcode_feed_unificado()` | ✅ Usa template |
| `[comunidades_calendario]` | `shortcode_calendario_coordinado()` | ⚠️ Placeholder/TODO |
| `[comunidades_recursos_compartidos]` | `shortcode_recursos_compartidos()` | ✅ Usa template |
| `[comunidades_notificaciones]` | `shortcode_centro_notificaciones()` | ✅ Usa template |
| `[comunidades_busqueda]` | `shortcode_busqueda_federada()` | ⚠️ Muy corto/básico |
| `[comunidades_tablon]` | `shortcode_tablon_anuncios()` | ✅ Usa template |
| `[comunidades_metricas]` | `shortcode_metricas_colaboracion()` | ⚠️ Muy corto/básico |

### 📦 core

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_module_form]` | `render_module_form()` | ⚠️ Placeholder/TODO |
| `[flavor_module_listing]` | `render_module_listing()` | ✅ Implementado |
| `[flavor_network_directory]` | `shortcode_directory()` | ✅ Usa template |
| `[flavor_network_map]` | `shortcode_map()` | ✅ Usa template |
| `[flavor_network_board]` | `shortcode_board()` | ✅ Usa template |
| `[flavor_network_events]` | `shortcode_events()` | ✅ Usa template |
| `[flavor_network_alerts]` | `shortcode_alerts()` | ✅ Usa template |
| `[flavor_network_catalog]` | `shortcode_catalog()` | ✅ Usa template |
| `[flavor_network_collaborations]` | `shortcode_collaborations()` | ✅ Usa template |
| `[flavor_network_time_offers]` | `shortcode_time_offers()` | ✅ Usa template |
| `[flavor_network_node_profile]` | `shortcode_node_profile()` | ✅ Usa template |
| `[flavor_network_questions]` | `shortcode_network_questions()` | ✅ Usa template |
| `[flavor_privacidad]` | `render_privacy_panel()` | ✅ Usa template |
| `[flavor_consentimientos]` | `render_consent_form()` | ❌ Vacío (return "") |
| `[mi_modulo_listado]` | `shortcode_listado()` | ⚠️ Parcial (362 chars) |
| `[flavor_red_contenido]` | `shortcode_network_content()` | ✅ Implementado |
| `[flavor_red_recetas]` | `shortcode_network_recipes()` | ⚠️ Parcial (92 chars) |
| `[flavor_red_eventos]` | `shortcode_network_events()` | ⚠️ Parcial (92 chars) |
| `[flavor_red_comunidades]` | `shortcode_network_communities()` | ✅ Implementado |
| `[flavor_dashboard_unificado]` | `shortcode_dashboard_unificado()` | ⚠️ Placeholder/TODO |
| `[flavor_dashboard_widget]` | `shortcode_dashboard_widget()` | ✅ Implementado |
| `[flavor_visual_builder]` | `render_shortcode()` | ❌ Vacío (return "") |
| `[flavor_app]` | `render_app()` | ⚠️ Parcial (479 chars) |
| `[flavor_mi_cuenta]` | `render_dashboard()` | ⚠️ Placeholder/TODO |
| `[flavor_user_dashboard]` | `render_dashboard()` | ⚠️ Placeholder/TODO |
| `[flavor_landing]` | `render_landing()` | ✅ Implementado |
| `[flavor_client_dashboard]` | `render_dashboard()` | ✅ Usa template |
| `[flavor_crud_form]` | `render_form_shortcode()` | ⚠️ Parcial (178 chars) |
| `[flavor_crud_list]` | `render_list_shortcode()` | ⚠️ Parcial (202 chars) |
| `[flavor_mis_registros]` | `render_my_records_shortcode()` | ⚠️ Parcial (157 chars) |
| `[flavor_notificaciones]` | `shortcode_notificaciones()` | ❌ Vacío (return "") |
| `[flavor_notificaciones_badge]` | `shortcode_badge()` | ❌ Vacío (return "") |
| `[flavor_busqueda_social]` | `shortcode_busqueda()` | ✅ Implementado |
| `[flavor_social_search]` | `shortcode_busqueda()` | ✅ Implementado |
| `[flavor_breadcrumbs]` | `render_shortcode()` | ✅ Encontrado |
| `[flavor_theme_customizer]` | `render_customizer()` | ❌ Vacío (return "") |
| `[flavor_notifications_widget]` | `render_widget()` | ❌ Vacío (return "") |
| `[flavor_landing_visual]` | `render_from_shortcode()` | ❌ Vacío (return "") |
| `[flavor_adaptive_menu]` | `render_menu()` | ✅ Implementado |
| `[flavor_menu]` | `shortcode_menu()` | ⚠️ Parcial (489 chars) |
| `[flavor_footer]` | `shortcode_footer()` | ⚠️ Parcial (493 chars) |
| `[flavor_chat]` | `render_chat_shortcode()` | ❌ Vacío (return "") |
| `[flavor_section]` | `render_section()` | ✅ Implementado |
| `[flavor_grupos_consumo]` | `render_grupos_consumo()` | ⚠️ Parcial (238 chars) |
| `[flavor_banco_tiempo]` | `render_banco_tiempo()` | ⚠️ Parcial (234 chars) |
| `[flavor_module_nav]` | `render_module_nav()` | ❌ Vacío (return "") |
| `[flavor_page_header]` | `render_page_header()` | ✅ Implementado |
| `[flavor_unified_dashboard]` | `render_shortcode()` | ✅ Encontrado |
| `[flavor_servicios]` | `render_servicios()` | ⚠️ Placeholder/TODO |
| `[flavor_mi_portal]` | `render_mi_portal()` | ⚠️ Placeholder/TODO |
| `[flavor_modulos_grid]` | `render_modulos_grid()` | ✅ Implementado |
| `[flavor_modulos_grid]` | `render_modulos_grid()` | ✅ Implementado |

### 📦 cursos

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[cursos_catalogo]` | `shortcode_catalogo()` | ✅ Usa template |
| `[cursos_mis_inscripciones]` | `shortcode_mis_inscripciones()` | ⚠️ Parcial (315 chars) |
| `[cursos_mis_cursos]` | `shortcode_mis_cursos()` | ✅ Usa template |
| `[cursos_calendario]` | `shortcode_calendario()` | ⚠️ Placeholder/TODO |
| `[cursos_destacados]` | `shortcode_destacados()` | ✅ Implementado |
| `[cursos_busqueda]` | `shortcode_busqueda()` | ✅ Implementado |
| `[cursos_aula]` | `shortcode_aula()` | ✅ Usa template |
| `[cursos_mi_progreso]` | `shortcode_mi_progreso()` | ✅ Implementado |
| `[cursos_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[cursos_instructor]` | `shortcode_instructor()` | ✅ Usa template |
| `[cursos_certificado]` | `shortcode_certificado()` | ✅ Usa template |

### 📦 documentacion-legal

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_documentacion_legal_dashboard]` | `shortcode_dashboard()` | ⚠️ Parcial (80 chars) |
| `[documentacion_legal_listar]` | `shortcode_listar()` | ✅ Usa template |
| `[documentacion_legal_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[documentacion_legal_buscar]` | `shortcode_buscar()` | ⚠️ Muy corto/básico |
| `[documentacion_legal_categorias]` | `shortcode_categorias()` | ⚠️ Muy corto/básico |
| `[documentacion_legal_subir]` | `shortcode_subir()` | ✅ Usa template |
| `[documentacion_legal_mis_guardados]` | `shortcode_mis_guardados()` | ✅ Usa template |

### 📦 economia-don

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[economia_don]` | `shortcode_listado()` | ✅ Usa template |
| `[mis_dones]` | `shortcode_mis_dones()` | ✅ Usa template |
| `[ofrecer_don]` | `shortcode_ofrecer()` | ✅ Usa template |
| `[muro_gratitud]` | `shortcode_muro_gratitud()` | ✅ Usa template |
| `[flavor_don_listado]` | `shortcode_listado()` | ⚠️ Placeholder/TODO |
| `[flavor_don_mis_dones]` | `shortcode_mis_dones()` | ❌ Vacío (return "") |
| `[flavor_don_ofrecer]` | `shortcode_ofrecer()` | ✅ Usa template |
| `[flavor_don_muro_gratitud]` | `shortcode_muro_gratitud()` | ✅ Implementado |
| `[flavor_don_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_don_mis_recepciones]` | `shortcode_mis_recepciones()` | ❌ Vacío (return "") |
| `[flavor_don_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |

### ✅ economia-suficiencia (10 shortcodes OK)

### 📦 email-marketing

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[em_formulario_suscripcion]` | `shortcode_formulario_suscripcion()` | ✅ Usa template |
| `[em_preferencias]` | `shortcode_preferencias()` | ✅ Usa template |
| `[em_confirmar_suscripcion]` | `shortcode_confirmar()` | ✅ Implementado |
| `[em_darse_baja]` | `shortcode_baja()` | ✅ Usa template |
| `[flavor_suscripcion_newsletter]` | `shortcode_suscripcion_newsletter()` | ⚠️ Parcial (64 chars) |
| `[flavor_preferencias_email]` | `shortcode_preferencias_email()` | ⚠️ Parcial (54 chars) |
| `[flavor_archivo_newsletters]` | `shortcode_archivo_newsletters()` | ✅ Implementado |
| `[flavor_contador_suscriptores]` | `shortcode_contador_suscriptores()` | ✅ Implementado |
| `[flavor_formulario_popup]` | `shortcode_formulario_popup()` | ✅ Usa template |
| `[flavor_email_preview]` | `shortcode_email_preview()` | ❌ Vacío (return "") |

### ✅ empresarial (5 shortcodes OK)

### 📦 encuestas

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_encuesta]` | `shortcode_encuesta()` | ❌ Vacío (return "") |
| `[flavor_encuesta_crear]` | `shortcode_crear_encuesta()` | ❌ Vacío (return "") |
| `[flavor_encuestas_contexto]` | `shortcode_encuestas_contexto()` | ❌ Vacío (return "") |
| `[flavor_encuesta_resultados]` | `shortcode_resultados()` | ❌ Vacío (return "") |
| `[flavor_encuesta_mini]` | `shortcode_encuesta_mini()` | ❌ Vacío (return "") |

### 📦 espacios-comunes

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[espacios_listado]` | `shortcode_listado()` | ✅ Usa template |
| `[espacios_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[espacios_reservar]` | `shortcode_reservar()` | ✅ Usa template |
| `[espacios_mis_reservas]` | `shortcode_mis_reservas()` | ✅ Usa template |
| `[espacios_calendario]` | `shortcode_calendario()` | ✅ Usa template |
| `[espacios_equipamiento]` | `shortcode_equipamiento()` | ✅ Usa template |
| `[espacios_calendario_mini]` | `shortcode_calendario_mini()` | ✅ Implementado |
| `[espacios_proxima_reserva]` | `shortcode_proxima_reserva()` | ❌ Vacío (return "") |
| `[ec_cesiones_disponibles]` | `shortcode_cesiones_disponibles()` | ✅ Usa template |
| `[ec_huella_espacio]` | `shortcode_huella_espacio()` | ✅ Usa template |
| `[ec_voluntariado]` | `shortcode_voluntariado()` | ✅ Usa template |
| `[ec_dashboard_sostenibilidad]` | `shortcode_dashboard_sostenibilidad()` | ✅ Usa template |
| `[ec_mi_impacto]` | `shortcode_mi_impacto()` | ✅ Usa template |

### 📦 eventos

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[ev_eventos_inclusivos]` | `shortcode_eventos_inclusivos()` | ✅ Usa template |
| `[ev_huella_evento]` | `shortcode_huella_evento()` | ✅ Usa template |
| `[ev_voluntariado_eventos]` | `shortcode_voluntariado_eventos()` | ✅ Usa template |
| `[ev_dashboard_impacto]` | `shortcode_dashboard_impacto()` | ✅ Usa template |
| `[ev_mi_participacion]` | `shortcode_mi_participacion()` | ✅ Usa template |
| `[eventos_listado]` | `shortcode_listado()` | ⚠️ Parcial (332 chars) |
| `[eventos_calendario]` | `shortcode_calendario()` | ⚠️ Parcial (275 chars) |
| `[eventos_mis_inscripciones]` | `shortcode_mis_inscripciones()` | ✅ Usa template |
| `[eventos_detalle]` | `shortcode_detalle()` | ⚠️ Parcial (448 chars) |
| `[eventos_proximos]` | `shortcode_proximos()` | ⚠️ Parcial (234 chars) |
| `[eventos_destacados]` | `shortcode_destacados()` | ⚠️ Parcial (207 chars) |
| `[flavor_eventos_acciones]` | `shortcode_acciones()` | ⚠️ Placeholder/TODO |
| `[eventos_proximo]` | `shortcode_proximo()` | ⚠️ Parcial (137 chars) |

### 📦 facturas

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_mis_facturas]` | `shortcode_mis_facturas()` | ⚠️ Placeholder/TODO |
| `[flavor_detalle_factura]` | `shortcode_detalle_factura()` | ⚠️ Placeholder/TODO |
| `[flavor_pagar_factura]` | `shortcode_pagar_factura()` | ⚠️ Parcial (125 chars) |
| `[flavor_historial_pagos]` | `shortcode_historial_pagos()` | ❌ Vacío (return "") |
| `[flavor_nueva_factura]` | `shortcode_nueva_factura()` | ⚠️ Placeholder/TODO |

### 📦 fichaje-empleados

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[fichaje_panel]` | `render_panel_fichaje()` | ✅ Usa template |
| `[fichaje_historial]` | `render_historial()` | ✅ Usa template |
| `[fichaje_resumen]` | `render_resumen()` | ✅ Usa template |
| `[fichaje_boton]` | `render_boton_fichaje()` | ❌ Vacío (return "") |
| `[fichaje_estado]` | `render_estado_actual()` | ✅ Usa template |
| `[fichaje_solicitar_cambio]` | `render_formulario_cambio()` | ✅ Usa template |
| `[flavor_fichaje_empleados_acciones]` | `render_acciones()` | ✅ Usa template |

### 📦 foros

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_foros_listado]` | `shortcode_listado_foros()` | ✅ Implementado |
| `[flavor_foros_categoria]` | `shortcode_categoria()` | ✅ Implementado |
| `[flavor_foros_tema]` | `shortcode_tema()` | ✅ Usa template |
| `[flavor_foros_nuevo_tema]` | `shortcode_nuevo_tema()` | ✅ Usa template |
| `[flavor_foros_mis_temas]` | `shortcode_mis_temas()` | ✅ Implementado |
| `[flavor_foros_mis_respuestas]` | `shortcode_mis_respuestas()` | ❌ Vacío (return "") |
| `[flavor_foros_buscar]` | `shortcode_buscar()` | ✅ Implementado |
| `[flavor_foros_actividad_reciente]` | `shortcode_actividad_reciente()` | ✅ Implementado |
| `[flavor_foros_integrado]` | `shortcode_foro_integrado()` | ⚠️ Placeholder/TODO |

### 📦 grupos-consumo

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[gc_catalogo]` | `shortcode_catalogo()` | ⚠️ Placeholder/TODO |
| `[gc_carrito]` | `shortcode_carrito()` | ✅ Implementado |
| `[gc_calendario]` | `shortcode_calendario()` | ⚠️ Placeholder/TODO |
| `[gc_historial]` | `shortcode_historial()` | ✅ Implementado |
| `[gc_suscripciones]` | `shortcode_suscripciones()` | ✅ Implementado |
| `[gc_mi_cesta]` | `shortcode_mi_cesta()` | ✅ Implementado |
| `[gc_formulario_union]` | `shortcode_formulario_union()` | ✅ Implementado |
| `[gc_grupos_lista]` | `shortcode_grupos_lista()` | ✅ Implementado |
| `[gc_ciclo_actual]` | `shortcode_ciclo_actual()` | ✅ Implementado |
| `[gc_productos]` | `shortcode_productos()` | ✅ Implementado |
| `[gc_mi_pedido]` | `shortcode_mi_pedido()` | ⚠️ Parcial (333 chars) |
| `[gc_productores_cercanos]` | `shortcode_productores_cercanos()` | ⚠️ Placeholder/TODO |
| `[gc_panel]` | `shortcode_panel()` | ✅ Implementado |
| `[gc_nav]` | `shortcode_nav()` | ❌ Vacío (return "") |
| `[gc_mis_pedidos]` | `shortcode_historial()` | ✅ Implementado |
| `[gc_productores]` | `shortcode_productores_cercanos()` | ⚠️ Placeholder/TODO |
| `[gc_ciclos]` | `shortcode_ciclo_actual()` | ✅ Implementado |
| `[gc_excedentes_disponibles]` | `shortcode_excedentes()` | ✅ Encontrado |
| `[gc_huella_ciclo]` | `shortcode_huella_ciclo()` | ✅ Encontrado |
| `[gc_precio_transparente]` | `shortcode_precio_transparente()` | ✅ Encontrado |
| `[gc_tablero_trueques]` | `shortcode_tablero_trueques()` | ✅ Encontrado |

### ✅ huella-ecologica (10 shortcodes OK)

### 📦 huertos-urbanos

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[huertos_listado]` | `shortcode_listado()` | ✅ Implementado |
| `[huertos_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[huertos_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[huertos_solicitar]` | `shortcode_solicitar()` | ✅ Usa template |
| `[huertos_mi_parcela]` | `shortcode_mi_parcela()` | ✅ Usa template |
| `[huertos_diario]` | `shortcode_diario()` | ✅ Usa template |
| `[huertos_cultivos]` | `shortcode_cultivos()` | ✅ Usa template |
| `[mapa_huertos]` | `shortcode_mapa_huertos()` | ⚠️ Placeholder/TODO |
| `[mi_parcela]` | `shortcode_mi_parcela()` | ✅ Implementado |
| `[calendario_cultivos]` | `shortcode_calendario_cultivos()` | ✅ Implementado |
| `[intercambios_huertos]` | `shortcode_intercambios()` | ✅ Implementado |
| `[tareas_huerto]` | `shortcode_tareas_huerto()` | ✅ Implementado |
| `[lista_huertos]` | `shortcode_lista_huertos()` | ✅ Implementado |

### ✅ incidencias (13 shortcodes OK)

### 📦 justicia-restaurativa

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[justicia_restaurativa]` | `shortcode_info()` | ⚠️ Muy corto/básico |
| `[solicitar_mediacion]` | `shortcode_solicitar()` | ✅ Usa template |
| `[mis_procesos]` | `shortcode_mis_procesos()` | ✅ Usa template |
| `[mediadores]` | `shortcode_mediadores()` | ⚠️ Muy corto/básico |
| `[flavor_justicia_info]` | `shortcode_info()` | ⚠️ Muy corto/básico |
| `[flavor_justicia_solicitar]` | `shortcode_solicitar()` | ✅ Usa template |
| `[flavor_justicia_mis_procesos]` | `shortcode_mis_procesos()` | ✅ Usa template |
| `[flavor_justicia_mediadores]` | `shortcode_mediadores()` | ✅ Implementado |
| `[flavor_justicia_inicio]` | `shortcode_inicio()` | ⚠️ Placeholder/TODO |
| `[flavor_justicia_mis_casos]` | `shortcode_mis_casos()` | ✅ Usa template |
| `[flavor_justicia_caso]` | `shortcode_caso()` | ✅ Usa template |
| `[flavor_justicia_recursos]` | `shortcode_recursos()` | ✅ Implementado |
| `[flavor_justicia_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |

### 📦 mapa-actores

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_mapa_actores]` | `shortcode_mapa()` | ✅ Implementado |
| `[flavor_mapa_actores_directorio]` | `shortcode_directorio()` | ⚠️ Placeholder/TODO |
| `[flavor_mapa_actores_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_mapa_actores_buscador]` | `shortcode_buscador()` | ✅ Implementado |
| `[flavor_mapa_actores_grafo]` | `shortcode_grafo()` | ✅ Implementado |
| `[flavor_mapa_actores_proponer]` | `shortcode_proponer()` | ✅ Usa template |
| `[flavor_mapa_actores_dashboard]` | `shortcode_dashboard()` | ⚠️ Muy corto/básico |
| `[actores_listar]` | `shortcode_listar()` | ✅ Usa template |
| `[actores_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[actores_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[actores_mapa]` | `shortcode_mapa()` | ⚠️ Muy corto/básico |
| `[actores_grafo]` | `shortcode_grafo()` | ⚠️ Muy corto/básico |
| `[actores_buscar]` | `shortcode_buscar()` | ⚠️ Muy corto/básico |

### 📦 marketplace

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[marketplace_catalogo]` | `shortcode_catalogo()` | ⚠️ Parcial (376 chars) |
| `[marketplace_listado]` | `shortcode_catalogo()` | ⚠️ Parcial (376 chars) |
| `[marketplace_mis_anuncios]` | `shortcode_mis_anuncios()` | ⚠️ Placeholder/TODO |
| `[marketplace_formulario]` | `shortcode_formulario()` | ✅ Usa template |
| `[marketplace_detalle]` | `shortcode_detalle()` | ❌ Método no encontrado |
| `[marketplace_favoritos]` | `shortcode_favoritos()` | ⚠️ Parcial (308 chars) |
| `[marketplace_busqueda]` | `shortcode_busqueda()` | ⚠️ Placeholder/TODO |

### 📦 multimedia

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_galeria]` | `shortcode_galeria()` | ⚠️ Placeholder/TODO |
| `[flavor_albumes]` | `shortcode_albumes()` | ✅ Implementado |
| `[flavor_subir_multimedia]` | `shortcode_subir()` | ✅ Implementado |
| `[flavor_mi_galeria]` | `shortcode_mi_galeria()` | ⚠️ Placeholder/TODO |
| `[flavor_carousel]` | `shortcode_carousel()` | ✅ Implementado |
| `[flavor_multimedia_galeria]` | `shortcode_galeria()` | ✅ Implementado |
| `[flavor_multimedia_mis_fotos]` | `shortcode_mis_fotos()` | ✅ Usa template |
| `[flavor_multimedia_subir]` | `shortcode_subir()` | ✅ Usa template |
| `[flavor_multimedia_albumes]` | `shortcode_albumes()` | ✅ Implementado |
| `[flavor_multimedia_album]` | `shortcode_album()` | ✅ Implementado |
| `[flavor_multimedia_visor]` | `shortcode_visor()` | ✅ Implementado |
| `[flavor_multimedia_dashboard]` | `shortcode_dashboard()` | ⚠️ Muy corto/básico |

### 📦 parkings

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_mapa_parkings]` | `shortcode_mapa_parkings()` | ✅ Implementado |
| `[flavor_disponibilidad_parking]` | `shortcode_disponibilidad()` | ✅ Implementado |
| `[flavor_mis_reservas_parking]` | `shortcode_mis_reservas()` | ✅ Implementado |
| `[flavor_solicitar_plaza]` | `shortcode_solicitar_plaza()` | ✅ Usa template |
| `[flavor_parking_grid]` | `shortcode_parking_grid()` | ✅ Implementado |
| `[flavor_parking_stats]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[flavor_tarifas_parking]` | `shortcode_tarifas_parking()` | ✅ Implementado |
| `[flavor_ocupacion_tiempo_real]` | `shortcode_ocupacion_tiempo_real()` | ✅ Implementado |
| `[flavor_parkings_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[flavor_parkings_listado]` | `shortcode_listado()` | ✅ Implementado |
| `[flavor_parkings_disponibles]` | `shortcode_disponibles()` | ⚠️ Placeholder/TODO |
| `[flavor_parkings_reservar]` | `shortcode_reservar()` | ✅ Usa template |
| `[flavor_parkings_mis_reservas]` | `shortcode_mis_reservas()` | ❌ Vacío (return "") |
| `[flavor_parkings_mi_plaza]` | `shortcode_mi_plaza()` | ❌ Vacío (return "") |
| `[flavor_parkings_lista_espera]` | `shortcode_lista_espera()` | ✅ Implementado |
| `[flavor_parkings_dashboard]` | `shortcode_dashboard()` | ⚠️ Parcial (321 chars) |

### 📦 participacion

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[participacion_encuestas]` | `shortcode_encuestas()` | ✅ Implementado |
| `[participacion_encuesta]` | `shortcode_encuesta()` | ✅ Implementado |
| `[participacion_peticiones]` | `shortcode_peticiones()` | ❌ Vacío (return "") |
| `[participacion_peticion]` | `shortcode_peticion()` | ✅ Implementado |
| `[participacion_crear_peticion]` | `shortcode_crear_peticion()` | ✅ Usa template |
| `[participacion_debates]` | `shortcode_debates()` | ❌ Vacío (return "") |
| `[participacion_debate]` | `shortcode_debate()` | ✅ Usa template |
| `[participacion_resumen]` | `shortcode_resumen()` | ✅ Implementado |
| `[propuestas_activas]` | `shortcode_propuestas_activas()` | ✅ Implementado |
| `[crear_propuesta]` | `shortcode_crear_propuesta()` | ✅ Usa template |
| `[votacion_activa]` | `shortcode_votacion_activa()` | ✅ Implementado |
| `[resultados_participacion]` | `shortcode_resultados()` | ✅ Implementado |
| `[fases_participacion]` | `shortcode_fases()` | ✅ Implementado |
| `[presupuesto_participativo]` | `shortcode_presupuesto()` | ✅ Implementado |
| `[detalle_propuesta]` | `shortcode_detalle_propuesta()` | ✅ Implementado |

### 📦 podcast

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_podcast_catalogo]` | `shortcode_catalogo()` | ✅ Implementado |
| `[flavor_podcast_series]` | `shortcode_series()` | ❌ Método no encontrado |
| `[flavor_podcast_serie]` | `shortcode_serie_integrada()` | ✅ Usa template |
| `[flavor_podcast_episodio]` | `shortcode_episodio()` | ❌ Vacío (return "") |
| `[flavor_podcast_player]` | `shortcode_player()` | ❌ Vacío (return "") |
| `[flavor_podcast_mis_suscripciones]` | `shortcode_mis_suscripciones()` | ❌ Método no encontrado |
| `[flavor_podcast_crear_serie]` | `shortcode_crear_serie()` | ❌ Método no encontrado |
| `[flavor_podcast_subir_episodio]` | `shortcode_subir_episodio()` | ❌ Método no encontrado |
| `[flavor_podcast_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[podcast_player]` | `shortcode_player()` | ✅ Implementado |
| `[podcast_lista_episodios]` | `shortcode_lista_episodios()` | ✅ Implementado |
| `[podcast_series]` | `shortcode_series()` | ✅ Implementado |
| `[podcast_suscribirse]` | `shortcode_suscribirse()` | ❌ Vacío (return "") |
| `[podcast_estadisticas]` | `shortcode_estadisticas()` | ❌ Vacío (return "") |
| `[podcast_buscar]` | `shortcode_buscar()` | ✅ Implementado |
| `[podcast_ultimo_episodio]` | `shortcode_ultimo_episodio()` | ⚠️ Placeholder/TODO |

### 📦 presupuestos-participativos

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[pp_proceso_activo]` | `shortcode_proceso_activo()` | ✅ Implementado |
| `[pp_listado_procesos]` | `shortcode_listado_procesos()` | ✅ Implementado |
| `[pp_propuestas]` | `shortcode_propuestas()` | ✅ Implementado |
| `[pp_detalle_propuesta]` | `shortcode_detalle_propuesta()` | ✅ Usa template |
| `[pp_crear_propuesta]` | `shortcode_crear_propuesta()` | ✅ Usa template |
| `[pp_mis_propuestas]` | `shortcode_mis_propuestas()` | ✅ Usa template |
| `[pp_resultados]` | `shortcode_resultados()` | ✅ Implementado |
| `[pp_votacion]` | `shortcode_votacion()` | ⚠️ Placeholder/TODO |
| `[presupuestos_listado]` | `shortcode_listado_proyectos()` | ✅ Usa template |
| `[presupuestos_proponer]` | `shortcode_formulario_propuesta()` | ✅ Usa template |
| `[presupuestos_votar]` | `shortcode_interfaz_votacion()` | ✅ Usa template |
| `[presupuestos_resultados]` | `shortcode_resultados()` | ✅ Usa template |
| `[presupuestos_mi_proyecto]` | `shortcode_mis_propuestas()` | ✅ Usa template |

### 📦 radio

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_radio_player]` | `shortcode_player()` | ✅ Implementado |
| `[flavor_radio_programacion]` | `shortcode_programacion()` | ✅ Implementado |
| `[flavor_radio_programa_actual]` | `shortcode_programa_actual()` | ❌ Método no encontrado |
| `[flavor_radio_dedicatorias]` | `shortcode_dedicatorias()` | ✅ Usa template |
| `[flavor_radio_chat]` | `shortcode_chat()` | ❌ Vacío (return "") |
| `[flavor_radio_proponer_programa]` | `shortcode_proponer_programa()` | ❌ Método no encontrado |
| `[flavor_radio_podcasts]` | `shortcode_podcasts()` | ⚠️ Placeholder/TODO |
| `[flavor_radio_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[flavor_radio_proponer]` | `shortcode_proponer_programa()` | ✅ Usa template |
| `[radio_en_directo]` | `shortcode_en_directo()` | ✅ Implementado |

### 📦 recetas

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_recetas]` | `shortcode_listado_recetas()` | ✅ Implementado |
| `[flavor_receta]` | `shortcode_receta_individual()` | ❌ Vacío (return "") |
| `[flavor_recetas_buscador]` | `shortcode_buscador()` | ✅ Implementado |
| `[flavor_recetas_categorias]` | `shortcode_categorias()` | ✅ Implementado |
| `[flavor_recetas_mis_recetas]` | `shortcode_mis_recetas()` | ✅ Usa template |
| `[flavor_recetas_favoritas]` | `shortcode_favoritas()` | ✅ Usa template |
| `[flavor_recetas_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[flavor_recetas_destacadas]` | `shortcode_destacadas()` | ✅ Implementado |
| `[flavor_recetas_por_ingrediente]` | `shortcode_por_ingrediente()` | ✅ Implementado |
| `[flavor_recetas_dashboard]` | `shortcode_dashboard()` | ⚠️ Muy corto/básico |

### 📦 reciclaje

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_reciclaje_mapa]` | `shortcode_mapa()` | ✅ Implementado |
| `[flavor_reciclaje_puntos]` | `shortcode_puntos()` | ❌ Vacío (return "") |
| `[flavor_reciclaje_registrar]` | `shortcode_registrar()` | ❌ Método no encontrado |
| `[flavor_reciclaje_mis_registros]` | `shortcode_mis_registros()` | ❌ Método no encontrado |
| `[flavor_reciclaje_canjear]` | `shortcode_canjear()` | ❌ Método no encontrado |
| `[flavor_reciclaje_guia]` | `shortcode_guia()` | ✅ Implementado |
| `[flavor_reciclaje_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[flavor_reciclaje_reportar]` | `shortcode_reportar()` | ❌ Método no encontrado |
| `[reciclaje_puntos_cercanos]` | `shortcode_puntos_cercanos()` | ⚠️ Parcial (454 chars) |
| `[reciclaje_calendario]` | `shortcode_calendario()` | ✅ Implementado |
| `[reciclaje_mis_puntos]` | `shortcode_mis_puntos()` | ✅ Implementado |
| `[reciclaje_ranking]` | `shortcode_ranking()` | ✅ Implementado |
| `[reciclaje_guia]` | `shortcode_guia()` | ✅ Implementado |
| `[reciclaje_recompensas]` | `shortcode_recompensas()` | ✅ Implementado |
| `[rec_economia_circular]` | `shortcode_economia_circular()` | ✅ Encontrado |
| `[rec_mi_huella_reciclaje]` | `shortcode_mi_huella()` | ✅ Encontrado |
| `[rec_retos_activos]` | `shortcode_retos_activos()` | ✅ Encontrado |
| `[rec_red_reparadores]` | `shortcode_red_reparadores()` | ✅ Encontrado |
| `[rec_dashboard_impacto]` | `shortcode_dashboard_impacto()` | ✅ Encontrado |

### 📦 red-social

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[rs_feed]` | `shortcode_feed()` | ✅ Implementado |
| `[rs_perfil]` | `shortcode_perfil()` | ✅ Implementado |
| `[rs_explorar]` | `shortcode_explorar()` | ✅ Implementado |
| `[rs_crear_publicacion]` | `shortcode_crear_publicacion()` | ⚠️ Parcial (167 chars) |
| `[rs_notificaciones]` | `shortcode_notificaciones()` | ✅ Implementado |
| `[rs_historias]` | `shortcode_historias()` | ✅ Implementado |
| `[rs_reputacion]` | `shortcode_reputacion()` | ❌ Vacío (return "") |
| `[rs_ranking]` | `shortcode_ranking()` | ✅ Implementado |
| `[rs_badges]` | `shortcode_badges()` | ⚠️ Placeholder/TODO |
| `[rs_mi_actividad]` | `shortcode_mi_actividad()` | ✅ Usa template |
| `[flavor_social_feed]` | `shortcode_feed_integrado()` | ✅ Implementado |

### 📦 reservas

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[reservas_recursos]` | `shortcode_recursos()` | ⚠️ Parcial (333 chars) |
| `[reservas_mis_reservas]` | `shortcode_mis_reservas()` | ✅ Usa template |
| `[reservas_calendario]` | `shortcode_calendario()` | ⚠️ Parcial (276 chars) |
| `[reservas_formulario]` | `shortcode_formulario()` | ✅ Usa template |
| `[reservas_detalle_recurso]` | `shortcode_detalle_recurso()` | ⚠️ Parcial (462 chars) |

### 📦 saberes-ancestrales

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_saberes_catalogo]` | `shortcode_catalogo()` | ✅ Encontrado |
| `[flavor_saberes_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_saberes_maestros]` | `shortcode_maestros()` | ✅ Implementado |
| `[flavor_saberes_aprendizaje]` | `shortcode_aprendizaje()` | ⚠️ Parcial (112 chars) |
| `[flavor_saberes_documentar]` | `shortcode_documentar()` | ✅ Usa template |
| `[flavor_saberes_mis_aprendizajes]` | `shortcode_mis_aprendizajes()` | ✅ Usa template |
| `[flavor_saberes_mapa]` | `shortcode_mapa()` | ⚠️ Parcial (471 chars) |
| `[flavor_saberes_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[saberes_catalogo]` | `shortcode_catalogo()` | ✅ Encontrado |
| `[saberes_portadores]` | `shortcode_portadores()` | ✅ Encontrado |
| `[saberes_talleres]` | `shortcode_talleres()` | ✅ Encontrado |
| `[saberes_compartir]` | `shortcode_compartir()` | ✅ Encontrado |
| `[saberes_mis_aprendizajes]` | `shortcode_mis_aprendizajes()` | ✅ Encontrado |
| `[flavor_saberes_compartir]` | `shortcode_compartir()` | ✅ Encontrado |
| `[flavor_saberes_talleres]` | `shortcode_talleres()` | ✅ Encontrado |

### 📦 seguimiento-denuncias

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_seguimiento_denuncias_dashboard]` | `shortcode_dashboard()` | ✅ Usa template |
| `[flavor_denuncias_buscador]` | `shortcode_buscador()` | ⚠️ Placeholder/TODO |
| `[flavor_denuncias_plantillas]` | `shortcode_plantillas()` | ✅ Implementado |
| `[denuncias_listar]` | `shortcode_listar()` | ✅ Usa template |
| `[denuncias_detalle]` | `shortcode_detalle()` | ✅ Usa template |
| `[denuncias_crear]` | `shortcode_crear()` | ✅ Usa template |
| `[denuncias_mis_denuncias]` | `shortcode_mis_denuncias()` | ✅ Usa template |
| `[denuncias_timeline]` | `shortcode_timeline()` | ✅ Usa template |
| `[denuncias_estadisticas]` | `shortcode_estadisticas()` | ⚠️ Muy corto/básico |

### ✅ sello-conciencia (4 shortcodes OK)

### 📦 socios

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[socios_formulario_alta]` | `shortcode_formulario_alta()` | ✅ Usa template |
| `[socios_mi_perfil]` | `shortcode_mi_perfil()` | ✅ Usa template |
| `[socios_mi_carnet]` | `shortcode_mi_carnet()` | ✅ Usa template |
| `[socios_mis_cuotas]` | `shortcode_mis_cuotas()` | ✅ Usa template |
| `[socios_directorio]` | `shortcode_directorio()` | ❌ Vacío (return "") |
| `[socios_ventajas]` | `shortcode_ventajas()` | ⚠️ Placeholder/TODO |
| `[socios_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[socios_pagar_cuota]` | `shortcode_pagar_cuota()` | ✅ Usa template |

### 📦 talleres

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[proximos_talleres]` | `shortcode_proximos_talleres()` | ✅ Usa template |
| `[detalle_taller]` | `shortcode_detalle_taller()` | ✅ Usa template |
| `[mis_inscripciones_talleres]` | `shortcode_mis_inscripciones()` | ✅ Usa template |
| `[proponer_taller]` | `shortcode_proponer_taller()` | ✅ Usa template |
| `[calendario_talleres]` | `shortcode_calendario()` | ✅ Usa template |
| `[mis_talleres_organizador]` | `shortcode_mis_talleres_organizador()` | ✅ Usa template |
| `[talleres_catalogo]` | `shortcode_catalogo()` | ⚠️ Parcial (307 chars) |
| `[talleres_mis_inscripciones]` | `shortcode_mis_inscripciones()` | ⚠️ Placeholder/TODO |
| `[talleres_calendario]` | `shortcode_calendario()` | ⚠️ Parcial (245 chars) |
| `[talleres_proponer]` | `shortcode_proponer()` | ✅ Usa template |
| `[talleres_detalle]` | `shortcode_detalle()` | ⚠️ Parcial (455 chars) |
| `[talleres_organizador]` | `shortcode_organizador()` | ✅ Usa template |

### 📦 trabajo-digno

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_trabajo_ofertas]` | `shortcode_ofertas()` | ✅ Implementado |
| `[flavor_trabajo_oferta]` | `shortcode_oferta()` | ✅ Implementado |
| `[flavor_trabajo_publicar]` | `shortcode_publicar()` | ✅ Usa template |
| `[flavor_trabajo_mis_ofertas]` | `shortcode_mis_ofertas()` | ✅ Usa template |
| `[flavor_trabajo_mis_candidaturas]` | `shortcode_mis_candidaturas()` | ✅ Usa template |
| `[flavor_trabajo_cooperativas]` | `shortcode_cooperativas()` | ✅ Implementado |
| `[flavor_trabajo_formacion]` | `shortcode_formacion()` | ✅ Implementado |
| `[flavor_trabajo_estadisticas]` | `shortcode_estadisticas()` | ✅ Implementado |
| `[trabajo_digno_ofertas]` | `shortcode_ofertas()` | ⚠️ Muy corto/básico |
| `[trabajo_digno_formacion]` | `shortcode_formacion()` | ⚠️ Muy corto/básico |
| `[trabajo_digno_emprendimientos]` | `shortcode_emprendimientos()` | ⚠️ Muy corto/básico |
| `[trabajo_digno_mi_perfil]` | `shortcode_mi_perfil()` | ⚠️ Muy corto/básico |
| `[trabajo_digno_publicar]` | `shortcode_publicar()` | ⚠️ Muy corto/básico |

### 📦 trading-ia

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[trading_ia_dashboard]` | `shortcode_dashboard()` | ⚠️ Placeholder/TODO |
| `[trading_ia_portfolio]` | `shortcode_portfolio()` | ✅ Usa template |
| `[trading_ia_mercado]` | `shortcode_mercado()` | ✅ Implementado |
| `[trading_ia_historial]` | `shortcode_historial()` | ✅ Usa template |
| `[trading_ia_panel_control]` | `shortcode_panel_control()` | ⚠️ Placeholder/TODO |
| `[trading_ia_widget_precio]` | `shortcode_widget_precio()` | ✅ Implementado |

### 📦 tramites

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[flavor_tramites_catalogo]` | `shortcode_catalogo()` | ✅ Implementado |
| `[flavor_tramites_detalle]` | `shortcode_detalle()` | ✅ Implementado |
| `[flavor_tramites_solicitar]` | `shortcode_solicitar()` | ✅ Usa template |
| `[flavor_tramites_mis_solicitudes]` | `shortcode_mis_solicitudes()` | ✅ Usa template |
| `[flavor_tramites_seguimiento]` | `shortcode_seguimiento()` | ✅ Usa template |
| `[flavor_tramites_citas]` | `shortcode_citas()` | ✅ Usa template |
| `[flavor_tramites_documentos]` | `shortcode_documentos()` | ✅ Implementado |
| `[flavor_tramites_buscar]` | `shortcode_buscar()` | ✅ Implementado |
| `[catalogo_tramites]` | `shortcode_catalogo_tramites()` | ⚠️ Placeholder/TODO |
| `[iniciar_tramite]` | `shortcode_iniciar_tramite()` | ✅ Usa template |
| `[mis_expedientes]` | `shortcode_mis_expedientes()` | ⚠️ Placeholder/TODO |
| `[estado_expediente]` | `shortcode_estado_expediente()` | ✅ Implementado |

### 📦 transparencia

| Shortcode | Método | Estado |
|-----------|--------|--------|
| `[transparencia_portal]` | `shortcode_portal()` | ⚠️ Placeholder/TODO |
| `[transparencia_presupuesto_actual]` | `shortcode_presupuesto_actual()` | ✅ Implementado |
| `[transparencia_ultimos_gastos]` | `shortcode_ultimos_gastos()` | ✅ Implementado |
| `[transparencia_buscador_docs]` | `shortcode_buscador_docs()` | ✅ Implementado |
| `[transparencia_solicitar_info]` | `shortcode_solicitar_info()` | ✅ Usa template |
| `[transparencia_actas]` | `shortcode_actas()` | ✅ Implementado |
| `[transparencia_grafico_presupuesto]` | `shortcode_grafico_presupuesto()` | ✅ Implementado |
| `[transparencia_indicadores]` | `shortcode_indicadores()` | ✅ Implementado |
| `[flavor_transparencia_dashboard]` | `shortcode_dashboard()` | ⚠️ Parcial (80 chars) |
| `[flavor_transparencia_mis_solicitudes]` | `shortcode_mis_solicitudes()` | ✅ Usa template |

## 🚨 Requieren Acción Inmediata

- `[flavor_consentimientos]` en `includes/privacy/class-privacy-manager.php` - ❌ Vacío (return "")
- `[carpooling_proximo_viaje]` en `includes/modules/carpooling/frontend/class-carpooling-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_circulos_publicar_necesidad]` en `includes/modules/circulos-cuidados/frontend/class-circulos-cuidados-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_circulos_mis_circulos]` en `includes/modules/circulos-cuidados/frontend/class-circulos-cuidados-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_circulos_mis_horas]` en `includes/modules/circulos-cuidados/frontend/class-circulos-cuidados-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_don_mis_dones]` en `includes/modules/economia-don/frontend/class-economia-don-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_don_mis_recepciones]` en `includes/modules/economia-don/frontend/class-economia-don-frontend-controller.php` - ❌ Vacío (return "")
- `[avisos_urgentes]` en `includes/modules/avisos-municipales/class-avisos-municipales-module.php` - ❌ Vacío (return "")
- `[flavor_avisos_urgentes]` en `includes/modules/avisos-municipales/frontend/class-avisos-municipales-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_avisos_banner]` en `includes/modules/avisos-municipales/frontend/class-avisos-municipales-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_ad]` en `includes/modules/advertising/class-advertising-module.php` - ❌ Vacío (return "")
- `[chat_grupos_sin_leer]` en `includes/modules/chat-grupos/class-chat-grupos-module.php` - ❌ Vacío (return "")
- `[chat_mensajes_sin_leer]` en `includes/modules/chat-grupos/class-chat-grupos-module.php` - ❌ Vacío (return "")
- `[flavor_biodiversidad_mis_avistamientos]` en `includes/modules/biodiversidad-local/frontend/class-biodiversidad-local-frontend-controller.php` - ❌ Vacío (return "")
- `[socios_directorio]` en `includes/modules/socios/frontend/class-socios-frontend-controller.php` - ❌ Vacío (return "")
- `[bicicletas_estaciones_cercanas]` en `includes/modules/bicicletas-compartidas/class-bicicletas-compartidas-module.php` - ❌ Vacío (return "")
- `[bicicletas_prestamo_actual]` en `includes/modules/bicicletas-compartidas/class-bicicletas-compartidas-module.php` - ❌ Vacío (return "")
- `[flavor_bicicletas_reservar]` en `includes/modules/bicicletas-compartidas/frontend/class-bicicletas-compartidas-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_bicicletas_prestamo_activo]` en `includes/modules/bicicletas-compartidas/frontend/class-bicicletas-compartidas-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_estados_crear]` en `includes/modules/chat-estados/class-chat-estados-module.php` - ❌ Vacío (return "")
- `[flavor_colectivos_mis_colectivos]` en `includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_colectivos_proyectos]` en `includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_colectivos_asambleas]` en `includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_colectivos_miembros]` en `includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_iniciar_chat]` en `includes/modules/chat-interno/class-chat-interno-module.php` - ❌ Vacío (return "")
- `[flavor_foros_mis_respuestas]` en `includes/modules/foros/frontend/class-foros-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_parkings_mis_reservas]` en `includes/modules/parkings/frontend/class-parkings-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_parkings_mi_plaza]` en `includes/modules/parkings/frontend/class-parkings-frontend-controller.php` - ❌ Vacío (return "")
- `[gc_nav]` en `includes/modules/grupos-consumo/class-grupos-consumo-module.php` - ❌ Vacío (return "")
- `[participacion_peticiones]` en `includes/modules/participacion/frontend/class-participacion-frontend-controller.php` - ❌ Vacío (return "")
- `[participacion_debates]` en `includes/modules/participacion/frontend/class-participacion-frontend-controller.php` - ❌ Vacío (return "")
- `[espacios_proxima_reserva]` en `includes/modules/espacios-comunes/class-espacios-comunes-module.php` - ❌ Vacío (return "")
- `[flavor_encuesta]` en `includes/modules/encuestas/class-encuestas-module.php` - ❌ Vacío (return "")
- `[flavor_encuesta_crear]` en `includes/modules/encuestas/class-encuestas-module.php` - ❌ Vacío (return "")
- `[flavor_encuestas_contexto]` en `includes/modules/encuestas/class-encuestas-module.php` - ❌ Vacío (return "")
- `[flavor_encuesta_resultados]` en `includes/modules/encuestas/class-encuestas-module.php` - ❌ Vacío (return "")
- `[flavor_encuesta_mini]` en `includes/modules/encuestas/class-encuestas-module.php` - ❌ Vacío (return "")
- `[flavor_radio_programa_actual]` en `includes/modules/radio/frontend/class-radio-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_radio_chat]` en `includes/modules/radio/class-radio-module.php` - ❌ Vacío (return "")
- `[flavor_radio_proponer_programa]` en `includes/modules/radio/frontend/class-radio-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_receta]` en `includes/modules/recetas/class-recetas-module.php` - ❌ Vacío (return "")
- `[compostaje_cercana]` en `includes/modules/compostaje/class-compostaje-module.php` - ❌ Vacío (return "")
- `[compostaje_mi_balance]` en `includes/modules/compostaje/class-compostaje-module.php` - ❌ Vacío (return "")
- `[flavor_compostaje_mi_balance]` en `includes/modules/compostaje/frontend/class-compostaje-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_historial_pagos]` en `includes/modules/facturas/class-facturas-module.php` - ❌ Vacío (return "")
- `[biblioteca_prestamos_activos]` en `includes/modules/biblioteca/frontend/class-biblioteca-frontend-controller.php` - ❌ Vacío (return "")
- `[marketplace_detalle]` en `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php` - ❌ Método no encontrado
- `[fichaje_boton]` en `includes/modules/fichaje-empleados/frontend/class-fichaje-empleados-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_podcast_series]` en `includes/modules/podcast/frontend/class-podcast-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_podcast_episodio]` en `includes/modules/podcast/frontend/class-podcast-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_podcast_player]` en `includes/modules/podcast/frontend/class-podcast-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_podcast_mis_suscripciones]` en `includes/modules/podcast/frontend/class-podcast-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_podcast_crear_serie]` en `includes/modules/podcast/frontend/class-podcast-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_podcast_subir_episodio]` en `includes/modules/podcast/frontend/class-podcast-frontend-controller.php` - ❌ Método no encontrado
- `[podcast_suscribirse]` en `includes/modules/podcast/class-podcast-module.php` - ❌ Vacío (return "")
- `[podcast_estadisticas]` en `includes/modules/podcast/class-podcast-module.php` - ❌ Vacío (return "")
- `[flavor_reciclaje_puntos]` en `includes/modules/reciclaje/frontend/class-reciclaje-frontend-controller.php` - ❌ Vacío (return "")
- `[flavor_reciclaje_registrar]` en `includes/modules/reciclaje/frontend/class-reciclaje-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_reciclaje_mis_registros]` en `includes/modules/reciclaje/frontend/class-reciclaje-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_reciclaje_canjear]` en `includes/modules/reciclaje/frontend/class-reciclaje-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_reciclaje_reportar]` en `includes/modules/reciclaje/frontend/class-reciclaje-frontend-controller.php` - ❌ Método no encontrado
- `[flavor_email_preview]` en `includes/modules/email-marketing/class-email-marketing-module.php` - ❌ Vacío (return "")
- `[rs_reputacion]` en `includes/modules/red-social/class-red-social-module.php` - ❌ Vacío (return "")
- `[flavor_visual_builder]` en `includes/visual-builder/class-visual-builder.php` - ❌ Vacío (return "")
- `[flavor_notificaciones]` en `includes/frontend/class-notifications-widget.php` - ❌ Vacío (return "")
- `[flavor_notificaciones_badge]` en `includes/frontend/class-notifications-widget.php` - ❌ Vacío (return "")
- `[flavor_theme_customizer]` en `includes/class-theme-customizer.php` - ❌ Vacío (return "")
- `[flavor_notifications_widget]` en `includes/class-notification-center.php` - ❌ Vacío (return "")
- `[flavor_landing_visual]` en `includes/editor/class-landing-editor.php` - ❌ Vacío (return "")
- `[flavor_chat]` en `includes/core/class-chat-core.php` - ❌ Vacío (return "")
- `[flavor_module_nav]` en `includes/class-module-navigation.php` - ❌ Vacío (return "")

## ⚠️ Parciales/Placeholders

- `[flavor_module_form]` en `includes/class-module-shortcodes.php` - ⚠️ Placeholder/TODO
- `[carpooling_viajes]` en `includes/modules/carpooling/frontend/class-carpooling-frontend-controller.php` - ⚠️ Parcial (310 chars)
- `[flavor_circulos_mapa]` en `includes/modules/circulos-cuidados/frontend/class-circulos-cuidados-frontend-controller.php` - ⚠️ Parcial (327 chars)
- `[flavor_documentacion_legal_dashboard]` en `includes/modules/documentacion-legal/frontend/class-documentacion-legal-frontend-controller.php` - ⚠️ Parcial (80 chars)
- `[documentacion_legal_buscar]` en `includes/modules/documentacion-legal/class-documentacion-legal-module.php` - ⚠️ Muy corto/básico
- `[documentacion_legal_categorias]` en `includes/modules/documentacion-legal/class-documentacion-legal-module.php` - ⚠️ Muy corto/básico
- `[flavor_galeria]` en `includes/modules/multimedia/class-multimedia-module.php` - ⚠️ Placeholder/TODO
- `[flavor_mi_galeria]` en `includes/modules/multimedia/class-multimedia-module.php` - ⚠️ Placeholder/TODO
- `[flavor_multimedia_dashboard]` en `includes/modules/multimedia/frontend/class-multimedia-frontend-controller.php` - ⚠️ Muy corto/básico
- `[flavor_don_listado]` en `includes/modules/economia-don/frontend/class-economia-don-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[ayuda_vecinal_solicitudes]` en `includes/modules/ayuda-vecinal/frontend/class-ayuda-vecinal-frontend-controller.php` - ⚠️ Parcial (311 chars)
- `[ayuda_vecinal_voluntarios]` en `includes/modules/ayuda-vecinal/frontend/class-ayuda-vecinal-frontend-controller.php` - ⚠️ Parcial (240 chars)
- `[ayuda_vecinal_estadisticas]` en `includes/modules/ayuda-vecinal/frontend/class-ayuda-vecinal-frontend-controller.php` - ⚠️ Muy corto/básico
- `[flavor_avisos_dashboard]` en `includes/modules/avisos-municipales/frontend/class-avisos-municipales-frontend-controller.php` - ⚠️ Parcial (334 chars)
- `[banco_tiempo_servicios]` en `includes/modules/banco-tiempo/frontend/class-banco-tiempo-frontend-controller.php` - ⚠️ Parcial (308 chars)
- `[banco_tiempo_detalle]` en `includes/modules/banco-tiempo/frontend/class-banco-tiempo-frontend-controller.php` - ⚠️ Parcial (469 chars)
- `[banco_tiempo_ranking]` en `includes/modules/banco-tiempo/frontend/class-banco-tiempo-frontend-controller.php` - ⚠️ Parcial (205 chars)
- `[flavor_biodiversidad_catalogo]` en `includes/modules/biodiversidad-local/frontend/class-biodiversidad-local-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[biodiversidad_catalogo]` en `includes/modules/biodiversidad-local/class-biodiversidad-local-module.php` - ⚠️ Muy corto/básico
- `[biodiversidad_mapa]` en `includes/modules/biodiversidad-local/class-biodiversidad-local-module.php` - ⚠️ Muy corto/básico
- `[biodiversidad_registrar]` en `includes/modules/biodiversidad-local/class-biodiversidad-local-module.php` - ⚠️ Muy corto/básico
- `[biodiversidad_proyectos]` en `includes/modules/biodiversidad-local/class-biodiversidad-local-module.php` - ⚠️ Muy corto/básico
- `[biodiversidad_mis_avistamientos]` en `includes/modules/biodiversidad-local/class-biodiversidad-local-module.php` - ⚠️ Muy corto/básico
- `[comunidades_calendario]` en `includes/modules/comunidades/class-comunidades-module.php` - ⚠️ Placeholder/TODO
- `[comunidades_busqueda]` en `includes/modules/comunidades/class-comunidades-module.php` - ⚠️ Muy corto/básico
- `[comunidades_metricas]` en `includes/modules/comunidades/class-comunidades-module.php` - ⚠️ Muy corto/básico
- `[socios_ventajas]` en `includes/modules/socios/frontend/class-socios-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[bicicletas-compartidas_mis-prestamos]` en `includes/modules/bicicletas-compartidas/class-bicicletas-compartidas-module.php` - ⚠️ Placeholder/TODO
- `[flavor_bicicletas_disponibles]` en `includes/modules/bicicletas-compartidas/frontend/class-bicicletas-compartidas-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_colectivos_listado]` en `includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_colectivos_detalle]` en `includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[mapa_huertos]` en `includes/modules/huertos-urbanos/class-huertos-urbanos-module.php` - ⚠️ Placeholder/TODO
- `[reservas_recursos]` en `includes/modules/reservas/frontend/class-reservas-frontend-controller.php` - ⚠️ Parcial (333 chars)
- `[reservas_calendario]` en `includes/modules/reservas/frontend/class-reservas-frontend-controller.php` - ⚠️ Parcial (276 chars)
- `[reservas_detalle_recurso]` en `includes/modules/reservas/frontend/class-reservas-frontend-controller.php` - ⚠️ Parcial (462 chars)
- `[justicia_restaurativa]` en `includes/modules/justicia-restaurativa/class-justicia-restaurativa-module.php` - ⚠️ Muy corto/básico
- `[mediadores]` en `includes/modules/justicia-restaurativa/class-justicia-restaurativa-module.php` - ⚠️ Muy corto/básico
- `[flavor_justicia_info]` en `includes/modules/justicia-restaurativa/class-justicia-restaurativa-module.php` - ⚠️ Muy corto/básico
- `[flavor_justicia_inicio]` en `includes/modules/justicia-restaurativa/frontend/class-justicia-restaurativa-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_foros_integrado]` en `includes/modules/foros/class-foros-module.php` - ⚠️ Placeholder/TODO
- `[mi_modulo_listado]` en `includes/modules/PLANTILLA_MODULO.php` - ⚠️ Parcial (362 chars)
- `[flavor_parkings_disponibles]` en `includes/modules/parkings/frontend/class-parkings-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_parkings_dashboard]` en `includes/modules/parkings/frontend/class-parkings-frontend-controller.php` - ⚠️ Parcial (321 chars)
- `[trading_ia_dashboard]` en `includes/modules/trading-ia/class-trading-ia-module.php` - ⚠️ Placeholder/TODO
- `[trading_ia_panel_control]` en `includes/modules/trading-ia/class-trading-ia-module.php` - ⚠️ Placeholder/TODO
- `[flavor_mapa_actores_directorio]` en `includes/modules/mapa-actores/frontend/class-mapa-actores-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_mapa_actores_dashboard]` en `includes/modules/mapa-actores/frontend/class-mapa-actores-frontend-controller.php` - ⚠️ Muy corto/básico
- `[actores_mapa]` en `includes/modules/mapa-actores/class-mapa-actores-module.php` - ⚠️ Muy corto/básico
- `[actores_grafo]` en `includes/modules/mapa-actores/class-mapa-actores-module.php` - ⚠️ Muy corto/básico
- `[actores_buscar]` en `includes/modules/mapa-actores/class-mapa-actores-module.php` - ⚠️ Muy corto/básico
- `[talleres_catalogo]` en `includes/modules/talleres/frontend/class-talleres-frontend-controller.php` - ⚠️ Parcial (307 chars)
- `[talleres_mis_inscripciones]` en `includes/modules/talleres/frontend/class-talleres-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[talleres_calendario]` en `includes/modules/talleres/frontend/class-talleres-frontend-controller.php` - ⚠️ Parcial (245 chars)
- `[talleres_detalle]` en `includes/modules/talleres/frontend/class-talleres-frontend-controller.php` - ⚠️ Parcial (455 chars)
- `[campanias_mapa]` en `includes/modules/campanias/class-campanias-module.php` - ⚠️ Muy corto/básico
- `[campanias_calendario]` en `includes/modules/campanias/class-campanias-module.php` - ⚠️ Muy corto/básico
- `[catalogo_tramites]` en `includes/modules/tramites/class-tramites-module.php` - ⚠️ Placeholder/TODO
- `[mis_expedientes]` en `includes/modules/tramites/class-tramites-module.php` - ⚠️ Placeholder/TODO
- `[gc_catalogo]` en `includes/modules/grupos-consumo/class-grupos-consumo-module.php` - ⚠️ Placeholder/TODO
- `[gc_calendario]` en `includes/modules/grupos-consumo/class-grupos-consumo-module.php` - ⚠️ Placeholder/TODO
- `[gc_mi_pedido]` en `includes/modules/grupos-consumo/class-grupos-consumo-module.php` - ⚠️ Parcial (333 chars)
- `[gc_productores_cercanos]` en `includes/modules/grupos-consumo/class-grupos-consumo-module.php` - ⚠️ Placeholder/TODO
- `[gc_productores]` en `includes/modules/grupos-consumo/class-grupos-consumo-module.php` - ⚠️ Placeholder/TODO
- `[pp_votacion]` en `includes/modules/presupuestos-participativos/frontend/class-presupuestos-participativos-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_radio_podcasts]` en `includes/modules/radio/class-radio-module.php` - ⚠️ Placeholder/TODO
- `[flavor_red_recetas]` en `includes/modules/class-network-content-bridge.php` - ⚠️ Parcial (92 chars)
- `[flavor_red_eventos]` en `includes/modules/class-network-content-bridge.php` - ⚠️ Parcial (92 chars)
- `[flavor_recetas_dashboard]` en `includes/modules/recetas/frontend/class-recetas-frontend-controller.php` - ⚠️ Muy corto/básico
- `[cursos_mis_inscripciones]` en `includes/modules/cursos/frontend/class-cursos-frontend-controller.php` - ⚠️ Parcial (315 chars)
- `[cursos_calendario]` en `includes/modules/cursos/frontend/class-cursos-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[transparencia_portal]` en `includes/modules/transparencia/class-transparencia-module.php` - ⚠️ Placeholder/TODO
- `[flavor_transparencia_dashboard]` en `includes/modules/transparencia/frontend/class-transparencia-frontend-controller.php` - ⚠️ Parcial (80 chars)
- `[mapa_composteras]` en `includes/modules/compostaje/class-compostaje-module.php` - ⚠️ Placeholder/TODO
- `[turnos_compostaje]` en `includes/modules/compostaje/class-compostaje-module.php` - ⚠️ Placeholder/TODO
- `[flavor_compostaje_turnos]` en `includes/modules/compostaje/frontend/class-compostaje-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_compostaje_dashboard]` en `includes/modules/compostaje/frontend/class-compostaje-frontend-controller.php` - ⚠️ Parcial (334 chars)
- `[flavor_mis_facturas]` en `includes/modules/facturas/class-facturas-module.php` - ⚠️ Placeholder/TODO
- `[flavor_detalle_factura]` en `includes/modules/facturas/class-facturas-module.php` - ⚠️ Placeholder/TODO
- `[flavor_pagar_factura]` en `includes/modules/facturas/class-facturas-module.php` - ⚠️ Parcial (125 chars)
- `[flavor_nueva_factura]` en `includes/modules/facturas/class-facturas-module.php` - ⚠️ Placeholder/TODO
- `[flavor_denuncias_buscador]` en `includes/modules/seguimiento-denuncias/frontend/class-seguimiento-denuncias-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[denuncias_estadisticas]` en `includes/modules/seguimiento-denuncias/class-seguimiento-denuncias-module.php` - ⚠️ Muy corto/básico
- `[biblioteca_reservas]` en `includes/modules/biblioteca/frontend/class-biblioteca-frontend-controller.php` - ⚠️ Parcial (305 chars)
- `[eventos_listado]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Parcial (332 chars)
- `[eventos_calendario]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Parcial (275 chars)
- `[eventos_detalle]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Parcial (448 chars)
- `[eventos_proximos]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Parcial (234 chars)
- `[eventos_destacados]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Parcial (207 chars)
- `[flavor_eventos_acciones]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[eventos_proximo]` en `includes/modules/eventos/frontend/class-eventos-frontend-controller.php` - ⚠️ Parcial (137 chars)
- `[marketplace_catalogo]` en `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php` - ⚠️ Parcial (376 chars)
- `[marketplace_listado]` en `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php` - ⚠️ Parcial (376 chars)
- `[marketplace_mis_anuncios]` en `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[marketplace_favoritos]` en `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php` - ⚠️ Parcial (308 chars)
- `[marketplace_busqueda]` en `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php` - ⚠️ Placeholder/TODO
- `[flavor_saberes_aprendizaje]` en `includes/modules/saberes-ancestrales/frontend/class-saberes-ancestrales-frontend-controller.php` - ⚠️ Parcial (112 chars)
- `[flavor_saberes_mapa]` en `includes/modules/saberes-ancestrales/frontend/class-saberes-ancestrales-frontend-controller.php` - ⚠️ Parcial (471 chars)
- `[podcast_ultimo_episodio]` en `includes/modules/podcast/class-podcast-module.php` - ⚠️ Placeholder/TODO
- `[reciclaje_puntos_cercanos]` en `includes/modules/reciclaje/class-reciclaje-module.php` - ⚠️ Parcial (454 chars)
- `[trabajo_digno_ofertas]` en `includes/modules/trabajo-digno/class-trabajo-digno-module.php` - ⚠️ Muy corto/básico
- `[trabajo_digno_formacion]` en `includes/modules/trabajo-digno/class-trabajo-digno-module.php` - ⚠️ Muy corto/básico
- `[trabajo_digno_emprendimientos]` en `includes/modules/trabajo-digno/class-trabajo-digno-module.php` - ⚠️ Muy corto/básico
- `[trabajo_digno_mi_perfil]` en `includes/modules/trabajo-digno/class-trabajo-digno-module.php` - ⚠️ Muy corto/básico
- `[trabajo_digno_publicar]` en `includes/modules/trabajo-digno/class-trabajo-digno-module.php` - ⚠️ Muy corto/básico
- `[flavor_suscripcion_newsletter]` en `includes/modules/email-marketing/class-email-marketing-module.php` - ⚠️ Parcial (64 chars)
- `[flavor_preferencias_email]` en `includes/modules/email-marketing/class-email-marketing-module.php` - ⚠️ Parcial (54 chars)
- `[rs_crear_publicacion]` en `includes/modules/red-social/class-red-social-module.php` - ⚠️ Parcial (167 chars)
- `[rs_badges]` en `includes/modules/red-social/class-red-social-module.php` - ⚠️ Placeholder/TODO
- `[flavor_dashboard_unificado]` en `includes/visual-builder/class-dashboard-vb-widgets.php` - ⚠️ Placeholder/TODO
- `[flavor_app]` en `includes/frontend/class-dynamic-pages.php` - ⚠️ Parcial (479 chars)
- `[flavor_mi_cuenta]` en `includes/frontend/class-user-dashboard.php` - ⚠️ Placeholder/TODO
- `[flavor_user_dashboard]` en `includes/frontend/class-user-dashboard.php` - ⚠️ Placeholder/TODO
- `[flavor_crud_form]` en `includes/frontend/class-dynamic-crud.php` - ⚠️ Parcial (178 chars)
- `[flavor_crud_list]` en `includes/frontend/class-dynamic-crud.php` - ⚠️ Parcial (202 chars)
- `[flavor_mis_registros]` en `includes/frontend/class-dynamic-crud.php` - ⚠️ Parcial (157 chars)
- `[flavor_menu]` en `includes/layouts/class-layout-renderer.php` - ⚠️ Parcial (489 chars)
- `[flavor_footer]` en `includes/layouts/class-layout-renderer.php` - ⚠️ Parcial (493 chars)
- `[flavor_grupos_consumo]` en `includes/class-landing-shortcodes.php` - ⚠️ Parcial (238 chars)
- `[flavor_banco_tiempo]` en `includes/class-landing-shortcodes.php` - ⚠️ Parcial (234 chars)
- `[flavor_servicios]` en `includes/class-portal-shortcodes.php` - ⚠️ Placeholder/TODO
- `[flavor_mi_portal]` en `includes/class-portal-shortcodes.php` - ⚠️ Placeholder/TODO

---
Fin del informe.