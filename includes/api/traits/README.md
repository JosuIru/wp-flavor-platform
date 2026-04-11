# VBP Claude API - Traits

Este directorio contiene los traits que componen la API de VBP Claude refactorizada.

## Estructura

```
traits/
├── trait-vbp-api-pages.php    # CRUD de páginas VBP
├── trait-vbp-api-blocks.php   # Manipulación de bloques
├── trait-vbp-api-design.php   # Sistema de diseño y presets
├── trait-vbp-api-system.php   # Operaciones de sistema
└── README.md                  # Este archivo
```

## Traits Disponibles

### VBP_API_Pages
Operaciones CRUD de páginas:
- `create_page()` - Crear página VBP
- `get_page()` - Obtener página
- `update_page()` - Actualizar página
- `list_pages()` - Listar páginas
- `duplicate_page()` - Duplicar página
- `publish_page()` - Publicar página
- `get_page_url()` - Obtener URL

### VBP_API_Blocks
Manipulación de bloques:
- `list_blocks()` - Listar bloques disponibles
- `get_block_presets()` - Obtener presets de bloque
- `add_block()` - Añadir bloque a página
- `get_page_blocks()` - Obtener bloques de página
- `delete_block()` - Eliminar bloque
- `move_block()` - Mover bloque
- `reorder_page_blocks()` - Reordenar bloques
- `copy_blocks_between_pages()` - Copiar bloques entre páginas

### VBP_API_Design
Sistema de diseño:
- `get_design_presets()` - Obtener presets disponibles
- `get_design_preset()` - Obtener preset específico
- `create_styled_page()` - Crear página con estilos
- `get_default_styles()` - Estilos por defecto
- `merge_styles()` - Mezclar estilos

### VBP_API_System
Operaciones de sistema:
- `get_system_status()` - Estado del sistema
- `clear_vbp_cache()` - Limpiar caché
- `flush_permalinks()` - Regenerar permalinks
- `get_capabilities()` - Obtener capabilities
- `list_modules()` - Listar módulos

## Cómo Activar los Traits

Para migrar gradualmente de la clase monolítica a los traits:

1. **Descomentar use statements** en `class-vbp-claude-api.php`:
   ```php
   class Flavor_VBP_Claude_API {
       use VBP_API_Pages;
       use VBP_API_Blocks;
       use VBP_API_Design;
       use VBP_API_System;
   ```

2. **Eliminar métodos duplicados** de la clase principal que ya están en los traits.

3. **Probar cada trait** individualmente antes de activar el siguiente.

## Dependencias entre Traits

Los traits pueden necesitar métodos de la clase principal:
- `is_supported_post_type()` - Validar post type
- `is_valid_vbp_post()` - Validar post VBP
- `ensure_vbp_loaded()` - Cargar VBP
- `prepare_elements()` - Preparar elementos
- `create_section()` - Crear sección

Estos métodos deben permanecer en la clase principal o moverse a un trait base.

## Crear Nuevos Traits

Para extraer más funcionalidad:

1. Crear archivo `trait-vbp-api-{nombre}.php`
2. Definir trait con namespace adecuado
3. Agregar require en `class-vbp-claude-api.php`
4. Agregar use statement (comentado inicialmente)
5. Probar que funciona
6. Eliminar métodos duplicados de clase principal

## Traits Pendientes

Funcionalidades que podrían extraerse:
- `VBP_API_Templates` - Plantillas de sección
- `VBP_API_Analytics` - SEO, A11Y, estadísticas
- `VBP_API_Collaboration` - Bloqueo, comentarios
- `VBP_API_I18n` - Multiidioma/traducción
- `VBP_API_History` - Historial y versiones
- `VBP_API_Export` - Import/export
