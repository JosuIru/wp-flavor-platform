<?php
/**
 * Componente: Card de Incidencia
 *
 * Card específica para mostrar incidencias con estado,
 * prioridad, imagen y acciones.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array $item  Datos de la incidencia (o usar $card_item si viene de items-grid)
 * @param int   $index Índice del item en el array
 */

if (!defined('ABSPATH')) {
    exit;
}

// Soportar ambas variables (directa o desde items-grid)
$incidencia = $item ?? $card_item ?? [];
$index = $index ?? $card_index ?? 0;

// No renderizar si no hay datos
if (empty($incidencia)) {
    return;
}

// Extraer campos
$id = $incidencia['id'] ?? 0;
$titulo = $incidencia['titulo'] ?? $incidencia['title'] ?? '';
$descripcion = $incidencia['descripcion'] ?? $incidencia['excerpt'] ?? '';
$imagen = $incidencia['imagen'] ?? $incidencia['image'] ?? '';
$url = $incidencia['url'] ?? $incidencia['permalink'] ?? '#';
$estado = $incidencia['estado'] ?? 'pendiente';
$prioridad = $incidencia['prioridad'] ?? '';
$ubicacion = $incidencia['ubicacion'] ?? '';
$fecha = $incidencia['fecha'] ?? '';
$categoria = $incidencia['categoria'] ?? '';
$votos = $incidencia['votos'] ?? 0;

// Configuración de estados
$estado_config = [
    // Estados en español
    'pendiente'  => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '🔴', 'label' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'en_proceso' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => '🟡', 'label' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'resuelta'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '🟢', 'label' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'resuelto'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '🟢', 'label' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'cerrada'    => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => '⚪', 'label' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    // Estados en inglés (compatibilidad)
    'pending'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '🔴', 'label' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'in_progress' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => '🟡', 'label' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'resolved'    => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '🟢', 'label' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'closed'      => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => '⚪', 'label' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];
$estado_style = $estado_config[$estado] ?? $estado_config['pendiente'];

// Configuración de prioridades
$prioridad_config = [
    'alta'  => ['bg' => 'bg-red-500', 'label' => '🔥 ' . __('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'media' => ['bg' => 'bg-yellow-500', 'label' => '⚡ ' . __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    'baja'  => ['bg' => 'bg-blue-500', 'label' => '💧 ' . __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];
$prioridad_style = $prioridad_config[$prioridad] ?? null;
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden border border-gray-100"
         data-estado="<?php echo esc_attr($estado); ?>"
         data-id="<?php echo esc_attr($id); ?>">
    <div class="flex flex-col md:flex-row">
        <!-- Imagen -->
        <div class="md:w-48 flex-shrink-0">
            <?php if ($imagen): ?>
            <img src="<?php echo esc_url($imagen); ?>"
                 alt="<?php echo esc_attr($titulo); ?>"
                 class="w-full h-32 md:h-full object-cover"
                 loading="lazy">
            <?php else: ?>
            <div class="w-full h-32 md:h-full bg-red-50 flex items-center justify-center text-4xl">
                ⚠️
            </div>
            <?php endif; ?>
        </div>

        <!-- Contenido -->
        <div class="flex-1 p-5">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div>
                    <!-- Estado badge -->
                    <span class="inline-flex items-center gap-1 <?php echo esc_attr($estado_style['bg']); ?> <?php echo esc_attr($estado_style['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                        <?php echo esc_html($estado_style['icon']); ?>
                        <?php echo esc_html($estado_style['label']); ?>
                    </span>

                    <h3 class="text-lg font-semibold text-gray-800 mt-2">
                        <a href="<?php echo esc_url($url); ?>" class="hover:text-red-600 transition-colors">
                            <?php echo esc_html($titulo); ?>
                        </a>
                    </h3>
                </div>

                <!-- Prioridad -->
                <?php if ($prioridad_style): ?>
                <span class="<?php echo esc_attr($prioridad_style['bg']); ?> text-white text-xs px-2 py-1 rounded flex-shrink-0">
                    <?php echo esc_html($prioridad_style['label']); ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if ($descripcion): ?>
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                <?php echo esc_html($descripcion); ?>
            </p>
            <?php endif; ?>

            <!-- Meta info -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <?php if ($ubicacion): ?>
                <span class="flex items-center gap-1">📍 <?php echo esc_html($ubicacion); ?></span>
                <?php endif; ?>

                <?php if ($fecha): ?>
                <span class="flex items-center gap-1">📅 <?php echo esc_html($fecha); ?></span>
                <?php endif; ?>

                <?php if ($categoria): ?>
                <span class="flex items-center gap-1">🏷️ <?php echo esc_html($categoria); ?></span>
                <?php endif; ?>

                <?php if ($votos): ?>
                <span class="flex items-center gap-1 text-red-600 font-medium">
                    👍 <?php echo esc_html($votos); ?> <?php echo esc_html(_n('apoyo', 'apoyos', $votos, FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex md:flex-col justify-end gap-2 p-4 bg-gray-50 md:bg-transparent">
            <button type="button"
                    class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                    onclick="flavorIncidencias && flavorIncidencias.apoyar(<?php echo esc_attr($id); ?>)"
                    title="<?php echo esc_attr__('Apoyar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    aria-label="<?php echo esc_attr__('Apoyar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
            </button>
            <a href="<?php echo esc_url($url); ?>"
               class="p-2 text-gray-400 hover:text-red-500 transition-colors"
               title="<?php echo esc_attr__('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
               aria-label="<?php echo esc_attr__('Ver detalles de la incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</article>
