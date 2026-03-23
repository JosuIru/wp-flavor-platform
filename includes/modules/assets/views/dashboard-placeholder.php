<?php
/**
 * Template Placeholder para Dashboards de Módulos
 *
 * Este template muestra un dashboard básico mientras el módulo
 * está en desarrollo o pendiente de implementación completa.
 *
 * Variables esperadas:
 * - $module_name: Nombre del módulo
 * - $module_icon: Clase de dashicon
 * - $module_color: Color hexadecimal del módulo
 * - $module_description: Descripción breve del módulo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

// Valores por defecto
$module_name = $module_name ?? __('Módulo', 'flavor-chat-ia');
$module_icon = $module_icon ?? 'dashicons-admin-generic';
$module_color = $module_color ?? '#6366f1';
$module_description = $module_description ?? '';
?>

<div class="flavor-module-dashboard flavor-module-placeholder">
    <style>
        .flavor-module-placeholder {
            padding: 40px 20px;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        .flavor-module-placeholder .module-icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            opacity: 0.9;
        }
        .flavor-module-placeholder .module-icon-container .dashicons {
            font-size: 40px;
            width: 40px;
            height: 40px;
            color: #fff;
        }
        .flavor-module-placeholder h2 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 12px;
            color: #1e293b;
        }
        .flavor-module-placeholder .module-description {
            font-size: 15px;
            color: #64748b;
            margin: 0 0 32px;
            line-height: 1.6;
        }
        .flavor-module-placeholder .placeholder-message {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .flavor-module-placeholder .placeholder-message .dashicons-info {
            color: #3b82f6;
            font-size: 24px;
            width: 24px;
            height: 24px;
            margin-bottom: 12px;
        }
        .flavor-module-placeholder .placeholder-message p {
            margin: 0;
            color: #475569;
            font-size: 14px;
        }
        .flavor-module-placeholder .module-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fef3c7;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .flavor-module-placeholder .module-status .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
    </style>

    <div class="module-icon-container" style="background-color: <?php echo esc_attr($module_color); ?>;">
        <span class="dashicons <?php echo esc_attr($module_icon); ?>"></span>
    </div>

    <h2><?php echo esc_html($module_name); ?></h2>

    <?php if ($module_description): ?>
        <p class="module-description"><?php echo esc_html($module_description); ?></p>
    <?php endif; ?>

    <div class="placeholder-message">
        <span class="dashicons dashicons-info"></span>
        <p>
            <?php _e('Este módulo está activo en tu plataforma. El panel de administración detallado estará disponible próximamente.', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <div class="module-status">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php _e('Módulo activo', 'flavor-chat-ia'); ?>
    </div>
</div>
