/**
 * JavaScript del módulo Mapa de Actores
 */
(function($) {
    'use strict';

    const MapaActores = {
        grafo: null,
        network: null,
        actoresData: [],

        init: function() {
            this.bindEvents();
            this.initFiltros();
            this.initGrafo();
            this.initInfluencia();
        },

        bindEvents: function() {
            // Formulario de nuevo actor
            $(document).on('submit', '.flavor-actor-form', this.handleSubmitActor.bind(this));

            // Añadir relación
            $(document).on('submit', '.flavor-relacion-form', this.handleAddRelacion.bind(this));

            // Registrar interacción
            $(document).on('submit', '.flavor-interaccion-form', this.handleAddInteraccion.bind(this));

            // Filtros
            $(document).on('change', '.flavor-actores-filtro', this.handleFiltrar.bind(this));

            // Búsqueda
            $(document).on('input', '.flavor-actores-buscar', this.handleBuscar.bind(this));

            // Toggle vista grafo/lista
            $(document).on('click', '.flavor-vista-toggle', this.toggleVista.bind(this));

            // Click en nodo del grafo
            $(document).on('click', '.flavor-grafo-node', this.handleNodeClick.bind(this));

            // Editar posición
            $(document).on('change', '.flavor-actor-posicion-select', this.handleCambiarPosicion.bind(this));
        },

        initFiltros: function() {
            const urlParams = new URLSearchParams(window.location.search);

            ['tipo', 'posicion', 'influencia'].forEach(filtro => {
                const valor = urlParams.get(filtro);
                if (valor) {
                    $(`.flavor-filtro-${filtro}`).val(valor);
                }
            });
        },

        initInfluencia: function() {
            // Visualizar nivel de influencia con dots
            $('.flavor-actor-influencia').each(function() {
                const nivel = parseInt($(this).data('nivel')) || 0;
                const $dots = $(this).find('.flavor-influencia-dot');

                $dots.each(function(index) {
                    if (index < nivel) {
                        $(this).addClass('activo');
                    }
                });
            });
        },

        initGrafo: function() {
            const $container = $('#flavor-grafo-actores');
            if (!$container.length) return;

            // Verificar si vis.js está disponible
            if (typeof vis === 'undefined') {
                console.warn('vis.js no está cargado. El grafo no se mostrará.');
                $container.html('<p class="flavor-grafo-error">Cargando visualización...</p>');
                return;
            }

            // Obtener datos del grafo
            $.post(flavorMapaActores.ajaxUrl, {
                action: 'flavor_mapa_actores_get_grafo',
                nonce: flavorMapaActores.nonce
            }, (response) => {
                if (response.success) {
                    this.renderGrafo($container[0], response.data);
                }
            });
        },

        renderGrafo: function(container, data) {
            // Configurar nodos
            const nodes = new vis.DataSet(data.nodes.map(node => ({
                id: node.id,
                label: node.nombre,
                title: `${node.nombre}\n${node.tipo}\nInfluencia: ${node.influencia}/5`,
                color: this.getColorByPosicion(node.posicion),
                size: 15 + (node.influencia * 5),
                font: { size: 12 }
            })));

            // Configurar aristas
            const edges = new vis.DataSet(data.edges.map(edge => ({
                from: edge.actor_origen_id,
                to: edge.actor_destino_id,
                label: edge.tipo_relacion,
                arrows: edge.direccion === 'bidireccional' ? 'to,from' : 'to',
                color: this.getEdgeColor(edge.tipo_relacion),
                width: 2
            })));

            // Opciones del grafo
            const options = {
                nodes: {
                    shape: 'dot',
                    borderWidth: 2
                },
                edges: {
                    smooth: {
                        type: 'continuous'
                    }
                },
                physics: {
                    stabilization: {
                        iterations: 100
                    },
                    barnesHut: {
                        gravitationalConstant: -2000,
                        springLength: 150
                    }
                },
                interaction: {
                    hover: true,
                    tooltipDelay: 200
                }
            };

            // Crear network
            this.network = new vis.Network(container, { nodes, edges }, options);

            // Eventos del network
            this.network.on('click', (params) => {
                if (params.nodes.length > 0) {
                    const nodeId = params.nodes[0];
                    this.showActorDetail(nodeId);
                }
            });

            this.network.on('doubleClick', (params) => {
                if (params.nodes.length > 0) {
                    const nodeId = params.nodes[0];
                    window.location.href = `${flavorMapaActores.baseUrl}?actor=${nodeId}`;
                }
            });
        },

        getColorByPosicion: function(posicion) {
            const colores = {
                'aliado': { background: '#dcfce7', border: '#16a34a' },
                'neutro': { background: '#f3f4f6', border: '#6b7280' },
                'opositor': { background: '#fee2e2', border: '#dc2626' },
                'desconocido': { background: '#e5e7eb', border: '#9ca3af' }
            };
            return colores[posicion] || colores['desconocido'];
        },

        getEdgeColor: function(tipo) {
            const colores = {
                'colaboracion': '#10b981',
                'oposicion': '#ef4444',
                'dependencia': '#3b82f6',
                'influencia': '#8b5cf6',
                'neutral': '#6b7280'
            };
            return { color: colores[tipo] || colores['neutral'] };
        },

        showActorDetail: function(actorId) {
            $.post(flavorMapaActores.ajaxUrl, {
                action: 'flavor_mapa_actores_get_actor',
                nonce: flavorMapaActores.nonce,
                actor_id: actorId
            }, (response) => {
                if (response.success) {
                    this.renderActorPanel(response.data.actor);
                }
            });
        },

        renderActorPanel: function(actor) {
            const $panel = $('.flavor-actor-panel');
            if (!$panel.length) return;

            const html = `
                <div class="flavor-actor-panel-header">
                    <h3>${this.escapeHtml(actor.nombre)}</h3>
                    <span class="flavor-actor-posicion flavor-posicion-${actor.posicion}">${actor.posicion}</span>
                </div>
                <div class="flavor-actor-panel-body">
                    <p><strong>Tipo:</strong> ${this.escapeHtml(actor.tipo)}</p>
                    <p><strong>Influencia:</strong> ${actor.nivel_influencia}/5</p>
                    ${actor.descripcion ? `<p>${this.escapeHtml(actor.descripcion)}</p>` : ''}
                    <a href="${flavorMapaActores.baseUrl}?actor=${actor.id}" class="flavor-btn flavor-btn-primary">Ver detalle</a>
                </div>
            `;

            $panel.html(html).addClass('activo');
        },

        handleSubmitActor: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submitBtn = $form.find('[type="submit"]');

            $submitBtn.prop('disabled', true).text('Guardando...');

            const formData = new FormData($form[0]);
            formData.append('action', 'flavor_mapa_actores_crear');
            formData.append('nonce', flavorMapaActores.nonce);

            $.ajax({
                url: flavorMapaActores.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('Actor registrado correctamente', 'success');
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        this.showNotice(response.data.message || 'Error al guardar', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Error de conexión', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text('Guardar actor');
                }
            });
        },

        handleAddRelacion: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);

            const formData = {
                action: 'flavor_mapa_actores_add_relacion',
                nonce: flavorMapaActores.nonce,
                actor_origen: $form.find('[name="actor_origen"]').val(),
                actor_destino: $form.find('[name="actor_destino"]').val(),
                tipo_relacion: $form.find('[name="tipo_relacion"]').val(),
                descripcion: $form.find('[name="descripcion"]').val()
            };

            $.post(flavorMapaActores.ajaxUrl, formData, (response) => {
                if (response.success) {
                    this.showNotice('Relación añadida', 'success');

                    // Actualizar grafo si está visible
                    if (this.network) {
                        this.initGrafo();
                    }

                    $form[0].reset();
                } else {
                    this.showNotice(response.data.message || 'Error', 'error');
                }
            });
        },

        handleAddInteraccion: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);

            const formData = {
                action: 'flavor_mapa_actores_add_interaccion',
                nonce: flavorMapaActores.nonce,
                actor_id: $form.find('[name="actor_id"]').val(),
                tipo: $form.find('[name="tipo"]').val(),
                descripcion: $form.find('[name="descripcion"]').val(),
                resultado: $form.find('[name="resultado"]').val(),
                fecha: $form.find('[name="fecha"]').val()
            };

            $.post(flavorMapaActores.ajaxUrl, formData, (response) => {
                if (response.success) {
                    this.showNotice('Interacción registrada', 'success');
                    this.appendInteraccion(response.data.interaccion);
                    $form[0].reset();
                } else {
                    this.showNotice(response.data.message || 'Error', 'error');
                }
            });
        },

        appendInteraccion: function(interaccion) {
            const html = `
                <div class="flavor-interaccion-item flavor-interaccion-${interaccion.resultado}">
                    <div class="flavor-interaccion-fecha">${interaccion.fecha_formatted}</div>
                    <div class="flavor-interaccion-tipo">${this.escapeHtml(interaccion.tipo)}</div>
                    <div class="flavor-interaccion-descripcion">${this.escapeHtml(interaccion.descripcion)}</div>
                </div>
            `;

            $('.flavor-interacciones-lista').prepend(html);
        },

        handleFiltrar: function(e) {
            const $filtros = $('.flavor-actores-filtro');
            const params = new URLSearchParams();

            $filtros.each(function() {
                const valor = $(this).val();
                const nombre = $(this).attr('name');
                if (valor) {
                    params.set(nombre, valor);
                }
            });

            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        },

        handleBuscar: function(e) {
            const query = $(e.currentTarget).val().toLowerCase();

            $('.flavor-actor-card').each(function() {
                const nombre = $(this).find('.flavor-actor-nombre').text().toLowerCase();
                const tipo = $(this).find('.flavor-actor-tipo').text().toLowerCase();

                const match = nombre.includes(query) || tipo.includes(query);
                $(this).toggle(match);
            });
        },

        toggleVista: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const vista = $btn.data('vista');

            $('.flavor-vista-toggle').removeClass('activo');
            $btn.addClass('activo');

            if (vista === 'grafo') {
                $('.flavor-actores-grid').hide();
                $('.flavor-grafo-container').show();

                // Reiniciar grafo si es necesario
                if (this.network) {
                    this.network.fit();
                }
            } else {
                $('.flavor-grafo-container').hide();
                $('.flavor-actores-grid').show();
            }
        },

        handleNodeClick: function(e) {
            const nodeId = $(e.currentTarget).data('node-id');
            this.showActorDetail(nodeId);
        },

        handleCambiarPosicion: function(e) {
            const $select = $(e.currentTarget);
            const actorId = $select.data('actor-id');
            const nuevaPosicion = $select.val();

            $.post(flavorMapaActores.ajaxUrl, {
                action: 'flavor_mapa_actores_cambiar_posicion',
                nonce: flavorMapaActores.nonce,
                actor_id: actorId,
                posicion: nuevaPosicion
            }, (response) => {
                if (response.success) {
                    this.showNotice('Posición actualizada', 'success');

                    // Actualizar visualización
                    const $card = $select.closest('.flavor-actor-card');
                    $card.find('.flavor-actor-posicion')
                        .removeClass('flavor-posicion-aliado flavor-posicion-neutro flavor-posicion-opositor flavor-posicion-desconocido')
                        .addClass('flavor-posicion-' + nuevaPosicion)
                        .text(nuevaPosicion);

                    // Actualizar grafo
                    if (this.network) {
                        this.initGrafo();
                    }
                } else {
                    this.showNotice('Error al actualizar', 'error');
                }
            });
        },

        showNotice: function(message, type) {
            const $notice = $(`
                <div class="flavor-notice flavor-notice-${type}">
                    ${this.escapeHtml(message)}
                </div>
            `);

            $('.flavor-notices-container').append($notice);

            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($('.flavor-mapa-actores').length || $('.flavor-actor-form').length || $('#flavor-grafo-actores').length) {
            MapaActores.init();
        }
    });

})(jQuery);
