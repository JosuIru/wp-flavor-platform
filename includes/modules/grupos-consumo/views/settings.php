<?php
/**
 * Vista Admin: Configuración del Módulo de Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Guardar configuración
if (isset($_POST['gc_guardar_config']) && wp_verify_nonce($_POST['gc_config_nonce'], 'gc_guardar_configuracion')) {
    $opciones = [
        'dias_anticipacion_pedido' => absint($_POST['dias_anticipacion_pedido']),
        'hora_cierre_pedidos' => sanitize_text_field($_POST['hora_cierre_pedidos']),
        'permitir_modificar_pedido' => isset($_POST['permitir_modificar_pedido']) ? 1 : 0,
        'horas_limite_modificacion' => absint($_POST['horas_limite_modificacion']),
        'porcentaje_gestion' => floatval($_POST['porcentaje_gestion']),
        'requiere_aprobacion_productores' => isset($_POST['requiere_aprobacion_productores']) ? 1 : 0,
        'notificar_nuevos_productos' => isset($_POST['notificar_nuevos_productos']) ? 1 : 0,
        'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? 1 : 0,
        'whatsapp_phone_id' => sanitize_text_field($_POST['whatsapp_phone_id'] ?? ''),
        'whatsapp_token' => sanitize_text_field($_POST['whatsapp_token'] ?? ''),
        'telegram_enabled' => isset($_POST['telegram_enabled']) ? 1 : 0,
        'telegram_bot_token' => sanitize_text_field($_POST['telegram_bot_token'] ?? ''),
        'email_notificaciones' => sanitize_email($_POST['email_notificaciones'] ?? ''),
    ];

    update_option('flavor_gc_settings', $opciones);

    echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
}

// Obtener configuración actual
$config = get_option('flavor_gc_settings', [
    'dias_anticipacion_pedido' => 7,
    'hora_cierre_pedidos' => '23:59',
    'permitir_modificar_pedido' => 1,
    'horas_limite_modificacion' => 24,
    'porcentaje_gestion' => 5,
    'requiere_aprobacion_productores' => 1,
    'notificar_nuevos_productos' => 1,
    'whatsapp_enabled' => 0,
    'whatsapp_phone_id' => '',
    'whatsapp_token' => '',
    'telegram_enabled' => 0,
    'telegram_bot_token' => '',
    'email_notificaciones' => '',
]);
?>

<div class="wrap gc-admin-settings">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=grupos-consumo'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-store" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Grupos de Consumo', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Configuración', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1><?php _e('Configuración de Grupos de Consumo', 'flavor-chat-ia'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('gc_guardar_configuracion', 'gc_config_nonce'); ?>

        <!-- Configuración General -->
        <div class="gc-settings-section">
            <h2><?php _e('Configuración General', 'flavor-chat-ia'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="dias_anticipacion_pedido"><?php _e('Días de anticipación para pedidos', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="dias_anticipacion_pedido" name="dias_anticipacion_pedido"
                               value="<?php echo esc_attr($config['dias_anticipacion_pedido']); ?>" min="1" max="30" class="small-text">
                        <p class="description"><?php _e('Cuántos días antes de la entrega se abre el ciclo de pedidos.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hora_cierre_pedidos"><?php _e('Hora de cierre de pedidos', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="time" id="hora_cierre_pedidos" name="hora_cierre_pedidos"
                               value="<?php echo esc_attr($config['hora_cierre_pedidos']); ?>">
                        <p class="description"><?php _e('Hora a la que se cierran automáticamente los pedidos.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Permitir modificar pedidos', 'flavor-chat-ia'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="permitir_modificar_pedido" value="1"
                                   <?php checked($config['permitir_modificar_pedido'], 1); ?>>
                            <?php _e('Permitir a los usuarios modificar sus pedidos', 'flavor-chat-ia'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="horas_limite_modificacion"><?php _e('Horas límite para modificar', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="horas_limite_modificacion" name="horas_limite_modificacion"
                               value="<?php echo esc_attr($config['horas_limite_modificacion']); ?>" min="0" max="72" class="small-text">
                        <p class="description"><?php _e('Horas antes del cierre hasta las que se puede modificar un pedido.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="porcentaje_gestion"><?php _e('Porcentaje de gestión', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="porcentaje_gestion" name="porcentaje_gestion"
                               value="<?php echo esc_attr($config['porcentaje_gestion']); ?>" min="0" max="20" step="0.5" class="small-text"> %
                        <p class="description"><?php _e('Porcentaje adicional para gastos de gestión del grupo.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Aprobación de productores', 'flavor-chat-ia'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="requiere_aprobacion_productores" value="1"
                                   <?php checked($config['requiere_aprobacion_productores'], 1); ?>>
                            <?php _e('Requiere aprobación de productores antes de confirmar pedidos', 'flavor-chat-ia'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notificaciones -->
        <div class="gc-settings-section">
            <h2><?php _e('Notificaciones', 'flavor-chat-ia'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Notificar nuevos productos', 'flavor-chat-ia'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="notificar_nuevos_productos" value="1"
                                   <?php checked($config['notificar_nuevos_productos'], 1); ?>>
                            <?php _e('Enviar notificación cuando se añaden nuevos productos', 'flavor-chat-ia'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_notificaciones"><?php _e('Email para notificaciones', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="email_notificaciones" name="email_notificaciones"
                               value="<?php echo esc_attr($config['email_notificaciones']); ?>" class="regular-text">
                        <p class="description"><?php _e('Email del coordinador para recibir notificaciones del sistema.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- WhatsApp Business API -->
        <div class="gc-settings-section">
            <h2><?php _e('WhatsApp Business API', 'flavor-chat-ia'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Habilitar WhatsApp', 'flavor-chat-ia'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="whatsapp_enabled" value="1"
                                   <?php checked($config['whatsapp_enabled'] ?? 0, 1); ?>>
                            <?php _e('Enviar notificaciones por WhatsApp', 'flavor-chat-ia'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="whatsapp_phone_id"><?php _e('Phone Number ID', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="whatsapp_phone_id" name="whatsapp_phone_id"
                               value="<?php echo esc_attr($config['whatsapp_phone_id'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('ID del número de teléfono de WhatsApp Business.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="whatsapp_token"><?php _e('Access Token', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="whatsapp_token" name="whatsapp_token"
                               value="<?php echo esc_attr($config['whatsapp_token'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('Token de acceso de la API de WhatsApp Business.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Telegram Bot -->
        <div class="gc-settings-section">
            <h2><?php _e('Telegram Bot', 'flavor-chat-ia'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Habilitar Telegram', 'flavor-chat-ia'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="telegram_enabled" value="1"
                                   <?php checked($config['telegram_enabled'] ?? 0, 1); ?>>
                            <?php _e('Enviar notificaciones por Telegram', 'flavor-chat-ia'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="telegram_bot_token"><?php _e('Bot Token', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="telegram_bot_token" name="telegram_bot_token"
                               value="<?php echo esc_attr($config['telegram_bot_token'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('Token del bot de Telegram obtenido de @BotFather.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Acciones -->
        <p class="submit">
            <input type="submit" name="gc_guardar_config" class="button button-primary" value="<?php _e('Guardar Cambios', 'flavor-chat-ia'); ?>">
        </p>
    </form>

    <!-- Información del Sistema -->
    <div class="gc-settings-section gc-system-info">
        <h2><?php _e('Información del Sistema', 'flavor-chat-ia'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('Versión del módulo', 'flavor-chat-ia'); ?></strong></td>
                    <td>1.0.0</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Tablas de base de datos', 'flavor-chat-ia'); ?></strong></td>
                    <td>
                        <?php
                        global $wpdb;
                        $tablas = [
                            'flavor_gc_pedidos',
                            'flavor_gc_entregas',
                            'flavor_gc_consolidado',
                            'flavor_gc_notificaciones',
                            'flavor_gc_consumidores',
                            'flavor_gc_suscripciones',
                            'flavor_gc_cestas_tipo',
                            'flavor_gc_lista_compra',
                            'flavor_gc_suscripciones_historial',
                        ];
                        foreach ($tablas as $tabla) {
                            $existe = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$tabla}'") !== null;
                            $icono = $existe ? '✓' : '✗';
                            $color = $existe ? 'green' : 'red';
                            echo "<span style='color:{$color}'>{$icono}</span> {$wpdb->prefix}{$tabla}<br>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Cron programado', 'flavor-chat-ia'); ?></strong></td>
                    <td>
                        <?php
                        $cron_cerrar = wp_next_scheduled('gc_cerrar_ciclos_automatico');
                        $cron_suscripciones = wp_next_scheduled('gc_procesar_suscripciones');

                        if ($cron_cerrar) {
                            echo '✓ Cierre automático: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $cron_cerrar) . '<br>';
                        } else {
                            echo '<span style="color:orange">⚠ Cierre automático no programado</span><br>';
                        }

                        if ($cron_suscripciones) {
                            echo '✓ Procesamiento suscripciones: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $cron_suscripciones);
                        } else {
                            echo '<span style="color:orange">⚠ Suscripciones no programado</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Endpoints API REST', 'flavor-chat-ia'); ?></strong></td>
                    <td>
                        <code><?php echo rest_url('flavor-chat-ia/v1/gc/'); ?></code>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.gc-admin-settings {
    max-width: 800px;
}
.gc-settings-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.gc-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.gc-system-info table td {
    padding: 12px;
}
.gc-system-info table td:first-child {
    width: 200px;
}
</style>
