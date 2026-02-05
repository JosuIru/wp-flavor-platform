<?php
/**
 * Frontend: Single Tramite
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$tramite = $tramite ?? [];
$relacionados = $relacionados ?? [];
$preguntas_frecuentes = $preguntas_frecuentes ?? [];
?>

<div class="flavor-frontend flavor-tramites-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-orange-600 transition-colors">Inicio</a>
        <span>&#8250;</span>
        <a href="<?php echo esc_url(home_url('/tramites/')); ?>" class="hover:text-orange-600 transition-colors">Tramites</a>
        <span>&#8250;</span>
        <span class="text-gray-700"><?php echo esc_html($tramite['titulo'] ?? 'Tramite'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header del tramite -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 flex-shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo esc_html($tramite['titulo'] ?? ''); ?></h1>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php
                            $modalidad_tramite = $tramite['modalidad'] ?? 'online';
                            $colores_modalidad = [
                                'online'     => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
                                'presencial' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                                'ambos'      => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
                            ];
                            $color_modalidad = $colores_modalidad[$modalidad_tramite] ?? $colores_modalidad['online'];
                            ?>
                            <span class="<?php echo esc_attr($color_modalidad['bg']); ?> <?php echo esc_attr($color_modalidad['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                                <?php echo esc_html(ucfirst($modalidad_tramite)); ?>
                            </span>
                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs">
                                <?php echo esc_html($tramite['categoria'] ?? ''); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($tramite['descripcion'] ?? ''); ?>
                </div>
            </div>

            <!-- Requisitos -->
            <?php if (!empty($tramite['requisitos'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Requisitos</h2>
                <ul class="space-y-3">
                    <?php foreach ($tramite['requisitos'] as $requisito_item): ?>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="text-gray-700"><?php echo esc_html($requisito_item); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Pasos a seguir -->
            <?php if (!empty($tramite['pasos'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Pasos a seguir</h2>
                <div class="space-y-4">
                    <?php foreach ($tramite['pasos'] as $indice_paso => $paso_detalle): ?>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center flex-shrink-0 font-bold text-sm">
                            <?php echo esc_html($indice_paso + 1); ?>
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-gray-700"><?php echo esc_html($paso_detalle); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documentos necesarios -->
            <?php if (!empty($tramite['documentos'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Documentos necesarios</h2>
                <div class="space-y-3">
                    <?php foreach ($tramite['documentos'] as $documento_necesario): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-700 text-sm"><?php echo esc_html($documento_necesario); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tramites relacionados -->
            <?php if (!empty($relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Tramites relacionados</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($relacionados as $tramite_relacionado): ?>
                    <a href="<?php echo esc_url($tramite_relacionado['url'] ?? '#'); ?>"
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors border border-gray-100">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700"><?php echo esc_html($tramite_relacionado['titulo'] ?? ''); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Iniciar tramite -->
            <div class="bg-gradient-to-br from-orange-500 to-amber-500 rounded-2xl p-6 text-white">
                <h3 class="font-bold text-lg mb-2">Iniciar tramite</h3>
                <p class="text-orange-100 text-sm mb-3">Tiempo estimado: <?php echo esc_html($tramite['tiempo_estimado'] ?? ''); ?></p>
                <button class="w-full bg-white text-orange-600 py-3 rounded-xl font-semibold hover:bg-orange-50 transition-colors"
                        onclick="flavorTramites.iniciar(<?php echo esc_attr($tramite['id'] ?? 0); ?>)">
                    Comenzar ahora
                </button>
            </div>

            <!-- FAQ Accordion -->
            <?php if (!empty($preguntas_frecuentes)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Preguntas frecuentes</h3>
                <div class="space-y-3">
                    <?php foreach ($preguntas_frecuentes as $indice_pregunta => $pregunta_frecuente): ?>
                    <details class="group">
                        <summary class="flex items-center justify-between cursor-pointer p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <span class="text-sm font-medium text-gray-700"><?php echo esc_html($pregunta_frecuente['pregunta'] ?? ''); ?></span>
                            <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="px-3 pb-3 text-sm text-gray-600">
                            <?php echo esc_html($pregunta_frecuente['respuesta'] ?? ''); ?>
                        </div>
                    </details>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informacion de contacto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Contacto</h3>
                <div class="space-y-3 text-sm">
                    <?php if (!empty($tramite['telefono'])): ?>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-gray-700"><?php echo esc_html($tramite['telefono']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($tramite['email'])): ?>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-gray-700"><?php echo esc_html($tramite['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($tramite['horario'])): ?>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-gray-700"><?php echo esc_html($tramite['horario']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
