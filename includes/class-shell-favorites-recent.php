<?php
/**
 * Flavor Shell - Favoritos y Recientes
 *
 * Sistema para gestionar páginas favoritas y visitadas recientemente
 * por cada usuario en el Admin Shell.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar Favoritos y Recientes del Shell
 */
class Flavor_Shell_Favorites_Recent {

    /**
     * Instancia singleton
     *
     * @var Flavor_Shell_Favorites_Recent|null
     */
    private static $instance = null;

    /**
     * Meta key para favoritos
     */
    const META_FAVORITES = 'flavor_shell_favorites';

    /**
     * Meta key para páginas recientes
     */
    const META_RECENT = 'flavor_shell_recent_pages';

    /**
     * Máximo de páginas recientes a guardar
     */
    const MAX_RECENT = 10;

    /**
     * Máximo de favoritos permitidos
     */
    const MAX_FAVORITES = 15;

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Shell_Favorites_Recent
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // AJAX endpoints
        add_action('wp_ajax_flavor_shell_toggle_favorite', [$this, 'ajax_toggle_favorite']);
        add_action('wp_ajax_flavor_shell_track_visit', [$this, 'ajax_track_visit']);
        add_action('wp_ajax_flavor_shell_get_favorites_recent', [$this, 'ajax_get_data']);
        add_action('wp_ajax_flavor_shell_clear_recent', [$this, 'ajax_clear_recent']);
        add_action('wp_ajax_flavor_shell_reorder_favorites', [$this, 'ajax_reorder_favorites']);

        // Trackear visitas automáticamente en páginas Flavor
        add_action('admin_init', [$this, 'auto_track_page_visit'], 20);
    }

    /**
     * Obtener favoritos del usuario actual
     *
     * @param int|null $user_id ID del usuario (por defecto, el actual)
     * @return array Lista de slugs favoritos con metadata
     */
    public function get_favorites($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return [];
        }

        $favorites = get_user_meta($user_id, self::META_FAVORITES, true);

        return is_array($favorites) ? $favorites : [];
    }

    /**
     * Obtener páginas recientes del usuario
     *
     * @param int|null $user_id ID del usuario
     * @param int      $limit   Límite de páginas a retornar
     * @return array Lista de páginas recientes con timestamp
     */
    public function get_recent($user_id = null, $limit = 5) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return [];
        }

        $recent = get_user_meta($user_id, self::META_RECENT, true);

        if (!is_array($recent)) {
            return [];
        }

        // Ordenar por timestamp descendente
        uasort($recent, function($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });

        return array_slice($recent, 0, $limit, true);
    }

    /**
     * Verificar si una página es favorita
     *
     * @param string   $slug    Slug de la página
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function is_favorite($slug, $user_id = null) {
        $favorites = $this->get_favorites($user_id);
        return isset($favorites[$slug]);
    }

    /**
     * Añadir página a favoritos
     *
     * @param string   $slug    Slug de la página
     * @param array    $data    Datos adicionales (label, icon)
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function add_favorite($slug, array $data = [], $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id || !$slug) {
            return false;
        }

        $favorites = $this->get_favorites($user_id);

        // Verificar límite
        if (count($favorites) >= self::MAX_FAVORITES && !isset($favorites[$slug])) {
            return false;
        }

        $favorites[$slug] = [
            'slug' => $slug,
            'label' => $data['label'] ?? $slug,
            'icon' => $data['icon'] ?? 'dashicons-star-filled',
            'added' => time(),
            'order' => count($favorites),
        ];

        return update_user_meta($user_id, self::META_FAVORITES, $favorites);
    }

    /**
     * Quitar página de favoritos
     *
     * @param string   $slug    Slug de la página
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function remove_favorite($slug, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id || !$slug) {
            return false;
        }

        $favorites = $this->get_favorites($user_id);

        if (!isset($favorites[$slug])) {
            return true;
        }

        unset($favorites[$slug]);

        // Reordenar
        $order = 0;
        foreach ($favorites as &$fav) {
            $fav['order'] = $order++;
        }

        return update_user_meta($user_id, self::META_FAVORITES, $favorites);
    }

    /**
     * Toggle favorito (añadir si no existe, quitar si existe)
     *
     * @param string   $slug    Slug de la página
     * @param array    $data    Datos adicionales
     * @param int|null $user_id ID del usuario
     * @return array ['action' => 'added'|'removed', 'is_favorite' => bool]
     */
    public function toggle_favorite($slug, array $data = [], $user_id = null) {
        if ($this->is_favorite($slug, $user_id)) {
            $this->remove_favorite($slug, $user_id);
            return ['action' => 'removed', 'is_favorite' => false];
        } else {
            $this->add_favorite($slug, $data, $user_id);
            return ['action' => 'added', 'is_favorite' => true];
        }
    }

    /**
     * Registrar visita a una página
     *
     * @param string   $slug    Slug de la página
     * @param array    $data    Datos adicionales
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function track_visit($slug, array $data = [], $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id || !$slug) {
            return false;
        }

        $recent = get_user_meta($user_id, self::META_RECENT, true);

        if (!is_array($recent)) {
            $recent = [];
        }

        // Actualizar o añadir página
        $recent[$slug] = [
            'slug' => $slug,
            'label' => $data['label'] ?? $slug,
            'icon' => $data['icon'] ?? 'dashicons-admin-page',
            'timestamp' => time(),
            'visits' => ($recent[$slug]['visits'] ?? 0) + 1,
        ];

        // Limitar cantidad
        if (count($recent) > self::MAX_RECENT) {
            uasort($recent, function($a, $b) {
                return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
            });
            $recent = array_slice($recent, 0, self::MAX_RECENT, true);
        }

        return update_user_meta($user_id, self::META_RECENT, $recent);
    }

    /**
     * Limpiar páginas recientes
     *
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function clear_recent($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return false;
        }

        return delete_user_meta($user_id, self::META_RECENT);
    }

    /**
     * Reordenar favoritos
     *
     * @param array    $order   Array de slugs en nuevo orden
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function reorder_favorites(array $order, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return false;
        }

        $favorites = $this->get_favorites($user_id);
        $reordered_favorites = [];

        $position = 0;
        foreach ($order as $slug) {
            if (isset($favorites[$slug])) {
                $favorites[$slug]['order'] = $position++;
                $reordered_favorites[$slug] = $favorites[$slug];
            }
        }

        // Añadir cualquiera que no estuviera en el orden
        foreach ($favorites as $slug => $fav) {
            if (!isset($reordered_favorites[$slug])) {
                $fav['order'] = $position++;
                $reordered_favorites[$slug] = $fav;
            }
        }

        return update_user_meta($user_id, self::META_FAVORITES, $reordered_favorites);
    }

    /**
     * Trackear visita automáticamente en páginas Flavor
     */
    public function auto_track_page_visit() {
        if (!is_admin() || !current_user_can('read')) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if (empty($page)) {
            return;
        }

        // Solo trackear páginas Flavor
        $flavor_prefixes = ['flavor-', 'gc-', 'pp-', 'bt-', 'hu-', 'part-', 'socios', 'comunidades',
            'colectivos', 'foros', 'eventos', 'cursos', 'talleres', 'reservas', 'tramites',
            'incidencias', 'marketplace', 'ayuda-', 'banco-tiempo', 'huertos', 'espacios',
            'biblioteca', 'carpooling', 'bicicletas', 'reciclaje', 'compostaje', 'multimedia',
            'podcast', 'radio', 'campanias', 'encuestas', 'economia-don'];

        $is_flavor_page = false;
        foreach ($flavor_prefixes as $prefix) {
            if (strpos($page, $prefix) === 0) {
                $is_flavor_page = true;
                break;
            }
        }

        if (!$is_flavor_page) {
            return;
        }

        // Obtener label de la página del título
        global $title;
        $page_label = $title ?: $page;

        $this->track_visit($page, ['label' => $page_label]);
    }

    /**
     * Obtener datos combinados para el shell
     *
     * @return array
     */
    public function get_shell_data() {
        $favorites = $this->get_favorites();
        $recent = $this->get_recent(null, 5);

        // Ordenar favoritos por orden
        uasort($favorites, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        // Excluir de recientes los que ya son favoritos
        $recent_filtered = array_filter($recent, function($item) use ($favorites) {
            return !isset($favorites[$item['slug']]);
        });

        return [
            'favorites' => array_values($favorites),
            'recent' => array_values($recent_filtered),
            'max_favorites' => self::MAX_FAVORITES,
            'favorites_count' => count($favorites),
        ];
    }

    /**
     * AJAX: Toggle favorito
     */
    public function ajax_toggle_favorite() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        $label = isset($_POST['label']) ? sanitize_text_field($_POST['label']) : $slug;
        $icon = isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : 'dashicons-star-filled';

        if (empty($slug)) {
            wp_send_json_error(['message' => 'Slug requerido']);
        }

        $result = $this->toggle_favorite($slug, [
            'label' => $label,
            'icon' => $icon,
        ]);

        wp_send_json_success([
            'action' => $result['action'],
            'is_favorite' => $result['is_favorite'],
            'message' => $result['action'] === 'added'
                ? __('Añadido a favoritos', 'flavor-chat-ia')
                : __('Eliminado de favoritos', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Trackear visita
     */
    public function ajax_track_visit() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        $label = isset($_POST['label']) ? sanitize_text_field($_POST['label']) : $slug;
        $icon = isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : '';

        if (empty($slug)) {
            wp_send_json_error(['message' => 'Slug requerido']);
        }

        $this->track_visit($slug, [
            'label' => $label,
            'icon' => $icon,
        ]);

        wp_send_json_success(['tracked' => true]);
    }

    /**
     * AJAX: Obtener datos de favoritos y recientes
     */
    public function ajax_get_data() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        wp_send_json_success($this->get_shell_data());
    }

    /**
     * AJAX: Limpiar recientes
     */
    public function ajax_clear_recent() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $this->clear_recent();

        wp_send_json_success([
            'message' => __('Historial limpiado', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Reordenar favoritos
     */
    public function ajax_reorder_favorites() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $order = isset($_POST['order']) ? array_map('sanitize_text_field', $_POST['order']) : [];

        if (empty($order)) {
            wp_send_json_error(['message' => 'Orden requerido']);
        }

        $this->reorder_favorites($order);

        wp_send_json_success([
            'message' => __('Orden guardado', 'flavor-chat-ia'),
        ]);
    }
}

// Inicializar singleton
Flavor_Shell_Favorites_Recent::get_instance();
