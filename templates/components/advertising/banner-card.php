<?php
/**
 * Template: Banner Card
 *
 * Anuncio tipo tarjeta con múltiples estilos
 *
 * @package FlavorPlatform
 * @var int $ad_id ID del anuncio
 * @var string $estilo Estilo: 'minimal', 'card', 'featured'
 * @var bool $mostrar_etiqueta Mostrar badge de "Contenido patrocinado"
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
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
$mostrar_etiqueta = $mostrar_etiqueta ?? true;
$estilo = $estilo ?? 'card';
$ad_excerpt = $ad->post_excerpt ?: wp_trim_words($ad->post_content, 20);

// Clases según el estilo
$container_classes = [
    'minimal' => 'flavor-card-minimal',
    'card' => 'flavor-card',
    'featured' => 'flavor-card-featured'
];
$container_class = $container_classes[$estilo] ?? $container_classes['card'];
?>

<div class="flavor-ad flavor-ad-card flavor-component"
     data-ad-id="<?php echo esc_attr($ad->ID); ?>"
     data-ad-type="card"
     data-ad-style="<?php echo esc_attr($estilo); ?>">

    <div class="<?php echo esc_attr($container_class); ?> overflow-hidden transition-all hover:shadow-xl">

        <?php if ($mostrar_etiqueta): ?>
            <div class="absolute top-4 left-4 z-10">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-flavor-text-secondary bg-flavor-surface-light/95 rounded-full backdrop-blur-sm border border-flavor-border">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Contenido patrocinado
                </span>
            </div>
        <?php endif; ?>

        <a href="<?php echo esc_url($ad_url); ?>"
           target="_blank"
           rel="noopener sponsored"
           class="flavor-ad-link block group"
           onclick="flavorTrackAdClick(<?php echo esc_js($ad->ID); ?>)">

            <?php if ($estilo === 'minimal'): ?>
                <!-- Estilo Minimal -->
                <div class="flex items-center gap-4 p-4">
                    <?php if ($thumbnail_url): ?>
                        <div class="flavor-ad-image flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-flavor-surface-light">
                            <img src="<?php echo esc_url($thumbnail_url); ?>"
                                 alt="<?php echo esc_attr($ad->post_title); ?>"
                                 class="w-full h-full object-cover transition-transform group-hover:scale-110">
                        </div>
                    <?php endif; ?>

                    <div class="flex-1 min-w-0">
                        <h3 class="flavor-ad-title text-flavor-text-primary font-semibold text-base mb-1 line-clamp-2">
                            <?php echo esc_html($ad->post_title); ?>
                        </h3>
                        <span class="text-flavor-primary text-sm font-medium">
                            <?php echo esc_html($ad_cta); ?> →
                        </span>
                    </div>
                </div>

            <?php elseif ($estilo === 'featured'): ?>
                <!-- Estilo Featured -->
                <?php if ($thumbnail_url): ?>
                    <div class="flavor-ad-image relative w-full aspect-[16/9] overflow-hidden bg-flavor-surface-light">
                        <img src="<?php echo esc_url($thumbnail_url); ?>"
                             alt="<?php echo esc_attr($ad->post_title); ?>"
                             class="w-full h-full object-cover transition-transform group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    </div>
                <?php endif; ?>

                <div class="p-6 md:p-8">
                    <h3 class="flavor-ad-title text-flavor-text-primary font-bold text-2xl md:text-3xl mb-3 line-clamp-2">
                        <?php echo esc_html($ad->post_title); ?>
                    </h3>

                    <?php if ($ad_excerpt): ?>
                        <p class="flavor-ad-excerpt text-flavor-text-secondary text-base mb-6 line-clamp-3">
                            <?php echo esc_html($ad_excerpt); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex">
                        <span class="flavor-button flavor-button-primary flavor-button-lg px-8 py-3 text-base font-bold">
                            <?php echo esc_html($ad_cta); ?>
                        </span>
                    </div>
                </div>

            <?php else: ?>
                <!-- Estilo Card (default) -->
                <?php if ($thumbnail_url): ?>
                    <div class="flavor-ad-image w-full aspect-[16/9] overflow-hidden bg-flavor-surface-light">
                        <img src="<?php echo esc_url($thumbnail_url); ?>"
                             alt="<?php echo esc_attr($ad->post_title); ?>"
                             class="w-full h-full object-cover transition-transform group-hover:scale-105">
                    </div>
                <?php endif; ?>

                <div class="p-5">
                    <h3 class="flavor-ad-title text-flavor-text-primary font-bold text-xl mb-2 line-clamp-2">
                        <?php echo esc_html($ad->post_title); ?>
                    </h3>

                    <?php if ($ad_excerpt): ?>
                        <p class="flavor-ad-excerpt text-flavor-text-secondary text-sm mb-4 line-clamp-3">
                            <?php echo esc_html($ad_excerpt); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex">
                        <span class="flavor-button flavor-button-secondary px-5 py-2.5 text-sm font-semibold">
                            <?php echo esc_html($ad_cta); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
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
