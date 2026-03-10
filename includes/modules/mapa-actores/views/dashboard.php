<?php
/**
 * Dashboard Admin - Mapa de Actores
 *
 * Panel principal con estadisticas de actores y relaciones
 * Migrado al sistema dm-* centralizado
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
$tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';
$tabla_interacciones = $wpdb->prefix . 'flavor_mapa_actores_interacciones';
$tabla_personas = $wpdb->prefix . 'flavor_mapa_actores_personas';

// Verificar existencia de tablas
$tabla_actores_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_actores
)) > 0;

$tabla_relaciones_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_relaciones
)) > 0;

$tabla_interacciones_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_interacciones
)) > 0;

$tabla_personas_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_personas
)) > 0;

// Inicializar valores por defecto
$total_actores = 0;
$total_relaciones = 0;
$total_interacciones = 0;
$total_personas = 0;
$aliados = 0;
$neutros = 0;
$opositores = 0;
$desconocidos = 0;
$pendientes_clasificar = 0;
$actores_por_tipo = [];
$actores_por_ambito = [];
$actores_recientes = [];
$interacciones_recientes = [];

// Obtener datos si las tablas existen
if ($tabla_actores_existe) {
    $total_actores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE activo = 1");
    $aliados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE posicion_general = 'aliado' AND activo = 1");
    $neutros = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE posicion_general = 'neutro' AND activo = 1");
    $opositores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE posicion_general = 'opositor' AND activo = 1");
    $desconocidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE posicion_general = 'desconocido' AND activo = 1");
    $pendientes_clasificar = $desconocidos;

    $actores_por_tipo = $wpdb->get_results(
        "SELECT tipo, COUNT(*) as total FROM $tabla_actores WHERE activo = 1 GROUP BY tipo ORDER BY total DESC"
    );

    $actores_por_ambito = $wpdb->get_results(
        "SELECT ambito, COUNT(*) as total FROM $tabla_actores WHERE activo = 1 GROUP BY ambito ORDER BY total DESC"
    );

    $actores_recientes = $wpdb->get_results(
        "SELECT * FROM $tabla_actores WHERE activo = 1 ORDER BY created_at DESC LIMIT 8"
    );
}

if ($tabla_relaciones_existe) {
    $total_relaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_relaciones");
}

if ($tabla_interacciones_existe && $tabla_actores_existe) {
    $total_interacciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_interacciones");

    $interacciones_recientes = $wpdb->get_results(
        "SELECT i.*, a.nombre as actor_nombre
         FROM $tabla_interacciones i
         LEFT JOIN $tabla_actores a ON i.actor_id = a.id
         ORDER BY i.fecha DESC, i.created_at DESC
         LIMIT 8"
    );
}

if ($tabla_personas_existe) {
    $total_personas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_personas WHERE activo = 1");
}

// Datos de ejemplo si no hay datos reales
$usando_demo = $total_actores == 0;

if ($usando_demo) {
    $total_actores = 45;
    $total_relaciones = 78;
    $total_interacciones = 23;
    $total_personas = 156;
    $aliados = 18;
    $neutros = 15;
    $opositores = 7;
    $desconocidos = 5;
    $pendientes_clasificar = 5;

    $actores_por_tipo = [
        (object) ['tipo' => 'administracion_publica', 'total' => 12],
        (object) ['tipo' => 'empresa', 'total' => 10],
        (object) ['tipo' => 'institucion', 'total' => 8],
        (object) ['tipo' => 'ong', 'total' => 6],
        (object) ['tipo' => 'colectivo', 'total' => 5],
        (object) ['tipo' => 'medio_comunicacion', 'total' => 4],
    ];

    $actores_por_ambito = [
        (object) ['ambito' => 'local', 'total' => 25],
        (object) ['ambito' => 'comarcal', 'total' => 8],
        (object) ['ambito' => 'provincial', 'total' => 6],
        (object) ['ambito' => 'autonomico', 'total' => 4],
        (object) ['ambito' => 'estatal', 'total' => 2],
    ];

    $actores_recientes = [
        (object) ['id' => 1, 'nombre' => 'Ayuntamiento Local', 'tipo' => 'administracion_publica', 'posicion_general' => 'aliado', 'nivel_influencia' => 'muy_alto', 'ambito' => 'local', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
        (object) ['id' => 2, 'nombre' => 'Cooperativa Agricola', 'tipo' => 'empresa', 'posicion_general' => 'aliado', 'nivel_influencia' => 'alto', 'ambito' => 'comarcal', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        (object) ['id' => 3, 'nombre' => 'Asociacion Vecinos Centro', 'tipo' => 'colectivo', 'posicion_general' => 'neutro', 'nivel_influencia' => 'medio', 'ambito' => 'local', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
        (object) ['id' => 4, 'nombre' => 'Diario Regional', 'tipo' => 'medio_comunicacion', 'posicion_general' => 'neutro', 'nivel_influencia' => 'alto', 'ambito' => 'provincial', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
        (object) ['id' => 5, 'nombre' => 'Constructora XYZ', 'tipo' => 'empresa', 'posicion_general' => 'opositor', 'nivel_influencia' => 'alto', 'ambito' => 'provincial', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
    ];

    $interacciones_recientes = [
        (object) ['id' => 1, 'actor_nombre' => 'Ayuntamiento Local', 'tipo' => 'reunion', 'titulo' => 'Reunion sobre proyecto comunitario', 'fecha' => date('Y-m-d', strtotime('-2 days')), 'resultado' => 'positivo'],
        (object) ['id' => 2, 'actor_nombre' => 'Diario Regional', 'tipo' => 'comunicacion', 'titulo' => 'Entrevista sobre iniciativas locales', 'fecha' => date('Y-m-d', strtotime('-5 days')), 'resultado' => 'positivo'],
        (object) ['id' => 3, 'actor_nombre' => 'Constructora XYZ', 'tipo' => 'conflicto', 'titulo' => 'Discrepancia sobre uso de terrenos', 'fecha' => date('Y-m-d', strtotime('-1 week')), 'resultado' => 'negativo'],
    ];
}

$tipos_labels = [
    'administracion_publica' => 'Administración Pública',
    'empresa' => 'Empresa',
    'institucion' => 'Institución',
    'medio_comunicacion' => 'Medio de Comunicación',
    'partido_politico' => 'Partido Político',
    'sindicato' => 'Sindicato',
    'ong' => 'ONG',
    'colectivo' => 'Colectivo',
    'persona' => 'Persona',
    'otro' => 'Otro',
];

$posicion_variantes = [
    'aliado' => 'success',
    'neutro' => 'warning',
    'opositor' => 'error',
    'desconocido' => 'secondary',
];

$influencia_variantes = [
    'bajo' => 'secondary',
    'medio' => 'info',
    'alto' => 'warning',
    'muy_alto' => 'error',
];

$resultado_variantes = [
    'positivo' => 'success',
    'neutro' => 'warning',
    'negativo' => 'error',
];
?>

<div class="dm-dashboard">
    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-networking"></span>
            <?php esc_html_e('Dashboard - Mapa de Actores', 'flavor-chat-ia'); ?>
        </h1>
        <p class="dm-header__description">
            <?php esc_html_e('Visualiza y gestiona las relaciones estratégicas con actores clave del entorno', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <?php if ($usando_demo): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Modo demostración:', 'flavor-chat-ia'); ?></strong>
        <?php esc_html_e('Se muestran datos de ejemplo. Agrega actores para ver datos reales.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('Listado', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-nuevo')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Nuevo Actor', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-relaciones')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-links"></span>
            <?php esc_html_e('Relaciones', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-interacciones')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-format-chat"></span>
            <?php esc_html_e('Interacciones', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-personas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-businessman"></span>
            <?php esc_html_e('Personas Clave', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Configuración', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_actores); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Actores', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($aliados); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Aliados', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-minus"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($neutros); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Neutros', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($opositores); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Opositores', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_relaciones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Relaciones', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_interacciones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Interacciones', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Alerta de pendientes -->
    <?php if ($pendientes_clasificar > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-info"></span>
        <strong><?php echo number_format_i18n($pendientes_clasificar); ?> <?php esc_html_e('actores sin clasificar', 'flavor-chat-ia'); ?></strong> -
        <?php esc_html_e('Revisa su posición (aliado/neutro/opositor) para mejorar el análisis.', 'flavor-chat-ia'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&posicion=desconocido')); ?>" class="dm-btn dm-btn--sm dm-btn--warning" style="margin-left: 10px;">
            <?php esc_html_e('Ver pendientes', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <!-- Distribución por Tipo -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Distribución por Tipo', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-tipos"></canvas>
            </div>
        </div>

        <!-- Distribución por Posición -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Distribución por Posición', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-posicion"></canvas>
            </div>
        </div>
    </div>

    <!-- Actores Recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-groups"></span> <?php esc_html_e('Actores Recientes', 'flavor-chat-ia'); ?></h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado')); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php if (!empty($actores_recientes)): ?>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Posición', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Influencia', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ámbito', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actores_recientes as $actor):
                    $posicion_variante = $posicion_variantes[$actor->posicion_general] ?? 'secondary';
                    $influencia_variante = $influencia_variantes[$actor->nivel_influencia] ?? 'info';
                    $posicion_label = ucfirst($actor->posicion_general);
                    $influencia_label = ucfirst(str_replace('_', ' ', $actor->nivel_influencia));
                ?>
                <tr>
                    <td><strong><?php echo esc_html($actor->nombre); ?></strong></td>
                    <td class="dm-table__muted"><?php echo esc_html($tipos_labels[$actor->tipo] ?? ucfirst($actor->tipo)); ?></td>
                    <td>
                        <span class="dm-badge dm-badge--<?php echo esc_attr($posicion_variante); ?>">
                            <?php echo esc_html($posicion_label); ?>
                        </span>
                    </td>
                    <td>
                        <span class="dm-badge dm-badge--<?php echo esc_attr($influencia_variante); ?>">
                            <?php echo esc_html($influencia_label); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(ucfirst($actor->ambito)); ?></td>
                    <td class="dm-table__muted"><?php echo esc_html(date_i18n('d/m/Y', strtotime($actor->created_at))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-groups"></span>
            <p><?php esc_html_e('No hay actores registrados', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-nuevo')); ?>" class="dm-btn dm-btn--primary">
                <?php esc_html_e('Agregar actor', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Interacciones Recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e('Interacciones Recientes', 'flavor-chat-ia'); ?></h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-interacciones')); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                <?php esc_html_e('Ver todas', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php if (!empty($interacciones_recientes)): ?>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Actor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Resultado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($interacciones_recientes as $interaccion):
                    $resultado_variante = $resultado_variantes[$interaccion->resultado] ?? 'warning';
                    $resultado_label = ucfirst($interaccion->resultado);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($interaccion->actor_nombre); ?></strong></td>
                    <td class="dm-table__muted"><?php echo esc_html(ucfirst($interaccion->tipo)); ?></td>
                    <td><?php echo esc_html($interaccion->titulo); ?></td>
                    <td>
                        <span class="dm-badge dm-badge--<?php echo esc_attr($resultado_variante); ?>">
                            <?php echo esc_html($resultado_label); ?>
                        </span>
                    </td>
                    <td class="dm-table__muted"><?php echo esc_html(date_i18n('d/m/Y', strtotime($interaccion->fecha))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-format-chat"></span>
            <p><?php esc_html_e('No hay interacciones registradas', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-interacciones&action=nueva')); ?>" class="dm-btn dm-btn--primary">
                <?php esc_html_e('Registrar interacción', 'flavor-chat-ia'); ?>
            </a>
        </div>
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
    const muted = style.getPropertyValue('--dm-text-muted').trim() || '#64748b';

    // Gráfico Tipos (Doughnut)
    const ctxTipos = document.getElementById('grafico-tipos');
    if (ctxTipos) {
        new Chart(ctxTipos.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [
                    <?php
                    foreach ($actores_por_tipo as $tipo) {
                        $label = $tipos_labels[$tipo->tipo] ?? ucfirst($tipo->tipo);
                        echo "'" . esc_js($label) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($t) { return $t->total; }, $actores_por_tipo)); ?>],
                    backgroundColor: [primary, success, warning, error, purple, info, muted, '#f472b6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                }
            }
        });
    }

    // Gráfico Posición (Bar horizontal)
    const ctxPosicion = document.getElementById('grafico-posicion');
    if (ctxPosicion) {
        new Chart(ctxPosicion.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Aliados', 'Neutros', 'Opositores', 'Sin clasificar'],
                datasets: [{
                    label: 'Actores',
                    data: [<?php echo "$aliados, $neutros, $opositores, $desconocidos"; ?>],
                    backgroundColor: [success, warning, error, muted]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>
