/**
 * Kulturaka - JavaScript Frontend
 * Red Cultural Descentralizada
 */

(function($) {
    'use strict';

    const Kulturaka = {
        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.initModals();
            this.initFilters();
            this.initGratitudeWall();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Botones de propuesta
            $(document).on('click', '.btn-enviar-propuesta', this.handleEnviarPropuesta);

            // Botones de agradecimiento
            $(document).on('click', '.btn-enviar-agradecimiento', this.handleEnviarAgradecimiento);

            // Likes en agradecimientos
            $(document).on('click', '.btn-like-agradecimiento', this.handleLikeAgradecimiento);

            // Ver detalles de nodo
            $(document).on('click', '.btn-ver-nodo', this.handleVerNodo);
        },

        /**
         * Inicializar modales
         */
        initModals: function() {
            // Cerrar modal al hacer clic fuera
            $(document).on('click', '.kulturaka-modal-overlay', function(e) {
                if (e.target === this) {
                    Kulturaka.closeModal($(this));
                }
            });

            // Cerrar modal con botón
            $(document).on('click', '.kulturaka-modal-close', function() {
                Kulturaka.closeModal($(this).closest('.kulturaka-modal-overlay'));
            });

            // Cerrar con ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.kulturaka-modal-overlay.active').each(function() {
                        Kulturaka.closeModal($(this));
                    });
                }
            });
        },

        /**
         * Abrir modal
         */
        openModal: function($modal) {
            $modal.addClass('active');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Cerrar modal
         */
        closeModal: function($modal) {
            $modal.removeClass('active');
            $('body').css('overflow', '');
        },

        /**
         * Inicializar filtros
         */
        initFilters: function() {
            // Filtros por tipo
            $(document).on('click', '.kulturaka-filter-btn', function() {
                const $btn = $(this);
                const tipo = $btn.data('tipo');
                const $container = $btn.closest('.kulturaka-filter-group');

                $container.find('.kulturaka-filter-btn').removeClass('active');
                $btn.addClass('active');

                Kulturaka.applyFilters();
            });

            // Filtro de ciudad
            $(document).on('change', '#kulturaka-filter-ciudad', function() {
                Kulturaka.applyFilters();
            });
        },

        /**
         * Aplicar filtros
         */
        applyFilters: function() {
            const tipo = $('.kulturaka-filter-btn.active').data('tipo') || 'todos';
            const ciudad = $('#kulturaka-filter-ciudad').val() || '';

            $('.kulturaka-nodo-item').each(function() {
                const $item = $(this);
                let visible = true;

                if (tipo !== 'todos' && $item.data('tipo') !== tipo) {
                    visible = false;
                }

                if (ciudad && $item.data('ciudad') !== ciudad) {
                    visible = false;
                }

                $item.toggle(visible);
            });

            // Actualizar contadores de grupos
            $('.kulturaka-ciudad-grupo').each(function() {
                const $grupo = $(this);
                const visibles = $grupo.find('.kulturaka-nodo-item:visible').length;
                $grupo.toggle(visibles > 0);
            });
        },

        /**
         * Inicializar muro de agradecimientos
         */
        initGratitudeWall: function() {
            // Cargar más agradecimientos al hacer scroll
            const $wall = $('.kulturaka-gratitude-wall');
            if ($wall.length && $wall.data('load-more')) {
                let loading = false;
                let page = 1;

                $(window).on('scroll', function() {
                    if (loading) return;

                    const scrollBottom = $(window).scrollTop() + $(window).height();
                    const wallBottom = $wall.offset().top + $wall.height();

                    if (scrollBottom > wallBottom - 200) {
                        loading = true;
                        page++;
                        Kulturaka.loadMoreGratitude(page, function() {
                            loading = false;
                        });
                    }
                });
            }
        },

        /**
         * Cargar más agradecimientos
         */
        loadMoreGratitude: function(page, callback) {
            $.ajax({
                url: kulturaka.resturl + 'agradecimientos',
                method: 'GET',
                data: { page: page },
                success: function(response) {
                    if (response.length) {
                        response.forEach(function(item) {
                            const html = Kulturaka.renderGratitudeItem(item);
                            $('.kulturaka-gratitude-wall').append(html);
                        });
                    }
                    if (callback) callback();
                },
                error: function() {
                    if (callback) callback();
                }
            });
        },

        /**
         * Renderizar item de agradecimiento
         */
        renderGratitudeItem: function(item) {
            return `
                <div class="kulturaka-gratitude-item kulturaka-fade-in">
                    <span class="kulturaka-gratitude-emoji">${item.emoji || '❤️'}</span>
                    <div class="kulturaka-gratitude-content">
                        <strong>${item.autor_nombre || 'Anónimo'}</strong>
                        ${item.destinatario_nombre ? '→ ' + item.destinatario_nombre : ''}
                        <p class="kulturaka-gratitude-message">${item.mensaje}</p>
                        <div class="kulturaka-gratitude-meta">
                            ${item.tiempo_transcurrido}
                            <button class="btn-like-agradecimiento" data-id="${item.id}">
                                ❤️ ${item.likes_count || 0}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Manejar envío de propuesta
         */
        handleEnviarPropuesta: function(e) {
            e.preventDefault();
            const $form = $(this).closest('form');
            const data = $form.serialize();

            $.ajax({
                url: kulturaka.resturl + 'propuestas',
                method: 'POST',
                data: data,
                headers: {
                    'X-WP-Nonce': kulturaka.nonce
                },
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true).text('Enviando...');
                },
                success: function(response) {
                    if (response.success) {
                        Kulturaka.showNotification('Propuesta enviada correctamente', 'success');
                        Kulturaka.closeModal($form.closest('.kulturaka-modal-overlay'));
                        $form[0].reset();
                    } else {
                        Kulturaka.showNotification(response.message || 'Error al enviar', 'error');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Error de conexión';
                    Kulturaka.showNotification(msg, 'error');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Enviar propuesta');
                }
            });
        },

        /**
         * Manejar envío de agradecimiento
         */
        handleEnviarAgradecimiento: function(e) {
            e.preventDefault();
            const $form = $(this).closest('form');
            const data = $form.serialize();

            $.ajax({
                url: kulturaka.resturl + 'agradecimientos',
                method: 'POST',
                data: data,
                headers: {
                    'X-WP-Nonce': kulturaka.nonce
                },
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        Kulturaka.showNotification('¡Agradecimiento enviado!', 'success');
                        Kulturaka.closeModal($form.closest('.kulturaka-modal-overlay'));
                        $form[0].reset();

                        // Añadir al muro si está visible
                        if ($('.kulturaka-gratitude-wall').length) {
                            location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    Kulturaka.showNotification('Error al enviar', 'error');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        },

        /**
         * Manejar like en agradecimiento
         */
        handleLikeAgradecimiento: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const id = $btn.data('id');

            $.ajax({
                url: kulturaka.resturl + 'agradecimientos/' + id + '/like',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': kulturaka.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const count = parseInt($btn.text().match(/\d+/) || 0) + 1;
                        $btn.html('❤️ ' + count);
                        $btn.addClass('liked');
                    }
                }
            });
        },

        /**
         * Manejar ver nodo
         */
        handleVerNodo: function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const $panel = $('#kulturaka-panel-nodo');

            $.ajax({
                url: kulturaka.resturl + 'nodos/' + id,
                method: 'GET',
                beforeSend: function() {
                    $panel.html('<div class="kulturaka-loading">Cargando...</div>').show();
                },
                success: function(response) {
                    $panel.html(Kulturaka.renderNodoPanel(response));
                },
                error: function() {
                    $panel.html('<p>Error al cargar datos</p>');
                }
            });
        },

        /**
         * Renderizar panel de nodo
         */
        renderNodoPanel: function(nodo) {
            return `
                <div class="kulturaka-nodo-panel">
                    <button class="kulturaka-panel-close">&times;</button>
                    <div class="kulturaka-nodo-header" style="background-color: ${nodo.color_marca || '#ec4899'}">
                        ${nodo.imagen_principal ? `<img src="${nodo.imagen_principal}" alt="">` : ''}
                        <h3>${nodo.nombre}</h3>
                    </div>
                    <div class="kulturaka-nodo-body">
                        <p>${nodo.descripcion || ''}</p>
                        <div class="kulturaka-nodo-stats">
                            <span>📅 ${nodo.eventos_realizados} eventos</span>
                            <span>🎤 ${nodo.artistas_apoyados} artistas</span>
                            <span>⭐ ${parseFloat(nodo.indice_cooperacion).toFixed(1)}</span>
                        </div>
                        ${nodo.acepta_propuestas ? '<button class="kulturaka-btn kulturaka-btn-primary btn-proponer-nodo" data-id="' + nodo.id + '">Enviar propuesta</button>' : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            const $notification = $('<div class="kulturaka-notification kulturaka-notification-' + type + '">' + message + '</div>');

            $('body').append($notification);

            setTimeout(function() {
                $notification.addClass('show');
            }, 10);

            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        Kulturaka.init();
    });

    // Exponer globalmente
    window.Kulturaka = Kulturaka;

})(jQuery);
