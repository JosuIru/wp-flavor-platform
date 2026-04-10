<?php
/**
 * Componente: Pagination
 *
 * Paginación reutilizable con estilos consistentes.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param int    $total       Total de items
 * @param int    $per_page    Items por página (default: 12)
 * @param int    $current     Página actual (default: 1)
 * @param string $color       Color del botón activo (red, green, blue, etc.)
 * @param string $base_url    URL base para los enlaces (opcional, usa JS si no se proporciona)
 * @param string $param_name  Nombre del parámetro de página en URL (default: 'paged')
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_color_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Valores por defecto
$total = $total ?? 0;
$per_page = $per_page ?? 12;
$current = $current ?? 1;
$color = $color ?? 'blue';
$base_url = $base_url ?? '';
$param_name = $param_name ?? 'paged';

// Calcular total de páginas
$total_pages = ceil($total / $per_page);

// No mostrar si solo hay una página
if ($total_pages <= 1) {
    return;
}

$color_classes = flavor_get_color_classes($color);
$pagination_id = flavor_unique_id('pagination');

/**
 * Genera la URL para una página específica
 */
$get_page_url = function($page) use ($base_url, $param_name) {
    if (empty($base_url)) {
        return '#';
    }

    $separator = strpos($base_url, '?') !== false ? '&' : '?';
    return $base_url . $separator . $param_name . '=' . $page;
};

$has_prev = $current > 1;
$has_next = $current < $total_pages;
?>

<div id="<?php echo esc_attr($pagination_id); ?>" class="flex justify-center mt-8">
    <nav class="flex items-center gap-2" role="navigation" aria-label="<?php esc_attr_e('Paginación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <!-- Botón Anterior -->
        <?php if ($base_url): ?>
        <a href="<?php echo $has_prev ? esc_url($get_page_url($current - 1)) : '#'; ?>"
           class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors <?php echo !$has_prev ? 'opacity-50 pointer-events-none' : ''; ?>"
           <?php if (!$has_prev): ?>aria-disabled="true"<?php endif; ?>
           aria-label="<?php esc_attr_e('Página anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php echo esc_html__('← Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php else: ?>
        <button type="button"
                class="pagination-prev px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors <?php echo !$has_prev ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php if (!$has_prev): ?>disabled<?php endif; ?>
                aria-label="<?php esc_attr_e('Página anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php echo esc_html__('← Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php endif; ?>

        <!-- Indicador de página -->
        <span class="px-4 py-2 text-gray-600" aria-live="polite">
            <?php
            printf(
                esc_html__('Página %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $current,
                $total_pages
            );
            ?>
        </span>

        <!-- Botón Siguiente -->
        <?php if ($base_url): ?>
        <a href="<?php echo $has_next ? esc_url($get_page_url($current + 1)) : '#'; ?>"
           class="px-4 py-2 rounded-lg <?php echo esc_attr($color_classes['bg_solid']); ?> text-white hover:opacity-90 transition-opacity <?php echo !$has_next ? 'opacity-50 pointer-events-none' : ''; ?>"
           <?php if (!$has_next): ?>aria-disabled="true"<?php endif; ?>
           aria-label="<?php esc_attr_e('Página siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php echo esc_html__('Siguiente →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php else: ?>
        <button type="button"
                class="pagination-next px-4 py-2 rounded-lg <?php echo esc_attr($color_classes['bg_solid']); ?> text-white hover:opacity-90 transition-opacity <?php echo !$has_next ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php if (!$has_next): ?>disabled<?php endif; ?>
                aria-label="<?php esc_attr_e('Página siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php echo esc_html__('Siguiente →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php endif; ?>
    </nav>
</div>

<?php if (empty($base_url)): ?>
<script>
(function() {
    const container = document.getElementById('<?php echo esc_js($pagination_id); ?>');
    if (!container) return;

    const prevBtn = container.querySelector('.pagination-prev');
    const nextBtn = container.querySelector('.pagination-next');

    // Emitir eventos para manejo JS externo
    if (prevBtn && !prevBtn.disabled) {
        prevBtn.addEventListener('click', function() {
            container.dispatchEvent(new CustomEvent('pagination:prev', {
                detail: { current: <?php echo (int) $current; ?> - 1 },
                bubbles: true
            }));
        });
    }

    if (nextBtn && !nextBtn.disabled) {
        nextBtn.addEventListener('click', function() {
            container.dispatchEvent(new CustomEvent('pagination:next', {
                detail: { current: <?php echo (int) $current; ?> + 1 },
                bubbles: true
            }));
        });
    }
})();
</script>
<?php endif; ?>
