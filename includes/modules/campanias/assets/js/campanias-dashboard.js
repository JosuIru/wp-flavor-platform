/**
 * Campanias Dashboard Tab - JavaScript
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Configuracion global del modulo
     */
    const CampaniasDashboard = {
        config: null,

        /**
         * Inicializar el modulo
         */
        init: function() {
            this.config = window.flavorCampaniasDashboard || {};
            this.bindEvents();
            this.initProgressBars();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Retirar firma
            $(document).on('click', '.flavor-campania-retirar-firma', this.handleRetirarFirma.bind(this));

            // Dejar de seguir
            $(document).on('click', '.flavor-campania-dejar-seguir', this.handleDejarSeguir.bind(this));

            // Cambiar estado
            $(document).on('click', '.flavor-campania-cambiar-estado', this.handleCambiarEstado.bind(this));
        },

        /**
         * Inicializar barras de progreso con colores segun porcentaje
         */
        initProgressBars: function() {
            $('.flavor-campania-card').each(function() {
                const $card = $(this);
                const $barraRelleno = $card.find('.flavor-progreso-barra__relleno');

                if ($barraRelleno.length) {
                    const ancho = parseFloat($barraRelleno.css('width')) || 0;
                    const contenedorAncho = $barraRelleno.parent().width() || 1;
                    const porcentaje = (ancho / contenedorAncho) * 100;

                    if (porcentaje >= 75) {
                        $card.attr('data-progreso', 'high');
                    } else if (porcentaje >= 40) {
                        $card.attr('data-progreso', 'medium');
                    }
                }
            });
        },

        /**
         * Handler: Retirar firma de campania
         */
        handleRetirarFirma: function(evento) {
            evento.preventDefault();

            const $boton = $(evento.currentTarget);
            const campaniaId = $boton.data('campania-id');
            const confirmarMensaje = this.config.strings?.confirmarRetirarFirma ||
                'Seguro que deseas retirar tu firma de esta campania?';

            if (!confirm(confirmarMensaje)) {
                return;
            }

            this.setCardLoading($boton, true);

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'campanias_dashboard_retirar_firma',
                    nonce: this.config.nonce,
                    campania_id: campaniaId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(
                            this.config.strings?.firmaRetirada || 'Firma retirada correctamente.',
                            'success'
                        );
                        this.removeCard($boton);
                    } else {
                        this.showToast(
                            response.data?.error || this.config.strings?.errorOperacion || 'Error al procesar la operacion.',
                            'error'
                        );
                        this.setCardLoading($boton, false);
                    }
                },
                error: () => {
                    this.showToast(
                        this.config.strings?.errorOperacion || 'Error de conexion.',
                        'error'
                    );
                    this.setCardLoading($boton, false);
                }
            });
        },

        /**
         * Handler: Dejar de seguir campania
         */
        handleDejarSeguir: function(evento) {
            evento.preventDefault();

            const $boton = $(evento.currentTarget);
            const campaniaId = $boton.data('campania-id');
            const confirmarMensaje = this.config.strings?.confirmarDejarSeguir ||
                'Seguro que deseas dejar de seguir esta campania?';

            if (!confirm(confirmarMensaje)) {
                return;
            }

            this.setCardLoading($boton, true);

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'campanias_dashboard_dejar_seguir',
                    nonce: this.config.nonce,
                    campania_id: campaniaId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(
                            this.config.strings?.dejadoDeSeguir || 'Has dejado de seguir la campania.',
                            'success'
                        );
                        this.removeCard($boton);
                    } else {
                        this.showToast(
                            response.data?.error || this.config.strings?.errorOperacion || 'Error al procesar la operacion.',
                            'error'
                        );
                        this.setCardLoading($boton, false);
                    }
                },
                error: () => {
                    this.showToast(
                        this.config.strings?.errorOperacion || 'Error de conexion.',
                        'error'
                    );
                    this.setCardLoading($boton, false);
                }
            });
        },

        /**
         * Handler: Cambiar estado de campania
         */
        handleCambiarEstado: function(evento) {
            evento.preventDefault();

            const $boton = $(evento.currentTarget);
            const campaniaId = $boton.data('campania-id');
            const estadoActual = $boton.data('estado-actual');

            // Mostrar menu de opciones de estado
            this.mostrarMenuEstados($boton, campaniaId, estadoActual);
        },

        /**
         * Mostrar menu de seleccion de estados
         */
        mostrarMenuEstados: function($boton, campaniaId, estadoActual) {
            // Eliminar menu existente si hay
            $('.flavor-menu-estados').remove();

            const estados = [
                { value: 'planificada', label: 'Planificada' },
                { value: 'activa', label: 'Activa' },
                { value: 'pausada', label: 'Pausada' },
                { value: 'completada', label: 'Completada' },
                { value: 'cancelada', label: 'Cancelada' }
            ];

            let menuHtml = '<div class="flavor-menu-estados">';
            estados.forEach(estado => {
                const activo = estado.value === estadoActual ? ' class="activo"' : '';
                menuHtml += `<button type="button" data-estado="${estado.value}"${activo}>${estado.label}</button>`;
            });
            menuHtml += '</div>';

            const $menu = $(menuHtml);

            // Posicionar menu
            $boton.after($menu);

            // Handler para seleccion de estado
            $menu.on('click', 'button', (evt) => {
                const nuevoEstado = $(evt.currentTarget).data('estado');
                $menu.remove();

                if (nuevoEstado === estadoActual) {
                    return;
                }

                this.cambiarEstadoCampania($boton, campaniaId, nuevoEstado);
            });

            // Cerrar menu al hacer click fuera
            $(document).one('click', (evt) => {
                if (!$(evt.target).closest('.flavor-menu-estados').length) {
                    $menu.remove();
                }
            });
        },

        /**
         * Ejecutar cambio de estado
         */
        cambiarEstadoCampania: function($boton, campaniaId, nuevoEstado) {
            this.setCardLoading($boton, true);

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'campanias_dashboard_cambiar_estado',
                    nonce: this.config.nonce,
                    campania_id: campaniaId,
                    nuevo_estado: nuevoEstado
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(response.data.mensaje, 'success');

                        // Actualizar UI
                        const $card = $boton.closest('.flavor-campania-card');
                        const $estadoBadge = $card.find('.flavor-campania-card__estado');

                        $estadoBadge
                            .removeClass()
                            .addClass('flavor-campania-card__estado flavor-estado--' + nuevoEstado)
                            .text(response.data.estado_label);

                        $boton.data('estado-actual', nuevoEstado);
                    } else {
                        this.showToast(
                            response.data?.error || this.config.strings?.errorOperacion || 'Error al actualizar estado.',
                            'error'
                        );
                    }
                    this.setCardLoading($boton, false);
                },
                error: () => {
                    this.showToast(
                        this.config.strings?.errorOperacion || 'Error de conexion.',
                        'error'
                    );
                    this.setCardLoading($boton, false);
                }
            });
        },

        /**
         * Establecer estado de carga en card
         */
        setCardLoading: function($elemento, loading) {
            const $card = $elemento.closest('.flavor-campania-card');

            if (loading) {
                $card.addClass('flavor-campania-card--loading');
                $elemento.prop('disabled', true);
            } else {
                $card.removeClass('flavor-campania-card--loading');
                $elemento.prop('disabled', false);
            }
        },

        /**
         * Eliminar card con animacion
         */
        removeCard: function($elemento) {
            const $card = $elemento.closest('.flavor-campania-card');
            const $grid = $card.closest('.flavor-campanias-grid');

            $card.css({
                transition: 'opacity 0.3s ease, transform 0.3s ease',
                opacity: 0,
                transform: 'scale(0.95)'
            });

            setTimeout(() => {
                $card.remove();

                // Mostrar estado vacio si no quedan cards
                if ($grid.children().length === 0) {
                    this.mostrarEstadoVacio($grid);
                }
            }, 300);
        },

        /**
         * Mostrar estado vacio cuando se eliminan todas las cards
         */
        mostrarEstadoVacio: function($grid) {
            const $tab = $grid.closest('.flavor-dashboard-tab');
            const emptyHtml = `
                <div class="flavor-empty-state">
                    <div class="flavor-empty-icon">✓</div>
                    <h3>No hay elementos para mostrar</h3>
                    <p>Todos los elementos han sido procesados.</p>
                </div>
            `;
            $grid.replaceWith(emptyHtml);
        },

        /**
         * Mostrar notificacion toast
         */
        showToast: function(mensaje, tipo = 'success') {
            // Eliminar toast existente
            $('.flavor-toast').remove();

            const $toast = $(`<div class="flavor-toast flavor-toast--${tipo}">${mensaje}</div>`);
            $('body').append($toast);

            // Auto-ocultar despues de 3 segundos
            setTimeout(() => {
                $toast.addClass('flavor-toast--hiding');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        }
    };

    // CSS adicional para menu de estados (inline)
    const menuEstilos = `
        <style>
        .flavor-menu-estados {
            position: absolute;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            z-index: 100;
            min-width: 160px;
            margin-top: 0.25rem;
            overflow: hidden;
        }
        .flavor-menu-estados button {
            display: block;
            width: 100%;
            padding: 0.625rem 1rem;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            color: #374151;
            transition: background 0.15s;
        }
        .flavor-menu-estados button:hover {
            background: #f3f4f6;
        }
        .flavor-menu-estados button.activo {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 500;
        }
        </style>
    `;
    $('head').append(menuEstilos);

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        CampaniasDashboard.init();
    });

})(jQuery);
