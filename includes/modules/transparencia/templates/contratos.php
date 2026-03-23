<?php
/**
 * Template: Lista de Contratos Publicos
 *
 * Muestra los contratos publicos con filtros y opciones de descarga.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_documentos = '';
$tablas_documentos_candidatas = [
    $prefijo_tabla . 'documentos_publicos',
    $prefijo_tabla . 'documentos',
];
foreach ($tablas_documentos_candidatas as $tabla_candidata) {
    if (Flavor_Chat_Helpers::tabla_existe($tabla_candidata)) {
        $tabla_documentos = $tabla_candidata;
        break;
    }
}

// Verificar que la tabla existe
if ($tabla_documentos === '') {
    echo '<div class="transparencia-aviso transparencia-aviso--info">';
    echo '<span class="dashicons dashicons-info"></span>';
    echo '<p>' . esc_html__('Todavía no hay contratos publicados en esta instalación.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Obtener parametros de filtro
$limite = isset($atts['limite']) ? intval($atts['limite']) : 20;
$tipo_contrato_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$anio_filtro = isset($_GET['anio']) ? intval($_GET['anio']) : '';
$entidad_filtro = isset($_GET['entidad']) ? sanitize_text_field($_GET['entidad']) : '';
$busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$offset = ($pagina_actual - 1) * $limite;

// Construir consulta para contratos (documentos con categoria = 'contratos')
$where_clauses = ["categoria = 'contratos'", "estado = 'publicado'"];
$where_params = [];

if ($tipo_contrato_filtro) {
    $where_clauses[] = "subcategoria = %s";
    $where_params[] = $tipo_contrato_filtro;
}

if ($anio_filtro) {
    $where_clauses[] = "YEAR(fecha_documento) = %d";
    $where_params[] = $anio_filtro;
}

if ($entidad_filtro) {
    $where_clauses[] = "entidad = %s";
    $where_params[] = $entidad_filtro;
}

if ($busqueda) {
    $where_clauses[] = "(titulo LIKE %s OR descripcion LIKE %s)";
    $like_term = '%' . $wpdb->esc_like($busqueda) . '%';
    $where_params[] = $like_term;
    $where_params[] = $like_term;
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener total de resultados
$total_query = "SELECT COUNT(*) FROM $tabla_documentos WHERE $where_sql";
$total_contratos = $wpdb->get_var($where_params ? $wpdb->prepare($total_query, $where_params) : $total_query);
$total_paginas = ceil($total_contratos / $limite);

// Obtener contratos
$query = "SELECT * FROM $tabla_documentos WHERE $where_sql ORDER BY fecha_documento DESC LIMIT %d OFFSET %d";
$query_params = array_merge($where_params, [$limite, $offset]);
$contratos = $wpdb->get_results($wpdb->prepare($query, $query_params));

// Obtener tipos de contrato disponibles
$tipos_contrato = $wpdb->get_col(
    "SELECT DISTINCT subcategoria FROM $tabla_documentos
     WHERE categoria = 'contratos' AND estado = 'publicado' AND subcategoria IS NOT NULL
     ORDER BY subcategoria"
);

// Obtener anios disponibles
$anios_disponibles = $wpdb->get_col(
    "SELECT DISTINCT YEAR(fecha_documento) as anio FROM $tabla_documentos
     WHERE categoria = 'contratos' AND estado = 'publicado'
     ORDER BY anio DESC"
);

// Obtener entidades disponibles
$entidades_disponibles = $wpdb->get_col(
    "SELECT DISTINCT entidad FROM $tabla_documentos
     WHERE categoria = 'contratos' AND estado = 'publicado' AND entidad IS NOT NULL
     ORDER BY entidad"
);

// Nombres legibles de tipos de contrato
$tipos_contrato_nombres = [
    'obra' => __('Contrato de Obra', 'flavor-chat-ia'),
    'servicio' => __('Contrato de Servicio', 'flavor-chat-ia'),
    'suministro' => __('Contrato de Suministro', 'flavor-chat-ia'),
    'consultoria' => __('Consultoria', 'flavor-chat-ia'),
    'concesion' => __('Concesion', 'flavor-chat-ia'),
    'administrativo' => __('Contrato Administrativo', 'flavor-chat-ia'),
    'menor' => __('Contrato Menor', 'flavor-chat-ia'),
];

// Calcular estadisticas
$estadisticas_importes = $wpdb->get_row(
    "SELECT SUM(importe) as total, AVG(importe) as promedio, COUNT(*) as cantidad
     FROM $tabla_documentos
     WHERE categoria = 'contratos' AND estado = 'publicado' AND importe > 0"
);
?>

<div class="transparencia-contratos">
    <header class="transparencia-contratos__header">
        <div class="transparencia-contratos__titulo">
            <span class="dashicons dashicons-media-document"></span>
            <h2><?php esc_html_e('Contratos Publicos', 'flavor-chat-ia'); ?></h2>
        </div>
        <p class="transparencia-contratos__descripcion">
            <?php esc_html_e('Consulta los contratos formalizados, su importe y documentacion asociada.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <!-- Estadisticas resumen -->
    <?php if ($estadisticas_importes && $estadisticas_importes->cantidad > 0) : ?>
    <div class="transparencia-contratos__stats">
        <div class="transparencia-stat-card">
            <span class="transparencia-stat-card__icono dashicons dashicons-portfolio"></span>
            <div class="transparencia-stat-card__contenido">
                <span class="transparencia-stat-card__valor"><?php echo esc_html(number_format($estadisticas_importes->cantidad)); ?></span>
                <span class="transparencia-stat-card__etiqueta"><?php esc_html_e('Contratos publicados', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="transparencia-stat-card">
            <span class="transparencia-stat-card__icono dashicons dashicons-chart-bar"></span>
            <div class="transparencia-stat-card__contenido">
                <span class="transparencia-stat-card__valor"><?php echo esc_html(number_format($estadisticas_importes->total, 2, ',', '.')); ?> &euro;</span>
                <span class="transparencia-stat-card__etiqueta"><?php esc_html_e('Importe total', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="transparencia-stat-card">
            <span class="transparencia-stat-card__icono dashicons dashicons-calculator"></span>
            <div class="transparencia-stat-card__contenido">
                <span class="transparencia-stat-card__valor"><?php echo esc_html(number_format($estadisticas_importes->promedio, 2, ',', '.')); ?> &euro;</span>
                <span class="transparencia-stat-card__etiqueta"><?php esc_html_e('Importe medio', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="transparencia-filtros">
        <form class="transparencia-filtros__form" method="get">
            <div class="transparencia-filtros__grupo transparencia-filtros__grupo--busqueda">
                <label for="buscar"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></label>
                <input type="text" name="buscar" id="buscar" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php esc_attr_e('Buscar contrato...', 'flavor-chat-ia'); ?>">
            </div>
            <div class="transparencia-filtros__grupo">
                <label for="tipo"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label>
                <select name="tipo" id="tipo">
                    <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_contrato as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo); ?>" <?php selected($tipo_contrato_filtro, $tipo); ?>>
                        <?php echo esc_html($tipos_contrato_nombres[$tipo] ?? ucfirst($tipo)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="transparencia-filtros__grupo">
                <label for="anio"><?php esc_html_e('Ano', 'flavor-chat-ia'); ?></label>
                <select name="anio" id="anio">
                    <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($anios_disponibles as $anio) : ?>
                    <option value="<?php echo esc_attr($anio); ?>" <?php selected($anio_filtro, $anio); ?>>
                        <?php echo esc_html($anio); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($entidades_disponibles)) : ?>
            <div class="transparencia-filtros__grupo">
                <label for="entidad"><?php esc_html_e('Entidad', 'flavor-chat-ia'); ?></label>
                <select name="entidad" id="entidad">
                    <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($entidades_disponibles as $entidad) : ?>
                    <option value="<?php echo esc_attr($entidad); ?>" <?php selected($entidad_filtro, $entidad); ?>>
                        <?php echo esc_html($entidad); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="transparencia-btn transparencia-btn--secondary">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>

    <!-- Contador de resultados -->
    <div class="transparencia-resultados-info">
        <?php printf(
            esc_html(_n('%d contrato encontrado', '%d contratos encontrados', $total_contratos, 'flavor-chat-ia')),
            $total_contratos
        ); ?>
    </div>

    <?php if (!empty($contratos)) : ?>
    <!-- Tabla de contratos -->
    <div class="transparencia-tabla-wrapper">
        <table class="transparencia-tabla transparencia-contratos__tabla">
            <thead>
                <tr>
                    <th><?php esc_html_e('Contrato', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Adjudicatario', 'flavor-chat-ia'); ?></th>
                    <th class="text-right"><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th class="text-center"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contratos as $contrato) :
                    $metadatos = $contrato->metadatos ? json_decode($contrato->metadatos, true) : [];
                    $adjudicatario = $metadatos['adjudicatario'] ?? $contrato->entidad ?? '-';
                    $tipo_nombre = $tipos_contrato_nombres[$contrato->subcategoria] ?? ucfirst($contrato->subcategoria ?? 'General');
                ?>
                <tr>
                    <td>
                        <div class="transparencia-contrato-titulo">
                            <strong><?php echo esc_html($contrato->titulo); ?></strong>
                            <?php if ($contrato->descripcion) : ?>
                            <span class="transparencia-contrato-desc"><?php echo esc_html(wp_trim_words($contrato->descripcion, 15)); ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span class="transparencia-badge transparencia-badge--tipo">
                            <?php echo esc_html($tipo_nombre); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($adjudicatario); ?></td>
                    <td class="text-right transparencia-importe">
                        <?php if ($contrato->importe) : ?>
                        <?php echo esc_html(number_format($contrato->importe, 2, ',', '.')); ?> &euro;
                        <?php else : ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $contrato->fecha_documento ? esc_html(date_i18n('d/m/Y', strtotime($contrato->fecha_documento))) : '-'; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($contrato->archivo_url) : ?>
                        <a href="<?php echo esc_url($contrato->archivo_url); ?>" class="transparencia-btn transparencia-btn--sm transparencia-btn--outline" target="_blank" title="<?php esc_attr_e('Descargar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                        <?php else : ?>
                        <span class="transparencia-no-archivo">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Vista movil en cards -->
    <div class="transparencia-contratos__cards">
        <?php foreach ($contratos as $contrato) :
            $metadatos = $contrato->metadatos ? json_decode($contrato->metadatos, true) : [];
            $adjudicatario = $metadatos['adjudicatario'] ?? $contrato->entidad ?? '-';
            $tipo_nombre = $tipos_contrato_nombres[$contrato->subcategoria] ?? ucfirst($contrato->subcategoria ?? 'General');
        ?>
        <article class="transparencia-contrato-card">
            <header class="transparencia-contrato-card__header">
                <span class="transparencia-badge transparencia-badge--tipo"><?php echo esc_html($tipo_nombre); ?></span>
                <?php if ($contrato->importe) : ?>
                <span class="transparencia-contrato-card__importe">
                    <?php echo esc_html(number_format($contrato->importe, 2, ',', '.')); ?> &euro;
                </span>
                <?php endif; ?>
            </header>
            <div class="transparencia-contrato-card__contenido">
                <h3><?php echo esc_html($contrato->titulo); ?></h3>
                <?php if ($contrato->descripcion) : ?>
                <p><?php echo esc_html(wp_trim_words($contrato->descripcion, 20)); ?></p>
                <?php endif; ?>
                <div class="transparencia-contrato-card__meta">
                    <span>
                        <span class="dashicons dashicons-businessman"></span>
                        <?php echo esc_html($adjudicatario); ?>
                    </span>
                    <?php if ($contrato->fecha_documento) : ?>
                    <span>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($contrato->fecha_documento))); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($contrato->archivo_url) : ?>
            <footer class="transparencia-contrato-card__footer">
                <a href="<?php echo esc_url($contrato->archivo_url); ?>" class="transparencia-btn transparencia-btn--primary transparencia-btn--sm" target="_blank">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Descargar', 'flavor-chat-ia'); ?>
                </a>
            </footer>
            <?php endif; ?>
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
        <span class="dashicons dashicons-media-document"></span>
        <h3><?php esc_html_e('No hay contratos disponibles', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('No se encontraron contratos que coincidan con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.transparencia-contratos {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-contratos__header {
    margin-bottom: 2rem;
}

.transparencia-contratos__titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.transparencia-contratos__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
    color: var(--flavor-primary, #8b5cf6);
}

.transparencia-contratos__titulo h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-contratos__descripcion {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
}

/* Estadisticas */
.transparencia-contratos__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.transparencia-stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--flavor-card-bg, #fff);
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-stat-card__icono {
    width: 44px;
    height: 44px;
    background: var(--flavor-primary-light, #f5f3ff);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--flavor-primary, #8b5cf6);
    font-size: 1.25rem;
}

.transparencia-stat-card__valor {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-stat-card__etiqueta {
    display: block;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
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

.transparencia-filtros__grupo select {
    min-width: 150px;
}

/* Tabla */
.transparencia-tabla-wrapper {
    overflow-x: auto;
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-tabla {
    width: 100%;
    border-collapse: collapse;
}

.transparencia-tabla th,
.transparencia-tabla td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-tabla th {
    background: var(--flavor-bg-light, #f9fafb);
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.transparencia-tabla tbody tr:hover {
    background: var(--flavor-bg-light, #f9fafb);
}

.transparencia-tabla .text-right {
    text-align: right;
}

.transparencia-tabla .text-center {
    text-align: center;
}

.transparencia-contrato-titulo strong {
    display: block;
    color: var(--flavor-text, #1f2937);
    margin-bottom: 0.25rem;
}

.transparencia-contrato-desc {
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-importe {
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
    white-space: nowrap;
}

.transparencia-badge--tipo {
    background: var(--flavor-primary-light, #f5f3ff);
    color: var(--flavor-primary, #8b5cf6);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

/* Cards para movil */
.transparencia-contratos__cards {
    display: none;
    flex-direction: column;
    gap: 1rem;
}

.transparencia-contrato-card {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.transparencia-contrato-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-contrato-card__importe {
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-contrato-card__contenido {
    padding: 1rem;
}

.transparencia-contrato-card__contenido h3 {
    margin: 0 0 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-contrato-card__contenido p {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-contrato-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.transparencia-contrato-card__meta span {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-contrato-card__meta .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.transparencia-contrato-card__footer {
    padding: 1rem;
    border-top: 1px solid var(--flavor-border, #e5e7eb);
}

/* Buttons */
.transparencia-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.transparencia-btn--primary {
    background: var(--flavor-primary, #8b5cf6);
    color: #fff;
}

.transparencia-btn--primary:hover {
    background: var(--flavor-primary-dark, #7c3aed);
}

.transparencia-btn--secondary {
    background: var(--flavor-bg-light, #f3f4f6);
    color: var(--flavor-text, #374151);
}

.transparencia-btn--outline {
    background: transparent;
    color: var(--flavor-primary, #8b5cf6);
    border: 1px solid var(--flavor-primary, #8b5cf6);
}

.transparencia-btn--sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
}

.transparencia-btn .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
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
    border-color: var(--flavor-primary, #8b5cf6);
    color: var(--flavor-primary, #8b5cf6);
}

.transparencia-paginacion__info {
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Resultados y estados */
.transparencia-resultados-info {
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
    margin-bottom: 1rem;
}

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
    color: var(--flavor-text, #374151);
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

@media (max-width: 768px) {
    .transparencia-tabla-wrapper {
        display: none;
    }

    .transparencia-contratos__cards {
        display: flex;
    }

    .transparencia-filtros__form {
        flex-direction: column;
    }

    .transparencia-filtros__grupo select,
    .transparencia-filtros__grupo input {
        width: 100%;
    }

    .transparencia-contratos__stats {
        grid-template-columns: 1fr;
    }
}
</style>
