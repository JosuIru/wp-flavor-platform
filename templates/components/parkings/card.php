<?php
/**
 * Componente: Card de Plaza de Parking
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$plaza = $item ?? $card_item ?? [];
if (empty($plaza)) return;

$id = $plaza['id'] ?? 0;
$ubicacion = $plaza['ubicacion'] ?? __('Plaza de parking', FLAVOR_PLATFORM_TEXT_DOMAIN);
$url = $plaza['url'] ?? '#';
$descripcion = $plaza['descripcion'] ?? '';
$tipo_vehiculo = $plaza['tipo_vehiculo'] ?? 'Coche';
$zona = $plaza['zona'] ?? '';
$horario = $plaza['horario'] ?? 'Flexible';
$precio = $plaza['precio'] ?? 0;
$periodo_precio = $plaza['periodo_precio'] ?? 'mes';
$disponible = $plaza['disponible'] ?? true;
$propietario_nombre = $plaza['propietario_nombre'] ?? 'Propietario';
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-zona="<?php echo esc_attr(sanitize_title($zona)); ?>">
    <div class="p-5">
        <!-- Cabecera -->
        <div class="flex items-center justify-between mb-3">
            <span class="bg-slate-100 text-slate-700 text-xs font-medium px-3 py-1 rounded-full">
                🚗 <?php echo esc_html($tipo_vehiculo); ?>
            </span>
            <span class="<?php echo $disponible ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> text-xs font-medium px-3 py-1 rounded-full">
                <?php echo $disponible ? esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Ocupada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-slate-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($ubicacion); ?>
            </a>
        </h3>

        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <div class="flex flex-wrap gap-2 mb-3">
            <?php if ($zona): ?>
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                📍 <?php echo esc_html($zona); ?>
            </span>
            <?php endif; ?>
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                🕐 <?php echo esc_html($horario); ?>
            </span>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 text-xs font-medium">
                    <?php echo esc_html(mb_substr($propietario_nombre, 0, 1)); ?>
                </div>
                <span class="text-sm text-gray-600"><?php echo esc_html($propietario_nombre); ?></span>
            </div>
            <span class="bg-slate-100 text-slate-700 font-bold px-3 py-1 rounded-full text-sm">
                <?php echo esc_html($precio); ?> €/<?php echo esc_html($periodo_precio); ?>
            </span>
        </div>
    </div>
</article>
