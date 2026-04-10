<?php
/**
 * Vista Gestión de Talleres - Dashboard Administrativo
 *
 * Panel completo con estadísticas, filtros avanzados, paginación
 * y visualización moderna de talleres.
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';
$tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

// =====================================================
// FUNCIONES HELPER
// =====================================================

/**
 * Obtiene el badge de estado del taller
 */
function obtener_badge_estado_taller($estado) {
    $estados = [
        'borrador' => [
            'clase' => 'secondary',
            'texto' => __('Borrador', 'flavor-platform'),
            'icono' => 'edit'
        ],
        'publicado' => [
            'clase' => 'info',
            'texto' => __('Publicado', 'flavor-platform'),
            'icono' => 'visibility'
        ],
        'confirmado' => [
            'clase' => 'success',
            'texto' => __('Confirmado', 'flavor-platform'),
            'icono' => 'yes-alt'
        ],
        'en_curso' => [
            'clase' => 'warning',
            'texto' => __('En Curso', 'flavor-platform'),
            'icono' => 'controls-play'
        ],
        'finalizado' => [
            'clase' => 'primary',
            'texto' => __('Finalizado', 'flavor-platform'),
            'icono' => 'flag'
        ],
        'cancelado' => [
            'clase' => 'danger',
            'texto' => __('Cancelado', 'flavor-platform'),
            'icono' => 'dismiss'
        ]
    ];

    $estado_key = strtolower(str_replace(' ', '_', $estado));
    return $estados[$estado_key] ?? [
        'clase' => 'secondary',
        'texto' => ucfirst(str_replace('_', ' ', $estado)),
        'icono' => 'marker'
    ];
}

/**
 * Obtiene el badge de nivel del taller
 */
function obtener_badge_nivel_taller($nivel) {
    $niveles = [
        'basico' => [
            'clase' => 'success',
            'texto' => __('Básico', 'flavor-platform'),
            'icono' => '🌱'
        ],
        'intermedio' => [
            'clase' => 'info',
            'texto' => __('Intermedio', 'flavor-platform'),
            'icono' => '🌿'
        ],
        'avanzado' => [
            'clase' => 'warning',
            'texto' => __('Avanzado', 'flavor-platform'),
            'icono' => '🌳'
        ],
        'experto' => [
            'clase' => 'danger',
            'texto' => __('Experto', 'flavor-platform'),
            'icono' => '🔥'
        ]
    ];

    $nivel_key = strtolower(str_replace(' ', '_', $nivel));
    return $niveles[$nivel_key] ?? [
        'clase' => 'secondary',
        'texto' => ucfirst($nivel ?: __('Sin definir', 'flavor-platform')),
        'icono' => '📚'
    ];
}

/**
 * Obtiene el badge de modalidad
 */
function obtener_badge_modalidad_taller($modalidad) {
    $modalidades = [
        'presencial' => [
            'clase' => 'success',
            'texto' => __('Presencial', 'flavor-platform'),
            'icono' => 'location'
        ],
        'online' => [
            'clase' => 'info',
            'texto' => __('Online', 'flavor-platform'),
            'icono' => 'video-alt2'
        ],
        'hibrido' => [
            'clase' => 'warning',
            'texto' => __('Híbrido', 'flavor-platform'),
            'icono' => 'randomize'
        ]
    ];

    $modalidad_key = strtolower(str_replace(' ', '_', $modalidad));
    return $modalidades[$modalidad_key] ?? [
        'clase' => 'secondary',
        'texto' => ucfirst($modalidad ?: __('Sin definir', 'flavor-platform')),
        'icono' => 'admin-generic'
    ];
}

/**
 * Calcula el porcentaje de ocupación
 */
function calcular_ocupacion_taller($inscritos, $maximo) {
    if ($maximo <= 0) {
        return ['porcentaje' => 0, 'clase' => 'secondary', 'texto' => __('Sin límite', 'flavor-platform')];
    }

    $porcentaje = round(($inscritos / $maximo) * 100);

    if ($porcentaje >= 100) {
        return ['porcentaje' => 100, 'clase' => 'danger', 'texto' => __('Completo', 'flavor-platform')];
    } elseif ($porcentaje >= 80) {
        return ['porcentaje' => $porcentaje, 'clase' => 'warning', 'texto' => __('Casi lleno', 'flavor-platform')];
    } elseif ($porcentaje >= 50) {
        return ['porcentaje' => $porcentaje, 'clase' => 'info', 'texto' => __('Disponible', 'flavor-platform')];
    } else {
        return ['porcentaje' => $porcentaje, 'clase' => 'success', 'texto' => __('Plazas libres', 'flavor-platform')];
    }
}

/**
 * Formatea el precio del taller
 */
function formatear_precio_taller($precio) {
    if ($precio <= 0) {
        return ['texto' => __('Gratis', 'flavor-platform'), 'clase' => 'success'];
    }
    return ['texto' => number_format($precio, 2, ',', '.') . ' €', 'clase' => 'info'];
}

// =====================================================
// VERIFICACIÓN DE TABLA Y DATOS DEMO
// =====================================================

$tabla_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_talleres
    )
) > 0;

if (!$tabla_existe) {
    $categorias_disponibles = [];
    $niveles_disponibles = [];
    $total_talleres = 0;
    $talleres_activos = 0;
    $total_inscritos = 0;
    $ingresos_estimados = 0;
    $por_categoria = [];
    $talleres_destacados = [];
    $usando_datos_demo = false;
} else {
    // =====================================================
    // DATOS REALES DE LA BASE DE DATOS
    // =====================================================

    // Estadísticas globales
    $total_talleres = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_talleres}");
    $talleres_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_talleres} WHERE estado IN ('publicado', 'confirmado', 'en_curso')");
    $total_inscritos = (int) $wpdb->get_var("SELECT COALESCE(SUM(inscritos_actuales), 0) FROM {$tabla_talleres}");
    $ingresos_estimados = (float) $wpdb->get_var("SELECT COALESCE(SUM(precio * inscritos_actuales), 0) FROM {$tabla_talleres}");

    // Categorías disponibles
    $categorias_disponibles = $wpdb->get_col("SELECT DISTINCT categoria FROM {$tabla_talleres} WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");

    // Niveles disponibles
    $niveles_disponibles = $wpdb->get_col("SELECT DISTINCT nivel FROM {$tabla_talleres} WHERE nivel IS NOT NULL AND nivel != '' ORDER BY nivel");

    // Por categoría
    $por_categoria_raw = $wpdb->get_results("
        SELECT categoria, COUNT(*) as total
        FROM {$tabla_talleres}
        WHERE categoria IS NOT NULL AND categoria != ''
        GROUP BY categoria
        ORDER BY total DESC
    ", ARRAY_A);
    $por_categoria = [];
    foreach ($por_categoria_raw as $row) {
        $por_categoria[$row['categoria']] = (int) $row['total'];
    }

    // Talleres destacados
    $talleres_destacados = $wpdb->get_results("
        SELECT t.*, u.display_name as organizador
        FROM {$tabla_talleres} t
        LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
        WHERE t.destacado = 1 AND t.estado IN ('publicado', 'confirmado', 'en_curso')
        ORDER BY t.fecha_inicio ASC
        LIMIT 5
    ");

    $usando_datos_demo = false;
}

// =====================================================
// FILTROS Y PAGINACIÓN
// =====================================================

$busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$filtro_nivel = isset($_GET['nivel']) ? sanitize_text_field($_GET['nivel']) : '';
$filtro_modalidad = isset($_GET['modalidad']) ? sanitize_text_field($_GET['modalidad']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'recientes';

$items_por_pagina = 12;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

if ($tabla_existe) {
    // Construir WHERE dinámico
    $where_conditions = ['1=1'];
    $where_values = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(t.titulo LIKE %s OR t.descripcion LIKE %s OR u.display_name LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    if (!empty($filtro_estado)) {
        $where_conditions[] = "t.estado = %s";
        $where_values[] = $filtro_estado;
    }

    if (!empty($filtro_categoria)) {
        $where_conditions[] = "t.categoria = %s";
        $where_values[] = $filtro_categoria;
    }

    if (!empty($filtro_nivel)) {
        $where_conditions[] = "t.nivel = %s";
        $where_values[] = $filtro_nivel;
    }

    if (!empty($filtro_modalidad)) {
        $where_conditions[] = "t.modalidad = %s";
        $where_values[] = $filtro_modalidad;
    }

    $where_sql = implode(' AND ', $where_conditions);

    // Orden
    switch ($orden) {
        case 'nombre':
            $order_sql = 't.titulo ASC';
            break;
        case 'fecha_inicio':
            $order_sql = 't.fecha_inicio ASC';
            break;
        case 'precio_asc':
            $order_sql = 't.precio ASC';
            break;
        case 'precio_desc':
            $order_sql = 't.precio DESC';
            break;
        case 'ocupacion':
            $order_sql = '(t.inscritos_actuales / NULLIF(t.max_participantes, 0)) DESC';
            break;
        case 'recientes':
        default:
            $order_sql = 't.fecha_creacion DESC';
            break;
    }

    // Total items
    $count_query = "SELECT COUNT(*) FROM {$tabla_talleres} t
                    LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
                    WHERE {$where_sql}";

    if (!empty($where_values)) {
        $total_items = (int) $wpdb->get_var($wpdb->prepare($count_query, $where_values));
    } else {
        $total_items = (int) $wpdb->get_var($count_query);
    }

    $total_paginas = ceil($total_items / $items_por_pagina);

    // Query principal
    $query = "SELECT t.*, u.display_name as organizador
              FROM {$tabla_talleres} t
              LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
              WHERE {$where_sql}
              ORDER BY {$order_sql}
              LIMIT %d OFFSET %d";

    $where_values[] = $items_por_pagina;
    $where_values[] = $offset;

    $talleres = $wpdb->get_results($wpdb->prepare($query, $where_values));
} else {
    $total_items = 0;
    $total_paginas = 0;
    $talleres = [];
}

// Preparar datos para el gráfico
$categorias_chart = array_keys($por_categoria);
$valores_chart = array_values($por_categoria);
$colores_categorias = [
    'agricultura' => '#10b981',
    'manualidades' => '#f59e0b',
    'tecnologia' => '#3b82f6',
    'cocina' => '#ef4444',
    'bienestar' => '#8b5cf6',
    'arte' => '#ec4899',
    'salud' => '#14b8a6',
    'idiomas' => '#6366f1',
    'musica' => '#f97316',
    'deporte' => '#22c55e'
];
$colores_chart = array_map(fn($cat) => $colores_categorias[$cat] ?? '#64748b', $categorias_chart);

// Estados disponibles
$estados_disponibles = ['borrador', 'publicado', 'confirmado', 'en_curso', 'finalizado', 'cancelado'];
$modalidades_disponibles = ['presencial', 'online', 'hibrido'];
?>

<div class="wrap flavor-talleres-dashboard">
    <!-- ===== CABECERA ===== -->
    <div class="flavor-dashboard-header">
        <div class="flavor-header-content">
            <h1>
                <span class="dashicons dashicons-welcome-learn-more"></span>
                <?php esc_html_e('Gestión de Talleres', 'flavor-platform'); ?>
            </h1>
            <p class="flavor-header-subtitle">
                <?php esc_html_e('Administra talleres, cursos y formaciones de la comunidad', 'flavor-platform'); ?>
            </p>
        </div>
        <div class="flavor-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=nuevo')); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Nuevo Taller', 'flavor-platform'); ?>
            </a>
        </div>
    </div>

    <?php if (!$tabla_existe): ?>
    <div class="notice notice-info is-dismissible" style="margin: 20px 0;">
        <p>
            <span class="dashicons dashicons-info"></span>
            <strong><?php esc_html_e('Sin datos:', 'flavor-platform'); ?></strong>
            <?php esc_html_e('No hay tablas del módulo Talleres disponibles todavía.', 'flavor-platform'); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- ===== TARJETAS DE ESTADÍSTICAS ===== -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card flavor-stat-primary">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_talleres); ?></span>
                <span class="flavor-stat-label"><?php esc_html_e('Total Talleres', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-success">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($talleres_activos); ?></span>
                <span class="flavor-stat-label"><?php esc_html_e('Talleres Activos', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-info">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_inscritos); ?></span>
                <span class="flavor-stat-label"><?php esc_html_e('Total Inscritos', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-warning">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($ingresos_estimados, 0, ',', '.'); ?> €</span>
                <span class="flavor-stat-label"><?php esc_html_e('Ingresos Estimados', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="flavor-filters-card">
        <form method="get" class="flavor-filters-form">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'flavor-chat-talleres'); ?>">
            <input type="hidden" name="tab" value="talleres">

            <div class="flavor-filter-group flavor-filter-search">
                <label for="filter-search">
                    <span class="dashicons dashicons-search"></span>
                </label>
                <input type="text" id="filter-search" name="s"
                       value="<?php echo esc_attr($busqueda); ?>"
                       placeholder="<?php esc_attr_e('Buscar talleres...', 'flavor-platform'); ?>">
            </div>

            <div class="flavor-filter-group">
                <select name="estado" id="filter-estado">
                    <option value=""><?php esc_html_e('Todos los estados', 'flavor-platform'); ?></option>
                    <?php foreach ($estados_disponibles as $estado_opcion): ?>
                        <option value="<?php echo esc_attr($estado_opcion); ?>" <?php selected($filtro_estado, $estado_opcion); ?>>
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $estado_opcion))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-filter-group">
                <select name="categoria" id="filter-categoria">
                    <option value=""><?php esc_html_e('Todas las categorías', 'flavor-platform'); ?></option>
                    <?php foreach ($categorias_disponibles as $cat_opcion): ?>
                        <option value="<?php echo esc_attr($cat_opcion); ?>" <?php selected($filtro_categoria, $cat_opcion); ?>>
                            <?php echo esc_html(ucfirst($cat_opcion)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-filter-group">
                <select name="nivel" id="filter-nivel">
                    <option value=""><?php esc_html_e('Todos los niveles', 'flavor-platform'); ?></option>
                    <?php foreach (['basico', 'intermedio', 'avanzado', 'experto'] as $nivel_opcion): ?>
                        <option value="<?php echo esc_attr($nivel_opcion); ?>" <?php selected($filtro_nivel, $nivel_opcion); ?>>
                            <?php echo esc_html(ucfirst($nivel_opcion)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-filter-group">
                <select name="modalidad" id="filter-modalidad">
                    <option value=""><?php esc_html_e('Todas las modalidades', 'flavor-platform'); ?></option>
                    <?php foreach ($modalidades_disponibles as $mod_opcion): ?>
                        <option value="<?php echo esc_attr($mod_opcion); ?>" <?php selected($filtro_modalidad, $mod_opcion); ?>>
                            <?php echo esc_html(ucfirst($mod_opcion)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-filter-group">
                <select name="orden" id="filter-orden">
                    <option value="recientes" <?php selected($orden, 'recientes'); ?>><?php esc_html_e('Más recientes', 'flavor-platform'); ?></option>
                    <option value="nombre" <?php selected($orden, 'nombre'); ?>><?php esc_html_e('Nombre A-Z', 'flavor-platform'); ?></option>
                    <option value="fecha_inicio" <?php selected($orden, 'fecha_inicio'); ?>><?php esc_html_e('Fecha inicio', 'flavor-platform'); ?></option>
                    <option value="precio_asc" <?php selected($orden, 'precio_asc'); ?>><?php esc_html_e('Precio menor', 'flavor-platform'); ?></option>
                    <option value="precio_desc" <?php selected($orden, 'precio_desc'); ?>><?php esc_html_e('Precio mayor', 'flavor-platform'); ?></option>
                    <option value="ocupacion" <?php selected($orden, 'ocupacion'); ?>><?php esc_html_e('Más ocupados', 'flavor-platform'); ?></option>
                </select>
            </div>

            <div class="flavor-filter-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span>
                    <?php esc_html_e('Filtrar', 'flavor-platform'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_GET['page'] ?? 'flavor-chat-talleres') . '&tab=talleres')); ?>" class="button">
                    <?php esc_html_e('Limpiar', 'flavor-platform'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="flavor-main-content">
        <div class="flavor-content-area">
            <?php if (empty($talleres)): ?>
                <!-- Estado vacío -->
                <div class="flavor-empty-state">
                    <div class="flavor-empty-icon">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                    </div>
                    <h3><?php esc_html_e('No se encontraron talleres', 'flavor-platform'); ?></h3>
                    <p><?php esc_html_e('Ajusta los filtros o crea un nuevo taller para comenzar.', 'flavor-platform'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=nuevo')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Crear Primer Taller', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- Info de resultados -->
                <div class="flavor-results-info">
                    <span class="flavor-results-count">
                        <?php printf(
                            esc_html__('Mostrando %1$d-%2$d de %3$d talleres', 'flavor-platform'),
                            $offset + 1,
                            min($offset + $items_por_pagina, $total_items),
                            $total_items
                        ); ?>
                    </span>
                </div>

                <!-- Grid de talleres -->
                <div class="flavor-talleres-grid">
                    <?php foreach ($talleres as $taller):
                        $estado_badge = obtener_badge_estado_taller($taller->estado);
                        $nivel_badge = obtener_badge_nivel_taller($taller->nivel ?? 'basico');
                        $modalidad_badge = obtener_badge_modalidad_taller($taller->modalidad ?? 'presencial');
                        $ocupacion = calcular_ocupacion_taller($taller->inscritos_actuales, $taller->max_participantes);
                        $precio_info = formatear_precio_taller($taller->precio);
                    ?>
                        <article class="flavor-taller-card <?php echo ($taller->destacado ?? false) ? 'destacado' : ''; ?>">
                            <?php if ($taller->destacado ?? false): ?>
                                <div class="flavor-taller-destacado-badge">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <?php esc_html_e('Destacado', 'flavor-platform'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="flavor-taller-header">
                                <div class="flavor-taller-badges">
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_badge['clase']); ?>">
                                        <span class="dashicons dashicons-<?php echo esc_attr($estado_badge['icono']); ?>"></span>
                                        <?php echo esc_html($estado_badge['texto']); ?>
                                    </span>
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($modalidad_badge['clase']); ?>">
                                        <span class="dashicons dashicons-<?php echo esc_attr($modalidad_badge['icono']); ?>"></span>
                                        <?php echo esc_html($modalidad_badge['texto']); ?>
                                    </span>
                                </div>
                                <span class="flavor-taller-id">#<?php echo esc_html($taller->id); ?></span>
                            </div>

                            <div class="flavor-taller-content">
                                <h3 class="flavor-taller-titulo">
                                    <?php echo esc_html($taller->titulo); ?>
                                </h3>

                                <p class="flavor-taller-descripcion">
                                    <?php echo esc_html(wp_trim_words($taller->descripcion, 15, '...')); ?>
                                </p>

                                <div class="flavor-taller-meta">
                                    <div class="flavor-taller-meta-item">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <span><?php echo esc_html($taller->organizador ?? __('Sin asignar', 'flavor-platform')); ?></span>
                                    </div>

                                    <?php if (!empty($taller->categoria)): ?>
                                    <div class="flavor-taller-meta-item">
                                        <span class="dashicons dashicons-category"></span>
                                        <span><?php echo esc_html(ucfirst($taller->categoria)); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="flavor-taller-meta-item">
                                        <span><?php echo esc_html($nivel_badge['icono']); ?></span>
                                        <span><?php echo esc_html($nivel_badge['texto']); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($taller->fecha_inicio)): ?>
                                <div class="flavor-taller-fecha">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <span>
                                        <?php echo esc_html(date_i18n('j M Y', strtotime($taller->fecha_inicio))); ?>
                                        <?php if (!empty($taller->hora_inicio)): ?>
                                            - <?php echo esc_html(date_i18n('H:i', strtotime($taller->hora_inicio))); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($taller->ubicacion)): ?>
                                <div class="flavor-taller-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <span><?php echo esc_html($taller->ubicacion); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-taller-footer">
                                <div class="flavor-taller-ocupacion">
                                    <div class="flavor-ocupacion-header">
                                        <span class="flavor-ocupacion-count">
                                            <?php echo esc_html($taller->inscritos_actuales); ?>/<?php echo esc_html($taller->max_participantes ?: '∞'); ?>
                                        </span>
                                        <span class="flavor-badge flavor-badge-sm flavor-badge-<?php echo esc_attr($ocupacion['clase']); ?>">
                                            <?php echo esc_html($ocupacion['texto']); ?>
                                        </span>
                                    </div>
                                    <?php if ($taller->max_participantes > 0): ?>
                                    <div class="flavor-ocupacion-bar">
                                        <div class="flavor-ocupacion-fill flavor-ocupacion-<?php echo esc_attr($ocupacion['clase']); ?>"
                                             style="width: <?php echo esc_attr($ocupacion['porcentaje']); ?>%"></div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flavor-taller-precio">
                                    <span class="flavor-precio-valor flavor-precio-<?php echo esc_attr($precio_info['clase']); ?>">
                                        <?php echo esc_html($precio_info['texto']); ?>
                                    </span>
                                </div>

                                <div class="flavor-taller-acciones">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=editar&id=' . $taller->id)); ?>"
                                       class="button button-small" title="<?php esc_attr_e('Editar', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=inscritos&id=' . $taller->id)); ?>"
                                       class="button button-small" title="<?php esc_attr_e('Ver Inscritos', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-groups"></span>
                                    </a>
                                    <button type="button" class="button button-small flavor-btn-duplicar"
                                            data-id="<?php echo esc_attr($taller->id); ?>" title="<?php esc_attr_e('Duplicar', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <div class="flavor-pagination">
                    <?php
                    $pagination_args = [
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $pagina_actual,
                        'total' => $total_paginas,
                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span>',
                        'next_text' => '<span class="dashicons dashicons-arrow-right-alt2"></span>',
                        'type' => 'array',
                        'end_size' => 1,
                        'mid_size' => 2
                    ];

                    // Preservar filtros en paginación
                    if ($busqueda) $pagination_args['add_args']['s'] = $busqueda;
                    if ($filtro_estado) $pagination_args['add_args']['estado'] = $filtro_estado;
                    if ($filtro_categoria) $pagination_args['add_args']['categoria'] = $filtro_categoria;
                    if ($filtro_nivel) $pagination_args['add_args']['nivel'] = $filtro_nivel;
                    if ($filtro_modalidad) $pagination_args['add_args']['modalidad'] = $filtro_modalidad;
                    if ($orden !== 'recientes') $pagination_args['add_args']['orden'] = $orden;

                    $pagination_links = paginate_links($pagination_args);

                    if ($pagination_links):
                        echo '<div class="flavor-pagination-info">';
                        printf(
                            esc_html__('Página %1$d de %2$d', 'flavor-platform'),
                            $pagina_actual,
                            $total_paginas
                        );
                        echo '</div>';
                        echo '<div class="flavor-pagination-links">';
                        foreach ($pagination_links as $link) {
                            echo $link;
                        }
                        echo '</div>';
                    endif;
                    ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- ===== SIDEBAR ===== -->
        <aside class="flavor-sidebar">
            <!-- Talleres Destacados -->
            <?php if (!empty($talleres_destacados)): ?>
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-title">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Talleres Destacados', 'flavor-platform'); ?>
                </h3>
                <ul class="flavor-sidebar-list">
                    <?php foreach ($talleres_destacados as $destacado):
                        $estado_dest = obtener_badge_estado_taller($destacado->estado);
                    ?>
                        <li class="flavor-sidebar-item">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=editar&id=' . $destacado->id)); ?>">
                                <span class="flavor-sidebar-item-titulo"><?php echo esc_html($destacado->titulo); ?></span>
                                <span class="flavor-badge flavor-badge-sm flavor-badge-<?php echo esc_attr($estado_dest['clase']); ?>">
                                    <?php echo esc_html($estado_dest['texto']); ?>
                                </span>
                            </a>
                            <span class="flavor-sidebar-item-meta">
                                <?php echo esc_html($destacado->inscritos_actuales); ?> inscritos
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Gráfico por Categoría -->
            <?php if (!empty($por_categoria)): ?>
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-title">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Por Categoría', 'flavor-platform'); ?>
                </h3>
                <div class="flavor-chart-container">
                    <canvas id="chart-categorias" width="250" height="250"></canvas>
                </div>
                <ul class="flavor-chart-legend">
                    <?php foreach ($por_categoria as $categoria => $total): ?>
                        <li>
                            <span class="flavor-legend-color" style="background-color: <?php echo esc_attr($colores_categorias[$categoria] ?? '#64748b'); ?>"></span>
                            <span class="flavor-legend-label"><?php echo esc_html(ucfirst($categoria)); ?></span>
                            <span class="flavor-legend-value"><?php echo esc_html($total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Acciones Rápidas -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-title">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Acciones Rápidas', 'flavor-platform'); ?>
                </h3>
                <div class="flavor-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=nuevo')); ?>" class="flavor-quick-action">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Nuevo Taller', 'flavor-platform'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=categorias')); ?>" class="flavor-quick-action">
                        <span class="dashicons dashicons-category"></span>
                        <?php esc_html_e('Categorías', 'flavor-platform'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=exportar')); ?>" class="flavor-quick-action">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Exportar Datos', 'flavor-platform'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-talleres&tab=ajustes')); ?>" class="flavor-quick-action">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Ajustes', 'flavor-platform'); ?>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
/* =====================================================
   ESTILOS DEL DASHBOARD DE TALLERES
   ===================================================== */

.flavor-talleres-dashboard {
    margin: 20px 20px 20px 0;
}

/* Cabecera */
.flavor-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 20px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: #fff;
}

.flavor-header-content h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-header-content h1 .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.flavor-header-subtitle {
    margin: 8px 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.flavor-header-actions .button-hero {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 14px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.5);
    color: #fff;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.flavor-header-actions .button-hero:hover {
    background: rgba(255,255,255,0.3);
    border-color: #fff;
    color: #fff;
}

/* Tarjetas de estadísticas */
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.flavor-stat-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.flavor-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-stat-primary .flavor-stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.flavor-stat-success .flavor-stat-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.flavor-stat-info .flavor-stat-icon { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.flavor-stat-warning .flavor-stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.flavor-stat-content {
    flex: 1;
}

.flavor-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.flavor-stat-label {
    display: block;
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

/* Filtros */
.flavor-filters-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.flavor-filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.flavor-filter-group {
    flex: 1;
    min-width: 150px;
}

.flavor-filter-search {
    flex: 2;
    min-width: 250px;
    position: relative;
}

.flavor-filter-search label {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.flavor-filter-search input {
    width: 100%;
    padding: 10px 12px 10px 40px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
}

.flavor-filter-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
}

.flavor-filter-actions {
    display: flex;
    gap: 10px;
}

.flavor-filter-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 8px;
}

/* Contenido principal */
.flavor-main-content {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
}

@media (max-width: 1200px) {
    .flavor-main-content {
        grid-template-columns: 1fr;
    }
}

.flavor-content-area {
    min-width: 0;
}

.flavor-results-info {
    margin-bottom: 15px;
    color: #64748b;
    font-size: 14px;
}

/* Grid de talleres */
.flavor-talleres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}

/* Card de taller */
.flavor-taller-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    flex-direction: column;
}

.flavor-taller-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.flavor-taller-card.destacado {
    border: 2px solid #f59e0b;
}

.flavor-taller-destacado-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    z-index: 10;
}

.flavor-taller-destacado-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-taller-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 16px 0;
}

.flavor-taller-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.flavor-taller-id {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 500;
}

.flavor-taller-content {
    padding: 12px 16px;
    flex: 1;
}

.flavor-taller-titulo {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
}

.flavor-taller-descripcion {
    margin: 0 0 12px;
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

.flavor-taller-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 10px;
}

.flavor-taller-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #64748b;
}

.flavor-taller-meta-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #94a3b8;
}

.flavor-taller-fecha,
.flavor-taller-ubicacion {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #64748b;
    margin-top: 6px;
}

.flavor-taller-fecha .dashicons,
.flavor-taller-ubicacion .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #94a3b8;
}

.flavor-taller-footer {
    padding: 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
}

.flavor-taller-ocupacion {
    flex: 1;
    min-width: 120px;
}

.flavor-ocupacion-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.flavor-ocupacion-count {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-ocupacion-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}

.flavor-ocupacion-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.flavor-ocupacion-success { background: #10b981; }
.flavor-ocupacion-info { background: #3b82f6; }
.flavor-ocupacion-warning { background: #f59e0b; }
.flavor-ocupacion-danger { background: #ef4444; }

.flavor-taller-precio {
    text-align: center;
}

.flavor-precio-valor {
    font-size: 18px;
    font-weight: 700;
}

.flavor-precio-success { color: #10b981; }
.flavor-precio-info { color: #3b82f6; }

.flavor-taller-acciones {
    display: flex;
    gap: 6px;
}

.flavor-taller-acciones .button {
    padding: 6px 10px;
    min-width: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-taller-acciones .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Badges */
.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.flavor-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-badge-sm {
    padding: 2px 8px;
    font-size: 10px;
}

.flavor-badge-primary { background: #ede9fe; color: #7c3aed; }
.flavor-badge-success { background: #d1fae5; color: #059669; }
.flavor-badge-info { background: #dbeafe; color: #2563eb; }
.flavor-badge-warning { background: #fef3c7; color: #d97706; }
.flavor-badge-danger { background: #fee2e2; color: #dc2626; }
.flavor-badge-secondary { background: #f1f5f9; color: #64748b; }

/* Estado vacío */
.flavor-empty-state {
    text-align: center;
    padding: 60px 40px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-empty-icon .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #94a3b8;
}

.flavor-empty-state h3 {
    margin: 0 0 10px;
    color: #1e293b;
    font-size: 18px;
}

.flavor-empty-state p {
    margin: 0 0 20px;
    color: #64748b;
}

/* Paginación */
.flavor-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 25px;
    padding: 15px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-pagination-info {
    color: #64748b;
    font-size: 14px;
}

.flavor-pagination-links {
    display: flex;
    gap: 5px;
}

.flavor-pagination-links a,
.flavor-pagination-links span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.flavor-pagination-links a:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.flavor-pagination-links .current {
    background: #667eea;
    color: #fff;
}

/* Sidebar */
.flavor-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.flavor-sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
}

.flavor-sidebar-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px;
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-sidebar-title .dashicons {
    color: #667eea;
}

.flavor-sidebar-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-sidebar-item {
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-sidebar-item:last-child {
    border-bottom: none;
}

.flavor-sidebar-item a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-decoration: none;
    color: #1e293b;
    font-weight: 500;
    font-size: 13px;
}

.flavor-sidebar-item a:hover {
    color: #667eea;
}

.flavor-sidebar-item-meta {
    display: block;
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}

/* Gráfico */
.flavor-chart-container {
    margin-bottom: 15px;
}

.flavor-chart-legend {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-chart-legend li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    font-size: 13px;
}

.flavor-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

.flavor-legend-label {
    flex: 1;
    color: #64748b;
}

.flavor-legend-value {
    font-weight: 600;
    color: #1e293b;
}

/* Acciones rápidas */
.flavor-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-quick-action {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f8fafc;
    border-radius: 8px;
    text-decoration: none;
    color: #475569;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.flavor-quick-action:hover {
    background: #f1f5f9;
    color: #667eea;
}

.flavor-quick-action .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #667eea;
}

/* Responsive */
@media (max-width: 782px) {
    .flavor-dashboard-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .flavor-filters-form {
        flex-direction: column;
    }

    .flavor-filter-group,
    .flavor-filter-search {
        min-width: 100%;
    }

    .flavor-talleres-grid {
        grid-template-columns: 1fr;
    }

    .flavor-taller-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-taller-acciones {
        justify-content: center;
    }

    .flavor-pagination {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<?php if (!empty($por_categoria)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chart-categorias');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', $categorias_chart)); ?>,
                datasets: [{
                    data: <?php echo json_encode($valores_chart); ?>,
                    backgroundColor: <?php echo json_encode($colores_chart); ?>,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const porcentaje = Math.round((context.raw / total) * 100);
                                return context.label + ': ' + context.raw + ' (' + porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>
