<?php
/**
 * Template: CTA Instructor Talleres
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_instructor = $titulo_instructor ?? 'Comparte tu Conocimiento';
$descripcion_instructor = $descripcion_instructor ?? 'Unete a nuestra comunidad de instructores y ayuda a otros a aprender. Crea talleres, comparte tu experiencia y conecta con personas apasionadas por aprender.';
$url_crear_taller = $url_crear_taller ?? '/talleres/crear/';
$url_mis_talleres = $url_mis_talleres ?? '/talleres/mis-talleres/';

$beneficios_instructor = $beneficios_instructor ?? [
    'Llega a cientos de participantes interesados',
    'Herramientas para gestionar inscripciones facilmente',
    'Comunidad activa y colaborativa',
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #FDF2F8 0%, #FCE7F3 50%, #F5F3FF 100%);">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-12">
            <!-- Ilustracion -->
            <div class="flex-shrink-0 order-2 lg:order-1">
                <div class="relative w-72">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                        <!-- Cabecera perfil instructor -->
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-fuchsia-500 to-pink-600 rounded-full flex items-center justify-center text-white font-bold">
                                IN
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('Instructor Activo', 'flavor-chat-ia'); ?></p>
                                <p class="text-xs text-gray-400"><?php echo esc_html__('Miembro desde 2024', 'flavor-chat-ia'); ?></p>
                            </div>
                        </div>
                        <!-- Estadisticas del instructor -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Talleres creados', 'flavor-chat-ia'); ?></span>
                                <span class="text-sm font-bold text-fuchsia-600">12</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Participantes totales', 'flavor-chat-ia'); ?></span>
                                <span class="text-sm font-bold text-fuchsia-600">284</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Valoracion media', 'flavor-chat-ia'); ?></span>
                                <span class="text-sm font-bold text-fuchsia-600">4.9 / 5</span>
                            </div>
                        </div>
                        <!-- Badge -->
                        <div class="mt-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-fuchsia-100 text-fuchsia-700 text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?php echo esc_html__('Instructor verificado', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                    </div>
                    <!-- Efectos decorativos -->
                    <div class="absolute -top-3 -right-3 w-20 h-20 bg-fuchsia-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-3 -left-3 w-24 h-24 bg-pink-400/20 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 text-center lg:text-left order-1 lg:order-2">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($titulo_instructor); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    <?php echo esc_html($descripcion_instructor); ?>
                </p>

                <ul class="space-y-3 mb-8 text-left max-w-md mx-auto lg:mx-0">
                    <?php foreach ($beneficios_instructor as $beneficio_texto) : ?>
                        <li class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-fuchsia-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm"><?php echo esc_html($beneficio_texto); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="<?php echo esc_url($url_crear_taller); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-fuchsia-500 to-pink-600 text-white font-semibold text-lg hover:from-fuchsia-600 hover:to-pink-700 transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php echo esc_html__('Crear un Taller', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url($url_mis_talleres); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-fuchsia-300 text-fuchsia-600 font-semibold text-lg hover:bg-fuchsia-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <?php echo esc_html__('Mis Talleres', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
