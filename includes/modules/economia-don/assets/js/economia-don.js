/**
 * Economía del Don - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const EconomiaDon = {
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
            // Solicitar don
            $(document).on('click', '.ed-btn-solicitar', this.handleSolicitarDon.bind(this));

            // Confirmar entrega
            $(document).on('click', '.ed-btn-confirmar-entrega', this.handleConfirmarEntrega.bind(this));

            // Publicar don
            $(document).on('submit', '.ed-form-ofrecer', this.handlePublicarDon.bind(this));

            // Agradecer
            $(document).on('submit', '.ed-form-agradecer', this.handleAgradecer.bind(this));

            // Filtros
            $(document).on('click', '.ed-filtro-btn', this.handleFiltro.bind(this));

            // Modal
            $(document).on('click', '.ed-modal__cerrar, .ed-modal', this.handleCerrarModal.bind(this));
            $(document).on('click', '.ed-modal__contenido', function(e) {
                e.stopPropagation();
            });
        },

        /**
         * Maneja solicitar don
         */
        handleSolicitarDon: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const donId = $btn.data('don');

            // Abrir modal de solicitud
            this.abrirModalSolicitud(donId);
        },

        /**
         * Abre modal de solicitud
         */
        abrirModalSolicitud: function(donId) {
            const modalHtml = `
                <div class="ed-modal is-active" id="ed-modal-solicitar">
                    <div class="ed-modal__contenido">
                        <div class="ed-modal__header">
                            <h3 class="ed-modal__titulo">Solicitar este don</h3>
                            <button class="ed-modal__cerrar">&times;</button>
                        </div>
                        <form class="ed-form-solicitar">
                            <input type="hidden" name="don_id" value="${donId}">
                            <div class="ed-modal__body">
                                <p style="margin-bottom: 1rem; color: var(--ed-text-light);">
                                    En la economía del don no hay obligación de devolver nada.
                                    Si lo deseas, puedes explicar por qué te sería útil este don.
                                </p>
                                <div class="ed-form-grupo">
                                    <label for="ed-mensaje">Mensaje (opcional)</label>
                                    <textarea id="ed-mensaje" name="mensaje" rows="3"
                                        placeholder="Me vendría muy bien porque..."></textarea>
                                </div>
                            </div>
                            <div class="ed-modal__footer">
                                <button type="button" class="ed-btn ed-btn--secondary ed-modal__cerrar">
                                    Cancelar
                                </button>
                                <button type="submit" class="ed-btn-solicitar-enviar">
                                    Solicitar don
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);

            // Manejar envío
            $('.ed-form-solicitar').on('submit', function(e) {
                e.preventDefault();
                EconomiaDon.enviarSolicitud($(this));
            });
        },

        /**
         * Envía solicitud de don
         */
        enviarSolicitud: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: edData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ed_solicitar_don',
                    nonce: edData.nonce,
                    don_id: $form.find('[name="don_id"]').val(),
                    mensaje: $form.find('[name="mensaje"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        EconomiaDon.showToast(response.data.message, 'success');
                        EconomiaDon.cerrarModales();

                        // Actualizar estado del botón
                        const donId = $form.find('[name="don_id"]').val();
                        $(`.ed-btn-solicitar[data-don="${donId}"]`)
                            .text('Solicitado')
                            .prop('disabled', true);
                    } else {
                        EconomiaDon.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Solicitar don');
                    }
                },
                error: function() {
                    EconomiaDon.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Solicitar don');
                }
            });
        },

        /**
         * Maneja confirmar entrega
         */
        handleConfirmarEntrega: function(e) {
            e.preventDefault();

            if (!confirm(edData.i18n.confirmEntrega)) {
                return;
            }

            const $btn = $(e.currentTarget);
            const donId = $btn.data('don');

            $btn.prop('disabled', true).text('Confirmando...');

            $.ajax({
                url: edData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ed_confirmar_entrega',
                    nonce: edData.nonce,
                    don_id: donId
                },
                success: function(response) {
                    if (response.success) {
                        EconomiaDon.showToast(response.data.message, 'success');
                        $btn.replaceWith('<span class="ed-badge-entregado">Entregado</span>');
                    } else {
                        EconomiaDon.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Confirmar entrega');
                    }
                },
                error: function() {
                    EconomiaDon.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Confirmar entrega');
                }
            });
        },

        /**
         * Maneja publicar don
         */
        handlePublicarDon: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Publicando...');

            $.ajax({
                url: edData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ed_publicar_don',
                    nonce: edData.nonce,
                    titulo: $form.find('[name="titulo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    categoria: $form.find('[name="categoria"]:checked').val(),
                    ubicacion: $form.find('[name="ubicacion"]').val(),
                    disponibilidad: $form.find('[name="disponibilidad"]').val(),
                    anonimo: $form.find('[name="anonimo"]').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        EconomiaDon.showToast(response.data.message, 'success');

                        // Redirigir al don creado
                        if (response.data.url) {
                            setTimeout(function() {
                                window.location.href = response.data.url;
                            }, 1500);
                        }
                    } else {
                        EconomiaDon.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Publicar don');
                    }
                },
                error: function() {
                    EconomiaDon.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Publicar don');
                }
            });
        },

        /**
         * Maneja agradecer
         */
        handleAgradecer: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: edData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ed_agradecer',
                    nonce: edData.nonce,
                    don_id: $form.find('[name="don_id"]').val(),
                    mensaje: $form.find('[name="mensaje"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        EconomiaDon.showToast(response.data.message, 'success');
                        EconomiaDon.cerrarModales();

                        // Recargar para mostrar actualización
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        EconomiaDon.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Publicar gratitud');
                    }
                },
                error: function() {
                    EconomiaDon.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Publicar gratitud');
                }
            });
        },

        /**
         * Maneja filtro de categorías
         */
        handleFiltro: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const categoria = $btn.data('categoria');

            // Toggle active
            if ($btn.hasClass('is-active') && categoria !== 'todos') {
                $btn.removeClass('is-active');
                this.filtrarDones('todos');
            } else {
                $('.ed-filtro-btn').removeClass('is-active');
                $btn.addClass('is-active');
                this.filtrarDones(categoria);
            }
        },

        /**
         * Filtra dones por categoría
         */
        filtrarDones: function(categoria) {
            const $cards = $('.ed-don-card');

            if (categoria === 'todos') {
                $cards.show();
            } else {
                $cards.each(function() {
                    const cardCategoria = $(this).data('categoria');
                    $(this).toggle(cardCategoria === categoria);
                });
            }
        },

        /**
         * Cierra modal
         */
        handleCerrarModal: function(e) {
            if ($(e.target).hasClass('ed-modal') || $(e.target).hasClass('ed-modal__cerrar')) {
                this.cerrarModales();
            }
        },

        /**
         * Cierra todos los modales
         */
        cerrarModales: function() {
            $('.ed-modal').removeClass('is-active');
            $('#ed-modal-solicitar').remove();
        },

        /**
         * Muestra toast
         */
        showToast: function(message, type) {
            const $toast = $(`<div class="ed-toast ed-toast--${type}">${message}</div>`);
            $('body').append($toast);

            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializar
    $(document).ready(function() {
        EconomiaDon.init();
    });

})(jQuery);
