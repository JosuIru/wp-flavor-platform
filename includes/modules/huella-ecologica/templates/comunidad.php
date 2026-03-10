<?php
/**
 * Template: Estadísticas de Comunidad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$modulo = new Flavor_Chat_Huella_Ecologica_Module();
$stats_comunidad = $modulo->get_estadisticas_comunidad();
$categorias = Flavor_Chat_Huella_Ecologica_Module::CATEGORIAS_HUELLA;

// Obtener ranking de contribuyentes
$top_contribuyentes = $stats_comunidad['top_contribuyentes'];
?>

<div class="he-container">
    <!-- Header destacado -->
    <div class="he-comunidad-header">
        <h2 class="he-comunidad-header__titulo">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e('Impacto Comunitario', 'flavor-chat-ia'); ?>
        </h2>
        <p class="he-comunidad-header__subtitulo">
            <?php esc_html_e('Juntos estamos reduciendo nuestra huella ecológica colectiva', 'flavor-chat-ia'); ?>
        </p>

        <div class="he-comunidad-stats">
            <div class="he-comunidad-stat">
                <div class="he-comunidad-stat__valor"><?php echo esc_html(number_format($stats_comunidad['huella_comunidad'], 0)); ?></div>
                <div class="he-comunidad-stat__label"><?php esc_html_e('kg CO2 registrados', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="he-comunidad-stat">
                <div class="he-comunidad-stat__valor"><?php echo esc_html(number_format($stats_comunidad['reduccion_comunidad'], 0)); ?></div>
                <div class="he-comunidad-stat__label"><?php esc_html_e('kg CO2 compensados', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="he-comunidad-stat">
                <div class="he-comunidad-stat__valor"><?php echo esc_html($stats_comunidad['usuarios_activos']); ?></div>
                <div class="he-comunidad-stat__label"><?php esc_html_e('personas activas', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="he-comunidad-stat">
                <div class="he-comunidad-stat__valor"><?php echo esc_html($stats_comunidad['proyectos_activos']); ?></div>
                <div class="he-comunidad-stat__label"><?php esc_html_e('proyectos activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
        <!-- Gráfico de impacto -->
        <div>
            <h3 style="margin-bottom: 1rem;">
                <span class="dashicons dashicons-chart-area"></span>
                <?php esc_html_e('Balance ecológico del mes', 'flavor-chat-ia'); ?>
            </h3>

            <div style="background: var(--he-bg-card); border-radius: var(--he-radius); padding: 2rem; box-shadow: var(--he-shadow);">
                <!-- Visualización de balance -->
                <div style="display: flex; justify-content: space-around; text-align: center; margin-bottom: 2rem;">
                    <div>
                        <div style="font-size: 3rem; color: var(--he-danger);">
                            <span class="dashicons dashicons-cloud"></span>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 700;"><?php echo esc_html(number_format($stats_comunidad['huella_comunidad'], 0)); ?> kg</div>
                        <div style="color: var(--he-text-light);"><?php esc_html_e('Emitido', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; font-size: 2rem; color: var(--he-text-light);">−</div>
                    <div>
                        <div style="font-size: 3rem; color: var(--he-secondary);">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 700;"><?php echo esc_html(number_format($stats_comunidad['reduccion_comunidad'], 0)); ?> kg</div>
                        <div style="color: var(--he-text-light);"><?php esc_html_e('Compensado', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; font-size: 2rem; color: var(--he-text-light);">=</div>
                    <div>
                        <div style="font-size: 3rem; color: <?php echo $stats_comunidad['huella_neta'] <= 0 ? 'var(--he-primary)' : 'var(--he-warning)'; ?>;">
                            <span class="dashicons dashicons-performance"></span>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 700;"><?php echo esc_html(number_format($stats_comunidad['huella_neta'], 0)); ?> kg</div>
                        <div style="color: var(--he-text-light);"><?php esc_html_e('Huella neta', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>

                <?php
                $porcentaje_compensado = $stats_comunidad['huella_comunidad'] > 0
                    ? min(100, ($stats_comunidad['reduccion_comunidad'] / $stats_comunidad['huella_comunidad']) * 100)
                    : 0;
                ?>
                <div style="margin-top: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><?php esc_html_e('Porcentaje compensado', 'flavor-chat-ia'); ?></span>
                        <span style="font-weight: 600;"><?php echo esc_html(number_format($porcentaje_compensado, 1)); ?>%</span>
                    </div>
                    <div class="he-progreso-bar" style="height: 16px; border-radius: 8px;">
                        <div class="he-progreso-bar__fill" style="width: <?php echo esc_attr($porcentaje_compensado); ?>%; border-radius: 8px;"></div>
                    </div>
                </div>

                <?php if ($porcentaje_compensado >= 100) : ?>
                <div style="margin-top: 1.5rem; text-align: center; padding: 1rem; background: color-mix(in srgb, var(--he-primary) 10%, #fff); border-radius: 8px;">
                    <span style="font-size: 2rem;">🎉</span>
                    <p style="margin: 0.5rem 0 0; font-weight: 600; color: var(--he-primary);">
                        <?php esc_html_e('¡Este mes somos carbono neutro como comunidad!', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Objetivos -->
            <div style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem;">
                    <span class="dashicons dashicons-flag"></span>
                    <?php esc_html_e('Objetivos colectivos', 'flavor-chat-ia'); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <div style="background: var(--he-bg-card); padding: 1.25rem; border-radius: var(--he-radius); text-align: center;">
                        <div style="font-size: 2rem;">🌍</div>
                        <div style="font-weight: 600; margin: 0.5rem 0;"><?php esc_html_e('2030', 'flavor-chat-ia'); ?></div>
                        <div style="font-size: 0.9rem; color: var(--he-text-light);"><?php esc_html_e('Reducir 50% la huella', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="background: var(--he-bg-card); padding: 1.25rem; border-radius: var(--he-radius); text-align: center;">
                        <div style="font-size: 2rem;">🌳</div>
                        <div style="font-weight: 600; margin: 0.5rem 0;"><?php esc_html_e('100 árboles', 'flavor-chat-ia'); ?></div>
                        <div style="font-size: 0.9rem; color: var(--he-text-light);"><?php esc_html_e('Meta de plantación', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="background: var(--he-bg-card); padding: 1.25rem; border-radius: var(--he-radius); text-align: center;">
                        <div style="font-size: 2rem;">♻️</div>
                        <div style="font-weight: 600; margin: 0.5rem 0;"><?php esc_html_e('Zero Waste', 'flavor-chat-ia'); ?></div>
                        <div style="font-size: 0.9rem; color: var(--he-text-light);"><?php esc_html_e('Comunidad sin residuos', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking lateral -->
        <div>
            <div class="he-ranking">
                <h3 class="he-ranking__titulo">
                    <span class="dashicons dashicons-awards"></span>
                    <?php esc_html_e('Top compensadores', 'flavor-chat-ia'); ?>
                </h3>

                <?php if ($top_contribuyentes) : ?>
                    <?php foreach ($top_contribuyentes as $index => $contribuyente) :
                        $usuario = get_userdata($contribuyente->post_author);
                        if (!$usuario) continue;
                    ?>
                    <div class="he-ranking-item">
                        <span class="he-ranking-item__posicion"><?php echo esc_html($index + 1); ?></span>
                        <?php echo get_avatar($contribuyente->post_author, 36, '', '', ['class' => 'he-ranking-item__avatar']); ?>
                        <div class="he-ranking-item__info">
                            <div class="he-ranking-item__nombre"><?php echo esc_html($usuario->display_name); ?></div>
                        </div>
                        <div class="he-ranking-item__valor"><?php echo esc_html(number_format($contribuyente->reduccion_total, 0)); ?> kg</div>
                    </div>
                    <?php endforeach; ?>
                <?php else : ?>
                <p style="color: var(--he-text-light); text-align: center; padding: 1rem;">
                    <?php esc_html_e('Aún no hay datos suficientes', 'flavor-chat-ia'); ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Únete -->
            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, var(--he-primary), var(--he-secondary)); border-radius: var(--he-radius); padding: 1.5rem; color: white; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🌱</div>
                <h4 style="margin: 0 0 0.5rem;"><?php esc_html_e('¿Quieres contribuir?', 'flavor-chat-ia'); ?></h4>
                <p style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 1rem;">
                    <?php esc_html_e('Cada acción cuenta para reducir nuestra huella colectiva', 'flavor-chat-ia'); ?>
                </p>
                <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/mi-portal/huella-ecologica/calculadora/')); ?>" class="he-btn" style="background: white; color: var(--he-primary);">
                    <?php esc_html_e('Calcular mi huella', 'flavor-chat-ia'); ?>
                </a>
                <?php else : ?>
                <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="he-btn" style="background: white; color: var(--he-primary);">
                    <?php esc_html_e('Unirme', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Proyectos activos -->
            <div style="margin-top: 1.5rem;">
                <h4 style="margin-bottom: 1rem;">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php esc_html_e('Proyectos activos', 'flavor-chat-ia'); ?>
                </h4>
                <?php if ($stats_comunidad['proyectos_activos'] > 0) : ?>
                <a href="<?php echo esc_url(home_url('/mi-portal/huella-ecologica/proyectos/')); ?>" class="he-btn he-btn--secondary" style="width: 100%; justify-content: center;">
                    <?php printf(esc_html__('Ver %d proyectos', 'flavor-chat-ia'), $stats_comunidad['proyectos_activos']); ?>
                </a>
                <?php else : ?>
                <p style="color: var(--he-text-light); font-size: 0.9rem;">
                    <?php esc_html_e('No hay proyectos activos aún', 'flavor-chat-ia'); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
