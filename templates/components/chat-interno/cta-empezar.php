<?php
/**
 * Template: Chat Interno CTA Empezar
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'Comunicacion segura y privada';
$descripcion_cta = $descripcion_cta ?? 'Empieza a chatear con tu comunidad de forma segura, sin anuncios y con cifrado de extremo a extremo.';
$url_comenzar = $url_comenzar ?? '#comenzar';
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #FFF1F2 0%, #FCE7F3 100%);">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-12">
            <!-- Mockup de telefono -->
            <div class="flex-shrink-0 order-2 lg:order-1">
                <div class="relative">
                    <!-- Marco del telefono -->
                    <div class="w-64 h-[480px] rounded-[2.5rem] border-4 border-gray-800 bg-gray-900 shadow-2xl overflow-hidden relative">
                        <!-- Notch -->
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-gray-800 rounded-b-2xl z-10"></div>
                        <!-- Pantalla -->
                        <div class="w-full h-full bg-white p-4 pt-8 overflow-hidden">
                            <!-- Cabecera del chat -->
                            <div class="flex items-center gap-3 pb-3 border-b border-gray-100 mb-3">
                                <div class="w-8 h-8 rounded-full bg-rose-500 flex items-center justify-center">
                                    <span class="text-white text-xs font-bold">MG</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800"><?php echo esc_html__('Mi Grupo', 'flavor-chat-ia'); ?></p>
                                    <p class="text-[10px] text-green-500"><?php echo esc_html__('3 en linea', 'flavor-chat-ia'); ?></p>
                                </div>
                            </div>
                            <!-- Mensajes de ejemplo -->
                            <div class="space-y-2">
                                <div class="flex justify-start">
                                    <div class="max-w-[80%] px-3 py-2 rounded-xl rounded-tl-sm bg-gray-100">
                                        <p class="text-[10px] text-gray-700"><?php echo esc_html__('Hola a todos! Quedamos manana?', 'flavor-chat-ia'); ?></p>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <div class="max-w-[80%] px-3 py-2 rounded-xl rounded-tr-sm bg-rose-500">
                                        <p class="text-[10px] text-white"><?php echo esc_html__('Perfecto, yo me apunto!', 'flavor-chat-ia'); ?></p>
                                    </div>
                                </div>
                                <div class="flex justify-start">
                                    <div class="max-w-[80%] px-3 py-2 rounded-xl rounded-tl-sm bg-gray-100">
                                        <p class="text-[10px] text-gray-700"><?php echo esc_html__('Genial! A las 18h?', 'flavor-chat-ia'); ?></p>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <div class="max-w-[80%] px-3 py-2 rounded-xl rounded-tr-sm bg-rose-500">
                                        <p class="text-[10px] text-white"><?php echo esc_html__('Nos vemos alli', 'flavor-chat-ia'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <!-- Input de mensaje -->
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 border border-gray-200">
                                    <span class="text-[10px] text-gray-400 flex-1"><?php echo esc_html__('Escribe un mensaje...', 'flavor-chat-ia'); ?></span>
                                    <div class="w-6 h-6 rounded-full bg-rose-500 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Efecto de brillo -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-rose-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-pink-400/20 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 text-center lg:text-left order-1 lg:order-2">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($titulo_cta); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    <?php echo esc_html($descripcion_cta); ?>
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="<?php echo esc_url($url_comenzar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-rose-500 to-pink-600 text-white font-semibold text-lg hover:from-rose-600 hover:to-pink-700 transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <?php echo esc_html__('Comenzar Ahora', 'flavor-chat-ia'); ?>
                    </a>
                    <span class="text-sm text-gray-500">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <?php echo esc_html__('Cifrado de extremo a extremo', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>
