/**
 * Multimedia Admin JavaScript
 * Flavor Chat IA - Panel de Administración
 */

(function($) {
    'use strict';

    const MMAdmin = {
        ajaxurl: typeof flavorMMAdmin !== 'undefined' ? flavorMMAdmin.ajaxurl : ajaxurl,
        nonce: typeof flavorMMAdmin !== 'undefined' ? flavorMMAdmin.nonce : '',
    };

    $(document).ready(function() {
        MMAdmin.init();
    });

    MMAdmin.init = function() {
        this.bindEvents();
        this.loadStats();
        this.loadPendientes();
    };

    MMAdmin.bindEvents = function() {
        const self = this;

        // Tabs
        $(document).on('click', '.mm-admin-tab', function() {
            const tab = $(this).data('tab');
            $('.mm-admin-tab').removeClass('active');
            $(this).addClass('active');
            $('.mm-admin-panel').hide();
            $(`#mm-panel-${tab}`).show();

            // Cargar contenido según tab
            switch (tab) {
                case 'moderacion':
                    self.loadPendientes();
                    break;
                case 'reportes':
                    self.loadReportes();
                    break;
                case 'archivos':
                    self.loadArchivos();
                    break;
            }
        });

        // Moderar
        $(document).on('click', '.mm-btn-aprobar', function() {
            const archivoId = $(this).data('id');
            self.moderar(archivoId, 'aprobar');
        });

        $(document).on('click', '.mm-btn-rechazar', function() {
            const archivoId = $(this).data('id');
            self.showConfirm('¿Rechazar este archivo?', function() {
                self.moderar(archivoId, 'rechazar');
            });
        });

        // Destacar
        $(document).on('click', '.mm-btn-destacar', function() {
            const archivoId = $(this).data('id');
            self.toggleDestacar(archivoId, $(this));
        });

        // Eliminar
        $(document).on('click', '.mm-btn-eliminar', function() {
            const archivoId = $(this).data('id');
            self.showConfirm('¿Eliminar este archivo permanentemente?', function() {
                self.eliminarArchivo(archivoId);
            });
        });

        // Resolver reporte
        $(document).on('click', '.mm-btn-resolver-reporte', function() {
            const reporteId = $(this).data('id');
            self.resolverReporte(reporteId);
        });
    };

    MMAdmin.loadStats = function() {
        const self = this;
        const container = $('#mm-stats-container');

        if (!container.length) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_admin_stats',
                nonce: this.nonce,
            },
            success: function(response) {
                if (response.success) {
                    self.renderStats(response.data);
                }
            }
        });
    };

    MMAdmin.renderStats = function(data) {
        // Stats grid
        $('#mm-stat-total').text(data.total_archivos.toLocaleString());
        $('#mm-stat-imagenes').text(data.imagenes.toLocaleString());
        $('#mm-stat-videos').text(data.videos.toLocaleString());
        $('#mm-stat-albumes').text(data.total_albumes.toLocaleString());
        $('#mm-stat-pendientes').text(data.pendientes_moderacion.toLocaleString());
        $('#mm-stat-reportes').text(data.reportes_pendientes.toLocaleString());
        $('#mm-stat-vistas').text(data.total_vistas.toLocaleString());
        $('#mm-stat-likes').text(data.total_likes.toLocaleString());

        // Espacio usado
        const espacioMB = (data.espacio_usado_bytes / (1024 * 1024)).toFixed(2);
        $('#mm-stat-espacio').text(espacioMB + ' MB');

        // Gráfico por mes
        if (data.por_mes && data.por_mes.length) {
            this.renderChart(data.por_mes);
        }

        // Top archivos
        if (data.top_archivos && data.top_archivos.length) {
            this.renderTopArchivos(data.top_archivos);
        }
    };

    MMAdmin.renderChart = function(datos) {
        const container = $('#mm-chart-mensual');
        if (!container.length) return;

        const maxVal = Math.max(...datos.map(d => d.total));
        let html = '';

        datos.forEach(function(item) {
            const height = maxVal > 0 ? (item.total / maxVal * 100) : 0;
            const mes = item.mes.split('-')[1]; // Solo el mes
            html += `
                <div class="mm-chart-bar" style="height: ${height}%">
                    <span class="mm-chart-bar-value">${item.total}</span>
                    <span class="mm-chart-bar-label">${mes}</span>
                </div>
            `;
        });

        container.find('.mm-chart-bars').html(html);
    };

    MMAdmin.renderTopArchivos = function(archivos) {
        const container = $('#mm-top-archivos');
        if (!container.length) return;

        let html = '';
        archivos.forEach(function(archivo) {
            html += `
                <li class="mm-top-item">
                    <img src="${archivo.thumbnail_url || ''}" class="mm-top-thumb" alt="">
                    <div class="mm-top-info">
                        <div class="mm-top-titulo">${archivo.titulo || 'Sin título'}</div>
                        <div class="mm-top-stats">
                            <span>${archivo.vistas} vistas</span> ·
                            <span>${archivo.me_gusta} likes</span>
                        </div>
                    </div>
                </li>
            `;
        });

        container.html(html);
    };

    MMAdmin.loadPendientes = function() {
        const self = this;
        const container = $('#mm-moderacion-list');

        if (!container.length) return;

        container.html('<div class="mm-admin-loading"><span class="spinner is-active"></span><p>Cargando...</p></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_mm_galeria',
                estado: 'pendiente',
                limite: 50,
            },
            success: function(response) {
                if (response.success && response.data.archivos && response.data.archivos.length) {
                    self.renderPendientes(container, response.data.archivos);
                } else {
                    container.html('<div class="mm-admin-empty"><span class="dashicons dashicons-yes-alt"></span><h3>Sin pendientes</h3><p>No hay archivos pendientes de moderación</p></div>');
                }
            }
        });
    };

    MMAdmin.renderPendientes = function(container, archivos) {
        let html = '';
        archivos.forEach(function(archivo) {
            html += `
                <div class="mm-moderacion-card pendiente" data-id="${archivo.id}">
                    <div class="mm-moderacion-preview">
                        <img src="${archivo.thumbnail}" alt="">
                    </div>
                    <div class="mm-moderacion-info">
                        <div class="mm-moderacion-titulo">${archivo.titulo || 'Sin título'}</div>
                        <div class="mm-moderacion-meta">
                            Por: ${archivo.autor.nombre} · ${archivo.fecha_humana}
                        </div>
                        <div class="mm-moderacion-acciones">
                            <button class="button button-primary mm-btn-aprobar" data-id="${archivo.id}">Aprobar</button>
                            <button class="button mm-btn-rechazar" data-id="${archivo.id}">Rechazar</button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.html('<div class="mm-moderacion-panel">' + html + '</div>');
    };

    MMAdmin.moderar = function(archivoId, accion) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_admin_moderar',
                nonce: this.nonce,
                archivo_id: archivoId,
                accion_moderacion: accion,
            },
            success: function(response) {
                if (response.success) {
                    $(`.mm-moderacion-card[data-id="${archivoId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        // Actualizar contador
                        const count = parseInt($('#mm-stat-pendientes').text()) - 1;
                        $('#mm-stat-pendientes').text(Math.max(0, count));
                    });
                    MMAdmin.showToast(accion === 'aprobar' ? 'Archivo aprobado' : 'Archivo rechazado', 'success');
                } else {
                    MMAdmin.showToast(response.data || 'Error', 'error');
                }
            }
        });
    };

    MMAdmin.toggleDestacar = function(archivoId, button) {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_admin_destacar',
                nonce: this.nonce,
                archivo_id: archivoId,
            },
            success: function(response) {
                if (response.success) {
                    button.toggleClass('destacado', response.data.destacado);
                    button.text(response.data.destacado ? 'Quitar destacado' : 'Destacar');
                }
            }
        });
    };

    MMAdmin.loadReportes = function() {
        const self = this;
        const container = $('#mm-reportes-list');

        if (!container.length) return;

        container.html('<div class="mm-admin-loading"><span class="spinner is-active"></span><p>Cargando...</p></div>');

        // Simular carga - implementar endpoint real
        setTimeout(function() {
            container.html('<div class="mm-admin-empty"><span class="dashicons dashicons-flag"></span><h3>Sin reportes</h3><p>No hay reportes pendientes</p></div>');
        }, 500);
    };

    MMAdmin.loadArchivos = function() {
        const self = this;
        const container = $('#mm-archivos-list');

        if (!container.length) return;

        container.html('<div class="mm-admin-loading"><span class="spinner is-active"></span><p>Cargando...</p></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_mm_galeria',
                limite: 50,
            },
            success: function(response) {
                if (response.success && response.data.archivos && response.data.archivos.length) {
                    self.renderArchivosTable(container, response.data.archivos);
                } else {
                    container.html('<div class="mm-admin-empty"><p>No hay archivos</p></div>');
                }
            }
        });
    };

    MMAdmin.renderArchivosTable = function(container, archivos) {
        let html = `
            <table class="mm-admin-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="mm-thumbnail-cell"></th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Autor</th>
                        <th>Estado</th>
                        <th>Vistas</th>
                        <th>Likes</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;

        archivos.forEach(function(archivo) {
            html += `
                <tr>
                    <td class="mm-thumbnail-cell">
                        <img src="${archivo.thumbnail}" alt="">
                    </td>
                    <td><strong>${archivo.titulo || 'Sin título'}</strong></td>
                    <td>${archivo.tipo}</td>
                    <td>${archivo.autor.nombre}</td>
                    <td><span class="mm-estado-badge ${archivo.estado}">${archivo.estado}</span></td>
                    <td>${archivo.vistas}</td>
                    <td>${archivo.me_gusta}</td>
                    <td>${archivo.fecha_humana}</td>
                    <td class="mm-acciones-cell">
                        <button class="button button-small mm-btn-destacar ${archivo.destacado ? 'destacado' : ''}" data-id="${archivo.id}">
                            ${archivo.destacado ? 'Quitar' : 'Destacar'}
                        </button>
                        <button class="button button-small mm-btn-eliminar" data-id="${archivo.id}">Eliminar</button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.html(html);
    };

    MMAdmin.eliminarArchivo = function(archivoId) {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_mm_eliminar',
                nonce: this.nonce,
                archivo_id: archivoId,
            },
            success: function(response) {
                if (response.success) {
                    $(`tr:has([data-id="${archivoId}"])`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    MMAdmin.showToast('Archivo eliminado', 'success');
                } else {
                    MMAdmin.showToast(response.data || 'Error al eliminar', 'error');
                }
            }
        });
    };

    MMAdmin.resolverReporte = function(reporteId) {
        // Implementar
        console.log('Resolver reporte:', reporteId);
    };

    MMAdmin.showToast = function(message, type) {
        type = type || 'info';

        if (!$('.mm-admin-toast-container').length) {
            $('body').append('<div class="mm-admin-toast-container"></div>');
        }

        const toast = $('<div class="mm-admin-toast ' + type + '">' + message + '</div>');
        $('.mm-admin-toast-container').append(toast);

        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    };

    MMAdmin.showConfirm = function(message, onConfirm) {
        if (!$('.mm-admin-toast-container').length) {
            $('body').append('<div class="mm-admin-toast-container"></div>');
        }

        const toast = $(
            '<div class="mm-admin-toast info">' +
                '<div class="mm-admin-confirm-text"></div>' +
                '<div class="mm-admin-confirm-actions" style="margin-top:10px;display:flex;gap:8px;">' +
                    '<button type="button" class="mm-admin-confirm-btn mm-admin-confirm-btn--primary" style="border:0;border-radius:8px;padding:8px 12px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;">Confirmar</button>' +
                    '<button type="button" class="mm-admin-confirm-btn mm-admin-confirm-btn--secondary" style="border:0;border-radius:8px;padding:8px 12px;background:#e5e7eb;color:#111827;font-weight:600;cursor:pointer;">Cancelar</button>' +
                '</div>' +
            '</div>'
        );

        toast.find('.mm-admin-confirm-text').text(message);
        $('.mm-admin-toast-container').append(toast);

        toast.find('.mm-admin-confirm-btn--primary').on('click', function() {
            toast.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        toast.find('.mm-admin-confirm-btn--secondary').on('click', function() {
            toast.remove();
        });
    };

    // Exponer
    window.MMAdmin = MMAdmin;

})(jQuery);
