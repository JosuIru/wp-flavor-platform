<?php
/**
 * Dashboard de Sector Empresarial
 *
 * Panel administrativo para componentes web profesionales corporativos.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener módulo para acceder a componentes
$module = Flavor_Platform_Module_Loader::get_instance()->get_module('empresarial');
$web_components = $module && method_exists($module, 'get_web_components') ? $module->get_web_components() : [];

// Contar componentes por categoría
$categorias_componentes = [];
foreach ($web_components as $id => $comp) {
    $cat = $comp['category'] ?? 'general';
    if (!isset($categorias_componentes[$cat])) {
        $categorias_componentes[$cat] = 0;
    }
    $categorias_componentes[$cat]++;
}

$total_componentes = count($web_components);

// Páginas usando componentes empresariales
global $wpdb;
$paginas_con_componentes = 0;
$meta_query = $wpdb->get_var(
    "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
     WHERE meta_key = '_flavor_vbp_data'
     AND meta_value LIKE '%empresarial%'"
);
$paginas_con_componentes = (int) $meta_query;

// Categorías de componentes disponibles
$categorias_labels = [
    'empresarial' => __('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'hero' => __('Hero', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'content' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cta' => __('Call to Action', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'features' => __('Características', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'team' => __('Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'testimonials' => __('Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pricing' => __('Precios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'general' => __('General', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Lista de componentes principales
$componentes_destacados = array_slice($web_components, 0, 8, true);
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('empresarial'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-building" style="color: #3b82f6;"></span>
            <h1><?php esc_html_e('Sector Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresarial-componentes')); ?>" class="button button-primary">
                <span class="dashicons dashicons-screenoptions"></span>
                <?php esc_html_e('Ver Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresarial-componentes')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-screenoptions"></span>
                <span><?php esc_html_e('Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresarial-plantillas')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-layout"></span>
                <span><?php esc_html_e('Plantillas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresarial-ejemplos')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-visibility"></span>
                <span><?php esc_html_e('Ejemplos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresarial-config')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary">
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
                <span class="dm-stat-card__value"><?php echo esc_html(count($categorias_componentes)); ?></span>
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
                <span class="dashicons dashicons-awards"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php esc_html_e('Pro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Info del Módulo -->
    <div class="dm-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;">
        <div class="dm-card__body" style="padding: 25px;">
            <h3 style="color: white; margin-top: 0;"><span class="dashicons dashicons-building"></span> <?php esc_html_e('Componentes Profesionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="opacity: 0.9; margin-bottom: 0;">
                <?php esc_html_e('Biblioteca de componentes web profesionales diseñados para empresas: héroes corporativos, secciones de servicios, equipos, testimonios, estadísticas, precios y más. Todos adaptables y personalizables.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>

    <div class="dm-grid dm-grid--2">
        <!-- Componentes Destacados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Componentes Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=empresarial-componentes')); ?>" class="dm-card__link">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
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
                        <?php foreach ($componentes_destacados as $id => $comp): ?>
                        <tr>
                            <td>
                                <span class="dashicons <?php echo esc_attr($comp['icon'] ?? 'dashicons-screenoptions'); ?>" style="color: #3b82f6;"></span>
                                <strong><?php echo esc_html($comp['label'] ?? $id); ?></strong>
                            </td>
                            <td>
                                <span class="dm-badge dm-badge--info">
                                    <?php echo esc_html($categorias_labels[$comp['category'] ?? 'general'] ?? ucfirst($comp['category'] ?? 'general')); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                    <?php
                    $colores = ['primary', 'success', 'info', 'warning', 'secondary'];
                    $indice_color = 0;
                    foreach ($categorias_componentes as $cat => $cantidad):
                        $porcentaje = $total_componentes > 0 ? ($cantidad / $total_componentes) * 100 : 0;
                        $color = $colores[$indice_color % count($colores)];
                        $indice_color++;
                    ?>
                    <div class="dm-distribution__item">
                        <div class="dm-distribution__label">
                            <span><?php echo esc_html($categorias_labels[$cat] ?? ucfirst($cat)); ?></span>
                            <span><?php echo esc_html($cantidad); ?></span>
                        </div>
                        <div class="dm-progress">
                            <div class="dm-progress__bar dm-progress__bar--<?php echo esc_attr($color); ?>" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
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

    <!-- Ejemplos de Uso -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Ejemplos de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-grid dm-grid--3" style="gap: 15px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center;">
                    <span class="dashicons dashicons-admin-home" style="font-size: 32px; color: #3b82f6;"></span>
                    <p style="margin: 10px 0 0; font-weight: bold;"><?php esc_html_e('Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center;">
                    <span class="dashicons dashicons-groups" style="font-size: 32px; color: #10b981;"></span>
                    <p style="margin: 10px 0 0; font-weight: bold;"><?php esc_html_e('Sobre Nosotros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center;">
                    <span class="dashicons dashicons-cart" style="font-size: 32px; color: #f59e0b;"></span>
                    <p style="margin: 10px 0 0; font-weight: bold;"><?php esc_html_e('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
