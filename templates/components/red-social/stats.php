<?php
/**
 * Template: Estadísticas de la Comunidad
 *
 * @package FlavorPlatform
 * @var array $args Parámetros opcionales del template
 */

if (!defined('ABSPATH')) exit;

// Parámetros opcionales
$titulo = $args['titulo'] ?? 'Estadísticas de la Comunidad';
$periodo = $args['periodo'] ?? 'Este mes';
$mostrar_graficas = $args['mostrar_graficas'] ?? true;

// Datos de estadísticas principales
$estadisticas_principales = [
    [
        'titulo' => 'Miembros Activos',
        'valor' => '1,234',
        'cambio' => '+12%',
        'icono' => 'usuarios',
        'color' => 'blue',
        'descripcion' => 'Usuarios activos en el último mes',
    ],
    [
        'titulo' => 'Publicaciones',
        'valor' => '2,847',
        'cambio' => '+28%',
        'icono' => 'publicaciones',
        'color' => 'green',
        'descripcion' => 'Contenido generado por la comunidad',
    ],
    [
        'titulo' => 'Interacciones',
        'valor' => '15,392',
        'cambio' => '+45%',
        'icono' => 'interacciones',
        'color' => 'purple',
        'descripcion' => 'Likes, comentarios y comparticiones',
    ],
    [
        'titulo' => 'Nuevos Miembros',
        'valor' => '156',
        'cambio' => '+8%',
        'icono' => 'nuevos',
        'color' => 'orange',
        'descripcion' => 'Personas que se unieron este mes',
    ],
];

// Datos de actividad por día
$actividad_diaria = [
    ['dia' => 'Lunes', 'publicaciones' => 180, 'interacciones' => 950],
    ['dia' => 'Martes', 'publicaciones' => 215, 'interacciones' => 1200],
    ['dia' => 'Miércoles', 'publicaciones' => 190, 'interacciones' => 1050],
    ['dia' => 'Jueves', 'publicaciones' => 240, 'interacciones' => 1400],
    ['dia' => 'Viernes', 'publicaciones' => 310, 'interacciones' => 1800],
    ['dia' => 'Sábado', 'publicaciones' => 280, 'interacciones' => 1600],
    ['dia' => 'Domingo', 'publicaciones' => 225, 'interacciones' => 1350],
];

// Tipos de contenido más popular
$contenido_popular = [
    ['tipo' => 'Eventos Comunitarios', 'publicaciones' => 342, 'porcentaje' => 35],
    ['tipo' => 'Iniciativas Locales', 'publicaciones' => 268, 'porcentaje' => 27],
    ['tipo' => 'Consejos y Recomendaciones', 'publicaciones' => 214, 'porcentaje' => 22],
    ['tipo' => 'Búsquedas y Ayuda', 'publicaciones' => 152, 'porcentaje' => 16],
];

// Miembros más activos
$miembros_destacados = [
    ['nombre' => 'Carolina Vega', 'avatar' => 'https://i.pravatar.cc/150?img=21', 'publicaciones' => 287, 'rango' => 'Moderador'],
    ['nombre' => 'Antonio García', 'avatar' => 'https://i.pravatar.cc/150?img=56', 'publicaciones' => 245, 'rango' => 'Contribuidor'],
    ['nombre' => 'Isabel Moreno', 'avatar' => 'https://i.pravatar.cc/150?img=44', 'publicaciones' => 198, 'rango' => 'Contribuidor'],
    ['nombre' => 'Miguel López', 'avatar' => 'https://i.pravatar.cc/150?img=72', 'publicaciones' => 176, 'rango' => 'Miembro'],
    ['nombre' => 'Elena Ruiz', 'avatar' => 'https://i.pravatar.cc/150?img=38', 'publicaciones' => 164, 'rango' => 'Miembro'],
];

// Máximo valor para escalar gráficas
$max_actividad = max(array_column($actividad_diaria, 'interacciones'));
$max_contenido = max(array_column($contenido_popular, 'publicaciones'));
?>

<section class="flavor-component py-16 bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <p class="text-lg text-gray-600">
                    <?php echo esc_html($periodo); ?>
                </p>
            </div>

            <!-- Tarjetas de Estadísticas Principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <?php foreach ($estadisticas_principales as $stat): ?>
                    <div class="flavor-stat-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, var(--color-<?php echo esc_attr($stat['color']); ?>-100), var(--color-<?php echo esc_attr($stat['color']); ?>-50));">
                                <?php if ($stat['icono'] === 'usuarios'): ?>
                                    <svg class="w-6 h-6" style="color: var(--color-<?php echo esc_attr($stat['color']); ?>-600);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                <?php elseif ($stat['icono'] === 'publicaciones'): ?>
                                    <svg class="w-6 h-6" style="color: var(--color-<?php echo esc_attr($stat['color']); ?>-600);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2m2 2a2 2 0 002-2m-2 2v-6a2 2 0 012-2h.344c.603 0 1.188.195 1.688.564m0 0a2 2 0 002-2V6a2 2 0 00-2-2h-1.344a2 2 0 00-2 2v10"/>
                                    </svg>
                                <?php elseif ($stat['icono'] === 'interacciones'): ?>
                                    <svg class="w-6 h-6" style="color: var(--color-<?php echo esc_attr($stat['color']); ?>-600);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-6 h-6" style="color: var(--color-<?php echo esc_attr($stat['color']); ?>-600);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full">
                                <?php echo esc_html($stat['cambio']); ?>
                            </span>
                        </div>
                        <h3 class="text-sm text-gray-600 font-medium mb-1">
                            <?php echo esc_html($stat['titulo']); ?>
                        </h3>
                        <p class="text-3xl font-bold text-gray-900 mb-2">
                            <?php echo esc_html($stat['valor']); ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            <?php echo esc_html($stat['descripcion']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Gráficas de Actividad -->
            <?php if ($mostrar_graficas): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                    <!-- Actividad Diaria -->
                    <div class="flavor-activity-chart bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html__('Actividad por Día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                <?php echo esc_html__('Publicaciones e interacciones diarias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                        <div class="space-y-4">
                            <?php foreach ($actividad_diaria as $dia_info): ?>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">
                                            <?php echo esc_html($dia_info['dia']); ?>
                                        </span>
                                        <span class="text-sm text-gray-600">
                                            <?php echo esc_html($dia_info['interacciones']); ?>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300" style="width: <?php echo ($dia_info['interacciones'] / $max_actividad) * 100; ?>%; background: linear-gradient(90deg, #0ea5e9 0%, #0284c7 100%);"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Contenido Popular -->
                    <div class="flavor-content-chart bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html__('Tipos de Contenido Popular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                <?php echo esc_html__('Distribución por categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                        <div class="space-y-4">
                            <?php foreach ($contenido_popular as $contenido): ?>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">
                                            <?php echo esc_html($contenido['tipo']); ?>
                                        </span>
                                        <span class="text-sm font-bold text-gray-900">
                                            <?php echo esc_html($contenido['porcentaje']); ?>%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300" style="width: <?php echo $contenido['porcentaje']; ?>%; background: linear-gradient(90deg, #ec4899 0%, #f43f5e 100%);"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Miembros Destacados -->
            <div class="flavor-top-members bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Miembros Más Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <p class="text-gray-600">
                        <?php echo esc_html__('Nuestros colaboradores destacados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <?php foreach ($miembros_destacados as $indice => $miembro): ?>
                        <div class="flavor-member-card text-center p-6 rounded-xl hover:shadow-lg transition-all duration-300 border border-gray-100 hover:border-blue-200">
                            <div class="mb-4">
                                <img src="<?php echo esc_url($miembro['avatar']); ?>" alt="<?php echo esc_attr($miembro['nombre']); ?>" class="w-16 h-16 rounded-full object-cover mx-auto shadow-md">
                            </div>
                            <h4 class="font-bold text-gray-900 mb-1">
                                <?php echo esc_html($miembro['nombre']); ?>
                            </h4>
                            <p class="text-xs text-gray-500 mb-3 px-2 py-1 inline-block rounded-full bg-blue-50 text-blue-600">
                                <?php echo esc_html($miembro['rango']); ?>
                            </p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo esc_html($miembro['publicaciones']); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo esc_html__('publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Insights y Recomendaciones -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="flavor-insight-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">
                                <?php echo esc_html__('Pico de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>
                            <p class="text-sm text-gray-600">
                                <?php echo esc_html__('Viernes es el día más activo con 310 publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flavor-insight-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">
                                <?php echo esc_html__('Contenido Estrella', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>
                            <p class="text-sm text-gray-600">
                                <?php echo esc_html__('Eventos comunitarios generan 35% del contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flavor-insight-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">
                                <?php echo esc_html__('Crecimiento Consistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>
                            <p class="text-sm text-gray-600">
                                <?php echo esc_html__('Aumento de 45% en interacciones mes a mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
