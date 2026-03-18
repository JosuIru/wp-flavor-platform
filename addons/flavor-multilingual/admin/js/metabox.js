/**
 * Flavor Multilingual - Metabox JavaScript
 */
(function($) {
    'use strict';

    const FlavorMLMetabox = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // AI Translate button
            $(document).on('click', '.flavor-ml-ai-translate', this.translateWithAI.bind(this));

            // Translate all button
            $('#flavor-ml-translate-all').on('click', this.translateAll.bind(this));

            // Edit translation
            $(document).on('click', '.flavor-ml-edit-translation', this.openEditModal.bind(this));

            // Modal controls
            $('.flavor-ml-modal-close, #flavor-ml-edit-cancel').on('click', this.closeEditModal);
            $('#flavor-ml-edit-save').on('click', this.saveTranslation.bind(this));

            // Close modal on outside click
            $('#flavor-ml-edit-modal').on('click', function(e) {
                if ($(e.target).is('#flavor-ml-edit-modal')) {
                    FlavorMLMetabox.closeEditModal();
                }
            });
        },

        translateWithAI: function(e) {
            const $btn = $(e.currentTarget);
            const lang = $btn.data('lang');
            const $item = $btn.closest('.flavor-ml-lang-item');

            // Check if already has translation
            const hasTranslation = $item.find('.flavor-ml-status').hasClass('flavor-ml-status-draft') ||
                                   $item.find('.flavor-ml-status').hasClass('flavor-ml-status-published');

            if (hasTranslation && !confirm(flavorMLMetabox.i18n.confirmOverwrite)) {
                return;
            }

            $btn.addClass('is-loading').prop('disabled', true);
            const originalText = $btn.text();
            $btn.find('.dashicons').hide();
            $btn.text(flavorMLMetabox.i18n.translating);

            $.ajax({
                url: flavorMLMetabox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_translate_post',
                    nonce: flavorMLMetabox.nonce,
                    post_id: flavorMLMetabox.postId,
                    lang: lang
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        $item.find('.flavor-ml-status')
                            .removeClass('flavor-ml-status-none')
                            .addClass('flavor-ml-status-draft')
                            .text('Borrador');

                        // Show auto notice
                        if (!$item.find('.flavor-ml-auto-notice').length) {
                            $item.append(
                                '<p class="flavor-ml-auto-notice">' +
                                '<span class="dashicons dashicons-info-outline"></span>' +
                                'Traducción automática - revisar antes de publicar</p>'
                            );
                        }

                        // Update button
                        $btn.text('Retraducir');
                        $btn.prepend('<span class="dashicons dashicons-translation"></span> ');

                        // Add edit button if not exists
                        if (!$item.find('.flavor-ml-edit-translation').length) {
                            $btn.before(
                                '<button type="button" class="button button-small flavor-ml-edit-translation" ' +
                                'data-lang="' + lang + '">Editar</button>'
                            );
                        }
                    } else {
                        alert(response.data || flavorMLMetabox.i18n.error);
                        $btn.html('<span class="dashicons dashicons-translation"></span> ' + originalText);
                    }
                },
                error: function() {
                    alert(flavorMLMetabox.i18n.error);
                    $btn.html('<span class="dashicons dashicons-translation"></span> ' + originalText);
                },
                complete: function() {
                    $btn.removeClass('is-loading').prop('disabled', false);
                }
            });
        },

        translateAll: function() {
            const $btn = $('#flavor-ml-translate-all');
            const $items = $('.flavor-ml-lang-item');

            if ($items.length === 0) return;

            $btn.prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('<span class="dashicons dashicons-update-alt spin"></span> Traduciendo...');

            let completed = 0;
            const total = $items.length;

            $items.each(function() {
                const $translateBtn = $(this).find('.flavor-ml-ai-translate');
                if ($translateBtn.length) {
                    $translateBtn.trigger('click');
                }
            });

            // Re-enable after a delay
            setTimeout(function() {
                $btn.prop('disabled', false).html(originalText);
            }, total * 3000);
        },

        openEditModal: function(e) {
            const $btn = $(e.currentTarget);
            const lang = $btn.data('lang');

            $('#flavor-ml-edit-lang').val(lang);
            $('#flavor-ml-edit-modal').show();

            // Load current translation
            this.loadTranslation(lang);
        },

        closeEditModal: function() {
            $('#flavor-ml-edit-modal').hide();
            $('#flavor-ml-edit-title, #flavor-ml-edit-excerpt').val('');
            $('#flavor-ml-edit-lang').val('');
        },

        loadTranslation: function(lang) {
            // For now, just show empty fields
            // In a full implementation, this would load from the server
            $('#flavor-ml-edit-title').val('');
            $('#flavor-ml-edit-excerpt').val('');
        },

        saveTranslation: function() {
            const lang = $('#flavor-ml-edit-lang').val();
            const title = $('#flavor-ml-edit-title').val();
            const excerpt = $('#flavor-ml-edit-excerpt').val();
            const status = $('#flavor-ml-edit-status').val();

            const $btn = $('#flavor-ml-edit-save');
            $btn.prop('disabled', true).text(flavorMLMetabox.i18n.saving || 'Guardando...');

            $.ajax({
                url: flavorMLMetabox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_translation',
                    nonce: flavorMLMetabox.nonce,
                    post_id: flavorMLMetabox.postId,
                    lang: lang,
                    title: title,
                    excerpt: excerpt,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        FlavorMLMetabox.closeEditModal();
                        // Update status badge
                        const $item = $('.flavor-ml-lang-item[data-lang="' + lang + '"]');
                        $item.find('.flavor-ml-status')
                            .removeClass('flavor-ml-status-none flavor-ml-status-draft flavor-ml-status-published')
                            .addClass('flavor-ml-status-' + status)
                            .text(status === 'published' ? 'Publicada' : 'Borrador');
                    } else {
                        alert(response.data || 'Error');
                    }
                },
                error: function() {
                    alert('Error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Guardar');
                }
            });
        }
    };

    $(document).ready(function() {
        FlavorMLMetabox.init();
    });

})(jQuery);
