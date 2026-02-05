<?php
/**
 * Template: Preferencias de suscriptor
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener listas del suscriptor
$listas_suscriptor = $wpdb->get_results($wpdb->prepare(
    "SELECT l.*, sl.estado as estado_suscripcion
     FROM {$wpdb->prefix}flavor_em_listas l
     INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON l.id = sl.lista_id
     WHERE sl.suscriptor_id = %d AND l.activa = 1",
    $suscriptor->id
));

// Obtener todas las listas públicas
$todas_listas = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE activa = 1 ORDER BY nombre ASC"
);

$listas_activas_ids = array_column(array_filter($listas_suscriptor, function($l) {
    return $l->estado_suscripcion === 'activo';
}), 'id');
?>

<div class="em-preferencias-wrapper">
    <div class="em-preferencias-header">
        <h2><?php _e('Preferencias de email', 'flavor-chat-ia'); ?></h2>
        <p><?php printf(__('Gestiona tus suscripciones para %s', 'flavor-chat-ia'), '<strong>' . esc_html($suscriptor->email) . '</strong>'); ?></p>
    </div>

    <form id="em-form-preferencias" class="em-preferencias-form" data-token="<?php echo esc_attr($token); ?>">
        <div class="em-preferencias-section">
            <h3><?php _e('Listas de correo', 'flavor-chat-ia'); ?></h3>
            <p class="em-descripcion"><?php _e('Selecciona las listas de las que quieres recibir emails.', 'flavor-chat-ia'); ?></p>

            <div class="em-listas-opciones">
                <?php foreach ($todas_listas as $lista): ?>
                    <label class="em-lista-opcion">
                        <input type="checkbox" name="listas[]" value="<?php echo esc_attr($lista->id); ?>"
                            <?php checked(in_array($lista->id, $listas_activas_ids)); ?>>
                        <span class="em-lista-info">
                            <strong><?php echo esc_html($lista->nombre); ?></strong>
                            <?php if ($lista->descripcion): ?>
                                <span class="em-lista-desc"><?php echo esc_html($lista->descripcion); ?></span>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="em-preferencias-acciones">
            <button type="submit" class="em-btn em-btn-primary">
                <?php _e('Guardar preferencias', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <div class="em-form-mensaje" style="display:none;"></div>
    </form>

    <div class="em-preferencias-baja">
        <hr>
        <p><?php _e('¿Quieres darte de baja completamente?', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(add_query_arg('token', $token, home_url('/baja-newsletter/'))); ?>" class="em-link-baja">
            <?php _e('Darme de baja de todas las listas', 'flavor-chat-ia'); ?>
        </a>
    </div>
</div>
