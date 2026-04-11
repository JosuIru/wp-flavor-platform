<?php
/**
 * Visual Builder Pro - Panel de Auto Layout
 *
 * Panel del inspector para configurar Auto Layout nivel Figma
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- ============================================ -->
<!-- Panel Auto Layout -->
<!-- ============================================ -->
<div class="vbp-auto-layout-panel" x-data="vbpAutoLayoutPanel()">
    <!-- Header del panel -->
    <div class="vbp-auto-layout-panel__header" @click="expanded = !expanded">
        <div class="vbp-auto-layout-panel__title">
            <svg class="vbp-auto-layout-panel__title-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 9h18M9 21V9"/>
            </svg>
            <span><?php esc_html_e( 'Auto Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <template x-if="hasAutoLayout">
                <span class="vbp-auto-layout-panel__badge"><?php esc_html_e( 'Activo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            </template>
        </div>
        <div class="vbp-auto-layout-panel__toggle" :class="{ 'is-expanded': expanded }">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
    </div>

    <!-- Contenido del panel -->
    <div class="vbp-auto-layout-panel__content" :class="{ 'is-collapsed': !expanded }">
        <!-- Botón para agregar Auto Layout -->
        <template x-if="!hasAutoLayout">
            <button type="button" class="vbp-auto-layout-add-btn" @click="addAutoLayout()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Agregar Auto Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                <small style="opacity: 0.7; font-size: 10px;">(Shift+A)</small>
            </button>
        </template>

        <!-- Configuración de Auto Layout -->
        <template x-if="hasAutoLayout">
            <div class="vbp-auto-layout-config">
                <!-- Tabs -->
                <div class="vbp-auto-layout-tabs">
                    <button type="button"
                            class="vbp-auto-layout-tab"
                            :class="{ 'is-active': activeTab === 'main' }"
                            @click="activeTab = 'main'">
                        <?php esc_html_e( 'Principal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button"
                            class="vbp-auto-layout-tab"
                            :class="{ 'is-active': activeTab === 'padding' }"
                            @click="activeTab = 'padding'">
                        <?php esc_html_e( 'Padding', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button"
                            class="vbp-auto-layout-tab"
                            :class="{ 'is-active': activeTab === 'advanced' }"
                            @click="activeTab = 'advanced'">
                        <?php esc_html_e( 'Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>

                <!-- Tab Principal -->
                <div x-show="activeTab === 'main'">
                    <!-- Dirección -->
                    <div class="vbp-auto-layout-direction">
                        <button type="button"
                                class="vbp-auto-layout-direction-btn"
                                :class="{ 'is-active': currentConfig && currentConfig.direction === 'vertical' }"
                                @click="updateValue('direction', 'vertical')">
                            <div class="vbp-auto-layout-direction-btn__icon">&#x2195;</div>
                            <span class="vbp-auto-layout-direction-btn__label"><?php esc_html_e( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                        <button type="button"
                                class="vbp-auto-layout-direction-btn"
                                :class="{ 'is-active': currentConfig && currentConfig.direction === 'horizontal' }"
                                @click="updateValue('direction', 'horizontal')">
                            <div class="vbp-auto-layout-direction-btn__icon">&#x2194;</div>
                            <span class="vbp-auto-layout-direction-btn__label"><?php esc_html_e( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </button>
                    </div>

                    <!-- Spacing -->
                    <div class="vbp-auto-layout-spacing">
                        <div class="vbp-auto-layout-spacing__header">
                            <span class="vbp-auto-layout-spacing__label"><?php esc_html_e( 'Spacing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </div>
                        <div class="vbp-auto-layout-spacing__row">
                            <div class="vbp-auto-layout-spacing__input-group">
                                <input type="number"
                                       class="vbp-auto-layout-spacing__input"
                                       :value="currentConfig ? currentConfig.spacing : 16"
                                       @input="updateValue('spacing', parseInt($event.target.value) || 0)"
                                       min="0"
                                       max="200"
                                       step="4">
                                <span class="vbp-auto-layout-spacing__unit">px</span>
                            </div>
                            <div class="vbp-auto-layout-spacing__mode">
                                <button type="button"
                                        class="vbp-auto-layout-spacing__mode-btn"
                                        :class="{ 'is-active': currentConfig && currentConfig.spacingMode === 'packed' }"
                                        @click="updateValue('spacingMode', 'packed')"
                                        title="<?php esc_attr_e( 'Packed', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    &#x25A0;
                                </button>
                                <button type="button"
                                        class="vbp-auto-layout-spacing__mode-btn"
                                        :class="{ 'is-active': currentConfig && currentConfig.spacingMode === 'space-between' }"
                                        @click="updateValue('spacingMode', 'space-between')"
                                        title="<?php esc_attr_e( 'Space Between', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                    &#x2261;
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Alineación -->
                    <div class="vbp-auto-layout-alignment">
                        <div class="vbp-auto-layout-alignment__header">
                            <span class="vbp-auto-layout-alignment__label"><?php esc_html_e( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <label class="vbp-auto-layout-alignment__wrap">
                                <input type="checkbox"
                                       class="vbp-auto-layout-alignment__wrap-checkbox"
                                       :checked="currentConfig && currentConfig.wrap"
                                       @change="toggleWrap()">
                                <span class="vbp-auto-layout-alignment__wrap-label"><?php esc_html_e( 'Wrap', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </label>
                        </div>
                        <div class="vbp-auto-layout-alignment__grid">
                            <!-- Fila 1 -->
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'start' && currentConfig.counterAlign === 'start' }"
                                    @click="updateValue('primaryAlign', 'start'); updateValue('counterAlign', 'start')">&#x2196;</button>
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'center' && currentConfig.counterAlign === 'start' }"
                                    @click="updateValue('primaryAlign', 'center'); updateValue('counterAlign', 'start')">&#x2191;</button>
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'end' && currentConfig.counterAlign === 'start' }"
                                    @click="updateValue('primaryAlign', 'end'); updateValue('counterAlign', 'start')">&#x2197;</button>
                            <!-- Fila 2 -->
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'start' && currentConfig.counterAlign === 'center' }"
                                    @click="updateValue('primaryAlign', 'start'); updateValue('counterAlign', 'center')">&#x2190;</button>
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'center' && currentConfig.counterAlign === 'center' }"
                                    @click="updateValue('primaryAlign', 'center'); updateValue('counterAlign', 'center')">&#x25CE;</button>
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'end' && currentConfig.counterAlign === 'center' }"
                                    @click="updateValue('primaryAlign', 'end'); updateValue('counterAlign', 'center')">&#x2192;</button>
                            <!-- Fila 3 -->
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'start' && currentConfig.counterAlign === 'end' }"
                                    @click="updateValue('primaryAlign', 'start'); updateValue('counterAlign', 'end')">&#x2199;</button>
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'center' && currentConfig.counterAlign === 'end' }"
                                    @click="updateValue('primaryAlign', 'center'); updateValue('counterAlign', 'end')">&#x2193;</button>
                            <button type="button" class="vbp-auto-layout-alignment__btn"
                                    :class="{ 'is-active': currentConfig && currentConfig.primaryAlign === 'end' && currentConfig.counterAlign === 'end' }"
                                    @click="updateValue('primaryAlign', 'end'); updateValue('counterAlign', 'end')">&#x2198;</button>
                        </div>
                    </div>

                    <!-- Sizing -->
                    <div class="vbp-auto-layout-sizing">
                        <span class="vbp-auto-layout-sizing__label"><?php esc_html_e( 'Sizing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>

                        <!-- Primary axis (W para horizontal, H para vertical) -->
                        <div class="vbp-auto-layout-sizing__row">
                            <span class="vbp-auto-layout-sizing__dimension" x-text="currentConfig && currentConfig.direction === 'horizontal' ? 'W' : 'H'"></span>
                            <div class="vbp-auto-layout-sizing__options">
                                <button type="button" class="vbp-auto-layout-sizing__option"
                                        :class="{ 'is-active': currentConfig && currentConfig.primarySizing === 'hug' }"
                                        @click="updateValue('primarySizing', 'hug')">
                                    <span class="vbp-auto-layout-sizing__option-icon">&#x21B9;</span>
                                    <span class="vbp-auto-layout-sizing__option-label"><?php esc_html_e( 'Hug', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </button>
                                <button type="button" class="vbp-auto-layout-sizing__option"
                                        :class="{ 'is-active': currentConfig && currentConfig.primarySizing === 'fixed' }"
                                        @click="updateValue('primarySizing', 'fixed')">
                                    <span class="vbp-auto-layout-sizing__option-icon">&#x1F4CF;</span>
                                    <span class="vbp-auto-layout-sizing__option-label"><?php esc_html_e( 'Fixed', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </button>
                                <button type="button" class="vbp-auto-layout-sizing__option"
                                        :class="{ 'is-active': currentConfig && currentConfig.primarySizing === 'fill' }"
                                        @click="updateValue('primarySizing', 'fill')">
                                    <span class="vbp-auto-layout-sizing__option-icon">&#x2194;</span>
                                    <span class="vbp-auto-layout-sizing__option-label"><?php esc_html_e( 'Fill', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </button>
                            </div>
                        </div>

                        <!-- Counter axis -->
                        <div class="vbp-auto-layout-sizing__row">
                            <span class="vbp-auto-layout-sizing__dimension" x-text="currentConfig && currentConfig.direction === 'horizontal' ? 'H' : 'W'"></span>
                            <div class="vbp-auto-layout-sizing__options">
                                <button type="button" class="vbp-auto-layout-sizing__option"
                                        :class="{ 'is-active': currentConfig && currentConfig.counterSizing === 'hug' }"
                                        @click="updateValue('counterSizing', 'hug')">
                                    <span class="vbp-auto-layout-sizing__option-icon">&#x21B9;</span>
                                    <span class="vbp-auto-layout-sizing__option-label"><?php esc_html_e( 'Hug', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </button>
                                <button type="button" class="vbp-auto-layout-sizing__option"
                                        :class="{ 'is-active': currentConfig && currentConfig.counterSizing === 'fixed' }"
                                        @click="updateValue('counterSizing', 'fixed')">
                                    <span class="vbp-auto-layout-sizing__option-icon">&#x1F4CF;</span>
                                    <span class="vbp-auto-layout-sizing__option-label"><?php esc_html_e( 'Fixed', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </button>
                                <button type="button" class="vbp-auto-layout-sizing__option"
                                        :class="{ 'is-active': currentConfig && currentConfig.counterSizing === 'fill' }"
                                        @click="updateValue('counterSizing', 'fill')">
                                    <span class="vbp-auto-layout-sizing__option-icon">&#x2194;</span>
                                    <span class="vbp-auto-layout-sizing__option-label"><?php esc_html_e( 'Fill', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Presets -->
                    <div class="vbp-auto-layout-presets">
                        <span class="vbp-auto-layout-presets__label"><?php esc_html_e( 'Presets', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        <div class="vbp-auto-layout-presets__grid">
                            <template x-for="(preset, presetName) in presets" :key="presetName">
                                <button type="button"
                                        class="vbp-auto-layout-preset-btn"
                                        :class="{ 'is-active': getActivePreset() === presetName }"
                                        @click="applyPreset(presetName)"
                                        :title="preset.label">
                                    <span class="vbp-auto-layout-preset-btn__icon" x-html="preset.icon"></span>
                                    <span class="vbp-auto-layout-preset-btn__label" x-text="preset.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Tab Padding -->
                <div x-show="activeTab === 'padding'">
                    <div class="vbp-auto-layout-padding">
                        <div class="vbp-auto-layout-padding__header">
                            <span class="vbp-auto-layout-padding__label"><?php esc_html_e( 'Padding', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <label class="vbp-auto-layout-padding__toggle">
                                <input type="checkbox" :checked="independentPadding" @change="independentPadding = !independentPadding">
                                <span><?php esc_html_e( 'Independiente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </label>
                        </div>

                        <!-- Modo uniforme -->
                        <template x-if="!independentPadding">
                            <div class="vbp-auto-layout-padding__visual">
                                <div class="vbp-auto-layout-padding__box">
                                    <div class="vbp-auto-layout-padding__inner"></div>
                                    <input type="number"
                                           class="vbp-auto-layout-padding__input vbp-auto-layout-padding__input--top"
                                           :value="currentConfig ? currentConfig.padding.top : 0"
                                           @input="updatePadding('top', $event.target.value)"
                                           min="0" step="4">
                                    <input type="number"
                                           class="vbp-auto-layout-padding__input vbp-auto-layout-padding__input--right"
                                           :value="currentConfig ? currentConfig.padding.right : 0"
                                           @input="updatePadding('right', $event.target.value)"
                                           min="0" step="4">
                                    <input type="number"
                                           class="vbp-auto-layout-padding__input vbp-auto-layout-padding__input--bottom"
                                           :value="currentConfig ? currentConfig.padding.bottom : 0"
                                           @input="updatePadding('bottom', $event.target.value)"
                                           min="0" step="4">
                                    <input type="number"
                                           class="vbp-auto-layout-padding__input vbp-auto-layout-padding__input--left"
                                           :value="currentConfig ? currentConfig.padding.left : 0"
                                           @input="updatePadding('left', $event.target.value)"
                                           min="0" step="4">
                                </div>
                            </div>
                        </template>

                        <!-- Modo independiente -->
                        <template x-if="independentPadding">
                            <div class="vbp-auto-layout-padding__grid">
                                <div class="vbp-auto-layout-padding__field">
                                    <span class="vbp-auto-layout-padding__field-label"><?php esc_html_e( 'Top', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <input type="number"
                                           class="vbp-auto-layout-padding__field-input"
                                           :value="currentConfig ? currentConfig.padding.top : 0"
                                           @input="updatePadding('top', $event.target.value)"
                                           min="0" step="4">
                                </div>
                                <div class="vbp-auto-layout-padding__field">
                                    <span class="vbp-auto-layout-padding__field-label"><?php esc_html_e( 'Right', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <input type="number"
                                           class="vbp-auto-layout-padding__field-input"
                                           :value="currentConfig ? currentConfig.padding.right : 0"
                                           @input="updatePadding('right', $event.target.value)"
                                           min="0" step="4">
                                </div>
                                <div class="vbp-auto-layout-padding__field">
                                    <span class="vbp-auto-layout-padding__field-label"><?php esc_html_e( 'Bottom', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <input type="number"
                                           class="vbp-auto-layout-padding__field-input"
                                           :value="currentConfig ? currentConfig.padding.bottom : 0"
                                           @input="updatePadding('bottom', $event.target.value)"
                                           min="0" step="4">
                                </div>
                                <div class="vbp-auto-layout-padding__field">
                                    <span class="vbp-auto-layout-padding__field-label"><?php esc_html_e( 'Left', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    <input type="number"
                                           class="vbp-auto-layout-padding__field-input"
                                           :value="currentConfig ? currentConfig.padding.left : 0"
                                           @input="updatePadding('left', $event.target.value)"
                                           min="0" step="4">
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Wrap Spacing -->
                    <div class="vbp-auto-layout-spacing" x-show="currentConfig && currentConfig.wrap">
                        <div class="vbp-auto-layout-spacing__header">
                            <span class="vbp-auto-layout-spacing__label"><?php esc_html_e( 'Wrap Spacing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </div>
                        <div class="vbp-auto-layout-spacing__row">
                            <div class="vbp-auto-layout-spacing__input-group">
                                <input type="number"
                                       class="vbp-auto-layout-spacing__input"
                                       :value="currentConfig ? currentConfig.wrapSpacing : 16"
                                       @input="updateValue('wrapSpacing', parseInt($event.target.value) || 0)"
                                       min="0"
                                       max="200"
                                       step="4">
                                <span class="vbp-auto-layout-spacing__unit">px</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Avanzado -->
                <div x-show="activeTab === 'advanced'">
                    <div class="vbp-auto-layout-advanced">
                        <!-- Reverse -->
                        <div class="vbp-auto-layout-advanced__row">
                            <span class="vbp-auto-layout-advanced__label"><?php esc_html_e( 'Reverse', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <button type="button"
                                    class="vbp-auto-layout-advanced__toggle"
                                    :class="{ 'is-active': currentConfig && currentConfig.reverse }"
                                    @click="toggleReverse()">
                            </button>
                        </div>

                        <!-- Clip Content -->
                        <div class="vbp-auto-layout-advanced__row">
                            <span class="vbp-auto-layout-advanced__label"><?php esc_html_e( 'Clip Content', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            <button type="button"
                                    class="vbp-auto-layout-advanced__toggle"
                                    :class="{ 'is-active': currentConfig && currentConfig.clipContent }"
                                    @click="toggleClipContent()">
                            </button>
                        </div>

                        <!-- Spacing Mode Selector -->
                        <div class="vbp-field-group" style="margin-top: 16px;">
                            <label class="vbp-field-label"><?php esc_html_e( 'Modo de Spacing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select class="vbp-field-select"
                                    :value="currentConfig ? currentConfig.spacingMode : 'packed'"
                                    @change="updateValue('spacingMode', $event.target.value)">
                                <template x-for="(mode, modeName) in spacingModes" :key="modeName">
                                    <option :value="modeName" x-text="mode.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Exportar CSS -->
                    <div class="vbp-auto-layout-export">
                        <button type="button" class="vbp-auto-layout-export__btn" @click="navigator.clipboard.writeText(exportCSS()); $dispatch('vbp-toast', { message: '<?php esc_attr_e( 'CSS copiado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' })">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                            </svg>
                            <?php esc_html_e( 'Copiar CSS', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>

                <!-- Botón Remover -->
                <button type="button" class="vbp-auto-layout-remove-btn" @click="removeAutoLayout()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3,6 5,6 21,6"/>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    </svg>
                    <?php esc_html_e( 'Remover Auto Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    <small style="opacity: 0.7; font-size: 10px;">(Alt+Shift+A)</small>
                </button>
            </div>
        </template>
    </div>
</div>

<!-- ============================================ -->
<!-- Panel Layout Child (para hijos de Auto Layout) -->
<!-- ============================================ -->
<template x-if="typeof vbpLayoutChildPanel !== 'undefined'">
    <div class="vbp-layout-child-panel" x-data="vbpLayoutChildPanel()" x-show="parentHasAutoLayout">
        <div class="vbp-layout-child-panel__header">
            <span class="vbp-layout-child-panel__title"><?php esc_html_e( 'Layout Child', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <span class="vbp-layout-child-panel__badge"><?php esc_html_e( 'En Auto Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </div>

        <!-- Sizing -->
        <div class="vbp-layout-child-panel__section">
            <span class="vbp-layout-child-panel__label"><?php esc_html_e( 'Sizing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <div class="vbp-layout-child-panel__sizing-options">
                <button type="button"
                        class="vbp-layout-child-panel__sizing-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.sizing === 'hug' }"
                        @click="updateValue('sizing', 'hug')">
                    <div class="vbp-layout-child-panel__sizing-btn-icon">&#x21B9;</div>
                    <span class="vbp-layout-child-panel__sizing-btn-label"><?php esc_html_e( 'Hug', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                </button>
                <button type="button"
                        class="vbp-layout-child-panel__sizing-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.sizing === 'fixed' }"
                        @click="updateValue('sizing', 'fixed')">
                    <div class="vbp-layout-child-panel__sizing-btn-icon">&#x1F4CF;</div>
                    <span class="vbp-layout-child-panel__sizing-btn-label"><?php esc_html_e( 'Fixed', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                </button>
                <button type="button"
                        class="vbp-layout-child-panel__sizing-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.sizing === 'fill' }"
                        @click="updateValue('sizing', 'fill')">
                    <div class="vbp-layout-child-panel__sizing-btn-icon">&#x2194;</div>
                    <span class="vbp-layout-child-panel__sizing-btn-label"><?php esc_html_e( 'Fill', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                </button>
            </div>
        </div>

        <!-- Fill Ratio (solo si sizing es fill) -->
        <div class="vbp-layout-child-panel__section" x-show="layoutChild && layoutChild.sizing === 'fill'">
            <span class="vbp-layout-child-panel__label"><?php esc_html_e( 'Fill Ratio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <div class="vbp-layout-child-panel__fill-ratio">
                <input type="number"
                       class="vbp-layout-child-panel__fill-ratio-input"
                       :value="layoutChild ? layoutChild.fillRatio : 1"
                       @input="updateValue('fillRatio', parseFloat($event.target.value) || 1)"
                       min="0.1"
                       max="10"
                       step="0.1">
                <span class="vbp-layout-child-panel__fill-ratio-hint"><?php esc_html_e( 'Relativo a otros fills', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            </div>
        </div>

        <!-- Min/Max Constraints -->
        <div class="vbp-layout-child-panel__section">
            <span class="vbp-layout-child-panel__label"><?php esc_html_e( 'Constraints', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <div class="vbp-layout-child-panel__constraints">
                <div class="vbp-layout-child-panel__constraint">
                    <span class="vbp-layout-child-panel__constraint-label"><?php esc_html_e( 'Min W', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__constraint-input"
                           :value="layoutChild && layoutChild.minWidth !== null ? layoutChild.minWidth : ''"
                           @input="updateValue('minWidth', $event.target.value ? parseInt($event.target.value) : null)"
                           placeholder="auto"
                           min="0">
                </div>
                <div class="vbp-layout-child-panel__constraint">
                    <span class="vbp-layout-child-panel__constraint-label"><?php esc_html_e( 'Max W', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__constraint-input"
                           :value="layoutChild && layoutChild.maxWidth !== null ? layoutChild.maxWidth : ''"
                           @input="updateValue('maxWidth', $event.target.value ? parseInt($event.target.value) : null)"
                           placeholder="auto"
                           min="0">
                </div>
                <div class="vbp-layout-child-panel__constraint">
                    <span class="vbp-layout-child-panel__constraint-label"><?php esc_html_e( 'Min H', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__constraint-input"
                           :value="layoutChild && layoutChild.minHeight !== null ? layoutChild.minHeight : ''"
                           @input="updateValue('minHeight', $event.target.value ? parseInt($event.target.value) : null)"
                           placeholder="auto"
                           min="0">
                </div>
                <div class="vbp-layout-child-panel__constraint">
                    <span class="vbp-layout-child-panel__constraint-label"><?php esc_html_e( 'Max H', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__constraint-input"
                           :value="layoutChild && layoutChild.maxHeight !== null ? layoutChild.maxHeight : ''"
                           @input="updateValue('maxHeight', $event.target.value ? parseInt($event.target.value) : null)"
                           placeholder="auto"
                           min="0">
                </div>
            </div>
        </div>

        <!-- Align Self -->
        <div class="vbp-layout-child-panel__section">
            <span class="vbp-layout-child-panel__label"><?php esc_html_e( 'Align Self', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <div class="vbp-layout-child-panel__align-options">
                <button type="button"
                        class="vbp-layout-child-panel__align-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.alignSelf === 'auto' }"
                        @click="updateValue('alignSelf', 'auto')">
                    <?php esc_html_e( 'Auto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button type="button"
                        class="vbp-layout-child-panel__align-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.alignSelf === 'start' }"
                        @click="updateValue('alignSelf', 'start')">
                    &#x21E4;
                </button>
                <button type="button"
                        class="vbp-layout-child-panel__align-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.alignSelf === 'center' }"
                        @click="updateValue('alignSelf', 'center')">
                    &#x21C6;
                </button>
                <button type="button"
                        class="vbp-layout-child-panel__align-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.alignSelf === 'end' }"
                        @click="updateValue('alignSelf', 'end')">
                    &#x21E5;
                </button>
                <button type="button"
                        class="vbp-layout-child-panel__align-btn"
                        :class="{ 'is-active': layoutChild && layoutChild.alignSelf === 'stretch' }"
                        @click="updateValue('alignSelf', 'stretch')">
                    &#x2195;
                </button>
            </div>
        </div>

        <!-- Absolute Positioning -->
        <div class="vbp-layout-child-panel__absolute">
            <div class="vbp-layout-child-panel__absolute-header">
                <span class="vbp-layout-child-panel__absolute-title"><?php esc_html_e( 'Posición Absoluta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <button type="button"
                        class="vbp-layout-child-panel__absolute-toggle"
                        :class="{ 'is-active': layoutChild && layoutChild.absolute }"
                        @click="toggleAbsolute()">
                </button>
            </div>
            <div class="vbp-layout-child-panel__absolute-positions" x-show="layoutChild && layoutChild.absolute">
                <div class="vbp-layout-child-panel__absolute-position">
                    <span class="vbp-layout-child-panel__absolute-position-label"><?php esc_html_e( 'Top', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__absolute-position-input"
                           :value="layoutChild && layoutChild.absolutePosition ? layoutChild.absolutePosition.top : ''"
                           @input="updateAbsolutePosition('top', $event.target.value)"
                           placeholder="auto">
                </div>
                <div class="vbp-layout-child-panel__absolute-position">
                    <span class="vbp-layout-child-panel__absolute-position-label"><?php esc_html_e( 'Right', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__absolute-position-input"
                           :value="layoutChild && layoutChild.absolutePosition ? layoutChild.absolutePosition.right : ''"
                           @input="updateAbsolutePosition('right', $event.target.value)"
                           placeholder="auto">
                </div>
                <div class="vbp-layout-child-panel__absolute-position">
                    <span class="vbp-layout-child-panel__absolute-position-label"><?php esc_html_e( 'Bottom', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__absolute-position-input"
                           :value="layoutChild && layoutChild.absolutePosition ? layoutChild.absolutePosition.bottom : ''"
                           @input="updateAbsolutePosition('bottom', $event.target.value)"
                           placeholder="auto">
                </div>
                <div class="vbp-layout-child-panel__absolute-position">
                    <span class="vbp-layout-child-panel__absolute-position-label"><?php esc_html_e( 'Left', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    <input type="number"
                           class="vbp-layout-child-panel__absolute-position-input"
                           :value="layoutChild && layoutChild.absolutePosition ? layoutChild.absolutePosition.left : ''"
                           @input="updateAbsolutePosition('left', $event.target.value)"
                           placeholder="auto">
                </div>
            </div>
        </div>
    </div>
</template>
