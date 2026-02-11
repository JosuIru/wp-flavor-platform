<?php
/**
 * Template: Grid de Grupos de Consumo
 *
 * Muestra un grid con todos los grupos de consumo disponibles.
 * Cada card muestra nombre, imagen, cantidad de miembros y fecha del próximo pedido.
 *
 * @var array  $grupos_disponibles Array con datos de los grupos
 * @var string $url_detalles_grupo URL para ver detalles de un grupo
 * @var string $component_classes Clases CSS adicionales para el componente
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$grupos_disponibles = $grupos_disponibles ?? [
    [
        'id'               => 1,
        'nombre'           => 'Grupo Centro',
        'imagen'           => '',
        'miembros'         => 24,
        'proximo_pedido'   => '2024-02-15',
        'productos_activos' => 45,
    ],
    [
        'id'               => 2,
        'nombre'           => 'Grupo Periférico',
        'imagen'           => '',
        'miembros'         => 18,
        'proximo_pedido'   => '2024-02-16',
        'productos_activos' => 38,
    ],
    [
        'id'               => 3,
        'nombre'           => 'Grupo Universidad',
        'imagen'           => '',
        'miembros'         => 35,
        'proximo_pedido'   => '2024-02-17',
        'productos_activos' => 52,
    ],
];

$url_detalles_grupo = $url_detalles_grupo ?? '/grupos-consumo/grupo/';
$component_classes  = $component_classes ?? '';
?>
<section class="flavor-component flavor-grupos-grid py-16 lg:py-24 <?php echo esc_attr($component_classes); ?>">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="max-w-3xl mx-auto text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html__('Nuestros Grupos de Consumo', 'flavor-chat-ia'); ?>
            </h2>
            <p class="text-lg text-gray-600">
                <?php echo esc_html__('Encuentra el grupo que mejor se adapte a tus necesidades y comienza a disfrutar de productos locales', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Grid de grupos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <?php foreach ($grupos_disponibles as $grupo_item) : ?>
                <div class="flavor-grupo-card rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 bg-white group">
                    <!-- Imagen del grupo -->
                    <div class="relative h-48 bg-gradient-to-br from-green-100 to-green-200 overflow-hidden">
                        <?php if (!empty($grupo_item['imagen'])) : ?>
                            <img
                                src="<?php echo esc_url($grupo_item['imagen']); ?>"
                                alt="<?php echo esc_attr($grupo_item['nombre']); ?>"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                            />
                        <?php else : ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-24 h-24 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Badge con cantidad de miembros -->
                        <div class="absolute top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-full flex items-center gap-2 shadow-lg">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 6a9 9 0 11-18 0 9 9 0 0118 0zM14.5 9a.5.5 0 00-.5.5v5a.5.5 0 001 0v-5a.5.5 0 00-.5-.5z"/>
                            </svg>
                            <span class="font-semibold text-sm"><?php echo esc_html($grupo_item['miembros']); ?></span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($grupo_item['nombre']); ?>
                        </h3>

                        <!-- Información del grupo -->
                        <div class="space-y-3 mb-6">
                            <!-- Productos activos -->
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span><?php echo esc_html($grupo_item['productos_activos']); ?> <?php echo esc_html__('productos activos', 'flavor-chat-ia'); ?></span>
                            </div>

                            <!-- Próximo pedido -->
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span><?php echo esc_html__('Próximo pedido: ', 'flavor-chat-ia'); ?><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($grupo_item['proximo_pedido']))); ?></span>
                            </div>
                        </div>

                        <!-- Botón de acción -->
                        <a
                            href="<?php echo esc_url($url_detalles_grupo . $grupo_item['id'] . '/'); ?>"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700 transition-colors"
                        >
                            <?php echo esc_html__('Ver Detalles', 'flavor-chat-ia'); ?>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
