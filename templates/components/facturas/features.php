<?php
/**
 * Template: Facturas Features
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_features = $titulo_features ?? 'Funcionalidades de Facturacion';

$funcionalidades_facturas = $funcionalidades_facturas ?? [
    [
        'titulo'      => 'Crear factura en segundos',
        'descripcion' => 'Interfaz intuitiva para generar facturas completas en pocos clics. Ahorra tiempo en la gestion administrativa.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'color'       => '#14B8A6',
    ],
    [
        'titulo'      => 'Plantillas personalizables',
        'descripcion' => 'Personaliza tus facturas con tu logo, colores corporativos y la informacion de tu empresa.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>',
        'color'       => '#0D9488',
    ],
    [
        'titulo'      => 'Envio automatico por email',
        'descripcion' => 'Envia las facturas directamente al correo de tus clientes con un solo clic.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'color'       => '#059669',
    ],
    [
        'titulo'      => 'Control de cobros',
        'descripcion' => 'Lleva el seguimiento de facturas pendientes, pagadas y vencidas de un vistazo.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'color'       => '#10B981',
    ],
    [
        'titulo'      => 'Exportar a PDF',
        'descripcion' => 'Genera PDFs profesionales listos para imprimir o enviar. Formato estandar y legible.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'color'       => '#34D399',
    ],
    [
        'titulo'      => 'Cumplimiento fiscal',
        'descripcion' => 'Facturas que cumplen con la normativa fiscal vigente. IVA, IRPF y retenciones configurables.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        'color'       => '#6EE7B7',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-50">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3"><?php echo esc_html($titulo_features); ?></h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto"><?php echo esc_html__('Todo lo necesario para gestionar tu facturacion de forma profesional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($funcionalidades_facturas as $funcionalidad_item) : ?>
                <div class="flavor-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110" style="background: <?php echo esc_attr($funcionalidad_item['color']); ?>15;">
                        <svg class="w-6 h-6" style="color: <?php echo esc_attr($funcionalidad_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $funcionalidad_item['icono']; ?>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo esc_html($funcionalidad_item['titulo']); ?></h3>
                    <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($funcionalidad_item['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
