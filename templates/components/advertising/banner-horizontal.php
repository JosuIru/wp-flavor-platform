<?php
/**
 * Template: Banner Horizontal (Leaderboard)
 *
 * Banner publicitario horizontal responsive
 *
 * @package FlavorPlatform
 * @var int $ad_id ID del anuncio
 * @var bool $mostrar_etiqueta Mostrar badge de "Anuncio"
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del anuncio
$ad = get_post($ad_id);
if (!$ad) return;

$ad_url = get_post_meta($ad->ID, '_ad_url', true);
$ad_cta = get_post_meta($ad->ID, '_ad_cta', true) ?: 'Más información';
$thumbnail_id = get_post_thumbnail_id($ad->ID);
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'full') : '';
$mostrar_etiqueta = $mostrar_etiqueta ?? true;
?>

<div class="flavor-ad flavor-ad-horizontal flavor-component"
     data-ad-id="<?php echo esc_attr($ad->ID); ?>"
     data-ad-type="horizontal">

    <a href="<?php echo esc_url($ad_url); ?>"
       target="_blank"
       rel="noopener sponsored"
       class="flavor-ad-link block relative overflow-hidden rounded-lg transition-transform hover:scale-[1.02] w-full"
       onclick="flavorTrackAdClick(<?php echo esc_js($ad->ID); ?>)">

        <?php if ($thumbnail_url): ?>
            <div class="flavor-ad-image absolute inset-0 w-full h-full">
                <img src="<?php echo esc_url($thumbnail_url); ?>"
                     alt="<?php echo esc_attr($ad->post_title); ?>"
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent"></div>
            </div>
        <?php else: ?>
            <div class="flavor-ad-image absolute inset-0 w-full h-full bg-gradient-to-r from-flavor-primary to-flavor-secondary"></div>
        <?php endif; ?>

        <div class="relative z-10 flex items-center justify-between px-6 py-4 md:px-8 md:py-6 min-h-[90px] md:min-h-[120px]">
            <div class="flex-1 pr-4">
                <h3 class="flavor-ad-title text-white font-bold text-lg md:text-2xl mb-1 line-clamp-2">
                    <?php echo esc_html($ad->post_title); ?>
                </h3>

                <?php if ($mostrar_etiqueta): ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium text-white/90 bg-white/20 rounded backdrop-blur-sm">
                        Anuncio
                    </span>
                <?php endif; ?>
            </div>

            <div class="flex-shrink-0">
                <span class="flavor-button flavor-button-primary px-4 py-2 md:px-6 md:py-3 text-sm md:text-base font-semibold whitespace-nowrap">
                    <?php echo esc_html($ad_cta); ?>
                </span>
            </div>
        </div>
    </a>
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
