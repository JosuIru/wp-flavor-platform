<?php
/**
 * Template: Calendario de Ciclos de Grupos de Consumo
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$ciclos = $args['ciclos'] ?? [];
$mes_actual = $args['mes'] ?? date('n');
$ano_actual = $args['ano'] ?? date('Y');
$vista = $args['vista'] ?? 'mes';
?>

<div class="gc-calendario-wrapper" data-mes="<?php echo esc_attr($mes_actual); ?>" data-ano="<?php echo esc_attr($ano_actual); ?>">

    <!-- Cabecera del calendario -->
    <div class="gc-calendario-header">
        <button type="button" class="gc-btn-nav gc-btn-prev" data-action="mes-anterior">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>

        <h2 class="gc-calendario-titulo">
            <?php
            $meses = [
                1 => __('Enero', 'flavor-chat-ia'),
                2 => __('Febrero', 'flavor-chat-ia'),
                3 => __('Marzo', 'flavor-chat-ia'),
                4 => __('Abril', 'flavor-chat-ia'),
                5 => __('Mayo', 'flavor-chat-ia'),
                6 => __('Junio', 'flavor-chat-ia'),
                7 => __('Julio', 'flavor-chat-ia'),
                8 => __('Agosto', 'flavor-chat-ia'),
                9 => __('Septiembre', 'flavor-chat-ia'),
                10 => __('Octubre', 'flavor-chat-ia'),
                11 => __('Noviembre', 'flavor-chat-ia'),
                12 => __('Diciembre', 'flavor-chat-ia'),
            ];
            echo esc_html($meses[$mes_actual] . ' ' . $ano_actual);
            ?>
        </h2>

        <button type="button" class="gc-btn-nav gc-btn-next" data-action="mes-siguiente">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    </div>

    <!-- Selector de vista -->
    <div class="gc-calendario-vistas">
        <button type="button" class="gc-vista-btn <?php echo $vista === 'mes' ? 'activa' : ''; ?>" data-vista="mes">
            <?php _e('Mes', 'flavor-chat-ia'); ?>
        </button>
        <button type="button" class="gc-vista-btn <?php echo $vista === 'lista' ? 'activa' : ''; ?>" data-vista="lista">
            <?php _e('Lista', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Leyenda -->
    <div class="gc-calendario-leyenda">
        <span class="gc-leyenda-item gc-estado-abierto">
            <span class="gc-leyenda-dot"></span>
            <?php _e('Pedidos abiertos', 'flavor-chat-ia'); ?>
        </span>
        <span class="gc-leyenda-item gc-estado-cerrado">
            <span class="gc-leyenda-dot"></span>
            <?php _e('Pedidos cerrados', 'flavor-chat-ia'); ?>
        </span>
        <span class="gc-leyenda-item gc-estado-entrega">
            <span class="gc-leyenda-dot"></span>
            <?php _e('Día de entrega', 'flavor-chat-ia'); ?>
        </span>
    </div>

    <!-- Vista de mes -->
    <div class="gc-calendario-mes <?php echo $vista !== 'mes' ? 'oculto' : ''; ?>">
        <div class="gc-calendario-dias-semana">
            <span><?php _e('Lun', 'flavor-chat-ia'); ?></span>
            <span><?php _e('Mar', 'flavor-chat-ia'); ?></span>
            <span><?php _e('Mié', 'flavor-chat-ia'); ?></span>
            <span><?php _e('Jue', 'flavor-chat-ia'); ?></span>
            <span><?php _e('Vie', 'flavor-chat-ia'); ?></span>
            <span><?php _e('Sáb', 'flavor-chat-ia'); ?></span>
            <span><?php _e('Dom', 'flavor-chat-ia'); ?></span>
        </div>

        <div class="gc-calendario-grid">
            <?php
            // Calcular días del mes
            $primer_dia = mktime(0, 0, 0, $mes_actual, 1, $ano_actual);
            $dias_mes = date('t', $primer_dia);
            $dia_semana_inicio = date('N', $primer_dia);
            $hoy = date('Y-m-d');

            // Días vacíos al inicio
            for ($i = 1; $i < $dia_semana_inicio; $i++): ?>
                <div class="gc-calendario-dia gc-dia-vacio"></div>
            <?php endfor;

            // Días del mes
            for ($dia = 1; $dia <= $dias_mes; $dia++):
                $fecha = sprintf('%04d-%02d-%02d', $ano_actual, $mes_actual, $dia);
                $es_hoy = ($fecha === $hoy);

                // Buscar eventos para este día
                $eventos_dia = array_filter($ciclos, function($ciclo) use ($fecha) {
                    return ($ciclo['fecha_inicio'] <= $fecha && $ciclo['fecha_cierre'] >= $fecha)
                        || $ciclo['fecha_entrega'] === $fecha;
                });

                $clases_dia = ['gc-calendario-dia'];
                if ($es_hoy) $clases_dia[] = 'gc-dia-hoy';
                if (!empty($eventos_dia)) $clases_dia[] = 'gc-dia-con-eventos';
                ?>
                <div class="<?php echo esc_attr(implode(' ', $clases_dia)); ?>" data-fecha="<?php echo esc_attr($fecha); ?>">
                    <span class="gc-dia-numero"><?php echo $dia; ?></span>

                    <?php if (!empty($eventos_dia)): ?>
                        <div class="gc-dia-eventos">
                            <?php foreach ($eventos_dia as $ciclo):
                                $es_entrega = ($ciclo['fecha_entrega'] === $fecha);
                                $es_cierre = ($ciclo['fecha_cierre'] === $fecha);
                                $estado_clase = $es_entrega ? 'gc-evento-entrega' : ($es_cierre ? 'gc-evento-cierre' : 'gc-evento-abierto');
                                ?>
                                <div class="gc-evento <?php echo esc_attr($estado_clase); ?>"
                                     data-ciclo="<?php echo esc_attr($ciclo['id']); ?>"
                                     title="<?php echo esc_attr($ciclo['titulo']); ?>">
                                    <?php if ($es_entrega): ?>
                                        <span class="dashicons dashicons-location"></span>
                                    <?php elseif ($es_cierre): ?>
                                        <span class="dashicons dashicons-lock"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-cart"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor;

            // Días vacíos al final
            $ultimo_dia_semana = date('N', mktime(0, 0, 0, $mes_actual, $dias_mes, $ano_actual));
            for ($i = $ultimo_dia_semana; $i < 7; $i++): ?>
                <div class="gc-calendario-dia gc-dia-vacio"></div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Vista de lista -->
    <div class="gc-calendario-lista <?php echo $vista !== 'lista' ? 'oculto' : ''; ?>">
        <?php if (empty($ciclos)): ?>
            <div class="gc-calendario-sin-ciclos">
                <span class="dashicons dashicons-calendar"></span>
                <p><?php _e('No hay ciclos programados para este mes.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($ciclos as $ciclo): ?>
                <div class="gc-ciclo-card <?php echo esc_attr('gc-estado-' . $ciclo['estado']); ?>">
                    <div class="gc-ciclo-fecha">
                        <span class="gc-ciclo-dia"><?php echo date('d', strtotime($ciclo['fecha_entrega'])); ?></span>
                        <span class="gc-ciclo-mes"><?php echo esc_html($meses[(int)date('n', strtotime($ciclo['fecha_entrega']))]); ?></span>
                    </div>

                    <div class="gc-ciclo-info">
                        <h3 class="gc-ciclo-titulo"><?php echo esc_html($ciclo['titulo']); ?></h3>
                        <div class="gc-ciclo-detalles">
                            <span class="gc-detalle">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php printf(__('Pedidos: %s - %s', 'flavor-chat-ia'),
                                    date('d/m', strtotime($ciclo['fecha_inicio'])),
                                    date('d/m', strtotime($ciclo['fecha_cierre']))
                                ); ?>
                            </span>
                            <?php if (!empty($ciclo['lugar_entrega'])): ?>
                                <span class="gc-detalle">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($ciclo['lugar_entrega']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($ciclo['hora_entrega'])): ?>
                                <span class="gc-detalle">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($ciclo['hora_entrega']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="gc-ciclo-accion">
                        <?php if ($ciclo['estado'] === 'abierto'): ?>
                            <a href="<?php echo esc_url(get_permalink($ciclo['id'])); ?>" class="gc-btn gc-btn-primary">
                                <?php _e('Hacer pedido', 'flavor-chat-ia'); ?>
                            </a>
                        <?php else: ?>
                            <span class="gc-badge gc-badge-<?php echo esc_attr($ciclo['estado']); ?>">
                                <?php
                                $estados = [
                                    'cerrado' => __('Cerrado', 'flavor-chat-ia'),
                                    'entregado' => __('Entregado', 'flavor-chat-ia'),
                                    'preparando' => __('En preparación', 'flavor-chat-ia'),
                                ];
                                echo esc_html($estados[$ciclo['estado']] ?? $ciclo['estado']);
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de detalle de ciclo -->
    <div class="gc-modal gc-modal-ciclo" id="gc-modal-ciclo" style="display: none;">
        <div class="gc-modal-content">
            <button type="button" class="gc-modal-close">&times;</button>
            <div class="gc-modal-body">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>
