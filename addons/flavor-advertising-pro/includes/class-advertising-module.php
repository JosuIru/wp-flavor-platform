<?php
/**
 * Módulo de Publicidad Ética
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Advertising_Module extends Flavor_Platform_Module_Base {

    public function __construct() {
        $this->id = 'advertising';
        $this->name = __('Publicidad Ética', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Sistema de anuncios éticos con reparto de beneficios.', FLAVOR_PLATFORM_TEXT_DOMAIN);

        parent::__construct();
    }

    public function can_activate() {
        return true;
    }

    public function get_activation_error() {
        return '';
    }

    public function init() {
        // El sistema principal ya se inicializa
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'ver_estadisticas' => [
                'description' => 'Ver estadísticas de publicidad',
                'params' => ['periodo'],
            ],
            'listar_anuncios' => [
                'description' => 'Listar anuncios activos',
                'params' => ['estado'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: estadísticas de publicidad agregadas por periodo.
     *
     * Consulta la tabla real flavor_ad_stats (impressions/clicks/revenue/date).
     *
     * @param array $params ['periodo' => today|week|month]
     * @return array
     */
    private function action_ver_estadisticas($params) {
        global $wpdb;

        $periodo = isset($params['periodo']) ? sanitize_key($params['periodo']) : 'month';
        $offsets = ['today' => 0, 'week' => 6, 'month' => 29];
        $offset_dias = isset($offsets[$periodo]) ? $offsets[$periodo] : 29;
        $desde = gmdate('Y-m-d', current_time('timestamp') - ($offset_dias * DAY_IN_SECONDS));

        $tabla_stats = $wpdb->prefix . 'flavor_ad_stats';
        $anuncios_activos = (int) ( wp_count_posts('flavor_ad')->publish ?? 0 );

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_stats)) {
            return [
                'success' => true,
                'data' => [
                    'periodo' => $periodo, 'desde' => $desde,
                    'impresiones' => 0, 'clicks' => 0, 'ctr' => 0, 'ingresos' => 0,
                    'anuncios_activos' => $anuncios_activos,
                ],
            ];
        }

        $fila = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(impressions),0) AS imp, COALESCE(SUM(clicks),0) AS clk, COALESCE(SUM(revenue),0) AS rev
             FROM {$tabla_stats} WHERE date >= %s",
            $desde
        ));

        $impresiones = (int) ($fila->imp ?? 0);
        $clicks = (int) ($fila->clk ?? 0);

        return [
            'success' => true,
            'data' => [
                'periodo' => $periodo,
                'desde' => $desde,
                'impresiones' => $impresiones,
                'clicks' => $clicks,
                'ctr' => $impresiones > 0 ? round($clicks / $impresiones * 100, 2) : 0,
                'ingresos' => round((float) ($fila->rev ?? 0), 2),
                'anuncios_activos' => $anuncios_activos,
            ],
        ];
    }

    /**
     * Acción: lista los anuncios (CPT flavor_ad) filtrando por estado.
     *
     * @param array $params ['estado' => publish|draft|pending|any]
     * @return array
     */
    private function action_listar_anuncios($params) {
        $estado = isset($params['estado']) && $params['estado'] !== '' ? sanitize_key($params['estado']) : 'publish';
        if (!in_array($estado, ['publish', 'draft', 'pending', 'future', 'any'], true)) {
            $estado = 'publish';
        }

        $posts = get_posts([
            'post_type'   => 'flavor_ad',
            'post_status' => $estado,
            'numberposts' => 50,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        $anuncios = [];
        foreach ($posts as $post) {
            $anuncios[] = [
                'id'     => (int) $post->ID,
                'titulo' => $post->post_title,
                'estado' => $post->post_status,
                'fecha'  => $post->post_date,
                'tipo'   => (string) get_post_meta($post->ID, '_flavor_ad_type', true),
            ];
        }

        return [
            'success' => true,
            'data' => [
                'estado'   => $estado,
                'total'    => count($anuncios),
                'anuncios' => $anuncios,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'advertising_stats',
                'description' => 'Obtener estadísticas de publicidad',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => ['type' => 'string', 'description' => 'Periodo: today, week, month'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Publicidad Ética**

Sistema de anuncios responsables integrados en la plataforma.

**Tipos de anuncios:**
- Banner horizontal
- Banner sidebar
- Anuncio tipo tarjeta
- Anuncio nativo

**Características:**
- Publicidad no intrusiva
- Etiquetado transparente
- Reparto de beneficios con la comunidad
KNOWLEDGE;
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'banner_horizontal' => [
                'label' => __('Banner Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Banner publicitario horizontal (728x90 o similar)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'position' => [
                        'type' => 'select',
                        'label' => __('Posición', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['header', 'content_top', 'content_bottom', 'footer'],
                        'default' => 'content_top',
                    ],
                    'mostrar_etiqueta' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar etiqueta "Anuncio"', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'advertising/banner-horizontal',
            ],
            'banner_sidebar' => [
                'label' => __('Banner Sidebar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Banner vertical para barra lateral (300x250)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'mostrar_etiqueta' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar etiqueta "Anuncio"', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'sticky' => [
                        'type' => 'toggle',
                        'label' => __('Fijo al hacer scroll', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => false,
                    ],
                ],
                'template' => 'advertising/banner-sidebar',
            ],
            'banner_card' => [
                'label' => __('Anuncio Tipo Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Anuncio integrado como tarjeta de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cards',
                'icon' => 'dashicons-format-aside',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['minimal', 'card', 'featured'],
                        'default' => 'card',
                    ],
                ],
                'template' => 'advertising/banner-card',
            ],
            'banner_nativo' => [
                'label' => __('Anuncio Nativo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Anuncio que se integra con el diseño del contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-admin-page',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'titulo_personalizado' => [
                        'type' => 'text',
                        'label' => __('Título personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                ],
                'template' => 'advertising/banner-nativo',
            ],
        ];
    }

    /**
     * Obtener anuncios disponibles
     */
    private function get_available_ads() {
        $ads = get_posts([
            'post_type' => 'flavor_ad',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $options = ['' => __('Seleccionar anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        foreach ($ads as $ad) {
            $options[$ad->ID] = $ad->post_title;
        }

        return $options;
    }

}

// Legacy alias for backward compatibility
if (!class_exists('Flavor_Chat_Advertising_Module', false)) {
    class_alias('Flavor_Platform_Advertising_Module', 'Flavor_Chat_Advertising_Module');
}
