# Flavor Platform 3.3.0

Plugin WordPress modular para comunidades, administracion, dashboards, contenidos, economia colaborativa, apps y modulos sectoriales.

## Novedades v3.3.0

- **Bootstrap Modular** - Código principal refactorizado en 5 clases especializadas
- **Sistema de Migrations** - 9 migrations versionadas con WP-CLI
- **CSS Consolidado** - 102 archivos organizados en 5 subdirectorios con build system
- **Federación Completa** - Sincronización de 8 tipos de contenido entre nodos
- **Webhooks** - Notificaciones en tiempo real con firma HMAC-SHA256
- **Shortcodes de Red** - 8 shortcodes para mostrar contenido federado

Ver `docs/ARQUITECTURA-V3.3.md` para detalles técnicos.

## Punto de entrada recomendado

La documentacion canonica para entender el plugin y navegar por sus subsistemas esta en:

- `docs/INDICE-DOCUMENTACION.md`
- `docs/ARQUITECTURA-V3.3.md` (nuevo)

## Lectura minima recomendada

- `docs/FILOSOFIA-PLUGIN.md`
- `docs/PLUGIN-COMPLETO.md`
- `docs/GUIA-ADMINISTRACION.md`
- `docs/GUIA_MODULOS.md`
- `docs/ESTADO-REAL-PLUGIN.md`

## Referencia de estado vigente

La auditoria de referencia para el estado real del sistema pasa a ser:

- `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`

## Alineacion documental realizada

Ya quedaron alineados con esa auditoria:

- `docs/ESTADO-REAL-PLUGIN.md`
- `docs/PLUGIN-COMPLETO.md`
- `docs/GUIA_MODULOS.md`
- `docs/CATALOGO-MODULOS.md`
- `docs/INDICE-DOCUMENTACION.md`

Esos documentos deben leerse como capa canonica actual.

## Nota

Si un documento historico contradice:

- el codigo actual
- la version declarada `3.3.0`
- la auditoria de `2026-03-04`
- la documentacion canonica de `docs/`

debe tratarse como material de contexto, no como fuente unica de verdad.
