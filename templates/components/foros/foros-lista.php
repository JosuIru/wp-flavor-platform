<?php
/**
 * Template: Lista de Categorias de Foros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? __('Categorias de Foros', 'flavor-chat-ia');
$numero_columnas = intval($columnas ?? 2);
$mostrar_estadisticas_foro = isset($mostrar_estadisticas) ? (bool) $mostrar_estadisticas : true;

// Datos de ejemplo para previsualizacion cuando no hay datos reales
$categorias_foro = [
    [
        'nombre' => 'General',
        'descripcion' => 'Discusiones generales sobre la comunidad, propuestas y temas abiertos para todos los miembros.',
        'icono' => 'chat',
        'total_hilos' => 45,
        'total_respuestas' => 312,
        'color' => '#667eea',
    ],
    [
        'nombre' => 'Ayuda y Soporte',
        'descripcion' => 'Resuelve dudas y obtiene ayuda de otros miembros de la comunidad.',
        'icono' => 'help',
        'total_hilos' => 28,
        'total_respuestas' => 156,
        'color' => '#10b981',
    ],
    [
        'nombre' => 'Propuestas e Ideas',
        'descripcion' => 'Comparte tus ideas y propuestas para mejorar la comunidad.',
        'icono' => 'lightbulb',
        'total_hilos' => 19,
        'total_respuestas' => 87,
        'color' => '#f59e0b',
    ],
    [
        'nombre' => 'Eventos y Actividades',
        'descripcion' => 'Organiza y comenta sobre eventos, talleres y actividades comunitarias.',
        'icono' => 'calendar',
        'total_hilos' => 34,
        'total_respuestas' => 198,
        'color' => '#ef4444',
    ],
    [
        'nombre' => 'Medio Ambiente',
        'descripcion' => 'Temas sobre sostenibilidad, reciclaje y cuidado del entorno.',
        'icono' => 'leaf',
        'total_hilos' => 22,
        'total_respuestas' => 134,
        'color' => '#059669',
    ],
    [
        'nombre' => 'Cultura y Ocio',
        'descripcion' => 'Recomendaciones culturales, deportes, aficiones y tiempo libre.',
        'icono' => 'star',
        'total_hilos' => 31,
        'total_respuestas' => 201,
        'color' => '#8b5cf6',
    ],
];

$clase_columnas_grid = $numero_columnas === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2';

/**
 * Obtiene el SVG del icono segun el tipo
 */
function flavor_foros_obtener_icono_svg($tipo_icono) {
    $iconos_disponibles = [
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m0 0V6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H9l-4 4V10z"/>',
        'help' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'lightbulb' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
        'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'leaf' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
        'star' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
        'forum' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m0 0V6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H9l-4 4V10z"/>',
    ];

    return $iconos_disponibles[$tipo_icono] ?? $iconos_disponibles['forum'];
}
?>

<section id="foros-categorias" class="flavor-component flavor-section py-16" style="background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);">
    <div class="flavor-container">
        <!-- Cabecera de seccion -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1"/>
                </svg>
                <?php echo esc_html__('Foros', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="text-xl" style="color: var(--flavor-text-secondary, #666666);"><?php echo esc_html__('Elige una categoria y participa en las conversaciones', 'flavor-chat-ia'); ?></p>
        </div>

        <!-- Grid de foros -->
        <div class="grid grid-cols-1 md:grid-cols-2 <?php echo esc_attr($clase_columnas_grid); ?> gap-6">
            <?php foreach ($categorias_foro as $indice_foro => $foro): ?>
                <article class="group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100">
                    <!-- Borde superior de color -->
                    <div class="absolute top-0 left-0 right-0 h-1 transition-all duration-300 group-hover:h-1.5" style="background: <?php echo esc_attr($foro['color']); ?>;"></div>

                    <div class="p-6 md:p-8">
                        <div class="flex items-start gap-4">
                            <!-- Icono -->
                            <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110" style="background: <?php echo esc_attr($foro['color']); ?>15;">
                                <svg class="w-7 h-7" fill="none" stroke="<?php echo esc_attr($foro['color']); ?>" viewBox="0 0 24 24">
                                    <?php echo flavor_foros_obtener_icono_svg($foro['icono']); ?>
                                </svg>
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-bold mb-2 transition-colors duration-300" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <a href="/foros/?categoria=<?php echo esc_attr(sanitize_title($foro['nombre'])); ?>" class="hover:underline" style="color: inherit;">
                                        <?php echo esc_html($foro['nombre']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm leading-relaxed mb-4" style="color: var(--flavor-text-secondary, #666666);">
                                    <?php echo esc_html($foro['descripcion']); ?>
                                </p>

                                <?php if ($mostrar_estadisticas_foro): ?>
                                    <div class="flex items-center gap-6 pt-4 border-t border-gray-100">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-text-secondary, #666666);">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                            </svg>
                                            <span class="text-sm font-medium" style="color: var(--flavor-text-secondary, #666666);">
                                                <?php echo esc_html($foro['total_hilos']); ?> <?php echo esc_html__('hilos', 'flavor-chat-ia'); ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-text-secondary, #666666);">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                            <span class="text-sm font-medium" style="color: var(--flavor-text-secondary, #666666);">
                                                <?php echo esc_html($foro['total_respuestas']); ?> <?php echo esc_html__('respuestas', 'flavor-chat-ia'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Flecha de acceso -->
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-text-secondary, #666666);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- CTA para crear nuevo hilo -->
        <div class="text-center mt-12">
            <div class="inline-flex flex-col sm:flex-row items-center gap-4 p-6 rounded-2xl" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea)08, var(--flavor-secondary, #764ba2)08); border: 1px dashed var(--flavor-primary, #667eea)40;">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-primary, #667eea);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-lg font-medium" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('No encuentras lo que buscas?', 'flavor-chat-ia'); ?></span>
                </div>
                <a href="/foros/nuevo-tema/" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-base font-semibold text-white transition-all duration-300 hover:scale-105 hover:shadow-lg" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                    <?php echo esc_html__('Crea un nuevo hilo', 'flavor-chat-ia'); ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>
