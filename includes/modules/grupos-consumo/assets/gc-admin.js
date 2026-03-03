/**
 * JavaScript Admin para Grupos de Consumo
 */
(function($) {
    'use strict';

    const gcAdmin = window.gcAdmin || {};

    /**
     * Inicialización
     */
    function init() {
        bindEventos();
        initDataTables();
        initCharts();
    }

    /**
     * Vincular eventos
     */
    function bindEventos() {
        // Consumidores
        $(document).on('click', '.gc-btn-cambiar-estado', cambiarEstadoConsumidor);
        $(document).on('click', '.gc-btn-cambiar-rol', cambiarRolConsumidor);
        $(document).on('submit', '#gc-form-nuevo-consumidor', agregarConsumidor);

        // Suscripciones
        $(document).on('click', '.gc-btn-ver-historial', verHistorialSuscripcion);
        $(document).on('submit', '#gc-form-nueva-cesta', guardarTipoCesta);

        // Consolidado
        $(document).on('click', '.gc-btn-enviar-productores', enviarConsolidadoProductores);
        $(document).on('click', '.gc-btn-regenerar-consolidado', regenerarConsolidado);

        // Exportación
        $(document).on('click', '.gc-btn-exportar', manejarExportacion);

        // Modales
        $(document).on('click', '.gc-modal-close, .gc-modal-overlay', cerrarModal);
        $(document).on('click', '[data-modal]', abrirModal);

        // Filtros
        $(document).on('change', '.gc-filtro-select', aplicarFiltros);
        $(document).on('input', '.gc-filtro-buscar', debounce(aplicarFiltros, 300));

        // Acciones masivas
        $(document).on('click', '#gc-seleccionar-todos', seleccionarTodos);
        $(document).on('click', '.gc-btn-accion-masiva', ejecutarAccionMasiva);

        // Tabs
        $(document).on('click', '.gc-tab-link', cambiarTab);
    }

    /**
     * Inicializar tablas con búsqueda y ordenación
     */
    function initDataTables() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.gc-datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [[0, 'desc']]
            });
        }
    }

    /**
     * Inicializar gráficos
     */
    function initCharts() {
        // Los gráficos se inicializan en cada vista específica
    }

    function mostrarAviso(mensaje, tipo) {
        tipo = tipo || 'error';
        let contenedor = $('.gc-admin-notice-container');
        if (!contenedor.length) {
            contenedor = $('<div class="gc-admin-notice-container"></div>').appendTo('body');
        }

        const aviso = $('<div class="gc-admin-notice gc-admin-notice-' + tipo + '"></div>').text(mensaje);
        contenedor.append(aviso);

        setTimeout(function() {
            aviso.addClass('is-visible');
        }, 10);

        setTimeout(function() {
            aviso.removeClass('is-visible');
            setTimeout(function() {
                aviso.remove();
            }, 200);
        }, 3000);
    }

    function solicitarConfirmacion(mensaje, onConfirm) {
        let contenedor = $('.gc-admin-notice-container');
        if (!contenedor.length) {
            contenedor = $('<div class="gc-admin-notice-container"></div>').appendTo('body');
        }

        $('.gc-admin-confirm').remove();
        const aviso = $('<div class="gc-admin-notice gc-admin-notice-error gc-admin-confirm"><div class="gc-admin-confirm-texto"></div><div class="gc-admin-confirm-actions"><button type="button" class="button button-primary gc-confirmar">Confirmar</button><button type="button" class="button gc-cancelar">Cancelar</button></div></div>');
        aviso.find('.gc-admin-confirm-texto').text(mensaje);
        contenedor.append(aviso);
        setTimeout(function() { aviso.addClass('is-visible'); }, 10);
        aviso.on('click', '.gc-confirmar', function() {
            aviso.remove();
            onConfirm();
        });
        aviso.on('click', '.gc-cancelar', function() {
            aviso.remove();
        });
    }

    /**
     * Cambiar estado de consumidor
     */
    function cambiarEstadoConsumidor(e) {
        e.preventDefault();
        const $btn = $(this);
        const consumidorId = $btn.data('consumidor');
        const nuevoEstado = $btn.data('estado');

        solicitarConfirmacion('¿Cambiar estado a ' + nuevoEstado + '?', function() {
        $btn.addClass('loading');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_cambiar_estado_consumidor',
                nonce: gcAdmin.nonce,
                consumidor_id: consumidorId,
                estado: nuevoEstado
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    mostrarAviso(response.data.message || 'Error al cambiar estado', 'error');
                }
            },
            error: function() {
                mostrarAviso('Error de conexión', 'error');
            },
            complete: function() {
                $btn.removeClass('loading');
            }
        });
        });
    }

    /**
     * Cambiar rol de consumidor
     */
    function cambiarRolConsumidor(e) {
        e.preventDefault();
        const $btn = $(this);
        const consumidorId = $btn.data('consumidor');
        const nuevoRol = $btn.data('rol');

        solicitarConfirmacion('¿Cambiar rol a ' + nuevoRol + '?', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_cambiar_rol_consumidor',
                nonce: gcAdmin.nonce,
                consumidor_id: consumidorId,
                rol: nuevoRol
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    mostrarAviso(response.data.message || 'Error al cambiar rol', 'error');
                }
            }
        });
        });
    }

    /**
     * Agregar nuevo consumidor
     */
    function agregarConsumidor(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');

        $btn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $form.serialize() + '&action=gc_alta_consumidor&nonce=' + gcAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    cerrarModal();
                    location.reload();
                } else {
                    mostrarAviso(response.data.message || 'Error al agregar consumidor', 'error');
                }
            },
            error: function() {
                mostrarAviso('Error de conexión', 'error');
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    }

    /**
     * Ver historial de suscripción
     */
    function verHistorialSuscripcion(e) {
        e.preventDefault();
        const suscripcionId = $(this).data('suscripcion');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_obtener_historial_suscripcion',
                nonce: gcAdmin.nonce,
                suscripcion_id: suscripcionId
            },
            success: function(response) {
                if (response.success) {
                    mostrarModalHistorial(response.data);
                }
            }
        });
    }

    /**
     * Mostrar modal con historial
     */
    function mostrarModalHistorial(datos) {
        let html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr><th>Fecha</th><th>Importe</th><th>Estado</th></tr></thead><tbody>';

        datos.forEach(function(item) {
            html += `<tr>
                <td>${item.fecha}</td>
                <td>${item.importe}€</td>
                <td>${item.estado}</td>
            </tr>`;
        });

        html += '</tbody></table>';

        abrirModalConContenido('Historial de Suscripción', html);
    }

    /**
     * Guardar tipo de cesta
     */
    function guardarTipoCesta(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');

        $btn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $form.serialize() + '&action=gc_guardar_cesta_tipo&nonce=' + gcAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    cerrarModal();
                    location.reload();
                } else {
                    mostrarAviso(response.data.message || 'Error al guardar cesta', 'error');
                }
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    }

    /**
     * Enviar consolidado a productores
     */
    function enviarConsolidadoProductores(e) {
        e.preventDefault();
        const cicloId = $(this).data('ciclo');

        solicitarConfirmacion('¿Enviar el consolidado a todos los productores?', function() {
        const $btn = $(e.currentTarget).addClass('loading');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_enviar_consolidado_productores',
                nonce: gcAdmin.nonce,
                ciclo_id: cicloId
            },
            success: function(response) {
                if (response.success) {
                    mostrarAviso('Consolidado enviado correctamente a ' + response.data.enviados + ' productores', 'success');
                } else {
                    mostrarAviso(response.data.message || 'Error al enviar', 'error');
                }
            },
            complete: function() {
                $btn.removeClass('loading');
            }
        });
        });
    }

    /**
     * Regenerar consolidado
     */
    function regenerarConsolidado(e) {
        e.preventDefault();
        const cicloId = $(this).data('ciclo');

        solicitarConfirmacion('¿Regenerar el consolidado? Esto reemplazará los datos actuales.', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_regenerar_consolidado',
                nonce: gcAdmin.nonce,
                ciclo_id: cicloId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    mostrarAviso(response.data.message || 'Error al regenerar', 'error');
                }
            }
        });
        });
    }

    /**
     * Manejar exportación
     */
    function manejarExportacion(e) {
        e.preventDefault();
        const tipo = $(this).data('tipo');
        const formato = $(this).data('formato') || 'excel';

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_generar_exportacion',
                nonce: gcAdmin.nonce,
                tipo: tipo,
                formato: formato,
                params: obtenerFiltrosActuales()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.url;
                } else {
                    mostrarAviso(response.data.message || 'Error al exportar', 'error');
                }
            }
        });
    }

    /**
     * Obtener filtros actuales
     */
    function obtenerFiltrosActuales() {
        const params = {};
        $('.gc-filtro-select, .gc-filtro-input').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value) {
                params[name] = value;
            }
        });
        return params;
    }

    /**
     * Aplicar filtros
     */
    function aplicarFiltros() {
        const params = obtenerFiltrosActuales();
        const url = new URL(window.location.href);

        Object.keys(params).forEach(function(key) {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });

        window.location.href = url.toString();
    }

    /**
     * Seleccionar/deseleccionar todos
     */
    function seleccionarTodos(e) {
        const checked = $(this).prop('checked');
        $('.gc-checkbox-item').prop('checked', checked);
        actualizarBotonesAccionMasiva();
    }

    /**
     * Actualizar botones de acción masiva
     */
    function actualizarBotonesAccionMasiva() {
        const seleccionados = $('.gc-checkbox-item:checked').length;
        $('.gc-btn-accion-masiva').prop('disabled', seleccionados === 0);
        $('.gc-seleccionados-count').text(seleccionados);
    }

    /**
     * Ejecutar acción masiva
     */
    function ejecutarAccionMasiva(e) {
        e.preventDefault();
        const accion = $(this).data('accion');
        const ids = [];

        $('.gc-checkbox-item:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            mostrarAviso('Selecciona al menos un elemento', 'error');
            return;
        }

        solicitarConfirmacion('¿Ejecutar acción "' + accion + '" en ' + ids.length + ' elementos?', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gc_accion_masiva',
                nonce: gcAdmin.nonce,
                accion: accion,
                ids: ids
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    mostrarAviso(response.data.message || 'Error al ejecutar acción', 'error');
                }
            }
        });
        });
    }

    /**
     * Cambiar tab
     */
    function cambiarTab(e) {
        e.preventDefault();
        const tab = $(this).data('tab');

        $('.gc-tab-link').removeClass('active');
        $(this).addClass('active');

        $('.gc-tab-content').removeClass('active');
        $('#gc-tab-' + tab).addClass('active');
    }

    /**
     * Abrir modal
     */
    function abrirModal(e) {
        e.preventDefault();
        const modalId = $(this).data('modal');
        $('#' + modalId).addClass('active');
        $('body').addClass('gc-modal-open');
    }

    /**
     * Abrir modal con contenido dinámico
     */
    function abrirModalConContenido(titulo, contenido) {
        const modal = `
            <div class="gc-modal-overlay active" id="gc-modal-dinamico">
                <div class="gc-modal">
                    <div class="gc-modal-header">
                        <h2>${titulo}</h2>
                        <button class="gc-modal-close">&times;</button>
                    </div>
                    <div class="gc-modal-body">
                        ${contenido}
                    </div>
                </div>
            </div>
        `;

        $('body').append(modal).addClass('gc-modal-open');
    }

    /**
     * Cerrar modal
     */
    function cerrarModal(e) {
        if (e) {
            if ($(e.target).hasClass('gc-modal-overlay') || $(e.target).hasClass('gc-modal-close')) {
                $('.gc-modal-overlay').removeClass('active');
                $('body').removeClass('gc-modal-open');
                $('#gc-modal-dinamico').remove();
            }
        } else {
            $('.gc-modal-overlay').removeClass('active');
            $('body').removeClass('gc-modal-open');
            $('#gc-modal-dinamico').remove();
        }
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

    // Inicializar
    $(document).ready(init);

    // Actualizar checkboxes
    $(document).on('change', '.gc-checkbox-item', actualizarBotonesAccionMasiva);

})(jQuery);

(function() {
    const css = [
        '.gc-admin-notice-container{position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:360px}',
        '.gc-admin-notice{opacity:0;transform:translateY(-6px);transition:all .2s ease;padding:12px 14px;border-radius:10px;box-shadow:0 10px 24px rgba(0,0,0,.12);font-size:14px}',
        '.gc-admin-notice.is-visible{opacity:1;transform:translateY(0)}',
        '.gc-admin-notice-error{background:#fee2e2;color:#991b1b}',
        '.gc-admin-notice-success{background:#dcfce7;color:#166534}'
    ].join('');
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
})();
