<?php
/**
 * Frontend: Single Servicio Banco de Tiempo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$servicio = $servicio ?? [];
$usuario = $usuario ?? [];
$servicios_relacionados = $servicios_relacionados ?? [];
?>

<div class="flavor-frontend flavor-banco-tiempo-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" role="navigation" aria-label="<?php esc_attr_e('Migas de pan', 'flavor-chat-ia'); ?>">
        <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="hover:text-violet-600 transition-colors"><?php echo esc_html__('Banco de Tiempo', 'flavor-chat-ia'); ?></a>
        <span aria-hidden="true">›</span>
        <span class="text-gray-700" aria-current="page"><?php echo esc_html($servicio['titulo'] ?? 'Servicio'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header del servicio -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="<?php echo ($servicio['tipo'] ?? 'oferta') === 'oferta' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?> px-4 py-2 rounded-full font-medium">
                        <?php echo ($servicio['tipo'] ?? 'oferta') === 'oferta' ? '🎁 Ofrezco este servicio' : '🙋 Busco este servicio'; ?>
                    </span>
                    <span class="bg-violet-100 text-violet-700 px-4 py-2 rounded-full font-bold">
                        ⏱️ <?php echo esc_html($servicio['horas'] ?? 1); ?> hora<?php echo ($servicio['horas'] ?? 1) > 1 ? 's' : ''; ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($servicio['titulo']); ?>
                </h1>

                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        🏷️ <?php echo esc_html($servicio['categoria'] ?? 'General'); ?>
                    </span>
                    <?php if (!empty($servicio['disponibilidad'])): ?>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        📅 <?php echo esc_html($servicio['disponibilidad']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($servicio['zona'])): ?>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        📍 <?php echo esc_html($servicio['zona']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="prose prose-violet max-w-none">
                    <?php echo wp_kses_post($servicio['descripcion'] ?? ''); ?>
                </div>
            </div>

            <!-- Detalles adicionales -->
            <?php if (!empty($servicio['detalles'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('📋 Detalles del servicio', 'flavor-chat-ia'); ?></h2>
                <dl class="grid grid-cols-2 gap-4">
                    <?php foreach ($servicio['detalles'] as $label => $valor): ?>
                    <div>
                        <dt class="text-sm text-gray-500"><?php echo esc_html($label); ?></dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($valor); ?></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
            </div>
            <?php endif; ?>

            <!-- Valoraciones del usuario -->
            <?php if (!empty($usuario['valoraciones'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">⭐ Valoraciones de <?php echo esc_html($usuario['nombre']); ?></h2>
                <div class="space-y-4">
                    <?php foreach ($usuario['valoraciones'] as $val): ?>
                    <div class="border-b border-gray-100 pb-4 last:border-0">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <span><?php echo $i < $val['puntuacion'] ? '★' : '☆'; ?></span>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo esc_html($val['fecha']); ?></span>
                        </div>
                        <p class="text-gray-600 text-sm"><?php echo esc_html($val['comentario']); ?></p>
                        <p class="text-xs text-gray-400 mt-1">— <?php echo esc_html($val['autor']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Perfil del usuario -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($usuario['nombre'] ?? 'U', 0, 1)); ?>
                </div>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($usuario['nombre'] ?? 'Usuario'); ?></h3>
                <p class="text-sm text-gray-500 mb-4">Miembro desde <?php echo esc_html($usuario['miembro_desde'] ?? ''); ?></p>

                <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                    <div>
                        <p class="text-xl font-bold text-violet-600"><?php echo esc_html($usuario['horas_disponibles'] ?? 0); ?>h</p>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Disponibles', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($usuario['intercambios'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Intercambios', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-yellow-500">⭐ <?php echo esc_html($usuario['valoracion'] ?? '5.0'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Valoración', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <?php if (!empty($usuario['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full mb-4">
                    <?php echo esc_html__('✓ Usuario verificado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Solicitar servicio -->
            <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2"><?php echo esc_html__('¿Te interesa?', 'flavor-chat-ia'); ?></h3>
                <p class="text-violet-100 text-sm mb-4">Contacta con <?php echo esc_html($usuario['nombre'] ?? 'el usuario'); ?> para acordar el intercambio.</p>
                <button class="w-full bg-white text-violet-600 py-3 px-4 rounded-xl font-semibold hover:bg-violet-50 transition-colors"
                        onclick="flavorBancoTiempo.solicitarServicio(<?php echo esc_attr($servicio['id']); ?>)">
                    <?php echo esc_html__('🤝 Solicitar servicio', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Otros servicios del usuario -->
            <?php if (!empty($usuario['otros_servicios'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Otros servicios de <?php echo esc_html($usuario['nombre']); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($usuario['otros_servicios'] as $otro): ?>
                    <a href="<?php echo esc_url($otro['url']); ?>" class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($otro['titulo']); ?></p>
                        <p class="text-xs text-violet-600"><?php echo esc_html($otro['horas']); ?>h • <?php echo esc_html($otro['categoria']); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Servicios relacionados -->
            <?php if (!empty($servicios_relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Servicios similares', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($servicios_relacionados as $rel): ?>
                    <a href="<?php echo esc_url($rel['url']); ?>" class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($rel['titulo']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($rel['usuario_nombre']); ?> • <?php echo esc_html($rel['horas']); ?>h</p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
