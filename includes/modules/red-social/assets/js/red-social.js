/**
 * Red Social Comunitaria - JavaScript
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const RedSocial = {
        config: {
            ajaxUrl: typeof flavorRedSocial !== 'undefined' ? flavorRedSocial.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: typeof flavorRedSocial !== 'undefined' ? flavorRedSocial.nonce : '',
            userId: typeof flavorRedSocial !== 'undefined' ? flavorRedSocial.userId : 0,
            maxCaracteres: 5000,
            maxImagenes: 10
        },

        state: {
            cargando: false,
            paginaActual: 1,
            hayMas: true,
            ultimoPostId: 0,
            historiaActual: 0,
            historiaTimer: null
        },

        init: function() {
            // Verificar si tenemos sesión válida antes de inicializar
            if (!this.config.nonce) {
                console.warn('RedSocial: Sin sesión válida, funcionalidades AJAX desactivadas');
                this.state.hayMas = false; // Desactivar infinite scroll sin sesión
            }

            this.bindEvents();
            this.initInfiniteScroll();
            this.initMenciones();
            this.initHashtags();
        },

        bindEvents: function() {
            const self = this;

            // Crear publicacion
            $(document).on('submit', '.rs-crear-post-form', function(e) {
                e.preventDefault();
                self.crearPublicacion($(this));
            });

            $(document).on('input', '.rs-crear-post-textarea', function() {
                self.actualizarContadorCaracteres($(this));
                self.autoExpandTextarea($(this));
            });

            // Adjuntos
            $(document).on('click', '.rs-adjunto-btn[data-tipo="imagen"]', function() {
                self.seleccionarImagenes();
            });

            $(document).on('click', '.rs-adjunto-btn[data-tipo="video"]', function() {
                self.seleccionarVideo();
            });

            // Acciones de post
            $(document).on('click', '.rs-post-accion[data-accion="like"]', function() {
                self.toggleLike($(this));
            });

            $(document).on('click', '.rs-post-accion[data-accion="comentar"]', function() {
                self.toggleComentarios($(this));
            });

            $(document).on('click', '.rs-post-accion[data-accion="compartir"]', function() {
                self.compartirPost($(this));
            });

            $(document).on('click', '.rs-post-accion[data-accion="guardar"]', function() {
                self.guardarPost($(this));
            });

            // Comentarios
            $(document).on('submit', '.rs-comentar-form', function(e) {
                e.preventDefault();
                self.enviarComentario($(this));
            });

            $(document).on('click', '.rs-comentario-responder', function() {
                self.responderComentario($(this));
            });

            $(document).on('click', '.rs-comentario-like', function() {
                self.likeComentario($(this));
            });

            // Seguir usuario
            $(document).on('click', '.rs-btn-seguir', function() {
                self.toggleSeguir($(this));
            });

            // Historias
            $(document).on('click', '.rs-historia', function() {
                self.abrirHistoria($(this));
            });

            $(document).on('click', '.rs-historia-cerrar', function() {
                self.cerrarHistoria();
            });

            $(document).on('click', '.rs-historia-modal', function(e) {
                if ($(e.target).hasClass('rs-historia-modal')) {
                    self.cerrarHistoria();
                }
            });

            // Navegacion historias
            $(document).on('click', '.rs-historia-nav-prev', function() {
                self.historiaAnterior();
            });

            $(document).on('click', '.rs-historia-nav-next', function() {
                self.historiaSiguiente();
            });

            // Tabs del feed
            $(document).on('click', '.rs-feed-tab', function() {
                self.cambiarTab($(this));
            });

            // Busqueda
            $(document).on('submit', '.rs-buscar-form', function(e) {
                e.preventDefault();
                self.buscar($(this));
            });

            // Hashtags y menciones clickeables
            $(document).on('click', '.rs-hashtag', function() {
                self.buscarHashtag($(this).data('tag'));
            });

            $(document).on('click', '.rs-mencion', function() {
                self.verPerfil($(this).data('usuario'));
            });

            // Menu de post
            $(document).on('click', '.rs-post-menu-btn', function(e) {
                e.stopPropagation();
                self.toggleMenuPost($(this));
            });

            $(document).on('click', function() {
                $('.rs-post-menu').removeClass('active');
            });

            // Notificaciones
            $(document).on('click', '.rs-notificacion', function() {
                self.marcarNotificacionLeida($(this));
            });

            // Cargar mas comentarios
            $(document).on('click', '.rs-cargar-mas-comentarios', function() {
                self.cargarMasComentarios($(this));
            });

            // Teclas
            $(document).on('keydown', function(e) {
                if ($('.rs-historia-modal').length) {
                    if (e.key === 'ArrowLeft') self.historiaAnterior();
                    if (e.key === 'ArrowRight') self.historiaSiguiente();
                    if (e.key === 'Escape') self.cerrarHistoria();
                }
            });
        },

        // ========================================
        // Publicaciones
        // ========================================
        crearPublicacion: function($form) {
            const self = this;
            const $textarea = $form.find('.rs-crear-post-textarea');
            const $btn = $form.find('.rs-btn-publicar');
            const contenido = $textarea.val().trim();

            if (!contenido && !this.state.adjuntosPendientes?.length) {
                this.mostrarError('Escribe algo o adjunta una imagen');
                return;
            }

            $btn.prop('disabled', true).text('Publicando...');

            const formData = new FormData();
            formData.append('action', 'rs_crear_publicacion');
            formData.append('nonce', this.config.nonce);
            formData.append('contenido', contenido);
            formData.append('visibilidad', $form.find('[name="visibilidad"]').val() || 'comunidad');

            if (this.state.adjuntosPendientes) {
                this.state.adjuntosPendientes.forEach((file, index) => {
                    formData.append('adjuntos[]', file);
                });
            }

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $textarea.val('');
                        self.state.adjuntosPendientes = [];
                        self.limpiarPreviewAdjuntos();

                        if (response.data.html) {
                            $('.rs-feed').prepend(response.data.html);
                        }

                        self.mostrarExito('Publicacion creada');
                    } else {
                        self.mostrarError(response.data?.message || 'Error al publicar');
                    }
                },
                error: function() {
                    self.mostrarError('Error de conexion');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Publicar');
                }
            });
        },

        toggleLike: function($btn) {
            const self = this;
            const postId = $btn.closest('.rs-post').data('post-id');
            const isLiked = $btn.hasClass('rs-liked');

            $btn.addClass('rs-animating');
            setTimeout(() => $btn.removeClass('rs-animating'), 300);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_toggle_like',
                    nonce: this.config.nonce,
                    publicacion_id: postId,
                    tipo: 'me_gusta'
                },
                success: function(response) {
                    if (response.success) {
                        $btn.toggleClass('rs-liked');
                        const $count = $btn.find('.rs-like-count');
                        $count.text(response.data.total);

                        const $statsCount = $btn.closest('.rs-post').find('.rs-post-likes-count span');
                        if ($statsCount.length) {
                            $statsCount.text(response.data.total + ' me gusta');
                        }
                    }
                }
            });
        },

        compartirPost: function($btn) {
            const postId = $btn.closest('.rs-post').data('post-id');
            const postUrl = window.location.origin + '/mi-portal/red-social/?publicacion_id=' + postId;

            if (navigator.share) {
                navigator.share({
                    title: 'Mira esta publicacion',
                    url: postUrl
                });
            } else {
                this.copiarAlPortapapeles(postUrl);
                this.mostrarExito('Enlace copiado');
            }
        },

        guardarPost: function($btn) {
            const postId = $btn.closest('.rs-post').data('post-id');
            const isGuardado = $btn.hasClass('rs-guardado');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_guardar_post',
                    nonce: this.config.nonce,
                    publicacion_id: postId
                },
                success: (response) => {
                    if (response.success) {
                        $btn.toggleClass('rs-guardado');
                        this.mostrarExito(isGuardado ? 'Eliminado de guardados' : 'Guardado');
                    }
                }
            });
        },

        // ========================================
        // Comentarios
        // ========================================
        toggleComentarios: function($btn) {
            const $post = $btn.closest('.rs-post');
            const $comentarios = $post.find('.rs-comentarios');

            if ($comentarios.length) {
                $comentarios.slideToggle();
            } else {
                this.cargarComentarios($post);
            }
        },

        cargarComentarios: function($post) {
            const postId = $post.data('post-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_obtener_comentarios',
                    nonce: this.config.nonce,
                    publicacion_id: postId
                },
                success: (response) => {
                    if (response.success) {
                        $post.append(response.data.html);
                    }
                }
            });
        },

        enviarComentario: function($form) {
            const $input = $form.find('.rs-comentar-input');
            const contenido = $input.val().trim();
            const postId = $form.closest('.rs-post').data('post-id');
            const padreId = $form.data('padre-id') || 0;

            if (!contenido) return;

            const $btn = $form.find('.rs-comentar-enviar');
            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_crear_comentario',
                    nonce: this.config.nonce,
                    publicacion_id: postId,
                    contenido: contenido,
                    padre_id: padreId
                },
                success: (response) => {
                    if (response.success) {
                        $input.val('');
                        const $lista = $form.closest('.rs-comentarios').find('.rs-comentarios-lista');
                        $lista.append(response.data.html);

                        const $countBtn = $form.closest('.rs-post').find('.rs-post-accion[data-accion="comentar"] span');
                        const currentCount = parseInt($countBtn.text()) || 0;
                        $countBtn.text(currentCount + 1);
                    } else {
                        this.mostrarError(response.data?.message || 'Error');
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false);
                }
            });
        },

        likeComentario: function($btn) {
            const comentarioId = $btn.closest('.rs-comentario').data('comentario-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_like_comentario',
                    nonce: this.config.nonce,
                    comentario_id: comentarioId
                },
                success: (response) => {
                    if (response.success) {
                        $btn.toggleClass('rs-liked');
                        $btn.text(response.data.total > 0 ? response.data.total + ' Me gusta' : 'Me gusta');
                    }
                }
            });
        },

        // ========================================
        // Seguir/Dejar de seguir
        // ========================================
        toggleSeguir: function($btn) {
            const usuarioId = $btn.data('usuario-id');
            const isSiguiendo = $btn.hasClass('rs-siguiendo');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_toggle_seguir',
                    nonce: this.config.nonce,
                    usuario_id: usuarioId
                },
                success: (response) => {
                    if (response.success) {
                        $btn.toggleClass('rs-siguiendo');
                        $btn.text(isSiguiendo ? 'Seguir' : 'Siguiendo');

                        // Actualizar contador si estamos en perfil
                        const $seguidores = $('.rs-perfil-stat[data-tipo="seguidores"] .rs-perfil-stat-num');
                        if ($seguidores.length) {
                            $seguidores.text(response.data.seguidores);
                        }
                    }
                }
            });
        },

        // ========================================
        // Historias
        // ========================================
        abrirHistoria: function($historia) {
            const usuarioId = $historia.data('usuario-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_obtener_historias',
                    nonce: this.config.nonce,
                    usuario_id: usuarioId
                },
                success: (response) => {
                    if (response.success && response.data.historias.length) {
                        this.mostrarHistoriaModal(response.data);
                    }
                }
            });
        },

        mostrarHistoriaModal: function(data) {
            this.state.historiasActuales = data.historias;
            this.state.historiaActual = 0;

            const historiaHtml = `
                <div class="rs-historia-modal">
                    <div class="rs-historia-modal-contenido">
                        <div class="rs-historia-progreso"></div>
                        <div class="rs-historia-header">
                            <img src="${data.usuario.avatar}" alt="">
                            <div class="rs-historia-header-info">
                                <div class="rs-historia-header-nombre">${data.usuario.nombre}</div>
                                <div class="rs-historia-header-tiempo"></div>
                            </div>
                            <button class="rs-historia-cerrar">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="rs-historia-media-container"></div>
                        <button class="rs-historia-nav-prev"></button>
                        <button class="rs-historia-nav-next"></button>
                    </div>
                </div>
            `;

            $('body').append(historiaHtml).addClass('rs-modal-abierto');
            this.mostrarHistoriaActual();
        },

        mostrarHistoriaActual: function() {
            const historia = this.state.historiasActuales[this.state.historiaActual];
            if (!historia) return;

            const $modal = $('.rs-historia-modal-contenido');
            const $progreso = $modal.find('.rs-historia-progreso');
            const $mediaContainer = $modal.find('.rs-historia-media-container');

            // Actualizar barras de progreso
            $progreso.html(this.state.historiasActuales.map((h, i) => `
                <div class="rs-historia-progreso-barra ${i < this.state.historiaActual ? 'rs-completada' : ''}">
                    <div class="rs-historia-progreso-fill"></div>
                </div>
            `).join(''));

            // Mostrar media
            if (historia.tipo === 'video') {
                $mediaContainer.html(`<video class="rs-historia-media" src="${historia.url}" autoplay muted></video>`);
            } else {
                $mediaContainer.html(`<img class="rs-historia-media" src="${historia.url}" alt="">`);
            }

            $modal.find('.rs-historia-header-tiempo').text(historia.tiempo);

            // Iniciar timer
            this.iniciarTimerHistoria();
        },

        iniciarTimerHistoria: function() {
            if (this.state.historiaTimer) {
                clearInterval(this.state.historiaTimer);
            }

            const duracion = 5000;
            let progreso = 0;
            const $fill = $('.rs-historia-progreso-barra').eq(this.state.historiaActual).find('.rs-historia-progreso-fill');

            this.state.historiaTimer = setInterval(() => {
                progreso += 100;
                $fill.css('width', (progreso / duracion * 100) + '%');

                if (progreso >= duracion) {
                    this.historiaSiguiente();
                }
            }, 100);
        },

        historiaSiguiente: function() {
            if (this.state.historiaActual < this.state.historiasActuales.length - 1) {
                this.state.historiaActual++;
                this.mostrarHistoriaActual();
            } else {
                this.cerrarHistoria();
            }
        },

        historiaAnterior: function() {
            if (this.state.historiaActual > 0) {
                this.state.historiaActual--;
                this.mostrarHistoriaActual();
            }
        },

        cerrarHistoria: function() {
            if (this.state.historiaTimer) {
                clearInterval(this.state.historiaTimer);
            }
            $('.rs-historia-modal').remove();
            $('body').removeClass('rs-modal-abierto');
        },

        // ========================================
        // Feed infinito
        // ========================================
        initInfiniteScroll: function() {
            const self = this;
            let throttle = false;

            $(window).on('scroll', function() {
                if (throttle) return;
                throttle = true;

                setTimeout(() => {
                    const scrollPos = $(window).scrollTop() + $(window).height();
                    const docHeight = $(document).height();

                    if (scrollPos > docHeight - 500 && !self.state.cargando && self.state.hayMas) {
                        self.cargarMasPosts();
                    }
                    throttle = false;
                }, 200);
            });
        },

        cargarMasPosts: function() {
            // Verificar sesión válida antes de hacer llamada AJAX
            if (!this.config.nonce) {
                this.state.hayMas = false;
                return;
            }

            this.state.cargando = true;
            const $feed = $('.rs-feed');
            const $loader = $('<div class="rs-loading"><div class="rs-spinner"></div></div>');
            $feed.append($loader);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_cargar_feed',
                    nonce: this.config.nonce,
                    desde: this.state.ultimoPostId,
                    tipo: $('.rs-feed-tab.active').data('tipo') || 'timeline'
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.posts.length) {
                            $feed.append(response.data.html);
                            this.state.ultimoPostId = response.data.ultimo_id;
                        }
                        this.state.hayMas = response.data.hay_mas;
                    } else {
                        // Error del servidor - detener infinite scroll
                        this.state.hayMas = false;
                    }
                },
                error: () => {
                    // Error de red o 400/500 - detener infinite scroll para evitar bucle
                    this.state.hayMas = false;
                    console.warn('RedSocial: Error cargando feed, infinite scroll desactivado');
                },
                complete: () => {
                    $loader.remove();
                    this.state.cargando = false;
                }
            });
        },

        // ========================================
        // Menciones y Hashtags
        // ========================================
        initMenciones: function() {
            $(document).on('input', '.rs-crear-post-textarea, .rs-comentar-input', (e) => {
                const $input = $(e.target);
                const texto = $input.val();
                const cursorPos = $input[0].selectionStart;
                const textoHastaCursor = texto.substring(0, cursorPos);
                const matchMencion = textoHastaCursor.match(/@(\w*)$/);

                if (matchMencion) {
                    this.buscarUsuariosMencion(matchMencion[1], $input);
                } else {
                    this.cerrarSugerenciasMencion();
                }
            });
        },

        buscarUsuariosMencion: function(query, $input) {
            if (query.length < 2) {
                this.cerrarSugerenciasMencion();
                return;
            }

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rs_buscar_usuarios',
                    nonce: this.config.nonce,
                    query: query,
                    limite: 5
                },
                success: (response) => {
                    if (response.success && response.data.usuarios.length) {
                        this.mostrarSugerenciasMencion(response.data.usuarios, $input);
                    } else {
                        this.cerrarSugerenciasMencion();
                    }
                }
            });
        },

        mostrarSugerenciasMencion: function(usuarios, $input) {
            this.cerrarSugerenciasMencion();

            const $sugerencias = $('<div class="rs-menciones-sugerencias"></div>');
            usuarios.forEach(usuario => {
                $sugerencias.append(`
                    <div class="rs-mencion-sugerencia" data-username="${usuario.username}">
                        <img src="${usuario.avatar}" alt="">
                        <div>
                            <strong>${usuario.nombre}</strong>
                            <span>@${usuario.username}</span>
                        </div>
                    </div>
                `);
            });

            $input.after($sugerencias);

            $sugerencias.on('click', '.rs-mencion-sugerencia', (e) => {
                const username = $(e.currentTarget).data('username');
                this.insertarMencion(username, $input);
            });
        },

        insertarMencion: function(username, $input) {
            const texto = $input.val();
            const cursorPos = $input[0].selectionStart;
            const textoAntes = texto.substring(0, cursorPos).replace(/@\w*$/, '@' + username + ' ');
            const textoDespues = texto.substring(cursorPos);

            $input.val(textoAntes + textoDespues);
            $input[0].selectionStart = $input[0].selectionEnd = textoAntes.length;
            $input.focus();

            this.cerrarSugerenciasMencion();
        },

        cerrarSugerenciasMencion: function() {
            $('.rs-menciones-sugerencias').remove();
        },

        initHashtags: function() {
            // Los hashtags se detectan al publicar
        },

        buscarHashtag: function(tag) {
            window.location.href = '/mi-portal/red-social/explorar/?hashtag=' + encodeURIComponent(tag);
        },

        // ========================================
        // Utilidades
        // ========================================
        actualizarContadorCaracteres: function($textarea) {
            const caracteres = $textarea.val().length;
            const max = this.config.maxCaracteres;
            const $contador = $textarea.closest('.rs-crear-post').find('.rs-contador-caracteres');

            if (!$contador.length) {
                $textarea.after(`<div class="rs-contador-caracteres">${caracteres}/${max}</div>`);
            } else {
                $contador.text(`${caracteres}/${max}`);
                $contador.toggleClass('rs-limite', caracteres > max * 0.9);
                $contador.toggleClass('rs-excedido', caracteres > max);
            }
        },

        autoExpandTextarea: function($textarea) {
            $textarea.css('height', 'auto');
            $textarea.css('height', $textarea[0].scrollHeight + 'px');
        },

        seleccionarImagenes: function() {
            const $input = $('<input type="file" accept="image/*" multiple>');
            $input.on('change', (e) => {
                const files = Array.from(e.target.files);
                if (files.length > this.config.maxImagenes) {
                    this.mostrarError(`Maximo ${this.config.maxImagenes} imagenes`);
                    return;
                }
                this.state.adjuntosPendientes = files;
                this.mostrarPreviewAdjuntos(files);
            });
            $input.click();
        },

        mostrarPreviewAdjuntos: function(files) {
            let $preview = $('.rs-adjuntos-preview');
            if (!$preview.length) {
                $preview = $('<div class="rs-adjuntos-preview"></div>');
                $('.rs-crear-post-acciones').before($preview);
            }

            $preview.empty();
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    $preview.append(`
                        <div class="rs-adjunto-preview" data-index="${index}">
                            <img src="${e.target.result}" alt="">
                            <button class="rs-adjunto-eliminar" data-index="${index}">&times;</button>
                        </div>
                    `);
                };
                reader.readAsDataURL(file);
            });

            $preview.on('click', '.rs-adjunto-eliminar', (e) => {
                const index = $(e.target).data('index');
                this.state.adjuntosPendientes.splice(index, 1);
                this.mostrarPreviewAdjuntos(this.state.adjuntosPendientes);
            });
        },

        limpiarPreviewAdjuntos: function() {
            $('.rs-adjuntos-preview').remove();
        },

        copiarAlPortapapeles: function(texto) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(texto);
            } else {
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(texto).select();
                document.execCommand('copy');
                $temp.remove();
            }
        },

        cambiarTab: function($tab) {
            $('.rs-feed-tab').removeClass('active');
            $tab.addClass('active');

            this.state.ultimoPostId = 0;
            this.state.hayMas = true;
            $('.rs-feed .rs-post').remove();
            this.cargarMasPosts();
        },

        verPerfil: function(usuarioId) {
            window.location.href = '/mi-portal/red-social/perfil/?usuario_id=' + usuarioId;
        },

        toggleMenuPost: function($btn) {
            const $menu = $btn.siblings('.rs-post-menu');
            $('.rs-post-menu').not($menu).removeClass('active');
            $menu.toggleClass('active');
        },

        mostrarExito: function(mensaje) {
            this.mostrarToast(mensaje, 'exito');
        },

        mostrarError: function(mensaje) {
            this.mostrarToast(mensaje, 'error');
        },

        mostrarToast: function(mensaje, tipo) {
            const $toast = $(`<div class="rs-toast rs-toast-${tipo}">${mensaje}</div>`);
            $('body').append($toast);

            setTimeout(() => $toast.addClass('rs-visible'), 10);
            setTimeout(() => {
                $toast.removeClass('rs-visible');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        RedSocial.init();
    });

    // Exponer globalmente
    window.FlavorRedSocial = RedSocial;

})(jQuery);
