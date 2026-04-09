<?php
/**
 * Vista Gestión de Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';

// Parámetros de filtrado y paginación
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$filtro_modalidad = isset($_GET['modalidad']) ? sanitize_text_field($_GET['modalidad']) : '';

// Construir query
$where = ['1=1'];
$prepare_values = [];

if (!empty($search)) {
    $where[] = '(titulo LIKE %s OR descripcion LIKE %s)';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
}

if (!empty($filtro_estado)) {
    $where[] = 'estado = %s';
    $prepare_values[] = $filtro_estado;
}

if (!empty($filtro_categoria)) {
    $where[] = 'categoria = %s';
    $prepare_values[] = $filtro_categoria;
}

if (!empty($filtro_modalidad)) {
    $where[] = 'modalidad = %s';
    $prepare_values[] = $filtro_modalidad;
}

$where_sql = implode(' AND ', $where);

// Obtener total de registros
$total_items = $wpdb->get_var(
    empty($prepare_values)
        ? "SELECT COUNT(*) FROM $tabla_cursos WHERE $where_sql"
        : $wpdb->prepare("SELECT COUNT(*) FROM $tabla_cursos WHERE $where_sql", ...$prepare_values)
);

$total_pages = ceil($total_items / $per_page);

// Obtener cursos
$query = "SELECT c.*, u.display_name as instructor_nombre
          FROM $tabla_cursos c
          LEFT JOIN {$wpdb->users} u ON c.instructor_id = u.ID
          WHERE $where_sql
          ORDER BY c.fecha_creacion DESC
          LIMIT $per_page OFFSET $offset";

$cursos = empty($prepare_values)
    ? $wpdb->get_results($query)
    : $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));

// Obtener categorías únicas
$categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_cursos WHERE categoria IS NOT NULL ORDER BY categoria");

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Cursos', 'flavor-platform'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-cursos&tab=nuevo')); ?>" class="page-title-action" id="btn-nuevo-curso"><?php echo esc_html__('Añadir Nuevo', 'flavor-platform'); ?></a>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-chat-cursos', 'flavor-platform'); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr__('cursos', 'flavor-platform'); ?>">

            <div class="flavor-filters-row">
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php echo esc_attr__('Buscar cursos...', 'flavor-platform'); ?>"
                       class="flavor-filter-search">

                <select name="estado" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todos los estados', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('borrador', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'borrador'); ?>><?php echo esc_html__('Borrador', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('publicado', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'publicado'); ?>><?php echo esc_html__('Publicado', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('en_curso', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'en_curso'); ?>><?php echo esc_html__('En curso', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('finalizado', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'finalizado'); ?>><?php echo esc_html__('Finalizado', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('cancelado', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'cancelado'); ?>><?php echo esc_html__('Cancelado', 'flavor-platform'); ?></option>
                </select>

                <select name="categoria" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todas las categorías', 'flavor-platform'); ?></option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat); ?>" <?php selected($filtro_categoria, $cat); ?>>
                            <?php echo esc_html($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="modalidad" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todas las modalidades', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('online', 'flavor-platform'); ?>" <?php selected($filtro_modalidad, 'online'); ?>><?php echo esc_html__('Online', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('presencial', 'flavor-platform'); ?>" <?php selected($filtro_modalidad, 'presencial'); ?>><?php echo esc_html__('Presencial', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('mixto', 'flavor-platform'); ?>" <?php selected($filtro_modalidad, 'mixto'); ?>><?php echo esc_html__('Mixto', 'flavor-platform'); ?></option>
                </select>

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-platform'); ?></button>
                <?php if ($search || $filtro_estado || $filtro_categoria || $filtro_modalidad): ?>
                    <a href="?page=flavor-chat-cursos&tab=cursos" class="button"><?php echo esc_html__('Limpiar', 'flavor-platform'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de cursos -->
    <div class="flavor-card">
        <div class="flavor-card-body">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Curso', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Instructor', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Categoría', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Modalidad', 'flavor-platform'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Alumnos', 'flavor-platform'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Precio', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-platform'); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cursos)): ?>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?php echo $curso->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($curso->titulo); ?></strong>
                                    <br>
                                    <small class="flavor-text-muted">
                                        <?php echo esc_html(wp_trim_words($curso->descripcion, 12)); ?>
                                    </small>
                                    <?php if ($curso->fecha_inicio): ?>
                                        <br><small class="flavor-text-muted">
                                            Inicio: <?php echo date('d/m/Y', strtotime($curso->fecha_inicio)); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($curso->instructor_nombre); ?></td>
                                <td>
                                    <span class="flavor-badge flavor-badge-light">
                                        <?php echo esc_html($curso->categoria ?: 'Sin categoría'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php
                                        echo $curso->modalidad === 'online' ? 'info' :
                                            ($curso->modalidad === 'presencial' ? 'warning' : 'primary');
                                    ?>">
                                        <?php echo ucfirst($curso->modalidad); ?>
                                    </span>
                                </td>
                                <td class="flavor-text-center">
                                    <?php echo number_format($curso->alumnos_inscritos); ?> / <?php echo number_format($curso->max_alumnos); ?>
                                </td>
                                <td class="flavor-text-right">
                                    <?php if ($curso->es_gratuito): ?>
                                        <strong class="flavor-text-success"><?php echo esc_html__('Gratis', 'flavor-platform'); ?></strong>
                                    <?php else: ?>
                                        <?php echo number_format($curso->precio, 2); ?>€
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php
                                        echo $curso->estado === 'publicado' ? 'success' :
                                            ($curso->estado === 'en_curso' ? 'primary' :
                                            ($curso->estado === 'finalizado' ? 'secondary' :
                                            ($curso->estado === 'cancelado' ? 'danger' : 'light')));
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $curso->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button button-small btn-editar-curso" data-id="<?php echo $curso->id; ?>">
                                        <?php echo esc_html__('Editar', 'flavor-platform'); ?>
                                    </button>
                                    <button class="button button-small btn-contenido-curso" data-id="<?php echo $curso->id; ?>">
                                        <?php echo esc_html__('Contenido', 'flavor-platform'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data">
                                <?php echo esc_html__('No se encontraron cursos', 'flavor-platform'); ?>
                            </td>
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
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged
                ]);
                echo $page_links;
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nuevo/Editar Curso -->
<div id="modal-curso" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-content flavor-modal-large">
        <div class="flavor-modal-header">
            <h2 id="modal-curso-title"><?php echo esc_html__('Nuevo Curso', 'flavor-platform'); ?></h2>
            <span class="flavor-modal-close"><?php echo esc_html__('&times;', 'flavor-platform'); ?></span>
        </div>
        <div class="flavor-modal-body">
            <form id="form-curso">
                <input type="hidden" name="curso_id" id="curso_id">

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label><?php echo esc_html__('Título del Curso *', 'flavor-platform'); ?></label>
                        <input type="text" name="titulo" id="titulo" required class="regular-text">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label><?php echo esc_html__('Descripción *', 'flavor-platform'); ?></label>
                        <textarea name="descripcion" id="descripcion" required rows="4" class="large-text"></textarea>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('Categoría *', 'flavor-platform'); ?></label>
                        <input type="text" name="categoria" id="categoria" required class="regular-text"
                               placeholder="<?php echo esc_attr__('ej: Tecnología, Idiomas...', 'flavor-platform'); ?>">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label><?php echo esc_html__('Nivel *', 'flavor-platform'); ?></label>
                        <select name="nivel" id="nivel" required>
                            <option value="<?php echo esc_attr__('todos', 'flavor-platform'); ?>"><?php echo esc_html__('Todos los niveles', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('principiante', 'flavor-platform'); ?>"><?php echo esc_html__('Principiante', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('intermedio', 'flavor-platform'); ?>"><?php echo esc_html__('Intermedio', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('avanzado', 'flavor-platform'); ?>"><?php echo esc_html__('Avanzado', 'flavor-platform'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label><?php echo esc_html__('Modalidad *', 'flavor-platform'); ?></label>
                        <select name="modalidad" id="modalidad" required>
                            <option value="<?php echo esc_attr__('online', 'flavor-platform'); ?>"><?php echo esc_html__('Online', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('presencial', 'flavor-platform'); ?>"><?php echo esc_html__('Presencial', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('mixto', 'flavor-platform'); ?>"><?php echo esc_html__('Mixto', 'flavor-platform'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-3">
                        <label><?php echo esc_html__('Duración (horas) *', 'flavor-platform'); ?></label>
                        <input type="number" name="duracion_horas" id="duracion_horas" required min="1">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label><?php echo esc_html__('Máx. Alumnos *', 'flavor-platform'); ?></label>
                        <input type="number" name="max_alumnos" id="max_alumnos" required min="1" value="30">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label><?php echo esc_html__('Precio (€)', 'flavor-platform'); ?></label>
                        <input type="number" name="precio" id="precio" min="0" step="0.01" value="0">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label><?php echo esc_html__('&nbsp;', 'flavor-platform'); ?></label>
                        <label>
                            <input type="checkbox" name="es_gratuito" id="es_gratuito" value="1" checked>
                            <?php echo esc_html__('Curso gratuito', 'flavor-platform'); ?>
                        </label>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('Fecha de Inicio', 'flavor-platform'); ?></label>
                        <input type="datetime-local" name="fecha_inicio" id="fecha_inicio">
                    </div>

                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('Fecha de Fin', 'flavor-platform'); ?></label>
                        <input type="datetime-local" name="fecha_fin" id="fecha_fin">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label><?php echo esc_html__('Ubicación (si es presencial)', 'flavor-platform'); ?></label>
                        <input type="text" name="ubicacion" id="ubicacion" class="regular-text">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('Requisitos', 'flavor-platform'); ?></label>
                        <textarea name="requisitos" id="requisitos" rows="3" class="large-text"></textarea>
                    </div>

                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('Qué Aprenderás', 'flavor-platform'); ?></label>
                        <textarea name="que_aprenderas" id="que_aprenderas" rows="3" class="large-text"></textarea>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('URL Imagen Portada', 'flavor-platform'); ?></label>
                        <input type="url" name="imagen_portada" id="imagen_portada" class="regular-text">
                    </div>

                    <div class="flavor-form-group flavor-form-col-6">
                        <label><?php echo esc_html__('URL Video Presentación', 'flavor-platform'); ?></label>
                        <input type="url" name="video_presentacion" id="video_presentacion" class="regular-text">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label><?php echo esc_html__('Estado *', 'flavor-platform'); ?></label>
                        <select name="estado" id="estado" required>
                            <option value="<?php echo esc_attr__('borrador', 'flavor-platform'); ?>"><?php echo esc_html__('Borrador', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('publicado', 'flavor-platform'); ?>"><?php echo esc_html__('Publicado', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('en_curso', 'flavor-platform'); ?>"><?php echo esc_html__('En Curso', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('finalizado', 'flavor-platform'); ?>"><?php echo esc_html__('Finalizado', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('cancelado', 'flavor-platform'); ?>"><?php echo esc_html__('Cancelado', 'flavor-platform'); ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-curso"><?php echo esc_html__('Cancelar', 'flavor-platform'); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-curso"><?php echo esc_html__('Guardar Curso', 'flavor-platform'); ?></button>
        </div>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>

<script>
jQuery(document).ready(function($) {
    // Abrir modal nuevo curso
    $('#btn-nuevo-curso').on('click', function(e) {
        e.preventDefault();
        $('#modal-curso-title').text('Nuevo Curso');
        $('#form-curso')[0].reset();
        $('#curso_id').val('');
        $('#modal-curso').fadeIn();
    });

    // Abrir modal editar curso
    $('.btn-editar-curso').on('click', function() {
        const cursoId = $(this).data('id');
        $('#modal-curso-title').text('Editar Curso');

        // Cargar datos del curso
        $.post(ajaxurl, {
            action: 'flavor_get_curso',
            curso_id: cursoId,
            nonce: '<?php echo wp_create_nonce('flavor_cursos_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                const curso = response.data;
                $('#curso_id').val(curso.id);
                $('#titulo').val(curso.titulo);
                $('#descripcion').val(curso.descripcion);
                $('#categoria').val(curso.categoria);
                $('#nivel').val(curso.nivel);
                $('#modalidad').val(curso.modalidad);
                $('#duracion_horas').val(curso.duracion_horas);
                $('#max_alumnos').val(curso.max_alumnos);
                $('#precio').val(curso.precio);
                $('#es_gratuito').prop('checked', curso.es_gratuito == 1);
                $('#fecha_inicio').val(curso.fecha_inicio);
                $('#fecha_fin').val(curso.fecha_fin);
                $('#ubicacion').val(curso.ubicacion);
                $('#requisitos').val(curso.requisitos);
                $('#que_aprenderas').val(curso.que_aprenderas);
                $('#imagen_portada').val(curso.imagen_portada);
                $('#video_presentacion').val(curso.video_presentacion);
                $('#estado').val(curso.estado);

                $('#modal-curso').fadeIn();
            }
        });
    });

    // Cerrar modal
    $('.flavor-modal-close, #btn-cancelar-curso').on('click', function() {
        $('#modal-curso').fadeOut();
    });

    // Guardar curso
    $('#btn-guardar-curso').on('click', function() {
        const formData = $('#form-curso').serialize();

        $.post(ajaxurl, {
            action: 'flavor_guardar_curso',
            nonce: '<?php echo wp_create_nonce('flavor_cursos_nonce'); ?>',
            ...Object.fromEntries(new URLSearchParams(formData))
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

    // Ver contenido del curso
    $('.btn-contenido-curso').on('click', function() {
        const cursoId = $(this).data('id');
        window.location.href = '?page=flavor-chat-cursos&tab=cursos&action=contenido&curso_id=' + cursoId;
    });
});
</script>
