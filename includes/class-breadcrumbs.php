<?php
/**
 * Sistema de Breadcrumbs para Flavor
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Breadcrumbs {

    /**
     * Renderiza breadcrumbs para la página actual
     */
    public static function render($args = []) {
        $defaults = [
            'home_text' => __('Mi Portal', 'flavor-chat-ia'),
            'home_url' => home_url('/mi-portal/'),
            'separator' => '›',
            'show_current' => true,
            'class' => 'flavor-breadcrumbs',
        ];

        $args = wp_parse_args($args, $defaults);

        // No mostrar en home o mi-portal
        if (is_front_page() || (is_page() && get_post_field('post_name') === 'mi-portal')) {
            return '';
        }

        $crumbs = [];

        // Siempre empezar con Mi Portal (salvo que sea la home pública)
        if (!is_front_page()) {
            $crumbs[] = [
                'url' => $args['home_url'],
                'title' => $args['home_text'],
            ];
        }

        // Construir breadcrumbs según tipo de página
        if (is_page()) {
            $page_id = get_the_ID();
            $ancestors = get_post_ancestors($page_id);

            // Añadir ancestros en orden
            if (!empty($ancestors)) {
                foreach (array_reverse($ancestors) as $ancestor) {
                    $crumbs[] = [
                        'url' => get_permalink($ancestor),
                        'title' => get_the_title($ancestor),
                    ];
                }
            }

            // Página actual
            if ($args['show_current']) {
                $crumbs[] = [
                    'url' => '',
                    'title' => get_the_title($page_id),
                ];
            }
        } elseif (is_single()) {
            $post_type = get_post_type();
            $post_type_obj = get_post_type_object($post_type);

            if ($post_type_obj) {
                $crumbs[] = [
                    'url' => get_post_type_archive_link($post_type),
                    'title' => $post_type_obj->labels->name,
                ];
            }

            if ($args['show_current']) {
                $crumbs[] = [
                    'url' => '',
                    'title' => get_the_title(),
                ];
            }
        }

        if (empty($crumbs)) {
            return '';
        }

        return self::render_html($crumbs, $args);
    }

    /**
     * Renderiza el HTML de los breadcrumbs
     */
    private static function render_html($crumbs, $args) {
        ob_start();
        ?>
        <nav class="<?php echo esc_attr($args['class']); ?>" aria-label="<?php esc_attr_e('Breadcrumbs', 'flavor-chat-ia'); ?>">
            <ol class="flavor-breadcrumbs__list">
                <?php foreach ($crumbs as $index => $crumb) : ?>
                    <li class="flavor-breadcrumbs__item">
                        <?php if (!empty($crumb['url'])) : ?>
                            <a href="<?php echo esc_url($crumb['url']); ?>" class="flavor-breadcrumbs__link">
                                <?php echo esc_html($crumb['title']); ?>
                            </a>
                        <?php else : ?>
                            <span class="flavor-breadcrumbs__current"><?php echo esc_html($crumb['title']); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($index < count($crumbs) - 1) : ?>
                            <span class="flavor-breadcrumbs__separator"><?php echo esc_html($args['separator']); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <style>
        .flavor-breadcrumbs {
            margin-bottom: 24px;
            padding: 12px 0;
        }
        .flavor-breadcrumbs__list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .flavor-breadcrumbs__item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .flavor-breadcrumbs__link {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.2s;
        }
        .flavor-breadcrumbs__link:hover {
            color: var(--flavor-primary, #3b82f6);
        }
        .flavor-breadcrumbs__current {
            color: #111827;
            font-weight: 500;
        }
        .flavor-breadcrumbs__separator {
            color: #d1d5db;
            user-select: none;
        }
        @media (max-width: 640px) {
            .flavor-breadcrumbs {
                font-size: 13px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
