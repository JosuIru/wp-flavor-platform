<?php
/**
 * Vista Admin: Gestión de Consumidores
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();

// Obtener grupo actual (por defecto primer grupo)
$grupo_id = isset($_GET['grupo_id']) ? absint($_GET['grupo_id']) : 0;

if (!$grupo_id) {
    $grupos = get_posts([
        'post_type' => 'gc_grupo',
        'posts_per_page' => 1,
        'post_status' => 'publish',
    ]);
    if (!empty($grupos)) {
        $grupo_id = $grupos[0]->ID;
    }
}

// Paginación y filtros
$pagina_actual = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$por_pagina = 20;
$offset = ($pagina_actual - 1) * $por_pagina;

$filtros = [
    'estado' => isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '',
    'rol' => isset($_GET['rol']) ? sanitize_text_field($_GET['rol']) : '',
    'busqueda' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
];

// Obtener consumidores
$resultado = $grupo_id ? $consumidor_manager->listar_consumidores($grupo_id, $filtros, $por_pagina, $offset) : ['consumidores' => [], 'total' => 0, 'paginas' => 0];
$consumidores = $resultado['consumidores'];
$total_consumidores = $resultado['total'];
$total_paginas = $resultado['paginas'];

// Estadísticas
$estadisticas = $grupo_id ? $consumidor_manager->obtener_estadisticas($grupo_id) : [];

// Obtener grupos para selector
$todos_los_grupos = get_posts([
    'post_type' => 'gc_grupo',
    'posts_per_page' => -1,
    'post_status' => 'publish',
]);
?>

<div class="wrap gc-admin-consumidores">
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Consumidores', 'flavor-chat-ia'); ?>
    </h1>
    <a href="#" class="page-title-action gc-modal-trigger" data-modal="modal-nuevo-consumidor">
        <?php _e('Añadir Consumidor', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Selector de Grupo -->
    <?php if (count($todos_los_grupos) > 1): ?>
        <div class="gc-grupo-selector">
            <label for="gc-grupo-select"><?php _e('Grupo:', 'flavor-chat-ia'); ?></label>
            <select id="gc-grupo-select" onchange="window.location.href='<?php echo admin_url('admin.php?page=gc-consumidores&grupo_id='); ?>'+this.value">
                <?php foreach ($todos_los_grupos as $grupo): ?>
                    <option value="<?php echo $grupo->ID; ?>" <?php selected($grupo_id, $grupo->ID); ?>>
                        <?php echo esc_html($grupo->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <?php if (!$grupo_id): ?>
        <div class="notice notice-warning">
            <p><?php _e('No hay ningún grupo de consumo creado. Crea uno primero.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>

    <!-- Estadísticas -->
    <div class="gc-stats-grid">
        <div class="gc-stat-card">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['total'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Total Miembros', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-activo">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['por_estado']['activo'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Activos', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-pendiente">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['por_estado']['pendiente'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Pendientes', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="gc-stat-card">
            <span class="gc-stat-numero">+<?php echo esc_html($estadisticas['altas_mes'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Altas este mes', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Filtros -->
    <div class="gc-filtros-wrapper">
        <form method="get" class="gc-filtros-form">
            <input type="hidden" name="page" value="gc-consumidores">
            <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo_id); ?>">

            <div class="gc-filtro">
                <label for="filtro-estado"><?php _e('Estado:', 'flavor-chat-ia'); ?></label>
                <select id="filtro-estado" name="estado">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="pendiente" <?php selected($filtros['estado'], 'pendiente'); ?>><?php _e('Pendiente', 'flavor-chat-ia'); ?></option>
                    <option value="activo" <?php selected($filtros['estado'], 'activo'); ?>><?php _e('Activo', 'flavor-chat-ia'); ?></option>
                    <option value="suspendido" <?php selected($filtros['estado'], 'suspendido'); ?>><?php _e('Suspendido', 'flavor-chat-ia'); ?></option>
                    <option value="baja" <?php selected($filtros['estado'], 'baja'); ?>><?php _e('Baja', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="gc-filtro">
                <label for="filtro-rol"><?php _e('Rol:', 'flavor-chat-ia'); ?></label>
                <select id="filtro-rol" name="rol">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="consumidor" <?php selected($filtros['rol'], 'consumidor'); ?>><?php _e('Consumidor', 'flavor-chat-ia'); ?></option>
                    <option value="coordinador" <?php selected($filtros['rol'], 'coordinador'); ?>><?php _e('Coordinador', 'flavor-chat-ia'); ?></option>
                    <option value="productor" <?php selected($filtros['rol'], 'productor'); ?>><?php _e('Productor', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="gc-filtro gc-filtro-busqueda">
                <label for="filtro-busqueda" class="screen-reader-text"><?php _e('Buscar', 'flavor-chat-ia'); ?></label>
                <input type="search" id="filtro-busqueda" name="s" value="<?php echo esc_attr($filtros['busqueda']); ?>" placeholder="<?php _e('Buscar...', 'flavor-chat-ia'); ?>">
            </div>

            <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=gc-consumidores&grupo_id=' . $grupo_id); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
        </form>
    </div>

    <!-- Tabla de Consumidores -->
    <table class="wp-list-table widefat fixed striped gc-tabla-consumidores">
        <thead>
            <tr>
                <th scope="col" class="column-nombre"><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-email"><?php _e('Email', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-rol"><?php _e('Rol', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-saldo"><?php _e('Saldo', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-fecha"><?php _e('Alta', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-acciones"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($consumidores)): ?>
                <tr>
                    <td colspan="7"><?php _e('No se encontraron consumidores.', 'flavor-chat-ia'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($consumidores as $consumidor): ?>
                    <tr data-consumidor-id="<?php echo esc_attr($consumidor->id); ?>">
                        <td class="column-nombre">
                            <strong>
                                <a href="<?php echo esc_url(get_edit_user_link($consumidor->usuario_id)); ?>">
                                    <?php echo esc_html($consumidor->display_name); ?>
                                </a>
                            </strong>
                            <?php if ($consumidor->preferencias_alimentarias || $consumidor->alergias): ?>
                                <span class="gc-tiene-notas dashicons dashicons-info" title="<?php _e('Tiene preferencias/alergias', 'flavor-chat-ia'); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-email">
                            <a href="mailto:<?php echo esc_attr($consumidor->user_email); ?>">
                                <?php echo esc_html($consumidor->user_email); ?>
                            </a>
                        </td>
                        <td class="column-rol">
                            <select class="gc-cambiar-rol" data-consumidor-id="<?php echo esc_attr($consumidor->id); ?>">
                                <option value="consumidor" <?php selected($consumidor->rol, 'consumidor'); ?>><?php _e('Consumidor', 'flavor-chat-ia'); ?></option>
                                <option value="coordinador" <?php selected($consumidor->rol, 'coordinador'); ?>><?php _e('Coordinador', 'flavor-chat-ia'); ?></option>
                                <option value="productor" <?php selected($consumidor->rol, 'productor'); ?>><?php _e('Productor', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                        <td class="column-estado">
                            <span class="gc-estado-badge <?php echo esc_attr($consumidor_manager->obtener_clase_estado($consumidor->estado)); ?>">
                                <?php echo esc_html($consumidor_manager->obtener_etiqueta_estado($consumidor->estado)); ?>
                            </span>
                        </td>
                        <td class="column-saldo <?php echo $consumidor->saldo_pendiente > 0 ? 'gc-saldo-pendiente' : ''; ?>">
                            <?php echo number_format($consumidor->saldo_pendiente, 2); ?> €
                        </td>
                        <td class="column-fecha">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($consumidor->fecha_alta))); ?>
                        </td>
                        <td class="column-acciones">
                            <div class="gc-acciones-dropdown">
                                <button type="button" class="button gc-acciones-btn">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </button>
                                <div class="gc-acciones-menu">
                                    <?php if ($consumidor->estado === 'pendiente'): ?>
                                        <a href="#" class="gc-accion-estado" data-estado="activo">
                                            <span class="dashicons dashicons-yes"></span> <?php _e('Aprobar', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($consumidor->estado === 'activo'): ?>
                                        <a href="#" class="gc-accion-estado" data-estado="suspendido">
                                            <span class="dashicons dashicons-warning"></span> <?php _e('Suspender', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($consumidor->estado === 'suspendido'): ?>
                                        <a href="#" class="gc-accion-estado" data-estado="activo">
                                            <span class="dashicons dashicons-yes"></span> <?php _e('Reactivar', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($consumidor->estado !== 'baja'): ?>
                                        <a href="#" class="gc-accion-estado gc-accion-peligro" data-estado="baja">
                                            <span class="dashicons dashicons-dismiss"></span> <?php _e('Dar de Baja', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <hr>
                                    <a href="#" class="gc-ver-detalles">
                                        <span class="dashicons dashicons-visibility"></span> <?php _e('Ver Detalles', 'flavor-chat-ia'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=gc-pedidos&usuario_id=' . $consumidor->usuario_id)); ?>">
                                        <span class="dashicons dashicons-cart"></span> <?php _e('Ver Pedidos', 'flavor-chat-ia'); ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s elemento', '%s elementos', $total_consumidores, 'flavor-chat-ia'), number_format_i18n($total_consumidores)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $paginate_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_paginas,
                        'current' => $pagina_actual,
                    ]);
                    echo $paginate_links;
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php endif; // fin if grupo_id ?>
</div>

<!-- Modal: Nuevo Consumidor -->
<div id="modal-nuevo-consumidor" class="gc-modal" style="display:none;">
    <div class="gc-modal-content">
        <div class="gc-modal-header">
            <h2><?php _e('Añadir Consumidor', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="gc-modal-close">&times;</button>
        </div>
        <div class="gc-modal-body">
            <form id="form-nuevo-consumidor">
                <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo_id); ?>">
                <?php wp_nonce_field('gc_admin_nonce', 'gc_admin_nonce'); ?>

                <div class="gc-form-field">
                    <label for="nuevo-usuario"><?php _e('Usuario', 'flavor-chat-ia'); ?> *</label>
                    <select id="nuevo-usuario" name="usuario_id" required class="gc-select-usuario">
                        <option value=""><?php _e('Buscar usuario...', 'flavor-chat-ia'); ?></option>
                    </select>
                    <p class="description"><?php _e('Busca un usuario existente de WordPress.', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="gc-form-field">
                    <label for="nuevo-rol"><?php _e('Rol', 'flavor-chat-ia'); ?></label>
                    <select id="nuevo-rol" name="rol">
                        <option value="consumidor"><?php _e('Consumidor', 'flavor-chat-ia'); ?></option>
                        <option value="coordinador"><?php _e('Coordinador', 'flavor-chat-ia'); ?></option>
                        <option value="productor"><?php _e('Productor', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="gc-form-field">
                    <label for="nuevo-preferencias"><?php _e('Preferencias Alimentarias', 'flavor-chat-ia'); ?></label>
                    <textarea id="nuevo-preferencias" name="preferencias" rows="3" placeholder="<?php _e('Ej: Vegetariano, sin gluten...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="gc-form-field">
                    <label for="nuevo-alergias"><?php _e('Alergias', 'flavor-chat-ia'); ?></label>
                    <textarea id="nuevo-alergias" name="alergias" rows="2" placeholder="<?php _e('Ej: Frutos secos, mariscos...', 'flavor-chat-ia'); ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="gc-modal-footer">
            <button type="button" class="button gc-modal-cancel"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button button-primary gc-guardar-consumidor"><?php _e('Añadir', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>

<!-- Modal: Detalles Consumidor -->
<div id="modal-detalles-consumidor" class="gc-modal" style="display:none;">
    <div class="gc-modal-content">
        <div class="gc-modal-header">
            <h2><?php _e('Detalles del Consumidor', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="gc-modal-close">&times;</button>
        </div>
        <div class="gc-modal-body">
            <div id="gc-detalles-contenido">
                <!-- Se carga vía AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Dropdown acciones
    $('.gc-acciones-btn').on('click', function(e) {
        e.stopPropagation();
        var menu = $(this).siblings('.gc-acciones-menu');
        $('.gc-acciones-menu').not(menu).removeClass('activo');
        menu.toggleClass('activo');
    });

    $(document).on('click', function() {
        $('.gc-acciones-menu').removeClass('activo');
    });

    // Cambiar estado
    $('.gc-accion-estado').on('click', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        var consumidorId = $row.data('consumidor-id');
        var nuevoEstado = $(this).data('estado');

        if (nuevoEstado === 'baja' && !confirm('<?php _e('¿Dar de baja a este consumidor?', 'flavor-chat-ia'); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'gc_cambiar_estado_consumidor',
            consumidor_id: consumidorId,
            estado: nuevoEstado,
            nonce: '<?php echo wp_create_nonce('gc_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.mensaje || response.data.error);
            }
        });
    });

    // Cambiar rol
    $('.gc-cambiar-rol').on('change', function() {
        var consumidorId = $(this).data('consumidor-id');
        var nuevoRol = $(this).val();

        $.post(ajaxurl, {
            action: 'gc_cambiar_rol_consumidor',
            consumidor_id: consumidorId,
            rol: nuevoRol,
            nonce: '<?php echo wp_create_nonce('gc_admin_nonce'); ?>'
        }, function(response) {
            if (!response.success) {
                alert(response.data.mensaje || response.data.error);
                location.reload();
            }
        });
    });

    // Modal
    $('.gc-modal-trigger').on('click', function(e) {
        e.preventDefault();
        var modalId = $(this).data('modal');
        $('#' + modalId).fadeIn(200);
    });

    $('.gc-modal-close, .gc-modal-cancel').on('click', function() {
        $(this).closest('.gc-modal').fadeOut(200);
    });

    $('.gc-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

    // Guardar nuevo consumidor
    $('.gc-guardar-consumidor').on('click', function() {
        var $form = $('#form-nuevo-consumidor');
        var formData = $form.serialize();

        $.post(ajaxurl, formData + '&action=gc_alta_consumidor', function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.mensaje || response.data.error);
            }
        });
    });
});
</script>

<style>
.gc-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.gc-stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}
.gc-stat-numero {
    display: block;
    font-size: 28px;
    font-weight: 600;
    color: #1d2327;
}
.gc-stat-label {
    color: #646970;
    font-size: 13px;
}
.gc-stat-activo .gc-stat-numero { color: #00a32a; }
.gc-stat-pendiente .gc-stat-numero { color: #dba617; }

.gc-filtros-wrapper {
    background: #fff;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.gc-filtros-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.gc-filtro label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.gc-estado-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.gc-estado-pendiente { background: #fcf0c3; color: #8a6d06; }
.gc-estado-activo { background: #d4edda; color: #155724; }
.gc-estado-suspendido { background: #f8d7da; color: #721c24; }
.gc-estado-baja { background: #e9ecef; color: #6c757d; }

.gc-saldo-pendiente { color: #d63638; font-weight: 600; }

.gc-acciones-dropdown {
    position: relative;
}
.gc-acciones-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    min-width: 180px;
    z-index: 100;
}
.gc-acciones-menu.activo { display: block; }
.gc-acciones-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    text-decoration: none;
    color: #1d2327;
}
.gc-acciones-menu a:hover { background: #f0f0f1; }
.gc-acciones-menu hr {
    margin: 5px 0;
    border: 0;
    border-top: 1px solid #ddd;
}
.gc-accion-peligro { color: #d63638 !important; }

/* Modal */
.gc-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}
.gc-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}
.gc-modal-header h2 { margin: 0; }
.gc-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
.gc-modal-body { padding: 20px; }
.gc-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}
.gc-modal-footer .button { margin-left: 10px; }

.gc-form-field {
    margin-bottom: 15px;
}
.gc-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.gc-form-field input,
.gc-form-field select,
.gc-form-field textarea {
    width: 100%;
}
</style>
