<?php
/**
 * Template: Panel del Organizador de Talleres
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$usuario_id = get_current_user_id();
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';
$tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

// Obtener talleres del organizador
$mis_talleres = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*,
            (SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE taller_id = t.id AND estado = 'confirmada') as inscritos
     FROM {$tabla_talleres} t
     WHERE t.organizador_id = %d
     ORDER BY t.created_at DESC",
    $usuario_id
));

// Estadisticas
$total_talleres = count($mis_talleres);
$total_inscritos = array_sum(array_column($mis_talleres, 'inscritos'));
$talleres_activos = count(array_filter($mis_talleres, function($t) { return $t->estado === 'activo'; }));
?>

<div class="talleres-organizador-panel">
    <h2><?php _e('Panel del Organizador', 'flavor-platform'); ?></h2>

    <div class="talleres-stats-grid">
        <div class="talleres-stat-card">
            <span class="talleres-stat-numero"><?php echo esc_html($total_talleres); ?></span>
            <span class="talleres-stat-label"><?php _e('Talleres creados', 'flavor-platform'); ?></span>
        </div>
        <div class="talleres-stat-card">
            <span class="talleres-stat-numero"><?php echo esc_html($talleres_activos); ?></span>
            <span class="talleres-stat-label"><?php _e('Talleres activos', 'flavor-platform'); ?></span>
        </div>
        <div class="talleres-stat-card">
            <span class="talleres-stat-numero"><?php echo esc_html($total_inscritos); ?></span>
            <span class="talleres-stat-label"><?php _e('Total inscritos', 'flavor-platform'); ?></span>
        </div>
    </div>

    <div class="talleres-panel-acciones">
        <a href="<?php echo esc_url(add_query_arg('accion', 'nuevo', home_url('/organizar-taller/'))); ?>" class="talleres-btn talleres-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Crear nuevo taller', 'flavor-platform'); ?>
        </a>
    </div>

    <h3><?php _e('Mis Talleres', 'flavor-platform'); ?></h3>

    <?php if (empty($mis_talleres)): ?>
    <div class="talleres-empty">
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <p><?php _e('Aun no has creado ningun taller.', 'flavor-platform'); ?></p>
    </div>
    <?php else: ?>
    <div class="talleres-tabla-container">
        <table class="talleres-tabla">
            <thead>
                <tr>
                    <th><?php _e('Taller', 'flavor-platform'); ?></th>
                    <th><?php _e('Categoria', 'flavor-platform'); ?></th>
                    <th><?php _e('Inscritos', 'flavor-platform'); ?></th>
                    <th><?php _e('Estado', 'flavor-platform'); ?></th>
                    <th><?php _e('Acciones', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mis_talleres as $taller): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($taller->titulo); ?></strong>
                        <br>
                        <small><?php echo esc_html(date_i18n('d/m/Y', strtotime($taller->created_at))); ?></small>
                    </td>
                    <td><?php echo esc_html(ucfirst($taller->categoria ?? '-')); ?></td>
                    <td>
                        <span class="talleres-inscritos-count">
                            <?php echo esc_html($taller->inscritos); ?>/<?php echo esc_html($taller->capacidad ?? '?'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="talleres-badge talleres-estado-<?php echo esc_attr($taller->estado ?? 'borrador'); ?>">
                            <?php echo esc_html(ucfirst($taller->estado ?? 'borrador')); ?>
                        </span>
                    </td>
                    <td class="talleres-acciones">
                        <a href="<?php echo esc_url(add_query_arg(['accion' => 'editar', 'id' => $taller->id], home_url('/organizar-taller/'))); ?>" class="talleres-btn-icon" title="<?php esc_attr_e('Editar', 'flavor-platform'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(['accion' => 'inscritos', 'id' => $taller->id], home_url('/organizar-taller/'))); ?>" class="talleres-btn-icon" title="<?php esc_attr_e('Ver inscritos', 'flavor-platform'); ?>">
                            <span class="dashicons dashicons-groups"></span>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('taller_id', $taller->id, home_url('/taller/'))); ?>" class="talleres-btn-icon" title="<?php esc_attr_e('Ver', 'flavor-platform'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
