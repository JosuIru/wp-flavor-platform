<?php
/**
 * Vista Admin: Gestión de Suscripciones
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$suscripciones_manager = Flavor_GC_Subscriptions::get_instance();

// Estadísticas
$estadisticas = $suscripciones_manager->obtener_estadisticas();

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$pagina_actual = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$por_pagina = 20;

// Obtener suscripciones
global $wpdb;
$tabla_suscripciones = $wpdb->prefix . 'flavor_gc_suscripciones';
$tabla_cestas = $wpdb->prefix . 'flavor_gc_cestas_tipo';
$tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

$where = '1=1';
$params = [];

if ($estado_filtro) {
    $where .= ' AND s.estado = %s';
    $params[] = $estado_filtro;
}

// Contar total
$total_query = "SELECT COUNT(*) FROM {$tabla_suscripciones} s WHERE {$where}";
$total = empty($params) ? $wpdb->get_var($total_query) : $wpdb->get_var($wpdb->prepare($total_query, ...$params));
$total_paginas = ceil($total / $por_pagina);

// Obtener suscripciones
$offset = ($pagina_actual - 1) * $por_pagina;
$query = "SELECT s.*, c.nombre as cesta_nombre, c.precio_base,
          cons.usuario_id, u.display_name, u.user_email
          FROM {$tabla_suscripciones} s
          LEFT JOIN {$tabla_cestas} c ON s.tipo_cesta_id = c.id
          LEFT JOIN {$tabla_consumidores} cons ON s.consumidor_id = cons.id
          LEFT JOIN {$wpdb->users} u ON cons.usuario_id = u.ID
          WHERE {$where}
          ORDER BY s.fecha_inicio DESC
          LIMIT %d OFFSET %d";

$params[] = $por_pagina;
$params[] = $offset;
$suscripciones = $wpdb->get_results($wpdb->prepare($query, ...$params));

// Tipos de cestas
$tipos_cestas = $suscripciones_manager->listar_tipos_cestas(true);
?>

<div class="wrap gc-admin-suscripciones">
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Suscripciones', 'flavor-chat-ia'); ?>
    </h1>
    <a href="#" class="page-title-action gc-modal-trigger" data-modal="modal-gestionar-cestas">
        <?php _e('Gestionar Cestas', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="gc-stats-grid">
        <div class="gc-stat-card gc-stat-activo">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['total_activas']); ?></span>
            <span class="gc-stat-label"><?php _e('Activas', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-pausada">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['total_pausadas']); ?></span>
            <span class="gc-stat-label"><?php _e('Pausadas', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-cancelada">
            <span class="gc-stat-numero"><?php echo esc_html($estadisticas['total_canceladas']); ?></span>
            <span class="gc-stat-label"><?php _e('Canceladas', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="gc-stat-card gc-stat-ingresos">
            <span class="gc-stat-numero"><?php echo number_format($estadisticas['ingresos_mensuales'], 0); ?> €</span>
            <span class="gc-stat-label"><?php _e('Ingresos/mes estimados', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Distribución por cesta -->
    <?php if (!empty($estadisticas['por_tipo_cesta'])): ?>
    <div class="gc-distribucion-cestas">
        <h3><?php _e('Distribución por tipo de cesta', 'flavor-chat-ia'); ?></h3>
        <div class="gc-cestas-bars">
            <?php
            $max_cantidad = max($estadisticas['por_tipo_cesta']);
            foreach ($estadisticas['por_tipo_cesta'] as $nombre => $cantidad):
                $porcentaje = $max_cantidad > 0 ? ($cantidad / $max_cantidad) * 100 : 0;
            ?>
                <div class="gc-cesta-bar">
                    <span class="gc-cesta-nombre"><?php echo esc_html($nombre); ?></span>
                    <div class="gc-bar-container">
                        <div class="gc-bar" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                    </div>
                    <span class="gc-cesta-cantidad"><?php echo esc_html($cantidad); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="gc-filtros-wrapper">
        <form method="get" class="gc-filtros-form">
            <input type="hidden" name="page" value="gc-suscripciones">

            <div class="gc-filtro">
                <label for="filtro-estado"><?php _e('Estado:', 'flavor-chat-ia'); ?></label>
                <select id="filtro-estado" name="estado">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="activa" <?php selected($estado_filtro, 'activa'); ?>><?php _e('Activa', 'flavor-chat-ia'); ?></option>
                    <option value="pausada" <?php selected($estado_filtro, 'pausada'); ?>><?php _e('Pausada', 'flavor-chat-ia'); ?></option>
                    <option value="cancelada" <?php selected($estado_filtro, 'cancelada'); ?>><?php _e('Cancelada', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=gc-suscripciones'); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
        </form>
    </div>

    <!-- Tabla de Suscripciones -->
    <table class="wp-list-table widefat fixed striped gc-tabla-suscripciones">
        <thead>
            <tr>
                <th scope="col" class="column-usuario"><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-cesta"><?php _e('Cesta', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-frecuencia"><?php _e('Frecuencia', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-importe"><?php _e('Importe', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-proximo"><?php _e('Próximo Cargo', 'flavor-chat-ia'); ?></th>
                <th scope="col" class="column-acciones"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suscripciones)): ?>
                <tr>
                    <td colspan="7"><?php _e('No se encontraron suscripciones.', 'flavor-chat-ia'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($suscripciones as $suscripcion): ?>
                    <tr data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                        <td class="column-usuario">
                            <strong><?php echo esc_html($suscripcion->display_name); ?></strong>
                            <br><small><?php echo esc_html($suscripcion->user_email); ?></small>
                        </td>
                        <td class="column-cesta">
                            <?php echo esc_html($suscripcion->cesta_nombre); ?>
                        </td>
                        <td class="column-frecuencia">
                            <?php echo esc_html($suscripciones_manager->obtener_etiqueta_frecuencia($suscripcion->frecuencia)); ?>
                        </td>
                        <td class="column-importe">
                            <?php echo number_format($suscripcion->importe, 2); ?> €
                        </td>
                        <td class="column-estado">
                            <span class="gc-estado-badge gc-estado-<?php echo esc_attr($suscripcion->estado); ?>">
                                <?php echo esc_html($suscripciones_manager->obtener_etiqueta_estado($suscripcion->estado)); ?>
                            </span>
                        </td>
                        <td class="column-proximo">
                            <?php if ($suscripcion->estado === 'activa' && $suscripcion->fecha_proximo_cargo): ?>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($suscripcion->fecha_proximo_cargo))); ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="column-acciones">
                            <?php if ($suscripcion->estado === 'activa'): ?>
                                <button type="button" class="button gc-pausar-suscripcion" data-id="<?php echo esc_attr($suscripcion->id); ?>">
                                    <?php _e('Pausar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php elseif ($suscripcion->estado === 'pausada'): ?>
                                <button type="button" class="button button-primary gc-reanudar-suscripcion" data-id="<?php echo esc_attr($suscripcion->id); ?>">
                                    <?php _e('Reanudar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                            <?php if ($suscripcion->estado !== 'cancelada'): ?>
                                <button type="button" class="button gc-cancelar-suscripcion" data-id="<?php echo esc_attr($suscripcion->id); ?>">
                                    <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
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
                    <?php printf(_n('%s elemento', '%s elementos', $total, 'flavor-chat-ia'), number_format_i18n($total)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_paginas,
                        'current' => $pagina_actual,
                    ]);
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Gestionar Cestas -->
<div id="modal-gestionar-cestas" class="gc-modal" style="display:none;">
    <div class="gc-modal-content gc-modal-lg">
        <div class="gc-modal-header">
            <h2><?php _e('Tipos de Cestas', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="gc-modal-close">&times;</button>
        </div>
        <div class="gc-modal-body">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Precio Base', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos_cestas as $cesta): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($cesta->nombre); ?></strong>
                                <br><small><?php echo esc_html($cesta->descripcion); ?></small>
                            </td>
                            <td><?php echo number_format($cesta->precio_base, 2); ?> €</td>
                            <td>
                                <span class="gc-estado-badge <?php echo $cesta->activa ? 'gc-estado-activo' : 'gc-estado-inactivo'; ?>">
                                    <?php echo $cesta->activa ? __('Activa', 'flavor-chat-ia') : __('Inactiva', 'flavor-chat-ia'); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small gc-editar-cesta" data-id="<?php echo esc_attr($cesta->id); ?>">
                                    <?php _e('Editar', 'flavor-chat-ia'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Modal
    $('.gc-modal-trigger').on('click', function(e) {
        e.preventDefault();
        var modalId = $(this).data('modal');
        $('#' + modalId).fadeIn(200);
    });

    $('.gc-modal-close').on('click', function() {
        $(this).closest('.gc-modal').fadeOut(200);
    });

    $('.gc-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

    // Acciones suscripción
    $('.gc-pausar-suscripcion').on('click', function() {
        var id = $(this).data('id');
        if (confirm('<?php _e('¿Pausar esta suscripción?', 'flavor-chat-ia'); ?>')) {
            $.post(ajaxurl, {
                action: 'gc_pausar_suscripcion',
                suscripcion_id: id,
                nonce: '<?php echo wp_create_nonce('gc_suscripcion_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.mensaje || response.data.error);
                }
            });
        }
    });

    $('.gc-reanudar-suscripcion').on('click', function() {
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'gc_reanudar_suscripcion',
            suscripcion_id: id,
            nonce: '<?php echo wp_create_nonce('gc_suscripcion_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.mensaje || response.data.error);
            }
        });
    });

    $('.gc-cancelar-suscripcion').on('click', function() {
        var id = $(this).data('id');
        if (confirm('<?php _e('¿Cancelar esta suscripción? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?>')) {
            $.post(ajaxurl, {
                action: 'gc_cancelar_suscripcion',
                suscripcion_id: id,
                nonce: '<?php echo wp_create_nonce('gc_suscripcion_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.mensaje || response.data.error);
                }
            });
        }
    });
});
</script>

<style>
.gc-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
}
.gc-stat-label {
    color: #646970;
    font-size: 13px;
}
.gc-stat-activo .gc-stat-numero { color: #00a32a; }
.gc-stat-pausada .gc-stat-numero { color: #dba617; }
.gc-stat-cancelada .gc-stat-numero { color: #d63638; }
.gc-stat-ingresos .gc-stat-numero { color: #2271b1; }

.gc-distribucion-cestas {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.gc-distribucion-cestas h3 {
    margin-top: 0;
}
.gc-cesta-bar {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.gc-cesta-nombre {
    width: 120px;
    font-weight: 500;
}
.gc-bar-container {
    flex: 1;
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    margin: 0 15px;
}
.gc-bar {
    height: 100%;
    background: linear-gradient(90deg, #2e7d32, #4caf50);
    border-radius: 10px;
    transition: width 0.3s ease;
}
.gc-cesta-cantidad {
    width: 40px;
    text-align: right;
    font-weight: 600;
}

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
}

.gc-estado-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.gc-estado-activa { background: #d4edda; color: #155724; }
.gc-estado-pausada { background: #fff3cd; color: #856404; }
.gc-estado-cancelada { background: #f8d7da; color: #721c24; }
.gc-estado-activo { background: #d4edda; color: #155724; }
.gc-estado-inactivo { background: #e9ecef; color: #6c757d; }

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
.gc-modal-lg {
    max-width: 800px;
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
</style>
