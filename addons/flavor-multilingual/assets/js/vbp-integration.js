/**
 * Integración de Flavor Multilingual con Visual Builder Pro
 *
 * @package FlavorMultilingual
 */

(function($) {
    'use strict';

    const FlavorMLVBP = {
        /**
         * Inicializa la integración
         */
        init: function() {
            this.bindEvents();
            this.loadTranslationStatus();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Selector de idioma en toolbar
            $(document).on('change', '#vbp-editing-language', this.onLanguageChange.bind(this));

            // Botones de traducción
            $(document).on('click', '.vbp-btn-translate-ai', this.onTranslateClick.bind(this));
            $(document).on('click', '.vbp-btn-edit-translation', this.onEditTranslationClick.bind(this));
            $(document).on('click', '.vbp-btn-translate-all', this.onTranslateAllClick.bind(this));

            // Guardar traducción
            $(document).on('click', '.vbp-save-translation', this.onSaveTranslation.bind(this));

            // Integración con el store de VBP
            if (window.vbpStore) {
                this.integrateWithStore();
            }
        },

        /**
         * Carga el estado de las traducciones
         */
        loadTranslationStatus: function() {
            const postId = this.getPostId();
            if (!postId) return;

            $.post(flavorMLVBP.ajaxUrl, {
                action: 'flavor_ml_get_vbp_translation_status',
                nonce: flavorMLVBP.nonce,
                post_id: postId
            }, (response) => {
                if (response.success && response.data.statuses) {
                    this.updateTranslationStatuses(response.data.statuses);
                }
            });
        },

        /**
         * Actualiza los indicadores de estado
         */
        updateTranslationStatuses: function(statuses) {
            Object.keys(statuses).forEach(lang => {
                const status = statuses[lang];
                const $item = $(`.vbp-translation-item[data-lang="${lang}"]`);
                const $status = $item.find('.vbp-translation-status');

                $status.attr('data-status', status);

                if (status === 'translated') {
                    $status.html('<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>');
                } else if (status === 'partial') {
                    $status.html('<span class="dashicons dashicons-warning" style="color: #f0ad4e;"></span>');
                } else {
                    $status.html('<span class="dashicons dashicons-minus" style="color: #ccc;"></span>');
                }
            });
        },

        /**
         * Obtiene el ID del post actual
         */
        getPostId: function() {
            // Intentar obtener de VBP store
            if (window.vbpStore && window.vbpStore.getState) {
                const state = window.vbpStore.getState();
                if (state.page && state.page.id) {
                    return state.page.id;
                }
            }

            // Fallback a URL
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('post') || urlParams.get('post_id') || $('#post_ID').val();
        },

        /**
         * Cambia el idioma de edición
         */
        onLanguageChange: function(e) {
            const lang = $(e.target).val();
            const postId = this.getPostId();

            if (lang === flavorMLVBP.defaultLang) {
                // Cargar contenido original
                this.loadOriginalContent();
            } else {
                // Cargar traducción
                this.loadTranslation(lang);
            }
        },

        /**
         * Carga el contenido original
         */
        loadOriginalContent: function() {
            // Recargar desde el meta original
            if (window.vbpStore && window.vbpStore.dispatch) {
                window.vbpStore.dispatch({ type: 'RELOAD_ORIGINAL_CONTENT' });
            }
        },

        /**
         * Carga una traducción
         */
        loadTranslation: function(lang) {
            const postId = this.getPostId();

            $.post(flavorMLVBP.ajaxUrl, {
                action: 'flavor_ml_get_vbp_translation',
                nonce: flavorMLVBP.nonce,
                post_id: postId,
                lang: lang
            }, (response) => {
                if (response.success && response.data.content) {
                    this.applyTranslationToEditor(response.data.content);
                }
            });
        },

        /**
         * Aplica la traducción al editor
         */
        applyTranslationToEditor: function(content) {
            if (window.vbpStore && window.vbpStore.dispatch) {
                window.vbpStore.dispatch({
                    type: 'SET_BLOCKS',
                    payload: content.blocks || []
                });
            }
        },

        /**
         * Click en traducir con IA
         */
        onTranslateClick: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const lang = $btn.data('lang');
            const postId = this.getPostId();

            this.setButtonLoading($btn, true);

            $.post(flavorMLVBP.ajaxUrl, {
                action: 'flavor_ml_translate_vbp_page',
                nonce: flavorMLVBP.nonce,
                post_id: postId,
                lang: lang
            }, (response) => {
                this.setButtonLoading($btn, false);

                if (response.success) {
                    this.showNotification(flavorMLVBP.i18n.translated, 'success');
                    this.loadTranslationStatus();

                    // Actualizar el estado del item
                    const $item = $btn.closest('.vbp-translation-item');
                    $item.find('.vbp-translation-status')
                        .attr('data-status', 'translated')
                        .html('<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>');
                } else {
                    this.showNotification(response.data || flavorMLVBP.i18n.error, 'error');
                }
            }).fail(() => {
                this.setButtonLoading($btn, false);
                this.showNotification(flavorMLVBP.i18n.error, 'error');
            });
        },

        /**
         * Click en editar traducción
         */
        onEditTranslationClick: function(e) {
            e.preventDefault();
            const lang = $(e.currentTarget).data('lang');

            // Cambiar el selector de idioma
            $('#vbp-editing-language').val(lang).trigger('change');

            // Scroll al editor
            $('html, body').animate({
                scrollTop: $('.vbp-editor-canvas').offset().top - 50
            }, 300);
        },

        /**
         * Click en traducir a todos los idiomas
         */
        onTranslateAllClick: function(e) {
            e.preventDefault();

            if (!confirm(flavorMLVBP.i18n.confirmTranslateAll)) {
                return;
            }

            const $btn = $(e.currentTarget);
            const postId = this.getPostId();

            this.setButtonLoading($btn, true);
            $btn.text(flavorMLVBP.i18n.translating);

            // Obtener todos los idiomas pendientes
            const languages = [];
            $('.vbp-translation-item').each(function() {
                const status = $(this).find('.vbp-translation-status').attr('data-status');
                if (status !== 'translated') {
                    languages.push($(this).data('lang'));
                }
            });

            if (languages.length === 0) {
                this.setButtonLoading($btn, false);
                this.showNotification('Todos los idiomas ya están traducidos', 'info');
                return;
            }

            // Traducir secuencialmente
            this.translateSequential(postId, languages, 0, $btn);
        },

        /**
         * Traduce idiomas secuencialmente
         */
        translateSequential: function(postId, languages, index, $btn) {
            if (index >= languages.length) {
                this.setButtonLoading($btn, false);
                $btn.html('<span class="dashicons dashicons-translation"></span> ' + flavorMLVBP.i18n.translated);
                this.loadTranslationStatus();
                return;
            }

            const lang = languages[index];
            $btn.text(`${flavorMLVBP.i18n.translating} (${index + 1}/${languages.length})`);

            $.post(flavorMLVBP.ajaxUrl, {
                action: 'flavor_ml_translate_vbp_page',
                nonce: flavorMLVBP.nonce,
                post_id: postId,
                lang: lang
            }, () => {
                // Actualizar estado visual
                const $item = $(`.vbp-translation-item[data-lang="${lang}"]`);
                $item.find('.vbp-translation-status')
                    .attr('data-status', 'translated')
                    .html('<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>');

                // Continuar con el siguiente
                this.translateSequential(postId, languages, index + 1, $btn);
            }).fail(() => {
                // Continuar incluso si falla
                this.translateSequential(postId, languages, index + 1, $btn);
            });
        },

        /**
         * Guarda la traducción actual
         */
        onSaveTranslation: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const lang = $('#vbp-editing-language').val();
            const postId = this.getPostId();

            if (lang === flavorMLVBP.defaultLang) {
                // Guardar normalmente si es el idioma por defecto
                return;
            }

            // Obtener contenido actual del editor
            let content = null;
            if (window.vbpStore && window.vbpStore.getState) {
                const state = window.vbpStore.getState();
                content = {
                    blocks: state.blocks || [],
                    version: state.version || '1.0'
                };
            }

            if (!content) {
                this.showNotification('No se pudo obtener el contenido', 'error');
                return;
            }

            this.setButtonLoading($btn, true);

            $.post(flavorMLVBP.ajaxUrl, {
                action: 'flavor_ml_save_vbp_translation',
                nonce: flavorMLVBP.nonce,
                post_id: postId,
                lang: lang,
                content: JSON.stringify(content)
            }, (response) => {
                this.setButtonLoading($btn, false);

                if (response.success) {
                    this.showNotification(flavorMLVBP.i18n.saved, 'success');
                } else {
                    this.showNotification(response.data || flavorMLVBP.i18n.error, 'error');
                }
            });
        },

        /**
         * Integra con el store de VBP
         */
        integrateWithStore: function() {
            // Añadir middleware para tracking de cambios en traducción
            if (window.vbpStore.subscribe) {
                let previousLang = $('#vbp-editing-language').val();

                window.vbpStore.subscribe(() => {
                    const currentLang = $('#vbp-editing-language').val();
                    if (currentLang !== previousLang) {
                        previousLang = currentLang;
                        // El idioma cambió, actualizar estado
                    }
                });
            }
        },

        /**
         * Establece estado de carga en un botón
         */
        setButtonLoading: function($btn, loading) {
            if (loading) {
                $btn.prop('disabled', true);
                $btn.find('.dashicons').removeClass('dashicons-translation').addClass('dashicons-update spin');
            } else {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-translation');
            }
        },

        /**
         * Muestra una notificación
         */
        showNotification: function(message, type = 'info') {
            // Usar sistema de notificaciones de VBP si existe
            if (window.vbpNotify) {
                window.vbpNotify(message, type);
                return;
            }

            // Fallback a alert/console
            if (type === 'error') {
                console.error('[FlavorML]', message);
                alert(message);
            } else {
                console.log('[FlavorML]', message);
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        // Esperar a que VBP esté listo
        if (window.vbpReady) {
            window.vbpReady(function() {
                FlavorMLVBP.init();
            });
        } else {
            // Fallback
            setTimeout(function() {
                FlavorMLVBP.init();
            }, 500);
        }
    });

    // Exponer globalmente
    window.FlavorMLVBP = FlavorMLVBP;

})(jQuery);
