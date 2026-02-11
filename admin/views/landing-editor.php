<?php
/**
 * Template del Editor Visual de Landing Pages
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del post
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$post = get_post($post_id);

if (!$post) {
    wp_die(__('Página no encontrada', 'flavor-chat-ia'));
}

// Obtener estructura guardada
$editor = Flavor_Landing_Editor::get_instance();
$structure = $editor->get_landing_structure($post_id);
$sections_config = $editor->get_available_sections();

// Color primario global
$global_color = '#3b82f6';
if ($structure && isset($structure['settings']['color_primario'])) {
    $global_color = $structure['settings']['color_primario'];
}

$category_map = [];
foreach ($sections_config as $section) {
    $cat = $section['category'] ?? 'general';
    $label = $section['category_label'] ?? ucfirst($cat);
    $category_map[$cat] = $label;
}
$category_order = ['general', 'base', 'contenido', 'conversion', 'social', 'multimedia', 'empresa', 'modulos'];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php printf(__('Editando: %s', 'flavor-chat-ia'), esc_html($post->post_title)); ?></title>
    <?php wp_head(); ?>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        #wpadminbar {
            display: none !important;
        }
    </style>
</head>
<body class="flavor-landing-editor-page">

<div id="flavor-landing-editor">
    <!-- Header -->
    <header class="flavor-editor-header">
        <div class="flavor-editor-header-left">
            <a href="<?php echo esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')); ?>" class="flavor-editor-back">
                <span class="dashicons dashicons-arrow-left-alt"></span>
            </a>
            <button type="button" id="flavor-toggle-sidebar" class="flavor-toggle-btn" title="<?php esc_attr_e('Mostrar/Ocultar Secciones', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-menu"></span>
            </button>
            <button type="button" id="flavor-toggle-canvas" class="flavor-toggle-btn" title="<?php esc_attr_e('Mostrar/Ocultar Canvas', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-editor-table"></span>
            </button>
        </div>

        <div class="flavor-editor-header-center">
            <!-- Botón de plantillas -->
            <button type="button" id="flavor-templates-btn" title="<?php esc_attr_e('Plantillas', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-layout"></span>
                <?php _e('Plantillas', 'flavor-chat-ia'); ?>
            </button>

            <span class="flavor-toolbar-separator"></span>

            <!-- Device preview toggle -->
            <div class="flavor-device-toggle">
                <button type="button" class="flavor-device-btn active" data-device="desktop" title="<?php esc_attr_e('Escritorio', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-desktop"></span>
                </button>
                <button type="button" class="flavor-device-btn" data-device="tablet" title="<?php esc_attr_e('Tablet', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-tablet"></span>
                </button>
                <button type="button" class="flavor-device-btn" data-device="mobile" title="<?php esc_attr_e('Móvil', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-smartphone"></span>
                </button>
            </div>

            <span class="flavor-toolbar-separator"></span>

            <!-- Zoom controls -->
            <div class="flavor-zoom-controls">
                <button type="button" id="flavor-zoom-out" class="flavor-toolbar-btn" title="<?php esc_attr_e('Reducir zoom (Ctrl+-)', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-minus"></span>
                </button>
                <select id="flavor-zoom-select" class="flavor-zoom-select" title="<?php esc_attr_e('Nivel de zoom', 'flavor-chat-ia'); ?>">
                    <option value="50">50%</option>
                    <option value="75">75%</option>
                    <option value="100" selected>100%</option>
                    <option value="125">125%</option>
                    <option value="150">150%</option>
                </select>
                <button type="button" id="flavor-zoom-in" class="flavor-toolbar-btn" title="<?php esc_attr_e('Aumentar zoom (Ctrl++)', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-plus"></span>
                </button>
            </div>

            <span class="flavor-toolbar-separator"></span>

            <!-- Global color -->
            <div class="flavor-global-color">
                <label for="flavor-global-color"><?php _e('Color:', 'flavor-chat-ia'); ?></label>
                <input type="text" id="flavor-global-color" class="flavor-color-picker" value="<?php echo esc_attr($global_color); ?>">
            </div>
        </div>

        <div class="flavor-editor-header-right">
            <button type="button" id="flavor-editor-undo" class="flavor-editor-btn flavor-editor-btn-secondary disabled" title="<?php esc_attr_e('Deshacer (Ctrl+Z)', 'flavor-chat-ia'); ?>" disabled>
                <span class="dashicons dashicons-undo"></span>
            </button>
            <button type="button" id="flavor-editor-redo" class="flavor-editor-btn flavor-editor-btn-secondary disabled" title="<?php esc_attr_e('Rehacer (Ctrl+Y)', 'flavor-chat-ia'); ?>" disabled>
                <span class="dashicons dashicons-redo"></span>
            </button>
            <button type="button" id="flavor-editor-preview" class="flavor-editor-btn flavor-editor-btn-secondary">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Preview', 'flavor-chat-ia'); ?>
            </button>
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" target="_blank" class="flavor-editor-btn flavor-editor-btn-secondary">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Ver', 'flavor-chat-ia'); ?>
            </a>
            <button type="button" id="flavor-editor-save" class="flavor-editor-btn flavor-editor-btn-primary">
                <span class="dashicons dashicons-cloud-saved"></span>
                <?php _e('Guardar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </header>

    <!-- Body -->
    <div class="flavor-editor-body">
        <!-- Panel izquierdo: Secciones disponibles -->
        <aside class="flavor-editor-sidebar">
            <div class="flavor-sidebar-header">
                <h2 class="flavor-sidebar-title"><?php _e('Secciones', 'flavor-chat-ia'); ?></h2>
                <input type="text" id="flavor-section-search" class="flavor-section-search" placeholder="<?php esc_attr_e('Buscar secciones...', 'flavor-chat-ia'); ?>">
                <div class="flavor-section-filters">
                    <button type="button" class="flavor-section-filter active" data-category="all"><?php _e('Todas', 'flavor-chat-ia'); ?></button>
                    <?php
                    foreach ($category_order as $cat_key) {
                        if (!isset($category_map[$cat_key])) {
                            continue;
                        }
                        echo '<button type="button" class="flavor-section-filter" data-category="' . esc_attr($cat_key) . '">' . esc_html($category_map[$cat_key]) . '</button>';
                    }
                    foreach ($category_map as $cat_key => $cat_label) {
                        if (in_array($cat_key, $category_order, true)) {
                            continue;
                        }
                        echo '<button type="button" class="flavor-section-filter" data-category="' . esc_attr($cat_key) . '">' . esc_html($cat_label) . '</button>';
                    }
                    ?>
                </div>
            </div>
            <div class="flavor-sidebar-sections">
                <?php foreach ($sections_config as $section_key => $section): ?>
                    <div class="flavor-editor-section-item" draggable="true" data-section-type="<?php echo esc_attr($section_key); ?>" data-category="<?php echo esc_attr($section['category'] ?? 'general'); ?>">
                        <span class="dashicons <?php echo esc_attr($section['icon']); ?>"></span>
                        <div class="flavor-section-item-info">
                            <span class="flavor-section-item-label"><?php echo esc_html($section['label']); ?></span>
                            <span class="flavor-section-item-desc"><?php echo esc_html($section['description']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Split view: Canvas + Preview -->
        <div class="flavor-editor-split">
            <!-- Panel central: Canvas -->
            <main class="flavor-editor-main">
                <div id="flavor-editor-canvas">
                    <div class="flavor-canvas-content">
                        <div class="flavor-canvas-empty">
                            <span class="dashicons dashicons-layout"></span>
                            <p><?php _e('Arrastra secciones aquí para empezar', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Preview area -->
            <div class="flavor-preview-area">
                <div class="flavor-preview-wrapper device-desktop">
                    <iframe id="flavor-preview-iframe" name="flavor-preview-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>

        <!-- Panel derecho: Opciones de sección -->
        <aside id="flavor-editor-options">
            <div class="flavor-options-content">
                <!-- Se rellena dinámicamente -->
            </div>
        </aside>
    </div>

    <!-- Notifications -->
    <div class="flavor-editor-notices"></div>
</div>

<!-- Modal: Selector de iconos (ya incluido en JS dinámicamente) -->

<?php wp_footer(); ?>

<script>
    // Inicializar el editor cuando todo esté cargado
    jQuery(document).ready(function($) {
        // El editor se inicializa automáticamente desde landing-editor.js
        console.log('Flavor Landing Editor initialized for post #<?php echo $post_id; ?>');
    });
</script>

</body>
</html>
