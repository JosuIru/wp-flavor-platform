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
        Gestión de Categorías
        <a href="#" class="page-title-action" onclick="abrirModalNuevaCategoria(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> Nueva Categoría
        </a>
    </h1>

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;">Total Categorías</p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($total_categorias); ?></h2>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;">Con Contenido</p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($con_contenido); ?></h2>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;">Sin Contenido</p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #999;"><?php echo number_format($sin_contenido); ?></h2>
        </div>

    </div>

    <!-- Tabla de categorías -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <?php if (empty($categorias)): ?>
            <div style="text-align: center; padding: 60px;">
                <span class="dashicons dashicons-category" style="font-size: 64px; color: #ddd;"></span>
                <h3 style="color: #666;">No hay categorías creadas</h3>
                <button onclick="abrirModalNuevaCategoria()" class="button button-primary button-large">
                    Crear Primera Categoría
                </button>
            </div>
        <?php else: ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 80px;">Color</th>
                        <th>Nombre</th>
                        <th style="width: 200px;">Slug</th>
                        <th style="width: 120px;">Total Items</th>
                        <th style="width: 150px;">Fecha Creación</th>
                        <th style="width: 150px;">Acciones</th>
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
                                    <a href="?page=flavor-chat-multimedia-galeria&categoria=<?php echo urlencode($categoria->slug); ?>" style="color: #2271b1; font-weight: 600;">
                                        <?php echo number_format($categoria->total_items); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($categoria->fecha_creacion)); ?></td>
                            <td>
                                <button class="button button-small" onclick="editarCategoria(<?php echo $categoria->id; ?>)">
                                    <span class="dashicons dashicons-edit"></span> Editar
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
            Nueva Categoría
        </h2>

        <form id="form-nueva-categoria">
            <div style="margin-bottom: 20px;">
                <label>Nombre de la Categoría:</label>
                <input type="text" name="nombre" class="regular-text" required style="width: 100%;" placeholder="Ej: Eventos Comunitarios">
            </div>

            <div style="margin-bottom: 20px;">
                <label>Slug (URL amigable):</label>
                <input type="text" name="slug" class="regular-text" required style="width: 100%;" placeholder="eventos-comunitarios">
                <p class="description">Solo letras minúsculas, números y guiones</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Descripción:</label>
                <textarea name="descripcion" rows="3" class="large-text" style="width: 100%;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Color:</label>
                <input type="color" name="color" value="#2271b1" style="width: 100px; height: 40px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="destacada" value="1">
                    Categoría destacada
                </label>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="cerrarModalNuevaCategoria()" class="button button-large">Cancelar</button>
                <button type="submit" class="button button-primary button-large">Crear Categoría</button>
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
    alert('Editar categoría #' + id);
}

function eliminarCategoria(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.')) {
        alert('Eliminar categoría #' + id);
    }
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
