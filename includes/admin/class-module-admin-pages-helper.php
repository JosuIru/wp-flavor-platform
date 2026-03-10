<?php
/**
 * Clase helper para exponer métodos estáticos del trait de páginas admin
 *
 * En PHP 8.2+ no se puede llamar directamente a métodos/propiedades estáticos
 * de un trait. Esta clase usa el trait y expone sus métodos.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Asegurar que el trait está cargado
require_once __DIR__ . '/trait-module-admin-pages.php';

/**
 * Clase helper que expone los métodos estáticos del trait
 */
class Flavor_Module_Admin_Pages_Helper {
    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
