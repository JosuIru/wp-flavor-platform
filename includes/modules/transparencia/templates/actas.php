<?php
/**
 * Template: Lista de Actas de Reuniones
 *
 * Muestra las actas de reuniones con opciones de descarga.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_actas = '';
$tablas_actas_candidatas = [
    $prefijo_tabla . 'actas',
    $prefijo_tabla . 'actas_reuniones',
];
foreach ($tablas_actas_candidatas as $tabla_candidata) {
    if (Flavor_Chat_Helpers::tabla_existe($tabla_candidata)) {
        $tabla_actas = $tabla_candidata;
        break;
    }
}

// Verificar que la tabla existe
if ($tabla_actas === '') {
    echo '<div class="transparencia-aviso transparencia-aviso--info">';
    echo '<span class="dashicons dashicons-info"></span>';
    echo '<p>' . esc_html__('Todavía no hay actas publicadas en esta instalación.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Obtener parametros de filtro
$limite = isset($atts['limite']) ? intval($atts['limite']) : 20;
$tipo_organo_filtro = isset($_GET['tipo_organo']) ? sanitize_text_field($_GET['tipo_organo']) : '';
$anio_filtro = isset($_GET['anio']) ? intval($_GET['anio']) : '';
$pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$offset = ($pagina_actual - 1) * $limite;

// Construir consulta
$where_clauses = ["estado IN ('aprobada', 'publicada')"];
$where_params = [];

if ($tipo_organo_filtro) {
    $where_clauses[] = "tipo_organo = %s";
    $where_params[] = $tipo_organo_filtro;
}

if ($anio_filtro) {
    $where_clauses[] = "YEAR(fecha_sesion) = %d";
    $where_params[] = $anio_filtro;
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener total de resultados
$total_query = "SELECT COUNT(*) FROM $tabla_actas WHERE $where_sql";
$total_actas = $wpdb->get_var($where_params ? $wpdb->prepare($total_query, $where_params) : $total_query);
$total_paginas = ceil($total_actas / $limite);

// Obtener actas
$query = "SELECT * FROM $tabla_actas WHERE $where_sql ORDER BY fecha_sesion DESC LIMIT %d OFFSET %d";
$query_params = array_merge($where_params, [$limite, $offset]);
$actas = $wpdb->get_results($wpdb->prepare($query, $query_params));

// Obtener tipos de organo disponibles
$tipos_organo = $wpdb->get_col("SELECT DISTINCT tipo_organo FROM $tabla_actas WHERE estado IN ('aprobada', 'publicada') ORDER BY tipo_organo");

// Obtener anios disponibles
$anios_disponibles = $wpdb->get_col("SELECT DISTINCT YEAR(fecha_sesion) as anio FROM $tabla_actas WHERE estado IN ('aprobada', 'publicada') ORDER BY anio DESC");

// Nombres legibles de tipos de organo
$tipos_organo_nombres = [
    'pleno' => __('Pleno', 'flavor-chat-ia'),
    'junta_gobierno' => __('Junta de Gobierno', 'flavor-chat-ia'),
    'comision' => __('Comision', 'flavor-chat-ia'),
    'consejo' => __('Consejo', 'flavor-chat-ia'),
    'otros' => __('Otros', 'flavor-chat-ia'),
];

// Tipos de sesion
$tipos_sesion_nombres = [
    'ordinaria' => __('Ordinaria', 'flavor-chat-ia'),
    'extraordinaria' => __('Extraordinaria', 'flavor-chat-ia'),
    'urgente' => __('Urgente', 'flavor-chat-ia'),
];
?>

<div class="transparencia-actas">
    <header class="transparencia-actas__header">
        <div class="transparencia-actas__titulo">
            <span class="dashicons dashicons-text-page"></span>
            <h2><?php esc_html_e('Actas de Reuniones', 'flavor-chat-ia'); ?></h2>
        </div>
        <p class="transparencia-actas__descripcion">
            <?php esc_html_e('Consulta y descarga las actas de las sesiones de los organos de gobierno.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <!-- Filtros -->
    <div class="transparencia-filtros">
        <form class="transparencia-filtros__form" method="get">
            <div class="transparencia-filtros__grupo">
                <label for="tipo_organo"><?php esc_html_e('Organo', 'flavor-chat-ia'); ?></label>
                <select name="tipo_organo" id="tipo_organo">
                    <option value=""><?php esc_html_e('Todos los organos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_organo as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo); ?>" <?php selected($tipo_organo_filtro, $tipo); ?>>
                        <?php echo esc_html($tipos_organo_nombres[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo))); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="transparencia-filtros__grupo">
                <label for="anio"><?php esc_html_e('Ano', 'flavor-chat-ia'); ?></label>
                <select name="anio" id="anio">
                    <option value=""><?php esc_html_e('Todos los anos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($anios_disponibles as $anio) : ?>
                    <option value="<?php echo esc_attr($anio); ?>" <?php selected($anio_filtro, $anio); ?>>
                        <?php echo esc_html($anio); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="transparencia-btn transparencia-btn--secondary">
                <span class="dashicons dashicons-filter"></span>
                <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>

    <!-- Contador de resultados -->
    <div class="transparencia-resultados-info">
        <?php printf(
            esc_html(_n('%d acta encontrada', '%d actas encontradas', $total_actas, 'flavor-chat-ia')),
            $total_actas
        ); ?>
    </div>

    <?php if (!empty($actas)) : ?>
    <!-- Lista de actas -->
    <div class="transparencia-actas__lista">
        <?php foreach ($actas as $acta) :
            $tipo_organo_nombre = $tipos_organo_nombres[$acta->tipo_organo] ?? ucfirst(str_replace('_', ' ', $acta->tipo_organo));
            $tipo_sesion_nombre = $tipos_sesion_nombres[$acta->tipo_sesion] ?? ucfirst($acta->tipo_sesion);
            $asistentes = $acta->asistentes ? json_decode($acta->asistentes, true) : [];
            $acuerdos = $acta->acuerdos ? json_decode($acta->acuerdos, true) : [];
        ?>
        <article class="transparencia-acta-card">
            <header class="transparencia-acta-card__header">
                <div class="transparencia-acta-card__organo">
                    <span class="transparencia-badge transparencia-badge--<?php echo esc_attr($acta->tipo_organo); ?>">
                        <?php echo esc_html($tipo_organo_nombre); ?>
                    </span>
                    <span class="transparencia-badge transparencia-badge--tipo-sesion">
                        <?php echo esc_html($tipo_sesion_nombre); ?>
                    </span>
                </div>
                <span class="transparencia-acta-card__numero">
                    <?php if ($acta->numero_sesion) : ?>
                    <?php printf(esc_html__('Sesion %s', 'flavor-chat-ia'), esc_html($acta->numero_sesion)); ?>
                    <?php endif; ?>
                </span>
            </header>

            <div class="transparencia-acta-card__contenido">
                <h3 class="transparencia-acta-card__titulo"><?php echo esc_html($acta->nombre_organo); ?></h3>

                <div class="transparencia-acta-card__meta">
                    <span class="transparencia-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html(date_i18n('d F Y, H:i', strtotime($acta->fecha_sesion))); ?>
                    </span>
                    <?php if ($acta->lugar) : ?>
                    <span class="transparencia-meta-item">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($acta->lugar); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($asistentes)) : ?>
                    <span class="transparencia-meta-item">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(esc_html__('%d asistentes', 'flavor-chat-ia'), count($asistentes)); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($acuerdos)) : ?>
                <div class="transparencia-acta-card__acuerdos">
                    <strong><?php esc_html_e('Acuerdos:', 'flavor-chat-ia'); ?></strong>
                    <span><?php echo esc_html(count($acuerdos)); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($acta->orden_del_dia) : ?>
                <details class="transparencia-acta-card__orden">
                    <summary><?php esc_html_e('Ver orden del dia', 'flavor-chat-ia'); ?></summary>
                    <div class="transparencia-orden-contenido">
                        <?php echo wp_kses_post(nl2br($acta->orden_del_dia)); ?>
                    </div>
                </details>
                <?php endif; ?>
            </div>

            <footer class="transparencia-acta-card__footer">
                <div class="transparencia-acta-card__acciones">
                    <?php if ($acta->acta_url) : ?>
                    <a href="<?php echo esc_url($acta->acta_url); ?>" class="transparencia-btn transparencia-btn--primary transparencia-btn--sm" target="_blank">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Descargar Acta', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($acta->convocatoria_url) : ?>
                    <a href="<?php echo esc_url($acta->convocatoria_url); ?>" class="transparencia-btn transparencia-btn--outline transparencia-btn--sm" target="_blank">
                        <span class="dashicons dashicons-media-document"></span>
                        <?php esc_html_e('Convocatoria', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($acta->video_url) : ?>
                    <a href="<?php echo esc_url($acta->video_url); ?>" class="transparencia-btn transparencia-btn--outline transparencia-btn--sm" target="_blank">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <?php esc_html_e('Video', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($acta->fecha_aprobacion) : ?>
                <span class="transparencia-acta-card__aprobacion">
                    <?php printf(
                        esc_html__('Aprobada: %s', 'flavor-chat-ia'),
                        date_i18n('d/m/Y', strtotime($acta->fecha_aprobacion))
                    ); ?>
                </span>
                <?php endif; ?>
            </footer>
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
        <span class="dashicons dashicons-text-page"></span>
        <h3><?php esc_html_e('No hay actas disponibles', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('No se encontraron actas que coincidan con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.transparencia-actas {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-actas__header {
    margin-bottom: 2rem;
}

.transparencia-actas__titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.transparencia-actas__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
    color: var(--flavor-primary, #6366f1);
}

.transparencia-actas__titulo h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-actas__descripcion {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
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

.transparencia-filtros__grupo label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-filtros__grupo select {
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: 8px;
    font-size: 0.875rem;
    background-color: #fff;
    min-width: 180px;
}

.transparencia-resultados-info {
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
    margin-bottom: 1rem;
}

/* Acta Card */
.transparencia-actas__lista {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.transparencia-acta-card {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    border-left: 4px solid var(--flavor-primary, #6366f1);
}

.transparencia-acta-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-acta-card__organo {
    display: flex;
    gap: 0.5rem;
}

.transparencia-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.transparencia-badge--pleno {
    background: #dbeafe;
    color: #1e40af;
}

.transparencia-badge--junta_gobierno {
    background: #fef3c7;
    color: #92400e;
}

.transparencia-badge--comision {
    background: #e0e7ff;
    color: #3730a3;
}

.transparencia-badge--consejo {
    background: #d1fae5;
    color: #065f46;
}

.transparencia-badge--tipo-sesion {
    background: var(--flavor-bg-light, #f3f4f6);
    color: var(--flavor-text, #374151);
}

.transparencia-acta-card__numero {
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-acta-card__contenido {
    padding: 1.25rem;
}

.transparencia-acta-card__titulo {
    margin: 0 0 1rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-acta-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.transparencia-meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-meta-item .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.transparencia-acta-card__acuerdos {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--flavor-text, #374151);
    margin-bottom: 1rem;
}

.transparencia-acta-card__orden {
    margin-top: 1rem;
}

.transparencia-acta-card__orden summary {
    cursor: pointer;
    color: var(--flavor-primary, #6366f1);
    font-weight: 500;
    font-size: 0.875rem;
}

.transparencia-orden-contenido {
    margin-top: 0.75rem;
    padding: 1rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-radius: 8px;
    font-size: 0.875rem;
    color: var(--flavor-text, #374151);
    line-height: 1.6;
}

.transparencia-acta-card__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-top: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-acta-card__acciones {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.transparencia-acta-card__aprobacion {
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
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
    background: var(--flavor-primary, #6366f1);
    color: #fff;
}

.transparencia-btn--primary:hover {
    background: var(--flavor-primary-dark, #4f46e5);
}

.transparencia-btn--secondary {
    background: var(--flavor-bg-light, #f3f4f6);
    color: var(--flavor-text, #374151);
}

.transparencia-btn--secondary:hover {
    background: var(--flavor-border, #e5e7eb);
}

.transparencia-btn--outline {
    background: transparent;
    color: var(--flavor-primary, #6366f1);
    border: 1px solid var(--flavor-primary, #6366f1);
}

.transparencia-btn--outline:hover {
    background: var(--flavor-primary-light, #eef2ff);
}

.transparencia-btn--sm {
    padding: 0.5rem 0.875rem;
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
    transition: all 0.2s;
}

.transparencia-paginacion__btn:hover {
    background: var(--flavor-bg-light, #f9fafb);
    border-color: var(--flavor-primary, #6366f1);
    color: var(--flavor-primary, #6366f1);
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
    color: var(--flavor-text, #374151);
}

.transparencia-empty-state p {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
}

/* Aviso */
.transparencia-aviso {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.transparencia-aviso--info {
    background: #eff6ff;
    color: #1e40af;
}

.transparencia-aviso .dashicons {
    flex-shrink: 0;
}

.transparencia-aviso p {
    margin: 0;
}

@media (max-width: 640px) {
    .transparencia-filtros__form {
        flex-direction: column;
    }

    .transparencia-filtros__grupo select {
        min-width: 100%;
    }

    .transparencia-acta-card__footer {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .transparencia-acta-card__meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
