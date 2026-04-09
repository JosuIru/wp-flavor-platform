<?php
/**
 * Módulo de Publicidad Ética
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Advertising_Module extends Flavor_Chat_Module_Base {

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
