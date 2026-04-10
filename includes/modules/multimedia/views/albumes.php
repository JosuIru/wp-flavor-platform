<?php
/**
 * Vista de Gestión de Álbumes - Dashboard Mejorado
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

// =====================================================
// FUNCIONES HELPER
// =====================================================

/**
 * Obtener badge de visibilidad del álbum
 */
function obtener_badge_visibilidad_album($publico) {
    if ($publico) {
        return [
            'clase' => 'success',
            'texto' => __('Público', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'visibility'
        ];
    }
    return [
        'clase' => 'secondary',
        'texto' => __('Privado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'hidden'
    ];
}

/**
 * Obtener información del tipo de contenido predominante
 */
function obtener_tipo_contenido_album($fotos, $videos, $audios, $documentos) {
    $tipos = [
        'fotos' => ['cantidad' => $fotos, 'icono' => 'format-image', 'texto' => __('Fotos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#3b82f6'],
        'videos' => ['cantidad' => $videos, 'icono' => 'video-alt3', 'texto' => __('Videos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ef4444'],
        'audios' => ['cantidad' => $audios, 'icono' => 'format-audio', 'texto' => __('Audios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#8b5cf6'],
        'documentos' => ['cantidad' => $documentos, 'icono' => 'media-document', 'texto' => __('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f59e0b']
    ];

    $tipo_predominante = 'mixto';
    $max_cantidad = 0;

    foreach ($tipos as $clave => $tipo) {
        if ($tipo['cantidad'] > $max_cantidad) {
            $max_cantidad = $tipo['cantidad'];
            $tipo_predominante = $clave;
        }
    }

    if ($max_cantidad === 0) {
        return [
            'tipo' => 'vacio',
            'icono' => 'images-alt2',
            'texto' => __('Vacío', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => '#9ca3af'
        ];
    }

    return [
        'tipo' => $tipo_predominante,
        'icono' => $tipos[$tipo_predominante]['icono'],
        'texto' => $tipos[$tipo_predominante]['texto'],
        'color' => $tipos[$tipo_predominante]['color']
    ];
}

/**
 * Calcular nivel de contenido del álbum
 */
function calcular_nivel_contenido_album($total_items) {
    if ($total_items === 0) {
        return ['nivel' => 'vacio', 'texto' => __('Vacío', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#9ca3af'];
    } elseif ($total_items <= 10) {
        return ['nivel' => 'bajo', 'texto' => __('Pocos archivos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f59e0b'];
    } elseif ($total_items <= 50) {
        return ['nivel' => 'medio', 'texto' => __('Moderado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#3b82f6'];
    } elseif ($total_items <= 100) {
        return ['nivel' => 'alto', 'texto' => __('Abundante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#10b981'];
    }
    return ['nivel' => 'muy_alto', 'texto' => __('Muy completo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#8b5cf6'];
}

/**
 * Formatear tamaño de archivo
 */
function formatear_tamano_archivo($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

// =====================================================
// VERIFICAR EXISTENCIA DE TABLAS
// =====================================================

$tabla_albumes_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_albumes
)) > 0;

$tabla_multimedia_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_multimedia
)) > 0;

$usar_datos_demo = !$tabla_albumes_existe;

// =====================================================
// DATOS DEMO
// =====================================================

if ($usar_datos_demo) {
    $albumes_demo = [
        (object)[
            'id' => 1,
            'titulo' => 'Eventos Comunitarios 2024',
            'descripcion' => 'Fotografías de los principales eventos organizados por la comunidad durante el año 2024, incluyendo fiestas patronales y jornadas deportivas.',
            'publico' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'usuario_id' => 1,
            'total_items' => 47,
            'total_fotos' => 38,
            'total_videos' => 6,
            'total_audios' => 0,
            'total_documentos' => 3,
            'tamano_total' => 524288000, // 500 MB
            'imagen_portada' => null,
            'vistas' => 234
        ],
        (object)[
            'id' => 2,
            'titulo' => 'Documentación Técnica',
            'descripcion' => 'Manuales, guías y documentos técnicos para la gestión de la plataforma.',
            'publico' => 0,
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-12 days')),
            'usuario_id' => 1,
            'total_items' => 23,
            'total_fotos' => 5,
            'total_videos' => 2,
            'total_audios' => 0,
            'total_documentos' => 16,
            'tamano_total' => 157286400, // 150 MB
            'imagen_portada' => null,
            'vistas' => 89
        ],
        (object)[
            'id' => 3,
            'titulo' => 'Podcast Vecinal',
            'descripcion' => 'Episodios del podcast comunitario con entrevistas a vecinos y noticias locales.',
            'publico' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-18 days')),
            'usuario_id' => 2,
            'total_items' => 34,
            'total_fotos' => 8,
            'total_videos' => 0,
            'total_audios' => 26,
            'total_documentos' => 0,
            'tamano_total' => 892313600, // 851 MB
            'imagen_portada' => null,
            'vistas' => 567
        ],
        (object)[
            'id' => 4,
            'titulo' => 'Galería Histórica',
            'descripcion' => 'Archivo fotográfico histórico del barrio con imágenes desde 1950 hasta la actualidad.',
            'publico' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'usuario_id' => 1,
            'total_items' => 156,
            'total_fotos' => 152,
            'total_videos' => 4,
            'total_audios' => 0,
            'total_documentos' => 0,
            'tamano_total' => 2147483648, // 2 GB
            'imagen_portada' => null,
            'vistas' => 1203
        ],
        (object)[
            'id' => 5,
            'titulo' => 'Tutoriales en Video',
            'descripcion' => 'Videos instructivos sobre el uso de las herramientas de la plataforma.',
            'publico' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-45 days')),
            'usuario_id' => 3,
            'total_items' => 18,
            'total_fotos' => 0,
            'total_videos' => 18,
            'total_audios' => 0,
            'total_documentos' => 0,
            'tamano_total' => 3221225472, // 3 GB
            'imagen_portada' => null,
            'vistas' => 445
        ],
        (object)[
            'id' => 6,
            'titulo' => 'Nuevo Álbum',
            'descripcion' => 'Álbum recién creado sin contenido todavía.',
            'publico' => 0,
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'usuario_id' => 1,
            'total_items' => 0,
            'total_fotos' => 0,
            'total_videos' => 0,
            'total_audios' => 0,
            'total_documentos' => 0,
            'tamano_total' => 0,
            'imagen_portada' => null,
            'vistas' => 3
        ],
    ];
}

// =====================================================
// PARÁMETROS DE FILTRADO Y PAGINACIÓN
// =====================================================

$busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$visibilidad_filtro = isset($_GET['visibilidad']) ? sanitize_text_field($_GET['visibilidad']) : '';
$tipo_contenido_filtro = isset($_GET['tipo_contenido']) ? sanitize_text_field($_GET['tipo_contenido']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'fecha_desc';
$pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$items_por_pagina = 12;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// =====================================================
// OBTENER DATOS
// =====================================================

if ($usar_datos_demo) {
    $albumes_filtrados = $albumes_demo;

    // Aplicar filtros a datos demo
    if (!empty($busqueda)) {
        $albumes_filtrados = array_filter($albumes_filtrados, function($album) use ($busqueda) {
            return stripos($album->titulo, $busqueda) !== false ||
                   stripos($album->descripcion, $busqueda) !== false;
        });
    }

    if (!empty($visibilidad_filtro)) {
        $albumes_filtrados = array_filter($albumes_filtrados, function($album) use ($visibilidad_filtro) {
            if ($visibilidad_filtro === 'publico') return $album->publico == 1;
            if ($visibilidad_filtro === 'privado') return $album->publico == 0;
            return true;
        });
    }

    if (!empty($tipo_contenido_filtro)) {
        $albumes_filtrados = array_filter($albumes_filtrados, function($album) use ($tipo_contenido_filtro) {
            switch ($tipo_contenido_filtro) {
                case 'fotos': return $album->total_fotos > 0;
                case 'videos': return $album->total_videos > 0;
                case 'audios': return $album->total_audios > 0;
                case 'documentos': return $album->total_documentos > 0;
                case 'vacio': return $album->total_items == 0;
                default: return true;
            }
        });
    }

    // Ordenar
    usort($albumes_filtrados, function($a, $b) use ($orden) {
        switch ($orden) {
            case 'fecha_asc': return strtotime($a->fecha_creacion) - strtotime($b->fecha_creacion);
            case 'nombre_asc': return strcasecmp($a->titulo, $b->titulo);
            case 'nombre_desc': return strcasecmp($b->titulo, $a->titulo);
            case 'items_desc': return $b->total_items - $a->total_items;
            case 'items_asc': return $a->total_items - $b->total_items;
            case 'vistas_desc': return $b->vistas - $a->vistas;
            default: return strtotime($b->fecha_creacion) - strtotime($a->fecha_creacion);
        }
    });

    $total_albumes_filtrados = count($albumes_filtrados);
    $albumes = array_slice(array_values($albumes_filtrados), $offset, $items_por_pagina);

    // Estadísticas demo
    $total_albumes = count($albumes_demo);
    $albumes_publicos = count(array_filter($albumes_demo, fn($a) => $a->publico == 1));
    $total_archivos = array_sum(array_column($albumes_demo, 'total_items'));
    $almacenamiento_total = array_sum(array_column($albumes_demo, 'tamano_total'));

    // Conteo por tipo de contenido
    $total_fotos = array_sum(array_column($albumes_demo, 'total_fotos'));
    $total_videos = array_sum(array_column($albumes_demo, 'total_videos'));
    $total_audios = array_sum(array_column($albumes_demo, 'total_audios'));
    $total_documentos = array_sum(array_column($albumes_demo, 'total_documentos'));

} else {
    // Construir consulta real
    $where_conditions = ["1=1"];
    $having_conditions = [];
    $params = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(a.titulo LIKE %s OR a.descripcion LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
    }

    if (!empty($visibilidad_filtro)) {
        if ($visibilidad_filtro === 'publico') {
            $where_conditions[] = "a.publico = 1";
        } elseif ($visibilidad_filtro === 'privado') {
            $where_conditions[] = "a.publico = 0";
        }
    }

    if (!empty($tipo_contenido_filtro)) {
        switch ($tipo_contenido_filtro) {
            case 'fotos':
                $having_conditions[] = "total_fotos > 0";
                break;
            case 'videos':
                $having_conditions[] = "total_videos > 0";
                break;
            case 'audios':
                $having_conditions[] = "total_audios > 0";
                break;
            case 'documentos':
                $having_conditions[] = "total_documentos > 0";
                break;
            case 'vacio':
                $having_conditions[] = "total_items = 0";
                break;
        }
    }

    $where_sql = implode(' AND ', $where_conditions);
    $having_sql = !empty($having_conditions) ? 'HAVING ' . implode(' AND ', $having_conditions) : '';

    switch ($orden) {
        case 'fecha_asc': $order_sql = 'a.fecha_creacion ASC'; break;
        case 'nombre_asc': $order_sql = 'a.titulo ASC'; break;
        case 'nombre_desc': $order_sql = 'a.titulo DESC'; break;
        case 'items_desc': $order_sql = 'total_items DESC'; break;
        case 'items_asc': $order_sql = 'total_items ASC'; break;
        case 'vistas_desc': $order_sql = 'a.vistas DESC'; break;
        default: $order_sql = 'a.fecha_creacion DESC';
    }

    // Query para obtener álbumes con conteos por tipo
    $query_base = "
        SELECT a.*,
               COUNT(m.id) as total_items,
               SUM(CASE WHEN m.tipo = 'imagen' THEN 1 ELSE 0 END) as total_fotos,
               SUM(CASE WHEN m.tipo = 'video' THEN 1 ELSE 0 END) as total_videos,
               SUM(CASE WHEN m.tipo = 'audio' THEN 1 ELSE 0 END) as total_audios,
               SUM(CASE WHEN m.tipo = 'documento' THEN 1 ELSE 0 END) as total_documentos,
               COALESCE(SUM(m.tamano), 0) as tamano_total,
               (SELECT url FROM $tabla_multimedia WHERE album_id = a.id AND tipo = 'imagen' ORDER BY fecha_creacion LIMIT 1) as imagen_portada
        FROM $tabla_albumes a
        LEFT JOIN $tabla_multimedia m ON a.id = m.album_id
        WHERE $where_sql
        GROUP BY a.id
        $having_sql
    ";

    // Contar total para paginación
    $count_query = "SELECT COUNT(*) FROM ($query_base) as subquery";
    if (!empty($params)) {
        $total_albumes_filtrados = $wpdb->get_var($wpdb->prepare($count_query, $params));
    } else {
        $total_albumes_filtrados = $wpdb->get_var($count_query);
    }

    // Obtener álbumes paginados
    $query_paginada = "$query_base ORDER BY $order_sql LIMIT %d OFFSET %d";
    $params_paginados = array_merge($params, [$items_por_pagina, $offset]);
    $albumes = $wpdb->get_results($wpdb->prepare($query_paginada, $params_paginados));

    // Estadísticas generales
    $total_albumes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_albumes");
    $albumes_publicos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_albumes WHERE publico = 1");
    $total_archivos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia");
    $almacenamiento_total = $wpdb->get_var("SELECT COALESCE(SUM(tamano), 0) FROM $tabla_multimedia") ?: 0;

    // Conteo por tipo
    $conteo_tipos = $wpdb->get_row("
        SELECT
            SUM(CASE WHEN tipo = 'imagen' THEN 1 ELSE 0 END) as total_fotos,
            SUM(CASE WHEN tipo = 'video' THEN 1 ELSE 0 END) as total_videos,
            SUM(CASE WHEN tipo = 'audio' THEN 1 ELSE 0 END) as total_audios,
            SUM(CASE WHEN tipo = 'documento' THEN 1 ELSE 0 END) as total_documentos
        FROM $tabla_multimedia
    ");

    $total_fotos = $conteo_tipos->total_fotos ?? 0;
    $total_videos = $conteo_tipos->total_videos ?? 0;
    $total_audios = $conteo_tipos->total_audios ?? 0;
    $total_documentos = $conteo_tipos->total_documentos ?? 0;
}

$total_paginas = ceil($total_albumes_filtrados / $items_por_pagina);
$albumes_privados = $total_albumes - $albumes_publicos;

// Top álbumes por vistas (para sidebar)
if ($usar_datos_demo) {
    $top_albumes = array_slice($albumes_demo, 0, 5);
    usort($top_albumes, fn($a, $b) => $b->vistas - $a->vistas);
} else {
    $top_albumes = $wpdb->get_results("
        SELECT a.*, COUNT(m.id) as total_items
        FROM $tabla_albumes a
        LEFT JOIN $tabla_multimedia m ON a.id = m.album_id
        GROUP BY a.id
        ORDER BY a.vistas DESC
        LIMIT 5
    ");
}
?>

<div class="wrap flavor-albumes-dashboard">

    <!-- Encabezado -->
    <div class="flavor-dashboard-header">
        <div class="flavor-header-content">
            <h1>
                <span class="dashicons dashicons-images-alt2"></span>
                <?php echo esc_html__('Gestión de Álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="flavor-header-descripcion">
                <?php echo esc_html__('Organiza y gestiona tu biblioteca multimedia en álbumes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="flavor-header-acciones">
            <button type="button" class="button button-primary button-hero" onclick="abrirModalNuevoAlbum();">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php echo esc_html__('Nuevo Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </div>

    <?php if ($usar_datos_demo): ?>
    <div class="notice notice-info" style="margin: 20px 0;">
        <p>
            <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
            <strong><?php echo esc_html__('Modo demostración:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <?php echo esc_html__('Las tablas de multimedia no existen. Se muestran datos de ejemplo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card" style="--gradient-start: #667eea; --gradient-end: #764ba2;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-images-alt2"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($total_albumes); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="--gradient-start: #11998e; --gradient-end: #38ef7d;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($albumes_publicos); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Álbumes Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="--gradient-start: #fc4a1a; --gradient-end: #f7b733;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-admin-media"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($total_archivos); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="--gradient-start: #4776e6; --gradient-end: #8e54e9;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-database"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo formatear_tamano_archivo($almacenamiento_total); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Almacenamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout Principal -->
    <div class="flavor-main-layout">

        <!-- Contenido Principal -->
        <div class="flavor-content-area">

            <!-- Filtros -->
            <div class="flavor-filtros-card">
                <form method="get" class="flavor-filtros-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'multimedia-albumes'); ?>">

                    <div class="flavor-filtro-grupo">
                        <label for="busqueda">
                            <span class="dashicons dashicons-search"></span>
                            <?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="text" id="busqueda" name="busqueda"
                               value="<?php echo esc_attr($busqueda); ?>"
                               placeholder="<?php echo esc_attr__('Nombre o descripción...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="visibilidad">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php echo esc_html__('Visibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="visibilidad" name="visibilidad">
                            <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="publico" <?php selected($visibilidad_filtro, 'publico'); ?>><?php echo esc_html__('Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="privado" <?php selected($visibilidad_filtro, 'privado'); ?>><?php echo esc_html__('Privados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="tipo_contenido">
                            <span class="dashicons dashicons-category"></span>
                            <?php echo esc_html__('Tipo Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="tipo_contenido" name="tipo_contenido">
                            <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="fotos" <?php selected($tipo_contenido_filtro, 'fotos'); ?>><?php echo esc_html__('Con fotos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="videos" <?php selected($tipo_contenido_filtro, 'videos'); ?>><?php echo esc_html__('Con videos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="audios" <?php selected($tipo_contenido_filtro, 'audios'); ?>><?php echo esc_html__('Con audios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="documentos" <?php selected($tipo_contenido_filtro, 'documentos'); ?>><?php echo esc_html__('Con documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="vacio" <?php selected($tipo_contenido_filtro, 'vacio'); ?>><?php echo esc_html__('Vacíos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="orden">
                            <span class="dashicons dashicons-sort"></span>
                            <?php echo esc_html__('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="orden" name="orden">
                            <option value="fecha_desc" <?php selected($orden, 'fecha_desc'); ?>><?php echo esc_html__('Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="fecha_asc" <?php selected($orden, 'fecha_asc'); ?>><?php echo esc_html__('Más antiguos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="nombre_asc" <?php selected($orden, 'nombre_asc'); ?>><?php echo esc_html__('Nombre A-Z', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="nombre_desc" <?php selected($orden, 'nombre_desc'); ?>><?php echo esc_html__('Nombre Z-A', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="items_desc" <?php selected($orden, 'items_desc'); ?>><?php echo esc_html__('Más archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="items_asc" <?php selected($orden, 'items_asc'); ?>><?php echo esc_html__('Menos archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="vistas_desc" <?php selected($orden, 'vistas_desc'); ?>><?php echo esc_html__('Más vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-acciones">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-filter"></span>
                            <?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_GET['page'] ?? 'multimedia-albumes'))); ?>" class="button">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Información de Resultados -->
            <div class="flavor-resultados-info">
                <span class="flavor-resultados-contador">
                    <?php
                    printf(
                        esc_html__('Mostrando %1$d-%2$d de %3$d álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        min($offset + 1, $total_albumes_filtrados),
                        min($offset + $items_por_pagina, $total_albumes_filtrados),
                        $total_albumes_filtrados
                    );
                    ?>
                </span>
                <?php if (!empty($busqueda) || !empty($visibilidad_filtro) || !empty($tipo_contenido_filtro)): ?>
                    <span class="flavor-filtros-activos">
                        <span class="dashicons dashicons-filter"></span>
                        <?php echo esc_html__('Filtros activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Grid de Álbumes -->
            <?php if (empty($albumes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <h3><?php echo esc_html__('No hay álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php echo esc_html__('No se encontraron álbumes con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button onclick="abrirModalNuevoAlbum()" class="button button-primary button-large">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php echo esc_html__('Crear Primer Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="flavor-albumes-grid">
                    <?php foreach ($albumes as $album):
                        $visibilidad_badge = obtener_badge_visibilidad_album($album->publico);
                        $fotos = $album->total_fotos ?? 0;
                        $videos = $album->total_videos ?? 0;
                        $audios = $album->total_audios ?? 0;
                        $documentos = $album->total_documentos ?? 0;
                        $tipo_contenido = obtener_tipo_contenido_album($fotos, $videos, $audios, $documentos);
                        $nivel_contenido = calcular_nivel_contenido_album($album->total_items);
                        $tamano = isset($album->tamano_total) ? formatear_tamano_archivo($album->tamano_total) : '0 B';
                    ?>
                        <div class="flavor-album-card" onclick="verAlbum(<?php echo $album->id; ?>)">
                            <!-- Portada del álbum -->
                            <div class="flavor-album-portada" style="--tipo-color: <?php echo esc_attr($tipo_contenido['color']); ?>;">
                                <?php if (!empty($album->imagen_portada)): ?>
                                    <img src="<?php echo esc_url($album->imagen_portada); ?>" alt="<?php echo esc_attr($album->titulo); ?>">
                                <?php else: ?>
                                    <div class="flavor-album-placeholder">
                                        <span class="dashicons dashicons-<?php echo esc_attr($tipo_contenido['icono']); ?>"></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Badge de visibilidad -->
                                <span class="flavor-album-visibilidad flavor-badge flavor-badge-<?php echo esc_attr($visibilidad_badge['clase']); ?>">
                                    <span class="dashicons dashicons-<?php echo esc_attr($visibilidad_badge['icono']); ?>"></span>
                                    <?php echo esc_html($visibilidad_badge['texto']); ?>
                                </span>

                                <!-- Badge de cantidad -->
                                <span class="flavor-album-cantidad">
                                    <span class="dashicons dashicons-admin-media"></span>
                                    <?php echo number_format($album->total_items); ?>
                                </span>
                            </div>

                            <!-- Contenido del álbum -->
                            <div class="flavor-album-content">
                                <h3 class="flavor-album-titulo">
                                    <?php echo esc_html($album->titulo); ?>
                                </h3>

                                <?php if (!empty($album->descripcion)): ?>
                                    <p class="flavor-album-descripcion">
                                        <?php echo esc_html(wp_trim_words($album->descripcion, 15)); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Desglose de contenido -->
                                <div class="flavor-album-desglose">
                                    <?php if ($fotos > 0): ?>
                                        <span class="flavor-desglose-item" title="<?php echo esc_attr__('Fotos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-format-image" style="color: #3b82f6;"></span>
                                            <?php echo $fotos; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($videos > 0): ?>
                                        <span class="flavor-desglose-item" title="<?php echo esc_attr__('Videos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-video-alt3" style="color: #ef4444;"></span>
                                            <?php echo $videos; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($audios > 0): ?>
                                        <span class="flavor-desglose-item" title="<?php echo esc_attr__('Audios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-format-audio" style="color: #8b5cf6;"></span>
                                            <?php echo $audios; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($documentos > 0): ?>
                                        <span class="flavor-desglose-item" title="<?php echo esc_attr__('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-media-document" style="color: #f59e0b;"></span>
                                            <?php echo $documentos; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Meta info -->
                                <div class="flavor-album-meta">
                                    <span class="flavor-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <?php echo date_i18n('d/m/Y', strtotime($album->fecha_creacion)); ?>
                                    </span>
                                    <span class="flavor-meta-item">
                                        <span class="dashicons dashicons-database"></span>
                                        <?php echo $tamano; ?>
                                    </span>
                                </div>

                                <!-- Indicador de nivel -->
                                <div class="flavor-album-nivel">
                                    <div class="flavor-nivel-barra">
                                        <div class="flavor-nivel-fill" style="width: <?php echo min(100, $album->total_items); ?>%; background: <?php echo esc_attr($nivel_contenido['color']); ?>;"></div>
                                    </div>
                                    <span class="flavor-nivel-texto" style="color: <?php echo esc_attr($nivel_contenido['color']); ?>;">
                                        <?php echo esc_html($nivel_contenido['texto']); ?>
                                    </span>
                                </div>

                                <!-- Acciones -->
                                <div class="flavor-album-acciones">
                                    <button onclick="event.stopPropagation(); editarAlbum(<?php echo $album->id; ?>)" class="button" title="<?php echo esc_attr__('Editar álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php echo esc_html__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <button onclick="event.stopPropagation(); verAlbum(<?php echo $album->id; ?>)" class="button button-primary" title="<?php echo esc_attr__('Ver contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flavor-paginacion">
                        <?php
                        $paginacion_args = [
                            'base' => add_query_arg('pag', '%#%'),
                            'format' => '',
                            'current' => $pagina_actual,
                            'total' => $total_paginas,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'next_text' => __('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                            'type' => 'list',
                            'end_size' => 1,
                            'mid_size' => 2,
                        ];

                        // Preservar filtros en paginación
                        if (!empty($busqueda)) {
                            $paginacion_args['add_args']['busqueda'] = $busqueda;
                        }
                        if (!empty($visibilidad_filtro)) {
                            $paginacion_args['add_args']['visibilidad'] = $visibilidad_filtro;
                        }
                        if (!empty($tipo_contenido_filtro)) {
                            $paginacion_args['add_args']['tipo_contenido'] = $tipo_contenido_filtro;
                        }
                        if (!empty($orden) && $orden !== 'fecha_desc') {
                            $paginacion_args['add_args']['orden'] = $orden;
                        }

                        echo paginate_links($paginacion_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="flavor-sidebar">

            <!-- Top Álbumes -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php echo esc_html__('Álbumes Populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-top-lista">
                    <?php
                    $posicion = 0;
                    foreach ($top_albumes as $top_album):
                        $posicion++;
                        $vistas_album = $top_album->vistas ?? 0;
                    ?>
                        <div class="flavor-top-item" onclick="verAlbum(<?php echo $top_album->id; ?>)">
                            <span class="flavor-top-posicion <?php echo $posicion <= 3 ? 'top-' . $posicion : ''; ?>">
                                <?php echo $posicion; ?>
                            </span>
                            <div class="flavor-top-info">
                                <span class="flavor-top-nombre"><?php echo esc_html(wp_trim_words($top_album->titulo, 4)); ?></span>
                                <span class="flavor-top-stats">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo number_format($vistas_album); ?> vistas
                                </span>
                            </div>
                            <span class="flavor-top-items">
                                <?php echo number_format($top_album->total_items ?? 0); ?>
                                <small><?php echo esc_html__('items', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gráfico de Tipo de Contenido -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Por Tipo de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-chart-container">
                    <canvas id="chartTipoContenido" width="200" height="200"></canvas>
                </div>
                <div class="flavor-chart-leyenda">
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #3b82f6;"></span>
                        <span class="flavor-leyenda-texto"><?php echo esc_html__('Fotos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($total_fotos); ?></span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #ef4444;"></span>
                        <span class="flavor-leyenda-texto"><?php echo esc_html__('Videos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($total_videos); ?></span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #8b5cf6;"></span>
                        <span class="flavor-leyenda-texto"><?php echo esc_html__('Audios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($total_audios); ?></span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #f59e0b;"></span>
                        <span class="flavor-leyenda-texto"><?php echo esc_html__('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($total_documentos); ?></span>
                    </div>
                </div>
            </div>

            <!-- Leyenda de Visibilidad -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php echo esc_html__('Visibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-visibilidad-stats">
                    <div class="flavor-vis-stat">
                        <div class="flavor-vis-icono" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <div class="flavor-vis-info">
                            <span class="flavor-vis-numero"><?php echo number_format($albumes_publicos); ?></span>
                            <span class="flavor-vis-label"><?php echo esc_html__('Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                    <div class="flavor-vis-stat">
                        <div class="flavor-vis-icono" style="background: linear-gradient(135deg, #6b7280, #9ca3af);">
                            <span class="dashicons dashicons-hidden"></span>
                        </div>
                        <div class="flavor-vis-info">
                            <span class="flavor-vis-numero"><?php echo number_format($albumes_privados); ?></span>
                            <span class="flavor-vis-label"><?php echo esc_html__('Privados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php echo esc_html__('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-acciones-rapidas">
                    <button onclick="abrirModalNuevoAlbum()" class="flavor-accion-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php echo esc_html__('Crear Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-galeria')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-format-gallery"></span>
                        <?php echo esc_html__('Ver Galería', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-subir')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <?php echo esc_html__('Subir Archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-configuracion')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php echo esc_html__('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Nuevo Álbum -->
<div id="modal-album" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalAlbum()"></div>
    <div class="flavor-modal-content">
        <button class="flavor-modal-close" onclick="cerrarModalAlbum()">&times;</button>
        <div class="flavor-modal-header">
            <span class="dashicons dashicons-images-alt2"></span>
            <h3><?php echo esc_html__('Crear Nuevo Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <form id="form-nuevo-album" method="post">
            <?php wp_nonce_field('nuevo_album', 'album_nonce'); ?>
            <input type="hidden" name="accion" value="crear_album">

            <div class="flavor-form-group">
                <label for="album_nombre">
                    <span class="dashicons dashicons-edit"></span>
                    <?php echo esc_html__('Nombre del álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" id="album_nombre" name="nombre" required
                       placeholder="<?php echo esc_attr__('Ej: Fotos del evento 2024', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="flavor-form-group">
                <label for="album_descripcion">
                    <span class="dashicons dashicons-text"></span>
                    <?php echo esc_html__('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <textarea id="album_descripcion" name="descripcion" rows="3"
                          placeholder="<?php echo esc_attr__('Describe el contenido del álbum...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="flavor-form-group">
                <label>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php echo esc_html__('Visibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <div class="flavor-radio-group">
                    <label class="flavor-radio-option">
                        <input type="radio" name="publico" value="1" checked>
                        <span class="flavor-radio-label">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php echo esc_html__('Público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                        <small><?php echo esc_html__('Visible para todos los usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                    </label>
                    <label class="flavor-radio-option">
                        <input type="radio" name="publico" value="0">
                        <span class="flavor-radio-label">
                            <span class="dashicons dashicons-hidden"></span>
                            <?php echo esc_html__('Privado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                        <small><?php echo esc_html__('Solo visible para administradores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                    </label>
                </div>
            </div>

            <div class="flavor-modal-acciones">
                <button type="button" class="button" onclick="cerrarModalAlbum()">
                    <?php echo esc_html__('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html__('Crear Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de tipo de contenido
    const ctxTipo = document.getElementById('chartTipoContenido');
    if (ctxTipo) {
        new Chart(ctxTipo.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Fotos', 'Videos', 'Audios', 'Documentos'],
                datasets: [{
                    data: [
                        <?php echo intval($total_fotos); ?>,
                        <?php echo intval($total_videos); ?>,
                        <?php echo intval($total_audios); ?>,
                        <?php echo intval($total_documentos); ?>
                    ],
                    backgroundColor: ['#3b82f6', '#ef4444', '#8b5cf6', '#f59e0b'],
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
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const porcentaje = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.raw + ' (' + porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});

function abrirModalNuevoAlbum() {
    document.getElementById('modal-album').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalAlbum() {
    document.getElementById('modal-album').style.display = 'none';
    document.body.style.overflow = '';
}

function verAlbum(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=multimedia-galeria&album_id='); ?>' + id;
}

function editarAlbum(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=multimedia-albumes&editar='); ?>' + id;
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAlbum();
    }
});
</script>

<style>
/* =====================================================
   ESTILOS GENERALES
   ===================================================== */
.flavor-albumes-dashboard {
    max-width: 1800px;
    margin: 0 auto;
}

/* Header */
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

.flavor-dashboard-header h1 {
    color: #fff;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
}

.flavor-dashboard-header h1 .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.flavor-header-descripcion {
    margin: 8px 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.flavor-header-acciones .button-hero {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.5);
    color: #fff;
    padding: 10px 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.flavor-header-acciones .button-hero:hover {
    background: rgba(255,255,255,0.3);
    border-color: #fff;
    color: #fff;
}

/* =====================================================
   TARJETAS DE ESTADÍSTICAS
   ===================================================== */
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.flavor-stat-card {
    background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    color: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.flavor-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.flavor-stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.flavor-stat-content {
    display: flex;
    flex-direction: column;
}

.flavor-stat-numero {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
}

.flavor-stat-label {
    font-size: 13px;
    opacity: 0.9;
}

/* =====================================================
   LAYOUT PRINCIPAL
   ===================================================== */
.flavor-main-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
}

@media (max-width: 1200px) {
    .flavor-main-layout {
        grid-template-columns: 1fr;
    }

    .flavor-sidebar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
}

/* =====================================================
   FILTROS
   ===================================================== */
.flavor-filtros-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.flavor-filtros-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.flavor-filtro-grupo {
    flex: 1;
    min-width: 150px;
}

.flavor-filtro-grupo label {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 12px;
    color: #374151;
    text-transform: uppercase;
}

.flavor-filtro-grupo label .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #6b7280;
}

.flavor-filtro-grupo input,
.flavor-filtro-grupo select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.flavor-filtro-grupo input:focus,
.flavor-filtro-grupo select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.flavor-filtro-acciones {
    display: flex;
    gap: 10px;
}

.flavor-filtro-acciones .button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 10px 16px;
}

/* =====================================================
   RESULTADOS INFO
   ===================================================== */
.flavor-resultados-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 15px;
    background: #f8fafc;
    border-radius: 8px;
    font-size: 13px;
}

.flavor-resultados-contador {
    color: #6b7280;
}

.flavor-filtros-activos {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #667eea;
    font-weight: 500;
}

/* =====================================================
   GRID DE ÁLBUMES
   ===================================================== */
.flavor-albumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.flavor-album-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
}

.flavor-album-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

/* Portada */
.flavor-album-portada {
    position: relative;
    padding-top: 65%;
    background: linear-gradient(135deg, var(--tipo-color, #667eea), color-mix(in srgb, var(--tipo-color, #667eea), black 20%));
    overflow: hidden;
}

.flavor-album-portada img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-album-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-album-placeholder .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: rgba(255,255,255,0.3);
}

.flavor-album-visibilidad {
    position: absolute;
    top: 12px;
    left: 12px;
}

.flavor-album-cantidad {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    background: rgba(0,0,0,0.7);
    color: #fff;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-album-cantidad .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Contenido */
.flavor-album-content {
    padding: 18px;
}

.flavor-album-titulo {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.3;
}

.flavor-album-descripcion {
    margin: 0 0 12px;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}

/* Desglose */
.flavor-album-desglose {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.flavor-desglose-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #6b7280;
}

.flavor-desglose-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Meta */
.flavor-album-meta {
    display: flex;
    gap: 15px;
    padding: 10px 0;
    border-top: 1px solid #f0f0f1;
    font-size: 12px;
    color: #9ca3af;
}

.flavor-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.flavor-meta-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Nivel */
.flavor-album-nivel {
    margin: 12px 0;
}

.flavor-nivel-barra {
    height: 6px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.flavor-nivel-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

.flavor-nivel-texto {
    font-size: 11px;
    font-weight: 500;
}

/* Acciones */
.flavor-album-acciones {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.flavor-album-acciones .button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 8px 12px;
    font-size: 12px;
}

/* =====================================================
   BADGES
   ===================================================== */
.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-badge-success {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}

.flavor-badge-secondary {
    background: rgba(107, 114, 128, 0.15);
    color: #4b5563;
}

.flavor-badge-info {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
}

.flavor-badge-warning {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
}

.flavor-badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

/* =====================================================
   EMPTY STATE
   ===================================================== */
.flavor-empty-state {
    text-align: center;
    padding: 60px 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.flavor-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #d1d5db;
    margin-bottom: 15px;
}

.flavor-empty-state h3 {
    margin: 0 0 10px;
    color: #374151;
}

.flavor-empty-state p {
    color: #6b7280;
    margin: 0 0 20px;
}

/* =====================================================
   PAGINACIÓN
   ===================================================== */
.flavor-paginacion {
    margin-top: 25px;
    display: flex;
    justify-content: center;
}

.flavor-paginacion ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 5px;
}

.flavor-paginacion li {
    margin: 0;
}

.flavor-paginacion a,
.flavor-paginacion span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s ease;
}

.flavor-paginacion a:hover {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

.flavor-paginacion .current {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

/* =====================================================
   SIDEBAR
   ===================================================== */
.flavor-sidebar-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.flavor-sidebar-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.flavor-sidebar-titulo .dashicons {
    color: #667eea;
}

/* Top Lista */
.flavor-top-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-top-item:hover {
    background: #f1f5f9;
}

.flavor-top-posicion {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
    border-radius: 50%;
    font-weight: 700;
    font-size: 12px;
    color: #6b7280;
}

.flavor-top-posicion.top-1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #fff;
}

.flavor-top-posicion.top-2 {
    background: linear-gradient(135deg, #9ca3af, #6b7280);
    color: #fff;
}

.flavor-top-posicion.top-3 {
    background: linear-gradient(135deg, #cd7f32, #a0522d);
    color: #fff;
}

.flavor-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-top-nombre {
    display: block;
    font-weight: 500;
    font-size: 13px;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-top-stats {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #9ca3af;
}

.flavor-top-stats .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-top-items {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-weight: 700;
    font-size: 14px;
    color: #667eea;
}

.flavor-top-items small {
    font-weight: 400;
    font-size: 10px;
    color: #9ca3af;
}

/* Chart */
.flavor-chart-container {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.flavor-chart-leyenda {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
}

.flavor-leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

.flavor-leyenda-texto {
    flex: 1;
    color: #6b7280;
}

.flavor-leyenda-valor {
    font-weight: 600;
    color: #1f2937;
}

/* Visibilidad Stats */
.flavor-visibilidad-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-vis-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
}

.flavor-vis-icono {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-vis-icono .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.flavor-vis-info {
    display: flex;
    flex-direction: column;
}

.flavor-vis-numero {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.flavor-vis-label {
    font-size: 12px;
    color: #6b7280;
}

/* Acciones Rápidas */
.flavor-acciones-rapidas {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-accion-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.flavor-accion-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

.flavor-accion-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* =====================================================
   MODAL
   ===================================================== */
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
    backdrop-filter: blur(4px);
}

.flavor-modal-content {
    position: relative;
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    min-width: 450px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.flavor-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 32px;
    height: 32px;
    background: #f3f4f6;
    border: none;
    border-radius: 50%;
    font-size: 20px;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.flavor-modal-close:hover {
    background: #ef4444;
    color: #fff;
}

.flavor-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
}

.flavor-modal-header .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #667eea;
}

.flavor-modal-header h3 {
    margin: 0;
    font-size: 20px;
    color: #1f2937;
}

/* Form Groups */
.flavor-form-group {
    margin-bottom: 20px;
}

.flavor-form-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 13px;
    color: #374151;
}

.flavor-form-group label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #6b7280;
}

.flavor-form-group label .required {
    color: #ef4444;
}

.flavor-form-group input[type="text"],
.flavor-form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.flavor-form-group input[type="text"]:focus,
.flavor-form-group textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

/* Radio Group */
.flavor-radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-radio-option {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-radio-option:hover {
    border-color: #667eea;
}

.flavor-radio-option input[type="radio"] {
    margin: 0;
}

.flavor-radio-option input[type="radio"]:checked + .flavor-radio-label {
    color: #667eea;
}

.flavor-radio-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
    color: #374151;
}

.flavor-radio-label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-radio-option small {
    width: 100%;
    padding-left: 28px;
    font-size: 12px;
    color: #9ca3af;
}

/* Modal Actions */
.flavor-modal-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.flavor-modal-acciones .button {
    padding: 10px 20px;
}

.flavor-modal-acciones .button-primary {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* =====================================================
   RESPONSIVE
   ===================================================== */
@media (max-width: 782px) {
    .flavor-dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }

    .flavor-filtros-form {
        flex-direction: column;
    }

    .flavor-filtro-grupo {
        width: 100%;
    }

    .flavor-albumes-grid {
        grid-template-columns: 1fr;
    }

    .flavor-modal-content {
        min-width: auto;
        width: 95%;
        padding: 20px;
    }
}
</style>
