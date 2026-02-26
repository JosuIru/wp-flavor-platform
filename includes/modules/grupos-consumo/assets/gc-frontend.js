/**
 * JavaScript Frontend para Grupos de Consumo
 */
(function($) {
    'use strict';

    // Configuración global
    const config = window.gcFrontend || {};

    // Estado local
    let listaCompra = {};

    /**
     * Inicialización
     */
    function init() {
        bindEventos();
        cargarEstadoLista();
    }

    /**
     * Vincular eventos
     */
    function bindEventos() {
        // Catálogo de productos
        $(document).on('click', '.gc-btn-agregar-lista', agregarProducto);
        $(document).on('click', '.gc-btn-cantidad', cambiarCantidadProducto);
        $(document).on('change', '.gc-cantidad-input', actualizarCantidadInput);

        // Filtros del catálogo
        $('#gc-buscar-producto').on('input', debounce(filtrarProductos, 300));
        $('#gc-filtrar-productor').on('change', filtrarProductos);
        $('#gc-ordenar-productos').on('change', ordenarProductos);

        // Carrito/Lista
        $(document).on('click', '.gc-btn-item-mas', incrementarItem);
        $(document).on('click', '.gc-btn-item-menos', decrementarItem);
        $(document).on('click', '.gc-btn-item-eliminar', eliminarItem);
        $(document).on('click', '#gc-convertir-pedido', convertirEnPedido);

        // Suscripciones
        $(document).on('click', '.gc-btn-suscribirse', mostrarModalSuscripcion);
        $(document).on('click', '.gc-btn-pausar-suscripcion', pausarSuscripcion);
        $(document).on('click', '.gc-btn-reanudar-suscripcion', reanudarSuscripcion);
        $(document).on('click', '.gc-btn-cancelar-suscripcion', cancelarSuscripcion);

        // Pedidos
        $(document).on('click', '.gc-btn-ver-detalle', verDetallePedido);

        // Botón añadir producto al pedido (shortcode simple)
        $(document).on('click', '.gc-anadir-pedido', anadirProductoPedido);

        // === Eventos del Modal (delegados a document para máxima compatibilidad) ===
        // Cerrar modal
        $(document).on('click', '.gc-modal-close, .gc-modal-cancelar', function(e) {
            e.preventDefault();
            $(this).closest('.gc-modal-overlay').remove();
        });

        // Cerrar al hacer clic en el overlay
        $(document).on('click', '.gc-modal-overlay', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });

        // Botón menos (-)
        $(document).on('click', '.gc-btn-modal-menos', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $control = $(this).closest('.gc-cantidad-control-modal');
            const $input = $control.find('input[type="number"]');
            const valorActual = parseInt($input.val()) || 1;
            $input.val(Math.max(1, valorActual - 2));
        });

        // Botón más (+)
        $(document).on('click', '.gc-btn-modal-mas', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $control = $(this).closest('.gc-cantidad-control-modal');
            const $input = $control.find('input[type="number"]');
            const valorActual = parseInt($input.val()) || 1;
            $input.val(Math.min(99, valorActual + 2));
        });

        // Enviar formulario del modal
        $(document).on('submit', '.gc-form-anadir', enviarFormularioModal);
    }

    /**
     * Añadir producto al pedido (desde shortcode simple)
     */
    function anadirProductoPedido(e) {
        e.preventDefault();

        const $btn = $(this);
        const productoId = $btn.data('producto-id');

        if (!productoId) {
            mostrarNotificacion('Error: No se encontró el producto', 'error');
            return;
        }

        // Cerrar cualquier modal existente
        $('.gc-modal-overlay').remove();

        // Mostrar modal para seleccionar cantidad
        const modalHtml = `
            <div class="gc-modal-overlay" id="modal-anadir-producto">
                <div class="gc-modal-content">
                    <button type="button" class="gc-modal-close">&times;</button>
                    <h3>Añadir al pedido</h3>
                    <form class="gc-form-anadir" data-producto-id="${productoId}">
                        <div class="gc-form-group">
                            <label for="gc-cantidad">Cantidad</label>
                            <div class="gc-cantidad-control-modal">
                                <button type="button" class="gc-btn-modal-menos">−</button>
                                <input type="number" id="gc-cantidad" name="cantidad" value="1" min="1" max="99" required>
                                <button type="button" class="gc-btn-modal-mas">+</button>
                            </div>
                        </div>
                        <div class="gc-form-actions">
                            <button type="submit" class="gc-btn gc-btn-primary">Confirmar</button>
                            <button type="button" class="gc-btn gc-btn-secondary gc-modal-cancelar">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    }

    /**
     * Enviar formulario del modal
     */
    function enviarFormularioModal(e) {
        e.preventDefault();

        const $form = $(this);
        const productoId = $form.data('producto-id');
        const cantidad = $form.find('input[name="cantidad"]').val();
        const $submitBtn = $form.find('button[type="submit"]');

        const textoOriginal = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Añadiendo...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gc_agregar_lista',
                nonce: config.nonce,
                producto_id: productoId,
                cantidad: cantidad
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion(response.data.message || '¡Producto añadido al pedido!', 'success');
                    $form.closest('.gc-modal-overlay').remove();

                    // Actualizar visual del botón
                    $(`.gc-anadir-pedido[data-producto-id="${productoId}"]`)
                        .addClass('gc-en-pedido')
                        .text('✓ En pedido');
                } else {
                    mostrarNotificacion(response.data.message || 'Error al añadir el producto', 'error');
                    $submitBtn.prop('disabled', false).text(textoOriginal);
                }
            },
            error: function() {
                mostrarNotificacion('Error de conexión. Intenta de nuevo.', 'error');
                $submitBtn.prop('disabled', false).text(textoOriginal);
            }
        });
    }

    /**
     * Cargar estado de la lista de compra
     */
    function cargarEstadoLista() {
        if (!config.isLoggedIn) return;

        // La lista ya viene marcada desde PHP, pero podemos actualizar vía REST si necesario
    }

    /**
     * Agregar producto a la lista
     */
    function agregarProducto(e) {
        e.preventDefault();

        if (!config.isLoggedIn) {
            window.location.href = config.loginUrl;
            return;
        }

        const $btn = $(this);
        const $card = $btn.closest('.gc-producto-card');
        const productoId = $card.data('producto-id');
        const cantidad = parseInt($card.find('.gc-cantidad-input').val()) || 1;

        if ($btn.hasClass('en-lista')) {
            // Quitar de la lista
            quitarDeLista(productoId, $card);
        } else {
            // Agregar a la lista
            agregarALista(productoId, cantidad, $card);
        }
    }

    /**
     * Agregar a la lista (AJAX)
     */
    function agregarALista(productoId, cantidad, $card) {
        const $btn = $card.find('.gc-btn-agregar-lista');
        $btn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_agregar_lista',
                nonce: config.nonce,
                producto_id: productoId,
                cantidad: cantidad
            },
            success: function(response) {
                if (response.success) {
                    $card.addClass('en-lista');
                    $btn.addClass('en-lista')
                        .find('.dashicons').removeClass('dashicons-cart').addClass('dashicons-yes');
                    $btn.find('.gc-btn-texto').text(config.i18n.agregado || 'En lista');

                    mostrarNotificacion(config.i18n.agregado, 'success');
                    actualizarContadorCarrito(1);
                } else {
                    mostrarNotificacion(response.data.message || config.i18n.error, 'error');
                }
            },
            error: function() {
                mostrarNotificacion(config.i18n.error, 'error');
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    }

    /**
     * Quitar de la lista (AJAX)
     */
    function quitarDeLista(productoId, $card) {
        const $btn = $card.find('.gc-btn-agregar-lista');
        $btn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_quitar_lista',
                nonce: config.nonce,
                producto_id: productoId
            },
            success: function(response) {
                if (response.success) {
                    $card.removeClass('en-lista');
                    $btn.removeClass('en-lista')
                        .find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-cart');
                    $btn.find('.gc-btn-texto').text('Agregar');

                    mostrarNotificacion(config.i18n.eliminado, 'success');
                    actualizarContadorCarrito(-1);
                }
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    }

    /**
     * Cambiar cantidad en producto
     */
    function cambiarCantidadProducto(e) {
        const $btn = $(this);
        const $card = $btn.closest('.gc-producto-card');
        const $input = $card.find('.gc-cantidad-input');
        let cantidad = parseInt($input.val()) || 1;

        if ($btn.data('action') === 'mas') {
            cantidad = Math.min(cantidad + 1, 99);
        } else {
            cantidad = Math.max(cantidad - 1, 1);
        }

        $input.val(cantidad);

        // Si ya está en lista, actualizar
        if ($card.hasClass('en-lista')) {
            actualizarCantidadEnLista($card.data('producto-id'), cantidad);
        }
    }

    /**
     * Actualizar cantidad desde input
     */
    function actualizarCantidadInput(e) {
        const $input = $(this);
        const $card = $input.closest('.gc-producto-card');
        let cantidad = parseInt($input.val()) || 1;
        cantidad = Math.max(1, Math.min(99, cantidad));
        $input.val(cantidad);

        if ($card.hasClass('en-lista')) {
            actualizarCantidadEnLista($card.data('producto-id'), cantidad);
        }
    }

    /**
     * Actualizar cantidad en lista
     */
    function actualizarCantidadEnLista(productoId, cantidad) {
        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_agregar_lista',
                nonce: config.nonce,
                producto_id: productoId,
                cantidad: cantidad
            }
        });
    }

    /**
     * Incrementar item del carrito
     */
    function incrementarItem(e) {
        const $item = $(this).closest('.gc-carrito-item');
        const itemId = $item.data('item-id');
        let cantidad = parseInt($item.find('.gc-item-qty').text()) + 1;

        actualizarItemCarrito(itemId, cantidad, $item);
    }

    /**
     * Decrementar item del carrito
     */
    function decrementarItem(e) {
        const $item = $(this).closest('.gc-carrito-item');
        const itemId = $item.data('item-id');
        let cantidad = parseInt($item.find('.gc-item-qty').text()) - 1;

        if (cantidad < 1) {
            if (confirm(config.i18n.confirmarEliminar)) {
                eliminarItemCarrito(itemId, $item);
            }
            return;
        }

        actualizarItemCarrito(itemId, cantidad, $item);
    }

    /**
     * Eliminar item del carrito
     */
    function eliminarItem(e) {
        const $item = $(this).closest('.gc-carrito-item');
        const productoId = $item.data('producto-id');

        if (!confirm(config.i18n.confirmarEliminar)) return;

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_quitar_lista',
                nonce: config.nonce,
                producto_id: productoId
            },
            success: function(response) {
                if (response.success) {
                    $item.slideUp(200, function() {
                        $(this).remove();
                        recalcularTotalCarrito();
                        actualizarContadorCarrito(-1);

                        // Actualizar card si está visible
                        $(`.gc-producto-card[data-producto-id="${productoId}"]`).removeClass('en-lista')
                            .find('.gc-btn-agregar-lista').removeClass('en-lista')
                            .find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-cart');
                    });
                }
            }
        });
    }

    /**
     * Actualizar item del carrito
     */
    function actualizarItemCarrito(itemId, cantidad, $item) {
        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_actualizar_cantidad',
                nonce: config.nonce,
                item_id: itemId,
                cantidad: cantidad
            },
            success: function(response) {
                if (response.success) {
                    $item.find('.gc-item-qty').text(cantidad);
                    recalcularTotalCarrito();
                }
            }
        });
    }

    /**
     * Eliminar item del carrito
     */
    function eliminarItemCarrito(itemId, $item) {
        const productoId = $item.data('producto-id');

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_quitar_lista',
                nonce: config.nonce,
                producto_id: productoId
            },
            success: function(response) {
                if (response.success) {
                    $item.slideUp(200, function() {
                        $(this).remove();
                        recalcularTotalCarrito();
                    });
                }
            }
        });
    }

    /**
     * Recalcular total del carrito
     */
    function recalcularTotalCarrito() {
        let total = 0;

        $('.gc-carrito-item').each(function() {
            const precio = parseFloat($(this).find('.gc-item-precio').text().replace('€', '').replace(',', '.'));
            const cantidad = parseInt($(this).find('.gc-item-qty').text());
            const subtotal = precio * cantidad;
            $(this).find('.gc-item-subtotal').text(formatearPrecio(subtotal) + '€');
            total += subtotal;
        });

        $('#gc-total-carrito').text(formatearPrecio(total) + '€');

        // Si no hay items, mostrar mensaje
        if ($('.gc-carrito-item').length === 0) {
            $('.gc-carrito-items').html('<p class="gc-carrito-vacio">' + (config.i18n.sinProductos || 'Tu lista está vacía') + '</p>');
            $('.gc-carrito-footer').hide();
        }
    }

    /**
     * Actualizar contador del carrito
     */
    function actualizarContadorCarrito(delta) {
        const $count = $('.gc-carrito-count');
        let count = parseInt($count.text()) || 0;
        count += delta;
        $count.text(Math.max(0, count));
    }

    /**
     * Convertir lista en pedido
     */
    function convertirEnPedido(e) {
        e.preventDefault();

        const $btn = $(this);
        $btn.addClass('loading').prop('disabled', true).text(config.i18n.cargando || 'Procesando...');

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gc_convertir_pedido',
                nonce: config.nonce
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion(response.data.message || config.i18n.pedidoCreado, 'success');

                    // Limpiar carrito visual
                    $('.gc-carrito-items').html('<p class="gc-carrito-vacio">' + (config.i18n.sinProductos || 'Tu lista está vacía') + '</p>');
                    $('.gc-carrito-footer').hide();
                    $('.gc-carrito-count').text('0');

                    // Limpiar productos marcados
                    $('.gc-producto-card.en-lista').removeClass('en-lista')
                        .find('.gc-btn-agregar-lista').removeClass('en-lista')
                        .find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-cart');

                    // Redirigir a pedidos
                    setTimeout(function() {
                        window.location.href = '/mi-cuenta/?tab=gc-mis-pedidos';
                    }, 1500);
                } else {
                    mostrarNotificacion(response.data.message || config.i18n.error, 'error');
                }
            },
            error: function() {
                mostrarNotificacion(config.i18n.error, 'error');
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false).text('Convertir en Pedido');
            }
        });
    }

    /**
     * Filtrar productos
     */
    function filtrarProductos() {
        const busqueda = $('#gc-buscar-producto').val().toLowerCase();
        const productor = $('#gc-filtrar-productor').val();

        $('.gc-producto-card').each(function() {
            const $card = $(this);
            const nombre = $card.data('nombre').toLowerCase();
            const cardProductor = $card.find('.gc-producto-productor').text();

            let mostrar = true;

            if (busqueda && nombre.indexOf(busqueda) === -1) {
                mostrar = false;
            }

            if (productor && cardProductor.indexOf(productor) === -1) {
                // Necesitaríamos el ID del productor en el data attr para un filtro preciso
            }

            $card.toggle(mostrar);
        });
    }

    /**
     * Ordenar productos
     */
    function ordenarProductos() {
        const orden = $('#gc-ordenar-productos').val();
        const $container = $('#gc-productos-lista');
        const $cards = $container.find('.gc-producto-card').get();

        $cards.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);

            switch (orden) {
                case 'nombre':
                    return $a.data('nombre').localeCompare($b.data('nombre'));
                case 'nombre-desc':
                    return $b.data('nombre').localeCompare($a.data('nombre'));
                case 'precio':
                    return parseFloat($a.data('precio')) - parseFloat($b.data('precio'));
                case 'precio-desc':
                    return parseFloat($b.data('precio')) - parseFloat($a.data('precio'));
                default:
                    return 0;
            }
        });

        $.each($cards, function(idx, card) {
            $container.append(card);
        });
    }

    /**
     * Mostrar modal de suscripción
     */
    function mostrarModalSuscripcion(e) {
        e.preventDefault();
        const cesta = $(this).data('cesta');

        // Crear modal simple
        const modal = `
            <div class="gc-modal-overlay" id="gc-modal-suscripcion">
                <div class="gc-modal">
                    <button class="gc-modal-cerrar">&times;</button>
                    <h3>Suscribirse a cesta</h3>
                    <form id="gc-form-suscripcion">
                        <input type="hidden" name="cesta" value="${cesta}">
                        <div class="gc-form-field">
                            <label>Frecuencia</label>
                            <select name="frecuencia">
                                <option value="semanal">Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <button type="submit" class="gc-btn-confirmar-suscripcion">Confirmar suscripción</button>
                    </form>
                </div>
            </div>
        `;

        $('body').append(modal);

        // Cerrar modal
        $(document).on('click', '.gc-modal-cerrar, .gc-modal-overlay', function(e) {
            if (e.target === this) {
                $('#gc-modal-suscripcion').remove();
            }
        });

        // Enviar formulario
        $('#gc-form-suscripcion').on('submit', function(e) {
            e.preventDefault();
            crearSuscripcion($(this).serialize());
        });
    }

    /**
     * Crear suscripción
     */
    function crearSuscripcion(formData) {
        $.ajax({
            url: config.restUrl + 'suscripciones',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.restNonce },
            data: formData,
            success: function(response) {
                $('#gc-modal-suscripcion').remove();
                mostrarNotificacion('Suscripción creada correctamente', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || config.i18n.error;
                mostrarNotificacion(msg, 'error');
            }
        });
    }

    /**
     * Pausar suscripción
     */
    function pausarSuscripcion(e) {
        e.preventDefault();
        const id = $(this).data('suscripcion');

        $.ajax({
            url: config.restUrl + 'suscripciones/' + id + '/pausar',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.restNonce },
            success: function() {
                mostrarNotificacion('Suscripción pausada', 'success');
                location.reload();
            }
        });
    }

    /**
     * Reanudar suscripción
     */
    function reanudarSuscripcion(e) {
        e.preventDefault();
        const id = $(this).data('suscripcion');

        $.ajax({
            url: config.restUrl + 'suscripciones/' + id + '/reanudar',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.restNonce },
            success: function() {
                mostrarNotificacion('Suscripción reanudada', 'success');
                location.reload();
            }
        });
    }

    /**
     * Cancelar suscripción
     */
    function cancelarSuscripcion(e) {
        e.preventDefault();

        if (!confirm('¿Estás seguro de que quieres cancelar tu suscripción?')) {
            return;
        }

        const id = $(this).data('suscripcion');

        $.ajax({
            url: config.restUrl + 'suscripciones/' + id + '/cancelar',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.restNonce },
            success: function() {
                mostrarNotificacion('Suscripción cancelada', 'success');
                location.reload();
            }
        });
    }

    /**
     * Ver detalle de pedido
     */
    function verDetallePedido(e) {
        e.preventDefault();
        const pedidoId = $(this).data('pedido');

        // Cargar detalle via AJAX o expandir
        const $card = $(this).closest('.gc-pedido-card');
        $card.toggleClass('expandido');
    }

    /**
     * Mostrar notificación
     */
    function mostrarNotificacion(mensaje, tipo) {
        // Eliminar notificaciones anteriores
        $('.gc-notificacion').remove();

        const $notif = $(`
            <div class="gc-notificacion gc-notificacion-${tipo}">
                <span class="gc-notificacion-texto">${mensaje}</span>
                <button class="gc-notificacion-cerrar">&times;</button>
            </div>
        `);

        $('body').append($notif);

        // Mostrar con animación
        setTimeout(function() {
            $notif.addClass('visible');
        }, 10);

        // Auto cerrar
        setTimeout(function() {
            $notif.removeClass('visible');
            setTimeout(function() {
                $notif.remove();
            }, 300);
        }, 3000);

        // Cerrar manual
        $notif.find('.gc-notificacion-cerrar').on('click', function() {
            $notif.removeClass('visible');
            setTimeout(function() {
                $notif.remove();
            }, 300);
        });
    }

    /**
     * Formatear precio
     */
    function formatearPrecio(precio) {
        return precio.toFixed(2).replace('.', ',');
    }

    /**
     * Debounce
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(init);

})(jQuery);
