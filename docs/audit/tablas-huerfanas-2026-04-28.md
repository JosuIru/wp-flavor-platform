# Auditoría — Tablas huérfanas (no referenciadas en código)

**Fecha**: 2026-04-28
**Total tablas físicas `wp_flavor_*`**: 509
**Tablas huérfanas detectadas**: 62
- Con datos: 2
- Vacías: 60

## Metodología

1. Listar tablas físicas: `SHOW TABLES LIKE 'wp_flavor_%'`.
2. Extraer del código: `grep -rhoE "\$wpdb->prefix . ['"]flavor_[a-zA-Z0-9_]+['"]"` en `includes/` y `addons/`.
3. Diferencia: tablas físicas que no aparecen en ningún string del código.

**Limitación**: tablas referenciadas con concatenación dinámica (ej. `'flavor_avisos_' . $tipo`) pueden aparecer aquí como falsos positivos. Verificar antes de borrar.

## Huérfanas con datos (revisión obligatoria antes de cualquier acción)

| Tabla | Filas | Posible owner |
|-------|-------|---------------|
| `wp_flavor_em_logs` | 37 | email-marketing (concatenación dinámica) |
| `wp_flavor_network_messages` | 3 | flavor-network-communities (mesh) |

## Huérfanas vacías (60 tablas, candidatos seguros para limpieza futura)

Estas tablas existen físicamente pero ningún archivo PHP las referencia y están vacías. Podrían ser:

- Restos de módulos eliminados (chat-estados subtablas, e2e encryption, ec_*, etc.).
- Refactorizaciones donde se renombró la tabla pero no se eliminó la antigua.
- Schema de features incompletas (eventos voluntarios, eventos inclusividad).

Listado completo:

- `wp_flavor_app_releases`
- `wp_flavor_biblioteca_favoritos`
- `wp_flavor_biblioteca_valoraciones`
- `wp_flavor_chat_api_usage`
- `wp_flavor_chat_estados_reacciones`
- `wp_flavor_chat_estados_respuestas`
- `wp_flavor_chat_estados_silenciados`
- `wp_flavor_cursos_modulos`
- `wp_flavor_e2e_identity_keys`
- `wp_flavor_e2e_one_time_prekeys`
- `wp_flavor_e2e_sender_key_distribution`
- `wp_flavor_e2e_sender_keys`
- `wp_flavor_e2e_sessions`
- `wp_flavor_e2e_signed_prekeys`
- `wp_flavor_ec_cesiones`
- `wp_flavor_ec_consumos`
- `wp_flavor_ec_lista_espera`
- `wp_flavor_ec_metricas`
- `wp_flavor_ec_participaciones`
- `wp_flavor_ec_voluntariado`
- `wp_flavor_em_rebotes`
- `wp_flavor_espacios_bloqueos`
- `wp_flavor_eventos_colaboraciones`
- `wp_flavor_eventos_huella_carbono`
- `wp_flavor_eventos_impacto_social`
- `wp_flavor_eventos_inclusividad`
- `wp_flavor_eventos_plazas_solidarias`
- `wp_flavor_eventos_voluntarios`
- `wp_flavor_eventos_voluntarios_necesidades`
- `wp_flavor_expedientes_documentos`
- `wp_flavor_expedientes_historial`
- `wp_flavor_huertos_pagos`
- `wp_flavor_moderation_report_counts`
- `wp_flavor_moderation_reports`
- `wp_flavor_moderation_warnings`
- `wp_flavor_module_integrations`
- `wp_flavor_network_answers`
- `wp_flavor_network_collaboration_participants`
- `wp_flavor_network_collaborations`
- `wp_flavor_network_favorites`
- `wp_flavor_network_matches`
- `wp_flavor_network_newsletter_subscribers`
- `wp_flavor_network_newsletters`
- `wp_flavor_network_quality_seals`
- `wp_flavor_network_questions`
- `wp_flavor_network_recommendations`
- `wp_flavor_network_time_offers`
- `wp_flavor_parkings_alquileres`
- `wp_flavor_parkings_disponibilidad`
- `wp_flavor_participacion_respuestas`
- `wp_flavor_podcast_descargas`
- `wp_flavor_pp_actualizaciones`
- `wp_flavor_privacy_consents`
- `wp_flavor_privacy_requests`
- `wp_flavor_social_likes`
- `wp_flavor_social_seguidores`
- `wp_flavor_transparencia_actas`
- `wp_flavor_transparencia_gastos`
- `wp_flavor_transparencia_presupuestos`
- `wp_flavor_transparencia_solicitudes_info`

## Acción recomendada

**No se eliminan automáticamente**. Antes de un eventual `DROP TABLE`:

1. Hacer backup (`wp db export /tmp/pre-cleanup.sql`).
2. Para cada tabla con datos: confirmar con el owner del módulo si los datos son históricos importantes o si se pueden archivar.
3. Para vacías: verificar que ningún feature en desarrollo activo las espera (revisar branches recientes en git, no solo `master`).
4. Ejecutar drop en lote agrupado por dominio (e2e, ec_*, eventos_*, etc.) en commits separados.
