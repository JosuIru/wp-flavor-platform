<?php
/**
 * Componente: Image Gallery
 *
 * Galería de imágenes con lightbox y múltiples layouts.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $images     Array de imágenes: [['src' => '', 'thumb' => '', 'alt' => '', 'caption' => '']]
 * @param string $layout     Layout: grid, masonry, carousel, stack
 * @param int    $columns    Número de columnas (2-6)
 * @param string $gap        Gap entre imágenes: sm, md, lg
 * @param bool   $lightbox   Habilitar lightbox
 * @param bool   $show_captions Mostrar captions
 * @param string $aspect     Aspect ratio para grid: auto, square, 4:3, 16:9
 * @param int    $limit      Límite de imágenes visibles (0 = todas)
 */

if (!defined('ABSPATH')) {
    exit;
}

$images = $images ?? [];
$layout = $layout ?? 'grid';
$columns = max(2, min(6, intval($columns ?? 3)));
$gap = $gap ?? 'md';
$lightbox = $lightbox ?? true;
$show_captions = $show_captions ?? false;
$aspect = $aspect ?? 'auto';
$limit = intval($limit ?? 0);

$gallery_id = 'flavor-gallery-' . wp_rand(1000, 9999);

// Gaps
$gap_classes = [
    'sm' => 'gap-1',
    'md' => 'gap-2',
    'lg' => 'gap-4',
];
$gap_class = $gap_classes[$gap] ?? $gap_classes['md'];

// Aspect ratios
$aspect_classes = [
    'auto'   => '',
    'square' => 'aspect-square',
    '4:3'    => 'aspect-[4/3]',
    '16:9'   => 'aspect-video',
    '3:4'    => 'aspect-[3/4]',
];
$aspect_class = $aspect_classes[$aspect] ?? '';

// Columnas
$col_classes = [
    2 => 'grid-cols-2',
    3 => 'grid-cols-2 sm:grid-cols-3',
    4 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
    5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
    6 => 'grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6',
];
$col_class = $col_classes[$columns] ?? $col_classes[3];

// Limitar imágenes
$visible_images = $limit > 0 ? array_slice($images, 0, $limit) : $images;
$remaining = $limit > 0 ? max(0, count($images) - $limit) : 0;
?>

<div class="flavor-image-gallery" id="<?php echo esc_attr($gallery_id); ?>">

    <?php if ($layout === 'carousel'): ?>
        <!-- Layout Carousel -->
        <div class="flavor-gallery-carousel relative overflow-hidden rounded-xl">
            <div class="flex transition-transform duration-300 ease-out" style="transform: translateX(0);">
                <?php foreach ($images as $index => $image): ?>
                    <div class="flex-shrink-0 w-full">
                        <img src="<?php echo esc_url($image['src'] ?? $image['thumb'] ?? ''); ?>"
                             alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                             class="w-full h-auto object-cover"
                             <?php if ($lightbox): ?>data-lightbox="<?php echo esc_attr($gallery_id); ?>" data-index="<?php echo $index; ?>"<?php endif; ?>>
                        <?php if ($show_captions && !empty($image['caption'])): ?>
                            <p class="mt-2 text-sm text-gray-600 text-center"><?php echo esc_html($image['caption']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Nav buttons -->
            <button type="button" class="carousel-prev absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/80 hover:bg-white shadow-lg flex items-center justify-center transition-all">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button type="button" class="carousel-next absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/80 hover:bg-white shadow-lg flex items-center justify-center transition-all">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Dots -->
            <div class="carousel-dots absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                <?php foreach ($images as $index => $image): ?>
                    <button type="button" class="w-2 h-2 rounded-full <?php echo $index === 0 ? 'bg-white' : 'bg-white/50'; ?> transition-colors" data-index="<?php echo $index; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($layout === 'stack'): ?>
        <!-- Layout Stack (imagen principal + thumbnails) -->
        <div class="flavor-gallery-stack">
            <?php if (!empty($images)): ?>
                <div class="stack-main rounded-xl overflow-hidden mb-2">
                    <img src="<?php echo esc_url($images[0]['src'] ?? $images[0]['thumb'] ?? ''); ?>"
                         alt="<?php echo esc_attr($images[0]['alt'] ?? ''); ?>"
                         class="w-full h-auto object-cover cursor-pointer"
                         <?php if ($lightbox): ?>data-lightbox="<?php echo esc_attr($gallery_id); ?>" data-index="0"<?php endif; ?>>
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="flex <?php echo esc_attr($gap_class); ?> overflow-x-auto pb-2">
                        <?php foreach ($images as $index => $image): ?>
                            <button type="button"
                                    class="stack-thumb flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden <?php echo $index === 0 ? 'ring-2 ring-blue-500' : 'opacity-70 hover:opacity-100'; ?> transition-all"
                                    data-index="<?php echo $index; ?>">
                                <img src="<?php echo esc_url($image['thumb'] ?? $image['src'] ?? ''); ?>"
                                     alt=""
                                     class="w-full h-full object-cover">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($layout === 'masonry'): ?>
        <!-- Layout Masonry -->
        <div class="flavor-gallery-masonry columns-<?php echo $columns; ?> <?php echo esc_attr($gap_class); ?>">
            <?php foreach ($visible_images as $index => $image): ?>
                <div class="mb-2 break-inside-avoid">
                    <div class="relative rounded-lg overflow-hidden group">
                        <img src="<?php echo esc_url($image['thumb'] ?? $image['src'] ?? ''); ?>"
                             alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                             class="w-full h-auto <?php if ($lightbox): ?>cursor-pointer<?php endif; ?>"
                             loading="lazy"
                             <?php if ($lightbox): ?>data-lightbox="<?php echo esc_attr($gallery_id); ?>" data-index="<?php echo $index; ?>" data-src="<?php echo esc_url($image['src'] ?? ''); ?>"<?php endif; ?>>
                        <?php if ($show_captions && !empty($image['caption'])): ?>
                            <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent text-white text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php echo esc_html($image['caption']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Layout Grid (default) -->
        <div class="flavor-gallery-grid grid <?php echo esc_attr($col_class); ?> <?php echo esc_attr($gap_class); ?>">
            <?php foreach ($visible_images as $index => $image): ?>
                <div class="relative rounded-lg overflow-hidden group <?php echo esc_attr($aspect_class); ?> <?php echo !$aspect_class ? '' : 'bg-gray-100'; ?>">
                    <img src="<?php echo esc_url($image['thumb'] ?? $image['src'] ?? ''); ?>"
                         alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                         class="<?php echo $aspect_class ? 'absolute inset-0 w-full h-full object-cover' : 'w-full h-auto'; ?> <?php if ($lightbox): ?>cursor-pointer<?php endif; ?> group-hover:scale-105 transition-transform duration-300"
                         loading="lazy"
                         <?php if ($lightbox): ?>data-lightbox="<?php echo esc_attr($gallery_id); ?>" data-index="<?php echo $index; ?>" data-src="<?php echo esc_url($image['src'] ?? ''); ?>"<?php endif; ?>>

                    <?php if ($show_captions && !empty($image['caption'])): ?>
                        <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent text-white text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                            <?php echo esc_html($image['caption']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Overlay para +X más -->
                    <?php if ($remaining > 0 && $index === count($visible_images) - 1): ?>
                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center cursor-pointer show-all-images">
                            <span class="text-white text-2xl font-bold">+<?php echo $remaining; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<?php if ($lightbox): ?>
<div id="<?php echo esc_attr($gallery_id); ?>-lightbox" class="fixed inset-0 z-50 hidden bg-black/95 flex items-center justify-center">
    <!-- Close button -->
    <button type="button" class="lightbox-close absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors z-10">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Nav buttons -->
    <button type="button" class="lightbox-prev absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    <button type="button" class="lightbox-next absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    <!-- Image -->
    <div class="lightbox-content max-w-[90vw] max-h-[90vh] flex items-center justify-center">
        <img src="" alt="" class="max-w-full max-h-[85vh] object-contain">
    </div>

    <!-- Caption & Counter -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-center text-white">
        <p class="lightbox-caption text-sm mb-1"></p>
        <p class="lightbox-counter text-xs text-white/60">
            <span class="current">1</span> / <span class="total"><?php echo count($images); ?></span>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gallery = document.getElementById('<?php echo esc_js($gallery_id); ?>');
    const lightbox = document.getElementById('<?php echo esc_js($gallery_id); ?>-lightbox');
    if (!gallery || !lightbox) return;

    const images = <?php echo json_encode(array_map(function($img) {
        return [
            'src' => $img['src'] ?? $img['thumb'] ?? '',
            'alt' => $img['alt'] ?? '',
            'caption' => $img['caption'] ?? ''
        ];
    }, $images)); ?>;

    let currentIndex = 0;

    const lightboxImg = lightbox.querySelector('.lightbox-content img');
    const lightboxCaption = lightbox.querySelector('.lightbox-caption');
    const lightboxCurrent = lightbox.querySelector('.lightbox-counter .current');

    function showImage(index) {
        currentIndex = (index + images.length) % images.length;
        const img = images[currentIndex];

        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightboxCaption.textContent = img.caption;
        lightboxCurrent.textContent = currentIndex + 1;
    }

    function openLightbox(index) {
        showImage(index);
        lightbox.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Click en imagen
    gallery.querySelectorAll('[data-lightbox]').forEach(img => {
        img.addEventListener('click', function() {
            openLightbox(parseInt(this.dataset.index));
        });
    });

    // Ver todas (cuando hay límite)
    gallery.querySelectorAll('.show-all-images').forEach(el => {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            openLightbox(parseInt(this.closest('[data-index]')?.dataset.index || 0));
        });
    });

    // Controles lightbox
    lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
    lightbox.querySelector('.lightbox-prev').addEventListener('click', () => showImage(currentIndex - 1));
    lightbox.querySelector('.lightbox-next').addEventListener('click', () => showImage(currentIndex + 1));

    // Teclas
    document.addEventListener('keydown', function(e) {
        if (lightbox.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showImage(currentIndex - 1);
        if (e.key === 'ArrowRight') showImage(currentIndex + 1);
    });

    // Click fuera
    lightbox.addEventListener('click', function(e) {
        if (e.target === this) closeLightbox();
    });

    // Carousel controls (si aplica)
    const carousel = gallery.querySelector('.flavor-gallery-carousel');
    if (carousel) {
        const track = carousel.querySelector('.flex');
        const dots = carousel.querySelectorAll('.carousel-dots button');
        let carouselIndex = 0;

        function goToSlide(index) {
            carouselIndex = (index + images.length) % images.length;
            track.style.transform = `translateX(-${carouselIndex * 100}%)`;
            dots.forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === carouselIndex);
                dot.classList.toggle('bg-white/50', i !== carouselIndex);
            });
        }

        carousel.querySelector('.carousel-prev')?.addEventListener('click', () => goToSlide(carouselIndex - 1));
        carousel.querySelector('.carousel-next')?.addEventListener('click', () => goToSlide(carouselIndex + 1));
        dots.forEach(dot => {
            dot.addEventListener('click', () => goToSlide(parseInt(dot.dataset.index)));
        });
    }

    // Stack controls (si aplica)
    const stackMain = gallery.querySelector('.stack-main img');
    const stackThumbs = gallery.querySelectorAll('.stack-thumb');

    if (stackMain && stackThumbs.length > 0) {
        stackThumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                stackMain.src = images[index].src;
                stackThumbs.forEach((t, i) => {
                    t.classList.toggle('ring-2', i === index);
                    t.classList.toggle('ring-blue-500', i === index);
                    t.classList.toggle('opacity-70', i !== index);
                });
            });
        });
    }
});
</script>
<?php endif; ?>
