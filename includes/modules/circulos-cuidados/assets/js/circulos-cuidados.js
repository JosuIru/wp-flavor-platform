/**
 * Círculos de Cuidados - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const CirculosCuidados = {
        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Unirse a círculo
            $(document).on('click', '.cc-btn-unirse', this.handleUnirseCirculo.bind(this));

            // Ofrecer ayuda
            $(document).on('click', '.cc-btn-ayudar', this.handleOfrecerAyuda.bind(this));

            // Registrar horas
            $(document).on('submit', '.cc-form-registrar-horas', this.handleRegistrarHoras.bind(this));

            // Crear necesidad
            $(document).on('submit', '.cc-form-crear-necesidad', this.handleCrearNecesidad.bind(this));

            // Modal
            $(document).on('click', '.cc-modal__cerrar, .cc-modal', this.handleCerrarModal.bind(this));
            $(document).on('click', '.cc-modal__contenido', function(e) {
                e.stopPropagation();
            });

            // Botones abrir modal
            $(document).on('click', '[data-cc-modal]', this.handleAbrirModal.bind(this));
        },

        /**
         * Maneja unirse a círculo
         */
        handleUnirseCirculo: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const circuloId = $btn.data('circulo');

            if (!confirm(ccData.i18n.confirmUnirse)) {
                return;
            }

            $btn.prop('disabled', true).text('...');

            $.ajax({
                url: ccData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cc_unirse_circulo',
                    nonce: ccData.nonce,
                    circulo_id: circuloId
                },
                success: function(response) {
                    if (response.success) {
                        CirculosCuidados.showToast(response.data.message, 'success');
                        $btn.replaceWith('<span class="cc-badge-miembro">Eres miembro</span>');
                    } else {
                        CirculosCuidados.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Unirme');
                    }
                },
                error: function() {
                    CirculosCuidados.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Unirme');
                }
            });
        },

        /**
         * Maneja ofrecer ayuda
         */
        handleOfrecerAyuda: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const necesidadId = $btn.data('necesidad');

            // Abrir modal para ofrecer ayuda
            this.abrirModalAyuda(necesidadId);
        },

        /**
         * Abre modal para ofrecer ayuda
         */
        abrirModalAyuda: function(necesidadId) {
            const modalHtml = `
                <div class="cc-modal is-active" id="cc-modal-ayuda">
                    <div class="cc-modal__contenido">
                        <div class="cc-modal__header">
                            <h3 class="cc-modal__titulo">Ofrecer ayuda</h3>
                            <button class="cc-modal__cerrar">&times;</button>
                        </div>
                        <form class="cc-form-ofrecer-ayuda">
                            <input type="hidden" name="necesidad_id" value="${necesidadId}">
                            <div class="cc-modal__body">
                                <div class="cc-form-grupo">
                                    <label for="cc-horas">¿Cuántas horas puedes dedicar?</label>
                                    <input type="number" id="cc-horas" name="horas" min="0.5" step="0.5" value="2" required>
                                </div>
                                <div class="cc-form-grupo">
                                    <label for="cc-mensaje">Mensaje (opcional)</label>
                                    <textarea id="cc-mensaje" name="mensaje" rows="3"
                                        placeholder="Cuéntale cómo puedes ayudar..."></textarea>
                                </div>
                            </div>
                            <div class="cc-modal__footer">
                                <button type="button" class="cc-btn cc-btn--secondary cc-modal__cerrar">Cancelar</button>
                                <button type="submit" class="cc-btn cc-btn--primary">Ofrecer ayuda</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);

            // Manejar envío
            $('.cc-form-ofrecer-ayuda').on('submit', function(e) {
                e.preventDefault();
                CirculosCuidados.enviarOfertaAyuda($(this));
            });
        },

        /**
         * Envía oferta de ayuda
         */
        enviarOfertaAyuda: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: ccData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cc_ofrecer_ayuda',
                    nonce: ccData.nonce,
                    necesidad_id: $form.find('[name="necesidad_id"]').val(),
                    horas: $form.find('[name="horas"]').val(),
                    mensaje: $form.find('[name="mensaje"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        CirculosCuidados.showToast(response.data.message, 'success');
                        CirculosCuidados.cerrarModales();

                        // Actualizar UI
                        const necesidadId = $form.find('[name="necesidad_id"]').val();
                        $(`.cc-btn-ayudar[data-necesidad="${necesidadId}"]`)
                            .text('Ayuda ofrecida')
                            .prop('disabled', true);
                    } else {
                        CirculosCuidados.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Ofrecer ayuda');
                    }
                },
                error: function() {
                    CirculosCuidados.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Ofrecer ayuda');
                }
            });
        },

        /**
         * Maneja registrar horas
         */
        handleRegistrarHoras: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Registrando...');

            $.ajax({
                url: ccData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cc_registrar_horas',
                    nonce: ccData.nonce,
                    necesidad_id: $form.find('[name="necesidad_id"]').val(),
                    horas: $form.find('[name="horas"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        CirculosCuidados.showToast(ccData.i18n.gracias, 'success');
                        $form[0].reset();

                        // Actualizar stats si existen
                        if (response.data.horas_totales) {
                            $('.cc-stat-card--horas .cc-stat-card__valor').text(response.data.horas_totales + 'h');
                        }
                    } else {
                        CirculosCuidados.showToast(response.data.message, 'error');
                    }
                    $btn.prop('disabled', false).text('Registrar');
                },
                error: function() {
                    CirculosCuidados.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Registrar');
                }
            });
        },

        /**
         * Maneja crear necesidad
         */
        handleCrearNecesidad: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Creando...');

            const formData = {
                action: 'cc_crear_necesidad',
                nonce: ccData.nonce,
                circulo_id: $form.find('[name="circulo_id"]').val(),
                titulo: $form.find('[name="titulo"]').val(),
                descripcion: $form.find('[name="descripcion"]').val(),
                tipo_ayuda: $form.find('[name="tipo_ayuda"]').val(),
                urgencia: $form.find('[name="urgencia"]').val(),
                fecha: $form.find('[name="fecha"]').val(),
                horas_estimadas: $form.find('[name="horas_estimadas"]').val()
            };

            $.ajax({
                url: ccData.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        CirculosCuidados.showToast(response.data.message, 'success');
                        CirculosCuidados.cerrarModales();

                        // Recargar página para mostrar nueva necesidad
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        CirculosCuidados.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Publicar necesidad');
                    }
                },
                error: function() {
                    CirculosCuidados.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Publicar necesidad');
                }
            });
        },

        /**
         * Abre modal genérico
         */
        handleAbrirModal: function(e) {
            e.preventDefault();
            const modalId = $(e.currentTarget).data('cc-modal');
            $(`#${modalId}`).addClass('is-active');
        },

        /**
         * Cierra modal
         */
        handleCerrarModal: function(e) {
            if ($(e.target).hasClass('cc-modal') || $(e.target).hasClass('cc-modal__cerrar')) {
                this.cerrarModales();
            }
        },

        /**
         * Cierra todos los modales
         */
        cerrarModales: function() {
            $('.cc-modal').removeClass('is-active');
            $('#cc-modal-ayuda').remove();
        },

        /**
         * Muestra toast de notificación
         */
        showToast: function(message, type) {
            const $toast = $(`<div class="cc-toast cc-toast--${type}">${message}</div>`);
            $('body').append($toast);

            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        CirculosCuidados.init();
    });

})(jQuery);
