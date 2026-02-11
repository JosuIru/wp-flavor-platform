<?php
/**
 * Frontend: Archive de Carpooling
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$viajes = $viajes ?? [];
$total_viajes = $total_viajes ?? 0;
$estadisticas = $estadisticas ?? [];
$destinos_populares = $destinos_populares ?? [];
?>

<div class="flavor-frontend flavor-carpooling-archive">
    <!-- Header con gradiente verde lima -->
    <div class="bg-gradient-to-r from-lime-500 to-green-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">🚗 <?php echo esc_html__('Carpooling', 'flavor-chat-ia'); ?></h1>
                <p class="text-lime-100"><?php echo esc_html__('Comparte viajes, reduce costes y cuida el medio ambiente', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php
                    echo esc_html(
                        sprintf(
                            _n('%d viaje disponible', '%d viajes disponibles', (int) $total_viajes, 'flavor-chat-ia'),
                            (int) $total_viajes
                        )
                    );
                    ?>
                </span>
                <button class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-lime-50 transition-all shadow-md"
                        onclick="flavorCarpooling.ofrecerViaje()">
                    ➕ <?php echo esc_html__('Ofrecer Viaje', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🚗</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['viajes_disponibles'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Viajes disponibles', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💺</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['plazas_libres'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Plazas libres', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🌿</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['co2_ahorrado'] ?? '0 kg'); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('CO2 ahorrado', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📏</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['km_compartidos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Km compartidos', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-lime-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">💡 <?php echo esc_html__('¿Como funciona?', 'flavor-chat-ia'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Busca', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Encuentra viajes disponibles con tu mismo destino y horario', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📩</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Reserva', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Solicita tu plaza y espera la confirmacion del conductor', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🚗</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Viaja', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Comparte el viaje, ahorra dinero y reduce tu huella de carbono', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filtros rapidos por destino -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-lime-100 text-lime-700 font-medium hover:bg-lime-200 transition-colors filter-active" data-destino="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($destinos_populares as $destino_popular): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-destino="<?php echo esc_attr($destino_popular['slug']); ?>">
            📍 <?php echo esc_html($destino_popular['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de viajes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($viajes)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🚗</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay viajes disponibles', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('¡Ofrece el primer viaje compartido!', 'flavor-chat-ia'); ?></p>
            <button class="bg-lime-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors"
                    onclick="flavorCarpooling.ofrecerViaje()">
                <?php echo esc_html__('Ofrecer Viaje', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($viajes as $viaje): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Ruta -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex flex-col items-center">
                        <div class="w-3 h-3 rounded-full bg-lime-500"></div>
                        <div class="w-0.5 h-8 bg-lime-300"></div>
                        <div class="w-3 h-3 rounded-full bg-green-600"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($viaje['origen'] ?? ''); ?></p>
                        <div class="h-4"></div>
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($viaje['destino'] ?? ''); ?></p>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-green-600 transition-colors">
                    <a href="<?php echo esc_url($viaje['url'] ?? '#'); ?>">
                        <?php echo esc_html($viaje['origen'] ?? ''); ?> → <?php echo esc_html($viaje['destino'] ?? ''); ?>
                    </a>
                </h3>

                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        📅 <?php echo esc_html($viaje['fecha'] ?? ''); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        🕐 <?php echo esc_html($viaje['hora'] ?? ''); ?>
                    </span>
                    <span class="bg-lime-100 text-lime-700 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        💺 <?php echo esc_html($viaje['plazas_libres'] ?? 0); ?> <?php echo esc_html__('plazas', 'flavor-chat-ia'); ?>
                    </span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($viaje['conductor_nombre'] ?? 'C', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo esc_html($viaje['conductor_nombre'] ?? __('Conductor', 'flavor-chat-ia')); ?></span>
                    </div>
                    <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-full text-sm">
                        <?php echo esc_html($viaje['precio'] ?? '0'); ?> €
                    </span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_viajes > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← <?php echo esc_html__('Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">
                <?php
                echo esc_html(
                    sprintf(
                        __('Pagina 1 de %d', 'flavor-chat-ia'),
                        (int) ceil($total_viajes / 12)
                    )
                );
                ?>
            </span>
            <button class="px-4 py-2 rounded-lg bg-lime-500 text-white hover:bg-lime-600 transition-colors"><?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?> →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
