/**
 * JavaScript de Búsqueda Avanzada Social
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

(function($) {
    'use strict';

    const FlavorSocialSearch = {
        config: null,
        debounceTimer: null,
        currentRequest: null,
        currentTab: 'todos',
        resultados: {},
        offset: 0,

        /**
         * Inicializar
         */
        init: function() {
            this.config = window.flavorSocialSearchConfig || {};
            this.bindEventos();
        },

        /**
         * Bind de eventos
         */
        bindEventos: function() {
            const self = this;

            // Input de búsqueda
            $(document).on('input', '.fss-input', function() {
                const $container = $(this).closest('.flavor-social-search');
                const termino = $(this).val().trim();

                self.toggleClearButton($container, termino.length > 0);

                if (termino.length >= 2) {
                    clearTimeout(self.debounceTimer);
                    self.debounceTimer = setTimeout(function() {
                        self.mostrarSugerencias($container, termino);
                    }, 300);
                } else {
                    self.ocultarSugerencias($container);
                }
            });

            // Enter para buscar
            $(document).on('keydown', '.fss-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const $container = $(this).closest('.flavor-social-search');
                    const termino = $(this).val().trim();

                    if (termino.length >= 2) {
                        self.ocultarSugerencias($container);
                        self.buscar($container, termino);
                    }
                }

                // Escape para cerrar
                if (e.key === 'Escape') {
                    const $container = $(this).closest('.flavor-social-search');
                    self.ocultarSugerencias($container);
                }
            });

            // Limpiar input
            $(document).on('click', '.fss-icon-clear', function() {
                const $container = $(this).closest('.flavor-social-search');
                $container.find('.fss-input').val('').focus();
                self.toggleClearButton($container, false);
                self.ocultarSugerencias($container);
                self.ocultarResultados($container);
            });

            // Toggle filtros
            $(document).on('click', '.fss-btn-filtros', function() {
                const $container = $(this).closest('.flavor-social-search');
                const $filtros = $container.find('.fss-filters');
                $(this).toggleClass('active');
                $filtros.slideToggle(200);
            });

            // Cerrar filtros
            $(document).on('click', '.fss-btn-close-filters', function() {
                const $container = $(this).closest('.flavor-social-search');
                $container.find('.fss-filters').slideUp(200);
                $container.find('.fss-btn-filtros').removeClass('active');
            });

            // Aplicar filtros
            $(document).on('click', '.fss-btn-apply', function() {
                const $container = $(this).closest('.flavor-social-search');
                const termino = $container.find('.fss-input').val().trim();

                if (termino.length >= 2) {
                    $container.find('.fss-filters').slideUp(200);
                    $container.find('.fss-btn-filtros').removeClass('active');
                    self.buscar($container, termino);
                }
            });

            // Reset filtros
            $(document).on('click', '.fss-btn-reset', function() {
                const $container = $(this).closest('.flavor-social-search');
                $container.find('.fss-filters input[type="checkbox"]').prop('checked', true);
                $container.find('.fss-filters select').val('');
                $container.find('.fss-filters input[type="text"]').val('');
            });

            // Click en sugerencia
            $(document).on('click', '.fss-suggestion-item', function() {
                const $container = $(this).closest('.flavor-social-search');
                const texto = $(this).find('.fss-suggestion-text').text();
                $container.find('.fss-input').val(texto);
                self.ocultarSugerencias($container);
                self.buscar($container, texto);
            });

            // Tabs de resultados
            $(document).on('click', '.fss-tab', function() {
                const $container = $(this).closest('.flavor-social-search');
                const tipo = $(this).data('tipo');

                $container.find('.fss-tab').removeClass('active');
                $(this).addClass('active');

                self.currentTab = tipo;
                self.mostrarResultadosFiltrados($container);
            });

            // Cargar más
            $(document).on('click', '.fss-btn-load-more', function() {
                const $container = $(this).closest('.flavor-social-search');
                const termino = $container.find('.fss-input').val().trim();
                self.offset += parseInt($container.data('limite') || 10);
                self.buscar($container, termino, true);
            });

            // Click fuera cierra sugerencias
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.flavor-social-search').length) {
                    $('.fss-suggestions').hide();
                }
            });
        },

        /**
         * Toggle botón limpiar
         */
        toggleClearButton: function($container, mostrar) {
            $container.find('.fss-icon-clear').toggle(mostrar);
        },

        /**
         * Mostrar/ocultar loading
         */
        toggleLoading: function($container, mostrar) {
            $container.find('.fss-icon-loading').toggle(mostrar);
            $container.find('.fss-icon-search').toggle(!mostrar);
        },

        /**
         * Mostrar sugerencias
         */
        mostrarSugerencias: function($container, termino) {
            const self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_social_search_suggestions',
                    nonce: this.config.nonce,
                    termino: termino
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.renderSugerencias($container, response.data);
                    } else {
                        self.ocultarSugerencias($container);
                    }
                }
            });
        },

        /**
         * Renderizar sugerencias
         */
        renderSugerencias: function($container, sugerencias) {
            const $wrapper = $container.find('.fss-suggestions');
            let html = '';

            sugerencias.forEach(function(sug) {
                const avatar = sug.avatar
                    ? '<img src="' + sug.avatar + '" alt="">'
                    : '<span class="dashicons ' + sug.icono + '"></span>';

                html += '<div class="fss-suggestion-item" data-tipo="' + sug.tipo + '">';
                html += '  <div class="fss-suggestion-icon">' + avatar + '</div>';
                html += '  <div class="fss-suggestion-content">';
                html += '    <div class="fss-suggestion-text">' + this.escapeHtml(sug.texto) + '</div>';
                if (sug.subtexto) {
                    html += '    <div class="fss-suggestion-subtext">' + this.escapeHtml(sug.subtexto) + '</div>';
                }
                html += '  </div>';
                html += '</div>';
            }.bind(this));

            $wrapper.html(html).show();
        },

        /**
         * Ocultar sugerencias
         */
        ocultarSugerencias: function($container) {
            $container.find('.fss-suggestions').hide().empty();
        },

        /**
         * Buscar
         */
        buscar: function($container, termino, append) {
            const self = this;

            if (!append) {
                this.offset = 0;
                this.resultados = {};
            }

            // Cancelar request anterior
            if (this.currentRequest) {
                this.currentRequest.abort();
            }

            // Obtener filtros
            const filtros = this.obtenerFiltros($container);

            this.toggleLoading($container, true);

            this.currentRequest = $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_social_search',
                    nonce: this.config.nonce,
                    termino: termino,
                    tipos: filtros.tipos,
                    fecha: filtros.fecha,
                    hashtag: filtros.hashtag,
                    ubicacion: filtros.ubicacion,
                    verificados: filtros.verificados,
                    ordenar: filtros.ordenar,
                    limite: $container.data('limite') || 10,
                    offset: this.offset
                },
                success: function(response) {
                    self.toggleLoading($container, false);

                    if (response.success) {
                        if (append) {
                            self.appendResultados(response.data.resultados);
                        } else {
                            self.resultados = response.data.resultados;
                        }
                        self.renderResultados($container, response.data);
                    } else {
                        self.mostrarError($container, response.data || self.config.strings.error);
                    }
                },
                error: function(xhr, status) {
                    if (status !== 'abort') {
                        self.toggleLoading($container, false);
                        self.mostrarError($container, self.config.strings.error);
                    }
                }
            });
        },

        /**
         * Obtener filtros del formulario
         */
        obtenerFiltros: function($container) {
            const $filters = $container.find('.fss-filters');

            const tipos = [];
            $filters.find('input[name="tipos[]"]:checked').each(function() {
                tipos.push($(this).val());
            });

            // Si no hay filtros visibles, usar todos los tipos del data attr
            if (tipos.length === 0) {
                const tiposData = $container.find('.fss-input').data('tipos');
                if (tiposData) {
                    tipos.push(...tiposData.split(',').map(t => t.trim()));
                }
            }

            return {
                tipos: tipos,
                fecha: $filters.find('select[name="fecha"]').val() || '',
                hashtag: $filters.find('input[name="hashtag"]').val() || '',
                ubicacion: $filters.find('input[name="ubicacion"]').val() || '',
                verificados: $filters.find('input[name="verificados"]').is(':checked'),
                ordenar: $filters.find('select[name="ordenar"]').val() || 'relevancia'
            };
        },

        /**
         * Append resultados
         */
        appendResultados: function(nuevos) {
            for (const tipo in nuevos) {
                if (this.resultados[tipo]) {
                    this.resultados[tipo].items = this.resultados[tipo].items.concat(nuevos[tipo].items);
                    this.resultados[tipo].total = nuevos[tipo].total;
                } else {
                    this.resultados[tipo] = nuevos[tipo];
                }
            }
        },

        /**
         * Renderizar resultados
         */
        renderResultados: function($container, data) {
            const $results = $container.find('.fss-results');
            const $empty = $container.find('.fss-empty');

            if (data.total === 0) {
                $results.hide();
                $empty.show();
                return;
            }

            $empty.hide();

            // Contador
            const textoResultados = data.total === 1
                ? this.config.strings.resultado
                : this.config.strings.resultados;
            $results.find('.fss-results-count').text(data.total + ' ' + textoResultados);

            // Tabs
            this.renderTabs($container, data.resultados);

            // Items
            this.mostrarResultadosFiltrados($container);

            // Mostrar botón cargar más
            const itemsMostrados = this.contarItemsMostrados();
            $results.find('.fss-results-footer').toggle(itemsMostrados < data.total);

            $results.show();
        },

        /**
         * Renderizar tabs
         */
        renderTabs: function($container, resultados) {
            const $tabs = $container.find('.fss-results-tabs');
            let html = '';

            // Tab "Todos"
            let totalItems = 0;
            for (const tipo in resultados) {
                totalItems += resultados[tipo].items.length;
            }

            html += '<button class="fss-tab' + (this.currentTab === 'todos' ? ' active' : '') + '" data-tipo="todos">';
            html += 'Todos <span class="fss-tab-count">' + totalItems + '</span>';
            html += '</button>';

            // Tabs por tipo
            for (const tipo in resultados) {
                const activo = this.currentTab === tipo ? ' active' : '';
                html += '<button class="fss-tab' + activo + '" data-tipo="' + tipo + '">';
                html += resultados[tipo].etiqueta;
                html += ' <span class="fss-tab-count">' + resultados[tipo].items.length + '</span>';
                html += '</button>';
            }

            $tabs.html(html);
        },

        /**
         * Mostrar resultados filtrados por tab
         */
        mostrarResultadosFiltrados: function($container) {
            const $body = $container.find('.fss-results-body');
            let html = '';

            for (const tipo in this.resultados) {
                if (this.currentTab !== 'todos' && this.currentTab !== tipo) {
                    continue;
                }

                const items = this.resultados[tipo].items;
                items.forEach(function(item) {
                    html += this.renderItem(item);
                }.bind(this));
            }

            $body.html(html);
        },

        /**
         * Renderizar un item
         */
        renderItem: function(item) {
            let html = '';

            switch (item.tipo) {
                case 'publicacion':
                    html = this.renderPublicacion(item);
                    break;
                case 'usuario':
                    html = this.renderUsuario(item);
                    break;
                case 'hashtag':
                    html = this.renderHashtag(item);
                    break;
                case 'comunidad':
                    html = this.renderComunidad(item);
                    break;
            }

            return html;
        },

        /**
         * Renderizar publicación
         */
        renderPublicacion: function(item) {
            return '<div class="fss-result-item fss-result-item--publicacion">' +
                '<img src="' + item.autor.avatar + '" class="fss-result-avatar" alt="">' +
                '<div class="fss-result-content">' +
                '  <div class="fss-result-title">' +
                '    <a href="' + item.url + '">' + this.escapeHtml(item.autor.nombre) + '</a>' +
                '  </div>' +
                '  <div class="fss-result-text">' + this.escapeHtml(item.contenido) + '</div>' +
                '  <div class="fss-result-meta">' +
                '    <span><span class="dashicons dashicons-heart"></span> ' + item.likes + '</span>' +
                '    <span><span class="dashicons dashicons-admin-comments"></span> ' + item.comentarios + '</span>' +
                '    <span><span class="dashicons dashicons-clock"></span> ' + item.fecha + '</span>' +
                '  </div>' +
                '</div>' +
                '</div>';
        },

        /**
         * Renderizar usuario
         */
        renderUsuario: function(item) {
            const verificado = item.verificado
                ? '<span class="dashicons dashicons-yes-alt fss-result-verified"></span>'
                : '';

            return '<div class="fss-result-item fss-result-item--usuario">' +
                '<img src="' + item.avatar + '" class="fss-result-avatar" alt="">' +
                '<div class="fss-result-content">' +
                '  <div class="fss-result-title">' +
                '    <a href="' + item.url + '">' + this.escapeHtml(item.nombre) + '</a>' +
                verificado +
                '  </div>' +
                '  <div class="fss-result-username">' + this.escapeHtml(item.username) + '</div>' +
                (item.bio ? '  <div class="fss-result-text">' + this.escapeHtml(item.bio) + '</div>' : '') +
                '  <div class="fss-result-meta">' +
                '    <span>' + item.seguidores + ' ' + this.config.strings.seguidores + '</span>' +
                '    <span>' + item.publicaciones + ' ' + this.config.strings.publicaciones + '</span>' +
                '  </div>' +
                '</div>' +
                '</div>';
        },

        /**
         * Renderizar hashtag
         */
        renderHashtag: function(item) {
            const trending = item.trending
                ? '<span class="fss-trending-badge"><span class="dashicons dashicons-chart-line"></span> Trending</span>'
                : '';

            return '<div class="fss-result-item fss-result-item--hashtag">' +
                '<div class="fss-result-icon"><span class="dashicons dashicons-tag"></span></div>' +
                '<div class="fss-result-content">' +
                '  <div class="fss-result-title">' +
                '    <a href="' + item.url + '">' + this.escapeHtml(item.nombre) + '</a>' +
                trending +
                '  </div>' +
                '  <div class="fss-result-meta">' +
                '    <span>' + item.usos + ' ' + this.config.strings.usos + '</span>' +
                '  </div>' +
                '</div>' +
                '</div>';
        },

        /**
         * Renderizar comunidad
         */
        renderComunidad: function(item) {
            const imagen = item.imagen
                ? '<img src="' + item.imagen + '" class="fss-result-avatar" alt="">'
                : '<div class="fss-result-icon"><span class="dashicons dashicons-groups"></span></div>';

            return '<div class="fss-result-item fss-result-item--comunidad">' +
                imagen +
                '<div class="fss-result-content">' +
                '  <div class="fss-result-title">' +
                '    <a href="' + item.url + '">' + this.escapeHtml(item.nombre) + '</a>' +
                '  </div>' +
                (item.descripcion ? '  <div class="fss-result-text">' + this.escapeHtml(item.descripcion) + '</div>' : '') +
                '  <div class="fss-result-meta">' +
                '    <span><span class="dashicons dashicons-admin-users"></span> ' + item.miembros + ' ' + this.config.strings.miembros + '</span>' +
                '  </div>' +
                '</div>' +
                '</div>';
        },

        /**
         * Contar items mostrados
         */
        contarItemsMostrados: function() {
            let total = 0;
            for (const tipo in this.resultados) {
                total += this.resultados[tipo].items.length;
            }
            return total;
        },

        /**
         * Ocultar resultados
         */
        ocultarResultados: function($container) {
            $container.find('.fss-results').hide();
            $container.find('.fss-empty').hide();
        },

        /**
         * Mostrar error
         */
        mostrarError: function($container, mensaje) {
            $container.find('.fss-empty').show().find('p').text(mensaje);
            $container.find('.fss-results').hide();
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorSocialSearch.init();
    });

})(jQuery);
