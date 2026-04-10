# Sistema de Páginas Dinámicas

## Resumen

El sistema de páginas dinámicas maneja URLs tipo `/mi-portal/{modulo}/{accion}/` y renderiza el contenido apropiado.

## Archivo Principal

`includes/frontend/class-dynamic-pages.php` - Controla todo el flujo.

---

## Flujo de Renderizado

```
URL: /mi-portal/grupos-consumo/unirme/

1. parse_request() detecta:
   - current_module = "grupos-consumo"
   - current_action = "unirme"

2. render_module_content() decide qué mostrar:

   SI action es vacío o "index":
     -> Muestra dashboard del módulo con tabs

   SI action es "crear", "nuevo", "publicar", etc:
     -> Muestra formulario CRUD

   SI action es "editar" + item_id:
     -> Muestra formulario edición

   SI hay item_id:
     -> Muestra detalle del item (single)

   ELSE:
     -> render_module_action_content()
```

---

## render_module_action_content() - Prioridades

```php
1. PRIORIDAD 0.5: Tab con content definido
   - Busca en get_renderer_config()['tabs'][$action]
   - Si tiene 'content', lo renderiza
   - Tipos de content:
     - "[shortcode]" -> do_shortcode()
     - "template:archivo.php" -> incluye template
     - callable -> lo ejecuta
     - "metodo" -> llama método del módulo
     - string -> HTML directo

2. PRIORIDAD 1: Integración con otro módulo
   - Si tab tiene is_integration + source_module
   - Renderiza contenido del módulo fuente

3. PRIORIDAD 2: Shortcodes específicos hardcodeados
   - Array $shortcodes_especificos[modulo][accion]
   - ⚠️ LEGACY - Evitar usar, migrar a renderer_config

4. PRIORIDAD 3: Form Builder dinámico
   - Para acciones de creación
```

---

## Cómo Añadir una Nueva Acción

### Método Correcto (Recomendado)

En el módulo, definir en `get_renderer_config()`:

```php
public static function get_renderer_config(): array {
    return [
        'module' => 'mi-modulo',
        // ... otros campos ...

        'tabs' => [
            'mi-accion' => [
                'label'   => __('Mi Acción', 'flavor-platform'),
                'icon'    => 'dashicons-admin-generic',
                'content' => '[mi_shortcode]',  // O cualquier tipo de content
                'requires_login' => true,       // Opcional
                'hidden_nav'     => true,       // No mostrar en navegación de tabs
            ],
        ],
    ];
}
```

### Tipos de Content Soportados

| Formato | Ejemplo | Descripción |
|---------|---------|-------------|
| Shortcode | `'[mi_shortcode param="valor"]'` | Ejecuta shortcode |
| Template | `'template:formulario.php'` | Carga template PHP |
| Callable | `[$this, 'mi_metodo']` | Ejecuta función |
| Método | `'render_mi_seccion'` | Método del módulo |
| HTML | `'<div>Contenido</div>'` | HTML directo |

### Ejemplo Completo: Añadir "unirme" a grupos-consumo

```php
'tabs' => [
    // ... otros tabs ...

    'unirme' => [
        'label'         => __('Unirme', 'flavor-platform'),
        'icon'          => 'dashicons-plus-alt',
        'content'       => '[gc_formulario_union]',
        'requires_login' => true,
        'hidden_nav'    => true,
    ],
],
```

Con esto, `/mi-portal/grupos-consumo/unirme/` ejecutará el shortcode `[gc_formulario_union]`.

---

## Tabs vs Acciones

- **Tab del Dashboard**: Se muestra como pestaña en el dashboard principal del módulo
- **Acción oculta**: Tab con `hidden_nav => true`, accesible por URL pero no en navegación

### Para tabs visibles en dashboard:
- No poner `hidden_nav`
- El tab aparecerá en la barra de pestañas

### Para acciones solo por URL:
- Poner `'hidden_nav' => true`
- Solo accesible vía `/mi-portal/{modulo}/{accion}/`

---

## Integraciones entre Módulos

Para mostrar contenido de otro módulo (ej: foros dentro de grupos-consumo):

```php
'tabs' => [
    'foro' => [
        'label'          => __('Foro', 'flavor-platform'),
        'icon'           => 'dashicons-admin-comments',
        'is_integration' => true,
        'source_module'  => 'foros',  // Módulo fuente
    ],
],
```

El sistema buscará automáticamente el shortcode del módulo fuente.

---

## Archivos Importantes

| Archivo | Función |
|---------|---------|
| `class-dynamic-pages.php` | Controlador principal |
| `class-archive-renderer.php` | Renderiza listados/archives |
| `class-module-renderer.php` | Renderiza módulos completos |
| `{modulo}/class-{modulo}-module.php` | Configuración del módulo |

---

## Qué NO Hacer

1. **NO añadir shortcodes hardcodeados** en `$shortcodes_especificos`
   - Esto es legacy, usar `get_renderer_config()['tabs']`

2. **NO crear templates estáticos** para acciones simples
   - Usar shortcodes existentes via content

3. **NO duplicar lógica** en múltiples lugares
   - Todo debe estar en `get_renderer_config()`

---

## Migración de Legacy

Si encuentras código en `$shortcodes_especificos`:

```php
// ANTES (legacy)
$shortcodes_especificos = [
    'grupos-consumo' => [
        'unirme' => '[gc_grupos_lista]',
    ],
];

// DESPUÉS (correcto)
// En class-grupos-consumo-module.php -> get_renderer_config()
'tabs' => [
    'unirme' => [
        'label'   => __('Unirme', 'flavor-platform'),
        'icon'    => 'dashicons-plus-alt',
        'content' => '[gc_formulario_union]',
    ],
],
```

---

## Debug

Para verificar qué tabs tiene un módulo:

```php
$module = Flavor_Grupos_Consumo_Module::get_instance();
$config = Flavor_Grupos_Consumo_Module::get_renderer_config();
var_dump($config['tabs']);
```

---

## Checklist para Nueva Funcionalidad

- [ ] Identificar el módulo
- [ ] Decidir si es tab visible o acción oculta
- [ ] Añadir entrada en `get_renderer_config()['tabs']`
- [ ] Asegurar que el shortcode/template existe
- [ ] Probar URL `/mi-portal/{modulo}/{accion}/`
- [ ] Verificar requires_login si aplica
