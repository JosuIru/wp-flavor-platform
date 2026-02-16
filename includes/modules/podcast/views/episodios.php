<?php
/**
 * Vista de Gestión de Episodios del módulo Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';
$tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';

// Obtener todos los podcasts para el selector
$podcasts_disponibles = $wpdb->get_results("SELECT id, titulo FROM $tabla_podcasts ORDER BY titulo");

// Filtros
$podcast_seleccionado = isset($_GET['podcast_id']) ? intval($_GET['podcast_id']) : 0;
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

// Construir consulta con filtros
$where_clauses = ['1=1'];
$prepare_values = [];

if ($podcast_seleccionado > 0) {
    $where_clauses[] = 'e.podcast_id = %d';
    $prepare_values[] = $podcast_seleccionado;
}

if (!empty($estado_filtro)) {
    $where_clauses[] = 'e.estado = %s';
    $prepare_values[] = $estado_filtro;
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener episodios
if (!empty($prepare_values)) {
    $episodios = $wpdb->get_results($wpdb->prepare("
        SELECT e.*, p.titulo as podcast_titulo
        FROM $tabla_episodios e
        INNER JOIN $tabla_podcasts p ON e.podcast_id = p.id
        WHERE $where_sql
        ORDER BY e.fecha_publicacion DESC
    ", ...$prepare_values));
} else {
    $episodios = $wpdb->get_results("
        SELECT e.*, p.titulo as podcast_titulo
        FROM $tabla_episodios e
        INNER JOIN $tabla_podcasts p ON e.podcast_id = p.id
        WHERE $where_sql
        ORDER BY e.fecha_publicacion DESC
    ");
}

// Función para formatear duración
function formatear_duracion_episodio($segundos) {
    if (empty($segundos)) return 'N/A';
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);
    $segs = $segundos % 60;

    if ($horas > 0) {
        return sprintf('%d:%02d:%02d', $horas, $minutos, $segs);
    }
    return sprintf('%d:%02d', $minutos, $segs);
}

// Función para formatear tamaño
function formatear_tamano_archivo($bytes) {
    if (empty($bytes)) return 'N/A';
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $indice = 0;
    while ($bytes >= 1024 && $indice < 3) {
        $bytes /= 1024;
        $indice++;
    }
    return round($bytes, 2) . ' ' . $unidades[$indice];
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-playlist-audio"></span>
        <?php echo esc_html__('Gestión de Episodios', 'flavor-chat-ia'); ?>
        <a href="#" class="page-title-action" onclick="abrirModalNuevoEpisodio(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Subir Episodio', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <!-- Filtros -->
    <div class="flavor-filters" style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

            <div style="flex: 1; min-width: 200px;">
                <label for="podcast_id" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Podcast:', 'flavor-chat-ia'); ?></label>
                <select name="podcast_id" id="podcast_id" class="regular-text">
                    <option value="0"><?php echo esc_html__('Todos los podcasts', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($podcasts_disponibles as $podcast): ?>
                        <option value="<?php echo $podcast->id; ?>" <?php selected($podcast_seleccionado, $podcast->id); ?>>
                            <?php echo esc_html($podcast->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 150px;">
                <label for="estado" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Estado:', 'flavor-chat-ia'); ?></label>
                <select name="estado" id="estado" class="regular-text">
                    <option value=""><?php echo esc_html__('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('publicado', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'publicado'); ?>><?php echo esc_html__('Publicado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('borrador', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'borrador'); ?>><?php echo esc_html__('Borrador', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('programado', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'programado'); ?>><?php echo esc_html__('Programado', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span> <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
                </button>
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">
                    <span class="dashicons dashicons-no"></span> <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de episodios -->
    <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Episodio', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Podcast', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Duración', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Tamaño', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Reproducciones', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($episodios)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-admin-media" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;"><?php echo esc_html__('No se encontraron episodios con los filtros seleccionados', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($episodios as $episodio): ?>
                        <tr>
                            <td><strong>#<?php echo $episodio->id; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($episodio->titulo); ?></strong>
                                <div style="color: #666; font-size: 12px;">
                                    Episodio #<?php echo $episodio->numero_episodio; ?>
                                </div>
                                <?php if (!empty($episodio->archivo_url)): ?>
                                    <div style="margin-top: 5px;">
                                        <audio controls style="width: 100%; max-width: 300px; height: 30px;">
                                            <source src="<?php echo esc_url($episodio->archivo_url); ?>" type="audio/mpeg">
                                            <?php echo esc_html__('Tu navegador no soporta audio HTML5.', 'flavor-chat-ia'); ?>
                                        </audio>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($episodio->podcast_titulo); ?></td>
                            <td><?php echo formatear_duracion_episodio($episodio->duracion_segundos); ?></td>
                            <td><?php echo formatear_tamano_archivo($episodio->tamano_bytes); ?></td>
                            <td style="text-align: center;">
                                <span class="dashicons dashicons-controls-play" style="color: #00a32a;"></span>
                                <?php echo number_format($episodio->reproducciones); ?>
                                <div style="color: #666; font-size: 12px;">
                                    <span class="dashicons dashicons-heart" style="font-size: 12px;"></span>
                                    <?php echo number_format($episodio->me_gusta); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $estado_class = [
                                    'publicado' => 'success',
                                    'borrador' => 'warning',
                                    'programado' => 'info'
                                ];
                                $estado_colors = [
                                    'publicado' => '#00a32a',
                                    'borrador' => '#dba617',
                                    'programado' => '#2271b1'
                                ];
                                ?>
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; color: #fff; background-color: <?php echo $estado_colors[$episodio->estado] ?? '#666'; ?>;">
                                    <?php echo ucfirst($episodio->estado); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date_i18n('d/m/Y H:i', strtotime($episodio->fecha_publicacion)); ?>
                            </td>
                            <td>
                                <button class="button button-small" onclick="editarEpisodio(<?php echo $episodio->id; ?>)">
                                    <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                                </button>
                                <button class="button button-small button-link-delete" onclick="eliminarEpisodio(<?php echo $episodio->id; ?>)">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>

<!-- Modal para nuevo episodio -->
<div id="modal-nuevo-episodio" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; width: 90%; max-width: 800px; border-radius: 8px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">
                <span class="dashicons dashicons-upload"></span>
                <?php echo esc_html__('Subir Nuevo Episodio', 'flavor-chat-ia'); ?>
            </h2>
            <button onclick="cerrarModalNuevoEpisodio()" style="background: none; border: none; font-size: 24px; cursor: pointer;"><?php echo esc_html__('&times;', 'flavor-chat-ia'); ?></button>
        </div>

        <form id="form-nuevo-episodio" method="post" enctype="multipart/form-data">

            <div style="margin-bottom: 20px;">
                <label for="episodio_podcast_id" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Podcast:', 'flavor-chat-ia'); ?></label>
                <select name="podcast_id" id="episodio_podcast_id" class="regular-text" required>
                    <option value=""><?php echo esc_html__('Seleccionar podcast...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($podcasts_disponibles as $podcast): ?>
                        <option value="<?php echo $podcast->id; ?>"><?php echo esc_html($podcast->titulo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_titulo" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Título del Episodio:', 'flavor-chat-ia'); ?></label>
                <input type="text" name="titulo" id="episodio_titulo" class="regular-text" required style="width: 100%;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_numero" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Número de Episodio:', 'flavor-chat-ia'); ?></label>
                <input type="number" name="numero_episodio" id="episodio_numero" class="regular-text" required min="1" value="1">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_descripcion" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Descripción / Show Notes:', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" id="episodio_descripcion" rows="6" class="large-text" style="width: 100%;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_archivo" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Archivo de Audio:', 'flavor-chat-ia'); ?></label>
                <input type="file" name="archivo_audio" id="episodio_archivo" accept=".mp3,.mp4,.ogg" required>
                <p class="description"><?php echo esc_html__('Formatos permitidos: MP3, MP4, OGG. Tamaño máximo: 100 MB', 'flavor-chat-ia'); ?></p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_imagen" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Imagen del Episodio (opcional):', 'flavor-chat-ia'); ?></label>
                <input type="file" name="imagen_episodio" id="episodio_imagen" accept="image/*">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_estado" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Estado:', 'flavor-chat-ia'); ?></label>
                <select name="estado" id="episodio_estado" class="regular-text">
                    <option value="<?php echo esc_attr__('publicado', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Publicar ahora', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('borrador', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Guardar como borrador', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('programado', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Programar publicación', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div id="fecha-programacion-container" style="margin-bottom: 20px; display: none;">
                <label for="episodio_fecha_programacion" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Fecha de Programación:', 'flavor-chat-ia'); ?></label>
                <input type="datetime-local" name="fecha_programacion" id="episodio_fecha_programacion" class="regular-text">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="cerrarModalNuevoEpisodio()" class="button button-large"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-upload"></span> <?php echo esc_html__('Subir Episodio', 'flavor-chat-ia'); ?>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function abrirModalNuevoEpisodio() {
    document.getElementById('modal-nuevo-episodio').style.display = 'block';
}

function cerrarModalNuevoEpisodio() {
    document.getElementById('modal-nuevo-episodio').style.display = 'none';
    document.getElementById('form-nuevo-episodio').reset();
}

function editarEpisodio(episodioId) {
    window.location.href = '<?php echo admin_url('admin.php?page=flavor-chat-podcast-episodios&editar='); ?>' + episodioId;
}

function eliminarEpisodio(episodioId) {
    if (confirm('<?php echo esc_js(__('¿Eliminar este episodio? Esta acción no se puede deshacer.', 'flavor-chat-ia')); ?>')) {
        jQuery.post(ajaxurl, {
            action: 'flavor_podcast_eliminar_episodio',
            episodio_id: episodioId,
            nonce: '<?php echo wp_create_nonce('podcast_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php echo esc_js(__('Error al eliminar', 'flavor-chat-ia')); ?>');
            }
        });
    }
}

jQuery(document).ready(function($) {
    // Mostrar/ocultar campo de fecha de programación
    $('#episodio_estado').on('change', function() {
        if ($(this).val() === 'programado') {
            $('#fecha-programacion-container').show();
        } else {
            $('#fecha-programacion-container').hide();
        }
    });
});
</script>

<style>
.flavor-filters select,
.flavor-filters input {
    padding: 6px 8px;
}

audio {
    outline: none;
}

.wp-list-table tbody tr:hover {
    background-color: #f6f7f7;
}

@media (max-width: 768px) {
    .flavor-filters form {
        flex-direction: column;
    }

    .flavor-filters form > div {
        width: 100%;
    }
}
</style>
