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
        Gestión de Episodios
        <a href="#" class="page-title-action" onclick="abrirModalNuevoEpisodio(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> Subir Episodio
        </a>
    </h1>

    <!-- Filtros -->
    <div class="flavor-filters" style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

            <div style="flex: 1; min-width: 200px;">
                <label for="podcast_id" style="display: block; margin-bottom: 5px; font-weight: 600;">Podcast:</label>
                <select name="podcast_id" id="podcast_id" class="regular-text">
                    <option value="0">Todos los podcasts</option>
                    <?php foreach ($podcasts_disponibles as $podcast): ?>
                        <option value="<?php echo $podcast->id; ?>" <?php selected($podcast_seleccionado, $podcast->id); ?>>
                            <?php echo esc_html($podcast->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 150px;">
                <label for="estado" style="display: block; margin-bottom: 5px; font-weight: 600;">Estado:</label>
                <select name="estado" id="estado" class="regular-text">
                    <option value="">Todos los estados</option>
                    <option value="publicado" <?php selected($estado_filtro, 'publicado'); ?>>Publicado</option>
                    <option value="borrador" <?php selected($estado_filtro, 'borrador'); ?>>Borrador</option>
                    <option value="programado" <?php selected($estado_filtro, 'programado'); ?>>Programado</option>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span> Filtrar
                </button>
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">
                    <span class="dashicons dashicons-no"></span> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de episodios -->
    <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Episodio</th>
                    <th>Podcast</th>
                    <th style="width: 100px;">Duración</th>
                    <th style="width: 100px;">Tamaño</th>
                    <th style="width: 120px;">Reproducciones</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 150px;">Fecha</th>
                    <th style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($episodios)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-admin-media" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;">No se encontraron episodios con los filtros seleccionados</p>
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
                                            Tu navegador no soporta audio HTML5.
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
                                    <span class="dashicons dashicons-edit"></span> Editar
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
                Subir Nuevo Episodio
            </h2>
            <button onclick="cerrarModalNuevoEpisodio()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <form id="form-nuevo-episodio" method="post" enctype="multipart/form-data">

            <div style="margin-bottom: 20px;">
                <label for="episodio_podcast_id" style="display: block; margin-bottom: 5px; font-weight: 600;">Podcast:</label>
                <select name="podcast_id" id="episodio_podcast_id" class="regular-text" required>
                    <option value="">Seleccionar podcast...</option>
                    <?php foreach ($podcasts_disponibles as $podcast): ?>
                        <option value="<?php echo $podcast->id; ?>"><?php echo esc_html($podcast->titulo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_titulo" style="display: block; margin-bottom: 5px; font-weight: 600;">Título del Episodio:</label>
                <input type="text" name="titulo" id="episodio_titulo" class="regular-text" required style="width: 100%;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_numero" style="display: block; margin-bottom: 5px; font-weight: 600;">Número de Episodio:</label>
                <input type="number" name="numero_episodio" id="episodio_numero" class="regular-text" required min="1" value="1">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_descripcion" style="display: block; margin-bottom: 5px; font-weight: 600;">Descripción / Show Notes:</label>
                <textarea name="descripcion" id="episodio_descripcion" rows="6" class="large-text" style="width: 100%;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_archivo" style="display: block; margin-bottom: 5px; font-weight: 600;">Archivo de Audio:</label>
                <input type="file" name="archivo_audio" id="episodio_archivo" accept=".mp3,.mp4,.ogg" required>
                <p class="description">Formatos permitidos: MP3, MP4, OGG. Tamaño máximo: 100 MB</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_imagen" style="display: block; margin-bottom: 5px; font-weight: 600;">Imagen del Episodio (opcional):</label>
                <input type="file" name="imagen_episodio" id="episodio_imagen" accept="image/*">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="episodio_estado" style="display: block; margin-bottom: 5px; font-weight: 600;">Estado:</label>
                <select name="estado" id="episodio_estado" class="regular-text">
                    <option value="publicado">Publicar ahora</option>
                    <option value="borrador">Guardar como borrador</option>
                    <option value="programado">Programar publicación</option>
                </select>
            </div>

            <div id="fecha-programacion-container" style="margin-bottom: 20px; display: none;">
                <label for="episodio_fecha_programacion" style="display: block; margin-bottom: 5px; font-weight: 600;">Fecha de Programación:</label>
                <input type="datetime-local" name="fecha_programacion" id="episodio_fecha_programacion" class="regular-text">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="cerrarModalNuevoEpisodio()" class="button button-large">Cancelar</button>
                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-upload"></span> Subir Episodio
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
    // Implementar edición de episodio
    alert('Editar episodio #' + episodioId);
}

function eliminarEpisodio(episodioId) {
    if (confirm('¿Estás seguro de que deseas eliminar este episodio? Esta acción no se puede deshacer.')) {
        // Implementar eliminación
        alert('Eliminar episodio #' + episodioId);
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
