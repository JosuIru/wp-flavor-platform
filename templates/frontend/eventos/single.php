<?php
/**
 * Frontend: Single Evento
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$evento = $evento ?? [];
$organizador = $organizador ?? [];
$asistentes_lista = $asistentes_lista ?? [];
$eventos_relacionados = $eventos_relacionados ?? [];
?>

<div class="flavor-frontend flavor-eventos-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="hover:text-pink-600 transition-colors">Eventos</a>
        <span>›</span>
        <?php if (!empty($evento['categoria'])): ?>
        <a href="<?php echo esc_url(home_url('/eventos/?cat=' . ($evento['categoria_slug'] ?? ''))); ?>" class="hover:text-pink-600 transition-colors">
            <?php echo esc_html($evento['categoria']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($evento['titulo'] ?? 'Evento'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Banner del evento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <?php if (!empty($evento['imagen'])): ?>
                    <img src="<?php echo esc_url($evento['imagen']); ?>" alt="<?php echo esc_attr($evento['titulo'] ?? ''); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-br from-rose-400 to-pink-600 flex items-center justify-center">
                        <span class="text-6xl">🎉</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-white rounded-xl p-3 text-center shadow-lg">
                        <p class="text-xs font-medium text-rose-600 uppercase"><?php echo esc_html($evento['mes'] ?? 'Ene'); ?></p>
                        <p class="text-2xl font-bold text-gray-800 leading-none"><?php echo esc_html($evento['dia'] ?? '01'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Informacion del evento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-rose-100 text-rose-700 px-4 py-2 rounded-full font-medium text-sm">
                        <?php echo esc_html($evento['categoria'] ?? 'General'); ?>
                    </span>
                    <span class="<?php echo ($evento['precio'] ?? 0) == 0 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?> px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo ($evento['precio'] ?? 0) == 0 ? 'Gratis' : esc_html($evento['precio']) . ' €'; ?>
                    </span>
                    <?php if (!empty($evento['tipo_formato'])): ?>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        <?php echo esc_html($evento['tipo_formato']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($evento['titulo'] ?? ''); ?>
                </h1>

                <!-- Info rapida -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-rose-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📅</span>
                        <div>
                            <p class="text-sm text-gray-500">Fecha</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($evento['fecha_completa'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🕐</span>
                        <div>
                            <p class="text-sm text-gray-500">Hora</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($evento['hora'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📍</span>
                        <div>
                            <p class="text-sm text-gray-500">Lugar</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($evento['ubicacion'] ?? 'Por confirmar'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">👥</span>
                        <div>
                            <p class="text-sm text-gray-500">Asistentes</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($evento['asistentes'] ?? 0); ?> confirmados</p>
                        </div>
                    </div>
                </div>

                <div class="prose prose-pink max-w-none">
                    <?php echo wp_kses_post($evento['descripcion'] ?? ''); ?>
                </div>
            </div>

            <!-- Mapa ubicacion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📍 Ubicacion del evento</h2>
                <div class="bg-gray-100 rounded-xl h-48 flex items-center justify-center text-gray-400 mb-3">
                    <span class="text-lg">Mapa de ubicacion</span>
                </div>
                <p class="text-gray-600"><?php echo esc_html($evento['direccion'] ?? ''); ?></p>
            </div>

            <!-- Lista de asistentes -->
            <?php if (!empty($asistentes_lista)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">👥 Asistentes confirmados (<?php echo count($asistentes_lista); ?>)</h2>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($asistentes_lista as $asistente_persona): ?>
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-3 py-1">
                        <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($asistente_persona['nombre'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-700"><?php echo esc_html($asistente_persona['nombre'] ?? ''); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Inscripcion -->
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2">¡No te lo pierdas!</h3>
                <p class="text-rose-100 text-sm mb-2">
                    <?php echo ($evento['precio'] ?? 0) == 0 ? 'Evento gratuito' : 'Precio: ' . esc_html($evento['precio']) . ' €'; ?>
                </p>
                <?php if (!empty($evento['plazas_disponibles'])): ?>
                <p class="text-rose-100 text-sm mb-4">Quedan <?php echo esc_html($evento['plazas_disponibles']); ?> plazas</p>
                <?php else: ?>
                <p class="text-rose-100 text-sm mb-4">Plazas limitadas</p>
                <?php endif; ?>
                <button class="w-full bg-white text-pink-600 py-3 px-4 rounded-xl font-semibold hover:bg-rose-50 transition-colors"
                        onclick="flavorEventos.inscribirse(<?php echo esc_attr($evento['id'] ?? 0); ?>)">
                    🎟️ Inscribirse
                </button>
            </div>

            <!-- Organizador -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($organizador['nombre'] ?? 'O', 0, 1)); ?>
                </div>
                <p class="text-xs text-gray-500 mb-1">Organizado por</p>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($organizador['nombre'] ?? 'Organizador'); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($organizador['eventos_organizados'] ?? 0); ?> eventos organizados</p>
                <?php if (!empty($organizador['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">
                    ✓ Organizador verificado
                </span>
                <?php endif; ?>
            </div>

            <!-- Compartir -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">📤 Compartir evento</h3>
                <div class="flex gap-3">
                    <button class="flex-1 bg-blue-100 text-blue-700 py-2 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors" onclick="flavorEventos.compartir('facebook')">
                        Facebook
                    </button>
                    <button class="flex-1 bg-sky-100 text-sky-700 py-2 rounded-lg text-sm font-medium hover:bg-sky-200 transition-colors" onclick="flavorEventos.compartir('twitter')">
                        Twitter
                    </button>
                    <button class="flex-1 bg-green-100 text-green-700 py-2 rounded-lg text-sm font-medium hover:bg-green-200 transition-colors" onclick="flavorEventos.compartir('whatsapp')">
                        WhatsApp
                    </button>
                </div>
            </div>

            <!-- Eventos relacionados -->
            <?php if (!empty($eventos_relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Eventos similares</h3>
                <div class="space-y-3">
                    <?php foreach ($eventos_relacionados as $evento_relacionado): ?>
                    <a href="<?php echo esc_url($evento_relacionado['url'] ?? '#'); ?>" class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="bg-rose-100 text-rose-700 rounded-lg p-2 text-center min-w-[44px] flex-shrink-0">
                                <p class="text-xs font-medium"><?php echo esc_html($evento_relacionado['mes'] ?? ''); ?></p>
                                <p class="text-lg font-bold leading-none"><?php echo esc_html($evento_relacionado['dia'] ?? ''); ?></p>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($evento_relacionado['titulo'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($evento_relacionado['hora'] ?? ''); ?> - <?php echo esc_html($evento_relacionado['ubicacion'] ?? ''); ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
