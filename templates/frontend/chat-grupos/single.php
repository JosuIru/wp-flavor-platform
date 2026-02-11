<?php
/**
 * Frontend: Single Chat Grupo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$grupo = $grupo ?? [];
$mensajes = $mensajes ?? [];
$miembros_lista = $miembros_lista ?? [];
$es_miembro = $es_miembro ?? false;
?>

<div class="flavor-frontend flavor-chat-grupos-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/chat-grupos/')); ?>" class="hover:text-purple-600 transition-colors"><?php echo esc_html__('Chat Grupos', 'flavor-chat-ia'); ?></a>
        <span>›</span>
        <?php if (!empty($grupo['categoria'])): ?>
        <a href="<?php echo esc_url(home_url('/chat-grupos/?cat=' . ($grupo['categoria_slug'] ?? ''))); ?>" class="hover:text-purple-600 transition-colors">
            <?php echo esc_html($grupo['categoria']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($grupo['nombre'] ?? 'Grupo'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Chat principal -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Cabecera del grupo -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-violet-500 text-white flex items-center justify-center text-2xl font-bold flex-shrink-0">
                            <?php echo esc_html(mb_substr($grupo['nombre'] ?? 'G', 0, 1)); ?>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800"><?php echo esc_html($grupo['nombre'] ?? ''); ?></h1>
                            <p class="text-sm text-gray-500"><?php echo esc_html($grupo['miembros'] ?? 0); ?> miembros - <?php echo ($grupo['tipo'] ?? 'publico') === 'publico' ? '🌐 Publico' : '🔒 Privado'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="<?php echo esc_attr__('Buscar en el grupo', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                        <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="<?php echo esc_attr__('Configuracion del grupo', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Area de mensajes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="h-[500px] overflow-y-auto p-6 space-y-4" id="mensajes-container">
                    <?php if (empty($mensajes)): ?>
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">💬</div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2"><?php echo esc_html__('Inicio de la conversacion', 'flavor-chat-ia'); ?></h3>
                        <p class="text-gray-500"><?php echo esc_html__('¡Se el primero en enviar un mensaje!', 'flavor-chat-ia'); ?></p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($mensajes as $mensaje_grupo): ?>
                    <div class="flex items-start gap-3 <?php echo !empty($mensaje_grupo['es_propio']) ? 'flex-row-reverse' : ''; ?>">
                        <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 text-sm font-medium flex-shrink-0">
                            <?php echo esc_html(mb_substr($mensaje_grupo['autor_nombre'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="max-w-[70%]">
                            <div class="flex items-center gap-2 mb-1 <?php echo !empty($mensaje_grupo['es_propio']) ? 'flex-row-reverse' : ''; ?>">
                                <span class="text-sm font-medium text-gray-800"><?php echo esc_html($mensaje_grupo['autor_nombre'] ?? ''); ?></span>
                                <span class="text-xs text-gray-400"><?php echo esc_html($mensaje_grupo['hora'] ?? ''); ?></span>
                            </div>
                            <div class="<?php echo !empty($mensaje_grupo['es_propio']) ? 'bg-violet-500 text-white' : 'bg-gray-100 text-gray-800'; ?> rounded-2xl px-4 py-3">
                                <p class="text-sm"><?php echo esc_html($mensaje_grupo['contenido'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input de mensaje -->
                <?php if ($es_miembro): ?>
                <div class="p-4 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="<?php echo esc_attr__('Adjuntar archivo', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </button>
                        <input type="text" placeholder="<?php echo esc_attr__('Escribe un mensaje...', 'flavor-chat-ia'); ?>"
                               class="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm"
                               id="input-mensaje-grupo">
                        <button class="bg-violet-500 text-white p-3 rounded-xl hover:bg-violet-600 transition-colors"
                                onclick="flavorChatGrupos.enviarMensaje(<?php echo esc_attr($grupo['id'] ?? 0); ?>)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="p-4 border-t border-gray-100 text-center">
                    <p class="text-gray-500 text-sm mb-3"><?php echo esc_html__('Unete al grupo para participar en la conversacion', 'flavor-chat-ia'); ?></p>
                    <button class="bg-violet-500 text-white px-6 py-2 rounded-xl font-medium hover:bg-violet-600 transition-colors"
                            onclick="flavorChatGrupos.unirse(<?php echo esc_attr($grupo['id'] ?? 0); ?>)">
                        <?php echo esc_html__('Unirse al grupo', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: Info del grupo y miembros -->
        <div class="space-y-6">
            <!-- Info del grupo -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-3"><?php echo esc_html__('Sobre el grupo', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600 mb-4"><?php echo esc_html($grupo['descripcion'] ?? ''); ?></p>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500"><?php echo esc_html__('Creado', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium text-gray-800"><?php echo esc_html($grupo['fecha_creacion'] ?? ''); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium text-gray-800"><?php echo esc_html(ucfirst($grupo['tipo'] ?? 'publico')); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium text-gray-800"><?php echo esc_html($grupo['categoria'] ?? 'General'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Miembros -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">👥 Miembros (<?php echo count($miembros_lista); ?>)</h3>
                <div class="space-y-3 max-h-[300px] overflow-y-auto">
                    <?php foreach ($miembros_lista as $miembro_grupo): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 text-xs font-medium flex-shrink-0">
                            <?php echo esc_html(mb_substr($miembro_grupo['nombre'] ?? 'M', 0, 1)); ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($miembro_grupo['nombre'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($miembro_grupo['rol'] ?? 'Miembro'); ?></p>
                        </div>
                        <?php if (!empty($miembro_grupo['online'])): ?>
                        <span class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Archivos compartidos -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-3"><?php echo esc_html__('📎 Archivos compartidos', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-500"><?php echo esc_html($grupo['archivos_compartidos'] ?? 0); ?> archivos</p>
            </div>
        </div>
    </div>
</div>
