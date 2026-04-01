# Guía: Sistema de Dashboards Mejorado

## Descripción

Sistema mejorado de componentes visuales para crear dashboards atractivos, consistentes y con mejor UX en todos los módulos de Flavor Platform.

---

## 🎨 Nuevos Componentes Disponibles

### 1. Stat Cards (Tarjetas de Estadísticas)

**Uso:**
```php
require_once FLAVOR_CHAT_IA_PATH . '/includes/dashboard/class-dashboard-components.php';
$DC = 'Flavor_Dashboard_Components';

echo $DC::stat_card([
    'value' => '1,234',
    'label' => 'Total Usuarios',
    'icon' => 'dashicons-admin-users',
    'color' => 'primary', // primary, success, warning, error, info, purple, pink, eco
    'trend' => 'up', // 'up', 'down', null
    'trend_value' => '+12%',
    'meta' => 'vs mes anterior',
    'link' => admin_url('admin.php?page=users'),
    'highlight' => false, // true para resaltar con degradado
]);
```

**Colores disponibles:**
- `primary` - Azul principal
- `success` - Verde
- `warning` - Naranja/Amarillo
- `error` - Rojo
- `info` - Azul información
- `purple` - Violeta
- `pink` - Rosa
- `eco` - Verde eco (con degradado)

### 2. Stats Grid (Grid de Estadísticas)

**Uso:**
```php
$stats = [
    ['value' => '123', 'label' => 'Total', 'icon' => 'dashicons-star', 'color' => 'success'],
    ['value' => '45', 'label' => 'Pendientes', 'icon' => 'dashicons-clock', 'color' => 'warning'],
    ['value' => '678', 'label' => 'Completados', 'icon' => 'dashicons-yes', 'color' => 'primary'],
];

// Auto-ajustable
echo $DC::stats_grid($stats);

// Forzar número de columnas
echo $DC::stats_grid($stats, 3); // 3 columnas
```

### 3. Data Tables (Tablas de Datos)

**Uso:**
```php
echo $DC::data_table([
    'title' => 'Usuarios Recientes',
    'icon' => 'dashicons-admin-users',
    'columns' => [
        'name' => 'Nombre',
        'email' => 'Email',
        'role' => 'Rol',
        'status' => 'Estado',
    ],
    'data' => [
        [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'role' => 'Editor',
            'status' => $DC::badge('Activo', 'success'),
        ],
        // ... más filas
    ],
    'empty_message' => 'No hay usuarios',
    'striped' => true,
    'hoverable' => true,
    'compact' => false,
]);
```

### 4. Progress Bars (Barras de Progreso)

**Uso:**
```php
echo $DC::progress_bar(
    75,           // Valor actual
    100,          // Valor máximo
    'Completado', // Label
    'success'     // Color
);
```

### 5. Badges (Insignias de Estado)

**Uso:**
```php
echo $DC::badge('Activo', 'success');
echo $DC::badge('Pendiente', 'warning');
echo $DC::badge('Error', 'error');
echo $DC::badge('Info', 'info');
```

### 6. Alerts (Alertas)

**Uso:**
```php
echo $DC::alert(
    'Este es un mensaje de éxito',
    'success',     // success, warning, error, info
    true           // dismissible (puede cerrarse)
);
```

### 7. Sections (Secciones con Header)

**Uso:**
```php
$content = '<p>Contenido de la sección...</p>';

echo $DC::section(
    'Mi Sección',
    $content,
    [
        'icon' => 'dashicons-admin-generic',
        'actions' => '<button class="button">Acción</button>',
        'collapsible' => true,
        'collapsed' => false,
    ]
);
```

### 8. Empty State (Estado Vacío)

**Uso:**
```php
echo $DC::empty_state(
    'No hay datos disponibles',
    'dashicons-info',
    '<a href="#" class="button button-primary">Crear Nuevo</a>'
);
```

### 9. Mini Charts (Gráficos Minimalistas)

**Uso:**
```php
$values = [45, 52, 48, 61, 58, 73, 69];
echo $DC::mini_chart($values, 'success');
```

---

## 📦 Assets a Incluir

### CSS
```php
// En tu dashboard.php
wp_enqueue_style(
    'flavor-dashboard-enhanced',
    plugins_url('assets/css/dashboard-components-enhanced.css', FLAVOR_CHAT_IA_FILE),
    [],
    '3.3.0'
);
```

### JavaScript (opcional, para animaciones)
```php
wp_enqueue_script(
    'flavor-dashboard-components',
    plugins_url('assets/js/dashboard-components.js', FLAVOR_CHAT_IA_FILE),
    ['jquery'],
    '3.3.0',
    true
);
```

---

## 🔄 Migrar Dashboard Existente

### Antes (Código antiguo):
```php
<div class="wrap">
    <h1>Dashboard de Mi Módulo</h1>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div style="background: white; padding: 20px; border-radius: 8px;">
            <div style="font-size: 28px; font-weight: bold;"><?php echo $total; ?></div>
            <div>Total Items</div>
        </div>
        <!-- Más cards... -->
    </div>

    <table class="wp-list-table widefat">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $item->name; ?></td>
                    <td><?php echo $item->status; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

### Después (Código mejorado):
```php
<?php
require_once FLAVOR_CHAT_IA_PATH . '/includes/dashboard/class-dashboard-components.php';
$DC = 'Flavor_Dashboard_Components';

wp_enqueue_style('flavor-dashboard-enhanced', plugins_url('assets/css/dashboard-components-enhanced.css', FLAVOR_CHAT_IA_FILE), [], '3.3.0');
wp_enqueue_script('flavor-dashboard-components', plugins_url('assets/js/dashboard-components.js', FLAVOR_CHAT_IA_FILE), ['jquery'], '3.3.0', true);
?>

<div class="wrap flavor-dashboard-wrap">

    <div class="dm-dashboard-header">
        <h1 class="dm-dashboard-title">
            <span class="dashicons dashicons-admin-generic"></span>
            Dashboard de Mi Módulo
        </h1>
    </div>

    <?php
    // Stats grid
    echo $DC::stats_grid([
        ['value' => $total, 'label' => 'Total Items', 'icon' => 'dashicons-star', 'color' => 'primary'],
        ['value' => $pending, 'label' => 'Pendientes', 'icon' => 'dashicons-clock', 'color' => 'warning'],
        ['value' => $completed, 'label' => 'Completados', 'icon' => 'dashicons-yes', 'color' => 'success'],
    ], 3);

    // Data table
    $table_data = [];
    foreach ($items as $item) {
        $table_data[] = [
            'name' => $item->name,
            'status' => $DC::badge($item->status, 'success'),
        ];
    }

    echo $DC::data_table([
        'title' => 'Items Recientes',
        'icon' => 'dashicons-list-view',
        'columns' => [
            'name' => 'Nombre',
            'status' => 'Estado',
        ],
        'data' => $table_data,
        'striped' => true,
        'hoverable' => true,
    ]);
    ?>

</div>
```

---

## 🎨 Variables CSS Disponibles

```css
--dm-primary
--dm-primary-hover
--dm-primary-light
--dm-secondary
--dm-success
--dm-warning
--dm-error
--dm-info

--dm-bg
--dm-bg-secondary
--dm-text
--dm-text-secondary
--dm-text-muted

--dm-border
--dm-radius
--dm-radius-sm
--dm-radius-lg
--dm-shadow
--dm-shadow-sm
--dm-shadow-md

--dm-transition
```

---

## ✨ Características Incluidas

### Animaciones
- Entrada suave de stat cards (slide in up)
- Hover effects en cards y tablas
- Animación de shimmer en progress bars
- Transiciones suaves

### Interactividad (JS)
- Secciones colapsables con estado persistente
- Alertas descartables
- Contadores animados en stat cards
- Mini charts interactivos
- Tooltips automáticos

### Responsividad
- Grid adaptativo para stat cards
- Tablas con scroll horizontal en móvil
- Headers de sección stackeables

### Accesibilidad
- ARIA labels adecuados
- Contraste de colores WCAG AA
- Focus states visibles
- Roles semánticos

### Dark Mode
- Soporte automático para `prefers-color-scheme: dark`
- Variables CSS adaptat adaptables

---

## 📚 Ejemplos Completos

### Dashboard Completo de Ejemplo
Ver: `includes/modules/assets/views/dashboard-example.php`

### Dashboard Real Mejorado
Ver: `includes/modules/assets/views/dashboard-v2.php`

---

## 🚀 Próximos Pasos

1. **Migrar dashboards existentes** gradualmente
2. **Crear templates** de dashboard por tipo de módulo
3. **Añadir más componentes**:
   - Tabs
   - Modals
   - Dropdowns
   - Date pickers
   - File uploaders
4. **Integrar gráficos** (Chart.js o similar)
5. **Dashboard builder** visual

---

## 💡 Tips y Best Practices

### 1. Consistencia
Usa siempre los mismos colores para tipos de estado:
- `success` - Verde para completados/activos
- `warning` - Amarillo para pendientes/advertencias
- `error` - Rojo para errores/cancelados
- `info` - Azul para información neutral

### 2. Jerarquía Visual
- Usa `highlight: true` solo para 1-2 stats más importantes
- Coloca stats más relevantes primero
- Agrupa información relacionada en secciones

### 3. Performance
- No incluir demasiados datos en una tabla (usar paginación)
- Mini charts limitados a 7-10 valores
- Lazy load de secciones colapsadas

### 4. Responsive
- Máximo 4-5 columnas en stats grid
- Tablas con columnas mínimas en móvil
- Considerar ocultar columnas secundarias

### 5. Accesibilidad
- Siempre añadir `icon` y `label` en stat cards
- Textos descriptivos en badges
- Mensajes claros en empty states

---

## 🐛 Solución de Problemas

### Los estilos no se aplican
```php
// Verifica que encolas el CSS:
wp_enqueue_style('flavor-dashboard-enhanced', ...);

// Y que la ruta es correcta:
plugins_url('assets/css/dashboard-components-enhanced.css', FLAVOR_CHAT_IA_FILE)
```

### Las animaciones no funcionan
```php
// Asegúrate de encolar el JavaScript:
wp_enqueue_script('flavor-dashboard-components', ...);
```

### Los componentes no se renderizan
```php
// Verifica que requieres la clase:
require_once FLAVOR_CHAT_IA_PATH . '/includes/dashboard/class-dashboard-components.php';

// Y que usas el alias correcto:
$DC = 'Flavor_Dashboard_Components';
```

---

## 📞 Soporte

Para preguntas o sugerencias sobre el sistema de dashboards mejorado, consulta:
- Documentación en `docs/GUIA-DASHBOARD-MEJORADO.md`
- Ejemplos en `includes/modules/assets/views/`
- Código fuente en `includes/dashboard/class-dashboard-components.php`

---

**Versión:** 1.0
**Fecha:** 2026-03-21
**Autor:** Flavor Platform Team
