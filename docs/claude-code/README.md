# Documentación para Claude Code

Guías y referencias para crear sitios con Flavor Platform desde Claude Code.

## Archivos de Documentación

| Archivo | Descripción |
|---------|-------------|
| [VBP-BLOCKS-REFERENCE.md](VBP-BLOCKS-REFERENCE.md) | Referencia completa de bloques del Visual Builder Pro |
| [WORKFLOWS.md](WORKFLOWS.md) | Flujos de trabajo para crear sitios |
| [API-REFERENCE.md](API-REFERENCE.md) | Referencia de APIs REST |

## Endpoints Principales

### Obtener Schema de Bloques
```bash
GET /wp-json/flavor-vbp/v1/blocks/schema
```
Retorna el schema JSON completo de todos los bloques disponibles (114 bloques, 10 categorías) con sus campos, variantes y opciones.

### Obtener Shortcodes
```bash
GET /wp-json/flavor-vbp/v1/shortcodes
```
Lista todos los shortcodes disponibles organizados por módulo.

### Single Templates (Plantillas para CPTs)
```bash
GET /wp-json/flavor-vbp/v1/single-templates/available-cpts
```
Lista todos los CPTs con sus campos disponibles para diseñar plantillas single.

```bash
POST /wp-json/flavor-vbp/v1/single-templates
```
Crea una plantilla VBP para páginas single de un CPT específico.

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
