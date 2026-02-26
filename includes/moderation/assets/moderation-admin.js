/**
 * JavaScript del Panel de Moderación - Admin
 *
 * @package FlavorPlatform
 * @subpackage Moderation
 * @since 3.0.0
 */

(function($) {
    'use strict';

    // Namespace
    window.FlavorModeration = window.FlavorModeration || {};

    /**
     * Configuración
     */
    FlavorModeration.config = {
        ajaxUrl: flavorModerationAdmin?.ajaxUrl || ajaxurl,
        nonce: flavorModerationAdmin?.nonce || '',
        strings: flavorModerationAdmin?.strings || {}
    };

    /**
     * Estado de la aplicación
     */
    FlavorModeration.state = {
        currentTab: 'queue',
        selectedReports: [],
        isLoading: false,
        filters: {
            estado: '',
            tipo_contenido: '',
            razon: '',
            fecha_desde: '',
            fecha_hasta: '',
            buscar: ''
        },
        pagination: {
            page: 1,
            perPage: 20,
            total: 0
        }
    };

    /**
     * Inicialización
     */
    FlavorModeration.init = function() {
        this.bindEvents();
        this.initTabs();
        this.initFilters();
        this.initBulkActions();
        this.initModals();
        this.initCharts();
    };

    /**
     * Vincular eventos
     */
    FlavorModeration.bindEvents = function() {
        var self = this;

        // Tabs
        $(document).on('click', '.moderation-tab', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            self.switchTab(tab);
        });

        // Filtros
        $(document).on('change', '.moderation-filters select, .moderation-filters input', function() {
            self.updateFilters();
        });

        $(document).on('click', '.btn-apply-filters', function(e) {
            e.preventDefault();
            self.loadReports();
        });

        $(document).on('click', '.btn-reset-filters', function(e) {
            e.preventDefault();
            self.resetFilters();
        });

        // Selección de reportes
        $(document).on('change', '.select-all-reports', function() {
            var checked = $(this).prop('checked');
            $('.report-checkbox').prop('checked', checked);
            self.updateSelectedReports();
        });

        $(document).on('change', '.report-checkbox', function() {
            self.updateSelectedReports();
        });

        // Acciones individuales
        $(document).on('click', '.btn-approve-report', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.openActionModal(reportId, 'aprobar');
        });

        $(document).on('click', '.btn-reject-report', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.openActionModal(reportId, 'rechazar');
        });

        $(document).on('click', '.btn-hide-content', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.openActionModal(reportId, 'ocultar');
        });

        $(document).on('click', '.btn-delete-content', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.openActionModal(reportId, 'eliminar');
        });

        $(document).on('click', '.btn-warn-user', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.openActionModal(reportId, 'warning');
        });

        $(document).on('click', '.btn-ban-user', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.openActionModal(reportId, 'ban_temporal');
        });

        $(document).on('click', '.btn-view-report', function(e) {
            e.preventDefault();
            var reportId = $(this).closest('tr').data('report-id');
            self.viewReportDetails(reportId);
        });

        // Acciones masivas
        $(document).on('click', '.btn-bulk-approve', function(e) {
            e.preventDefault();
            self.bulkAction('aprobar');
        });

        $(document).on('click', '.btn-bulk-reject', function(e) {
            e.preventDefault();
            self.bulkAction('rechazar');
        });

        // Modal
        $(document).on('click', '.moderation-modal-close, .moderation-modal-overlay', function(e) {
            if (e.target === this) {
                self.closeModal();
            }
        });

        $(document).on('submit', '#moderation-action-form', function(e) {
            e.preventDefault();
            self.submitAction();
        });

        // Sanciones
        $(document).on('click', '.btn-remove-sanction', function(e) {
            e.preventDefault();
            var sanctionId = $(this).data('sanction-id');
            self.removeSanction(sanctionId);
        });

        $(document).on('click', '.btn-edit-sanction', function(e) {
            e.preventDefault();
            var sanctionId = $(this).data('sanction-id');
            self.editSanction(sanctionId);
        });

        // Paginación
        $(document).on('click', '.pagination-links a:not(.disabled)', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            self.goToPage(page);
        });

        // Búsqueda en tiempo real (debounced)
        var searchTimeout;
        $(document).on('input', '.filter-search', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                self.loadReports();
            }, 500);
        });

        // Teclado - ESC para cerrar modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                self.closeModal();
            }
        });
    };

    /**
     * Inicializar pestañas
     */
    FlavorModeration.initTabs = function() {
        var hash = window.location.hash.replace('#', '');
        if (hash && $('.moderation-tab[data-tab="' + hash + '"]').length) {
            this.switchTab(hash);
        } else {
            this.switchTab('queue');
        }
    };

    /**
     * Cambiar pestaña
     */
    FlavorModeration.switchTab = function(tab) {
        this.state.currentTab = tab;

        // Actualizar UI
        $('.moderation-tab').removeClass('active');
        $('.moderation-tab[data-tab="' + tab + '"]').addClass('active');

        $('.moderation-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');

        // Actualizar URL
        history.replaceState(null, null, '#' + tab);

        // Cargar datos según pestaña
        switch (tab) {
            case 'queue':
                this.state.filters.estado = 'pendiente';
                this.loadReports();
                break;
            case 'all':
                this.state.filters.estado = '';
                this.loadReports();
                break;
            case 'sanctions':
                this.loadSanctions();
                break;
            case 'history':
                this.loadHistory();
                break;
            case 'stats':
                this.loadStats();
                break;
        }
    };

    /**
     * Inicializar filtros
     */
    FlavorModeration.initFilters = function() {
        // Establecer valores iniciales desde la URL si existen
        var urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('estado')) {
            this.state.filters.estado = urlParams.get('estado');
            $('#filter-estado').val(this.state.filters.estado);
        }

        if (urlParams.has('tipo_contenido')) {
            this.state.filters.tipo_contenido = urlParams.get('tipo_contenido');
            $('#filter-tipo').val(this.state.filters.tipo_contenido);
        }
    };

    /**
     * Actualizar filtros desde formulario
     */
    FlavorModeration.updateFilters = function() {
        this.state.filters = {
            estado: $('#filter-estado').val() || '',
            tipo_contenido: $('#filter-tipo').val() || '',
            razon: $('#filter-razon').val() || '',
            fecha_desde: $('#filter-fecha-desde').val() || '',
            fecha_hasta: $('#filter-fecha-hasta').val() || '',
            buscar: $('.filter-search').val() || ''
        };
    };

    /**
     * Resetear filtros
     */
    FlavorModeration.resetFilters = function() {
        this.state.filters = {
            estado: '',
            tipo_contenido: '',
            razon: '',
            fecha_desde: '',
            fecha_hasta: '',
            buscar: ''
        };

        // Limpiar campos
        $('.moderation-filters select').val('');
        $('.moderation-filters input').val('');

        this.loadReports();
    };

    /**
     * Cargar reportes
     */
    FlavorModeration.loadReports = function() {
        var self = this;

        if (this.state.isLoading) return;
        this.state.isLoading = true;

        this.showLoading('#reports-table-container');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_get_reports',
                nonce: this.config.nonce,
                filters: this.state.filters,
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage
            },
            success: function(response) {
                if (response.success) {
                    self.renderReportsTable(response.data.reports);
                    self.renderPagination(response.data.pagination);
                    self.updateQueueCount(response.data.pending_count);
                } else {
                    self.showToast(response.data.message || 'Error al cargar reportes', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            },
            complete: function() {
                self.state.isLoading = false;
                self.hideLoading('#reports-table-container');
            }
        });
    };

    /**
     * Renderizar tabla de reportes
     */
    FlavorModeration.renderReportsTable = function(reports) {
        var $tbody = $('#reports-table tbody');
        $tbody.empty();

        if (!reports || reports.length === 0) {
            $tbody.html(
                '<tr><td colspan="8">' +
                '<div class="empty-state">' +
                '<span class="dashicons dashicons-yes-alt"></span>' +
                '<h3>No hay reportes</h3>' +
                '<p>No se encontraron reportes con los filtros seleccionados</p>' +
                '</div>' +
                '</td></tr>'
            );
            return;
        }

        var self = this;
        reports.forEach(function(report) {
            var row = self.buildReportRow(report);
            $tbody.append(row);
        });
    };

    /**
     * Construir fila de reporte
     */
    FlavorModeration.buildReportRow = function(report) {
        var statusClass = 'status-' + report.estado;
        var reasonClass = 'reason-' + report.razon;
        var priorityClass = report.num_reportes >= 5 ? 'priority-alta' :
                           (report.num_reportes >= 3 ? 'priority-media' : 'priority-baja');

        var html = '<tr data-report-id="' + report.id + '">';
        html += '<td class="checkbox-column"><input type="checkbox" class="report-checkbox" value="' + report.id + '"></td>';
        html += '<td class="id-column">#' + report.id + '</td>';
        html += '<td class="content-cell">';
        html += '<div class="content-preview">';
        html += '<span class="content-type"><span class="dashicons dashicons-' + this.getContentIcon(report.tipo_contenido) + '"></span> ' + this.getContentTypeLabel(report.tipo_contenido) + '</span>';
        html += '<div class="content-text">' + this.escapeHtml(report.contenido_preview || '(Sin contenido)') + '</div>';
        html += '<div class="content-meta"><a href="' + (report.contenido_url || '#') + '" target="_blank">Ver contenido original</a></div>';
        html += '</div>';
        html += '</td>';
        html += '<td>';
        html += '<div class="user-info">';
        html += '<img src="' + (report.autor_avatar || '') + '" class="avatar" alt="">';
        html += '<div class="user-details">';
        html += '<div class="user-name"><a href="' + (report.autor_url || '#') + '">' + this.escapeHtml(report.autor_nombre || 'Usuario') + '</a></div>';
        if (report.autor_reportes_previos > 0) {
            html += '<div class="user-reports-count">' + report.autor_reportes_previos + ' reportes previos</div>';
        }
        html += '</div></div>';
        html += '</td>';
        html += '<td><span class="reason-badge ' + reasonClass + '">' + this.getReasonLabel(report.razon) + '</span></td>';
        html += '<td>';
        html += '<span class="priority-badge ' + priorityClass + '">' + report.num_reportes + '</span>';
        html += '</td>';
        html += '<td><span class="status-badge ' + statusClass + '">' + this.getStatusLabel(report.estado) + '</span></td>';
        html += '<td class="actions-column">';
        html += '<div class="action-buttons">';

        if (report.estado === 'pendiente' || report.estado === 'en_revision') {
            html += '<button class="action-btn action-btn-success btn-approve-report" title="Aprobar"><span class="dashicons dashicons-yes"></span></button>';
            html += '<button class="action-btn action-btn-secondary btn-hide-content" title="Ocultar"><span class="dashicons dashicons-hidden"></span></button>';
            html += '<button class="action-btn action-btn-danger btn-delete-content" title="Eliminar"><span class="dashicons dashicons-trash"></span></button>';
            html += '<button class="action-btn action-btn-warning btn-warn-user" title="Avisar"><span class="dashicons dashicons-warning"></span></button>';
        }

        html += '<button class="action-btn action-btn-secondary btn-view-report" title="Ver detalles"><span class="dashicons dashicons-visibility"></span></button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';

        return html;
    };

    /**
     * Renderizar paginación
     */
    FlavorModeration.renderPagination = function(pagination) {
        if (!pagination) return;

        this.state.pagination.total = pagination.total;
        var totalPages = Math.ceil(pagination.total / this.state.pagination.perPage);
        var currentPage = this.state.pagination.page;

        var $container = $('.moderation-pagination');
        $container.empty();

        // Info
        var start = ((currentPage - 1) * this.state.pagination.perPage) + 1;
        var end = Math.min(currentPage * this.state.pagination.perPage, pagination.total);

        $container.append(
            '<div class="pagination-info">Mostrando ' + start + '-' + end + ' de ' + pagination.total + ' reportes</div>'
        );

        // Links
        var $links = $('<div class="pagination-links"></div>');

        // Primera y anterior
        if (currentPage > 1) {
            $links.append('<a href="#" data-page="1">&laquo;</a>');
            $links.append('<a href="#" data-page="' + (currentPage - 1) + '">&lsaquo;</a>');
        } else {
            $links.append('<span class="disabled">&laquo;</span>');
            $links.append('<span class="disabled">&lsaquo;</span>');
        }

        // Números de página
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        for (var i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                $links.append('<span class="current">' + i + '</span>');
            } else {
                $links.append('<a href="#" data-page="' + i + '">' + i + '</a>');
            }
        }

        // Siguiente y última
        if (currentPage < totalPages) {
            $links.append('<a href="#" data-page="' + (currentPage + 1) + '">&rsaquo;</a>');
            $links.append('<a href="#" data-page="' + totalPages + '">&raquo;</a>');
        } else {
            $links.append('<span class="disabled">&rsaquo;</span>');
            $links.append('<span class="disabled">&raquo;</span>');
        }

        $container.append($links);
    };

    /**
     * Ir a página
     */
    FlavorModeration.goToPage = function(page) {
        this.state.pagination.page = parseInt(page);
        this.loadReports();
    };

    /**
     * Actualizar contador de cola
     */
    FlavorModeration.updateQueueCount = function(count) {
        var $badge = $('.moderation-tab[data-tab="queue"] .badge');
        if (count > 0) {
            if ($badge.length) {
                $badge.text(count);
            } else {
                $('.moderation-tab[data-tab="queue"]').append('<span class="badge">' + count + '</span>');
            }
        } else {
            $badge.remove();
        }
    };

    /**
     * Actualizar reportes seleccionados
     */
    FlavorModeration.updateSelectedReports = function() {
        var selected = [];
        $('.report-checkbox:checked').each(function() {
            selected.push($(this).val());
        });

        this.state.selectedReports = selected;

        // Mostrar/ocultar acciones masivas
        if (selected.length > 0) {
            $('.bulk-actions').removeClass('hidden');
            $('.bulk-actions .selected-count').text(selected.length + ' seleccionados');
        } else {
            $('.bulk-actions').addClass('hidden');
        }

        // Actualizar checkbox de seleccionar todos
        var totalCheckboxes = $('.report-checkbox').length;
        var checkedCheckboxes = $('.report-checkbox:checked').length;

        $('.select-all-reports').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
        $('.select-all-reports').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
    };

    /**
     * Inicializar acciones masivas
     */
    FlavorModeration.initBulkActions = function() {
        // Ya vinculado en bindEvents
    };

    /**
     * Ejecutar acción masiva
     */
    FlavorModeration.bulkAction = function(action) {
        var self = this;
        var reportIds = this.state.selectedReports;

        if (reportIds.length === 0) {
            this.showToast('Selecciona al menos un reporte', 'warning');
            return;
        }

        var confirmMsg = action === 'aprobar'
            ? '¿Aprobar ' + reportIds.length + ' reportes seleccionados?'
            : '¿Rechazar ' + reportIds.length + ' reportes seleccionados?';

        if (!confirm(confirmMsg)) return;

        this.showLoading('#reports-table-container');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_bulk_action',
                nonce: this.config.nonce,
                report_ids: reportIds,
                moderation_action: action
            },
            success: function(response) {
                if (response.success) {
                    self.showToast(response.data.message || 'Acción completada', 'success');
                    self.state.selectedReports = [];
                    self.loadReports();
                } else {
                    self.showToast(response.data.message || 'Error al procesar', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            },
            complete: function() {
                self.hideLoading('#reports-table-container');
            }
        });
    };

    /**
     * Inicializar modales
     */
    FlavorModeration.initModals = function() {
        // Crear estructura del modal si no existe
        if ($('#moderation-action-modal').length === 0) {
            var modalHtml =
                '<div id="moderation-action-modal" class="moderation-modal-overlay">' +
                '<div class="moderation-modal">' +
                '<div class="moderation-modal-header">' +
                '<h3><span class="dashicons dashicons-shield"></span> <span class="modal-title">Acción de Moderación</span></h3>' +
                '<button type="button" class="moderation-modal-close">&times;</button>' +
                '</div>' +
                '<div class="moderation-modal-body">' +
                '<form id="moderation-action-form">' +
                '<input type="hidden" name="report_id" id="modal-report-id">' +
                '<input type="hidden" name="action_type" id="modal-action-type">' +
                '<div class="content-preview-box">' +
                '<div class="preview-label">Contenido reportado</div>' +
                '<div class="preview-content" id="modal-content-preview"></div>' +
                '</div>' +
                '<div class="modal-form-group">' +
                '<label for="modal-action-select">Acción a tomar</label>' +
                '<select id="modal-action-select" name="action_select" required>' +
                '<option value="">Seleccionar acción...</option>' +
                '<option value="aprobar">Aprobar (no hay violación)</option>' +
                '<option value="rechazar">Rechazar reporte</option>' +
                '<option value="ocultar">Ocultar contenido</option>' +
                '<option value="eliminar">Eliminar contenido</option>' +
                '<option value="warning">Enviar advertencia al usuario</option>' +
                '<option value="ban_temporal">Ban temporal (3 días)</option>' +
                '<option value="ban_permanente">Ban permanente</option>' +
                '</select>' +
                '</div>' +
                '<div class="modal-form-group" id="ban-duration-group" style="display:none;">' +
                '<label for="modal-ban-duration">Duración del ban (días)</label>' +
                '<input type="number" id="modal-ban-duration" name="ban_duration" min="1" max="365" value="3">' +
                '</div>' +
                '<div class="modal-form-group">' +
                '<label for="modal-notes">Notas de moderación</label>' +
                '<textarea id="modal-notes" name="notes" placeholder="Explica la razón de esta acción..."></textarea>' +
                '<div class="help-text">Estas notas son visibles solo para moderadores</div>' +
                '</div>' +
                '<div class="modal-form-group">' +
                '<label>' +
                '<input type="checkbox" name="notify_user" id="modal-notify-user" checked> ' +
                'Notificar al usuario sobre esta acción' +
                '</label>' +
                '</div>' +
                '</form>' +
                '</div>' +
                '<div class="moderation-modal-footer">' +
                '<button type="button" class="action-btn action-btn-secondary moderation-modal-close">Cancelar</button>' +
                '<button type="submit" form="moderation-action-form" class="action-btn action-btn-primary" id="modal-submit-btn">Aplicar Acción</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('body').append(modalHtml);

            // Cambiar visibilidad de duración según acción
            $('#modal-action-select').on('change', function() {
                var action = $(this).val();
                if (action === 'ban_temporal') {
                    $('#ban-duration-group').show();
                } else {
                    $('#ban-duration-group').hide();
                }
            });
        }
    };

    /**
     * Abrir modal de acción
     */
    FlavorModeration.openActionModal = function(reportId, defaultAction) {
        var self = this;

        // Cargar datos del reporte
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_get_report',
                nonce: this.config.nonce,
                report_id: reportId
            },
            success: function(response) {
                if (response.success) {
                    var report = response.data;

                    $('#modal-report-id').val(reportId);
                    $('#modal-action-type').val(defaultAction || '');
                    $('#modal-action-select').val(defaultAction || '');
                    $('#modal-content-preview').html(self.escapeHtml(report.contenido_preview || '(Sin contenido)'));
                    $('#modal-notes').val('');
                    $('#modal-notify-user').prop('checked', true);

                    // Título según acción
                    var titles = {
                        'aprobar': 'Aprobar Contenido',
                        'rechazar': 'Rechazar Reporte',
                        'ocultar': 'Ocultar Contenido',
                        'eliminar': 'Eliminar Contenido',
                        'warning': 'Enviar Advertencia',
                        'ban_temporal': 'Ban Temporal',
                        'ban_permanente': 'Ban Permanente'
                    };
                    $('.modal-title').text(titles[defaultAction] || 'Acción de Moderación');

                    // Mostrar/ocultar duración
                    if (defaultAction === 'ban_temporal') {
                        $('#ban-duration-group').show();
                    } else {
                        $('#ban-duration-group').hide();
                    }

                    $('#moderation-action-modal').addClass('active');
                } else {
                    self.showToast('Error al cargar reporte', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Cerrar modal
     */
    FlavorModeration.closeModal = function() {
        $('.moderation-modal-overlay').removeClass('active');
    };

    /**
     * Enviar acción
     */
    FlavorModeration.submitAction = function() {
        var self = this;
        var $form = $('#moderation-action-form');
        var reportId = $('#modal-report-id').val();
        var actionType = $('#modal-action-select').val();
        var notes = $('#modal-notes').val();
        var notifyUser = $('#modal-notify-user').is(':checked');
        var banDuration = $('#modal-ban-duration').val();

        if (!actionType) {
            this.showToast('Selecciona una acción', 'warning');
            return;
        }

        $('#modal-submit-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_process_report',
                nonce: this.config.nonce,
                report_id: reportId,
                moderation_action: actionType,
                notes: notes,
                notify_user: notifyUser ? 1 : 0,
                ban_duration: banDuration
            },
            success: function(response) {
                if (response.success) {
                    self.showToast(response.data.message || 'Acción completada', 'success');
                    self.closeModal();
                    self.loadReports();
                } else {
                    self.showToast(response.data.message || 'Error al procesar', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            },
            complete: function() {
                $('#modal-submit-btn').prop('disabled', false).text('Aplicar Acción');
            }
        });
    };

    /**
     * Ver detalles del reporte
     */
    FlavorModeration.viewReportDetails = function(reportId) {
        var self = this;

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_get_report_details',
                nonce: this.config.nonce,
                report_id: reportId
            },
            success: function(response) {
                if (response.success) {
                    self.showReportDetailsModal(response.data);
                } else {
                    self.showToast('Error al cargar detalles', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Mostrar modal de detalles
     */
    FlavorModeration.showReportDetailsModal = function(report) {
        // Modal simplificado para ver detalles
        var html =
            '<div class="moderation-modal-overlay active" id="report-details-modal">' +
            '<div class="moderation-modal" style="max-width: 700px;">' +
            '<div class="moderation-modal-header">' +
            '<h3><span class="dashicons dashicons-clipboard"></span> Reporte #' + report.id + '</h3>' +
            '<button type="button" class="moderation-modal-close">&times;</button>' +
            '</div>' +
            '<div class="moderation-modal-body">' +
            '<div class="content-preview-box">' +
            '<div class="preview-label">Contenido reportado</div>' +
            '<div class="preview-content">' + this.escapeHtml(report.contenido_completo || report.contenido_preview || '(Sin contenido)') + '</div>' +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">' +
            '<div>' +
            '<h4>Información del reporte</h4>' +
            '<p><strong>Tipo:</strong> ' + this.getContentTypeLabel(report.tipo_contenido) + '</p>' +
            '<p><strong>Razón:</strong> ' + this.getReasonLabel(report.razon) + '</p>' +
            '<p><strong>Estado:</strong> ' + this.getStatusLabel(report.estado) + '</p>' +
            '<p><strong>Fecha:</strong> ' + report.fecha + '</p>' +
            '<p><strong>Reportes totales:</strong> ' + report.num_reportes + '</p>' +
            '</div>' +
            '<div>' +
            '<h4>Autor del contenido</h4>' +
            '<div class="user-info">' +
            '<img src="' + (report.autor_avatar || '') + '" class="avatar" alt="">' +
            '<div class="user-details">' +
            '<div class="user-name">' + this.escapeHtml(report.autor_nombre || 'Usuario') + '</div>' +
            '<div class="user-role">' + (report.autor_email || '') + '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';

        if (report.historial && report.historial.length > 0) {
            html += '<h4 style="margin-top: 20px;">Historial de acciones</h4>';
            html += '<ul class="action-history-list">';
            report.historial.forEach(function(item) {
                html += '<li class="action-history-item">';
                html += '<div class="action-history-icon action-' + item.accion + '"><span class="dashicons dashicons-admin-generic"></span></div>';
                html += '<div class="action-history-content">';
                html += '<div class="action-history-title">' + item.accion + '</div>';
                html += '<div class="action-history-meta">Por ' + item.moderador + ' - ' + item.fecha + '</div>';
                if (item.notas) {
                    html += '<div class="action-history-reason">' + this.escapeHtml(item.notas) + '</div>';
                }
                html += '</div></li>';
            }.bind(this));
            html += '</ul>';
        }

        html += '</div>' +
            '<div class="moderation-modal-footer">' +
            '<button type="button" class="action-btn action-btn-secondary moderation-modal-close">Cerrar</button>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('#report-details-modal').remove();
        $('body').append(html);
    };

    /**
     * Cargar sanciones
     */
    FlavorModeration.loadSanctions = function() {
        var self = this;

        this.showLoading('#sanctions-container');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_get_sanctions',
                nonce: this.config.nonce,
                active_only: true
            },
            success: function(response) {
                if (response.success) {
                    self.renderSanctions(response.data);
                } else {
                    self.showToast('Error al cargar sanciones', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            },
            complete: function() {
                self.hideLoading('#sanctions-container');
            }
        });
    };

    /**
     * Renderizar sanciones
     */
    FlavorModeration.renderSanctions = function(sanctions) {
        var $container = $('#sanctions-container');
        $container.empty();

        if (!sanctions || sanctions.length === 0) {
            $container.html(
                '<div class="empty-state">' +
                '<span class="dashicons dashicons-smiley"></span>' +
                '<h3>Sin sanciones activas</h3>' +
                '<p>No hay usuarios sancionados actualmente</p>' +
                '</div>'
            );
            return;
        }

        var $grid = $('<div class="sanctioned-users-grid"></div>');

        var self = this;
        sanctions.forEach(function(sanction) {
            var cardHtml =
                '<div class="sanction-card sanction-active">' +
                '<div class="sanction-card-header">' +
                '<img src="' + (sanction.avatar || '') + '" class="avatar" alt="">' +
                '<div class="user-info">' +
                '<div class="user-name">' + self.escapeHtml(sanction.nombre || 'Usuario') + '</div>' +
                '<div class="sanction-type status-badge status-' + sanction.tipo + '">' + self.getSanctionTypeLabel(sanction.tipo) + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="sanction-card-body">' +
                '<div class="sanction-detail"><span class="label">Fecha inicio:</span><span class="value">' + sanction.fecha_inicio + '</span></div>' +
                '<div class="sanction-detail"><span class="label">Fecha fin:</span><span class="value">' + (sanction.fecha_fin || 'Permanente') + '</span></div>' +
                '<div class="sanction-detail"><span class="label">Motivo:</span><span class="value">' + self.escapeHtml(sanction.motivo || 'No especificado') + '</span></div>' +
                '<div class="sanction-detail"><span class="label">Moderador:</span><span class="value">' + self.escapeHtml(sanction.moderador || '') + '</span></div>' +
                '</div>' +
                '<div class="sanction-card-footer">' +
                '<button class="action-btn action-btn-secondary btn-edit-sanction" data-sanction-id="' + sanction.id + '"><span class="dashicons dashicons-edit"></span> Editar</button>' +
                '<button class="action-btn action-btn-success btn-remove-sanction" data-sanction-id="' + sanction.id + '"><span class="dashicons dashicons-unlock"></span> Levantar</button>' +
                '</div>' +
                '</div>';

            $grid.append(cardHtml);
        });

        $container.append($grid);
    };

    /**
     * Eliminar sanción
     */
    FlavorModeration.removeSanction = function(sanctionId) {
        var self = this;

        if (!confirm('¿Levantar esta sanción?')) return;

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_remove_sanction',
                nonce: this.config.nonce,
                sanction_id: sanctionId
            },
            success: function(response) {
                if (response.success) {
                    self.showToast('Sanción levantada', 'success');
                    self.loadSanctions();
                } else {
                    self.showToast(response.data.message || 'Error', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Cargar historial
     */
    FlavorModeration.loadHistory = function() {
        var self = this;

        this.showLoading('#history-container');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_get_history',
                nonce: this.config.nonce,
                limit: 50
            },
            success: function(response) {
                if (response.success) {
                    self.renderHistory(response.data);
                } else {
                    self.showToast('Error al cargar historial', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            },
            complete: function() {
                self.hideLoading('#history-container');
            }
        });
    };

    /**
     * Renderizar historial
     */
    FlavorModeration.renderHistory = function(history) {
        var $container = $('#history-container');
        $container.empty();

        if (!history || history.length === 0) {
            $container.html(
                '<div class="empty-state">' +
                '<span class="dashicons dashicons-clock"></span>' +
                '<h3>Sin historial</h3>' +
                '<p>No hay acciones de moderación registradas</p>' +
                '</div>'
            );
            return;
        }

        var $list = $('<ul class="action-history-list"></ul>');

        var self = this;
        history.forEach(function(item) {
            var iconClass = 'action-' + item.accion.replace('_', '-');
            var itemHtml =
                '<li class="action-history-item">' +
                '<div class="action-history-icon ' + iconClass + '"><span class="dashicons dashicons-admin-generic"></span></div>' +
                '<div class="action-history-content">' +
                '<div class="action-history-title">' + self.getActionLabel(item.accion) + '</div>' +
                '<div class="action-history-meta">' +
                'Por <strong>' + self.escapeHtml(item.moderador || 'Sistema') + '</strong> ' +
                'sobre <a href="#">' + self.escapeHtml(item.usuario_afectado || 'Usuario') + '</a> ' +
                '• ' + item.fecha +
                '</div>';

            if (item.notas) {
                itemHtml += '<div class="action-history-reason">' + self.escapeHtml(item.notas) + '</div>';
            }

            itemHtml += '</div></li>';

            $list.append(itemHtml);
        });

        $container.append($list);
    };

    /**
     * Cargar estadísticas
     */
    FlavorModeration.loadStats = function() {
        var self = this;

        this.showLoading('#stats-container');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_moderation_get_stats',
                nonce: this.config.nonce
            },
            success: function(response) {
                if (response.success) {
                    self.renderStats(response.data);
                } else {
                    self.showToast('Error al cargar estadísticas', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            },
            complete: function() {
                self.hideLoading('#stats-container');
            }
        });
    };

    /**
     * Renderizar estadísticas
     */
    FlavorModeration.renderStats = function(stats) {
        // Actualizar tarjetas de resumen
        $('#stat-pending').text(stats.pendientes || 0);
        $('#stat-resolved').text(stats.resueltos || 0);
        $('#stat-sanctions').text(stats.sanciones_activas || 0);
        $('#stat-total').text(stats.total || 0);

        // Actualizar gráficos si Chart.js está disponible
        if (typeof Chart !== 'undefined') {
            this.updateCharts(stats);
        }
    };

    /**
     * Inicializar gráficos
     */
    FlavorModeration.initCharts = function() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js no está disponible');
            return;
        }

        // Los gráficos se actualizarán cuando se carguen las estadísticas
    };

    /**
     * Actualizar gráficos
     */
    FlavorModeration.updateCharts = function(stats) {
        // Gráfico de razones
        var reasonsCtx = document.getElementById('chart-reasons');
        if (reasonsCtx) {
            if (this.reasonsChart) {
                this.reasonsChart.destroy();
            }

            this.reasonsChart = new Chart(reasonsCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(stats.por_razon || {}).map(this.getReasonLabel.bind(this)),
                    datasets: [{
                        data: Object.values(stats.por_razon || {}),
                        backgroundColor: [
                            '#ffc107', '#dc3545', '#fd7e14', '#6f42c1',
                            '#20c997', '#17a2b8', '#6c757d', '#343a40', '#e83e8c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // Gráfico temporal
        var timelineCtx = document.getElementById('chart-timeline');
        if (timelineCtx && stats.por_fecha) {
            if (this.timelineChart) {
                this.timelineChart.destroy();
            }

            this.timelineChart = new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(stats.por_fecha || {}),
                    datasets: [{
                        label: 'Reportes',
                        data: Object.values(stats.por_fecha || {}),
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    };

    /**
     * Mostrar loading
     */
    FlavorModeration.showLoading = function(selector) {
        var $container = $(selector);
        if ($container.find('.loading-overlay').length === 0) {
            $container.css('position', 'relative');
            $container.append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
        }
    };

    /**
     * Ocultar loading
     */
    FlavorModeration.hideLoading = function(selector) {
        $(selector).find('.loading-overlay').remove();
    };

    /**
     * Mostrar toast
     */
    FlavorModeration.showToast = function(message, type) {
        type = type || 'info';

        // Remover toasts anteriores
        $('.moderation-toast').remove();

        var $toast = $('<div class="moderation-toast ' + type + '">' +
            '<span class="dashicons dashicons-' + this.getToastIcon(type) + '"></span>' +
            '<span>' + message + '</span>' +
            '</div>');

        $('body').append($toast);

        setTimeout(function() {
            $toast.addClass('show');
        }, 100);

        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 4000);
    };

    /**
     * Obtener icono de toast
     */
    FlavorModeration.getToastIcon = function(type) {
        var icons = {
            'success': 'yes-alt',
            'error': 'dismiss',
            'warning': 'warning',
            'info': 'info'
        };
        return icons[type] || 'info';
    };

    /**
     * Helpers - Labels
     */
    FlavorModeration.getContentTypeLabel = function(type) {
        var labels = {
            'publicacion': 'Publicación',
            'comentario': 'Comentario',
            'tema_foro': 'Tema de foro',
            'mensaje': 'Mensaje',
            'anuncio_marketplace': 'Anuncio',
            'incidencia': 'Incidencia',
            'ayuda_vecinal': 'Ayuda vecinal',
            'propuesta_presupuesto': 'Propuesta',
            'perfil_usuario': 'Perfil'
        };
        return labels[type] || type;
    };

    FlavorModeration.getContentIcon = function(type) {
        var icons = {
            'publicacion': 'format-status',
            'comentario': 'format-chat',
            'tema_foro': 'format-quote',
            'mensaje': 'email',
            'anuncio_marketplace': 'cart',
            'incidencia': 'flag',
            'ayuda_vecinal': 'groups',
            'propuesta_presupuesto': 'money-alt',
            'perfil_usuario': 'admin-users'
        };
        return icons[type] || 'admin-post';
    };

    FlavorModeration.getReasonLabel = function(reason) {
        var labels = {
            'spam': 'Spam',
            'contenido_inapropiado': 'Contenido inapropiado',
            'acoso': 'Acoso',
            'informacion_falsa': 'Información falsa',
            'suplantacion': 'Suplantación',
            'odio': 'Discurso de odio',
            'privacidad': 'Violación privacidad',
            'ilegal': 'Contenido ilegal',
            'otro': 'Otro'
        };
        return labels[reason] || reason;
    };

    FlavorModeration.getStatusLabel = function(status) {
        var labels = {
            'pendiente': 'Pendiente',
            'en_revision': 'En revisión',
            'resuelto': 'Resuelto',
            'rechazado': 'Rechazado',
            'escalado': 'Escalado'
        };
        return labels[status] || status;
    };

    FlavorModeration.getActionLabel = function(action) {
        var labels = {
            'aprobar': 'Contenido aprobado',
            'rechazar': 'Reporte rechazado',
            'ocultar': 'Contenido ocultado',
            'restaurar': 'Contenido restaurado',
            'editar': 'Contenido editado',
            'eliminar': 'Contenido eliminado',
            'warning': 'Advertencia enviada',
            'ban_temporal': 'Ban temporal aplicado',
            'ban_permanente': 'Ban permanente aplicado',
            'desbloquear': 'Usuario desbloqueado',
            'silenciar': 'Usuario silenciado',
            'quitar_silencio': 'Silencio removido'
        };
        return labels[action] || action;
    };

    FlavorModeration.getSanctionTypeLabel = function(type) {
        var labels = {
            'warning': 'Advertencia',
            'ban_temporal': 'Ban temporal',
            'ban_permanente': 'Ban permanente',
            'silenciado': 'Silenciado'
        };
        return labels[type] || type;
    };

    /**
     * Escapar HTML
     */
    FlavorModeration.escapeHtml = function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        if ($('.flavor-moderation-wrap').length) {
            FlavorModeration.init();
        }
    });

})(jQuery);
