<?php
/**
 * Template: Grid de Datos Abiertos
 *
 * Grid de categorias de datos abiertos con icono,
 * nombre, cantidad de documentos y enlaces de descarga.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$categorias_datos = [
    [
        'nombre'             => 'Presupuestos',
        'descripcion'        => 'Presupuestos anuales, ejecucion presupuestaria y cuentas generales del municipio.',
        'icono'              => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'total_documentos'   => 34,
        'ultima_actualizacion' => '15 ene 2026',
    ],
    [
        'nombre'             => 'Contratos',
        'descripcion'        => 'Contratos publicos, licitaciones, adjudicaciones y perfil del contratante.',
        'icono'              => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'total_documentos'   => 156,
        'ultima_actualizacion' => '28 ene 2026',
    ],
    [
        'nombre'             => 'Personal',
        'descripcion'        => 'Relacion de puestos de trabajo, retribuciones, organigramas y compatibilidades.',
        'icono'              => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'total_documentos'   => 28,
        'ultima_actualizacion' => '10 ene 2026',
    ],
    [
        'nombre'             => 'Subvenciones',
        'descripcion'        => 'Convocatorias, resoluciones y listados de subvenciones y ayudas concedidas.',
        'icono'              => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
        'total_documentos'   => 67,
        'ultima_actualizacion' => '22 ene 2026',
    ],
    [
        'nombre'             => 'Urbanismo',
        'descripcion'        => 'Planes urbanisticos, licencias de obra, informes de impacto ambiental y ordenanzas.',
        'icono'              => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'total_documentos'   => 43,
        'ultima_actualizacion' => '18 ene 2026',
    ],
    [
        'nombre'             => 'Actas y Acuerdos',
        'descripcion'        => 'Actas de pleno, juntas de gobierno, comisiones informativas y ordenes del dia.',
        'icono'              => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
        'total_documentos'   => 92,
        'ultima_actualizacion' => '25 ene 2026',
    ],
];
?>

<section class="flavor-component flavor-section py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Datos Abiertos'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Accede a toda la informacion publica organizada por categorias.'); ?>
            </p>
            <div class="w-20 h-1 bg-teal-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Grid de categorias -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($categorias_datos as $categoria_dato): ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 p-6 group border border-gray-100">
                    <!-- Icono y titulo -->
                    <div class="flex items-start space-x-4 mb-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-teal-400 to-cyan-500 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition duration-300">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($categoria_dato['icono']); ?>" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-teal-600 transition duration-300">
                                <?php echo esc_html($categoria_dato['nombre']); ?>
                            </h3>
                            <span class="text-sm text-teal-600 font-medium">
                                <?php echo esc_html($categoria_dato['total_documentos']); ?> <?php echo esc_html__('documentos', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Descripcion -->
                    <p class="text-gray-600 text-sm leading-relaxed mb-4">
                        <?php echo esc_html($categoria_dato['descripcion']); ?>
                    </p>

                    <!-- Pie con fecha y enlace -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <span class="text-xs text-gray-400">
                            <?php echo esc_html__('Actualizado:', 'flavor-chat-ia'); ?> <?php echo esc_html($categoria_dato['ultima_actualizacion']); ?>
                        </span>
                        <a href="#" class="inline-flex items-center text-teal-600 hover:text-teal-700 text-sm font-medium transition duration-300">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <?php echo esc_html__('Descargar', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
