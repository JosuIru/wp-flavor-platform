<?php
/**
 * Template: Como Unirse a Comunidades
 *
 * @var string $titulo_seccion
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion_valor = $titulo_seccion ?? __('Como Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN);

$pasos_para_unirse = [
    [
        'numero'      => '1',
        'titulo'      => __('Explora', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Navega por las comunidades disponibles y encuentra las que se alineen con tus intereses. Usa los filtros por categoria para encontrar exactamente lo que buscas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
        'color_desde' => 'from-blue-500',
        'color_hasta' => 'to-cyan-500',
        'color_fondo' => 'bg-blue-50',
        'color_texto' => 'text-blue-600',
    ],
    [
        'numero'      => '2',
        'titulo'      => __('Unete', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Haz clic en "Unirse" para entrar en comunidades abiertas al instante. Para comunidades cerradas, envia una solicitud y espera la aprobacion del administrador.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>',
        'color_desde' => 'from-emerald-500',
        'color_hasta' => 'to-green-500',
        'color_fondo' => 'bg-emerald-50',
        'color_texto' => 'text-emerald-600',
    ],
    [
        'numero'      => '3',
        'titulo'      => __('Participa', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Publica contenido, comenta, participa en eventos y encuestas. Cuanto mas activo seas, mas enriquecedora sera la experiencia para todos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'color_desde' => 'from-purple-500',
        'color_hasta' => 'to-violet-500',
        'color_fondo' => 'bg-purple-50',
        'color_texto' => 'text-purple-600',
    ],
    [
        'numero'      => '4',
        'titulo'      => __('Crece', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Conviertete en moderador, organiza eventos o crea tu propia comunidad. Las posibilidades crecen contigo y con la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
        'color_desde' => 'from-orange-500',
        'color_hasta' => 'to-amber-500',
        'color_fondo' => 'bg-orange-50',
        'color_texto' => 'text-orange-600',
    ],
];

$tipos_comunidad_info = [
    [
        'tipo'        => __('Abierta', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Cualquier persona puede unirse libremente sin necesidad de aprobacion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color_fondo' => 'bg-green-50',
        'color_borde' => 'border-green-200',
        'color_texto' => 'text-green-700',
        'color_icono' => 'text-green-500',
    ],
    [
        'tipo'        => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Requiere aprobacion del administrador. Envia una solicitud y espera respuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'color_fondo' => 'bg-amber-50',
        'color_borde' => 'border-amber-200',
        'color_texto' => 'text-amber-700',
        'color_icono' => 'text-amber-500',
    ],
    [
        'tipo'        => __('Secreta', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('No aparece en listados publicos. Solo puedes acceder por invitacion directa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>',
        'color_fondo' => 'bg-slate-50',
        'color_borde' => 'border-slate-200',
        'color_texto' => 'text-slate-700',
        'color_icono' => 'text-slate-500',
    ],
];
?>

<section class="flavor-component flavor-section py-20" style="background: white;">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4" style="color: var(--flavor-text, #111827);">
                    <?php echo esc_html($titulo_seccion_valor); ?>
                </h2>
                <p class="text-lg max-w-2xl mx-auto" style="color: var(--flavor-text-secondary, #6b7280);">
                    <?php esc_html_e('Sigue estos sencillos pasos para empezar a formar parte de comunidades activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Pasos -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-20">
                <?php foreach ($pasos_para_unirse as $indice_paso => $paso_datos): ?>
                    <div class="relative text-center group">
                        <!-- Linea conectora (excepto el ultimo) -->
                        <?php if ($indice_paso < count($pasos_para_unirse) - 1): ?>
                            <div class="hidden lg:block absolute top-12 left-1/2 w-full h-0.5 bg-gray-200 z-0"></div>
                        <?php endif; ?>

                        <!-- Circulo con icono -->
                        <div class="relative z-10 inline-flex items-center justify-center w-24 h-24 rounded-full mb-6 transition-transform group-hover:scale-110 bg-gradient-to-br <?php echo esc_attr($paso_datos['color_desde'] . ' ' . $paso_datos['color_hasta']); ?> shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $paso_datos['icono']; ?>
                            </svg>
                        </div>

                        <!-- Numero del paso -->
                        <div class="absolute top-0 right-1/2 translate-x-14 -translate-y-1 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white bg-gradient-to-br <?php echo esc_attr($paso_datos['color_desde'] . ' ' . $paso_datos['color_hasta']); ?> shadow-md z-20">
                            <?php echo esc_html($paso_datos['numero']); ?>
                        </div>

                        <h3 class="text-xl font-bold mb-3" style="color: var(--flavor-text, #111827);">
                            <?php echo esc_html($paso_datos['titulo']); ?>
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: var(--flavor-text-secondary, #6b7280);">
                            <?php echo esc_html($paso_datos['descripcion']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Tipos de comunidad -->
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-center mb-8" style="color: var(--flavor-text, #111827);">
                    <?php esc_html_e('Tipos de Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ($tipos_comunidad_info as $tipo_info): ?>
                        <div class="p-6 rounded-xl border-2 <?php echo esc_attr($tipo_info['color_fondo'] . ' ' . $tipo_info['color_borde']); ?> transition-all hover:shadow-md">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full <?php echo esc_attr($tipo_info['color_fondo']); ?> flex items-center justify-center">
                                    <svg class="w-5 h-5 <?php echo esc_attr($tipo_info['color_icono']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <?php echo $tipo_info['icono']; ?>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold <?php echo esc_attr($tipo_info['color_texto']); ?>">
                                    <?php echo esc_html($tipo_info['tipo']); ?>
                                </h4>
                            </div>
                            <p class="text-sm" style="color: var(--flavor-text-secondary, #6b7280);">
                                <?php echo esc_html($tipo_info['descripcion']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Call to action -->
            <div class="text-center p-10 rounded-2xl" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);">
                <h3 class="text-2xl md:text-3xl font-bold text-white mb-4">
                    <?php esc_html_e('Listo para empezar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <p class="text-lg text-white/80 mb-8 max-w-xl mx-auto">
                    <?php esc_html_e('Unete a una comunidad existente o crea la tuya propia. Es rapido, facil y gratuito.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#comunidades" class="inline-flex items-center gap-2 px-8 py-3 bg-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all" style="color: var(--flavor-primary);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php esc_html_e('Explorar Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="#crear" class="inline-flex items-center gap-2 px-8 py-3 font-bold rounded-full transition-all" style="background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.3);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <?php esc_html_e('Crear mi Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
