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
        }
    }
};
