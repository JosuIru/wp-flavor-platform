<?php
/**
 * Template: Banner Nativo
 *
 * Anuncio nativo integrado que se mimetiza con el contenido
 *
 * @package FlavorPlatform
 * @var int $ad_id ID del anuncio
 * @var string $titulo_personalizado Título personalizado (opcional)
 * @var bool $mostrar_etiqueta Mostrar badge de "Patrocinado por"
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del anuncio
$ad = get_post($ad_id);
if (!$ad) return;

$ad_url = get_post_meta($ad->ID, '_ad_url', true);
$ad_cta = get_post_meta($ad->ID, '_ad_cta', true) ?: 'Descubre más';
$ad_anunciante = get_post_meta($ad->ID, '_ad_anunciante', true) ?: get_bloginfo('name');
$thumbnail_id = get_post_thumbnail_id($ad->ID);
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
$mostrar_etiqueta = $mostrar_etiqueta ?? true;
$titulo_personalizado = $titulo_personalizado ?? '';
$titulo_mostrar = !empty($titulo_personalizado) ? $titulo_personalizado : $ad->post_title;
$ad_content = $ad->post_content ? wp_trim_words($ad->post_content, 40) : $ad->post_excerpt;
?>

<div class="flavor-ad flavor-ad-nativo flavor-component"
     data-ad-id="<?php echo esc_attr($ad->ID); ?>"
     data-ad-type="nativo">

    <article class="flavor-article-native relative">

        <?php if ($mostrar_etiqueta): ?>
            <div class="mb-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-flavor-text-tertiary">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Patrocinado por <span class="font-semibold text-flavor-text-secondary"><?php echo esc_html($ad_anunciante); ?></span>
                </span>
            </div>
        <?php endif; ?>

        <a href="<?php echo esc_url($ad_url); ?>"
           target="_blank"
           rel="noopener sponsored"
           class="flavor-ad-link block group"
           onclick="flavorTrackAdClick(<?php echo esc_js($ad->ID); ?>)">

            <div class="flex flex-col md:flex-row gap-4 md:gap-6">

                <?php if ($thumbnail_url): ?>
                    <div class="flavor-ad-image flex-shrink-0 w-full md:w-64 lg:w-80">
                        <div class="relative aspect-[16/9] md:aspect-[4/3] rounded-lg overflow-hidden bg-flavor-surface-light">
                            <img src="<?php echo esc_url($thumbnail_url); ?>"
                                 alt="<?php echo esc_attr($titulo_mostrar); ?>"
                                 class="w-full h-full object-cover transition-transform group-hover:scale-105">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex-1 min-w-0">
                    <h2 class="flavor-ad-title text-flavor-text-primary font-bold text-xl md:text-2xl mb-3 line-clamp-2 group-hover:text-flavor-primary transition-colors">
                        <?php echo esc_html($titulo_mostrar); ?>
                    </h2>

                    <?php if ($ad_content): ?>
                        <div class="flavor-ad-content text-flavor-text-secondary text-base leading-relaxed mb-4">
                            <p class="line-clamp-3 md:line-clamp-4">
                                <?php echo esc_html($ad_content); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-2">
                        <span class="text-flavor-primary font-semibold text-sm group-hover:underline">
                            <?php echo esc_html($ad_cta); ?>
                        </span>
                        <svg class="w-4 h-4 text-flavor-primary transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </a>

        <!-- Línea separadora sutil -->
        <div class="mt-6 pt-6 border-t border-flavor-border/30">
            <p class="text-xs text-flavor-text-tertiary italic">
                Este es contenido promocional. <?php echo esc_html($ad_anunciante); ?> colabora con nosotros para ofrecerte esta información.
            </p>
        </div>
    </article>
</div>

<script>
// Tracking de impresiones
(function() {
    const adElement = document.querySelector('[data-ad-id="<?php echo esc_js($ad->ID); ?>"]');
    if (adElement && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (typeof flavorTrackAdImpression === 'function') {
                        flavorTrackAdImpression(<?php echo esc_js($ad->ID); ?>);
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        observer.observe(adElement);
    }
})();

// Función de tracking de clicks
function flavorTrackAdClick(adId) {
    if (typeof window.flavorAds !== 'undefined' && window.flavorAds.trackClick) {
        window.flavorAds.trackClick(adId);
    }
}
</script>
