<?php
/**
 * Template: Repositorio de Documentos
 *
 * Seccion con buscador, filtros por categoria y listado
 * de documentos con tipo, titulo, fecha, tamano y descarga.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$documentos_ejemplo = [
    [
        'titulo'    => 'Presupuesto General 2026',
        'tipo'      => 'pdf',
        'categoria' => 'Presupuestos',
        'fecha'     => '15/01/2026',
        'tamano'    => '2.4 MB',
    ],
    [
        'titulo'    => 'Listado de contratos menores Q4 2025',
        'tipo'      => 'excel',
        'categoria' => 'Contratos',
        'fecha'     => '10/01/2026',
        'tamano'    => '856 KB',
    ],
    [
        'titulo'    => 'Acta del Pleno Ordinario - Diciembre 2025',
        'tipo'      => 'pdf',
        'categoria' => 'Actas',
        'fecha'     => '22/12/2025',
        'tamano'    => '1.1 MB',
    ],
    [
        'titulo'    => 'Informe de sostenibilidad medioambiental',
        'tipo'      => 'pdf',
        'categoria' => 'Medio Ambiente',
        'fecha'     => '05/01/2026',
        'tamano'    => '3.8 MB',
    ],
    [
        'titulo'    => 'Datos abiertos de movilidad urbana',
        'tipo'      => 'excel',
        'categoria' => 'Movilidad',
        'fecha'     => '28/12/2025',
        'tamano'    => '1.5 MB',
    ],
];

$iconos_tipo_documento = [
    'pdf'   => ['color' => 'bg-red-100 text-red-600',    'etiqueta' => 'PDF'],
    'excel' => ['color' => 'bg-green-100 text-green-600', 'etiqueta' => 'XLS'],
    'word'  => ['color' => 'bg-blue-100 text-blue-600',   'etiqueta' => 'DOC'],
    'csv'   => ['color' => 'bg-purple-100 text-purple-600','etiqueta' => 'CSV'],
];

$categorias_filtro_documentos = ['Todos', 'Presupuestos', 'Contratos', 'Actas', 'Personal', 'Medio Ambiente'];
?>

<section class="flavor-component flavor-section py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Repositorio de Documentos'); ?>
            </h2>
            <div class="w-20 h-1 bg-teal-500 mx-auto rounded-full"></div>
        </div>

        <!-- Buscador y filtros -->
        <div class="max-w-4xl mx-auto mb-10">
            <!-- Buscador -->
            <div class="relative mb-6">
                <input type="text" placeholder="<?php echo esc_attr__('Buscar documentos por titulo o categoria...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="w-full px-5 py-3 pr-12 border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-teal-400 text-gray-900">
                <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Filtros por categoria -->
            <div class="flex flex-wrap gap-2 justify-center">
                <?php foreach ($categorias_filtro_documentos as $indice_filtro => $categoria_filtro): ?>
                    <button class="px-4 py-2 rounded-full text-sm font-medium transition duration-300 <?php echo ($indice_filtro === 0) ? 'bg-teal-500 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-teal-50 border border-gray-200'; ?>">
                        <?php echo esc_html($categoria_filtro); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Listado de documentos -->
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
            <?php foreach ($documentos_ejemplo as $indice_documento => $documento):
                $tipo_doc = $iconos_tipo_documento[$documento['tipo']] ?? $iconos_tipo_documento['pdf'];
            ?>
                <div class="flex items-center px-6 py-4 <?php echo ($indice_documento < count($documentos_ejemplo) - 1) ? 'border-b border-gray-100' : ''; ?> hover:bg-teal-50 transition duration-200 group">
                    <!-- Icono de tipo -->
                    <div class="flex-shrink-0 w-12 h-12 <?php echo esc_attr($tipo_doc['color']); ?> rounded-lg flex items-center justify-center mr-4">
                        <span class="text-xs font-bold"><?php echo esc_html($tipo_doc['etiqueta']); ?></span>
                    </div>

                    <!-- Info del documento -->
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-gray-900 truncate group-hover:text-teal-600 transition duration-300">
                            <?php echo esc_html($documento['titulo']); ?>
                        </h4>
                        <div class="flex items-center space-x-3 mt-1">
                            <span class="text-xs text-gray-400">
                                <?php echo esc_html($documento['categoria']); ?>
                            </span>
                            <span class="text-xs text-gray-300"><?php echo esc_html__('&bull;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="text-xs text-gray-400">
                                <?php echo esc_html($documento['fecha']); ?>
                            </span>
                            <span class="text-xs text-gray-300"><?php echo esc_html__('&bull;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="text-xs text-gray-400">
                                <?php echo esc_html($documento['tamano']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Boton descargar -->
                    <a href="#" class="flex-shrink-0 ml-4 p-2 text-teal-500 hover:text-teal-700 hover:bg-teal-100 rounded-lg transition duration-300" title="<?php echo esc_attr__('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginacion -->
        <div class="flex justify-center mt-8 space-x-2">
            <button class="w-10 h-10 rounded-lg bg-teal-500 text-white font-semibold text-sm shadow-md">1</button>
            <button class="w-10 h-10 rounded-lg bg-white text-gray-600 font-semibold text-sm border border-gray-200 hover:bg-teal-50 transition duration-300">2</button>
            <button class="w-10 h-10 rounded-lg bg-white text-gray-600 font-semibold text-sm border border-gray-200 hover:bg-teal-50 transition duration-300">3</button>
            <span class="w-10 h-10 flex items-center justify-center text-gray-400">...</span>
            <button class="w-10 h-10 rounded-lg bg-white text-gray-600 font-semibold text-sm border border-gray-200 hover:bg-teal-50 transition duration-300">12</button>
        </div>
    </div>
</section>
