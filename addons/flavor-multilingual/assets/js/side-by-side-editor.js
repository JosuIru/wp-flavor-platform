/**
 * Side by Side Editor
 * @package FlavorMultilingual
 * @version 1.3.0
 */

(function($) {
    'use strict';

    var SBSEditor = {

        config: {},
        unsavedChanges: false,
        autoSaveTimer: null,

        /**
         * Initialize
         */
        init: function() {
            this.config = window.flavorMLEditor || {};
            this.bindEvents();
            this.bindKeyboardShortcuts();
            this.initAutoSave();
            this.loadTMSuggestions();
            this.showShortcutsHint();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Field changes
            $(document).on('input change', '.translation-field', function() {
                self.onFieldChange($(this));
            });

            // TinyMCE changes
            $(document).on('tinymce-editor-init', function(event, editor) {
                editor.on('change keyup', function() {
                    var $textarea = $('#' + editor.id);
                    self.onFieldChange($textarea);
                });
            });

            // AI translate button
            $(document).on('click', '.ai-translate-btn', function(e) {
                e.preventDefault();
                var $row = $(this).closest('.flavor-ml-field-row');
                self.translateField($row);
            });

            // Copy source button
            $(document).on('click', '.copy-source-btn', function(e) {
                e.preventDefault();
                var $row = $(this).closest('.flavor-ml-field-row');
                self.copySourceToTarget($row);
            });

            // Save all button
            $('#flavor-ml-save-all').on('click', function(e) {
                e.preventDefault();
                self.saveAll();
            });

            // Translate all button
            $('#flavor-ml-translate-all').on('click', function(e) {
                e.preventDefault();
                if (confirm(self.config.strings.confirm_ai)) {
                    self.translateAll();
                }
            });

            // Form submit
            $('#flavor-ml-editor-form').on('submit', function(e) {
                e.preventDefault();
                self.saveAll(function() {
                    self.unsavedChanges = false;
                    // Optionally redirect or show success
                });
            });

            // TM suggestion click
            $(document).on('click', '.tm-item', function() {
                var $row = $(this).closest('.flavor-ml-field-row');
                var translation = $(this).find('.tm-target').text();
                self.setFieldValue($row, translation);
            });

            // Toggle TM suggestions
            $('#show-tm-suggestions').on('change', function() {
                if ($(this).is(':checked')) {
                    self.loadTMSuggestions();
                    $('.tm-suggestions').show();
                } else {
                    $('.tm-suggestions').hide();
                }
            });

            // Warn before leaving with unsaved changes
            $(window).on('beforeunload', function() {
                if (self.unsavedChanges) {
                    return self.config.strings.unsaved;
                }
            });
        },

        /**
         * Initialize auto-save
         */
        initAutoSave: function() {
            // Auto-save after 2 seconds of inactivity
            // Already handled by onFieldChange debounce
        },

        /**
         * On field change
         */
        onFieldChange: function($field) {
            var self = this;
            var $row = $field.closest('.flavor-ml-field-row');

            this.unsavedChanges = true;
            $row.find('.save-indicator').text('').removeClass('saving saved error');

            // Debounced auto-save
            clearTimeout(this.autoSaveTimer);
            this.autoSaveTimer = setTimeout(function() {
                self.saveField($row);
            }, 1500);
        },

        /**
         * Save single field
         */
        saveField: function($row) {
            var self = this;
            var $form = $('#flavor-ml-editor-form');
            var fieldKey = $row.data('field');
            var $indicator = $row.find('.save-indicator');

            var value = this.getFieldValue($row);

            $indicator.text(this.config.strings.saving).addClass('saving');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_translation_field',
                    nonce: this.config.nonce,
                    post_id: $form.data('post-id'),
                    lang: $form.data('lang'),
                    field: fieldKey,
                    value: value
                },
                success: function(response) {
                    if (response.success) {
                        $indicator.text(self.config.strings.saved).removeClass('saving').addClass('saved');
                        $row.removeClass('not-translated').addClass('is-translated');

                        setTimeout(function() {
                            $indicator.text('').removeClass('saved');
                        }, 2000);
                    } else {
                        $indicator.text(self.config.strings.error).removeClass('saving').addClass('error');
                    }
                },
                error: function() {
                    $indicator.text(self.config.strings.error).removeClass('saving').addClass('error');
                }
            });
        },

        /**
         * Save all fields
         */
        saveAll: function(callback) {
            var self = this;
            var $rows = $('.flavor-ml-field-row');
            var total = $rows.length;
            var completed = 0;

            $rows.each(function() {
                var $row = $(this);
                self.saveField($row);
                completed++;

                if (completed >= total && typeof callback === 'function') {
                    setTimeout(callback, 500);
                }
            });
        },

        /**
         * Translate field with AI
         */
        translateField: function($row) {
            var self = this;
            var $form = $('#flavor-ml-editor-form');
            var fieldKey = $row.data('field');
            var sourceText = $row.find('.source-content').text().trim();
            var $indicator = $row.find('.save-indicator');

            if (!sourceText) {
                return;
            }

            $row.addClass('loading');
            $indicator.text(this.config.strings.translating).addClass('saving');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_ai_translate_field',
                    nonce: this.config.nonce,
                    post_id: $form.data('post-id'),
                    source_lang: this.getSourceLang(),
                    target_lang: $form.data('lang'),
                    field: fieldKey,
                    text: sourceText
                },
                success: function(response) {
                    $row.removeClass('loading');

                    if (response.success) {
                        self.setFieldValue($row, response.data.translation);
                        $indicator.text(self.config.strings.translated).removeClass('saving').addClass('saved');

                        // Auto-save after translation
                        self.saveField($row);
                    } else {
                        $indicator.text(response.data.message || self.config.strings.error).removeClass('saving').addClass('error');
                    }
                },
                error: function() {
                    $row.removeClass('loading');
                    $indicator.text(self.config.strings.error).removeClass('saving').addClass('error');
                }
            });
        },

        /**
         * Translate all fields
         */
        translateAll: function() {
            var self = this;
            var $rows = $('.flavor-ml-field-row.not-translated');
            var index = 0;

            function translateNext() {
                if (index >= $rows.length) {
                    return;
                }

                var $row = $rows.eq(index);
                self.translateField($row);

                index++;
                setTimeout(translateNext, 2000); // Wait between API calls
            }

            translateNext();
        },

        /**
         * Copy source to target
         */
        copySourceToTarget: function($row) {
            var sourceText = $row.find('.source-content').text().trim();
            this.setFieldValue($row, sourceText);
            this.onFieldChange($row.find('.translation-field'));
        },

        /**
         * Get field value
         */
        getFieldValue: function($row) {
            var fieldKey = $row.data('field');
            var $field = $row.find('.translation-field');

            // Check if it's a TinyMCE editor
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('translation_' + fieldKey)) {
                return tinyMCE.get('translation_' + fieldKey).getContent();
            }

            return $field.val();
        },

        /**
         * Set field value
         */
        setFieldValue: function($row, value) {
            var fieldKey = $row.data('field');
            var $field = $row.find('.translation-field');

            // Check if it's a TinyMCE editor
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('translation_' + fieldKey)) {
                tinyMCE.get('translation_' + fieldKey).setContent(value);
            } else {
                $field.val(value);
            }

            this.unsavedChanges = true;
        },

        /**
         * Get source language
         */
        getSourceLang: function() {
            // Get from toolbar or default to 'es'
            var sourceLang = $('.source-lang').text().toLowerCase();

            // Map common names to codes
            var langMap = {
                'español': 'es',
                'castellano': 'es',
                'english': 'en',
                'euskara': 'eu',
                'català': 'ca',
                'galego': 'gl',
                'français': 'fr',
                'deutsch': 'de'
            };

            for (var name in langMap) {
                if (sourceLang.indexOf(name) !== -1) {
                    return langMap[name];
                }
            }

            return 'es'; // Default
        },

        /**
         * Load TM suggestions for all fields
         */
        loadTMSuggestions: function() {
            var self = this;

            if (!$('#show-tm-suggestions').is(':checked')) {
                return;
            }

            $('.flavor-ml-field-row').each(function() {
                var $row = $(this);
                var sourceText = $row.find('.source-content').text().trim();

                if (sourceText.length < 20) {
                    return;
                }

                self.loadFieldTMSuggestions($row, sourceText);
            });
        },

        /**
         * Load TM suggestions for a single field
         */
        loadFieldTMSuggestions: function($row, sourceText) {
            var $tmContainer = $row.find('.tm-suggestions');
            var $tmList = $tmContainer.find('.tm-list');
            var $form = $('#flavor-ml-editor-form');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_get_tm_suggestions',
                    nonce: this.config.nonce,
                    text: sourceText.substring(0, 500), // Limit text length
                    source_lang: this.getSourceLang(),
                    target_lang: $form.data('lang')
                },
                success: function(response) {
                    if (response.success && response.data.suggestions.length > 0) {
                        $tmList.empty();

                        $.each(response.data.suggestions, function(i, suggestion) {
                            var scorePercent = Math.round(suggestion.score * 100);

                            var $item = $('<div class="tm-item">' +
                                '<span class="tm-score">' + scorePercent + '%</span>' +
                                '<div class="tm-content">' +
                                    '<div class="tm-source">' + self.escapeHtml(suggestion.source.substring(0, 100)) + '...</div>' +
                                    '<div class="tm-target">' + self.escapeHtml(suggestion.target) + '</div>' +
                                '</div>' +
                            '</div>');

                            $tmList.append($item);
                        });

                        $tmContainer.show();
                    }
                }
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Bind keyboard shortcuts
         */
        bindKeyboardShortcuts: function() {
            var self = this;

            $(document).on('keydown', function(e) {
                // Check for Ctrl/Cmd key
                var isCtrlOrCmd = e.ctrlKey || e.metaKey;

                if (!isCtrlOrCmd) {
                    return;
                }

                var $focusedRow = $('.flavor-ml-field-row:focus-within');

                switch (e.key.toLowerCase()) {
                    // Ctrl+S: Save current field or all
                    case 's':
                        e.preventDefault();
                        if ($focusedRow.length) {
                            self.saveField($focusedRow);
                            self.showNotification(self.config.strings.saving, 'info');
                        } else {
                            self.saveAll();
                            self.showNotification(self.config.strings.saving_all || 'Guardando todo...', 'info');
                        }
                        break;

                    // Ctrl+T: Translate current field
                    case 't':
                        if ($focusedRow.length) {
                            e.preventDefault();
                            self.translateField($focusedRow);
                        }
                        break;

                    // Ctrl+Shift+T: Translate all
                    case 't':
                        if (e.shiftKey) {
                            e.preventDefault();
                            if (confirm(self.config.strings.confirm_ai)) {
                                self.translateAll();
                            }
                        }
                        break;

                    // Ctrl+D: Copy source to target
                    case 'd':
                        if ($focusedRow.length) {
                            e.preventDefault();
                            self.copySourceToTarget($focusedRow);
                        }
                        break;

                    // Ctrl+Enter: Save and go to next field
                    case 'enter':
                        if ($focusedRow.length) {
                            e.preventDefault();
                            self.saveField($focusedRow);
                            self.focusNextField($focusedRow);
                        }
                        break;

                    // Ctrl+Arrow Down: Go to next field
                    case 'arrowdown':
                        e.preventDefault();
                        if ($focusedRow.length) {
                            self.focusNextField($focusedRow);
                        } else {
                            $('.flavor-ml-field-row:first .translation-field').focus();
                        }
                        break;

                    // Ctrl+Arrow Up: Go to previous field
                    case 'arrowup':
                        e.preventDefault();
                        if ($focusedRow.length) {
                            self.focusPrevField($focusedRow);
                        } else {
                            $('.flavor-ml-field-row:last .translation-field').focus();
                        }
                        break;
                }
            });

            // Escape to blur current field
            $(document).on('keydown', '.translation-field', function(e) {
                if (e.key === 'Escape') {
                    $(this).blur();
                }
            });
        },

        /**
         * Focus next field
         */
        focusNextField: function($currentRow) {
            var $nextRow = $currentRow.next('.flavor-ml-field-row');
            if ($nextRow.length) {
                $nextRow.find('.translation-field').focus();
            } else {
                // Wrap to first
                $('.flavor-ml-field-row:first .translation-field').focus();
            }
        },

        /**
         * Focus previous field
         */
        focusPrevField: function($currentRow) {
            var $prevRow = $currentRow.prev('.flavor-ml-field-row');
            if ($prevRow.length) {
                $prevRow.find('.translation-field').focus();
            } else {
                // Wrap to last
                $('.flavor-ml-field-row:last .translation-field').focus();
            }
        },

        /**
         * Show keyboard shortcuts hint
         */
        showShortcutsHint: function() {
            var self = this;

            // Only show once per session
            if (sessionStorage.getItem('flavor_ml_shortcuts_shown')) {
                return;
            }

            var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            var modKey = isMac ? 'Cmd' : 'Ctrl';

            var shortcuts = [
                { key: modKey + '+S', desc: this.config.strings.shortcut_save || 'Guardar campo' },
                { key: modKey + '+T', desc: this.config.strings.shortcut_translate || 'Traducir con IA' },
                { key: modKey + '+D', desc: this.config.strings.shortcut_copy || 'Copiar original' },
                { key: modKey + '+Enter', desc: this.config.strings.shortcut_next || 'Guardar y siguiente' },
                { key: modKey + '+\u2191/\u2193', desc: this.config.strings.shortcut_navigate || 'Navegar campos' },
                { key: 'Esc', desc: this.config.strings.shortcut_blur || 'Deseleccionar' }
            ];

            var $hint = $('<div class="shortcuts-hint">' +
                '<div class="shortcuts-hint-header">' +
                    '<span class="dashicons dashicons-info"></span> ' +
                    (this.config.strings.shortcuts_title || 'Atajos de teclado') +
                    '<button type="button" class="shortcuts-hint-close">&times;</button>' +
                '</div>' +
                '<div class="shortcuts-hint-list"></div>' +
            '</div>');

            var $list = $hint.find('.shortcuts-hint-list');
            $.each(shortcuts, function(i, shortcut) {
                $list.append(
                    '<div class="shortcut-item">' +
                        '<kbd>' + shortcut.key + '</kbd>' +
                        '<span>' + shortcut.desc + '</span>' +
                    '</div>'
                );
            });

            // Insert after toolbar
            $('.flavor-ml-toolbar').after($hint);

            // Close button
            $hint.find('.shortcuts-hint-close').on('click', function() {
                $hint.fadeOut(200, function() {
                    $(this).remove();
                });
                sessionStorage.setItem('flavor_ml_shortcuts_shown', '1');
            });

            // Auto-hide after 10 seconds
            setTimeout(function() {
                $hint.fadeOut(300, function() {
                    $(this).remove();
                });
                sessionStorage.setItem('flavor_ml_shortcuts_shown', '1');
            }, 10000);
        },

        /**
         * Show notification toast
         */
        showNotification: function(message, type) {
            type = type || 'info';

            // Remove existing
            $('.flavor-ml-notification').remove();

            var iconMap = {
                'success': 'yes',
                'error': 'no',
                'warning': 'warning',
                'info': 'info'
            };

            var $notification = $('<div class="flavor-ml-notification type-' + type + '">' +
                '<span class="dashicons dashicons-' + (iconMap[type] || 'info') + '"></span> ' +
                '<span class="notification-message">' + this.escapeHtml(message) + '</span>' +
            '</div>');

            $('body').append($notification);

            // Animate in
            setTimeout(function() {
                $notification.addClass('visible');
            }, 10);

            // Auto-hide after 3 seconds
            setTimeout(function() {
                $notification.removeClass('visible');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Initialize on DOM ready
    $(function() {
        if ($('#flavor-ml-editor-form').length) {
            SBSEditor.init();
        }
    });

    // Expose for external use
    window.FlavorMLSBSEditor = SBSEditor;

})(jQuery);
