/**
 * Cursos - Aula Virtual JavaScript
 */

(function($) {
    'use strict';

    const CursosAula = {
        cursoId: 0,
        leccionActual: null,
        lecciones: [],
        tiempoInicio: null,

        init: function() {
            this.cursoId = cursosAulaData.curso_id;

            this.bindEvents();
            this.cargarLecciones();
        },

        bindEvents: function() {
            // Seleccionar lección
            $(document).on('click', '.aula-leccion', this.handleSeleccionLeccion.bind(this));

            // Completar lección
            $(document).on('click', '.btn-completar', this.handleCompletar.bind(this));

            // Navegación
            $(document).on('click', '.aula-nav-btn.anterior', this.handleAnterior.bind(this));
            $(document).on('click', '.aula-nav-btn.siguiente', this.handleSiguiente.bind(this));

            // Toggle sidebar móvil
            $(document).on('click', '.aula-toggle-sidebar', function() {
                $('.aula-sidebar').toggleClass('open');
            });

            // Quiz
            $(document).on('click', '.quiz-opcion', this.handleQuizOpcion.bind(this));
            $(document).on('click', '.btn-enviar-quiz', this.handleEnviarQuiz.bind(this));
        },

        cargarLecciones: function() {
            const self = this;

            $.ajax({
                url: cursosAulaData.ajax_url.replace('admin-ajax.php', 'wp-json/flavor/v1/cursos/' + this.cursoId + '/lecciones'),
                type: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cursosAulaData.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        self.lecciones = response.lecciones;
                        self.renderLecciones(response.lecciones, response.progreso);

                        // Cargar lección específica o primera
                        const leccionId = cursosAulaData.leccion_id || (response.lecciones[0] ? response.lecciones[0].id : null);
                        if (leccionId) {
                            self.cargarLeccion(leccionId);
                        }
                    }
                }
            });
        },

        renderLecciones: function(lecciones, progreso) {
            let html = '';

            lecciones.forEach(function(leccion) {
                const completadaClass = leccion.completada ? 'completada' : '';
                const checkIcon = leccion.completada ? '<span class="dashicons dashicons-yes"></span>' : leccion.orden;

                html += `
                    <li class="aula-leccion ${completadaClass}" data-id="${leccion.id}">
                        <span class="leccion-check">${checkIcon}</span>
                        <div class="aula-leccion-info">
                            <div class="aula-leccion-titulo">${leccion.titulo}</div>
                            <div class="aula-leccion-meta">
                                ${leccion.duracion ? leccion.duracion + ' min' : ''} &bull; ${leccion.tipo}
                            </div>
                        </div>
                        <span class="aula-leccion-tipo dashicons dashicons-${this.getTipoIcon(leccion.tipo)}"></span>
                    </li>
                `;
            }.bind(this));

            $('.aula-lecciones').html(html);

            // Actualizar progreso
            $('.aula-progreso-fill').css('width', progreso.porcentaje + '%');
            $('.aula-progreso-texto .completadas').text(progreso.completadas + '/' + lecciones.length);
            $('.aula-progreso-texto .porcentaje').text(progreso.porcentaje + '%');
        },

        getTipoIcon: function(tipo) {
            const icons = {
                video: 'video-alt3',
                texto: 'text-page',
                quiz: 'clipboard',
                archivo: 'download',
                enlace: 'admin-links',
                live: 'format-video'
            };
            return icons[tipo] || 'media-document';
        },

        cargarLeccion: function(leccionId) {
            const self = this;

            // Marcar como activa en sidebar
            $('.aula-leccion').removeClass('active');
            $('.aula-leccion[data-id="' + leccionId + '"]').addClass('active');

            $.ajax({
                url: cursosAulaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cursos_ver_leccion',
                    nonce: cursosAulaData.nonce,
                    leccion_id: leccionId
                },
                success: function(response) {
                    if (response.success) {
                        self.leccionActual = response.leccion;
                        self.renderLeccion(response.leccion, response.progreso);
                        self.tiempoInicio = Date.now();

                        // Actualizar URL
                        const url = new URL(window.location);
                        url.searchParams.set('leccion_id', leccionId);
                        window.history.replaceState({}, '', url);
                    }
                }
            });
        },

        renderLeccion: function(leccion, progreso) {
            // Header
            $('.aula-header h2').text(leccion.titulo);

            // Contenido según tipo
            let contenidoHtml = '';

            switch (leccion.tipo) {
                case 'video':
                    contenidoHtml = this.renderVideo(leccion);
                    break;
                case 'texto':
                    contenidoHtml = this.renderTexto(leccion);
                    break;
                case 'quiz':
                    contenidoHtml = this.renderQuiz(leccion);
                    break;
                case 'archivo':
                    contenidoHtml = this.renderArchivo(leccion);
                    break;
                default:
                    contenidoHtml = this.renderTexto(leccion);
            }

            $('.aula-leccion-contenido').html(contenidoHtml);

            // Botón completar
            if (progreso && progreso.completada) {
                $('.btn-completar').addClass('completada').text('Completada').prop('disabled', true);
            } else {
                $('.btn-completar').removeClass('completada').text('Marcar como completada').prop('disabled', false);
            }

            // Navegación
            this.actualizarNavegacion();
        },

        renderVideo: function(leccion) {
            let videoHtml = '';

            if (leccion.video_url) {
                // Detectar tipo de video (YouTube, Vimeo, etc.)
                if (leccion.video_url.includes('youtube') || leccion.video_url.includes('youtu.be')) {
                    const videoId = this.getYouTubeId(leccion.video_url);
                    videoHtml = `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
                } else if (leccion.video_url.includes('vimeo')) {
                    const videoId = leccion.video_url.split('/').pop();
                    videoHtml = `<iframe src="https://player.vimeo.com/video/${videoId}" frameborder="0" allowfullscreen></iframe>`;
                } else {
                    videoHtml = `<video src="${leccion.video_url}" controls></video>`;
                }
            }

            return `
                <div class="aula-video-container">${videoHtml}</div>
                ${leccion.descripcion ? `<div class="aula-texto-contenido">${leccion.descripcion}</div>` : ''}
            `;
        },

        renderTexto: function(leccion) {
            return `
                <div class="aula-texto-contenido">
                    ${leccion.contenido || leccion.descripcion || '<p>Sin contenido disponible.</p>'}
                </div>
            `;
        },

        renderQuiz: function(leccion) {
            // Parsear preguntas del contenido JSON
            let preguntas = [];
            try {
                preguntas = JSON.parse(leccion.contenido);
            } catch (e) {
                return '<div class="aula-texto-contenido"><p>Error al cargar el quiz.</p></div>';
            }

            let html = '<div class="aula-quiz">';

            preguntas.forEach(function(pregunta, index) {
                html += `
                    <div class="quiz-pregunta" data-index="${index}">
                        <div class="quiz-pregunta-texto">${index + 1}. ${pregunta.texto}</div>
                        <div class="quiz-opciones">
                            ${pregunta.opciones.map((opcion, i) => `
                                <label class="quiz-opcion" data-valor="${i}">
                                    <input type="radio" name="pregunta_${index}" value="${i}" style="display:none;">
                                    <span class="opcion-letra">${String.fromCharCode(65 + i)}</span>
                                    <span class="opcion-texto">${opcion}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
            });

            html += '<button type="button" class="btn-enviar-quiz btn-completar" style="width:100%; margin-top:1rem;">Enviar respuestas</button>';
            html += '</div>';

            return html;
        },

        renderArchivo: function(leccion) {
            return `
                <div class="aula-texto-contenido">
                    ${leccion.descripcion || ''}
                    ${leccion.archivo_url ? `
                        <div style="margin-top: 2rem; text-align: center;">
                            <a href="${leccion.archivo_url}" download class="btn-completar" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-download"></span>
                                Descargar archivo
                            </a>
                        </div>
                    ` : ''}
                </div>
            `;
        },

        getYouTubeId: function(url) {
            const match = url.match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([^&?]+)/);
            return match ? match[1] : '';
        },

        actualizarNavegacion: function() {
            const currentIndex = this.lecciones.findIndex(l => l.id === this.leccionActual.id);

            $('.aula-nav-btn.anterior').prop('disabled', currentIndex <= 0);
            $('.aula-nav-btn.siguiente').prop('disabled', currentIndex >= this.lecciones.length - 1);
        },

        handleSeleccionLeccion: function(e) {
            const leccionId = $(e.currentTarget).data('id');
            this.cargarLeccion(leccionId);

            // Cerrar sidebar en móvil
            $('.aula-sidebar').removeClass('open');
        },

        handleCompletar: function(e) {
            e.preventDefault();
            const self = this;
            const $btn = $(e.currentTarget);

            if ($btn.hasClass('completada')) return;

            const tiempoMinutos = Math.round((Date.now() - this.tiempoInicio) / 60000);

            $btn.prop('disabled', true).text('Guardando...');

            $.ajax({
                url: cursosAulaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cursos_marcar_leccion',
                    nonce: cursosAulaData.nonce,
                    leccion_id: self.leccionActual.id,
                    tiempo: tiempoMinutos
                },
                success: function(response) {
                    if (response.success) {
                        $btn.addClass('completada').text('Completada');

                        // Actualizar sidebar
                        $('.aula-leccion[data-id="' + self.leccionActual.id + '"]')
                            .addClass('completada')
                            .find('.leccion-check').html('<span class="dashicons dashicons-yes"></span>');

                        // Mostrar mensaje
                        self.showToast('success', response.mensaje + (response.puntos ? ' +' + response.puntos + ' puntos' : ''));

                        // Recargar lecciones para actualizar progreso
                        self.cargarLecciones();
                    } else {
                        self.showToast('error', response.error);
                        $btn.prop('disabled', false).text('Marcar como completada');
                    }
                },
                error: function() {
                    self.showToast('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Marcar como completada');
                }
            });
        },

        handleAnterior: function() {
            const currentIndex = this.lecciones.findIndex(l => l.id === this.leccionActual.id);
            if (currentIndex > 0) {
                this.cargarLeccion(this.lecciones[currentIndex - 1].id);
            }
        },

        handleSiguiente: function() {
            const currentIndex = this.lecciones.findIndex(l => l.id === this.leccionActual.id);
            if (currentIndex < this.lecciones.length - 1) {
                this.cargarLeccion(this.lecciones[currentIndex + 1].id);
            }
        },

        handleQuizOpcion: function(e) {
            const $opcion = $(e.currentTarget);
            const $pregunta = $opcion.closest('.quiz-pregunta');

            $pregunta.find('.quiz-opcion').removeClass('seleccionada');
            $opcion.addClass('seleccionada');
            $opcion.find('input').prop('checked', true);
        },

        handleEnviarQuiz: function(e) {
            e.preventDefault();
            // Por ahora, simplemente marca como completada
            // Se puede expandir para verificar respuestas
            this.handleCompletar(e);
        },

        showToast: function(type, message) {
            const $toast = $('<div class="aula-toast aula-toast-' + type + '">' + message + '</div>');
            $('body').append($toast);

            setTimeout(function() {
                $toast.addClass('show');
            }, 100);

            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    // Estilos de toast
    $('<style>').text(`
        .aula-toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: #fff;
            font-weight: 500;
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }
        .aula-toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .aula-toast-success { background: #10b981; }
        .aula-toast-error { background: #ef4444; }
    `).appendTo('head');

    // Inicializar
    $(document).ready(function() {
        CursosAula.init();
    });

})(jQuery);
