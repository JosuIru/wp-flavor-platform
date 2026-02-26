<?php
/**
 * Template: Listado de Recursos Reservables
 *
 * Muestra los recursos disponibles para reservar
 *
 * @package FlavorChatIA
 * @subpackage Reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar que el módulo esté activo
$modulo_activo = class_exists('Flavor_Chat_Module_Loader')
    && Flavor_Chat_Module_Loader::get_instance()->is_module_active('reservas');

if (!$modulo_activo) {
    echo '<div class="flavor-notice flavor-notice-warning">';
    echo '<p>' . esc_html__('El módulo de reservas no está activo.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

// Verificar que existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_recursos)) {
    echo '<div class="flavor-notice flavor-notice-info">';
    echo '<p>' . esc_html__('No hay recursos configurados todavía.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Parámetros de filtrado
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
$items_por_pagina = 12;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Construir query
$where_condiciones = ["estado = 'activo'"];
$params_query = [];

if (!empty($filtro_tipo)) {
    $where_condiciones[] = "tipo = %s";
    $params_query[] = $filtro_tipo;
}

if (!empty($filtro_busqueda)) {
    $where_condiciones[] = "(nombre LIKE %s OR descripcion LIKE %s)";
    $like_pattern = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $params_query[] = $like_pattern;
    $params_query[] = $like_pattern;
}

$clausula_where = implode(' AND ', $where_condiciones);

// Contar total
$sql_count = "SELECT COUNT(*) FROM $tabla_recursos WHERE $clausula_where";
$total_recursos = !empty($params_query)
    ? $wpdb->get_var($wpdb->prepare($sql_count, ...$params_query))
    : $wpdb->get_var($sql_count);

$total_paginas = ceil($total_recursos / $items_por_pagina);

// Obtener recursos
$sql_recursos = "SELECT * FROM $tabla_recursos WHERE $clausula_where ORDER BY nombre ASC LIMIT %d OFFSET %d";
$params_query[] = $items_por_pagina;
$params_query[] = $offset;

$recursos = $wpdb->get_results($wpdb->prepare($sql_recursos, ...$params_query));

// Obtener tipos únicos para filtros
$tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_recursos WHERE estado = 'activo' ORDER BY tipo ASC");

// Enqueue styles
wp_enqueue_style('flavor-reservas');
wp_enqueue_script('flavor-reservas');
?>

<div class="flavor-reservas-archive">
    <!-- Header -->
    <header class="reservas-archive-header">
        <h1 class="page-title"><?php esc_html_e('Recursos Disponibles', 'flavor-chat-ia'); ?></h1>
        <p class="page-description"><?php esc_html_e('Explora nuestros recursos y realiza tu reserva', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Filtros -->
    <div class="reservas-filtros">
        <form method="get" class="filtros-form">
            <div class="filtros-row">
                <div class="filtro-item">
                    <select name="tipo" class="filtro-select" onchange="this.form.submit()">
                        <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($tipos_disponibles as $tipo) : ?>
                            <option value="<?php echo esc_attr($tipo); ?>" <?php selected($filtro_tipo, $tipo); ?>>
                                <?php echo esc_html(ucfirst($tipo)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-item filtro-busqueda">
                    <input type="text" name="s"
                           value="<?php echo esc_attr($filtro_busqueda); ?>"
                           placeholder="<?php esc_attr_e('Buscar recursos...', 'flavor-chat-ia'); ?>"
                           class="filtro-input">
                    <button type="submit" class="filtro-btn">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>

                <?php if (!empty($filtro_tipo) || !empty($filtro_busqueda)) : ?>
                    <a href="<?php echo esc_url(remove_query_arg(['tipo', 's', 'paged'])); ?>" class="filtro-limpiar">
                        <?php esc_html_e('Limpiar filtros', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Contador de resultados -->
    <div class="reservas-resultados-info">
        <?php
        printf(
            esc_html(_n('%d recurso encontrado', '%d recursos encontrados', $total_recursos, 'flavor-chat-ia')),
            $total_recursos
        );
        ?>
    </div>

    <!-- Grid de recursos -->
    <?php if (!empty($recursos)) : ?>
        <div class="recursos-grid">
            <?php foreach ($recursos as $recurso) : ?>
                <article class="recurso-card" data-id="<?php echo esc_attr($recurso->id); ?>">
                    <?php if (!empty($recurso->imagen)) : ?>
                        <div class="recurso-imagen">
                            <img src="<?php echo esc_url($recurso->imagen); ?>"
                                 alt="<?php echo esc_attr($recurso->nombre); ?>"
                                 loading="lazy">
                            <span class="recurso-tipo-badge"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
                        </div>
                    <?php else : ?>
                        <div class="recurso-imagen recurso-imagen-placeholder">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span class="recurso-tipo-badge"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="recurso-contenido">
                        <h3 class="recurso-titulo">
                            <a href="<?php echo esc_url(home_url('/reservas/' . $recurso->id . '/')); ?>">
                                <?php echo esc_html($recurso->nombre); ?>
                            </a>
                        </h3>

                        <?php if (!empty($recurso->ubicacion)) : ?>
                            <p class="recurso-ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($recurso->ubicacion); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($recurso->descripcion)) : ?>
                            <p class="recurso-descripcion">
                                <?php echo esc_html(wp_trim_words($recurso->descripcion, 20)); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($recurso->capacidad)) : ?>
                            <div class="recurso-meta">
                                <span class="meta-item">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(esc_html__('%d personas', 'flavor-chat-ia'), $recurso->capacidad); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="recurso-acciones">
                            <a href="<?php echo esc_url(home_url('/reservas/' . $recurso->id . '/')); ?>"
                               class="flavor-btn flavor-btn-outline flavor-btn-sm">
                                <?php esc_html_e('Ver Detalles', 'flavor-chat-ia'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/reservas/nueva/?recurso_id=' . $recurso->id)); ?>"
                               class="flavor-btn flavor-btn-primary flavor-btn-sm">
                                <?php esc_html_e('Reservar', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1) : ?>
            <nav class="reservas-paginacion">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $pagina_actual,
                    'total' => $total_paginas,
                    'prev_text' => '&laquo; ' . __('Anterior', 'flavor-chat-ia'),
                    'next_text' => __('Siguiente', 'flavor-chat-ia') . ' &raquo;',
                    'type' => 'list',
                ]);
                ?>
            </nav>
        <?php endif; ?>

    <?php else : ?>
        <div class="reservas-sin-resultados">
            <span class="dashicons dashicons-calendar-alt"></span>
            <h3><?php esc_html_e('No hay recursos disponibles', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('No se encontraron recursos con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
            <?php if (!empty($filtro_tipo) || !empty($filtro_busqueda)) : ?>
                <a href="<?php echo esc_url(remove_query_arg(['tipo', 's', 'paged'])); ?>" class="flavor-btn flavor-btn-outline">
                    <?php esc_html_e('Ver todos los recursos', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.flavor-reservas-archive {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--flavor-spacing-lg, 2rem);
}

.reservas-archive-header {
    text-align: center;
    margin-bottom: var(--flavor-spacing-xl, 3rem);
}

.reservas-archive-header .page-title {
    font-size: var(--flavor-font-size-3xl, 2rem);
    font-weight: var(--flavor-font-weight-bold, 700);
    color: var(--flavor-text-primary, #1a1a1a);
    margin-bottom: var(--flavor-spacing-sm, 0.5rem);
}

.reservas-archive-header .page-description {
    font-size: var(--flavor-font-size-lg, 1.125rem);
    color: var(--flavor-text-secondary, #666);
}

.reservas-filtros {
    background: var(--flavor-bg-secondary, #f8f9fa);
    padding: var(--flavor-spacing-md, 1rem);
    border-radius: var(--flavor-radius-lg, 12px);
    margin-bottom: var(--flavor-spacing-lg, 2rem);
}

.filtros-row {
    display: flex;
    flex-wrap: wrap;
    gap: var(--flavor-spacing-md, 1rem);
    align-items: center;
}

.filtro-item {
    flex: 0 0 auto;
}

.filtro-busqueda {
    flex: 1;
    min-width: 200px;
    display: flex;
    gap: 0;
}

.filtro-select,
.filtro-input {
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    border: 1px solid var(--flavor-border-color, #ddd);
    border-radius: var(--flavor-radius-md, 8px);
    font-size: var(--flavor-font-size-base, 1rem);
    background: var(--flavor-bg-primary, #fff);
}

.filtro-input {
    flex: 1;
    border-right: none;
    border-radius: var(--flavor-radius-md, 8px) 0 0 var(--flavor-radius-md, 8px);
}

.filtro-btn {
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    background: var(--flavor-color-primary, #3b82f6);
    color: #fff;
    border: none;
    border-radius: 0 var(--flavor-radius-md, 8px) var(--flavor-radius-md, 8px) 0;
    cursor: pointer;
    transition: background 0.2s;
}

.filtro-btn:hover {
    background: var(--flavor-color-primary-dark, #2563eb);
}

.filtro-limpiar {
    color: var(--flavor-color-danger, #dc2626);
    font-size: var(--flavor-font-size-sm, 0.875rem);
    text-decoration: none;
}

.filtro-limpiar:hover {
    text-decoration: underline;
}

.reservas-resultados-info {
    color: var(--flavor-text-secondary, #666);
    margin-bottom: var(--flavor-spacing-md, 1rem);
    font-size: var(--flavor-font-size-sm, 0.875rem);
}

.recursos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--flavor-spacing-lg, 2rem);
}

.recurso-card {
    background: var(--flavor-bg-primary, #fff);
    border-radius: var(--flavor-radius-lg, 12px);
    overflow: hidden;
    box-shadow: var(--flavor-shadow-md, 0 4px 6px rgba(0,0,0,0.1));
    transition: transform 0.2s, box-shadow 0.2s;
}

.recurso-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--flavor-shadow-lg, 0 10px 25px rgba(0,0,0,0.15));
}

.recurso-imagen {
    position: relative;
    height: 180px;
    overflow: hidden;
    background: var(--flavor-bg-tertiary, #e5e7eb);
}

.recurso-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.recurso-imagen-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
}

.recurso-imagen-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--flavor-text-muted, #9ca3af);
}

.recurso-tipo-badge {
    position: absolute;
    top: var(--flavor-spacing-sm, 0.5rem);
    left: var(--flavor-spacing-sm, 0.5rem);
    background: var(--flavor-color-primary, #3b82f6);
    color: #fff;
    padding: 4px 12px;
    border-radius: var(--flavor-radius-full, 9999px);
    font-size: var(--flavor-font-size-xs, 0.75rem);
    font-weight: var(--flavor-font-weight-medium, 500);
    text-transform: uppercase;
}

.recurso-contenido {
    padding: var(--flavor-spacing-md, 1rem);
}

.recurso-titulo {
    font-size: var(--flavor-font-size-lg, 1.125rem);
    font-weight: var(--flavor-font-weight-semibold, 600);
    margin: 0 0 var(--flavor-spacing-xs, 0.25rem);
}

.recurso-titulo a {
    color: var(--flavor-text-primary, #1a1a1a);
    text-decoration: none;
}

.recurso-titulo a:hover {
    color: var(--flavor-color-primary, #3b82f6);
}

.recurso-ubicacion {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--flavor-text-secondary, #666);
    font-size: var(--flavor-font-size-sm, 0.875rem);
    margin-bottom: var(--flavor-spacing-sm, 0.5rem);
}

.recurso-ubicacion .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.recurso-descripcion {
    color: var(--flavor-text-secondary, #666);
    font-size: var(--flavor-font-size-sm, 0.875rem);
    line-height: 1.5;
    margin-bottom: var(--flavor-spacing-sm, 0.5rem);
}

.recurso-meta {
    display: flex;
    gap: var(--flavor-spacing-md, 1rem);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--flavor-text-muted, #9ca3af);
    font-size: var(--flavor-font-size-sm, 0.875rem);
}

.meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.recurso-acciones {
    display: flex;
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.recurso-acciones .flavor-btn {
    flex: 1;
    text-align: center;
}

.reservas-paginacion {
    margin-top: var(--flavor-spacing-xl, 3rem);
    display: flex;
    justify-content: center;
}

.reservas-paginacion ul {
    display: flex;
    gap: var(--flavor-spacing-xs, 0.25rem);
    list-style: none;
    padding: 0;
    margin: 0;
}

.reservas-paginacion .page-numbers {
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    border-radius: var(--flavor-radius-md, 8px);
    background: var(--flavor-bg-secondary, #f8f9fa);
    color: var(--flavor-text-primary, #1a1a1a);
    text-decoration: none;
    transition: background 0.2s;
}

.reservas-paginacion .page-numbers:hover,
.reservas-paginacion .page-numbers.current {
    background: var(--flavor-color-primary, #3b82f6);
    color: #fff;
}

.reservas-sin-resultados {
    text-align: center;
    padding: var(--flavor-spacing-3xl, 4rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-lg, 12px);
}

.reservas-sin-resultados .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--flavor-text-muted, #9ca3af);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.reservas-sin-resultados h3 {
    font-size: var(--flavor-font-size-xl, 1.25rem);
    color: var(--flavor-text-primary, #1a1a1a);
    margin-bottom: var(--flavor-spacing-sm, 0.5rem);
}

.reservas-sin-resultados p {
    color: var(--flavor-text-secondary, #666);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

@media (max-width: 768px) {
    .flavor-reservas-archive {
        padding: var(--flavor-spacing-md, 1rem);
    }

    .filtros-row {
        flex-direction: column;
    }

    .filtro-item,
    .filtro-busqueda {
        width: 100%;
    }

    .filtro-select {
        width: 100%;
    }

    .recursos-grid {
        grid-template-columns: 1fr;
    }

    .recurso-acciones {
        flex-direction: column;
    }
}
</style>
