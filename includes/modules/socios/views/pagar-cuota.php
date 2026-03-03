<?php
/**
 * Vista: Pagar cuota de socio
 *
 * @package FlavorChatIA
 * @var array  $cuotas_pendientes   Lista de cuotas pendientes
 * @var array  $socio               Datos del socio
 * @var array  $gateways            Métodos de pago disponibles
 * @var float  $total_pendiente     Total pendiente
 * @var int    $identificador_usuario ID del usuario actual
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-soc-pagar" data-socio="<?php echo esc_attr($socio['id'] ?? 0); ?>">
    <?php if (!$identificador_usuario): ?>
        <div class="flavor-soc-login-requerido">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php esc_html_e('Inicia sesión para ver tus cuotas', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Accede a tu cuenta para gestionar tus pagos de socio.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-soc-btn flavor-soc-btn-primary">
                <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php elseif (empty($socio)): ?>
        <div class="flavor-soc-no-socio">
            <span class="dashicons dashicons-info-outline"></span>
            <h3><?php esc_html_e('No eres socio aún', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Para acceder a esta sección necesitas ser socio de la cooperativa.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(home_url('/mi-portal/socios/unirse/')); ?>" class="flavor-soc-btn flavor-soc-btn-primary">
                <?php esc_html_e('Hacerse socio', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <!-- Mensaje de resultado si hay -->
        <?php
        $resultado_pago = isset($_GET['socios_pago']) ? sanitize_key($_GET['socios_pago']) : '';
        if ($resultado_pago === 'exitoso'): ?>
            <div class="flavor-soc-mensaje flavor-soc-mensaje-success">
                <span class="dashicons dashicons-yes-alt"></span>
                <p><?php esc_html_e('¡Pago realizado con éxito! Gracias por tu cuota.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php elseif ($resultado_pago === 'cancelado'): ?>
            <div class="flavor-soc-mensaje flavor-soc-mensaje-warning">
                <span class="dashicons dashicons-warning"></span>
                <p><?php esc_html_e('El pago ha sido cancelado. Puedes intentarlo de nuevo.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Info del socio -->
        <div class="flavor-soc-info-socio">
            <div class="flavor-soc-avatar">
                <?php echo get_avatar($identificador_usuario, 80); ?>
            </div>
            <div class="flavor-soc-info-datos">
                <h3><?php echo esc_html($socio['nombre']); ?></h3>
                <span class="flavor-soc-numero"><?php printf(esc_html__('Socio #%s', 'flavor-chat-ia'), esc_html($socio['numero'])); ?></span>
                <span class="flavor-soc-tipo"><?php echo esc_html($socio['tipo_label']); ?></span>
            </div>
        </div>

        <!-- Resumen -->
        <?php if ($total_pendiente > 0): ?>
            <div class="flavor-soc-resumen">
                <div class="flavor-soc-resumen-item">
                    <span class="label"><?php esc_html_e('Cuotas pendientes', 'flavor-chat-ia'); ?></span>
                    <span class="valor"><?php echo count($cuotas_pendientes); ?></span>
                </div>
                <div class="flavor-soc-resumen-item flavor-soc-resumen-total">
                    <span class="label"><?php esc_html_e('Total pendiente', 'flavor-chat-ia'); ?></span>
                    <span class="valor"><?php echo esc_html(number_format($total_pendiente, 2, ',', '.')); ?> €</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de cuotas -->
        <div class="flavor-soc-cuotas-lista">
            <h3><?php esc_html_e('Cuotas pendientes', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($cuotas_pendientes)): ?>
                <div class="flavor-soc-sin-cuotas">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php esc_html_e('¡Estás al día! No tienes cuotas pendientes.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-soc-cuotas-grid">
                    <?php foreach ($cuotas_pendientes as $cuota): ?>
                        <article class="flavor-soc-cuota" data-cuota="<?php echo esc_attr($cuota['id']); ?>">
                            <div class="flavor-soc-cuota-periodo">
                                <span class="periodo"><?php echo esc_html($cuota['periodo']); ?></span>
                                <span class="fecha-cargo">
                                    <?php printf(
                                        esc_html__('Cargo: %s', 'flavor-chat-ia'),
                                        esc_html(date_i18n('j M Y', strtotime($cuota['fecha_cargo'])))
                                    ); ?>
                                </span>
                            </div>
                            <div class="flavor-soc-cuota-importe">
                                <span class="importe"><?php echo esc_html(number_format($cuota['importe'], 2, ',', '.')); ?> €</span>
                                <span class="estado estado-<?php echo esc_attr($cuota['estado']); ?>">
                                    <?php echo esc_html(ucfirst($cuota['estado'])); ?>
                                </span>
                            </div>
                            <div class="flavor-soc-cuota-acciones">
                                <button type="button" class="flavor-soc-btn flavor-soc-btn-primary flavor-soc-btn-pagar"
                                        data-cuota="<?php echo esc_attr($cuota['id']); ?>"
                                        data-importe="<?php echo esc_attr($cuota['importe']); ?>">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    <?php esc_html_e('Pagar', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal de pago -->
        <div id="modal-pagar-cuota" class="flavor-soc-modal" style="display: none;">
            <div class="flavor-soc-modal-content">
                <div class="flavor-soc-modal-header">
                    <h3><?php esc_html_e('Selecciona método de pago', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="flavor-soc-modal-close">&times;</button>
                </div>

                <div class="flavor-soc-modal-body">
                    <div class="flavor-soc-pago-resumen">
                        <p class="flavor-soc-pago-periodo"></p>
                        <p class="flavor-soc-pago-importe"></p>
                    </div>

                    <div class="flavor-soc-metodos-pago">
                        <?php foreach ($gateways as $gateway_id => $gateway): ?>
                            <?php if ($gateway['activo']): ?>
                                <label class="flavor-soc-metodo-opcion">
                                    <input type="radio" name="metodo_pago" value="<?php echo esc_attr($gateway_id); ?>"
                                           <?php checked($gateway_id, 'manual'); ?>>
                                    <span class="flavor-soc-metodo-info">
                                        <span class="nombre"><?php echo esc_html($gateway['nombre']); ?></span>
                                        <span class="descripcion"><?php echo esc_html($gateway['descripcion']); ?></span>
                                    </span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Panel de pago manual -->
                    <div id="panel-pago-manual" class="flavor-soc-panel-pago">
                        <div class="flavor-soc-datos-bancarios">
                            <h4><?php esc_html_e('Datos para transferencia', 'flavor-chat-ia'); ?></h4>
                            <?php
                            $opciones_pago = get_option('flavor_socios_settings', []);
                            $iban = $opciones_pago['iban_cooperativa'] ?? '';
                            $titular = $opciones_pago['titular_cuenta'] ?? get_bloginfo('name');
                            $bizum = $opciones_pago['telefono_bizum'] ?? '';
                            ?>
                            <?php if ($iban): ?>
                                <div class="flavor-soc-dato">
                                    <span class="label"><?php esc_html_e('IBAN:', 'flavor-chat-ia'); ?></span>
                                    <span class="valor copiable" data-copiar="<?php echo esc_attr($iban); ?>">
                                        <?php echo esc_html($iban); ?>
                                        <button type="button" class="flavor-soc-btn-copiar" title="<?php esc_attr_e('Copiar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-clipboard"></span>
                                        </button>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-soc-dato">
                                <span class="label"><?php esc_html_e('Titular:', 'flavor-chat-ia'); ?></span>
                                <span class="valor"><?php echo esc_html($titular); ?></span>
                            </div>
                            <?php if ($bizum): ?>
                                <div class="flavor-soc-dato">
                                    <span class="label"><?php esc_html_e('Bizum:', 'flavor-chat-ia'); ?></span>
                                    <span class="valor copiable" data-copiar="<?php echo esc_attr($bizum); ?>">
                                        <?php echo esc_html($bizum); ?>
                                        <button type="button" class="flavor-soc-btn-copiar" title="<?php esc_attr_e('Copiar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-clipboard"></span>
                                        </button>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-soc-dato">
                                <span class="label"><?php esc_html_e('Concepto:', 'flavor-chat-ia'); ?></span>
                                <span class="valor referencia-pago"></span>
                            </div>
                        </div>

                        <div class="flavor-soc-confirmar-manual">
                            <p><?php esc_html_e('Una vez realizada la transferencia, introduce la referencia:', 'flavor-chat-ia'); ?></p>
                            <input type="text" id="referencia-pago-manual" class="flavor-soc-input"
                                   placeholder="<?php esc_attr_e('Referencia de la operación', 'flavor-chat-ia'); ?>">
                        </div>
                    </div>

                    <!-- Panel de pago con tarjeta -->
                    <div id="panel-pago-stripe" class="flavor-soc-panel-pago" style="display: none;">
                        <p><?php esc_html_e('Serás redirigido a la pasarela de pago segura.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <div class="flavor-soc-modal-footer">
                    <button type="button" class="flavor-soc-btn flavor-soc-btn-secondary flavor-soc-modal-cancelar">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" id="btn-confirmar-pago" class="flavor-soc-btn flavor-soc-btn-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Confirmar pago', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
