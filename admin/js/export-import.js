/**
 * Flavor Platform - Export/Import JavaScript
 *
 * Sistema completo de exportación/importación con tabs, presets y previsualización.
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */

(function($) {
    'use strict';

    const FlavorExportImport = {
        // Estado
        previewData: null,
        exportData: null,

        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.renderPresets();
        },

        /**
         * Bindea todos los eventos
         */
        bindEvents: function() {
            // Tabs
            $('.flavor-export-import-tabs .nav-tab').on('click', this.handleTabClick.bind(this));

            // Exportar
            $('#flavor-export-form').on('submit', this.handleExport.bind(this));
            $('#flavor-select-all-export').on('click', this.selectAllExport.bind(this));
            $('#flavor-deselect-all-export').on('click', this.deselectAllExport.bind(this));
            $('#flavor-copy-export').on('click', this.copyExportToClipboard.bind(this));
            $('#flavor-download-export').on('click', this.downloadExport.bind(this));

            // Importar - Dropzone
            this.initDropzone();

            // Importar - Textarea
            $('#flavor-import-json-paste').on('input', this.handlePasteInput.bind(this));

            // Importar - Botones de navegación
            $('#flavor-preview-import-btn').on('click', this.handlePreview.bind(this));
            $('#flavor-back-step-1').on('click', () => this.goToStep(1));
            $('#flavor-back-step-2').on('click', () => this.goToStep(2));
            $('#flavor-continue-step-3').on('click', () => this.goToStep(3));
            $('#flavor-import-form').on('submit', this.handleImport.bind(this));

            // Presets
            $(document).on('click', '.flavor-preset-card .button', this.handleApplyPreset.bind(this));
        },

        /**
         * Inicializa las tabs
         */
        initTabs: function() {
            // Verificar hash en URL
            const hashActual = window.location.hash;
            if (hashActual && hashActual.startsWith('#tab-')) {
                const nombreTab = hashActual.replace('#tab-', '');
                this.switchTab(nombreTab);
            }
        },

        /**
         * Maneja el click en tabs
         */
        handleTabClick: function(evento) {
            evento.preventDefault();
            const $tabElement = $(evento.currentTarget);
            const nombreTab = $tabElement.data('tab');
            this.switchTab(nombreTab);
            window.location.hash = 'tab-' + nombreTab;
        },

        /**
         * Cambia a una tab específica
         */
        switchTab: function(nombreTab) {
            $('.flavor-export-import-tabs .nav-tab').removeClass('nav-tab-active');
            $(`.flavor-export-import-tabs .nav-tab[data-tab="${nombreTab}"]`).addClass('nav-tab-active');

            $('.flavor-tab-content').removeClass('active');
            $(`#tab-${nombreTab}`).addClass('active');
        },

        // =====================================================================
        // EXPORTACIÓN
        // =====================================================================

        /**
         * Maneja el submit del formulario de exportación
         */
        handleExport: function(evento) {
            evento.preventDefault();

            const $formulario = $(evento.currentTarget);
            const $botonExportar = $('#flavor-export-btn');
            const seccionesSeleccionadas = [];

            $formulario.find('input[name="export_sections[]"]:checked').each(function() {
                seccionesSeleccionadas.push($(this).val());
            });

            if (seccionesSeleccionadas.length === 0) {
                this.showNotice(flavorExportImport.strings.sinSeleccion, 'warning');
                return;
            }

            $botonExportar.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + flavorExportImport.strings.exportando);

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_export_config',
                    nonce: flavorExportImport.nonce,
                    sections: seccionesSeleccionadas
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.exportData = respuesta.data;
                        this.showExportResult(respuesta.data.data, respuesta.data.filename);
                        this.showNotice(flavorExportImport.strings.exportCompletada, 'success');
                    } else {
                        this.showNotice(respuesta.data.message || flavorExportImport.strings.errorExport, 'error');
                    }
                },
                error: () => {
                    this.showNotice(flavorExportImport.strings.errorExport, 'error');
                },
                complete: () => {
                    $botonExportar.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Generar y Descargar JSON');
                }
            });
        },

        /**
         * Muestra el resultado de la exportación
         */
        showExportResult: function(datosExportados, nombreArchivo) {
            const contenidoJsonFormateado = JSON.stringify(datosExportados, null, 2);
            $('#flavor-export-json').val(contenidoJsonFormateado);
            $('#flavor-export-result').removeClass('hidden');
            this.exportData = {
                data: datosExportados,
                filename: nombreArchivo
            };
        },

        /**
         * Selecciona todas las opciones de exportación
         */
        selectAllExport: function() {
            $('#flavor-export-form input[type="checkbox"]').prop('checked', true);
        },

        /**
         * Deselecciona todas las opciones de exportación
         */
        deselectAllExport: function() {
            $('#flavor-export-form input[type="checkbox"]').prop('checked', false);
        },

        /**
         * Copia el JSON al portapapeles
         */
        copyExportToClipboard: function() {
            const $areaTexto = $('#flavor-export-json');
            $areaTexto.select();
            document.execCommand('copy');

            const $botonCopiar = $('#flavor-copy-export');
            const contenidoOriginal = $botonCopiar.html();
            $botonCopiar.html('<span class="dashicons dashicons-yes"></span> Copiado!');
            setTimeout(() => {
                $botonCopiar.html(contenidoOriginal);
            }, 2000);
        },

        /**
         * Descarga el archivo de exportación
         */
        downloadExport: function() {
            if (!this.exportData) {
                this.showNotice('No hay datos para descargar', 'error');
                return;
            }

            const contenidoJson = JSON.stringify(this.exportData.data, null, 2);
            const blobArchivo = new Blob([contenidoJson], { type: 'application/json' });
            const urlDescarga = URL.createObjectURL(blobArchivo);
            const enlaceDescarga = document.createElement('a');

            enlaceDescarga.href = urlDescarga;
            enlaceDescarga.download = this.exportData.filename;
            document.body.appendChild(enlaceDescarga);
            enlaceDescarga.click();
            document.body.removeChild(enlaceDescarga);
            URL.revokeObjectURL(urlDescarga);
        },

        // =====================================================================
        // IMPORTACIÓN
        // =====================================================================

        /**
         * Inicializa la dropzone de archivos
         */
        initDropzone: function() {
            const $zonaArrastre = $('#flavor-import-dropzone');
            const $inputArchivo = $('#flavor-import-file');
            const $contenidoZona = $zonaArrastre.find('.flavor-dropzone-content');
            const $archivoZona = $zonaArrastre.find('.flavor-dropzone-file');

            // Eventos de drag & drop
            $zonaArrastre.on('dragover dragenter', (evento) => {
                evento.preventDefault();
                $zonaArrastre.addClass('dragover');
            });

            $zonaArrastre.on('dragleave drop', (evento) => {
                evento.preventDefault();
                $zonaArrastre.removeClass('dragover');
            });

            $zonaArrastre.on('drop', (evento) => {
                const archivos = evento.originalEvent.dataTransfer.files;
                if (archivos.length > 0 && archivos[0].type === 'application/json') {
                    $inputArchivo[0].files = archivos;
                    this.handleFileSelect(archivos[0]);
                }
            });

            // Evento de selección de archivo
            $inputArchivo.on('change', (evento) => {
                const archivos = evento.target.files;
                if (archivos.length > 0) {
                    this.handleFileSelect(archivos[0]);
                }
            });

            // Botón de quitar archivo
            $zonaArrastre.find('.flavor-remove-file').on('click', (evento) => {
                evento.stopPropagation();
                $inputArchivo.val('');
                $contenidoZona.removeClass('hidden');
                $archivoZona.addClass('hidden');
                this.updatePreviewButton();
            });
        },

        /**
         * Maneja la selección de archivo
         */
        handleFileSelect: function(archivoSeleccionado) {
            const $zonaArrastre = $('#flavor-import-dropzone');
            const $contenidoZona = $zonaArrastre.find('.flavor-dropzone-content');
            const $archivoZona = $zonaArrastre.find('.flavor-dropzone-file');

            $contenidoZona.addClass('hidden');
            $archivoZona.removeClass('hidden');
            $archivoZona.find('.flavor-filename').text(archivoSeleccionado.name);

            // Limpiar el textarea
            $('#flavor-import-json-paste').val('');

            this.updatePreviewButton();
        },

        /**
         * Maneja el input en el textarea de pegado
         */
        handlePasteInput: function() {
            // Limpiar el file input
            $('#flavor-import-file').val('');
            const $zonaArrastre = $('#flavor-import-dropzone');
            $zonaArrastre.find('.flavor-dropzone-content').removeClass('hidden');
            $zonaArrastre.find('.flavor-dropzone-file').addClass('hidden');

            this.updatePreviewButton();
        },

        /**
         * Actualiza el estado del botón de preview
         */
        updatePreviewButton: function() {
            const tieneArchivoSeleccionado = $('#flavor-import-file')[0].files.length > 0;
            const tieneTextoPegado = $('#flavor-import-json-paste').val().trim().length > 0;

            $('#flavor-preview-import-btn').prop('disabled', !(tieneArchivoSeleccionado || tieneTextoPegado));
        },

        /**
         * Maneja la previsualización
         */
        handlePreview: function() {
            const $botonPreview = $('#flavor-preview-import-btn');
            $botonPreview.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + flavorExportImport.strings.previsualizando);

            const datosFormulario = new FormData();
            datosFormulario.append('action', 'flavor_preview_import');
            datosFormulario.append('nonce', flavorExportImport.nonce);

            const archivoSeleccionado = $('#flavor-import-file')[0].files[0];
            const contenidoJsonPegado = $('#flavor-import-json-paste').val().trim();

            if (archivoSeleccionado) {
                datosFormulario.append('import_file', archivoSeleccionado);
            } else if (contenidoJsonPegado) {
                datosFormulario.append('json_content', contenidoJsonPegado);
            } else {
                $botonPreview.prop('disabled', false).html('<span class="dashicons dashicons-visibility"></span> Analizar y Previsualizar');
                this.showNotice(flavorExportImport.strings.errorArchivo, 'error');
                return;
            }

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: datosFormulario,
                processData: false,
                contentType: false,
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.previewData = respuesta.data;
                        this.renderPreview(respuesta.data);
                        this.goToStep(2);
                    } else {
                        this.showNotice(respuesta.data.message || 'Error al analizar el archivo', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Error de conexión', 'error');
                },
                complete: () => {
                    $botonPreview.prop('disabled', false).html('<span class="dashicons dashicons-visibility"></span> Analizar y Previsualizar');
                }
            });
        },

        /**
         * Renderiza la previsualización
         */
        renderPreview: function(datosPreview) {
            // Metadata
            const $contenedorMetadata = $('#flavor-import-metadata');
            $contenedorMetadata.empty();

            if (datosPreview.metadata) {
                const elementosMetadata = [
                    { etiqueta: 'Versión', valor: datosPreview.metadata.version },
                    { etiqueta: 'Exportado', valor: datosPreview.metadata.exported_at },
                    { etiqueta: 'Sitio origen', valor: datosPreview.metadata.site_url }
                ];

                elementosMetadata.forEach(elemento => {
                    $contenedorMetadata.append(`
                        <div class="flavor-metadata-item">
                            <label>${elemento.etiqueta}</label>
                            <span>${this.escapeHtml(elemento.valor)}</span>
                        </div>
                    `);
                });
            }

            // Warnings
            const $contenedorAvisos = $('#flavor-import-warnings');
            $contenedorAvisos.empty();
            if (datosPreview.warnings && datosPreview.warnings.length > 0) {
                datosPreview.warnings.forEach(aviso => {
                    $contenedorAvisos.append(`<p><span class="dashicons dashicons-warning"></span> ${this.escapeHtml(aviso)}</p>`);
                });
                $contenedorAvisos.removeClass('hidden');
            } else {
                $contenedorAvisos.addClass('hidden');
            }

            // Secciones
            const $previewSecciones = $('#flavor-import-sections-preview');
            const $checkboxesSecciones = $('#flavor-import-sections-checkboxes');
            $previewSecciones.empty();
            $checkboxesSecciones.empty();

            if (datosPreview.sections) {
                Object.keys(datosPreview.sections).forEach(claveSeccion => {
                    const datosSeccion = datosPreview.sections[claveSeccion];

                    // Preview card
                    let htmlItems = '';
                    if (datosSeccion.items && datosSeccion.items.length > 0) {
                        htmlItems = '<div class="items">';
                        datosSeccion.items.slice(0, 5).forEach(item => {
                            const claseAccion = item.action === 'create' ? 'action-create' : 'action-update';
                            const iconoAccion = item.action === 'create' ? 'dashicons-plus-alt2' : 'dashicons-update';
                            htmlItems += `
                                <div class="item">
                                    <span class="dashicons ${iconoAccion} ${claseAccion}"></span>
                                    <span>${this.escapeHtml(item.title)}</span>
                                </div>
                            `;
                        });
                        if (datosSeccion.items.length > 5) {
                            htmlItems += `<div class="item" style="color: #646970;">...y ${datosSeccion.items.length - 5} más</div>`;
                        }
                        htmlItems += '</div>';
                    }

                    let htmlCambios = '';
                    if (datosSeccion.changes && datosSeccion.changes.length > 0) {
                        htmlCambios = '<div class="changes">' + datosSeccion.changes.map(cambio => this.escapeHtml(cambio)).join('<br>') + '</div>';
                    }

                    $previewSecciones.append(`
                        <div class="flavor-section-preview-card">
                            <h4>
                                ${this.escapeHtml(datosSeccion.label)}
                                <span class="count">${datosSeccion.count || 0}</span>
                            </h4>
                            ${htmlCambios}
                            ${htmlItems}
                        </div>
                    `);

                    // Checkbox
                    $checkboxesSecciones.append(`
                        <label>
                            <input type="checkbox" name="import_sections[]" value="${claveSeccion}" checked>
                            ${this.escapeHtml(datosSeccion.label)}
                        </label>
                    `);
                });
            }
        },

        /**
         * Cambia al paso indicado
         */
        goToStep: function(numeroPaso) {
            $('.flavor-import-step').removeClass('active');
            $(`#flavor-import-step-${numeroPaso}`).addClass('active');
        },

        /**
         * Maneja la importación
         */
        handleImport: function(evento) {
            evento.preventDefault();

            if (!confirm(flavorExportImport.strings.confirmarImport)) {
                return;
            }

            const $botonAplicar = $('#flavor-apply-import-btn');
            const $barraProgreso = $('#flavor-import-progress');
            const $areaResultado = $('#flavor-import-result');

            const seccionesSeleccionadas = [];
            $('#flavor-import-sections-checkboxes input:checked').each(function() {
                seccionesSeleccionadas.push($(this).val());
            });

            const modoImportacion = $('input[name="import_mode"]:checked').val();

            if (seccionesSeleccionadas.length === 0) {
                this.showNotice('Selecciona al menos una sección para importar', 'warning');
                return;
            }

            // Mostrar progreso
            $botonAplicar.prop('disabled', true);
            $('#flavor-import-step-3').addClass('hidden');
            $barraProgreso.removeClass('hidden');
            $areaResultado.addClass('hidden');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_import_config',
                    nonce: flavorExportImport.nonce,
                    sections: seccionesSeleccionadas,
                    mode: modoImportacion
                },
                success: (respuesta) => {
                    $barraProgreso.addClass('hidden');
                    $areaResultado.removeClass('hidden');

                    if (respuesta.success) {
                        $areaResultado.html(this.renderImportResult(respuesta.data));
                        $areaResultado.addClass('success').removeClass('error');
                        this.showNotice(flavorExportImport.strings.importCompletada, 'success');
                    } else {
                        $areaResultado.html(`<p><span class="dashicons dashicons-warning"></span> ${this.escapeHtml(respuesta.data.message)}</p>`);
                        $areaResultado.addClass('error').removeClass('success');
                        this.showNotice(respuesta.data.message, 'error');
                    }
                },
                error: () => {
                    $barraProgreso.addClass('hidden');
                    $areaResultado.removeClass('hidden').addClass('error');
                    $areaResultado.html(`<p><span class="dashicons dashicons-warning"></span> ${flavorExportImport.strings.errorImport}</p>`);
                    this.showNotice(flavorExportImport.strings.errorImport, 'error');
                },
                complete: () => {
                    $botonAplicar.prop('disabled', false);
                }
            });
        },

        /**
         * Renderiza el resultado de la importación
         */
        renderImportResult: function(datosResultado) {
            let htmlResultado = '<h4><span class="dashicons dashicons-yes-alt"></span> ' + datosResultado.message + '</h4>';

            if (datosResultado.results && datosResultado.results.imported) {
                htmlResultado += '<div class="flavor-import-result-details">';

                Object.keys(datosResultado.results.imported).forEach(claveSeccion => {
                    const datosSeccion = datosResultado.results.imported[claveSeccion];
                    htmlResultado += `<p><strong>${claveSeccion}:</strong> `;

                    if (datosSeccion.created !== undefined) {
                        htmlResultado += `${datosSeccion.created} creados, `;
                    }
                    if (datosSeccion.updated !== undefined) {
                        htmlResultado += `${datosSeccion.updated} actualizados`;
                    }
                    if (datosSeccion.skipped !== undefined && datosSeccion.skipped > 0) {
                        htmlResultado += `, ${datosSeccion.skipped} omitidos`;
                    }

                    htmlResultado += '</p>';
                });

                htmlResultado += '</div>';
            }

            htmlResultado += '<p style="margin-top: 15px;"><button type="button" class="button" onclick="location.reload()">Recargar página</button></p>';

            return htmlResultado;
        },

        // =====================================================================
        // PRESETS
        // =====================================================================

        /**
         * Renderiza los presets disponibles
         */
        renderPresets: function() {
            const $contenedorGrid = $('#flavor-presets-grid');
            $contenedorGrid.empty();

            if (!flavorExportImport.presets) {
                return;
            }

            Object.keys(flavorExportImport.presets).forEach(idPreset => {
                const datosPreset = flavorExportImport.presets[idPreset];

                let htmlModulos = '';
                if (datosPreset.config && datosPreset.config.active_modules) {
                    htmlModulos = '<div class="flavor-preset-card-meta">';
                    datosPreset.config.active_modules.forEach(nombreModulo => {
                        htmlModulos += `<span class="tag">${this.escapeHtml(nombreModulo)}</span>`;
                    });
                    htmlModulos += '</div>';
                }

                $contenedorGrid.append(`
                    <div class="flavor-preset-card" data-preset-id="${idPreset}">
                        <div class="flavor-preset-card-header">
                            <span class="dashicons ${datosPreset.icono || 'dashicons-admin-generic'}"></span>
                            <h3>${this.escapeHtml(datosPreset.nombre)}</h3>
                        </div>
                        <p>${this.escapeHtml(datosPreset.descripcion)}</p>
                        ${htmlModulos}
                        <button type="button" class="button button-primary">
                            <span class="dashicons dashicons-yes"></span>
                            Aplicar Preset
                        </button>
                    </div>
                `);
            });
        },

        /**
         * Maneja la aplicación de un preset
         */
        handleApplyPreset: function(evento) {
            evento.preventDefault();
            evento.stopPropagation();

            const $tarjetaPreset = $(evento.currentTarget).closest('.flavor-preset-card');
            const idPreset = $tarjetaPreset.data('preset-id');
            const datosPreset = flavorExportImport.presets[idPreset];

            if (!confirm(flavorExportImport.strings.confirmarPreset + '\n\nPreset: ' + datosPreset.nombre)) {
                return;
            }

            const $botonAplicar = $tarjetaPreset.find('.button');
            $botonAplicar.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + flavorExportImport.strings.aplicandoPreset);

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_apply_preset',
                    nonce: flavorExportImport.nonce,
                    preset_id: idPreset
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice(respuesta.data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotice(respuesta.data.message || 'Error al aplicar preset', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Error de conexión', 'error');
                },
                complete: () => {
                    $botonAplicar.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Aplicar Preset');
                }
            });
        },

        // =====================================================================
        // UTILIDADES
        // =====================================================================

        /**
         * Muestra una notificación
         */
        showNotice: function(textoMensaje, tipoNotificacion = 'success') {
            const $contenedor = $('#flavor-export-import-notices');
            const iconoNotificacion = tipoNotificacion === 'success' ? 'yes-alt' : (tipoNotificacion === 'error' ? 'dismiss' : 'warning');
            const $elementoNotice = $(`
                <div class="flavor-notice ${tipoNotificacion}">
                    <span class="dashicons dashicons-${iconoNotificacion}"></span>
                    ${this.escapeHtml(textoMensaje)}
                </div>
            `);

            $contenedor.append($elementoNotice);

            setTimeout(() => {
                $elementoNotice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Escapa HTML para prevenir XSS
         */
        escapeHtml: function(textoSinEscapar) {
            if (!textoSinEscapar) return '';
            const elementoDiv = document.createElement('div');
            elementoDiv.textContent = textoSinEscapar;
            return elementoDiv.innerHTML;
        },

        // =====================================================================
        // MIGRACIÓN COMPLETA DEL SITIO
        // =====================================================================

        /**
         * Inicializa eventos de migración
         */
        initMigration: function() {
            // Exportar sitio completo
            $('#flavor-full-export-form').on('submit', this.handleFullExport.bind(this));

            // Dropzone de migración
            this.initMigrationDropzone();

            // Buscar/Reemplazar
            $('#flavor-preview-replace').on('click', this.handlePreviewSearchReplace.bind(this));
            $('#flavor-search-replace-form').on('submit', this.handleApplySearchReplace.bind(this));
        },

        /**
         * Exporta el sitio completo
         */
        handleFullExport: function(evento) {
            evento.preventDefault();

            const $formulario = $(evento.currentTarget);
            const $botonSubmit = $formulario.find('button[type="submit"]');
            const textoBtnOriginal = $botonSubmit.html();

            const checkboxes = $formulario.find('input[name="export_full[]"]:checked');
            const opciones = checkboxes.map((indice, elemento) => $(elemento).val()).get();

            $botonSubmit.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Generando paquete...');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_export_full_site',
                    nonce: flavorExportImport.nonce,
                    include_database: opciones.includes('database') ? 'true' : 'false',
                    include_uploads: opciones.includes('uploads') ? 'true' : 'false',
                    include_plugins: opciones.includes('plugins') ? 'true' : 'false',
                    include_themes: opciones.includes('themes') ? 'true' : 'false'
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice('success', respuesta.data.message);
                        // Mostrar enlace de descarga
                        const $enlaceDescarga = $(`
                            <div class="flavor-download-ready">
                                <p><strong>¡Paquete listo!</strong></p>
                                <a href="${respuesta.data.download_url}" class="button button-primary" download>
                                    <span class="dashicons dashicons-download"></span>
                                    Descargar (${respuesta.data.size})
                                </a>
                            </div>
                        `);
                        $formulario.after($enlaceDescarga);
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error de conexión al generar el paquete.');
                },
                complete: () => {
                    $botonSubmit.prop('disabled', false).html(textoBtnOriginal);
                }
            });
        },

        /**
         * Inicializa el dropzone de migración
         */
        initMigrationDropzone: function() {
            const $dropzone = $('#flavor-migration-dropzone');
            const $inputArchivo = $('#flavor-migration-file');

            if (!$dropzone.length) return;

            $dropzone.on('click', () => $inputArchivo.trigger('click'));

            $dropzone.on('dragover dragenter', (evento) => {
                evento.preventDefault();
                evento.stopPropagation();
                $dropzone.addClass('dragover');
            });

            $dropzone.on('dragleave dragend drop', (evento) => {
                evento.preventDefault();
                evento.stopPropagation();
                $dropzone.removeClass('dragover');
            });

            $dropzone.on('drop', (evento) => {
                const archivos = evento.originalEvent.dataTransfer.files;
                if (archivos.length) {
                    this.processMigrationFile(archivos[0]);
                }
            });

            $inputArchivo.on('change', (evento) => {
                if (evento.target.files.length) {
                    this.processMigrationFile(evento.target.files[0]);
                }
            });
        },

        /**
         * Procesa archivo de migración
         */
        processMigrationFile: function(archivo) {
            if (!archivo.name.endsWith('.zip')) {
                this.showNotice('error', 'Por favor, selecciona un archivo ZIP válido.');
                return;
            }

            const $dropzone = $('#flavor-migration-dropzone');
            $dropzone.html(`
                <div class="flavor-dropzone-uploading">
                    <span class="dashicons dashicons-update spin"></span>
                    <p>Subiendo ${archivo.name}...</p>
                    <progress value="0" max="100"></progress>
                </div>
            `);

            const formData = new FormData();
            formData.append('action', 'flavor_import_full_site');
            formData.append('nonce', flavorExportImport.nonce);
            formData.append('migration_file', archivo);

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (evento) => {
                        if (evento.lengthComputable) {
                            const porcentaje = Math.round((evento.loaded / evento.total) * 100);
                            $dropzone.find('progress').val(porcentaje);
                        }
                    });
                    return xhr;
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice('success', respuesta.data.message);
                        $dropzone.html(`
                            <div class="flavor-dropzone-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <p>Migración importada correctamente</p>
                                <ul>${respuesta.data.details.map(detalle => `<li>${detalle}</li>`).join('')}</ul>
                            </div>
                        `);
                    } else {
                        this.showNotice('error', respuesta.data.message);
                        this.resetMigrationDropzone();
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al subir el archivo.');
                    this.resetMigrationDropzone();
                }
            });
        },

        /**
         * Resetea el dropzone de migración
         */
        resetMigrationDropzone: function() {
            $('#flavor-migration-dropzone').html(`
                <div class="flavor-dropzone-content">
                    <span class="dashicons dashicons-upload"></span>
                    <p>Arrastra el paquete de migración (.zip) aquí</p>
                    <input type="file" id="flavor-migration-file" accept=".zip">
                </div>
            `);
            this.initMigrationDropzone();
        },

        /**
         * Previsualiza buscar/reemplazar
         */
        handlePreviewSearchReplace: function(evento) {
            evento.preventDefault();

            const urlAnterior = $('#old_url').val().trim();
            const urlNueva = $('#new_url').val().trim();

            if (!urlAnterior) {
                this.showNotice('error', 'Ingresa la URL anterior.');
                return;
            }

            const $boton = $(evento.currentTarget);
            const textoOriginal = $boton.text();
            $boton.prop('disabled', true).text('Analizando...');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_preview_search_replace',
                    nonce: flavorExportImport.nonce,
                    search: urlAnterior,
                    replace: urlNueva
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        const datos = respuesta.data;
                        let htmlPreview = `<div class="flavor-search-replace-preview">
                            <h4>Se encontraron ${datos.total} coincidencias:</h4>
                            <table class="widefat striped">
                                <thead><tr><th>Tabla</th><th>Columna</th><th>Coincidencias</th></tr></thead>
                                <tbody>
                                    ${datos.preview.map(fila => `<tr><td>${fila.tabla}</td><td>${fila.columna}</td><td>${fila.coincidencias}</td></tr>`).join('')}
                                </tbody>
                            </table>
                        </div>`;

                        $boton.after(htmlPreview);
                        $('#flavor-apply-replace').prop('disabled', datos.total === 0);
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al analizar la base de datos.');
                },
                complete: () => {
                    $boton.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Aplica buscar/reemplazar
         */
        handleApplySearchReplace: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Estás seguro? Esta acción modificará la base de datos. Se creará un backup automático antes de continuar.')) {
                return;
            }

            const urlAnterior = $('#old_url').val().trim();
            const urlNueva = $('#new_url').val().trim();

            const $botonSubmit = $('#flavor-apply-replace');
            const textoOriginal = $botonSubmit.text();
            $botonSubmit.prop('disabled', true).text('Aplicando cambios...');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_apply_search_replace',
                    nonce: flavorExportImport.nonce,
                    search: urlAnterior,
                    replace: urlNueva
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice('success', respuesta.data.message);
                        $('.flavor-search-replace-preview').remove();
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al aplicar los cambios.');
                },
                complete: () => {
                    $botonSubmit.prop('disabled', true).text(textoOriginal);
                }
            });
        },

        // =====================================================================
        // SISTEMA DE BACKUPS
        // =====================================================================

        /**
         * Inicializa eventos de backups
         */
        initBackups: function() {
            // Crear backup
            $('#flavor-create-backup-form').on('submit', this.handleCreateBackup.bind(this));

            // Configuración de backups programados
            $('#flavor-schedule-backup-form').on('submit', this.handleSaveBackupSchedule.bind(this));

            // Acciones en lista de backups
            $(document).on('click', '.flavor-backup-restore', this.handleRestoreBackup.bind(this));
            $(document).on('click', '.flavor-backup-download', this.handleDownloadBackup.bind(this));
            $(document).on('click', '.flavor-backup-delete', this.handleDeleteBackup.bind(this));
        },

        /**
         * Crea un backup manual
         */
        handleCreateBackup: function(evento) {
            evento.preventDefault();

            const $formulario = $(evento.currentTarget);
            const $botonSubmit = $formulario.find('button[type="submit"]');
            const textoOriginal = $botonSubmit.html();

            const checkboxes = $formulario.find('input[name="backup_type[]"]:checked');
            const opciones = checkboxes.map((indice, elemento) => $(elemento).val()).get();
            const nombreBackup = $formulario.find('#backup_name').val();

            $botonSubmit.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creando backup...');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_create_backup',
                    nonce: flavorExportImport.nonce,
                    backup_name: nombreBackup,
                    include_database: opciones.includes('database') ? 'true' : 'false',
                    include_uploads: opciones.includes('uploads') ? 'true' : 'false',
                    include_plugins: opciones.includes('plugins') ? 'true' : 'false',
                    include_themes: opciones.includes('themes') ? 'true' : 'false'
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice('success', respuesta.data.message);
                        // Recargar lista de backups
                        this.refreshBackupList();
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al crear el backup.');
                },
                complete: () => {
                    $botonSubmit.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        /**
         * Guarda configuración de backups programados
         */
        handleSaveBackupSchedule: function(evento) {
            evento.preventDefault();

            const $formulario = $(evento.currentTarget);
            const datos = {
                action: 'flavor_save_backup_schedule',
                nonce: flavorExportImport.nonce,
                backup_enabled: $formulario.find('[name="backup_frequency"]').val() !== 'disabled',
                backup_frequency: $formulario.find('[name="backup_frequency"]').val(),
                backup_retain: $formulario.find('[name="backup_retention"]').val(),
                backup_database: $formulario.find('[name="backup_database"]').is(':checked'),
                backup_uploads: $formulario.find('[name="backup_uploads"]').is(':checked'),
                backup_email: $formulario.find('[name="backup_email"]').val()
            };

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: datos,
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice('success', respuesta.data.message);
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al guardar la configuración.');
                }
            });
        },

        /**
         * Restaura un backup
         */
        handleRestoreBackup: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Estás seguro de restaurar este backup? Los datos actuales serán reemplazados.')) {
                return;
            }

            const backupId = $(evento.currentTarget).data('backup-id');
            const $fila = $(evento.currentTarget).closest('tr');

            $fila.addClass('restoring');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_restore_backup',
                    nonce: flavorExportImport.nonce,
                    backup_id: backupId,
                    restore_database: true,
                    restore_uploads: true
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        this.showNotice('success', respuesta.data.message);
                        // Recargar página para reflejar cambios
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al restaurar el backup.');
                },
                complete: () => {
                    $fila.removeClass('restoring');
                }
            });
        },

        /**
         * Descarga un backup
         */
        handleDownloadBackup: function(evento) {
            evento.preventDefault();

            const backupId = $(evento.currentTarget).data('backup-id');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_download_backup',
                    nonce: flavorExportImport.nonce,
                    backup_id: backupId
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        // Abrir enlace de descarga
                        window.location.href = respuesta.data.download_url;
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al obtener el backup.');
                }
            });
        },

        /**
         * Elimina un backup
         */
        handleDeleteBackup: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Estás seguro de eliminar este backup? Esta acción no se puede deshacer.')) {
                return;
            }

            const backupId = $(evento.currentTarget).data('backup-id');
            const $fila = $(evento.currentTarget).closest('tr');

            $.ajax({
                url: flavorExportImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_delete_backup',
                    nonce: flavorExportImport.nonce,
                    backup_id: backupId
                },
                success: (respuesta) => {
                    if (respuesta.success) {
                        $fila.fadeOut(300, function() { $(this).remove(); });
                        this.showNotice('success', respuesta.data.message);
                    } else {
                        this.showNotice('error', respuesta.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Error al eliminar el backup.');
                }
            });
        },

        /**
         * Refresca la lista de backups
         */
        refreshBackupList: function() {
            // Recargar sección de backups
            const $tablaBackups = $('#flavor-backups-table tbody');
            if ($tablaBackups.length) {
                location.reload();
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorExportImport.init();
        FlavorExportImport.initMigration();
        FlavorExportImport.initBackups();
    });

    // Estilos para el spinner de carga
    $('<style>')
        .text('.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery);
