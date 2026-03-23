<?php
/**
 * Visual Builder Pro - Editor Fullscreen Template
 *
 * Template principal del editor visual fullscreen tipo Photoshop/Figma.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Obtener datos del post
$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
$post    = get_post( $post_id );
$editor  = Flavor_VBP_Editor::get_instance();
$datos   = $editor->obtener_datos_documento( $post_id );

// Serializar datos para Alpine.js
$datos_json = wp_json_encode( $datos );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="vbp-editor-html">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $post->post_title ); ?> - Visual Builder Pro</title>
    <?php wp_head(); ?>
    <style id="vbp-fullscreen-overrides">
        /* === FORZAR FULLSCREEN - OCULTAR MENUS WP === */
        html.vbp-editor-html,
        body.vbp-editor-body {
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            min-width: 100vw !important;
            min-height: 100vh !important;
        }
        #adminmenuwrap,
        #adminmenuback,
        #adminmenumain,
        #adminmenu,
        #wpadminbar,
        #wpfooter,
        .update-nag,
        .notice,
        #screen-meta,
        #screen-meta-links {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        #wpcontent,
        #wpbody,
        #wpbody-content,
        .wrap {
            margin: 0 !important;
            padding: 0 !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
        }
        html.wp-toolbar {
            padding-top: 0 !important;
        }
        body.admin-bar {
            padding-top: 0 !important;
            margin-top: 0 !important;
        }
    </style>
</head>
<body class="vbp-editor-body" x-data="vbpApp()" x-init="initEditor(<?php echo esc_attr( $datos_json ); ?>)" @keydown.window="handleKeydown($event)">

    <!-- Skip to content link for accessibility -->
    <a href="#vbp-canvas-main" class="vbp-skip-link"><?php esc_html_e( 'Saltar al contenido del canvas', 'flavor-chat-ia' ); ?></a>

    <!-- Toolbar Superior -->
    <header class="vbp-toolbar-top" role="banner" aria-label="<?php esc_attr_e( 'Barra de herramientas principal', 'flavor-chat-ia' ); ?>">
        <div class="vbp-toolbar-left">
            <div class="vbp-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18"/>
                    <path d="M9 21V9"/>
                </svg>
            </div>

            <button type="button" @click="showTemplatesModal = true" class="vbp-btn vbp-btn-secondary" title="<?php esc_attr_e( 'Templates predefinidos', 'flavor-chat-ia' ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                <span><?php esc_html_e( 'Templates', 'flavor-chat-ia' ); ?></span>
            </button>

            <div class="vbp-document-title">
                <input type="text" x-model="documentTitle" @change="markDirty()" class="vbp-title-input" placeholder="<?php esc_attr_e( 'Título del documento', 'flavor-chat-ia' ); ?>">
            </div>

            <div class="vbp-history-buttons" role="group" aria-label="<?php esc_attr_e( 'Historial de cambios', 'flavor-chat-ia' ); ?>">
                <button type="button" @click="$store.vbp.undo()" :disabled="!$store.vbp.canUndo" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Deshacer', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+Z" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Deshacer', 'flavor-chat-ia' ); ?>" :aria-disabled="!$store.vbp.canUndo">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
                </button>
                <button type="button" @click="$store.vbp.redo()" :disabled="!$store.vbp.canRedo" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Rehacer', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+Shift+Z" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Rehacer', 'flavor-chat-ia' ); ?>" :aria-disabled="!$store.vbp.canRedo">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 019-9 9 9 0 016 2.3l3 2.7"/></svg>
                </button>
            </div>
        </div>

        <div class="vbp-toolbar-center">
            <div class="vbp-device-selector" role="group" aria-label="<?php esc_attr_e( 'Vista previa de dispositivo', 'flavor-chat-ia' ); ?>">
                <button type="button" @click="setDevicePreview('desktop')" :class="{ 'active': devicePreview === 'desktop' }" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Desktop', 'flavor-chat-ia' ); ?>" data-shortcut="D" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Vista desktop', 'flavor-chat-ia' ); ?>" :aria-pressed="devicePreview === 'desktop'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </button>
                <button type="button" @click="setDevicePreview('tablet')" :class="{ 'active': devicePreview === 'tablet' }" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Tablet', 'flavor-chat-ia' ); ?>" data-shortcut="T" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Vista tablet', 'flavor-chat-ia' ); ?>" :aria-pressed="devicePreview === 'tablet'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                </button>
                <button type="button" @click="setDevicePreview('mobile')" :class="{ 'active': devicePreview === 'mobile' }" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Mobile', 'flavor-chat-ia' ); ?>" data-shortcut="M" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Vista móvil', 'flavor-chat-ia' ); ?>" :aria-pressed="devicePreview === 'mobile'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                </button>
            </div>

            <!-- Split Screen Toggle -->
            <button type="button" @click="toggleSplitScreen()" :class="{ 'active': splitScreenMode }" class="vbp-btn-icon vbp-split-screen-btn" data-tooltip="<?php esc_attr_e( 'Vista dividida', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+\\" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Activar/desactivar vista dividida', 'flavor-chat-ia' ); ?>" :aria-pressed="splitScreenMode">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="2" y="3" width="20" height="18" rx="2"/>
                    <path d="M12 3v18"/>
                </svg>
            </button>

            <div class="vbp-zoom-controls" role="group" aria-label="<?php esc_attr_e( 'Control de zoom', 'flavor-chat-ia' ); ?>" x-data="{ showSlider: false }">
                <button type="button" @click="zoomOut()" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Alejar', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+-" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Alejar zoom', 'flavor-chat-ia' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M8 11h6"/></svg>
                </button>
                <span class="vbp-zoom-value" @click="showSlider = !showSlider" x-text="zoom + '%'" aria-live="polite" aria-atomic="true" data-tooltip="<?php esc_attr_e( 'Clic para ajustar zoom', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+0" data-tooltip-position="bottom">100%</span>
                <button type="button" @click="zoomIn()" class="vbp-btn-icon" data-tooltip="<?php esc_attr_e( 'Acercar', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl++" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Acercar zoom', 'flavor-chat-ia' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
                </button>

                <!-- Zoom progress bar -->
                <div class="vbp-zoom-bar" aria-hidden="true">
                    <div class="vbp-zoom-bar-fill" :style="{ width: ((zoom - 25) / 175 * 100) + '%' }"></div>
                </div>

                <!-- Zoom slider popup -->
                <div class="vbp-zoom-slider-popup" :class="{ 'visible': showSlider }" @click.away="showSlider = false">
                    <div class="vbp-zoom-slider-container">
                        <div class="vbp-zoom-slider-header">
                            <span class="vbp-zoom-slider-label"><?php esc_html_e( 'Nivel de Zoom', 'flavor-chat-ia' ); ?></span>
                            <span class="vbp-zoom-slider-value" x-text="zoom + '%'"></span>
                        </div>
                        <input
                            type="range"
                            class="vbp-zoom-slider"
                            min="25"
                            max="200"
                            step="5"
                            x-model="zoom"
                            aria-label="<?php esc_attr_e( 'Ajustar zoom', 'flavor-chat-ia' ); ?>"
                        >
                        <div class="vbp-zoom-presets">
                            <button type="button" class="vbp-zoom-preset" :class="{ 'active': zoom == 50 }" @click="zoom = 50">50%</button>
                            <button type="button" class="vbp-zoom-preset" :class="{ 'active': zoom == 75 }" @click="zoom = 75">75%</button>
                            <button type="button" class="vbp-zoom-preset" :class="{ 'active': zoom == 100 }" @click="zoom = 100">100%</button>
                            <button type="button" class="vbp-zoom-preset" :class="{ 'active': zoom == 150 }" @click="zoom = 150">150%</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="vbp-toolbar-right">
            <!-- Indicador de Autosave Mejorado -->
            <div class="vbp-autosave-indicator" x-data="{ store: $store.vbp }">
                <!-- Guardado -->
                <template x-if="store.saveStatus === 'saved'">
                    <div class="vbp-autosave vbp-autosave--saved" :title="store.getSaveStatusText()">
                        <svg class="vbp-autosave__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span class="vbp-autosave__text"><?php esc_html_e( 'Guardado', 'flavor-chat-ia' ); ?></span>
                    </div>
                </template>
                <!-- Guardando -->
                <template x-if="store.saveStatus === 'saving'">
                    <div class="vbp-autosave vbp-autosave--saving">
                        <svg class="vbp-autosave__spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        <span class="vbp-autosave__text"><?php esc_html_e( 'Guardando...', 'flavor-chat-ia' ); ?></span>
                    </div>
                </template>
                <!-- Error -->
                <template x-if="store.saveStatus === 'error'">
                    <div class="vbp-autosave vbp-autosave--error" @click="store.autoSave()">
                        <svg class="vbp-autosave__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <span class="vbp-autosave__text"><?php esc_html_e( 'Error - Reintentar', 'flavor-chat-ia' ); ?></span>
                    </div>
                </template>
                <!-- Sin guardar (dirty) -->
                <template x-if="store.saveStatus === 'dirty'">
                    <div class="vbp-autosave vbp-autosave--dirty">
                        <span class="vbp-autosave__dot"></span>
                        <span class="vbp-autosave__text"><?php esc_html_e( 'Sin guardar', 'flavor-chat-ia' ); ?></span>
                    </div>
                </template>
            </div>

            <!-- Theme Toggle -->
            <div class="vbp-theme-toggle" x-data="{ showMenu: false }">
                <button
                    type="button"
                    @click="showMenu = !showMenu"
                    @click.away="showMenu = false"
                    class="vbp-btn vbp-btn-icon"
                    :title="'<?php esc_attr_e( 'Tema:', 'flavor-chat-ia' ); ?> ' + $store.vbpTheme.getLabel()"
                    aria-haspopup="true"
                    :aria-expanded="showMenu"
                >
                    <template x-if="$store.vbpTheme.isDark()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </template>
                    <template x-if="$store.vbpTheme.isLight()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                    </template>
                </button>
                <div
                    x-show="showMenu"
                    x-transition:enter="vbp-dropdown-enter"
                    x-transition:leave="vbp-dropdown-leave"
                    class="vbp-theme-menu"
                    role="menu"
                >
                    <button
                        type="button"
                        @click="$store.vbpTheme.setTheme('light'); showMenu = false"
                        class="vbp-theme-option"
                        :class="{ 'active': $store.vbpTheme.current === 'light' }"
                        role="menuitemradio"
                        :aria-checked="$store.vbpTheme.current === 'light'"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <span><?php esc_html_e( 'Claro', 'flavor-chat-ia' ); ?></span>
                    </button>
                    <button
                        type="button"
                        @click="$store.vbpTheme.setTheme('dark'); showMenu = false"
                        class="vbp-theme-option"
                        :class="{ 'active': $store.vbpTheme.current === 'dark' }"
                        role="menuitemradio"
                        :aria-checked="$store.vbpTheme.current === 'dark'"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <span><?php esc_html_e( 'Oscuro', 'flavor-chat-ia' ); ?></span>
                    </button>
                    <button
                        type="button"
                        @click="$store.vbpTheme.setTheme('system'); showMenu = false"
                        class="vbp-theme-option"
                        :class="{ 'active': $store.vbpTheme.current === 'system' }"
                        role="menuitemradio"
                        :aria-checked="$store.vbpTheme.current === 'system'"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <path d="M8 21h8M12 17v4"/>
                        </svg>
                        <span><?php esc_html_e( 'Sistema', 'flavor-chat-ia' ); ?></span>
                    </button>
                </div>
            </div>

            <button type="button" @click="openRevisionsModal()" class="vbp-btn vbp-btn-secondary" data-tooltip="<?php esc_attr_e( 'Historial de revisiones', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+H" data-tooltip-position="bottom">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </button>

            <button type="button" @click="openPageSettings()" class="vbp-btn vbp-btn-secondary" data-tooltip="<?php esc_attr_e( 'Configuración de página', 'flavor-chat-ia' ); ?>" data-tooltip-position="bottom">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            </button>

            <a href="<?php echo esc_url( get_preview_post_link( $post_id ) ); ?>" target="_blank" class="vbp-btn vbp-btn-secondary" data-tooltip="<?php esc_attr_e( 'Ver preview en nueva pestaña', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+P" data-tooltip-position="bottom">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <span><?php esc_html_e( 'Preview', 'flavor-chat-ia' ); ?></span>
            </a>

            <button type="button" @click="saveDocument()" class="vbp-btn vbp-btn-primary" :disabled="isSaving" data-tooltip="<?php esc_attr_e( 'Guardar cambios', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+S" data-tooltip-position="bottom">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
                <span x-text="isSaving ? '<?php esc_attr_e( 'Guardando...', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Guardar', 'flavor-chat-ia' ); ?>'"></span>
            </button>

            <button type="button" @click="publishDocument()" class="vbp-btn vbp-btn-success" data-tooltip="<?php esc_attr_e( 'Publicar página', 'flavor-chat-ia' ); ?>" data-shortcut="Ctrl+Shift+P" data-tooltip-position="bottom">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
                <span><?php esc_html_e( 'Publicar', 'flavor-chat-ia' ); ?></span>
            </button>

            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=flavor_landing' ) ); ?>" class="vbp-btn vbp-btn-icon" title="<?php esc_attr_e( 'Cerrar', 'flavor-chat-ia' ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </a>
        </div>
    </header>

    <!-- Contenedor Principal -->
    <main class="vbp-main" role="application" aria-label="<?php esc_attr_e( 'Editor Visual Builder Pro', 'flavor-chat-ia' ); ?>">
        <!-- Panel Izquierdo -->
        <aside class="vbp-sidebar-left" :class="{ 'collapsed': !panels.blocks && !panels.layers }" role="complementary" aria-label="<?php esc_attr_e( 'Panel de bloques y capas', 'flavor-chat-ia' ); ?>">
            <!-- Header móvil -->
            <div class="vbp-sidebar-mobile-header">
                <span class="vbp-sidebar-title"><?php esc_html_e( 'Bloques', 'flavor-chat-ia' ); ?></span>
                <button type="button" class="vbp-sidebar-close" onclick="closeMobileSidebars()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-sidebar-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Navegación del panel izquierdo', 'flavor-chat-ia' ); ?>">
                <button type="button" @click="activeLeftTab = 'blocks'" :class="{ 'active': activeLeftTab === 'blocks' }" class="vbp-tab-btn" role="tab" :aria-selected="activeLeftTab === 'blocks'" aria-controls="vbp-panel-blocks" id="vbp-tab-blocks">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    <?php esc_html_e( 'Bloques', 'flavor-chat-ia' ); ?>
                </button>
                <button type="button" @click="activeLeftTab = 'layers'" :class="{ 'active': activeLeftTab === 'layers' }" class="vbp-tab-btn" role="tab" :aria-selected="activeLeftTab === 'layers'" aria-controls="vbp-panel-layers" id="vbp-tab-layers">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12,2 2,7 12,12 22,7"/><polyline points="2,17 12,22 22,17"/><polyline points="2,12 12,17 22,12"/></svg>
                    <?php esc_html_e( 'Capas', 'flavor-chat-ia' ); ?>
                </button>
                <button type="button" @click="activeLeftTab = 'components'" :class="{ 'active': activeLeftTab === 'components' }" class="vbp-tab-btn" role="tab" :aria-selected="activeLeftTab === 'components'" aria-controls="vbp-panel-components" id="vbp-tab-components">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    <?php esc_html_e( 'Biblioteca', 'flavor-chat-ia' ); ?>
                </button>
            </div>

            <div x-show="activeLeftTab === 'blocks'" class="vbp-panel vbp-blocks-panel" role="tabpanel" id="vbp-panel-blocks" aria-labelledby="vbp-tab-blocks">
                <?php include __DIR__ . '/panel-blocks.php'; ?>
            </div>

            <div x-show="activeLeftTab === 'layers'" class="vbp-panel vbp-layers-panel" role="tabpanel" id="vbp-panel-layers" aria-labelledby="vbp-tab-layers">
                <?php include __DIR__ . '/panel-layers.php'; ?>
            </div>

            <div x-show="activeLeftTab === 'components'" class="vbp-panel vbp-components-panel" role="tabpanel" id="vbp-panel-components" aria-labelledby="vbp-tab-components">
                <?php include __DIR__ . '/panel-components.php'; ?>
            </div>
        </aside>

        <!-- Canvas Central -->
        <section class="vbp-canvas-area" id="vbp-canvas-main" aria-label="<?php esc_attr_e( 'Área de diseño', 'flavor-chat-ia' ); ?>">
            <div class="vbp-ruler vbp-ruler-horizontal" x-show="showRulers" aria-hidden="true">
                <canvas id="vbp-ruler-h" width="2000" height="20"></canvas>
            </div>
            <div class="vbp-ruler vbp-ruler-vertical" x-show="showRulers" aria-hidden="true">
                <canvas id="vbp-ruler-v" width="20" height="2000"></canvas>
            </div>

            <!-- Breadcrumbs de navegación -->
            <nav class="vbp-breadcrumbs" aria-label="<?php esc_attr_e( 'Navegación de elementos', 'flavor-chat-ia' ); ?>" x-show="breadcrumbs.length > 0">
                <ol class="vbp-breadcrumbs-list">
                    <template x-for="(crumb, index) in breadcrumbs" :key="crumb.id">
                        <li class="vbp-breadcrumb-item" :class="{ 'vbp-breadcrumb-item--active': index === breadcrumbs.length - 1 }">
                            <button
                                type="button"
                                class="vbp-breadcrumb-link"
                                @click="navigateToBreadcrumb(crumb)"
                                :title="crumb.name"
                                :aria-current="index === breadcrumbs.length - 1 ? 'location' : false"
                            >
                                <span class="vbp-breadcrumb-icon" x-html="getBreadcrumbIcon(crumb.type)" aria-hidden="true"></span>
                                <span class="vbp-breadcrumb-text" x-text="crumb.name"></span>
                            </button>
                            <template x-if="index < breadcrumbs.length - 1">
                                <span class="vbp-breadcrumb-separator" aria-hidden="true">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </span>
                            </template>
                        </li>
                    </template>
                </ol>
            </nav>

            <div class="vbp-canvas-wrapper" :style="{ transform: 'scale(' + (zoom/100) + ')' }" @click.self="clearSelection()">
                <div class="vbp-canvas" :class="'vbp-canvas--' + devicePreview" :style="canvasStyles" x-ref="canvas" @dragover.prevent="handleDragOver($event)" @drop.prevent="handleDrop($event)" role="region" aria-label="<?php esc_attr_e( 'Canvas del diseño', 'flavor-chat-ia' ); ?>" aria-live="polite">
                    <template x-for="element in $store.vbp.elements" :key="element.id + '-' + (element._version || 0)">
                        <div class="vbp-element" :class="getElementClasses(element)" :data-element-id="element.id" :data-element-type="element.type" @click.stop="selectElement(element, $event)" @dblclick="editElement(element)" :draggable="!element.locked" @dragstart="handleElementDragStart($event, element)" @dragend="handleElementDragEnd($event)">
                            <template x-if="isSelected(element.id) && !element.locked">
                                <div class="vbp-element-handles">
                                    <div class="vbp-handle vbp-handle-nw"></div>
                                    <div class="vbp-handle vbp-handle-n"></div>
                                    <div class="vbp-handle vbp-handle-ne"></div>
                                    <div class="vbp-handle vbp-handle-w"></div>
                                    <div class="vbp-handle vbp-handle-e"></div>
                                    <div class="vbp-handle vbp-handle-sw"></div>
                                    <div class="vbp-handle vbp-handle-s"></div>
                                    <div class="vbp-handle vbp-handle-se"></div>
                                </div>
                            </template>
                            <template x-if="isSelected(element.id)">
                                <div class="vbp-element-toolbar">
                                    <button type="button" @click.stop="moveElementUp(element)" class="vbp-element-action"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
                                    <button type="button" @click.stop="moveElementDown(element)" class="vbp-element-action"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg></button>
                                    <button type="button" @click.stop="duplicateElement(element)" class="vbp-element-action"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button>
                                    <button type="button" @click.stop="deleteElement(element)" class="vbp-element-action vbp-element-action--danger"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></button>
                                </div>
                            </template>
                            <div class="vbp-element-content" x-html="renderElement(element)"></div>
                        </div>
                    </template>

                    <div class="vbp-drop-indicator" x-show="dropIndicator.visible" :style="{ top: dropIndicator.y + 'px' }"></div>

                    <template x-if="$store.vbp.elements.length === 0">
                        <div class="vbp-canvas-empty">
                            <div class="vbp-empty-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>
                            </div>
                            <h3><?php esc_html_e( 'Arrastra bloques aquí', 'flavor-chat-ia' ); ?></h3>
                            <p><?php esc_html_e( 'Selecciona un bloque del panel izquierdo y arrástralo al canvas', 'flavor-chat-ia' ); ?></p>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- Panel Derecho -->
        <aside class="vbp-sidebar-right" :class="{ 'collapsed': !panels.inspector }" role="complementary" aria-label="<?php esc_attr_e( 'Inspector de propiedades', 'flavor-chat-ia' ); ?>">
            <!-- Header móvil -->
            <div class="vbp-sidebar-mobile-header">
                <span class="vbp-sidebar-title"><?php esc_html_e( 'Inspector', 'flavor-chat-ia' ); ?></span>
                <button type="button" class="vbp-sidebar-close" onclick="closeMobileSidebars()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <?php include __DIR__ . '/panel-inspector.php'; ?>
        </aside>
    </main>

    <?php include __DIR__ . '/toolbar-floating.php'; ?>

    <!-- Modal de Ayuda -->
    <div class="vbp-modal-overlay" x-show="showHelpModal" x-cloak @click.self="showHelpModal = false" @keydown.escape.window="showHelpModal = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" role="dialog" aria-modal="true" aria-labelledby="vbp-help-modal-title">
        <div class="vbp-modal vbp-modal-help">
            <div class="vbp-modal-header">
                <h2 id="vbp-help-modal-title"><?php esc_html_e( 'Atajos de Teclado', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showHelpModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <div class="vbp-shortcuts-grid">
                    <div class="vbp-shortcuts-group">
                        <h4><?php esc_html_e( 'Archivo', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>S</kbd> <span><?php esc_html_e( 'Guardar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>S</kbd> <span><?php esc_html_e( 'Guardar como template', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>P</kbd> <span><?php esc_html_e( 'Preview', 'flavor-chat-ia' ); ?></span></div>
                    </div>
                    <div class="vbp-shortcuts-group">
                        <h4><?php esc_html_e( 'Edición', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>Z</kbd> <span><?php esc_html_e( 'Deshacer', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>Z</kbd> <span><?php esc_html_e( 'Rehacer', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>C</kbd> <span><?php esc_html_e( 'Copiar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>X</kbd> <span><?php esc_html_e( 'Cortar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>V</kbd> <span><?php esc_html_e( 'Pegar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>D</kbd> <span><?php esc_html_e( 'Duplicar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Delete</kbd> <span><?php esc_html_e( 'Eliminar', 'flavor-chat-ia' ); ?></span></div>
                    </div>
                    <div class="vbp-shortcuts-group">
                        <h4><?php esc_html_e( 'Selección', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>A</kbd> <span><?php esc_html_e( 'Seleccionar todo', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Esc</kbd> <span><?php esc_html_e( 'Deseleccionar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>↑</kbd> / <kbd>↓</kbd> <span><?php esc_html_e( 'Mover elemento', 'flavor-chat-ia' ); ?></span></div>
                    </div>
                    <div class="vbp-shortcuts-group">
                        <h4><?php esc_html_e( 'Zoom', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>+</kbd> <span><?php esc_html_e( 'Acercar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>-</kbd> <span><?php esc_html_e( 'Alejar', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>0</kbd> <span><?php esc_html_e( 'Zoom 100%', 'flavor-chat-ia' ); ?></span></div>
                    </div>
                    <div class="vbp-shortcuts-group">
                        <h4><?php esc_html_e( 'Paneles', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>\</kbd> <span><?php esc_html_e( 'Toggle paneles', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>B</kbd> <span><?php esc_html_e( 'Panel bloques', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>Ctrl</kbd> + <kbd>I</kbd> <span><?php esc_html_e( 'Inspector', 'flavor-chat-ia' ); ?></span></div>
                        <div class="vbp-shortcut"><kbd>?</kbd> <span><?php esc_html_e( 'Mostrar ayuda', 'flavor-chat-ia' ); ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paleta de Comandos -->
    <div class="vbp-modal-overlay vbp-command-palette-overlay" x-show="showCommandPalette" x-cloak @click.self="showCommandPalette = false" @keydown.escape.window="showCommandPalette = false" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Paleta de comandos', 'flavor-chat-ia' ); ?>">
        <div class="vbp-command-palette" @keydown.arrow-down.prevent="commandIndex = Math.min(commandIndex + 1, filteredCommands.length - 1)" @keydown.arrow-up.prevent="commandIndex = Math.max(commandIndex - 1, 0)" @keydown.enter.prevent="executeCommand(filteredCommands[commandIndex])">
            <div class="vbp-command-search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="commandSearch" x-ref="commandInput" @input="filterCommands()" placeholder="<?php esc_attr_e( 'Buscar comando...', 'flavor-chat-ia' ); ?>" class="vbp-command-input" aria-label="<?php esc_attr_e( 'Buscar comando', 'flavor-chat-ia' ); ?>" aria-autocomplete="list" aria-controls="vbp-command-results" :aria-activedescendant="'vbp-cmd-' + commandIndex">
            </div>
            <div class="vbp-command-list" x-show="filteredCommands.length > 0" role="listbox" id="vbp-command-results" aria-label="<?php esc_attr_e( 'Resultados de comandos', 'flavor-chat-ia' ); ?>">
                <template x-for="(cmd, index) in filteredCommands" :key="cmd.id">
                    <div class="vbp-command-item" :class="{ 'active': commandIndex === index }" @click="executeCommand(cmd)" @mouseenter="commandIndex = index" role="option" :id="'vbp-cmd-' + index" :aria-selected="commandIndex === index">
                        <span class="vbp-command-icon" x-html="cmd.icon" aria-hidden="true"></span>
                        <span class="vbp-command-name" x-text="cmd.name"></span>
                        <span class="vbp-command-shortcut" x-text="cmd.shortcut" aria-hidden="true"></span>
                    </div>
                </template>
            </div>
            <div class="vbp-command-empty" x-show="filteredCommands.length === 0 && commandSearch" role="status">
                <p><?php esc_html_e( 'No se encontraron comandos', 'flavor-chat-ia' ); ?></p>
            </div>
        </div>
    </div>

    <div class="vbp-notifications" x-show="notifications.length > 0" role="region" aria-label="<?php esc_attr_e( 'Notificaciones', 'flavor-chat-ia' ); ?>" aria-live="polite">
        <template x-for="notification in notifications" :key="notification.id">
            <div class="vbp-notification" :class="'vbp-notification--' + notification.type" x-show="notification.visible" x-transition :role="notification.type === 'error' ? 'alert' : 'status'">
                <span x-text="notification.message"></span>
                <template x-if="notification.actionLabel">
                    <button type="button" @click="executeNotificationAction(notification)" class="vbp-notification-action">
                        <span x-text="notification.actionLabel"></span>
                    </button>
                </template>
                <button type="button" @click="dismissNotification(notification.id)" class="vbp-notification-close" aria-label="<?php esc_attr_e( 'Cerrar notificación', 'flavor-chat-ia' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Modal de Templates -->
    <div class="vbp-modal-overlay" x-show="showTemplatesModal" x-cloak @click.self="showTemplatesModal = false" @keydown.escape.window="showTemplatesModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-templates-modal-title">
        <div class="vbp-modal vbp-modal-templates">
            <div class="vbp-modal-header">
                <h2 id="vbp-templates-modal-title"><?php esc_html_e( 'Templates', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showTemplatesModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <!-- Tabs de Templates -->
                <div class="vbp-templates-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Secciones de templates', 'flavor-chat-ia' ); ?>">
                    <button type="button" @click="templatesTab = 'library'" :class="{ 'active': templatesTab === 'library' }" class="vbp-templates-tab" role="tab" :aria-selected="templatesTab === 'library'" aria-controls="vbp-templates-library" id="vbp-tab-library">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        <?php esc_html_e( 'Librería', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" @click="templatesTab = 'my-templates'" :class="{ 'active': templatesTab === 'my-templates' }" class="vbp-templates-tab" role="tab" :aria-selected="templatesTab === 'my-templates'" aria-controls="vbp-templates-my" id="vbp-tab-my-templates">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <?php esc_html_e( 'Mis Templates', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" @click="templatesTab = 'import'" :class="{ 'active': templatesTab === 'import' }" class="vbp-templates-tab" role="tab" :aria-selected="templatesTab === 'import'" aria-controls="vbp-templates-import" id="vbp-tab-import">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7,10 12,15 17,10"/><path d="M12 15V3"/></svg>
                        <?php esc_html_e( 'Importar', 'flavor-chat-ia' ); ?>
                    </button>
                </div>

                <!-- Librería de Templates -->
                <div class="vbp-templates-content" x-show="templatesTab === 'library'" role="tabpanel" id="vbp-templates-library" aria-labelledby="vbp-tab-library">
                    <div class="vbp-templates-filter">
                        <input type="text" x-model="templateSearch" placeholder="<?php esc_attr_e( 'Buscar templates...', 'flavor-chat-ia' ); ?>" class="vbp-templates-search">
                        <select x-model="templateCategory" class="vbp-templates-category">
                            <option value=""><?php esc_html_e( 'Todas las categorías', 'flavor-chat-ia' ); ?></option>
                            <option value="landing"><?php esc_html_e( 'Landing Pages', 'flavor-chat-ia' ); ?></option>
                            <option value="portfolio"><?php esc_html_e( 'Portfolio', 'flavor-chat-ia' ); ?></option>
                            <option value="business"><?php esc_html_e( 'Negocios', 'flavor-chat-ia' ); ?></option>
                            <option value="ecommerce"><?php esc_html_e( 'E-commerce', 'flavor-chat-ia' ); ?></option>
                            <option value="blog"><?php esc_html_e( 'Blog', 'flavor-chat-ia' ); ?></option>
                        </select>
                    </div>
                    <div class="vbp-templates-grid">
                        <template x-for="template in filteredTemplates" :key="template.id">
                            <div class="vbp-template-card" @click="selectTemplate(template)">
                                <div class="vbp-template-preview">
                                    <img :src="template.thumbnail || '<?php echo esc_url( FLAVOR_CHAT_IA_URL . 'assets/vbp/images/template-placeholder.svg' ); ?>'" :alt="template.title">
                                    <div class="vbp-template-overlay">
                                        <button type="button" class="vbp-btn vbp-btn-primary"><?php esc_html_e( 'Usar template', 'flavor-chat-ia' ); ?></button>
                                        <button type="button" @click.stop="previewTemplate(template)" class="vbp-btn vbp-btn-secondary"><?php esc_html_e( 'Preview', 'flavor-chat-ia' ); ?></button>
                                    </div>
                                </div>
                                <div class="vbp-template-info">
                                    <h4 x-text="template.title"></h4>
                                    <span class="vbp-template-category" x-text="template.category"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="filteredTemplates.length === 0">
                            <div class="vbp-templates-empty">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                                <p><?php esc_html_e( 'No se encontraron templates', 'flavor-chat-ia' ); ?></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Mis Templates -->
                <div class="vbp-templates-content" x-show="templatesTab === 'my-templates'" role="tabpanel" id="vbp-templates-my" aria-labelledby="vbp-tab-my-templates">
                    <div class="vbp-my-templates-header">
                        <button type="button" @click="saveAsTemplate()" class="vbp-btn vbp-btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            <?php esc_html_e( 'Guardar diseño actual', 'flavor-chat-ia' ); ?>
                        </button>
                    </div>
                    <div class="vbp-templates-grid">
                        <template x-for="template in userTemplates" :key="template.id">
                            <div class="vbp-template-card">
                                <div class="vbp-template-preview">
                                    <img :src="template.thumbnail || '<?php echo esc_url( FLAVOR_CHAT_IA_URL . 'assets/vbp/images/template-placeholder.svg' ); ?>'" :alt="template.title">
                                    <div class="vbp-template-overlay">
                                        <button type="button" @click="applyTemplate(template)" class="vbp-btn vbp-btn-primary"><?php esc_html_e( 'Aplicar', 'flavor-chat-ia' ); ?></button>
                                        <button type="button" @click.stop="deleteTemplate(template)" class="vbp-btn vbp-btn-danger"><?php esc_html_e( 'Eliminar', 'flavor-chat-ia' ); ?></button>
                                    </div>
                                </div>
                                <div class="vbp-template-info">
                                    <h4 x-text="template.title"></h4>
                                    <span class="vbp-template-date" x-text="template.date"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="userTemplates.length === 0">
                            <div class="vbp-templates-empty">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/></svg>
                                <p><?php esc_html_e( 'No tienes templates guardados', 'flavor-chat-ia' ); ?></p>
                                <p class="vbp-templates-hint"><?php esc_html_e( 'Guarda tu diseño actual para reutilizarlo en otros proyectos', 'flavor-chat-ia' ); ?></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Importar Template -->
                <div class="vbp-templates-content" x-show="templatesTab === 'import'" role="tabpanel" id="vbp-templates-import" aria-labelledby="vbp-tab-import">
                    <div class="vbp-import-section">
                        <div class="vbp-import-dropzone" @dragover.prevent="importDragOver = true" @dragleave.prevent="importDragOver = false" @drop.prevent="handleImportDrop($event)" :class="{ 'dragover': importDragOver }">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17,8 12,3 7,8"/><path d="M12 3v12"/></svg>
                            <h4><?php esc_html_e( 'Arrastra un archivo JSON aquí', 'flavor-chat-ia' ); ?></h4>
                            <p><?php esc_html_e( 'o', 'flavor-chat-ia' ); ?></p>
                            <label class="vbp-btn vbp-btn-secondary">
                                <?php esc_html_e( 'Seleccionar archivo', 'flavor-chat-ia' ); ?>
                                <input type="file" accept=".json" @change="handleImportFile($event)" style="display: none;">
                            </label>
                        </div>

                        <div class="vbp-import-paste">
                            <h4><?php esc_html_e( 'O pega el JSON directamente:', 'flavor-chat-ia' ); ?></h4>
                            <textarea x-model="importJsonText" placeholder='{"elements": [...], "settings": {...}}' class="vbp-import-textarea"></textarea>
                            <button type="button" @click="importFromJson()" class="vbp-btn vbp-btn-primary" :disabled="!importJsonText.trim()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7,10 12,15 17,10"/><path d="M12 15V3"/></svg>
                                <?php esc_html_e( 'Importar', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Unsplash -->
    <div class="vbp-modal-overlay" x-show="showUnsplashModal" x-cloak @click.self="showUnsplashModal = false" @keydown.escape.window="showUnsplashModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-unsplash-modal-title">
        <div class="vbp-modal vbp-modal-unsplash">
            <div class="vbp-modal-header">
                <h2 id="vbp-unsplash-modal-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M7.5 6.75V0h9v6.75h-9zm9 3.75H24V24H0V10.5h7.5v6.75h9V10.5z"/></svg>
                    <?php esc_html_e( 'Unsplash - Imágenes Gratuitas', 'flavor-chat-ia' ); ?>
                </h2>
                <button type="button" @click="showUnsplashModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <!-- Barra de búsqueda -->
                <div class="vbp-unsplash-search">
                    <div class="vbp-search-input-wrapper">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input type="text"
                               x-model="unsplashQuery"
                               @keydown.enter="searchUnsplash()"
                               placeholder="<?php esc_attr_e( 'Buscar imágenes...', 'flavor-chat-ia' ); ?>"
                               class="vbp-input">
                        <button type="button" @click="searchUnsplash()" class="vbp-btn vbp-btn-primary" :disabled="isSearchingUnsplash">
                            <span x-show="!isSearchingUnsplash"><?php esc_html_e( 'Buscar', 'flavor-chat-ia' ); ?></span>
                            <span x-show="isSearchingUnsplash"><?php esc_html_e( 'Buscando...', 'flavor-chat-ia' ); ?></span>
                        </button>
                    </div>
                    <div class="vbp-unsplash-filters">
                        <select x-model="unsplashOrientation" class="vbp-select">
                            <option value=""><?php esc_html_e( 'Cualquier orientación', 'flavor-chat-ia' ); ?></option>
                            <option value="landscape"><?php esc_html_e( 'Horizontal', 'flavor-chat-ia' ); ?></option>
                            <option value="portrait"><?php esc_html_e( 'Vertical', 'flavor-chat-ia' ); ?></option>
                            <option value="squarish"><?php esc_html_e( 'Cuadrada', 'flavor-chat-ia' ); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Estado no configurado -->
                <div x-show="!unsplashConfigured" class="vbp-unsplash-not-configured">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <h3><?php esc_html_e( 'Unsplash no está configurado', 'flavor-chat-ia' ); ?></h3>
                    <p><?php esc_html_e( 'Para usar imágenes de Unsplash, configura tu Access Key en los ajustes del plugin.', 'flavor-chat-ia' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-chat-ia&tab=vbp' ) ); ?>" target="_blank" class="vbp-btn vbp-btn-primary">
                        <?php esc_html_e( 'Ir a Ajustes', 'flavor-chat-ia' ); ?>
                    </a>
                </div>

                <!-- Grid de imágenes -->
                <div x-show="unsplashConfigured" class="vbp-unsplash-grid-wrapper">
                    <!-- Estado vacío inicial -->
                    <div x-show="!unsplashQuery && unsplashImages.length === 0 && !isSearchingUnsplash" class="vbp-unsplash-empty">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                        <h3><?php esc_html_e( 'Busca imágenes gratuitas', 'flavor-chat-ia' ); ?></h3>
                        <p><?php esc_html_e( 'Escribe un término de búsqueda para encontrar imágenes de alta calidad.', 'flavor-chat-ia' ); ?></p>
                        <div class="vbp-unsplash-suggestions">
                            <span><?php esc_html_e( 'Sugerencias:', 'flavor-chat-ia' ); ?></span>
                            <button type="button" @click="unsplashQuery = 'nature'; searchUnsplash()" class="vbp-tag">nature</button>
                            <button type="button" @click="unsplashQuery = 'business'; searchUnsplash()" class="vbp-tag">business</button>
                            <button type="button" @click="unsplashQuery = 'technology'; searchUnsplash()" class="vbp-tag">technology</button>
                            <button type="button" @click="unsplashQuery = 'food'; searchUnsplash()" class="vbp-tag">food</button>
                            <button type="button" @click="unsplashQuery = 'architecture'; searchUnsplash()" class="vbp-tag">architecture</button>
                        </div>
                    </div>

                    <!-- Cargando -->
                    <div x-show="isSearchingUnsplash" class="vbp-unsplash-loading">
                        <div class="vbp-loading-spinner"></div>
                        <p><?php esc_html_e( 'Buscando imágenes...', 'flavor-chat-ia' ); ?></p>
                    </div>

                    <!-- Sin resultados -->
                    <div x-show="!isSearchingUnsplash && unsplashQuery && unsplashImages.length === 0" class="vbp-unsplash-no-results">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/><path d="M8 8l6 6M14 8l-6 6"/></svg>
                        <p><?php esc_html_e( 'No se encontraron imágenes para tu búsqueda.', 'flavor-chat-ia' ); ?></p>
                    </div>

                    <!-- Grid de resultados -->
                    <div x-show="!isSearchingUnsplash && unsplashImages.length > 0" class="vbp-unsplash-grid">
                        <template x-for="image in unsplashImages" :key="image.id">
                            <div class="vbp-unsplash-item" @click="selectUnsplashImage(image)">
                                <img :src="image.urls.small" :alt="image.description" loading="lazy">
                                <div class="vbp-unsplash-item-overlay">
                                    <span class="vbp-unsplash-author">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <span x-text="image.user.name"></span>
                                    </span>
                                    <button type="button" class="vbp-btn vbp-btn-sm vbp-btn-light">
                                        <?php esc_html_e( 'Insertar', 'flavor-chat-ia' ); ?>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Paginación -->
                    <div x-show="unsplashTotalPages > 1" class="vbp-unsplash-pagination">
                        <button type="button" @click="unsplashPrevPage()" :disabled="unsplashPage <= 1" class="vbp-btn vbp-btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"/></svg>
                            <?php esc_html_e( 'Anterior', 'flavor-chat-ia' ); ?>
                        </button>
                        <span class="vbp-pagination-info">
                            <span x-text="unsplashPage"></span> / <span x-text="unsplashTotalPages"></span>
                        </span>
                        <button type="button" @click="unsplashNextPage()" :disabled="unsplashPage >= unsplashTotalPages" class="vbp-btn vbp-btn-secondary">
                            <?php esc_html_e( 'Siguiente', 'flavor-chat-ia' ); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="vbp-modal-footer vbp-unsplash-footer">
                <span class="vbp-unsplash-attribution-note">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7.5 6.75V0h9v6.75h-9zm9 3.75H24V24H0V10.5h7.5v6.75h9V10.5z"/></svg>
                    <?php esc_html_e( 'Imágenes proporcionadas por Unsplash', 'flavor-chat-ia' ); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Modal de Exportar -->
    <div class="vbp-modal-overlay" x-show="showExportModal" x-cloak @click.self="showExportModal = false" @keydown.escape.window="showExportModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-export-modal-title">
        <div class="vbp-modal vbp-modal-export">
            <div class="vbp-modal-header">
                <h2 id="vbp-export-modal-title"><?php esc_html_e( 'Exportar Diseño', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showExportModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <div class="vbp-export-options">
                    <div class="vbp-export-option" @click="exportAsJson()">
                        <div class="vbp-export-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><path d="M12 18v-6M9 15l3 3 3-3"/></svg>
                        </div>
                        <h4><?php esc_html_e( 'Descargar JSON', 'flavor-chat-ia' ); ?></h4>
                        <p><?php esc_html_e( 'Exporta el diseño completo como archivo JSON', 'flavor-chat-ia' ); ?></p>
                    </div>
                    <div class="vbp-export-option" @click="copyJsonToClipboard()">
                        <div class="vbp-export-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </div>
                        <h4><?php esc_html_e( 'Copiar al portapapeles', 'flavor-chat-ia' ); ?></h4>
                        <p><?php esc_html_e( 'Copia el JSON para pegarlo en otro lugar', 'flavor-chat-ia' ); ?></p>
                    </div>
                    <div class="vbp-export-option" @click="exportAsHtml()">
                        <div class="vbp-export-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>
                        </div>
                        <h4><?php esc_html_e( 'Exportar HTML', 'flavor-chat-ia' ); ?></h4>
                        <p><?php esc_html_e( 'Descarga el diseño como archivo HTML estático', 'flavor-chat-ia' ); ?></p>
                    </div>
                </div>

                <div class="vbp-export-preview">
                    <h4><?php esc_html_e( 'Vista previa del JSON', 'flavor-chat-ia' ); ?></h4>
                    <pre class="vbp-json-preview" x-text="getExportJson()"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Guardar como Template -->
    <div class="vbp-modal-overlay" x-show="showSaveTemplateModal" x-cloak @click.self="showSaveTemplateModal = false" @keydown.escape.window="showSaveTemplateModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-save-template-modal-title">
        <div class="vbp-modal vbp-modal-save-template">
            <div class="vbp-modal-header">
                <h2 id="vbp-save-template-modal-title"><?php esc_html_e( 'Guardar como Template', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showSaveTemplateModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <div class="vbp-form-group">
                    <label><?php esc_html_e( 'Nombre del template', 'flavor-chat-ia' ); ?></label>
                    <input type="text" x-model="newTemplateName" placeholder="<?php esc_attr_e( 'Ej: Landing de producto', 'flavor-chat-ia' ); ?>" class="vbp-input">
                </div>
                <div class="vbp-form-group">
                    <label><?php esc_html_e( 'Categoría', 'flavor-chat-ia' ); ?></label>
                    <select x-model="newTemplateCategory" class="vbp-select">
                        <option value="landing"><?php esc_html_e( 'Landing Page', 'flavor-chat-ia' ); ?></option>
                        <option value="portfolio"><?php esc_html_e( 'Portfolio', 'flavor-chat-ia' ); ?></option>
                        <option value="business"><?php esc_html_e( 'Negocios', 'flavor-chat-ia' ); ?></option>
                        <option value="ecommerce"><?php esc_html_e( 'E-commerce', 'flavor-chat-ia' ); ?></option>
                        <option value="blog"><?php esc_html_e( 'Blog', 'flavor-chat-ia' ); ?></option>
                        <option value="other"><?php esc_html_e( 'Otro', 'flavor-chat-ia' ); ?></option>
                    </select>
                </div>
                <div class="vbp-form-group">
                    <label><?php esc_html_e( 'Descripción (opcional)', 'flavor-chat-ia' ); ?></label>
                    <textarea x-model="newTemplateDescription" placeholder="<?php esc_attr_e( 'Breve descripción del template...', 'flavor-chat-ia' ); ?>" class="vbp-textarea" rows="3"></textarea>
                </div>
            </div>
            <div class="vbp-modal-footer">
                <button type="button" @click="showSaveTemplateModal = false" class="vbp-btn vbp-btn-secondary"><?php esc_html_e( 'Cancelar', 'flavor-chat-ia' ); ?></button>
                <button type="button" @click="confirmSaveTemplate()" class="vbp-btn vbp-btn-primary" :disabled="!newTemplateName.trim() || isSavingTemplate">
                    <span x-show="!isSavingTemplate"><?php esc_html_e( 'Guardar Template', 'flavor-chat-ia' ); ?></span>
                    <span x-show="isSavingTemplate"><?php esc_html_e( 'Guardando...', 'flavor-chat-ia' ); ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Guardar como Widget Global -->
    <div class="vbp-modal-overlay" x-show="showSaveGlobalWidgetModal" x-cloak @click.self="showSaveGlobalWidgetModal = false" @keydown.escape.window="showSaveGlobalWidgetModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-save-global-widget-modal-title">
        <div class="vbp-modal vbp-modal-save-global-widget">
            <div class="vbp-modal-header">
                <h2 id="vbp-save-global-widget-modal-title"><?php esc_html_e( 'Guardar como Widget Global', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showSaveGlobalWidgetModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <p class="vbp-modal-description"><?php esc_html_e( 'Los widgets globales son elementos reutilizables que puedes insertar en cualquier landing. Al actualizar un widget global, todas las instancias se actualizarán automáticamente.', 'flavor-chat-ia' ); ?></p>
                <div class="vbp-form-group">
                    <label><?php esc_html_e( 'Nombre del widget', 'flavor-chat-ia' ); ?></label>
                    <input type="text" x-model="newGlobalWidgetName" placeholder="<?php esc_attr_e( 'Ej: Header principal, Footer contacto', 'flavor-chat-ia' ); ?>" class="vbp-input">
                </div>
                <div class="vbp-form-group">
                    <label><?php esc_html_e( 'Categoría', 'flavor-chat-ia' ); ?></label>
                    <select x-model="newGlobalWidgetCategory" class="vbp-select">
                        <option value="general"><?php esc_html_e( 'General', 'flavor-chat-ia' ); ?></option>
                        <option value="header"><?php esc_html_e( 'Headers', 'flavor-chat-ia' ); ?></option>
                        <option value="footer"><?php esc_html_e( 'Footers', 'flavor-chat-ia' ); ?></option>
                        <option value="cta"><?php esc_html_e( 'CTAs', 'flavor-chat-ia' ); ?></option>
                        <option value="navigation"><?php esc_html_e( 'Navegación', 'flavor-chat-ia' ); ?></option>
                        <option value="forms"><?php esc_html_e( 'Formularios', 'flavor-chat-ia' ); ?></option>
                        <option value="social"><?php esc_html_e( 'Social', 'flavor-chat-ia' ); ?></option>
                    </select>
                </div>
            </div>
            <div class="vbp-modal-footer">
                <button type="button" @click="showSaveGlobalWidgetModal = false" class="vbp-btn vbp-btn-secondary"><?php esc_html_e( 'Cancelar', 'flavor-chat-ia' ); ?></button>
                <button type="button" @click="confirmSaveGlobalWidget()" class="vbp-btn vbp-btn-primary" :disabled="!newGlobalWidgetName.trim() || isSavingGlobalWidget">
                    <span x-show="!isSavingGlobalWidget"><?php esc_html_e( 'Guardar Widget', 'flavor-chat-ia' ); ?></span>
                    <span x-show="isSavingGlobalWidget"><?php esc_html_e( 'Guardando...', 'flavor-chat-ia' ); ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Historial de Versiones -->
    <div class="vbp-modal-overlay" x-show="showVersionHistoryModal" x-cloak @click.self="showVersionHistoryModal = false" @keydown.escape.window="showVersionHistoryModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-version-history-modal-title">
        <div class="vbp-modal vbp-modal-version-history" style="max-width: 900px;">
            <div class="vbp-modal-header">
                <h2 id="vbp-version-history-modal-title"><?php esc_html_e( 'Historial de Versiones', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showVersionHistoryModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <!-- Crear snapshot manual -->
                <div class="vbp-version-create" style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; display: flex; gap: 12px; align-items: center;">
                    <input type="text" x-model="newVersionLabel" placeholder="<?php esc_attr_e( 'Etiqueta opcional (ej: Antes de cambios)', 'flavor-chat-ia' ); ?>" class="vbp-input" style="flex: 1;">
                    <button type="button" @click="createVersionSnapshot()" class="vbp-btn vbp-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
                        <?php esc_html_e( 'Guardar Versión', 'flavor-chat-ia' ); ?>
                    </button>
                </div>

                <!-- Comparar versiones -->
                <div x-show="selectedVersionA || selectedVersionB" class="vbp-version-compare-bar" style="margin-bottom: 16px; padding: 12px; background: #eff6ff; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span style="color: #3b82f6; font-weight: 500;"><?php esc_html_e( 'Comparar:', 'flavor-chat-ia' ); ?></span>
                        <span x-show="selectedVersionA" class="vbp-version-chip" style="padding: 4px 8px; background: #dbeafe; border-radius: 4px; font-size: 13px;">
                            V<span x-text="selectedVersionA?.version_number"></span>
                        </span>
                        <span x-show="selectedVersionA && selectedVersionB">vs</span>
                        <span x-show="selectedVersionB" class="vbp-version-chip" style="padding: 4px 8px; background: #dbeafe; border-radius: 4px; font-size: 13px;">
                            V<span x-text="selectedVersionB?.version_number"></span>
                        </span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" @click="compareVersions()" :disabled="!selectedVersionA || !selectedVersionB" class="vbp-btn vbp-btn-sm vbp-btn-primary">
                            <?php esc_html_e( 'Ver Diferencias', 'flavor-chat-ia' ); ?>
                        </button>
                        <button type="button" @click="selectedVersionA = null; selectedVersionB = null" class="vbp-btn vbp-btn-sm vbp-btn-secondary">
                            <?php esc_html_e( 'Limpiar', 'flavor-chat-ia' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Lista de versiones -->
                <div class="vbp-versions-list" style="max-height: 400px; overflow-y: auto;">
                    <div x-show="isLoadingVersions" style="padding: 40px; text-align: center;">
                        <div class="vbp-spinner"></div>
                        <p style="margin-top: 12px; color: #6b7280;"><?php esc_html_e( 'Cargando versiones...', 'flavor-chat-ia' ); ?></p>
                    </div>

                    <div x-show="!isLoadingVersions && versions.length === 0" style="padding: 40px; text-align: center; color: #6b7280;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 16px; opacity: 0.5;"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                        <p><?php esc_html_e( 'No hay versiones guardadas todavía', 'flavor-chat-ia' ); ?></p>
                    </div>

                    <template x-for="version in versions" :key="version.id">
                        <div class="vbp-version-item" style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; transition: all 0.2s;" :class="{ 'selected': selectedVersionA?.id === version.id || selectedVersionB?.id === version.id }">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <span style="font-weight: 600; color: #1f2937;">V<span x-text="version.version_number"></span></span>
                                        <span x-show="version.label" style="padding: 2px 8px; background: #f3f4f6; border-radius: 4px; font-size: 12px; color: #6b7280;" x-text="version.label"></span>
                                    </div>
                                    <div style="font-size: 13px; color: #6b7280;">
                                        <span x-text="version.autor_nombre"></span> · <span x-text="version.fecha_formateada"></span>
                                    </div>
                                    <div x-show="version.resumen" style="margin-top: 8px; font-size: 12px; color: #9ca3af;">
                                        <span x-text="version.resumen?.total_elementos || 0"></span> <?php esc_html_e( 'elementos', 'flavor-chat-ia' ); ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" @click="selectVersionForCompare(version, selectedVersionA ? 'B' : 'A')" class="vbp-btn vbp-btn-xs vbp-btn-outline" title="<?php esc_attr_e( 'Seleccionar para comparar', 'flavor-chat-ia' ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
                                    </button>
                                    <button type="button" @click="restoreVersion(version)" :disabled="isRestoringVersion" class="vbp-btn vbp-btn-xs vbp-btn-primary" title="<?php esc_attr_e( 'Restaurar esta versión', 'flavor-chat-ia' ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                    <button type="button" @click="deleteVersion(version)" class="vbp-btn vbp-btn-xs vbp-btn-danger" title="<?php esc_attr_e( 'Eliminar versión', 'flavor-chat-ia' ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="vbp-modal-footer">
                <button type="button" @click="showVersionHistoryModal = false" class="vbp-btn vbp-btn-secondary"><?php esc_html_e( 'Cerrar', 'flavor-chat-ia' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Modal de Diff de Versiones -->
    <div class="vbp-modal-overlay" x-show="showVersionDiffModal" x-cloak @click.self="showVersionDiffModal = false" @keydown.escape.window="showVersionDiffModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-version-diff-modal-title">
        <div class="vbp-modal vbp-modal-version-diff" style="max-width: 1000px; max-height: 90vh;">
            <div class="vbp-modal-header">
                <h2 id="vbp-version-diff-modal-title"><?php esc_html_e( 'Comparación de Versiones', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showVersionDiffModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content" style="max-height: calc(90vh - 140px); overflow-y: auto;">
                <template x-if="versionDiff">
                    <div>
                        <!-- Estadísticas -->
                        <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                            <div style="flex: 1; padding: 16px; background: #ecfdf5; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #059669;" x-text="versionDiff.estadisticas?.elementos_agregados || 0"></div>
                                <div style="font-size: 13px; color: #047857;"><?php esc_html_e( 'Añadidos', 'flavor-chat-ia' ); ?></div>
                            </div>
                            <div style="flex: 1; padding: 16px; background: #fef2f2; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #dc2626;" x-text="versionDiff.estadisticas?.elementos_eliminados || 0"></div>
                                <div style="font-size: 13px; color: #b91c1c;"><?php esc_html_e( 'Eliminados', 'flavor-chat-ia' ); ?></div>
                            </div>
                            <div style="flex: 1; padding: 16px; background: #fefce8; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #ca8a04;" x-text="versionDiff.estadisticas?.elementos_modificados || 0"></div>
                                <div style="font-size: 13px; color: #a16207;"><?php esc_html_e( 'Modificados', 'flavor-chat-ia' ); ?></div>
                            </div>
                        </div>

                        <!-- Lista de cambios -->
                        <div class="vbp-diff-changes">
                            <template x-for="change in versionDiff.diff?.changes || []" :key="change.id">
                                <div class="vbp-diff-item" :class="getDiffChangeTypeClass(change.type)" style="padding: 12px; border-radius: 8px; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="vbp-diff-badge" :class="'vbp-diff-badge--' + change.type" style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;" x-text="getDiffChangeTypeLabel(change.type)"></span>
                                        <span style="font-weight: 500;" x-text="change.elemento?.type || change.elemento_b?.type || 'Elemento'"></span>
                                        <span style="color: #6b7280; font-size: 13px;" x-text="'ID: ' + change.id"></span>
                                    </div>
                                    <template x-if="change.cambios && change.cambios.length > 0">
                                        <div style="margin-top: 8px; padding-left: 16px; font-size: 13px;">
                                            <template x-for="cambio in change.cambios" :key="cambio.property">
                                                <div style="display: flex; gap: 8px; padding: 4px 0; border-bottom: 1px dashed #e5e7eb;">
                                                    <span style="font-weight: 500; color: #374151;" x-text="cambio.property + ':'"></span>
                                                    <span style="color: #dc2626; text-decoration: line-through;" x-text="JSON.stringify(cambio.old_value)"></span>
                                                    <span>→</span>
                                                    <span style="color: #059669;" x-text="JSON.stringify(cambio.new_value)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="vbp-modal-footer">
                <button type="button" @click="showVersionDiffModal = false" class="vbp-btn vbp-btn-secondary"><?php esc_html_e( 'Cerrar', 'flavor-chat-ia' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Modal de Configuración de Página -->
    <div class="vbp-modal-overlay" x-show="showPageSettings" x-cloak @click.self="showPageSettings = false" @keydown.escape.window="showPageSettings = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-page-settings-modal-title">
        <div class="vbp-modal vbp-modal-page-settings">
            <div class="vbp-modal-header">
                <h2 id="vbp-page-settings-modal-title"><?php esc_html_e( 'Configuración de Página', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showPageSettings = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <!-- Tabs de configuración -->
                <div class="vbp-page-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Secciones de configuración', 'flavor-chat-ia' ); ?>">
                    <button type="button" @click="pageSettingsTab = 'general'" :class="{ 'active': pageSettingsTab === 'general' || !pageSettingsTab }" class="vbp-settings-tab" role="tab" :aria-selected="pageSettingsTab === 'general' || !pageSettingsTab" aria-controls="vbp-settings-general" id="vbp-settings-tab-general">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>
                        <?php esc_html_e( 'General', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" @click="pageSettingsTab = 'seo'" :class="{ 'active': pageSettingsTab === 'seo' }" class="vbp-settings-tab" role="tab" :aria-selected="pageSettingsTab === 'seo'" aria-controls="vbp-settings-seo" id="vbp-settings-tab-seo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <?php esc_html_e( 'SEO', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" @click="pageSettingsTab = 'social'" :class="{ 'active': pageSettingsTab === 'social' }" class="vbp-settings-tab" role="tab" :aria-selected="pageSettingsTab === 'social'" aria-controls="vbp-settings-social" id="vbp-settings-tab-social">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                        <?php esc_html_e( 'Social', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" @click="pageSettingsTab = 'code'" :class="{ 'active': pageSettingsTab === 'code' }" class="vbp-settings-tab" role="tab" :aria-selected="pageSettingsTab === 'code'" aria-controls="vbp-settings-code" id="vbp-settings-tab-code">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>
                        <?php esc_html_e( 'Código', 'flavor-chat-ia' ); ?>
                    </button>
                </div>

                <!-- Tab: General -->
                <div class="vbp-page-settings-content" x-show="pageSettingsTab === 'general' || !pageSettingsTab" role="tabpanel" id="vbp-settings-general" aria-labelledby="vbp-settings-tab-general">
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Ancho de página', 'flavor-chat-ia' ); ?></label>
                        <div class="vbp-input-with-unit">
                            <input
                                type="text"
                                x-model="$store.vbp.settings.pageWidth"
                                class="vbp-input"
                                placeholder="1200px o 100%"
                            >
                            <select x-model="$store.vbp.settings.pageWidthUnit" class="vbp-select-unit" @change="updatePageWidthUnit()">
                                <option value="px">px</option>
                                <option value="%">%</option>
                            </select>
                        </div>
                        <small class="vbp-field-hint"><?php esc_html_e( 'Usa px para ancho fijo o % para ancho relativo', 'flavor-chat-ia' ); ?></small>
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Color de fondo', 'flavor-chat-ia' ); ?></label>
                        <div class="vbp-color-input-wrapper">
                            <input type="color" x-model="$store.vbp.settings.backgroundColor" class="vbp-color-input">
                            <input type="text" x-model="$store.vbp.settings.backgroundColor" class="vbp-input" placeholder="#ffffff">
                        </div>
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Clase CSS de página', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="pageSettings.pageClass" class="vbp-input" placeholder="mi-landing-page">
                        <small class="vbp-field-hint"><?php esc_html_e( 'Añade clases CSS personalizadas al body', 'flavor-chat-ia' ); ?></small>
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'ID de página', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="pageSettings.pageId" class="vbp-input" placeholder="landing-producto">
                    </div>
                </div>

                <!-- Tab: SEO -->
                <div class="vbp-page-settings-content" x-show="pageSettingsTab === 'seo'" role="tabpanel" id="vbp-settings-seo" aria-labelledby="vbp-settings-tab-seo">
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Título SEO', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="pageSettings.seoTitle" class="vbp-input" placeholder="Título para motores de búsqueda">
                        <div class="vbp-char-counter">
                            <span x-text="(pageSettings.seoTitle || '').length"></span>/60
                        </div>
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Meta descripción', 'flavor-chat-ia' ); ?></label>
                        <textarea x-model="pageSettings.seoDescription" class="vbp-textarea" rows="3" placeholder="Descripción para motores de búsqueda"></textarea>
                        <div class="vbp-char-counter">
                            <span x-text="(pageSettings.seoDescription || '').length"></span>/160
                        </div>
                    </div>
                    <div class="vbp-seo-preview">
                        <h4><?php esc_html_e( 'Vista previa en Google', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-google-preview">
                            <div class="vbp-google-title" x-text="pageSettings.seoTitle || documentTitle || 'Título de la página'"></div>
                            <div class="vbp-google-url"><?php echo esc_url( get_site_url() ); ?>/...</div>
                            <div class="vbp-google-description" x-text="pageSettings.seoDescription || 'La descripción de la página aparecerá aquí...'"></div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Social (Open Graph) -->
                <div class="vbp-page-settings-content" x-show="pageSettingsTab === 'social'" role="tabpanel" id="vbp-settings-social" aria-labelledby="vbp-settings-tab-social">
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Título para redes sociales', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="pageSettings.ogTitle" class="vbp-input" placeholder="Título cuando se comparte en redes">
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Descripción para redes sociales', 'flavor-chat-ia' ); ?></label>
                        <textarea x-model="pageSettings.ogDescription" class="vbp-textarea" rows="3" placeholder="Descripción cuando se comparte"></textarea>
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'Imagen para redes sociales', 'flavor-chat-ia' ); ?></label>
                        <div class="vbp-image-upload">
                            <template x-if="pageSettings.ogImage">
                                <div class="vbp-og-image-preview">
                                    <img :src="pageSettings.ogImage" alt="OG Image">
                                    <button type="button" @click="pageSettings.ogImage = ''" class="vbp-remove-image">✕</button>
                                </div>
                            </template>
                            <template x-if="!pageSettings.ogImage">
                                <div class="vbp-og-image-placeholder" @click="selectOgImage()">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                    <span><?php esc_html_e( 'Seleccionar imagen (1200x630px recomendado)', 'flavor-chat-ia' ); ?></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="vbp-social-preview">
                        <h4><?php esc_html_e( 'Vista previa en redes sociales', 'flavor-chat-ia' ); ?></h4>
                        <div class="vbp-facebook-preview">
                            <div class="vbp-fb-image" :style="pageSettings.ogImage ? 'background-image: url(' + pageSettings.ogImage + ')' : ''">
                                <template x-if="!pageSettings.ogImage">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                </template>
                            </div>
                            <div class="vbp-fb-content">
                                <div class="vbp-fb-domain"><?php echo esc_html( wp_parse_url( get_site_url(), PHP_URL_HOST ) ); ?></div>
                                <div class="vbp-fb-title" x-text="pageSettings.ogTitle || pageSettings.seoTitle || documentTitle || 'Título'"></div>
                                <div class="vbp-fb-description" x-text="pageSettings.ogDescription || pageSettings.seoDescription || 'Descripción de la página'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Código personalizado -->
                <div class="vbp-page-settings-content" x-show="pageSettingsTab === 'code'" role="tabpanel" id="vbp-settings-code" aria-labelledby="vbp-settings-tab-code">
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'CSS personalizado', 'flavor-chat-ia' ); ?></label>
                        <textarea x-model="pageSettings.customCss" class="vbp-textarea vbp-code-textarea" rows="8" placeholder="/* Tu CSS aquí */&#10;.mi-clase {&#10;    color: red;&#10;}"></textarea>
                        <small class="vbp-field-hint"><?php esc_html_e( 'Este CSS se aplicará solo a esta página', 'flavor-chat-ia' ); ?></small>
                    </div>
                    <div class="vbp-form-group">
                        <label><?php esc_html_e( 'JavaScript personalizado', 'flavor-chat-ia' ); ?></label>
                        <textarea x-model="pageSettings.customJs" class="vbp-textarea vbp-code-textarea" rows="6" placeholder="// Tu JavaScript aquí&#10;console.log('Página cargada');"></textarea>
                        <small class="vbp-field-hint"><?php esc_html_e( 'Se ejecutará al cargar la página', 'flavor-chat-ia' ); ?></small>
                    </div>
                </div>
            </div>
            <div class="vbp-modal-footer">
                <button type="button" @click="showPageSettings = false" class="vbp-btn vbp-btn-secondary"><?php esc_html_e( 'Cancelar', 'flavor-chat-ia' ); ?></button>
                <button type="button" @click="savePageSettings()" class="vbp-btn vbp-btn-primary"><?php esc_html_e( 'Guardar configuración', 'flavor-chat-ia' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Modal de Revisiones -->
    <div class="vbp-modal-overlay" x-show="showRevisionsModal" x-cloak @click.self="showRevisionsModal = false" @keydown.escape.window="showRevisionsModal = false" x-transition role="dialog" aria-modal="true" aria-labelledby="vbp-revisions-modal-title">
        <div class="vbp-modal vbp-modal-revisions">
            <div class="vbp-modal-header">
                <h2 id="vbp-revisions-modal-title"><?php esc_html_e( 'Historial de Revisiones', 'flavor-chat-ia' ); ?></h2>
                <button type="button" @click="showRevisionsModal = false" class="vbp-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="vbp-modal-content">
                <div class="vbp-revisions-info">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <p><?php esc_html_e( 'Las revisiones se crean automáticamente cada vez que guardas el documento.', 'flavor-chat-ia' ); ?></p>
                </div>

                <div class="vbp-revisions-loading" x-show="isLoadingRevisions">
                    <div class="vbp-spinner"></div>
                    <span><?php esc_html_e( 'Cargando revisiones...', 'flavor-chat-ia' ); ?></span>
                </div>

                <div class="vbp-revisions-list" x-show="!isLoadingRevisions && revisions.length > 0">
                    <template x-for="revision in revisions" :key="revision.id">
                        <div class="vbp-revision-item" :class="{ 'current': revision.isCurrent }">
                            <div class="vbp-revision-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            </div>
                            <div class="vbp-revision-details">
                                <span class="vbp-revision-date" x-text="formatRevisionDate(revision.date)"></span>
                                <span class="vbp-revision-author" x-text="revision.author"></span>
                            </div>
                            <div class="vbp-revision-actions">
                                <template x-if="revision.isCurrent">
                                    <span class="vbp-revision-badge"><?php esc_html_e( 'Actual', 'flavor-chat-ia' ); ?></span>
                                </template>
                                <template x-if="!revision.isCurrent">
                                    <button type="button" @click="restoreRevision(revision)" class="vbp-btn vbp-btn-sm vbp-btn-secondary" :disabled="isRestoringRevision">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        <?php esc_html_e( 'Restaurar', 'flavor-chat-ia' ); ?>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="vbp-revisions-empty" x-show="!isLoadingRevisions && revisions.length === 0">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <p><?php esc_html_e( 'No hay revisiones disponibles aún.', 'flavor-chat-ia' ); ?></p>
                    <p class="vbp-revisions-hint"><?php esc_html_e( 'Las revisiones aparecerán después de guardar el documento.', 'flavor-chat-ia' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini mapa de navegación -->
    <?php include __DIR__ . '/panel-minimap.php'; ?>

    <!-- Barra de estado inferior -->
    <?php include __DIR__ . '/panel-statusbar.php'; ?>

    <!-- Modales de selectores avanzados -->
    <?php include __DIR__ . '/modals/modal-icons.php'; ?>
    <?php include __DIR__ . '/modals/modal-emoji.php'; ?>
    <?php include __DIR__ . '/modals/modal-command-palette.php'; ?>
    <?php include __DIR__ . '/modals/modal-ai-assistant.php'; ?>
    <?php include __DIR__ . '/modals/modal-comments.php'; ?>

    <!-- Contenedor de notificaciones Toast -->
    <div class="vbp-toast-container" x-data="vbpToastContainer()" aria-live="polite" aria-label="<?php esc_attr_e( 'Notificaciones', 'flavor-chat-ia' ); ?>">
        <template x-for="notification in notifications" :key="notification.id">
            <div class="vbp-toast" :class="getTypeClass(notification.type)" role="status">
                <span class="vbp-toast-icon" x-text="notification.icon" aria-hidden="true"></span>
                <div class="vbp-toast-content">
                    <span class="vbp-toast-message" x-text="notification.message"></span>
                </div>
                <div class="vbp-toast-actions" x-show="notification.action">
                    <button type="button" class="vbp-toast-action" @click="executeAction(notification)" x-text="notification.actionLabel" x-show="notification.actionLabel"></button>
                </div>
                <button type="button" class="vbp-toast-dismiss" @click="dismiss(notification.id)" x-show="notification.dismissible" aria-label="<?php esc_attr_e( 'Cerrar', 'flavor-chat-ia' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <div class="vbp-toast-progress" x-show="notification.duration > 0">
                    <div class="vbp-toast-progress-bar" :style="'--duration: ' + notification.duration + 'ms'"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Mobile Navigation -->
    <div class="vbp-mobile-overlay" @click="closeMobileSidebars()"></div>

    <button type="button" class="vbp-mobile-fab vbp-mobile-fab--blocks" @click="toggleMobileSidebar('left')" aria-label="<?php esc_attr_e( 'Bloques', 'flavor-chat-ia' ); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"/>
            <rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/>
        </svg>
    </button>

    <button type="button" class="vbp-mobile-fab vbp-mobile-fab--inspector" @click="toggleMobileSidebar('right')" aria-label="<?php esc_attr_e( 'Inspector', 'flavor-chat-ia' ); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 20V10"/>
            <path d="M18 20V4"/>
            <path d="M6 20v-4"/>
        </svg>
    </button>

    <?php wp_footer(); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('beforeunload', function(e) {
            if (Alpine.store('vbp') && Alpine.store('vbp').isDirty) {
                e.preventDefault();
                e.returnValue = VBP_Config.strings.unsavedChanges;
                return VBP_Config.strings.unsavedChanges;
            }
        });
    });

    // Mobile sidebar handling
    window.toggleMobileSidebar = function(side) {
        var overlay = document.querySelector('.vbp-mobile-overlay');
        var leftSidebar = document.querySelector('.vbp-sidebar-left');
        var rightSidebar = document.querySelector('.vbp-sidebar-right');

        if (side === 'left') {
            leftSidebar.classList.toggle('mobile-open');
            rightSidebar.classList.remove('mobile-open');
        } else {
            rightSidebar.classList.toggle('mobile-open');
            leftSidebar.classList.remove('mobile-open');
        }

        var isOpen = leftSidebar.classList.contains('mobile-open') || rightSidebar.classList.contains('mobile-open');
        overlay.classList.toggle('active', isOpen);
        document.body.classList.toggle('vbp-mobile-sidebar-open', isOpen);
    };

    window.closeMobileSidebars = function() {
        var overlay = document.querySelector('.vbp-mobile-overlay');
        var leftSidebar = document.querySelector('.vbp-sidebar-left');
        var rightSidebar = document.querySelector('.vbp-sidebar-right');

        leftSidebar.classList.remove('mobile-open');
        rightSidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.classList.remove('vbp-mobile-sidebar-open');
    };
    </script>
</body>
</html>
