<?php
/**
 * Template: Historial de Pedidos de Grupos de Consumo
 *
 * @package FlavorPlatform
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$pedidos = $args['pedidos'] ?? [];
$paginacion = $args['paginacion'] ?? [];
$filtros = $args['filtros'] ?? [];
?>

<div class="gc-historial-wrapper">

    <?php if (!is_user_logged_in()): ?>
        <div class="gc-historial-login-requerido">
            <span class="dashicons dashicons-lock"></span>
            <p><?php _e('Inicia sesión para ver tu historial de pedidos.', 'flavor-platform'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'mis-pedidos'))); ?>" class="gc-btn gc-btn-primary">
                <?php _e('Iniciar sesión', 'flavor-platform'); ?>
            </a>
        </div>
    <?php else: ?>

        <!-- Filtros -->
        <div class="gc-historial-filtros">
            <form method="get" class="gc-filtros-form">
                <div class="gc-filtro-grupo">
                    <label for="gc-filtro-estado"><?php _e('Estado:', 'flavor-platform'); ?></label>
                    <select id="gc-filtro-estado" name="estado">
                        <option value=""><?php _e('Todos', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('pendiente', 'flavor-platform'); ?>" <?php selected($filtros['estado'] ?? '', 'pendiente'); ?>><?php _e('Pendiente', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('pagado', 'flavor-platform'); ?>" <?php selected($filtros['estado'] ?? '', 'pagado'); ?>><?php _e('Pagado', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('recogido', 'flavor-platform'); ?>" <?php selected($filtros['estado'] ?? '', 'recogido'); ?>><?php _e('Recogido', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('cancelado', 'flavor-platform'); ?>" <?php selected($filtros['estado'] ?? '', 'cancelado'); ?>><?php _e('Cancelado', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="gc-filtro-grupo">
                    <label for="gc-filtro-fecha"><?php _e('Período:', 'flavor-platform'); ?></label>
                    <select id="gc-filtro-fecha" name="periodo">
                        <option value=""><?php _e('Todo el tiempo', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('mes', 'flavor-platform'); ?>" <?php selected($filtros['periodo'] ?? '', 'mes'); ?>><?php _e('Último mes', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('trimestre', 'flavor-platform'); ?>" <?php selected($filtros['periodo'] ?? '', 'trimestre'); ?>><?php _e('Último trimestre', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('ano', 'flavor-platform'); ?>" <?php selected($filtros['periodo'] ?? '', 'ano'); ?>><?php _e('Último año', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <button type="submit" class="gc-btn gc-btn-secondary gc-btn-sm">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Filtrar', 'flavor-platform'); ?>
                </button>
            </form>
        </div>

        <?php if (empty($pedidos)): ?>
            <div class="gc-historial-vacio">
                <span class="gc-historial-icono-vacio">📋</span>
                <h3><?php _e('No tienes pedidos', 'flavor-platform'); ?></h3>
                <p><?php _e('Cuando hagas tu primer pedido, aparecerá aquí.', 'flavor-platform'); ?></p>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="gc-btn gc-btn-primary">
                    <?php _e('Ver productos', 'flavor-platform'); ?>
                </a>
            </div>
        <?php else: ?>

            <!-- Resumen -->
            <div class="gc-historial-resumen">
                <div class="gc-resumen-stat">
                    <span class="gc-stat-valor"><?php echo count($pedidos); ?></span>
                    <span class="gc-stat-label"><?php _e('Pedidos', 'flavor-platform'); ?></span>
                </div>
                <div class="gc-resumen-stat">
                    <span class="gc-stat-valor">
                        <?php
                        $total_gastado = array_sum(array_column($pedidos, 'total'));
                        echo number_format($total_gastado, 2, ',', '.');
                        ?> €
                    </span>
                    <span class="gc-stat-label"><?php _e('Total gastado', 'flavor-platform'); ?></span>
                </div>
            </div>

            <!-- Lista de pedidos -->
            <div class="gc-historial-lista">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="gc-pedido-card" data-id="<?php echo esc_attr($pedido['id']); ?>">
                        <div class="gc-pedido-header">
                            <div class="gc-pedido-info">
                                <span class="gc-pedido-ciclo"><?php echo esc_html($pedido['ciclo_titulo']); ?></span>
                                <span class="gc-pedido-fecha"><?php echo date_i18n('d M Y', strtotime($pedido['fecha'])); ?></span>
                            </div>
                            <span class="gc-pedido-estado gc-estado-<?php echo esc_attr($pedido['estado']); ?>">
                                <?php
                                $estados = [
                                    'pendiente' => __('Pendiente', 'flavor-platform'),
                                    'pagado' => __('Pagado', 'flavor-platform'),
                                    'recogido' => __('Recogido', 'flavor-platform'),
                                    'cancelado' => __('Cancelado', 'flavor-platform'),
                                ];
                                echo esc_html($estados[$pedido['estado']] ?? $pedido['estado']);
                                ?>
                            </span>
                        </div>

                        <div class="gc-pedido-productos">
                            <?php
                            $productos_mostrar = array_slice($pedido['productos'], 0, 3);
                            foreach ($productos_mostrar as $producto):
                                ?>
                                <div class="gc-pedido-producto">
                                    <span class="gc-producto-nombre"><?php echo esc_html($producto['nombre']); ?></span>
                                    <span class="gc-producto-cantidad">x<?php echo esc_html($producto['cantidad']); ?></span>
                                </div>
                            <?php endforeach; ?>

                            <?php if (count($pedido['productos']) > 3): ?>
                                <span class="gc-pedido-mas">
                                    <?php printf(__('y %d más...', 'flavor-platform'), count($pedido['productos']) - 3); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="gc-pedido-footer">
                            <div class="gc-pedido-total">
                                <span class="gc-total-label"><?php _e('Total:', 'flavor-platform'); ?></span>
                                <span class="gc-total-valor"><?php echo number_format($pedido['total'], 2, ',', '.'); ?> €</span>
                            </div>

                            <div class="gc-pedido-acciones">
                                <button type="button" class="gc-btn gc-btn-sm gc-btn-secondary gc-btn-ver-detalle"
                                        data-pedido="<?php echo esc_attr($pedido['id']); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php _e('Ver detalle', 'flavor-platform'); ?>
                                </button>

                                <?php if ($pedido['estado'] === 'recogido'): ?>
                                    <button type="button" class="gc-btn gc-btn-sm gc-btn-outline gc-btn-repetir"
                                            data-pedido="<?php echo esc_attr($pedido['id']); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e('Repetir', 'flavor-platform'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if (!empty($paginacion) && $paginacion['total_paginas'] > 1): ?>
                <div class="gc-historial-paginacion">
                    <?php if ($paginacion['pagina_actual'] > 1): ?>
                        <a href="<?php echo esc_url(add_query_arg('pag', $paginacion['pagina_actual'] - 1)); ?>"
                           class="gc-btn gc-btn-sm gc-btn-secondary">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php _e('Anterior', 'flavor-platform'); ?>
                        </a>
                    <?php endif; ?>

                    <span class="gc-paginacion-info">
                        <?php printf(__('Página %d de %d', 'flavor-platform'),
                            $paginacion['pagina_actual'],
                            $paginacion['total_paginas']
                        ); ?>
                    </span>

                    <?php if ($paginacion['pagina_actual'] < $paginacion['total_paginas']): ?>
                        <a href="<?php echo esc_url(add_query_arg('pag', $paginacion['pagina_actual'] + 1)); ?>"
                           class="gc-btn gc-btn-sm gc-btn-secondary">
                            <?php _e('Siguiente', 'flavor-platform'); ?>
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Modal de detalle de pedido -->
    <div class="gc-modal gc-modal-pedido" id="gc-modal-pedido" style="display: none;">
        <div class="gc-modal-content gc-modal-lg">
            <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', 'flavor-platform'); ?></button>
            <div class="gc-modal-body">
                <div class="gc-modal-loading">
                    <span class="gc-spinner"></span>
                    <?php _e('Cargando...', 'flavor-platform'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
