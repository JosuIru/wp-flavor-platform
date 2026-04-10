<?php
/**
 * Template: CTA para Hacerse Locutor
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Tu Voz Puede Ser Escuchada';
$subtitulo = $subtitulo ?? 'Unete a nuestro equipo de locutores y comparte tu pasion con la comunidad';

$beneficios = [
    ['titulo' => 'Expresate', 'desc' => 'Tu propio programa con total libertad creativa', 'icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>'],
    ['titulo' => 'Formacion', 'desc' => 'Cursos gratuitos de locution y produccion', 'icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'],
    ['titulo' => 'Equipo', 'desc' => 'Acceso a estudios profesionales', 'icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'],
    ['titulo' => 'Comunidad', 'desc' => 'Red de apoyo entre locutores', 'icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'],
];

$testimonios = [
    ['nombre' => 'Elena Vega', 'programa' => 'Tardes de Jazz', 'avatar' => 'https://i.pravatar.cc/150?img=29', 'texto' => 'Empece sin experiencia y ahora tengo mi propio programa. El equipo te apoya en todo.'],
    ['nombre' => 'Roberto Diaz', 'programa' => 'Deportes al Dia', 'avatar' => 'https://i.pravatar.cc/150?img=53', 'texto' => 'La mejor decision que tome. Conectar con los oyentes es una experiencia unica.'],
];
?>

<section class="flavor-component py-16 relative overflow-hidden" style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6" style="background: rgba(255,255,255,0.2); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                <?php echo esc_html__('Se Locutor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                            <div class="text-sm text-purple-600"><?php echo esc_html($test['programa']); ?></div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"<?php echo esc_html($test['texto']); ?>"</p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA -->
        <div class="text-center bg-white/15 backdrop-blur-sm rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-white mb-4"><?php echo esc_html__('Empieza Tu Aventura en la Radio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="text-white/90 mb-6"><?php echo esc_html__('No necesitas experiencia previa. Solo ganas de comunicar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#solicitar" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl bg-white text-purple-600 hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <?php echo esc_html__('Quiero Ser Locutor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#info" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl text-white border-2 border-white/50 hover:bg-white/20 transition-colors">
                    <?php echo esc_html__('Mas Informacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>
