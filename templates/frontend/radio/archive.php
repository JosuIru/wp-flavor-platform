<?php
/**
 * Frontend: Archive de Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$programas = $programas ?? [];
$total_programas = $total_programas ?? 0;
$en_directo = $en_directo ?? [];
?>

<div class="flavor-archive radio">
    <!-- Header con reproductor en directo -->
    <div class="bg-gradient-to-r from-red-600 to-rose-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Radio Comunitaria</h1>
                    <p class="text-white/90 text-lg">La voz de tu barrio, 24 horas</p>
                </div>

                <!-- Mini reproductor -->
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 flex items-center gap-4">
                    <button id="play-radio" class="w-14 h-14 rounded-full bg-white text-red-600 flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 ml-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    <div>
                        <span class="flex items-center gap-2 text-white text-sm">
                            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                            EN DIRECTO
                        </span>
                        <p class="text-white font-bold"><?php echo esc_html($en_directo['programa'] ?? 'Radio Barrio'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Programacion de hoy -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Programacion de Hoy</h2>
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <?php
                $programacion_hoy = [
                    ['hora' => '07:00', 'programa' => 'Buenos Dias Barrio', 'locutor' => 'Maria Garcia', 'activo' => false],
                    ['hora' => '10:00', 'programa' => 'Magazine Matinal', 'locutor' => 'Pedro Lopez', 'activo' => true],
                    ['hora' => '13:00', 'programa' => 'Informativos Locales', 'locutor' => 'Redaccion', 'activo' => false],
                    ['hora' => '15:00', 'programa' => 'Musica Sin Fronteras', 'locutor' => 'DJ Vecino', 'activo' => false],
                    ['hora' => '18:00', 'programa' => 'La Tertulia', 'locutor' => 'Ana Martinez', 'activo' => false],
                    ['hora' => '21:00', 'programa' => 'Noches de Blues', 'locutor' => 'Carlos Ruiz', 'activo' => false],
                ];
                foreach ($programacion_hoy as $slot):
                ?>
                    <div class="flex items-center gap-4 p-4 border-b border-gray-100 last:border-0 <?php echo $slot['activo'] ? 'bg-red-50' : ''; ?>">
                        <span class="w-16 text-sm font-bold <?php echo $slot['activo'] ? 'text-red-600' : 'text-gray-500'; ?>">
                            <?php echo esc_html($slot['hora']); ?>
                        </span>
                        <div class="flex-1">
                            <p class="font-bold text-gray-900"><?php echo esc_html($slot['programa']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($slot['locutor']); ?></p>
                        </div>
                        <?php if ($slot['activo']): ?>
                            <span class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                AHORA
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Todos los programas -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Todos los Programas</h2>
                <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-red-500">
                    <option>Mas escuchados</option>
                    <option>Alfabetico A-Z</option>
                    <option>Mas recientes</option>
                </select>
            </div>

            <?php if (empty($programas)): ?>
                <div class="bg-gray-50 rounded-2xl p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay programas disponibles</h3>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($programas as $programa): ?>
                        <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                            <div class="relative aspect-[16/9] overflow-hidden">
                                <img src="<?php echo esc_url($programa['imagen'] ?? 'https://picsum.photos/seed/radio' . rand(1,100) . '/400/225'); ?>"
                                     alt="<?php echo esc_attr($programa['titulo']); ?>"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <div class="absolute bottom-3 left-3 text-white">
                                    <p class="font-bold"><?php echo esc_html($programa['titulo']); ?></p>
                                    <p class="text-sm text-white/80"><?php echo esc_html($programa['horario'] ?? 'L-V 10:00'); ?></p>
                                </div>
                            </div>
                            <div class="p-5">
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo esc_html($programa['descripcion'] ?? ''); ?></p>
                                <a href="<?php echo esc_url($programa['url'] ?? '#'); ?>"
                                   class="text-red-600 font-medium text-sm hover:text-red-700">
                                    Ver programa →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
