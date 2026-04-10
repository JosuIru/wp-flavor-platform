<?php
/**
 * Dashboard de Themacle
 *
 * Panel administrativo para componentes web universales reutilizables
 * basados en la librería Themacle de Figma.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener módulo para acceder a componentes
$module = Flavor_Platform_Module_Loader::get_instance()->get_module('themacle');
$web_components = $module && method_exists($module, 'get_web_components') ? $module->get_web_components() : [];

// Contar componentes por categoría
$categorias_componentes = [];
foreach ($web_components as $id => $comp) {
    $cat = $comp['category'] ?? 'otros';
    if (!isset($categorias_componentes[$cat])) {
        $categorias_componentes[$cat] = 0;
    }
    $categorias_componentes[$cat]++;
}

$total_componentes = count($web_components);
$total_categorias = count($categorias_componentes);

// Páginas usando componentes Themacle
global $wpdb;
$paginas_con_componentes = 0;
$meta_query = $wpdb->get_var(
    "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
     WHERE meta_key = '_flavor_vbp_data'
     AND meta_value LIKE '%themacle%'"
);
$paginas_con_componentes = (int) $meta_query;

// Contar campos totales disponibles
$total_campos = 0;
foreach ($web_components as $comp) {
    $total_campos += count($comp['fields'] ?? []);
}

// Categorías de componentes disponibles
$categorias_labels = [
    'hero' => [
        'nombre' => __('Heroes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-cover-image',
        'color' => '#8b5cf6',
    ],
    'content' => [
        'nombre' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-editor-paragraph',
        'color' => '#3b82f6',
    ],
    'listings' => [
        'nombre' => __('Listados', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-grid-view',
        'color' => '#10b981',
    ],
    'features' => [
        'nombre' => __('Características', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-star-filled',
        'color' => '#f59e0b',
    ],
    'cta' => [
        'nombre' => __('CTA', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-megaphone',
        'color' => '#ef4444',
    ],
    'navigation' => [
        'nombre' => __('Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-menu',
        'color' => '#6366f1',
    ],
    'otros' => [
        'nombre' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-admin-generic',
        'color' => '#64748b',
    ],
];

// Lista de componentes destacados (primeros 8)
$componentes_destacados = array_slice($web_components, 0, 8, true);
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('themacle'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-art" style="color: #d946ef;"></span>
            <h1><?php esc_html_e('Themacle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=themacle-temas')); ?>" class="button button-primary">
                <span class="dashicons dashicons-admin-appearance"></span>
                <?php esc_html_e('Ver Temas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=themacle-dashboard')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-screenoptions"></span>
                <span><?php esc_html_e('Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themacle-temas')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-appearance"></span>
                <span><?php esc_html_e('Temas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(rest_url('flavor/v1/themacle/componentes')); ?>" class="dm-action-item" target="_blank">
                <span class="dashicons dashicons-rest-api"></span>
                <span><?php esc_html_e('API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themacle-config')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary" style="border-left-color: #d946ef;">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-screenoptions"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($total_componentes); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Componentes Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($total_categorias); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-page"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($paginas_con_componentes); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Páginas Usando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-forms"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($total_campos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Campos Configurables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Info del Módulo -->
    <div class="dm-card" style="background: linear-gradient(135deg, #d946ef 0%, #a855f7 100%); color: white;">
        <div class="dm-card__body" style="padding: 25px;">
            <h3 style="color: white; margin-top: 0;"><span class="dashicons dashicons-art"></span> <?php esc_html_e('Componentes Universales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="opacity: 0.9; margin-bottom: 0;">
                <?php esc_html_e('Themacle es una librería de componentes web universales y reutilizables. Los componentes se adaptan automáticamente al tema visual activo mediante CSS custom properties, garantizando coherencia visual en todo el sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>

    <div class="dm-grid dm-grid--2">
        <!-- Componentes Destacados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Componentes Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($componentes_destacados)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Componente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($componentes_destacados as $id => $comp):
                            $cat = $comp['category'] ?? 'otros';
                            $cat_info = $categorias_labels[$cat] ?? $categorias_labels['otros'];
                        ?>
                        <tr>
                            <td>
                                <span class="dashicons <?php echo esc_attr($comp['icon'] ?? 'dashicons-screenoptions'); ?>" style="color: <?php echo esc_attr($cat_info['color']); ?>;"></span>
                                <strong><?php echo esc_html($comp['label'] ?? $id); ?></strong>
                            </td>
                            <td>
                                <span class="dm-badge" style="background: <?php echo esc_attr($cat_info['color']); ?>; color: white;">
                                    <?php echo esc_html($cat_info['nombre']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($total_componentes > 8): ?>
                <p style="text-align: center; margin-top: 10px; color: #64748b;">
                    <?php printf(esc_html__('+ %d componentes más disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_componentes - 8); ?>
                </p>
                <?php endif; ?>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('No hay componentes disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribución por Categoría -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($categorias_componentes)): ?>
                <div class="dm-distribution">
                    <?php foreach ($categorias_componentes as $cat => $cantidad):
                        $porcentaje = $total_componentes > 0 ? ($cantidad / $total_componentes) * 100 : 0;
                        $cat_info = $categorias_labels[$cat] ?? $categorias_labels['otros'];
                    ?>
                    <div class="dm-distribution__item">
                        <div class="dm-distribution__label">
                            <span>
                                <span class="dashicons <?php echo esc_attr($cat_info['icono']); ?>" style="font-size: 16px; width: 16px; height: 16px; color: <?php echo esc_attr($cat_info['color']); ?>; vertical-align: middle;"></span>
                                <?php echo esc_html($cat_info['nombre']); ?>
                            </span>
                            <span><?php echo esc_html($cantidad); ?></span>
                        </div>
                        <div class="dm-progress">
                            <div class="dm-progress__bar" style="background: <?php echo esc_attr($cat_info['color']); ?>; width: <?php echo esc_attr($porcentaje); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin categorías.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Componentes por Categoría -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Catálogo de Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-grid dm-grid--3" style="gap: 15px;">
                <?php foreach ($categorias_labels as $cat_slug => $cat_info):
                    if (!isset($categorias_componentes[$cat_slug])) continue;
                    $cantidad = $categorias_componentes[$cat_slug];
                ?>
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo esc_attr($cat_info['color']); ?>;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span class="dashicons <?php echo esc_attr($cat_info['icono']); ?>" style="font-size: 24px; width: 24px; height: 24px; color: <?php echo esc_attr($cat_info['color']); ?>;"></span>
                        <strong><?php echo esc_html($cat_info['nombre']); ?></strong>
                    </div>
                    <p style="margin: 0; color: #64748b; font-size: 0.9em;">
                        <?php printf(esc_html(_n('%d componente disponible', '%d componentes disponibles', $cantidad, FLAVOR_PLATFORM_TEXT_DOMAIN)), $cantidad); ?>
                    </p>
                    <ul style="margin: 10px 0 0; padding-left: 20px; font-size: 0.85em; color: #64748b;">
                        <?php
                        $count = 0;
                        foreach ($web_components as $id => $comp) {
                            if (($comp['category'] ?? 'otros') === $cat_slug) {
                                echo '<li>' . esc_html($comp['label']) . '</li>';
                                $count++;
                                if ($count >= 4) {
                                    if ($cantidad > 4) {
                                        echo '<li style="font-style: italic;">+ ' . ($cantidad - 4) . ' más...</li>';
                                    }
                                    break;
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Cómo Usar -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Cómo Usar Themacle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-grid dm-grid--3" style="gap: 15px;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center;">
                    <span class="dashicons dashicons-layout" style="font-size: 32px; color: #d946ef;"></span>
                    <p style="margin: 10px 0 0; font-weight: bold;"><?php esc_html_e('Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p style="margin: 5px 0 0; font-size: 0.9em; color: #64748b;"><?php esc_html_e('Arrastra y suelta componentes en el editor visual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center;">
                    <span class="dashicons dashicons-rest-api" style="font-size: 32px; color: #3b82f6;"></span>
                    <p style="margin: 10px 0 0; font-weight: bold;"><?php esc_html_e('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p style="margin: 5px 0 0; font-size: 0.9em; color: #64748b;"><?php esc_html_e('Consulta componentes vía /flavor/v1/themacle/', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center;">
                    <span class="dashicons dashicons-admin-appearance" style="font-size: 32px; color: #10b981;"></span>
                    <p style="margin: 10px 0 0; font-weight: bold;"><?php esc_html_e('Temas Adaptativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p style="margin: 5px 0 0; font-size: 0.9em; color: #64748b;"><?php esc_html_e('Se adaptan al tema activo automáticamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tip -->
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-lightbulb"></span>
        <div class="dm-alert__content">
            <strong><?php esc_html_e('Consejo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong>
            <?php esc_html_e('Los componentes Themacle usan CSS custom properties. Cambia el tema visual del sitio para ver cómo se adaptan los colores, tipografías y espaciados automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </div>
    </div>
</div>
