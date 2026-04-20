<?php
/**
 * Addon Name: Flavor Visual Builder Pro
 * Description: Editor visual drag-and-drop con bloques dinámicos que consumen datos de los módulos del ecosistema (eventos, socios, biblioteca, etc.). Incluye filtros editables por el visitante, "Cargar más" con firma HMAC, caché CDN-friendly y templates custom con placeholders.
 * Version: 2.2.4
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * Requires: Flavor Platform 3.2.0+
 *
 * @package     FlavorVisualBuilderPro
 * @copyright   2026 Gailu Labs
 * @license     GPL-2.0+
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ESTADO DEL ADDON — Camino A paso 2 (archivos movidos):
 *
 * El código real de VBP ya vive aquí (addons/flavor-visual-builder-pro/).
 * El bootstrap (class-bootstrap-dependencies.php) hace require_once del
 * loader desde esta ruta; las constantes FLAVOR_VBP_ADDON_PATH/URL
 * apuntan a plugin_dir_path(__FILE__) como cualquier addon normal.
 *
 * Este archivo registra el addon con Flavor_Addon_Manager para que
 * aparezca listado junto a Multilingual, Network Communities, etc.
 *
 * Assets (CSS/JS) siguen en FLAVOR_PLATFORM_PATH . 'assets/vbp/'
 * porque son assets compartidos cargados por class-vbp-editor.php;
 * no se mueven para evitar duplicar paths en 90+ enqueues.
 *
 * Camino A paso 3 (plugin standalone): fuera de alcance. Requiere
 * resolver el acoplamiento real con tablas de módulos y extracción
 * de utilidades compartidas.
 */

// Constantes del addon.
if ( ! defined( 'FLAVOR_VBP_ADDON_VERSION' ) ) {
    define( 'FLAVOR_VBP_ADDON_VERSION', '2.2.4' );
}

if ( ! defined( 'FLAVOR_VBP_ADDON_PATH' ) ) {
    define( 'FLAVOR_VBP_ADDON_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'FLAVOR_VBP_ADDON_URL' ) ) {
    define( 'FLAVOR_VBP_ADDON_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'FLAVOR_VBP_ADDON_FILE' ) ) {
    define( 'FLAVOR_VBP_ADDON_FILE', __FILE__ );
}

/**
 * Registra Visual Builder Pro en el sistema de addons al disparar el
 * hook flavor_register_addons. El init_callback no necesita cargar el
 * loader (ya está instanciado por bootstrap), solo expone metadata.
 */
add_action( 'flavor_register_addons', function () {
    if ( ! class_exists( 'Flavor_Addon_Manager' ) ) {
        return;
    }

    Flavor_Addon_Manager::register_addon( 'visual-builder-pro', array(
        'name'             => __( 'Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        'description'      => __( 'Editor drag-and-drop con bloques dinámicos (Lista Dinámica, 6 collections), filtros visitante firmados, caché CDN y templates custom.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        'version'          => FLAVOR_VBP_ADDON_VERSION,
        'author'           => 'Gailu Labs',
        'author_uri'       => 'https://gailu.net',
        'requires_core'    => '3.2.0',
        'settings_page'    => admin_url( 'admin.php?page=vbp-landing-list' ),
        'icon'             => 'dashicons-layout',
        'file'             => FLAVOR_VBP_ADDON_FILE,
        'is_premium'       => false,
        'documentation_url' => '',
        // init_callback intencionalmente vacío: el bootstrap ya inicializa
        // el VBP_Loader antes que este hook dispare. Cuando se mueva el
        // código aquí (paso 2), este callback instanciará el loader.
        'init_callback'    => null,
    ) );
} );
