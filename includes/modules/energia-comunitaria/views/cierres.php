<?php
/**
 * Vista: Cierres y Liquidaciones de Reparto
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_cierres = $wpdb->prefix . 'flavor_energia_repartos_cierre';
$tabla_detalle = $wpdb->prefix . 'flavor_energia_repartos_detalle';
$tabla_liquidaciones = $wpdb->prefix . 'flavor_energia_liquidaciones';
$tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';

// Obtener cierres
$cierres = [];
if (Flavor_Chat_Helpers::tabla_existe($tabla_cierres)) {
    $cierres = $wpdb->get_results(
        "SELECT c.*, ec.nombre as comunidad_nombre,
                (SELECT COUNT(*) FROM $tabla_detalle d WHERE d.cierre_id = c.id) as num_participantes,
                (SELECT SUM(kwh_asignados) FROM $tabla_detalle d WHERE d.cierre_id = c.id) as total_kwh_repartidos
         FROM $tabla_cierres c
         LEFT JOIN $tabla_comunidades ec ON c.energia_comunidad_id = ec.id
         ORDER BY c.periodo DESC, c.created_at DESC
         LIMIT 24"
    );
}

// Estadísticas
$stats = [
    'total_cierres' => count($cierres),
    'pendientes_liquidar' => 0,
    'kwh_repartidos_ano' => 0,
    'ahorro_total_ano' => 0,
];

if (Flavor_Chat_Helpers::tabla_existe($tabla_cierres)) {
    $stats['pendientes_liquidar'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cierres WHERE estado = 'pendiente'");
    $stats['kwh_repartidos_ano'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(kwh_total_periodo), 0) FROM $tabla_cierres WHERE YEAR(periodo) = %d",
        date('Y')
    ));
    $stats['ahorro_total_ano'] = $stats['kwh_repartidos_ano'] * 0.18;
}

// Comunidades para selector
$comunidades = $wpdb->get_results("SELECT id, nombre FROM $tabla_comunidades WHERE estado = 'activa' ORDER BY nombre");

$estados_cierre = [
    'borrador' => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => '#6b7280'],
    'pendiente' => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => '#f59e0b'],
    'procesando' => ['label' => __('Procesando', 'flavor-chat-ia'), 'color' => '#3b82f6'],
    'completado' => ['label' => __('Completado', 'flavor-chat-ia'), 'color' => '#10b981'],
    'anulado' => ['label' => __('Anulado', 'flavor-chat-ia'), 'color' => '#ef4444'],
];
?>

<div class="energia-cierres" x-data="energiaCierres()">
    <!-- KPIs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Cierres este año', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo $stats['total_cierres']; ?></div>
        </div>

        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Pendientes liquidar', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo $stats['pendientes_liquidar']; ?></div>
        </div>

        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('kWh repartidos (año)', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo number_format($stats['kwh_repartidos_ano'], 0); ?></div>
        </div>

        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Ahorro total (año)', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo number_format($stats['ahorro_total_ano'], 0); ?> €</div>
        </div>
    </div>

    <!-- Acciones -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;"><?php esc_html_e('Histórico de Cierres', 'flavor-chat-ia'); ?></h2>
        <button class="button button-primary" @click="showModalCierre = true">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nuevo Cierre de Período', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Lista de cierres -->
    <?php if ($cierres): ?>
    <div class="cierres-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 16px;">
        <?php foreach ($cierres as $cierre):
            $estado_info = $estados_cierre[$cierre->estado] ?? $estados_cierre['pendiente'];
            $periodo_format = date_i18n('F Y', strtotime($cierre->periodo . '-01'));
        ?>
        <div class="cierre-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <div style="background: #f9fafb; padding: 16px; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px;"><?php echo esc_html(ucfirst($periodo_format)); ?></h3>
                        <span style="font-size: 13px; color: #666;"><?php echo esc_html($cierre->comunidad_nombre); ?></span>
                    </div>
                    <span style="background: <?php echo esc_attr($estado_info['color']); ?>20; color: <?php echo esc_attr($estado_info['color']); ?>;
                           padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                        <?php echo esc_html($estado_info['label']); ?>
                    </span>
                </div>
            </div>

            <div style="padding: 16px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
                    <div style="text-align: center;">
                        <div style="font-size: 20px; font-weight: bold; color: #10b981;">
                            <?php echo number_format($cierre->kwh_total_periodo, 1); ?>
                        </div>
                        <div style="font-size: 11px; color: #666;"><?php esc_html_e('kWh generados', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 20px; font-weight: bold; color: #3b82f6;">
                            <?php echo intval($cierre->num_participantes); ?>
                        </div>
                        <div style="font-size: 11px; color: #666;"><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 20px; font-weight: bold; color: #8b5cf6;">
                            <?php echo number_format($cierre->total_kwh_repartidos * 0.18, 0); ?>€
                        </div>
                        <div style="font-size: 11px; color: #666;"><?php esc_html_e('Valor', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>

                <?php if ($cierre->modelo_reparto): ?>
                <div style="background: #f3f4f6; padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; font-size: 13px;">
                    <strong><?php esc_html_e('Modelo:', 'flavor-chat-ia'); ?></strong>
                    <?php echo esc_html(ucfirst($cierre->modelo_reparto)); ?>
                </div>
                <?php endif; ?>

                <div style="display: flex; gap: 8px;">
                    <button class="button" style="flex: 1;" @click="verDetalleCierre(<?php echo $cierre->id; ?>)">
                        <?php esc_html_e('Ver detalle', 'flavor-chat-ia'); ?>
                    </button>
                    <?php if ($cierre->estado === 'pendiente'): ?>
                    <button class="button button-primary" @click="procesarCierre(<?php echo $cierre->id; ?>)">
                        <?php esc_html_e('Procesar', 'flavor-chat-ia'); ?>
                    </button>
                    <?php endif; ?>
                    <a href="<?php echo admin_url('admin-post.php?action=energia_comunitaria_exportar_liquidacion&cierre_id=' . $cierre->id . '&_wpnonce=' . wp_create_nonce('export_liquidacion')); ?>"
                       class="button" title="<?php esc_attr_e('Exportar CSV', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 12px;">
        <span class="dashicons dashicons-chart-pie" style="font-size: 48px; color: #ccc;"></span>
        <h3><?php esc_html_e('Sin cierres registrados', 'flavor-chat-ia'); ?></h3>
        <p style="color: #666;"><?php esc_html_e('Crea el primer cierre de período para repartir la energía generada.', 'flavor-chat-ia'); ?></p>
        <button class="button button-primary" @click="showModalCierre = true">
            <?php esc_html_e('Crear Cierre', 'flavor-chat-ia'); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Información sobre modelos de reparto -->
    <div style="margin-top: 24px; padding: 20px; background: #eff6ff; border-radius: 12px; border: 1px solid #bfdbfe;">
        <h4 style="margin: 0 0 12px; color: #1e40af;">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e('Modelos de reparto disponibles', 'flavor-chat-ia'); ?>
        </h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div>
                <strong><?php esc_html_e('Proporcional', 'flavor-chat-ia'); ?></strong>
                <p style="margin: 4px 0 0; font-size: 13px; color: #1e3a8a;">
                    <?php esc_html_e('Reparto según el coeficiente de cada participante (inversión, consumo estimado, etc.)', 'flavor-chat-ia'); ?>
                </p>
            </div>
            <div>
                <strong><?php esc_html_e('Igualitario', 'flavor-chat-ia'); ?></strong>
                <p style="margin: 4px 0 0; font-size: 13px; color: #1e3a8a;">
                    <?php esc_html_e('Todos los participantes reciben la misma cantidad de energía.', 'flavor-chat-ia'); ?>
                </p>
            </div>
            <div>
                <strong><?php esc_html_e('Por consumo real', 'flavor-chat-ia'); ?></strong>
                <p style="margin: 4px 0 0; font-size: 13px; color: #1e3a8a;">
                    <?php esc_html_e('Se prioriza el autoconsumo y se reparte el excedente.', 'flavor-chat-ia'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Cierre -->
    <div x-show="showModalCierre" x-cloak
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"
         @click.self="showModalCierre = false">
        <div style="background: #fff; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin: 0 0 20px;"><?php esc_html_e('Nuevo Cierre de Período', 'flavor-chat-ia'); ?></h2>
            <?php echo do_shortcode('[flavor_energia_form_cierre]'); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('energiaCierres', () => ({
        showModalCierre: false,

        verDetalleCierre(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-energia-cierre&id='); ?>' + id;
        },

        procesarCierre(id) {
            if (!confirm('<?php echo esc_js(__('¿Procesar este cierre? Se generarán las liquidaciones para cada participante.', 'flavor-chat-ia')); ?>')) {
                return;
            }

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'energia_comunitaria_cerrar_reparto',
                    cierre_id: id,
                    _wpnonce: '<?php echo wp_create_nonce('energia_cierre'); ?>'
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data?.message || 'Error al procesar');
                }
            });
        }
    }));
});
</script>

<style>
.energia-cierres [x-cloak] { display: none !important; }
.cierre-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
</style>
