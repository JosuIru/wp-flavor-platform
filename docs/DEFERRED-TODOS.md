# TODOs Diferidos - Flavor Platform v3.5.0

Estos TODOs representan mejoras opcionales que no bloquean el uso en producción.
Se documentan aquí para seguimiento futuro.

## Módulo Radio (4 items)
Archivo: `includes/modules/radio/views/media-manager.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 417 | Cargar programación desde API | Baja |
| 543 | Modal para seleccionar playlist | Baja |
| 554 | Preview de playlist | Baja |
| 562 | Modal para configurar slot | Baja |

**Estado**: El módulo radio funciona sin estas features. Son mejoras de UX.

## Visual Builder (2 items)
Archivo: `includes/visual-builder/class-visual-builder.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 949 | Implementar sistema de historial | Media |
| 958 | Implementar sistema de historial | Media |

**Estado**: VBP funciona correctamente. El historial de versiones es un nice-to-have.

## VBP Editor (1 item)
Archivo: `includes/visual-builder-pro/class-vbp-editor.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 542 | Evaluar alternativa local o lazy-load bajo demanda | Baja |

**Estado**: Optimización de rendimiento, no afecta funcionalidad.

## CLI Migration (2 items)
Archivo: `includes/cli/class-migration-command.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 462 | Implementar migration | Media |
| 469 | Revertir migration | Media |

**Estado**: Comandos WP-CLI para migraciones. No crítico para usuarios normales.

## Mobile API (1 item)
Archivo: `includes/api/class-mobile-api-extensions.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 648 | Añadir conteo de otros módulos si están activos | Baja |

**Estado**: Mejora de estadísticas en API móvil.

## Portal Shortcodes (2 items)
Archivo: `includes/class-portal-shortcodes.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 4490 | Integrar con sistema de notificaciones si existe | Baja |
| 4608 | Calcular actividad real de los módulos | Baja |

**Estado**: Integraciones opcionales.

## Addon Multilingual (1 item)
Archivo: `addons/flavor-multilingual/includes/class-translation-memory.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 888 | Implementar importación de CSV | Media |

**Estado**: Feature adicional para importar memorias de traducción.

## Addon Network Communities (1 item)
Archivo: `addons/flavor-network-communities/includes/mesh/class-gossip-protocol.php`

| Línea | TODO | Prioridad |
|-------|------|-----------|
| 698 | Implementar registro de peers desconocidos | Media |

**Estado**: Mejora del protocolo de red mesh. Funciona sin esto.

---

## Resumen

| Prioridad | Cantidad |
|-----------|----------|
| Alta | 0 |
| Media | 6 |
| Baja | 8 |
| **Total** | **14** |

**Conclusión**: Ninguno de estos TODOs es crítico para producción. El plugin es completamente funcional sin ellos.

---
*Documentado: 2024-04-14*
