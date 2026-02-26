/**
 * Mi Red Social - JavaScript
 *
 * Maneja la interactividad del feed unificado:
 * - Scroll infinito
 * - Publicar contenido
 * - Likes y comentarios
 * - Filtros de contenido
 * - Búsqueda
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // Namespace global
    window.MiRedSocial = window.MiRedSocial || {};

    // Configuración
    const config = {
        ajaxUrl: flavorMiRed?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: flavorMiRed?.nonce || '',
        restUrl: flavorMiRed?.restUrl || '',
        restNonce: flavorMiRed?.restNonce || '',
        i18n: flavorMiRed?.i18n || {},
        contentTypes: flavorMiRed?.contentTypes || {},
    };

    // Estado del feed
    const feedState = {
        loading: false,
        offset: 20, // Empezamos en 20 porque ya hay 20 items cargados
        tipo: 'todos',
        hasMore: true,
    };

    /**
     * Inicializa el módulo de feed
     */
    MiRedSocial.initFeed = function() {
        initComposer();
        initInfiniteScroll();
        initFilters();
        initCardActions();
        initSearch();
        initDropdowns();
    };

    /**
     * Inicializa el composer de publicaciones
     */
    function initComposer() {
        const $trigger = $('#abrir-composer');
        const $expanded = $('#composer-expandido');
        const $close = $('#cerrar-composer');
        const $form = $('#form-publicar');

        // Abrir composer
        $trigger.on('click', function() {
            $expanded.removeAttr('hidden').hide().slideDown(200);
            $expanded.find('textarea').focus();
        });

        // Cerrar composer
        $close.on('click', function() {
            $expanded.slideUp(200, function() {
                $(this).attr('hidden', true);
                $form[0].reset();
            });
        });

        // Submit publicación
        $form.on('submit', function(e) {
            e.preventDefault();
            submitPublicacion($(this));
        });

        // Preview de archivos
        $form.find('input[type="file"]').on('change', function() {
            previewFile(this);
        });

        // Herramientas del composer
        $form.find('.mi-red-composer-tool[data-accion]').on('click', function() {
            const action = $(this).data('accion');
            handleComposerTool(action, $form.find('textarea'));
        });
    }

    /**
     * Envía una nueva publicación
     */
    function submitPublicacion($form) {
        const $btn = $form.find('#btn-publicar');
        const contenido = $form.find('textarea[name="contenido"]').val().trim();

        if (!contenido) {
            showToast(config.i18n.error || 'Error', 'error');
            return;
        }

        // Deshabilitar botón
        $btn.prop('disabled', true).text(config.i18n.cargando || 'Publicando...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mi_red_crear_publicacion',
                nonce: config.nonce,
                contenido: contenido,
                tipo: 'texto',
            },
            success: function(response) {
                if (response.success) {
                    showToast(config.i18n.publicado || 'Publicado', 'success');
                    $form[0].reset();
                    $('#composer-expandido').slideUp(200, function() {
                        $(this).attr('hidden', true);
                    });
                    // Recargar feed
                    reloadFeed();
                } else {
                    showToast(response.data?.message || config.i18n.error, 'error');
                }
            },
            error: function() {
                showToast(config.i18n.error || 'Error', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Publicar');
            },
        });
    }

    /**
     * Preview de archivo subido
     */
    function previewFile(input) {
        const $preview = $('#preview-adjuntos');
        const file = input.files[0];

        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            let html = '';
            if (file.type.startsWith('image/')) {
                html = `<div class="mi-red-preview-item">
                    <img src="${e.target.result}" alt="">
                    <button type="button" class="mi-red-preview-remove">&times;</button>
                </div>`;
            } else if (file.type.startsWith('video/')) {
                html = `<div class="mi-red-preview-item">
                    <video src="${e.target.result}" controls></video>
                    <button type="button" class="mi-red-preview-remove">&times;</button>
                </div>`;
            }
            $preview.html(html).removeAttr('hidden');
        };
        reader.readAsDataURL(file);
    }

    /**
     * Maneja herramientas del composer
     */
    function handleComposerTool(action, $textarea) {
        const pos = $textarea[0].selectionStart;
        const value = $textarea.val();

        switch (action) {
            case 'emoji':
                // Insertar emoji picker (simplificado)
                insertAtCursor($textarea, '😊');
                break;
            case 'hashtag':
                insertAtCursor($textarea, '#');
                break;
            case 'mencion':
                insertAtCursor($textarea, '@');
                break;
        }
    }

    /**
     * Inserta texto en la posición del cursor
     */
    function insertAtCursor($textarea, text) {
        const el = $textarea[0];
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const value = $textarea.val();

        $textarea.val(value.substring(0, start) + text + value.substring(end));
        el.selectionStart = el.selectionEnd = start + text.length;
        $textarea.focus();
    }

    /**
     * Inicializa scroll infinito
     * Usa IntersectionObserver si está disponible (más eficiente)
     * Fallback a scroll listener con debounce
     */
    function initInfiniteScroll() {
        const $feedList = $('#feed-lista');
        const $loader = $('#feed-loader');
        const $feedEnd = $('#feed-fin');
        const loaderEl = $loader[0];

        // Usar IntersectionObserver si está disponible
        if ('IntersectionObserver' in window && loaderEl) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && !feedState.loading && feedState.hasMore) {
                        loadMoreItems();
                    }
                });
            }, {
                root: null,
                rootMargin: '200px',
                threshold: 0
            });

            observer.observe(loaderEl);
            return; // No necesitamos el scroll listener
        }

        // Fallback: scroll listener con debounce
        const $window = $(window);
        let scrollTimer;

        $window.on('scroll', function() {
            if (scrollTimer) clearTimeout(scrollTimer);

            scrollTimer = setTimeout(function() {
                const scrollPos = $window.scrollTop() + $window.height();
                const docHeight = $(document).height();

                if (scrollPos >= docHeight - 500 && !feedState.loading && feedState.hasMore) {
                    loadMoreItems();
                }
            }, 100);
        });

        function loadMoreItems() {
            feedState.loading = true;
            $loader.removeAttr('hidden');

            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mi_red_cargar_feed',
                    nonce: config.nonce,
                    offset: feedState.offset,
                    limite: 20,
                    tipo: feedState.tipo,
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.html) {
                            $feedList.append(response.data.html);
                            // Re-inicializar eventos en nuevos items
                            initCardActions();
                        }

                        feedState.offset = response.data.offset;
                        feedState.hasMore = response.data.hay_mas;

                        if (!feedState.hasMore) {
                            $feedEnd.removeAttr('hidden');
                        }
                    }
                },
                error: function() {
                    showToast(config.i18n.error || 'Error al cargar', 'error');
                },
                complete: function() {
                    feedState.loading = false;
                    $loader.attr('hidden', true);
                },
            });
        }
    }

    /**
     * Inicializa filtros de contenido
     */
    function initFilters() {
        const $filters = $('#filtros-tipo');

        $filters.on('click', '.mi-red-filter-btn', function() {
            const $btn = $(this);
            const tipo = $btn.data('tipo');

            // Actualizar UI
            $filters.find('.mi-red-filter-btn').removeClass('mi-red-filter-btn--active');
            $btn.addClass('mi-red-filter-btn--active');

            // Actualizar estado
            feedState.tipo = tipo;
            feedState.offset = 0;
            feedState.hasMore = true;

            // Recargar feed
            reloadFeed();
        });
    }

    /**
     * Recarga el feed con los filtros actuales
     */
    function reloadFeed() {
        const $feedList = $('#feed-lista');
        const $loader = $('#feed-loader');
        const $feedEnd = $('#feed-fin');

        feedState.loading = true;
        $feedList.empty();
        $loader.removeAttr('hidden');
        $feedEnd.attr('hidden', true);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mi_red_cargar_feed',
                nonce: config.nonce,
                offset: 0,
                limite: 20,
                tipo: feedState.tipo,
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.html) {
                        $feedList.html(response.data.html);
                        initCardActions();
                    } else {
                        $feedList.html(`
                            <div class="mi-red-empty-state">
                                <div class="mi-red-empty-state__icon">📭</div>
                                <p>${config.i18n.sinResultados || 'No hay contenido'}</p>
                            </div>
                        `);
                    }

                    feedState.offset = response.data.offset;
                    feedState.hasMore = response.data.hay_mas;
                }
            },
            complete: function() {
                feedState.loading = false;
                $loader.attr('hidden', true);
            },
        });
    }

    /**
     * Inicializa acciones de las tarjetas
     */
    function initCardActions() {
        // Like
        $(document).off('click', '[data-action="like"]').on('click', '[data-action="like"]', function(e) {
            e.preventDefault();
            handleLike($(this));
        });

        // Comentar
        $(document).off('click', '[data-action="comentar"]').on('click', '[data-action="comentar"]', function(e) {
            e.preventDefault();
            handleComment($(this));
        });

        // Compartir
        $(document).off('click', '[data-action="compartir"]').on('click', '[data-action="compartir"]', function(e) {
            e.preventDefault();
            handleShare($(this));
        });

        // Guardar
        $(document).off('click', '[data-action="guardar"]').on('click', '[data-action="guardar"]', function(e) {
            e.preventDefault();
            handleSave($(this));
        });

        // Submit comentario
        $(document).off('submit', '.mi-red-comments__form').on('submit', '.mi-red-comments__form', function(e) {
            e.preventDefault();
            submitComment($(this));
        });
    }

    /**
     * Maneja like
     */
    function handleLike($btn) {
        const itemId = $btn.data('item');
        const tipo = $btn.data('tipo');

        $btn.prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mi_red_toggle_like',
                nonce: config.nonce,
                item_id: itemId,
                tipo_item: tipo,
            },
            success: function(response) {
                if (response.success) {
                    $btn.toggleClass('active', response.data.liked);

                    // Actualizar contador
                    const $card = $btn.closest('.mi-red-card');
                    const $count = $card.find('[data-stat="likes"]');
                    if ($count.length) {
                        $count.text(response.data.count);
                    }
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            },
        });
    }

    /**
     * Maneja comentarios
     */
    function handleComment($btn) {
        const itemId = $btn.data('item');
        const $card = $btn.closest('.mi-red-card');
        const $comments = $card.find(`#comentarios-${itemId}`);

        // Toggle sección de comentarios
        if ($comments.attr('hidden')) {
            $comments.removeAttr('hidden');
            loadComments(itemId, $comments);
        } else {
            $comments.attr('hidden', true);
        }
    }

    /**
     * Carga comentarios de un item
     */
    function loadComments(itemId, $container) {
        const $list = $container.find('.mi-red-comments__list');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mi_red_obtener_comentarios',
                nonce: config.nonce,
                item_id: itemId,
                tipo_item: 'publicacion',
            },
            success: function(response) {
                if (response.success && response.data.comentarios) {
                    let html = '';
                    response.data.comentarios.forEach(function(com) {
                        html += `
                            <div class="mi-red-comment">
                                <img src="${com.avatar}" alt="" class="mi-red-comment__avatar">
                                <div class="mi-red-comment__body">
                                    <span class="mi-red-comment__author">${com.autor_nombre}</span>
                                    <span class="mi-red-comment__time">${com.fecha_humana}</span>
                                    <p class="mi-red-comment__text">${com.contenido}</p>
                                </div>
                            </div>
                        `;
                    });
                    $list.html(html || `<p class="mi-red-comments__empty">Sé el primero en comentar</p>`);
                }
            },
        });
    }

    /**
     * Envía un comentario
     */
    function submitComment($form) {
        const itemId = $form.data('item');
        const tipo = $form.data('tipo');
        const $input = $form.find('input[name="comentario"]');
        const contenido = $input.val().trim();

        if (!contenido) return;

        const $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mi_red_crear_comentario',
                nonce: config.nonce,
                item_id: itemId,
                tipo_item: tipo,
                contenido: contenido,
            },
            success: function(response) {
                if (response.success) {
                    $input.val('');
                    // Recargar comentarios
                    loadComments(itemId, $form.closest('.mi-red-card__comments'));

                    // Actualizar contador
                    const $card = $form.closest('.mi-red-card');
                    const $count = $card.find('[data-stat="comentarios"]');
                    if ($count.length) {
                        $count.text(parseInt($count.text()) + 1);
                    }
                } else {
                    showToast(response.data?.message || config.i18n.error, 'error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            },
        });
    }

    /**
     * Maneja compartir
     */
    function handleShare($btn) {
        const url = $btn.data('url');

        if (navigator.share) {
            navigator.share({
                title: document.title,
                url: url,
            }).catch(console.error);
        } else {
            // Fallback: copiar al portapapeles
            copyToClipboard(url);
            showToast('Enlace copiado', 'success');
        }
    }

    /**
     * Maneja guardar
     */
    function handleSave($btn) {
        $btn.toggleClass('active');
        showToast($btn.hasClass('active') ? 'Guardado' : 'Eliminado de guardados', 'info');
    }

    /**
     * Inicializa búsqueda
     */
    function initSearch() {
        const $btnMobile = $('#btn-buscar-mobile');
        const $modal = $('#modal-buscar');
        const $backdrop = $modal.find('.mi-red-modal__backdrop');
        const $close = $modal.find('.mi-red-modal__close');

        $btnMobile.on('click', function() {
            $modal.removeAttr('hidden');
            $modal.find('input').focus();
        });

        $backdrop.on('click', closeSearchModal);
        $close.on('click', closeSearchModal);

        function closeSearchModal() {
            $modal.attr('hidden', true);
        }

        // Búsqueda en tiempo real
        let searchTimer;
        $modal.find('input[type="search"]').on('input', function() {
            const query = $(this).val().trim();

            if (searchTimer) clearTimeout(searchTimer);

            if (query.length < 2) {
                $('#resultados-busqueda').empty();
                return;
            }

            searchTimer = setTimeout(function() {
                performSearch(query);
            }, 300);
        });
    }

    /**
     * Realiza búsqueda
     */
    function performSearch(query) {
        const $results = $('#resultados-busqueda');

        $results.html(`<div class="mi-red-loader"><div class="mi-red-loader__spinner"></div></div>`);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mi_red_buscar',
                nonce: config.nonce,
                termino: query,
            },
            success: function(response) {
                if (response.success) {
                    renderSearchResults(response.data, $results);
                }
            },
        });
    }

    /**
     * Renderiza resultados de búsqueda
     */
    function renderSearchResults(data, $container) {
        let html = '';

        // Usuarios
        if (data.usuarios && data.usuarios.length) {
            html += '<div class="mi-red-search-section"><h4>Personas</h4>';
            data.usuarios.forEach(function(u) {
                html += `
                    <a href="/mi-portal/mi-red/perfil/?id=${u.ID}" class="mi-red-search-result">
                        <img src="${u.avatar}" alt="" class="mi-red-search-result__avatar">
                        <span class="mi-red-search-result__name">${u.display_name}</span>
                    </a>
                `;
            });
            html += '</div>';
        }

        // Publicaciones
        if (data.publicaciones && data.publicaciones.length) {
            html += '<div class="mi-red-search-section"><h4>Publicaciones</h4>';
            data.publicaciones.forEach(function(p) {
                html += `
                    <a href="${p.url}" class="mi-red-search-result">
                        <span class="mi-red-search-result__text">${p.contenido.texto.substring(0, 100)}...</span>
                    </a>
                `;
            });
            html += '</div>';
        }

        if (!html) {
            html = `<p class="mi-red-search-empty">${config.i18n.sinResultados || 'Sin resultados'}</p>`;
        }

        $container.html(html);
    }

    /**
     * Inicializa dropdowns
     */
    function initDropdowns() {
        $(document).on('click', '.mi-red-card__menu-btn', function(e) {
            e.stopPropagation();
            const $dropdown = $(this).siblings('.mi-red-card__dropdown');

            // Cerrar otros dropdowns
            $('.mi-red-card__dropdown').not($dropdown).attr('hidden', true);

            // Toggle este dropdown
            $dropdown.attr('hidden') ? $dropdown.removeAttr('hidden') : $dropdown.attr('hidden', true);
        });

        // Cerrar al hacer clic fuera
        $(document).on('click', function() {
            $('.mi-red-card__dropdown').attr('hidden', true);
        });
    }

    /**
     * Muestra notificación toast
     */
    function showToast(message, type = 'info') {
        // Crear toast si no existe
        let $toast = $('#mi-red-toast');
        if (!$toast.length) {
            $toast = $('<div id="mi-red-toast" class="mi-red-toast"></div>');
            $('body').append($toast);
        }

        // Mostrar mensaje
        $toast
            .removeClass('mi-red-toast--success mi-red-toast--error mi-red-toast--info')
            .addClass(`mi-red-toast--${type}`)
            .text(message)
            .addClass('mi-red-toast--visible');

        // Ocultar después de 3 segundos
        setTimeout(function() {
            $toast.removeClass('mi-red-toast--visible');
        }, 3000);
    }

    /**
     * Copia texto al portapapeles
     */
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        }
    }

    // Estilos para toast (añadir dinámicamente)
    const toastStyles = `
        .mi-red-toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            padding: 12px 24px;
            background: #1f2937;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .mi-red-toast--visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        .mi-red-toast--success { background: #10b981; }
        .mi-red-toast--error { background: #ef4444; }
        .mi-red-toast--info { background: #3b82f6; }

        .mi-red-comment {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .mi-red-comment__avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        .mi-red-comment__body { flex: 1; }
        .mi-red-comment__author {
            font-weight: 600;
            font-size: 14px;
            margin-right: 8px;
        }
        .mi-red-comment__time {
            font-size: 12px;
            color: #9ca3af;
        }
        .mi-red-comment__text {
            margin: 4px 0 0;
            font-size: 14px;
        }
        .mi-red-comments__empty {
            text-align: center;
            color: #9ca3af;
            padding: 20px;
        }

        .mi-red-search-section {
            margin-bottom: 16px;
        }
        .mi-red-search-section h4 {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin: 0 0 8px;
        }
        .mi-red-search-result {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
        }
        .mi-red-search-result:hover {
            background: #f3f4f6;
        }
        .mi-red-search-result__avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .mi-red-search-result__name {
            font-weight: 500;
        }
        .mi-red-search-result__text {
            font-size: 14px;
            color: #4b5563;
        }
        .mi-red-search-empty {
            text-align: center;
            color: #9ca3af;
            padding: 20px;
        }
    `;

    // Añadir estilos al cargar
    $('<style>').text(toastStyles).appendTo('head');

    // =========================================================================
    // LAZY LOADING AVANZADO
    // =========================================================================

    /**
     * Módulo de Lazy Loading para multimedia
     * - Usa IntersectionObserver para detectar elementos visibles
     * - Carga videos solo cuando están cerca del viewport
     * - Placeholder animado mientras carga
     */
    MiRedSocial.LazyLoad = {
        observer: null,
        options: {
            rootMargin: '200px 0px', // Cargar 200px antes de que sea visible
            threshold: 0.01
        },

        /**
         * Inicializa el lazy loading
         */
        init: function() {
            if (!('IntersectionObserver' in window)) {
                // Fallback: cargar todo inmediatamente
                this.loadAllMedia();
                return;
            }

            this.observer = new IntersectionObserver(
                this.handleIntersection.bind(this),
                this.options
            );

            this.observeMedia();

            // Re-observar cuando se añade nuevo contenido (scroll infinito)
            $(document).on('mi-red:content-loaded', () => {
                this.observeMedia();
            });
        },

        /**
         * Observa elementos de multimedia
         */
        observeMedia: function() {
            // Videos con data-src
            document.querySelectorAll('video[data-src]:not(.lazy-loaded)').forEach(el => {
                this.observer.observe(el);
            });

            // Iframes (para embeds de YouTube, etc.)
            document.querySelectorAll('iframe[data-src]:not(.lazy-loaded)').forEach(el => {
                this.observer.observe(el);
            });

            // Imágenes sin loading="lazy" nativo (fallback para navegadores antiguos)
            if (!('loading' in HTMLImageElement.prototype)) {
                document.querySelectorAll('img[loading="lazy"]:not(.lazy-loaded)').forEach(el => {
                    el.removeAttribute('loading');
                    el.dataset.src = el.src;
                    el.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
                    this.observer.observe(el);
                });
            }
        },

        /**
         * Maneja la intersección
         */
        handleIntersection: function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadElement(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        },

        /**
         * Carga un elemento multimedia
         */
        loadElement: function(el) {
            const src = el.dataset.src;
            if (!src) return;

            el.classList.add('lazy-loading');

            if (el.tagName === 'VIDEO') {
                // Para videos, crear source element
                const source = document.createElement('source');
                source.src = src;
                source.type = 'video/mp4';
                el.appendChild(source);
                el.load();
                el.classList.remove('lazy-loading');
                el.classList.add('lazy-loaded');
            } else if (el.tagName === 'IFRAME') {
                el.src = src;
                el.onload = () => {
                    el.classList.remove('lazy-loading');
                    el.classList.add('lazy-loaded');
                };
            } else if (el.tagName === 'IMG') {
                const img = new Image();
                img.onload = () => {
                    el.src = src;
                    el.classList.remove('lazy-loading');
                    el.classList.add('lazy-loaded');
                };
                img.onerror = () => {
                    el.classList.remove('lazy-loading');
                    el.classList.add('lazy-error');
                };
                img.src = src;
            }
        },

        /**
         * Fallback: carga todo inmediatamente
         */
        loadAllMedia: function() {
            document.querySelectorAll('[data-src]').forEach(el => {
                this.loadElement(el);
            });
        }
    };

    // Estilos para lazy loading
    const lazyStyles = `
        .lazy-loading {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: lazy-shimmer 1.5s infinite;
        }
        @keyframes lazy-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .lazy-loaded {
            animation: lazy-fade-in 0.3s ease-out;
        }
        @keyframes lazy-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .lazy-error {
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lazy-error::after {
            content: '⚠️';
            font-size: 2rem;
        }
    `;
    $('<style>').text(lazyStyles).appendTo('head');

    // =========================================================================
    // OPTIMIZACIÓN DE RENDIMIENTO
    // =========================================================================

    /**
     * Pausar videos que no están visibles
     */
    MiRedSocial.VideoOptimizer = {
        observer: null,

        init: function() {
            if (!('IntersectionObserver' in window)) return;

            this.observer = new IntersectionObserver(
                this.handleIntersection.bind(this),
                { threshold: 0.5 }
            );

            this.observeVideos();

            $(document).on('mi-red:content-loaded', () => {
                this.observeVideos();
            });
        },

        observeVideos: function() {
            document.querySelectorAll('.mi-red-media__video').forEach(video => {
                this.observer.observe(video);
            });
        },

        handleIntersection: function(entries) {
            entries.forEach(entry => {
                const video = entry.target;
                if (!entry.isIntersecting && !video.paused) {
                    video.pause();
                }
            });
        }
    };

    // =========================================================================
    // PWA - SERVICE WORKER Y NOTIFICACIONES PUSH
    // =========================================================================

    /**
     * Módulo PWA para notificaciones push y offline
     */
    MiRedSocial.PWA = {
        swRegistration: null,
        pushSubscription: null,

        /**
         * Inicializa PWA
         */
        init: function() {
            if (!('serviceWorker' in navigator)) {
                console.log('[PWA] Service Worker no soportado');
                return;
            }

            this.registerServiceWorker();
            this.initPushNotifications();
        },

        /**
         * Registra el Service Worker
         */
        registerServiceWorker: async function() {
            try {
                this.swRegistration = await navigator.serviceWorker.register(
                    '/wp-content/plugins/flavor-chat-ia/assets/js/sw-mi-red.js',
                    { scope: '/mi-portal/mi-red/' }
                );

                console.log('[PWA] Service Worker registrado:', this.swRegistration.scope);

                // Verificar actualizaciones
                this.swRegistration.addEventListener('updatefound', () => {
                    const newWorker = this.swRegistration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.error('[PWA] Error registrando Service Worker:', error);
            }
        },

        /**
         * Inicializa notificaciones push
         */
        initPushNotifications: async function() {
            if (!('PushManager' in window)) {
                console.log('[PWA] Push no soportado');
                return;
            }

            // Verificar permisos actuales
            const permission = Notification.permission;

            if (permission === 'granted') {
                this.subscribeToPush();
            } else if (permission === 'default') {
                // Mostrar botón para activar notificaciones
                this.showEnableNotificationsUI();
            }
        },

        /**
         * Solicita permiso para notificaciones
         */
        requestNotificationPermission: async function() {
            try {
                const permission = await Notification.requestPermission();

                if (permission === 'granted') {
                    this.subscribeToPush();
                    MiRedSocial.showToast('Notificaciones activadas', 'success');
                    this.hideEnableNotificationsUI();
                }

                return permission;
            } catch (error) {
                console.error('[PWA] Error solicitando permiso:', error);
                return 'denied';
            }
        },

        /**
         * Suscribe a notificaciones push
         */
        subscribeToPush: async function() {
            if (!this.swRegistration) return;

            try {
                // VAPID public key del servidor (debe configurarse)
                const vapidPublicKey = flavorMiRed?.vapidPublicKey || '';

                if (!vapidPublicKey) {
                    console.log('[PWA] VAPID key no configurada');
                    return;
                }

                this.pushSubscription = await this.swRegistration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                });

                // Enviar suscripción al servidor
                await this.sendSubscriptionToServer(this.pushSubscription);

                console.log('[PWA] Suscrito a notificaciones push');
            } catch (error) {
                console.error('[PWA] Error suscribiendo a push:', error);
            }
        },

        /**
         * Envía la suscripción al servidor
         */
        sendSubscriptionToServer: async function(subscription) {
            try {
                const response = await fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'mi_red_save_push_subscription',
                        nonce: config.nonce,
                        subscription: JSON.stringify(subscription)
                    })
                });

                return response.json();
            } catch (error) {
                console.error('[PWA] Error guardando suscripción:', error);
            }
        },

        /**
         * Muestra UI para activar notificaciones
         */
        showEnableNotificationsUI: function() {
            const $sidebar = $('.mi-red-sidebar');
            if (!$sidebar.length) return;

            const $banner = $(`
                <div class="mi-red-notifications-banner">
                    <div class="mi-red-notifications-banner__content">
                        <span class="mi-red-notifications-banner__icon">🔔</span>
                        <div class="mi-red-notifications-banner__text">
                            <strong>Activa las notificaciones</strong>
                            <p>Recibe alertas de nuevos mensajes y actividad</p>
                        </div>
                    </div>
                    <button class="mi-red-notifications-banner__btn" id="activar-notificaciones">
                        Activar
                    </button>
                    <button class="mi-red-notifications-banner__close">&times;</button>
                </div>
            `);

            $sidebar.prepend($banner);

            $('#activar-notificaciones').on('click', () => {
                this.requestNotificationPermission();
            });

            $banner.find('.mi-red-notifications-banner__close').on('click', () => {
                $banner.slideUp(() => $banner.remove());
            });
        },

        /**
         * Oculta UI de notificaciones
         */
        hideEnableNotificationsUI: function() {
            $('.mi-red-notifications-banner').slideUp(function() {
                $(this).remove();
            });
        },

        /**
         * Muestra notificación de actualización disponible
         */
        showUpdateNotification: function() {
            const $toast = $(`
                <div class="mi-red-update-toast">
                    <span>Nueva versión disponible</span>
                    <button id="actualizar-app">Actualizar</button>
                </div>
            `);

            $('body').append($toast);

            $('#actualizar-app').on('click', () => {
                window.location.reload();
            });
        },

        /**
         * Convierte VAPID key base64 a Uint8Array
         */
        urlBase64ToUint8Array: function(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }

            return outputArray;
        }
    };

    // Estilos para PWA
    const pwaStyles = `
        .mi-red-notifications-banner {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .mi-red-notifications-banner__content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        .mi-red-notifications-banner__icon {
            font-size: 24px;
        }
        .mi-red-notifications-banner__text p {
            margin: 0;
            font-size: 12px;
            opacity: 0.9;
        }
        .mi-red-notifications-banner__btn {
            background: white;
            color: #6366f1;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .mi-red-notifications-banner__close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
        }
        .mi-red-update-toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
        }
        .mi-red-update-toast button {
            background: #6366f1;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
    `;
    $('<style>').text(pwaStyles).appendTo('head');

    // Auto-inicializar si existe el feed
    $(document).ready(function() {
        if ($('.mi-red-feed').length) {
            MiRedSocial.initFeed();
            MiRedSocial.LazyLoad.init();
            MiRedSocial.VideoOptimizer.init();
            MiRedSocial.PWA.init();
        }
    });

})(jQuery);
