/**
 * Visual Builder Pro - App Module: Unsplash
 * Búsqueda e inserción de imágenes desde Unsplash
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppUnsplash = {
    showUnsplashModal: false,
    unsplashConfigured: true,
    unsplashQuery: '',
    unsplashOrientation: '',
    unsplashImages: [],
    unsplashPage: 1,
    unsplashTotalPages: 0,
    isSearchingUnsplash: false,
    unsplashTargetElement: null,

    openUnsplash: function(targetElement) {
        this.unsplashTargetElement = targetElement || null;
        this.showUnsplashModal = true;
        this.checkUnsplashStatus();
    },

    checkUnsplashStatus: function() {
        var self = this;
        fetch(VBP_Config.restUrl + 'unsplash/status', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            self.unsplashConfigured = data.configured || false;
        })
        .catch(function() {
            self.unsplashConfigured = false;
        });
    },

    searchUnsplash: function() {
        if (!this.unsplashQuery.trim()) return;

        var self = this;
        this.isSearchingUnsplash = true;
        this.unsplashPage = 1;

        var url = VBP_Config.restUrl + 'unsplash/search?' + new URLSearchParams({
            query: this.unsplashQuery,
            page: this.unsplashPage,
            per_page: 20,
            orientation: this.unsplashOrientation
        });

        fetch(url, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.error) {
                throw new Error(data.error);
            }
            self.unsplashImages = data.results || [];
            self.unsplashTotalPages = data.totalPages || 0;
        })
        .catch(function(error) {
            self.showNotification('Error buscando imágenes: ' + error.message, 'error');
            self.unsplashImages = [];
        })
        .finally(function() {
            self.isSearchingUnsplash = false;
        });
    },

    unsplashNextPage: function() {
        if (this.unsplashPage >= this.unsplashTotalPages) return;
        this.unsplashPage++;
        this.loadUnsplashPage();
    },

    unsplashPrevPage: function() {
        if (this.unsplashPage <= 1) return;
        this.unsplashPage--;
        this.loadUnsplashPage();
    },

    loadUnsplashPage: function() {
        var self = this;
        this.isSearchingUnsplash = true;

        var url = VBP_Config.restUrl + 'unsplash/search?' + new URLSearchParams({
            query: this.unsplashQuery,
            page: this.unsplashPage,
            per_page: 20,
            orientation: this.unsplashOrientation
        });

        fetch(url, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            self.unsplashImages = data.results || [];
        })
        .catch(function() {
            self.showNotification('Error cargando página', 'error');
        })
        .finally(function() {
            self.isSearchingUnsplash = false;
        });
    },

    selectUnsplashImage: function(image) {
        fetch(VBP_Config.restUrl + 'unsplash/photos/' + image.id + '/download', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        }).catch(function() {
            return null;
        });

        if (this.unsplashTargetElement) {
            var store = Alpine.store('vbp');
            var element = store.getElement(this.unsplashTargetElement);
            if (element) {
                var data = JSON.parse(JSON.stringify(element.data || {}));
                data.src = image.urls.regular;
                data.alt = image.description || 'Imagen de ' + image.user.name + ' en Unsplash';
                data.unsplashId = image.id;
                data.unsplashAuthor = image.user.name;
                data.unsplashAuthorUrl = image.user.link;
                store.updateElement(this.unsplashTargetElement, { data: data });
                this.showNotification('Imagen actualizada', 'success');
            }
        } else {
            var imageStore = Alpine.store('vbp');
            imageStore.addElement('image', {
                src: image.urls.regular,
                alt: image.description || 'Imagen de ' + image.user.name + ' en Unsplash',
                unsplashId: image.id,
                unsplashAuthor: image.user.name,
                unsplashAuthorUrl: image.user.link
            });
            this.showNotification('Imagen insertada', 'success');
        }

        this.showUnsplashModal = false;
        this.unsplashTargetElement = null;
    }
};
