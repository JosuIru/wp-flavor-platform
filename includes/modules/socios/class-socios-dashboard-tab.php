<?php
/**
 * Dashboard Tab para Socios
 *
 * @package FlavorChatIA
 * @subpackage Modules\Socios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Socios_Dashboard_Tab {

    private static $instancia = null;

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function registrar_tabs($tabs) {
        $tabs['socios-mi-membresia'] = [
            'label' => __('Mi Membresía', 'flavor-chat-ia'),
            'icon' => 'id-alt',
            'callback' => [$this, 'render_tab_membresia'],
            'orden' => 10,
        ];

        $tabs['socios-cuotas'] = [
            'label' => __('Mis Cuotas', 'flavor-chat-ia'),
            'icon' => 'money-alt',
            'callback' => [$this, 'render_tab_cuotas'],
            'orden' => 11,
        ];

        return $tabs;
    }

    public function render_tab_membresia() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';

        $socio = null;
        $tipo_socio = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));

            if ($socio && Flavor_Chat_Helpers::tabla_existe($tabla_tipos)) {
                $tipo_socio = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$tabla_tipos} WHERE slug = %s",
                    $socio->tipo_socio
                ));
            }
        }

        ?>
        <div class="flavor-panel flavor-membresia-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-id-alt"></span> <?php esc_html_e('Mi Membresía', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (!$socio): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-id"></span>
                    <h3><?php esc_html_e('¡Únete como socio!', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Accede a beneficios exclusivos, descuentos y participa activamente.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('socios', 'unirse')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Hacerme socio', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-membresia-card">
                    <div class="membresia-header" style="background: <?php echo esc_attr($tipo_socio->color ?? '#3b82f6'); ?>">
                        <div class="membresia-tipo">
                            <span class="dashicons dashicons-<?php echo esc_attr($tipo_socio->icono ?? 'id'); ?>"></span>
                            <span><?php echo esc_html($tipo_socio->nombre ?? ucfirst($socio->tipo_socio)); ?></span>
                        </div>
                        <div class="membresia-numero">
                            <?php esc_html_e('Socio Nº', 'flavor-chat-ia'); ?> <?php echo esc_html($socio->numero_socio); ?>
                        </div>
                    </div>

                    <div class="membresia-body">
                        <div class="membresia-info">
                            <div class="info-item">
                                <span class="label"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></span>
                                <span class="valor"><?php echo esc_html($socio->nombre . ' ' . $socio->apellidos); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label"><?php esc_html_e('Fecha de Alta', 'flavor-chat-ia'); ?></span>
                                <span class="valor"><?php echo esc_html(date_i18n('d/m/Y', strtotime($socio->fecha_alta))); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></span>
                                <span class="valor">
                                    <span class="flavor-badge flavor-badge-<?php echo $socio->estado === 'activo' ? 'success' : 'warning'; ?>">
                                        <?php echo esc_html(ucfirst($socio->estado)); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="label"><?php esc_html_e('Cuota', 'flavor-chat-ia'); ?></span>
                                <span class="valor">
                                    <?php
                                    if ($socio->cuota_importe > 0) {
                                        echo esc_html(number_format_i18n($socio->cuota_importe, 2) . ' €/' . $socio->cuota_tipo);
                                    } else {
                                        esc_html_e('Gratuita', 'flavor-chat-ia');
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($tipo_socio->beneficios)): ?>
                            <div class="membresia-beneficios">
                                <h4><?php esc_html_e('Tus beneficios', 'flavor-chat-ia'); ?></h4>
                                <ul>
                                    <?php foreach (explode(',', $tipo_socio->beneficios) as $beneficio): ?>
                                        <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html(trim($beneficio)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="membresia-footer">
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('socios', 'mi-perfil')); ?>" class="flavor-btn flavor-btn-outline">
                            <?php esc_html_e('Actualizar datos', 'flavor-chat-ia'); ?>
                        </a>
                        <?php if (!$socio->carnet_emitido): ?>
                            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('socios', 'carnet')); ?>" class="flavor-btn flavor-btn-primary">
                                <?php esc_html_e('Solicitar carnet', 'flavor-chat-ia'); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('socios', 'carnet')); ?>" class="flavor-btn flavor-btn-secondary">
                                <?php esc_html_e('Ver carnet digital', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_tab_cuotas() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = null;
        $cuotas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));

            if ($socio && Flavor_Chat_Helpers::tabla_existe($tabla_cuotas)) {
                $cuotas = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$tabla_cuotas} WHERE socio_id = %d ORDER BY fecha_vencimiento DESC LIMIT 12",
                    $socio->id
                ));
            }
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'pagada' => 'success',
            'vencida' => 'danger',
            'cancelada' => 'secondary',
        ];

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Mis Cuotas', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (!$socio): ?>
                <div class="flavor-empty-state">
                    <p><?php esc_html_e('No eres socio todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php elseif (empty($cuotas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-money-alt"></span>
                    <p><?php esc_html_e('No tienes cuotas registradas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Concepto', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Período', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Vencimiento', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuotas as $cuota): ?>
                                <tr>
                                    <td><?php echo esc_html($cuota->concepto); ?></td>
                                    <td><?php echo esc_html($cuota->periodo); ?></td>
                                    <td><?php echo esc_html(number_format_i18n($cuota->importe, 2)); ?> €</td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($cuota->fecha_vencimiento))); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$cuota->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst($cuota->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cuota->estado === 'pendiente' || $cuota->estado === 'vencida'): ?>
                                            <a href="<?php echo esc_url(add_query_arg('cuota_id', $cuota->id, Flavor_Chat_Helpers::get_action_url('socios', 'pagar-cuota'))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                                <?php esc_html_e('Pagar', 'flavor-chat-ia'); ?>
                                            </a>
                                        <?php elseif ($cuota->factura_url): ?>
                                            <a href="<?php echo esc_url($cuota->factura_url); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline" target="_blank">
                                                <?php esc_html_e('Factura', 'flavor-chat-ia'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
