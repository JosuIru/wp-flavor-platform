/**
 * Deep Links Admin Interface JavaScript
 * Handles all admin interface interactions
 */

(function($) {
    'use strict';

    // Estado de la aplicación
    const estadoAplicacion = {
        empresaActual: null,
        modoEdicion: false
    };

    /**
     * Inicialización cuando el DOM está listo
     */
    $(document).ready(function() {
        inicializarEventos();
        inicializarColorPicker();
        inicializarMediaUploader();
    });

    /**
     * Inicializar todos los event listeners
     */
    function inicializarEventos() {
        // Botón añadir nueva empresa
        $('#flavor-add-company').on('click', abrirModalNuevaEmpresa);

        // Botones de las tarjetas de empresa
        $(document).on('click', '.flavor-btn-edit', abrirModalEditarEmpresa);
        $(document).on('click', '.flavor-btn-delete', eliminarEmpresa);
        $(document).on('click', '.flavor-btn-generate', generarEnlaces);

        // Modal controls
        $('.flavor-modal-close, .flavor-modal-cancel').on('click', cerrarModal);
        $(document).on('click', '.flavor-modal-overlay', cerrarModalConClick);

        // Formulario de empresa
        $('#flavor-company-form').on('submit', guardarEmpresa);

        // Botones de copiar
        $(document).on('click', '.flavor-copy-button', copiarEnlace);

        // Tecla Escape para cerrar modal
        $(document).on('keydown', function(evento) {
            if (evento.key === 'Escape') {
                cerrarModal();
            }
        });
    }

    /**
     * Inicializar WordPress Color Picker
     */
    function inicializarColorPicker() {
        if ($.fn.wpColorPicker) {
            $('#company_primary_color').wpColorPicker({
                change: function(evento, interfazColor) {
                    // Opcional: validar el color en tiempo real
                },
                clear: function() {
                    $('#company_primary_color').val('#2271b1');
                }
            });
        }
    }

    /**
     * Inicializar WordPress Media Uploader
     */
    function inicializarMediaUploader() {
        let frameMediaUploader;

        $('#flavor-upload-logo').on('click', function(evento) {
            evento.preventDefault();

            // Si ya existe el media frame, abrirlo
            if (frameMediaUploader) {
                frameMediaUploader.open();
                return;
            }

            // Crear nuevo media frame
            frameMediaUploader = wp.media({
                title: flavorDeepLinks.i18n.selectLogo || 'Seleccionar Logo',
                button: {
                    text: flavorDeepLinks.i18n.useThisImage || 'Usar esta imagen'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // Cuando se selecciona una imagen
            frameMediaUploader.on('select', function() {
                const adjuntoSeleccionado = frameMediaUploader.state().get('selection').first().toJSON();

                // Actualizar preview y campo oculto
                $('#company_logo_url').val(adjuntoSeleccionado.url);
                $('.flavor-logo-preview').html(`<img src="${adjuntoSeleccionado.url}" alt="Logo">`);
            });

            frameMediaUploader.open();
        });

        // Botón para eliminar logo
        $('#flavor-remove-logo').on('click', function(evento) {
            evento.preventDefault();
            $('#company_logo_url').val('');
            $('.flavor-logo-preview').html('<span class="dashicons dashicons-format-image"></span>');
        });
    }

    /**
     * Abrir modal para nueva empresa
     */
    function abrirModalNuevaEmpresa() {
        estadoAplicacion.empresaActual = null;
        estadoAplicacion.modoEdicion = false;

        // Limpiar formulario
        $('#flavor-company-form')[0].reset();
        $('.flavor-logo-preview').html('<span class="dashicons dashicons-format-image"></span>');

        // Reinicializar color picker con valor por defecto
        if ($.fn.wpColorPicker) {
            $('#company_primary_color').wpColorPicker('color', '#2271b1');
        }

        // Actualizar título del modal
        $('.flavor-modal-header h2').text(flavorDeepLinks.i18n.addNewCompany || 'Añadir Nueva Empresa');

        // Limpiar errores
        limpiarErroresFormulario();

        // Mostrar modal
        $('#flavor-company-modal').addClass('active');
    }

    /**
     * Abrir modal para editar empresa
     */
    function abrirModalEditarEmpresa() {
        const idEmpresa = $(this).data('company-id');

        estadoAplicacion.empresaActual = idEmpresa;
        estadoAplicacion.modoEdicion = true;

        // Obtener datos de la empresa
        $.ajax({
            url: `${flavorDeepLinks.apiUrl}/${idEmpresa}`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', flavorDeepLinks.nonce);
            },
            success: function(datosEmpresa) {
                // Rellenar formulario
                $('#company_name').val(datosEmpresa.name);
                $('#company_slug').val(datosEmpresa.slug);
                $('#company_ios_scheme').val(datosEmpresa.ios_scheme);
                $('#company_android_package').val(datosEmpresa.android_package);
                $('#company_fallback_url').val(datosEmpresa.fallback_url);
                $('#company_logo_url').val(datosEmpresa.logo_url || '');

                // Actualizar color picker
                if ($.fn.wpColorPicker) {
                    $('#company_primary_color').wpColorPicker('color', datosEmpresa.primary_color || '#2271b1');
                }

                // Actualizar preview del logo
                if (datosEmpresa.logo_url) {
                    $('.flavor-logo-preview').html(`<img src="${datosEmpresa.logo_url}" alt="Logo">`);
                } else {
                    $('.flavor-logo-preview').html('<span class="dashicons dashicons-format-image"></span>');
                }

                // Actualizar título del modal
                $('.flavor-modal-header h2').text(flavorDeepLinks.i18n.editCompany || 'Editar Empresa');

                // Limpiar errores
                limpiarErroresFormulario();

                // Mostrar modal
                $('#flavor-company-modal').addClass('active');
            },
            error: function(xhr) {
                mostrarNotificacion('error', flavorDeepLinks.i18n.errorLoadingCompany || 'Error al cargar los datos de la empresa');
            }
        });
    }

    /**
     * Guardar empresa (crear o actualizar)
     */
    function guardarEmpresa(evento) {
        evento.preventDefault();

        // Validar formulario
        if (!validarFormulario()) {
            return;
        }

        // Recoger datos del formulario
        const datosEmpresa = {
            name: $('#company_name').val().trim(),
            slug: $('#company_slug').val().trim(),
            ios_scheme: $('#company_ios_scheme').val().trim(),
            android_package: $('#company_android_package').val().trim(),
            fallback_url: $('#company_fallback_url').val().trim(),
            logo_url: $('#company_logo_url').val().trim(),
            primary_color: $('#company_primary_color').val()
        };

        const esActualizacion = estadoAplicacion.modoEdicion && estadoAplicacion.empresaActual;
        const metodoHttp = esActualizacion ? 'PUT' : 'POST';
        const urlEndpoint = esActualizacion
            ? `${flavorDeepLinks.apiUrl}/${estadoAplicacion.empresaActual}`
            : flavorDeepLinks.apiUrl;

        const $botonGuardar = $('#flavor-save-company');
        const textoOriginalBoton = $botonGuardar.text();

        $.ajax({
            url: urlEndpoint,
            method: metodoHttp,
            data: JSON.stringify(datosEmpresa),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', flavorDeepLinks.nonce);
                $botonGuardar.prop('disabled', true).html(
                    `${flavorDeepLinks.i18n.saving || 'Guardando...'} <span class="flavor-loading"></span>`
                );
            },
            success: function(respuesta) {
                cerrarModal();
                mostrarNotificacion(
                    'success',
                    esActualizacion
                        ? (flavorDeepLinks.i18n.companyUpdated || 'Empresa actualizada correctamente')
                        : (flavorDeepLinks.i18n.companyCreated || 'Empresa creada correctamente')
                );

                // Recargar la página para mostrar los cambios
                setTimeout(() => {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                let mensajeError = flavorDeepLinks.i18n.errorSavingCompany || 'Error al guardar la empresa';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensajeError = xhr.responseJSON.message;
                }

                mostrarNotificacion('error', mensajeError);
            },
            complete: function() {
                $botonGuardar.prop('disabled', false).text(textoOriginalBoton);
            }
        });
    }

    /**
     * Eliminar empresa
     */
    function eliminarEmpresa() {
        const idEmpresa = $(this).data('company-id');
        const nombreEmpresa = $(this).closest('.flavor-company-card').find('h3').text();

        const confirmacionMensaje = flavorDeepLinks.i18n.confirmDelete
            ? flavorDeepLinks.i18n.confirmDelete.replace('%s', nombreEmpresa)
            : `¿Estás seguro de que quieres eliminar la empresa "${nombreEmpresa}"? Esta acción no se puede deshacer.`;

        if (!confirm(confirmacionMensaje)) {
            return;
        }

        const $tarjetaEmpresa = $(this).closest('.flavor-company-card');

        $.ajax({
            url: `${flavorDeepLinks.apiUrl}/${idEmpresa}`,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', flavorDeepLinks.nonce);
                $tarjetaEmpresa.css('opacity', '0.5');
            },
            success: function(respuesta) {
                mostrarNotificacion(
                    'success',
                    flavorDeepLinks.i18n.companyDeleted || 'Empresa eliminada correctamente'
                );

                // Animar y eliminar la tarjeta
                $tarjetaEmpresa.fadeOut(400, function() {
                    $(this).remove();

                    // Si no quedan empresas, recargar para mostrar el estado vacío
                    if ($('.flavor-company-card').length === 0) {
                        location.reload();
                    }
                });
            },
            error: function(xhr) {
                $tarjetaEmpresa.css('opacity', '1');

                let mensajeError = flavorDeepLinks.i18n.errorDeletingCompany || 'Error al eliminar la empresa';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensajeError = xhr.responseJSON.message;
                }

                mostrarNotificacion('error', mensajeError);
            }
        });
    }

    /**
     * Generar enlaces para una empresa
     */
    function generarEnlaces() {
        const idEmpresa = $(this).data('company-id');
        const slugEmpresa = $(this).data('company-slug');

        // Limpiar enlaces anteriores
        $('#flavor-generated-links').empty();

        // Mostrar modal
        $('#flavor-links-modal').addClass('active');
        $('.flavor-modal-header h2').text(
            (flavorDeepLinks.i18n.generatedLinks || 'Enlaces Generados') + ` - ${slugEmpresa}`
        );

        // Generar enlaces
        $.ajax({
            url: `${flavorDeepLinks.apiUrl}/${idEmpresa}/generate-links`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', flavorDeepLinks.nonce);
                $('#flavor-generated-links').html(
                    `<div style="text-align: center; padding: 20px;">
                        <span class="flavor-loading"></span>
                        <p>${flavorDeepLinks.i18n.generatingLinks || 'Generando enlaces...'}</p>
                    </div>`
                );
            },
            success: function(datosEnlaces) {
                mostrarEnlacesGenerados(datosEnlaces);
            },
            error: function(xhr) {
                let mensajeError = flavorDeepLinks.i18n.errorGeneratingLinks || 'Error al generar los enlaces';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensajeError = xhr.responseJSON.message;
                }

                $('#flavor-generated-links').html(
                    `<div class="flavor-notice error show">
                        <span class="dashicons dashicons-warning"></span>
                        <p>${mensajeError}</p>
                    </div>`
                );
            }
        });
    }

    /**
     * Mostrar enlaces generados en el modal
     */
    function mostrarEnlacesGenerados(datosEnlaces) {
        let htmlEnlaces = '';

        // Enlace iOS
        if (datosEnlaces.ios_link) {
            htmlEnlaces += crearItemEnlace(
                'iOS Deep Link',
                datosEnlaces.ios_link,
                'dashicons-smartphone'
            );
        }

        // Enlace Android
        if (datosEnlaces.android_link) {
            htmlEnlaces += crearItemEnlace(
                'Android Deep Link',
                datosEnlaces.android_link,
                'dashicons-smartphone'
            );
        }

        // Enlace Universal
        if (datosEnlaces.universal_link) {
            htmlEnlaces += crearItemEnlace(
                'Universal Link',
                datosEnlaces.universal_link,
                'dashicons-admin-links'
            );
        }

        $('#flavor-generated-links').html(htmlEnlaces);
    }

    /**
     * Crear HTML para un item de enlace
     */
    function crearItemEnlace(titulo, urlEnlace, iconoDashicons) {
        return `
            <div class="flavor-link-item">
                <div class="flavor-link-header">
                    <h4><span class="dashicons ${iconoDashicons}"></span> ${titulo}</h4>
                </div>
                <div class="flavor-link-url-wrapper">
                    <div class="flavor-link-url">${urlEnlace}</div>
                    <button type="button" class="button flavor-copy-button" data-url="${urlEnlace}">
                        <span class="dashicons dashicons-clipboard"></span>
                        ${flavorDeepLinks.i18n.copy || 'Copiar'}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Copiar enlace al portapapeles
     */
    function copiarEnlace() {
        const $boton = $(this);
        const urlParaCopiar = $boton.data('url');

        // Usar API moderna del portapapeles si está disponible
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(urlParaCopiar)
                .then(() => {
                    indicarCopiado($boton);
                })
                .catch(() => {
                    copiarConMetodoLegacy(urlParaCopiar, $boton);
                });
        } else {
            copiarConMetodoLegacy(urlParaCopiar, $boton);
        }
    }

    /**
     * Método legacy para copiar al portapapeles
     */
    function copiarConMetodoLegacy(texto, $boton) {
        const $areaTextoTemporal = $('<textarea>');
        $('body').append($areaTextoTemporal);
        $areaTextoTemporal.val(texto).select();

        try {
            document.execCommand('copy');
            indicarCopiado($boton);
        } catch (error) {
            console.error('Error al copiar:', error);
            mostrarNotificacion('error', flavorDeepLinks.i18n.errorCopying || 'Error al copiar al portapapeles');
        }

        $areaTextoTemporal.remove();
    }

    /**
     * Indicar visualmente que se ha copiado
     */
    function indicarCopiado($boton) {
        const textoOriginal = $boton.html();

        $boton.addClass('copied')
            .html('<span class="dashicons dashicons-yes"></span> ' + (flavorDeepLinks.i18n.copied || '¡Copiado!'));

        setTimeout(() => {
            $boton.removeClass('copied').html(textoOriginal);
        }, 2000);
    }

    /**
     * Validar formulario
     */
    function validarFormulario() {
        let esValido = true;
        limpiarErroresFormulario();

        // Validar nombre
        const nombreEmpresa = $('#company_name').val().trim();
        if (!nombreEmpresa) {
            mostrarErrorCampo('company_name', flavorDeepLinks.i18n.nameRequired || 'El nombre es obligatorio');
            esValido = false;
        }

        // Validar slug
        const slugEmpresa = $('#company_slug').val().trim();
        if (!slugEmpresa) {
            mostrarErrorCampo('company_slug', flavorDeepLinks.i18n.slugRequired || 'El slug es obligatorio');
            esValido = false;
        } else if (!/^[a-z0-9-]+$/.test(slugEmpresa)) {
            mostrarErrorCampo('company_slug', flavorDeepLinks.i18n.slugInvalid || 'El slug solo puede contener letras minúsculas, números y guiones');
            esValido = false;
        }

        // Validar URL de fallback
        const urlFallback = $('#company_fallback_url').val().trim();
        if (!urlFallback) {
            mostrarErrorCampo('company_fallback_url', flavorDeepLinks.i18n.fallbackRequired || 'La URL de fallback es obligatoria');
            esValido = false;
        } else if (!esUrlValida(urlFallback)) {
            mostrarErrorCampo('company_fallback_url', flavorDeepLinks.i18n.urlInvalid || 'La URL no es válida');
            esValido = false;
        }

        return esValido;
    }

    /**
     * Validar formato de URL
     */
    function esUrlValida(texto) {
        try {
            const url = new URL(texto);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (error) {
            return false;
        }
    }

    /**
     * Mostrar error en un campo
     */
    function mostrarErrorCampo(idCampo, mensajeError) {
        const $campo = $(`#${idCampo}`);
        $campo.addClass('error');

        const $mensajeError = $campo.siblings('.error-message');
        if ($mensajeError.length) {
            $mensajeError.text(mensajeError).addClass('show');
        } else {
            $campo.after(`<span class="error-message show">${mensajeError}</span>`);
        }
    }

    /**
     * Limpiar todos los errores del formulario
     */
    function limpiarErroresFormulario() {
        $('.flavor-form-group input').removeClass('error');
        $('.error-message').removeClass('show');
    }

    /**
     * Cerrar modal
     */
    function cerrarModal() {
        $('.flavor-modal-overlay').removeClass('active');
        limpiarErroresFormulario();
    }

    /**
     * Cerrar modal al hacer click en el overlay
     */
    function cerrarModalConClick(evento) {
        if ($(evento.target).hasClass('flavor-modal-overlay')) {
            cerrarModal();
        }
    }

    /**
     * Mostrar notificación
     */
    function mostrarNotificacion(tipo, mensaje) {
        const icono = tipo === 'success' ? 'yes-alt' : 'warning';

        const $notificacion = $(`
            <div class="flavor-notice ${tipo}">
                <span class="dashicons dashicons-${icono}"></span>
                <p>${mensaje}</p>
            </div>
        `);

        // Insertar después del header
        $('.flavor-deep-links-header').after($notificacion);

        // Mostrar con animación
        setTimeout(() => {
            $notificacion.addClass('show');
        }, 100);

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            $notificacion.removeClass('show');
            setTimeout(() => {
                $notificacion.remove();
            }, 300);
        }, 5000);

        // Scroll suave hacia la notificación
        $('html, body').animate({
            scrollTop: $notificacion.offset().top - 100
        }, 400);
    }

})(jQuery);
