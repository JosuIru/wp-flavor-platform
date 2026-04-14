# Flavor Dashboard Components

Sistema de componentes reutilizables para construir dashboards de módulos con una apariencia consistente.

## Introducción

El trait `Flavor_Dashboard_Components` proporciona métodos para crear interfaces de dashboard con una UX unificada en todo el plugin. Usa el prefijo CSS `fdc-` (Flavor Dashboard Components).

## Uso Básico

```php
class Mi_Modulo_Dashboard_Tab {
    use Flavor_Dashboard_Components;

    public function render_tab() {
        // Encolar estilos (llamar una vez)
        $this->enqueue_dashboard_component_styles();

        // Crear dashboard
        echo $this->dashboard_header('Mi Módulo', 'admin-site', [
            ['label' => 'Total Items', 'value' => 42, 'icon' => 'chart-bar'],
            ['label' => 'Activos', 'value' => 38, 'icon' => 'yes-alt', 'color' => '#22c55e'],
        ], [
            ['label' => 'Crear Nuevo', 'url' => admin_url('admin.php?page=crear'), 'icon' => 'plus', 'primary' => true],
        ]);

        echo $this->dashboard_section_start('Listado', 'list-view');
        // ... contenido de la sección
        echo $this->dashboard_section_end();

        echo $this->dashboard_footer();
    }
}
```

## Componentes Disponibles

### 1. Header con Stats

```php
$this->dashboard_header(
    'Título',           // Título del dashboard
    'dashicons-name',   // Icono (sin prefijo dashicons-)
    [                   // Array de estadísticas
        [
            'label' => 'Etiqueta',
            'value' => 123,
            'icon' => 'chart-bar',      // Opcional
            'color' => '#3b82f6',       // Opcional: color personalizado
            'trend' => 5,               // Opcional: porcentaje de cambio (+/-)
        ],
    ],
    [                   // Array de acciones (botones)
        [
            'label' => 'Acción',
            'url' => '/path',
            'icon' => 'plus',           // Opcional
            'primary' => true,          // true = botón azul, false = outline
        ],
    ]
);

// Cerrar al final
$this->dashboard_footer();
```

### 2. Secciones

```php
echo $this->dashboard_section_start(
    'Título Sección',   // Título
    'calendar',         // Icono (opcional)
    [                   // Acciones de la sección (opcional)
        ['label' => 'Ver todos', 'url' => '/todos'],
    ]
);

// Contenido de la sección aquí

echo $this->dashboard_section_end();
```

### 3. Estado Vacío

```php
echo $this->dashboard_empty_state(
    'Sin elementos',                    // Título
    'Crea tu primer elemento...',       // Mensaje
    'marker',                           // Icono
    [                                   // Acción (opcional)
        'label' => 'Crear primero',
        'url' => '/crear',
        'icon' => 'plus',
    ]
);
```

### 4. Cards de Items

```php
echo $this->dashboard_item_card([
    'title' => 'Título del Item',
    'subtitle' => 'Descripción breve',  // Opcional
    'image' => 'url/imagen.jpg',        // Opcional
    'badge' => [                        // Opcional
        'text' => 'Activo',
        'type' => 'success',            // success, warning, error, info
    ],
    'meta' => [                         // Opcional: metadatos
        ['icon' => 'calendar', 'text' => '15 Ene 2025'],
        ['icon' => 'location', 'text' => 'Madrid'],
    ],
    'actions' => [                      // Opcional: botones de acción
        ['label' => 'Editar', 'url' => '/editar/1'],
        ['label' => 'Eliminar', 'ajax' => 'delete_item', 'id' => 1],
    ],
]);
```

### 5. Lista de Items

```php
echo $this->dashboard_item_list(
    $items,                             // Array de items
    function($item) {                   // Callback para renderizar cada item
        echo $this->dashboard_item_card([
            'title' => $item->titulo,
            // ...
        ]);
    },
    'No hay elementos'                  // Mensaje si vacío (opcional)
);
```

### 6. Tabs

```php
echo $this->dashboard_tabs([
    ['id' => 'activos', 'label' => 'Activos', 'icon' => 'yes', 'badge' => 5],
    ['id' => 'pendientes', 'label' => 'Pendientes', 'icon' => 'clock'],
    ['id' => 'archivados', 'label' => 'Archivados'],
], 'activos'); // Tab activo por defecto

echo $this->dashboard_tab_content_start('activos');
// Contenido del tab activos
echo $this->dashboard_tab_content_end();

echo $this->dashboard_tab_content_start('pendientes');
// Contenido del tab pendientes
echo $this->dashboard_tab_content_end();

echo $this->dashboard_tabs_end();
```

> **Nota**: Los tabs usan Alpine.js para la interactividad.

### 7. Tablas

```php
echo $this->dashboard_table(
    [   // Columnas
        ['key' => 'nombre', 'label' => 'Nombre', 'sortable' => true],
        ['key' => 'fecha', 'label' => 'Fecha'],
        [
            'key' => 'estado',
            'label' => 'Estado',
            'render' => function($row) {
                return '<span class="fdc-badge fdc-badge--' . $row['estado'] . '">' . ucfirst($row['estado']) . '</span>';
            }
        ],
        [
            'key' => 'acciones',
            'label' => '',
            'render' => function($row) {
                return '<a href="/editar/' . $row['id'] . '" class="fdc-btn fdc-btn--small">Editar</a>';
            }
        ],
    ],
    $rows,  // Datos
    [
        'empty_message' => 'No hay datos disponibles',
    ]
);
```

### 8. Timeline/Historial

```php
echo $this->dashboard_timeline([
    [
        'date' => '15 Ene 2025, 10:30',
        'title' => 'Elemento creado',
        'description' => 'El usuario X creó este elemento',
        'icon' => 'plus',
        'type' => 'success',  // success, warning, error, default
    ],
    [
        'date' => '14 Ene 2025, 15:00',
        'title' => 'Comentario añadido',
        'icon' => 'admin-comments',
    ],
]);
```

### 9. Barra de Progreso

```php
echo $this->dashboard_progress_bar(
    75,                 // Valor actual
    100,                // Valor máximo
    'Completado',       // Etiqueta (opcional)
    '#22c55e'           // Color (opcional)
);
```

### 10. Alertas

```php
echo $this->dashboard_alert(
    'Operación completada correctamente',
    'success',          // success, warning, error, info
    true                // Dismissible (cerrable)
);
```

### 11. Acciones Rápidas

```php
echo $this->dashboard_quick_actions([
    [
        'label' => 'Crear Elemento',
        'url' => '/crear',
        'icon' => 'plus',
        'description' => 'Añade un nuevo elemento',
        'color' => '#3b82f6',
    ],
    [
        'label' => 'Importar',
        'url' => '/importar',
        'icon' => 'upload',
        'description' => 'Desde CSV o Excel',
        'color' => '#22c55e',
    ],
]);
```

## Ejemplo Completo

```php
<?php
class Flavor_Eventos_Dashboard_Tab {
    use Flavor_Dashboard_Components;

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['eventos-mis-eventos'] = [
            'label' => __('Mis Eventos', 'flavor-platform'),
            'icon' => 'calendar-alt',
            'callback' => [$this, 'render_tab_eventos'],
            'orden' => 30,
        ];
        return $tabs;
    }

    public function render_tab_eventos() {
        $this->enqueue_dashboard_component_styles();

        $user_id = get_current_user_id();
        $eventos = $this->obtener_eventos_usuario($user_id);
        $stats = $this->obtener_estadisticas($user_id);

        // Header con stats
        echo $this->dashboard_header(
            __('Mis Eventos', 'flavor-platform'),
            'calendar-alt',
            [
                ['label' => __('Total', 'flavor-platform'), 'value' => $stats['total'], 'icon' => 'calendar'],
                ['label' => __('Próximos', 'flavor-platform'), 'value' => $stats['proximos'], 'icon' => 'clock', 'color' => '#3b82f6'],
                ['label' => __('Asistentes', 'flavor-platform'), 'value' => $stats['asistentes'], 'icon' => 'groups'],
            ],
            [
                [
                    'label' => __('Crear Evento', 'flavor-platform'),
                    'url' => home_url('/crear-evento/'),
                    'icon' => 'plus',
                    'primary' => true,
                ],
            ]
        );

        // Tabs
        echo $this->dashboard_tabs([
            ['id' => 'proximos', 'label' => __('Próximos', 'flavor-platform'), 'badge' => $stats['proximos']],
            ['id' => 'pasados', 'label' => __('Pasados', 'flavor-platform')],
        ], 'proximos');

        // Tab: Próximos
        echo $this->dashboard_tab_content_start('proximos');

        if (empty($eventos['proximos'])) {
            echo $this->dashboard_empty_state(
                __('Sin eventos próximos', 'flavor-platform'),
                __('Crea tu primer evento y compártelo con tu comunidad.', 'flavor-platform'),
                'calendar',
                [
                    'label' => __('Crear evento', 'flavor-platform'),
                    'url' => home_url('/crear-evento/'),
                    'icon' => 'plus',
                ]
            );
        } else {
            echo $this->dashboard_item_list(
                $eventos['proximos'],
                [$this, 'render_evento_card']
            );
        }

        echo $this->dashboard_tab_content_end();

        // Tab: Pasados
        echo $this->dashboard_tab_content_start('pasados');
        // ... contenido similar
        echo $this->dashboard_tab_content_end();

        echo $this->dashboard_tabs_end();
        echo $this->dashboard_footer();
    }

    public function render_evento_card($evento) {
        echo $this->dashboard_item_card([
            'title' => $evento->titulo,
            'subtitle' => wp_trim_words($evento->descripcion, 15),
            'badge' => [
                'text' => ucfirst($evento->estado),
                'type' => $evento->estado === 'activo' ? 'success' : 'warning',
            ],
            'meta' => [
                ['icon' => 'calendar', 'text' => date_i18n('d M Y', strtotime($evento->fecha))],
                ['icon' => 'location', 'text' => $evento->ubicacion ?: __('Online', 'flavor-platform')],
                ['icon' => 'groups', 'text' => sprintf(__('%d asistentes', 'flavor-platform'), $evento->asistentes)],
            ],
            'actions' => [
                ['label' => __('Ver', 'flavor-platform'), 'url' => get_permalink($evento->ID)],
                ['label' => __('Editar', 'flavor-platform'), 'url' => home_url('/editar-evento/' . $evento->ID)],
            ],
        ]);
    }
}
```

## Personalización CSS

Puedes sobrescribir las variables CSS para personalizar la apariencia:

```css
.fdc-dashboard {
    --fdc-primary: #your-color;
    --fdc-primary-hover: #your-hover-color;
    --fdc-radius: 12px;
    --fdc-shadow: your-shadow;
}
```

### Variables disponibles

| Variable | Default | Descripción |
|----------|---------|-------------|
| `--fdc-bg` | `#f8fafc` | Color de fondo |
| `--fdc-card-bg` | `#ffffff` | Fondo de cards |
| `--fdc-border` | `#e2e8f0` | Color de bordes |
| `--fdc-text` | `#1e293b` | Color de texto |
| `--fdc-text-muted` | `#64748b` | Texto secundario |
| `--fdc-primary` | `#3b82f6` | Color primario |
| `--fdc-success` | `#22c55e` | Color éxito |
| `--fdc-warning` | `#f59e0b` | Color advertencia |
| `--fdc-error` | `#ef4444` | Color error |
| `--fdc-radius` | `8px` | Border radius |
| `--fdc-radius-lg` | `12px` | Radius grande |

## Modo Oscuro

Los componentes soportan modo oscuro automáticamente via:
- `@media (prefers-color-scheme: dark)`
- Clase `body.fls-shell-dark`

## Dependencias

- **Alpine.js**: Requerido para tabs interactivos
- **Dashicons**: Iconos de WordPress

## Migración de Dashboards Existentes

Para migrar un dashboard existente al nuevo sistema:

1. Añadir `use Flavor_Dashboard_Components;` al trait
2. Llamar `$this->enqueue_dashboard_component_styles()` al inicio del render
3. Reemplazar HTML manual por métodos del trait
4. Eliminar CSS personalizado redundante

### Antes

```php
public function render_tab() {
    ?>
    <div class="carpooling-dashboard-tab">
        <div class="resumen-rapido">
            <div class="resumen-item">
                <span class="numero">42</span>
                <span class="label">Viajes</span>
            </div>
        </div>
        <div class="seccion">
            <h3>Próximos viajes</h3>
            <?php if (empty($viajes)): ?>
                <p class="vacio">No hay viajes</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
```

### Después

```php
public function render_tab() {
    $this->enqueue_dashboard_component_styles();

    echo $this->dashboard_header('Mis Viajes', 'car', [
        ['label' => 'Viajes', 'value' => 42, 'icon' => 'car'],
    ]);

    echo $this->dashboard_section_start('Próximos viajes', 'calendar');

    if (empty($viajes)) {
        echo $this->dashboard_empty_state(
            'Sin viajes',
            'No tienes viajes programados',
            'car'
        );
    }

    echo $this->dashboard_section_end();
    echo $this->dashboard_footer();
}
```

## Soporte

- Versión mínima PHP: 7.4
- Compatible con WordPress 5.8+
- Requiere Flavor Platform 3.5.1+
