<?php
/**
 * Template: Portfolio / Casos de Éxito
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Nuestros Casos de Éxito';
$descripcion_seccion = $descripcion_seccion ?? 'Proyectos que transformaron negocios';
$layout = $layout ?? 'masonry';
$columnas = $columnas ?? '3';
$mostrar_filtros = $mostrar_filtros ?? true;

// Usar items del repeater o fallback a datos de ejemplo
$proyectos_mostrar = $items ?? [];

// Asegurar estructura consistente para cada proyecto
foreach ($proyectos_mostrar as &$proyecto_item) {
    $proyecto_item['categoria'] = $proyecto_item['categoria'] ?? 'general';
    $proyecto_item['imagen'] = $proyecto_item['imagen'] ?? '';
    $proyecto_item['resultados'] = $proyecto_item['resultados'] ?? '';
    $proyecto_item['etiquetas'] = $proyecto_item['etiquetas'] ?? [];
    $proyecto_item['cliente'] = $proyecto_item['cliente'] ?? '';

    // Si la imagen es un ID de attachment, obtener la URL
    if (!empty($proyecto_item['imagen']) && is_numeric($proyecto_item['imagen'])) {
        $imagen_url = wp_get_attachment_image_url($proyecto_item['imagen'], 'medium_large');
        $proyecto_item['imagen'] = $imagen_url ?: '';
    }
}
unset($proyecto_item);

// Fallback: si no hay items configurados, mostrar ejemplo minimo
if (empty($proyectos_mostrar)) {
    $proyectos_mostrar = [
        [
            'titulo' => 'Proyecto de ejemplo 1',
            'cliente' => 'Cliente ejemplo',
            'categoria' => 'general',
            'imagen' => 'https://picsum.photos/600/400?random=1',
            'descripcion' => 'Edita este componente para añadir tus proyectos reales.',
            'resultados' => 'Resultado ejemplo',
            'etiquetas' => ['Ejemplo'],
        ],
        [
            'titulo' => 'Proyecto de ejemplo 2',
            'cliente' => 'Otro cliente',
            'categoria' => 'general',
            'imagen' => 'https://picsum.photos/600/450?random=2',
            'descripcion' => 'Añade más proyectos desde el editor del componente.',
            'resultados' => 'Resultado ejemplo',
            'etiquetas' => ['Ejemplo'],
        ],
    ];
}

// Categorías únicas para filtros
$categorias = array_unique(array_column($proyectos_mostrar, 'categoria'));

// Clases de columnas
$grid_columnas = [
    '2' => 'md:grid-cols-2',
    '3' => 'md:grid-cols-2 lg:grid-cols-3',
    '4' => 'md:grid-cols-2 lg:grid-cols-4'
];

$clase_columnas = $grid_columnas[$columnas] ?? $grid_columnas['3'];
?>

<section class="flavor-component flavor-section" style="background: var(--flavor-background, #ffffff);">
    <div class="flavor-container">
        <!-- Encabezado de sección -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <?php if (!empty($descripcion_seccion)): ?>
                <p class="text-xl" style="color: var(--flavor-text-secondary, #666666);">
                    <?php echo esc_html($descripcion_seccion); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($mostrar_filtros && count($categorias) > 1): ?>
            <!-- Filtros de categoría -->
            <div class="flex flex-wrap justify-center gap-4 mb-12" id="portfolio-filters">
                <button class="portfolio-filter active px-6 py-3 rounded-full font-semibold transition-all duration-300 hover:transform hover:scale-105"
                        data-filter="all"
                        style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                    Todos
                </button>
                <?php foreach ($categorias as $categoria): ?>
                    <button class="portfolio-filter px-6 py-3 rounded-full font-semibold transition-all duration-300 hover:transform hover:scale-105"
                            data-filter="<?php echo esc_attr($categoria); ?>"
                            style="background: rgba(0,0,0,0.05); color: var(--flavor-text-primary, #1a1a1a);">
                        <?php echo esc_html(ucfirst($categoria)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($layout === 'masonry'): ?>
            <!-- Layout Masonry -->
            <div class="columns-1 <?php echo esc_attr($clase_columnas); ?> gap-8" id="portfolio-grid">
                <?php foreach ($proyectos_mostrar as $proyecto): ?>
                    <div class="portfolio-item break-inside-avoid mb-8" data-category="<?php echo esc_attr($proyecto['categoria']); ?>">
                        <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300">
                            <!-- Imagen -->
                            <div class="relative overflow-hidden">
                                <img src="<?php echo esc_url($proyecto['imagen']); ?>"
                                     alt="<?php echo esc_attr($proyecto['titulo']); ?>"
                                     class="w-full h-auto object-cover transition-transform duration-500 group-hover:scale-110">

                                <!-- Overlay -->
                                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center"
                                     style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);">
                                    <a href="#" class="px-6 py-3 bg-white rounded-full font-semibold transition-transform duration-300 hover:scale-110"
                                       style="color: var(--flavor-primary, #667eea);">
                                        Ver Proyecto
                                    </a>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="p-6">
                                <!-- Resultado destacado -->
                                <div class="inline-block px-4 py-2 rounded-full text-sm font-bold text-white mb-4"
                                     style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                    <?php echo esc_html($proyecto['resultados']); ?>
                                </div>

                                <h3 class="text-2xl font-bold mb-2 transition-colors duration-300"
                                    style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html($proyecto['titulo']); ?>
                                </h3>

                                <p class="text-sm font-semibold mb-3" style="color: var(--flavor-primary, #667eea);">
                                    <?php echo esc_html($proyecto['cliente']); ?>
                                </p>

                                <p class="mb-4" style="color: var(--flavor-text-secondary, #666666);">
                                    <?php echo esc_html($proyecto['descripcion']); ?>
                                </p>

                                <!-- Etiquetas -->
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($proyecto['etiquetas'] as $etiqueta): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                              style="background: rgba(102, 126, 234, 0.1); color: var(--flavor-primary, #667eea);">
                                            <?php echo esc_html($etiqueta); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($layout === 'carousel'): ?>
            <!-- Layout Carousel - simplified as horizontal scroll -->
            <div class="overflow-x-auto pb-8 -mx-4 px-4" id="portfolio-grid">
                <div class="flex gap-8 min-w-min">
                    <?php foreach ($proyectos_mostrar as $proyecto): ?>
                        <div class="portfolio-item" data-category="<?php echo esc_attr($proyecto['categoria']); ?>" style="min-width: 400px;">
                            <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 h-full">
                                <!-- Imagen -->
                                <div class="relative overflow-hidden h-64">
                                    <img src="<?php echo esc_url($proyecto['imagen']); ?>"
                                         alt="<?php echo esc_attr($proyecto['titulo']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                </div>

                                <!-- Contenido -->
                                <div class="p-6">
                                    <div class="inline-block px-4 py-2 rounded-full text-sm font-bold text-white mb-4"
                                         style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                        <?php echo esc_html($proyecto['resultados']); ?>
                                    </div>

                                    <h3 class="text-xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html($proyecto['titulo']); ?>
                                    </h3>

                                    <p class="text-sm font-semibold mb-3" style="color: var(--flavor-primary, #667eea);">
                                        <?php echo esc_html($proyecto['cliente']); ?>
                                    </p>

                                    <p class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                        <?php echo esc_html($proyecto['descripcion']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: // grid ?>
            <!-- Layout Grid -->
            <div class="grid grid-cols-1 <?php echo esc_attr($clase_columnas); ?> gap-8" id="portfolio-grid">
                <?php foreach ($proyectos_mostrar as $proyecto): ?>
                    <div class="portfolio-item" data-category="<?php echo esc_attr($proyecto['categoria']); ?>">
                        <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 h-full">
                            <!-- Imagen -->
                            <div class="relative overflow-hidden h-64">
                                <img src="<?php echo esc_url($proyecto['imagen']); ?>"
                                     alt="<?php echo esc_attr($proyecto['titulo']); ?>"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">

                                <!-- Overlay -->
                                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center"
                                     style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);">
                                    <a href="#" class="px-6 py-3 bg-white rounded-full font-semibold transition-transform duration-300 hover:scale-110"
                                       style="color: var(--flavor-primary, #667eea);">
                                        Ver Proyecto
                                    </a>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="p-6">
                                <div class="inline-block px-4 py-2 rounded-full text-sm font-bold text-white mb-4"
                                     style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                    <?php echo esc_html($proyecto['resultados']); ?>
                                </div>

                                <h3 class="text-2xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html($proyecto['titulo']); ?>
                                </h3>

                                <p class="text-sm font-semibold mb-3" style="color: var(--flavor-primary, #667eea);">
                                    <?php echo esc_html($proyecto['cliente']); ?>
                                </p>

                                <p class="mb-4" style="color: var(--flavor-text-secondary, #666666);">
                                    <?php echo esc_html($proyecto['descripcion']); ?>
                                </p>

                                <!-- Etiquetas -->
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($proyecto['etiquetas'] as $etiqueta): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                              style="background: rgba(102, 126, 234, 0.1); color: var(--flavor-primary, #667eea);">
                                            <?php echo esc_html($etiqueta); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Call to action -->
        <div class="text-center mt-16">
            <p class="text-2xl font-semibold mb-6" style="color: var(--flavor-text-primary, #1a1a1a);">
                ¿Listo para ser nuestro próximo caso de éxito?
            </p>
            <a href="#contacto"
               class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl"
               style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                <span>Hablemos de Tu Proyecto</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<?php if ($mostrar_filtros && count($categorias) > 1): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.portfolio-filter');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterValue = this.dataset.filter;

            // Actualizar botones activos
            filterButtons.forEach(btn => {
                if (btn === this) {
                    btn.classList.add('active');
                    btn.style.background = 'linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%)';
                    btn.style.color = 'white';
                } else {
                    btn.classList.remove('active');
                    btn.style.background = 'rgba(0,0,0,0.05)';
                    btn.style.color = 'var(--flavor-text-primary, #1a1a1a)';
                }
            });

            // Filtrar proyectos
            portfolioItems.forEach(item => {
                if (filterValue === 'all' || item.dataset.category === filterValue) {
                    item.style.display = 'block';
                    item.style.animation = 'fadeIn 0.5s ease-in';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>
<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
<?php endif; ?>
