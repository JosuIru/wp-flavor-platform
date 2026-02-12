<?php
/**
 * Simplificador de Menús - Solución Simple
 *
 * Elimina el menú "Gestión" gigante y menús duplicados
 * Deja solo: Flavor Systems + Flavor Platform
 *
 * @package FlavorChatIA
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

        // 2. Remover categorías vacías creadas por el organizador
        remove_menu_page('flavor-cat-personas');
        remove_menu_page('flavor-cat-economia');
        remove_menu_page('flavor-cat-operaciones');
        remove_menu_page('flavor-cat-recursos');
        remove_menu_page('flavor-cat-comunicacion');
        remove_menu_page('flavor-cat-actividades');
        remove_menu_page('flavor-cat-servicios');
        remove_menu_page('flavor-cat-comunidad');
        remove_menu_page('flavor-cat-sostenibilidad');

        // 3. Ocultar CPTs del sidebar (siguen existiendo, solo no saturan el menú)
        // Los usuarios pueden acceder desde el Unified Dashboard
        remove_menu_page('edit.php?post_type=gc_productor');
        remove_menu_page('edit.php?post_type=marketplace_item');
        remove_menu_page('edit.php?post_type=recompensa_reciclaje');
        remove_menu_page('edit.php?post_type=guia_reciclaje');
        remove_menu_page('edit.php?post_type=camps');

        // 4. Remover "Clientes Semana" (parece duplicado)
        remove_menu_page('clientes_semana');

        // 5. Log para debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Flavor Menu Simplifier] Menús simplificados: Gestión y categorías removidos, CPTs ocultados');
        }
    }
}

// Inicializar
if (is_admin()) {
    Flavor_Menu_Simplifier::get_instance();
}
