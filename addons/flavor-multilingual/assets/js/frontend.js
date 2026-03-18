/**
 * Flavor Multilingual - Frontend JavaScript
 */
(function($) {
    'use strict';

    const FlavorML = {
        init: function() {
            this.bindDropdowns();
            this.bindSelects();
        },

        bindDropdowns: function() {
            // Toggle dropdown
            $(document).on('click', '.flavor-ml-dropdown-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $toggle = $(this);
                const isExpanded = $toggle.attr('aria-expanded') === 'true';

                // Close all other dropdowns
                $('.flavor-ml-dropdown-toggle').attr('aria-expanded', 'false');

                // Toggle this one
                $toggle.attr('aria-expanded', !isExpanded);
            });

            // Close on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.flavor-ml-switcher-dropdown').length) {
                    $('.flavor-ml-dropdown-toggle').attr('aria-expanded', 'false');
                }
            });

            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.flavor-ml-dropdown-toggle').attr('aria-expanded', 'false');
                }
            });

            // Keyboard navigation
            $(document).on('keydown', '.flavor-ml-dropdown-toggle', function(e) {
                if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).attr('aria-expanded', 'true');
                    $(this).next('.flavor-ml-dropdown-menu').find('a').first().focus();
                }
            });

            $(document).on('keydown', '.flavor-ml-dropdown-menu a', function(e) {
                const $links = $(this).closest('.flavor-ml-dropdown-menu').find('a');
                const currentIndex = $links.index(this);

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % $links.length;
                    $links.eq(nextIndex).focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIndex = (currentIndex - 1 + $links.length) % $links.length;
                    $links.eq(prevIndex).focus();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    $(this).closest('.flavor-ml-switcher-dropdown')
                        .find('.flavor-ml-dropdown-toggle')
                        .attr('aria-expanded', 'false')
                        .focus();
                }
            });
        },

        bindSelects: function() {
            // Handle native select change
            $(document).on('change', '.flavor-ml-select', function() {
                const url = $(this).val();
                if (url) {
                    window.location.href = url;
                }
            });
        },

        // Public API
        getCurrentLanguage: function() {
            return flavorML.currentLang;
        },

        getDefaultLanguage: function() {
            return flavorML.defaultLang;
        },

        switchLanguage: function(lang) {
            const url = this.getLanguageUrl(lang);
            if (url) {
                window.location.href = url;
            }
        },

        getLanguageUrl: function(lang) {
            // Get URL from existing links if possible
            const $link = $('.flavor-ml-lang-link[hreflang="' + lang + '"]').first();
            if ($link.length) {
                return $link.attr('href');
            }

            // Otherwise construct it
            const currentUrl = window.location.href;
            const urlObj = new URL(currentUrl);

            // Remove existing lang parameter
            urlObj.searchParams.delete('lang');

            // Add new lang parameter
            if (lang !== flavorML.defaultLang) {
                urlObj.searchParams.set('lang', lang);
            }

            return urlObj.toString();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        FlavorML.init();
    });

    // Expose to global scope
    window.FlavorML = FlavorML;

})(jQuery);
