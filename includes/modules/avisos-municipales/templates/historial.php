<?php
/**
 * Template: Historial de Avisos
 *
 * Muestra el historial de avisos pasados con paginacion.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
$tabla_categorias = $wpdb->prefix . 'flavor_avisos_categorias';
$tabla_zonas = $wpdb->prefix . 'flavor_avisos_zonas';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_avisos)) {
    echo '<div class="avisos-empty"><p>' . esc_html__('El modulo de avisos municipales no esta configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Parametros del template
$por_pagina = isset($atts['por_pagina']) ? intval($atts['por_pagina']) : 15;
$mostrar_filtros = isset($atts['filtros']) ? ($atts['filtros'] === 'true') : true;
$vista = isset($atts['vista']) ? sanitize_text_field($atts['vista']) : 'timeline'; // timeline | lista | grid

// Paginacion
$pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$anio_filtro = isset($_GET['anio']) ? intval($_GET['anio']) : 0;
$mes_filtro = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Construir query
$where = ['a.publicado = 1'];
$params = [];

if ($categoria_filtro) {
    $where[] = 'a.categoria_id = %d';
    $params[] = $categoria_filtro;
}

if ($anio_filtro) {
    $where[] = 'YEAR(a.fecha_inicio) = %d';
    $params[] = $anio_filtro;
}

if ($mes_filtro && $anio_filtro) {
    $where[] = 'MONTH(a.fecha_inicio) = %d';
    $params[] = $mes_filtro;
}

if ($buscar) {
    $where[] = '(a.titulo LIKE %s OR a.contenido LIKE %s)';
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

$where_sql = implode(' AND ', $where);

// Contar total de registros
$total_sql = "SELECT COUNT(*) FROM $tabla_avisos a WHERE $where_sql";
$total_avisos = empty($params)
    ? $wpdb->get_var($total_sql)
    : $wpdb->get_var($wpdb->prepare($total_sql, $params));

$total_paginas = ceil($total_avisos / $por_pagina);

// Obtener avisos
$params_query = $params;
$params_query[] = $por_pagina;
$params_query[] = $offset;

$avisos_historial = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, c.nombre AS categoria_nombre, c.icono AS categoria_icono, c.color AS categoria_color, z.nombre AS zona_nombre
     FROM $tabla_avisos a
     LEFT JOIN $tabla_categorias c ON a.categoria_id = c.id
     LEFT JOIN $tabla_zonas z ON a.zona_id = z.id
     WHERE $where_sql
     ORDER BY a.fecha_inicio DESC
     LIMIT %d OFFSET %d",
    $params_query
));

// Agrupar por fecha para vista timeline
$avisos_agrupados = [];
if ($vista === 'timeline') {
    foreach ($avisos_historial as $aviso) {
        $fecha_grupo = date('Y-m-d', strtotime($aviso->fecha_inicio));
        if (!isset($avisos_agrupados[$fecha_grupo])) {
            $avisos_agrupados[$fecha_grupo] = [];
        }
        $avisos_agrupados[$fecha_grupo][] = $aviso;
    }
}

// Obtener categorias para filtros
$categorias_disponibles = $wpdb->get_results("SELECT * FROM $tabla_categorias WHERE activa = 1 ORDER BY orden ASC, nombre ASC");

// Obtener anios disponibles
$anios_disponibles = $wpdb->get_col("SELECT DISTINCT YEAR(fecha_inicio) as anio FROM $tabla_avisos WHERE publicado = 1 ORDER BY anio DESC");

// Meses para el filtro
$meses = [
    1 => __('Enero', FLAVOR_PLATFORM_TEXT_DOMAIN),
    2 => __('Febrero', FLAVOR_PLATFORM_TEXT_DOMAIN),
    3 => __('Marzo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    4 => __('Abril', FLAVOR_PLATFORM_TEXT_DOMAIN),
    5 => __('Mayo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    6 => __('Junio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    7 => __('Julio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    8 => __('Agosto', FLAVOR_PLATFORM_TEXT_DOMAIN),
    9 => __('Septiembre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    10 => __('Octubre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    11 => __('Noviembre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    12 => __('Diciembre', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Labels de prioridades
$prioridades_config = [
    'urgente' => ['label' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#dc2626'],
    'alta'    => ['label' => __('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f97316'],
    'media'   => ['label' => __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#eab308'],
    'baja'    => ['label' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#22c55e'],
];

// URL base para los detalles
$avisos_base_url = Flavor_Chat_Helpers::get_action_url('avisos_municipales', '');
$url_actual = strtok($_SERVER['REQUEST_URI'], '?');
?>

<div class="avisos-historial-wrapper">
    <header class="avisos-historial-header">
        <div>
            <h2><?php esc_html_e('Historial de Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Consulta los comunicados anteriores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <div class="avisos-historial-stats">
            <span class="avisos-stat">
                <span class="dashicons dashicons-archive"></span>
                <?php printf(esc_html__('%d avisos', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_avisos); ?>
            </span>
        </div>
    </header>

    <?php if ($mostrar_filtros): ?>
    <form class="avisos-filtros" method="get">
        <div class="avisos-filtros-row">
            <div class="filtro-grupo">
                <label for="anio"><?php esc_html_e('Anio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="anio" id="anio">
                    <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($anios_disponibles as $anio): ?>
                    <option value="<?php echo esc_attr($anio); ?>" <?php selected($anio_filtro, $anio); ?>>
                        <?php echo esc_html($anio); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($anio_filtro): ?>
            <div class="filtro-grupo">
                <label for="mes"><?php esc_html_e('Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="mes" id="mes">
                    <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($meses as $numero_mes => $nombre_mes): ?>
                    <option value="<?php echo esc_attr($numero_mes); ?>" <?php selected($mes_filtro, $numero_mes); ?>>
                        <?php echo esc_html($nombre_mes); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="filtro-grupo">
                <label for="categoria"><?php esc_html_e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="categoria" id="categoria">
                    <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($categorias_disponibles as $categoria): ?>
                    <option value="<?php echo esc_attr($categoria->id); ?>" <?php selected($categoria_filtro, $categoria->id); ?>>
                        <?php echo esc_html($categoria->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-grupo filtro-buscar">
                <label for="buscar"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="buscar" id="buscar" value="<?php echo esc_attr($buscar); ?>"
                       placeholder="<?php esc_attr_e('Buscar en historial...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
            <div class="filtro-grupo filtro-acciones">
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <?php if ($categoria_filtro || $anio_filtro || $mes_filtro || $buscar): ?>
                <a href="<?php echo esc_url($url_actual); ?>" class="btn btn-outline">
                    <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($avisos_historial): ?>

    <?php if ($vista === 'timeline'): ?>
    <!-- Vista Timeline -->
    <div class="avisos-timeline">
        <?php foreach ($avisos_agrupados as $fecha_grupo => $avisos_del_dia): ?>
        <div class="timeline-grupo">
            <div class="timeline-fecha">
                <span class="timeline-dia"><?php echo date_i18n('j', strtotime($fecha_grupo)); ?></span>
                <span class="timeline-mes"><?php echo date_i18n('M Y', strtotime($fecha_grupo)); ?></span>
            </div>
            <div class="timeline-contenido">
                <?php foreach ($avisos_del_dia as $aviso):
                    $prioridad_config = $prioridades_config[$aviso->prioridad] ?? $prioridades_config['media'];
                ?>
                <article class="timeline-item">
                    <div class="timeline-item-header">
                        <span class="timeline-prioridad" style="background: <?php echo esc_attr($prioridad_config['color']); ?>">
                            <?php echo esc_html($prioridad_config['label']); ?>
                        </span>
                        <?php if ($aviso->categoria_nombre): ?>
                        <span class="timeline-categoria" style="color: <?php echo esc_attr($aviso->categoria_color ?: '#6b7280'); ?>">
                            <?php echo esc_html($aviso->categoria_nombre); ?>
                        </span>
                        <?php endif; ?>
                        <span class="timeline-hora">
                            <?php echo date_i18n('H:i', strtotime($aviso->fecha_inicio)); ?>
                        </span>
                    </div>
                    <h3 class="timeline-titulo">
                        <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>">
                            <?php echo esc_html($aviso->titulo); ?>
                        </a>
                    </h3>
                    <p class="timeline-extracto">
                        <?php echo esc_html(wp_trim_words($aviso->extracto ?: $aviso->contenido, 15)); ?>
                    </p>
                    <?php if ($aviso->zona_nombre): ?>
                    <span class="timeline-zona">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($aviso->zona_nombre); ?>
                    </span>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- Vista Lista/Grid -->
    <div class="avisos-historial-lista">
        <?php foreach ($avisos_historial as $aviso):
            $prioridad_config = $prioridades_config[$aviso->prioridad] ?? $prioridades_config['media'];
        ?>
        <article class="aviso-historial-item">
            <div class="aviso-historial-fecha">
                <span class="fecha-dia"><?php echo date_i18n('j', strtotime($aviso->fecha_inicio)); ?></span>
                <span class="fecha-mes"><?php echo date_i18n('M', strtotime($aviso->fecha_inicio)); ?></span>
                <span class="fecha-anio"><?php echo date_i18n('Y', strtotime($aviso->fecha_inicio)); ?></span>
            </div>
            <div class="aviso-historial-contenido">
                <div class="aviso-historial-badges">
                    <span class="aviso-badge" style="background: <?php echo esc_attr($prioridad_config['color']); ?>; color: white;">
                        <?php echo esc_html($prioridad_config['label']); ?>
                    </span>
                    <?php if ($aviso->categoria_nombre): ?>
                    <span class="aviso-badge aviso-badge--outline" style="border-color: <?php echo esc_attr($aviso->categoria_color ?: '#6b7280'); ?>; color: <?php echo esc_attr($aviso->categoria_color ?: '#6b7280'); ?>">
                        <?php echo esc_html($aviso->categoria_nombre); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <h3 class="aviso-historial-titulo">
                    <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>">
                        <?php echo esc_html($aviso->titulo); ?>
                    </a>
                </h3>
                <p class="aviso-historial-extracto">
                    <?php echo esc_html(wp_trim_words($aviso->extracto ?: $aviso->contenido, 20)); ?>
                </p>
                <div class="aviso-historial-meta">
                    <?php if ($aviso->zona_nombre): ?>
                    <span>
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($aviso->zona_nombre); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($aviso->visualizaciones): ?>
                    <span>
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo number_format_i18n($aviso->visualizaciones); ?> <?php esc_html_e('vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aviso-historial-accion">
                <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>" class="btn btn-sm btn-outline">
                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1): ?>
    <nav class="avisos-paginacion">
        <div class="paginacion-info">
            <?php printf(
                esc_html__('Pagina %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $pagina_actual,
                $total_paginas
            ); ?>
        </div>
        <div class="paginacion-links">
            <?php if ($pagina_actual > 1): ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1)); ?>" class="paginacion-link">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <?php endif; ?>

            <?php
            $rango = 2;
            $inicio = max(1, $pagina_actual - $rango);
            $fin = min($total_paginas, $pagina_actual + $rango);

            if ($inicio > 1): ?>
            <a href="<?php echo esc_url(add_query_arg('pag', 1)); ?>" class="paginacion-numero">1</a>
            <?php if ($inicio > 2): ?>
            <span class="paginacion-ellipsis">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $i)); ?>"
               class="paginacion-numero <?php echo $i === $pagina_actual ? 'paginacion-numero--activo' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($fin < $total_paginas): ?>
            <?php if ($fin < $total_paginas - 1): ?>
            <span class="paginacion-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $total_paginas)); ?>" class="paginacion-numero"><?php echo $total_paginas; ?></a>
            <?php endif; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1)); ?>" class="paginacion-link">
                <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="avisos-empty">
        <span class="dashicons dashicons-archive"></span>
        <h3><?php esc_html_e('Sin resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('No se encontraron avisos en el historial con los criterios seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php if ($categoria_filtro || $anio_filtro || $buscar): ?>
        <a href="<?php echo esc_url($url_actual); ?>" class="btn btn-outline">
            <?php esc_html_e('Ver todo el historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.avisos-historial-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.avisos-historial-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.avisos-historial-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #1f2937;
}

.avisos-historial-header p {
    margin: 0.25rem 0 0;
    font-size: 0.9rem;
    color: #6b7280;
}

.avisos-historial-stats {
    display: flex;
    gap: 1rem;
}

.avisos-stat {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #6b7280;
}

.avisos-stat .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.avisos-filtros {
    padding: 1.25rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.avisos-filtros-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filtro-grupo {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.filtro-grupo label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
}

.filtro-grupo select,
.filtro-grupo input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    min-width: 120px;
}

.filtro-buscar {
    flex: 1;
    min-width: 180px;
}

.filtro-buscar input {
    width: 100%;
}

.filtro-acciones {
    display: flex;
    gap: 0.5rem;
    flex-direction: row;
}

/* Vista Timeline */
.avisos-timeline {
    position: relative;
    padding-left: 1rem;
}

.avisos-timeline::before {
    content: '';
    position: absolute;
    left: 70px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-grupo {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.timeline-fecha {
    width: 60px;
    text-align: center;
    flex-shrink: 0;
    position: relative;
}

.timeline-fecha::after {
    content: '';
    position: absolute;
    right: -1.5rem;
    top: 0.5rem;
    width: 12px;
    height: 12px;
    background: #3b82f6;
    border: 3px solid white;
    border-radius: 50%;
    box-shadow: 0 0 0 2px #e5e7eb;
    transform: translateX(50%);
}

.timeline-dia {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.timeline-mes {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
}

.timeline-contenido {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.timeline-item {
    background: white;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.timeline-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.timeline-item-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.timeline-prioridad {
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
}

.timeline-categoria {
    font-size: 0.75rem;
    font-weight: 500;
}

.timeline-hora {
    margin-left: auto;
    font-size: 0.75rem;
    color: #9ca3af;
}

.timeline-titulo {
    margin: 0 0 0.375rem;
    font-size: 1rem;
    font-weight: 600;
}

.timeline-titulo a {
    color: #1f2937;
    text-decoration: none;
}

.timeline-titulo a:hover {
    color: #3b82f6;
}

.timeline-extracto {
    margin: 0;
    font-size: 0.85rem;
    color: #6b7280;
    line-height: 1.5;
}

.timeline-zona {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

.timeline-zona .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

/* Vista Lista */
.avisos-historial-lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.aviso-historial-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.aviso-historial-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.aviso-historial-fecha {
    width: 60px;
    text-align: center;
    padding: 0.5rem;
    background: #f9fafb;
    border-radius: 8px;
    flex-shrink: 0;
}

.fecha-dia {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.fecha-mes {
    display: block;
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: uppercase;
}

.fecha-anio {
    display: block;
    font-size: 0.65rem;
    color: #9ca3af;
}

.aviso-historial-contenido {
    flex: 1;
    min-width: 0;
}

.aviso-historial-badges {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
    flex-wrap: wrap;
}

.aviso-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
}

.aviso-badge--outline {
    background: transparent;
    border: 1px solid;
}

.aviso-historial-titulo {
    margin: 0 0 0.375rem;
    font-size: 1rem;
    font-weight: 600;
}

.aviso-historial-titulo a {
    color: #1f2937;
    text-decoration: none;
}

.aviso-historial-titulo a:hover {
    color: #3b82f6;
}

.aviso-historial-extracto {
    margin: 0;
    font-size: 0.85rem;
    color: #6b7280;
    line-height: 1.5;
}

.aviso-historial-meta {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

.aviso-historial-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.aviso-historial-meta .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.aviso-historial-accion {
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

/* Paginacion */
.avisos-paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 1rem;
}

.paginacion-info {
    font-size: 0.875rem;
    color: #6b7280;
}

.paginacion-links {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.paginacion-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.paginacion-link:hover {
    background: #f3f4f6;
}

.paginacion-link .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.paginacion-numero {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.paginacion-numero:hover {
    background: #f3f4f6;
}

.paginacion-numero--activo {
    background: #3b82f6;
    color: white;
}

.paginacion-ellipsis {
    color: #9ca3af;
    padding: 0 0.25rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

.avisos-empty {
    text-align: center;
    padding: 3rem;
    background: #f9fafb;
    border-radius: 12px;
}

.avisos-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
    margin-bottom: 1rem;
}

.avisos-empty h3 {
    margin: 0 0 0.5rem;
    color: #374151;
}

.avisos-empty p {
    margin: 0 0 1.5rem;
    color: #6b7280;
}

@media (max-width: 640px) {
    .avisos-filtros-row {
        flex-direction: column;
    }

    .filtro-grupo {
        width: 100%;
    }

    .filtro-grupo select,
    .filtro-grupo input {
        width: 100%;
    }

    .avisos-timeline::before {
        left: 10px;
    }

    .timeline-grupo {
        flex-direction: column;
        gap: 0.75rem;
        padding-left: 1.5rem;
    }

    .timeline-fecha {
        width: auto;
        text-align: left;
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
    }

    .timeline-fecha::after {
        left: -1.5rem;
        right: auto;
        transform: none;
    }

    .aviso-historial-item {
        flex-direction: column;
    }

    .aviso-historial-fecha {
        width: auto;
        display: flex;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
    }

    .paginacion-links {
        width: 100%;
        justify-content: center;
    }
}
</style>
