<?php
/**
 * Vista: Participantes de Comunidades Energéticas
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_participantes = $wpdb->prefix . 'flavor_energia_participantes';
$tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';

$energia_comunidad_id = isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0;

// Obtener participantes
$where = "WHERE p.estado = 'activo'";
if ($energia_comunidad_id) {
    $where .= $wpdb->prepare(" AND p.energia_comunidad_id = %d", $energia_comunidad_id);
}

$participantes = [];
if (Flavor_Platform_Helpers::tabla_existe($tabla_participantes)) {
    $participantes = $wpdb->get_results(
        "SELECT p.*, c.nombre as comunidad_nombre, u.display_name, u.user_email
         FROM $tabla_participantes p
         LEFT JOIN $tabla_comunidades c ON p.energia_comunidad_id = c.id
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
         $where
         ORDER BY p.coeficiente_reparto DESC, p.created_at ASC"
    );
}

// Comunidades para filtro
$comunidades = $wpdb->get_results("SELECT id, nombre FROM $tabla_comunidades WHERE estado = 'activa' ORDER BY nombre");

// Calcular totales
$total_coeficiente = array_sum(array_column($participantes, 'coeficiente_reparto'));
?>

<div class="energia-participantes" x-data="energiaParticipantes()">
    <!-- Filtros y acciones -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <select @change="filtrarComunidad($event.target.value)" style="padding: 8px 12px;">
                <option value=""><?php esc_html_e('Todas las comunidades', 'flavor-platform'); ?></option>
                <?php foreach ($comunidades as $com): ?>
                    <option value="<?php echo esc_attr($com->id); ?>" <?php selected($energia_comunidad_id, $com->id); ?>>
                        <?php echo esc_html($com->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <span style="color: #666; font-size: 13px;">
                <?php echo count($participantes); ?> <?php esc_html_e('participantes', 'flavor-platform'); ?>
            </span>
        </div>

        <button class="button button-primary" @click="showModalParticipante = true">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Añadir Participante', 'flavor-platform'); ?>
        </button>
    </div>

    <!-- Tabla de participantes -->
    <?php if ($participantes): ?>
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table class="wp-list-table widefat fixed striped" style="margin: 0;">
            <thead>
                <tr>
                    <th style="width: 50px;"></th>
                    <th><?php esc_html_e('Participante', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Comunidad', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Rol', 'flavor-platform'); ?></th>
                    <th style="text-align: center;"><?php esc_html_e('Coeficiente', 'flavor-platform'); ?></th>
                    <th style="text-align: center;"><?php esc_html_e('% Reparto', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Desde', 'flavor-platform'); ?></th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participantes as $p):
                    $porcentaje = $total_coeficiente > 0 ? ($p->coeficiente_reparto / $total_coeficiente) * 100 : 0;
                ?>
                <tr>
                    <td>
                        <?php echo get_avatar($p->user_id, 36); ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($p->display_name ?: __('Usuario', 'flavor-platform')); ?></strong>
                        <br>
                        <span style="color: #666; font-size: 12px;"><?php echo esc_html($p->user_email); ?></span>
                    </td>
                    <td>
                        <span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo esc_html($p->comunidad_nombre); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $roles = [
                            'miembro' => ['label' => __('Miembro', 'flavor-platform'), 'color' => '#6b7280'],
                            'gestor' => ['label' => __('Gestor', 'flavor-platform'), 'color' => '#3b82f6'],
                            'administrador' => ['label' => __('Administrador', 'flavor-platform'), 'color' => '#10b981'],
                        ];
                        $rol_info = $roles[$p->rol] ?? $roles['miembro'];
                        ?>
                        <span style="background: <?php echo esc_attr($rol_info['color']); ?>20; color: <?php echo esc_attr($rol_info['color']); ?>;
                               padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                            <?php echo esc_html($rol_info['label']); ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-weight: bold;">
                        <?php echo number_format($p->coeficiente_reparto, 2); ?>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                            <div style="flex: 1; max-width: 60px; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo min(100, $porcentaje); ?>%; height: 100%; background: #f59e0b;"></div>
                            </div>
                            <span style="font-size: 13px; font-weight: 500;"><?php echo number_format($porcentaje, 1); ?>%</span>
                        </div>
                    </td>
                    <td style="color: #666; font-size: 13px;">
                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($p->created_at))); ?>
                    </td>
                    <td>
                        <button class="button button-small" @click="editarParticipante(<?php echo $p->id; ?>)">
                            <?php esc_html_e('Editar', 'flavor-platform'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f9fafb;">
                    <td colspan="4" style="text-align: right; font-weight: bold;">
                        <?php esc_html_e('Total:', 'flavor-platform'); ?>
                    </td>
                    <td style="text-align: center; font-weight: bold;">
                        <?php echo number_format($total_coeficiente, 2); ?>
                    </td>
                    <td style="text-align: center; font-weight: bold;">100%</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 12px;">
        <span class="dashicons dashicons-groups" style="font-size: 48px; color: #ccc;"></span>
        <h3><?php esc_html_e('No hay participantes registrados', 'flavor-platform'); ?></h3>
        <p style="color: #666;"><?php esc_html_e('Añade participantes a las comunidades energéticas para gestionar el reparto.', 'flavor-platform'); ?></p>
        <button class="button button-primary" @click="showModalParticipante = true">
            <?php esc_html_e('Añadir Participante', 'flavor-platform'); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Información sobre coeficientes -->
    <div style="margin-top: 20px; padding: 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
        <h4 style="margin: 0 0 8px; color: #92400e;"><?php esc_html_e('Sobre los coeficientes de reparto', 'flavor-platform'); ?></h4>
        <p style="margin: 0; color: #78350f; font-size: 13px;">
            <?php esc_html_e('El coeficiente de reparto determina qué porcentaje de la energía generada corresponde a cada participante. Se puede basar en la inversión realizada, consumo estimado, o acuerdo entre los miembros.', 'flavor-platform'); ?>
        </p>
    </div>

    <!-- Modal Añadir Participante -->
    <div x-show="showModalParticipante" x-cloak
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"
         @click.self="showModalParticipante = false">
        <div style="background: #fff; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin: 0 0 20px;"><?php esc_html_e('Añadir Participante', 'flavor-platform'); ?></h2>
            <?php echo do_shortcode('[flavor_energia_form_participante]'); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('energiaParticipantes', () => ({
        showModalParticipante: false,

        filtrarComunidad(id) {
            const params = new URLSearchParams(window.location.search);
            if (id) {
                params.set('comunidad', id);
            } else {
                params.delete('comunidad');
            }
            window.location.search = params.toString();
        },

        editarParticipante(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-energia-participante&id='); ?>' + id;
        }
    }));
});
</script>

<style>
.energia-participantes [x-cloak] { display: none !important; }
</style>
