<?php
/**
 * Template: CTA Participar - Banco de Tiempo
 *
 * Seccion de llamada a la accion para unirse al banco de tiempo,
 * ofrecer servicios o registrarse como miembro.
 *
 * @var string $titulo_participar
 * @var string $descripcion_participar
 * @var string $url_registro
 * @var string $url_crear_servicio
 * @var array  $beneficios_participar
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_participar      = $titulo_participar ?? 'Unete al Banco de Tiempo';
$descripcion_participar = $descripcion_participar ?? 'Forma parte de una comunidad donde el tiempo es la moneda mas valiosa. Comparte tus habilidades y recibe ayuda cuando la necesites.';
$url_registro           = $url_registro ?? '/banco-tiempo/registro/';
$url_crear_servicio     = $url_crear_servicio ?? '/banco-tiempo/ofrecer/';

$beneficios_participar = $beneficios_participar ?? [
    'Intercambia servicios sin dinero de por medio',
    'Conoce a tus vecinos y fortalece la comunidad',
    'Accede a una gran variedad de habilidades y servicios',
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 50%, #FFF7ED 100%);">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-12">
            <!-- Ilustracion -->
            <div class="flex-shrink-0 order-2 lg:order-1">
                <div class="relative w-72">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                        <!-- Cabecera perfil miembro -->
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold">
                                BT
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('Miembro Activo', 'flavor-chat-ia'); ?></p>
                                <p class="text-xs text-gray-400"><?php echo esc_html__('Nivel: Colaborador', 'flavor-chat-ia'); ?></p>
                            </div>
                        </div>
                        <!-- Estadisticas del miembro -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Creditos disponibles', 'flavor-chat-ia'); ?></span>
                                <span class="text-sm font-bold text-amber-600">24h</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Servicios ofrecidos', 'flavor-chat-ia'); ?></span>
                                <span class="text-sm font-bold text-amber-600">8</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Intercambios realizados', 'flavor-chat-ia'); ?></span>
                                <span class="text-sm font-bold text-amber-600">15</span>
                            </div>
                        </div>
                        <!-- Badge -->
                        <div class="mt-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?php echo esc_html__('Miembro verificado', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                    </div>
                    <!-- Efectos decorativos -->
                    <div class="absolute -top-3 -right-3 w-20 h-20 bg-amber-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-3 -left-3 w-24 h-24 bg-yellow-400/20 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 text-center lg:text-left order-1 lg:order-2">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($titulo_participar); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    <?php echo esc_html($descripcion_participar); ?>
                </p>

                <ul class="space-y-3 mb-8 text-left max-w-md mx-auto lg:mx-0">
                    <?php foreach ($beneficios_participar as $beneficio_texto) : ?>
                        <li class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm"><?php echo esc_html($beneficio_texto); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="<?php echo esc_url($url_registro); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-600 text-white font-semibold text-lg hover:from-amber-600 hover:to-yellow-700 transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        <?php echo esc_html__('Registrarme', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url($url_crear_servicio); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-amber-300 text-amber-600 font-semibold text-lg hover:bg-amber-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php echo esc_html__('Ofrecer un Servicio', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
