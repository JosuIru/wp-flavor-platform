<?php
/**
 * Componente: Membership Card
 *
 * Tarjeta de membresía/suscripción con información del plan y estado.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $membership Datos de membresía: ['plan' => '', 'status' => '', 'start_date' => '', 'end_date' => '', 'price' => 0]
 * @param array  $user       Datos del usuario: ['id' => 0, 'name' => '', 'avatar' => '', 'member_number' => '']
 * @param array  $benefits   Lista de beneficios del plan
 * @param string $color      Color del tema: blue, green, purple, gold
 * @param bool   $show_qr    Mostrar código QR
 * @param bool   $compact    Versión compacta
 * @param string $cta_text   Texto del botón CTA
 * @param string $cta_url    URL del botón CTA
 */

if (!defined('ABSPATH')) {
    exit;
}

$membership = $membership ?? [];
$user = $user ?? [];
$benefits = $benefits ?? [];
$color = $color ?? 'blue';
$show_qr = $show_qr ?? true;
$compact = $compact ?? false;
$cta_text = $cta_text ?? '';
$cta_url = $cta_url ?? '';

// Datos por defecto
$plan_name = $membership['plan'] ?? __('Básico', 'flavor-chat-ia');
$status = $membership['status'] ?? 'active';
$start_date = $membership['start_date'] ?? '';
$end_date = $membership['end_date'] ?? '';
$price = $membership['price'] ?? 0;
$billing_period = $membership['billing_period'] ?? 'month';

$user_id = $user['id'] ?? get_current_user_id();
$user_name = $user['name'] ?? '';
$user_avatar = $user['avatar'] ?? '';
$member_number = $user['member_number'] ?? '';

if (!$user_name && $user_id) {
    $wp_user = get_userdata($user_id);
    $user_name = $wp_user ? $wp_user->display_name : '';
}
if (!$user_avatar && $user_id) {
    $user_avatar = get_avatar_url($user_id, ['size' => 80]);
}
if (!$member_number && $user_id) {
    $member_number = str_pad($user_id, 6, '0', STR_PAD_LEFT);
}

// Estados
$status_config = [
    'active'    => ['label' => __('Activo', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✓'],
    'pending'   => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏳'],
    'expired'   => ['label' => __('Expirado', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '⚠'],
    'cancelled' => ['label' => __('Cancelado', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '✕'],
    'trial'     => ['label' => __('Prueba', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '🎁'],
];
$status_info = $status_config[$status] ?? $status_config['active'];

// Colores del tema
$color_themes = [
    'blue'   => ['from' => 'from-blue-600', 'to' => 'to-blue-800', 'accent' => 'blue-400', 'light' => 'blue-100'],
    'green'  => ['from' => 'from-green-600', 'to' => 'to-green-800', 'accent' => 'green-400', 'light' => 'green-100'],
    'purple' => ['from' => 'from-purple-600', 'to' => 'to-purple-800', 'accent' => 'purple-400', 'light' => 'purple-100'],
    'gold'   => ['from' => 'from-amber-500', 'to' => 'to-yellow-600', 'accent' => 'yellow-300', 'light' => 'amber-100'],
    'red'    => ['from' => 'from-red-600', 'to' => 'to-red-800', 'accent' => 'red-400', 'light' => 'red-100'],
];
$theme = $color_themes[$color] ?? $color_themes['blue'];

// Períodos de facturación
$billing_labels = [
    'month' => __('/mes', 'flavor-chat-ia'),
    'year'  => __('/año', 'flavor-chat-ia'),
    'once'  => '',
    'week'  => __('/semana', 'flavor-chat-ia'),
];
$billing_label = $billing_labels[$billing_period] ?? '';

// Calcular días restantes
$days_remaining = null;
if ($end_date && $status === 'active') {
    $end_timestamp = strtotime($end_date);
    $days_remaining = max(0, ceil(($end_timestamp - time()) / DAY_IN_SECONDS));
}
?>

<?php if ($compact): ?>
    <!-- Versión compacta -->
    <div class="flavor-membership-card-compact bg-gradient-to-r <?php echo esc_attr($theme['from'] . ' ' . $theme['to']); ?> rounded-xl p-4 text-white">
        <div class="flex items-center gap-4">
            <img src="<?php echo esc_url($user_avatar); ?>"
                 alt="<?php echo esc_attr($user_name); ?>"
                 class="w-12 h-12 rounded-full border-2 border-white/30">
            <div class="flex-1 min-w-0">
                <p class="font-bold truncate"><?php echo esc_html($user_name); ?></p>
                <p class="text-sm text-white/80"><?php echo esc_html($plan_name); ?></p>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-<?php echo esc_attr($status_info['color']); ?>-500/30 text-white">
                    <?php echo esc_html($status_info['icon']); ?>
                    <?php echo esc_html($status_info['label']); ?>
                </span>
                <?php if ($days_remaining !== null && $days_remaining <= 30): ?>
                    <p class="text-xs text-white/60 mt-1">
                        <?php printf(__('%d días restantes', 'flavor-chat-ia'), $days_remaining); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Versión completa (tarjeta tipo carnet) -->
    <div class="flavor-membership-card relative overflow-hidden rounded-2xl shadow-xl" style="max-width: 400px;">

        <!-- Frente de la tarjeta -->
        <div class="bg-gradient-to-br <?php echo esc_attr($theme['from'] . ' ' . $theme['to']); ?> p-6 text-white relative">

            <!-- Patrón decorativo -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <pattern id="card-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="10" cy="10" r="2" fill="white"/>
                    </pattern>
                    <rect fill="url(#card-pattern)" width="100" height="100"/>
                </svg>
            </div>

            <!-- Header -->
            <div class="relative flex items-start justify-between mb-6">
                <div>
                    <p class="text-xs text-white/60 uppercase tracking-wider"><?php esc_html_e('Tarjeta de socio', 'flavor-chat-ia'); ?></p>
                    <h3 class="text-2xl font-bold mt-1"><?php echo esc_html($plan_name); ?></h3>
                </div>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-<?php echo esc_attr($status_info['color']); ?>-500/30 backdrop-blur-sm">
                    <?php echo esc_html($status_info['icon']); ?>
                    <?php echo esc_html($status_info['label']); ?>
                </span>
            </div>

            <!-- Info del usuario -->
            <div class="relative flex items-center gap-4 mb-6">
                <img src="<?php echo esc_url($user_avatar); ?>"
                     alt="<?php echo esc_attr($user_name); ?>"
                     class="w-16 h-16 rounded-full border-3 border-white/30 shadow-lg">
                <div>
                    <p class="font-bold text-lg"><?php echo esc_html($user_name); ?></p>
                    <p class="text-sm text-white/70">
                        <?php esc_html_e('Nº Socio:', 'flavor-chat-ia'); ?>
                        <span class="font-mono"><?php echo esc_html($member_number); ?></span>
                    </p>
                </div>
            </div>

            <!-- Fechas y precio -->
            <div class="relative grid grid-cols-3 gap-4 text-sm">
                <?php if ($start_date): ?>
                    <div>
                        <p class="text-white/50 text-xs"><?php esc_html_e('Desde', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium"><?php echo esc_html(date_i18n('d/m/Y', strtotime($start_date))); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($end_date): ?>
                    <div>
                        <p class="text-white/50 text-xs"><?php esc_html_e('Válido hasta', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium"><?php echo esc_html(date_i18n('d/m/Y', strtotime($end_date))); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($price > 0): ?>
                    <div class="text-right">
                        <p class="text-white/50 text-xs"><?php esc_html_e('Cuota', 'flavor-chat-ia'); ?></p>
                        <p class="font-bold"><?php echo esc_html(number_format_i18n($price, 2) . '€' . $billing_label); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Días restantes (advertencia) -->
            <?php if ($days_remaining !== null && $days_remaining <= 30): ?>
                <div class="relative mt-4 bg-white/10 rounded-lg px-3 py-2 text-sm">
                    ⏰ <?php printf(__('%d días restantes para renovar', 'flavor-chat-ia'), $days_remaining); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Parte inferior (beneficios + QR) -->
        <div class="bg-white p-6">
            <div class="flex gap-6">
                <!-- Beneficios -->
                <?php if (!empty($benefits)): ?>
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">
                            <?php esc_html_e('Incluye', 'flavor-chat-ia'); ?>
                        </p>
                        <ul class="space-y-1.5">
                            <?php foreach (array_slice($benefits, 0, 4) as $benefit): ?>
                                <li class="flex items-center gap-2 text-sm text-gray-700">
                                    <span class="w-5 h-5 rounded-full bg-<?php echo esc_attr($theme['light']); ?> flex items-center justify-center text-xs">✓</span>
                                    <?php echo esc_html($benefit); ?>
                                </li>
                            <?php endforeach; ?>
                            <?php if (count($benefits) > 4): ?>
                                <li class="text-xs text-gray-400">
                                    +<?php echo count($benefits) - 4; ?> <?php esc_html_e('más', 'flavor-chat-ia'); ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- QR Code -->
                <?php if ($show_qr): ?>
                    <div class="flex-shrink-0 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?php echo urlencode(home_url('/socio/' . $member_number)); ?>"
                                 alt="QR Code"
                                 class="w-16 h-16">
                        </div>
                        <p class="text-xs text-gray-400 mt-1"><?php esc_html_e('Escanear', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CTA -->
            <?php if ($cta_text && $cta_url): ?>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="<?php echo esc_url($cta_url); ?>"
                       class="block w-full text-center py-2.5 px-4 bg-gradient-to-r <?php echo esc_attr($theme['from'] . ' ' . $theme['to']); ?> text-white font-medium rounded-lg hover:opacity-90 transition-opacity">
                        <?php echo esc_html($cta_text); ?>
                    </a>
                </div>
            <?php elseif ($status === 'expired' || $status === 'cancelled'): ?>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="<?php echo esc_url(home_url('/mi-cuenta/renovar/')); ?>"
                       class="block w-full text-center py-2.5 px-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-medium rounded-lg hover:opacity-90 transition-opacity">
                        🔄 <?php esc_html_e('Renovar membresía', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
