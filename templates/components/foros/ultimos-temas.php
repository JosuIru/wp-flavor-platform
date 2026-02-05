<?php
/**
 * Template: Ultimos Temas de los Foros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? __('Ultimos Temas', 'flavor-chat-ia');
$limite_temas = intval($limite ?? 10);
$mostrar_nombre_foro = isset($mostrar_foro) ? (bool) $mostrar_foro : true;

// Datos de ejemplo para previsualizacion cuando no hay datos reales
$temas_recientes = [
    [
        'titulo' => 'Propuesta para mejorar la zona verde del parque central',
        'autor_nombre' => 'Maria Garcia',
        'autor_inicial' => 'M',
        'foro_nombre' => 'Propuestas e Ideas',
        'foro_color' => '#f59e0b',
        'respuestas' => 23,
        'vistas' => 156,
        'fecha' => __('Hace 15 min', 'flavor-chat-ia'),
        'es_fijado' => true,
        'es_destacado' => false,
        'tiene_solucion' => false,
    ],
    [
        'titulo' => 'Problema con el horario de recogida de basuras',
        'autor_nombre' => 'Carlos Ruiz',
        'autor_inicial' => 'C',
        'foro_nombre' => 'General',
        'foro_color' => '#667eea',
        'respuestas' => 8,
        'vistas' => 92,
        'fecha' => __('Hace 1 hora', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => true,
        'tiene_solucion' => true,
    ],
    [
        'titulo' => 'Organizamos una quedada para limpiar el rio este sabado',
        'autor_nombre' => 'Ana Lopez',
        'autor_inicial' => 'A',
        'foro_nombre' => 'Eventos y Actividades',
        'foro_color' => '#ef4444',
        'respuestas' => 15,
        'vistas' => 234,
        'fecha' => __('Hace 2 horas', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => false,
        'tiene_solucion' => false,
    ],
    [
        'titulo' => 'Como separar correctamente los residuos organicos',
        'autor_nombre' => 'Pedro Martinez',
        'autor_inicial' => 'P',
        'foro_nombre' => 'Medio Ambiente',
        'foro_color' => '#059669',
        'respuestas' => 12,
        'vistas' => 178,
        'fecha' => __('Hace 3 horas', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => false,
        'tiene_solucion' => true,
    ],
    [
        'titulo' => 'Recomendaciones de restaurantes en la zona norte',
        'autor_nombre' => 'Laura Sanchez',
        'autor_inicial' => 'L',
        'foro_nombre' => 'Cultura y Ocio',
        'foro_color' => '#8b5cf6',
        'respuestas' => 31,
        'vistas' => 412,
        'fecha' => __('Hace 5 horas', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => false,
        'tiene_solucion' => false,
    ],
    [
        'titulo' => 'Nuevo servicio de bicicletas compartidas: dudas frecuentes',
        'autor_nombre' => 'Sofia Torres',
        'autor_inicial' => 'S',
        'foro_nombre' => 'Ayuda y Soporte',
        'foro_color' => '#10b981',
        'respuestas' => 7,
        'vistas' => 65,
        'fecha' => __('Hace 8 horas', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => false,
        'tiene_solucion' => false,
    ],
    [
        'titulo' => 'Taller de huerto urbano: apuntaos que quedan plazas',
        'autor_nombre' => 'Diego Fernandez',
        'autor_inicial' => 'D',
        'foro_nombre' => 'Eventos y Actividades',
        'foro_color' => '#ef4444',
        'respuestas' => 19,
        'vistas' => 287,
        'fecha' => __('Hace 1 dia', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => false,
        'tiene_solucion' => false,
    ],
    [
        'titulo' => 'Encuesta: que tipo de actividades os gustaria tener este verano',
        'autor_nombre' => 'Elena Martin',
        'autor_inicial' => 'E',
        'foro_nombre' => 'Propuestas e Ideas',
        'foro_color' => '#f59e0b',
        'respuestas' => 42,
        'vistas' => 523,
        'fecha' => __('Hace 1 dia', 'flavor-chat-ia'),
        'es_fijado' => false,
        'es_destacado' => false,
        'tiene_solucion' => false,
    ],
];

// Limitar segun configuracion
$temas_a_mostrar = array_slice($temas_recientes, 0, $limite_temas);
?>

<section class="flavor-component flavor-section py-16 bg-white">
    <div class="flavor-container">
        <!-- Cabecera de seccion -->
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo esc_html__('Reciente', 'flavor-chat-ia'); ?>
                </span>
                <h2 class="text-4xl md:text-5xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html($titulo_seccion); ?></h2>
                <p class="text-xl" style="color: var(--flavor-text-secondary, #666666);"><?php echo esc_html__('Las conversaciones mas recientes de la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="/foros/" class="mt-4 md:mt-0 font-semibold flex items-center gap-1 transition-colors" style="color: var(--flavor-primary, #667eea);">
                <?php echo esc_html__('Ver todos', 'flavor-chat-ia'); ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Lista de temas -->
        <div class="space-y-3">
            <?php foreach ($temas_a_mostrar as $indice_tema => $tema): ?>
                <article class="group bg-gray-50 hover:bg-white rounded-2xl p-4 md:p-6 transition-all duration-300 border border-gray-100 hover:border-gray-200 hover:shadow-md">
                    <div class="flex items-center gap-4 md:gap-6">
                        <!-- Avatar del autor -->
                        <div class="relative flex-shrink-0">
                            <div class="w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center text-white text-lg font-bold" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                <?php echo esc_html($tema['autor_inicial']); ?>
                            </div>
                            <?php if ($tema['es_fijado']): ?>
                                <div class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-amber-400 flex items-center justify-center" title="<?php echo esc_attr__('Fijado', 'flavor-chat-ia'); ?>">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Contenido del tema -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <?php if ($mostrar_nombre_foro): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold text-white" style="background: <?php echo esc_attr($tema['foro_color']); ?>;">
                                        <?php echo esc_html($tema['foro_nombre']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($tema['es_destacado']): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                        <?php echo esc_html__('Destacado', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($tema['tiene_solucion']): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <?php echo esc_html__('Resuelto', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-xs" style="color: var(--flavor-text-secondary, #666666);"><?php echo esc_html($tema['fecha']); ?></span>
                            </div>

                            <h3 class="text-lg font-bold transition-colors truncate" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <a href="/foros/tema/?id=<?php echo esc_attr($indice_tema); ?>" class="hover:underline" style="color: inherit;">
                                    <?php echo esc_html($tema['titulo']); ?>
                                </a>
                            </h3>

                            <div class="flex items-center gap-4 mt-2 text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                <span class="font-medium"><?php echo esc_html($tema['autor_nombre']); ?></span>
                                <span style="color: #d1d5db;">|</span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <?php echo esc_html($tema['respuestas']); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <?php echo esc_html($tema['vistas']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Indicador de actividad -->
                        <div class="hidden md:flex flex-col items-center gap-1 flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg" style="background: var(--flavor-primary, #667eea)10; color: var(--flavor-primary, #667eea);">
                                <?php echo esc_html($tema['respuestas']); ?>
                            </div>
                            <span class="text-xs" style="color: var(--flavor-text-secondary, #666666);"><?php echo esc_html__('resp.', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Boton para ver mas -->
        <div class="text-center mt-10">
            <a href="/foros/" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                <span><?php echo esc_html__('Ver Todos los Temas', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
