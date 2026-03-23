/**
 * Flavor Platform - License Admin JS
 * @since 3.2.0
 */

(function($) {
    'use strict';

    var FlavorLicenseAdmin = {
        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
            this.formatLicenseInput();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            var self = this;

            // Activar licencia
            $('#flavor-activate-license-form').on('submit', function(e) {
                e.preventDefault();
                self.activateLicense();
            });

            // Desactivar licencia
            $('#flavor-deactivate-license').on('click', function(e) {
                e.preventDefault();
                self.deactivateLicense();
            });

            // Verificar licencia
            $('#flavor-verify-license').on('click', function(e) {
                e.preventDefault();
                self.verifyLicense();
            });

            // Formatear input de licencia mientras se escribe
            $('#license-key').on('input', function() {
                self.formatLicenseKeyInput(this);
            });
        },

        /**
         * Formatea el input de licencia mientras se escribe
         */
        formatLicenseInput: function() {
            var input = document.getElementById('license-key');
            if (!input) return;

            input.addEventListener('keyup', function(e) {
                // Solo formatear si no son teclas de control
                if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'Tab') {
                    return;
                }

                var value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                var formatted = '';

                for (var i = 0; i < value.length && i < 16; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formatted += '-';
                    }
                    formatted += value[i];
                }

                this.value = formatted;
            });
        },

        /**
         * Formatea el valor del input de licencia
         * @param {HTMLElement} input
         */
        formatLicenseKeyInput: function(input) {
            var value = input.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
            var clean = value.replace(/-/g, '');

            if (clean.length > 16) {
                clean = clean.substr(0, 16);
            }

            var formatted = '';
            for (var i = 0; i < clean.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += clean[i];
            }

            input.value = formatted;
        },

        /**
         * Activa una licencia
         */
        activateLicense: function() {
            var self = this;
            var $button = $('#flavor-activate-license');
            var $message = $('#license-message');
            var licenseKey = $('#license-key').val().trim();

            if (!licenseKey) {
                this.showMessage($message, flavorLicense.i18n.error + ': Introduce una clave de licencia', 'error');
                return;
            }

            // Validar formato básico
            if (!/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(licenseKey)) {
                this.showMessage($message, flavorLicense.i18n.error + ': Formato inválido. Usa XXXX-XXXX-XXXX-XXXX', 'error');
                return;
            }

            this.setButtonLoading($button, flavorLicense.i18n.activating);

            $.ajax({
                url: flavorLicense.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_platform_activate_license',
                    nonce: flavorLicense.nonce,
                    license_key: licenseKey
                },
                success: function(response) {
                    self.resetButton($button, 'Activar Licencia');

                    if (response.success) {
                        self.showMessage($message, response.data.message, 'success');

                        // Recargar página tras 1.5s para mostrar el nuevo estado
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        self.showMessage($message, response.data || flavorLicense.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.resetButton($button, 'Activar Licencia');
                    self.showMessage($message, flavorLicense.i18n.error + ': Error de conexión', 'error');
                }
            });
        },

        /**
         * Desactiva la licencia
         */
        deactivateLicense: function() {
            var self = this;

            if (!confirm(flavorLicense.i18n.confirmDeactivate)) {
                return;
            }

            var $button = $('#flavor-deactivate-license');
            this.setButtonLoading($button, flavorLicense.i18n.deactivating);

            $.ajax({
                url: flavorLicense.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_platform_deactivate_license',
                    nonce: flavorLicense.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Recargar página para mostrar el nuevo estado
                        window.location.reload();
                    } else {
                        self.resetButton($button, 'Desactivar licencia');
                        alert(response.data || flavorLicense.i18n.error);
                    }
                },
                error: function() {
                    self.resetButton($button, 'Desactivar licencia');
                    alert(flavorLicense.i18n.error + ': Error de conexión');
                }
            });
        },

        /**
         * Verifica la licencia con el servidor
         */
        verifyLicense: function() {
            var self = this;
            var $button = $('#flavor-verify-license');

            this.setButtonLoading($button, flavorLicense.i18n.verifying);

            $.ajax({
                url: flavorLicense.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_platform_verify_license',
                    nonce: flavorLicense.nonce
                },
                success: function(response) {
                    self.resetButton($button, 'Verificar');

                    if (response.success) {
                        if (response.data.active) {
                            alert(flavorLicense.i18n.success + ': Licencia verificada correctamente');
                        } else {
                            alert(flavorLicense.i18n.error + ': La licencia no está activa');
                            window.location.reload();
                        }
                    } else {
                        alert(response.data || flavorLicense.i18n.error);
                    }
                },
                error: function() {
                    self.resetButton($button, 'Verificar');
                    alert(flavorLicense.i18n.error + ': Error de conexión');
                }
            });
        },

        /**
         * Muestra un mensaje en el contenedor
         * @param {jQuery} $container
         * @param {string} message
         * @param {string} type - 'success' o 'error'
         */
        showMessage: function($container, message, type) {
            $container
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .show();
        },

        /**
         * Pone un botón en estado de carga
         * @param {jQuery} $button
         * @param {string} text
         */
        setButtonLoading: function($button, text) {
            $button
                .addClass('loading')
                .data('original-text', $button.text())
                .prop('disabled', true);
        },

        /**
         * Restaura un botón a su estado normal
         * @param {jQuery} $button
         * @param {string} text
         */
        resetButton: function($button, text) {
            $button
                .removeClass('loading')
                .prop('disabled', false);

            if (text) {
                // Preservar el SVG si existe
                var $svg = $button.find('svg').clone();
                $button.text(text);
                if ($svg.length) {
                    $button.prepend($svg).prepend(' ');
                }
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorLicenseAdmin.init();
    });

})(jQuery);
