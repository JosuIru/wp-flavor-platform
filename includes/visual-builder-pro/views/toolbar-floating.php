<?php
/**
 * Visual Builder Pro - Toolbar Flotante
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div
    class="vbp-floating-toolbar"
    x-show="showFloatingToolbar"
    :style="{ left: floatingToolbarPosition.x + 'px', top: floatingToolbarPosition.y + 'px' }"
    x-transition
    @mousedown.stop
>
    <!-- Formato de texto -->
    <div class="vbp-toolbar-group">
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('bold')"
            :class="{ 'active': window.vbpTextEditor.isFormatActive('bold') }"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Negrita (Ctrl+B)', 'flavor-chat-ia' ); ?>"
        >
            <strong>B</strong>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('italic')"
            :class="{ 'active': window.vbpTextEditor.isFormatActive('italic') }"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Cursiva (Ctrl+I)', 'flavor-chat-ia' ); ?>"
        >
            <em>I</em>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('underline')"
            :class="{ 'active': window.vbpTextEditor.isFormatActive('underline') }"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Subrayado (Ctrl+U)', 'flavor-chat-ia' ); ?>"
        >
            <u>U</u>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('strikeThrough')"
            :class="{ 'active': window.vbpTextEditor.isFormatActive('strikeThrough') }"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Tachado', 'flavor-chat-ia' ); ?>"
        >
            <s>S</s>
        </button>
    </div>

    <div class="vbp-toolbar-divider"></div>

    <!-- Enlaces -->
    <div class="vbp-toolbar-group">
        <button
            type="button"
            @click="window.vbpTextEditor.insertLink()"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Insertar enlace (Ctrl+K)', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
            </svg>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.removeLink()"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Eliminar enlace', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
                <line x1="4" y1="4" x2="20" y2="20"/>
            </svg>
        </button>
    </div>

    <div class="vbp-toolbar-divider"></div>

    <!-- Headings -->
    <div class="vbp-toolbar-group">
        <select @change="window.vbpTextEditor.formatHeading($event.target.value)" class="vbp-toolbar-select">
            <option value="p"><?php esc_html_e( 'Párrafo', 'flavor-chat-ia' ); ?></option>
            <option value="h1">H1</option>
            <option value="h2">H2</option>
            <option value="h3">H3</option>
            <option value="h4">H4</option>
            <option value="h5">H5</option>
            <option value="h6">H6</option>
        </select>
    </div>

    <div class="vbp-toolbar-divider"></div>

    <!-- Listas -->
    <div class="vbp-toolbar-group">
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('insertUnorderedList')"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Lista con viñetas', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"/>
                <line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/>
                <circle cx="4" cy="6" r="1"/>
                <circle cx="4" cy="12" r="1"/>
                <circle cx="4" cy="18" r="1"/>
            </svg>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('insertOrderedList')"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Lista numerada', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="10" y1="6" x2="21" y2="6"/>
                <line x1="10" y1="12" x2="21" y2="12"/>
                <line x1="10" y1="18" x2="21" y2="18"/>
                <path d="M4 6h1v4H4zM4 10h2"/>
                <path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>
            </svg>
        </button>
    </div>

    <div class="vbp-toolbar-divider"></div>

    <!-- Alineación -->
    <div class="vbp-toolbar-group">
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('justifyLeft')"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Alinear izquierda', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="15" y2="12"/>
                <line x1="3" y1="18" x2="18" y2="18"/>
            </svg>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('justifyCenter')"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Centrar', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="6" y1="12" x2="18" y2="12"/>
                <line x1="4" y1="18" x2="20" y2="18"/>
            </svg>
        </button>
        <button
            type="button"
            @click="window.vbpTextEditor.formatText('justifyRight')"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Alinear derecha', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="9" y1="12" x2="21" y2="12"/>
                <line x1="6" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>

    <div class="vbp-toolbar-divider"></div>

    <!-- Limpiar formato -->
    <div class="vbp-toolbar-group">
        <button
            type="button"
            @click="window.vbpTextEditor.clearFormatting()"
            class="vbp-toolbar-btn"
            title="<?php esc_attr_e( 'Limpiar formato', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16"/>
                <path d="M10 7v8"/>
                <path d="M8 15h4"/>
                <path d="M16 7v8"/>
                <path d="M3 21l18-18"/>
            </svg>
        </button>
    </div>
</div>
