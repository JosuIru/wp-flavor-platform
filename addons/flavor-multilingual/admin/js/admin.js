/**
 * Flavor Multilingual - Admin JavaScript
 */
(function($) {
    'use strict';

    const FlavorMLAdmin = {
        init: function() {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function() {
            // Add language
            $('#flavor-ml-add-language-btn').on('click', this.addLanguage.bind(this));

            // Toggle active
            $(document).on('click', '.flavor-ml-toggle-active', this.toggleActive.bind(this));

            // Delete language
            $(document).on('click', '.flavor-ml-delete', this.deleteLanguage.bind(this));
        },

        initSortable: function() {
            if (!$('#flavor-ml-languages-list').length) return;

            $('#flavor-ml-languages-list').sortable({
                handle: '.flavor-ml-drag-handle',
                axis: 'y',
                update: this.saveOrder.bind(this)
            });
        },

        addLanguage: function() {
            const code = $('#flavor-ml-add-language-select').val();
            if (!code) {
                alert(flavorML.i18n.error);
                return;
            }

            const $btn = $('#flavor-ml-add-language-btn');
            $btn.prop('disabled', true).text(flavorML.i18n.saving);

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_language',
                    nonce: flavorML.nonce,
                    code: code,
                    action_type: 'add'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || flavorML.i18n.error);
                        $btn.prop('disabled', false).text('Añadir');
                    }
                },
                error: function() {
                    alert(flavorML.i18n.error);
                    $btn.prop('disabled', false).text('Añadir');
                }
            });
        },

        toggleActive: function(e) {
            const $btn = $(e.currentTarget);
            const code = $btn.data('code');
            const currentActive = $btn.data('active') === 1;

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_language',
                    nonce: flavorML.nonce,
                    code: code,
                    action_type: 'toggle',
                    active: currentActive ? 0 : 1
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || flavorML.i18n.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(flavorML.i18n.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        deleteLanguage: function(e) {
            const $btn = $(e.currentTarget);
            const code = $btn.data('code');

            if (!confirm(flavorML.i18n.confirmDelete)) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_delete_language',
                    nonce: flavorML.nonce,
                    code: code
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data || flavorML.i18n.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(flavorML.i18n.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        saveOrder: function(event, ui) {
            const order = [];
            $('#flavor-ml-languages-list tr').each(function() {
                order.push($(this).data('code'));
            });

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_reorder_languages',
                    nonce: flavorML.nonce,
                    order: order
                }
            });
        }
    };

    // === Strings Manager ===
    const FlavorMLStrings = {
        currentPage: 1,
        currentLang: '',

        init: function() {
            if (!$('#flavor-ml-strings-table').length) return;

            this.currentLang = $('#flavor-ml-strings-lang').val();
            this.bindEvents();
            this.loadStrings();
        },

        bindEvents: function() {
            $('#flavor-ml-strings-filter').on('click', () => this.loadStrings());
            $('#flavor-ml-scan-strings').on('click', () => this.scanStrings());
            $('#flavor-ml-strings-lang').on('change', (e) => {
                this.currentLang = $(e.target).val();
                this.loadStrings();
            });

            // Enter key in search
            $('#flavor-ml-strings-search').on('keypress', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.loadStrings();
                }
            });

            // Save string translation
            $(document).on('blur', '.flavor-ml-string-input', (e) => this.saveString(e));

            // Translate with AI
            $(document).on('click', '.flavor-ml-translate-string-ai', (e) => this.translateStringAI(e));
        },

        loadStrings: function(page = 1) {
            this.currentPage = page;
            const $tbody = $('#flavor-ml-strings-list');

            $tbody.html('<tr><td colspan="4" class="flavor-ml-loading">Cargando...</td></tr>');

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_get_strings',
                    nonce: flavorML.nonce,
                    domain: $('#flavor-ml-strings-domain').val(),
                    lang: this.currentLang,
                    status: $('#flavor-ml-strings-status').val(),
                    search: $('#flavor-ml-strings-search').val(),
                    page: page
                },
                success: (response) => {
                    if (response.success) {
                        this.renderStrings(response.data);
                    } else {
                        $tbody.html('<tr><td colspan="4">Error al cargar</td></tr>');
                    }
                }
            });
        },

        renderStrings: function(data) {
            const $tbody = $('#flavor-ml-strings-list');
            $tbody.empty();

            if (!data.strings || data.strings.length === 0) {
                $tbody.html('<tr><td colspan="4">No se encontraron cadenas</td></tr>');
                return;
            }

            data.strings.forEach(str => {
                const row = `
                    <tr data-string-key="${str.string_key}">
                        <td class="column-original">
                            <div class="flavor-ml-original-string">${this.escapeHtml(str.original_string)}</div>
                        </td>
                        <td class="column-translation">
                            <input type="text" class="flavor-ml-string-input"
                                   value="${this.escapeHtml(str.translation || '')}"
                                   data-original="${this.escapeHtml(str.original_string)}"
                                   data-domain="${str.domain || ''}"
                                   placeholder="Sin traducir...">
                        </td>
                        <td class="column-domain">${str.domain || '-'}</td>
                        <td class="column-actions">
                            <button type="button" class="button button-small flavor-ml-translate-string-ai"
                                    data-original="${this.escapeHtml(str.original_string)}"
                                    data-domain="${str.domain || ''}">
                                <span class="dashicons dashicons-translation"></span>
                            </button>
                        </td>
                    </tr>
                `;
                $tbody.append(row);
            });

            // Pagination
            this.renderPagination(data);
        },

        renderPagination: function(data) {
            const $pagination = $('#flavor-ml-strings-pagination');
            $pagination.empty();

            if (data.pages <= 1) return;

            for (let i = 1; i <= data.pages; i++) {
                const $link = $('<a href="#" class="page-numbers"></a>').text(i);
                if (i === data.page) {
                    $link.addClass('current');
                }
                $link.on('click', (e) => {
                    e.preventDefault();
                    this.loadStrings(i);
                });
                $pagination.append($link);
            }
        },

        saveString: function(e) {
            const $input = $(e.target);
            const original = $input.data('original');
            const translation = $input.val();
            const domain = $input.data('domain');

            if (!original) return;

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_string',
                    nonce: flavorML.nonce,
                    original: original,
                    translation: translation,
                    lang: this.currentLang,
                    domain: domain
                },
                success: (response) => {
                    if (response.success) {
                        $input.css('border-color', '#00a32a');
                        setTimeout(() => $input.css('border-color', ''), 1500);
                    }
                }
            });
        },

        translateStringAI: function(e) {
            const $btn = $(e.currentTarget);
            const $row = $btn.closest('tr');
            const $input = $row.find('.flavor-ml-string-input');
            const original = $btn.data('original');
            const domain = $btn.data('domain');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_translate_string_ai',
                    nonce: flavorML.nonce,
                    original: original,
                    lang: this.currentLang,
                    domain: domain
                },
                success: (response) => {
                    if (response.success && response.data.translation) {
                        $input.val(response.data.translation);
                        $input.css('border-color', '#00a32a');
                        setTimeout(() => $input.css('border-color', ''), 1500);
                    }
                    $btn.prop('disabled', false);
                },
                error: () => {
                    $btn.prop('disabled', false);
                }
            });
        },

        scanStrings: function() {
            const $btn = $('#flavor-ml-scan-strings');
            $btn.prop('disabled', true).html('<span class="flavor-ml-spinner"></span> Escaneando...');

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_scan_strings',
                    nonce: flavorML.nonce
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message);
                        this.loadStrings();
                    }
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Escanear cadenas');
                },
                error: () => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Escanear cadenas');
                }
            });
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // === Translations Editor ===
    const FlavorMLEditor = {
        currentPostId: null,
        currentLang: null,
        originalContent: {},

        init: function() {
            if (!$('#flavor-ml-content-table').length) return;
            this.bindEvents();
        },

        bindEvents: function() {
            // Open editor
            $(document).on('click', '.flavor-ml-edit-all-translations', (e) => this.openEditor(e));

            // Close editor
            $(document).on('click', '.flavor-ml-modal-close, #flavor-ml-editor-cancel', () => this.closeEditor());

            // Language tabs
            $(document).on('click', '.flavor-ml-lang-tabs a', (e) => this.switchLanguage(e));

            // Save translation
            $('#flavor-ml-editor-save').on('click', () => this.saveTranslation());

            // AI translate
            $('#flavor-ml-editor-ai-translate').on('click', () => this.translateWithAI());

            // Translate all languages for a post
            $(document).on('click', '.flavor-ml-translate-all-langs', (e) => this.translateAllLanguages(e));

            // Close on ESC
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') this.closeEditor();
            });
        },

        openEditor: function(e) {
            const $btn = $(e.currentTarget);
            this.currentPostId = $btn.data('post-id');

            // Reset
            this.originalContent = {};

            // Load post data via AJAX
            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_get_post_data',
                    nonce: flavorML.nonce,
                    post_id: this.currentPostId
                },
                success: (response) => {
                    if (response.success) {
                        this.originalContent = response.data;
                        this.renderOriginal();

                        // Select first language
                        const $firstLang = $('.flavor-ml-lang-tabs a').first();
                        $firstLang.addClass('active');
                        this.currentLang = $firstLang.data('lang');
                        $('#flavor-ml-current-lang-name').text($firstLang.text().trim());
                        $('#flavor-ml-editor-lang').val(this.currentLang);
                        $('#flavor-ml-editor-post-id').val(this.currentPostId);

                        this.loadTranslation(this.currentLang);

                        $('#flavor-ml-full-editor-modal').show();
                    }
                }
            });
        },

        renderOriginal: function() {
            $('#flavor-ml-original-title').text(this.originalContent.title || '');
            $('#flavor-ml-original-content').html(this.originalContent.content || '');
            $('#flavor-ml-original-excerpt').text(this.originalContent.excerpt || '');
        },

        switchLanguage: function(e) {
            e.preventDefault();
            const $link = $(e.currentTarget);

            $('.flavor-ml-lang-tabs a').removeClass('active');
            $link.addClass('active');

            this.currentLang = $link.data('lang');
            $('#flavor-ml-current-lang-name').text($link.text().trim());
            $('#flavor-ml-editor-lang').val(this.currentLang);

            this.loadTranslation(this.currentLang);
        },

        loadTranslation: function(lang) {
            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_get_translation',
                    nonce: flavorML.nonce,
                    post_id: this.currentPostId,
                    lang: lang
                },
                success: (response) => {
                    if (response.success) {
                        $('#flavor-ml-trans-title').val(response.data.title || '');
                        if (typeof tinymce !== 'undefined' && tinymce.get('flavor-ml-trans-content')) {
                            tinymce.get('flavor-ml-trans-content').setContent(response.data.content || '');
                        }
                        $('#flavor-ml-trans-excerpt').val(response.data.excerpt || '');
                        $('#flavor-ml-trans-status').val(response.data.status || 'draft');
                    }
                }
            });
        },

        saveTranslation: function() {
            const $btn = $('#flavor-ml-editor-save');
            $btn.prop('disabled', true).text('Guardando...');

            let content = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('flavor-ml-trans-content')) {
                content = tinymce.get('flavor-ml-trans-content').getContent();
            } else {
                content = $('#flavor-ml-trans-content').val();
            }

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_full_translation',
                    nonce: flavorML.nonce,
                    post_id: this.currentPostId,
                    lang: this.currentLang,
                    title: $('#flavor-ml-trans-title').val(),
                    content: content,
                    excerpt: $('#flavor-ml-trans-excerpt').val(),
                    status: $('#flavor-ml-trans-status').val()
                },
                success: (response) => {
                    if (response.success) {
                        $btn.text('Guardado!');
                        setTimeout(() => $btn.prop('disabled', false).text('Guardar traducción'), 1500);
                    } else {
                        alert(response.data || 'Error');
                        $btn.prop('disabled', false).text('Guardar traducción');
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Guardar traducción');
                }
            });
        },

        translateWithAI: function() {
            const $btn = $('#flavor-ml-editor-ai-translate');
            $btn.prop('disabled', true).html('<span class="flavor-ml-spinner"></span> Traduciendo...');

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_translate_post',
                    nonce: flavorML.nonce,
                    post_id: this.currentPostId,
                    lang: this.currentLang
                },
                success: (response) => {
                    if (response.success && response.data.translations) {
                        const trans = response.data.translations;
                        if (trans.title) $('#flavor-ml-trans-title').val(trans.title);
                        if (trans.content && typeof tinymce !== 'undefined' && tinymce.get('flavor-ml-trans-content')) {
                            tinymce.get('flavor-ml-trans-content').setContent(trans.content);
                        }
                        if (trans.excerpt) $('#flavor-ml-trans-excerpt').val(trans.excerpt);
                    }
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span> Traducir con IA');
                },
                error: () => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span> Traducir con IA');
                }
            });
        },

        translateAllLanguages: function(e) {
            const $btn = $(e.currentTarget);
            const postId = $btn.data('post-id');

            if (!confirm('¿Traducir este contenido a todos los idiomas con IA?')) return;

            $btn.prop('disabled', true).html('<span class="flavor-ml-spinner"></span>');

            $.ajax({
                url: flavorML.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_translate_all_languages',
                    nonce: flavorML.nonce,
                    post_id: postId
                },
                success: (response) => {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span>');
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span>');
                }
            });
        },

        closeEditor: function() {
            $('#flavor-ml-full-editor-modal').hide();
            this.currentPostId = null;
            this.currentLang = null;
        }
    };

    $(document).ready(function() {
        FlavorMLAdmin.init();
        FlavorMLStrings.init();
        FlavorMLEditor.init();
    });

})(jQuery);
