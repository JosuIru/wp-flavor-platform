<?php
/**
 * Vista de Galería Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

// Filtros
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';

$where_clauses = ["estado = 'aprobado'"];
$prepare_values = [];

if ($tipo_filtro) {
    $where_clauses[] = 'tipo = %s';
    $prepare_values[] = $tipo_filtro;
}

if ($categoria_filtro) {
    $where_clauses[] = 'categoria = %s';
    $prepare_values[] = $categoria_filtro;
}

$where_sql = implode(' AND ', $where_clauses);

if (!empty($prepare_values)) {
    $multimedia = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $tabla_multimedia WHERE $where_sql ORDER BY fecha_subida DESC
    ", ...$prepare_values));
} else {
    $multimedia = $wpdb->get_results("
        SELECT * FROM $tabla_multimedia WHERE $where_sql ORDER BY fecha_subida DESC
    ");
}

$categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_multimedia WHERE categoria IS NOT NULL ORDER BY categoria");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-format-gallery"></span>
        Galería Multimedia
        <a href="#" class="page-title-action" onclick="abrirModalSubir(); return false;">
            <span class="dashicons dashicons-upload"></span> Subir Archivo
        </a>
    </h1>

    <!-- Filtros -->
    <div class="flavor-filters" style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

            <div style="flex: 1; min-width: 200px;">
                <label for="tipo">Tipo:</label>
                <select name="tipo" id="tipo" class="regular-text">
                    <option value="">Todos</option>
                    <option value="foto" <?php selected($tipo_filtro, 'foto'); ?>>Fotos</option>
                    <option value="video" <?php selected($tipo_filtro, 'video'); ?>>Videos</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label for="categoria">Categoría:</label>
                <select name="categoria" id="categoria" class="regular-text">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo esc_attr($categoria); ?>" <?php selected($categoria_filtro, $categoria); ?>>
                            <?php echo esc_html($categoria); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button button-primary">Filtrar</button>
            <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">Limpiar</a>
        </form>
    </div>

    <!-- Galería -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="flavor-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php if (empty($multimedia)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                    <span class="dashicons dashicons-format-gallery" style="font-size: 64px; color: #ddd;"></span>
                    <h3 style="color: #666;">No se encontró contenido</h3>
                </div>
            <?php else: ?>
                <?php foreach ($multimedia as $item): ?>
                    <div class="flavor-media-item" style="position: relative; padding-top: 100%; background: #f0f0f1; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.3s ease;" onclick="verDetalle(<?php echo $item->id; ?>)">
                        <?php if ($item->tipo == 'foto'): ?>
                            <img src="<?php echo esc_url($item->url); ?>" alt="<?php echo esc_attr($item->titulo ?? ''); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-video-alt3" style="font-size: 48px; color: #fff;"></span>
                            </div>
                        <?php endif; ?>

                        <!-- Overlay con info -->
                        <div class="media-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent); padding: 15px 10px 10px; opacity: 0; transition: opacity 0.3s;">
                            <?php if ($item->titulo): ?>
                                <strong style="display: block; color: #fff; font-size: 12px; margin-bottom: 5px;"><?php echo esc_html($item->titulo); ?></strong>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <small style="color: rgba(255,255,255,0.8);">
                                    <span class="dashicons dashicons-visibility" style="font-size: 12px;"></span>
                                    <?php echo number_format($item->visualizaciones ?? 0); ?>
                                </small>
                                <small style="color: rgba(255,255,255,0.8);">
                                    <span class="dashicons dashicons-heart" style="font-size: 12px;"></span>
                                    <?php echo number_format($item->me_gusta ?? 0); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Tipo badge -->
                        <div style="position: absolute; top: 10px; right: 10px; padding: 4px 8px; background: rgba(0,0,0,0.7); color: #fff; border-radius: 3px; font-size: 10px; font-weight: 600;">
                            <?php echo strtoupper($item->tipo); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function abrirModalSubir() {
    alert('Subir nuevo archivo multimedia');
}

function verDetalle(id) {
    alert('Ver detalle de multimedia #' + id);
}

jQuery(document).ready(function($) {
    $('.flavor-media-item').hover(
        function() {
            $(this).find('.media-overlay').css('opacity', '1');
            $(this).css({'transform': 'scale(1.05)', 'z-index': '10'});
        },
        function() {
            $(this).find('.media-overlay').css('opacity', '0');
            $(this).css({'transform': 'scale(1)', 'z-index': '1'});
        }
    );
});
</script>

<style>
.flavor-media-item {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-media-item:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
}
</style>
