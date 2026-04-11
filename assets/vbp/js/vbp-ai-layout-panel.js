/**
 * Visual Builder Pro - AI Layout Panel Template
 *
 * Componente Alpine.js para el panel de AI Layout Assistant.
 *
 * @package Flavor_Chat_IA
 * @since 2.2.0
 */

/**
 * Genera el HTML del panel de AI Layout
 * Se inyecta en el DOM cuando se abre el panel
 */
function vbpAILayoutPanelHTML() {
    return /* html */`
<div class="vbp-ai-layout-panel"
     x-data="vbpAILayoutPanel()"
     x-show="store.isOpen"
     x-transition:enter="vbp-ai-layout-fade-enter"
     x-transition:enter-start="vbp-ai-layout-fade-enter"
     x-transition:enter-end="vbp-ai-layout-fade-enter-active"
     x-transition:leave="vbp-ai-layout-fade-leave"
     x-transition:leave-start="vbp-ai-layout-fade-leave"
     x-transition:leave-end="vbp-ai-layout-fade-leave-active"
     @keydown.escape="store.close()">

    <!-- Overlay -->
    <div class="vbp-ai-layout-overlay" @click="store.close()"></div>

    <!-- Content -->
    <div class="vbp-ai-layout-content" @click.stop>

        <!-- Header -->
        <div class="vbp-ai-layout-header">
            <div class="vbp-ai-layout-title">
                <span class="vbp-ai-layout-icon">***</span>
                <span>AI Layout Assistant</span>
            </div>
            <button class="vbp-ai-layout-close" @click="store.close()" title="Cerrar (Esc)">x</button>
        </div>

        <!-- Tabs -->
        <div class="vbp-ai-layout-tabs">
            <button class="vbp-ai-layout-tab"
                    :class="{ 'active': store.activeTab === 'generate' }"
                    @click="store.setTab('generate')">
                <span class="vbp-ai-layout-tab-icon">***</span>
                Generar
            </button>
            <button class="vbp-ai-layout-tab"
                    :class="{ 'active': store.activeTab === 'spacing' }"
                    @click="store.setTab('spacing')">
                <span class="vbp-ai-layout-tab-icon">!!!</span>
                Spacing
            </button>
            <button class="vbp-ai-layout-tab"
                    :class="{ 'active': store.activeTab === 'colors' }"
                    @click="store.setTab('colors')">
                <span class="vbp-ai-layout-tab-icon">!!!</span>
                Colores
            </button>
            <button class="vbp-ai-layout-tab"
                    :class="{ 'active': store.activeTab === 'variants' }"
                    @click="store.setTab('variants')">
                <span class="vbp-ai-layout-tab-icon">!!!</span>
                Variantes
            </button>
            <button class="vbp-ai-layout-tab"
                    :class="{ 'active': store.activeTab === 'analyze' }"
                    @click="store.setTab('analyze')">
                <span class="vbp-ai-layout-tab-icon">!!!</span>
                Analizar
            </button>
        </div>

        <!-- Body -->
        <div class="vbp-ai-layout-body">

            <!-- Error -->
            <div class="vbp-ai-layout-error" x-show="store.error">
                <span class="vbp-ai-layout-error-icon">!!</span>
                <span x-text="store.error"></span>
            </div>

            <!-- Tab: Generate -->
            <template x-if="store.activeTab === 'generate'">
                <div class="vbp-ai-layout-section">
                    <!-- Command Input -->
                    <div class="vbp-ai-layout-input-wrapper vbp-ai-layout-magic">
                        <span class="vbp-ai-layout-input-icon">***</span>
                        <input type="text"
                               class="vbp-ai-layout-input"
                               x-model="store.prompt"
                               @keydown="handleKeydown($event)"
                               placeholder="Describe el layout que quieres crear..."
                               autocomplete="off">
                        <span class="vbp-ai-layout-input-hint">Enter</span>

                        <!-- Suggestions -->
                        <div class="vbp-ai-layout-suggestions"
                             x-show="store.prompt.length > 1 && store.getSuggestions().length > 0">
                            <template x-for="suggestion in store.getSuggestions()" :key="suggestion">
                                <div class="vbp-ai-layout-suggestion"
                                     @click="store.applySuggestion(suggestion)">
                                    <span class="vbp-ai-layout-suggestion-icon">-></span>
                                    <span x-text="suggestion"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="vbp-ai-layout-section">
                        <div class="vbp-ai-layout-section-title">Acciones rapidas</div>
                        <div class="vbp-ai-layout-quick-actions">
                            <button class="vbp-ai-layout-quick-btn"
                                    @click="store.generateLayout('Crear hero section')">
                                Hero
                            </button>
                            <button class="vbp-ai-layout-quick-btn"
                                    @click="store.generateLayout('Crear grid de 3 columnas')">
                                Grid 3 col
                            </button>
                            <button class="vbp-ai-layout-quick-btn"
                                    @click="store.generateLayout('Generar features')">
                                Features
                            </button>
                            <button class="vbp-ai-layout-quick-btn"
                                    @click="store.generateLayout('Crear testimonios')">
                                Testimonios
                            </button>
                            <button class="vbp-ai-layout-quick-btn"
                                    @click="store.generateLayout('Crear pricing table')">
                                Pricing
                            </button>
                            <button class="vbp-ai-layout-quick-btn"
                                    @click="store.generateLayout('Crear CTA')">
                                CTA
                            </button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div class="vbp-ai-layout-loading" x-show="store.loading">
                        <div class="vbp-ai-layout-spinner"></div>
                        <span>Generando layout...</span>
                    </div>

                    <!-- Generated Blocks Preview -->
                    <div class="vbp-ai-layout-section" x-show="store.generatedBlocks.length > 0 && !store.loading">
                        <div class="vbp-ai-layout-section-title">Bloques generados</div>
                        <div class="vbp-ai-layout-preview">
                            <template x-for="(block, index) in store.generatedBlocks" :key="index">
                                <div class="vbp-ai-layout-preview-block">
                                    <span class="vbp-ai-layout-preview-block-icon">[]</span>
                                    <span x-text="block.props?.className || block.type"></span>
                                    <span class="vbp-ai-layout-preview-block-type" x-text="block.type"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Templates -->
                    <div class="vbp-ai-layout-section" x-show="store.templates.length > 0 && store.generatedBlocks.length === 0">
                        <div class="vbp-ai-layout-section-title">Templates predefinidos</div>
                        <div class="vbp-ai-layout-templates">
                            <template x-for="template in store.templates.slice(0, 6)" :key="template.id">
                                <div class="vbp-ai-layout-template-card"
                                     @click="store.generateLayout(template.name)">
                                    <div class="vbp-ai-layout-template-name" x-text="template.name"></div>
                                    <div class="vbp-ai-layout-template-category" x-text="template.category"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="vbp-ai-layout-section" x-show="store.commandHistory.length > 0 && store.generatedBlocks.length === 0">
                        <div class="vbp-ai-layout-section-title">Historial reciente</div>
                        <div class="vbp-ai-layout-history">
                            <template x-for="entry in store.commandHistory.slice(0, 5)" :key="entry.id">
                                <button class="vbp-ai-layout-history-item"
                                        @click="store.useFromHistory(entry)">
                                    <span class="vbp-ai-layout-history-icon">-</span>
                                    <span class="vbp-ai-layout-history-prompt" x-text="entry.prompt"></span>
                                    <span class="vbp-ai-layout-history-time" x-text="store.formatHistoryDate(entry.timestamp)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Tab: Spacing -->
            <template x-if="store.activeTab === 'spacing'">
                <div class="vbp-ai-layout-section">
                    <div class="vbp-ai-layout-section-title">Auto-Spacing (Grid de 8px)</div>

                    <!-- Grid Base Selector -->
                    <div style="margin-bottom: 16px;">
                        <select x-model="store.gridBase" class="vbp-ai-layout-scheme-select">
                            <option value="4">Grid 4px</option>
                            <option value="8">Grid 8px (recomendado)</option>
                            <option value="12">Grid 12px</option>
                        </select>
                    </div>

                    <button class="vbp-ai-layout-btn vbp-ai-layout-btn-primary"
                            @click="store.calculateAutoSpacing()"
                            :disabled="store.loading">
                        Calcular spacing para seleccion
                    </button>

                    <!-- Loading -->
                    <div class="vbp-ai-layout-loading" x-show="store.loading">
                        <div class="vbp-ai-layout-spinner"></div>
                        <span>Calculando...</span>
                    </div>

                    <!-- Spacing Suggestions -->
                    <div class="vbp-ai-layout-spacing-list" x-show="store.spacingSuggestions.length > 0">
                        <template x-for="suggestion in store.spacingSuggestions" :key="suggestion.elementId">
                            <div class="vbp-ai-layout-spacing-item">
                                <div class="vbp-ai-layout-spacing-info">
                                    <div class="vbp-ai-layout-spacing-type" x-text="suggestion.type"></div>
                                    <div class="vbp-ai-layout-spacing-values">
                                        <span class="vbp-ai-layout-spacing-value">
                                            <span class="vbp-ai-layout-spacing-label">P:</span>
                                            <span x-text="suggestion.spacing.padding + 'px'"></span>
                                        </span>
                                        <span class="vbp-ai-layout-spacing-value">
                                            <span class="vbp-ai-layout-spacing-label">M:</span>
                                            <span x-text="suggestion.spacing.margin + 'px'"></span>
                                        </span>
                                    </div>
                                </div>
                                <button class="vbp-ai-layout-spacing-apply"
                                        @click="store.applySpacing(suggestion)">
                                    Aplicar
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Apply All Button -->
                    <button class="vbp-ai-layout-btn vbp-ai-layout-btn-secondary"
                            x-show="store.spacingSuggestions.length > 1"
                            @click="store.applyAllSpacing()">
                        Aplicar a todos
                    </button>
                </div>
            </template>

            <!-- Tab: Colors -->
            <template x-if="store.activeTab === 'colors'">
                <div class="vbp-ai-layout-color-section">
                    <!-- Color Picker -->
                    <div class="vbp-ai-layout-section">
                        <div class="vbp-ai-layout-section-title">Color base</div>
                        <div class="vbp-ai-layout-color-picker">
                            <input type="color"
                                   class="vbp-ai-layout-color-input"
                                   x-model="baseColor">
                            <select x-model="store.colorScheme" class="vbp-ai-layout-scheme-select">
                                <option value="complementary">Complementario</option>
                                <option value="analogous">Analogo</option>
                                <option value="triadic">Triadico</option>
                                <option value="split-complementary">Split-complementario</option>
                                <option value="monochromatic">Monocromatico</option>
                            </select>
                            <button class="vbp-ai-layout-btn vbp-ai-layout-btn-primary"
                                    @click="store.suggestColors(baseColor)"
                                    :disabled="store.loading">
                                Generar
                            </button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div class="vbp-ai-layout-loading" x-show="store.loading">
                        <div class="vbp-ai-layout-spinner"></div>
                        <span>Generando paleta...</span>
                    </div>

                    <!-- Color Palette -->
                    <div class="vbp-ai-layout-section" x-show="store.colorPalette.length > 0">
                        <div class="vbp-ai-layout-section-title">Paleta <span x-text="store.getSchemeLabel(store.colorScheme)"></span></div>
                        <div class="vbp-ai-layout-palette">
                            <template x-for="(color, index) in store.colorPalette" :key="index">
                                <div class="vbp-ai-layout-color-swatch"
                                     :style="'background-color: ' + color"
                                     :data-color="color"
                                     @click="store.copyColor(color)"
                                     title="Clic para copiar"></div>
                            </template>
                        </div>
                    </div>

                    <!-- Color Variations -->
                    <div class="vbp-ai-layout-section" x-show="Object.keys(store.colorVariations).length > 0">
                        <div class="vbp-ai-layout-section-title">Variaciones</div>
                        <div class="vbp-ai-layout-variations">
                            <template x-for="(color, name) in store.colorVariations" :key="name">
                                <div class="vbp-ai-layout-variation-row">
                                    <span class="vbp-ai-layout-variation-label" x-text="name"></span>
                                    <div class="vbp-ai-layout-variation-swatch"
                                         :style="'background-color: ' + color"
                                         @click="store.copyColor(color)"
                                         title="Clic para copiar"></div>
                                    <span class="vbp-ai-layout-variation-hex" x-text="color"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Apply Color Buttons -->
                    <div class="vbp-ai-layout-section" x-show="store.colorPalette.length > 0">
                        <div class="vbp-ai-layout-section-title">Aplicar a seleccion</div>
                        <div class="vbp-ai-layout-color-apply-btns">
                            <button class="vbp-ai-layout-color-apply-btn"
                                    @click="store.applyColorToSelection(store.colorPalette[0], 'background')">
                                Como fondo
                            </button>
                            <button class="vbp-ai-layout-color-apply-btn"
                                    @click="store.applyColorToSelection(store.colorPalette[0], 'color')">
                                Como texto
                            </button>
                            <button class="vbp-ai-layout-color-apply-btn"
                                    @click="store.applyColorToSelection(store.colorPalette[0], 'borderColor')">
                                Como borde
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Tab: Variants -->
            <template x-if="store.activeTab === 'variants'">
                <div class="vbp-ai-layout-section">
                    <div class="vbp-ai-layout-section-title">Generar variantes de diseno</div>
                    <p style="color: var(--vbp-text-muted); font-size: 13px; margin-bottom: 16px;">
                        Selecciona un elemento y genera variaciones automaticas de estilo.
                    </p>

                    <button class="vbp-ai-layout-btn vbp-ai-layout-btn-primary"
                            @click="store.generateVariants()"
                            :disabled="store.loading">
                        Generar variantes
                    </button>

                    <!-- Loading -->
                    <div class="vbp-ai-layout-loading" x-show="store.loading">
                        <div class="vbp-ai-layout-spinner"></div>
                        <span>Generando variantes...</span>
                    </div>

                    <!-- Variants Grid -->
                    <div class="vbp-ai-layout-variants-grid" x-show="store.variants.length > 0">
                        <template x-for="(variant, index) in store.variants" :key="index">
                            <div class="vbp-ai-layout-variant-card"
                                 @click="store.applyVariant(index)">
                                <div class="vbp-ai-layout-variant-header">
                                    <span class="vbp-ai-layout-variant-style" x-text="variant.variantStyle || 'Variante ' + (index + 1)"></span>
                                </div>
                                <div class="vbp-ai-layout-variant-preview">
                                    <div class="vbp-ai-layout-variant-preview-placeholder"></div>
                                    <div class="vbp-ai-layout-variant-preview-placeholder"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Tab: Analyze -->
            <template x-if="store.activeTab === 'analyze'">
                <div class="vbp-ai-layout-analysis">
                    <button class="vbp-ai-layout-btn vbp-ai-layout-btn-primary"
                            @click="store.analyzeDesign()"
                            :disabled="store.loading">
                        Analizar diseno
                    </button>

                    <!-- Loading -->
                    <div class="vbp-ai-layout-loading" x-show="store.loading">
                        <div class="vbp-ai-layout-spinner"></div>
                        <span>Analizando...</span>
                    </div>

                    <!-- Analysis Results -->
                    <template x-if="store.analysisResult && !store.loading">
                        <div>
                            <!-- Score -->
                            <div class="vbp-ai-layout-score">
                                <div class="vbp-ai-layout-score-circle"
                                     :style="'background-color: ' + store.getScoreColor(store.analysisResult.score)">
                                    <span x-text="store.analysisResult.score"></span>
                                </div>
                                <div class="vbp-ai-layout-score-info">
                                    <div class="vbp-ai-layout-score-label">Puntuacion de diseno</div>
                                    <div class="vbp-ai-layout-score-desc"
                                         x-text="store.analysisResult.score >= 90 ? 'Excelente' : store.analysisResult.score >= 70 ? 'Bueno' : store.analysisResult.score >= 50 ? 'Mejorable' : 'Necesita atencion'">
                                    </div>
                                </div>
                            </div>

                            <!-- Issues -->
                            <div class="vbp-ai-layout-section" x-show="store.analysisResult.issues.length > 0">
                                <div class="vbp-ai-layout-section-title">Problemas detectados</div>
                                <div class="vbp-ai-layout-issues">
                                    <template x-for="issue in store.analysisResult.issues" :key="issue.elementId + issue.type">
                                        <div class="vbp-ai-layout-issue"
                                             :class="'severity-' + issue.severity">
                                            <span class="vbp-ai-layout-issue-icon" x-text="store.getSeverityIcon(issue.severity)"></span>
                                            <div class="vbp-ai-layout-issue-content">
                                                <div class="vbp-ai-layout-issue-message" x-text="issue.message"></div>
                                                <button class="vbp-ai-layout-issue-fix"
                                                        x-show="issue.fix"
                                                        @click="store.applyFix(issue)">
                                                    Aplicar fix
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Suggestions -->
                            <div class="vbp-ai-layout-section" x-show="store.analysisResult.suggestions.length > 0">
                                <div class="vbp-ai-layout-section-title">Sugerencias de mejora</div>
                                <div class="vbp-ai-layout-suggestions-list">
                                    <template x-for="suggestion in store.analysisResult.suggestions" :key="suggestion.elementId + suggestion.action">
                                        <div class="vbp-ai-layout-suggestion-item">
                                            <span class="vbp-ai-layout-suggestion-icon">i</span>
                                            <span class="vbp-ai-layout-suggestion-message" x-text="suggestion.message"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- No Issues -->
                            <div class="vbp-ai-layout-empty"
                                 x-show="store.analysisResult.issues.length === 0 && store.analysisResult.suggestions.length === 0">
                                <div class="vbp-ai-layout-empty-icon">OK</div>
                                <div class="vbp-ai-layout-empty-text">Sin problemas detectados</div>
                                <div class="vbp-ai-layout-empty-hint">Tu diseno se ve bien</div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="vbp-ai-layout-footer">
            <div class="vbp-ai-layout-footer-info">
                <div class="vbp-ai-layout-footer-status">
                    <span class="vbp-ai-layout-status-dot" :class="store.aiAvailable ? 'active' : 'inactive'"></span>
                    <span x-text="store.aiAvailable ? 'IA activa' : 'Modo fallback'"></span>
                </div>
            </div>
            <div class="vbp-ai-layout-footer-actions">
                <button class="vbp-ai-layout-btn vbp-ai-layout-btn-secondary" @click="store.close()">
                    Cancelar
                </button>
                <button class="vbp-ai-layout-btn vbp-ai-layout-btn-primary"
                        x-show="store.activeTab === 'generate' && store.generatedBlocks.length > 0"
                        @click="store.applyGeneratedBlocks()">
                    Aplicar bloques
                </button>
            </div>
        </div>
    </div>
</div>
    `;
}

/**
 * Inyecta el panel de AI Layout en el DOM
 */
function injectAILayoutPanel() {
    // Verificar si ya existe
    if (document.querySelector('.vbp-ai-layout-panel')) {
        return;
    }

    // Crear contenedor
    var panelContainer = document.createElement('div');
    panelContainer.id = 'vbp-ai-layout-panel-container';
    panelContainer.innerHTML = vbpAILayoutPanelHTML();

    // Inyectar al final del body
    document.body.appendChild(panelContainer);
}

// Exportar funciones
window.vbpAILayoutPanelHTML = vbpAILayoutPanelHTML;
window.injectAILayoutPanel = injectAILayoutPanel;

// Inyectar panel cuando el DOM este listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectAILayoutPanel);
} else {
    injectAILayoutPanel();
}
