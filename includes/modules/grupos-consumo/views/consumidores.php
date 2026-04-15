<?php
/**
 * Vista Admin: Gestión de Consumidores
 *
 * @package FlavorPlatform
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
        <?php _e('Gestión de Consumidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <button type="button" class="page-title-action gc-modal-trigger" data-modal="modal-nuevo-consumidor">
        <?php _e('Añadir Consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Selector de Grupo -->
    <?php if (count($todos_los_grupos) > 1): ?>
        <div class="gc-grupo-selector">
            <label for="gc-grupo-select"><?php _e('Grupo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
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
        <div class="gc-empty-state" style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; margin-top: 20px;">
            <span class="dashicons dashicons-store" style="font-size: 64px; color: #c3c4c7; display: block; margin-bottom: 20px;"></span>
            <h2 style="margin: 0 0 10px; color: #1d2327;"><?php _e('No hay grupos de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p style="color: #646970; margin-bottom: 20px; max-width: 400px; margin-left: auto; margin-right: auto;">
                <?php _e('Para gestionar consumidores, primero necesitas crear un grupo de consumo donde añadirlos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <a href="<?php echo admin_url('post-new.php?post_type=gc_grupo'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt2" style="margin-top: 5px;"></span>
                <?php _e('Crear primer grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    <?php else: ?>

    <!-- Estadísticas -->
    <div class="gc-stats-grid">
        <div class="gc-stat-card">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['total'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Total Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-activo">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['por_estado']['activo'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-pendiente">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['por_estado']['pendiente'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="gc-stat-card">
            <span class="gc-stat-numero">+<?php echo esc_html($estadisticas['altas_mes'] ?? 0); ?></span>
            <span class="gc-stat-label"><?php _e('Altas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Filtros -->
    <div class="gc-filtros-wrapper">
        <form method="get" class="gc-filtros-form">
            <input type="hidden" name="page" value="<?php echo esc_attr__('gc-consumidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo_id); ?>">

            <div class="gc-filtro">
                <label for="filtro-estado"><?php _e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select id="filtro-estado" name="estado">
                    <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['estado'], 'pendiente'); ?>><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['estado'], 'activo'); ?>><?php _e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['estado'], 'suspendido'); ?>><?php _e('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['estado'], 'baja'); ?>><?php _e('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="gc-filtro">
                <label for="filtro-rol"><?php _e('Rol:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select id="filtro-rol" name="rol">
                    <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['rol'], 'consumidor'); ?>><?php _e('Consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('coordinador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['rol'], 'coordinador'); ?>><?php _e('Coordinador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo esc_attr__('productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtros['rol'], 'productor'); ?>><?php _e('Productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="gc-filtro gc-filtro-busqueda">
                <label for="filtro-busqueda" class="screen-reader-text"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="search" id="filtro-busqueda" name="s" value="<?php echo esc_attr($filtros['busqueda']); ?>" placeholder="<?php _e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <a href="<?php echo admin_url('admin.php?page=gc-consumidores&grupo_id=' . $grupo_id); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
        </form>
    </div>

    <!-- Tabla de Consumidores -->
    <table class="wp-list-table widefat fixed striped gc-tabla-consumidores">
        <thead>
            <tr>
                <th scope="col" class="column-nombre"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" class="column-email"><?php _e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" class="column-rol"><?php _e('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" class="column-estado"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" class="column-saldo"><?php _e('Saldo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" class="column-fecha"><?php _e('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" class="column-acciones"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($consumidores)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <span class="dashicons dashicons-groups" style="font-size: 48px; color: #c3c4c7; display: block; margin-bottom: 15px;"></span>
                        <p style="color: #646970; margin-bottom: 15px;"><?php _e('No hay consumidores registrados en este grupo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <button type="button" class="button button-primary gc-modal-trigger" data-modal="modal-nuevo-consumidor">
                            <span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span> <?php _e('Añadir primer consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="button gc-importar-usuarios-wp" style="margin-left: 10px;">
                            <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> <?php _e('Importar usuarios de WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </td>
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
                                <span class="gc-tiene-notas dashicons dashicons-info" title="<?php _e('Tiene preferencias/alergias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-email">
                            <a href="mailto:<?php echo esc_attr($consumidor->user_email); ?>">
                                <?php echo esc_html($consumidor->user_email); ?>
                            </a>
                        </td>
                        <td class="column-rol">
                            <?php
                            // Parsear roles (pueden ser múltiples separados por coma)
                            $roles_actuales = array_map('trim', explode(',', $consumidor->rol));
                            ?>
                            <div class="gc-roles-badges" data-consumidor-id="<?php echo esc_attr($consumidor->id); ?>">
                                <label class="gc-rol-badge <?php echo in_array('consumidor', $roles_actuales) ? 'activo' : ''; ?>">
                                    <input type="checkbox" value="consumidor" <?php checked(in_array('consumidor', $roles_actuales)); ?>>
                                    <span><?php _e('Consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="gc-rol-badge gc-rol-coordinador <?php echo in_array('coordinador', $roles_actuales) ? 'activo' : ''; ?>">
                                    <input type="checkbox" value="coordinador" <?php checked(in_array('coordinador', $roles_actuales)); ?>>
                                    <span><?php _e('Coordinador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="gc-rol-badge gc-rol-productor <?php echo in_array('productor', $roles_actuales) ? 'activo' : ''; ?>">
                                    <input type="checkbox" value="productor" <?php checked(in_array('productor', $roles_actuales)); ?>>
                                    <span><?php _e('Productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                            </div>
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
                            <?php
                            // Obtener teléfono del usuario
                            $telefono_usuario = get_user_meta($consumidor->usuario_id, 'billing_phone', true);
                            if (!$telefono_usuario) {
                                $telefono_usuario = get_user_meta($consumidor->usuario_id, 'phone', true);
                            }
                            $telefono_limpio = preg_replace('/[^0-9+]/', '', $telefono_usuario);
                            ?>
                            <div class="gc-acciones-dropdown">
                                <button type="button" class="button gc-acciones-btn">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </button>
                                <div class="gc-acciones-menu">
                                    <!-- Acciones de comunicación -->
                                    <a href="mailto:<?php echo esc_attr($consumidor->user_email); ?>" class="gc-accion-comunicacion">
                                        <span class="dashicons dashicons-email"></span> <?php _e('Enviar Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                    <?php if ($telefono_limpio): ?>
                                        <a href="https://wa.me/<?php echo esc_attr(ltrim($telefono_limpio, '+')); ?>" target="_blank" class="gc-accion-comunicacion gc-accion-whatsapp">
                                            <span class="dashicons dashicons-whatsapp"></span> <?php _e('WhatsApp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                        <a href="tel:<?php echo esc_attr($telefono_limpio); ?>" class="gc-accion-comunicacion">
                                            <span class="dashicons dashicons-phone"></span> <?php _e('Llamar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="gc-accion-disabled" title="<?php _e('Sin teléfono registrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-whatsapp"></span> <?php _e('WhatsApp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                        <span class="gc-accion-disabled" title="<?php _e('Sin teléfono registrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-phone"></span> <?php _e('Llamar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    <?php endif; ?>
                                    <hr>
                                    <!-- Acciones de estado -->
                                    <?php if ($consumidor->estado === 'pendiente'): ?>
                                        <button type="button" class="gc-accion-estado" data-estado="activo">
                                            <span class="dashicons dashicons-yes"></span> <?php _e('Aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($consumidor->estado === 'activo'): ?>
                                        <button type="button" class="gc-accion-estado" data-estado="suspendido">
                                            <span class="dashicons dashicons-warning"></span> <?php _e('Suspender', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($consumidor->estado === 'suspendido'): ?>
                                        <button type="button" class="gc-accion-estado" data-estado="activo">
                                            <span class="dashicons dashicons-yes"></span> <?php _e('Reactivar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($consumidor->estado !== 'baja'): ?>
                                        <button type="button" class="gc-accion-estado gc-accion-peligro" data-estado="baja">
                                            <span class="dashicons dashicons-dismiss"></span> <?php _e('Dar de Baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                    <hr>
                                    <!-- Ver detalles y pedidos -->
                                    <button type="button" class="gc-ver-detalles" data-consumidor-id="<?php echo esc_attr($consumidor->id); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php _e('Ver Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=gc-pedidos&usuario_id=' . $consumidor->usuario_id)); ?>">
                                        <span class="dashicons dashicons-cart"></span> <?php _e('Ver Pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                    <?php
                                    // Boton de crear factura si el modulo de facturas esta activo
                                    $facturas_module_class = function_exists('flavor_get_runtime_class_name')
                                        ? flavor_get_runtime_class_name('Flavor_Chat_Facturas_Module')
                                        : 'Flavor_Chat_Facturas_Module';
                                    $modulo_facturas_activo = class_exists($facturas_module_class);
                                    if ($modulo_facturas_activo):
                                        $url_factura = admin_url('admin.php?page=facturas-nueva&cliente_id=' . $consumidor->usuario_id . '&cliente_tipo=consumidor');
                                    ?>
                                    <hr>
                                    <a href="<?php echo esc_url($url_factura); ?>" class="gc-accion-factura">
                                        <span class="dashicons dashicons-media-text"></span> <?php _e('Crear Factura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                    <?php endif; ?>
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
                    <?php printf(_n('%s elemento', '%s elementos', $total_consumidores, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total_consumidores)); ?>
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
            <h2><?php _e('Añadir Consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
        <div class="gc-modal-body">
            <form id="form-nuevo-consumidor">
                <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo_id); ?>">
                <?php wp_nonce_field('flavor_grupos_consumo_nonce', 'flavor_grupos_consumo_nonce'); ?>

                <div class="gc-form-field">
                    <label for="nuevo-usuario"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <select id="nuevo-usuario" name="usuario_id" required class="gc-select-usuario">
                        <option value=""><?php _e('Buscar usuario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                    <p class="description"><?php _e('Busca un usuario existente de WordPress.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="gc-form-field">
                    <label for="nuevo-rol"><?php _e('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="nuevo-rol" name="rol">
                        <option value="<?php echo esc_attr__('consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('coordinador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Coordinador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="gc-form-field">
                    <label for="nuevo-preferencias"><?php _e('Preferencias Alimentarias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="nuevo-preferencias" name="preferencias" rows="3" placeholder="<?php _e('Ej: Vegetariano, sin gluten...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="gc-form-field">
                    <label for="nuevo-alergias"><?php _e('Alergias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="nuevo-alergias" name="alergias" rows="2" placeholder="<?php _e('Ej: Frutos secos, mariscos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="gc-modal-footer">
            <button type="button" class="button gc-modal-cancel"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="button button-primary gc-guardar-consumidor"><?php _e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
    </div>
</div>

<!-- Modal: Detalles Consumidor -->
<div id="modal-detalles-consumidor" class="gc-modal" style="display:none;">
    <div class="gc-modal-content">
        <div class="gc-modal-header">
            <h2><?php _e('Detalles del Consumidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
        <div class="gc-modal-body">
            <div id="gc-detalles-contenido">
                <!-- Se carga vía AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Importar Usuarios de WordPress -->
<div id="modal-importar-usuarios" class="gc-modal" style="display:none;">
    <div class="gc-modal-content" style="max-width: 600px;">
        <div class="gc-modal-header">
            <h2><?php _e('Importar Usuarios de WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
        <div class="gc-modal-body">
            <p style="margin-bottom: 15px; color: #646970;">
                <?php _e('Selecciona los usuarios de WordPress que quieres añadir como consumidores del grupo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <input type="checkbox" id="gc-seleccionar-todos">
                <strong><?php _e('Seleccionar todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            </label>
            <div id="gc-lista-usuarios-wp" style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                <!-- Se carga vía AJAX -->
            </div>
        </div>
        <div class="gc-modal-footer">
            <button type="button" class="button gc-modal-cancel"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="button button-primary gc-confirmar-importacion"><?php _e('Importar seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function gcAviso(mensaje, tipo) {
        tipo = tipo || 'error';
        $('.gc-inline-notice').remove();
        $('<div class="gc-inline-notice gc-inline-notice-' + tipo + '"><p>' + mensaje + '</p></div>').insertAfter('.wrap h1.wp-heading-inline').hide().fadeIn(150);
    }

    function gcConfirmar(mensaje, onConfirm) {
        $('.gc-inline-notice').remove();
        var $confirm = $('<div class="gc-inline-notice gc-inline-notice-error"><p>' + mensaje + '</p><div class="gc-inline-confirm-actions"><button type="button" class="button button-primary gc-confirmar"><?php echo esc_js(__('Confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></button><button type="button" class="button gc-cancelar"><?php echo esc_js(__('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></button></div></div>').insertAfter('.wrap h1.wp-heading-inline').hide().fadeIn(150);
        $confirm.on('click', '.gc-confirmar', function() {
            $confirm.remove();
            onConfirm();
        });
        $confirm.on('click', '.gc-cancelar', function() {
            $confirm.remove();
        });
    }

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

        var ejecutarCambioEstado = function() {
            $.post(ajaxurl, {
                action: 'gc_cambiar_estado_consumidor',
                consumidor_id: consumidorId,
                estado: nuevoEstado,
                nonce: '<?php echo wp_create_nonce('flavor_grupos_consumo_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    gcAviso(response.data.mensaje || response.data.error, 'error');
                }
            });
        };

        if (nuevoEstado === 'baja') {
            gcConfirmar('<?php echo esc_js(__('¿Dar de baja a este consumidor?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', ejecutarCambioEstado);
            return;
        }

        ejecutarCambioEstado();
    });

    // Cambiar roles (múltiples)
    $('.gc-roles-badges input[type="checkbox"]').on('change', function() {
        var $container = $(this).closest('.gc-roles-badges');
        var consumidorId = $container.data('consumidor-id');
        var $label = $(this).closest('.gc-rol-badge');

        // Toggle clase activo
        if ($(this).is(':checked')) {
            $label.addClass('activo');
        } else {
            $label.removeClass('activo');
        }

        // Recopilar todos los roles seleccionados
        var rolesSeleccionados = [];
        $container.find('input[type="checkbox"]:checked').each(function() {
            rolesSeleccionados.push($(this).val());
        });

        // Si no hay ningún rol seleccionado, mantener al menos "consumidor"
        if (rolesSeleccionados.length === 0) {
            rolesSeleccionados = ['consumidor'];
            $container.find('input[value="consumidor"]').prop('checked', true).closest('.gc-rol-badge').addClass('activo');
        }

        var rolesString = rolesSeleccionados.join(',');

        $.post(ajaxurl, {
            action: 'gc_cambiar_rol_consumidor',
            consumidor_id: consumidorId,
            rol: rolesString,
            nonce: '<?php echo wp_create_nonce('flavor_grupos_consumo_nonce'); ?>'
        }, function(response) {
            if (!response.success) {
                gcAviso(response.data.mensaje || response.data.error, 'error');
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
                gcAviso(response.data.mensaje || response.data.error, 'error');
            }
        });
    });

    // Ver detalles de consumidor
    $('.gc-ver-detalles').on('click', function(e) {
        e.preventDefault();
        var consumidorId = $(this).data('consumidor-id');
        var $modal = $('#modal-detalles-consumidor');
        var $contenido = $('#gc-detalles-contenido');

        $contenido.html('<p style="text-align:center;padding:30px;"><span class="spinner is-active" style="float:none;"></span> <?php _e('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>');
        $modal.fadeIn(200);

        $.post(ajaxurl, {
            action: 'gc_obtener_detalles_consumidor',
            consumidor_id: consumidorId,
            nonce: '<?php echo wp_create_nonce('flavor_grupos_consumo_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var c = response.data.consumidor;
                var html = '<div class="gc-detalles-grid">';
                html += '<div class="gc-detalle-avatar">';
                html += '<img src="' + c.avatar + '" alt="" style="width:80px;height:80px;border-radius:50%;">';
                html += '</div>';
                html += '<div class="gc-detalle-info">';
                html += '<h3 style="margin:0 0 5px;">' + c.nombre + '</h3>';
                html += '<p style="margin:0;color:#646970;"><a href="mailto:' + c.email + '">' + c.email + '</a></p>';
                if (c.telefono) {
                    html += '<p style="margin:5px 0 0;color:#646970;">' + c.telefono + '</p>';
                }
                html += '</div>';
                html += '</div>';

                html += '<table class="gc-detalles-tabla" style="width:100%;margin-top:20px;">';
                html += '<tr><th style="text-align:left;padding:8px 0;border-bottom:1px solid #eee;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                html += '<td style="padding:8px 0;border-bottom:1px solid #eee;"><span class="gc-estado-badge gc-estado-' + c.estado + '">' + c.estado_label + '</span></td></tr>';
                html += '<tr><th style="text-align:left;padding:8px 0;border-bottom:1px solid #eee;"><?php _e('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                html += '<td style="padding:8px 0;border-bottom:1px solid #eee;">' + c.rol_label + '</td></tr>';
                html += '<tr><th style="text-align:left;padding:8px 0;border-bottom:1px solid #eee;"><?php _e('Fecha de alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                html += '<td style="padding:8px 0;border-bottom:1px solid #eee;">' + c.fecha_alta + '</td></tr>';
                html += '<tr><th style="text-align:left;padding:8px 0;border-bottom:1px solid #eee;"><?php _e('Saldo pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                html += '<td style="padding:8px 0;border-bottom:1px solid #eee;' + (c.saldo > 0 ? 'color:#d63638;font-weight:600;' : '') + '">' + c.saldo + ' €</td></tr>';
                if (c.preferencias) {
                    html += '<tr><th style="text-align:left;padding:8px 0;border-bottom:1px solid #eee;"><?php _e('Preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                    html += '<td style="padding:8px 0;border-bottom:1px solid #eee;">' + c.preferencias + '</td></tr>';
                }
                if (c.alergias) {
                    html += '<tr><th style="text-align:left;padding:8px 0;border-bottom:1px solid #eee;"><?php _e('Alergias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                    html += '<td style="padding:8px 0;border-bottom:1px solid #eee;color:#d63638;">' + c.alergias + '</td></tr>';
                }
                html += '<tr><th style="text-align:left;padding:8px 0;"><?php _e('Total pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>';
                html += '<td style="padding:8px 0;">' + c.total_pedidos + '</td></tr>';
                html += '</table>';

                $contenido.html(html);
            } else {
                $contenido.html('<p style="text-align:center;padding:30px;color:#d63638;">' + (response.data.error || 'Error') + '</p>');
            }
        });
    });

    // Importar usuarios de WordPress
    $('.gc-importar-usuarios-wp').on('click', function() {
        var $modal = $('#modal-importar-usuarios');
        var $lista = $('#gc-lista-usuarios-wp');

        $lista.html('<p style="text-align:center;padding:20px;"><?php _e('Cargando usuarios...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>');
        $modal.fadeIn(200);

        // Cargar usuarios de WordPress
        $.post(ajaxurl, {
            action: 'gc_listar_usuarios_wp',
            grupo_id: <?php echo $grupo_id ?: 0; ?>,
            nonce: '<?php echo wp_create_nonce('flavor_grupos_consumo_nonce'); ?>'
        }, function(response) {
            if (response.success && response.data.usuarios.length > 0) {
                var html = '<div class="gc-usuarios-checkboxes">';
                $.each(response.data.usuarios, function(i, usuario) {
                    html += '<label class="gc-usuario-checkbox">';
                    html += '<input type="checkbox" name="usuarios[]" value="' + usuario.id + '">';
                    html += '<span class="gc-usuario-info">';
                    html += '<strong>' + usuario.nombre + '</strong>';
                    html += '<small>' + usuario.email + '</small>';
                    html += '</span></label>';
                });
                html += '</div>';
                $lista.html(html);
            } else if (response.success && response.data.usuarios.length === 0) {
                $lista.html('<p style="text-align:center;padding:20px;color:#646970;"><?php _e('Todos los usuarios ya son miembros del grupo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>');
            } else {
                $lista.html('<p style="text-align:center;padding:20px;color:#d63638;">' + (response.data.error || 'Error') + '</p>');
            }
        });
    });

    // Seleccionar todos los usuarios
    $('#gc-seleccionar-todos').on('change', function() {
        var checked = $(this).prop('checked');
        $('#gc-lista-usuarios-wp input[type="checkbox"]').prop('checked', checked);
    });

    // Confirmar importación
    $('.gc-confirmar-importacion').on('click', function() {
        var usuarios = [];
        $('#gc-lista-usuarios-wp input[type="checkbox"]:checked').each(function() {
            usuarios.push($(this).val());
        });

        if (usuarios.length === 0) {
            gcAviso('<?php echo esc_js(__('Selecciona al menos un usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'error');
            return;
        }

        $(this).prop('disabled', true).text('<?php _e('Importando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');

        $.post(ajaxurl, {
            action: 'gc_importar_usuarios_wp',
            grupo_id: <?php echo $grupo_id ?: 0; ?>,
            usuarios: usuarios,
            nonce: '<?php echo wp_create_nonce('flavor_grupos_consumo_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                gcAviso(response.data.mensaje, 'success');
                location.reload();
            } else {
                gcAviso(response.data.error || 'Error', 'error');
                $('.gc-confirmar-importacion').prop('disabled', false).text('<?php _e('Importar seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
            }
        });
    });
});
</script>

<style>
.gc-inline-notice{margin:16px 0;padding:12px 14px;border-left:4px solid #d63638;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.gc-inline-notice-success{border-left-color:#00a32a}
.gc-inline-notice-error{border-left-color:#d63638}
.gc-inline-confirm-actions{display:flex;gap:8px;margin-top:10px}
</style>

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

/* Acciones de comunicación */
.gc-accion-whatsapp { color: #25D366 !important; }
.gc-accion-whatsapp:hover { background: #dcf8e6 !important; }
.gc-accion-disabled {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    color: #a0a5aa;
    cursor: not-allowed;
}
.dashicons-whatsapp:before { content: "\f110"; } /* Usando icono de smartphone como alternativa */

/* Badges de roles múltiples */
.gc-roles-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.gc-rol-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    cursor: pointer;
    border: 1px solid #ddd;
    background: #f6f7f7;
    color: #646970;
    transition: all 0.2s ease;
}
.gc-rol-badge input[type="checkbox"] {
    display: none;
}
.gc-rol-badge:hover {
    border-color: #2271b1;
}
.gc-rol-badge.activo {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
}
.gc-rol-badge.gc-rol-coordinador.activo {
    background: #9b59b6;
    border-color: #9b59b6;
}
.gc-rol-badge.gc-rol-productor.activo {
    background: #27ae60;
    border-color: #27ae60;
}

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

/* Importar usuarios checkboxes */
.gc-usuarios-checkboxes {
    padding: 5px;
}
.gc-usuario-checkbox {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s ease;
}
.gc-usuario-checkbox:last-child {
    border-bottom: none;
}
.gc-usuario-checkbox:hover {
    background: #f8f9fa;
}
.gc-usuario-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.gc-usuario-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.gc-usuario-info strong {
    font-weight: 500;
    color: #1d2327;
}
.gc-usuario-info small {
    color: #646970;
    font-size: 12px;
}
</style>
