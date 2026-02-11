<?php
/**
 * Template: Marketplace CTA Publicar Anuncio
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta_publicar = $titulo_cta_publicar ?? '¿Tienes algo para compartir?';
$subtitulo_cta_publicar = $subtitulo_cta_publicar ?? 'Publica tu anuncio de forma gratuita y llega a miles de vecinos';
$url_publicar_anuncio = $url_publicar_anuncio ?? '#nuevo-anuncio';
$mostrar_ventajas = $mostrar_ventajas ?? true;
$estilo_fondo = $estilo_fondo ?? 'gradient';

$ventajas_publicar = $ventajas_publicar ?? [
    [
        'titulo'      => 'Publicacion Instantanea',
        'descripcion' => 'Tu anuncio se publica inmediatamente y es visible para todos los usuarios de la plataforma.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'color'       => 'text-green-500',
    ],
    [
        'titulo'      => 'Completamente Gratis',
        'descripcion' => 'Publica anuncios ilimitados sin pagar comisiones, cuotas o gastos ocultos.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => 'text-green-500',
    ],
    [
        'titulo'      => 'Contacto Directo',
        'descripcion' => 'Conecta directamente con compradores interesados sin intermediarios innecesarios.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'color'       => 'text-green-500',
    ],
    [
        'titulo'      => 'Apoyas tu Comunidad',
        'descripcion' => 'Promueve la economia local y fortalece los vinculos entre vecinos de tu barrio.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
        'color'       => 'text-green-500',
    ],
];

$tipos_anuncios = $tipos_anuncios ?? [
    [
        'tipo'        => 'venta',
        'titulo'      => 'Vender',
        'descripcion' => 'Articulos nuevos o usados que ya no necesitas',
        'icono'       => '💰',
    ],
    [
        'tipo'        => 'regalo',
        'titulo'      => 'Regalar',
        'descripcion' => 'Comparte lo que tienes sin esperar dinero a cambio',
        'icono'       => '🎁',
    ],
    [
        'tipo'        => 'intercambio',
        'titulo'      => 'Intercambiar',
        'descripcion' => 'Cambia tus cosas por otras que necesites',
        'icono'       => '🔄',
    ],
];
?>

<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);">
    <div class="flavor-container">
        <!-- Contenido principal -->
        <div class="max-w-4xl mx-auto text-center mb-16">
            <!-- Badge decorativo -->
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-200 text-green-800 text-sm font-semibold mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <?php echo esc_html__('Empieza a publicar ahora', 'flavor-chat-ia'); ?>
            </span>

            <!-- Titulo principal -->
            <h2 class="text-4xl lg:text-5xl font-black text-gray-900 mb-4 leading-tight">
                <?php echo esc_html($titulo_cta_publicar); ?>
            </h2>

            <!-- Subtitulo -->
            <p class="text-xl text-gray-700 mb-12 max-w-2xl mx-auto leading-relaxed">
                <?php echo esc_html($subtitulo_cta_publicar); ?>
            </p>

            <!-- Tipos de anuncios disponibles (Opcional) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-12">
                <?php foreach ($tipos_anuncios as $tipo_item) : ?>
                    <div class="p-6 rounded-xl bg-white/80 backdrop-blur border border-green-100 shadow-sm hover:shadow-md transition-all">
                        <div class="text-4xl mb-3"><?php echo esc_html($tipo_item['icono']); ?></div>
                        <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($tipo_item['titulo']); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo esc_html($tipo_item['descripcion']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Boton CTA Principal -->
            <a href="<?php echo esc_url($url_publicar_anuncio); ?>" class="flavor-cta-publicar-btn inline-flex items-center gap-3 px-8 py-4 rounded-xl bg-gradient-to-r from-lime-500 via-green-500 to-emerald-600 text-white font-bold text-lg hover:from-lime-600 hover:via-green-600 hover:to-emerald-700 transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span><?php echo esc_html__('Publicar Anuncio Gratis', 'flavor-chat-ia'); ?></span>
            </a>
        </div>

        <?php if ($mostrar_ventajas) : ?>
            <!-- Ventajas de publicar -->
            <div class="max-w-5xl mx-auto">
                <h3 class="text-2xl font-bold text-gray-900 text-center mb-10">
                    <?php echo esc_html__('Por que publicar con nosotros?', 'flavor-chat-ia'); ?>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($ventajas_publicar as $ventaja_item) : ?>
                        <div class="flavor-ventaja-card p-6 rounded-2xl bg-white shadow-sm border border-green-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                            <!-- Icono -->
                            <div class="w-14 h-14 rounded-xl bg-green-100 flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 <?php echo esc_attr($ventaja_item['color']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $ventaja_item['icono']; ?>
                                </svg>
                            </div>

                            <!-- Contenido -->
                            <h4 class="text-lg font-bold text-gray-900 mb-2">
                                <?php echo esc_html($ventaja_item['titulo']); ?>
                            </h4>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                <?php echo esc_html($ventaja_item['descripcion']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Seccion complementaria -->
            <div class="max-w-4xl mx-auto mt-16 p-8 rounded-2xl bg-white/50 backdrop-blur border border-green-100">
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        <?php echo esc_html__('Pasos simples para publicar', 'flavor-chat-ia'); ?>
                    </h3>
                    <ol class="flex flex-col md:flex-row justify-center items-center gap-6 text-sm text-gray-700">
                        <li class="flex items-center gap-2">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 text-white font-bold flex items-center justify-center">1</span>
                            <span><?php echo esc_html__('Completa el formulario', 'flavor-chat-ia'); ?></span>
                        </li>
                        <svg class="w-4 h-4 text-gray-300 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <li class="flex items-center gap-2">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 text-white font-bold flex items-center justify-center">2</span>
                            <span><?php echo esc_html__('Agrega fotos y descripcion', 'flavor-chat-ia'); ?></span>
                        </li>
                        <svg class="w-4 h-4 text-gray-300 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <li class="flex items-center gap-2">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 text-white font-bold flex items-center justify-center">3</span>
                            <span><?php echo esc_html__('Publica y recibe mensajes', 'flavor-chat-ia'); ?></span>
                        </li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
