<?php
/**
 * Vista de Categorías - Módulo Foros
 *
 * @package FlavorChatIA
 * @subpackage Foros
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_categorias = $wpdb->prefix . 'flavor_foros_categorias';
$tabla_temas = $wpdb->prefix . 'flavor_foros_temas';

// Procesar acciones
$mensaje = '';
$mensaje_tipo = '';

if (isset($_POST['crear_categoria']) && wp_verify_nonce($_POST['_wpnonce'], 'crear_categoria_foro')) {
    $nombre = sanitize_text_field($_POST['nombre'] ?? '');
    $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
    $icono = sanitize_text_field($_POST['icono'] ?? 'dashicons-category');
    $color = sanitize_hex_color($_POST['color'] ?? '#2271b1');
    $orden = absint($_POST['orden'] ?? 0);
    $padre_id = absint($_POST['padre_id'] ?? 0);

    if (!empty($nombre)) {
        $resultado = $wpdb->insert($tabla_categorias, [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'icono' => $icono,
            'color' => $color,
            'orden' => $orden,
            'padre_id' => $padre_id,
            'activo' => 1,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $mensaje = __('Categoría creada correctamente.', 'flavor-chat-ia');
            $mensaje_tipo = 'success';
        } else {
            $mensaje = __('Error al crear la categoría.', 'flavor-chat-ia');
            $mensaje_tipo = 'error';
        }
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'eliminar_categoria_' . $_GET['id'])) {
        $id = absint($_GET['id']);
        $wpdb->delete($tabla_categorias, ['id' => $id]);
        $mensaje = __('Categoría eliminada.', 'flavor-chat-ia');
        $mensaje_tipo = 'success';
    }
}

// Obtener categorías
$categorias = $wpdb->get_results("
    SELECT c.*,
           (SELECT COUNT(*) FROM {$tabla_temas} t WHERE t.categoria_id = c.id) as total_temas,
           (SELECT nombre FROM {$tabla_categorias} p WHERE p.id = c.padre_id) as padre_nombre
    FROM {$tabla_categorias} c
    ORDER BY c.orden ASC, c.nombre ASC
");

// Categorías para selector de padre
$categorias_padre = $wpdb->get_results("SELECT id, nombre FROM {$tabla_categorias} WHERE padre_id = 0 OR padre_id IS NULL ORDER BY nombre");
?>

<div class="wrap flavor-foros-categorias">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-category"></span>
        <?php esc_html_e('Categorías del Foro', 'flavor-chat-ia'); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-nueva-categoria">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php esc_html_e('Nueva Categoría', 'flavor-chat-ia'); ?>
    </button>
    <hr class="wp-header-end">

    <?php if ($mensaje): ?>
        <div class="notice notice-<?php echo esc_attr($mensaje_tipo); ?> is-dismissible">
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-categorias-layout">
        <!-- Listado de categorías -->
        <div class="dm-card dm-card--categorias">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Categorías', 'flavor-chat-ia'); ?></h3>
                <span class="dm-badge"><?php echo count($categorias); ?></span>
            </div>
            <div class="dm-card__body">
                <?php if ($categorias): ?>
                    <table class="widefat striped dm-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><?php esc_html_e('Orden', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Padre', 'flavor-chat-ia'); ?></th>
                                <th class="num"><?php esc_html_e('Temas', 'flavor-chat-ia'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td>
                                        <span class="dm-orden"><?php echo esc_html($categoria->orden); ?></span>
                                    </td>
                                    <td>
                                        <div class="dm-categoria-nombre">
                                            <span class="dashicons <?php echo esc_attr($categoria->icono ?: 'dashicons-category'); ?>" style="color: <?php echo esc_attr($categoria->color ?: '#2271b1'); ?>;"></span>
                                            <strong><?php echo esc_html($categoria->nombre); ?></strong>
                                        </div>
                                        <?php if ($categoria->descripcion): ?>
                                            <p class="dm-descripcion"><?php echo esc_html($categoria->descripcion); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($categoria->padre_nombre): ?>
                                            <span class="dm-padre-badge"><?php echo esc_html($categoria->padre_nombre); ?></span>
                                        <?php else: ?>
                                            <span class="dm-raiz"><?php esc_html_e('Raíz', 'flavor-chat-ia'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num">
                                        <span class="dm-count"><?php echo number_format_i18n($categoria->total_temas); ?></span>
                                    </td>
                                    <td>
                                        <div class="dm-acciones">
                                            <button type="button" class="button button-small btn-editar" data-id="<?php echo esc_attr($categoria->id); ?>">
                                                <span class="dashicons dashicons-edit"></span>
                                            </button>
                                            <?php if ($categoria->total_temas == 0): ?>
                                                <a href="<?php echo wp_nonce_url(add_query_arg(['accion' => 'eliminar', 'id' => $categoria->id]), 'eliminar_categoria_' . $categoria->id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('¿Eliminar esta categoría?', 'flavor-chat-ia'); ?>')">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="dm-empty-state">
                        <span class="dashicons dashicons-category"></span>
                        <p><?php esc_html_e('No hay categorías creadas.', 'flavor-chat-ia'); ?></p>
                        <button type="button" class="button button-primary" id="btn-crear-primera">
                            <?php esc_html_e('Crear primera categoría', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario de nueva categoría -->
        <div class="dm-card dm-card--form" id="form-categoria" style="display: none;">
            <div class="dm-card__header">
                <h3 id="form-titulo"><?php esc_html_e('Nueva Categoría', 'flavor-chat-ia'); ?></h3>
                <button type="button" class="dm-close-btn" id="btn-cerrar-form">&times;</button>
            </div>
            <div class="dm-card__body">
                <form method="post">
                    <?php wp_nonce_field('crear_categoria_foro'); ?>
                    <input type="hidden" name="categoria_id" id="categoria_id" value="">

                    <div class="dm-form-group">
                        <label for="nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?> *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="2"></textarea>
                    </div>

                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="icono"><?php esc_html_e('Icono', 'flavor-chat-ia'); ?></label>
                            <select id="icono" name="icono">
                                <option value="dashicons-category">Categoría</option>
                                <option value="dashicons-admin-comments">Comentarios</option>
                                <option value="dashicons-groups">Grupos</option>
                                <option value="dashicons-lightbulb">Ideas</option>
                                <option value="dashicons-welcome-learn-more">Aprendizaje</option>
                                <option value="dashicons-megaphone">Anuncios</option>
                                <option value="dashicons-admin-tools">Herramientas</option>
                                <option value="dashicons-admin-generic">General</option>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="color"><?php esc_html_e('Color', 'flavor-chat-ia'); ?></label>
                            <input type="color" id="color" name="color" value="#2271b1">
                        </div>
                    </div>

                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="padre_id"><?php esc_html_e('Categoría Padre', 'flavor-chat-ia'); ?></label>
                            <select id="padre_id" name="padre_id">
                                <option value="0"><?php esc_html_e('Ninguna (Raíz)', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($categorias_padre as $padre): ?>
                                    <option value="<?php echo esc_attr($padre->id); ?>"><?php echo esc_html($padre->nombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="orden"><?php esc_html_e('Orden', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="orden" name="orden" value="0" min="0">
                        </div>
                    </div>

                    <div class="dm-form-actions">
                        <button type="submit" name="crear_categoria" class="button button-primary">
                            <?php esc_html_e('Guardar Categoría', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="button" id="btn-cancelar">
                            <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-foros-categorias { max-width: 1200px; }
.dm-categorias-layout { display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px; }

.dm-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.dm-card__header { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; display: flex; align-items: center; justify-content: space-between; }
.dm-card__header h3 { margin: 0; font-size: 14px; }
.dm-card__body { padding: 20px; }

.dm-badge { background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
.dm-close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #646970; line-height: 1; }

.dm-table { border: none; }
.dm-table th { font-weight: 600; font-size: 13px; }
.dm-table td.num { text-align: right; }

.dm-categoria-nombre { display: flex; align-items: center; gap: 8px; }
.dm-categoria-nombre .dashicons { font-size: 18px; width: 18px; height: 18px; }
.dm-descripcion { margin: 5px 0 0; font-size: 12px; color: #646970; }
.dm-orden { display: inline-block; width: 24px; height: 24px; background: #f0f0f1; border-radius: 4px; text-align: center; line-height: 24px; font-size: 12px; }
.dm-padre-badge { background: #f0f0f1; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
.dm-raiz { color: #646970; font-style: italic; font-size: 12px; }
.dm-count { font-weight: 600; }

.dm-acciones { display: flex; gap: 5px; }
.dm-acciones .button { padding: 0 6px; }
.dm-acciones .dashicons { font-size: 16px; width: 16px; height: 16px; line-height: 1.5; }

.dm-empty-state { text-align: center; padding: 40px 20px; }
.dm-empty-state .dashicons { font-size: 48px; width: 48px; height: 48px; color: #dcdcde; margin-bottom: 15px; }
.dm-empty-state p { color: #646970; margin-bottom: 15px; }

.dm-card--form { position: sticky; top: 32px; }

.dm-form-group { margin-bottom: 15px; }
.dm-form-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; }
.dm-form-group input[type="text"], .dm-form-group input[type="number"], .dm-form-group select, .dm-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 14px; }
.dm-form-group input[type="color"] { width: 100%; height: 38px; padding: 2px; cursor: pointer; }
.dm-form-group textarea { resize: vertical; }
.dm-form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }

.dm-form-actions { display: flex; gap: 10px; padding-top: 10px; border-top: 1px solid #f0f0f1; margin-top: 20px; }

@media (min-width: 1024px) {
    .dm-categorias-layout { grid-template-columns: 2fr 1fr; }
    .dm-card--form { display: block !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var formCategoria = document.getElementById('form-categoria');
    var btnNueva = document.getElementById('btn-nueva-categoria');
    var btnPrimera = document.getElementById('btn-crear-primera');
    var btnCerrar = document.getElementById('btn-cerrar-form');
    var btnCancelar = document.getElementById('btn-cancelar');

    function mostrarForm() {
        formCategoria.style.display = 'block';
        document.getElementById('nombre').focus();
    }

    function ocultarForm() {
        formCategoria.style.display = 'none';
        document.getElementById('categoria_id').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('descripcion').value = '';
    }

    if (btnNueva) btnNueva.addEventListener('click', mostrarForm);
    if (btnPrimera) btnPrimera.addEventListener('click', mostrarForm);
    if (btnCerrar) btnCerrar.addEventListener('click', ocultarForm);
    if (btnCancelar) btnCancelar.addEventListener('click', ocultarForm);
});
</script>
