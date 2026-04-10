<?php
/**
 * Vista: Dashboard Unificado v4.1.0
 *
 * Implementa el sistema de diseño unificado con:
 * - Breadcrumbs de navegacion
 * - Filtros por categoria
 * - Widgets agrupados por categoria
 * - Niveles visuales de widgets (featured, standard, compact)
 * - Drag & drop entre categorias
 * - Accesibilidad WCAG 2.1 AA
 *
 * Variables disponibles desde el controlador:
 * - $widgets        Widgets visibles ordenados
 * - $all_widgets    Todos los widgets (para personalizacion)
 * - $renderer       Instancia del renderizador
 * - $total_widgets  Total de widgets registrados
 * - $visible_count  Widgets visibles
 * - $last_refresh   Ultima actualizacion
 * - $user_prefs     Preferencias del usuario
 * - $categories     Categorias disponibles (si existe)
 *
 * @package FlavorPlatform
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$get_severity_payload = static function ($severity_slug) {
    if (class_exists('Flavor_Dashboard_Severity')) {
        return Flavor_Dashboard_Severity::get_payload($severity_slug);
    }

    $fallback_labels = [
        'attention' => __('Atención', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'followup' => __('Seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'stable' => __('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    $severity_slug = sanitize_key((string) $severity_slug);
    if (!isset($fallback_labels[$severity_slug])) {
        $severity_slug = 'stable';
    }

    return [
        'slug' => $severity_slug,
        'label' => $fallback_labels[$severity_slug],
    ];
};

$get_admin_action_severity = static function ($action_kind) use ($get_severity_payload) {
    if (class_exists('Flavor_Dashboard_Severity')) {
        return $get_severity_payload(Flavor_Dashboard_Severity::from_admin_action($action_kind));
    }

    $map = [
        'focus' => 'attention',
        'complete' => 'attention',
        'review' => 'followup',
    ];

    return $get_severity_payload($map[sanitize_key((string) $action_kind)] ?? 'stable');
};

// Obtener registry para categorias
$registry = class_exists('Flavor_Widget_Registry') ? Flavor_Widget_Registry::get_instance() : null;
$categories = $registry ? $registry->get_categories() : [];

// Obtener widgets agrupados por categoria
$widgets_by_category = [];
if ($registry && !empty($widgets)) {
    foreach ($widgets as $widget) {
        $config = $widget->get_widget_config();
        $category = $config['category'] ?? 'sistema';
        if (!isset($widgets_by_category[$category])) {
            $widgets_by_category[$category] = [];
        }
        $widgets_by_category[$category][] = $widget;
    }
}

// Categorias colapsadas del usuario
$collapsed_categories = $user_prefs['collapsedCategories'] ?? [];

// Filtro activo
$active_filter = $user_prefs['activeFilter'] ?? 'all';

// Vista actual
$view_mode = $user_prefs['viewMode'] ?? 'grid';

$module_loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
$registered_modules = $module_loader ? $module_loader->get_registered_modules() : [];
$ecosystem_nodes = [];
$active_module_ids = [];
$normalize_dashboard_context = static function ($context) {
    return sanitize_key(str_replace('-', '_', (string) $context));
};
$dashboard_contexts = ['admin', 'dashboard'];
$current_page = $normalize_dashboard_context($_GET['page'] ?? '');
if ($current_page !== '') {
    $dashboard_contexts[] = $current_page;
}
if ($active_filter !== 'all') {
    $dashboard_contexts[] = $normalize_dashboard_context($active_filter);
}
foreach (['comunidad', 'energia', 'consumo', 'cuidados', 'participacion', 'gobernanza', 'transparencia'] as $candidate_context) {
    if (strpos($current_page, $candidate_context) !== false) {
        $dashboard_contexts[] = $candidate_context;
    }
}
$dashboard_contexts = array_values(array_unique(array_filter($dashboard_contexts)));
$get_dashboard_module_contexts = static function ($module_data) use ($normalize_dashboard_context) {
    $admin_contexts = (array) ($module_data['dashboard']['admin_contexts'] ?? []);
    if (!empty($admin_contexts)) {
        return array_values(array_unique(array_filter(array_map($normalize_dashboard_context, $admin_contexts))));
    }

    return array_values(array_unique(array_filter(array_map($normalize_dashboard_context, (array) ($module_data['dashboard']['client_contexts'] ?? [])))));
};
$calculate_dashboard_context_match = static function ($module_contexts, $current_contexts) {
    if (empty($module_contexts) || empty($current_contexts)) {
        return 0;
    }

    return count(array_intersect($module_contexts, $current_contexts));
};

$find_dashboard_base_parent_for_module = static function ($module_id, $registered_modules) {
    foreach ($registered_modules as $candidate_module_id => $candidate_module) {
        $candidate_ecosystem = is_array($candidate_module['ecosystem'] ?? null) ? $candidate_module['ecosystem'] : [];
        if (($candidate_ecosystem['module_role'] ?? '') !== 'base') {
            continue;
        }

        $base_for_modules = array_map(static function ($id) {
            return sanitize_key(str_replace('-', '_', (string) $id));
        }, (array) ($candidate_ecosystem['base_for_modules'] ?? []));

        if (in_array($module_id, $base_for_modules, true)) {
            return sanitize_key(str_replace('-', '_', (string) $candidate_module_id));
        }
    }

    return '';
};

$get_module_dashboard_admin_url = static function ($module_id) {
    $module_id = sanitize_key(str_replace('-', '_', (string) $module_id));
    $mapped_url = class_exists('Flavor_Module_Admin_Pages_Trait')
        ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($module_id)
        : null;

    if (!empty($mapped_url)) {
        return $mapped_url;
    }

    // Fallback al índice de dashboards si no hay mapping específico
    return admin_url('admin.php?page=flavor-module-dashboards');
};

$admin_social_panel = [
    'feed' => [],
    'nodes' => [],
    'groups' => [],
];

$get_admin_social_node_type_label = static function ($entity_type) {
    $labels = [
        'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'colectivo' => __('Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'energia_comunidad' => __('Energía', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'grupo_consumo' => __('Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'evento' => __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    return $labels[sanitize_key((string) $entity_type)] ?? __('Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN);
};

$get_admin_social_node_icon = static function ($entity_type) {
    $icons = [
        'comunidad' => '👥',
        'colectivo' => '🕸',
        'energia_comunidad' => '⚡',
        'grupo_consumo' => '🧺',
        'evento' => '📅',
    ];

    return $icons[sanitize_key((string) $entity_type)] ?? '👥';
};

$get_admin_social_node_cta = static function ($entity_type) {
    $labels = [
        'comunidad' => __('Abrir comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'colectivo' => __('Abrir colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'energia_comunidad' => __('Abrir energía', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'grupo_consumo' => __('Abrir consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'evento' => __('Abrir evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    return $labels[sanitize_key((string) $entity_type)] ?? __('Abrir nodo', FLAVOR_PLATFORM_TEXT_DOMAIN);
};

$get_admin_social_node_url = static function ($entity_type, $entity_id) {
    $entity_id = absint($entity_id);

    switch (sanitize_key((string) $entity_type)) {
        case 'comunidad':
            return home_url('/mi-portal/comunidades/?comunidad_id=' . $entity_id);
        case 'colectivo':
            return home_url('/mi-portal/colectivos/?colectivo_id=' . $entity_id);
        case 'energia_comunidad':
            return home_url('/mi-portal/energia-comunitaria/?comunidad_id=' . $entity_id);
        case 'grupo_consumo':
            return home_url('/mi-portal/grupos-consumo/');
        case 'evento':
            return home_url('/mi-portal/eventos/');
        default:
            return home_url('/mi-portal/comunidades/');
    }
};

if (class_exists('Flavor_Mi_Red_Social') && is_user_logged_in()) {
    $mi_red = Flavor_Mi_Red_Social::get_instance();
    $admin_user_id = get_current_user_id();

    if ($mi_red && $admin_user_id > 0) {
        $social_feed = method_exists($mi_red, 'obtener_feed_unificado')
            ? (array) $mi_red->obtener_feed_unificado($admin_user_id, 4, 0, 'todos')
            : [];
        $social_communities = method_exists($mi_red, 'obtener_comunidades_usuario')
            ? (array) $mi_red->obtener_comunidades_usuario($admin_user_id)
            : [];
        $social_groups = method_exists($mi_red, 'obtener_grupos_chat')
            ? (array) $mi_red->obtener_grupos_chat($admin_user_id)
            : [];
        $social_nodes_map = [];

        foreach (array_slice($social_feed, 0, 4) as $item) {
            $item = (array) $item;
            $title = trim((string) ($item['contenido']['titulo'] ?? ''));
            if ($title === '') {
                $title = wp_trim_words(wp_strip_all_tags((string) ($item['contenido']['texto'] ?? '')), 10);
            }

            $admin_social_panel['feed'][] = [
                'title' => $title !== '' ? $title : __('Publicación reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'meta' => trim((string) (($item['autor']['nombre'] ?? __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)) . (!empty($item['fecha_humana']) ? ' · ' . $item['fecha_humana'] : ''))),
                'url' => (string) ($item['url'] ?? '#'),
                'icon' => (string) ($item['tipo_info']['icon'] ?? '📝'),
            ];
        }

        foreach (array_slice($social_communities, 0, 5) as $community) {
            $community = (array) $community;
            $community_id = absint($community['id'] ?? 0);
            if ($community_id <= 0) {
                continue;
            }

            $social_nodes_map['comunidad:' . $community_id] = [
                'entity_type' => 'comunidad',
                'title' => (string) ($community['nombre'] ?? __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)),
                'meta' => !empty($community['miembros_count'])
                    ? sprintf(_n('%d miembro', '%d miembros', (int) $community['miembros_count'], FLAVOR_PLATFORM_TEXT_DOMAIN), (int) $community['miembros_count'])
                    : __('Nodo activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'summary' => '',
                'url' => $get_admin_social_node_url('comunidad', $community_id),
                'icon' => $get_admin_social_node_icon('comunidad'),
                'type_label' => $get_admin_social_node_type_label('comunidad'),
                'cta_label' => $get_admin_social_node_cta('comunidad'),
                'unread_count' => 0,
                'group_count' => 0,
            ];
        }

        foreach ($social_groups as $group) {
            $group = (array) $group;
            $entity_type = sanitize_key((string) ($group['entidad_tipo'] ?? ''));
            $entity_id = absint($group['entidad_id'] ?? 0);
            $members = absint($group['miembros'] ?? $group['miembros_count'] ?? 0);
            $unread = absint($group['mensajes_no_leidos'] ?? 0);
            $meta_parts = [];

            if ($entity_type === '' || $entity_id <= 0) {
                continue;
            }

            if (!in_array($entity_type, ['comunidad', 'colectivo', 'energia_comunidad', 'grupo_consumo', 'evento'], true)) {
                continue;
            }

            $node_key = $entity_type . ':' . $entity_id;
            if (!isset($social_nodes_map[$node_key])) {
                $social_nodes_map[$node_key] = [
                    'entity_type' => $entity_type,
                    'title' => trim((string) preg_replace('/^\s*chat\s*:\s*/i', '', (string) ($group['nombre'] ?? __('Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN)))),
                    'meta' => $get_admin_social_node_type_label($entity_type),
                    'summary' => '',
                    'url' => $get_admin_social_node_url($entity_type, $entity_id),
                    'icon' => $get_admin_social_node_icon($entity_type),
                    'type_label' => $get_admin_social_node_type_label($entity_type),
                    'cta_label' => $get_admin_social_node_cta($entity_type),
                    'unread_count' => 0,
                    'group_count' => 0,
                ];
            }

            $social_nodes_map[$node_key]['group_count']++;
            $social_nodes_map[$node_key]['unread_count'] += $unread;

            if ($members > 0) {
                $meta_parts[] = sprintf(_n('%d miembro', '%d miembros', $members, FLAVOR_PLATFORM_TEXT_DOMAIN), $members);
            }
            if ($unread > 0) {
                $meta_parts[] = sprintf(_n('%d no leído', '%d no leídos', $unread, FLAVOR_PLATFORM_TEXT_DOMAIN), $unread);
            }

            $admin_social_panel['groups'][] = [
                'title' => trim((string) preg_replace('/^\s*chat\s*:\s*/i', '', (string) ($group['nombre'] ?? __('Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN)))),
                'meta' => !empty($meta_parts) ? implode(' · ', $meta_parts) : __('Conversación activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => !empty($group['id']) ? home_url('/mi-portal/chat-grupos/mensajes/?grupo_id=' . absint($group['id'])) : home_url('/mi-portal/chat-grupos/'),
                'badge' => $unread > 0 ? (string) $unread : '',
            ];
        }

        foreach ($social_nodes_map as $node) {
            $summary_parts = [];
            if (!empty($node['group_count'])) {
                $summary_parts[] = sprintf(_n('%d grupo', '%d grupos', (int) $node['group_count'], FLAVOR_PLATFORM_TEXT_DOMAIN), (int) $node['group_count']);
            }
            if (!empty($node['unread_count'])) {
                $summary_parts[] = sprintf(_n('%d no leído', '%d no leídos', (int) $node['unread_count'], FLAVOR_PLATFORM_TEXT_DOMAIN), (int) $node['unread_count']);
            }

            $node['summary'] = !empty($summary_parts) ? implode(' · ', $summary_parts) : __('Sin actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $admin_social_panel['nodes'][] = $node;
        }

        usort($admin_social_panel['nodes'], static function ($a, $b) {
            if (($b['unread_count'] ?? 0) !== ($a['unread_count'] ?? 0)) {
                return ($b['unread_count'] ?? 0) <=> ($a['unread_count'] ?? 0);
            }

            return ($b['group_count'] ?? 0) <=> ($a['group_count'] ?? 0);
        });

        $admin_social_panel['nodes'] = array_slice($admin_social_panel['nodes'], 0, 5);
    }
}

if (!empty($widgets)) {
    foreach ($widgets as $widget) {
        $config = $widget->get_widget_config();
        $module_id = sanitize_key(str_replace('-', '_', (string) ($config['module'] ?? '')));
        if ($module_id === '') {
            continue;
        }
        $active_module_ids[$module_id] = true;

        $module_data = $registered_modules[$module_id] ?? [];
        $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];
        $dashboard = is_array($module_data['dashboard'] ?? null) ? $module_data['dashboard'] : [];
        $role = (string) ($ecosystem['module_role'] ?? 'vertical');
        $parent_module = sanitize_key(str_replace('-', '_', (string) ($dashboard['parent_module'] ?? ($ecosystem['depends_on'][0] ?? ''))));

        if ($role === 'base') {
            $parent_module = $module_id;
        }

        if ($parent_module === '') {
            $parent_module = $find_dashboard_base_parent_for_module($module_id, $registered_modules);
        }

        if ($parent_module === '') {
            continue;
        }

        if (!isset($ecosystem_nodes[$parent_module])) {
            $parent_data = $registered_modules[$parent_module] ?? [];
            $parent_ecosystem = is_array($parent_data['ecosystem'] ?? null) ? $parent_data['ecosystem'] : [];
            $parent_name = trim((string) ($parent_data['name'] ?? ''));
            if ($parent_name === '') {
                $parent_name = ucwords(str_replace(['_', '-'], ' ', $parent_module));
            }

            $ecosystem_nodes[$parent_module] = [
                'id' => $parent_module,
                'name' => $parent_name,
                'dashboard_url' => $get_module_dashboard_admin_url($parent_module),
                'role' => $parent_ecosystem['module_role'] ?? 'vertical',
                'priority' => absint($parent_data['dashboard']['satellite_priority'] ?? 50),
                'client_contexts' => $get_dashboard_module_contexts($parent_data),
                'context_match_score' => $calculate_dashboard_context_match($get_dashboard_module_contexts($parent_data), $dashboard_contexts),
                'satellites' => [],
                'widgets' => [],
            ];
        }

        if ($module_id !== $parent_module) {
            $satellite_name = trim((string) ($module_data['name'] ?? ''));
            if ($satellite_name === '') {
                $satellite_name = ucwords(str_replace(['_', '-'], ' ', $module_id));
            }

            $ecosystem_nodes[$parent_module]['satellites'][$module_id] = [
                'name' => $satellite_name,
                'url' => $get_module_dashboard_admin_url($module_id),
                'priority' => absint($module_data['dashboard']['satellite_priority'] ?? 50),
            ];
        }

        $ecosystem_nodes[$parent_module]['widgets'][] = [
            'id' => $config['id'] ?? '',
            'title' => $config['title'] ?? '',
            'actions' => (array) ($config['actions'] ?? []),
            'object' => $widget,
        ];
    }
}

foreach ($ecosystem_nodes as &$ecosystem_node) {
    $satellite_ids = array_keys($ecosystem_node['satellites']);
    $targets = array_values(array_unique(array_merge([$ecosystem_node['id']], $satellite_ids)));
    $transversals = [];
    foreach ($registered_modules as $candidate_module_id => $candidate_module) {
        $candidate_ecosystem = is_array($candidate_module['ecosystem'] ?? null) ? $candidate_module['ecosystem'] : [];
        if (($candidate_ecosystem['module_role'] ?? 'vertical') !== 'transversal') {
            continue;
        }

        $matched = false;
        foreach (['supports_modules', 'measures_modules', 'governs_modules', 'teaches_modules'] as $relation_key) {
            $related = array_map(static function ($id) {
                return sanitize_key(str_replace('-', '_', (string) $id));
            }, (array) ($candidate_ecosystem[$relation_key] ?? []));
            if (!empty(array_intersect($targets, $related))) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            continue;
        }

        $candidate_name = trim((string) ($candidate_module['name'] ?? ''));
        if ($candidate_name === '') {
            $candidate_name = ucwords(str_replace(['_', '-'], ' ', $candidate_module_id));
        }

        $transversals[] = [
            'id' => $candidate_module_id,
            'name' => $candidate_name,
            'is_active' => isset($active_module_ids[$candidate_module_id]),
            'priority' => absint($candidate_module['dashboard']['transversal_priority'] ?? 50),
            'client_contexts' => $get_dashboard_module_contexts($candidate_module),
            'context_match_score' => $calculate_dashboard_context_match($get_dashboard_module_contexts($candidate_module), $dashboard_contexts),
            'url' => $get_module_dashboard_admin_url($candidate_module_id),
        ];
    }

    usort($transversals, function ($a, $b) {
        if ($a['is_active'] !== $b['is_active']) {
            return $a['is_active'] ? -1 : 1;
        }

        if (($a['context_match_score'] ?? 0) !== ($b['context_match_score'] ?? 0)) {
            return ($a['context_match_score'] ?? 0) > ($b['context_match_score'] ?? 0) ? -1 : 1;
        }

        if (($a['priority'] ?? 50) !== ($b['priority'] ?? 50)) {
            return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
        }

        return strcmp($a['name'], $b['name']);
    });

    $ecosystem_node['transversals'] = array_slice($transversals, 0, 5);
    $ecosystem_node['satellites'] = array_values($ecosystem_node['satellites']);
    usort($ecosystem_node['satellites'], function ($a, $b) {
        if (($a['priority'] ?? 50) !== ($b['priority'] ?? 50)) {
            return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
        }

        return strcmp($a['name'], $b['name']);
    });
    $ecosystem_node['widget_count'] = count($ecosystem_node['widgets']);
    $ecosystem_node['satellite_count'] = count($ecosystem_node['satellites']);
    $ecosystem_node['transversal_count'] = count($ecosystem_node['transversals']);
}
unset($ecosystem_node);

$ecosystem_nodes = array_filter($ecosystem_nodes, static function ($ecosystem_node) {
    return !empty($ecosystem_node['satellite_count']);
});

$ecosystem_widget_ids = [];
foreach ($ecosystem_nodes as $ecosystem_node) {
    foreach ((array) ($ecosystem_node['widgets'] ?? []) as $node_widget) {
        $ecosystem_widget_ids[sanitize_key((string) ($node_widget['id'] ?? ''))] = true;
    }
}

$remaining_widgets_by_category = [];
foreach ($widgets_by_category as $cat_id => $cat_widgets) {
    foreach ($cat_widgets as $widget) {
        $widget_id = sanitize_key((string) $widget->get_widget_id());
        if (isset($ecosystem_widget_ids[$widget_id])) {
            continue;
        }
        if (!isset($remaining_widgets_by_category[$cat_id])) {
            $remaining_widgets_by_category[$cat_id] = [];
        }
        $remaining_widgets_by_category[$cat_id][] = $widget;
    }
}

$remaining_widgets_by_category = array_filter($remaining_widgets_by_category);

uasort($ecosystem_nodes, function ($a, $b) {
    if (($a['context_match_score'] ?? 0) !== ($b['context_match_score'] ?? 0)) {
        return ($a['context_match_score'] ?? 0) > ($b['context_match_score'] ?? 0) ? -1 : 1;
    }

    if (($a['priority'] ?? 50) !== ($b['priority'] ?? 50)) {
        return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
    }

    if (($a['satellite_count'] ?? 0) === ($b['satellite_count'] ?? 0)) {
        return strcmp($a['name'], $b['name']);
    }

    return ($a['satellite_count'] ?? 0) > ($b['satellite_count'] ?? 0) ? -1 : 1;
});

$executive_totals = [
    'ecosystems' => count($ecosystem_nodes),
    'widgets' => (int) $visible_count,
    'active_transversals' => 0,
    'suggested_transversals' => 0,
    'relevant_ecosystems' => 0,
];
$executive_actions = [];
$next_actions = [];

foreach ($ecosystem_nodes as $ecosystem_node) {
    if (!empty($ecosystem_node['context_match_score'])) {
        $executive_totals['relevant_ecosystems']++;
    }

    foreach ((array) ($ecosystem_node['transversals'] ?? []) as $transversal) {
        if (!empty($transversal['is_active'])) {
            $executive_totals['active_transversals']++;
        } else {
            $executive_totals['suggested_transversals']++;
        }
    }

    if (!empty($ecosystem_node['satellites'])) {
        $executive_actions[] = [
            'kind' => __('Coordinar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'title' => sprintf(__('Abrir %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $ecosystem_node['name']),
            'description' => sprintf(
                esc_html(_n('%d satelite operativo disponible', '%d satelites operativos disponibles', $ecosystem_node['satellite_count'], FLAVOR_PLATFORM_TEXT_DOMAIN)),
                (int) $ecosystem_node['satellite_count']
            ),
            'url' => $ecosystem_node['dashboard_url'] ?? ('#fud-ecosystem-group-' . $ecosystem_node['id']),
            'priority' => 90 + (int) ($ecosystem_node['context_match_score'] ?? 0),
        ];
    }

    foreach ((array) ($ecosystem_node['transversals'] ?? []) as $transversal) {
        if (empty($transversal['is_active'])) {
            $executive_actions[] = [
                'kind' => __('Completar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'title' => sprintf(__('Activar %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $transversal['name']),
                'description' => __('Capa transversal sugerida para reforzar este ecosistema.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => $transversal['url'],
                'priority' => 70 + (int) ($transversal['context_match_score'] ?? 0),
            ];
        }
    }

    if (!empty($ecosystem_node['context_match_score'])) {
        $severity = $get_admin_action_severity('focus');
        $next_actions[] = [
            'kind' => __('Foco', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'severity' => $severity['label'],
            'severity_slug' => $severity['slug'],
            'title' => sprintf(__('Entrar en %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $ecosystem_node['name']),
            'description' => __('Es el ecosistema más alineado con la vista actual del dashboard.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'url' => $ecosystem_node['dashboard_url'] ?? ('#fud-ecosystem-group-' . $ecosystem_node['id']),
            'priority' => 96 + (int) $ecosystem_node['context_match_score'],
        ];
    }

    foreach ((array) ($ecosystem_node['widgets'] ?? []) as $node_widget) {
        foreach ((array) ($node_widget['actions'] ?? []) as $widget_action) {
            if (($widget_action['type'] ?? '') !== 'link' || empty($widget_action['url'])) {
                continue;
            }

            $severity = $get_admin_action_severity('review');
            $next_actions[] = [
                'kind' => __('Revisar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'severity' => $severity['label'],
                'severity_slug' => $severity['slug'],
                'title' => sprintf(__('Abrir %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $node_widget['title']),
                'description' => __('Acceso directo a un widget con información o gestión ya disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => $widget_action['url'],
                'priority' => 72 + (int) ($ecosystem_node['context_match_score'] ?? 0),
            ];
            break;
        }
    }
}

if ($executive_totals['suggested_transversals'] > 0) {
    $severity = $get_admin_action_severity('complete');
    $next_actions[] = [
        'kind' => __('Completar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'severity' => $severity['label'],
        'severity_slug' => $severity['slug'],
        'title' => __('Atender capas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'description' => sprintf(
            esc_html(_n('%d capa transversal sugerida sigue pendiente.', '%d capas transversales sugeridas siguen pendientes.', $executive_totals['suggested_transversals'], FLAVOR_PLATFORM_TEXT_DOMAIN)),
            (int) $executive_totals['suggested_transversals']
        ),
        'url' => '#fud-ecosystem-title',
        'priority' => 94,
    ];
}

usort($executive_actions, static function ($a, $b) {
    return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
});
$executive_actions = array_slice($executive_actions, 0, 4);

usort($next_actions, static function ($a, $b) {
    return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
});
$next_actions = array_slice(array_values(array_unique($next_actions, SORT_REGULAR)), 0, 4);
?>
<div class="wrap fl-dashboard-wrapper fud-wrapper"
     data-view-mode="<?php echo esc_attr($view_mode); ?>"
     data-active-filter="<?php echo esc_attr($active_filter); ?>">

    <!-- Skip Links (Accesibilidad) -->
    <a href="#fl-main-content" class="fl-skip-link"><?php esc_html_e('Saltar al contenido principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    <a href="#fl-category-filters" class="fl-skip-link"><?php esc_html_e('Saltar a filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>

    <!-- Instrucciones Drag & Drop (Screen Readers) -->
    <div id="fl-drag-instructions" class="fl-sr-only">
        <?php esc_html_e('Para reordenar widgets: presione Enter o Espacio para activar el modo arrastre, use las flechas para mover, Enter para soltar o Escape para cancelar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </div>

    <!-- =========================================================
         BREADCRUMBS
    ========================================================= -->
    <nav class="fl-breadcrumbs" aria-label="<?php esc_attr_e('Navegacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <ol class="fl-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li class="fl-breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?php echo esc_url(admin_url()); ?>" class="fl-breadcrumbs__link" itemprop="item">
                    <span class="fl-breadcrumbs__icon dashicons dashicons-admin-home" aria-hidden="true"></span>
                    <span itemprop="name"><?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <meta itemprop="position" content="1">
            </li>
            <li class="fl-breadcrumbs__separator" aria-hidden="true">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </li>
            <li class="fl-breadcrumbs__item fl-breadcrumbs__item--current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span class="fl-breadcrumbs__current" itemprop="item">
                    <span class="fl-breadcrumbs__icon dashicons dashicons-dashboard" aria-hidden="true"></span>
                    <span itemprop="name"><?php esc_html_e('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </span>
                <meta itemprop="position" content="2">
            </li>
        </ol>
    </nav>

    <!-- =========================================================
         HEADER
    ========================================================= -->
    <header class="fl-dashboard-header fud-header">
        <div class="fl-dashboard-header__left fud-header-left">
            <h1 class="fl-dashboard-header__title fud-title">
                <span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
                <?php esc_html_e('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <span class="fl-dashboard-header__subtitle fud-subtitle">
                <?php printf(
                    esc_html(_n('%d widget activo', '%d widgets activos', $visible_count, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                    $visible_count
                ); ?>
            </span>
        </div>

        <div class="fl-dashboard-header__actions fud-header-actions">
            <!-- Toggle Vista -->
            <div class="fl-view-toggle fud-view-toggle" role="group" aria-label="<?php esc_attr_e('Modo de vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <button type="button"
                        class="fl-view-btn fud-view-btn <?php echo $view_mode === 'grid' ? 'active' : ''; ?>"
                        data-view="grid"
                        aria-pressed="<?php echo $view_mode === 'grid' ? 'true' : 'false'; ?>"
                        title="<?php esc_attr_e('Vista cuadricula', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
                    <span class="fl-sr-only"><?php esc_html_e('Cuadricula', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </button>
                <button type="button"
                        class="fl-view-btn fud-view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>"
                        data-view="list"
                        aria-pressed="<?php echo $view_mode === 'list' ? 'true' : 'false'; ?>"
                        title="<?php esc_attr_e('Vista lista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                    <span class="fl-sr-only"><?php esc_html_e('Lista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </button>
            </div>

            <!-- Boton Personalizar -->
            <button type="button" class="button fl-btn fl-btn--secondary fud-btn-secondary" id="fud-customize-btn">
                <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                <?php esc_html_e('Personalizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>

            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-module-dashboards')); ?>" class="button fl-btn fl-btn--secondary fud-btn-secondary">
                <span class="dashicons dashicons-chart-pie" aria-hidden="true"></span>
                <?php esc_html_e('Índice de dashboards', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>

            <!-- Boton Actualizar -->
            <button type="button" class="button button-primary fl-btn fl-btn--primary fud-btn-primary" id="fud-refresh-all-btn">
                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                <?php esc_html_e('Actualizar todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </header>

    <?php if (!empty($ecosystem_nodes)) : ?>
    <section class="fud-executive-summary" aria-labelledby="fud-executive-title">
        <div class="fud-executive-summary__header">
            <div>
                <h2 id="fud-executive-title" class="fud-executive-summary__title">
                    <?php esc_html_e('Visión ejecutiva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="fud-executive-summary__description">
                    <?php esc_html_e('Una lectura rápida del nodo, sus capas activas y los siguientes movimientos recomendados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>
        </div>
        <div class="fud-executive-summary__grid">
            <article class="fud-summary-card">
                <span class="fud-summary-card__label"><?php esc_html_e('Ecosistemas activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <strong class="fud-summary-card__value"><?php echo esc_html($executive_totals['ecosystems']); ?></strong>
                <p class="fud-summary-card__hint"><?php esc_html_e('Bases y satélites operativos con widget visible en este dashboard.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </article>
            <article class="fud-summary-card">
                <span class="fud-summary-card__label"><?php esc_html_e('Herramientas visibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <strong class="fud-summary-card__value"><?php echo esc_html($executive_totals['widgets']); ?></strong>
                <p class="fud-summary-card__hint"><?php esc_html_e('Widgets activos listos para operación, seguimiento o revisión.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </article>
            <article class="fud-summary-card">
                <span class="fud-summary-card__label"><?php esc_html_e('Capas transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <strong class="fud-summary-card__value"><?php echo esc_html($executive_totals['active_transversals']); ?></strong>
                <p class="fud-summary-card__hint">
                    <?php
                    printf(
                        esc_html(_n('%d sugerencia pendiente', '%d sugerencias pendientes', $executive_totals['suggested_transversals'], FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        (int) $executive_totals['suggested_transversals']
                    );
                    ?>
                </p>
            </article>
            <article class="fud-summary-card">
                <span class="fud-summary-card__label"><?php esc_html_e('Foco de esta vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <strong class="fud-summary-card__value"><?php echo esc_html($executive_totals['relevant_ecosystems']); ?></strong>
                <p class="fud-summary-card__hint"><?php esc_html_e('Ecosistemas priorizados por el contexto actual del dashboard.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </article>
        </div>

        <?php if (!empty($next_actions)) : ?>
        <div class="fud-next-actions">
            <div class="fud-next-actions__header">
                <h3 class="fud-next-actions__title"><?php esc_html_e('Qué hacer ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="fud-next-actions__subtitle"><?php esc_html_e('Una secuencia corta para destrabar gestión, abrir foco y completar capas del nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <div class="fud-severity-legend" aria-label="<?php esc_attr_e('Leyenda de prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="fud-severity-legend__label"><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="fud-severity-legend__item">
                        <span class="fud-severity-legend__dot fud-severity-legend__dot--attention" aria-hidden="true"></span>
                        <?php esc_html_e('Atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <span class="fud-severity-legend__item">
                        <span class="fud-severity-legend__dot fud-severity-legend__dot--followup" aria-hidden="true"></span>
                        <?php esc_html_e('Seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <span class="fud-severity-legend__item">
                        <span class="fud-severity-legend__dot fud-severity-legend__dot--stable" aria-hidden="true"></span>
                        <?php esc_html_e('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            </div>
            <div class="fud-next-actions__grid">
                <?php foreach ($next_actions as $action) : ?>
                <a href="<?php echo esc_url($action['url']); ?>" class="fud-next-action fud-next-action--<?php echo esc_attr($action['severity_slug'] ?? 'stable'); ?>" data-severity="<?php echo esc_attr($action['severity_slug'] ?? 'stable'); ?>">
                    <div class="fud-next-action__meta">
                        <span class="fud-next-action__kind"><?php echo esc_html($action['kind']); ?></span>
                        <?php if (!empty($action['severity'])) : ?>
                        <span class="fud-next-action__severity fud-next-action__severity--<?php echo esc_attr($action['severity_slug'] ?? 'stable'); ?>">
                            <?php echo esc_html($action['severity']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <strong class="fud-next-action__title"><?php echo esc_html($action['title']); ?></strong>
                    <span class="fud-next-action__description"><?php echo esc_html($action['description']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($executive_actions)) : ?>
        <div class="fud-executive-tools">
            <div class="fud-executive-tools__header">
                <h3 class="fud-executive-tools__title"><?php esc_html_e('Herramientas clave', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="fud-executive-tools__subtitle"><?php esc_html_e('Accesos cortos para coordinar, completar o abrir los puntos más útiles ahora mismo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="fud-executive-tools__grid">
                <?php foreach ($executive_actions as $action) : ?>
                <a href="<?php echo esc_url($action['url']); ?>" class="fud-executive-tool">
                    <span class="fud-executive-tool__kind"><?php echo esc_html($action['kind']); ?></span>
                    <strong class="fud-executive-tool__title"><?php echo esc_html($action['title']); ?></strong>
                    <span class="fud-executive-tool__description"><?php echo esc_html($action['description']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($admin_social_panel['feed']) || !empty($admin_social_panel['nodes']) || !empty($admin_social_panel['groups'])) : ?>
    <section class="fud-admin-social" aria-labelledby="fud-admin-social-title">
        <div class="fud-admin-social__header">
            <h2 id="fud-admin-social-title" class="fud-admin-social__title"><?php esc_html_e('Pulso social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="fud-admin-social__description"><?php esc_html_e('Una lectura rápida de publicaciones, nodos comunitarios y conversaciones activas ligadas a tu red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <div class="fud-admin-social__filters" role="toolbar" aria-label="<?php esc_attr_e('Filtrar pulso social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <button type="button" class="fud-admin-social__filter is-active" data-social-filter="all" aria-pressed="true"><?php esc_html_e('Todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="fud-admin-social__filter" data-social-filter="nodes" aria-pressed="false"><?php esc_html_e('Nodos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="fud-admin-social__filter" data-social-filter="groups" aria-pressed="false"><?php esc_html_e('Con no leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="fud-admin-social__filter" data-social-filter="feed" aria-pressed="false"><?php esc_html_e('Posts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
        <div class="fud-admin-social__grid">
            <article class="fud-admin-social__card" data-social-card="feed">
                <div class="fud-admin-social__card-head">
                    <h3 class="fud-admin-social__card-title"><?php esc_html_e('Últimos posts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(home_url('/mi-portal/mi-red/')); ?>" class="fud-admin-social__link"><?php esc_html_e('Abrir red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
                <?php if (!empty($admin_social_panel['feed'])) : ?>
                    <div class="fud-admin-social__list">
                        <?php foreach ($admin_social_panel['feed'] as $item) : ?>
                            <a href="<?php echo esc_url($item['url']); ?>" class="fud-admin-social__item">
                                <span class="fud-admin-social__item-icon"><?php echo esc_html($item['icon']); ?></span>
                                <span class="fud-admin-social__item-copy">
                                    <span class="fud-admin-social__item-title"><?php echo esc_html($item['title']); ?></span>
                                    <span class="fud-admin-social__item-meta"><?php echo esc_html($item['meta']); ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="fud-admin-social__empty"><?php esc_html_e('Todavía no hay publicaciones recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </article>

            <article class="fud-admin-social__card" data-social-card="nodes">
                <div class="fud-admin-social__card-head">
                    <h3 class="fud-admin-social__card-title"><?php esc_html_e('Nodos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/')); ?>" class="fud-admin-social__link"><?php esc_html_e('Ver espacios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
                <?php if (!empty($admin_social_panel['nodes'])) : ?>
                    <div class="fud-admin-social__sort" role="toolbar" aria-label="<?php esc_attr_e('Ordenar nodos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="fud-admin-social__sort-label"><?php esc_html_e('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <button type="button" class="fud-admin-social__sort-btn is-active" data-social-sort="unread" aria-pressed="true"><?php esc_html_e('Más no leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <button type="button" class="fud-admin-social__sort-btn" data-social-sort="active" aria-pressed="false"><?php esc_html_e('Más activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </div>
                    <div class="fud-admin-social__list">
                        <?php foreach ($admin_social_panel['nodes'] as $item) : ?>
                            <?php $admin_node_type = sanitize_key((string) ($item['entity_type'] ?? 'comunidad')); ?>
                            <a href="<?php echo esc_url($item['url']); ?>" class="fud-admin-social__item fud-admin-social__item--<?php echo esc_attr($admin_node_type); ?>" data-node-unread="<?php echo esc_attr((int) ($item['unread_count'] ?? 0)); ?>" data-node-groups="<?php echo esc_attr((int) ($item['group_count'] ?? 0)); ?>">
                                <span class="fud-admin-social__item-icon"><?php echo esc_html($item['icon'] ?? '👥'); ?></span>
                                <span class="fud-admin-social__item-copy">
                                    <?php if (!empty($item['type_label'])) : ?>
                                        <span class="fud-admin-social__item-type fud-admin-social__item-type--<?php echo esc_attr($admin_node_type); ?>"><?php echo esc_html($item['type_label']); ?></span>
                                    <?php endif; ?>
                                    <span class="fud-admin-social__item-title"><?php echo esc_html($item['title']); ?></span>
                                    <span class="fud-admin-social__item-meta"><?php echo esc_html($item['meta']); ?></span>
                                    <?php if (!empty($item['summary'])) : ?>
                                        <span class="fud-admin-social__item-summary"><?php echo esc_html($item['summary']); ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($item['cta_label'])) : ?>
                                    <span class="fud-admin-social__item-cta"><?php echo esc_html($item['cta_label']); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="fud-admin-social__empty"><?php esc_html_e('Todavía no hay nodos activos visibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </article>

            <article class="fud-admin-social__card" data-social-card="groups">
                <div class="fud-admin-social__card-head">
                    <h3 class="fud-admin-social__card-title"><?php esc_html_e('Conversaciones abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(home_url('/mi-portal/chat-grupos/')); ?>" class="fud-admin-social__link"><?php esc_html_e('Abrir grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
                <?php if (!empty($admin_social_panel['groups'])) : ?>
                    <div class="fud-admin-social__list">
                        <?php foreach ($admin_social_panel['groups'] as $item) : ?>
                            <a href="<?php echo esc_url($item['url']); ?>" class="fud-admin-social__item">
                                <span class="fud-admin-social__item-icon">💬</span>
                                <span class="fud-admin-social__item-copy">
                                    <span class="fud-admin-social__item-title"><?php echo esc_html($item['title']); ?></span>
                                    <span class="fud-admin-social__item-meta"><?php echo esc_html($item['meta']); ?></span>
                                </span>
                                <?php if (!empty($item['badge'])) : ?>
                                    <span class="fud-admin-social__badge"><?php echo esc_html($item['badge']); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="fud-admin-social__empty"><?php esc_html_e('Todavía no hay grupos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </article>
        </div>
    </section>
    <?php endif; ?>

    <div class="fud-priority-filter" role="group" aria-label="<?php esc_attr_e('Filtrar por prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <span class="fud-priority-filter__label"><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        <button type="button" class="fud-priority-filter__btn is-active" data-priority="all" aria-pressed="true"><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <button type="button" class="fud-priority-filter__btn" data-priority="attention" aria-pressed="false"><?php esc_html_e('Atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <button type="button" class="fud-priority-filter__btn" data-priority="followup" aria-pressed="false"><?php esc_html_e('Seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <button type="button" class="fud-priority-filter__btn" data-priority="stable" aria-pressed="false"><?php esc_html_e('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </div>

    <?php if (!empty($ecosystem_nodes)) : ?>
    <section class="fud-ecosystem-hierarchy" aria-labelledby="fud-ecosystem-title">
        <div class="fud-ecosystem-hierarchy__header">
            <h2 id="fud-ecosystem-title" class="fud-ecosystem-hierarchy__title">
                <?php esc_html_e('Ecosistemas activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="fud-ecosystem-hierarchy__description">
                <?php esc_html_e('Agrupa los widgets por modulo base y sus satelites para entender mejor la operativa del nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="fud-ecosystem-hierarchy__grid">
            <?php foreach ($ecosystem_nodes as $ecosystem_node) : ?>
            <article class="fud-ecosystem-card">
                <div class="fud-ecosystem-card__head">
                    <div>
                        <h3 class="fud-ecosystem-card__name"><?php echo esc_html($ecosystem_node['name']); ?></h3>
                        <span class="fud-ecosystem-card__role"><?php echo esc_html(ucfirst($ecosystem_node['role'])); ?></span>
                        <?php if (!empty($ecosystem_node['context_match_score'])) : ?>
                        <div class="fud-ecosystem-card__context"><?php esc_html_e('Relevante en esta vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="fud-ecosystem-card__count"><?php echo esc_html($ecosystem_node['satellite_count']); ?></span>
                </div>
                <?php if (!empty($ecosystem_node['satellites'])) : ?>
                <div class="fud-ecosystem-card__block">
                    <div class="fud-ecosystem-card__label"><?php esc_html_e('Satelites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="fud-ecosystem-card__tags">
                        <?php foreach ($ecosystem_node['satellites'] as $satellite) : ?>
                        <a href="<?php echo esc_url($satellite['url'] ?? '#'); ?>" class="fud-ecosystem-card__tag"><?php echo esc_html($satellite['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($ecosystem_node['widgets'])) : ?>
                <div class="fud-ecosystem-card__block">
                    <div class="fud-ecosystem-card__label"><?php esc_html_e('Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="fud-ecosystem-card__tags">
                        <?php foreach (array_slice($ecosystem_node['widgets'], 0, 4) as $node_widget) : ?>
                        <span class="fud-ecosystem-card__tag fud-ecosystem-card__tag--widget"><?php echo esc_html($node_widget['title']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($ecosystem_node['transversals'])) : ?>
                <div class="fud-ecosystem-card__block">
                    <div class="fud-ecosystem-card__label"><?php esc_html_e('Capas transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="fud-ecosystem-card__tags">
                        <?php foreach ($ecosystem_node['transversals'] as $transversal) : ?>
                        <a href="<?php echo esc_url($transversal['url']); ?>" class="fud-ecosystem-card__tag <?php echo !empty($transversal['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                            <?php echo esc_html($transversal['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- =========================================================
         FILTROS DE CATEGORIA
    ========================================================= -->
    <?php if (!empty($categories)): ?>
    <nav class="fl-category-filters" id="fl-category-filters" role="navigation" aria-label="<?php esc_attr_e('Filtrar por categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <button type="button"
                class="fl-category-filter fl-category-filter--all <?php echo $active_filter === 'all' ? 'active' : ''; ?>"
                data-category="all"
                aria-pressed="<?php echo $active_filter === 'all' ? 'true' : 'false'; ?>">
            <span class="dashicons dashicons-menu" aria-hidden="true"></span>
            <span class="fl-category-filter__label"><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <span class="fl-category-filter__badge" aria-label="<?php printf(esc_attr__('%d widgets', FLAVOR_PLATFORM_TEXT_DOMAIN), $visible_count); ?>">
                <?php echo absint($visible_count); ?>
            </span>
        </button>

        <?php foreach ($categories as $cat_id => $cat_info):
            $cat_widgets_count = isset($widgets_by_category[$cat_id]) ? count($widgets_by_category[$cat_id]) : 0;
            if ($cat_widgets_count === 0) continue;

            $cat_icon = $cat_info['icon'] ?? 'dashicons-admin-generic';
            $cat_label = $cat_info['label'] ?? ucfirst($cat_id);
            $cat_color = $cat_info['color'] ?? '#4f46e5';
        ?>
            <button type="button"
                    class="fl-category-filter fl-category-filter--<?php echo esc_attr($cat_id); ?> <?php echo $active_filter === $cat_id ? 'active' : ''; ?>"
                    data-category="<?php echo esc_attr($cat_id); ?>"
                    aria-pressed="<?php echo $active_filter === $cat_id ? 'true' : 'false'; ?>"
                    style="--fl-cat-color: <?php echo esc_attr($cat_color); ?>;">
                <span class="dashicons <?php echo esc_attr($cat_icon); ?>" aria-hidden="true"></span>
                <span class="fl-category-filter__label"><?php echo esc_html($cat_label); ?></span>
                <span class="fl-category-filter__badge" aria-label="<?php printf(esc_attr__('%d widgets', FLAVOR_PLATFORM_TEXT_DOMAIN), $cat_widgets_count); ?>">
                    <?php echo absint($cat_widgets_count); ?>
                </span>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Dropdown para mobile -->
    <div class="fl-category-dropdown" style="display: none;">
        <select id="fl-category-select" class="fl-category-select" aria-label="<?php esc_attr_e('Filtrar por categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('Todas las categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach ($categories as $cat_id => $cat_info):
                $cat_widgets_count = isset($widgets_by_category[$cat_id]) ? count($widgets_by_category[$cat_id]) : 0;
                if ($cat_widgets_count === 0) continue;
            ?>
                <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($active_filter, $cat_id); ?>>
                    <?php echo esc_html($cat_info['label'] ?? ucfirst($cat_id)); ?> (<?php echo $cat_widgets_count; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================= -->
    <main class="fl-dashboard-main fud-widgets-container" id="fl-main-content" role="main">
        <?php if (empty($widgets)): ?>
            <!-- Estado Vacio -->
            <div class="fl-empty-state fud-empty-state">
                <span class="fl-empty-state__icon dashicons dashicons-layout" aria-hidden="true"></span>
                <h2 class="fl-empty-state__title"><?php esc_html_e('No hay widgets visibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="fl-empty-state__message"><?php esc_html_e('Usa el boton "Personalizar" para elegir que widgets mostrar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <button type="button" class="button button-primary fl-empty-state__action" id="fl-empty-customize">
                    <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                    <?php esc_html_e('Personalizar Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

        <?php elseif (!empty($ecosystem_nodes)): ?>
            <section class="fud-ecosystem-groups" aria-labelledby="fud-ecosystem-groups-title">
                <h2 id="fud-ecosystem-groups-title" class="fl-sr-only">
                    <?php esc_html_e('Widgets agrupados por ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>

                <?php foreach ($ecosystem_nodes as $ecosystem_node):
                    $ecosystem_content_id = 'fud-ecosystem-group-' . $ecosystem_node['id'];
                ?>
                <section class="fl-widget-group fud-widget-group fud-widget-group--ecosystem"
                         id="fud-ecosystem-group-<?php echo esc_attr($ecosystem_node['id']); ?>"
                         data-ecosystem="<?php echo esc_attr($ecosystem_node['id']); ?>">
                    <header class="fl-widget-group__header">
                        <div class="fud-ecosystem-group__intro">
                            <div class="fud-ecosystem-group__title-row">
                                <h2 class="fl-widget-group__title"><?php echo esc_html($ecosystem_node['name']); ?></h2>
                                <span class="fud-ecosystem-card__role"><?php echo esc_html(ucfirst($ecosystem_node['role'])); ?></span>
                                <?php if (!empty($ecosystem_node['context_match_score'])) : ?>
                                <span class="fud-ecosystem-card__context"><?php esc_html_e('Relevante en esta vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($ecosystem_node['satellites'])) : ?>
                            <div class="fud-ecosystem-group__tags">
                                <?php foreach ($ecosystem_node['satellites'] as $satellite) : ?>
                                <a href="<?php echo esc_url($satellite['url'] ?? '#'); ?>" class="fud-ecosystem-card__tag"><?php echo esc_html($satellite['name']); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($ecosystem_node['transversals'])) : ?>
                            <div class="fud-ecosystem-group__tags">
                                <?php foreach ($ecosystem_node['transversals'] as $transversal) : ?>
                                <a href="<?php echo esc_url($transversal['url']); ?>" class="fud-ecosystem-card__tag <?php echo !empty($transversal['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                                    <?php echo esc_html($transversal['name']); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="fl-widget-group__toggle"
                                aria-expanded="true"
                                aria-controls="<?php echo esc_attr($ecosystem_content_id); ?>">
                            <span class="fl-widget-group__count"><?php echo esc_html($ecosystem_node['widget_count']); ?></span>
                            <span class="fl-widget-group__arrow dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                        </button>
                    </header>

                    <div class="fl-widget-group__content fl-widgets-grid fud-widgets-grid"
                         id="<?php echo esc_attr($ecosystem_content_id); ?>">
                        <?php foreach ($ecosystem_node['widgets'] as $node_widget) : ?>
                            <?php echo $renderer->render($node_widget['object']); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
                </section>
            <?php if (!empty($remaining_widgets_by_category)) : ?>
            <section class="fud-secondary-widget-groups" aria-labelledby="fud-secondary-widget-groups-title">
                <div class="fud-secondary-widget-groups__header">
                    <h2 id="fud-secondary-widget-groups-title" class="fud-secondary-widget-groups__title"><?php esc_html_e('Otros espacios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="fud-secondary-widget-groups__description"><?php esc_html_e('Widgets activos que no forman un ecosistema jerárquico completo en esta vista.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="fl-widgets-grouped fud-widgets-grouped">
                    <?php foreach ($remaining_widgets_by_category as $cat_id => $cat_widgets):
                        if (empty($cat_widgets)) continue;

                        $cat_info = $categories[$cat_id] ?? [
                            'label' => ucfirst($cat_id),
                            'icon'  => 'dashicons-admin-generic',
                            'color' => '#4f46e5',
                        ];

                        $is_collapsed = in_array($cat_id, $collapsed_categories);
                        $group_id = 'fl-remaining-group-' . esc_attr($cat_id);
                        $content_id = 'fl-remaining-group-content-' . esc_attr($cat_id);
                    ?>
                    <section class="fl-widget-group fud-widget-group <?php echo $is_collapsed ? 'fl-widget-group--collapsed' : ''; ?>"
                             data-category="<?php echo esc_attr($cat_id); ?>"
                             id="<?php echo $group_id; ?>"
                             aria-labelledby="<?php echo $group_id; ?>-title"
                             style="--fl-group-color: <?php echo esc_attr($cat_info['color'] ?? '#4f46e5'); ?>;">
                        <header class="fl-widget-group__header">
                            <button type="button"
                                    class="fl-widget-group__toggle"
                                    aria-expanded="<?php echo $is_collapsed ? 'false' : 'true'; ?>"
                                    aria-controls="<?php echo $content_id; ?>">
                                <span class="fl-widget-group__icon dashicons <?php echo esc_attr($cat_info['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                                <h2 class="fl-widget-group__title" id="<?php echo $group_id; ?>-title">
                                    <?php echo esc_html($cat_info['label'] ?? ucfirst($cat_id)); ?>
                                </h2>
                                <span class="fl-widget-group__count"><?php echo count($cat_widgets); ?></span>
                                <span class="fl-widget-group__arrow dashicons <?php echo $is_collapsed ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>" aria-hidden="true"></span>
                            </button>
                        </header>
                        <div class="fl-widget-group__content fl-widgets-grid fud-widgets-grid"
                             id="<?php echo $content_id; ?>"
                             <?php echo $is_collapsed ? 'hidden' : ''; ?>>
                            <?php foreach ($cat_widgets as $widget): ?>
                                <?php echo $renderer->render($widget); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        <?php elseif (!empty($widgets_by_category)): ?>
            <!-- Widgets Agrupados por Categoria -->
            <div class="fl-widgets-grouped fud-widgets-grouped">
                <?php foreach ($widgets_by_category as $cat_id => $cat_widgets):
                    if (empty($cat_widgets)) continue;

                    $cat_info = $categories[$cat_id] ?? [
                        'label' => ucfirst($cat_id),
                        'icon'  => 'dashicons-admin-generic',
                        'color' => '#4f46e5',
                    ];

                    $is_collapsed = in_array($cat_id, $collapsed_categories);
                    $group_id = 'fl-group-' . esc_attr($cat_id);
                    $content_id = 'fl-group-content-' . esc_attr($cat_id);
                ?>
                <section class="fl-widget-group fud-widget-group <?php echo $is_collapsed ? 'fl-widget-group--collapsed' : ''; ?>"
                         data-category="<?php echo esc_attr($cat_id); ?>"
                         id="<?php echo $group_id; ?>"
                         aria-labelledby="<?php echo $group_id; ?>-title"
                         style="--fl-group-color: <?php echo esc_attr($cat_info['color'] ?? '#4f46e5'); ?>;">

                    <!-- Header del Grupo -->
                    <header class="fl-widget-group__header">
                        <button type="button"
                                class="fl-widget-group__toggle"
                                aria-expanded="<?php echo $is_collapsed ? 'false' : 'true'; ?>"
                                aria-controls="<?php echo $content_id; ?>">
                            <span class="fl-widget-group__icon dashicons <?php echo esc_attr($cat_info['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                            <h2 class="fl-widget-group__title" id="<?php echo $group_id; ?>-title">
                                <?php echo esc_html($cat_info['label'] ?? ucfirst($cat_id)); ?>
                            </h2>
                            <span class="fl-widget-group__count" aria-label="<?php printf(esc_attr__('%d widgets en esta categoria', FLAVOR_PLATFORM_TEXT_DOMAIN), count($cat_widgets)); ?>">
                                <?php echo count($cat_widgets); ?>
                            </span>
                            <span class="fl-widget-group__arrow dashicons <?php echo $is_collapsed ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>" aria-hidden="true"></span>
                        </button>

                        <div class="fl-widget-group__actions">
                            <button type="button"
                                    class="fl-widget-group__action fl-widget-group__action--refresh"
                                    aria-label="<?php printf(esc_attr__('Actualizar widgets de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $cat_info['label'] ?? $cat_id); ?>"
                                    data-category="<?php echo esc_attr($cat_id); ?>">
                                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            </button>
                        </div>
                    </header>

                    <!-- Contenido del Grupo -->
                    <div class="fl-widget-group__content fl-widgets-grid fud-widgets-grid"
                         id="<?php echo $content_id; ?>"
                         <?php echo $is_collapsed ? 'hidden' : ''; ?>>
                        <?php foreach ($cat_widgets as $widget): ?>
                            <?php echo $renderer->render($widget); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Grid Simple (fallback) -->
            <div class="fl-widgets-grid fud-widgets-grid" id="fud-widgets-grid">
                <?php foreach ($widgets as $widget): ?>
                    <?php echo $renderer->render($widget); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- =========================================================
         FOOTER
    ========================================================= -->
    <footer class="fl-dashboard-footer fud-footer">
        <span class="fl-last-update fud-last-update">
            <span class="dashicons dashicons-clock" aria-hidden="true"></span>
            <?php esc_html_e('Ultima actualizacion:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <time datetime="<?php echo esc_attr($last_refresh); ?>" id="fud-last-refresh">
                <?php echo esc_html(date_i18n(get_option('time_format'), current_time('timestamp'))); ?>
            </time>
        </span>
    </footer>

    <!-- =========================================================
         MODAL DE PERSONALIZACION
    ========================================================= -->
    <div class="fl-modal fud-modal" id="fud-customize-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="fud-modal-title">
        <div class="fl-modal__overlay fud-modal-overlay"></div>
        <div class="fl-modal__content fud-modal-content">
            <header class="fl-modal__header fud-modal-header">
                <h2 id="fud-modal-title"><?php esc_html_e('Personalizar Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <button type="button" class="fl-modal__close fud-modal-close" aria-label="<?php esc_attr_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
            </header>

            <div class="fl-modal__body fud-modal-body">
                <p class="fl-modal__help fud-modal-help">
                    <?php esc_html_e('Arrastra para reordenar. Desmarca para ocultar widgets.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>

                <ul class="fl-widgets-list fud-widgets-list" id="fud-widgets-sortable" role="listbox" aria-label="<?php esc_attr_e('Lista de widgets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <?php foreach ($all_widgets as $widget):
                        $config = $widget->get_widget_config();
                        $widget_id = $widget->get_widget_id();
                        $is_visible = in_array($widget_id, $user_prefs['widgetVisibility'] ?? array_map(fn($w) => $w->get_widget_id(), $all_widgets));
                        $widget_category = $config['category'] ?? 'sistema';
                        $cat_color = isset($categories[$widget_category]['color']) ? $categories[$widget_category]['color'] : '#4f46e5';
                    ?>
                        <li class="fl-widget-item fud-widget-item"
                            data-widget-id="<?php echo esc_attr($widget_id); ?>"
                            data-category="<?php echo esc_attr($widget_category); ?>"
                            role="option"
                            aria-selected="<?php echo $is_visible ? 'true' : 'false'; ?>">
                            <span class="fl-drag-handle fud-drag-handle" aria-hidden="true">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                            <label class="fl-widget-toggle fud-widget-toggle">
                                <input type="checkbox"
                                       name="widget_visibility[]"
                                       value="<?php echo esc_attr($widget_id); ?>"
                                       <?php checked($is_visible); ?>
                                       aria-describedby="widget-desc-<?php echo esc_attr($widget_id); ?>">
                                <span class="fl-widget-icon fud-widget-icon" style="background-color: <?php echo esc_attr($cat_color); ?>;">
                                    <span class="dashicons <?php echo esc_attr($config['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                                </span>
                                <span class="fl-widget-info">
                                    <span class="fl-widget-name fud-widget-name"><?php echo esc_html($config['title'] ?? $widget_id); ?></span>
                                    <span class="fl-widget-category" id="widget-desc-<?php echo esc_attr($widget_id); ?>">
                                        <?php echo esc_html($categories[$widget_category]['label'] ?? ucfirst($widget_category)); ?>
                                    </span>
                                </span>
                            </label>
                            <span class="fl-widget-size fud-widget-size"><?php echo esc_html($config['size'] ?? 'medium'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <footer class="fl-modal__footer fud-modal-footer">
                <button type="button" class="button fl-btn" id="fud-reset-layout">
                    <span class="dashicons dashicons-undo" aria-hidden="true"></span>
                    <?php esc_html_e('Restablecer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button button-primary fl-btn fl-btn--primary" id="fud-save-layout">
                    <span class="dashicons dashicons-saved" aria-hidden="true"></span>
                    <?php esc_html_e('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </footer>
        </div>
    </div>

    <!-- Live Region para anuncios de accesibilidad -->
    <div id="fl-live-announcer" class="fl-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
</div>

<script>
/**
 * Dashboard Unificado v4.1.0 - JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';

    const FlavorDashboard = {
        // Configuracion
        config: {
            ajaxUrl: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: typeof fudData !== 'undefined' ? fudData.nonce : '',
        },

        // Estado
        state: {
            viewMode: '<?php echo esc_js($view_mode); ?>',
            activeFilter: '<?php echo esc_js($active_filter); ?>',
            collapsedCategories: <?php echo wp_json_encode($collapsed_categories); ?>,
        },

        /**
         * Inicializa el dashboard
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initCategoryFilters();
            this.initGroupCollapse();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            const self = this;

            // Modal personalizar
            $('#fud-customize-btn, #fl-empty-customize').on('click', function() {
                $('#fud-customize-modal').fadeIn(200).attr('aria-hidden', 'false');
                $('#fud-customize-modal').find('.fud-modal-close').focus();
            });

            $('.fud-modal-close, .fud-modal-overlay, .fl-modal__close, .fl-modal__overlay').on('click', function() {
                $('#fud-customize-modal').fadeOut(200).attr('aria-hidden', 'true');
            });

            // Cerrar modal con Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#fud-customize-modal').is(':visible')) {
                    $('#fud-customize-modal').fadeOut(200).attr('aria-hidden', 'true');
                }
            });

            // Toggle vista
            $('.fl-view-btn, .fud-view-btn').on('click', function() {
                const view = $(this).data('view');
                self.setViewMode(view);
            });

            // Refresh all
            $('#fud-refresh-all-btn').on('click', function() {
                self.refreshAll($(this));
            });

            // Refresh categoria
            $('.fl-widget-group__action--refresh').on('click', function() {
                const category = $(this).data('category');
                self.refreshCategory(category, $(this));
            });

            // Guardar layout
            $('#fud-save-layout').on('click', function() {
                self.saveLayout();
            });

            // Reset layout
            $('#fud-reset-layout').on('click', function() {
                if (confirm(<?php echo wp_json_encode(__('¿Restablecer configuracion por defecto?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>)) {
                    self.resetLayout();
                }
            });

            // Dropdown categoria (mobile)
            $('#fl-category-select').on('change', function() {
                self.filterByCategory($(this).val());
            });
        },

        /**
         * Inicializa filtros de categoria
         */
        initCategoryFilters: function() {
            const self = this;

            $('.fl-category-filter').on('click', function() {
                const category = $(this).data('category');
                self.filterByCategory(category);
            });
        },

        /**
         * Filtra widgets por categoria
         */
        filterByCategory: function(category) {
            this.state.activeFilter = category;

            // Actualizar botones
            $('.fl-category-filter').removeClass('active').attr('aria-pressed', 'false');
            $('.fl-category-filter[data-category="' + category + '"]').addClass('active').attr('aria-pressed', 'true');

            // Actualizar dropdown mobile
            $('#fl-category-select').val(category);

            // Mostrar/ocultar grupos
            if (category === 'all') {
                $('.fl-widget-group').show();
            } else {
                $('.fl-widget-group').hide();
                $('.fl-widget-group[data-category="' + category + '"]').show();
            }

            // Actualizar atributo
            $('.fl-dashboard-wrapper').attr('data-active-filter', category);

            // Anunciar cambio
            this.announce(<?php echo wp_json_encode(__('Mostrando widgets de categoria: ', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> + category);

            // Guardar preferencia
            this.savePreference('activeFilter', category);
        },

        /**
         * Inicializa colapso de grupos
         */
        initGroupCollapse: function() {
            const self = this;

            $('.fl-widget-group__toggle').on('click', function() {
                const $group = $(this).closest('.fl-widget-group');
                const category = $group.data('category');
                const $content = $group.find('.fl-widget-group__content');
                const $arrow = $(this).find('.fl-widget-group__arrow');
                const isCollapsed = $group.hasClass('fl-widget-group--collapsed');

                if (isCollapsed) {
                    // Expandir
                    $group.removeClass('fl-widget-group--collapsed');
                    $content.removeAttr('hidden').slideDown(200);
                    $(this).attr('aria-expanded', 'true');
                    $arrow.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');

                    // Quitar de colapsados
                    self.state.collapsedCategories = self.state.collapsedCategories.filter(c => c !== category);
                } else {
                    // Colapsar
                    $group.addClass('fl-widget-group--collapsed');
                    $content.slideUp(200, function() {
                        $(this).attr('hidden', '');
                    });
                    $(this).attr('aria-expanded', 'false');
                    $arrow.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');

                    // Agregar a colapsados
                    if (!self.state.collapsedCategories.includes(category)) {
                        self.state.collapsedCategories.push(category);
                    }
                }

                // Guardar preferencia
                self.savePreference('collapsedCategories', self.state.collapsedCategories);
            });
        },

        /**
         * Cambia modo de vista
         */
        setViewMode: function(mode) {
            this.state.viewMode = mode;

            // Actualizar botones
            $('.fl-view-btn, .fud-view-btn').removeClass('active').attr('aria-pressed', 'false');
            $('.fl-view-btn[data-view="' + mode + '"], .fud-view-btn[data-view="' + mode + '"]').addClass('active').attr('aria-pressed', 'true');

            // Actualizar wrapper
            $('.fl-dashboard-wrapper, .fud-wrapper').attr('data-view-mode', mode);

            // Guardar preferencia
            this.savePreference('viewMode', mode);
        },

        /**
         * Inicializa sortable
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('#fud-widgets-sortable').sortable({
                    handle: '.fl-drag-handle, .fud-drag-handle',
                    placeholder: 'fl-widget-placeholder fud-widget-placeholder',
                    tolerance: 'pointer',
                    update: function() {
                        $('#fud-save-layout').addClass('button-primary fl-btn--changed');
                    }
                });
            }
        },

        /**
         * Actualiza todo
         */
        refreshAll: function($btn) {
            $btn.prop('disabled', true).find('.dashicons').addClass('fl-spinning');
            this.announce(<?php echo wp_json_encode(__('Actualizando todos los widgets...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_refresh_all',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('fl-spinning');
                }
            });
        },

        /**
         * Actualiza una categoria
         */
        refreshCategory: function(category, $btn) {
            $btn.prop('disabled', true).find('.dashicons').addClass('fl-spinning');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_refresh_category',
                    nonce: this.config.nonce,
                    category: category
                },
                success: function(response) {
                    if (response.success && response.data && response.data.html) {
                        $('#fl-group-content-' + category).html(response.data.html);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('fl-spinning');
                }
            });
        },

        /**
         * Guarda layout
         */
        saveLayout: function() {
            const order = [];
            const visibility = [];

            $('#fud-widgets-sortable .fl-widget-item, #fud-widgets-sortable .fud-widget-item').each(function() {
                const id = $(this).data('widget-id');
                order.push(id);
                if ($(this).find('input').is(':checked')) {
                    visibility.push(id);
                }
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_save_layout',
                    nonce: this.config.nonce,
                    order: order,
                    visibility: visibility
                },
                success: function(response) {
                    if (response.success) {
                        $('#fud-customize-modal').fadeOut(200);
                        location.reload();
                    }
                }
            });
        },

        /**
         * Resetea layout
         */
        resetLayout: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_reset_layout',
                    nonce: this.config.nonce
                },
                success: function() {
                    location.reload();
                }
            });
        },

        /**
         * Guarda una preferencia
         */
        savePreference: function(key, value) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_save_preference',
                    nonce: this.config.nonce,
                    key: key,
                    value: typeof value === 'object' ? JSON.stringify(value) : value
                }
            });
        },

        /**
         * Anuncia mensaje para screen readers
         */
        announce: function(message) {
            const $announcer = $('#fl-live-announcer');
            $announcer.text('');
            setTimeout(function() {
                $announcer.text(message);
            }, 100);
        }
    };

    // Inicializar
    FlavorDashboard.init();

    // Animacion de spinning
    $('<style>.fl-spinning { animation: fl-spin 1s linear infinite !important; } @keyframes fl-spin { to { transform: rotate(360deg); } }</style>').appendTo('head');
});
</script>
