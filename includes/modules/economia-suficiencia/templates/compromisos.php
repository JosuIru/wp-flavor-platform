<?php
/**
 * Template: Compromisos de Suficiencia
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$economia_suficiencia_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Economia_Suficiencia_Module')
    : 'Flavor_Chat_Economia_Suficiencia_Module';
$tipos_compromiso = $economia_suficiencia_module_class::TIPOS_COMPROMISO;

// Obtener compromisos activos del usuario
global $wpdb;
$compromisos_activos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, pm_tipo.meta_value as tipo, pm_duracion.meta_value as duracion,
            pm_dias.meta_value as dias_cumplidos, pm_fecha_fin.meta_value as fecha_fin
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '_es_estado'
     LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_es_tipo'
     LEFT JOIN {$wpdb->postmeta} pm_duracion ON p.ID = pm_duracion.post_id AND pm_duracion.meta_key = '_es_duracion_dias'
     LEFT JOIN {$wpdb->postmeta} pm_dias ON p.ID = pm_dias.post_id AND pm_dias.meta_key = '_es_dias_cumplidos'
     LEFT JOIN {$wpdb->postmeta} pm_fecha_fin ON p.ID = pm_fecha_fin.post_id AND pm_fecha_fin.meta_key = '_es_fecha_fin'
     WHERE p.post_type = 'es_compromiso'
       AND p.post_author = %d
       AND pm_estado.meta_value = 'activo'
     ORDER BY p.post_date DESC",
    $user_id
));
?>

<div class="es-container">
    <header class="es-header">
        <h2><?php esc_html_e('Compromisos de Suficiencia', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Pequeños compromisos conscientes que transforman nuestra relación con el consumo', 'flavor-platform'); ?></p>
    </header>

    <?php if ($compromisos_activos) : ?>
    <!-- Compromisos activos -->
    <section class="es-mis-compromisos">
        <h3><?php esc_html_e('Mis compromisos activos', 'flavor-platform'); ?></h3>

        <?php foreach ($compromisos_activos as $compromiso) :
            $tipo_data = $tipos_compromiso[$compromiso->tipo] ?? ['nombre' => $compromiso->post_title, 'icono' => 'dashicons-yes'];
            $progreso = $compromiso->duracion > 0 ? ($compromiso->dias_cumplidos / $compromiso->duracion) * 100 : 0;
            $dias_restantes = max(0, (strtotime($compromiso->fecha_fin) - time()) / 86400);
        ?>
        <div class="es-compromiso-activo" data-duracion="<?php echo esc_attr($compromiso->duracion); ?>">
            <div style="width: 48px; height: 48px; background: var(--es-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>" style="color: var(--es-primary);"></span>
            </div>

            <div class="es-compromiso-activo__progreso">
                <div class="es-compromiso-activo__header">
                    <span class="es-compromiso-activo__nombre"><?php echo esc_html($tipo_data['nombre']); ?></span>
                    <span class="es-compromiso-activo__dias">
                        <span class="es-dias-cumplidos"><?php echo esc_html($compromiso->dias_cumplidos); ?></span> / <?php echo esc_html($compromiso->duracion); ?> días
                    </span>
                </div>
                <div class="es-progreso-bar">
                    <div class="es-progreso-bar__fill" data-progreso="<?php echo esc_attr($progreso); ?>" style="width: <?php echo esc_attr($progreso); ?>%"></div>
                </div>
                <?php if ($dias_restantes > 0) : ?>
                <small style="color: var(--es-text-light);">
                    <?php printf(esc_html__('Quedan %d días', 'flavor-platform'), ceil($dias_restantes)); ?>
                </small>
                <?php endif; ?>
            </div>

            <div class="es-compromiso-activo__accion">
                <button class="es-btn es-btn--primary es-btn--small es-btn-registrar-practica" data-compromiso="<?php echo esc_attr($compromiso->ID); ?>">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Hoy lo cumplí', 'flavor-platform'); ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- Tipos de compromisos -->
    <section style="margin-top: 2rem;">
        <h3><?php esc_html_e('Hacer un nuevo compromiso', 'flavor-platform'); ?></h3>
        <p style="color: var(--es-text-light); margin-bottom: 1.5rem;">
            <?php esc_html_e('Selecciona un tipo de compromiso y define su duración', 'flavor-platform'); ?>
        </p>

        <form class="es-form-compromiso">
            <input type="hidden" id="es-compromiso-tipo" name="tipo" value="">

            <div class="es-compromisos-grid">
                <?php foreach ($tipos_compromiso as $tipo_id => $tipo_data) : ?>
                <div class="es-compromiso-card" data-tipo="<?php echo esc_attr($tipo_id); ?>">
                    <div class="es-compromiso-card__icono">
                        <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                    </div>
                    <h4 class="es-compromiso-card__nombre"><?php echo esc_html($tipo_data['nombre']); ?></h4>
                    <p class="es-compromiso-card__descripcion"><?php echo esc_html($tipo_data['descripcion']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="es-form-compromiso-detalle" style="display: none; margin-top: 2rem; padding: 1.5rem; background: var(--es-bg-card); border-radius: var(--es-radius);">
                <div class="es-form-grupo">
                    <label for="es-duracion"><?php esc_html_e('Duración del compromiso', 'flavor-platform'); ?></label>
                    <select name="duracion" id="es-duracion">
                        <option value="7"><?php esc_html_e('1 semana', 'flavor-platform'); ?></option>
                        <option value="14"><?php esc_html_e('2 semanas', 'flavor-platform'); ?></option>
                        <option value="30" selected><?php esc_html_e('1 mes', 'flavor-platform'); ?></option>
                        <option value="90"><?php esc_html_e('3 meses', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="es-form-grupo">
                    <label for="es-descripcion"><?php esc_html_e('Detalles de tu compromiso (opcional)', 'flavor-platform'); ?></label>
                    <textarea name="descripcion" id="es-descripcion" rows="3"
                              placeholder="<?php esc_attr_e('Describe cómo vas a aplicar este compromiso...', 'flavor-platform'); ?>"></textarea>
                </div>

                <button type="submit" class="es-btn es-btn--primary">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Hacer este compromiso', 'flavor-platform'); ?>
                </button>
            </div>
        </form>
    </section>
</div>
