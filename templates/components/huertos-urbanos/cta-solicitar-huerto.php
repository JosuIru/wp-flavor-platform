<?php
/**
 * Template: CTA para Solicitar Huerto
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Cultiva Tu Propio Huerto';
$subtitulo = $subtitulo ?? 'Conecta con la tierra, produce alimentos sanos y forma parte de una comunidad que crece junta';

$beneficios = [
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>', 'titulo' => 'Salud', 'desc' => 'Alimentos frescos y ecologicos para tu familia'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>', 'titulo' => 'Comunidad', 'desc' => 'Conoce vecinos con tus mismos intereses'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>', 'titulo' => 'Aprendizaje', 'desc' => 'Talleres gratuitos para hortelanos'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Sostenibilidad', 'desc' => 'Reduce tu huella ecologica'],
];

$pasos = [
    ['numero' => '1', 'titulo' => 'Solicita', 'desc' => 'Rellena el formulario de solicitud online'],
    ['numero' => '2', 'titulo' => 'Formacion', 'desc' => 'Asiste al taller de bienvenida obligatorio'],
    ['numero' => '3', 'titulo' => 'Asignacion', 'desc' => 'Recibe tu parcela segun disponibilidad'],
    ['numero' => '4', 'titulo' => 'Cultiva', 'desc' => 'Empieza a disfrutar de tu huerto'],
];

$requisitos = [
    'Ser mayor de 18 anos',
    'Estar empadronado en el municipio',
    'Comprometerse a un uso responsable',
    'No tener otro huerto municipal asignado',
];
?>

<section class="flavor-component py-16 relative overflow-hidden" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6" style="background: rgba(255,255,255,0.2); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <?php echo esc_html__('Solicita tu Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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

        <!-- Proceso y Requisitos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Pasos -->
            <div class="bg-white rounded-2xl p-8 shadow-2xl">
                <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Como solicitarlo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="space-y-4">
                    <?php foreach ($pasos as $paso): ?>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                                <?php echo esc_html($paso['numero']); ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900"><?php echo esc_html($paso['titulo']); ?></h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html($paso['desc']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Requisitos -->
            <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-white mb-6"><?php echo esc_html__('Requisitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <ul class="space-y-4">
                    <?php foreach ($requisitos as $requisito): ?>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-6 h-6 flex-shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <?php echo esc_html($requisito); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-6 p-4 rounded-xl bg-white/10">
                    <p class="text-white/90 text-sm">
                        <strong><?php echo esc_html__('Nota:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('El tiempo de espera actual es de aproximadamente 6-12 meses dependiendo del huerto solicitado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA Final -->
        <div class="text-center">
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#formulario-solicitud" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl bg-white text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span><?php echo esc_html__('Solicitar Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <a href="#mas-info" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:bg-white/20" style="background: transparent; color: white; border: 2px solid rgba(255,255,255,0.5);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?php echo esc_html__('Mas Informacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
            </div>
            <p class="mt-6 text-white/70 text-sm"><?php echo esc_html__('Convocatoria abierta todo el ano · Asignacion por sorteo entre solicitantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>
</section>
