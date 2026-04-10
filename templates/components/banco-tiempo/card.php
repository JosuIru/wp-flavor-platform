<?php
/**
 * Componente: Card de Servicio Banco de Tiempo
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$servicio = $item ?? $card_item ?? [];
if (empty($servicio)) return;

$id = $servicio['id'] ?? 0;
$titulo = $servicio['titulo'] ?? $servicio['title'] ?? '';
$descripcion = $servicio['descripcion'] ?? '';
$url = $servicio['url'] ?? '#';
$tipo = $servicio['tipo'] ?? 'oferta';
$horas = $servicio['horas'] ?? 1;
$categoria = $servicio['categoria'] ?? __('General', FLAVOR_PLATFORM_TEXT_DOMAIN);
$usuario_nombre = $servicio['usuario_nombre'] ?? __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
$usuario_valoracion = $servicio['usuario_valoracion'] ?? '5.0';
$usuario_intercambios = $servicio['usuario_intercambios'] ?? 0;
$usuario_inicial = mb_substr($usuario_nombre, 0, 1);

$tipo_class = $tipo === 'oferta' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700';
$tipo_label = $tipo === 'oferta' ? '🎁 ' . __('Ofrezco', FLAVOR_PLATFORM_TEXT_DOMAIN) : '🙋 ' . __('Busco', FLAVOR_PLATFORM_TEXT_DOMAIN);
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-tipo="<?php echo esc_attr($tipo); ?>"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-6">
        <!-- Tipo y horas -->
        <div class="flex items-center justify-between mb-3">
            <span class="<?php echo esc_attr($tipo_class); ?> text-xs font-medium px-3 py-1 rounded-full">
                <?php echo esc_html($tipo_label); ?>
            </span>
            <span class="text-violet-600 font-bold"><?php echo esc_html($horas); ?>h</span>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-violet-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($titulo); ?></a>
        </h3>

        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <!-- Usuario -->
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-medium">
                <?php echo esc_html($usuario_inicial); ?>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-800"><?php echo esc_html($usuario_nombre); ?></p>
                <div class="flex items-center gap-1 text-xs text-gray-500">
                    <span>⭐ <?php echo esc_html($usuario_valoracion); ?></span>
                    <span>•</span>
                    <span><?php echo esc_html($usuario_intercambios); ?> intercambios</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <span class="text-xs text-gray-500 flex items-center gap-1">🏷️ <?php echo esc_html($categoria); ?></span>
            <a href="<?php echo esc_url($url); ?>" class="text-violet-600 hover:text-violet-700 font-medium text-sm">
                <?php echo esc_html__('Ver más →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</article>
