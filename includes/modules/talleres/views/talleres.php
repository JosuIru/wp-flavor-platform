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
    <h1 class="wp-heading-inline">Gestión de Talleres</h1>
    <a href="#" class="page-title-action">Añadir Nuevo</a>
    <hr class="wp-header-end">

    <div class="flavor-filters">
        <form method="get">
            <input type="hidden" name="page" value="flavor-chat-talleres">
            <input type="hidden" name="tab" value="talleres">
            <select name="estado">
                <option value="">Todos los estados</option>
                <option value="borrador" <?php selected($filtro_estado, 'borrador'); ?>>Borrador</option>
                <option value="publicado" <?php selected($filtro_estado, 'publicado'); ?>>Publicado</option>
                <option value="confirmado" <?php selected($filtro_estado, 'confirmado'); ?>>Confirmado</option>
                <option value="en_curso" <?php selected($filtro_estado, 'en_curso'); ?>>En curso</option>
                <option value="finalizado" <?php selected($filtro_estado, 'finalizado'); ?>>Finalizado</option>
                <option value="cancelado" <?php selected($filtro_estado, 'cancelado'); ?>>Cancelado</option>
            </select>
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($filtro_categoria, $cat); ?>>
                        <?php echo esc_html($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">Filtrar</button>
        </form>
    </div>

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Taller</th>
                    <th>Organizador</th>
                    <th>Categoría</th>
                    <th>Participantes</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
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
                            <td><button class="button button-small">Editar</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="flavor-no-data">No se encontraron talleres</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
