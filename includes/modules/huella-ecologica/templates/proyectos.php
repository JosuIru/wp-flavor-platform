<?php
/**
 * Template: Proyectos de Compensación
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$estados = Flavor_Chat_Huella_Ecologica_Module::ESTADOS_PROYECTO;

// Obtener proyectos activos
global $wpdb;
$proyectos = $wpdb->get_results(
    "SELECT p.*, pm_estado.meta_value as estado,
            pm_meta.meta_value as meta_co2,
            pm_actual.meta_value as co2_actual,
            pm_ubicacion.meta_value as ubicacion,
            pm_tipo.meta_value as tipo_proyecto,
            pm_participantes.meta_value as participantes
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '_he_estado'
     LEFT JOIN {$wpdb->postmeta} pm_meta ON p.ID = pm_meta.post_id AND pm_meta.meta_key = '_he_meta_co2'
     LEFT JOIN {$wpdb->postmeta} pm_actual ON p.ID = pm_actual.post_id AND pm_actual.meta_key = '_he_co2_actual'
     LEFT JOIN {$wpdb->postmeta} pm_ubicacion ON p.ID = pm_ubicacion.post_id AND pm_ubicacion.meta_key = '_he_ubicacion'
     LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_he_tipo_proyecto'
     LEFT JOIN {$wpdb->postmeta} pm_participantes ON p.ID = pm_participantes.post_id AND pm_participantes.meta_key = '_he_participantes'
     WHERE p.post_type = 'he_proyecto'
       AND pm_estado.meta_value IN ('aprobado', 'en_curso')
     ORDER BY p.post_date DESC"
);

$tipos_proyecto = [
    'reforestacion' => ['nombre' => 'Reforestación', 'icono' => '🌳'],
    'huerto' => ['nombre' => 'Huerto comunitario', 'icono' => '🥕'],
    'energia' => ['nombre' => 'Energía renovable', 'icono' => '☀️'],
    'movilidad' => ['nombre' => 'Movilidad sostenible', 'icono' => '🚲'],
    'reciclaje' => ['nombre' => 'Reciclaje', 'icono' => '♻️'],
    'educacion' => ['nombre' => 'Educación ambiental', 'icono' => '📚'],
    'biodiversidad' => ['nombre' => 'Biodiversidad', 'icono' => '🦋'],
    'agua' => ['nombre' => 'Conservación del agua', 'icono' => '💧'],
];
?>

<div class="he-container">
    <header class="he-header">
        <h2>
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <?php esc_html_e('Proyectos de Compensación', 'flavor-platform'); ?>
        </h2>
        <p><?php esc_html_e('Iniciativas comunitarias para reducir y compensar nuestra huella ecológica colectiva', 'flavor-platform'); ?></p>
    </header>

    <?php if (is_user_logged_in()) : ?>
    <div style="text-align: right; margin-bottom: 1.5rem;">
        <button class="he-btn he-btn--primary he-btn-abrir-modal" data-modal="modal-proponer">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Proponer proyecto', 'flavor-platform'); ?>
        </button>
    </div>
    <?php endif; ?>

    <?php if ($proyectos) : ?>
    <div class="he-proyectos-grid">
        <?php foreach ($proyectos as $proyecto) :
            $estado_data = $estados[$proyecto->estado] ?? $estados['propuesto'];
            $tipo_data = $tipos_proyecto[$proyecto->tipo_proyecto] ?? ['nombre' => 'Otro', 'icono' => '🌱'];
            $participantes = maybe_unserialize($proyecto->participantes) ?: [];
            $num_participantes = is_array($participantes) ? count($participantes) : 0;
            $progreso = $proyecto->meta_co2 > 0 ? min(100, ($proyecto->co2_actual / $proyecto->meta_co2) * 100) : 0;
            $ya_participo = is_array($participantes) && in_array($user_id, $participantes);
        ?>
        <article class="he-proyecto-card" data-tipo="<?php echo esc_attr($proyecto->tipo_proyecto); ?>">
            <div class="he-proyecto-card__imagen">
                <?php if (has_post_thumbnail($proyecto->ID)) : ?>
                    <?php echo get_the_post_thumbnail($proyecto->ID, 'medium'); ?>
                <?php else : ?>
                    <span style="font-size: 4rem;"><?php echo esc_html($tipo_data['icono']); ?></span>
                <?php endif; ?>
            </div>

            <div class="he-proyecto-card__body">
                <div class="he-proyecto-card__header">
                    <h3 class="he-proyecto-card__titulo"><?php echo esc_html($proyecto->post_title); ?></h3>
                    <span class="he-estado-badge he-estado-badge--<?php echo esc_attr($proyecto->estado); ?>">
                        <?php echo esc_html($estado_data['nombre']); ?>
                    </span>
                </div>

                <p class="he-proyecto-card__descripcion">
                    <?php echo esc_html(wp_trim_words($proyecto->post_content, 25)); ?>
                </p>

                <?php if ($proyecto->meta_co2 > 0) : ?>
                <div class="he-proyecto-card__progreso">
                    <div class="he-proyecto-card__progreso-header">
                        <span><?php esc_html_e('Progreso', 'flavor-platform'); ?></span>
                        <span><?php echo esc_html(number_format($proyecto->co2_actual, 0)); ?> / <?php echo esc_html(number_format($proyecto->meta_co2, 0)); ?> kg CO2</span>
                    </div>
                    <div class="he-progreso-bar">
                        <div class="he-progreso-bar__fill" data-progreso="<?php echo esc_attr($progreso); ?>" style="width: <?php echo esc_attr($progreso); ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="he-proyecto-card__meta">
                    <span>
                        <span class="dashicons dashicons-groups"></span>
                        <span class="he-participantes-count"><?php echo esc_html($num_participantes); ?></span> <?php esc_html_e('participantes', 'flavor-platform'); ?>
                    </span>
                    <?php if ($proyecto->ubicacion) : ?>
                    <span>
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($proyecto->ubicacion); ?>
                    </span>
                    <?php endif; ?>
                    <span><?php echo esc_html($tipo_data['icono']); ?> <?php echo esc_html($tipo_data['nombre']); ?></span>
                </div>
            </div>

            <div class="he-proyecto-card__footer">
                <?php if (is_user_logged_in()) : ?>
                    <?php if ($ya_participo) : ?>
                    <span class="he-btn he-btn--secondary" style="cursor: default;">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Ya participas', 'flavor-platform'); ?>
                    </span>
                    <?php elseif ($proyecto->estado === 'aprobado' || $proyecto->estado === 'en_curso') : ?>
                    <button class="he-btn he-btn--primary he-btn-unirse" data-proyecto="<?php echo esc_attr($proyecto->ID); ?>">
                        <span class="dashicons dashicons-groups"></span>
                        <?php esc_html_e('Unirme', 'flavor-platform'); ?>
                    </button>
                    <?php endif; ?>
                <?php else : ?>
                <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="he-btn he-btn--secondary">
                    <?php esc_html_e('Inicia sesión para unirte', 'flavor-platform'); ?>
                </a>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="he-empty-state">
        <span class="dashicons dashicons-admin-site-alt3"></span>
        <p><?php esc_html_e('Aún no hay proyectos de compensación activos.', 'flavor-platform'); ?></p>
        <?php if (is_user_logged_in()) : ?>
        <p><?php esc_html_e('¡Sé el primero en proponer uno!', 'flavor-platform'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal proponer proyecto -->
<div id="modal-proponer" class="he-modal">
    <div class="he-modal__contenido">
        <div class="he-modal__header">
            <h3><?php esc_html_e('Proponer proyecto de compensación', 'flavor-platform'); ?></h3>
            <button class="he-modal__cerrar">&times;</button>
        </div>
        <form class="he-modal__body he-form-proyecto">
            <div class="he-form-grupo">
                <label for="proyecto-titulo"><?php esc_html_e('Nombre del proyecto', 'flavor-platform'); ?> *</label>
                <input type="text" name="titulo" id="proyecto-titulo" required
                       placeholder="<?php esc_attr_e('Ej: Reforestación en el parque municipal', 'flavor-platform'); ?>">
            </div>

            <div class="he-form-grupo">
                <label for="proyecto-tipo"><?php esc_html_e('Tipo de proyecto', 'flavor-platform'); ?></label>
                <select name="tipo_proyecto" id="proyecto-tipo">
                    <?php foreach ($tipos_proyecto as $tipo_id => $tipo_data) : ?>
                    <option value="<?php echo esc_attr($tipo_id); ?>">
                        <?php echo esc_html($tipo_data['icono'] . ' ' . $tipo_data['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="he-form-grupo">
                <label for="proyecto-descripcion"><?php esc_html_e('Descripción', 'flavor-platform'); ?> *</label>
                <textarea name="descripcion" id="proyecto-descripcion" rows="4" required
                          placeholder="<?php esc_attr_e('Describe el proyecto, sus objetivos y cómo se llevará a cabo...', 'flavor-platform'); ?>"></textarea>
            </div>

            <div class="he-form-row">
                <div class="he-form-grupo">
                    <label for="proyecto-meta"><?php esc_html_e('Meta de compensación (kg CO2)', 'flavor-platform'); ?></label>
                    <input type="number" name="meta_co2" id="proyecto-meta" min="0" step="100"
                           placeholder="<?php esc_attr_e('Ej: 1000', 'flavor-platform'); ?>">
                </div>
                <div class="he-form-grupo">
                    <label for="proyecto-ubicacion"><?php esc_html_e('Ubicación', 'flavor-platform'); ?></label>
                    <input type="text" name="ubicacion" id="proyecto-ubicacion"
                           placeholder="<?php esc_attr_e('Ej: Parque del Oeste', 'flavor-platform'); ?>">
                </div>
            </div>

            <div class="he-modal__footer">
                <button type="button" class="he-btn he-btn--secondary he-modal__cerrar">
                    <?php esc_html_e('Cancelar', 'flavor-platform'); ?>
                </button>
                <button type="submit" class="he-btn he-btn--primary">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Proponer', 'flavor-platform'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
