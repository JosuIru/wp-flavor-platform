/**
 * Flavor User Dashboard - JavaScript
 * Gestion de la interfaz del dashboard Mi Cuenta.
 * Usa jQuery (disponible en WordPress).
 * @package FlavorChatIA
 */
(function($) {
    var FlavorUserDashboard = {
        configuracion: {},
        intervaloPollingNotificaciones: null,
        peticionEnCurso: false,
        init: function() {
            if (typeof flavorDashboardData === 'undefined') { return; }
            this.configuracion = flavorDashboardData;
            this.vincularEventosPerfil();
            this.vincularEventosPassword();
            this.vincularEventosNotificaciones();
            this.iniciarPollingNotificaciones();
        },
        vincularEventosPerfil: function() {
            var self = this;
            $(document).on('submit', '#flavor-form-perfil', function(e) {
                e.preventDefault();
                self.guardarPerfil($(this));
            });
        },
        guardarPerfil: function(formulario) {
            var self = this;
            if (this.peticionEnCurso) return;
            var btn = $('#flavor-btn-guardar-perfil');
            var estado = $('#flavor-perfil-status');
            var nombre = formulario.find('[name="nombre"]').val();
            var apellido = formulario.find('[name="apellido"]').val();
            var email = formulario.find('[name="email"]').val();
            var telefono = formulario.find('[name="telefono"]').val();
            if (email && !this.validarFormatoEmail(email)) {
                this.mostrarEstadoFormulario(estado, this.configuracion.i18n.error_email, 'error');
                return;
            }
            this.peticionEnCurso = true;
            btn.addClass('flavor-dashboard-btn--cargando').prop('disabled', true).text(this.configuracion.i18n.guardando);
            $.ajax({
                url: this.configuracion.ajaxUrl, type: 'POST',
                data: { action: 'flavor_update_profile', nonce: this.configuracion.nonce, nombre: nombre, apellido: apellido, email: email, telefono: telefono },
                success: function(r) {
                    if (r.success) {
                        self.mostrarMensajeDashboard(r.data.message || self.configuracion.i18n.perfil_actualizado, 'exito');
                        self.mostrarEstadoFormulario(estado, self.configuracion.i18n.guardado, 'exito');
                        if (r.data.data && r.data.data.nombre_mostrar) $('.flavor-dashboard-user-name').text(r.data.data.nombre_mostrar);
                    } else {
                        var msg = (r.data && r.data.message) ? r.data.message : self.configuracion.i18n.error_guardar;
                        self.mostrarEstadoFormulario(estado, msg, 'error');
                        self.mostrarMensajeDashboard(msg, 'error');
                    }
                },
                error: function() {
                    self.mostrarEstadoFormulario(estado, self.configuracion.i18n.error_conexion, 'error');
                    self.mostrarMensajeDashboard(self.configuracion.i18n.error_conexion, 'error');
                },
                complete: function() {
                    self.peticionEnCurso = false;
                    btn.removeClass('flavor-dashboard-btn--cargando').prop('disabled', false).text('Guardar cambios');
                }
            });
        },
        vincularEventosPassword: function() {
            var self = this;
            $(document).on('submit', '#flavor-form-password', function(e) {
                e.preventDefault();
                self.cambiarContrasena($(this));
            });
        },
        cambiarContrasena: function(formulario) {
            var self = this;
            if (this.peticionEnCurso) return;
            var btn = $('#flavor-btn-cambiar-password');
            var estado = $('#flavor-password-status');
            var passActual = formulario.find('[name="password_actual"]').val();
            var passNueva = formulario.find('[name="password_nueva"]').val();
            var passConfirmar = formulario.find('[name="password_confirmar"]').val();
            if (!passActual || !passNueva || !passConfirmar) {
                this.mostrarEstadoFormulario(estado, 'Todos los campos son obligatorios.', 'error');
                return;
            }
            if (passNueva.length < 8) {
                this.mostrarEstadoFormulario(estado, this.configuracion.i18n.password_corto, 'error');
                return;
            }
            if (passNueva !== passConfirmar) {
                this.mostrarEstadoFormulario(estado, this.configuracion.i18n.confirmar_password, 'error');
                return;
            }
            this.peticionEnCurso = true;
            btn.addClass('flavor-dashboard-btn--cargando').prop('disabled', true);
            $.ajax({
                url: this.configuracion.ajaxUrl, type: 'POST',
                data: { action: 'flavor_update_password', nonce: this.configuracion.nonce, password_actual: passActual, password_nueva: passNueva, password_confirmar: passConfirmar },
                success: function(r) {
                    if (r.success) {
                        self.mostrarMensajeDashboard(r.data.message || self.configuracion.i18n.password_actualizado, 'exito');
                        self.mostrarEstadoFormulario(estado, self.configuracion.i18n.password_actualizado, 'exito');
                        formulario[0].reset();
                    } else {
                        var msg = (r.data && r.data.message) ? r.data.message : self.configuracion.i18n.error_guardar;
                        self.mostrarEstadoFormulario(estado, msg, 'error');
                        self.mostrarMensajeDashboard(msg, 'error');
                    }
                },
                error: function() { self.mostrarEstadoFormulario(estado, self.configuracion.i18n.error_conexion, 'error'); },
                complete: function() { self.peticionEnCurso = false; btn.removeClass('flavor-dashboard-btn--cargando').prop('disabled', false); }
            });
        },
        vincularEventosNotificaciones: function() {
            var self = this;
            $(document).on('click', '.flavor-dashboard-btn-notificacion-leida', function(e) {
                e.preventDefault();
                var idNotificacion = $(this).data('notification-id');
                self.marcarNotificacionLeida(idNotificacion, $(this));
            });
            $(document).on('click', '#flavor-btn-marcar-todas-leidas', function(e) {
                e.preventDefault();
                self.marcarTodasNotificacionesLeidas($(this));
            });
        },
        marcarNotificacionLeida: function(idNotificacion, botonLeida) {
            var self = this;
            $.ajax({
                url: this.configuracion.ajaxUrl, type: 'POST',
                data: { action: 'flavor_mark_notification_read', nonce: this.configuracion.nonce, notification_id: idNotificacion },
                success: function(r) {
                    if (r.success) {
                        var notifEl = botonLeida.closest('.flavor-dashboard-notificacion');
                        notifEl.removeClass('flavor-dashboard-notificacion--sin-leer');
                        notifEl.find('.flavor-dashboard-notificacion-indicador').css('background', 'transparent');
                        botonLeida.remove();
                        self.actualizarBadgeNotificaciones(r.data.cantidad_sin_leer);
                    }
                }
            });
        },
        marcarTodasNotificacionesLeidas: function(botonTodas) {
            var self = this;
            botonTodas.prop('disabled', true).text(self.configuracion.i18n.cargando);
            $.ajax({
                url: this.configuracion.ajaxUrl, type: 'POST',
                data: { action: 'flavor_mark_all_notifications_read', nonce: this.configuracion.nonce },
                success: function(r) {
                    if (r.success) {
                        $('.flavor-dashboard-notificacion--sin-leer').removeClass('flavor-dashboard-notificacion--sin-leer');
                        $('.flavor-dashboard-notificacion-indicador').css('background', 'transparent');
                        $('.flavor-dashboard-btn-notificacion-leida').remove();
                        self.actualizarBadgeNotificaciones(0);
                        botonTodas.hide();
                    }
                },
                complete: function() { botonTodas.prop('disabled', false).text(self.configuracion.i18n.marcar_todas_leidas); }
            });
        },
        iniciarPollingNotificaciones: function() {
            var self = this;
            var intervaloMs = this.configuracion.pollingInterval || 60000;
            if (!this.configuracion.userId) return;
            this.intervaloPollingNotificaciones = setInterval(function() { self.consultarNotificacionesSinLeer(); }, intervaloMs);
            $(window).on('beforeunload', function() { if (self.intervaloPollingNotificaciones) clearInterval(self.intervaloPollingNotificaciones); });
        },
        consultarNotificacionesSinLeer: function() {
            var self = this;
            $.ajax({
                url: this.configuracion.ajaxUrl, type: 'POST',
                data: { action: 'flavor_get_unread_count', nonce: this.configuracion.nonce },
                success: function(r) { if (r.success) self.actualizarBadgeNotificaciones(r.data.cantidad_sin_leer); }
            });
        },
        actualizarBadgeNotificaciones: function(cantidadSinLeer) {
            var badge = $('#flavor-badge-notificaciones');
            if (cantidadSinLeer > 0) {
                if (badge.length) { badge.text(cantidadSinLeer); }
                else {
                    var enlace = $('[data-tab="notificaciones"]');
                    if (enlace.length) enlace.append('<span class="flavor-dashboard-badge" id="flavor-badge-notificaciones">' + cantidadSinLeer + '</span>');
                }
            } else { badge.remove(); }
            var badgeInline = $('.flavor-dashboard-badge--inline');
            if (cantidadSinLeer > 0) { if (badgeInline.length) badgeInline.text(cantidadSinLeer); }
            else { badgeInline.remove(); }
        },
        mostrarMensajeDashboard: function(textoMensaje, tipoMensaje) {
            var contenedor = $('#flavor-dashboard-mensaje');
            contenedor.removeClass('flavor-dashboard-mensaje--exito flavor-dashboard-mensaje--error flavor-dashboard-mensaje--info')
                .addClass('flavor-dashboard-mensaje--' + tipoMensaje).text(textoMensaje).slideDown(200);
            setTimeout(function() { contenedor.slideUp(200); }, 5000);
        },
        mostrarEstadoFormulario: function(elementoEstado, textoEstado, tipoEstado) {
            var colorEstado = tipoEstado === 'exito' ? 'var(--flavor-color-exito)' : 'var(--flavor-color-error)';
            elementoEstado.text(textoEstado).css('color', colorEstado).css('opacity', 1);
            setTimeout(function() { elementoEstado.css('opacity', 0); }, 4000);
        },
        validarFormatoEmail: function(direccionEmail) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(direccionEmail);
        }
    };
    $(document).ready(function() { FlavorUserDashboard.init(); });
})(jQuery);
