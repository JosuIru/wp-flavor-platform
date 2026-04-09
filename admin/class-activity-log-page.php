<?php
/**
 * Pagina de administracion del Registro de Actividad
 *
 * Muestra una tabla filtrable con toda la actividad registrada
 * por los modulos del sistema.
 *
 * @package FlavorChatIA
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Activity_Log_Page {

    private static $instancia = null;

    const SLUG_PAGINA = 'flavor-platform-activity-log';

    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
    }

    public function registrar_pagina_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Registro de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::SLUG_PAGINA,
            [$this, 'renderizar_pagina']
        );
    }

    public function renderizar_pagina() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        if (!class_exists('Flavor_Activity_Log')) {
            echo '<div class="wrap"><h1>Registro de Actividad</h1><p>El sistema de actividad no esta disponible.</p></div>';
            return;
        }

        $registro_actividad = Flavor_Activity_Log::get_instance();

        $filtros_aplicados = [
            'modulo_id'   => sanitize_key($_GET['modulo'] ?? ''),
            'tipo'        => sanitize_key($_GET['tipo'] ?? ''),
            'buscar'      => sanitize_text_field($_GET['buscar'] ?? ''),
            'fecha_desde' => sanitize_text_field($_GET['desde'] ?? ''),
            'fecha_hasta' => sanitize_text_field($_GET['hasta'] ?? ''),
            'usuario_id'  => absint($_GET['usuario'] ?? 0),
            'pagina'      => max(1, absint($_GET['paged'] ?? 1)),
            'por_pagina'  => 30,
        ];

        $resultado_actividad = $registro_actividad->obtener_actividad($filtros_aplicados);
        $resumen_actividad = $registro_actividad->obtener_resumen(7);
        $modulos_disponibles = $registro_actividad->obtener_modulos_con_actividad();

        $total_por_tipo = [];
        if (!empty($resumen_actividad['por_tipo'])) {
            foreach ($resumen_actividad['por_tipo'] as $fila_tipo) {
                $total_por_tipo[$fila_tipo->tipo] = intval($fila_tipo->total);
            }
        }

        $modulo_mas_activo_nombre = '-';
        $modulo_mas_activo_total = 0;
        if (!empty($resumen_actividad['por_modulo']) && isset($resumen_actividad['por_modulo'][0])) {
            $modulo_mas_activo_nombre = $resumen_actividad['por_modulo'][0]->modulo_id;
            $modulo_mas_activo_total = intval($resumen_actividad['por_modulo'][0]->total);
        }

        $url_base_pagina = admin_url('admin.php?page=' . self::SLUG_PAGINA);

        $iconos_tipo = [
            'info'        => 'dashicons-info',
            'exito'       => 'dashicons-yes-alt',
            'advertencia' => 'dashicons-warning',
            'error'       => 'dashicons-dismiss',
        ];

        $colores_tipo = [
            'info'        => '#2271b1',
            'exito'       => '#00a32a',
            'advertencia' => '#dba617',
            'error'       => '#d63638',
        ];

        $fondos_tipo = [
            'info'        => '#f0f6fc',
            'exito'       => '#edfaef',
            'advertencia' => '#fcf9e8',
            'error'       => '#fcf0f1',
        ];

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Registro de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap;">
                <div style="background:#fff;border-left:4px solid #2271b1;padding:16px 20px;min-width:150px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
                    <div style="font-size:13px;color:#50575e;"><?php esc_html_e('Total (7 dias)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div style="font-size:28px;font-weight:600;color:#2271b1;"><?php echo esc_html($resumen_actividad['total']); ?></div>
                </div>
                <div style="background:#fff;border-left:4px solid #d63638;padding:16px 20px;min-width:150px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
                    <div style="font-size:13px;color:#50575e;"><?php esc_html_e('Errores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div style="font-size:28px;font-weight:600;color:#d63638;"><?php echo esc_html($total_por_tipo['error'] ?? 0); ?></div>
                </div>
                <div style="background:#fff;border-left:4px solid #dba617;padding:16px 20px;min-width:150px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
                    <div style="font-size:13px;color:#50575e;"><?php esc_html_e('Advertencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div style="font-size:28px;font-weight:600;color:#dba617;"><?php echo esc_html($total_por_tipo['advertencia'] ?? 0); ?></div>
                </div>
                <div style="background:#fff;border-left:4px solid #00a32a;padding:16px 20px;min-width:150px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
                    <div style="font-size:13px;color:#50575e;"><?php esc_html_e('Modulo mas activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div style="font-size:18px;font-weight:600;color:#00a32a;"><?php echo esc_html(ucfirst($modulo_mas_activo_nombre)); ?></div>
                    <div style="font-size:12px;color:#50575e;"><?php echo esc_html($modulo_mas_activo_total); ?> <?php esc_html_e('eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>

            <form method="get" action="<?php echo esc_url($url_base_pagina); ?>" style="background:#fff;padding:16px;margin:16px 0;border:1px solid #c3c4c7;">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::SLUG_PAGINA); ?>">
                <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;font-weight:600;"><?php esc_html_e('Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="modulo" style="min-width:140px;">
                            <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($modulos_disponibles as $modulo_opcion) : ?>
                                <option value="<?php echo esc_attr($modulo_opcion); ?>" <?php selected($filtros_aplicados['modulo_id'], $modulo_opcion); ?>>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $modulo_opcion))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;font-weight:600;"><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="tipo" style="min-width:140px;">
                            <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="info" <?php selected($filtros_aplicados['tipo'], 'info'); ?>><?php esc_html_e('Info', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="exito" <?php selected($filtros_aplicados['tipo'], 'exito'); ?>><?php esc_html_e('Exito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="advertencia" <?php selected($filtros_aplicados['tipo'], 'advertencia'); ?>><?php esc_html_e('Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="error" <?php selected($filtros_aplicados['tipo'], 'error'); ?>><?php esc_html_e('Error', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;font-weight:600;"><?php esc_html_e('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" name="desde" value="<?php echo esc_attr($filtros_aplicados['fecha_desde']); ?>">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;font-weight:600;"><?php esc_html_e('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" name="hasta" value="<?php echo esc_attr($filtros_aplicados['fecha_hasta']); ?>">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;font-weight:600;"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="buscar" value="<?php echo esc_attr($filtros_aplicados['buscar']); ?>" placeholder="<?php esc_attr_e('Titulo o descripcion...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="min-width:160px;">
                    </div>
                    <div>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <a href="<?php echo esc_url($url_base_pagina); ?>" class="button"><?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    </div>
                </div>
            </form>

            <div class="tablenav top">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(esc_html__('%d elementos', FLAVOR_PLATFORM_TEXT_DOMAIN), $resultado_actividad['total']); ?>
                    </span>
                    <?php if ($resultado_actividad['paginas'] > 1) : ?>
                        <?php
                        $pagina_actual = $resultado_actividad['pagina'];
                        $total_paginas = $resultado_actividad['paginas'];
                        $parametros_paginacion = array_filter([
                            'page'    => self::SLUG_PAGINA,
                            'modulo'  => $filtros_aplicados['modulo_id'],
                            'tipo'    => $filtros_aplicados['tipo'],
                            'desde'   => $filtros_aplicados['fecha_desde'],
                            'hasta'   => $filtros_aplicados['fecha_hasta'],
                            'buscar'  => $filtros_aplicados['buscar'],
                        ]);
                        ?>
                        <span class="pagination-links">
                            <?php if ($pagina_actual > 1) :
                                $parametros_paginacion['paged'] = $pagina_actual - 1;
                            ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg($parametros_paginacion, admin_url('admin.php'))); ?>">&laquo;</a>
                            <?php endif; ?>
                            <span class="tablenav-pages-navspan button disabled"><?php printf('%d / %d', $pagina_actual, $total_paginas); ?></span>
                            <?php if ($pagina_actual < $total_paginas) :
                                $parametros_paginacion['paged'] = $pagina_actual + 1;
                            ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg($parametros_paginacion, admin_url('admin.php'))); ?>">&raquo;</a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width:40px;"><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Accion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:150px;"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resultado_actividad['registros'])) : ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:#50575e;">
                                <?php esc_html_e('No se encontraron registros de actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($resultado_actividad['registros'] as $registro_fila) : ?>
                            <?php
                            $tipo_registro = $registro_fila->tipo;
                            $icono_tipo = $iconos_tipo[$tipo_registro] ?? 'dashicons-marker';
                            $color_tipo = $colores_tipo[$tipo_registro] ?? '#50575e';
                            $fondo_tipo = $fondos_tipo[$tipo_registro] ?? '#f0f0f1';
                            ?>
                            <tr>
                                <td style="text-align:center;">
                                    <span class="dashicons <?php echo esc_attr($icono_tipo); ?>" style="color:<?php echo esc_attr($color_tipo); ?>;" title="<?php echo esc_attr(ucfirst($tipo_registro)); ?>"></span>
                                </td>
                                <td>
                                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $registro_fila->modulo_id))); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($registro_fila->titulo); ?>
                                    <?php if (!empty($registro_fila->descripcion)) : ?>
                                        <br><span style="color:#646970;font-size:12px;"><?php echo esc_html(mb_substr($registro_fila->descripcion, 0, 120)); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($registro_fila->objeto_tipo)) : ?>
                                        <br><small style="color:#a7aaad;"><?php echo esc_html($registro_fila->objeto_tipo); ?>
                                        <?php if (!empty($registro_fila->objeto_id)) : ?>
                                            #<?php echo esc_html($registro_fila->objeto_id); ?>
                                        <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="font-size:11px;"><?php echo esc_html($registro_fila->accion); ?></code>
                                </td>
                                <td>
                                    <?php echo esc_html($registro_fila->nombre_usuario ?? __('Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </td>
                                <td style="font-size:12px;color:#646970;white-space:nowrap;">
                                    <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($registro_fila->fecha))); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
