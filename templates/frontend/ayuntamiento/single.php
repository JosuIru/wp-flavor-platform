<?php
/**
 * Frontend: Single Ayuntamiento (Trámite/Noticia/Servicio)
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$item = $item ?? [];
$tipo = $tipo ?? 'tramite'; // tramite, noticia, servicio
$documentos = $documentos ?? [];
$relacionados = $relacionados ?? [];
?>

<div class="flavor-frontend flavor-ayuntamiento-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/ayuntamiento/')); ?>" class="hover:text-blue-600 transition-colors">Ayuntamiento</a>
        <span>›</span>
        <span class="text-gray-700"><?php echo esc_html($item['titulo'] ?? 'Detalle'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <?php if (!empty($item['categoria'])): ?>
                <span class="inline-block bg-blue-100 text-blue-700 text-sm font-medium px-3 py-1 rounded-full mb-4">
                    <?php echo esc_html($item['categoria']); ?>
                </span>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($item['titulo']); ?>
                </h1>

                <?php if (!empty($item['fecha'])): ?>
                <p class="text-gray-500 text-sm mb-6">
                    <?php if ($tipo === 'noticia'): ?>
                    Publicado el <?php echo esc_html($item['fecha']); ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($item['imagen'])): ?>
                <div class="rounded-xl overflow-hidden mb-6">
                    <img src="<?php echo esc_url($item['imagen']); ?>" alt="" class="w-full">
                </div>
                <?php endif; ?>

                <div class="prose prose-blue max-w-none">
                    <?php echo wp_kses_post($item['contenido'] ?? $item['descripcion'] ?? ''); ?>
                </div>
            </div>

            <?php if ($tipo === 'tramite'): ?>
            <!-- Requisitos -->
            <?php if (!empty($item['requisitos'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Requisitos</h2>
                <ul class="space-y-2">
                    <?php foreach ($item['requisitos'] as $req): ?>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500 mt-1">✓</span>
                        <span class="text-gray-600"><?php echo esc_html($req); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Pasos -->
            <?php if (!empty($item['pasos'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🔢 Pasos a seguir</h2>
                <ol class="space-y-4">
                    <?php foreach ($item['pasos'] as $i => $paso): ?>
                    <li class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold flex-shrink-0">
                            <?php echo $i + 1; ?>
                        </div>
                        <div class="pt-1">
                            <p class="text-gray-700"><?php echo esc_html($paso); ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Documentos adjuntos -->
            <?php if (!empty($documentos)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📎 Documentos</h2>
                <div class="space-y-3">
                    <?php foreach ($documentos as $doc): ?>
                    <a href="<?php echo esc_url($doc['url']); ?>"
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors border border-gray-100"
                       download>
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                            📄
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800"><?php echo esc_html($doc['nombre']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($doc['formato'] ?? 'PDF'); ?> • <?php echo esc_html($doc['tamaño'] ?? ''); ?></p>
                        </div>
                        <span class="text-blue-600">⬇️</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <?php if ($tipo === 'tramite'): ?>
            <!-- Acciones trámite -->
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-4">Iniciar trámite</h3>
                <?php if (!empty($item['tramite_online'])): ?>
                <a href="<?php echo esc_url($item['url_tramite']); ?>"
                   class="block w-full bg-white text-blue-700 py-3 px-4 rounded-xl font-semibold text-center hover:bg-blue-50 transition-colors mb-3">
                    💻 Tramitar online
                </a>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/ayuntamiento/cita-previa/')); ?>"
                   class="block w-full bg-blue-500 text-white py-3 px-4 rounded-xl font-semibold text-center hover:bg-blue-400 transition-colors">
                    📅 Pedir cita presencial
                </a>
            </div>

            <!-- Info trámite -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">ℹ️ Información</h3>
                <dl class="space-y-3 text-sm">
                    <?php if (!empty($item['plazo'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Plazo resolución</dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($item['plazo']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($item['coste'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Coste</dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($item['coste']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($item['departamento'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Departamento</dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($item['departamento']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
            <?php endif; ?>

            <!-- Contacto -->
            <div class="bg-blue-50 rounded-2xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">¿Necesitas ayuda?</h3>
                <div class="space-y-3 text-sm">
                    <a href="tel:900000000" class="flex items-center gap-2 text-blue-600 hover:underline">
                        📞 Atención ciudadana
                    </a>
                    <a href="<?php echo esc_url(home_url('/ayuntamiento/contacto/')); ?>" class="flex items-center gap-2 text-blue-600 hover:underline">
                        ✉️ Enviar consulta
                    </a>
                </div>
            </div>

            <!-- Relacionados -->
            <?php if (!empty($relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Relacionados</h3>
                <ul class="space-y-3">
                    <?php foreach ($relacionados as $rel): ?>
                    <li>
                        <a href="<?php echo esc_url($rel['url']); ?>" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">
                            → <?php echo esc_html($rel['titulo']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
