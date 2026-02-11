<?php
/**
 * Frontend: Archive de Chat Interno
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$conversaciones = $conversaciones ?? [];
$total_conversaciones = $total_conversaciones ?? 0;
$estadisticas = $estadisticas ?? [];
?>

<div class="flavor-frontend flavor-chat-interno-archive">
    <!-- Header con gradiente azul cielo -->
    <div class="bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('✉️ Chat Interno', 'flavor-chat-ia'); ?></h1>
                <p class="text-sky-100"><?php echo esc_html__('Mensajeria directa entre miembros de la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_conversaciones); ?> conversaciones
                </span>
                <button class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-sky-50 transition-all shadow-md"
                        onclick="flavorChatInterno.nuevaConversacion()">
                    <?php echo esc_html__('➕ Nuevo Mensaje', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💬</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['conversaciones'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Conversaciones', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🔔</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['no_leidos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('No leidos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👤</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['contactos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Contactos', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lista de conversaciones -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <div class="relative">
                <input type="text" placeholder="<?php echo esc_attr__('Buscar conversaciones...', 'flavor-chat-ia'); ?>"
                       class="w-full px-4 py-3 pl-10 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                       id="buscar-conversaciones">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <?php if (empty($conversaciones)): ?>
        <div class="text-center py-16">
            <div class="text-6xl mb-4">✉️</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('Sin conversaciones', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('Inicia una conversacion con alguien de la comunidad', 'flavor-chat-ia'); ?></p>
            <button class="bg-sky-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-sky-600 transition-colors"
                    onclick="flavorChatInterno.nuevaConversacion()">
                <?php echo esc_html__('Nuevo Mensaje', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($conversaciones as $conversacion): ?>
            <a href="<?php echo esc_url($conversacion['url'] ?? '#'); ?>"
               class="flex items-center gap-4 p-4 hover:bg-sky-50 transition-colors <?php echo !empty($conversacion['no_leido']) ? 'bg-sky-50/50' : ''; ?>">
                <!-- Avatar -->
                <div class="relative flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 text-lg font-bold">
                        <?php echo esc_html(mb_substr($conversacion['contacto_nombre'] ?? 'U', 0, 1)); ?>
                    </div>
                    <?php if (!empty($conversacion['online'])): ?>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></span>
                    <?php endif; ?>
                </div>

                <!-- Info conversacion -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-semibold text-gray-800 truncate <?php echo !empty($conversacion['no_leido']) ? 'text-gray-900' : ''; ?>">
                            <?php echo esc_html($conversacion['contacto_nombre'] ?? 'Usuario'); ?>
                        </h3>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2"><?php echo esc_html($conversacion['hora'] ?? ''); ?></span>
                    </div>
                    <p class="text-sm truncate <?php echo !empty($conversacion['no_leido']) ? 'text-gray-800 font-medium' : 'text-gray-500'; ?>">
                        <?php echo esc_html($conversacion['ultimo_mensaje'] ?? ''); ?>
                    </p>
                </div>

                <!-- Badge no leido -->
                <?php if (!empty($conversacion['no_leido'])): ?>
                <span class="bg-sky-500 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0">
                    <?php echo esc_html($conversacion['cantidad_no_leidos'] ?? '1'); ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_conversaciones > 20): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_conversaciones / 20); ?></span>
            <button class="px-4 py-2 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition-colors"><?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
