(function() {
    'use strict';

    function updateSelectorLoadingState() {
        var selector = document.querySelector('#wp-admin-bar-flavor-vista-selector > .ab-item');
        if (!selector || typeof flavorAdminMenuManager === 'undefined') {
            return;
        }

        selector.innerHTML = '<span class="flavor-vista-icono">⏳</span> ' + flavorAdminMenuManager.i18n.switching;
    }

    window.flavorCambiarVista = function(vista) {
        if (!vista || typeof flavorAdminMenuManager === 'undefined') {
            return;
        }

        updateSelectorLoadingState();

        var xhr = new XMLHttpRequest();
        xhr.open('POST', flavorAdminMenuManager.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status !== 200) {
                return;
            }

            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success && response.data && response.data.reload) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error parsing response', error);
            }
        };
        xhr.send(
            'action=flavor_cambiar_vista_admin' +
            '&nonce=' + encodeURIComponent(flavorAdminMenuManager.nonce) +
            '&vista=' + encodeURIComponent(vista)
        );
    };

    function initConfigVistasForm() {
        if (typeof window.jQuery === 'undefined' || typeof flavorAdminMenuManager === 'undefined') {
            return;
        }

        var $ = window.jQuery;
        var $form = $('#flavor-config-vistas-form');
        if (!$form.length) {
            return;
        }

        var $status = $('.flavor-save-status');
        var defaultMenus = Array.isArray(flavorAdminMenuManager.defaultGestorMenus)
            ? flavorAdminMenuManager.defaultGestorMenus
            : [];

        $form.on('submit', function(e) {
            e.preventDefault();

            var menus = [];
            $('input[name="menus_gestor[]"]:checked').each(function() {
                menus.push($(this).val());
            });

            $.ajax({
                url: flavorAdminMenuManager.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_guardar_config_vistas',
                    nonce: $('#flavor_config_vistas_nonce').val(),
                    menus: menus
                },
                beforeSend: function() {
                    $form.find('button[type="submit"]')
                        .prop('disabled', true)
                        .text(flavorAdminMenuManager.i18n.saving);
                },
                success: function(response) {
                    if (response.success) {
                        $status.stop(true, true).fadeIn().delay(2000).fadeOut();
                    } else {
                        window.alert((response.data && response.data.message) || flavorAdminMenuManager.i18n.saveError);
                    }
                },
                error: function() {
                    window.alert(flavorAdminMenuManager.i18n.connectionError);
                },
                complete: function() {
                    $form.find('button[type="submit"]')
                        .prop('disabled', false)
                        .html('<span class="dashicons dashicons-saved flavor-config-vistas-actions__icon"></span> ' + flavorAdminMenuManager.i18n.saveConfig);
                }
            });
        });

        $('#flavor-reset-default').on('click', function() {
            if (!window.confirm(flavorAdminMenuManager.i18n.restoreDefaultsConfirm)) {
                return;
            }

            $('input[name="menus_gestor[]"]').each(function() {
                $(this).prop('checked', defaultMenus.indexOf($(this).val()) !== -1);
            });

            $form.trigger('submit');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConfigVistasForm);
    } else {
        initConfigVistasForm();
    }
})();
