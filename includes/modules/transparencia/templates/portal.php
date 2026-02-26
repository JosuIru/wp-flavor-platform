<?php
/**
 * Template: Portal de Transparencia
 *
 * Portal principal con acceso a todas las secciones de transparencia.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_documentos = $prefijo_tabla . 'documentos_publicos';
$tabla_presupuestos = $prefijo_tabla . 'presupuestos';
$tabla_actas = $prefijo_tabla . 'actas';
$tabla_solicitudes = $prefijo_tabla . 'solicitudes_info';
$tabla_gastos = $prefijo_tabla . 'gastos';
$tabla_contratos = $prefijo_tabla . 'contratos';

// Obtener estadisticas generales
$total_documentos = 0;
$total_presupuestos = 0;
$total_actas = 0;
$total_solicitudes_resueltas = 0;
$ejercicio_actual = date('Y');

if (Flavor_Chat_Helpers::tabla_existe($tabla_documentos)) {
    $total_documentos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_documentos WHERE estado = 'publicado'");
}

if (Flavor_Chat_Helpers::tabla_existe($tabla_presupuestos)) {
    $total_presupuestos = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT ejercicio) FROM $tabla_presupuestos WHERE ejercicio >= %d",
        $ejercicio_actual - 5
    ));
}

if (Flavor_Chat_Helpers::tabla_existe($tabla_actas)) {
    $total_actas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actas WHERE estado IN ('aprobada', 'publicada')");
}

if (Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
    $total_solicitudes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
    $solicitudes_resueltas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta'");
    $tasa_respuesta = $total_solicitudes > 0 ? round(($solicitudes_resueltas / $total_solicitudes) * 100) : 0;
}

// Categorias disponibles
$categorias = [
    'presupuestos' => [
        'nombre' => __('Presupuestos', 'flavor-chat-ia'),
        'icono' => 'dashicons-chart-pie',
        'color' => '#3b82f6',
        'descripcion' => __('Presupuestos anuales, ejecucion y modificaciones', 'flavor-chat-ia'),
        'url' => home_url('/transparencia/presupuestos/')
    ],
    'gastos' => [
        'nombre' => __('Gastos', 'flavor-chat-ia'),
        'icono' => 'dashicons-money-alt',
        'color' => '#10b981',
        'descripcion' => __('Detalle de gastos y facturas', 'flavor-chat-ia'),
        'url' => home_url('/transparencia/gastos/')
    ],
    'contratos' => [
        'nombre' => __('Contratos', 'flavor-chat-ia'),
        'icono' => 'dashicons-media-document',
        'color' => '#8b5cf6',
        'descripcion' => __('Contratos publicos y licitaciones', 'flavor-chat-ia'),
        'url' => home_url('/transparencia/contratos/')
    ],
    'actas' => [
        'nombre' => __('Actas', 'flavor-chat-ia'),
        'icono' => 'dashicons-text-page',
        'color' => '#6366f1',
        'descripcion' => __('Actas de reuniones y sesiones', 'flavor-chat-ia'),
        'url' => home_url('/transparencia/actas/')
    ],
    'indicadores' => [
        'nombre' => __('Indicadores', 'flavor-chat-ia'),
        'icono' => 'dashicons-chart-bar',
        'color' => '#14b8a6',
        'descripcion' => __('Indicadores de gestion y rendimiento', 'flavor-chat-ia'),
        'url' => home_url('/transparencia/indicadores/')
    ],
    'normativa' => [
        'nombre' => __('Normativa', 'flavor-chat-ia'),
        'icono' => 'dashicons-clipboard',
        'color' => '#f59e0b',
        'descripcion' => __('Reglamentos y ordenanzas', 'flavor-chat-ia'),
        'url' => home_url('/transparencia/normativa/')
    ],
];

// Documentos recientes
$documentos_recientes = [];
if (Flavor_Chat_Helpers::tabla_existe($tabla_documentos)) {
    $documentos_recientes = $wpdb->get_results(
        "SELECT id, titulo, categoria, archivo_url, fecha_publicacion
         FROM $tabla_documentos
         WHERE estado = 'publicado'
         ORDER BY fecha_publicacion DESC
         LIMIT 5"
    );
}
?>

<div class="transparencia-portal">
    <!-- Cabecera del portal -->
    <header class="transparencia-portal__header">
        <div class="transparencia-portal__titulo">
            <span class="dashicons dashicons-visibility"></span>
            <h1><?php esc_html_e('Portal de Transparencia', 'flavor-chat-ia'); ?></h1>
        </div>
        <p class="transparencia-portal__descripcion">
            <?php esc_html_e('Accede a toda la informacion publica sobre gestion, presupuestos, contratos y actividad institucional.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <!-- KPIs principales -->
    <div class="transparencia-portal__kpis">
        <div class="transparencia-kpi">
            <span class="transparencia-kpi__icono dashicons dashicons-media-document"></span>
            <div class="transparencia-kpi__contenido">
                <span class="transparencia-kpi__valor"><?php echo esc_html($total_documentos); ?></span>
                <span class="transparencia-kpi__etiqueta"><?php esc_html_e('Documentos publicos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="transparencia-kpi">
            <span class="transparencia-kpi__icono dashicons dashicons-chart-pie"></span>
            <div class="transparencia-kpi__contenido">
                <span class="transparencia-kpi__valor"><?php echo esc_html($total_presupuestos); ?></span>
                <span class="transparencia-kpi__etiqueta"><?php esc_html_e('Ejercicios presupuestarios', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="transparencia-kpi">
            <span class="transparencia-kpi__icono dashicons dashicons-text-page"></span>
            <div class="transparencia-kpi__contenido">
                <span class="transparencia-kpi__valor"><?php echo esc_html($total_actas); ?></span>
                <span class="transparencia-kpi__etiqueta"><?php esc_html_e('Actas publicadas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="transparencia-kpi">
            <span class="transparencia-kpi__icono dashicons dashicons-yes-alt"></span>
            <div class="transparencia-kpi__contenido">
                <span class="transparencia-kpi__valor"><?php echo esc_html($tasa_respuesta ?? 0); ?>%</span>
                <span class="transparencia-kpi__etiqueta"><?php esc_html_e('Tasa de respuesta', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Grid de categorias -->
    <section class="transparencia-portal__categorias">
        <h2><?php esc_html_e('Secciones', 'flavor-chat-ia'); ?></h2>
        <div class="transparencia-categorias-grid">
            <?php foreach ($categorias as $categoria_clave => $categoria_datos) : ?>
            <a href="<?php echo esc_url($categoria_datos['url']); ?>" class="transparencia-categoria-card" style="--categoria-color: <?php echo esc_attr($categoria_datos['color']); ?>">
                <div class="transparencia-categoria-card__icono">
                    <span class="dashicons <?php echo esc_attr($categoria_datos['icono']); ?>"></span>
                </div>
                <h3 class="transparencia-categoria-card__titulo"><?php echo esc_html($categoria_datos['nombre']); ?></h3>
                <p class="transparencia-categoria-card__descripcion"><?php echo esc_html($categoria_datos['descripcion']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Documentos recientes -->
    <?php if (!empty($documentos_recientes)) : ?>
    <section class="transparencia-portal__recientes">
        <div class="transparencia-seccion-header">
            <h2><?php esc_html_e('Documentos recientes', 'flavor-chat-ia'); ?></h2>
            <a href="<?php echo esc_url(home_url('/transparencia/documentos/')); ?>" class="transparencia-ver-todos">
                <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
        <div class="transparencia-documentos-lista">
            <?php foreach ($documentos_recientes as $documento) : ?>
            <div class="transparencia-documento-item">
                <div class="transparencia-documento-item__icono">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="transparencia-documento-item__info">
                    <h4><?php echo esc_html($documento->titulo); ?></h4>
                    <span class="transparencia-documento-item__fecha">
                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($documento->fecha_publicacion))); ?>
                    </span>
                </div>
                <?php if ($documento->archivo_url) : ?>
                <a href="<?php echo esc_url($documento->archivo_url); ?>" class="transparencia-btn-descargar" target="_blank" title="<?php esc_attr_e('Descargar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Solicitar informacion -->
    <section class="transparencia-portal__solicitar">
        <div class="transparencia-solicitar-cta">
            <span class="dashicons dashicons-email-alt"></span>
            <div class="transparencia-solicitar-cta__contenido">
                <h3><?php esc_html_e('Derecho de acceso a la informacion', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Puedes solicitar informacion publica que no este disponible en este portal.', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="<?php echo esc_url(home_url('/transparencia/solicitar/')); ?>" class="transparencia-btn transparencia-btn--primary">
                <?php esc_html_e('Solicitar informacion', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </section>
</div>

<style>
.transparencia-portal {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-portal__header {
    text-align: center;
    margin-bottom: 3rem;
}

.transparencia-portal__titulo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.transparencia-portal__titulo .dashicons {
    font-size: 2rem;
    width: 2rem;
    height: 2rem;
    color: var(--flavor-primary, #3b82f6);
}

.transparencia-portal__titulo h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-portal__descripcion {
    color: var(--flavor-text-light, #6b7280);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
}

/* KPIs */
.transparencia-portal__kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.transparencia-kpi {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--flavor-card-bg, #fff);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-kpi__icono {
    width: 48px;
    height: 48px;
    background: var(--flavor-primary-light, #eff6ff);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--flavor-primary, #3b82f6);
    font-size: 1.5rem;
}

.transparencia-kpi__valor {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-kpi__etiqueta {
    display: block;
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Categorias */
.transparencia-portal__categorias h2 {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    color: var(--flavor-text, #1f2937);
}

.transparencia-categorias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.transparencia-categoria-card {
    display: block;
    background: var(--flavor-card-bg, #fff);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    border-left: 4px solid var(--categoria-color);
}

.transparencia-categoria-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.transparencia-categoria-card__icono {
    width: 48px;
    height: 48px;
    background: color-mix(in srgb, var(--categoria-color) 15%, transparent);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.transparencia-categoria-card__icono .dashicons {
    font-size: 1.5rem;
    color: var(--categoria-color);
}

.transparencia-categoria-card__titulo {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-categoria-card__descripcion {
    margin: 0;
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Documentos recientes */
.transparencia-portal__recientes {
    margin-bottom: 3rem;
}

.transparencia-seccion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.transparencia-seccion-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.transparencia-ver-todos {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--flavor-primary, #3b82f6);
    text-decoration: none;
    font-weight: 500;
}

.transparencia-documentos-lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.transparencia-documento-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--flavor-card-bg, #fff);
    padding: 1rem 1.25rem;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.transparencia-documento-item__icono {
    width: 40px;
    height: 40px;
    background: var(--flavor-primary-light, #eff6ff);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--flavor-primary, #3b82f6);
    flex-shrink: 0;
}

.transparencia-documento-item__info {
    flex: 1;
    min-width: 0;
}

.transparencia-documento-item__info h4 {
    margin: 0 0 0.25rem;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--flavor-text, #1f2937);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.transparencia-documento-item__fecha {
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-btn-descargar {
    width: 36px;
    height: 36px;
    background: var(--flavor-primary, #3b82f6);
    color: #fff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: background 0.2s;
    flex-shrink: 0;
}

.transparencia-btn-descargar:hover {
    background: var(--flavor-primary-dark, #2563eb);
}

/* CTA Solicitar */
.transparencia-solicitar-cta {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    background: linear-gradient(135deg, var(--flavor-primary, #3b82f6), var(--flavor-primary-dark, #2563eb));
    color: #fff;
    padding: 2rem;
    border-radius: 16px;
}

.transparencia-solicitar-cta > .dashicons {
    font-size: 2.5rem;
    width: 2.5rem;
    height: 2.5rem;
    opacity: 0.9;
}

.transparencia-solicitar-cta__contenido {
    flex: 1;
}

.transparencia-solicitar-cta h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.transparencia-solicitar-cta p {
    margin: 0;
    opacity: 0.9;
}

.transparencia-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.transparencia-btn--primary {
    background: #fff;
    color: var(--flavor-primary, #3b82f6);
}

.transparencia-btn--primary:hover {
    background: rgba(255,255,255,0.9);
}

@media (max-width: 768px) {
    .transparencia-solicitar-cta {
        flex-direction: column;
        text-align: center;
    }

    .transparencia-portal__titulo h1 {
        font-size: 1.5rem;
    }

    .transparencia-categorias-grid {
        grid-template-columns: 1fr;
    }
}
</style>
