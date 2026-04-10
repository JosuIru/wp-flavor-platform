<?php
/**
 * Código para añadir en includes/bootstrap/class-bootstrap-dependencies.php
 * Ubicación: Al final de la sección de frontend controllers
 *
 * INSTRUCCIONES:
 * 1. Abrir includes/bootstrap/class-bootstrap-dependencies.php
 * 2. Buscar la sección de frontend controllers existentes
 * 3. Añadir este bloque al final de esa sección
 * 4. Guardar archivo
 * 5. Ejecutar: wp plugin deactivate flavor-chat-ia && wp plugin activate flavor-chat-ia
 */

// === FRONTEND CONTROLLERS - BATCH 2 (21 módulos recién generados) ===

// Advertising
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/advertising/frontend/class-advertising-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/advertising/frontend/class-advertising-frontend-controller.php';
    Flavor_Advertising_Frontend_Controller::get_instance();
}

// Agregador Contenido
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/agregador-contenido/frontend/class-agregador-contenido-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/agregador-contenido/frontend/class-agregador-contenido-frontend-controller.php';
    Flavor_Agregador_Contenido_Frontend_Controller::get_instance();
}

// Bares
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/bares/frontend/class-bares-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/bares/frontend/class-bares-frontend-controller.php';
    Flavor_Bares_Frontend_Controller::get_instance();
}

// Chat Estados
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/chat-estados/frontend/class-chat-estados-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/chat-estados/frontend/class-chat-estados-frontend-controller.php';
    Flavor_Chat_Estados_Frontend_Controller::get_instance();
}

// Clientes
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/clientes/frontend/class-clientes-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/clientes/frontend/class-clientes-frontend-controller.php';
    Flavor_Clientes_Frontend_Controller::get_instance();
}

// Contabilidad
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/contabilidad/frontend/class-contabilidad-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/contabilidad/frontend/class-contabilidad-frontend-controller.php';
    Flavor_Contabilidad_Frontend_Controller::get_instance();
}

// Crowdfunding
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/crowdfunding/frontend/class-crowdfunding-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/crowdfunding/frontend/class-crowdfunding-frontend-controller.php';
    Flavor_Crowdfunding_Frontend_Controller::get_instance();
}

// DEX Solana
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/dex-solana/frontend/class-dex-solana-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/dex-solana/frontend/class-dex-solana-frontend-controller.php';
    Flavor_Dex_Solana_Frontend_Controller::get_instance();
}

// Economía Suficiencia
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/economia-suficiencia/frontend/class-economia-suficiencia-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/economia-suficiencia/frontend/class-economia-suficiencia-frontend-controller.php';
    Flavor_Economia_Suficiencia_Frontend_Controller::get_instance();
}

// Email Marketing
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/email-marketing/frontend/class-email-marketing-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/email-marketing/frontend/class-email-marketing-frontend-controller.php';
    Flavor_Email_Marketing_Frontend_Controller::get_instance();
}

// Empresarial
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/empresarial/frontend/class-empresarial-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/empresarial/frontend/class-empresarial-frontend-controller.php';
    Flavor_Empresarial_Frontend_Controller::get_instance();
}

// Encuestas
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/encuestas/frontend/class-encuestas-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/encuestas/frontend/class-encuestas-frontend-controller.php';
    Flavor_Encuestas_Frontend_Controller::get_instance();
}

// Energía Comunitaria
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/energia-comunitaria/frontend/class-energia-comunitaria-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/energia-comunitaria/frontend/class-energia-comunitaria-frontend-controller.php';
    Flavor_Energia_Comunitaria_Frontend_Controller::get_instance();
}

// Facturas
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/facturas/frontend/class-facturas-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/facturas/frontend/class-facturas-frontend-controller.php';
    Flavor_Facturas_Frontend_Controller::get_instance();
}

// Huella Ecológica
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/huella-ecologica/frontend/class-huella-ecologica-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/huella-ecologica/frontend/class-huella-ecologica-frontend-controller.php';
    Flavor_Huella_Ecologica_Frontend_Controller::get_instance();
}

// Kulturaka
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/kulturaka/frontend/class-kulturaka-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/kulturaka/frontend/class-kulturaka-frontend-controller.php';
    Flavor_Kulturaka_Frontend_Controller::get_instance();
}

// Red Social
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/red-social/frontend/class-red-social-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/red-social/frontend/class-red-social-frontend-controller.php';
    Flavor_Red_Social_Frontend_Controller::get_instance();
}

// Sello Conciencia
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/sello-conciencia/frontend/class-sello-conciencia-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/sello-conciencia/frontend/class-sello-conciencia-frontend-controller.php';
    Flavor_Sello_Conciencia_Frontend_Controller::get_instance();
}

// Themacle
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/themacle/frontend/class-themacle-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/themacle/frontend/class-themacle-frontend-controller.php';
    Flavor_Themacle_Frontend_Controller::get_instance();
}

// Trading IA
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/trading-ia/frontend/class-trading-ia-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trading-ia/frontend/class-trading-ia-frontend-controller.php';
    Flavor_Trading_IA_Frontend_Controller::get_instance();
}

// WooCommerce
if (file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/woocommerce/frontend/class-woocommerce-frontend-controller.php')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/modules/woocommerce/frontend/class-woocommerce-frontend-controller.php';
    Flavor_Woocommerce_Frontend_Controller::get_instance();
}

// === FIN FRONTEND CONTROLLERS BATCH 2 ===
