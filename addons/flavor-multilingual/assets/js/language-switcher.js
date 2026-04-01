/**
 * Flavor Multilingual - Language Switcher
 * @package FlavorMultilingual
 * @version 1.3.0
 */

(function($) {
    'use strict';

    var FlavorLangSwitcher = {

        config: {},

        /**
         * Initialize
         */
        init: function() {
            this.config = window.flavorMLSwitcher || {};
            this.bindEvents();
            this.initDropdowns();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Toggle dropdown
            $(document).on('click', '.flavor-ml-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleDropdown($(this));
            });

            // Close on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.flavor-ml-switcher').length) {
                    self.closeAllDropdowns();
                }
            });

            // Keyboard navigation
            $(document).on('keydown', '.flavor-ml-switcher', function(e) {
                self.handleKeyboard(e, $(this));
            });

            // Close on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeAllDropdowns();
                }
            });

            // Language link click (optional AJAX)
            $(document).on('click', '.flavor-ml-link[href]', function(e) {
                var $link = $(this);
                var href = $link.attr('href');

                // If AJAX is enabled and link has data-lang
                if (self.config.useAjax && $link.data('lang')) {
                    e.preventDefault();
                    self.switchLanguage($link.data('lang'), href);
                }
                // Otherwise, normal navigation
            });
        },

        /**
         * Initialize dropdowns
         */
        initDropdowns: function() {
            // Set initial ARIA states
            $('.flavor-ml-toggle').attr('aria-expanded', 'false');
            $('.flavor-ml-list').attr('role', 'listbox');
        },

        /**
         * Toggle dropdown
         */
        toggleDropdown: function($toggle) {
            var $switcher = $toggle.closest('.flavor-ml-switcher');
            var $list = $switcher.find('.flavor-ml-list');
            var isOpen = $toggle.attr('aria-expanded') === 'true';

            // Close other dropdowns
            this.closeAllDropdowns();

            if (!isOpen) {
                $toggle.attr('aria-expanded', 'true');
                $list.addClass('is-open');

                // Focus first item
                $list.find('.flavor-ml-link').first().focus();
            }
        },

        /**
         * Close all dropdowns
         */
        closeAllDropdowns: function() {
            $('.flavor-ml-toggle').attr('aria-expanded', 'false');
            $('.flavor-ml-list').removeClass('is-open');
        },

        /**
         * Handle keyboard navigation
         */
        handleKeyboard: function(e, $switcher) {
            var $toggle = $switcher.find('.flavor-ml-toggle');
            var $list = $switcher.find('.flavor-ml-list');
            var $items = $list.find('.flavor-ml-link');
            var $focused = $items.filter(':focus');
            var currentIndex = $items.index($focused);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if ($toggle.attr('aria-expanded') === 'false') {
                        this.toggleDropdown($toggle);
                    } else {
                        var nextIndex = currentIndex < $items.length - 1 ? currentIndex + 1 : 0;
                        $items.eq(nextIndex).focus();
                    }
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    if ($toggle.attr('aria-expanded') === 'true') {
                        var prevIndex = currentIndex > 0 ? currentIndex - 1 : $items.length - 1;
                        $items.eq(prevIndex).focus();
                    }
                    break;

                case 'Enter':
                case ' ':
                    if ($focused.length && $focused.is('a')) {
                        // Let the link navigate
                    } else if ($(e.target).is('.flavor-ml-toggle')) {
                        e.preventDefault();
                        this.toggleDropdown($toggle);
                    }
                    break;

                case 'Tab':
                    this.closeAllDropdowns();
                    break;

                case 'Home':
                    e.preventDefault();
                    $items.first().focus();
                    break;

                case 'End':
                    e.preventDefault();
                    $items.last().focus();
                    break;
            }
        },

        /**
         * Switch language via AJAX
         */
        switchLanguage: function(lang, fallbackUrl) {
            var self = this;

            if (!this.config.ajaxUrl || !this.config.nonce) {
                window.location.href = fallbackUrl;
                return;
            }

            // Show loading state
            $('body').addClass('flavor-ml-loading');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_ml_switch_lang',
                    nonce: this.config.nonce,
                    lang: lang,
                    current_url: window.location.href
                },
                success: function(response) {
                    if (response.success && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        window.location.href = fallbackUrl;
                    }
                },
                error: function() {
                    window.location.href = fallbackUrl;
                }
            });
        },

        /**
         * Update switcher dynamically (for SPA)
         */
        updateSwitcher: function(translations) {
            // This method can be called to update the switcher
            // when content changes without page reload (SPA scenarios)
            $('.flavor-ml-switcher').each(function() {
                var $switcher = $(this);
                var style = $switcher.data('style');

                // Trigger custom event for external handlers
                $switcher.trigger('flavor-ml:update', [translations]);
            });
        }
    };

    // Initialize on DOM ready
    $(function() {
        FlavorLangSwitcher.init();
    });

    // Expose for external use
    window.FlavorLangSwitcher = FlavorLangSwitcher;

})(jQuery);
