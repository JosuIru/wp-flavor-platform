<?php
/**
 * Template: Nuevo Tramite
 *
 * Pagina de seleccion de tipo de tramite para iniciar uno nuevo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar login
if (!is_user_logged_in()) {
    echo '<div class="tramites-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesion para realizar un tramite', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
    echo '<p>' . esc_html__('Necesitas una cuenta para poder iniciar tramites.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_tipos_tramite)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$buscar = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

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

$where_sql = implode(' AND ', $where);

$query = "SELECT * FROM $tabla_tipos_tramite WHERE $where_sql ORDER BY categoria ASC, orden ASC, nombre ASC";

if ($params) {
    $tipos_tramite = $wpdb->get_results($wpdb->prepare($query, $params));
} else {
    $tipos_tramite = $wpdb->get_results($query);
}

// Obtener categorias disponibles con conteo
$categorias = $wpdb->get_results(
    "SELECT categoria, COUNT(*) as total
     FROM $tabla_tipos_tramite
     WHERE estado = 'activo' AND categoria IS NOT NULL AND categoria != ''
     GROUP BY categoria
     ORDER BY categoria ASC"
);

// Agrupar por categoria
$tramites_por_categoria = [];
foreach ($tipos_tramite as $tipo) {
    $cat = $tipo->categoria ?: __('General', FLAVOR_PLATFORM_TEXT_DOMAIN);
    if (!isset($tramites_por_categoria[$cat])) {
        $tramites_por_categoria[$cat] = [];
    }
    $tramites_por_categoria[$cat][] = $tipo;
}

$tramites_base_url = Flavor_Chat_Helpers::get_action_url('tramites', '');
?>

<div class="nuevo-tramite-wrapper">
    <div class="nuevo-tramite-header">
        <nav class="tramites-breadcrumb">
            <a href="<?php echo esc_url($tramites_base_url . 'mis-tramites/'); ?>"><?php esc_html_e('Mis tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <span class="separator">&rsaquo;</span>
            <span><?php esc_html_e('Nuevo tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </nav>
        <h2><?php esc_html_e('Iniciar nuevo tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="header-intro"><?php esc_html_e('Selecciona el tipo de tramite que deseas realizar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>

    <!-- Buscador -->
    <div class="nuevo-tramite-buscar">
        <form method="get" class="buscar-form">
            <div class="buscar-input-wrapper">
                <span class="dashicons dashicons-search"></span>
                <input type="text" name="q" value="<?php echo esc_attr($buscar); ?>"
                       placeholder="<?php esc_attr_e('Buscar tramite por nombre...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
            <button type="submit" class="btn btn-primary"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </form>
    </div>

    <div class="nuevo-tramite-content">
        <!-- Sidebar con categorias -->
        <aside class="categorias-sidebar">
            <h4><?php esc_html_e('Categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul class="categorias-lista">
                <li>
                    <a href="<?php echo esc_url(remove_query_arg('categoria')); ?>" class="<?php echo empty($categoria_filtro) ? 'activo' : ''; ?>">
                        <span class="dashicons dashicons-category"></span>
                        <?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="cat-count"><?php echo count($tipos_tramite); ?></span>
                    </a>
                </li>
                <?php foreach ($categorias as $cat): ?>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('categoria', $cat->categoria)); ?>" class="<?php echo $categoria_filtro === $cat->categoria ? 'activo' : ''; ?>">
                        <span class="dashicons dashicons-tag"></span>
                        <?php echo esc_html(ucfirst($cat->categoria)); ?>
                        <span class="cat-count"><?php echo intval($cat->total); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Listado de tramites -->
        <div class="tramites-main">
            <?php if ($buscar || $categoria_filtro): ?>
            <div class="filtros-activos">
                <?php if ($buscar): ?>
                    <span class="filtro-tag">
                        <?php echo sprintf(esc_html__('Busqueda: "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($buscar)); ?>
                        <a href="<?php echo esc_url(remove_query_arg('q')); ?>"><span class="dashicons dashicons-no-alt"></span></a>
                    </span>
                <?php endif; ?>
                <?php if ($categoria_filtro): ?>
                    <span class="filtro-tag">
                        <?php echo esc_html(ucfirst($categoria_filtro)); ?>
                        <a href="<?php echo esc_url(remove_query_arg('categoria')); ?>"><span class="dashicons dashicons-no-alt"></span></a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($tipos_tramite): ?>
                <?php foreach ($tramites_por_categoria as $categoria_nombre => $tipos): ?>
                <div class="tramites-categoria-grupo">
                    <h3 class="categoria-titulo"><?php echo esc_html(ucfirst($categoria_nombre)); ?></h3>
                    <div class="tramites-grid">
                        <?php foreach ($tipos as $tipo): ?>
                        <div class="tipo-tramite-card">
                            <div class="tipo-header">
                                <span class="tipo-icono" style="background: <?php echo esc_attr($tipo->color ?: '#6b7280'); ?>">
                                    <span class="dashicons <?php echo esc_attr($tipo->icono ?: 'dashicons-clipboard'); ?>"></span>
                                </span>
                                <div class="tipo-badges">
                                    <?php if ($tipo->permite_online): ?>
                                        <span class="badge badge-success" title="<?php esc_attr_e('Disponible online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-laptop"></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($tipo->requiere_cita): ?>
                                        <span class="badge badge-info" title="<?php esc_attr_e('Requiere cita previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h4 class="tipo-nombre"><?php echo esc_html($tipo->nombre); ?></h4>
                            <?php if (!empty($tipo->descripcion)): ?>
                                <p class="tipo-descripcion"><?php echo esc_html(wp_trim_words($tipo->descripcion, 18)); ?></p>
                            <?php endif; ?>
                            <div class="tipo-meta">
                                <?php if ($tipo->plazo_resolucion_dias): ?>
                                    <span class="meta-item" title="<?php esc_attr_e('Plazo estimado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo sprintf(esc_html__('%d dias', FLAVOR_PLATFORM_TEXT_DOMAIN), $tipo->plazo_resolucion_dias); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="meta-item precio <?php echo $tipo->precio > 0 ? '' : 'gratuito'; ?>">
                                    <?php if ($tipo->precio > 0): ?>
                                        <span class="dashicons dashicons-money-alt"></span>
                                        <?php echo esc_html(number_format($tipo->precio, 2)); ?> &euro;
                                    <?php else: ?>
                                        <?php esc_html_e('Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="tipo-footer">
                                <a href="<?php echo esc_url($tramites_base_url . 'iniciar/?tipo=' . $tipo->id); ?>" class="btn btn-primary btn-block">
                                    <?php esc_html_e('Iniciar tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="tramites-empty">
                    <span class="dashicons dashicons-search"></span>
                    <h3><?php esc_html_e('No se encontraron tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Intenta con otros terminos de busqueda o selecciona otra categoria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url($tramites_base_url . 'nuevo/'); ?>" class="btn btn-outline">
                        <?php esc_html_e('Ver todos los tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.nuevo-tramite-wrapper { max-width: 1200px; margin: 0 auto; }
.tramites-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-login-required .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; display: block; margin: 0 auto 1rem; }
.tramites-breadcrumb { margin-bottom: 1rem; font-size: 0.9rem; color: #6b7280; }
.tramites-breadcrumb a { color: #3b82f6; text-decoration: none; }
.tramites-breadcrumb .separator { margin: 0 0.5rem; }
.nuevo-tramite-header h2 { margin: 0 0 0.5rem; font-size: 1.75rem; color: #1f2937; }
.header-intro { margin: 0 0 1.5rem; color: #6b7280; }
.nuevo-tramite-buscar { margin-bottom: 2rem; }
.buscar-form { display: flex; gap: 0.75rem; max-width: 500px; }
.buscar-input-wrapper { flex: 1; position: relative; }
.buscar-input-wrapper .dashicons { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
.buscar-input-wrapper input { width: 100%; padding: 0.875rem 1rem 0.875rem 2.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; }
.buscar-input-wrapper input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.nuevo-tramite-content { display: grid; grid-template-columns: 240px 1fr; gap: 2rem; }
.categorias-sidebar { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); height: fit-content; position: sticky; top: 2rem; }
.categorias-sidebar h4 { margin: 0 0 1rem; font-size: 1rem; color: #374151; }
.categorias-lista { list-style: none; margin: 0; padding: 0; }
.categorias-lista li a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 8px; text-decoration: none; color: #6b7280; transition: all 0.2s; }
.categorias-lista li a:hover { background: #f9fafb; color: #374151; }
.categorias-lista li a.activo { background: #eff6ff; color: #3b82f6; font-weight: 500; }
.categorias-lista li a .dashicons { font-size: 18px; width: 18px; height: 18px; }
.cat-count { margin-left: auto; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; }
.categorias-lista li a.activo .cat-count { background: #dbeafe; }
.tramites-main { min-width: 0; }
.filtros-activos { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.filtro-tag { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.75rem; background: #eff6ff; border-radius: 6px; font-size: 0.85rem; color: #3b82f6; }
.filtro-tag a { color: #3b82f6; display: flex; }
.filtro-tag a:hover { color: #1d4ed8; }
.filtro-tag .dashicons { font-size: 16px; width: 16px; height: 16px; }
.tramites-categoria-grupo { margin-bottom: 2rem; }
.categoria-titulo { margin: 0 0 1rem; font-size: 1.15rem; color: #374151; padding-bottom: 0.5rem; border-bottom: 2px solid #e5e7eb; }
.tramites-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
.tipo-tramite-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; display: flex; flex-direction: column; }
.tipo-tramite-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.tipo-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.tipo-icono { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.tipo-icono .dashicons { color: white; font-size: 24px; width: 24px; height: 24px; }
.tipo-badges { display: flex; gap: 0.35rem; }
.badge { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 6px; }
.badge .dashicons { font-size: 16px; width: 16px; height: 16px; }
.badge-success { background: #d1fae5; color: #059669; }
.badge-info { background: #dbeafe; color: #2563eb; }
.tipo-nombre { margin: 0 0 0.5rem; font-size: 1.05rem; color: #1f2937; }
.tipo-descripcion { margin: 0 0 1rem; font-size: 0.9rem; color: #6b7280; line-height: 1.5; flex: 1; }
.tipo-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.85rem; color: #6b7280; }
.meta-item { display: flex; align-items: center; gap: 0.35rem; }
.meta-item .dashicons { font-size: 14px; width: 14px; height: 14px; }
.meta-item.precio { font-weight: 600; color: #374151; }
.meta-item.gratuito { color: #059669; }
.tipo-footer { margin-top: auto; }
.tramites-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-empty .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; margin-bottom: 1rem; }
.tramites-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.tramites-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.95rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-block { width: 100%; }
@media (max-width: 768px) {
    .nuevo-tramite-content { grid-template-columns: 1fr; }
    .categorias-sidebar { position: static; margin-bottom: 1rem; }
    .categorias-lista { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .categorias-lista li a { padding: 0.5rem 0.75rem; }
}
</style>
