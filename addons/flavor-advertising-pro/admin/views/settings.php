<?php
/**
 * Vista de Configuración del Sistema de Publicidad
 *
 * @package FlavorPlatform
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

// Procesar formulario de configuración
if (isset($_POST['flavor_advertising_settings']) && check_admin_referer('flavor_advertising_settings_action', 'flavor_advertising_settings_nonce')) {

    // Monetización
    $cpm_por_defecto = floatval($_POST['cpm_por_defecto']);
    $cpc_por_defecto = floatval($_POST['cpc_por_defecto']);
    $umbral_minimo_pago = floatval($_POST['umbral_minimo_pago']);

    // Ética y Transparencia
    $mostrar_etiqueta_anuncio = isset($_POST['mostrar_etiqueta_anuncio']) ? 1 : 0;
    $solo_anunciantes_verificados = isset($_POST['solo_anunciantes_verificados']) ? 1 : 0;
    $excluir_categorias_sensibles = isset($_POST['excluir_categorias_sensibles']) ? 1 : 0;
    $categorias_prohibidas = sanitize_textarea_field($_POST['categorias_prohibidas']);

    // Red Global
    $api_key_red = sanitize_text_field($_POST['api_key_red']);
    $url_servidor_central = esc_url_raw($_POST['url_servidor_central']);
    $modo_sandbox = isset($_POST['modo_sandbox']) ? 1 : 0;

    // Distribución de Ingresos
    $porcentaje_sitio_mostrador = intval($_POST['porcentaje_sitio_mostrador']);
    $porcentaje_plataforma_red = intval($_POST['porcentaje_plataforma_red']);
    $porcentaje_proyectos_comunitarios = intval($_POST['porcentaje_proyectos_comunitarios']);

    // Validar que los porcentajes sumen 100
    $suma_porcentajes = $porcentaje_sitio_mostrador + $porcentaje_plataforma_red + $porcentaje_proyectos_comunitarios;
    if ($suma_porcentajes !== 100) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Error: Los porcentajes de distribución deben sumar exactamente 100%.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    } else {
        // Privacidad
        $cumplir_gdpr = isset($_POST['cumplir_gdpr']) ? 1 : 0;
        $no_tracking_sin_consentimiento = isset($_POST['no_tracking_sin_consentimiento']) ? 1 : 0;
        $anonimizar_ips = isset($_POST['anonimizar_ips']) ? 1 : 0;

        // Guardar todas las opciones
        update_option('flavor_advertising_cpm_default', $cpm_por_defecto);
        update_option('flavor_advertising_cpc_default', $cpc_por_defecto);
        update_option('flavor_advertising_min_payout', $umbral_minimo_pago);

        update_option('flavor_advertising_show_ad_label', $mostrar_etiqueta_anuncio);
        update_option('flavor_advertising_verified_only', $solo_anunciantes_verificados);
        update_option('flavor_advertising_exclude_sensitive', $excluir_categorias_sensibles);
        update_option('flavor_advertising_blocked_categories', $categorias_prohibidas);

        update_option('flavor_advertising_network_api_key', $api_key_red);
        update_option('flavor_advertising_central_server_url', $url_servidor_central);
        update_option('flavor_advertising_sandbox_mode', $modo_sandbox);

        update_option('flavor_advertising_revenue_site', $porcentaje_sitio_mostrador);
        update_option('flavor_advertising_revenue_platform', $porcentaje_plataforma_red);
        update_option('flavor_advertising_revenue_community', $porcentaje_proyectos_comunitarios);

        update_option('flavor_advertising_gdpr_compliance', $cumplir_gdpr);
        update_option('flavor_advertising_consent_required', $no_tracking_sin_consentimiento);
        update_option('flavor_advertising_anonymize_ips', $anonimizar_ips);

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }
}

// Obtener valores actuales
$cpm_por_defecto = get_option('flavor_advertising_cpm_default', 2.50);
$cpc_por_defecto = get_option('flavor_advertising_cpc_default', 0.25);
$umbral_minimo_pago = get_option('flavor_advertising_min_payout', 50.00);

$mostrar_etiqueta_anuncio = get_option('flavor_advertising_show_ad_label', 1);
$solo_anunciantes_verificados = get_option('flavor_advertising_verified_only', 0);
$excluir_categorias_sensibles = get_option('flavor_advertising_exclude_sensitive', 1);
$categorias_prohibidas = get_option('flavor_advertising_blocked_categories', "alcohol\napuestas\ntabaco\narmas\ncontenido_adulto");

$api_key_red = get_option('flavor_advertising_network_api_key', '');
$url_servidor_central = get_option('flavor_advertising_central_server_url', 'https://api.flavor-network.example');
$modo_sandbox = get_option('flavor_advertising_sandbox_mode', 1);

$porcentaje_sitio_mostrador = get_option('flavor_advertising_revenue_site', 70);
$porcentaje_plataforma_red = get_option('flavor_advertising_revenue_platform', 30);
$porcentaje_proyectos_comunitarios = get_option('flavor_advertising_revenue_community', 0);

$cumplir_gdpr = get_option('flavor_advertising_gdpr_compliance', 1);
$no_tracking_sin_consentimiento = get_option('flavor_advertising_consent_required', 1);
$anonimizar_ips = get_option('flavor_advertising_anonymize_ips', 1);
?>

<div class="wrap">
    <h1><?php echo esc_html__('Configuración del Sistema de Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('flavor_advertising_settings_action', 'flavor_advertising_settings_nonce'); ?>

        <!-- Monetización -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Configuración de Monetización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cpm_por_defecto"><?php esc_html_e('CPM por Defecto (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="cpm_por_defecto" name="cpm_por_defecto" step="0.01" min="0" value="<?php echo esc_attr($cpm_por_defecto); ?>" required style="width: 150px;">
                        <p class="description">
                            <?php esc_html_e('Coste por mil impresiones. Este será el precio base para anuncios CPM.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cpc_por_defecto"><?php esc_html_e('CPC por Defecto (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="cpc_por_defecto" name="cpc_por_defecto" step="0.01" min="0" value="<?php echo esc_attr($cpc_por_defecto); ?>" required style="width: 150px;">
                        <p class="description">
                            <?php esc_html_e('Coste por click. Este será el precio base para anuncios CPC.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="umbral_minimo_pago"><?php esc_html_e('Umbral Mínimo para Solicitar Pago (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="umbral_minimo_pago" name="umbral_minimo_pago" step="0.01" min="0" value="<?php echo esc_attr($umbral_minimo_pago); ?>" required style="width: 150px;">
                        <p class="description">
                            <?php esc_html_e('Monto mínimo acumulado necesario para poder solicitar un pago.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Ética y Transparencia -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Ética y Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Etiquetado de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="mostrar_etiqueta_anuncio" value="1" <?php checked($mostrar_etiqueta_anuncio, 1); ?>>
                            <?php esc_html_e('Mostrar siempre la etiqueta "Anuncio" en todos los anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Recomendado para cumplir con estándares de transparencia. Los usuarios sabrán claramente qué contenido es publicitario.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Verificación de Anunciantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="solo_anunciantes_verificados" value="1" <?php checked($solo_anunciantes_verificados, 1); ?>>
                            <?php esc_html_e('Permitir solo anunciantes verificados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Los anunciantes deberán pasar un proceso de verificación antes de publicar anuncios. Aumenta la confianza pero puede limitar el inventario.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Categorías Sensibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="excluir_categorias_sensibles" value="1" <?php checked($excluir_categorias_sensibles, 1); ?>>
                            <?php esc_html_e('Excluir automáticamente categorías sensibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('No permitir anuncios de categorías consideradas sensibles (alcohol, apuestas, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="categorias_prohibidas"><?php esc_html_e('Categorías Prohibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <textarea id="categorias_prohibidas" name="categorias_prohibidas" rows="6" class="large-text"><?php echo esc_textarea($categorias_prohibidas); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Una categoría por línea. Los anuncios de estas categorías no se mostrarán en tu sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <br>
                            <strong><?php esc_html_e('Ejemplos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> alcohol, apuestas, tabaco, armas, contenido_adulto, farmaceuticos_sin_receta
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Red Global -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Configuración de Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key_red"><?php esc_html_e('API Key de Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="text" id="api_key_red" name="api_key_red" class="regular-text" value="<?php echo esc_attr($api_key_red); ?>">
                        <p class="description">
                            <?php esc_html_e('Clave API para autenticación con la red global de anuncios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <a href="https://network.flavor-advertising.example" target="_blank"><?php esc_html_e('Obtener API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="url_servidor_central"><?php esc_html_e('URL del Servidor Central', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="url" id="url_servidor_central" name="url_servidor_central" class="regular-text" value="<?php echo esc_attr($url_servidor_central); ?>" required>
                        <p class="description">
                            <?php esc_html_e('URL del servidor central de la red global de anuncios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Modo Sandbox', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="modo_sandbox" value="1" <?php checked($modo_sandbox, 1); ?>>
                            <?php esc_html_e('Activar modo de prueba (sandbox)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('En modo sandbox, las transacciones no son reales y se pueden realizar pruebas sin afectar datos de producción.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Distribución de Ingresos -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Distribución de Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description" style="margin-bottom: 20px;">
                <?php esc_html_e('Configura cómo se distribuyen los ingresos por publicidad. Los porcentajes deben sumar exactamente 100%.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="porcentaje_sitio_mostrador"><?php esc_html_e('% para el Sitio que Muestra el Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="porcentaje_sitio_mostrador" name="porcentaje_sitio_mostrador" min="0" max="100" value="<?php echo esc_attr($porcentaje_sitio_mostrador); ?>" required style="width: 100px;">
                        <span style="margin-left: 5px; font-size: 18px;">%</span>
                        <p class="description">
                            <?php esc_html_e('Porcentaje de ingresos que recibe el sitio donde se muestra el anuncio. Valor por defecto: 70%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="porcentaje_plataforma_red"><?php esc_html_e('% para la Plataforma/Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="porcentaje_plataforma_red" name="porcentaje_plataforma_red" min="0" max="100" value="<?php echo esc_attr($porcentaje_plataforma_red); ?>" required style="width: 100px;">
                        <span style="margin-left: 5px; font-size: 18px;">%</span>
                        <p class="description">
                            <?php esc_html_e('Porcentaje que la plataforma/red toma por gestión y mantenimiento. Valor por defecto: 30%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="porcentaje_proyectos_comunitarios"><?php esc_html_e('% para Proyectos Comunitarios (Opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="porcentaje_proyectos_comunitarios" name="porcentaje_proyectos_comunitarios" min="0" max="100" value="<?php echo esc_attr($porcentaje_proyectos_comunitarios); ?>" required style="width: 100px;">
                        <span style="margin-left: 5px; font-size: 18px;">%</span>
                        <p class="description">
                            <?php esc_html_e('Porcentaje opcional destinado a proyectos comunitarios, sociales o de código abierto. Valor por defecto: 0%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <div id="revenue-distribution-preview" style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                <strong><?php esc_html_e('Vista Previa:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <p id="revenue-preview-text" style="margin: 10px 0 0 0;"></p>
            </div>
        </div>

        <!-- Privacidad -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Configuración de Privacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Cumplimiento GDPR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="cumplir_gdpr" value="1" <?php checked($cumplir_gdpr, 1); ?>>
                            <?php esc_html_e('Cumplir con el Reglamento General de Protección de Datos (GDPR)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Activar protecciones y controles necesarios para cumplir con GDPR. Recomendado para sitios con usuarios en la Unión Europea.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Consentimiento de Tracking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="no_tracking_sin_consentimiento" value="1" <?php checked($no_tracking_sin_consentimiento, 1); ?>>
                            <?php esc_html_e('No trackear usuarios sin su consentimiento explícito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Los datos de impresiones y clicks solo se registrarán si el usuario ha dado su consentimiento. Cumple con leyes de privacidad pero puede afectar a las estadísticas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Anonimización de IPs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="anonimizar_ips" value="1" <?php checked($anonimizar_ips, 1); ?>>
                            <?php esc_html_e('Anonimizar direcciones IP en el tracking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Las IPs se almacenarán de forma anonimizada (ejemplo: 192.168.1.XXX) para proteger la privacidad de los usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Botón de guardar -->
        <p class="submit">
            <button type="submit" name="flavor_advertising_settings" class="button button-primary button-large">
                <?php esc_html_e('Guardar Toda la Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-advertising-dashboard')); ?>" class="button button-large">
                <?php esc_html_e('Volver al Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Actualizar vista previa de distribución de ingresos
    function updateRevenuePreview() {
        var porcentaje_sitio = parseInt($('#porcentaje_sitio_mostrador').val()) || 0;
        var porcentaje_plataforma = parseInt($('#porcentaje_plataforma_red').val()) || 0;
        var porcentaje_comunidad = parseInt($('#porcentaje_proyectos_comunitarios').val()) || 0;
        var total = porcentaje_sitio + porcentaje_plataforma + porcentaje_comunidad;

        var preview_text = '<?php esc_html_e('Por cada €100 de ingresos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> ';
        preview_text += '<strong>€' + porcentaje_sitio + '</strong> <?php esc_html_e('para el sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>, ';
        preview_text += '<strong>€' + porcentaje_plataforma + '</strong> <?php esc_html_e('para la plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';

        if (porcentaje_comunidad > 0) {
            preview_text += ', <strong>€' + porcentaje_comunidad + '</strong> <?php esc_html_e('para proyectos comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
        }

        preview_text += '. <strong><?php esc_html_e('Total:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> ' + total + '%</strong>';

        if (total !== 100) {
            preview_text += ' <span style="color: #d63638; font-weight: bold;">⚠️ <?php esc_html_e('Error: Debe sumar 100%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>';
            $('#revenue-distribution-preview').css('border-color', '#d63638');
        } else {
            preview_text += ' <span style="color: #00a32a; font-weight: bold;">✓</span>';
            $('#revenue-distribution-preview').css('border-color', '#00a32a');
        }

        $('#revenue-preview-text').html(preview_text);
    }

    // Ejecutar al cargar y al cambiar cualquier porcentaje
    $('#porcentaje_sitio_mostrador, #porcentaje_plataforma_red, #porcentaje_proyectos_comunitarios').on('input change', updateRevenuePreview);
    updateRevenuePreview();

    // Validación antes de enviar
    $('form').on('submit', function(e) {
        var porcentaje_sitio = parseInt($('#porcentaje_sitio_mostrador').val()) || 0;
        var porcentaje_plataforma = parseInt($('#porcentaje_plataforma_red').val()) || 0;
        var porcentaje_comunidad = parseInt($('#porcentaje_proyectos_comunitarios').val()) || 0;
        var total = porcentaje_sitio + porcentaje_plataforma + porcentaje_comunidad;

        if (total !== 100) {
            e.preventDefault();
            alert('<?php esc_html_e('Error: Los porcentajes de distribución de ingresos deben sumar exactamente 100%. Actualmente suman:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> ' + total + '%');
            $('html, body').animate({
                scrollTop: $('#revenue-distribution-preview').offset().top - 100
            }, 500);
            return false;
        }
    });
});
</script>
