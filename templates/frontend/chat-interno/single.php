<?php
/**
 * Frontend: Single Conversacion Chat Interno
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$conversacion = $conversacion ?? [];
$mensajes = $mensajes ?? [];
$contacto = $contacto ?? [];
?>

<div class="flavor-frontend flavor-chat-interno-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/chat-interno/')); ?>" class="hover:text-blue-600 transition-colors"><?php echo esc_html__('Chat Interno', 'flavor-chat-ia'); ?></a>
        <span>›</span>
        <span class="text-gray-700"><?php echo esc_html($contacto['nombre'] ?? 'Conversacion'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Chat principal -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Cabecera de conversacion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 text-xl font-bold">
                                <?php echo esc_html(mb_substr($contacto['nombre'] ?? 'U', 0, 1)); ?>
                            </div>
                            <?php if (!empty($contacto['online'])): ?>
                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-gray-800"><?php echo esc_html($contacto['nombre'] ?? ''); ?></h1>
                            <p class="text-sm text-gray-500">
                                <?php echo !empty($contacto['online']) ? '🟢 En linea' : 'Ult. vez: ' . esc_html($contacto['ultima_conexion'] ?? ''); ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="<?php echo esc_attr__('Buscar en conversacion', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                        <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="<?php echo esc_attr__('Opciones', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Area de mensajes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="h-[500px] overflow-y-auto p-6 space-y-4" id="mensajes-container-interno">
                    <?php if (empty($mensajes)): ?>
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">✉️</div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2"><?php echo esc_html__('Inicio de la conversacion', 'flavor-chat-ia'); ?></h3>
                        <p class="text-gray-500">Envia tu primer mensaje a <?php echo esc_html($contacto['nombre'] ?? 'este usuario'); ?></p>
                    </div>
                    <?php else: ?>
                    <?php
                    $fecha_anterior_mensaje = '';
                    foreach ($mensajes as $mensaje_interno):
                        $fecha_mensaje_actual = $mensaje_interno['fecha'] ?? '';
                        if ($fecha_mensaje_actual !== $fecha_anterior_mensaje):
                    ?>
                    <div class="text-center my-4">
                        <span class="bg-gray-100 text-gray-500 text-xs px-4 py-1 rounded-full"><?php echo esc_html($fecha_mensaje_actual); ?></span>
                    </div>
                    <?php
                        $fecha_anterior_mensaje = $fecha_mensaje_actual;
                        endif;
                    ?>
                    <div class="flex items-start gap-3 <?php echo !empty($mensaje_interno['es_propio']) ? 'flex-row-reverse' : ''; ?>">
                        <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 text-xs font-medium flex-shrink-0">
                            <?php echo esc_html(mb_substr($mensaje_interno['autor_nombre'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="max-w-[70%]">
                            <div class="<?php echo !empty($mensaje_interno['es_propio']) ? 'bg-sky-500 text-white' : 'bg-gray-100 text-gray-800'; ?> rounded-2xl px-4 py-3">
                                <p class="text-sm"><?php echo esc_html($mensaje_interno['contenido'] ?? ''); ?></p>
                            </div>
                            <div class="flex items-center gap-1 mt-1 <?php echo !empty($mensaje_interno['es_propio']) ? 'justify-end' : ''; ?>">
                                <span class="text-xs text-gray-400"><?php echo esc_html($mensaje_interno['hora'] ?? ''); ?></span>
                                <?php if (!empty($mensaje_interno['es_propio']) && !empty($mensaje_interno['leido'])): ?>
                                <span class="text-xs text-sky-400">✓✓</span>
                                <?php elseif (!empty($mensaje_interno['es_propio'])): ?>
                                <span class="text-xs text-gray-400">✓</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input de mensaje -->
                <div class="p-4 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="<?php echo esc_attr__('Adjuntar archivo', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </button>
                        <input type="text" placeholder="<?php echo esc_attr__('Escribe un mensaje...', 'flavor-chat-ia'); ?>"
                               class="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm"
                               id="input-mensaje-interno">
                        <button class="bg-sky-500 text-white p-3 rounded-xl hover:bg-sky-600 transition-colors"
                                onclick="flavorChatInterno.enviarMensaje(<?php echo esc_attr($conversacion['id'] ?? 0); ?>)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Info del contacto -->
        <div class="space-y-6">
            <!-- Perfil del contacto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($contacto['nombre'] ?? 'U', 0, 1)); ?>
                </div>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($contacto['nombre'] ?? 'Usuario'); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($contacto['rol'] ?? 'Vecino'); ?></p>
                <?php if (!empty($contacto['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full mb-4">
                    <?php echo esc_html__('✓ Usuario verificado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500"><?php echo esc_html__('Miembro desde', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium text-gray-800"><?php echo esc_html($contacto['fecha_registro'] ?? ''); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500"><?php echo esc_html__('Comunidad', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium text-gray-800"><?php echo esc_html($contacto['comunidad'] ?? ''); ?></span>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <button class="w-full text-left px-4 py-3 rounded-xl text-sm text-gray-700 hover:bg-sky-50 hover:text-sky-700 transition-colors"
                            onclick="flavorChatInterno.verPerfil(<?php echo esc_attr($contacto['id'] ?? 0); ?>)">
                        <?php echo esc_html__('👤 Ver perfil completo', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="w-full text-left px-4 py-3 rounded-xl text-sm text-gray-700 hover:bg-sky-50 hover:text-sky-700 transition-colors"
                            onclick="flavorChatInterno.silenciar(<?php echo esc_attr($conversacion['id'] ?? 0); ?>)">
                        <?php echo esc_html__('🔇 Silenciar conversacion', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="w-full text-left px-4 py-3 rounded-xl text-sm text-red-600 hover:bg-red-50 transition-colors"
                            onclick="flavorChatInterno.bloquear(<?php echo esc_attr($contacto['id'] ?? 0); ?>)">
                        <?php echo esc_html__('🚫 Bloquear usuario', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <!-- Archivos compartidos -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-3"><?php echo esc_html__('📎 Archivos compartidos', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-500"><?php echo esc_html($conversacion['archivos_compartidos'] ?? 0); ?> archivos</p>
            </div>
        </div>
    </div>
</div>
