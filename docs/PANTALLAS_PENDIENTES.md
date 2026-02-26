# Pantallas pendientes por módulo (auditoría 2026-02-16)

> **Nota:** Este documento se refiere a las **pantallas móviles Flutter** (`mobile-apps/`), NO a los módulos PHP del plugin.
> Para el estado de los módulos PHP del backend, consulta **[ESTADO-REAL-MODULOS.md](ESTADO-REAL-MODULOS.md)**.

Resumen extraído de `reports/matriz_pantallas_faltantes_audit_2026-02-16.csv`. Para cada pantalla se incluye el módulo, el tipo de riesgo/pendiente y los endpoints asociados para que el backlog pueda priorizar.

## features/admin/admin_chat_screen.dart
- Pantalla: `features/admin/admin_chat_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/admin_reservations_screen.dart
- Pantalla: `features/admin/admin_reservations_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/calendar_view_screen.dart
- Pantalla: `features/admin/calendar_view_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## camps
- Pantalla: `features/admin/camps/camp_form_screen.dart` (area=admin)
  - CRUD heurístico: CRUD
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok
- Pantalla: `features/admin/camps/camp_inscriptions_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo
- Pantalla: `features/admin/camps/camps_management_screen.dart` (area=admin)
  - CRUD heurístico: CRUD
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok
- Pantalla: `features/client/camps/camp_detail_screen.dart` (area=client)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: N/A
  - Notas audit: placeholder_o_en_desarrollo|sin_crud_evidente
- Pantalla: `features/client/camps/camp_inscription_form_screen.dart` (area=client)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente
- Pantalla: `features/client/camps/camps_screen.dart` (area=client)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## features/admin/dashboard_screen.dart
- Pantalla: `features/admin/dashboard_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/escalated_chat_detail_screen.dart
- Pantalla: `features/admin/escalated_chat_detail_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/escalated_chats_screen.dart
- Pantalla: `features/admin/escalated_chats_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/export_screen.dart
- Pantalla: `features/admin/export_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/manual_customers_screen.dart
- Pantalla: `features/admin/manual_customers_screen.dart` (area=admin)
  - CRUD heurístico: CRUD
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/admin/module_placeholder_screen.dart
- Pantalla: `features/admin/module_placeholder_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: N/A
  - Notas audit: placeholder_o_en_desarrollo|sin_indicios_api_directa|admin_sin_crud_completo

## features/admin/qr_scanner_screen.dart
- Pantalla: `features/admin/qr_scanner_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/settings/language_screen.dart
- Pantalla: `features/admin/settings/language_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/settings/notifications_screen.dart
- Pantalla: `features/admin/settings/notifications_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: admin_sin_crud_completo

## features/admin/settings/server_config_screen.dart
- Pantalla: `features/admin/settings/server_config_screen.dart` (area=admin)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: /wp-json
  - Notas audit: admin_sin_crud_completo

## features/admin/settings/support_screen.dart
- Pantalla: `features/admin/settings/support_screen.dart` (area=admin)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: sin_indicios_api_directa|admin_sin_crud_completo

## features/chat/chat_screen.dart
- Pantalla: `features/chat/chat_screen.dart` (area=chat)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/client/client_dashboard_screen.dart
- Pantalla: `features/client/client_dashboard_screen.dart` (area=client)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /client/activity?limit=10|/client/notifications?limit=5|/client/profile|/client/statistics
  - Notas audit: ok

## features/dynamic/screens/dynamic_detail_screen.dart
- Pantalla: `features/dynamic/screens/dynamic_detail_screen.dart` (area=dynamic)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: pantalla_generica_dinamica

## features/dynamic/screens/dynamic_form_screen.dart
- Pantalla: `features/dynamic/screens/dynamic_form_screen.dart` (area=dynamic)
  - CRUD heurístico: CRUD
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: N/A
  - Notas audit: placeholder_o_en_desarrollo|pantalla_generica_dinamica

## features/dynamic/screens/dynamic_list_screen.dart
- Pantalla: `features/dynamic/screens/dynamic_list_screen.dart` (area=dynamic)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: pantalla_generica_dinamica

## features/info/contact_webview_screen.dart
- Pantalla: `features/info/contact_webview_screen.dart` (area=info)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: MEDIO
  - Endpoints de muestra: N/A
  - Notas audit: sin_indicios_api_directa

## features/info/directory_screen.dart
- Pantalla: `features/info/directory_screen.dart` (area=info)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/info/info_screen.dart
- Pantalla: `features/info/info_screen.dart` (area=info)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: N/A
  - Notas audit: placeholder_o_en_desarrollo

## advertising_screen.dart
- Pantalla: `features/modules/advertising/advertising_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /advertising
  - Notas audit: sin_crud_evidente

## avisos_municipales_screen.dart
- Pantalla: `features/modules/avisos_municipales/avisos_municipales_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## ayuda_vecinal_screen.dart
- Pantalla: `features/modules/ayuda_vecinal/ayuda_vecinal_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## banco_tiempo_form_screen.dart
- Pantalla: `features/modules/banco_tiempo/banco_tiempo_form_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /banco-tiempo/servicio|/banco-tiempo/servicio/${widget.servicioId}
  - Notas audit: ok

## bares_screen.dart
- Pantalla: `features/modules/bares/bares_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## biblioteca_screen.dart
- Pantalla: `features/modules/biblioteca/biblioteca_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## bicicletas_compartidas_screen.dart
- Pantalla: `features/modules/bicicletas_compartidas/bicicletas_compartidas_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## biodiversidad_local_screen.dart
- Pantalla: `features/modules/biodiversidad_local/biodiversidad_local_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /biodiversidad/especies
  - Notas audit: placeholder_o_en_desarrollo

## carpooling_screen.dart
- Pantalla: `features/modules/carpooling/carpooling_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /carpooling/viajes
  - Notas audit: ok

## circulos_cuidados_screen.dart
- Pantalla: `features/modules/circulos_cuidados/circulos_cuidados_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /circulos-cuidados/lista
  - Notas audit: placeholder_o_en_desarrollo

## clientes_screen.dart
- Pantalla: `features/modules/clientes/clientes_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /clientes
  - Notas audit: ok

## colectivos_screen.dart
- Pantalla: `features/modules/colectivos/colectivos_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /colectivos
  - Notas audit: ok

## compostaje_screen.dart
- Pantalla: `features/modules/compostaje/compostaje_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /compostaje/dashboard
  - Notas audit: ok

## comunidades_screen.dart
- Pantalla: `features/modules/comunidades/comunidades_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /comunidades|/comunidades/${widget.comunidadId}
  - Notas audit: sin_crud_evidente

## cursos_screen.dart
- Pantalla: `features/modules/cursos/cursos_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## dex_solana_screen.dart
- Pantalla: `features/modules/dex_solana/dex_solana_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /dex-solana/dashboard|/dex-solana/token/${widget.tokenId}
  - Notas audit: sin_crud_evidente

## economia_don_screen.dart
- Pantalla: `features/modules/economia_don/economia_don_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /economia-don/tablero
  - Notas audit: placeholder_o_en_desarrollo

## economia_suficiencia_screen.dart
- Pantalla: `features/modules/economia_suficiencia/economia_suficiencia_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /economia-suficiencia/dashboard
  - Notas audit: sin_crud_evidente

## email_marketing_screen.dart
- Pantalla: `features/modules/email_marketing/email_marketing_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /email-marketing/campanas|/email-marketing/campanas/${widget.campanaId}
  - Notas audit: ok

## empresarial_screen.dart
- Pantalla: `features/modules/empresarial/empresarial_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /empresarial|/empresarial/componente/${widget.componenteId}
  - Notas audit: sin_crud_evidente

## espacios_comunes_screen.dart
- Pantalla: `features/modules/espacios_comunes/espacios_comunes_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## eventos_detail_screen.dart
- Pantalla: `features/modules/eventos/eventos_detail_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /eventos/${widget.eventoId}|/eventos/${widget.eventoId}/inscribir
  - Notas audit: ok

## fichaje_empleados_screen.dart
- Pantalla: `features/modules/fichaje_empleados/fichaje_empleados_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## foros_screen.dart
- Pantalla: `features/modules/foros/foros_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /foros|/foros/${widget.foroId}|/foros/${widget.foroId}/respuestas
  - Notas audit: ok

## huella_ecologica_screen.dart
- Pantalla: `features/modules/huella_ecologica/huella_ecologica_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /huella-ecologica/mi-huella
  - Notas audit: placeholder_o_en_desarrollo|sin_crud_evidente

## huertos_urbanos_screen.dart
- Pantalla: `features/modules/huertos_urbanos/huertos_urbanos_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## incidencias_screen.dart
- Pantalla: `features/modules/incidencias/incidencias_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## justicia_restaurativa_screen.dart
- Pantalla: `features/modules/justicia_restaurativa/justicia_restaurativa_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /justicia-restaurativa/dashboard
  - Notas audit: placeholder_o_en_desarrollo|sin_crud_evidente

## marketplace_form_screen.dart
- Pantalla: `features/modules/marketplace/marketplace_form_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /marketplace/anuncio|/marketplace/anuncio/${widget.anuncioId}
  - Notas audit: ok

## features/modules/module_client_dashboard_screen.dart
- Pantalla: `features/modules/module_client_dashboard_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/modules/module_hub_screen.dart
- Pantalla: `features/modules/module_hub_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/modules/module_placeholder_screen.dart
- Pantalla: `features/modules/module_placeholder_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: N/A
  - Notas audit: placeholder_o_en_desarrollo|sin_indicios_api_directa|sin_crud_evidente

## multimedia_screen.dart
- Pantalla: `features/modules/multimedia/multimedia_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /multimedia
  - Notas audit: sin_crud_evidente

## parkings_screen.dart
- Pantalla: `features/modules/parkings/parkings_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## participacion_screen.dart
- Pantalla: `features/modules/participacion/participacion_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /participacion/procesos
  - Notas audit: sin_crud_evidente

## podcast_screen.dart
- Pantalla: `features/modules/podcast/podcast_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /podcast/episodios
  - Notas audit: sin_crud_evidente

## presupuestos_participativos_screen.dart
- Pantalla: `features/modules/presupuestos_participativos/presupuestos_participativos_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /presupuestos/propuestas
  - Notas audit: ok

## radio_screen.dart
- Pantalla: `features/modules/radio/radio_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /radio/stream
  - Notas audit: sin_crud_evidente

## reciclaje_screen.dart
- Pantalla: `features/modules/reciclaje/reciclaje_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## red_social_screen.dart
- Pantalla: `features/modules/red_social/red_social_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /red-social/feed
  - Notas audit: ok

## reservas_screen.dart
- Pantalla: `features/modules/reservas/reservas_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /reservas
  - Notas audit: ok

## saberes_ancestrales_screen.dart
- Pantalla: `features/modules/saberes_ancestrales/saberes_ancestrales_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /saberes-ancestrales/catalogo
  - Notas audit: placeholder_o_en_desarrollo

## sello_conciencia_screen.dart
- Pantalla: `features/modules/sello_conciencia/sello_conciencia_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /sello-conciencia/dashboard
  - Notas audit: placeholder_o_en_desarrollo|sin_crud_evidente

## talleres_screen.dart
- Pantalla: `features/modules/talleres/talleres_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## themacle_screen.dart
- Pantalla: `features/modules/themacle/themacle_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /themacle
  - Notas audit: sin_crud_evidente

## trabajo_digno_screen.dart
- Pantalla: `features/modules/trabajo_digno/trabajo_digno_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /trabajo-digno/ofertas
  - Notas audit: placeholder_o_en_desarrollo

## trading_ia_screen.dart
- Pantalla: `features/modules/trading_ia/trading_ia_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /trading-ia/dashboard
  - Notas audit: ok

## tramites_screen.dart
- Pantalla: `features/modules/tramites/tramites_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## transparencia_screen.dart
- Pantalla: `features/modules/transparencia/transparencia_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: /transparencia
  - Notas audit: sin_crud_evidente

## woocommerce_admin_screen.dart
- Pantalla: `features/modules/woocommerce/woocommerce_admin_screen.dart` (area=modules)
  - CRUD heurístico: LECTURA/NA
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: sin_crud_evidente

## woocommerce_screen.dart
- Pantalla: `features/modules/woocommerce/woocommerce_screen.dart` (area=modules)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: ALTO
  - Marcada como placeholder/sin implementación completa
  - Endpoints de muestra: /woocommerce/carrito|/woocommerce/carrito/add|/woocommerce/carrito/remove|/woocommerce/pedidos|/woocommerce/productos
  - Notas audit: placeholder_o_en_desarrollo

## features/reservations/my_reservations_screen.dart
- Pantalla: `features/reservations/my_reservations_screen.dart` (area=reservations)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/reservations/reservations_screen.dart
- Pantalla: `features/reservations/reservations_screen.dart` (area=reservations)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

## features/setup/setup_screen.dart
- Pantalla: `features/setup/setup_screen.dart` (area=setup)
  - CRUD heurístico: PARCIAL
  - Riesgo/reportado: BAJO
  - Endpoints de muestra: N/A
  - Notas audit: ok

