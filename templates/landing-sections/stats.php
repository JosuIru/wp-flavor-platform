<?php
/**
 * Template de sección Stats (Estadísticas)
 *
 * @package FlavorPlatform
 * @var array $data Datos de la sección
 * @var string $variant Variante de la sección
 * @var string $section_id ID de la sección
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer datos
$titulo = $data['titulo'] ?? '';
$items = $data['items'] ?? [];
$color_fondo = $data['color_fondo'] ?? '#1e293b';
$color_texto = $data['color_texto'] ?? '#ffffff';
?>

<div class="flavor-stats flavor-stats--<?php echo esc_attr($variant); ?>">
    <?php if ($titulo): ?>
        <div class="flavor-section__header">
            <h2 class="flavor-section__title"><?php echo esc_html($titulo); ?></h2>
        </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
        <div class="flavor-stats__grid">
            <?php foreach ($items as $item): ?>
                <div class="flavor-stat-card">
                    <?php if (!empty($item['icono'])): ?>
                        <div class="flavor-stat-card__icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($item['icono']); ?>"></span>
                        </div>
                    <?php endif; ?>

                    <div class="flavor-stat-card__number">
                        <span
                            class="flavor-stat-card__value"
                            data-value="<?php echo esc_attr($item['numero'] ?? '0'); ?>"
                        >
                            <?php echo esc_html($item['numero'] ?? '0'); ?>
                        </span>
                        <?php if (!empty($item['sufijo'])): ?>
                            <span class="flavor-stat-card__suffix"><?php echo esc_html($item['sufijo']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($item['etiqueta'])): ?>
                        <div class="flavor-stat-card__label"><?php echo esc_html($item['etiqueta']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($variant === 'animado'): ?>
<script>
(function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var counters = entry.target.querySelectorAll('.flavor-stat-card__value');
                counters.forEach(function(counter) {
                    var target = parseInt(counter.getAttribute('data-value'), 10);
                    if (isNaN(target)) return;

                    var duration = 2000;
                    var start = 0;
                    var startTime = null;

                    function animate(currentTime) {
                        if (!startTime) startTime = currentTime;
                        var progress = Math.min((currentTime - startTime) / duration, 1);
                        var easeProgress = 1 - Math.pow(1 - progress, 3);
                        counter.textContent = Math.floor(easeProgress * target);

                        if (progress < 1) {
                            requestAnimationFrame(animate);
                        } else {
                            counter.textContent = target;
                        }
                    }

                    requestAnimationFrame(animate);
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    var section = document.getElementById('<?php echo esc_js($section_id); ?>');
    if (section) {
        observer.observe(section);
    }
})();
</script>
<?php endif; ?>
