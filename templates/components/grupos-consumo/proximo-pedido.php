<?php
/**
 * Template: Widget Próximo Pedido
 *
 * Widget que muestra información sobre el próximo pedido del grupo:
 * fecha de cierre, fecha de entrega, productos disponibles y progreso del pedido.
 *
 * @var string $titulo_widget Título del widget
 * @var string $fecha_cierre Fecha límite para realizar el pedido
 * @var string $fecha_entrega Fecha de entrega del pedido
 * @var int    $productos_disponibles Cantidad de productos disponibles
 * @var int    $miembros_pedido Cantidad de miembros que han hecho pedido
 * @var int    $miembros_totales Total de miembros del grupo
 * @var string $url_realizar_pedido URL para realizar un pedido
 * @var string $component_classes Clases CSS adicionales para el componente
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_widget            = $titulo_widget ?? 'Próximo Pedido';
$fecha_cierre             = $fecha_cierre ?? date('Y-m-d', strtotime('+5 days'));
$fecha_entrega            = $fecha_entrega ?? date('Y-m-d', strtotime('+12 days'));
$productos_disponibles    = $productos_disponibles ?? 45;
$miembros_pedido          = $miembros_pedido ?? 16;
$miembros_totales         = $miembros_totales ?? 24;
$url_realizar_pedido      = $url_realizar_pedido ?? '/grupos-consumo/realizar-pedido/';
$component_classes        = $component_classes ?? '';

// Calcular progreso
$porcentaje_progreso = ($miembros_totales > 0) ? round(($miembros_pedido / $miembros_totales) * 100) : 0;

// Calcular días restantes
$dias_restantes = floor((strtotime($fecha_cierre) - current_time('timestamp')) / (60 * 60 * 24));
$dias_restantes = max(0, $dias_restantes);
?>
<div class="flavor-component flavor-proximo-pedido <?php echo esc_attr($component_classes); ?>">
    <div class="max-w-xl mx-auto">
        <div class="rounded-2xl overflow-hidden shadow-xl bg-white">
            <!-- Header degradado -->
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 lg:px-8 pt-8 pb-6">
                <h3 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                    <?php echo esc_html($titulo_widget); ?>
                </h3>
                <p class="text-green-100 text-sm">
                    <?php echo esc_html__('Grupo Centro - Próxima entrega', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- Contenido -->
            <div class="p-6 lg:p-8 space-y-8">
                <!-- Fechas importantes -->
                <div class="grid grid-cols-2 gap-6">
                    <!-- Fecha de cierre -->
                    <div class="flavor-fecha-item">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    <?php echo esc_html__('Cierre del pedido', 'flavor-chat-ia'); ?>
                                </p>
                                <p class="text-lg font-bold text-gray-900">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha_cierre))); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?php
                                    if ($dias_restantes > 0) {
                                        echo esc_html(sprintf(_n('%d día restante', '%d días restantes', $dias_restantes, 'flavor-chat-ia'), $dias_restantes));
                                    } else {
                                        echo esc_html__('Pedido cerrado', 'flavor-chat-ia');
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Fecha de entrega -->
                    <div class="flavor-fecha-item">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    <?php echo esc_html__('Fecha de entrega', 'flavor-chat-ia'); ?>
                                </p>
                                <p class="text-lg font-bold text-gray-900">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha_entrega))); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?php echo esc_html__('Entre 17:00 y 20:00', 'flavor-chat-ia'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Divisor -->
                <div class="h-px bg-gray-200"></div>

                <!-- Información de productos -->
                <div class="space-y-4">
                    <h4 class="text-lg font-bold text-gray-900">
                        <?php echo esc_html__('Información del Pedido', 'flavor-chat-ia'); ?>
                    </h4>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Productos disponibles -->
                        <div class="rounded-xl bg-blue-50 p-4 border border-blue-200">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <p class="text-xs font-semibold text-blue-600 uppercase">
                                    <?php echo esc_html__('Productos', 'flavor-chat-ia'); ?>
                                </p>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo esc_html($productos_disponibles); ?>
                            </p>
                        </div>

                        <!-- Miembros participantes -->
                        <div class="rounded-xl bg-purple-50 p-4 border border-purple-200">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.682"/>
                                </svg>
                                <p class="text-xs font-semibold text-purple-600 uppercase">
                                    <?php echo esc_html__('Participantes', 'flavor-chat-ia'); ?>
                                </p>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo esc_html($miembros_pedido); ?>/<span class="text-gray-500"><?php echo esc_html($miembros_totales); ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progreso de participación -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-700">
                            <?php echo esc_html__('Participación del grupo', 'flavor-chat-ia'); ?>
                        </p>
                        <p class="text-sm font-bold text-green-600">
                            <?php echo esc_html($porcentaje_progreso); ?>%
                        </p>
                    </div>
                    <div class="w-full h-3 rounded-full bg-gray-200 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500 bg-gradient-to-r from-green-500 to-emerald-600"
                            style="width: <?php echo esc_attr($porcentaje_progreso); ?>%;"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-600">
                        <?php echo esc_html(sprintf(__('%d miembros de %d ya han realizado su pedido', 'flavor-chat-ia'), $miembros_pedido, $miembros_totales)); ?>
                    </p>
                </div>

                <!-- Divisor -->
                <div class="h-px bg-gray-200"></div>

                <!-- Botones de acción -->
                <div class="space-y-3">
                    <?php if ($dias_restantes > 0) : ?>
                        <a
                            href="<?php echo esc_url($url_realizar_pedido); ?>"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-green-600 text-white font-semibold text-lg hover:bg-green-700 transition-all shadow-lg hover:shadow-xl"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                            <?php echo esc_html__('Realizar Pedido', 'flavor-chat-ia'); ?>
                        </a>
                    <?php else : ?>
                        <button disabled class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-gray-300 text-gray-600 font-semibold text-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo esc_html__('Pedido Cerrado', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>

                    <a
                        href="<?php echo esc_url(apply_filters('flavor_ver_productos_url', '/grupos-consumo/productos/')); ?>"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl border-2 border-green-600 text-green-600 font-semibold hover:bg-green-50 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <?php echo esc_html__('Ver Productos Disponibles', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
