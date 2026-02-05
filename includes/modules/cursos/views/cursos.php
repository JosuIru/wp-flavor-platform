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
    <h1 class="wp-heading-inline">Gestión de Cursos</h1>
    <a href="#" class="page-title-action" id="btn-nuevo-curso">Añadir Nuevo</a>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-chat-cursos">
            <input type="hidden" name="tab" value="cursos">

            <div class="flavor-filters-row">
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="Buscar cursos..."
                       class="flavor-filter-search">

                <select name="estado" class="flavor-filter-select">
                    <option value="">Todos los estados</option>
                    <option value="borrador" <?php selected($filtro_estado, 'borrador'); ?>>Borrador</option>
                    <option value="publicado" <?php selected($filtro_estado, 'publicado'); ?>>Publicado</option>
                    <option value="en_curso" <?php selected($filtro_estado, 'en_curso'); ?>>En curso</option>
                    <option value="finalizado" <?php selected($filtro_estado, 'finalizado'); ?>>Finalizado</option>
                    <option value="cancelado" <?php selected($filtro_estado, 'cancelado'); ?>>Cancelado</option>
                </select>

                <select name="categoria" class="flavor-filter-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat); ?>" <?php selected($filtro_categoria, $cat); ?>>
                            <?php echo esc_html($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="modalidad" class="flavor-filter-select">
                    <option value="">Todas las modalidades</option>
                    <option value="online" <?php selected($filtro_modalidad, 'online'); ?>>Online</option>
                    <option value="presencial" <?php selected($filtro_modalidad, 'presencial'); ?>>Presencial</option>
                    <option value="mixto" <?php selected($filtro_modalidad, 'mixto'); ?>>Mixto</option>
                </select>

                <button type="submit" class="button">Filtrar</button>
                <?php if ($search || $filtro_estado || $filtro_categoria || $filtro_modalidad): ?>
                    <a href="?page=flavor-chat-cursos&tab=cursos" class="button">Limpiar</a>
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
                        <th style="width: 50px;">ID</th>
                        <th>Curso</th>
                        <th>Instructor</th>
                        <th>Categoría</th>
                        <th>Modalidad</th>
                        <th style="width: 80px;">Alumnos</th>
                        <th style="width: 80px;">Precio</th>
                        <th style="width: 100px;">Estado</th>
                        <th style="width: 150px;">Acciones</th>
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
                                        <strong class="flavor-text-success">Gratis</strong>
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
                                        Editar
                                    </button>
                                    <button class="button button-small btn-contenido-curso" data-id="<?php echo $curso->id; ?>">
                                        Contenido
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data">
                                No se encontraron cursos
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
            <h2 id="modal-curso-title">Nuevo Curso</h2>
            <span class="flavor-modal-close">&times;</span>
        </div>
        <div class="flavor-modal-body">
            <form id="form-curso">
                <input type="hidden" name="curso_id" id="curso_id">

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label>Título del Curso *</label>
                        <input type="text" name="titulo" id="titulo" required class="regular-text">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label>Descripción *</label>
                        <textarea name="descripcion" id="descripcion" required rows="4" class="large-text"></textarea>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label>Categoría *</label>
                        <input type="text" name="categoria" id="categoria" required class="regular-text"
                               placeholder="ej: Tecnología, Idiomas...">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label>Nivel *</label>
                        <select name="nivel" id="nivel" required>
                            <option value="todos">Todos los niveles</option>
                            <option value="principiante">Principiante</option>
                            <option value="intermedio">Intermedio</option>
                            <option value="avanzado">Avanzado</option>
                        </select>
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label>Modalidad *</label>
                        <select name="modalidad" id="modalidad" required>
                            <option value="online">Online</option>
                            <option value="presencial">Presencial</option>
                            <option value="mixto">Mixto</option>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-3">
                        <label>Duración (horas) *</label>
                        <input type="number" name="duracion_horas" id="duracion_horas" required min="1">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label>Máx. Alumnos *</label>
                        <input type="number" name="max_alumnos" id="max_alumnos" required min="1" value="30">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label>Precio (€)</label>
                        <input type="number" name="precio" id="precio" min="0" step="0.01" value="0">
                    </div>

                    <div class="flavor-form-group flavor-form-col-3">
                        <label>&nbsp;</label>
                        <label>
                            <input type="checkbox" name="es_gratuito" id="es_gratuito" value="1" checked>
                            Curso gratuito
                        </label>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label>Fecha de Inicio</label>
                        <input type="datetime-local" name="fecha_inicio" id="fecha_inicio">
                    </div>

                    <div class="flavor-form-group flavor-form-col-6">
                        <label>Fecha de Fin</label>
                        <input type="datetime-local" name="fecha_fin" id="fecha_fin">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label>Ubicación (si es presencial)</label>
                        <input type="text" name="ubicacion" id="ubicacion" class="regular-text">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label>Requisitos</label>
                        <textarea name="requisitos" id="requisitos" rows="3" class="large-text"></textarea>
                    </div>

                    <div class="flavor-form-group flavor-form-col-6">
                        <label>Qué Aprenderás</label>
                        <textarea name="que_aprenderas" id="que_aprenderas" rows="3" class="large-text"></textarea>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label>URL Imagen Portada</label>
                        <input type="url" name="imagen_portada" id="imagen_portada" class="regular-text">
                    </div>

                    <div class="flavor-form-group flavor-form-col-6">
                        <label>URL Video Presentación</label>
                        <input type="url" name="video_presentacion" id="video_presentacion" class="regular-text">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-12">
                        <label>Estado *</label>
                        <select name="estado" id="estado" required>
                            <option value="borrador">Borrador</option>
                            <option value="publicado">Publicado</option>
                            <option value="en_curso">En Curso</option>
                            <option value="finalizado">Finalizado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-curso">Cancelar</button>
            <button type="button" class="button button-primary" id="btn-guardar-curso">Guardar Curso</button>
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
