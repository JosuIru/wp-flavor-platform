<?php
/**
 * Template: Hero Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto extraídos de $args o variables legacy
$titulo = $args['titulo'] ?? $titulo ?? __('Comparte Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = $args['subtitulo'] ?? $subtitulo ?? __('Transporte sostenible para tu ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN);
$imagen_fondo_id = $args['imagen_fondo_id'] ?? $imagen_fondo ?? null;
$mostrar_buscador = $args['mostrar_buscador'] ?? true;
$color_primario = $args['color_primario'] ?? '#3b82f6';
$color_secundario = $args['color_secundario'] ?? '#06b6d4';

$imagen_url = !empty($imagen_fondo_id) ? wp_get_attachment_image_url($imagen_fondo_id, 'full') : '';
?>

<section class="flavor-bicicletas-hero flavor-component relative min-h-screen flex items-center overflow-hidden" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url): ?>
            <img src="<?php echo esc_url($imagen_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, <?php echo esc_attr($color_primario); ?> 0%, <?php echo esc_attr($color_secundario); ?> 100%); opacity: 0.75;"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, <?php echo esc_attr($color_primario); ?> 0%, <?php echo esc_attr($color_secundario); ?> 100%);"></div>
        <?php endif; ?>

        <!-- Elementos decorativos de bicicletas -->
        <div class="absolute top-10 right-10 opacity-10 text-white text-9xl">🚲</div>
        <div class="absolute bottom-20 left-10 opacity-10 text-white text-9xl">🚴</div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20 w-full">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 text-white">
                🚲 <?php echo esc_html($titulo); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12 text-white/90">
                <?php echo esc_html($subtitulo); ?>
            </p>

            <?php if ($mostrar_buscador): ?>
            <div class="flavor-card max-w-3xl mx-auto shadow-2xl">
                <form class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-700">
                                <?php echo esc_html__('Ubicación Actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <input type="text"
                                   placeholder="<?php echo esc_attr__('Mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                   class="flavor-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-700">
                                <?php echo esc_html__('Estación Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <input type="text"
                                   placeholder="<?php echo esc_attr__('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                   class="flavor-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-700">
                                <?php echo esc_html__('Tipo de Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <select class="flavor-select w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    style="border-color: #e5e7eb;">
                                <option><?php echo esc_html__('Una sola vez', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option><?php echo esc_html__('Diario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option><?php echo esc_html__('Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="flavor-button flavor-button-primary w-full md:w-auto px-12 py-3" style="background-color: <?php echo esc_attr($color_primario); ?>;">
                        🔍 <?php echo esc_html__('Buscar Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </form>

                <!-- Estadísticas -->
                <div class="grid grid-cols-3 gap-6 mt-8 pt-8 border-t" style="border-color: #e5e7eb;">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">487</div>
                        <div class="text-sm text-gray-600">
                            <?php echo esc_html__('Bicicletas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">52</div>
                        <div class="text-sm text-gray-600">
                            <?php echo esc_html__('Estaciones activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">12,450</div>
                        <div class="text-sm text-gray-600">
                            <?php echo esc_html__('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
