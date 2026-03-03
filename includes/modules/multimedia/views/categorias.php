<?php
/**
 * Vista de Gestión de Categorías Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
$tabla_categorias = $wpdb->prefix . 'flavor_multimedia_categorias';

// Obtener categorías con estadísticas
$categorias = $wpdb->get_results("
    SELECT c.*, COUNT(m.id) as total_items
    FROM $tabla_categorias c
    LEFT JOIN $tabla_multimedia m ON c.slug = m.categoria
    GROUP BY c.id
    ORDER BY c.nombre
");

// Obtener estadísticas generales
$total_categorias = count($categorias);
$con_contenido = count(array_filter($categorias, function($c) { return $c->total_items > 0; }));
$sin_contenido = $total_categorias - $con_contenido;
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-category"></span>
        <?php echo esc_html__('Gestión de Categorías', 'flavor-chat-ia'); ?>
        <button type="button" class="page-title-action" onclick="abrirModalNuevaCategoria();">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Nueva Categoría', 'flavor-chat-ia'); ?>
        </button>
    </h1>

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Total Categorías', 'flavor-chat-ia'); ?></p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($total_categorias); ?></h2>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Con Contenido', 'flavor-chat-ia'); ?></p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($con_contenido); ?></h2>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Sin Contenido', 'flavor-chat-ia'); ?></p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #999;"><?php echo number_format($sin_contenido); ?></h2>
        </div>

    </div>

    <!-- Tabla de categorías -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <?php if (empty($categorias)): ?>
            <div style="text-align: center; padding: 60px;">
                <span class="dashicons dashicons-category" style="font-size: 64px; color: #ddd;"></span>
                <h3 style="color: #666;"><?php echo esc_html__('No hay categorías creadas', 'flavor-chat-ia'); ?></h3>
                <button onclick="abrirModalNuevaCategoria()" class="button button-primary button-large">
                    <?php echo esc_html__('Crear Primera Categoría', 'flavor-chat-ia'); ?>
                </button>
            </div>
        <?php else: ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Color', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></th>
                        <th style="width: 200px;"><?php echo esc_html__('Slug', 'flavor-chat-ia'); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Total Items', 'flavor-chat-ia'); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Fecha Creación', 'flavor-chat-ia'); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><strong>#<?php echo $categoria->id; ?></strong></td>
                            <td>
                                <div style="width: 40px; height: 40px; background-color: <?php echo esc_attr($categoria->color ?? '#2271b1'); ?>; border-radius: 4px; border: 1px solid #ddd;"></div>
                            </td>
                            <td>
                                <strong><?php echo esc_html($categoria->nombre); ?></strong>
                                <?php if ($categoria->descripcion): ?>
                                    <div style="color: #666; font-size: 12px;">
                                        <?php echo wp_trim_words($categoria->descripcion, 10); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html($categoria->slug); ?></code>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($categoria->total_items > 0): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-galeria&categoria=' . urlencode($categoria->slug))); ?>" style="color: #2271b1; font-weight: 600;">
                                        <?php echo number_format($categoria->total_items); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($categoria->fecha_creacion)); ?></td>
                            <td>
                                <button class="button button-small" onclick="editarCategoria(<?php echo $categoria->id; ?>)">
                                    <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                                </button>
                                <?php if ($categoria->total_items == 0): ?>
                                    <button class="button button-small button-link-delete" onclick="eliminarCategoria(<?php echo $categoria->id; ?>)">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<!-- Modal para nueva categoría -->
<div id="modal-nueva-categoria" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 10% auto; padding: 30px; width: 90%; max-width: 600px; border-radius: 8px;">
        <h2>
            <span class="dashicons dashicons-plus-alt"></span>
            <?php echo esc_html__('Nueva Categoría', 'flavor-chat-ia'); ?>
        </h2>

        <form id="form-nueva-categoria">
            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Nombre de la Categoría:', 'flavor-chat-ia'); ?></label>
                <input type="text" name="nombre" class="regular-text" required style="width: 100%;" placeholder="<?php echo esc_attr__('Ej: Eventos Comunitarios', 'flavor-chat-ia'); ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Slug (URL amigable):', 'flavor-chat-ia'); ?></label>
                <input type="text" name="slug" class="regular-text" required style="width: 100%;" placeholder="<?php echo esc_attr__('eventos-comunitarios', 'flavor-chat-ia'); ?>">
                <p class="description"><?php echo esc_html__('Solo letras minúsculas, números y guiones', 'flavor-chat-ia'); ?></p>
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Descripción:', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" rows="3" class="large-text" style="width: 100%;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Color:', 'flavor-chat-ia'); ?></label>
                <input type="color" name="color" value="<?php echo esc_attr__('#2271b1', 'flavor-chat-ia'); ?>" style="width: 100px; height: 40px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="destacada" value="1">
                    <?php echo esc_html__('Categoría destacada', 'flavor-chat-ia'); ?>
                </label>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="cerrarModalNuevaCategoria()" class="button button-large"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary button-large"><?php echo esc_html__('Crear Categoría', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevaCategoria() {
    document.getElementById('modal-nueva-categoria').style.display = 'block';
}

function cerrarModalNuevaCategoria() {
    document.getElementById('modal-nueva-categoria').style.display = 'none';
    document.getElementById('form-nueva-categoria').reset();
}

function editarCategoria(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=multimedia-categorias&editar='); ?>' + id;
}

function mmCategoriasAviso(mensaje, tipo) {
    var contenedor = document.getElementById('mm-categorias-notice');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'mm-categorias-notice';
        contenedor.style.marginBottom = '16px';
        var wrap = document.querySelector('.wrap');
        if (wrap) {
            wrap.insertBefore(contenedor, wrap.children[1] || null);
        } else {
            document.body.prepend(contenedor);
        }
    }
    contenedor.innerHTML = '<div class="notice notice-' + (tipo === 'error' ? 'error' : 'success') + ' is-dismissible"><p>' + mensaje + '</p></div>';
}

function mmCategoriasConfirmar(mensaje, onConfirm) {
    var contenedor = document.getElementById('mm-categorias-notice');
    if (!contenedor) {
        mmCategoriasAviso('', 'success');
        contenedor = document.getElementById('mm-categorias-notice');
    }
    contenedor.innerHTML =
        '<div class="notice notice-warning"><p>' + mensaje + '</p>' +
        '<p style="display:flex;gap:8px;margin-top:8px;">' +
        '<button type="button" class="button button-primary" id="mm-categorias-confirmar">Confirmar</button>' +
        '<button type="button" class="button" id="mm-categorias-cancelar">Cancelar</button>' +
        '</p></div>';
    document.getElementById('mm-categorias-confirmar').onclick = function() {
        contenedor.innerHTML = '';
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    };
    document.getElementById('mm-categorias-cancelar').onclick = function() {
        contenedor.innerHTML = '';
    };
}

function eliminarCategoria(id) {
    mmCategoriasConfirmar('<?php echo esc_js(__('¿Eliminar esta categoría? Esta acción no se puede deshacer.', 'flavor-chat-ia')); ?>', function() {
        jQuery.post(ajaxurl, {
            action: 'flavor_multimedia_eliminar_categoria',
            categoria_id: id,
            nonce: '<?php echo wp_create_nonce('multimedia_categoria_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                mmCategoriasAviso(response.data || '<?php echo esc_js(__('Error al eliminar', 'flavor-chat-ia')); ?>', 'error');
            }
        });
    });
}

// Auto-generar slug desde el nombre
jQuery(document).ready(function($) {
    $('input[name="nombre"]').on('input', function() {
        const nombre = $(this).val();
        const slug = nombre
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        $('input[name="slug"]').val(slug);
    });
});
</script>

<style>
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}

.wp-list-table tbody tr:hover {
    background-color: #f6f7f7;
}
</style>
