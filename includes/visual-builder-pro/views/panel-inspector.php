<?php
/**
 * Visual Builder Pro - Panel Inspector Completo
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-inspector-container" x-data="vbpInspector()" :data-inspector-mode="$store.vbp.inspectorMode" x-effect="if ($store.vbp.inspectorMode === 'basic' && activeTab !== 'content' && activeTab !== 'styles') activeTab = 'content'">
    <template x-if="selectionCount === 0">
        <div class="vbp-inspector-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01"/>
            </svg>
            <p><?php esc_html_e( 'Selecciona un elemento para editarlo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
            <small><?php esc_html_e( 'Haz clic en un bloque del canvas para ver sus ajustes.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
        </div>
    </template>

    <template x-if="hasMultipleSelection">
        <div class="vbp-inspector-empty vbp-inspector-empty--multi">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                <rect x="3" y="5" width="10" height="10" rx="2"></rect>
                <rect x="11" y="9" width="10" height="10" rx="2"></rect>
            </svg>
            <p><span x-text="selectionCount"></span> <?php esc_html_e( 'elementos seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
            <small><?php esc_html_e( 'La edición detallada del inspector se activa con una sola selección. Aquí puedes aplicar acciones rápidas al conjunto.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
            <div class="vbp-inspector-multi-summary" x-show="selectedElementsSummary">
                <span class="vbp-inspector-multi-summary__label"><?php esc_html_e( 'Selección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <span class="vbp-inspector-multi-summary__text" x-text="selectedElementsSummary"></span>
            </div>
            <div class="vbp-inspector-multi-actions">
                <button type="button" class="vbp-btn vbp-btn-secondary" @click="clearSelection()">
                    <?php esc_html_e( 'Deseleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="vbp-btn vbp-btn-danger" @click="deleteSelectedElements()">
                    <?php esc_html_e( 'Eliminar selección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>
    </template>

    <template x-if="selectedElement">
        <div class="vbp-inspector-content">
            <!-- Header con tipo de elemento -->
            <div class="vbp-inspector-header">
                <div class="vbp-inspector-header-info">
                    <span class="vbp-inspector-type" x-text="getTypeName(selectedElement.type)"></span>
                    <span class="vbp-inspector-id" x-show="$store.vbp.inspectorMode === 'advanced'" x-text="selectedElement.id"></span>
                </div>
                <div class="vbp-inspector-actions" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <button type="button" @click="toggleVisibility()" class="vbp-btn-icon-sm" :title="selectedElement.visible ? '<?php esc_attr_e( 'Ocultar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'">
                        <svg x-show="selectedElement.visible" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="!selectedElement.visible" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                    <button type="button" @click="toggleLock()" class="vbp-btn-icon-sm" :title="selectedElement.locked ? '<?php esc_attr_e( 'Desbloquear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Bloquear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'">
                        <svg x-show="!selectedElement.locked" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        <svg x-show="selectedElement.locked" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 019.9-1"/></svg>
                    </button>
                </div>
            </div>

            <div class="vbp-inspector-basic-intro" x-show="$store.vbp.inspectorMode === 'basic'">
                <span class="vbp-inspector-basic-intro__eyebrow"><?php esc_html_e( 'Edición rápida', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <p><?php esc_html_e( 'Primero contenido y apariencia. La estructura, estados y ajustes técnicos quedan fuera del camino hasta que cambies a Avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
            </div>

            <div class="vbp-inspector-context-card" :class="{ 'is-structure': isStructuralSelection }">
                <div class="vbp-inspector-context-card__header">
                    <span class="vbp-inspector-context-card__eyebrow" x-text="selectionContextEyebrow"></span>
                    <span class="vbp-inspector-context-card__pill" x-text="$store.vbp.inspectorMode === 'basic' ? '<?php echo esc_js( __( 'Modo foco', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' : '<?php echo esc_js( __( 'Inspector completo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
                </div>
                <div class="vbp-inspector-context-card__title" x-text="selectionContextTitle"></div>
                <p class="vbp-inspector-context-card__text" x-text="selectionContextDescription"></p>
                <div class="vbp-inspector-breadcrumb" x-show="selectedElementPath.length > 0">
                    <span class="vbp-inspector-breadcrumb__label"><?php esc_html_e( 'Ruta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <div class="vbp-inspector-breadcrumb__trail">
                        <template x-for="(node, index) in selectedElementPath" :key="node.id + '-' + index">
                            <div class="vbp-inspector-breadcrumb__item">
                                <button type="button"
                                        class="vbp-inspector-breadcrumb__node"
                                        :class="{ 'is-current': selectedElement && node.id === selectedElement.id, 'is-root': node.id === 'root' }"
                                        @click="selectPathNode(node.id)"
                                        :disabled="node.id === 'root'">
                                    <span x-text="node.name || node.type || node.id"></span>
                                </button>
                                <span class="vbp-inspector-breadcrumb__separator" x-show="index < selectedElementPath.length - 1">/</span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="vbp-inspector-structure-quick" x-show="isStructuralSelection">
                    <span class="vbp-inspector-structure-quick__label"><?php esc_html_e( 'Acciones rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>

                    <div class="vbp-inspector-structure-quick__group" x-show="isContainerSelection">
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <button type="button" @click="setContainerWidthPreset('full')" :class="{ 'active': (selectedElement.data.max_width || 'full') === 'full' }" class="vbp-btn-toggle"><?php esc_html_e( 'Full', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            <button type="button" @click="setContainerWidthPreset('1200px')" :class="{ 'active': selectedElement.data.max_width === '1200px' }" class="vbp-btn-toggle">1200</button>
                            <button type="button" @click="setContainerWidthPreset('960px')" :class="{ 'active': selectedElement.data.max_width === '960px' }" class="vbp-btn-toggle">960</button>
                        </div>
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <button type="button" @click="setContainerAlignmentQuick('left')" :class="{ 'active': selectedElement.data.align === 'left' }" class="vbp-btn-toggle"><?php esc_html_e( 'Izq', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            <button type="button" @click="setContainerAlignmentQuick('center')" :class="{ 'active': !selectedElement.data.align || selectedElement.data.align === 'center' }" class="vbp-btn-toggle"><?php esc_html_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            <button type="button" @click="setContainerAlignmentQuick('right')" :class="{ 'active': selectedElement.data.align === 'right' }" class="vbp-btn-toggle"><?php esc_html_e( 'Der', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                        </div>
                        <button type="button" class="vbp-btn vbp-btn-secondary vbp-btn-sm" @click="toggleContainerFullHeightQuick()">
                            <span x-text="selectedElement.data.full_height ? '<?php echo esc_js( __( 'Quitar altura completa', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' : '<?php echo esc_js( __( 'Altura completa', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
                        </button>
                    </div>

                    <div class="vbp-inspector-structure-quick__group" x-show="isColumnsLikeSelection">
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <template x-for="n in [2, 3, 4]" :key="'quick-cols-' + n">
                                <button type="button" @click="setQuickColumnsCount(n)" :class="{ 'active': currentStructureColumnsCount === n }" class="vbp-btn-toggle" x-text="n + ' col'"></button>
                            </template>
                        </div>
                        <div class="vbp-inspector-structure-presets">
                            <button type="button" class="vbp-layout-preset" @click="applyStructureLayoutPreset('equal-2')" title="50 / 50">
                                <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 1"></span></div>
                            </button>
                            <button type="button" class="vbp-layout-preset" @click="applyStructureLayoutPreset('sidebar-left')" title="33 / 67">
                                <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 2"></span></div>
                            </button>
                            <button type="button" class="vbp-layout-preset" @click="applyStructureLayoutPreset('sidebar-right')" title="67 / 33">
                                <div class="vbp-preset-preview"><span style="flex: 2"></span><span style="flex: 1"></span></div>
                            </button>
                            <button type="button" class="vbp-layout-preset" @click="applyStructureLayoutPreset('equal-3')" title="33 / 33 / 34">
                                <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 1"></span><span style="flex: 1"></span></div>
                            </button>
                        </div>
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <button type="button" @click="setQuickGap('0px')" :class="{ 'active': (selectedElement.data.gap || '20px') === '0px' }" class="vbp-btn-toggle">0</button>
                            <button type="button" @click="setQuickGap('20px')" :class="{ 'active': (selectedElement.data.gap || '20px') === '20px' }" class="vbp-btn-toggle">20</button>
                            <button type="button" @click="setQuickGap('40px')" :class="{ 'active': selectedElement.data.gap === '40px' }" class="vbp-btn-toggle">40</button>
                        </div>
                        <button type="button" class="vbp-btn vbp-btn-secondary vbp-btn-sm" @click="toggleMobileStackQuick()">
                            <span x-text="selectedElement.data.stackOnMobile === false ? '<?php echo esc_js( __( 'Activar apilado móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' : '<?php echo esc_js( __( 'No apilar en móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
                        </button>
                    </div>

                    <div class="vbp-inspector-structure-quick__group" x-show="isGridSelection">
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <template x-for="n in [2, 3, 4]" :key="'quick-grid-' + n">
                                <button type="button" @click="setQuickColumnsCount(n)" :class="{ 'active': currentStructureColumnsCount === n && !selectedElement.data.auto_fit }" class="vbp-btn-toggle" x-text="n + ' col'"></button>
                            </template>
                        </div>
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <button type="button" @click="applyStructureLayoutPreset('grid-2')" class="vbp-btn-toggle"><?php esc_html_e( 'Grid 2', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            <button type="button" @click="applyStructureLayoutPreset('grid-3')" class="vbp-btn-toggle"><?php esc_html_e( 'Grid 3', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            <button type="button" @click="applyStructureLayoutPreset('grid-auto')" :class="{ 'active': selectedElement.data.auto_fit === 'auto-fit' }" class="vbp-btn-toggle"><?php esc_html_e( 'Auto-fit', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                        </div>
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <button type="button" @click="setQuickGap('12px')" :class="{ 'active': (selectedElement.data.gap || '') === '12px' }" class="vbp-btn-toggle">12</button>
                            <button type="button" @click="setQuickGap('20px')" :class="{ 'active': (selectedElement.data.gap || '') === '20px' }" class="vbp-btn-toggle">20</button>
                            <button type="button" @click="setQuickGap('32px')" :class="{ 'active': selectedElement.data.gap === '32px' }" class="vbp-btn-toggle">32</button>
                        </div>
                    </div>
                </div>
                <div class="vbp-inspector-context-card__actions">
                    <button type="button" class="vbp-btn vbp-btn-secondary" @click="focusSelectedElement()">
                        <?php esc_html_e( 'Ver en canvas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="vbp-btn vbp-btn-secondary" @click="duplicateCurrentElement()">
                        <?php esc_html_e( 'Duplicar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="vbp-btn vbp-btn-danger" @click="deleteCurrentElement()">
                        <?php esc_html_e( 'Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>

            <!-- Selector de Variantes -->
            <template x-if="hasVariants()">
                <div class="vbp-inspector-variants">
                    <label class="vbp-field-label vbp-variants-label"><?php esc_html_e( 'Variante', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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

            <!-- Toggle Modo Básico/Avanzado -->
            <div class="vbp-inspector-mode-toggle" x-show="$store.vbp.inspectorMode === 'advanced'">
                <button type="button"
                        @click="setWorkspaceMode($store.vbp.inspectorMode === 'basic' ? 'advanced' : 'basic')"
                        class="vbp-mode-toggle-btn"
                        :class="{ 'is-advanced': $store.vbp.inspectorMode === 'advanced' }"
                        :title="$store.vbp.inspectorMode === 'basic' ? '<?php esc_attr_e( 'Cambiar a modo avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Cambiar a modo básico', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'">
                    <span class="vbp-mode-label" x-text="$store.vbp.inspectorMode === 'basic' ? '<?php esc_attr_e( 'Básico', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                    <span class="vbp-mode-icon">
                        <svg x-show="$store.vbp.inspectorMode === 'basic'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                        <svg x-show="$store.vbp.inspectorMode === 'advanced'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 00-2 2v.18a2 2 0 01-1 1.73l-.43.25a2 2 0 01-2 0l-.15-.08a2 2 0 00-2.73.73l-.22.38a2 2 0 00.73 2.73l.15.1a2 2 0 011 1.72v.51a2 2 0 01-1 1.74l-.15.09a2 2 0 00-.73 2.73l.22.38a2 2 0 002.73.73l.15-.08a2 2 0 012 0l.43.25a2 2 0 011 1.73V20a2 2 0 002 2h.44a2 2 0 002-2v-.18a2 2 0 011-1.73l.43-.25a2 2 0 012 0l.15.08a2 2 0 002.73-.73l.22-.39a2 2 0 00-.73-2.73l-.15-.08a2 2 0 01-1-1.74v-.5a2 2 0 011-1.74l.15-.09a2 2 0 00.73-2.73l-.22-.38a2 2 0 00-2.73-.73l-.15.08a2 2 0 01-2 0l-.43-.25a2 2 0 01-1-1.73V4a2 2 0 00-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                </button>
            </div>

            <!-- Tabs -->
            <div class="vbp-inspector-tabs">
                <button type="button" @click="activeTab = 'content'" :class="{ 'active': activeTab === 'content' }" class="vbp-inspector-tab">
                    <?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" @click="activeTab = 'styles'" :class="{ 'active': activeTab === 'styles' }" class="vbp-inspector-tab">
                    <span x-text="$store.vbp.inspectorMode === 'basic' ? '<?php esc_attr_e( 'Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Estilos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                </button>
                <button type="button" x-show="$store.vbp.inspectorMode === 'advanced'" @click="activeTab = 'advanced'" :class="{ 'active': activeTab === 'advanced' }" class="vbp-inspector-tab">
                    <?php esc_html_e( 'Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>

            <!-- ============================================ -->
            <!-- Tab: Contenido -->
            <!-- ============================================ -->
            <div x-show="activeTab === 'content'" class="vbp-inspector-panel">
                <!-- Nombre del elemento (común) -->
                <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <label class="vbp-field-label"><?php esc_html_e( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                    <input type="text" x-model="selectedElement.name" @input="updateElement('name', $event.target.value)" class="vbp-field-input">
                </div>

                <!-- ========== HEADING ========== -->
                <template x-if="selectedElement.type === 'heading'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">
                                <?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <template x-if="typeof VBP_Config !== 'undefined' && VBP_Config.ai && VBP_Config.ai.enabled">
                                    <button type="button" @click="$dispatch('vbp-ai-assist', { field: 'heading_text', content: selectedElement.data.text, element: selectedElement, type: 'hero_title' })" class="vbp-ai-field-btn" title="<?php esc_attr_e( 'Generar con IA', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        ✨
                                    </button>
                                </template>
                            </label>
                            <textarea x-model="selectedElement.data.text" @input="updateElementData('text', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-richtext-wrapper" :class="{ 'focused': isFocused }" style="position: relative;">
                                <!-- Toolbar fija -->
                                <div class="vbp-richtext-toolbar">
                                    <button type="button" @click="toggleBold()" :class="{ 'active': isFormatActive('bold') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Negrita (Ctrl+B)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
                                    </button>
                                    <button type="button" @click="toggleItalic()" :class="{ 'active': isFormatActive('italic') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Cursiva (Ctrl+I)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
                                    </button>
                                    <button type="button" @click="toggleUnderline()" :class="{ 'active': isFormatActive('underline') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Subrayado (Ctrl+U)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
                                    </button>
                                    <button type="button" @click="toggleStrike()" :class="{ 'active': isFormatActive('strikeThrough') }" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Tachado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.3 4.9c-2.3-.6-4.4-1-6.2-.9-2.7 0-5.3.7-5.3 3.6 0 1.5 1.1 2.6 3.7 3.2"/><path d="M4 12h16"/><path d="M6.7 19.1c2.3.6 4.4 1 6.2.9 2.7 0 5.3-.7 5.3-3.6 0-1.5-1.1-2.6-3.7-3.2"/></svg>
                                    </button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="insertLink()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Enlace (Ctrl+K)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                                    </button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="insertList(false)" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                                    </button>
                                    <button type="button" @click="insertList(true)" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Lista numerada', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="3" y="7" font-size="6" fill="currentColor">1</text><text x="3" y="13" font-size="6" fill="currentColor">2</text><text x="3" y="19" font-size="6" fill="currentColor">3</text></svg>
                                    </button>
                                    <span class="vbp-richtext-separator"></span>
                                    <button type="button" @click="undo()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Deshacer (Ctrl+Z)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 7"/></svg>
                                    </button>
                                    <button type="button" @click="redo()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Rehacer (Ctrl+Y)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M21 13a9 9 0 1 1-3-7.7L21 7"/></svg>
                                    </button>
                                    <button type="button" @click="clearFormat()" class="vbp-richtext-btn" data-tooltip="<?php esc_attr_e( 'Limpiar formato', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                    <template x-if="typeof VBP_Config !== 'undefined' && VBP_Config.ai && VBP_Config.ai.enabled">
                                        <span class="vbp-ai-toolbar-group" style="display:contents;">
                                            <span class="vbp-richtext-separator"></span>
                                            <button type="button" @click="$dispatch('vbp-ai-assist', { field: 'text', content: content, element: $store.vbp.selectedElement })" class="vbp-richtext-btn vbp-ai-btn" data-tooltip="<?php esc_attr_e( 'IA: Generar o mejorar texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                            </button>
                                        </span>
                                    </template>
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
                                     data-placeholder="<?php esc_attr_e( 'Escribe aquí... Usa **negrita**, *cursiva* o Ctrl+B, Ctrl+I', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-image-preview" x-show="selectedElement.data.src">
                                <img :src="selectedElement.data.src" alt="">
                                <button type="button" @click="updateElementData('src', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('src')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto alternativo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.alt" @input="updateElementData('alt', $event.target.value)" class="vbp-field-input" placeholder="Descripción de la imagen">
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Pie de foto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.caption" @input="updateElementData('caption', $event.target.value)" class="vbp-field-input">
                        </div>
                    </div>
                </template>

                <!-- ========== BUTTON ========== -->
                <template x-if="selectedElement.type === 'button'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">
                                <?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <template x-if="typeof VBP_Config !== 'undefined' && VBP_Config.ai && VBP_Config.ai.enabled">
                                    <button type="button" @click="$dispatch('vbp-ai-assist', { field: 'button_text', content: selectedElement.data.text, element: selectedElement, type: 'cta_button' })" class="vbp-ai-field-btn" title="<?php esc_attr_e( 'Generar con IA', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        ✨
                                    </button>
                                </template>
                            </label>
                            <input type="text" x-model="selectedElement.data.text" @input="updateElementData('text', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()" x-init="field = 'url'">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.url"
                                       @input="searchQuery = $event.target.value; updateElementData('url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="if ($event.detail.field === 'url') updateElementData('url', $event.detail.url)"
                                       class="vbp-field-input"
                                       :class="getValidationClass()"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar o pega URL...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <!-- Error de validación -->
                                <div class="vbp-field-error" x-show="hasValidationError()" x-text="validationError" x-cloak></div>
                                <!-- Botones de acciones rápidas -->
                                <div class="vbp-link-actions" x-show="$store.vbp.inspectorMode === 'advanced'">
                                    <button type="button" @click="openFileSelector()" class="vbp-btn-icon vbp-btn-xs" title="<?php esc_attr_e( 'Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">📎</button>
                                    <button type="button" @click="insertAnchor()" class="vbp-btn-icon vbp-btn-xs" title="<?php esc_attr_e( 'Ancla', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">⚓</button>
                                    <button type="button" @click="insertEmail()" class="vbp-btn-icon vbp-btn-xs" title="<?php esc_attr_e( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">✉️</button>
                                    <button type="button" @click="insertPhone()" class="vbp-btn-icon vbp-btn-xs" title="<?php esc_attr_e( 'Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">📞</button>
                                </div>
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading">
                                        <?php esc_html_e( 'Buscando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
                                    </template>
                                    <template x-for="(result, idx) in results" :key="result.id || idx">
                                        <button type="button"
                                                class="vbp-autocomplete-item"
                                                :class="{ 'active': activeIndex === idx }"
                                                @click="selectResult(result)">
                                            <span class="vbp-autocomplete-icon" x-text="getTypeIcon(result.type)"></span>
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
                            <div class="vbp-field-group vbp-field-half" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Abrir en', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.target" @change="updateElementData('target', $event.target.value)" class="vbp-field-select">
                                    <option value="_self"><?php esc_html_e( 'Misma ventana', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="_blank"><?php esc_html_e( 'Nueva ventana', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.style" @change="updateElementData('style', $event.target.value)" class="vbp-field-select">
                                    <option value="filled"><?php esc_html_e( 'Relleno', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="outline"><?php esc_html_e( 'Contorno', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="ghost"><?php esc_html_e( 'Ghost', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('align', 'left')" :class="{ 'active': selectedElement.data.align === 'left' }" class="vbp-btn-icon"><?php esc_html_e( 'Izq', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('align', 'center')" :class="{ 'active': selectedElement.data.align === 'center' }" class="vbp-btn-icon"><?php esc_html_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('align', 'right')" :class="{ 'active': selectedElement.data.align === 'right' }" class="vbp-btn-icon"><?php esc_html_e( 'Der', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== HERO ========== -->
                <template x-if="selectedElement.type === 'hero'">
                    <div class="vbp-inspector-section">
                        <!-- Contenido -->
                        <h4 class="vbp-section-title">📝 <?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">
                                <?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <template x-if="typeof VBP_Config !== 'undefined' && VBP_Config.ai && VBP_Config.ai.enabled">
                                    <button type="button" @click="$dispatch('vbp-ai-assist', { field: 'hero_titulo', content: selectedElement.data.titulo, element: selectedElement, type: 'hero_title' })" class="vbp-ai-field-btn" title="<?php esc_attr_e( 'Generar con IA', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        ✨
                                    </button>
                                </template>
                            </label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpToolbarEditor()" x-init="content = selectedElement.data.subtitulo || ''; field = 'hero_subtitulo'" @richtext-change.window="if ($event.detail.field === 'hero_subtitulo') updateElementData('subtitulo', $event.detail.content)">
                            <label class="vbp-field-label">
                                <?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <template x-if="typeof VBP_Config !== 'undefined' && VBP_Config.ai && VBP_Config.ai.enabled">
                                    <button type="button" @click="$dispatch('vbp-ai-assist', { field: 'hero_subtitulo', content: selectedElement.data.subtitulo, element: selectedElement, type: 'hero_subtitle' })" class="vbp-ai-field-btn" title="<?php esc_attr_e( 'Generar con IA', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        ✨
                                    </button>
                                </template>
                            </label>
                            <div class="vbp-richtext-wrapper compact">
                                <div class="vbp-richtext-toolbar">
                                    <button type="button" @click="toggleBold()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg></button>
                                    <button type="button" @click="toggleItalic()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
                                    <button type="button" @click="insertLink()" class="vbp-richtext-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg></button>
                                </div>
                                <div x-ref="editor" contenteditable="true" class="vbp-richtext-editor" @input="updateContent()" @focus="handleFocus()" @blur="handleBlur()" @paste="handlePaste($event)" x-html="content"></div>
                            </div>
                        </div>

                        <!-- Colores de texto -->
                        <h4 class="vbp-section-title" x-show="$store.vbp.inspectorMode === 'advanced'">🎨 <?php esc_html_e( 'Colores de Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.titulo_color || '#ffffff')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <input type="color" :value="normalizeForInput(currentColor)" @input="selectColor($event.target.value); updateElementData('titulo_color', $event.target.value)" class="vbp-color-native">
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('titulo_color', $event.target.value)" class="vbp-field-input vbp-color-text">
                                    <button type="button" class="vbp-color-dropdown-btn" @click="togglePicker()" title="<?php esc_attr_e( 'Paleta de colores', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">▼</button>
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-section">
                                            <span class="vbp-color-section-label"><?php esc_html_e( 'Colores del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                            <div class="vbp-color-picker-grid vbp-site-colors">
                                                <template x-for="(item, siteColorIndex) in siteColors" :key="'site-color-' + siteColorIndex">
                                                    <button type="button" class="vbp-color-preset" :class="{ 'active': isActive(item.color) }" :style="{ backgroundColor: item.color }" :title="item.label" @click="selectColor(item.color); updateElementData('titulo_color', item.color)"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="vbp-color-section">
                                            <span class="vbp-color-section-label"><?php esc_html_e( 'Colores comunes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                            <div class="vbp-color-picker-grid">
                                                <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                    <button type="button" class="vbp-color-preset" :class="{ 'active': isActive(color) }" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('titulo_color', color)"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.subtitulo_color || '#e0e0e0')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <input type="color" :value="normalizeForInput(currentColor)" @input="selectColor($event.target.value); updateElementData('subtitulo_color', $event.target.value)" class="vbp-color-native">
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('subtitulo_color', $event.target.value)" class="vbp-field-input vbp-color-text">
                                    <button type="button" class="vbp-color-dropdown-btn" @click="togglePicker()" title="<?php esc_attr_e( 'Paleta de colores', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">▼</button>
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-section">
                                            <span class="vbp-color-section-label"><?php esc_html_e( 'Colores del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                            <div class="vbp-color-picker-grid vbp-site-colors">
                                                <template x-for="(item, siteColorIndex) in siteColors" :key="'site-color-' + siteColorIndex">
                                                    <button type="button" class="vbp-color-preset" :class="{ 'active': isActive(item.color) }" :style="{ backgroundColor: item.color }" :title="item.label" @click="selectColor(item.color); updateElementData('subtitulo_color', item.color)"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="vbp-color-section">
                                            <span class="vbp-color-section-label"><?php esc_html_e( 'Colores comunes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                            <div class="vbp-color-picker-grid">
                                                <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                    <button type="button" class="vbp-color-preset" :class="{ 'active': isActive(color) }" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('subtitulo_color', color)"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón Principal -->
                        <h4 class="vbp-section-title">🔘 <?php esc_html_e( 'Botón Principal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()" x-init="field = 'boton_url'">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.boton_url"
                                       @input="searchQuery = $event.target.value; updateElementData('boton_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="if ($event.detail.field === 'boton_url') updateElementData('boton_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading"><?php esc_html_e( 'Buscando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
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
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_color_fondo || '#3b82f6')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_color_fondo', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_color_fondo', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_color_texto || '#ffffff')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_color_texto', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_color_texto', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón Secundario -->
                        <h4 class="vbp-section-title">🔘 <?php esc_html_e( 'Botón Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_2_texto" @input="updateElementData('boton_2_texto', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Dejar vacío para ocultar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()" x-init="field = 'boton_2_url'">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.boton_2_url"
                                       @input="searchQuery = $event.target.value; updateElementData('boton_2_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="if ($event.detail.field === 'boton_2_url') updateElementData('boton_2_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_2_color_fondo || 'transparent')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_2_color_fondo', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_2_color_fondo', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_2_color_texto || '#ffffff')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_2_color_texto', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_2_color_texto', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_2_color_borde || '#ffffff')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color del borde', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_2_color_borde', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_2_color_borde', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fondo -->
                        <h4 class="vbp-section-title">🖼️ <?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>

                        <!-- Tipo de fondo -->
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group vbp-btn-group-3">
                                <button type="button"
                                        class="vbp-btn-group-item"
                                        :class="{ 'active': !selectedElement.data.fondo_tipo || selectedElement.data.fondo_tipo === 'imagen' }"
                                        @click="updateElementData('fondo_tipo', 'imagen')">
                                    🖼️ <?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button"
                                        class="vbp-btn-group-item"
                                        :class="{ 'active': selectedElement.data.fondo_tipo === 'video' }"
                                        @click="updateElementData('fondo_tipo', 'video')">
                                    🎬 <?php esc_html_e( 'Video', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button"
                                        class="vbp-btn-group-item"
                                        :class="{ 'active': selectedElement.data.fondo_tipo === 'color' }"
                                        @click="updateElementData('fondo_tipo', 'color')">
                                    🎨 <?php esc_html_e( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Imagen de fondo -->
                        <div class="vbp-field-group" x-show="!selectedElement.data.fondo_tipo || selectedElement.data.fondo_tipo === 'imagen'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-image-preview vbp-image-preview-bg" x-show="selectedElement.data.imagen_fondo" :style="{ backgroundImage: 'url(' + selectedElement.data.imagen_fondo + ')' }">
                                <button type="button" @click="updateElementData('imagen_fondo', '')" class="vbp-image-remove">×</button>
                            </div>
                            <div class="vbp-btn-row">
                                <button type="button" @click="openMediaLibrary('imagen_fondo')" class="vbp-btn vbp-btn-secondary vbp-btn-sm">
                                    📁 <?php esc_html_e( 'Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button" @click="openUnsplash(selectedElement)" class="vbp-btn vbp-btn-secondary vbp-btn-sm">
                                    📷 Unsplash
                                </button>
                            </div>
                        </div>

                        <!-- Video de fondo -->
                        <div x-show="selectedElement.data.fondo_tipo === 'video'" class="vbp-video-background-config">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fuente del video', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-btn-group vbp-btn-group-2">
                                    <button type="button"
                                            class="vbp-btn-group-item"
                                            :class="{ 'active': !selectedElement.data.video_fuente || selectedElement.data.video_fuente === 'url' }"
                                            @click="updateElementData('video_fuente', 'url')">
                                        🔗 URL
                                    </button>
                                    <button type="button"
                                            class="vbp-btn-group-item"
                                            :class="{ 'active': selectedElement.data.video_fuente === 'archivo' }"
                                            @click="updateElementData('video_fuente', 'archivo')">
                                        📁 <?php esc_html_e( 'Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- URL de video (YouTube, Vimeo, etc) -->
                            <div class="vbp-field-group" x-show="!selectedElement.data.video_fuente || selectedElement.data.video_fuente === 'url'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Video externo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="url"
                                       x-model="selectedElement.data.video_url"
                                       @input="updateElementData('video_url', $event.target.value)"
                                       class="vbp-field-input"
                                       placeholder="https://youtube.com/watch?v=... o https://vimeo.com/...">
                                <small class="vbp-field-hint"><?php esc_html_e( 'Usa una URL de YouTube, Vimeo o un archivo directo. Si prefieres un archivo de WordPress, cambia a "Archivo".', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                            </div>

                            <!-- Archivo de video desde biblioteca -->
                            <div class="vbp-field-group" x-show="selectedElement.data.video_fuente === 'archivo'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Video de la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-video-preview" x-show="selectedElement.data.video_archivo">
                                    <video :src="selectedElement.data.video_archivo" muted loop style="max-width: 100%; max-height: 120px; border-radius: 6px;"></video>
                                    <button type="button" @click="updateElementData('video_archivo', '')" class="vbp-image-remove">×</button>
                                </div>
                                <button type="button" @click="openMediaLibrary('video_archivo', 'video')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                    📁 <?php esc_html_e( 'Seleccionar video', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                            </div>

                            <!-- Imagen de respaldo para video -->
                            <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Imagen de respaldo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <small class="vbp-field-hint" style="margin-bottom: 8px; display: block;"><?php esc_html_e( 'Se muestra mientras carga el video o en móviles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                                <div class="vbp-image-preview vbp-image-preview-bg" x-show="selectedElement.data.video_poster" :style="{ backgroundImage: 'url(' + selectedElement.data.video_poster + ')' }">
                                    <button type="button" @click="updateElementData('video_poster', '')" class="vbp-image-remove">×</button>
                                </div>
                                <button type="button" @click="openMediaLibrary('video_poster')" class="vbp-btn vbp-btn-secondary vbp-btn-sm">
                                    <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                            </div>

                            <!-- Opciones de video -->
                            <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Opciones de reproducción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-checkbox-group">
                                    <label class="vbp-checkbox-label">
                                        <input type="checkbox"
                                               :checked="selectedElement.data.video_autoplay !== false"
                                               @change="updateElementData('video_autoplay', $event.target.checked)"
                                               class="vbp-checkbox">
                                        <span><?php esc_html_e( 'Reproducir automáticamente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    </label>
                                    <label class="vbp-checkbox-label">
                                        <input type="checkbox"
                                               :checked="selectedElement.data.video_loop !== false"
                                               @change="updateElementData('video_loop', $event.target.checked)"
                                               class="vbp-checkbox">
                                        <span><?php esc_html_e( 'Reproducir en bucle', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    </label>
                                    <label class="vbp-checkbox-label">
                                        <input type="checkbox"
                                               :checked="selectedElement.data.video_muted !== false"
                                               @change="updateElementData('video_muted', $event.target.checked)"
                                               class="vbp-checkbox">
                                        <span><?php esc_html_e( 'Sin sonido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.color_fondo || '#1a1a2e')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('color_fondo', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('color_fondo', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.overlay_color || 'rgba(0,0,0,0.5)')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('overlay_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('overlay_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Opacidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="range" x-model="selectedElement.data.overlay_opacity" @input="updateElementData('overlay_opacity', $event.target.value)" min="0" max="100" class="vbp-range-input">
                            </div>
                        </div>

                        <!-- Layout -->
                        <h4 class="vbp-section-title" x-show="$store.vbp.inspectorMode === 'advanced'">📐 <?php esc_html_e( 'Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Altura', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.altura" @change="updateElementData('altura', $event.target.value)" class="vbp-field-select">
                                    <option value="auto">Auto</option>
                                    <option value="50vh">50%</option>
                                    <option value="75vh">75%</option>
                                    <option value="100vh">100%</option>
                                </select>
                            </div>
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.alineacion" @change="updateElementData('alineacion', $event.target.value)" class="vbp-field-select">
                                    <option value="left"><?php esc_html_e( 'Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="center"><?php esc_html_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="right"><?php esc_html_e( 'Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== CTA ========== -->
                <template x-if="selectedElement.type === 'cta'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">
                                <?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <template x-if="typeof VBP_Config !== 'undefined' && VBP_Config.ai && VBP_Config.ai.enabled">
                                    <button type="button" @click="$dispatch('vbp-ai-assist', { field: 'cta_titulo', content: selectedElement.data.titulo, element: selectedElement, type: 'cta_title' })" class="vbp-ai-field-btn" title="<?php esc_attr_e( 'Generar con IA', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        ✨
                                    </button>
                                </template>
                            </label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpToolbarEditor()" x-init="content = selectedElement.data.subtitulo || ''; field = 'cta_subtitulo'" @richtext-change.window="if ($event.detail.field === 'cta_subtitulo') updateElementData('subtitulo', $event.detail.content)">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()" x-init="field = 'boton_url'">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.boton_url"
                                       @input="searchQuery = $event.target.value; updateElementData('boton_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="if ($event.detail.field === 'boton_url') updateElementData('boton_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading"><?php esc_html_e( 'Buscando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Lista de características', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('features')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('items') === 0">
                            <p><?php esc_html_e( 'Añade características una a una. En modo básico te centras en título y descripción; el icono queda en avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="item.icono || '✨'"></span>
                                        <span class="vbp-item-title" x-text="item.titulo || '<?php esc_attr_e( 'Característica', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <div class="vbp-field-with-selector">
                                                <input type="text" x-model="item.icono" @input="updateItem(index, 'icono', $event.target.value)" class="vbp-field-input" placeholder="home">
                                                <button type="button" @click="openIconSelectorForItem(index, 'icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('testimonials')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-avatar" x-text="(item.autor || 'U').charAt(0)"></span>
                                        <span class="vbp-item-title" x-text="item.autor || '<?php esc_attr_e( 'Autor', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Foto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <div class="vbp-image-preview vbp-image-preview-small vbp-image-preview-round" x-show="item.foto">
                                                <img :src="item.foto" alt="">
                                                <button type="button" @click="updateItem(index, 'foto', '')" class="vbp-image-remove" title="<?php esc_attr_e( 'Eliminar foto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">×</button>
                                            </div>
                                            <button type="button" @click="openMediaLibraryForItem(index, 'foto')" class="vbp-btn vbp-btn-secondary vbp-btn-sm vbp-btn-block">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                                <span x-text="item.foto ? '<?php echo esc_js( __( 'Cambiar foto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' : '<?php echo esc_js( __( 'Seleccionar foto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
                                            </button>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Testimonio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.texto" @input="updateItem(index, 'texto', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                        <div class="vbp-field-row">
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Autor', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                                <input type="text" x-model="item.autor" @input="updateItem(index, 'autor', $event.target.value)" class="vbp-field-input">
                                            </div>
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Planes y precios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('pricing')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('items') === 0">
                            <p><?php esc_html_e( 'Crea los planes que quieras mostrar. Los ajustes comerciales finos, como destacado o período, quedan en avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index, 'highlighted': item.destacado }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-price">$<span x-text="item.precio || '0'"></span></span>
                                        <span class="vbp-item-title" x-text="item.nombre || '<?php esc_attr_e( 'Plan', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-row">
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                                <input type="text" x-model="item.nombre" @input="updateItem(index, 'nombre', $event.target.value)" class="vbp-field-input">
                                            </div>
                                            <div class="vbp-field-group vbp-field-half" x-show="$store.vbp.inspectorMode === 'advanced'">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Precio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                                <input type="text" x-model="item.precio" @input="updateItem(index, 'precio', $event.target.value)" class="vbp-field-input">
                                            </div>
                                        </div>
                                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.periodo" @input="updateItem(index, 'periodo', $event.target.value)" class="vbp-field-input" placeholder="/mes">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Características (una por línea)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.caracteristicas_text" @input="updatePricingFeatures(index, $event.target.value)" class="vbp-field-textarea" rows="4" :placeholder="'<?php esc_attr_e( 'Característica 1\nCaracterística 2\nCaracterística 3', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></textarea>
                                        </div>
                                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                            <label class="vbp-checkbox-label">
                                                <input type="checkbox" x-model="item.destacado" @change="updateItem(index, 'destacado', item.destacado)">
                                                <?php esc_html_e( 'Plan destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('faq')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('items') === 0">
                            <p><?php esc_html_e( 'Añade preguntas y respuestas. En básico solo editas el contenido; el comportamiento por defecto vive en avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon">❓</span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="item.pregunta || '<?php esc_attr_e( 'Pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.pregunta" @input="updateItem(index, 'pregunta', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje de éxito', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_exito" @input="updateElementData('mensaje_exito', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Campos del formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('form')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(campo, index) in getEditableCollection('campos')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="campo.tipo === 'textarea' ? '📝' : (campo.tipo === 'email' ? '📧' : (campo.tipo === 'tel' ? '📞' : (campo.tipo === 'select' ? '📋' : (campo.tipo === 'checkbox' ? '☑️' : '📄'))))"></span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="campo.label || '<?php esc_attr_e( 'Campo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <span class="vbp-item-badge" x-show="campo.requerido">*</span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveCampo(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveCampo(index, 1)" :disabled="index === getEditableCollectionLength('campos') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeCampo(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo de campo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <select x-model="campo.tipo" @change="updateCampo(index, 'tipo', $event.target.value)" class="vbp-field-select">
                                                <option value="text"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                                <option value="email"><?php esc_html_e( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                                <option value="tel"><?php esc_html_e( 'Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                                <option value="number"><?php esc_html_e( 'Número', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                                <option value="textarea"><?php esc_html_e( 'Área de texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                                <option value="select"><?php esc_html_e( 'Selector', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                                <option value="checkbox"><?php esc_html_e( 'Checkbox', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                            </select>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="campo.label" @input="updateCampo(index, 'label', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group" x-show="campo.tipo !== 'checkbox' && campo.tipo !== 'select'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Placeholder', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="campo.placeholder" @input="updateCampo(index, 'placeholder', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group" x-show="campo.tipo === 'select'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Opciones (una por línea)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="campo.opciones_text" @input="updateCampo(index, 'opciones_text', $event.target.value)" class="vbp-field-textarea" rows="3" placeholder="<?php esc_attr_e( 'Opción 1&#10;Opción 2&#10;Opción 3', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-checkbox-label">
                                                <input type="checkbox" x-model="campo.requerido" @change="updateCampo(index, 'requerido', campo.requerido)">
                                                <?php esc_html_e( 'Campo obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('team')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-avatar" x-text="(item.nombre || 'M').charAt(0)"></span>
                                        <span class="vbp-item-title" x-text="item.nombre || '<?php esc_attr_e( 'Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.nombre" @input="updateItem(index, 'nombre', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.cargo" @input="updateItem(index, 'cargo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Biografía', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.bio" @input="updateItem(index, 'bio', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Foto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <div class="vbp-image-preview vbp-image-preview-small" x-show="item.foto">
                                                <img :src="item.foto" alt="">
                                                <button type="button" @click="updateItem(index, 'foto', '')" class="vbp-image-remove" title="<?php esc_attr_e( 'Eliminar foto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">×</button>
                                            </div>
                                            <button type="button" @click="openMediaLibraryForItem(index, 'foto')" class="vbp-btn vbp-btn-secondary vbp-btn-sm vbp-btn-block">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                                <span x-text="item.foto ? '<?php echo esc_js( __( 'Cambiar foto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' : '<?php echo esc_js( __( 'Seleccionar foto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
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
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('stats')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>

                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card vbp-item-card-inline" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-stat" x-text="item.numero || '0'"></span>
                                        <span class="vbp-item-title" x-text="item.label || '<?php esc_attr_e( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-row">
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Número', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                                <input type="text" x-model="item.numero" @input="updateItem(index, 'numero', $event.target.value)" class="vbp-field-input" placeholder="10K+">
                                            </div>
                                            <div class="vbp-field-group vbp-field-half">
                                                <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Galería de imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addGalleryImage()" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('items') === 0">
                            <p><?php esc_html_e( 'Empieza añadiendo imágenes. Después podrás reordenarlas o eliminarlas desde la cuadrícula.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>

                        <div class="vbp-gallery-grid">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Últimos artículos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.categoria" @change="updateElementData('categoria', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
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
                                <label class="vbp-field-label"><?php esc_html_e( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.cantidad" @change="updateElementData('cantidad', parseInt($event.target.value))" class="vbp-field-select">
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="6">6</option>
                                    <option value="9">9</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-btn-group vbp-btn-group-full">
                                    <template x-for="n in [2, 3, 4]">
                                        <button type="button" @click="updateElementData('columnas', n)" :class="{ 'active': selectedElement.data.columnas === n || (!selectedElement.data.columnas && n === 3) }" class="vbp-btn-toggle" x-text="n"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-row" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.ordenar_por" @change="updateElementData('ordenar_por', $event.target.value)" class="vbp-field-select">
                                    <option value="date"><?php esc_html_e( 'Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="title"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="rand"><?php esc_html_e( 'Aleatorio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="comment_count"><?php esc_html_e( 'Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.orden" @change="updateElementData('orden', $event.target.value)" class="vbp-field-select">
                                    <option value="DESC"><?php esc_html_e( 'Descendente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="ASC"><?php esc_html_e( 'Ascendente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Opciones de visualización', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_extracto !== false" @change="updateElementData('mostrar_extracto', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar extracto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_autor !== false" @change="updateElementData('mostrar_autor', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar autor', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_fecha !== false" @change="updateElementData('mostrar_fecha', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ========== CONTACT ========== -->
                <template x-if="selectedElement.type === 'contact'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Enviar mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje de éxito', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_exito" @input="updateElementData('mensaje_exito', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( '¡Mensaje enviado correctamente!', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </div>
                    </div>
                </template>

                <!-- ========== COUNTDOWN ========== -->
                <template x-if="selectedElement.type === 'countdown'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fecha fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="date" x-model="selectedElement.data.fecha" @input="updateElementData('fecha', $event.target.value)" class="vbp-field-input">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Hora', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="time" x-model="selectedElement.data.hora" @input="updateElementData('hora', $event.target.value)" class="vbp-field-input">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje al finalizar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_fin" @input="updateElementData('mensaje_fin', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-checkbox-group">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_dias" @change="updateElementData('mostrar_dias', selectedElement.data.mostrar_dias)">
                                    <?php esc_html_e( 'Días', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_horas" @change="updateElementData('mostrar_horas', selectedElement.data.mostrar_horas)">
                                    <?php esc_html_e( 'Horas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_minutos" @change="updateElementData('mostrar_minutos', selectedElement.data.mostrar_minutos)">
                                    <?php esc_html_e( 'Minutos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_segundos" @change="updateElementData('mostrar_segundos', selectedElement.data.mostrar_segundos)">
                                    <?php esc_html_e( 'Segundos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== SOCIAL ICONS ========== -->
                <template x-if="selectedElement.type === 'social-icons'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="Síguenos">
                        </div>
                        <div class="vbp-field-row" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.estilo" @change="updateElementData('estilo', $event.target.value)" class="vbp-field-select">
                                    <option value="circle"><?php esc_html_e( 'Círculo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="square"><?php esc_html_e( 'Cuadrado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.tamano" @change="updateElementData('tamano', $event.target.value)" class="vbp-field-select">
                                    <option value="small"><?php esc_html_e( 'Pequeño', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="medium"><?php esc_html_e( 'Mediano', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="large"><?php esc_html_e( 'Grande', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('alineacion', 'flex-start')" :class="{ 'active': selectedElement.data.alineacion === 'flex-start' }" class="vbp-btn-icon"><?php esc_html_e( 'Izq', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'center')" :class="{ 'active': selectedElement.data.alineacion === 'center' || !selectedElement.data.alineacion }" class="vbp-btn-icon"><?php esc_html_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'flex-end')" :class="{ 'active': selectedElement.data.alineacion === 'flex-end' }" class="vbp-btn-icon"><?php esc_html_e( 'Der', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            </div>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Perfiles sociales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('social-icons')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('redes') === 0">
                            <p><?php esc_html_e( 'Añade cada red con su nombre y URL. El estilo visual y la alineación están en avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(red, index) in getEditableCollection('redes')" :key="index">
                                <div class="vbp-item-card vbp-item-card-inline" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="red.icono || '🔗'"></span>
                                        <span class="vbp-item-title" x-text="red.red || '<?php esc_attr_e( 'Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="removeSocialItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <div class="vbp-field-with-selector">
                                                <input type="text" x-model="red.icono" @input="updateSocialItem(index, 'icono', $event.target.value)" class="vbp-field-input" placeholder="link">
                                                <button type="button" @click="openIconSelectorForSocial(index)" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="red.red" @input="updateSocialItem(index, 'red', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <textarea x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Placeholder email', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.placeholder_email" @input="updateElementData('placeholder_email', $event.target.value)" class="vbp-field-input" placeholder="tu@email.com">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.boton_texto" @input="updateElementData('boton_texto', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.mostrar_nombre" @change="updateElementData('mostrar_nombre', selectedElement.data.mostrar_nombre)">
                                <?php esc_html_e( 'Mostrar campo de nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje de éxito', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.mensaje_exito" @input="updateElementData('mensaje_exito', $event.target.value)" class="vbp-field-input">
                        </div>
                    </div>
                </template>

                <!-- ========== LOGO GRID ========== -->
                <template x-if="selectedElement.type === 'logo-grid'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="Confían en nosotros">
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group vbp-btn-group-full">
                                <template x-for="n in [3, 4, 5, 6]">
                                    <button type="button" @click="updateElementData('columnas', n)" :class="{ 'active': selectedElement.data.columnas === n }" class="vbp-btn-toggle" x-text="n"></button>
                                </template>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.escala_grises" @change="updateElementData('escala_grises', selectedElement.data.escala_grises)">
                                <?php esc_html_e( 'Escala de grises', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.hover_color" @change="updateElementData('hover_color', selectedElement.data.hover_color)">
                                <?php esc_html_e( 'Color al pasar el ratón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Logos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addLogoImage()" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-gallery-grid vbp-logo-grid-preview">
                            <template x-for="(logo, index) in getEditableCollection('logos')" :key="index">
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
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-field-with-selector">
                                <div class="vbp-icon-field-preview">
                                    <span class="vbp-icon-field-value" :class="{ 'material-type': /^[a-z_]+$/.test(selectedElement.data.icono) }">
                                        <span x-show="/^[a-z_]+$/.test(selectedElement.data.icono)" class="material-icons" x-text="selectedElement.data.icono"></span>
                                        <span x-show="!/^[a-z_]+$/.test(selectedElement.data.icono)" x-text="selectedElement.data.icono || '✨'"></span>
                                    </span>
                                </div>
                                <button type="button" @click="openIconSelector('icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                </button>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <textarea x-model="selectedElement.data.descripcion" @input="updateElementData('descripcion', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('alineacion', 'left')" :class="{ 'active': selectedElement.data.alineacion === 'left' }" class="vbp-btn-icon"><?php esc_html_e( 'Izq', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'center')" :class="{ 'active': selectedElement.data.alineacion === 'center' || !selectedElement.data.alineacion }" class="vbp-btn-icon"><?php esc_html_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('alineacion', 'right')" :class="{ 'active': selectedElement.data.alineacion === 'right' }" class="vbp-btn-icon"><?php esc_html_e( 'Der', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.enlace_texto" @input="updateElementData('enlace_texto', $event.target.value)" class="vbp-field-input" placeholder="Saber más">
                        </div>
                        <div class="vbp-field-group" x-data="vbpLinkAutocomplete()" x-init="field = 'enlace_url'" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'URL enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-link-autocomplete">
                                <input type="url"
                                       x-model="selectedElement.data.enlace_url"
                                       @input="searchQuery = $event.target.value; updateElementData('enlace_url', $event.target.value)"
                                       @keydown="handleKeydown($event)"
                                       @blur="closeDropdown()"
                                       @link-selected.window="if ($event.detail.field === 'enlace_url') updateElementData('enlace_url', $event.detail.url)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Escribe para buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-autocomplete-dropdown" x-show="isOpen" x-cloak>
                                    <div class="vbp-autocomplete-loading" x-show="isLoading"><?php esc_html_e( 'Buscando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
                                    <template x-if="!isLoading && results.length === 0">
                                        <div class="vbp-autocomplete-empty"><?php esc_html_e( 'No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
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
                        <!-- Campos de cabecera de sección -->
                        <div class="vbp-section-header-fields">
                            <h4 class="vbp-section-title"><?php esc_html_e( '📋 Cabecera de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Título opcional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Subtítulo opcional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-row" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.titulo_color, '#ffffff')" @input="updateElementData('titulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.subtitulo_color, '#9CA3AF')" @input="updateElementData('subtitulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                            </div>
                            <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="color" :value="normalizeColorForInput(selectedElement.data.color_fondo, '#0f0f0f')" @input="updateElementData('color_fondo', $event.target.value)" class="vbp-field-color">
                            </div>
                        </div>

                        <h4 class="vbp-section-title" x-show="$store.vbp.inspectorMode === 'advanced'"><?php esc_html_e( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.multiples_abiertos" @change="updateElementData('multiples_abiertos', selectedElement.data.multiples_abiertos)">
                                <?php esc_html_e( 'Permitir múltiples abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Items del acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('accordion')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('items') === 0">
                            <p><?php esc_html_e( 'Añade bloques desplegables de pregunta y respuesta. El estado inicial de apertura está en avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon" x-text="item.abierto ? '▼' : '▶'"></span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="item.titulo || '<?php esc_attr_e( 'Elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.contenido" @input="updateItem(index, 'contenido', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                            <label class="vbp-checkbox-label">
                                                <input type="checkbox" x-model="item.abierto" @change="updateItem(index, 'abierto', item.abierto)">
                                                <?php esc_html_e( 'Abierto por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
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
                        <!-- Campos de cabecera de sección -->
                        <div class="vbp-section-header-fields">
                            <h4 class="vbp-section-title"><?php esc_html_e( '📋 Cabecera de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Título opcional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Subtítulo opcional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-row" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.titulo_color, '#ffffff')" @input="updateElementData('titulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.subtitulo_color, '#9CA3AF')" @input="updateElementData('subtitulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                            </div>
                            <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="color" :value="normalizeColorForInput(selectedElement.data.color_fondo, '#0f0f0f')" @input="updateElementData('color_fondo', $event.target.value)" class="vbp-field-color">
                            </div>
                        </div>

                        <h4 class="vbp-section-title" x-show="$store.vbp.inspectorMode === 'advanced'"><?php esc_html_e( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.estilo" @change="updateElementData('estilo', $event.target.value)" class="vbp-field-select">
                                <option value="horizontal"><?php esc_html_e( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="vertical"><?php esc_html_e( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Contenido por pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('tabs')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-inline-help" x-show="getEditableCollectionLength('items') === 0">
                            <p><?php esc_html_e( 'Añade pestañas con su título y contenido. La orientación general del componente está en avanzado.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index, 'highlighted': selectedElement.data.tab_activa === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon">📑</span>
                                        <span class="vbp-item-title" x-text="item.titulo || '<?php esc_attr_e( 'Tab', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> ' + (index + 1)"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="updateElementData('tab_activa', index)" :class="{ 'active': selectedElement.data.tab_activa === index }" class="vbp-btn-icon-xs" title="<?php esc_attr_e( 'Activar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">★</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título de pestaña', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.contenido" @input="updateItem(index, 'contenido', $event.target.value)" class="vbp-field-textarea" rows="4"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== TIMELINE ========== -->
                <template x-if="selectedElement.type === 'timeline'">
                    <div class="vbp-inspector-section">
                        <!-- Campos de cabecera de sección -->
                        <div class="vbp-section-header-fields">
                            <h4 class="vbp-section-title"><?php esc_html_e( '📋 Cabecera de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Ej: Nuestra Historia', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Descripción breve', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.titulo_color, '#ffffff')" @input="updateElementData('titulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.subtitulo_color, '#9CA3AF')" @input="updateElementData('subtitulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="color" :value="normalizeColorForInput(selectedElement.data.color_fondo, '#1f2937')" @input="updateElementData('color_fondo', $event.target.value)" class="vbp-field-color">
                            </div>
                        </div>

                        <h4 class="vbp-section-title"><?php esc_html_e( '🎨 Estilo de Línea', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color línea', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="color" :value="normalizeColorForInput(selectedElement.data.color_linea, '#dc2626')" @input="updateElementData('color_linea', $event.target.value)" class="vbp-field-color">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color marcador', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="color" :value="normalizeColorForInput(selectedElement.data.color_marcador, '#dc2626')" @input="updateElementData('color_marcador', $event.target.value)" class="vbp-field-color">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.animacion_scroll" @change="updateElementData('animacion_scroll', selectedElement.data.animacion_scroll)">
                                <?php esc_html_e( 'Animar al hacer scroll', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( '📅 Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('timeline')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('eventos')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon">📍</span>
                                        <span class="vbp-item-title vbp-item-title-truncate">
                                            <strong x-text="item.fecha || '<?php esc_attr_e( 'Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></strong>
                                            <span x-text="' - ' + (item.titulo || '<?php esc_attr_e( 'Evento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>')"></span>
                                        </span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('eventos') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Fecha/Año', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.fecha" @input="updateItem(index, 'fecha', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Ej: 2019', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.descripcion" @input="updateItem(index, 'descripcion', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <div class="vbp-selector-field">
                                                <input type="text" x-model="item.icono" @input="updateItem(index, 'icono', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Nombre del icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                <button type="button" @click="openIconSelectorForTimeline(index, 'icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== CAROUSEL ========== -->
                <template x-if="selectedElement.type === 'carousel'">
                    <div class="vbp-inspector-section">
                        <!-- Campos de cabecera de sección -->
                        <div class="vbp-section-header-fields">
                            <h4 class="vbp-section-title"><?php esc_html_e( '📋 Cabecera de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Título opcional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.subtitulo" @input="updateElementData('subtitulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Subtítulo opcional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.titulo_color, '#ffffff')" @input="updateElementData('titulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="color" :value="normalizeColorForInput(selectedElement.data.subtitulo_color, '#9CA3AF')" @input="updateElementData('subtitulo_color', $event.target.value)" class="vbp-field-color">
                                </div>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="color" :value="normalizeColorForInput(selectedElement.data.color_fondo, '#0f0f0f')" @input="updateElementData('color_fondo', $event.target.value)" class="vbp-field-color">
                            </div>
                        </div>

                        <h4 class="vbp-section-title"><?php esc_html_e( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.autoplay" @change="updateElementData('autoplay', selectedElement.data.autoplay)">
                                <?php esc_html_e( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group" x-show="selectedElement.data.autoplay">
                            <label class="vbp-field-label"><?php esc_html_e( 'Intervalo (segundos)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="number" x-model="selectedElement.data.intervalo" @input="updateElementData('intervalo', parseInt($event.target.value) || 5)" class="vbp-field-input" min="1" max="30">
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_flechas" @change="updateElementData('mostrar_flechas', selectedElement.data.mostrar_flechas)">
                                    <?php esc_html_e( 'Flechas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.mostrar_dots" @change="updateElementData('mostrar_dots', selectedElement.data.mostrar_dots)">
                                    <?php esc_html_e( 'Indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.data.loop" @change="updateElementData('loop', selectedElement.data.loop)">
                                    <?php esc_html_e( 'Loop infinito', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Slides visibles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="number" x-model="selectedElement.data.slides_visibles" @input="updateElementData('slides_visibles', parseInt($event.target.value) || 1)" class="vbp-field-input" min="1" max="6">
                            </div>
                        </div>

                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( '🖼️ Slides', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('carousel')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-icon">🖼️</span>
                                        <span class="vbp-item-title vbp-item-title-truncate" x-text="item.titulo || '<?php esc_attr_e( 'Slide', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> ' + (index + 1)"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="moveItem(index, -1)" :disabled="index === 0" class="vbp-btn-icon-xs">↑</button>
                                            <button type="button" @click.stop="moveItem(index, 1)" :disabled="index === getEditableCollectionLength('items') - 1" class="vbp-btn-icon-xs">↓</button>
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <div class="vbp-image-field">
                                                <template x-if="item.imagen">
                                                    <div class="vbp-image-preview">
                                                        <img :src="item.imagen" alt="">
                                                        <button type="button" @click="updateItem(index, 'imagen', '')" class="vbp-btn-remove-image">×</button>
                                                    </div>
                                                </template>
                                                <button type="button" @click="openMediaLibraryForItem(index, 'imagen')" class="vbp-btn-secondary vbp-btn-sm">
                                                    <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.titulo" @input="updateItem(index, 'titulo', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <textarea x-model="item.descripcion" @input="updateItem(index, 'descripcion', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'URL del enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.enlace_url" @input="updateItem(index, 'enlace_url', $event.target.value)" class="vbp-field-input" placeholder="https://...">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Texto del enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.enlace_texto" @input="updateItem(index, 'enlace_texto', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
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
                                <?php esc_html_e( 'Mostrar porcentaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.animado" @change="updateElementData('animado', selectedElement.data.animado)">
                                <?php esc_html_e( 'Animación al cargar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-items-header">
                            <h4 class="vbp-section-title"><?php esc_html_e( 'Barras', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                            <button type="button" @click="addItem('progress-bar')" class="vbp-btn-add">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <div class="vbp-items-list">
                            <template x-for="(item, index) in getEditableCollection('items')" :key="index">
                                <div class="vbp-item-card" :class="{ 'active': editingItemIndex === index }">
                                    <div class="vbp-item-header" @click="toggleItemEdit(index)">
                                        <span class="vbp-item-stat" x-text="(item.porcentaje || 0) + '%'"></span>
                                        <span class="vbp-item-title" x-text="item.label || '<?php esc_attr_e( 'Skill', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></span>
                                        <div class="vbp-item-actions">
                                            <button type="button" @click.stop="removeItem(index)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                        </div>
                                    </div>
                                    <div class="vbp-item-content" x-show="editingItemIndex === index" x-collapse>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                            <input type="text" x-model="item.label" @input="updateItem(index, 'label', $event.target.value)" class="vbp-field-input">
                                        </div>
                                        <div class="vbp-field-group">
                                            <label class="vbp-field-label"><?php esc_html_e( 'Porcentaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.tipo" @change="updateElementData('tipo', $event.target.value)" class="vbp-field-select">
                                <option value="info"><?php esc_html_e( 'Información', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="success"><?php esc_html_e( 'Éxito', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="warning"><?php esc_html_e( 'Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="error"><?php esc_html_e( 'Error', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <textarea x-model="selectedElement.data.mensaje" @input="updateElementData('mensaje', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.icono" @change="updateElementData('icono', selectedElement.data.icono)">
                                <?php esc_html_e( 'Mostrar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.dismissible" @change="updateElementData('dismissible', selectedElement.data.dismissible)">
                                <?php esc_html_e( 'Permitir cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ========== BEFORE AFTER ========== -->
                <template x-if="selectedElement.type === 'before-after'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen "Antes"', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-image-preview" x-show="selectedElement.data.imagen_antes">
                                <img :src="selectedElement.data.imagen_antes" alt="">
                                <button type="button" @click="updateElementData('imagen_antes', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('imagen_antes')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta "Antes"', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.label_antes" @input="updateElementData('label_antes', $event.target.value)" class="vbp-field-input" placeholder="Antes">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Imagen "Después"', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-image-preview" x-show="selectedElement.data.imagen_despues">
                                <img :src="selectedElement.data.imagen_despues" alt="">
                                <button type="button" @click="updateElementData('imagen_despues', '')" class="vbp-image-remove">×</button>
                            </div>
                            <button type="button" @click="openMediaLibrary('imagen_despues')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Etiqueta "Después"', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.label_despues" @input="updateElementData('label_despues', $event.target.value)" class="vbp-field-input" placeholder="Después">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Orientación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.orientacion" @change="updateElementData('orientacion', $event.target.value)" class="vbp-field-select">
                                <option value="horizontal"><?php esc_html_e( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="vertical"><?php esc_html_e( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Posición inicial del slider', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.style" @change="updateElementData('style', $event.target.value)" class="vbp-field-select">
                                <option value="solid"><?php esc_html_e( 'Sólido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="dashed"><?php esc_html_e( 'Discontinuo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="dotted"><?php esc_html_e( 'Punteado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Grosor', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.width" @input="updateElementData('width', $event.target.value)" class="vbp-field-input" placeholder="1px">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Altura', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-field-with-selector">
                                <div class="vbp-icon-field-preview">
                                    <span class="vbp-icon-field-value">
                                        <span x-show="/^[a-z_]+$/.test(selectedElement.data.icon)" class="material-icons" x-text="selectedElement.data.icon"></span>
                                        <span x-show="!/^[a-z_]+$/.test(selectedElement.data.icon)" x-text="selectedElement.data.icon || '⭐'"></span>
                                    </span>
                                </div>
                                <button type="button" @click="openIconSelector('icon')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <span class="material-icons" style="font-size: 18px;">apps</span>
                                </button>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.size" @input="updateElementData('size', $event.target.value)" class="vbp-field-input" placeholder="48px">
                        </div>
                    </div>
                </template>

                <!-- ========== COLUMNS/ROW ========== -->
                <template x-if="selectedElement.type === 'columns' || selectedElement.type === 'row'">
                    <div class="vbp-inspector-section">
                        <!-- Presets rápidos -->
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Presets de layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-layout-presets">
                                <!-- 2 columnas -->
                                <button type="button" @click="applyColumnPreset([50, 50])" class="vbp-layout-preset" title="50% / 50%">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 1"></span></div>
                                </button>
                                <button type="button" @click="applyColumnPreset([33, 67])" class="vbp-layout-preset" title="33% / 67%">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 2"></span></div>
                                </button>
                                <button type="button" @click="applyColumnPreset([67, 33])" class="vbp-layout-preset" title="67% / 33%">
                                    <div class="vbp-preset-preview"><span style="flex: 2"></span><span style="flex: 1"></span></div>
                                </button>
                                <button type="button" @click="applyColumnPreset([25, 75])" class="vbp-layout-preset" title="25% / 75%">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 3"></span></div>
                                </button>
                                <button type="button" @click="applyColumnPreset([75, 25])" class="vbp-layout-preset" title="75% / 25%">
                                    <div class="vbp-preset-preview"><span style="flex: 3"></span><span style="flex: 1"></span></div>
                                </button>
                                <!-- 3 columnas -->
                                <button type="button" @click="applyColumnPreset([33, 33, 34])" class="vbp-layout-preset" title="33% / 33% / 33%">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 1"></span><span style="flex: 1"></span></div>
                                </button>
                                <button type="button" @click="applyColumnPreset([25, 50, 25])" class="vbp-layout-preset" title="25% / 50% / 25%">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 2"></span><span style="flex: 1"></span></div>
                                </button>
                                <button type="button" @click="applyColumnPreset([20, 60, 20])" class="vbp-layout-preset" title="20% / 60% / 20%">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 3"></span><span style="flex: 1"></span></div>
                                </button>
                                <!-- 4 columnas -->
                                <button type="button" @click="applyColumnPreset([25, 25, 25, 25])" class="vbp-layout-preset" title="4 columnas iguales">
                                    <div class="vbp-preset-preview"><span style="flex: 1"></span><span style="flex: 1"></span><span style="flex: 1"></span><span style="flex: 1"></span></div>
                                </button>
                            </div>
                        </div>

                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Número de columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group vbp-btn-group-full">
                                <template x-for="n in [2, 3, 4, 5, 6]">
                                    <button type="button" @click="updateColumnsCount(n)" :class="{ 'active': (selectedElement.data.columnas || selectedElement.data.columns || 2) == n }" class="vbp-btn-toggle" x-text="n"></button>
                                </template>
                            </div>
                            <small class="vbp-field-hint" x-show="$store.vbp.inspectorMode === 'basic'"><?php esc_html_e( 'Usa avanzado si necesitas anchos distintos por columna o presets complejos.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>

                        <!-- Anchos de columnas individuales -->
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Anchos de columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-columns-widths">
                                <template x-for="(width, index) in getColumnWidths()" :key="index">
                                    <div class="vbp-column-width-control">
                                        <span class="vbp-column-label" x-text="'Col ' + (index + 1)"></span>
                                        <input type="range"
                                               min="10"
                                               max="80"
                                               :value="parseFloat(width) || (100 / (selectedElement.data.columnas || selectedElement.data.columns || 2))"
                                               @input="updateColumnWidth(index, $event.target.value)"
                                               class="vbp-field-range vbp-field-range-sm">
                                        <span class="vbp-column-width-value" x-text="Math.round(parseFloat(width) || (100 / (selectedElement.data.columnas || selectedElement.data.columns || 2))) + '%'"></span>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="resetColumnWidths()" class="vbp-btn vbp-btn-ghost vbp-btn-sm">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                <?php esc_html_e( 'Igualar anchos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>

                        <!-- Gap entre columnas -->
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Espacio entre columnas (gap)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('verticalAlign', 'start')" :class="{ 'active': selectedElement.data.verticalAlign === 'start' || !selectedElement.data.verticalAlign }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">↑</button>
                                <button type="button" @click="updateElementData('verticalAlign', 'center')" :class="{ 'active': selectedElement.data.verticalAlign === 'center' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">⎯</button>
                                <button type="button" @click="updateElementData('verticalAlign', 'end')" :class="{ 'active': selectedElement.data.verticalAlign === 'end' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">↓</button>
                                <button type="button" @click="updateElementData('verticalAlign', 'stretch')" :class="{ 'active': selectedElement.data.verticalAlign === 'stretch' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Estirar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">↕</button>
                            </div>
                        </div>

                        <!-- Responsive: apilar en móvil -->
                        <div class="vbp-field-group" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.stackOnMobile !== false" @change="updateElementData('stackOnMobile', $event.target.checked)">
                                <?php esc_html_e( 'Apilar en móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>

                        <!-- Acciones de columnas -->
                        <div class="vbp-columns-actions" x-show="$store.vbp.inspectorMode === 'advanced'">
                            <button type="button" @click="reverseColumns()" class="vbp-btn vbp-btn-secondary vbp-btn-sm" title="<?php esc_attr_e( 'Invertir orden de columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg>
                                <?php esc_html_e( 'Invertir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                            <button type="button" @click="resetColumnWidths()" class="vbp-btn vbp-btn-secondary vbp-btn-sm" title="<?php esc_attr_e( 'Igualar anchos de columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12H3m18 0l-4-4m4 4l-4 4M3 12l4-4m-4 4l4 4"/></svg>
                                <?php esc_html_e( 'Igualar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- ========== CONTAINER ========== -->
                <template x-if="selectedElement.type === 'container'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ancho máximo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.max_width" @change="updateElementData('max_width', $event.target.value)" class="vbp-field-select">
                                <option value="full"><?php esc_html_e( 'Completo (100%)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="1400px">1400px</option>
                                <option value="1200px">1200px</option>
                                <option value="960px">960px</option>
                                <option value="720px">720px</option>
                                <option value="540px">540px</option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('align', 'left')" :class="{ 'active': selectedElement.data.align === 'left' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="12" height="18" rx="2"/></svg>
                                </button>
                                <button type="button" @click="updateElementData('align', 'center')" :class="{ 'active': selectedElement.data.align === 'center' || !selectedElement.data.align }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="3" width="12" height="18" rx="2"/></svg>
                                </button>
                                <button type="button" @click="updateElementData('align', 'right')" :class="{ 'active': selectedElement.data.align === 'right' }" class="vbp-btn-icon" title="<?php esc_attr_e( 'Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="12" height="18" rx="2"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" x-model="selectedElement.data.full_height" @change="updateElementData('full_height', selectedElement.data.full_height)">
                                <?php esc_html_e( 'Altura completa (100vh)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <small class="vbp-field-hint"><?php esc_html_e( 'Arrastra elementos dentro de este contenedor', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                    </div>
                </template>

                <!-- ========== GRID ========== -->
                <template x-if="selectedElement.type === 'grid'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="number" min="1" max="12" x-model="selectedElement.data.columnas" @input="updateElementData('columnas', parseInt($event.target.value))" class="vbp-field-input" placeholder="3">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Filas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="number" min="1" max="12" x-model="selectedElement.data.filas" @input="updateElementData('filas', parseInt($event.target.value))" class="vbp-field-input" placeholder="auto">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Gap (espacio)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.gap" @input="updateElementData('gap', $event.target.value)" class="vbp-field-input" placeholder="16px">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tamaño automático de columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.auto_fit" @change="updateElementData('auto_fit', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Columnas fijas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="auto-fit"><?php esc_html_e( 'Auto-fit (llena el espacio)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="auto-fill"><?php esc_html_e( 'Auto-fill (rellena con vacíos)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group" x-show="selectedElement.data.auto_fit">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ancho mínimo de celda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.min_col_width" @input="updateElementData('min_col_width', $event.target.value)" class="vbp-field-input" placeholder="200px">
                        </div>
                        <small class="vbp-field-hint"><?php esc_html_e( 'Arrastra elementos dentro de este grid', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                    </div>
                </template>

                <!-- ========== VIDEO ========== -->
                <template x-if="selectedElement.type === 'video-embed' || selectedElement.type === 'video-section'">
                    <div class="vbp-inspector-section">
                        <template x-if="selectedElement.type === 'video-section'">
                            <div>
                                <div class="vbp-field-group">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input">
                                </div>
                                <div class="vbp-field-group">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <textarea x-model="selectedElement.data.descripcion" @input="updateElementData('descripcion', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                                </div>
                            </div>
                        </template>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Video', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-field-with-button">
                                <input type="url" x-model="selectedElement.data.video_url" @input="updateElementData('video_url', $event.target.value)" class="vbp-field-input" placeholder="https://youtube.com/watch?v=...">
                                <button type="button" @click="openMediaLibrary('video_url', 'video')" class="vbp-btn vbp-btn-secondary vbp-btn-sm" title="<?php esc_attr_e( 'Seleccionar de biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                </button>
                            </div>
                            <small class="vbp-field-hint"><?php esc_html_e( 'Puedes pegar una URL externa o elegir un video desde la biblioteca de medios.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>
                    </div>
                </template>

                <!-- ========== MAP ========== -->
                <template x-if="selectedElement.type === 'map' || selectedElement.type === 'mapa'">
                    <div class="vbp-inspector-section" x-data="{ isGeocoding: false, geocodeError: '' }">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-field-with-button">
                                <input type="text"
                                       x-model="selectedElement.data.direccion"
                                       @input="updateElementData('direccion', $event.target.value)"
                                       @keydown.enter.prevent="geocodeAddress(selectedElement.data.direccion, isGeocoding, $refs)"
                                       class="vbp-field-input"
                                       placeholder="<?php esc_attr_e( 'Ej: Gran Vía 1, Madrid, España', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <button type="button"
                                        @click="geocodeAddress(selectedElement.data.direccion, isGeocoding, $refs)"
                                        :disabled="isGeocoding || !selectedElement.data.direccion"
                                        class="vbp-btn vbp-btn-secondary vbp-btn-sm"
                                        title="<?php esc_attr_e( 'Buscar coordenadas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
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
                                <label class="vbp-field-label"><?php esc_html_e( 'Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.lat" @input="updateElementData('lat', $event.target.value)" class="vbp-field-input" placeholder="40.4168">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.lng" @input="updateElementData('lng', $event.target.value)" class="vbp-field-input" placeholder="-3.7038">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="range" min="1" max="20" x-model="selectedElement.data.zoom" @input="updateElementData('zoom', parseInt($event.target.value))" class="vbp-field-range">
                            <span class="vbp-range-value" x-text="selectedElement.data.zoom || 14"></span>
                        </div>
                    </div>
                </template>

                <!-- ========== HTML ========== -->
                <template x-if="selectedElement.type === 'html'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Código HTML', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <textarea x-model="selectedElement.data.code" @input="updateElementData('code', $event.target.value)" class="vbp-field-textarea vbp-code-textarea" rows="10"></textarea>
                        </div>
                    </div>
                </template>

                <!-- ========== SHORTCODE ========== -->
                <template x-if="selectedElement.type === 'shortcode'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Shortcode', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <?php esc_html_e( 'Actualizar preview', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </template>

                <!-- ========== COLORES DE SECCIÓN (para bloques de sección) ========== -->
                <template x-if="isSectionBlock(selectedElement.type)">
                    <div class="vbp-inspector-section vbp-section-colors">
                        <!-- Colores de texto -->
                        <h4 class="vbp-section-title">🎨 <?php esc_html_e( 'Colores de Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.titulo_color || '#1f2937')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('titulo_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('titulo_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.subtitulo_color || '#6b7280')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('subtitulo_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('subtitulo_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.texto_color || '#374151')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto general', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('texto_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('texto_color', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colores de botón -->
                        <h4 class="vbp-section-title">🔘 <?php esc_html_e( 'Colores de Botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_color_fondo || '#3b82f6')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_color_fondo', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_color_fondo', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_color_texto || '#ffffff')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_color_texto', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_color_texto', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.boton_color_hover || '#2563eb')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Fondo hover', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('boton_color_hover', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('boton_color_hover', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fondo de sección -->
                        <h4 class="vbp-section-title">🖼️ <?php esc_html_e( 'Fondo de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.seccion_fondo_tipo" @change="updateElementData('seccion_fondo_tipo', $event.target.value)" class="vbp-field-select">
                                <option value="color"><?php esc_html_e( 'Color sólido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="gradient"><?php esc_html_e( 'Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="image"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <!-- Color sólido -->
                        <div class="vbp-field-group" x-show="!selectedElement.data.seccion_fondo_tipo || selectedElement.data.seccion_fondo_tipo === 'color'" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.seccion_fondo_color || '#ffffff')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('seccion_fondo_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('seccion_fondo_color', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Gradiente -->
                        <template x-if="selectedElement.data.seccion_fondo_tipo === 'gradient'">
                            <div class="vbp-field-row">
                                <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.seccion_fondo_gradiente_inicio || '#3b82f6')">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <div class="vbp-color-input-wrapper">
                                        <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                        <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('seccion_fondo_gradiente_inicio', $event.target.value)" class="vbp-field-input vbp-color-input">
                                        <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                            <div class="vbp-color-picker-grid">
                                                <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                    <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('seccion_fondo_gradiente_inicio', color)"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.seccion_fondo_gradiente_fin || '#8b5cf6')">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <div class="vbp-color-input-wrapper">
                                        <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                        <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('seccion_fondo_gradiente_fin', $event.target.value)" class="vbp-field-input vbp-color-input">
                                        <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                            <div class="vbp-color-picker-grid">
                                                <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                    <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('seccion_fondo_gradiente_fin', color)"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!-- Imagen de fondo -->
                        <template x-if="selectedElement.data.seccion_fondo_tipo === 'image'">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-image-preview vbp-image-preview-bg" x-show="selectedElement.data.seccion_fondo_imagen" :style="{ backgroundImage: 'url(' + selectedElement.data.seccion_fondo_imagen + ')' }">
                                    <button type="button" @click="updateElementData('seccion_fondo_imagen', '')" class="vbp-image-remove">×</button>
                                </div>
                                <button type="button" @click="openMediaLibrary('seccion_fondo_imagen')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                    <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                            </div>
                        </template>
                        <!-- Overlay -->
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.seccion_overlay_color || 'rgba(0,0,0,0.5)')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('seccion_overlay_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('seccion_overlay_color', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Opacidad overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-range-wrapper">
                                <input type="range" min="0" max="100" step="5"
                                       :value="selectedElement.data.seccion_overlay_opacity || 50"
                                       @input="updateElementData('seccion_overlay_opacity', parseInt($event.target.value))"
                                       class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.data.seccion_overlay_opacity || 50) + '%'"></span>
                            </div>
                        </div>

                        <!-- Colores de tarjetas -->
                        <h4 class="vbp-section-title">🃏 <?php esc_html_e( 'Colores de Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.card_fondo_color || '#ffffff')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('card_fondo_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('card_fondo_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.card_borde_color || '#e5e7eb')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Borde', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('card_borde_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('card_borde_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.card_titulo_color || '#1f2937')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('card_titulo_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('card_titulo_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.card_texto_color || '#6b7280')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('card_texto_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('card_texto_color', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.card_icono_color || '#3b82f6')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color de iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('card_icono_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('card_icono_color', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colores de acento -->
                        <h4 class="vbp-section-title">✨ <?php esc_html_e( 'Colores de Acento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.acento_color || '#3b82f6')">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color de acento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('acento_color', $event.target.value)" class="vbp-field-input vbp-color-input">
                                <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                    <div class="vbp-color-picker-grid">
                                        <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                            <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('acento_color', color)"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.destacado_fondo || '#eff6ff')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Fondo destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('destacado_fondo', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('destacado_fondo', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-field-half" x-data="vbpColorPicker()" x-init="initColor(selectedElement.data.destacado_borde || '#3b82f6')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Borde destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-color-input-wrapper">
                                    <button type="button" class="vbp-color-swatch" :style="{ backgroundColor: currentColor }" @click="togglePicker()"></button>
                                    <input type="text" x-model="currentColor" @input="updateColor($event.target.value); updateElementData('destacado_borde', $event.target.value)" class="vbp-field-input vbp-color-input">
                                    <div class="vbp-color-picker-dropdown" x-show="isOpen" x-cloak @click.away="isOpen = false">
                                        <div class="vbp-color-picker-grid">
                                            <template x-for="(color, colorIndex) in presetColors" :key="colorIndex + '-' + color">
                                                <button type="button" class="vbp-color-preset" :style="{ backgroundColor: color }" @click="selectColor(color); updateElementData('destacado_borde', color)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== MÓDULOS / WIDGETS (shortcode-based) ========== -->
                <template x-if="selectedElement.data && (selectedElement.data.shortcode || selectedElement.shortcode)">
                    <div class="vbp-inspector-section vbp-module-settings">
                        <div class="vbp-module-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding: 12px; background: var(--vbp-accent-color-10); border-radius: 8px;">
                            <span style="font-size: 24px;">⚡</span>
                            <div>
                                <div style="font-weight: 600; color: var(--vbp-text-primary);" x-text="selectedElement.name || selectedElement.data.shortcode || '<?php esc_attr_e( 'Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"></div>
                                <code style="font-size: 11px; color: var(--vbp-text-secondary);" x-text="'[' + (selectedElement.data.shortcode || selectedElement.shortcode) + ']'"></code>
                            </div>
                        </div>

                        <!-- Configuración de Layout -->
                        <h4 class="vbp-section-title">📐 <?php esc_html_e( 'Disposición', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-row">
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.columnas" @change="updateElementData('columnas', $event.target.value)" class="vbp-field-select">
                                    <option value="1">1 <?php esc_html_e( 'columna', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="2">2 <?php esc_html_e( 'columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="3">3 <?php esc_html_e( 'columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="4">4 <?php esc_html_e( 'columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="auto"><?php esc_html_e( 'Automático', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Límite items', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.limite" @change="updateElementData('limite', $event.target.value)" class="vbp-field-select">
                                    <option value="6">6</option>
                                    <option value="9">9</option>
                                    <option value="12">12</option>
                                    <option value="15">15</option>
                                    <option value="24">24</option>
                                    <option value="-1"><?php esc_html_e( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.tipo" @input="updateElementData('tipo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Dejar vacío para todos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <small class="vbp-field-hint"><?php esc_html_e( 'Filtrar por tipo específico del módulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>

                        <!-- Estilo Visual -->
                        <h4 class="vbp-section-title">🎨 <?php esc_html_e( 'Estilo Visual', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Esquema de color', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.esquema_color" @change="updateElementData('esquema_color', $event.target.value)" class="vbp-field-select">
                                <option value="default"><?php esc_html_e( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="primary"><?php esc_html_e( 'Primario (azul)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="success"><?php esc_html_e( 'Éxito (verde)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="warning"><?php esc_html_e( 'Advertencia (amarillo)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="danger"><?php esc_html_e( 'Peligro (rojo)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="purple"><?php esc_html_e( 'Púrpura', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="dark"><?php esc_html_e( 'Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>

                        <div class="vbp-field-row">
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Estilo tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.estilo_tarjeta" @change="updateElementData('estilo_tarjeta', $event.target.value)" class="vbp-field-select">
                                    <option value="elevated"><?php esc_html_e( 'Elevada', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="outlined"><?php esc_html_e( 'Con borde', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="filled"><?php esc_html_e( 'Rellena', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="glass"><?php esc_html_e( 'Cristal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="minimal"><?php esc_html_e( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Bordes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.radio_bordes" @change="updateElementData('radio_bordes', $event.target.value)" class="vbp-field-select">
                                    <option value="none"><?php esc_html_e( 'Sin redondear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="sm"><?php esc_html_e( 'Pequeño', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="md"><?php esc_html_e( 'Mediano', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="lg"><?php esc_html_e( 'Grande', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="xl"><?php esc_html_e( 'Extra grande', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Animación de entrada', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.animacion_entrada" @change="updateElementData('animacion_entrada', $event.target.value)" class="vbp-field-select">
                                <option value="none"><?php esc_html_e( 'Sin animación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="fade"><?php esc_html_e( 'Aparecer', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="slide-up"><?php esc_html_e( 'Deslizar arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="slide-down"><?php esc_html_e( 'Deslizar abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="zoom"><?php esc_html_e( 'Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>

                        <!-- Opciones de Visualización -->
                        <h4 class="vbp-section-title">👁️ <?php esc_html_e( 'Visualización', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_titulo !== false" @change="updateElementData('mostrar_titulo', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_descripcion === true" @change="updateElementData('mostrar_descripcion', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_filtros === 'si' || selectedElement.data.mostrar_filtros === 'true' || selectedElement.data.mostrar_filtros === true" @change="updateElementData('mostrar_filtros', $event.target.checked ? 'si' : 'no')">
                                <?php esc_html_e( 'Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.mostrar_busqueda !== false" @change="updateElementData('mostrar_busqueda', $event.target.checked)">
                                <?php esc_html_e( 'Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>

                        <!-- Título personalizado (si está activado) -->
                        <template x-if="selectedElement.data.mostrar_titulo !== false">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Título personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.titulo_personalizado" @input="updateElementData('titulo_personalizado', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Dejar vacío para usar título por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>
                        </template>

                        <!-- Descripción (si está activada) -->
                        <template x-if="selectedElement.data.mostrar_descripcion === true">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <textarea x-model="selectedElement.data.descripcion" @input="updateElementData('descripcion', $event.target.value)" class="vbp-field-textarea" rows="2"></textarea>
                            </div>
                        </template>

                        <!-- Filtros avanzados (según tipo de módulo) -->
                        <h4 class="vbp-section-title">🔧 <?php esc_html_e( 'Filtros del Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.categoria" @input="updateElementData('categoria', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Slug de categoría o vacío para todas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.orderby" @change="updateElementData('orderby', $event.target.value)" class="vbp-field-select">
                                <option value="date"><?php esc_html_e( 'Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="title"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="modified"><?php esc_html_e( 'Última modificación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="rand"><?php esc_html_e( 'Aleatorio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="menu_order"><?php esc_html_e( 'Orden del menú', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-btn-group">
                                <button type="button" @click="updateElementData('order', 'DESC')" :class="{ 'active': selectedElement.data.order === 'DESC' || !selectedElement.data.order }" class="vbp-btn-toggle"><?php esc_html_e( 'Descendente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                <button type="button" @click="updateElementData('order', 'ASC')" :class="{ 'active': selectedElement.data.order === 'ASC' }" class="vbp-btn-toggle"><?php esc_html_e( 'Ascendente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========== CONTACT SECTION (antes two_columns) ========== -->
                <template x-if="selectedElement.type === 'two_columns' || selectedElement.type === 'contact_section'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Gap entre columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="number" x-model="selectedElement.data.gap" @input="updateElementData('gap', parseInt($event.target.value) || 24)" class="vbp-field-input" min="0" max="100" placeholder="24">
                        </div>

                        <!-- COLUMNA IZQUIERDA -->
                        <div class="vbp-subsection">
                            <h4 class="vbp-section-title" style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;"></span>
                                <?php esc_html_e( 'Columna Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </h4>

                            <div class="vbp-field-group" x-init="if (!selectedElement.data.columna_izquierda) initColumnContent('columna_izquierda', 'contact_info')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Tipo de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.columna_izquierda.type" @change="initColumnContent('columna_izquierda', $event.target.value)" class="vbp-field-select">
                                    <option value="contact_info"><?php esc_html_e( 'Información de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="contact_form"><?php esc_html_e( 'Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="text"><?php esc_html_e( 'Texto libre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="image"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>

                            <!-- Editor contact_info izquierda -->
                            <template x-if="selectedElement.data.columna_izquierda && selectedElement.data.columna_izquierda.type === 'contact_info'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_izquierda.data.titulo" @input="updateColumnData('columna_izquierda', 'titulo', $event.target.value)" class="vbp-field-input">
                                    </div>
                                    <div class="vbp-items-header">
                                        <span class="vbp-field-label"><?php esc_html_e( 'Items de información', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                        <button type="button" @click="addColumnItem('columna_izquierda', 'contact_info')" class="vbp-btn-add" title="<?php esc_attr_e( 'Añadir item', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                    <div class="vbp-items-list">
                                        <template x-for="(item, idx) in (selectedElement.data.columna_izquierda.data.items || [])" :key="idx">
                                            <div class="vbp-item-card vbp-item-card-compact">
                                                <div class="vbp-item-row">
                                                    <input type="text" x-model="item.icono" @input="updateColumnItem('columna_izquierda', idx, 'icono', $event.target.value)" class="vbp-field-input vbp-field-icon" placeholder="📧" style="width: 50px; text-align: center;">
                                                    <button type="button" @click="openIconSelectorForColumnItem('columna_izquierda', idx, 'icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                        <span class="material-icons" style="font-size: 18px;">apps</span>
                                                    </button>
                                                    <input type="text" x-model="item.titulo" @input="updateColumnItem('columna_izquierda', idx, 'titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="flex: 1;">
                                                    <button type="button" @click="removeColumnItem('columna_izquierda', idx)" class="vbp-btn-icon-xs vbp-btn-danger" title="<?php esc_attr_e( 'Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">×</button>
                                                </div>
                                                <input type="text" x-model="item.valor" @input="updateColumnItem('columna_izquierda', idx, 'valor', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Valor (email, teléfono, etc.)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Editor contact_form izquierda -->
                            <template x-if="selectedElement.data.columna_izquierda && selectedElement.data.columna_izquierda.type === 'contact_form'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_izquierda.data.titulo" @input="updateColumnData('columna_izquierda', 'titulo', $event.target.value)" class="vbp-field-input">
                                    </div>
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_izquierda.data.boton_texto" @input="updateColumnData('columna_izquierda', 'boton_texto', $event.target.value)" class="vbp-field-input" placeholder="Enviar">
                                    </div>
                                    <div class="vbp-items-header">
                                        <span class="vbp-field-label"><?php esc_html_e( 'Campos del formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                        <button type="button" @click="addColumnItem('columna_izquierda', 'contact_form')" class="vbp-btn-add" title="<?php esc_attr_e( 'Añadir campo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                    <div class="vbp-items-list">
                                        <template x-for="(campo, idx) in (selectedElement.data.columna_izquierda.data.campos || [])" :key="idx">
                                            <div class="vbp-item-card vbp-item-card-compact">
                                                <div class="vbp-item-row">
                                                    <input type="text" x-model="campo.label" @input="updateColumnItem('columna_izquierda', idx, 'label', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Label', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="flex: 1;">
                                                    <select x-model="campo.tipo" @change="updateColumnItem('columna_izquierda', idx, 'tipo', $event.target.value)" class="vbp-field-select" style="width: 100px;">
                                                        <option value="text">Texto</option>
                                                        <option value="email">Email</option>
                                                        <option value="tel">Teléfono</option>
                                                        <option value="textarea">Área texto</option>
                                                        <option value="select">Selector</option>
                                                    </select>
                                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; color: #666;">
                                                        <input type="checkbox" :checked="campo.requerido" @change="updateColumnItem('columna_izquierda', idx, 'requerido', $event.target.checked)"> *
                                                    </label>
                                                    <button type="button" @click="removeColumnItem('columna_izquierda', idx)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                                </div>
                                                <template x-if="campo.tipo === 'select'">
                                                    <input type="text" x-model="campo.opciones_text" @input="updateColumnItemOptions('columna_izquierda', idx, $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Opciones separadas por coma', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Editor texto izquierda -->
                            <template x-if="selectedElement.data.columna_izquierda && selectedElement.data.columna_izquierda.type === 'text'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <textarea x-model="selectedElement.data.columna_izquierda.data.contenido" @input="updateColumnData('columna_izquierda', 'contenido', $event.target.value)" class="vbp-field-textarea" rows="5"></textarea>
                                    </div>
                                </div>
                            </template>

                            <!-- Editor imagen izquierda -->
                            <template x-if="selectedElement.data.columna_izquierda && selectedElement.data.columna_izquierda.type === 'image'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <div class="vbp-image-preview" x-show="selectedElement.data.columna_izquierda.data.src">
                                            <img :src="selectedElement.data.columna_izquierda.data.src" alt="">
                                            <button type="button" @click="updateColumnData('columna_izquierda', 'src', '')" class="vbp-image-remove">×</button>
                                        </div>
                                        <div class="vbp-field-with-button">
                                            <input type="url" x-model="selectedElement.data.columna_izquierda.data.src" @input="updateColumnData('columna_izquierda', 'src', $event.target.value)" class="vbp-field-input">
                                            <button type="button" @click="openColumnMediaLibrary('columna_izquierda', 'src')" class="vbp-btn vbp-btn-secondary vbp-btn-sm" title="<?php esc_attr_e( 'Seleccionar de biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Texto alternativo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_izquierda.data.alt" @input="updateColumnData('columna_izquierda', 'alt', $event.target.value)" class="vbp-field-input">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- COLUMNA DERECHA -->
                        <div class="vbp-subsection">
                            <h4 class="vbp-section-title" style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; background: #8b5cf6; border-radius: 50%;"></span>
                                <?php esc_html_e( 'Columna Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </h4>

                            <div class="vbp-field-group" x-init="if (!selectedElement.data.columna_derecha) initColumnContent('columna_derecha', 'contact_info')">
                                <label class="vbp-field-label"><?php esc_html_e( 'Tipo de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.data.columna_derecha.type" @change="initColumnContent('columna_derecha', $event.target.value)" class="vbp-field-select">
                                    <option value="contact_info"><?php esc_html_e( 'Información de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="contact_form"><?php esc_html_e( 'Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="text"><?php esc_html_e( 'Texto libre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="image"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>

                            <!-- Editor contact_info derecha -->
                            <template x-if="selectedElement.data.columna_derecha && selectedElement.data.columna_derecha.type === 'contact_info'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_derecha.data.titulo" @input="updateColumnData('columna_derecha', 'titulo', $event.target.value)" class="vbp-field-input">
                                    </div>
                                    <div class="vbp-items-header">
                                        <span class="vbp-field-label"><?php esc_html_e( 'Items de información', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                        <button type="button" @click="addColumnItem('columna_derecha', 'contact_info')" class="vbp-btn-add" title="<?php esc_attr_e( 'Añadir item', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                    <div class="vbp-items-list">
                                        <template x-for="(item, idx) in (selectedElement.data.columna_derecha.data.items || [])" :key="idx">
                                            <div class="vbp-item-card vbp-item-card-compact">
                                                <div class="vbp-item-row">
                                                    <input type="text" x-model="item.icono" @input="updateColumnItem('columna_derecha', idx, 'icono', $event.target.value)" class="vbp-field-input vbp-field-icon" placeholder="📧" style="width: 50px; text-align: center;">
                                                    <button type="button" @click="openIconSelectorForColumnItem('columna_derecha', idx, 'icono')" class="vbp-selector-trigger" title="<?php esc_attr_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                        <span class="material-icons" style="font-size: 18px;">apps</span>
                                                    </button>
                                                    <input type="text" x-model="item.titulo" @input="updateColumnItem('columna_derecha', idx, 'titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="flex: 1;">
                                                    <button type="button" @click="removeColumnItem('columna_derecha', idx)" class="vbp-btn-icon-xs vbp-btn-danger" title="<?php esc_attr_e( 'Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">×</button>
                                                </div>
                                                <input type="text" x-model="item.valor" @input="updateColumnItem('columna_derecha', idx, 'valor', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Valor (email, teléfono, etc.)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Editor contact_form derecha -->
                            <template x-if="selectedElement.data.columna_derecha && selectedElement.data.columna_derecha.type === 'contact_form'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_derecha.data.titulo" @input="updateColumnData('columna_derecha', 'titulo', $event.target.value)" class="vbp-field-input">
                                    </div>
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_derecha.data.boton_texto" @input="updateColumnData('columna_derecha', 'boton_texto', $event.target.value)" class="vbp-field-input" placeholder="Enviar">
                                    </div>
                                    <div class="vbp-items-header">
                                        <span class="vbp-field-label"><?php esc_html_e( 'Campos del formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                        <button type="button" @click="addColumnItem('columna_derecha', 'contact_form')" class="vbp-btn-add" title="<?php esc_attr_e( 'Añadir campo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                    <div class="vbp-items-list">
                                        <template x-for="(campo, idx) in (selectedElement.data.columna_derecha.data.campos || [])" :key="idx">
                                            <div class="vbp-item-card vbp-item-card-compact">
                                                <div class="vbp-item-row">
                                                    <input type="text" x-model="campo.label" @input="updateColumnItem('columna_derecha', idx, 'label', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Label', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="flex: 1;">
                                                    <select x-model="campo.tipo" @change="updateColumnItem('columna_derecha', idx, 'tipo', $event.target.value)" class="vbp-field-select" style="width: 100px;">
                                                        <option value="text">Texto</option>
                                                        <option value="email">Email</option>
                                                        <option value="tel">Teléfono</option>
                                                        <option value="textarea">Área texto</option>
                                                        <option value="select">Selector</option>
                                                    </select>
                                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; color: #666;">
                                                        <input type="checkbox" :checked="campo.requerido" @change="updateColumnItem('columna_derecha', idx, 'requerido', $event.target.checked)"> *
                                                    </label>
                                                    <button type="button" @click="removeColumnItem('columna_derecha', idx)" class="vbp-btn-icon-xs vbp-btn-danger">×</button>
                                                </div>
                                                <template x-if="campo.tipo === 'select'">
                                                    <input type="text" x-model="campo.opciones_text" @input="updateColumnItemOptions('columna_derecha', idx, $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Opciones separadas por coma', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Editor texto derecha -->
                            <template x-if="selectedElement.data.columna_derecha && selectedElement.data.columna_derecha.type === 'text'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <textarea x-model="selectedElement.data.columna_derecha.data.contenido" @input="updateColumnData('columna_derecha', 'contenido', $event.target.value)" class="vbp-field-textarea" rows="5"></textarea>
                                    </div>
                                </div>
                            </template>

                            <!-- Editor imagen derecha -->
                            <template x-if="selectedElement.data.columna_derecha && selectedElement.data.columna_derecha.type === 'image'">
                                <div class="vbp-column-editor">
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <div class="vbp-image-preview" x-show="selectedElement.data.columna_derecha.data.src">
                                            <img :src="selectedElement.data.columna_derecha.data.src" alt="">
                                            <button type="button" @click="updateColumnData('columna_derecha', 'src', '')" class="vbp-image-remove">×</button>
                                        </div>
                                        <div class="vbp-field-with-button">
                                            <input type="url" x-model="selectedElement.data.columna_derecha.data.src" @input="updateColumnData('columna_derecha', 'src', $event.target.value)" class="vbp-field-input">
                                            <button type="button" @click="openColumnMediaLibrary('columna_derecha', 'src')" class="vbp-btn vbp-btn-secondary vbp-btn-sm" title="<?php esc_attr_e( 'Seleccionar de biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="vbp-field-group">
                                        <label class="vbp-field-label"><?php esc_html_e( 'Texto alternativo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                        <input type="text" x-model="selectedElement.data.columna_derecha.data.alt" @input="updateColumnData('columna_derecha', 'alt', $event.target.value)" class="vbp-field-input">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ========== AUDIO ========== -->
                <template x-if="selectedElement.type === 'audio'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Audio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-field-with-button">
                                <input type="url" x-model="selectedElement.data.src" @input="updateElementData('src', $event.target.value)" class="vbp-field-input" placeholder="https://ejemplo.com/audio.mp3">
                                <button type="button" @click="openMediaLibrary('src', 'audio')" class="vbp-btn vbp-btn-secondary vbp-btn-sm" title="<?php esc_attr_e( 'Seleccionar de biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                </button>
                            </div>
                            <small class="vbp-field-hint"><?php esc_html_e( 'Puedes elegir un archivo de la biblioteca o pegar una URL directa. Formatos: MP3, WAV, OGG.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Título (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.data.titulo" @input="updateElementData('titulo', $event.target.value)" class="vbp-field-input" placeholder="<?php esc_attr_e( 'Nombre del audio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" :checked="selectedElement.data.autoplay" @change="updateElementData('autoplay', $event.target.checked)">
                                    <?php esc_html_e( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" :checked="selectedElement.data.loop" @change="updateElementData('loop', $event.target.checked)">
                                    <?php esc_html_e( 'Repetir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" :checked="selectedElement.data.controls !== false" @change="updateElementData('controls', $event.target.checked)">
                                    <?php esc_html_e( 'Controles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" :checked="selectedElement.data.muted" @change="updateElementData('muted', $event.target.checked)">
                                    <?php esc_html_e( 'Silenciado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Preload', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.preload" @change="updateElementData('preload', $event.target.value)" class="vbp-field-select">
                                <option value="auto"><?php esc_html_e( 'Auto - Cargar completo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="metadata"><?php esc_html_e( 'Metadata - Solo información', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="none"><?php esc_html_e( 'Ninguno - No precargar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                    </div>
                </template>

                <!-- ========== EMBED ========== -->
                <template x-if="selectedElement.type === 'embed'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Código embed', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <textarea x-model="selectedElement.data.code" @input="updateElementData('code', $event.target.value)" class="vbp-field-textarea vbp-code-textarea" rows="6" placeholder="<iframe src=&quot;...&quot;></iframe>"></textarea>
                            <small class="vbp-field-hint"><?php esc_html_e( 'Pega el código iframe de YouTube, Vimeo, Spotify, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'O URL directa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="url" x-model="selectedElement.data.url" @input="updateElementData('url', $event.target.value)" class="vbp-field-input" placeholder="https://youtube.com/watch?v=...">
                            <small class="vbp-field-hint"><?php esc_html_e( 'YouTube, Vimeo, Twitter, etc. se convertirán automáticamente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Ancho', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.width" @input="updateElementData('width', $event.target.value)" class="vbp-field-input" placeholder="100%">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label"><?php esc_html_e( 'Alto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="selectedElement.data.height" @input="updateElementData('height', $event.target.value)" class="vbp-field-input" placeholder="400px">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Proporción de aspecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.data.aspect_ratio" @change="updateElementData('aspect_ratio', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Ninguna (usar ancho/alto)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="16/9">16:9 (Vídeo estándar)</option>
                                <option value="4/3">4:3 (Vídeo clásico)</option>
                                <option value="1/1">1:1 (Cuadrado)</option>
                                <option value="9/16">9:16 (Vertical)</option>
                                <option value="21/9">21:9 (Ultrawide)</option>
                            </select>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.lazy_load !== false" @change="updateElementData('lazy_load', $event.target.checked)">
                                <?php esc_html_e( 'Carga perezosa (lazy load)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </label>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-scene'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">Preset 3D</label>
                            <select x-model="selectedElement.data.preset" @change="updateElementData('preset', $event.target.value)" class="vbp-field-select">
                                <option value="minimal">Minimalista</option>
                                <option value="product-showcase">Showcase de Producto</option>
                                <option value="floating-cards">Tarjetas Flotantes</option>
                                <option value="particle-background">Fondo de Partículas</option>
                                <option value="hero-3d">Hero 3D</option>
                                <option value="gallery-3d">Galería 3D</option>
                            </select>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label">Ancho</label>
                                <input type="text" x-model="selectedElement.data.width" @input="updateElementData('width', $event.target.value)" class="vbp-field-input">
                            </div>
                            <div class="vbp-field-group vbp-field-half">
                                <label class="vbp-field-label">Alto</label>
                                <input type="text" x-model="selectedElement.data.height" @input="updateElementData('height', $event.target.value)" class="vbp-field-input">
                            </div>
                        </div>
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">Controles</label>
                            <select x-model="selectedElement.data.controls" @change="updateElementData('controls', $event.target.value)" class="vbp-field-select">
                                <option value="orbit">Órbita</option>
                                <option value="fly">Vuelo</option>
                                <option value="none">Sin controles</option>
                            </select>
                        </div>
                        <div class="vbp-field-row">
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.autoRotate === true" @change="updateElementData('autoRotate', $event.target.checked)">
                                Auto-rotar
                            </label>
                            <label class="vbp-checkbox-label">
                                <input type="checkbox" :checked="selectedElement.data.shadows === true" @change="updateElementData('shadows', $event.target.checked)">
                                Sombras
                            </label>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-object'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">Forma</label>
                            <select x-model="selectedElement.data.primitive" @change="updateElementData('primitive', $event.target.value)" class="vbp-field-select">
                                <option value="box">Cubo</option>
                                <option value="sphere">Esfera</option>
                                <option value="cylinder">Cilindro</option>
                                <option value="cone">Cono</option>
                                <option value="torus">Toro</option>
                                <option value="plane">Plano</option>
                            </select>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">X</label><input type="number" step="0.1" :value="selectedElement.data.position?.x ?? 0" @input="updateElementData('position.x', parseFloat($event.target.value || 0))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Y</label><input type="number" step="0.1" :value="selectedElement.data.position?.y ?? 0" @input="updateElementData('position.y', parseFloat($event.target.value || 0))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Z</label><input type="number" step="0.1" :value="selectedElement.data.position?.z ?? 0" @input="updateElementData('position.z', parseFloat($event.target.value || 0))" class="vbp-field-input"></div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Color</label><input type="color" :value="selectedElement.data.material?.color || '#6366f1'" @input="updateElementData('material.color', $event.target.value)" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Material</label><select x-model="selectedElement.data.material.type" @change="updateElementData('material.type', $event.target.value)" class="vbp-field-select"><option value="standard">Estándar</option><option value="basic">Básico</option><option value="phong">Phong</option><option value="lambert">Lambert</option><option value="physical">Físico</option><option value="toon">Cartoon</option></select></div>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-model'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">Archivo 3D</label>
                            <input type="text" x-model="selectedElement.data.src" @input="updateElementData('src', $event.target.value)" class="vbp-field-input" placeholder="/ruta/modelo.glb o https://...">
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Escala</label><input type="number" step="0.1" :value="selectedElement.data.scale ?? 1" @input="updateElementData('scale', parseFloat($event.target.value || 1))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Velocidad</label><input type="number" step="0.1" :value="selectedElement.data.autoRotateSpeed ?? 1" @input="updateElementData('autoRotateSpeed', parseFloat($event.target.value || 1))" class="vbp-field-input"></div>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-text'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label">Texto</label>
                            <textarea x-model="selectedElement.data.text" @input="updateElementData('text', $event.target.value)" class="vbp-field-textarea" rows="3"></textarea>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Tamaño</label><input type="number" step="0.1" :value="selectedElement.data.size ?? 1" @input="updateElementData('size', parseFloat($event.target.value || 1))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Profundidad</label><input type="number" step="0.01" :value="selectedElement.data.depth ?? 0.2" @input="updateElementData('depth', parseFloat($event.target.value || 0.2))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Color</label><input type="color" :value="selectedElement.data.material?.color || '#ffffff'" @input="updateElementData('material.color', $event.target.value)" class="vbp-field-input"></div>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-particles'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Cantidad</label><input type="number" step="100" :value="selectedElement.data.count ?? 1000" @input="updateElementData('count', parseInt($event.target.value || 1000, 10))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Tamaño</label><input type="number" step="0.001" :value="selectedElement.data.size ?? 0.02" @input="updateElementData('size', parseFloat($event.target.value || 0.02))" class="vbp-field-input"></div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Color</label><input type="color" :value="selectedElement.data.color || '#ffffff'" @input="updateElementData('color', $event.target.value)" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Movimiento</label><select x-model="selectedElement.data.movement" @change="updateElementData('movement', $event.target.value)" class="vbp-field-select"><option value="float">Flotante</option><option value="rise">Ascendente</option><option value="fall">Descendente</option><option value="orbit">Orbital</option><option value="static">Estático</option></select></div>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-light'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Tipo</label><select x-model="selectedElement.data.lightType" @change="updateElementData('lightType', $event.target.value)" class="vbp-field-select"><option value="ambient">Ambiental</option><option value="directional">Direccional</option><option value="point">Puntual</option><option value="spot">Spot</option><option value="hemisphere">Hemisférica</option></select></div>
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Color</label><input type="color" :value="selectedElement.data.color || '#ffffff'" @input="updateElementData('color', $event.target.value)" class="vbp-field-input"></div>
                        </div>
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Intensidad</label><input type="number" step="0.1" :value="selectedElement.data.intensity ?? 1" @input="updateElementData('intensity', parseFloat($event.target.value || 1))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-half"><label class="vbp-field-label">Posición X</label><input type="number" step="0.1" :value="selectedElement.data.position?.x ?? 1" @input="updateElementData('position.x', parseFloat($event.target.value || 1))" class="vbp-field-input"></div>
                        </div>
                    </div>
                </template>

                <template x-if="selectedElement.type === '3d-group'">
                    <div class="vbp-inspector-section">
                        <div class="vbp-field-row">
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Pos X</label><input type="number" step="0.1" :value="selectedElement.data.position?.x ?? 0" @input="updateElementData('position.x', parseFloat($event.target.value || 0))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Pos Y</label><input type="number" step="0.1" :value="selectedElement.data.position?.y ?? 0" @input="updateElementData('position.y', parseFloat($event.target.value || 0))" class="vbp-field-input"></div>
                            <div class="vbp-field-group vbp-field-third"><label class="vbp-field-label">Pos Z</label><input type="number" step="0.1" :value="selectedElement.data.position?.z ?? 0" @input="updateElementData('position.z', parseFloat($event.target.value || 0))" class="vbp-field-input"></div>
                        </div>
                    </div>
                </template>

            </div>

            <!-- ============================================ -->
            <!-- Tab: Estilos -->
            <!-- ============================================ -->
            <div x-show="activeTab === 'styles'" class="vbp-inspector-panel">

                <!-- Presets Rápidos de Estilos -->
                <div class="vbp-inspector-section vbp-style-presets">
                    <h4 class="vbp-section-title">
                        <?php esc_html_e( 'Presets Rápidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        <span class="vbp-section-badge"><?php esc_html_e( 'Un clic', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    </h4>
                    <div class="vbp-presets-grid">
                        <button type="button" @click="applyStylePreset('modern')" class="vbp-preset-btn" data-tooltip="<?php esc_attr_e( 'Estilo moderno con sombras suaves', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <span class="vbp-preset-preview vbp-preset-modern"></span>
                            <span class="vbp-preset-name"><?php esc_html_e( 'Moderno', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button" @click="applyStylePreset('minimal')" class="vbp-preset-btn" data-tooltip="<?php esc_attr_e( 'Diseño limpio y minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <span class="vbp-preset-preview vbp-preset-minimal"></span>
                            <span class="vbp-preset-name"><?php esc_html_e( 'Minimal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button" @click="applyStylePreset('bold')" class="vbp-preset-btn" data-tooltip="<?php esc_attr_e( 'Estilo llamativo con contraste alto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <span class="vbp-preset-preview vbp-preset-bold"></span>
                            <span class="vbp-preset-name"><?php esc_html_e( 'Bold', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button" @click="applyStylePreset('outlined')" class="vbp-preset-btn" data-tooltip="<?php esc_attr_e( 'Bordes definidos sin relleno', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <span class="vbp-preset-preview vbp-preset-outlined"></span>
                            <span class="vbp-preset-name"><?php esc_html_e( 'Outlined', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button" @click="applyStylePreset('gradient')" class="vbp-preset-btn" data-tooltip="<?php esc_attr_e( 'Fondo con degradado atractivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <span class="vbp-preset-preview vbp-preset-gradient"></span>
                            <span class="vbp-preset-name"><?php esc_html_e( 'Gradient', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button" @click="applyStylePreset('glassmorphism')" class="vbp-preset-btn" data-tooltip="<?php esc_attr_e( 'Efecto cristal translúcido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <span class="vbp-preset-preview vbp-preset-glass"></span>
                            <span class="vbp-preset-name"><?php esc_html_e( 'Glass', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                    </div>
                    <button type="button" @click="resetStyles()" class="vbp-btn vbp-btn-link vbp-btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 01-9 9 9.75 9.75 0 01-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                        <?php esc_html_e( 'Resetear estilos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>

                <!-- Selector de Breakpoints -->
                <div class="vbp-breakpoint-selector vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <div class="vbp-breakpoint-tabs">
                        <button type="button"
                                @click="setBreakpoint('desktop')"
                                :class="{ 'active': activeBreakpoint === 'desktop' }"
                                class="vbp-breakpoint-tab"
                                title="<?php esc_attr_e( 'Desktop (> 1024px)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                            <span class="vbp-breakpoint-label"><?php esc_html_e( 'Desktop', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button"
                                @click="setBreakpoint('tablet')"
                                :class="{ 'active': activeBreakpoint === 'tablet', 'has-overrides': hasBreakpointOverridesForElement('tablet') }"
                                class="vbp-breakpoint-tab"
                                title="<?php esc_attr_e( 'Tablet (769px - 1024px)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="2" width="16" height="20" rx="2"/>
                                <line x1="12" y1="18" x2="12" y2="18"/>
                            </svg>
                            <span class="vbp-breakpoint-label"><?php esc_html_e( 'Tablet', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button"
                                @click="setBreakpoint('mobile')"
                                :class="{ 'active': activeBreakpoint === 'mobile', 'has-overrides': hasBreakpointOverridesForElement('mobile') }"
                                class="vbp-breakpoint-tab"
                                title="<?php esc_attr_e( 'Mobile (< 768px)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="6" y="2" width="12" height="20" rx="2"/>
                                <line x1="12" y1="18" x2="12" y2="18"/>
                            </svg>
                            <span class="vbp-breakpoint-label"><?php esc_html_e( 'Mobile', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                    </div>

                    <!-- Indicador de breakpoint activo -->
                    <div class="vbp-breakpoint-info" x-show="activeBreakpoint !== 'desktop'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        <span><?php esc_html_e( 'Editando estilos para', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            <strong x-text="activeBreakpoint === 'tablet' ? 'Tablet' : 'Mobile'"></strong>
                        </span>
                        <button type="button"
                                @click="$store.vbp.clearBreakpointOverrides(selectedElement.id, activeBreakpoint)"
                                class="vbp-btn-link vbp-btn-xs"
                                title="<?php esc_attr_e( 'Limpiar todos los overrides de este breakpoint', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <?php esc_html_e( 'Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>

                <!-- Panel de Responsive Variants (integrado con VBPResponsiveVariants) -->
                <div class="vbp-responsive-variants-panel vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'" x-data="{ showOverrides: false }">
                    <div class="vbp-responsive-variants-header">
                        <h4 class="vbp-section-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <rect x="7" y="9" width="10" height="8" rx="1"/>
                            </svg>
                            <?php esc_html_e( 'Variantes Responsive', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </h4>
                        <button type="button"
                                @click="showOverrides = !showOverrides"
                                class="vbp-btn-link vbp-btn-xs"
                                x-show="window.VBPResponsiveVariants && window.VBPResponsiveVariants.hasOverrides(selectedElement.id, $store.vbp.activeBreakpoint)">
                            <span x-text="showOverrides ? '<?php echo esc_js( __( 'Ocultar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' : '<?php echo esc_js( __( 'Ver cambios', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
                        </button>
                    </div>

                    <!-- Lista de overrides del breakpoint actual -->
                    <template x-if="showOverrides && window.VBPResponsiveVariants && $store.vbp.activeBreakpoint !== 'desktop'">
                        <div class="vbp-responsive-overrides-list">
                            <div class="vbp-responsive-overrides-info">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20V10M6 20V16M18 20V4"/>
                                </svg>
                                <span><?php esc_html_e( 'Propiedades modificadas en este breakpoint:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="vbp-responsive-overrides-items">
                                <template x-for="(prop, propIndex) in (window.VBPResponsiveVariants ? window.VBPResponsiveVariants.getOverriddenProps(selectedElement.id, $store.vbp.activeBreakpoint) : [])" :key="'override-' + propIndex">
                                    <div class="vbp-responsive-override-item">
                                        <span class="vbp-responsive-override-prop" x-text="prop.replace(/\./g, ' > ').replace(/([A-Z])/g, ' $1').toLowerCase()"></span>
                                        <button type="button"
                                                @click="window.VBPResponsiveVariants && window.VBPResponsiveVariants.clearOverride(selectedElement.id, $store.vbp.activeBreakpoint, prop)"
                                                class="vbp-btn-icon-xs"
                                                title="<?php esc_attr_e( 'Eliminar override', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18 6L6 18M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <div class="vbp-responsive-overrides-actions">
                                <button type="button"
                                        @click="window.VBPResponsiveVariants && window.VBPResponsiveVariants.copyLayout(selectedElement.id, 'desktop', $store.vbp.activeBreakpoint)"
                                        class="vbp-btn vbp-btn-sm vbp-btn-secondary">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2"/>
                                        <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                                    </svg>
                                    <?php esc_html_e( 'Copiar de Desktop', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button"
                                        @click="window.VBPResponsiveVariants && window.VBPResponsiveVariants.clearAllOverrides(selectedElement.id, $store.vbp.activeBreakpoint)"
                                        class="vbp-btn vbp-btn-sm vbp-btn-danger">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    </svg>
                                    <?php esc_html_e( 'Limpiar todos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Indicador cuando no hay overrides -->
                    <template x-if="$store.vbp.activeBreakpoint !== 'desktop' && window.VBPResponsiveVariants && !window.VBPResponsiveVariants.hasOverrides(selectedElement.id, $store.vbp.activeBreakpoint)">
                        <div class="vbp-responsive-no-overrides">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 8v8M8 12h8"/>
                            </svg>
                            <span><?php esc_html_e( 'Este breakpoint hereda los estilos de Desktop.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <small><?php esc_html_e( 'Modifica cualquier propiedad para crear un override.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                        </div>
                    </template>
                </div>

                <!-- Selector de Estados Interactivos -->
                <div class="vbp-state-selector" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <div class="vbp-state-header">
                        <h4 class="vbp-section-title"><?php esc_html_e( 'Estado Interactivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    </div>
                    <div class="vbp-state-tabs">
                        <button type="button"
                                @click="$store.vbp.setStyleState('normal')"
                                :class="{ 'active': $store.vbp.activeStyleState === 'normal' }"
                                class="vbp-state-btn">
                            <?php esc_html_e( 'Normal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                        <button type="button"
                                @click="$store.vbp.setStyleState('hover')"
                                :class="{ 'active': $store.vbp.activeStyleState === 'hover', 'has-styles': $store.vbp.hasStateStyles && $store.vbp.hasStateStyles(selectedElement.id, 'hover') }"
                                class="vbp-state-btn vbp-state-hover">
                            <?php esc_html_e( 'Hover', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                        <button type="button"
                                @click="$store.vbp.setStyleState('active')"
                                :class="{ 'active': $store.vbp.activeStyleState === 'active', 'has-styles': $store.vbp.hasStateStyles && $store.vbp.hasStateStyles(selectedElement.id, 'active') }"
                                class="vbp-state-btn vbp-state-active">
                            <?php esc_html_e( 'Active', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                        <button type="button"
                                @click="$store.vbp.setStyleState('focus')"
                                :class="{ 'active': $store.vbp.activeStyleState === 'focus', 'has-styles': $store.vbp.hasStateStyles && $store.vbp.hasStateStyles(selectedElement.id, 'focus') }"
                                class="vbp-state-btn vbp-state-focus">
                            <?php esc_html_e( 'Focus', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </div>

                    <!-- Configuración de Transiciones -->
                    <template x-if="$store.vbp.activeStyleState !== 'normal' && selectedElement.styles.states && selectedElement.styles.states[$store.vbp.activeStyleState]">
                        <div class="vbp-transition-config">
                            <div class="vbp-transition-toggle">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox"
                                           x-model="selectedElement.styles.states[$store.vbp.activeStyleState].enabled"
                                           @change="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'enabled', $event.target.checked)"
                                           class="vbp-checkbox">
                                    <span><?php esc_html_e( 'Activar estilos para este estado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </label>
                            </div>

                        <template x-if="selectedElement.styles.states && selectedElement.styles.states[$store.vbp.activeStyleState] && selectedElement.styles.states[$store.vbp.activeStyleState].enabled">
                            <div class="vbp-state-properties">
                                <!-- Color de fondo -->
                                <div class="vbp-field-row">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <div class="vbp-color-field">
                                        <input type="color"
                                               :value="selectedElement.styles.states[$store.vbp.activeStyleState].background || '#ffffff'"
                                               @input="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'background', $event.target.value)"
                                               class="vbp-color-input">
                                        <input type="text"
                                               :value="selectedElement.styles.states[$store.vbp.activeStyleState].background || ''"
                                               @input="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'background', $event.target.value)"
                                               class="vbp-field-input"
                                               placeholder="<?php esc_attr_e( 'Color o var(--)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    </div>
                                </div>

                                <!-- Color de texto -->
                                <div class="vbp-field-row">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <div class="vbp-color-field">
                                        <input type="color"
                                               :value="selectedElement.styles.states[$store.vbp.activeStyleState].color || '#000000'"
                                               @input="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'color', $event.target.value)"
                                               class="vbp-color-input">
                                        <input type="text"
                                               :value="selectedElement.styles.states[$store.vbp.activeStyleState].color || ''"
                                               @input="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'color', $event.target.value)"
                                               class="vbp-field-input"
                                               placeholder="<?php esc_attr_e( 'Color o var(--)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    </div>
                                </div>

                                <!-- Transform -->
                                <div class="vbp-field-row">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Transform', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select class="vbp-field-select"
                                            :value="selectedElement.styles.states[$store.vbp.activeStyleState].transform || ''"
                                            @change="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'transform', $event.target.value)">
                                        <option value=""><?php esc_html_e( 'Ninguno', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="scale(1.05)"><?php esc_html_e( 'Escalar +5%', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="scale(1.1)"><?php esc_html_e( 'Escalar +10%', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="scale(0.95)"><?php esc_html_e( 'Escalar -5%', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="translateY(-2px)"><?php esc_html_e( 'Subir 2px', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="translateY(-4px)"><?php esc_html_e( 'Subir 4px', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="rotate(5deg)"><?php esc_html_e( 'Rotar 5°', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    </select>
                                </div>

                                <!-- Opacidad -->
                                <div class="vbp-field-row">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Opacidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="range"
                                           min="0" max="1" step="0.1"
                                           :value="selectedElement.styles.states[$store.vbp.activeStyleState].opacity || 1"
                                           @input="$store.vbp.updateStateStyle(selectedElement.id, $store.vbp.activeStyleState, 'opacity', $event.target.value)"
                                           class="vbp-range-input">
                                </div>
                            </div>
                        </template>
                        </div>
                    </template>

                    <!-- Transición Global -->
                    <div class="vbp-transition-global" x-show="$store.vbp.activeStyleState === 'normal'">
                        <label class="vbp-checkbox-label">
                            <input type="checkbox"
                                   x-model="selectedElement.styles.transition.enabled"
                                   @change="$store.vbp.updateTransition(selectedElement.id, { enabled: $event.target.checked })"
                                   class="vbp-checkbox">
                            <span><?php esc_html_e( 'Animación de transición', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </label>

                        <template x-if="selectedElement.styles.transition.enabled">
                            <div class="vbp-transition-fields">
                                <div class="vbp-field-row">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Duración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select class="vbp-field-select"
                                            x-model="selectedElement.styles.transition.duration"
                                            @change="$store.vbp.updateTransition(selectedElement.id, { duration: $event.target.value })">
                                        <option value="0.15s"><?php esc_html_e( 'Rápida (0.15s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="0.3s"><?php esc_html_e( 'Normal (0.3s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="0.5s"><?php esc_html_e( 'Lenta (0.5s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    </select>
                                </div>
                                <div class="vbp-field-row">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Curva', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select class="vbp-field-select"
                                            x-model="selectedElement.styles.transition.timing"
                                            @change="$store.vbp.updateTransition(selectedElement.id, { timing: $event.target.value })">
                                        <option value="ease"><?php esc_html_e( 'Ease', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="ease-in"><?php esc_html_e( 'Ease In', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="ease-out"><?php esc_html_e( 'Ease Out', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="ease-in-out"><?php esc_html_e( 'Ease In Out', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="linear"><?php esc_html_e( 'Linear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Auto-Layout Visual (para contenedores) -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced' && ['container', 'section', 'columns', 'row', 'grid', 'hero'].indexOf(selectedElement.type) !== -1">
                    <h4 class="vbp-section-title" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18M9 21V9"/>
                        </svg>
                        <?php esc_html_e( 'Auto-Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>

                    <div class="vbp-autolayout-panel">
                        <!-- Toggle Auto-Layout -->
                        <div class="vbp-autolayout-toggle">
                            <span class="vbp-autolayout-toggle-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                    <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                                </svg>
                                <?php esc_html_e( 'Activar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </span>
                            <button type="button"
                                class="vbp-autolayout-toggle-switch"
                                :class="{ 'active': selectedElement.styles.layout && selectedElement.styles.layout.display === 'flex' }"
                                @click="toggleAutoLayout()">
                            </button>
                        </div>

                        <!-- Controles (solo si está activo) -->
                        <template x-if="selectedElement.styles.layout && selectedElement.styles.layout.display === 'flex'">
                            <div>
                                <!-- Dirección -->
                                <div class="vbp-autolayout-direction">
                                    <button type="button" class="vbp-direction-btn"
                                        :class="{ 'active': !selectedElement.styles.layout.flexDirection || selectedElement.styles.layout.flexDirection === 'row' }"
                                        @click="updateStyle('layout.flexDirection', 'row')"
                                        data-tooltip="<?php esc_attr_e( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="vbp-direction-btn"
                                        :class="{ 'active': selectedElement.styles.layout.flexDirection === 'column' }"
                                        @click="updateStyle('layout.flexDirection', 'column')"
                                        data-tooltip="<?php esc_attr_e( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14M5 12l7 7 7-7"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="vbp-direction-btn"
                                        :class="{ 'active': selectedElement.styles.layout.flexWrap === 'wrap' }"
                                        @click="updateStyle('layout.flexWrap', selectedElement.styles.layout.flexWrap === 'wrap' ? 'nowrap' : 'wrap')"
                                        data-tooltip="<?php esc_attr_e( 'Wrap', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18M3 12h12M3 18h18"/>
                                            <path d="M15 12v3a3 3 0 003 3h3"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Alignment Grid Visual -->
                                <div class="vbp-alignment-grid" data-tooltip="<?php esc_attr_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    <?php
                                    $alignments = [
                                        ['flex-start', 'flex-start'],
                                        ['center', 'flex-start'],
                                        ['flex-end', 'flex-start'],
                                        ['flex-start', 'center'],
                                        ['center', 'center'],
                                        ['flex-end', 'center'],
                                        ['flex-start', 'flex-end'],
                                        ['center', 'flex-end'],
                                        ['flex-end', 'flex-end'],
                                    ];
                                    foreach ($alignments as $align) :
                                        $justify = $align[0];
                                        $items = $align[1];
                                    ?>
                                    <button type="button" class="vbp-alignment-cell"
                                        :class="{ 'active': selectedElement.styles.layout.justifyContent === '<?php echo $justify; ?>' && selectedElement.styles.layout.alignItems === '<?php echo $items; ?>' }"
                                        @click="setAlignment('<?php echo $justify; ?>', '<?php echo $items; ?>')">
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Distribution -->
                                <div class="vbp-distribution-row">
                                    <button type="button" class="vbp-distribution-btn"
                                        :class="{ 'active': selectedElement.styles.layout.justifyContent === 'space-between' }"
                                        @click="updateStyle('layout.justifyContent', 'space-between')"
                                        data-tooltip="<?php esc_attr_e( 'Space Between', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="8" width="4" height="8"/>
                                            <rect x="17" y="8" width="4" height="8"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="vbp-distribution-btn"
                                        :class="{ 'active': selectedElement.styles.layout.justifyContent === 'space-around' }"
                                        @click="updateStyle('layout.justifyContent', 'space-around')"
                                        data-tooltip="<?php esc_attr_e( 'Space Around', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="5" y="8" width="4" height="8"/>
                                            <rect x="15" y="8" width="4" height="8"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="vbp-distribution-btn"
                                        :class="{ 'active': selectedElement.styles.layout.justifyContent === 'space-evenly' }"
                                        @click="updateStyle('layout.justifyContent', 'space-evenly')"
                                        data-tooltip="<?php esc_attr_e( 'Space Evenly', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="4" y="8" width="4" height="8"/>
                                            <rect x="10" y="8" width="4" height="8"/>
                                            <rect x="16" y="8" width="4" height="8"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Gap -->
                                <div class="vbp-autolayout-spacing">
                                    <div class="vbp-spacing-control">
                                        <span class="vbp-spacing-label"><?php esc_html_e( 'Gap', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                        <input type="text"
                                            x-model="selectedElement.styles.layout.gap"
                                            @input="updateStyle('layout.gap', $event.target.value)"
                                            class="vbp-spacing-input"
                                            placeholder="16px">
                                    </div>
                                    <div class="vbp-spacing-control">
                                        <span class="vbp-spacing-label"><?php esc_html_e( 'Padding', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                        <input type="text"
                                            x-model="selectedElement.styles.spacing.padding.all"
                                            @input="updateStyle('spacing.padding.all', $event.target.value)"
                                            class="vbp-spacing-input"
                                            placeholder="20px">
                                    </div>
                                </div>

                                <!-- Preview miniatura -->
                                <div class="vbp-autolayout-preview"
                                    :class="{
                                        'direction-column': selectedElement.styles.layout.flexDirection === 'column',
                                        'wrap-enabled': selectedElement.styles.layout.flexWrap === 'wrap'
                                    }"
                                    :style="{
                                        'justify-content': selectedElement.styles.layout.justifyContent || 'flex-start',
                                        'align-items': selectedElement.styles.layout.alignItems || 'stretch',
                                        'gap': selectedElement.styles.layout.gap || '4px'
                                    }">
                                    <div class="vbp-preview-item"></div>
                                    <div class="vbp-preview-item"></div>
                                    <div class="vbp-preview-item"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Spacing -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Espaciado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>

                    <!-- Presets de espaciado rápido -->
                    <div class="vbp-spacing-presets">
                        <div class="vbp-spacing-preset-row">
                            <span class="vbp-preset-label"><?php esc_html_e( 'Padding', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <div class="vbp-preset-buttons">
                                <?php foreach ([0, 4, 8, 16, 24, 32, 48] as $size) : ?>
                                <button type="button"
                                        class="vbp-preset-btn"
                                        :class="{ 'active': isPaddingPresetActive('<?php echo $size; ?>px') }"
                                        @click="applyPaddingPreset('<?php echo $size; ?>px')"
                                        title="<?php echo $size; ?>px">
                                    <?php echo $size; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="vbp-spacing-preset-row">
                            <span class="vbp-preset-label"><?php esc_html_e( 'Margin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <div class="vbp-preset-buttons">
                                <?php foreach ([0, 4, 8, 16, 24, 32, 48] as $size) : ?>
                                <button type="button"
                                        class="vbp-preset-btn"
                                        :class="{ 'active': isMarginPresetActive('<?php echo $size; ?>px') }"
                                        @click="applyMarginPreset('<?php echo $size; ?>px')"
                                        title="<?php echo $size; ?>px">
                                    <?php echo $size; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="vbp-spacing-editor">
                        <div class="vbp-spacing-box">
                            <div class="vbp-spacing-margin">
                                <span class="vbp-spacing-label"><?php esc_html_e( 'Margin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                <input type="text" x-model="selectedElement.styles.spacing.margin.top" @input="updateStyle('spacing.margin.top', $event.target.value)" class="vbp-spacing-input vbp-spacing-top" placeholder="0">
                                <input type="text" x-model="selectedElement.styles.spacing.margin.right" @input="updateStyle('spacing.margin.right', $event.target.value)" class="vbp-spacing-input vbp-spacing-right" placeholder="0">
                                <input type="text" x-model="selectedElement.styles.spacing.margin.bottom" @input="updateStyle('spacing.margin.bottom', $event.target.value)" class="vbp-spacing-input vbp-spacing-bottom" placeholder="0">
                                <input type="text" x-model="selectedElement.styles.spacing.margin.left" @input="updateStyle('spacing.margin.left', $event.target.value)" class="vbp-spacing-input vbp-spacing-left" placeholder="0">

                                <div class="vbp-spacing-padding">
                                    <span class="vbp-spacing-label"><?php esc_html_e( 'Padding', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.top" @input="updateStyle('spacing.padding.top', $event.target.value)" class="vbp-spacing-input vbp-spacing-top" placeholder="0">
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.right" @input="updateStyle('spacing.padding.right', $event.target.value)" class="vbp-spacing-input vbp-spacing-right" placeholder="0">
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.bottom" @input="updateStyle('spacing.padding.bottom', $event.target.value)" class="vbp-spacing-input vbp-spacing-bottom" placeholder="0">
                                    <input type="text" x-model="selectedElement.styles.spacing.padding.left" @input="updateStyle('spacing.padding.left', $event.target.value)" class="vbp-spacing-input vbp-spacing-left" placeholder="0">

                                    <div class="vbp-spacing-content">
                                        <?php esc_html_e( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colors -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Colores', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>

                    <!-- Paleta rápida del sitio -->
                    <div class="vbp-field-group">
                        <span class="vbp-color-palette-label"><?php esc_html_e( 'Paleta del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        <div class="vbp-color-palette">
                            <template x-for="(swatch, swatchIndex) in (getSitePalette() || [])" :key="'swatch-' + swatchIndex">
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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
                            <label class="vbp-field-label"><?php esc_html_e( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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

                <!-- Background -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>

                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Tipo de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <select x-model="selectedElement.styles.background.type" @change="updateStyle('background.type', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Color sólido (usar arriba)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="gradient"><?php esc_html_e( 'Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="image"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                        </select>
                    </div>

                    <!-- Gradiente -->
                    <template x-if="selectedElement.styles.background && selectedElement.styles.background.type === 'gradient'">
                        <div class="vbp-gradient-editor">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.styles.background.gradientDirection" @change="updateStyle('background.gradientDirection', $event.target.value)" class="vbp-field-select">
                                    <option value="to bottom"><?php esc_html_e( 'Arriba → Abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to top"><?php esc_html_e( 'Abajo → Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to right"><?php esc_html_e( 'Izquierda → Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to left"><?php esc_html_e( 'Derecha → Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to bottom right"><?php esc_html_e( 'Diagonal ↘', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to bottom left"><?php esc_html_e( 'Diagonal ↙', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to top right"><?php esc_html_e( 'Diagonal ↗', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="to top left"><?php esc_html_e( 'Diagonal ↖', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <div class="vbp-color-input-wrapper">
                                        <input type="color" :value="selectedElement.styles.background.gradientStart || '#3b82f6'" @input="updateStyle('background.gradientStart', $event.target.value)" class="vbp-color-input">
                                        <input type="text" x-model="selectedElement.styles.background.gradientStart" @input="updateStyle('background.gradientStart', $event.target.value)" class="vbp-field-input" placeholder="#3b82f6">
                                    </div>
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Color fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <div class="vbp-color-input-wrapper">
                                        <input type="color" :value="selectedElement.styles.background.gradientEnd || '#8b5cf6'" @input="updateStyle('background.gradientEnd', $event.target.value)" class="vbp-color-input">
                                        <input type="text" x-model="selectedElement.styles.background.gradientEnd" @input="updateStyle('background.gradientEnd', $event.target.value)" class="vbp-field-input" placeholder="#8b5cf6">
                                    </div>
                                </div>
                            </div>
                            <div class="vbp-gradient-preview" :style="'height: 40px; border-radius: 6px; background: linear-gradient(' + (selectedElement.styles.background.gradientDirection || 'to bottom') + ', ' + (selectedElement.styles.background.gradientStart || '#3b82f6') + ', ' + (selectedElement.styles.background.gradientEnd || '#8b5cf6') + ')'"></div>
                        </div>
                    </template>

                    <!-- Imagen de fondo -->
                    <template x-if="selectedElement.styles.background && selectedElement.styles.background.type === 'image'">
                        <div class="vbp-background-image-editor">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-image-selector">
                                    <template x-if="selectedElement.styles.background.image">
                                        <div class="vbp-image-preview" style="height: 80px; border-radius: 6px; background-size: cover; background-position: center;" :style="'background-image: url(' + selectedElement.styles.background.image + ')'">
                                            <button type="button" @click="updateStyle('background.image', '')" class="vbp-btn-remove" title="<?php esc_attr_e( 'Eliminar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">×</button>
                                        </div>
                                    </template>
                                    <button type="button" @click="openMediaLibrary('background.image')" class="vbp-btn vbp-btn-secondary vbp-btn-block">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                        <?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select x-model="selectedElement.styles.background.size" @change="updateStyle('background.size', $event.target.value)" class="vbp-field-select">
                                        <option value="cover"><?php esc_html_e( 'Cubrir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="contain"><?php esc_html_e( 'Contener', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="auto"><?php esc_html_e( 'Auto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="100% 100%"><?php esc_html_e( 'Estirar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    </select>
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Posición', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select x-model="selectedElement.styles.background.position" @change="updateStyle('background.position', $event.target.value)" class="vbp-field-select">
                                        <option value="center"><?php esc_html_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="top"><?php esc_html_e( 'Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="bottom"><?php esc_html_e( 'Abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="left"><?php esc_html_e( 'Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="right"><?php esc_html_e( 'Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Repetir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.styles.background.repeat" @change="updateStyle('background.repeat', $event.target.value)" class="vbp-field-select">
                                    <option value="no-repeat"><?php esc_html_e( 'No repetir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="repeat"><?php esc_html_e( 'Repetir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="repeat-x"><?php esc_html_e( 'Repetir horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="repeat-y"><?php esc_html_e( 'Repetir vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox" x-model="selectedElement.styles.background.fixed" @change="updateStyle('background.fixed', selectedElement.styles.background.fixed)">
                                    <?php esc_html_e( 'Fondo fijo (parallax)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Overlay (capa oscura)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-range-input">
                                    <input type="range" min="0" max="0.9" step="0.1" x-model="selectedElement.styles.background.overlayOpacity" @input="updateStyle('background.overlayOpacity', $event.target.value)" class="vbp-field-range">
                                    <span class="vbp-range-value" x-text="Math.round((selectedElement.styles.background.overlayOpacity || 0) * 100) + '%'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Typography -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Tipografía', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.typography.fontSize" @input="updateStyle('typography.fontSize', $event.target.value)" class="vbp-field-input" placeholder="16px">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Peso', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.typography.fontWeight" @change="updateStyle('typography.fontWeight', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Normal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="300">Light (300)</option>
                                <option value="400">Regular (400)</option>
                                <option value="500">Medium (500)</option>
                                <option value="600">Semibold (600)</option>
                                <option value="700">Bold (700)</option>
                            </select>
                        </div>
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Interlineado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <input type="text" x-model="selectedElement.styles.typography.lineHeight" @input="updateStyle('typography.lineHeight', $event.target.value)" class="vbp-field-input" placeholder="1.5">
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
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

                <!-- Layout (para contenedores - solo en modo avanzado) -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced' && ['container', 'columns', 'row', 'grid'].indexOf(selectedElement.type) !== -1">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Display', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.layout.display" @change="updateStyle('layout.display', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Auto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="flex">Flex</option>
                                <option value="grid">Grid</option>
                                <option value="block">Block</option>
                            </select>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Gap', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.layout.gap" @input="updateStyle('layout.gap', $event.target.value)" class="vbp-field-input" placeholder="16px">
                        </div>
                    </div>
                    <div class="vbp-field-row" x-show="selectedElement.styles.layout.display === 'flex'">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.layout.flexDirection" @change="updateStyle('layout.flexDirection', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Fila', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="row">Row</option>
                                <option value="column">Column</option>
                                <option value="row-reverse">Row Reverse</option>
                                <option value="column-reverse">Column Reverse</option>
                            </select>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.layout.alignItems" @change="updateStyle('layout.alignItems', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Auto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="flex-start">Inicio</option>
                                <option value="center">Centro</option>
                                <option value="flex-end">Fin</option>
                                <option value="stretch">Estirar</option>
                            </select>
                        </div>
                    </div>
                    <div class="vbp-field-group" x-show="selectedElement.styles.layout.display === 'flex'">
                        <label class="vbp-field-label"><?php esc_html_e( 'Justificar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <select x-model="selectedElement.styles.layout.justifyContent" @change="updateStyle('layout.justifyContent', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Auto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Fin</option>
                            <option value="space-between">Space Between</option>
                            <option value="space-around">Space Around</option>
                            <option value="space-evenly">Space Evenly</option>
                        </select>
                    </div>
                </div>

                <!-- Borders -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Bordes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> <span class="vbp-advanced-badge">PRO</span></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Radio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.borders.radius" @input="updateStyle('borders.radius', $event.target.value)" class="vbp-field-input" placeholder="0px">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ancho', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.borders.width" @input="updateStyle('borders.width', $event.target.value)" class="vbp-field-input" placeholder="0px">
                        </div>
                    </div>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.borders.style" @change="updateStyle('borders.style', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Ninguno', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="solid"><?php esc_html_e( 'Sólido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="dashed"><?php esc_html_e( 'Discontinuo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="dotted"><?php esc_html_e( 'Punteado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-color-input-wrapper">
                                <input type="color" :value="normalizeColorForInput(selectedElement.styles.borders.color, '#333333')" @input="updateStyle('borders.color', $event.target.value)" class="vbp-color-input">
                                <input type="text" x-model="selectedElement.styles.borders.color" @input="updateStyle('borders.color', $event.target.value)" class="vbp-field-input">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shadows -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Sombras', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> <span class="vbp-advanced-badge">PRO</span></h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Presets', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <div class="vbp-shadow-presets">
                            <button type="button" @click="updateStyle('shadows.boxShadow', 'none')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Ninguna', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-shadow-preview"></div>
                            </button>
                            <button type="button" @click="updateStyle('shadows.boxShadow', '0 1px 3px rgba(0,0,0,0.12)')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Suave', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-shadow-preview" style="box-shadow: 0 1px 3px rgba(0,0,0,0.12)"></div>
                            </button>
                            <button type="button" @click="updateStyle('shadows.boxShadow', '0 4px 6px rgba(0,0,0,0.1)')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Media', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-shadow-preview" style="box-shadow: 0 4px 6px rgba(0,0,0,0.1)"></div>
                            </button>
                            <button type="button" @click="updateStyle('shadows.boxShadow', '0 10px 25px rgba(0,0,0,0.15)')" class="vbp-shadow-preset" title="<?php esc_attr_e( 'Fuerte', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <div class="vbp-shadow-preview" style="box-shadow: 0 10px 25px rgba(0,0,0,0.15)"></div>
                            </button>
                        </div>
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <input type="text" x-model="selectedElement.styles.shadows.boxShadow" @input="updateStyle('shadows.boxShadow', $event.target.value)" class="vbp-field-input" placeholder="0 4px 6px rgba(0,0,0,0.1)">
                    </div>
                </div>

                <!-- Dimensions -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Dimensiones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ancho', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.dimensions.width" @input="updateStyle('dimensions.width', $event.target.value)" class="vbp-field-input" placeholder="auto">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.dimensions.height" @input="updateStyle('dimensions.height', $event.target.value)" class="vbp-field-input" placeholder="auto">
                        </div>
                    </div>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Alto mínimo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.dimensions.minHeight" @input="updateStyle('dimensions.minHeight', $event.target.value)" class="vbp-field-input" placeholder="0">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Ancho máximo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.dimensions.maxWidth" @input="updateStyle('dimensions.maxWidth', $event.target.value)" class="vbp-field-input" placeholder="none">
                        </div>
                    </div>
                </div>

                <!-- Overflow y Opacity -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Efectos visuales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Overflow', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.overflow" @change="updateStyle('overflow', $event.target.value)" class="vbp-field-select">
                                <option value=""><?php esc_html_e( 'Visible', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="hidden"><?php esc_html_e( 'Oculto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="scroll"><?php esc_html_e( 'Scroll', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="auto"><?php esc_html_e( 'Auto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Opacidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="0" max="1" step="0.1" x-model="selectedElement.styles.opacity" @input="updateStyle('opacity', $event.target.value)" class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.styles.opacity || 1)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Position -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Posición', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <select x-model="selectedElement.styles.position.position" @change="updateStyle('position.position', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Estático (normal)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="relative"><?php esc_html_e( 'Relativo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="absolute"><?php esc_html_e( 'Absoluto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="fixed"><?php esc_html_e( 'Fijo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="sticky"><?php esc_html_e( 'Sticky', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                        </select>
                    </div>
                    <template x-if="selectedElement.styles.position && selectedElement.styles.position.position && selectedElement.styles.position.position !== ''">
                        <div class="vbp-position-controls">
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="text" x-model="selectedElement.styles.position.top" @input="updateStyle('position.top', $event.target.value)" class="vbp-field-input" placeholder="auto">
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="text" x-model="selectedElement.styles.position.right" @input="updateStyle('position.right', $event.target.value)" class="vbp-field-input" placeholder="auto">
                                </div>
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="text" x-model="selectedElement.styles.position.bottom" @input="updateStyle('position.bottom', $event.target.value)" class="vbp-field-input" placeholder="auto">
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <input type="text" x-model="selectedElement.styles.position.left" @input="updateStyle('position.left', $event.target.value)" class="vbp-field-input" placeholder="auto">
                                </div>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Z-Index', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="number" x-model="selectedElement.styles.position.zIndex" @input="updateStyle('position.zIndex', $event.target.value)" class="vbp-field-input" placeholder="auto">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Transform -->
                <div class="vbp-inspector-section vbp-field-advanced" x-show="$store.vbp.inspectorMode === 'advanced'">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Transformaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Rotación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="-180" max="180" step="5" x-model="selectedElement.styles.transform.rotate" @input="updateStyle('transform.rotate', $event.target.value)" class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.styles.transform.rotate || 0) + '°'"></span>
                            </div>
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Escala', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="0.5" max="2" step="0.1" x-model="selectedElement.styles.transform.scale" @input="updateStyle('transform.scale', $event.target.value)" class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.styles.transform.scale || 1) + 'x'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mover X', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.transform.translateX" @input="updateStyle('transform.translateX', $event.target.value)" class="vbp-field-input" placeholder="0px">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Mover Y', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.transform.translateY" @input="updateStyle('transform.translateY', $event.target.value)" class="vbp-field-input" placeholder="0px">
                        </div>
                    </div>
                    <div class="vbp-field-row">
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Sesgar X', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.transform.skewX" @input="updateStyle('transform.skewX', $event.target.value)" class="vbp-field-input" placeholder="0deg">
                        </div>
                        <div class="vbp-field-group vbp-field-half">
                            <label class="vbp-field-label"><?php esc_html_e( 'Sesgar Y', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" x-model="selectedElement.styles.transform.skewY" @input="updateStyle('transform.skewY', $event.target.value)" class="vbp-field-input" placeholder="0deg">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- Tab: Avanzado -->
            <!-- ============================================ -->
            <div x-show="activeTab === 'advanced'" class="vbp-inspector-panel">
                <!-- Auto-layout (Flexbox/Grid) -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                        <?php esc_html_e( 'Auto-layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>

                    <!-- Display Mode -->
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Display', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <div class="vbp-btn-group vbp-btn-group-full">
                            <button type="button"
                                    @click="updateStyle('layout.display', 'block')"
                                    :class="{ 'active': (selectedElement.styles.layout?.display || 'block') === 'block' }"
                                    class="vbp-btn-toggle"
                                    title="<?php esc_attr_e( 'Block', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                            </button>
                            <button type="button"
                                    @click="updateStyle('layout.display', 'flex')"
                                    :class="{ 'active': selectedElement.styles.layout?.display === 'flex' }"
                                    class="vbp-btn-toggle"
                                    title="<?php esc_attr_e( 'Flex', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/><rect x="11" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>
                            </button>
                            <button type="button"
                                    @click="updateStyle('layout.display', 'grid')"
                                    :class="{ 'active': selectedElement.styles.layout?.display === 'grid' }"
                                    class="vbp-btn-toggle"
                                    title="<?php esc_attr_e( 'Grid', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Flex Options -->
                    <template x-if="selectedElement.styles.layout?.display === 'flex'">
                        <div class="vbp-layout-flex-options">
                            <!-- Direction -->
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-btn-group">
                                    <button type="button"
                                            @click="updateStyle('layout.flexDirection', 'row')"
                                            :class="{ 'active': (selectedElement.styles.layout?.flexDirection || 'row') === 'row' }"
                                            class="vbp-btn-toggle"
                                            title="<?php esc_attr_e( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    </button>
                                    <button type="button"
                                            @click="updateStyle('layout.flexDirection', 'column')"
                                            :class="{ 'active': selectedElement.styles.layout?.flexDirection === 'column' }"
                                            class="vbp-btn-toggle"
                                            title="<?php esc_attr_e( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                    </button>
                                    <button type="button"
                                            @click="updateStyle('layout.flexDirection', 'row-reverse')"
                                            :class="{ 'active': selectedElement.styles.layout?.flexDirection === 'row-reverse' }"
                                            class="vbp-btn-toggle"
                                            title="<?php esc_attr_e( 'Horizontal inverso', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 5 5 12 12 19"/></svg>
                                    </button>
                                    <button type="button"
                                            @click="updateStyle('layout.flexDirection', 'column-reverse')"
                                            :class="{ 'active': selectedElement.styles.layout?.flexDirection === 'column-reverse' }"
                                            class="vbp-btn-toggle"
                                            title="<?php esc_attr_e( 'Vertical inverso', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Justify Content -->
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Distribución', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-btn-group">
                                    <button type="button" @click="updateStyle('layout.justifyContent', 'flex-start')" :class="{ 'active': (selectedElement.styles.layout?.justifyContent || 'flex-start') === 'flex-start' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="4" height="12"/><rect x="9" y="6" width="4" height="12"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.justifyContent', 'center')" :class="{ 'active': selectedElement.styles.layout?.justifyContent === 'center' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="6" width="4" height="12"/><rect x="13" y="6" width="4" height="12"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.justifyContent', 'flex-end')" :class="{ 'active': selectedElement.styles.layout?.justifyContent === 'flex-end' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="11" y="6" width="4" height="12"/><rect x="17" y="6" width="4" height="12"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.justifyContent', 'space-between')" :class="{ 'active': selectedElement.styles.layout?.justifyContent === 'space-between' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Space Between', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="4" height="12"/><rect x="17" y="6" width="4" height="12"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.justifyContent', 'space-around')" :class="{ 'active': selectedElement.styles.layout?.justifyContent === 'space-around' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Space Around', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="6" width="4" height="12"/><rect x="15" y="6" width="4" height="12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Align Items -->
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-btn-group">
                                    <button type="button" @click="updateStyle('layout.alignItems', 'flex-start')" :class="{ 'active': selectedElement.styles.layout?.alignItems === 'flex-start' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="10"/><rect x="14" y="4" width="4" height="6"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.alignItems', 'center')" :class="{ 'active': (selectedElement.styles.layout?.alignItems || 'center') === 'center' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="7" width="4" height="10"/><rect x="14" y="9" width="4" height="6"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.alignItems', 'flex-end')" :class="{ 'active': selectedElement.styles.layout?.alignItems === 'flex-end' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="10" width="4" height="10"/><rect x="14" y="14" width="4" height="6"/></svg>
                                    </button>
                                    <button type="button" @click="updateStyle('layout.alignItems', 'stretch')" :class="{ 'active': selectedElement.styles.layout?.alignItems === 'stretch' }" class="vbp-btn-toggle" title="<?php esc_attr_e( 'Estirar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Wrap -->
                            <div class="vbp-field-group">
                                <label class="vbp-checkbox-label">
                                    <input type="checkbox"
                                           :checked="selectedElement.styles.layout?.flexWrap === 'wrap'"
                                           @change="updateStyle('layout.flexWrap', $event.target.checked ? 'wrap' : 'nowrap')"
                                           class="vbp-checkbox">
                                    <span><?php esc_html_e( 'Permitir saltos de línea (wrap)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </label>
                            </div>

                            <!-- Gap -->
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Espacio entre elementos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-input-with-unit">
                                    <input type="number"
                                           :value="parseInt(selectedElement.styles.layout?.gap || '0')"
                                           @input="updateStyle('layout.gap', $event.target.value + 'px')"
                                           class="vbp-field-input"
                                           min="0"
                                           step="4"
                                           placeholder="0">
                                    <span class="vbp-input-unit">px</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Grid Options -->
                    <template x-if="selectedElement.styles.layout?.display === 'grid'">
                        <div class="vbp-layout-grid-options">
                            <!-- Grid Columns -->
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select @change="updateStyle('layout.gridTemplateColumns', $event.target.value)" class="vbp-field-select">
                                    <option value="repeat(2, 1fr)" :selected="selectedElement.styles.layout?.gridTemplateColumns === 'repeat(2, 1fr)'">2 columnas</option>
                                    <option value="repeat(3, 1fr)" :selected="selectedElement.styles.layout?.gridTemplateColumns === 'repeat(3, 1fr)'">3 columnas</option>
                                    <option value="repeat(4, 1fr)" :selected="selectedElement.styles.layout?.gridTemplateColumns === 'repeat(4, 1fr)'">4 columnas</option>
                                    <option value="repeat(auto-fit, minmax(200px, 1fr))" :selected="selectedElement.styles.layout?.gridTemplateColumns?.includes('auto-fit')">Auto-fit (responsive)</option>
                                </select>
                            </div>

                            <!-- Grid Gap -->
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Espacio entre celdas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-input-with-unit">
                                    <input type="number"
                                           :value="parseInt(selectedElement.styles.layout?.gap || '0')"
                                           @input="updateStyle('layout.gap', $event.target.value + 'px')"
                                           class="vbp-field-input"
                                           min="0"
                                           step="4"
                                           placeholder="0">
                                    <span class="vbp-input-unit">px</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Constraints / Pinning -->
                <div class="vbp-inspector-section vbp-inspector-section--constraints" x-data="vbpConstraintsPanel()">
                    <h4 class="vbp-section-title">
                        <svg class="vbp-section-header__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <line x1="12" y1="3" x2="12" y2="10"/>
                            <line x1="12" y1="14" x2="12" y2="21"/>
                            <line x1="3" y1="12" x2="10" y2="12"/>
                            <line x1="14" y1="12" x2="21" y2="12"/>
                        </svg>
                        <?php esc_html_e( 'Constraints', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>

                    <div class="vbp-constraints-panel">
                        <div class="vbp-constraints-panel__header">
                            <span class="vbp-constraints-panel__title"><?php esc_html_e( 'Anclaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <button type="button" class="vbp-constraints-panel__reset" x-show="hasAnyConstraint" @click="resetConstraints()">
                                <?php esc_html_e( 'Resetear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>

                        <div class="vbp-constraints-visual">
                            <!-- Cuadrado visual tipo Figma -->
                            <div class="vbp-constraints-box">
                                <!-- Elemento interno -->
                                <div class="vbp-constraints-box__inner"></div>

                                <!-- Lineas de constraint -->
                                <div class="vbp-constraints-box__line vbp-constraints-box__line--top" :class="{ 'is-active': isConstraintActive('top') }"></div>
                                <div class="vbp-constraints-box__line vbp-constraints-box__line--right" :class="{ 'is-active': isConstraintActive('right') }"></div>
                                <div class="vbp-constraints-box__line vbp-constraints-box__line--bottom" :class="{ 'is-active': isConstraintActive('bottom') }"></div>
                                <div class="vbp-constraints-box__line vbp-constraints-box__line--left" :class="{ 'is-active': isConstraintActive('left') }"></div>
                                <div class="vbp-constraints-box__line vbp-constraints-box__line--centerH" :class="{ 'is-active': isConstraintActive('centerH') }"></div>
                                <div class="vbp-constraints-box__line vbp-constraints-box__line--centerV" :class="{ 'is-active': isConstraintActive('centerV') }"></div>

                                <!-- Puntos de constraint clickeables -->
                                <button type="button"
                                        class="vbp-constraints-box__point vbp-constraints-box__point--top"
                                        :class="{ 'is-active': isConstraintActive('top') }"
                                        @click="toggleConstraint('top')"
                                        title="<?php esc_attr_e( 'Anclar arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                </button>
                                <button type="button"
                                        class="vbp-constraints-box__point vbp-constraints-box__point--right"
                                        :class="{ 'is-active': isConstraintActive('right') }"
                                        @click="toggleConstraint('right')"
                                        title="<?php esc_attr_e( 'Anclar derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                </button>
                                <button type="button"
                                        class="vbp-constraints-box__point vbp-constraints-box__point--bottom"
                                        :class="{ 'is-active': isConstraintActive('bottom') }"
                                        @click="toggleConstraint('bottom')"
                                        title="<?php esc_attr_e( 'Anclar abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                </button>
                                <button type="button"
                                        class="vbp-constraints-box__point vbp-constraints-box__point--left"
                                        :class="{ 'is-active': isConstraintActive('left') }"
                                        @click="toggleConstraint('left')"
                                        title="<?php esc_attr_e( 'Anclar izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                </button>
                                <button type="button"
                                        class="vbp-constraints-box__point vbp-constraints-box__point--centerH"
                                        :class="{ 'is-active': isConstraintActive('centerH') }"
                                        @click="toggleConstraint('centerH')"
                                        title="<?php esc_attr_e( 'Centrar horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                </button>
                                <button type="button"
                                        class="vbp-constraints-box__point vbp-constraints-box__point--centerV"
                                        :class="{ 'is-active': isConstraintActive('centerV') }"
                                        @click="toggleConstraint('centerV')"
                                        title="<?php esc_attr_e( 'Centrar vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                </button>
                            </div>

                            <!-- Info panel -->
                            <div class="vbp-constraints-info">
                                <div class="vbp-constraints-info__row">
                                    <span class="vbp-constraints-info__label"><?php esc_html_e( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <span class="vbp-constraints-info__status"
                                          :class="isConstraintActive('left') || isConstraintActive('right') || isConstraintActive('centerH') ? 'vbp-constraints-info__status--active' : 'vbp-constraints-info__status--none'"
                                          x-text="getHorizontalStatusText()">
                                    </span>
                                </div>
                                <div class="vbp-constraints-info__row">
                                    <span class="vbp-constraints-info__label"><?php esc_html_e( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <span class="vbp-constraints-info__status"
                                          :class="isConstraintActive('top') || isConstraintActive('bottom') || isConstraintActive('centerV') ? 'vbp-constraints-info__status--active' : 'vbp-constraints-info__status--none'"
                                          x-text="getVerticalStatusText()">
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Presets de constraints -->
                        <div class="vbp-constraints-presets">
                            <span class="vbp-constraints-presets__label"><?php esc_html_e( 'Presets', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <div class="vbp-constraints-presets__grid">
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'top-left' }"
                                        @click="applyPreset('top-left')"
                                        title="<?php esc_attr_e( 'Superior izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2196;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'top-center' }"
                                        @click="applyPreset('top-center')"
                                        title="<?php esc_attr_e( 'Superior centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2191;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'top-right' }"
                                        @click="applyPreset('top-right')"
                                        title="<?php esc_attr_e( 'Superior derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2197;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn vbp-constraints-preset-btn--stretch"
                                        :class="{ 'is-active': activePreset === 'stretch-horizontal' }"
                                        @click="applyPreset('stretch-horizontal')"
                                        title="<?php esc_attr_e( 'Estirar horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2194;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'center-left' }"
                                        @click="applyPreset('center-left')"
                                        title="<?php esc_attr_e( 'Centro izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2190;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'center' }"
                                        @click="applyPreset('center')"
                                        title="<?php esc_attr_e( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2B55;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'center-right' }"
                                        @click="applyPreset('center-right')"
                                        title="<?php esc_attr_e( 'Centro derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2192;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn vbp-constraints-preset-btn--stretch"
                                        :class="{ 'is-active': activePreset === 'stretch-vertical' }"
                                        @click="applyPreset('stretch-vertical')"
                                        title="<?php esc_attr_e( 'Estirar vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2195;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'bottom-left' }"
                                        @click="applyPreset('bottom-left')"
                                        title="<?php esc_attr_e( 'Inferior izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2199;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'bottom-center' }"
                                        @click="applyPreset('bottom-center')"
                                        title="<?php esc_attr_e( 'Inferior centro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2193;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn"
                                        :class="{ 'is-active': activePreset === 'bottom-right' }"
                                        @click="applyPreset('bottom-right')"
                                        title="<?php esc_attr_e( 'Inferior derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2198;</button>
                                <button type="button"
                                        class="vbp-constraints-preset-btn vbp-constraints-preset-btn--fill"
                                        :class="{ 'is-active': activePreset === 'fill' }"
                                        @click="applyPreset('fill')"
                                        title="<?php esc_attr_e( 'Rellenar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">&#x2B1C;</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Panel de Auto Layout - Sistema nivel Figma
                $autoLayoutPanelPath = __DIR__ . '/panel-auto-layout.php';
                if ( file_exists( $autoLayoutPanelPath ) ) {
                    include $autoLayoutPanelPath;
                }
                ?>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Atributos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'ID CSS', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <input type="text" x-model="selectedElement.styles.advanced.cssId" @input="updateStyle('advanced.cssId', $event.target.value)" class="vbp-field-input" placeholder="mi-elemento">
                    </div>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Clases CSS', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <input type="text" x-model="selectedElement.styles.advanced.cssClasses" @input="updateStyle('advanced.cssClasses', $event.target.value)" class="vbp-field-input" placeholder="clase1 clase2">
                    </div>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'CSS Personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <div class="vbp-field-group">
                        <textarea x-model="selectedElement.styles.advanced.customCss" @input="updateStyle('advanced.customCss', $event.target.value)" class="vbp-field-textarea vbp-code-textarea" rows="8" placeholder=".selector {
    /* tus estilos */
}"></textarea>
                        <small class="vbp-field-hint"><?php esc_html_e( 'El CSS se aplicará solo a este elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                    </div>
                </div>

                <!-- Animaciones completas -->
                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6l2.1 2.1M5.6 18.4l2.1-2.1m8.6-8.6l2.1-2.1"/></svg>
                        <?php esc_html_e( 'Animación de entrada', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Efecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <select x-model="selectedElement.styles.advanced.entranceAnimation" @change="updateStyle('advanced.entranceAnimation', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Ninguna', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <optgroup label="<?php esc_attr_e( 'Fade', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <option value="fade-in"><?php esc_html_e( 'Fade In', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="fade-in-up"><?php esc_html_e( 'Fade In Up', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="fade-in-down"><?php esc_html_e( 'Fade In Down', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="fade-in-left"><?php esc_html_e( 'Fade In Left', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="fade-in-right"><?php esc_html_e( 'Fade In Right', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e( 'Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <option value="zoom-in"><?php esc_html_e( 'Zoom In', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="zoom-out"><?php esc_html_e( 'Zoom Out', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e( 'Bounce', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <option value="bounce-in"><?php esc_html_e( 'Bounce In', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="bounce-in-up"><?php esc_html_e( 'Bounce In Up', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e( 'Especiales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                <option value="rotate-in"><?php esc_html_e( 'Rotate In', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="flip-in-x"><?php esc_html_e( 'Flip In X', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="flip-in-y"><?php esc_html_e( 'Flip In Y', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </optgroup>
                        </select>
                    </div>
                    <template x-if="selectedElement.styles.advanced.entranceAnimation">
                        <div class="vbp-animation-options">
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Disparador', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <div class="vbp-btn-group vbp-btn-group-full">
                                    <button type="button" @click="updateStyle('advanced.animTrigger', 'load')" :class="{ 'active': (selectedElement.styles.advanced.animTrigger || 'scroll') === 'load' }" class="vbp-btn-toggle">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
                                        <?php esc_html_e( 'Al cargar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </button>
                                    <button type="button" @click="updateStyle('advanced.animTrigger', 'scroll')" :class="{ 'active': (selectedElement.styles.advanced.animTrigger || 'scroll') === 'scroll' }" class="vbp-btn-toggle">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                                        <?php esc_html_e( 'Al hacer scroll', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="vbp-field-row">
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Duración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select x-model="selectedElement.styles.advanced.animDuration" @change="updateStyle('advanced.animDuration', $event.target.value)" class="vbp-field-select">
                                        <option value="0.3s"><?php esc_html_e( 'Rápida (0.3s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="0.6s"><?php esc_html_e( 'Normal (0.6s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="1s"><?php esc_html_e( 'Lenta (1s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="1.5s"><?php esc_html_e( 'Muy lenta (1.5s)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    </select>
                                </div>
                                <div class="vbp-field-group vbp-field-half">
                                    <label class="vbp-field-label"><?php esc_html_e( 'Retardo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select x-model="selectedElement.styles.advanced.animDelay" @change="updateStyle('advanced.animDelay', $event.target.value)" class="vbp-field-select">
                                        <option value="0s"><?php esc_html_e( 'Sin retardo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <option value="0.2s">0.2s</option>
                                        <option value="0.4s">0.4s</option>
                                        <option value="0.6s">0.6s</option>
                                        <option value="0.8s">0.8s</option>
                                        <option value="1s">1s</option>
                                    </select>
                                </div>
                            </div>
                            <div class="vbp-field-group">
                                <label class="vbp-field-label"><?php esc_html_e( 'Easing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="selectedElement.styles.advanced.animEasing" @change="updateStyle('advanced.animEasing', $event.target.value)" class="vbp-field-select">
                                    <option value="ease"><?php esc_html_e( 'Ease (suave)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="ease-in"><?php esc_html_e( 'Ease In', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="ease-out"><?php esc_html_e( 'Ease Out', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="ease-in-out"><?php esc_html_e( 'Ease In Out', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="linear"><?php esc_html_e( 'Linear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="bounce"><?php esc_html_e( 'Bounce (rebote)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <option value="elastic"><?php esc_html_e( 'Elastic (elástico)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 12l5-5"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php esc_html_e( 'Animación hover', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Efecto al pasar el cursor', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <select x-model="selectedElement.styles.advanced.hoverAnimation" @change="updateStyle('advanced.hoverAnimation', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Ninguna', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="grow"><?php esc_html_e( 'Crecer', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="shrink"><?php esc_html_e( 'Encoger', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="float"><?php esc_html_e( 'Flotar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="pulse"><?php esc_html_e( 'Pulsar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="wobble"><?php esc_html_e( 'Tambalear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="swing"><?php esc_html_e( 'Balancear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="glow"><?php esc_html_e( 'Brillar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        <?php esc_html_e( 'Animación continua', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-field-label"><?php esc_html_e( 'Efecto en bucle', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                        <select x-model="selectedElement.styles.advanced.loopAnimation" @change="updateStyle('advanced.loopAnimation', $event.target.value)" class="vbp-field-select">
                            <option value=""><?php esc_html_e( 'Ninguna', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="spin"><?php esc_html_e( 'Girar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="ping"><?php esc_html_e( 'Ping', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="bounce"><?php esc_html_e( 'Rebotar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="shake"><?php esc_html_e( 'Agitar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="heartbeat"><?php esc_html_e( 'Latido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            <option value="blink"><?php esc_html_e( 'Parpadear', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                        </select>
                    </div>
                    <template x-if="selectedElement.styles.advanced.loopAnimation">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Velocidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="selectedElement.styles.advanced.loopDuration" @change="updateStyle('advanced.loopDuration', $event.target.value)" class="vbp-field-select">
                                <option value="0.5s"><?php esc_html_e( 'Muy rápida', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="1s"><?php esc_html_e( 'Rápida', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="2s"><?php esc_html_e( 'Normal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <option value="3s"><?php esc_html_e( 'Lenta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </div>
                    </template>
                </div>

                <div class="vbp-inspector-section">
                    <h4 class="vbp-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V8M5 12l7-10 7 10"/></svg>
                        <?php esc_html_e( 'Parallax', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </h4>
                    <div class="vbp-field-group">
                        <label class="vbp-checkbox-label">
                            <input type="checkbox" x-model="selectedElement.styles.advanced.parallaxEnabled" @change="updateStyle('advanced.parallaxEnabled', selectedElement.styles.advanced.parallaxEnabled)">
                            <?php esc_html_e( 'Activar efecto parallax', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </label>
                    </div>
                    <template x-if="selectedElement.styles.advanced.parallaxEnabled">
                        <div class="vbp-field-group">
                            <label class="vbp-field-label"><?php esc_html_e( 'Intensidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-range-input">
                                <input type="range" min="0.1" max="0.8" step="0.1" x-model="selectedElement.styles.advanced.parallaxSpeed" @input="updateStyle('advanced.parallaxSpeed', $event.target.value)" class="vbp-field-range">
                                <span class="vbp-range-value" x-text="(selectedElement.styles.advanced.parallaxSpeed || 0.3)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="vbp-inspector-section vbp-danger-zone">
                    <h4 class="vbp-section-title"><?php esc_html_e( 'Zona de peligro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h4>
                    <button type="button" @click="deleteCurrentElement()" class="vbp-btn vbp-btn-danger vbp-btn-block">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        <?php esc_html_e( 'Eliminar elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Mini Color Picker Popup -->
    <div x-show="colorPickerOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="vbp-mini-color-picker"
         :style="{ top: colorPickerPosition.top + 'px', left: colorPickerPosition.left + 'px' }"
         @click.stop>
        <div class="vbp-mini-color-picker__header">
            <div class="vbp-mini-color-picker__preview"
                 :style="{ backgroundColor: colorPickerCurrentColor }"
                 @click="copyColorToClipboard(colorPickerCurrentColor)">
                <span class="vbp-mini-color-picker__hex"
                      :style="{ color: getContrastColor(colorPickerCurrentColor) }"
                      x-text="colorPickerCurrentColor"></span>
            </div>
            <input type="color"
                   class="vbp-mini-color-picker__input"
                   :value="colorPickerCurrentColor"
                   @input="updateColorFromInput($event)">
        </div>
        <div class="vbp-mini-color-picker__presets">
            <template x-for="(color, colorIndex) in colorPresets" :key="colorIndex + '-' + color">
                <button type="button"
                        class="vbp-mini-color-picker__swatch"
                        :class="{ 'vbp-mini-color-picker__swatch--active': color === colorPickerCurrentColor }"
                        :style="{ backgroundColor: color }"
                        :title="color"
                        @click="selectColor(color)">
                </button>
            </template>
        </div>
        <div class="vbp-mini-color-picker__actions">
            <button type="button" class="vbp-mini-color-picker__btn" @click="selectColor('transparent')">
                <?php esc_html_e( 'Transparente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>
            <button type="button" class="vbp-mini-color-picker__btn vbp-mini-color-picker__btn--close" @click="closeColorPicker()">
                <?php esc_html_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>
        </div>
    </div>

    <!-- Modal de URL Fallback (cuando wp.media no está disponible) -->
    <div x-show="urlModal.isOpen"
         x-cloak
         class="vbp-url-modal-overlay"
         @click.self="closeUrlModal()"
         @keydown.escape.window="closeUrlModal()">
        <div class="vbp-url-modal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">
            <div class="vbp-url-modal__header">
                <h3 x-text="urlModal.title"><?php esc_html_e( 'Introducir URL', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                <button type="button" class="vbp-url-modal__close" @click="closeUrlModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="vbp-url-modal__body">
                <p class="vbp-url-modal__hint">
                    <?php esc_html_e( 'La biblioteca de medios no está disponible. Introduce la URL directamente.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </p>
                <div class="vbp-field-group">
                    <label class="vbp-field-label"><?php esc_html_e( 'URL del archivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                    <input type="url"
                           x-model="urlModal.url"
                           :placeholder="getUrlPlaceholder()"
                           class="vbp-field-input"
                           :class="{ 'vbp-input-error': urlModal.error }"
                           @keydown.enter="confirmUrlModal()"
                           x-ref="urlModalInput">
                    <div x-show="urlModal.error" class="vbp-field-error" x-text="urlModal.error"></div>
                </div>
            </div>
            <div class="vbp-url-modal__footer">
                <button type="button" class="vbp-btn vbp-btn-secondary" @click="closeUrlModal()">
                    <?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="vbp-btn vbp-btn-primary" @click="confirmUrlModal()">
                    <?php esc_html_e( 'Aplicar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
