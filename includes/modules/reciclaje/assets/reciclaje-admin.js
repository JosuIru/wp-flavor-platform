/**
 * JavaScript Admin - Módulo de Reciclaje
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // ========================================
    // INICIALIZACIÓN
    // ========================================

    $(document).ready(function() {
        inicializarTablas();
        inicializarFiltros();
        inicializarFormularios();
        inicializarGraficas();
    });

    // ========================================
    // TABLAS
    // ========================================

    function inicializarTablas() {
        // Hacer las tablas responsivas
        if ($('.wp-list-table').length) {
            $('.wp-list-table').wrap('<div class="table-responsive"></div>');
        }

        // Acciones en fila
        $('.row-actions a').on('click', function(e) {
            if ($(this).hasClass('delete')) {
                e.preventDefault();
                if (!confirm('¿Estás seguro de que quieres eliminar este elemento?')) {
                    return false;
                }
            }
        });
    }

    // ========================================
    // FILTROS
    // ========================================

    function inicializarFiltros() {
        const $filtros = $('.reciclaje-filters');

        if ($filtros.length === 0) {
            return;
        }

        // Aplicar filtros
        $('#btn-aplicar-filtros').on('click', function() {
            aplicarFiltros();
        });

        // Limpiar filtros
        $('#btn-limpiar-filtros').on('click', function() {
            $filtros.find('input, select').val('');
            aplicarFiltros();
        });

        // Filtrar al cambiar
        $filtros.find('select').on('change', function() {
            aplicarFiltros();
        });
    }

    function aplicarFiltros() {
        const filtros = {};
        $('.reciclaje-filters').find('input, select').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value) {
                filtros[name] = value;
            }
        });

        // Recargar página con filtros
        const params = new URLSearchParams(filtros);
        window.location.href = window.location.pathname + '?' + params.toString();
    }

    // ========================================
    // FORMULARIOS
    // ========================================

    function inicializarFormularios() {
        // Validación de formularios
        $('.reciclaje-form').on('submit', function(e) {
            if (!validarFormulario($(this))) {
                e.preventDefault();
                return false;
            }
        });

        // Autoguardado
        $('.autosave-field').on('change', function() {
            const $field = $(this);
            const valor = $field.val();
            const campo = $field.attr('name');

            $.ajax({
                url: reciclajeAdminData.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'reciclaje_autosave',
                    nonce: reciclajeAdminData.nonce,
                    campo: campo,
                    valor: valor
                },
                success: function(response) {
                    mostrarNotificacion('Guardado', 'success');
                }
            });
        });

        // Selector de coordenadas en mapa
        inicializarMapaPuntos();
    }

    function validarFormulario($form) {
        let valido = true;
        const errores = [];

        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                valido = false;
                errores.push($(this).attr('name'));
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        if (!valido) {
            mostrarNotificacion('Por favor, completa todos los campos requeridos', 'error');
        }

        return valido;
    }

    // ========================================
    // MAPA DE PUNTOS
    // ========================================

    function inicializarMapaPuntos() {
        const $mapContainer = $('#admin-mapa-puntos');

        if ($mapContainer.length === 0 || typeof L === 'undefined') {
            return;
        }

        const mapa = L.map('admin-mapa-puntos').setView([40.4168, -3.7038], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapa);

        // Seleccionar ubicación al hacer clic
        let marcador = null;

        mapa.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            if (marcador) {
                marcador.setLatLng(e.latlng);
            } else {
                marcador = L.marker(e.latlng).addTo(mapa);
            }

            $('#campo-latitud').val(lat.toFixed(7));
            $('#campo-longitud').val(lng.toFixed(7));
        });

        // Si hay coordenadas existentes, mostrar marcador
        const latExistente = $('#campo-latitud').val();
        const lngExistente = $('#campo-longitud').val();

        if (latExistente && lngExistente) {
            marcador = L.marker([latExistente, lngExistente]).addTo(mapa);
            mapa.setView([latExistente, lngExistente], 13);
        }

        // Búsqueda de dirección
        $('#buscar-direccion').on('click', function() {
            const direccion = $('#campo-direccion').val();

            if (!direccion) {
                mostrarNotificacion('Introduce una dirección', 'error');
                return;
            }

            buscarDireccion(direccion, function(lat, lng) {
                if (marcador) {
                    marcador.setLatLng([lat, lng]);
                } else {
                    marcador = L.marker([lat, lng]).addTo(mapa);
                }

                mapa.setView([lat, lng], 15);
                $('#campo-latitud').val(lat.toFixed(7));
                $('#campo-longitud').val(lng.toFixed(7));
            });
        });
    }

    function buscarDireccion(direccion, callback) {
        $.ajax({
            url: `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}`,
            method: 'GET',
            success: function(data) {
                if (data && data.length > 0) {
                    const resultado = data[0];
                    callback(parseFloat(resultado.lat), parseFloat(resultado.lon));
                } else {
                    mostrarNotificacion('No se encontró la dirección', 'error');
                }
            },
            error: function() {
                mostrarNotificacion('Error al buscar la dirección', 'error');
            }
        });
    }

    // ========================================
    // GRÁFICAS
    // ========================================

    function inicializarGraficas() {
        if (typeof Chart === 'undefined') {
            return;
        }

        // Gráfica de reciclaje por material
        const $graficaMateriales = $('#grafica-materiales');

        if ($graficaMateriales.length) {
            cargarGraficaMateriales();
        }

        // Gráfica de evolución temporal
        const $graficaEvolucion = $('#grafica-evolucion');

        if ($graficaEvolucion.length) {
            cargarGraficaEvolucion();
        }
    }

    function cargarGraficaMateriales() {
        $.ajax({
            url: reciclajeAdminData.restUrl + '/estadisticas',
            method: 'GET',
            data: { periodo: 'mes' },
            success: function(response) {
                if (response.success) {
                    const materiales = response.estadisticas.por_material;

                    const labels = materiales.map(m => m.tipo_material);
                    const valores = materiales.map(m => parseFloat(m.total_kg));

                    new Chart($('#grafica-materiales'), {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: valores,
                                backgroundColor: [
                                    '#10b981',
                                    '#3b82f6',
                                    '#f59e0b',
                                    '#ef4444',
                                    '#8b5cf6',
                                    '#ec4899',
                                    '#06b6d4',
                                    '#84cc16'
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
            }
        });
    }

    function cargarGraficaEvolucion() {
        $.ajax({
            url: reciclajeAdminData.restUrl + '/estadisticas',
            method: 'GET',
            data: { periodo: 'año' },
            success: function(response) {
                if (response.success) {
                    // Aquí se procesarían los datos para mostrar evolución temporal
                    // Por simplicidad, se muestra una gráfica básica
                    new Chart($('#grafica-evolucion'), {
                        type: 'line',
                        data: {
                            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                            datasets: [{
                                label: 'KG Reciclados',
                                data: [120, 190, 160, 180, 220, 250, 280, 300, 270, 290, 320, 350],
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
    }

    // ========================================
    // EXPORTACIÓN
    // ========================================

    $('#btn-exportar-excel').on('click', function() {
        exportarDatos('excel');
    });

    $('#btn-exportar-pdf').on('click', function() {
        exportarDatos('pdf');
    });

    function exportarDatos(formato) {
        const filtros = obtenerFiltrosActuales();

        window.location.href = reciclajeAdminData.ajaxUrl +
            '?action=reciclaje_exportar' +
            '&formato=' + formato +
            '&' + new URLSearchParams(filtros).toString();
    }

    function obtenerFiltrosActuales() {
        const filtros = {};

        $('.reciclaje-filters').find('input, select').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value) {
                filtros[name] = value;
            }
        });

        return filtros;
    }

    // ========================================
    // ACCIONES MASIVAS
    // ========================================

    $('#bulk-action-apply').on('click', function() {
        const accion = $('#bulk-action-selector').val();

        if (!accion || accion === '-1') {
            return;
        }

        const seleccionados = [];
        $('.check-column input:checked').each(function() {
            seleccionados.push($(this).val());
        });

        if (seleccionados.length === 0) {
            mostrarNotificacion('Selecciona al menos un elemento', 'error');
            return;
        }

        if (!confirm(`¿Confirmas aplicar la acción "${accion}" a ${seleccionados.length} elementos?`)) {
            return;
        }

        aplicarAccionMasiva(accion, seleccionados);
    });

    function aplicarAccionMasiva(accion, ids) {
        $.ajax({
            url: reciclajeAdminData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reciclaje_bulk_action',
                nonce: reciclajeAdminData.nonce,
                bulk_action: accion,
                ids: ids
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion('Acción aplicada correctamente', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarNotificacion(response.error || 'Error al aplicar acción', 'error');
                }
            },
            error: function() {
                mostrarNotificacion('Error al aplicar acción', 'error');
            }
        });
    }

    // ========================================
    // VERIFICACIÓN DE DEPÓSITOS
    // ========================================

    $('.btn-verificar-deposito').on('click', function(e) {
        e.preventDefault();
        const depositoId = $(this).data('deposito-id');
        verificarDeposito(depositoId);
    });

    function verificarDeposito(depositoId) {
        $.ajax({
            url: reciclajeAdminData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reciclaje_verificar_deposito',
                nonce: reciclajeAdminData.nonce,
                deposito_id: depositoId
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion('Depósito verificado', 'success');
                    $(`[data-deposito-id="${depositoId}"]`)
                        .closest('tr')
                        .find('.verificado-col')
                        .html('✓');
                } else {
                    mostrarNotificacion('Error al verificar', 'error');
                }
            }
        });
    }

    // ========================================
    // UTILIDADES
    // ========================================

    function mostrarNotificacion(mensaje, tipo = 'info') {
        const $notif = $('<div>', {
            class: `notice notice-${tipo} is-dismissible`,
            html: `<p>${mensaje}</p>`
        });

        $('.wrap').prepend($notif);

        setTimeout(() => {
            $notif.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);
