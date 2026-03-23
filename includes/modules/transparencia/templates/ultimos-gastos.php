<?php
/**
 * Template: Ultimos Gastos Realizados
 *
 * Muestra los gastos mas recientes con opciones de filtrado.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_gastos = '';
$tablas_gastos_candidatas = [
    $prefijo_tabla . 'gastos',
    $prefijo_tabla . 'movimientos',
];
foreach ($tablas_gastos_candidatas as $tabla_candidata) {
    if (Flavor_Chat_Helpers::tabla_existe($tabla_candidata)) {
        $tabla_gastos = $tabla_candidata;
        break;
    }
}

// Verificar que la tabla existe
if ($tabla_gastos === '') {
    echo '<div class="transparencia-aviso transparencia-aviso--info">';
    echo '<span class="dashicons dashicons-info"></span>';
    echo '<p>' . esc_html__('Todavía no hay gastos publicados en esta instalación.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Obtener parametros
$limite = isset($atts['limite']) ? intval($atts['limite']) : 20;
$ejercicio_actual = date('Y');

// Filtros desde GET
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$mes_filtro = isset($_GET['mes']) ? intval($_GET['mes']) : '';
$importe_min = isset($_GET['importe_min']) ? floatval($_GET['importe_min']) : '';
$importe_max = isset($_GET['importe_max']) ? floatval($_GET['importe_max']) : '';
$busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$offset = ($pagina_actual - 1) * $limite;

// Construir consulta
$where_clauses = ["ejercicio = %d", "estado_pago != 'anulado'"];
$where_params = [$ejercicio_actual];

if ($categoria_filtro) {
    $where_clauses[] = "categoria = %s";
    $where_params[] = $categoria_filtro;
}

if ($mes_filtro) {
    $where_clauses[] = "MONTH(fecha_operacion) = %d";
    $where_params[] = $mes_filtro;
}

if ($importe_min !== '') {
    $where_clauses[] = "importe_total >= %f";
    $where_params[] = $importe_min;
}

if ($importe_max !== '') {
    $where_clauses[] = "importe_total <= %f";
    $where_params[] = $importe_max;
}

if ($busqueda) {
    $where_clauses[] = "(concepto LIKE %s OR proveedor LIKE %s)";
    $like_term = '%' . $wpdb->esc_like($busqueda) . '%';
    $where_params[] = $like_term;
    $where_params[] = $like_term;
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener total de resultados
$total_query = "SELECT COUNT(*) FROM $tabla_gastos WHERE $where_sql";
$total_gastos = $wpdb->get_var($wpdb->prepare($total_query, $where_params));
$total_paginas = ceil($total_gastos / $limite);

// Obtener gastos
$query = "SELECT * FROM $tabla_gastos WHERE $where_sql ORDER BY fecha_operacion DESC LIMIT %d OFFSET %d";
$query_params = array_merge($where_params, [$limite, $offset]);
$gastos = $wpdb->get_results($wpdb->prepare($query, $query_params));

// Obtener categorias disponibles
$categorias = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT categoria FROM $tabla_gastos
     WHERE ejercicio = %d AND categoria IS NOT NULL
     ORDER BY categoria",
    $ejercicio_actual
));

// Calcular estadisticas del periodo
$stats_periodo = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as total_operaciones,
        SUM(importe_total) as total_importe,
        AVG(importe_total) as promedio,
        MAX(importe_total) as maximo,
        MIN(importe_total) as minimo
     FROM $tabla_gastos
     WHERE ejercicio = %d AND estado_pago != 'anulado'",
    $ejercicio_actual
));

// Gastos por mes
$gastos_por_mes = $wpdb->get_results($wpdb->prepare(
    "SELECT
        MONTH(fecha_operacion) as mes,
        SUM(importe_total) as total
     FROM $tabla_gastos
     WHERE ejercicio = %d AND estado_pago != 'anulado'
     GROUP BY MONTH(fecha_operacion)
     ORDER BY mes",
    $ejercicio_actual
));

$meses_nombres = [
    1 => __('Enero', 'flavor-chat-ia'),
    2 => __('Febrero', 'flavor-chat-ia'),
    3 => __('Marzo', 'flavor-chat-ia'),
    4 => __('Abril', 'flavor-chat-ia'),
    5 => __('Mayo', 'flavor-chat-ia'),
    6 => __('Junio', 'flavor-chat-ia'),
    7 => __('Julio', 'flavor-chat-ia'),
    8 => __('Agosto', 'flavor-chat-ia'),
    9 => __('Septiembre', 'flavor-chat-ia'),
    10 => __('Octubre', 'flavor-chat-ia'),
    11 => __('Noviembre', 'flavor-chat-ia'),
    12 => __('Diciembre', 'flavor-chat-ia'),
];
?>

<div class="transparencia-gastos">
    <header class="transparencia-gastos__header">
        <div class="transparencia-gastos__titulo">
            <span class="dashicons dashicons-money-alt"></span>
            <h2><?php printf(esc_html__('Gastos %d', 'flavor-chat-ia'), $ejercicio_actual); ?></h2>
        </div>
        <p class="transparencia-gastos__descripcion">
            <?php esc_html_e('Detalle de los gastos realizados con informacion de proveedores e importes.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <!-- Estadisticas del periodo -->
    <?php if ($stats_periodo && $stats_periodo->total_operaciones > 0) : ?>
    <div class="transparencia-gastos__stats">
        <div class="transparencia-stat-mini">
            <span class="transparencia-stat-mini__valor"><?php echo esc_html(number_format($stats_periodo->total_operaciones)); ?></span>
            <span class="transparencia-stat-mini__label"><?php esc_html_e('Operaciones', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="transparencia-stat-mini">
            <span class="transparencia-stat-mini__valor"><?php echo esc_html(number_format($stats_periodo->total_importe, 0, ',', '.')); ?> &euro;</span>
            <span class="transparencia-stat-mini__label"><?php esc_html_e('Total gastado', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="transparencia-stat-mini">
            <span class="transparencia-stat-mini__valor"><?php echo esc_html(number_format($stats_periodo->promedio, 0, ',', '.')); ?> &euro;</span>
            <span class="transparencia-stat-mini__label"><?php esc_html_e('Promedio', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="transparencia-stat-mini">
            <span class="transparencia-stat-mini__valor"><?php echo esc_html(number_format($stats_periodo->maximo, 0, ',', '.')); ?> &euro;</span>
            <span class="transparencia-stat-mini__label"><?php esc_html_e('Mayor gasto', 'flavor-chat-ia'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="transparencia-filtros">
        <form class="transparencia-filtros__form" method="get">
            <div class="transparencia-filtros__grupo transparencia-filtros__grupo--busqueda">
                <label for="buscar"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></label>
                <input type="text" name="buscar" id="buscar" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php esc_attr_e('Concepto o proveedor...', 'flavor-chat-ia'); ?>">
            </div>
            <div class="transparencia-filtros__grupo">
                <label for="categoria"><?php esc_html_e('Categoria', 'flavor-chat-ia'); ?></label>
                <select name="categoria" id="categoria">
                    <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $cat) : ?>
                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($categoria_filtro, $cat); ?>>
                        <?php echo esc_html(ucfirst($cat)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="transparencia-filtros__grupo">
                <label for="mes"><?php esc_html_e('Mes', 'flavor-chat-ia'); ?></label>
                <select name="mes" id="mes">
                    <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                    <?php for ($mes = 1; $mes <= 12; $mes++) : ?>
                    <option value="<?php echo esc_attr($mes); ?>" <?php selected($mes_filtro, $mes); ?>>
                        <?php echo esc_html($meses_nombres[$mes]); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="transparencia-filtros__grupo transparencia-filtros__grupo--importe">
                <label><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></label>
                <div class="transparencia-filtros__rango">
                    <input type="number" name="importe_min" value="<?php echo esc_attr($importe_min); ?>" placeholder="<?php esc_attr_e('Min', 'flavor-chat-ia'); ?>" step="0.01">
                    <span>-</span>
                    <input type="number" name="importe_max" value="<?php echo esc_attr($importe_max); ?>" placeholder="<?php esc_attr_e('Max', 'flavor-chat-ia'); ?>" step="0.01">
                </div>
            </div>
            <button type="submit" class="transparencia-btn transparencia-btn--secondary">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>

    <!-- Contador de resultados -->
    <div class="transparencia-resultados-info">
        <?php printf(
            esc_html(_n('%d gasto encontrado', '%d gastos encontrados', $total_gastos, 'flavor-chat-ia')),
            $total_gastos
        ); ?>
    </div>

    <?php if (!empty($gastos)) : ?>
    <!-- Lista de gastos -->
    <div class="transparencia-gastos__lista">
        <?php foreach ($gastos as $gasto) :
            $estado_pago_clase = $gasto->estado_pago === 'pagado' ? 'pagado' : 'pendiente';
            $estado_pago_texto = $gasto->estado_pago === 'pagado' ? __('Pagado', 'flavor-chat-ia') : __('Pendiente', 'flavor-chat-ia');
        ?>
        <article class="transparencia-gasto-card">
            <div class="transparencia-gasto-card__icono">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="transparencia-gasto-card__contenido">
                <div class="transparencia-gasto-card__header">
                    <h4 class="transparencia-gasto-card__concepto"><?php echo esc_html(wp_trim_words($gasto->concepto, 15)); ?></h4>
                    <span class="transparencia-gasto-card__importe"><?php echo esc_html(number_format($gasto->importe_total, 2, ',', '.')); ?> &euro;</span>
                </div>
                <div class="transparencia-gasto-card__meta">
                    <?php if ($gasto->proveedor) : ?>
                    <span class="transparencia-gasto-card__proveedor">
                        <span class="dashicons dashicons-businessman"></span>
                        <?php echo esc_html($gasto->proveedor); ?>
                        <?php if ($gasto->proveedor_nif) : ?>
                        <small>(<?php echo esc_html($gasto->proveedor_nif); ?>)</small>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <span class="transparencia-gasto-card__fecha">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html(date_i18n('d M Y', strtotime($gasto->fecha_operacion))); ?>
                    </span>
                    <?php if ($gasto->categoria) : ?>
                    <span class="transparencia-gasto-card__categoria">
                        <span class="dashicons dashicons-category"></span>
                        <?php echo esc_html(ucfirst($gasto->categoria)); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if ($gasto->factura_numero) : ?>
                <div class="transparencia-gasto-card__factura">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php printf(esc_html__('Factura: %s', 'flavor-chat-ia'), esc_html($gasto->factura_numero)); ?>
                    <?php if ($gasto->factura_fecha) : ?>
                    <small>(<?php echo esc_html(date_i18n('d/m/Y', strtotime($gasto->factura_fecha))); ?>)</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="transparencia-gasto-card__estado">
                <span class="transparencia-badge transparencia-badge--<?php echo esc_attr($estado_pago_clase); ?>">
                    <?php echo esc_html($estado_pago_texto); ?>
                </span>
                <?php if ($gasto->documento_url) : ?>
                <a href="<?php echo esc_url($gasto->documento_url); ?>" class="transparencia-btn transparencia-btn--outline transparencia-btn--sm" target="_blank" title="<?php esc_attr_e('Ver documento', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-media-document"></span>
                </a>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1) : ?>
    <nav class="transparencia-paginacion">
        <?php if ($pagina_actual > 1) : ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1)); ?>" class="transparencia-paginacion__btn">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Anterior', 'flavor-chat-ia'); ?>
        </a>
        <?php endif; ?>

        <span class="transparencia-paginacion__info">
            <?php printf(
                esc_html__('Pagina %d de %d', 'flavor-chat-ia'),
                $pagina_actual,
                $total_paginas
            ); ?>
        </span>

        <?php if ($pagina_actual < $total_paginas) : ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1)); ?>" class="transparencia-paginacion__btn">
            <?php esc_html_e('Siguiente', 'flavor-chat-ia'); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php else : ?>
    <div class="transparencia-empty-state">
        <span class="dashicons dashicons-money-alt"></span>
        <h3><?php esc_html_e('No hay gastos registrados', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('No se encontraron gastos que coincidan con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.transparencia-gastos {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-gastos__header {
    margin-bottom: 2rem;
}

.transparencia-gastos__titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.transparencia-gastos__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
    color: var(--flavor-primary, #10b981);
}

.transparencia-gastos__titulo h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-gastos__descripcion {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
}

/* Estadisticas */
.transparencia-gastos__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.transparencia-stat-mini {
    background: var(--flavor-card-bg, #fff);
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-stat-mini__valor {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-stat-mini__label {
    display: block;
    font-size: 0.75rem;
    color: var(--flavor-text-light, #6b7280);
    margin-top: 0.25rem;
}

/* Filtros */
.transparencia-filtros {
    background: var(--flavor-card-bg, #fff);
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-filtros__form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.transparencia-filtros__grupo {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.transparencia-filtros__grupo--busqueda {
    flex: 1;
    min-width: 200px;
}

.transparencia-filtros__grupo label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-filtros__grupo select,
.transparencia-filtros__grupo input {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: 8px;
    font-size: 0.875rem;
    background-color: #fff;
}

.transparencia-filtros__rango {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transparencia-filtros__rango input {
    width: 90px;
}

.transparencia-filtros__rango span {
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-resultados-info {
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
    margin-bottom: 1rem;
}

/* Lista de gastos */
.transparencia-gastos__lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.transparencia-gasto-card {
    display: flex;
    gap: 1rem;
    background: var(--flavor-card-bg, #fff);
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}

.transparencia-gasto-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.transparencia-gasto-card__icono {
    width: 44px;
    height: 44px;
    background: var(--flavor-primary-light, #d1fae5);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.transparencia-gasto-card__icono .dashicons {
    color: var(--flavor-primary, #10b981);
    font-size: 1.25rem;
}

.transparencia-gasto-card__contenido {
    flex: 1;
    min-width: 0;
}

.transparencia-gasto-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.transparencia-gasto-card__concepto {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-gasto-card__importe {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
    white-space: nowrap;
}

.transparencia-gasto-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.transparencia-gasto-card__proveedor,
.transparencia-gasto-card__fecha,
.transparencia-gasto-card__categoria {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-gasto-card__proveedor small,
.transparencia-gasto-card__factura small {
    color: var(--flavor-text-light, #9ca3af);
}

.transparencia-gasto-card__meta .dashicons {
    font-size: 0.875rem;
    width: 0.875rem;
    height: 0.875rem;
}

.transparencia-gasto-card__factura {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--flavor-text-light, #9ca3af);
}

.transparencia-gasto-card__factura .dashicons {
    font-size: 0.875rem;
    width: 0.875rem;
    height: 0.875rem;
}

.transparencia-gasto-card__estado {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
    flex-shrink: 0;
}

/* Badges */
.transparencia-badge--pagado {
    background: #d1fae5;
    color: #047857;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.transparencia-badge--pendiente {
    background: #fef3c7;
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Buttons */
.transparencia-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.8125rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.transparencia-btn--secondary {
    background: var(--flavor-bg-light, #f3f4f6);
    color: var(--flavor-text, #374151);
}

.transparencia-btn--outline {
    background: transparent;
    color: var(--flavor-primary, #10b981);
    border: 1px solid var(--flavor-primary, #10b981);
}

.transparencia-btn--sm {
    padding: 0.375rem 0.625rem;
}

.transparencia-btn .dashicons {
    font-size: 0.875rem;
    width: 0.875rem;
    height: 0.875rem;
}

/* Paginacion */
.transparencia-paginacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-paginacion__btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: var(--flavor-card-bg, #fff);
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: 8px;
    color: var(--flavor-text, #374151);
    text-decoration: none;
    font-size: 0.875rem;
}

.transparencia-paginacion__btn:hover {
    border-color: var(--flavor-primary, #10b981);
    color: var(--flavor-primary, #10b981);
}

.transparencia-paginacion__info {
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Empty state */
.transparencia-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
}

.transparencia-empty-state .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: var(--flavor-text-light, #9ca3af);
    margin-bottom: 1rem;
}

.transparencia-empty-state h3 {
    margin: 0 0 0.5rem;
}

.transparencia-empty-state p {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
}

.transparencia-aviso {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 8px;
}

.transparencia-aviso--info {
    background: #eff6ff;
    color: #1e40af;
}

@media (max-width: 640px) {
    .transparencia-filtros__form {
        flex-direction: column;
    }

    .transparencia-filtros__grupo,
    .transparencia-filtros__grupo--busqueda {
        width: 100%;
    }

    .transparencia-gasto-card {
        flex-direction: column;
    }

    .transparencia-gasto-card__icono {
        display: none;
    }

    .transparencia-gasto-card__header {
        flex-direction: column;
        gap: 0.25rem;
    }

    .transparencia-gasto-card__estado {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--flavor-border, #e5e7eb);
    }

    .transparencia-gasto-card__meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
