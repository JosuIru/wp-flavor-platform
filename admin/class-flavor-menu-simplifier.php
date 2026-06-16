<?php
/**
 * Simplificador de Menús - Solución Simple
 *
 * Elimina el menú "Gestión" gigante y menús duplicados
 * Deja solo: Flavor Systems + Flavor Platform
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Menu_Simplifier {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Ejecutar con prioridad muy alta para limpiar después de todo
        add_action('admin_menu', [$this, 'simplify_menus'], 9999);
    }

    public function simplify_menus() {
        // 1. Remover el menú "Gestión" gigante completamente
        remove_menu_page('flavor-gestion');

        // (Se eliminaron 9 remove_menu_page('flavor-cat-*'): eran no-op desde
        // que la clase Flavor_Menu_Organizer que creaba esas categorías quedó
        // como código muerto y fue eliminada.)

        // 2. Ocultar CPTs del sidebar (siguen existiendo, solo no saturan el menú)
        // Los usuarios pueden acceder desde el Unified Dashboard
        remove_menu_page('edit.php?post_type=gc_productor');
        remove_menu_page('edit.php?post_type=marketplace_item');
        remove_menu_page('edit.php?post_type=recompensa_reciclaje');
        remove_menu_page('edit.php?post_type=guia_reciclaje');
        remove_menu_page('edit.php?post_type=camps');

        // 3. Remover "Clientes Semana" (parece duplicado)
        remove_menu_page('clientes_semana');

        // 4. Log para debugging
        flavor_log_debug( 'Menús simplificados: Gestión removido, CPTs ocultados', 'MenuSimplifier' );
    }
}

// Inicializar
if (is_admin()) {
    Flavor_Menu_Simplifier::get_instance();
}
