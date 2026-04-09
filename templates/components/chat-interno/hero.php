<?php
/**
 * Template: Chat Interno Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'Mensajeria Interna';
$subtitulo_hero = $subtitulo_hero ?? 'Comunicate de forma segura con tu comunidad';
$url_empezar = $url_empezar ?? '#empezar-chat';

$caracteristicas_destacadas = $caracteristicas_destacadas ?? [
    [
        'nombre' => 'Cifrado',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
    ],
    [
        'nombre' => 'Sin publicidad',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
    ],
    [
        'nombre' => 'Privacidad',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    ],
];
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #F43F5E) 0%, var(--flavor-secondary, #EC4899) 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Mensajeria segura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-8">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- Iconos de caracteristicas -->
            <div class="flex items-center justify-center gap-8 mb-10">
                <?php foreach ($caracteristicas_destacadas as $caracteristica_destacada) : ?>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center bg-white/15 backdrop-blur border border-white/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $caracteristica_destacada['icono']; ?>
                            </svg>
                        </div>
                        <span class="text-white/80 text-sm font-medium"><?php echo esc_html($caracteristica_destacada['nombre']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_empezar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-rose-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <?php echo esc_html__('Empezar a Chatear', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</section>
