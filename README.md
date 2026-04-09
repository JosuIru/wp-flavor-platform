# Flavor Platform

Plugin WordPress modular para comunidades, cooperativas y asociaciones. Incluye web builder, apps móviles Flutter y automatización con IA.

Nombre comercial actual: `Flavor Platform`.
Slug técnico y compatibilidad actual: `flavor-chat-ia`.

## Quickstart

```bash
# 1. Activar plugin en WordPress
wp plugin activate flavor-chat-ia

# 2. Verificar instalación
bash tools/full-inventory.sh "http://tu-sitio.local" "." "mobile-apps"

# 3. Crear sitio con API
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -X POST "http://tu-sitio.local/wp-json/flavor-site-builder/v1/site/create" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"template": "cooperativa", "name": "Mi Cooperativa"}'
```

## Documentación

| Archivo | Contenido |
|---------|-----------|
| `CLAUDE.md` | **Instrucciones para Claude Code** (reglas, APIs, flujos) |
| `CLAUDE-APK.md` | Configuración de apps móviles |
| `CHANGELOG.md` | Historial de cambios |
| `docs/` | Documentación técnica detallada |

## Estructura Principal

```
flavor-chat-ia/  # slug técnico actual del plugin
├── includes/
│   ├── modules/           # Catálogo principal de módulos
│   ├── visual-builder-pro/ # Page builder
│   └── api/               # REST APIs
├── mobile-apps/           # Apps Flutter
│   └── lib/features/modules/  # Pantallas y módulos Flutter
├── addons/                # Extensiones opcionales
│   └── flavor-multilingual/   # Multiidioma
└── tools/                 # Scripts de utilidad
    ├── full-inventory.sh     # Inventario completo
    ├── vbp-inventory.sh      # Inventario VBP
    └── apk-inventory.sh      # Inventario APKs
```

## APIs Principales

| Base | Uso |
|------|-----|
| `/wp-json/flavor-vbp/v1/claude/` | Automatización con Claude Code |
| `/wp-json/flavor-site-builder/v1/` | Creación de sitios |
| `/wp-json/flavor-platform/v1/` | Diagnóstico y compatibilidad |
| `/wp-json/flavor-multilingual/v1/` | Traducciones (addon) |

## Requisitos

- WordPress 5.8+
- PHP 7.4+
- Para apps: Flutter 3.19+

## Auditoría de Estado

La referencia actualizada del estado real del sistema:
- `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`

## Documentación Histórica

Los documentos anteriores a marzo 2026 se han movido a `archive/docs-historicos/` para referencia. En caso de contradicción, el código y la auditoría tienen prioridad.

## Licencia

GPL-2.0+
