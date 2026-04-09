<?php
/**
 * Vista de lista de bugs para el panel de administración
 *
 * @package Flavor_Chat_IA
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener filtros de la URL
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field(wp_unslash($_GET['estado'])) : '';
$filtro_severidad = isset($_GET['severidad']) ? sanitize_text_field(wp_unslash($_GET['severidad'])) : '';
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field(wp_unslash($_GET['tipo'])) : '';
$filtro_busqueda = isset($_GET['busqueda']) ? sanitize_text_field(wp_unslash($_GET['busqueda'])) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$bug_id_detalle = isset($_GET['bug_id']) ? intval($_GET['bug_id']) : 0;

// Obtener bugs
$args = [
    'estado' => $filtro_estado ?: null,
    'severidad' => $filtro_severidad ?: null,
    'tipo' => $filtro_tipo ?: null,
    'busqueda' => $filtro_busqueda ?: null,
    'limit' => 20,
    'offset' => ($pagina_actual - 1) * 20,
];

$resultado = $this->listar_bugs($args);
$bugs = $resultado['bugs'];
$total = $resultado['total'];
$paginas = $resultado['paginas'];

// Obtener estadísticas
$estadisticas = $this->obtener_estadisticas();

// Colores y emojis
$colores_severidad = [
    'critical' => '#dc2626',
    'high' => '#ea580c',
    'medium' => '#ca8a04',
    'low' => '#2563eb',
    'info' => '#6b7280',
];

$colores_estado = [
    'nuevo' => '#dc2626',
    'abierto' => '#ea580c',
    'resuelto' => '#16a34a',
    'ignorado' => '#6b7280',
];

$emojis_tipo = [
    'error_php' => '💥',
    'exception' => '⚠️',
    'warning' => '⚡',
    'notice' => '📝',
    'manual' => '📋',
    'crash' => '💀',
    'deprecation' => '🕰️',
];

// Mostrar detalle de bug si se solicitó
if ($bug_id_detalle) {
    $bug_detalle = $this->obtener_bug($bug_id_detalle);
    if ($bug_detalle) {
        include dirname(__FILE__) . '/admin-bug-detail.php';
        return;
    }
}
?>

<style>
.flavor-bug-tracker-admin .stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.flavor-bug-tracker-admin .stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}
.flavor-bug-tracker-admin .stat-card .stat-number {
    font-size: 32px;
    font-weight: bold;
    line-height: 1;
}
.flavor-bug-tracker-admin .stat-card .stat-label {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}
.flavor-bug-tracker-admin .filters-bar {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.flavor-bug-tracker-admin .bugs-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.flavor-bug-tracker-admin .bugs-table th,
.flavor-bug-tracker-admin .bugs-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.flavor-bug-tracker-admin .bugs-table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
}
.flavor-bug-tracker-admin .bugs-table tr:hover {
    background: #f8f9fa;
}
.flavor-bug-tracker-admin .badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}
.flavor-bug-tracker-admin .badge-severidad {
    color: white;
}
.flavor-bug-tracker-admin .badge-estado {
    color: white;
}
.flavor-bug-tracker-admin .bug-titulo {
    font-weight: 500;
    color: #333;
    text-decoration: none;
}
.flavor-bug-tracker-admin .bug-titulo:hover {
    color: #2563eb;
}
.flavor-bug-tracker-admin .bug-meta {
    font-size: 12px;
    color: #888;
    margin-top: 3px;
}
.flavor-bug-tracker-admin .bug-actions {
    display: flex;
    gap: 5px;
}
.flavor-bug-tracker-admin .bug-actions button {
    padding: 4px 8px;
    font-size: 11px;
    cursor: pointer;
}
.flavor-bug-tracker-admin .ocurrencias-badge {
    background: #e5e7eb;
    color: #374151;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}
.flavor-bug-tracker-admin .pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}
.flavor-bug-tracker-admin .pagination a,
.flavor-bug-tracker-admin .pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}
.flavor-bug-tracker-admin .pagination .current {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}
</style>

<div class="flavor-bug-tracker-list">
    <!-- Estadísticas -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-number" style="color: #dc2626;">
                <?php echo esc_html($estadisticas['por_estado']['nuevo'] ?? 0); ?>
            </div>
            <div class="stat-label"><?php esc_html_e('Nuevos', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #ea580c;">
                <?php echo esc_html($estadisticas['por_estado']['abierto'] ?? 0); ?>
            </div>
            <div class="stat-label"><?php esc_html_e('Abiertos', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #16a34a;">
                <?php echo esc_html($estadisticas['por_estado']['resuelto'] ?? 0); ?>
            </div>
            <div class="stat-label"><?php esc_html_e('Resueltos', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #dc2626;">
                <?php echo esc_html($estadisticas['por_severidad']['critical'] ?? 0); ?>
            </div>
            <div class="stat-label"><?php esc_html_e('Críticos', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #2563eb;">
                <?php echo esc_html($estadisticas['ultimas_24h']); ?>
            </div>
            <div class="stat-label"><?php esc_html_e('Últimas 24h', 'flavor-platform'); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="filters-bar">
        <input type="hidden" name="page" value="flavor-bug-tracker">
        <input type="hidden" name="tab" value="lista">

        <select name="estado">
            <option value=""><?php esc_html_e('Todos los estados', 'flavor-platform'); ?></option>
            <option value="nuevo" <?php selected($filtro_estado, 'nuevo'); ?>><?php esc_html_e('Nuevo', 'flavor-platform'); ?></option>
            <option value="abierto" <?php selected($filtro_estado, 'abierto'); ?>><?php esc_html_e('Abierto', 'flavor-platform'); ?></option>
            <option value="resuelto" <?php selected($filtro_estado, 'resuelto'); ?>><?php esc_html_e('Resuelto', 'flavor-platform'); ?></option>
            <option value="ignorado" <?php selected($filtro_estado, 'ignorado'); ?>><?php esc_html_e('Ignorado', 'flavor-platform'); ?></option>
        </select>

        <select name="severidad">
            <option value=""><?php esc_html_e('Todas las severidades', 'flavor-platform'); ?></option>
            <option value="critical" <?php selected($filtro_severidad, 'critical'); ?>><?php esc_html_e('Crítica', 'flavor-platform'); ?></option>
            <option value="high" <?php selected($filtro_severidad, 'high'); ?>><?php esc_html_e('Alta', 'flavor-platform'); ?></option>
            <option value="medium" <?php selected($filtro_severidad, 'medium'); ?>><?php esc_html_e('Media', 'flavor-platform'); ?></option>
            <option value="low" <?php selected($filtro_severidad, 'low'); ?>><?php esc_html_e('Baja', 'flavor-platform'); ?></option>
            <option value="info" <?php selected($filtro_severidad, 'info'); ?>><?php esc_html_e('Info', 'flavor-platform'); ?></option>
        </select>

        <select name="tipo">
            <option value=""><?php esc_html_e('Todos los tipos', 'flavor-platform'); ?></option>
            <option value="error_php" <?php selected($filtro_tipo, 'error_php'); ?>><?php esc_html_e('Error PHP', 'flavor-platform'); ?></option>
            <option value="exception" <?php selected($filtro_tipo, 'exception'); ?>><?php esc_html_e('Excepción', 'flavor-platform'); ?></option>
            <option value="warning" <?php selected($filtro_tipo, 'warning'); ?>><?php esc_html_e('Warning', 'flavor-platform'); ?></option>
            <option value="notice" <?php selected($filtro_tipo, 'notice'); ?>><?php esc_html_e('Notice', 'flavor-platform'); ?></option>
            <option value="manual" <?php selected($filtro_tipo, 'manual'); ?>><?php esc_html_e('Manual', 'flavor-platform'); ?></option>
            <option value="crash" <?php selected($filtro_tipo, 'crash'); ?>><?php esc_html_e('Crash', 'flavor-platform'); ?></option>
            <option value="deprecation" <?php selected($filtro_tipo, 'deprecation'); ?>><?php esc_html_e('Deprecation', 'flavor-platform'); ?></option>
        </select>

        <input type="text" name="busqueda" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php esc_attr_e('Buscar...', 'flavor-platform'); ?>" style="min-width: 200px;">

        <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-platform'); ?></button>

        <?php if ($filtro_estado || $filtro_severidad || $filtro_tipo || $filtro_busqueda) : ?>
            <a href="?page=flavor-bug-tracker" class="button"><?php esc_html_e('Limpiar', 'flavor-platform'); ?></a>
        <?php endif; ?>
    </form>

    <!-- Tabla de bugs -->
    <?php if (empty($bugs)) : ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 8px; border: 1px solid #ddd;">
            <span style="font-size: 48px;">✓</span>
            <p style="color: #16a34a; font-size: 18px; margin-top: 10px;">
                <?php esc_html_e('No se encontraron bugs', 'flavor-platform'); ?>
            </p>
        </div>
    <?php else : ?>
        <table class="bugs-table">
            <thead>
                <tr>
                    <th style="width: 120px;"><?php esc_html_e('Código', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Bug', 'flavor-platform'); ?></th>
                    <th style="width: 90px;"><?php esc_html_e('Severidad', 'flavor-platform'); ?></th>
                    <th style="width: 80px;"><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                    <th style="width: 80px;"><?php esc_html_e('Ocurrencias', 'flavor-platform'); ?></th>
                    <th style="width: 140px;"><?php esc_html_e('Última vez', 'flavor-platform'); ?></th>
                    <th style="width: 150px;"><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bugs as $bug) : ?>
                    <tr>
                        <td>
                            <code style="font-size: 11px;"><?php echo esc_html($bug->codigo); ?></code>
                        </td>
                        <td>
                            <a href="?page=flavor-bug-tracker&bug_id=<?php echo esc_attr($bug->id); ?>" class="bug-titulo">
                                <?php echo esc_html($emojis_tipo[$bug->tipo] ?? '🐛'); ?>
                                <?php echo esc_html(mb_substr($bug->titulo, 0, 80)); ?>
                                <?php if (strlen($bug->titulo) > 80) : ?>...<?php endif; ?>
                            </a>
                            <div class="bug-meta">
                                <?php if ($bug->modulo_id) : ?>
                                    <span title="Módulo"><?php echo esc_html($bug->modulo_id); ?></span> •
                                <?php endif; ?>
                                <?php if ($bug->archivo) : ?>
                                    <span title="Archivo"><?php echo esc_html(basename($bug->archivo)); ?>:<?php echo esc_html($bug->linea); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-severidad" style="background: <?php echo esc_attr($colores_severidad[$bug->severidad] ?? '#6b7280'); ?>;">
                                <?php echo esc_html(ucfirst($bug->severidad)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-estado" style="background: <?php echo esc_attr($colores_estado[$bug->estado] ?? '#6b7280'); ?>;">
                                <?php echo esc_html(ucfirst($bug->estado)); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($bug->ocurrencias > 1) : ?>
                                <span class="ocurrencias-badge">×<?php echo esc_html($bug->ocurrencias); ?></span>
                            <?php else : ?>
                                1
                            <?php endif; ?>
                        </td>
                        <td>
                            <span title="<?php echo esc_attr($bug->ultima_ocurrencia); ?>">
                                <?php echo esc_html(human_time_diff(strtotime($bug->ultima_ocurrencia), current_time('timestamp'))); ?> ago
                            </span>
                        </td>
                        <td>
                            <div class="bug-actions">
                                <?php if ($bug->estado !== 'resuelto') : ?>
                                    <button type="button" class="button button-small btn-resolver" data-id="<?php echo esc_attr($bug->id); ?>" title="<?php esc_attr_e('Marcar como resuelto', 'flavor-platform'); ?>">
                                        ✓
                                    </button>
                                <?php endif; ?>
                                <?php if ($bug->estado !== 'ignorado') : ?>
                                    <button type="button" class="button button-small btn-ignorar" data-id="<?php echo esc_attr($bug->id); ?>" title="<?php esc_attr_e('Ignorar', 'flavor-platform'); ?>">
                                        ✗
                                    </button>
                                <?php endif; ?>
                                <?php if ($bug->estado === 'resuelto' || $bug->estado === 'ignorado') : ?>
                                    <button type="button" class="button button-small btn-reabrir" data-id="<?php echo esc_attr($bug->id); ?>" title="<?php esc_attr_e('Reabrir', 'flavor-platform'); ?>">
                                        ↺
                                    </button>
                                <?php endif; ?>
                                <a href="?page=flavor-bug-tracker&bug_id=<?php echo esc_attr($bug->id); ?>" class="button button-small" title="<?php esc_attr_e('Ver detalles', 'flavor-platform'); ?>">
                                    👁
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($paginas > 1) : ?>
            <div class="pagination">
                <?php
                $url_base = add_query_arg([
                    'page' => 'flavor-bug-tracker',
                    'tab' => 'lista',
                    'estado' => $filtro_estado,
                    'severidad' => $filtro_severidad,
                    'tipo' => $filtro_tipo,
                    'busqueda' => $filtro_busqueda,
                ], admin_url('admin.php'));

                if ($pagina_actual > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $pagina_actual - 1, $url_base)); ?>">« <?php esc_html_e('Anterior', 'flavor-platform'); ?></a>
                <?php endif;

                for ($numero_pagina = 1; $numero_pagina <= $paginas; $numero_pagina++) :
                    if ($numero_pagina === $pagina_actual) : ?>
                        <span class="current"><?php echo esc_html($numero_pagina); ?></span>
                    <?php else : ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $numero_pagina, $url_base)); ?>"><?php echo esc_html($numero_pagina); ?></a>
                    <?php endif;
                endfor;

                if ($pagina_actual < $paginas) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $pagina_actual + 1, $url_base)); ?>"><?php esc_html_e('Siguiente', 'flavor-platform'); ?> »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Resolver bug
    $('.btn-resolver').on('click', function() {
        var bugId = $(this).data('id');
        if (confirm('<?php echo esc_js(__('¿Marcar este bug como resuelto?', 'flavor-platform')); ?>')) {
            $.post(ajaxurl, {
                action: 'flavor_bug_tracker_action',
                bug_id: bugId,
                bug_action: 'resolve',
                _wpnonce: '<?php echo esc_js(wp_create_nonce('flavor_bug_tracker')); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error');
                }
            });
        }
    });

    // Ignorar bug
    $('.btn-ignorar').on('click', function() {
        var bugId = $(this).data('id');
        if (confirm('<?php echo esc_js(__('¿Ignorar este bug?', 'flavor-platform')); ?>')) {
            $.post(ajaxurl, {
                action: 'flavor_bug_tracker_action',
                bug_id: bugId,
                bug_action: 'ignore',
                _wpnonce: '<?php echo esc_js(wp_create_nonce('flavor_bug_tracker')); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error');
                }
            });
        }
    });

    // Reabrir bug
    $('.btn-reabrir').on('click', function() {
        var bugId = $(this).data('id');
        if (confirm('<?php echo esc_js(__('¿Reabrir este bug?', 'flavor-platform')); ?>')) {
            $.post(ajaxurl, {
                action: 'flavor_bug_tracker_action',
                bug_id: bugId,
                bug_action: 'reopen',
                _wpnonce: '<?php echo esc_js(wp_create_nonce('flavor_bug_tracker')); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error');
                }
            });
        }
    });
});
</script>

<?php
// Registrar handler AJAX
add_action('wp_ajax_flavor_bug_tracker_action', function() {
    check_ajax_referer('flavor_bug_tracker', '_wpnonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permisos insuficientes');
    }

    $bug_id = intval($_POST['bug_id'] ?? 0);
    $accion = sanitize_text_field($_POST['bug_action'] ?? '');

    $modulo = Flavor_Chat_Module_Loader::get_instance()->get_module('bug-tracker');
    if (!$modulo) {
        wp_send_json_error('Módulo no disponible');
    }

    $resultado = false;
    switch ($accion) {
        case 'resolve':
            $resultado = $modulo->actualizar_estado_bug($bug_id, 'resuelto');
            break;
        case 'ignore':
            $resultado = $modulo->actualizar_estado_bug($bug_id, 'ignorado');
            break;
        case 'reopen':
            $resultado = $modulo->actualizar_estado_bug($bug_id, 'abierto');
            break;
    }

    if ($resultado) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Error al actualizar');
    }
});
