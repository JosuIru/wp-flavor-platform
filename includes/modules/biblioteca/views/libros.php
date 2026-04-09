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
    <h1 class="wp-heading-inline"><?php echo esc_html__('Catálogo de Libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-biblioteca&tab=nuevo')); ?>" class="page-title-action" id="btn-nuevo-libro"><?php echo esc_html__('Añadir Libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-chat-biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr__('libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

            <div class="flavor-filters-row">
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php echo esc_attr__('Buscar por título, autor o ISBN...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="flavor-filter-search">

                <select name="genero" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todos los géneros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($generos as $genero): ?>
                        <option value="<?php echo esc_attr($genero); ?>" <?php selected($filtro_genero, $genero); ?>>
                            <?php echo esc_html($genero); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="disponibilidad" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todas las disponibilidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_disponibilidad, 'disponible'); ?>><?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('prestado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_disponibilidad, 'prestado'); ?>><?php echo esc_html__('Prestado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('reservado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_disponibilidad, 'reservado'); ?>><?php echo esc_html__('Reservado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('no_disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_disponibilidad, 'no_disponible'); ?>><?php echo esc_html__('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <?php if ($search || $filtro_genero || $filtro_disponibilidad): ?>
                    <a href="?page=flavor-chat-biblioteca&tab=libros" class="button"><?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
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
                        <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Género', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Propietario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
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
                                        <?php echo esc_html__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <button class="button button-small btn-historial-libro" data-id="<?php echo $libro->id; ?>">
                                        <?php echo esc_html__('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data"><?php echo esc_html__('No se encontraron libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
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

<!-- Modal Libro -->
<div id="modal-libro" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalLibro()"></div>
    <div class="flavor-modal-content" style="min-width:500px;max-height:80vh;overflow-y:auto;">
        <button class="flavor-modal-close" onclick="cerrarModalLibro()">&times;</button>
        <div id="modal-libro-contenido"></div>
    </div>
</div>

<style>
.flavor-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; }
.flavor-modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); }
.flavor-modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 25px; border-radius: 8px; }
.flavor-modal-close { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; z-index: 1; }
</style>

<script>
jQuery(document).ready(function($) {
    // Nuevo libro
    $('#btn-nuevo-libro').on('click', function(e) {
        e.preventDefault();
        window.location.href = '<?php echo admin_url('admin.php?page=flavor-chat-biblioteca&tab=nuevo'); ?>';
    });

    // Editar libro
    $('.btn-editar-libro').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        window.location.href = '<?php echo admin_url('admin.php?page=flavor-chat-biblioteca&tab=editar&id='); ?>' + id;
    });

    // Historial libro
    $('.btn-historial-libro').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#modal-libro-contenido').html('<p><?php echo esc_js(__('Cargando historial...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>');
        $('#modal-libro').show();

        $.get(ajaxurl, {
            action: 'flavor_biblioteca_historial',
            libro_id: id,
            nonce: '<?php echo wp_create_nonce('biblioteca_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var html = '<h3><?php echo esc_js(__('Historial de Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h3>';
                if (response.data.length === 0) {
                    html += '<p><?php echo esc_js(__('No hay préstamos registrados', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>';
                } else {
                    html += '<table class="wp-list-table widefat striped"><thead><tr><th><?php echo esc_js(__('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></th><th><?php echo esc_js(__('Fecha préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></th><th><?php echo esc_js(__('Fecha devolución', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></th></tr></thead><tbody>';
                    response.data.forEach(function(p) {
                        html += '<tr><td>' + p.usuario + '</td><td>' + p.fecha_prestamo + '</td><td>' + (p.fecha_devolucion || '-') + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                $('#modal-libro-contenido').html(html);
            } else {
                $('#modal-libro-contenido').html('<p><?php echo esc_js(__('Error al cargar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>');
            }
        });
    });
});

function cerrarModalLibro() {
    document.getElementById('modal-libro').style.display = 'none';
}
</script>
