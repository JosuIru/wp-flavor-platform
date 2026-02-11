<?php
/**
 * Template: Grid de Servicios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Nuestros Servicios';
$descripcion_seccion = $descripcion_seccion ?? 'Soluciones integrales diseñadas para hacer crecer tu negocio';
$columnas = $columnas ?? '3';
$estilo = $estilo ?? 'cards';

// Usar items del repeater o fallback a datos de ejemplo
$servicios_mostrar = $items ?? [];

// Si los items vienen del repeater y no tienen icono SVG, generar un icono genérico
foreach ($servicios_mostrar as &$servicio_item) {
    if (empty($servicio_item['icono'])) {
        $servicio_item['icono'] = '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>';
    }
}
unset($servicio_item);

// Fallback: si no hay items configurados, mostrar ejemplo minimo
if (empty($servicios_mostrar)) {
    $servicios_mostrar = [
        [
            'icono' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
            'titulo' => 'Consultoría Estratégica',
            'descripcion' => 'Asesoramiento experto para optimizar tus procesos de negocio.',
        ],
        [
            'icono' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>',
            'titulo' => 'Desarrollo de Software',
            'descripcion' => 'Soluciones tecnológicas a medida para tu empresa.',
        ],
        [
            'icono' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>',
            'titulo' => 'Marketing Digital',
            'descripcion' => 'Estrategias integrales para aumentar tu presencia online.',
        ],
    ];
}

// Clases de columnas según la selección
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
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <?php if (!empty($descripcion_seccion)): ?>
                <p class="text-xl" style="color: var(--flavor-text-secondary, #666666);">
                    <?php echo esc_html($descripcion_seccion); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Grid de servicios -->
        <div class="grid grid-cols-1 <?php echo esc_attr($clase_columnas); ?> gap-8">
            <?php foreach ($servicios_mostrar as $servicio): ?>
                <div class="group relative transition-all duration-300 hover:transform hover:-translate-y-2">
                    <?php if ($estilo === 'cards'): ?>
                        <!-- Estilo Cards -->
                        <div class="h-full p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300"
                             style="background: white; border: 1px solid rgba(0,0,0,0.05);">
                            <div class="mb-6 inline-flex items-center justify-center p-4 rounded-xl transition-all duration-300 group-hover:scale-110"
                                 style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                                <?php echo $servicio['icono']; ?>
                            </div>
                            <h3 class="text-2xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($servicio['titulo']); ?>
                            </h3>
                            <p class="leading-relaxed" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html($servicio['descripcion']); ?>
                            </p>
                            <div class="mt-6 flex items-center gap-2 font-semibold transition-all duration-300"
                                 style="color: var(--flavor-primary, #667eea);">
                                <span><?php echo esc_html__('Más información', 'flavor-chat-ia'); ?></span>
                                <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                        </div>

                    <?php elseif ($estilo === 'minimal'): ?>
                        <!-- Estilo Minimal -->
                        <div class="h-full p-8">
                            <div class="mb-6" style="color: var(--flavor-primary, #667eea);">
                                <?php echo $servicio['icono']; ?>
                            </div>
                            <h3 class="text-2xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($servicio['titulo']); ?>
                            </h3>
                            <p class="leading-relaxed" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html($servicio['descripcion']); ?>
                            </p>
                        </div>

                    <?php else: // bordered ?>
                        <!-- Estilo Bordered -->
                        <div class="h-full p-8 rounded-xl transition-all duration-300 group-hover:border-opacity-100"
                             style="border: 2px solid rgba(102, 126, 234, 0.2); background: rgba(255,255,255,0.5);">
                            <div class="mb-6" style="color: var(--flavor-primary, #667eea);">
                                <?php echo $servicio['icono']; ?>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 transition-colors duration-300 group-hover:text-current"
                                style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($servicio['titulo']); ?>
                            </h3>
                            <p class="leading-relaxed" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html($servicio['descripcion']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Call to action -->
        <div class="text-center mt-16">
            <a href="#contacto"
               class="flavor-button inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:shadow-lg"
               style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                <span><?php echo esc_html__('Solicitar Información', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
