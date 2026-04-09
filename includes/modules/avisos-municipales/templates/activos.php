<?php
/**
 * Template: Lista de Avisos Activos
 *
 * Muestra los avisos municipales activos actualmente.
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
$limite = isset($atts['limite']) ? intval($atts['limite']) : 20;
$mostrar_filtros = isset($atts['filtros']) ? ($atts['filtros'] === 'true') : true;
$columnas = isset($atts['columnas']) ? intval($atts['columnas']) : 2;

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$zona_filtro = isset($_GET['zona']) ? intval($_GET['zona']) : 0;
$prioridad_filtro = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Construir query
$where = ['a.publicado = 1', 'a.fecha_inicio <= NOW()', '(a.fecha_fin IS NULL OR a.fecha_fin >= NOW())'];
$params = [];

if ($categoria_filtro) {
    $where[] = 'a.categoria_id = %d';
    $params[] = $categoria_filtro;
}

if ($zona_filtro) {
    $where[] = '(a.zona_id = %d OR a.zona_id IS NULL)';
    $params[] = $zona_filtro;
}

if ($prioridad_filtro) {
    $where[] = 'a.prioridad = %s';
    $params[] = $prioridad_filtro;
}

if ($buscar) {
    $where[] = '(a.titulo LIKE %s OR a.contenido LIKE %s)';
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

$where_sql = implode(' AND ', $where);
$params[] = $limite;

$avisos_activos = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, c.nombre AS categoria_nombre, c.icono AS categoria_icono, c.color AS categoria_color, z.nombre AS zona_nombre
     FROM $tabla_avisos a
     LEFT JOIN $tabla_categorias c ON a.categoria_id = c.id
     LEFT JOIN $tabla_zonas z ON a.zona_id = z.id
     WHERE $where_sql
     ORDER BY a.destacado DESC, a.prioridad DESC, a.fecha_inicio DESC
     LIMIT %d",
    $params
));

// Obtener categorias y zonas para filtros
$categorias_disponibles = $wpdb->get_results("SELECT * FROM $tabla_categorias WHERE activa = 1 ORDER BY orden ASC, nombre ASC");
$zonas_disponibles = $wpdb->get_results("SELECT * FROM $tabla_zonas WHERE activa = 1 ORDER BY tipo ASC, nombre ASC");

// Labels de prioridades
$prioridades_config = [
    'urgente' => ['label' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#dc2626', 'icon' => 'warning'],
    'alta'    => ['label' => __('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f97316', 'icon' => 'flag'],
    'media'   => ['label' => __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#eab308', 'icon' => 'info'],
    'baja'    => ['label' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#22c55e', 'icon' => 'yes-alt'],
];

// URL base para los detalles
$avisos_base_url = Flavor_Chat_Helpers::get_action_url('avisos_municipales', '');
$usuario_id = get_current_user_id();
?>

<div class="avisos-activos-wrapper">
    <header class="avisos-activos-header">
        <div>
            <h2><?php esc_html_e('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Informacion oficial y comunicados del ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <?php if ($avisos_activos): ?>
        <span class="avisos-activos-total">
            <?php printf(esc_html__('%d avisos activos', FLAVOR_PLATFORM_TEXT_DOMAIN), count($avisos_activos)); ?>
        </span>
        <?php endif; ?>
    </header>

    <?php if ($mostrar_filtros): ?>
    <form class="avisos-filtros" method="get">
        <div class="avisos-filtros-row">
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
            <div class="filtro-grupo">
                <label for="zona"><?php esc_html_e('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="zona" id="zona">
                    <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($zonas_disponibles as $zona): ?>
                    <option value="<?php echo esc_attr($zona->id); ?>" <?php selected($zona_filtro, $zona->id); ?>>
                        <?php echo esc_html($zona->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="prioridad"><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="prioridad" id="prioridad">
                    <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($prioridades_config as $prioridad_key => $prioridad_data): ?>
                    <option value="<?php echo esc_attr($prioridad_key); ?>" <?php selected($prioridad_filtro, $prioridad_key); ?>>
                        <?php echo esc_html($prioridad_data['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-grupo filtro-buscar">
                <label for="buscar"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="buscar" id="buscar" value="<?php echo esc_attr($buscar); ?>"
                       placeholder="<?php esc_attr_e('Buscar avisos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
            <div class="filtro-grupo filtro-acciones">
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <?php if ($categoria_filtro || $zona_filtro || $prioridad_filtro || $buscar): ?>
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="btn btn-outline">
                    <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($avisos_activos): ?>
    <div class="avisos-activos-grid" data-columnas="<?php echo esc_attr($columnas); ?>">
        <?php foreach ($avisos_activos as $aviso):
            $prioridad_config = $prioridades_config[$aviso->prioridad] ?? $prioridades_config['media'];
            $es_destacado = !empty($aviso->destacado);
            $es_nuevo = (strtotime($aviso->fecha_inicio) > strtotime('-3 days'));
        ?>
        <article class="aviso-activo-card <?php echo $es_destacado ? 'aviso-activo-card--destacado' : ''; ?> <?php echo $aviso->prioridad === 'urgente' ? 'aviso-activo-card--urgente' : ''; ?>">
            <?php if ($es_destacado): ?>
            <div class="aviso-destacado-ribbon">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Destacado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
            <?php endif; ?>

            <header class="aviso-activo-header">
                <div class="aviso-badges">
                    <?php if ($es_nuevo): ?>
                    <span class="aviso-badge aviso-badge--nuevo">
                        <?php esc_html_e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <?php endif; ?>
                    <span class="aviso-badge aviso-badge--prioridad" style="background: <?php echo esc_attr($prioridad_config['color']); ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr($prioridad_config['icon']); ?>"></span>
                        <?php echo esc_html($prioridad_config['label']); ?>
                    </span>
                    <?php if ($aviso->categoria_nombre): ?>
                    <span class="aviso-badge aviso-badge--categoria" style="background: <?php echo esc_attr($aviso->categoria_color ?: '#6b7280'); ?>">
                        <?php echo esc_html($aviso->categoria_nombre); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </header>

            <div class="aviso-activo-body">
                <h3 class="aviso-activo-titulo">
                    <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>">
                        <?php echo esc_html($aviso->titulo); ?>
                    </a>
                </h3>
                <p class="aviso-activo-extracto">
                    <?php echo esc_html(wp_trim_words($aviso->extracto ?: $aviso->contenido, 20)); ?>
                </p>
            </div>

            <footer class="aviso-activo-footer">
                <div class="aviso-activo-meta">
                    <span class="aviso-meta-fecha">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php echo date_i18n('j M Y', strtotime($aviso->fecha_inicio)); ?>
                    </span>
                    <?php if ($aviso->zona_nombre): ?>
                    <span class="aviso-meta-zona">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($aviso->zona_nombre); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>" class="aviso-ver-mas">
                    <?php esc_html_e('Ver mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </footer>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="avisos-empty">
        <span class="dashicons dashicons-megaphone"></span>
        <h3><?php esc_html_e('No hay avisos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('No se encontraron avisos con los criterios seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php if ($categoria_filtro || $zona_filtro || $prioridad_filtro || $buscar): ?>
        <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="btn btn-outline">
            <?php esc_html_e('Ver todos los avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.avisos-activos-wrapper {
    max-width: 1100px;
    margin: 0 auto;
}

.avisos-activos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.avisos-activos-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #1f2937;
}

.avisos-activos-header p {
    margin: 0.25rem 0 0;
    font-size: 0.9rem;
    color: #6b7280;
}

.avisos-activos-total {
    padding: 0.5rem 1rem;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
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
    letter-spacing: 0.025em;
}

.filtro-grupo select,
.filtro-grupo input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    min-width: 150px;
}

.filtro-buscar {
    flex: 1;
    min-width: 200px;
}

.filtro-buscar input {
    width: 100%;
}

.filtro-acciones {
    display: flex;
    gap: 0.5rem;
    flex-direction: row;
}

.avisos-activos-grid {
    display: grid;
    gap: 1.25rem;
}

.avisos-activos-grid[data-columnas="1"] {
    grid-template-columns: 1fr;
}

.avisos-activos-grid[data-columnas="2"] {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
}

.avisos-activos-grid[data-columnas="3"] {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.aviso-activo-card {
    position: relative;
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
}

.aviso-activo-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.aviso-activo-card--destacado {
    border: 2px solid #f59e0b;
}

.aviso-activo-card--urgente {
    border-left: 4px solid #dc2626;
}

.aviso-destacado-ribbon {
    position: absolute;
    top: -1px;
    right: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    background: #f59e0b;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 0 0 6px 6px;
}

.aviso-destacado-ribbon .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.aviso-activo-header {
    margin-bottom: 0.75rem;
}

.aviso-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.aviso-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.aviso-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.aviso-badge--nuevo {
    background: #dbeafe;
    color: #1d4ed8;
}

.aviso-badge--prioridad {
    color: white;
}

.aviso-badge--categoria {
    color: white;
}

.aviso-activo-body {
    flex: 1;
}

.aviso-activo-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.05rem;
    font-weight: 600;
    line-height: 1.4;
}

.aviso-activo-titulo a {
    color: #1f2937;
    text-decoration: none;
}

.aviso-activo-titulo a:hover {
    color: #3b82f6;
}

.aviso-activo-extracto {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

.aviso-activo-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.aviso-activo-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.8rem;
    color: #9ca3af;
}

.aviso-activo-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.aviso-activo-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.aviso-ver-mas {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #3b82f6;
    text-decoration: none;
}

.aviso-ver-mas:hover {
    color: #1d4ed8;
}

.aviso-ver-mas .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
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
    border-color: #9ca3af;
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
    font-size: 1.25rem;
}

.avisos-empty p {
    margin: 0 0 1.5rem;
    color: #6b7280;
    font-size: 0.9rem;
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

    .filtro-acciones {
        width: 100%;
    }

    .filtro-acciones .btn {
        flex: 1;
        justify-content: center;
    }

    .aviso-activo-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
}
</style>
