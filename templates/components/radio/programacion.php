<?php
/**
 * Template: Programacion de Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Programacion Semanal';
$dia_activo = $dia_activo ?? 'lunes';

$dias = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miercoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sabado', 'domingo' => 'Domingo'];

$programas = [
    ['nombre' => 'Despierta Vecinal', 'horario' => '07:00 - 09:00', 'locutor' => 'Carlos Martinez', 'descripcion' => 'Noticias locales y musica para empezar el dia', 'icono' => '☀️'],
    ['nombre' => 'Magazine de la Manana', 'horario' => '09:00 - 12:00', 'locutor' => 'Maria Garcia', 'descripcion' => 'Entrevistas, cultura y actualidad', 'icono' => '🎙️'],
    ['nombre' => 'Informativos Locales', 'horario' => '12:00 - 13:00', 'locutor' => 'Pedro Lopez', 'descripcion' => 'Lo mas importante de tu zona', 'icono' => '📰'],
    ['nombre' => 'Sobremesa Musical', 'horario' => '13:00 - 16:00', 'locutor' => 'Ana Torres', 'descripcion' => 'Los exitos de siempre y novedades', 'icono' => '🎵'],
    ['nombre' => 'Tarde Deportiva', 'horario' => '16:00 - 18:00', 'locutor' => 'Juan Sanchez', 'descripcion' => 'Futbol, baloncesto y deportes locales', 'icono' => '⚽'],
    ['nombre' => 'Cultura y Sociedad', 'horario' => '18:00 - 20:00', 'locutor' => 'Laura Ruiz', 'descripcion' => 'Arte, literatura y eventos culturales', 'icono' => '🎭'],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-purple-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Programacion
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600">Descubre todos nuestros programas</p>
        </div>

        <!-- Selector de dias -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <?php foreach ($dias as $slug => $nombre): ?>
                <button class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 <?php echo $slug === $dia_activo ? '' : 'hover:bg-purple-50'; ?>"
                        style="<?php echo $slug === $dia_activo ? 'background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); color: white;' : 'background: white; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
                    <?php echo esc_html($nombre); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Lista de programas -->
        <div class="max-w-3xl mx-auto space-y-4">
            <?php foreach ($programas as $i => $prog): ?>
                <div class="group bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 <?php echo $i === 1 ? 'ring-2 ring-purple-500 ring-offset-2' : ''; ?>">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-16 h-16 rounded-xl bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center text-3xl">
                            <?php echo $prog['icono']; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition-colors"><?php echo esc_html($prog['nombre']); ?></h3>
                                <?php if ($i === 1): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                        EN VIVO
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 mb-2"><?php echo esc_html($prog['descripcion']); ?></p>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?php echo esc_html($prog['horario']); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <?php echo esc_html($prog['locutor']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <?php if ($i === 1): ?>
                                <button class="p-3 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </button>
                            <?php else: ?>
                                <button class="p-3 rounded-full bg-gray-100 text-gray-600 hover:bg-purple-100 hover:text-purple-600 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="#programacion-completa" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); color: white;">
                <span>Ver Programacion Completa</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
