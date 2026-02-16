# Auditoría actualizada de pantallas faltantes
Fecha: 2026-02-16
Alcance: `mobile-apps/lib/features/**/*_screen.dart` no cubiertas explícitamente en la auditoría previa
Modo: solo reporte (sin correcciones)

## Resultado global
- Pantallas actuales detectadas: **93**
- Pantallas cubiertas explícitamente en auditoría previa: **11**
- Pantallas re-auditadas en esta pasada: **82**
- Riesgo ALTO: **14**
- Riesgo MEDIO: **14**
- Riesgo BAJO: **54**

## Cobertura por área
- admin: **17**
- chat: **1**
- client: **4**
- dynamic: **3**
- info: **3**
- modules: **51**
- reservations: **2**
- setup: **1**

## Hallazgos críticos (ALTO)
- `features/admin/module_placeholder_screen.dart` | CRUD=LECTURA/NA | notas=placeholder_o_en_desarrollo|sin_indicios_api_directa|admin_sin_crud_completo
- `features/client/camps/camp_detail_screen.dart` | CRUD=LECTURA/NA | notas=placeholder_o_en_desarrollo|sin_crud_evidente
- `features/dynamic/screens/dynamic_form_screen.dart` | CRUD=CRUD | notas=placeholder_o_en_desarrollo|pantalla_generica_dinamica
- `features/info/info_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo
- `features/modules/biodiversidad_local/biodiversidad_local_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo
- `features/modules/circulos_cuidados/circulos_cuidados_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo
- `features/modules/economia_don/economia_don_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo
- `features/modules/huella_ecologica/huella_ecologica_screen.dart` | CRUD=LECTURA/NA | notas=placeholder_o_en_desarrollo|sin_crud_evidente
- `features/modules/justicia_restaurativa/justicia_restaurativa_screen.dart` | CRUD=LECTURA/NA | notas=placeholder_o_en_desarrollo|sin_crud_evidente
- `features/modules/module_placeholder_screen.dart` | CRUD=LECTURA/NA | notas=placeholder_o_en_desarrollo|sin_indicios_api_directa|sin_crud_evidente
- `features/modules/saberes_ancestrales/saberes_ancestrales_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo
- `features/modules/sello_conciencia/sello_conciencia_screen.dart` | CRUD=LECTURA/NA | notas=placeholder_o_en_desarrollo|sin_crud_evidente
- `features/modules/trabajo_digno/trabajo_digno_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo
- `features/modules/woocommerce/woocommerce_screen.dart` | CRUD=PARCIAL | notas=placeholder_o_en_desarrollo

## Hallazgos medios (MEDIO)
- `features/admin/admin_chat_screen.dart` | CRUD=LECTURA/NA | notas=admin_sin_crud_completo
- `features/admin/admin_reservations_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/calendar_view_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/camps/camp_inscriptions_screen.dart` | CRUD=LECTURA/NA | notas=admin_sin_crud_completo
- `features/admin/dashboard_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/escalated_chat_detail_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/escalated_chats_screen.dart` | CRUD=LECTURA/NA | notas=admin_sin_crud_completo
- `features/admin/export_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/qr_scanner_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/settings/language_screen.dart` | CRUD=LECTURA/NA | notas=admin_sin_crud_completo
- `features/admin/settings/notifications_screen.dart` | CRUD=LECTURA/NA | notas=admin_sin_crud_completo
- `features/admin/settings/server_config_screen.dart` | CRUD=PARCIAL | notas=admin_sin_crud_completo
- `features/admin/settings/support_screen.dart` | CRUD=LECTURA/NA | notas=sin_indicios_api_directa|admin_sin_crud_completo
- `features/info/contact_webview_screen.dart` | CRUD=LECTURA/NA | notas=sin_indicios_api_directa

## Pantallas más grandes (prioridad de revisión manual)
- `features/admin/admin_reservations_screen.dart` (2141 líneas) | riesgo=MEDIO
- `features/info/info_screen.dart` (1643 líneas) | riesgo=ALTO
- `features/client/client_dashboard_screen.dart` (1439 líneas) | riesgo=BAJO
- `features/reservations/my_reservations_screen.dart` (1230 líneas) | riesgo=BAJO
- `features/admin/manual_customers_screen.dart` (1093 líneas) | riesgo=BAJO
- `features/modules/tramites/tramites_screen.dart` (1004 líneas) | riesgo=BAJO
- `features/modules/talleres/talleres_screen.dart` (911 líneas) | riesgo=BAJO
- `features/modules/biblioteca/biblioteca_screen.dart` (857 líneas) | riesgo=BAJO
- `features/admin/export_screen.dart` (855 líneas) | riesgo=MEDIO
- `features/modules/incidencias/incidencias_screen.dart` (813 líneas) | riesgo=BAJO
- `features/modules/espacios_comunes/espacios_comunes_screen.dart` (801 líneas) | riesgo=BAJO
- `features/client/camps/camps_screen.dart` (794 líneas) | riesgo=BAJO

## Archivos de evidencia
- Matriz detallada: `reports/matriz_pantallas_faltantes_audit_2026-02-16.csv`
- Informe: `reports/informe_pantallas_faltantes_audit_2026-02-16.md`
