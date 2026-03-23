<?php
/**
 * Vista de listado de campanias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_campanias';

$tipo_filtro = sanitize_text_field($atts['tipo'] ?? $_GET['tipo'] ?? '');
$estado_filtro = sanitize_text_field($atts['estado'] ?? $_GET['estado'] ?? 'activa');
$limite = intval($atts['limite'] ?? 12);
$columnas = intval($atts['columnas'] ?? 3);

$where = "WHERE visibilidad = 'publica'";
$params = [];

if ($tipo_filtro) {
    $where .= " AND tipo = %s";
    $params[] = $tipo_filtro;
}
if ($estado_filtro) {
    $where .= " AND estado = %s";
    $params[] = $estado_filtro;
}

$params[] = $limite;

$campanias = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla $where ORDER BY destacada DESC, created_at DESC LIMIT %d",
    $params
));

$tipos_campania = [
    'protesta' => __('Protesta', 'flavor-chat-ia'),
    'recogida_firmas' => __('Recogida de firmas', 'flavor-chat-ia'),
    'concentracion' => __('Concentración', 'flavor-chat-ia'),
    'boicot' => __('Boicot', 'flavor-chat-ia'),
    'denuncia_publica' => __('Denuncia pública', 'flavor-chat-ia'),
    'sensibilizacion' => __('Sensibilización', 'flavor-chat-ia'),
    'accion_legal' => __('Acción legal', 'flavor-chat-ia'),
    'otra' => __('Otra', 'flavor-chat-ia'),
];
?>

<div class="flavor-campanias-wrapper">
    <form method="get" class="flavor-campanias-filtros" aria-label="<?php echo esc_attr__('Formulario de filtros de campañas', 'flavor-chat-ia'); ?>" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
        <?php if (isset($_GET['page'])): ?>
            <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['page']))); ?>">
        <?php endif; ?>
        <div>
            <label for="flavor-campanias-tipo" class="screen-reader-text"><?php esc_html_e('Filtrar por tipo', 'flavor-chat-ia'); ?></label>
            <select id="flavor-campanias-tipo" name="tipo" class="flavor-campanias-filtro flavor-filtro-tipo">
                <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                <?php foreach ($tipos_campania as $valor => $etiqueta): ?>
                    <option value="<?php echo esc_attr($valor); ?>" <?php selected($tipo_filtro, $valor); ?>>
                        <?php echo esc_html($etiqueta); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="flavor-campanias-estado" class="screen-reader-text"><?php esc_html_e('Filtrar por estado', 'flavor-chat-ia'); ?></label>
            <select id="flavor-campanias-estado" name="estado" class="flavor-campanias-filtro flavor-filtro-estado">
                <option value="activa" <?php selected($estado_filtro, 'activa'); ?>><?php esc_html_e('Activas', 'flavor-chat-ia'); ?></option>
                <option value="planificada" <?php selected($estado_filtro, 'planificada'); ?>><?php esc_html_e('Planificadas', 'flavor-chat-ia'); ?></option>
                <option value="completada" <?php selected($estado_filtro, 'completada'); ?>><?php esc_html_e('Completadas', 'flavor-chat-ia'); ?></option>
                <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <button type="submit" class="button"><?php esc_html_e('Aplicar filtros', 'flavor-chat-ia'); ?></button>
    </form>

    <?php if (empty($campanias)): ?>
        <div class="flavor-empty-state" style="text-align: center; padding: 3rem;">
            <span class="dashicons dashicons-megaphone" style="font-size: 3rem; color: #9ca3af;"></span>
            <p style="color: #6b7280; margin-top: 1rem;"><?php esc_html_e('No hay campañas que mostrar.', 'flavor-chat-ia'); ?></p>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/campanias/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Crear nueva campaña', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-campanias-grid" style="--columnas: <?php echo $columnas; ?>;">
            <?php foreach ($campanias as $campania):
                $porcentaje_firmas = $campania->objetivo_firmas > 0
                    ? ($campania->firmas_actuales / $campania->objetivo_firmas) * 100
                    : 0;
            ?>
                <article class="flavor-campania-card <?php echo $campania->destacada ? 'destacada' : ''; ?>">
                    <?php if ($campania->imagen): ?>
                        <img src="<?php echo esc_url($campania->imagen); ?>" alt="" class="flavor-campania-imagen">
                    <?php else: ?>
                        <div class="flavor-campania-imagen"></div>
                    <?php endif; ?>

                    <div class="flavor-campania-content">
                        <span class="flavor-campania-tipo">
                            <?php echo esc_html($tipos_campania[$campania->tipo] ?? $campania->tipo); ?>
                        </span>

                        <h3 class="flavor-campania-titulo">
                            <a href="<?php echo esc_url(add_query_arg('campania_id', $campania->id, home_url('/campanias/'))); ?>">
                                <?php echo esc_html($campania->titulo); ?>
                            </a>
                        </h3>

                        <p class="flavor-campania-descripcion">
                            <?php echo esc_html(wp_trim_words($campania->descripcion, 25)); ?>
                        </p>

                        <?php if ($campania->tipo === 'recogida_firmas' && $campania->objetivo_firmas > 0): ?>
                            <div class="flavor-firmas-progress">
                                <div class="flavor-firmas-bar">
                                    <div class="flavor-firmas-fill" data-porcentaje="<?php echo esc_attr($porcentaje_firmas); ?>"></div>
                                </div>
                                <div class="flavor-firmas-count">
                                    <span class="flavor-firmas-actual">
                                        <?php
                                        printf(
                                            esc_html__('%s firmas', 'flavor-chat-ia'),
                                            number_format_i18n((int) $campania->firmas_actuales)
                                        );
                                        ?>
                                    </span>
                                    <span class="flavor-firmas-objetivo" data-objetivo="<?php echo esc_attr($campania->objetivo_firmas); ?>">
                                        <?php
                                        printf(
                                            esc_html__('Objetivo: %s', 'flavor-chat-ia'),
                                            number_format_i18n((int) $campania->objetivo_firmas)
                                        );
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="flavor-campania-stats">
                            <?php if ($campania->fecha_inicio): ?>
                                <span class="flavor-campania-stat">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo date_i18n('j M Y', strtotime($campania->fecha_inicio)); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($campania->ubicacion): ?>
                                <span class="flavor-campania-stat">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($campania->ubicacion); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
