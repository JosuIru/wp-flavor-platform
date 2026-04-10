<?php
/**
 * Template: Episodios Recientes - Lista de últimos episodios con reproductor
 *
 * Muestra los episodios más recientes con reproductor de audio integrado
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Podcast
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_seccion = $args['titulo_seccion'] ?? 'Últimos Episodios';
$descripcion_seccion = $args['descripcion_seccion'] ?? 'Escucha los episodios más recientes de nuestros podcasts comunitarios';
$episodios = $args['episodios'] ?? [];
$mostrar_limite = $args['limite'] ?? 6;
$tipo_visualizacion = $args['tipo'] ?? 'lista'; // 'lista' o 'destacado'
?>

<section class="py-12 sm:py-16 lg:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado de sección -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 rounded-full text-purple-700 text-sm font-semibold mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Recién Publicado
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($descripcion_seccion); ?>
            </p>
        </div>

        <?php if (!empty($episodios)): ?>
            <div class="space-y-6">
                <?php
                $contador_episodios = 0;
                foreach ($episodios as $episodio):
                    if ($contador_episodios >= $mostrar_limite) break;
                    $contador_episodios++;

                    $titulo_episodio = $episodio['titulo'] ?? 'Episodio sin título';
                    $descripcion_episodio = $episodio['descripcion'] ?? '';
                    $serie_nombre = $episodio['serie'] ?? 'Sin serie';
                    $numero_episodio = $episodio['numero'] ?? '';
                    $duracion = $episodio['duracion'] ?? '00:00';
                    $fecha_publicacion = $episodio['fecha'] ?? '';
                    $url_audio = $episodio['audio_url'] ?? '';
                    $imagen_miniatura = $episodio['imagen'] ?? '';
                    $autor_nombre = $episodio['autor'] ?? 'Desconocido';
                    $reproducciones = $episodio['reproducciones'] ?? 0;
                ?>
                    <article class="grupo-episodio bg-gradient-to-r from-gray-50 to-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                        <div class="grid md:grid-cols-12 gap-0">
                            <!-- Imagen del episodio -->
                            <div class="md:col-span-3">
                                <div class="relative aspect-square md:aspect-auto md:h-full overflow-hidden bg-gradient-to-br from-purple-400 to-pink-500">
                                    <?php if ($imagen_miniatura): ?>
                                        <img src="<?php echo esc_url($imagen_miniatura); ?>"
                                             alt="<?php echo esc_attr($titulo_episodio); ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Badge de nuevo episodio -->
                                    <?php if ($contador_episodios <= 3): ?>
                                        <div class="absolute top-3 left-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-rose-500 text-white shadow-lg">
                                                NUEVO
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Contenido del episodio -->
                            <div class="md:col-span-9 p-6 flex flex-col justify-between">
                                <div>
                                    <!-- Metadatos superiores -->
                                    <div class="flex flex-wrap items-center gap-3 mb-3">
                                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-purple-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            <?php echo esc_html($serie_nombre); ?>
                                        </span>

                                        <?php if ($numero_episodio): ?>
                                            <span class="text-sm text-gray-500">
                                                Episodio <?php echo esc_html($numero_episodio); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($fecha_publicacion): ?>
                                            <span class="text-sm text-gray-500">
                                                • <?php echo esc_html(date('d/m/Y', strtotime($fecha_publicacion))); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Título del episodio -->
                                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 hover:text-purple-600 transition-colors">
                                        <?php echo esc_html($titulo_episodio); ?>
                                    </h3>

                                    <!-- Descripción -->
                                    <p class="text-gray-600 mb-4 line-clamp-2">
                                        <?php echo esc_html($descripcion_episodio); ?>
                                    </p>
                                </div>

                                <!-- Reproductor de audio personalizado -->
                                <?php if ($url_audio): ?>
                                    <div class="mt-4">
                                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-100">
                                            <audio class="w-full reproductor-episodio" preload="metadata" data-episodio-id="<?php echo esc_attr($episodio['id'] ?? ''); ?>">
                                                <source src="<?php echo esc_url($url_audio); ?>" type="audio/mpeg">
                                                Tu navegador no soporta el elemento de audio.
                                            </audio>

                                            <!-- Controles personalizados -->
                                            <div class="flex items-center gap-4 mt-3">
                                                <!-- Botón play/pause -->
                                                <button class="btn-reproducir flex-shrink-0 w-10 h-10 bg-purple-600 hover:bg-purple-700 text-white rounded-full flex items-center justify-center transition-colors shadow-md">
                                                    <svg class="w-5 h-5 icono-play" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z"/>
                                                    </svg>
                                                    <svg class="w-5 h-5 icono-pause hidden" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                                                    </svg>
                                                </button>

                                                <!-- Barra de progreso -->
                                                <div class="flex-1">
                                                    <div class="bg-purple-200 rounded-full h-2 cursor-pointer barra-progreso">
                                                        <div class="bg-purple-600 h-2 rounded-full progreso-actual" style="width: 0%"></div>
                                                    </div>
                                                </div>

                                                <!-- Tiempo y duración -->
                                                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                                    <span class="tiempo-actual">00:00</span>
                                                    <span>/</span>
                                                    <span class="tiempo-total"><?php echo esc_html($duracion); ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Información adicional -->
                                        <div class="flex items-center justify-between mt-3 text-sm text-gray-500">
                                            <div class="flex items-center gap-4">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <?php echo esc_html($autor_nombre); ?>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    <?php echo number_format($reproducciones, 0, ',', '.'); ?> reproducciones
                                                </span>
                                            </div>

                                            <!-- Acciones -->
                                            <div class="flex items-center gap-2">
                                                <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Compartir">
                                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                                    </svg>
                                                </button>
                                                <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Descargar">
                                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Botón ver más -->
            <div class="text-center mt-10">
                <a href="#todos-episodios"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Ver Todos los Episodios
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="text-center py-16 bg-gray-50 rounded-2xl">
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay episodios disponibles aún</h3>
                <p class="text-gray-600">Los nuevos episodios aparecerán aquí cuando sean publicados</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Script para controlar los reproductores de audio personalizados
document.addEventListener('DOMContentLoaded', function() {
    const reproductores = document.querySelectorAll('.reproductor-episodio');

    reproductores.forEach(function(audio) {
        const contenedor = audio.closest('.grupo-episodio');
        const btnReproducir = contenedor.querySelector('.btn-reproducir');
        const iconoPlay = btnReproducir.querySelector('.icono-play');
        const iconoPause = btnReproducir.querySelector('.icono-pause');
        const barraProgreso = contenedor.querySelector('.barra-progreso');
        const progresoActual = contenedor.querySelector('.progreso-actual');
        const tiempoActual = contenedor.querySelector('.tiempo-actual');

        // Play/Pause
        btnReproducir.addEventListener('click', function() {
            if (audio.paused) {
                audio.play();
                iconoPlay.classList.add('hidden');
                iconoPause.classList.remove('hidden');
            } else {
                audio.pause();
                iconoPlay.classList.remove('hidden');
                iconoPause.classList.add('hidden');
            }
        });

        // Actualizar progreso
        audio.addEventListener('timeupdate', function() {
            const porcentaje = (audio.currentTime / audio.duration) * 100;
            progresoActual.style.width = porcentaje + '%';

            const minutos = Math.floor(audio.currentTime / 60);
            const segundos = Math.floor(audio.currentTime % 60);
            tiempoActual.textContent = `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
        });

        // Clic en barra de progreso
        barraProgreso.addEventListener('click', function(e) {
            const rect = barraProgreso.getBoundingClientRect();
            const porcentaje = (e.clientX - rect.left) / rect.width;
            audio.currentTime = porcentaje * audio.duration;
        });
    });
});
</script>
