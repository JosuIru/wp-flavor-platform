<?php
/**
 * Vista del visor de estados
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Chat_Estados
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$avatar = get_avatar_url($user_id, ['size' => 100]);
?>

<div class="flavor-estados-container" data-flavor-estados>
    <div class="flavor-estados-header">
        <h3><?php esc_html_e('Estados', 'flavor-platform'); ?></h3>
        <button class="btn-crear-estado" type="button" title="<?php esc_attr_e('Crear estado', 'flavor-platform'); ?>">
            <span class="dashicons dashicons-plus-alt2"></span>
        </button>
    </div>

    <div class="estados-lista-horizontal">
        <!-- Mi estado -->
        <div class="estado-item mi-estado" data-user-id="<?php echo esc_attr($user_id); ?>">
            <div class="estado-avatar-wrapper">
                <div class="estado-ring"></div>
                <img src="<?php echo esc_url($avatar); ?>" class="estado-avatar" alt="">
            </div>
            <span class="estado-nombre"><?php esc_html_e('Tu estado', 'flavor-platform'); ?></span>
        </div>

        <!-- Los estados de contactos se cargarán por JavaScript -->
        <div class="estados-loading">
            <span class="spinner"></span>
        </div>
    </div>

    <?php if ($atts['mostrar_crear']): ?>
    <div class="estados-acciones">
        <button class="btn-crear-estado-grande" type="button">
            <span class="dashicons dashicons-camera"></span>
            <?php esc_html_e('Crear estado', 'flavor-platform'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<style>
.estados-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.estados-loading .spinner {
    width: 24px;
    height: 24px;
    border: 2px solid var(--estados-border);
    border-top-color: var(--estados-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.btn-crear-estado {
    background: none;
    border: none;
    color: var(--estados-primary);
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
}

.btn-crear-estado:hover {
    opacity: 0.8;
}

.estados-acciones {
    padding: 16px 20px;
    border-top: 1px solid var(--estados-border);
}

.btn-crear-estado-grande {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: var(--estados-primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-crear-estado-grande:hover {
    background: var(--estados-secondary);
}
</style>
