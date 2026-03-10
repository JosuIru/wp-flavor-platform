<?php
/**
 * Dashboard Admin - Documentación Legal
 * Migrado al sistema dm-* centralizado
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_docs = $wpdb->prefix . 'flavor_documentacion_legal';
$tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';
$tabla_favoritos = $wpdb->prefix . 'flavor_documentacion_legal_favoritos';

// Verificar existencia de tablas
$tabla_docs_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_docs
)) > 0;

// Inicializar valores
$total_docs = 0;
$total_borradores = 0;
$total_revision = 0;
$total_descargas = 0;
$total_visitas = 0;
$total_favoritos = 0;
$pendientes_verificar = 0;
$docs_por_tipo = [];
$docs_por_ambito = [];
$docs_populares = [];
$docs_recientes = [];

if ($tabla_docs_existe) {
    $total_docs = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_docs WHERE estado = 'publicado'");
    $total_borradores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_docs WHERE estado = 'borrador'");
    $total_revision = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_docs WHERE estado = 'revision'");
    $total_descargas = (int) $wpdb->get_var("SELECT IFNULL(SUM(descargas), 0) FROM $tabla_docs");
    $total_visitas = (int) $wpdb->get_var("SELECT IFNULL(SUM(visitas), 0) FROM $tabla_docs");
    $pendientes_verificar = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_docs WHERE verificado = 0 AND estado = 'publicado'");

    $docs_por_tipo = $wpdb->get_results("SELECT tipo, COUNT(*) as total FROM $tabla_docs WHERE estado = 'publicado' GROUP BY tipo ORDER BY total DESC");
    $docs_por_ambito = $wpdb->get_results("SELECT ambito, COUNT(*) as total FROM $tabla_docs WHERE estado = 'publicado' GROUP BY ambito ORDER BY total DESC");
    $docs_populares = $wpdb->get_results("SELECT id, titulo, tipo, descargas, visitas FROM $tabla_docs WHERE estado = 'publicado' ORDER BY descargas DESC LIMIT 8");
    $docs_recientes = $wpdb->get_results("SELECT * FROM $tabla_docs ORDER BY created_at DESC LIMIT 8");

    $tabla_favoritos_existe = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_favoritos
    )) > 0;
    if ($tabla_favoritos_existe) {
        $total_favoritos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_favoritos");
    }
}

// Datos demo
$usando_demo = $total_docs == 0;

if ($usando_demo) {
    $total_docs = 156;
    $total_borradores = 12;
    $total_revision = 5;
    $total_descargas = 3420;
    $total_visitas = 12580;
    $total_favoritos = 89;
    $pendientes_verificar = 8;

    $docs_por_tipo = [
        (object) ['tipo' => 'ley', 'total' => 45],
        (object) ['tipo' => 'sentencia', 'total' => 38],
        (object) ['tipo' => 'modelo_denuncia', 'total' => 28],
        (object) ['tipo' => 'guia', 'total' => 22],
        (object) ['tipo' => 'decreto', 'total' => 15],
        (object) ['tipo' => 'modelo_recurso', 'total' => 8],
    ];

    $docs_por_ambito = [
        (object) ['ambito' => 'estatal', 'total' => 65],
        (object) ['ambito' => 'autonomico', 'total' => 48],
        (object) ['ambito' => 'europeo', 'total' => 23],
        (object) ['ambito' => 'municipal', 'total' => 12],
    ];

    $docs_recientes = [
        (object) ['id' => 1, 'titulo' => 'Ley de Evaluación Ambiental', 'tipo' => 'ley', 'ambito' => 'estatal', 'estado' => 'publicado', 'verificado' => 1, 'descargas' => 234, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
        (object) ['id' => 2, 'titulo' => 'Modelo denuncia vertidos ilegales', 'tipo' => 'modelo_denuncia', 'ambito' => 'estatal', 'estado' => 'publicado', 'verificado' => 1, 'descargas' => 189, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        (object) ['id' => 3, 'titulo' => 'Sentencia TS sobre acceso información', 'tipo' => 'sentencia', 'ambito' => 'estatal', 'estado' => 'publicado', 'verificado' => 1, 'descargas' => 156, 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
        (object) ['id' => 4, 'titulo' => 'Guía recurso de alzada', 'tipo' => 'guia', 'ambito' => 'estatal', 'estado' => 'revision', 'verificado' => 0, 'descargas' => 78, 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
        (object) ['id' => 5, 'titulo' => 'Decreto protección espacios naturales', 'tipo' => 'decreto', 'ambito' => 'autonomico', 'estado' => 'borrador', 'verificado' => 0, 'descargas' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
    ];

    $docs_populares = [
        (object) ['id' => 1, 'titulo' => 'Modelo denuncia urbanística', 'tipo' => 'modelo_denuncia', 'descargas' => 456, 'visitas' => 1234],
        (object) ['id' => 2, 'titulo' => 'Ley de Evaluación Ambiental', 'tipo' => 'ley', 'descargas' => 389, 'visitas' => 987],
        (object) ['id' => 3, 'titulo' => 'Modelo recurso contencioso', 'tipo' => 'modelo_recurso', 'descargas' => 312, 'visitas' => 856],
        (object) ['id' => 4, 'titulo' => 'Guía acceso información pública', 'tipo' => 'guia', 'descargas' => 278, 'visitas' => 743],
        (object) ['id' => 5, 'titulo' => 'Sentencia sobre participación', 'tipo' => 'sentencia', 'descargas' => 234, 'visitas' => 654],
    ];
}

$tipos_labels = [
    'ley' => 'Ley',
    'decreto' => 'Decreto',
    'ordenanza' => 'Ordenanza',
    'sentencia' => 'Sentencia',
    'modelo_denuncia' => 'Modelo Denuncia',
    'modelo_recurso' => 'Modelo Recurso',
    'guia' => 'Guía',
    'informe' => 'Informe',
    'otro' => 'Otro',
];

$estado_variantes = [
    'publicado' => 'success',
    'borrador' => 'secondary',
    'revision' => 'warning',
    'archivado' => 'error',
];
?>

<div class="dm-dashboard">
    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-media-document"></span>
            <?php esc_html_e('Dashboard - Documentación Legal', 'flavor-chat-ia'); ?>
        </h1>
        <p class="dm-header__description">
            <?php esc_html_e('Gestiona leyes, sentencias, modelos y guías legales para la comunidad', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <?php if ($usando_demo): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Modo demostración:', 'flavor-chat-ia'); ?></strong>
        <?php esc_html_e('Se muestran datos de ejemplo. Agrega documentos para ver datos reales.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('Listado', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-nuevo')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Nuevo', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-categorias')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-category"></span>
            <?php esc_html_e('Categorías', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-modelos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-media-text"></span>
            <?php esc_html_e('Modelos', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-estadisticas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/documentacion-legal/')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <?php esc_html_e('Portal público', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-media-document"></span></div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_docs); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Documentos Publicados', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-edit"></span></div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_borradores + $total_revision); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-download"></span></div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_descargas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Descargas Totales', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-visibility"></span></div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_visitas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Visitas', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-heart"></span></div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_favoritos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Favoritos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Alerta pendientes -->
    <?php if ($pendientes_verificar > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-info"></span>
        <strong><?php echo number_format_i18n($pendientes_verificar); ?> <?php esc_html_e('documentos sin verificar', 'flavor-chat-ia'); ?></strong> -
        <?php esc_html_e('Revisa y marca como verificados los documentos publicados.', 'flavor-chat-ia'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-listado&verificado=0')); ?>" class="dm-btn dm-btn--sm dm-btn--warning" style="margin-left: 10px;">
            <?php esc_html_e('Ver pendientes', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Documentos por Tipo', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-tipos"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Documentos por Ámbito', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-ambito"></canvas>
            </div>
        </div>
    </div>

    <!-- Documentos Más Descargados -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-download"></span> <?php esc_html_e('Documentos Más Descargados', 'flavor-chat-ia'); ?></h3>
        </div>
        <?php if (!empty($docs_populares)): ?>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Descargas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Visitas', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs_populares as $doc): ?>
                <tr>
                    <td><strong><?php echo esc_html($doc->titulo); ?></strong></td>
                    <td><span class="dm-badge dm-badge--primary"><?php echo esc_html($tipos_labels[$doc->tipo] ?? ucfirst($doc->tipo)); ?></span></td>
                    <td><strong><?php echo number_format_i18n($doc->descargas); ?></strong></td>
                    <td class="dm-table__muted"><?php echo number_format_i18n($doc->visitas); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-media-document"></span>
            <p><?php esc_html_e('No hay documentos registrados', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Documentos Recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-update"></span> <?php esc_html_e('Documentos Recientes', 'flavor-chat-ia'); ?></h3>
        </div>
        <?php if (!empty($docs_recientes)): ?>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Verificado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs_recientes as $doc):
                    $variante = $estado_variantes[$doc->estado] ?? 'secondary';
                ?>
                <tr>
                    <td><strong><?php echo esc_html($doc->titulo); ?></strong></td>
                    <td class="dm-table__muted"><?php echo esc_html($tipos_labels[$doc->tipo] ?? ucfirst($doc->tipo)); ?></td>
                    <td><span class="dm-badge dm-badge--<?php echo esc_attr($variante); ?>"><?php echo esc_html(ucfirst($doc->estado)); ?></span></td>
                    <td>
                        <?php if ($doc->verificado): ?>
                            <span class="dashicons dashicons-yes-alt dm-text-success"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-marker dm-text-warning"></span>
                        <?php endif; ?>
                    </td>
                    <td class="dm-table__muted"><?php echo esc_html(date_i18n('d/m/Y', strtotime($doc->created_at))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    const style = getComputedStyle(document.documentElement);
    const primary = style.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    const success = style.getPropertyValue('--dm-success').trim() || '#10b981';
    const warning = style.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    const error = style.getPropertyValue('--dm-error').trim() || '#ef4444';
    const purple = style.getPropertyValue('--dm-purple').trim() || '#8b5cf6';
    const info = style.getPropertyValue('--dm-info').trim() || '#06b6d4';

    // Gráfico Tipos
    const ctxTipos = document.getElementById('grafico-tipos');
    if (ctxTipos) {
        new Chart(ctxTipos.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($docs_por_tipo as $tipo) { echo "'" . esc_js($tipos_labels[$tipo->tipo] ?? ucfirst($tipo->tipo)) . "',"; } ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($t) { return $t->total; }, $docs_por_tipo)); ?>],
                    backgroundColor: [primary, success, warning, error, purple, info]
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 15 } } }
            }
        });
    }

    // Gráfico Ámbito
    const ctxAmbito = document.getElementById('grafico-ambito');
    if (ctxAmbito) {
        new Chart(ctxAmbito.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php foreach ($docs_por_ambito as $amb) { echo "'" . esc_js(ucfirst($amb->ambito)) . "',"; } ?>],
                datasets: [{
                    label: 'Documentos',
                    data: [<?php echo implode(',', array_map(function($a) { return $a->total; }, $docs_por_ambito)); ?>],
                    backgroundColor: primary
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
