<?php
/**
 * Template: Listado de Incidencias
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

// Verificar si existe la tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_incidencias)) {
    echo '<div class="incidencias-empty"><p>' . esc_html__('El módulo de incidencias no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Construir query
$where = ["estado != 'eliminada'"];
$params = [];

if ($estado_filtro) {
    $where[] = "estado = %s";
    $params[] = $estado_filtro;
}

if ($tipo_filtro) {
    $where[] = "tipo = %s";
    $params[] = $tipo_filtro;
}

if ($buscar) {
    $where[] = "(titulo LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s)";
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

$limite = isset($limit) ? intval($limit) : 20;
$where_sql = implode(' AND ', $where);

$query = "SELECT * FROM $tabla_incidencias WHERE $where_sql ORDER BY created_at DESC LIMIT %d";
$params[] = $limite;

$incidencias = $wpdb->get_results($wpdb->prepare($query, $params));

// Obtener opciones de filtro
$estados_disponibles = ['pendiente', 'en_proceso', 'resuelta', 'cerrada'];
$tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_incidencias WHERE tipo IS NOT NULL ORDER BY tipo");

// Labels para estados (español e inglés)
$estados_labels = [
    // Estados en español
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_proceso' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'resuelta' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cerrada' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    // Estados en inglés (para datos existentes)
    'pending' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'in_progress' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'resolved' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'closed' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estados_colors = [
    // Estados en español
    'pendiente' => '#f59e0b',
    'en_proceso' => '#3b82f6',
    'resuelta' => '#10b981',
    'cerrada' => '#6b7280',
    // Estados en inglés
    'pending' => '#f59e0b',
    'in_progress' => '#3b82f6',
    'resolved' => '#10b981',
    'closed' => '#6b7280',
];

// URL base para los detalles de incidencias
$incidencias_base_url = Flavor_Platform_Helpers::get_action_url('incidencias', '');
?>

<div class="incidencias-listado-wrapper">
    <div class="incidencias-header">
        <h2><?php esc_html_e('Incidencias de la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo esc_url($incidencias_base_url . 'reportar/'); ?>" class="btn btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Reportar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <?php $mostrar_filtros = isset($mostrar_filtros) ? $mostrar_filtros : true; ?>
    <?php if ($mostrar_filtros): ?>
    <form class="incidencias-filtros" method="get">
        <div class="filtro-grupo">
            <select name="estado">
                <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($estados_disponibles as $estado): ?>
                    <option value="<?php echo esc_attr($estado); ?>" <?php selected($estado_filtro, $estado); ?>>
                        <?php echo esc_html($estados_labels[$estado] ?? ucfirst($estado)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($tipos_disponibles): ?>
            <div class="filtro-grupo">
                <select name="tipo">
                    <option value=""><?php esc_html_e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos_disponibles as $tipo): ?>
                        <option value="<?php echo esc_attr($tipo); ?>" <?php selected($tipo_filtro, $tipo); ?>>
                            <?php echo esc_html(ucfirst($tipo)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="filtro-grupo filtro-buscar">
            <input type="text" name="buscar" value="<?php echo esc_attr($buscar); ?>"
                   placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>
        <button type="submit" class="btn btn-outline"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </form>
    <?php endif; ?>

    <!-- Listado -->
    <?php if ($incidencias): ?>
        <div class="incidencias-grid">
            <?php foreach ($incidencias as $incidencia): ?>
                <div class="incidencia-card">
                    <div class="incidencia-header">
                        <span class="incidencia-estado" style="background: <?php echo esc_attr($estados_colors[$incidencia->estado] ?? '#6b7280'); ?>">
                            <?php echo esc_html($estados_labels[$incidencia->estado] ?? ucfirst($incidencia->estado)); ?>
                        </span>
                        <?php if (!empty($incidencia->tipo)): ?>
                            <span class="incidencia-tipo"><?php echo esc_html(ucfirst($incidencia->tipo)); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="incidencia-titulo"><?php echo esc_html($incidencia->titulo); ?></h3>
                    <?php if (!empty($incidencia->ubicacion)): ?>
                        <div class="incidencia-ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($incidencia->ubicacion); ?>
                        </div>
                    <?php endif; ?>
                    <p class="incidencia-descripcion"><?php echo esc_html(wp_trim_words($incidencia->descripcion, 20)); ?></p>
                    <div class="incidencia-meta">
                        <span class="incidencia-fecha">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($incidencia->created_at))); ?>
                        </span>
                    </div>
                    <div class="incidencia-footer">
                        <a href="<?php echo esc_url($incidencias_base_url . $incidencia->id . '/'); ?>" class="btn btn-sm btn-outline">
                            <?php esc_html_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="incidencias-empty">
            <span class="dashicons dashicons-flag"></span>
            <h3><?php esc_html_e('No hay incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('No se encontraron incidencias con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.incidencias-listado-wrapper { max-width: 1100px; margin: 0 auto; }
.incidencias-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.incidencias-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
.incidencias-filtros { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.filtro-grupo select, .filtro-grupo input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
.filtro-buscar { flex: 1; min-width: 200px; }
.filtro-buscar input { width: 100%; }
.incidencias-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem; }
.incidencia-card { background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
.incidencia-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.incidencia-header { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
.incidencia-estado { padding: 3px 10px; border-radius: 4px; color: white; font-size: 0.75rem; font-weight: 500; }
.incidencia-tipo { padding: 3px 8px; background: #f3f4f6; border-radius: 4px; font-size: 0.75rem; color: #6b7280; }
.incidencia-titulo { margin: 0 0 0.5rem; font-size: 1.1rem; color: #1f2937; }
.incidencia-ubicacion { font-size: 0.85rem; color: #6b7280; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
.incidencia-ubicacion .dashicons { font-size: 14px; width: 14px; height: 14px; }
.incidencia-descripcion { margin: 0 0 0.75rem; font-size: 0.9rem; color: #6b7280; line-height: 1.5; }
.incidencia-meta { font-size: 0.8rem; color: #9ca3af; margin-bottom: 1rem; }
.incidencia-meta .dashicons { font-size: 14px; width: 14px; height: 14px; vertical-align: middle; }
.incidencia-footer { border-top: 1px solid #f3f4f6; padding-top: 0.75rem; }
.incidencias-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.incidencias-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.incidencias-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.incidencias-empty p { margin: 0; color: #6b7280; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #ef4444; color: white; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8rem; }
</style>
