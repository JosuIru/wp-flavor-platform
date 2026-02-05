<?php
/**
 * Template: Contacto Empresarial
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20" id="contacto">
    <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Contacta con nosotros'); ?></h2>
                <p class="text-gray-600 mb-8">Estamos aquí para ayudarte. Cuéntanos tu proyecto y te responderemos en menos de 24 horas.</p>

                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl">📧</div>
                        <div>
                            <div class="font-semibold text-gray-800">Email</div>
                            <div class="text-gray-600">info@empresa.com</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl">📞</div>
                        <div>
                            <div class="font-semibold text-gray-800">Teléfono</div>
                            <div class="text-gray-600">+34 900 000 000</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl">📍</div>
                        <div>
                            <div class="font-semibold text-gray-800">Ubicación</div>
                            <div class="text-gray-600">Madrid, España</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <form class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                            <input type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Tu nombre">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="tu@email.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asunto</label>
                        <input type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="¿En qué podemos ayudarte?">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje</label>
                        <textarea rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" placeholder="Cuéntanos sobre tu proyecto..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition-colors">
                        Enviar Mensaje
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
