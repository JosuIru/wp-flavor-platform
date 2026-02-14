/**
 * JavaScript del módulo Comunidades - Frontend
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorComunidades = {
        config: window.flavorComunidadesConfig || {},

        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initUpload();
            this.loadFeed();
        },

        bindEvents: function() {
            // Unirse a comunidad
            $(document).on('click', '.flavor-com-btn-unirse', this.handleUnirse.bind(this));

            // Salir de comunidad
            $(document).on('click', '.flavor-com-btn-salir', this.handleSalir.bind(this));

            // Crear comunidad
            $(document).on('submit', '#flavor-com-form-crear', this.handleCrear.bind(this));

            // Publicar
            $(document).on('submit', '#flavor-com-form-publicar', this.handlePublicar.bind(this));

            // Tabs
            $(document).on('click', '.flavor-com-tab', this.handleTab.bind(this));

            // Filtros
            $(document).on('change', '#com-filtro-categoria, #com-filtro-tipo', this.handleFiltrar.bind(this));
        },

        handleUnirse: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const comunidadId = $btn.data('comunidad');

            if (!confirm(this.config.strings?.confirmUnirse || '¿Deseas unirte?')) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-com-spinner"></span>');

            this.ajax('comunidades_unirse', { comunidad_id: comunidadId })
                .done(function(res) {
                    if (res.success) {
                        $btn.html('<span class="dashicons dashicons-yes"></span> ' +
                            (res.data.mensaje || 'Unido'));
                        FlavorComunidades.toast(res.data.mensaje || 'Te has unido', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        FlavorComunidades.toast(res.data?.message || 'Error', 'error');
                        $btn.prop('disabled', false)
                            .html('<span class="dashicons dashicons-plus"></span> Unirse');
                    }
                })
                .fail(function() {
                    FlavorComunidades.toast('Error de conexión', 'error');
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-plus"></span> Unirse');
                });
        },

        handleSalir: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const comunidadId = $btn.data('comunidad');

            if (!confirm(this.config.strings?.confirmSalir || '¿Estás seguro?')) {
                return;
            }

            $btn.prop('disabled', true);

            this.ajax('comunidades_salir', { comunidad_id: comunidadId })
                .done(function(res) {
                    if (res.success) {
                        FlavorComunidades.toast(res.data.mensaje || 'Has abandonado la comunidad', 'success');
                        setTimeout(() => window.location.href = '/comunidades/', 1500);
                    } else {
                        FlavorComunidades.toast(res.data?.message || 'Error', 'error');
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function() {
                    FlavorComunidades.toast('Error de conexión', 'error');
                    $btn.prop('disabled', false);
                });
        },

        handleCrear: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('#com-crear-btn');

            if (!this.validarFormulario($form)) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-com-spinner"></span> Creando...');

            this.ajax('comunidades_crear', {
                nombre: $form.find('#com-nombre').val(),
                descripcion: $form.find('#com-descripcion').val(),
                categoria: $form.find('#com-categoria').val(),
                tipo: $form.find('#com-tipo').val()
            })
            .done(function(res) {
                if (res.success) {
                    $form.hide();
                    $('#com-mensaje-exito').show();
                    $('#com-ir-comunidad').attr('href',
                        '/comunidades/?comunidad=' + res.data.comunidad_id);
                } else {
                    FlavorComunidades.toast(res.data?.message || 'Error', 'error');
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-groups"></span> Crear comunidad');
                }
            })
            .fail(function() {
                FlavorComunidades.toast('Error de conexión', 'error');
                $btn.prop('disabled', false)
                    .html('<span class="dashicons dashicons-groups"></span> Crear comunidad');
            });
        },

        handlePublicar: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $textarea = $form.find('textarea');
            const contenido = $textarea.val().trim();
            const comunidadId = $('.flavor-com-detalle').data('comunidad');

            if (!contenido) {
                this.toast('Escribe algo para publicar', 'error');
                return;
            }

            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="flavor-com-spinner"></span>');

            this.ajax('comunidades_publicar', {
                comunidad_id: comunidadId,
                contenido: contenido
            })
            .done(function(res) {
                if (res.success) {
                    $textarea.val('');
                    FlavorComunidades.toast('Publicación creada', 'success');
                    FlavorComunidades.loadFeed();
                } else {
                    FlavorComunidades.toast(res.data?.message || 'Error', 'error');
                }
            })
            .fail(function() {
                FlavorComunidades.toast('Error de conexión', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html('Publicar');
            });
        },

        handleTab: function(e) {
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');

            $('.flavor-com-tab').removeClass('active');
            $tab.addClass('active');

            $('.flavor-com-panel').removeClass('active');
            $('#panel-' + tabId).addClass('active');
        },

        handleFiltrar: function() {
            // Implementar filtrado
            this.toast('Filtros actualizados', 'info');
        },

        initTabs: function() {
            // Ya manejado por eventos
        },

        initUpload: function() {
            const $area = $('#com-upload-area');
            const $input = $('#com-imagen');

            if (!$area.length) return;

            $area.on('click', function() {
                $input.click();
            });

            $input.on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#com-preview')
                            .html('<img src="' + e.target.result + '" style="max-width:100%;max-height:200px;border-radius:8px;">')
                            .show();
                        $('.flavor-com-upload-placeholder').hide();
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        loadFeed: function() {
            const $feed = $('#com-feed');
            const comunidadId = $('.flavor-com-detalle').data('comunidad');

            if (!$feed.length || !comunidadId) return;

            // Por ahora mostrar mensaje de ejemplo
            setTimeout(function() {
                $feed.html(`
                    <div class="flavor-com-sin-actividad">
                        <span class="dashicons dashicons-format-chat"></span>
                        <p>No hay actividad reciente. ¡Sé el primero en publicar!</p>
                    </div>
                `);
            }, 500);
        },

        validarFormulario: function($form) {
            let valido = true;

            $form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    valido = false;
                } else {
                    $(this).removeClass('error');
                }
            });

            if (!valido) {
                this.toast('Completa todos los campos obligatorios', 'error');
            }

            return valido;
        },

        ajax: function(action, data) {
            data = data || {};
            data.action = action;
            data.nonce = this.config.nonce;

            return $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                dataType: 'json'
            });
        },

        toast: function(mensaje, tipo) {
            tipo = tipo || 'info';

            const $toast = $(`
                <div class="flavor-com-toast flavor-com-toast-${tipo}">
                    ${this.escapeHtml(mensaje)}
                </div>
            `);

            $('body').append($toast);

            setTimeout(() => $toast.addClass('show'), 10);

            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // CSS para toast
    $('<style>')
        .text(`
            .flavor-com-toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 14px 20px;
                border-radius: 8px;
                color: #fff;
                font-size: 14px;
                z-index: 100001;
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.3s, transform 0.3s;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .flavor-com-toast.show {
                opacity: 1;
                transform: translateY(0);
            }
            .flavor-com-toast-success { background: #10b981; }
            .flavor-com-toast-error { background: #ef4444; }
            .flavor-com-toast-info { background: #6366f1; }
            .flavor-com-input.error,
            .flavor-com-select.error,
            .flavor-com-textarea.error {
                border-color: #ef4444 !important;
            }
        `)
        .appendTo('head');

    $(document).ready(function() {
        FlavorComunidades.init();
    });

})(jQuery);
