<?php
/**
 * Vista Admin: Configuración de Pagos para Grupos de Consumo
 *
 * Panel para configurar las pasarelas de pago disponibles.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Views
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$manager = Flavor_GC_Payment_Manager::get_instance();
$gateways = $manager->get_all_gateways();
$settings = get_option('flavor_gc_payment_settings', []);
$active_tab = sanitize_key($_GET['tab'] ?? 'general');

// Guardar configuración si se envía el formulario
if (isset($_POST['gc_payment_settings_nonce']) && wp_verify_nonce($_POST['gc_payment_settings_nonce'], 'gc_payment_settings')) {
    $new_settings = [];

    foreach ($gateways as $gateway_id => $gateway) {
        if (isset($_POST['gateway'][$gateway_id])) {
            $gateway_data = $_POST['gateway'][$gateway_id];
            $validated = $gateway->validate_settings($gateway_data);

            if (!is_wp_error($validated)) {
                $new_settings[$gateway_id] = $validated;
            }
        }
    }

    update_option('flavor_gc_payment_settings', $new_settings);
    $settings = $new_settings;

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
}
?>

<div class="wrap gc-payment-settings">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=grupos-consumo'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-store" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Grupos de Consumo', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Configuración de Pagos', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1><?php esc_html_e('Configuración de Pagos - Grupos de Consumo', 'flavor-chat-ia'); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(add_query_arg('tab', 'general')); ?>"
           class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General', 'flavor-chat-ia'); ?>
        </a>
        <?php foreach ($gateways as $gateway_id => $gateway) : ?>
        <a href="<?php echo esc_url(add_query_arg('tab', $gateway_id)); ?>"
           class="nav-tab <?php echo $active_tab === $gateway_id ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html($gateway->get_name()); ?>
            <?php if ($gateway->is_enabled()) : ?>
            <span class="gc-gateway-badge gc-badge-active"><?php esc_html_e('Activa', 'flavor-chat-ia'); ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'transactions')); ?>"
           class="nav-tab <?php echo $active_tab === 'transactions' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Transacciones', 'flavor-chat-ia'); ?>
        </a>
    </nav>

    <form method="post" class="gc-payment-form">
        <?php wp_nonce_field('gc_payment_settings', 'gc_payment_settings_nonce'); ?>

        <?php if ($active_tab === 'general') : ?>
        <!-- Tab General -->
        <div class="gc-settings-section">
            <h2><?php esc_html_e('Configuración General', 'flavor-chat-ia'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Pasarelas activas', 'flavor-chat-ia'); ?></th>
                    <td>
                        <ul class="gc-gateways-list">
                            <?php foreach ($gateways as $gateway_id => $gateway) : ?>
                            <li class="gc-gateway-item <?php echo $gateway->is_enabled() ? 'gc-enabled' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($gateway->get_icon()); ?>"></span>
                                <strong><?php echo esc_html($gateway->get_name()); ?></strong>
                                <span class="gc-gateway-status">
                                    <?php if ($gateway->is_enabled()) : ?>
                                    <span class="gc-status-active"><?php esc_html_e('Activa', 'flavor-chat-ia'); ?></span>
                                    <?php elseif (!$gateway->can_activate()) : ?>
                                    <span class="gc-status-warning"><?php esc_html_e('Requiere configuración', 'flavor-chat-ia'); ?></span>
                                    <?php else : ?>
                                    <span class="gc-status-inactive"><?php esc_html_e('Inactiva', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Moneda', 'flavor-chat-ia'); ?></th>
                    <td>
                        <select name="gateway[general][currency]" id="gc_currency">
                            <option value="EUR" selected>EUR (€)</option>
                        </select>
                        <p class="description"><?php esc_html_e('Moneda utilizada para los pagos.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Estadísticas de Pagos', 'flavor-chat-ia'); ?></h3>
            <?php
            $stats = $manager->get_transaction_stats();
            ?>
            <div class="gc-payment-stats">
                <div class="gc-stat-card gc-stat-success">
                    <div class="gc-stat-value"><?php echo number_format($stats['completado']['total'], 2, ',', '.'); ?> €</div>
                    <div class="gc-stat-label"><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></div>
                    <div class="gc-stat-count"><?php echo (int) $stats['completado']['cantidad']; ?> <?php esc_html_e('transacciones', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="gc-stat-card gc-stat-pending">
                    <div class="gc-stat-value"><?php echo number_format($stats['pendiente']['total'], 2, ',', '.'); ?> €</div>
                    <div class="gc-stat-label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></div>
                    <div class="gc-stat-count"><?php echo (int) $stats['pendiente']['cantidad']; ?> <?php esc_html_e('transacciones', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="gc-stat-card gc-stat-refund">
                    <div class="gc-stat-value"><?php echo number_format($stats['reembolsado']['total'], 2, ',', '.'); ?> €</div>
                    <div class="gc-stat-label"><?php esc_html_e('Reembolsados', 'flavor-chat-ia'); ?></div>
                    <div class="gc-stat-count"><?php echo (int) $stats['reembolsado']['cantidad']; ?> <?php esc_html_e('transacciones', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>

        <?php elseif ($active_tab === 'transactions') : ?>
        <!-- Tab Transacciones -->
        <div class="gc-settings-section">
            <h2><?php esc_html_e('Historial de Transacciones', 'flavor-chat-ia'); ?></h2>
            <?php
            global $wpdb;
            $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';
            $page = max(1, absint($_GET['paged'] ?? 1));
            $per_page = 20;
            $offset = ($page - 1) * $per_page;

            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_pagos}");
            $transacciones = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, u.display_name as usuario_nombre
                 FROM {$tabla_pagos} p
                 LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
                 ORDER BY p.fecha_creacion DESC
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ));
            ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Pasarela', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transacciones)) : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e('No hay transacciones registradas.', 'flavor-chat-ia'); ?></td>
                    </tr>
                    <?php else : ?>
                    <?php foreach ($transacciones as $tx) : ?>
                    <tr>
                        <td>#<?php echo esc_html($tx->id); ?></td>
                        <td><?php echo esc_html($tx->usuario_nombre ?: __('Usuario eliminado', 'flavor-chat-ia')); ?></td>
                        <td><?php echo esc_html(ucfirst($tx->pasarela)); ?></td>
                        <td><?php echo number_format($tx->importe, 2, ',', '.'); ?> <?php echo esc_html($tx->moneda); ?></td>
                        <td>
                            <span class="gc-status-badge gc-status-<?php echo esc_attr(Flavor_GC_Payment_Gateway::get_status_color($tx->estado)); ?>">
                                <?php echo esc_html(Flavor_GC_Payment_Gateway::get_status_label($tx->estado)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tx->fecha_creacion))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total > $per_page) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $page,
                        'total' => ceil($total / $per_page),
                    ]);
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php else : ?>
        <!-- Tab de pasarela específica -->
        <?php
        $gateway = $gateways[$active_tab] ?? null;
        if ($gateway) :
            $gateway_settings = $settings[$active_tab] ?? [];
            $fields = $gateway->get_admin_fields();
        ?>
        <div class="gc-settings-section">
            <h2><?php echo esc_html($gateway->get_name()); ?></h2>
            <p class="gc-gateway-description"><?php echo esc_html($gateway->get_description()); ?></p>

            <?php if (!$gateway->can_activate() && !empty($gateway_settings['enabled'])) : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('Esta pasarela está habilitada pero no puede activarse. Verifica la configuración.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>

            <table class="form-table">
                <?php foreach ($fields as $field) : ?>
                <tr>
                    <th scope="row">
                        <label for="gc_<?php echo esc_attr($active_tab); ?>_<?php echo esc_attr($field['id']); ?>">
                            <?php echo esc_html($field['label']); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        $field_name = "gateway[{$active_tab}][{$field['id']}]";
                        $field_id = "gc_{$active_tab}_{$field['id']}";
                        $field_value = $gateway_settings[$field['id']] ?? ($field['default'] ?? '');

                        switch ($field['type']) {
                            case 'checkbox':
                                ?>
                                <label>
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($field_name); ?>"
                                           id="<?php echo esc_attr($field_id); ?>"
                                           value="1"
                                           <?php checked($field_value, '1'); ?>>
                                    <?php if (!empty($field['checkbox_label'])) echo esc_html($field['checkbox_label']); ?>
                                </label>
                                <?php
                                break;

                            case 'textarea':
                                ?>
                                <textarea name="<?php echo esc_attr($field_name); ?>"
                                          id="<?php echo esc_attr($field_id); ?>"
                                          rows="4"
                                          class="large-text"><?php echo esc_textarea($field_value); ?></textarea>
                                <?php
                                break;

                            case 'password':
                                ?>
                                <input type="password"
                                       name="<?php echo esc_attr($field_name); ?>"
                                       id="<?php echo esc_attr($field_id); ?>"
                                       value="<?php echo esc_attr($field_value); ?>"
                                       class="regular-text"
                                       autocomplete="off">
                                <?php
                                break;

                            case 'select':
                                ?>
                                <select name="<?php echo esc_attr($field_name); ?>"
                                        id="<?php echo esc_attr($field_id); ?>">
                                    <?php foreach ($field['options'] as $opt_value => $opt_label) : ?>
                                    <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($field_value, $opt_value); ?>>
                                        <?php echo esc_html($opt_label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                break;

                            case 'notice':
                                $level = $field['level'] ?? 'info';
                                ?>
                                <div class="notice notice-<?php echo esc_attr($level); ?> inline">
                                    <p><?php echo esc_html($field['content']); ?></p>
                                </div>
                                <?php
                                break;

                            default: // text
                                ?>
                                <input type="text"
                                       name="<?php echo esc_attr($field_name); ?>"
                                       id="<?php echo esc_attr($field_id); ?>"
                                       value="<?php echo esc_attr($field_value); ?>"
                                       class="regular-text">
                                <?php
                        }

                        if (!empty($field['description'])) :
                        ?>
                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($active_tab !== 'transactions') : ?>
        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?>
            </button>
        </p>
        <?php endif; ?>
    </form>
</div>

<style>
.gc-payment-settings .nav-tab .gc-gateway-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
    vertical-align: middle;
}
.gc-badge-active {
    background: #46b450;
    color: #fff;
}
.gc-gateways-list {
    margin: 0;
    padding: 0;
    list-style: none;
}
.gc-gateway-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f7f7f7;
    border-radius: 4px;
    margin-bottom: 5px;
}
.gc-gateway-item.gc-enabled {
    background: #e7f5e9;
}
.gc-gateway-item .dashicons {
    color: #666;
}
.gc-gateway-status {
    margin-left: auto;
}
.gc-status-active { color: #46b450; font-weight: 600; }
.gc-status-warning { color: #ffb900; }
.gc-status-inactive { color: #999; }
.gc-payment-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 15px;
}
.gc-stat-card {
    flex: 1;
    min-width: 150px;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}
.gc-stat-success { background: #e7f5e9; }
.gc-stat-pending { background: #fff3cd; }
.gc-stat-refund { background: #f8d7da; }
.gc-stat-value {
    font-size: 24px;
    font-weight: 700;
}
.gc-stat-label {
    font-size: 14px;
    color: #666;
}
.gc-stat-count {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}
.gc-status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.gc-status-success { background: #e7f5e9; color: #1e7e34; }
.gc-status-warning { background: #fff3cd; color: #856404; }
.gc-status-danger { background: #f8d7da; color: #721c24; }
.gc-status-info { background: #d1ecf1; color: #0c5460; }
.gc-status-secondary { background: #e9ecef; color: #495057; }
.gc-gateway-description {
    color: #666;
    font-style: italic;
    margin-bottom: 20px;
}
</style>
