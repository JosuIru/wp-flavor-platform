<?php
/**
 * Vista de Configuración - Módulo Marketplace
 *
 * @package FlavorPlatform
 * @subpackage Marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion = get_option('flavor_marketplace_settings', []);
$configuracion_default = [
    'nombre_tienda' => __('Marketplace Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'descripcion' => '',
    'moneda' => 'EUR',
    'simbolo_moneda' => '€',
    'posicion_moneda' => 'antes',
    'decimales' => 2,
    'permitir_registro_vendedores' => true,
    'requiere_aprobacion_vendedor' => true,
    'requiere_aprobacion_producto' => false,
    'comision_porcentaje' => 0,
    'minimo_retiro' => 50,
    'permitir_negociar' => true,
    'permitir_intercambio' => true,
    'mostrar_vendedor' => true,
    'mostrar_ubicacion' => true,
    'radio_busqueda_km' => 50,
    'productos_por_pagina' => 12,
    'habilitar_favoritos' => true,
    'habilitar_valoraciones' => true,
    'habilitar_mensajes' => true,
    'notificar_nuevo_producto' => true,
    'notificar_venta' => true,
    'notificar_mensaje' => true,
    'categorias_destacadas' => '',
    'terminos_condiciones' => '',
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

$mensaje_guardado = '';
if (isset($_POST['guardar_config_marketplace']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_marketplace')) {
    $nueva_config = [
        'nombre_tienda' => sanitize_text_field($_POST['nombre_tienda'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'moneda' => sanitize_text_field($_POST['moneda'] ?? 'EUR'),
        'simbolo_moneda' => sanitize_text_field($_POST['simbolo_moneda'] ?? '€'),
        'posicion_moneda' => sanitize_text_field($_POST['posicion_moneda'] ?? 'antes'),
        'decimales' => absint($_POST['decimales'] ?? 2),
        'permitir_registro_vendedores' => isset($_POST['permitir_registro_vendedores']),
        'requiere_aprobacion_vendedor' => isset($_POST['requiere_aprobacion_vendedor']),
        'requiere_aprobacion_producto' => isset($_POST['requiere_aprobacion_producto']),
        'comision_porcentaje' => floatval($_POST['comision_porcentaje'] ?? 0),
        'minimo_retiro' => floatval($_POST['minimo_retiro'] ?? 50),
        'permitir_negociar' => isset($_POST['permitir_negociar']),
        'permitir_intercambio' => isset($_POST['permitir_intercambio']),
        'mostrar_vendedor' => isset($_POST['mostrar_vendedor']),
        'mostrar_ubicacion' => isset($_POST['mostrar_ubicacion']),
        'radio_busqueda_km' => absint($_POST['radio_busqueda_km'] ?? 50),
        'productos_por_pagina' => absint($_POST['productos_por_pagina'] ?? 12),
        'habilitar_favoritos' => isset($_POST['habilitar_favoritos']),
        'habilitar_valoraciones' => isset($_POST['habilitar_valoraciones']),
        'habilitar_mensajes' => isset($_POST['habilitar_mensajes']),
        'notificar_nuevo_producto' => isset($_POST['notificar_nuevo_producto']),
        'notificar_venta' => isset($_POST['notificar_venta']),
        'notificar_mensaje' => isset($_POST['notificar_mensaje']),
        'categorias_destacadas' => sanitize_text_field($_POST['categorias_destacadas'] ?? ''),
        'terminos_condiciones' => wp_kses_post($_POST['terminos_condiciones'] ?? ''),
    ];

    update_option('flavor_marketplace_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
}
?>

<div class="wrap flavor-marketplace-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-store"></span>
        <?php esc_html_e('Configuración del Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_marketplace'); ?>

        <div class="dm-config-grid">
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-store"></span> <?php esc_html_e('Información General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="nombre_tienda"><?php esc_html_e('Nombre del Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="nombre_tienda" name="nombre_tienda" value="<?php echo esc_attr($configuracion['nombre_tienda']); ?>">
                    </div>
                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>
                    <div class="dm-form-group">
                        <label for="productos_por_pagina"><?php esc_html_e('Productos por página', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="productos_por_pagina" name="productos_por_pagina" value="<?php echo esc_attr($configuracion['productos_por_pagina']); ?>" min="4" max="48">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Moneda y Precios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="moneda"><?php esc_html_e('Moneda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="moneda" name="moneda">
                                <option value="EUR" <?php selected($configuracion['moneda'], 'EUR'); ?>>Euro (EUR)</option>
                                <option value="USD" <?php selected($configuracion['moneda'], 'USD'); ?>>Dólar (USD)</option>
                                <option value="GBP" <?php selected($configuracion['moneda'], 'GBP'); ?>>Libra (GBP)</option>
                                <option value="MXN" <?php selected($configuracion['moneda'], 'MXN'); ?>>Peso MX (MXN)</option>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="simbolo_moneda"><?php esc_html_e('Símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="simbolo_moneda" name="simbolo_moneda" value="<?php echo esc_attr($configuracion['simbolo_moneda']); ?>" maxlength="5">
                        </div>
                    </div>
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="posicion_moneda"><?php esc_html_e('Posición del símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="posicion_moneda" name="posicion_moneda">
                                <option value="antes" <?php selected($configuracion['posicion_moneda'], 'antes'); ?>><?php esc_html_e('Antes (€10)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="despues" <?php selected($configuracion['posicion_moneda'], 'despues'); ?>><?php esc_html_e('Después (10€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="decimales"><?php esc_html_e('Decimales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="decimales" name="decimales">
                                <option value="0" <?php selected($configuracion['decimales'], 0); ?>>0</option>
                                <option value="2" <?php selected($configuracion['decimales'], 2); ?>>2</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-businessman"></span> <?php esc_html_e('Vendedores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_registro_vendedores" value="1" <?php checked($configuracion['permitir_registro_vendedores']); ?>>
                            <span><?php esc_html_e('Permitir registro de vendedores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="requiere_aprobacion_vendedor" value="1" <?php checked($configuracion['requiere_aprobacion_vendedor']); ?>>
                            <span><?php esc_html_e('Requerir aprobación de vendedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="requiere_aprobacion_producto" value="1" <?php checked($configuracion['requiere_aprobacion_producto']); ?>>
                            <span><?php esc_html_e('Requerir aprobación de productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_vendedor" value="1" <?php checked($configuracion['mostrar_vendedor']); ?>>
                            <span><?php esc_html_e('Mostrar información del vendedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-row" style="margin-top: 15px;">
                        <div class="dm-form-group">
                            <label for="comision_porcentaje"><?php esc_html_e('Comisión (%)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" id="comision_porcentaje" name="comision_porcentaje" value="<?php echo esc_attr($configuracion['comision_porcentaje']); ?>" min="0" max="100" step="0.5">
                        </div>
                        <div class="dm-form-group">
                            <label for="minimo_retiro"><?php esc_html_e('Mínimo para retiro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" id="minimo_retiro" name="minimo_retiro" value="<?php echo esc_attr($configuracion['minimo_retiro']); ?>" min="0" step="0.01">
                        </div>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-cart"></span> <?php esc_html_e('Opciones de Compra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_negociar" value="1" <?php checked($configuracion['permitir_negociar']); ?>>
                            <span><?php esc_html_e('Permitir negociar precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_intercambio" value="1" <?php checked($configuracion['permitir_intercambio']); ?>>
                            <span><?php esc_html_e('Permitir trueque/intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_ubicacion" value="1" <?php checked($configuracion['mostrar_ubicacion']); ?>>
                            <span><?php esc_html_e('Mostrar ubicación del producto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="radio_busqueda_km"><?php esc_html_e('Radio de búsqueda (km)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="radio_busqueda_km" name="radio_busqueda_km" value="<?php echo esc_attr($configuracion['radio_busqueda_km']); ?>" min="1" max="500">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_favoritos" value="1" <?php checked($configuracion['habilitar_favoritos']); ?>>
                            <span><?php esc_html_e('Habilitar lista de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_valoraciones" value="1" <?php checked($configuracion['habilitar_valoraciones']); ?>>
                            <span><?php esc_html_e('Habilitar valoraciones y reseñas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_mensajes" value="1" <?php checked($configuracion['habilitar_mensajes']); ?>>
                            <span><?php esc_html_e('Habilitar mensajería entre usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-email"></span> <?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_nuevo_producto" value="1" <?php checked($configuracion['notificar_nuevo_producto']); ?>>
                            <span><?php esc_html_e('Notificar nuevos productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_venta" value="1" <?php checked($configuracion['notificar_venta']); ?>>
                            <span><?php esc_html_e('Notificar ventas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_mensaje" value="1" <?php checked($configuracion['notificar_mensaje']); ?>>
                            <span><?php esc_html_e('Notificar mensajes nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_marketplace" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-marketplace-config { max-width: 1200px; }
.dm-config-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
@media (max-width: 1024px) { .dm-config-grid { grid-template-columns: 1fr; } }
.dm-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.dm-card__header { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; }
.dm-card__header h3 { margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.dm-card__header .dashicons { color: #2271b1; }
.dm-card__body { padding: 20px; }
.dm-form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px; }
.dm-form-group { margin-bottom: 15px; }
.dm-form-group:last-child { margin-bottom: 0; }
.dm-form-group label { display: flex; align-items: center; gap: 5px; font-weight: 600; font-size: 13px; margin-bottom: 5px; }
.dm-form-group input[type="text"], .dm-form-group input[type="number"], .dm-form-group select, .dm-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 14px; }
.dm-form-group textarea { resize: vertical; }
.dm-checkbox-group { display: flex; flex-direction: column; gap: 12px; }
.dm-checkbox { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; }
.dm-checkbox input { margin-top: 3px; }
.dm-checkbox span { font-size: 13px; }
.dm-form-actions { margin-top: 25px; padding: 20px; background: #f6f7f7; border-radius: 8px; text-align: center; }
.dm-form-actions .button-hero { display: inline-flex; align-items: center; gap: 8px; padding: 10px 30px; font-size: 14px; }
.dm-form-actions .dashicons { font-size: 18px; width: 18px; height: 18px; }
</style>
