/**
 * Sistema de Tracking de Anuncios
 * @package FlavorChatIA
 */

(function ($) {
	'use strict';

	const FlavorAdTracking = {
		/**
         * Inicializar
         */
		init: function () {
			this.setupIntersectionObserver();
			this.setupClickTracking();
		},

		/**
         * Configurar observador de intersección para impresiones
         */
		setupIntersectionObserver: function () {
			const options = {
				root: null,
				rootMargin: '0px',
				threshold: 0.5 // El anuncio debe ser 50% visible
			};

			const observer = new IntersectionObserver((entries) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						const adElement = entry.target;
						const adId = adElement.dataset.adId;

						// Solo trackear una vez
						if (!adElement.dataset.tracked) {
							this.trackImpression(adId);
							adElement.dataset.tracked = 'true';
							observer.unobserve(adElement);
						}
					}
				});
			}, options);

			// Observar todos los anuncios
			document.querySelectorAll('.flavor-ad[data-ad-id]').forEach(ad => {
				observer.observe(ad);
			});
		},

		/**
         * Configurar tracking de clicks
         */
		setupClickTracking: function () {
			$(document).on('click', '.flavor-ad a', function (e) {
				const $ad = $(this).closest('.flavor-ad');
				const adId = $ad.data('ad-id');

				if (adId) {
					FlavorAdTracking.trackClick(adId);
				}
			});
		},

		/**
         * Trackear impresión
         */
		trackImpression: function (adId) {
			$.ajax({
				url: flavorAds.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_ad_impression',
					nonce: flavorAds.nonce,
					ad_id: adId
				}
			});
		},

		/**
         * Trackear click
         */
		trackClick: function (adId) {
			$.ajax({
				url: flavorAds.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_ad_click',
					nonce: flavorAds.nonce,
					ad_id: adId
				}
			});
		}
	};

	// Inicializar cuando el DOM esté listo
	$(document).ready(function () {
		FlavorAdTracking.init();
	});

})(jQuery);
