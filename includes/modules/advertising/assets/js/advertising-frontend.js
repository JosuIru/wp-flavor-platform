/**
 * Advertising Frontend JavaScript
 * Flavor Chat IA - Publicidad Ética
 */

(function($) {
    'use strict';

    const FlavorAds = {
        ajaxurl: typeof flavorAdsData !== 'undefined' ? flavorAdsData.ajaxurl : '/wp-admin/admin-ajax.php',
        nonce: typeof flavorAdsData !== 'undefined' ? flavorAdsData.nonce : '',
        strings: typeof flavorAdsData !== 'undefined' ? flavorAdsData.strings : {},
    };

    /**
     * Inicialización
     */
    $(document).ready(function() {
        FlavorAds.init();
    });

    FlavorAds.init = function() {
        this.bindEvents();
        this.initDashboard();
    };

    /**
     * Vincular eventos
     */
    FlavorAds.bindEvents = function() {
        const self = this;

        // Formulario de crear anuncio
        $(document).on('submit', '#crear-anuncio-form', function(e) {
            e.preventDefault();
            self.crearAnuncio($(this));
        });

        // Pausar anuncio
        $(document).on('click', '.btn-pausar-anuncio', function(e) {
            e.preventDefault();
            const adId = $(this).data('ad-id');
            if (confirm(self.strings.confirmar || '¿Estás seguro?')) {
                self.pausarAnuncio(adId, $(this));
            }
        });

        // Reanudar anuncio
        $(document).on('click', '.btn-reanudar-anuncio', function(e) {
            e.preventDefault();
            const adId = $(this).data('ad-id');
            self.reanudarAnuncio(adId, $(this));
        });

        // Eliminar anuncio
        $(document).on('click', '.btn-eliminar-anuncio', function(e) {
            e.preventDefault();
            const adId = $(this).data('ad-id');
            if (confirm(self.strings.confirmar || '¿Estás seguro?')) {
                self.eliminarAnuncio(adId, $(this));
            }
        });

        // Cambiar periodo de estadísticas
        $(document).on('change', '.ads-periodo-selector', function() {
            self.cargarEstadisticas($(this).val());
        });
    };

    /**
     * Inicializar dashboard
     */
    FlavorAds.initDashboard = function() {
        if ($('.flavor-ads-dashboard').length) {
            this.cargarEstadisticas('month');
        }
    };

    /**
     * Crear anuncio
     */
    FlavorAds.crearAnuncio = function(form) {
        const self = this;
        const submitBtn = form.find('button[type="submit"]');

        submitBtn.addClass('loading').prop('disabled', true);

        const data = {
            action: 'flavor_ads_crear_campana',
            nonce: self.nonce,
            titulo: form.find('[name="titulo"]').val(),
            tipo: form.find('[name="tipo"]').val(),
            url_destino: form.find('[name="url_destino"]').val(),
            imagen: form.find('[name="imagen"]').val(),
            presupuesto: form.find('[name="presupuesto"]').val(),
        };

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                submitBtn.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast(response.data.message || 'Anuncio creado correctamente', 'success');
                    form[0].reset();

                    // Redirigir al dashboard después de 2 segundos
                    setTimeout(function() {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.delete('vista');
                        window.location.href = currentUrl.toString();
                    }, 2000);
                } else {
                    self.showToast(response.data || 'Error al crear el anuncio', 'error');
                }
            },
            error: function() {
                submitBtn.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Pausar anuncio
     */
    FlavorAds.pausarAnuncio = function(adId, button) {
        const self = this;

        button.addClass('loading').prop('disabled', true);

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_ads_pausar_campana',
                nonce: self.nonce,
                ad_id: adId
            },
            success: function(response) {
                button.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast('Anuncio pausado', 'success');
                    location.reload();
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            },
            error: function() {
                button.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Reanudar anuncio
     */
    FlavorAds.reanudarAnuncio = function(adId, button) {
        const self = this;

        button.addClass('loading').prop('disabled', true);

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_ads_reanudar_campana',
                nonce: self.nonce,
                ad_id: adId
            },
            success: function(response) {
                button.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast('Anuncio reanudado', 'success');
                    location.reload();
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            },
            error: function() {
                button.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Cargar estadísticas
     */
    FlavorAds.cargarEstadisticas = function(periodo) {
        const self = this;
        const container = $('.ads-stats-container');

        if (!container.length) return;

        container.html('<div class="ads-loading">Cargando...</div>');

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_ads_stats',
                nonce: self.nonce,
                periodo: periodo
            },
            success: function(response) {
                if (response.success) {
                    self.renderEstadisticas(response.data, container);
                } else {
                    container.html('<p>Error al cargar estadísticas</p>');
                }
            },
            error: function() {
                container.html('<p>Error de conexión</p>');
            }
        });
    };

    /**
     * Renderizar estadísticas
     */
    FlavorAds.renderEstadisticas = function(data, container) {
        const html = `
            <div class="ads-stats-grid">
                <div class="ads-stat-card">
                    <span class="ads-stat-valor">${data.impresiones.toLocaleString()}</span>
                    <span class="ads-stat-label">Impresiones</span>
                </div>
                <div class="ads-stat-card">
                    <span class="ads-stat-valor">${data.clics.toLocaleString()}</span>
                    <span class="ads-stat-label">Clics</span>
                </div>
                <div class="ads-stat-card">
                    <span class="ads-stat-valor">${data.ctr}%</span>
                    <span class="ads-stat-label">CTR</span>
                </div>
                <div class="ads-stat-card">
                    <span class="ads-stat-valor">${data.gasto.toFixed(2)}€</span>
                    <span class="ads-stat-label">Gasto</span>
                </div>
            </div>
        `;

        container.html(html);
    };

    /**
     * Mostrar toast
     */
    FlavorAds.showToast = function(message, type) {
        type = type || 'info';

        // Crear contenedor si no existe
        if (!$('.ads-toast-container').length) {
            $('body').append('<div class="ads-toast-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999;"></div>');
        }

        const bgColors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };

        const toast = $(`
            <div class="ads-toast" style="
                background: ${bgColors[type]};
                color: #fff;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                margin-top: 0.5rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            ">
                ${message}
            </div>
        `);

        $('.ads-toast-container').append(toast);

        // Auto-cerrar después de 4 segundos
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    };

    // Exponer globalmente
    window.FlavorAds = FlavorAds;

})(jQuery);
