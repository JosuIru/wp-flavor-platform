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
     * Configuración de CPTs con sus labels personalizados
     */
    private static $cpt_labels = [
        'gc_grupo' => 'Grupos de Consumo',
        'gc_producto' => 'Productos',
        'gc_productor' => 'Productores',
        'evento' => 'Eventos',
        'curso' => 'Cursos',
        'taller' => 'Talleres',
        'banco_tiempo' => 'Banco de Tiempo',
        'marketplace_item' => 'Marketplace',
        'carpooling_viaje' => 'Carpooling',
        'parking_plaza' => 'Parkings',
        'biblioteca_libro' => 'Biblioteca',
        'espacio_comun' => 'Espacios Comunes',
        'huerto_urbano' => 'Huertos Urbanos',
    ];

    /**
     * URLs de archivo personalizadas para módulos
     */
    private static $archive_urls = [
        'banco_tiempo' => '/banco-tiempo/',
        'carpooling_viaje' => '/carpooling/',
        'parking_plaza' => '/parkings/',
        'biblioteca_libro' => '/biblioteca/',
    ];

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
            'archive_label' => '', // Para override manual del label del archivo
            'archive_url' => '',   // Para override manual de la URL del archivo
            'items' => [],         // Items intermedios adicionales
        ];

        $args = wp_parse_args($args, $defaults);
        $args = apply_filters('flavor_breadcrumbs_args', $args);

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

            // Determinar label del archivo
            $archive_label = $args['archive_label'];
            if (empty($archive_label)) {
                $archive_label = self::get_cpt_label($post_type);
                if (empty($archive_label) && $post_type_obj) {
                    $archive_label = $post_type_obj->labels->name;
                }
            }

            // Determinar URL del archivo
            $archive_url = $args['archive_url'];
            if (empty($archive_url)) {
                $archive_url = self::get_archive_url($post_type);
            }

            if ($archive_label && $archive_url) {
                $crumbs[] = [
                    'url' => $archive_url,
                    'title' => $archive_label,
                ];
            }

            // Añadir items intermedios
            foreach ($args['items'] as $item) {
                $crumbs[] = $item;
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
     * Renderiza breadcrumbs para páginas de módulo (no CPTs)
     *
     * @param string $module_label Etiqueta del módulo
     * @param string $module_url URL del módulo
     * @param string $current_title Título actual
     * @param array $extra_items Items adicionales intermedios
     */
    public static function render_module($module_label, $module_url, $current_title, $extra_items = []) {
        $args = [
            'home_text' => __('Mi Portal', 'flavor-chat-ia'),
            'home_url' => home_url('/mi-portal/'),
            'separator' => '›',
            'class' => 'flavor-breadcrumbs',
        ];

        $crumbs = [
            ['url' => $args['home_url'], 'title' => $args['home_text']],
            ['url' => $module_url, 'title' => $module_label],
        ];

        foreach ($extra_items as $item) {
            $crumbs[] = $item;
        }

        $crumbs[] = ['url' => '', 'title' => $current_title];

        echo self::render_html($crumbs, $args);
    }

    /**
     * Renderiza un botón de volver
     *
     * @param string $url URL de destino
     * @param string $label Etiqueta (default: 'Volver al listado')
     */
    public static function render_back_button($url = '', $label = '') {
        if (empty($url)) {
            $post_type = get_post_type();
            $url = self::get_archive_url($post_type);
        }

        if (empty($label)) {
            $label = __('Volver al listado', 'flavor-chat-ia');
        }
        ?>
        <div class="flavor-back-button mb-6">
            <a href="<?php echo esc_url($url); ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                <?php echo esc_html($label); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza breadcrumbs + botón de volver
     *
     * @param array $args Argumentos de breadcrumbs
     * @param string $back_url URL del botón volver
     * @param string $back_label Label del botón volver
     */
    public static function render_with_back($args = [], $back_url = '', $back_label = '') {
        echo self::render($args);
        self::render_back_button($back_url, $back_label);
    }

    /**
     * Obtiene el label de un CPT
     */
    private static function get_cpt_label($post_type) {
        $labels = apply_filters('flavor_breadcrumbs_cpt_labels', self::$cpt_labels);
        return isset($labels[$post_type]) ? __($labels[$post_type], 'flavor-chat-ia') : '';
    }

    /**
     * Obtiene la URL de archivo de un CPT
     */
    private static function get_archive_url($post_type) {
        $urls = apply_filters('flavor_breadcrumbs_archive_urls', self::$archive_urls);

        if (isset($urls[$post_type])) {
            return home_url($urls[$post_type]);
        }

        return get_post_type_archive_link($post_type) ?: '';
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
