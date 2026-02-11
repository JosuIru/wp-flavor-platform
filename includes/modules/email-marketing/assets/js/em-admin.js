/**
 * Email Marketing - Admin JavaScript
 */

(function($) {
    'use strict';

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);

        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    function apiCall(endpoint, method, data) {
        return $.ajax({
            url: flavorEM.ajax_url.replace('admin-ajax.php', 'wp-json/flavor/v1/' + endpoint),
            type: method || 'GET',
            contentType: 'application/json',
            data: data ? JSON.stringify(data) : null,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpApiSettings?.nonce || '');
            }
        });
    }

    // =========================================================================
    // MODALES
    // =========================================================================

    function openModal(modalId) {
        $('#' + modalId).fadeIn(200);
    }

    function closeModal() {
        $('.em-modal').fadeOut(200);
    }

    $(document).on('click', '.em-modal-close, .em-modal-cancelar', function(e) {
        e.preventDefault();
        closeModal();
    });

    $(document).on('click', '.em-modal', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // =========================================================================
    // CAMPAÑAS
    // =========================================================================

    // Guardar campaña
    $('#em-form-campania').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const campaniaId = $form.find('input[name="campania_id"]').val();

        const data = {
            nombre: $form.find('#em-nombre').val(),
            asunto: $form.find('#em-asunto').val(),
            preview_text: $form.find('#em-preview-text').val(),
            contenido_html: tinymce.get('em_contenido_html') ?
                tinymce.get('em_contenido_html').getContent() :
                $form.find('#em_contenido_html').val(),
            listas_ids: [],
            remitente_nombre: $form.find('#em-remitente-nombre').val(),
            remitente_email: $form.find('#em-remitente-email').val(),
        };

        $form.find('input[name="listas_ids[]"]:checked').each(function() {
            data.listas_ids.push(parseInt($(this).val()));
        });

        const endpoint = campaniaId ?
            'em/admin/campanias/' + campaniaId :
            'em/admin/campanias';
        const method = campaniaId ? 'PUT' : 'POST';

        apiCall(endpoint, method, data)
            .done(function(response) {
                if (response.success) {
                    showNotice(flavorEM.strings.saved, 'success');

                    if (!campaniaId && response.campania_id) {
                        // Redirigir a edición
                        window.location.href = window.location.href.replace('action=new', 'action=edit&id=' + response.campania_id);
                    }
                } else {
                    showNotice(response.error || flavorEM.strings.error, 'error');
                }
            })
            .fail(function() {
                showNotice(flavorEM.strings.error, 'error');
            });
    });

    // Enviar test
    $('.em-btn-test').on('click', function() {
        const email = prompt('Introduce el email de prueba:', '');

        if (!email) return;

        const campaniaId = $('input[name="campania_id"]').val();

        if (!campaniaId) {
            alert('Guarda la campaña primero');
            return;
        }

        $(this).prop('disabled', true).text(flavorEM.strings.sending);

        apiCall('em/admin/campanias/' + campaniaId + '/test', 'POST', { email: email })
            .done(function(response) {
                if (response.success) {
                    showNotice(flavorEM.strings.sent, 'success');
                } else {
                    showNotice(response.error || flavorEM.strings.error, 'error');
                }
            })
            .always(function() {
                $('.em-btn-test').prop('disabled', false).html('<span class="dashicons dashicons-email"></span> Enviar test');
            });
    });

    // Enviar campaña
    $('.em-btn-enviar').on('click', function() {
        if (!confirm('¿Estás seguro de que quieres enviar esta campaña ahora?')) {
            return;
        }

        const campaniaId = $('input[name="campania_id"]').val();

        if (!campaniaId) {
            alert('Guarda la campaña primero');
            return;
        }

        $(this).prop('disabled', true).text(flavorEM.strings.sending);

        apiCall('em/admin/campanias/' + campaniaId + '/enviar', 'POST', {})
            .done(function(response) {
                if (response.success) {
                    showNotice(response.mensaje || 'Campaña enviándose', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotice(response.error || flavorEM.strings.error, 'error');
                    $('.em-btn-enviar').prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Enviar campaña');
                }
            })
            .fail(function() {
                showNotice(flavorEM.strings.error, 'error');
                $('.em-btn-enviar').prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Enviar campaña');
            });
    });

    // Programar campaña
    $('.em-btn-programar').on('click', function() {
        openModal('em-modal-programar');
    });

    $('#em-form-programar').on('submit', function(e) {
        e.preventDefault();

        const campaniaId = $('input[name="campania_id"]').val();
        const fechaProgramada = $('#em-fecha-programada').val();

        apiCall('em/admin/campanias/' + campaniaId + '/enviar', 'POST', {
            fecha_programada: fechaProgramada
        })
            .done(function(response) {
                if (response.success) {
                    showNotice(response.mensaje || 'Campaña programada', 'success');
                    closeModal();
                    setTimeout(function() {
                        window.location.href = window.location.href.replace(/&action=\w+/, '').replace(/&id=\d+/, '');
                    }, 1500);
                } else {
                    showNotice(response.error, 'error');
                }
            });
    });

    // Calcular total destinatarios
    function calcularDestinatarios() {
        let total = 0;
        $('input[name="listas_ids[]"]:checked').each(function() {
            const count = $(this).siblings('.em-lista-count').text();
            total += parseInt(count.replace(/[^\d]/g, '')) || 0;
        });
        $('#em-total-dest').text(total.toLocaleString());
    }

    $('input[name="listas_ids[]"]').on('change', calcularDestinatarios);
    calcularDestinatarios();

    // =========================================================================
    // LISTAS
    // =========================================================================

    // Nueva lista
    $('.em-btn-nueva-lista').on('click', function() {
        $('#em-modal-lista-titulo').text('Nueva lista');
        $('#em-form-lista')[0].reset();
        $('#em-lista-id').val('');
        openModal('em-modal-lista');
    });

    // Editar lista
    $('.em-btn-editar-lista').on('click', function() {
        const $card = $(this).closest('.em-lista-card');
        const id = $card.data('id');

        $('#em-modal-lista-titulo').text('Editar lista');
        $('#em-lista-id').val(id);
        $('#em-lista-nombre').val($card.data('nombre') || '');
        $('#em-lista-descripcion').val($card.data('descripcion') || '');
        $('#em-lista-tipo').val($card.data('tipo') || 'newsletter');
        $('#em-lista-doble-optin').prop('checked', !!$card.data('dobleOptin'));
        openModal('em-modal-lista');
    });

    // Guardar lista
    $('#em-form-lista').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const listaId = $('#em-lista-id').val();

        const data = {
            nombre: $('#em-lista-nombre').val(),
            descripcion: $('#em-lista-descripcion').val(),
            tipo: $('#em-lista-tipo').val(),
            doble_optin: $('#em-lista-doble-optin').is(':checked'),
        };

        const endpoint = listaId ?
            'em/admin/listas/' + listaId :
            'em/admin/listas';
        const method = listaId ? 'PUT' : 'POST';

        apiCall(endpoint, method, data)
            .done(function(response) {
                if (response.success) {
                    showNotice(flavorEM.strings.saved, 'success');
                    closeModal();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotice(response.error, 'error');
                }
            });
    });

    // Copiar shortcode
    $('.em-copiar-shortcode').on('click', function() {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function() {
            showNotice('Shortcode copiado', 'success');
        });
    });

    // =========================================================================
    // AUTOMATIZACIONES
    // =========================================================================

    // Cambiar tipo de paso
    $(document).on('change', '.em-paso-tipo', function() {
        const tipo = $(this).val();
        const $paso = $(this).closest('.em-paso');

        $paso.find('.em-paso-contenido').hide();
        $paso.find('.em-paso-' + tipo).show();
    });

    // Agregar paso
    $('.em-btn-agregar-paso').on('click', function() {
        const $lista = $('#em-pasos-lista');
        const index = $lista.find('.em-paso').length;

        const html = `
            <div class="em-paso" data-index="${index}">
                <div class="em-paso-header">
                    <span class="em-paso-numero">${index + 1}</span>
                    <select name="pasos[${index}][tipo]" class="em-paso-tipo">
                        <option value="email">Enviar email</option>
                        <option value="espera">Esperar</option>
                        <option value="tag">Añadir tag</option>
                        <option value="lista">Mover a lista</option>
                    </select>
                    <button type="button" class="button em-eliminar-paso">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="em-paso-contenido em-paso-email">
                    <div class="em-paso-espera">
                        <label>Esperar:</label>
                        <input type="text" name="pasos[${index}][espera]" value="1 day" placeholder="1 day, 2 hours">
                    </div>
                    <div class="em-paso-asunto">
                        <label>Asunto:</label>
                        <input type="text" name="pasos[${index}][asunto]">
                    </div>
                    <div class="em-paso-cuerpo">
                        <label>Contenido:</label>
                        <textarea name="pasos[${index}][contenido]" rows="5"></textarea>
                    </div>
                </div>
                <div class="em-paso-contenido em-paso-tag" style="display:none;">
                    <label>Tag a añadir:</label>
                    <input type="text" name="pasos[${index}][tag]">
                </div>
            </div>
        `;

        $lista.append(html);
    });

    // Eliminar paso
    $(document).on('click', '.em-eliminar-paso', function() {
        if ($('#em-pasos-lista .em-paso').length <= 1) {
            alert('Debe haber al menos un paso');
            return;
        }

        $(this).closest('.em-paso').remove();

        // Renumerar pasos
        $('#em-pasos-lista .em-paso').each(function(index) {
            $(this).find('.em-paso-numero').text(index + 1);
        });
    });

    // Activar/pausar automatización
    $('.em-btn-activar-auto, .em-btn-pausar-auto').on('click', function() {
        const id = $(this).data('id');
        const nuevoEstado = $(this).hasClass('em-btn-activar-auto') ? 'activa' : 'pausada';

        apiCall('em/admin/automatizaciones/' + id + '/estado', 'PUT', { estado: nuevoEstado })
            .done(function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    showNotice(response.error, 'error');
                }
            });
    });

    // =========================================================================
    // PLANTILLAS
    // =========================================================================

    // Nueva plantilla
    $('.em-btn-nueva-plantilla').on('click', function() {
        $('#em-modal-plantilla-titulo').text('Nueva plantilla');
        $('#em-form-plantilla')[0].reset();
        $('#em-plantilla-id').val('');
        openModal('em-modal-plantilla');
    });

    // Preview plantilla
    $('.em-btn-preview-plantilla').on('click', function() {
        const id = $(this).data('id');

        apiCall('em/admin/plantillas/' + id, 'GET')
            .done(function(response) {
                if (response.success && response.plantilla) {
                    const iframe = document.getElementById('em-preview-iframe');
                    iframe.srcdoc = response.plantilla.contenido_html;
                    openModal('em-modal-preview');
                }
            });
    });

    // Filtrar plantillas por categoría
    $('.em-categorias-tabs a').on('click', function(e) {
        e.preventDefault();

        const categoria = $(this).data('categoria');

        $('.em-categorias-tabs a').removeClass('active');
        $(this).addClass('active');

        if (categoria === 'todas') {
            $('.em-plantilla-card').show();
        } else {
            $('.em-plantilla-card').hide();
            $('.em-plantilla-card[data-categoria="' + categoria + '"]').show();
            $('.em-plantilla-nueva').show();
        }
    });

    // =========================================================================
    // SUSCRIPTORES
    // =========================================================================

    // Importar
    $('.em-btn-importar').on('click', function() {
        openModal('em-modal-importar');
    });

    $('#em-form-importar').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'em_importar_suscriptores');
        formData.append('nonce', flavorEM.nonce);

        $.ajax({
            url: flavorEM.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice('Importados ' + response.data.importados + ' suscriptores', 'success');
                    closeModal();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || 'Error al importar', 'error');
                }
            }
        });
    });

    // Ver detalle suscriptor
    $('.em-ver-suscriptor').on('click', function(e) {
        e.preventDefault();

        const id = $(this).data('id');
        const $modal = $('#em-modal-suscriptor');
        const $loading = $modal.find('.em-modal-loading');
        const $detalle = $modal.find('.em-suscriptor-detalle');

        $loading.show();
        $detalle.hide().empty();
        openModal('em-modal-suscriptor');

        apiCall('em/admin/suscriptores/' + id, 'GET')
            .done(function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = `
                        <h3>${data.suscriptor.email}</h3>
                        <p><strong>Nombre:</strong> ${data.suscriptor.nombre || '-'}</p>
                        <p><strong>Estado:</strong> ${data.suscriptor.estado}</p>
                        <p><strong>Puntuación:</strong> ${data.suscriptor.puntuacion}</p>
                        <hr>
                        <h4>Métricas</h4>
                        <p>Emails recibidos: ${data.metricas.emails_recibidos}</p>
                        <p>Aperturas: ${data.metricas.aperturas}</p>
                        <p>Clicks: ${data.metricas.clicks}</p>
                    `;
                    $detalle.html(html);
                }
                $loading.hide();
                $detalle.show();
            });
    });

    // =========================================================================
    // CONFIGURACIÓN
    // =========================================================================

    // Tabs de configuración
    $('.em-config-tab').on('click', function() {
        const tab = $(this).data('tab');

        $('.em-config-tab').removeClass('active');
        $(this).addClass('active');

        $('.em-config-panel').hide();
        $('.em-config-panel[data-tab="' + tab + '"]').show();
    });

    // Mostrar/ocultar SMTP
    $('#proveedor_smtp').on('change', function() {
        if ($(this).val() === 'smtp') {
            $('.em-smtp-config').show();
        } else {
            $('.em-smtp-config').hide();
        }
    });

    // Test SMTP
    $('.em-btn-test-smtp').on('click', function() {
        const $btn = $(this);
        const $result = $('.em-smtp-test-result');

        $btn.prop('disabled', true);
        $result.text('Probando...');

        $.ajax({
            url: flavorEM.ajax_url,
            type: 'POST',
            data: {
                action: 'em_test_smtp',
                nonce: flavorEM.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.css('color', 'green').text('Conexión exitosa');
                } else {
                    $result.css('color', 'red').text('Error: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

})(jQuery);
