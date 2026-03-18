/**
 * Flavor Multilingual - Menu Admin JavaScript
 */
(function($) {
    'use strict';

    const FlavorMLMenu = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Translate single menu item with AI
            $(document).on('click', '.flavor-ml-translate-menu-item-ai', this.translateMenuItem.bind(this));

            // Translate all menu items
            $('#flavor-ml-translate-all-menu-items').on('click', this.translateAllMenuItems.bind(this));

            // Auto-save on blur
            $(document).on('blur', '.flavor-ml-menu-title, .flavor-ml-menu-attr', this.saveMenuItem.bind(this));
        },

        translateMenuItem: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const $row = $btn.closest('.flavor-ml-menu-lang-row');
            const itemId = $btn.data('item-id');
            const lang = $btn.data('lang');

            $btn.prop('disabled', true);
            $row.addClass('flavor-ml-translating');

            $.ajax({
                url: flavorMLMenu.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_translate_menu_item_ai',
                    nonce: flavorMLMenu.nonce,
                    item_id: itemId,
                    lang: lang
                },
                success: (response) => {
                    if (response.success && response.data.translations) {
                        const trans = response.data.translations;

                        if (trans.title) {
                            $row.find('.flavor-ml-menu-title').val(trans.title).addClass('flavor-ml-translated');
                        }
                        if (trans.attr_title) {
                            $row.find('.flavor-ml-menu-attr').val(trans.attr_title).addClass('flavor-ml-translated');
                        }

                        setTimeout(() => {
                            $row.find('input').removeClass('flavor-ml-translated');
                        }, 2000);
                    }

                    $btn.prop('disabled', false);
                    $row.removeClass('flavor-ml-translating');
                },
                error: () => {
                    $btn.prop('disabled', false);
                    $row.removeClass('flavor-ml-translating');
                    alert(flavorMLMenu.i18n.error);
                }
            });
        },

        translateAllMenuItems: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);

            if (!confirm('¿Traducir todos los elementos del menú a todos los idiomas?')) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span> ' + flavorMLMenu.i18n.translating);

            // Get all menu items
            const $menuItems = $('.menu-item');
            const items = [];

            $menuItems.each(function() {
                const itemId = $(this).attr('id').replace('menu-item-', '');
                items.push(itemId);
            });

            // Translate each item sequentially
            this.translateItemsSequentially(items, 0, $btn);
        },

        translateItemsSequentially: function(items, index, $btn) {
            if (index >= items.length) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span> Traducir todo el menú con IA');
                alert('Traducción completada');
                return;
            }

            const itemId = items[index];
            const $itemContainer = $('#menu-item-' + itemId);
            const $langRows = $itemContainer.find('.flavor-ml-menu-lang-row');

            // Translate to each language
            const promises = [];
            $langRows.each(function() {
                const $row = $(this);
                const lang = $row.data('lang');

                // Skip if already has translation
                if ($row.find('.flavor-ml-menu-title').val()) {
                    return;
                }

                promises.push($.ajax({
                    url: flavorMLMenu.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flavor_ml_translate_menu_item_ai',
                        nonce: flavorMLMenu.nonce,
                        item_id: itemId,
                        lang: lang
                    },
                    success: (response) => {
                        if (response.success && response.data.translations) {
                            const trans = response.data.translations;
                            if (trans.title) $row.find('.flavor-ml-menu-title').val(trans.title);
                            if (trans.attr_title) $row.find('.flavor-ml-menu-attr').val(trans.attr_title);
                        }
                    }
                }));
            });

            // Wait for all translations for this item, then proceed to next
            $.when.apply($, promises).always(() => {
                this.translateItemsSequentially(items, index + 1, $btn);
            });
        },

        saveMenuItem: function(e) {
            const $input = $(e.target);
            const $row = $input.closest('.flavor-ml-menu-lang-row');
            const $translateBtn = $row.find('.flavor-ml-translate-menu-item-ai');

            const itemId = $translateBtn.data('item-id');
            const lang = $row.data('lang');
            const field = $input.hasClass('flavor-ml-menu-title') ? 'title' : 'attr_title';
            const value = $input.val();

            // Only save if there's a value
            if (!value) return;

            $.ajax({
                url: flavorMLMenu.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_save_menu_item',
                    nonce: flavorMLMenu.nonce,
                    item_id: itemId,
                    lang: lang,
                    field: field,
                    value: value
                },
                success: (response) => {
                    if (response.success) {
                        $input.css('border-color', '#00a32a');
                        setTimeout(() => $input.css('border-color', ''), 1500);
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        FlavorMLMenu.init();
    });

})(jQuery);
