(function($) {
    'use strict';

    var configuracion = window.flavorExportImport || {};

    // Mostrar nombre de archivo seleccionado
    $('#flavor-import-file').on('change', function() {
        var nombreArchivo = this.files.length > 0 ? this.files[0].name : '';
        $('#flavor-import-filename').text(nombreArchivo);
    });

    // Exportar datos
    $('#flavor-export-form').on('submit', function(evento) {
        evento.preventDefault();

        var seccionesSeleccionadas = [];
        $('input[name="export_secciones[]"]:checked').each(function() {
            seccionesSeleccionadas.push($(this).val());
        });

        if (seccionesSeleccionadas.length === 0) {
            mostrarEstado('#flavor-export-status', configuracion.strings.sinSeleccion, 'error');
            return;
        }

        mostrarEstado('#flavor-export-status', configuracion.strings.exportando, 'loading');

        $.ajax({
            url: configuracion.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_export_data',
                nonce: configuracion.nonce,
                secciones: seccionesSeleccionadas
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    var contenidoJson = JSON.stringify(respuesta.data.data, null, 2);
                    var blobArchivo = new Blob([contenidoJson], { type: 'application/json' });
                    var urlDescarga = URL.createObjectURL(blobArchivo);
                    var enlaceDescarga = document.createElement('a');
                    enlaceDescarga.href = urlDescarga;
                    enlaceDescarga.download = respuesta.data.filename;
                    document.body.appendChild(enlaceDescarga);
                    enlaceDescarga.click();
                    document.body.removeChild(enlaceDescarga);
                    URL.revokeObjectURL(urlDescarga);
                    mostrarEstado('#flavor-export-status', configuracion.strings.exportCompletada, 'success');
                } else {
                    mostrarEstado('#flavor-export-status', respuesta.data || configuracion.strings.errorExport, 'error');
                }
            },
            error: function() {
                mostrarEstado('#flavor-export-status', configuracion.strings.errorExport, 'error');
            }
        });
    });

    // Previsualizar importacion
    $('#flavor-import-upload-form').on('submit', function(evento) {
        evento.preventDefault();

        var archivoInput = document.getElementById('flavor-import-file');
        if (!archivoInput.files.length) {
            mostrarEstado('#flavor-import-status', configuracion.strings.errorArchivo, 'error');
            return;
        }

        var datosFormulario = new FormData();
        datosFormulario.append('action', 'flavor_import_preview');
        datosFormulario.append('nonce', configuracion.nonce);
        datosFormulario.append('archivo_importacion', archivoInput.files[0]);

        mostrarEstado('#flavor-import-status', configuracion.strings.previsualizando, 'loading');

        $.ajax({
            url: configuracion.ajaxUrl,
            type: 'POST',
            data: datosFormulario,
            processData: false,
            contentType: false,
            success: function(respuesta) {
                if (respuesta.success) {
                    ocultarEstado('#flavor-import-status');
                    mostrarPrevisualizacion(respuesta.data);
                } else {
                    mostrarEstado('#flavor-import-status', respuesta.data || configuracion.strings.errorImport, 'error');
                }
            },
            error: function() {
                mostrarEstado('#flavor-import-status', configuracion.strings.errorImport, 'error');
            }
        });
    });

    // Aplicar importacion
    $('#flavor-import-apply-form').on('submit', function(evento) {
        evento.preventDefault();

        if (!confirm(configuracion.strings.confirmarImport)) {
            return;
        }

        var seccionesImportar = [];
        $('input[name="import_secciones[]"]:checked').each(function() {
            seccionesImportar.push($(this).val());
        });

        if (seccionesImportar.length === 0) {
            mostrarEstado('#flavor-import-status', configuracion.strings.sinSeleccion, 'error');
            return;
        }

        mostrarEstado('#flavor-import-status', configuracion.strings.importando, 'loading');

        $.ajax({
            url: configuracion.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_import_apply',
                nonce: configuracion.nonce,
                secciones: seccionesImportar
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    mostrarEstado('#flavor-import-status', configuracion.strings.importCompletada, 'success');
                    $('#flavor-import-step-preview').addClass('hidden');
                    $('#flavor-import-step-upload').removeClass('hidden');
                } else {
                    mostrarEstado('#flavor-import-status', respuesta.data.message || configuracion.strings.errorImport, 'error');
                }
            },
            error: function() {
                mostrarEstado('#flavor-import-status', configuracion.strings.errorImport, 'error');
            }
        });
    });

    // Cancelar importacion
    $('#flavor-import-cancel-btn').on('click', function() {
        $('#flavor-import-step-preview').addClass('hidden');
        $('#flavor-import-step-upload').removeClass('hidden');
        ocultarEstado('#flavor-import-status');
    });

    function mostrarPrevisualizacion(datosPrevia) {
        $('#flavor-import-step-upload').addClass('hidden');
        $('#flavor-import-step-preview').removeClass('hidden');

        var metadatos = datosPrevia.metadata;
        var contenidoMetadatos = '<strong>Version:</strong> ' + escapeHtml(metadatos.version) +
            ' | <strong>Exportado:</strong> ' + escapeHtml(metadatos.exported_at) +
            ' | <strong>Sitio origen:</strong> ' + escapeHtml(metadatos.site_url);
        $('#flavor-import-metadata').html(contenidoMetadatos);

        var contenedorCheckboxes = $('#flavor-import-checkboxes');
        contenedorCheckboxes.empty();

        var resumenSecciones = datosPrevia.resumen;
        for (var claveSeccion in resumenSecciones) {
            if (resumenSecciones.hasOwnProperty(claveSeccion)) {
                var infoSeccion = resumenSecciones[claveSeccion];
                var htmlCheckbox = '<label class="flavor-checkbox-option">' +
                    '<input type="checkbox" name="import_secciones[]" value="' + escapeHtml(claveSeccion) + '" checked>' +
                    '<span class="flavor-checkbox-label">' +
                    '<strong>' + escapeHtml(infoSeccion.label) + '</strong>' +
                    '<span class="flavor-checkbox-desc">' + escapeHtml(infoSeccion.description) + '</span>' +
                    '</span></label>';
                contenedorCheckboxes.append(htmlCheckbox);
            }
        }
    }

    function mostrarEstado(selectorElemento, textoMensaje, tipoMensaje) {
        $(selectorElemento)
            .removeClass('hidden success error loading')
            .addClass(tipoMensaje)
            .text(textoMensaje);
    }

    function ocultarEstado(selectorElemento) {
        $(selectorElemento).addClass('hidden').text('');
    }

    function escapeHtml(textoSinEscapar) {
        var elementoDiv = document.createElement('div');
        elementoDiv.appendChild(document.createTextNode(textoSinEscapar));
        return elementoDiv.innerHTML;
    }

})(jQuery);
