/**
 * Translation Comments System
 * @package FlavorMultilingual
 * @version 1.4.0
 */

(function($) {
    'use strict';

    var TranslationComments = {

        config: {},

        /**
         * Initialize
         */
        init: function() {
            this.config = window.flavorMLComments || {};
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Toggle resolved comments
            $(document).on('click', '.toggle-resolved', function(e) {
                e.preventDefault();
                var $panel = $(this).closest('.flavor-ml-comments-panel');
                $panel.toggleClass('show-resolved');

                if ($panel.hasClass('show-resolved')) {
                    $(this).text(self.config.strings.hide_resolved || 'Ocultar resueltos');
                    $panel.find('.comment-thread.resolved').slideDown(200);
                } else {
                    $(this).text(self.config.strings.show_resolved || 'Ver resueltos');
                    $panel.find('.comment-thread.resolved').slideUp(200);
                }
            });

            // Comment type selector
            $(document).on('click', '.type-btn', function() {
                var $form = $(this).closest('.comment-form');
                $form.find('.type-btn').removeClass('active');
                $(this).addClass('active');
                $form.find('input[name="comment_type"]').val($(this).data('type'));
            });

            // Submit new comment
            $(document).on('click', '#submit-comment', function(e) {
                e.preventDefault();
                var $form = $(this).closest('.comment-form');
                self.submitComment($form);
            });

            // Reply button
            $(document).on('click', '.reply-btn', function(e) {
                e.preventDefault();
                var $thread = $(this).closest('.comment-thread');
                var $item = $(this).closest('.comment-item');

                // Remove any existing reply forms
                $('.reply-form').remove();

                // Create reply form
                var $replyForm = $('<div class="reply-form">' +
                    '<textarea rows="2" placeholder="' + (self.config.strings.write_reply || 'Escribe tu respuesta...') + '"></textarea>' +
                    '<div class="reply-actions">' +
                        '<button type="button" class="button button-primary submit-reply">' +
                            (self.config.strings.reply || 'Responder') +
                        '</button>' +
                        '<button type="button" class="button cancel-reply">' +
                            (self.config.strings.cancel || 'Cancelar') +
                        '</button>' +
                    '</div>' +
                '</div>');

                $replyForm.data('parent-id', $item.data('comment-id'));
                $replyForm.data('thread-id', $thread.data('thread-id'));

                $item.after($replyForm);
                $replyForm.find('textarea').focus();
            });

            // Submit reply
            $(document).on('click', '.submit-reply', function(e) {
                e.preventDefault();
                var $form = $(this).closest('.reply-form');
                self.submitReply($form);
            });

            // Cancel reply
            $(document).on('click', '.cancel-reply', function(e) {
                e.preventDefault();
                $(this).closest('.reply-form').fadeOut(150, function() {
                    $(this).remove();
                });
            });

            // Resolve thread
            $(document).on('click', '.resolve-btn', function(e) {
                e.preventDefault();
                var $thread = $(this).closest('.comment-thread');
                self.resolveThread($thread);
            });

            // Delete comment
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();

                if (!confirm(self.config.strings.confirm_delete || '¿Eliminar este comentario?')) {
                    return;
                }

                var $item = $(this).closest('.comment-item');
                self.deleteComment($item);
            });

            // Floating toggle button
            $(document).on('click', '.flavor-ml-comments-toggle', function(e) {
                e.preventDefault();
                var $panel = $('.flavor-ml-comments-panel');

                if ($panel.is(':visible')) {
                    $panel.slideUp(200);
                    $(this).removeClass('active');
                } else {
                    $panel.slideDown(200);
                    $(this).addClass('active');
                }
            });

            // Keyboard shortcut: Ctrl+M to toggle comments
            $(document).on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'm') {
                    e.preventDefault();
                    $('.flavor-ml-comments-toggle').trigger('click');
                }
            });
        },

        /**
         * Submit new comment
         */
        submitComment: function($form) {
            var self = this;
            var $textarea = $form.find('textarea');
            var $button = $form.find('#submit-comment');
            var commentText = $textarea.val().trim();
            var commentType = $form.find('input[name="comment_type"]').val() || 'note';

            if (!commentText) {
                $textarea.focus();
                return;
            }

            $button.prop('disabled', true).text(self.config.strings.sending || 'Enviando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_add_comment',
                    nonce: this.config.nonce,
                    post_id: this.config.postId,
                    language_code: this.config.langCode,
                    field_name: this.config.fieldName || '',
                    comment_text: commentText,
                    comment_type: commentType
                },
                success: function(response) {
                    $button.prop('disabled', false).text(self.config.strings.add_comment || 'Añadir comentario');

                    if (response.success) {
                        $textarea.val('');
                        self.prependComment(response.data.comment);
                        self.updateCommentCount(1);
                    } else {
                        self.showError(response.data.message || self.config.strings.error);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(self.config.strings.add_comment || 'Añadir comentario');
                    self.showError(self.config.strings.error || 'Error de conexión');
                }
            });
        },

        /**
         * Submit reply
         */
        submitReply: function($form) {
            var self = this;
            var $textarea = $form.find('textarea');
            var $button = $form.find('.submit-reply');
            var replyText = $textarea.val().trim();
            var parentId = $form.data('parent-id');
            var threadId = $form.data('thread-id');

            if (!replyText) {
                $textarea.focus();
                return;
            }

            $button.prop('disabled', true).text(self.config.strings.sending || 'Enviando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_add_comment',
                    nonce: this.config.nonce,
                    post_id: this.config.postId,
                    language_code: this.config.langCode,
                    field_name: this.config.fieldName || '',
                    comment_text: replyText,
                    comment_type: 'note',
                    parent_id: parentId
                },
                success: function(response) {
                    if (response.success) {
                        $form.fadeOut(150, function() {
                            $(this).remove();
                        });

                        // Append reply to thread
                        var $thread = $('[data-thread-id="' + threadId + '"]');
                        var $replies = $thread.find('.comment-replies');

                        if (!$replies.length) {
                            $replies = $('<div class="comment-replies"></div>');
                            $thread.find('.comment-item:first').after($replies);
                        }

                        $replies.append(self.buildReplyHtml(response.data.comment));
                    } else {
                        $button.prop('disabled', false).text(self.config.strings.reply || 'Responder');
                        self.showError(response.data.message || self.config.strings.error);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(self.config.strings.reply || 'Responder');
                    self.showError(self.config.strings.error || 'Error de conexión');
                }
            });
        },

        /**
         * Resolve thread
         */
        resolveThread: function($thread) {
            var self = this;
            var threadId = $thread.data('thread-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_resolve_comment',
                    nonce: this.config.nonce,
                    comment_id: threadId
                },
                success: function(response) {
                    if (response.success) {
                        $thread.addClass('resolved');

                        // Check if should hide
                        var $panel = $thread.closest('.flavor-ml-comments-panel');
                        if (!$panel.hasClass('show-resolved')) {
                            $thread.slideUp(200);
                        }

                        self.updateCommentCount(-1);
                    } else {
                        self.showError(response.data.message || self.config.strings.error);
                    }
                },
                error: function() {
                    self.showError(self.config.strings.error || 'Error de conexión');
                }
            });
        },

        /**
         * Delete comment
         */
        deleteComment: function($item) {
            var self = this;
            var commentId = $item.data('comment-id');
            var $thread = $item.closest('.comment-thread');
            var isMainComment = !$item.hasClass('reply');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_delete_comment',
                    nonce: this.config.nonce,
                    comment_id: commentId
                },
                success: function(response) {
                    if (response.success) {
                        if (isMainComment) {
                            // Remove entire thread
                            $thread.slideUp(200, function() {
                                $(this).remove();

                                // Check if no more comments
                                if ($('.comment-thread').length === 0) {
                                    $('.comments-list').html(
                                        '<p class="no-comments">' +
                                        (self.config.strings.no_comments || 'No hay comentarios') +
                                        '</p>'
                                    );
                                }
                            });
                            self.updateCommentCount(-1);
                        } else {
                            // Remove just this reply
                            $item.slideUp(200, function() {
                                $(this).remove();
                            });
                        }
                    } else {
                        self.showError(response.data.message || self.config.strings.error);
                    }
                },
                error: function() {
                    self.showError(self.config.strings.error || 'Error de conexión');
                }
            });
        },

        /**
         * Prepend new comment to list
         */
        prependComment: function(comment) {
            var $list = $('.comments-list');
            var $noComments = $list.find('.no-comments');

            if ($noComments.length) {
                $noComments.remove();
            }

            var threadHtml = this.buildThreadHtml(comment);
            $(threadHtml).hide().prependTo($list).slideDown(200);
        },

        /**
         * Build thread HTML
         */
        buildThreadHtml: function(comment) {
            var typeLabels = {
                'note': '<span class="comment-type" style="color: #6b7280;">Nota</span>',
                'suggestion': '<span class="comment-type" style="color: #059669;">Sugerencia</span>',
                'issue': '<span class="comment-type" style="color: #dc2626;">Problema</span>',
                'question': '<span class="comment-type" style="color: #2563eb;">Pregunta</span>'
            };

            return '<div class="comment-thread" data-thread-id="' + comment.id + '">' +
                '<div class="comment-item" data-comment-id="' + comment.id + '">' +
                    '<img class="comment-avatar" src="' + comment.avatar + '" alt="">' +
                    '<div class="comment-content">' +
                        '<div class="comment-header">' +
                            '<span class="comment-author">' + this.escapeHtml(comment.author) + '</span>' +
                            (typeLabels[comment.type] || '') +
                            '<span class="comment-date">' + comment.date + '</span>' +
                        '</div>' +
                        '<div class="comment-text">' + this.escapeHtml(comment.text) + '</div>' +
                        '<div class="comment-actions">' +
                            '<button type="button" class="reply-btn">' +
                                (this.config.strings.reply || 'Responder') +
                            '</button>' +
                            '<button type="button" class="resolve-btn">' +
                                (this.config.strings.resolve || 'Resolver') +
                            '</button>' +
                            '<button type="button" class="delete-btn">' +
                                (this.config.strings.delete || 'Eliminar') +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        },

        /**
         * Build reply HTML
         */
        buildReplyHtml: function(comment) {
            return '<div class="comment-item reply" data-comment-id="' + comment.id + '">' +
                '<img class="comment-avatar" src="' + comment.avatar + '" alt="">' +
                '<div class="comment-content">' +
                    '<div class="comment-header">' +
                        '<span class="comment-author">' + this.escapeHtml(comment.author) + '</span>' +
                        '<span class="comment-date">' + comment.date + '</span>' +
                    '</div>' +
                    '<div class="comment-text">' + this.escapeHtml(comment.text) + '</div>' +
                    '<div class="comment-actions">' +
                        '<button type="button" class="delete-btn">' +
                            (this.config.strings.delete || 'Eliminar') +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        },

        /**
         * Update comment count badge
         */
        updateCommentCount: function(delta) {
            var $badge = $('.comment-count');
            var $toggleBadge = $('.flavor-ml-comments-toggle .badge');

            var currentCount = parseInt($badge.text()) || 0;
            var newCount = Math.max(0, currentCount + delta);

            $badge.text(newCount);

            if ($toggleBadge.length) {
                if (newCount > 0) {
                    $toggleBadge.text(newCount).show();
                } else {
                    $toggleBadge.hide();
                }
            }
        },

        /**
         * Show error message
         */
        showError: function(message) {
            var $error = $('<div class="notice notice-error is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 9999; padding: 10px 15px;">' +
                '<p>' + this.escapeHtml(message) + '</p>' +
                '<button type="button" class="notice-dismiss"></button>' +
            '</div>');

            $('body').append($error);

            $error.find('.notice-dismiss').on('click', function() {
                $error.fadeOut(200, function() {
                    $(this).remove();
                });
            });

            setTimeout(function() {
                $error.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on DOM ready
    $(function() {
        if ($('.flavor-ml-comments-panel').length || $('.flavor-ml-comments-toggle').length) {
            TranslationComments.init();
        }
    });

    // Expose for external use
    window.FlavorMLComments = TranslationComments;

})(jQuery);
