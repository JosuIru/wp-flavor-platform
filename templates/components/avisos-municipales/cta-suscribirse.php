<?php
/**
 * Template: CTA Suscribirse - Avisos Municipales
 *
 * Seccion de llamada a la accion para suscribirse a notificaciones
 * y alertas de avisos municipales.
 *
 * @var string $titulo_suscribir
 * @var string $descripcion_suscribir
 * @var string $url_suscribirse
 * @var string $url_configurar_alertas
 * @var array  $beneficios_suscripcion
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_suscribir      = $titulo_suscribir ?? 'No te Pierdas Ningun Aviso';
$descripcion_suscribir = $descripcion_suscribir ?? 'Suscribete a las notificaciones municipales y recibe alertas en tiempo real sobre lo que ocurre en tu municipio. Elige las categorias que te interesan.';
$url_suscribirse       = $url_suscribirse ?? '/avisos-municipales/suscribirse/';
$url_configurar_alertas = $url_configurar_alertas ?? '/avisos-municipales/configurar/';

$beneficios_suscripcion = $beneficios_suscripcion ?? [
    'Recibe avisos urgentes al instante por notificacion',
    'Elige las categorias que mas te interesan',
    'Resumen semanal con los avisos mas importantes',
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #FEF2F2 0%, #FFE4E6 50%, #FFF1F2 100%);">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-12">
            <!-- Ilustracion -->
            <div class="flex-shrink-0 order-2 lg:order-1">
                <div class="relative w-72">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                        <!-- Cabecera notificaciones -->
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-full flex items-center justify-center text-white font-bold">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('Mis Alertas', 'flavor-chat-ia'); ?></p>
                                <p class="text-xs text-gray-400"><?php echo esc_html__('3 categorias activas', 'flavor-chat-ia'); ?></p>
                            </div>
                        </div>
                        <!-- Simulacion de notificaciones -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center gap-3 p-2 rounded-lg bg-red-50">
                                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse flex-shrink-0"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-800 truncate"><?php echo esc_html__('Aviso urgente recibido', 'flavor-chat-ia'); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo esc_html__('Hace 5 min', 'flavor-chat-ia'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg bg-blue-50">
                                <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-800 truncate"><?php echo esc_html__('Nuevo aviso de Cultura', 'flavor-chat-ia'); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo esc_html__('Hace 2 horas', 'flavor-chat-ia'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg bg-orange-50">
                                <span class="w-2 h-2 bg-orange-500 rounded-full flex-shrink-0"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-800 truncate"><?php echo esc_html__('Obras: desvio de trafico', 'flavor-chat-ia'); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo esc_html__('Ayer', 'flavor-chat-ia'); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Badge -->
                        <div class="mt-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?php echo esc_html__('Notificaciones activas', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                    </div>
                    <!-- Efectos decorativos -->
                    <div class="absolute -top-3 -right-3 w-20 h-20 bg-red-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-3 -left-3 w-24 h-24 bg-rose-400/20 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 text-center lg:text-left order-1 lg:order-2">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($titulo_suscribir); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    <?php echo esc_html($descripcion_suscribir); ?>
                </p>

                <ul class="space-y-3 mb-8 text-left max-w-md mx-auto lg:mx-0">
                    <?php foreach ($beneficios_suscripcion as $beneficio_texto) : ?>
                        <li class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm"><?php echo esc_html($beneficio_texto); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="<?php echo esc_url($url_suscribirse); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-red-500 to-rose-600 text-white font-semibold text-lg hover:from-red-600 hover:to-rose-700 transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php echo esc_html__('Suscribirme a Alertas', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url($url_configurar_alertas); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-red-300 text-red-600 font-semibold text-lg hover:bg-red-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <?php echo esc_html__('Configurar Alertas', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
