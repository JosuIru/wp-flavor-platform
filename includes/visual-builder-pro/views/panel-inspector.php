<?php
/**
 * Visual Builder Pro - Panel Inspector Completo
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-inspector-container" x-data="vbpInspector()">
    <template x-if="!selectedElement">
        <div class="vbp-inspector-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01"/>
            </svg>
            <p><?php esc_html_e( 'Selecciona un elemento para editarlo', 'flavor-chat-ia' ); ?></p>
        </div>
    </template>

    <template x-if="selectedElement">
        <div class="vbp-inspector-content">
            <!-- Header con tipo de elemento -->
            <div class="vbp-inspector-header">
                <div class="vbp-inspector-header-info">
                    <span class="vbp-inspector-type" x-text="getTypeName(selectedElement.type)"></span>
                    <span class="vbp-inspector-id" x-text="selectedElement.id"></span>
                </div>
                <div class="vbp-inspector-actions">
                    <button type="button" @click="toggleVisibility()" class="vbp-btn-icon-sm" :title="selectedElement.visible ? '<?php esc_attr_e( 'Ocultar', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Mostrar', 'flavor-chat-ia' ); ?>'">
                        <svg x-show="selectedElement.visible" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="!selectedElement.visible" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                    <button type="button" @click="toggleLock()" class="vbp-btn-icon-sm" :title="selectedElement.locked ? '<?php esc_attr_e( 'Desbloquear', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Bloquear', 'flavor-chat-ia' ); ?>'">
                        <svg x-show="!selectedElement.locked" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        <svg x-show="selectedElement.locked" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 019.9-1"/></svg>
                    </button>
                </div>
            </div>

            <!-- Selector de Variantes -->
            <template x-if="hasVariants()">
                <div class="vbp-inspector-variants">
                    <label class="vbp-field-label vbp-variants-label"><?php esc_html_e( 'Variante', 'flavor-chat-ia' ); ?></label>
                    <div class="vbp-variants-grid">
                        <template x-for="variant in getVariants()" :key="variant.id">
                            <button type="button"
                                    @click="setVariant(variant.id)"
                                    :class="{ 'active': selectedElement.variant === variant.id }"
                                    class="vbp-variant-btn"
                                    :title="variant.name">
                                <span class="vbp-variant-icon" x-text="variant.icon"></span>
                                <span class="vbp-variant-name" x-text="variant.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Tabs -->
            <div class="vbp-inspector-tabs">
                <button type="button" @click="activeTab = 'content'" :class="{ 'active': activeTab === 'content' }" class="vbp-inspector-tab">
                    <?php esc_html_e( 'Contenido', 'flavor-chat-ia' ); ?>
                </button>
                <button type="button" @click="activeTab = 'styles'" :class="{ 'active': activeTab === 'styles' }" class="vbp-inspector-tab">
                    <?php esc_html_e( 'Estilos', 'flavor-chat-ia' ); ?>
                </button>
                <button type="button" @click="activeTab = 'advanced'" :class="{ 'active': activeTab === 'advanced' }" class="vbp-inspector-tab">
                    <?php esc_html_e( 'Avanzado', 'flavor-chat-ia' ); ?>
                </button>
            </div>

            <!-- ============================================ -->
            <!-- Tab: Contenido -->
            <!-- ============================================ -->
            <div x-show="activeTab === 'content'" class="vbp-inspector-panel">
                <!-- Nombre del elemento (común) -->
                <div class="vbp-field-group">
                    <label class="vbp-field-label"><?php esc_html_e( 'Nombre', 'flavor-chat-ia' ); ?></label>
                    <input type="text" x-model="selectedElement.name" @input="updateElement('name', $event.target.value)" class="vbp-field-input">
                </div>

                <!-- ========== HEADING ========== -->
                <template x-if="selectedElement.type === 'heading'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto', 'flavor-chat-ia' ); ?></label>
                            <textarea x-model="selectedElement.data.text" @input="updateElementData('text', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Nivel', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group vbp-btn-group-full">
                                <template x-for="level in ['h1','h2','h3','h4','h5','h6']">
                                    <button type="button" @click="updateElementData('level', level)" :class="{ 'active': selectedElement.data.level === level }" class="vbp-btn-toggle" x-text="level.toUpperCase()"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== TEXT ========== -->
                <template x-if="selectedElement.type === 'text'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group" x-data="vbpToolbarEditor()" x-init="content = selectedElement.data.text || ''; field = 'text'" @richtext-change.window="if ($event.detail.field === 'text') updateElementData('text', $event.detail.content)">
                            <label class="vbp-field-label"><?php esc_html_e( 'Contenido', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-richtext-wrapper" :class="{ 'focused': isFocused }" style="position: relative;">
                                <!-- Toolbar fija -->
                                <div class="vbp-richtext-toolbar">
                                    <button type="button" @click="toggleBold()" :class="{ 'active': isFormatActive('bold') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Negrita (Ctrl+B)', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
                                    </button>
                                    <button type="button" @click="toggleItalic()" :class="{ 'active': isFormatActive('italic') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Cursiva (Ctrl+I)', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
                                    </button>
                                    <button type="button" @click="toggleUnderline()" :class="{ 'active': isFormatActive('underline') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Subrayado (Ctrl+U)', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
                                    </button>
                                    <button type="button" @click="toggleStrike()" :class="{ 'active': isFormatActive('strikeThrough') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Tachado', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.3 4.9c-2.3-.6-4.4-1-6.2-.9-2.7 0-5.3.7-5.3 3.6 0 1.5 1.1 2.6 3.7 3.2"/><path d="M4 12h16"/><path d="M6.7 19.1c2.3.6 4.4 1 6.2.9 2.7 0 5.3-.7 5.3-3.6 0-1.5-1.1-2.6-3.7-3.2"/></svg>
                                    </button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="insertLink()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Enlace (Ctrl+K)', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                                    </button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="insertList(false)" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Lista', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                                    </button>
                                    <button type="button" @click="insertList(true)" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Lista numerada', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="3" y="7" font-size="6" fill="currentColor">1</text><text x="3" y="13" font-size="6" fill="currentColor">2</text><text x="3" y="19" font-size="6" fill="currentColor">3</text></svg>
                                    </button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="undo()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Deshacer (Ctrl+Z)', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 7"/></svg>
                                    </button>
                                    <button type="button" @click="redo()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Rehacer (Ctrl+Y)', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M21 13a9 9 0 1 1-3-7.7L21 7"/></svg>
                                    </button>
                                    <button type="button" @click="clearFormat()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Limpiar formato', 'flavor-chat-ia' ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>

                                <!-- Toolbar flotante (aparece al seleccionar texto) -->
                                <div x-show="showFloatingToolbar"
                                     x-cloak
                                     class="vbp-richtext-floating-toolbar"
                                     :style="{ top: floatingToolbarPosition.top + 'px', left: floatingToolbarPosition.left + 'px' }"
                                     @mousedown.prevent>
                                    <button type="button" @click="toggleBold()" :class="{ 'active': isFormatActive('bold') }" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg></button>
                                    <button type="button" @click="toggleItalic()" :class="{ 'active': isFormatActive('italic') }" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
                                    <button type="button" @click="toggleUnderline()" :class="{ 'active': isFormatActive('underline') }" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg></button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="insertLink()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg></button>
                                </div>

                                <!-- Área de edición -->
                                <div x-ref="editor"
                                     contenteditable="true"
                                     class="vbp-richtext-editor"
                                     data-placeholder="<?php esc_attr_e( 'Escribe aquí... Usa **negrita**, *cursiva* o Ctrl+B, Ctrl+I', 'flavor-chat-ia' ); ?>"
                                     @input="handleInput()"
                                     @keydown="handleKeydown($event)"
                                     @focus="handleFocus()"
                                     @blur="handleBlur()"
                                     @paste="handlePaste($event)"
                                     x-html="content"></div>

                                <!-- Footer con contador -->
                                <div class="vbp-richtext-footer">
                                    <span class="vbp-richtext-stats">
                                        <span x-text="getWordCount() + ' palabras'"></span>
                                    </span>
                                    <span class="vbp-richtext-shortcuts">Ctrl+B/I/U/K</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== IMAGE ========== -->
                <template x-if="selectedElement.type === 'image'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-image-preview" x-show="selectedElement.data.src">
                                <img :src="selectedElement.data.src" alt="">
                                <button type="button" @click="updateElementData('src', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('src')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                <?php esc_html_e( 'Seleccionar imagen', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto alternativo', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.alt" @input="updateElementData('alt', $event.target.value)" class="vbp-field-input" placeholder="Descripción de la imagen">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Pie de foto', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.caption" @input="updateElementData('caption', $event.target.value)" class="vbp-field-input">
                        </div>
                    </div>
                </template>

                <!-- ========== BUTTON ========== -->
                <template x-if="selectedElement.type === 'button'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.text" @input="updateElementData('text', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.url"
                                       @input="searchQuery = $event.target.value; updateElementData('url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="updateElementData('url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar o pega URL...', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading">
                                        <?php esc_html_e( 'Buscando...', 'flavor-chat-ia' ); ?>
                                    </div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', 'flavor-chat-ia' ); ?></div>
                                    </template>
                                    <template x-for="(result, idx) in results" :key="result.id">
                                        <button type="button"
                                                class="vbp-autocomplete-item"
                                                :class="{ 'active': activeIndex === idx }"
                                                @click="selectResult(result)">
                                            <span class="vbp-autocomplete-icon" x-text="result.icon"></span>
                                            <span class="vbp-autocomplete-info">
                                                <span class="vbp-autocomplete-title" x-text="result.title"></span>
                                                <span class="vbp-autocomplete-type" x-text="getTypeLabel(result.type)"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Abrir en', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.target" @change="updateElementData('target', $event.target.value)" class="vbp-field-select">
                                    <option value="_self"><?php esc_html_e( 'Misma ventana', 'flavor-chat-ia' ); ?></option>
                                    <option value="_blank"><?php esc_html_e( 'Nueva ventana', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Estilo', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.style" @change="updateElementData('style', $event.target.value)" class="vbp-field-select">
                                    <option value="filled"><?php esc_html_e( 'Relleno', 'flavor-chat-ia' ); ?></option>
                                    <option value="outline"><?php esc_html_e( 'Contorno', 'flavor-chat-ia' ); ?></option>
                                    <option value="ghost"><?php esc_html_e( 'Ghost', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('align', 'left')" :class="{ 'active': selectedElement.data.align === 'left' }" class="vbp-btn-icon"><?php esc_html_e( 'Izq', 'flavor-chat-ia' ); ?></button>
                                <button type="button" @click="updateElementData('align', 'center')" :class="{ 'active': selectedElement.data.align === 'center' }" class="vbp-btn-icon"><?php esc_html_e( 'Centro', 'flavor-chat-ia' ); ?></button>
                                <button type="button" @click="updateElementData('align', 'right')" :class="{ 'active': selectedElement.data.align === 'right' }" class="vbp-btn-icon"><?php esc_html_e( 'Der', 'flavor-chat-ia' ); ?></button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== HERO ========== -->
                <template x-if="selectedElement.type === 'hero'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpToolbarEditor()" x-init="content = selectedElement.data.subtitulo || ''; field = 'hero_subtitulo'" @richtext-change.window="if ($event.detail.field === 'hero_subtitulo') updateElementData('subtitulo', $event.detail.content)">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-richtext-wrapper compact">
                                <div class="vbp-richtext-toolbar">
                                    <button type="button" @click="toggleBold()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg></button>
                                    <button type="button" @click="toggleItalic()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
                                    <button type="button" @click="insertLink()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg></button>
                                </div>
                                <div x-ref="editor" contenteditable="true" class="vbp-richtext-editor" @input="updateContent()" @focus="handleFocus()" @blur="handleBlur()" @paste="handlePaste($event)" x-html="content"></div>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto botón', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL botón', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.boton_url"
                                       @input="searchQuery = $event.target.value; updateElementData('boton_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="updateElementData('boton_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading"><?php esc_html_e( 'Buscando...', 'flavor-chat-ia' ); ?></div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', 'flavor-chat-ia' ); ?></div>
                                    </template>
                                    <template x-for="(result, idx) in results" :key="result.id">
                                        <button type="button" class="vbp-autocomplete-item" :class="{ 'active': activeIndex === idx }" @click="selectResult(result)">
                                            <span class="vbp-autocomplete-icon" x-text="result.icon"></span>
                                            <span class="vbp-autocomplete-info">
                                                <span class="vbp-autocomplete-title" x-text="result.title"></span>
                                                <span class="vbp-autocomplete-type" x-text="getTypeLabel(result.type)"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen de fondo', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-image-preview vbp-image-preview-bg" x-show="selectedElement.data.imagen_fondo" :style="{ backgroundImage: 'url(' + selectedElement.data.imagen_fondo + ')' }">
                                <button type="button" @click="updateElementData('imagen_fondo', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('imagen_fondo')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <?php esc_html_e( 'Seleccionar imagen de fondo', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- ========== CTA ========== -->
                <template x-if="selectedElement.type === 'cta'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpToolbarEditor()" x-init="content = selectedElement.data.subtitulo || ''; field = 'cta_subtitulo'" @richtext-change.window="if ($event.detail.field === 'cta_subtitulo') updateElementData('subtitulo', $event.detail.content)">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-richtext-wrapper compact">
                                <div class="vbp-richtext-toolbar">
                                    <button type="button" @click="toggleBold()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg></button>
                                    <button type="button" @click="toggleItalic()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
                                    <button type="button" @click="insertLink()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg></button>
                                </div>
                                <div x-ref="editor" contenteditable="true" class="vbp-richtext-editor" @input="updateContent()" @focus="handleFocus()" @blur="handleBlur()" @paste="handlePaste($event)" x-html="content"></div>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto botón', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL botón', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.boton_url"
                                       @input="searchQuery = $event.target.value; updateElementData('boton_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="updateElementData('boton_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading"><?php esc_html_e( 'Buscando...', 'flavor-chat-ia' ); ?></div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', 'flavor-chat-ia' ); ?></div>
                                    </template>
                                    <template x-for="(result, idx) in results" :key="result.id">
                                        <button type="button" class="vbp-autocomplete-item" :class="{ 'active': activeIndex === idx }" @click="selectResult(result)">
                                            <span class="vbp-autocomplete-icon" x-text="result.icon"></span>
                                            <span class="vbp-autocomplete-info">
                                                <span class="vbp-autocomplete-title" x-text="result.title"></span>
                                                <span class="vbp-autocomplete-type" x-text="getTypeLabel(result.type)"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== FEATURES (con items) ========== -->
                <template x-if="selectedElement.type === 'features'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Características', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('features')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="item.icono || '✨'"></span>
                                        <span class="vbp-item-title" x-text="item.titulo || '<?php esc_attr_e( 'Característica', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === selectedElement.data.items.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', 'flavor-chat-ia' ); ?></label>
                                            <div class="vbp-field-with-selector">
                                                <input type="text" x-model="item.icono" @input="updateItem(index, 'icono', $event.target.value)" class="vbp-field-input" placeholder="home">
                                                <button type="button" @click="openIconSelectorForItem(index, 'icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', 'flavor-chat-ia' ); ?>">
                                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Descripción', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.descripcion" @input="updateItem(index, 'descripcion', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== TESTIMONIALS (con items) ========== -->
                <template x-if="selectedElement.type === 'testimonials'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Testimonios', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('testimonials')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-avatar" x-text="(item.autor || 'U').charAt(0)"></span>
                                        <span class="vbp-item-title" x-text="item.autor || '<?php esc_attr_e( 'Autor', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === selectedElement.data.items.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Foto', 'flavor-chat-ia' ); ?></label>
                                            <div class="vbp-image-preview vbp-image-preview-small vbp-image-preview-round" x-show="item.foto">
                                                <img :src="item.foto" alt="">
                                                <button type="button" @click="updateItem(index, 'foto', '')" class="vbp-image-remove" title="<?php esc_attr_e( 'Eliminar foto', 'flavor-chat-ia' ); ?>">×</button>
                                            </div>
                                            <button type="button" @click="openMediaLibraryForItem(index, 'foto')" class="vbp-btn vbp-btn-secondary vbp-btn-sm vbp-btn-block">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                                <span x-text="item.foto ? '<?php echo esc_js( __( 'Cambiar foto', 'flavor-chat-ia' ) ); ?>' : '<?php echo esc_js( __( 'Seleccionar foto', 'flavor-chat-ia' ) ); ?>'"></span>
                                            </button>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Testimonio', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.texto" @input="updateItem(index, 'texto', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                        <div class="vbp-field-row">
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Autor', 'flavor-chat-ia' ); ?></label>
                                                <input type="text" x-model="item.autor" @input="updateItem(index, 'autor', $event.target.value)" class="vbp-field-input">
                                            </div>
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Cargo', 'flavor-chat-ia' ); ?></label>
                                                <input type="text" x-model="item.cargo" @input="updateItem(index, 'cargo', $event.target.value)" class="vbp-field-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== PRICING (con items) ========== -->
                <template x-if="selectedElement.type === 'pricing'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Planes', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('pricing')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index, 'highlighted': item.destacado }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-price">$<span x-text="item.precio || '0'"></span></span>
                                        <span class="vbp-item-title" x-text="item.nombre || '<?php esc_attr_e( 'Plan', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === selectedElement.data.items.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-row">
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Nombre', 'flavor-chat-ia' ); ?></label>
                                                <input type="text" x-model="item.nombre" @input="updateItem(index, 'nombre', $event.target.value)" class="vbp-field-input">
                                            </div>
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Precio', 'flavor-chat-ia' ); ?></label>
                                                <input type="text" x-model="item.precio" @input="updateItem(index, 'precio', $event.target.value)" class="vbp-field-input">
                                            </div>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Período', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.periodo" @input="updateItem(index, 'periodo', $event.target.value)" class="vbp-field-input" placeholder="/mes">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Características (una por línea)', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.caracteristicas_text" @input="updatePricingFeatures(index, $event.target.value)" class="vbp-field-textarea" rows="4" :placeholder="'<?php esc_attr_e( 'Característica 1\nCaracterística 2\nCaracterística 3', 'flavor-chat-ia' ); ?>'"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-checkbox-label">
                                                <input type="checkbox" x-model="item.destacado" @change="updateItem(index, 'destacado', item.destacado)">
                                                <?php esc_html_e( 'Plan destacado', 'flavor-chat-ia' ); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== FAQ (con items) ========== -->
                <template x-if="selectedElement.type === 'faq'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Preguntas', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('faq')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon">❓</span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="item.pregunta || '<?php esc_attr_e( 'Pregunta', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === selectedElement.data.items.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Pregunta', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.pregunta" @input="updateItem(index, 'pregunta', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Respuesta', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.respuesta" @input="updateItem(index, 'respuesta', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== FORM (con campos) ========== -->
                <template x-if="selectedElement.type === 'form'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje de éxito', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_exito" @input="updateElementData('mensaje_exito', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Campos del formulario', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('form')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(campo, index) in selectedElement.data.campos" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="campo.tipo === 'textarea' ? '📝' : (campo.tipo === 'email' ? '📧' : (campo.tipo === 'tel' ? '📞' : (campo.tipo === 'select' ? '📋' : (campo.tipo === 'checkbox' ? '☑️' : '📄'))))"></span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="campo.label || '<?php esc_attr_e( 'Campo', 'flavor-chat-ia' ); ?>'"></span>
                                        <span class="vbp-item-badge" x-show="campo.requerido">*</span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveCampo(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveCampo(index, 1)" :disabled="index === selectedElement.data.campos.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeCampo(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo de campo', 'flavor-chat-ia' ); ?></label>
                                            <select x-model="campo.tipo" @change="updateCampo(index, 'tipo', $event.target.value)" class="vbp-field-select">
                                                <option value="text"><?php esc_html_e( 'Texto', 'flavor-chat-ia' ); ?></option>
                                                <option value="email"><?php esc_html_e( 'Email', 'flavor-chat-ia' ); ?></option>
                                                <option value="tel"><?php esc_html_e( 'Teléfono', 'flavor-chat-ia' ); ?></option>
                                                <option value="number"><?php esc_html_e( 'Número', 'flavor-chat-ia' ); ?></option>
                                                <option value="textarea"><?php esc_html_e( 'Área de texto', 'flavor-chat-ia' ); ?></option>
                                                <option value="select"><?php esc_html_e( 'Selector', 'flavor-chat-ia' ); ?></option>
                                                <option value="checkbox"><?php esc_html_e( 'Checkbox', 'flavor-chat-ia' ); ?></option>
                                            </select>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="campo.label" @input="updateCampo(index, 'label', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group" x-show="campo.tipo !== 'checkbox' && campo.tipo !== 'select'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Placeholder', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="campo.placeholder" @input="updateCampo(index, 'placeholder', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group" x-show="campo.tipo === 'select'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Opciones (una por línea)', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="campo.opciones_text" @input="updateCampo(index, 'opciones_text', $event.target.value)" class="vbp-field-textarea" rows="3" placeholder="<?php esc_attr_e( 'Opción 1&#10;Opción 2&#10;Opción 3', 'flavor-chat-ia' ); ?>"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-checkbox-label">
                                                <input type="checkbox" x-model="campo.requerido" @change="updateCampo(index, 'requerido', campo.requerido)">
                                                <?php esc_html_e( 'Campo obligatorio', 'flavor-chat-ia' ); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== TEAM (con items) ========== -->
                <template x-if="selectedElement.type === 'team'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Miembros', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('team')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-avatar" x-text="(item.nombre || 'M').charAt(0)"></span>
                                        <span class="vbp-item-title" x-text="item.nombre || '<?php esc_attr_e( 'Miembro', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === selectedElement.data.items.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Nombre', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.nombre" @input="updateItem(index, 'nombre', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Cargo', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.cargo" @input="updateItem(index, 'cargo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Biografía', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.bio" @input="updateItem(index, 'bio', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Foto', 'flavor-chat-ia' ); ?></label>
                                            <div class="vbp-image-preview vbp-image-preview-small" x-show="item.foto">
                                                <img :src="item.foto" alt="">
                                                <button type="button" @click="updateItem(index, 'foto', '')" class="vbp-image-remove" title="<?php esc_attr_e( 'Eliminar foto', 'flavor-chat-ia' ); ?>">×</button>
                                            </div>
                                            <button type="button" @click="openMediaLibraryForItem(index, 'foto')" class="vbp-btn vbp-btn-secondary vbp-btn-sm vbp-btn-block">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                                <span x-text="item.foto ? '<?php echo esc_js( __( 'Cambiar foto', 'flavor-chat-ia' ) ); ?>' : '<?php echo esc_js( __( 'Seleccionar foto', 'flavor-chat-ia' ) ); ?>'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== STATS (con items) ========== -->
                <template x-if="selectedElement.type === 'stats'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Estadísticas', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('stats')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card vbp-item-card-inline" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-stat" x-text="item.numero || '0'"></span>
                                        <span class="vbp-item-title" x-text="item.label || '<?php esc_attr_e( 'Etiqueta', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-row">
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Número', 'flavor-chat-ia' ); ?></label>
                                                <input type="text" x-model="item.numero" @input="updateItem(index, 'numero', $event.target.value)" class="vbp-field-input" placeholder="10K+">
                                            </div>
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta', 'flavor-chat-ia' ); ?></label>
                                                <input type="text" x-model="item.label" @input="updateItem(index, 'label', $event.target.value)" class="vbp-field-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== GALLERY (con items) ========== -->
                <template x-if="selectedElement.type === 'gallery'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Imágenes', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addGalleryImage()" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-gallery-grid">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-gallery-item">
                                    <img :src="item.src" :alt="item.alt || ''" class="vbp-gallery-thumb">
                                    <button type="button" @click="removeItem(index)" class="vbp-gallery-remove">×</button>
                                </div>
                            </template>
                            <button type="button" @click="addGalleryImage()" class="vbp-gallery-add">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- ========== BLOG ========== -->
                <template x-if="selectedElement.type === 'blog'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Últimos artículos', 'flavor-chat-ia' ); ?>">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Categoría', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.data.categoria" @change="updateElementData('categoria', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Todas las categorías', 'flavor-chat-ia' ); ?></option>
                                <?php
                                $categorias = get_categories( array( 'hide_empty' => false ) );
                                foreach ( $categorias as $cat ) {
                                    echo '<option value="' . esc_attr( $cat->slug ) . '">' . esc_html( $cat->name ) . ' (' . intval( $cat->count ) . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Cantidad', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.cantidad" @change="updateElementData('cantidad', parseInt($event.target.value))" class="vbp-field-select">
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="6">6</option>
                                    <option value="9">9</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Columnas', 'flavor-chat-ia' ); ?></label>
                                <div class="vbp-btn-group vbp-btn-group-full">
                                    <template x-for="n in [2, 3, 4]">
                                        <button type="button" @click="updateElementData('columnas', n)" :class="{ 'active': selectedElement.data.columnas === n || (!selectedElement.data.columnas && n === 3) }" class="vbp-btn-toggle" x-text="n"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Ordenar por', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.ordenar_por" @change="updateElementData('ordenar_por', $event.target.value)" class="vbp-field-select">
                                    <option value="date"><?php esc_html_e( 'Fecha', 'flavor-chat-ia' ); ?></option>
                                    <option value="title"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></option>
                                    <option value="rand"><?php esc_html_e( 'Aleatorio', 'flavor-chat-ia' ); ?></option>
                                    <option value="comment_count"><?php esc_html_e( 'Comentarios', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Orden', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.orden" @change="updateElementData('orden', $event.target.value)" class="vbp-field-select">
                                    <option value="DESC"><?php esc_html_e( 'Descendente', 'flavor-chat-ia' ); ?></option>
                                    <option value="ASC"><?php esc_html_e( 'Ascendente', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Opciones de visualización', 'flavor-chat-ia' ); ?></h4>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_extracto !== false" @change="updateElementData('mostrar_extracto', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar extracto', 'flavor-chat-ia' ); ?>
                            </label>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_autor !== false" @change="updateElementData('mostrar_autor', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar autor', 'flavor-chat-ia' ); ?>
                            </label>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_fecha !== false" @change="updateElementData('mostrar_fecha', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar fecha', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ========== CONTACT ========== -->
                <template x-if="selectedElement.type === 'contact'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Enviar mensaje', 'flavor-chat-ia' ); ?>">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje de éxito', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_exito" @input="updateElementData('mensaje_exito', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( '¡Mensaje enviado correctamente!', 'flavor-chat-ia' ); ?>">
                        </div>
                    </div>
                </template>

                <!-- ========== COUNTDOWN ========== -->
                <template x-if="selectedElement.type === 'countdown'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fecha fin', 'flavor-chat-ia' ); ?></label>
                                <input type="date" x-model="selectedElement.data.fecha" @input="updateElementData('fecha', $event.target.value)" class="vbp-field-input">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Hora', 'flavor-chat-ia' ); ?></label>
                                <input type="time" x-model="selectedElement.data.hora" @input="updateElementData('hora', $event.target.value)" class="vbp-field-input">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje al finalizar', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_fin" @input="updateElementData('mensaje_fin', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mostrar', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-checkbox-group">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_dias" @change="updateElementData('mostrar_dias', selectedElement.data.mostrar_dias)">
                                    <?php esc_html_e( 'Días', 'flavor-chat-ia' ); ?>
                                </label>
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_horas" @change="updateElementData('mostrar_horas', selectedElement.data.mostrar_horas)">
                                    <?php esc_html_e( 'Horas', 'flavor-chat-ia' ); ?>
                                </label>
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_minutos" @change="updateElementData('mostrar_minutos', selectedElement.data.mostrar_minutos)">
                                    <?php esc_html_e( 'Minutos', 'flavor-chat-ia' ); ?>
                                </label>
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_segundos" @change="updateElementData('mostrar_segundos', selectedElement.data.mostrar_segundos)">
                                    <?php esc_html_e( 'Segundos', 'flavor-chat-ia' ); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== SOCIAL ICONS ========== -->
                <template x-if="selectedElement.type === 'social-icons'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título (opcional)', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="Síguenos">
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Estilo', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.estilo" @change="updateElementData('estilo', $event.target.value)" class="vbp-field-select">
                                    <option value="circle"><?php esc_html_e( 'Círculo', 'flavor-chat-ia' ); ?></option>
                                    <option value="square"><?php esc_html_e( 'Cuadrado', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.data.tamano" @change="updateElementData('tamano', $event.target.value)" class="vbp-field-select">
                                    <option value="small"><?php esc_html_e( 'Pequeño', 'flavor-chat-ia' ); ?></option>
                                    <option value="medium"><?php esc_html_e( 'Mediano', 'flavor-chat-ia' ); ?></option>
                                    <option value="large"><?php esc_html_e( 'Grande', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('alineacion', 'flex-start')" :class="{ 'active': selectedElement.data.alineacion === 'flex-start' }" class="vbp-btn-icon"><?php esc_html_e( 'Izq', 'flavor-chat-ia' ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'center')" :class="{ 'active': selectedElement.data.alineacion === 'center' || !selectedElement.data.alineacion }" class="vbp-btn-icon"><?php esc_html_e( 'Centro', 'flavor-chat-ia' ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'flex-end')" :class="{ 'active': selectedElement.data.alineacion === 'flex-end' }" class="vbp-btn-icon"><?php esc_html_e( 'Der', 'flavor-chat-ia' ); ?></button>
                            </div>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Redes sociales', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('social-icons')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(red, index) in selectedElement.data.redes" :key="index">
                                <div class="vbp-item-card vbp-item-card-inline" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="red.icono || '🔗'"></span>
                                        <span class="vbp-item-title" x-text="red.red || '<?php esc_attr_e( 'Red', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="removeSocialItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', 'flavor-chat-ia' ); ?></label>
                                            <div class="vbp-field-with-selector">
                                                <input type="text" x-model="red.icono" @input="updateSocialItem(index, 'icono', $event.target.value)" class="vbp-field-input" placeholder="link">
                                                <button type="button" @click="openIconSelectorForSocial(index)" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', 'flavor-chat-ia' ); ?>">
                                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Red', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="red.red" @input="updateSocialItem(index, 'red', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'URL', 'flavor-chat-ia' ); ?></label>
                                            <input type="url" x-model="red.url" @input="updateSocialItem(index, 'url', $event.target.value)" class="vbp-field-input" placeholder="https://">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== NEWSLETTER ========== -->
                <template x-if="selectedElement.type === 'newsletter'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', 'flavor-chat-ia' ); ?></label>
                            <textarea x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Placeholder email', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.placeholder_email" @input="updateElementData('placeholder_email', $event.target.value)" class="vbp-field-input" placeholder="tu@email.com">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.mostrar_nombre" @change="updateElementData('mostrar_nombre', selectedElement.data.mostrar_nombre)">
                                <?php esc_html_e( 'Mostrar campo de nombre', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje de éxito', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_exito" @input="updateElementData('mensaje_exito', $event.target.value)" class="vbp-field-input">
                        </div>
                    </div>
                </template>

                <!-- ========== LOGO GRID ========== -->
                <template x-if="selectedElement.type === 'logo-grid'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="Confían en nosotros">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Columnas', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group vbp-btn-group-full">
                                <template x-for="n in [3, 4, 5, 6]">
                                    <button type="button" @click="updateElementData('columnas', n)" :class="{ 'active': selectedElement.data.columnas === n }" class="vbp-btn-toggle" x-text="n"></button>
                                </template>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.escala_grises" @change="updateElementData('escala_grises', selectedElement.data.escala_grises)">
                                <?php esc_html_e( 'Escala de grises', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.hover_color" @change="updateElementData('hover_color', selectedElement.data.hover_color)">
                                <?php esc_html_e( 'Color al pasar el ratón', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Logos', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addLogoImage()" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-gallery-grid vbp-logo-grid-preview">
                            <template x-for="(logo, index) in selectedElement.data.logos" :key="index">
                                <div class="vbp-gallery-item">
                                    <img :src="logo.src" :alt="logo.alt || 'Logo'" class="vbp-gallery-thumb">
                                    <button type="button" @click="removeLogoItem(index)" class="vbp-gallery-remove">×</button>
                                </div>
                            </template>
                            <button type="button" @click="addLogoImage()" class="vbp-gallery-add">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- ========== ICON BOX ========== -->
                <template x-if="selectedElement.type === 'icon-box'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-field-with-selector">
                                <div class="vbp-icon-field-preview">
                                    <span class="vbp-icon-field-value" :class="{ 'material-type': /^[a-z_]+$/.test(selectedElement.data.icono) }">
                                        <span x-show="/^[a-z_]+$/.test(selectedElement.data.icono)" class="material-icons" x-text="selectedElement.data.icono"></span>
                                        <span x-show="!/^[a-z_]+$/.test(selectedElement.data.icono)" x-text="selectedElement.data.icono || '✨'"></span>
                                    </span>
                                </div>
                                <button type="button" @click="openIconSelector('icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', 'flavor-chat-ia' ); ?>">
                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                </button>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Descripción', 'flavor-chat-ia' ); ?></label>
                            <textarea x-model="selectedElement.data.descripcion" @input="updateElementData('descripcion', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('alineacion', 'left')" :class="{ 'active': selectedElement.data.alineacion === 'left' }" class="vbp-btn-icon"><?php esc_html_e( 'Izq', 'flavor-chat-ia' ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'center')" :class="{ 'active': selectedElement.data.alineacion === 'center' || !selectedElement.data.alineacion }" class="vbp-btn-icon"><?php esc_html_e( 'Centro', 'flavor-chat-ia' ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'right')" :class="{ 'active': selectedElement.data.alineacion === 'right' }" class="vbp-btn-icon"><?php esc_html_e( 'Der', 'flavor-chat-ia' ); ?></button>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto enlace', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.enlace_texto" @input="updateElementData('enlace_texto', $event.target.value)" class="vbp-field-input" placeholder="Saber más">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL enlace', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.enlace_url"
                                       @input="searchQuery = $event.target.value; updateElementData('enlace_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="updateElementData('enlace_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading"><?php esc_html_e( 'Buscando...', 'flavor-chat-ia' ); ?></div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', 'flavor-chat-ia' ); ?></div>
                                    </template>
                                    <template x-for="(result, idx) in results" :key="result.id">
                                        <button type="button" class="vbp-autocomplete-item" :class="{ 'active': activeIndex === idx }" @click="selectResult(result)">
                                            <span class="vbp-autocomplete-icon" x-text="result.icon"></span>
                                            <span class="vbp-autocomplete-info">
                                                <span class="vbp-autocomplete-title" x-text="result.title"></span>
                                                <span class="vbp-autocomplete-type" x-text="getTypeLabel(result.type)"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== ACCORDION ========== -->
                <template x-if="selectedElement.type === 'accordion'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.multiples_abiertos" @change="updateElementData('multiples_abiertos', selectedElement.data.multiples_abiertos)">
                                <?php esc_html_e( 'Permitir múltiples abiertos', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Elementos', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('accordion')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="item.abierto ? '▼' : '▶'"></span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="item.titulo || '<?php esc_attr_e( 'Elemento', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === selectedElement.data.items.length - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Contenido', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.contenido" @input="updateItem(index, 'contenido', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-checkbox-label">
                                                <input type="checkbox" x-model="item.abierto" @change="updateItem(index, 'abierto', item.abierto)">
                                                <?php esc_html_e( 'Abierto por defecto', 'flavor-chat-ia' ); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== TABS ========== -->
                <template x-if="selectedElement.type === 'tabs'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Estilo', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.data.estilo" @change="updateElementData('estilo', $event.target.value)" class="vbp-field-select">
                                <option value="horizontal"><?php esc_html_e( 'Horizontal', 'flavor-chat-ia' ); ?></option>
                                <option value="vertical"><?php esc_html_e( 'Vertical', 'flavor-chat-ia' ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Pestañas', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('tabs')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index, 'highlighted': selectedElement.data.tab_activa === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon">📑</span>
                                        <span class="vbp-item-title" x-text="item.titulo || '<?php esc_attr_e( 'Tab', 'flavor-chat-ia' ); ?> ' + (index + 1)"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="updateElementData('tab_activa', index)" :class="{ 'active': selectedElement.data.tab_activa === index }" class="vbp-btn-icon-xs" title="<?php esc_attr_e( 'Activar', 'flavor-chat-ia' ); ?>">★</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título de pestaña', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Contenido', 'flavor-chat-ia' ); ?></label>
                                            <textarea x-model="item.contenido" @input="updateItem(index, 'contenido', $event.target.value)" class="vbp-field-textarea" rows="4"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== PROGRESS BAR ========== -->
                <template x-if="selectedElement.type === 'progress-bar'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.mostrar_porcentaje" @change="updateElementData('mostrar_porcentaje', selectedElement.data.mostrar_porcentaje)">
                                <?php esc_html_e( 'Mostrar porcentaje', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.animado" @change="updateElementData('animado', selectedElement.data.animado)">
                                <?php esc_html_e( 'Animación al cargar', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Barras', 'flavor-chat-ia' ); ?></h4>
                            <button type="button" @click="addItem('progress-bar')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in selectedElement.data.items" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-stat" x-text="(item.porcentaje || 0) + '%'"></span>
                                        <span class="vbp-item-title" x-text="item.label || '<?php esc_attr_e( 'Skill', 'flavor-chat-ia' ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta', 'flavor-chat-ia' ); ?></label>
                                            <input type="text" x-model="item.label" @input="updateItem(index, 'label', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Porcentaje', 'flavor-chat-ia' ); ?></label>
                                            <div class="vbp-range-input">
                                                <input type="range" min="0" max="100" x-model="item.porcentaje" @input="updateItem(index, 'porcentaje', parseInt($event.target.value))" class="vbp-field-range">
                                                <span class="vbp-range-value" x-text="(item.porcentaje || 0) + '%'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== ALERT ========== -->
                <template x-if="selectedElement.type === 'alert'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.data.tipo" @change="updateElementData('tipo', $event.target.value)" class="vbp-field-select">
                                <option value="info"><?php esc_html_e( 'Información', 'flavor-chat-ia' ); ?></option>
                                <option value="success"><?php esc_html_e( 'Éxito', 'flavor-chat-ia' ); ?></option>
                                <option value="warning"><?php esc_html_e( 'Advertencia', 'flavor-chat-ia' ); ?></option>
                                <option value="error"><?php esc_html_e( 'Error', 'flavor-chat-ia' ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje', 'flavor-chat-ia' ); ?></label>
                            <textarea x-model="selectedElement.data.mensaje" @input="updateElementData('mensaje', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.icono" @change="updateElementData('icono', selectedElement.data.icono)">
                                <?php esc_html_e( 'Mostrar icono', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.dismissible" @change="updateElementData('dismissible', selectedElement.data.dismissible)">
                                <?php esc_html_e( 'Permitir cerrar', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ========== BEFORE AFTER ========== -->
                <template x-if="selectedElement.type === 'before-after'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen "Antes"', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-image-preview" x-show="selectedElement.data.imagen_antes">
                                <img :src="selectedElement.data.imagen_antes" alt="">
                                <button type="button" @click="updateElementData('imagen_antes', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('imagen_antes')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <?php esc_html_e( 'Seleccionar imagen', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta "Antes"', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.label_antes" @input="updateElementData('label_antes', $event.target.value)" class="vbp-field-input" placeholder="Antes">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen "Después"', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-image-preview" x-show="selectedElement.data.imagen_despues">
                                <img :src="selectedElement.data.imagen_despues" alt="">
                                <button type="button" @click="updateElementData('imagen_despues', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('imagen_despues')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <?php esc_html_e( 'Seleccionar imagen', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta "Después"', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.label_despues" @input="updateElementData('label_despues', $event.target.value)" class="vbp-field-input" placeholder="Después">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Orientación', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.data.orientacion" @change="updateElementData('orientacion', $event.target.value)" class="vbp-field-select">
                                <option value="horizontal"><?php esc_html_e( 'Horizontal', 'flavor-chat-ia' ); ?></option>
                                <option value="vertical"><?php esc_html_e( 'Vertical', 'flavor-chat-ia' ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Posición inicial del slider', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="10" max="90" x-model="selectedElement.data.posicion_inicial" @input="updateElementData('posicion_inicial', parseInt($event.target.value))" class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.data.posicion_inicial || 50) + '%'"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== DIVIDER ========== -->
                <template x-if="selectedElement.type === 'divider'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Estilo', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.data.style" @change="updateElementData('style', $event.target.value)" class="vbp-field-select">
                                <option value="solid"><?php esc_html_e( 'Sólido', 'flavor-chat-ia' ); ?></option>
                                <option value="dashed"><?php esc_html_e( 'Discontinuo', 'flavor-chat-ia' ); ?></option>
                                <option value="dotted"><?php esc_html_e( 'Punteado', 'flavor-chat-ia' ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Grosor', 'flavor-chat-ia' ); ?></label>
                                <input type="text" x-model="selectedElement.data.width" @input="updateElementData('width', $event.target.value)" class="vbp-field-input" placeholder="1px">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color', 'flavor-chat-ia' ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.color, '#cccccc')" @input="updateElementData('color', $event.target.value)" class="vbp-color-input">
                                    <input type="text" x-model="selectedElement.data.color" @input="updateElementData('color', $event.target.value)" class="vbp-field-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== SPACER ========== -->
                <template x-if="selectedElement.type === 'spacer'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Altura', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="10" max="200" x-model="spacerHeight" @input="updateElementData('height', $event.target.value + 'px')" class="vbp-field-range">
                                <input type="text" x-model="selectedElement.data.height" @input="updateElementData('height', $event.target.value)" class="vbp-field-input vbp-field-input-sm" placeholder="60px">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== ICON ========== -->
                <template x-if="selectedElement.type === 'icon'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-field-with-selector">
                                <div class="vbp-icon-field-preview">
                                    <span class="vbp-icon-field-value">
                                        <span x-show="/^[a-z_]+$/.test(selectedElement.data.icon)" class="material-icons" x-text="selectedElement.data.icon"></span>
                                        <span x-show="!/^[a-z_]+$/.test(selectedElement.data.icon)" x-text="selectedElement.data.icon || '⭐'"></span>
                                    </span>
                                </div>
                                <button type="button" @click="openIconSelector('icon')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', 'flavor-chat-ia' ); ?>">
                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                </button>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.size" @input="updateElementData('size', $event.target.value)" class="vbp-field-input" placeholder="48px">
                        </div>
                    </div>
                </template>

                <!-- ========== COLUMNS/ROW ========== -->
                <template x-if="selectedElement.type === 'columns' || selectedElement.type === 'row'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Número de columnas', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group vbp-btn-group-full">
                                <template x-for="n in [2, 3, 4, 5, 6]">
                                    <button type="button" @click="updateColumnsCount(n)" :class="{ 'active': selectedElement.data.columns === n }" class="vbp-btn-toggle" x-text="n"></button>
                                </template>
                            </div>
                        </div>

                        <!-- Anchos de columnas individuales -->
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Anchos de columnas', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-columns-widths">
                                <template x-for="(width, index) in getColumnWidths()" :key="index">
                                    <div class="vbp-column-width-control">
                                        <span class="vbp-column-label" x-text="'Col ' + (index + 1)"></span>
                                        <input type="range"
                                               min="10"
                                               max="80"
                                               :value="parseFloat(width) || (100 / selectedElement.data.columns)"
                                               @input="updateColumnWidth(index, $event.target.value)"
                                               class="vbp-field-range vbp-field-range-sm">
                                        <span class="vbp-column-width-value" x-text="Math.round(parseFloat(width) || (100 / selectedElement.data.columns)) + '%'"></span>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="resetColumnWidths()" class="vbp-btn vbp-btn-ghost vbp-btn-sm">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                <?php esc_html_e( 'Igualar anchos', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>

                        <!-- Gap entre columnas -->
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Espacio entre columnas (gap)', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range"
                                       min="0"
                                       max="60"
                                       :value="parseInt(selectedElement.data.gap) || 20"
                                       @input="updateElementData('gap', $event.target.value + 'px')"
                                       class="vbp-field-range">
                                <input type="text"
                                       :value="selectedElement.data.gap || '20px'"
                                       @input="updateElementData('gap', $event.target.value)"
                                       class="vbp-field-input vbp-field-input-sm"
                                       placeholder="20px">
                            </div>
                        </div>

                        <!-- Alineación vertical -->
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación vertical', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('verticalAlign', 'start')" :class="{ 'active': selectedElement.data.verticalAlign === 'start' || !selectedElement.data.verticalAlign }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Arriba', 'flavor-chat-ia' ); ?>">↑</button>
                                <button type="button" @click="updateElementData('verticalAlign', 'center')" :class="{ 'active': selectedElement.data.verticalAlign === 'center' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Centro', 'flavor-chat-ia' ); ?>">⎯</button>
                                <button type="button" @click="updateElementData('verticalAlign', 'end')" :class="{ 'active': selectedElement.data.verticalAlign === 'end' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Abajo', 'flavor-chat-ia' ); ?>">↓</button>
                                <button type="button" @click="updateElementData('verticalAlign', 'stretch')" :class="{ 'active': selectedElement.data.verticalAlign === 'stretch' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Estirar', 'flavor-chat-ia' ); ?>">↕</button>
                            </div>
                        </div>

                        <!-- Responsive: apilar en móvil -->
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.stackOnMobile !== false" @change="updateElementData('stackOnMobile', $event.target.checked)">
                                <?php esc_html_e( 'Apilar en móvil', 'flavor-chat-ia' ); ?>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ========== VIDEO ========== -->
                <template x-if="selectedElement.type === 'video-embed' || selectedElement.type === 'video-section'">
                    <div class="vbp-inspector-section">
                        <template x-if="selectedElement.type === 'video-section'">
                            <div>
                                <div class="vbp-field-group">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                                    <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                                </div>
                                <div class="vbp-field-group">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Descripción', 'flavor-chat-ia' ); ?></label>
                                    <textarea x-model="selectedElement.data.descripcion" @input="updateElementData('descripcion', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                                </div>
                            </div>
                        </template>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL del video', 'flavor-chat-ia' ); ?></label>
                            <input type="url" x-model="selectedElement.data.video_url" @input="updateElementData('video_url', $event.target.value)" class="vbp-field-input" placeholder="https://youtube.com/watch?v=...">
                            <small class="vbp-field-hint"><?php esc_html_e( 'YouTube, Vimeo o URL de video directo', 'flavor-chat-ia' ); ?></small>
                        </div>
                    </div>
                </template>

                <!-- ========== MAP ========== -->
                <template x-if="selectedElement.type === 'map' || selectedElement.type === 'mapa'">
                    <div class="vbp-inspector-section" x-data="{ isGeocoding: false, geocodeError: '' }">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Dirección', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-field-with-button">
                                <input type="text"
                                       x-model="selectedElement.data.direccion"
                                       @input="updateElementData('direccion', $event.target.value)"
                                       @keydown.enter.prevent="geocodeAddress(selectedElement.data.direccion, isGeocoding, $refs)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Ej: Gran Vía 1, Madrid, España', 'flavor-chat-ia' ); ?>">
                                <button type="button"
                                        @click="geocodeAddress(selectedElement.data.direccion, isGeocoding, $refs)"
                                        :disabled="isGeocoding || !selectedElement.data.direccion"
                                        class="vbp-btn vbp-btn-secondary vbp-btn-sm"
                                        title="<?php esc_attr_e( 'Buscar coordenadas', 'flavor-chat-ia' ); ?>">
                                    <span x-show="!isGeocoding">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                    </span>
                                    <span x-show="isGeocoding" class="vbp-spinner-small"></span>
                                </button>
                            </div>
                            <small x-show="geocodeError" class="vbp-field-error" x-text="geocodeError"></small>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Latitud', 'flavor-chat-ia' ); ?></label>
                                <input type="text" x-model="selectedElement.data.lat" @input="updateElementData('lat', $event.target.value)" class="vbp-field-input" placeholder="40.4168">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Longitud', 'flavor-chat-ia' ); ?></label>
                                <input type="text" x-model="selectedElement.data.lng" @input="updateElementData('lng', $event.target.value)" class="vbp-field-input" placeholder="-3.7038">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Zoom', 'flavor-chat-ia' ); ?></label>
                            <input type="range" min="1" max="20" x-model="selectedElement.data.zoom" @input="updateElementData('zoom', parseInt($event.target.value))" class="vbp-field-range">
                            <span class="vbp-range-value" x-text="selectedElement.data.zoom || 14"></span>
                        </div>
                    </div>
                </template>

                <!-- ========== HTML ========== -->
                <template x-if="selectedElement.type === 'html'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Código HTML', 'flavor-chat-ia' ); ?></label>
                            <textarea x-model="selectedElement.data.code" @input="updateElementData('code', $event.target.value)" class="vbp-field-textarea vbp-code-textarea" rows="10"></textarea>
                        </div>
                    </div>
                </template>

                <!-- ========== SHORTCODE ========== -->
                <template x-if="selectedElement.type === 'shortcode'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Shortcode', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.data.shortcode" @input="updateElementData('shortcode', $event.target.value)" class="vbp-field-input vbp-code-input" placeholder="[mi_shortcode]">
                        </div>
                    </div>
                </template>

                <!-- ========== MODULE WIDGETS ========== -->
                <template x-if="isModuleWidget()">
                    <div class="vbp-inspector-section vbp-module-section">
                        <!-- Header del módulo -->
                        <div class="vbp-module-header">
                            <span class="vbp-module-icon" x-html="getModuleIcon()"></span>
                            <div class="vbp-module-info">
                                <span class="vbp-module-name" x-text="getModuleName()"></span>
                                <span class="vbp-module-id" x-text="selectedElement.type"></span>
                            </div>
                        </div>

                        <!-- Campos dinámicos del módulo -->
                        <div class="vbp-module-fields">
                            <template x-for="(field, key) in getModuleFields()" :key="key">
                                <div class="vbp-field-group">
                                    <label class="vbp-field-label" x-text="field.label"></label>

                                    <!-- Input text -->
                                    <template x-if="field.type === 'text'">
                                        <input type="text"
                                               :value="selectedElement.data[key] || field.default || ''"
                                               @input="updateElementData(key, $event.target.value)"
                                               class="vbp-field-input">
                                    </template>

                                    <!-- Input number -->
                                    <template x-if="field.type === 'number'">
                                        <input type="number"
                                               :value="selectedElement.data[key] || field.default || ''"
                                               @input="updateElementData(key, parseInt($event.target.value) || field.default)"
                                               :min="field.min"
                                               :max="field.max"
                                               class="vbp-field-input">
                                    </template>

                                    <!-- Select -->
                                    <template x-if="field.type === 'select'">
                                        <select :value="selectedElement.data[key] || field.default || ''"
                                                @change="updateElementData(key, $event.target.value)"
                                                class="vbp-field-select">
                                            <template x-for="(label, val) in field.options" :key="val">
                                                <option :value="val" x-text="label" :selected="(selectedElement.data[key] || field.default) === val"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <!-- Toggle -->
                                    <template x-if="field.type === 'toggle'">
                                        <label class="vbp-toggle">
                                            <input type="checkbox"
                                                   :checked="selectedElement.data[key] !== undefined ? selectedElement.data[key] : field.default"
                                                   @change="updateElementData(key, $event.target.checked)">
                                            <span class="vbp-toggle-slider"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Botón preview en vivo -->
                        <button type="button" @click="refreshModulePreview()" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/></svg>
                            <?php esc_html_e( 'Actualizar preview', 'flavor-chat-ia' ); ?>
                        </button>
                    </div>
                </template>

            </div>

            <!-- ============================================ -->
            <!-- Tab: Estilos -->
            <!-- ============================================ -->
            <div x-show="activeTab === 'styles'" class="vbp-inspector-panel">

                <!-- Selector de Breakpoints -->
                <div class="vbp-breakpoint-selector">
                    <div class="vbp-breakpoint-tabs">
                        <button type="button"
                                @click="setBreakpoint('desktop')"
                                :class="{ 'active': activeBreakpoint === 'desktop' }"
                                class="vbp-breakpoint-tab"
                                title="<?php esc_attr_e( 'Desktop (> 1024px)', 'flavor-chat-ia' ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                            <span class="vbp-breakpoint-label"><?php esc_html_e( 'Desktop', 'flavor-chat-ia' ); ?></span>
                        </button>
                        <button type="button"
                                @click="setBreakpoint('tablet')"
                                :class="{ 'active': activeBreakpoint === 'tablet', 'has-overrides': hasBreakpointOverridesForElement('tablet') }"
                                class="vbp-breakpoint-tab"
                                title="<?php esc_attr_e( 'Tablet (769px - 1024px)', 'flavor-chat-ia' ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="2" width="16" height="20" rx="2"/>
                                <line x1="12" y1="18" x2="12" y2="18"/>
                            </svg>
                            <span class="vbp-breakpoint-label"><?php esc_html_e( 'Tablet', 'flavor-chat-ia' ); ?></span>
                        </button>
                        <button type="button"
                                @click="setBreakpoint('mobile')"
                                :class="{ 'active': activeBreakpoint === 'mobile', 'has-overrides': hasBreakpointOverridesForElement('mobile') }"
                                class="vbp-breakpoint-tab"
                                title="<?php esc_attr_e( 'Mobile (< 768px)', 'flavor-chat-ia' ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="6" y="2" width="12" height="20" rx="2"/>
                                <line x1="12" y1="18" x2="12" y2="18"/>
                            </svg>
                            <span class="vbp-breakpoint-label"><?php esc_html_e( 'Mobile', 'flavor-chat-ia' ); ?></span>
                        </button>
                    </div>

                    <!-- Indicador de breakpoint activo -->
                    <div class="vbp-breakpoint-info" x-show="activeBreakpoint !== 'desktop'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        <span><?php esc_html_e( 'Editando estilos para', 'flavor-chat-ia' ); ?>
                            <strong x-text="activeBreakpoint === 'tablet' ? 'Tablet' : 'Mobile'"></strong>
                        </span>
                        <button type="button"
                                @click="$store.vbp.clearBreakpointOverrides(selectedElement.id, activeBreakpoint)"
                                class="vbp-btn-link vbp-btn-xs"
                                title="<?php esc_attr_e( 'Limpiar todos los overrides de este breakpoint', 'flavor-chat-ia' ); ?>">
                            <?php esc_html_e( 'Limpiar', 'flavor-chat-ia' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Spacing -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Espaciado', 'flavor-chat-ia' ); ?></h4>

                    <div class="vbp-spacing-editor">
                        <div class="vbp-spacing-box">
                            <div class="vbp-spacing-margin">
                                <span class="vbp-spacing-label"><?php esc_html_e( 'Margin', 'flavor-chat-ia' ); ?></span>
                                <input type="text" x-model="selectedElement.styles.spacing.margin.top" @input="updateStyle('spacing.margin.top', $event.target.value)" class="vbp-spacing-input vbp-spacing-top" placeholder="0">
                                <input type="text" x-model="selectedElement.styles.spacing.margin.right" @input="updateStyle('spacing.margin.right', $event.target.value)" class="vbp-spacing-input vbp-spacing-right" placeholder="0">
                                <input type="text" x-model="selectedElement.styles.spacing.margin.bottom" @input="updateStyle('spacing.margin.bottom', $event.target.value)" class="vbp-spacing-input vbp-spacing-bottom" placeholder="0">
                                <input type="text" x-model="selectedElement.styles.spacing.margin.left" @input="updateStyle('spacing.margin.left', $event.target.value)" class="vbp-spacing-input vbp-spacing-left" placeholder="0">

                                <div class="vbp-spacing-padding">
                                    <span class="vbp-spacing-label"><?php esc_html_e( 'Padding', 'flavor-chat-ia' ); ?></span>
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.top" @input="updateStyle('spacing.padding.top', $event.target.value)" class="vbp-spacing-input vbp-spacing-top" placeholder="0">
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.right" @input="updateStyle('spacing.padding.right', $event.target.value)" class="vbp-spacing-input vbp-spacing-right" placeholder="0">
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.bottom" @input="updateStyle('spacing.padding.bottom', $event.target.value)" class="vbp-spacing-input vbp-spacing-bottom" placeholder="0">
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.left" @input="updateStyle('spacing.padding.left', $event.target.value)" class="vbp-spacing-input vbp-spacing-left" placeholder="0">

                                    <div class="vbp-spacing-content">
                                        <?php esc_html_e( 'Contenido', 'flavor-chat-ia' ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colors -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Colores', 'flavor-chat-ia' ); ?></h4>

                    <!-- Paleta rápida del sitio -->
                    <div class="vbp-field-group">
                        <span class="vbp-color-palette-label"><?php esc_html_e( 'Paleta del sitio', 'flavor-chat-ia' ); ?></span>
                        <div class="vbp-color-palette">
                            <template x-for="swatch in getSitePalette()" :key="swatch.label">
                                <button type="button"
                                        class="vbp-color-swatch"
                                        :style="{ backgroundColor: swatch.color }"
                                        :data-label="swatch.label"
                                        :title="swatch.label + ': ' + swatch.color"
                                        @click="applyColorFromPalette('colors.background', swatch.color)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Fondo', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-color-picker-enhanced">
                                <div class="vbp-color-palette vbp-color-palette-sm">
                                    <template x-for="swatch in getSitePalette()" :key="'bg-' + swatch.label">
                                        <button type="button"
                                                class="vbp-color-swatch"
                                                :class="{ 'active': isColorActive('colors.background', swatch.color) }"
                                                :style="{ backgroundColor: swatch.color }"
                                                :title="swatch.label"
                                                @click="applyColorFromPalette('colors.background', swatch.color)">
                                        </button>
                                    </template>
                                </div>
                                <div class="vbp-color-input-wrapper">
                                    <input type="color" :value="normalizeColorForInput(selectedElement.styles.colors.background, '#ffffff')" @input="updateStyle('colors.background', $event.target.value)" class="vbp-color-input">
                                    <input type="text" x-model="selectedElement.styles.colors.background" @input="updateStyle('colors.background', $event.target.value)" class="vbp-field-input" placeholder="#ffffff">
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-color-picker-enhanced">
                                <div class="vbp-color-palette vbp-color-palette-sm">
                                    <template x-for="swatch in getSitePalette()" :key="'txt-' + swatch.label">
                                        <button type="button"
                                                class="vbp-color-swatch"
                                                :class="{ 'active': isColorActive('colors.text', swatch.color) }"
                                                :style="{ backgroundColor: swatch.color }"
                                                :title="swatch.label"
                                                @click="applyColorFromPalette('colors.text', swatch.color)">
                                        </button>
                                    </template>
                                </div>
                                <div class="vbp-color-input-wrapper">
                                    <input type="color" :value="normalizeColorForInput(selectedElement.styles.colors.text, '#000000')" @input="updateStyle('colors.text', $event.target.value)" class="vbp-color-input">
                                    <input type="text" x-model="selectedElement.styles.colors.text" @input="updateStyle('colors.text', $event.target.value)" class="vbp-field-input" placeholder="#000000">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Typography -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Tipografía', 'flavor-chat-ia' ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.styles.typography.fontSize" @input="updateStyle('typography.fontSize', $event.target.value)" class="vbp-field-input" placeholder="16px">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Peso', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.styles.typography.fontWeight" @change="updateStyle('typography.fontWeight', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Normal', 'flavor-chat-ia' ); ?></option>
                                <option value="300">Light (300)</option>
                                <option value="400">Regular (400)</option>
                                <option value="500">Medium (500)</option>
                                <option value="600">Semibold (600)</option>
                                <option value="700">Bold (700)</option>
                            </select>
                        </div>
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Alineación', 'flavor-chat-ia' ); ?></label>
                        <div class="vbp-btn-group">
                            <button type="button" @click="updateStyle('typography.textAlign', 'left')" :class="{ 'active': selectedElement.styles.typography.textAlign === 'left' }" class="vbp-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
                            </button>
                            <button type="button" @click="updateStyle('typography.textAlign', 'center')" :class="{ 'active': selectedElement.styles.typography.textAlign === 'center' }" class="vbp-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                            </button>
                            <button type="button" @click="updateStyle('typography.textAlign', 'right')" :class="{ 'active': selectedElement.styles.typography.textAlign === 'right' }" class="vbp-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
                            </button>
                            <button type="button" @click="updateStyle('typography.textAlign', 'justify')" :class="{ 'active': selectedElement.styles.typography.textAlign === 'justify' }" class="vbp-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Borders -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Bordes', 'flavor-chat-ia' ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Radio', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.styles.borders.radius" @input="updateStyle('borders.radius', $event.target.value)" class="vbp-field-input" placeholder="0px">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ancho', 'flavor-chat-ia' ); ?></label>
                            <input type="text" x-model="selectedElement.styles.borders.width" @input="updateStyle('borders.width', $event.target.value)" class="vbp-field-input" placeholder="0px">
                        </div>
                    </div>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Estilo', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.styles.borders.style" @change="updateStyle('borders.style', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Ninguno', 'flavor-chat-ia' ); ?></option>
                                <option value="solid"><?php esc_html_e( 'Sólido', 'flavor-chat-ia' ); ?></option>
                                <option value="dashed"><?php esc_html_e( 'Discontinuo', 'flavor-chat-ia' ); ?></option>
                                <option value="dotted"><?php esc_html_e( 'Punteado', 'flavor-chat-ia' ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <input type="color" :value="normalizeColorForInput(selectedElement.styles.borders.color, '#333333')" @input="updateStyle('borders.color', $event.target.value)" class="vbp-color-input">
                                <input type="text" x-model="selectedElement.styles.borders.color" @input="updateStyle('borders.color', $event.target.value)" class="vbp-field-input">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shadows -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Sombras', 'flavor-chat-ia' ); ?></h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Presets', 'flavor-chat-ia' ); ?></label>
                        <div class="vbp-shadow-presets">
                            <button type="button" @click="updateStyle('shadows.boxShadow', 'none')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Ninguna', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-shadow-preview"></div>
                            </button>
                            <button type="button" @click="updateStyle('shadows.boxShadow', '0 1px 3px rgba(0,0,0,0.12)')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Suave', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-shadow-preview" style="box-shadow: 0 1px 3px rgba(0,0,0,0.12)"></div>
                            </button>
                            <button type="button" @click="updateStyle('shadows.boxShadow', '0 4px 6px rgba(0,0,0,0.1)')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Media', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-shadow-preview" style="box-shadow: 0 4px 6px rgba(0,0,0,0.1)"></div>
                            </button>
                            <button type="button" @click="updateStyle('shadows.boxShadow', '0 10px 25px rgba(0,0,0,0.15)')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Fuerte', 'flavor-chat-ia' ); ?>">
                                <div class="vbp-shadow-preview" style="box-shadow: 0 10px 25px rgba(0,0,0,0.15)"></div>
                            </button>
                        </div>
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Personalizado', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="selectedElement.styles.shadows.boxShadow" @input="updateStyle('shadows.boxShadow', $event.target.value)" class="vbp-field-input" placeholder="0 4px 6px rgba(0,0,0,0.1)">
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- Tab: Avanzado -->
            <!-- ============================================ -->
            <div x-show="activeTab === 'advanced'" class="vbp-inspector-panel">
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Atributos', 'flavor-chat-ia' ); ?></h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'ID CSS', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="selectedElement.styles.advanced.cssId" @input="updateStyle('advanced.cssId', $event.target.value)" class="vbp-field-input" placeholder="mi-elemento">
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Clases CSS', 'flavor-chat-ia' ); ?></label>
                        <input type="text" x-model="selectedElement.styles.advanced.cssClasses" @input="updateStyle('advanced.cssClasses', $event.target.value)" class="vbp-field-input" placeholder="clase1 clase2">
                    </div>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'CSS Personalizado', 'flavor-chat-ia' ); ?></h4>
                    <div class="vbp-field-group">
                        <textarea x-model="selectedElement.styles.advanced.customCss" @input="updateStyle('advanced.customCss', $event.target.value)" class="vbp-field-textarea vbp-code-textarea" rows="8" placeholder=".selector {
    /* tus estilos */
}"></textarea>
                        <small class="vbp-field-hint"><?php esc_html_e( 'El CSS se aplicará solo a este elemento', 'flavor-chat-ia' ); ?></small>
                    </div>
                </div>

                <!-- Animaciones completas -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6l2.1 2.1M5.6 18.4l2.1-2.1m8.6-8.6l2.1-2.1"/></svg>
                        <?php esc_html_e( 'Animación de entrada', 'flavor-chat-ia' ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Efecto', 'flavor-chat-ia' ); ?></label>
                        <select x-model="selectedElement.styles.advanced.entranceAnimation" @change="updateStyle('advanced.entranceAnimation', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Ninguna', 'flavor-chat-ia' ); ?></option>
                            <optgroup label="<?php esc_attr_e( 'Fade', 'flavor-chat-ia' ); ?>">
                                <option value="fade-in"><?php esc_html_e( 'Fade In', 'flavor-chat-ia' ); ?></option>
                                <option value="fade-in-up"><?php esc_html_e( 'Fade In Up', 'flavor-chat-ia' ); ?></option>
                                <option value="fade-in-down"><?php esc_html_e( 'Fade In Down', 'flavor-chat-ia' ); ?></option>
                                <option value="fade-in-left"><?php esc_html_e( 'Fade In Left', 'flavor-chat-ia' ); ?></option>
                                <option value="fade-in-right"><?php esc_html_e( 'Fade In Right', 'flavor-chat-ia' ); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e( 'Zoom', 'flavor-chat-ia' ); ?>">
                                <option value="zoom-in"><?php esc_html_e( 'Zoom In', 'flavor-chat-ia' ); ?></option>
                                <option value="zoom-out"><?php esc_html_e( 'Zoom Out', 'flavor-chat-ia' ); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e( 'Bounce', 'flavor-chat-ia' ); ?>">
                                <option value="bounce-in"><?php esc_html_e( 'Bounce In', 'flavor-chat-ia' ); ?></option>
                                <option value="bounce-in-up"><?php esc_html_e( 'Bounce In Up', 'flavor-chat-ia' ); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e( 'Especiales', 'flavor-chat-ia' ); ?>">
                                <option value="rotate-in"><?php esc_html_e( 'Rotate In', 'flavor-chat-ia' ); ?></option>
                                <option value="flip-in-x"><?php esc_html_e( 'Flip In X', 'flavor-chat-ia' ); ?></option>
                                <option value="flip-in-y"><?php esc_html_e( 'Flip In Y', 'flavor-chat-ia' ); ?></option>
                            </optgroup>
                        </select>
                    </div>
                    <template x-if="selectedElement.styles.advanced.entranceAnimation">
                        <div class="vbp-animation-options">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Disparador', 'flavor-chat-ia' ); ?></label>
                                <div class="vbp-btn-group vbp-btn-group-full">
                                    <button type="button" @click="updateStyle('advanced.animTrigger', 'load')" :class="{ 'active': (selectedElement.styles.advanced.animTrigger || 'scroll') === 'load' }" class="vbp-btn-toggle">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
                                        <?php esc_html_e( 'Al cargar', 'flavor-chat-ia' ); ?>
                                    </button>
                                    <button type="button" @click="updateStyle('advanced.animTrigger', 'scroll')" :class="{ 'active': (selectedElement.styles.advanced.animTrigger || 'scroll') === 'scroll' }" class="vbp-btn-toggle">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                                        <?php esc_html_e( 'Al hacer scroll', 'flavor-chat-ia' ); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Duración', 'flavor-chat-ia' ); ?></label>
                                    <select x-model="selectedElement.styles.advanced.animDuration" @change="updateStyle('advanced.animDuration', $event.target.value)" class="vbp-field-select">
                                        <option value="0.3s"><?php esc_html_e( 'Rápida (0.3s)', 'flavor-chat-ia' ); ?></option>
                                        <option value="0.6s"><?php esc_html_e( 'Normal (0.6s)', 'flavor-chat-ia' ); ?></option>
                                        <option value="1s"><?php esc_html_e( 'Lenta (1s)', 'flavor-chat-ia' ); ?></option>
                                        <option value="1.5s"><?php esc_html_e( 'Muy lenta (1.5s)', 'flavor-chat-ia' ); ?></option>
                                    </select>
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Retardo', 'flavor-chat-ia' ); ?></label>
                                    <select x-model="selectedElement.styles.advanced.animDelay" @change="updateStyle('advanced.animDelay', $event.target.value)" class="vbp-field-select">
                                        <option value="0s"><?php esc_html_e( 'Sin retardo', 'flavor-chat-ia' ); ?></option>
                                        <option value="0.2s">0.2s</option>
                                        <option value="0.4s">0.4s</option>
                                        <option value="0.6s">0.6s</option>
                                        <option value="0.8s">0.8s</option>
                                        <option value="1s">1s</option>
                                    </select>
                                </div>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Easing', 'flavor-chat-ia' ); ?></label>
                                <select x-model="selectedElement.styles.advanced.animEasing" @change="updateStyle('advanced.animEasing', $event.target.value)" class="vbp-field-select">
                                    <option value="ease"><?php esc_html_e( 'Ease (suave)', 'flavor-chat-ia' ); ?></option>
                                    <option value="ease-in"><?php esc_html_e( 'Ease In', 'flavor-chat-ia' ); ?></option>
                                    <option value="ease-out"><?php esc_html_e( 'Ease Out', 'flavor-chat-ia' ); ?></option>
                                    <option value="ease-in-out"><?php esc_html_e( 'Ease In Out', 'flavor-chat-ia' ); ?></option>
                                    <option value="linear"><?php esc_html_e( 'Linear', 'flavor-chat-ia' ); ?></option>
                                    <option value="bounce"><?php esc_html_e( 'Bounce (rebote)', 'flavor-chat-ia' ); ?></option>
                                    <option value="elastic"><?php esc_html_e( 'Elastic (elástico)', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 12l5-5"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php esc_html_e( 'Animación hover', 'flavor-chat-ia' ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Efecto al pasar el cursor', 'flavor-chat-ia' ); ?></label>
                        <select x-model="selectedElement.styles.advanced.hoverAnimation" @change="updateStyle('advanced.hoverAnimation', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Ninguna', 'flavor-chat-ia' ); ?></option>
                            <option value="grow"><?php esc_html_e( 'Crecer', 'flavor-chat-ia' ); ?></option>
                            <option value="shrink"><?php esc_html_e( 'Encoger', 'flavor-chat-ia' ); ?></option>
                            <option value="float"><?php esc_html_e( 'Flotar', 'flavor-chat-ia' ); ?></option>
                            <option value="pulse"><?php esc_html_e( 'Pulsar', 'flavor-chat-ia' ); ?></option>
                            <option value="wobble"><?php esc_html_e( 'Tambalear', 'flavor-chat-ia' ); ?></option>
                            <option value="swing"><?php esc_html_e( 'Balancear', 'flavor-chat-ia' ); ?></option>
                            <option value="glow"><?php esc_html_e( 'Brillar', 'flavor-chat-ia' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        <?php esc_html_e( 'Animación continua', 'flavor-chat-ia' ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Efecto en bucle', 'flavor-chat-ia' ); ?></label>
                        <select x-model="selectedElement.styles.advanced.loopAnimation" @change="updateStyle('advanced.loopAnimation', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Ninguna', 'flavor-chat-ia' ); ?></option>
                            <option value="spin"><?php esc_html_e( 'Girar', 'flavor-chat-ia' ); ?></option>
                            <option value="ping"><?php esc_html_e( 'Ping', 'flavor-chat-ia' ); ?></option>
                            <option value="bounce"><?php esc_html_e( 'Rebotar', 'flavor-chat-ia' ); ?></option>
                            <option value="shake"><?php esc_html_e( 'Agitar', 'flavor-chat-ia' ); ?></option>
                            <option value="heartbeat"><?php esc_html_e( 'Latido', 'flavor-chat-ia' ); ?></option>
                            <option value="blink"><?php esc_html_e( 'Parpadear', 'flavor-chat-ia' ); ?></option>
                        </select>
                    </div>
                    <template x-if="selectedElement.styles.advanced.loopAnimation">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Velocidad', 'flavor-chat-ia' ); ?></label>
                            <select x-model="selectedElement.styles.advanced.loopDuration" @change="updateStyle('advanced.loopDuration', $event.target.value)" class="vbp-field-select">
                                <option value="0.5s"><?php esc_html_e( 'Muy rápida', 'flavor-chat-ia' ); ?></option>
                                <option value="1s"><?php esc_html_e( 'Rápida', 'flavor-chat-ia' ); ?></option>
                                <option value="2s"><?php esc_html_e( 'Normal', 'flavor-chat-ia' ); ?></option>
                                <option value="3s"><?php esc_html_e( 'Lenta', 'flavor-chat-ia' ); ?></option>
                            </select>
                        </div>
                    </template>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V8M5 12l7-10 7 10"/></svg>
                        <?php esc_html_e( 'Parallax', 'flavor-chat-ia' ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-checkbox-label">
                            <input type="checkbox" x-model="selectedElement.styles.advanced.parallaxEnabled" @change="updateStyle('advanced.parallaxEnabled', selectedElement.styles.advanced.parallaxEnabled)">
                            <?php esc_html_e( 'Activar efecto parallax', 'flavor-chat-ia' ); ?>
                        </label>
                    </div>
                    <template x-if="selectedElement.styles.advanced.parallaxEnabled">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Intensidad', 'flavor-chat-ia' ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="0.1" max="0.8" step="0.1" x-model="selectedElement.styles.advanced.parallaxSpeed" @input="updateStyle('advanced.parallaxSpeed', $event.target.value)" class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.styles.advanced.parallaxSpeed || 0.3)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="vbp-inspector-section vbp-danger-zone">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Zona de peligro', 'flavor-chat-ia' ); ?></h4>
                    <button type="button" @click="deleteCurrentElement()" class="vbp-btn vbp-btn-danger vbp-btn-block">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        <?php esc_html_e( 'Eliminar elemento', 'flavor-chat-ia' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
