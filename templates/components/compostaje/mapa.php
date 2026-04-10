<?php
/**
 * Template: Mapa de Puntos de Compostaje
 *
 * @package FlavorPlatform
 * @var array $args Parámetros opcionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$titulo = $args['titulo'] ?? __('Puntos de Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = $args['subtitulo'] ?? __('Encuentra el punto más cercano para compostar', FLAVOR_PLATFORM_TEXT_DOMAIN);
$puntos_compostaje = $args['puntos_compostaje'] ?? [];
$altura_mapa = $args['altura_mapa'] ?? '500px';
$mostrar_filtros = $args['mostrar_filtros'] ?? true;

// Datos de ejemplo si no hay puntos
if (empty($puntos_compostaje)) {
    $puntos_compostaje = [
        [
            'id' => 1,
            'nombre' => 'Centro Compostaje Municipal',
            'direccion' => 'Calle Sostenibilidad, 10',
            'latitud' => 40.4158,
            'longitud' => -3.7035,
            'horario' => '8:00 - 20:00',
            'tipos_aceptados' => ['residuos_verdes', 'restos_comida', 'papel'],
            'capacidad_actual' => 75,
            'contacto' => '+34 900 123 456',
            'abierta' => true,
        ],
        [
            'id' => 2,
            'nombre' => 'Huerto Urbano Verde',
            'direccion' => 'Parque Central, 5',
            'latitud' => 40.4166,
            'longitud' => -3.7035,
            'horario' => '9:00 - 18:00',
            'tipos_aceptados' => ['residuos_verdes', 'restos_comida'],
            'capacidad_actual' => 45,
            'contacto' => '+34 900 234 567',
            'abierta' => true,
        ],
        [
            'id' => 3,
            'nombre' => 'Punto Verde Barrio',
            'direccion' => 'Plaza del Barrio, 3',
            'latitud' => 40.4158,
            'longitud' => -3.6900,
            'horario' => '7:00 - 21:00',
            'tipos_aceptados' => ['residuos_verdes', 'papel', 'carton'],
            'capacidad_actual' => 60,
            'contacto' => '+34 900 345 678',
            'abierta' => true,
        ],
    ];
}

// Definir colores por tipo de residuo
$colores_residuos = [
    'residuos_verdes' => '#10b981',
    'restos_comida' => '#f59e0b',
    'papel' => '#3b82f6',
    'carton' => '#8b5cf6',
];
?>

<section class="flavor-compostaje-mapa flavor-component py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                ♻️ <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-xl text-gray-600">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </div>

        <!-- Filtros -->
        <?php if ($mostrar_filtros): ?>
        <div class="flavor-compostaje-filtros mb-8 flex flex-wrap gap-3 justify-center">
            <button class="flavor-filtro-btn active px-4 py-2 rounded-full bg-green-500 text-white font-medium" data-filtro="todas">
                <?php echo esc_html__('Todos los puntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button class="flavor-filtro-btn px-4 py-2 rounded-full bg-white text-gray-700 font-medium border border-gray-300" data-filtro="abiertos">
                <?php echo esc_html__('Abiertos ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button class="flavor-filtro-btn px-4 py-2 rounded-full bg-white text-gray-700 font-medium border border-gray-300" data-filtro="cercanos">
                <?php echo esc_html__('Más cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php endif; ?>

        <!-- Contenedor del mapa -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Mapa -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div id="flavor-mapa-compostaje" class="flavor-mapa w-full" style="height: <?php echo esc_attr($altura_mapa); ?>;">
                        <!-- El mapa se renderiza aquí con JavaScript -->
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    💡 <?php echo esc_html__('Haz clic en cualquier punto para ver los detalles y horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Panel lateral de puntos -->
            <div class="bg-white rounded-2xl shadow-lg p-6 overflow-y-auto" style="max-height: 600px;">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html__('Puntos Cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <div class="space-y-4 flavor-puntos-lista">
                    <?php foreach ($puntos_compostaje as $punto): ?>
                    <div class="flavor-punto-card p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all cursor-pointer"
                         data-punto-id="<?php echo esc_attr($punto['id']); ?>"
                         data-latitud="<?php echo esc_attr($punto['latitud']); ?>"
                         data-longitud="<?php echo esc_attr($punto['longitud']); ?>">

                        <!-- Encabezado -->
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-900">
                                <?php echo esc_html($punto['nombre']); ?>
                            </h4>
                            <?php if ($punto['abierta']): ?>
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">
                                <?php echo esc_html__('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                                <?php echo esc_html__('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Información -->
                        <p class="text-sm text-gray-600 mb-2">
                            📍 <?php echo esc_html($punto['direccion']); ?>
                        </p>
                        <p class="text-sm text-gray-600 mb-3">
                            🕐 <?php echo esc_html($punto['horario']); ?>
                        </p>

                        <!-- Capacidad -->
                        <div class="mb-3">
                            <div class="text-xs text-gray-600 mb-1">
                                <?php echo esc_html__('Capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full"
                                     style="width: <?php echo (int)$punto['capacidad_actual']; ?>%;"></div>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                <?php echo (int)$punto['capacidad_actual']; ?>% <?php echo esc_html__('lleno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>

                        <!-- Tipos aceptados -->
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-700 mb-2">
                                ✓ <?php echo esc_html__('Acepta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <?php
                                $tipos_labels = [
                                    'residuos_verdes' => __('Residuos Verdes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'restos_comida' => __('Restos de Comida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'papel' => __('Papel', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'carton' => __('Cartón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                ];
                                foreach ($punto['tipos_aceptados'] as $tipo):
                                    $color = $colores_residuos[$tipo] ?? '#6b7280';
                                ?>
                                <span class="text-xs px-2 py-1 rounded-full text-white"
                                      style="background-color: <?php echo esc_attr($color); ?>;">
                                    <?php echo esc_html($tipos_labels[$tipo] ?? $tipo); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Botón contacto -->
                        <a href="tel:<?php echo esc_attr(str_replace(' ', '', $punto['contacto'])); ?>"
                           class="text-xs text-blue-600 font-medium hover:text-blue-700">
                            📞 <?php echo esc_html($punto['contacto']); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-green-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-green-600 mb-2"><?php echo count($puntos_compostaje); ?></div>
                <p class="text-gray-700"><?php echo esc_html__('Puntos de compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="bg-blue-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2">24/7</div>
                <p class="text-gray-700"><?php echo esc_html__('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-purple-600 mb-2">4+</div>
                <p class="text-gray-700"><?php echo esc_html__('Tipos de residuos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    'use strict';

    // Datos de puntos de compostaje
    const puntosCompostaje = <?php echo wp_json_encode($puntos_compostaje); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const puntosLista = document.querySelectorAll('.flavor-punto-card');

        puntosLista.forEach(card => {
            card.addEventListener('click', function() {
                const puntoId = this.dataset.puntoId;
                // Resaltar punto seleccionado
                puntosLista.forEach(c => c.classList.remove('bg-green-50'));
                this.classList.add('bg-green-50');
            });
        });

        // Filtros
        const filtros = document.querySelectorAll('.flavor-filtro-btn');
        filtros.forEach(filtro => {
            filtro.addEventListener('click', function() {
                filtros.forEach(f => f.classList.remove('bg-green-500', 'text-white'));
                this.classList.add('bg-green-500', 'text-white');

                const tipoFiltro = this.dataset.filtro;
                console.log('Filtrando por:', tipoFiltro);
            });
        });
    });
})();
</script>
