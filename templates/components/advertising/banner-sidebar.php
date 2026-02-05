<?php
/**
 * Template: Banner Sidebar (Rectangle)
 *
 * Banner publicitario vertical para sidebar
 *
 * @package FlavorChatIA
 * @var int $ad_id ID del anuncio
 * @var bool $mostrar_etiqueta Mostrar badge de "Anuncio"
 * @var bool $sticky Habilitar sticky positioning
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
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium_large') : '';
$mostrar_etiqueta = $mostrar_etiqueta ?? true;
$sticky = $sticky ?? false;
$ad_excerpt = $ad->post_excerpt ?: wp_trim_words($ad->post_content, 15);
?>

<div class="flavor-ad flavor-ad-sidebar flavor-component <?php echo $sticky ? 'sticky top-4' : ''; ?>"
     data-ad-id="<?php echo esc_attr($ad->ID); ?>"
     data-ad-type="sidebar">

    <div class="flavor-card overflow-hidden transition-shadow hover:shadow-lg">

        <?php if ($mostrar_etiqueta): ?>
            <div class="absolute top-3 right-3 z-10">
                <span class="inline-block px-2 py-1 text-xs font-medium text-white bg-flavor-primary/90 rounded backdrop-blur-sm">
                    Anuncio
                </span>
            </div>
        <?php endif; ?>

        <a href="<?php echo esc_url($ad_url); ?>"
           target="_blank"
           rel="noopener sponsored"
           class="flavor-ad-link block"
           onclick="flavorTrackAdClick(<?php echo esc_js($ad->ID); ?>)">

            <?php if ($thumbnail_url): ?>
                <div class="flavor-ad-image w-full aspect-[4/3] overflow-hidden bg-flavor-surface-light">
                    <img src="<?php echo esc_url($thumbnail_url); ?>"
                         alt="<?php echo esc_attr($ad->post_title); ?>"
                         class="w-full h-full object-cover transition-transform hover:scale-105">
                </div>
            <?php else: ?>
                <div class="flavor-ad-image w-full aspect-[4/3] bg-gradient-to-br from-flavor-primary to-flavor-secondary"></div>
            <?php endif; ?>

            <div class="p-4">
                <h3 class="flavor-ad-title text-flavor-text-primary font-bold text-lg mb-2 line-clamp-2">
                    <?php echo esc_html($ad->post_title); ?>
                </h3>

                <?php if ($ad_excerpt): ?>
                    <p class="flavor-ad-excerpt text-flavor-text-secondary text-sm mb-4 line-clamp-3">
                        <?php echo esc_html($ad_excerpt); ?>
                    </p>
                <?php endif; ?>

                <div class="flex justify-center">
                    <span class="flavor-button flavor-button-primary w-full text-center px-4 py-2 text-sm font-semibold">
                        <?php echo esc_html($ad_cta); ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
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
