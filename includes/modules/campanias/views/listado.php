<?php
/**
 * Vista de listado de campanias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

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
    'protesta' => 'Protesta',
    'recogida_firmas' => 'Recogida de Firmas',
    'concentracion' => 'Concentracion',
    'boicot' => 'Boicot',
    'denuncia_publica' => 'Denuncia Publica',
    'sensibilizacion' => 'Sensibilizacion',
    'accion_legal' => 'Accion Legal',
    'otra' => 'Otra',
];
?>

<div class="flavor-campanias-wrapper">
    <div class="flavor-campanias-filtros" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <select class="flavor-campanias-filtro flavor-filtro-tipo">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipos_campania as $valor => $etiqueta): ?>
                <option value="<?php echo esc_attr($valor); ?>" <?php selected($tipo_filtro, $valor); ?>>
                    <?php echo esc_html($etiqueta); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select class="flavor-campanias-filtro flavor-filtro-estado">
            <option value="activa" <?php selected($estado_filtro, 'activa'); ?>>Activas</option>
            <option value="planificada" <?php selected($estado_filtro, 'planificada'); ?>>Planificadas</option>
            <option value="completada" <?php selected($estado_filtro, 'completada'); ?>>Completadas</option>
            <option value="">Todas</option>
        </select>
    </div>

    <?php if (empty($campanias)): ?>
        <div class="flavor-empty-state" style="text-align: center; padding: 3rem;">
            <span class="dashicons dashicons-megaphone" style="font-size: 3rem; color: #9ca3af;"></span>
            <p style="color: #6b7280; margin-top: 1rem;">No hay campanias que mostrar.</p>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/campanias/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    Crear nueva campania
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
                                    <span class="flavor-firmas-actual"><?php echo number_format($campania->firmas_actuales); ?> firmas</span>
                                    <span class="flavor-firmas-objetivo" data-objetivo="<?php echo esc_attr($campania->objetivo_firmas); ?>">
                                        Objetivo: <?php echo number_format($campania->objetivo_firmas); ?>
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
