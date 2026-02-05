<?php
/**
 * Vista: Listas de suscriptores
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$listas = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_listas ORDER BY nombre ASC"
);
?>

<div class="wrap em-listas">
    <h1>
        <?php _e('Listas de suscriptores', 'flavor-chat-ia'); ?>
        <button type="button" class="page-title-action em-btn-nueva-lista">
            <?php _e('Nueva lista', 'flavor-chat-ia'); ?>
        </button>
    </h1>

    <p class="description">
        <?php _e('Organiza tus suscriptores en diferentes listas según sus intereses o características.', 'flavor-chat-ia'); ?>
    </p>

    <?php if (empty($listas)): ?>
        <div class="em-empty-state">
            <span class="dashicons dashicons-list-view"></span>
            <h2><?php _e('No hay listas', 'flavor-chat-ia'); ?></h2>
            <p><?php _e('Crea tu primera lista para organizar tus suscriptores.', 'flavor-chat-ia'); ?></p>
            <button type="button" class="button button-primary button-hero em-btn-nueva-lista">
                <?php _e('Crear lista', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php else: ?>
        <div class="em-listas-grid">
            <?php foreach ($listas as $lista): ?>
                <div class="em-lista-card" data-id="<?php echo esc_attr($lista->id); ?>">
                    <div class="em-lista-header">
                        <h3><?php echo esc_html($lista->nombre); ?></h3>
                        <span class="em-lista-tipo em-tipo-<?php echo esc_attr($lista->tipo); ?>">
                            <?php echo esc_html(ucfirst($lista->tipo)); ?>
                        </span>
                    </div>

                    <?php if ($lista->descripcion): ?>
                        <p class="em-lista-descripcion"><?php echo esc_html($lista->descripcion); ?></p>
                    <?php endif; ?>

                    <div class="em-lista-stats">
                        <div class="em-stat">
                            <span class="em-stat-valor"><?php echo number_format($lista->total_suscriptores); ?></span>
                            <span class="em-stat-label"><?php _e('Suscriptores', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="em-stat">
                            <span class="em-stat-valor">
                                <?php echo $lista->doble_optin ? __('Sí', 'flavor-chat-ia') : __('No', 'flavor-chat-ia'); ?>
                            </span>
                            <span class="em-stat-label"><?php _e('Doble opt-in', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>

                    <div class="em-lista-acciones">
                        <a href="<?php echo admin_url('admin.php?page=flavor-em-suscriptores&lista=' . $lista->id); ?>" class="button button-small">
                            <span class="dashicons dashicons-groups"></span>
                            <?php _e('Ver suscriptores', 'flavor-chat-ia'); ?>
                        </a>
                        <button type="button" class="button button-small em-btn-editar-lista" data-id="<?php echo esc_attr($lista->id); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <?php if ($lista->slug !== 'newsletter-principal'): ?>
                            <button type="button" class="button button-small em-btn-eliminar-lista" data-id="<?php echo esc_attr($lista->id); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="em-lista-shortcode">
                        <code>[em_formulario_suscripcion lista="<?php echo esc_attr($lista->slug); ?>"]</code>
                        <button type="button" class="em-copiar-shortcode" data-shortcode='[em_formulario_suscripcion lista="<?php echo esc_attr($lista->slug); ?>"]'>
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de lista -->
<div class="em-modal" id="em-modal-lista" style="display:none;">
    <div class="em-modal-content">
        <h3 id="em-modal-lista-titulo"><?php _e('Nueva lista', 'flavor-chat-ia'); ?></h3>
        <form id="em-form-lista">
            <input type="hidden" name="lista_id" id="em-lista-id" value="">

            <div class="em-form-section">
                <label for="em-lista-nombre"><?php _e('Nombre de la lista', 'flavor-chat-ia'); ?></label>
                <input type="text" id="em-lista-nombre" name="nombre" required>
            </div>

            <div class="em-form-section">
                <label for="em-lista-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                <textarea id="em-lista-descripcion" name="descripcion" rows="3"></textarea>
            </div>

            <div class="em-form-section">
                <label for="em-lista-tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
                <select id="em-lista-tipo" name="tipo">
                    <option value="newsletter"><?php _e('Newsletter', 'flavor-chat-ia'); ?></option>
                    <option value="segmento"><?php _e('Segmento', 'flavor-chat-ia'); ?></option>
                    <option value="automatica"><?php _e('Automática', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="em-form-section">
                <label>
                    <input type="checkbox" name="doble_optin" id="em-lista-doble-optin" value="1" checked>
                    <?php _e('Requerir confirmación por email (doble opt-in)', 'flavor-chat-ia'); ?>
                </label>
                <p class="description"><?php _e('Recomendado para cumplir con GDPR y mejorar la calidad de la lista.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="em-modal-actions">
                <button type="button" class="button em-modal-close"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Guardar', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>
