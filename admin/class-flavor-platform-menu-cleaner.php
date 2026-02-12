<?php
/**
 * Limpiador de Menú Flavor Platform
 *
 * Remueve módulos de negocio del menú Flavor Platform
 * y los deja en sus categorías correctas
 *
 * @package FlavorChatIA
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Menu_Cleaner {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Items que deben removerse de Flavor Platform
     * porque son módulos de negocio, no configuración de plataforma
     */
    private $items_to_remove = [
        'flavor-multimedia',   // → Recursos
        'flavor-radio',        // → Comunicación
        'flavor-reciclaje',    // → Sostenibilidad
    ];

    /**
     * Obtener instancia
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
        // Ejecutar después del menu organizer (prioridad 1000)
        add_action('admin_menu', [$this, 'clean_platform_menu'], 1000);
    }

    /**
     * Limpiar menú Flavor Platform
     */
    public function clean_platform_menu() {
        global $submenu;

        // Verificar que existe el menú Flavor Platform
        if (!isset($submenu['flavor-chat-ia'])) {
            return;
        }

        $removed_count = 0;
        $removed_items = [];

        // Remover items que son módulos de negocio
        foreach ($submenu['flavor-chat-ia'] as $key => $item) {
            // $item[0] = título, $item[1] = capacidad, $item[2] = slug
            $slug = $item[2];

            if (in_array($slug, $this->items_to_remove)) {
                unset($submenu['flavor-chat-ia'][$key]);
                $removed_count++;
                $removed_items[] = $slug;
            }
        }

        // Reindexar el array después de remover items
        if ($removed_count > 0) {
            $submenu['flavor-chat-ia'] = array_values($submenu['flavor-chat-ia']);
        }

        // Log para debugging
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($removed_items)) {
            error_log('[Flavor Platform Cleaner] Items removidos de Flavor Platform: ' . implode(', ', $removed_items));
            error_log('[Flavor Platform Cleaner] Estos items ahora están en sus categorías correctas');
        }
    }

    /**
     * Obtener lista de items removidos (para debugging)
     */
    public function get_removed_items() {
        return $this->items_to_remove;
    }

    /**
     * Verificar si un item fue removido
     */
    public function is_item_removed($slug) {
        return in_array($slug, $this->items_to_remove);
    }
}

// Inicializar solo en admin
if (is_admin()) {
    Flavor_Platform_Menu_Cleaner::get_instance();
}
