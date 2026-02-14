/**
 * JavaScript del modulo Presupuestos Participativos
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var PP = {
        config: window.flavorPresupuestosConfig || {},

        init: function() {
            this.bindEvents();
            this.initUploadArea();
            this.initFilters();
        },

        bindEvents: function() {
            // Votar proyecto
            $(document).on('click', '.flavor-pp-btn-votar', this.handleVotar.bind(this));

            // Quitar voto
            $(document).on('click', '.flavor-pp-btn-quitar-voto', this.handleQuitarVoto.bind(this));

            // Enviar propuesta
            $(document).on('submit', '#flavor-pp-form-propuesta', this.handleEnviarPropuesta.bind(this));

            // Eliminar propuesta
            $(document).on('click', '.flavor-pp-btn-eliminar', this.handleEliminarPropuesta.bind(this));

            // Editar propuesta
            $(document).on('click', '.flavor-pp-btn-editar', this.handleEditarPropuesta.bind(this));
        },

        // Votar proyecto
        handleVotar: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var proyectoId = $btn.data('proyecto-id');

            if ($btn.prop('disabled')) {
                return;
            }

            // Confirmar voto
            if (!confirm(this.config.strings.confirmVoto)) {
                return;
            }

            this.setLoading($btn, true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pp_votar_proyecto',
                    nonce: this.config.nonce,
                    proyecto_id: proyectoId
                },
                success: function(response) {
                    if (response.success) {
                        PP.showMessage('exito', PP.config.strings.votoRegistrado);
                        PP.updateProjectCard($btn.closest('.flavor-pp-proyecto'), true);
                        PP.updateVotosContador(-1);
                    } else {
                        PP.showMessage('error', response.data.message || PP.config.strings.error);
                    }
                },
                error: function() {
                    PP.showMessage('error', PP.config.strings.error);
                },
                complete: function() {
                    PP.setLoading($btn, false);
                }
            });
        },

        // Quitar voto
        handleQuitarVoto: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var proyectoId = $btn.data('proyecto-id');

            this.setLoading($btn, true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pp_quitar_voto',
                    nonce: this.config.nonce,
                    proyecto_id: proyectoId
                },
                success: function(response) {
                    if (response.success) {
                        PP.updateProjectCard($btn.closest('.flavor-pp-proyecto'), false);
                        PP.updateVotosContador(1);
                    } else {
                        PP.showMessage('error', response.data.message || PP.config.strings.error);
                    }
                },
                error: function() {
                    PP.showMessage('error', PP.config.strings.error);
                },
                complete: function() {
                    PP.setLoading($btn, false);
                }
            });
        },

        // Actualizar tarjeta de proyecto despues de votar
        updateProjectCard: function($card, votado) {
            var proyectoId = $card.data('id');

            if (votado) {
                $card.addClass('votado');
                $card.find('.flavor-pp-btn-votar')
                    .removeClass('flavor-pp-btn-votar flavor-pp-boton-primario')
                    .addClass('flavor-pp-btn-quitar-voto flavor-pp-boton-secundario')
                    .html('<span class="dashicons dashicons-heart"></span> Quitar voto');
            } else {
                $card.removeClass('votado');
                $card.find('.flavor-pp-btn-quitar-voto')
                    .removeClass('flavor-pp-btn-quitar-voto flavor-pp-boton-secundario')
                    .addClass('flavor-pp-btn-votar flavor-pp-boton-primario')
                    .html('<span class="dashicons dashicons-heart"></span> Votar');
            }

            // Actualizar contador de votos en la tarjeta
            var $votosSpan = $card.find('.flavor-pp-meta-item .dashicons-heart').parent();
            if ($votosSpan.length) {
                var votosActuales = parseInt($votosSpan.text()) || 0;
                var nuevosVotos = votado ? votosActuales + 1 : votosActuales - 1;
                $votosSpan.html('<span class="dashicons dashicons-heart"></span> ' + nuevosVotos);
            }
        },

        // Actualizar contador de votos disponibles
        updateVotosContador: function(cambio) {
            var $contador = $('.flavor-pp-votos-texto');
            if ($contador.length) {
                var match = $contador.text().match(/(\d+)\s+de\s+(\d+)/);
                if (match) {
                    var disponibles = parseInt(match[1]) + cambio;
                    var total = parseInt(match[2]);
                    $contador.text('Votos disponibles: ' + disponibles + ' de ' + total);

                    // Actualizar barra de progreso
                    var progreso = ((total - disponibles) / total) * 100;
                    $('.flavor-pp-votos-progreso').css('width', progreso + '%');

                    // Deshabilitar/habilitar botones de votar
                    if (disponibles <= 0) {
                        $('.flavor-pp-btn-votar').prop('disabled', true);
                    } else {
                        $('.flavor-pp-btn-votar').prop('disabled', false);
                    }
                }
            }
        },

        // Enviar propuesta
        handleEnviarPropuesta: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            // Validaciones
            var descripcion = $form.find('#pp-descripcion').val();
            if (descripcion.length < 50) {
                this.showFormMessage($form, 'error', 'La descripcion debe tener al menos 50 caracteres.');
                return;
            }

            var presupuesto = parseFloat($form.find('#pp-presupuesto').val());
            if (presupuesto < this.config.presupuestoMinimo || presupuesto > this.config.presupuestoMaximo) {
                this.showFormMessage($form, 'error', 'El presupuesto debe estar entre ' + 
                    this.config.presupuestoMinimo.toLocaleString() + ' y ' + 
                    this.config.presupuestoMaximo.toLocaleString() + ' EUR.');
                return;
            }

            this.setLoading($btn, true);

            var formData = new FormData($form[0]);
            formData.append('action', 'pp_proponer_proyecto');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        PP.showFormMessage($form, 'exito', PP.config.strings.propuestaEnviada);
                        $form[0].reset();
                        PP.resetUploadPreview();

                        // Redirigir a mis propuestas despues de 2 segundos
                        setTimeout(function() {
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            }
                        }, 2000);
                    } else {
                        PP.showFormMessage($form, 'error', response.data.message || PP.config.strings.error);
                    }
                },
                error: function() {
                    PP.showFormMessage($form, 'error', PP.config.strings.error);
                },
                complete: function() {
                    PP.setLoading($btn, false);
                }
            });
        },

        // Eliminar propuesta
        handleEliminarPropuesta: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var propuestaId = $btn.data('id');

            if (!confirm('Estas seguro de que quieres eliminar esta propuesta? Esta accion no se puede deshacer.')) {
                return;
            }

            this.setLoading($btn, true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pp_eliminar_propuesta',
                    nonce: this.config.nonce,
                    propuesta_id: propuestaId
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('.flavor-pp-propuesta-card').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        PP.showMessage('error', response.data.message || PP.config.strings.error);
                    }
                },
                error: function() {
                    PP.showMessage('error', PP.config.strings.error);
                },
                complete: function() {
                    PP.setLoading($btn, false);
                }
            });
        },

        // Editar propuesta (redirigir al formulario)
        handleEditarPropuesta: function(e) {
            e.preventDefault();
            var propuestaId = $(e.currentTarget).data('id');
            window.location.href = window.location.pathname + '?editar=' + propuestaId;
        },

        // Inicializar area de upload
        initUploadArea: function() {
            var $uploadArea = $('#pp-upload-area');
            var $input = $('#pp-imagen');
            var $preview = $('.flavor-pp-upload-preview');
            var $placeholder = $('.flavor-pp-upload-placeholder');
            var $previewImg = $('#pp-imagen-preview');

            if (!$uploadArea.length) return;

            // Drag and drop
            $uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $uploadArea.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            $uploadArea.on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $input[0].files = files;
                    $input.trigger('change');
                }
            });

            // Preview de imagen
            $input.on('change', function() {
                var file = this.files[0];
                if (file) {
                    // Validar tipo
                    if (!file.type.match(/image\/(jpeg|png|webp)/)) {
                        alert('Solo se permiten imagenes JPG, PNG o WebP.');
                        this.value = '';
                        return;
                    }

                    // Validar tamano (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('La imagen no puede superar los 2MB.');
                        this.value = '';
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $previewImg.attr('src', e.target.result);
                        $placeholder.hide();
                        $preview.show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Quitar imagen
            $('.flavor-pp-btn-quitar-imagen').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                PP.resetUploadPreview();
            });
        },

        resetUploadPreview: function() {
            $('#pp-imagen').val('');
            $('.flavor-pp-upload-preview').hide();
            $('.flavor-pp-upload-placeholder').show();
        },

        // Inicializar filtros
        initFilters: function() {
            var $categoriaFilter = $('#flavor-pp-filtro-categoria, #flavor-pp-filtro-cat-votacion');
            var $ordenFilter = $('#flavor-pp-filtro-orden');
            var $buscarInput = $('#flavor-pp-buscar');

            // Filtro por categoria
            $categoriaFilter.on('change', function() {
                var categoria = $(this).val();
                PP.filterProjects({ categoria: categoria });
            });

            // Filtro por orden
            $ordenFilter.on('change', function() {
                var orden = $(this).val();
                PP.sortProjects(orden);
            });

            // Busqueda
            var searchTimeout;
            $buscarInput.on('input', function() {
                clearTimeout(searchTimeout);
                var query = $(this).val().toLowerCase();
                searchTimeout = setTimeout(function() {
                    PP.searchProjects(query);
                }, 300);
            });
        },

        filterProjects: function(filters) {
            var $projects = $('.flavor-pp-proyecto');

            $projects.each(function() {
                var $project = $(this);
                var show = true;

                if (filters.categoria && $project.data('categoria') !== filters.categoria) {
                    show = false;
                }

                $project.toggle(show);
            });
        },

        sortProjects: function(orden) {
            var $grid = $('.flavor-pp-grid');
            var $projects = $grid.children('.flavor-pp-proyecto').get();

            $projects.sort(function(a, b) {
                var $a = $(a);
                var $b = $(b);

                switch (orden) {
                    case 'votos':
                        var votosA = parseInt($a.find('.dashicons-heart').parent().text()) || 0;
                        var votosB = parseInt($b.find('.dashicons-heart').parent().text()) || 0;
                        return votosB - votosA;
                    case 'presupuesto':
                        var presA = parseInt($a.find('.dashicons-money-alt').parent().text().replace(/\D/g, '')) || 0;
                        var presB = parseInt($b.find('.dashicons-money-alt').parent().text().replace(/\D/g, '')) || 0;
                        return presB - presA;
                    case 'recientes':
                    default:
                        return $(b).data('id') - $(a).data('id');
                }
            });

            $grid.append($projects);
        },

        searchProjects: function(query) {
            var $projects = $('.flavor-pp-proyecto');

            if (!query) {
                $projects.show();
                return;
            }

            $projects.each(function() {
                var $project = $(this);
                var titulo = $project.find('.flavor-pp-proyecto-titulo').text().toLowerCase();
                var descripcion = $project.find('.flavor-pp-proyecto-descripcion').text().toLowerCase();

                var match = titulo.indexOf(query) !== -1 || descripcion.indexOf(query) !== -1;
                $project.toggle(match);
            });
        },

        // Helpers
        setLoading: function($btn, loading) {
            if (loading) {
                $btn.prop('disabled', true).addClass('loading');
                $btn.data('original-text', $btn.html());
                $btn.html('<span class="flavor-pp-spinner"></span> ' + this.config.strings.cargando);
            } else {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.html($btn.data('original-text'));
            }
        },

        showMessage: function(tipo, mensaje) {
            var $mensaje = $('<div class="flavor-pp-mensaje flavor-pp-mensaje-' + tipo + '">' + mensaje + '</div>');
            $('.flavor-pp-contenedor, .flavor-pp-votacion-contenedor').first().prepend($mensaje);

            setTimeout(function() {
                $mensaje.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        showFormMessage: function($form, tipo, mensaje) {
            var $mensajeEl = $form.find('#pp-mensaje-resultado');
            $mensajeEl
                .removeClass('flavor-pp-mensaje-oculto flavor-pp-mensaje-exito flavor-pp-mensaje-error')
                .addClass('flavor-pp-mensaje-' + tipo)
                .text(mensaje)
                .show();

            if (tipo === 'exito') {
                setTimeout(function() {
                    $mensajeEl.fadeOut();
                }, 5000);
            }
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        PP.init();
    });

})(jQuery);
