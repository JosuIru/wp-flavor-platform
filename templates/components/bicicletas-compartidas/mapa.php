<?php
/**
 * Template: Mapa de Estaciones - Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$titulo = $args['titulo'] ?? __('Estaciones de Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = $args['subtitulo'] ?? __('Encuentra la estación más cercana', FLAVOR_PLATFORM_TEXT_DOMAIN);
$estaciones = $args['estaciones'] ?? [];
$altura_mapa = $args['altura_mapa'] ?? '500px';
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$api_key_maps = $args['api_key_maps'] ?? '';

// Datos de ejemplo si no hay estaciones
if (empty($estaciones)) {
    $estaciones = [
        [
            'id' => 1,
            'nombre' => 'Estación Central',
            'direccion' => 'Plaza Mayor, 15',
            'latitud' => 40.4158,
            'longitud' => -3.7035,
            'bicicletas_disponibles' => 12,
            'total_bicicletas' => 25,
            'abierta' => true,
        ],
        [
            'id' => 2,
            'nombre' => 'Estación Parque',
            'direccion' => 'Calle del Parque, 8',
            'latitud' => 40.4166,
            'longitud' => -3.7035,
            'bicicletas_disponibles' => 8,
            'total_bicicletas' => 20,
            'abierta' => true,
        ],
        [
            'id' => 3,
            'nombre' => 'Estación Mercado',
            'direccion' => 'Avenida Mercado, 42',
            'latitud' => 40.4158,
            'longitud' => -3.6900,
            'bicicletas_disponibles' => 5,
            'total_bicicletas' => 15,
            'abierta' => true,
        ],
    ];
}
?>

<section class="flavor-bicicletas-mapa flavor-component py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                📍 <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-xl text-gray-600">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </div>

        <!-- Controles de filtro -->
        <?php if ($mostrar_filtros): ?>
        <div class="flavor-bicicletas-filtros mb-8 flex flex-wrap gap-3 justify-center">
            <button class="flavor-filtro-btn active px-4 py-2 rounded-full bg-blue-500 text-white font-medium" data-filtro="todas">
                <?php echo esc_html__('Todas las estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button class="flavor-filtro-btn px-4 py-2 rounded-full bg-white text-gray-700 font-medium border border-gray-300" data-filtro="disponibles">
                <?php echo esc_html__('Con bicicletas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button class="flavor-filtro-btn px-4 py-2 rounded-full bg-white text-gray-700 font-medium border border-gray-300" data-filtro="cercanas">
                <?php echo esc_html__('Cerca de mí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php endif; ?>

        <!-- Contenedor del mapa -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Mapa -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div id="flavor-mapa-bicicletas" class="flavor-mapa w-full" style="height: <?php echo esc_attr($altura_mapa); ?>;">
                        <!-- El mapa se renderiza aquí con JavaScript -->
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    💡 <?php echo esc_html__('Haz clic en cualquier marcador para ver los detalles de la estación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Panel lateral de estaciones -->
            <div class="bg-white rounded-2xl shadow-lg p-6 overflow-y-auto" style="max-height: 600px;">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html__('Estaciones Cercanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <div class="space-y-4 flavor-estaciones-lista">
                    <?php foreach ($estaciones as $estacion): ?>
                    <div class="flavor-estacion-card p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all cursor-pointer"
                         data-estacion-id="<?php echo esc_attr($estacion['id']); ?>"
                         data-latitud="<?php echo esc_attr($estacion['latitud']); ?>"
                         data-longitud="<?php echo esc_attr($estacion['longitud']); ?>">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-900">
                                <?php echo esc_html($estacion['nombre']); ?>
                            </h4>
                            <?php if ($estacion['abierta']): ?>
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">
                                <?php echo esc_html__('Abierta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                                <?php echo esc_html__('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-sm text-gray-600 mb-3">
                            📍 <?php echo esc_html($estacion['direccion']); ?>
                        </p>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="text-2xl font-bold text-blue-600">
                                    <?php echo (int)$estacion['bicicletas_disponibles']; ?>
                                </div>
                                <div class="text-xs text-gray-600">
                                    <?php echo esc_html__('de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <br>
                                    <?php echo (int)$estacion['total_bicicletas']; ?>
                                </div>
                            </div>
                            <div class="flex-1 ml-3">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full"
                                         style="width: <?php echo (int)(($estacion['bicicletas_disponibles'] / $estacion['total_bicicletas']) * 100); ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo count($estaciones); ?></div>
                <p class="text-gray-700"><?php echo esc_html__('Estaciones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="bg-green-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-green-600 mb-2">487</div>
                <p class="text-gray-700"><?php echo esc_html__('Bicicletas en servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-purple-600 mb-2">24/7</div>
                <p class="text-gray-700"><?php echo esc_html__('Servicio disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    'use strict';

    // Datos de estaciones
    const estaciones = <?php echo wp_json_encode($estaciones); ?>;

    // Inicializar mapa (básico con lista)
    document.addEventListener('DOMContentLoaded', function() {
        const estacionesLista = document.querySelectorAll('.flavor-estacion-card');

        estacionesLista.forEach(card => {
            card.addEventListener('click', function() {
                const estacionId = this.dataset.estacionId;
                // Resaltar estación seleccionada
                estacionesLista.forEach(c => c.classList.remove('bg-blue-50'));
                this.classList.add('bg-blue-50');
            });
        });

        // Filtros
        const filtros = document.querySelectorAll('.flavor-filtro-btn');
        filtros.forEach(filtro => {
            filtro.addEventListener('click', function() {
                filtros.forEach(f => f.classList.remove('bg-blue-500', 'text-white'));
                this.classList.add('bg-blue-500', 'text-white');

                const tipoFiltro = this.dataset.filtro;
                // Aquí irá la lógica de filtrado
                console.log('Filtrando por:', tipoFiltro);
            });
        });
    });
})();
</script>
