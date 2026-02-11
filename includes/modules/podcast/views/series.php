<?php
/**
 * Vista de Gestión de Series de Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';
$tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
$tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

// Obtener todas las series/podcasts
$podcasts = $wpdb->get_results("
    SELECT p.*,
           COUNT(DISTINCT e.id) as total_episodios_reales,
           SUM(e.reproducciones) as total_reproducciones
    FROM $tabla_podcasts p
    LEFT JOIN $tabla_episodios e ON p.id = e.podcast_id AND e.estado = 'publicado'
    GROUP BY p.id
    ORDER BY p.fecha_actualizacion DESC
");

// Obtener categorías únicas
$categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_podcasts WHERE categoria IS NOT NULL ORDER BY categoria");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-book-alt"></span>
        <?php echo esc_html__('Series de Podcast', 'flavor-chat-ia'); ?>
        <a href="#" class="page-title-action" onclick="abrirModalNuevaSerie(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Nueva Serie', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <!-- Grid de series -->
    <div class="flavor-series-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">

        <?php if (empty($podcasts)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px;">
                <span class="dashicons dashicons-book-alt" style="font-size: 64px; color: #ddd;"></span>
                <h3 style="color: #666;"><?php echo esc_html__('No hay series de podcast todavía', 'flavor-chat-ia'); ?></h3>
                <p style="color: #999;"><?php echo esc_html__('Crea tu primera serie para comenzar a publicar episodios', 'flavor-chat-ia'); ?></p>
                <button onclick="abrirModalNuevaSerie()" class="button button-primary button-large">
                    <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Crear Primera Serie', 'flavor-chat-ia'); ?>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($podcasts as $podcast): ?>
                <div class="flavor-serie-card" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease;">

                    <!-- Imagen del podcast -->
                    <div style="position: relative; padding-top: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); overflow: hidden;">
                        <?php if (!empty($podcast->imagen_url)): ?>
                            <img src="<?php echo esc_url($podcast->imagen_url); ?>" alt="<?php echo esc_attr($podcast->titulo); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-microphone" style="font-size: 64px; color: rgba(255,255,255,0.3);"></span>
                            </div>
                        <?php endif; ?>

                        <!-- Badge de estado -->
                        <?php
                        $estado_colors = [
                            'publicado' => '#00a32a',
                            'borrador' => '#dba617',
                            'pausado' => '#d63638'
                        ];
                        ?>
                        <div style="position: absolute; top: 10px; right: 10px; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #fff; background-color: <?php echo $estado_colors[$podcast->estado] ?? '#666'; ?>;">
                            <?php echo ucfirst($podcast->estado); ?>
                        </div>
                    </div>

                    <!-- Información del podcast -->
                    <div style="padding: 20px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 18px;">
                            <a href="#" onclick="verDetalleSerie(<?php echo $podcast->id; ?>); return false;" style="text-decoration: none; color: #2271b1;">
                                <?php echo esc_html($podcast->titulo); ?>
                            </a>
                        </h3>

                        <?php if (!empty($podcast->categoria)): ?>
                            <span style="display: inline-block; padding: 3px 8px; background: #f0f0f1; border-radius: 3px; font-size: 11px; color: #666; margin-bottom: 10px;">
                                <?php echo esc_html($podcast->categoria); ?>
                            </span>
                        <?php endif; ?>

                        <p style="color: #666; font-size: 14px; margin: 10px 0; line-height: 1.5;">
                            <?php echo wp_trim_words($podcast->descripcion, 15); ?>
                        </p>

                        <!-- Estadísticas -->
                        <div style="display: flex; gap: 15px; margin: 15px 0; padding-top: 15px; border-top: 1px solid #f0f0f1; font-size: 13px;">
                            <div style="flex: 1;">
                                <span class="dashicons dashicons-playlist-audio" style="color: #2271b1;"></span>
                                <strong><?php echo $podcast->total_episodios_reales; ?></strong>
                                <div style="color: #666; font-size: 11px;"><?php echo esc_html__('Episodios', 'flavor-chat-ia'); ?></div>
                            </div>
                            <div style="flex: 1;">
                                <span class="dashicons dashicons-groups" style="color: #8c49d8;"></span>
                                <strong><?php echo number_format($podcast->suscriptores); ?></strong>
                                <div style="color: #666; font-size: 11px;"><?php echo esc_html__('Suscriptores', 'flavor-chat-ia'); ?></div>
                            </div>
                            <div style="flex: 1;">
                                <span class="dashicons dashicons-controls-play" style="color: #00a32a;"></span>
                                <strong><?php echo number_format($podcast->total_reproducciones ?? 0); ?></strong>
                                <div style="color: #666; font-size: 11px;"><?php echo esc_html__('Reproducciones', 'flavor-chat-ia'); ?></div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button onclick="editarSerie(<?php echo $podcast->id; ?>)" class="button button-small" style="flex: 1;">
                                <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                            </button>
                            <button onclick="verEpisodiosSerie(<?php echo $podcast->id; ?>)" class="button button-small button-primary" style="flex: 1;">
                                <span class="dashicons dashicons-playlist-audio"></span> <?php echo esc_html__('Episodios', 'flavor-chat-ia'); ?>
                            </button>
                        </div>

                        <div style="margin-top: 10px;">
                            <button onclick="generarRSS(<?php echo $podcast->id; ?>)" class="button button-small" style="width: 100%;">
                                <span class="dashicons dashicons-rss"></span> <?php echo esc_html__('Feed RSS', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</div>

<!-- Modal para nueva serie -->
<div id="modal-nueva-serie" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; width: 90%; max-width: 700px; border-radius: 8px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php echo esc_html__('Nueva Serie de Podcast', 'flavor-chat-ia'); ?>
            </h2>
            <button onclick="cerrarModalNuevaSerie()" style="background: none; border: none; font-size: 24px; cursor: pointer;"><?php echo esc_html__('&times;', 'flavor-chat-ia'); ?></button>
        </div>

        <form id="form-nueva-serie" method="post" enctype="multipart/form-data">

            <div style="margin-bottom: 20px;">
                <label for="serie_titulo" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Título de la Serie:', 'flavor-chat-ia'); ?></label>
                <input type="text" name="titulo" id="serie_titulo" class="regular-text" required style="width: 100%;" placeholder="<?php echo esc_attr__('Ej: Historias del Barrio', 'flavor-chat-ia'); ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="serie_descripcion" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Descripción:', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" id="serie_descripcion" rows="5" class="large-text" required style="width: 100%;" placeholder="<?php echo esc_attr__('Describe de qué trata tu podcast...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="serie_categoria" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Categoría:', 'flavor-chat-ia'); ?></label>
                <input type="text" name="categoria" id="serie_categoria" class="regular-text" list="categorias-existentes" style="width: 100%;" placeholder="<?php echo esc_attr__('Ej: Noticias locales', 'flavor-chat-ia'); ?>">
                <datalist id="categorias-existentes">
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo esc_attr($categoria); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="serie_idioma" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Idioma:', 'flavor-chat-ia'); ?></label>
                <select name="idioma" id="serie_idioma" class="regular-text">
                    <option value="<?php echo esc_attr__('es', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Español', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('eu', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Euskera', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('ca', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Catalán', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('gl', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Gallego', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="serie_imagen" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Imagen de Portada:', 'flavor-chat-ia'); ?></label>
                <input type="file" name="imagen_portada" id="serie_imagen" accept="image/*">
                <p class="description"><?php echo esc_html__('Recomendado: 1400x1400px (formato cuadrado)', 'flavor-chat-ia'); ?></p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="serie_estado" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Estado Inicial:', 'flavor-chat-ia'); ?></label>
                <select name="estado" id="serie_estado" class="regular-text">
                    <option value="<?php echo esc_attr__('publicado', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Publicado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('borrador', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Borrador', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="cerrarModalNuevaSerie()" class="button button-large"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-saved"></span> <?php echo esc_html__('Crear Serie', 'flavor-chat-ia'); ?>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function abrirModalNuevaSerie() {
    document.getElementById('modal-nueva-serie').style.display = 'block';
}

function cerrarModalNuevaSerie() {
    document.getElementById('modal-nueva-serie').style.display = 'none';
    document.getElementById('form-nueva-serie').reset();
}

function verDetalleSerie(serieId) {
    alert('Ver detalles de serie #' + serieId);
}

function editarSerie(serieId) {
    alert('Editar serie #' + serieId);
}

function verEpisodiosSerie(serieId) {
    window.location.href = '?page=flavor-chat-podcast-episodios&podcast_id=' + serieId;
}

function generarRSS(serieId) {
    const rssUrl = '<?php echo home_url(); ?>/feed/podcast/' + serieId;
    prompt('URL del Feed RSS:', rssUrl);
}

jQuery(document).ready(function($) {
    // Hover effect en las tarjetas
    $('.flavor-serie-card').hover(
        function() {
            $(this).css({
                'transform': 'translateY(-5px)',
                'box-shadow': '0 8px 16px rgba(0,0,0,0.15)'
            });
        },
        function() {
            $(this).css({
                'transform': 'translateY(0)',
                'box-shadow': '0 2px 4px rgba(0,0,0,0.1)'
            });
        }
    );
});
</script>

<style>
.flavor-serie-card {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .flavor-series-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
