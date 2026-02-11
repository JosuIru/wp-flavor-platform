<?php
/**
 * Template: Single Grupo de Consumo (gc_grupo)
 *
 * Muestra la vista individual de un grupo de consumo con:
 * - Header del grupo con info principal
 * - Ciclo activo si existe
 * - Productores asociados
 * - Productos destacados
 * - CTA para unirse
 * - Informacion adicional
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $wpdb;

// Obtener datos del grupo
$grupo_id = get_the_ID();
$grupo_nombre = get_the_title();
$grupo_descripcion = get_the_excerpt() ?: wp_trim_words(get_the_content(), 30);
$grupo_descripcion_larga = get_the_content();
$grupo_imagen = get_the_post_thumbnail_url($grupo_id, 'large');

// Meta fields del grupo
$grupo_zona = get_post_meta($grupo_id, '_gc_zona', true);
$grupo_estado = get_post_meta($grupo_id, '_gc_estado', true) ?: 'abierto'; // abierto, cerrado
$grupo_cuota = floatval(get_post_meta($grupo_id, '_gc_cuota', true));
$grupo_dia_pedido = get_post_meta($grupo_id, '_gc_dia_pedido', true) ?: 'Semanal';
$grupo_punto_recogida = get_post_meta($grupo_id, '_gc_punto_recogida', true);
$grupo_horario_recogida = get_post_meta($grupo_id, '_gc_horario_recogida', true);
$grupo_normas = get_post_meta($grupo_id, '_gc_normas', true);
$grupo_email = get_post_meta($grupo_id, '_gc_email', true);
$grupo_telefono = get_post_meta($grupo_id, '_gc_telefono', true);
$grupo_coordinador_id = get_post_meta($grupo_id, '_gc_coordinador_id', true);
$grupo_verificado = get_post_meta($grupo_id, '_gc_verificado', true);
$grupo_icono = get_post_meta($grupo_id, '_gc_icono', true) ?: '🥕';

// Obtener coordinador
$coordinador_nombre = '';
if ($grupo_coordinador_id) {
    $coordinador_usuario = get_userdata($grupo_coordinador_id);
    if ($coordinador_usuario) {
        $coordinador_nombre = $coordinador_usuario->display_name;
    }
}

// Contar miembros del grupo
$tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
$numero_miembros = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_consumidores} WHERE grupo_id = %d AND estado = 'activo'",
    $grupo_id
));

// Verificar si el usuario actual es miembro
$usuario_actual_id = get_current_user_id();
$es_miembro = false;
$estado_membresia = null;
$rol_miembro = null;

if ($usuario_actual_id) {
    $membresia = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tabla_consumidores} WHERE usuario_id = %d AND grupo_id = %d",
        $usuario_actual_id,
        $grupo_id
    ));

    if ($membresia) {
        $es_miembro = ($membresia->estado === 'activo');
        $estado_membresia = $membresia->estado;
        $rol_miembro = $membresia->rol;
    }
}

// Obtener ciclo activo del grupo
$ciclo_activo = get_posts([
    'post_type' => 'gc_ciclo',
    'post_status' => 'gc_abierto',
    'posts_per_page' => 1,
    'meta_query' => [
        [
            'key' => '_gc_grupo_id',
            'value' => $grupo_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'meta_value',
    'meta_key' => '_gc_fecha_cierre',
    'order' => 'ASC'
]);

// Si no hay ciclo asociado al grupo, buscar ciclo global
if (empty($ciclo_activo)) {
    $ciclo_activo = get_posts([
        'post_type' => 'gc_ciclo',
        'post_status' => 'gc_abierto',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
}

$ciclo_info = null;
if (!empty($ciclo_activo)) {
    $ciclo = $ciclo_activo[0];
    $ciclo_info = [
        'id' => $ciclo->ID,
        'nombre' => $ciclo->post_title,
        'fecha_inicio' => get_post_meta($ciclo->ID, '_gc_fecha_inicio', true),
        'fecha_cierre' => get_post_meta($ciclo->ID, '_gc_fecha_cierre', true),
        'fecha_entrega' => get_post_meta($ciclo->ID, '_gc_fecha_entrega', true),
        'hora_entrega' => get_post_meta($ciclo->ID, '_gc_hora_entrega', true),
        'lugar_entrega' => get_post_meta($ciclo->ID, '_gc_lugar_entrega', true),
        'notas' => get_post_meta($ciclo->ID, '_gc_notas', true),
    ];
}

// Obtener productores asociados al grupo
$productores_ids = get_post_meta($grupo_id, '_gc_productores', true);
$productores = [];

if (!empty($productores_ids) && is_array($productores_ids)) {
    $productores_query = new WP_Query([
        'post_type' => 'gc_productor',
        'post__in' => $productores_ids,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    if ($productores_query->have_posts()) {
        while ($productores_query->have_posts()) {
            $productores_query->the_post();
            $productor_id = get_the_ID();
            $radio_entrega = floatval(get_post_meta($productor_id, '_gc_radio_entrega_km', true));
            $productores[] = [
                'id' => $productor_id,
                'nombre' => get_the_title(),
                'descripcion' => wp_trim_words(get_the_content(), 15),
                'imagen' => get_the_post_thumbnail_url($productor_id, 'thumbnail'),
                'ubicacion' => get_post_meta($productor_id, '_gc_ubicacion', true),
                'certificacion_eco' => get_post_meta($productor_id, '_gc_certificacion_eco', true),
                'radio_entrega_km' => $radio_entrega,
                'tiene_entrega' => $radio_entrega > 0,
                'url' => get_permalink($productor_id),
            ];
        }
        wp_reset_postdata();
    }
} else {
    // Si no hay productores asociados, obtener todos los productores
    $productores_query = new WP_Query([
        'post_type' => 'gc_productor',
        'posts_per_page' => 6,
        'post_status' => 'publish'
    ]);

    if ($productores_query->have_posts()) {
        while ($productores_query->have_posts()) {
            $productores_query->the_post();
            $productor_id = get_the_ID();
            $radio_entrega = floatval(get_post_meta($productor_id, '_gc_radio_entrega_km', true));
            $productores[] = [
                'id' => $productor_id,
                'nombre' => get_the_title(),
                'descripcion' => wp_trim_words(get_the_content(), 15),
                'imagen' => get_the_post_thumbnail_url($productor_id, 'thumbnail'),
                'ubicacion' => get_post_meta($productor_id, '_gc_ubicacion', true),
                'certificacion_eco' => get_post_meta($productor_id, '_gc_certificacion_eco', true),
                'radio_entrega_km' => $radio_entrega,
                'tiene_entrega' => $radio_entrega > 0,
                'url' => get_permalink($productor_id),
            ];
        }
        wp_reset_postdata();
    }
}

// Obtener productos destacados (primeros 8)
$productos_query = new WP_Query([
    'post_type' => 'gc_producto',
    'posts_per_page' => 8,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

$productos = [];
if ($productos_query->have_posts()) {
    while ($productos_query->have_posts()) {
        $productos_query->the_post();
        $producto_id = get_the_ID();
        $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
        $productor_post = get_post($productor_id);

        $productos[] = [
            'id' => $producto_id,
            'nombre' => get_the_title(),
            'descripcion' => wp_trim_words(get_the_content(), 10),
            'imagen' => get_the_post_thumbnail_url($producto_id, 'medium'),
            'precio' => floatval(get_post_meta($producto_id, '_gc_precio', true)),
            'unidad' => get_post_meta($producto_id, '_gc_unidad', true) ?: 'kg',
            'productor' => $productor_post ? $productor_post->post_title : '',
            'temporada' => get_post_meta($producto_id, '_gc_temporada', true),
            'url' => get_permalink($producto_id),
        ];
    }
    wp_reset_postdata();
}

// Obtener miembros para mostrar avatares
$miembros_lista = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, u.display_name, u.user_email
     FROM {$tabla_consumidores} c
     JOIN {$wpdb->users} u ON c.usuario_id = u.ID
     WHERE c.grupo_id = %d AND c.estado = 'activo'
     ORDER BY c.fecha_alta DESC
     LIMIT 15",
    $grupo_id
));

// Estadisticas del usuario si es miembro
$estadisticas_usuario = null;
if ($es_miembro && $usuario_actual_id) {
    $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

    $total_pedidos = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT ciclo_id) FROM {$tabla_pedidos} WHERE usuario_id = %d",
        $usuario_actual_id
    ));

    $total_gastado = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(cantidad * precio_unitario) FROM {$tabla_pedidos} WHERE usuario_id = %d",
        $usuario_actual_id
    ));

    $estadisticas_usuario = [
        'total_pedidos' => intval($total_pedidos),
        'total_gastado' => floatval($total_gastado),
        'fecha_alta' => $membresia->fecha_alta ?? '',
    ];
}

// Parsear normas del grupo
$normas_array = [];
if (!empty($grupo_normas)) {
    if (is_string($grupo_normas)) {
        $normas_array = array_filter(array_map('trim', explode("\n", $grupo_normas)));
    } elseif (is_array($grupo_normas)) {
        $normas_array = $grupo_normas;
    }
}
?>

<main id="main-content" class="flex-1 bg-gray-50">

    <div class="flavor-frontend flavor-gc-single max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" aria-label="<?php echo esc_attr__('Breadcrumb', 'flavor-chat-ia'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-lime-600 transition-colors">
                <?php esc_html_e('Inicio', 'flavor-chat-ia'); ?>
            </a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo esc_url(get_post_type_archive_link('gc_grupo')); ?>" class="hover:text-lime-600 transition-colors">
                <?php esc_html_e('Grupos de Consumo', 'flavor-chat-ia'); ?>
            </a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 font-medium"><?php echo esc_html($grupo_nombre); ?></span>
        </nav>

        <!-- 1. HEADER DEL GRUPO -->
        <header class="bg-gradient-to-r from-lime-500 via-green-500 to-emerald-500 text-white rounded-2xl p-6 md:p-10 mb-8 shadow-xl relative overflow-hidden">
            <!-- Patron decorativo -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <circle cx="1" cy="1" r="1" fill="white"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative flex flex-col lg:flex-row items-start gap-6">
                <!-- Imagen o icono -->
                <div class="flex-shrink-0">
                    <?php if ($grupo_imagen): ?>
                        <img src="<?php echo esc_url($grupo_imagen); ?>"
                             alt="<?php echo esc_attr($grupo_nombre); ?>"
                             class="w-28 h-28 md:w-36 md:h-36 rounded-2xl object-cover shadow-lg border-4 border-white/20">
                    <?php else: ?>
                        <div class="w-28 h-28 md:w-36 md:h-36 bg-white/20 rounded-2xl flex items-center justify-center text-6xl md:text-7xl shadow-lg">
                            <?php echo esc_html($grupo_icono); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info principal -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <h1 class="text-2xl md:text-4xl font-bold leading-tight">
                            <?php echo esc_html($grupo_nombre); ?>
                        </h1>
                        <?php if ($grupo_verificado): ?>
                            <span class="inline-flex items-center gap-1 bg-white/20 px-3 py-1 rounded-full text-sm font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <?php esc_html_e('Verificado', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>

                        <!-- Estado del grupo -->
                        <?php if ($grupo_estado === 'cerrado'): ?>
                            <span class="inline-flex items-center gap-1 bg-red-500/80 px-3 py-1 rounded-full text-sm font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php esc_html_e('Cerrado a nuevos miembros', 'flavor-chat-ia'); ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-white/20 px-3 py-1 rounded-full text-sm font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                                <?php esc_html_e('Abierto a nuevos miembros', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-lime-100 text-base md:text-lg mb-5 max-w-2xl">
                        <?php echo esc_html($grupo_descripcion); ?>
                    </p>

                    <!-- Estadisticas rapidas -->
                    <div class="flex flex-wrap gap-4 md:gap-6 text-sm md:text-base">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-semibold"><?php echo intval($numero_miembros); ?></span>
                            <span class="opacity-80"><?php esc_html_e('miembros', 'flavor-chat-ia'); ?></span>
                        </div>

                        <?php if ($grupo_zona): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span><?php echo esc_html($grupo_zona); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span><?php echo esc_html($grupo_dia_pedido); ?></span>
                        </div>

                        <?php if (count($productores) > 0): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span class="font-semibold"><?php echo count($productores); ?></span>
                                <span class="opacity-80"><?php esc_html_e('productores', 'flavor-chat-ia'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones del header -->
                <div class="flex flex-col gap-3 w-full lg:w-auto">
                    <?php if ($es_miembro): ?>
                        <div class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-xl text-center flex items-center gap-2 justify-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium"><?php esc_html_e('Eres miembro', 'flavor-chat-ia'); ?></span>
                            <?php if ($rol_miembro === 'coordinador'): ?>
                                <span class="bg-white/30 px-2 py-0.5 rounded text-xs"><?php esc_html_e('Coordinador', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($ciclo_info): ?>
                            <a href="#productos" class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 transition-all text-center shadow-lg flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <?php esc_html_e('Hacer pedido', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    <?php elseif ($estado_membresia === 'pendiente'): ?>
                        <div class="bg-yellow-500/80 px-4 py-2 rounded-xl text-center">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php esc_html_e('Solicitud pendiente', 'flavor-chat-ia'); ?>
                        </div>
                    <?php elseif ($grupo_estado !== 'cerrado'): ?>
                        <a href="#unirse" class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 transition-all text-center shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            <?php esc_html_e('Unirme al grupo', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- COLUMNA PRINCIPAL -->
            <div class="lg:col-span-2 space-y-8">

                <!-- 2. CICLO ACTIVO -->
                <?php if ($ciclo_info): ?>
                    <section class="bg-gradient-to-r from-amber-50 to-yellow-50 border-2 border-amber-200 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h2 class="text-xl font-bold text-amber-800">
                                        <?php esc_html_e('Ciclo de pedidos abierto', 'flavor-chat-ia'); ?>
                                    </h2>
                                    <span class="animate-pulse inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                    <div class="bg-white/60 rounded-lg p-3">
                                        <p class="text-xs text-amber-600 font-medium uppercase tracking-wide mb-1">
                                            <?php esc_html_e('Cierre de pedidos', 'flavor-chat-ia'); ?>
                                        </p>
                                        <p class="text-amber-900 font-semibold">
                                            <?php
                                            if ($ciclo_info['fecha_cierre']) {
                                                echo esc_html(date_i18n('j M, H:i', strtotime($ciclo_info['fecha_cierre'])));
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="bg-white/60 rounded-lg p-3">
                                        <p class="text-xs text-amber-600 font-medium uppercase tracking-wide mb-1">
                                            <?php esc_html_e('Fecha de entrega', 'flavor-chat-ia'); ?>
                                        </p>
                                        <p class="text-amber-900 font-semibold">
                                            <?php
                                            if ($ciclo_info['fecha_entrega']) {
                                                echo esc_html(date_i18n('l j M', strtotime($ciclo_info['fecha_entrega'])));
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="bg-white/60 rounded-lg p-3">
                                        <p class="text-xs text-amber-600 font-medium uppercase tracking-wide mb-1">
                                            <?php esc_html_e('Lugar de entrega', 'flavor-chat-ia'); ?>
                                        </p>
                                        <p class="text-amber-900 font-semibold text-sm">
                                            <?php echo esc_html($ciclo_info['lugar_entrega'] ?: $grupo_punto_recogida ?: '-'); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($ciclo_info['notas']): ?>
                                    <p class="text-amber-700 text-sm mb-4">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <?php echo esc_html($ciclo_info['notas']); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($es_miembro): ?>
                                    <a href="#productos" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl font-semibold transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <?php esc_html_e('Ver productos y hacer pedido', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php else: ?>
                                    <p class="text-amber-700 text-sm italic">
                                        <?php esc_html_e('Unete al grupo para poder hacer pedidos', 'flavor-chat-ia'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 3. PRODUCTORES ASOCIADOS -->
                <?php if (!empty($productores)): ?>
                    <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-6 h-6 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <?php esc_html_e('Nuestros productores', 'flavor-chat-ia'); ?>
                            </h2>
                            <a href="<?php echo esc_url(get_post_type_archive_link('gc_productor')); ?>" class="text-lime-600 hover:text-lime-700 text-sm font-medium flex items-center gap-1">
                                <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($productores as $productor): ?>
                                <a href="<?php echo esc_url($productor['url']); ?>" class="flex items-center gap-4 p-4 bg-gray-50 hover:bg-lime-50 rounded-xl transition-colors group">
                                    <?php if ($productor['imagen']): ?>
                                        <img src="<?php echo esc_url($productor['imagen']); ?>"
                                             alt="<?php echo esc_attr($productor['nombre']); ?>"
                                             class="w-16 h-16 rounded-full object-cover flex-shrink-0">
                                    <?php else: ?>
                                        <div class="w-16 h-16 rounded-full bg-lime-100 flex items-center justify-center text-2xl flex-shrink-0">
                                            👨‍🌾
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-semibold text-gray-800 group-hover:text-lime-700 transition-colors truncate">
                                                <?php echo esc_html($productor['nombre']); ?>
                                            </h3>
                                            <?php if ($productor['certificacion_eco']): ?>
                                                <span class="flex-shrink-0 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full"><?php esc_html_e('ECO', 'flavor-chat-ia'); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($productor['tiene_entrega']) && $productor['tiene_entrega']): ?>
                                                <span class="flex-shrink-0 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                    </svg>
                                                    <?php esc_html_e('Entrega', 'flavor-chat-ia'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-500 truncate">
                                            <?php echo esc_html($productor['descripcion']); ?>
                                        </p>
                                        <?php if ($productor['ubicacion']): ?>
                                            <p class="text-xs text-lime-600 mt-1 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                </svg>
                                                <?php echo esc_html($productor['ubicacion']); ?>
                                                <?php if (!empty($productor['radio_entrega_km']) && $productor['radio_entrega_km'] > 0): ?>
                                                    <span class="text-gray-400 mx-1">|</span>
                                                    <span class="text-blue-600">
                                                        <?php printf(esc_html__('Entrega hasta %d km', 'flavor-chat-ia'), intval($productor['radio_entrega_km'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                        <?php elseif (!empty($productor['radio_entrega_km']) && $productor['radio_entrega_km'] > 0): ?>
                                            <p class="text-xs text-blue-600 mt-1 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                                <?php printf(esc_html__('Entrega hasta %d km', 'flavor-chat-ia'), intval($productor['radio_entrega_km'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-300 group-hover:text-lime-500 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 4. PRODUCTOS DESTACADOS -->
                <section id="productos" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-6 h-6 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <?php esc_html_e('Productos destacados', 'flavor-chat-ia'); ?>
                        </h2>
                        <a href="<?php echo esc_url(get_post_type_archive_link('gc_producto')); ?>" class="text-lime-600 hover:text-lime-700 text-sm font-medium flex items-center gap-1">
                            <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    <?php if (empty($productos)): ?>
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-gray-500"><?php esc_html_e('No hay productos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach ($productos as $producto): ?>
                                <a href="<?php echo esc_url($producto['url']); ?>" class="group border border-gray-200 rounded-xl p-3 hover:border-lime-300 hover:shadow-md transition-all">
                                    <div class="aspect-square rounded-lg bg-gray-100 mb-3 overflow-hidden">
                                        <?php if ($producto['imagen']): ?>
                                            <img src="<?php echo esc_url($producto['imagen']); ?>"
                                                 alt="<?php echo esc_attr($producto['nombre']); ?>"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-4xl text-gray-300">
                                                🥬
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="font-medium text-gray-800 text-sm line-clamp-2 mb-1 group-hover:text-lime-700 transition-colors">
                                        <?php echo esc_html($producto['nombre']); ?>
                                    </h3>
                                    <?php if ($producto['productor']): ?>
                                        <p class="text-xs text-gray-500 mb-2 truncate">
                                            <?php echo esc_html($producto['productor']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-lime-600 font-bold">
                                        <?php echo number_format($producto['precio'], 2); ?><?php esc_html_e('&euro;/', 'flavor-chat-ia'); ?><?php echo esc_html($producto['unidad']); ?>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($es_miembro && $ciclo_info): ?>
                            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                                <button type="button"
                                        class="gc-abrir-catalogo inline-flex items-center gap-2 bg-lime-500 hover:bg-lime-600 text-white px-6 py-3 rounded-xl font-semibold transition-colors"
                                        data-grupo-id="<?php echo esc_attr($grupo_id); ?>"
                                        data-ciclo-id="<?php echo esc_attr($ciclo_info['id']); ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <?php esc_html_e('Abrir catalogo completo', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <!-- 6. INFORMACION ADICIONAL -->
                <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php esc_html_e('Sobre este grupo', 'flavor-chat-ia'); ?>
                    </h2>

                    <!-- Descripcion larga -->
                    <?php if ($grupo_descripcion_larga): ?>
                        <div class="prose prose-lime max-w-none mb-6">
                            <?php echo wp_kses_post($grupo_descripcion_larga); ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Horarios de recogida -->
                        <?php if ($grupo_horario_recogida || $grupo_punto_recogida): ?>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?php esc_html_e('Recogida de pedidos', 'flavor-chat-ia'); ?>
                                </h3>
                                <?php if ($grupo_punto_recogida): ?>
                                    <p class="text-gray-600 text-sm mb-2">
                                        <strong><?php esc_html_e('Lugar:', 'flavor-chat-ia'); ?></strong>
                                        <?php echo esc_html($grupo_punto_recogida); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($grupo_horario_recogida): ?>
                                    <p class="text-gray-600 text-sm">
                                        <strong><?php esc_html_e('Horario:', 'flavor-chat-ia'); ?></strong>
                                        <?php echo esc_html($grupo_horario_recogida); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Normas del grupo -->
                        <?php if (!empty($normas_array)): ?>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <?php esc_html_e('Normas del grupo', 'flavor-chat-ia'); ?>
                                </h3>
                                <ul class="text-sm text-gray-600 space-y-1.5">
                                    <?php foreach ($normas_array as $norma): ?>
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-lime-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo esc_html($norma); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

            </div>

            <!-- SIDEBAR -->
            <div class="space-y-6">

                <!-- Info rapida -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <?php esc_html_e('Informacion', 'flavor-chat-ia'); ?>
                    </h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <dt class="text-gray-500"><?php esc_html_e('Creado', 'flavor-chat-ia'); ?></dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html(get_the_date('M Y')); ?></dd>
                        </div>
                        <?php if ($coordinador_nombre): ?>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <dt class="text-gray-500"><?php esc_html_e('Coordinador', 'flavor-chat-ia'); ?></dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($coordinador_nombre); ?></dd>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <dt class="text-gray-500"><?php esc_html_e('Frecuencia pedidos', 'flavor-chat-ia'); ?></dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($grupo_dia_pedido); ?></dd>
                        </div>
                        <?php if ($grupo_punto_recogida): ?>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <dt class="text-gray-500"><?php esc_html_e('Punto recogida', 'flavor-chat-ia'); ?></dt>
                                <dd class="text-gray-800 font-medium text-right max-w-[150px] truncate" title="<?php echo esc_attr($grupo_punto_recogida); ?>">
                                    <?php echo esc_html($grupo_punto_recogida); ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center py-2">
                            <dt class="text-gray-500"><?php esc_html_e('Cuota mensual', 'flavor-chat-ia'); ?></dt>
                            <dd class="text-gray-800 font-semibold">
                                <?php echo $grupo_cuota > 0 ? number_format($grupo_cuota, 2) . '&euro;' : esc_html__('Gratuito', 'flavor-chat-ia'); ?>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Miembros -->
                <?php if (!empty($miembros_lista)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?php printf(esc_html__('Miembros (%d)', 'flavor-chat-ia'), intval($numero_miembros)); ?>
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($miembros_lista as $miembro): ?>
                                <div class="w-10 h-10 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 font-medium text-sm"
                                     title="<?php echo esc_attr($miembro->display_name); ?>">
                                    <?php echo esc_html(mb_strtoupper(mb_substr($miembro->display_name, 0, 1))); ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($numero_miembros > count($miembros_lista)): ?>
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs font-medium">
                                    +<?php echo intval($numero_miembros - count($miembros_lista)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Estadisticas del usuario (si es miembro) -->
                <?php if ($es_miembro && $estadisticas_usuario): ?>
                    <div class="bg-gradient-to-br from-lime-50 to-green-50 rounded-2xl border border-lime-200 p-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <?php esc_html_e('Tu actividad', 'flavor-chat-ia'); ?>
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 text-sm"><?php esc_html_e('Pedidos realizados', 'flavor-chat-ia'); ?></span>
                                <span class="font-bold text-lime-700"><?php echo intval($estadisticas_usuario['total_pedidos']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 text-sm"><?php esc_html_e('Total comprado', 'flavor-chat-ia'); ?></span>
                                <span class="font-bold text-lime-700"><?php echo number_format($estadisticas_usuario['total_gastado'], 2); ?><?php esc_html_e('&euro;', 'flavor-chat-ia'); ?></span>
                            </div>
                            <?php if ($estadisticas_usuario['fecha_alta']): ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm"><?php esc_html_e('Miembro desde', 'flavor-chat-ia'); ?></span>
                                    <span class="font-medium text-gray-800"><?php echo esc_html(date_i18n('M Y', strtotime($estadisticas_usuario['fecha_alta']))); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contacto -->
                <?php if ($grupo_email || $grupo_telefono): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <?php esc_html_e('Contacto', 'flavor-chat-ia'); ?>
                        </h3>
                        <div class="space-y-3">
                            <?php if ($grupo_email): ?>
                                <a href="mailto:<?php echo esc_attr($grupo_email); ?>" class="flex items-center gap-3 text-gray-600 hover:text-lime-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm truncate"><?php echo esc_html($grupo_email); ?></span>
                                </a>
                            <?php endif; ?>
                            <?php if ($grupo_telefono): ?>
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $grupo_telefono)); ?>" class="flex items-center gap-3 text-gray-600 hover:text-lime-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span class="text-sm"><?php echo esc_html($grupo_telefono); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 5. CTA PARA UNIRSE -->
                <div id="unirse" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <?php if (!is_user_logged_in()): ?>
                        <!-- Usuario no logueado -->
                        <div class="text-center">
                            <div class="w-16 h-16 bg-lime-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-800 mb-2"><?php esc_html_e('Unete a este grupo', 'flavor-chat-ia'); ?></h3>
                            <p class="text-gray-600 text-sm mb-4">
                                <?php esc_html_e('Registrate o inicia sesion para unirte y empezar a hacer pedidos de productos locales.', 'flavor-chat-ia'); ?>
                            </p>
                            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="block w-full bg-lime-500 hover:bg-lime-600 text-white py-3 px-4 rounded-xl font-semibold transition-colors text-center mb-2">
                                <?php esc_html_e('Iniciar sesion', 'flavor-chat-ia'); ?>
                            </a>
                            <a href="<?php echo esc_url(wp_registration_url()); ?>" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-xl font-semibold transition-colors text-center">
                                <?php esc_html_e('Crear cuenta', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php elseif ($es_miembro): ?>
                        <!-- Ya es miembro -->
                        <div class="text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-800 mb-2"><?php esc_html_e('Ya eres miembro', 'flavor-chat-ia'); ?></h3>
                            <p class="text-gray-600 text-sm mb-4">
                                <?php esc_html_e('Tienes acceso completo a los pedidos y productos del grupo.', 'flavor-chat-ia'); ?>
                            </p>
                            <?php if ($ciclo_info): ?>
                                <a href="#productos" class="block w-full bg-lime-500 hover:bg-lime-600 text-white py-3 px-4 rounded-xl font-semibold transition-colors text-center">
                                    <?php esc_html_e('Hacer un pedido', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($estado_membresia === 'pendiente'): ?>
                        <!-- Solicitud pendiente -->
                        <div class="text-center">
                            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-800 mb-2"><?php esc_html_e('Solicitud en revision', 'flavor-chat-ia'); ?></h3>
                            <p class="text-gray-600 text-sm">
                                <?php esc_html_e('Tu solicitud de ingreso esta siendo revisada por los coordinadores del grupo. Te notificaremos cuando sea aprobada.', 'flavor-chat-ia'); ?>
                            </p>
                        </div>
                    <?php elseif ($grupo_estado === 'cerrado'): ?>
                        <!-- Grupo cerrado -->
                        <div class="text-center">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-800 mb-2"><?php esc_html_e('Grupo cerrado', 'flavor-chat-ia'); ?></h3>
                            <p class="text-gray-600 text-sm">
                                <?php esc_html_e('Este grupo no esta aceptando nuevos miembros en este momento.', 'flavor-chat-ia'); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Formulario para solicitar unirse -->
                        <div class="text-center">
                            <div class="w-16 h-16 bg-lime-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-800 mb-2"><?php esc_html_e('Solicitar unirse', 'flavor-chat-ia'); ?></h3>
                            <p class="text-gray-600 text-sm mb-4">
                                <?php esc_html_e('Envia una solicitud para unirte a este grupo de consumo.', 'flavor-chat-ia'); ?>
                            </p>

                            <form id="gc-form-unirse" class="space-y-4 text-left">
                                <?php wp_nonce_field('gc_frontend_nonce', 'nonce'); ?>
                                <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo_id); ?>">

                                <div>
                                    <label for="gc_mensaje" class="block text-sm font-medium text-gray-700 mb-1">
                                        <?php esc_html_e('Mensaje (opcional)', 'flavor-chat-ia'); ?>
                                    </label>
                                    <textarea id="gc_mensaje" name="mensaje" rows="3"
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                              placeholder="<?php esc_attr_e('Cuentanos por que quieres unirte...', 'flavor-chat-ia'); ?>"></textarea>
                                </div>

                                <button type="submit" class="w-full bg-gradient-to-r from-lime-500 to-green-500 hover:from-lime-600 hover:to-green-600 text-white py-3 px-4 rounded-xl font-semibold transition-all shadow-lg flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <?php esc_html_e('Enviar solicitud', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>

</main>

<?php
// Agregar JavaScript para el formulario de union
add_action('wp_footer', function() use ($grupo_id) {
?>
<script>
(function() {
    const formUnirse = document.getElementById('gc-form-unirse');
    if (formUnirse) {
        formUnirse.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'gc_solicitar_union');

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <?php esc_html_e('Enviando...\';

            fetch(\'', 'flavor-chat-ia'); ?><?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    formUnirse.innerHTML = '<div class="text-center py-4"><svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><p class="text-green-700 font-medium"><?php esc_html_e('\' + (data.data.message || \'Solicitud enviada correctamente\') + \'', 'flavor-chat-ia'); ?></p></div>';
                } else {
                    alert(data.data.message || 'Error al enviar la solicitud');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexion. Intentalo de nuevo.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
})();
</script>
<?php
});

get_footer();
