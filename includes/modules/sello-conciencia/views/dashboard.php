<?php
/**
 * Dashboard de Sello de Conciencia
 *
 * Panel administrativo para evaluar el nivel de conciencia de la aplicación
 * basándose en los módulos activos y las 5 premisas fundamentales.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener módulo para acceder a métodos de evaluación
$module = Flavor_Platform_Module_Loader::get_instance()->get_module('sello_conciencia');
$evaluacion = [];
$puntuacion_global = 0;
$nivel_actual = 'ninguno';

if ($module && method_exists($module, 'evaluar_aplicacion')) {
    $evaluacion = $module->evaluar_aplicacion();
    $puntuacion_global = $evaluacion['puntuacion_global'] ?? 0;
    $nivel_actual = $evaluacion['nivel'] ?? 'ninguno';
}

// Premisas definidas
$premisas = [
    'conciencia_fundamental' => [
        'nombre' => __('Conciencia Fundamental', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-heart',
        'color' => '#9b59b6',
        'descripcion' => __('La conciencia es tan fundamental como la materia.', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
    'abundancia_organizable' => [
        'nombre' => __('Abundancia Organizable', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-share-alt',
        'color' => '#27ae60',
        'descripcion' => __('No hay escasez de recursos; hay escasez de distribución equitativa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
    'interdependencia_radical' => [
        'nombre' => __('Interdependencia Radical', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-networking',
        'color' => '#3498db',
        'descripcion' => __('La separación es abstracción útil pero no realidad ontológica.', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
    'madurez_ciclica' => [
        'nombre' => __('Madurez Cíclica', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-update',
        'color' => '#e67e22',
        'descripcion' => __('Los sistemas sanos crecen, maduran y se renuevan cíclicamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
    'valor_intrinseco' => [
        'nombre' => __('Valor Intrínseco', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-awards',
        'color' => '#f39c12',
        'descripcion' => __('Las cosas valen por lo que son, no por lo que puede extraerse de ellas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
];

// Niveles de conciencia
$niveles = [
    'ninguno' => ['nombre' => __('Sin evaluar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#95a5a6', 'icono' => 'dashicons-minus'],
    'basico' => ['nombre' => __('Básico', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#e74c3c', 'icono' => 'dashicons-marker'],
    'transicion' => ['nombre' => __('En Transición', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f39c12', 'icono' => 'dashicons-arrow-up-alt'],
    'consciente' => ['nombre' => __('Consciente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#27ae60', 'icono' => 'dashicons-yes-alt'],
    'ejemplar' => ['nombre' => __('Ejemplar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#9b59b6', 'icono' => 'dashicons-star-filled'],
];

$nivel_info = $niveles[$nivel_actual] ?? $niveles['ninguno'];

// Módulos activos que contribuyen
$modulos_activos = Flavor_Platform_Module_Loader::get_instance()->get_active_modules();
$total_modulos_activos = count($modulos_activos);

// Puntuación por premisa (simulada si no hay evaluación real)
$puntuaciones_premisas = $evaluacion['premisas'] ?? [];
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('sello_conciencia'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-awards" style="color: #16a34a;"></span>
            <h1><?php esc_html_e('Sello de Conciencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=sello-conciencia-evaluar')); ?>" class="button button-primary">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Evaluar App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Nivel Actual Destacado -->
    <div class="dm-card" style="background: linear-gradient(135deg, <?php echo esc_attr($nivel_info['color']); ?> 0%, <?php echo esc_attr($nivel_info['color']); ?>cc 100%); color: white;">
        <div class="dm-card__body" style="text-align: center; padding: 30px;">
            <span class="dashicons <?php echo esc_attr($nivel_info['icono']); ?>" style="font-size: 48px; width: 48px; height: 48px;"></span>
            <h2 style="color: white; margin: 15px 0 5px; font-size: 1.8em;"><?php esc_html_e('Nivel:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($nivel_info['nombre']); ?></h2>
            <div style="font-size: 3em; font-weight: bold; margin: 10px 0;">
                <?php echo esc_html(round($puntuacion_global)); ?><span style="font-size: 0.5em;">/100</span>
            </div>
            <p style="opacity: 0.9; margin: 0;">
                <?php esc_html_e('Puntuación global de conciencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=sello-conciencia-evaluar')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-chart-bar"></span>
                <span><?php esc_html_e('Evaluación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=sello-conciencia-premisas')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-book"></span>
                <span><?php esc_html_e('5 Premisas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=sello-conciencia-modulos')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-plugins"></span>
                <span><?php esc_html_e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=sello-conciencia-certificado')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-awards"></span>
                <span><?php esc_html_e('Certificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-plugins"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($total_modulos_activos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Módulos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value">5</span>
                <span class="dm-stat-card__label"><?php esc_html_e('Premisas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(round($puntuacion_global)); ?>%</span>
                <span class="dm-stat-card__label"><?php esc_html_e('Cumplimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-<?php echo esc_attr($nivel_info['icono']); ?>"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($nivel_info['nombre']); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Las 5 Premisas -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Las 5 Premisas de Economía Consciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-grid dm-grid--1" style="gap: 15px;">
                <?php foreach ($premisas as $id => $premisa):
                    $puntuacion_premisa = $puntuaciones_premisas[$id] ?? 0;
                ?>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo esc_attr($premisa['color']); ?>;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span class="dashicons <?php echo esc_attr($premisa['icono']); ?>" style="color: <?php echo esc_attr($premisa['color']); ?>; font-size: 20px;"></span>
                        <strong><?php echo esc_html($premisa['nombre']); ?></strong>
                        <span class="dm-badge" style="background: <?php echo esc_attr($premisa['color']); ?>; color: white; margin-left: auto;">
                            <?php echo esc_html(round($puntuacion_premisa)); ?>%
                        </span>
                    </div>
                    <p style="margin: 0; color: #64748b; font-size: 0.9em;"><?php echo esc_html($premisa['descripcion']); ?></p>
                    <div class="dm-progress" style="margin-top: 10px;">
                        <div class="dm-progress__bar" style="background: <?php echo esc_attr($premisa['color']); ?>; width: <?php echo esc_attr($puntuacion_premisa); ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Niveles de Conciencia -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Niveles de Conciencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-grid dm-grid--5" style="gap: 10px;">
                <?php foreach ($niveles as $id => $nivel):
                    $es_actual = $id === $nivel_actual;
                ?>
                <div style="background: <?php echo $es_actual ? esc_attr($nivel['color']) : '#f8fafc'; ?>; color: <?php echo $es_actual ? 'white' : '#64748b'; ?>; padding: 15px; border-radius: 8px; text-align: center; <?php echo $es_actual ? 'box-shadow: 0 4px 6px rgba(0,0,0,0.1);' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr($nivel['icono']); ?>" style="font-size: 24px;"></span>
                    <p style="margin: 8px 0 0; font-weight: bold; font-size: 0.85em;"><?php echo esc_html($nivel['nombre']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="dm-card" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white;">
        <div class="dm-card__body" style="padding: 25px;">
            <h3 style="color: white; margin-top: 0;"><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('¿Qué es el Sello de Conciencia?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="opacity: 0.9; margin-bottom: 0;">
                <?php esc_html_e('El Sello de Conciencia evalúa automáticamente tu aplicación basándose en los módulos activos y su alineación con las 5 premisas fundamentales de una economía consciente. Cuantos más módulos actives que promuevan valores éticos, sostenibles y comunitarios, mayor será tu puntuación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>
</div>
