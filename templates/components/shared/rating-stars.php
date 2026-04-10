<?php
/**
 * Componente: Rating Stars
 *
 * Sistema de valoración con estrellas interactivo o de solo lectura.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param float  $rating      Valoración actual (0-5)
 * @param int    $total       Total de valoraciones
 * @param bool   $interactive Permite votar (requiere login)
 * @param string $item_type   Tipo de item (producto, servicio, usuario, etc.)
 * @param int    $item_id     ID del item a valorar
 * @param string $size        Tamaño: sm, md, lg
 * @param bool   $show_count  Mostrar número de valoraciones
 * @param bool   $show_average Mostrar promedio numérico
 * @param string $color       Color de las estrellas activas
 */

if (!defined('ABSPATH')) {
    exit;
}

$rating = floatval($rating ?? 0);
$total = intval($total ?? 0);
$interactive = $interactive ?? false;
$item_type = $item_type ?? 'item';
$item_id = intval($item_id ?? 0);
$size = $size ?? 'md';
$show_count = $show_count ?? true;
$show_average = $show_average ?? true;
$color = $color ?? 'yellow';

// Tamaños
$sizes = [
    'sm' => 'text-sm',
    'md' => 'text-lg',
    'lg' => 'text-2xl',
];
$size_class = $sizes[$size] ?? $sizes['md'];

// Colores
$color_classes = [
    'yellow' => 'text-yellow-400',
    'orange' => 'text-orange-400',
    'red'    => 'text-red-400',
    'blue'   => 'text-blue-400',
    'green'  => 'text-green-400',
];
$star_color = $color_classes[$color] ?? $color_classes['yellow'];

// ID único para el componente
$component_id = 'rating-' . ($item_id ?: wp_rand(1000, 9999));

// Verificar si el usuario ya votó
$user_rating = 0;
if ($interactive && is_user_logged_in() && $item_id) {
    $user_rating = intval(get_user_meta(get_current_user_id(), "_rating_{$item_type}_{$item_id}", true));
}
?>

<div class="flavor-rating-stars inline-flex items-center gap-2"
     id="<?php echo esc_attr($component_id); ?>"
     data-item-type="<?php echo esc_attr($item_type); ?>"
     data-item-id="<?php echo esc_attr($item_id); ?>"
     data-current-rating="<?php echo esc_attr($rating); ?>"
     data-user-rating="<?php echo esc_attr($user_rating); ?>">

    <!-- Estrellas -->
    <div class="flavor-stars-container flex items-center gap-0.5 <?php echo esc_attr($size_class); ?>"
         role="<?php echo $interactive ? 'radiogroup' : 'img'; ?>"
         aria-label="<?php printf(esc_attr__('Valoración: %s de 5 estrellas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($rating, 1)); ?>">

        <?php for ($i = 1; $i <= 5; $i++):
            $fill_percent = 0;
            if ($rating >= $i) {
                $fill_percent = 100;
            } elseif ($rating > $i - 1) {
                $fill_percent = ($rating - ($i - 1)) * 100;
            }

            $is_active = $user_rating >= $i;
        ?>
            <?php if ($interactive && is_user_logged_in()): ?>
                <button type="button"
                        class="flavor-star-btn relative cursor-pointer transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-yellow-300 rounded <?php echo $is_active ? 'scale-110' : ''; ?>"
                        data-value="<?php echo $i; ?>"
                        aria-label="<?php printf(esc_attr__('%d estrellas', FLAVOR_PLATFORM_TEXT_DOMAIN), $i); ?>">
            <?php else: ?>
                <span class="flavor-star relative">
            <?php endif; ?>

                <!-- Estrella vacía (fondo) -->
                <svg class="w-[1em] h-[1em] text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>

                <!-- Estrella llena (overlay con clip) -->
                <div class="absolute inset-0 overflow-hidden" style="width: <?php echo $fill_percent; ?>%;">
                    <svg class="w-[1em] h-[1em] <?php echo esc_attr($star_color); ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>

            <?php if ($interactive && is_user_logged_in()): ?>
                </button>
            <?php else: ?>
                </span>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <!-- Info adicional -->
    <div class="flavor-rating-info flex items-center gap-1.5 text-sm text-gray-500">
        <?php if ($show_average && $rating > 0): ?>
            <span class="font-medium text-gray-700"><?php echo number_format($rating, 1); ?></span>
        <?php endif; ?>

        <?php if ($show_count && $total > 0): ?>
            <span class="text-gray-400">(<?php echo number_format_i18n($total); ?>)</span>
        <?php endif; ?>
    </div>

    <!-- Mensaje de feedback -->
    <?php if ($interactive): ?>
        <div class="flavor-rating-feedback hidden ml-2 text-sm text-green-600 animate-fade-in">
            <?php esc_html_e('¡Gracias por tu valoración!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($interactive && is_user_logged_in()): ?>
<script>
(function() {
    const container = document.getElementById('<?php echo esc_js($component_id); ?>');
    if (!container) return;

    const buttons = container.querySelectorAll('.flavor-star-btn');
    const feedback = container.querySelector('.flavor-rating-feedback');

    buttons.forEach(btn => {
        // Hover effect
        btn.addEventListener('mouseenter', function() {
            const value = parseInt(this.dataset.value);
            buttons.forEach((b, i) => {
                const star = b.querySelector('svg:last-child');
                if (i < value) {
                    b.querySelector('.absolute').style.width = '100%';
                } else {
                    b.querySelector('.absolute').style.width = '0%';
                }
            });
        });

        // Click to rate
        btn.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            const itemType = container.dataset.itemType;
            const itemId = container.dataset.itemId;

            // Enviar valoración via AJAX
            fetch(flavorAjax.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'flavor_submit_rating',
                    item_type: itemType,
                    item_id: itemId,
                    rating: value,
                    _wpnonce: flavorAjax.nonce
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    container.dataset.userRating = value;
                    feedback.classList.remove('hidden');
                    setTimeout(() => feedback.classList.add('hidden'), 3000);
                }
            });
        });
    });

    // Reset on mouse leave
    container.querySelector('.flavor-stars-container').addEventListener('mouseleave', function() {
        const currentRating = parseFloat(container.dataset.currentRating);
        buttons.forEach((btn, i) => {
            const fillPercent = currentRating >= (i + 1) ? 100 :
                               (currentRating > i ? (currentRating - i) * 100 : 0);
            btn.querySelector('.absolute').style.width = fillPercent + '%';
        });
    });
})();
</script>
<?php endif; ?>
