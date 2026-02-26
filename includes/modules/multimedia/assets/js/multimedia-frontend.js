/**
 * Multimedia Frontend JavaScript
 * Flavor Chat IA - Galería Comunitaria
 */

(function($) {
    'use strict';

    const FlavorMultimedia = {
        ajaxurl: typeof flavorMultimedia !== 'undefined' ? flavorMultimedia.ajaxurl : '/wp-admin/admin-ajax.php',
        resturl: typeof flavorMultimedia !== 'undefined' ? flavorMultimedia.resturl : '/wp-json/flavor/v1/multimedia/',
        nonce: typeof flavorMultimedia !== 'undefined' ? flavorMultimedia.nonce : '',
        user_id: typeof flavorMultimedia !== 'undefined' ? flavorMultimedia.user_id : 0,
        strings: typeof flavorMultimedia !== 'undefined' ? flavorMultimedia.strings : {},
        currentArchivos: [],
        currentIndex: 0,
    };

    /**
     * Inicialización
     */
    $(document).ready(function() {
        FlavorMultimedia.init();
    });

    FlavorMultimedia.init = function() {
        this.initGalerias();
        this.initAlbumes();
        this.initSubirForm();
        this.initMiGaleria();
        this.initCarousels();
        this.bindLightboxEvents();
    };

    // =========================================================================
    // Galería
    // =========================================================================

    FlavorMultimedia.initGalerias = function() {
        const self = this;

        $('.flavor-mm-galeria').each(function() {
            const container = $(this);
            const config = {
                tipo: container.data('tipo') || '',
                album: container.data('album') || 0,
                limite: container.data('limite') || 20,
                columnas: container.data('columnas') || 4,
                orden: container.data('orden') || 'recientes',
                pagina: 1,
            };

            container.data('config', config);
            self.loadGaleria(container);
        });

        // Filtros
        $(document).on('click', '.mm-filtro-btn', function() {
            const btn = $(this);
            const container = btn.closest('.flavor-mm-galeria');
            const config = container.data('config');

            btn.siblings().removeClass('active');
            btn.addClass('active');

            config.tipo = btn.data('tipo') || '';
            config.pagina = 1;
            container.data('config', config);

            self.loadGaleria(container);
        });

        // Orden
        $(document).on('change', '.mm-orden-select', function() {
            const select = $(this);
            const container = select.closest('.flavor-mm-galeria');
            const config = container.data('config');

            config.orden = select.val();
            config.pagina = 1;
            container.data('config', config);

            self.loadGaleria(container);
        });

        // Búsqueda
        let searchTimeout;
        $(document).on('input', '.mm-busqueda-input', function() {
            const input = $(this);
            const container = input.closest('.flavor-mm-galeria');
            const config = container.data('config');

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                config.busqueda = input.val();
                config.pagina = 1;
                container.data('config', config);
                self.loadGaleria(container);
            }, 300);
        });
    };

    FlavorMultimedia.loadGaleria = function(container) {
        const self = this;
        const config = container.data('config');
        const grid = container.find('.mm-grid');

        grid.html('<div class="mm-loading">' + (this.strings.loading || 'Cargando...') + '</div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_mm_galeria',
                tipo: config.tipo,
                album_id: config.album,
                limite: config.limite,
                pagina: config.pagina,
                orden: config.orden,
                busqueda: config.busqueda || '',
            },
            success: function(response) {
                if (response.success && response.data.archivos) {
                    self.currentArchivos = response.data.archivos;
                    self.renderGaleria(grid, response.data.archivos, config.columnas);
                    self.renderPaginacion(container, response.data);
                } else {
                    grid.html('<div class="mm-empty"><p>' + (self.strings.no_results || 'No se encontraron resultados') + '</p></div>');
                }
            },
            error: function() {
                grid.html('<div class="mm-empty"><p>' + (self.strings.error || 'Error al cargar') + '</p></div>');
            }
        });
    };

    FlavorMultimedia.renderGaleria = function(container, archivos, columnas) {
        if (!archivos.length) {
            container.html('<div class="mm-empty"><span class="dashicons dashicons-format-gallery"></span><h3>Sin contenido</h3><p>' + (this.strings.no_results || 'No hay archivos para mostrar') + '</p></div>');
            return;
        }

        let html = '';
        archivos.forEach(function(archivo, index) {
            html += `
                <div class="mm-item" data-id="${archivo.id}" data-index="${index}" data-tipo="${archivo.tipo}">
                    <img src="${archivo.thumbnail}" alt="${archivo.titulo || ''}" loading="lazy">
                    <div class="mm-item-overlay">
                        <span class="mm-item-titulo">${archivo.titulo || 'Sin título'}</span>
                        <div class="mm-item-meta">
                            <span><span class="dashicons dashicons-heart"></span> ${archivo.me_gusta}</span>
                            <span><span class="dashicons dashicons-visibility"></span> ${archivo.vistas}</span>
                        </div>
                    </div>
                    ${archivo.tipo !== 'imagen' ? '<span class="mm-item-tipo">' + archivo.tipo + '</span>' : ''}
                    ${archivo.destacado ? '<span class="mm-item-destacado">Destacado</span>' : ''}
                </div>
            `;
        });

        container.removeClass('mm-cols-3 mm-cols-4 mm-cols-5 mm-cols-6').addClass('mm-cols-' + columnas);
        container.html(html);
    };

    FlavorMultimedia.renderPaginacion = function(container, data) {
        const paginacion = container.find('.mm-paginacion');
        const config = container.data('config');
        const self = this;

        if (data.paginas <= 1) {
            paginacion.empty();
            return;
        }

        let html = '';

        // Anterior
        html += `<button class="mm-pag-prev" ${data.pagina_actual <= 1 ? 'disabled' : ''}>&laquo;</button>`;

        // Números
        for (let i = 1; i <= data.paginas; i++) {
            if (i === 1 || i === data.paginas || (i >= data.pagina_actual - 2 && i <= data.pagina_actual + 2)) {
                html += `<button class="mm-pag-num ${i === data.pagina_actual ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === data.pagina_actual - 3 || i === data.pagina_actual + 3) {
                html += '<span>...</span>';
            }
        }

        // Siguiente
        html += `<button class="mm-pag-next" ${data.pagina_actual >= data.paginas ? 'disabled' : ''}>&raquo;</button>`;

        paginacion.html(html);

        // Eventos
        paginacion.find('.mm-pag-prev').on('click', function() {
            if (config.pagina > 1) {
                config.pagina--;
                container.data('config', config);
                self.loadGaleria(container);
            }
        });

        paginacion.find('.mm-pag-next').on('click', function() {
            if (config.pagina < data.paginas) {
                config.pagina++;
                container.data('config', config);
                self.loadGaleria(container);
            }
        });

        paginacion.find('.mm-pag-num').on('click', function() {
            config.pagina = parseInt($(this).data('page'));
            container.data('config', config);
            self.loadGaleria(container);
        });
    };

    // =========================================================================
    // Lightbox
    // =========================================================================

    FlavorMultimedia.bindLightboxEvents = function() {
        const self = this;

        // Abrir lightbox
        $(document).on('click', '.mm-item', function() {
            const index = parseInt($(this).data('index'));
            self.openLightbox(index);
        });

        // Cerrar
        $(document).on('click', '.mm-lightbox-overlay, .mm-lightbox-close', function() {
            self.closeLightbox();
        });

        // Navegación
        $(document).on('click', '.mm-lightbox-prev', function() {
            self.prevLightbox();
        });

        $(document).on('click', '.mm-lightbox-next', function() {
            self.nextLightbox();
        });

        // Teclado
        $(document).on('keydown', function(e) {
            if (!$('.mm-lightbox').is(':visible')) return;

            if (e.key === 'Escape') self.closeLightbox();
            if (e.key === 'ArrowLeft') self.prevLightbox();
            if (e.key === 'ArrowRight') self.nextLightbox();
        });

        // Like
        $(document).on('click', '.mm-btn-like', function() {
            self.toggleLike();
        });

        // Descargar
        $(document).on('click', '.mm-btn-descargar', function() {
            self.downloadCurrent();
        });

        // Comentarios
        $(document).on('click', '.mm-btn-comentarios', function() {
            self.toggleComentarios();
        });

        // Enviar comentario
        $(document).on('submit', '.mm-form-comentario', function(e) {
            e.preventDefault();
            self.enviarComentario($(this));
        });
    };

    FlavorMultimedia.openLightbox = function(index) {
        const self = this;
        this.currentIndex = index;

        const archivo = this.currentArchivos[index];
        if (!archivo) return;

        const lightbox = $('.mm-lightbox');

        // Cargar detalle
        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_mm_detalle',
                archivo_id: archivo.id,
            },
            success: function(response) {
                if (response.success && response.data.archivo) {
                    self.renderLightboxContent(response.data.archivo);
                    lightbox.fadeIn(200);
                    $('body').css('overflow', 'hidden');
                }
            }
        });
    };

    FlavorMultimedia.renderLightboxContent = function(archivo) {
        const lightbox = $('.mm-lightbox');

        // Media
        let mediaHtml = '';
        if (archivo.tipo === 'imagen') {
            mediaHtml = `<img src="${archivo.url}" alt="${archivo.titulo || ''}">`;
        } else if (archivo.tipo === 'video') {
            mediaHtml = `<video src="${archivo.url}" controls autoplay></video>`;
        } else if (archivo.tipo === 'audio') {
            mediaHtml = `<audio src="${archivo.url}" controls autoplay></audio>`;
        }
        lightbox.find('.mm-lightbox-media').html(mediaHtml);

        // Info
        lightbox.find('.mm-lightbox-titulo').text(archivo.titulo || 'Sin título');
        lightbox.find('.mm-lightbox-descripcion').text(archivo.descripcion || '');
        lightbox.find('.mm-lightbox-autor').html(
            `<img src="${archivo.autor.avatar}" style="width: 24px; height: 24px; border-radius: 50%; vertical-align: middle; margin-right: 8px;">` +
            archivo.autor.nombre
        );
        lightbox.find('.mm-lightbox-fecha').text(archivo.fecha_humana);

        // Acciones
        lightbox.find('.mm-like-count').text(archivo.me_gusta);
        lightbox.find('.mm-comentarios-count').text(archivo.comentarios_count);
        lightbox.find('.mm-btn-like').toggleClass('liked', archivo.usuario_dio_like);

        // Guardar ID actual
        lightbox.data('archivo-id', archivo.id);
        lightbox.data('archivo', archivo);

        // Mostrar/ocultar botones según permisos
        if (!archivo.permite_descargas) {
            lightbox.find('.mm-btn-descargar').hide();
        } else {
            lightbox.find('.mm-btn-descargar').show();
        }
    };

    FlavorMultimedia.closeLightbox = function() {
        $('.mm-lightbox').fadeOut(200);
        $('body').css('overflow', '');
    };

    FlavorMultimedia.prevLightbox = function() {
        if (this.currentIndex > 0) {
            this.openLightbox(this.currentIndex - 1);
        }
    };

    FlavorMultimedia.nextLightbox = function() {
        if (this.currentIndex < this.currentArchivos.length - 1) {
            this.openLightbox(this.currentIndex + 1);
        }
    };

    FlavorMultimedia.toggleLike = function() {
        const self = this;
        const lightbox = $('.mm-lightbox');
        const archivoId = lightbox.data('archivo-id');
        const btn = lightbox.find('.mm-btn-like');

        if (!this.user_id) {
            this.showToast('Debes iniciar sesión', 'warning');
            return;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_me_gusta',
                nonce: this.nonce,
                archivo_id: archivoId,
            },
            success: function(response) {
                if (response.success) {
                    btn.find('.mm-like-count').text(response.data.me_gusta);
                    btn.toggleClass('liked', response.data.liked);

                    // Actualizar en grid
                    const item = $(`.mm-item[data-id="${archivoId}"]`);
                    item.find('.mm-item-meta span:first-child').html(
                        `<span class="dashicons dashicons-heart"></span> ${response.data.me_gusta}`
                    );
                }
            }
        });
    };

    FlavorMultimedia.downloadCurrent = function() {
        const archivo = $('.mm-lightbox').data('archivo');
        if (!archivo || !archivo.permite_descargas) return;

        const link = document.createElement('a');
        link.href = archivo.url;
        link.download = archivo.titulo || 'descarga';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    FlavorMultimedia.toggleComentarios = function() {
        const lightbox = $('.mm-lightbox');
        let comentariosSection = lightbox.find('.mm-lightbox-comentarios');

        if (comentariosSection.length) {
            comentariosSection.toggle();
            return;
        }

        // Crear sección de comentarios
        const html = `
            <div class="mm-lightbox-comentarios">
                <div class="mm-comentarios-lista"></div>
                ${this.user_id ? `
                <form class="mm-form-comentario">
                    <input type="text" name="comentario" placeholder="Escribe un comentario...">
                    <button type="submit"><span class="dashicons dashicons-arrow-right-alt"></span></button>
                </form>
                ` : ''}
            </div>
        `;

        lightbox.find('.mm-lightbox-info').append(html);
        this.loadComentarios();
    };

    FlavorMultimedia.loadComentarios = function() {
        const self = this;
        const archivoId = $('.mm-lightbox').data('archivo-id');
        const lista = $('.mm-comentarios-lista');

        $.ajax({
            url: this.resturl + 'archivo/' + archivoId + '/comentarios',
            type: 'GET',
            success: function(response) {
                if (response.success && response.comentarios.length) {
                    let html = '';
                    response.comentarios.forEach(function(c) {
                        html += `
                            <div class="mm-comentario">
                                <img src="${c.autor.avatar}" class="mm-comentario-avatar">
                                <div class="mm-comentario-content">
                                    <span class="mm-comentario-autor">${c.autor.nombre}</span>
                                    <p class="mm-comentario-texto">${c.comentario}</p>
                                    <span class="mm-comentario-fecha">${c.fecha_humana}</span>
                                </div>
                            </div>
                        `;
                    });
                    lista.html(html);
                } else {
                    lista.html('<p style="color: #9ca3af; text-align: center; padding: 1rem;">Sin comentarios</p>');
                }
            }
        });
    };

    FlavorMultimedia.enviarComentario = function(form) {
        const self = this;
        const archivoId = $('.mm-lightbox').data('archivo-id');
        const input = form.find('input[name="comentario"]');
        const comentario = input.val().trim();

        if (!comentario) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_comentar',
                nonce: this.nonce,
                archivo_id: archivoId,
                comentario: comentario,
            },
            success: function(response) {
                if (response.success) {
                    input.val('');
                    self.loadComentarios();

                    // Actualizar contador
                    const count = parseInt($('.mm-comentarios-count').text()) + 1;
                    $('.mm-comentarios-count').text(count);
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            }
        });
    };

    // =========================================================================
    // Álbumes
    // =========================================================================

    FlavorMultimedia.initAlbumes = function() {
        const self = this;

        $('.flavor-mm-albumes').each(function() {
            const container = $(this);
            self.loadAlbumes(container);
        });

        // Click en álbum
        $(document).on('click', '.mm-album-card', function() {
            const albumId = $(this).data('id');
            // Redirigir o cargar galería del álbum
            const galeria = $('.flavor-mm-galeria');
            if (galeria.length) {
                const config = galeria.data('config');
                config.album = albumId;
                config.pagina = 1;
                galeria.data('config', config);
                self.loadGaleria(galeria);

                // Scroll a galería
                $('html, body').animate({
                    scrollTop: galeria.offset().top - 50
                }, 300);
            }
        });
    };

    FlavorMultimedia.loadAlbumes = function(container) {
        const self = this;
        const grid = container.find('.mm-albumes-grid');
        const limite = container.data('limite') || 12;
        const columnas = container.data('columnas') || 3;
        const usuario = container.data('usuario') || 0;

        $.ajax({
            url: this.resturl + 'albumes',
            type: 'GET',
            data: {
                limite: limite,
                usuario_id: usuario,
            },
            success: function(response) {
                if (response.success && response.albumes.length) {
                    self.renderAlbumes(grid, response.albumes, columnas);
                } else {
                    grid.html('<div class="mm-empty"><p>No hay álbumes</p></div>');
                }
            }
        });
    };

    FlavorMultimedia.renderAlbumes = function(container, albumes, columnas) {
        let html = '';
        albumes.forEach(function(album) {
            html += `
                <div class="mm-album-card" data-id="${album.id}">
                    <div class="mm-album-portada">
                        ${album.portada
                            ? `<img src="${album.portada}" alt="${album.nombre}">`
                            : '<div class="mm-album-portada-empty"><span class="dashicons dashicons-images-alt2"></span></div>'
                        }
                        <span class="mm-album-count">${album.archivos_count} archivos</span>
                    </div>
                    <div class="mm-album-info">
                        <h4 class="mm-album-nombre">${album.nombre}</h4>
                        <p class="mm-album-autor">${album.autor.nombre}</p>
                    </div>
                </div>
            `;
        });

        container.removeClass('mm-cols-2 mm-cols-3 mm-cols-4').addClass('mm-cols-' + columnas);
        container.html(html);
    };

    // =========================================================================
    // Formulario de subida
    // =========================================================================

    FlavorMultimedia.initSubirForm = function() {
        const self = this;
        const dropzone = $('#mm-dropzone');
        const fileInput = $('#mm-archivo-input');
        const form = $('#mm-form-subir');

        if (!dropzone.length) return;

        // Click para seleccionar
        dropzone.on('click', function(e) {
            // Evitar recursión si el click viene del fileInput
            if ($(e.target).is(fileInput)) {
                return;
            }
            if (!$(this).find('.mm-preview').is(':visible')) {
                fileInput.trigger('click');
            }
        });

        // Drag & drop
        dropzone.on('dragover dragenter', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        dropzone.on('dragleave drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        dropzone.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                self.handleFileSelect(files[0]);
            }
        });

        // Selección de archivo
        fileInput.on('change', function() {
            if (this.files.length) {
                self.handleFileSelect(this.files[0]);
            }
        });

        // Cancelar
        $(document).on('click', '.mm-btn-cancelar, .mm-preview-remove', function() {
            self.resetSubirForm();
        });

        // Cargar álbumes del usuario
        this.loadUserAlbums();

        // Submit
        form.on('submit', function(e) {
            e.preventDefault();
            self.submitUpload();
        });

        // Crear álbum
        $(document).on('click', '.mm-btn-nuevo-album', function() {
            self.showCrearAlbumModal();
        });
    };

    FlavorMultimedia.handleFileSelect = function(file) {
        const dropzone = $('#mm-dropzone');
        const preview = dropzone.find('.mm-preview');
        const content = dropzone.find('.mm-dropzone-content');

        // Validar tipo
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'video/quicktime', 'audio/mpeg', 'audio/wav', 'audio/ogg'];
        if (!validTypes.some(type => file.type.startsWith(type.split('/')[0]))) {
            this.showToast('Tipo de archivo no permitido', 'error');
            return;
        }

        // Preview
        content.hide();
        preview.show();

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.html(`
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="mm-preview-remove">&times;</button>
                `);
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            preview.html(`
                <video src="${URL.createObjectURL(file)}" controls style="max-height: 300px;"></video>
                <button type="button" class="mm-preview-remove">&times;</button>
            `);
        } else {
            preview.html(`
                <div style="padding: 2rem; text-align: center;">
                    <span class="dashicons dashicons-media-audio" style="font-size: 48px; color: #9ca3af;"></span>
                    <p>${file.name}</p>
                </div>
                <button type="button" class="mm-preview-remove">&times;</button>
            `);
        }

        // Mostrar campos
        $('.mm-form-campos').slideDown();

        // Autocompletar título
        const titulo = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
        $('#mm-titulo').val(titulo);
    };

    FlavorMultimedia.resetSubirForm = function() {
        const dropzone = $('#mm-dropzone');
        dropzone.find('.mm-preview').hide().empty();
        dropzone.find('.mm-dropzone-content').show();
        $('.mm-form-campos').slideUp();
        $('#mm-form-subir')[0].reset();
        $('.mm-progress').hide();
    };

    FlavorMultimedia.loadUserAlbums = function() {
        const self = this;
        const select = $('#mm-album');

        if (!select.length || !this.user_id) return;

        $.ajax({
            url: this.resturl + 'mis-albumes',
            type: 'GET',
            beforeSend: function(xhr) {
                var nonce = FlavorMultimedia.nonce || (typeof wpApiSettings !== 'undefined' ? wpApiSettings.nonce : '');
                if (nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', nonce);
                }
            },
            success: function(response) {
                if (response.success && response.albumes) {
                    response.albumes.forEach(function(album) {
                        select.append(`<option value="${album.id}">${album.nombre}</option>`);
                    });
                }
            }
        });
    };

    FlavorMultimedia.submitUpload = function() {
        const self = this;
        const form = $('#mm-form-subir');
        const fileInput = $('#mm-archivo-input')[0];

        if (!fileInput.files.length) {
            this.showToast('Selecciona un archivo', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'flavor_mm_subir');
        formData.append('nonce', this.nonce);
        formData.append('archivo', fileInput.files[0]);
        formData.append('titulo', $('#mm-titulo').val());
        formData.append('descripcion', $('#mm-descripcion').val());
        formData.append('album_id', $('#mm-album').val() || 0);
        formData.append('privacidad', $('#mm-privacidad').val());

        // Tags
        const tags = $('#mm-tags').val().split(',').map(t => t.trim()).filter(t => t);
        tags.forEach(function(tag, i) {
            formData.append('tags[' + i + ']', tag);
        });

        const submitBtn = form.find('.mm-btn-subir');
        const progress = $('.mm-progress');

        submitBtn.prop('disabled', true);
        progress.show();

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progress.find('.mm-progress-bar').css('width', percent + '%');
                        progress.find('.mm-progress-text').text(percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                submitBtn.prop('disabled', false);

                if (response.success) {
                    self.showToast(response.data.mensaje || self.strings.upload_success || 'Subido correctamente', 'success');
                    self.resetSubirForm();

                    // Recargar galería si existe
                    const galeria = $('.flavor-mm-galeria');
                    if (galeria.length) {
                        self.loadGaleria(galeria);
                    }
                } else {
                    self.showToast(response.data || self.strings.upload_error || 'Error', 'error');
                }
            },
            error: function() {
                submitBtn.prop('disabled', false);
                self.showToast(self.strings.upload_error || 'Error al subir', 'error');
            }
        });
    };

    FlavorMultimedia.showCrearAlbumModal = function() {
        const self = this;

        const html = `
            <div class="mm-modal" id="mm-modal-album">
                <div class="mm-modal-overlay"></div>
                <div class="mm-modal-content">
                    <div class="mm-modal-header">
                        <h3>Crear álbum</h3>
                        <button class="mm-modal-close">&times;</button>
                    </div>
                    <form id="mm-form-album">
                        <div class="mm-campo">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required>
                        </div>
                        <div class="mm-campo">
                            <label>Descripción</label>
                            <textarea name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mm-campo">
                            <label>Privacidad</label>
                            <select name="privacidad">
                                <option value="comunidad">Comunidad</option>
                                <option value="publico">Público</option>
                                <option value="privado">Privado</option>
                            </select>
                        </div>
                        <div class="mm-acciones">
                            <button type="submit" class="btn btn-primary">Crear</button>
                            <button type="button" class="btn btn-outline mm-modal-close">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        $('body').append(html);

        // Cerrar modal
        $(document).on('click', '#mm-modal-album .mm-modal-overlay, #mm-modal-album .mm-modal-close', function() {
            $('#mm-modal-album').remove();
        });

        // Submit
        $('#mm-form-album').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: self.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_mm_crear_album',
                    nonce: self.nonce,
                    nombre: form.find('[name="nombre"]').val(),
                    descripcion: form.find('[name="descripcion"]').val(),
                    privacidad: form.find('[name="privacidad"]').val(),
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('Álbum creado', 'success');
                        $('#mm-album').append(`<option value="${response.data.album_id}" selected>${form.find('[name="nombre"]').val()}</option>`);
                        $('#mm-modal-album').remove();
                    } else {
                        self.showToast(response.data || 'Error', 'error');
                    }
                }
            });
        });
    };

    // =========================================================================
    // Mi Galería
    // =========================================================================

    FlavorMultimedia.initMiGaleria = function() {
        const self = this;
        const container = $('.flavor-mm-mi-galeria');

        if (!container.length) return;

        // Tabs
        container.on('click', '.mm-tab', function() {
            const tab = $(this).data('tab');
            container.find('.mm-tab').removeClass('active');
            $(this).addClass('active');
            container.find('.mm-tab-content').removeClass('active');
            container.find(`.mm-tab-content[data-tab="${tab}"]`).addClass('active');

            if (tab === 'archivos') {
                self.loadMisArchivos(container);
            } else {
                self.loadMisAlbumes(container);
            }
        });

        // Filtros
        container.on('change', '.mm-filtro-tipo, .mm-filtro-album', function() {
            self.loadMisArchivos(container);
        });

        // Cargar álbumes para filtro
        this.loadAlbumesParaFiltro(container);

        // Cargar inicial
        this.loadMisArchivos(container);

        // Acciones de archivo
        container.on('click', '.mm-btn-editar-archivo', function(e) {
            e.stopPropagation();
            const id = $(this).closest('.mm-item').data('id');
            self.showEditarArchivoModal(id);
        });

        container.on('click', '.mm-btn-eliminar-archivo', function(e) {
            e.stopPropagation();
            const id = $(this).closest('.mm-item').data('id');
            if (confirm(self.strings.confirm_delete || '¿Eliminar este archivo?')) {
                self.eliminarArchivo(id, container);
            }
        });

        // Crear álbum
        container.on('click', '.mm-btn-crear-album', function() {
            self.showCrearAlbumModal();
        });
    };

    FlavorMultimedia.loadMisArchivos = function(container) {
        const self = this;
        const grid = container.find('.mm-mis-archivos-grid');
        const tipo = container.find('.mm-filtro-tipo').val() || '';
        const albumId = container.find('.mm-filtro-album').val() || 0;

        grid.html('<div class="mm-loading">Cargando...</div>');

        $.ajax({
            url: this.resturl + 'mis-archivos',
            type: 'GET',
            data: {
                tipo: tipo,
                album_id: albumId,
                limite: 20,
            },
            beforeSend: function(xhr) {
                var nonce = FlavorMultimedia.nonce || (typeof wpApiSettings !== 'undefined' ? wpApiSettings.nonce : '');
                if (nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', nonce);
                }
            },
            success: function(response) {
                if (response.success && response.archivos.length) {
                    self.currentArchivos = response.archivos;
                    let html = '';
                    response.archivos.forEach(function(archivo, index) {
                        html += `
                            <div class="mm-item" data-id="${archivo.id}" data-index="${index}" data-tipo="${archivo.tipo}">
                                <img src="${archivo.thumbnail}" alt="${archivo.titulo || ''}">
                                <div class="mm-item-overlay">
                                    <span class="mm-item-titulo">${archivo.titulo || 'Sin título'}</span>
                                    <div class="mm-item-meta">
                                        <span><span class="dashicons dashicons-heart"></span> ${archivo.me_gusta}</span>
                                        <span><span class="dashicons dashicons-visibility"></span> ${archivo.vistas}</span>
                                    </div>
                                </div>
                                <div class="mm-item-acciones">
                                    <button class="mm-btn-editar-archivo" title="Editar"><span class="dashicons dashicons-edit"></span></button>
                                    <button class="mm-btn-eliminar-archivo" title="Eliminar"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                                ${archivo.estado === 'pendiente' ? '<span class="mm-item-tipo">Pendiente</span>' : ''}
                            </div>
                        `;
                    });
                    grid.html(html);
                } else {
                    grid.html('<div class="mm-empty"><span class="dashicons dashicons-format-gallery"></span><h3>Sin archivos</h3><p>No tienes archivos subidos</p></div>');
                }
            }
        });
    };

    FlavorMultimedia.loadMisAlbumes = function(container) {
        const self = this;
        const grid = container.find('.mm-mis-albumes-grid');

        grid.html('<div class="mm-loading">Cargando...</div>');

        $.ajax({
            url: this.resturl + 'mis-albumes',
            type: 'GET',
            beforeSend: function(xhr) {
                var nonce = FlavorMultimedia.nonce || (typeof wpApiSettings !== 'undefined' ? wpApiSettings.nonce : '');
                if (nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', nonce);
                }
            },
            success: function(response) {
                if (response.success && response.albumes.length) {
                    self.renderAlbumes(grid, response.albumes, 3);
                } else {
                    grid.html('<div class="mm-empty"><span class="dashicons dashicons-images-alt2"></span><h3>Sin álbumes</h3><p>Crea tu primer álbum</p></div>');
                }
            }
        });
    };

    FlavorMultimedia.loadAlbumesParaFiltro = function(container) {
        const select = container.find('.mm-filtro-album');

        $.ajax({
            url: this.resturl + 'mis-albumes',
            type: 'GET',
            beforeSend: function(xhr) {
                var nonce = FlavorMultimedia.nonce || (typeof wpApiSettings !== 'undefined' ? wpApiSettings.nonce : '');
                if (nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', nonce);
                }
            },
            success: function(response) {
                if (response.success && response.albumes) {
                    response.albumes.forEach(function(album) {
                        select.append(`<option value="${album.id}">${album.nombre}</option>`);
                    });
                }
            }
        });
    };

    FlavorMultimedia.eliminarArchivo = function(id, container) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_eliminar',
                nonce: this.nonce,
                archivo_id: id,
            },
            success: function(response) {
                if (response.success) {
                    self.showToast('Archivo eliminado', 'success');
                    self.loadMisArchivos(container);
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            }
        });
    };

    FlavorMultimedia.showEditarArchivoModal = function(id) {
        // Implementación similar a crear álbum modal
        // Por brevedad, aquí solo mostramos la estructura
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_mm_detalle',
                archivo_id: id,
            },
            success: function(response) {
                if (response.success) {
                    const archivo = response.data.archivo;
                    // Mostrar modal con los datos
                    self.showToast('Función de edición en desarrollo', 'info');
                }
            }
        });
    };

    // =========================================================================
    // Carousel
    // =========================================================================

    FlavorMultimedia.initCarousels = function() {
        const self = this;

        $('.flavor-mm-carousel').each(function() {
            const carousel = $(this);
            self.loadCarousel(carousel);
        });
    };

    FlavorMultimedia.loadCarousel = function(carousel) {
        const self = this;
        const slides = carousel.find('.mm-carousel-slides');
        const limite = carousel.data('limite') || 10;
        const album = carousel.data('album') || 0;
        const destacados = carousel.data('destacados') === 'true';
        const autoplay = carousel.data('autoplay') === 'true';
        const intervalo = (carousel.data('intervalo') || 5) * 1000;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_mm_galeria',
                tipo: 'imagen',
                album_id: album,
                limite: limite,
                destacados: destacados,
            },
            success: function(response) {
                if (response.success && response.data.archivos.length) {
                    self.renderCarousel(carousel, response.data.archivos, autoplay, intervalo);
                }
            }
        });
    };

    FlavorMultimedia.renderCarousel = function(carousel, archivos, autoplay, intervalo) {
        const slides = carousel.find('.mm-carousel-slides');
        const dots = carousel.find('.mm-carousel-dots');
        let currentSlide = 0;

        // Render slides
        let slidesHtml = '';
        let dotsHtml = '';
        archivos.forEach(function(archivo, index) {
            slidesHtml += `
                <div class="mm-carousel-slide">
                    <img src="${archivo.url}" alt="${archivo.titulo || ''}">
                    <div class="mm-carousel-slide-info">
                        <h3 class="mm-carousel-slide-titulo">${archivo.titulo || ''}</h3>
                    </div>
                </div>
            `;
            dotsHtml += `<span class="mm-carousel-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></span>`;
        });

        slides.html(slidesHtml);
        dots.html(dotsHtml);

        const totalSlides = archivos.length;

        function goToSlide(index) {
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            currentSlide = index;
            slides.css('transform', `translateX(-${index * 100}%)`);
            dots.find('.mm-carousel-dot').removeClass('active');
            dots.find(`.mm-carousel-dot[data-index="${index}"]`).addClass('active');
        }

        // Navigation
        carousel.find('.mm-carousel-prev').on('click', function() {
            goToSlide(currentSlide - 1);
        });

        carousel.find('.mm-carousel-next').on('click', function() {
            goToSlide(currentSlide + 1);
        });

        dots.on('click', '.mm-carousel-dot', function() {
            goToSlide(parseInt($(this).data('index')));
        });

        // Autoplay
        if (autoplay && totalSlides > 1) {
            setInterval(function() {
                goToSlide(currentSlide + 1);
            }, intervalo);
        }
    };

    // =========================================================================
    // Utilidades
    // =========================================================================

    FlavorMultimedia.showToast = function(message, type) {
        type = type || 'info';

        if (!$('.mm-toast-container').length) {
            $('body').append('<div class="mm-toast-container"></div>');
        }

        const toast = $(`<div class="mm-toast ${type}">${message}</div>`);
        $('.mm-toast-container').append(toast);

        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    };

    // Exponer globalmente
    window.FlavorMultimedia = FlavorMultimedia;

})(jQuery);
