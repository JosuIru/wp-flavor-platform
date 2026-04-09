<?php
/**
 * Template: Tab Mi Cesta/Suscripción en Mi Cuenta
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$suscripcion = $args['suscripcion'] ?? null;
$cestas_disponibles = $args['cestas'] ?? [];
$historial_entregas = $args['historial'] ?? [];
?>

<div class="gc-dashboard-suscripcion">
    <div class="gc-dashboard-header">
        <h2><?php _e('Mi Cesta', 'flavor-platform'); ?></h2>
    </div>

    <?php if ($suscripcion): ?>
        <!-- Suscripción activa -->
        <div class="gc-suscripcion-activa">
            <div class="gc-suscripcion-card">
                <div class="gc-suscripcion-header">
                    <?php if (!empty($suscripcion['imagen'])): ?>
                        <img src="<?php echo esc_url($suscripcion['imagen']); ?>" alt="<?php echo esc_attr($suscripcion['nombre']); ?>" class="gc-suscripcion-imagen">
                    <?php else: ?>
                        <div class="gc-suscripcion-imagen-placeholder">🧺</div>
                    <?php endif; ?>

                    <div class="gc-suscripcion-info">
                        <h3><?php echo esc_html($suscripcion['nombre']); ?></h3>
                        <span class="gc-badge gc-badge-<?php echo esc_attr($suscripcion['estado']); ?>">
                            <?php
                            $estados = [
                                'activa' => __('Activa', 'flavor-platform'),
                                'pausada' => __('Pausada', 'flavor-platform'),
                            ];
                            echo esc_html($estados[$suscripcion['estado']] ?? $suscripcion['estado']);
                            ?>
                        </span>
                    </div>
                </div>

                <div class="gc-suscripcion-detalles">
                    <div class="gc-detalle">
                        <span class="gc-detalle-label"><?php _e('Frecuencia:', 'flavor-platform'); ?></span>
                        <span class="gc-detalle-valor">
                            <?php
                            $frecuencias = [
                                'semanal' => __('Semanal', 'flavor-platform'),
                                'quincenal' => __('Quincenal', 'flavor-platform'),
                                'mensual' => __('Mensual', 'flavor-platform'),
                            ];
                            echo esc_html($frecuencias[$suscripcion['frecuencia']] ?? $suscripcion['frecuencia']);
                            ?>
                        </span>
                    </div>

                    <div class="gc-detalle">
                        <span class="gc-detalle-label"><?php _e('Importe:', 'flavor-platform'); ?></span>
                        <span class="gc-detalle-valor gc-importe"><?php echo number_format($suscripcion['importe'], 2, ',', '.'); ?> €</span>
                    </div>

                    <div class="gc-detalle">
                        <span class="gc-detalle-label"><?php _e('Próxima entrega:', 'flavor-platform'); ?></span>
                        <span class="gc-detalle-valor">
                            <?php echo date_i18n('d M Y', strtotime($suscripcion['fecha_proximo_cargo'])); ?>
                        </span>
                    </div>

                    <div class="gc-detalle">
                        <span class="gc-detalle-label"><?php _e('Desde:', 'flavor-platform'); ?></span>
                        <span class="gc-detalle-valor">
                            <?php echo date_i18n('d M Y', strtotime($suscripcion['fecha_inicio'])); ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($suscripcion['descripcion'])): ?>
                    <div class="gc-suscripcion-descripcion">
                        <p><?php echo wp_kses_post($suscripcion['descripcion']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="gc-suscripcion-acciones">
                    <?php if ($suscripcion['estado'] === 'activa'): ?>
                        <button type="button" class="gc-btn gc-btn-secondary gc-btn-pausar" data-id="<?php echo esc_attr($suscripcion['id']); ?>">
                            <span class="dashicons dashicons-controls-pause"></span>
                            <?php _e('Pausar', 'flavor-platform'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="gc-btn gc-btn-primary gc-btn-reanudar" data-id="<?php echo esc_attr($suscripcion['id']); ?>">
                            <span class="dashicons dashicons-controls-play"></span>
                            <?php _e('Reanudar', 'flavor-platform'); ?>
                        </button>
                    <?php endif; ?>

                    <button type="button" class="gc-btn gc-btn-outline gc-btn-cambiar" data-id="<?php echo esc_attr($suscripcion['id']); ?>">
                        <span class="dashicons dashicons-randomize"></span>
                        <?php _e('Cambiar cesta', 'flavor-platform'); ?>
                    </button>

                    <button type="button" class="gc-btn gc-btn-danger-outline gc-btn-cancelar" data-id="<?php echo esc_attr($suscripcion['id']); ?>">
                        <span class="dashicons dashicons-no"></span>
                        <?php _e('Cancelar', 'flavor-platform'); ?>
                    </button>
                </div>
            </div>

            <?php if (!empty($historial_entregas)): ?>
                <div class="gc-suscripcion-historial">
                    <h4><?php _e('Últimas entregas', 'flavor-platform'); ?></h4>
                    <ul class="gc-historial-mini">
                        <?php foreach (array_slice($historial_entregas, 0, 5) as $entrega): ?>
                            <li>
                                <span class="gc-entrega-fecha"><?php echo date_i18n('d M', strtotime($entrega['fecha'])); ?></span>
                                <span class="gc-entrega-estado gc-estado-<?php echo esc_attr($entrega['estado']); ?>">
                                    <?php echo $entrega['estado'] === 'entregado' ? '✓' : '○'; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- Sin suscripción -->
        <div class="gc-suscripcion-vacia">
            <div class="gc-empty-icon">🧺</div>
            <h3><?php _e('No tienes una cesta suscrita', 'flavor-platform'); ?></h3>
            <p><?php _e('Suscríbete a una cesta y recibe productos frescos periódicamente sin tener que hacer pedidos manuales.', 'flavor-platform'); ?></p>
        </div>

        <!-- Cestas disponibles -->
        <?php if (!empty($cestas_disponibles)): ?>
            <div class="gc-cestas-disponibles">
                <h3><?php _e('Cestas disponibles', 'flavor-platform'); ?></h3>

                <div class="gc-cestas-grid">
                    <?php foreach ($cestas_disponibles as $cesta): ?>
                        <div class="gc-cesta-card" data-id="<?php echo esc_attr($cesta['id']); ?>">
                            <?php if (!empty($cesta['imagen'])): ?>
                                <img src="<?php echo esc_url($cesta['imagen']); ?>" alt="<?php echo esc_attr($cesta['nombre']); ?>" class="gc-cesta-imagen">
                            <?php else: ?>
                                <div class="gc-cesta-imagen-placeholder">
                                    <?php
                                    $iconos = [
                                        'mixta' => '🥗',
                                        'verduras' => '🥬',
                                        'fruta' => '🍎',
                                        'lacteos' => '🧀',
                                        'personalizada' => '✨',
                                    ];
                                    echo $iconos[$cesta['slug']] ?? '🧺';
                                    ?>
                                </div>
                            <?php endif; ?>

                            <div class="gc-cesta-info">
                                <h4><?php echo esc_html($cesta['nombre']); ?></h4>
                                <p><?php echo esc_html($cesta['descripcion']); ?></p>
                            </div>

                            <div class="gc-cesta-footer">
                                <span class="gc-cesta-precio">
                                    <?php if ($cesta['precio_base'] > 0): ?>
                                        <?php echo number_format($cesta['precio_base'], 2, ',', '.'); ?> €
                                    <?php else: ?>
                                        <?php _e('Variable', 'flavor-platform'); ?>
                                    <?php endif; ?>
                                </span>

                                <button type="button" class="gc-btn gc-btn-primary gc-btn-sm gc-btn-suscribir" data-cesta="<?php echo esc_attr($cesta['id']); ?>">
                                    <?php _e('Suscribirme', 'flavor-platform'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de suscripción -->
<div class="gc-modal gc-modal-suscripcion" id="gc-modal-suscripcion" style="display: none;">
    <div class="gc-modal-content">
        <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', 'flavor-platform'); ?></button>
        <div class="gc-modal-header">
            <h3><?php _e('Suscribirse a cesta', 'flavor-platform'); ?></h3>
        </div>
        <div class="gc-modal-body">
            <form id="gc-form-suscripcion">
                <input type="hidden" name="cesta_id" id="gc-suscripcion-cesta-id">

                <div class="gc-form-grupo">
                    <label for="gc-suscripcion-frecuencia"><?php _e('Frecuencia de entrega:', 'flavor-platform'); ?></label>
                    <select name="frecuencia" id="gc-suscripcion-frecuencia" required>
                        <option value="<?php echo esc_attr__('semanal', 'flavor-platform'); ?>"><?php _e('Semanal', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('quincenal', 'flavor-platform'); ?>"><?php _e('Quincenal', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('mensual', 'flavor-platform'); ?>"><?php _e('Mensual', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="gc-form-grupo">
                    <label for="gc-suscripcion-inicio"><?php _e('Fecha de inicio:', 'flavor-platform'); ?></label>
                    <input type="date" name="fecha_inicio" id="gc-suscripcion-inicio" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="gc-form-grupo">
                    <label for="gc-suscripcion-notas"><?php _e('Notas o preferencias:', 'flavor-platform'); ?></label>
                    <textarea name="notas" id="gc-suscripcion-notas" rows="3" placeholder="<?php esc_attr_e('Alergias, preferencias, instrucciones especiales...', 'flavor-platform'); ?>"></textarea>
                </div>

                <div class="gc-modal-acciones">
                    <button type="button" class="gc-btn gc-btn-secondary gc-modal-cancelar"><?php _e('Cancelar', 'flavor-platform'); ?></button>
                    <button type="submit" class="gc-btn gc-btn-primary"><?php _e('Confirmar suscripción', 'flavor-platform'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
