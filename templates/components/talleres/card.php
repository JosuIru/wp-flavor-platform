<?php
/**
 * Componente: Card de Taller
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$taller = $item ?? $card_item ?? [];
if (empty($taller)) return;

$id = $taller['id'] ?? 0;
$titulo = $taller['titulo'] ?? $taller['title'] ?? '';
$descripcion = $taller['descripcion'] ?? '';
$url = $taller['url'] ?? '#';
$imagen = $taller['imagen'] ?? '';
$fecha = $taller['fecha'] ?? '';
$duracion = $taller['duracion'] ?? '';
$categoria = $taller['categoria'] ?? __('General', FLAVOR_PLATFORM_TEXT_DOMAIN);
$instructor_nombre = $taller['instructor_nombre'] ?? __('Instructor', FLAVOR_PLATFORM_TEXT_DOMAIN);
$instructor_inicial = mb_substr($instructor_nombre, 0, 1);
$precio = $taller['precio'] ?? 0;
$plazas_disponibles = $taller['plazas_disponibles'] ?? 0;
$nivel = $taller['nivel'] ?? __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN);

$nivel_class = 'bg-gray-500';
if ($nivel === 'Principiante') {
    $nivel_class = 'bg-green-500';
} elseif ($nivel === 'Intermedio') {
    $nivel_class = 'bg-amber-500';
} elseif ($nivel === 'Avanzado') {
    $nivel_class = 'bg-red-500';
}
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="aspect-video bg-gray-100 relative overflow-hidden">
        <?php if (!empty($imagen)): ?>
        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-gray-400">
            <span class="text-5xl">🎨</span>
        </div>
        <?php endif; ?>
        <span class="absolute top-3 right-3 <?php echo esc_attr($nivel_class); ?> text-white text-xs font-medium px-3 py-1 rounded-full shadow">
            <?php echo esc_html($nivel); ?>
        </span>
    </div>

    <div class="p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-violet-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>

        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-medium">
                <?php echo esc_html($instructor_inicial); ?>
            </div>
            <span class="text-sm text-gray-600"><?php echo esc_html($instructor_nombre); ?></span>
        </div>

        <div class="flex flex-wrap gap-2 mb-3 text-xs text-gray-500">
            <?php if ($fecha): ?>
            <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                📅 <?php echo esc_html($fecha); ?>
            </span>
            <?php endif; ?>
            <?php if ($duracion): ?>
            <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                ⏱️ <?php echo esc_html($duracion); ?>
            </span>
            <?php endif; ?>
            <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                💺 <?php echo esc_html($plazas_disponibles); ?> <?php echo esc_html__('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <span class="text-lg font-bold <?php echo $precio == 0 ? 'text-green-600' : 'text-violet-600'; ?>">
                <?php echo $precio == 0 ? __('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html($precio) . ' €'; ?>
            </span>
            <a href="<?php echo esc_url($url); ?>"
               class="bg-purple-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-600 transition-colors">
                <?php echo esc_html__('Inscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</article>
