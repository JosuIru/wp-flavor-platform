<?php
/**
 * Vista Admin: Editar Reserva
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// $reserva viene del método render_pagina_editar()
if (!isset($reserva) || !$reserva) {
    return;
}

global $wpdb;
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

// Obtener configuración del módulo
$module = Flavor_Platform_Module_Loader::get_instance()->get_module('reservas');
$settings = $module ? $module->get_settings() : [];

// Estados
$estados = $settings['estados_reserva'] ?? [
    'pendiente'  => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelada'  => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'completada' => __('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_estado = [
    'pendiente'  => '#dba617',
    'confirmada' => '#00a32a',
    'cancelada'  => '#d63638',
    'completada' => '#2271b1',
];

// Tipos de servicio
$tipos_servicio = $settings['tipos_servicio'] ?? [
    'mesa_restaurante'  => __('Mesa de Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'espacio_coworking' => __('Espacio Coworking', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'clase_deportiva'   => __('Clase Deportiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Obtener recursos disponibles
$recursos = [];
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_recursos)) === $tabla_recursos) {
    $recursos = $wpdb->get_results("SELECT id, nombre FROM {$tabla_recursos} WHERE activo = 1 ORDER BY nombre ASC");
}

// Mensajes
$mensaje_error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
?>

<div class="wrap">
    <!-- Breadcrumbs -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-dashboard')); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php esc_html_e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado')); ?>" style="color: #2271b1; text-decoration: none;">
            <?php esc_html_e('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php esc_html_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> #<?php echo esc_html($reserva->id); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-edit"></span>
        <?php esc_html_e('Editar Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> #<?php echo esc_html($reserva->id); ?>
    </h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado')); ?>" class="page-title-action">
        <span class="dashicons dashicons-arrow-left-alt" style="vertical-align: middle; margin-top: -2px;"></span>
        <?php esc_html_e('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <?php if ($mensaje_error === 'db'): ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e('Error al guardar los cambios. Inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <!-- Info de la reserva -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin: 20px 0; max-width: 600px;">
        <div style="background: #fff; padding: 12px 15px; border-left: 4px solid <?php echo esc_attr($colores_estado[$reserva->estado] ?? '#646970'); ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #646970; text-transform: uppercase;"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 14px; font-weight: 600; color: <?php echo esc_attr($colores_estado[$reserva->estado] ?? '#646970'); ?>;">
                <?php echo esc_html($estados[$reserva->estado] ?? ucfirst($reserva->estado)); ?>
            </div>
        </div>
        <div style="background: #fff; padding: 12px 15px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #646970; text-transform: uppercase;"><?php esc_html_e('Creada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 14px; font-weight: 500;">
                <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->created_at))); ?>
            </div>
        </div>
        <?php if (!empty($reserva->updated_at) && $reserva->updated_at !== $reserva->created_at): ?>
        <div style="background: #fff; padding: 12px 15px; border-left: 4px solid #646970; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #646970; text-transform: uppercase;"><?php esc_html_e('Modificada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 14px; font-weight: 500;">
                <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->updated_at))); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Formulario -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width: 800px;">
        <?php wp_nonce_field('editar_reserva_' . $reserva->id, 'reserva_nonce'); ?>
        <input type="hidden" name="action" value="flavor_actualizar_reserva">
        <input type="hidden" name="reserva_id" value="<?php echo esc_attr($reserva->id); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">

                <!-- Columna principal -->
                <div id="post-body-content" style="position: relative;">

                    <!-- Datos del cliente -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><span class="dashicons dashicons-admin-users" style="margin-right: 5px;"></span><?php esc_html_e('Datos del cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nombre_cliente"><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" id="nombre_cliente" name="nombre_cliente"
                                               value="<?php echo esc_attr($reserva->nombre_cliente); ?>"
                                               class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="email_cliente"><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="email" id="email_cliente" name="email_cliente"
                                               value="<?php echo esc_attr($reserva->email_cliente); ?>"
                                               class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="telefono_cliente"><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="tel" id="telefono_cliente" name="telefono_cliente"
                                               value="<?php echo esc_attr($reserva->telefono_cliente ?? ''); ?>"
                                               class="regular-text">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Detalles de la reserva -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><span class="dashicons dashicons-calendar-alt" style="margin-right: 5px;"></span><?php esc_html_e('Detalles de la reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <?php if (!empty($recursos)): ?>
                                <tr>
                                    <th scope="row">
                                        <label for="recurso_id"><?php esc_html_e('Recurso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <select id="recurso_id" name="recurso_id" class="regular-text">
                                            <option value=""><?php esc_html_e('— Sin recurso asignado —', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                            <?php foreach ($recursos as $recurso): ?>
                                            <option value="<?php echo esc_attr($recurso->id); ?>" <?php selected($reserva->recurso_id, $recurso->id); ?>>
                                                <?php echo esc_html($recurso->nombre); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th scope="row">
                                        <label for="tipo_servicio"><?php esc_html_e('Tipo de servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <select id="tipo_servicio" name="tipo_servicio">
                                            <?php foreach ($tipos_servicio as $tipo_valor => $tipo_etiqueta): ?>
                                            <option value="<?php echo esc_attr($tipo_valor); ?>" <?php selected($reserva->tipo_servicio, $tipo_valor); ?>>
                                                <?php echo esc_html($tipo_etiqueta); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="fecha_reserva"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="date" id="fecha_reserva" name="fecha_reserva"
                                               value="<?php echo esc_attr($reserva->fecha_reserva); ?>"
                                               required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="hora_inicio"><?php esc_html_e('Hora inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="time" id="hora_inicio" name="hora_inicio"
                                               value="<?php echo esc_attr($reserva->hora_inicio); ?>"
                                               required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="hora_fin"><?php esc_html_e('Hora fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="time" id="hora_fin" name="hora_fin"
                                               value="<?php echo esc_attr($reserva->hora_fin ?? ''); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="num_personas"><?php esc_html_e('Personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="num_personas" name="num_personas"
                                               value="<?php echo esc_attr($reserva->num_personas ?? 1); ?>"
                                               min="1" max="100" class="small-text">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><span class="dashicons dashicons-edit-page" style="margin-right: 5px;"></span><?php esc_html_e('Notas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        </div>
                        <div class="inside">
                            <textarea id="notas" name="notas" rows="4" class="large-text"><?php echo esc_textarea($reserva->notas ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('Notas internas o comentarios del cliente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container" style="width: 280px;">

                    <!-- Estado -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        </div>
                        <div class="inside">
                            <div style="margin-bottom: 15px;">
                                <label for="estado" class="screen-reader-text"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="estado" name="estado" style="width: 100%;">
                                    <?php foreach ($estados as $estado_valor => $estado_etiqueta): ?>
                                    <option value="<?php echo esc_attr($estado_valor); ?>" <?php selected($reserva->estado, $estado_valor); ?>>
                                        <?php echo esc_html($estado_etiqueta); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="border-top: 1px solid #dcdcde; padding-top: 15px; margin-top: 15px;">
                                <button type="submit" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-saved" style="vertical-align: middle; margin-top: -2px;"></span>
                                    <?php esc_html_e('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        </div>
                        <div class="inside">
                            <p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado')); ?>" class="button" style="width: 100%; text-align: center;">
                                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </p>
                            <?php if ($reserva->estado !== 'cancelada'): ?>
                            <p>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=reservas-listado&action=cancelar&id=' . $reserva->id), 'cancelar_reserva_' . $reserva->id)); ?>"
                                   class="button" style="width: 100%; text-align: center; color: #d63638;"
                                   onclick="return confirm('<?php esc_attr_e('¿Seguro que quieres cancelar esta reserva?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');">
                                    <?php esc_html_e('Cancelar reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<style>
.wrap { margin-left: 20px; padding-right: 20px; }
.required { color: #d63638; }
#post-body-content { margin-right: 300px; }
#postbox-container-1 { float: right; }
.postbox-header h2 { padding: 10px 12px; margin: 0; font-size: 14px; }
.postbox .inside { padding: 0 12px 12px; margin: 0; }
.postbox .form-table th { padding: 15px 10px 15px 0; width: 120px; }
.postbox .form-table td { padding: 10px 0; }
@media screen and (max-width: 782px) {
    #post-body-content { margin-right: 0; }
    #postbox-container-1 { float: none; width: 100%; margin-top: 20px; }
}
</style>
