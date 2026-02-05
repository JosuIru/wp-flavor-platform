/**
 * JavaScript para la administración de pedidos y mesas
 */

(function($) {
    'use strict';

    const RestaurantAdmin = {
        currentPage: null,
        refreshInterval: null,

        init() {
            this.currentPage = flavorRestaurantAdmin.current_page;

            if (this.currentPage.includes('orders')) {
                this.initOrders();
            } else if (this.currentPage.includes('tables')) {
                this.initTables();
            }
        },

        /**
         * Inicializar página de pedidos
         */
        initOrders() {
            this.loadOrders();
            this.bindOrdersEvents();

            // Auto-refresh cada 30 segundos
            this.refreshInterval = setInterval(() => {
                this.loadOrders(true);
            }, 30000);
        },

        bindOrdersEvents() {
            // Filtros
            $('#filter-status, #filter-table, #filter-date').on('change', () => {
                this.loadOrders();
            });

            // Refrescar
            $('#refresh-orders').on('click', () => {
                this.loadOrders();
            });

            // Mostrar estadísticas
            $('#show-statistics').on('click', () => {
                this.showStatistics();
            });

            // Click en pedido
            $(document).on('click', '.order-card', (e) => {
                const orderId = $(e.currentTarget).data('order-id');
                this.showOrderDetails(orderId);
            });

            // Cambiar estado
            $(document).on('click', '.change-order-status', (e) => {
                e.stopPropagation();
                const orderId = $(e.currentTarget).data('order-id');
                this.changeOrderStatus(orderId);
            });

            // Cerrar modal
            $('.flavor-modal-close').on('click', () => {
                $('.flavor-modal').fadeOut(300);
            });

            // Click fuera del modal
            $('.flavor-modal').on('click', (e) => {
                if ($(e.target).hasClass('flavor-modal')) {
                    $('.flavor-modal').fadeOut(300);
                }
            });
        },

        loadOrders(silent = false) {
            const $list = $('#orders-list');
            const $noOrders = $('#no-orders');

            if (!silent) {
                $list.html('<div class="loading-indicator"><span class="spinner is-active"></span> Cargando pedidos...</div>');
            }

            const data = {
                action: 'get_restaurant_orders',
                nonce: flavorRestaurantAdmin.nonce,
                status: $('#filter-status').val(),
                table_id: $('#filter-table').val(),
                date: $('#filter-date').val()
            };

            $.post(flavorRestaurantAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.renderOrders(response.data.orders);
                        this.updateOrdersStats(response.data.orders);
                    }
                })
                .fail(() => {
                    $list.html('<div class="notice notice-error"><p>Error al cargar pedidos</p></div>');
                });
        },

        renderOrders(orders) {
            const $list = $('#orders-list');
            const $noOrders = $('#no-orders');

            if (!orders || orders.length === 0) {
                $list.empty();
                $noOrders.fadeIn(300);
                return;
            }

            $noOrders.hide();

            let html = '';
            orders.forEach(order => {
                html += this.getOrderCardHTML(order);
            });

            $list.html(html);
        },

        getOrderCardHTML(order) {
            const itemsCount = order.items ? order.items.length : 0;
            const tableInfo = order.table ? `Mesa ${order.table.table_number}` : 'Sin mesa';

            return `
                <div class="order-card" data-order-id="${order.id}">
                    <div class="order-header">
                        <span class="order-number">#${order.order_number}</span>
                        <span class="order-status ${order.status}">${order.status_label}</span>
                    </div>

                    <div class="order-details">
                        <div class="order-detail-row">
                            <span class="dashicons dashicons-admin-home"></span>
                            <span>${tableInfo}</span>
                        </div>
                        ${order.customer.name ? `
                        <div class="order-detail-row">
                            <span class="dashicons dashicons-admin-users"></span>
                            <span>${order.customer.name}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="order-items">
                        ${itemsCount} ${itemsCount === 1 ? 'item' : 'items'}
                    </div>

                    <div class="order-footer">
                        <span class="order-total">${order.total_formatted}</span>
                        <span class="order-time">${this.timeAgo(order.created_at)}</span>
                    </div>
                </div>
            `;
        },

        updateOrdersStats(orders) {
            let pending = 0;
            let preparing = 0;
            let ready = 0;
            let revenue = 0;

            orders.forEach(order => {
                if (order.status === 'pending') pending++;
                if (order.status === 'preparing') preparing++;
                if (order.status === 'ready') ready++;
                if (order.status === 'completed') revenue += parseFloat(order.total);
            });

            $('#stat-pending').text(pending);
            $('#stat-preparing').text(preparing);
            $('#stat-ready').text(ready);
            $('#stat-revenue').text('€ ' + revenue.toFixed(2));
        },

        showOrderDetails(orderId) {
            const $modal = $('#order-details-modal');
            const $content = $('#order-details-content');

            $modal.fadeIn(300);
            $content.html('<div class="loading-indicator"><span class="spinner is-active"></span> Cargando detalles...</div>');

            $.post(flavorRestaurantAdmin.ajax_url, {
                action: 'get_order_details',
                nonce: flavorRestaurantAdmin.nonce,
                order_id: orderId
            })
                .done((response) => {
                    if (response.success) {
                        this.renderOrderDetails(response.data.order, response.data.history);
                    }
                })
                .fail(() => {
                    $content.html('<p>Error al cargar detalles del pedido</p>');
                });
        },

        renderOrderDetails(order, history) {
            const $content = $('#order-details-content');
            const $title = $('#modal-order-title');

            $title.text(`Pedido #${order.order_number}`);

            let html = `
                <div class="order-detail-section">
                    <h3>Estado: <span class="order-status ${order.status}">${order.status_label}</span></h3>

                    <div class="order-actions" style="margin: 15px 0;">
                        <button class="button change-order-status" data-order-id="${order.id}">
                            Cambiar Estado
                        </button>
                    </div>
                </div>

                <div class="order-detail-section">
                    <h3>Información del Cliente</h3>
                    <table class="widefat">
                        <tr>
                            <th>Nombre:</th>
                            <td>${order.customer.name || '-'}</td>
                        </tr>
                        <tr>
                            <th>Teléfono:</th>
                            <td>${order.customer.phone || '-'}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>${order.customer.email || '-'}</td>
                        </tr>
                        <tr>
                            <th>Mesa:</th>
                            <td>${order.table ? order.table.table_name : 'Sin mesa'}</td>
                        </tr>
                    </table>
                </div>

                <div class="order-detail-section">
                    <h3>Items del Pedido</h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (order.items) {
                order.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.name}${item.notes ? `<br><small>${item.notes}</small>` : ''}</td>
                            <td>${item.quantity}</td>
                            <td>€ ${item.unit_price.toFixed(2)}</td>
                            <td>€ ${item.subtotal.toFixed(2)}</td>
                        </tr>
                    `;
                });
            }

            html += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Subtotal:</th>
                                <td>€ ${order.subtotal.toFixed(2)}</td>
                            </tr>
                            <tr>
                                <th colspan="3">IVA:</th>
                                <td>€ ${order.tax.toFixed(2)}</td>
                            </tr>
                            <tr>
                                <th colspan="3"><strong>Total:</strong></th>
                                <td><strong>€ ${order.total.toFixed(2)}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                ${order.notes ? `
                <div class="order-detail-section">
                    <h3>Notas</h3>
                    <p>${order.notes}</p>
                </div>
                ` : ''}
            `;

            $content.html(html);
        },

        changeOrderStatus(orderId) {
            const newStatus = prompt('Nuevo estado (pending, preparing, ready, served, completed, cancelled):');

            if (!newStatus) return;

            const notes = prompt('Notas opcionales:');

            $.post(flavorRestaurantAdmin.ajax_url, {
                action: 'update_order_status',
                nonce: flavorRestaurantAdmin.nonce,
                order_id: orderId,
                status: newStatus,
                notes: notes || ''
            })
                .done((response) => {
                    if (response.success) {
                        alert('Estado actualizado correctamente');
                        this.loadOrders();
                        $('.flavor-modal').fadeOut(300);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                })
                .fail(() => {
                    alert('Error al actualizar el estado');
                });
        },

        /**
         * Inicializar página de mesas
         */
        initTables() {
            this.loadTables();
            this.bindTablesEvents();
        },

        bindTablesEvents() {
            $('#add-table, #add-first-table').on('click', () => {
                this.showTableForm();
            });

            $('#refresh-tables').on('click', () => {
                this.loadTables();
            });

            $('#save-table').on('click', () => {
                this.saveTable();
            });

            $(document).on('click', '.edit-table', (e) => {
                e.stopPropagation();
                const tableId = $(e.currentTarget).data('table-id');
                this.editTable(tableId);
            });

            $(document).on('click', '.delete-table', (e) => {
                e.stopPropagation();
                const tableId = $(e.currentTarget).data('table-id');
                this.deleteTable(tableId);
            });
        },

        loadTables() {
            const $list = $('#tables-list');

            $list.html('<div class="loading-indicator"><span class="spinner is-active"></span> Cargando mesas...</div>');

            $.post(flavorRestaurantAdmin.ajax_url, {
                action: 'get_tables_list',
                nonce: flavorRestaurantAdmin.nonce
            })
                .done((response) => {
                    if (response.success) {
                        this.renderTables(response.data.tables);
                        this.updateTablesStats(response.data.statistics);
                    }
                })
                .fail(() => {
                    $list.html('<div class="notice notice-error"><p>Error al cargar mesas</p></div>');
                });
        },

        renderTables(tables) {
            const $list = $('#tables-list');
            const $noTables = $('#no-tables');

            if (!tables || tables.length === 0) {
                $list.empty();
                $noTables.fadeIn(300);
                return;
            }

            $noTables.hide();

            let html = '';
            tables.forEach(table => {
                html += this.getTableCardHTML(table);
            });

            $list.html(html);
        },

        getTableCardHTML(table) {
            return `
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-number">${table.table_name}</div>
                        <span class="table-status ${table.status}">${table.status_label}</span>
                    </div>

                    <div class="table-info">
                        <div>Número: ${table.table_number}</div>
                        <div>Capacidad: ${table.capacity} personas</div>
                        ${table.location ? `<div>Ubicación: ${table.location}</div>` : ''}
                    </div>

                    <div class="table-actions">
                        <button class="button button-small edit-table" data-table-id="${table.id}">
                            <span class="dashicons dashicons-edit"></span> Editar
                        </button>
                        <button class="button button-small delete-table" data-table-id="${table.id}">
                            <span class="dashicons dashicons-trash"></span> Eliminar
                        </button>
                    </div>
                </div>
            `;
        },

        updateTablesStats(stats) {
            $('#stat-tables-available').text(stats.available || 0);
            $('#stat-tables-occupied').text(stats.occupied || 0);
            $('#stat-tables-reserved').text(stats.reserved || 0);
            $('#stat-tables-total').text(stats.total || 0);
        },

        showTableForm(tableData = null) {
            const $modal = $('#table-form-modal');
            const $form = $('#table-form');
            const $title = $('#modal-table-title');

            // Limpiar formulario
            $form[0].reset();
            $('#table-id').val('');

            if (tableData) {
                $title.text('Editar Mesa');
                $('#table-id').val(tableData.id);
                $('#table-number').val(tableData.table_number);
                $('#table-name').val(tableData.table_name);
                $('#table-capacity').val(tableData.capacity);
                $('#table-location').val(tableData.location);
                $('#table-notes').val(tableData.notes);
            } else {
                $title.text('Nueva Mesa');
            }

            $modal.fadeIn(300);
        },

        editTable(tableId) {
            // Buscar datos de la mesa
            $.post(flavorRestaurantAdmin.ajax_url, {
                action: 'get_tables_list',
                nonce: flavorRestaurantAdmin.nonce
            })
                .done((response) => {
                    if (response.success) {
                        const table = response.data.tables.find(t => t.id === tableId);
                        if (table) {
                            this.showTableForm(table);
                        }
                    }
                });
        },

        saveTable() {
            const tableId = $('#table-id').val();
            const isEdit = tableId !== '';

            const data = {
                action: isEdit ? 'update_table' : 'create_table',
                nonce: flavorRestaurantAdmin.nonce,
                table_number: $('#table-number').val(),
                table_name: $('#table-name').val(),
                capacity: $('#table-capacity').val(),
                location: $('#table-location').val(),
                notes: $('#table-notes').val()
            };

            if (isEdit) {
                data.table_id = tableId;
            }

            $.post(flavorRestaurantAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        alert(response.data.message);
                        this.loadTables();
                        $('.flavor-modal').fadeOut(300);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                })
                .fail(() => {
                    alert('Error al guardar la mesa');
                });
        },

        deleteTable(tableId) {
            if (!confirm('¿Estás seguro de eliminar esta mesa?')) {
                return;
            }

            $.post(flavorRestaurantAdmin.ajax_url, {
                action: 'delete_table',
                nonce: flavorRestaurantAdmin.nonce,
                table_id: tableId
            })
                .done((response) => {
                    if (response.success) {
                        this.loadTables();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                })
                .fail(() => {
                    alert('Error al eliminar la mesa');
                });
        },

        /**
         * Utilidades
         */
        timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);

            if (minutes < 1) return 'Ahora mismo';
            if (minutes === 1) return 'Hace 1 minuto';
            if (minutes < 60) return `Hace ${minutes} minutos`;

            const hours = Math.floor(minutes / 60);
            if (hours === 1) return 'Hace 1 hora';
            if (hours < 24) return `Hace ${hours} horas`;

            const days = Math.floor(hours / 24);
            if (days === 1) return 'Ayer';
            return `Hace ${days} días`;
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        RestaurantAdmin.init();
    });

    // Limpiar interval al salir
    $(window).on('beforeunload', () => {
        if (RestaurantAdmin.refreshInterval) {
            clearInterval(RestaurantAdmin.refreshInterval);
        }
    });

})(jQuery);
