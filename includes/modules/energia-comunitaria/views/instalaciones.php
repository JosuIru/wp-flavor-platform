<?php
/**
 * Vista: Instalaciones Energéticas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
$tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';

// Filtros
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_comunidad = isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0;

// Construir query
$where = "WHERE 1=1";
if ($filtro_tipo) {
    $where .= $wpdb->prepare(" AND i.tipo = %s", $filtro_tipo);
}
if ($filtro_estado) {
    $where .= $wpdb->prepare(" AND i.estado = %s", $filtro_estado);
}
if ($filtro_comunidad) {
    $where .= $wpdb->prepare(" AND i.energia_comunidad_id = %d", $filtro_comunidad);
}

$instalaciones = [];
if (Flavor_Chat_Helpers::tabla_existe($tabla_instalaciones)) {
    $instalaciones = $wpdb->get_results(
        "SELECT i.*, c.nombre as comunidad_nombre
         FROM $tabla_instalaciones i
         LEFT JOIN $tabla_comunidades c ON i.energia_comunidad_id = c.id
         $where
         ORDER BY i.created_at DESC"
    );
}

// Tipos de instalación
$tipos = [
    'solar_fotovoltaica' => ['label' => __('Solar Fotovoltaica', 'flavor-chat-ia'), 'icon' => '☀️', 'color' => '#f59e0b'],
    'solar_termica' => ['label' => __('Solar Térmica', 'flavor-chat-ia'), 'icon' => '🌡️', 'color' => '#ef4444'],
    'eolica' => ['label' => __('Eólica', 'flavor-chat-ia'), 'icon' => '💨', 'color' => '#3b82f6'],
    'biomasa' => ['label' => __('Biomasa', 'flavor-chat-ia'), 'icon' => '🌿', 'color' => '#10b981'],
    'hidraulica' => ['label' => __('Hidráulica', 'flavor-chat-ia'), 'icon' => '💧', 'color' => '#06b6d4'],
    'bateria' => ['label' => __('Batería/Almacenamiento', 'flavor-chat-ia'), 'icon' => '🔋', 'color' => '#8b5cf6'],
    'punto_recarga' => ['label' => __('Punto de Recarga', 'flavor-chat-ia'), 'icon' => '⚡', 'color' => '#ec4899'],
    'otro' => ['label' => __('Otro', 'flavor-chat-ia'), 'icon' => '⚙️', 'color' => '#6b7280'],
];

// Comunidades para filtro
$comunidades = $wpdb->get_results("SELECT id, nombre FROM $tabla_comunidades WHERE estado = 'activa' ORDER BY nombre");
?>

<div class="energia-instalaciones" x-data="energiaInstalaciones()">
    <!-- Filtros -->
    <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
        <select x-model="filtroTipo" @change="filtrar()" style="padding: 8px 12px;">
            <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
            <?php foreach ($tipos as $key => $tipo): ?>
                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($tipo['icon'] . ' ' . $tipo['label']); ?></option>
            <?php endforeach; ?>
        </select>

        <select x-model="filtroEstado" @change="filtrar()" style="padding: 8px 12px;">
            <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
            <option value="activa"><?php esc_html_e('Activa', 'flavor-chat-ia'); ?></option>
            <option value="mantenimiento"><?php esc_html_e('En mantenimiento', 'flavor-chat-ia'); ?></option>
            <option value="inactiva"><?php esc_html_e('Inactiva', 'flavor-chat-ia'); ?></option>
        </select>

        <?php if ($comunidades): ?>
        <select x-model="filtroComunidad" @change="filtrar()" style="padding: 8px 12px;">
            <option value=""><?php esc_html_e('Todas las comunidades', 'flavor-chat-ia'); ?></option>
            <?php foreach ($comunidades as $com): ?>
                <option value="<?php echo esc_attr($com->id); ?>"><?php echo esc_html($com->nombre); ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <div style="flex: 1;"></div>

        <button class="button button-primary" @click="showModalInstalacion = true">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nueva Instalación', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Grid de instalaciones -->
    <?php if ($instalaciones): ?>
    <div class="instalaciones-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
        <?php foreach ($instalaciones as $inst):
            $tipo_info = $tipos[$inst->tipo] ?? $tipos['otro'];
        ?>
        <div class="instalacion-card" style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #fff;">
            <div style="background: <?php echo esc_attr($tipo_info['color']); ?>; color: #fff; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 28px;"><?php echo $tipo_info['icon']; ?></span>
                    <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                        <?php echo esc_html(ucfirst($inst->estado)); ?>
                    </span>
                </div>
                <h3 style="margin: 10px 0 0; font-size: 16px;"><?php echo esc_html($inst->nombre); ?></h3>
            </div>

            <div style="padding: 16px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: 11px; color: #666; text-transform: uppercase;"><?php esc_html_e('Potencia', 'flavor-chat-ia'); ?></div>
                        <div style="font-size: 18px; font-weight: bold;"><?php echo number_format($inst->potencia_kw, 2); ?> kW</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #666; text-transform: uppercase;"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></div>
                        <div style="font-size: 14px;"><?php echo esc_html($tipo_info['label']); ?></div>
                    </div>
                </div>

                <?php if ($inst->comunidad_nombre): ?>
                <div style="background: #f3f4f6; padding: 8px 12px; border-radius: 8px; margin-bottom: 12px;">
                    <span class="dashicons dashicons-groups" style="color: #666; font-size: 14px;"></span>
                    <span style="font-size: 13px;"><?php echo esc_html($inst->comunidad_nombre); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($inst->ubicacion): ?>
                <div style="font-size: 13px; color: #666; margin-bottom: 12px;">
                    <span class="dashicons dashicons-location" style="font-size: 14px;"></span>
                    <?php echo esc_html($inst->ubicacion); ?>
                </div>
                <?php endif; ?>

                <div style="display: flex; gap: 8px;">
                    <button class="button" style="flex: 1;" @click="editarInstalacion(<?php echo $inst->id; ?>)">
                        <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="button" @click="registrarLectura(<?php echo $inst->id; ?>)">
                        <span class="dashicons dashicons-chart-area"></span>
                    </button>
                    <button class="button" @click="reportarIncidencia(<?php echo $inst->id; ?>)">
                        <span class="dashicons dashicons-warning"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 12px;">
        <span style="font-size: 64px;">⚡</span>
        <h3><?php esc_html_e('No hay instalaciones registradas', 'flavor-chat-ia'); ?></h3>
        <p style="color: #666;"><?php esc_html_e('Añade tu primera instalación energética para empezar a gestionar la producción.', 'flavor-chat-ia'); ?></p>
        <button class="button button-primary button-hero" @click="showModalInstalacion = true">
            <?php esc_html_e('Añadir Instalación', 'flavor-chat-ia'); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Modal Nueva Instalación -->
    <div x-show="showModalInstalacion" x-cloak
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"
         @click.self="showModalInstalacion = false">
        <div style="background: #fff; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;"><?php esc_html_e('Nueva Instalación', 'flavor-chat-ia'); ?></h2>
                <button @click="showModalInstalacion = false" style="background: none; border: none; cursor: pointer; font-size: 20px;">&times;</button>
            </div>
            <?php echo do_shortcode('[flavor_energia_form_instalacion]'); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('energiaInstalaciones', () => ({
        filtroTipo: '<?php echo esc_js($filtro_tipo); ?>',
        filtroEstado: '<?php echo esc_js($filtro_estado); ?>',
        filtroComunidad: '<?php echo esc_js($filtro_comunidad); ?>',
        showModalInstalacion: false,

        filtrar() {
            const params = new URLSearchParams(window.location.search);
            if (this.filtroTipo) params.set('tipo', this.filtroTipo); else params.delete('tipo');
            if (this.filtroEstado) params.set('estado', this.filtroEstado); else params.delete('estado');
            if (this.filtroComunidad) params.set('comunidad', this.filtroComunidad); else params.delete('comunidad');
            window.location.search = params.toString();
        },

        editarInstalacion(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-energia-instalacion&id='); ?>' + id;
        },

        registrarLectura(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-energia-lectura&instalacion='); ?>' + id;
        },

        reportarIncidencia(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-energia-incidencia&instalacion='); ?>' + id;
        }
    }));
});
</script>

<style>
.instalacion-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
    transition: all 0.2s;
}
.energia-instalaciones [x-cloak] {
    display: none !important;
}
</style>
