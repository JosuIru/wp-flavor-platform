/**
 * Mapa de Actores Frontend JavaScript
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorMapaActores = {
        config: window.flavorMapaActoresConfig || {},
        mapa: null,
        grafo: null,
        simulation: null,
        actoresData: [],

        // Colores por tipo de actor
        colores: {
            'administracion_publica': '#3b82f6',
            'empresa': '#10b981',
            'institucion': '#8b5cf6',
            'medio_comunicacion': '#f59e0b',
            'partido_politico': '#ef4444',
            'sindicato': '#ec4899',
            'ong': '#14b8a6',
            'colectivo': '#f97316',
            'persona': '#6366f1',
            'otro': '#6b7280'
        },

        init: function() {
            this.bindEvents();
            this.initMapa();
            this.initGrafo();
        },

        bindEvents: function() {
            // Búsqueda
            $(document).on('submit', '#form-buscar-actores', this.buscar.bind(this));
            $(document).on('change', '#filtro-tipo', this.filtrar.bind(this));

            // Proponer actor
            $(document).on('submit', '#form-proponer-actor', this.proponerActor.bind(this));

            // Ver detalle
            $(document).on('click', '.flavor-btn-ver-actor', this.verDetalle.bind(this));

            // Grafo controles
            $(document).on('click', '#grafo-zoom-in', this.grafoZoomIn.bind(this));
            $(document).on('click', '#grafo-zoom-out', this.grafoZoomOut.bind(this));
            $(document).on('click', '#grafo-reset', this.grafoReset.bind(this));
        },

        /**
         * Inicializar mapa Leaflet
         */
        initMapa: function() {
            var $container = $('#mapa-actores');
            if (!$container.length || typeof L === 'undefined') return;

            var self = this;
            var centro = this.config.centro || [40.4168, -3.7038];

            this.mapa = L.map('mapa-actores').setView(centro, 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.mapa);

            // Cargar actores
            this.cargarActoresMapa();
        },

        /**
         * Cargar actores en el mapa
         */
        cargarActoresMapa: function() {
            var self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_mapa_actores_buscar',
                    nonce: this.config.nonce,
                    todos: true
                },
                success: function(response) {
                    if (response.success && response.data.actores) {
                        self.actoresData = response.data.actores;
                        self.mostrarActoresMapa(response.data.actores);
                    }
                }
            });
        },

        /**
         * Mostrar actores en el mapa
         */
        mostrarActoresMapa: function(actores) {
            var self = this;

            if (!this.mapa) return;

            // Limpiar marcadores existentes
            this.mapa.eachLayer(function(layer) {
                if (layer instanceof L.Marker) {
                    self.mapa.removeLayer(layer);
                }
            });

            var bounds = [];

            actores.forEach(function(actor) {
                if (!actor.lat || !actor.lng) return;

                var color = self.colores[actor.tipo] || self.colores.otro;

                var icon = L.divIcon({
                    className: 'flavor-actor-marker',
                    html: '<div style="background: ' + color + '; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><span class="dashicons dashicons-' + (self.config.iconos[actor.tipo] || 'marker') + '" style="font-size: 16px; width: 16px; height: 16px;"></span></div>',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                var marker = L.marker([actor.lat, actor.lng], { icon: icon })
                    .addTo(self.mapa);

                var popupContent = '<div class="flavor-mapa-actores-popup flavor-actor-tipo-' + actor.tipo + '">' +
                    '<h4>' + actor.nombre + '</h4>' +
                    '<span class="actor-tipo">' + (self.config.tipos[actor.tipo] || actor.tipo) + '</span>' +
                    (actor.descripcion ? '<p>' + actor.descripcion.substring(0, 100) + '...</p>' : '') +
                    '<a href="' + actor.url + '" class="flavor-btn flavor-btn-sm">Ver detalle</a>' +
                    '</div>';

                marker.bindPopup(popupContent);
                bounds.push([actor.lat, actor.lng]);
            });

            if (bounds.length > 0) {
                this.mapa.fitBounds(bounds, { padding: [50, 50] });
            }
        },

        /**
         * Inicializar grafo D3
         */
        initGrafo: function() {
            var $container = $('#grafo-actores');
            if (!$container.length || typeof d3 === 'undefined') return;

            var self = this;
            var width = $container.width();
            var height = $container.height() || 600;

            // Crear SVG
            var svg = d3.select('#grafo-actores')
                .append('svg')
                .attr('width', width)
                .attr('height', height);

            this.grafo = {
                svg: svg,
                width: width,
                height: height,
                zoom: d3.zoom()
                    .scaleExtent([0.1, 4])
                    .on('zoom', function(event) {
                        self.grafo.g.attr('transform', event.transform);
                    })
            };

            svg.call(this.grafo.zoom);
            this.grafo.g = svg.append('g');

            // Cargar datos del grafo
            this.cargarGrafo();
        },

        /**
         * Cargar datos del grafo
         */
        cargarGrafo: function() {
            var self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_mapa_actores_relaciones',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.renderizarGrafo(response.data);
                    }
                }
            });
        },

        /**
         * Renderizar grafo con D3
         */
        renderizarGrafo: function(data) {
            var self = this;
            var g = this.grafo.g;
            var width = this.grafo.width;
            var height = this.grafo.height;

            // Limpiar
            g.selectAll('*').remove();

            if (!data.nodes || !data.links) return;

            // Simulación de fuerzas
            this.simulation = d3.forceSimulation(data.nodes)
                .force('link', d3.forceLink(data.links).id(function(d) { return d.id; }).distance(100))
                .force('charge', d3.forceManyBody().strength(-300))
                .force('center', d3.forceCenter(width / 2, height / 2))
                .force('collision', d3.forceCollide().radius(40));

            // Enlaces
            var link = g.append('g')
                .attr('class', 'links')
                .selectAll('line')
                .data(data.links)
                .enter().append('line')
                .attr('class', function(d) {
                    return 'link' + (d.strength > 0.7 ? ' strong' : '');
                });

            // Nodos
            var node = g.append('g')
                .attr('class', 'nodes')
                .selectAll('.node')
                .data(data.nodes)
                .enter().append('g')
                .attr('class', 'node')
                .call(d3.drag()
                    .on('start', function(event, d) { self.dragStarted(event, d); })
                    .on('drag', function(event, d) { self.dragged(event, d); })
                    .on('end', function(event, d) { self.dragEnded(event, d); })
                );

            node.append('circle')
                .attr('r', function(d) {
                    return 15 + (d.relaciones || 0) * 2;
                })
                .attr('fill', function(d) {
                    return self.colores[d.tipo] || self.colores.otro;
                });

            node.append('text')
                .attr('dx', 20)
                .attr('dy', 4)
                .text(function(d) { return d.nombre; });

            // Tooltip
            var tooltip = d3.select('body').append('div')
                .attr('class', 'flavor-grafo-tooltip')
                .style('opacity', 0)
                .style('display', 'none');

            node.on('mouseover', function(event, d) {
                tooltip.transition()
                    .duration(200)
                    .style('opacity', 1)
                    .style('display', 'block');
                tooltip.html('<h5>' + d.nombre + '</h5><span class="tipo">' + (self.config.tipos[d.tipo] || d.tipo) + '</span>')
                    .style('left', (event.pageX + 10) + 'px')
                    .style('top', (event.pageY - 10) + 'px');
            })
            .on('mouseout', function() {
                tooltip.transition()
                    .duration(500)
                    .style('opacity', 0)
                    .on('end', function() {
                        tooltip.style('display', 'none');
                    });
            })
            .on('click', function(event, d) {
                if (d.url) {
                    window.location.href = d.url;
                }
            });

            // Actualizar posiciones
            this.simulation.on('tick', function() {
                link
                    .attr('x1', function(d) { return d.source.x; })
                    .attr('y1', function(d) { return d.source.y; })
                    .attr('x2', function(d) { return d.target.x; })
                    .attr('y2', function(d) { return d.target.y; });

                node
                    .attr('transform', function(d) {
                        return 'translate(' + d.x + ',' + d.y + ')';
                    });
            });
        },

        /**
         * Drag handlers para D3
         */
        dragStarted: function(event, d) {
            if (!event.active) this.simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        },

        dragged: function(event, d) {
            d.fx = event.x;
            d.fy = event.y;
        },

        dragEnded: function(event, d) {
            if (!event.active) this.simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        },

        /**
         * Controles de zoom del grafo
         */
        grafoZoomIn: function(e) {
            e.preventDefault();
            if (this.grafo && this.grafo.svg) {
                this.grafo.svg.transition().call(this.grafo.zoom.scaleBy, 1.5);
            }
        },

        grafoZoomOut: function(e) {
            e.preventDefault();
            if (this.grafo && this.grafo.svg) {
                this.grafo.svg.transition().call(this.grafo.zoom.scaleBy, 0.67);
            }
        },

        grafoReset: function(e) {
            e.preventDefault();
            if (this.grafo && this.grafo.svg) {
                this.grafo.svg.transition().call(
                    this.grafo.zoom.transform,
                    d3.zoomIdentity
                );
            }
        },

        /**
         * Buscar actores
         */
        buscar: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $resultados = $('#resultados-actores');
            var $btn = $form.find('button[type="submit"]');

            var busqueda = $form.find('[name="busqueda"]').val();
            var tipo = $form.find('[name="tipo"]').val();

            $btn.addClass('loading').prop('disabled', true);
            $resultados.html('<p class="flavor-loading"><span class="spinner"></span> ' + this.config.strings.buscando + '</p>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_mapa_actores_buscar',
                    nonce: this.config.nonce,
                    busqueda: busqueda,
                    tipo: tipo
                },
                success: function(response) {
                    if (response.success) {
                        self.renderResultados(response.data.actores);

                        // Actualizar mapa si existe
                        if (self.mapa) {
                            self.mostrarActoresMapa(response.data.actores);
                        }
                    } else {
                        $resultados.html('<p class="flavor-empty-state">' + self.config.strings.error + '</p>');
                    }
                },
                error: function() {
                    $resultados.html('<p class="flavor-empty-state">' + self.config.strings.error + '</p>');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Filtrar por tipo
         */
        filtrar: function(e) {
            var tipo = $(e.target).val();

            if (tipo) {
                $('.flavor-actor-card').hide().filter('[data-tipo="' + tipo + '"]').show();
            } else {
                $('.flavor-actor-card').show();
            }
        },

        /**
         * Renderizar resultados de búsqueda
         */
        renderResultados: function(actores) {
            var self = this;
            var $resultados = $('#resultados-actores');

            if (!actores || actores.length === 0) {
                $resultados.html('<div class="flavor-empty-state"><span class="dashicons dashicons-groups"></span><p>' + this.config.strings.sin_resultados + '</p></div>');
                return;
            }

            var html = '<div class="flavor-actores-directorio">';

            actores.forEach(function(actor) {
                var color = self.colores[actor.tipo] || self.colores.otro;

                html += '<article class="flavor-actor-card flavor-actor-tipo-' + actor.tipo + '" data-id="' + actor.id + '" data-tipo="' + actor.tipo + '" style="--actor-color: ' + color + '">';
                html += '<div class="flavor-actor-header">';
                html += '<div class="flavor-actor-avatar">';

                if (actor.imagen) {
                    html += '<img src="' + actor.imagen + '" alt="' + actor.nombre + '">';
                } else {
                    html += '<span class="dashicons dashicons-' + (self.config.iconos[actor.tipo] || 'groups') + '"></span>';
                }

                html += '</div>';
                html += '<div class="flavor-actor-info">';
                html += '<h4><a href="' + actor.url + '">' + actor.nombre + '</a></h4>';
                html += '<span class="flavor-actor-tipo-badge">' + (self.config.tipos[actor.tipo] || actor.tipo) + '</span>';
                html += '</div></div>';

                html += '<div class="flavor-actor-body">';

                if (actor.descripcion) {
                    html += '<p class="flavor-actor-descripcion">' + actor.descripcion + '</p>';
                }

                html += '<div class="flavor-actor-meta">';

                if (actor.ubicacion) {
                    html += '<div class="flavor-actor-meta-item"><span class="dashicons dashicons-location"></span> ' + actor.ubicacion + '</div>';
                }

                if (actor.web) {
                    html += '<div class="flavor-actor-meta-item"><span class="dashicons dashicons-admin-links"></span> <a href="' + actor.web + '" target="_blank">Sitio web</a></div>';
                }

                html += '</div></div>';

                html += '<div class="flavor-actor-footer">';
                html += '<span class="flavor-actor-relaciones"><span class="dashicons dashicons-networking"></span> ' + (actor.relaciones || 0) + ' relaciones</span>';
                html += '<a href="' + actor.url + '" class="flavor-btn flavor-btn-sm">Ver detalle</a>';
                html += '</div></article>';
            });

            html += '</div>';
            $resultados.html(html);
        },

        /**
         * Ver detalle de actor
         */
        verDetalle: function(e) {
            e.preventDefault();

            var actorId = $(e.currentTarget).data('actor-id');
            var url = this.config.urlBase + '?actor=' + actorId;

            window.location.href = url;
        },

        /**
         * Proponer nuevo actor
         */
        proponerActor: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            var formData = {
                action: 'flavor_mapa_actores_proponer',
                nonce: this.config.nonce,
                nombre: $form.find('[name="nombre"]').val(),
                tipo: $form.find('[name="tipo"]').val(),
                descripcion: $form.find('[name="descripcion"]').val(),
                web: $form.find('[name="web"]').val(),
                email: $form.find('[name="email"]').val(),
                telefono: $form.find('[name="telefono"]').val(),
                direccion: $form.find('[name="direccion"]').val()
            };

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');
                        $form[0].reset();
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Mostrar toast
         */
        showToast: function(mensaje, tipo) {
            var $toast = $('<div class="flavor-toast ' + (tipo || '') + '">' + mensaje + '</div>');
            $('body').append($toast);

            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializar
    $(document).ready(function() {
        FlavorMapaActores.init();
    });

    // Estilos dinámicos para marcadores
    var estilos = document.createElement('style');
    estilos.textContent = '.flavor-actor-marker { background: transparent !important; border: none !important; }' +
        '.flavor-btn.loading { pointer-events: none; opacity: 0.7; }';
    document.head.appendChild(estilos);

})(jQuery);
