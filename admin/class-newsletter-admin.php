<?php
/**
 * Panel de administracion de Newsletter
 *
 * Paginas admin para listar, editar y ver estadisticas de campanas.
 *
 * @package FlavorChatIA
 * @subpackage Newsletter
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Newsletter_Admin {

    private static $instancia = null;

    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'encolar_assets_admin']);
    }

    public function encolar_assets_admin($sufijo_hook) {
        if (strpos($sufijo_hook, 'flavor-newsletter') === false) {
            return;
        }
        wp_enqueue_editor();
        wp_enqueue_style('flavor-newsletter-admin', FLAVOR_CHAT_IA_URL . 'admin/css/admin.css', [], FLAVOR_CHAT_IA_VERSION);
        wp_enqueue_script('flavor-newsletter-admin', FLAVOR_CHAT_IA_URL . 'admin/js/newsletter-admin.js', ['jquery'], FLAVOR_CHAT_IA_VERSION, true);
        wp_localize_script('flavor-newsletter-admin', 'flavorNewsletter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_newsletter_nonce'),
            'i18n'    => [
                'confirmar_envio'    => __('Seguro que deseas enviar esta campana?', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('Seguro que deseas eliminar esta campana?', 'flavor-chat-ia'),
                'guardando'          => __('Guardando...', 'flavor-chat-ia'),
                'guardado'           => __('Campana guardada', 'flavor-chat-ia'),
                'enviando'           => __('Iniciando envio...', 'flavor-chat-ia'),
                'error_general'      => __('Ha ocurrido un error.', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Renderiza la pagina principal de newsletter (listado de campanas)
     */
    public function renderizar_pagina_newsletter() {
        $gestor_newsletter = Flavor_Newsletter_Manager::get_instance();
        $estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
        $pagina_actual = max(1, intval($_GET['pag'] ?? 1));
        $por_pagina = 20;
        $offset_resultados = ($pagina_actual - 1) * $por_pagina;
        $lista_campanas = $gestor_newsletter->listar_campanas($estado_filtro, $por_pagina, $offset_resultados);

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Newsletter - Campanas', 'flavor-chat-ia') . '</h1>';
        echo ' <a href="' . esc_url(admin_url('admin.php?page=flavor-newsletter-editor')) . '" class="page-title-action">' . esc_html__('Crear campana', 'flavor-chat-ia') . '</a>';
        echo '<hr class="wp-header-end">';

        if (empty($lista_campanas)) {
            echo '<div class="notice notice-info"><p>' . esc_html__('No hay campanas todavia. Crea la primera.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Asunto', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Enviados', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Abiertos', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Clicks', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($lista_campanas as $campana_item) {
            $url_editar = admin_url('admin.php?page=flavor-newsletter-editor&id=' . intval($campana_item->id));
            $url_stats = admin_url('admin.php?page=flavor-newsletter&view=stats&id=' . intval($campana_item->id));
            echo '<tr>';
            echo '<td>' . intval($campana_item->id) . '</td>';
            echo '<td><a href="' . esc_url($url_editar) . '">' . esc_html($campana_item->asunto ?: __('(Sin asunto)', 'flavor-chat-ia')) . '</a></td>';
            echo '<td><span class="flavor-badge flavor-badge--' . esc_attr($campana_item->estado) . '">' . esc_html($campana_item->estado) . '</span></td>';
            echo '<td>' . intval($campana_item->total_enviados) . '/' . intval($campana_item->total_destinatarios) . '</td>';
            echo '<td>' . intval($campana_item->total_abiertos) . '</td>';
            echo '<td>' . intval($campana_item->total_clicks) . '</td>';
            echo '<td>' . esc_html($campana_item->created_at) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url($url_editar) . '" class="button button-small">' . esc_html__('Editar', 'flavor-chat-ia') . '</a> ';
            echo '<a href="' . esc_url($url_stats) . '" class="button button-small">' . esc_html__('Estadisticas', 'flavor-chat-ia') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    /**
     * Renderiza la pagina del editor de campana
     */
    public function renderizar_pagina_editor() {
        $identificador_campana = intval($_GET['id'] ?? 0);
        $datos_campana = null;
        if ($identificador_campana > 0) {
            $gestor_newsletter = Flavor_Newsletter_Manager::get_instance();
            $datos_campana = $gestor_newsletter->obtener_campana($identificador_campana);
        }
        include FLAVOR_CHAT_IA_PATH . 'admin/views/newsletter-editor.php';
    }

    /**
     * Renderiza la vista de estadisticas de una campana
     */
    public function renderizar_vista_estadisticas() {
        $identificador_campana = intval($_GET['id'] ?? 0);
        if ($identificador_campana <= 0) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('ID de campana no valido.', 'flavor-chat-ia') . '</p></div></div>';
            return;
        }
        $gestor_newsletter = Flavor_Newsletter_Manager::get_instance();
        $estadisticas = $gestor_newsletter->obtener_estadisticas_campana($identificador_campana);
        if (!$estadisticas) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Campana no encontrada.', 'flavor-chat-ia') . '</p></div></div>';
            return;
        }
        $campana_datos = $estadisticas['campana'];

        echo '<div class="wrap">';
        echo '<h1>' . sprintf(esc_html__('Estadisticas: %s', 'flavor-chat-ia'), esc_html($campana_datos->asunto)) . '</h1>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=flavor-newsletter')) . '" class="button">&laquo; ' . esc_html__('Volver al listado', 'flavor-chat-ia') . '</a>';

        echo '<div class="flavor-stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:20px 0;">';
        $this->renderizar_tarjeta_stat(__('Enviados', 'flavor-chat-ia'), intval($campana_datos->total_enviados) . '/' . intval($campana_datos->total_destinatarios));
        $this->renderizar_tarjeta_stat(__('Aperturas unicas', 'flavor-chat-ia'), $estadisticas['aperturas_unicas'] . ' (' . $estadisticas['tasa_apertura'] . '%)');
        $this->renderizar_tarjeta_stat(__('Clicks unicos', 'flavor-chat-ia'), $estadisticas['clicks_unicos'] . ' (' . $estadisticas['tasa_clicks'] . '%)');
        $this->renderizar_tarjeta_stat(__('Estado', 'flavor-chat-ia'), esc_html($campana_datos->estado));
        echo '</div>';
        echo '</div>';
    }

    private function renderizar_tarjeta_stat($titulo_tarjeta, $valor_tarjeta) {
        echo '<div style="background:#fff;border:1px solid #ccd0d4;border-radius:6px;padding:16px;text-align:center;">';
        echo '<div style="font-size:24px;font-weight:bold;color:#1d2327;">' . esc_html($valor_tarjeta) . '</div>';
        echo '<div style="font-size:13px;color:#646970;margin-top:4px;">' . esc_html($titulo_tarjeta) . '</div>';
        echo '</div>';
    }

    /**
     * Callback del menu principal: decide vista segun parametro GET
     */
    public function callback_pagina_principal() {
        $vista_actual = sanitize_text_field($_GET['view'] ?? 'list');
        switch ($vista_actual) {
            case 'stats':
                $this->renderizar_vista_estadisticas();
                break;
            default:
                $this->renderizar_pagina_newsletter();
                break;
        }
    }
}
