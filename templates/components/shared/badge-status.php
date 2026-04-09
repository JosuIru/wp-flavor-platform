<?php
/**
 * Componente: Badge Status
 *
 * Badge/etiqueta de estado con colores semánticos.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $status   Estado (pending, active, completed, cancelled, etc.)
 * @param string $label    Etiqueta personalizada (opcional, si no se usa el status)
 * @param string $size     Tamaño: xs, sm, md, lg
 * @param string $variant  Variante: filled, outline, soft
 * @param string $icon     Icono emoji (opcional)
 * @param bool   $dot      Mostrar punto de color en lugar de fondo
 * @param bool   $pulse    Animación de pulso (para estados activos)
 */

if (!defined('ABSPATH')) {
    exit;
}

$status = $status ?? 'default';
$label = $label ?? '';
$size = $size ?? 'sm';
$variant = $variant ?? 'soft';
$icon = $icon ?? '';
$dot = $dot ?? false;
$pulse = $pulse ?? false;

// Mapeo de estados a colores y etiquetas
$status_config = [
    // Estados generales
    'pending'    => ['color' => 'yellow', 'label' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⏳'],
    'active'     => ['color' => 'green', 'label' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅'],
    'completed'  => ['color' => 'green', 'label' => __('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✔️'],
    'cancelled'  => ['color' => 'red', 'label' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '❌'],
    'rejected'   => ['color' => 'red', 'label' => __('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🚫'],
    'expired'    => ['color' => 'gray', 'label' => __('Expirado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⌛'],
    'draft'      => ['color' => 'gray', 'label' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📝'],
    'published'  => ['color' => 'blue', 'label' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📢'],

    // Estados de proceso
    'in_progress'=> ['color' => 'blue', 'label' => __('En progreso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔄'],
    'processing' => ['color' => 'blue', 'label' => __('Procesando', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⚙️'],
    'reviewing'  => ['color' => 'purple', 'label' => __('En revisión', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👁️'],
    'approved'   => ['color' => 'green', 'label' => __('Aprobado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👍'],

    // Estados de incidencias
    'reported'   => ['color' => 'orange', 'label' => __('Reportado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⚠️'],
    'assigned'   => ['color' => 'blue', 'label' => __('Asignado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👤'],
    'resolved'   => ['color' => 'green', 'label' => __('Resuelto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅'],
    'closed'     => ['color' => 'gray', 'label' => __('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔒'],

    // Estados de pagos
    'paid'       => ['color' => 'green', 'label' => __('Pagado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '💰'],
    'unpaid'     => ['color' => 'red', 'label' => __('Sin pagar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '💸'],
    'refunded'   => ['color' => 'purple', 'label' => __('Reembolsado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '↩️'],

    // Estados de reservas/pedidos
    'confirmed'  => ['color' => 'green', 'label' => __('Confirmado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✓'],
    'waiting'    => ['color' => 'yellow', 'label' => __('En espera', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⏰'],
    'shipped'    => ['color' => 'blue', 'label' => __('Enviado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📦'],
    'delivered'  => ['color' => 'green', 'label' => __('Entregado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🎁'],
    'pickup'     => ['color' => 'orange', 'label' => __('Para recoger', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📍'],

    // Estados de usuarios
    'online'     => ['color' => 'green', 'label' => __('En línea', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟢'],
    'offline'    => ['color' => 'gray', 'label' => __('Desconectado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⚫'],
    'away'       => ['color' => 'yellow', 'label' => __('Ausente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟡'],
    'busy'       => ['color' => 'red', 'label' => __('Ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔴'],

    // Grupos consumo
    'open'       => ['color' => 'green', 'label' => __('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🛒'],
    'sin_stock'  => ['color' => 'orange', 'label' => __('Sin stock', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📭'],

    // Default
    'default'    => ['color' => 'gray', 'label' => __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => ''],
];

// Normalizar status
$status_key = strtolower(str_replace([' ', '-'], '_', $status));
$config = $status_config[$status_key] ?? $status_config['default'];

// Usar label personalizado o el del config
$display_label = !empty($label) ? $label : $config['label'];
$display_icon = !empty($icon) ? $icon : $config['icon'];
$color = $config['color'];

// Clases de tamaño
$size_classes = [
    'xs' => 'text-xs px-1.5 py-0.5',
    'sm' => 'text-xs px-2 py-1',
    'md' => 'text-sm px-2.5 py-1',
    'lg' => 'text-sm px-3 py-1.5',
];
$size_class = $size_classes[$size] ?? $size_classes['sm'];

// Clases de color por variante
$color_variants = [
    'green' => [
        'filled' => 'bg-green-500 text-white',
        'outline' => 'border border-green-500 text-green-600',
        'soft' => 'bg-green-100 text-green-700',
    ],
    'red' => [
        'filled' => 'bg-red-500 text-white',
        'outline' => 'border border-red-500 text-red-600',
        'soft' => 'bg-red-100 text-red-700',
    ],
    'yellow' => [
        'filled' => 'bg-yellow-500 text-white',
        'outline' => 'border border-yellow-500 text-yellow-600',
        'soft' => 'bg-yellow-100 text-yellow-700',
    ],
    'blue' => [
        'filled' => 'bg-blue-500 text-white',
        'outline' => 'border border-blue-500 text-blue-600',
        'soft' => 'bg-blue-100 text-blue-700',
    ],
    'purple' => [
        'filled' => 'bg-purple-500 text-white',
        'outline' => 'border border-purple-500 text-purple-600',
        'soft' => 'bg-purple-100 text-purple-700',
    ],
    'orange' => [
        'filled' => 'bg-orange-500 text-white',
        'outline' => 'border border-orange-500 text-orange-600',
        'soft' => 'bg-orange-100 text-orange-700',
    ],
    'gray' => [
        'filled' => 'bg-gray-500 text-white',
        'outline' => 'border border-gray-400 text-gray-600',
        'soft' => 'bg-gray-100 text-gray-600',
    ],
];

$variant_classes = $color_variants[$color][$variant] ?? $color_variants['gray'][$variant];

// Colores para dot
$dot_colors = [
    'green'  => 'bg-green-500',
    'red'    => 'bg-red-500',
    'yellow' => 'bg-yellow-500',
    'blue'   => 'bg-blue-500',
    'purple' => 'bg-purple-500',
    'orange' => 'bg-orange-500',
    'gray'   => 'bg-gray-500',
];
$dot_color = $dot_colors[$color] ?? $dot_colors['gray'];
?>

<?php if ($dot): ?>
    <!-- Versión con punto -->
    <span class="inline-flex items-center gap-1.5 <?php echo esc_attr($size_class); ?>">
        <span class="relative flex h-2 w-2">
            <?php if ($pulse): ?>
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo esc_attr($dot_color); ?> opacity-75"></span>
            <?php endif; ?>
            <span class="relative inline-flex rounded-full h-2 w-2 <?php echo esc_attr($dot_color); ?>"></span>
        </span>
        <span class="text-gray-700"><?php echo esc_html($display_label); ?></span>
    </span>
<?php else: ?>
    <!-- Versión con badge -->
    <span class="inline-flex items-center gap-1 font-medium rounded-full whitespace-nowrap <?php echo esc_attr($size_class); ?> <?php echo esc_attr($variant_classes); ?> <?php echo $pulse ? 'animate-pulse' : ''; ?>">
        <?php if ($display_icon): ?>
            <span class="<?php echo $size === 'xs' ? 'text-[10px]' : ''; ?>"><?php echo esc_html($display_icon); ?></span>
        <?php endif; ?>
        <?php echo esc_html($display_label); ?>
    </span>
<?php endif; ?>
