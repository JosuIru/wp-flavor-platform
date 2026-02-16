<?php
/**
 * Vista Gestión de Talleres
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';

$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';

$where = ['1=1'];
$prepare_values = [];

if (!empty($filtro_estado)) {
    $where[] = 't.estado = %s';
    $prepare_values[] = $filtro_estado;
}

if (!empty($filtro_categoria)) {
    $where[] = 't.categoria = %s';
    $prepare_values[] = $filtro_categoria;
}

$where_sql = implode(' AND ', $where);

$query = "SELECT t.*, u.display_name as organizador
          FROM $tabla_talleres t
          LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
          WHERE $where_sql
          ORDER BY t.fecha_creacion DESC
          LIMIT 50";

$talleres = empty($prepare_values)
    ? $wpdb->get_results($query)
    : $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));

$categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_talleres WHERE categoria IS NOT NULL");

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Talleres', 'flavor-chat-ia'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=nuevo')); ?>" class="page-title-action"><?php echo esc_html__('Añadir Nuevo', 'flavor-chat-ia'); ?></a>
    <hr class="wp-header-end">

    <div class="flavor-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-chat-talleres', 'flavor-chat-ia'); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr__('talleres', 'flavor-chat-ia'); ?>">
            <select name="estado">
                <option value=""><?php echo esc_html__('Todos los estados', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('borrador', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'borrador'); ?>><?php echo esc_html__('Borrador', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('publicado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'publicado'); ?>><?php echo esc_html__('Publicado', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('confirmado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'confirmado'); ?>><?php echo esc_html__('Confirmado', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('en_curso', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'en_curso'); ?>><?php echo esc_html__('En curso', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('finalizado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'finalizado'); ?>><?php echo esc_html__('Finalizado', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('cancelado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'cancelado'); ?>><?php echo esc_html__('Cancelado', 'flavor-chat-ia'); ?></option>
            </select>
            <select name="categoria">
                <option value=""><?php echo esc_html__('Todas las categorías', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($filtro_categoria, $cat); ?>>
                        <?php echo esc_html($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>
        </form>
    </div>

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Organizador', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Participantes', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($talleres)): ?>
                    <?php foreach ($talleres as $t): ?>
                        <tr>
                            <td><?php echo $t->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($t->titulo); ?></strong>
                                <br><small class="flavor-text-muted"><?php echo esc_html(wp_trim_words($t->descripcion, 10)); ?></small>
                            </td>
                            <td><?php echo esc_html($t->organizador); ?></td>
                            <td><span class="flavor-badge"><?php echo esc_html($t->categoria); ?></span></td>
                            <td><?php echo $t->inscritos_actuales; ?>/<?php echo $t->max_participantes; ?></td>
                            <td><?php echo $t->precio > 0 ? number_format($t->precio, 2) . '€' : 'Gratis'; ?></td>
                            <td><span class="flavor-badge flavor-badge-<?php echo $t->estado === 'confirmado' ? 'success' : 'info'; ?>"><?php echo ucfirst($t->estado); ?></span></td>
                            <td><button class="button button-small"><?php echo esc_html__('Editar', 'flavor-chat-ia'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="flavor-no-data"><?php echo esc_html__('No se encontraron talleres', 'flavor-chat-ia'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
