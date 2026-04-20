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
 * ESTADO DEL ADDON — Camino A paso 1 (wrapper):
 *
 * Actualmente este addon es un wrapper metadata. El código real de VBP
 * sigue viviendo en includes/visual-builder-pro/ y se carga desde
 * class-bootstrap-dependencies.php::load_editor_visual_builder() como
 * antes. Este archivo añade:
 *
 *   - Presencia en Flavor_Addon_Manager (visible en el admin de addons).
 *   - Metadata estable (versión, autor, descripción, URL docs).
 *   - Punto único para en el futuro mover físicamente los archivos
 *     sin tocar el resto del plugin.
 *
 * No hay riesgo de regresión porque el addon no carga VBP: VBP ya está
 * cargado cuando esto se ejecuta (plugins_loaded prio 5 vs. bootstrap
 * que corre antes). Aquí solo se registra para que aparezca listado.
 *
 * Cuando se quiera hacer el Camino A paso 2 (mover archivos):
 *   1. Copiar includes/visual-builder-pro/ aquí.
 *   2. Cambiar class-bootstrap-dependencies.php para require desde
 *      FLAVOR_VBP_ADDON_PATH en vez de FLAVOR_PLATFORM_PATH.
 *   3. Actualizar autoloader mapeos si es necesario.
 *   4. Actualizar asset paths en class-vbp-canvas.php (URL/PATH).
 *
 * Paso 3 (plugin independiente): no se hace todavía. Ver debate en la
 * decisión #12 del TODO — requiere signal de usuario y ~1.5 meses
 * de refactor, no es trivial.
 */

// Constantes del addon. Apuntan a la ubicación actual del código
// VBP (includes/...) para que el paso 2 sea cambiar una sola línea.
if ( ! defined( 'FLAVOR_VBP_ADDON_VERSION' ) ) {
    define( 'FLAVOR_VBP_ADDON_VERSION', '2.2.4' );
}

if ( ! defined( 'FLAVOR_VBP_ADDON_PATH' ) ) {
    // Por ahora el código físicamente vive en includes/visual-builder-pro/.
    // Cuando se migre aquí, cambiar a plugin_dir_path( __FILE__ ).
    define( 'FLAVOR_VBP_ADDON_PATH', FLAVOR_PLATFORM_PATH . 'includes/visual-builder-pro/' );
}

if ( ! defined( 'FLAVOR_VBP_ADDON_URL' ) ) {
    define( 'FLAVOR_VBP_ADDON_URL', FLAVOR_PLATFORM_URL . 'includes/visual-builder-pro/' );
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
