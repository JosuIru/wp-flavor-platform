<?php
/**
 * Vista Gestión de Libros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_genero = isset($_GET['genero']) ? sanitize_text_field($_GET['genero']) : '';
$filtro_disponibilidad = isset($_GET['disponibilidad']) ? sanitize_text_field($_GET['disponibilidad']) : '';

$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 25;
$offset = ($paged - 1) * $per_page;

// Construir query
$where = ['1=1'];
$prepare_values = [];

if (!empty($search)) {
    $where[] = '(titulo LIKE %s OR autor LIKE %s OR isbn LIKE %s)';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
}

if (!empty($filtro_genero)) {
    $where[] = 'genero = %s';
    $prepare_values[] = $filtro_genero;
}

if (!empty($filtro_disponibilidad)) {
    $where[] = 'disponibilidad = %s';
    $prepare_values[] = $filtro_disponibilidad;
}

$where_sql = implode(' AND ', $where);

// Total registros
$total_items = $wpdb->get_var(
    empty($prepare_values)
        ? "SELECT COUNT(*) FROM $tabla_libros WHERE $where_sql"
        : $wpdb->prepare("SELECT COUNT(*) FROM $tabla_libros WHERE $where_sql", ...$prepare_values)
);

$total_pages = ceil($total_items / $per_page);

// Obtener libros
$query = "SELECT l.*, u.display_name as propietario_nombre
          FROM $tabla_libros l
          LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
          WHERE $where_sql
          ORDER BY l.fecha_agregado DESC
          LIMIT $per_page OFFSET $offset";

$libros = empty($prepare_values)
    ? $wpdb->get_results($query)
    : $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));

// Obtener géneros únicos
$generos = $wpdb->get_col("SELECT DISTINCT genero FROM $tabla_libros WHERE genero IS NOT NULL ORDER BY genero");

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Catálogo de Libros', 'flavor-chat-ia'); ?></h1>
    <a href="#" class="page-title-action" id="btn-nuevo-libro"><?php echo esc_html__('Añadir Libro', 'flavor-chat-ia'); ?></a>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-chat-biblioteca', 'flavor-chat-ia'); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr__('libros', 'flavor-chat-ia'); ?>">

            <div class="flavor-filters-row">
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php echo esc_attr__('Buscar por título, autor o ISBN...', 'flavor-chat-ia'); ?>"
                       class="flavor-filter-search">

                <select name="genero" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todos los géneros', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($generos as $genero): ?>
                        <option value="<?php echo esc_attr($genero); ?>" <?php selected($filtro_genero, $genero); ?>>
                            <?php echo esc_html($genero); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="disponibilidad" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todas las disponibilidades', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('disponible', 'flavor-chat-ia'); ?>" <?php selected($filtro_disponibilidad, 'disponible'); ?>><?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('prestado', 'flavor-chat-ia'); ?>" <?php selected($filtro_disponibilidad, 'prestado'); ?>><?php echo esc_html__('Prestado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('reservado', 'flavor-chat-ia'); ?>" <?php selected($filtro_disponibilidad, 'reservado'); ?>><?php echo esc_html__('Reservado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('no_disponible', 'flavor-chat-ia'); ?>" <?php selected($filtro_disponibilidad, 'no_disponible'); ?>><?php echo esc_html__('No disponible', 'flavor-chat-ia'); ?></option>
                </select>

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>
                <?php if ($search || $filtro_genero || $filtro_disponibilidad): ?>
                    <a href="?page=flavor-chat-biblioteca&tab=libros" class="button"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de libros -->
    <div class="flavor-card">
        <div class="flavor-card-body">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Libro', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Género', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Propietario', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Préstamos', 'flavor-chat-ia'); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($libros)): ?>
                        <?php foreach ($libros as $libro): ?>
                            <tr>
                                <td><?php echo $libro->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($libro->titulo); ?></strong>
                                    <?php if ($libro->isbn): ?>
                                        <br><small class="flavor-text-muted">ISBN: <?php echo esc_html($libro->isbn); ?></small>
                                    <?php endif; ?>
                                    <?php if ($libro->editorial): ?>
                                        <br><small class="flavor-text-muted"><?php echo esc_html($libro->editorial); ?> (<?php echo $libro->ano_publicacion; ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($libro->autor); ?></td>
                                <td>
                                    <span class="flavor-badge flavor-badge-light">
                                        <?php echo esc_html($libro->genero ?: '-'); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($libro->propietario_nombre); ?></td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php
                                        echo $libro->estado_fisico === 'excelente' ? 'success' :
                                            ($libro->estado_fisico === 'bueno' ? 'info' :
                                            ($libro->estado_fisico === 'aceptable' ? 'warning' : 'danger'));
                                    ?>">
                                        <?php echo ucfirst($libro->estado_fisico); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php
                                        echo $libro->disponibilidad === 'disponible' ? 'success' :
                                            ($libro->disponibilidad === 'prestado' ? 'warning' :
                                            ($libro->disponibilidad === 'reservado' ? 'info' : 'secondary'));
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $libro->disponibilidad)); ?>
                                    </span>
                                </td>
                                <td class="flavor-text-center">
                                    <strong><?php echo number_format($libro->veces_prestado); ?></strong>
                                </td>
                                <td>
                                    <button class="button button-small btn-editar-libro" data-id="<?php echo $libro->id; ?>">
                                        <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                                    </button>
                                    <button class="button button-small btn-historial-libro" data-id="<?php echo $libro->id; ?>">
                                        <?php echo esc_html__('Historial', 'flavor-chat-ia'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data"><?php echo esc_html__('No se encontraron libros', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo number_format($total_items); ?> elementos</span>
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>

<script>
jQuery(document).ready(function($) {
    $('#btn-nuevo-libro, .btn-editar-libro, .btn-historial-libro').on('click', function(e) {
        e.preventDefault();
        alert('Funcionalidad en desarrollo');
    });
});
</script>
