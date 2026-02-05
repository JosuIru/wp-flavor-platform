<?php
/**
 * Frontend: Archive del Portal de Transparencia
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-transparencia-archive">
    <!-- Header con gradiente teal/cyan -->
    <div class="bg-gradient-to-r from-teal-500 to-cyan-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Portal de Transparencia</h1>
                <p class="text-teal-100">Acceso a toda la informacion publica del municipio</p>
            </div>
            <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                <?php echo esc_html($total); ?> documentos publicados
            </span>
        </div>
    </div>

    <!-- Estadisticas rapidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center text-teal-600 font-bold">D</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['documentos_publicados'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Documentos publicados</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center text-cyan-600 font-bold">O</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['datasets_abiertos'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Datasets abiertos</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold">C</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['consultas'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Consultas realizadas</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600 font-bold">A</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['ultima_actualizacion'] ?? ''); ?></p>
                    <p class="text-xs text-gray-500">Ultima actualizacion</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros rapidos por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-teal-100 text-teal-700 font-medium hover:bg-teal-200 transition-colors filter-active"
                data-categoria="todos">
            Todos
        </button>
        <?php
        $categorias_transparencia = ['Presupuestos', 'Contratos', 'Personal', 'Subvenciones', 'Plenos'];
        foreach ($categorias_transparencia as $categoria_trans): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-categoria="<?php echo esc_attr(strtolower($categoria_trans)); ?>">
            <?php echo esc_html($categoria_trans); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de documentos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($items)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">&#x1F4C4;</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay documentos disponibles</h3>
            <p class="text-gray-500">Los documentos se publicaran proximamente</p>
        </div>
        <?php else: ?>
        <?php foreach ($items as $documento): ?>
        <?php
        $tipo_documento = strtolower($documento['formato'] ?? 'pdf');
        $iconos_formato = [
            'pdf'   => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'label' => 'PDF'],
            'xls'   => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'XLS'],
            'xlsx'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'XLS'],
            'csv'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'CSV'],
            'doc'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'DOC'],
            'docx'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'DOC'],
        ];
        $icono_formato = $iconos_formato[$tipo_documento] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => strtoupper($tipo_documento)];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-12 h-12 <?php echo esc_attr($icono_formato['bg']); ?> rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="<?php echo esc_attr($icono_formato['text']); ?> font-bold text-xs"><?php echo esc_html($icono_formato['label']); ?></span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <a href="<?php echo esc_url($documento['url'] ?? '#'); ?>" class="hover:text-teal-600 transition-colors">
                                <?php echo esc_html($documento['titulo'] ?? ''); ?>
                            </a>
                        </h3>
                        <span class="text-xs text-gray-500"><?php echo esc_html($documento['categoria'] ?? ''); ?></span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-4">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?php echo esc_html($documento['fecha'] ?? ''); ?>
                    </span>
                    <?php if (!empty($documento['tamano'])): ?>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <?php echo esc_html($documento['tamano']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
                <a href="<?php echo esc_url($documento['enlace_descarga'] ?? $documento['url'] ?? '#'); ?>"
                   class="w-full inline-flex items-center justify-center gap-2 bg-teal-500 text-white py-2 px-4 rounded-lg font-medium hover:bg-teal-600 transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Descargar
                </a>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-teal-500 text-white hover:bg-teal-600 transition-colors">Siguiente</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
