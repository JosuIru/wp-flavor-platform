/**
 * JavaScript del módulo Presupuestos Participativos - Frontend
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorPresupuestos = {
        config: window.flavorPresupuestosConfig || {},
        paginaActual: 1,

        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
            this.initUploadArea();
        },

        /**
         * Vincula los eventos
         */
        bindEvents: function() {
            // Votación
            $(document).on('click', '.flavor-pp-btn-votar', this.handleVotar.bind(this));
            $(document).on('click', '.flavor-pp-btn-quitar-voto', this.handleQuitarVoto.bind(this));

            // Formulario de propuesta
            $(document).on('submit', '#flavor-pp-form-propuesta', this.handleEnviarPropuesta.bind(this));
            $(document).on('click', '#pp-guardar-borrador', this.handleGuardarBorrador.bind(this));
            $(document).on('click', '#pp-nueva-propuesta', this.handleNuevaPropuesta.bind(this));

            // Filtros
            $(document).on('change', '#pp-filtro-categoria, #pp-filtro-orden', this.handleFiltrar.bind(this));
            $(document).on('change', '#pp-filtro-categoria-votacion', this.handleFiltrarVotacion.bind(this));
            $(document).on('input', '#pp-busqueda', this.debounce(this.handleBuscar.bind(this), 300));

            // Cargar más
            $(document).on('click', '#pp-cargar-mas', this.handleCargarMas.bind(this));

            // Modal de detalles
            $(document).on('click', '.flavor-pp-ver-detalles', this.handleVerDetalles.bind(this));
            $(document).on('click', '.flavor-pp-modal-overlay, .flavor-pp-modal-cerrar', this.cerrarModal.bind(this));

            // Toggle vista
            $(document).on('click', '.flavor-pp-toggle-vista button', this.handleToggleVista.bind(this));

            // Mis propuestas
            $(document).on('click', '.flavor-pp-eliminar-propuesta', this.handleEliminarPropuesta.bind(this));
            $(document).on('click', '.flavor-pp-modal-confirmar', this.handleConfirmarEliminar.bind(this));
            $(document).on('click', '.flavor-pp-modal-cancelar', this.cerrarModalEliminar.bind(this));

            // Escape para cerrar modales
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    FlavorPresupuestos.cerrarModal();
                    FlavorPresupuestos.cerrarModalEliminar();
                }
            });
        },

        /**
         * Maneja el voto de un proyecto
         */
        handleVotar: function(e) {
            e.preventDefault();
            const $boton = $(e.currentTarget);
            const proyectoId = $boton.data('proyecto');

            if ($boton.prop('disabled')) {
                return;
            }

            if (!confirm(this.config.strings?.confirmVoto || '¿Confirmas tu voto?')) {
                return;
            }

            $boton.prop('disabled', true).html('<span class="flavor-pp-loading"></span>');

            this.ajaxRequest('pp_votar_proyecto', {
                proyecto_id: proyectoId
            })
            .done(function(response) {
                if (response.success) {
                    FlavorPresupuestos.mostrarNotificacion(
                        FlavorPresupuestos.config.strings?.votoRegistrado || '¡Voto registrado!',
                        'success'
                    );
                    FlavorPresupuestos.actualizarUIVoto($boton, proyectoId, true);
                    FlavorPresupuestos.actualizarContadorVotos(-1);
                } else {
                    FlavorPresupuestos.mostrarNotificacion(response.data?.message || 'Error', 'error');
                    $boton.prop('disabled', false).html('<span class="dashicons dashicons-thumbs-up"></span> Votar');
                }
            })
            .fail(function() {
                FlavorPresupuestos.mostrarNotificacion(
                    FlavorPresupuestos.config.strings?.error || 'Error de conexión',
                    'error'
                );
                $boton.prop('disabled', false).html('<span class="dashicons dashicons-thumbs-up"></span> Votar');
            });
        },

        /**
         * Maneja quitar un voto
         */
        handleQuitarVoto: function(e) {
            e.preventDefault();
            const $boton = $(e.currentTarget);
            const proyectoId = $boton.data('proyecto');

            $boton.prop('disabled', true);

            this.ajaxRequest('pp_quitar_voto', {
                proyecto_id: proyectoId
            })
            .done(function(response) {
                if (response.success) {
                    FlavorPresupuestos.actualizarUIVoto($boton, proyectoId, false);
                    FlavorPresupuestos.actualizarContadorVotos(1);
                } else {
                    FlavorPresupuestos.mostrarNotificacion(response.data?.message || 'Error', 'error');
                }
                $boton.prop('disabled', false);
            })
            .fail(function() {
                FlavorPresupuestos.mostrarNotificacion('Error de conexión', 'error');
                $boton.prop('disabled', false);
            });
        },

        /**
         * Actualiza la UI después de votar/quitar voto
         */
        actualizarUIVoto: function($boton, proyectoId, votado) {
            const $card = $boton.closest('.flavor-pp-proyecto-votacion, .flavor-pp-proyecto-card');
            const $contadorVotos = $card.find('.flavor-pp-votos-count, .flavor-pp-votos-actuales .count');
            const contadorActual = parseInt($contadorVotos.text()) || 0;

            if (votado) {
                $card.addClass('votado flavor-pp-votado');
                $contadorVotos.text(contadorActual + 1);

                $boton
                    .removeClass('flavor-pp-btn-votar')
                    .addClass('flavor-pp-btn-votado flavor-pp-btn-quitar-voto')
                    .html(`
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="texto-votado">Votado</span>
                        <span class="texto-quitar">Quitar voto</span>
                    `);
            } else {
                $card.removeClass('votado flavor-pp-votado');
                $contadorVotos.text(Math.max(0, contadorActual - 1));

                $boton
                    .removeClass('flavor-pp-btn-votado flavor-pp-btn-quitar-voto')
                    .addClass('flavor-pp-btn-votar')
                    .html('<span class="dashicons dashicons-thumbs-up"></span> Votar este proyecto');
            }
        },

        /**
         * Actualiza el contador de votos disponibles
         */
        actualizarContadorVotos: function(delta) {
            const $contador = $('.flavor-pp-votos-numero');
            const $emitidos = $('.flavor-pp-votos-count');
            const $disponibles = $('.flavor-pp-votos-disponibles');
            const $botonesVotar = $('.flavor-pp-btn-votar');

            if ($contador.length) {
                const actual = parseInt($contador.text()) || 0;
                const nuevo = Math.max(0, actual + delta);
                $contador.text(nuevo);

                if (nuevo === 0) {
                    $disponibles.addClass('agotados');
                    $botonesVotar.prop('disabled', true);
                } else {
                    $disponibles.removeClass('agotados');
                    $botonesVotar.prop('disabled', false);
                }
            }

            if ($emitidos.length) {
                const emitidos = parseInt($emitidos.text()) || 0;
                $emitidos.text(Math.max(0, emitidos - delta));
            }
        },

        /**
         * Maneja el envío de propuesta
         */
        handleEnviarPropuesta: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submitBtn = $form.find('#pp-enviar-propuesta');

            if (!this.validarFormulario($form)) {
                return;
            }

            $submitBtn.prop('disabled', true).html('<span class="flavor-pp-loading"></span> Enviando...');

            const datos = {
                titulo: $form.find('#pp-titulo').val(),
                descripcion: $form.find('#pp-descripcion').val(),
                categoria: $form.find('#pp-categoria').val(),
                presupuesto: $form.find('#pp-presupuesto').val(),
                ubicacion: $form.find('#pp-ubicacion').val()
            };

            this.ajaxRequest('pp_proponer_proyecto', datos)
            .done(function(response) {
                if (response.success) {
                    $form.hide();
                    $('#pp-mensaje-exito').show();
                } else {
                    FlavorPresupuestos.mostrarNotificacion(response.data?.message || 'Error', 'error');
                    $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Enviar propuesta');
                }
            })
            .fail(function() {
                FlavorPresupuestos.mostrarNotificacion('Error de conexión', 'error');
                $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Enviar propuesta');
            });
        },

        /**
         * Valida el formulario de propuesta
         */
        validarFormulario: function($form) {
            let valido = true;
            const presupuesto = parseFloat($form.find('#pp-presupuesto').val());
            const min = parseFloat(this.config.presupuestoMinimo) || 1000;
            const max = parseFloat(this.config.presupuestoMaximo) || 50000;

            // Validar campos requeridos
            $form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    valido = false;
                } else {
                    $(this).removeClass('error');
                }
            });

            // Validar presupuesto
            if (presupuesto < min || presupuesto > max) {
                $form.find('#pp-presupuesto').addClass('error');
                this.mostrarNotificacion(`El presupuesto debe estar entre ${min}€ y ${max}€`, 'error');
                valido = false;
            }

            // Validar checkbox de condiciones
            if (!$form.find('[name="acepto_condiciones"]').is(':checked')) {
                this.mostrarNotificacion('Debes aceptar las condiciones', 'error');
                valido = false;
            }

            return valido;
        },

        /**
         * Guarda la propuesta como borrador
         */
        handleGuardarBorrador: function(e) {
            e.preventDefault();
            this.mostrarNotificacion('Funcionalidad de borrador en desarrollo', 'info');
        },

        /**
         * Reinicia el formulario para nueva propuesta
         */
        handleNuevaPropuesta: function(e) {
            e.preventDefault();
            $('#flavor-pp-form-propuesta')[0].reset();
            $('#flavor-pp-form-propuesta').show();
            $('#pp-mensaje-exito').hide();
            $('#pp-preview-imagenes').empty();
        },

        /**
         * Maneja el filtrado por categoría/orden
         */
        handleFiltrar: function(e) {
            this.paginaActual = 1;
            this.cargarProyectos();
        },

        /**
         * Maneja el filtrado en la interfaz de votación
         */
        handleFiltrarVotacion: function(e) {
            const categoria = $(e.currentTarget).val();
            const $proyectos = $('.flavor-pp-proyecto-votacion');

            if (!categoria) {
                $proyectos.show();
            } else {
                $proyectos.each(function() {
                    const cat = $(this).data('categoria');
                    $(this).toggle(cat === categoria);
                });
            }
        },

        /**
         * Maneja la búsqueda
         */
        handleBuscar: function(e) {
            this.paginaActual = 1;
            this.cargarProyectos();
        },

        /**
         * Carga más proyectos (paginación)
         */
        handleCargarMas: function(e) {
            e.preventDefault();
            this.paginaActual++;
            this.cargarProyectos(true);
        },

        /**
         * Carga proyectos vía AJAX
         */
        cargarProyectos: function(append) {
            const $grid = $('.flavor-pp-grid');
            const $cargarMas = $('.flavor-pp-cargar-mas');
            const $boton = $('#pp-cargar-mas');

            append = append || false;

            if (!append) {
                $grid.html('<div class="flavor-pp-loading-full"><span class="flavor-pp-loading"></span></div>');
            }

            $boton.prop('disabled', true);

            this.ajaxRequest('pp_cargar_proyectos', {
                pagina: this.paginaActual,
                limite: 12,
                categoria: $('#pp-filtro-categoria').val() || '',
                ordenar: $('#pp-filtro-orden').val() || 'votos',
                busqueda: $('#pp-busqueda').val() || ''
            })
            .done(function(response) {
                if (response.success) {
                    if (!append) {
                        $grid.empty();
                    }

                    if (response.data.proyectos.length === 0 && !append) {
                        $grid.html('<div class="flavor-pp-vacio"><span class="dashicons dashicons-portfolio"></span><p>No hay proyectos que coincidan con tu búsqueda.</p></div>');
                        $cargarMas.hide();
                    } else {
                        response.data.proyectos.forEach(function(proyecto) {
                            $grid.append(FlavorPresupuestos.renderProyectoCard(proyecto));
                        });

                        if (response.data.hay_mas) {
                            $cargarMas.show();
                        } else {
                            $cargarMas.hide();
                        }
                    }
                }
            })
            .always(function() {
                $boton.prop('disabled', false);
            });
        },

        /**
         * Renderiza una tarjeta de proyecto
         */
        renderProyectoCard: function(proyecto) {
            return `
                <article class="flavor-pp-proyecto-card" data-id="${proyecto.id}">
                    <div class="flavor-pp-proyecto-header">
                        <span class="flavor-pp-categoria flavor-pp-cat-${proyecto.categoria}">
                            ${this.capitalizar(proyecto.categoria)}
                        </span>
                    </div>
                    <h3 class="flavor-pp-proyecto-titulo">
                        <a href="?proyecto=${proyecto.id}">${this.escapeHtml(proyecto.titulo)}</a>
                    </h3>
                    <p class="flavor-pp-proyecto-descripcion">${this.escapeHtml(proyecto.descripcion)}</p>
                    <div class="flavor-pp-proyecto-meta">
                        <div class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-money-alt"></span>
                            <span class="flavor-pp-presupuesto">${proyecto.presupuesto_fmt}</span>
                        </div>
                        ${proyecto.ubicacion ? `
                        <div class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-location"></span>
                            <span>${this.escapeHtml(proyecto.ubicacion)}</span>
                        </div>
                        ` : ''}
                    </div>
                    <div class="flavor-pp-proyecto-footer">
                        <div class="flavor-pp-votos">
                            <span class="dashicons dashicons-thumbs-up"></span>
                            <span class="flavor-pp-votos-count">${proyecto.votos}</span>
                            <span class="flavor-pp-votos-label">votos</span>
                        </div>
                        <a href="?proyecto=${proyecto.id}" class="flavor-pp-btn flavor-pp-btn-ver">Ver más</a>
                    </div>
                </article>
            `;
        },

        /**
         * Maneja ver detalles de proyecto en modal
         */
        handleVerDetalles: function(e) {
            e.preventDefault();
            const proyectoId = $(e.currentTarget).data('proyecto');
            const $modal = $('#flavor-pp-modal-proyecto');
            const $body = $modal.find('.flavor-pp-modal-body');

            $body.html('<div class="flavor-pp-loading-full"><span class="flavor-pp-loading"></span></div>');
            $modal.show();

            this.ajaxRequest('pp_obtener_proyecto', {
                proyecto_id: proyectoId
            })
            .done(function(response) {
                if (response.success) {
                    const p = response.data.proyecto;
                    $body.html(`
                        <span class="flavor-pp-categoria flavor-pp-cat-${p.categoria}">${p.categoria_label}</span>
                        <h2>${FlavorPresupuestos.escapeHtml(p.titulo)}</h2>
                        <div class="flavor-pp-proyecto-meta" style="margin: 16px 0;">
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-money-alt"></span>
                                <span><strong>${p.presupuesto_fmt}</strong></span>
                            </div>
                            ${p.ubicacion ? `
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-location"></span>
                                <span>${FlavorPresupuestos.escapeHtml(p.ubicacion)}</span>
                            </div>
                            ` : ''}
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-thumbs-up"></span>
                                <span><strong>${p.votos}</strong> votos</span>
                            </div>
                        </div>
                        <div style="line-height: 1.6; color: #374151;">
                            ${FlavorPresupuestos.escapeHtml(p.descripcion).replace(/\n/g, '<br>')}
                        </div>
                        ${p.proponente ? `
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px;">
                            <img src="${p.proponente.avatar}" alt="" style="width: 40px; height: 40px; border-radius: 50%;">
                            <div>
                                <div style="font-weight: 500;">${FlavorPresupuestos.escapeHtml(p.proponente.nombre)}</div>
                                <div style="font-size: 13px; color: #6b7280;">Proponente</div>
                            </div>
                        </div>
                        ` : ''}
                    `);
                } else {
                    $body.html('<p>No se pudo cargar el proyecto.</p>');
                }
            })
            .fail(function() {
                $body.html('<p>Error de conexión.</p>');
            });
        },

        /**
         * Cierra el modal de detalles
         */
        cerrarModal: function() {
            $('#flavor-pp-modal-proyecto').hide();
        },

        /**
         * Toggle entre vista grid y lista
         */
        handleToggleVista: function(e) {
            const $btn = $(e.currentTarget);
            const vista = $btn.data('vista');

            $('.flavor-pp-toggle-vista button').removeClass('active');
            $btn.addClass('active');

            const $grid = $('.flavor-pp-grid-votacion');
            $grid.removeClass('flavor-pp-grid-2 flavor-pp-grid-3 flavor-pp-grid-lista');

            if (vista === 'lista') {
                $grid.addClass('flavor-pp-grid-lista');
            } else {
                $grid.addClass('flavor-pp-grid-2');
            }
        },

        /**
         * Maneja el clic en eliminar propuesta
         */
        handleEliminarPropuesta: function(e) {
            e.preventDefault();
            const propuestaId = $(e.currentTarget).data('id');
            $('#flavor-pp-modal-eliminar').data('propuesta-id', propuestaId).show();
        },

        /**
         * Confirma la eliminación de propuesta
         */
        handleConfirmarEliminar: function(e) {
            const $modal = $('#flavor-pp-modal-eliminar');
            const propuestaId = $modal.data('propuesta-id');
            const $boton = $(e.currentTarget);

            $boton.prop('disabled', true).text('Eliminando...');

            this.ajaxRequest('pp_eliminar_propuesta', {
                proyecto_id: propuestaId
            })
            .done(function(response) {
                if (response.success) {
                    $(`.flavor-pp-mi-propuesta[data-id="${propuestaId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    FlavorPresupuestos.cerrarModalEliminar();
                    FlavorPresupuestos.mostrarNotificacion('Propuesta eliminada', 'success');
                } else {
                    FlavorPresupuestos.mostrarNotificacion(response.data?.message || 'Error', 'error');
                }
            })
            .fail(function() {
                FlavorPresupuestos.mostrarNotificacion('Error de conexión', 'error');
            })
            .always(function() {
                $boton.prop('disabled', false).text('Sí, eliminar');
            });
        },

        /**
         * Cierra el modal de eliminar
         */
        cerrarModalEliminar: function() {
            $('#flavor-pp-modal-eliminar').hide();
        },

        /**
         * Inicializa el área de subida de imágenes
         */
        initUploadArea: function() {
            const $area = $('#pp-upload-area');
            const $input = $('#pp-imagenes');
            const $preview = $('#pp-preview-imagenes');

            if (!$area.length) return;

            // Click para seleccionar
            $area.on('click', function(e) {
                if ($(e.target).is('img, .flavor-pp-remove-img')) return;
                $input.click();
            });

            // Drag & drop
            $area.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $area.on('dragleave drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            $area.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                FlavorPresupuestos.handleFiles(files);
            });

            $input.on('change', function() {
                FlavorPresupuestos.handleFiles(this.files);
            });
        },

        /**
         * Maneja los archivos de imagen
         */
        handleFiles: function(files) {
            const $preview = $('#pp-preview-imagenes');
            const maxFiles = 5;
            const maxSize = 2 * 1024 * 1024; // 2MB
            const currentCount = $preview.find('img').length;

            Array.from(files).slice(0, maxFiles - currentCount).forEach(function(file) {
                if (!file.type.startsWith('image/')) {
                    FlavorPresupuestos.mostrarNotificacion('Solo se permiten imágenes', 'error');
                    return;
                }

                if (file.size > maxSize) {
                    FlavorPresupuestos.mostrarNotificacion('La imagen supera los 2MB', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const $img = $('<div class="flavor-pp-preview-item"><img src="' + e.target.result + '"><button type="button" class="flavor-pp-remove-img">&times;</button></div>');
                    $preview.append($img);

                    $img.find('.flavor-pp-remove-img').on('click', function() {
                        $img.remove();
                    });
                };
                reader.readAsDataURL(file);
            });
        },

        /**
         * Realiza petición AJAX
         */
        ajaxRequest: function(action, data) {
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

        /**
         * Muestra una notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            tipo = tipo || 'info';

            const $notificacion = $(`
                <div class="flavor-pp-toast flavor-pp-toast-${tipo}">
                    ${this.escapeHtml(mensaje)}
                </div>
            `);

            $('body').append($notificacion);

            setTimeout(function() {
                $notificacion.addClass('show');
            }, 10);

            setTimeout(function() {
                $notificacion.removeClass('show');
                setTimeout(function() {
                    $notificacion.remove();
                }, 300);
            }, 3000);
        },

        /**
         * Utilidades
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        capitalizar: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // CSS para notificaciones toast
    $('<style>')
        .text(`
            .flavor-pp-toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 14px 20px;
                border-radius: 8px;
                color: #fff;
                font-size: 14px;
                z-index: 100001;
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.3s, transform 0.3s;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .flavor-pp-toast.show {
                opacity: 1;
                transform: translateY(0);
            }
            .flavor-pp-toast-success { background: #16a34a; }
            .flavor-pp-toast-error { background: #dc2626; }
            .flavor-pp-toast-info { background: #2563eb; }
            .flavor-pp-loading-full {
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 60px;
            }
            #pp-upload-area.dragover {
                border-color: #2563eb;
                background: rgba(37, 99, 235, 0.05);
            }
            .flavor-pp-preview-item {
                position: relative;
                display: inline-block;
            }
            .flavor-pp-remove-img {
                position: absolute;
                top: -8px;
                right: -8px;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: #dc2626;
                color: #fff;
                border: none;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
            }
            .flavor-pp-grid-lista {
                grid-template-columns: 1fr !important;
            }
            .flavor-pp-grid-lista .flavor-pp-proyecto-votacion {
                display: flex;
                align-items: stretch;
            }
            .flavor-pp-grid-lista .flavor-pp-proyecto-content {
                flex: 1;
            }
            .flavor-pp-grid-lista .flavor-pp-proyecto-acciones {
                flex-direction: column;
                justify-content: center;
                min-width: 200px;
            }
            .flavor-pp-input.error,
            .flavor-pp-select.error,
            .flavor-pp-textarea.error {
                border-color: #dc2626 !important;
            }
        `)
        .appendTo('head');

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorPresupuestos.init();
    });

})(jQuery);
