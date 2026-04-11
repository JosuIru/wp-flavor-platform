/**
 * VBP Benchmark Configuration
 *
 * Defines benchmark scenarios, expected metrics, and competitor baselines.
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

'use strict';

/**
 * Benchmark scenario definitions.
 * Each scenario represents a typical page-building task.
 */
const BENCHMARKS = {
    // Benchmark 1: Landing simple
    'landing-simple': {
        id: 'landing-simple',
        name: 'Landing Simple',
        description: 'Hero + 3 features + CTA',
        category: 'basic',
        difficulty: 'beginner',
        steps: [
            { id: 'add-hero', action: 'add-section', type: 'hero', description: 'Añadir sección hero' },
            { id: 'edit-title', action: 'edit-text', selector: 'h1', value: 'Título Principal', description: 'Editar título hero' },
            { id: 'edit-subtitle', action: 'edit-text', selector: 'p.subtitle', value: 'Subtítulo descriptivo', description: 'Editar subtítulo' },
            { id: 'add-cta-button', action: 'add-button', text: 'Comenzar', style: 'primary', description: 'Añadir botón CTA' },
            { id: 'add-features', action: 'add-section', type: 'features-3col', description: 'Añadir sección features' },
            { id: 'edit-feature-1', action: 'edit-feature', index: 0, title: 'Característica 1', description: 'Editar feature 1' },
            { id: 'edit-feature-2', action: 'edit-feature', index: 1, title: 'Característica 2', description: 'Editar feature 2' },
            { id: 'edit-feature-3', action: 'edit-feature', index: 2, title: 'Característica 3', description: 'Editar feature 3' },
            { id: 'add-cta-section', action: 'add-section', type: 'cta', description: 'Añadir sección CTA final' },
            { id: 'customize-cta', action: 'edit-text', selector: '.cta-title', value: '¿Listo para empezar?', description: 'Personalizar CTA' },
            { id: 'save', action: 'save', description: 'Guardar página' }
        ],
        expectedMetrics: {
            time: { min: 60, target: 120, max: 300 },      // segundos
            clicks: { min: 15, target: 25, max: 50 },
            keystrokes: { min: 50, target: 100, max: 200 },
            errors: { min: 0, target: 0, max: 2 }
        }
    },

    // Benchmark 2: Home corporativa
    'home-corporate': {
        id: 'home-corporate',
        name: 'Home Corporativa',
        description: 'Header + Hero + About + Services + Team + Testimonials + Contact + Footer',
        category: 'intermediate',
        difficulty: 'intermediate',
        steps: [
            { id: 'add-header', action: 'add-section', type: 'header-nav', description: 'Añadir header con navegación' },
            { id: 'config-nav', action: 'configure-menu', items: 5, description: 'Configurar menú navegación' },
            { id: 'add-hero-video', action: 'add-section', type: 'hero-video', description: 'Añadir hero con video' },
            { id: 'edit-hero-content', action: 'edit-content', description: 'Editar contenido hero' },
            { id: 'add-about', action: 'add-section', type: 'about-2col', description: 'Añadir sección about' },
            { id: 'edit-about', action: 'edit-content', description: 'Editar contenido about' },
            { id: 'add-services', action: 'add-section', type: 'services-grid', description: 'Añadir grid de servicios' },
            { id: 'edit-services', action: 'edit-features', count: 6, description: 'Editar 6 servicios' },
            { id: 'add-team', action: 'add-section', type: 'team-cards', description: 'Añadir equipo' },
            { id: 'edit-team', action: 'edit-features', count: 4, description: 'Editar 4 miembros' },
            { id: 'add-testimonials', action: 'add-section', type: 'testimonials-slider', description: 'Añadir testimonios slider' },
            { id: 'edit-testimonials', action: 'edit-features', count: 3, description: 'Editar 3 testimonios' },
            { id: 'add-contact', action: 'add-section', type: 'contact-form', description: 'Añadir formulario contacto' },
            { id: 'config-form', action: 'configure-form', fields: 5, description: 'Configurar campos formulario' },
            { id: 'add-footer', action: 'add-section', type: 'footer', description: 'Añadir footer' },
            { id: 'edit-footer', action: 'edit-content', description: 'Editar contenido footer' },
            { id: 'customize-colors', action: 'customize-colors', description: 'Personalizar colores globales' },
            { id: 'save', action: 'save', description: 'Guardar página' }
        ],
        expectedMetrics: {
            time: { min: 180, target: 300, max: 600 },
            clicks: { min: 40, target: 60, max: 100 },
            keystrokes: { min: 200, target: 400, max: 600 },
            errors: { min: 0, target: 0, max: 3 }
        }
    },

    // Benchmark 3: Página compleja
    'page-complex': {
        id: 'page-complex',
        name: 'Página Compleja',
        description: 'Múltiples secciones, animaciones, responsive, símbolos',
        category: 'advanced',
        difficulty: 'advanced',
        steps: [
            { id: 'add-sections', action: 'add-sections', count: 10, description: 'Añadir 10 secciones variadas' },
            { id: 'organize-layout', action: 'organize-layout', description: 'Organizar layout de secciones' },
            { id: 'add-animations', action: 'add-animations', count: 5, description: 'Añadir 5 animaciones' },
            { id: 'config-animations', action: 'configure-animations', description: 'Configurar parámetros animaciones' },
            { id: 'create-symbol', action: 'create-symbol', name: 'Card Reutilizable', description: 'Crear símbolo reutilizable' },
            { id: 'insert-symbols', action: 'insert-symbol-instances', count: 3, description: 'Insertar 3 instancias del símbolo' },
            { id: 'config-desktop', action: 'configure-responsive', breakpoint: 'desktop', description: 'Configurar vista desktop' },
            { id: 'config-tablet', action: 'configure-responsive', breakpoint: 'tablet', description: 'Configurar vista tablet' },
            { id: 'config-mobile', action: 'configure-responsive', breakpoint: 'mobile', description: 'Configurar vista mobile' },
            { id: 'add-interactions', action: 'add-interactions', count: 3, description: 'Añadir 3 interacciones hover/click' },
            { id: 'config-interactions', action: 'configure-interactions', description: 'Configurar comportamiento interacciones' },
            { id: 'preview-check', action: 'preview', description: 'Verificar preview' },
            { id: 'save', action: 'save', description: 'Guardar página' }
        ],
        expectedMetrics: {
            time: { min: 300, target: 480, max: 900 },
            clicks: { min: 80, target: 120, max: 200 },
            keystrokes: { min: 300, target: 500, max: 800 },
            errors: { min: 0, target: 1, max: 5 }
        }
    },

    // Benchmark 4: Blog/Artículo
    'blog-article': {
        id: 'blog-article',
        name: 'Artículo de Blog',
        description: 'Header + Hero artículo + Contenido + Sidebar + Related + Footer',
        category: 'content',
        difficulty: 'intermediate',
        steps: [
            { id: 'add-article-header', action: 'add-section', type: 'article-header', description: 'Añadir cabecera artículo' },
            { id: 'set-featured-image', action: 'set-image', type: 'featured', description: 'Establecer imagen destacada' },
            { id: 'add-content-area', action: 'add-section', type: 'content-sidebar', description: 'Añadir área contenido + sidebar' },
            { id: 'add-text-blocks', action: 'add-text-blocks', count: 5, description: 'Añadir bloques de texto' },
            { id: 'add-images', action: 'add-images', count: 3, description: 'Añadir imágenes en contenido' },
            { id: 'add-quote', action: 'add-element', type: 'blockquote', description: 'Añadir cita destacada' },
            { id: 'config-sidebar', action: 'configure-sidebar', widgets: 3, description: 'Configurar sidebar' },
            { id: 'add-author-box', action: 'add-section', type: 'author-box', description: 'Añadir caja autor' },
            { id: 'add-related', action: 'add-section', type: 'related-posts', description: 'Añadir posts relacionados' },
            { id: 'add-comments', action: 'add-section', type: 'comments', description: 'Añadir sección comentarios' },
            { id: 'save', action: 'save', description: 'Guardar artículo' }
        ],
        expectedMetrics: {
            time: { min: 120, target: 200, max: 400 },
            clicks: { min: 30, target: 45, max: 80 },
            keystrokes: { min: 150, target: 300, max: 500 },
            errors: { min: 0, target: 0, max: 2 }
        }
    },

    // Benchmark 5: E-commerce/Producto
    'ecommerce-product': {
        id: 'ecommerce-product',
        name: 'Página de Producto',
        description: 'Galería + Info + Tabs + Related + Reviews',
        category: 'ecommerce',
        difficulty: 'intermediate',
        steps: [
            { id: 'add-product-gallery', action: 'add-section', type: 'product-gallery', description: 'Añadir galería producto' },
            { id: 'upload-images', action: 'upload-images', count: 5, description: 'Subir 5 imágenes producto' },
            { id: 'add-product-info', action: 'add-section', type: 'product-info', description: 'Añadir info producto' },
            { id: 'edit-product-details', action: 'edit-product', description: 'Editar detalles producto' },
            { id: 'add-tabs', action: 'add-section', type: 'product-tabs', description: 'Añadir tabs información' },
            { id: 'edit-tab-description', action: 'edit-tab', index: 0, description: 'Editar tab descripción' },
            { id: 'edit-tab-specs', action: 'edit-tab', index: 1, description: 'Editar tab especificaciones' },
            { id: 'add-related', action: 'add-section', type: 'related-products', description: 'Añadir productos relacionados' },
            { id: 'add-reviews', action: 'add-section', type: 'reviews', description: 'Añadir sección reviews' },
            { id: 'save', action: 'save', description: 'Guardar página producto' }
        ],
        expectedMetrics: {
            time: { min: 150, target: 240, max: 450 },
            clicks: { min: 35, target: 55, max: 90 },
            keystrokes: { min: 100, target: 200, max: 350 },
            errors: { min: 0, target: 0, max: 2 }
        }
    },

    // Benchmark 6: Portfolio/Galería
    'portfolio-gallery': {
        id: 'portfolio-gallery',
        name: 'Portfolio/Galería',
        description: 'Hero + Filtros + Grid masonry + Lightbox + CTA',
        category: 'creative',
        difficulty: 'intermediate',
        steps: [
            { id: 'add-portfolio-hero', action: 'add-section', type: 'portfolio-hero', description: 'Añadir hero portfolio' },
            { id: 'add-filters', action: 'add-element', type: 'category-filters', description: 'Añadir filtros categoría' },
            { id: 'config-filters', action: 'configure-filters', categories: 4, description: 'Configurar 4 categorías' },
            { id: 'add-masonry-grid', action: 'add-section', type: 'masonry-grid', description: 'Añadir grid masonry' },
            { id: 'add-portfolio-items', action: 'add-portfolio-items', count: 12, description: 'Añadir 12 items portfolio' },
            { id: 'config-lightbox', action: 'configure-lightbox', description: 'Configurar lightbox' },
            { id: 'add-load-more', action: 'add-element', type: 'load-more', description: 'Añadir botón cargar más' },
            { id: 'add-cta', action: 'add-section', type: 'cta', description: 'Añadir CTA contacto' },
            { id: 'save', action: 'save', description: 'Guardar portfolio' }
        ],
        expectedMetrics: {
            time: { min: 180, target: 280, max: 500 },
            clicks: { min: 45, target: 70, max: 120 },
            keystrokes: { min: 80, target: 150, max: 250 },
            errors: { min: 0, target: 0, max: 2 }
        }
    }
};

/**
 * Competitor baseline metrics for comparison.
 * Based on typical usage patterns and community benchmarks.
 */
const COMPETITORS = {
    'elementor': {
        id: 'elementor',
        name: 'Elementor Pro',
        version: '3.x',
        category: 'page-builder',
        benchmarks: {
            'landing-simple': { avgTime: 180, avgClicks: 35, avgKeystrokes: 120 },
            'home-corporate': { avgTime: 420, avgClicks: 80, avgKeystrokes: 450 },
            'page-complex': { avgTime: 720, avgClicks: 150, avgKeystrokes: 600 },
            'blog-article': { avgTime: 280, avgClicks: 55, avgKeystrokes: 350 },
            'ecommerce-product': { avgTime: 320, avgClicks: 70, avgKeystrokes: 250 },
            'portfolio-gallery': { avgTime: 380, avgClicks: 90, avgKeystrokes: 200 }
        },
        strengths: ['Amplia biblioteca widgets', 'Gran comunidad', 'Theme builder'],
        weaknesses: ['Performance', 'Complejidad', 'Lock-in']
    },
    'gutenberg': {
        id: 'gutenberg',
        name: 'Gutenberg + Patterns',
        version: 'WP 6.x',
        category: 'native',
        benchmarks: {
            'landing-simple': { avgTime: 240, avgClicks: 45, avgKeystrokes: 150 },
            'home-corporate': { avgTime: 540, avgClicks: 100, avgKeystrokes: 550 },
            'page-complex': { avgTime: 900, avgClicks: 180, avgKeystrokes: 750 },
            'blog-article': { avgTime: 200, avgClicks: 40, avgKeystrokes: 300 },
            'ecommerce-product': { avgTime: 400, avgClicks: 85, avgKeystrokes: 300 },
            'portfolio-gallery': { avgTime: 480, avgClicks: 110, avgKeystrokes: 250 }
        },
        strengths: ['Nativo WordPress', 'Sin dependencias', 'Performance'],
        weaknesses: ['Diseño limitado', 'Sin responsive visual', 'Curva aprendizaje']
    },
    'divi': {
        id: 'divi',
        name: 'Divi Builder',
        version: '4.x',
        category: 'page-builder',
        benchmarks: {
            'landing-simple': { avgTime: 200, avgClicks: 40, avgKeystrokes: 130 },
            'home-corporate': { avgTime: 480, avgClicks: 90, avgKeystrokes: 500 },
            'page-complex': { avgTime: 780, avgClicks: 160, avgKeystrokes: 650 },
            'blog-article': { avgTime: 300, avgClicks: 60, avgKeystrokes: 380 },
            'ecommerce-product': { avgTime: 360, avgClicks: 75, avgKeystrokes: 280 },
            'portfolio-gallery': { avgTime: 420, avgClicks: 100, avgKeystrokes: 220 }
        },
        strengths: ['Visual editing', 'Plantillas incluidas', 'A/B testing'],
        weaknesses: ['Performance', 'HTML complejo', 'Vendor lock-in']
    },
    'webflow': {
        id: 'webflow',
        name: 'Webflow',
        version: 'SaaS',
        category: 'external',
        benchmarks: {
            'landing-simple': { avgTime: 150, avgClicks: 30, avgKeystrokes: 90 },
            'home-corporate': { avgTime: 360, avgClicks: 65, avgKeystrokes: 400 },
            'page-complex': { avgTime: 600, avgClicks: 110, avgKeystrokes: 500 },
            'blog-article': { avgTime: 220, avgClicks: 45, avgKeystrokes: 280 },
            'ecommerce-product': { avgTime: 280, avgClicks: 60, avgKeystrokes: 220 },
            'portfolio-gallery': { avgTime: 320, avgClicks: 75, avgKeystrokes: 180 }
        },
        strengths: ['Diseño profesional', 'CSS visual', 'Animaciones'],
        weaknesses: ['Precio', 'No WordPress', 'Curva aprendizaje']
    },
    'manual-code': {
        id: 'manual-code',
        name: 'Código Manual (HTML/CSS/JS)',
        version: 'N/A',
        category: 'development',
        benchmarks: {
            'landing-simple': { avgTime: 1800, avgClicks: 0, avgKeystrokes: 3000 },
            'home-corporate': { avgTime: 7200, avgClicks: 0, avgKeystrokes: 12000 },
            'page-complex': { avgTime: 14400, avgClicks: 0, avgKeystrokes: 20000 },
            'blog-article': { avgTime: 3600, avgClicks: 0, avgKeystrokes: 5000 },
            'ecommerce-product': { avgTime: 5400, avgClicks: 0, avgKeystrokes: 8000 },
            'portfolio-gallery': { avgTime: 6000, avgClicks: 0, avgKeystrokes: 9000 }
        },
        strengths: ['Control total', 'Sin dependencias', 'Performance óptima'],
        weaknesses: ['Tiempo', 'Requiere expertise', 'Mantenimiento']
    }
};

/**
 * Metric weights for scoring.
 */
const METRIC_WEIGHTS = {
    time: 0.4,       // 40% del score
    clicks: 0.3,     // 30% del score
    keystrokes: 0.2, // 20% del score
    errors: 0.1      // 10% del score
};

/**
 * Rating thresholds.
 */
const RATINGS = {
    excellent: { min: 90, label: 'Excelente', color: '#22c55e', emoji: '🏆' },
    good: { min: 75, label: 'Bueno', color: '#84cc16', emoji: '✅' },
    acceptable: { min: 60, label: 'Aceptable', color: '#eab308', emoji: '⚠️' },
    needsWork: { min: 40, label: 'Mejorable', color: '#f97316', emoji: '🔧' },
    poor: { min: 0, label: 'Deficiente', color: '#ef4444', emoji: '❌' }
};

/**
 * Learning curve multipliers.
 * Factor applied based on user experience level.
 */
const LEARNING_CURVE = {
    firstTime: 2.5,    // Primera vez usando VBP
    beginner: 1.8,     // Menos de 5 páginas
    intermediate: 1.2, // 5-20 páginas
    experienced: 1.0,  // 20-50 páginas
    expert: 0.8        // Más de 50 páginas
};

/**
 * Export configuration.
 */
module.exports = {
    BENCHMARKS,
    COMPETITORS,
    METRIC_WEIGHTS,
    RATINGS,
    LEARNING_CURVE
};
