<?php
/**
 * Componente: Category Pills
 *
 * Pills/chips de categorías con iconos y contadores.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $categories  Array de categorías: [['id' => '', 'name' => '', 'icon' => '', 'count' => 0, 'color' => '', 'active' => false]]
 * @param string $active      ID de la categoría activa
 * @param bool   $show_count  Mostrar contador
 * @param bool   $show_all    Mostrar opción "Todas"
 * @param string $all_label   Label para "Todas"
 * @param int    $all_count   Contador total para "Todas"
 * @param string $size        Tamaño: sm, md, lg
 * @param bool   $scrollable  Scroll horizontal en móvil
 * @param string $variant     Variante: pills, buttons, underline
 * @param string $filter_key  Nombre del parámetro URL para filtrar
 * @param bool   $ajax        Usar AJAX en lugar de navegar
 * @param string $target      ID del contenedor a actualizar via AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

$categories = $categories ?? [];
$active = $active ?? '';
$show_count = $show_count ?? true;
$show_all = $show_all ?? true;
$all_label = $all_label ?? __('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN);
$all_count = $all_count ?? 0;
$size = $size ?? 'md';
$scrollable = $scrollable ?? true;
$variant = $variant ?? 'pills';
$filter_key = $filter_key ?? 'categoria';
$ajax = $ajax ?? false;
$target = $target ?? '';

// Si no hay categoría activa, "Todas" está activa
if (empty($active) && $show_all) {
    $active = 'all';
}

// Calcular total si no se proporciona
if ($all_count === 0 && $show_all) {
    foreach ($categories as $cat) {
        $all_count += $cat['count'] ?? 0;
    }
}

// Clases de tamaño
$size_classes = [
    'sm' => 'px-2.5 py-1 text-xs gap-1',
    'md' => 'px-3 py-1.5 text-sm gap-1.5',
    'lg' => 'px-4 py-2 text-base gap-2',
];
$size_class = $size_classes[$size] ?? $size_classes['md'];

// Clases base según variante
$variant_base = [
    'pills'     => 'rounded-full border transition-all duration-200',
    'buttons'   => 'rounded-lg border transition-all duration-200',
    'underline' => 'pb-2 border-b-2 transition-all duration-200',
];
$base_class = $variant_base[$variant] ?? $variant_base['pills'];

// Clases para estado activo/inactivo
$state_classes = [
    'pills' => [
        'active'   => 'bg-blue-600 border-blue-600 text-white shadow-sm',
        'inactive' => 'bg-white border-gray-200 text-gray-700 hover:border-blue-300 hover:bg-blue-50',
    ],
    'buttons' => [
        'active'   => 'bg-blue-600 border-blue-600 text-white shadow-sm',
        'inactive' => 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100',
    ],
    'underline' => [
        'active'   => 'border-blue-600 text-blue-600 font-medium',
        'inactive' => 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
    ],
];

// Construir URL base
$current_url = remove_query_arg($filter_key);
?>

<div class="flavor-category-pills <?php echo $scrollable ? 'overflow-x-auto' : ''; ?>"
     <?php if ($ajax && $target): ?>
     data-ajax="true"
     data-target="<?php echo esc_attr($target); ?>"
     <?php endif; ?>>

    <div class="flex <?php echo $scrollable ? 'flex-nowrap pb-2' : 'flex-wrap'; ?> gap-2 <?php echo $variant === 'underline' ? '' : ''; ?>">

        <?php if ($show_all): ?>
            <?php
            $is_active = ($active === 'all' || empty($active));
            $state = $is_active ? 'active' : 'inactive';
            $url = remove_query_arg($filter_key);
            ?>
            <a href="<?php echo esc_url($url); ?>"
               class="flavor-cat-pill inline-flex items-center whitespace-nowrap <?php echo esc_attr($size_class); ?> <?php echo esc_attr($base_class); ?> <?php echo esc_attr($state_classes[$variant][$state]); ?>"
               data-category="all"
               <?php if ($is_active): ?>aria-current="true"<?php endif; ?>>
                <span class="cat-icon"><?php echo $variant === 'underline' ? '' : '📋'; ?></span>
                <span class="cat-name"><?php echo esc_html($all_label); ?></span>
                <?php if ($show_count): ?>
                    <span class="cat-count <?php echo $is_active ? 'bg-white/20' : 'bg-gray-100'; ?> px-1.5 py-0.5 rounded-full text-xs ml-1">
                        <?php echo number_format_i18n($all_count); ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php foreach ($categories as $cat): ?>
            <?php
            $cat_id = $cat['id'] ?? sanitize_title($cat['name']);
            $cat_name = $cat['name'] ?? '';
            $cat_icon = $cat['icon'] ?? '';
            $cat_count = $cat['count'] ?? 0;
            $cat_color = $cat['color'] ?? '';
            $is_active = ($active === $cat_id);
            $state = $is_active ? 'active' : 'inactive';
            $url = add_query_arg($filter_key, $cat_id);

            // Color personalizado
            $custom_style = '';
            if ($cat_color && $is_active) {
                $custom_style = "background-color: {$cat_color}; border-color: {$cat_color};";
            }
            ?>
            <a href="<?php echo esc_url($url); ?>"
               class="flavor-cat-pill inline-flex items-center whitespace-nowrap <?php echo esc_attr($size_class); ?> <?php echo esc_attr($base_class); ?> <?php echo esc_attr($state_classes[$variant][$state]); ?>"
               data-category="<?php echo esc_attr($cat_id); ?>"
               <?php if ($custom_style): ?>style="<?php echo esc_attr($custom_style); ?>"<?php endif; ?>
               <?php if ($is_active): ?>aria-current="true"<?php endif; ?>>
                <?php if ($cat_icon): ?>
                    <span class="cat-icon"><?php echo esc_html($cat_icon); ?></span>
                <?php endif; ?>
                <span class="cat-name"><?php echo esc_html($cat_name); ?></span>
                <?php if ($show_count): ?>
                    <span class="cat-count <?php echo $is_active ? 'bg-white/20' : 'bg-gray-100'; ?> px-1.5 py-0.5 rounded-full text-xs ml-1">
                        <?php echo number_format_i18n($cat_count); ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($ajax && $target): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.flavor-category-pills[data-ajax="true"]');
    if (!container) return;

    const targetEl = document.getElementById(container.dataset.target);
    if (!targetEl) return;

    container.querySelectorAll('.flavor-cat-pill').forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.preventDefault();

            const category = this.dataset.category;
            const url = this.href;

            // Actualizar estado visual
            container.querySelectorAll('.flavor-cat-pill').forEach(p => {
                const isActive = p.dataset.category === category;
                const variant = '<?php echo esc_js($variant); ?>';

                // Remover clases activas
                p.classList.remove('bg-blue-600', 'border-blue-600', 'text-white', 'shadow-sm');
                p.classList.remove('border-blue-600', 'text-blue-600', 'font-medium');

                if (isActive) {
                    if (variant === 'underline') {
                        p.classList.add('border-blue-600', 'text-blue-600', 'font-medium');
                    } else {
                        p.classList.add('bg-blue-600', 'border-blue-600', 'text-white', 'shadow-sm');
                    }
                    p.setAttribute('aria-current', 'true');
                } else {
                    if (variant === 'underline') {
                        p.classList.add('border-transparent', 'text-gray-500');
                    } else {
                        p.classList.add('bg-white', 'border-gray-200', 'text-gray-700');
                    }
                    p.removeAttribute('aria-current');
                }
            });

            // Mostrar loading
            targetEl.style.opacity = '0.5';

            // Cargar contenido via AJAX
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.text())
            .then(html => {
                // Parsear y extraer solo el contenido del target
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById(container.dataset.target);

                if (newContent) {
                    targetEl.innerHTML = newContent.innerHTML;
                }
                targetEl.style.opacity = '1';

                // Actualizar URL sin recargar
                history.pushState({}, '', url);
            })
            .catch(() => {
                targetEl.style.opacity = '1';
                window.location.href = url; // Fallback a navegación normal
            });
        });
    });
});
</script>
<?php endif; ?>
