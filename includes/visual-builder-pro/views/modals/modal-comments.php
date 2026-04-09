<?php
/**
 * Visual Builder Pro - Panel de Comentarios Colaborativos
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div
    class="vbp-comments-panel"
    x-data="vbpCommentsPanel()"
    x-show="$store.vbpComments.isOpen"
    x-transition:enter="vbp-panel-enter"
    x-transition:leave="vbp-panel-leave"
    @keydown.escape.window="$store.vbpComments.close()"
>
    <div class="vbp-comments-panel-overlay" @click="$store.vbpComments.close()"></div>

    <div class="vbp-comments-panel-content">
        <!-- Header -->
        <div class="vbp-comments-panel-header">
            <div class="vbp-comments-panel-title">
                <span class="vbp-comments-icon">💬</span>
                <?php esc_html_e( 'Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                <span class="vbp-comments-count" x-show="$store.vbpComments.stats.pending > 0" x-text="$store.vbpComments.stats.pending"></span>
            </div>
            <div class="vbp-comments-panel-actions">
                <button
                    type="button"
                    @click="$store.vbpComments.startAddingMode()"
                    class="vbp-btn vbp-btn-primary vbp-btn-sm"
                    :disabled="$store.vbpComments.isAddingMode"
                    title="<?php esc_attr_e( 'Añadir comentario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                >
                    <span>+</span>
                    <?php esc_html_e( 'Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button
                    type="button"
                    @click="$store.vbpComments.showResolved = !$store.vbpComments.showResolved"
                    class="vbp-btn vbp-btn-secondary vbp-btn-sm"
                    :class="{ 'active': $store.vbpComments.showResolved }"
                    title="<?php esc_attr_e( 'Mostrar resueltos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                >
                    <span x-text="$store.vbpComments.showResolved ? '👁' : '👁‍🗨'"></span>
                </button>
            </div>
            <button type="button" @click="$store.vbpComments.close()" class="vbp-comments-panel-close" title="<?php esc_attr_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Stats bar -->
        <div class="vbp-comments-stats" x-show="$store.vbpComments.stats.total > 0">
            <div class="vbp-comments-stat">
                <span class="vbp-comments-stat-value" x-text="$store.vbpComments.stats.total"></span>
                <span class="vbp-comments-stat-label"><?php esc_html_e( 'Total', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            </div>
            <div class="vbp-comments-stat vbp-comments-stat-pending">
                <span class="vbp-comments-stat-value" x-text="$store.vbpComments.stats.pending"></span>
                <span class="vbp-comments-stat-label"><?php esc_html_e( 'Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            </div>
            <div class="vbp-comments-stat vbp-comments-stat-resolved">
                <span class="vbp-comments-stat-value" x-text="$store.vbpComments.stats.resolved"></span>
                <span class="vbp-comments-stat-label"><?php esc_html_e( 'Resueltos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            </div>
        </div>

        <!-- Body -->
        <div class="vbp-comments-panel-body">
            <!-- Modo añadir comentario -->
            <template x-if="$store.vbpComments.isAddingMode">
                <div class="vbp-comments-adding-mode">
                    <div class="vbp-comments-adding-info">
                        <span class="vbp-comments-adding-icon">🎯</span>
                        <?php esc_html_e( 'Haz clic en un elemento del canvas para añadir un comentario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </div>
                    <button type="button" @click="$store.vbpComments.cancelAddingMode()" class="vbp-btn vbp-btn-secondary vbp-btn-sm">
                        <?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </template>

            <!-- Formulario de nuevo comentario -->
            <template x-if="$store.vbpComments.activeElementId && !$store.vbpComments.isAddingMode">
                <div class="vbp-comments-new-form">
                    <div class="vbp-comments-new-header">
                        <template x-if="$store.vbpComments.replyingTo">
                            <span class="vbp-comments-replying">
                                <?php esc_html_e( 'Respondiendo a comentario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <button type="button" @click="$store.vbpComments.cancelReply()" class="vbp-comments-cancel-reply">✕</button>
                            </span>
                        </template>
                        <template x-if="!$store.vbpComments.replyingTo">
                            <span class="vbp-comments-new-title"><?php esc_html_e( 'Nuevo comentario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </template>
                    </div>
                    <textarea
                        x-model="$store.vbpComments.newCommentContent"
                        rows="3"
                        placeholder="<?php esc_attr_e( 'Escribe tu comentario...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        class="vbp-comments-textarea"
                        @keydown.ctrl.enter="$store.vbpComments.addComment()"
                        @keydown.meta.enter="$store.vbpComments.addComment()"
                    ></textarea>
                    <div class="vbp-comments-new-actions">
                        <span class="vbp-comments-hint"><?php esc_html_e( 'Ctrl+Enter para enviar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        <button
                            type="button"
                            @click="$store.vbpComments.addComment()"
                            class="vbp-btn vbp-btn-primary vbp-btn-sm"
                            :disabled="!$store.vbpComments.newCommentContent.trim() || $store.vbpComments.isLoading"
                        >
                            <?php esc_html_e( 'Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Loading -->
            <template x-if="$store.vbpComments.isLoading && $store.vbpComments.comments.length === 0">
                <div class="vbp-comments-loading">
                    <span class="vbp-loading-spinner"></span>
                    <?php esc_html_e( 'Cargando comentarios...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </div>
            </template>

            <!-- Error -->
            <template x-if="$store.vbpComments.error">
                <div class="vbp-comments-error">
                    <span class="vbp-comments-error-icon">⚠️</span>
                    <span x-text="$store.vbpComments.error"></span>
                </div>
            </template>

            <!-- Sin comentarios -->
            <template x-if="!$store.vbpComments.isLoading && $store.vbpComments.comments.length === 0">
                <div class="vbp-comments-empty">
                    <span class="vbp-comments-empty-icon">💬</span>
                    <p><?php esc_html_e( 'No hay comentarios aún', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                    <p class="vbp-comments-empty-hint"><?php esc_html_e( 'Haz clic en "Añadir" para crear el primer comentario', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                </div>
            </template>

            <!-- Lista de hilos de comentarios -->
            <div class="vbp-comments-threads" x-show="$store.vbpComments.comments.length > 0">
                <template x-for="thread in $store.vbpComments.getThreads()" :key="thread.id">
                    <div class="vbp-comments-thread" :class="{ 'resolved': thread.resolved }">
                        <!-- Comentario principal -->
                        <div class="vbp-comment-item">
                            <div class="vbp-comment-avatar" :style="'background-image: url(' + thread.author.avatar + ')'">
                                <template x-if="!thread.author.avatar">
                                    <span x-text="thread.author.initials"></span>
                                </template>
                            </div>
                            <div class="vbp-comment-content">
                                <div class="vbp-comment-header">
                                    <span class="vbp-comment-author" x-text="thread.author.name"></span>
                                    <span class="vbp-comment-time" x-text="thread.created_ago"></span>
                                    <template x-if="thread.resolved">
                                        <span class="vbp-comment-badge vbp-comment-badge-resolved"><?php esc_html_e( 'Resuelto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                    </template>
                                </div>
                                <div class="vbp-comment-text" x-text="thread.content"></div>
                                <div class="vbp-comment-actions">
                                    <button type="button" @click="$store.vbpComments.goToElement(thread.element_id)" class="vbp-comment-action" title="<?php esc_attr_e( 'Ir al elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        🎯
                                    </button>
                                    <button type="button" @click="$store.vbpComments.startReply(thread.id)" class="vbp-comment-action" title="<?php esc_attr_e( 'Responder', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                        ↩
                                    </button>
                                    <button type="button" @click="$store.vbpComments.toggleResolved(thread.id)" class="vbp-comment-action" :title="thread.resolved ? '<?php esc_attr_e( 'Reabrir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Resolver', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'">
                                        <span x-text="thread.resolved ? '🔄' : '✓'"></span>
                                    </button>
                                    <template x-if="$store.vbpComments.canEdit(thread)">
                                        <button type="button" @click="$store.vbpComments.deleteComment(thread.id)" class="vbp-comment-action vbp-comment-action-danger" title="<?php esc_attr_e( 'Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                            🗑
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Respuestas -->
                        <template x-if="$store.vbpComments.getReplies(thread.id).length > 0">
                            <div class="vbp-comment-replies">
                                <template x-for="reply in $store.vbpComments.getReplies(thread.id)" :key="reply.id">
                                    <div class="vbp-comment-item vbp-comment-reply">
                                        <div class="vbp-comment-avatar vbp-comment-avatar-sm" :style="'background-image: url(' + reply.author.avatar + ')'">
                                            <template x-if="!reply.author.avatar">
                                                <span x-text="reply.author.initials"></span>
                                            </template>
                                        </div>
                                        <div class="vbp-comment-content">
                                            <div class="vbp-comment-header">
                                                <span class="vbp-comment-author" x-text="reply.author.name"></span>
                                                <span class="vbp-comment-time" x-text="reply.created_ago"></span>
                                            </div>
                                            <div class="vbp-comment-text" x-text="reply.content"></div>
                                            <div class="vbp-comment-actions">
                                                <template x-if="$store.vbpComments.canEdit(reply)">
                                                    <button type="button" @click="$store.vbpComments.deleteComment(reply.id)" class="vbp-comment-action vbp-comment-action-danger" title="<?php esc_attr_e( 'Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                                                        🗑
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
