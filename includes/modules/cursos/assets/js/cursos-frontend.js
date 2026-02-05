/**
 * Cursos - Frontend JavaScript
 */

(function($) {
    'use strict';

    const CursosFrontend = {
        init: function() {
            this.bindEvents();
            this.initFilters();
        },

        bindEvents: function() {
            // Inscribirse en curso
            $(document).on('click', '.btn-inscribirse', this.handleInscripcion);

            // Marcar lección completada
            $(document).on('click', '.btn-completar-leccion', this.handleCompletarLeccion);

            // Valorar curso
            $(document).on('click', '.valoracion-estrellas .estrella', this.handleValoracion);
            $(document).on('submit', '#form-valoracion', this.handleEnviarValoracion);

            // Solicitar certificado
            $(document).on('click', '.btn-certificado', this.handleSolicitarCertificado);

            // Tabs
            $(document).on('click', '.curso-tab', this.handleTab);

            // Filtros
            $(document).on('change', '.cursos-filtros select, .cursos-filtros input', this.handleFiltro);
        },

        initFilters: function() {
            // Cargar parámetros de URL si existen
            const urlParams = new URLSearchParams(window.location.search);
            const categoria = urlParams.get('categoria');
            const nivel = urlParams.get('nivel');
            const modalidad = urlParams.get('modalidad');

            if (categoria) $('[name="filtro_categoria"]').val(categoria);
            if (nivel) $('[name="filtro_nivel"]').val(nivel);
            if (modalidad) $('[name="filtro_modalidad"]').val(modalidad);
        },

        handleInscripcion: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const cursoId = $btn.data('curso-id');

            if (!cursoId) return;

            $btn.prop('disabled', true).html('<span class="cursos-spinner"></span>');

            $.ajax({
                url: cursosData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cursos_inscribirse',
                    nonce: cursosData.nonce,
                    curso_id: cursoId
                },
                success: function(response) {
                    if (response.success) {
                        CursosFrontend.showMessage('success', response.mensaje);
                        // Actualizar UI
                        $btn.removeClass('btn-inscribirse').addClass('btn-continuar')
                            .html('Acceder al curso').attr('href', '?page=aula&curso_id=' + cursoId);

                        // Recargar si es necesario
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        CursosFrontend.showMessage('error', response.error);
                        $btn.prop('disabled', false).text('Inscribirse');
                    }
                },
                error: function() {
                    CursosFrontend.showMessage('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Inscribirse');
                }
            });
        },

        handleCompletarLeccion: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const leccionId = $btn.data('leccion-id');
            const tiempo = $btn.data('tiempo') || 0;

            if (!leccionId) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: cursosData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cursos_marcar_leccion',
                    nonce: cursosData.nonce,
                    leccion_id: leccionId,
                    tiempo: tiempo
                },
                success: function(response) {
                    if (response.success) {
                        CursosFrontend.showMessage('success', response.mensaje);
                        // Actualizar UI
                        $btn.closest('.leccion-item').addClass('completada');
                        $btn.html('<span class="dashicons dashicons-yes-alt"></span> Completada');

                        // Actualizar progreso si existe
                        CursosFrontend.actualizarProgreso();
                    } else {
                        CursosFrontend.showMessage('error', response.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    CursosFrontend.showMessage('error', 'Error de conexión');
                    $btn.prop('disabled', false);
                }
            });
        },

        handleValoracion: function(e) {
            const $estrella = $(this);
            const valor = $estrella.data('valor');
            const $container = $estrella.closest('.valoracion-estrellas');

            $container.find('.estrella').removeClass('activa');
            $container.find('.estrella').each(function() {
                if ($(this).data('valor') <= valor) {
                    $(this).addClass('activa');
                }
            });

            $container.closest('form').find('[name="valoracion"]').val(valor);
        },

        handleEnviarValoracion: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');

            const cursoId = $form.data('curso-id');
            const valoracion = $form.find('[name="valoracion"]').val();
            const comentario = $form.find('[name="comentario"]').val();

            if (!valoracion) {
                CursosFrontend.showMessage('error', 'Selecciona una valoración');
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: cursosData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cursos_valorar',
                    nonce: cursosData.nonce,
                    curso_id: cursoId,
                    valoracion: valoracion,
                    comentario: comentario
                },
                success: function(response) {
                    if (response.success) {
                        CursosFrontend.showMessage('success', response.mensaje);
                        $form.html('<p class="valoracion-gracias">¡Gracias por tu valoración!</p>');
                    } else {
                        CursosFrontend.showMessage('error', response.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    CursosFrontend.showMessage('error', 'Error de conexión');
                    $btn.prop('disabled', false);
                }
            });
        },

        handleSolicitarCertificado: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const cursoId = $btn.data('curso-id');

            $btn.prop('disabled', true).html('<span class="cursos-spinner"></span>');

            $.ajax({
                url: cursosData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cursos_solicitar_certificado',
                    nonce: cursosData.nonce,
                    curso_id: cursoId
                },
                success: function(response) {
                    if (response.success) {
                        CursosFrontend.showMessage('success', response.mensaje);

                        if (response.certificado && response.certificado.codigo) {
                            const verUrl = '?page=verificar-certificado&codigo=' + response.certificado.codigo;
                            $btn.html('Ver certificado').attr('href', verUrl).removeAttr('disabled');
                        }
                    } else {
                        CursosFrontend.showMessage('error', response.error);
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-awards"></span>');
                    }
                },
                error: function() {
                    CursosFrontend.showMessage('error', 'Error de conexión');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-awards"></span>');
                }
            });
        },

        handleTab: function(e) {
            e.preventDefault();
            const $tab = $(this);
            const target = $tab.data('tab');

            $tab.siblings().removeClass('active');
            $tab.addClass('active');

            $tab.closest('.curso-main').find('.curso-tab-contenido').hide();
            $tab.closest('.curso-main').find('[data-tab-content="' + target + '"]').show();
        },

        handleFiltro: function() {
            const categoria = $('[name="filtro_categoria"]').val();
            const nivel = $('[name="filtro_nivel"]').val();
            const modalidad = $('[name="filtro_modalidad"]').val();
            const busqueda = $('[name="filtro_busqueda"]').val();

            const $grid = $('.cursos-grid');
            $grid.html('<div class="cursos-loading"><div class="cursos-spinner"></div></div>');

            // Actualizar URL
            const params = new URLSearchParams();
            if (categoria) params.set('categoria', categoria);
            if (nivel) params.set('nivel', nivel);
            if (modalidad) params.set('modalidad', modalidad);
            if (busqueda) params.set('q', busqueda);

            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newUrl);

            // Cargar cursos filtrados
            $.ajax({
                url: cursosData.rest_url,
                type: 'GET',
                data: {
                    categoria: categoria,
                    nivel: nivel,
                    modalidad: modalidad,
                    q: busqueda
                },
                success: function(response) {
                    if (response.success && response.cursos.length > 0) {
                        let html = '';
                        response.cursos.forEach(function(curso) {
                            html += CursosFrontend.renderCursoCard(curso);
                        });
                        $grid.html(html);
                    } else {
                        $grid.html('<div class="cursos-vacio"><span class="dashicons dashicons-welcome-learn-more"></span><p>No se encontraron cursos</p></div>');
                    }
                },
                error: function() {
                    $grid.html('<div class="cursos-error">Error al cargar los cursos</div>');
                }
            });
        },

        renderCursoCard: function(curso) {
            const precioText = curso.es_gratuito ? 'Gratis' : curso.precio + ' €';
            const precioClass = curso.es_gratuito ? 'gratuito' : '';
            const badge = curso.es_gratuito ? '<span class="curso-badge gratuito">Gratis</span>' : '';
            const destacadoBadge = curso.destacado ? '<span class="curso-badge destacado">Destacado</span>' : '';

            return `
                <div class="curso-card">
                    <div class="curso-card-imagen">
                        <img src="${curso.imagen || 'https://placehold.co/400x225/e5e7eb/6b7280?text=Curso'}" alt="${curso.titulo}">
                        ${badge}${destacadoBadge}
                    </div>
                    <div class="curso-card-contenido">
                        <div class="curso-card-categoria">${curso.categoria || 'General'}</div>
                        <h3 class="curso-card-titulo">
                            <a href="?curso_id=${curso.id}">${curso.titulo}</a>
                        </h3>
                        <div class="curso-card-instructor">
                            <span>Por ${curso.instructor}</span>
                        </div>
                        <div class="curso-card-meta">
                            <span><span class="dashicons dashicons-clock"></span> ${curso.duracion_horas}h</span>
                            <span><span class="dashicons dashicons-admin-users"></span> ${curso.alumnos} alumnos</span>
                            <span><span class="dashicons dashicons-chart-bar"></span> ${curso.nivel}</span>
                        </div>
                    </div>
                    <div class="curso-card-footer">
                        <span class="curso-card-precio ${precioClass}">${precioText}</span>
                        <div class="curso-card-valoracion">
                            <span class="dashicons dashicons-star-filled"></span>
                            <span>${curso.valoracion.toFixed(1)}</span>
                        </div>
                    </div>
                </div>
            `;
        },

        actualizarProgreso: function() {
            // Actualizar barra de progreso si existe
            const $progresoBar = $('.progreso-bar-fill');
            if ($progresoBar.length) {
                const total = $('.leccion-item').length;
                const completadas = $('.leccion-item.completada').length;
                const porcentaje = total > 0 ? Math.round((completadas / total) * 100) : 0;

                $progresoBar.css('width', porcentaje + '%');
                $('.progreso-texto').text(porcentaje + '% completado');
            }
        },

        showMessage: function(type, message) {
            const $toast = $('<div class="cursos-toast cursos-toast-' + type + '">' + message + '</div>');
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

    // Inicializar
    $(document).ready(function() {
        CursosFrontend.init();
    });

    // Toast styles (añadidas dinámicamente)
    $('<style>')
        .text(`
            .cursos-toast {
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
            .cursos-toast.show {
                transform: translateY(0);
                opacity: 1;
            }
            .cursos-toast-success {
                background: #10b981;
            }
            .cursos-toast-error {
                background: #ef4444;
            }
        `)
        .appendTo('head');

})(jQuery);
