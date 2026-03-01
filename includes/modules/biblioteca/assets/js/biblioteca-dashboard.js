/**
 * JavaScript para Biblioteca Dashboard Tab
 *
 * @package FlavorChatIA
 * @subpackage Biblioteca
 */

(function($) {
    'use strict';

    /**
     * Objeto principal del Dashboard de Biblioteca
     */
    const BibliotecaDashboard = {

        /**
         * Configuración
         */
        config: {
            ajaxUrl: bibliotecaDashboard?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: bibliotecaDashboard?.nonce || '',
            i18n: bibliotecaDashboard?.i18n || {}
        },

        /**
         * Elementos del DOM
         */
        elementos: {
            contenedor: '.biblioteca-dashboard-tab',
            btnRenovar: '.btn-renovar',
            btnCancelarReserva: '.btn-cancelar-reserva',
            btnAgregarFavorito: '.btn-agregar-favorito',
            btnQuitarFavorito: '.btn-quitar-favorito',
            btnReservar: '.btn-reservar',
            btnListaEspera: '.btn-lista-espera',
            btnContactar: '.btn-contactar',
            btnValorar: '.btn-valorar'
        },

        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.cargarEstadoFavoritos();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            const self = this;

            // Renovar préstamo
            $(document).on('click', this.elementos.btnRenovar, function(evento) {
                evento.preventDefault();
                const prestamoId = $(this).data('prestamo-id');
                self.renovarPrestamo(prestamoId, $(this));
            });

            // Cancelar reserva
            $(document).on('click', this.elementos.btnCancelarReserva, function(evento) {
                evento.preventDefault();
                const reservaId = $(this).data('reserva-id');
                self.cancelarReserva(reservaId, $(this));
            });

            // Toggle favorito
            $(document).on('click', this.elementos.btnAgregarFavorito, function(evento) {
                evento.preventDefault();
                const libroId = $(this).data('libro-id');
                self.toggleFavorito(libroId, $(this));
            });

            // Quitar de favoritos
            $(document).on('click', this.elementos.btnQuitarFavorito, function(evento) {
                evento.preventDefault();
                const libroId = $(this).data('libro-id');
                const favoritoCard = $(this).closest('.favorito-card');

                if (confirm(self.config.i18n.confirmarEliminarFavorito)) {
                    self.quitarFavorito(libroId, favoritoCard);
                }
            });

            // Reservar libro
            $(document).on('click', this.elementos.btnReservar, function(evento) {
                evento.preventDefault();
                const libroId = $(this).data('libro-id');
                self.reservarLibro(libroId, $(this));
            });

            // Lista de espera
            $(document).on('click', this.elementos.btnListaEspera, function(evento) {
                evento.preventDefault();
                const libroId = $(this).data('libro-id');
                self.agregarListaEspera(libroId, $(this));
            });

            // Contactar prestamista
            $(document).on('click', this.elementos.btnContactar, function(evento) {
                evento.preventDefault();
                const usuarioId = $(this).data('usuario-id');
                self.contactarUsuario(usuarioId);
            });

            // Valorar libro
            $(document).on('click', this.elementos.btnValorar, function(evento) {
                evento.preventDefault();
                const libroId = $(this).data('libro-id');
                self.mostrarModalValoracion(libroId);
            });
        },

        /**
         * Renovar préstamo
         */
        renovarPrestamo: function(prestamoId, boton) {
            const self = this;
            const textoOriginal = boton.html();

            boton.prop('disabled', true).html('<span class="spinner"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_renovar_prestamo',
                    nonce: this.config.nonce,
                    prestamo_id: prestamoId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarNotificacion(respuesta.data.mensaje, 'success');
                        // Recargar la página para mostrar la nueva fecha
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.mostrarNotificacion(respuesta.data.mensaje || self.config.i18n.errorGeneral, 'error');
                        boton.prop('disabled', false).html(textoOriginal);
                    }
                },
                error: function() {
                    self.mostrarNotificacion(self.config.i18n.errorGeneral, 'error');
                    boton.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        /**
         * Cancelar reserva
         */
        cancelarReserva: function(reservaId, boton) {
            const self = this;
            const textoOriginal = boton.html();
            const itemReserva = boton.closest('.reserva-item');

            boton.prop('disabled', true).html('<span class="spinner"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_cancelar_reserva',
                    nonce: this.config.nonce,
                    reserva_id: reservaId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarNotificacion(respuesta.data.mensaje, 'success');
                        itemReserva.fadeOut(300, function() {
                            $(this).remove();
                            // Verificar si quedan reservas
                            if ($('.reservas-lista .reserva-item').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        self.mostrarNotificacion(respuesta.data.mensaje || self.config.i18n.errorGeneral, 'error');
                        boton.prop('disabled', false).html(textoOriginal);
                    }
                },
                error: function() {
                    self.mostrarNotificacion(self.config.i18n.errorGeneral, 'error');
                    boton.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        /**
         * Toggle favorito (agregar/quitar)
         */
        toggleFavorito: function(libroId, boton) {
            const self = this;
            const iconoCorazon = boton.find('.dashicons');

            boton.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_toggle_favorito',
                    nonce: this.config.nonce,
                    libro_id: libroId,
                    accion: 'toggle'
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        if (respuesta.data.accion === 'agregado') {
                            iconoCorazon.css('color', '#ef4444');
                            self.mostrarNotificacion(self.config.i18n.agregadoFavoritos, 'success');
                        } else {
                            iconoCorazon.css('color', '');
                            self.mostrarNotificacion(self.config.i18n.eliminadoFavoritos, 'success');
                        }
                    } else {
                        self.mostrarNotificacion(respuesta.data.mensaje || self.config.i18n.errorGeneral, 'error');
                    }
                    boton.prop('disabled', false);
                },
                error: function() {
                    self.mostrarNotificacion(self.config.i18n.errorGeneral, 'error');
                    boton.prop('disabled', false);
                }
            });
        },

        /**
         * Quitar de favoritos
         */
        quitarFavorito: function(libroId, tarjeta) {
            const self = this;

            tarjeta.addClass('cargando');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_toggle_favorito',
                    nonce: this.config.nonce,
                    libro_id: libroId,
                    accion: 'eliminar'
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarNotificacion(self.config.i18n.eliminadoFavoritos, 'success');
                        tarjeta.fadeOut(300, function() {
                            $(this).remove();
                            // Verificar si quedan favoritos
                            if ($('.favoritos-grid .favorito-card').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        self.mostrarNotificacion(respuesta.data.mensaje || self.config.i18n.errorGeneral, 'error');
                        tarjeta.removeClass('cargando');
                    }
                },
                error: function() {
                    self.mostrarNotificacion(self.config.i18n.errorGeneral, 'error');
                    tarjeta.removeClass('cargando');
                }
            });
        },

        /**
         * Reservar libro
         */
        reservarLibro: function(libroId, boton) {
            const self = this;
            const textoOriginal = boton.html();

            boton.prop('disabled', true).html('<span class="spinner"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_reservar_libro',
                    nonce: this.config.nonce,
                    libro_id: libroId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarNotificacion(respuesta.data.mensaje, 'success');
                        boton.removeClass('btn-primary').addClass('btn-secondary')
                             .html('<span class="dashicons dashicons-yes"></span> Reservado');
                    } else {
                        self.mostrarNotificacion(respuesta.data.mensaje || self.config.i18n.errorGeneral, 'error');
                        boton.prop('disabled', false).html(textoOriginal);
                    }
                },
                error: function() {
                    self.mostrarNotificacion(self.config.i18n.errorGeneral, 'error');
                    boton.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        /**
         * Agregar a lista de espera
         */
        agregarListaEspera: function(libroId, boton) {
            // Similar a reservar, pero para libros no disponibles
            this.reservarLibro(libroId, boton);
        },

        /**
         * Contactar usuario
         */
        contactarUsuario: function(usuarioId) {
            // Redirigir al sistema de mensajería interno o mostrar modal
            const urlMensajeria = '/mi-portal/mensajes/?destinatario=' + usuarioId;
            window.location.href = urlMensajeria;
        },

        /**
         * Mostrar modal de valoración
         */
        mostrarModalValoracion: function(libroId) {
            // Crear modal de valoración
            const modalHtml = `
                <div class="biblioteca-modal-valoracion" id="modal-valoracion">
                    <div class="modal-contenido">
                        <button class="modal-cerrar">&times;</button>
                        <h3>Valorar libro</h3>
                        <div class="valoracion-estrellas">
                            ${[1,2,3,4,5].map(i => `<span class="estrella" data-valor="${i}">★</span>`).join('')}
                        </div>
                        <input type="hidden" id="valoracion-valor" value="0">
                        <textarea id="valoracion-comentario" placeholder="Escribe tu reseña (opcional)"></textarea>
                        <div class="modal-acciones">
                            <button class="btn btn-secondary modal-cancelar">Cancelar</button>
                            <button class="btn btn-primary modal-enviar" data-libro-id="${libroId}">Enviar valoración</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            this.bindEventosModal();
        },

        /**
         * Vincular eventos del modal
         */
        bindEventosModal: function() {
            const self = this;
            const modal = $('#modal-valoracion');

            // Cerrar modal
            modal.on('click', '.modal-cerrar, .modal-cancelar', function() {
                modal.fadeOut(200, function() {
                    $(this).remove();
                });
            });

            // Click fuera del modal
            modal.on('click', function(e) {
                if ($(e.target).is(modal)) {
                    modal.fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });

            // Seleccionar estrellas
            modal.on('click', '.estrella', function() {
                const valor = $(this).data('valor');
                $('#valoracion-valor').val(valor);
                modal.find('.estrella').removeClass('seleccionada');
                modal.find('.estrella').each(function() {
                    if ($(this).data('valor') <= valor) {
                        $(this).addClass('seleccionada');
                    }
                });
            });

            // Hover estrellas
            modal.on('mouseenter', '.estrella', function() {
                const valor = $(this).data('valor');
                modal.find('.estrella').each(function() {
                    if ($(this).data('valor') <= valor) {
                        $(this).addClass('hover');
                    }
                });
            }).on('mouseleave', '.estrella', function() {
                modal.find('.estrella').removeClass('hover');
            });

            // Enviar valoración
            modal.on('click', '.modal-enviar', function() {
                const libroId = $(this).data('libro-id');
                const valoracion = $('#valoracion-valor').val();
                const comentario = $('#valoracion-comentario').val();

                if (valoracion < 1) {
                    self.mostrarNotificacion('Selecciona al menos una estrella', 'error');
                    return;
                }

                self.enviarValoracion(libroId, valoracion, comentario, modal);
            });
        },

        /**
         * Enviar valoración
         */
        enviarValoracion: function(libroId, valoracion, comentario, modal) {
            const self = this;
            const btnEnviar = modal.find('.modal-enviar');

            btnEnviar.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_valorar_libro',
                    nonce: this.config.nonce,
                    libro_id: libroId,
                    valoracion: valoracion,
                    resena: comentario
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarNotificacion('Valoración enviada correctamente', 'success');
                        modal.fadeOut(200, function() {
                            $(this).remove();
                        });
                        // Actualizar UI
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.mostrarNotificacion(respuesta.data.mensaje || self.config.i18n.errorGeneral, 'error');
                        btnEnviar.prop('disabled', false).text('Enviar valoración');
                    }
                },
                error: function() {
                    self.mostrarNotificacion(self.config.i18n.errorGeneral, 'error');
                    btnEnviar.prop('disabled', false).text('Enviar valoración');
                }
            });
        },

        /**
         * Cargar estado de favoritos para botones de corazón
         */
        cargarEstadoFavoritos: function() {
            const self = this;
            const botonesCorazon = $(this.elementos.btnAgregarFavorito);

            if (botonesCorazon.length === 0) {
                return;
            }

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biblioteca_obtener_favoritos',
                    nonce: this.config.nonce
                },
                success: function(respuesta) {
                    if (respuesta.success && respuesta.data.favoritos) {
                        const favoritos = respuesta.data.favoritos;
                        botonesCorazon.each(function() {
                            const libroId = parseInt($(this).data('libro-id'));
                            if (favoritos.includes(libroId)) {
                                $(this).find('.dashicons').css('color', '#ef4444');
                            }
                        });
                    }
                }
            });
        },

        /**
         * Mostrar notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            // Remover notificaciones existentes
            $('.biblioteca-notificacion').remove();

            const iconos = {
                success: 'dashicons-yes-alt',
                error: 'dashicons-warning',
                info: 'dashicons-info'
            };

            const notificacionHtml = `
                <div class="biblioteca-notificacion biblioteca-notificacion-${tipo}">
                    <span class="dashicons ${iconos[tipo] || iconos.info}"></span>
                    <span class="notificacion-texto">${mensaje}</span>
                    <button class="notificacion-cerrar">&times;</button>
                </div>
            `;

            $('body').append(notificacionHtml);

            const notificacion = $('.biblioteca-notificacion');

            // Auto cerrar después de 4 segundos
            setTimeout(function() {
                notificacion.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);

            // Cerrar manualmente
            notificacion.on('click', '.notificacion-cerrar', function() {
                notificacion.fadeOut(200, function() {
                    $(this).remove();
                });
            });
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($(BibliotecaDashboard.elementos.contenedor).length > 0) {
            BibliotecaDashboard.init();
        }
    });

    // Exponer al objeto global
    window.BibliotecaDashboard = BibliotecaDashboard;

})(jQuery);

/**
 * Estilos inline para modal y notificaciones
 * (Agregados dinámicamente para evitar dependencias)
 */
(function() {
    const estilosModal = `
        .biblioteca-modal-valoracion {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        }
        .biblioteca-modal-valoracion .modal-contenido {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            position: relative;
        }
        .biblioteca-modal-valoracion .modal-cerrar {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }
        .biblioteca-modal-valoracion h3 {
            margin: 0 0 1.5rem;
            font-size: 1.25rem;
        }
        .biblioteca-modal-valoracion .valoracion-estrellas {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .biblioteca-modal-valoracion .estrella {
            font-size: 2rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
        }
        .biblioteca-modal-valoracion .estrella:hover,
        .biblioteca-modal-valoracion .estrella.hover {
            transform: scale(1.1);
        }
        .biblioteca-modal-valoracion .estrella.seleccionada,
        .biblioteca-modal-valoracion .estrella.hover {
            color: #fbbf24;
        }
        .biblioteca-modal-valoracion textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            margin-bottom: 1rem;
        }
        .biblioteca-modal-valoracion .modal-acciones {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .biblioteca-notificacion {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 99999;
            animation: slideIn 0.3s ease;
        }
        .biblioteca-notificacion-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .biblioteca-notificacion-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .biblioteca-notificacion-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .biblioteca-notificacion .notificacion-cerrar {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0.6;
            margin-left: 0.5rem;
        }
        .biblioteca-notificacion .notificacion-cerrar:hover {
            opacity: 1;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;

    const style = document.createElement('style');
    style.textContent = estilosModal;
    document.head.appendChild(style);
})();
