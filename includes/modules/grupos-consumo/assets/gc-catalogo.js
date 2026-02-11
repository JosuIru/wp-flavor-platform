/**
 * JavaScript del Catalogo y Carrito - Grupos de Consumo
 *
 * Funcionalidades:
 * - Catalogo con filtros dinamicos
 * - Carrito flotante con persistencia en localStorage
 * - Sincronizacion con servidor
 * - Paginacion AJAX (load more)
 * - Animaciones y notificaciones
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

(function($) {
    'use strict';

    // Configuracion global del modulo
    const GCConfig = window.gcFrontend || {};

    // Estado del carrito local
    const CarritoLocal = {
        KEY: 'gc_carrito_local',

        /**
         * Obtener carrito del localStorage
         */
        obtener: function() {
            try {
                const data = localStorage.getItem(this.KEY);
                return data ? JSON.parse(data) : { items: [], actualizadoEn: null };
            } catch (e) {
                console.error('Error al leer carrito local:', e);
                return { items: [], actualizadoEn: null };
            }
        },

        /**
         * Guardar carrito en localStorage
         */
        guardar: function(items) {
            try {
                const data = {
                    items: items,
                    actualizadoEn: new Date().toISOString()
                };
                localStorage.setItem(this.KEY, JSON.stringify(data));
            } catch (e) {
                console.error('Error al guardar carrito local:', e);
            }
        },

        /**
         * Agregar item al carrito local
         */
        agregar: function(productoId, cantidad) {
            const carrito = this.obtener();
            const indice = carrito.items.findIndex(item => item.producto_id === productoId);

            if (indice >= 0) {
                carrito.items[indice].cantidad = cantidad;
            } else {
                carrito.items.push({ producto_id: productoId, cantidad: cantidad });
            }

            this.guardar(carrito.items);
        },

        /**
         * Quitar item del carrito local
         */
        quitar: function(productoId) {
            const carrito = this.obtener();
            const items = carrito.items.filter(item => item.producto_id !== productoId);
            this.guardar(items);
        },

        /**
         * Limpiar carrito local
         */
        limpiar: function() {
            localStorage.removeItem(this.KEY);
        },

        /**
         * Sincronizar con servidor
         */
        sincronizar: function() {
            if (!GCConfig.isLoggedIn) return;

            const carrito = this.obtener();
            if (!carrito.items.length) return;

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_sincronizar_carrito',
                    nonce: GCConfig.nonce,
                    items_local: JSON.stringify(carrito.items)
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar UI con datos del servidor
                        CarritoFlotante.actualizar(response.data.carrito);
                    }
                }
            });
        }
    };

    /**
     * Modulo del Catalogo
     */
    const Catalogo = {
        $contenedor: null,
        $grid: null,
        $filtros: {},
        paginaActual: 1,
        cargando: false,
        filtrosActivos: {},

        /**
         * Inicializar catalogo
         */
        init: function() {
            this.$contenedor = $('.flavor-gc-catalogo');
            if (!this.$contenedor.length) return;

            this.$grid = $('#gc-productos-grid');
            this.cachearFiltros();
            this.vincularEventos();
            this.iniciarCountdown();
        },

        /**
         * Cachear elementos de filtro
         */
        cachearFiltros: function() {
            this.$filtros = {
                busqueda: $('#gc-buscar-producto'),
                limpiarBusqueda: $('#gc-limpiar-busqueda'),
                categorias: $('.gc-filtro-categoria'),
                productores: $('.gc-filtro-productor'),
                precioMin: $('#gc-precio-min'),
                precioMax: $('#gc-precio-max'),
                precioMinValor: $('#gc-precio-min-valor'),
                precioMaxValor: $('#gc-precio-max-valor'),
                soloDisponibles: $('#gc-solo-disponibles'),
                soloEcologicos: $('#gc-solo-ecologicos'),
                ordenar: $('#gc-ordenar'),
                limpiarFiltros: $('#gc-limpiar-filtros, #gc-reiniciar-filtros'),
                toggleFiltros: $('#gc-toggle-filtros'),
                sidebar: $('.flavor-gc-filtros-sidebar'),
                overlay: $('#gc-filtros-overlay')
            };
        },

        /**
         * Vincular eventos
         */
        vincularEventos: function() {
            const self = this;

            // Busqueda con debounce
            this.$filtros.busqueda.on('input', this.debounce(function() {
                const valor = $(this).val().trim();
                self.$filtros.limpiarBusqueda.toggle(valor.length > 0);
                self.filtrar();
            }, 300));

            this.$filtros.limpiarBusqueda.on('click', function() {
                self.$filtros.busqueda.val('').trigger('input');
            });

            // Filtros de checkbox
            this.$filtros.categorias.add(this.$filtros.productores).on('change', function() {
                self.filtrar();
            });

            // Rango de precio
            this.$filtros.precioMin.add(this.$filtros.precioMax).on('input', function() {
                self.actualizarRangoPrecios();
            });

            this.$filtros.precioMin.add(this.$filtros.precioMax).on('change', function() {
                self.filtrar();
            });

            // Toggles
            this.$filtros.soloDisponibles.add(this.$filtros.soloEcologicos).on('change', function() {
                self.filtrar();
            });

            // Ordenar
            this.$filtros.ordenar.on('change', function() {
                self.ordenar($(this).val());
            });

            // Limpiar filtros
            this.$filtros.limpiarFiltros.on('click', function() {
                self.limpiarFiltros();
            });

            // Toggle filtros movil
            this.$filtros.toggleFiltros.on('click', function() {
                self.toggleFiltrosMovil(true);
            });

            this.$filtros.overlay.on('click', function() {
                self.toggleFiltrosMovil(false);
            });

            // Vista grid/lista
            $('.flavor-gc-vista-btn').on('click', function() {
                self.cambiarVista($(this).data('vista'));
            });

            // Cargar mas productos
            $('#gc-btn-cargar-mas').on('click', function() {
                self.cargarMas();
            });

            // Colapsar/expandir grupos de filtros
            $('.flavor-gc-filtro-titulo').on('click', function() {
                $(this).closest('.flavor-gc-filtro-grupo').toggleClass('collapsed');
            });

            // Productos: agregar/quitar
            $(document).on('click', '.flavor-gc-btn-agregar', function(e) {
                e.preventDefault();
                const $card = $(this).closest('.flavor-gc-producto-card');
                const productoId = $card.data('producto-id');
                const cantidad = parseFloat($card.find('.flavor-gc-cantidad-input').val()) || 1;

                if ($(this).hasClass('en-lista')) {
                    Carrito.quitar(productoId, $card);
                } else {
                    Carrito.agregar(productoId, cantidad, $card);
                }
            });

            // Control de cantidad en producto
            $(document).on('click', '.flavor-gc-cantidad-btn', function() {
                const $card = $(this).closest('.flavor-gc-producto-card');
                const $input = $card.find('.flavor-gc-cantidad-input');
                const min = parseFloat($input.attr('min')) || 1;
                const max = parseFloat($input.attr('max')) || 999;
                let cantidad = parseFloat($input.val()) || 1;

                if ($(this).data('action') === 'incrementar') {
                    cantidad = Math.min(cantidad + 1, max);
                } else {
                    cantidad = Math.max(cantidad - 1, min);
                }

                $input.val(cantidad);

                // Si ya esta en lista, actualizar
                if ($card.hasClass('en-lista')) {
                    Carrito.actualizar($card.data('producto-id'), cantidad, $card);
                }
            });

            // Cambio directo en input de cantidad
            $(document).on('change', '.flavor-gc-cantidad-input', function() {
                const $card = $(this).closest('.flavor-gc-producto-card');
                const min = parseFloat($(this).attr('min')) || 1;
                const max = parseFloat($(this).attr('max')) || 999;
                let cantidad = parseFloat($(this).val()) || min;

                cantidad = Math.max(min, Math.min(max, cantidad));
                $(this).val(cantidad);

                if ($card.hasClass('en-lista')) {
                    Carrito.actualizar($card.data('producto-id'), cantidad, $card);
                }
            });
        },

        /**
         * Actualizar visualizacion de rango de precios
         */
        actualizarRangoPrecios: function() {
            const minVal = parseInt(this.$filtros.precioMin.val());
            const maxVal = parseInt(this.$filtros.precioMax.val());

            if (minVal > maxVal) {
                this.$filtros.precioMin.val(maxVal);
            }

            this.$filtros.precioMinValor.text(this.$filtros.precioMin.val());
            this.$filtros.precioMaxValor.text(this.$filtros.precioMax.val());
        },

        /**
         * Filtrar productos (lado cliente para rapido feedback)
         */
        filtrar: function() {
            const self = this;
            const busqueda = this.$filtros.busqueda.val().toLowerCase().trim();
            const categoriasSeleccionadas = this.$filtros.categorias.filter(':checked').map(function() {
                return $(this).val();
            }).get();
            const productoresSeleccionados = this.$filtros.productores.filter(':checked').map(function() {
                return $(this).val();
            }).get();
            const precioMin = parseFloat(this.$filtros.precioMin.val()) || 0;
            const precioMax = parseFloat(this.$filtros.precioMax.val()) || 9999;
            const soloDisponibles = this.$filtros.soloDisponibles.is(':checked');
            const soloEcologicos = this.$filtros.soloEcologicos.is(':checked');

            let productosVisibles = 0;

            this.$grid.find('.flavor-gc-producto-card').each(function() {
                const $card = $(this);
                const nombre = $card.data('nombre') || '';
                const precio = parseFloat($card.data('precio')) || 0;
                const productorId = String($card.data('productor-id') || '');
                const categoriasProducto = ($card.data('categorias') || '').split(' ');
                const stock = $card.data('stock');
                const esEcologico = $card.data('ecologico') === 1 || $card.data('ecologico') === '1';

                let mostrar = true;

                // Filtro busqueda
                if (busqueda && nombre.indexOf(busqueda) === -1) {
                    mostrar = false;
                }

                // Filtro categorias
                if (mostrar && categoriasSeleccionadas.length > 0) {
                    const coincide = categoriasSeleccionadas.some(cat => categoriasProducto.includes(cat));
                    if (!coincide) mostrar = false;
                }

                // Filtro productores
                if (mostrar && productoresSeleccionados.length > 0) {
                    if (!productoresSeleccionados.includes(productorId)) {
                        mostrar = false;
                    }
                }

                // Filtro precio
                if (mostrar && (precio < precioMin || precio > precioMax)) {
                    mostrar = false;
                }

                // Filtro disponibles
                if (mostrar && soloDisponibles) {
                    if (stock !== '' && stock !== undefined && parseFloat(stock) <= 0) {
                        mostrar = false;
                    }
                }

                // Filtro ecologicos
                if (mostrar && soloEcologicos && !esEcologico) {
                    mostrar = false;
                }

                $card.toggle(mostrar);
                if (mostrar) productosVisibles++;
            });

            // Mostrar/ocultar mensaje de sin resultados
            $('#gc-sin-resultados').toggle(productosVisibles === 0);

            // Actualizar contador de filtros activos
            this.actualizarContadorFiltros();
        },

        /**
         * Ordenar productos
         */
        ordenar: function(criterio) {
            const $cards = this.$grid.find('.flavor-gc-producto-card').get();

            $cards.sort(function(a, b) {
                const $a = $(a);
                const $b = $(b);

                switch (criterio) {
                    case 'nombre-asc':
                        return ($a.data('nombre') || '').localeCompare($b.data('nombre') || '');
                    case 'nombre-desc':
                        return ($b.data('nombre') || '').localeCompare($a.data('nombre') || '');
                    case 'precio-asc':
                        return parseFloat($a.data('precio') || 0) - parseFloat($b.data('precio') || 0);
                    case 'precio-desc':
                        return parseFloat($b.data('precio') || 0) - parseFloat($a.data('precio') || 0);
                    case 'productor':
                        return String($a.data('productor-id') || '').localeCompare(String($b.data('productor-id') || ''));
                    default:
                        return 0;
                }
            });

            $.each($cards, function(idx, card) {
                this.$grid.append(card);
            }.bind(this));
        },

        /**
         * Limpiar todos los filtros
         */
        limpiarFiltros: function() {
            this.$filtros.busqueda.val('');
            this.$filtros.limpiarBusqueda.hide();
            this.$filtros.categorias.prop('checked', false);
            this.$filtros.productores.prop('checked', false);
            this.$filtros.precioMin.val(this.$filtros.precioMin.attr('min'));
            this.$filtros.precioMax.val(this.$filtros.precioMax.attr('max'));
            this.$filtros.soloDisponibles.prop('checked', false);
            this.$filtros.soloEcologicos.prop('checked', false);
            this.$filtros.ordenar.val('nombre-asc');

            this.actualizarRangoPrecios();
            this.filtrar();
            this.ordenar('nombre-asc');
        },

        /**
         * Toggle filtros en movil
         */
        toggleFiltrosMovil: function(mostrar) {
            this.$filtros.sidebar.toggleClass('visible', mostrar);
            this.$filtros.overlay.toggleClass('visible', mostrar);
            $('body').toggleClass('gc-filtros-abiertos', mostrar);
        },

        /**
         * Cambiar vista grid/lista
         */
        cambiarVista: function(vista) {
            $('.flavor-gc-vista-btn').removeClass('active');
            $(`.flavor-gc-vista-btn[data-vista="${vista}"]`).addClass('active');

            this.$grid.toggleClass('vista-lista', vista === 'lista');
        },

        /**
         * Cargar mas productos (paginacion AJAX)
         */
        cargarMas: function() {
            if (this.cargando) return;

            const self = this;
            const $btn = $('#gc-btn-cargar-mas');

            this.cargando = true;
            $btn.find('.flavor-gc-btn-texto').hide();
            $btn.find('.flavor-gc-btn-loading').show();

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_cargar_mas_productos',
                    nonce: GCConfig.nonce,
                    pagina: this.paginaActual,
                    por_pagina: 12,
                    categoria: this.$filtros.categorias.filter(':checked').first().val() || '',
                    productor_id: this.$filtros.productores.filter(':checked').first().val() || 0,
                    busqueda: this.$filtros.busqueda.val() || '',
                    orden: this.$filtros.ordenar.val() || 'nombre-asc'
                },
                success: function(response) {
                    if (response.success && response.data.productos.length) {
                        self.renderizarProductos(response.data.productos);
                        self.paginaActual = response.data.pagina_actual;

                        if (!response.data.hay_mas) {
                            $btn.parent().hide();
                        }
                    }
                },
                error: function() {
                    Notificacion.mostrar(GCConfig.i18n.error || 'Error al cargar productos', 'error');
                },
                complete: function() {
                    self.cargando = false;
                    $btn.find('.flavor-gc-btn-texto').show();
                    $btn.find('.flavor-gc-btn-loading').hide();
                }
            });
        },

        /**
         * Renderizar productos cargados
         */
        renderizarProductos: function(productos) {
            const self = this;

            productos.forEach(function(producto) {
                const html = self.crearHTMLProducto(producto);
                self.$grid.append(html);
            });
        },

        /**
         * Crear HTML de tarjeta de producto
         */
        crearHTMLProducto: function(producto) {
            const enLista = producto.en_lista ? 'en-lista' : '';
            const sinStock = !producto.tiene_stock ? 'sin-stock' : '';
            const badgeEco = producto.es_ecologico ? `
                <span class="flavor-gc-badge-eco">
                    <span class="dashicons dashicons-awards"></span>
                    ECO
                </span>
            ` : '';
            const badgeEnLista = producto.en_lista ? '<span class="flavor-gc-badge-en-lista"><span class="dashicons dashicons-yes"></span></span>' : '';
            const overlayAgotado = !producto.tiene_stock ? '<div class="flavor-gc-overlay-agotado"><span>Agotado</span></div>' : '';

            let imagen = '';
            if (producto.imagen) {
                imagen = `<img src="${producto.imagen}" alt="${producto.nombre}" loading="lazy">`;
            } else {
                imagen = '<div class="flavor-gc-imagen-placeholder"><span class="dashicons dashicons-carrot"></span></div>';
            }

            return `
                <article class="flavor-gc-producto-card ${enLista} ${sinStock}"
                         data-producto-id="${producto.id}"
                         data-precio="${producto.precio}"
                         data-nombre="${producto.nombre.toLowerCase()}"
                         data-productor-id="${producto.productor_id}"
                         data-stock="${producto.stock || ''}"
                         data-ecologico="${producto.es_ecologico ? '1' : '0'}">
                    <div class="flavor-gc-producto-imagen">
                        ${imagen}
                        ${badgeEco}
                        ${badgeEnLista}
                        ${overlayAgotado}
                    </div>
                    <div class="flavor-gc-producto-contenido">
                        <h3 class="flavor-gc-producto-nombre">
                            <a href="${producto.enlace}">${producto.nombre}</a>
                        </h3>
                        ${producto.productor_nombre ? `
                            <p class="flavor-gc-producto-productor">
                                <span class="dashicons dashicons-admin-users"></span>
                                ${producto.productor_nombre}
                            </p>
                        ` : ''}
                        <div class="flavor-gc-producto-precio-stock">
                            <div class="flavor-gc-producto-precio">
                                <span class="flavor-gc-precio-valor">${producto.precio.toFixed(2).replace('.', ',')}</span>
                                <span class="flavor-gc-precio-moneda">EUR</span>
                                <span class="flavor-gc-precio-unidad">/ ${producto.unidad}</span>
                            </div>
                            ${producto.tiene_stock && producto.stock_bajo ? `
                                <div class="flavor-gc-producto-stock stock-bajo">
                                    <span class="dashicons dashicons-warning"></span>
                                    Quedan ${producto.stock}
                                </div>
                            ` : ''}
                        </div>
                        ${producto.tiene_stock && GCConfig.isLoggedIn ? `
                            <div class="flavor-gc-producto-acciones">
                                <div class="flavor-gc-cantidad-control">
                                    <button type="button" class="flavor-gc-cantidad-btn flavor-gc-cantidad-menos" data-action="decrementar">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                    <input type="number" class="flavor-gc-cantidad-input" value="${producto.cantidad_en_lista}" min="1" max="${producto.stock || 999}" step="1">
                                    <button type="button" class="flavor-gc-cantidad-btn flavor-gc-cantidad-mas" data-action="incrementar">
                                        <span class="dashicons dashicons-plus"></span>
                                    </button>
                                </div>
                                <button type="button" class="flavor-gc-btn-agregar ${enLista}">
                                    <span class="dashicons ${producto.en_lista ? 'dashicons-yes' : 'dashicons-cart'}"></span>
                                    <span class="flavor-gc-btn-texto">${producto.en_lista ? 'En pedido' : 'Anadir'}</span>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </article>
            `;
        },

        /**
         * Actualizar contador de filtros activos
         */
        actualizarContadorFiltros: function() {
            let count = 0;

            if (this.$filtros.busqueda.val().trim()) count++;
            count += this.$filtros.categorias.filter(':checked').length;
            count += this.$filtros.productores.filter(':checked').length;
            if (this.$filtros.soloDisponibles.is(':checked')) count++;
            if (this.$filtros.soloEcologicos.is(':checked')) count++;

            const $contador = $('#gc-filtros-count');
            if (count > 0) {
                $contador.text(count).show();
            } else {
                $contador.hide();
            }
        },

        /**
         * Iniciar countdown del ciclo
         */
        iniciarCountdown: function() {
            const $countdown = $('#gc-countdown');
            if (!$countdown.length) return;

            const fechaCierre = new Date($countdown.closest('[data-cierre]').data('cierre')).getTime();

            const actualizarCountdown = function() {
                const ahora = new Date().getTime();
                const diferencia = fechaCierre - ahora;

                if (diferencia < 0) {
                    $countdown.html('<strong>Ciclo cerrado</strong>');
                    return;
                }

                const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
                const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));

                let texto = '';
                if (dias > 0) texto += `${dias}d `;
                if (horas > 0 || dias > 0) texto += `${horas}h `;
                texto += `${minutos}m`;

                $countdown.text(texto);
            };

            actualizarCountdown();
            setInterval(actualizarCountdown, 60000); // Actualizar cada minuto
        },

        /**
         * Debounce helper
         */
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    };

    /**
     * Modulo del Carrito (operaciones)
     */
    const Carrito = {
        /**
         * Agregar producto
         */
        agregar: function(productoId, cantidad, $card) {
            if (!GCConfig.isLoggedIn) {
                window.location.href = GCConfig.loginUrl || '/wp-login.php';
                return;
            }

            const $btn = $card.find('.flavor-gc-btn-agregar');
            $btn.prop('disabled', true);

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_agregar_producto',
                    nonce: GCConfig.nonce,
                    producto_id: productoId,
                    cantidad: cantidad
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar UI de la tarjeta
                        $card.addClass('en-lista');
                        $btn.addClass('en-lista');
                        $btn.find('.dashicons').removeClass('dashicons-cart').addClass('dashicons-yes');
                        $btn.find('.flavor-gc-btn-texto').text('En pedido');

                        // Agregar badge
                        if (!$card.find('.flavor-gc-badge-en-lista').length) {
                            $card.find('.flavor-gc-producto-imagen').append(
                                '<span class="flavor-gc-badge-en-lista"><span class="dashicons dashicons-yes"></span></span>'
                            );
                        }

                        // Actualizar carrito flotante
                        CarritoFlotante.actualizar(response.data.carrito);

                        // Guardar en local
                        CarritoLocal.agregar(productoId, cantidad);

                        Notificacion.mostrar(response.data.message || 'Producto agregado', 'success');
                    } else {
                        Notificacion.mostrar(response.data.message || 'Error', 'error');
                    }
                },
                error: function() {
                    Notificacion.mostrar(GCConfig.i18n.error || 'Error de conexion', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Quitar producto
         */
        quitar: function(productoId, $card) {
            const $btn = $card.find('.flavor-gc-btn-agregar');
            $btn.prop('disabled', true);

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_quitar_producto',
                    nonce: GCConfig.nonce,
                    producto_id: productoId
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar UI de la tarjeta
                        $card.removeClass('en-lista');
                        $btn.removeClass('en-lista');
                        $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-cart');
                        $btn.find('.flavor-gc-btn-texto').text('Anadir');

                        // Quitar badge
                        $card.find('.flavor-gc-badge-en-lista').remove();

                        // Actualizar carrito flotante
                        CarritoFlotante.actualizar(response.data.carrito);

                        // Quitar de local
                        CarritoLocal.quitar(productoId);

                        Notificacion.mostrar(response.data.message || 'Producto eliminado', 'success');
                    }
                },
                error: function() {
                    Notificacion.mostrar(GCConfig.i18n.error || 'Error de conexion', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Actualizar cantidad
         */
        actualizar: function(productoId, cantidad, $card) {
            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_actualizar_cantidad',
                    nonce: GCConfig.nonce,
                    producto_id: productoId,
                    cantidad: cantidad
                },
                success: function(response) {
                    if (response.success) {
                        CarritoFlotante.actualizar(response.data.carrito);
                        CarritoLocal.agregar(productoId, cantidad);
                    }
                }
            });
        }
    };

    /**
     * Modulo del Carrito Flotante
     */
    const CarritoFlotante = {
        $elemento: null,
        $panel: null,
        $contador: null,
        $contenido: null,
        $subtotal: null,
        $total: null,
        visible: false,

        /**
         * Inicializar carrito flotante
         */
        init: function() {
            this.$elemento = $('#gc-carrito-flotante');
            if (!this.$elemento.length) return;

            this.$panel = $('#gc-carrito-panel');
            this.$contador = $('#gc-carrito-contador');
            this.$contenido = $('#gc-carrito-contenido');
            this.$subtotal = $('#gc-carrito-subtotal');
            this.$total = $('#gc-carrito-total');

            this.vincularEventos();

            // Sincronizar al cargar si hay usuario logueado
            if (GCConfig.isLoggedIn) {
                CarritoLocal.sincronizar();
            }
        },

        /**
         * Vincular eventos
         */
        vincularEventos: function() {
            const self = this;

            // Toggle panel
            $('#gc-carrito-toggle').on('click', function() {
                self.toggle();
            });

            // Cerrar panel
            $('#gc-carrito-cerrar, #gc-carrito-overlay').on('click', function() {
                self.cerrar();
            });

            // Eliminar item
            $(document).on('click', '.flavor-gc-item-eliminar', function() {
                const $item = $(this).closest('.flavor-gc-carrito-item');
                const productoId = $item.data('producto-id');
                self.eliminarItem(productoId, $item);
            });

            // Vaciar carrito
            $('#gc-vaciar-carrito').on('click', function() {
                if (confirm(GCConfig.i18n.confirmarVaciar || 'Seguro que deseas vaciar el pedido?')) {
                    self.vaciar();
                }
            });

            // Confirmar pedido desde flotante
            $('#gc-confirmar-pedido-flotante').on('click', function() {
                self.confirmarPedido();
            });

            // Cerrar con Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.visible) {
                    self.cerrar();
                }
            });
        },

        /**
         * Toggle visibilidad
         */
        toggle: function() {
            if (this.visible) {
                this.cerrar();
            } else {
                this.abrir();
            }
        },

        /**
         * Abrir panel
         */
        abrir: function() {
            this.visible = true;
            this.$elemento.attr('data-visible', 'true');
            $('body').addClass('gc-carrito-abierto');
        },

        /**
         * Cerrar panel
         */
        cerrar: function() {
            this.visible = false;
            this.$elemento.attr('data-visible', 'false');
            $('body').removeClass('gc-carrito-abierto');
        },

        /**
         * Actualizar datos del carrito
         */
        actualizar: function(datosCarrito) {
            if (!datosCarrito) return;

            // Actualizar contador
            this.$contador.text(datosCarrito.total_items || 0);
            this.$elemento.toggleClass('tiene-items', datosCarrito.total_items > 0);

            // Actualizar totales
            if (this.$subtotal.length) {
                this.$subtotal.text(datosCarrito.subtotal_formateado || '0,00 EUR');
            }
            if ($('#gc-carrito-gestion').length && datosCarrito.gastos_gestion_formateado) {
                $('#gc-carrito-gestion').text(datosCarrito.gastos_gestion_formateado);
            }
            if (this.$total.length) {
                this.$total.text(datosCarrito.total_formateado || '0,00 EUR');
            }

            // Si hay items completos, renderizar lista
            if (datosCarrito.items) {
                this.renderizarItems(datosCarrito.items);
            }
        },

        /**
         * Renderizar items del carrito
         */
        renderizarItems: function(items) {
            if (!items.length) {
                this.$contenido.html(`
                    <div class="flavor-gc-carrito-vacio">
                        <span class="dashicons dashicons-products"></span>
                        <p>Tu pedido esta vacio</p>
                        <span class="flavor-gc-carrito-vacio-hint">Anade productos desde el catalogo</span>
                    </div>
                `);
                return;
            }

            let html = '<ul class="flavor-gc-carrito-items">';

            items.slice(0, 5).forEach(function(item) {
                const imagen = item.imagen
                    ? `<img src="${item.imagen}" alt="${item.nombre}">`
                    : '<span class="dashicons dashicons-carrot"></span>';

                html += `
                    <li class="flavor-gc-carrito-item" data-item-id="${item.id}" data-producto-id="${item.producto_id}">
                        <div class="flavor-gc-item-imagen">${imagen}</div>
                        <div class="flavor-gc-item-info">
                            <span class="flavor-gc-item-nombre">${item.nombre}</span>
                            <span class="flavor-gc-item-detalle">${item.cantidad} x ${item.precio.toFixed(2).replace('.', ',')} EUR</span>
                        </div>
                        <div class="flavor-gc-item-subtotal">${item.subtotal_formateado}</div>
                        <button type="button" class="flavor-gc-item-eliminar" data-action="eliminar-item">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </li>
                `;
            });

            html += '</ul>';

            if (items.length > 5) {
                html += `<div class="flavor-gc-carrito-mas">+ ${items.length - 5} productos mas</div>`;
            }

            this.$contenido.html(html);
        },

        /**
         * Eliminar item del carrito
         */
        eliminarItem: function(productoId, $item) {
            $item.css('opacity', '0.5');

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_quitar_producto',
                    nonce: GCConfig.nonce,
                    producto_id: productoId
                },
                success: function(response) {
                    if (response.success) {
                        $item.slideUp(200, function() {
                            $(this).remove();
                        });

                        CarritoFlotante.actualizar(response.data.carrito);
                        CarritoLocal.quitar(productoId);

                        // Actualizar tarjeta si esta visible
                        const $card = $(`.flavor-gc-producto-card[data-producto-id="${productoId}"]`);
                        if ($card.length) {
                            $card.removeClass('en-lista');
                            $card.find('.flavor-gc-btn-agregar').removeClass('en-lista')
                                .find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-cart');
                            $card.find('.flavor-gc-btn-agregar .flavor-gc-btn-texto').text('Anadir');
                            $card.find('.flavor-gc-badge-en-lista').remove();
                        }
                    }
                },
                error: function() {
                    $item.css('opacity', '1');
                    Notificacion.mostrar('Error al eliminar', 'error');
                }
            });
        },

        /**
         * Vaciar todo el carrito
         */
        vaciar: function() {
            const self = this;

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_vaciar_carrito',
                    nonce: GCConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.actualizar(response.data.carrito);
                        CarritoLocal.limpiar();

                        // Limpiar todas las tarjetas
                        $('.flavor-gc-producto-card.en-lista').removeClass('en-lista')
                            .find('.flavor-gc-btn-agregar').removeClass('en-lista')
                            .find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-cart');
                        $('.flavor-gc-badge-en-lista').remove();

                        Notificacion.mostrar(response.data.message || 'Pedido vaciado', 'success');
                    }
                }
            });
        },

        /**
         * Confirmar pedido desde carrito flotante
         */
        confirmarPedido: function() {
            // Redirigir a pagina completa del carrito para confirmar
            window.location.href = GCConfig.carritoUrl || '/mi-cuenta/?tab=gc-lista-compra';
        }
    };

    /**
     * Sistema de notificaciones
     */
    const Notificacion = {
        $actual: null,
        timeout: null,

        /**
         * Mostrar notificacion
         */
        mostrar: function(mensaje, tipo) {
            tipo = tipo || 'info';

            // Eliminar notificacion anterior
            if (this.$actual) {
                this.$actual.remove();
            }
            clearTimeout(this.timeout);

            // Crear nueva notificacion
            this.$actual = $(`
                <div class="flavor-gc-notificacion flavor-gc-notificacion-${tipo}">
                    <span class="flavor-gc-notificacion-texto">${mensaje}</span>
                    <button class="flavor-gc-notificacion-cerrar">&times;</button>
                </div>
            `);

            $('body').append(this.$actual);

            // Mostrar con animacion
            setTimeout(() => {
                this.$actual.addClass('visible');
            }, 10);

            // Auto cerrar
            this.timeout = setTimeout(() => {
                this.cerrar();
            }, 4000);

            // Cerrar manual
            this.$actual.find('.flavor-gc-notificacion-cerrar').on('click', () => {
                this.cerrar();
            });
        },

        /**
         * Cerrar notificacion
         */
        cerrar: function() {
            if (!this.$actual) return;

            this.$actual.removeClass('visible');
            setTimeout(() => {
                if (this.$actual) {
                    this.$actual.remove();
                    this.$actual = null;
                }
            }, 300);
        }
    };

    /**
     * Pagina completa del carrito
     */
    const CarritoCompleto = {
        $contenedor: null,

        /**
         * Inicializar
         */
        init: function() {
            this.$contenedor = $('.flavor-gc-carrito-completo');
            if (!this.$contenedor.length) return;

            this.vincularEventos();
        },

        /**
         * Vincular eventos
         */
        vincularEventos: function() {
            const self = this;

            // Control de cantidad
            $(document).on('click', '.flavor-gc-carrito-completo .flavor-gc-cantidad-btn', function() {
                const $fila = $(this).closest('.flavor-gc-carrito-fila');
                const $input = $fila.find('.flavor-gc-cantidad-input');
                const min = parseFloat($input.attr('min')) || 1;
                const max = parseFloat($input.attr('max')) || 999;
                let cantidad = parseFloat($input.val()) || 1;

                if ($(this).data('action') === 'incrementar') {
                    cantidad = Math.min(cantidad + 1, max);
                } else {
                    cantidad = Math.max(cantidad - 1, min);
                }

                $input.val(cantidad);
                self.actualizarCantidadFila($fila, cantidad);
            });

            // Cambio directo en input
            $(document).on('change', '.flavor-gc-carrito-completo .flavor-gc-cantidad-input', function() {
                const $fila = $(this).closest('.flavor-gc-carrito-fila');
                const min = parseFloat($(this).attr('min')) || 1;
                const max = parseFloat($(this).attr('max')) || 999;
                let cantidad = parseFloat($(this).val()) || min;

                cantidad = Math.max(min, Math.min(max, cantidad));
                $(this).val(cantidad);
                self.actualizarCantidadFila($fila, cantidad);
            });

            // Eliminar producto
            $(document).on('click', '.flavor-gc-carrito-completo .flavor-gc-btn-eliminar', function() {
                const $fila = $(this).closest('.flavor-gc-carrito-fila');
                self.eliminarFila($fila);
            });

            // Info gastos gestion
            $('#gc-info-gestion').on('click', function() {
                $('#gc-gestion-detalle').slideToggle(200);
            });

            // Vaciar pedido
            $('#gc-vaciar-pedido').on('click', function() {
                if (confirm(GCConfig.i18n.confirmarVaciar || 'Seguro que deseas vaciar el pedido?')) {
                    self.vaciar();
                }
            });

            // Confirmar pedido
            $('#gc-confirmar-pedido').on('click', function() {
                self.abrirModalConfirmacion();
            });

            // Modal confirmar
            $('#gc-modal-confirmar-btn').on('click', function() {
                self.confirmarPedido();
            });

            $('#gc-modal-cancelar, .flavor-gc-modal-cerrar, .flavor-gc-modal-overlay').on('click', function(e) {
                if ($(e.target).is(this)) {
                    self.cerrarModal();
                }
            });
        },

        /**
         * Actualizar cantidad de una fila
         */
        actualizarCantidadFila: function($fila, cantidad) {
            const itemId = $fila.data('item-id');
            const precio = parseFloat($fila.data('precio'));
            const subtotal = precio * cantidad;

            $fila.find('.flavor-gc-subtotal-valor').text(subtotal.toFixed(2).replace('.', ','));

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_actualizar_cantidad',
                    nonce: GCConfig.nonce,
                    item_id: itemId,
                    cantidad: cantidad
                },
                success: function(response) {
                    if (response.success) {
                        CarritoCompleto.recalcularTotales();
                    }
                }
            });
        },

        /**
         * Eliminar fila
         */
        eliminarFila: function($fila) {
            const productoId = $fila.data('producto-id');

            $fila.css('opacity', '0.5');

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_quitar_producto',
                    nonce: GCConfig.nonce,
                    producto_id: productoId
                },
                success: function(response) {
                    if (response.success) {
                        $fila.slideUp(200, function() {
                            $(this).remove();
                            CarritoCompleto.recalcularTotales();
                            CarritoCompleto.verificarVacio();
                        });
                    }
                },
                error: function() {
                    $fila.css('opacity', '1');
                }
            });
        },

        /**
         * Recalcular totales
         */
        recalcularTotales: function() {
            let subtotal = 0;

            this.$contenedor.find('.flavor-gc-carrito-fila').each(function() {
                const precio = parseFloat($(this).data('precio'));
                const cantidad = parseFloat($(this).find('.flavor-gc-cantidad-input').val());
                subtotal += precio * cantidad;
            });

            const porcentajeGestion = parseFloat($('#gc-gastos-gestion').closest('.flavor-gc-resumen-linea').length ? 5 : 0);
            const gastosGestion = subtotal * (porcentajeGestion / 100);
            const total = subtotal + gastosGestion;

            $('#gc-subtotal-productos').text(subtotal.toFixed(2).replace('.', ',') + ' EUR');
            if ($('#gc-gastos-gestion').length) {
                $('#gc-gastos-gestion').text(gastosGestion.toFixed(2).replace('.', ',') + ' EUR');
            }
            $('#gc-total-pedido').text(total.toFixed(2).replace('.', ',') + ' EUR');
            $('#gc-modal-total').text(total.toFixed(2).replace('.', ',') + ' EUR');
        },

        /**
         * Verificar si el carrito esta vacio
         */
        verificarVacio: function() {
            if (this.$contenedor.find('.flavor-gc-carrito-fila').length === 0) {
                location.reload();
            }
        },

        /**
         * Vaciar carrito
         */
        vaciar: function() {
            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_vaciar_carrito',
                    nonce: GCConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        },

        /**
         * Abrir modal de confirmacion
         */
        abrirModalConfirmacion: function() {
            $('#gc-modal-confirmar').fadeIn(200);
            $('body').addClass('gc-modal-abierto');
        },

        /**
         * Cerrar modal
         */
        cerrarModal: function() {
            $('.flavor-gc-modal').fadeOut(200);
            $('body').removeClass('gc-modal-abierto');
        },

        /**
         * Confirmar pedido
         */
        confirmarPedido: function() {
            const $btn = $('#gc-modal-confirmar-btn');
            $btn.prop('disabled', true);
            $btn.find('.flavor-gc-btn-texto').hide();
            $btn.find('.flavor-gc-btn-loading').show();

            $.ajax({
                url: GCConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gc_confirmar_pedido',
                    nonce: GCConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CarritoCompleto.cerrarModal();
                        $('#gc-modal-exito').fadeIn(200);

                        // Redirigir despues de 2 segundos
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || '/mi-cuenta/?tab=gc-mis-pedidos';
                        }, 2000);
                    } else {
                        Notificacion.mostrar(response.data.message || 'Error al confirmar', 'error');
                        $btn.prop('disabled', false);
                        $btn.find('.flavor-gc-btn-texto').show();
                        $btn.find('.flavor-gc-btn-loading').hide();
                    }
                },
                error: function() {
                    Notificacion.mostrar('Error de conexion', 'error');
                    $btn.prop('disabled', false);
                    $btn.find('.flavor-gc-btn-texto').show();
                    $btn.find('.flavor-gc-btn-loading').hide();
                }
            });
        }
    };

    /**
     * Inicializacion global
     */
    $(document).ready(function() {
        Catalogo.init();
        CarritoFlotante.init();
        CarritoCompleto.init();
    });

})(jQuery);
