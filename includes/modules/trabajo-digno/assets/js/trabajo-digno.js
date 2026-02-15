/**
 * Trabajo Digno - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const TrabajoDigno = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.td-tipo-tab', this.filtrarTipo.bind(this));
            $(document).on('click', '.td-tab', this.cambiarTab.bind(this));
            $(document).on('submit', '.td-form-oferta', this.publicarOferta.bind(this));
            $(document).on('submit', '.td-form-perfil', this.guardarPerfil.bind(this));
            $(document).on('submit', '.td-form-emprendimiento', this.registrarEmprendimiento.bind(this));
            $(document).on('click', '.td-btn-postular', this.postular.bind(this));
            $(document).on('click', '.td-btn-inscribir', this.inscribirFormacion.bind(this));
            $(document).on('change', '.td-filtro-sector, .td-filtro-jornada', this.aplicarFiltros.bind(this));
        },

        filtrarTipo: function(e) {
            const $btn = $(e.currentTarget);
            const tipo = $btn.data('tipo');

            $('.td-tipo-tab').removeClass('activo');
            $btn.addClass('activo');

            if (tipo === 'todos') {
                $('.td-oferta-card').show();
            } else {
                $('.td-oferta-card').hide();
                $(`.td-oferta-card[data-tipo="${tipo}"]`).show();
            }
        },

        cambiarTab: function(e) {
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');

            $('.td-tab').removeClass('activo');
            $tab.addClass('activo');

            $('.td-tab-contenido').hide();
            $(`#${tabId}`).show();
        },

        aplicarFiltros: function() {
            const sector = $('.td-filtro-sector').val();
            const jornada = $('.td-filtro-jornada').val();

            $('.td-oferta-card').each(function() {
                const $card = $(this);
                const cardSector = $card.data('sector');
                const cardJornada = $card.data('jornada');

                let visible = true;

                if (sector && sector !== 'todos' && cardSector !== sector) {
                    visible = false;
                }

                if (jornada && jornada !== 'todos' && cardJornada !== jornada) {
                    visible = false;
                }

                $card.toggle(visible);
            });
        },

        publicarOferta: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            const criterios = [];
            $form.find('.td-criterio-checkbox input:checked').each(function() {
                criterios.push($(this).val());
            });

            $btn.prop('disabled', true).text('Publicando...');

            $.ajax({
                url: flavorTrabajoDigno.ajaxurl,
                type: 'POST',
                data: {
                    action: 'td_publicar_oferta',
                    nonce: flavorTrabajoDigno.nonce,
                    titulo: $form.find('[name="titulo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    tipo: $form.find('[name="tipo"]').val(),
                    sector: $form.find('[name="sector"]').val(),
                    jornada: $form.find('[name="jornada"]').val(),
                    ubicacion: $form.find('[name="ubicacion"]').val(),
                    salario: $form.find('[name="salario"]').val(),
                    criterios: criterios
                },
                success: (response) => {
                    $btn.prop('disabled', false).text('Publicar Oferta');
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Publicar Oferta');
                    this.mostrarError(flavorTrabajoDigno.i18n.error);
                }
            });
        },

        postular: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const ofertaId = $btn.data('oferta');

            if (!confirm(flavorTrabajoDigno.i18n.confirm_postular)) {
                return;
            }

            const mensaje = prompt('Mensaje de presentación (opcional):');
            if (mensaje === null) return; // Canceló

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorTrabajoDigno.ajaxurl,
                type: 'POST',
                data: {
                    action: 'td_postular',
                    nonce: flavorTrabajoDigno.nonce,
                    oferta_id: ofertaId,
                    mensaje: mensaje
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Postulado').removeClass('td-btn--primary').addClass('td-btn--secondary');
                    } else {
                        $btn.prop('disabled', false);
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorTrabajoDigno.i18n.error);
                }
            });
        },

        guardarPerfil: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            const habilidades = [];
            $form.find('.td-habilidad-checkbox:checked').each(function() {
                habilidades.push($(this).val());
            });

            const sectores = [];
            $form.find('.td-sector-checkbox:checked').each(function() {
                sectores.push($(this).val());
            });

            $btn.prop('disabled', true).text('Guardando...');

            $.ajax({
                url: flavorTrabajoDigno.ajaxurl,
                type: 'POST',
                data: {
                    action: 'td_guardar_perfil',
                    nonce: flavorTrabajoDigno.nonce,
                    titulo: $form.find('[name="titulo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    experiencia: $form.find('[name="experiencia"]').val(),
                    formacion: $form.find('[name="formacion"]').val(),
                    habilidades: habilidades,
                    sectores: sectores,
                    disponibilidad: $form.find('[name="disponibilidad"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false).text('Guardar Perfil');
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Guardar Perfil');
                    this.mostrarError(flavorTrabajoDigno.i18n.error);
                }
            });
        },

        registrarEmprendimiento: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Registrando...');

            $.ajax({
                url: flavorTrabajoDigno.ajaxurl,
                type: 'POST',
                data: {
                    action: 'td_registrar_emprendimiento',
                    nonce: flavorTrabajoDigno.nonce,
                    nombre: $form.find('[name="nombre"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    sector: $form.find('[name="sector"]').val(),
                    tipo_organizacion: $form.find('[name="tipo_organizacion"]').val(),
                    web: $form.find('[name="web"]').val(),
                    contacto: $form.find('[name="contacto"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false).text('Registrar Emprendimiento');
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Registrar Emprendimiento');
                    this.mostrarError(flavorTrabajoDigno.i18n.error);
                }
            });
        },

        inscribirFormacion: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const formacionId = $btn.data('formacion');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorTrabajoDigno.ajaxurl,
                type: 'POST',
                data: {
                    action: 'td_inscribir_formacion',
                    nonce: flavorTrabajoDigno.nonce,
                    formacion_id: formacionId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Inscrito/a').removeClass('td-btn--primary').addClass('td-btn--secondary');
                        const $contador = $btn.closest('.td-formacion-card').find('.td-inscritos-count');
                        $contador.text(response.data.inscritos);
                    } else {
                        $btn.prop('disabled', false);
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorTrabajoDigno.i18n.error);
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
            const $notif = $(`<div class="td-notificacion td-notificacion--${tipo}">${mensaje}</div>`);
            $('body').append($notif);

            setTimeout(() => $notif.addClass('mostrar'), 10);
            setTimeout(() => {
                $notif.removeClass('mostrar');
                setTimeout(() => $notif.remove(), 300);
            }, 4000);
        }
    };

    $(document).ready(function() {
        TrabajoDigno.init();
    });

})(jQuery);
