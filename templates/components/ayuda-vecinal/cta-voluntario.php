<?php
/**
 * Template: CTA para Hacerse Voluntario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Se Parte del Cambio';
$subtitulo = $subtitulo ?? 'Ayudar a tus vecinos es mas facil de lo que piensas. Pequenos gestos, grandes impactos.';

$beneficios = [
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>', 'titulo' => 'Crea Comunidad', 'desc' => 'Conoce a tus vecinos y fortalece lazos'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Tu Tiempo, Tu Ritmo', 'desc' => 'Elige cuando y como quieres ayudar'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>', 'titulo' => 'Reconocimiento', 'desc' => 'Gana insignias y puntos por cada ayuda'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Satisfaccion Personal', 'desc' => 'Siente la gratitud de quienes ayudas'],
];

$testimonios = [
    ['nombre' => 'Elena Fernandez', 'rol' => 'Voluntaria desde 2022', 'avatar' => 'https://i.pravatar.cc/150?img=45', 'texto' => 'Empece ayudando a mi vecina con las compras y ahora tengo amigos de todas las edades en el barrio.', 'ayudas' => 47],
    ['nombre' => 'Roberto Iglesias', 'rol' => 'Voluntario desde 2023', 'avatar' => 'https://i.pravatar.cc/150?img=68', 'texto' => 'Ver la sonrisa de las personas mayores cuando les ensenamos a usar el movil no tiene precio.', 'ayudas' => 23],
];
?>

<section class="flavor-component py-16 relative overflow-hidden" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot;%3E%3Cpath d=&quot;M30 30l15-15v30l-15 15V30zm0 0L15 15v30l15 15V30z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6" style="background: rgba(255,255,255,0.2); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Voluntariado
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-white/90"><?php echo esc_html($subtitulo); ?></p>
        </div>

        <!-- Beneficios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach ($beneficios as $ben): ?>
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-6 transition-all duration-300 hover:bg-white/25 hover:-translate-y-1">
                    <div class="inline-flex items-center justify-center p-3 rounded-xl mb-4" style="background: rgba(255,255,255,0.2); color: white;">
                        <?php echo $ben['icono']; ?>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?php echo esc_html($ben['titulo']); ?></h3>
                    <p class="text-white/80 text-sm"><?php echo esc_html($ben['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Testimonios -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            <?php foreach ($testimonios as $test): ?>
                <div class="bg-white rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="<?php echo esc_url($test['avatar']); ?>" alt="<?php echo esc_attr($test['nombre']); ?>" class="w-14 h-14 rounded-full object-cover">
                        <div>
                            <div class="font-bold text-gray-900"><?php echo esc_html($test['nombre']); ?></div>
                            <div class="text-sm text-amber-600"><?php echo esc_html($test['rol']); ?></div>
                        </div>
                        <div class="ml-auto text-center">
                            <div class="text-2xl font-bold text-amber-600"><?php echo esc_html($test['ayudas']); ?></div>
                            <div class="text-xs text-gray-500">ayudas</div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"<?php echo esc_html($test['texto']); ?>"</p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA Final -->
        <div class="text-center bg-white/15 backdrop-blur-sm rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-white mb-4">Listo para Empezar?</h3>
            <p class="text-white/90 mb-6">Registrate como voluntario en menos de 2 minutos y empieza a marcar la diferencia</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#registro-voluntario" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl bg-white text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span>Quiero Ser Voluntario</span>
                </a>
                <a href="#solicitar-ayuda" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:bg-white/20" style="background: transparent; color: white; border: 2px solid rgba(255,255,255,0.5);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Necesito Ayuda</span>
                </a>
            </div>
        </div>
    </div>
</section>
