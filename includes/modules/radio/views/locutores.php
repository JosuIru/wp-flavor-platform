<?php
/**
 * Vista de Gestión de Locutores - Panel Administración
 *
 * Dashboard mejorado con estadísticas, filtros avanzados, paginación
 * y visualización de datos.
 *
 * @package FlavorChatIA
 * @subpackage Radio
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// =====================================================
// CONFIGURACIÓN Y VERIFICACIÓN DE TABLAS
// =====================================================

$tabla_locutores = $wpdb->prefix . 'flavor_radio_locutores';
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
$tabla_audiencia = $wpdb->prefix . 'flavor_radio_audiencia';

// Verificar existencia de tablas
$tabla_locutores_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_locutores
    )
);

$tabla_programas_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_programas
    )
);

$usar_datos_demo = !$tabla_locutores_existe;

// =====================================================
// FUNCIONES HELPER
// =====================================================

/**
 * Obtener badge de nivel del locutor según programas y antigüedad
 */
function obtener_nivel_locutor($total_programas, $fecha_inicio = null) {
    $niveles = [
        'estrella' => [
            'clase' => 'estrella',
            'texto' => __('Estrella', 'flavor-chat-ia'),
            'icono' => 'star-filled',
            'color' => '#f59e0b'
        ],
        'senior' => [
            'clase' => 'senior',
            'texto' => __('Senior', 'flavor-chat-ia'),
            'icono' => 'awards',
            'color' => '#8b5cf6'
        ],
        'profesional' => [
            'clase' => 'profesional',
            'texto' => __('Profesional', 'flavor-chat-ia'),
            'icono' => 'microphone',
            'color' => '#3b82f6'
        ],
        'junior' => [
            'clase' => 'junior',
            'texto' => __('Junior', 'flavor-chat-ia'),
            'icono' => 'admin-users',
            'color' => '#10b981'
        ],
        'nuevo' => [
            'clase' => 'nuevo',
            'texto' => __('Nuevo', 'flavor-chat-ia'),
            'icono' => 'welcome-learn-more',
            'color' => '#6b7280'
        ]
    ];

    // Calcular antigüedad en meses
    $antiguedad_meses = 0;
    if ($fecha_inicio) {
        $fecha_inicio_timestamp = strtotime($fecha_inicio);
        $antiguedad_meses = floor((time() - $fecha_inicio_timestamp) / (30 * 24 * 3600));
    }

    if ($total_programas >= 5 && $antiguedad_meses >= 24) {
        return $niveles['estrella'];
    } elseif ($total_programas >= 3 && $antiguedad_meses >= 12) {
        return $niveles['senior'];
    } elseif ($total_programas >= 2 && $antiguedad_meses >= 6) {
        return $niveles['profesional'];
    } elseif ($total_programas >= 1) {
        return $niveles['junior'];
    }
    return $niveles['nuevo'];
}

/**
 * Obtener badge de estado
 */
function obtener_badge_estado_locutor($estado) {
    $estados = [
        'activo' => [
            'clase' => 'success',
            'texto' => __('Activo', 'flavor-chat-ia'),
            'icono' => 'yes-alt'
        ],
        'inactivo' => [
            'clase' => 'secondary',
            'texto' => __('Inactivo', 'flavor-chat-ia'),
            'icono' => 'marker'
        ],
        'vacaciones' => [
            'clase' => 'info',
            'texto' => __('Vacaciones', 'flavor-chat-ia'),
            'icono' => 'palmtree'
        ],
        'suspendido' => [
            'clase' => 'danger',
            'texto' => __('Suspendido', 'flavor-chat-ia'),
            'icono' => 'dismiss'
        ]
    ];

    $estado_key = strtolower($estado);
    return $estados[$estado_key] ?? ['clase' => 'secondary', 'texto' => ucfirst($estado), 'icono' => 'marker'];
}

/**
 * Calcular rating de audiencia
 */
function calcular_rating_locutor($audiencia_promedio) {
    if ($audiencia_promedio >= 1000) return 5;
    if ($audiencia_promedio >= 500) return 4;
    if ($audiencia_promedio >= 200) return 3;
    if ($audiencia_promedio >= 50) return 2;
    return 1;
}

// =====================================================
// DATOS DEMO
// =====================================================

if ($usar_datos_demo) {
    $locutores_demo = [
        (object) [
            'id' => 1,
            'nombre' => 'María García',
            'email' => 'maria@radio.com',
            'telefono' => '600-111-222',
            'bio' => 'Locutora con más de 10 años de experiencia en radio musical. Especializada en programas matutinos y entrevistas.',
            'foto_url' => '',
            'estado' => 'activo',
            'especialidad' => 'Musical',
            'fecha_inicio' => date('Y-m-d', strtotime('-3 years')),
            'redes_sociales' => json_encode(['twitter' => '@mariaradio', 'instagram' => '@maria_radio']),
            'total_programas' => 3,
            'audiencia_promedio' => 850,
            'horas_emision' => 156
        ],
        (object) [
            'id' => 2,
            'nombre' => 'Carlos Rodríguez',
            'email' => 'carlos@radio.com',
            'telefono' => '600-333-444',
            'bio' => 'Periodista y locutor especializado en noticias y actualidad. Conductor del informativo de mediodía.',
            'foto_url' => '',
            'estado' => 'activo',
            'especialidad' => 'Noticias',
            'fecha_inicio' => date('Y-m-d', strtotime('-5 years')),
            'redes_sociales' => json_encode(['twitter' => '@carlosinfo']),
            'total_programas' => 5,
            'audiencia_promedio' => 1200,
            'horas_emision' => 340
        ],
        (object) [
            'id' => 3,
            'nombre' => 'Ana Martínez',
            'email' => 'ana@radio.com',
            'telefono' => '600-555-666',
            'bio' => 'Locutora nocturna especializada en programas de entretenimiento y música alternativa.',
            'foto_url' => '',
            'estado' => 'activo',
            'especialidad' => 'Entretenimiento',
            'fecha_inicio' => date('Y-m-d', strtotime('-1 year')),
            'redes_sociales' => json_encode(['instagram' => '@ana_noche']),
            'total_programas' => 2,
            'audiencia_promedio' => 450,
            'horas_emision' => 78
        ],
        (object) [
            'id' => 4,
            'nombre' => 'Pedro López',
            'email' => 'pedro@radio.com',
            'telefono' => '600-777-888',
            'bio' => 'Comentarista deportivo con amplia experiencia en retransmisiones de fútbol y baloncesto.',
            'foto_url' => '',
            'estado' => 'vacaciones',
            'especialidad' => 'Deportes',
            'fecha_inicio' => date('Y-m-d', strtotime('-2 years')),
            'redes_sociales' => json_encode(['twitter' => '@pedroDeportes']),
            'total_programas' => 2,
            'audiencia_promedio' => 680,
            'horas_emision' => 124
        ],
        (object) [
            'id' => 5,
            'nombre' => 'Laura Sánchez',
            'email' => 'laura@radio.com',
            'telefono' => '600-999-000',
            'bio' => 'Nueva incorporación al equipo. Especializada en programas culturales y literarios.',
            'foto_url' => '',
            'estado' => 'activo',
            'especialidad' => 'Cultural',
            'fecha_inicio' => date('Y-m-d', strtotime('-3 months')),
            'redes_sociales' => json_encode([]),
            'total_programas' => 1,
            'audiencia_promedio' => 180,
            'horas_emision' => 24
        ],
        (object) [
            'id' => 6,
            'nombre' => 'Miguel Torres',
            'email' => 'miguel@radio.com',
            'telefono' => '600-123-456',
            'bio' => 'Técnico de sonido y locutor ocasional. Actualmente inactivo por motivos personales.',
            'foto_url' => '',
            'estado' => 'inactivo',
            'especialidad' => 'Técnico',
            'fecha_inicio' => date('Y-m-d', strtotime('-4 years')),
            'redes_sociales' => json_encode([]),
            'total_programas' => 0,
            'audiencia_promedio' => 0,
            'horas_emision' => 45
        ]
    ];

    // Estadísticas demo
    $estadisticas = [
        'total_locutores' => 6,
        'locutores_activos' => 4,
        'total_programas' => 13,
        'audiencia_total' => 3360,
        'horas_emision_mes' => 767
    ];
}

// =====================================================
// PARÁMETROS DE FILTRADO Y PAGINACIÓN
// =====================================================

$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$items_por_pagina = 12;
$offset = ($pagina_actual - 1) * $items_por_pagina;

$filtro_busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_especialidad = isset($_GET['especialidad']) ? sanitize_text_field($_GET['especialidad']) : '';
$filtro_nivel = isset($_GET['nivel']) ? sanitize_text_field($_GET['nivel']) : '';
$filtro_orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'nombre_asc';

// =====================================================
// CONSULTA DE DATOS REALES O FILTRADO DEMO
// =====================================================

if (!$usar_datos_demo) {
    // Construir query con filtros
    $where_clauses = ["1=1"];
    $having_clauses = [];
    $params = [];

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(l.nombre LIKE %s OR l.email LIKE %s OR l.bio LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $params[] = $busqueda_like;
        $params[] = $busqueda_like;
        $params[] = $busqueda_like;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "l.estado = %s";
        $params[] = $filtro_estado;
    }

    if (!empty($filtro_especialidad)) {
        $where_clauses[] = "l.especialidad = %s";
        $params[] = $filtro_especialidad;
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Ordenamiento
    $order_sql = "l.nombre ASC";
    switch ($filtro_orden) {
        case 'nombre_desc':
            $order_sql = "l.nombre DESC";
            break;
        case 'programas_desc':
            $order_sql = "total_programas DESC";
            break;
        case 'programas_asc':
            $order_sql = "total_programas ASC";
            break;
        case 'audiencia_desc':
            $order_sql = "audiencia_promedio DESC";
            break;
        case 'fecha_desc':
            $order_sql = "l.fecha_inicio DESC";
            break;
        case 'fecha_asc':
            $order_sql = "l.fecha_inicio ASC";
            break;
    }

    // Query de estadísticas
    $estadisticas = [
        'total_locutores' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_locutores"),
        'locutores_activos' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_locutores WHERE estado = 'activo'"),
        'total_programas' => $tabla_programas_existe ? $wpdb->get_var("SELECT COUNT(*) FROM $tabla_programas") : 0,
        'audiencia_total' => $wpdb->get_var("SELECT COALESCE(SUM(audiencia_promedio), 0) FROM $tabla_locutores"),
        'horas_emision_mes' => $wpdb->get_var("SELECT COALESCE(SUM(horas_emision), 0) FROM $tabla_locutores")
    ];

    // Query principal con conteo de programas
    $query_base = "
        SELECT l.*,
               COUNT(DISTINCT p.id) as total_programas,
               COALESCE(l.audiencia_promedio, 0) as audiencia_promedio
        FROM $tabla_locutores l
        LEFT JOIN $tabla_programas p ON l.id = p.locutor_principal_id
        WHERE $where_sql
        GROUP BY l.id
    ";

    // Filtro por nivel (post-GROUP BY)
    if (!empty($filtro_nivel)) {
        switch ($filtro_nivel) {
            case 'estrella':
                $having_clauses[] = "total_programas >= 5";
                break;
            case 'senior':
                $having_clauses[] = "total_programas >= 3 AND total_programas < 5";
                break;
            case 'profesional':
                $having_clauses[] = "total_programas >= 2 AND total_programas < 3";
                break;
            case 'junior':
                $having_clauses[] = "total_programas = 1";
                break;
            case 'nuevo':
                $having_clauses[] = "total_programas = 0";
                break;
        }
    }

    if (!empty($having_clauses)) {
        $query_base .= " HAVING " . implode(' AND ', $having_clauses);
    }

    // Conteo total
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM ($query_base) as subquery", $params);

    // Query con orden y paginación
    $query_final = "$query_base ORDER BY $order_sql LIMIT %d OFFSET %d";
    $params[] = $items_por_pagina;
    $params[] = $offset;

    $locutores = $wpdb->get_results($wpdb->prepare($query_final, $params));

    // Obtener especialidades únicas para filtro
    $especialidades = $wpdb->get_col("SELECT DISTINCT especialidad FROM $tabla_locutores WHERE especialidad IS NOT NULL AND especialidad != '' ORDER BY especialidad");

} else {
    // Filtrar datos demo
    $locutores_filtrados = array_filter($locutores_demo, function($locutor) use ($filtro_busqueda, $filtro_estado, $filtro_especialidad, $filtro_nivel) {
        if (!empty($filtro_busqueda)) {
            $busqueda_lower = strtolower($filtro_busqueda);
            if (strpos(strtolower($locutor->nombre), $busqueda_lower) === false &&
                strpos(strtolower($locutor->email), $busqueda_lower) === false &&
                strpos(strtolower($locutor->bio), $busqueda_lower) === false) {
                return false;
            }
        }

        if (!empty($filtro_estado) && $locutor->estado !== $filtro_estado) {
            return false;
        }

        if (!empty($filtro_especialidad) && $locutor->especialidad !== $filtro_especialidad) {
            return false;
        }

        if (!empty($filtro_nivel)) {
            $nivel_locutor = obtener_nivel_locutor($locutor->total_programas, $locutor->fecha_inicio);
            if ($nivel_locutor['clase'] !== $filtro_nivel) {
                return false;
            }
        }

        return true;
    });

    // Ordenar
    usort($locutores_filtrados, function($a, $b) use ($filtro_orden) {
        switch ($filtro_orden) {
            case 'nombre_desc':
                return strcasecmp($b->nombre, $a->nombre);
            case 'programas_desc':
                return $b->total_programas - $a->total_programas;
            case 'programas_asc':
                return $a->total_programas - $b->total_programas;
            case 'audiencia_desc':
                return $b->audiencia_promedio - $a->audiencia_promedio;
            case 'fecha_desc':
                return strtotime($b->fecha_inicio) - strtotime($a->fecha_inicio);
            case 'fecha_asc':
                return strtotime($a->fecha_inicio) - strtotime($b->fecha_inicio);
            default:
                return strcasecmp($a->nombre, $b->nombre);
        }
    });

    $total_items = count($locutores_filtrados);
    $locutores = array_slice($locutores_filtrados, $offset, $items_por_pagina);
    $especialidades = array_unique(array_column($locutores_demo, 'especialidad'));
    sort($especialidades);
}

$total_paginas = ceil($total_items / $items_por_pagina);

// Top locutores para sidebar
$top_locutores = $usar_datos_demo
    ? array_slice(array_filter($locutores_demo, fn($l) => $l->estado === 'activo'), 0, 5)
    : ($tabla_locutores_existe ? $wpdb->get_results("
        SELECT l.*, COUNT(p.id) as total_programas
        FROM $tabla_locutores l
        LEFT JOIN $tabla_programas p ON l.id = p.locutor_principal_id
        WHERE l.estado = 'activo'
        GROUP BY l.id
        ORDER BY audiencia_promedio DESC
        LIMIT 5
    ") : []);

// Distribución por especialidad para gráfico
$distribucion_especialidad = [];
$datos_distribucion = $usar_datos_demo ? $locutores_demo : ($tabla_locutores_existe ? $wpdb->get_results("SELECT especialidad, COUNT(*) as total FROM $tabla_locutores GROUP BY especialidad") : []);

foreach ($datos_distribucion as $item) {
    $esp = $usar_datos_demo ? $item->especialidad : $item->especialidad;
    $total = $usar_datos_demo ? 1 : $item->total;
    if (!isset($distribucion_especialidad[$esp])) {
        $distribucion_especialidad[$esp] = 0;
    }
    $distribucion_especialidad[$esp] += $usar_datos_demo ? 1 : $total;
}

if ($usar_datos_demo) {
    $distribucion_especialidad = [];
    foreach ($locutores_demo as $l) {
        if (!isset($distribucion_especialidad[$l->especialidad])) {
            $distribucion_especialidad[$l->especialidad] = 0;
        }
        $distribucion_especialidad[$l->especialidad]++;
    }
}
?>

<div class="wrap flavor-radio-locutores">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-microphone"></span>
        <?php echo esc_html__('Gestión de Locutores', 'flavor-chat-ia'); ?>
    </h1>
    <a href="#" class="page-title-action" onclick="abrirModalNuevoLocutor(); return false;">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php echo esc_html__('Nuevo Locutor', 'flavor-chat-ia'); ?>
    </a>

    <?php if ($usar_datos_demo): ?>
    <div class="notice notice-info inline" style="margin: 15px 0;">
        <p>
            <span class="dashicons dashicons-info"></span>
            <?php echo esc_html__('Mostrando datos de demostración. Las tablas de la base de datos no están configuradas.', 'flavor-chat-ia'); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['total_locutores']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Locutores', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['locutores_activos']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Activos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span class="dashicons dashicons-playlist-audio"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['total_programas']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Programas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['audiencia_total']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Audiencia Total', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout principal con sidebar -->
    <div class="flavor-main-layout">
        <div class="flavor-main-content">
            <!-- Filtros -->
            <div class="flavor-filtros-card">
                <form method="get" class="flavor-filtros-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'flavor-radio'); ?>">
                    <input type="hidden" name="tab" value="locutores">

                    <div class="flavor-filtro-grupo">
                        <label for="buscar"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="buscar" name="buscar" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Nombre, email...', 'flavor-chat-ia'); ?>">
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="estado"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                        <select id="estado" name="estado">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php echo esc_html__('Activo', 'flavor-chat-ia'); ?></option>
                            <option value="inactivo" <?php selected($filtro_estado, 'inactivo'); ?>><?php echo esc_html__('Inactivo', 'flavor-chat-ia'); ?></option>
                            <option value="vacaciones" <?php selected($filtro_estado, 'vacaciones'); ?>><?php echo esc_html__('Vacaciones', 'flavor-chat-ia'); ?></option>
                            <option value="suspendido" <?php selected($filtro_estado, 'suspendido'); ?>><?php echo esc_html__('Suspendido', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="especialidad"><?php echo esc_html__('Especialidad', 'flavor-chat-ia'); ?></label>
                        <select id="especialidad" name="especialidad">
                            <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($especialidades as $especialidad): ?>
                                <option value="<?php echo esc_attr($especialidad); ?>" <?php selected($filtro_especialidad, $especialidad); ?>>
                                    <?php echo esc_html($especialidad); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="nivel"><?php echo esc_html__('Nivel', 'flavor-chat-ia'); ?></label>
                        <select id="nivel" name="nivel">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="estrella" <?php selected($filtro_nivel, 'estrella'); ?>><?php echo esc_html__('Estrella', 'flavor-chat-ia'); ?></option>
                            <option value="senior" <?php selected($filtro_nivel, 'senior'); ?>><?php echo esc_html__('Senior', 'flavor-chat-ia'); ?></option>
                            <option value="profesional" <?php selected($filtro_nivel, 'profesional'); ?>><?php echo esc_html__('Profesional', 'flavor-chat-ia'); ?></option>
                            <option value="junior" <?php selected($filtro_nivel, 'junior'); ?>><?php echo esc_html__('Junior', 'flavor-chat-ia'); ?></option>
                            <option value="nuevo" <?php selected($filtro_nivel, 'nuevo'); ?>><?php echo esc_html__('Nuevo', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="orden"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                        <select id="orden" name="orden">
                            <option value="nombre_asc" <?php selected($filtro_orden, 'nombre_asc'); ?>><?php echo esc_html__('Nombre A-Z', 'flavor-chat-ia'); ?></option>
                            <option value="nombre_desc" <?php selected($filtro_orden, 'nombre_desc'); ?>><?php echo esc_html__('Nombre Z-A', 'flavor-chat-ia'); ?></option>
                            <option value="audiencia_desc" <?php selected($filtro_orden, 'audiencia_desc'); ?>><?php echo esc_html__('Mayor audiencia', 'flavor-chat-ia'); ?></option>
                            <option value="programas_desc" <?php selected($filtro_orden, 'programas_desc'); ?>><?php echo esc_html__('Más programas', 'flavor-chat-ia'); ?></option>
                            <option value="fecha_desc" <?php selected($filtro_orden, 'fecha_desc'); ?>><?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?></option>
                            <option value="fecha_asc" <?php selected($filtro_orden, 'fecha_asc'); ?>><?php echo esc_html__('Más antiguos', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-acciones">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_GET['page'] ?? 'flavor-radio') . '&tab=locutores')); ?>" class="button">
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
                        esc_html__('Mostrando %d-%d de %d locutores', 'flavor-chat-ia'),
                        min($offset + 1, $total_items),
                        min($offset + $items_por_pagina, $total_items),
                        $total_items
                    );
                    ?>
                </span>
            </div>

            <!-- Grid de locutores -->
            <?php if (empty($locutores)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-admin-users"></span>
                    <h3><?php echo esc_html__('No se encontraron locutores', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Intenta ajustar los filtros o añade un nuevo locutor.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-locutores-grid">
                    <?php foreach ($locutores as $locutor):
                        $nivel = obtener_nivel_locutor($locutor->total_programas, $locutor->fecha_inicio ?? null);
                        $badge_estado = obtener_badge_estado_locutor($locutor->estado);
                        $rating = calcular_rating_locutor($locutor->audiencia_promedio ?? 0);
                        $redes = !empty($locutor->redes_sociales) ? json_decode($locutor->redes_sociales, true) : [];
                    ?>
                        <div class="flavor-locutor-card" data-id="<?php echo esc_attr($locutor->id); ?>">
                            <div class="flavor-locutor-header">
                                <?php if (!empty($locutor->foto_url)): ?>
                                    <img src="<?php echo esc_url($locutor->foto_url); ?>"
                                         alt="<?php echo esc_attr($locutor->nombre); ?>"
                                         class="flavor-locutor-avatar">
                                <?php else: ?>
                                    <div class="flavor-locutor-avatar-placeholder">
                                        <span class="dashicons dashicons-admin-users"></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flavor-locutor-badges">
                                    <span class="flavor-badge flavor-badge-estado-<?php echo esc_attr($badge_estado['clase']); ?>">
                                        <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                        <?php echo esc_html($badge_estado['texto']); ?>
                                    </span>
                                    <span class="flavor-badge flavor-badge-nivel-<?php echo esc_attr($nivel['clase']); ?>" title="<?php echo esc_attr($nivel['texto']); ?>">
                                        <span class="dashicons dashicons-<?php echo esc_attr($nivel['icono']); ?>"></span>
                                        <?php echo esc_html($nivel['texto']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="flavor-locutor-body">
                                <h3 class="flavor-locutor-nombre"><?php echo esc_html($locutor->nombre); ?></h3>

                                <?php if (!empty($locutor->especialidad)): ?>
                                    <span class="flavor-locutor-especialidad">
                                        <span class="dashicons dashicons-tag"></span>
                                        <?php echo esc_html($locutor->especialidad); ?>
                                    </span>
                                <?php endif; ?>

                                <p class="flavor-locutor-bio"><?php echo esc_html(wp_trim_words($locutor->bio ?? '', 15)); ?></p>

                                <!-- Rating de audiencia -->
                                <div class="flavor-locutor-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="dashicons dashicons-star-<?php echo $i <= $rating ? 'filled' : 'empty'; ?>"></span>
                                    <?php endfor; ?>
                                    <span class="flavor-rating-text"><?php echo number_format($locutor->audiencia_promedio ?? 0); ?> oyentes</span>
                                </div>

                                <!-- Stats -->
                                <div class="flavor-locutor-stats">
                                    <div class="flavor-locutor-stat">
                                        <span class="dashicons dashicons-playlist-audio"></span>
                                        <strong><?php echo intval($locutor->total_programas); ?></strong>
                                        <span><?php echo esc_html__('Programas', 'flavor-chat-ia'); ?></span>
                                    </div>
                                    <div class="flavor-locutor-stat">
                                        <span class="dashicons dashicons-clock"></span>
                                        <strong><?php echo number_format($locutor->horas_emision ?? 0); ?>h</strong>
                                        <span><?php echo esc_html__('Al aire', 'flavor-chat-ia'); ?></span>
                                    </div>
                                </div>

                                <!-- Redes sociales -->
                                <?php if (!empty($redes)): ?>
                                    <div class="flavor-locutor-redes">
                                        <?php if (!empty($redes['twitter'])): ?>
                                            <a href="https://twitter.com/<?php echo esc_attr(ltrim($redes['twitter'], '@')); ?>" target="_blank" class="flavor-red-social twitter" title="Twitter">
                                                <span class="dashicons dashicons-twitter"></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($redes['instagram'])): ?>
                                            <a href="https://instagram.com/<?php echo esc_attr(ltrim($redes['instagram'], '@')); ?>" target="_blank" class="flavor-red-social instagram" title="Instagram">
                                                <span class="dashicons dashicons-instagram"></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($redes['facebook'])): ?>
                                            <a href="<?php echo esc_url($redes['facebook']); ?>" target="_blank" class="flavor-red-social facebook" title="Facebook">
                                                <span class="dashicons dashicons-facebook"></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-locutor-footer">
                                <button type="button" onclick="editarLocutor(<?php echo $locutor->id; ?>)" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                                </button>
                                <button type="button" onclick="verProgramasLocutor(<?php echo $locutor->id; ?>)" class="button button-small button-link">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo esc_html__('Ver programas', 'flavor-chat-ia'); ?>
                                </button>
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
            <!-- Top Locutores -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-awards"></span>
                    <?php echo esc_html__('Top Locutores', 'flavor-chat-ia'); ?>
                </h3>
                <?php if (!empty($top_locutores)): ?>
                    <ul class="flavor-top-list">
                        <?php foreach ($top_locutores as $index => $top): ?>
                            <li>
                                <span class="flavor-top-posicion"><?php echo $index + 1; ?></span>
                                <div class="flavor-top-info">
                                    <strong><?php echo esc_html($top->nombre); ?></strong>
                                    <span><?php echo number_format($top->audiencia_promedio ?? 0); ?> oyentes</span>
                                </div>
                                <?php
                                $nivel_top = obtener_nivel_locutor($top->total_programas ?? 0, $top->fecha_inicio ?? null);
                                ?>
                                <span class="flavor-badge flavor-badge-nivel-<?php echo esc_attr($nivel_top['clase']); ?>" style="font-size: 10px;">
                                    <?php echo esc_html($nivel_top['texto']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="flavor-no-data"><?php echo esc_html__('Sin datos disponibles', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Gráfico de Especialidades -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Por Especialidad', 'flavor-chat-ia'); ?>
                </h3>
                <canvas id="graficoEspecialidades" height="200"></canvas>
            </div>

            <!-- Leyenda de niveles -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php echo esc_html__('Niveles de Locutor', 'flavor-chat-ia'); ?>
                </h3>
                <ul class="flavor-leyenda-niveles">
                    <li>
                        <span class="flavor-badge flavor-badge-nivel-estrella"><span class="dashicons dashicons-star-filled"></span> Estrella</span>
                        <small>5+ programas, 2+ años</small>
                    </li>
                    <li>
                        <span class="flavor-badge flavor-badge-nivel-senior"><span class="dashicons dashicons-awards"></span> Senior</span>
                        <small>3+ programas, 1+ año</small>
                    </li>
                    <li>
                        <span class="flavor-badge flavor-badge-nivel-profesional"><span class="dashicons dashicons-microphone"></span> Profesional</span>
                        <small>2+ programas, 6+ meses</small>
                    </li>
                    <li>
                        <span class="flavor-badge flavor-badge-nivel-junior"><span class="dashicons dashicons-admin-users"></span> Junior</span>
                        <small>1+ programa</small>
                    </li>
                    <li>
                        <span class="flavor-badge flavor-badge-nivel-nuevo"><span class="dashicons dashicons-welcome-learn-more"></span> Nuevo</span>
                        <small>Sin programas</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Locutor -->
<div id="modal-locutor" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalLocutor()"></div>
    <div class="flavor-modal-content">
        <button type="button" class="flavor-modal-close" onclick="cerrarModalLocutor()">&times;</button>
        <h3 id="modal-locutor-titulo"><?php echo esc_html__('Nuevo Locutor', 'flavor-chat-ia'); ?></h3>

        <form id="form-locutor" method="post">
            <?php wp_nonce_field('nuevo_locutor', 'locutor_nonce'); ?>
            <input type="hidden" name="accion" value="crear_locutor">
            <input type="hidden" name="locutor_id" id="locutor_id" value="">

            <div class="flavor-form-grid">
                <div class="flavor-form-group">
                    <label for="nombre"><?php echo esc_html__('Nombre completo', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="flavor-form-group">
                    <label for="email"><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></label>
                    <input type="email" id="email" name="email">
                </div>

                <div class="flavor-form-group">
                    <label for="telefono"><?php echo esc_html__('Teléfono', 'flavor-chat-ia'); ?></label>
                    <input type="tel" id="telefono" name="telefono">
                </div>

                <div class="flavor-form-group">
                    <label for="especialidad"><?php echo esc_html__('Especialidad', 'flavor-chat-ia'); ?></label>
                    <select id="especialidad_form" name="especialidad">
                        <option value=""><?php echo esc_html__('Seleccionar...', 'flavor-chat-ia'); ?></option>
                        <option value="Musical"><?php echo esc_html__('Musical', 'flavor-chat-ia'); ?></option>
                        <option value="Noticias"><?php echo esc_html__('Noticias', 'flavor-chat-ia'); ?></option>
                        <option value="Deportes"><?php echo esc_html__('Deportes', 'flavor-chat-ia'); ?></option>
                        <option value="Entretenimiento"><?php echo esc_html__('Entretenimiento', 'flavor-chat-ia'); ?></option>
                        <option value="Cultural"><?php echo esc_html__('Cultural', 'flavor-chat-ia'); ?></option>
                        <option value="Técnico"><?php echo esc_html__('Técnico', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group flavor-form-full">
                    <label for="bio"><?php echo esc_html__('Biografía', 'flavor-chat-ia'); ?></label>
                    <textarea id="bio" name="bio" rows="3"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="foto_url"><?php echo esc_html__('URL de foto', 'flavor-chat-ia'); ?></label>
                    <input type="url" id="foto_url" name="foto_url" placeholder="https://...">
                </div>

                <div class="flavor-form-group">
                    <label for="estado_form"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="estado_form" name="estado">
                        <option value="activo"><?php echo esc_html__('Activo', 'flavor-chat-ia'); ?></option>
                        <option value="inactivo"><?php echo esc_html__('Inactivo', 'flavor-chat-ia'); ?></option>
                        <option value="vacaciones"><?php echo esc_html__('Vacaciones', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>

            <div class="flavor-modal-actions">
                <button type="button" class="button" onclick="cerrarModalLocutor()"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
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

.flavor-radio-locutores {
    max-width: 1600px;
}

.flavor-radio-locutores .page-title-action {
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

/* Grid de locutores */
.flavor-locutores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.flavor-locutor-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-locutor-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.flavor-locutor-header {
    position: relative;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 140px;
}

.flavor-locutor-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.flavor-locutor-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-locutor-avatar-placeholder .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: rgba(255,255,255,0.5);
}

.flavor-locutor-badges {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Badges de estado */
.flavor-badge-estado-success { background: rgba(0,163,42,0.15); color: #00a32a; }
.flavor-badge-estado-secondary { background: rgba(120,124,130,0.15); color: #787c82; }
.flavor-badge-estado-info { background: rgba(114,174,230,0.15); color: #2271b1; }
.flavor-badge-estado-danger { background: rgba(214,54,56,0.15); color: #d63638; }

/* Badges de nivel */
.flavor-badge-nivel-estrella { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff; }
.flavor-badge-nivel-senior { background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: #fff; }
.flavor-badge-nivel-profesional { background: linear-gradient(135deg, #3b82f6, #60a5fa); color: #fff; }
.flavor-badge-nivel-junior { background: linear-gradient(135deg, #10b981, #34d399); color: #fff; }
.flavor-badge-nivel-nuevo { background: #f3f4f6; color: #6b7280; }

.flavor-locutor-body {
    padding: 20px;
}

.flavor-locutor-nombre {
    margin: 0 0 5px 0;
    font-size: 18px;
    color: #1d2327;
}

.flavor-locutor-especialidad {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #646970;
    margin-bottom: 10px;
}

.flavor-locutor-especialidad .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-locutor-bio {
    font-size: 13px;
    color: #646970;
    margin: 10px 0;
    line-height: 1.5;
}

.flavor-locutor-rating {
    display: flex;
    align-items: center;
    gap: 2px;
    margin: 10px 0;
}

.flavor-locutor-rating .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #f59e0b;
}

.flavor-locutor-rating .dashicons-star-empty {
    color: #dcdcde;
}

.flavor-rating-text {
    font-size: 12px;
    color: #646970;
    margin-left: 8px;
}

.flavor-locutor-stats {
    display: flex;
    gap: 20px;
    margin: 15px 0;
    padding: 12px 0;
    border-top: 1px solid #f0f0f1;
    border-bottom: 1px solid #f0f0f1;
}

.flavor-locutor-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.flavor-locutor-stat .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: var(--flavor-primary);
    margin-bottom: 4px;
}

.flavor-locutor-stat strong {
    font-size: 16px;
    color: #1d2327;
}

.flavor-locutor-stat span {
    font-size: 11px;
    color: #646970;
}

.flavor-locutor-redes {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.flavor-red-social {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: transform 0.2s;
}

.flavor-red-social:hover {
    transform: scale(1.1);
}

.flavor-red-social.twitter { background: #1da1f2; }
.flavor-red-social.instagram { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
.flavor-red-social.facebook { background: #1877f2; }

.flavor-red-social .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #fff;
}

.flavor-locutor-footer {
    padding: 15px 20px;
    background: #f9fafb;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.flavor-locutor-footer .button {
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

.flavor-leyenda-niveles {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-leyenda-niveles li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.flavor-leyenda-niveles li:last-child {
    border-bottom: none;
}

.flavor-leyenda-niveles small {
    font-size: 11px;
    color: #646970;
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
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.flavor-modal-close {
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

.flavor-modal-close:hover {
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
    // Gráfico de especialidades
    const ctx = document.getElementById('graficoEspecialidades');
    if (ctx) {
        const distribucion = <?php echo json_encode($distribucion_especialidad); ?>;
        const labels = Object.keys(distribucion);
        const data = Object.values(distribucion);

        const colores = [
            '#667eea', '#f093fb', '#11998e', '#fa709a', '#fee140',
            '#00c6ff', '#f857a6', '#4facfe', '#43e97b', '#fa709a'
        ];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colores.slice(0, labels.length),
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
});

function abrirModalNuevoLocutor() {
    document.getElementById('modal-locutor-titulo').textContent = '<?php echo esc_js(__('Nuevo Locutor', 'flavor-chat-ia')); ?>';
    document.getElementById('form-locutor').reset();
    document.getElementById('locutor_id').value = '';
    document.getElementById('modal-locutor').style.display = 'flex';
}

function cerrarModalLocutor() {
    document.getElementById('modal-locutor').style.display = 'none';
}

function editarLocutor(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=flavor-radio&tab=locutores&editar='); ?>' + id;
}

function verProgramasLocutor(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=flavor-radio&tab=programas&locutor='); ?>' + id;
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalLocutor();
    }
});
</script>
