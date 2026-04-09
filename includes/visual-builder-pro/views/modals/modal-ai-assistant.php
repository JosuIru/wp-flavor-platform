<?php
/**
 * Visual Builder Pro - Modal AI Assistant
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div
    class="vbp-ai-panel"
    x-data="vbpAIPanel()"
    x-show="$store.vbpAI.isOpen"
    x-transition:enter="vbp-panel-enter"
    x-transition:leave="vbp-panel-leave"
    @keydown.escape.window="$store.vbpAI.close()"
>
    <div class="vbp-ai-panel-overlay" @click="$store.vbpAI.close()"></div>

    <div class="vbp-ai-panel-content" :class="{ 'vbp-ai-panel-wide': $store.vbpAI.mode === 'page' }">
        <!-- Header -->
        <div class="vbp-ai-panel-header">
            <div class="vbp-ai-panel-title">
                <span class="vbp-ai-icon">✨</span>
                <template x-if="$store.vbpAI.mode === 'element'">
                    <span><?php esc_html_e( 'Asistente de IA', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                </template>
                <template x-if="$store.vbpAI.mode === 'page'">
                    <span><?php esc_html_e( 'Generar Página Completa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                </template>
            </div>
            <div class="vbp-ai-panel-mode-tabs">
                <button
                    type="button"
                    @click="$store.vbpAI.mode = 'element'"
                    class="vbp-ai-mode-tab"
                    :class="{ 'active': $store.vbpAI.mode === 'element' }"
                >
                    <?php esc_html_e( 'Elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button
                    type="button"
                    @click="$store.vbpAI.openPageMode()"
                    class="vbp-ai-mode-tab"
                    :class="{ 'active': $store.vbpAI.mode === 'page' }"
                >
                    <?php esc_html_e( 'Página Completa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
            <button type="button" @click="$store.vbpAI.close()" class="vbp-ai-panel-close" title="<?php esc_attr_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="vbp-ai-panel-body">
            <!-- ================== MODO ELEMENTO ================== -->
            <template x-if="$store.vbpAI.mode === 'element'">
                <div class="vbp-ai-element-mode">
                    <!-- Contexto -->
                    <div class="vbp-ai-context">
                        <div class="vbp-ai-field">
                            <label><?php esc_html_e( 'Industria/Sector', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="$store.vbpAI.industry">
                                <option value=""><?php esc_html_e( 'General', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <template x-for="ind in $store.vbpAI.industries" :key="ind.id">
                                    <option :value="ind.id" x-text="ind.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="vbp-ai-field">
                            <label><?php esc_html_e( 'Tono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <select x-model="$store.vbpAI.tone">
                                <option value="profesional"><?php esc_html_e( 'Profesional', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                <template x-for="t in $store.vbpAI.tones" :key="t.id">
                                    <option :value="t.id" x-text="t.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Contenido actual -->
                    <template x-if="$store.vbpAI.currentContent">
                        <div class="vbp-ai-current">
                            <label><?php esc_html_e( 'Contenido actual', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-ai-current-content" x-text="$store.vbpAI.currentContent"></div>
                        </div>
                    </template>

                    <!-- Botones de acción -->
                    <div class="vbp-ai-actions-grid">
                        <button type="button" @click="$store.vbpAI.generate()" class="vbp-ai-action-btn vbp-ai-action-primary" :disabled="$store.vbpAI.isLoading">
                            <span class="vbp-ai-action-icon">✨</span>
                            <?php esc_html_e( 'Generar nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>

                        <template x-if="$store.vbpAI.currentContent || $store.vbpAI.generatedContent">
                            <div class="vbp-ai-improve-actions">
                                <button type="button" @click="$store.vbpAI.improve('rewrite')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                                    <span class="vbp-ai-action-icon">🔄</span>
                                    <?php esc_html_e( 'Reescribir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button" @click="$store.vbpAI.improve('shorten')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                                    <span class="vbp-ai-action-icon">📝</span>
                                    <?php esc_html_e( 'Acortar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button" @click="$store.vbpAI.improve('expand')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                                    <span class="vbp-ai-action-icon">📖</span>
                                    <?php esc_html_e( 'Expandir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button" @click="$store.vbpAI.improve('persuasive')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                                    <span class="vbp-ai-action-icon">🎯</span>
                                    <?php esc_html_e( 'Persuasivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Resultado elemento -->
                    <template x-if="$store.vbpAI.generatedContent && !$store.vbpAI.isLoading">
                        <div class="vbp-ai-result">
                            <label><?php esc_html_e( 'Contenido generado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <textarea
                                x-model="$store.vbpAI.generatedContent"
                                class="vbp-ai-result-textarea"
                                rows="5"
                            ></textarea>
                        </div>
                    </template>
                </div>
            </template>

            <!-- ================== MODO PÁGINA COMPLETA ================== -->
            <template x-if="$store.vbpAI.mode === 'page'">
                <div class="vbp-ai-page-mode">
                    <div class="vbp-ai-page-grid">
                        <!-- Columna izquierda: Configuración -->
                        <div class="vbp-ai-page-config">
                            <!-- Tipo de página -->
                            <div class="vbp-ai-field">
                                <label><?php esc_html_e( 'Tipo de Página', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <select x-model="$store.vbpAI.selectedPageType" @change="$store.vbpAI.selectPageType($event.target.value)">
                                    <option value=""><?php esc_html_e( '-- Seleccionar --', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                    <template x-for="pt in $store.vbpAI.pageTypes" :key="pt.id">
                                        <option :value="pt.id" x-text="pt.name"></option>
                                    </template>
                                </select>
                                <template x-if="$store.vbpAI.selectedPageType">
                                    <p class="vbp-ai-field-help" x-text="$store.vbpAI.pageTypes.find(p => p.id === $store.vbpAI.selectedPageType)?.description"></p>
                                </template>
                            </div>

                            <!-- Contexto de la empresa -->
                            <div class="vbp-ai-field">
                                <label><?php esc_html_e( 'Nombre de la empresa/proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input type="text" x-model="$store.vbpAI.companyName" placeholder="<?php esc_attr_e( 'Ej: Mi Cooperativa Verde', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            </div>

                            <div class="vbp-ai-context-row">
                                <div class="vbp-ai-field">
                                    <label><?php esc_html_e( 'Industria/Sector', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select x-model="$store.vbpAI.industry">
                                        <option value="general"><?php esc_html_e( 'General', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                                        <template x-for="ind in $store.vbpAI.industries" :key="ind.id">
                                            <option :value="ind.id" x-text="ind.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="vbp-ai-field">
                                    <label><?php esc_html_e( 'Tono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                    <select x-model="$store.vbpAI.tone">
                                        <template x-for="t in $store.vbpAI.tones" :key="t.id">
                                            <option :value="t.id" x-text="t.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="vbp-ai-field">
                                <label><?php esc_html_e( 'Descripción breve', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <textarea
                                    x-model="$store.vbpAI.description"
                                    rows="2"
                                    placeholder="<?php esc_attr_e( 'Describe brevemente tu negocio o proyecto...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                                ></textarea>
                            </div>

                            <div class="vbp-ai-field">
                                <label><?php esc_html_e( 'Público objetivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                                <input
                                    type="text"
                                    x-model="$store.vbpAI.targetAudience"
                                    placeholder="<?php esc_attr_e( 'Ej: Familias, profesionales, jóvenes...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                                >
                            </div>
                        </div>

                        <!-- Columna derecha: Secciones -->
                        <div class="vbp-ai-page-sections">
                            <label><?php esc_html_e( 'Secciones a incluir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-ai-sections-grid">
                                <template x-for="section in $store.vbpAI.sectionTypes" :key="section.id">
                                    <label class="vbp-ai-section-checkbox" :class="{ 'selected': $store.vbpAI.isSectionSelected(section.id) }">
                                        <input
                                            type="checkbox"
                                            :checked="$store.vbpAI.isSectionSelected(section.id)"
                                            @change="$store.vbpAI.toggleSection(section.id)"
                                        >
                                        <span class="vbp-ai-section-name" x-text="section.name"></span>
                                    </label>
                                </template>
                            </div>
                            <p class="vbp-ai-sections-count">
                                <span x-text="$store.vbpAI.selectedSections.length"></span> <?php esc_html_e( 'secciones seleccionadas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Preview de página generada -->
                    <template x-if="$store.vbpAI.generatedPage && !$store.vbpAI.isLoading">
                        <div class="vbp-ai-page-preview">
                            <label><?php esc_html_e( 'Vista previa de la estructura', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="vbp-ai-page-preview-content">
                                <template x-if="$store.vbpAI.generatedPage.title">
                                    <div class="vbp-ai-preview-title">
                                        <strong><?php esc_html_e( 'Título:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong>
                                        <span x-text="$store.vbpAI.generatedPage.title"></span>
                                    </div>
                                </template>
                                <template x-if="$store.vbpAI.generatedPage.meta_description">
                                    <div class="vbp-ai-preview-meta">
                                        <strong><?php esc_html_e( 'Meta descripción:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong>
                                        <span x-text="$store.vbpAI.generatedPage.meta_description"></span>
                                    </div>
                                </template>
                                <template x-if="$store.vbpAI.generatedPage.blocks">
                                    <div class="vbp-ai-preview-blocks">
                                        <strong><?php esc_html_e( 'Bloques:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong>
                                        <span x-text="$store.vbpAI.generatedPage.blocks.length + ' secciones generadas'"></span>
                                    </div>
                                </template>
                            </div>
                            <details class="vbp-ai-json-details">
                                <summary><?php esc_html_e( 'Ver JSON completo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></summary>
                                <pre class="vbp-ai-json-preview" x-text="$store.vbpAI.generatedContent"></pre>
                            </details>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Loading (común) -->
            <template x-if="$store.vbpAI.isLoading">
                <div class="vbp-ai-loading">
                    <span class="vbp-loading-spinner"></span>
                    <template x-if="$store.vbpAI.mode === 'element'">
                        <span><?php esc_html_e( 'Generando contenido...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    </template>
                    <template x-if="$store.vbpAI.mode === 'page'">
                        <span><?php esc_html_e( 'Generando página completa...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                    </template>
                </div>
            </template>

            <!-- Error (común) -->
            <template x-if="$store.vbpAI.error">
                <div class="vbp-ai-error">
                    <span class="vbp-ai-error-icon">⚠️</span>
                    <span x-text="$store.vbpAI.error"></span>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="vbp-ai-panel-footer">
            <button type="button" @click="$store.vbpAI.close()" class="vbp-btn vbp-btn-secondary">
                <?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>

            <!-- Botones modo elemento -->
            <template x-if="$store.vbpAI.mode === 'element'">
                <button
                    type="button"
                    @click="$store.vbpAI.apply()"
                    class="vbp-btn vbp-btn-primary"
                    :disabled="!$store.vbpAI.generatedContent || $store.vbpAI.isLoading"
                >
                    <?php esc_html_e( 'Aplicar contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </template>

            <!-- Botones modo página -->
            <template x-if="$store.vbpAI.mode === 'page'">
                <div class="vbp-ai-page-buttons">
                    <template x-if="!$store.vbpAI.generatedPage">
                        <button
                            type="button"
                            @click="$store.vbpAI.generatePage()"
                            class="vbp-btn vbp-btn-primary"
                            :disabled="!$store.vbpAI.selectedPageType || $store.vbpAI.selectedSections.length === 0 || $store.vbpAI.isLoading"
                        >
                            <span class="vbp-ai-action-icon">✨</span>
                            <?php esc_html_e( 'Generar Página', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </template>
                    <template x-if="$store.vbpAI.generatedPage">
                        <div class="vbp-ai-page-apply-buttons">
                            <button
                                type="button"
                                @click="$store.vbpAI.generatePage()"
                                class="vbp-btn vbp-btn-secondary"
                                :disabled="$store.vbpAI.isLoading"
                            >
                                <span class="vbp-ai-action-icon">🔄</span>
                                <?php esc_html_e( 'Regenerar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                            <button
                                type="button"
                                @click="$store.vbpAI.applyPage()"
                                class="vbp-btn vbp-btn-primary"
                                :disabled="$store.vbpAI.isLoading"
                            >
                                <span class="vbp-ai-action-icon">✓</span>
                                <?php esc_html_e( 'Aplicar al Canvas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
