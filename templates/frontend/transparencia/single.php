<?php
/**
 * Frontend: Single Documento de Transparencia
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$documento = $documento ?? [];
$relacionados = $relacionados ?? [];
?>

<div class="flavor-frontend flavor-transparencia-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-teal-600 transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
        <span>&#8250;</span>
        <a href="<?php echo esc_url(home_url('/transparencia/')); ?>" class="hover:text-teal-600 transition-colors"><?php echo esc_html__('Transparencia', 'flavor-chat-ia'); ?></a>
        <span>&#8250;</span>
        <span class="text-gray-700"><?php echo esc_html($documento['titulo'] ?? 'Documento'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header del documento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <?php
                $tipo_documento = strtolower($documento['formato'] ?? 'pdf');
                $iconos_formato = [
                    'pdf'  => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'label' => 'PDF'],
                    'xls'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'XLS'],
                    'xlsx' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'XLS'],
                    'csv'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'CSV'],
                    'doc'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'DOC'],
                    'docx' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'DOC'],
                ];
                $icono_formato = $iconos_formato[$tipo_documento] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => strtoupper($tipo_documento)];
                ?>
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-14 h-14 <?php echo esc_attr($icono_formato['bg']); ?> rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="<?php echo esc_attr($icono_formato['text']); ?> font-bold text-sm"><?php echo esc_html($icono_formato['label']); ?></span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo esc_html($documento['titulo'] ?? ''); ?></h1>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-xs font-medium">
                                <?php echo esc_html($documento['categoria'] ?? ''); ?>
                            </span>
                            <span class="text-sm text-gray-500">
                                <?php echo esc_html($documento['fecha'] ?? ''); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Metadatos -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-xl mb-6">
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Fecha publicacion', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-700"><?php echo esc_html($documento['fecha'] ?? ''); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-700"><?php echo esc_html($documento['autor'] ?? 'Ayuntamiento'); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-700"><?php echo esc_html($documento['categoria'] ?? ''); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Formato', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-700"><?php echo esc_html(strtoupper($documento['formato'] ?? '')); ?> (<?php echo esc_html($documento['tamano'] ?? ''); ?>)</p>
                    </div>
                </div>

                <!-- Contenido / Resumen -->
                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($documento['contenido'] ?? $documento['resumen'] ?? ''); ?>
                </div>
            </div>

            <!-- Boton de descarga principal -->
            <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-2xl p-6 border border-teal-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800"><?php echo esc_html__('Descargar documento', 'flavor-chat-ia'); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo esc_html(strtoupper($documento['formato'] ?? '')); ?> - <?php echo esc_html($documento['tamano'] ?? ''); ?></p>
                </div>
                <a href="<?php echo esc_url($documento['enlace_descarga'] ?? '#'); ?>"
                   class="bg-teal-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-teal-600 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <?php echo esc_html__('Descargar', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Documentos relacionados -->
            <?php if (!empty($relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Documentos relacionados', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-3">
                    <?php foreach ($relacionados as $documento_relacionado): ?>
                    <a href="<?php echo esc_url($documento_relacionado['url'] ?? '#'); ?>"
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors border border-gray-100">
                        <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-teal-600 font-bold text-xs"><?php echo esc_html(strtoupper($documento_relacionado['formato'] ?? 'PDF')); ?></span>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($documento_relacionado['titulo'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($documento_relacionado['fecha'] ?? ''); ?></p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Informacion del documento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Informacion del documento', 'flavor-chat-ia'); ?></h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Referencia', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($documento['referencia'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($documento['tipo'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Formato', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html(strtoupper($documento['formato'] ?? '')); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Tamano', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($documento['tamano'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Descargas', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($documento['descargas'] ?? 0); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Categorias de la misma seccion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Mas en esta categoria', 'flavor-chat-ia'); ?></h3>
                <?php if (!empty($documento['misma_categoria'])): ?>
                <div class="space-y-3">
                    <?php foreach ($documento['misma_categoria'] as $documento_categoria): ?>
                    <a href="<?php echo esc_url($documento_categoria['url'] ?? '#'); ?>"
                       class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($documento_categoria['titulo'] ?? ''); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($documento_categoria['fecha'] ?? ''); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-500"><?php echo esc_html__('No hay mas documentos en esta categoria', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <!-- CTA de descarga -->
            <div class="bg-gradient-to-br from-teal-500 to-cyan-500 rounded-2xl p-6 text-white">
                <h3 class="font-bold text-lg mb-2"><?php echo esc_html__('Descarga directa', 'flavor-chat-ia'); ?></h3>
                <p class="text-teal-100 text-sm mb-4"><?php echo esc_html(strtoupper($documento['formato'] ?? '')); ?> - <?php echo esc_html($documento['tamano'] ?? ''); ?></p>
                <a href="<?php echo esc_url($documento['enlace_descarga'] ?? '#'); ?>"
                   class="w-full inline-block text-center bg-white text-teal-600 py-3 rounded-xl font-semibold hover:bg-teal-50 transition-colors">
                    <?php echo esc_html__('Descargar ahora', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
