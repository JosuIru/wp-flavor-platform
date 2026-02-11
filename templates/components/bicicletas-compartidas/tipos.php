<?php
/**
 * Template: Tipos de Bicicletas - Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$titulo = $args['titulo'] ?? __('Elige tu Bicicleta', 'flavor-chat-ia');
$subtitulo = $args['subtitulo'] ?? __('Selecciona el modelo que mejor se adapta a tu viaje', 'flavor-chat-ia');
$tipos = $args['tipos'] ?? [];

// Datos de ejemplo si no hay tipos
if (empty($tipos)) {
    $tipos = [
        [
            'id' => 1,
            'nombre' => 'Bicicleta Urbana',
            'descripcion' => 'Perfecta para viajes cortos en la ciudad. Cómoda y fácil de manejar.',
            'icono' => '🚲',
            'color' => '#3b82f6',
            'caracteristicas' => [
                __('Ruedas de 28 pulgadas', 'flavor-chat-ia'),
                __('Cambios: 21 velocidades', 'flavor-chat-ia'),
                __('Cesta incluida', 'flavor-chat-ia'),
                __('Peso: 15 kg', 'flavor-chat-ia'),
            ],
            'precio_por_hora' => '€0.50',
            'disponibles' => 234,
        ],
        [
            'id' => 2,
            'nombre' => 'Bicicleta Eléctrica',
            'descripcion' => 'Motor de asistencia para viajes más largos. Ideal para distancias mayores.',
            'icono' => '⚡',
            'color' => '#10b981',
            'caracteristicas' => [
                __('Motor eléctrico 250W', 'flavor-chat-ia'),
                __('Batería: 50 km de autonomía', 'flavor-chat-ia'),
                __('Cambios: 8 velocidades', 'flavor-chat-ia'),
                __('Peso: 24 kg', 'flavor-chat-ia'),
            ],
            'precio_por_hora' => '€1.50',
            'disponibles' => 87,
        ],
        [
            'id' => 3,
            'nombre' => 'Bicicleta Infantil',
            'descripcion' => 'Diseñada para niños de 5 a 12 años. Segura y estable.',
            'icono' => '🎨',
            'color' => '#f59e0b',
            'caracteristicas' => [
                __('Ruedas de 20 pulgadas', 'flavor-chat-ia'),
                __('Frenos de seguridad mejorados', 'flavor-chat-ia'),
                __('Asiento ajustable', 'flavor-chat-ia'),
                __('Peso: 12 kg', 'flavor-chat-ia'),
            ],
            'precio_por_hora' => '€0.30',
            'disponibles' => 156,
        ],
    ];
}
?>

<section class="flavor-bicicletas-tipos flavor-component py-16 bg-white">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-xl text-gray-600">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </div>

        <!-- Grid de tipos de bicicletas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($tipos as $tipo): ?>
            <div class="flavor-tipo-bicicleta group">
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 h-full flex flex-col">
                    <!-- Encabezado con color -->
                    <div class="h-24 flex items-center justify-center text-6xl"
                         style="background: linear-gradient(135deg, <?php echo esc_attr($tipo['color']); ?> 0%, <?php echo esc_attr($tipo['color']); ?>dd 100%);">
                        <?php echo esc_html($tipo['icono']); ?>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6 flex-1 flex flex-col">
                        <!-- Nombre -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            <?php echo esc_html($tipo['nombre']); ?>
                        </h3>

                        <!-- Descripción -->
                        <p class="text-gray-600 mb-4 flex-1">
                            <?php echo esc_html($tipo['descripcion']); ?>
                        </p>

                        <!-- Características -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-sm font-semibold text-gray-700 mb-3">
                                ✓ <?php echo esc_html__('Características', 'flavor-chat-ia'); ?>
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ($tipo['caracteristicas'] as $caracteristica): ?>
                                <li class="text-sm text-gray-600 flex items-start gap-2">
                                    <span class="text-green-500 font-bold mt-0.5">•</span>
                                    <span><?php echo esc_html($caracteristica); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Pie de tarjeta -->
                        <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-600">
                                    <?php echo esc_html__('Precio por hora', 'flavor-chat-ia'); ?>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">
                                    <?php echo esc_html($tipo['precio_por_hora']); ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">
                                    <?php echo esc_html__('Disponibles', 'flavor-chat-ia'); ?>
                                </div>
                                <div class="text-2xl font-bold" style="color: <?php echo esc_attr($tipo['color']); ?>;">
                                    <?php echo (int)$tipo['disponibles']; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de acción -->
                        <button class="flavor-button flavor-button-primary w-full mt-4 py-3 rounded-lg font-semibold transition-all"
                                style="background-color: <?php echo esc_attr($tipo['color']); ?>;"
                                data-tipo-id="<?php echo esc_attr($tipo['id']); ?>">
                            <?php echo esc_html__('Seleccionar', 'flavor-chat-ia'); ?> →
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Información adicional -->
        <div class="mt-16 bg-blue-50 rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">
                💡 <?php echo esc_html__('¿Cómo elegir la bicicleta adecuada?', 'flavor-chat-ia'); ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Viajes Cortos (< 5 km)', 'flavor-chat-ia'); ?>
                    </h4>
                    <p class="text-gray-600">
                        <?php echo esc_html__('Elige una bicicleta urbana. Son cómodas, ligeras y perfectas para paseos rápidos por la ciudad.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Viajes Medianos (5-15 km)', 'flavor-chat-ia'); ?>
                    </h4>
                    <p class="text-gray-600">
                        <?php echo esc_html__('Una bicicleta eléctrica te permitirá llegar más rápido y sin esfuerzo excesivo.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Viajes en Familia', 'flavor-chat-ia'); ?>
                    </h4>
                    <p class="text-gray-600">
                        <?php echo esc_html__('Para los más pequeños, nuestras bicicletas infantiles son seguras y cómodas.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const botones = document.querySelectorAll('[data-tipo-id]');

        botones.forEach(boton => {
            boton.addEventListener('click', function() {
                const tipoId = this.dataset.tipoId;
                console.log('Seleccionado tipo de bicicleta:', tipoId);
                // Aquí iría la lógica para proceder con la selección
            });
        });
    });
})();
</script>
