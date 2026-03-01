<?php
/**
 * Componente: Leaderboard
 *
 * Tabla de clasificación/ranking con posiciones, avatares y puntuaciones.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $users      Array de usuarios: [['id' => x, 'name' => '', 'avatar' => '', 'score' => 0, 'change' => 0, 'badges' => []]]
 * @param string $title      Título del leaderboard
 * @param string $metric     Nombre de la métrica (puntos, horas, servicios, etc.)
 * @param int    $highlight  ID del usuario a destacar (usuario actual)
 * @param bool   $show_medals Mostrar medallas en top 3
 * @param bool   $show_change Mostrar cambio de posición
 * @param int    $limit      Número máximo de usuarios a mostrar
 * @param string $period     Período: all, year, month, week
 * @param string $module     Módulo para cargar datos via AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

$users = $users ?? [];
$title = $title ?? __('Clasificación', 'flavor-chat-ia');
$metric = $metric ?? __('puntos', 'flavor-chat-ia');
$highlight = intval($highlight ?? get_current_user_id());
$show_medals = $show_medals ?? true;
$show_change = $show_change ?? true;
$limit = intval($limit ?? 10);
$period = $period ?? 'all';
$module = $module ?? '';

// Limitar usuarios
$users = array_slice($users, 0, $limit);

// Medallas para top 3
$medals = ['🥇', '🥈', '🥉'];

// Períodos disponibles
$periods = [
    'all'   => __('Todo el tiempo', 'flavor-chat-ia'),
    'year'  => __('Este año', 'flavor-chat-ia'),
    'month' => __('Este mes', 'flavor-chat-ia'),
    'week'  => __('Esta semana', 'flavor-chat-ia'),
];
?>

<div class="flavor-leaderboard bg-white rounded-xl shadow-lg overflow-hidden" data-module="<?php echo esc_attr($module); ?>">

    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🏆</span>
                <h3 class="text-xl font-bold text-white"><?php echo esc_html($title); ?></h3>
            </div>

            <?php if (count($periods) > 1): ?>
                <select class="flavor-leaderboard-period bg-white/20 text-white border-0 rounded-lg px-3 py-1.5 text-sm font-medium cursor-pointer hover:bg-white/30 transition-colors">
                    <?php foreach ($periods as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($period, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top 3 podium (si hay suficientes usuarios) -->
    <?php if (count($users) >= 3 && $show_medals): ?>
        <div class="bg-gradient-to-b from-amber-50 to-white px-6 py-8">
            <div class="flex justify-center items-end gap-4">
                <!-- 2º puesto -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <img src="<?php echo esc_url($users[1]['avatar'] ?? get_avatar_url($users[1]['id'] ?? 0, ['size' => 64])); ?>"
                             alt="<?php echo esc_attr($users[1]['name'] ?? ''); ?>"
                             class="w-16 h-16 rounded-full border-4 border-gray-300 shadow-lg">
                        <span class="absolute -bottom-2 -right-2 text-2xl"><?php echo $medals[1]; ?></span>
                    </div>
                    <p class="mt-3 font-semibold text-gray-800 text-sm text-center max-w-[80px] truncate">
                        <?php echo esc_html($users[1]['name'] ?? ''); ?>
                    </p>
                    <p class="text-gray-500 text-sm">
                        <?php echo number_format_i18n($users[1]['score'] ?? 0); ?> <?php echo esc_html($metric); ?>
                    </p>
                    <div class="mt-2 h-16 w-20 bg-gray-200 rounded-t-lg flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-400">2</span>
                    </div>
                </div>

                <!-- 1er puesto -->
                <div class="flex flex-col items-center -mt-4">
                    <div class="relative">
                        <img src="<?php echo esc_url($users[0]['avatar'] ?? get_avatar_url($users[0]['id'] ?? 0, ['size' => 80])); ?>"
                             alt="<?php echo esc_attr($users[0]['name'] ?? ''); ?>"
                             class="w-20 h-20 rounded-full border-4 border-yellow-400 shadow-xl ring-4 ring-yellow-200">
                        <span class="absolute -bottom-2 -right-2 text-3xl"><?php echo $medals[0]; ?></span>
                    </div>
                    <p class="mt-3 font-bold text-gray-900 text-center max-w-[100px] truncate">
                        <?php echo esc_html($users[0]['name'] ?? ''); ?>
                    </p>
                    <p class="text-amber-600 font-semibold">
                        <?php echo number_format_i18n($users[0]['score'] ?? 0); ?> <?php echo esc_html($metric); ?>
                    </p>
                    <div class="mt-2 h-24 w-24 bg-gradient-to-t from-amber-400 to-yellow-300 rounded-t-lg flex items-center justify-center shadow-lg">
                        <span class="text-3xl font-bold text-white drop-shadow">1</span>
                    </div>
                </div>

                <!-- 3er puesto -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <img src="<?php echo esc_url($users[2]['avatar'] ?? get_avatar_url($users[2]['id'] ?? 0, ['size' => 56])); ?>"
                             alt="<?php echo esc_attr($users[2]['name'] ?? ''); ?>"
                             class="w-14 h-14 rounded-full border-4 border-orange-300 shadow-lg">
                        <span class="absolute -bottom-2 -right-2 text-2xl"><?php echo $medals[2]; ?></span>
                    </div>
                    <p class="mt-3 font-semibold text-gray-800 text-sm text-center max-w-[80px] truncate">
                        <?php echo esc_html($users[2]['name'] ?? ''); ?>
                    </p>
                    <p class="text-gray-500 text-sm">
                        <?php echo number_format_i18n($users[2]['score'] ?? 0); ?> <?php echo esc_html($metric); ?>
                    </p>
                    <div class="mt-2 h-12 w-20 bg-orange-200 rounded-t-lg flex items-center justify-center">
                        <span class="text-2xl font-bold text-orange-400">3</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lista completa -->
    <div class="divide-y divide-gray-100">
        <?php
        $start_index = ($show_medals && count($users) >= 3) ? 3 : 0;
        for ($i = $start_index; $i < count($users); $i++):
            $user = $users[$i];
            $position = $i + 1;
            $is_current = ($user['id'] ?? 0) == $highlight;
            $change = $user['change'] ?? 0;
        ?>
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors <?php echo $is_current ? 'bg-blue-50 border-l-4 border-blue-500' : ''; ?>">
                <!-- Posición -->
                <div class="flex-shrink-0 w-8 text-center">
                    <?php if ($position <= 3 && $show_medals): ?>
                        <span class="text-xl"><?php echo $medals[$position - 1]; ?></span>
                    <?php else: ?>
                        <span class="text-lg font-bold text-gray-400"><?php echo $position; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Avatar -->
                <img src="<?php echo esc_url($user['avatar'] ?? get_avatar_url($user['id'] ?? 0, ['size' => 40])); ?>"
                     alt="<?php echo esc_attr($user['name'] ?? ''); ?>"
                     class="w-10 h-10 rounded-full <?php echo $is_current ? 'ring-2 ring-blue-500' : ''; ?>">

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate <?php echo $is_current ? 'text-blue-700' : ''; ?>">
                        <?php echo esc_html($user['name'] ?? ''); ?>
                        <?php if ($is_current): ?>
                            <span class="text-xs text-blue-500 ml-1">(<?php esc_html_e('tú', 'flavor-chat-ia'); ?>)</span>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($user['badges'])): ?>
                        <div class="flex gap-1 mt-0.5">
                            <?php foreach (array_slice($user['badges'], 0, 3) as $badge): ?>
                                <span class="text-xs" title="<?php echo esc_attr($badge['title'] ?? ''); ?>">
                                    <?php echo esc_html($badge['icon'] ?? '🏅'); ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($user['badges']) > 3): ?>
                                <span class="text-xs text-gray-400">+<?php echo count($user['badges']) - 3; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cambio de posición -->
                <?php if ($show_change && $change !== 0): ?>
                    <div class="flex-shrink-0">
                        <?php if ($change > 0): ?>
                            <span class="inline-flex items-center gap-0.5 text-green-600 text-sm font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php echo $change; ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-0.5 text-red-600 text-sm font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php echo abs($change); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Puntuación -->
                <div class="flex-shrink-0 text-right">
                    <p class="font-bold <?php echo $is_current ? 'text-blue-700' : 'text-gray-900'; ?>">
                        <?php echo number_format_i18n($user['score'] ?? 0); ?>
                    </p>
                    <p class="text-xs text-gray-500"><?php echo esc_html($metric); ?></p>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Estado vacío -->
    <?php if (empty($users)): ?>
        <div class="px-6 py-12 text-center">
            <span class="text-4xl">🏆</span>
            <p class="mt-3 text-gray-500"><?php esc_html_e('No hay participantes aún', 'flavor-chat-ia'); ?></p>
            <p class="text-sm text-gray-400"><?php esc_html_e('¡Sé el primero en participar!', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Footer con posición del usuario actual si no está en la lista -->
    <?php
    $user_in_list = false;
    foreach ($users as $u) {
        if (($u['id'] ?? 0) == $highlight) {
            $user_in_list = true;
            break;
        }
    }
    ?>
    <?php if ($highlight && !$user_in_list): ?>
        <div class="border-t-2 border-dashed border-gray-200 px-6 py-4 bg-gray-50">
            <div class="flex items-center gap-4">
                <span class="text-gray-400 text-sm">···</span>
                <div class="flex-shrink-0 w-8 text-center">
                    <span class="text-lg font-bold text-gray-400">?</span>
                </div>
                <img src="<?php echo esc_url(get_avatar_url($highlight, ['size' => 40])); ?>"
                     class="w-10 h-10 rounded-full ring-2 ring-blue-500">
                <div class="flex-1">
                    <p class="font-medium text-blue-700">
                        <?php
                        $current_user = get_userdata($highlight);
                        echo esc_html($current_user ? $current_user->display_name : '');
                        ?>
                        <span class="text-xs text-blue-500 ml-1">(<?php esc_html_e('tú', 'flavor-chat-ia'); ?>)</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500"><?php esc_html_e('Participa para aparecer', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.flavor-leaderboard-period').forEach(select => {
        select.addEventListener('change', function() {
            const leaderboard = this.closest('.flavor-leaderboard');
            const module = leaderboard.dataset.module;
            const period = this.value;

            if (!module) return;

            // Cargar datos via AJAX
            fetch(flavorAjax.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'flavor_get_leaderboard',
                    module: module,
                    period: period,
                    _wpnonce: flavorAjax.nonce
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.html) {
                    leaderboard.outerHTML = data.data.html;
                }
            });
        });
    });
});
</script>
