<?php
/**
 * Theme Manager - Sistema de Temas/Skins
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Theme_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Temas disponibles
     */
    private $themes = [];

    /**
     * Tema activo
     */
    private $active_theme = 'default';

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_themes();
        $this->active_theme = get_option('flavor_active_theme', 'default');
        $this->init_hooks();
    }

    /**
     * Inicializar temas predefinidos
     */
    private function init_themes() {
        $this->themes = [
            'default' => [
                'name' => 'Default',
                'description' => 'Tema por defecto con colores neutros',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Cualquier proyecto, punto de partida personalizable', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/default.png',
                'variables' => [
                    // Colores primarios
                    '--flavor-primary' => '#3b82f6',
                    '--flavor-primary-hover' => '#2563eb',
                    '--flavor-primary-light' => '#dbeafe',
                    '--flavor-primary-dark' => '#1d4ed8',

                    // Colores secundarios
                    '--flavor-secondary' => '#6b7280',
                    '--flavor-secondary-hover' => '#4b5563',

                    // Colores de fondo
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f9fafb',
                    '--flavor-bg-tertiary' => '#f3f4f6',

                    // Colores de texto
                    '--flavor-text' => '#1f2937',
                    '--flavor-text-secondary' => '#6b7280',
                    '--flavor-text-muted' => '#9ca3af',

                    // Colores de borde
                    '--flavor-border' => '#e5e7eb',
                    '--flavor-border-light' => '#f3f4f6',

                    // Colores de estado
                    '--flavor-success' => '#22c55e',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#3b82f6',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 2px rgba(0,0,0,0.05)',
                    '--flavor-shadow' => '0 4px 6px -1px rgba(0,0,0,0.1)',
                    '--flavor-shadow-lg' => '0 10px 15px -3px rgba(0,0,0,0.1)',

                    // Bordes redondeados
                    '--flavor-radius-sm' => '4px',
                    '--flavor-radius' => '8px',
                    '--flavor-radius-lg' => '12px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.25rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Header (opcional - usa --flavor-bg por defecto)
                    // '--flavor-header-bg' => '#ffffff',
                    // '--flavor-header-text' => '#1f2937',

                    // Footer Dark (opcional - usa --flavor-text por defecto)
                    '--flavor-footer-bg-dark' => '#1f2937',
                    '--flavor-footer-text-dark' => '#ffffff',
                ],
            ],
            'modern-purple' => [
                'name' => 'Modern Purple',
                'description' => 'Tema moderno con tonos púrpura',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Proyectos creativos, agencias, portfolios', 'flavor-chat-ia'),
                'font_family_headings' => 'Poppins',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/purple.png',
                'variables' => [
                    '--flavor-primary' => '#8b5cf6',
                    '--flavor-primary-hover' => '#7c3aed',
                    '--flavor-primary-light' => '#ede9fe',
                    '--flavor-primary-dark' => '#6d28d9',
                    '--flavor-secondary' => '#64748b',
                    '--flavor-secondary-hover' => '#475569',
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#faf5ff',
                    '--flavor-bg-tertiary' => '#f3e8ff',
                    '--flavor-text' => '#1e1b4b',
                    '--flavor-text-secondary' => '#6366f1',
                    '--flavor-text-muted' => '#a5b4fc',
                    '--flavor-border' => '#e9d5ff',
                    '--flavor-border-light' => '#f3e8ff',
                    '--flavor-success' => '#10b981',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#8b5cf6',
                    '--flavor-shadow-sm' => '0 1px 3px rgba(139,92,246,0.1)',
                    '--flavor-shadow' => '0 4px 6px rgba(139,92,246,0.15)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(139,92,246,0.2)',
                    '--flavor-radius-sm' => '6px',
                    '--flavor-radius' => '12px',
                    '--flavor-radius-lg' => '16px',
                    '--flavor-radius-full' => '9999px',
                ],
            ],
            'ocean-blue' => [
                'name' => 'Ocean Blue',
                'description' => 'Tema fresco con azules oceánicos',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Proyectos marítimos, wellness, viajes', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/ocean.png',
                'variables' => [
                    '--flavor-primary' => '#0891b2',
                    '--flavor-primary-hover' => '#0e7490',
                    '--flavor-primary-light' => '#cffafe',
                    '--flavor-primary-dark' => '#155e75',
                    '--flavor-secondary' => '#64748b',
                    '--flavor-secondary-hover' => '#475569',
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f0fdfa',
                    '--flavor-bg-tertiary' => '#ccfbf1',
                    '--flavor-text' => '#134e4a',
                    '--flavor-text-secondary' => '#0d9488',
                    '--flavor-text-muted' => '#5eead4',
                    '--flavor-border' => '#99f6e4',
                    '--flavor-border-light' => '#ccfbf1',
                    '--flavor-success' => '#22c55e',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#0891b2',
                    '--flavor-shadow-sm' => '0 1px 3px rgba(8,145,178,0.1)',
                    '--flavor-shadow' => '0 4px 6px rgba(8,145,178,0.15)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(8,145,178,0.2)',
                ],
            ],
            'forest-green' => [
                'name' => 'Forest Green',
                'description' => 'Tema natural con verdes bosque',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Proyectos ecológicos, naturaleza, sostenibilidad', 'flavor-chat-ia'),
                'font_family_headings' => 'Nunito',
                'font_family_body' => 'Open Sans',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/forest.png',
                'variables' => [
                    '--flavor-primary' => '#16a34a',
                    '--flavor-primary-hover' => '#15803d',
                    '--flavor-primary-light' => '#dcfce7',
                    '--flavor-primary-dark' => '#166534',
                    '--flavor-secondary' => '#64748b',
                    '--flavor-secondary-hover' => '#475569',
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f0fdf4',
                    '--flavor-bg-tertiary' => '#dcfce7',
                    '--flavor-text' => '#14532d',
                    '--flavor-text-secondary' => '#22c55e',
                    '--flavor-text-muted' => '#86efac',
                    '--flavor-border' => '#bbf7d0',
                    '--flavor-border-light' => '#dcfce7',
                    '--flavor-success' => '#16a34a',
                    '--flavor-warning' => '#eab308',
                    '--flavor-error' => '#dc2626',
                    '--flavor-info' => '#0284c7',
                ],
            ],
            'sunset-orange' => [
                'name' => 'Sunset Orange',
                'description' => 'Tema cálido con naranjas atardecer',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Proyectos creativos, eventos, entretenimiento', 'flavor-chat-ia'),
                'font_family_headings' => 'Montserrat',
                'font_family_body' => 'Open Sans',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/sunset.png',
                'variables' => [
                    '--flavor-primary' => '#ea580c',
                    '--flavor-primary-hover' => '#c2410c',
                    '--flavor-primary-light' => '#ffedd5',
                    '--flavor-primary-dark' => '#9a3412',
                    '--flavor-secondary' => '#78716c',
                    '--flavor-secondary-hover' => '#57534e',
                    '--flavor-bg' => '#fffbeb',
                    '--flavor-bg-secondary' => '#fef3c7',
                    '--flavor-bg-tertiary' => '#fde68a',
                    '--flavor-text' => '#78350f',
                    '--flavor-text-secondary' => '#f59e0b',
                    '--flavor-text-muted' => '#fbbf24',
                    '--flavor-border' => '#fed7aa',
                    '--flavor-border-light' => '#ffedd5',
                    '--flavor-success' => '#22c55e',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#dc2626',
                    '--flavor-info' => '#0891b2',
                ],
            ],
            'dark-mode' => [
                'name' => 'Dark Mode',
                'description' => 'Tema oscuro para uso nocturno',
                'category' => 'tecnologia',
                'category_label' => __('Tecnologia', 'flavor-chat-ia'),
                'ideal_for' => __('Apps, plataformas tech, uso nocturno', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/dark.png',
                'variables' => [
                    '--flavor-primary' => '#60a5fa',
                    '--flavor-primary-hover' => '#3b82f6',
                    '--flavor-primary-light' => '#1e3a5f',
                    '--flavor-primary-dark' => '#93c5fd',
                    '--flavor-secondary' => '#9ca3af',
                    '--flavor-secondary-hover' => '#d1d5db',
                    '--flavor-bg' => '#111827',
                    '--flavor-bg-secondary' => '#1f2937',
                    '--flavor-bg-tertiary' => '#374151',
                    '--flavor-text' => '#f9fafb',
                    '--flavor-text-secondary' => '#d1d5db',
                    '--flavor-text-muted' => '#9ca3af',
                    '--flavor-border' => '#374151',
                    '--flavor-border-light' => '#4b5563',
                    '--flavor-success' => '#34d399',
                    '--flavor-warning' => '#fbbf24',
                    '--flavor-error' => '#f87171',
                    '--flavor-info' => '#60a5fa',
                    '--flavor-shadow-sm' => '0 1px 3px rgba(0,0,0,0.3)',
                    '--flavor-shadow' => '0 4px 6px rgba(0,0,0,0.4)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(0,0,0,0.5)',
                ],
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'Tema minimalista en blanco y negro',
                'category' => 'cultura',
                'category_label' => __('Cultura y Arte', 'flavor-chat-ia'),
                'ideal_for' => __('Portfolios, diseño editorial, marcas premium', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/minimal.png',
                'variables' => [
                    '--flavor-primary' => '#171717',
                    '--flavor-primary-hover' => '#404040',
                    '--flavor-primary-light' => '#f5f5f5',
                    '--flavor-primary-dark' => '#0a0a0a',
                    '--flavor-secondary' => '#737373',
                    '--flavor-secondary-hover' => '#525252',
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#fafafa',
                    '--flavor-bg-tertiary' => '#f5f5f5',
                    '--flavor-text' => '#171717',
                    '--flavor-text-secondary' => '#525252',
                    '--flavor-text-muted' => '#a3a3a3',
                    '--flavor-border' => '#e5e5e5',
                    '--flavor-border-light' => '#f5f5f5',
                    '--flavor-success' => '#171717',
                    '--flavor-warning' => '#171717',
                    '--flavor-error' => '#171717',
                    '--flavor-info' => '#171717',
                    '--flavor-shadow-sm' => '0 1px 2px rgba(0,0,0,0.05)',
                    '--flavor-shadow' => '0 2px 4px rgba(0,0,0,0.05)',
                    '--flavor-shadow-lg' => '0 4px 8px rgba(0,0,0,0.05)',
                    '--flavor-radius-sm' => '2px',
                    '--flavor-radius' => '4px',
                    '--flavor-radius-lg' => '6px',
                ],
            ],
            'corporate' => [
                'name' => 'Corporate',
                'description' => 'Tema profesional para empresas',
                'category' => 'legal',
                'category_label' => __('Legal y Finanzas', 'flavor-chat-ia'),
                'ideal_for' => __('Empresas, corporaciones, consultoras', 'flavor-chat-ia'),
                'font_family_headings' => 'Montserrat',
                'font_family_body' => 'Open Sans',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/corporate.png',
                'variables' => [
                    '--flavor-primary' => '#1e40af',
                    '--flavor-primary-hover' => '#1e3a8a',
                    '--flavor-primary-light' => '#dbeafe',
                    '--flavor-primary-dark' => '#1e3a8a',
                    '--flavor-secondary' => '#475569',
                    '--flavor-secondary-hover' => '#334155',
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f8fafc',
                    '--flavor-bg-tertiary' => '#f1f5f9',
                    '--flavor-text' => '#0f172a',
                    '--flavor-text-secondary' => '#475569',
                    '--flavor-text-muted' => '#94a3b8',
                    '--flavor-border' => '#cbd5e1',
                    '--flavor-border-light' => '#e2e8f0',
                    '--flavor-success' => '#059669',
                    '--flavor-warning' => '#d97706',
                    '--flavor-error' => '#dc2626',
                    '--flavor-info' => '#0284c7',
                    '--flavor-radius-sm' => '3px',
                    '--flavor-radius' => '6px',
                    '--flavor-radius-lg' => '8px',
                ],
            ],
            'themacle' => [
                'name' => 'Themacle',
                'description' => 'Tema profesional con tonos indigo y turquesa (basado en Themacle v3)',
                'category' => 'tecnologia',
                'category_label' => __('Tecnologia', 'flavor-chat-ia'),
                'ideal_for' => __('Startups, SaaS, plataformas digitales', 'flavor-chat-ia'),
                'font_family_headings' => 'Roboto',
                'font_family_body' => 'Roboto',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/themacle.png',
                'variables' => [
                    // Colores primarios (índigo)
                    '--flavor-primary' => '#5660b9',
                    '--flavor-primary-hover' => '#50589e',
                    '--flavor-primary-light' => '#f0f2ff',
                    '--flavor-primary-dark' => '#50589e',

                    // Colores secundarios (turquesa)
                    '--flavor-secondary' => '#4fd0e2',
                    '--flavor-secondary-hover' => '#43c2e0',

                    // Colores de fondo
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f1f5f7',
                    '--flavor-bg-tertiary' => '#e7ebef',

                    // Colores de texto
                    '--flavor-text' => '#000000',
                    '--flavor-text-secondary' => '#50589e',
                    '--flavor-text-muted' => '#9ca1a5',

                    // Colores de borde
                    '--flavor-border' => '#e7ebef',
                    '--flavor-border-light' => '#f1f5f7',

                    // Colores de estado
                    '--flavor-success' => '#008968',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#de3d63',
                    '--flavor-info' => '#4fd0e2',

                    // Sombras (tintadas con primary)
                    '--flavor-shadow-sm' => '0 1px 3px rgba(86,96,185,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(86,96,185,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(86,96,185,0.16)',

                    // Bordes redondeados
                    '--flavor-radius-sm' => '4px',
                    '--flavor-radius' => '8px',
                    '--flavor-radius-lg' => '12px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía (Roboto del design system)
                    '--flavor-font-family' => '"Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                    '--flavor-font-size-sm' => '0.75rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3125rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra del design system Themacle
                    '--flavor-tertiary' => '#008968',
                    '--flavor-tertiary-light' => '#badfd7',
                    '--flavor-tertiary-dark' => '#10462d',
                    '--flavor-complementary-1' => '#f0c7c8',
                    '--flavor-complementary-2' => '#bdd3cc',
                    '--flavor-secondary-light' => '#bcefff',
                ],
            ],
            'themacle-dark' => [
                'name' => 'Themacle Dark',
                'description' => 'Variante oscura del tema Themacle',
                'category' => 'tecnologia',
                'category_label' => __('Tecnologia', 'flavor-chat-ia'),
                'ideal_for' => __('Apps nocturnas, dashboards, herramientas dev', 'flavor-chat-ia'),
                'font_family_headings' => 'Roboto',
                'font_family_body' => 'Roboto',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/themacle-dark.png',
                'variables' => [
                    // Colores primarios (índigo claro sobre oscuro)
                    '--flavor-primary' => '#7b84d1',
                    '--flavor-primary-hover' => '#5660b9',
                    '--flavor-primary-light' => '#1e2048',
                    '--flavor-primary-dark' => '#a5acf0',

                    // Colores secundarios (turquesa)
                    '--flavor-secondary' => '#4fd0e2',
                    '--flavor-secondary-hover' => '#6ee0f0',

                    // Colores de fondo (oscuros)
                    '--flavor-bg' => '#0f1117',
                    '--flavor-bg-secondary' => '#1a1d2e',
                    '--flavor-bg-tertiary' => '#252940',

                    // Colores de texto (claros)
                    '--flavor-text' => '#e7ebef',
                    '--flavor-text-secondary' => '#a5acf0',
                    '--flavor-text-muted' => '#6b7280',

                    // Colores de borde
                    '--flavor-border' => '#252940',
                    '--flavor-border-light' => '#1a1d2e',

                    // Colores de estado
                    '--flavor-success' => '#39cb94',
                    '--flavor-warning' => '#fbbf24',
                    '--flavor-error' => '#f87171',
                    '--flavor-info' => '#4fd0e2',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(0,0,0,0.3)',
                    '--flavor-shadow' => '0 4px 6px rgba(0,0,0,0.4)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(0,0,0,0.5)',

                    // Bordes redondeados
                    '--flavor-radius-sm' => '4px',
                    '--flavor-radius' => '8px',
                    '--flavor-radius-lg' => '12px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                    '--flavor-font-size-sm' => '0.75rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3125rem',

                    // Colores extra del design system Themacle
                    '--flavor-tertiary' => '#39cb94',
                    '--flavor-tertiary-light' => '#1a3d30',
                    '--flavor-tertiary-dark' => '#badfd7',
                    '--flavor-complementary-1' => '#3d2a2b',
                    '--flavor-complementary-2' => '#2a3d36',
                    '--flavor-secondary-light' => '#1a3d45',
                ],
            ],

            // ─── Temas de diseños web (Themacle Templates) ───

            'zunbeltz' => [
                'name' => 'Zunbeltz',
                'description' => 'Tema organico y natural para comunidades ecologicas',
                'category' => 'alimentacion',
                'category_label' => __('Alimentacion', 'flavor-chat-ia'),
                'ideal_for' => __('Comunidades ecologicas, huertos, sostenibilidad', 'flavor-chat-ia'),
                'font_family_headings' => 'Merriweather',
                'font_family_body' => 'Merriweather',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/zunbeltz.png',
                'variables' => [
                    // Colores primarios (verde oscuro)
                    '--flavor-primary' => '#2D5F2E',
                    '--flavor-primary-hover' => '#245024',
                    '--flavor-primary-light' => '#e8f5e9',
                    '--flavor-primary-dark' => '#1b3d1c',

                    // Colores secundarios (verde claro)
                    '--flavor-secondary' => '#6da96e',
                    '--flavor-secondary-hover' => '#5c9a5d',

                    // Colores de fondo
                    '--flavor-bg' => '#fafaf5',
                    '--flavor-bg-secondary' => '#f0f4ea',
                    '--flavor-bg-tertiary' => '#e5ead9',

                    // Colores de texto
                    '--flavor-text' => '#2c2c2c',
                    '--flavor-text-secondary' => '#4a6b4b',
                    '--flavor-text-muted' => '#8a9e8b',

                    // Colores de borde
                    '--flavor-border' => '#d4ddc6',
                    '--flavor-border-light' => '#e5ead9',

                    // Colores de estado
                    '--flavor-success' => '#2D5F2E',
                    '--flavor-warning' => '#c49a2a',
                    '--flavor-error' => '#b94a48',
                    '--flavor-info' => '#4a8a6a',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(45,95,46,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(45,95,46,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(45,95,46,0.16)',

                    // Bordes redondeados
                    '--flavor-radius-sm' => '4px',
                    '--flavor-radius' => '8px',
                    '--flavor-radius-lg' => '12px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía (serif/natural)
                    '--flavor-font-family' => '"Merriweather", Georgia, "Times New Roman", serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.25rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',
                ],
            ],

            'naarq' => [
                'name' => 'Naarq',
                'description' => 'Tema minimal y arquitectonico con bordes rectos',
                'category' => 'inmobiliaria',
                'category_label' => __('Inmobiliaria', 'flavor-chat-ia'),
                'ideal_for' => __('Estudios de arquitectura, inmobiliarias, interiorismo', 'flavor-chat-ia'),
                'font_family_headings' => 'Bricolage Grotesque',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/naarq.png',
                'variables' => [
                    // Colores primarios (negro)
                    '--flavor-primary' => '#1a1a1a',
                    '--flavor-primary-hover' => '#333333',
                    '--flavor-primary-light' => '#f5f0e8',
                    '--flavor-primary-dark' => '#000000',

                    // Colores secundarios (beige/crema)
                    '--flavor-secondary' => '#d4c5a9',
                    '--flavor-secondary-hover' => '#c4b494',

                    // Colores de fondo
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f9f6f0',
                    '--flavor-bg-tertiary' => '#f0ebe0',

                    // Colores de texto
                    '--flavor-text' => '#1a1a1a',
                    '--flavor-text-secondary' => '#555555',
                    '--flavor-text-muted' => '#999999',

                    // Colores de borde
                    '--flavor-border' => '#e0d8c8',
                    '--flavor-border-light' => '#f0ebe0',

                    // Colores de estado
                    '--flavor-success' => '#3a7d44',
                    '--flavor-warning' => '#c49a2a',
                    '--flavor-error' => '#c0392b',
                    '--flavor-info' => '#1a1a1a',

                    // Sombras (sutiles)
                    '--flavor-shadow-sm' => '0 1px 2px rgba(0,0,0,0.04)',
                    '--flavor-shadow' => '0 2px 4px rgba(0,0,0,0.06)',
                    '--flavor-shadow-lg' => '0 4px 12px rgba(0,0,0,0.08)',

                    // Bordes rectos (0px)
                    '--flavor-radius-sm' => '0px',
                    '--flavor-radius' => '0px',
                    '--flavor-radius-lg' => '0px',
                    '--flavor-radius-full' => '0px',

                    // Tipografía (Bricolage Grotesque / Clash Grotesk)
                    '--flavor-font-family' => '"Bricolage Grotesque", "Clash Grotesk", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.375rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2.5rem',
                ],
            ],

            'campi' => [
                'name' => 'Campi',
                'description' => 'Tema teatral y bold con colores vibrantes',
                'category' => 'cultura',
                'category_label' => __('Cultura y Arte', 'flavor-chat-ia'),
                'ideal_for' => __('Teatros, espacios culturales, eventos artisticos', 'flavor-chat-ia'),
                'font_family_headings' => 'Poppins',
                'font_family_body' => 'Montserrat',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/campi.png',
                'variables' => [
                    // Colores primarios (navy oscuro)
                    '--flavor-primary' => '#1a1b3a',
                    '--flavor-primary-hover' => '#2a2b5a',
                    '--flavor-primary-light' => '#eeedf5',
                    '--flavor-primary-dark' => '#0d0e1f',

                    // Colores secundarios (amarillo)
                    '--flavor-secondary' => '#f5d547',
                    '--flavor-secondary-hover' => '#e5c537',

                    // Colores de fondo
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#f8f7fc',
                    '--flavor-bg-tertiary' => '#eeedf5',

                    // Colores de texto
                    '--flavor-text' => '#1a1b3a',
                    '--flavor-text-secondary' => '#4a4b6a',
                    '--flavor-text-muted' => '#8a8ba0',

                    // Colores de borde
                    '--flavor-border' => '#dddce8',
                    '--flavor-border-light' => '#eeedf5',

                    // Colores de estado
                    '--flavor-success' => '#22c55e',
                    '--flavor-warning' => '#f5d547',
                    '--flavor-error' => '#e85d9a',
                    '--flavor-info' => '#4fd0e2',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(26,27,58,0.08)',
                    '--flavor-shadow' => '0 4px 8px rgba(26,27,58,0.12)',
                    '--flavor-shadow-lg' => '0 12px 30px rgba(26,27,58,0.18)',

                    // Bordes redondeados (12px - bold)
                    '--flavor-radius-sm' => '6px',
                    '--flavor-radius' => '12px',
                    '--flavor-radius-lg' => '20px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía (bold, display)
                    '--flavor-font-family' => '"Poppins", "Montserrat", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.25rem',
                    '--flavor-font-size-xl' => '1.5rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.75rem',
                    '--flavor-spacing-xl' => '2.5rem',

                    // Colores extra (accent teatral)
                    '--flavor-tertiary' => '#e85d9a',
                    '--flavor-tertiary-light' => '#fce4ef',
                    '--flavor-complementary-1' => '#4fd0e2',
                    '--flavor-complementary-2' => '#f5d547',
                ],
            ],

            'denendako' => [
                'name' => 'Denendako',
                'description' => 'Tema limpio y minimal con acentos amarillos',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Redes locales, comunidades, plataformas civicas', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/denendako.png',
                'variables' => [
                    // Colores primarios (gris oscuro)
                    '--flavor-primary' => '#333333',
                    '--flavor-primary-hover' => '#222222',
                    '--flavor-primary-light' => '#f5f5f5',
                    '--flavor-primary-dark' => '#111111',

                    // Colores secundarios (amarillo)
                    '--flavor-secondary' => '#f5c518',
                    '--flavor-secondary-hover' => '#e5b508',

                    // Colores de fondo
                    '--flavor-bg' => '#ffffff',
                    '--flavor-bg-secondary' => '#fafafa',
                    '--flavor-bg-tertiary' => '#f5f5f5',

                    // Colores de texto
                    '--flavor-text' => '#333333',
                    '--flavor-text-secondary' => '#666666',
                    '--flavor-text-muted' => '#999999',

                    // Colores de borde
                    '--flavor-border' => '#e5e5e5',
                    '--flavor-border-light' => '#f0f0f0',

                    // Colores de estado
                    '--flavor-success' => '#27ae60',
                    '--flavor-warning' => '#f5c518',
                    '--flavor-error' => '#e74c3c',
                    '--flavor-info' => '#3498db',

                    // Sombras (mínimas)
                    '--flavor-shadow-sm' => '0 1px 2px rgba(0,0,0,0.04)',
                    '--flavor-shadow' => '0 2px 4px rgba(0,0,0,0.06)',
                    '--flavor-shadow-lg' => '0 4px 8px rgba(0,0,0,0.08)',

                    // Bordes redondeados (4px - clean)
                    '--flavor-radius-sm' => '2px',
                    '--flavor-radius' => '4px',
                    '--flavor-radius-lg' => '6px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía (clean sans-serif)
                    '--flavor-font-family' => '"Inter", "Helvetica Neue", Arial, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.25rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',
                ],
            ],

            'escena-familiar' => [
                'name' => 'Escena Familiar',
                'description' => 'Tema infantil y colorido con formas redondeadas',
                'category' => 'infantil',
                'category_label' => __('Infantil', 'flavor-chat-ia'),
                'ideal_for' => __('Teatro familiar, actividades infantiles, ludotecas', 'flavor-chat-ia'),
                'font_family_headings' => 'Nunito',
                'font_family_body' => 'Quicksand',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/escena-familiar.png',
                'variables' => [
                    // Colores primarios (púrpura)
                    '--flavor-primary' => '#7c3aed',
                    '--flavor-primary-hover' => '#6d28d9',
                    '--flavor-primary-light' => '#ede9fe',
                    '--flavor-primary-dark' => '#5b21b6',

                    // Colores secundarios (rosa)
                    '--flavor-secondary' => '#ec4899',
                    '--flavor-secondary-hover' => '#db2777',

                    // Colores de fondo
                    '--flavor-bg' => '#fffbfe',
                    '--flavor-bg-secondary' => '#fdf2f8',
                    '--flavor-bg-tertiary' => '#fce7f3',

                    // Colores de texto
                    '--flavor-text' => '#3b0764',
                    '--flavor-text-secondary' => '#7c3aed',
                    '--flavor-text-muted' => '#a78bfa',

                    // Colores de borde
                    '--flavor-border' => '#e9d5ff',
                    '--flavor-border-light' => '#f3e8ff',

                    // Colores de estado
                    '--flavor-success' => '#34d399',
                    '--flavor-warning' => '#fbbf24',
                    '--flavor-error' => '#f87171',
                    '--flavor-info' => '#60a5fa',

                    // Sombras (coloridas)
                    '--flavor-shadow-sm' => '0 1px 3px rgba(124,58,237,0.1)',
                    '--flavor-shadow' => '0 4px 8px rgba(124,58,237,0.15)',
                    '--flavor-shadow-lg' => '0 12px 30px rgba(124,58,237,0.2)',

                    // Bordes muy redondeados (16px - playful)
                    '--flavor-radius-sm' => '8px',
                    '--flavor-radius' => '16px',
                    '--flavor-radius-lg' => '24px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía (playful sans-serif)
                    '--flavor-font-family' => '"Nunito", "Quicksand", "Comic Neue", sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.375rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra (playful)
                    '--flavor-tertiary' => '#06b6d4',
                    '--flavor-tertiary-light' => '#cffafe',
                    '--flavor-complementary-1' => '#fbbf24',
                    '--flavor-complementary-2' => '#34d399',
                ],
            ],

            'grupos-consumo' => [
                'name' => 'Grupos de Consumo',
                'description' => 'Tema fresco y organico para grupos de consumo y cooperativas alimentarias',
                'category' => 'alimentacion',
                'category_label' => __('Alimentacion', 'flavor-chat-ia'),
                'ideal_for' => __('Grupos de consumo, cooperativas alimentarias, km0', 'flavor-chat-ia'),
                'font_family_headings' => 'Nunito Sans',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/grupos-consumo.png',
                'variables' => [
                    // Colores primarios (verde orgánico)
                    '--flavor-primary' => '#4a7c59',
                    '--flavor-primary-hover' => '#3d6a4b',
                    '--flavor-primary-light' => '#e8f0eb',
                    '--flavor-primary-dark' => '#2e4d37',

                    // Colores secundarios (ámbar/cosecha)
                    '--flavor-secondary' => '#d4953a',
                    '--flavor-secondary-hover' => '#c08530',

                    // Colores de fondo
                    '--flavor-bg' => '#fefcf8',
                    '--flavor-bg-secondary' => '#f5f1ea',
                    '--flavor-bg-tertiary' => '#ece7dc',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#4a7c59',
                    '--flavor-text-muted' => '#8a9a8e',

                    // Colores de borde
                    '--flavor-border' => '#d6ddd8',
                    '--flavor-border-light' => '#e8f0eb',

                    // Colores de estado
                    '--flavor-success' => '#4a7c59',
                    '--flavor-warning' => '#d4953a',
                    '--flavor-error' => '#c0392b',
                    '--flavor-info' => '#3498db',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(74,124,89,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(74,124,89,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(74,124,89,0.16)',

                    // Bordes redondeados (10px - amigable)
                    '--flavor-radius-sm' => '5px',
                    '--flavor-radius' => '10px',
                    '--flavor-radius-lg' => '16px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Nunito Sans", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#7ab648',
                    '--flavor-tertiary-light' => '#e8f5e0',
                    '--flavor-complementary-1' => '#d4953a',
                    '--flavor-complementary-2' => '#8bc34a',
                ],
            ],

            'comunidad-viva' => [
                'name' => 'Comunidad Viva',
                'description' => 'Red social cooperativa local - Dashboard central del ecosistema Gailu',
                'category' => 'tecnologia',
                'category_label' => __('Tecnologia', 'flavor-chat-ia'),
                'ideal_for' => __('Redes sociales locales, comunidades digitales, dashboards', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Nunito Sans',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/comunidad-viva.png',
                'variables' => [
                    // Colores primarios (indigo)
                    '--flavor-primary' => '#4f46e5',
                    '--flavor-primary-hover' => '#4338ca',
                    '--flavor-primary-light' => '#eef2ff',
                    '--flavor-primary-dark' => '#312e81',

                    // Colores secundarios (emerald)
                    '--flavor-secondary' => '#10b981',
                    '--flavor-secondary-hover' => '#059669',

                    // Colores de fondo
                    '--flavor-bg' => '#f8f9ff',
                    '--flavor-bg-secondary' => '#eef0fb',
                    '--flavor-bg-tertiary' => '#e4e7f6',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#4f46e5',
                    '--flavor-text-muted' => '#8890a4',

                    // Colores de borde
                    '--flavor-border' => '#d0d4e8',
                    '--flavor-border-light' => '#e8eaf5',

                    // Colores de estado
                    '--flavor-success' => '#10b981',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#3b82f6',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(79,70,229,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(79,70,229,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(79,70,229,0.16)',

                    // Bordes redondeados (12px - moderno)
                    '--flavor-radius-sm' => '6px',
                    '--flavor-radius' => '12px',
                    '--flavor-radius-lg' => '18px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Inter", "Nunito Sans", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#f59e0b',
                    '--flavor-tertiary-light' => '#fef3c7',
                    '--flavor-complementary-1' => '#10b981',
                    '--flavor-complementary-2' => '#8b5cf6',
                ],
            ],

            'jantoki' => [
                'name' => 'Jantoki',
                'description' => 'Restaurante cooperativo - Cocina comunitaria km0',
                'category' => 'hosteleria',
                'category_label' => __('Hosteleria', 'flavor-chat-ia'),
                'ideal_for' => __('Restaurantes, cocinas comunitarias, catering', 'flavor-chat-ia'),
                'font_family_headings' => 'Playfair Display',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/jantoki.png',
                'variables' => [
                    // Colores primarios (warm brown)
                    '--flavor-primary' => '#8b5a2b',
                    '--flavor-primary-hover' => '#744b24',
                    '--flavor-primary-light' => '#f5ece3',
                    '--flavor-primary-dark' => '#5c3a1b',

                    // Colores secundarios (amber)
                    '--flavor-secondary' => '#d4953a',
                    '--flavor-secondary-hover' => '#c08530',

                    // Colores de fondo
                    '--flavor-bg' => '#fdf8f3',
                    '--flavor-bg-secondary' => '#f5efe6',
                    '--flavor-bg-tertiary' => '#ece5d9',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#8b5a2b',
                    '--flavor-text-muted' => '#9a8a7a',

                    // Colores de borde
                    '--flavor-border' => '#ddd2c4',
                    '--flavor-border-light' => '#ede6db',

                    // Colores de estado
                    '--flavor-success' => '#6b8e4e',
                    '--flavor-warning' => '#d4953a',
                    '--flavor-error' => '#c0392b',
                    '--flavor-info' => '#3498db',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(139,90,43,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(139,90,43,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(139,90,43,0.16)',

                    // Bordes redondeados (8px - clásico)
                    '--flavor-radius-sm' => '4px',
                    '--flavor-radius' => '8px',
                    '--flavor-radius-lg' => '14px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Playfair Display", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#6b8e4e',
                    '--flavor-tertiary-light' => '#e6f0de',
                    '--flavor-complementary-1' => '#d4953a',
                    '--flavor-complementary-2' => '#6b8e4e',
                ],
            ],

            'mercado-espiral' => [
                'name' => 'Mercado Espiral',
                'description' => 'Marketplace cooperativo local con cashback en SEMILLA',
                'category' => 'alimentacion',
                'category_label' => __('Alimentacion', 'flavor-chat-ia'),
                'ideal_for' => __('Marketplaces locales, tiendas online, comercio km0', 'flavor-chat-ia'),
                'font_family_headings' => 'Nunito',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/mercado-espiral.png',
                'variables' => [
                    // Colores primarios (green)
                    '--flavor-primary' => '#2e7d32',
                    '--flavor-primary-hover' => '#256b29',
                    '--flavor-primary-light' => '#e8f5e9',
                    '--flavor-primary-dark' => '#1b5e20',

                    // Colores secundarios (amber)
                    '--flavor-secondary' => '#ff8f00',
                    '--flavor-secondary-hover' => '#e68200',

                    // Colores de fondo
                    '--flavor-bg' => '#f9fdf9',
                    '--flavor-bg-secondary' => '#eff5ef',
                    '--flavor-bg-tertiary' => '#e5ece5',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#2e7d32',
                    '--flavor-text-muted' => '#7e998a',

                    // Colores de borde
                    '--flavor-border' => '#c8dcc8',
                    '--flavor-border-light' => '#ddeedd',

                    // Colores de estado
                    '--flavor-success' => '#2e7d32',
                    '--flavor-warning' => '#ff8f00',
                    '--flavor-error' => '#d32f2f',
                    '--flavor-info' => '#1565c0',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(46,125,50,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(46,125,50,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(46,125,50,0.16)',

                    // Bordes redondeados (10px - amigable)
                    '--flavor-radius-sm' => '5px',
                    '--flavor-radius' => '10px',
                    '--flavor-radius-lg' => '16px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Nunito", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#1565c0',
                    '--flavor-tertiary-light' => '#e3f2fd',
                    '--flavor-complementary-1' => '#ff8f00',
                    '--flavor-complementary-2' => '#1565c0',
                ],
            ],

            'spiral-bank' => [
                'name' => 'Spiral Bank',
                'description' => 'Sistema financiero cooperativo multi-moneda - EUR + SEMILLA + Horas + ESTRELLAS',
                'category' => 'legal',
                'category_label' => __('Legal y Finanzas', 'flavor-chat-ia'),
                'ideal_for' => __('Banca cooperativa, finanzas eticas, criptomonedas', 'flavor-chat-ia'),
                'font_family_headings' => 'Space Grotesk',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/spiral-bank.png',
                'variables' => [
                    // Colores primarios (purple)
                    '--flavor-primary' => '#764ba2',
                    '--flavor-primary-hover' => '#643f8a',
                    '--flavor-primary-light' => '#f0eaf7',
                    '--flavor-primary-dark' => '#4a2d6a',

                    // Colores secundarios (blue)
                    '--flavor-secondary' => '#667eea',
                    '--flavor-secondary-hover' => '#5568d4',

                    // Colores de fondo
                    '--flavor-bg' => '#faf8ff',
                    '--flavor-bg-secondary' => '#f0ecf8',
                    '--flavor-bg-tertiary' => '#e6e0f2',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#764ba2',
                    '--flavor-text-muted' => '#918a9e',

                    // Colores de borde
                    '--flavor-border' => '#d6cfe4',
                    '--flavor-border-light' => '#eae5f3',

                    // Colores de estado
                    '--flavor-success' => '#10b981',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#667eea',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(118,75,162,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(118,75,162,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(118,75,162,0.16)',

                    // Bordes redondeados (12px - moderno)
                    '--flavor-radius-sm' => '6px',
                    '--flavor-radius' => '12px',
                    '--flavor-radius-lg' => '18px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Space Grotesk", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#10b981',
                    '--flavor-tertiary-light' => '#d1fae5',
                    '--flavor-complementary-1' => '#667eea',
                    '--flavor-complementary-2' => '#10b981',
                ],
            ],

            'red-cuidados' => [
                'name' => 'Red de Cuidados',
                'description' => 'Red de apoyo mutuo y cuidados comunitarios',
                'category' => 'salud',
                'category_label' => __('Salud y Bienestar', 'flavor-chat-ia'),
                'ideal_for' => __('Redes de cuidados, apoyo mutuo, servicios sociales', 'flavor-chat-ia'),
                'font_family_headings' => 'Nunito Sans',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/red-cuidados.png',
                'variables' => [
                    // Colores primarios (pink)
                    '--flavor-primary' => '#ec4899',
                    '--flavor-primary-hover' => '#db2777',
                    '--flavor-primary-light' => '#fce7f3',
                    '--flavor-primary-dark' => '#9d174d',

                    // Colores secundarios (violet)
                    '--flavor-secondary' => '#8b5cf6',
                    '--flavor-secondary-hover' => '#7c3aed',

                    // Colores de fondo
                    '--flavor-bg' => '#fef7fb',
                    '--flavor-bg-secondary' => '#f8ecf4',
                    '--flavor-bg-tertiary' => '#f1e0ec',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#ec4899',
                    '--flavor-text-muted' => '#a0899a',

                    // Colores de borde
                    '--flavor-border' => '#e8d0de',
                    '--flavor-border-light' => '#f5e4ef',

                    // Colores de estado
                    '--flavor-success' => '#10b981',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#06b6d4',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(236,72,153,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(236,72,153,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(236,72,153,0.16)',

                    // Bordes redondeados (14px - suave)
                    '--flavor-radius-sm' => '7px',
                    '--flavor-radius' => '14px',
                    '--flavor-radius-lg' => '20px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Nunito Sans", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#06b6d4',
                    '--flavor-tertiary-light' => '#cffafe',
                    '--flavor-complementary-1' => '#8b5cf6',
                    '--flavor-complementary-2' => '#06b6d4',
                ],
            ],

            'academia-espiral' => [
                'name' => 'Academia Espiral',
                'description' => 'Plataforma de aprendizaje entre iguales P2P con recompensas',
                'category' => 'educacion',
                'category_label' => __('Educacion', 'flavor-chat-ia'),
                'ideal_for' => __('Plataformas e-learning, educacion P2P, cursos online', 'flavor-chat-ia'),
                'font_family_headings' => 'Plus Jakarta Sans',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/academia-espiral.png',
                'variables' => [
                    // Colores primarios (amber)
                    '--flavor-primary' => '#d97706',
                    '--flavor-primary-hover' => '#b45309',
                    '--flavor-primary-light' => '#fef3c7',
                    '--flavor-primary-dark' => '#92400e',

                    // Colores secundarios (indigo)
                    '--flavor-secondary' => '#4f46e5',
                    '--flavor-secondary-hover' => '#4338ca',

                    // Colores de fondo
                    '--flavor-bg' => '#fffbf5',
                    '--flavor-bg-secondary' => '#f8f2e8',
                    '--flavor-bg-tertiary' => '#f0e8db',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#d97706',
                    '--flavor-text-muted' => '#9a8e7e',

                    // Colores de borde
                    '--flavor-border' => '#e4d8c4',
                    '--flavor-border-light' => '#f2ead8',

                    // Colores de estado
                    '--flavor-success' => '#16a34a',
                    '--flavor-warning' => '#d97706',
                    '--flavor-error' => '#dc2626',
                    '--flavor-info' => '#4f46e5',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(217,119,6,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(217,119,6,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(217,119,6,0.16)',

                    // Bordes redondeados (10px - amigable)
                    '--flavor-radius-sm' => '5px',
                    '--flavor-radius' => '10px',
                    '--flavor-radius-lg' => '16px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Plus Jakarta Sans", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#16a34a',
                    '--flavor-tertiary-light' => '#dcfce7',
                    '--flavor-complementary-1' => '#4f46e5',
                    '--flavor-complementary-2' => '#16a34a',
                ],
            ],

            'democracia-universal' => [
                'name' => 'Democracia Universal',
                'description' => 'Toma de decisiones colectiva con democracia liquida y votacion cuadratica',
                'category' => 'tecnologia',
                'category_label' => __('Tecnologia', 'flavor-chat-ia'),
                'ideal_for' => __('Gobernanza participativa, votaciones, asambleas digitales', 'flavor-chat-ia'),
                'font_family_headings' => 'Inter',
                'font_family_body' => 'Nunito Sans',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/democracia-universal.png',
                'variables' => [
                    // Colores primarios (violet)
                    '--flavor-primary' => '#8b5cf6',
                    '--flavor-primary-hover' => '#7c3aed',
                    '--flavor-primary-light' => '#ede9fe',
                    '--flavor-primary-dark' => '#5b21b6',

                    // Colores secundarios (sky)
                    '--flavor-secondary' => '#0ea5e9',
                    '--flavor-secondary-hover' => '#0284c7',

                    // Colores de fondo
                    '--flavor-bg' => '#faf8ff',
                    '--flavor-bg-secondary' => '#f0ecf9',
                    '--flavor-bg-tertiary' => '#e6e0f3',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#8b5cf6',
                    '--flavor-text-muted' => '#918a9e',

                    // Colores de borde
                    '--flavor-border' => '#d6cfe4',
                    '--flavor-border-light' => '#eae5f3',

                    // Colores de estado
                    '--flavor-success' => '#10b981',
                    '--flavor-warning' => '#f59e0b',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#0ea5e9',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(139,92,246,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(139,92,246,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(139,92,246,0.16)',

                    // Bordes redondeados (10px - amigable)
                    '--flavor-radius-sm' => '5px',
                    '--flavor-radius' => '10px',
                    '--flavor-radius-lg' => '16px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Inter", "Nunito Sans", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#f59e0b',
                    '--flavor-tertiary-light' => '#fef3c7',
                    '--flavor-complementary-1' => '#0ea5e9',
                    '--flavor-complementary-2' => '#f59e0b',
                ],
            ],

            'flujo' => [
                'name' => 'FLUJO',
                'description' => 'Red de video consciente - Alternativa a YouTube que recompensa impacto',
                'category' => 'tecnologia',
                'category_label' => __('Tecnologia', 'flavor-chat-ia'),
                'ideal_for' => __('Plataformas de video, streaming, contenido multimedia', 'flavor-chat-ia'),
                'font_family_headings' => 'Poppins',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/flujo.png',
                'variables' => [
                    // Colores primarios (dark green)
                    '--flavor-primary' => '#166534',
                    '--flavor-primary-hover' => '#115529',
                    '--flavor-primary-light' => '#dcfce7',
                    '--flavor-primary-dark' => '#0b3d1f',

                    // Colores secundarios (yellow)
                    '--flavor-secondary' => '#eab308',
                    '--flavor-secondary-hover' => '#ca9a06',

                    // Colores de fondo
                    '--flavor-bg' => '#f7fdf9',
                    '--flavor-bg-secondary' => '#ecf5ef',
                    '--flavor-bg-tertiary' => '#e0ece4',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#166534',
                    '--flavor-text-muted' => '#7a9a86',

                    // Colores de borde
                    '--flavor-border' => '#c2dcc8',
                    '--flavor-border-light' => '#d8eddd',

                    // Colores de estado
                    '--flavor-success' => '#166534',
                    '--flavor-warning' => '#eab308',
                    '--flavor-error' => '#dc2626',
                    '--flavor-info' => '#3b82f6',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(22,101,52,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(22,101,52,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(22,101,52,0.16)',

                    // Bordes redondeados (12px - moderno)
                    '--flavor-radius-sm' => '6px',
                    '--flavor-radius' => '12px',
                    '--flavor-radius-lg' => '18px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Poppins", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#3b82f6',
                    '--flavor-tertiary-light' => '#dbeafe',
                    '--flavor-complementary-1' => '#eab308',
                    '--flavor-complementary-2' => '#3b82f6',
                ],
            ],

            'kulturaka' => [
                'name' => 'Kulturaka',
                'description' => 'Plataforma de eventos y produccion cultural cooperativa',
                'category' => 'cultura',
                'category_label' => __('Cultura y Arte', 'flavor-chat-ia'),
                'ideal_for' => __('Eventos culturales, festivales, produccion artistica', 'flavor-chat-ia'),
                'font_family_headings' => 'DM Sans',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/kulturaka.png',
                'variables' => [
                    // Colores primarios (red)
                    '--flavor-primary' => '#e63946',
                    '--flavor-primary-hover' => '#cf2f3c',
                    '--flavor-primary-light' => '#fde8ea',
                    '--flavor-primary-dark' => '#9b1b24',

                    // Colores secundarios (navy)
                    '--flavor-secondary' => '#1d3557',
                    '--flavor-secondary-hover' => '#152a47',

                    // Colores de fondo
                    '--flavor-bg' => '#fffaf9',
                    '--flavor-bg-secondary' => '#f7efed',
                    '--flavor-bg-tertiary' => '#eee5e2',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#e63946',
                    '--flavor-text-muted' => '#9a8a88',

                    // Colores de borde
                    '--flavor-border' => '#e0d2d0',
                    '--flavor-border-light' => '#f0e5e3',

                    // Colores de estado
                    '--flavor-success' => '#2a9d8f',
                    '--flavor-warning' => '#f4a261',
                    '--flavor-error' => '#e63946',
                    '--flavor-info' => '#457b9d',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(230,57,70,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(230,57,70,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(230,57,70,0.16)',

                    // Bordes redondeados (8px - clásico)
                    '--flavor-radius-sm' => '4px',
                    '--flavor-radius' => '8px',
                    '--flavor-radius-lg' => '14px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"DM Sans", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#f4a261',
                    '--flavor-tertiary-light' => '#fef0e0',
                    '--flavor-complementary-1' => '#1d3557',
                    '--flavor-complementary-2' => '#f4a261',
                ],
            ],

            'pueblo-vivo' => [
                'name' => 'Pueblo Vivo',
                'description' => 'Revitalizacion rural - Combatir la despoblacion con infraestructura digital',
                'category' => 'inmobiliaria',
                'category_label' => __('Inmobiliaria', 'flavor-chat-ia'),
                'ideal_for' => __('Revitalizacion rural, municipios, desarrollo local', 'flavor-chat-ia'),
                'font_family_headings' => 'Outfit',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/pueblo-vivo.png',
                'variables' => [
                    // Colores primarios (earth orange)
                    '--flavor-primary' => '#c2703a',
                    '--flavor-primary-hover' => '#a85f30',
                    '--flavor-primary-light' => '#f8ece3',
                    '--flavor-primary-dark' => '#7d4725',

                    // Colores secundarios (forest)
                    '--flavor-secondary' => '#4a7c59',
                    '--flavor-secondary-hover' => '#3d6a4b',

                    // Colores de fondo
                    '--flavor-bg' => '#fdf9f5',
                    '--flavor-bg-secondary' => '#f5efe8',
                    '--flavor-bg-tertiary' => '#ece5db',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#c2703a',
                    '--flavor-text-muted' => '#9a8a7a',

                    // Colores de borde
                    '--flavor-border' => '#ddd2c4',
                    '--flavor-border-light' => '#ede6db',

                    // Colores de estado
                    '--flavor-success' => '#4a7c59',
                    '--flavor-warning' => '#d4953a',
                    '--flavor-error' => '#c0392b',
                    '--flavor-info' => '#3498db',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(194,112,58,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(194,112,58,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(194,112,58,0.16)',

                    // Bordes redondeados (10px - amigable)
                    '--flavor-radius-sm' => '5px',
                    '--flavor-radius' => '10px',
                    '--flavor-radius-lg' => '16px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Outfit", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#8b6914',
                    '--flavor-tertiary-light' => '#f5ecd4',
                    '--flavor-complementary-1' => '#4a7c59',
                    '--flavor-complementary-2' => '#8b6914',
                ],
            ],

            'ecos-comunitarios' => [
                'name' => 'Ecos Comunitarios',
                'description' => 'Gestion de espacios comunes y recursos compartidos',
                'category' => 'general',
                'category_label' => __('General', 'flavor-chat-ia'),
                'ideal_for' => __('Coworkings, espacios compartidos, gestion de recursos', 'flavor-chat-ia'),
                'font_family_headings' => 'Nunito Sans',
                'font_family_body' => 'Inter',
                'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/ecos-comunitarios.png',
                'variables' => [
                    // Colores primarios (teal/cyan)
                    '--flavor-primary' => '#0891b2',
                    '--flavor-primary-hover' => '#0e7490',
                    '--flavor-primary-light' => '#e0f7fa',
                    '--flavor-primary-dark' => '#155e75',

                    // Colores secundarios (indigo)
                    '--flavor-secondary' => '#6366f1',
                    '--flavor-secondary-hover' => '#4f46e5',

                    // Colores de fondo
                    '--flavor-bg' => '#f5fcff',
                    '--flavor-bg-secondary' => '#eaf4f8',
                    '--flavor-bg-tertiary' => '#dfecf1',

                    // Colores de texto
                    '--flavor-text' => '#2d3436',
                    '--flavor-text-secondary' => '#0891b2',
                    '--flavor-text-muted' => '#7a9aa4',

                    // Colores de borde
                    '--flavor-border' => '#c2dce4',
                    '--flavor-border-light' => '#d8edf3',

                    // Colores de estado
                    '--flavor-success' => '#10b981',
                    '--flavor-warning' => '#f97316',
                    '--flavor-error' => '#ef4444',
                    '--flavor-info' => '#0891b2',

                    // Sombras
                    '--flavor-shadow-sm' => '0 1px 3px rgba(8,145,178,0.08)',
                    '--flavor-shadow' => '0 4px 6px rgba(8,145,178,0.12)',
                    '--flavor-shadow-lg' => '0 10px 25px rgba(8,145,178,0.16)',

                    // Bordes redondeados (12px - moderno)
                    '--flavor-radius-sm' => '6px',
                    '--flavor-radius' => '12px',
                    '--flavor-radius-lg' => '18px',
                    '--flavor-radius-full' => '9999px',

                    // Tipografía
                    '--flavor-font-family' => '"Nunito Sans", "Inter", -apple-system, sans-serif',
                    '--flavor-font-size-sm' => '0.875rem',
                    '--flavor-font-size' => '1rem',
                    '--flavor-font-size-lg' => '1.125rem',
                    '--flavor-font-size-xl' => '1.3rem',

                    // Espaciado
                    '--flavor-spacing-xs' => '0.25rem',
                    '--flavor-spacing-sm' => '0.5rem',
                    '--flavor-spacing' => '1rem',
                    '--flavor-spacing-lg' => '1.5rem',
                    '--flavor-spacing-xl' => '2rem',

                    // Colores extra
                    '--flavor-tertiary' => '#f97316',
                    '--flavor-tertiary-light' => '#ffedd5',
                    '--flavor-complementary-1' => '#6366f1',
                    '--flavor-complementary-2' => '#f97316',
                ],
            ],
        ];

        $this->themes = apply_filters('flavor_themes', $this->themes);

        // ═══════════════════════════════════════════════════════════════════════════
        // Cargar temas adicionales por sector desde theme-presets.php
        // ═══════════════════════════════════════════════════════════════════════════
        $this->load_sector_themes();

        // Variables base comunes a todos los temas
        $variables_base_comunes = [
            '--flavor-font-weight-normal'   => '400',
            '--flavor-font-weight-medium'   => '500',
            '--flavor-font-weight-semibold' => '600',
            '--flavor-font-weight-bold'     => '700',
            '--flavor-line-height-tight'    => '1.25',
            '--flavor-line-height'          => '1.5',
            '--flavor-line-height-relaxed'  => '1.75',
            '--flavor-font-size-xs'         => '0.75rem',
            '--flavor-font-size-2xl'        => '1.5rem',
            '--flavor-font-size-3xl'        => '1.875rem',
            '--flavor-font-size-4xl'        => '2.25rem',
            '--flavor-transition-fast'      => '0.15s ease',
            '--flavor-transition'           => '0.2s ease',
            '--flavor-transition-slow'      => '0.3s ease',
            '--flavor-container-width'      => '1200px',
        ];

        foreach ($this->themes as $identificador_tema => &$configuracion_tema) {
            // Solo añadir si el tema no define ya la variable (permite override por tema)
            foreach ($variables_base_comunes as $nombre_variable => $valor_variable) {
                if (!isset($configuracion_tema['variables'][$nombre_variable])) {
                    $configuracion_tema['variables'][$nombre_variable] = $valor_variable;
                }
            }
        }
        unset($configuracion_tema);
    }

    /**
     * Cargar temas adicionales organizados por sector desde theme-presets.php
     *
     * @since 2.0.0
     */
    private function load_sector_themes() {
        // Incluir archivo de presets si existe
        $ruta_archivo_presets = FLAVOR_CHAT_IA_PATH . 'includes/config/theme-presets.php';

        if (!file_exists($ruta_archivo_presets)) {
            return;
        }

        require_once $ruta_archivo_presets;

        // Verificar que la función existe
        if (!function_exists('flavor_get_theme_presets')) {
            return;
        }

        $presets_por_sector = flavor_get_theme_presets();

        // Añadir solo los temas que no existan ya (evitar duplicados)
        foreach ($presets_por_sector as $identificador_tema => $configuracion_tema) {
            if (!isset($this->themes[$identificador_tema])) {
                // Extraer variables y metadata
                $variables = $configuracion_tema['variables'] ?? [];

                // Construir estructura de tema compatible
                $this->themes[$identificador_tema] = [
                    'name' => $configuracion_tema['name'] ?? $identificador_tema,
                    'description' => $configuracion_tema['description'] ?? '',
                    'preview' => $configuracion_tema['preview'] ?? '',
                    'category' => $configuracion_tema['category'] ?? 'general',
                    'category_label' => $configuracion_tema['category_label'] ?? __('General', 'flavor-chat-ia'),
                    'ideal_for' => $configuracion_tema['ideal_for'] ?? '',
                    'font_family_headings' => $configuracion_tema['font_family_headings'] ?? 'Inter',
                    'font_family_body' => $configuracion_tema['font_family_body'] ?? 'Inter',
                    'variables' => $variables,
                ];
            }
        }
    }

    /**
     * Obtener temas filtrados por categoría
     *
     * @param string $categoria ID de la categoría (o 'all' para todos)
     * @return array Array de temas filtrados
     */
    public function get_themes_by_category($categoria = 'all') {
        if ($categoria === 'all') {
            return $this->themes;
        }

        $temas_filtrados = [];
        foreach ($this->themes as $identificador_tema => $configuracion_tema) {
            $categoria_tema = $configuracion_tema['category'] ?? 'general';
            if ($categoria_tema === $categoria) {
                $temas_filtrados[$identificador_tema] = $configuracion_tema;
            }
        }

        return $temas_filtrados;
    }

    /**
     * Obtener todas las categorías de temas disponibles
     *
     * @return array Array de categorías únicas
     */
    public function get_available_categories() {
        $categorias = ['all' => __('Todos los temas', 'flavor-chat-ia')];

        foreach ($this->themes as $configuracion_tema) {
            $categoria_id = $configuracion_tema['category'] ?? 'general';
            $categoria_label = $configuracion_tema['category_label'] ?? __('General', 'flavor-chat-ia');

            if (!isset($categorias[$categoria_id])) {
                $categorias[$categoria_id] = $categoria_label;
            }
        }

        return $categorias;
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_head', [$this, 'output_theme_css'], 5);
        add_action('admin_head', [$this, 'output_theme_css'], 5);

        // CSS base de componentes (carga antes que los demás para permitir overrides)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_base_css'], 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_base_css'], 1);

        // AJAX handlers
        add_action('wp_ajax_flavor_get_themes', [$this, 'ajax_get_themes']);
        add_action('wp_ajax_flavor_set_theme', [$this, 'ajax_set_theme']);
        add_action('wp_ajax_flavor_save_custom_theme', [$this, 'ajax_save_custom_theme']);
        add_action('wp_ajax_flavor_delete_custom_theme', [$this, 'ajax_delete_custom_theme']);
        add_action('wp_ajax_flavor_export_theme', [$this, 'ajax_export_theme']);
        add_action('wp_ajax_flavor_import_theme', [$this, 'ajax_import_theme']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Encolar CSS base de componentes
     */
    public function enqueue_base_css() {
        $ruta_archivo_css = FLAVOR_CHAT_IA_PATH . 'assets/css/core/flavor-base.css';
        if (!file_exists($ruta_archivo_css)) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-base-css',
            FLAVOR_CHAT_IA_URL . "assets/css/flavor-base{$sufijo_asset}.css",
            [],
            filemtime($ruta_archivo_css)
        );
    }

    /**
     * Generar CSS del tema activo
     */
    public function output_theme_css() {
        $theme_id = $this->active_theme;

        // En landing pages, usar el tema específico de la página
        if (is_singular('flavor_landing')) {
            $page_theme = get_post_meta(get_the_ID(), '_flavor_page_theme', true);
            if (!empty($page_theme)) {
                $theme_id = $page_theme;
            }
        }

        $theme = $this->get_theme($theme_id);

        if (!$theme) {
            $theme = $this->themes['default'];
        }

        // Obtener personalizaciones del usuario
        $customizations = get_option('flavor_theme_customizations', []);

        // Combinar variables del tema con personalizaciones
        $variables = array_merge(
            $theme['variables'] ?? [],
            $customizations
        );

        echo "<style id=\"flavor-theme-variables\">\n";
        echo ":root {\n";

        foreach ($variables as $var_name => $value) {
            // Solo permitir nombres de variables CSS válidos
            $safe_var_name = preg_replace('/[^a-zA-Z0-9\-_]/', '', $var_name);
            if (strpos($safe_var_name, '--') !== 0) {
                continue;
            }
            // Escapar valor para evitar inyección CSS
            $safe_value = str_replace(['<', '>', '"', "'", '\\', ';'], '', $value);
            echo "  {$safe_var_name}: {$safe_value};\n";
        }

        // Generar aliases de variables para compatibilidad con layouts y componentes
        echo "\n  /* Aliases de variables --flavor-* para compatibilidad */\n";
        echo "  --flavor-bg-alt: var(--flavor-bg-secondary, #f9fafb);\n";
        echo "  --flavor-text-light: var(--flavor-text-secondary, #6b7280);\n";
        echo "  --flavor-text-inverse: var(--flavor-bg, #ffffff);\n";
        echo "  --flavor-text-inverse-muted: rgba(255, 255, 255, 0.7);\n";
        echo "  --flavor-border-light: var(--flavor-border, #e5e7eb);\n";

        // Generar puente completo entre --flavor-* y --fl-* para widgets del dashboard
        echo "\n  /* Puente de variables: --flavor-* → --fl-* (design tokens) */\n";

        // Fondos
        echo "  --fl-bg-page: var(--flavor-bg-secondary, var(--fl-gray-50));\n";
        echo "  --fl-bg-card: var(--flavor-bg, var(--fl-white));\n";
        echo "  --fl-bg-card-hover: var(--flavor-bg-secondary, var(--fl-gray-50));\n";
        echo "  --fl-bg-elevated: var(--flavor-bg, var(--fl-white));\n";
        echo "  --fl-bg-muted: var(--flavor-bg-tertiary, var(--fl-gray-100));\n";
        echo "  --fl-bg-subtle: var(--flavor-bg-secondary, var(--fl-gray-50));\n";
        echo "  --fl-bg-inverse: var(--flavor-text, var(--fl-gray-900));\n";

        // Textos
        echo "  --fl-text-primary: var(--flavor-text, var(--fl-gray-900));\n";
        echo "  --fl-text-secondary: var(--flavor-text-secondary, var(--fl-gray-600));\n";
        echo "  --fl-text-muted: var(--flavor-text-muted, var(--fl-gray-500));\n";
        echo "  --fl-text-inverse: var(--flavor-bg, var(--fl-white));\n";
        echo "  --fl-text-link: var(--flavor-primary, var(--fl-primary-600));\n";
        echo "  --fl-text-link-hover: var(--flavor-primary-hover, var(--fl-primary-700));\n";

        // Bordes
        echo "  --fl-border-default: var(--flavor-border, var(--fl-gray-200));\n";
        echo "  --fl-border-muted: var(--flavor-border-light, var(--fl-gray-100));\n";
        echo "  --fl-border-focus: var(--flavor-primary, var(--fl-primary-500));\n";

        // Colores primarios con escala
        echo "  --fl-primary-500: var(--flavor-primary, #6366f1);\n";
        echo "  --fl-primary-600: var(--flavor-primary-hover, #4f46e5);\n";
        echo "  --fl-primary-700: var(--flavor-primary-dark, #4338ca);\n";
        echo "  --fl-primary-100: var(--flavor-primary-light, #e0e7ff);\n";
        echo "  --fl-primary-50: var(--flavor-primary-light, #eef2ff);\n";

        // Colores de estado
        echo "  --fl-success-500: var(--flavor-success, #22c55e);\n";
        echo "  --fl-warning-500: var(--flavor-warning, #f59e0b);\n";
        echo "  --fl-danger-500: var(--flavor-error, #ef4444);\n";
        echo "  --fl-info-500: var(--flavor-info, #3b82f6);\n";

        // Componentes específicos
        echo "  --fl-widget-bg: var(--flavor-bg, var(--fl-bg-card));\n";
        echo "  --fl-widget-border: var(--flavor-border, var(--fl-border-default));\n";
        echo "  --fl-widget-shadow: var(--flavor-shadow-sm, var(--fl-shadow-sm));\n";

        echo "}\n";
        echo "</style>\n";
    }

    /**
     * Obtener tema
     *
     * @param string $theme_id
     * @return array|null
     */
    public function get_theme($theme_id) {
        // Primero buscar en temas predefinidos
        if (isset($this->themes[$theme_id])) {
            return $this->themes[$theme_id];
        }

        // Buscar en temas personalizados
        $custom_themes = get_option('flavor_custom_themes', []);
        if (isset($custom_themes[$theme_id])) {
            return $custom_themes[$theme_id];
        }

        return null;
    }

    /**
     * Obtener todos los temas disponibles
     *
     * Devuelve tanto los temas predefinidos como los personalizados.
     *
     * @return array Todos los temas indexados por ID.
     */
    public function get_all_themes() {
        $custom_themes = get_option('flavor_custom_themes', []);
        return array_merge($this->themes, $custom_themes);
    }

    /**
     * Establecer tema activo
     *
     * @param string $theme_id
     * @return bool
     */
    public function set_active_theme($theme_id) {
        $theme = $this->get_theme($theme_id);

        if (!$theme) {
            return false;
        }

        $this->active_theme = $theme_id;
        update_option('flavor_active_theme', $theme_id);

        // Limpiar personalizaciones manuales para que el tema nuevo se aplique limpio
        delete_option('flavor_theme_customizations');
        delete_option('flavor_design_settings');

        do_action('flavor_theme_changed', $theme_id, $theme);

        return true;
    }

    /**
     * AJAX: Obtener temas
     */
    public function ajax_get_themes() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        $custom_themes = get_option('flavor_custom_themes', []);
        $all_themes = array_merge($this->themes, $custom_themes);

        // Obtener filtro de categoría si se proporciona
        $filtro_categoria = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'all';

        $themes_list = [];
        foreach ($all_themes as $theme_id => $theme) {
            $categoria_tema = $theme['category'] ?? 'general';

            // Filtrar por categoría si se especifica
            if ($filtro_categoria !== 'all' && $categoria_tema !== $filtro_categoria) {
                continue;
            }

            $themes_list[$theme_id] = [
                'id' => $theme_id,
                'name' => $theme['name'],
                'description' => $theme['description'] ?? '',
                'preview' => $theme['preview'] ?? '',
                'is_custom' => isset($custom_themes[$theme_id]),
                'is_active' => $theme_id === $this->active_theme,
                'category' => $categoria_tema,
                'category_label' => $theme['category_label'] ?? __('General', 'flavor-chat-ia'),
                'ideal_for' => $theme['ideal_for'] ?? '',
                'font_family_headings' => $theme['font_family_headings'] ?? 'Inter',
                'font_family_body' => $theme['font_family_body'] ?? 'Inter',
            ];
        }

        wp_send_json_success([
            'themes' => $themes_list,
            'active_theme' => $this->active_theme,
            'customizations' => get_option('flavor_theme_customizations', []),
            'categories' => $this->get_available_categories(),
        ]);
    }

    /**
     * AJAX: Establecer tema
     */
    public function ajax_set_theme() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $theme_id = sanitize_text_field($_POST['theme_id'] ?? '');

        if (!$theme_id) {
            wp_send_json_error(['message' => __('ID de tema requerido', 'flavor-chat-ia')]);
        }

        $result = $this->set_active_theme($theme_id);

        if ($result) {
            wp_send_json_success(['message' => __('Tema aplicado', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Tema no encontrado', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Guardar tema personalizado
     */
    public function ajax_save_custom_theme() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $theme_id = sanitize_key($_POST['theme_id'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $description = sanitize_text_field($_POST['description'] ?? '');

        // Validar y sanitizar variables CSS
        $variables_raw = isset($_POST['variables']) && is_array($_POST['variables']) ? $_POST['variables'] : [];

        if (!$theme_id || !$name) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        // No permitir sobrescribir temas predefinidos
        if (isset($this->themes[$theme_id])) {
            $theme_id = 'custom_' . $theme_id . '_' . time();
        }

        // Sanitizar variables CSS - solo permitir variables --flavor-*
        $sanitized_variables = [];
        foreach ($variables_raw as $var_name => $value) {
            $var_name_clean = sanitize_key($var_name);
            if (strpos($var_name_clean, '--flavor-') === 0) {
                $sanitized_variables[$var_name_clean] = sanitize_text_field($value);
            }
        }

        $custom_themes = get_option('flavor_custom_themes', []);
        $custom_themes[$theme_id] = [
            'name' => $name,
            'description' => $description,
            'variables' => $sanitized_variables,
            'created_at' => current_time('mysql'),
        ];

        update_option('flavor_custom_themes', $custom_themes);

        wp_send_json_success([
            'message' => __('Tema guardado', 'flavor-chat-ia'),
            'theme_id' => $theme_id,
        ]);
    }

    /**
     * AJAX: Eliminar tema personalizado
     */
    public function ajax_delete_custom_theme() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $theme_id = sanitize_text_field($_POST['theme_id'] ?? '');

        // No permitir eliminar temas predefinidos
        if (isset($this->themes[$theme_id])) {
            wp_send_json_error(['message' => __('No se puede eliminar un tema predefinido', 'flavor-chat-ia')]);
        }

        $custom_themes = get_option('flavor_custom_themes', []);

        if (!isset($custom_themes[$theme_id])) {
            wp_send_json_error(['message' => __('Tema no encontrado', 'flavor-chat-ia')]);
        }

        // Si es el tema activo, cambiar al default
        if ($this->active_theme === $theme_id) {
            $this->set_active_theme('default');
        }

        unset($custom_themes[$theme_id]);
        update_option('flavor_custom_themes', $custom_themes);

        wp_send_json_success(['message' => __('Tema eliminado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Exportar tema
     */
    public function ajax_export_theme() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        $theme_id = sanitize_text_field($_POST['theme_id'] ?? '');
        $theme = $this->get_theme($theme_id);

        if (!$theme) {
            wp_send_json_error(['message' => __('Tema no encontrado', 'flavor-chat-ia')]);
        }

        $export_data = [
            'id' => $theme_id,
            'name' => $theme['name'],
            'description' => $theme['description'] ?? '',
            'variables' => $theme['variables'],
            'exported_at' => current_time('c'),
            'flavor_version' => FLAVOR_CHAT_IA_VERSION,
        ];

        wp_send_json_success([
            'data' => $export_data,
            'filename' => 'flavor-theme-' . $theme_id . '.json',
        ]);
    }

    /**
     * AJAX: Importar tema
     */
    public function ajax_import_theme() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $theme_data_raw = isset($_POST['theme_data']) ? sanitize_textarea_field(wp_unslash($_POST['theme_data'])) : '';

        if (!$theme_data_raw) {
            wp_send_json_error(['message' => __('Datos de tema requeridos', 'flavor-chat-ia')]);
        }

        $theme = json_decode($theme_data_raw, true);

        // Validar estructura del JSON
        if (!is_array($theme) || !isset($theme['name']) || !isset($theme['variables'])) {
            wp_send_json_error(['message' => __('Formato de tema inválido', 'flavor-chat-ia')]);
        }

        // Validar que variables sea un array
        if (!is_array($theme['variables'])) {
            wp_send_json_error(['message' => __('Las variables deben ser un array', 'flavor-chat-ia')]);
        }

        // Generar ID único
        $theme_id = 'imported_' . sanitize_key($theme['name']) . '_' . time();

        // Sanitizar variables - solo permitir variables CSS válidas
        $sanitized_variables = [];
        foreach ($theme['variables'] as $var_key => $var_value) {
            $clean_key = sanitize_key($var_key);
            if (!empty($clean_key)) {
                $sanitized_variables[$clean_key] = sanitize_text_field($var_value);
            }
        }

        $custom_themes = get_option('flavor_custom_themes', []);
        $custom_themes[$theme_id] = [
            'name' => sanitize_text_field($theme['name']),
            'description' => sanitize_text_field($theme['description'] ?? ''),
            'variables' => $sanitized_variables,
            'imported_at' => current_time('mysql'),
        ];

        update_option('flavor_custom_themes', $custom_themes);

        wp_send_json_success([
            'message' => __('Tema importado', 'flavor-chat-ia'),
            'theme_id' => $theme_id,
        ]);
    }

    /**
     * Guardar personalizaciones
     *
     * @param array $customizations
     * @return bool
     */
    public function save_customizations($customizations) {
        $sanitized = [];
        foreach ($customizations as $var_name => $value) {
            if (strpos($var_name, '--flavor-') === 0) {
                $sanitized[$var_name] = sanitize_text_field($value);
            }
        }
        return update_option('flavor_theme_customizations', $sanitized);
    }

    /**
     * Limpiar personalizaciones
     */
    public function clear_customizations() {
        return delete_option('flavor_theme_customizations');
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/themes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_themes'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/themes/active', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_get_active_theme'],
                'permission_callback' => [$this, 'public_permission_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'rest_set_active_theme'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);
    }

    /**
     * REST: Obtener temas
     */
    public function rest_get_themes($request) {
        $custom_themes = get_option('flavor_custom_themes', []);
        $all_themes = array_merge($this->themes, $custom_themes);

        return rest_ensure_response($all_themes);
    }

    /**
     * REST: Obtener tema activo
     */
    public function rest_get_active_theme($request) {
        $theme = $this->get_theme($this->active_theme);

        return rest_ensure_response([
            'id' => $this->active_theme,
            'theme' => $theme,
        ]);
    }

    /**
     * REST: Establecer tema activo
     */
    public function rest_set_active_theme($request) {
        $theme_id = $request->get_param('theme_id');

        if (!$theme_id) {
            return new WP_Error('invalid_request', 'theme_id requerido', ['status' => 400]);
        }

        $result = $this->set_active_theme($theme_id);

        if (!$result) {
            return new WP_Error('not_found', 'Tema no encontrado', ['status' => 404]);
        }

        return rest_ensure_response([
            'message' => __('Tema guardado', 'flavor-chat-ia'),
            'active_theme' => $this->active_theme,
        ]);
    }

    /**
     * Obtener todos los temas
     */
    public function get_themes() {
        $custom_themes = get_option('flavor_custom_themes', []);
        return array_merge($this->themes, $custom_themes);
    }

    /**
     * Obtener tema activo
     */
    public function get_active_theme() {
        return $this->active_theme;
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

/**
 * Helper global
 */
function flavor_theme_var($var_name, $default = '') {
    $theme_manager = Flavor_Theme_Manager::get_instance();
    $theme = $theme_manager->get_theme($theme_manager->get_active_theme());

    if ($theme && isset($theme['variables'][$var_name])) {
        return $theme['variables'][$var_name];
    }

    return $default;
}
