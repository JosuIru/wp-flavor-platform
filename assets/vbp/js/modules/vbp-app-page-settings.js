/**
 * Visual Builder Pro - App Module: Page Settings
 * Gestión de configuración SEO/social/código por página
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppPageSettings = {
    showPageSettings: false,
    pageSettingsTab: 'general',
    pageSettings: {
        seoTitle: '',
        seoDescription: '',
        ogImage: '',
        ogTitle: '',
        ogDescription: '',
        customCss: '',
        customJs: '',
        pageClass: '',
        pageId: ''
    },

    openPageSettings: function() {
        this.showPageSettings = true;
    },

    savePageSettings: function() {
        var settings = Alpine.store('vbp').settings;
        settings.pageSettings = this.pageSettings;
        Alpine.store('vbp').settings = settings;
        Alpine.store('vbp').isDirty = true;
        this.showPageSettings = false;
        this.showNotification('Configuración de página guardada', 'success');
    },

    selectOgImage: function() {
        var self = this;
        var isValidOgImageUrl = function(url) {
            if (!url || typeof url !== 'string') return false;

            var normalized = url.trim().toLowerCase();
            if (!(normalized.startsWith('/') || normalized.startsWith('http://') || normalized.startsWith('https://'))) {
                return false;
            }

            return /\.(png|jpe?g|gif|webp|svg)(\?.*)?(#.*)?$/.test(normalized);
        };

        if (typeof wp !== 'undefined' && wp.media) {
            var mediaUploader = wp.media({
                title: 'Seleccionar imagen para redes sociales',
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                self.pageSettings.ogImage = attachment.url;
            });

            mediaUploader.open();
            return;
        }

        var url = prompt('La biblioteca de medios no está disponible. Introduce la URL de la imagen OG:');
        if (url) {
            if (isValidOgImageUrl(url)) {
                self.pageSettings.ogImage = url;
                if (typeof self.showNotification === 'function') {
                    self.showNotification('Imagen OG aplicada', 'success');
                }
            } else {
                if (typeof self.showNotification === 'function') {
                    self.showNotification('URL no válida para la imagen OG', 'error');
                }
            }
        }
    }
};
