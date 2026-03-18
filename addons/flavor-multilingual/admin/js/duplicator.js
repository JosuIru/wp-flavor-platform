/**
 * Flavor Multilingual - Content Duplicator JavaScript
 */
(function($) {
    'use strict';

    const FlavorMLDuplicator = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Duplicar desde listado de posts
            $(document).on('click', '.flavor-ml-duplicate-link', this.handleDuplicateClick.bind(this));

            // Duplicar desde metabox
            $(document).on('click', '.flavor-ml-duplicate-btn', this.handleDuplicateFromMetabox.bind(this));

            // Traducir con IA desde metabox
            $(document).on('click', '.flavor-ml-translate-ai-btn', this.handleTranslateAI.bind(this));

            // Traducir TODO con IA
            $(document).on('click', '.flavor-ml-translate-all-btn', this.handleTranslateAll.bind(this));

            // Editar traducción existente
            $(document).on('click', '.flavor-ml-edit-translation', this.handleEditTranslation.bind(this));
        },

        handleDuplicateClick: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $link = $(e.currentTarget);
            const postId = $link.data('post-id');
            const lang = $link.data('lang');
            const action = $link.data('action');

            if (action === 'translate') {
                this.duplicateAndTranslate(postId, lang, $link);
            } else {
                this.duplicateOnly(postId, lang, $link);
            }
        },

        handleDuplicateFromMetabox: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const postId = $btn.data('post-id');
            const lang = $btn.data('lang');

            this.duplicateOnly(postId, lang, $btn);
        },

        handleTranslateAI: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const postId = $btn.data('post-id');
            const lang = $btn.data('lang');

            this.duplicateAndTranslate(postId, lang, $btn);
        },

        handleTranslateAll: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const postId = $btn.data('post-id');

            if (!confirm(flavorMLDuplicator.i18n.confirm_all)) {
                return;
            }

            this.translateToAllLanguages(postId, $btn);
        },

        handleEditTranslation: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const postId = $btn.data('post-id');
            const lang = $btn.data('lang');

            // Abrir editor de traducción (del módulo principal)
            if (typeof FlavorMLEditor !== 'undefined') {
                FlavorMLEditor.openEditor(postId, lang);
            } else {
                // Fallback: ir al editor del post
                window.location.href = `post.php?post=${postId}&action=edit&flavor_ml_lang=${lang}`;
            }
        },

        duplicateOnly: function(postId, lang, $element) {
            const originalText = $element.text();
            $element.prop('disabled', true).text(flavorMLDuplicator.i18n.translating);

            $.ajax({
                url: flavorMLDuplicator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_duplicate_for_translation',
                    nonce: flavorMLDuplicator.nonce,
                    post_id: postId,
                    lang: lang
                },
                success: (response) => {
                    if (response.success) {
                        this.updateLangItem(postId, lang, false);
                        this.showNotice('success', 'Duplicado creado. Ahora puedes editarlo.');
                    } else {
                        this.showNotice('error', response.data.message || 'Error');
                    }
                },
                error: () => {
                    this.showNotice('error', flavorMLDuplicator.i18n.error);
                },
                complete: () => {
                    $element.prop('disabled', false).text(originalText);
                }
            });
        },

        duplicateAndTranslate: function(postId, lang, $element) {
            const originalHtml = $element.html();
            $element.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span>');

            $.ajax({
                url: flavorMLDuplicator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_duplicate_and_translate',
                    nonce: flavorMLDuplicator.nonce,
                    post_id: postId,
                    lang: lang
                },
                success: (response) => {
                    if (response.success) {
                        this.updateLangItem(postId, lang, true);
                        this.showNotice('success', `✓ Traducido a ${lang.toUpperCase()}`);
                    } else {
                        this.showNotice('error', response.data.message || 'Error en la traducción');
                    }
                },
                error: () => {
                    this.showNotice('error', flavorMLDuplicator.i18n.error);
                },
                complete: () => {
                    $element.prop('disabled', false).html(originalHtml);
                }
            });
        },

        translateToAllLanguages: function(postId, $btn) {
            const $container = $btn.closest('.flavor-ml-duplicator-box');
            const $progress = $container.find('.flavor-ml-progress');
            const $progressFill = $container.find('.flavor-ml-progress-fill');
            const $progressText = $container.find('.flavor-ml-progress-text');
            const $langItems = $container.find('.flavor-ml-lang-item:not(.has-translation)');

            if ($langItems.length === 0) {
                this.showNotice('info', 'Todas las traducciones ya están completas');
                return;
            }

            $btn.prop('disabled', true);
            $progress.show();

            const languages = [];
            $langItems.each(function() {
                languages.push({
                    code: $(this).data('lang'),
                    name: $(this).find('strong').text()
                });
            });

            let current = 0;
            const total = languages.length;

            const translateNext = () => {
                if (current >= total) {
                    $progress.hide();
                    $btn.prop('disabled', false);
                    this.showNotice('success', flavorMLDuplicator.i18n.complete);
                    return;
                }

                const lang = languages[current];
                const percent = Math.round((current / total) * 100);

                $progressFill.css('width', percent + '%');
                $progressText.text(flavorMLDuplicator.i18n.progress.replace('%s', lang.name));

                $.ajax({
                    url: flavorMLDuplicator.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flavor_ml_duplicate_and_translate',
                        nonce: flavorMLDuplicator.nonce,
                        post_id: postId,
                        lang: lang.code
                    },
                    success: (response) => {
                        if (response.success) {
                            this.updateLangItem(postId, lang.code, true);
                        }
                    },
                    complete: () => {
                        current++;
                        translateNext();
                    }
                });
            };

            translateNext();
        },

        updateLangItem: function(postId, lang, hasTranslation) {
            const $item = $(`.flavor-ml-lang-item[data-lang="${lang}"]`);

            if (hasTranslation) {
                $item.removeClass('no-translation').addClass('has-translation');
                $item.find('.flavor-ml-status-icon').text('✓');

                // Reemplazar botones
                $item.find('.flavor-ml-lang-actions').html(`
                    <button type="button" class="button button-small flavor-ml-edit-translation"
                            data-post-id="${postId}"
                            data-lang="${lang}">
                        Editar
                    </button>
                `);
            }
        },

        showNotice: function(type, message) {
            // Crear notificación temporal
            const $notice = $(`
                <div class="notice notice-${type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success')} is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 100000; padding: 10px 15px;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss"></button>
                </div>
            `);

            $('body').append($notice);

            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(200, function() { $(this).remove(); });
            });

            setTimeout(() => {
                $notice.fadeOut(200, function() { $(this).remove(); });
            }, 4000);
        }
    };

    $(document).ready(function() {
        FlavorMLDuplicator.init();
    });

})(jQuery);
