/**
 * JavaScript del módulo Seguimiento de Denuncias
 */
(function($) {
    'use strict';

    const SeguimientoDenuncias = {
        init: function() {
            this.bindEvents();
            this.initTimeline();
            this.initFiltros();
            this.checkPlazos();
        },

        bindEvents: function() {
            // Formulario de nueva denuncia
            $(document).on('submit', '.flavor-denuncia-form', this.handleSubmitDenuncia.bind(this));

            // Añadir evento a timeline
            $(document).on('submit', '.flavor-evento-form', this.handleAddEvento.bind(this));

            // Cambiar estado
            $(document).on('change', '.flavor-denuncia-estado-select', this.handleCambiarEstado.bind(this));

            // Filtros
            $(document).on('change', '.flavor-denuncias-filtro', this.handleFiltrar.bind(this));

            // Recordatorio de plazo
            $(document).on('click', '.flavor-btn-recordatorio', this.handleRecordatorio.bind(this));

            // Expandir/colapsar timeline
            $(document).on('click', '.flavor-timeline-toggle', this.toggleTimeline.bind(this));

            // Adjuntar documento
            $(document).on('click', '.flavor-btn-adjuntar', this.handleAdjuntar.bind(this));

            // Ver plantilla
            $(document).on('click', '.flavor-btn-usar-plantilla', this.handleUsarPlantilla.bind(this));
        },

        initTimeline: function() {
            // Animar entrada de elementos del timeline
            $('.flavor-timeline-item').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateX(-20px)'
                }).delay(index * 100).animate({
                    'opacity': '1'
                }, 300).css('transform', 'translateX(0)');
            });
        },

        initFiltros: function() {
            // Inicializar filtros desde URL
            const urlParams = new URLSearchParams(window.location.search);
            const estado = urlParams.get('estado');
            const organismo = urlParams.get('organismo');

            if (estado) {
                $('.flavor-filtro-estado').val(estado);
            }
            if (organismo) {
                $('.flavor-filtro-organismo').val(organismo);
            }
        },

        checkPlazos: function() {
            // Verificar plazos y mostrar alertas
            $('.flavor-denuncia-card').each(function() {
                const $card = $(this);
                const diasRestantes = parseInt($card.data('dias-restantes'));

                if (diasRestantes !== undefined && diasRestantes <= 3) {
                    $card.addClass('flavor-plazo-urgente');

                    if (diasRestantes <= 0) {
                        $card.find('.flavor-denuncia-plazo')
                            .addClass('flavor-plazo-vencido')
                            .prepend('<span class="dashicons dashicons-warning"></span> ');
                    }
                }
            });
        },

        handleSubmitDenuncia: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submitBtn = $form.find('[type="submit"]');

            // Validar campos requeridos
            const titulo = $form.find('[name="titulo"]').val().trim();
            const organismo = $form.find('[name="organismo"]').val().trim();
            const fechaPresentacion = $form.find('[name="fecha_presentacion"]').val();

            if (!titulo || !organismo || !fechaPresentacion) {
                this.showNotice('Por favor, completa todos los campos obligatorios', 'error');
                return;
            }

            $submitBtn.prop('disabled', true).text('Guardando...');

            const formData = new FormData($form[0]);
            formData.append('action', 'flavor_seguimiento_denuncias_crear');
            formData.append('nonce', flavorSeguimientoDenuncias.nonce);

            $.ajax({
                url: flavorSeguimientoDenuncias.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('Denuncia registrada correctamente', 'success');
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        this.showNotice(response.data.message || 'Error al guardar', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Error de conexión', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text('Registrar denuncia');
                }
            });
        },

        handleAddEvento: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const denunciaId = $form.data('denuncia-id');

            const formData = {
                action: 'flavor_seguimiento_denuncias_add_evento',
                nonce: flavorSeguimientoDenuncias.nonce,
                denuncia_id: denunciaId,
                tipo: $form.find('[name="tipo_evento"]').val(),
                titulo: $form.find('[name="titulo_evento"]').val(),
                descripcion: $form.find('[name="descripcion_evento"]').val(),
                fecha: $form.find('[name="fecha_evento"]').val()
            };

            $.post(flavorSeguimientoDenuncias.ajaxUrl, formData, (response) => {
                if (response.success) {
                    this.showNotice('Evento añadido al timeline', 'success');
                    this.appendTimelineItem(response.data.evento);
                    $form[0].reset();
                } else {
                    this.showNotice(response.data.message || 'Error al añadir evento', 'error');
                }
            });
        },

        appendTimelineItem: function(evento) {
            const $timeline = $('.flavor-timeline');
            const itemHtml = `
                <div class="flavor-timeline-item flavor-timeline-${evento.tipo}" style="opacity: 0;">
                    <div class="flavor-timeline-fecha">${evento.fecha_formatted}</div>
                    <div class="flavor-timeline-titulo">${this.escapeHtml(evento.titulo)}</div>
                    <div class="flavor-timeline-descripcion">${this.escapeHtml(evento.descripcion)}</div>
                </div>
            `;

            const $item = $(itemHtml).prependTo($timeline);
            $item.animate({ opacity: 1 }, 300);
        },

        handleCambiarEstado: function(e) {
            const $select = $(e.currentTarget);
            const denunciaId = $select.data('denuncia-id');
            const nuevoEstado = $select.val();
            const estadoAnterior = $select.data('estado-anterior');

            if (nuevoEstado === estadoAnterior) return;

            // Confirmar cambio de estado
            if (!confirm(`¿Cambiar estado a "${this.getEstadoLabel(nuevoEstado)}"?`)) {
                $select.val(estadoAnterior);
                return;
            }

            $.post(flavorSeguimientoDenuncias.ajaxUrl, {
                action: 'flavor_seguimiento_denuncias_cambiar_estado',
                nonce: flavorSeguimientoDenuncias.nonce,
                denuncia_id: denunciaId,
                estado: nuevoEstado
            }, (response) => {
                if (response.success) {
                    this.showNotice('Estado actualizado', 'success');
                    $select.data('estado-anterior', nuevoEstado);

                    // Actualizar clase visual
                    const $card = $select.closest('.flavor-denuncia-card');
                    $card.removeClass(function(index, className) {
                        return (className.match(/(^|\s)flavor-estado-\S+/g) || []).join(' ');
                    }).addClass('flavor-estado-' + nuevoEstado);
                } else {
                    this.showNotice('Error al cambiar estado', 'error');
                    $select.val(estadoAnterior);
                }
            });
        },

        getEstadoLabel: function(estado) {
            const estados = {
                'presentada': 'Presentada',
                'en_tramite': 'En trámite',
                'requerimiento': 'Requerimiento',
                'silencio': 'Silencio administrativo',
                'resuelta_favorable': 'Resuelta favorable',
                'resuelta_desfavorable': 'Resuelta desfavorable',
                'recurrida': 'Recurrida'
            };
            return estados[estado] || estado;
        },

        handleFiltrar: function(e) {
            const $filtros = $('.flavor-denuncias-filtro');
            const params = new URLSearchParams();

            $filtros.each(function() {
                const valor = $(this).val();
                const nombre = $(this).attr('name');
                if (valor) {
                    params.set(nombre, valor);
                }
            });

            // Actualizar URL y recargar
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        },

        handleRecordatorio: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const denunciaId = $btn.data('denuncia-id');

            $.post(flavorSeguimientoDenuncias.ajaxUrl, {
                action: 'flavor_seguimiento_denuncias_recordatorio',
                nonce: flavorSeguimientoDenuncias.nonce,
                denuncia_id: denunciaId
            }, (response) => {
                if (response.success) {
                    this.showNotice('Recordatorio programado', 'success');
                    $btn.prop('disabled', true).text('Recordatorio activo');
                } else {
                    this.showNotice(response.data.message || 'Error', 'error');
                }
            });
        },

        toggleTimeline: function(e) {
            e.preventDefault();
            const $toggle = $(e.currentTarget);
            const $timeline = $toggle.siblings('.flavor-timeline');

            $timeline.slideToggle(300);
            $toggle.toggleClass('collapsed');
        },

        handleAdjuntar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const $input = $btn.siblings('input[type="file"]');
            $input.trigger('click');
        },

        handleUsarPlantilla: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const plantillaId = $btn.data('plantilla-id');

            $.post(flavorSeguimientoDenuncias.ajaxUrl, {
                action: 'flavor_seguimiento_denuncias_get_plantilla',
                nonce: flavorSeguimientoDenuncias.nonce,
                plantilla_id: plantillaId
            }, (response) => {
                if (response.success) {
                    // Rellenar formulario con datos de la plantilla
                    const plantilla = response.data.plantilla;
                    $('[name="titulo"]').val(plantilla.titulo);
                    $('[name="organismo"]').val(plantilla.organismo_destino);
                    $('[name="descripcion"]').val(plantilla.contenido);

                    this.showNotice('Plantilla aplicada', 'success');
                }
            });
        },

        showNotice: function(message, type) {
            const $notice = $(`
                <div class="flavor-notice flavor-notice-${type}">
                    ${this.escapeHtml(message)}
                </div>
            `);

            $('.flavor-notices-container').append($notice);

            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($('.flavor-seguimiento-denuncias').length || $('.flavor-denuncia-form').length) {
            SeguimientoDenuncias.init();
        }
    });

})(jQuery);
