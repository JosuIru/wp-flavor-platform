<?php
/**
 * Vista: Incidencias y Mantenimiento
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_incidencias = $wpdb->prefix . 'flavor_energia_incidencias';
$tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
$tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_prioridad = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';

$where = "WHERE 1=1";
if ($filtro_estado) {
    $where .= $wpdb->prepare(" AND i.estado = %s", $filtro_estado);
}
if ($filtro_prioridad) {
    $where .= $wpdb->prepare(" AND i.prioridad = %s", $filtro_prioridad);
}

// Obtener incidencias
$incidencias = [];
if (Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
    $incidencias = $wpdb->get_results(
        "SELECT i.*, inst.nombre as instalacion_nombre, c.nombre as comunidad_nombre,
                u.display_name as reportado_por_nombre
         FROM $tabla_incidencias i
         LEFT JOIN $tabla_instalaciones inst ON i.instalacion_id = inst.id
         LEFT JOIN $tabla_comunidades c ON i.energia_comunidad_id = c.id
         LEFT JOIN {$wpdb->users} u ON i.reportado_por = u.ID
         $where
         ORDER BY
            CASE i.prioridad WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END,
            i.created_at DESC"
    );
}

// Estadísticas
$stats = [
    'abiertas' => 0,
    'en_progreso' => 0,
    'resueltas_mes' => 0,
    'criticas' => 0,
];

if (Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
    $stats['abiertas'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'abierta'");
    $stats['en_progreso'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'en_progreso'");
    $stats['resueltas_mes'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'resuelta' AND DATE_FORMAT(updated_at, '%%Y-%%m') = %s",
        date('Y-m')
    ));
    $stats['criticas'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE prioridad = 'critica' AND estado NOT IN ('resuelta', 'cerrada')");
}

$prioridades = [
    'critica' => ['label' => __('Crítica', 'flavor-platform'), 'color' => '#ef4444', 'icon' => '🔴'],
    'alta' => ['label' => __('Alta', 'flavor-platform'), 'color' => '#f59e0b', 'icon' => '🟠'],
    'media' => ['label' => __('Media', 'flavor-platform'), 'color' => '#3b82f6', 'icon' => '🔵'],
    'baja' => ['label' => __('Baja', 'flavor-platform'), 'color' => '#10b981', 'icon' => '🟢'],
];

$estados = [
    'abierta' => ['label' => __('Abierta', 'flavor-platform'), 'color' => '#ef4444'],
    'en_progreso' => ['label' => __('En progreso', 'flavor-platform'), 'color' => '#f59e0b'],
    'pendiente_piezas' => ['label' => __('Pendiente piezas', 'flavor-platform'), 'color' => '#8b5cf6'],
    'resuelta' => ['label' => __('Resuelta', 'flavor-platform'), 'color' => '#10b981'],
    'cerrada' => ['label' => __('Cerrada', 'flavor-platform'), 'color' => '#6b7280'],
];
?>

<div class="energia-incidencias" x-data="energiaIncidencias()">
    <!-- Stats rápidos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div style="background: #fee2e2; padding: 16px; border-radius: 8px; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #dc2626;"><?php echo $stats['abiertas']; ?></div>
            <div style="font-size: 12px; color: #991b1b;"><?php esc_html_e('Abiertas', 'flavor-platform'); ?></div>
        </div>
        <div style="background: #fef3c7; padding: 16px; border-radius: 8px; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #d97706;"><?php echo $stats['en_progreso']; ?></div>
            <div style="font-size: 12px; color: #92400e;"><?php esc_html_e('En progreso', 'flavor-platform'); ?></div>
        </div>
        <div style="background: #dcfce7; padding: 16px; border-radius: 8px; text-align: center;">
            <div style="font-size: 28px; font-weight: bold; color: #16a34a;"><?php echo $stats['resueltas_mes']; ?></div>
            <div style="font-size: 12px; color: #166534;"><?php esc_html_e('Resueltas (mes)', 'flavor-platform'); ?></div>
        </div>
        <?php if ($stats['criticas'] > 0): ?>
        <div style="background: #fecaca; padding: 16px; border-radius: 8px; text-align: center; animation: pulse 2s infinite;">
            <div style="font-size: 28px; font-weight: bold; color: #b91c1c;"><?php echo $stats['criticas']; ?></div>
            <div style="font-size: 12px; color: #7f1d1d;"><?php esc_html_e('Críticas', 'flavor-platform'); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
        <select x-model="filtroEstado" @change="filtrar()" style="padding: 8px 12px;">
            <option value=""><?php esc_html_e('Todos los estados', 'flavor-platform'); ?></option>
            <?php foreach ($estados as $key => $est): ?>
                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($est['label']); ?></option>
            <?php endforeach; ?>
        </select>

        <select x-model="filtroPrioridad" @change="filtrar()" style="padding: 8px 12px;">
            <option value=""><?php esc_html_e('Todas las prioridades', 'flavor-platform'); ?></option>
            <?php foreach ($prioridades as $key => $pri): ?>
                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($pri['icon'] . ' ' . $pri['label']); ?></option>
            <?php endforeach; ?>
        </select>

        <div style="flex: 1;"></div>

        <button class="button button-primary" @click="showModalIncidencia = true">
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('Reportar Incidencia', 'flavor-platform'); ?>
        </button>
    </div>

    <!-- Lista de incidencias -->
    <?php if ($incidencias): ?>
    <div class="incidencias-list" style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($incidencias as $inc):
            $pri_info = $prioridades[$inc->prioridad] ?? $prioridades['media'];
            $est_info = $estados[$inc->estado] ?? $estados['abierta'];
        ?>
        <div class="incidencia-card" style="background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid <?php echo esc_attr($pri_info['color']); ?>; border-radius: 8px; padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div>
                    <h3 style="margin: 0 0 4px; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <span><?php echo $pri_info['icon']; ?></span>
                        <?php echo esc_html($inc->titulo); ?>
                    </h3>
                    <div style="font-size: 13px; color: #666;">
                        <?php if ($inc->instalacion_nombre): ?>
                            <span class="dashicons dashicons-admin-tools" style="font-size: 14px;"></span>
                            <?php echo esc_html($inc->instalacion_nombre); ?>
                            &nbsp;•&nbsp;
                        <?php endif; ?>
                        <?php echo esc_html($inc->comunidad_nombre); ?>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="background: <?php echo esc_attr($est_info['color']); ?>20; color: <?php echo esc_attr($est_info['color']); ?>;
                           padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                        <?php echo esc_html($est_info['label']); ?>
                    </span>
                    <span style="background: <?php echo esc_attr($pri_info['color']); ?>20; color: <?php echo esc_attr($pri_info['color']); ?>;
                           padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                        <?php echo esc_html($pri_info['label']); ?>
                    </span>
                </div>
            </div>

            <?php if ($inc->descripcion): ?>
            <p style="margin: 0 0 12px; color: #4b5563; font-size: 14px; line-height: 1.5;">
                <?php echo esc_html(wp_trim_words($inc->descripcion, 30)); ?>
            </p>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                <div style="font-size: 12px; color: #9ca3af;">
                    <?php esc_html_e('Reportado por', 'flavor-platform'); ?>
                    <strong><?php echo esc_html($inc->reportado_por_nombre ?: __('Anónimo', 'flavor-platform')); ?></strong>
                    •
                    <?php echo esc_html(human_time_diff(strtotime($inc->created_at), current_time('timestamp'))); ?>
                </div>

                <div style="display: flex; gap: 8px;">
                    <?php if ($inc->estado !== 'resuelta' && $inc->estado !== 'cerrada'): ?>
                    <button class="button button-small" @click="cambiarEstado(<?php echo $inc->id; ?>, 'en_progreso')">
                        <?php esc_html_e('En progreso', 'flavor-platform'); ?>
                    </button>
                    <button class="button button-small button-primary" @click="cambiarEstado(<?php echo $inc->id; ?>, 'resuelta')">
                        <?php esc_html_e('Resolver', 'flavor-platform'); ?>
                    </button>
                    <?php endif; ?>
                    <button class="button button-small" @click="verDetalle(<?php echo $inc->id; ?>)">
                        <?php esc_html_e('Ver', 'flavor-platform'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f0fdf4; border-radius: 12px;">
        <span style="font-size: 48px;">✅</span>
        <h3 style="color: #166534;"><?php esc_html_e('Sin incidencias pendientes', 'flavor-platform'); ?></h3>
        <p style="color: #15803d;"><?php esc_html_e('Todas las instalaciones funcionan correctamente.', 'flavor-platform'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Modal Reportar Incidencia -->
    <div x-show="showModalIncidencia" x-cloak
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"
         @click.self="showModalIncidencia = false">
        <div style="background: #fff; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin: 0 0 20px;"><?php esc_html_e('Reportar Incidencia', 'flavor-platform'); ?></h2>
            <?php echo do_shortcode('[flavor_energia_form_incidencia]'); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('energiaIncidencias', () => ({
        filtroEstado: '<?php echo esc_js($filtro_estado); ?>',
        filtroPrioridad: '<?php echo esc_js($filtro_prioridad); ?>',
        showModalIncidencia: false,

        filtrar() {
            const params = new URLSearchParams(window.location.search);
            if (this.filtroEstado) params.set('estado', this.filtroEstado); else params.delete('estado');
            if (this.filtroPrioridad) params.set('prioridad', this.filtroPrioridad); else params.delete('prioridad');
            window.location.search = params.toString();
        },

        cambiarEstado(id, estado) {
            // AJAX para cambiar estado
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'energia_comunitaria_actualizar_incidencia',
                    incidencia_id: id,
                    estado: estado,
                    _wpnonce: '<?php echo wp_create_nonce('energia_incidencia'); ?>'
                })
            }).then(() => location.reload());
        },

        verDetalle(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-energia-incidencia&id='); ?>' + id;
        }
    }));
});
</script>

<style>
.energia-incidencias [x-cloak] { display: none !important; }
.incidencia-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
</style>
