/**
 * JavaScript del módulo Colectivos - Frontend
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorColectivos = {
        config: window.flavorColectivosConfig || {},

        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initModals();
        },

        bindEvents: function() {
            // Unirse a colectivo
            $(document).on('click', '.flavor-col-btn-unirse', this.handleUnirse.bind(this));

            // Abandonar colectivo
            $(document).on('click', '.flavor-col-btn-abandonar', this.handleAbandonar.bind(this));

            // Crear colectivo
            $(document).on('submit', '#flavor-col-form-crear', this.handleCrear.bind(this));

            // Tabs
            $(document).on('click', '.flavor-col-tab', this.handleTab.bind(this));

            // Filtros
            $(document).on('change', '#col-filtro-tipo', this.handleFiltrarTipo.bind(this));
            $(document).on('input', '#col-buscar', this.debounce(this.handleBuscar.bind(this), 300));

            // Nuevo proyecto
            $(document).on('click', '#col-nuevo-proyecto, #col-crear-proyecto', this.abrirModalProyecto.bind(this));
            $(document).on('submit', '#form-nuevo-proyecto', this.handleCrearProyecto.bind(this));

            // Nueva asamblea
            $(document).on('click', '#col-nueva-asamblea, #col-convocar-asamblea', this.abrirModalAsamblea.bind(this));
            $(document).on('submit', '#form-nueva-asamblea', this.handleConvocarAsamblea.bind(this));

            // Confirmar asistencia
            $(document).on('click', '.flavor-col-btn-confirmar, .flavor-col-btn-confirmar-asistencia', this.handleConfirmarAsistencia.bind(this));

            // Actualizar progreso proyecto
            $(document).on('click', '.flavor-col-actualizar-progreso', this.handleActualizarProgreso.bind(this));

            // Cerrar modales
            $(document).on('click', '.flavor-col-modal-close, .flavor-col-modal-cancelar', this.cerrarModales.bind(this));
            $(document).on('click', '.flavor-col-modal', function(e) {
                if ($(e.target).hasClass('flavor-col-modal')) {
                    FlavorColectivos.cerrarModales();
                }
            });
        },

        handleUnirse: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const colectivoId = $btn.data('colectivo');

            if (!confirm(this.config.strings?.confirmUnirse || '¿Deseas unirte a este colectivo?')) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-col-spinner"></span>');

            this.ajax('colectivos_unirse', { colectivo_id: colectivoId })
                .done(function(res) {
                    if (res.success) {
                        $btn.html('<span class="dashicons dashicons-yes"></span> ' +
                            (res.mensaje || 'Solicitud enviada'));
                        FlavorColectivos.toast(res.mensaje || 'Solicitud enviada', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        FlavorColectivos.toast(res.error || 'Error', 'error');
                        $btn.prop('disabled', false)
                            .html('<span class="dashicons dashicons-plus"></span> Solicitar unirse');
                    }
                })
                .fail(function() {
                    FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error de conexión', 'error');
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-plus"></span> Solicitar unirse');
                });
        },

        handleAbandonar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const colectivoId = $btn.data('colectivo');

            if (!confirm(this.config.strings?.confirmAbandonar || '¿Estás seguro de que quieres abandonar este colectivo?')) {
                return;
            }

            $btn.prop('disabled', true);

            this.ajax('colectivos_abandonar', { colectivo_id: colectivoId })
                .done(function(res) {
                    if (res.success) {
                        FlavorColectivos.toast(res.mensaje || 'Has abandonado el colectivo', 'success');
                        setTimeout(() => window.location.href = '/colectivos/', 1500);
                    } else {
                        FlavorColectivos.toast(res.error || 'Error', 'error');
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function() {
                    FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error de conexión', 'error');
                    $btn.prop('disabled', false);
                });
        },

        handleCrear: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('#col-crear-btn');

            if (!this.validarFormulario($form)) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-col-spinner"></span> Creando...');

            const datos = {
                nombre: $form.find('#col-nombre').val(),
                descripcion: $form.find('#col-descripcion').val(),
                tipo: $form.find('#col-tipo').val(),
                sector: $form.find('#col-sector').val(),
                email_contacto: $form.find('#col-email').val(),
                telefono: $form.find('#col-telefono').val(),
                direccion: $form.find('#col-direccion').val(),
                web: $form.find('#col-web').val()
            };

            this.ajax('colectivos_crear', datos)
                .done(function(res) {
                    if (res.success) {
                        $form.hide();
                        $('#col-mensaje-exito').show();
                        $('#col-ir-colectivo').attr('href',
                            '/colectivos/?colectivo=' + res.colectivo_id);
                    } else {
                        FlavorColectivos.toast(res.error || 'Error', 'error');
                        $btn.prop('disabled', false)
                            .html('<span class="dashicons dashicons-networking"></span> Crear colectivo');
                    }
                })
                .fail(function() {
                    FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error de conexión', 'error');
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-networking"></span> Crear colectivo');
                });
        },

        handleTab: function(e) {
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');

            $('.flavor-col-tab').removeClass('active');
            $tab.addClass('active');

            $('.flavor-col-panel').removeClass('active');
            $('#panel-' + tabId).addClass('active');
        },

        initTabs: function() {
            // Activar primer tab si no hay ninguno activo
            if ($('.flavor-col-tab.active').length === 0) {
                $('.flavor-col-tab').first().addClass('active');
                $('.flavor-col-panel').first().addClass('active');
            }
        },

        handleFiltrarTipo: function(e) {
            const tipo = $(e.currentTarget).val();
            const $cards = $('.flavor-col-card');

            if (!tipo) {
                $cards.show();
            } else {
                $cards.hide();
                $cards.filter(function() {
                    return $(this).find('.flavor-col-tipo-' + tipo).length > 0;
                }).show();
            }
        },

        handleBuscar: function(e) {
            const termino = $(e.currentTarget).val().toLowerCase();
            const $cards = $('.flavor-col-card');

            if (!termino) {
                $cards.show();
                return;
            }

            $cards.each(function() {
                const $card = $(this);
                const nombre = $card.find('.flavor-col-card-titulo').text().toLowerCase();
                const sector = $card.find('.flavor-col-sector').text().toLowerCase();

                if (nombre.includes(termino) || sector.includes(termino)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
        },

        // Modales
        initModals: function() {
            // ESC para cerrar modales
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    FlavorColectivos.cerrarModales();
                }
            });
        },

        abrirModalProyecto: function(e) {
            e.preventDefault();
            $('#modal-nuevo-proyecto').show();
            $('#proy-titulo').focus();
        },

        abrirModalAsamblea: function(e) {
            e.preventDefault();
            $('#modal-nueva-asamblea').show();
            $('#asam-titulo').focus();
        },

        cerrarModales: function() {
            $('.flavor-col-modal').hide();
            $('.flavor-col-modal form')[0]?.reset();
        },

        handleCrearProyecto: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            if (!this.validarFormulario($form)) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-col-spinner"></span>');

            const datos = {
                colectivo_id: $form.find('[name="colectivo_id"]').val(),
                titulo: $form.find('#proy-titulo').val(),
                descripcion: $form.find('#proy-descripcion').val(),
                presupuesto: $form.find('#proy-presupuesto').val(),
                fecha_inicio: $form.find('#proy-fecha-inicio').val()
            };

            this.ajax('colectivos_crear_proyecto', datos)
                .done(function(res) {
                    if (res.success) {
                        FlavorColectivos.toast(res.mensaje || 'Proyecto creado', 'success');
                        FlavorColectivos.cerrarModales();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        FlavorColectivos.toast(res.error || 'Error', 'error');
                    }
                })
                .fail(function() {
                    FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Crear proyecto');
                });
        },

        handleConvocarAsamblea: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            if (!this.validarFormulario($form)) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-col-spinner"></span>');

            const datos = {
                colectivo_id: $form.find('[name="colectivo_id"]').val(),
                titulo: $form.find('#asam-titulo').val(),
                tipo: $form.find('#asam-tipo').val(),
                fecha: $form.find('#asam-fecha').val(),
                lugar: $form.find('#asam-lugar').val(),
                orden_del_dia: $form.find('#asam-orden').val()
            };

            this.ajax('colectivos_convocar_asamblea', datos)
                .done(function(res) {
                    if (res.success) {
                        FlavorColectivos.toast(res.mensaje || 'Asamblea convocada', 'success');
                        FlavorColectivos.cerrarModales();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        FlavorColectivos.toast(res.error || 'Error', 'error');
                    }
                })
                .fail(function() {
                    FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Convocar asamblea');
                });
        },

        handleConfirmarAsistencia: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const asambleaId = $btn.data('asamblea');

            if (!confirm(this.config.strings?.confirmAsistencia || '¿Confirmas tu asistencia a esta asamblea?')) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="flavor-col-spinner"></span>');

            this.ajax('colectivos_confirmar_asistencia', { asamblea_id: asambleaId })
                .done(function(res) {
                    if (res.success) {
                        $btn.html('<span class="dashicons dashicons-yes"></span> Confirmado')
                            .removeClass('flavor-col-btn-primary')
                            .addClass('flavor-col-btn-secondary');
                        FlavorColectivos.toast(res.mensaje || 'Asistencia confirmada', 'success');
                    } else {
                        FlavorColectivos.toast(res.error || 'Error', 'error');
                        $btn.prop('disabled', false).html('Confirmar asistencia');
                    }
                })
                .fail(function() {
                    FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error', 'error');
                    $btn.prop('disabled', false).html('Confirmar asistencia');
                });
        },

        handleActualizarProgreso: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const proyectoId = $btn.data('proyecto');
            const progresoActual = parseInt($btn.data('progreso')) || 0;

            const nuevoProgreso = prompt('Nuevo porcentaje de progreso (0-100):', progresoActual);

            if (nuevoProgreso === null) return;

            const progreso = Math.min(100, Math.max(0, parseInt(nuevoProgreso) || 0));
            const colectivoId = $('.flavor-col-detalle, .flavor-col-proyectos').data('colectivo');

            $btn.prop('disabled', true);

            this.ajax('colectivos_actualizar_proyecto', {
                proyecto_id: proyectoId,
                colectivo_id: colectivoId,
                progreso: progreso
            })
            .done(function(res) {
                if (res.success) {
                    FlavorColectivos.toast(res.mensaje || 'Progreso actualizado', 'success');
                    // Actualizar barra de progreso
                    const $card = $btn.closest('.flavor-col-proyecto-card, .flavor-col-proyecto');
                    $card.find('.flavor-col-progreso-fill').css('width', progreso + '%');
                    $card.find('.flavor-col-progreso-valor').text(progreso + '%');
                    $btn.data('progreso', progreso);
                } else {
                    FlavorColectivos.toast(res.error || 'Error', 'error');
                }
            })
            .fail(function() {
                FlavorColectivos.toast(FlavorColectivos.config.strings?.errorConexion || 'Error', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        },

        // Utilidades
        validarFormulario: function($form) {
            let valido = true;

            $form.find('[required]').each(function() {
                const $campo = $(this);
                if (!$campo.val().trim()) {
                    $campo.addClass('error');
                    valido = false;
                } else {
                    $campo.removeClass('error');
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
                <div class="flavor-col-toast flavor-col-toast-${tipo}">
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
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    $(document).ready(function() {
        FlavorColectivos.init();
    });

})(jQuery);
