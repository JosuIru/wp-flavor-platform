/**
 * Cursos - Panel Instructor JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var CursosInstructor = {
        config: window.cursosInstructorConfig || {},

        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initTabs();
        },

        bindEvents: function() {
            var self = this;

            // Crear nuevo curso
            $(document).on('click', '.ci-btn-nuevo-curso', function(e) {
                e.preventDefault();
                self.abrirEditorCurso();
            });

            // Editar curso
            $(document).on('click', '.ci-btn-editar-curso', function(e) {
                e.preventDefault();
                var cursoId = $(this).data('curso-id');
                self.cargarCurso(cursoId);
            });

            // Guardar curso
            $(document).on('submit', '#ci-form-curso', function(e) {
                e.preventDefault();
                self.guardarCurso($(this));
            });

            // Toggle modulo
            $(document).on('click', '.ci-modulo-header', function() {
                var $modulo = $(this).closest('.ci-modulo-item');
                $modulo.toggleClass('abierto');
                $modulo.find('.ci-lecciones-lista').slideToggle(200);
            });

            // Añadir modulo
            $(document).on('click', '.ci-btn-anadir-modulo', function(e) {
                e.preventDefault();
                self.anadirModulo();
            });

            // Añadir leccion
            $(document).on('click', '.ci-btn-anadir-leccion', function(e) {
                e.preventDefault();
                var $modulo = $(this).closest('.ci-modulo-item');
                self.anadirLeccion($modulo);
            });

            // Eliminar modulo/leccion
            $(document).on('click', '.ci-btn-eliminar', function(e) {
                e.preventDefault();
                var $item = $(this).closest('.ci-modulo-item, .ci-leccion-item');
                if (confirm(self.config.i18n?.confirmarEliminar || '¿Eliminar este elemento?')) {
                    $item.slideUp(200, function() {
                        $(this).remove();
                    });
                }
            });

            // Publicar/despublicar curso
            $(document).on('click', '.ci-btn-publicar', function(e) {
                e.preventDefault();
                var cursoId = $(this).data('curso-id');
                var estado = $(this).data('estado');
                self.cambiarEstadoCurso(cursoId, estado, $(this));
            });

            // Buscar alumnos
            $(document).on('input', '#ci-buscar-alumnos', function() {
                var query = $(this).val().toLowerCase();
                self.filtrarAlumnos(query);
            });

            // Exportar datos
            $(document).on('click', '.ci-btn-exportar', function(e) {
                e.preventDefault();
                var tipo = $(this).data('tipo');
                self.exportarDatos(tipo);
            });
        },

        initSortable: function() {
            var self = this;

            // Sortable para modulos
            if ($.fn.sortable && $('.ci-modulos-lista').length) {
                $('.ci-modulos-lista').sortable({
                    handle: '.ci-modulo-drag',
                    placeholder: 'ci-sortable-placeholder',
                    update: function() {
                        self.actualizarOrden();
                    }
                });
            }

            // Sortable para lecciones
            if ($.fn.sortable && $('.ci-lecciones-lista').length) {
                $('.ci-lecciones-lista').sortable({
                    handle: '.ci-leccion-drag',
                    connectWith: '.ci-lecciones-lista',
                    placeholder: 'ci-sortable-placeholder',
                    update: function() {
                        self.actualizarOrden();
                    }
                });
            }
        },

        initTabs: function() {
            $(document).on('click', '.ci-editor-tab', function() {
                var tabId = $(this).data('tab');

                $('.ci-editor-tab').removeClass('active');
                $(this).addClass('active');

                $('.ci-editor-panel').hide();
                $('#ci-panel-' + tabId).show();
            });
        },

        abrirEditorCurso: function(cursoId) {
            var $editor = $('.ci-editor-section');

            if (cursoId) {
                // Cargar datos del curso
                this.cargarCurso(cursoId);
            } else {
                // Limpiar formulario
                $('#ci-form-curso')[0].reset();
                $('.ci-modulos-lista').empty();
            }

            $editor.slideDown(300);
            $('html, body').animate({
                scrollTop: $editor.offset().top - 50
            }, 300);
        },

        cargarCurso: function(cursoId) {
            var self = this;

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cursos_instructor_get_curso',
                    nonce: self.config.nonce,
                    curso_id: cursoId
                },
                beforeSend: function() {
                    self.mostrarLoading();
                },
                success: function(response) {
                    self.ocultarLoading();
                    if (response.success) {
                        self.rellenarFormulario(response.data);
                    } else {
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.ocultarLoading();
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error de conexión', 'error');
                }
            });
        },

        rellenarFormulario: function(curso) {
            $('#ci-curso-id').val(curso.id);
            $('#ci-curso-titulo').val(curso.titulo);
            $('#ci-curso-descripcion').val(curso.descripcion);
            $('#ci-curso-categoria').val(curso.categoria);
            $('#ci-curso-nivel').val(curso.nivel);
            $('#ci-curso-precio').val(curso.precio);
            $('#ci-curso-duracion').val(curso.duracion);

            // Renderizar modulos
            this.renderizarModulos(curso.modulos || []);
        },

        renderizarModulos: function(modulos) {
            var self = this;
            var $lista = $('.ci-modulos-lista');
            $lista.empty();

            modulos.forEach(function(modulo, index) {
                var $modulo = self.crearModuloHTML(modulo, index);
                $lista.append($modulo);
            });

            this.initSortable();
        },

        crearModuloHTML: function(modulo, index) {
            var lecciones = modulo.lecciones || [];
            var html = '<div class="ci-modulo-item" data-modulo-id="' + (modulo.id || '') + '">';
            html += '  <div class="ci-modulo-header">';
            html += '    <span class="ci-modulo-drag dashicons dashicons-menu"></span>';
            html += '    <input type="text" class="ci-modulo-titulo-input" value="' + this.escapeHtml(modulo.titulo || 'Módulo ' + (index + 1)) + '" placeholder="Título del módulo">';
            html += '    <span class="ci-modulo-lecciones">' + lecciones.length + ' lecciones</span>';
            html += '    <button type="button" class="ci-btn ci-btn-icon ci-btn-eliminar" title="Eliminar módulo"><span class="dashicons dashicons-trash"></span></button>';
            html += '  </div>';
            html += '  <div class="ci-lecciones-lista" style="display:none;">';

            lecciones.forEach(function(leccion) {
                html += this.crearLeccionHTML(leccion);
            }, this);

            html += '    <button type="button" class="ci-btn ci-btn-outline ci-btn-sm ci-btn-anadir-leccion"><span class="dashicons dashicons-plus-alt2"></span> Añadir lección</button>';
            html += '  </div>';
            html += '</div>';

            return $(html);
        },

        crearLeccionHTML: function(leccion) {
            var tipo = leccion.tipo || 'texto';
            var html = '<div class="ci-leccion-item" data-leccion-id="' + (leccion.id || '') + '">';
            html += '  <span class="ci-leccion-drag dashicons dashicons-menu"></span>';
            html += '  <span class="ci-leccion-tipo ' + tipo + '"><span class="dashicons dashicons-' + this.getIconoTipo(tipo) + '"></span></span>';
            html += '  <input type="text" class="ci-leccion-titulo-input" value="' + this.escapeHtml(leccion.titulo || '') + '" placeholder="Título de la lección">';
            html += '  <select class="ci-leccion-tipo-select">';
            html += '    <option value="video"' + (tipo === 'video' ? ' selected' : '') + '>Video</option>';
            html += '    <option value="texto"' + (tipo === 'texto' ? ' selected' : '') + '>Texto</option>';
            html += '    <option value="quiz"' + (tipo === 'quiz' ? ' selected' : '') + '>Quiz</option>';
            html += '  </select>';
            html += '  <button type="button" class="ci-btn ci-btn-icon ci-btn-eliminar" title="Eliminar lección"><span class="dashicons dashicons-trash"></span></button>';
            html += '</div>';

            return html;
        },

        getIconoTipo: function(tipo) {
            var iconos = {
                'video': 'video-alt3',
                'texto': 'media-document',
                'quiz': 'forms'
            };
            return iconos[tipo] || 'media-default';
        },

        anadirModulo: function() {
            var $lista = $('.ci-modulos-lista');
            var index = $lista.find('.ci-modulo-item').length;
            var $modulo = this.crearModuloHTML({}, index);
            $lista.append($modulo);
            $modulo.find('.ci-modulo-titulo-input').focus();
            this.initSortable();
        },

        anadirLeccion: function($modulo) {
            var $lista = $modulo.find('.ci-lecciones-lista');
            var html = this.crearLeccionHTML({});
            $lista.find('.ci-btn-anadir-leccion').before(html);
            $lista.find('.ci-leccion-item:last .ci-leccion-titulo-input').focus();
        },

        guardarCurso: function($form) {
            var self = this;
            var formData = new FormData($form[0]);
            formData.append('action', 'cursos_instructor_guardar_curso');
            formData.append('nonce', this.config.nonce);

            // Recoger estructura de modulos
            var modulos = [];
            $('.ci-modulo-item').each(function(mIndex) {
                var $modulo = $(this);
                var lecciones = [];

                $modulo.find('.ci-leccion-item').each(function(lIndex) {
                    var $leccion = $(this);
                    lecciones.push({
                        id: $leccion.data('leccion-id') || '',
                        titulo: $leccion.find('.ci-leccion-titulo-input').val(),
                        tipo: $leccion.find('.ci-leccion-tipo-select').val(),
                        orden: lIndex
                    });
                });

                modulos.push({
                    id: $modulo.data('modulo-id') || '',
                    titulo: $modulo.find('.ci-modulo-titulo-input').val(),
                    orden: mIndex,
                    lecciones: lecciones
                });
            });

            formData.append('modulos', JSON.stringify(modulos));

            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text(self.config.i18n?.guardando || 'Guardando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.prop('disabled', false).text(self.config.i18n?.guardar || 'Guardar curso');
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        if (response.data.curso_id) {
                            $('#ci-curso-id').val(response.data.curso_id);
                        }
                    } else {
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(self.config.i18n?.guardar || 'Guardar curso');
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        cambiarEstadoCurso: function(cursoId, nuevoEstado, $btn) {
            var self = this;

            $btn.prop('disabled', true);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cursos_instructor_cambiar_estado',
                    nonce: self.config.nonce,
                    curso_id: cursoId,
                    estado: nuevoEstado
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        // Actualizar UI
                        var $item = $btn.closest('.ci-curso-item');
                        $item.find('.ci-curso-estado')
                            .removeClass('publicado borrador revision')
                            .addClass(nuevoEstado)
                            .text(nuevoEstado);

                        $btn.data('estado', nuevoEstado === 'publicado' ? 'borrador' : 'publicado');
                        $btn.text(nuevoEstado === 'publicado' ? 'Despublicar' : 'Publicar');
                    } else {
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        actualizarOrden: function() {
            // Recoger nuevo orden y enviar al servidor
            var orden = [];
            $('.ci-modulo-item').each(function(mIndex) {
                var $modulo = $(this);
                var leccionesOrden = [];

                $modulo.find('.ci-leccion-item').each(function(lIndex) {
                    leccionesOrden.push({
                        id: $(this).data('leccion-id'),
                        orden: lIndex
                    });
                });

                orden.push({
                    id: $modulo.data('modulo-id'),
                    orden: mIndex,
                    lecciones: leccionesOrden
                });
            });

            // Auto-save del orden
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cursos_instructor_actualizar_orden',
                    nonce: this.config.nonce,
                    orden: JSON.stringify(orden)
                }
            });
        },

        filtrarAlumnos: function(query) {
            $('.ci-alumnos-table tbody tr').each(function() {
                var nombre = $(this).find('td:first').text().toLowerCase();
                var email = $(this).find('td:nth-child(2)').text().toLowerCase();

                if (nombre.indexOf(query) > -1 || email.indexOf(query) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        exportarDatos: function(tipo) {
            var self = this;
            var cursoId = $('#ci-curso-id').val();

            if (!cursoId) {
                self.mostrarNotificacion('Selecciona un curso primero', 'warning');
                return;
            }

            window.location.href = self.config.ajaxUrl + '?action=cursos_instructor_exportar&nonce=' + self.config.nonce + '&curso_id=' + cursoId + '&tipo=' + tipo;
        },

        mostrarLoading: function() {
            if (!$('.ci-loading-overlay').length) {
                $('body').append('<div class="ci-loading-overlay"><div class="ci-loading-spinner"></div></div>');
            }
            $('.ci-loading-overlay').fadeIn(200);
        },

        ocultarLoading: function() {
            $('.ci-loading-overlay').fadeOut(200);
        },

        mostrarNotificacion: function(mensaje, tipo) {
            var $notif = $('<div class="ci-notificacion ' + tipo + '">' + mensaje + '</div>');

            $('body').append($notif);

            setTimeout(function() {
                $notif.addClass('visible');
            }, 10);

            setTimeout(function() {
                $notif.removeClass('visible');
                setTimeout(function() {
                    $notif.remove();
                }, 300);
            }, 4000);
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        CursosInstructor.init();
    });

})(jQuery);
