<?php
/**
 * Template: CTA para Crear tu Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Crea Tu Propio Podcast';
$subtitulo = $subtitulo ?? 'Comparte tus ideas con toda la comunidad. Es facil, gratuito y divertido.';

$pasos = [
    ['numero' => '1', 'titulo' => 'Registrate', 'desc' => 'Crea tu cuenta de podcaster en menos de 2 minutos'],
    ['numero' => '2', 'titulo' => 'Configura', 'desc' => 'Personaliza tu canal con nombre, descripcion e imagen'],
    ['numero' => '3', 'titulo' => 'Graba', 'desc' => 'Usa nuestro estudio virtual o sube tus grabaciones'],
    ['numero' => '4', 'titulo' => 'Publica', 'desc' => 'Comparte con la comunidad y llega a nuevos oyentes'],
];

$features = [
    ['icono' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>', 'texto' => 'Estudio de grabacion virtual'],
    ['icono' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>', 'texto' => 'Editor de audio integrado'],
    ['icono' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>', 'texto' => 'Estadisticas detalladas'],
    ['icono' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>', 'texto' => 'Comunidad de oyentes activa'],
];

$stats = [
    ['numero' => '150+', 'texto' => 'Podcasts activos'],
    ['numero' => '5K+', 'texto' => 'Episodios publicados'],
    ['numero' => '25K+', 'texto' => 'Oyentes mensuales'],
];
?>

<section class="flavor-component py-16 relative overflow-hidden" style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;4&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6" style="background: rgba(255,255,255,0.2); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Crea tu Podcast
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-white/90"><?php echo esc_html($subtitulo); ?></p>
        </div>

        <!-- Pasos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach ($pasos as $paso): ?>
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-6 text-center hover:bg-white/25 transition-all duration-300">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white text-teal-600 text-xl font-bold mb-4">
                        <?php echo esc_html($paso['numero']); ?>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?php echo esc_html($paso['titulo']); ?></h3>
                    <p class="text-white/80 text-sm"><?php echo esc_html($paso['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Features y Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Features -->
            <div class="bg-white rounded-2xl p-8 shadow-2xl">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Todo lo que necesitas</h3>
                <div class="space-y-4">
                    <?php foreach ($features as $feature): ?>
                        <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-teal-50 transition-colors">
                            <div class="flex-shrink-0 p-2 rounded-lg bg-teal-100 text-teal-600">
                                <?php echo $feature['icono']; ?>
                            </div>
                            <span class="font-medium text-gray-700"><?php echo esc_html($feature['texto']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-8 flex flex-col justify-center">
                <h3 class="text-2xl font-bold text-white mb-8 text-center">Unete a nuestra comunidad</h3>
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach ($stats as $stat): ?>
                        <div class="text-center">
                            <div class="text-3xl md:text-4xl font-bold text-white mb-1"><?php echo esc_html($stat['numero']); ?></div>
                            <div class="text-sm text-white/80"><?php echo esc_html($stat['texto']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- CTA Final -->
        <div class="text-center">
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#crear-podcast" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl bg-white text-teal-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    <span>Empezar Ahora</span>
                </a>
                <a href="#como-funciona" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:bg-white/20" style="background: transparent; color: white; border: 2px solid rgba(255,255,255,0.5);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Ver Demo</span>
                </a>
            </div>
            <p class="mt-6 text-white/70 text-sm">Sin tarjeta de credito · Configuracion en 2 minutos · Soporte incluido</p>
        </div>
    </div>
</section>
