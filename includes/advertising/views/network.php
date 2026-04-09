<?php
/**
 * Vista de Gestión de Red Global de Anuncios
 *
 * @package FlavorChatIA
 * @subpackage Advertising
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

// Procesar formulario de conexión a la red
if (isset($_POST['flavor_network_connect']) && check_admin_referer('flavor_network_connect_action', 'flavor_network_connect_nonce')) {
    $api_key_red = sanitize_text_field($_POST['api_key_red']);
    $nombre_sitio_red = sanitize_text_field($_POST['nombre_sitio_red']);
    $url_sitio_red = esc_url_raw($_POST['url_sitio_red']);

    update_option('flavor_advertising_network_api_key', $api_key_red);
    update_option('flavor_advertising_network_site_name', $nombre_sitio_red);
    update_option('flavor_advertising_network_site_url', $url_sitio_red);
    update_option('flavor_advertising_network_connected', true);

    echo '<div class="notice notice-success"><p>' . esc_html__('Conectado a la red global exitosamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
}

// Procesar formulario de configuración de red
if (isset($_POST['flavor_network_settings']) && check_admin_referer('flavor_network_settings_action', 'flavor_network_settings_nonce')) {
    $permitir_anuncios_globales = isset($_POST['permitir_anuncios_globales']) ? 1 : 0;
    $compartir_anuncios_red = isset($_POST['compartir_anuncios_red']) ? 1 : 0;
    $porcentaje_comision_red = intval($_POST['porcentaje_comision_red']);

    update_option('flavor_advertising_allow_global_ads', $permitir_anuncios_globales);
    update_option('flavor_advertising_share_ads', $compartir_anuncios_red);
    update_option('flavor_advertising_network_commission', $porcentaje_comision_red);

    echo '<div class="notice notice-success"><p>' . esc_html__('Configuración de red actualizada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
}

// Procesar desconexión de red
if (isset($_POST['flavor_network_disconnect']) && check_admin_referer('flavor_network_disconnect_action', 'flavor_network_disconnect_nonce')) {
    delete_option('flavor_advertising_network_api_key');
    delete_option('flavor_advertising_network_site_name');
    delete_option('flavor_advertising_network_site_url');
    delete_option('flavor_advertising_network_connected');

    echo '<div class="notice notice-success"><p>' . esc_html__('Desconectado de la red global.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
}

// Verificar estado de conexión
$esta_conectado = get_option('flavor_advertising_network_connected', false);
$api_key_red = get_option('flavor_advertising_network_api_key', '');
$nombre_sitio_red = get_option('flavor_advertising_network_site_name', '');
$url_sitio_red = get_option('flavor_advertising_network_site_url', '');
$id_sitio_red = get_option('flavor_advertising_network_site_id', wp_generate_uuid4());

// Configuración actual
$permitir_anuncios_globales = get_option('flavor_advertising_allow_global_ads', 1);
$compartir_anuncios_red = get_option('flavor_advertising_share_ads', 0);
$porcentaje_comision_red = get_option('flavor_advertising_network_commission', 20);

// Datos simulados de sitios en la red (en producción vendría de API)
$sitios_en_red = [
    [
        'id' => 'site-001',
        'nombre' => 'Comunidad Basabe',
        'url' => 'https://basabe.example',
        'anuncios_compartidos' => 12,
        'ingresos_generados' => 245.80
    ],
    [
        'id' => 'site-002',
        'nombre' => 'Red Solidaria Pamplona',
        'url' => 'https://pamplona.example',
        'anuncios_compartidos' => 8,
        'ingresos_generados' => 156.50
    ],
    [
        'id' => 'site-003',
        'nombre' => 'Cooperativa Local',
        'url' => 'https://cooperativa.example',
        'anuncios_compartidos' => 15,
        'ingresos_generados' => 389.20
    ]
];

// Estadísticas de red
$total_sitios_red = count($sitios_en_red);
$total_anuncios_compartidos = array_sum(array_column($sitios_en_red, 'anuncios_compartidos'));
$total_ingresos_red = array_sum(array_column($sitios_en_red, 'ingresos_generados'));
?>

<div class="wrap">
    <h1><?php echo esc_html__('Red Global de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <!-- Explicación de la red -->
    <div class="card" style="margin: 20px 0; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
        <h2 style="margin-top: 0;"><?php esc_html_e('¿Qué es la Red Global de Anuncios?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('La Red Global de Anuncios conecta múltiples sitios de comunidades para compartir publicidad ética y transparente. Los anunciantes pueden llegar a una audiencia más amplia, mientras que los sitios generan ingresos adicionales mostrando anuncios relevantes de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <ul style="margin-left: 20px;">
            <li><strong><?php esc_html_e('Visibilidad ampliada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Tus anuncios pueden mostrarse en todos los sitios de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><strong><?php esc_html_e('Ingresos compartidos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Gana dinero mostrando anuncios de otros sitios de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><strong><?php esc_html_e('Publicidad ética:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Todos los anuncios cumplen con estándares de transparencia y responsabilidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><strong><?php esc_html_e('Control total:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Tú decides qué anuncios mostrar y qué porcentaje compartir.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
        </ul>
    </div>

    <?php if ($esta_conectado) : ?>
        <!-- Estado: CONECTADO -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2 style="color: #00a32a;"><?php esc_html_e('✓ Conectado a la Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('ID de Sitio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td><code><?php echo esc_html($id_sitio_red); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Nombre del Sitio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td><strong><?php echo esc_html($nombre_sitio_red); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('URL:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td><?php echo esc_html($url_sitio_red); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('API Key:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td><code><?php echo esc_html(substr($api_key_red, 0, 20) . '...'); ?></code></td>
                </tr>
            </table>

            <form method="post" action="">
                <?php wp_nonce_field('flavor_network_disconnect_action', 'flavor_network_disconnect_nonce'); ?>
                <p>
                    <button type="submit" name="flavor_network_disconnect" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que deseas desconectarte de la red? Perderás acceso a anuncios compartidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')">
                        <?php esc_html_e('Desconectar de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Estadísticas de red -->
        <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                    <?php esc_html_e('Sitios en la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                    <?php echo esc_html($total_sitios_red); ?>
                </p>
            </div>

            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                    <?php esc_html_e('Anuncios Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                    <?php echo esc_html($total_anuncios_compartidos); ?>
                </p>
            </div>

            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                    <?php esc_html_e('Ingresos de Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                    €<?php echo esc_html(number_format($total_ingresos_red, 2, ',', '.')); ?>
                </p>
            </div>
        </div>

        <!-- Configuración de visibilidad -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Configuración de Participación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <form method="post" action="">
                <?php wp_nonce_field('flavor_network_settings_action', 'flavor_network_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Anuncios Globales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="permitir_anuncios_globales" value="1" <?php checked($permitir_anuncios_globales, 1); ?>>
                                <?php esc_html_e('Permitir mostrar anuncios de otros sitios de la red en mi sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Activa esta opción para generar ingresos mostrando anuncios de la red global.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Compartir Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="compartir_anuncios_red" value="1" <?php checked($compartir_anuncios_red, 1); ?>>
                                <?php esc_html_e('Compartir mis anuncios con otros sitios de la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Permite que tus anuncios se muestren en otros sitios de la red para mayor alcance.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Comisión de Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="porcentaje_comision_red" style="width: 150px;">
                                <?php for ($porcentaje_comision = 10; $porcentaje_comision <= 50; $porcentaje_comision += 5) : ?>
                                    <option value="<?php echo esc_attr($porcentaje_comision); ?>" <?php selected($porcentaje_comision_red, $porcentaje_comision); ?>>
                                        <?php echo esc_html($porcentaje_comision); ?>%
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Porcentaje de comisión que la red toma de los ingresos por anuncios compartidos. El resto se distribuye entre el sitio que muestra el anuncio y el sitio que lo publicó.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="flavor_network_settings" class="button button-primary">
                        <?php esc_html_e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Lista de sitios en la red -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Sitios en la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nombre del Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('URL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Anuncios Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Ingresos Generados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sitios_en_red as $sitio) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($sitio['nombre']); ?></strong></td>
                            <td><a href="<?php echo esc_url($sitio['url']); ?>" target="_blank"><?php echo esc_html($sitio['url']); ?></a></td>
                            <td><?php echo esc_html($sitio['anuncios_compartidos']); ?></td>
                            <td><strong>€<?php echo esc_html(number_format($sitio['ingresos_generados'], 2, ',', '.')); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else : ?>
        <!-- Estado: NO CONECTADO -->
        <div class="card" style="margin: 20px 0; padding: 20px; border-left: 4px solid #d63638;">
            <h2 style="color: #d63638;"><?php esc_html_e('No estás conectado a la Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Para unirte a la red global de anuncios y comenzar a compartir publicidad con otras comunidades, completa el siguiente formulario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('flavor_network_connect_action', 'flavor_network_connect_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key_red"><?php esc_html_e('API Key de Red *', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <input type="text" id="api_key_red" name="api_key_red" class="regular-text" required>
                            <p class="description">
                                <?php esc_html_e('Solicita tu API Key en el portal de la red global.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <a href="https://network.flavor-advertising.example" target="_blank"><?php esc_html_e('Obtener API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="nombre_sitio_red"><?php esc_html_e('Nombre del Sitio *', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nombre_sitio_red" name="nombre_sitio_red" class="regular-text" value="<?php echo esc_attr(get_bloginfo('name')); ?>" required>
                            <p class="description"><?php esc_html_e('Nombre con el que tu sitio aparecerá en la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="url_sitio_red"><?php esc_html_e('URL del Sitio *', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <input type="url" id="url_sitio_red" name="url_sitio_red" class="regular-text" value="<?php echo esc_url(home_url()); ?>" required>
                            <p class="description"><?php esc_html_e('URL principal de tu sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="flavor_network_connect" class="button button-primary button-large">
                        <?php esc_html_e('Conectar a la Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Beneficios de unirse -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Beneficios de Unirse a la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h3 style="color: #2271b1;">💰 <?php esc_html_e('Ingresos Adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Genera ingresos mostrando anuncios relevantes de otros sitios de la red en tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div>
                    <h3 style="color: #2271b1;">📈 <?php esc_html_e('Mayor Alcance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Amplifica el alcance de tus anuncios mostrándolos en múltiples sitios de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div>
                    <h3 style="color: #2271b1;">🤝 <?php esc_html_e('Comunidad Colaborativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Forma parte de una red de comunidades que comparten valores de transparencia y ética.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div>
                    <h3 style="color: #2271b1;">✅ <?php esc_html_e('Control Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Mantén el control sobre qué anuncios se muestran y cómo se distribuyen los ingresos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
