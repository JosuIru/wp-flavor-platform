/**
 * Visual Builder Pro - Dev Panel Template
 * Template HTML para el panel de Dev Mode
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

/**
 * Genera el HTML del panel Dev Mode
 * @returns {string} HTML del panel
 */
function getDevPanelTemplate() {
    return `
<div x-data="vbpDevPanel()"
     x-show="$store.vbpDevMode.enabled"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     class="vbp-dev-panel"
     :class="{ collapsed: isCollapsed }"
     :style="{ width: panelWidth + 'px' }">

    <!-- Resize Handle -->
    <div class="vbp-dev-panel-resize" @mousedown="startResize($event)"></div>

    <!-- Header -->
    <div class="vbp-dev-panel-header">
        <div class="vbp-dev-panel-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            <span>Dev Mode</span>
        </div>
        <div class="vbp-dev-panel-actions">
            <button class="vbp-dev-panel-btn"
                    :class="{ active: $store.vbpDevMode.measureMode }"
                    @click="$store.vbpDevMode.toggleMeasureMode()"
                    title="Medir distancias (Alt+Click)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 12h20M12 2v20M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
            <button class="vbp-dev-panel-btn"
                    @click="toggleCollapse()"
                    :title="isCollapsed ? 'Expandir' : 'Colapsar'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline :points="isCollapsed ? '15 18 9 12 15 6' : '9 18 15 12 9 6'"/>
                </svg>
            </button>
            <button class="vbp-dev-panel-btn"
                    @click="$store.vbpDevMode.toggle()"
                    title="Cerrar Dev Mode">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="vbp-dev-tabs">
        <button class="vbp-dev-tab"
                :class="{ active: $store.vbpDevMode.activeTab === 'css' }"
                @click="$store.vbpDevMode.activeTab = 'css'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22 6 12 13 2 6"/>
            </svg>
            CSS
        </button>
        <button class="vbp-dev-tab"
                :class="{ active: $store.vbpDevMode.activeTab === 'code' }"
                @click="$store.vbpDevMode.activeTab = 'code'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="16 18 22 12 16 6"/>
                <polyline points="8 6 2 12 8 18"/>
            </svg>
            Code
        </button>
        <button class="vbp-dev-tab"
                :class="{ active: $store.vbpDevMode.activeTab === 'assets' }"
                @click="$store.vbpDevMode.activeTab = 'assets'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
            Assets
            <span class="vbp-dev-tab-badge" x-show="$store.vbpDevMode.assets.length > 0" x-text="$store.vbpDevMode.assets.length"></span>
        </button>
        <button class="vbp-dev-tab"
                :class="{ active: $store.vbpDevMode.activeTab === 'tokens' }"
                @click="$store.vbpDevMode.activeTab = 'tokens'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Tokens
        </button>
        <button class="vbp-dev-tab"
                :class="{ active: $store.vbpDevMode.activeTab === 'compare' }"
                @click="$store.vbpDevMode.activeTab = 'compare'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Compare
        </button>
    </div>

    <!-- Content -->
    <div class="vbp-dev-panel-content">
        <!-- No Selection State -->
        <template x-if="!$store.vbpDevMode.selectedElementId">
            <div class="vbp-dev-empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div class="vbp-dev-empty-state-title">Selecciona un elemento</div>
                <div class="vbp-dev-empty-state-description">
                    Haz clic en cualquier elemento del canvas para inspeccionar sus estilos y propiedades.
                </div>
            </div>
        </template>

        <!-- CSS Tab -->
        <template x-if="$store.vbpDevMode.selectedElementId && $store.vbpDevMode.activeTab === 'css'">
            <div>
                <!-- Toolbar -->
                <div class="vbp-dev-toolbar">
                    <div class="vbp-dev-toolbar-group">
                        <span class="vbp-dev-toolbar-label">Formato</span>
                        <select class="vbp-dev-toolbar-select"
                                x-model="$store.vbpDevMode.codeFormat"
                                @change="$store.vbpDevMode.updateGeneratedCode()">
                            <option value="css">CSS</option>
                            <option value="scss">SCSS</option>
                            <option value="tailwind">Tailwind</option>
                            <option value="styled-components">Styled</option>
                            <option value="css-in-js">CSS-in-JS</option>
                        </select>
                    </div>
                    <div class="vbp-dev-toolbar-group">
                        <span class="vbp-dev-toolbar-label">Unidad</span>
                        <select class="vbp-dev-toolbar-select"
                                x-model="$store.vbpDevMode.units"
                                @change="$store.vbpDevMode.updateGeneratedCode()">
                            <option value="px">px</option>
                            <option value="rem">rem</option>
                            <option value="em">em</option>
                        </select>
                    </div>
                    <div class="vbp-dev-toolbar-group" x-show="$store.vbpDevMode.units === 'rem'">
                        <span class="vbp-dev-toolbar-label">Base</span>
                        <input type="number"
                               class="vbp-dev-toolbar-input"
                               x-model="$store.vbpDevMode.remBase"
                               @change="$store.vbpDevMode.setRemBase($store.vbpDevMode.remBase)"
                               min="10" max="24">
                    </div>
                </div>

                <!-- Code Block -->
                <div class="vbp-dev-code-container">
                    <div class="vbp-dev-code-header">
                        <span class="vbp-dev-code-lang" x-text="$store.vbpDevMode.codeFormat.toUpperCase()"></span>
                        <button class="vbp-dev-code-copy"
                                @click="$store.vbpDevMode.copyToClipboard($store.vbpDevMode.generatedCode, 'css')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                            Copiar
                        </button>
                    </div>
                    <div class="vbp-dev-code-block">
                        <pre><code x-html="VBPSyntaxHighlighter ? VBPSyntaxHighlighter.highlight($store.vbpDevMode.generatedCode, $store.vbpDevMode.codeFormat) : $store.vbpDevMode.generatedCode"></code></pre>
                    </div>
                </div>

                <!-- Measurements -->
                <div class="vbp-dev-measurements" x-show="$store.vbpDevMode.measurements">
                    <div class="vbp-dev-measurements-title">Medidas</div>
                    <div class="vbp-dev-measurements-grid">
                        <div class="vbp-dev-measurement-item">
                            <span class="vbp-dev-measurement-label">W</span>
                            <span class="vbp-dev-measurement-value" x-text="($store.vbpDevMode.measurements?.width || 0) + 'px'"></span>
                        </div>
                        <div class="vbp-dev-measurement-item">
                            <span class="vbp-dev-measurement-label">H</span>
                            <span class="vbp-dev-measurement-value" x-text="($store.vbpDevMode.measurements?.height || 0) + 'px'"></span>
                        </div>
                        <div class="vbp-dev-measurement-item">
                            <span class="vbp-dev-measurement-label">X</span>
                            <span class="vbp-dev-measurement-value" x-text="($store.vbpDevMode.measurements?.x || 0) + 'px'"></span>
                        </div>
                        <div class="vbp-dev-measurement-item">
                            <span class="vbp-dev-measurement-label">Y</span>
                            <span class="vbp-dev-measurement-value" x-text="($store.vbpDevMode.measurements?.y || 0) + 'px'"></span>
                        </div>
                    </div>
                </div>

                <!-- Style Categories -->
                <div class="vbp-dev-style-categories" x-show="$store.vbpDevMode.extractedStyles">
                    <template x-for="(category, categoryName) in ($store.vbpDevMode.extractedStyles || {})" :key="categoryName">
                        <div class="vbp-dev-style-category" x-data="{ open: categoryName === 'layout' }">
                            <button class="vbp-dev-style-category-header" @click="open = !open" :class="{ open: open }">
                                <span x-html="getCategoryIcon(categoryName)"></span>
                                <span x-text="getCategoryName(categoryName)"></span>
                                <span class="count" x-text="Object.keys(category).filter(k => category[k] && category[k] !== '' && category[k] !== 'none').length"></span>
                                <svg class="arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div class="vbp-dev-style-category-content" x-show="open" x-collapse>
                                <template x-for="(value, prop) in category" :key="prop">
                                    <div class="vbp-dev-style-property" x-show="value && value !== '' && value !== 'none' && value !== 'normal'">
                                        <template x-if="prop.toLowerCase().includes('color') && value && value.startsWith('#')">
                                            <span class="vbp-dev-color-preview" :style="{ backgroundColor: value }"></span>
                                        </template>
                                        <span class="vbp-dev-style-property-name" x-text="formatPropertyName(prop)"></span>
                                        <span class="vbp-dev-style-property-value" x-text="value"></span>
                                        <button class="vbp-dev-style-property-copy" @click="copyProperty(prop, value)" title="Copiar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Code Tab -->
        <template x-if="$store.vbpDevMode.selectedElementId && $store.vbpDevMode.activeTab === 'code'">
            <div>
                <!-- Toolbar -->
                <div class="vbp-dev-toolbar">
                    <div class="vbp-dev-toolbar-group">
                        <span class="vbp-dev-toolbar-label">Framework</span>
                        <select class="vbp-dev-toolbar-select"
                                x-model="$store.vbpDevMode.componentFramework">
                            <option value="react">React</option>
                            <option value="vue">Vue 3</option>
                            <option value="html">HTML</option>
                        </select>
                    </div>
                </div>

                <!-- Code Block -->
                <div class="vbp-dev-code-container">
                    <div class="vbp-dev-code-header">
                        <span class="vbp-dev-code-lang" x-text="$store.vbpDevMode.componentFramework.toUpperCase()"></span>
                        <button class="vbp-dev-code-copy"
                                @click="$store.vbpDevMode.copyToClipboard($store.vbpDevMode.generateComponent($store.vbpDevMode.componentFramework), 'component')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                            Copiar
                        </button>
                    </div>
                    <div class="vbp-dev-code-block">
                        <pre><code x-html="VBPSyntaxHighlighter ? VBPSyntaxHighlighter.highlight($store.vbpDevMode.generateComponent($store.vbpDevMode.componentFramework), $store.vbpDevMode.componentFramework) : $store.vbpDevMode.generateComponent($store.vbpDevMode.componentFramework)"></code></pre>
                    </div>
                </div>
            </div>
        </template>

        <!-- Assets Tab -->
        <template x-if="$store.vbpDevMode.selectedElementId && $store.vbpDevMode.activeTab === 'assets'">
            <div class="vbp-dev-assets">
                <template x-if="$store.vbpDevMode.assets.length > 0">
                    <div>
                        <div class="vbp-dev-assets-header">
                            <span class="vbp-dev-assets-title">Assets (<span x-text="$store.vbpDevMode.assets.length"></span>)</span>
                            <button class="vbp-dev-assets-export-all" @click="$store.vbpDevMode.exportAllAssets()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Exportar Todo
                            </button>
                        </div>
                        <div class="vbp-dev-assets-list">
                            <template x-for="(asset, index) in $store.vbpDevMode.assets" :key="index">
                                <div class="vbp-dev-asset-item">
                                    <div class="vbp-dev-asset-preview">
                                        <template x-if="asset.type === 'image' || asset.type === 'svg'">
                                            <img :src="asset.url" :alt="asset.name" loading="lazy">
                                        </template>
                                        <template x-if="asset.type !== 'image' && asset.type !== 'svg'">
                                            <span x-html="$store.vbpDevMode.getAssetIcon(asset.type)"></span>
                                        </template>
                                    </div>
                                    <div class="vbp-dev-asset-info">
                                        <div class="vbp-dev-asset-name" x-text="asset.name"></div>
                                        <div class="vbp-dev-asset-type" x-text="asset.type"></div>
                                    </div>
                                    <button class="vbp-dev-asset-export" @click="$store.vbpDevMode.exportAsset(asset)" title="Descargar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="$store.vbpDevMode.assets.length === 0">
                    <div class="vbp-dev-assets-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <path d="M21 15l-5-5L5 21"/>
                        </svg>
                        <p>No hay assets en este elemento</p>
                    </div>
                </template>
            </div>
        </template>

        <!-- Tokens Tab -->
        <template x-if="$store.vbpDevMode.selectedElementId && $store.vbpDevMode.activeTab === 'tokens'">
            <div class="vbp-dev-tokens">
                <template x-if="$store.vbpDevMode.totalTokensCount() > 0">
                    <div>
                        <!-- Colors -->
                        <div class="vbp-dev-tokens-section" x-show="$store.vbpDevMode.hasColorTokens()">
                            <div class="vbp-dev-tokens-section-title">Colores</div>
                            <template x-for="(value, name) in ($store.vbpDevMode.usedTokens?.colors || {})" :key="name">
                                <div class="vbp-dev-token-item">
                                    <span class="vbp-dev-color-preview" :style="{ backgroundColor: value }"></span>
                                    <span class="vbp-dev-token-name" x-text="name"></span>
                                    <span class="vbp-dev-token-value" x-text="value"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Spacing -->
                        <div class="vbp-dev-tokens-section" x-show="$store.vbpDevMode.hasSpacingTokens()">
                            <div class="vbp-dev-tokens-section-title">Espaciado</div>
                            <template x-for="(value, name) in ($store.vbpDevMode.usedTokens?.spacing || {})" :key="name">
                                <div class="vbp-dev-token-item">
                                    <span class="vbp-dev-token-name" x-text="name"></span>
                                    <span class="vbp-dev-token-value" x-text="value"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Typography -->
                        <div class="vbp-dev-tokens-section" x-show="$store.vbpDevMode.hasTypographyTokens()">
                            <div class="vbp-dev-tokens-section-title">Tipografia</div>
                            <template x-for="(value, name) in ($store.vbpDevMode.usedTokens?.typography || {})" :key="name">
                                <div class="vbp-dev-token-item">
                                    <span class="vbp-dev-token-name" x-text="name"></span>
                                    <span class="vbp-dev-token-value" x-text="value"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Copy All Tokens -->
                        <div style="padding: 16px;">
                            <button class="vbp-dev-compare-btn"
                                    @click="$store.vbpDevMode.copyToClipboard($store.vbpDevMode.generateTokensCSS(), 'tokens')">
                                Copiar CSS Variables
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="$store.vbpDevMode.totalTokensCount() === 0">
                    <div class="vbp-dev-tokens-empty">
                        <p>No se encontraron tokens de diseno en este elemento</p>
                    </div>
                </template>
            </div>
        </template>

        <!-- Compare Tab -->
        <template x-if="$store.vbpDevMode.selectedElementId && $store.vbpDevMode.activeTab === 'compare'">
            <div class="vbp-dev-compare">
                <div class="vbp-dev-compare-input">
                    <label>Pega aqui los estilos implementados (JSON)</label>
                    <textarea x-model="compareCodeInput"
                              placeholder='{"display": "flex", "padding": "16px", ...}'></textarea>
                </div>
                <button class="vbp-dev-compare-btn" @click="applyComparison()">
                    Comparar
                </button>

                <template x-if="$store.vbpDevMode.comparisonResult">
                    <div class="vbp-dev-compare-result" :class="getComparisonClass($store.vbpDevMode.comparisonResult)">
                        <div class="vbp-dev-compare-score">
                            <span class="vbp-dev-compare-score-value" x-text="$store.vbpDevMode.comparisonResult.matchPercentage + '%'"></span>
                            <span class="vbp-dev-compare-score-label">coincidencia</span>
                        </div>

                        <!-- Matches -->
                        <div class="vbp-dev-compare-section" x-show="$store.vbpDevMode.comparisonResult.matches.length > 0">
                            <div class="vbp-dev-compare-section-title matches">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                Coinciden (<span x-text="$store.vbpDevMode.comparisonResult.matches.length"></span>)
                            </div>
                            <template x-for="prop in $store.vbpDevMode.comparisonResult.matches" :key="prop">
                                <div class="vbp-dev-compare-item">
                                    <span class="vbp-dev-compare-item-prop" x-text="formatPropertyName(prop)"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Differs -->
                        <div class="vbp-dev-compare-section" x-show="$store.vbpDevMode.comparisonResult.differs.length > 0">
                            <div class="vbp-dev-compare-section-title differs">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                Difieren (<span x-text="$store.vbpDevMode.comparisonResult.differs.length"></span>)
                            </div>
                            <template x-for="diff in $store.vbpDevMode.comparisonResult.differs" :key="diff.property">
                                <div class="vbp-dev-compare-item">
                                    <span class="vbp-dev-compare-item-prop" x-text="formatPropertyName(diff.property)"></span>
                                    <span class="vbp-dev-compare-item-design" x-text="diff.design"></span>
                                    <span class="vbp-dev-compare-item-arrow">vs</span>
                                    <span class="vbp-dev-compare-item-code" x-text="diff.code"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Missing -->
                        <div class="vbp-dev-compare-section" x-show="$store.vbpDevMode.comparisonResult.missing.length > 0">
                            <div class="vbp-dev-compare-section-title missing">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Faltan (<span x-text="$store.vbpDevMode.comparisonResult.missing.length"></span>)
                            </div>
                            <template x-for="item in $store.vbpDevMode.comparisonResult.missing" :key="item.property">
                                <div class="vbp-dev-compare-item">
                                    <span class="vbp-dev-compare-item-prop" x-text="formatPropertyName(item.property)"></span>
                                    <span class="vbp-dev-compare-item-design" x-text="item.design"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Footer -->
    <div class="vbp-dev-panel-footer">
        <div class="vbp-dev-panel-footer-info">
            <span x-show="$store.vbpDevMode.selectedElementId" x-text="$store.vbpDevMode.selectedElementId"></span>
        </div>
        <div class="vbp-dev-panel-footer-shortcuts">
            <span class="vbp-dev-shortcut">
                <kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd>
                <span>Toggle</span>
            </span>
        </div>
    </div>
</div>
`;
}

/**
 * Inyecta el panel en el DOM
 */
function injectDevPanel() {
    // Verificar si ya existe
    if (document.querySelector('.vbp-dev-panel')) {
        return;
    }

    // Crear contenedor
    var panelContainer = document.createElement('div');
    panelContainer.id = 'vbp-dev-panel-container';
    panelContainer.innerHTML = getDevPanelTemplate();

    // Agregar al body
    document.body.appendChild(panelContainer);

    vbpLog.log('Dev Panel injected into DOM');
}

// Inyectar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectDevPanel);
} else {
    injectDevPanel();
}

// Exportar funciones
window.getDevPanelTemplate = getDevPanelTemplate;
window.injectDevPanel = injectDevPanel;
