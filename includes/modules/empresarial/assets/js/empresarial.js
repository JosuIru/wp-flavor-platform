/**
 * JavaScript del módulo Empresarial - Frontend
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorEmpresarial = {
        config: window.flavorEmpresarialConfig || {},

        init: function() {
            this.bindEvents();
            this.initCarousel();
            this.initPortfolioFiltros();
        },

        bindEvents: function() {
            // Formulario de contacto
            $(document).on('submit', '#form-contacto-empresarial', this.handleContactoSubmit.bind(this));

            // Modal de proyecto
            $(document).on('click', '.ver-proyecto', this.abrirModalProyecto.bind(this));
            $(document).on('click', '.flavor-emp-modal-close, .flavor-emp-modal-overlay', this.cerrarModal.bind(this));

            // ESC para cerrar modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    FlavorEmpresarial.cerrarModal();
                }
            });
        },

        // ==========================================
        // Formulario de Contacto
        // ==========================================

        handleContactoSubmit: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');
            const $mensaje = $form.find('.form-mensaje');

            // Validar campos requeridos
            const nombre = $form.find('#contacto-nombre').val().trim();
            const email = $form.find('#contacto-email').val().trim();
            const mensajeTexto = $form.find('#contacto-mensaje').val().trim();

            if (!nombre || !email || !mensajeTexto) {
                this.mostrarMensaje($mensaje, this.config.strings?.camposRequeridos || 'Completa los campos obligatorios', 'error');
                return;
            }

            // Deshabilitar botón
            const textoOriginal = $btn.find('.btn-texto').text();
            $btn.prop('disabled', true);
            $btn.find('.btn-texto').text(this.config.strings?.enviando || 'Enviando...');

            // Preparar datos
            const datos = {
                action: 'empresarial_contacto',
                nonce: this.config.nonce,
                nombre: nombre,
                email: email,
                telefono: $form.find('#contacto-telefono').val().trim(),
                empresa: $form.find('#contacto-empresa').val().trim(),
                asunto: $form.find('#contacto-asunto').val().trim(),
                mensaje: mensajeTexto,
                origen: $form.find('input[name="origen"]').val() || 'web'
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datos,
                dataType: 'json'
            })
            .done(function(res) {
                if (res.success) {
                    FlavorEmpresarial.mostrarMensaje($mensaje, res.data.message, 'success');
                    $form[0].reset();
                } else {
                    FlavorEmpresarial.mostrarMensaje($mensaje, res.data?.message || 'Error al enviar', 'error');
                }
            })
            .fail(function() {
                FlavorEmpresarial.mostrarMensaje($mensaje, FlavorEmpresarial.config.strings?.error || 'Error de conexión', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false);
                $btn.find('.btn-texto').text(textoOriginal);
            });
        },

        mostrarMensaje: function($elemento, texto, tipo) {
            $elemento
                .removeClass('success error')
                .addClass(tipo)
                .text(texto)
                .slideDown();

            if (tipo === 'success') {
                setTimeout(function() {
                    $elemento.slideUp();
                }, 5000);
            }
        },

        // ==========================================
        // Carousel de Testimonios
        // ==========================================

        initCarousel: function() {
            const $carousel = $('.testimonios-carousel');
            if (!$carousel.length) return;

            let slideActual = 0;
            const $track = $carousel.find('.testimonios-track');
            const $slides = $track.find('.carousel-slide');
            const totalSlides = $slides.length;

            if (totalSlides <= 1) return;

            const irASlide = function(indice) {
                if (indice < 0) indice = totalSlides - 1;
                if (indice >= totalSlides) indice = 0;

                slideActual = indice;
                $track.css('transform', 'translateX(-' + (indice * 100) + '%)');

                $carousel.find('.dot').removeClass('active');
                $carousel.find('.dot[data-slide="' + indice + '"]').addClass('active');
            };

            $carousel.find('.control-prev').on('click', function() {
                irASlide(slideActual - 1);
            });

            $carousel.find('.control-next').on('click', function() {
                irASlide(slideActual + 1);
            });

            $carousel.find('.dot').on('click', function() {
                irASlide($(this).data('slide'));
            });

            // Auto-avance
            setInterval(function() {
                irASlide(slideActual + 1);
            }, 6000);
        },

        // ==========================================
        // Filtros de Portfolio
        // ==========================================

        initPortfolioFiltros: function() {
            const $contenedor = $('.flavor-emp-portfolio');
            if (!$contenedor.length) return;

            $contenedor.find('.filtro-btn').on('click', function() {
                const categoria = $(this).data('categoria');

                // Actualizar botón activo
                $contenedor.find('.filtro-btn').removeClass('active');
                $(this).addClass('active');

                // Filtrar proyectos
                const $proyectos = $contenedor.find('.flavor-emp-proyecto-card');

                if (categoria === 'todos') {
                    $proyectos.removeClass('hidden').fadeIn(300);
                } else {
                    $proyectos.each(function() {
                        const categoriaProyecto = $(this).data('categoria');
                        if (categoriaProyecto === categoria) {
                            $(this).removeClass('hidden').fadeIn(300);
                        } else {
                            $(this).addClass('hidden').fadeOut(200);
                        }
                    });
                }
            });
        },

        // ==========================================
        // Modal de Proyecto
        // ==========================================

        abrirModalProyecto: function(e) {
            e.preventDefault();
            const proyectoId = $(e.currentTarget).data('id');
            const $card = $(e.currentTarget).closest('.flavor-emp-proyecto-card');

            // Obtener datos del proyecto
            const titulo = $card.find('.proyecto-titulo').text();
            const cliente = $card.find('.proyecto-cliente').text();
            const descripcion = $card.find('.proyecto-descripcion').text();
            const categoria = $card.find('.proyecto-categoria').text();

            // Construir contenido del modal
            let html = '<div class="proyecto-detalle">';

            if (categoria) {
                html += '<span class="proyecto-categoria-modal">' + this.escapeHtml(categoria) + '</span>';
            }

            html += '<h2>' + this.escapeHtml(titulo) + '</h2>';
            html += '<p class="proyecto-cliente-modal"><strong>Cliente:</strong> ' + this.escapeHtml(cliente) + '</p>';

            if (descripcion) {
                html += '<div class="proyecto-descripcion-modal">';
                html += '<h4>Descripción</h4>';
                html += '<p>' + this.escapeHtml(descripcion) + '</p>';
                html += '</div>';
            }

            html += '</div>';

            $('#proyecto-detalle').html(html);
            $('#modal-proyecto').fadeIn(200);
            $('body').css('overflow', 'hidden');
        },

        cerrarModal: function() {
            $('.flavor-emp-modal').fadeOut(200);
            $('body').css('overflow', '');
        },

        // ==========================================
        // Utilidades
        // ==========================================

        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        FlavorEmpresarial.init();
    });

})(jQuery);
