<?php
/**
 * Componente: User List
 *
 * Lista de usuarios para administración y gestión.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $users       Usuarios: [['id' => 1, 'name' => '', 'email' => '', 'avatar' => '', 'role' => '', 'status' => '', 'meta' => []]]
 * @param string $title       Título de la lista
 * @param array  $columns     Columnas a mostrar: ['avatar', 'name', 'email', 'role', 'status', 'meta', 'actions']
 * @param array  $actions     Acciones por usuario: [['id' => 'edit', 'label' => 'Editar', 'icon' => '✏️']]
 * @param string $color       Color del tema
 * @param bool   $selectable  Permitir selección múltiple
 * @param bool   $searchable  Mostrar buscador
 * @param string $empty_text  Texto cuando está vacía
 * @param string $on_action   Callback JS: fn(action, userId)
 * @param string $on_select   Callback JS: fn(selectedIds)
 * @param array  $roles       Roles disponibles para filtrar
 * @param array  $statuses    Estados disponibles para filtrar
 */

if (!defined('ABSPATH')) {
    exit;
}

$users = $users ?? [];
$title = $title ?? __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN);
$columns = $columns ?? ['avatar', 'name', 'email', 'role', 'status', 'actions'];
$actions = $actions ?? [];
$color = $color ?? 'blue';
$selectable = $selectable ?? false;
$searchable = $searchable ?? true;
$empty_text = $empty_text ?? __('No hay usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN);
$on_action = $on_action ?? '';
$on_select = $on_select ?? '';
$roles = $roles ?? [];
$statuses = $statuses ?? [];

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Colores de estado
$status_colors = [
    'active'   => 'bg-green-100 text-green-700',
    'inactive' => 'bg-gray-100 text-gray-600',
    'pending'  => 'bg-yellow-100 text-yellow-700',
    'blocked'  => 'bg-red-100 text-red-700',
    'verified' => 'bg-blue-100 text-blue-700',
];

// Labels de estado
$status_labels = [
    'active'   => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'inactive' => __('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pending'  => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'blocked'  => __('Bloqueado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'verified' => __('Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$list_id = 'user-list-' . wp_rand(1000, 9999);
?>

<div id="<?php echo esc_attr($list_id); ?>"
     class="flavor-user-list bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="p-4 border-b border-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
                <span class="px-2 py-0.5 text-sm rounded-full bg-gray-100 text-gray-600">
                    <?php echo esc_html(count($users)); ?>
                </span>
            </div>

            <div class="flex items-center gap-3">
                <?php if ($searchable): ?>
                    <div class="relative">
                        <input type="search"
                               class="user-search w-48 pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20 focus:border-<?php echo esc_attr($color); ?>-500"
                               placeholder="<?php esc_attr_e('Buscar usuario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                               oninput="flavorUserList.search('<?php echo esc_js($list_id); ?>', this.value)">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($roles)): ?>
                    <select class="role-filter px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20"
                            onchange="flavorUserList.filterRole('<?php echo esc_js($list_id); ?>', this.value)">
                        <option value=""><?php esc_html_e('Todos los roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo esc_attr($role['id'] ?? ''); ?>">
                                <?php echo esc_html($role['label'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if (!empty($statuses)): ?>
                    <select class="status-filter px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20"
                            onchange="flavorUserList.filterStatus('<?php echo esc_js($list_id); ?>', this.value)">
                        <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo esc_attr($status['id'] ?? ''); ?>">
                                <?php echo esc_html($status['label'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Lista -->
    <div class="divide-y divide-gray-50">
        <?php if (empty($users)): ?>
            <div class="p-8 text-center">
                <span class="text-4xl block mb-3">👥</span>
                <p class="text-gray-500"><?php echo esc_html($empty_text); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $user):
                $user_id = $user['id'] ?? 0;
                $user_name = $user['name'] ?? $user['display_name'] ?? '';
                $user_email = $user['email'] ?? '';
                $user_avatar = $user['avatar'] ?? '';
                $user_role = $user['role'] ?? '';
                $user_status = $user['status'] ?? 'active';
                $user_meta = $user['meta'] ?? [];
                $user_verified = $user['verified'] ?? false;
            ?>
                <div class="user-item flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors"
                     data-user-id="<?php echo esc_attr($user_id); ?>"
                     data-user-name="<?php echo esc_attr(strtolower($user_name)); ?>"
                     data-user-email="<?php echo esc_attr(strtolower($user_email)); ?>"
                     data-user-role="<?php echo esc_attr($user_role); ?>"
                     data-user-status="<?php echo esc_attr($user_status); ?>">

                    <?php if ($selectable): ?>
                        <label class="flex-shrink-0 cursor-pointer">
                            <input type="checkbox"
                                   class="user-checkbox w-5 h-5 rounded border-gray-300 text-<?php echo esc_attr($color); ?>-600 focus:ring-<?php echo esc_attr($color); ?>-500"
                                   value="<?php echo esc_attr($user_id); ?>"
                                   onchange="flavorUserList.toggleSelect('<?php echo esc_js($list_id); ?>')">
                        </label>
                    <?php endif; ?>

                    <?php if (in_array('avatar', $columns)): ?>
                        <div class="flex-shrink-0">
                            <?php if ($user_avatar): ?>
                                <img src="<?php echo esc_url($user_avatar); ?>"
                                     alt="<?php echo esc_attr($user_name); ?>"
                                     class="w-10 h-10 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center">
                                    <span class="text-lg"><?php echo esc_html(mb_substr($user_name, 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <?php if (in_array('name', $columns)): ?>
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo esc_html($user_name); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($user_verified): ?>
                                <span class="text-blue-500 text-sm" title="<?php esc_attr_e('Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">✓</span>
                            <?php endif; ?>

                            <?php if (in_array('role', $columns) && $user_role): ?>
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                    <?php echo esc_html($user_role); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (in_array('email', $columns) && $user_email): ?>
                            <p class="text-sm text-gray-500 truncate"><?php echo esc_html($user_email); ?></p>
                        <?php endif; ?>

                        <?php if (in_array('meta', $columns) && !empty($user_meta)): ?>
                            <div class="flex items-center gap-3 mt-1">
                                <?php foreach ($user_meta as $meta): ?>
                                    <span class="text-xs text-gray-400">
                                        <?php if (!empty($meta['icon'])): ?>
                                            <span class="mr-1"><?php echo esc_html($meta['icon']); ?></span>
                                        <?php endif; ?>
                                        <?php echo esc_html($meta['value'] ?? ''); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (in_array('status', $columns)): ?>
                        <div class="flex-shrink-0">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo esc_attr($status_colors[$user_status] ?? 'bg-gray-100 text-gray-600'); ?>">
                                <?php echo esc_html($status_labels[$user_status] ?? ucfirst($user_status)); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array('actions', $columns) && !empty($actions)): ?>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <?php foreach ($actions as $action): ?>
                                <button type="button"
                                        onclick="flavorUserList.executeAction('<?php echo esc_js($list_id); ?>', '<?php echo esc_js($action['id'] ?? ''); ?>', <?php echo esc_attr($user_id); ?>)"
                                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                        title="<?php echo esc_attr($action['label'] ?? ''); ?>">
                                    <?php echo esc_html($action['icon'] ?? '⋮'); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer con selección -->
    <?php if ($selectable): ?>
        <div class="selection-footer hidden p-4 border-t border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <span class="selection-count text-sm text-gray-600">
                    <?php esc_html_e('0 usuarios seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <div class="flex items-center gap-2">
                    <button type="button"
                            onclick="flavorUserList.clearSelection('<?php echo esc_js($list_id); ?>')"
                            class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200 rounded-lg transition-colors">
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button"
                            onclick="flavorUserList.getSelected('<?php echo esc_js($list_id); ?>')"
                            class="px-3 py-1.5 text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> rounded-lg hover:opacity-90 transition-colors">
                        <?php esc_html_e('Aplicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
window.flavorUserList = window.flavorUserList || {
    search: function(listId, query) {
        const list = document.getElementById(listId);
        if (!list) return;

        const normalizedQuery = query.toLowerCase().trim();

        list.querySelectorAll('.user-item').forEach(item => {
            const name = item.dataset.userName || '';
            const email = item.dataset.userEmail || '';
            const matches = !normalizedQuery || name.includes(normalizedQuery) || email.includes(normalizedQuery);
            item.style.display = matches ? '' : 'none';
        });
    },

    filterRole: function(listId, role) {
        const list = document.getElementById(listId);
        if (!list) return;

        list.querySelectorAll('.user-item').forEach(item => {
            const itemRole = item.dataset.userRole || '';
            const matches = !role || itemRole === role;
            item.style.display = matches ? '' : 'none';
        });
    },

    filterStatus: function(listId, status) {
        const list = document.getElementById(listId);
        if (!list) return;

        list.querySelectorAll('.user-item').forEach(item => {
            const itemStatus = item.dataset.userStatus || '';
            const matches = !status || itemStatus === status;
            item.style.display = matches ? '' : 'none';
        });
    },

    toggleSelect: function(listId) {
        const list = document.getElementById(listId);
        if (!list) return;

        const checkboxes = list.querySelectorAll('.user-checkbox:checked');
        const count = checkboxes.length;
        const footer = list.querySelector('.selection-footer');
        const countEl = list.querySelector('.selection-count');

        if (footer) {
            footer.classList.toggle('hidden', count === 0);
        }
        if (countEl) {
            countEl.textContent = count + ' usuario' + (count !== 1 ? 's' : '') + ' seleccionado' + (count !== 1 ? 's' : '');
        }
    },

    clearSelection: function(listId) {
        const list = document.getElementById(listId);
        if (!list) return;

        list.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
        this.toggleSelect(listId);
    },

    getSelected: function(listId) {
        const list = document.getElementById(listId);
        if (!list) return [];

        const selected = Array.from(list.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);

        <?php if ($on_select): ?>
        <?php echo $on_select; ?>(selected);
        <?php endif; ?>

        return selected;
    },

    executeAction: function(listId, actionId, userId) {
        document.dispatchEvent(new CustomEvent('userlist-action', {
            detail: { listId, actionId, userId }
        }));

        <?php if ($on_action): ?>
        <?php echo $on_action; ?>(actionId, userId);
        <?php endif; ?>
    }
};
</script>
