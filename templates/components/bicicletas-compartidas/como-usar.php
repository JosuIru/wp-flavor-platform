<?php
/**
 * Template: Cómo Usar - Bicicletas Compartidas
 *
 * @package FlavorPlatform
 * @var array $args Parámetros opcionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$titulo = $args['titulo'] ?? __('Cómo Usar Nuestras Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = $args['subtitulo'] ?? __('Pasos sencillos para comenzar tu viaje', FLAVOR_PLATFORM_TEXT_DOMAIN);
$pasos = $args['pasos'] ?? [];
$mostrar_preguntas_frecuentes = $args['mostrar_preguntas_frecuentes'] ?? true;

// Datos de ejemplo si no hay pasos
if (empty($pasos)) {
    $pasos = [
        [
            'numero' => 1,
            'titulo' => __('Descarga la App', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Descarga nuestra aplicación móvil en Android o iOS e instálala en tu teléfono.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => '📱',
            'color' => '#3b82f6',
        ],
        [
            'numero' => 2,
            'titulo' => __('Regístrate', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Crea tu cuenta con tu email, teléfono o redes sociales. El proceso toma solo 2 minutos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => '📝',
            'color' => '#06b6d4',
        ],
        [
            'numero' => 3,
            'titulo' => __('Localiza una Estación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Usa el mapa para encontrar la estación más cercana con bicicletas disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => '📍',
            'color' => '#10b981',
        ],
        [
            'numero' => 4,
            'titulo' => __('Desbloquea la Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Escanea el código QR en la bicicleta o introduce el número de serie en la app.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => '🔓',
            'color' => '#f59e0b',
        ],
        [
            'numero' => 5,
            'titulo' => __('Disfruta tu Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('¡Monta y disfruta! La app registrará automáticamente el tiempo y distancia.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => '🚴',
            'color' => '#ec4899',
        ],
        [
            'numero' => 6,
            'titulo' => __('Devuelve la Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Regresa a cualquier estación, introduce la bicicleta en el soporte y ciérrala.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => '🏁',
            'color' => '#8b5cf6',
        ],
    ];
}
?>

<section class="flavor-bicicletas-como-usar flavor-component py-16 bg-gradient-to-b from-white to-gray-50">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                🚀 <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-xl text-gray-600">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </div>

        <!-- Pasos -->
        <div class="max-w-6xl mx-auto mb-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($pasos as $paso): ?>
                <div class="flavor-paso-tarjeta">
                    <!-- Número del paso -->
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-14 w-14 rounded-full text-white text-xl font-bold"
                                 style="background-color: <?php echo esc_attr($paso['color']); ?>;">
                                <?php echo (int)$paso['numero']; ?>
                            </div>
                        </div>
                        <div class="text-4xl ml-4">
                            <?php echo esc_html($paso['icono']); ?>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                        <?php echo esc_html($paso['titulo']); ?>
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        <?php echo esc_html($paso['descripcion']); ?>
                    </p>

                    <!-- Línea conectora (solo en desktop, excepto el último elemento) -->
                    <?php if ($paso['numero'] < count($pasos)): ?>
                    <div class="hidden lg:block absolute right-0 top-1/2 transform translate-x-1/2 -translate-y-1/2 w-8 h-0.5 bg-gradient-to-r from-gray-300 to-transparent"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Requisitos -->
        <div class="bg-blue-50 rounded-2xl p-8 mb-16">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">
                ✅ <?php echo esc_html__('Requisitos Previos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📱</span>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo esc_html__('Teléfono Inteligente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <p class="text-sm text-gray-600"><?php echo esc_html__('Android 6.0+ o iOS 11.0+', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💳</span>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo esc_html__('Tarjeta de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <p class="text-sm text-gray-600"><?php echo esc_html__('Para registrar datos de pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🆔</span>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo esc_html__('Documento de Identidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <p class="text-sm text-gray-600"><?php echo esc_html__('Mayor de 14 años', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📍</span>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo esc_html__('Localización GPS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <p class="text-sm text-gray-600"><?php echo esc_html__('Permisos de ubicación activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preguntas frecuentes -->
        <?php if ($mostrar_preguntas_frecuentes): ?>
        <div class="max-w-3xl mx-auto">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                ❓ <?php echo esc_html__('Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>

            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <details class="flavor-faq-item bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer">
                    <summary class="p-6 font-semibold text-gray-900 hover:bg-gray-50 flex items-center justify-between">
                        <span><?php echo esc_html__('¿Cuál es el coste por usar la bicicleta?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="text-gray-500">▼</span>
                    </summary>
                    <div class="px-6 pb-6 text-gray-600 border-t border-gray-200">
                        <?php echo esc_html__('El coste depende del tipo de bicicleta. Las urbanas cuestan €0.50/hora, las eléctricas €1.50/hora y las infantiles €0.30/hora. Hay también planes de suscripción mensuales con descuentos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </details>

                <!-- FAQ Item 2 -->
                <details class="flavor-faq-item bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer">
                    <summary class="p-6 font-semibold text-gray-900 hover:bg-gray-50 flex items-center justify-between">
                        <span><?php echo esc_html__('¿Dónde puedo dejar la bicicleta?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="text-gray-500">▼</span>
                    </summary>
                    <div class="px-6 pb-6 text-gray-600 border-t border-gray-200">
                        <?php echo esc_html__('Puedes devolver la bicicleta en cualquiera de nuestras 52 estaciones repartidas por la ciudad. Simplemente colócala en el soporte y ciérrala.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </details>

                <!-- FAQ Item 3 -->
                <details class="flavor-faq-item bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer">
                    <summary class="p-6 font-semibold text-gray-900 hover:bg-gray-50 flex items-center justify-between">
                        <span><?php echo esc_html__('¿Qué pasa si daño la bicicleta?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="text-gray-500">▼</span>
                    </summary>
                    <div class="px-6 pb-6 text-gray-600 border-t border-gray-200">
                        <?php echo esc_html__('Los daños accidentales menores están cubiertos. Para daños graves, se puede aplicar una tarifa de reparación. Los robos están cubiertos por seguro si se reportan en 24 horas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </details>

                <!-- FAQ Item 4 -->
                <details class="flavor-faq-item bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer">
                    <summary class="p-6 font-semibold text-gray-900 hover:bg-gray-50 flex items-center justify-between">
                        <span><?php echo esc_html__('¿Es seguro usar la bicicleta?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="text-gray-500">▼</span>
                    </summary>
                    <div class="px-6 pb-6 text-gray-600 border-t border-gray-200">
                        <?php echo esc_html__('Todas nuestras bicicletas se revisan diariamente y cumplen estándares de seguridad. Recomendamos usar casco (disponible en estaciones) y respetar las normas de tráfico.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </details>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-paso-tarjeta {
    position: relative;
    padding: 2rem;
    background: white;
    border-radius: 1.5rem;
    border: 2px solid #f3f4f6;
    transition: all 0.3s ease;
}

.flavor-paso-tarjeta:hover {
    border-color: #3b82f6;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.flavor-faq-item {
    transition: all 0.3s ease;
}

.flavor-faq-item:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.flavor-faq-item[open] summary {
    background-color: #f3f4f6;
}

.flavor-faq-item[open] summary span:last-child {
    transform: rotate(180deg);
}

.flavor-faq-item summary span:last-child {
    transition: transform 0.3s ease;
}
</style>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Aquí se pueden agregar interacciones adicionales si es necesario
        console.log('Template de Cómo Usar cargado correctamente');
    });
})();
</script>
