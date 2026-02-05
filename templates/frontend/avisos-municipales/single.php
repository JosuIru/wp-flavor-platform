<?php
/**
 * Frontend: Single Aviso Municipal
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$aviso = $aviso ?? [];
$relacionados = $relacionados ?? [];
?>

<div class="flavor-frontend flavor-avisos-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-sky-600 transition-colors">Inicio</a>
        <span>&#8250;</span>
        <a href="<?php echo esc_url(home_url('/avisos-municipales/')); ?>" class="hover:text-sky-600 transition-colors">Avisos</a>
        <span>&#8250;</span>
        <span class="text-gray-700"><?php echo esc_html($aviso['titulo'] ?? 'Aviso'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header del aviso -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <?php
                $urgencia_aviso = $aviso['urgencia'] ?? 'informativo';
                $colores_urgencia = [
                    'informativo' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'label' => 'Informativo'],
                    'importante'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Importante'],
                    'urgente'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Urgente'],
                ];
                $color_urgencia = $colores_urgencia[$urgencia_aviso] ?? $colores_urgencia['informativo'];
                ?>

                <!-- Urgencia y categoria -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="<?php echo esc_attr($color_urgencia['bg']); ?> <?php echo esc_attr($color_urgencia['text']); ?> px-4 py-2 rounded-full font-medium">
                        <?php echo esc_html($color_urgencia['label']); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-sm">
                        <?php echo esc_html($aviso['categoria'] ?? ''); ?>
                    </span>
                    <span class="text-sm text-gray-500 ml-auto">
                        <?php echo esc_html($aviso['fecha'] ?? ''); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo esc_html($aviso['titulo'] ?? ''); ?></h1>

                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($aviso['contenido'] ?? ''); ?>
                </div>
            </div>

            <!-- Fechas afectadas -->
            <?php if (!empty($aviso['fecha_inicio']) || !empty($aviso['fecha_fin'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Fechas afectadas</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php if (!empty($aviso['fecha_inicio'])): ?>
                    <div class="p-4 bg-sky-50 rounded-xl border border-sky-100">
                        <p class="text-xs text-sky-600 font-medium mb-1">Fecha inicio</p>
                        <p class="text-gray-800 font-semibold"><?php echo esc_html($aviso['fecha_inicio']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($aviso['fecha_fin'])): ?>
                    <div class="p-4 bg-sky-50 rounded-xl border border-sky-100">
                        <p class="text-xs text-sky-600 font-medium mb-1">Fecha fin</p>
                        <p class="text-gray-800 font-semibold"><?php echo esc_html($aviso['fecha_fin']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Zonas afectadas - placeholder de mapa -->
            <?php if (!empty($aviso['zonas_afectadas'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Zonas afectadas</h2>
                <div class="aspect-video bg-gray-100 rounded-xl mb-4 flex items-center justify-center" id="mapa-zonas-aviso">
                    <div class="text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <p class="text-sm">Mapa de zonas afectadas</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($aviso['zonas_afectadas'] as $zona_afectada): ?>
                    <span class="bg-sky-100 text-sky-700 px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo esc_html($zona_afectada); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Adjuntos -->
            <?php if (!empty($aviso['adjuntos'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Documentos adjuntos</h2>
                <div class="space-y-3">
                    <?php foreach ($aviso['adjuntos'] as $adjunto_archivo): ?>
                    <a href="<?php echo esc_url($adjunto_archivo['url'] ?? '#'); ?>"
                       class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-sky-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700"><?php echo esc_html($adjunto_archivo['nombre'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($adjunto_archivo['tamano'] ?? ''); ?></p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Informacion del aviso -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Informacion del aviso</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Urgencia</dt>
                        <dd class="<?php echo esc_attr($color_urgencia['text']); ?> font-medium"><?php echo esc_html($color_urgencia['label']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Categoria</dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($aviso['categoria'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Publicado</dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($aviso['fecha'] ?? ''); ?></dd>
                    </div>
                    <?php if (!empty($aviso['zona_afectada'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Zona</dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($aviso['zona_afectada']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($aviso['departamento'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Departamento</dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($aviso['departamento']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Compartir -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Compartir aviso</h3>
                <div class="flex gap-3">
                    <button class="flex-1 bg-sky-100 text-sky-700 py-2 rounded-lg text-sm font-medium hover:bg-sky-200 transition-colors"
                            onclick="flavorAvisos.compartir('twitter', <?php echo esc_attr($aviso['id'] ?? 0); ?>)">
                        Twitter
                    </button>
                    <button class="flex-1 bg-blue-100 text-blue-700 py-2 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors"
                            onclick="flavorAvisos.compartir('facebook', <?php echo esc_attr($aviso['id'] ?? 0); ?>)">
                        Facebook
                    </button>
                    <button class="flex-1 bg-green-100 text-green-700 py-2 rounded-lg text-sm font-medium hover:bg-green-200 transition-colors"
                            onclick="flavorAvisos.compartir('whatsapp', <?php echo esc_attr($aviso['id'] ?? 0); ?>)">
                        WhatsApp
                    </button>
                </div>
            </div>

            <!-- Avisos relacionados -->
            <?php if (!empty($relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Avisos relacionados</h3>
                <div class="space-y-3">
                    <?php foreach ($relacionados as $aviso_relacionado): ?>
                    <?php
                    $urgencia_rel = $aviso_relacionado['urgencia'] ?? 'informativo';
                    $color_rel = $colores_urgencia[$urgencia_rel] ?? $colores_urgencia['informativo'];
                    ?>
                    <a href="<?php echo esc_url($aviso_relacionado['url'] ?? '#'); ?>"
                       class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="<?php echo esc_attr($color_rel['bg']); ?> <?php echo esc_attr($color_rel['text']); ?> px-2 py-0.5 rounded text-xs font-medium">
                                <?php echo esc_html(ucfirst($urgencia_rel)); ?>
                            </span>
                        </div>
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($aviso_relacionado['titulo'] ?? ''); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($aviso_relacionado['fecha'] ?? ''); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- CTA suscripcion -->
            <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-2xl p-6 text-white">
                <h3 class="font-bold text-lg mb-2">No te pierdas nada</h3>
                <p class="text-sky-100 text-sm mb-4">Recibe los avisos directamente en tu correo o movil</p>
                <button class="w-full bg-white text-blue-600 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-colors"
                        onclick="flavorAvisos.suscribirse()">
                    Suscribirse a Avisos
                </button>
            </div>
        </div>
    </div>
</div>
