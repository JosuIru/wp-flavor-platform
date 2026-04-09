<?php
/**
 * Vista del Panel de Tours de Ayuda
 *
 * Muestra todos los tours disponibles con su estado y opciones
 *
 * @package FlavorPlatform
 * @subpackage Admin/Views
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$tours_manager = Flavor_Guided_Tours::get_instance();
$all_tours = $tours_manager->get_all_tours();
$completed_tours = $tours_manager->get_completed_tours();
$tour_stats = $tours_manager->get_tour_stats();

// Organizar tours por categoría
$tours_categorias = [
    'basicos' => [
        'titulo' => __('Tours Básicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Aprende los conceptos fundamentales de Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'tours' => ['tour_dashboard', 'tour_modulos', 'tour_chat_ia'],
    ],
    'personalizacion' => [
        'titulo' => __('Personalización', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Configura la apariencia y comportamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'tours' => ['tour_diseno', 'tour_landing'],
    ],
    'emprendimiento' => [
        'titulo' => __('Emprendimiento y Tejido Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Herramientas para crear y gestionar el ecosistema empresarial local', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'tours' => ['tour_tejido_empresarial'],
    ],
    'avanzados' => [
        'titulo' => __('Funciones Avanzadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Características avanzadas y red distribuida', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'tours' => ['tour_red_nodos', 'tour_app_profiles'],
    ],
];
?>

<div class="wrap flavor-tours-panel">
    <!-- Header -->
    <div class="flavor-tours-header">
        <div class="flavor-tours-header-content">
            <h1><?php esc_html_e('Centro de Tours y Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <p><?php esc_html_e('Aprende a usar Flavor Platform con tours interactivos paso a paso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="flavor-tours-stats">
            <div class="flavor-tours-stat">
                <div class="flavor-tours-stat-value"><?php echo esc_html($tour_stats['completed']); ?></div>
                <div class="flavor-tours-stat-label"><?php esc_html_e('Completados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="flavor-tours-stat">
                <div class="flavor-tours-stat-value"><?php echo esc_html($tour_stats['total']); ?></div>
                <div class="flavor-tours-stat-label"><?php esc_html_e('Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="flavor-tours-progress">
                <div class="flavor-tours-progress-bar">
                    <div class="flavor-tours-progress-fill" style="width: <?php echo esc_attr($tour_stats['percentage']); ?>%"></div>
                </div>
                <span class="flavor-tours-progress-text"><?php echo esc_html($tour_stats['percentage']); ?>%</span>
            </div>
        </div>
    </div>

    <!-- Acciones globales -->
    <div class="flavor-tours-actions" style="margin-bottom: 30px;">
        <button type="button" class="button" id="flavor-reset-all-tours">
            <span class="dashicons dashicons-image-rotate" style="vertical-align: middle;"></span>
            <?php esc_html_e('Reiniciar Todos los Tours', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>

    <?php if ($tour_stats['completed'] === $tour_stats['total'] && $tour_stats['total'] > 0): ?>
    <!-- Mensaje de felicitación -->
    <div class="notice notice-success" style="padding: 20px; display: flex; align-items: center; gap: 15px;">
        <span class="dashicons dashicons-awards" style="font-size: 40px; width: 40px; height: 40px; color: #00a32a;"></span>
        <div>
            <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Has completado todos los tours!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="margin: 0; color: #646970;">
                <?php esc_html_e('Ahora conoces todas las funcionalidades de Flavor Platform. Si necesitas repasar algún tema, puedes reiniciar cualquier tour.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tours por categoría -->
    <?php foreach ($tours_categorias as $categoria_slug => $categoria): ?>
        <div class="flavor-tours-categoria" style="margin-bottom: 40px;">
            <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600;">
                <?php echo esc_html($categoria['titulo']); ?>
            </h2>
            <p style="margin: 0 0 20px 0; color: #646970;">
                <?php echo esc_html($categoria['descripcion']); ?>
            </p>

            <div class="flavor-tours-grid">
                <?php foreach ($categoria['tours'] as $tour_id):
                    if (!isset($all_tours[$tour_id])) continue;

                    $tour = $all_tours[$tour_id];
                    $is_completed = in_array($tour_id, $completed_tours);
                    $pasos_count = isset($tour['pasos']) ? count($tour['pasos']) : 0;

                    // Encontrar la página donde se ejecuta este tour
                    $pagina_destino = '';
                    if (!empty($tour['paginas'])) {
                        $pagina_slug = str_replace(['flavor-platform_page_', 'toplevel_page_'], '', $tour['paginas'][0]);
                        $pagina_destino = admin_url('admin.php?page=' . $pagina_slug);
                    }
                ?>
                    <div class="flavor-tour-card <?php echo $is_completed ? 'completed' : ''; ?>" data-tour-id="<?php echo esc_attr($tour_id); ?>">
                        <div class="flavor-tour-card-header">
                            <div class="flavor-tour-card-icon">
                                <span class="dashicons <?php echo esc_attr($tour['icono'] ?? 'dashicons-info'); ?>"></span>
                            </div>
                            <div class="flavor-tour-card-title">
                                <h3><?php echo esc_html($tour['titulo']); ?></h3>
                                <span><?php echo esc_html($tour['duracion'] ?? ''); ?></span>
                            </div>
                            <span class="flavor-tour-card-status">
                                <?php echo $is_completed ? esc_html__('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>

                        <div class="flavor-tour-card-body">
                            <p class="flavor-tour-card-description">
                                <?php echo esc_html($tour['descripcion']); ?>
                            </p>

                            <div class="flavor-tour-card-meta">
                                <span>
                                    <span class="dashicons dashicons-editor-ol"></span>
                                    <?php
                                    printf(
                                        esc_html(_n('%d paso', '%d pasos', $pasos_count, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                                        $pasos_count
                                    );
                                    ?>
                                </span>
                                <?php if (!empty($tour['video_url'])): ?>
                                <span>
                                    <span class="dashicons dashicons-video-alt3"></span>
                                    <?php esc_html_e('Con video', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-tour-card-actions">
                                <?php if ($pagina_destino): ?>
                                    <a href="<?php echo esc_url($pagina_destino . '&start_tour=' . $tour_id); ?>" class="button button-primary">
                                        <?php if ($is_completed): ?>
                                            <span class="dashicons dashicons-controls-repeat" style="vertical-align: middle; margin-right: 4px;"></span>
                                            <?php esc_html_e('Repetir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-controls-play" style="vertical-align: middle; margin-right: 4px;"></span>
                                            <?php esc_html_e('Iniciar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($is_completed): ?>
                                    <button type="button" class="button flavor-reset-tour-btn" data-tour-id="<?php echo esc_attr($tour_id); ?>">
                                        <span class="dashicons dashicons-image-rotate" style="vertical-align: middle; margin-right: 4px;"></span>
                                        <?php esc_html_e('Reiniciar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>

                                <?php if (!empty($tour['video_url'])): ?>
                                    <button type="button" class="button flavor-tour-video-btn" data-video-url="<?php echo esc_url($tour['video_url']); ?>">
                                        <span class="dashicons dashicons-video-alt3" style="vertical-align: middle;"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Sección de Videos Tutoriales -->
    <div class="flavor-tours-videos" style="margin-top: 40px;">
        <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600;">
            <span class="dashicons dashicons-video-alt3" style="vertical-align: middle; margin-right: 8px;"></span>
            <?php esc_html_e('Videos Tutoriales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <p style="margin: 0 0 20px 0; color: #646970;">
            <?php esc_html_e('Videos explicativos sobre las principales funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>

        <div class="flavor-videos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <!-- Video placeholders - se llenarían con videos reales -->
            <div class="flavor-video-card" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                <div style="position: relative; padding-bottom: 56.25%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-format-video" style="font-size: 48px; width: 48px; height: 48px; color: rgba(255,255,255,0.8);"></span>
                    </div>
                </div>
                <div style="padding: 16px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px;"><?php esc_html_e('Introducción a Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p style="margin: 0; font-size: 12px; color: #646970;"><?php esc_html_e('Visión general de la plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <div class="flavor-video-card" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                <div style="position: relative; padding-bottom: 56.25%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-format-video" style="font-size: 48px; width: 48px; height: 48px; color: rgba(255,255,255,0.8);"></span>
                    </div>
                </div>
                <div style="padding: 16px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px;"><?php esc_html_e('Configuración del Motor IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p style="margin: 0; font-size: 12px; color: #646970;"><?php esc_html_e('Cómo configurar tu API key y modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <div class="flavor-video-card" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                <div style="position: relative; padding-bottom: 56.25%; background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);">
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-format-video" style="font-size: 48px; width: 48px; height: 48px; color: rgba(255,255,255,0.8);"></span>
                    </div>
                </div>
                <div style="padding: 16px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px;"><?php esc_html_e('Crear Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p style="margin: 0; font-size: 12px; color: #646970;"><?php esc_html_e('Diseña páginas para tus apps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>

        <p style="margin-top: 20px; text-align: center; color: #646970; font-style: italic;">
            <?php esc_html_e('Más videos próximamente...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <!-- Sección de Recursos Adicionales -->
    <div class="flavor-tours-resources" style="margin-top: 40px; padding: 30px; background: #f6f7f7; border-radius: 8px;">
        <h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600;">
            <span class="dashicons dashicons-welcome-learn-more" style="vertical-align: middle; margin-right: 8px;"></span>
            <?php esc_html_e('Recursos Adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-docs')); ?>" class="flavor-resource-card" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border-radius: 6px; text-decoration: none; color: inherit; transition: all 0.2s;">
                <span class="dashicons dashicons-book" style="font-size: 24px; width: 24px; height: 24px; color: #2271b1;"></span>
                <div>
                    <strong style="display: block; margin-bottom: 2px;"><?php esc_html_e('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span style="font-size: 12px; color: #646970;"><?php esc_html_e('Guías completas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </a>

            <a href="https://support.flavor-platform.com" target="_blank" rel="noopener" class="flavor-resource-card" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border-radius: 6px; text-decoration: none; color: inherit; transition: all 0.2s;">
                <span class="dashicons dashicons-sos" style="font-size: 24px; width: 24px; height: 24px; color: #2271b1;"></span>
                <div>
                    <strong style="display: block; margin-bottom: 2px;"><?php esc_html_e('Soporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span style="font-size: 12px; color: #646970;"><?php esc_html_e('Ayuda técnica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </a>

            <a href="https://community.flavor-platform.com" target="_blank" rel="noopener" class="flavor-resource-card" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border-radius: 6px; text-decoration: none; color: inherit; transition: all 0.2s;">
                <span class="dashicons dashicons-groups" style="font-size: 24px; width: 24px; height: 24px; color: #2271b1;"></span>
                <div>
                    <strong style="display: block; margin-bottom: 2px;"><?php esc_html_e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span style="font-size: 12px; color: #646970;"><?php esc_html_e('Foro de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </a>

            <a href="https://changelog.flavor-platform.com" target="_blank" rel="noopener" class="flavor-resource-card" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border-radius: 6px; text-decoration: none; color: inherit; transition: all 0.2s;">
                <span class="dashicons dashicons-backup" style="font-size: 24px; width: 24px; height: 24px; color: #2271b1;"></span>
                <div>
                    <strong style="display: block; margin-bottom: 2px;"><?php esc_html_e('Changelog', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span style="font-size: 12px; color: #646970;"><?php esc_html_e('Novedades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
.flavor-resource-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.flavor-tours-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.flavor-tours-progress-text {
    font-size: 12px;
    color: #646970;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Reiniciar un tour individual
    $('.flavor-reset-tour-btn').on('click', function() {
        var tourId = $(this).data('tour-id');
        var $card = $(this).closest('.flavor-tour-card');

        if (confirm('<?php echo esc_js(__('¿Seguro que quieres reiniciar este tour?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_reset_tour',
                    nonce: FlavorOnboardingData.nonce,
                    tour_id: tourId
                },
                success: function(response) {
                    if (response.success) {
                        $card.removeClass('completed');
                        $card.find('.flavor-tour-card-status').text('<?php echo esc_js(__('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        location.reload();
                    }
                }
            });
        }
    });

    // Reiniciar todos los tours
    $('#flavor-reset-all-tours').on('click', function() {
        if (confirm('<?php echo esc_js(__('¿Seguro que quieres reiniciar TODOS los tours? Perderás todo el progreso.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_reset_all_tours',
                    nonce: FlavorOnboardingData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // Abrir video en modal
    $('.flavor-tour-video-btn').on('click', function() {
        var videoUrl = $(this).data('video-url');
        if (videoUrl && typeof FlavorOnboarding !== 'undefined') {
            FlavorOnboarding.openVideoModal(videoUrl);
        }
    });

    // Verificar si hay que iniciar un tour automáticamente
    var urlParams = new URLSearchParams(window.location.search);
    var startTour = urlParams.get('start_tour');
    if (startTour && typeof FlavorOnboarding !== 'undefined') {
        setTimeout(function() {
            FlavorOnboarding.startTour(startTour);
        }, 500);
    }
});
</script>
