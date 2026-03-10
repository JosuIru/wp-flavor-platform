/**
 * PostCSS Configuration
 *
 * Configura el procesamiento de CSS para Flavor Platform.
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

module.exports = (ctx) => ({
    plugins: {
        // Procesa @import e inline los archivos CSS
        'postcss-import': {},

        // Añade prefijos de vendor automáticamente
        'autoprefixer': {
            overrideBrowserslist: [
                '> 1%',
                'last 2 versions',
                'not dead'
            ]
        },

        // Minifica CSS en producción
        ...(ctx.env === 'production' ? {
            'cssnano': {
                preset: ['default', {
                    discardComments: {
                        removeAll: true
                    },
                    normalizeWhitespace: true,
                    minifyFontValues: true,
                    minifyGradients: true
                }]
            }
        } : {})
    }
});
