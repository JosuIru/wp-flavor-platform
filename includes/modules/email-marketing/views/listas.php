<?php
/**
 * Vista: Listas de suscriptores - Panel Administración
 *
 * Dashboard mejorado con estadísticas, filtros avanzados, paginación
 * y visualización de datos.
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// =====================================================
// CONFIGURACIÓN Y VERIFICACIÓN DE TABLAS
// =====================================================

$tabla_listas = $wpdb->prefix . 'flavor_em_listas';
$tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
$tabla_suscriptores_listas = $wpdb->prefix . 'flavor_em_suscriptor_lista';
$tabla_campanas = $wpdb->prefix . 'flavor_em_campanas';

// Verificar existencia de tablas
$tabla_listas_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_listas
    )
);

$tablas_email_disponibles = (bool) $tabla_listas_existe;

// =====================================================
// FUNCIONES HELPER
// =====================================================

/**
 * Obtener badge de tipo de lista
 */
function obtener_badge_tipo_lista($tipo) {
    $tipos = [
        'newsletter' => [
            'clase' => 'newsletter',
            'texto' => __('Newsletter', 'flavor-chat-ia'),
            'icono' => 'email-alt',
            'color' => '#3b82f6'
        ],
        'segmento' => [
            'clase' => 'segmento',
            'texto' => __('Segmento', 'flavor-chat-ia'),
            'icono' => 'filter',
            'color' => '#8b5cf6'
        ],
        'automatica' => [
            'clase' => 'automatica',
            'texto' => __('Automática', 'flavor-chat-ia'),
            'icono' => 'controls-repeat',
            'color' => '#10b981'
        ],
        'transaccional' => [
            'clase' => 'transaccional',
            'texto' => __('Transaccional', 'flavor-chat-ia'),
            'icono' => 'cart',
            'color' => '#f59e0b'
        ]
    ];

    $tipo_key = strtolower($tipo);
    return $tipos[$tipo_key] ?? ['clase' => 'otro', 'texto' => ucfirst($tipo), 'icono' => 'list-view', 'color' => '#6b7280'];
}

/**
 * Obtener badge de estado de lista
 */
function obtener_badge_estado_lista($estado) {
    $estados = [
        'activa' => [
            'clase' => 'success',
            'texto' => __('Activa', 'flavor-chat-ia'),
            'icono' => 'yes-alt'
        ],
        'pausada' => [
            'clase' => 'warning',
            'texto' => __('Pausada', 'flavor-chat-ia'),
            'icono' => 'controls-pause'
        ],
        'archivada' => [
            'clase' => 'secondary',
            'texto' => __('Archivada', 'flavor-chat-ia'),
            'icono' => 'archive'
        ]
    ];

    $estado_key = strtolower($estado);
    return $estados[$estado_key] ?? ['clase' => 'secondary', 'texto' => ucfirst($estado), 'icono' => 'marker'];
}

/**
 * Calcular nivel de engagement de la lista
 */
function calcular_engagement_lista($tasa_apertura, $tasa_clics) {
    $promedio = ($tasa_apertura + $tasa_clics) / 2;

    if ($promedio >= 30) {
        return ['nivel' => __('Excelente', 'flavor-chat-ia'), 'clase' => 'excelente', 'color' => '#10b981'];
    } elseif ($promedio >= 20) {
        return ['nivel' => __('Bueno', 'flavor-chat-ia'), 'clase' => 'bueno', 'color' => '#3b82f6'];
    } elseif ($promedio >= 10) {
        return ['nivel' => __('Regular', 'flavor-chat-ia'), 'clase' => 'regular', 'color' => '#f59e0b'];
    }
    return ['nivel' => __('Bajo', 'flavor-chat-ia'), 'clase' => 'bajo', 'color' => '#ef4444'];
}

/**
 * Calcular crecimiento mensual
 */
function calcular_crecimiento($actual, $anterior) {
    if ($anterior == 0) return $actual > 0 ? 100 : 0;
    return round((($actual - $anterior) / $anterior) * 100, 1);
}

// =====================================================
// PARÁMETROS DE FILTRADO Y PAGINACIÓN
// =====================================================

$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$items_por_pagina = 12;
$offset = ($pagina_actual - 1) * $items_por_pagina;

$filtro_busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_optin = isset($_GET['optin']) ? sanitize_text_field($_GET['optin']) : '';
$filtro_orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'suscriptores_desc';

// =====================================================
// CONSULTA DE DATOS REALES O FILTRADO DEMO
// =====================================================

if ($tablas_email_disponibles) {
    // Construir query con filtros
    $where_clauses = ["1=1"];
    $params = [];

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(nombre LIKE %s OR descripcion LIKE %s OR slug LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $params[] = $busqueda_like;
        $params[] = $busqueda_like;
        $params[] = $busqueda_like;
    }

    if (!empty($filtro_tipo)) {
        $where_clauses[] = "tipo = %s";
        $params[] = $filtro_tipo;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "estado = %s";
        $params[] = $filtro_estado;
    }

    if ($filtro_optin !== '') {
        $where_clauses[] = "doble_optin = %d";
        $params[] = intval($filtro_optin);
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Ordenamiento
    $order_sql = "total_suscriptores DESC";
    switch ($filtro_orden) {
        case 'nombre_asc':
            $order_sql = "nombre ASC";
            break;
        case 'nombre_desc':
            $order_sql = "nombre DESC";
            break;
        case 'suscriptores_asc':
            $order_sql = "total_suscriptores ASC";
            break;
        case 'apertura_desc':
            $order_sql = "tasa_apertura DESC";
            break;
        case 'fecha_desc':
            $order_sql = "fecha_creacion DESC";
            break;
        case 'fecha_asc':
            $order_sql = "fecha_creacion ASC";
            break;
    }

    // Query de estadísticas
    $estadisticas = [
        'total_listas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_listas"),
        'listas_activas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_listas WHERE estado = 'activa'"),
        'total_suscriptores' => $wpdb->get_var("SELECT COALESCE(SUM(total_suscriptores), 0) FROM $tabla_listas"),
        'suscriptores_activos' => $wpdb->get_var("SELECT COALESCE(SUM(suscriptores_activos), 0) FROM $tabla_listas"),
        'promedio_apertura' => $wpdb->get_var("SELECT COALESCE(AVG(tasa_apertura), 0) FROM $tabla_listas WHERE estado = 'activa'"),
        'nuevos_mes' => $wpdb->get_var("SELECT COALESCE(SUM(suscriptores_mes), 0) FROM $tabla_listas")
    ];

    // Conteo total
    $count_query = "SELECT COUNT(*) FROM $tabla_listas WHERE $where_sql";
    $total_items = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query);

    // Query principal
    $query = "SELECT * FROM $tabla_listas WHERE $where_sql ORDER BY $order_sql LIMIT %d OFFSET %d";
    $params[] = $items_por_pagina;
    $params[] = $offset;

    $listas = $wpdb->get_results($wpdb->prepare($query, $params));

} else {
    $estadisticas = [
        'total_listas' => 0,
        'listas_activas' => 0,
        'total_suscriptores' => 0,
        'suscriptores_activos' => 0,
        'promedio_apertura' => 0,
        'nuevos_mes' => 0,
    ];
    $total_items = 0;
    $listas = [];
}

$total_paginas = ceil($total_items / $items_por_pagina);

// Top listas para sidebar
$top_listas = $tablas_email_disponibles
    ? $wpdb->get_results("
        SELECT * FROM $tabla_listas
        WHERE estado = 'activa'
        ORDER BY total_suscriptores DESC
        LIMIT 5
    ")
    : [];

// Distribución por tipo para gráfico
$distribucion_tipo = [];
if ($tablas_email_disponibles) {
    $resultados = $wpdb->get_results("SELECT tipo, COUNT(*) as total FROM $tabla_listas GROUP BY tipo");
    foreach ($resultados as $r) {
        $distribucion_tipo[$r->tipo] = $r->total;
    }
}
?>

<div class="wrap flavor-em-listas">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-list-view"></span>
        <?php echo esc_html__('Listas de Suscriptores', 'flavor-chat-ia'); ?>
    </h1>
    <button type="button" class="page-title-action em-btn-nueva-lista">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php echo esc_html__('Nueva Lista', 'flavor-chat-ia'); ?>
    </button>

    <?php if (!$tablas_email_disponibles): ?>
    <div class="notice notice-info inline" style="margin: 15px 0;">
        <p>
            <span class="dashicons dashicons-info"></span>
            <?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Email Marketing.', 'flavor-chat-ia'); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-list-view"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['total_listas']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Listas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['total_suscriptores']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Suscriptores', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['promedio_apertura'], 1); ?>%</span>
                <span class="flavor-stat-label"><?php echo esc_html__('Tasa Apertura Prom.', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <span class="dashicons dashicons-trending-up"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value">+<?php echo number_format($estadisticas['nuevos_mes']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Nuevos Este Mes', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout principal con sidebar -->
    <div class="flavor-main-layout">
        <div class="flavor-main-content">
            <!-- Filtros -->
            <div class="flavor-filtros-card">
                <form method="get" class="flavor-filtros-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'flavor-em-listas'); ?>">

                    <div class="flavor-filtro-grupo">
                        <label for="buscar"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="buscar" name="buscar" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Nombre, descripción...', 'flavor-chat-ia'); ?>">
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="tipo"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></label>
                        <select id="tipo" name="tipo">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="newsletter" <?php selected($filtro_tipo, 'newsletter'); ?>><?php echo esc_html__('Newsletter', 'flavor-chat-ia'); ?></option>
                            <option value="segmento" <?php selected($filtro_tipo, 'segmento'); ?>><?php echo esc_html__('Segmento', 'flavor-chat-ia'); ?></option>
                            <option value="automatica" <?php selected($filtro_tipo, 'automatica'); ?>><?php echo esc_html__('Automática', 'flavor-chat-ia'); ?></option>
                            <option value="transaccional" <?php selected($filtro_tipo, 'transaccional'); ?>><?php echo esc_html__('Transaccional', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="estado"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                        <select id="estado" name="estado">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="activa" <?php selected($filtro_estado, 'activa'); ?>><?php echo esc_html__('Activa', 'flavor-chat-ia'); ?></option>
                            <option value="pausada" <?php selected($filtro_estado, 'pausada'); ?>><?php echo esc_html__('Pausada', 'flavor-chat-ia'); ?></option>
                            <option value="archivada" <?php selected($filtro_estado, 'archivada'); ?>><?php echo esc_html__('Archivada', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="optin"><?php echo esc_html__('Doble Opt-in', 'flavor-chat-ia'); ?></label>
                        <select id="optin" name="optin">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="1" <?php selected($filtro_optin, '1'); ?>><?php echo esc_html__('Sí', 'flavor-chat-ia'); ?></option>
                            <option value="0" <?php selected($filtro_optin, '0'); ?>><?php echo esc_html__('No', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="orden"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                        <select id="orden" name="orden">
                            <option value="suscriptores_desc" <?php selected($filtro_orden, 'suscriptores_desc'); ?>><?php echo esc_html__('Más suscriptores', 'flavor-chat-ia'); ?></option>
                            <option value="suscriptores_asc" <?php selected($filtro_orden, 'suscriptores_asc'); ?>><?php echo esc_html__('Menos suscriptores', 'flavor-chat-ia'); ?></option>
                            <option value="apertura_desc" <?php selected($filtro_orden, 'apertura_desc'); ?>><?php echo esc_html__('Mayor apertura', 'flavor-chat-ia'); ?></option>
                            <option value="nombre_asc" <?php selected($filtro_orden, 'nombre_asc'); ?>><?php echo esc_html__('Nombre A-Z', 'flavor-chat-ia'); ?></option>
                            <option value="fecha_desc" <?php selected($filtro_orden, 'fecha_desc'); ?>><?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-acciones">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_GET['page'] ?? 'flavor-em-listas'))); ?>" class="button">
                            <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Contador de resultados -->
            <div class="flavor-resultados-info">
                <span>
                    <?php
                    printf(
                        esc_html__('Mostrando %d-%d de %d listas', 'flavor-chat-ia'),
                        min($offset + 1, $total_items),
                        min($offset + $items_por_pagina, $total_items),
                        $total_items
                    );
                    ?>
                </span>
            </div>

            <!-- Grid de listas -->
            <?php if (empty($listas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-list-view"></span>
                    <h3><?php echo esc_html__('No se encontraron listas', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Intenta ajustar los filtros o crea una nueva lista.', 'flavor-chat-ia'); ?></p>
                    <button type="button" class="button button-primary button-hero em-btn-nueva-lista">
                        <?php echo esc_html__('Crear Lista', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="flavor-listas-grid">
                    <?php foreach ($listas as $lista):
                        $badge_tipo = obtener_badge_tipo_lista($lista->tipo);
                        $badge_estado = obtener_badge_estado_lista($lista->estado);
                        $engagement = calcular_engagement_lista($lista->tasa_apertura ?? 0, $lista->tasa_clics ?? 0);
                        $porcentaje_activos = $lista->total_suscriptores > 0
                            ? round(($lista->suscriptores_activos / $lista->total_suscriptores) * 100)
                            : 0;
                    ?>
                        <div class="flavor-lista-card <?php echo $lista->estado !== 'activa' ? 'flavor-lista-inactiva' : ''; ?>" data-id="<?php echo esc_attr($lista->id); ?>">
                            <div class="flavor-lista-header">
                                <div class="flavor-lista-badges">
                                    <span class="flavor-badge flavor-badge-tipo-<?php echo esc_attr($badge_tipo['clase']); ?>" style="background-color: <?php echo esc_attr($badge_tipo['color']); ?>;">
                                        <span class="dashicons dashicons-<?php echo esc_attr($badge_tipo['icono']); ?>"></span>
                                        <?php echo esc_html($badge_tipo['texto']); ?>
                                    </span>
                                    <span class="flavor-badge flavor-badge-estado-<?php echo esc_attr($badge_estado['clase']); ?>">
                                        <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                        <?php echo esc_html($badge_estado['texto']); ?>
                                    </span>
                                </div>
                                <?php if ($lista->doble_optin): ?>
                                    <span class="flavor-optin-badge" title="<?php echo esc_attr__('Doble opt-in activado', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-shield-alt"></span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-lista-body">
                                <h3 class="flavor-lista-nombre"><?php echo esc_html($lista->nombre); ?></h3>

                                <?php if (!empty($lista->descripcion)): ?>
                                    <p class="flavor-lista-descripcion"><?php echo esc_html(wp_trim_words($lista->descripcion, 15)); ?></p>
                                <?php endif; ?>

                                <!-- Stats principales -->
                                <div class="flavor-lista-stats-main">
                                    <div class="flavor-lista-stat-big">
                                        <span class="flavor-stat-numero"><?php echo number_format($lista->total_suscriptores ?? 0); ?></span>
                                        <span class="flavor-stat-etiqueta"><?php echo esc_html__('Suscriptores', 'flavor-chat-ia'); ?></span>
                                    </div>
                                    <div class="flavor-lista-metricas">
                                        <div class="flavor-metrica">
                                            <span class="dashicons dashicons-email-alt"></span>
                                            <strong><?php echo number_format($lista->tasa_apertura ?? 0, 1); ?>%</strong>
                                            <span><?php echo esc_html__('Apertura', 'flavor-chat-ia'); ?></span>
                                        </div>
                                        <div class="flavor-metrica">
                                            <span class="dashicons dashicons-admin-links"></span>
                                            <strong><?php echo number_format($lista->tasa_clics ?? 0, 1); ?>%</strong>
                                            <span><?php echo esc_html__('Clics', 'flavor-chat-ia'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Barra de suscriptores activos -->
                                <div class="flavor-lista-activos">
                                    <div class="flavor-activos-header">
                                        <span><?php echo esc_html__('Suscriptores activos', 'flavor-chat-ia'); ?></span>
                                        <strong><?php echo $porcentaje_activos; ?>%</strong>
                                    </div>
                                    <div class="flavor-progress-bar">
                                        <div class="flavor-progress-fill" style="width: <?php echo $porcentaje_activos; ?>%; background-color: <?php echo $engagement['color']; ?>;"></div>
                                    </div>
                                    <div class="flavor-activos-footer">
                                        <span><?php echo number_format($lista->suscriptores_activos ?? 0); ?> de <?php echo number_format($lista->total_suscriptores ?? 0); ?></span>
                                        <span class="flavor-engagement flavor-engagement-<?php echo esc_attr($engagement['clase']); ?>"><?php echo esc_html($engagement['nivel']); ?></span>
                                    </div>
                                </div>

                                <!-- Info adicional -->
                                <div class="flavor-lista-info">
                                    <div class="flavor-info-item">
                                        <span class="dashicons dashicons-megaphone"></span>
                                        <span><?php echo number_format($lista->campanas_enviadas ?? 0); ?> <?php echo esc_html__('campañas', 'flavor-chat-ia'); ?></span>
                                    </div>
                                    <?php if (!empty($lista->ultima_campana)): ?>
                                    <div class="flavor-info-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($lista->ultima_campana))); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Shortcode -->
                                <div class="flavor-lista-shortcode">
                                    <code>[em_formulario_suscripcion lista="<?php echo esc_attr($lista->slug); ?>"]</code>
                                    <button type="button" class="flavor-copiar-shortcode" data-shortcode='[em_formulario_suscripcion lista="<?php echo esc_attr($lista->slug); ?>"]' title="<?php echo esc_attr__('Copiar shortcode', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="flavor-lista-footer">
                                <a href="<?php echo admin_url('admin.php?page=flavor-em-suscriptores&lista=' . $lista->id); ?>" class="button button-small">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php echo esc_html__('Ver suscriptores', 'flavor-chat-ia'); ?>
                                </a>
                                <button type="button" class="button button-small em-btn-editar-lista" data-id="<?php echo esc_attr($lista->id); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <?php if ($lista->slug !== 'newsletter-principal'): ?>
                                    <button type="button" class="button button-small em-btn-eliminar-lista" data-id="<?php echo esc_attr($lista->id); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flavor-paginacion">
                        <?php
                        $pagination_args = [
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $pagina_actual,
                            'total' => $total_paginas,
                            'prev_text' => '&laquo; ' . __('Anterior', 'flavor-chat-ia'),
                            'next_text' => __('Siguiente', 'flavor-chat-ia') . ' &raquo;',
                            'type' => 'list'
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="flavor-sidebar">
            <!-- Top Listas -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php echo esc_html__('Listas Más Grandes', 'flavor-chat-ia'); ?>
                </h3>
                <?php if (!empty($top_listas)): ?>
                    <ul class="flavor-top-list">
                        <?php foreach ($top_listas as $index => $top): ?>
                            <li>
                                <span class="flavor-top-posicion"><?php echo $index + 1; ?></span>
                                <div class="flavor-top-info">
                                    <strong><?php echo esc_html($top->nombre); ?></strong>
                                    <span><?php echo number_format($top->total_suscriptores ?? 0); ?> suscriptores</span>
                                </div>
                                <span class="flavor-top-tasa"><?php echo number_format($top->tasa_apertura ?? 0, 1); ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="flavor-no-data"><?php echo esc_html__('Sin datos disponibles', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Gráfico de Tipos -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Por Tipo', 'flavor-chat-ia'); ?>
                </h3>
                <canvas id="graficoTipos" height="200"></canvas>
            </div>

            <!-- Info GDPR -->
            <div class="flavor-sidebar-card flavor-sidebar-gdpr">
                <h3>
                    <span class="dashicons dashicons-shield"></span>
                    <?php echo esc_html__('Cumplimiento GDPR', 'flavor-chat-ia'); ?>
                </h3>
                <ul class="flavor-gdpr-checklist">
                    <li>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__('Doble opt-in disponible', 'flavor-chat-ia'); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__('Desuscripción en 1 clic', 'flavor-chat-ia'); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__('Registro de consentimiento', 'flavor-chat-ia'); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html__('Exportación de datos', 'flavor-chat-ia'); ?>
                    </li>
                </ul>
            </div>

            <!-- Acciones rápidas -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php echo esc_html__('Acciones Rápidas', 'flavor-chat-ia'); ?>
                </h3>
                <div class="flavor-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-importar'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php echo esc_html__('Importar CSV', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-exportar'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php echo esc_html__('Exportar Listas', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-limpiar'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-trash"></span>
                        <?php echo esc_html__('Limpiar Inactivos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de lista -->
<div class="flavor-modal" id="em-modal-lista" style="display:none;">
    <div class="flavor-modal-overlay em-modal-close"></div>
    <div class="flavor-modal-content">
        <button type="button" class="flavor-modal-close-btn em-modal-close">&times;</button>
        <h3 id="em-modal-lista-titulo"><?php echo esc_html__('Nueva Lista', 'flavor-chat-ia'); ?></h3>

        <form id="em-form-lista">
            <input type="hidden" name="lista_id" id="em-lista-id" value="">

            <div class="flavor-form-grid">
                <div class="flavor-form-group flavor-form-full">
                    <label for="em-lista-nombre"><?php echo esc_html__('Nombre de la lista', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="em-lista-nombre" name="nombre" required>
                </div>

                <div class="flavor-form-group flavor-form-full">
                    <label for="em-lista-descripcion"><?php echo esc_html__('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea id="em-lista-descripcion" name="descripcion" rows="3"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="em-lista-tipo"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></label>
                    <select id="em-lista-tipo" name="tipo">
                        <option value="newsletter"><?php echo esc_html__('Newsletter', 'flavor-chat-ia'); ?></option>
                        <option value="segmento"><?php echo esc_html__('Segmento', 'flavor-chat-ia'); ?></option>
                        <option value="automatica"><?php echo esc_html__('Automática', 'flavor-chat-ia'); ?></option>
                        <option value="transaccional"><?php echo esc_html__('Transaccional', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="em-lista-estado"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="em-lista-estado" name="estado">
                        <option value="activa"><?php echo esc_html__('Activa', 'flavor-chat-ia'); ?></option>
                        <option value="pausada"><?php echo esc_html__('Pausada', 'flavor-chat-ia'); ?></option>
                        <option value="archivada"><?php echo esc_html__('Archivada', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group flavor-form-full">
                    <label class="flavor-checkbox-label">
                        <input type="checkbox" name="doble_optin" id="em-lista-doble-optin" value="1" checked>
                        <?php echo esc_html__('Requerir confirmación por email (doble opt-in)', 'flavor-chat-ia'); ?>
                    </label>
                    <p class="description"><?php echo esc_html__('Recomendado para cumplir con GDPR y mejorar la calidad de la lista.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <div class="flavor-modal-actions">
                <button type="button" class="button em-modal-close"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
/* Variables */
:root {
    --flavor-primary: #2271b1;
    --flavor-success: #00a32a;
    --flavor-warning: #dba617;
    --flavor-danger: #d63638;
    --flavor-info: #72aee6;
    --flavor-secondary: #787c82;
}

.flavor-em-listas {
    max-width: 1600px;
}

.flavor-em-listas .page-title-action {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Stats Grid */
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.flavor-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.flavor-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-stat-content {
    display: flex;
    flex-direction: column;
}

.flavor-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1.2;
}

.flavor-stat-label {
    font-size: 13px;
    color: #646970;
}

/* Main Layout */
.flavor-main-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
    margin-top: 20px;
}

@media (max-width: 1200px) {
    .flavor-main-layout {
        grid-template-columns: 1fr;
    }
}

/* Filtros */
.flavor-filtros-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-filtros-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.flavor-filtro-grupo {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 140px;
}

.flavor-filtro-grupo label {
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

.flavor-filtro-grupo input,
.flavor-filtro-grupo select {
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    font-size: 13px;
}

.flavor-filtro-acciones {
    display: flex;
    gap: 10px;
    margin-left: auto;
}

.flavor-filtro-acciones .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Resultados info */
.flavor-resultados-info {
    padding: 10px 0;
    color: #646970;
    font-size: 13px;
}

/* Grid de listas */
.flavor-listas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}

.flavor-lista-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-lista-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.flavor-lista-inactiva {
    opacity: 0.7;
}

.flavor-lista-header {
    padding: 15px 20px;
    background: #f9fafb;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-lista-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
}

.flavor-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Badges de estado */
.flavor-badge-estado-success { background: rgba(0,163,42,0.15); color: #00a32a; }
.flavor-badge-estado-warning { background: rgba(219,166,23,0.15); color: #996800; }
.flavor-badge-estado-secondary { background: rgba(120,124,130,0.15); color: #787c82; }

.flavor-optin-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-optin-badge .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #fff;
}

.flavor-lista-body {
    padding: 20px;
}

.flavor-lista-nombre {
    margin: 0 0 8px 0;
    font-size: 18px;
    color: #1d2327;
}

.flavor-lista-descripcion {
    font-size: 13px;
    color: #646970;
    margin: 0 0 15px 0;
    line-height: 1.5;
}

/* Stats principales */
.flavor-lista-stats-main {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 15px;
}

.flavor-lista-stat-big {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-right: 20px;
    border-right: 1px solid #e5e7eb;
}

.flavor-stat-numero {
    font-size: 32px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1;
}

.flavor-stat-etiqueta {
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
    margin-top: 4px;
}

.flavor-lista-metricas {
    display: flex;
    gap: 20px;
    flex: 1;
}

.flavor-metrica {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.flavor-metrica .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: var(--flavor-primary);
    margin-bottom: 4px;
}

.flavor-metrica strong {
    font-size: 16px;
    color: #1d2327;
}

.flavor-metrica span {
    font-size: 11px;
    color: #646970;
}

/* Barra de activos */
.flavor-lista-activos {
    margin-bottom: 15px;
}

.flavor-activos-header {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-bottom: 6px;
}

.flavor-activos-header span {
    color: #646970;
}

.flavor-activos-header strong {
    color: #1d2327;
}

.flavor-progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.flavor-activos-footer {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-top: 6px;
    color: #646970;
}

.flavor-engagement {
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
}

.flavor-engagement-excelente { background: rgba(16,185,129,0.15); color: #059669; }
.flavor-engagement-bueno { background: rgba(59,130,246,0.15); color: #2563eb; }
.flavor-engagement-regular { background: rgba(245,158,11,0.15); color: #d97706; }
.flavor-engagement-bajo { background: rgba(239,68,68,0.15); color: #dc2626; }

/* Info adicional */
.flavor-lista-info {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 12px;
    color: #646970;
}

.flavor-info-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-info-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Shortcode */
.flavor-lista-shortcode {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f3f4f6;
    padding: 10px 12px;
    border-radius: 6px;
}

.flavor-lista-shortcode code {
    flex: 1;
    font-size: 11px;
    background: none;
    padding: 0;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-copiar-shortcode {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #646970;
    transition: color 0.2s;
}

.flavor-copiar-shortcode:hover {
    color: var(--flavor-primary);
}

.flavor-lista-footer {
    padding: 15px 20px;
    background: #f9fafb;
    border-top: 1px solid #f0f0f1;
    display: flex;
    gap: 10px;
    justify-content: flex-start;
}

.flavor-lista-footer .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Empty state */
.flavor-empty-state {
    background: #fff;
    border-radius: 12px;
    padding: 60px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #dcdcde;
}

.flavor-empty-state h3 {
    margin: 20px 0 10px;
    color: #1d2327;
}

.flavor-empty-state p {
    color: #646970;
    margin-bottom: 20px;
}

/* Sidebar */
.flavor-sidebar-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-sidebar-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px 0;
    font-size: 15px;
    color: #1d2327;
}

.flavor-sidebar-card h3 .dashicons {
    color: var(--flavor-primary);
}

.flavor-top-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-top-list li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f1;
}

.flavor-top-list li:last-child {
    border-bottom: none;
}

.flavor-top-posicion {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    flex-shrink: 0;
}

.flavor-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-top-info strong {
    display: block;
    font-size: 13px;
    color: #1d2327;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-top-info span {
    font-size: 11px;
    color: #646970;
}

.flavor-top-tasa {
    font-size: 12px;
    font-weight: 600;
    color: var(--flavor-success);
}

/* GDPR */
.flavor-gdpr-checklist {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-gdpr-checklist li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 13px;
    color: #1d2327;
}

.flavor-gdpr-checklist .dashicons {
    color: var(--flavor-success);
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Quick actions */
.flavor-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-quick-actions .button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.flavor-no-data {
    color: #646970;
    font-size: 13px;
    text-align: center;
    padding: 20px;
}

/* Paginación */
.flavor-paginacion {
    margin-top: 25px;
    display: flex;
    justify-content: center;
}

.flavor-paginacion .page-numbers {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 5px;
}

.flavor-paginacion .page-numbers li a,
.flavor-paginacion .page-numbers li span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    color: #1d2327;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s;
}

.flavor-paginacion .page-numbers li a:hover {
    background: var(--flavor-primary);
    border-color: var(--flavor-primary);
    color: #fff;
}

.flavor-paginacion .page-numbers li span.current {
    background: var(--flavor-primary);
    border-color: var(--flavor-primary);
    color: #fff;
}

/* Modal */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
}

.flavor-modal-content {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    width: 90%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.flavor-modal-close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 28px;
    color: #646970;
    cursor: pointer;
    line-height: 1;
}

.flavor-modal-close-btn:hover {
    color: #d63638;
}

.flavor-modal-content h3 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #1d2327;
}

.flavor-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.flavor-form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.flavor-form-full {
    grid-column: 1 / -1;
}

.flavor-form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #1d2327;
}

.flavor-form-group input,
.flavor-form-group select,
.flavor-form-group textarea {
    padding: 10px 12px;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    font-size: 14px;
}

.flavor-form-group textarea {
    resize: vertical;
}

.flavor-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal !important;
    cursor: pointer;
}

.flavor-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.flavor-form-group .description {
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.flavor-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f1;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de tipos
    const ctx = document.getElementById('graficoTipos');
    if (ctx) {
        const distribucion = <?php echo json_encode($distribucion_tipo); ?>;
        const labels = Object.keys(distribucion).map(t => t.charAt(0).toUpperCase() + t.slice(1));
        const data = Object.values(distribucion);

        const colores = {
            'Newsletter': '#3b82f6',
            'Segmento': '#8b5cf6',
            'Automatica': '#10b981',
            'Transaccional': '#f59e0b'
        };

        const backgroundColors = labels.map(l => colores[l] || '#6b7280');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Abrir modal nueva lista
    document.querySelectorAll('.em-btn-nueva-lista').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('em-modal-lista-titulo').textContent = '<?php echo esc_js(__('Nueva Lista', 'flavor-chat-ia')); ?>';
            document.getElementById('em-form-lista').reset();
            document.getElementById('em-lista-id').value = '';
            document.getElementById('em-modal-lista').style.display = 'flex';
        });
    });

    // Cerrar modal
    document.querySelectorAll('.em-modal-close').forEach(el => {
        el.addEventListener('click', function() {
            document.getElementById('em-modal-lista').style.display = 'none';
        });
    });

    // Copiar shortcode
    document.querySelectorAll('.flavor-copiar-shortcode').forEach(btn => {
        btn.addEventListener('click', function() {
            const shortcode = this.dataset.shortcode;
            navigator.clipboard.writeText(shortcode).then(() => {
                const icon = this.querySelector('.dashicons');
                icon.classList.remove('dashicons-clipboard');
                icon.classList.add('dashicons-yes');
                setTimeout(() => {
                    icon.classList.remove('dashicons-yes');
                    icon.classList.add('dashicons-clipboard');
                }, 2000);
            });
        });
    });

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('em-modal-lista').style.display = 'none';
        }
    });

    // Editar lista
    document.querySelectorAll('.em-btn-editar-lista').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            window.location.href = '<?php echo admin_url('admin.php?page=flavor-em-listas&editar='); ?>' + id;
        });
    });

    // Eliminar lista
    document.querySelectorAll('.em-btn-eliminar-lista').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('<?php echo esc_js(__('¿Estás seguro de eliminar esta lista? Esta acción no se puede deshacer.', 'flavor-chat-ia')); ?>')) {
                const id = this.dataset.id;
                window.location.href = '<?php echo admin_url('admin.php?page=flavor-em-listas&eliminar='); ?>' + id + '&_wpnonce=<?php echo wp_create_nonce('eliminar_lista'); ?>';
            }
        });
    });
});
</script>
