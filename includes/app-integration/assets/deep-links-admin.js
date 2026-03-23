(function($) {
    'use strict';

    const config = window.flavorDeepLinks || {};
    const apiBase = (config.apiUrl || '').replace(/\/$/, '');
    let mediaFrame = null;
    let currentSlug = '';

    function apiRequest(path, options) {
        return fetch(apiBase + path, {
            method: options.method || 'GET',
            headers: Object.assign({
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce || ''
            }, options.headers || {}),
            body: options.body ? JSON.stringify(options.body) : undefined
        }).then(async function(response) {
            const data = await response.json().catch(function() { return null; });
            if (!response.ok) {
                const message = data && (data.message || (data.data && data.data.message)) || config.i18n?.error || 'Error';
                throw new Error(message);
            }
            return data;
        });
    }

    function openModal(selector) {
        $(selector).removeClass('flavor-dl-modal--hidden');
    }

    function closeModal(selector) {
        $(selector).addClass('flavor-dl-modal--hidden');
    }

    function closeAllModals() {
        $('.flavor-dl-modal').addClass('flavor-dl-modal--hidden');
    }

    function setColorSamples() {
        $('.flavor-dl-color-sample').each(function() {
            const color = $(this).data('color');
            if (color) {
                this.style.setProperty('--flavor-dl-color', color);
            }
        });
    }

    function resetForm() {
        const form = document.getElementById('flavor-dl-company-form');
        if (form) {
            form.reset();
        }

        currentSlug = '';
        $('#company-id').val('');
        $('#flavor-dl-modal-title').text(config.i18n?.addCompany || 'Nueva Empresa');
        $('#color-primario').wpColorPicker?.('color', '#3B82F6');
        $('#color-secundario').wpColorPicker?.('color', '#8B5CF6');
        $('#color-acento').wpColorPicker?.('color', '#10B981');
        $('#color-fondo').wpColorPicker?.('color', '#FFFFFF');
        $('#color-texto').wpColorPicker?.('color', '#1F2937');
        $('#color-error').wpColorPicker?.('color', '#EF4444');
        $('#color-exito').wpColorPicker?.('color', '#10B981');
        $('#color-advertencia').wpColorPicker?.('color', '#F59E0B');
        $('#company-activo').prop('checked', true);
        $('input[name="modulos_activos[]"]').prop('checked', false);
    }

    function fillForm(data) {
        currentSlug = data.slug || '';
        $('#company-id').val(currentSlug);
        $('#company-slug').val(data.slug || '');
        $('#company-nombre').val(data.nombre || '');
        $('#company-descripcion').val(data.descripcion || '');
        $('#company-logo').val(data.logo || '');
        $('#company-api-base').val(data.api_base || '');
        $('#config-tema').val(data.tema || 'light');
        $('#config-idioma').val(data.idioma || 'es');
        $('#company-activo').prop('checked', true);

        const colors = data.colores || {};
        const modules = data.modulos_activos || [];
        ['primario', 'secundario', 'acento', 'fondo', 'texto', 'error', 'exito', 'advertencia'].forEach(function(key) {
            const $field = $('#color-' + key);
            if ($field.length && colors[key]) {
                if ($field.data('wpWpColorPicker')) {
                    $field.wpColorPicker('color', colors[key]);
                } else {
                    $field.val(colors[key]);
                }
            }
        });

        $('input[name="modulos_activos[]"]').each(function() {
            $(this).prop('checked', modules.indexOf($(this).val()) !== -1);
        });

        $('#flavor-dl-modal-title').text(config.i18n?.editCompany || 'Editar Empresa');
    }

    function collectFormData() {
        return {
            slug: $('#company-slug').val().trim(),
            nombre: $('#company-nombre').val().trim(),
            descripcion: $('#company-descripcion').val().trim(),
            logo_url: $('#company-logo').val().trim(),
            api_base: $('#company-api-base').val().trim(),
            activo: $('#company-activo').is(':checked'),
            tema: $('#config-tema').val(),
            idioma: $('#config-idioma').val(),
            colores: {
                primario: $('#color-primario').val(),
                secundario: $('#color-secundario').val(),
                acento: $('#color-acento').val(),
                fondo: $('#color-fondo').val(),
                texto: $('#color-texto').val(),
                error: $('#color-error').val(),
                exito: $('#color-exito').val(),
                advertencia: $('#color-advertencia').val()
            },
            modulos_activos: $('input[name="modulos_activos[]"]:checked').map(function() {
                return this.value;
            }).get()
        };
    }

    function renderLinks(data) {
        const links = data.links || {};
        const $target = $('#flavor-dl-generated-links');
        const items = Object.keys(links).map(function(key) {
            const value = links[key];
            return '' +
                '<div class="flavor-dl-link-item">' +
                    '<strong>' + key + '</strong>' +
                    '<div class="flavor-dl-link-row">' +
                        '<code>' + $('<div>').text(value).html() + '</code>' +
                        '<button type="button" class="button flavor-dl-copy-link" data-link="' + $('<div>').text(value).html() + '">' + (config.i18n?.copied ? 'Copiar' : 'Copiar') + '</button>' +
                    '</div>' +
                '</div>';
        }).join('');

        $target
            .addClass('flavor-dl-generated-links')
            .html(items || '<p>No hay enlaces generados.</p>');
    }

    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.flavor-color-picker').wpColorPicker();
        }
    }

    function initMediaUploader() {
        $('.flavor-upload-logo').on('click', function(event) {
            event.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: config.i18n?.selectLogo || 'Seleccionar Logo',
                button: {
                    text: config.i18n?.useThisImage || 'Usar esta imagen'
                },
                multiple: false,
                library: { type: 'image' }
            });

            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                $('#company-logo').val(attachment.url);
            });

            mediaFrame.open();
        });
    }

    $(function() {
        setColorSamples();
        initColorPickers();
        initMediaUploader();

        $(document).on('click', '.flavor-add-company', function() {
            resetForm();
            openModal('#flavor-dl-modal');
        });

        $(document).on('click', '.flavor-dl-modal-close, .flavor-dl-modal-overlay, #flavor-dl-cancel, #flavor-dl-close-links', function() {
            closeAllModals();
        });

        $(document).on('click', '.flavor-edit-company', function() {
            const slug = $(this).data('slug');
            if (!slug) {
                return;
            }

            apiRequest('/config/' + slug, { method: 'GET' })
                .then(function(data) {
                    resetForm();
                    fillForm(data);
                    openModal('#flavor-dl-modal');
                })
                .catch(function(error) {
                    window.alert(error.message);
                });
        });

        $('#flavor-dl-save').on('click', function() {
            const payload = collectFormData();
            const $button = $(this);
            const originalText = $button.text();

            $button.prop('disabled', true).text(config.i18n?.saving || 'Guardando...');

            apiRequest('/config', { method: 'POST', body: payload })
                .then(function() {
                    closeAllModals();
                    window.location.reload();
                })
                .catch(function(error) {
                    window.alert(error.message);
                })
                .finally(function() {
                    $button.prop('disabled', false).text(originalText);
                });
        });

        $(document).on('click', '.flavor-delete-company', function() {
            const slug = $(this).data('slug');
            if (!slug || !window.confirm(config.i18n?.confirmDelete || '')) {
                return;
            }

            apiRequest('/config/' + slug, { method: 'DELETE' })
                .then(function() {
                    window.location.reload();
                })
                .catch(function(error) {
                    window.alert(error.message);
                });
        });

        $(document).on('click', '.flavor-generate-links', function() {
            const slug = $(this).data('slug');
            if (!slug) {
                return;
            }

            apiRequest('/generate-link/' + slug, { method: 'GET' })
                .then(function(data) {
                    renderLinks(data);
                    openModal('#flavor-dl-links-modal');
                })
                .catch(function(error) {
                    window.alert(error.message);
                });
        });

        $(document).on('click', '.flavor-dl-copy-link', function() {
            const link = $(this).data('link') || '';
            navigator.clipboard?.writeText(link);
        });
    });
})(jQuery);
