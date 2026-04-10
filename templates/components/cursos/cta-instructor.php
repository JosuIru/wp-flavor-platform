<?php
/**
 * Template: CTA para Hacerse Instructor
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Comparte tu Conocimiento';
$subtitulo = $subtitulo ?? 'Unete a nuestra comunidad de instructores y ayuda a otros a aprender';

$beneficios = [
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Genera Ingresos', 'desc' => 'Gana dinero compartiendo lo que sabes'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Horarios Flexibles', 'desc' => 'Tu decides cuando y como ensenas'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>', 'titulo' => 'Impacto Real', 'desc' => 'Ayuda a cientos de estudiantes a crecer'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>', 'titulo' => 'Soporte Completo', 'desc' => 'Te ayudamos en cada paso del proceso'],
];

$stats = [
    ['numero' => '50+', 'texto' => 'Instructores Activos'],
    ['numero' => '1,200+', 'texto' => 'Estudiantes Formados'],
    ['numero' => '4.8', 'texto' => 'Valoracion Media'],
    ['numero' => '85%', 'texto' => 'Repiten Curso'],
];

$testimonios = [
    ['nombre' => 'Carlos Mendez', 'rol' => 'Instructor de Programacion', 'avatar' => 'https://i.pravatar.cc/150?img=60', 'texto' => 'Empece como hobby y ahora es mi fuente principal de ingresos. La comunidad es increible.', 'cursos' => 8],
    ['nombre' => 'Laura Garcia', 'rol' => 'Instructora de Yoga', 'avatar' => 'https://i.pravatar.cc/150?img=32', 'texto' => 'Poder ensear lo que amo y ayudar a otros a encontrar su bienestar no tiene precio.', 'cursos' => 5],
];
?>

<section class="flavor-component py-16 relative overflow-hidden" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6" style="background: rgba(255,255,255,0.2); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <?php echo esc_html__('Se Instructor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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

        <!-- Stats -->
        <div class="bg-white rounded-2xl p-8 mb-12 shadow-2xl">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <?php foreach ($stats as $stat): ?>
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold mb-2" style="color: #06b6d4;"><?php echo esc_html($stat['numero']); ?></div>
                        <div class="text-sm font-medium text-gray-600"><?php echo esc_html($stat['texto']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Testimonios -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            <?php foreach ($testimonios as $test): ?>
                <div class="bg-white rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="<?php echo esc_url($test['avatar']); ?>" alt="<?php echo esc_attr($test['nombre']); ?>" class="w-14 h-14 rounded-full object-cover">
                        <div>
                            <div class="font-bold text-gray-900"><?php echo esc_html($test['nombre']); ?></div>
                            <div class="text-sm text-cyan-600"><?php echo esc_html($test['rol']); ?></div>
                        </div>
                        <div class="ml-auto text-center">
                            <div class="text-2xl font-bold text-cyan-600"><?php echo esc_html($test['cursos']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo esc_html__('cursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"<?php echo esc_html($test['texto']); ?>"</p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA Final -->
        <div class="text-center bg-white/15 backdrop-blur-sm rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-white mb-4"><?php echo esc_html__('Listo para Empezar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="text-white/90 mb-6"><?php echo esc_html__('El proceso es sencillo. Registrate, crea tu perfil y comienza a ensear.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#registro-instructor" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl bg-white text-cyan-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span><?php echo esc_html__('Quiero Ser Instructor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <a href="#como-funciona" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:bg-white/20" style="background: transparent; color: white; border: 2px solid rgba(255,255,255,0.5);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?php echo esc_html__('Como Funciona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
            </div>
        </div>
    </div>
</section>
