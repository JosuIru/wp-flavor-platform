# Documentación para Claude Code

Guías y referencias para crear sitios con Flavor Chat IA desde Claude Code.

## Archivos de Documentación

| Archivo | Descripción |
|---------|-------------|
| [VBP-BLOCKS-REFERENCE.md](VBP-BLOCKS-REFERENCE.md) | Referencia completa de bloques del Visual Builder Pro |
| [WORKFLOWS.md](WORKFLOWS.md) | Flujos de trabajo para crear sitios |
| [API-REFERENCE.md](API-REFERENCE.md) | Referencia de APIs REST |

## Endpoints Principales

### Obtener Schema de Bloques (Nuevo)
```bash
GET /wp-json/flavor-vbp/v1/blocks/schema
```
Retorna el schema JSON completo de todos los bloques disponibles con sus campos, variantes y opciones.

### Obtener Shortcodes (Nuevo)
```bash
GET /wp-json/flavor-vbp/v1/shortcodes
```
Lista todos los shortcodes disponibles organizados por módulo.

### Crear Sitio Completo
```bash
POST /wp-json/flavor-site-builder/v1/site/create
```
Crea un sitio completo desde una plantilla.

### Guardar Documento VBP
```bash
POST /wp-json/flavor-vbp/v1/documents/{id}
```
Guarda/actualiza el contenido de una landing page.

## Flujo Rápido: Crear Página

```bash
# 1. Crear post de landing
POST /wp-json/wp/v2/flavor_landing
{"title": "Mi Página", "status": "publish"}

# 2. Añadir contenido VBP
POST /wp-json/flavor-vbp/v1/documents/{id}
{
  "elements": [
    {"type": "hero", "data": {...}},
    {"type": "features", "data": {...}}
  ],
  "settings": {"pageWidth": "1200"}
}
```

## Variables CSS del Tema

```css
--flavor-primary
--flavor-secondary
--flavor-accent
--flavor-text
--flavor-text-muted
--flavor-bg
--flavor-border
```

## Autenticación

Usar Application Passwords de WordPress:
```bash
curl -u "usuario:xxxx xxxx xxxx xxxx" URL
```

## Módulos Principales

| Módulo | ID | Shortcodes principales |
|--------|-----|----------------------|
| Grupos de Consumo | `grupos_consumo` | `gc_catalogo`, `gc_grupos_lista` |
| Eventos | `eventos` | `eventos_proximos`, `ev_calendario` |
| Socios | `socios` | `socios_listado`, `socios_stats` |
| Marketplace | `marketplace` | `marketplace_productos` |
| Cursos | `cursos` | `cursos_catalogo`, `cursos_aula` |
| Reservas | `reservas` | `reservas_disponibilidad` |
| Incidencias | `incidencias` | `incidencias_crear` |
| Foros | `foros` | `foros_listado` |
