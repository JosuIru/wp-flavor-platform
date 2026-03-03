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

    <div class="vbp-ai-panel-content">
        <!-- Header -->
        <div class="vbp-ai-panel-header">
            <div class="vbp-ai-panel-title">
                <span class="vbp-ai-icon">✨</span>
                <?php esc_html_e( 'Asistente de IA', 'flavor-chat-ia' ); ?>
            </div>
            <button type="button" @click="$store.vbpAI.close()" class="vbp-ai-panel-close" title="<?php esc_attr_e( 'Cerrar', 'flavor-chat-ia' ); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="vbp-ai-panel-body">
            <!-- Contexto -->
            <div class="vbp-ai-context">
                <div class="vbp-ai-field">
                    <label><?php esc_html_e( 'Industria/Sector', 'flavor-chat-ia' ); ?></label>
                    <select x-model="$store.vbpAI.industry">
                        <option value=""><?php esc_html_e( 'General', 'flavor-chat-ia' ); ?></option>
                        <template x-for="ind in $store.vbpAI.industries" :key="ind.id">
                            <option :value="ind.id" x-text="ind.name"></option>
                        </template>
                    </select>
                </div>

                <div class="vbp-ai-field">
                    <label><?php esc_html_e( 'Tono', 'flavor-chat-ia' ); ?></label>
                    <select x-model="$store.vbpAI.tone">
                        <option value="professional"><?php esc_html_e( 'Profesional', 'flavor-chat-ia' ); ?></option>
                        <option value="casual"><?php esc_html_e( 'Casual', 'flavor-chat-ia' ); ?></option>
                        <option value="formal"><?php esc_html_e( 'Formal', 'flavor-chat-ia' ); ?></option>
                        <option value="friendly"><?php esc_html_e( 'Amigable', 'flavor-chat-ia' ); ?></option>
                        <template x-for="t in $store.vbpAI.tones" :key="t.id">
                            <option :value="t.id" x-text="t.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Contenido actual -->
            <template x-if="$store.vbpAI.currentContent">
                <div class="vbp-ai-current">
                    <label><?php esc_html_e( 'Contenido actual', 'flavor-chat-ia' ); ?></label>
                    <div class="vbp-ai-current-content" x-text="$store.vbpAI.currentContent"></div>
                </div>
            </template>

            <!-- Botones de acción -->
            <div class="vbp-ai-actions-grid">
                <button type="button" @click="$store.vbpAI.generate()" class="vbp-ai-action-btn vbp-ai-action-primary" :disabled="$store.vbpAI.isLoading">
                    <span class="vbp-ai-action-icon">✨</span>
                    <?php esc_html_e( 'Generar nuevo', 'flavor-chat-ia' ); ?>
                </button>

                <template x-if="$store.vbpAI.currentContent || $store.vbpAI.generatedContent">
                    <div class="vbp-ai-improve-actions">
                        <button type="button" @click="$store.vbpAI.improve('rewrite')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                            <span class="vbp-ai-action-icon">🔄</span>
                            <?php esc_html_e( 'Reescribir', 'flavor-chat-ia' ); ?>
                        </button>
                        <button type="button" @click="$store.vbpAI.improve('shorten')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                            <span class="vbp-ai-action-icon">📝</span>
                            <?php esc_html_e( 'Acortar', 'flavor-chat-ia' ); ?>
                        </button>
                        <button type="button" @click="$store.vbpAI.improve('expand')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                            <span class="vbp-ai-action-icon">📖</span>
                            <?php esc_html_e( 'Expandir', 'flavor-chat-ia' ); ?>
                        </button>
                        <button type="button" @click="$store.vbpAI.improve('persuasive')" class="vbp-ai-action-btn" :disabled="$store.vbpAI.isLoading">
                            <span class="vbp-ai-action-icon">🎯</span>
                            <?php esc_html_e( 'Persuasivo', 'flavor-chat-ia' ); ?>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Loading -->
            <template x-if="$store.vbpAI.isLoading">
                <div class="vbp-ai-loading">
                    <span class="vbp-loading-spinner"></span>
                    <?php esc_html_e( 'Generando contenido...', 'flavor-chat-ia' ); ?>
                </div>
            </template>

            <!-- Error -->
            <template x-if="$store.vbpAI.error">
                <div class="vbp-ai-error">
                    <span class="vbp-ai-error-icon">⚠️</span>
                    <span x-text="$store.vbpAI.error"></span>
                </div>
            </template>

            <!-- Resultado -->
            <template x-if="$store.vbpAI.generatedContent && !$store.vbpAI.isLoading">
                <div class="vbp-ai-result">
                    <label><?php esc_html_e( 'Contenido generado', 'flavor-chat-ia' ); ?></label>
                    <textarea
                        x-model="$store.vbpAI.generatedContent"
                        class="vbp-ai-result-textarea"
                        rows="5"
                    ></textarea>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="vbp-ai-panel-footer">
            <button type="button" @click="$store.vbpAI.close()" class="vbp-btn vbp-btn-secondary">
                <?php esc_html_e( 'Cancelar', 'flavor-chat-ia' ); ?>
            </button>
            <button
                type="button"
                @click="$store.vbpAI.apply()"
                class="vbp-btn vbp-btn-primary"
                :disabled="!$store.vbpAI.generatedContent || $store.vbpAI.isLoading"
            >
                <?php esc_html_e( 'Aplicar contenido', 'flavor-chat-ia' ); ?>
            </button>
        </div>
    </div>
</div>
