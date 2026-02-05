/**
 * Advertising Tracking JavaScript
 * Flavor Chat IA - Seguimiento de impresiones y clics
 */

(function($) {
    'use strict';

    const AdsTracking = {
        ajaxurl: typeof flavorAdsTracking !== 'undefined' ? flavorAdsTracking.ajaxurl : '/wp-admin/admin-ajax.php',
        trackedImpressions: [],
        observer: null,
    };

    /**
     * Inicialización
     */
    $(document).ready(function() {
        AdsTracking.init();
    });

    AdsTracking.init = function() {
        this.setupIntersectionObserver();
        this.bindClickTracking();
        this.trackVisibleAds();
    };

    /**
     * Configurar Intersection Observer para tracking de impresiones
     */
    AdsTracking.setupIntersectionObserver = function() {
        const self = this;

        if (!('IntersectionObserver' in window)) {
            // Fallback para navegadores sin soporte
            this.trackAllAdsImmediately();
            return;
        }

        this.observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const adElement = entry.target;
                    const adId = adElement.dataset.adId;

                    if (adId && self.trackedImpressions.indexOf(adId) === -1) {
                        self.trackImpression(adId);
                        self.trackedImpressions.push(adId);
                    }
                }
            });
        }, {
            threshold: 0.5, // 50% del anuncio debe ser visible
            rootMargin: '0px'
        });

        // Observar todos los anuncios
        document.querySelectorAll('.flavor-ad[data-ad-id]').forEach(function(ad) {
            self.observer.observe(ad);
        });
    };

    /**
     * Vincular tracking de clics
     */
    AdsTracking.bindClickTracking = function() {
        const self = this;

        $(document).on('click', '.flavor-ad[data-ad-id] a, .flavor-ad[data-ad-id] .flavor-ad-link', function(e) {
            const adElement = $(this).closest('.flavor-ad');
            const adId = adElement.data('ad-id');

            if (adId) {
                self.trackClick(adId);
            }
        });
    };

    /**
     * Rastrear anuncios visibles al cargar
     */
    AdsTracking.trackVisibleAds = function() {
        const self = this;

        // Pequeño delay para asegurar que el DOM está listo
        setTimeout(function() {
            document.querySelectorAll('.flavor-ad[data-ad-id]').forEach(function(ad) {
                if (self.isElementInViewport(ad)) {
                    const adId = ad.dataset.adId;
                    if (adId && self.trackedImpressions.indexOf(adId) === -1) {
                        self.trackImpression(adId);
                        self.trackedImpressions.push(adId);
                    }
                }
            });
        }, 500);
    };

    /**
     * Fallback: Rastrear todos los anuncios inmediatamente
     */
    AdsTracking.trackAllAdsImmediately = function() {
        const self = this;

        document.querySelectorAll('.flavor-ad[data-ad-id]').forEach(function(ad) {
            const adId = ad.dataset.adId;
            if (adId && self.trackedImpressions.indexOf(adId) === -1) {
                self.trackImpression(adId);
                self.trackedImpressions.push(adId);
            }
        });
    };

    /**
     * Verificar si un elemento está en el viewport
     */
    AdsTracking.isElementInViewport = function(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };

    /**
     * Enviar tracking de impresión
     */
    AdsTracking.trackImpression = function(adId) {
        const self = this;

        // Usar sendBeacon si está disponible (más fiable)
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('action', 'flavor_ads_track_impression');
            formData.append('ad_id', adId);
            navigator.sendBeacon(self.ajaxurl, formData);
        } else {
            // Fallback a AJAX
            $.ajax({
                url: self.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_ads_track_impression',
                    ad_id: adId
                },
                async: true
            });
        }
    };

    /**
     * Enviar tracking de clic
     */
    AdsTracking.trackClick = function(adId) {
        const self = this;

        // Usar sendBeacon para asegurar que se envía antes de la navegación
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('action', 'flavor_ads_track_click');
            formData.append('ad_id', adId);
            navigator.sendBeacon(self.ajaxurl, formData);
        } else {
            // Fallback síncrono para asegurar tracking
            $.ajax({
                url: self.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_ads_track_click',
                    ad_id: adId
                },
                async: false
            });
        }
    };

    /**
     * Método público para observar nuevos anuncios (para contenido dinámico)
     */
    AdsTracking.observeNewAd = function(element) {
        if (this.observer && element.dataset.adId) {
            this.observer.observe(element);
        }
    };

    // Exponer globalmente
    window.AdsTracking = AdsTracking;

})(jQuery);
