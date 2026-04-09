<?php
/**
 * Template: Tabla de Precios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Planes y Precios';
$descripcion_seccion = $descripcion_seccion ?? 'Elige el plan perfecto para tu negocio';
$periodo = $periodo ?? 'mensual';

// Usar items del repeater o fallback a datos de ejemplo
$planes_mostrar = $items ?? [];

// Procesar items del repeater: convertir caracteristicas de texto a array
foreach ($planes_mostrar as &$plan_item) {
    if (!empty($plan_item['caracteristicas']) && is_string($plan_item['caracteristicas'])) {
        $plan_item['caracteristicas'] = array_filter(array_map('trim', explode("\n", $plan_item['caracteristicas'])));
    }
    if (empty($plan_item['caracteristicas'])) {
        $plan_item['caracteristicas'] = [];
    }
}
unset($plan_item);

// Fallback: si no hay items configurados, mostrar ejemplo minimo
if (empty($planes_mostrar)) {
    $planes_mostrar = [
        [
            'nombre' => 'Starter',
            'descripcion' => 'Perfecto para emprendedores',
            'precio_mensual' => '29',
            'precio_anual' => '290',
            'caracteristicas' => ['Hasta 5 usuarios', '10 GB almacenamiento', 'Soporte por email'],
            'destacar' => false,
        ],
        [
            'nombre' => 'Professional',
            'descripcion' => 'Ideal para empresas',
            'precio_mensual' => '79',
            'precio_anual' => '790',
            'caracteristicas' => ['Usuarios ilimitados', '100 GB almacenamiento', 'Soporte 24/7', 'API completa'],
            'destacar' => true,
            'badge' => 'Más Popular',
        ],
        [
            'nombre' => 'Enterprise',
            'descripcion' => 'Para grandes organizaciones',
            'precio_mensual' => '199',
            'precio_anual' => '1990',
            'caracteristicas' => ['Todo ilimitado', 'Soporte dedicado', 'SLA garantizado'],
            'destacar' => false,
        ],
    ];
}

$numero_planes = count($planes_mostrar);

// Clases de grid según número de planes
$grid_clases = [
    '2' => 'md:grid-cols-2',
    '3' => 'md:grid-cols-3',
    '4' => 'md:grid-cols-2 lg:grid-cols-4'
];

$clase_grid = $grid_clases[(string)$numero_planes] ?? $grid_clases['3'];
?>

<section class="flavor-component flavor-section" style="background: var(--flavor-background-alt, #f8f9fa);">
    <div class="flavor-container">
        <!-- Encabezado de sección -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <?php if (!empty($descripcion_seccion)): ?>
                <p class="text-xl mb-8" style="color: var(--flavor-text-secondary, #666666);">
                    <?php echo esc_html($descripcion_seccion); ?>
                </p>
            <?php endif; ?>

            <?php if ($periodo === 'ambos'): ?>
                <!-- Toggle mensual/anual -->
                <div class="inline-flex items-center gap-4 bg-white rounded-full p-2 shadow-lg">
                    <button class="px-6 py-2 rounded-full font-semibold transition-all duration-300 plan-toggle active"
                            data-periodo="mensual"
                            style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                        <?php echo esc_html__('Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button class="px-6 py-2 rounded-full font-semibold transition-all duration-300 plan-toggle"
                            data-periodo="anual"
                            style="color: var(--flavor-text-secondary, #666666);">
                        <?php echo esc_html__('Anual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="ml-2 text-xs px-2 py-1 rounded-full" style="background: var(--flavor-primary, #667eea); color: white;">
                            -20%
                        </span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grid de planes -->
        <div class="grid grid-cols-1 <?php echo esc_attr($clase_grid); ?> gap-8 max-w-7xl mx-auto">
            <?php foreach ($planes_mostrar as $plan): ?>
                <div class="relative <?php echo $plan['destacar'] ? 'lg:-mt-4 lg:mb-4' : ''; ?>">
                    <?php if ($plan['destacar'] && !empty($plan['badge'])): ?>
                        <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 z-10">
                            <span class="inline-block px-6 py-2 rounded-full text-sm font-bold text-white shadow-lg"
                                  style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                <?php echo esc_html($plan['badge']); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="h-full bg-white rounded-2xl p-8 shadow-lg transition-all duration-300 hover:shadow-2xl <?php echo $plan['destacar'] ? 'ring-4' : ''; ?>"
                         style="<?php echo $plan['destacar'] ? 'ring-color: var(--flavor-primary, #667eea);' : ''; ?>">

                        <!-- Encabezado del plan -->
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($plan['nombre']); ?>
                            </h3>
                            <p class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html($plan['descripcion']); ?>
                            </p>
                        </div>

                        <!-- Precio -->
                        <div class="text-center mb-8 pb-8 border-b" style="border-color: rgba(0,0,0,0.1);">
                            <?php if ($periodo === 'mensual' || $periodo === 'ambos'): ?>
                                <div class="precio-mensual <?php echo $periodo === 'ambos' ? 'precio-toggle' : ''; ?>" data-periodo="mensual">
                                    <?php if (is_numeric($plan['precio_mensual'])): ?>
                                        <div class="flex items-start justify-center">
                                            <span class="text-2xl font-bold mt-2" style="color: var(--flavor-text-primary, #1a1a1a);">€</span>
                                            <span class="text-6xl font-bold mx-1" style="color: var(--flavor-text-primary, #1a1a1a);">
                                                <?php echo esc_html($plan['precio_mensual']); ?>
                                            </span>
                                            <span class="text-xl mt-8" style="color: var(--flavor-text-secondary, #666666);"><?php echo esc_html__('/mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-4xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                            <?php echo esc_html($plan['precio_mensual']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($periodo === 'anual' || $periodo === 'ambos'): ?>
                                <div class="precio-anual <?php echo $periodo === 'ambos' ? 'precio-toggle hidden' : ''; ?>" data-periodo="anual">
                                    <?php if (is_numeric($plan['precio_anual'])): ?>
                                        <div class="flex items-start justify-center">
                                            <span class="text-2xl font-bold mt-2" style="color: var(--flavor-text-primary, #1a1a1a);">€</span>
                                            <span class="text-6xl font-bold mx-1" style="color: var(--flavor-text-primary, #1a1a1a);">
                                                <?php echo esc_html($plan['precio_anual']); ?>
                                            </span>
                                            <span class="text-xl mt-8" style="color: var(--flavor-text-secondary, #666666);"><?php echo esc_html__('/año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-4xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                            <?php echo esc_html($plan['precio_anual']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Características -->
                        <ul class="space-y-4 mb-8">
                            <?php foreach ($plan['caracteristicas'] as $caracteristica): ?>
                                <li class="flex items-start gap-3">
                                    <svg class="w-6 h-6 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-primary, #667eea);">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span style="color: var(--flavor-text-secondary, #666666);">
                                        <?php echo esc_html($caracteristica); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Botón de acción -->
                        <a href="#contacto"
                           class="block w-full px-8 py-4 text-center text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl <?php echo $plan['destacar'] ? '' : 'border-2'; ?>"
                           style="<?php echo $plan['destacar']
                               ? 'background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;'
                               : 'background: white; color: var(--flavor-primary, #667eea); border-color: var(--flavor-primary, #667eea);'; ?>">
                            <?php echo $plan['destacar'] ? 'Comenzar Ahora' : 'Seleccionar Plan'; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Información adicional -->
        <div class="text-center mt-16 max-w-3xl mx-auto">
            <p class="text-lg mb-6" style="color: var(--flavor-text-secondary, #666666);">
                <?php echo esc_html__('Todos los planes incluyen 30 días de garantía de devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <div class="flex flex-wrap justify-center gap-8">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-primary, #667eea);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="font-semibold" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Pagos Seguros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-primary, #667eea);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="font-semibold" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Datos Encriptados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-primary, #667eea);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-semibold" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Soporte 24/7', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($periodo === 'ambos'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.plan-toggle');
    const precioElements = document.querySelectorAll('.precio-toggle');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const periodo = this.dataset.periodo;

            // Actualizar botones
            toggleButtons.forEach(btn => {
                if (btn.dataset.periodo === periodo) {
                    btn.classList.add('active');
                    btn.style.background = 'linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%)';
                    btn.style.color = 'white';
                } else {
                    btn.classList.remove('active');
                    btn.style.background = 'transparent';
                    btn.style.color = 'var(--flavor-text-secondary, #666666)';
                }
            });

            // Mostrar/ocultar precios
            precioElements.forEach(el => {
                if (el.dataset.periodo === periodo) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
        });
    });
});
</script>
<?php endif; ?>
