<?php
/**
 * Vista de Gestión de Álbumes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

$albumes = $wpdb->get_results("
    SELECT a.*, COUNT(m.id) as total_items,
           (SELECT url FROM $tabla_multimedia WHERE album_id = a.id AND tipo = 'foto' ORDER BY fecha_subida LIMIT 1) as imagen_portada
    FROM $tabla_albumes a
    LEFT JOIN $tabla_multimedia m ON a.id = m.album_id
    GROUP BY a.id
    ORDER BY a.fecha_creacion DESC
");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-images-alt2"></span>
        <?php echo esc_html__('Gestión de Álbumes', 'flavor-chat-ia'); ?>
        <button type="button" class="page-title-action" onclick="abrirModalNuevoAlbum();">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Nuevo Álbum', 'flavor-chat-ia'); ?>
        </button>
    </h1>

    <!-- Grid de álbumes -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 20px 0;">
        <?php if (empty($albumes)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: #fff; border-radius: 8px;">
                <span class="dashicons dashicons-images-alt2" style="font-size: 64px; color: #ddd;"></span>
                <h3 style="color: #666;"><?php echo esc_html__('No hay álbumes creados', 'flavor-chat-ia'); ?></h3>
                <button onclick="abrirModalNuevoAlbum()" class="button button-primary button-large">
                    <?php echo esc_html__('Crear Primer Álbum', 'flavor-chat-ia'); ?>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($albumes as $album): ?>
                <div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer;" onclick="verAlbum(<?php echo $album->id; ?>)">
                    <!-- Portada del álbum -->
                    <div style="position: relative; padding-top: 75%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); overflow: hidden;">
                        <?php if ($album->imagen_portada): ?>
                            <img src="<?php echo esc_url($album->imagen_portada); ?>" alt="<?php echo esc_attr($album->titulo); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-images-alt2" style="font-size: 64px; color: rgba(255,255,255,0.3);"></span>
                            </div>
                        <?php endif; ?>

                        <!-- Badge con cantidad -->
                        <div style="position: absolute; top: 10px; right: 10px; padding: 5px 10px; background: rgba(0,0,0,0.7); color: #fff; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            <span class="dashicons dashicons-images-alt" style="font-size: 14px;"></span>
                            <?php echo $album->total_items; ?>
                        </div>
                    </div>

                    <!-- Información del álbum -->
                    <div style="padding: 15px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 16px;"><?php echo esc_html($album->titulo); ?></h3>

                        <?php if ($album->descripcion): ?>
                            <p style="color: #666; font-size: 13px; margin: 0 0 15px 0; line-height: 1.4;">
                                <?php echo wp_trim_words($album->descripcion, 12); ?>
                            </p>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid #f0f0f1; font-size: 12px; color: #666;">
                            <span>
                                <span class="dashicons dashicons-calendar" style="font-size: 14px;"></span>
                                <?php echo date_i18n('d/m/Y', strtotime($album->fecha_creacion)); ?>
                            </span>
                            <?php if ($album->publico): ?>
                                <span style="color: #00a32a;">
                                    <span class="dashicons dashicons-visibility" style="font-size: 14px;"></span>
                                    <?php echo esc_html__('Público', 'flavor-chat-ia'); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">
                                    <span class="dashicons dashicons-hidden" style="font-size: 14px;"></span>
                                    <?php echo esc_html__('Privado', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button onclick="event.stopPropagation(); editarAlbum(<?php echo $album->id; ?>)" class="button button-small" style="flex: 1;">
                                <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                            </button>
                            <button onclick="event.stopPropagation(); verAlbum(<?php echo $album->id; ?>)" class="button button-primary button-small" style="flex: 1;">
                                <span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<div id="modal-album" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalAlbum()"></div>
    <div class="flavor-modal-content" style="min-width:400px;">
        <button class="flavor-modal-close" onclick="cerrarModalAlbum()">&times;</button>
        <h3><?php echo esc_html__('Nuevo Álbum', 'flavor-chat-ia'); ?></h3>
        <form id="form-nuevo-album" method="post">
            <?php wp_nonce_field('nuevo_album', 'album_nonce'); ?>
            <input type="hidden" name="accion" value="crear_album">
            <div class="form-row" style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Nombre del álbum', 'flavor-chat-ia'); ?></label>
                <input type="text" name="nombre" required style="width:100%;padding:8px;">
            </div>
            <div class="form-row" style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Descripción', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" rows="3" style="width:100%;padding:8px;"></textarea>
            </div>
            <div style="text-align:right;">
                <button type="button" class="button" onclick="cerrarModalAlbum()"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Crear Álbum', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevoAlbum() {
    document.getElementById('modal-album').style.display = 'block';
}

function cerrarModalAlbum() {
    document.getElementById('modal-album').style.display = 'none';
}

function verAlbum(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=multimedia-galeria&album_id='); ?>' + id;
}

function editarAlbum(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=multimedia-albumes&editar='); ?>' + id;
}
</script>

<style>
.wrap > div > div:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
}
</style>
