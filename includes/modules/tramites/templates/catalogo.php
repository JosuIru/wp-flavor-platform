<?php
/**
 * Template: Catalogo de Tramites
 *
 * Muestra todos los tramites disponibles agrupados por categoria
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_tipos_tramite)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$modalidad_filtro = isset($_GET['modalidad']) ? sanitize_text_field($_GET['modalidad']) : '';

// Construir query
$where = ["estado = 'activo'"];
$params = [];

if ($categoria_filtro) {
    $where[] = "categoria = %s";
    $params[] = $categoria_filtro;
}

if ($buscar) {
    $where[] = "(nombre LIKE %s OR descripcion LIKE %s)";
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

if ($modalidad_filtro === 'online') {
    $where[] = "permite_online = 1";
} elseif ($modalidad_filtro === 'presencial') {
    $where[] = "permite_presencial = 1";
}

$limite = isset($limit) ? intval($limit) : 50;
$where_sql = implode(' AND ', $where);

$query = "SELECT * FROM $tabla_tipos_tramite WHERE $where_sql ORDER BY orden ASC, nombre ASC LIMIT %d";
$params[] = $limite;

$tramites = $wpdb->get_results($wpdb->prepare($query, $params));

// Obtener categorias disponibles
$categorias_disponibles = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_tipos_tramite WHERE estado = 'activo' AND categoria IS NOT NULL ORDER BY categoria");

// Agrupar tramites por categoria
$tramites_por_categoria = [];
foreach ($tramites as $tramite) {
    $categoria = $tramite->categoria ?: __('General', 'flavor-chat-ia');
    if (!isset($tramites_por_categoria[$categoria])) {
        $tramites_por_categoria[$categoria] = [];
    }
    $tramites_por_categoria[$categoria][] = $tramite;
}

// URL base para los tramites
$tramites_base_url = Flavor_Chat_Helpers::get_action_url('tramites', '');
?>

<div class="tramites-catalogo-wrapper">
    <div class="tramites-header">
        <div class="tramites-header-content">
            <h2><?php esc_html_e('Catalogo de Tramites', 'flavor-chat-ia'); ?></h2>
            <p class="tramites-intro"><?php esc_html_e('Encuentra el tramite que necesitas y realiza tu solicitud de forma sencilla.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo esc_url($tramites_base_url . 'mis-tramites/'); ?>" class="btn btn-outline">
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Mis tramites', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <?php $mostrar_filtros = isset($mostrar_filtros) ? $mostrar_filtros : true; ?>
    <?php if ($mostrar_filtros): ?>
    <form class="tramites-filtros" method="get">
        <div class="filtro-grupo filtro-buscar">
            <input type="text" name="buscar" value="<?php echo esc_attr($buscar); ?>"
                   placeholder="<?php esc_attr_e('Buscar tramite...', 'flavor-chat-ia'); ?>">
        </div>
        <?php if ($categorias_disponibles): ?>
            <div class="filtro-grupo">
                <select name="categoria">
                    <option value=""><?php esc_html_e('Todas las categorias', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias_disponibles as $categoria): ?>
                        <option value="<?php echo esc_attr($categoria); ?>" <?php selected($categoria_filtro, $categoria); ?>>
                            <?php echo esc_html(ucfirst($categoria)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="filtro-grupo">
            <select name="modalidad">
                <option value=""><?php esc_html_e('Todas las modalidades', 'flavor-chat-ia'); ?></option>
                <option value="online" <?php selected($modalidad_filtro, 'online'); ?>><?php esc_html_e('Online', 'flavor-chat-ia'); ?></option>
                <option value="presencial" <?php selected($modalidad_filtro, 'presencial'); ?>><?php esc_html_e('Presencial', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></button>
    </form>
    <?php endif; ?>

    <!-- Resumen de categorias -->
    <?php if (empty($categoria_filtro) && empty($buscar) && $categorias_disponibles): ?>
    <div class="tramites-categorias-grid">
        <?php foreach ($categorias_disponibles as $categoria):
            $total_categoria = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_tipos_tramite WHERE estado = 'activo' AND categoria = %s",
                $categoria
            ));
        ?>
            <a href="<?php echo esc_url(add_query_arg('categoria', $categoria)); ?>" class="categoria-card">
                <span class="categoria-icono">
                    <span class="dashicons dashicons-category"></span>
                </span>
                <div class="categoria-info">
                    <h4><?php echo esc_html(ucfirst($categoria)); ?></h4>
                    <span class="categoria-count"><?php echo intval($total_categoria); ?> <?php esc_html_e('tramites', 'flavor-chat-ia'); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Listado de tramites -->
    <?php if ($tramites): ?>
        <?php foreach ($tramites_por_categoria as $categoria_nombre => $tramites_categoria): ?>
        <div class="tramites-seccion">
            <h3 class="tramites-seccion-titulo">
                <span class="dashicons dashicons-category"></span>
                <?php echo esc_html(ucfirst($categoria_nombre)); ?>
                <span class="tramites-seccion-count">(<?php echo count($tramites_categoria); ?>)</span>
            </h3>
            <div class="tramites-grid">
                <?php foreach ($tramites_categoria as $tramite): ?>
                    <div class="tramite-card">
                        <div class="tramite-header">
                            <span class="tramite-icono" style="background: <?php echo esc_attr($tramite->color ?: '#6b7280'); ?>">
                                <span class="dashicons <?php echo esc_attr($tramite->icono ?: 'dashicons-clipboard'); ?>"></span>
                            </span>
                            <div class="tramite-badges">
                                <?php if ($tramite->permite_online): ?>
                                    <span class="badge badge-success"><?php esc_html_e('Online', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                                <?php if ($tramite->requiere_cita): ?>
                                    <span class="badge badge-info"><?php esc_html_e('Cita previa', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h4 class="tramite-titulo"><?php echo esc_html($tramite->nombre); ?></h4>
                        <?php if (!empty($tramite->descripcion)): ?>
                            <p class="tramite-descripcion"><?php echo esc_html(wp_trim_words($tramite->descripcion, 20)); ?></p>
                        <?php endif; ?>
                        <div class="tramite-meta">
                            <?php if ($tramite->plazo_resolucion_dias): ?>
                                <span class="tramite-plazo">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo sprintf(esc_html__('%d dias', 'flavor-chat-ia'), $tramite->plazo_resolucion_dias); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($tramite->precio > 0): ?>
                                <span class="tramite-precio"><?php echo esc_html(number_format($tramite->precio, 2)); ?> &euro;</span>
                            <?php else: ?>
                                <span class="tramite-precio tramite-gratuito"><?php esc_html_e('Gratuito', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="tramite-footer">
                            <a href="<?php echo esc_url(add_query_arg('tramite_id', $tramite->id, $tramites_base_url . 'iniciar/')); ?>" class="btn btn-primary btn-block">
                                <?php esc_html_e('Iniciar tramite', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="tramites-empty">
            <span class="dashicons dashicons-clipboard"></span>
            <h3><?php esc_html_e('No hay tramites disponibles', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('No se encontraron tramites con los criterios seleccionados.', 'flavor-chat-ia'); ?></p>
            <?php if ($categoria_filtro || $buscar || $modalidad_filtro): ?>
                <a href="<?php echo esc_url(remove_query_arg(['categoria', 'buscar', 'modalidad'])); ?>" class="btn btn-outline">
                    <?php esc_html_e('Ver todos los tramites', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.tramites-catalogo-wrapper { max-width: 1200px; margin: 0 auto; }
.tramites-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.tramites-header-content h2 { margin: 0 0 0.5rem; font-size: 1.75rem; color: #1f2937; }
.tramites-intro { margin: 0; color: #6b7280; font-size: 1rem; }
.tramites-filtros { display: flex; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap; padding: 1.25rem; background: #f9fafb; border-radius: 12px; }
.filtro-grupo select, .filtro-grupo input { padding: 0.625rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; background: white; }
.filtro-buscar { flex: 1; min-width: 250px; }
.filtro-buscar input { width: 100%; }
.tramites-categorias-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.categoria-card { display: flex; align-items: center; gap: 1rem; background: white; padding: 1.25rem; border-radius: 10px; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
.categoria-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.categoria-icono { width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.categoria-icono .dashicons { color: #3b82f6; font-size: 24px; width: 24px; height: 24px; }
.categoria-info h4 { margin: 0 0 0.25rem; color: #1f2937; font-size: 1rem; }
.categoria-count { font-size: 0.85rem; color: #6b7280; }
.tramites-seccion { margin-bottom: 2.5rem; }
.tramites-seccion-titulo { display: flex; align-items: center; gap: 0.5rem; margin: 0 0 1.25rem; font-size: 1.25rem; color: #1f2937; padding-bottom: 0.75rem; border-bottom: 2px solid #e5e7eb; }
.tramites-seccion-titulo .dashicons { color: #3b82f6; }
.tramites-seccion-count { font-weight: 400; color: #9ca3af; font-size: 0.9rem; }
.tramites-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.25rem; }
.tramite-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
.tramite-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.tramite-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.tramite-icono { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.tramite-icono .dashicons { color: white; font-size: 22px; width: 22px; height: 22px; }
.tramite-badges { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.badge { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 500; }
.badge-success { background: #d1fae5; color: #059669; }
.badge-info { background: #dbeafe; color: #2563eb; }
.tramite-titulo { margin: 0 0 0.5rem; font-size: 1.1rem; color: #1f2937; }
.tramite-descripcion { margin: 0 0 1rem; font-size: 0.9rem; color: #6b7280; line-height: 1.5; }
.tramite-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.85rem; color: #6b7280; }
.tramite-plazo { display: flex; align-items: center; gap: 0.35rem; }
.tramite-plazo .dashicons { font-size: 14px; width: 14px; height: 14px; }
.tramite-precio { font-weight: 600; color: #1f2937; }
.tramite-gratuito { color: #059669; }
.tramite-footer { border-top: 1px solid #f3f4f6; padding-top: 1rem; }
.tramites-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-empty .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; margin-bottom: 1rem; }
.tramites-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.tramites-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-block { width: 100%; }
@media (max-width: 640px) {
    .tramites-header { flex-direction: column; }
    .tramites-filtros { flex-direction: column; }
    .filtro-buscar { min-width: 100%; }
    .tramites-grid { grid-template-columns: 1fr; }
}
</style>
