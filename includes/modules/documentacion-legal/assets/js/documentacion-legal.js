/**
 * JavaScript del modulo Documentacion Legal
 */
(function($) {
    'use strict';

    const FlavorDocLegal = {
        config: window.flavorDocLegalConfig || {},

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('submit', '.flavor-docs-buscador-form', this.handleBuscar.bind(this));
            $(document).on('click', '.flavor-btn-guardar', this.handleGuardar.bind(this));
            $(document).on('click', '.flavor-btn-quitar-guardado', this.handleQuitarGuardado.bind(this));
            $(document).on('submit', '.flavor-doc-subir-form', this.handleSubir.bind(this));
            $(document).on('change', '.flavor-filtro-tipo, .flavor-filtro-categoria', this.handleFiltrar.bind(this));
        },

        handleBuscar: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $resultados = $('.flavor-docs-resultados');
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Buscando...');

            $.post(this.config.ajaxUrl, {
                action: 'documentacion_legal_buscar',
                nonce: this.config.nonce,
                q: $form.find('[name="q"]').val(),
                tipo: $form.find('[name="tipo"]').val(),
                categoria: $form.find('[name="categoria"]').val()
            })
            .done(function(response) {
                if (response.success && response.data.documentos) {
                    FlavorDocLegal.renderResultados($resultados, response.data.documentos);
                } else {
                    $resultados.html('<p class="flavor-empty">No se encontraron documentos.</p>');
                }
            })
            .fail(function() {
                $resultados.html('<p class="flavor-error">Error en la busqueda.</p>');
            })
            .always(function() {
                $btn.prop('disabled', false).text('Buscar');
            });
        },

        renderResultados: function($container, documentos) {
            if (documentos.length === 0) {
                $container.html('<p class="flavor-empty">No se encontraron documentos.</p>');
                return;
            }

            let html = '<div class="flavor-docs-grid">';
            documentos.forEach(function(doc) {
                html += FlavorDocLegal.renderDocCard(doc);
            });
            html += '</div>';

            $container.html(html);
        },

        renderDocCard: function(doc) {
            const verificado = doc.verificado ? '<span class="flavor-verificado-badge"><span class="dashicons dashicons-yes"></span> Verificado</span>' : '';

            return `
                <article class="flavor-doc-card ${doc.verificado ? 'verificado' : ''}">
                    <div class="flavor-doc-header">
                        <div class="flavor-doc-icono">
                            <span class="dashicons dashicons-media-document"></span>
                        </div>
                        <div class="flavor-doc-meta">
                            <span class="flavor-doc-tipo">${doc.tipo}</span>
                            <h3 class="flavor-doc-titulo">
                                <a href="?documento_id=${doc.id}">${doc.titulo}</a>
                            </h3>
                        </div>
                    </div>
                    <div class="flavor-doc-footer">
                        <div class="flavor-doc-stats">
                            <span class="flavor-doc-stat">
                                <span class="dashicons dashicons-download"></span>
                                ${doc.descargas || 0}
                            </span>
                        </div>
                        ${verificado}
                    </div>
                </article>
            `;
        },

        handleGuardar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const documentoId = $btn.data('documento-id');

            $btn.prop('disabled', true);

            $.post(this.config.ajaxUrl, {
                action: 'documentacion_legal_guardar',
                nonce: this.config.nonce,
                documento_id: documentoId
            })
            .done(function(response) {
                if (response.success) {
                    $btn.replaceWith('<span class="flavor-guardado">Guardado</span>');
                } else {
                    alert(response.data.error || 'Error al guardar');
                    $btn.prop('disabled', false);
                }
            })
            .fail(function() {
                alert('Error de conexion');
                $btn.prop('disabled', false);
            });
        },

        handleQuitarGuardado: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const documentoId = $btn.data('documento-id');

            if (!confirm('Quitar de guardados?')) return;

            $btn.prop('disabled', true);

            $.post(this.config.ajaxUrl, {
                action: 'documentacion_legal_quitar_guardado',
                nonce: this.config.nonce,
                documento_id: documentoId
            })
            .done(function(response) {
                if (response.success) {
                    $btn.closest('.flavor-doc-card').fadeOut();
                } else {
                    alert(response.data.error || 'Error');
                    $btn.prop('disabled', false);
                }
            });
        },

        handleSubir: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Subiendo...');

            const formData = new FormData($form[0]);
            formData.append('action', 'documentacion_legal_subir');
            formData.append('nonce', this.config.nonce);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                if (response.success) {
                    $form.html('<div class="flavor-mensaje-exito">' + response.data.mensaje + '</div>');
                } else {
                    alert(response.data.error || 'Error al subir');
                    $btn.prop('disabled', false).text('Subir documento');
                }
            })
            .fail(function() {
                alert('Error de conexion');
                $btn.prop('disabled', false).text('Subir documento');
            });
        },

        handleFiltrar: function(e) {
            const tipo = $('.flavor-filtro-tipo').val();
            const categoria = $('.flavor-filtro-categoria').val();

            const params = new URLSearchParams(window.location.search);
            if (tipo) params.set('tipo', tipo);
            else params.delete('tipo');
            if (categoria) params.set('categoria', categoria);
            else params.delete('categoria');

            window.location.search = params.toString();
        }
    };

    $(document).ready(function() {
        FlavorDocLegal.init();
    });

    window.FlavorDocLegal = FlavorDocLegal;

})(jQuery);
