/**
 * Saberes Ancestrales - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const Saberes = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.sa-categoria-btn', this.filtrarCategoria.bind(this));
            $(document).on('submit', '.sa-form-saber', this.registrarSaber.bind(this));
            $(document).on('click', '.sa-btn-solicitar', this.solicitarAprendizaje.bind(this));
            $(document).on('click', '.sa-btn-inscribirse', this.inscribirseTaller.bind(this));
            $(document).on('click', '.sa-saber-card__agradecimientos', this.agradecer.bind(this));
        },

        filtrarCategoria: function(e) {
            const $btn = $(e.currentTarget);
            const categoria = $btn.data('categoria');

            $('.sa-categoria-btn').removeClass('activo');
            $btn.addClass('activo');

            if (categoria === 'todos') {
                $('.sa-saber-card').show();
            } else {
                $('.sa-saber-card').hide();
                $(`.sa-saber-card[data-categoria="${categoria}"]`).show();
            }
        },

        registrarSaber: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSaberes.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sa_registrar_saber',
                    nonce: flavorSaberes.nonce,
                    titulo: $form.find('[name="titulo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    categoria: $form.find('[name="categoria"]').val(),
                    origen: $form.find('[name="origen"]').val(),
                    portador: $form.find('[name="portador"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSaberes.i18n.error);
                }
            });
        },

        solicitarAprendizaje: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const saberId = $btn.data('saber');
            const mensaje = prompt('¿Por qué te interesa aprender este saber?');

            if (mensaje === null) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSaberes.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sa_solicitar_aprendizaje',
                    nonce: flavorSaberes.nonce,
                    saber_id: saberId,
                    mensaje: mensaje
                },
                success: (response) => {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Solicitado').removeClass('sa-btn--primary').addClass('sa-btn--secondary');
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSaberes.i18n.error);
                }
            });
        },

        inscribirseTaller: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const tallerId = $btn.data('taller');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSaberes.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sa_inscribirse_taller',
                    nonce: flavorSaberes.nonce,
                    taller_id: tallerId
                },
                success: (response) => {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Inscrito/a').removeClass('sa-btn--primary').addClass('sa-btn--secondary');
                        const $plazas = $btn.closest('.sa-taller-card').find('.sa-plazas');
                        $plazas.text(response.data.plazas_restantes + ' plazas');
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSaberes.i18n.error);
                }
            });
        },

        agradecer: function(e) {
            const $elem = $(e.currentTarget);
            const saberId = $elem.data('saber');

            $.ajax({
                url: flavorSaberes.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sa_agradecer_saber',
                    nonce: flavorSaberes.nonce,
                    saber_id: saberId
                },
                success: (response) => {
                    if (response.success) {
                        $elem.find('.sa-agradecimientos-count').text(response.data.agradecimientos);
                        $elem.css('color', 'var(--sa-gold)');
                    }
                }
            });
        },

        mostrarExito: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'success');
        },

        mostrarError: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'error');
        },

        mostrarNotificacion: function(mensaje, tipo) {
            const color = tipo === 'success' ? '#8B4513' : '#e74c3c';
            const $notif = $(`<div style="position:fixed;bottom:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:${color};color:white;box-shadow:0 4px 12px rgba(0,0,0,0.15);transform:translateX(120%);transition:transform 0.3s;z-index:10000;">${mensaje}</div>`);
            $('body').append($notif);
            setTimeout(() => $notif.css('transform', 'translateX(0)'), 10);
            setTimeout(() => { $notif.css('transform', 'translateX(120%)'); setTimeout(() => $notif.remove(), 300); }, 4000);
        }
    };

    $(document).ready(function() { Saberes.init(); });
})(jQuery);
