/**
 * Herramientas IA - JavaScript común
 *
 * Funciones compartidas para todas las herramientas de IA.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

(function($) {
    'use strict';

    // Namespace global
    window.FlavorAITools = window.FlavorAITools || {};

    /**
     * Configuración global
     */
    FlavorAITools.config = {
        ajaxUrl: flavorAITools?.ajaxUrl || ajaxurl,
        nonces: flavorAITools?.nonces || {},
        currentModule: flavorAITools?.currentModule || 'general',
        isConfigured: flavorAITools?.isConfigured || false,
        i18n: flavorAITools?.i18n || {}
    };

    /**
     * Utilidades
     */
    FlavorAITools.utils = {
        /**
         * Hacer petición AJAX
         */
        ajax: function(action, data, nonce) {
            return $.ajax({
                url: FlavorAITools.config.ajaxUrl,
                type: 'POST',
                data: Object.assign({
                    action: action,
                    nonce: nonce || FlavorAITools.config.nonces.chat
                }, data)
            });
        },

        /**
         * Mostrar notificación toast
         */
        toast: function(message, type) {
            type = type || 'info';

            var $toast = $('<div class="flavor-ai-toast flavor-ai-toast--' + type + '">')
                .html('<span class="dashicons dashicons-' + this.getToastIcon(type) + '"></span>' + message);

            $('body').append($toast);

            setTimeout(function() {
                $toast.addClass('show');
            }, 10);

            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        },

        getToastIcon: function(type) {
            var icons = {
                success: 'yes-alt',
                error: 'warning',
                warning: 'info',
                info: 'info-outline'
            };
            return icons[type] || 'info-outline';
        },

        /**
         * Escapar HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Copiar al portapapeles
         */
        copyToClipboard: function(text) {
            var self = this;

            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).then(function() {
                    self.toast(FlavorAITools.config.i18n.copySuccess || 'Copiado', 'success');
                });
            }

            // Fallback para navegadores antiguos
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                document.execCommand('copy');
                self.toast(FlavorAITools.config.i18n.copySuccess || 'Copiado', 'success');
            } catch (err) {
                console.error('Error al copiar:', err);
            }

            document.body.removeChild(textarea);
        },

        /**
         * Formatear fecha
         */
        formatDate: function(date) {
            var d = new Date(date);
            return d.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },

        /**
         * Debounce
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };

    /**
     * Verificar si IA está configurada
     */
    FlavorAITools.checkConfiguration = function() {
        if (!this.config.isConfigured) {
            this.utils.toast(
                this.config.i18n.aiNotConfigured || 'La IA no está configurada',
                'warning'
            );
            return false;
        }
        return true;
    };

    /**
     * Añadir botones IA a textareas
     */
    FlavorAITools.enhanceTextareas = function() {
        if (!this.config.isConfigured) return;

        // Buscar textareas que deban tener botones IA
        var selectors = [
            '.flavor-module-dashboard textarea.large-text',
            '#post textarea#content',
            '.flavor-ai-enhanced-textarea',
            '.flavor-ai-content-target',
            'textarea[data-ai-enabled="true"]'
        ];

        $(selectors.join(', ')).each(function() {
            var $textarea = $(this);

            // No añadir si ya tiene botones o está dentro de wrapper ya procesado
            if ($textarea.closest('.flavor-ai-textarea-wrapper').find('.flavor-ai-textarea-actions').length) {
                return;
            }

            // Si ya está en wrapper, solo añadir acciones
            var $wrapper = $textarea.closest('.flavor-ai-textarea-wrapper');
            if (!$wrapper.length) {
                // Crear wrapper
                $wrapper = $('<div class="flavor-ai-textarea-wrapper">');
                $textarea.wrap($wrapper);
                $wrapper = $textarea.parent();
            }

            // Obtener contexto del textarea si está definido
            var contentType = $textarea.data('content-type') || 'general';
            var context = $textarea.data('context') || '';

            // Añadir acciones
            var $actions = $('<div class="flavor-ai-textarea-actions">')
                .append(
                    '<button type="button" class="flavor-ai-btn flavor-ai-btn--small flavor-ai-generate-btn" ' +
                    'title="' + (FlavorAITools.config.i18n.generateWithAI || 'Generar con IA') + '" ' +
                    'data-content-type="' + contentType + '" data-context="' + context + '">' +
                    '<span class="dashicons dashicons-admin-generic"></span>' +
                    '</button>'
                )
                .append(
                    '<button type="button" class="flavor-ai-btn flavor-ai-btn--small flavor-ai-btn--outline flavor-ai-translate-btn" title="' +
                    (FlavorAITools.config.i18n.translateTo || 'Traducir') + '">' +
                    '<span class="dashicons dashicons-translation"></span>' +
                    '<div class="flavor-ai-translate-dropdown">' +
                    '<div class="flavor-ai-translate-option" data-lang="es"><span class="flag">🇪🇸</span> Español</div>' +
                    '<div class="flavor-ai-translate-option" data-lang="eu"><span class="flag">🏴</span> Euskera</div>' +
                    '<div class="flavor-ai-translate-option" data-lang="ca"><span class="flag">🏴</span> Catalán</div>' +
                    '<div class="flavor-ai-translate-option" data-lang="en"><span class="flag">🇬🇧</span> English</div>' +
                    '<div class="flavor-ai-translate-option" data-lang="fr"><span class="flag">🇫🇷</span> Français</div>' +
                    '</div>' +
                    '</button>'
                );

            $wrapper.append($actions);
        });
    };

    /**
     * Inicialización
     */
    FlavorAITools.init = function() {
        var self = this;

        // Añadir estilos para toasts
        if (!$('#flavor-ai-toast-styles').length) {
            $('head').append(
                '<style id="flavor-ai-toast-styles">' +
                '.flavor-ai-toast {' +
                '    position: fixed;' +
                '    bottom: 20px;' +
                '    right: 20px;' +
                '    padding: 12px 20px;' +
                '    background: #333;' +
                '    color: white;' +
                '    border-radius: 8px;' +
                '    font-size: 13px;' +
                '    display: flex;' +
                '    align-items: center;' +
                '    gap: 10px;' +
                '    transform: translateY(100px);' +
                '    opacity: 0;' +
                '    transition: all 0.3s ease;' +
                '    z-index: 999999;' +
                '    box-shadow: 0 4px 12px rgba(0,0,0,0.3);' +
                '}' +
                '.flavor-ai-toast.show {' +
                '    transform: translateY(0);' +
                '    opacity: 1;' +
                '}' +
                '.flavor-ai-toast--success { background: #10b981; }' +
                '.flavor-ai-toast--error { background: #ef4444; }' +
                '.flavor-ai-toast--warning { background: #f59e0b; }' +
                '.flavor-ai-toast--info { background: #3b82f6; }' +
                '</style>'
            );
        }

        // Mejorar textareas
        $(document).ready(function() {
            self.enhanceTextareas();
        });

        // Evento para abrir modal de generación
        $(document).on('click', '.flavor-ai-generate-btn', function(e) {
            e.preventDefault();
            var $textarea = $(this).closest('.flavor-ai-textarea-wrapper').find('textarea');
            self.openContentGenerator($textarea);
        });

        // Evento para traducir
        $(document).on('click', '.flavor-ai-translate-btn', function(e) {
            e.preventDefault();
            var $textarea = $(this).closest('.flavor-ai-textarea-wrapper').find('textarea');
            self.openTranslator($textarea);
        });
    };

    /**
     * Abrir generador de contenido
     */
    FlavorAITools.openContentGenerator = function($textarea) {
        if (!this.checkConfiguration()) return;

        // Guardar referencia al textarea
        this.activeTextarea = $textarea;

        // Mostrar modal
        var $modal = $('#flavor-ai-content-modal');
        if ($modal.length) {
            // Prellenar contexto si hay texto
            var existingText = $textarea.val();
            if (existingText) {
                $('#ai-content-context').val(existingText);
            }

            $modal.fadeIn(200);
        }
    };

    /**
     * Abrir traductor
     */
    FlavorAITools.openTranslator = function($textarea) {
        if (!this.checkConfiguration()) return;

        var text = $textarea.val();
        if (!text.trim()) {
            this.utils.toast('No hay texto para traducir', 'warning');
            return;
        }

        // Guardar referencia
        this.activeTextarea = $textarea;

        // Mostrar dropdown de idiomas (ya incluido en el HTML)
        var $btn = $textarea.closest('.flavor-ai-textarea-wrapper').find('.flavor-ai-translate-btn');
        var $dropdown = $btn.find('.flavor-ai-translate-dropdown');

        $dropdown.toggleClass('active');
    };

    /**
     * Hub de herramientas - cargar estadísticas
     */
    FlavorAITools.loadHubStats = function() {
        // Estadísticas mock (en producción vendría de AJAX)
        $('#ai-requests-today').text('12');
        $('#ai-requests-week').text('78');
        $('#ai-content-generated').text('45');
        $('#ai-time-saved').text('~3h');
    };

    // Inicializar cuando esté listo
    $(document).ready(function() {
        FlavorAITools.init();

        // Si estamos en el hub, cargar estadísticas
        if ($('.flavor-ai-hub').length) {
            FlavorAITools.loadHubStats();
        }
    });

})(jQuery);
